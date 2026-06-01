<?php

$conn = mysqli_connect("localhost", "phpmyadmin", "tp", "enigmatech");
mysqli_set_charset($conn, "utf8");

if (!$conn) {
    die("DB connection failed: " . mysqli_connect_error());
}

$DOMOTICZ_URL = "http://192.168.4.1:8080";
$DOMOTICZ_USER = "admin";
$DOMOTICZ_PASS = "domoticz";

// Certains dispositifs sont mal détectés par Domoticz, on force leur type via leur idx
$idx_to_type = [
    34 => 'door_sensor',
    35 => 'door_sensor',
    41 => 'door_sensor',
];


/**
 * Convertit le type/sous-type Domoticz en type simplifié pour notre BDD.
 */
function map_type($type, $subType)
{
    $type = strtolower($type);
    $subType = strtolower($subType);

    if (strpos($subType, 'door') !== false || strpos($subType, 'contact') !== false) {
        return 'door_sensor';
    }

    if ($type === 'light/switch' && $subType === 'switch') {
        return 'button';
    }

    if ($type === 'usage') {
        return 'wall_plug';
    }

    return 'multisensor';
}


/**
 * Effectue une requête GET vers l'API JSON de Domoticz via cURL.
 */
function domoticz_get($url, $user, $pass)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_USERPWD, "$user:$pass");

    $raw = curl_exec($ch);

    if (curl_errno($ch)) {
        echo "  CURL ERROR: " . curl_error($ch) . "\n";
        curl_close($ch);
        return null;
    }

    curl_close($ch);

    $data = json_decode($raw, true);

    return ($data && $data['status'] === 'OK') ? $data : null;
}


/* ── Partie 1 : mise à jour des dispositifs ────────── */

echo "=== PART 1: Updating dispositif ===\n\n";

$data = domoticz_get(
    "$DOMOTICZ_URL/json.htm?type=command&param=getdevices&filter=all&used=true",
    $DOMOTICZ_USER,
    $DOMOTICZ_PASS
);

if (!$data) {
    die("ERROR: Could not reach Domoticz.\n");
}

$stmt = $conn->prepare("
    UPDATE dispositif
    SET nom = ?, typeDispositif = ?, typeDomotic = ?, sousType = ?,
        unitId = ?, alimentationType = ?, niveauBatterie = ?
    WHERE idxDomoticz = ?
");

if (!$stmt) {
    die("Prepare failed: " . $conn->error . "\n");
}

foreach ($data['result'] as $d) {

    $idx = (int) ($d['idx'] ?? 0);
    $nom = (string) ($d['Name'] ?? 'Inconnu');
    $typeDomo = (string) ($d['Type'] ?? '');
    $sousType = (string) ($d['SubType'] ?? '');
    $unitId = (int) ($d['Unit'] ?? 0);

    // BatteryLevel = 255 signifie alimentation secteur
    $battery = (isset($d['BatteryLevel']) && $d['BatteryLevel'] != 255)
        ? (int) $d['BatteryLevel']
        : null;

    $alim = ($battery !== null) ? 'battery' : 'secteur';
    $typeEnum = isset($idx_to_type[$idx]) ? $idx_to_type[$idx] : map_type($typeDomo, $sousType);

    $stmt->bind_param("ssssissi", $nom, $typeEnum, $typeDomo, $sousType, $unitId, $alim, $battery, $idx);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo "  Updated: [$idx] $nom\n";
    } else {
        echo "  Skipped (not in DB yet): [$idx] $nom\n";
    }
}

$stmt->close();


/* ── Partie 2 : enregistrement des mesures ─────────── */

echo "\n=== PART 2: Recording mesures ===\n\n";

$result = $conn->query("SELECT idDispositif, idxDomoticz, nom FROM dispositif");

if (!$result) {
    die("Query failed: " . $conn->error . "\n");
}

$stmtMesure = $conn->prepare("
    INSERT INTO mesure (dateEnregistrement, typeValeur, Valeur, unite, idDispositif)
    VALUES (NOW(), ?, ?, ?, ?)
");

if (!$stmtMesure) {
    die("Prepare failed: " . $conn->error . "\n");
}

while ($row = $result->fetch_assoc()) {

    $idxDomoticz = (int) $row['idxDomoticz'];
    $idDispositif = (int) $row['idDispositif'];
    $nomDevice = $row['nom'];

    $dev = domoticz_get(
        "$DOMOTICZ_URL/json.htm?type=command&param=getdevices&rid=$idxDomoticz",
        $DOMOTICZ_USER,
        $DOMOTICZ_PASS
    );

    if (!$dev || empty($dev['result'])) {
        echo "  No data for idx=$idxDomoticz ($nomDevice)\n";
        continue;
    }

    $device = $dev['result'][0];
    $typeValeur = (string) ($device['Type'] ?? '');
    $rawData = $device['Data'] ?? null;
    $valeur = $rawData;
    $unite = null;

    // Sépare valeur et unité depuis des chaînes comme "58 Lux" ou "23.2 C"
    if ($rawData && preg_match('/^([\d.]+)\s+([^,]+)$/', $rawData, $matches)) {
        $valeur = $matches[1];
        $unite = trim($matches[2]);
    }

    $stmtMesure->bind_param("sssi", $typeValeur, $valeur, $unite, $idDispositif);

    if ($stmtMesure->execute()) {
        echo "  Saved: [$idxDomoticz] $nomDevice → $valeur" . ($unite ? " $unite" : '') . "\n";
    } else {
        echo "  ERROR [$idxDomoticz]: " . $stmtMesure->error . "\n";
    }
}

$stmtMesure->close();
mysqli_close($conn);

echo "\nDone.\n";