<?php
 
header('Content-Type: text/html; charset=iso-8859-1'); 
 
error_reporting(E_ALL); 

include("config.inc.php"); 

$k = '';
if(isset($_REQUEST['k'])) $k = $_REQUEST["k"];
if($k!="sJHsdwvIFTyhDuGtZoOfevsgG1A1H2s6") { 
	exit;
}

$server="Driver={IBM i Access ODBC Driver};System=127.0.0.1;Uid=".DB2_USER.";Pwd=".DB2_PASS.";TRANSLATE=1;"; 
$user=DB2_USER; 
$pass=DB2_PASS; 

//connessione:
$time_start = microtime(true); 

$conn=odbc_connect($server,$user,$pass); 
if(!$conn) {
	echo odbc_errormsg($conn);
	exit;
}

$DRKY = '';
if(isset($_REQUEST["DRKY"])) $DRKY = trim($_REQUEST["DRKY"]);
 

$query = "SELECT TRIM(DRKY) AS DRKY, 
TRIM(SUBSTR(DRDL01, 1, 30)) AS DRDL01
FROM JRGCOM94T.F0005  
WHERE DRSY='55' 
AND DRRT='BK' ";
if($DRKY!="") $query.= " AND TRIM(DRKY) = ? ";
$pstmt = odbc_prepare($conn,$query);

$r = 0;
echo '[';
if($pstmt) {
	$arrParams = array();	
	if($DRKY!="") $arrParams[] = trim($DRKY);			
	 
	$res = odbc_execute($pstmt,$arrParams);
	if($res) {
		while($row = odbc_fetch_array($pstmt)) {
			foreach(array_keys($row) as $key)
			{
				$row[$key] = utf8_encode($row[$key]);
			}
			
			if($r>0) echo ',';
			echo json_encode($row);
			
			$r++;
		}
	}
}
echo ']';

odbc_close($conn);
