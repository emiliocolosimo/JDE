<?php
if(!function_exists('xlLoadWebSmartObject')) {
		function xlLoadWebSmartObject($file, $class) {	if(realpath($file) !== realpath($_SERVER["SCRIPT_FILENAME"])) {	return;	} $instance = new $class; $instance->runMain(); }
}

//	Program Name:		VISITATORI.php 
//	Program Title:		Visitatori
//	Created by:			matti
//	Template name:		A Simple Page.tpl
//	Purpose:        
//	Program Modifications:

require_once('websmart/v13.2/include/WebSmartObject.php');
require_once('websmart/v13.2/include/xl_functions.php');

class VISITATORI extends WebSmartObject
{   
	public function runMain()
	{
		error_reporting(E_ALL);
		ini_set('display_errors',1);
		date_default_timezone_set('Europe/Rome');
		
		// Connect to the database
		try 
		{
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
		}
		catch (PDOException $ex)
		{
			die('Could not connect to database: ' . $ex->getMessage());
		}
		
		header('Content-Type: text/html; charset=iso-8859-1');				
		
		// Run the specified task (place additional task calls here)
		switch ($this->pf_task)
		{
			// Display the main list
			case 'default':
			$this->displayPage();
			break;
			 
			case 'beginAddIngresso':
			$this->beginAddIngresso();
			break;
			
			case 'fltReferenti':
			$this->fltReferenti();
			break;
			 
			case 'endAddIngresso':
			$this->endAddIngresso();
			break;
			
			case 'dspSicurezza':
			$this->dspSicurezza();
			break;

			case 'dspPrivacy':
			$this->dspPrivacy();
			break;
			
			case 'beginAddFirmaIngresso':
			$this->beginAddFirmaIngresso();
			break;
			
			case 'endAddFirmaIngresso':
			$this->endAddFirmaIngresso();
			break;
			
			case 'dspIngressoConferma':
			$this->dspIngressoConferma();
			break;	
			
			case 'beginAddUscita':
			$this->beginAddUscita();
			break;	
			
			case 'filterUscita':
			$this->filterUscita();
			break;	
			
			case 'beginAddFirmaUscita':
			$this->beginAddFirmaUscita();
			break;	

			case 'endAddFirmaUscita':
			$this->endAddFirmaUscita();
			break;

			case 'dspUscitaConferma':
			$this->dspUscitaConferma();
			break;
					
			case 'dspListaPresenti':
			$this->dspListaPresenti();
			break;
			 		
		}
	}
	
	protected function displayPage()
	{
		$this->writeSegment('MainSeg', array_merge(get_object_vars($this), get_defined_vars()));
	}
	
	protected function beginAddIngresso() {
		unset($_SESSION["visitatori_ingresso"]);
		$this->writeSegment('ingressoAdd', array_merge(get_object_vars($this), get_defined_vars()));
	}
	
	protected function endAddIngresso() {
 		header("Content-Type: application/json");
  
		$NOME = xl_get_parameter('NOME'); 
		$COGNOME = xl_get_parameter('COGNOME');
		$REFERENTE = xl_get_parameter('REFERENTE');
		$CODREFE = xl_get_parameter('CODREFE');
		$AZIENDA = xl_get_parameter('AZIENDA');
		$NUMCART = xl_get_parameter('NUMCART');
		
		// Do any add validation here
		$errorMsg = $this->checkIngresso();
		if($errorMsg!="") {
			echo '['.$errorMsg.']';
			exit;
		}
		
		//salvo in sessione
		$_SESSION["visitatori_ingresso"]["NOME"] = $NOME;
		$_SESSION["visitatori_ingresso"]["COGNOME"] = $COGNOME;
		$_SESSION["visitatori_ingresso"]["REFERENTE"] = $REFERENTE;
		$_SESSION["visitatori_ingresso"]["CODREFE"] = $CODREFE;
		$_SESSION["visitatori_ingresso"]["AZIENDA"] = $AZIENDA;
		$_SESSION["visitatori_ingresso"]["NUMCART"] = $NUMCART; 
		session_write_close();
		
		echo '[{"stat":"OK"}]'; 
	}
	
	protected function checkIngresso() {
		$errorMsg = "";
		$errorSep = "";

		if(trim(xl_get_parameter('NOME'))=='') { $errorMsg = $errorMsg.$errorSep.'{"stat":"ERR","id":"NOME","msg":"Campo obbligatorio"}'; $errorSep = ","; }
		if(trim(xl_get_parameter('COGNOME'))=='') { $errorMsg = $errorMsg.$errorSep.'{"stat":"ERR","id":"COGNOME","msg":"Campo obbligatorio"}'; $errorSep = ","; }
		if(trim(xl_get_parameter('REFERENTE'))=='') { $errorMsg = $errorMsg.$errorSep.'{"stat":"ERR","id":"REFERENTE","msg":"Campo obbligatorio"}'; $errorSep = ","; }
		if(trim(xl_get_parameter('CODREFE'))=='') { $errorMsg = $errorMsg.$errorSep.'{"stat":"ERR","id":"REFERENTE","msg":"Campo obbligatorio"}'; $errorSep = ","; }
		if(trim(xl_get_parameter('AZIENDA'))=='') { $errorMsg = $errorMsg.$errorSep.'{"stat":"ERR","id":"AZIENDA","msg":"Campo obbligatorio"}'; $errorSep = ","; }
		if(trim(xl_get_parameter('NUMCART'))=='') { $errorMsg = $errorMsg.$errorSep.'{"stat":"ERR","id":"NUMCART","msg":"Campo obbligatorio"}'; $errorSep = ","; }
  
		return $errorMsg;
	}
	
	protected function fltReferenti() {
		$fltREFERENTE = xl_get_parameter("fltREFERENTE");
		$this->lstReferenti($fltREFERENTE);
	}
	
