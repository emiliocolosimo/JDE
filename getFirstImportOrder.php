<?php
include("config.inc.php");

header('Content-Type: application/json; charset=utf-8');

set_time_limit(120);

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set("log_errors", 1);
ini_set("error_log", "/www/php80/htdocs/logs/getOrdersJrn/php-error.log");

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
 		 
		if($whrClause=="") $whrClause = " WHERE ";
		
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
$query = "SELECT RRN(JRGDTA94C.F4211) AS ID_ORDER ,
	CASE 
        WHEN SDDCTO IN ('SQ', 'OF') THEN 'Offerta'
        WHEN SDDCTO IN ('OB') THEN 'Richiamo'
        ELSE 'Ordine'
        END AS TIPO, 
    CASE 
        WHEN SDDCTO IN ('OF' , 'O1' , 'OB' , 'OG' , 'O4' , 'O5') THEN 'Italia'
        WHEN SDDCTO IN ('SQ' , 'O2' , 'O3' , 'O6' , 'O7') THEN 'Estero'
        ELSE 'Non definito'
        END AS UFFICIO,
        TRIM(SDLITM) AS SDLITM,
        TRIM(SDFRGD) AS SDFRGD,
        TRIM(SDEUSE) AS SDEUSE,
        TRIM(SDUPRC) AS SDUPRC,
        TRIM(SDFUP) AS SDFUP,
        TRIM(SDUOM4) AS SDUOM4,
        TRIM(SDCRCD) AS SDCRCD,
        TRIM(SDPDDJ) AS SDPDDJ,
        TRIM(SDADDJ) AS SDADDJ,
        TRIM(SDAN8) AS SDAN8,
        TRIM(SDASN) AS SDASN, 
        TRIM(SDDOCO) AS SDDOCO,
        TRIM(SDDOC) AS SDDOC,
        TRIM(SDDCT) AS SDDCT,
        TRIM(SDDELN) AS SDDELN,
        trim(coalesce((select min(WWMLNM) from JRGDTA94C.F0111 where JRGDTA94C.F4211.SDCARS=JRGDTA94C.F0111.WWAN8 and JRGDTA94C.F0111.WWIDLN=0), '')) as SDCARS,
        TRIM(SDLNID) AS SDLNID,
        TRIM(SDSOQS) AS SDSOQS,
        TRIM(SDAEXP) AS SDAEXP,
        TRIM(SDFEA) AS SDFEA
		FROM JRGDTA94C.F4211
    ";
if ($whrClause != "") $query .= $whrClause;
if ($ordbyClause != "") $query .= $ordbyClause;
if ($limitClause != "") $query .= $limitClause; 

 $query .= " FOR FETCH ONLY"; 
 

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