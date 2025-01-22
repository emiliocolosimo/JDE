<?php
include("/www/php80/htdocs/config.inc.php");

header('Content-Type: application/json; charset=utf-8');

set_time_limit(120);

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set("log_errors", 1);
ini_set("error_log", "/www/php80/htdocs/logs/getappmail/php-error.log");

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

$time_start = microtime(true); 
//query:
$query = "SELECT 
TRIM(ABAN8) as ABAN8, 
TRIM(ABALPH) as ABALPH,
TRIM(ABAT1) as ABAT1,
trim(coalesce((select min(WWMLNM) from ".$curLib.".F0111 where ".$curLib.".F0101.ABAN8=".$curLib.".F0111.WWAN8 and ".$curLib.".F0111.WWIDLN=0), '')) as WWMLNM,
trim(coalesce((select min(ALADD1) from ".$curLib.".F0116 where ".$curLib.".F0101.ABAN8=".$curLib.".F0116.ALAN8), '')) as ALADD1,
trim(coalesce((select min(ALADD2) from ".$curLib.".F0116 where ".$curLib.".F0101.ABAN8=".$curLib.".F0116.ALAN8), '')) as ALADD2,
trim(coalesce((select min(ALADD3) from ".$curLib.".F0116 where ".$curLib.".F0101.ABAN8=".$curLib.".F0116.ALAN8), '')) as ALADD3,
trim(coalesce((select min(ALADDZ) from ".$curLib.".F0116 where ".$curLib.".F0101.ABAN8=".$curLib.".F0116.ALAN8), '')) as ALADDZ,
trim(coalesce((select min(ALCTY1) from ".$curLib.".F0116 where ".$curLib.".F0101.ABAN8=".$curLib.".F0116.ALAN8), '')) as ALCTY1,
trim(coalesce((select min(ALADDS) from ".$curLib.".F0116 where ".$curLib.".F0101.ABAN8=".$curLib.".F0116.ALAN8), '')) as ALADDS,
trim(coalesce((select min(ALCTR) from ".$curLib.".F0116 where ".$curLib.".F0101.ABAN8=".$curLib.".F0116.ALAN8), '')) as ALCTR,
trim(coalesce((select min(WWMLNM CONCAT WWATTL) from ".$curLib.".F0111 where ".$curLib.".F0101.ABAN8=".$curLib.".F0111.WWAN8 and ".$curLib.".F0111.WWTYC='EM'), '')) as MAILEM,
trim(coalesce((select min(WWMLNM CONCAT WWATTL) from ".$curLib.".F0111 where ".$curLib.".F0101.ABAN8=".$curLib.".F0111.WWAN8 and ".$curLib.".F0111.WWTYC='EF'), '')) as MAILEF, 
trim(coalesce((select min(WWMLNM CONCAT WWATTL) from ".$curLib.".F0111 where ".$curLib.".F0101.ABAN8=".$curLib.".F0111.WWAN8 and ".$curLib.".F0111.WWTYC='PE'), '')) as MAILPE,
coalesce((select SUM(SDAEXP/100) from ".$curLib.".F564211 where ".$curLib.".F564211.SDAN8 = ".$curLib.".F0101.ABAN8 AND SDDCTO NOT IN ('OF' , 'SQ')), 0) as FATTURATO, 
coalesce((select SUM(SDAEXP/100) from ".$curLib.".F564211 where ".$curLib.".F564211.SDAN8 = ".$curLib.".F0101.ABAN8 AND SDDCTO IN ('OF' , 'SQ')), 0) as OFFERTO,
trim(coalesce((SELECT max(A2KY)
FROM ".$curLib.".F01092 WHERE ".$curLib.".F0101.ABAN8 = ".$curLib.".F01092.A2AN8 AND A2TYDT = 'CV' AND rrn(".$curLib.".F01092) = (SELECT MAX(rrn(".$curLib.".F01092)) FROM ".$curLib.".F01092 WHERE ".$curLib.".F0101.ABAN8 = ".$curLib.".F01092.A2AN8 
AND A2TYDT = 'CV')), '')) AS CONDIZIONI 
FROM ".$curLib.".F0101 
WHERE EXISTS(SELECT 1 FROM ".$curLib.".F564211 WHERE SDAN8 =  ABAN8)
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
echo '[';
$time_start = microtime(true); 
$r = 0;
while($row = odbc_fetch_array($result)){
		
		foreach(array_keys($row) as $key)
		{
			$row[$key] = utf8_encode(trim($row[$key]));
			$row[$key] = str_replace("§","@",trim($row[$key]));
			if($key=="FATTURATO" && $row[$key]==0) $row[$key] = "0";
			if($key=="OFFERTO" && $row[$key]==0) $row[$key] = "0";			
		}
		
		$jsonCustomer = $row;
  
		$query = "SELECT T.TIPO, T.ANNO, T.MESE, T.IMPORTO  
		FROM TABLE(
			SELECT 
			'ORDINATO' AS TIPO,
			digits(ondtey) AS ANNO,
			digits(ondtem) AS MESE, 		
			sum(sdaexp/100) AS IMPORTO 
			FROM ".$curLib.".f564211  
			left join ".$curLib.".f00365 on sddrqj=ondtej
			WHERE SDDCTO NOT IN ('OF' , 'SQ') 
			AND SDAN8 =  '".$row["ABAN8"]."'  
			GROUP BY digits(ondtey), digits(ondtem) 
			
			UNION 
			
			SELECT 
			'OFFERTO' AS TIPO,
			digits(ondtey) AS ANNO,
			digits(ondtem) AS MESE, 		
			sum(sdaexp/100) AS IMPORTO 
			FROM ".$curLib.".f564211  
			left join ".$curLib.".f00365 on sddrqj=ondtej
			WHERE SDDCTO IN ('OF' , 'SQ') 
			AND SDAN8 =  '".$row["ABAN8"]."'  
			GROUP BY digits(ondtey), digits(ondtem) 
		) AS T 
		ORDER BY T.ANNO DESC, T.MESE DESC
		";
		 
		$result2=odbc_exec($conn,$query);
		if(!$result2) {
			echo '{"status":"ERROR","errmsg":'.json_encode(odbc_errormsg($conn)).'}';
			exit;
		} 
		$jsonDati = array();
		$r2 = 0;
		while($row2 = odbc_fetch_array($result2)){
			foreach(array_keys($row2) as $key)
			{
				$row2[$key] = utf8_encode(trim($row2[$key]));
			}
			$jsonDati[] = $row2; 
		} 
		
		$jsonCustomer['DATI'] = $jsonDati;
		
		if($r>0) echo ',';
		echo json_encode($jsonCustomer);
		$r++;			
}

