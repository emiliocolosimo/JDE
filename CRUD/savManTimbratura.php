<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set("log_errors", 1); 
ini_set("error_log", "/www/php80/htdocs/timbrature/logs/timbraSave_".date("Ym")."_".date("His")."_".rand(0,9999).".txt");
require_once("/www/php80/htdocs/timbrature/config.inc.php");

$server="Driver={IBM i Access ODBC Driver};System=127.0.0.1;Uid=".DB2_USER.";Pwd=".DB2_PASS.";TRANSLATE=1;";
$user=DB2_USER;
$pass=DB2_PASS;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	// Legge dati dal form HTML
	$transitRecord = array(
		"WDay" => $_POST["WDay"] ?? '',
		"Badge" => $_POST["Badge"] ?? '',
		"ReasonCode" => $_POST["ReasonCode"] ?? '',
		"Hour" => $_POST["Hour"] ?? '',
		"Minute" => $_POST["Minute"] ?? '',
		"Day" => $_POST["Day"] ?? '',
		"Month" => $_POST["Month"] ?? '',
		"Year" => $_POST["Year"] ?? '',
	);

	$requestType = $_POST['RequestType'] ?? 'M';

	$conn = odbc_connect($server, $user, $pass); 
	if (!$conn) {
		error_log("Errore connessione al database : " . odbc_errormsg($conn));
		exit("Errore connessione al database.");
	}

	$query = "SELECT BDNOME, BDCOGN, BDCOGE
	FROM BCD_DATIV2.BDGDIP0F
	WHERE BDCOGE = ('".$transitRecord["Badge"]."')";
	$res = odbc_exec($conn, $query);
if (!$res) {
	error_log("Errore query estrazione nome e cognome: " . odbc_errormsg());
	echo "<p style='color:red;'>Errore nella query badge.</p>";
	echo "<pre>Query: " . htmlspecialchars($query) . "</pre>";
	exit;
}
	$row = odbc_fetch_array($res);

	if ($row) {
		$codDip = $row["BDCOGE"];
		$res = saveTransitRecord($conn, $requestType, $codDip, $transitRecord);
		if ($res) {
			echo "<p style='color: green;'>Timbratura salvata correttamente per {$row["BDNOME"]} {$row["BDCOGN"]}.</p>";
		} else {
			echo "<p style='color: red;'>Errore durante il salvataggio della timbratura.</p>";
		}
	} else {
		echo "<p style='color: red;'>Badge non trovato.</p>";
	}
}

  $now = new DateTime(); // Oggetto data-ora corrente

// Mostra sempre il form
?>
<!DOCTYPE html>
<html lang="it">
<head>
	<meta charset="UTF-8">
	<title>Inserimento Timbratura Manuale</title>
</head>
<body>
	<h2>Inserisci Timbratura Manualmente</h2>
	<form method="POST">
		<input type="hidden" name="RequestType" value="M">
		<label>Badge: <input name="Badge" required></label><br>
<?php $currentWDay = date('w'); // 0 (domenica) - 6 (sabato) ?>
<label>Giorno Settimana (0-6):
  <input name="WDay" value="<?= $currentWDay ?>" required>
</label><br><label>Motivo:
  <select name="ReasonCode" required>
    <option value="0001" selected>Inizio</option>
    <option value="0002">Fine</option>
  </select><br>
<label>Ora (hh): <input name="Hour" value="<?= $now->format('H') ?>" required></label><br>
<label>Minuto (mm): <input name="Minute" value="<?= $now->format('i') ?>" required></label><br>
<label>Giorno: <input name="Day" value="<?= $now->format('d') ?>" required></label><br>
<label>Mese: <input name="Month" value="<?= $now->format('m') ?>" required></label><br>
<label>Anno (es. 25): <input name="Year" value="<?= $now->format('y') ?>" required></label><br>
		<br>
		<button type="submit">Salva Timbratura</button>
	</form>
</body>
</html>

<?php
function saveTransitRecord($conn, $requestType, $codDipendente, $tr) {
	$STTPRE = $requestType;
	$STSENS = "E";
	$STTYPE = "4";
	$STWDAY = $tr["WDay"];
    $STBADG = $tr["Badge"];
	$STCDDI = trim($codDipendente);
	$STRECO = $tr["ReasonCode"];
	$STHOUR = $tr["Hour"];
	$STMINU = $tr["Minute"];
	$STSECO = "00";
	$STDAY  = $tr["Day"];
	$STMONT = $tr["Month"];
	$STYEAR = $tr["Year"];
	$STDEID = "MN";

	$STDATE = "20".$STYEAR."-".$STMONT."-".$STDAY;
	$STTIME = $STHOUR.":".$STMINU.":".$STSECO;
	$STTIMS = $STDATE."-".$STHOUR.".".$STMINU.".".$STSECO.".000000";
	$STDTIN = date("Ymd");
	$STORIN = date("His");
	$STTRAS = '';


	$query = "
	INSERT INTO BCD_DATIV2.SAVTIM0F (
		STTPRE, STSENS, STTYPE, STWDAY, STBADG, STCDDI, STRECO, STHOUR, STMINU, STSECO,
		STDAY, STMONT, STYEAR, STDEID, STTIME, STDATE, STTIMS, STDTIN, STORIN, STTRAS
	) VALUES (
		?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?
	) WITH NC";
	
	$pstmt = odbc_prepare($conn, $query);
	if ($pstmt) {
		$params = [$STTPRE, $STSENS, $STTYPE, $STWDAY, $STBADG, $STCDDI, $STRECO, $STHOUR,
			$STMINU, $STSECO, $STDAY, $STMONT, $STYEAR, $STDEID, $STTIME, $STDATE,
			$STTIMS, $STDTIN, $STORIN, $STTRAS];
		$res = odbc_execute($pstmt, $params);
		if (!$res) {
			error_log("Errore inserimento: ".odbc_errormsg());
			return false;
		}
		return true;
	} else {
		error_log("Errore prepare: ".odbc_errormsg());
		return false;
	}
}
?>