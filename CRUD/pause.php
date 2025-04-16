<?php
if (!function_exists('xlLoadWebSmartObject')) {
	function xlLoadWebSmartObject($file, $class)
	{
		if (realpath($file) !== realpath($_SERVER["SCRIPT_FILENAME"])) {
			return;
		}
		$instance = new $class;
		$instance->runMain();
	}
}

require_once('websmart/v13.2/include/WebSmartObject.php');
require_once('websmart/v13.2/include/xl_functions.php');

class PAUSE extends WebSmartObject
{
	public function runMain()
	{
		error_reporting(E_ALL);
		ini_set('display_errors', 1);
		date_default_timezone_set('Europe/Rome');

		// Connect to the database
		try {
			// Defaults
			// pf_db2OdbcDsn: DSN=*LOCAL
			// pf_db2OdbcLibraryList: ;DBQ=, <<libraries, space separated, build from files included, add ',' at the beginning to not have a default schema>>
			// pf_db2OdbcOptions: ;NAM=1;TSFT=1 -> setting system naming and timestamp type to IBM standards
			$this->db_connection = new PDO(
				'odbc:' . $this->defaults['pf_db2OdbcDsn'] . $this->defaults['pf_db2OdbcLibraryList'] . $this->defaults['pf_db2OdbcOptions'],
				$this->defaults['pf_db2OdbcUserID'],
				$this->defaults['pf_db2OdbcPassword'],
				$this->defaults['pf_db2PDOOdbcOptions']
			);
		} catch (PDOException $ex) {
			die('Could not connect to database: ' . $ex->getMessage());
		}

		header('Content-Type: text/html; charset=iso-8859-1');

		// Run the specified task (place additional task calls here)
		switch ($this->pf_task) {
			// Display the main list
			case 'default':
				$this->dspListaPresenti();
				break;

		}
	}

	protected function dspListaPresenti()
	{
		$this->printPageHeader();
		$totPresenti = 0;
		$totpausafatta = 0;
		$totinpausa = 0;


		// Gestione del POST
		if ($_SERVER["REQUEST_METHOD"] === "POST") {
			$_SESSION["filtDate"] = $_POST["filtDate"] ?? date("Y-m-d");
			$_SESSION["filtCognome"] = $_POST["filtCognome"] ?? '';
			$_SESSION["filtNome"] = $_POST["filtNome"] ?? '';
			header("Location: " . $_SERVER["PHP_SELF"]);
			exit;
		}

		// Data da visualizzare (dalla sessione o data odierna)
		$filtDate = !empty($_SESSION["filtDate"]) ? $_SESSION["filtDate"] : date("Y-m-d");
		$filtCognome = strtoupper(htmlspecialchars($_SESSION['filtCognome'] ?? ''));
		$filtNome = strtoupper(htmlspecialchars($_SESSION['filtNome'] ?? ''));

		$query = "
		SELECT 
			A.STDATE,
			COALESCE(D.BDCOGN, '') AS BDCOGN,
			COALESCE(D.BDNOME, '') AS BDNOME,
			COALESCE(A.STRECO, '') AS STRECO,
			CAST(
				XMLSERIALIZE(
					CONTENT XMLAGG(XMLTEXT(A.STTIME || ', ') ORDER BY A.STTIME)
					AS VARCHAR(1000)
				)
			AS VARCHAR(1000)) AS STTIMES
		FROM BCD_DATIV2.SAVTIM0F AS A 
		LEFT JOIN BCD_DATIV2.BDGDIP0F AS D ON A.STCDDI = D.BDCOGE 
		WHERE A.STRECO in ('0001' , '0002') 
		AND A.STDATE = '" . $filtDate . "' 
		AND D.BDNOME LIKE '%" . $filtNome . "%'
  		AND D.BDCOGN LIKE '%" . $filtCognome . "%'
		GROUP BY A.STDATE, D.BDCOGN, D.BDNOME , A.STRECO
			Order by D.BDNOME , D.BDCOGN , STTIMES
		";

		$stmt = $this->db_connection->prepare($query);
		$result = $stmt->execute();
		$y = 0;
		$x = 0;
		$z = 0;

		$raggruppati = [];

		while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			foreach ($row as $key => $value) {
				$row[$key] = htmlspecialchars(rtrim($value));
				$escapedField = xl_fieldEscape($key);
				$$escapedField = $row[$key];
			}

			$presNome = $BDCOGN;
			$presCogn = $BDNOME;
			$presStreco = $STRECO;
			$presDtIn = $this->cvtDateFromDb(str_replace("-", "", $STDATE));
			$chiave = $presNome . '|' . $presCogn . '|' . $presDtIn;

			$orariArray = explode(', ', $STTIMES);
			$listaDateTime = [];

			foreach ($orariArray as $orario) {
				try {
					$dt = new DateTime($orario);
					$listaDateTime[] = $dt->format('H:i');
				} catch (Exception $e) {
					// salta orari invalidi
				}
			}

			if (!isset($raggruppati[$chiave])) {
				$raggruppati[$chiave] = [
					'presNome' => $presNome,
					'presCogn' => $presCogn,
					'presDtIn' => $presDtIn,
					'orari' => []
				];
			}

			$raggruppati[$chiave]['orari'] = array_merge($raggruppati[$chiave]['orari'], $listaDateTime);
		}

