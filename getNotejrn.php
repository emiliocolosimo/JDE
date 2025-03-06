<?php
include("config.inc.php");
include("query_helpers.php");

header('Content-Type: application/json; charset=utf-8');

set_time_limit(120);

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set("log_errors", 1);
ini_set("error_log", "/www/php80/htdocs/logs/getdispart/php-error.log");

$k = isset($_REQUEST['k']) ? $_REQUEST["k"] : '';
if ($k != "sJHsdwvIFTyhDuGtZoOfevsgG1A1H2s6") {
    echo json_encode(["status" => "ERROR", "message" => "Invalid access key"]);
    exit;
}

$env = isset($_REQUEST["env"]) ? $_REQUEST["env"] : 'prod';
$curLib = isset($envLib[$env]) ? $envLib[$env] : '';

$postedBody = file_get_contents('php://input');
$resArray = json_decode($postedBody, true);

if (!$resArray) {
    http_response_code(400); // Codice HTTP 400: Bad Request
    echo json_encode(["status" => "ERROR", "message" => "Invalid JSON in request body"]);
    exit;
}

if (isset($resArray['starting_timestamp']) && !empty($resArray['starting_timestamp'])) {
    $startingTimestamp = $resArray['starting_timestamp'];
    if (!preg_match('/^\d{4}-\d{2}-\d{2}-\d{2}\.\d{2}\.\d{2}$/', $startingTimestamp)) {
        http_response_code(400);
        echo json_encode([
            "status" => "ERROR",
            "message" => "Invalid format for starting_timestamp. Expected: YYYY-MM-DD-HH.MM.SS"
        ]);
        exit;
    }
} else {
    http_response_code(400);
    echo json_encode(["status" => "ERROR", "message" => "Missing required parameter: starting_timestamp"]);
    exit;
}

$server = "Driver={IBM i Access ODBC Driver};System=127.0.0.1;Uid=" . DB2_USER . ";Pwd=" . DB2_PASS . ";TRANSLATE=1;CONNTYPE=2;CMT=0;BLOCKFETCH=1;BLOCKSIZE=2000";
$user = DB2_USER;
$pass = DB2_PASS;

$conn = odbc_connect($server, $user, $pass);
if (!$conn) {
    echo json_encode(["status" => "ERROR", "errmsg" => odbc_errormsg()]);
    exit;
}

$whrClause = generateWhereClause($resArray);
$ordbyClause = generateOrderByClause($resArray);
$limitClause = generateLimitClause($resArray);
 
//query:

/*
SELECT * FROM (
    SELECT ENTRY00001 AS TIME_CHANGE,
            SEQUE00001 AS RRN_CHANGE,
            JOURN00002 AS TYPE_CHANGE, 
            'CLOSED' AS SOURCE_FILE,
           COUNT00001 AS ID_ORDER,                         
           CASE 
               WHEN F42119.SDDCTO IN ('SQ', 'OF') THEN 'Offerta'
               WHEN F42119.SDDCTO IN ('OB') THEN 'Richiamo'
               ELSE 'Ordine'
           END AS TIPO, 
           CASE 
               WHEN F42119.SDDCTO IN ('OF', 'O1', 'OB', 'OG', 'O4', 'O5') THEN 'Italia'
               WHEN F42119.SDDCTO IN ('SQ', 'O2', 'O3', 'O6', 'O7') THEN 'Estero'
               ELSE 'Non definito'
           END AS UFFICIO,
            TRIM(F42119.SDDCTO) AS SDDCTO,
           TRIM(F42119.SDLITM) AS SDLITM,
           TRIM(F42119.SDFRGD) AS SDFRGD,
           TRIM(F42119.SDEUSE) AS SDEUSE,
           TRIM(F42119.SDUPRC) AS SDUPRC,
           TRIM(F42119.SDFUP) AS SDFUP,
           TRIM(F42119.SDUOM4) AS SDUOM4,
           TRIM(F42119.SDCRCD) AS SDCRCD,
           TRIM(F42119.SDPDDJ) AS SDPDDJ,
           TRIM(F42119.SDADDJ) AS SDADDJ,
           TRIM(F42119.SDAN8) AS SDAN8,
           TRIM(F42119.SDASN) AS SDASN, 
           TRIM(F42119.SDDOCO) AS SDDOCO,
           TRIM(F42119.SDDOC) AS SDDOC,
           TRIM(F42119.SDDCT) AS SDDCT,
            TRIM(F42119.SDLTTR) AS SDLTTR,
            TRIM(F42119.SDLNTY) AS SDLNTY,
           TRIM(F42119.SDDELN) AS SDDELN,
           TRIM(COALESCE((SELECT MIN(F0111.WWMLNM) FROM JRGDTA94C.F0111 AS F0111 WHERE F42119.SDCARS = F0111.WWAN8 AND F0111.WWIDLN = 0), '')) AS SDCARS,
           TRIM(F42119.SDLNID) AS SDLNID,
           DECIMAL(SDSOQS / 100, 14, 4) AS SDSOQS, 
           DECIMAL(SDAEXP / 100, 14, 4) AS SDAEXP,  
            DECIMAL(SDFEA / 100, 14, 4) AS SDFEA
    FROM TABLE (
            QSYS2.DISPLAY_JOURNAL( 'JRGPFIL', 'RGPJRN',
            OBJECT_NAME=>'F41021',
            STARTING_RECEIVER_NAME => '*CURAVLCHN',
            OBJECT_LIBRARY=>'JRGDTA94C',
            OBJECT_OBJTYPE=>'*FILE',
            OBJECT_MEMBER=>'*ALL',
            STARTING_TIMESTAMP => '$startingTimestamp')) AS JT
    LEFT JOIN JRGDTA94C.F41021JD AS F41021JD
        ON JT.COUNT00001 = RRN(F41021)) AS A
    ";

*/




$query = "SELECT  
    HEX(ENTRY_DATA) AS ENTRY_DATA_HEX

FROM TABLE (                                               
QSYS2.DISPLAY_JOURNAL( 'JRGPFIL', 'RGPJRN',                
STARTING_RECEIVER_NAME => '*CURAVLCHN',                    
OBJECT_LIBRARY=>'JRGDTA94C',                               
OBJECT_OBJTYPE=>'*FILE',                                   
OBJECT_MEMBER=>'*ALL'  ,                                   
STARTING_TIMESTAMP => '$startingTimestamp'         
)) AS JT        

    ";
  //  if($whrClause!="") $query.=$whrClause;
     /*
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
    */
   // $query.=") T  ";
     
      
   // if($ordbyClause!="") $query.=$ordbyClause;
    if($limitClause!="") $query.=$limitClause;
    $query.=" FOR FETCH ONLY";
         
    
echo $query;

// Esecuzione query
$result = odbc_exec($conn, $query);
if (!$result) {
    echo json_encode(["status" => "ERROR", "errmsg" => odbc_errormsg()]);
    exit;
}

echo '[';
$r = 0;

while ($row = odbc_fetch_array($result)) {
    foreach ($row as $key => $value) {
        $row[$key] = utf8_encode($value);
    }
    if ($r > 0)
        echo ',';
        echo json_encode($row);
    $r++;
}

echo ']';

odbc_close($conn);	