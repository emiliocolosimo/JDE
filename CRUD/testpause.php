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
		if ($_SERVER["REQUEST_METHOD"] === "POST") {
			$_SESSION["filtDate"] = $_POST["filtDate"] ?? date("Y-m-d");
			$_SESSION["filtCognome"] = $_POST["filtCognome"] ?? '';
			$_SESSION["filtNome"] = $_POST["filtNome"] ?? '';
			$_SESSION["chkInPausa"] = isset($_POST["chkInPausa"]) ? 1 : 0;
			$_SESSION["chkPausaFatta"] = isset($_POST["chkPausaFatta"]) ? 1 : 0;
			$_SESSION["chkPresenti"] = isset($_POST["chkPresenti"]) ? 1 : 0;
			$_SESSION["chkError"] = isset($_POST["chkError"]) ? 1 : 0;
			header("Location: " . $_SERVER["PHP_SELF"]);
			exit;
		}

		$this->printPageHeader();
		$totPresenti = 0;
		$totpausafatta = 0;
		$totinpausa = 0;

		// Data da visualizzare (dalla sessione o data odierna)
		$filtDate = !empty($_SESSION["filtDate"]) ? $_SESSION["filtDate"] : date("Y-m-d");
		$filtCognome = strtoupper(htmlspecialchars($_SESSION['filtCognome'] ?? ''));
		$filtNome = strtoupper(htmlspecialchars($_SESSION['filtNome'] ?? ''));

		if (!isset($_SESSION["chkInPausa"])) $_SESSION["chkInPausa"] = 1;
		if (!isset($_SESSION["chkPausaFatta"])) $_SESSION["chkPausaFatta"] = 1;
		if (!isset($_SESSION["chkPresenti"])) $_SESSION["chkPresenti"] = 1;
		if (!isset($_SESSION["chkError"])) $_SESSION["chkError"] = 1;

		$query = "
		SELECT 
			A.STDATE,
			COALESCE(D.BDCOGN, '') AS BDCOGN,
			COALESCE(D.BDNOME, '') AS BDNOME,
			COALESCE(A.STRECO, '') AS STRECO,
			COALESCE(A.STDEID, '') AS STDEID,
			COALESCE(A.STCDDI, '') AS STCDDI,
			COALESCE(D.BDTIMB, '') AS BDTIMB,

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
		GROUP BY A.STDATE, D.BDCOGN, D.BDNOME , A.STRECO , A.STDEID, A.STCDDI , BDTIMB
			Order by D.BDNOME , D.BDCOGN , STTIMES
		";

		$stmt = $this->db_connection->prepare($query);
		$result = $stmt->execute();
		$y = 0;
		$x = 0;
		$z = 0;

		$raggruppati = [];
		$presentiInSede = [];
		$errpausagen = '';

		while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			foreach ($row as $key => $value) {
				$row[$key] = htmlspecialchars(rtrim($value));
				$escapedField = xl_fieldEscape($key);
				$$escapedField = $row[$key];
			}

			$presNome = $BDCOGN;
			$presCogn = $BDNOME;
			$presStreco = $STRECO;
			$DeId = $STDEID;
			$IdGest = $STCDDI;
			$IdTimb = $BDTIMB;
			$presDtIn = $this->cvtDateFromDb(str_replace("-", "", $STDATE));
			$chiave = $presNome . '|' . $presCogn . '|' . $presDtIn ;

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
					'IdGest' => $IdGest,
					'IdTimb' => $IdTimb, 	
  					'DeId' => $DeId,  
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
			$IdGest = $record ['IdGest'];
			$IdTimb = $record['IdTimb'];
$DeId = $record['DeId'];

			$errIdTimbDeid = '';
			if ($IdTimb !== $DeId && $IdTimb !== '' && $DeId !== '') {
    			$errIdTimbDeid = 'style="background-color: ORANGE;"';
					}

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
			$analisi = $this->getErrorePausa([$ora1, $ora2, $ora3, $ora4, $ora5, $ora6], $inPausaAttuale);
extract($analisi);

$errIdTimbDeid = '';

			$errIdTimbDeid = '';
			if ($IdTimb !== $DeId && $IdTimb !== '' && $DeId !== '') {
    			$errIdTimbDeid = 'style="background-color: ORANGE;"';
					}

