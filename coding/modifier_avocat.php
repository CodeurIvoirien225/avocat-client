<?php
require_once __DIR__ . '/config/config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Vérifier que l'utilisateur est avocat et propriétaire du profil
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'avocat' || !isset($_SESSION['id'])) {
    header('Location: connexion_avocat.php');
    exit;
}
$id = (int) ($_GET['id'] ?? $_SESSION['id']);
if ($_SESSION['id'] != $id) {
    die('Accès refusé.');
}
// Récupérer les infos actuelles
global $pdo;
$stmt = $pdo->prepare('SELECT * FROM avocat WHERE id = ?');
$stmt->execute([$id]);
$avocat = $stmt->fetch();
if (!$avocat) {
    die('Avocat introuvable !');
}
// Traitement du formulaire
$success = false;
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_prenom = trim($_POST['prenom'] ?? '');
    $new_nom = trim($_POST['nom'] ?? '');
    $new_username = trim($_POST['username'] ?? '');
    $new_email = trim($_POST['email'] ?? '');
    $new_ville = trim($_POST['ville'] ?? '');
    $new_specialite = trim($_POST['specialite'] ?? '');
    $new_annee_experience = intval($_POST['annee_experience'] ?? 0);
    $new_adresse_cabinet = trim($_POST['adresse_cabinet'] ?? '');
    $new_langues = trim($_POST['langues'] ?? '');
    $new_linkedin = trim($_POST['linkedin'] ?? '');
    $profile_photo = $avocat['profile_photo'];
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) { mkdir($uploadDir, 0777, true); }
        $fileName = uniqid() . '_' . basename($_FILES['profile_photo']['name']);
        $uploadFile = $uploadDir . $fileName;
        if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $uploadFile)) {
            $profile_photo = $uploadFile;
        }
    }
    $stmt = $pdo->prepare('UPDATE avocat SET prenom = ?, nom = ?, username = ?, email = ?, ville = ?, specialite = ?, annee_experience = ?, adresse_cabinet = ?, langues = ?, linkedin = ?, profile_photo = ? WHERE id = ?');
    if ($stmt->execute([$new_prenom, $new_nom, $new_username, $new_email, $new_ville, $new_specialite, $new_annee_experience, $new_adresse_cabinet, $new_langues, $new_linkedin, $profile_photo, $id])) {
        $success = true;
        // Recharger les infos
        $stmt = $pdo->prepare('SELECT * FROM avocat WHERE id = ?');
        $stmt->execute([$id]);
        $avocat = $stmt->fetch();
    } else {
        $error = 'Erreur lors de la mise à jour.';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier mon profil - <?= htmlspecialchars($avocat['prenom'] . " " . $avocat['nom']) ?> - JustisConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
        
        .profile-header {
            background: linear-gradient(135deg, var(--primary-color), #283593);
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
        }
        
        .info-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        
        .info-card:hover {
            transform: translateY(-5px);
        }
        
        .info-icon {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
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
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(26, 35, 126, 0.25);
        }
        
        .section-title {
            position: relative;
            padding-bottom: 1rem;
            margin-bottom: 2rem;
            border-bottom: 2px solid #e9ecef;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 60px;
            height: 2px;
            background-color: var(--secondary-color);
        }
        
        .profile-img-preview {
            width: 120px;
            height: 120px;
            border-radius: 10px;
            object-fit: cover;
            border: 3px solid var(--primary-color);
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <?php include 'navbar.php'; ?>

    <!-- Profile Header -->
    <div class="profile-header">
        <div class="container text-center">
            <h1 class="display-5 mb-3">Modifier mon profil</h1>
            <p class="lead mb-0">Mettez à jour vos informations personnelles et professionnelles</p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container pb-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <!-- Alert Messages -->
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>Profil mis à jour avec succès !
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php elseif ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Modification Form -->
                <div class="card info-card p-4 mb-4">
                    <h3 class="section-title"><i class="fas fa-user-edit info-icon"></i>Informations du profil</h3>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <div class="row g-4">
                            <!-- Informations personnelles -->
                            <div class="col-md-6">
                                <h5 class="text-primary mb-3"><i class="fas fa-user me-2"></i>Informations personnelles</h5>
                                
                                <div class="mb-3">
                                    <label for="prenom" class="form-label">Prénom *</label>
                                    <input type="text" class="form-control" id="prenom" name="prenom" value="<?= htmlspecialchars($avocat['prenom']) ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="nom" class="form-label">Nom *</label>
                                    <input type="text" class="form-control" id="nom" name="nom" value="<?= htmlspecialchars($avocat['nom']) ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="username" class="form-label">Nom d'utilisateur *</label>
                                    <input type="text" class="form-control" id="username" name="username" value="<?= htmlspecialchars($avocat['username']) ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email *</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($avocat['email']) ?>" required>
                                </div>
                            </div>

                            <!-- Informations professionnelles -->
                            <div class="col-md-6">
                                <h5 class="text-primary mb-3"><i class="fas fa-briefcase me-2"></i>Informations professionnelles</h5>
                                
                                <div class="mb-3">
                                    <label for="ville" class="form-label">Ville</label>
                                    <input type="text" class="form-control" id="ville" name="ville" value="<?= htmlspecialchars($avocat['ville']) ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="specialite" class="form-label">Spécialité</label>
                                    <input type="text" class="form-control" id="specialite" name="specialite" value="<?= htmlspecialchars($avocat['specialite']) ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="annee_experience" class="form-label">Années d'expérience</label>
                                    <input type="number" class="form-control" id="annee_experience" name="annee_experience" value="<?= htmlspecialchars($avocat['annee_experience']) ?>" min="0">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="langues" class="form-label">Langues parlées</label>
                                    <input type="text" class="form-control" id="langues" name="langues" value="<?= htmlspecialchars($avocat['langues']) ?>" placeholder="Français, Anglais, Arabe...">
                                </div>
                            </div>

                            <!-- Adresse et LinkedIn -->
                            <div class="col-md-6">
                                <h5 class="text-primary mb-3"><i class="fas fa-map-marker-alt me-2"></i>Coordonnées</h5>
                                
                                <div class="mb-3">
                                    <label for="adresse_cabinet" class="form-label">Adresse du cabinet</label>
                                    <textarea class="form-control" id="adresse_cabinet" name="adresse_cabinet" rows="3"><?= htmlspecialchars($avocat['adresse_cabinet']) ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="linkedin" class="form-label">Lien LinkedIn</label>
                                    <input type="url" class="form-control" id="linkedin" name="linkedin" value="<?= htmlspecialchars($avocat['linkedin']) ?>" placeholder="https://linkedin.com/in/votre-profil">
                                </div>
                            </div>

                            <!-- Photo de profil -->
                            <div class="col-md-6">
                                <h5 class="text-primary mb-3"><i class="fas fa-camera me-2"></i>Photo de profil</h5>
                                
                                <div class="mb-3">
                                    <label for="profile_photo" class="form-label">Changer la photo de profil</label>
                                    <input type="file" class="form-control" id="profile_photo" name="profile_photo" accept="image/*">
                                </div>
                                
                                <?php if (!empty($avocat['profile_photo'])): ?>
                                    <div class="mt-3">
                                        <label class="form-label">Photo actuelle</label>
                                        <div>
                                            <img src="<?= htmlspecialchars($avocat['profile_photo']) ?>" alt="Photo actuelle" class="profile-img-preview">
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Boutons d'action -->
                        <div class="mt-4 pt-3 border-top">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save me-2"></i>Enregistrer les modifications
                            </button>
                            <a href="profil_avocat.php?id=<?= $avocat['id'] ?>" class="btn btn-outline-primary btn-lg ms-2">
                                <i class="fas fa-times me-2"></i>Annuler
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container text-center">
            <p class="mb-0">&copy; 2025 JustisConnect - Tous droits réservés</p>
            <p class="mb-0">Plateforme de mise en relation avocat-client</p>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>