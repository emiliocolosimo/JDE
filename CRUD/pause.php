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
		if ($_SERVER["REQUEST_METHOD"] === "POST" && !empty($_POST["filtDate"])) {
			$_SESSION["filtDate"] = $_POST["filtDate"];
			header("Location: " . $_SERVER["PHP_SELF"]);
			exit;
		}

		// Data da visualizzare (dalla sessione o data odierna)
		$filtDate = $_SESSION["filtDate"] ?? date("Y-m-d");

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
		GROUP BY A.STDATE, D.BDCOGN, D.BDNOME , A.STRECO
			Order by D.BDNOME , D.BDCOGN
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
			$orari = array_slice($record['orari'], 0, 6);

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
		GROUP BY A.STDATE, D.BDCOGN, D.BDNOME
			Order by D.BDNOME , D.BDCOGN
		";
		$stmt = $this->db_connection->prepare($query);
		$result = $stmt->execute();
		while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {


			foreach (array_keys($row) as $key) {
				$row[$key] = htmlspecialchars(rtrim($row[$key]));


				// make the file field names available in HTML
				$escapedField = xl_fieldEscape($key);
				$$escapedField = $row[$key];
			}

			$presNome = $BDNOME;
			$presCogn = $BDCOGN;
			$presDtIn = $this->cvtDateFromDb(str_replace("-", "", $STDATE));

			// Gestione timbrature
			$orariArray = explode(', ', $STTIMES);
			$listaDateTime = [];

			foreach ($orariArray as $orario) {
				try {
					$dt = new DateTime($orario);
					$listaDateTime[] = $dt->format('H:i');
				} catch (Exception $e) {
					// Salta orari invalidi
				}
			}

			// Inizializza le 6 colonne
			$ora1 = isset($listaDateTime[0]) ? $listaDateTime[0] : '';
			$ora2 = isset($listaDateTime[1]) ? $listaDateTime[1] : '';
			$ora3 = isset($listaDateTime[2]) ? $listaDateTime[2] : '';
			$ora4 = isset($listaDateTime[3]) ? $listaDateTime[3] : '';
			$ora5 = isset($listaDateTime[4]) ? $listaDateTime[4] : '';
			$ora6 = isset($listaDateTime[5]) ? $listaDateTime[5] : '';
			$totPause = 0;

			try {
				if (!empty($ora1) && !empty($ora2)) {
					$dt1 = new DateTime($ora1);
					$dt2 = new DateTime($ora2);
					$totPause += abs($dt2->getTimestamp() - $dt1->getTimestamp());
				}

				if (!empty($ora3) && !empty($ora4)) {
					$dt3 = new DateTime($ora3);
					$dt4 = new DateTime($ora4);
					$totPause += abs($dt4->getTimestamp() - $dt3->getTimestamp());
				}

				if (!empty($ora5) && !empty($ora6)) {
					$dt5 = new DateTime($ora5);
					$dt6 = new DateTime($ora6);
					$totPause += abs($dt6->getTimestamp() - $dt5->getTimestamp());
				}
			} catch (Exception $e) {
				$totPause = 0; // fallback
			}
			$totPauseMinuti = floor($totPause / 60);

			$this->writeSegment("totPresenti", array_merge(get_object_vars($this), get_defined_vars()));

			$totPresenti++;
			$x++;
		}
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

		echo <<<SEGDATA
	<!DOCTYPE html>
	<html>
	  <head>
		<meta name="generator" content="WebSmart" />
		<meta charset="UTF-8">
		<meta http-equiv="x-ua-compatible" content="ie=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta http-equiv="Pragma" content="no-cache" />
		<title>Pause</title>
		
		<link rel="stylesheet" href="websmart/v13.2/Responsive/css/bootstrap.min.css" type="text/css" />
		<link rel="stylesheet" href="websmart/v13.2/Responsive/css/screen.css" media="all" type="text/css" />
		<link rel="stylesheet" href="websmart/v13.2/Responsive/css/jquery-ui.css" media="all" type="text/css" />
		
		<style>
		
		h1 {
			font-size: 42px;	
		}
		#backButton, #closeListReferentiButton {
			color: black !important;
			font-size: 25px;
			text-decoration: none !important;
		} 
		#navbottom {
			text-align:center;	
		} 
		.referenteLetter {
			font-size: 52px;
			background-color: #92a2a8;
			border-radius: 35px;
			height: 70px;
			width: 70px;
			text-align: center;
			float: left;
			color: white;
		}
		.referenteName {
			font-size:24px; 
			padding-left:100px;
			padding-top:20px;
		}
		table.table tr td, table.table tr th {
			font-size:18px !important;	
		}
		#divTotPresenti {
			font-size:18px !important;	
		} 
		#divTotPausa {
			font-size:18px !important;	
		} 
		#divInPausa {
			font-size:18px !important;	
		} 
		@media print {
			h1 {
				font-size: 16px !important;		
			}
			#backButton, #printButton {
				display:none;
			}  
			table.table tr td, table.table tr th {
				font-size:12px !important;	
				padding: 2px !important;
			}
		}
		
		</style> 
		<script src="websmart/v13.2/js/jquery.min.js"></script>
		<script src="websmart/v13.2/Responsive/js/bootstrap.min.js"></script>
	  </head>
	  <body>
		<div id="outer-content" style="position:relative"> 
		  <div id="page-content">
			 
			<div class="clearfix"></div>
			<div id="contents"> 
					  <div class="table-responsive">
					<table class="table table-striped table-bordered">	
					<thead>
							<tr> 
							  <input id="printButton" type="button" onclick="window.print();" class="btn btn-lg btn-primary accept" value="Stampa" /> 
							<br>
							<br>
							<br>
						<form method="POST">
		<label for="filtDate">Scegli una data: </label>
		<input type="date" id="filtDate" name="filtDate" value="<?= date('Y-m-d') ?>">
		<input type="submit" value="Filtra">
	<br><br>
	<div id="divTotPresenti">Totale Timbrature: <span id="totPresenti"></span></div>
					<div id="divInPausa">In Pausa: <span id="totinpausa"></span></div>
					<div id="divTotPausa">Che hanno fatto Pausa: <span id="totpausafatta"></span></div>
					  <br>
					<tbody>
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
		<th>Data</th>
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
		<th>Data</th>
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
 	<td colspan="10"><strong>Entrati in Sede: </strong></td> 
</tr>
		<tr> 
		<th>Cognome Nome</th>
		<th>Data</th>
		</tr>
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

		if ($xlSegmentToWrite == "totpresenti") {
			echo <<<SEGDTA
	<tr>
    <td>{$presNome} {$presCogn}</td>
    <td>{$presDtIn}</td>
</tr>
    <script> 
   
    $("#totPresenti").html("$totPresenti");
    $("#totpausafatta").html("$totpausafatta");
	  $("#totinpausa").html("$totinpausa");

    </script>
SEGDTA;
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