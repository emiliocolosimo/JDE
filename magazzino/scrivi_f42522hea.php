<?php
require("/www/php80/htdocs/sped/config.inc.php");
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

$data = json_decode(file_get_contents("php://input"), true);

$W1PSN = $data['W1PSN'] ?? '';
$W1USER = $data['W1USER'] ?? '';
$W1PREL = $data['W1PREL'] ?? '';
$W1CONF = $data['W1CONF'] ?? '';

$now = new DateTime('now', new DateTimeZone('Europe/Rome'));

$W1DATE = $now->format('Y-m-d');
$W1TIME = $now->format('H:i:s');
$W1HOUR = $now->format('H');
$W1MINU = $now->format('i');
$W1SECO = $now->format('s');
$W1DAY  = $now->format('d');
$W1MONT = $now->format('m');
$W1YEAR = $now->format('y');
$W1TIMS = $now->format('Y-m-d-H.i.s.000000');

$server="Driver={IBM i Access ODBC Driver};System=127.0.0.1;Uid=".AH_DB2_USER.";Pwd=".AH_DB2_PASS.";";
$conn=odbc_connect($server, AH_DB2_USER, AH_DB2_PASS);

if (!$conn) {
    http_response_code(500);
    exit("Connessione DB fallita.");
}

$sql = "INSERT INTO JRGDTA94C.F42522HEA (
  W1PSN,  
W1TIME ,
W1DATE ,
W1HOUR ,
W1MINU ,
W1SECO ,
W1DAY  ,
W1MONT ,
W1YEAR ,
W1TIMS ,
W1PREL ,
W1CONF ,
W1USER 
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = odbc_prepare($conn, $sql);
$ok = odbc_execute($stmt, [
$W1PSN  ,
$W1TIME ,
$W1DATE ,
$W1HOUR ,
$W1MINU ,
$W1SECO ,
$W1DAY  ,
$W1MONT ,
$W1YEAR ,
$W1TIMS ,
$W1PREL ,
$W1CONF ,
$W1USER 
]);
echo json_encode([
    "success" => $ok,
    "message" => $ok ? "Lista $W1PSN assegnata a $W1CONF" : "Errore durante l'assegnazione della lista"
]);
?>