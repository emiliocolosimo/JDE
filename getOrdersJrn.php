<?php
include("config.inc.php");

header('Content-Type: application/json; charset=utf-8');

set_time_limit(120);

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set("log_errors", 1);
ini_set("error_log", "/www/php80/htdocs/logs/getOrdersJrn/php-error.log");

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

if (isset($resArray['number_last_change']) && !empty($resArray['number_last_change'])) {
    $numberLastChange = $resArray['number_last_change'];
    if (!is_numeric($numberLastChange) || $numberLastChange <= 0) {
        http_response_code(400);
        echo json_encode([
            "status" => "ERROR",
            "message" => "Invalid value for number_last_charge. Expected a positive numeric value."
        ]);
        exit;
    }
} else
// Convertire la variabile in numero
$numberLastChange = intval($numberLastChange);


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

$whrClause = "";
$ordbyClause = "";
$limitClause = "";

if (isset($resArray['filters']) && count($resArray['filters']) > 0) {
    $filterMode = $resArray["filter_mode"] ?? "AND";
    $whrClause = " AND (";
    $arrFilters = $resArray['filters'];
    foreach ($arrFilters as $i => $filter) {
        if ($i > 0)
            $whrClause .= " $filterMode ";
        $whrClause .= " (";
        $curFilterMode = $filter["filter_mode"] ?? "AND";
        foreach ($filter["fields"] as $f => $curFilterField) {
            if ($f > 0)
                $whrClause .= " $curFilterMode ";
            $fieldName = $curFilterField["field"];
            $fieldType = $curFilterField["type"];
            $fieldValue = $curFilterField["value"];
            switch ($fieldType) {
                case "eq":
                    $whrClause .= " ($fieldName = '$fieldValue') ";
                    break;
                case "neq":
                    $whrClause .= " ($fieldName <> '$fieldValue') ";
                    break;
                case "lt":
                    $whrClause .= " ($fieldName < '$fieldValue') ";
                    break;
                case "gt":
                    $whrClause .= " ($fieldName > '$fieldValue') ";
                    break;
                case "le":
                    $whrClause .= " ($fieldName <= '$fieldValue') ";
                    break;
                case "ge":
                    $whrClause .= " ($fieldName >= '$fieldValue') ";
                    break;
                case "like":
                    $whrClause .= " (UPPER($fieldName) LIKE '%" . strtoupper($fieldValue) . "%') ";
                    break;
            }
        }
        $whrClause .= ") ";
    }
    $whrClause .= ")";
}

if (isset($resArray['ordby']) && count($resArray['ordby']) > 0) {
    $ordbyClause = " ORDER BY ";
    foreach ($resArray['ordby'] as $i => $order) {
        if ($i > 0)
            $ordbyClause .= ", ";
        $field = $order['field'];
        $dir = strtoupper($order['dir']) === 'DESC' ? 'DESC' : 'ASC';
        $ordbyClause .= "$field $dir";
    }
}

if (isset($resArray['limit'])) {
    $limitClause = " FETCH FIRST " . intval($resArray['limit']) . " ROWS ONLY";
}

