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

    // Vérifier session client
    $client_id = $_SESSION['user_id'] ?? $_SESSION['id_client'] ?? null;
    if (!$client_id) {
        echo json_encode(['success' => false, 'error' => 'Non connecté']);
        exit;
    }

    $avocat_id = (int)($_POST['avocat_id'] ?? 0);
    $msg_raw = trim($_POST['message'] ?? '');

    if ($avocat_id <= 0 || $msg_raw === '') {
        echo json_encode(['success' => false, 'error' => 'Paramètres invalides']);
        exit;
    }

    // chiffrement
    $iv_length = openssl_cipher_iv_length($cipher);
    $iv = random_bytes($iv_length);
    $encrypted_raw = openssl_encrypt($msg_raw, $cipher, hash('sha256', $secret_key, true), OPENSSL_RAW_DATA, $iv);
    $encrypted_b64 = base64_encode($encrypted_raw);
    $iv_b64 = base64_encode($iv);

    // insertion DB
$stmt = $pdo->prepare("INSERT INTO conversation (id_client, id_avocat, expediteur, message, iv, lu) VALUES (?, ?, 'client', ?, ?, 0)");
    $stmt->execute([$client_id, $avocat_id, $encrypted_b64, $iv_b64]);

    $date = date('Y-m-d H:i:s');

    // infos client & avocat pour email
    $stmtC = $pdo->prepare("SELECT prenom, nom FROM client WHERE id = ?");
    $stmtC->execute([$client_id]);
    $client = $stmtC->fetch(PDO::FETCH_ASSOC);

    $stmtA = $pdo->prepare("SELECT prenom, nom, email FROM avocat WHERE id = ?");
    $stmtA->execute([$avocat_id]);
    $avocat = $stmtA->fetch(PDO::FETCH_ASSOC);

    $email_sent = false;
    if ($avocat && !empty($avocat['email'])) {
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
            $mail->addAddress($avocat['email'], ($avocat['prenom'] ?? '') . ' ' . ($avocat['nom'] ?? ''));
            $mail->Subject = "Nouveau message d'un client";
            $mail->isHTML(true);
            $mail->Body = "<p>Vous avez reçu un nouveau message d'un client : " . htmlspecialchars($client['prenom'] . ' ' . $client['nom']) . ".</p>"
                       . "<div style='border:1px solid #ddd;padding:10px;background:#fafafa;'>" . nl2br(htmlspecialchars($msg_raw)) . "</div>"
                       . "<p><small>Cet échange a également été stocké sur la plateforme JustisConnect.</small></p>";

            // options SSL pour dev local
           /* $mail->SMTPOptions = [
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
            error_log("Erreur email client->avocat : " . $mail->ErrorInfo);
            $email_sent = false;
        }
    }

    // Retour JSON corrigé
    echo json_encode([
        'success' => true,
        'message' => nl2br(htmlspecialchars($msg_raw)), // Correction: $msg_raw au lieu de $message
        'expediteur' => 'client', // Correction: valeur fixe
        'date_envoi' => $date // Correction: nom de clé cohérent
    ]);
    exit;

} catch (Throwable $e) {
    error_log("ajax_envoyer_message error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erreur serveur']);
    exit;
}
?>