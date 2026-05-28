<?php

$DOMOTICZ = "http://192.168.4.1:8080";
$USER = "admin";
$PASS = "domoticz";

function domo($url, $user, $pass)
{
    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);

    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_USERPWD, "$user:$pass");

    $output = curl_exec($ch);

    if (curl_errno($ch)) {
        $err = curl_error($ch);
        curl_close($ch);
        return "CURL ERROR: $err";
    }

    curl_close($ch);
    return $output;
}

echo "=== TEST 1 : Version Domoticz ===\n";
echo domo("http://192.168.4.1:8080/json.htm?type=command&param=getversion", "admin", "domoticz") . "\n\n";

echo "=== TEST 2 : Tous les appareils ===\n";
echo domo("http://192.168.4.1:8080/json.htm?type=command&param=getdevices&filter=all&used=true&order=Name", "admin", "domoticz") . "\n\n";

echo "=== TEST 3 : Appareils utility (consommation) ===\n";
echo domo("http://192.168.4.1:8080/json.htm?type=command&param=getdevices&filter=utility&used=true", $USER, $PASS) . "\n\n";

echo "=== TEST 4 : Appareils lumières/prises ===\n";
echo domo("http://192.168.4.1:8080/json.htm?type=command&param=getdevices&filter=light&used=true", $USER, $PASS) . "\n\n";

echo "=== TEST 5 : Activer prise idx=1 ===\n";
$raw = domo("http://192.168.4.1:8080/json.htm?type=command&param=switchlight&idx=1&switchcmd=On", $USER, $PASS);
$r = json_decode($raw);
echo ($r && $r->status == "OK") ? "Prise activée\n\n" : "Erreur: $raw\n\n";

echo "=== TEST 6 : Désactiver prise idx=1 ===\n";
$raw = domo("http://192.168.4.1:8080/json.htm?type=command&param=switchlight&idx=1&switchcmd=Off", $USER, $PASS);
$r = json_decode($raw);
echo ($r && $r->status == "OK") ? "Prise désactivée\n\n" : "Erreur: $raw\n\n";

echo "=== TEST 7 : Heure serveur ===\n";
echo domo("http://192.168.4.1:8080/json.htm?type=command&param=getServerTime", $USER, $PASS) . "\n\n";

echo domo("http://192.168.4.1:8080/json.htm?type=command&param=switchlight&idx=22&switchcmd=On", $USER, $PASS);