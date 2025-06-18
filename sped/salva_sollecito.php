<?php
require("/www/php80/htdocs/sped/config.inc.php");

// Ricezione POST
$w1an8 = $_POST['w1an8'] ?? '';
$w1note = $_POST['w1note'] ?? '';
$w1shan = $_POST['w1shan'] ?? '';
$w1pddj = $_POST['w1pddj'] ?? '';
$w1user = $_SERVER['REMOTE_USER'] ?? 'websmart';
$w1dtso = date('d/m/y'); // formato GG/MM/AA

if (!$w1an8 || !$w1shan || !$w1pddj) {
    http_response_code(400);
    echo "Dati mancanti." . "an8 " . $w1an8 . "shan " . $w1shan . "pddj " . $w1pddj . "user " . $w1user . "dtso " . $w1dtso . "note " . $w1note;
    exit;
}

$server = "Driver={IBM i Access ODBC Driver};System=127.0.0.1;Uid=" . AH_DB2_USER . ";Pwd=" . AH_DB2_PASS . ";TRANSLATE=1;";
$db_connection = odbc_connect($server, AH_DB2_USER, AH_DB2_PASS);

$query = "INSERT INTO JRGDTA94C.FSOLLSPE0F (W1AN8, W1SHAN, W1PDDJ, W1DTSO, W1USER, W1NOTE)
          VALUES (?, ?, ?, ?, ?, ?)";
$stmt = odbc_prepare($db_connection, $query);
$success = odbc_execute($stmt, [$w1an8, $w1shan, $w1pddj, $w1dtso, $w1user, $w1note]);

if ($success) {
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
} else {
    http_response_code(500);
    echo "Errore scrittura: " . odbc_errormsg();
}
odbc_close($db_connection);
?>