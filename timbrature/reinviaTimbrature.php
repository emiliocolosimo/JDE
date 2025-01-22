<?php 
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set("log_errors", 1);
ini_set("error_log", "/www/php80/htdocs/timbrature/logs/transfer_all_".date("Ym").".log");
require_once("/www/php80/htdocs/timbrature/config.inc.php");


$server="Driver={IBM i Access ODBC Driver};System=127.0.0.1;Uid=".DB2_USER.";Pwd=".DB2_PASS.";TRANSLATE=1;"; 
$user=DB2_USER; 
$pass=DB2_PASS;   
$conn=odbc_connect($server,$user,$pass); 
if(!$conn) {
	$errMsg = "Errore connessione al database : ".odbc_errormsg($conn);
	error_log($errMsg);
	exit;
}

error_log("Inizio elaborazione");
 
$fileContent = "";
$cntTimbrature = 0;

$query = "SELECT TRIM(TMTIPO) AS TMTIPO, TMDTIN, DIGITS(TMORIN) AS TMORIN, TMDAFI, DIGITS(TMORFI) AS TMORFI, TRIM(TMDCDI) AS TMDCDI
FROM BCD_DATIV2.RQTIMB00F 
WHERE TMDTIN >= 20240601 
ORDER BY TMDTIN, TMDCDI, TMID, TMTIPO 
";
$res = odbc_exec($conn,$query);
if($res) {
	while($row = odbc_fetch_array($res)) {
 	 
		$TMTIPO = $row["TMTIPO"];
		$TMDTIN = $row["TMDTIN"];
		$TMORIN = $row["TMORIN"];
		$TMDAFI = $row["TMDAFI"];
		$TMORFI = $row["TMORFI"];
		$TMDCDI = $row["TMDCDI"];
		
		//entrata = 0, uscita = 1
		if($TMTIPO=="E") {
			$gepTipo = '0'; 
			$gepAnno = substr($TMDTIN,0,4);
			$gepMese = substr($TMDTIN,4,2);
			$gepGiorno = substr($TMDTIN,6,2);			
			$gepOra = substr($TMORIN,0,2);
			$gepMinuti = substr($TMORIN,2,2);
		}
		if($TMTIPO=="U") {
			$gepTipo = '1';  
			$gepAnno = substr($TMDAFI,0,4);
			$gepMese = substr($TMDAFI,4,2);
			$gepGiorno = substr($TMDAFI,6,2);			
			$gepOra = substr($TMORFI,0,2);
			$gepMinuti = substr($TMORFI,2,2); //qui c'era un errore
		}
		$gepSecondi = '00'; 
		$gepBadge = $TMDCDI; //codice gestionale
		
		if(!is_numeric($gepOra)) $gepOra = '00';
		if(!is_numeric($gepMinuti)) $gepMinuti = '00';
		  	
		if($cntTimbrature>0) $fileContent.="\n";
		 
		$fileContent.= $gepTipo.";".$gepGiorno.";".$gepMese.";".$gepAnno.";".$gepOra.";".$gepMinuti.";".$gepBadge;
		
		$cntTimbrature++;
		 
	}
}
 