		// Stampa le righe raggruppate
		$y = 0;
		$z = 0;
		$inPausaList = [];
		$pausaFattaList = [];

		foreach ($raggruppati as $record) {
			$presNome = $record['presNome'];
			$presCogn = $record['presCogn'];
			$presDtIn = $record['presDtIn'];
			$orari = $record['orari'];
			sort($orari); // Ordina le timbrature in ordine crescente
			$orari = array_slice($orari, 0, 6); // Prendi solo le prime 6

			$ora1 = $ora2 = $ora3 = $ora4 = $ora5 = $ora6 = '';
			foreach ($orari as $index => $ora) {
				${'ora' . ($index + 1)} = $ora;
			}

			$totPause = 0;
			$inPausaAttuale = false;

			try {
				$now = new DateTime();

				if (!empty($ora1) && !empty($ora2)) {
					$dt1 = new DateTime($ora1);
					$dt2 = new DateTime($ora2);
					$totPause += abs($dt2->getTimestamp() - $dt1->getTimestamp());
				} elseif (!empty($ora1) && empty($ora2)) {
					$dt1 = new DateTime($ora1);
					$totPause += abs($now->getTimestamp() - $dt1->getTimestamp());
					$inPausaAttuale = true;
				}

				if (!empty($ora3) && !empty($ora4)) {
					$dt3 = new DateTime($ora3);
					$dt4 = new DateTime($ora4);
					$totPause += abs($dt4->getTimestamp() - $dt3->getTimestamp());
				} elseif (!empty($ora3) && empty($ora4)) {
					$dt3 = new DateTime($ora3);
					$totPause += abs($now->getTimestamp() - $dt3->getTimestamp());
					$inPausaAttuale = true;
				}

				if (!empty($ora5) && !empty($ora6)) {
					$dt5 = new DateTime($ora5);
					$dt6 = new DateTime($ora6);
					$totPause += abs($dt6->getTimestamp() - $dt5->getTimestamp());
				} elseif (!empty($ora5) && empty($ora6)) {
					$dt5 = new DateTime($ora5);
					$totPause += abs($now->getTimestamp() - $dt5->getTimestamp());
					$inPausaAttuale = true;
				}
			} catch (Exception $e) {
				$totPause = 0;
				$inPausaAttuale = false;
			}

			$totPauseMinuti = floor($totPause / 60);
			$hh = floor($totPauseMinuti / 60);
			$mm = $totPauseMinuti % 60;
			$totPauseHHMM = sprintf('%dh %02dm', $hh, $mm);

			// Salva i dati in un array temporaneo
			$recordData = compact(
				'presNome',
				'presCogn',
				'presDtIn',
				'ora1',
				'ora2',
				'ora3',
				'ora4',
				'ora5',
				'ora6',
				'totPauseMinuti',
				'totPauseHHMM',
				'inPausaAttuale'
			);

			if ($inPausaAttuale) {
				$inPausaList[] = $recordData;
			} else {
				$pausaFattaList[] = $recordData;
			}
		}

