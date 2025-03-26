<?php
include("/www/php80/htdocs/config.inc.php");
include("/www/php80/htdocs/query_helpers.php");

header('Content-Type: application/json; charset=utf-8');

set_time_limit(120);

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set("log_errors", 1);
ini_set("error_log", "/www/php80/htdocs/logs/Tassi/php-error.log");

$k = isset($_REQUEST['k']) ? $_REQUEST["k"] : '';
if ($k != "sJHsdwvIFTyhDuGtZoOfevsgG1A1H2s6") {
    echo json_encode(["status" => "ERROR", "message" => "Invalid access key"]);
    exit;
}

$env = isset($_REQUEST["env"]) ? $_REQUEST["env"] : 'prod';
$curLib = isset($envLib[$env]) ? $envLib[$env] : '';

$postedBody = file_get_contents('php://input');
$resArray = json_decode($postedBody, true);

/*
if (!$resArray) {
    http_response_code(400); // Codice HTTP 400: Bad Request
    echo json_encode(["status" => "ERROR", "message" => "Invalid JSON in request body"]);
    exit;
}
*/
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
        /*
        Il tasso di cambio si trova nel campo CXCRRD del file F0015 letto per:
        - CXCRCD = <valuta estera>
        - CXCRDC = "EUR"
        Essendo i tassi di cambio definiti per data, si deve usare il record più
        recente data CXEFT
        */
// Query principale
$query = "WITH CTE AS (
    SELECT 
        CXCRCD AS VALUTA,   
        TRIM(VARCHAR_FORMAT((DECIMAL(CXCRRD, 10, 7 )),'9999999990.0000000')) AS TASSO,
        CXEFT AS DATA,
        ROW_NUMBER() OVER (PARTITION BY CXCRCD ORDER BY CXEFT DESC) AS RN
    FROM JRGDTA94C.F0015 where CXCRDC = 'EUR'
)
SELECT VALUTA, TASSO, DATA
FROM CTE
WHERE RN = 1
ORDER BY VALUTA
		";
// 		ORDER BY CXEFT DESC 

//		FETCH FIRST ROW ONLY


$query .= $whrClause ;

$query .= $ordbyClause . $limitClause . " FOR FETCH ONLY";
//echo $whrClause;

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