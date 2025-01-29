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
$filter_desc = '';
if(isset($_REQUEST["filter_desc"])) $filter_desc = trim($_REQUEST["filter_desc"]); 

 
 
//if($SHAN8=='') die('{"stat":"err","msg":"Parametro Customer Number obbligatorio"}');


/*
1) Nel caso di campo reperito dalle tabelle presenti nel file F0005, l'eventuale descrizione in lingua, se presente, è da reperire nel campo DRDL01 del file F0005D con chiave:
- DRSY (come per F0005)
- DRRT (come per F0005)
- DRKY (come per F0005)
- DRLNGP = codice lingua (char 2). 

Il codice lingua è presente nel campo ABLNGP del file F0101 (anagrafica cliente) letto per  ABAN8=<codice cliente>

*/
$DRLNGP = '';
if($SHAN8!='') {
	$query = "SELECT TRIM(ABLNGP) AS DRLNGP 
	FROM JRGDTA94C.F0101 
	WHERE TRIM(ABAN8) = ?
	";
	$pstmt = odbc_prepare($conn,$query);
	if($pstmt) {
		$arrParams = array();	 		
		$arrParams[] = trim($SHAN8);		
		 
		$res = odbc_execute($pstmt,$arrParams);
		if($res) {
			$row = odbc_fetch_array($pstmt);
			$DRLNGP = $row["DRLNGP"];
		}
	}
}

$query = "SELECT TRIM(DRKY) AS DRKY,  
TRIM(SUBSTR(DRDL01, 1, 30)) AS DRDL01
FROM JRGCOM94T.F0005   
WHERE DRSY='00' 
AND DRRT='RY'  
"; 
$pstmt = odbc_prepare($conn,$query);

$returnJson = '';
$r = 0; 
if($pstmt) {
	$arrParams = array();		
 	 
 	$r = 0;
	 
	$res = odbc_execute($pstmt,$arrParams);
	if($res) {
		while($row = odbc_fetch_array($pstmt)) {
			$DRKY = utf8_encode($row["DRKY"]);  
			$DRDL01 = utf8_encode($row["DRDL01"]); 
			
			if($SHAN8!='') {
				//cerco in lingua:
				$query = "SELECT TRIM(DRKY) AS DRKY,  
				TRIM(DRLNGP) AS DRLNGP, 
				TRIM(SUBSTR(DRDL01, 1, 30)) AS DRDL01
				FROM JRGCOM94T.F0005D  
				WHERE DRSY='00' 
				AND DRRT='RY' 
				AND TRIM(DRKY) = ?   
				";  
				$whrClause = '';
				$whrLink = ' '; 
				if($DRLNGP!="") {
					$whrClause .= $whrLink." TRIM(DRLNGP) = ?  ";
					$whrLink = ' OR ';
				}
				if($filter_desc!="") {
					$whrClause .= $whrLink." (TRIM(SUBSTR(LOWER(DRDL01), 1, 30)) LIKE ? OR TRIM(DRKY) = ?)  ";
					$whrLink = ' OR ';
				} 
				if($whrClause!='') $query.= ' AND ('.$whrClause.')';
				
				$pstmtLng = odbc_prepare($conn,$query);
				if($pstmtLng) {
					$arrParams = array();	
					$arrParams[] = trim($DRKY);		
					if($DRLNGP!="") $arrParams[] = trim($DRLNGP);	
					if($filter_desc!="") $arrParams[] = '%'.trim($filter_desc).'%';
					if($filter_desc!="") $arrParams[] = trim(substr($filter_desc,0,10));
					 
					$resLng = odbc_execute($pstmtLng,$arrParams);
					if($resLng) {
						$rowLng = odbc_fetch_array($pstmtLng);
						if($rowLng) $DRDL01 = utf8_encode($rowLng["DRDL01"]); 
					}
				}
			}
			
			$dspVal = true;
			if($SHRYIN!="" || $filter_desc!="") {
				$dspVal = false;
				if($filter_desc!="") { 
					if(strpos(strtolower($DRDL01), strtolower($filter_desc)) !== false) $dspVal = true;
					if(strpos(strtolower($DRKY), strtolower($filter_desc)) !== false) $dspVal = true;
				}
				if($SHRYIN!="") { 
					if(strpos(strtolower($DRKY), strtolower($SHRYIN)) !== false) $dspVal = true;
				} 
			}
			 
			
			if($dspVal) {
				if($r>0) $returnJson .= ',';
				$returnJson .= '{"payment_method_lang":'.json_encode($DRLNGP).',"payment_method_code":'.json_encode($DRKY).',"payment_method_descr":'.json_encode($DRDL01).'}';
				$r++;
			} 
			
		}
	}
	
	if($r>1) echo '['.$returnJson.']';
	else echo $returnJson;
	
}

 

odbc_close($conn);