		// --- PRIMA in pausa ---
		if (!empty($inPausaList)) {
			$this->writeSegment("titoloinpausa", array_merge(get_object_vars($this), get_defined_vars()));
			foreach ($inPausaList as $record) {
				extract($record);
				$this->writeSegment("rigainpausa", array_merge(get_object_vars($this), get_defined_vars()));
				$totinpausa++;
			}
		}

		// --- POI chi ha fatto pausa ---
		if (!empty($pausaFattaList)) {
			$this->writeSegment("titolopausafatta", array_merge(get_object_vars($this), get_defined_vars()));
			foreach ($pausaFattaList as $record) {
				extract($record);
				$this->writeSegment("rigapausafatta", array_merge(get_object_vars($this), get_defined_vars()));
				$totpausafatta++;
			}
		}

		//TOTALE ORE:
		if ($x == 0)
			$this->writeSegment("hPresenti", array_merge(get_object_vars($this), get_defined_vars()));

		$query = "		SELECT 
			A.STDATE,
			COALESCE(D.BDCOGN, '') AS BDCOGN,
			COALESCE(D.BDNOME, '') AS BDNOME,
			CAST(
				XMLSERIALIZE(
					CONTENT XMLAGG(XMLTEXT(A.STTIME || ', ') ORDER BY A.STTIME)
					AS VARCHAR(1000)
				)
			AS VARCHAR(1000)) AS STTIMES
		FROM BCD_DATIV2.SAVTIM0F AS A 
		LEFT JOIN BCD_DATIV2.BDGDIP0F AS D ON A.STCDDI = D.BDCOGE 
		WHERE A.STRECO = '0000' 
		AND A.STDATE = '" . $filtDate . "' 
		AND D.BDNOME LIKE '%" . $filtNome . "%'
  		AND D.BDCOGN LIKE '%" . $filtCognome . "%'
		AND NOT EXISTS(
			SELECT 1 
			FROM BCD_DATIV2.SAVTIM0F AS B  
			WHERE B.STTIMS > A.STTIMS 
			AND B.STCDDI = A.STCDDI  
			AND B.STSENS = 'U'  
			AND A.STRECO = '0000' 
			AND A.STDATE = '" . $filtDate . "' 
			FETCH FIRST ROW ONLY
		)  
		GROUP BY A.STDATE, D.BDCOGN, D.BDNOME
			Order by D.BDNOME , D.BDCOGN
		";
		$stmt = $this->db_connection->prepare($query);
		$result = $stmt->execute();
		while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {


			foreach (array_keys($row) as $key) {
				$row[$key] = htmlspecialchars(rtrim($row[$key]));
				$escapedField = xl_fieldEscape($key);
				$$escapedField = $row[$key];
			}

			$presNome = $BDNOME;
			$presCogn = $BDCOGN;
			$errpausagen = '';
			$presDtIn = $this->cvtDateFromDb(str_replace("-", "", $STDATE));

			$presentiInSede[] = ['nome' => $presNome, 'cognome' => $presCogn, 'errpausagen' => $errpausagen];

			$totPresenti++;
			$x++;
		}

		$this->writeSegment("totpresenti", array_merge(get_object_vars($this), [
			'errpausagen' => $errpausagen,
			'presentiInSede' => $presentiInSede,
			'totPresenti' => $totPresenti,
			'totpausafatta' => $totpausafatta,
			'totinpausa' => $totinpausa
		]));
		$this->printPageFooter();
	}

	protected function cvtDateFromDb($date)
	{
		return substr($date, 6, 2) . "-" . substr($date, 4, 2) . "-" . substr($date, 0, 4);
	}

	protected function cvtTimeFromDb($time)
	{
		$time = str_pad($time, 6, "0", STR_PAD_LEFT);
		return substr($time, 0, 2) . ":" . substr($time, 2, 2) . ":" . substr($time, 4, 2);
	}

	protected function printPageHeader()
	{
		$filtNome = strtoupper($_SESSION['filtNome'] ?? '');
		$filtCognome = strtoupper($_SESSION['filtCognome'] ?? '');
		$filtDate = $_SESSION['filtDate'] ?? date('Y-m-d');
		$dataVis = date('d/m/Y', strtotime($_SESSION['filtDate'])) ?? date('d-m-Y');

		echo <<<SEGDATA
	<!DOCTYPE html>
	<html>
	  <head>

	  <meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>Pause</title>
		<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
		<link rel="stylesheet" href="websmart/v13.2/Responsive/css/bootstrap.min.css" />
		<link rel="stylesheet" href="websmart/v13.2/Responsive/css/screen.css" />
		<link rel="stylesheet" href="websmart/v13.2/Responsive/css/jquery-ui.css" />
	
		<style>
			h1 {
				font-size: 42px;
			}
			form .form-control {
				font-size: 16px;
			}
			form .form-label {
				font-weight: 500;
			}
			#contents {
				padding: 20px;
			}
			.btn {
				font-size: 16px;
			}
			@media print {
				#printButton, form {
					display: none;
				}
				table.table tr td, table.table tr th {
					font-size: 12px !important;
					padding: 2px !important;
				}
			}
		</style>
	
		<script src="websmart/v13.2/js/jquery.min.js"></script>
		<script src="websmart/v13.2/Responsive/js/bootstrap.min.js"></script>
	  </head>
	  <body>
		<div id="outer-content">
