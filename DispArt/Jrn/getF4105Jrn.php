<?php
include("/www/php80/htdocs/config.inc.php");
include("/www/php80/htdocs/query_helpers.php");

header('Content-Type: application/json; charset=utf-8');

set_time_limit(120);

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set("log_errors", 1);
ini_set("error_log", "/www/php80/htdocs/logs/getF4105Jrn/php-error.log");

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
    /*    if (!preg_match('/^\d{4}-\d{2}-\d{2}-\d{2}\.\d{2}\.\d{2}$/', $startingTimestamp)) {
            http_response_code(400);
            echo json_encode([
                "status" => "ERROR",
                "message" => "Invalid format for starting_timestamp. Expected: YYYY-MM-DD-HH.MM.SS"
            ]);
            exit;
        }*/
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

$queryDisp .= "WITH OrderedChanges AS (
    SELECT 
        ENTRY00001 AS TIME_CHANGE,
        JOURN00002 AS TYPE_CHANGE, 
        RRN(F4105) AS RRN_F4105, 
        TRIM(COALESCE(F4105.COITM, '')) AS COITM,
        TRIM(COALESCE(F4105.COLOTN, '')) AS COLOTN,
        TRIM(COALESCE(F4105.COMCU, '')) AS COMCU,
        TRIM(COALESCE(F4105.COLOCN, '')) AS COLOCN,
        TRIM(VARCHAR_FORMAT((DECIMAL(COALESCE(F4105.COUNCS, 0)/10000000, 10, 7)),'9999999990.0000000')) AS COUNCS, 
        TRIM(COALESCE(F4105.COLEDG, '')) AS COLEDG,
        ROW_NUMBER() OVER (PARTITION BY RRN(F4105) ORDER BY ENTRY00001 DESC) AS RowNum
   FROM TABLE (
        QSYS2.DISPLAY_JOURNAL( 'JRGPFIL', 'RGPJRN',
        OBJECT_NAME=>'F4105',
        STARTING_RECEIVER_NAME => '*CURAVLCHN',
        OBJECT_LIBRARY=>'JRGDTA94C',
        OBJECT_OBJTYPE=>'*FILE',
        OBJECT_MEMBER=>'*ALL',
        STARTING_TIMESTAMP => '$startingTimestamp')) 
       AS JT 
LEFT JOIN JRGDTA94C.F4105 AS F4105
    ON JT.COUNT00001 = RRN(F4105) WHERE COLEDG IN ('06' , 'CD' , 'CS')

)

SELECT * FROM OrderedChanges 
";

$queryDisp .= $whrClause . (empty($whrClause) ? " WHERE " : " AND ") . "   RowNum = 1 AND COLOTN<>'  ' ";
if ($ordbyClause != "")
    $queryDisp .= $ordbyClause;
if ($limitClause != "")
    $queryDisp .= $limitClause;
$queryDisp .= " FOR FETCH ONLY";

//echo $queryDisp;

// 1️⃣ **Esegui la prima query ($queryDisp)**
$resultDisp = odbc_exec($conn, $queryDisp);
if (!$resultDisp) {
    echo json_encode(["status" => "ERROR", "errmsg" => odbc_errormsg()]);
    exit;
}

while ($row = odbc_fetch_array($resultDisp)) {
    foreach ($row as $key => $value) {
        $row[$key] = utf8_encode($value);
    }
    $response[] = $row;
}

// **Restituisci i dati in formato JSON**
echo json_encode($response, JSON_PRETTY_PRINT);

odbc_close($conn);
