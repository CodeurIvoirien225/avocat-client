
<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/vendor/autoload.php';
$config = require __DIR__ . '/mot_de_passe_application.php';


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $error = "Veuillez saisir votre email.";
    } else {
        // Vérifier si l'email existe dans la table avocat
        $stmt = $pdo->prepare("SELECT * FROM avocat WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            $error = "Aucun compte avocat trouvé avec cet email.";
        } else {
            // Générer un token unique
            $token = bin2hex(random_bytes(16));
            $expiration = date("Y-m-d H:i:s", strtotime("+1 hour"));

            // Créer la table password_resets si elle n'existe pas
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS password_resets_avocat (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    token VARCHAR(255) NOT NULL,
                    expires_at DATETIME NOT NULL,
                    FOREIGN KEY (user_id) REFERENCES avocat(id) ON DELETE CASCADE
                )
            ");

            // Insérer le token dans la table
            $stmtToken = $pdo->prepare("INSERT INTO password_resets_avocat (user_id, token, expires_at) VALUES (?, ?, ?)");
            $stmtToken->execute([$user['id'], $token, $expiration]);

            // Envoyer l'email
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'koneelabass1@gmail.com'; // Ton Gmail
                $mail->Password = $config['smtp_password']; // Mot de passe application
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                $mail->setFrom('koneelabass1@gmail.com', 'JustisConnect');
                $mail->addAddress($email, $user['username']);

                $mail->isHTML(true);
                $mail->Subject = 'Réinitialisation de votre mot de passe';
                $mail->Body = "
                    Bonjour {$user['username']},<br><br>
                    Cliquez sur le lien ci-dessous pour réinitialiser votre mot de passe (valable 1 heure) :<br>
                    <a href='http://localhost/avocat-client/coding/reinitialiser_mdp_avocat.php?token=$token'>Réinitialiser mon mot de passe</a><br><br>
                    Si vous n'avez pas demandé ce changement, ignorez ce message.
                ";

/*
                $mail->SMTPOptions = [
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false,
        'allow_self_signed' => true
    ]
];

*/
                $mail->send();
                $success = "Un email de réinitialisation a été envoyé à $email.";
            } catch (Exception $e) {
                $error = "Erreur lors de l'envoi de l'email : {$mail->ErrorInfo}";
            }
        }
    }
}
?>


<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mot de passe oublié - Plateforme Avocat-Client</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #1a237e;
            --secondary-color: #ff6f00;
            --light-color: #f5f5f5;
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .navbar-brand {
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .password-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            margin-top: 2rem;
            margin-bottom: 2rem;
            overflow: hidden;
            flex-grow: 1;
        }
        
        .password-header {
            background: linear-gradient(135deg, var(--primary-color), #283593);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .password-body {
            padding: 2rem;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(26, 35, 126, 0.25);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: #283593;
            border-color: #283593;
        }
        
        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            color: white;
        }
        
        .legal-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: var(--secondary-color);
        }
        
        .login-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }
        
        .login-link:hover {
            text-decoration: underline;
        }
        
        .feature-icon {
            color: var(--primary-color);
            margin-right: 10px;
        }
        
        .steps-section {
            background-color: #f8f9fa;
            padding: 2rem;
            border-radius: 10px;
            margin-top: 2rem;
        }
        
        footer {
            background-color: var(--primary-color);
            color: white;
            padding: 1.5rem 0;
            margin-top: auto;
        }
        
        .step-card {
            text-align: center;
            padding: 1.5rem;
            border-radius: 8px;
            background-color: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            height: 100%;
        }
        
        .step-number {
            width: 40px;
            height: 40px;
            line-height: 40px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            font-weight: bold;
            margin: 0 auto 1rem;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-balance-scale-left me-2"></i>JustiConnect
            </a>
            <div class="d-flex">
                <a href="connexion_avocat.php" class="btn btn-outline-primary me-2">Connexion</a>
                <a href="index.php" class="btn btn-primary">
                    <i class="fas fa-home me-1"></i> Accueil
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="password-container">
                    <div class="password-header">
                        <i class="fas fa-key legal-icon"></i>
                        <h1 class="h3 mb-0">Mot de passe oublié</h1>
                        <p class="mb-0">Réinitialisez votre mot de passe en quelques étapes</p>
                    </div>
                    
                    <div class="password-body">
                        <?php if(isset($error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if(isset($success)): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-2"></i>
                                <?php echo $success; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" class="row g-3">
                            <div class="col-12">
                                <p class="text-muted mb-3">Saisissez votre adresse email associée à votre compte avocat. Nous vous enverrons un lien pour réinitialiser votre mot de passe.</p>
                            </div>
                            
                            <div class="col-12">
                                <label for="email" class="form-label">Email professionnel <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" class="form-control" id="email" name="email" placeholder="votre@email.com" required>
                                </div>
                            </div>
                            
                            <div class="col-12 mt-4">
                                <button type="submit" class="btn btn-primary btn-lg w-100">
                                    <i class="fas fa-paper-plane me-2"></i> Envoyer le lien de réinitialisation
                                </button>
                            </div>
                        </form>
                        
                        <div class="text-center mt-4">
                            <p>Vous vous souvenez de votre mot de passe ? <a href="connexion_avocat.php" class="login-link">Connectez-vous ici</a></p>
                            <p>Vous êtes un client ? <a href="mot_de_passe_oublie_client.php" class="login-link">Réinitialiser le mot de passe client</a></p>
                        </div>
                    </div>
                </div>
                
                <div class="steps-section mt-4">
                    <h4 class="mb-3 text-center"><i class="fas fa-info-circle feature-icon"></i>Comment réinitialiser votre mot de passe</h4>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="step-card">
                                <div class="step-number">1</div>
                                <h5>Saisissez votre email</h5>
                                <p class="small mb-0">Entrez l'adresse email associée à votre compte avocat</p>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="step-card">
                                <div class="step-number">2</div>
                                <h5>Recevez le lien</h5>
                                <p class="small mb-0">Consultez votre boîte mail et cliquez sur le lien reçu</p>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="step-card">
                                <div class="step-number">3</div>
                                <h5>Nouveau mot de passe</h5>
                                <p class="small mb-0">Créez un nouveau mot de passe sécurisé</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="alert alert-info mt-4">
                    <div class="d-flex">
                        <i class="fas fa-lightbulb me-3 mt-1"></i>
                        <div>
                            <h5 class="alert-heading">Conseil de sécurité</h5>
                            <p class="mb-0">Le lien de réinitialisation est valable pendant 1 heure pour des raisons de sécurité. Si vous ne recevez pas l'email, vérifiez votre dossier de spam.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="mt-5">
        <div class="container text-center">
            <p class="mb-0">&copy; 2023 JustiConnect - Tous droits réservés</p>
            <p class="mb-0">Plateforme de mise en relation avocat-client</p>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>