echo ']';
exit;


$query = "SELECT * FROM TABLE(
	SELECT T1.* 
	FROM TABLE(


SELECT 
'OFFERTO' as TIPO , 
TRIM(ABAN8) as ABAN8 , 
TRIM(ABALPH) AS ABALPH
,TRIM(ABAT1) AS ABAT1
,trim(coalesce((select min(WWMLNM) from ".$curLib.".F0111 where ".$curLib.".F0101.ABAN8=".$curLib.".F0111.WWAN8 and ".$curLib.".F0111.WWIDLN=0), '')) as WWMLNM
,trim(coalesce((select min(ALADD1) from ".$curLib.".F0116 where ".$curLib.".F0101.ABAN8=".$curLib.".F0116.ALAN8), '')) as ALADD1
,trim(coalesce((select min(ALADD2) from ".$curLib.".F0116 where ".$curLib.".F0101.ABAN8=".$curLib.".F0116.ALAN8), '')) as ALADD2
,trim(coalesce((select min(ALADD3) from ".$curLib.".F0116 where ".$curLib.".F0101.ABAN8=".$curLib.".F0116.ALAN8), '')) as ALADD3
,trim(coalesce((select min(ALADDZ) from ".$curLib.".F0116 where ".$curLib.".F0101.ABAN8=".$curLib.".F0116.ALAN8), '')) as ALADDZ
,trim(coalesce((select min(ALCTY1) from ".$curLib.".F0116 where ".$curLib.".F0101.ABAN8=".$curLib.".F0116.ALAN8), '')) as ALCTY1
,trim(coalesce((select min(ALADDS) from ".$curLib.".F0116 where ".$curLib.".F0101.ABAN8=".$curLib.".F0116.ALAN8), '')) as ALADDS
,trim(coalesce((select min(ALCTR) from ".$curLib.".F0116 where ".$curLib.".F0101.ABAN8=".$curLib.".F0116.ALAN8), '')) as ALCTR
,trim(coalesce((select min(WWMLNM CONCAT WWATTL) from ".$curLib.".F0111 where ".$curLib.".F0101.ABAN8=".$curLib.".F0111.WWAN8 and ".$curLib.".F0111.WWTYC='EM'), '')) as MAILEM 
,trim(coalesce((select min(WWMLNM CONCAT WWATTL) from ".$curLib.".F0111 where ".$curLib.".F0101.ABAN8=".$curLib.".F0111.WWAN8 and ".$curLib.".F0111.WWTYC='EF'), '')) as MAILEF 
,trim(coalesce((select min(WWMLNM CONCAT WWATTL) from ".$curLib.".F0111 where ".$curLib.".F0101.ABAN8=".$curLib.".F0111.WWAN8 and ".$curLib.".F0111.WWTYC='PE'), '')) as MAILPE 
,coalesce((select SUM(SDAEXP/100) from ".$curLib.".F564211 where ".$curLib.".F0101.ABAN8=".$curLib.".F564211.SDAN8 AND SDDCTO NOT IN ('OF' , 'SQ')), 0) as FATTURATO 
,coalesce((select SUM(SDAEXP/100) from ".$curLib.".F564211 where ".$curLib.".F0101.ABAN8=".$curLib.".F564211.SDAN8 AND SDDCTO IN ('OF' , 'SQ')), 0) as OFFERTO 
,trim(coalesce((SELECT max(A2KY)
FROM ".$curLib.".F01092 WHERE ".$curLib.".F0101.ABAN8 = ".$curLib.".F01092.A2AN8 AND A2TYDT = 'CV' AND rrn(".$curLib.".F01092) = (SELECT MAX(rrn(".$curLib.".F01092)) FROM ".$curLib.".F01092 WHERE ".$curLib.".F0101.ABAN8 = ".$curLib.".F01092.A2AN8 
AND A2TYDT = 'CV')), '')) AS CONDIZIONI,
digits(ondtey)||'/'||digits(ondtem) AS ANNO_MESE , 
sum(sdaexp/100) AS IMPORTO 
FROM ".$curLib.".f564211 
LEFT JOIN ".$curLib.".F0101  ON ABAN8=SDAN8
LEFT JOIN ".$curLib.".F0116 ON ALAN8=SDAN8
left join ".$curLib.".f00365 on sddrqj=ondtej
WHERE SDDCTO  IN ('OF' , 'SQ')      


GROUP BY
ABan8 , 
ABALPH,
ABAT1
,trim(coalesce((select min(WWMLNM) from ".$curLib.".F0111 where ".$curLib.".F0101.ABAN8=".$curLib.".F0111.WWAN8 and ".$curLib.".F0111.WWIDLN=0), ''))
,ALADD1
,ALADD2
,ALADD3
,ALADDZ
,ALCTY1
,ALADDS
,ALCTR
,trim(coalesce((select min(WWMLNM CONCAT WWATTL) from ".$curLib.".F0111 where ".$curLib.".F0101.ABAN8=".$curLib.".F0111.WWAN8 and ".$curLib.".F0111.WWTYC='EM'), ''))
,trim(coalesce((select min(WWMLNM CONCAT WWATTL) from ".$curLib.".F0111 where ".$curLib.".F0101.ABAN8=".$curLib.".F0111.WWAN8 and ".$curLib.".F0111.WWTYC='EF'), ''))
,trim(coalesce((select min(WWMLNM CONCAT WWATTL) from ".$curLib.".F0111 where ".$curLib.".F0101.ABAN8=".$curLib.".F0111.WWAN8 and ".$curLib.".F0111.WWTYC='PE'), '')) 
,coalesce((select SUM(SDAEXP/100) from ".$curLib.".F564211 where ".$curLib.".F0101.ABAN8=".$curLib.".F564211.SDAN8 AND SDDCTO NOT IN ('OF' , 'SQ')), 0)
,coalesce((select SUM(SDAEXP/100) from ".$curLib.".F564211 where ".$curLib.".F0101.ABAN8=".$curLib.".F564211.SDAN8 AND SDDCTO IN ('OF' , 'SQ')), 0)
,trim(coalesce((SELECT max(A2KY)
FROM ".$curLib.".F01092 WHERE ".$curLib.".F0101.ABAN8 = ".$curLib.".F01092.A2AN8 AND A2TYDT = 'CV' AND rrn(".$curLib.".F01092) = (SELECT MAX(rrn(".$curLib.".F01092)) FROM ".$curLib.".F01092 WHERE ".$curLib.".F0101.ABAN8 = ".$curLib.".F01092.A2AN8 
AND A2TYDT = 'CV')), '')),
digits(ondtey)||'/'||digits(ondtem) 




) T1
";

