<?php
 
header('Content-Type: application/json; charset=iso-8859-1'); 
 
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

$ABAN8 = '';
if(isset($_REQUEST["ABAN8"])) $ABAN8 = trim($_REQUEST["ABAN8"]);
$DRKY = '';
if(isset($_REQUEST["DRKY"])) $DRKY = trim($_REQUEST["DRKY"]); 

/*
1) Nel caso di campo reperito dalle tabelle presenti nel file F0005, l'eventuale descrizione in lingua, se presente, è da reperire nel campo DRDL01 del file F0005D con chiave:
- DRSY (come per F0005)
- DRRT (come per F0005)
- DRKY (come per F0005)
- DRLNGP = codice lingua (char 2). 

Il codice lingua è presente nel campo ABLNGP del file F0101 (anagrafica cliente) letto per  ABAN8=<codice cliente>

*/
 
$query = "SELECT TRIM(DRKY) AS DRKY, 
TRIM(ABAN8) AS ABAN8, 
TRIM(SUBSTR(DRDL01, 1, 30)) AS DRDL01
FROM JRGCOM94T.F0005D 
JOIN JRGDTA94C.F0101 ON DRLNGP = ABLNGP 
WHERE DRSY='00' 
AND DRRT='RY' ";
if($DRKY!="") $query.= " AND TRIM(DRKY) = ? ";
if($ABAN8!="") $query.= " AND TRIM(ABAN8) = ? ";
$pstmt = odbc_prepare($conn,$query);

$r = 0;
echo '[';
if($pstmt) {
	$arrParams = array();	
	if($DRKY!="") $arrParams[] = trim($DRKY);			
	if($ABAN8!="") $arrParams[] = trim($ABAN8);		
	 
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
