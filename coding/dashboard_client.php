<?php
session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/vendor/autoload.php';
$config = require __DIR__ . '/mot_de_passe_application.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// --- session / identification du client (gère plusieurs noms possibles de session)
$client_id = null;
if (isset($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'client') {
    $client_id = (int)$_SESSION['user_id'];
} elseif (isset($_SESSION['id_client'])) {
    $client_id = (int)$_SESSION['id_client'];
}

if (!$client_id) {
    // Pas connecté en tant que client -> redirection vers page de connexion (à adapter)
    header("Location: connexion_client.php");
    exit;
}

// Récupérer l'ID de l'avocat sélectionné (s'il y en a un)
$selected_avocat_id = isset($_GET['avocat_id']) ? (int)$_GET['avocat_id'] : null;

// ==================
// MARQUER TOUS LES MESSAGES COMME LUS QUAND LE CLIENT CONSULTE LE DASHBOARD
// ==================
$stmt_marquer_lus = $pdo->prepare("UPDATE conversation SET lu = 1 WHERE id_client = ? AND expediteur = 'avocat' AND lu = 0");
$stmt_marquer_lus->execute([$client_id]);

// supprimer conversation client 
if (isset($_POST['supprimer_conversation'])) {
    $avocat_id = (int)($_POST['avocat_id'] ?? 0);
    if ($avocat_id > 0) {
        $stmtDel = $pdo->prepare("DELETE FROM conversation WHERE id_client = ? AND id_avocat = ?");
        $stmtDel->execute([$client_id, $avocat_id]);
        $_SESSION['success'] = "Conversation supprimée avec succès !";
        header("Location: dashboard_client.php" . ($selected_avocat_id ? "?avocat_id=" . $selected_avocat_id : ""));
        exit;
    }
}

// --- chiffrement
$secret_key = $config['encryption_key'] ?? null;
$cipher = "AES-256-CBC";

// Fonction de déchiffrement (ordre des paramètres : $data, $secret_key, $iv_base64)
function decryptMessage(string $data, ?string $secret_key, string $iv_b64) {
    if (!$data || !$secret_key || !$iv_b64) {
        return false;
    }
    $key = hash('sha256', $secret_key, true);
    $raw = base64_decode($data, true);
    $iv  = base64_decode($iv_b64, true);
    if ($raw === false || $iv === false) return false;
    $decrypted = openssl_decrypt($raw, "AES-256-CBC", $key, OPENSSL_RAW_DATA, $iv);
    return $decrypted === false ? false : $decrypted;
}

// Fonction pour récupérer le dernier message (pour l'aperçu)
function getLastMessagePreview($pdo, $client_id, $avocat_id, $secret_key) {
    $stmt = $pdo->prepare("SELECT message, iv, date_envoi FROM conversation 
                          WHERE id_client = ? AND id_avocat = ? 
                          ORDER BY date_envoi DESC LIMIT 1");
    $stmt->execute([$client_id, $avocat_id]);
    $last_msg = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($last_msg) {
        $decrypted = decryptMessage($last_msg['message'], $secret_key, $last_msg['iv']);
        if ($decrypted === false) return "Message chiffré";
        // Limiter la longueur pour l'aperçu
        return strlen($decrypted) > 50 ? substr($decrypted, 0, 50) . "..." : $decrypted;
    }
    return "Aucun message";
}

// ----- Récupérer la liste des avocats avec qui le client a conversé
$stmtAv = $pdo->prepare("
    SELECT DISTINCT conv.id_avocat AS avocat_id, a.prenom, a.nom, a.email,
           (SELECT COUNT(*) FROM conversation WHERE id_client = ? AND id_avocat = a.id AND expediteur = 'avocat' AND lu = 0) as nouveaux_messages
    FROM conversation conv
    LEFT JOIN avocat a ON conv.id_avocat = a.id
    WHERE conv.id_client = ?
    ORDER BY (SELECT MAX(date_envoi) FROM conversation WHERE id_client = ? AND id_avocat = a.id) DESC
");
$stmtAv->execute([$client_id, $client_id, $client_id]);
$avocats = $stmtAv->fetchAll(PDO::FETCH_ASSOC);

// Marquer les messages comme lus si un avocat est sélectionné
if ($selected_avocat_id) {
    $stmt = $pdo->prepare("UPDATE conversation SET lu = 1 WHERE id_client = ? AND id_avocat = ? AND expediteur = 'avocat'");
    $stmt->execute([$client_id, $selected_avocat_id]);
}

// --- HTML / affichage
?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Dashboard Client</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .message-client { background:#e8f0ff; padding:8px; border-radius:6px; margin-bottom:6px; }
        .message-avocat { background:#f0f7ea; padding:8px; border-radius:6px; margin-bottom:6px; }
        
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
        .avocat-name {
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
        
        /* Styles pour la disposition fixe */
        .card.h-100 {
            height: calc(100vh - 150px) !important;
        }
        .conversation-box.flex-grow-1 {
            min-height: 0;
            overflow-y: auto;
        }
        .card-footer {
            background-color: #f8f9fa;
            border-top: 1px solid #dee2e6;
            position: sticky;
            bottom: 0;
            z-index: 10;
            flex-shrink: 0;
        }
        
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
                    <span class="badge bg-primary"><?= count($avocats) ?></span>
                </div>
                
                <?php if (empty($avocats)): ?>
                    <div class="empty-state">
                        <i class="fas fa-comments"></i>
                        <h5>Aucune conversation</h5>
                        <p>Vous n'avez pas encore de conversations avec des avocats.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($avocats as $avocat): ?>
                        <div class="conversation-item <?= $selected_avocat_id == $avocat['avocat_id'] ? 'active' : '' ?>" 
                             onclick="selectConversation(<?= $avocat['avocat_id'] ?>)">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <div class="avocat-name">
                                        <?= htmlspecialchars(trim(($avocat['prenom'] ?? '') . ' ' . ($avocat['nom'] ?? ''))) ?>
                                        <?php if ($avocat['nouveaux_messages'] > 0): ?>
                                            <span class="unread-badge"><?= $avocat['nouveaux_messages'] ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="conversation-preview">
                                        <?= htmlspecialchars(getLastMessagePreview($pdo, $client_id, $avocat['avocat_id'], $secret_key)) ?>
                                    </div>
                                    <?php if (!empty($avocat['email'])): ?>
                                        <small class="text-muted"><?= htmlspecialchars($avocat['email']) ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="col-md-8">
            <?php if ($selected_avocat_id && $avocats): ?>
                <?php 
                // Trouver l'avocat sélectionné
                $selected_avocat = null;
                foreach ($avocats as $avocat) {
                    if ($avocat['avocat_id'] == $selected_avocat_id) {
                        $selected_avocat = $avocat;
                        break;
                    }
                }
                
                if ($selected_avocat): ?>
                <div class="card h-100 d-flex flex-column">
                    <div class="conversation-header d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">Conversation avec <?= htmlspecialchars(trim(($selected_avocat['prenom'] ?? '') . ' ' . ($selected_avocat['nom'] ?? ''))) ?></h5>
                            <?php if (!empty($selected_avocat['email'])): ?>
                                <small class="text-muted"><?= htmlspecialchars($selected_avocat['email']) ?></small>
                            <?php endif; ?>
                        </div>
                        <form method="POST" onsubmit="return confirm('Voulez-vous vraiment supprimer cette conversation ?');">
                            <input type="hidden" name="avocat_id" value="<?= $selected_avocat['avocat_id'] ?>">
                            <button type="submit" name="supprimer_conversation" class="btn btn-danger btn-sm">
                                <i class="fas fa-trash"></i> Supprimer
                            </button>
                        </form>
                    </div>
                    
                    <div class="card-body conversation-box flex-grow-1" id="conversation-content">
                        <?php
                        // Récupérer tous les messages client <-> avocat
                        $stmtMsg = $pdo->prepare("SELECT * FROM conversation WHERE id_client = ? AND id_avocat = ? ORDER BY date_envoi ASC");
                        $stmtMsg->execute([$client_id, $selected_avocat_id]);
                        $messages = $stmtMsg->fetchAll(PDO::FETCH_ASSOC);

                        if (empty($messages)): ?>
                            <p class="text-muted">Aucun message enregistré pour cette conversation.</p>
                        <?php else: ?>
                            <?php foreach ($messages as $msg): ?>
                                <?php
                                    $dech = decryptMessage($msg['message'], $secret_key, $msg['iv']);
                                    $display = $dech === false ? "[Erreur de déchiffrement]" : $dech;
                                    $display_html = nl2br(htmlspecialchars($display));
                                    if ($msg['expediteur'] === 'client') {
                                        echo "<div class='message-client'><strong>Vous :</strong><br>$display_html<br><small class='text-muted'>{$msg['date_envoi']}</small></div>";
                                    } else {
                                        echo "<div class='message-avocat'><strong>Avocat :</strong><br>$display_html<br><small class='text-muted'>{$msg['date_envoi']}</small></div>";
                                    }
                                ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <div class="card-footer">
                        <form class="envoyer-message-form d-flex flex-column gap-2" data-avocat-id="<?= $selected_avocat_id ?>" data-role="client">
                            <textarea name="message" rows="3" class="form-control" placeholder="Votre réponse..." required></textarea>
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
function selectConversation(avocatId) {
    window.location.href = 'dashboard_client.php?avocat_id=' + avocatId;
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

        const avocat_id = form.dataset.avocatId;
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;

        // Désactiver le bouton pendant l'envoi
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Envoi...';

        try {
            const formData = new FormData();
            formData.append('message', message);
            formData.append('avocat_id', avocat_id);

            const res = await fetch('ajax_envoyer_message.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await res.json();
            
            if (data.success) {
                // Ajouter le message à la conversation
                const box = document.getElementById('conversation-content');
                const div = document.createElement('div');
                div.className = 'message-client';
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
    // Réduire le badge de l'avocat actuel (les messages sont maintenant lus)
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