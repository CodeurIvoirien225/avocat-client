<?php
require_once __DIR__ . '/config/config.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("ID avocat non spécifié !");
}

$id = (int) $_GET['id'];

// Récupérer les infos de l'avocat
$canEdit = false;
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
if (isset($_SESSION['role']) && $_SESSION['role'] === 'avocat' && isset($_SESSION['id']) && $_SESSION['id'] == $id) {
  $canEdit = true;
}
$stmt = $pdo->prepare("SELECT * FROM avocat WHERE id = ?");
$stmt->execute([$id]);
$avocat = $stmt->fetch();

if (!$avocat) {
    die("Avocat introuvable !");
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Profil de <?= htmlspecialchars($avocat['prenom'] . " " . $avocat['nom']) ?> - Plateforme Avocat-Client</title>
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
    
    .badge-experience {
      background-color: #6c757d;
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
    
    .contact-btn {
      background-color: var(--secondary-color);
      border-color: var(--secondary-color);
      color: white;
      padding: 0.75rem 2rem;
      font-weight: 600;
    }
    
    .contact-btn:hover {
      background-color: #e65100;
      border-color: #e65100;
      color: white;
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
    
    .rating {
      color: #ffc107;
      font-size: 1.2rem;
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
    
    .stat-number {
      font-size: 2.5rem;
      font-weight: 700;
      color: var(--primary-color);
      line-height: 1;
    }
    
    .stat-label {
      color: #6c757d;
      font-size: 0.9rem;
      text-transform: uppercase;
      letter-spacing: 1px;
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
          <i class="fas fa-arrow-left me-1"></i> Retour aux avocats
        </a>
      </div>
    </div>
  </nav>

  <!-- Profile Header -->
  <div class="profile-header">
    <div class="container text-center">
      <h1 class="display-5 mb-3">Profil Avocat</h1>
      <p class="lead mb-0">Découvrez le parcours et l'expertise de notre avocat</p>
    </div>
  </div>

  <!-- Main Content -->
  <div class="container pb-5">
  
    <!-- Profile Card -->
    <div class="card profile-card mb-5">
      <div class="card-body p-4">
        <div class="row align-items-center">
          <div class="col-md-3 text-center">


          <img src="<?= htmlspecialchars($avocat['profile_photo'] ?? 'https://via.placeholder.com/200x200/1a237e/ffffff?text=' . urlencode($avocat['prenom'][0] . $avocat['nom'][0])) ?>" 
     class="profile-img rounded-circle mb-3" 
     alt="Photo de <?= htmlspecialchars($avocat['prenom'] . ' ' . $avocat['nom']) ?>"
     id="profilePhoto"
     style="cursor:pointer;"
     data-bs-toggle="modal" data-bs-target="#photoModal">

         <div class="social-links mb-3">
    <?php if (!empty($avocat['linkedin'])): ?>
        <a href="<?= htmlspecialchars($avocat['linkedin']) ?>" target="_blank" title="LinkedIn">
            <i class="fab fa-linkedin-in"></i>
        </a>
    <?php endif; ?>

    <a href="mailto:<?= htmlspecialchars($avocat['email']) ?>" title="Email">
        <i class="fas fa-envelope"></i>
    </a>
</div>

          </div>
          
          <div class="col-md-6">
            <h2 class="card-title mb-2"><?= htmlspecialchars($avocat['prenom'] . " " . $avocat['nom']) ?></h2>
            <p class="text-muted mb-3"><?= htmlspecialchars($avocat['specialite']) ?></p>
            
            <div class="d-flex flex-wrap gap-2 mb-3">
              <span class="badge badge-primary rounded-pill p-2">
                <i class="fas fa-city me-1"></i><?= htmlspecialchars($avocat['ville']) ?>
              </span>
              <span class="badge badge-secondary rounded-pill p-2">
                <i class="fas fa-briefcase me-1"></i><?= htmlspecialchars($avocat['specialite']) ?>
              </span>
              <span class="badge badge-experience rounded-pill p-2">
                <i class="fas fa-chart-line me-1"></i><?= htmlspecialchars($avocat['annee_experience']) ?> ans d'expérience
              </span>
            </div>
         
          </div>
          
          <div class="col-md-3 text-center text-md-end">
            <a href="contact_avocat.php?avocat_id=<?= $avocat['id'] ?>" class="btn contact-btn btn-lg mb-2 w-100">
    <i class="fas fa-envelope me-2"></i>Contacter
</a>

            <button class="btn back-btn btn-lg w-100" onclick="history.back()">
              <i class="fas fa-arrow-left me-2"></i>Retour
            </button>

            <?php if ($canEdit): ?>
        <a href="modifier_avocat.php" class="btn btn-primary btn-lg w-100 mt-2">
            <i class="fas fa-edit me-2"></i>Modifier mes informations
        </a>
    <?php endif; ?>
    
          </div>
        </div>
      </div>
    </div>

    <!-- Informations Section -->
    <div class="row mb-5">
      <div class="col-md-4 mb-4">
        <div class="card info-card h-100 text-center p-4">
          <i class="fas fa-user info-icon"></i>
          <h5>Informations Personnelles</h5>
          <p class="text-muted mb-0">
            <strong>Nom d'utilisateur :</strong> <?= htmlspecialchars($avocat['username']) ?><br>
            <strong>Email :</strong> <?= htmlspecialchars($avocat['email']) ?><br>
            <strong>Inscrit depuis :</strong> <?= date("d/m/Y", strtotime($avocat['created_at'])) ?>
          </p>
        </div>
      </div>
      
      <div class="col-md-4 mb-4">
        <div class="card info-card h-100 text-center p-4">
          <i class="fas fa-map-marker-alt info-icon"></i>
          <h5>Localisation</h5>
          <p class="text-muted mb-0">
            <strong>Ville :</strong> <?= htmlspecialchars($avocat['ville']) ?><br>
            <strong>Adresse du cabinet :</strong><br>
            <?= htmlspecialchars($avocat['adresse_cabinet']) ?>
          </p>
        </div>
      </div>
      
      <div class="col-md-4 mb-4">
        <div class="card info-card h-100 text-center p-4">
          <i class="fas fa-graduation-cap info-icon"></i>
          <h5>Expertise</h5>
          <p class="text-muted mb-0">
    <strong>Spécialité :</strong> <?= htmlspecialchars($avocat['specialite']) ?><br>
    <strong>Expérience :</strong> <?= htmlspecialchars($avocat['annee_experience']) ?> ans<br>
    <strong>Langues :</strong> <?= htmlspecialchars($avocat['langues']) ?>
</p>
        </div>
      </div>
    </div>



    <!-- About Section -->
    <div class="row">
      <div class="col-12">
        <h3 class="section-title">À propos</h3>
        <div class="card info-card p-4">
          <p class="lead"><?= htmlspecialchars($avocat['prenom'] . ' ' . $avocat['nom']) ?> est un avocat spécialisé en <?= htmlspecialchars($avocat['specialite']) ?> avec <?= htmlspecialchars($avocat['annee_experience']) ?> années d'expérience.</p>
          <p>Passionné par le droit et toujours à l'écoute de ses clients, il met son expertise à votre service pour vous accompagner dans toutes vos démarches juridiques.</p>
          <p>Installé à <?= htmlspecialchars($avocat['ville']) ?>, il reçoit sur rendez-vous dans son cabinet et propose également des consultations en ligne.</p>
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
        <img src="<?= htmlspecialchars($avocat['profile_photo'] ?? 'https://via.placeholder.com/400x400/1a237e/ffffff?text=' . urlencode($avocat['prenom'][0] . $avocat['nom'][0])) ?>" 
             alt="Photo de <?= htmlspecialchars($avocat['prenom'] . ' ' . $avocat['nom']) ?>" 
             class="img-fluid rounded shadow" style="max-width: 100%; max-height: 80vh;">
      </div>
    </div>
  </div>
</div>

</body>
</html>