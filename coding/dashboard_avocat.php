<?php
session_start();
require_once 'config/config.php';
require_once __DIR__ . '/vendor/autoload.php';
$config = require __DIR__ . '/mot_de_passe_application.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Vérifier si l'avocat est connecté
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'avocat') {
    header("Location: connexion_avocat.php");
    exit;
}

$avocat_id = (int)$_SESSION['user_id'];
$secret_key = $config['encryption_key'];
$cipher = "AES-256-CBC";

// Récupérer l'ID du client sélectionné (s'il y en a un)
$selected_client_id = isset($_GET['client_id']) ? (int)$_GET['client_id'] : null;

// suppression de conversation
if (isset($_POST['supprimer_conversation'])) {
    $client_id = (int)$_POST['client_id'];
    $stmt = $pdo->prepare("DELETE FROM conversation WHERE id_avocat = ? AND id_client = ?");
    $stmt->execute([$avocat_id, $client_id]);
    $_SESSION['success'] = "Conversation supprimée avec succès !";
    header("Location: dashboard_avocat.php" . ($selected_client_id ? "?client_id=" . $selected_client_id : ""));
    exit;
}

// Fonction pour décrypter avec gestion d'erreur
function decryptMessage($data, $key, $iv) {
    $decrypted = openssl_decrypt(base64_decode($data), "AES-256-CBC", hash('sha256', $key, true), OPENSSL_RAW_DATA, base64_decode($iv));
    return $decrypted !== false ? $decrypted : "[Erreur de déchiffrement]";
}

