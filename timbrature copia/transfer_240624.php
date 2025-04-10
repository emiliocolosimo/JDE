<?php 
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set("log_errors", 1);
ini_set("error_log", "/www/php80/htdocs/timbrature/logs/transfer_".date("Ym").".log");
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

/*
	0. Pulisco dal file db2 i record più vecchi di 1 anno

	1. Richiamo ws timbrature per intervallo di date .. a ..
	2. Controllo variabile 'ritorno' -> se = 0 errore -> scrivo log ed esco
	3. Ricavo il progressivo della richiesta AAAANNNNNNN
	3. Scorro risposta json timbrature 
		3.1 Controllo se già presente in file db2
			3.1a Se non presente vado a 3.2
			3.1b Se presente salto il 3.2 e scorro il prossimo
		3.2 Inserisco la timbratura in file db2 
	4. Leggo file db2 con progressivo richiesta e creo il file allegato
	5. Invio file allegato con chiamata a gepacon
	6. Se chiamata in errore cancello questa richiesta dal file

*/

//pulitura:
//elimino più vecchi di un anno:
$date_dlt = date("Y-m-d",strtotime("-1 year"));
$query = "DELETE FROM BCD_DATIV2.RQTIMB00F WHERE TMDTIN < ".$date_dlt." WITH NC";
$res = odbc_exec($conn,$query);
if(!$res) {
	$errMsg = "Errore pulitura del file [1]: ".odbc_errormsg($conn);
	error_log($errMsg);
	exit;
}
//elimino trasferimenti non andati a buon fine:
$query = "DELETE FROM BCD_DATIV2.RQTIMB00F WHERE TMSTAT<>'OK' WITH NC";
$res = odbc_exec($conn,$query);
if(!$res) {
	$errMsg = "Errore pulitura del file [2]: ".odbc_errormsg($conn);
	error_log($errMsg);
	exit;
}
  
error_log("Inizio elaborazione");
 

//richiamo dati APP:
$curl_handle = curl_init(); 
$date_from = date("Y-m-d",strtotime("-3 days"));
$date_to = date("Y-m-d");
$url = "https://".LIBEMAX_ACCOUNT_NAME.".libemax.com/app-timbrature/it/api/v3/timbratura/timbratura_elenco?da=".$date_from."&a=".$date_to; 

curl_setopt($curl_handle, CURLOPT_URL, $url);
curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl_handle, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($curl_handle, CURLOPT_HTTPHEADER, ['x-api-key: '.LIBEMAX_API_KEY]);


$curl_data = curl_exec($curl_handle);
if(!$curl_data) {
	$errMsg = "Errore chiamata cURL libemax : ".curl_error($curl_handle);
	error_log($errMsg);
	exit;
}
curl_close($curl_handle);

$response_data = json_decode($curl_data,true); 

if(!isset($response_data["ritorno"])) {  
	$errMsg = "Errore dati di ritorno libemax: ".$curl_data;
	error_log($errMsg);
	exit;
} 
if($response_data["ritorno"]!='1') {  
	$errMsg = "Codice di ritorno '1' libemax: ".$curl_data;
	error_log($errMsg);
	exit;
} 

 
 
//scrivo la testata: db2:
$fileContent = "";
$cntTimbrature = 0;
$timbrature = $response_data["dati"]["timbratura"]; 

$progrElab = 0;
if(count($timbrature)>0) {
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
}

if($progrElab==0) {
	$errMsg = "Nessuna timbratura da trasferire";
	error_log($errMsg);
	exit;
}

