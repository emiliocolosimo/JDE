<?php
/*
	- Keep-alive transmission
	device send periodically request to server with 'N' in his body.
	Server response with status 202 (empty body) or put command to device in body (see next section).
	'N' periodic time depends from par #0065 of ltcom.cfg (dec/sec.)
	
	- Upload transit from device to host
	device send 'M' + transit record if it has in memory some transit to upload.
	If server response with status = 200 (empty body) than device will send next transit but if status is
	different than 200 device will send same transit again.
	
	- Transaction authorization
	device send 'V' + transit record to authorized; When server response status is 202 transit will be
	authorized but if status is 203 transit will be non authorized. If there is some message in body
	response it will be showed on device display. Set parameter #0060 to 2 .
*/


ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set("log_errors", 1); 
ini_set("error_log", "/www/php80/htdocs/timbrature/logs/timbraSave_".date("Ym")."_".date("His")."_".rand(0,9999).".txt");
require_once("/www/php80/htdocs/timbrature/config.inc.php");

$server="Driver={IBM i Access ODBC Driver};System=127.0.0.1;Uid=".DB2_USER.";Pwd=".DB2_PASS.";TRANSLATE=1;"; 
$user=DB2_USER; 
$pass=DB2_PASS;   	
 
$entityBody = file_get_contents('php://input');


$requestType = substr($entityBody,0,1);

if($requestType=="N") {
	http_response_code(202);
	exit;
}
if($requestType=="V") {
 
	//error_log("Ricevuto: ".$entityBody);
 
	$transitString = substr($entityBody,1);
	$transitRecord = getTransitRecord($transitString);

	$conn=odbc_connect($server,$user,$pass); 
	if(!$conn) {
		$errMsg = "Errore connessione al database : ".odbc_errormsg($conn);
		error_log($errMsg);
		return false;
	}		
	 
	//controllo badge:
	$query = "SELECT BDNOME, BDCOGN, BDCOGE   
	FROM BCD_DATIV2.BDGDIP0F 
	WHERE BDBADG = HEX(".$transitRecord["Badge"].") 
	OR BDBDTM = HEX(".$transitRecord["Badge"].") 
	";
	$res = odbc_exec($conn,$query);
	if(!$res) {
		$errMsg = "Errore query estrazione nome e cognome: ".odbc_errormsg();
		error_log($errMsg);
		return false; 
	}	
	$row = odbc_fetch_array($res);
	if($row) {
		echo trim($row["BDNOME"])." ".trim($row["BDCOGN"]);
		http_response_code(202);
	} else {
		echo "Badge non trovato";
		http_response_code(203);
	} 
	exit;
}
if($requestType=="M") {
	
	//error_log("Ricevuto: ".$entityBody);
	
	$transitString = substr($entityBody,1);
	$transitRecord = getTransitRecord($transitString);

	$conn=odbc_connect($server,$user,$pass); 
	if(!$conn) {
		$errMsg = "Errore connessione al database : ".odbc_errormsg($conn);
		error_log($errMsg);
		return false;
	}		
	 
	//controllo badge:
	$query = "SELECT BDNOME, BDCOGN, BDCOGE   
	FROM BCD_DATIV2.BDGDIP0F 
	WHERE BDBADG = HEX(".$transitRecord["Badge"].") 
	OR BDBDTM = HEX(".$transitRecord["Badge"].") 
	";
	$res = odbc_exec($conn,$query);
	if(!$res) { 
		$errMsg = "Errore query estrazione nome e cognome: ".odbc_errormsg();
		error_log($errMsg);
		return false; 
	}	
	$row = odbc_fetch_array($res);
	if($row) { 
		$res = saveTransitRecord($conn,$requestType,$row["BDCOGE"],$transitRecord); 
		http_response_code(200);
	}  
	
	exit;
}
if($requestType=="I") {
	http_response_code(200);
	exit;
}

function getTransitRecord($transitString) {
	//	     	1         2         3     
	//012345678901234567890123456789012345678
	//E15001265741482426368000010120111062401
	
	$tr = array();
	$tr["Sense"] = substr($transitString,0,1);
	$tr["Type"] = substr($transitString,1,1);
	$tr["WDay"] = substr($transitString,2,1);
	$tr["Badge"] = substr($transitString,3,18);
	$tr["ReasonCode"] = substr($transitString,21,4);
	$tr["Hour"] = substr($transitString,25,2);
	$tr["Minute"] = substr($transitString,27,2);
	$tr["Second"] = substr($transitString,29,2);
	$tr["Day"] = substr($transitString,31,2);
	$tr["Month"] = substr($transitString,33,2);
	$tr["Year"] = substr($transitString,35,2);
	$tr["DeviceId"] = substr($transitString,37,2);
	
	return $tr;
}

