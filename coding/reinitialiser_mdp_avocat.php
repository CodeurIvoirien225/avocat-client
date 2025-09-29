
<?php
require_once __DIR__ . '/config/config.php';
session_start();

// Vérifier si le token est présent
$token = $_GET['token'] ?? '';
if (empty($token)) {
    die("Lien invalide ou expiré.");
}

// Vérifier le token dans la base et qu'il n'est pas expiré
$stmt = $pdo->prepare("SELECT * FROM password_resets_avocat WHERE token = ? AND expires_at > NOW()");
$stmt->execute([$token]);
$reset = $stmt->fetch();

if (!$reset) {
    die("Lien invalide ou expiré.");
}

// Récupérer les informations de l'utilisateur
$stmtUser = $pdo->prepare("SELECT * FROM avocat WHERE id = ?");
$stmtUser->execute([$reset['user_id']]);
$user = $stmtUser->fetch();
if (!$user) {
    die("Utilisateur introuvable.");
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($password) || empty($confirmPassword)) {
        $error = "Veuillez remplir tous les champs.";
    } elseif ($password !== $confirmPassword) {
        $error = "Les mots de passe ne correspondent pas.";
    } else {
        // Mettre à jour le mot de passe
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmtUpdate = $pdo->prepare("UPDATE avocat SET password = ? WHERE id = ?");
        $stmtUpdate->execute([$hashedPassword, $user['id']]);

        // Supprimer le token
        $stmtDel = $pdo->prepare("DELETE FROM password_resets_avocat WHERE token = ?");
        $stmtDel->execute([$token]);

        $success = "Votre mot de passe a été réinitialisé avec succès ! Vous pouvez maintenant vous connecter.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialisation mot de passe - Plateforme Avocat-Client</title>
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
        
        .reset-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            margin-top: 2rem;
            margin-bottom: 2rem;
            overflow: hidden;
            flex-grow: 1;
        }
        
        .reset-header {
            background: linear-gradient(135deg, var(--primary-color), #283593);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .reset-body {
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
        
        .password-strength {
            height: 5px;
            margin-top: 5px;
            border-radius: 5px;
            background-color: #e9ecef;
        }
        
        .password-strength-bar {
            height: 100%;
            border-radius: 5px;
            width: 0%;
            transition: width 0.3s;
        }
        
        footer {
            background-color: var(--primary-color);
            color: white;
            padding: 1.5rem 0;
            margin-top: auto;
        }
        
        .requirement-list {
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 0.5rem;
        }
        
        .requirement-item {
            margin-bottom: 0.25rem;
        }
        
        .requirement-item.valid {
            color: #198754;
        }
        
        .requirement-item.invalid {
            color: #6c757d;
        }
        
        .requirement-item i {
            margin-right: 0.5rem;
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
                <div class="reset-container">
                    <div class="reset-header">
                        <i class="fas fa-key legal-icon"></i>
                        <h1 class="h3 mb-0">Réinitialisation du mot de passe</h1>
                        <p class="mb-0">Créez un nouveau mot de passe sécurisé</p>
                    </div>
                    
                    <div class="reset-body">
                        <?php if(isset($error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if(isset($success)): ?>
                            <div class="alert alert-success" role="alert">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-check-circle me-3 fs-4"></i>
                                    <div>
                                        <h4 class="alert-heading">Réinitialisation réussie !</h4>
                                        <p class="mb-0"><?php echo $success; ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="text-center mt-4">
                                <a href="connexion_avocat.php" class="btn btn-primary btn-lg">
                                    <i class="fas fa-sign-in-alt me-2"></i> Se connecter
                                </a>
                            </div>
                        <?php else: ?>
                            <p class="text-muted mb-4">Bonjour <?php echo htmlspecialchars($user['username'] ?? ''); ?>, veuillez saisir votre nouveau mot de passe ci-dessous.</p>
                            
                            <form method="POST" id="resetForm">
                                <div class="mb-3">
                                    <label for="password" class="form-label">Nouveau mot de passe <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" class="form-control" id="password" name="password" placeholder="Saisissez votre nouveau mot de passe" required>
                                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="password-strength mt-2">
                                        <div class="password-strength-bar" id="passwordStrengthBar"></div>
                                    </div>
                                    <div class="requirement-list" id="passwordRequirements">
                                        <div class="requirement-item invalid" id="lengthReq">
                                            <i class="fas fa-circle"></i> Au moins 8 caractères
                                        </div>
                                        <div class="requirement-item invalid" id="numberReq">
                                            <i class="fas fa-circle"></i> Au moins un chiffre
                                        </div>
                                        <div class="requirement-item invalid" id="specialReq">
                                            <i class="fas fa-circle"></i> Au moins un caractère spécial
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="confirm_password" class="form-label">Confirmer le mot de passe <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirmez votre nouveau mot de passe" required>
                                        <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="invalid-feedback" id="confirmPasswordFeedback">
                                        Les mots de passe ne correspondent pas.
                                    </div>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-lg" id="submitButton" disabled>
                                        <i class="fas fa-key me-2"></i> Réinitialiser le mot de passe
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="alert alert-info mt-4">
                    <div class="d-flex">
                        <i class="fas fa-lightbulb me-3 mt-1"></i>
                        <div>
                            <h5 class="alert-heading">Conseils de sécurité</h5>
                            <ul class="mb-0 ps-3">
                                <li>Utilisez un mot de passe unique que vous n'utilisez nulle part ailleurs</li>
                                <li>Évitez les informations personnelles facilement devinables</li>
                                <li>Changez régulièrement votre mot de passe</li>
                            </ul>
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
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            const togglePasswordButton = document.getElementById('togglePassword');
            const toggleConfirmPasswordButton = document.getElementById('toggleConfirmPassword');
            const strengthBar = document.getElementById('passwordStrengthBar');
            const submitButton = document.getElementById('submitButton');
            
            // Fonction pour basculer la visibilité du mot de passe
            function togglePasswordVisibility(input, button) {
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);
                button.querySelector('i').classList.toggle('fa-eye');
                button.querySelector('i').classList.toggle('fa-eye-slash');
            }
            
            togglePasswordButton.addEventListener('click', function() {
                togglePasswordVisibility(passwordInput, this);
            });
            
            toggleConfirmPasswordButton.addEventListener('click', function() {
                togglePasswordVisibility(confirmPasswordInput, this);
            });
            
            // Fonction pour vérifier la force du mot de passe
            function checkPasswordStrength(password) {
                let strength = 0;
                
                // Longueur minimale
                if (password.length >= 8) {
                    strength += 25;
                    document.getElementById('lengthReq').classList.add('valid');
                    document.getElementById('lengthReq').classList.remove('invalid');
                    document.getElementById('lengthReq').querySelector('i').className = 'fas fa-check-circle';
                } else {
                    document.getElementById('lengthReq').classList.remove('valid');
                    document.getElementById('lengthReq').classList.add('invalid');
                    document.getElementById('lengthReq').querySelector('i').className = 'fas fa-circle';
                }
                
                // Contient un chiffre
                if (/\d/.test(password)) {
                    strength += 25;
                    document.getElementById('numberReq').classList.add('valid');
                    document.getElementById('numberReq').classList.remove('invalid');
                    document.getElementById('numberReq').querySelector('i').className = 'fas fa-check-circle';
                } else {
                    document.getElementById('numberReq').classList.remove('valid');
                    document.getElementById('numberReq').classList.add('invalid');
                    document.getElementById('numberReq').querySelector('i').className = 'fas fa-circle';
                }
                
                // Contient un caractère spécial
                if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) {
                    strength += 25;
                    document.getElementById('specialReq').classList.add('valid');
                    document.getElementById('specialReq').classList.remove('invalid');
                    document.getElementById('specialReq').querySelector('i').className = 'fas fa-check-circle';
                } else {
                    document.getElementById('specialReq').classList.remove('valid');
                    document.getElementById('specialReq').classList.add('invalid');
                    document.getElementById('specialReq').querySelector('i').className = 'fas fa-circle';
                }
                
                // Longueur supplémentaire
                if (password.length >= 12) {
                    strength += 25;
                }
                
                // Mettre à jour la barre de force
                strengthBar.style.width = strength + '%';
                
                if (strength < 50) {
                    strengthBar.style.backgroundColor = '#dc3545'; // Rouge
                } else if (strength < 75) {
                    strengthBar.style.backgroundColor = '#ffc107'; // Orange
                } else {
                    strengthBar.style.backgroundColor = '#198754'; // Vert
                }
                
                return strength;
            }
            
            // Fonction pour valider le formulaire
            function validateForm() {
                const password = passwordInput.value;
                const confirmPassword = confirmPasswordInput.value;
                const strength = checkPasswordStrength(password);
                
                // Vérifier si les mots de passe correspondent
                if (password !== confirmPassword) {
                    confirmPasswordInput.classList.add('is-invalid');
                    return false;
                } else {
                    confirmPasswordInput.classList.remove('is-invalid');
                }
                
                // Vérifier la force du mot de passe
                if (strength < 50) {
                    return false;
                }
                
                return true;
            }
            
            // Événements pour la validation en temps réel
            passwordInput.addEventListener('input', function() {
                checkPasswordStrength(this.value);
                submitButton.disabled = !validateForm();
            });
            
            confirmPasswordInput.addEventListener('input', function() {
                submitButton.disabled = !validateForm();
            });
            
            // Validation à la soumission du formulaire
            document.getElementById('resetForm').addEventListener('submit', function(e) {
                if (!validateForm()) {
                    e.preventDefault();
                    alert('Veuillez corriger les erreurs dans le formulaire.');
                }
            });
        });
    </script>
</body>
</html>