<div id="page-content" class="container-fluid">
  <div class="text-center my-4">
    <img src="include/logo.jpg" alt="Logo" style="max-height: 80px;">
  </div>
  <div class="clearfix"></div>
  <div id="contents">
				<div class="text-end mb-3">
				  <button id="printButton" type="button" onclick="window.print();" class="btn btn-outline-secondary">
				      <i class="bi bi-printer"></i>
				  </button>
				</div>
				<form method="POST" class="mb-4">
				  <div class="row g-3 align-items-end">
					<div class="col-md-3">
					  <label for="filtDate" class="form-label">Data:</label>
					  <input type="date" id="filtDate" name="filtDate" class="form-control" value="{$filtDate}">
					</div>
					<div class="col-md-3">
					  <label for="filtCognome" class="form-label">Cognome:</label>
					  <input type="text" id="filtCognome" name="filtCognome" class="form-control text-uppercase" value="{$filtCognome}">
					</div>
					<div class="col-md-3">
					  <label for="filtNome" class="form-label">Nome:</label>
					  <input type="text" id="filtNome" name="filtNome" class="form-control text-uppercase" value="{$filtNome}">
					</div>
					<div class="col-md-3 d-flex flex-column">
					  <label class="form-label invisible">Azioni</label>
					  <div class="d-flex gap-2 mt-auto">
						<button type="submit" class="btn btn-primary w-50">Filtra</button>
						<button type="button" class="btn btn-success w-50" onclick="resetForm()">Reset</button>
					  </div>
					</div>
				  </div>
				</form>
	
<div class="mb-3">
  <div id="divTotPresenti"><strong>In sede:</strong> <span id="totPresenti"></span></div>
  <div id="divInPausa"><strong>In Pausa:</strong> <span id="totinpausa"></span></div>
  <div id="divTotPausa"><strong>Che hanno fatto Pausa:</strong> <span id="totpausafatta"></span></div>
</div>

<div class="text-center mb-4">
  <h4 class="fw-bold text-primary">Data selezionata: {$dataVis}</h4>
</div>
				<div class="table-responsive">
					<table class="table table-striped table-bordered">
						<thead>
