	<?php
include("config.inc.php");

header('Content-Type: application/json; charset=utf-8');

set_time_limit(120);

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set("log_errors", 1);
ini_set("error_log", "/www/php80/htdocs/logs/getItems/php-error-".date("Ym").".log");

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

//file_put_contents("/www/php80/htdocs/logs/getItems/".date("Ymd")."-".date("His").".txt",$postedBody);

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
		$limitClause = " LIMIT ".$resArray['limit']." OPTIMIZE FOR ".$resArray['limit']." ROWS ";
	}
	
}

$time_start = microtime(true); 
//query:
$query = "SELECT * FROM TABLE(
SELECT 
TRIM(LIITM) AS LIITM,    
TRIM(LIMCU) AS LIMCU,  
TRIM(LILOCN) AS LILOCN, 
TRIM(LILOTN) AS LILOTN, 
TRIM(LILOTS) AS LILOTS,  
TRIM(VARCHAR_FORMAT((DECIMAL(LIPQOH/100, 10, 2)),'9999999990.00')) AS LIPQOH, 
TRIM(VARCHAR_FORMAT((DECIMAL(LIHCOM/100, 10, 4)),'9999999990.00')) AS LIHCOM,
TRIM(VARCHAR_FORMAT((DECIMAL(LIPCOM/100, 10, 4)),'9999999990.00')) AS LIPCOM,
TRIM(LIURRF) AS LIURRF,   
COALESCE(F4101.IMITM, 0) AS IMITM,  
TRIM(COALESCE(F4101.IMLITM, '')) AS IMLITM,
TRIM(COALESCE(F4101.IMDSC1, '')) AS IMDSC1,
TRIM(COALESCE(F4101.IMSRP1, '')) AS IMSRP1,
TRIM(COALESCE(F4101.IMSRP2, '')) AS IMSRP2,
TRIM(COALESCE(F4101.IMSRP3, '')) AS IMSRP3, 
TRIM(COALESCE(F4101.IMUOM1, '')) AS IMUOM1,  
TRIM(COALESCE(F4101.IMUOM4, '')) AS IMUOM4,  
TRIM(COALESCE(F4101D.IMLNGP, '')) AS IMLNGP, 
TRIM(COALESCE(F4101D.IMDSC1, '')) AS IMDSCL, 
TRIM(COALESCE(F4108.IOLOT1, '')) AS IOLOT1,
TRIM(COALESCE(F4108.IOLOT2, '')) AS IOLOT2,
COALESCE(F4108.IOU1DJ, 0) AS IOU1DJ,
COALESCE(F4108.IOU2DJ, 0) AS IOU2DJ,
COALESCE(F4108.IOU3DJ, 0) AS IOU3DJ,
COALESCE(F4108.IOVEND, 0) AS IOVEND, 
TRIM(VARCHAR_FORMAT((DECIMAL(COALESCE(F4105.COUNCS, 0)/10000000, 10, 7)),'9999999990.0000000')) AS COUNCS, 
TRIM(COALESCE(F4105.COLEDG, '')) AS COLEDG,
COALESCE(F0101.ABAN8, 0) AS ABAN8, 
TRIM(COALESCE(F0101.ABAC20, '')) AS ABAC20,
TRIM(COALESCE(F0101.ABALPH, '')) AS ABALPH,
TRIM(VARCHAR_FORMAT((DECIMAL(LIPQOH/100, 10, 2)),'9999999990.00')) AS LIDISP,
CASE WHEN TRIM(F4101.IMUOM1) = 'NR' THEN TRIM(VARCHAR_FORMAT((DECIMAL((UMCONV*(LIPQOH/100)/10000000), 10, 2)),'9999999990.00')) ELSE TRIM(VARCHAR_FORMAT((DECIMAL(0 , 10, 2)),'9999999990.00')) END AS LIDISP_PESO, 
CASE WHEN TRIM(F4101.IMUOM1) = 'NR' THEN TRIM(VARCHAR_FORMAT((DECIMAL((UMCONV*(LIPQOH/100)/10000000), 10, 2)),'9999999990.00')) ELSE TRIM(VARCHAR_FORMAT((DECIMAL(0 , 10, 2)),'9999999990.00')) END AS LIPQOH_PESO, 
CASE WHEN TRIM(F4101.IMUOM1) = 'NR' THEN TRIM(VARCHAR_FORMAT((DECIMAL((UMCONV*(LIHCOM/100)/10000000), 10, 4)),'9999999990.00')) ELSE TRIM(VARCHAR_FORMAT((DECIMAL(0 , 10, 2)),'9999999990.00')) END AS LIHCOM_PESO,
CASE WHEN TRIM(F4101.IMUOM1) = 'NR' THEN TRIM(VARCHAR_FORMAT((DECIMAL((UMCONV*(LIPCOM/100)/10000000), 10, 4)),'9999999990.00')) ELSE TRIM(VARCHAR_FORMAT((DECIMAL(0 , 10, 2)),'9999999990.00')) END AS LIPCOM_PESO,
TRIM(COALESCE((SELECT 'Y' FROM ".$curLib.".f00164 WHERE C6WAPP = '*P4108' AND C6CKY2 = LIMCU || (DIGITS(LIITM)) || LILOTN), '')) AS NOTA
FROM ".$curLib.".F41021 as F41021   
LEFT JOIN ".$curLib.".F4101 as F4101 ON F4101.IMITM = F41021.LIITM 
LEFT JOIN ".$curLib.".F4101D as F4101D ON F4101D.IMITM = F41021.LIITM AND IMLNGP = 'E' 
LEFT JOIN ".$curLib.".F4105 as F4105 ON F4105.COITM = F41021.LIITM AND F4105.COLOTN = F41021.LILOTN AND F4105.COLOCN = F41021.LILOCN AND COLEDG = '06'  
LEFT JOIN ".$curLib.".F4108 as F4108 ON F4108.IOITM = F41021.LIITM AND F4108.IOLOTN = F41021.LILOTN AND F4108.IOMCU = F41021.LIMCU
LEFT JOIN ".$curLib.".F0101 as F0101 ON F4108.IOVEND = F0101.ABAN8
LEFT JOIN ".$curLib.".F41002 as F41002 ON  F41021.LIITM = F41002.UMITM and UMRUM='KG'
LEFT JOIN JRGCOM94T.F0005 as F0005 ON F0005.DRSY='41' AND F0005.DRRT='L ' AND SUBSTR(F0005.DRKY, 10, 1)=F41021.LILOTS 
WHERE 
LIPQOH <> 0 
AND TRIM(LIMCU) IN ('RGPM01','RGPM02') 
AND SUBSTR(F0005.DRSPHD, 1, 1)<>'H'
";
if($whrClause!="") $query.=$whrClause;
 