	protected function lstReferenti($fltREFERENTE) {
		
		$arrColori = array();
		$arrColori[] = "92a2a8";
		$arrColori[] = "57889c";
		$arrColori[] = "568a89";
		$arrColori[] = "4c4f53";
		$arrColori[] = "356e35";
		$arrColori[] = "6e587a";
		$arrColori[] = "496949";
		$arrColori[] = "b09b5b";
		$arrColori[] = "a65858";
		$arrColori[] = "c79121";
		
		$x = 0;
		$selString = "SELECT VRNOME, VRCOGN, VRCOGE 
		FROM BCD_DATIV2.VISREF0F ";
		if($fltREFERENTE!="") {
			$selString.= " WHERE UPPER(VRNOME) LIKE :fltREFERENTE1 
			OR UPPER(VRCOGN) LIKE :fltREFERENTE2 
			OR TRIM(UPPER(VRNOME)) CONCAT ' ' CONCAT TRIM(UPPER(VRCOGN)) LIKE :fltREFERENTE3
			"; 	
		} 
		$selString.= " ORDER BY VRCOGN ";
		
		$stmt = $this->db_connection->prepare($selString);
		if($fltREFERENTE!="") {
			$stmt->bindValue(':fltREFERENTE1', '%'.strtoupper($fltREFERENTE).'%', PDO::PARAM_STR);
			$stmt->bindValue(':fltREFERENTE2', '%'.strtoupper($fltREFERENTE).'%', PDO::PARAM_STR);
			$stmt->bindValue(':fltREFERENTE3', '%'.strtoupper($fltREFERENTE).'%', PDO::PARAM_STR);
		} 
			
		$result = $stmt->execute();
		while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			 
			foreach(array_keys($row) as $key)
			{
				$row[$key] = htmlspecialchars(rtrim($row[$key]));
				
				
				// make the file field names available in HTML
				$escapedField = xl_fieldEscape($key);
				$$escapedField = $row[$key];
			}
			
			$color = '#'.$arrColori[$x];
			
			$this->writeSegment("dReferente", array_merge(get_object_vars($this), get_defined_vars()));
			
			$x++;
			if($x > 9) $x = 0;
			 
		} 		
			
	}
	
	protected function dspSicurezza() {
		$this->writeSegment('sicurezza', array_merge(get_object_vars($this), get_defined_vars()));
	}	

	protected function dspPrivacy() {
		$this->writeSegment('privacy', array_merge(get_object_vars($this), get_defined_vars()));
	}
	
	protected function beginAddFirmaIngresso() {
		$this->writeSegment('firmaAddIngresso', array_merge(get_object_vars($this), get_defined_vars()));	
	}
	
	
	protected function endAddFirmaIngresso() {
 		header("Content-Type: application/json");
		
		//contatore:
		$VRPROG = $this->getProgre("VISDAT0F");
		//contatore [f]
		
		$VRNOME = $_SESSION["visitatori_ingresso"]["NOME"];
		$VRCOGN = $_SESSION["visitatori_ingresso"]["COGNOME"];
		$VRREFE = $_SESSION["visitatori_ingresso"]["REFERENTE"];
		$VRCORE = $_SESSION["visitatori_ingresso"]["CODREFE"];
		$VRSOCI = $_SESSION["visitatori_ingresso"]["NUMCART"];
		$VRNUCA = $_SESSION["visitatori_ingresso"]["NUMCART"];
		$VRDTIN = date("Ymd");
		$VRORIN = date("His");
		
		$query = "INSERT INTO BCD_DATIV2.VISDAT0F (VRPROG,VRNOME,VRCOGN,VRCORE,VRSOCI,VRNUCA,VRDTIN,VRORIN,VRDTUS,VRORUS) VALUES(:VRPROG,:VRNOME,:VRCOGN,:VRCORE,:VRSOCI,:VRNUCA,:VRDTIN,:VRORIN, 0, 0) WITH NC";
		$stmt = $this->db_connection->prepare($query);
		if (!$stmt)
		{
			echo '[{"stat":"ERR","msg":"Errore inserimento"}]';
			exit;
		} 
		// Bind the parameters
		$stmt->bindValue(':VRPROG', $VRPROG, PDO::PARAM_INT);
		$stmt->bindValue(':VRNOME', $VRNOME, PDO::PARAM_STR);
		$stmt->bindValue(':VRCOGN', $VRCOGN, PDO::PARAM_STR);
		$stmt->bindValue(':VRCORE', $VRCORE, PDO::PARAM_STR); 
		$stmt->bindValue(':VRSOCI', $VRSOCI, PDO::PARAM_STR);   
		$stmt->bindValue(':VRNUCA', $VRNUCA, PDO::PARAM_STR);  
		$stmt->bindValue(':VRDTIN', $VRDTIN, PDO::PARAM_INT);  			
		$stmt->bindValue(':VRORIN', $VRORIN, PDO::PARAM_INT);  			
		
		$result = $stmt->execute();
		if ($result === false) 
		{
			echo '[{"stat":"ERR","msg":"Errore inserimento"}]';
			exit;
		}
		
		$signCode  = xl_get_parameter("signCode");
		$signCode  = str_replace("data:image/png;base64,","",$signCode);
		$signCode = base64_decode($signCode);
		
		$signCodeFile = fopen("/www/php80/htdocs/CRUD/visitatori/signatures/".$VRPROG."_I.png", "w"); 
		fwrite($signCodeFile, $signCode);
		fclose($signCodeFile);
		
		unset($_SESSION["visitatori_ingresso"]);
		
		echo '[{"stat":"OK"}]';
		
	}	
	
	protected function dspIngressoConferma() {
		$this->writeSegment('ingressoConferma', array_merge(get_object_vars($this), get_defined_vars()));	
	}
	
	protected function beginAddUscita() {
		$this->writeSegment('uscitaAdd', array_merge(get_object_vars($this), get_defined_vars()));	
	}
	
	protected function filterUscita() {
		$fltUSCITA = xl_get_parameter("fltUSCITA");
		//if($fltUSCITA=="" || strlen(trim($fltUSCITA))<3) return;
		
		$selString = "SELECT * 
		FROM VISDAT0F 
		WHERE VRDTUS = 0 ";
		if($fltUSCITA!="") {
			$selString .= "
			AND (
				UPPER(VRNOME) LIKE :fltUSCITA1 
				OR UPPER(VRCOGN) LIKE :fltUSCITA2
				OR UPPER(TRIM(VRNOME)) CONCAT ' ' CONCAT UPPER(TRIM(VRCOGN)) LIKE :fltUSCITA3 
			)"; 
		}
		$selString .= "ORDER BY VRDTIN DESC";
		$stmt = $this->db_connection->prepare($selString);
		if (!$stmt)
		{
			echo '[{"stat":"ERR","msg":"Errore inserimento"}]';
			exit;
		} 
		if($fltUSCITA!="") {
			$stmt->bindValue(':fltUSCITA1', '%'.strtoupper($fltUSCITA).'%', PDO::PARAM_STR);
			$stmt->bindValue(':fltUSCITA2', '%'.strtoupper($fltUSCITA).'%', PDO::PARAM_STR);
			$stmt->bindValue(':fltUSCITA3', '%'.strtoupper($fltUSCITA).'%', PDO::PARAM_STR);
		}
		$result = $stmt->execute();
		if ($result === false) 
		{
			echo '[{"stat":"ERR","msg":"Errore inserimento"}]';
			exit;
		}

		
		$x = 0;
		while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			if($x==0) $this->writeSegment("hUscita", array_merge(get_object_vars($this), get_defined_vars()));
			
			foreach(array_keys($row) as $key)
			{
				$row[$key] = htmlspecialchars(rtrim($row[$key]));
				
				
				// make the file field names available in HTML
				$escapedField = xl_fieldEscape($key);
				$$escapedField = $row[$key];
			}
			
			$VRNOME_O = str_pad(substr($VRNOME,0,3),strlen($VRNOME),"X");
			$VRCOGN_O = str_pad(substr($VRCOGN,0,3),strlen($VRCOGN),"X");
			
			
			
			$this->writeSegment("dUscita", array_merge(get_object_vars($this), get_defined_vars()));
			
			$x++;
			
		}
		if($x>0) $this->writeSegment("fUscita", array_merge(get_object_vars($this), get_defined_vars()));
		else $this->writeSegment("nessunaUscita", array_merge(get_object_vars($this), get_defined_vars()));

	}
	
	protected function beginAddFirmaUscita() {
		$VRPROG = (int) xl_get_parameter("VRPROG");
		$this->writeSegment('firmaAddUscita', array_merge(get_object_vars($this), get_defined_vars()));	
	}
	  
	protected function dspUscitaConferma() { 
		$this->writeSegment("uscitaConferma", array_merge(get_object_vars($this), get_defined_vars()));
	}
	 
	protected function endAddFirmaUscita() {
 		header("Content-Type: application/json");
		 
		$VRPROG = (int) xl_get_parameter("VRPROG");
		
		$VRDTUS = date("Ymd");
		$VRORUS = date("His");
		$query = "UPDATE BCD_DATIV2.VISDAT0F SET VRDTUS = '".$VRDTUS."', VRORUS = '".$VRORUS."' WHERE VRPROG = ".$VRPROG." WITH NC";
		$res = $this->db_connection->exec($query); 		
		  
		$signCode  = xl_get_parameter("signCode");
		$signCode  = str_replace("data:image/png;base64,","",$signCode);
		$signCode = base64_decode($signCode);
		
		$signCodeFile = fopen("/www/php80/htdocs/CRUD/visitatori/signatures/".$VRPROG."_U.png", "w"); 
		fwrite($signCodeFile, $signCode);
		fclose($signCodeFile);
		  
		echo '[{"stat":"OK"}]';
		
	}	 
	
	protected function dspListaPresenti() {
		 
		$authPass = xl_get_parameter("authPass");
		if($authPass!="presenti") {
			header("Location: visitatori.php");
			exit;	
		}
		
		$totPresenti = 0;
		
		//dipendenti da file timbrature:  
		$filtDate = date("Y-m-d");
		$query = "SELECT A.STDATE, A.STTIME, COALESCE(D.BDCOGN,'') AS BDCOGN, COALESCE(D.BDNOME,'') AS BDNOME 
		FROM BCD_DATIV2.SAVTIM0F AS A 
		LEFT JOIN BCD_DATIV2.BDGDIP0F AS D ON A.STCDDI = D.BDCOGE 
		WHERE A.STSENS = 'E' 
		AND A.STDATE = '".$filtDate."'
		AND NOT EXISTS(
			SELECT 1 
			FROM BCD_DATIV2.SAVTIM0F AS B  
			WHERE B.STTIMS > A.STTIMS 
			AND B.STCDDI = A.STCDDI  
			AND B.STSENS = 'U' 
			FETCH FIRST ROW ONLY
		)  
		"; 
		$stmt = $this->db_connection->prepare($query); 
		$result = $stmt->execute();  
		$x = 0;
		while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			if($x==0) {
				$this->writeSegment("hPresenti", array_merge(get_object_vars($this), get_defined_vars()));
				$tipoPresenti = "DIPENDENTI:";
				$this->writeSegment("tPresenti", array_merge(get_object_vars($this), get_defined_vars()));
			}
			
			foreach(array_keys($row) as $key)
			{
				$row[$key] = htmlspecialchars(rtrim($row[$key]));
				
				
				// make the file field names available in HTML
				$escapedField = xl_fieldEscape($key);
				$$escapedField = $row[$key];
			}
			 
			$presNome = $BDCOGN;
			$presCogn = $BDNOME;
			$presDtIn = $this->cvtDateFromDb(str_replace("-","",$STDATE));
			$presOrIn = $STTIME;
			
			$this->writeSegment("dPresenti", array_merge(get_object_vars($this), get_defined_vars()));
			
			$totPresenti++;
			$x++;
			
		}		
		
		//visitatori:
		if($totPresenti==0) $this->writeSegment("hPresenti", array_merge(get_object_vars($this), get_defined_vars()));
		$tipoPresenti = "VISITATORI:";
		$this->writeSegment("tPresenti", array_merge(get_object_vars($this), get_defined_vars()));		
		
		$query = "SELECT * 
		FROM BCD_DATIV2.VISDAT0F 
		WHERE VRDTUS = 0
		ORDER BY VRDTIN
		";
		$stmt = $this->db_connection->prepare($query); 
		$result = $stmt->execute();  
		while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			
		 
			foreach(array_keys($row) as $key)
			{
				$row[$key] = htmlspecialchars(rtrim($row[$key]));
				
				
				// make the file field names available in HTML
				$escapedField = xl_fieldEscape($key);
				$$escapedField = $row[$key];
			}
			 
			$presNome = $VRNOME;
			$presCogn = $VRCOGN;
			$presDtIn = $this->cvtDateFromDb($VRDTIN);
			$presOrIn = $this->cvtTimeFromDb($VRORIN);
			
			$this->writeSegment("dPresenti", array_merge(get_object_vars($this), get_defined_vars()));
			
			$totPresenti++;
			$x++;
			
		}
		
		 
		if($x>0) $this->writeSegment("fPresenti", array_merge(get_object_vars($this), get_defined_vars()));
		else $this->writeSegment("nessunPresente", array_merge(get_object_vars($this), get_defined_vars()));
	}
	 
	protected function cvtDateFromDb($date) { 
		return substr($date,6,2)."-".substr($date,4,2)."-".substr($date,0,4); 
	} 	 

	protected function cvtTimeFromDb($time) { 
		$time = str_pad($time,6,"0",STR_PAD_LEFT);
		return substr($time,0,2).":".substr($time,2,2).":".substr($time,4,2); 
	} 
	 
	protected function getProgre($VCTIPO) {
		$VCANNO = date("Y");
		$selString = "SELECT VCCONT FROM BCD_DATIV2.VISCNT0F WHERE VCTIPO = '".$VCTIPO."' AND VCANNO = ".$VCANNO." FETCH FIRST ROW ONLY";
		$stmt_cnt = $this->db_connection->prepare($selString);
		if (!$stmt_cnt)
		{
			$this->dieWithPDOError($stmt_cnt);
		} 
		$result_cnt = $stmt_cnt->execute();
		if ($result_cnt === false)
		{
			$this->dieWithPDOError($stmt_cnt);
		}
		$row_cnt = $stmt_cnt->fetch(PDO::FETCH_ASSOC);
		if(!$row_cnt) {
			$VCCONT = 1;
			$selString = "INSERT INTO BCD_DATIV2.VISCNT0F (VCANNO,VCTIPO,VCCONT) VALUES ('".$VCANNO."','".$VCTIPO."','".$VCCONT."') WITH NC";
			$res_cnt = $this->db_connection->exec($selString);
			return ($VCANNO * 1000000 + $VCCONT);
		} else {
			$VCCONT = $row_cnt["VCCONT"];
			$VCCONT++;
			$selString = "UPDATE BCD_DATIV2.VISCNT0F SET VCCONT = ".$VCCONT." WHERE VCANNO = '".$VCANNO."' AND VCTIPO = '".$VCTIPO."' WITH NC";
			$res_cnt = $this->db_connection->exec($selString);
			return ($VCANNO * 1000000 + $VCCONT);
		}
		
		return false;
		
	}	
	
 

	function writeSegment($xlSegmentToWrite, $segmentVars=array())
	{
		foreach($segmentVars as $arraykey=>$arrayvalue)
		{
			${$arraykey} = $arrayvalue;
		}
		// Make sure it's case insensitive
		$xlSegmentToWrite = strtolower($xlSegmentToWrite);

	// Output the requested segment:

	if($xlSegmentToWrite == "mainseg")
	{

		echo <<<SEGDTA
<!DOCTYPE html>
<html>
  <head>
    <meta name="generator" content="WebSmart" />
    <meta charset="UTF-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Pragma" content="no-cache" />
    <title>Visitatori</title>
    
    <link rel="stylesheet" href="websmart/v13.2/Responsive/css/bootstrap.min.css" type="text/css" />
    <link rel="stylesheet" href="websmart/v13.2/Responsive/css/screen.css" media="all" type="text/css" />
    <link rel="stylesheet" href="websmart/v13.2/Responsive/css/jquery-ui.css" media="all" type="text/css" />
    
    <style>
    .btnAzione {
		padding: 17px 23px;
		font-size: 25px;
		line-height: 1.3333333;
		border-radius: 13px;	
    }
    /*#btnAzioneIngresso {
    	position:absolute;
    	left:40px;
    	bottom:50px;	
    }
    #btnAzioneUscita {
    	position:absolute;
    	right:40px;
    	bottom:50px;
    } 
    #btnAzioneListaPresenti {
    	position:absolute;
		left: 50%;
		transform: translateX(-50%);
    	width: 300px;
    	bottom:50px;		
    }*/
    .page-title-image {
    	max-width:100%;	
    }
    
    #btnContainer {
    	position: fixed;
    	bottom:50px;
    	left:0px;
    	width:100%;
    }
    
    #btnAzioneIngresso {
    	margin-left:20px;	
    }
    #btnAzioneUscita {
    	margin-right:20px;	
    }  
    
	@media all and (min-width:0px) and (max-width: 959px) {
	 	#btnAzioneUscita, #btnAzioneIngresso, #btnAzioneListaPresenti {
	 		width: 100% !important;	
	 		margin:0px !important;
	 	}
	}
     
    </style>
    
    <script src="websmart/v13.2/js/jquery.min.js"></script>
    <script src="websmart/v13.2/Responsive/js/bootstrap.min.js"></script>
  </head>
  <body>
    <div id="outer-content"> 
      <div id="page-content">
        <div id="content-header">
          <h1 class="title"></h1>
        </div>
        <div class="clearfix"></div>
        <div id="contents"> 
          <div class="text-center">
          	<img class="page-title-image" src="/crud/websmart/v13.2/images/rgp-logo.jpg" alt="logo">
          </div>
          
          <div id="btnContainer">
            <div class="row">
	          	<div class="col-xs-12 col-sm-4 text-center">
	          		<a id="btnAzioneIngresso" type="button" class="btn btn-success btnAzione" href="?task=beginAddIngresso">INGRESSO</a>
	          	</div>
	          	<div class="col-xs-12 col-sm-4 text-center">
	          		<a id="btnAzioneListaPresenti" type="button" class="btn btn-primary btnAzione" data-toggle="modal" data-target="#passwordModal">LISTA PRESENTI</a>
	          	</div>
	          	<div class="col-xs-12 col-sm-4 text-center">
	          		<a id="btnAzioneUscita" type="button" class="btn btn-danger btnAzione" href="?task=beginAddUscita">USCITA</a>
	          	</div>
          	</div>
          </div>
          
          <!--<a id="btnAzioneIngresso" type="button" class="btn btn-success btnAzione" href="?task=beginAddIngresso">INGRESSO</a>
          <a id="btnAzioneListaPresenti" type="button" class="btn btn-primary btnAzione" href="?task=dspListaPresenti">LISTA PRESENTI</a>
          <a id="btnAzioneUscita" type="button" class="btn btn-danger btnAzione" href="?task=beginAddUscita">USCITA</a>
          -->
          
        </div>
      </div>
    </div>
    
	<!-- Modal -->
	<div class="modal fade" id="passwordModal" tabindex="-1" role="dialog" aria-labelledby="passwordModalLabel" aria-hidden="true">
	  <div class="modal-dialog" role="document">
	    <div class="modal-content">
	      <div class="modal-header">
	        <h5 class="modal-title" id="passwordModalLabel">Password</h5>
	        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
	          <span aria-hidden="true">&times;</span>
	        </button>
	      </div>
	      <div class="modal-body">
	          <form id="authorization-form" action="$pf_scriptname" method="post">
	            <input type="hidden" name="task" value="dspListaPresenti" />
	 			  
	              <div class="row"> 
		              <div class="form-group col-xs-12" id="authPass_lbl">
		                <label for="authPass"> </label>
	 	                <div>
	 	                	<input type="password" id="authPass" class="form-control input-lg" name="authPass" size="50" maxlength="50">
		                	<span class="help-block" id="authPass_err"></span>
		                </div>
	 	              </div>
	              </div>
	          </form>
	      </div>
	      <div class="modal-footer">
	        <button type="button" class="btn btn-secondary" data-dismiss="modal">Chiudi</button>
	        <button type="button" class="btn btn-primary" onclick="$('#authorization-form').submit();">Procedi</button>
	      </div>
	    </div>
	  </div>
	</div>    

    
  </body>
