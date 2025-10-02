<?php
// navbar.php - Barre de navigation réutilisable avec gestion des sessions
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialiser le compteur de messages
$nombre_messages_non_lus = 0;

// Si l'avocat est connecté, compter les messages non lus
if (isset($_SESSION['role']) && $_SESSION['role'] === 'avocat' && isset($_SESSION['user_id'])) {
    require_once 'config/config.php';
    $avocat_id = (int)$_SESSION['user_id'];
    
    // Compter les messages non lus pour cet avocat
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as nb_non_lus 
        FROM conversation 
        WHERE id_avocat = ? AND expediteur = 'client' AND lu = 0
    ");
    $stmt->execute([$avocat_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $nombre_messages_non_lus = (int)$result['nb_non_lus'];
}

// Si le client est connecté, compter les messages non lus
elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'client') {
    require_once 'config/config.php';
    
    // Identifier le client (gestion des différentes sessions possibles)
    $client_id = null;
    if (isset($_SESSION['user_id'])) {
        $client_id = (int)$_SESSION['user_id'];
    } elseif (isset($_SESSION['id_client'])) {
        $client_id = (int)$_SESSION['id_client'];
    }
    
    if ($client_id) {
        // Compter les messages non lus pour ce client (messages des avocats non lus)
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as nb_non_lus 
            FROM conversation 
            WHERE id_client = ? AND expediteur = 'avocat' AND lu = 0
        ");
        $stmt->execute([$client_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $nombre_messages_non_lus = (int)$result['nb_non_lus'];
    }
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JustisConnect</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
    :root {
      --primary-color: #1a237e;
      --secondary-color: #ff6f00;
    }
    
    .navbar-brand {
      font-weight: 700;
      color: var(--primary-color);
    }
    
    /* Style pour le badge de notification */
    .notification-badge {
      position: absolute;
      top: -8px;
      right: -8px;
      background-color: #dc3545;
      color: white;
      border-radius: 50%;
      width: 20px;
      height: 20px;
      font-size: 0.75rem;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
    }
    
    .nav-item {
      position: relative;
    }
    
    .page-header {
      background: linear-gradient(135deg, var(--primary-color), #283593);
      color: white;
      padding: 3rem 0;
      margin-bottom: 2rem;
      text-align: center;
    }
    
    .filter-section {
      background-color: white;
      border-radius: 10px;
      box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
      padding: 1.5rem;
      margin-bottom: 2rem;
    }
    
    .btn-primary {
      background-color: var(--primary-color);
      border-color: var(--primary-color);
    }
    
    .btn-primary:hover {
      background-color: #283593;
      border-color: #283593;
    }
    
    .contact-btn:hover {
      background-color: #e65100;
      border-color: #e65100;
      color: white;
    }

    .btn-outline-primary {
      color: var(--primary-color);
      border-color: var(--primary-color);
    }
    
    .btn-outline-primary:hover {
      background-color: var(--primary-color);
      color: white;
    }
    
    .avocat-card {
      border: none;
      border-radius: 10px;
      box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      height: 100%;
    }
    
    .navbar-nav .nav-link {
      color: var(--primary-color) !important;
      font-weight: 500;
    }
    
    .navbar-nav .nav-link:hover {
      color: #283593 !important;
    }
  </style>

</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="fil_actualite.php">
                <i class="fas fa-balance-scale me-2"></i>JustisConnect
            </a>

            <!-- Bouton toggle pour mobile -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <?php if (isset($_SESSION['role'])): ?>
                    <!-- Utilisateur connecté -->
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="fil_actualite.php">
                                <i class="fas fa-home me-1"></i> Accueil
                            </a>
                        </li>
                        
                        <?php if ($_SESSION['role'] === 'avocat'): ?>
                            <!-- Menu pour avocat -->
                            <li class="nav-item">
                                <a class="nav-link" href="profil_avocat.php?id=<?= isset($_SESSION['id']) ? $_SESSION['id'] : '' ?>">
                                    <i class="fas fa-user me-1"></i> Mon profil
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="dashboard_avocat.php">
                                    <i class="fas fa-chart-bar me-1"></i> Mon dashboard
                                    <?php if ($nombre_messages_non_lus > 0): ?>
                                        <span class="notification-badge"><?= $nombre_messages_non_lus ?></span>
                                    <?php endif; ?>
                                </a>
                            </li>
                        
                           
                        <?php elseif ($_SESSION['role'] === 'client'): ?>
                            <!-- Menu pour client -->
                            <li class="nav-item">
                                <a class="nav-link" href="profil_client.php">
                                    <i class="fas fa-user me-1"></i> Mon compte
                                </a>
                            </li>
                           <li class="nav-item">
    <a class="nav-link" href="dashboard_client.php">
        <i class="fas fa-chart-bar me-1"></i> Mon dashboard
        <?php if ($nombre_messages_non_lus > 0): ?>
            <span class="notification-badge"><?= $nombre_messages_non_lus ?></span>
        <?php endif; ?>
    </a>
</li>
                          
       
                        <?php endif; ?>
                    </ul>

                    <div class="d-flex align-items-center">
                        <span class="navbar-text me-3">
                            <i class="fas fa-user-circle me-1"></i>
                            <?php 
                            if ($_SESSION['role'] === 'avocat') {
                                echo "Avocat";
                            } else {
                                echo "Client";
                            }
                            ?>
                        </span>
<a href="deconnexion.php" class="btn btn-outline-danger">
    <i class="fas fa-sign-out-alt me-1"></i> Déconnexion
</a>

                    </div>

                <?php else: ?>
                    <!-- Utilisateur non connecté -->
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="fil_actualite.php">
                                <i class="fas fa-home me-1"></i> Accueil
                            </a>
                        </li>
                    </ul>

                    <div class="d-flex">
                        <a href="connexion_client.php" class="btn btn-outline-primary me-2">
                            <i class="fas fa-user me-1"></i> Connexion Client
                        </a>
                        <a href="connexion_avocat.php" class="btn btn-outline-primary me-2">
                            <i class="fas fa-gavel me-1"></i> Connexion Avocat
                        </a>
                        <a href="fil_actualite.php" class="btn btn-primary">
                            <i class="fas fa-home me-1"></i> Accueil
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>