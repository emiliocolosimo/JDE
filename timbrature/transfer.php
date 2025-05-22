<?php 
date_default_timezone_set("Europe/Rome");

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set("log_errors", 1);
ini_set("error_log", "/www/php80/htdocs/timbrature/logs/transferNew_".date("Ym").".log");
require_once("/www/php80/htdocs/timbrature/config.inc.php");

$temporaryFolder = "/www/php80/htdocs/timbrature/temp/".date("Ym")."/";
if(!file_exists($temporaryFolder)) mkdir($temporaryFolder);

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
 
$RRNToUpdate = array(); 
$fileContent = "";
$cntTimbrature = 0; 
 
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
WHERE STSENS IN('E','U') AND STTRAS = '' and STRECO='0000'
ORDER BY STTIMS 
";
$res = odbc_exec($conn,$query);
if($res) {
	while($row = odbc_fetch_array($res)) {
		 
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
 
error_log("Trovate ".$cntTimbrature." timbrature");

if($cntTimbrature>0) {
	//ricavo progressivo elaborazione:
	$query = "SELECT CNCONT FROM BCD_DATIV2.CRMCNT01L WHERE CNTIPO = 'PHPTIMBRAT' AND CNANNO = ".date("Y");
	$res = odbc_exec($conn,$query);
	if(!$res) {
		$errMsg = "Errore recupero progressivo elaborazione: ".odbc_errormsg($conn);
		error_log($errMsg);
		exit;
	}
	$row = odbc_fetch_array($res);
	if(!isset($row["CNCONT"])) {
		$cntAnno = 1;
		$query = "INSERT INTO BCD_DATIV2.CRMCNT01L (CNTIPO,CNANNO,CNCONT) VALUES('PHPTIMBRAT', ".date("Y").", ".$cntAnno.") WITH NC";
		$res = odbc_exec($conn,$query);
		if(!$res) {
			$errMsg = "Errore inserimento contatore: ".odbc_errormsg($conn);
			error_log($errMsg);
			exit;
		}	
	} else {
		$cntAnno = $row["CNCONT"] + 1;
		$query = "UPDATE BCD_DATIV2.CRMCNT01L SET CNCONT = ".$cntAnno." WHERE CNTIPO = 'PHPTIMBRAT' AND CNANNO = ".date("Y")." WITH NC";
		$res = odbc_exec($conn,$query);
		if(!$res) {
			$errMsg = "Errore aggiornamento contatore: ".odbc_errormsg($conn);
			error_log($errMsg);
			exit;
		}		
	}
	//ricavo progressivo elaborazione [f]

	$progrElab = date("Y") * 10000000 + $cntAnno;  
 
	error_log($progrElab." - richiamo servizio gepacon");

	$filePath = $temporaryFolder."tra_".$progrElab.".txt";
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
		'ffile' => new \CurlFile($filePath, 'text/plain', 'tra_'.$progrElab.'.txt')
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
	
	
	//tutto ok.. aggiorno dati su file:
	for($r=0; $r<count($RRNToUpdate);$r++) { 
		$RRN = $RRNToUpdate[$r];
		$query = "UPDATE BCD_DATIV2.SAVTIM0F AS F SET STTRAS = 'S' WHERE RRN(F) = ".$RRN." WITH NC";
		$res_upd = odbc_exec($conn,$query);
		if(!$res_upd) {
			$errMsg = "Errore aggiornamento stato: ".odbc_errormsg();
			error_log($errMsg);
			exit;
		}
	}
	 	
} 

//....
error_log("fine procedura");
	  
echo "<html><body style='font-family: sans-serif; padding: 20px;'>";
echo "<h2>Invio completato</h2>";
echo "<p>Timbrature inviate: <strong>{$cntTimbrature}</strong></p>";
echo "<p>Progressivo elaborazione: <strong>{$progrElab}</strong></p>";
echo "<button onclick=\"window.close()\" style='margin-top: 20px; padding: 10px 20px; font-size: 16px;'>Chiudi</button>";
echo "</body></html>";