</html>
SEGDTA;
		return;
	}
	if($xlSegmentToWrite == "ingressoadd")
	{

		echo <<<SEGDTA
<!DOCTYPE html>
<html>
  <head>
    <meta name="generator" content="WebSmart" />
    <meta charset="UTF-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Pragma" content="no-cache" />
    <title>Visitatori</title>
    
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
    </style>

<!--
		NOME
		COGNOME
		REFERENTE (TABELLATO)
		AZIENDA
		CARTELLINO ID N.
-->
    
    <script src="websmart/v13.2/js/jquery.min.js"></script>
    <script src="websmart/v13.2/Responsive/js/bootstrap.min.js"></script>
  </head>
  <body>
    <div id="outer-content" style="position:relative"> 
      <div id="page-content">
         
        <div class="clearfix"></div>
        <div id="contents"> 
	      <a id="backButton" class="glyphicon glyphicon-arrow-left" href="?task=default" title="Torna alla lista"></a>
 
 		  <h1>INSERISCI I DATI</h1>
 
          <form id="rcd-add-form" action="$pf_scriptname" method="post" style="margin-top:50px;">
            <input type="hidden" name="task" value="endAddIngresso" />
			<input type="hidden" name="CODREFE" id="CODREFE" value="" />
			  
              <div class="row"> 
	              <div class="form-group col-xs-12" id="NOME_lbl">
	                <label for="NOME">Nome *</label>
 	                <div>
 	                	<input type="text" id="NOME" class="form-control input-lg" name="NOME" size="50" maxlength="50">
	                	<span class="help-block" id="NOME_err"></span>
	                </div>
 	              </div>
              </div>
			    
              <div class="row"> 
	              <div class="form-group col-xs-12" id="COGNOME_lbl">
	                <label for="COGNOME">Cognome *</label>
 	                <div>
 	                	<input type="text" id="COGNOME" class="form-control input-lg" name="COGNOME" size="50" maxlength="50">
	                	<span class="help-block" id="COGNOME_err"></span>
	                </div>
 	              </div>
              </div>
              
              <div class="row"> 
	              <div class="form-group col-xs-12" id="REFERENTE_lbl">
	                <label for="REFERENTE">Referente *</label>
	                <div>
	                	<input type="text" id="REFERENTE" class="form-control input-lg" name="REFERENTE" size="50" maxlength="50" readonly>
	                	<span class="help-block" id="REFERENTE_err"></span>
	                </div>
	              </div>
              </div>
              
              <div class="row"> 
	              <div class="form-group col-xs-12" id="AZIENDA_lbl">
	                <label for="AZIENDA">Azienda *</label>
	                <div>
	                	<input type="text" id="AZIENDA" class="form-control input-lg" name="AZIENDA" size="100" maxlength="100">
	                	<span class="help-block" id="AZIENDA_err"></span>
	                </div>
	              </div>
              </div>
              
              <div class="row"> 
	              <div class="form-group col-xs-12" id="NUMCART_lbl">
	                <label for="NUMCART">Cartellino nr. *</label>
	                <div>
	                	<input type="text" id="NUMCART" class="form-control input-lg" name="NUMCART" size="100" maxlength="100">
	                	<span class="help-block" id="NUMCART_err"></span>
	                </div>
	              </div>
              </div>
              
              <div class="row"> 
	              <div class="form-group col-xs-12">
	                <label>&nbsp;</label>
	              </div>
              </div>
              
              <div class="row"> 
	              <div class="form-group col-xs-12">
	                <label>* Campo obbligatorio</label>
	              </div>
              </div>
              
            <div id="navbottom">
              <input type="button" onclick="sbm_add()" class="btn btn-lg btn-primary accept" value="Invia" /> 
            </div>
              
        </div>
      </div>
      
	  <div id="listReferenti" style="display:none;position:absolute;height:100%;width:100%;background-color:white;top:1200px">
			<a id="closeListReferentiButton" class="glyphicon glyphicon-arrow-left" href="javascript:void('0');" onclick="closeListReferenti();" title="Torna alla lista"></a>
			<br>
			<br>
			<form>
				<div class="row"> 
				  <div class="form-group col-xs-12"> 
				    <input type="text" id="fltREFERENTE" class="form-control input-lg" placeholder="Cerca.." name="fltREFERENTE" size="50" maxlength="50">
				  </div>
				</div>
			</form>
			<br>
			<div id="divResultsReferenti">
			
SEGDTA;

				$this->lstReferenti('');
			
		echo <<<SEGDTA

			</div>
	  </div>
      
      
    </div>
    

    <script src="/crud/websmart/v13.2/js/jquery.form_2.64.js"></script>    
    <script src="/crud/websmart/v13.2/js/jquery.blockui.js"></script>     
    <script>
    jQuery(function() {
		$("#REFERENTE").focus(function(){
			$("#listReferenti").css("display","block");
			$("#listReferenti").animate({top: '0px'},500,function(e){
				$("#fltREFERENTE").focus();
			}); 
		});     
		 
		$("#fltREFERENTE").on("keyup",function(data){
			jfltREFERENTE = $(this).val();
			url = "?task=fltReferenti&fltREFERENTE="+encodeURIComponent(jfltREFERENTE);	
			$("#divResultsReferenti").load(url);
		}); 
		 
		var options = { 
			dataType:  'json',
			success: showResponseAdd 
		}; 
		$('#rcd-add-form').ajaxForm(options); 		
		
    });
    
	function sbm_add() {
		$.blockUI();
		$("#rcd-add-form").submit();
	}    
    
	function showResponseAdd(respobj, statusText, xhr, _form)  { 
		 
		$(".help-block").html("");
		$(".has-error").removeClass("has-error");
		 
		if(respobj[0].stat!="OK") { 
			$.unblockUI();
			for(ie=0;ie<respobj.length;ie++) { 
				$("#"+$.trim(respobj[ie].id)+"_lbl").addClass("has-error");
				$("#"+$.trim(respobj[ie].id)+"_err").html(respobj[ie].msg);
			}
		}	    
		else {		
			 document.location.href="?task=dspSicurezza";
		}  
	}     
    
    function closeListReferenti() {
		$("#listReferenti").css("display","none");
		$("#listReferenti").css("top","1200px");
		$("#AZIENDA").focus();
    }
    
    function setReferente(jCODREFE,jDESREFE) {
    	$("#CODREFE").val(jCODREFE);
    	$("#REFERENTE").val(jDESREFE);
    	closeListReferenti();
    }
    
    </script>
    
    
    
    
  </body>
</html>
SEGDTA;
		return;
	}
	if($xlSegmentToWrite == "dreferente")
	{

		echo <<<SEGDTA
	<div class="well">
		<div class="row"> 
			<div onclick="setReferente('$VRCOGE','$VRNOME $VRCOGN');">
				<div class="referenteLetter" style="background-color:$color">
SEGDTA;
 echo substr($VRNOME,0,1); 
		echo <<<SEGDTA
</div>
				<div class="referenteName">$VRNOME $VRCOGN</div>
			</div> 
		</div>
	</div>
SEGDTA;
		return;
	}
	if($xlSegmentToWrite == "sicurezza")
	{

		echo <<<SEGDTA
<!DOCTYPE html>
<html>
  <head>
    <meta name="generator" content="WebSmart" />
    <meta charset="UTF-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Pragma" content="no-cache" />
    <title>Visitatori</title>
    
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
	.aligner {
		display: flex;
		justify-content: center;		
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
			<a id="backButton" class="glyphicon glyphicon-arrow-left" href="?task=default" title="Torna alla lista"></a>
			
			<h1>DOCUMENTI</h1>
			
			<embed src="visitatori/files/opuscolo-emergenze.pdf#toolbar=0&navpanes=0&scrollbar=0" width="100%" height="700" />	
			 
			<form>
				<div class="form-group" id="checkbox-accept_lbl"> 
					<div class="aligner"> 
						<div class="checkbox">
							<label>
							  <input type="checkbox" id="checkbox-accept" class="checkbox style-0">
							  <span>Ho letto ed accetto norme sicurezza</span>
							</label>
							<span class="help-block" id="checkbox-accept_err"></span>
						</div>
					</div>
			 	</div>
			 	
				<div id="navbottom">
					<input type="button" onclick="sbm_add()" class="btn btn-lg btn-primary accept" value="Continua" /> 
				</div>
			</form>
  			
        </div>
      
    </div>
     
    <script>
    
    function sbm_add() {
    	if(!$("#checkbox-accept").is(":checked")) {
    		$("#checkbox-accept_lbl").addClass("has-error");
    		$("#checkbox-accept_err").html("Campo obbligatorio");
    		return false;
    	}
    	
    	document.location.href = "?task=dspPrivacy";
    }
    
    </script> 
     
  </body>
</html>
SEGDTA;
		return;
	}
	if($xlSegmentToWrite == "privacy")
	{

		echo <<<SEGDTA
<!DOCTYPE html>
<html>
  <head>
    <meta name="generator" content="WebSmart" />
    <meta charset="UTF-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Pragma" content="no-cache" />
    <title>Visitatori</title>
    
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
	.aligner {
		display: flex;
		justify-content: center;		
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
			<a id="backButton" class="glyphicon glyphicon-arrow-left" href="?task=default" title="Torna alla lista"></a>

			<h1>DOCUMENTI</h1>
			
			<embed src="visitatori/files/privacy.pdf#toolbar=0&navpanes=0&scrollbar=0" width="100%" height="700" />	
			 
			<form>
				<div class="form-group" id="checkbox-accept_lbl"> 
					<div class="aligner"> 
						<div class="checkbox">
							<label>
							  <input type="checkbox" id="checkbox-accept" class="checkbox style-0">
							  <span>Ho letto ed accetto condizioni e termini privacy</span>
							</label>
							<span class="help-block" id="checkbox-accept_err"></span>
						</div>
					</div>
			 	</div>
			 	
				<div id="navbottom">
					<input type="button" onclick="sbm_add()" class="btn btn-lg btn-primary accept" value="Continua" /> 
				</div>
			</form>
  			
        </div>
      
    </div>
     
    <script>
    
    function sbm_add() {
    	if(!$("#checkbox-accept").is(":checked")) {
    		$("#checkbox-accept_lbl").addClass("has-error");
    		$("#checkbox-accept_err").html("Campo obbligatorio");
    		return false;
    	}
    	
    	document.location.href = "?task=beginAddFirmaIngresso";
    }
    
    </script> 
     
  </body>
</html>
SEGDTA;
		return;
	}
	if($xlSegmentToWrite == "firmaaddingresso")
	{

		echo <<<SEGDTA
<!DOCTYPE html>
<html>
  <head>
    <meta name="generator" content="WebSmart" />
    <meta charset="UTF-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Pragma" content="no-cache" />
    <title>Visitatori</title>
    
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
	.aligner {
		display: flex;
		justify-content: center;		
	}
	
	.wrapper {
	  position: relative;
	  width: 100%;
	  height: 305px;
	  -moz-user-select: none;
	  -webkit-user-select: none;
	  -ms-user-select: none;
	  user-select: none;
	  border: 2px solid black;
	}
	
	.signature-pad {
	  position: absolute;
	  left: 0;
	  top: 0;
	  width:100%;
	  height:300px;
	  background-color: white;
	}	
	
	
    </style>
 
    <script src="websmart/v13.2/js/jquery.min.js"></script>
    <script src="websmart/v13.2/Responsive/js/bootstrap.min.js"></script> 
    <script src="websmart/v13.2/js/signature_pad/signature_pad.js"></script> 
  </head>
  <body>
    <div id="outer-content" style="position:relative"> 
      <div id="page-content">
         
        <div class="clearfix"></div>
        <div id="contents"> 
			<a id="backButton" class="glyphicon glyphicon-arrow-left" href="?task=default" title="Torna alla lista"></a>
			
			<h1>FIRMA</h1>

			<form id="rcd-add-form" action="$pf_scriptname" method="POST"> 
				<input type="hidden" name="task" id="task" value="endAddFirmaIngresso" />
				<input type="hidden" name="signCode" id="signCode" value="" />
				
				<div class="form-group col-xs-12" id="firma_lbl">
					<div class="wrapper">
					  <canvas id="signature-pad" class="signature-pad" width="100%" height=300></canvas>
					</div>
					<span class="help-block" id="firma_err"></span>
					<input type="button" id="clear" value="Pulisci" />
				</div>	
				
				<div id="navbottom">
					<input type="button" onclick="sbm_add()" class="btn btn-lg btn-primary accept" value="Continua" /> 
				</div>
			</form>
        </div>
      
    </div>
    
    <script src="/crud/websmart/v13.2/js/jquery.form_2.64.js"></script>    
    <script src="/crud/websmart/v13.2/js/jquery.blockui.js"></script>         
    <script>
      
    jQuery(function() { 
		var options = { 
			dataType:  'json',
			success: showResponseAdd 
		}; 
		$('#rcd-add-form').ajaxForm(options); 		
		
    });
      
	var canvas = document.getElementById('signature-pad');
	 
	function resizeCanvas() { 
	    var ratio =  Math.max(window.devicePixelRatio || 1, 1);
	    canvas.width = canvas.offsetWidth * ratio;
	    canvas.height = canvas.offsetHeight * ratio;
	    canvas.getContext("2d").scale(ratio, ratio);
	}
	
	window.onresize = resizeCanvas;
	resizeCanvas();
	
	var signaturePad = new SignaturePad(canvas, {
	  	backgroundColor: 'rgb(255, 255, 255)' // necessary for saving image as JPEG; can be removed is only saving as PNG or SVG
	});
	 
	document.getElementById('clear').addEventListener('click', function () {
	  	signaturePad.clear();
	});
      
	function sbm_add() {
		if(signaturePad.isEmpty()) {
			$("#firma_lbl").addClass("has-error");
			$("#firma_err").html("Campo obbligatorio");
			return false;
		}
		
		var jsignCode = signaturePad.toDataURL();
		$("#signCode").val(jsignCode); 		
		
		$.blockUI(); 
		$("#rcd-add-form").submit();
		document.location.href = "?task=dspIngressoConferma";

		 
	}
	    
	function showResponseAdd(respobj, statusText, xhr, jform)  { 
		 
		$("#firma_err").html(""); 
		if(respobj[0].stat!="OK") {
			$.unblockUI(); 
    		$("#firma_err").html(respobj[0].msg);
		}	    
		else {	
			document.location.href = "?task=dspIngressoConferma";
		}  
	} 	    
	     
	function isCanvasTransparent() {
		// true if all pixels Alpha equals to zero
		var ctx = canvas.getContext("2d");
		var result;
		var imageData = ctx.getImageData(0, 0, canvas.offsetWidth, canvas.offsetHeight);
		for (var i = 0; i < imageData.data.length; i += 4) {
			if (imageData.data[i + 3] !== 0) return false;
		}
		return true;
	}	    
    
    </script> 
     
  </body>
</html>
SEGDTA;
		return;
	}
	if($xlSegmentToWrite == "ingressoconferma")
	{

		echo <<<SEGDTA
<!DOCTYPE html>
<html>
  <head>
    <meta name="generator" content="WebSmart" />
    <meta charset="UTF-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Pragma" content="no-cache" />
    <title>Visitatori</title>
    
    <link rel="stylesheet" href="websmart/v13.2/Responsive/css/bootstrap.min.css" type="text/css" />
    <link rel="stylesheet" href="websmart/v13.2/Responsive/css/screen.css" media="all" type="text/css" />
    <link rel="stylesheet" href="websmart/v13.2/Responsive/css/jquery-ui.css" media="all" type="text/css" />
    
    <style>
    h1 {
    	font-size: 42px;	
    }
    .btnAzione {
		padding: 17px 23px;
		font-size: 25px;
		line-height: 1.3333333;
		border-radius: 13px;	
    }
    #btnAzioneIngresso {
    	position:absolute;
    	left:50px;
    	bottom:50px;	
    }
    #btnAzioneUscita {
    	position:absolute;
    	right:50px;
    	bottom:50px;
    } 
    </style>
 
    <script src="websmart/v13.2/js/jquery.min.js"></script>
    <script src="websmart/v13.2/Responsive/js/bootstrap.min.js"></script>
  </head>
  <body>
    <div id="outer-content"> 
      <div id="page-content">
        <div id="content-header">
          <h1 class="title"></h1>
        </div>
        <div class="clearfix"></div>
        <div id="contents"> 
          <h1 class="text-center">INGRESSO REGISTRATO CON SUCCESSO</h1>
        
          <a id="btnAzioneIngresso" type="button" class="btn btn-success btnAzione" href="?task=default">CHIUDI</a>
          
        </div>
      </div> 
     
  </body>
</html>
SEGDTA;
		return;
	}
	if($xlSegmentToWrite == "uscitaadd")
	{

		echo <<<SEGDTA
<!DOCTYPE html>
<html>
  <head>
    <meta name="generator" content="WebSmart" />
    <meta charset="UTF-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Pragma" content="no-cache" />
    <title>Visitatori</title>
    
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
	
    </style> 
    <script src="websmart/v13.2/js/jquery.min.js"></script>
    <script src="websmart/v13.2/Responsive/js/bootstrap.min.js"></script>
  </head>
  <body>
    <div id="outer-content" style="position:relative"> 
      <div id="page-content">
         
        <div class="clearfix"></div>
        <div id="contents"> 
	      <a id="backButton" class="glyphicon glyphicon-arrow-left" href="?task=default" title="Torna alla lista"></a>
 
 		  <h4>Per uscire, inserire nel campo sottostante nome e/o cognome (almeno 3 caratteri) e premere cerca</h4>
 
          <form id="filter-form" action="$pf_scriptname" method="post">
            <input type="hidden" name="task" value="filterUscita" />
 			  
              <div class="row"> 
	              <div class="form-group col-xs-12" id="fltUSCITA_lbl">
	                <label for="fltUSCITA"> </label>
 	                <div>
 	                	<input type="text" id="fltUSCITA" class="form-control input-lg" name="fltUSCITA" size="50" maxlength="50">
	                	<span class="help-block" id="fltUSCITA_err"></span>
	                </div>
 	              </div>
              </div>
			      
              <div id="navbottom">
               <input type="button" onclick="sbm_filter();" class="btn btn-lg btn-primary accept" value="Cerca" /> 
              </div>
              <br><br>
              <div id="divResultsUscite">
              
SEGDTA;

              	$this->filterUscita();
              
		echo <<<SEGDTA

              </div>
              
        </div>
      </div>
       
    </div>
    

    <script src="/crud/websmart/v13.2/js/jquery.form_2.64.js"></script>    
    <script src="/crud/websmart/v13.2/js/jquery.blockui.js"></script>     
    <script> 
   jQuery(function() {
		 
		var options = { 
			dataType:  'HTML',
			success: showResponseSrc 
		}; 
		$('#filter-form').ajaxForm(options); 		
		
    });
    
	function sbm_filter() {
		$.blockUI();
		$("#filter-form").submit();
	}    
    
	function showResponseSrc(resptxt, statusText, xhr, _form)  { 
		$.unblockUI();
		$("#divResultsUscite").html(resptxt);
	}   
    
    function beginAddFirmaUscita(jVRPROG) {
    	if(!confirm("Confermi l'uscita?")) return false;
    	document.location.href = '?task=beginAddFirmaUscita&VRPROG='+jVRPROG;
    }
    
    </script>
    
    
    
    
  </body>
</html>
SEGDTA;
		return;
	}
	if($xlSegmentToWrite == "huscita")
	{

		echo <<<SEGDTA
<div class="table-responsive">
<table class="table table-striped table-bordered">
<thead>
	<tr>
		<td></td>
		<td>Nome</td>
		<td>Cognome</td>
		<td>Data ingresso</td>
		<td>Ora ingresso</td>
	</tr>
</thead>
<tbody>
SEGDTA;
		return;
	}
	if($xlSegmentToWrite == "duscita")
	{

		echo <<<SEGDTA
<tr>
	<td>
		<a class="btn btn-success btn-lg glyphicon glyphicon-check" href="javascript:void('0');" onclick="beginAddFirmaUscita('$VRPROG');" title="Conferma uscita"></a>
	</td>
	<td>$VRNOME_O</td>
	<td>$VRCOGN_O</td>
	<td>
SEGDTA;
 echo $this->cvtDateFromDb($VRDTIN); 
		echo <<<SEGDTA
</td>
	<td>
SEGDTA;
 echo $this->cvtTimeFromDb($VRORIN); 
		echo <<<SEGDTA
</td>
</tr>
SEGDTA;
		return;
	}
	if($xlSegmentToWrite == "fuscita")
	{

		echo <<<SEGDTA
</tbody>
</table>
</div>
SEGDTA;
		return;
	}
	if($xlSegmentToWrite == "nessunauscita")
	{

		echo <<<SEGDTA
<h4 class="text-center">Nessun ingresso trovato</h4>
SEGDTA;
		return;
	}
	if($xlSegmentToWrite == "firmaadduscita")
	{

		echo <<<SEGDTA
<!DOCTYPE html>
<html>
  <head>
    <meta name="generator" content="WebSmart" />
    <meta charset="UTF-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Pragma" content="no-cache" />
    <title>Visitatori</title>
    
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
	.aligner {
		display: flex;
		justify-content: center;		
	}
	
	.wrapper {
	  position: relative;
	  width: 100%;
	  height: 305px;
	  -moz-user-select: none;
	  -webkit-user-select: none;
	  -ms-user-select: none;
	  user-select: none;
	  border: 2px solid black;
	}
	
	.signature-pad {
	  position: absolute;
	  left: 0;
	  top: 0;
	  width:100%;
	  height:300px;
	  background-color: white;
	}	
	
	
    </style>
 
    <script src="websmart/v13.2/js/jquery.min.js"></script>
    <script src="websmart/v13.2/Responsive/js/bootstrap.min.js"></script> 
    <script src="websmart/v13.2/js/signature_pad/signature_pad.js"></script> 
  </head>
  <body>
    <div id="outer-content" style="position:relative"> 
      <div id="page-content">
         
        <div class="clearfix"></div>
        <div id="contents"> 
			<a id="backButton" class="glyphicon glyphicon-arrow-left" href="?task=default" title="Torna alla lista"></a>
			
			<h1>FIRMA</h1>

			<form id="rcd-add-form" action="$pf_scriptname" method="POST"> 
				<input type="hidden" name="task" id="task" value="endAddFirmaUscita" />
				<input type="hidden" name="VRPROG" id="VRPROG" value="$VRPROG" />
				<input type="hidden" name="signCode" id="signCode" value="" />
				
				<div class="form-group col-xs-12" id="firma_lbl">
					<div class="wrapper">
					  <canvas id="signature-pad" class="signature-pad" width="100%" height=300></canvas>
					</div>
					<span class="help-block" id="firma_err"></span>
					<input type="button" id="clear" value="Pulisci" />
				</div>	
				
				<div id="navbottom">
					<input type="button" onclick="sbm_add()" class="btn btn-lg btn-primary accept" value="Continua" /> 
				</div>
			</form>
        </div>
      
    </div>
    
    <script src="/crud/websmart/v13.2/js/jquery.form_2.64.js"></script>    
    <script src="/crud/websmart/v13.2/js/jquery.blockui.js"></script>         
    <script>
      
    jQuery(function() { 
		var options = { 
			dataType:  'json',
			success: showResponseAdd 
		}; 
		$('#rcd-add-form').ajaxForm(options); 		
		
    });
      
	var canvas = document.getElementById('signature-pad');
	 
	function resizeCanvas() { 
	    var ratio =  Math.max(window.devicePixelRatio || 1, 1);
	    canvas.width = canvas.offsetWidth * ratio;
	    canvas.height = canvas.offsetHeight * ratio;
	    canvas.getContext("2d").scale(ratio, ratio);
	}
	
	window.onresize = resizeCanvas;
	resizeCanvas();
	
	var signaturePad = new SignaturePad(canvas, {
	  	backgroundColor: 'rgb(255, 255, 255)' // necessary for saving image as JPEG; can be removed is only saving as PNG or SVG
	});
	 
	document.getElementById('clear').addEventListener('click', function () {
	  	signaturePad.clear();
	});
      
	function sbm_add() {
		if(signaturePad.isEmpty()) {
			$("#firma_lbl").addClass("has-error");
			$("#firma_err").html("Campo obbligatorio");
			return false;
		}
		
		var jsignCode = signaturePad.toDataURL();
		$("#signCode").val(jsignCode); 		
		
		$.blockUI(); 
		$("#rcd-add-form").submit();
		 			document.location.href = "?task=dspUscitaConferma";

	}
	    
	function showResponseAdd(respobj, statusText, xhr, jform)  { 
		 
		$("#firma_err").html(""); 
		if(respobj[0].stat!="OK") {
			$.unblockUI(); 
    		$("#firma_err").html(respobj[0].msg);
		}	    
		else {	
			document.location.href = "?task=dspUscitaConferma";
		}  
	} 	    
	     
	function isCanvasTransparent() {
		// true if all pixels Alpha equals to zero
		var ctx = canvas.getContext("2d");
		var result;
		var imageData = ctx.getImageData(0, 0, canvas.offsetWidth, canvas.offsetHeight);
		for (var i = 0; i < imageData.data.length; i += 4) {
			if (imageData.data[i + 3] !== 0) return false;
		}
		return true;
	}	    
    
    </script> 
     
  </body>
</html>
SEGDTA;
		return;
	}
	if($xlSegmentToWrite == "uscitaconferma")
	{

		echo <<<SEGDTA
<!DOCTYPE html>
<html>
  <head>
    <meta name="generator" content="WebSmart" />
    <meta charset="UTF-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Pragma" content="no-cache" />
    <title>Visitatori</title>
    
    <link rel="stylesheet" href="websmart/v13.2/Responsive/css/bootstrap.min.css" type="text/css" />
    <link rel="stylesheet" href="websmart/v13.2/Responsive/css/screen.css" media="all" type="text/css" />
    <link rel="stylesheet" href="websmart/v13.2/Responsive/css/jquery-ui.css" media="all" type="text/css" />
    
    <style>
    h1 {
    	font-size: 42px;	
    }
    .btnAzione {
		padding: 17px 23px;
		font-size: 25px;
		line-height: 1.3333333;
		border-radius: 13px;	
    }
    #btnAzioneIngresso {
    	position:absolute;
    	left:50px;
    	bottom:50px;	
    }
    #btnAzioneUscita {
    	position:absolute;
    	right:50px;
    	bottom:50px;
    } 
    </style>
 
    <script src="websmart/v13.2/js/jquery.min.js"></script>
    <script src="websmart/v13.2/Responsive/js/bootstrap.min.js"></script>
  </head>
  <body>
    <div id="outer-content"> 
      <div id="page-content">
        <div id="content-header">
          <h1 class="title"></h1>
        </div>
        <div class="clearfix"></div>
        <div id="contents"> 
          <h1 class="text-center">USCITA REGISTRATA CON SUCCESSO</h1>
        
          <a id="btnAzioneIngresso" type="button" class="btn btn-success btnAzione" href="?task=default">CHIUDI</a>
          
        </div>
      </div> 
     
  </body>
