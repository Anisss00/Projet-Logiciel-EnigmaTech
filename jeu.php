<?php 
    session_start();
    require_once './include/connecte.php';
    include './include/verifConnect.php';

    $page_title = $page_title ?? "Jeu";
    include './include/header.php';
?>

<body class="has-header">



</body>

<?php
    include './include/footer.php';
?>