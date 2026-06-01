<?php

$conn = mysqli_connect("localhost", "phpmyadmin", "tp", "enigmatech");

if (!$conn) {
    die("0"); 
}

$result = $conn->query("
    SELECT j.dureeMax
    FROM etatsession es
    JOIN jeu j ON es.IdJeu = j.IdJeu
    WHERE es.STatut = 'En cours'
      AND es.HeureFin IS NULL
    ORDER BY es.HeureDebut DESC
    LIMIT 1
");

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo $row['dureeMax']; 
} else {
    echo "0"; // 0 parce que pas de session de jeu trouvee
}

mysqli_close($conn);