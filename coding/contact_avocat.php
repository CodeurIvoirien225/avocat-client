<?php
session_start();
require_once __DIR__ . '/config/config.php'; 
require_once __DIR__ . '/vendor/autoload.php'; // PHPMailer autoload
$config = require __DIR__ . '/mot_de_passe_application.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Vérifier que le client est connecté
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'client') {
    header("Location: connexion_client.php");
    exit;
}

$client_id = (int) $_SESSION['user_id'];

// Récupérer la clé et SMTP depuis config sécurisé
$smtp_user      = $config['smtp_user'] ?? null;
$smtp_password  = $config['smtp_password'] ?? null;
$mail_from_name = $config['mail_from_name'] ?? 'JustisConnect';
$secret_phrase  = $config['encryption_key'] ?? null;

if (!$secret_phrase) {
    die("Clé de chiffrement manquante. Voir mot_de_passe_application.php");
}

// Dériver clé 32 bytes à partir de la phrase (SHA-256)
$cipher = "AES-256-CBC";
$key = hash('sha256', $secret_phrase, true);

// Récupérer l'ID de l'avocat depuis GET
$avocat_id = filter_input(INPUT_GET, 'avocat_id', FILTER_VALIDATE_INT);
if (!$avocat_id) {
    die("Avocat invalide.");
}

// Récupérer les infos de l'avocat
$stmt = $pdo->prepare("SELECT * FROM avocat WHERE id = ?");
$stmt->execute([$avocat_id]);
$avocat = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$avocat) {
    die("Avocat introuvable.");
}

// Récupérer infos client depuis DB pour pré-remplir (plutôt que la session)
$stmtC = $pdo->prepare("SELECT * FROM client WHERE id = ?");
$stmtC->execute([$client_id]);
$client = $stmtC->fetch(PDO::FETCH_ASSOC);

if (!$client) {
    $client = [
        'prenom' => '',
        'nom' => '',
        'email' => ''
    ];
}

// si client introuvable (au cas où), on prend des valeurs vides
$client_nom   = $client['prenom'] . ' ' . $client['nom'] ?? '';
$client_email = $client['email'] ?? '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>Contacter l'avocat</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
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
    
    .loading {
        opacity: 0.6;
        pointer-events: none;
    }
  </style>
</head>
<body class="bg-light">
      <?php include 'navbar.php'; ?>
<div class="container py-5">
  <h2>Contacter l'avocat : <?= htmlspecialchars($avocat['prenom'] . ' ' . $avocat['nom']) ?></h2>

  <div id="alert-container"></div>

  <form id="contact-form" class="mt-3">
    <input type="hidden" name="avocat_id" value="<?= $avocat_id ?>">
    <input type="hidden" name="client_id" value="<?= $client_id ?>">
    
    <div class="mb-3">
      <label class="form-label">Votre nom</label>
      <input type="text" name="nom" class="form-control" value="<?= htmlspecialchars($client_nom) ?>" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Votre email</label>
      <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($client_email) ?>" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Message</label>
      <textarea name="message" rows="6" class="form-control" required></textarea>
    </div>

    <button type="submit" class="btn btn-primary" id="submit-btn">
        <span id="btn-text">Envoyer</span>
        <span id="btn-loading" style="display: none;">Envoi en cours...</span>
    </button>
    <a href="profil_avocat.php?id=<?= $avocat_id ?>" class="btn btn-secondary ms-2">Retour au profil</a>
  </form>
</div>

<script>
document.getElementById('contact-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const form = e.target;
    const submitBtn = document.getElementById('submit-btn');
    const btnText = document.getElementById('btn-text');
    const btnLoading = document.getElementById('btn-loading');
    const alertContainer = document.getElementById('alert-container');
    
    // Afficher l'indicateur de chargement
    btnText.style.display = 'none';
    btnLoading.style.display = 'inline';
    submitBtn.disabled = true;
    form.classList.add('loading');
    
    try {
        const formData = new FormData(form);
        
        const response = await fetch('ajax_contact_avocat.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Afficher le popup de succès
            showSuccessPopup('Votre message a été envoyé avec succès !');
            
            // Vider le formulaire
            form.reset();
            
            // Réinitialiser les valeurs par défaut
            document.querySelector('input[name="nom"]').value = '<?= htmlspecialchars($client_nom) ?>';
            document.querySelector('input[name="email"]').value = '<?= htmlspecialchars($client_email) ?>';
            
        } else {
            // Afficher l'erreur
            showAlert(data.error, 'danger');
        }
        
    } catch (error) {
        console.error('Erreur:', error);
        showAlert('Erreur réseau lors de l\'envoi du message', 'danger');
    } finally {
        // Masquer l'indicateur de chargement
        btnText.style.display = 'inline';
        btnLoading.style.display = 'none';
        submitBtn.disabled = false;
        form.classList.remove('loading');
    }
});

// Fonction pour afficher les alertes
function showAlert(message, type) {
    const alertContainer = document.getElementById('alert-container');
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    alertContainer.innerHTML = '';
    alertContainer.appendChild(alertDiv);
    
    // Supprimer automatiquement après 5 secondes
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

// Fonction pour afficher un popup de succès
function showSuccessPopup(message) {
    // Créer l'overlay (fond semi-transparent)
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

    // Créer le popup
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

    // Contenu du popup
    popup.innerHTML = `
        <div style="color: #28a745; font-size: 3rem; margin-bottom: 1rem;">✓</div>
        <h5 style="color: #28a745; margin-bottom: 1rem;">Succès !</h5>
        <p style="margin-bottom: 1.5rem;">${message}</p>
        <button id="close-popup-btn" class="btn btn-success" style="width: 100%;">OK</button>
    `;

    // Ajouter le popup à l'overlay
    overlay.appendChild(popup);
    
    // Ajouter l'overlay au body
    document.body.appendChild(overlay);

    // Animation d'entrée
    setTimeout(() => {
        overlay.style.opacity = '1';
        popup.style.transform = 'scale(1)';
    }, 10);

    // Fermer le popup quand on clique sur OK
    const closeBtn = popup.querySelector('#close-popup-btn');
    closeBtn.addEventListener('click', closePopup);

    // Fermer le popup quand on clique en dehors
    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) {
            closePopup();
        }
    });

    // Fermer avec la touche Escape
    document.addEventListener('keydown', handleEscapeKey);

    function closePopup() {
        // Animation de sortie
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

    // Fermer automatiquement après 3 secondes
    setTimeout(closePopup, 3000);
}
</script>

</body>
</html>