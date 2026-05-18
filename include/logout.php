<?php
session_start();

// Détruire la sessiona
$_SESSION = array();
session_destroy();

error_log("Session destroyed", 0);  // Ajout du log pour vérifier si le script est appelé

// Rediriger vers la page d'accueil
header("location:../login.php");
exit();
?>
