<?php
include("config.inc.php");

header('Content-Type: application/json; charset=utf-8');

set_time_limit(60);

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set("log_errors", 1);
ini_set("error_log", "/www/php80/htdocs/logs/getDesMate/php-error.log");

$k = '';
if(isset($_REQUEST['k'])) $k = $_REQUEST["k"];
if($k!="sJHsdwvIFTyhDuGtZoOfevsgG1A1H2s6") { 
	exit;
}

$IMITM = '';
if(isset($_REQUEST['IMITM'])) $IMITM = $_REQUEST["IMITM"];
 
$IMLITM = '';
if(isset($_REQUEST['IMLITM'])) $IMLITM = $_REQUEST["IMLITM"];
 if($IMLITM=="" && $IMITM=="") { 
	exit;
} 

$server="Driver={IBM i Access ODBC Driver};System=127.0.0.1;Uid=".DB2_USER.";Pwd=".DB2_PASS.";TRANSLATE=1;CONNTYPE=2;CMT=0;BLOCKFETCH=1;BLOCKSIZE=2000"; 
$user=DB2_USER; 
$pass=DB2_PASS; 

//connessione:
$time_start = microtime(true); 

$conn=odbc_connect($server,$user,$pass); 
if(!$conn) {
	echo odbc_errormsg($conn);
	exit;
}
 
//query:
$DRDL01A = "";
$query = "  
SELECT TRIM(DRDL01) AS DRDL01 
FROM JRGCOM94T.F0005, JRGDTA94C.F41021JCRM    
WHERE trim(imsrp1)=trim(drky) 
AND DRSY='55' 
AND DRRT='MT' ";      
if($IMITM!="") $query.= " AND IMITM = ? ";		 
if($IMLITM!="") $query.= " AND IMLITM = ? ";		 
$query.= " FETCH FIRST ROW ONLY";
$pstmt = odbc_prepare($conn,$query);
if($pstmt) {
	$arrParams = array();	
	if($IMITM!="") $arrParams[] = $IMITM;		 
	if($IMLITM!="") $arrParams[] = $IMLITM;		 
	
	$res = odbc_execute($pstmt,$arrParams);
	if($res) {
		$row = odbc_fetch_array($pstmt);
		if(isset($row) && isset($row["DRDL01"])) {
			$DRDL01A = utf8_encode($row["DRDL01"]);
		}
	}
}

$DRDL01B = "";
$query = "  
SELECT TRIM(DRDL01) AS DRDL01 
FROM JRGCOM94T.F0005, JRGDTA94C.F41021JCRM    
WHERE trim(imsrp3)=trim(drky) 
AND DRSY='55' 
AND DRRT='MT' ";      
if($IMITM!="") $query.= " AND IMITM = ? ";		 
if($IMLITM!="") $query.= " AND IMLITM = ? ";	      
$query.= " FETCH FIRST ROW ONLY";
$pstmt = odbc_prepare($conn,$query);
if($pstmt) {
	$arrParams = array();	
	if($IMITM!="") $arrParams[] = $IMITM;		 
	if($IMLITM!="") $arrParams[] = $IMLITM;			 
	
	$res = odbc_execute($pstmt,$arrParams);
	if($res) {
		$row = odbc_fetch_array($pstmt);
		if(isset($row) && isset($row["DRDL01"])) {
			$DRDL01B = utf8_encode($row["DRDL01"]);
		}
	}
}
 
echo '{"DES_IMSRP1":'.json_encode($DRDL01A).',"DES_IMSRP3":'.json_encode($DRDL01B).'}';  
 
odbc_close($conn);