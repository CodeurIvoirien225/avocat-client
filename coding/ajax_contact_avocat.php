<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config/config.php'; 
require_once __DIR__ . '/vendor/autoload.php';
$config = require __DIR__ . '/mot_de_passe_application.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

try {
    // Vérifier que le client est connecté
    if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'client') {
        echo json_encode(['success' => false, 'error' => 'Non connecté']);
        exit;
    }

    $client_id = (int) $_SESSION['user_id'];
    
    // Récupérer les données du formulaire
    $avocat_id = filter_input(INPUT_POST, 'avocat_id', FILTER_VALIDATE_INT);
    $message_raw = trim($_POST['message'] ?? '');
    $input_nom = trim($_POST['nom'] ?? '');
    $input_email = trim($_POST['email'] ?? '');

    // Validation
    if (!$avocat_id) {
        echo json_encode(['success' => false, 'error' => 'Avocat invalide']);
        exit;
    }

    if ($message_raw === '' || $input_nom === '' || $input_email === '') {
        echo json_encode(['success' => false, 'error' => 'Tous les champs sont obligatoires.']);
        exit;
    }

    if (!filter_var($input_email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'error' => 'Email client invalide.']);
        exit;
    }

    // Récupérer les infos de l'avocat
    $stmt = $pdo->prepare("SELECT * FROM avocat WHERE id = ?");
    $stmt->execute([$avocat_id]);
    $avocat = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$avocat) {
        echo json_encode(['success' => false, 'error' => 'Avocat introuvable.']);
        exit;
    }

    // Configuration du chiffrement
    $secret_phrase = $config['encryption_key'] ?? null;
    if (!$secret_phrase) {
        echo json_encode(['success' => false, 'error' => 'Erreur de configuration.']);
        exit;
    }

    $cipher = "AES-256-CBC";
    $key = hash('sha256', $secret_phrase, true);

    // Chiffrement du message
    $iv_length = openssl_cipher_iv_length($cipher);
    $iv = random_bytes($iv_length);
    $encrypted_raw = openssl_encrypt($message_raw, $cipher, $key, OPENSSL_RAW_DATA, $iv);
    $encrypted_b64 = base64_encode($encrypted_raw);
    $iv_b64 = base64_encode($iv);

    // Insertion en base de données
    $stmtIns = $pdo->prepare("INSERT INTO conversation (id_client, id_avocat, expediteur, message, iv) VALUES (?, ?, ?, ?, ?)");
    $stmtIns->execute([$client_id, $avocat_id, 'client', $encrypted_b64, $iv_b64]);

    // Envoi de l'email à l'avocat
    $smtp_user = $config['smtp_user'] ?? null;
    $smtp_password = $config['smtp_password'] ?? null;
    $mail_from_name = $config['mail_from_name'] ?? 'JustisConnect';

    $email_sent = false;
    $mail_error = '';

    if ($smtp_user && $smtp_password) {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtp_user;
            $mail->Password   = $smtp_password;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom($smtp_user, $mail_from_name);
            $mail->addReplyTo($input_email, $input_nom);
            $mail->addAddress($avocat['email'], $avocat['prenom'] . ' ' . $avocat['nom']);

            $mail->isHTML(true);
            $mail->Subject = "Nouveau message d'un client";
            $mail->Body = "<p>Bonjour " . htmlspecialchars($avocat['prenom']) . " " . htmlspecialchars($avocat['nom']) . ",</p>
                <p>Vous avez reçu un nouveau message d'un client :</p>
                <p><strong>" . htmlspecialchars($input_nom) . " (" . htmlspecialchars($input_email) . ")</strong></p>
                <p>Message :</p>
                <div style='border:1px solid #ddd;padding:10px;background:#fafafa'>" . nl2br(htmlspecialchars($message_raw)) . "</div>
                <p><small>Cet échange a également été stocké sur la plateforme JustisConnect.</small></p>";

      /*      $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];
*/
            $mail->send();
            $email_sent = true;
        } catch (Exception $e) {
            $mail_error = $mail->ErrorInfo;
            $email_sent = false;
        }
    }

    // Réponse JSON
    echo json_encode([
        'success' => true,
        'message' => 'Message envoyé avec succès' . (!$email_sent ? ' (email non envoyé: ' . $mail_error . ')' : '')
    ]);

} catch (Throwable $e) {
    error_log("ajax_contact_avocat error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erreur serveur: ' . $e->getMessage()]);
}
?>