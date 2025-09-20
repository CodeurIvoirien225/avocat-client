<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: connexion.php");
    exit;
}
echo "<h2>Bienvenue sur votre dashboard, ".$_SESSION['role']."</h2>";
echo "<p><a href='deconnexion.php'>Se déconnecter</a></p>";
?>
