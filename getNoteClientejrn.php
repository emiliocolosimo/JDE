<?php
include("config.inc.php");
include("query_helpers.php");

header('Content-Type: application/json; charset=utf-8');

set_time_limit(120);

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set("log_errors", 0);
ini_set("error_log", "/www/php80/htdocs/logs/getNoteClienteJrn/php-error.log");

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

// Query principale
$query = "SELECT SEQUE00001 AS RRN_CHANGE, ENTRY_TIMESTAMP AS TIME_CHANGE , TRIM(C5CKEY) AS CODCLI, 
TRIM(F0016.CYWTXT) AS NOTE_CLIENTE 
FROM TABLE (
        QSYS2.DISPLAY_JOURNAL( 'JRGPFIL', 'RGPJRN',
        OBJECT_NAME=>'F00163',
        STARTING_RECEIVER_NAME => '*CURAVLCHN',
        OBJECT_LIBRARY=>'JRGDTA94C',
        OBJECT_OBJTYPE=>'*FILE',
        OBJECT_MEMBER=>'*ALL',
        STARTING_TIMESTAMP => '$startingTimestamp')) AS JT , JRGDTA94C.F00163 AS F00163 , JRGDTA94C.F0016 AS F0016 
WHERE JT.COUNT00001 = RRN(F00163) 
AND F0016.CYSERK = F00163.C5SERK AND C5WAPP = '*ADDNOTE' and JOURN00002 NOT IN ('UB' , 'CB' , 'SS' , 'DW' , 'DH' , 'MS')
";

$query .= $whrClause . $ordbyClause . $limitClause . " FOR FETCH ONLY";
//echo $query;

// Esecuzione query
$result = odbc_exec($conn, $query);
if (!$result) {
    echo json_encode(["status" => "ERROR", "errmsg" => odbc_errormsg()]);
    exit;
}

$r = 0;
$groupedData = [];

while ($row = odbc_fetch_array($result)) {
    $seque = $row['RRN_CHANGE'];
    $entryTimestamp = $row['TIME_CHANGE'];
    $c5ckey = $row['CODCLI'];
    $cywtxt = utf8_encode($row['NOTE_CLIENTE']);

    $key = "{$seque}|{$entryTimestamp}|{$c5ckey}";

    if (!isset($groupedData[$key])) {
        $groupedData[$key] = [
            "RRN_CHANGE" => $seque,
            "TIME_CHANGE" => $entryTimestamp,
            "CODCLI" => $c5ckey,
            "NOTE_CLIENTE" => []
        ];
    }

    // Aggiunge il valore CYWTXT alla lista
    $groupedData[$key]["NOTE_CLIENTE"][] = $cywtxt;
}

odbc_close($conn);

// Converte in JSON e stampa
echo json_encode(
    array_values(array: 
$groupedData
)
, JSON_PRETTY_PRINT
);
