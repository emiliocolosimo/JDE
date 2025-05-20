<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set("log_errors", 1); 
ini_set("error_log", "/www/php80/htdocs/timbrature/logs/timbraSave_".date("Ym")."_".date("His")."_".rand(0,9999).".txt");

date_default_timezone_set('Europe/Rome');

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
			echo "<p style='color: blue;'>Timbratura salvata correttamente per {$row["BDNOME"]} {$row["BDCOGN"]}.</p>";
		} else {
			echo "<p style='color: red;'>Errore durante il salvataggio della timbratura.</p>";
		}
	} else {
		echo "<p style='color: red;'>Badge non trovato.</p>";
	}
}

  $now = new DateTime(); 	
  $badgeFromUrl = $_GET['BDBADG'] ?? '';

// Mostra sempre il form
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Inserimento Timbratura Manuale</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <style>
        body { padding: 30px; }
        .form-label { font-weight: 500; }
    </style>
</head>
<body>
<div class="container">
    <h2 class="text-center mb-4">Inserisci Timbratura Manualmente</h2>

    <?php if (isset($msgSuccess)): ?>
        <div class="alert alert-success"><?= $msgSuccess ?></div>
    <?php elseif (isset($msgError)): ?>
        <div class="alert alert-danger"><?= $msgError ?></div>
    <?php endif; ?>

    <form method="POST" class="row g-3">
        <input type="hidden" name="RequestType" value="M">

        <div class="col-md-4">
            <label class="form-label">Badge</label>
            <input name="Badge" class="form-control" value="<?= htmlspecialchars($badgeFromUrl) ?>" required>
        </div>

        <div class="col-md-4">
            <label class="form-label">Giorno Settimana (0-6)</label>
            <input name="WDay" class="form-control" value="<?= date('w') ?>" required>
        </div>

        <div class="col-md-4">
            <label class="form-label">Motivo</label>
            <select name="ReasonCode" class="form-select" required>
                <option value="0001" selected>Inizio Pausa</option>
                <option value="0002">Fine Pausa</option>
            </select>
        </div>

        <div class="col-md-2">
            <label class="form-label">Ora (hh)</label>
            <input name="Hour" class="form-control" value="<?= $now->format('H') ?>" required>
        </div>

        <div class="col-md-2">
            <label class="form-label">Minuto (mm)</label>
            <input name="Minute" class="form-control" value="<?= $now->format('i') ?>" required>
        </div>

        <div class="col-md-2">
            <label class="form-label">Giorno</label>
            <input name="Day" class="form-control" value="<?= $now->format('d') ?>" required>
        </div>

        <div class="col-md-3">
            <label class="form-label">Mese</label>
            <input name="Month" class="form-control" value="<?= $now->format('m') ?>" required>
        </div>

        <div class="col-md-3">
            <label class="form-label">Anno (es. 25)</label>
            <input name="Year" class="form-control" value="<?= $now->format('y') ?>" required>
        </div>

<div class="d-flex justify-content-between gap-3 mt-4">
  <button type="button" class="btn btn-primary btn-lg w-50 me-2" onclick="window.close();">
    <i class="bi bi-x-circle"></i> Chiudi
  </button>
  <button type="submit" class="btn btn-primary btn-lg w-50 me-2">
    <i class="bi bi-save"></i> Salva Timbratura
  </button>
</div>
        </div>
    </form>
</div>
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

    $STDATE = "20{$STYEAR}-{$STMONT}-{$STDAY}";
    $STTIME = "{$STHOUR}:{$STMINU}:{$STSECO}";
    $STTIMS = "{$STDATE}-{$STHOUR}.{$STMINU}.{$STSECO}.000000";
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
            error_log("Errore inserimento: " . odbc_errormsg());
            return false;
        }
        return true;
    } else {
        error_log("Errore prepare: " . odbc_errormsg());
        return false;
    }
}
?>