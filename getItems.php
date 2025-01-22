<?php
include("config.inc.php");

header('Content-Type: application/json; charset=utf-8');

set_time_limit(120);

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set("log_errors", 1);
ini_set("error_log", "/www/php80/htdocs/logs/getItems/php-error.log");

$k = '';
if(isset($_REQUEST['k'])) $k = $_REQUEST["k"];
if($k!="sJHsdwvIFTyhDuGtZoOfevsgG1A1H2s6") { 
	exit;
}
 

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
	 
	if(isset($resArray['filters'])) {
		 
		$filterMode = "";  
		 
		if($whrClause=="") $whrClause = " WHERE ";
		$whrClause .= " (";
		
		$arrFilters = $resArray['filters'];
		for($i=0;$i<count($arrFilters);$i++) {
			$field = $arrFilters[$i]['field'];
			$type = $arrFilters[$i]['type'];
			$value = $arrFilters[$i]['value'];
			 
			if($type=="eq") $whrClause .= " ".$filterMode." (".$field." = '".$value."') ";
			if($type=="neq") $whrClause .= " ".$filterMode." (".$field." <> '".$value."') ";
			if($type=="lt") $whrClause .= " ".$filterMode." (".$field." < '".$value."') ";
			if($type=="gt") $whrClause .= " ".$filterMode." (".$field." > '".$value."') ";
			if($type=="like") $whrClause .= " ".$filterMode." (upper(".$field.") LIKE '%".strtoupper($value)."%') ";
			 
			$filterMode = "AND"; 
			if(isset($resArray['filter_mode'])) $filterMode = $resArray['filter_mode'];
			  
		}
		$whrClause .= " ) ";
	}
	 
	if(isset($resArray['filters2'])) {
		 
		$filterMode = ""; 
		 
		if($whrClause=="") $whrClause = " WHERE ";
		$whrClause .= " AND (";
		
		$arrFilters = $resArray['filters2'];
		for($i=0;$i<count($arrFilters);$i++) {
			$field = $arrFilters[$i]['field'];
			$type = $arrFilters[$i]['type'];
			$value = $arrFilters[$i]['value'];
			 
			if($type=="eq") $whrClause .= " ".$filterMode." (".$field." = '".$value."') ";
			if($type=="neq") $whrClause .= " ".$filterMode." (".$field." <> '".$value."') ";
			if($type=="lt") $whrClause .= " ".$filterMode." (".$field." < '".$value."') ";
			if($type=="gt") $whrClause .= " ".$filterMode." (".$field." > '".$value."') ";
			if($type=="like") $whrClause .= " ".$filterMode." (upper(".$field.") LIKE '%".strtoupper($value)."%') ";
			 
			$filterMode = "AND"; 
			if(isset($resArray['filter_mode2'])) $filterMode = $resArray['filter_mode2'];
			  
		}
		$whrClause .= " ) ";
	}
	/*
		[
			{
				"field": "LIURRF",
				"dir": "ASC"
			},
			{
				"field": "IOU2DJ",
				"dir": "DESC"
			}
		]	
	*/ 
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
$query = "SELECT 
LIITM, 
TRIM(LIMCU) AS LIMCU,
TRIM(LILOCN) AS LILOCN,
TRIM(LILOTN) AS LILOTN,
TRIM(LILOTS) AS LILOTS, 
TRIM(VARCHAR_FORMAT((DECIMAL(LIPQOH/100, 10, 4)),'9999999990.00')) AS LIPQOH, 
TRIM(VARCHAR_FORMAT((DECIMAL(LIHCOM/100, 10, 4)),'9999999990.00')) AS LIHCOM,
TRIM(VARCHAR_FORMAT((DECIMAL(LIPCOM/100, 10, 4)),'9999999990.00')) AS LIPCOM,
TRIM(LIURRF) AS LIURRF,
IMITM ,
TRIM(IMLITM) AS IMLITM,
TRIM(IMDSC1) AS IMDSC1,
TRIM(IOLOT1) AS IOLOT1,
TRIM(IOLOT2) AS IOLOT2,
IOU1DJ,
IOU2DJ, 
TRIM(ABAC20) AS ABAC20, 
TRIM(VARCHAR_FORMAT((DECIMAL(COUNCS/10000000, 10, 4)),'9999999990.00')) AS COUNCS, 
IOU3DJ,
TRIM(IMDSCL) AS IMDSCL,
TRIM(IMSRP1) AS IMSRP1, 
TRIM(IMSRP2) AS IMSRP2, 
TRIM(IMSRP3) AS IMSRP3, 
TRIM(IMUOM1) AS IMUOM1,
TRIM(IMUOM4) AS IMUOM4
FROM JRGDTA94C.F41021JCRM 
"; 
if($whrClause!="") $query.=$whrClause;
if($ordbyClause!="") $query.=$ordbyClause;
if($limitClause!="") $query.=$limitClause;
$query.=" FOR FETCH ONLY";
    

$result=odbc_exec($conn,$query);
if(!$result) {
	echo '{"status":"ERROR","errmsg":"'.json_encode(odbc_errormsg()).'"}';
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