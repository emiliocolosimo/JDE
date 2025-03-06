<?php
include("/www/php80/htdocs/config.inc.php");
include("/www/php80/htdocs/query_helpers.php");

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


    $query.="SELECT * FROM TABLE(
    SELECT
    RRN(F4108) AS RRN_F4108, 
    TRIM(COALESCE(F4108.IOITM, '')) AS IOITM,
    TRIM(COALESCE(F4108.IOLOTN, '')) AS IOLOTN,
    TRIM(COALESCE(F4108.IOMCU, '')) AS IOMCU,
    TRIM(COALESCE(F4108.IOLOT1, '')) AS IOLOT1,
    TRIM(COALESCE(F4108.IOLOT2, '')) AS IOLOT2,
    COALESCE(F4108.IOU1DJ, 0) AS IOU1DJ,
    COALESCE(F4108.IOU2DJ, 0) AS IOU2DJ,
    COALESCE(F4108.IOU3DJ, 0) AS IOU3DJ,
    COALESCE(F4108.IOVEND, 0) AS IOVEND
    FROM JRGDTA94C.F4108 AS F4108
    
    ) AS T
";

$query .= $whrClause . (empty($whrClause) ? " WHERE " : " AND ") . " IOITM<>0";
  
      
    if($ordbyClause!="") $query.=$ordbyClause;
    if($limitClause!="") $query.=$limitClause;
    $query.=" FOR FETCH ONLY";
         
    
//echo $query;

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