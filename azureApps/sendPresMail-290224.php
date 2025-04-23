<?php

/*
ADDENVVAR ENVVAR(QIBM_MULTI_THREADED) VALUE(Y) REPLACE(*YES)                  
QSH CMD('/QOpenSys/pkgs/bin/php /www/php80/htdocs/azureApps/sendPresMail.php')   

CALL PGM(JRGOBJ94P/LCMAICON)
*/

/*
	!! QUANDO SI LANCERA' LA PRIMA VOLTA
	!! ESTRARRE UN TOT DI RECORD ALLA VOLTA
	!! ALTRIMENTI GIRA PER 30 MINUTI E VA IN TIMEOUT
*/

set_time_limit(0);

error_reporting(E_ALL);
ini_set("display_errors",0);
ini_set("log_errors",1);
ini_set("error_log", "/www/php80/htdocs/azureApps/logs/sendMail_".date("Ym").".log");
require("/www/php80/htdocs/azureApps/config.inc.php");
require("/www/php80/htdocs/azureApps/classes/AzureService.class.php");
  
error_log("Avvio lettura da MySQL sito web");  
  
$AzureService = new AzureService();
$AzureService->setTenantId(TENANT_ID);
$AzureService->setClientId(CLIENT_ID);
$AzureService->setClientSecret(CLIENT_SECRET);
$res = $AzureService->retrieveAccessToken();
if(!$res) {
	error_log($AzureService->getLastError());
	exit;
}  
  
// Create connection

//MYSQL:
$conn = mysqli_connect(AH_MYSQL_HOST, AH_MYSQL_USER, AH_MYSQL_PASS);
if(!$conn) {
  $errMsg = "Connection failed: " . mysqli_connect_error();
  error_log($errMsg);
  exit;
}
$res = mysqli_select_db($conn, AH_MYSQL_DB);
if(!$res) {
  $errMsg = "Error db selection: " . mysqli_error($conn);
  error_log($errMsg);
  exit;
}

//DB2:
$server="Driver={IBM i Access ODBC Driver};System=127.0.0.1;Uid=".AH_DB2_USER.";Pwd=".AH_DB2_PASS.";TRANSLATE=1;"; 
$user=AH_DB2_USER; 
$pass=AH_DB2_PASS;   
$db2conn=odbc_connect($server,$user,$pass); 
if(!$db2conn) {
	$errMsg = "Errore connessione al database : ".odbc_errormsg($db2conn);
	error_log($errMsg);
	exit;
}

$query = "
select user_email, 
ID, 
(select meta_value from aef8_usermeta wu where wu.user_id = wus.ID and meta_key = 'first_name') as uname, 
(select meta_value from aef8_usermeta wu where wu.user_id = wus.ID and meta_key = 'last_name') as usurname, 
(select meta_value from aef8_usermeta wu where wu.user_id = wus.ID and meta_key = 'locale') as ulocale  
from aef8_users wus 
where ID > 2317    
";
$res = mysqli_query($conn,$query);
if(!$res) {
  $errMsg = "Error mysql query: " . mysqli_error($conn);
  error_log($errMsg);
  exit;
}