$query.=" 
UNION ALL

SELECT
TRIM(LIITM) AS LIITM,    
TRIM(LIMCU) AS LIMCU,  
TRIM(LILOCN) AS LILOCN, 
TRIM(LILOTN) AS LILOTN, 
TRIM(LILOTS) AS LILOTS,  
TRIM(VARCHAR_FORMAT((DECIMAL(LIPQOH/100, 10, 2)),'9999999990.00')) AS LIPQOH, 
TRIM(VARCHAR_FORMAT((DECIMAL(LIHCOM/100, 10, 4)),'9999999990.00')) AS LIHCOM,
TRIM(VARCHAR_FORMAT((DECIMAL(LIPCOM/100, 10, 4)),'9999999990.00')) AS LIPCOM,
TRIM(LIURRF) AS LIURRF,   
COALESCE(F4101.IMITM, 0) AS IMITM,  
TRIM(COALESCE(F4101.IMLITM, '')) AS IMLITM,
TRIM(COALESCE(F4101.IMDSC1, '')) AS IMDSC1,
TRIM(COALESCE(F4101.IMSRP1, '')) AS IMSRP1,
TRIM(COALESCE(F4101.IMSRP2, '')) AS IMSRP2,
TRIM(COALESCE(F4101.IMSRP3, '')) AS IMSRP3, 
TRIM(COALESCE(F4101.IMUOM1, '')) AS IMUOM1,  
TRIM(COALESCE(F4101.IMUOM4, '')) AS IMUOM4,  
TRIM(COALESCE(F4101D.IMLNGP, '')) AS IMLNGP, 
TRIM(COALESCE(F4101D.IMDSC1, '')) AS IMDSCL, 
TRIM(COALESCE(F4108.IOLOT1, '')) AS IOLOT1,
TRIM(COALESCE(F4108.IOLOT2, '')) AS IOLOT2,
COALESCE(F4108.IOU1DJ, 0) AS IOU1DJ,
COALESCE(F4108.IOU2DJ, 0) AS IOU2DJ,
COALESCE(F4108.IOU3DJ, 0) AS IOU3DJ,
COALESCE(F4108.IOVEND, 0) AS IOVEND, 
TRIM(VARCHAR_FORMAT((DECIMAL(COALESCE(F4105.COUNCS, 0)/10000000, 10, 7)),'9999999990.0000000')) AS COUNCS, 
TRIM(COALESCE(F4105.COLEDG, '')) AS COLEDG,
COALESCE(F0101.ABAN8, 0) AS ABAN8, 
TRIM(COALESCE(F0101.ABAC20, '')) AS ABAC20,
TRIM(COALESCE(F0101.ABALPH, '')) AS ABALPH,
TRIM(VARCHAR_FORMAT((DECIMAL(0         , 10, 4)),'9999999990.00')) AS LIDISP,
TRIM(VARCHAR_FORMAT((DECIMAL(0         , 10, 4)),'9999999990.00')) AS LIDISP_PESO,
CASE WHEN TRIM(F4101.IMUOM1) = 'NR' THEN TRIM(VARCHAR_FORMAT((DECIMAL((UMCONV*(LIPQOH/100)/10000000), 10, 2)),'9999999990.00')) ELSE TRIM(VARCHAR_FORMAT((DECIMAL(0 , 10, 2)),'9999999990.00')) END AS LIPQOH_PESO, 
CASE WHEN TRIM(F4101.IMUOM1) = 'NR' THEN TRIM(VARCHAR_FORMAT((DECIMAL((UMCONV*(LIHCOM/100)/10000000), 10, 4)),'9999999990.00')) ELSE TRIM(VARCHAR_FORMAT((DECIMAL(0 , 10, 2)),'9999999990.00')) END AS LIHCOM_PESO,
CASE WHEN TRIM(F4101.IMUOM1) = 'NR' THEN TRIM(VARCHAR_FORMAT((DECIMAL((UMCONV*(LIPCOM/100)/10000000), 10, 4)),'9999999990.00')) ELSE TRIM(VARCHAR_FORMAT((DECIMAL(0 , 10, 2)),'9999999990.00')) END AS LIPCOM_PESO,
TRIM(COALESCE((SELECT 'Y' FROM ".$curLib.".f00164 WHERE C6WAPP = '*P4108' AND C6CKY2 = LIMCU || (DIGITS(LIITM)) || LILOTN), '')) AS NOTA
FROM ".$curLib.".F41021 as F41021   
LEFT JOIN ".$curLib.".F4101 as F4101 ON F4101.IMITM = F41021.LIITM 
LEFT JOIN ".$curLib.".F4101D as F4101D ON F4101D.IMITM = F41021.LIITM AND IMLNGP = 'E' 
LEFT JOIN ".$curLib.".F4105 as F4105 ON F4105.COITM = F41021.LIITM AND F4105.COLOTN = F41021.LILOTN AND F4105.COLOCN = F41021.LILOCN AND COLEDG = '06'  
LEFT JOIN ".$curLib.".F4108 as F4108 ON F4108.IOITM = F41021.LIITM AND F4108.IOLOTN = F41021.LILOTN AND F4108.IOMCU = F41021.LIMCU
LEFT JOIN ".$curLib.".F0101 as F0101 ON F4108.IOVEND = F0101.ABAN8

LEFT JOIN ".$curLib.".F41002 as F41002 ON  F41021.LIITM = F41002.UMITM and UMRUM='KG'
LEFT JOIN JRGCOM94T.F0005 as F0005 ON F0005.DRSY='41' AND F0005.DRRT='L ' AND SUBSTR(F0005.DRKY, 10, 1)=F41021.LILOTS 
WHERE 
LIPQOH <> 0 
AND TRIM(LIMCU) IN ('RGPM01','RGPM02') 
AND SUBSTR(F0005.DRSPHD, 1, 1)='H'   
";
if($whrClause!="") $query.=$whrClause;

$query.=") T  ";
 
  
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

// echo '['; 

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

//echo ']';

$time_end = microtime(true);
$execution_time = ($time_end - $time_start);
//echo '<b>Ciclo:</b> '.$execution_time.' s';

odbc_close($conn);