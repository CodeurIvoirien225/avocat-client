<?php
session_start();

// Sauvegarder le rôle avant de détruire la session
$role = isset($_SESSION['role']) ? $_SESSION['role'] : null;

// Détruire la session
session_unset();
session_destroy();

// Rediriger selon le rôle
if ($role === 'avocat') {
    header("Location: connexion_avocat.php");
} else {
    header("Location: connexion_client.php");
}
exit;
?>
