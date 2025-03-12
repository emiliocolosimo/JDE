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
    RRN(F41021) AS RRN_F41021, 
    TRIM(LIITM) AS LIITM, 
    TRIM(LIMCU) AS LIMCU,  
    TRIM(LILOCN) AS LILOCN, 
    TRIM(LILOTN) AS LILOTN, 
    TRIM(LILOTS) AS LILOTS,  
    TRIM(VARCHAR_FORMAT((DECIMAL(LIPQOH/100, 10, 2)),'9999999990.00')) AS LIPQOH, 
    TRIM(VARCHAR_FORMAT((DECIMAL(LIHCOM/100, 10, 4)),'9999999990.00')) AS LIHCOM,
    TRIM(LIURRF) AS LIURRF
    FROM JRGDTA94C.F41021 AS F41021)AS T
";

$query .= $whrClause . (empty($whrClause) ? " WHERE " : " AND ") . "    TRIM(LIMCU) IN ('RGPM01','RGPM02')";

      
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