if ($whrClause != "")
	$query .= $whrClause;

$query .= "
UNION ALL 
SELECT T2.* 
		FROM TABLE(
SELECT 
'ORDINATO' as TIPO , 
TRIM(ABan8) as ABAN8 , 
TRIM(ABALPH) AS ABALPH
,TRIM(ABAT1) AS ABAT1
,trim(coalesce((select min(WWMLNM) from ".$curLib.".F0111 where ".$curLib.".F0101.ABAN8=".$curLib.".F0111.WWAN8 and ".$curLib.".F0111.WWIDLN=0), '')) as WWMLNM
,trim(coalesce((select min(ALADD1) from ".$curLib.".F0116 where ".$curLib.".F0101.ABAN8=".$curLib.".F0116.ALAN8), '')) as ALADD1
,trim(coalesce((select min(ALADD2) from ".$curLib.".F0116 where ".$curLib.".F0101.ABAN8=".$curLib.".F0116.ALAN8), '')) as ALADD2
,trim(coalesce((select min(ALADD3) from ".$curLib.".F0116 where ".$curLib.".F0101.ABAN8=".$curLib.".F0116.ALAN8), '')) as ALADD3
,trim(coalesce((select min(ALADDZ) from ".$curLib.".F0116 where ".$curLib.".F0101.ABAN8=".$curLib.".F0116.ALAN8), '')) as ALADDZ
,trim(coalesce((select min(ALCTY1) from ".$curLib.".F0116 where ".$curLib.".F0101.ABAN8=".$curLib.".F0116.ALAN8), '')) as ALCTY1
,trim(coalesce((select min(ALADDS) from ".$curLib.".F0116 where ".$curLib.".F0101.ABAN8=".$curLib.".F0116.ALAN8), '')) as ALADDS
,trim(coalesce((select min(ALCTR) from ".$curLib.".F0116 where ".$curLib.".F0101.ABAN8=".$curLib.".F0116.ALAN8), '')) as ALCTR
,trim(coalesce((select min(WWMLNM CONCAT WWATTL) from ".$curLib.".F0111 where ".$curLib.".F0101.ABAN8=".$curLib.".F0111.WWAN8 and ".$curLib.".F0111.WWTYC='EM'), '')) as MAILEM 
,trim(coalesce((select min(WWMLNM CONCAT WWATTL) from ".$curLib.".F0111 where ".$curLib.".F0101.ABAN8=".$curLib.".F0111.WWAN8 and ".$curLib.".F0111.WWTYC='EF'), '')) as MAILEF 
,trim(coalesce((select min(WWMLNM CONCAT WWATTL) from ".$curLib.".F0111 where ".$curLib.".F0101.ABAN8=".$curLib.".F0111.WWAN8 and ".$curLib.".F0111.WWTYC='PE'), '')) as MAILPE 
,coalesce((select SUM(SDAEXP/100) from ".$curLib.".F564211 where ".$curLib.".F0101.ABAN8=".$curLib.".F564211.SDAN8 AND SDDCTO NOT IN ('OF' , 'SQ')), 0) as FATTURATO 
,coalesce((select SUM(SDAEXP/100) from ".$curLib.".F564211 where ".$curLib.".F0101.ABAN8=".$curLib.".F564211.SDAN8 AND SDDCTO IN ('OF' , 'SQ')), 0) as OFFERTO 
,trim(coalesce((SELECT max(A2KY)
FROM ".$curLib.".F01092 WHERE ".$curLib.".F0101.ABAN8 = ".$curLib.".F01092.A2AN8 AND A2TYDT = 'CV' AND rrn(".$curLib.".F01092) = (SELECT MAX(rrn(".$curLib.".F01092)) FROM ".$curLib.".F01092 WHERE ".$curLib.".F0101.ABAN8 = ".$curLib.".F01092.A2AN8 
AND A2TYDT = 'CV')), '')) AS CONDIZIONI,
digits(ondtey)||'/'||digits(ondtem) AS ANNO_MESE , 
sum(sdaexp/100) AS IMPORTO 
FROM ".$curLib.".f564211 
LEFT JOIN ".$curLib.".F0101  ON ABAN8=SDAN8
LEFT JOIN ".$curLib.".F0116 ON ALAN8=SDAN8
left join ".$curLib.".f00365 on sddrqj=ondtej
WHERE SDDCTO NOT IN ('OF' , 'SQ')           
 
GROUP BY
ALADDS,
ABan8 , 
ABALPH,
ABAT1
,trim(coalesce((select min(WWMLNM) from ".$curLib.".F0111 where ".$curLib.".F0101.ABAN8=".$curLib.".F0111.WWAN8 and ".$curLib.".F0111.WWIDLN=0), ''))
,ALADD1
,ALADD2
,ALADD3
,ALADDZ
,ALCTY1
,ALADDS
,ALCTR
,trim(coalesce((select min(WWMLNM CONCAT WWATTL) from ".$curLib.".F0111 where ".$curLib.".F0101.ABAN8=".$curLib.".F0111.WWAN8 and ".$curLib.".F0111.WWTYC='EM'), ''))
,trim(coalesce((select min(WWMLNM CONCAT WWATTL) from ".$curLib.".F0111 where ".$curLib.".F0101.ABAN8=".$curLib.".F0111.WWAN8 and ".$curLib.".F0111.WWTYC='EF'), ''))
,trim(coalesce((select min(WWMLNM CONCAT WWATTL) from ".$curLib.".F0111 where ".$curLib.".F0101.ABAN8=".$curLib.".F0111.WWAN8 and ".$curLib.".F0111.WWTYC='PE'), '')) 
,coalesce((select SUM(SDAEXP/100) from ".$curLib.".F564211 where ".$curLib.".F0101.ABAN8=".$curLib.".F564211.SDAN8 AND SDDCTO NOT IN ('OF' , 'SQ')), 0)
,coalesce((select SUM(SDAEXP/100) from ".$curLib.".F564211 where ".$curLib.".F0101.ABAN8=".$curLib.".F564211.SDAN8 AND SDDCTO IN ('OF' , 'SQ')), 0)
,trim(coalesce((SELECT max(A2KY)
FROM ".$curLib.".F01092 WHERE ".$curLib.".F0101.ABAN8 = ".$curLib.".F01092.A2AN8 AND A2TYDT = 'CV' AND rrn(".$curLib.".F01092) = (SELECT MAX(rrn(".$curLib.".F01092)) FROM ".$curLib.".F01092 WHERE ".$curLib.".F0101.ABAN8 = ".$curLib.".F01092.A2AN8 
AND A2TYDT = 'CV')), '')),
digits(ondtey)||'/'||digits(ondtem)
) T2                                                                                            
";  
if ($whrClause != "")
	$query .= $whrClause;