$sentMailsCounter = 0;
$errorMailsCounter = 0;
while($row = mysqli_fetch_assoc($res)) {
	$curMail = $row["user_email"];
	$curUserName = $row["uname"];
	$curUserSurname = $row["usurname"];
	$curLocale = $row["ulocale"];
	if($curLocale=='') $curLocale = "en_GB";
	$curUserID = $row["ID"];
	 
	//log delle mail già inviate:
	$query = "SELECT 'S' AS CHKINS FROM BCD_DATIV2.WLGMAPR0F WHERE MPUSID = ? ";
	$pstmt = odbc_prepare($db2conn,$query);
	if($pstmt) {
		$arrParams = array();	
		$arrParams[] = $curUserID; 			
		 
		$resChk = odbc_execute($pstmt,$arrParams);
		if(!$resChk) {
			$errMsg = "Errore query 1: ".odbc_errormsg($db2conn);
			error_log($errMsg);
			exit;
		}
	} else {
		$errMsg = "Errore prepare 1: ".odbc_errormsg($db2conn);
		error_log($errMsg);
		exit;
	}	
	$rowChk = odbc_fetch_array($pstmt); 
	$chkIns = "";
	if(isset($rowChk["CHKINS"])) $chkIns = $rowChk["CHKINS"];
	
	if($chkIns!="S") {
		if(strpos($curMail,"@")!==false && strpos($curMail,".")!==false) {
			//nuovo contatto, invio mail:
			if(!isset($mailSubjectText[$curLocale])) {
				error_log("Errore recupero oggetto mail:".$curLocale);
				exit;
			}
			if(!isset($mailBodyText[$curLocale])) {
				error_log("Errore recupero corpo mail:".$curLocale);
				exit;
			}
			$mailSubject = $mailSubjectText[$curLocale];
			$mailLanguageText = $mailBodyText[$curLocale];
			$mailLanguageText = str_replace(array("[-USER_NAME-]","[-USER_SURNAME-]"),array(utf8_encode($curUserName),utf8_encode($curUserSurname)),$mailLanguageText);
			 
			$mailBody = '
			<div style="font-size:14px">
			<p>
				<img width="270" height="270" style="width:2.8125in;height:2.8125in" src="https://download.rgpballs.com/Downloads/foto1.jpg" >&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				<img width="270" height="270" style="width:2.8125in;height:2.8125in" src="https://download.rgpballs.com/Downloads/foto2.jpg" >&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				<img width="270" height="270" style="width:2.8125in;height:2.8125in" src="https://download.rgpballs.com/Downloads/foto3.jpg" >
			</p>
			<p>
				<img width="662" height="323" style="width:6.8958in;height:3.3645in" src="https://download.rgpballs.com/Downloads/foto4.png" >
			</p>	 
			'.$mailLanguageText.' 
			</div> 
			'.$mailSignature;
			  
			$from = "giorgia.belotti@rgpballs.com";
			
			error_log('Invio mail a:'.$curMail);  
			 
			//$to = array("mattia.marsura@bigblue.it","emilio.colosimo@rgpballs.com","emiglio84@gmail.com","gabrielebarzaghi@icloud.com","duilio_canta@yahoo.com");
			//$to = array("mattia.marsura@bigblue.it");
			$to = array($curMail);
			 
			$resMail = $AzureService->sendMail($from,$to,$mailSubject,$mailBody);
			if(!$resMail) {
				error_log($AzureService->getLastError()); 
				
				$errorMailsCounter++; 
			} else {
				error_log("Mail inviata a ".$curMail);
				
				//dopo aver inviato (con successo) inserisco: 
				$query = "INSERT INTO BCD_DATIV2.WLGMAPR0F (MPUSID, MPMAIL, MPNAME, MPSURN, MPDTIN, MPORIN) VALUES (?,?,?,?,?,?)";		
				$pstmtIns = odbc_prepare($db2conn,$query);
				if($pstmtIns) {
					$arrParams = array();	
					$arrParams[] = $curUserID; 
					$arrParams[] = $curMail; 
					$arrParams[] = $curUserName; 
					$arrParams[] = $curUserSurname; 
					$arrParams[] = date("Ymd"); 
					$arrParams[] = date("His"); 
					 
					$resIns = odbc_execute($pstmtIns,$arrParams);
					if(!$resIns) {
						$errMsg = "Errore query 2: ".odbc_errormsg($db2conn);
						error_log($errMsg);
						exit;
					}
				} else {
					$errMsg = "Errore prepare 2: ".odbc_errormsg($db2conn);
					error_log($errMsg);
					exit;
				}			
				
				$sentMailsCounter++;
				
				sleep(1);
				
			}
		
		} else {
			error_log("Salto indirizzo mail:".$curMail.":indirizzo mail non valido"); 
			$errorMailsCounter++; 
		}
	} 
	  
}

//
error_log("Totale mail inviate: ".$sentMailsCounter);
error_log("Totale errori: ".$errorMailsCounter);
//error_log("Totale mail inviate: ".count($sentMailsCounter));


mysqli_close($conn);
odbc_close($db2conn);

