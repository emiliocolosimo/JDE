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

,trim(coalesce((select listagg(A3DS80,chr(10)) from JRGDTA94C.F01093 where JRGDTA94C.F0101.ABAN8=JRGDTA94C.F01093.A3AN8 and A3TYDT='NA'), '')) as F01093_1_A3DS80
,trim(coalesce((select listagg(A3DS80,chr(10)) from JRGDTA94C.F01093 where JRGDTA94C.F0101.ABAN8=JRGDTA94C.F01093.A3AN8 and A3TYDT='NP'), '')) as F01093_2_A3DS80
,trim(coalesce((select listagg(CYWTXT,chr(10)) from JRGDTA94C.F00163 left join JRGDTA94C.F0016 on JRGDTA94C.F0016.CYSERK=JRGDTA94C.F00163.C5SERK where JRGDTA94C.F0101.ABAN8=SUBSTRING(C5CKEY,1,8) and C5WAPP='*ADDNOTE'), '')) as F0016_CYWTXT
*/

$time_start = microtime(true); 
//query:
$query = "
SELECT JRGDTA94C.F0101.*, 
trim(coalesce((select min(WWMLNM) from JRGDTA94C.F0111 where JRGDTA94C.F0101.ABAN8=JRGDTA94C.F0111.WWAN8 and JRGDTA94C.F0111.WWIDLN=0), '')) as WWMLNM
,trim(coalesce((select min(A2RMK) from JRGDTA94C.F01092 where JRGDTA94C.F0101.ABAN8=JRGDTA94C.F01092.A2AN8 and A2TYDT='CF'), '')) as F01092_1_A2RMK
	,trim(coalesce((select A2KY from JRGDTA94C.F01092 where JRGDTA94C.F0101.ABAN8=JRGDTA94C.F01092.A2AN8 and A2TYDT='CV' order by RRN(JRGDTA94C.F01092) DESC FETCH FIRST ROW ONLY), '')) as F01092_2_A2KY
	,coalesce((select A2EFT from JRGDTA94C.F01092 where JRGDTA94C.F0101.ABAN8=JRGDTA94C.F01092.A2AN8 and A2TYDT='CV' order by RRN(JRGDTA94C.F01092) DESC FETCH FIRST ROW ONLY), 0) as F01092_2_A2EFT
	,trim(coalesce((select A2RMK from JRGDTA94C.F01092 where JRGDTA94C.F0101.ABAN8=JRGDTA94C.F01092.A2AN8 and A2TYDT='CV' order by RRN(JRGDTA94C.F01092) DESC FETCH FIRST ROW ONLY), '')) as F01092_2_A2RMK
