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

    // Chaque société ne voit que ses propres jeux
    $query = "
    SELECT IdJeu, nomJeu, description, dureeMax
    FROM jeu
    WHERE IdOperateur = " . $_SESSION["user_IdOperateur"] . ";
";

    $resultat = mysqli_query($conn, $query);

    if ($resultat) {

        echo "<div class=\"jeux-grid\">";

        while ($m = mysqli_fetch_array($resultat)) {

            // Convertit les minutes en format lisible, ex: 90 → 1h30
            $duree = $m["dureeMax"];
            if ($duree >= 60) {
                $duree = "1h" . ($duree - 60);
            }

            echo "<a class=\"jeu-card\" href=\"jeu.php?id=" . $m["IdJeu"] . "\">";
            echo "<h3>" . htmlspecialchars($m["nomJeu"]) . "</h3>";
            echo "<p>" . htmlspecialchars($m["description"]) . "</p>";
            echo "<span>" . htmlspecialchars($duree) . "</span>";
            echo "</a>";
        }

        echo "</div>";

    } else {
        echo "<p>Erreur dans l'exécution de la requête.<br>";
        echo "Message du serveur de base de données : " . mysqli_error($conn);
    }

    ?>

</body>

<?php include './include/footer.php'; ?>