for($i=0;$i<count($timbrature);$i++) {
	$curTimbratura = $timbrature[$i];
	
	$codDipendente = $curTimbratura["dipendente"]["codice_gestionale"];
	$oraInizio = $curTimbratura["ora_inizio"];
	$oraInizioArrotondata = $curTimbratura["ora_inizio_arrotondata"];
	$oraFine = $curTimbratura["ora_fine"];
	$oraFineArrotondata = $curTimbratura["ora_fine_arrotondata"];
	$ore = $curTimbratura["ore"];
	$oreArrotondate = $curTimbratura["ore_arrotondate"];
	
	//controllo se già presente in db:
	$skipRecord = false;
	$TMID = $curTimbratura["id"];
	$TMTIPO = "E";
	if($oraFineArrotondata!='' && $oraFineArrotondata!=null) {
		$entReg = false;
		//controllo se è stata registrata l'entrata per questo ID
		$query = "SELECT 'S' AS HASENT FROM BCD_DATIV2.RQTIMB00F WHERE TMID = ".$TMID." AND TMTIPO = 'E' FETCH FIRST ROW ONLY";
		$res = odbc_exec($conn,$query);
		if($res) {
			$row = odbc_fetch_array($res);
			if(isset($row) && isset($row["HASENT"]) && $row["HASENT"]=='S') $entReg = true;
		}
		
		if($entReg) $TMTIPO = "U";
		else $TMTIPO = "E";
	}
	
	$query = "SELECT 'S' AS HASREC FROM BCD_DATIV2.RQTIMB00F WHERE TMID = ".$TMID." AND TMTIPO = '".$TMTIPO."' FETCH FIRST ROW ONLY";
	$res = odbc_exec($conn,$query);
	if($res) {
		$row = odbc_fetch_array($res);
		if(isset($row) && isset($row["HASREC"]) && $row["HASREC"]=='S') $skipRecord = true;
	}
	
	if(!$skipRecord) {
		 
		$TMPROG = $progrElab;
		$TMID   = $TMID;
		$TMDCDI = $codDipendente; 
		$TMDTIN = substr($oraInizio,6,4).substr($oraInizio,3,2).substr($oraInizio,0,2);
		$TMORIN = substr($oraInizio,11,2).substr($oraInizio,14,2).substr($oraInizio,17,2);
		$TMDTIA = substr($oraInizioArrotondata,6,4).substr($oraInizioArrotondata,3,2).substr($oraInizioArrotondata,0,2);
		$TMORIA = substr($oraInizioArrotondata,11,2).substr($oraInizioArrotondata,14,2);
		$TMDAFI = substr($oraFine,6,4).substr($oraFine,3,2).substr($oraFine,0,2);
		$TMORFI = substr($oraFine,11,2).substr($oraFine,14,2).substr($oraFine,17,2);
		$TMDAFA = substr($oraFineArrotondata,6,4).substr($oraFineArrotondata,3,2).substr($oraFineArrotondata,0,2);
		$TMORFA = substr($oraFineArrotondata,11,2).substr($oraFineArrotondata,14,2);
		$TMORE  = $ore;
		$TMOREA = $oreArrotondate;
		$TMSTAT = "";
		$TMDTAD = date("Ymd");
		$TMORAD = date("His");	
		
		if(!is_numeric($TMDTIN)) $TMDTIN = 0;
		if(!is_numeric($TMORIN)) $TMORIN = 0;
		if(!is_numeric($TMDTIA)) $TMDTIA = 0;
		if(!is_numeric($TMORIA)) $TMORIA = 0;
		if(!is_numeric($TMDAFI)) $TMDAFI = 0;
		if(!is_numeric($TMORFI)) $TMORFI = 0;
		if(!is_numeric($TMDAFA)) $TMDAFA = 0;
		if(!is_numeric($TMORFA)) $TMORFA = 0;
		if($TMORE==null) $TMORE = "";
		if($TMOREA==null) $TMOREA = "";
		
		
		
		//inserisco in file db:
		$query = "INSERT INTO BCD_DATIV2.RQTIMB00F (TMPROG,TMTIPO,TMID,TMDCDI,TMDTIN,TMORIN,TMDTIA,TMORIA,TMDAFI,TMORFI,TMDAFA,TMORFA,TMORE,TMOREA,TMSTAT,TMDTAD,TMORAD) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) WITH NC";
		$pstmt = odbc_prepare($conn,$query);
		if($pstmt) {
			$arrParams = array();	
			$arrParams[] = $TMPROG;
			$arrParams[] = $TMTIPO;
			$arrParams[] = $TMID  ;
			$arrParams[] = $TMDCDI;
			$arrParams[] = $TMDTIN;
			$arrParams[] = $TMORIN;
			$arrParams[] = $TMDTIA;
			$arrParams[] = $TMORIA;
			$arrParams[] = $TMDAFI;
			$arrParams[] = $TMORFI;
			$arrParams[] = $TMDAFA;
			$arrParams[] = $TMORFA;
			$arrParams[] = $TMORE ;
			$arrParams[] = $TMOREA;
			$arrParams[] = $TMSTAT;
			$arrParams[] = $TMDTAD;
			$arrParams[] = $TMORAD;
			 
			$res = odbc_execute($pstmt,$arrParams);
			if(!$res) {
				$errMsg = "Errore query inserimento richiesta: ".odbc_errormsg();
				error_log($errMsg);
				exit;
			}		
			
		} else {
			$errMsg = "Errore prepare inserimento richiesta: ".odbc_errormsg();
			error_log($errMsg);
			exit;
		}
		  

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
		
		/*
			entrata/uscita	1	1
			giorno			3	2
			mese			6	2
			anno			9	4
			ora			   14	2
			minuti		   17	2
			badge		   20   4	 
		*/ 
		$fileContent.= $gepTipo.";".$gepGiorno.";".$gepMese.";".$gepAnno.";".$gepOra.";".$gepMinuti.";".$gepBadge;
		
		$cntTimbrature++;
		
		/*
		0         1         2         3
		1234567890123456789012345678901234567890
		0;12;04;2024;16;00;PROVA
		*/
	}
	
	
}



error_log($progrElab." - trovate ".$cntTimbrature." timbrature");

if($cntTimbrature>0) {

	error_log($progrElab." - richiamo servizio gepacon");

	$filePath = "/www/php80/htdocs/timbrature/temp/tra_".$progrElab.".txt";
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

	//tutto ok.. aggiorno dati su file temp:
	$query = "UPDATE BCD_DATIV2.RQTIMB00F SET TMSTAT = 'OK' WHERE TMPROG = ".$progrElab." WITH NC";
	$res = odbc_exec($conn,$query);
	if(!$res) {
		$errMsg = "Errore aggiornamento stato richiesta: ".odbc_errormsg($conn);
		error_log($errMsg);
		exit;
	}	
} else {
	//riporto indietro il contatore:
	$cntAnno = $cntAnno - 1;
	$query = "UPDATE BCD_DATIV2.CRMCNT01L SET CNCONT = ".$cntAnno." WHERE CNTIPO = 'PHPTIMBRAT' AND CNANNO = ".date("Y")." WITH NC";
	$res = odbc_exec($conn,$query);
	if(!$res) {
		$errMsg = "Errore aggiornamento contatore: ".odbc_errormsg($conn);
		error_log($errMsg);
		exit;
	}	
}

//....
error_log($progrElab." - fine procedura");
	  



