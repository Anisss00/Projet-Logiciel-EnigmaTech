<?php

// ── 1. Connect to the database ───────────────────────────────────────────────
$conn = mysqli_connect("localhost", "phpmyadmin", "tp", "enigmatech");
mysqli_set_charset($conn, "utf8");

if (!$conn) {
    die("DB connection failed: " . mysqli_connect_error());
}

// ── 2. Domoticz config ───────────────────────────────────────────────────────
$DOMOTICZ_URL  = "http://192.168.4.1:8080";
$DOMOTICZ_USER = "admin";
$DOMOTICZ_PASS = "domoticz";

// ── 3. Type overrides ────────────────────────────────────────────────────────
// Domoticz doesn't always report the correct type for every device.
// This map forces the correct type for specific devices by their idx.
$idx_to_type = [
    34 => 'door_sensor', // Detecteur porte
    35 => 'door_sensor', // Home Security Zstick 005
    41 => 'door_sensor', // Home Security Zstick 006
];

// ── 4. Helper: guess device type from Domoticz Type + SubType ────────────────
function map_type($type, $subType)
{
    $type    = strtolower($type);
    $subType = strtolower($subType);

    if (strpos($subType, 'door')    !== false) return 'door_sensor';
    if (strpos($subType, 'contact') !== false) return 'door_sensor';
    if ($type === 'light/switch' && $subType === 'switch') return 'button';
    if ($type === 'usage') return 'wall_plug';

    return 'multisensor'; // default fallback
}

// ── 5. Helper: call the Domoticz JSON API ────────────────────────────────────
// cURL is PHP's way of making HTTP requests to external services.
function domoticz_get($url, $user, $pass)
{
    $ch = curl_init(); // create a cURL session

    curl_setopt($ch, CURLOPT_URL,            $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  // return response as string
    curl_setopt($ch, CURLOPT_TIMEOUT,        10);    // give up after 10 seconds
    curl_setopt($ch, CURLOPT_HTTPAUTH,       CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_USERPWD,        "$user:$pass");

    $raw = curl_exec($ch); // actually send the request

    if (curl_errno($ch)) {
        echo "  CURL ERROR: " . curl_error($ch) . "\n";
        curl_close($ch);
        return null;
    }

    curl_close($ch);

    // Domoticz returns JSON — decode it into a PHP array
    $data = json_decode($raw, true);

    // Only return data if Domoticz says "OK"
    if ($data && $data['status'] === 'OK') {
        return $data;
    }
    return null;
}


// ════════════════════════════════════════════════════════════════════════════
// PART 1 — Update device info in dispositif
// We only UPDATE existing rows. We never INSERT here because we don't know
// which room (IdSalle) to assign a brand new device to — that must be done
// manually or via a game SQL script.
// ════════════════════════════════════════════════════════════════════════════
echo "=== PART 1: Updating dispositif ===\n\n";

// Fetch all devices currently marked as "used" in Domoticz
$data = domoticz_get(
    "$DOMOTICZ_URL/json.htm?type=command&param=getdevices&filter=all&used=true",
    $DOMOTICZ_USER,
    $DOMOTICZ_PASS
);

if (!$data) {
    die("ERROR: Could not reach Domoticz.\n");
}

// Prepare the UPDATE query once, then reuse it for each device
$stmt = $conn->prepare("
    UPDATE dispositif SET
        nom              = ?,
        typeDispositif   = ?,
        typeDomotic      = ?,
        sousType         = ?,
        unitId           = ?,
        alimentationType = ?,
        niveauBatterie   = ?
    WHERE idxDomoticz = ?
");
if (!$stmt) { die("Prepare failed: " . $conn->error . "\n"); }

// Loop over every device Domoticz returned
foreach ($data['result'] as $d) {

    $idx      = (int)   ($d['idx']     ?? 0);
    $nom      = (string)($d['Name']    ?? 'Inconnu');
    $typeDomo = (string)($d['Type']    ?? '');
    $sousType = (string)($d['SubType'] ?? '');
    $unitId   = (int)   ($d['Unit']    ?? 0);

    // BatteryLevel = 255 means the device is mains-powered (no battery)
    $battery = (isset($d['BatteryLevel']) && $d['BatteryLevel'] != 255)
                ? (int)$d['BatteryLevel'] : null;
    $alim    = ($battery !== null) ? 'battery' : 'secteur';

    // Use forced type if defined, otherwise auto-detect
    $typeEnum = isset($idx_to_type[$idx]) ? $idx_to_type[$idx] : map_type($typeDomo, $sousType);

    // bind_param type string: s=string, i=integer
    // Order must match the ? placeholders in the query above
    $stmt->bind_param("ssssissi",
        $nom, $typeEnum, $typeDomo, $sousType, $unitId, $alim, $battery, $idx
    );
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo "  Updated: [$idx] $nom\n";
    } else {
        echo "  Skipped (not in DB yet): [$idx] $nom\n";
    }
}

$stmt->close();


// ════════════════════════════════════════════════════════════════════════════
// PART 2 — Record a fresh sensor reading for every device in the DB
// This is what jeu.php reads to check if a step is validated.
// Run this script on a cron job every minute to keep data fresh.
// ════════════════════════════════════════════════════════════════════════════
echo "\n=== PART 2: Recording mesures ===\n\n";

// Get every device we have in our database
$result = $conn->query("SELECT idDispositif, idxDomoticz, nom FROM dispositif");

if (!$result) {
    die("Query failed: " . $conn->error . "\n");
}

// Prepare the INSERT once, reuse for each device
$stmtMesure = $conn->prepare("
    INSERT INTO mesure (dateEnregistrement, typeValeur, Valeur, unite, idDispositif)
    VALUES (NOW(), ?, ?, ?, ?)
");
if (!$stmtMesure) { die("Prepare failed: " . $conn->error . "\n"); }

// Loop over each device in our DB
while ($row = $result->fetch_assoc()) {

    $idxDomoticz  = (int)$row['idxDomoticz'];
    $idDispositif = (int)$row['idDispositif'];
    $nomDevice    = $row['nom'];

    // Ask Domoticz for the current value of this specific device
    $dev = domoticz_get(
        "$DOMOTICZ_URL/json.htm?type=command&param=getdevices&rid=$idxDomoticz",
        $DOMOTICZ_USER,
        $DOMOTICZ_PASS
    );

    if (!$dev || empty($dev['result'])) {
        echo "  No data for idx=$idxDomoticz ($nomDevice)\n";
        continue; // skip to next device
    }

    $device     = $dev['result'][0];
    $typeValeur = (string)($device['Type'] ?? '');
    $rawData    = $device['Data'] ?? null; // e.g. "23.2 C" or "On" or "58 Lux"
    $valeur     = $rawData;
    $unite      = null;

    // Try to split "58 Lux" into value=58, unit=Lux
    // preg_match uses a regex: ^ = start, [\d.]+ = digits/dot, \s+ = space, (.+) = rest
    if ($rawData && preg_match('/^([\d.]+)\s+([^,]+)$/', $rawData, $matches)) {
        $valeur = $matches[1]; // "58"
        $unite  = trim($matches[2]); // "Lux"
    }
    // Note: "23.2 C, 62 %" won't match (has a comma) so it stays as-is in Valeur

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