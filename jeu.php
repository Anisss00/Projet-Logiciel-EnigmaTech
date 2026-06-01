<?php

session_start();

require_once './include/connecte.php';
include './include/verifConnect.php';

mysqli_query($conn, "SET NAMES 'utf8'");

// Récupère l'id depuis jeu.php?id=5, redirige si absent
$idJeu = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($idJeu === 0) {
    header("Location: index.php");
    exit();
}

// Vérifie que le jeu existe et appartient à l'opérateur connecté
$stmtJeu = $conn->prepare("
    SELECT nomJeu, description, dureeMax
    FROM jeu
    WHERE IdJeu = ? AND IdOperateur = ?
");
$stmtJeu->bind_param("ii", $idJeu, $_SESSION['user_IdOperateur']);
$stmtJeu->execute();
$jeu = $stmtJeu->get_result()->fetch_assoc();
$stmtJeu->close();

if (!$jeu) {
    header("Location: index.php");
    exit();
}


/* ── Lancer une session ────────────────────────────── */

if (isset($_POST['start_game'])) {

    // Vérifie qu'aucune session n'est déjà active sur ce jeu
    $stmtCheck = $conn->prepare("
        SELECT IdSession FROM etatsession
        WHERE IdJeu = ? AND Statut = 'En cours' AND HeureFin IS NULL
        LIMIT 1
    ");
    $stmtCheck->bind_param("i", $idJeu);
    $stmtCheck->execute();
    $sessionExistante = $stmtCheck->get_result()->fetch_assoc();
    $stmtCheck->close();

    if (!$sessionExistante) {
        $stmtStart = $conn->prepare("
            INSERT INTO etatsession (Statut, HeureDebut, idUtilisateur, IdJeu)
            VALUES ('En cours', NOW(), ?, ?)
        ");
        $stmtStart->bind_param("ii", $_SESSION['user_id'], $idJeu);
        $stmtStart->execute();
        $stmtStart->close();
    }

    header("Location: jeu.php?id=" . $idJeu);
    exit();
}


/* ── Terminer une session ──────────────────────────── */

if (isset($_POST['stop_game'])) {

    $stmtStop = $conn->prepare("
        UPDATE etatsession
        SET Statut = 'Terminée', HeureFin = NOW()
        WHERE IdJeu = ? AND Statut = 'En cours' AND HeureFin IS NULL
    ");
    $stmtStop->bind_param("i", $idJeu);
    $stmtStop->execute();
    $stmtStop->close();

    header("Location: jeu.php?id=" . $idJeu);
    exit();
}


/* ── Session active ────────────────────────────────── */

$stmtSession = $conn->prepare("
    SELECT IdSession, HeureDebut FROM etatsession
    WHERE IdJeu = ? AND Statut = 'En cours' AND HeureFin IS NULL
    LIMIT 1
");
$stmtSession->bind_param("i", $idJeu);
$stmtSession->execute();
$sessionActive = $stmtSession->get_result()->fetch_assoc();
$stmtSession->close();


$page_title = $jeu['nomJeu'];
include './include/header.php';

?>

<body class="has-header">

    <div class="jeu-page">

        <a href="index.php" class="jeu-back">Retour</a>

        <!-- Infos du jeu -->
        <div class="etape-bloc">

            <div class="etape-titre">
                <span style="font-size:1.1rem; font-weight:700;">
                    <?= htmlspecialchars($jeu['nomJeu']) ?>
                </span>
            </div>

            <div style="padding:1rem 1.2rem;">

                <p style="margin-bottom:1rem; line-height:1.6;">
                    <?= htmlspecialchars($jeu['description']) ?>
                </p>

                <p style="margin-bottom:1rem;">
                    <strong>Durée maximum :</strong>
                    <?= htmlspecialchars($jeu['dureeMax']) ?> minutes
                </p>

                <?php if (!$sessionActive): ?>

                    <form method="POST">
                        <button type="submit" name="start_game" class="btn-primary">
                            Lancer le jeu
                        </button>
                    </form>

                <?php else: ?>

                    <div style="margin-bottom:1rem; color:var(--success); font-weight:600;">
                        Session en cours depuis :
                        <?= htmlspecialchars($sessionActive['HeureDebut']) ?>
                    </div>

                    <form method="POST">
                        <button type="submit" name="stop_game" class="btn-primary" style="background:var(--danger);">
                            Terminer le jeu
                        </button>
                    </form>

                <?php endif; ?>

            </div>

        </div>


        <?php if (!$sessionActive): ?>

            <div class="error">Aucune session active pour ce jeu.</div>

        <?php else: ?>

            <?php

            // Charge les étapes du jeu dans l'ordre
            $stmtEtapes = $conn->prepare("
            SELECT e.idEtape, e.ordre, e.description, s.nom AS nomSalle
            FROM etape e
            JOIN salle s ON e.IdSalle = s.IdSalle
            WHERE e.IdJeu = ?
            ORDER BY e.ordre ASC
        ");
            $stmtEtapes->bind_param("i", $idJeu);
            $stmtEtapes->execute();
            $resultEtapes = $stmtEtapes->get_result();
            $stmtEtapes->close();

            while ($etape = $resultEtapes->fetch_assoc()):

                $idEtape = $etape['idEtape'];

                // Récupère les dispositifs à valider pour cette étape
                $stmtDispositifs = $conn->prepare("
                SELECT d.idDispositif, d.nom AS nomDispositif, d.typeDispositif, v.mesure AS valeurAttendue
                FROM valider v
                JOIN dispositif d ON d.idDispositif = v.idDispositif
                WHERE v.idEtape = ?
            ");
                $stmtDispositifs->bind_param("i", $idEtape);
                $stmtDispositifs->execute();
                $resultDispositifs = $stmtDispositifs->get_result();
                $stmtDispositifs->close();

                $dispositifs = [];
                $etapeValidee = true; // sera mis à false si un dispositif ne correspond pas
        
                while ($disp = $resultDispositifs->fetch_assoc()) {

                    $idDispositif = $disp['idDispositif'];

                    // Dernière mesure enregistrée depuis le début de la session
                    $stmtMesure = $conn->prepare("
                    SELECT Valeur, dateEnregistrement FROM mesure
                    WHERE idDispositif = ? AND dateEnregistrement >= ?
                    ORDER BY dateEnregistrement DESC
                    LIMIT 1
                ");
                    $stmtMesure->bind_param("is", $idDispositif, $sessionActive['HeureDebut']);
                    $stmtMesure->execute();
                    $mesure = $stmtMesure->get_result()->fetch_assoc();
                    $stmtMesure->close();

                    $derniereValeur = $mesure ? $mesure['Valeur'] : null;
                    $dateMesure = $mesure ? $mesure['dateEnregistrement'] : null;

                    // Valide si la mesure correspond à la valeur attendue (insensible à la casse)
                    $ok = (
                        $derniereValeur !== null &&
                        strtolower(trim($derniereValeur)) === strtolower(trim($disp['valeurAttendue']))
                    );

                    if (!$ok) {
                        $etapeValidee = false;
                    }

                    $dispositifs[] = [
                        'nom' => $disp['nomDispositif'],
                        'type' => $disp['typeDispositif'],
                        'valeurAttendue' => $disp['valeurAttendue'],
                        'derniereValeur' => $derniereValeur,
                        'dateMesure' => $dateMesure,
                        'ok' => $ok,
                    ];
                }

                ?>

                <div class="etape-bloc <?= $etapeValidee ? 'etape-ok' : '' ?>">

                    <div class="etape-titre">
                        <span class="etape-num"><?= $etape['ordre'] ?></span>
                        <span><?= htmlspecialchars($etape['description']) ?></span>
                        <span class="etape-salle"><?= htmlspecialchars($etape['nomSalle']) ?></span>
                        <span class="etape-statut"><?= $etapeValidee ? 'Validée' : 'En attente' ?></span>
                    </div>

                    <table class="disp-table">
                        <thead>
                            <tr>
                                <th>Dispositif</th>
                                <th>Type</th>
                                <th>Attendu</th>
                                <th>Mesuré</th>
                                <th>Horodatage</th>
                                <th>État</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dispositifs as $d): ?>
                                <tr>
                                    <td><?= htmlspecialchars($d['nom']) ?></td>
                                    <td><?= htmlspecialchars($d['type']) ?></td>
                                    <td><?= htmlspecialchars($d['valeurAttendue']) ?></td>
                                    <td class="<?= $d['ok'] ? 'mesure-ok' : 'mesure-ko' ?>">
                                        <?= $d['derniereValeur'] !== null ? htmlspecialchars($d['derniereValeur']) : '—' ?>
                                    </td>
                                    <td>
                                        <?= $d['dateMesure'] ? htmlspecialchars($d['dateMesure']) : '—' ?>
                                    </td>
                                    <td class="<?= $d['ok'] ? 'mesure-ok' : 'mesure-ko' ?>">
                                        <?= $d['ok'] ? 'OK' : 'NON' ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                </div>

            <?php endwhile; ?>
        <?php endif; ?>

    </div>

</body>

<?php include './include/footer.php'; ?>