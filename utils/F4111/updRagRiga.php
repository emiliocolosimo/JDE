<?php
include("config.inc.php");

set_time_limit(0);

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);
ini_set("log_errors", 0);
ini_set("error_log", "/www/php80/htdocs/utils/F4111/logs/log_".date("Ym").".txt");
 

 
$server="Driver={IBM i Access ODBC Driver};System=127.0.0.1;Uid=".DB2_USER.";Pwd=".DB2_PASS.";TRANSLATE=1;"; 
$user=DB2_USER; 
$pass=DB2_PASS; 
 
$conn=odbc_connect($server,$user,$pass); 
if(!$conn) {
	echo odbc_errormsg($conn);
	exit;
}
odbc_setoption($conn, 1, 103, 240);
 
 
$query = "SELECT RRN(T) AS ROWRRN, T.ILDOC AS ILDOC, T.ILDCT AS ILDCT, T.ILTRNO AS ILTRNO, T.ILFRTO AS ILFRTO, T.ILCTWT AS ILCTWT  
FROM JRGDTA94C.F4111 AS T
WHERE ILDCT = 'IL'                                              
ORDER BY ILDOC, ILDCT, ILTRNO  
";

$result=odbc_exec($conn,$query);
if(!$result) {
	error_log("Errore query: ".odbc_errormsg());
	exit;
}
 
$prevILDOC = ''; 
$prevILDCT = '';
$prevILFRTO = '';
$groupNbr = 1;

while($row = odbc_fetch_array($result)){
		
		$ROWRRN = $row["ROWRRN"]; 
		$ILDOC = $row["ILDOC"]; 
		$ILDCT = $row["ILDCT"];    
		$ILFRTO = $row["ILFRTO"];  
		$ILCTWT = $row["ILCTWT"]; 
		
		if($ILDOC!=$prevILDOC || $ILDCT!=$prevILDCT) {
			$groupNbr = 1;
		} else {
			if($prevILFRTO=='T' && $ILFRTO=='F') $groupNbr++;
		}
		
		$query = "UPDATE JRGDTA94C.F4111 AS T SET T.ILCTWT = ? WHERE RRN(T) = ? WITH NC";
		$pstmt = odbc_prepare($conn,$query);
		if($pstmt) {
			$arrParams = array();	
			$arrParams[] = $groupNbr;
			$arrParams[] = $ROWRRN;
			$resUpd = odbc_execute($pstmt,$arrParams);
			if(!$resUpd) {
				$errMsg = "Errore query aggiornamento: ".odbc_errormsg($conn);
				error_log($errMsg);
				exit;
			}	
		} else {
			$errMsg = "Errore prepare aggiornamento: ".odbc_errormsg($conn);
			error_log($errMsg);
			exit;
		}
		
		$prevILDOC = $ILDOC;
		$prevILDCT = $ILDCT;
		$prevILFRTO = $ILFRTO;
		
}

error_log("Procedura completata");