// Fonction pour récupérer le dernier message (pour l'aperçu)
function getLastMessagePreview($pdo, $avocat_id, $client_id, $secret_key) {
    $stmt = $pdo->prepare("SELECT message, iv, date_envoi FROM conversation 
                          WHERE id_avocat = ? AND id_client = ? 
                          ORDER BY date_envoi DESC LIMIT 1");
    $stmt->execute([$avocat_id, $client_id]);
    $last_msg = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($last_msg) {
        $decrypted = decryptMessage($last_msg['message'], $secret_key, $last_msg['iv']);
        // Limiter la longueur pour l'aperçu
        return strlen($decrypted) > 50 ? substr($decrypted, 0, 50) . "..." : $decrypted;
    }
    return "Aucun message";
}

// ==================
// Récupérer tous les clients avec conversations
// ==================
$stmtClients = $pdo->prepare("
    SELECT DISTINCT c.id, c.prenom, c.nom, c.email,
           (SELECT COUNT(*) FROM conversation WHERE id_avocat = ? AND id_client = c.id AND expediteur = 'client' AND lu = 0) as nouveaux_messages
    FROM conversation conv
    JOIN client c ON conv.id_client = c.id
    WHERE conv.id_avocat = ?
    ORDER BY (SELECT MAX(date_envoi) FROM conversation WHERE id_avocat = ? AND id_client = c.id) DESC
");
$stmtClients->execute([$avocat_id, $avocat_id, $avocat_id]);
$clients = $stmtClients->fetchAll(PDO::FETCH_ASSOC);

// Marquer les messages comme lus si un client est sélectionné
if ($selected_client_id) {
    $stmt = $pdo->prepare("UPDATE conversation SET lu = 1 WHERE id_avocat = ? AND id_client = ? AND expediteur = 'client'");
    $stmt->execute([$avocat_id, $selected_client_id]);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Avocat</title>
    <link rel="icon" type="image/x-icon" href="icons/balance.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .message-avocat { background:#e8f0ff; padding:8px; border-radius:6px; margin-bottom:6px; }
        .message-client { background:#f0f7ea; padding:8px; border-radius:6px; margin-bottom:6px; }
        .conversation-box { max-height:400px; overflow-y:auto; padding:10px; border:1px solid #ddd; border-radius:6px; }
        
        /* Nouveaux styles pour la mise en page */
        .conversations-sidebar {
            border-right: 1px solid #dee2e6;
            height: calc(100vh - 100px);
            overflow-y: auto;
        }
        .conversation-item {
            padding: 12px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .conversation-item:hover {
            background-color: #f8f9fa;
        }
        .conversation-item.active {
            background-color: #e3f2fd;
            border-left: 4px solid #0d6efd;
        }
        .conversation-preview {
            font-size: 0.9em;
            color: #6c757d;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .client-name {
            font-weight: 600;
            color: #333;
        }
        .unread-badge {
            background-color: #dc3545;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8em;
            margin-left: auto;
        }
        .conversation-header {
            background-color: #f8f9fa;
            padding: 15px;
            border-bottom: 1px solid #dee2e6;
        }
        
        /* Styles pour le popup personnalisé */
        #success-popup-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        #success-popup {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            transform: scale(0.7);
            transition: transform 0.3s ease;
            max-width: 90%;
            width: 300px;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: #dee2e6;
        }

        /* Nouveaux styles pour la disposition fixe */
.card.h-100 {
    height: calc(100vh - 150px) !important;
}

.conversation-box.flex-grow-1 {
    min-height: 0; /* Important pour le flex-grow */
}

.card-footer {
    background-color: #f8f9fa;
    border-top: 1px solid #dee2e6;
    position: sticky;
    bottom: 0;
    z-index: 10;
}

/* Assurer que la zone de message reste utilisable */
.conversation-box {
    padding-bottom: 20px;
}

/* Responsive */
@media (max-height: 700px) {
    .card.h-100 {
        height: calc(100vh - 120px) !important;
    }
    
    textarea[name="message"] {
        rows: 2;
        max-height: 80px;
    }
}

    </style>
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="container-fluid py-4">
    <!-- Affichage du message de succès -->
    <?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success"><?= $_SESSION['success']; ?></div>
    <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-4">
            <div class="conversations-sidebar">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4>Conversations</h4>
                    <span class="badge bg-primary"><?= count($clients) ?></span>
                </div>
                
                <?php if (empty($clients)): ?>
                    <div class="empty-state">
                        <i class="fas fa-comments"></i>
                        <h5>Aucune conversation</h5>
                        <p>Vous n'avez pas encore de messages de clients.</p>
                    </div>
                <?php else: ?>
                    <?php foreach($clients as $client): ?>
                        <div class="conversation-item <?= $selected_client_id == $client['id'] ? 'active' : '' ?>" 
                             onclick="selectConversation(<?= $client['id'] ?>)">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <div class="client-name">
                                        <?= htmlspecialchars($client['prenom'] . ' ' . $client['nom']) ?>
                                        <?php if ($client['nouveaux_messages'] > 0): ?>
                                            <span class="unread-badge"><?= $client['nouveaux_messages'] ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="conversation-preview">
                                        <?= htmlspecialchars(getLastMessagePreview($pdo, $avocat_id, $client['id'], $secret_key)) ?>
                                    </div>
                                    <small class="text-muted"><?= htmlspecialchars($client['email']) ?></small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="col-md-8">
            <?php if ($selected_client_id && $clients): ?>
                <?php 
                // Trouver le client sélectionné
                $selected_client = null;
                foreach ($clients as $client) {
                    if ($client['id'] == $selected_client_id) {
                        $selected_client = $client;
                        break;
                    }
                }
                
                if ($selected_client): ?>
                <div class="card h-100 d-flex flex-column">
    <div class="conversation-header d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0">Conversation avec <?= htmlspecialchars($selected_client['prenom'] . ' ' . $selected_client['nom']) ?></h5>
            <small class="text-muted"><?= htmlspecialchars($selected_client['email']) ?></small>
        </div>
        <form method="POST" onsubmit="return confirm('Voulez-vous vraiment supprimer cette conversation ?');">
            <input type="hidden" name="client_id" value="<?= $selected_client['id'] ?>">
            <button type="submit" name="supprimer_conversation" class="btn btn-danger btn-sm">
                <i class="fas fa-trash"></i> Supprimer
            </button>
        </form>
    </div>
    
    <div class="card-body conversation-box flex-grow-1" id="conversation-content" style="overflow-y: auto;">
        <?php
        // Récupérer messages de ce client uniquement
        $stmt = $pdo->prepare("SELECT * FROM conversation WHERE id_avocat = ? AND id_client = ? ORDER BY date_envoi ASC");
        $stmt->execute([$avocat_id, $selected_client['id']]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach($messages as $msg):
            $msg_dechiffre = decryptMessage($msg['message'], $secret_key, $msg['iv']);
            $display_html = nl2br(htmlspecialchars($msg_dechiffre));
            if ($msg['expediteur'] === 'avocat') {
                echo "<div class='message-avocat'><strong>Vous :</strong><br>$display_html<br><small class='text-muted'>{$msg['date_envoi']}</small></div>";
            } else {
                echo "<div class='message-client'><strong>Client :</strong><br>$display_html<br><small class='text-muted'>{$msg['date_envoi']}</small></div>";
            }
        endforeach;
        ?>
    </div>

    <div class="card-footer" style="flex-shrink: 0;">
        <form class="envoyer-message-form d-flex flex-column gap-2" data-client-id="<?= $selected_client['id'] ?>" data-role="avocat">
            <textarea name="message" rows="3" class="form-control" placeholder="Écrire une réponse..." required></textarea>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-paper-plane"></i> Envoyer
            </button>
        </form>
    </div>
</div>
                <?php endif; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-comment"></i>
                    <h5>Sélectionnez une conversation</h5>
                    <p>Choisissez une conversation dans la liste pour commencer à discuter.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function selectConversation(clientId) {
    window.location.href = 'dashboard_avocat.php?client_id=' + clientId;
}

// Faire défiler vers le bas de la conversation
function scrollToBottom() {
    const conversationBox = document.getElementById('conversation-content');
    if (conversationBox) {
        conversationBox.scrollTop = conversationBox.scrollHeight;
    }
}

// Au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    scrollToBottom();
});

document.querySelectorAll('.envoyer-message-form').forEach(form => {
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const textarea = form.querySelector('textarea[name="message"]');
        const message = textarea.value.trim();
        if (!message) return;

        const client_id = form.dataset.clientId;
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;

        // Désactiver le bouton pendant l'envoi
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Envoi...';

        try {
            const formData = new FormData();
            formData.append('message', message);
            formData.append('client_id', client_id);
            formData.append('avocat_id', <?= $avocat_id ?>);

            const res = await fetch('ajax_envoyer_message_avocat.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await res.json();
            
            if (data.success) {
                // Ajouter le message à la conversation
                const box = document.getElementById('conversation-content');
                const div = document.createElement('div');
                div.className = 'message-avocat';
                div.innerHTML = `<strong>Vous :</strong><br>${data.message}<br><small class="text-muted">${data.date_envoi}</small>`;
                box.appendChild(div);
                scrollToBottom();

                // Vider le textarea
                textarea.value = '';

                // Afficher le popup de succès
                showSuccessPopup('Message envoyé avec succès !');
                
                // Mettre à jour le badge des nouveaux messages
                updateUnreadBadges();
                
            } else {
                alert(data.error || 'Erreur lors de l\'envoi');
            }
        } catch (err) {
            console.error(err);
            alert('Erreur réseau');
        } finally {
            // Réactiver le bouton
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    });
});

function updateUnreadBadges() {
    // Réduire le badge du client actuel (les messages sont maintenant lus)
    const currentBadge = document.querySelector('.conversation-item.active .unread-badge');
    if (currentBadge) {
        const count = parseInt(currentBadge.textContent) - 1;
        if (count > 0) {
            currentBadge.textContent = count;
        } else {
            currentBadge.remove();
        }
    }
}

// Fonction pour afficher un popup de succès (identique à votre version)
// Fonction pour afficher un popup de succès
function showSuccessPopup(message) {
    const overlay = document.createElement('div');
    overlay.id = 'success-popup-overlay';
    overlay.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 9999;
        opacity: 0;
        transition: opacity 0.3s ease;
    `;

    const popup = document.createElement('div');
    popup.id = 'success-popup';
    popup.style.cssText = `
        background: white;
        padding: 2rem;
        border-radius: 10px;
        text-align: center;
        box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        transform: scale(0.7);
        transition: transform 0.3s ease;
        max-width: 90%;
        width: 300px;
    `;

    popup.innerHTML = `
        <div style="color: #28a745; font-size: 3rem; margin-bottom: 1rem;">✓</div>
        <h5 style="color: #28a745; margin-bottom: 1rem;">Succès !</h5>
        <p style="margin-bottom: 1.5rem;">${message}</p>
        <button id="close-popup-btn" class="btn btn-success" style="width: 100%;">OK</button>
    `;

    overlay.appendChild(popup);
    document.body.appendChild(overlay);

    setTimeout(() => {
        overlay.style.opacity = '1';
        popup.style.transform = 'scale(1)';
    }, 10);

    const closeBtn = popup.querySelector('#close-popup-btn');
    closeBtn.addEventListener('click', closePopup);

    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) {
            closePopup();
        }
    });

    document.addEventListener('keydown', handleEscapeKey);

    function closePopup() {
        overlay.style.opacity = '0';
        popup.style.transform = 'scale(0.7)';
        
        setTimeout(() => {
            if (overlay.parentNode) {
                overlay.remove();
            }
            document.removeEventListener('keydown', handleEscapeKey);
        }, 300);
    }

    function handleEscapeKey(e) {
        if (e.key === 'Escape') {
            closePopup();
        }
    }

    setTimeout(closePopup, 3000);
}
</script>

</body>
</html>