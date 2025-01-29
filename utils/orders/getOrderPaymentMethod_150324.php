<?php
 
header('Content-Type: application/json; charset=iso-8859-1'); 
 
error_reporting(E_ALL); 

include("config.inc.php"); 
/*
$k = '';
if(isset($_REQUEST['k'])) $k = $_REQUEST["k"];
if($k!="sJHsdwvIFTyhDuGtZoOfevsgG1A1H2s6") { 
	exit;
}
*/

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

/*
SHDOCO (Order Number)
SHDCTO (Order Type)        
SHKCOO (Order Company)
*/

$SHRYIN = '';
if(isset($_REQUEST["SHRYIN"])) $SHRYIN = trim($_REQUEST["SHRYIN"]);
$SHAN8 = '';
if(isset($_REQUEST["SHAN8"])) $SHAN8 = trim($_REQUEST["SHAN8"]); 
 

if($SHRYIN=='') die('{"stat":"err","msg":"Parametro Payment Method obbligatorio"}');
if($SHAN8=='') die('{"stat":"err","msg":"Parametro Customer Number obbligatorio"}');


/*
1) Nel caso di campo reperito dalle tabelle presenti nel file F0005, l'eventuale descrizione in lingua, se presente, è da reperire nel campo DRDL01 del file F0005D con chiave:
- DRSY (come per F0005)
- DRRT (come per F0005)
- DRKY (come per F0005)
- DRLNGP = codice lingua (char 2). 

Il codice lingua è presente nel campo ABLNGP del file F0101 (anagrafica cliente) letto per  ABAN8=<codice cliente>

*/
  
$query = "SELECT TRIM(DRKY) AS DRKY,  
TRIM(SUBSTR(DRDL01, 1, 30)) AS DRDL01
FROM JRGCOM94T.F0005D 
JOIN JRGDTA94C.F0101 ON DRLNGP = ABLNGP 
WHERE DRSY='00' 
AND DRRT='RY' 
AND TRIM(DRKY) = ? 
AND TRIM(ABAN8) = ? ";
$pstmt = odbc_prepare($conn,$query);

$r = 0; 
if($pstmt) {
	$arrParams = array();	
	$arrParams[] = trim($SHRYIN);			
	$arrParams[] = trim($SHAN8);		
	 
	$res = odbc_execute($pstmt,$arrParams);
	if($res) {
		$row = odbc_fetch_array($pstmt);
		if($row) {
			$DRKY = utf8_encode($row["DRKY"]); 
			$DRDL01 = utf8_encode($row["DRDL01"]); 
		} else {
			//descrizione non presente in lingua
			//estraggo quella in italiano:
			$query = "SELECT TRIM(DRKY) AS DRKY,  
			TRIM(SUBSTR(DRDL01, 1, 30)) AS DRDL01
			FROM JRGCOM94T.F0005 
			WHERE DRSY='00' 
			AND DRRT='RY' 
			AND TRIM(DRKY) = ? 
			 ";
			$pstmtIta = odbc_prepare($conn,$query);
			if($pstmtIta) {
				$arrParams = array();	
				$arrParams[] = trim($SHRYIN);	 	
				 
				$resIta = odbc_execute($pstmtIta,$arrParams);
				if($resIta) {
					$rowIta = odbc_fetch_array($pstmtIta);
					if($rowIta) {
						$DRKY = utf8_encode($rowIta["DRKY"]); 
						$DRDL01 = utf8_encode($rowIta["DRDL01"]); 
					} 
				}
			} 
		}
		
		echo '{"payment_method_code":'.json_encode($DRKY).',"payment_method_descr":'.json_encode($DRDL01).'}';
		 
	}
} 

odbc_close($conn);
