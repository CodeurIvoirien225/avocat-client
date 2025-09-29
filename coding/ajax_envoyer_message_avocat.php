<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/vendor/autoload.php';
$config = require __DIR__ . '/mot_de_passe_application.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

try {
    $cipher = "AES-256-CBC";
    $secret_key = $config['encryption_key'] ?? null;

    // Vérifier session avocat
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'avocat') {
        echo json_encode(['success' => false, 'error' => 'Non connecté']);
        exit;
    }

    $avocat_id = (int)$_SESSION['user_id'];
    $client_id = (int)($_POST['client_id'] ?? 0);
    $msg_raw = trim($_POST['message'] ?? '');

    if ($client_id <= 0 || $msg_raw === '') {
        echo json_encode(['success' => false, 'error' => 'Paramètres invalides']);
        exit;
    }

    // chiffrement
    $iv_length = openssl_cipher_iv_length($cipher);
    $iv = random_bytes($iv_length);
    $encrypted_raw = openssl_encrypt($msg_raw, $cipher, hash('sha256', $secret_key, true), OPENSSL_RAW_DATA, $iv);
    $encrypted_b64 = base64_encode($encrypted_raw);
    $iv_b64 = base64_encode($iv);

    // insertion DB - MESSAGE MARQUÉ COMME NON LU POUR LE CLIENT (lu = 0)
    $stmt = $pdo->prepare("INSERT INTO conversation (id_client, id_avocat, expediteur, message, iv, lu) VALUES (?, ?, 'avocat', ?, ?, 0)");
    $stmt->execute([$client_id, $avocat_id, $encrypted_b64, $iv_b64]);

    $date = date('Y-m-d H:i:s');

    // infos client & avocat pour email
    $stmtC = $pdo->prepare("SELECT prenom, nom, email FROM client WHERE id = ?");
    $stmtC->execute([$client_id]);
    $client = $stmtC->fetch(PDO::FETCH_ASSOC);

    $stmtA = $pdo->prepare("SELECT prenom, nom FROM avocat WHERE id = ?");
    $stmtA->execute([$avocat_id]);
    $avocat = $stmtA->fetch(PDO::FETCH_ASSOC);

    $email_sent = false;
    if ($client && !empty($client['email'])) {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $config['smtp_user'];
            $mail->Password   = $config['smtp_password'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom($config['smtp_user'], $config['mail_from_name'] ?? 'JustisConnect');
            $mail->addAddress($client['email'], ($client['prenom'] ?? '') . ' ' . ($client['nom'] ?? ''));
            $mail->Subject = "Réponse de votre avocat - JustisConnect";
            $mail->isHTML(true);
            $mail->Body = "
                <p>Bonjour " . htmlspecialchars($client['prenom'] ?? '') . ",</p>
                <p>Votre avocat Maître " . htmlspecialchars(($avocat['prenom'] ?? '') . ' ' . ($avocat['nom'] ?? '')) . " vous a répondu :</p>
                <div style='border:1px solid #ddd;padding:15px;background:#f9f9f9;border-radius:5px;margin:10px 0;'>
                    " . nl2br(htmlspecialchars($msg_raw)) . "
                </div>
                <p>Vous pouvez consulter cette réponse et poursuivre la conversation sur votre <a href='https://votresite.com/dashboard_client.php'>dashboard client</a>.</p>
                <p><small>Cet échange est sécurisé et stocké sur la plateforme JustisConnect.</small></p>
            ";

            // options SSL pour dev local
         /*   $mail->SMTPOptions = [
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
            error_log("Erreur email avocat->client : " . $mail->ErrorInfo);
            $email_sent = false;
        }
    }

    // Retour JSON
    echo json_encode([
        'success' => true,
        'message' => nl2br(htmlspecialchars($msg_raw)),
        'expediteur' => 'avocat',
        'date_envoi' => $date,
        'email_sent' => $email_sent
    ]);
    exit;

} catch (Throwable $e) {
    error_log("ajax_envoyer_message_avocat error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erreur serveur']);
    exit;
}
?>