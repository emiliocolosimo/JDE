<?php
include("config.inc.php");

header('Content-Type: application/json; charset=utf-8');

set_time_limit(120);

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set("log_errors", 1);
ini_set("error_log", "/www/php80/htdocs/logs/getOrders/php-error.log");

$k = '';
if(isset($_REQUEST['k'])) $k = $_REQUEST["k"];
if($k!="sJHsdwvIFTyhDuGtZoOfevsgG1A1H2s6") { 
	exit;
}

$env = '';
if(isset($_REQUEST["env"])) $env = $_REQUEST["env"];
if($env=='') {
	$env='prod'; //per retrocompatibilità
}
$curLib = $envLib[$env];

$postedBody = file_get_contents('php://input');

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

$time_end = microtime(true);
$execution_time = ($time_end - $time_start);
//echo '<b>Connessione:</b> '.$execution_time.' s';

$whrClause = ""; 
$ordbyClause = "";
$limitClause = "";
$rowCount = 0;
$resArray = json_decode($postedBody, true);
if($resArray) {
	if(isset($resArray['filters']) && count($resArray['filters'])>0) {
		 
		$filterMode = $resArray["filter_mode"];
 		 
		if($whrClause=="") $whrClause = " AND ";
		
		$whrClause .= " (";
		
		$arrFilters = $resArray['filters'];
		for($i=0;$i<count($arrFilters);$i++) { 
			if($i>0) $whrClause.= " ".$filterMode." ";
			$whrClause .= " (";
			
			$curFilterMode = $arrFilters[$i]["filter_mode"];
			$curFilterFields = $arrFilters[$i]["fields"];
			
			for($f=0;$f<count($curFilterFields);$f++) {
				 
				$curFilterField = $curFilterFields[$f];
				$curFilterFieldName = $curFilterField["field"];
				$curFilterFieldType = $curFilterField["type"];
				$curFilterFieldValue = $curFilterField["value"];
				
				if($f>0) $whrClause .= " ".$curFilterMode;
				
				if($curFilterFieldName=="STATO_ORDINE") $curFilterFieldName = " (CASE WHEN PDUREC = 0 THEN 'ORDINI APERTI' ELSE CASE WHEN PXOPRC = 'TRAN' THEN 'MERCE IN TRANSITO' ELSE 'MERCE RICEVUTA' END END) ";
				
				if($curFilterFieldType=="eq") $whrClause .= " (".$curFilterFieldName." = '".$curFilterFieldValue."') ";
				if($curFilterFieldType=="neq") $whrClause .= " (".$curFilterFieldName." <> '".$curFilterFieldValue."') ";
				if($curFilterFieldType=="lt") $whrClause .= " (".$curFilterFieldName." < '".$curFilterFieldValue."') ";
				if($curFilterFieldType=="gt") $whrClause .= " (".$curFilterFieldName." > '".$curFilterFieldValue."') ";
				if($curFilterFieldType=="le") $whrClause .= " (".$curFilterFieldName." <= '".$curFilterFieldValue."') ";
				if($curFilterFieldType=="ge") $whrClause .= " (".$curFilterFieldName." >= '".$curFilterFieldValue."') ";
				if($curFilterFieldType=="like") $whrClause .= " (upper(".$curFilterFieldName.") LIKE '%".strtoupper($curFilterFieldValue)."%') ";
				
 				
				
			} 
			$whrClause .= " ) "; 
		} 
		$whrClause .= " ) "; 
	}
	
	
	if(isset($resArray['ordby'])) {
		$arrOrdby = $resArray['ordby'];
		//var_dump($arrOrdby);
		
		if(isset($arrOrdby[0])) {
			$ordbyClause = " ORDER BY ";
			for($ob=0;$ob<count($arrOrdby);$ob++) {
				if($ob>0) $ordbyClause.= ",";
				$ordbyClause .= $arrOrdby[$ob]["field"]." ".$arrOrdby[$ob]["dir"];
			}
		} else { 
			$ordbyFields = $arrOrdby['field'];
			$arrOrdbyFields = explode(",",$ordbyFields);
			$ordbyClause = " ORDER BY ";
			for($ob=0;$ob<count($arrOrdbyFields);$ob++) {
				if($ob>0) $ordbyClause.= ",";
				$ordbyClause.= trim($arrOrdbyFields[$ob])." ".$arrOrdby['dir'];
			}
		} 
	}
	
	if(isset($resArray['limit'])) {
		$limitClause = " LIMIT ".$resArray['limit'];
	}
	
}