$recordData = array_merge(compact(
	'presNome',
	'presCogn',
	'presDtIn',
	'IdGest',
	'ora1',
	'ora2',
	'ora3',
	'ora4',
	'ora5',
	'ora6',
	'totPauseMinuti',
	'totPauseHHMM',
	'inPausaAttuale',
	'DeId',
    'IdTimb',
    'errIdTimbDeid' 
), $analisi);
			if ($inPausaAttuale) {
				$inPausaList[] = $recordData;
			} else {
				$pausaFattaList[] = $recordData;
			}
		
	} 

		$showInPausa = $_SESSION["chkInPausa"] ?? true;
		$showError = $_SESSION["chkError"] ?? 1;

		// --- PRIMA in pausa ---
		if ($showInPausa && !empty($inPausaList)) {
			$this->writeSegment("titoloinpausa", array_merge(get_object_vars($this), get_defined_vars()));
	foreach ($inPausaList as $record) {
	extract($record);
	$analisi = $this->getErrorePausa([$ora1, $ora2, $ora3, $ora4, $ora5, $ora6], $inPausaAttuale);
	extract($analisi);

	// MOSTRA SOLO RECORD IN ERRORE SE chkError È ATTIVO
	if ($showError && empty($errpausagen)) {
		echo "<!-- DEBUG: {$presCogn} {$presNome} | errpausagen: {$errpausagen} -->";
		continue;
	}

	$this->writeSegment("rigainpausa", array_merge(get_object_vars($this), get_defined_vars()));
	$totinpausa++;
}
		}
		
		$showPausaFatta = $_SESSION["chkPausaFatta"] ?? 1;

		// --- POI chi ha fatto pausa ---

		if ($showPausaFatta && !empty($pausaFattaList)) {
			$this->writeSegment("titolopausafatta", array_merge(get_object_vars($this), get_defined_vars()));
foreach ($pausaFattaList as $record) {
	extract($record);
	$analisi = $this->getErrorePausa([$ora1, $ora2, $ora3, $ora4, $ora5, $ora6], $inPausaAttuale);
	extract($analisi);

	// MOSTRA SOLO RECORD IN ERRORE SE chkError È ATTIVO
	if ($showError && empty($errpausagen)) {
		continue;
	}

	$this->writeSegment("rigapausafatta", array_merge(get_object_vars($this), get_defined_vars()));
	$totpausafatta++;
}
		}

		//TOTALE ORE:
		$showPresenti = $_SESSION["chkPresenti"] ?? 1;
		if ($showPresenti && $x == 0) {
			$this->writeSegment("hPresenti", array_merge(get_object_vars($this), get_defined_vars()));
		}

		$query = "		SELECT 
			A.STDATE,
			COALESCE(D.BDCOGN, '') AS BDCOGN,
			COALESCE(D.BDNOME, '') AS BDNOME,
			COALESCE(A.STCDDI, '') AS STCDDI,
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

	  
		GROUP BY A.STDATE, D.BDCOGN, D.BDNOME , A.STCDDI
			Order by D.BDNOME , D.BDCOGN
		";
		$stmt = $this->db_connection->prepare($query);
		$result = $stmt->execute();
		while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

/*
		AND NOT EXISTS(
			SELECT 1 
			FROM BCD_DATIV2.SAVTIM0F AS B  
			WHERE B.STTIMS > A.STTIMS 
			AND B.STCDDI = A.STCDDI  
			AND B.STSENS = 'U'  
			AND A.STRECO = '0000' 
			AND A.STDATE = '" . $filtDate . "' 
			FETCH FIRST ROW ONLY


			*/
			foreach (array_keys($row) as $key) {
				$row[$key] = htmlspecialchars(rtrim($row[$key]));
				$escapedField = xl_fieldEscape($key);
				$$escapedField = $row[$key];
			}

			$presNome = $BDNOME;
			$presCogn = $BDCOGN;
			$IdGest = $STCDDI;
			$presDtIn = $this->cvtDateFromDb(str_replace("-", "", $STDATE));
			
			// prepara gli orari per analisi
			
	$IdGest = $STCDDI;
$orariArray = explode(', ', $STTIMES);
$listaDateTime = [];

foreach ($orariArray as $orario) {
	try {
		$dt = new DateTime($orario);
		$listaDateTime[] = $dt->format('H:i');
	} catch (Exception $e) {
		// ignora orari non validi
	}
}

$presentiInSede[] = [
	'nome' => $presNome,
	'cognome' => $presCogn,
	'IdGest' => $IdGest,
	'orari' => $listaDateTime
];

			$totPresenti++;
			$x++;
		}