$query .= ") T  ";

if($whrClause!="") $query.=$whrClause;
if($ordbyClause!="") $query.=$ordbyClause;
if($limitClause!="") $query.=$limitClause;
$query.=" FOR FETCH ONLY";
 


/*



$query = "
SELECT  
trim(ALADD1)  as ALADD1
,trim(ALADD2)  as ALADD2
,trim(ALADD3)  as ALADD3
,trim(ALADDZ)  as ALADDZ
,trim(ALCTY1)  as ALCTY1
,trim(ALADDS)  as ALADDS
,trim(ALCTR)  as ALCTR
,trim(coalesce((select min(WWMLNM CONCAT WWATTL) from JRGDTA94C.F0111 where JRGDTA94C.F0116.ALAN8=JRGDTA94C.F0111.WWAN8 and JRGDTA94C.F0111.WWTYC='EM'), '')) as MAILEM 
,trim(coalesce((select min(WWMLNM CONCAT WWATTL) from JRGDTA94C.F0111 where JRGDTA94C.F0116.ALAN8=JRGDTA94C.F0111.WWAN8 and JRGDTA94C.F0111.WWTYC='EF'), '')) as MAILEF 
,trim(coalesce((select min(WWMLNM CONCAT WWATTL) from JRGDTA94C.F0111 where JRGDTA94C.F0116.ALAN8=JRGDTA94C.F0111.WWAN8 and JRGDTA94C.F0111.WWTYC='PE'), '')) as MAILPE 

FROM JRGDTA94C.F0111 
LEFT JOIN JRGDTA94C.F0116 ON WWAN8=ALAN8
LEFT JOIN JRGDTA94C.F0101 ON ABAN8=ALAN8
"; 
if($whrClause!="") $query.=$whrClause;
if($ordbyClause!="") $query.=$ordbyClause;
if($limitClause!="") $query.=$limitClause;
$query.=" FOR FETCH ONLY";
 */
 
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
			if($key=="FATTURATO" && $row[$key]==0) $row[$key] = "0";
			if($key=="OFFERTO" && $row[$key]==0) $row[$key] = "0";
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