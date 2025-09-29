<?php
require_once __DIR__ . '/config/config.php';

// Récupérer la liste unique des villes
$villes = $pdo->query("SELECT DISTINCT ville FROM avocat WHERE ville IS NOT NULL AND ville != '' ORDER BY ville ASC")->fetchAll(PDO::FETCH_COLUMN);

// Récupérer la liste unique des spécialités
$specialites = $pdo->query("SELECT DISTINCT specialite FROM avocat WHERE specialite IS NOT NULL AND specialite != '' ORDER BY specialite ASC")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Fil d'actualité - Plateforme Avocat-Client</title>
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
    


    
    .filter-section {
      background-color: white;
      border-radius: 10px;
      box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
      padding: 1.5rem;
      margin-bottom: 2rem;
    }
    
    .form-control:focus, .form-select:focus {
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
    
    .avocat-card {
      border: none;
      border-radius: 10px;
      box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      height: 100%;
    }
    
    .avocat-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
    }
    
    .avocat-card .card-img-top {
      height: 200px;
      object-fit: cover;
      border-top-left-radius: 10px;
      border-top-right-radius: 10px;
    }
    
    .avocat-card .card-body {
      padding: 1.5rem;
    }
    
    .specialite-badge {
      background-color: var(--secondary-color);
      color: white;
    }
    
    .ville-badge {
      background-color: var(--primary-color);
      color: white;
    }
    
    .experience-badge {
      background-color: #6c757d;
      color: white;
    }
    
    .rating {
      color: #ffc107;
    }
    
    .loading-container {
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 2rem;
      display: none;
    }
    
    .no-results {
      text-align: center;
      padding: 3rem;
      color: #6c757d;
    }
    
    .no-results i {
      font-size: 4rem;
      margin-bottom: 1rem;
      color: #dee2e6;
    }
    
    .filter-icon {
      color: var(--primary-color);
      margin-right: 0.5rem;
    }
    
    .card-footer {
      background-color: transparent;
      border-top: 1px solid rgba(0,0,0,.125);
    }
    
    .contact-btn {
      background-color: var(--secondary-color);
      border-color: var(--secondary-color);
      color: white;
    }
    
    .contact-btn:hover {
      background-color: #e65100;
      border-color: #e65100;
      color: white;
    }
  </style>
</head>
<body>
  <!-- Navigation Bar -->
  <?php include 'navbar.php'; ?>

  <!-- Page Header -->
  <div class="page-header">
    <div class="container">
      <h1 class="display-5 mb-3"><i class="fas fa-newspaper me-2"></i>Fil d'Actualité</h1>
      <p class="lead mb-0">Découvrez notre réseau d'avocats qualifiés</p>
    </div>
  </div>

  <!-- Main Content -->
  <div class="container py-4">
    <!-- Filter Section -->
    <div class="filter-section">
      <h4 class="mb-3"><i class="fas fa-filter filter-icon"></i>Filtres de recherche</h4>
      <form id="filterForm" class="row g-3">
        <div class="col-md-5">
          <label for="ville" class="form-label">Ville</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fas fa-city"></i></span>
            <select name="ville" id="ville" class="form-select">
              <option value="">Toutes les villes</option>
              <?php foreach ($villes as $v): ?>
                <option value="<?= htmlspecialchars($v) ?>"><?= htmlspecialchars($v) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="col-md-5">
          <label for="specialite" class="form-label">Spécialité</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fas fa-briefcase"></i></span>
            <select name="specialite" id="specialite" class="form-select">
              <option value="">Toutes les spécialités</option>
              <?php foreach ($specialites as $s): ?>
                <option value="<?= htmlspecialchars($s) ?>"><?= htmlspecialchars($s) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="col-md-2 d-flex align-items-end">
          <button type="submit" class="btn btn-primary w-100">
            <i class="fas fa-search me-1"></i> Filtrer
          </button>
        </div>
      </form>
    </div>

    <!-- Avocats Container -->
    <div id="avocats" class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4"></div>

    <!-- Loading Indicator -->
    <div id="loading" class="loading-container">
      <div class="text-center">
        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;"></div>
        <p class="mt-3">Chargement des avocats...</p>
      </div>
    </div>

    <!-- No Results Message -->
    <div id="no-results" class="no-results" style="display: none;">
      <i class="fas fa-search"></i>
      <h4>Aucun avocat trouvé</h4>
      <p>Essayez de modifier vos critères de recherche</p>
    </div>
  </div>

  <!-- Footer -->
  <footer class="bg-dark text-white mt-5 py-4">
    <div class="container text-center">
      <p class="mb-0">&copy; 2025 JustisConnect - Tous droits réservés</p>
      <p class="mb-0">Plateforme de mise en relation avocat-client</p>
    </div>
  </footer>

  <!-- Bootstrap JS Bundle with Popper -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  
  <script>
  let offset = 0;
  const limit = 6;
  let loading = false;
  let finished = false;
  let currentFilters = {ville: "", specialite: ""};

  // Charger les avocats
  function loadAvocats(reset = false) {
    if (loading || finished) return;
    loading = true;
    $("#loading").show();
    $("#no-results").hide();

    $.get("load_avocats.php", {
        offset: offset,
        limit: limit,
        ville: currentFilters.ville,
        specialite: currentFilters.specialite
    }, function(data) {
        if (reset) {
          $("#avocats").html(""); 
          finished = false;
          offset = 0;
        }
        
        if (data.trim() === "") {
          finished = true;
          if (reset || offset === 0) {
            $("#no-results").show();
          }
        } else {
          $("#avocats").append(data);
          offset += limit;
        }
        loading = false;
        $("#loading").hide();
    }).fail(function() {
        loading = false;
        $("#loading").hide();
        if (reset || offset === 0) {
          $("#no-results").show();
        }
    });
  }

  // Premier chargement
  loadAvocats();

  // Scroll infini
  $(window).scroll(function() {
    if ($(window).scrollTop() + $(window).height() >= $(document).height() - 100) {
      loadAvocats();
    }
  });

  // Appliquer filtres
  $("#filterForm").submit(function(e) {
    e.preventDefault();
    currentFilters.ville = $("#ville").val();
    currentFilters.specialite = $("#specialite").val();
    offset = 0;
    finished = false;
    loadAvocats(true);
  });

  // Réinitialiser le scroll infini quand on change de filtre
  $("#ville, #specialite").change(function() {
    offset = 0;
    finished = false;
  });
  </script>
</body>
</html>