$showPresenti = $_SESSION["chkPresenti"] ?? 1;
if ($showPresenti) {
    $this->writeSegment("totpresenti", array_merge(get_object_vars($this), [
        'errpausagen' => $errpausagen,
        'presentiInSede' => $presentiInSede,
        'totPresenti' => $totPresenti,
        'totpausafatta' => $totpausafatta,
        'totinpausa' => $totinpausa
    ]));
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

	private function getErrorePausa(array $orari, bool $inPausaAttuale = false)
{
	$errpausa1lunga = '';
	$errpausa2lunga = '';
	$errtroppepausa = '';
	$errtotPauseMinuti = '';
	$errinpausaattuale = '';
	$errpausagen = '';
	$totPause = 0;
	$ora1 = $orari[0] ?? '';
	$ora2 = $orari[1] ?? '';
	$ora3 = $orari[2] ?? '';
	$ora4 = $orari[3] ?? '';
	$ora5 = $orari[4] ?? '';
	$ora6 = $orari[5] ?? '';

	try {
		$now = new DateTime();

		if (!empty($ora1) && !empty($ora2)) {
			$dt1 = new DateTime($ora1);
			$dt2 = new DateTime($ora2);
			$intervallo1 = abs($dt2->getTimestamp() - $dt1->getTimestamp());
			$totPause += $intervallo1;
			if ($intervallo1 > 600) $errpausa1lunga = 'style="background-color: red;"';
		} elseif (!empty($ora1)) {
			$dt1 = new DateTime($ora1);
			$totPause += abs($now->getTimestamp() - $dt1->getTimestamp());
			$inPausaAttuale = true;
			if ($totPause > 600) $errpausa1lunga = 'style="background-color: red;"';
		}

		if (!empty($ora3) && !empty($ora4)) {
			$dt3 = new DateTime($ora3);
			$dt4 = new DateTime($ora4);
			$intervallo2 = abs($dt4->getTimestamp() - $dt3->getTimestamp());
			$totPause += $intervallo2;
			if ($intervallo2 > 600) $errpausa2lunga = 'style="background-color: red;"';
		} elseif (!empty($ora3)) {
			$dt3 = new DateTime($ora3);
			$durataPausa2 = 0;
			$durataPausa2 += abs($now->getTimestamp() - $dt3->getTimestamp());
			$totPause += abs($now->getTimestamp() - $dt3->getTimestamp());
			$inPausaAttuale = true;
			if ($durataPausa2 > 600) $errpausa2lunga = 'style="background-color: red;"';
		}

		if (!empty($ora5)) {
			if (!empty($ora6)) {
				$dt5 = new DateTime($ora5);
				$dt6 = new DateTime($ora6);
				$intervallo3 = abs($dt6->getTimestamp() - $dt5->getTimestamp());
				$totPause += $intervallo3;
			} else {
				$dt5 = new DateTime($ora5);
				$totPause += abs($now->getTimestamp() - $dt5->getTimestamp());
				$inPausaAttuale = true;

			}
			$errtroppepausa = 'style="background-color: orange;"';
		}

		$totPauseMinuti = floor($totPause / 60);
		if ($totPauseMinuti > 20) $errtotPauseMinuti = 'style="background-color: red;"';
		if ($inPausaAttuale) $errinpausaattuale = 'style="background-color: yellow;"';

		if (
			!empty($errpausa1lunga) ||
			!empty($errpausa2lunga) ||
			!empty($errtroppepausa) ||
			!empty($errtotPauseMinuti)
		) {
			$errpausagen = 'style="background-color: green;"';
		}

		return [
			'errpausa1lunga' => $errpausa1lunga,
			'errpausa2lunga' => $errpausa2lunga,
			'errtroppepausa' => $errtroppepausa,
			'errtotPauseMinuti' => $errtotPauseMinuti,
			'errinpausaattuale' => $errinpausaattuale,
			'errpausagen' => $errpausagen,
			'inPausaAttuale' => $inPausaAttuale,
			'totPauseMinuti' => $totPauseMinuti,
			'totPauseHHMM' => sprintf('%dh %02dm', floor($totPauseMinuti / 60), $totPauseMinuti % 60)
		];
	} catch (Exception $e) {
		return [];
	}
}
	protected function printPageHeader()
	{
		$filtNome = strtoupper($_SESSION['filtNome'] ?? '');
		$filtCognome = strtoupper($_SESSION['filtCognome'] ?? '');
		$filtDate = $_SESSION['filtDate'] ?? date('Y-m-d');
		$dataVis = date('d/m/Y', strtotime($filtDate));
		$chkInPausaChecked = isset($_SESSION['chkInPausa']) && $_SESSION['chkInPausa'] ? 'checked' : '';
		$chkPausaFattaChecked = isset($_SESSION['chkPausaFatta']) && $_SESSION['chkPausaFatta'] ? 'checked' : '';
		$chkPresentiChecked = isset($_SESSION['chkPresenti']) && $_SESSION['chkPresenti'] ? 'checked' : '';
		$chkError = isset($_SESSION['chkError']) && $_SESSION['chkError'] ? 'checked' : '';

		$chkInPausaBtnClass = isset($_SESSION['chkInPausa']) && $_SESSION['chkInPausa'] ? 'btn-primary' : 'btn-outline-primary';
		$chkPausaFattaBtnClass = isset($_SESSION['chkPausaFatta']) && $_SESSION['chkPausaFatta'] ? 'btn-primary' : 'btn-outline-primary';
		$chkPresentiBtnClass = isset($_SESSION['chkPresenti']) && $_SESSION['chkPresenti'] ? 'btn-primary' : 'btn-outline-primary';
		$chkErrorBtnClass = isset($_SESSION['chkError']) && $_SESSION['chkError'] ? 'btn-danger' : 'btn-outline-danger';

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
				<form method="POST" class="mb-5">
				  <div class="row g-3 align-items-end">
					<div class="col-md-3">
					  <label for="filtDate" class="form-label">Data:</label>
					  <input type="date" id="filtDate" name="filtDate" class="form-control" value="{$filtDate}" onchange="this.form.submit();">
					</div>
					<div class="col-md-3">
					  <label for="filtNome" class="form-label">Cognome:</label>
					  <input type="text" id="filtNome" name="filtNome" class="form-control text-uppercase" value="{$filtNome}" onchange="this.form.submit();">
					</div>
					<div class="col-md-3">
					  <label for="filtCognome" class="form-label">Nome:</label>
					  <input type="text" id="filtCognome" name="filtCognome" class="form-control text-uppercase" value="{$filtCognome}" onchange="this.form.submit();">
					</div>
<br>
<br>
<br>
<br>
<div class="col-md-9 d-flex flex-column mt-5">

  <div class="btn-group flex-wrap gap-2" role="group" aria-label="Toggle segmenti">

    <input type="checkbox" class="btn-check" id="chkInPausa" name="chkInPausa" value="1" {$chkInPausaChecked} autocomplete="off" onchange="this.form.submit();">
    <label class="btn {$chkInPausaBtnClass} d-flex align-items-center gap-2 px-3 py-2" for="chkInPausa">
      <i class="bi bi-pause-circle"></i> In Pausa
    </label>

    <input type="checkbox" class="btn-check" id="chkPausaFatta" name="chkPausaFatta" value="1" {$chkPausaFattaChecked} autocomplete="off" onchange="this.form.submit();">
    <label class="btn {$chkPausaFattaBtnClass} d-flex align-items-center gap-2 px-3 py-2" for="chkPausaFatta">
      <i class="bi bi-check-circle"></i> Pausa Fatta
    </label>

    <input type="checkbox" class="btn-check" id="chkPresenti" name="chkPresenti" value="1" {$chkPresentiChecked} autocomplete="off" onchange="this.form.submit();">
    <label class="btn {$chkPresentiBtnClass} d-flex align-items-center gap-2 px-3 py-2" for="chkPresenti">
      <i class="bi bi-person-fill-check"></i> Presenti
    </label>

    <input type="checkbox" class="btn-check" id="chkError" name="chkError" value="1" {$chkError} autocomplete="off" onchange="this.form.submit();">
    <label class="btn {$chkErrorBtnClass} d-flex align-items-center gap-2 px-3 py-2" for="chkError">
      <i class="bi bi-exclamation-triangle-fill"></i> Errori
    </label>  
	</div> 
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
<div class="row mb-4 d-flex justify-content-between">
  <!-- Totali a sinistra -->
  <div class="col-md-3 text-start">
    <div class="mb-1"><strong>Presenti:</strong> <span id="totPresenti"></span></div>
    <div class="mb-1"><strong>Attualmente In Pausa:</strong> <span id="totinpausa"></span></div>
    <div class="mb-1"><strong>Che hanno fatto Pausa:</strong> <span id="totpausafatta"></span></div>
  </div>

<div class="col-md-6 text-center">
    <div class="bg-light border rounded shadow-sm px-4 py-3 text-primary fw-bold text-start" style="font-size: 2.4rem;">
      <i class="bi bi-calendar3 me-2" style="font-size: 2.4rem;"></i>
      <strong>Data selezionata:</strong> {$dataVis}
    </div>
</div>

<div class="col-md-3">
</div>
</div>

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
 	<td colspan="10"><strong>Attualmente In Pausa:</strong></td> 
</tr>
		<th>Cognome Nome</th>
		<th>ID Badge</th>
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
		<th>ID Badge</th>
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
 	<td colspan="9"><strong>Presenti: </strong></td> 
</tr>
			<tr>
				<th>Cognome Nome</th>
				<th>ID Badge</th>
				<th>Orario 1</th>
				<th>Orario 2</th>
				<th>Orario 3</th>
				<th>Orario 4</th>
				<th>Orario 5</th>
				<th>Orario 6</th>
			</tr>
		</thead>
		<tbody>
SEGDTA;
			return;
		}

		if ($xlSegmentToWrite == "rigainpausa") {
		echo <<<SEGDTA
<tr>
    <td>{$presCogn} {$presNome}</td>
	<td>{$IdGest}</td>
	<td $errpausa1lunga $errIdTimbDeid>{$ora1}</td>
	<td $errpausa1lunga $errIdTimbDeid>{$ora2}</td> 
 	<td $errpausa2lunga $errIdTimbDeid>{$ora3}</td>
  	<td $errpausa2lunga $errIdTimbDeid>{$ora4}</td>
  	<td $errtroppepausa $errIdTimbDeid>{$ora5}</td>
    <td $errtroppepausa $errIdTimbDeid>{$ora6}</td>
	<td $errtotPauseMinuti>{$totPauseMinuti}</td>
</tr>

SEGDTA;
			return;
		}

		if ($xlSegmentToWrite == "rigapausafatta") {
						echo <<<SEGDTA
<tr>
    <td $errinpausaattuale>{$presCogn} {$presNome} {$inPausaAttuale}</td>
	<td>{$IdGest}</td>
    <td $errpausa1lunga $errIdTimbDeid>{$ora1}</td>
	<td $errpausa1lunga $errIdTimbDeid>{$ora2}</td> 
 	<td $errpausa2lunga $errIdTimbDeid>{$ora3}</td>
  	<td $errpausa2lunga $errIdTimbDeid>{$ora4}</td>
  	<td $errtroppepausa $errIdTimbDeid>{$ora5}</td>
    <td $errtroppepausa $errIdTimbDeid>{$ora6}</td>
	<td $errtotPauseMinuti>{$totPauseMinuti}</td>
</tr>

SEGDTA;
			return;
		}
		if ($xlSegmentToWrite == "inerrore") {
						echo <<<SEGDTA
<tr>
    <td $errinpausaattuale>{$presCogn} {$presNome} {$IdGest} {$inPausaAttuale}</td>
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
		foreach ($presentiInSede as $presente) {
			$presNome = htmlspecialchars($presente['nome']);
			$presCogn = htmlspecialchars($presente['cognome']);
			$IdGest = htmlspecialchars($presente['IdGest']);
			$orari = $presente['orari'];

			// Riempie fino a 6 celle
			$ora1 = $orari[0] ?? '';
			$ora2 = $orari[1] ?? '';
			$ora3 = $orari[2] ?? '';
			$ora4 = $orari[3] ?? '';
			$ora5 = $orari[4] ?? '';
			$ora6 = $orari[5] ?? '';

			echo "<tr>
				<td>{$presCogn} {$presNome}</td>
				<td>{$IdGest}</td>
				<td>{$ora1}</td>
				<td>{$ora2}</td>
				<td>{$ora3}</td>
				<td>{$ora4}</td>
				<td>{$ora5}</td>
				<td>{$ora6}</td>
			</tr>";
		}
	}

	// JS per contatori
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
	document.getElementById('chkInPausa').checked = true;
    document.getElementById('chkPausaFatta').checked = true;
    document.getElementById('chkPresenti').checked = true;
    document.forms[0].submit();
  }

  setTimeout(function() {
    location.reload();
}, 30000); // 30000 millisecondi = 30 secondi
</script>