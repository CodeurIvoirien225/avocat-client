<?php
require __DIR__ . '/config/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = password_hash($_POST['password'] ?? '', PASSWORD_DEFAULT);
    $prenom = trim($_POST['prenom'] ?? '');
    $nom = trim($_POST['nom'] ?? '');

    // Gestion de la photo de profil
    $profile_photo = '';
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($ext, $allowed)) {
            $uploadDir = __DIR__ . '/uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            $filename = uniqid() . '_' . time() . '.' . $ext;
            $uploadFile = $uploadDir . $filename;
            if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $uploadFile)) {
                $profile_photo = 'uploads/' . $filename;
            } else {
                $error = "Erreur lors de l'upload de la photo.";
            }
        } else {
            $error = "Format de photo non autorisé.";
        }
    } else {
        $error = "La photo de profil est obligatoire.";
    }

    // Vérifier si l'utilisateur existe déjà
    if (empty($error)) {
        $stmt = $pdo->prepare("SELECT id FROM client WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);

        if ($stmt->rowCount() > 0) {
            $error = "Nom d'utilisateur ou email déjà utilisé.";
        } else {
            // Insérer le client avec la photo
            $stmt = $pdo->prepare("INSERT INTO client (username, email, password, prenom, nom, profile_photo) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$username, $email, $password, $prenom, $nom, $profile_photo]);
            $success = "Inscription réussie !";
            echo '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><meta http-equiv="refresh" content="3;url=connexion_client.php"><title>Inscription réussie</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"></head><body><div class="container py-5"><div class="alert alert-success text-center" role="alert">'. $success .'<br>Vous allez être redirigé pour vous connecter...</div></div></body></html>';
            exit;
        }
    }
}

?>




<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription Client - Plateforme Avocat-Client</title>
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
        }
        
        .navbar-brand {
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .registration-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            margin-top: 2rem;
            margin-bottom: 2rem;
            overflow: hidden;
        }
        
        .registration-header {
            background: linear-gradient(135deg, var(--primary-color), #283593);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .registration-body {
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
        
        .step-progress {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
            position: relative;
        }
        
        .step-progress::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 0;
            right: 0;
            height: 4px;
            background-color: #e9ecef;
            z-index: 1;
        }
        
        .step {
            position: relative;
            z-index: 2;
            text-align: center;
            width: 40px;
            height: 40px;
            line-height: 40px;
            border-radius: 50%;
            background-color: #e9ecef;
            color: #6c757d;
            font-weight: bold;
        }
        
        .step.active {
            background-color: var(--primary-color);
            color: white;
        }
        
        .step.completed {
            background-color: var(--secondary-color);
            color: white;
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
        
        .password-requirements {
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 0.5rem;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="http://localhost/avocat-client/wordpress/">
                <i class="fas fa-balance-scale-left me-2"></i>JustisConnect
            </a>
            <div class="d-flex">
                <a href="connexion_client.php" class="btn btn-outline-primary me-2">Connexion</a>
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
                <div class="registration-container">
                    <div class="registration-header">
                        <i class="fas fa-user-tie legal-icon"></i>
                        <h1 class="h3 mb-0">Inscription Client</h1>
                        <p class="mb-0">Rejoignez notre plateforme pour trouver l'avocat qu'il vous faut</p>
                    </div>
                    
                    <div class="registration-body">
                      
                        
                        <?php if(isset($error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if(isset($success)): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?php echo $success; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" class="row g-3" enctype="multipart/form-data">
                            <div class="col-md-6">
                                <label for="prenom" class="form-label">Prénom <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" id="prenom" name="prenom" placeholder="Votre prénom" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="nom" class="form-label">Nom <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" id="nom" name="nom" placeholder="Votre nom" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="username" class="form-label">Nom d'utilisateur <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-at"></i></span>
                                    <input type="text" class="form-control" id="username" name="username" placeholder="Choisissez un nom d'utilisateur" required>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" class="form-control" id="email" name="email" placeholder="Votre adresse email" required>
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <label for="password" class="form-label">Mot de passe <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" placeholder="Créez un mot de passe sécurisé" required>
                                </div>
                                <div class="password-requirements">
                                    <small>Le mot de passe doit contenir au moins 8 caractères, incluant des lettres et des chiffres.</small>
                                </div>
                            </div>
                            
                        
                            
                            <div class="col-12 mt-4">

                            <div class="col-12">
    <label for="profile_photo" class="form-label">Photo de profil <span class="text-danger">*</span></label>
    <input type="file" class="form-control" id="profile_photo" name="profile_photo" accept="image/*" required>
</div>

                                <button type="submit" class="btn btn-primary btn-lg w-100 mt-4">
                                    <i class="fas fa-user-plus me-2"></i> S'inscrire
                                </button>
                            </div>
                        </form>
                        
                        <div class="text-center mt-4">
                            <p>Vous avez déjà un compte ? <a href="connexion_client.php" class="login-link">Connectez-vous ici</a></p>
                            <p>Vous êtes un avocat ? <a href="connexion_avocat.php" class="login-link">Connectez-vous en tant qu'avocat</a></p>
                        </div>
                    </div>
                </div>
                
                <div class="benefits-section mt-4">
                    <h4 class="mb-3"><i class="fas fa-star feature-icon"></i>Avantages pour les clients</h4>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="d-flex">
                                <i class="fas fa-search text-primary mt-1 me-2"></i>
                                <div>
                                    <h6 class="mb-0">Trouvez le bon avocat</h6>
                                    <p class="small mb-0">Recherchez par spécialité, localisation et expérience</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="d-flex">
                                <i class="fas fa-clock text-primary mt-1 me-2"></i>
                                <div>
                                    <h6 class="mb-0">Gagnez du temps</h6>
                                    <p class="small mb-0">Prenez rendez-vous en ligne facilement</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="d-flex">
                                <i class="fas fa-euro-sign text-primary mt-1 me-2"></i>
                                <div>
                                    <h6 class="mb-0">Transparence tarifaire</h6>
                                    <p class="small mb-0">Consultez les honoraires à l'avance</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Script pour la force du mot de passe
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const requirements = document.querySelector('.password-requirements');
            
            if (password.length > 0) {
                let strength = '';
                let color = '';
                
                if (password.length < 8) {
                    strength = 'Faible';
                    color = 'red';
                } else if (password.length < 12) {
                    strength = 'Moyen';
                    color = 'orange';
                } else {
                    strength = 'Fort';
                    color = 'green';
                }
                
                requirements.innerHTML = `<small>Force du mot de passe: <strong style="color:${color}">${strength}</strong></small>`;
            } else {
                requirements.innerHTML = '<small>Le mot de passe doit contenir au moins 8 caractères, incluant des lettres et des chiffres.</small>';
            }
        });
    </script>
</body>
</html>