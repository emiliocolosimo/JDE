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
$query = "SELECT 
TRIM(IMLITM) AS IMLITM,    
TRIM(LILOTN) AS LILOTN, 
TRIM(LIURRF) AS LIURRF,   
TRIM(VARCHAR_FORMAT((DECIMAL(COALESCE(F4105.COUNCS, 0)/10000000, 10, 7)),'9999999990.0000000')) AS COUNCS,
TRIM(VARCHAR_FORMAT((DECIMAL(COALESCE(F41166.ICQ4UD, 0)/10000, 10, 4)),'9999999990.0000'))  AS ICQ4UD,
TRIM(COALESCE(F41166.ICR1UD, 'NON PRESENTE IN JDE')) AS ICR1UD,
TRIM(VARCHAR_FORMAT((DECIMAL(SUM(LIPQOH-LIHCOM)/100, 10, 2)),'9999999990.00')) AS LIPQOH
FROM ".$curLib.".F41021JD as F41021JD   
LEFT JOIN ".$curLib.".F4105 as F4105 ON F4105.COITM = F41021JD.LIITM AND F4105.COLOTN = F41021JD.LILOTN AND F4105.COLOCN = F41021JD.LILOCN AND COLEDG = '06'  
LEFT JOIN ".$curLib.".F41166 AS F41166 ON F41166.ICLITM=IMLITM AND F41166.ICUDDT='QA'
WHERE LIPQOH <> 0 
";

if($whrClause!="") $query.=$whrClause;

$query .= " GROUP BY IMLITM , LILOTN , LIURRF , COUNCS , ICQ4UD , ICR1UD ";

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
//echo '<b>Query:</b> '.$query.' s';

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