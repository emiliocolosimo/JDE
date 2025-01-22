<?php
//UNA TANTUM PER IMPORTAZIONE INIZIALE:
/*
ADDENVVAR ENVVAR(QIBM_MULTI_THREADED) VALUE(Y) REPLACE(*YES)                  
QSH CMD('/QOpenSys/pkgs/bin/php /www/php80/htdocs/apihunter/getFromWebsite.php')   
*/

set_time_limit(0);

ini_set("error_log", "/www/php80/htdocs/tests/logs/getFromWebsite_".date("Ym").".log");
include("/www/php80/htdocs/tests/config.inc.php"); 
  
error_log("Avvio lettura da MySQL sito web");  
  
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
(select meta_value from wp_usermeta wu where wu.user_id = wus.ID and meta_key = 'first_name') as uname, 
(select meta_value from wp_usermeta wu where wu.user_id = wus.ID and meta_key = 'last_name') as usurname, 
(select meta_value from wp_usermeta wu where wu.user_id = wus.ID and meta_key = 'locale') as ulocale  
from wp_users wus
";
$res = mysqli_query($conn,$query);
if(!$res) {
  $errMsg = "Error mysql query: " . mysqli_error($conn);
  error_log($errMsg);
  exit;
}

$sentMailsCounter = 0;
while($row = mysqli_fetch_assoc($res)) {
	$curMail = $row["user_email"];
	$curUserName = $row["uname"];
	$curUserSurname = $row["usurname"];
	$curLocale = $row["ulocale"];
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
 
		//nuovo contatto, invio mail:
		//..$curLocale [it_IT
		//en_GB
		//fr_FR
		//de_DE
		//es_ES] 
		//..
		//..
		
		$sentMailsCounter++;
		
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
	} 
	  
}

//
error_log("Totale mail inviate: ".$sentMailsCounter);
//error_log("Totale mail inviate: ".count($sentMailsCounter));


mysqli_close($conn);
odbc_close($db2conn);

