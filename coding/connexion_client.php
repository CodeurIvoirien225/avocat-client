

<?php
require __DIR__ . '/config/config.php';
session_start();

// Gestion de la déconnexion
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit();
}


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username_email = trim($_POST['username_email'] ?? '');
    $password = $_POST['password'] ?? '';

    // 1️⃣ Vérifier dans la table client
    $stmt = $pdo->prepare("SELECT * FROM client WHERE username = ? OR email = ?");
    $stmt->execute([$username_email, $username_email]);
    $user = $stmt->fetch();

    if ($user) {
        // Utilisateur trouvé dans client
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['id'] = $user['id']; // Pour le profil client
            $_SESSION['role'] = 'client';
            header("Location: fil_actualite.php");
            exit;
        } else {
            $error = "Mot de passe incorrect.";
        }
    } else {
        // Vérifier dans la table avocat pour détecter si c'est le mauvais formulaire
        $stmt2 = $pdo->prepare("SELECT * FROM avocat WHERE username = ? OR email = ?");
        $stmt2->execute([$username_email, $username_email]);
        $user_avocat = $stmt2->fetch();

        if ($user_avocat) {
            $error = "Cet utilisateur est un avocat. Veuillez utiliser le formulaire de connexion avocat.";
        } else {
            $error = "Nom d'utilisateur ou email inexistant.";
        }
    }
}
?>



<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Client - Plateforme Avocat-Client</title>
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
        
        .login-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            margin-top: 2rem;
            margin-bottom: 2rem;
            overflow: hidden;
            flex-grow: 1;
        }
        
        .login-header {
            background: linear-gradient(135deg, var(--primary-color), #283593);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .login-body {
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
        
        .benefits-section {
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
        
        .social-login {
            border-top: 1px solid #dee2e6;
            padding-top: 1.5rem;
            margin-top: 1.5rem;
        }
        
        .social-btn {
            display: block;
            padding: 0.75rem;
            border-radius: 5px;
            text-align: center;
            margin-bottom: 0.75rem;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .social-btn.google {
            background-color: #DB4437;
            color: white;
        }
        
        .social-btn.facebook {
            background-color: #4267B2;
            color: white;
        }
        
        .social-btn:hover {
            opacity: 0.9;
            color: white;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-balance-scale-left me-2"></i>JustisConnect
            </a>
            <div class="d-flex">
                <a href="inscription_client.php" class="btn btn-outline-primary me-2">Inscription</a>
                <a href="http://localhost/avocat-client/wordpress/" class="btn btn-primary">
                    <i class="fas fa-home me-1"></i> Accueil
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="login-container">
                    <div class="login-header">
                        <i class="fas fa-user-tie legal-icon"></i>
                        <h1 class="h3 mb-0">Connexion Client</h1>
                        <p class="mb-0">Accédez à votre espace personnel</p>
                    </div>
                    
                    <div class="login-body">
                        <?php if(isset($error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" class="row g-3">
                            <div class="col-12">
                                <label for="username_email" class="form-label">Nom d'utilisateur ou Email <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" id="username_email" name="username_email" placeholder="Votre nom d'utilisateur ou email" required>
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <label for="password" class="form-label">Mot de passe <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" placeholder="Votre mot de passe" required>
                                </div>
                            </div>
                            

                            
                            <div class="col-12 mt-4">
                                <button type="submit" class="btn btn-primary btn-lg w-100">
                                    <i class="fas fa-sign-in-alt me-2"></i> Se connecter
                                </button>
                            </div>

                            <div class="col-12 text-center">
    <a href="mot_de_passe_oublie_client.php" class="login-link">Mot de passe oublié ?</a>
</div>

                        </form>
                        
                       
                        
                        <div class="text-center mt-4">
                            <p>Vous n'avez pas de compte ? <a href="inscription_client.php" class="login-link">Inscrivez-vous ici</a></p>
                            <p>Vous êtes un avocat ? <a href="connexion_avocat.php" class="login-link">Connectez-vous en tant qu'avocat</a></p>
                        </div>
                    </div>
                </div>
                
                
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="mt-5">
        <div class="container text-center">
            <p class="mb-0">&copy; 2023 JustisConnect - Tous droits réservés</p>
            <p class="mb-0">Plateforme de mise en relation avocat-client</p>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>