$query = "SELECT 
RRN(F) AS RRN,
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
FROM BCD_DATIV2.SAVTIM0F AS F 
ORDER BY STDATE, STCDDI, STTIME  
";
$res = odbc_exec($conn,$query);
if($res) {
	while($row = odbc_fetch_array($res)) {
		
		//cancello timbrature entro 10 minuti di differenza
		/*
		$tims = date("H:i:s",strtotime($STTIME.' - 10 minutes'));
		$timf = date("H:i:s",strtotime($STTIME.' + 10 minutes'));
		$query = "DELETE FROM BCD_DATIV2.SAVTIM0F AS F 
		WHERE STYEAR = '".$row["STYEAR"]."' 
		AND STMONT = '".$row["STMONT"]."' 
		AND STDAY = '".$row["STDAY"]."' 
		AND STCDDI = '".$row["STCDDI"]."' 
		AND STSENS = '".$row["STSENS"]."' 
		AND (STTIME > '".$tims."' OR STTIME < '".$timf."' )
		AND RRN(F) <> '".$row["RRN"]."' 
		WITH NC 
		";
		echo $query;
		*/
		//$res_del = odbc_exec($conn,$query);
		
		//aggiorno record come trasferito:
		$RRNToUpdate[] = $row["RRN"];

		//compongo tracciato:
		//entrata = 0, uscita = 1
		if($row["STSENS"]=="E") $gepTipo = '0'; 
		if($row["STSENS"]=="U") $gepTipo = '1'; 
			
		$gepAnno = "20".$row["STYEAR"];
		$gepMese = $row["STMONT"];
		$gepGiorno = $row["STDAY"];	
		$gepOra = $row["STHOUR"];	
		$gepMinuti = $row["STMINU"];	 
		$gepSecondi = '00'; 
		$gepBadge = $row["STCDDI"]; //codice gestionale
		
		if(!is_numeric($gepOra)) $gepOra = '00';
		if(!is_numeric($gepMinuti)) $gepMinuti = '00';
		  	
		if($cntTimbrature>0) $fileContent.="\n";
		 
		$fileContent.= $gepTipo.";".$gepGiorno.";".$gepMese.";".$gepAnno.";".$gepOra.";".$gepMinuti.";".$gepBadge;
		
		$cntTimbrature++;	 
	}
} else {
	$errMsg = "Errore lettura timbrature: ".odbc_errormsg();
	error_log($errMsg);
	exit;
} 
 
 
$filePath = "/www/php80/htdocs/timbrature/temp/transfer_all.txt";
file_put_contents($filePath,$fileContent); 
 
error_log(" - trovate ".$cntTimbrature." timbrature");
 

if($cntTimbrature>0) {

	error_log(" - richiamo servizio gepacon");

	$filePath = "/www/php80/htdocs/timbrature/temp/transfer_all.txt";
	file_put_contents($filePath,$fileContent);
 

	//invio file:
	$url = "https://gepacon.gishrm.online/server/ws/presenzeUpFile.php";
	//$url = "https://app.bigblue.it/wsphp/presenzeUpFile.php"; 
	$curl_handle = curl_init();
	curl_setopt($curl_handle, CURLOPT_URL, $url);
	curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true); 
	curl_setopt($curl_handle, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($curl_handle, CURLOPT_POST, 1);

	$fields = [
		'ffile' => new \CurlFile($filePath, 'text/plain', '/transfer_all.txt')
	];
	 
	curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $fields);
	curl_setopt($curl_handle, CURLOPT_HTTPHEADER, [
		'Authorization: Bearer '.GEPACON_API_KEY,
		'Accept: application/json'
	]);
	
	curl_setopt($curl_handle, CURLOPT_VERBOSE, true);
	$streamVerboseHandle = fopen('php://temp', 'w+');
	curl_setopt($curl_handle, CURLOPT_STDERR, $streamVerboseHandle);


	$curl_data = curl_exec($curl_handle);
	
	//var_dump($curl_data);	
	 
	if(!$curl_data) {
		$errMsg = "Errore chiamata cURL gepacon:0: ".curl_error($curl_handle);
		error_log($errMsg);
		
		rewind($streamVerboseHandle);
		$verboseLog = stream_get_contents($streamVerboseHandle); 
		error_log($verboseLog);
		
		exit;
	}
	curl_close($curl_handle);

	error_log("curl data: ".$curl_data);

	$response_data = json_decode($curl_data,true); 
	if(!isset($response_data["success"])) {  
		$errMsg = "Errore dati di ritorno gepacon:1: ".$curl_data;
		error_log($errMsg);
		exit;
	} 

	if(!$response_data["success"]) {  
		$errMsg = "Errore dati di ritorno gepacon:2: ".$curl_data."->".$response_data["errorMessage"];
		error_log($errMsg);
		exit;
	} 

	 
}  

//....
error_log(" - fine procedura");
