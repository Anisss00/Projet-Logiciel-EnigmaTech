<?php 
    session_start();
    require_once './include/connecte.php';
    include './include/verifConnect.php';

    $page_title = $page_title ?? "Accueil";
    include './include/header.php';
?>

<body class="has-header">

<?php 

mysqli_query($conn, "SET NAMES 'utf8'");
$query = "SELECT idJeu, nom, description, dureeMax FROM JEU"; /* a modifier quand j'aurai la base de donnée  il faut afficher que les jeu de l'entreprise de l'utilisateur */
$resultat = mysqli_query($conn, $query);

if ($resultat)
{
    echo "<div class=\"jeux-grid\">";
    
    while ($m = mysqli_fetch_array($resultat))
    {
        echo "<a class=\"jeu-card\" href=\"jeu.php\" >";
        echo "<h3>" . htmlspecialchars($m["nom"]) . "</h3>";
        echo "<p>" . htmlspecialchars($m["description"]) . "</p>";
        echo "<span>⏱ " . htmlspecialchars($m["dureeMax"]) . "</span>";
        echo "</a>";
    }
    
    echo "</div>";
}
else
{
    echo "<p>Erreur dans l'exécution de la requête.<br>";
    echo "Message du serveur de base de données : " . mysqli_error($conn);
}

?>

</body>

<?php
    include './include/footer.php';
?>