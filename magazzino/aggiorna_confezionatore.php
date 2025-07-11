<?php
// Legge l'input JSON
$data = json_decode(file_get_contents('php://input'), true);

// Controlla che siano presenti i dati necessari
if (!isset($data['NUMLISTA'], $data['W1CONF'])) {
    echo json_encode(['success' => false, 'message' => 'Dati mancanti']);
    exit;
}

$psn = $data['NUMLISTA'];
$confezionatore = $data['W1CONF'];

// Carica la configurazione del database
require("/www/php80/htdocs/sped/config.inc.php");

$conn = odbc_connect(
    "Driver={IBM i Access ODBC Driver};System=127.0.0.1;Uid=" . AH_DB2_USER . ";Pwd=" . AH_DB2_PASS . ";TRANSLATE=1;",
    AH_DB2_USER,
    AH_DB2_PASS
);

if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Connessione fallita']);
    exit;
}

// Esegui l'UPDATE
$sql = "UPDATE JRGDTA94C.F42522HEA SET W1CONF = ? WHERE W1PSN = ?";
$stmt = odbc_prepare($conn, $sql);
$ok = odbc_execute($stmt, [$confezionatore, $psn]);

echo json_encode(['success' => $ok]);
?>