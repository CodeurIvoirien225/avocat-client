<?php
require __DIR__ . '/config/config.php';

$message = "";

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Vérifier le token
    $stmt = $pdo->prepare("SELECT * FROM avocat WHERE reset_token=? AND reset_expire > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if ($user) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $password = $_POST['password'] ?? '';
            $confirm  = $_POST['confirm'] ?? '';

            if (empty($password) || empty($confirm)) {
                $message = "<div class='alert alert-danger'>Veuillez remplir tous les champs.</div>";
            } elseif ($password !== $confirm) {
                $message = "<div class='alert alert-danger'>Les mots de passe ne correspondent pas.</div>";
            } else {
                // Hachage et mise à jour
                $hashed = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $pdo->prepare("UPDATE avocat 
                                        SET password=?, reset_token=NULL, reset_expire=NULL 
                                        WHERE id=?");
                $stmt->execute([$hashed, $user['id']]);

                $message = "<div class='alert alert-success'>
                                Votre mot de passe a été réinitialisé avec succès.<br>
                                <a href='connexion_avocat.php'>Cliquez ici pour vous connecter</a>.
                            </div>";
                $user = null; // on cache le formulaire
            }
        }
    } else {
        $message = "<div class='alert alert-danger'>Lien invalide ou expiré.</div>";
    }
} else {
    $message = "<div class='alert alert-danger'>Token manquant.</div>";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Réinitialiser mot de passe</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-5">
  <h2>Réinitialiser votre mot de passe</h2>
  <?php echo $message; ?>

  <?php if (isset($user) && $user): ?>
  <form method="POST" class="mt-4">
    <div class="mb-3">
      <label for="password" class="form-label">Nouveau mot de passe</label>
      <input type="password" class="form-control" id="password" name="password" required>
    </div>
    <div class="mb-3">
      <label for="confirm" class="form-label">Confirmer le mot de passe</label>
      <input type="password" class="form-control" id="confirm" name="confirm" required>
    </div>
    <button type="submit" class="btn btn-success w-100">Changer le mot de passe</button>
  </form>
  <?php endif; ?>
</body>
</html>