SEGDATA;
		return;
	}
	protected function printPageFooter()
	{
		echo <<<SEGDATA
		</tbody>
		</table>
		</div> <!-- table-responsive -->
		</div> <!-- contents -->
		</div> <!-- page-content -->
		</div> <!-- outer-content -->
		</body>
		</html>
		SEGDATA;
		return;
	}

	function writeSegment($xlSegmentToWrite, $segmentVars = array())
	{
		foreach ($segmentVars as $arraykey => $arrayvalue) {
			${$arraykey} = $arrayvalue;
		}
		// Make sure it's case insensitive
		$xlSegmentToWrite = strtolower($xlSegmentToWrite);

		// Output the requested segment:

		if ($xlSegmentToWrite == "titoloinpausa") {

			echo <<<SEGDTA
				<thead>
		<tr> 
		<tr style="background-color:#92a2a8;color:white;">
 	<td colspan="10"><strong>In Pausa:</strong></td> 
</tr>
		<th>Cognome Nome</th>
		<th>Inizio Pausa 1</th>
		<th>Fine Pausa 1</th>
		<th>Inizio Pausa 2</th>
		<th>Fine Pausa 2</th>
		<th>Inizio Pausa 3</th>
		<th>Fine Pausa 3</th>
		<th>Totale Pausa</th>
					</tr>
				</thead>
				<tbody>
SEGDTA;
			return;
		}

		if ($xlSegmentToWrite == "titolopausafatta") {

			echo <<<SEGDTA
				<thead>
		<tr> 
		<tr style="background-color:#92a2a8;color:white;">
 	<td colspan="10"><strong>Che hanno fatto Pausa:</strong></td> 
</tr>
		<th>Cognome Nome</th>
		<th>Inizio Pausa 1</th>
		<th>Fine Pausa 1</th>
		<th>Inizio Pausa 2</th>
		<th>Fine Pausa 2</th>
		<th>Inizio Pausa 3</th>
		<th>Fine Pausa 3</th>
		<th>Totale Pausa</th>
					</tr>
				</thead>
				<tbody>
SEGDTA;
			return;
		}


		if ($xlSegmentToWrite == "hpresenti") {

			echo <<<SEGDTA
				<thead>
						<tr style="background-color:#92a2a8;color:white;">
 	<td colspan="9"><strong>In Sede: </strong></td> 
</tr>
 	<td colspan="9"><strong>Cognome Nome </strong></td> 
		</thead>
		<tbody>
SEGDTA;
			return;
		}

		if ($xlSegmentToWrite == "rigainpausa") {
			$errtotPauseMinuti = '';
			$errpausa1lunga = '';
			$errpausa2lunga = '';
			$errtroppepausa = '';
			$errinpausaattuale = '';
			$errpausagen = '';

			try {
				$ora5Valorizzata = !empty($ora5);
				$condizione1 = false;
				$condizione2 = false;
				$condtotPauseMinuti = $totPauseMinuti > 20;

				if (!empty($ora1) && !empty($ora2)) {
					$dt1 = new DateTime($ora1);
					$dt2 = new DateTime($ora2);
					$intervallo1 = abs($dt2->getTimestamp() - $dt1->getTimestamp());
					$condizione1 = $intervallo1 > 600;
				}

				if (!empty($ora3) && !empty($ora4)) {
					$dt3 = new DateTime($ora3);
					$dt4 = new DateTime($ora4);
					$intervallo2 = abs($dt4->getTimestamp() - $dt3->getTimestamp());
					$condizione2 = $intervallo2 > 600;
				}

				if ($condizione1 || $condizione2 || $ora5Valorizzata || $inPausaAttuale || $condtotPauseMinuti) {
					$errpausagen = 'style="background-color: pink;"';
				}

				if ($condizione1) {
					$errpausa1lunga = 'style="background-color: red;"';
				}

				if ($condizione2) {
					$errpausa2lunga = 'style="background-color: red;"';
				}

				if ($ora5Valorizzata) {
					$errtroppepausa = 'style="background-color: orange;"';
				}
				if ($inPausaAttuale) {
					$errinpausaattuale = 'style="background-color: yellow;"';
				}
				if ($condtotPauseMinuti) {
					$errtotPauseMinuti = 'style="background-color: red;"';
				}
			} catch (Exception $e) {
				$stileRiga = '';
			}

			echo <<<SEGDTA
<tr>
    <td $errinpausaattuale>{$presNome} {$presCogn}</td>
    <td $errinpausaattuale>{$presDtIn}</td>
    <td $errpausa1lunga>{$ora1}</td>
	<td $errpausa1lunga>{$ora2}</td> 
 	<td $errpausa2lunga>{$ora3}</td>
  	<td $errpausa2lunga>{$ora4}</td>
  	<td $errtroppepausa>{$ora5}</td>
    <td $errtroppepausa>{$ora6}</td>
	<td $errtotPauseMinuti>{$totPauseMinuti}</td>
</tr>

SEGDTA;
			return;
		}

		if ($xlSegmentToWrite == "rigapausafatta") {
			$errtotPauseMinuti = '';
			$errpausa1lunga = '';
			$errpausa2lunga = '';
			$errtroppepausa = '';
			$errinpausaattuale = '';

			try {
				$ora5Valorizzata = !empty($ora5);
				$condizione1 = false;
				$condizione2 = false;
				$condtotPauseMinuti = $totPauseMinuti > 20;

				if (!empty($ora1) && !empty($ora2)) {
					$dt1 = new DateTime($ora1);
					$dt2 = new DateTime($ora2);
					$intervallo1 = abs($dt2->getTimestamp() - $dt1->getTimestamp());
					$condizione1 = $intervallo1 > 600;
				}

				if (!empty($ora3) && !empty($ora4)) {
					$dt3 = new DateTime($ora3);
					$dt4 = new DateTime($ora4);
					$intervallo2 = abs($dt4->getTimestamp() - $dt3->getTimestamp());
					$condizione2 = $intervallo2 > 600;
				}

				if ($condtotPauseMinuti) {
					$errtotPauseMinuti = 'style="background-color: red;"';
				}

				if ($condizione1) {
					$errpausa1lunga = 'style="background-color: red;"';
				}

				if ($condizione2) {
					$errpausa2lunga = 'style="background-color: red;"';
				}

				if ($ora5Valorizzata) {
					$errtroppepausa = 'style="background-color: orange;"';
				}
				if ($inPausaAttuale) {
					$errinpausaattuale = 'style="background-color: yellow;"';
				}
				if ($condtotPauseMinuti) {
					$errtotPauseMinuti = 'style="background-color: red;"';
				}
			} catch (Exception $e) {
				$stileRiga = '';
			}

			echo <<<SEGDTA
<tr>
    <td $errinpausaattuale>{$presNome} {$presCogn} {$inPausaAttuale}</td>
    <td $errpausa1lunga>{$ora1}</td>
	<td $errpausa1lunga>{$ora2}</td> 
 	<td $errpausa2lunga>{$ora3}</td>
  	<td $errpausa2lunga>{$ora4}</td>
  	<td $errtroppepausa>{$ora5}</td>
    <td $errtroppepausa>{$ora6}</td>
	<td $errtotPauseMinuti>{$totPauseMinuti}</td>
</tr>

SEGDTA;
			return;
		}

		if ($xlSegmentToWrite == "totpresenti") {
			if (!empty($presentiInSede)) {
				$count = 0;
				$total = count($presentiInSede);

				echo '<tr>'; // apre la prima riga

				foreach ($presentiInSede as $index => $presente) {
					$presNome = htmlspecialchars($presente['nome']);
					$presCogn = htmlspecialchars($presente['cognome']);
					$errpausagen = $presente['errpausagen'] ?? '';

					echo "<td $errpausagen>{$presCogn} {$presNome}</td>";
					$count++;

					// Chiude e apre ogni 9 celle, tranne dopo l'ultimo elemento
					if ($count % 8 === 0 && $count < $total) {
						echo '</tr><tr>';
					}
				}

				// Se l'ultima riga ha meno di 9 celle, la chiude
				if ($count % 9 !== 0) {
					echo '</tr>';
				}
			}

			// JS per aggiornare i contatori
			echo <<<JS
		<script> 
			$("#totPresenti").html("$totPresenti");
			$("#totpausafatta").html("$totpausafatta");
			$("#totinpausa").html("$totinpausa");
		</script>
		JS;

			return;
		}


		if ($xlSegmentToWrite == "nessunpresente") {

			echo <<<SEGDTA
<h4 class="text-center">Nessun in pausa trovato</h4>
SEGDTA;
			return;
		}

		// If we reach here, the segment is not found
		echo ("Segment $xlSegmentToWrite is not defined! ");
	}

	// return a segment's content instead of outputting it to the browser
	function getSegment($xlSegmentToWrite, $segmentVars = array())
	{
		ob_start();

		$this->writeSegment($xlSegmentToWrite, $segmentVars);

		return ob_get_clean();
	}

	function __construct()
	{

		$this->pf_liblLibs[1] = 'BCD_DATIV2';

		parent::__construct();

		$this->pf_scriptname = 'pause.php';
		$this->pf_wcm_set = 'PRODUZIONE';


		$this->xl_set_env($this->pf_wcm_set);


	}
}


xlLoadWebSmartObject(__FILE__, 'PAUSE'); ?>

<script>
	function resetForm() {
		document.getElementById('filtDate').value = new Date().toISOString().split('T')[0];
		document.getElementById('filtCognome').value = '';
		document.getElementById('filtNome').value = '';
		document.forms[0].submit();
	}
</script>