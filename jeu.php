<?php
session_start();
require_once './include/connecte.php';
include './include/verifConnect.php';

mysqli_query($conn, "SET NAMES 'utf8'");

// ── 1. Get the game ID from the URL (?id=21) ────────────────────────────────
$idJeu = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// If no ID was given, send user back to home
if ($idJeu === 0) {
    header("Location: index.php");
    exit();
}

// ── 2. Load the game info ────────────────────────────────────────────────────
// We also check IdOperateur so a user can't access another company's game
// by just changing the ?id= in the URL
$stmtJeu = $conn->prepare("
    SELECT nomJeu, dureeMax
    FROM jeu
    WHERE IdJeu = ? AND IdOperateur = ?
");
$stmtJeu->bind_param("ii", $idJeu, $_SESSION['user_IdOperateur']);
$stmtJeu->execute();
$jeu = $stmtJeu->get_result()->fetch_assoc();
$stmtJeu->close();

// If game not found (or belongs to another company), go back home
if (!$jeu) {
    header("Location: index.php");
    exit();
}

$page_title = $jeu['nomJeu'];
include './include/header.php';

// ── 3. Load all steps for this game ─────────────────────────────────────────
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
?>

<body class="has-header">
    <div class="jeu-page">

        <a href="index.php" class="jeu-back">Retour</a>

        <?php
        // ── 4. Loop over each step ───────────────────────────────────────────────
        while ($etape = $resultEtapes->fetch_assoc()):

            $idEtape = $etape['idEtape'];

            // ── 5. For this step, get all devices that need to be validated ──────
            // The valider table links a step to a device + the expected value
            $stmtDispositifs = $conn->prepare("
            SELECT d.idDispositif, d.nom AS nomDispositif, d.typeDispositif,
                   v.mesure AS valeurAttendue
            FROM valider v
            JOIN dispositif d ON d.idDispositif = v.idDispositif
            WHERE v.idEtape = ?
        ");
            $stmtDispositifs->bind_param("i", $idEtape);
            $stmtDispositifs->execute();
            $resultDispositifs = $stmtDispositifs->get_result();
            $stmtDispositifs->close();

            // ── 6. For each device, get its latest sensor reading ────────────────
            $dispositifs = [];
            $etapeValidee = true; // assume valid until we find a device that fails
        
            while ($disp = $resultDispositifs->fetch_assoc()) {

                $idDispositif = $disp['idDispositif'];

                // Get the most recent mesure for this device
                $stmtMesure = $conn->prepare("
                SELECT Valeur, dateEnregistrement
                FROM mesure
                WHERE idDispositif = ?
                ORDER BY dateEnregistrement DESC
                LIMIT 1
            ");
                $stmtMesure->bind_param("i", $idDispositif);
                $stmtMesure->execute();
                $mesure = $stmtMesure->get_result()->fetch_assoc();
                $stmtMesure->close();

                $derniereValeur = $mesure ? $mesure['Valeur'] : null;
                $dateMesure = $mesure ? $mesure['dateEnregistrement'] : null;

                // Compare latest reading with expected value (case-insensitive)
                $ok = ($derniereValeur !== null &&
                    strtolower(trim($derniereValeur)) === strtolower(trim($disp['valeurAttendue'])));

                // If even one device fails, the whole step is not validated
                if (!$ok) {
                    $etapeValidee = false;
                }

                // Store everything we need to display this device
                $dispositifs[] = [
                    'nom' => $disp['nomDispositif'],
                    'type' => $disp['typeDispositif'],
                    'valeurAttendue' => $disp['valeurAttendue'],
                    'derniereValeur' => $derniereValeur,
                    'dateMesure' => $dateMesure,
                    'ok' => $ok,
                ];
            }

            // ── 7. Display the step ──────────────────────────────────────────────
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
                                <td><?= $d['dateMesure'] ? htmlspecialchars($d['dateMesure']) : '—' ?></td>
                                <td class="<?= $d['ok'] ? 'mesure-ok' : 'mesure-ko' ?>">
                                    <?= $d['ok'] ? 'OK' : 'NON' ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

            </div>

        <?php endwhile; ?>

    </div>
</body>

<?php include './include/footer.php'; ?>