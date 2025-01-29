<?php
 
header('Content-Type: application/json; charset=utf-8');
  

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
$conn=odbc_connect($server,$user,$pass); 
if(!$conn) {
	echo odbc_errormsg($conn);
	exit;
}

$TATXA1 = '';
if(isset($_REQUEST["TATXA1"])) $TATXA1 = $_REQUEST["TATXA1"];

$resArray = array();

$query = "SELECT TRIM(TATXA1) AS TATXA1,
TRIM(TATAXA) AS TATAXA, 
TATXR1, 
TATXR3 
FROM JRGDTA94C.F4008    
";
if($TATXA1!="") $query .= " WHERE TATXA1 = ? "; 
if($TATXA1=="SOGG") $query .= "AND TATXR1 = 22000 FETCH FIRST ROW ONLY";

$pstmt = odbc_prepare($conn,$query);
if($pstmt) {
	$arrParams = array();	
	if($TATXA1!="") $arrParams[] = trim($TATXA1);			
	$res = odbc_execute($pstmt, $arrParams);
	
	while($row = odbc_fetch_array($pstmt)) {
		
		foreach(array_keys($row) as $key)
		{
			$row[$key] = utf8_encode($row[$key]);
		}
		
		$resArray[] = $row;
		
	}
	 
}


echo json_encode($resArray);

odbc_close($conn);