// Query principale
$query = "SELECT ENTRY00001 AS TIME_CHANGE,
        SEQUE00001 AS RRN_CHANGE,
        JOURN00002 AS TYPE_CHANGE, 
        'OPEN' AS SOURCE_FILE,
       COUNT00001 AS ID_ORDER,                         
       CASE 
           WHEN F4211.SDDCTO IN ('SQ', 'OF') THEN 'Offerta'
           WHEN F4211.SDDCTO IN ('OB') THEN 'Richiamo'
           ELSE 'Ordine'
       END AS TIPO, 
       CASE 
           WHEN F4211.SDDCTO IN ('OF', 'O1', 'OB', 'OG', 'O4', 'O5') THEN 'Italia'
           WHEN F4211.SDDCTO IN ('SQ', 'O2', 'O3', 'O6', 'O7') THEN 'Estero'
           ELSE 'Non definito'
       END AS UFFICIO,
       TRIM(F4211.SDLITM) AS SDLITM,
       TRIM(F4211.SDFRGD) AS SDFRGD,
       TRIM(F4211.SDEUSE) AS SDEUSE,
       TRIM(F4211.SDUPRC) AS SDUPRC,
       TRIM(F4211.SDFUP) AS SDFUP,
       TRIM(F4211.SDUOM4) AS SDUOM4,
       TRIM(F4211.SDCRCD) AS SDCRCD,
       TRIM(F4211.SDPDDJ) AS SDPDDJ,
       TRIM(F4211.SDADDJ) AS SDADDJ,
       TRIM(F4211.SDAN8) AS SDAN8,
       TRIM(F4211.SDASN) AS SDASN, 
       TRIM(F4211.SDDOCO) AS SDDOCO,
       TRIM(F4211.SDDOC) AS SDDOC,
       TRIM(F4211.SDDCT) AS SDDCT,
       TRIM(F4211.SDDELN) AS SDDELN,
       TRIM(COALESCE((SELECT MIN(F0111.WWMLNM) FROM JRGDTA94C.F0111 AS F0111 WHERE F4211.SDCARS = F0111.WWAN8 AND F0111.WWIDLN = 0), '')) AS SDCARS,
       TRIM(F4211.SDLNID) AS SDLNID,
       DECIMAL(SDSOQS / 100, 14, 4) AS SDSOQS, 
       DECIMAL(SDAEXP / 100, 14, 4) AS SDAEXP,  
        DECIMAL(SDFEA / 100, 14, 4) AS SDFEA
FROM TABLE (
        QSYS2.DISPLAY_JOURNAL( 'JRGPFIL', 'RGPJRN',
        OBJECT_NAME=>'F4211',
        STARTING_RECEIVER_NAME => '*CURAVLCHN',
        OBJECT_LIBRARY=>'JRGDTA94C',
        OBJECT_OBJTYPE=>'*FILE',
        OBJECT_MEMBER=>'*ALL',
        STARTING_TIMESTAMP => '$startingTimestamp')) AS JT
LEFT JOIN JRGDTA94C.F4211 AS F4211
    ON JT.COUNT00001 = RRN(F4211)
WHERE JT.JOURN00002 NOT IN ('UB' , 'CB' , 'SS' , 'DW' , 'DH' , 'MS') and JT.SEQUE00001 >=  '$numberLastChange'


union all 

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
       TRIM(F42119.SDDELN) AS SDDELN,
       TRIM(COALESCE((SELECT MIN(F0111.WWMLNM) FROM JRGDTA94C.F0111 AS F0111 WHERE F42119.SDCARS = F0111.WWAN8 AND F0111.WWIDLN = 0), '')) AS SDCARS,
       TRIM(F42119.SDLNID) AS SDLNID,
       DECIMAL(SDSOQS / 100, 14, 4) AS SDSOQS, 
       DECIMAL(SDAEXP / 100, 14, 4) AS SDAEXP,  
        DECIMAL(SDFEA / 100, 14, 4) AS SDFEA
FROM TABLE (
        QSYS2.DISPLAY_JOURNAL( 'JRGPFIL', 'RGPJRN',
        OBJECT_NAME=>'F42119',
        STARTING_RECEIVER_NAME => '*CURAVLCHN',
        OBJECT_LIBRARY=>'JRGDTA94C',
        OBJECT_OBJTYPE=>'*FILE',
        OBJECT_MEMBER=>'*ALL',
        STARTING_TIMESTAMP => '$startingTimestamp')) AS JT
LEFT JOIN JRGDTA94C.F42119 AS F42119
    ON JT.COUNT00001 = RRN(F42119)
WHERE JT.JOURN00002 NOT IN ('UB' , 'CB' , 'SS' , 'DW' , 'DH' , 'MS') 
  AND JT.SEQUE00001 >=  '$numberLastChange'


";

$query .= $whrClause . $ordbyClause . $limitClause . " FOR FETCH ONLY";

// Esecuzione query
$result = odbc_exec($conn, $query);
if (!$result) {
    echo json_encode(["status" => "ERROR", "errmsg" => odbc_errormsg()]);
    exit;
}
//echo $query;

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