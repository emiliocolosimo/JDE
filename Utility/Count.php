<?php
function fetchApiData($url) {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Evita problemi con certificati SSL
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        die("Errore nella chiamata API. Codice HTTP: $httpCode");
    }

    $data = json_decode($response, true);

    if (!is_array($data)) {
        die("Errore: la risposta non è un array valido.");
    }

    return count($data);
}

$apiUrl = "http://172.30.155.170:10099/DispArt/FirstImport/getF4105First.php?env=prod&k=sJHsdwvIFTyhDuGtZoOfevsgG1A1H2s6"; // URL di esempio
$numRecords = fetchApiData($apiUrl);

echo "Numero di record ricevuti: " . $numRecords;
?>