function saveTransitRecord($conn,$requestType,$codDipendente,$transitRecord) {

	//salvo il record: 
	$STTPRE = $requestType;
	$STSENS = $transitRecord["Sense"];
	$STTYPE = $transitRecord["Type"];
	$STWDAY = $transitRecord["WDay"];
	$STBADG = $transitRecord["Badge"];
	$STCDDI = trim($codDipendente);
	$STRECO = $transitRecord["ReasonCode"];
	$STHOUR = $transitRecord["Hour"];
	$STMINU = $transitRecord["Minute"];
	$STSECO = $transitRecord["Second"];
	$STDAY  = $transitRecord["Day"];
	$STMONT = $transitRecord["Month"];
	$STYEAR = $transitRecord["Year"];
	$STDEID = $transitRecord["DeviceId"];
	
	$STDATE = "20".$STYEAR."-".$STMONT."-".$STDAY;
	$STTIME = $STHOUR.":".$STMINU.":".$STSECO;
	$STTIMS = $STDATE."-".$STHOUR.".".$STMINU.".".$STSECO.".000000";
	$STDTIN = date("Ymd");
	$STORIN = date("His");
	$STTRAS = '';
	
	//controllo che non ci sia altra timbratura entro i 10 minuti:	
	$skip = false;

	//$tims = date("H:i:s", strtotime($STTIME . ' - 1 second'));
	//a$timf = date("H:i:s", strtotime($STTIME . ' + 1 second'));

	$tims = date("H:i:s",strtotime($STTIME.' - 15 minutes'));
	$timf = date("H:i:s",strtotime($STTIME.' + 15 minutes'));
	$query = "SELECT 1 AS CHECKT 
	FROM BCD_DATIV2.SAVTIM0F AS F 
	WHERE STYEAR = '".$STYEAR."' 
	AND STMONT = '".$STMONT."' 
	AND STDAY = '".$STDAY."' 
	AND STCDDI = '".$STCDDI."' 
	AND STSENS = '".$STSENS."' 
	AND (STTIME > '".$tims."' AND STTIME < '".$timf."' ) 
	AND STRECO = '0000'
	FETCH FIRST ROW ONLY
	";
 echo $query;
 
	$res = odbc_exec($conn,$query);
	if($res) {
		$row = odbc_fetch_array($res);
		if(isset($row) && isset($row["CHECKT"]) && $row["CHECKT"]=='1') $skip = true;
	}
	
	if(!$skip) {
		$query = "
		INSERT INTO BCD_DATIV2.SAVTIM1F (
		STTPRE,
		STSENS,
		STTYPE,
		STWDAY,
		STBADG,
		STCDDI,
		STRECO,
		STHOUR,
		STMINU,
		STSECO,
		STDAY ,
		STMONT,
		STYEAR,
		STDEID,  
		STTIME,
		STDATE,
		STTIMS,
		STDTIN,
		STORIN,
		STTRAS 
		)
		VALUES (
		?,
		?,
		?,
		?,
		?,
		?,
		?,
		?,
		?,
		?,
		?,
		?,
		?,
		?,  
		?,
		?,
		?,
		?,  
		?,
		?	
		) WITH NC
		";
		$pstmt = odbc_prepare($conn,$query);
		if($pstmt) {
			$arrParams = array();	
			$arrParams[] = $STTPRE;
			$arrParams[] = $STSENS;
			$arrParams[] = $STTYPE;
			$arrParams[] = $STWDAY;
			$arrParams[] = $STBADG;
			$arrParams[] = $STCDDI;
			$arrParams[] = $STRECO;
			$arrParams[] = $STHOUR;
			$arrParams[] = $STMINU;
			$arrParams[] = $STSECO;
			$arrParams[] = $STDAY ;
			$arrParams[] = $STMONT;
			$arrParams[] = $STYEAR;
			$arrParams[] = $STDEID;
			$arrParams[] = $STTIME;
			$arrParams[] = $STDATE;
			$arrParams[] = $STTIMS;
			$arrParams[] = $STDTIN;
			$arrParams[] = $STORIN;
			$arrParams[] = $STTRAS;
			 
			$res = odbc_execute($pstmt,$arrParams);
			if(!$res) {
				$errMsg = "Errore query inserimento richiesta: ".odbc_errormsg();
				error_log($errMsg);
				return false;
			}		
			
		} else {
			$errMsg = "Errore prepare inserimento richiesta: ".odbc_errormsg();
			error_log($errMsg);
			return false;
		}
	} else {
		error_log("Timbratura saltata - trovata precedente entro 10 minuti");
	}
	
	return true;
	
}