</html>
SEGDTA;
		return;
	}
	if($xlSegmentToWrite == "hpresenti")
	{

		echo <<<SEGDTA
<!DOCTYPE html>
<html>
  <head>
    <meta name="generator" content="WebSmart" />
    <meta charset="UTF-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Pragma" content="no-cache" />
    <title>Visitatori</title>
    
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
	      <a id="backButton" class="glyphicon glyphicon-arrow-left" href="?task=default" title="Torna alla lista"></a>
 
 		  <h1>LISTA PRESENTI</h1>
  
              
              	<input id="printButton" type="button" onclick="window.print();" class="btn btn-lg btn-primary accept" value="Stampa" /> 
              	<br><br>
              	<div id="divTotPresenti">Totale presenti: <span id="totPresenti"></span></div>
              	<br><br>
              	<div class="table-responsive">
				<table class="table table-striped table-bordered">
				<thead>
					<tr> 
						<td>Nome</td>
						<td>Cognome</td>
						<td>Data ingresso</td>
						<td>Ora ingresso</td>
					</tr>
				</thead>
				<tbody>
SEGDTA;
		return;
	}
	if($xlSegmentToWrite == "tpresenti")
	{

		echo <<<SEGDTA
<tr style="background-color:#92a2a8;color:white;">
 	<td colspan="4"><strong>$tipoPresenti</strong></td> 
</tr>
 
SEGDTA;
		return;
	}
	if($xlSegmentToWrite == "dpresenti")
	{

		echo <<<SEGDTA
<tr>
 
	<td>$presNome</td>
	<td>$presCogn</td>
	<td>$presDtIn</td>
	<td>$presOrIn</td>
</tr>
 
SEGDTA;
		return;
	}
	if($xlSegmentToWrite == "fpresenti")
	{

		echo <<<SEGDTA
 		</tbody>
 		</table>
 		</div>
      </div>
       
    </div>
    

    <script src="/crud/websmart/v13.2/js/jquery.form_2.64.js"></script>    
    <script src="/crud/websmart/v13.2/js/jquery.blockui.js"></script>     
    <script> 
   
    $("#totPresenti").html("$totPresenti");
   
    </script>
    
    
    
    
  </body>
</html>
SEGDTA;
		return;
	}
	if($xlSegmentToWrite == "nessunpresente")
	{

		echo <<<SEGDTA
<h4 class="text-center">Nessun presente trovato</h4>
SEGDTA;
		return;
	}

		// If we reach here, the segment is not found
		echo("Segment $xlSegmentToWrite is not defined! ");
	}

	// return a segment's content instead of outputting it to the browser
	function getSegment($xlSegmentToWrite, $segmentVars=array())
	{
		ob_start();
		
		$this->writeSegment($xlSegmentToWrite, $segmentVars);
		
		return ob_get_clean();
	}
	
	function __construct()
	{
		
		$this->pf_liblLibs[1] = 'BCD_DATIV2';
		
		parent::__construct();

		$this->pf_scriptname = 'VISITATORI.php';
		$this->pf_wcm_set = 'PRODUZIONE';
		
		
		$this->xl_set_env($this->pf_wcm_set);
		
		// Last Generated CRC: 7DF46874 D9DD30CE ED9B6F99 7B384F1B
		// Last Generated Date: 2024-06-18 17:52:53
		// Path: visitatori.phw
	}
}

// Auto-load this WebSmart object (by calling xlLoadWebSmartObject) if this script is called directly (not via an include/require).
// Comment this line out if you do not wish this object to be invoked directly.
xlLoadWebSmartObject(__FILE__, 'VISITATORI');?>