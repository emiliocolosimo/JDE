<?php
include("config.inc.php");

header('Content-Type: application/json; charset=utf-8');

set_time_limit(120);

ini_set('display_errors', 1);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set("log_errors", 1);
ini_set("error_log", "/www/php80/htdocs/logs/getF4101JCRM/php-error.log");

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
$GRADO = "";
$CODCLI = "";
$CODART = "";
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

	if(isset($resArray['grado'])) $GRADO = $resArray['grado']; 
	if(isset($resArray['codcli'])) $CODCLI = $resArray['codcli'];
	if(isset($resArray['codart'])) $CODART = $resArray['codart'];
	
}

$time_start = microtime(true); 
//query:
$query = "SELECT 
IMITM, 
TRIM(IMLITM) AS IMLITM,
TRIM(IMDSC1) AS IMDSC1,
TRIM(IMDSCL) AS IMDSCL, 
TRIM(IMLNGP) AS IMLNGP, 
TRIM(IMUOM1) AS IMUOM1,
TRIM(IMUOM4) AS IMUOM4,
TRIM(IMSRTX) AS IMSRTX 
FROM ".$curLib.".F4101JCRM 
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
		
		//recupero IVCITM se filtro GRADO impostato:
		if($GRADO!="" && $CODCLI!="" && $CODART!="") {
			$query = "SELECT SUBSTR(JRGCOM94T.F0005.DRSPHD , 1 , 2) AS DRSPHD FROM JRGCOM94T.F0005 WHERE drrt='LG' AND DRSY='40' and trim(drky)=? FETCH FIRST ROW ONLY";
			$pstmt_g = odbc_prepare($conn,$query);
			if($pstmt_g) {
				$arrParams = array();	
				$arrParams[] = trim($GRADO);
			}
			$res_g = odbc_execute($pstmt_g,$arrParams);
			if(!$res_g) {
				echo '{"status":"ERROR","errmsg":'.json_encode(odbc_errormsg()).'}';
				exit;
			}
			$row_g = odbc_fetch_array($pstmt_g);
			if($row_g && $row_g["DRSPHD"]!="") { 
				$query = "SELECT trim(IVCITM) as IVCITM FROM ".$curLib.".F4104 WHERE IVAN8= ? and ivlitm=? and IVXRT=? FETCH FIRST ROW ONLY";
				$pstmt_g2 = odbc_prepare($conn,$query);
				if($pstmt_g2) {
					$arrParams = array();	
					$arrParams[] = trim($CODCLI);
					$arrParams[] = trim($CODART);
					$arrParams[] = trim($row_g["DRSPHD"]);
					 
				}
				$res_g2 = odbc_execute($pstmt_g2,$arrParams);
				if(!$res_g2) {
					echo '{"status":"ERROR","errmsg":'.json_encode(odbc_errormsg()).'}';
					exit;
				}
				$row_g2= odbc_fetch_array($pstmt_g2);
				if($row_g2 && $row_g2["IVCITM"]!="") {
					$row["IVCITM"] = $row_g2["IVCITM"];
				} else {
					$query = "SELECT trim(IVCITM) as IVCITM FROM ".$curLib.".F4104 WHERE IVAN8= ? and ivlitm=? and IVXRT='C' FETCH FIRST ROW ONLY";
					$pstmt_g3 = odbc_prepare($conn,$query);
					if($pstmt_g3) {
						$arrParams = array();	
						$arrParams[] = trim($CODCLI);
						$arrParams[] = trim($CODART); 
					}
					$res_g3 = odbc_execute($pstmt_g3,$arrParams);
					if(!$res_g3) {
						echo '{"status":"ERROR","errmsg":'.json_encode(odbc_errormsg()).'}';
						exit;
					}
					$row_g3= odbc_fetch_array($pstmt_g3);
					if($row_g3 && $row_g3["IVCITM"]!="") {
						$row["IVCITM"] = $row_g3["IVCITM"];
					}
				}
			}
			
		} 
		//recupero IVCITM se filtro GRADO impostato [f]
		
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