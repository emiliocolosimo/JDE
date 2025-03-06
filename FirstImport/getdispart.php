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
    RRN(F4105) AS RRN_F4105, 
    TRIM(COALESCE(F4105.COITM, '')) AS COITM,
    TRIM(COALESCE(F4105.COLOTN, '')) AS COLOTN,
        TRIM(VARCHAR_FORMAT((DECIMAL(COALESCE(F4105.COUNCS, 0)/10000000, 10, 7)),'9999999990.0000000')) AS COUNCS, 
    TRIM(COALESCE(F4105.COLEDG, '')) AS COLEDG

    FROM JRGDTA94C.F4105 AS F4105 WHERE COLEDG='06'
    
    ) AS T
";

$query.="SELECT * FROM TABLE(
    SELECT
    RRN(F4101) AS RRN_F4101, 
    TRIM(F4101.IMITM) AS IMITM,     
    TRIM(COALESCE(F4101.IMLITM, '')) AS IMLITM,
    TRIM(COALESCE(F4101.IMDSC1, '')) AS IMDSC1,
    TRIM(COALESCE(F4101.IMSRP1, '')) AS IMSRP1,
    TRIM(COALESCE(F4101.IMSRP2, '')) AS IMSRP2,
    TRIM(COALESCE(F4101.IMSRP3, '')) AS IMSRP3, 
    TRIM(COALESCE(F4101.IMUOM1, '')) AS IMUOM1,  
    TRIM(COALESCE(F4101.IMUOM4, '')) AS IMUOM4,  
    TRIM(COALESCE(F4101D.IMDSC1, '')) AS IMDSCL
    FROM JRGDTA94C.F4101 AS F4101
    LEFT JOIN JRGDTA94C.F4101D AS F4101D ON F4101.IMITM = F4101D.IMITM AND F4101D.IMLNGP = 'E' 
    WHERE IMSTKT NOT IN ('O')) AS T
";

$query .= $whrClause . (empty($whrClause) ? " WHERE " : " AND ") . " IMITM<>' '";
  
      
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