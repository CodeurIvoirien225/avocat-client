<?php
require_once __DIR__ . '/config/config.php';

$offset = intval($_GET['offset'] ?? 0);
$limit = intval($_GET['limit'] ?? 6);
$ville = trim($_GET['ville'] ?? '');
$specialite = trim($_GET['specialite'] ?? '');

$query = "SELECT * FROM avocat WHERE 1=1";
$params = [];

// Filtre par ville
if (!empty($ville)) {
    $query .= " AND ville LIKE ?";
    $params[] = "%$ville%";
}

// Filtre par spécialité
if (!empty($specialite)) {
    $query .= " AND specialite LIKE ?";
    $params[] = "%$specialite%";
}

$query .= " ORDER BY created_at DESC LIMIT ?, ?";
$params[] = $offset;
$params[] = $limit;

$stmt = $pdo->prepare($query);
foreach ($params as $i => $val) {
    $stmt->bindValue($i + 1, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$stmt->execute();
$avocats = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($avocats as $avocat): ?>
  <div class="col-md-4 mb-4">
    <div class="card shadow">
      <img src="<?= !empty($avocat['profile_photo']) ? htmlspecialchars($avocat['profile_photo']) : 'default-avatar.png' ?>" 
           class="card-img-top" alt="Photo de profil" style="height: 220px; object-fit: cover;">
      <div class="card-body">
        <h5 class="card-title"><?= htmlspecialchars($avocat['prenom'] . ' ' . $avocat['nom']) ?></h5>
        <p class="card-text">
          <strong>Spécialité :</strong> <?= htmlspecialchars($avocat['specialite'] ?? 'Non spécifiée') ?><br>
          <strong>Expérience :</strong> <?= htmlspecialchars($avocat['annee_experience'] ?? 0) ?> ans<br>
          <strong>Ville :</strong> <?= htmlspecialchars($avocat['ville'] ?? 'Non spécifiée') ?><br>
        </p>
        <a href="profil_avocat.php?id=<?= $avocat['id'] ?>" class="btn btn-outline-primary w-100">Voir Profil</a>
      </div>
    </div>
  </div>
<?php endforeach; ?>
