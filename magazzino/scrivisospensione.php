<?php
session_start();
if ($_SESSION['bdauth'] !== 'UFFICIOCOMMERCIALE') die("Accesso negato");

include("config.inc.php");

$numlista = $_POST['numlista'] ?? null;
if (!$numlista) die("Numero lista mancante");

$now = new DateTime('now', new DateTimeZone('Europe/Rome'));

$utente = $_SESSION['nomeutente'] ?? 'Sconosciuto';
$data = $now->format('Y-m-d');
$ora  = $now->format('H:i:s');
$timestamp  = $now->format('Y-m-d-H.i.s.000000');
$messaggio = "Lista $numlista sospesa da $utente";

// Connessione ODBC
$server = "Driver={IBM i Access ODBC Driver};System=127.0.0.1;Uid=" . DB2_USER . ";Pwd=" . DB2_PASS . ";TRANSLATE=1;";
$conn = odbc_connect($server, DB2_USER, DB2_PASS);
if (!$conn) die("Errore connessione: " . odbc_errormsg());

$sql = "SELECT MAX(W1IDMS) AS MAXID FROM JRGDTA94C.F42522MSG";
$stmt = odbc_exec($conn, $sql);
$row = odbc_fetch_array($stmt);

// Recupera il valore corretto
$ultimoId = (int)($row['MAXID'] ?? 0);
$nuovoId = $ultimoId + 1;

// 2. Inserisci record in F42522MSG
// Limita a 12 caratteri
$idmsg = 'MSG' . $nuovoId;
$sql2 = "INSERT INTO JRGDTA94C.F42522MSG (W1IDMS, W1PSN, W1TIME, W1DATE, W1TIMS, W1USER, W1SOSP, W1MSAT , W1MSG)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt2 = odbc_prepare($conn, $sql2);
$res2 = odbc_execute($stmt2, [
    $idmsg,
    $numlista,
    $ora,
    $data,
    $timestamp,
    $utente,
    '1',
    '1',
    $messaggio
]);
if (!$res2) die("Errore inserimento log: " . odbc_errormsg($conn));

header("Location: sospendiliste.php");
exit;
?>