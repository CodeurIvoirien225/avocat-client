<?php
session_start();

// Détruire toutes les données de session
session_unset();
session_destroy();

// Rediriger vers une page de connexion générique ou choisir selon le rôle
// Ici, on peut créer une page d'accueil qui propose "connexion avocat / client"
header("Location: index.php"); // ou "connexion_client.php" / "connexion_avocat.php"
exit;
?>
