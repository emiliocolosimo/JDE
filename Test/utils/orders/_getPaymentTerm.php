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

$PNPTC = '';
if(isset($_REQUEST["PNPTC"])) $PNPTC = $_REQUEST["PNPTC"];

$query = "SELECT TRIM(PNPTC) AS PNPTC, 
TRIM(PNPTD) AS PNPTD
FROM JRGDTA94C.F0014 
";
if($PNPTC!="") $query.= " WHERE PNPTC = ? ";
$pstmt = odbc_prepare($conn,$query);

$r = 0;
echo '[';
if($pstmt) {
	$arrParams = array();	
	if($PNPTC!="") $arrParams[] = trim($PNPTC);			
	 
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
