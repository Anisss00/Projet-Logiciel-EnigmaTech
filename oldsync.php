<?php

$conn = mysqli_connect("p:localhost", "phpmyadmin", "tp", "enigmatech");
mysqli_set_charset($conn, "utf8");

$url = "http://192.168.4.1:8080/json.htm?type=command&param=getdevices&filter=all&used=true";

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
curl_setopt($ch, CURLOPT_USERPWD, "admin:domoticz");
curl_setopt($ch, CURLOPT_UNRESTRICTED_AUTH, true);

$raw = curl_exec($ch);

if (curl_errno($ch)) {
    die("CURL ERROR: " . curl_error($ch));
}

curl_close($ch);

$data = json_decode($raw, true);

if (!$data || $data['status'] !== 'OK') {
    die("Erreur Domoticz");
}

$stmt = $conn->prepare("
    INSERT INTO Dispositif (idxDomoticz, nom, typeDomotic)
    VALUES (?, ?, ?)
    ON DUPLICATE KEY UPDATE nom = VALUES(nom)
");

foreach ($data['result'] as $d) {
    $idx = (int) $d['idx'];
    $nom = $d['Name'] ?? 'Inconnu';
    $type = $d['Type'] ?? '';

    $stmt->bind_param("iss", $idx, $nom, $type);
    $stmt->execute();

    echo "Inserted/Updated: [$idx] $nom<br>";
}

$stmt->close();
mysqli_close($conn);