,trim(coalesce((select min(A2KY) from JRGDTA94C.F01092 where JRGDTA94C.F0101.ABAN8=JRGDTA94C.F01092.A2AN8 and A2TYDT='FE'), '')) as F01092_3_A2KY
,trim(coalesce((select min(A2RMK) from JRGDTA94C.F01092 where JRGDTA94C.F0101.ABAN8=JRGDTA94C.F01092.A2AN8 and A2TYDT='LI'), ''))  as F01092_4_A2RMK
,trim(coalesce((select min(ALADD1) from JRGDTA94C.F0116 where JRGDTA94C.F0101.ABAN8=JRGDTA94C.F0116.ALAN8), '')) as ALADD1
,trim(coalesce((select min(ALADD2) from JRGDTA94C.F0116 where JRGDTA94C.F0101.ABAN8=JRGDTA94C.F0116.ALAN8), '')) as ALADD2
,trim(coalesce((select min(ALADD3) from JRGDTA94C.F0116 where JRGDTA94C.F0101.ABAN8=JRGDTA94C.F0116.ALAN8), '')) as ALADD3
,trim(coalesce((select min(ALADDZ) from JRGDTA94C.F0116 where JRGDTA94C.F0101.ABAN8=JRGDTA94C.F0116.ALAN8), '')) as ALADDZ
,trim(coalesce((select min(ALCTY1) from JRGDTA94C.F0116 where JRGDTA94C.F0101.ABAN8=JRGDTA94C.F0116.ALAN8), '')) as ALCTY1
,trim(coalesce((select min(ALADDS) from JRGDTA94C.F0116 where JRGDTA94C.F0101.ABAN8=JRGDTA94C.F0116.ALAN8), '')) as ALADDS
,trim(coalesce((select min(ALCTR) from JRGDTA94C.F0116 where JRGDTA94C.F0101.ABAN8=JRGDTA94C.F0116.ALAN8), '')) as ALCTR
,trim(coalesce((select min(A5TRAR) from JRGDTA94C.F0301 where JRGDTA94C.F0101.ABAN8=JRGDTA94C.F0301.A5AN8), '')) as A5TRAR
,trim(coalesce((select min(A5RYIN) from JRGDTA94C.F0301 where JRGDTA94C.F0101.ABAN8=JRGDTA94C.F0301.A5AN8), '')) as A5RYIN
,trim(coalesce((select min(A5TXA1) from JRGDTA94C.F0301 where JRGDTA94C.F0101.ABAN8=JRGDTA94C.F0301.A5AN8), '')) as A5TXA1
,trim(coalesce((select min(A5CRCD) from JRGDTA94C.F0301 where JRGDTA94C.F0101.ABAN8=JRGDTA94C.F0301.A5AN8), '')) as A5CRCD
,trim(coalesce((select min(A5ARC) from JRGDTA94C.F0301 where JRGDTA94C.F0101.ABAN8=JRGDTA94C.F0301.A5AN8), '')) as A5ARC
,trim(coalesce((select min(A5FRTH) from JRGDTA94C.F0301 where JRGDTA94C.F0101.ABAN8=JRGDTA94C.F0301.A5AN8), '')) as A5FRTH
,coalesce((select min(A5CARS) from JRGDTA94C.F0301 where JRGDTA94C.F0101.ABAN8=JRGDTA94C.F0301.A5AN8), 0) as A5CARS
,trim(coalesce((select min(A5HOLD) from JRGDTA94C.F0301 where JRGDTA94C.F0101.ABAN8=JRGDTA94C.F0301.A5AN8), '')) as A5HOLD
,trim(coalesce((select min(A5CACT) from JRGDTA94C.F0301 where JRGDTA94C.F0101.ABAN8=JRGDTA94C.F0301.A5AN8), '')) as A5CACT
,trim(coalesce((select min(A5INMG) from JRGDTA94C.F0301 where JRGDTA94C.F0101.ABAN8=JRGDTA94C.F0301.A5AN8), '')) as A5INMG
,trim(coalesce((select min(WPPH1) from JRGDTA94C.F0115 where JRGDTA94C.F0101.ABAN8=JRGDTA94C.F0115.WPAN8 and JRGDTA94C.F0115.WPIDLN=0), '')) as WPPH1
,trim(coalesce((select min(WWMLNM CONCAT WWATTL) from JRGDTA94C.F0111 where JRGDTA94C.F0101.ABAN8=JRGDTA94C.F0111.WWAN8 and JRGDTA94C.F0111.WWTYC='EM'), '')) as MAILEM 
,trim(coalesce((select min(WWMLNM CONCAT WWATTL) from JRGDTA94C.F0111 where JRGDTA94C.F0101.ABAN8=JRGDTA94C.F0111.WWAN8 and JRGDTA94C.F0111.WWTYC='EF'), '')) as MAILEF 
,trim(coalesce((select min(WWMLNM CONCAT WWATTL) from JRGDTA94C.F0111 where JRGDTA94C.F0101.ABAN8=JRGDTA94C.F0111.WWAN8 and JRGDTA94C.F0111.WWTYC='PE'), '')) as MAILPE 
,trim(coalesce((select min(A5ROUT) from JRGDTA94C.F0301 where JRGDTA94C.F0101.ABAN8=JRGDTA94C.F0301.A5AN8), '')) as A5ROUT  
FROM JRGDTA94C.F0101  
"; 
if($whrClause!="") $query.=$whrClause;
if($ordbyClause!="") $query.=$ordbyClause;
if($limitClause!="") $query.=$limitClause;
$query.=" FOR FETCH ONLY";
 
/*
Ciao Mattia,

per l'API http://172.30.155.170:10099/getF0101.php?k=sJHsdwvIFTyhDuGtZoOfevsgG1A1H2s6

mi servirebbero i campi mail e telefono (non so quali siano i campi jde). Mi servirebbe anche la descrizione della resa a mezzo (attualmente arriva il codice A5ROUT da F0301), forse per questo si potrebbe creare un'altra api.

Grazie e buona giornata

i numeri di telefono sono nel file F0115:

chiave:
WPAN8 (num 8) = codice cliente
WPIDLN (num 5) = 0
dati:
WPPH1  (char 40) = numero di telefono
WPPHTP (char 4) = tipo (blank = telefono , FAX = fax)


l'indirizzo email è nel file F0111:

chiave:
WWAN8 (num 8) = codice cliente 
WWTYC (char 3) = tipo ("EM " = email principale, "EF" = email per invio fatture, "PE" email PEC)
dati:
WWMLNM (char 40) = email
WWATTL (char 40) = email 2a parte
*/ 
 

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