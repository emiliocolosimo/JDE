<?php

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set("log_errors", 1); 
ini_set("error_log", "/www/php80/htdocs/apihunter/logs/trasfDomains_".date("Ym").".log");

include("/www/php80/htdocs/apihunter/config.inc.php");

$server="Driver={IBM i Access ODBC Driver};System=127.0.0.1;Uid=".AH_DB2_USER.";Pwd=".AH_DB2_PASS.";TRANSLATE=1;"; 
$user=AH_DB2_USER; 
$pass=AH_DB2_PASS;   
$conn=odbc_connect($server,$user,$pass); 
if(!$conn) {
	$errMsg = "Errore connessione al database : ".odbc_errormsg($conn);
	error_log($errMsg);
	exit;
}

$query = "SELECT * FROM JRGDTA94C.DSINFOTF";
$res = odbc_exec($conn,$query);
while($row = odbc_fetch_array($res)) {
	$mails = trim($row["DIMAIL"]);
	$mailArray = explode(",",$mails);
	for($j=0;$j<count($mailArray);$j++) {
		
		$DIDOMA  = $row["DIDOMA"];
		$DIORGA  = $row["DIORGA"];
		$DILKIN  = $row["DILKIN"];
		$DICOUN  = $row["DICOUN"];
		$DISTATE = $row["DISTATE"];
		$DICITY  = $row["DICITY"];
		$DIFBOK  = $row["DIFBOK"];
		$DIINGR  = $row["DIINGR"];
		$DIMAIL  = $mailArray[$j];
		$DIIMDT  = $row["DIIMDT"];
		$DIIMTI  = $row["DIIMTI"];	
		
		$query = "INSERT INTO JRGDTA94C.DSINFO0F 
		(DIDOMA,DIORGA,DILKIN,DICOUN,DISTATE,DICITY,DIFBOK,DIINGR,DIMAIL,DIIMDT,DIIMTI) 
		VALUES(?,?,?,?,?,?,?,?,?,?,?)";
		 
		$pstmt = odbc_prepare($conn,$query);
		if($pstmt) {
			$arrParams = array();	
			$arrParams[] = $DIDOMA; 			
			$arrParams[] = $DIORGA; 			
			$arrParams[] = $DILKIN;			
			$arrParams[] = $DICOUN; 			
			$arrParams[] = $DISTATE;			
			$arrParams[] = $DICITY; 			
			$arrParams[] = $DIFBOK; 			
			$arrParams[] = $DIINGR; 			
			$arrParams[] = $DIMAIL; 
			$arrParams[] = $DIIMDT; 
			$arrParams[] = $DIIMTI; 
			 
			$resIns = odbc_execute($pstmt,$arrParams);
			 
		}
	}
	

}