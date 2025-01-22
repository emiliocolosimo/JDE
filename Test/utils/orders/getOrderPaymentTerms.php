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

$SHPTC = '';
if(isset($_REQUEST["SHPTC"])) $SHPTC = trim($_REQUEST["SHPTC"]);
$filter_desc = '';
if(isset($_REQUEST["filter_desc"])) $filter_desc = trim($_REQUEST["filter_desc"]); 

//if($SHPTC=='') die('{"stat":"err","msg":"Parametro Payment Terms obbligatorio"}');
 

/*
1) Nel caso di campo reperito dalle tabelle presenti nel file F0005, l'eventuale descrizione in lingua, se presente, è da reperire nel campo DRDL01 del file F0005D con chiave:
- DRSY (come per F0005)
- DRRT (come per F0005)
- DRKY (come per F0005)
- DRLNGP = codice lingua (char 2). 

Il codice lingua è presente nel campo ABLNGP del file F0101 (anagrafica cliente) letto per  ABAN8=<codice cliente>

*/
   
$query = "SELECT TRIM(PNPTC) AS PNPTC, 
TRIM(PNPTD) AS PNPTD
FROM JRGDTA94C.F0014 
 ";
$whrClause = '';
$whrLink = ' WHERE '; 
if($SHPTC!="") {
	$whrClause .= $whrLink." PNPTC = ?";
	$whrLink = ' OR ';
}
if($filter_desc!="") {
	$whrClause .= $whrLink." (LOWER(PNPTD) LIKE ? OR PNPTC = ?)";
	$whrLink = ' OR ';
} 
if($whrClause!='') $query.=$whrClause;
 
$pstmt = odbc_prepare($conn,$query);

$returnJson = '';
$r = 0; 
if($pstmt) {
	$arrParams = array();	
	if($SHPTC!="") $arrParams[] = trim($SHPTC);			
	if($filter_desc!="") $arrParams[] = '%'.trim(strtolower($filter_desc)).'%';	
	if($filter_desc!="") $arrParams[] = substr(trim($filter_desc), 0, 3);		
 
	$res = odbc_execute($pstmt,$arrParams);
	$r = 0;
	if($res) {
		while($row = odbc_fetch_array($pstmt)) {
			$PNPTC = utf8_encode($row["PNPTC"]);
			$PNPTD = utf8_encode($row["PNPTD"]);
			 
			if($r>0) $returnJson .= ',';
			$returnJson .= '{"payment_terms_code":'.json_encode($PNPTC).',"payment_terms_descr":'.json_encode($PNPTD).'}';
			$r++;
			
		} 
		
	}
	
	if($r>1) echo '['.$returnJson.']';
	else echo $returnJson;
	
}
  
odbc_close($conn);
