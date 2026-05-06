<?php 
    session_start();
    include './include/verifConnect.php';

    $page_title = $page_title ?? "Accueil";
    include './include/header.php';
    include './include/footer.php';
?>