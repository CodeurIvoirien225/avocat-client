<?php
require_once __DIR__ . '/config/config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'client') {
    die('Accès refusé. Seul le client connecté peut accéder à cette page.');
}
if (!isset($_SESSION['id']) || empty($_SESSION['id'])) {
    die('ID client non spécifié !');
}
$id = (int) $_SESSION['id'];

// Récupérer les infos du client
$stmt = $pdo->prepare("SELECT * FROM client WHERE id = ?");
$stmt->execute([$id]);
$client = $stmt->fetch();
if (!$client) {
    die('Client introuvable !');
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil de <?= htmlspecialchars($client['prenom'] . " " . $client['nom']) ?> - Plateforme Avocat-Client</title>
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
        
        .navbar-brand {
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .profile-header {
            background: linear-gradient(135deg, var(--primary-color), #283593);
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
        }
        
        .profile-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-top: -50px;
            position: relative;
            z-index: 1;
        }
        
        .profile-img {
            width: 180px;
            height: 180px;
            border: 5px solid white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .badge-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .badge-secondary {
            background-color: var(--secondary-color);
            color: white;
        }
        
        .info-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
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
        
        .back-btn {
            background-color: white;
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            padding: 0.75rem 2rem;
            font-weight: 600;
        }
        
        .back-btn:hover {
            background-color: var(--primary-color);
            color: white;
        }
        
        .social-links a {
            width: 40px;
            height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            margin-right: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .social-links a:hover {
            background-color: var(--secondary-color);
            transform: translateY(-3px);
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
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="fil_actualite.php">
                <i class="fas fa-balance-scale-left me-2"></i>JustisConnect
            </a>
            <div class="d-flex">
                <a href="fil_actualite.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left me-1"></i> Retour à l'accueil
                </a>
            </div>
        </div>
    </nav>

    <!-- Profile Header -->
    <div class="profile-header">
        <div class="container text-center">
            <h1 class="display-5 mb-3">Profil Client</h1>
            <p class="lead mb-0">Gérez vos informations personnelles et votre compte</p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container pb-5">
        <!-- Profile Card -->
        <div class="card profile-card mb-5">
            <div class="card-body p-4">
                <div class="row align-items-center">
                    <div class="col-md-3 text-center">

                    <img src="<?= htmlspecialchars($client['profile_photo'] ?? 'https://via.placeholder.com/200x200/1a237e/ffffff?text=' . urlencode($client['prenom'][0] . $client['nom'][0])) ?>" 
     class="profile-img rounded-circle mb-3"
     alt="Photo de <?= htmlspecialchars($client['prenom'] . ' ' . $client['nom']) ?>"
     id="profilePhoto"
     style="cursor:pointer;"
     data-bs-toggle="modal" data-bs-target="#photoModal">
                    
                    <div class="social-links mb-3">
                            <a href="mailto:<?= htmlspecialchars($client['email']) ?>" title="Email">
                                <i class="fas fa-envelope"></i>
                            </a>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <h2 class="card-title mb-2"><?= htmlspecialchars($client['prenom'] . " " . $client['nom']) ?></h2>
                        <p class="text-muted mb-3">Client sur JustisConnect</p>
                        
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <span class="badge badge-primary rounded-pill p-2">
                                <i class="fas fa-user me-1"></i>Client
                            </span>
                            <span class="badge badge-secondary rounded-pill p-2">
                                <i class="fas fa-calendar me-1"></i>Inscrit le <?= date("d/m/Y", strtotime($client['created_at'])) ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="col-md-3 text-center text-md-end">
                        <button class="btn back-btn btn-lg w-100" onclick="history.back()">
                            <i class="fas fa-arrow-left me-2"></i>Retour
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Informations Section -->
        <div class="row mb-5">
            <div class="col-md-6 mb-4">
                <div class="card info-card h-100 text-center p-4">
                    <i class="fas fa-user info-icon"></i>
                    <h5>Informations Personnelles</h5>
                    <p class="text-muted mb-0">
                        <strong>Nom d'utilisateur :</strong> <?= htmlspecialchars($client['username']) ?><br>
                        <strong>Email :</strong> <?= htmlspecialchars($client['email']) ?><br>
                        <strong>Inscrit depuis :</strong> <?= date("d/m/Y", strtotime($client['created_at'])) ?>
                    </p>
                </div>
            </div>
            
            <div class="col-md-6 mb-4">
                <div class="card info-card h-100 text-center p-4">
                    <i class="fas fa-id-card info-icon"></i>
                    <h5>Identité</h5>
                    <p class="text-muted mb-0">
                        <strong>Prénom :</strong> <?= htmlspecialchars($client['prenom']) ?><br>
                        <strong>Nom :</strong> <?= htmlspecialchars($client['nom']) ?><br>
                        <strong>Statut :</strong> Client
                    </p>
                </div>
            </div>
        </div>

        <!-- Formulaire de modification -->
        <div class="row">
            <div class="col-12">
                <h3 class="section-title">Modifier mes informations</h3>
                <div class="card info-card p-4">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="prenom" class="form-label">Prénom</label>
                                <input type="text" class="form-control" id="prenom" name="prenom" value="<?= htmlspecialchars($client['prenom']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="nom" class="form-label">Nom</label>
                                <input type="text" class="form-control" id="nom" name="nom" value="<?= htmlspecialchars($client['nom']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="username" class="form-label">Nom d'utilisateur</label>
                                <input type="text" class="form-control" id="username" name="username" value="<?= htmlspecialchars($client['username']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($client['email']) ?>" required>
                            </div>
                            <div class="col-12">
                                <label for="profile_photo" class="form-label">Photo de profil</label>
                                <input type="file" class="form-control" id="profile_photo" name="profile_photo" accept="image/*">
                            </div>
                        </div>
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Mettre à jour
                            </button>
                            <a href="fil_actualite.php" class="btn back-btn ms-2">
                                <i class="fas fa-home me-2"></i>Retour à l'accueil
                            </a>
                        </div>
                    </form>
                    <?php
                    // Traitement de la mise à jour du profil client
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $new_prenom = trim($_POST['prenom'] ?? '');
                        $new_nom = trim($_POST['nom'] ?? '');
                        $new_username = trim($_POST['username'] ?? '');
                        $new_email = trim($_POST['email'] ?? '');
                        $profile_photo = $client['profile_photo'];
                        
                        if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == UPLOAD_ERR_OK) {
                            $uploadDir = 'uploads/';
                            if (!is_dir($uploadDir)) { 
                                mkdir($uploadDir, 0777, true); 
                            }
                            $fileName = uniqid() . '_' . basename($_FILES['profile_photo']['name']);
                            $uploadFile = $uploadDir . $fileName;
                            if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $uploadFile)) {
                                $profile_photo = $uploadFile;
                            }
                        }
                        
                        $stmt = $pdo->prepare("UPDATE client SET prenom = ?, nom = ?, username = ?, email = ?, profile_photo = ? WHERE id = ?");
                        if ($stmt->execute([$new_prenom, $new_nom, $new_username, $new_email, $profile_photo, $id])) {
                            echo '<div class="alert alert-success mt-3">Profil mis à jour avec succès ! Actualisez la page pour voir les changements.</div>';
                            // Recharger les données du client
                            $stmt = $pdo->prepare("SELECT * FROM client WHERE id = ?");
                            $stmt->execute([$id]);
                            $client = $stmt->fetch();
                        } else {
                            echo '<div class="alert alert-danger mt-3">Erreur lors de la mise à jour du profil.</div>';
                        }
                    }
                    ?>
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


    <!-- Modal pour afficher la photo en grand -->
<div class="modal fade" id="photoModal" tabindex="-1" aria-labelledby="photoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content bg-transparent border-0">
      <div class="modal-body text-center p-0">
        <img src="<?= htmlspecialchars($client['profile_photo'] ?? 'https://via.placeholder.com/400x400/1a237e/ffffff?text=' . urlencode($client['prenom'][0] . $client['nom'][0])) ?>" 
             alt="Photo de <?= htmlspecialchars($client['prenom'] . ' ' . $client['nom']) ?>" 
             class="img-fluid rounded shadow" style="max-width: 100%; max-height: 80vh;">
      </div>
    </div>
  </div>
</div>

</body>
</html>