$time_start = microtime(true); 
//query:
//pre 290124:
/*
$query = "
SELECT 
TRIM(PDLITM) AS PDLITM, 
TRIM(PDFRGD) AS PDFRGD, 
TRIM(PDUOPN) AS PDUOPN, 
TRIM(PDPRRC) AS PDPRRC, 
TRIM(PDFRRC) AS PDFRRC, 
TRIM(PDUOM3) AS PDUOM3, 
TRIM(PDAOPN) AS PDAOPN, 
TRIM(PDFAP) AS PDFAP, 
TRIM(PDCRCD) AS PDCRCD, 
TRIM(PRRCDJ) AS PRRCDJ 
FROM JRGDTA94C.F4311JB 
"; 
*/

$query = "SELECT 
CASE WHEN PRMATC <> '' THEN TRIM(PRLITM) ELSE TRIM(PDLITM) END AS PDLITM, 
TRIM(PDFRGD) AS PDFRGD, 
CASE WHEN PRMATC <> '' THEN TRIM(VARCHAR_FORMAT((DECIMAL(PRUREC/100, 10, 4)),'9999999990.00')) ELSE TRIM(VARCHAR_FORMAT((DECIMAL(PDUOPN/100, 10, 4)),'9999999990.00')) END AS PDUOPN, 
CASE WHEN PRMATC <> '' THEN TRIM(VARCHAR_FORMAT((DECIMAL(PRPRRC/10000, 10, 4)),'9999999990.00')) ELSE TRIM(VARCHAR_FORMAT((DECIMAL(PDPRRC/10000, 10, 4)),'9999999990.00')) END AS PDPRRC, 
CASE WHEN PRMATC <> '' THEN TRIM(VARCHAR_FORMAT((DECIMAL(PRFRRC/10000, 10, 4)),'9999999990.00')) ELSE TRIM(VARCHAR_FORMAT((DECIMAL(PDFRRC/10000, 10, 4)),'9999999990.00')) END AS PDFRRC, 
CASE WHEN PRMATC <> '' THEN TRIM(PRUOM3) ELSE TRIM(PDUOM3) END AS PDUOM3, 
CASE WHEN PRMATC <> '' THEN TRIM(VARCHAR_FORMAT((DECIMAL(PRAREC/100, 10, 4)),'9999999990.00')) ELSE TRIM(VARCHAR_FORMAT((DECIMAL(PDAOPN/100, 10, 4)),'9999999990.00')) END AS PDAOPN, 
CASE WHEN PRMATC <> '' THEN TRIM(VARCHAR_FORMAT((DECIMAL(PRFREC/100, 10, 4)),'9999999990.00')) ELSE TRIM(VARCHAR_FORMAT((DECIMAL(PDFAP/100, 10, 4)),'9999999990.00')) END AS PDFAP, 
 
CASE WHEN PRMATC <> '' THEN TRIM(PRCRCD) ELSE TRIM(PDCRCD) END AS PDCRCD, 
CASE WHEN PRRCDJ <> 0 THEN TRIM(PRRCDJ) ELSE TRIM(PDPDDJ) END AS PDPDDJ,
PDAN8, 
TRIM(COALESCE(WWMLNM, '')) AS WWMLNM,
CASE WHEN PDUREC = 0 THEN 'ORDINI APERTI' ELSE CASE WHEN PXOPRC = 'TRAN' THEN 'MERCE IN TRANSITO' ELSE 'MERCE RICEVUTA' END END AS STATO_ORDINE,
TRIM(PDCRCD) AS PDCRCD
FROM ".$curLib.".F4311JB 
LEFT JOIN ".$curLib.".F0111 ON PDAN8 = WWAN8 AND WWIDLN = 0 
WHERE TRIM(PDMCU) IN ('RGPM01','RGPM02') 
";


if($whrClause!="") $query.=$whrClause;
if($ordbyClause!="") $query.=$ordbyClause;
if($limitClause!="") $query.=$limitClause;
$query.=" FOR FETCH ONLY";


$result=odbc_exec($conn,$query);
if(!$result) {
	echo '{"status":"ERROR","errmsg":'.json_encode(odbc_errormsg()).'}';
	exit;
}
$time_end = microtime(true);
$execution_time = ($time_end - $time_start);
//echo '<b>Query:</b> '.$execution_time.' s';

echo '[';

$time_start = microtime(true); 
$r = 0;
while($row = odbc_fetch_array($result)){
		
		foreach(array_keys($row) as $key)
		{
			$row[$key] = utf8_encode($row[$key]);
		}
		
		if($r>0) echo ',';
		echo json_encode($row);
		$r++;
}

echo ']';

$time_end = microtime(true);
$execution_time = ($time_end - $time_start);
//echo '<b>Ciclo:</b> '.$execution_time.' s';

odbc_close($conn);