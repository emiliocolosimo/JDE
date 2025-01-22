<?php
include("config.inc.php");

header('Content-Type: application/json; charset=utf-8');

set_time_limit(120);

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set("log_errors", 1);
ini_set("error_log", "/www/php80/htdocs/logs/getF0101/php-error.log");

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

/*
,trim(coalesce((select listagg(A3DS80,chr(10)) from ".$curLib.".F01093 where ".$curLib.".F0101.ABAN8=".$curLib.".F01093.A3AN8 and A3TYDT='NA'), '')) as F01093_1_A3DS80
,trim(coalesce((select listagg(A3DS80,chr(10)) from ".$curLib.".F01093 where ".$curLib.".F0101.ABAN8=".$curLib.".F01093.A3AN8 and A3TYDT='NP'), '')) as F01093_2_A3DS80
,trim(coalesce((select listagg(CYWTXT,chr(10)) from ".$curLib.".F00163 left join ".$curLib.".F0016 on ".$curLib.".F0016.CYSERK=".$curLib.".F00163.C5SERK where ".$curLib.".F0101.ABAN8=SUBSTRING(C5CKEY,1,8) and C5WAPP='*ADDNOTE'), '')) as F0016_CYWTXT
*/

$time_start = microtime(true); 
//query:
$query = "
SELECT * FROM
TABLE(
	SELECT ".$curLib.".F0101.*, 
	 trim(coalesce((select min(WWMLNM) from ".$curLib.".F0111 where ".$curLib.".F0101.ABAN8=".$curLib.".F0111.WWAN8 and ".$curLib.".F0111.WWIDLN=0), '')) as WWMLNM
	,trim(coalesce((select min(A2RMK) from ".$curLib.".F01092 where ".$curLib.".F0101.ABAN8=".$curLib.".F01092.A2AN8 and A2TYDT='CF'), '')) as F01092_1_A2RMK
	,trim(coalesce((select A2KY from ".$curLib.".F01092 where ".$curLib.".F0101.ABAN8=".$curLib.".F01092.A2AN8 and A2TYDT='CV' order by RRN(".$curLib.".F01092) DESC FETCH FIRST ROW ONLY), '')) as F01092_2_A2KY
	,coalesce((select A2EFT from ".$curLib.".F01092 where ".$curLib.".F0101.ABAN8=".$curLib.".F01092.A2AN8 and A2TYDT='CV' order by RRN(".$curLib.".F01092) DESC FETCH FIRST ROW ONLY), 0) as F01092_2_A2EFT
	,trim(coalesce((select A2RMK from ".$curLib.".F01092 where ".$curLib.".F0101.ABAN8=".$curLib.".F01092.A2AN8 and A2TYDT='CV' order by RRN(".$curLib.".F01092) DESC FETCH FIRST ROW ONLY), '')) as F01092_2_A2RMK
	,trim(coalesce((select min(A2KY) from ".$curLib.".F01092 where ".$curLib.".F0101.ABAN8=".$curLib.".F01092.A2AN8 and A2TYDT='FE'), '')) as F01092_3_A2KY
	,trim(coalesce((select min(A2RMK) from ".$curLib.".F01092 where ".$curLib.".F0101.ABAN8=".$curLib.".F01092.A2AN8 and A2TYDT='LI'), ''))  as F01092_4_A2RMK
	,trim(coalesce((select min(ALADD1) from ".$curLib.".F0116 where ".$curLib.".F0101.ABAN8=".$curLib.".F0116.ALAN8), '')) as ALADD1
	,trim(coalesce((select min(ALADD2) from ".$curLib.".F0116 where ".$curLib.".F0101.ABAN8=".$curLib.".F0116.ALAN8), '')) as ALADD2
	,trim(coalesce((select min(ALADD3) from ".$curLib.".F0116 where ".$curLib.".F0101.ABAN8=".$curLib.".F0116.ALAN8), '')) as ALADD3
	,trim(coalesce((select min(ALADDZ) from ".$curLib.".F0116 where ".$curLib.".F0101.ABAN8=".$curLib.".F0116.ALAN8), '')) as ALADDZ
	,trim(coalesce((select min(ALCTY1) from ".$curLib.".F0116 where ".$curLib.".F0101.ABAN8=".$curLib.".F0116.ALAN8), '')) as ALCTY1
	,trim(coalesce((select min(ALADDS) from ".$curLib.".F0116 where ".$curLib.".F0101.ABAN8=".$curLib.".F0116.ALAN8), '')) as ALADDS
	,trim(coalesce((select min(ALCTR) from ".$curLib.".F0116 where ".$curLib.".F0101.ABAN8=".$curLib.".F0116.ALAN8), '')) as ALCTR
	,trim(coalesce((select min(A5TRAR) from ".$curLib.".F0301 where ".$curLib.".F0101.ABAN8=".$curLib.".F0301.A5AN8), '')) as A5TRAR
	,trim(coalesce((select min(A5RYIN) from ".$curLib.".F0301 where ".$curLib.".F0101.ABAN8=".$curLib.".F0301.A5AN8), '')) as A5RYIN
	,trim(coalesce((select min(A5TXA1) from ".$curLib.".F0301 where ".$curLib.".F0101.ABAN8=".$curLib.".F0301.A5AN8), '')) as A5TXA1
	,trim(coalesce((select min(A5CRCD) from ".$curLib.".F0301 where ".$curLib.".F0101.ABAN8=".$curLib.".F0301.A5AN8), '')) as A5CRCD
	,trim(coalesce((select min(A5ARC) from ".$curLib.".F0301 where ".$curLib.".F0101.ABAN8=".$curLib.".F0301.A5AN8), '')) as A5ARC
	,trim(coalesce((select min(A5FRTH) from ".$curLib.".F0301 where ".$curLib.".F0101.ABAN8=".$curLib.".F0301.A5AN8), '')) as A5FRTH
	,coalesce((select min(A5CARS) from ".$curLib.".F0301 where ".$curLib.".F0101.ABAN8=".$curLib.".F0301.A5AN8), 0) as A5CARS
	,trim(coalesce((select min(A5HOLD) from ".$curLib.".F0301 where ".$curLib.".F0101.ABAN8=".$curLib.".F0301.A5AN8), '')) as A5HOLD
	,trim(coalesce((select min(A5CACT) from ".$curLib.".F0301 where ".$curLib.".F0101.ABAN8=".$curLib.".F0301.A5AN8), '')) as A5CACT
	,trim(coalesce((select min(A5INMG) from ".$curLib.".F0301 where ".$curLib.".F0101.ABAN8=".$curLib.".F0301.A5AN8), '')) as A5INMG
	,trim(coalesce((select min(WPPH1) from ".$curLib.".F0115 where ".$curLib.".F0101.ABAN8=".$curLib.".F0115.WPAN8 and ".$curLib.".F0115.WPIDLN=0), '')) as WPPH1
	,trim(coalesce((select min(WWMLNM CONCAT WWATTL) from ".$curLib.".F0111 where ".$curLib.".F0101.ABAN8=".$curLib.".F0111.WWAN8 and ".$curLib.".F0111.WWTYC='EM'), '')) as MAILEM 
	,trim(coalesce((select min(WWMLNM CONCAT WWATTL) from ".$curLib.".F0111 where ".$curLib.".F0101.ABAN8=".$curLib.".F0111.WWAN8 and ".$curLib.".F0111.WWTYC='EF'), '')) as MAILEF 
	,trim(coalesce((select min(WWMLNM CONCAT WWATTL) from ".$curLib.".F0111 where ".$curLib.".F0101.ABAN8=".$curLib.".F0111.WWAN8 and ".$curLib.".F0111.WWTYC='PE'), '')) as MAILPE 
	,trim(coalesce((select min(A5ROUT) from ".$curLib.".F0301 where ".$curLib.".F0101.ABAN8=".$curLib.".F0301.A5AN8), '')) as A5ROUT  
	FROM ".$curLib.".F0101  
) AS T 
"; 
if($whrClause!="") $query.=$whrClause;
if($ordbyClause!="") $query.=$ordbyClause;
if($limitClause!="") $query.=$limitClause;
$query.=" FOR FETCH ONLY";
 
 
$result=odbc_exec($conn,$query);
if(!$result) {
	echo '{"status":"ERROR","errmsg":'.json_encode(odbc_errormsg($conn)).'}';
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
			$row[$key] = utf8_encode(trim($row[$key]));
			$row[$key] = str_replace("§","@",trim($row[$key]));
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