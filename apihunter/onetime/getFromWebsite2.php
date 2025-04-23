<?php
//UNA TANTUM PER IMPORTAZIONE INIZIALE:
/*
ADDENVVAR ENVVAR(QIBM_MULTI_THREADED) VALUE(Y) REPLACE(*YES)                  
QSH CMD('/QOpenSys/pkgs/bin/php /www/php80/htdocs/apihunter/getFromWebsite2.php')   
*/

set_time_limit(0);

ini_set("error_log", "/www/php80/htdocs/apihunter/logs/getFromWebsite2_".date("Ym").".log");
include("/www/php80/htdocs/apihunter/config.inc.php");
include("/www/php80/htdocs/apihunter/classes/DomainSearch.class.php");
  
error_log("Avvio lettura da MySQL sito web");  
  
// Create connection

//MYSQL:
$conn = mysqli_connect(AH2_MYSQL_HOST, AH2_MYSQL_USER, AH2_MYSQL_PASS);
if(!$conn) {
  $errMsg = "Connection failed: " . mysqli_connect_error();
  error_log($errMsg);
  exit;
}
$res = mysqli_select_db($conn, AH2_MYSQL_DB);
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


$query = "select distinct(user_email) from aef8_users";
$res = mysqli_query($conn,$query);
if(!$res) {
  $errMsg = "Error mysql query: " . mysqli_error($conn);
  error_log($errMsg);
  exit;
}
$extractedDomains = array();
while($row = mysqli_fetch_assoc($res)) {
	$curMail = $row["user_email"];
	if($curMail!="" && strpos($curMail,"@") !== false && strpos($curMail,".") !== false) { 
		$curDomain = substr($curMail, strpos($curMail, '@') + 1);
		if(!in_array($curDomain,$extractedDomains)) $extractedDomains[] = $curDomain;
	}	 
}

//
error_log("Totale domini estratti: ".count($extractedDomains));

//scrivo in file db2 tmp:
/*
$query = "DELETE FROM JRGDTA94C.DSSITM0F WITH NC";
$res = odbc_exec($db2conn,$query);
if(!$res) {
  $errMsg = "Errore cancellazione file temporaneo: " . odbc_errormsg($db2conn);
  error_log($errMsg);
  exit;
} 
*/

for($i=0;$i<count($extractedDomains) && $i<2000;$i++) {
	$curDomain = $extractedDomains[$i];
	$DTDOMA = $curDomain;
	$DTIMDT = date("Ymd");
	$DTIMTI = date("His");
	
	$query = "INSERT INTO JRGDTA94C.DSSITM0F(DTDOMA,DTIMDT,DTIMTI) 
	VALUES(?,?,?)
	";
	$pstmt = odbc_prepare($db2conn,$query);
	if($pstmt) {
		$arrParams = array();	
		$arrParams[] = $DTDOMA; 			
		$arrParams[] = $DTIMDT; 			
		$arrParams[] = $DTIMTI;		 
		 
		$res = odbc_execute($pstmt,$arrParams);
		if(!$res) {
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


//elaboro:
$query = "SELECT trim(DTDOMA) AS DTDOMA 
FROM JRGDTA94C.DSSITM0F 
WHERE upper(DTDOMA) NOT IN (SELECT upper(DIDOMA) FROM JRGDTA94C.DSINFO0F)
";
$res = odbc_exec($db2conn,$query);
if(!$res) {
	$errMsg = "Errore lettura records: ".odbc_errormsg($db2conn);
	error_log($errMsg);
	exit;
}
$cntr = 0; 
while($row = odbc_fetch_array($res)) {
	$curDomain = trim($row["DTDOMA"]);
	$DomainSearch = new DomainSearch();
	$resDomain = $DomainSearch->saveInfo($curDomain);
	if(!$resDomain) {
		error_log("Errore nel recupero info dominio: ".$curDomain);
	} else {
		$cntr++;
	}
	usleep(500000);
	
}
  
mysqli_close($conn);
odbc_close($db2conn);

error_log("Fine lettura da MySQL sito web CNT = ".$cntr);  