<?php
include("config.inc.php");
include("query_helpers.php");

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
$query = "SELECT count(*) FROM (
SELECT  COUNT00001 AS ID_ORDER
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
    where sdlttr<>'980' and sddcto in ('OF' , 'O1' , 'OB' , 'OG' , 'O4' , 'O5' , 'SQ' , 'O2' , 'O3' , 'O6' , 'O7') 
        and sdlnty not like 'T%'
    group by COUNT00001
    ) AS A
    ";

//$query .= $whrClause . (empty($whrClause) ? " WHERE " : " AND ") . 
  //        " sdlttr<>'980' and sddcto in ('OF' , 'O1' , 'OB' , 'OG' , 'O4' , 'O5' , 'SQ' , 'O2' , 'O3' , 'O6' , 'O7') 
    //    and sdlnty not like 'T%'




/*

$query .= "union all SELECT count(*) FROM (
SELECT  COUNT00001 AS ID_ORDER
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
    where sdlttr<>'980' and sddcto in ('OF' , 'O1' , 'OB' , 'OG' , 'O4' , 'O5' , 'SQ' , 'O2' , 'O3' , 'O6' , 'O7') 
        and sdlnty not like 'T%'
    group by COUNT00001
    ) AS A ";

    // $query .= $whrClause . (empty($whrClause) ? " WHERE " : " AND ") . 
  //        "  TYPE_CHANGE NOT IN ( 'UB' , 'CB' , 'SS' , 'DW' , 'DH' , 'MS')  and sdlttr<>'980' and sddcto in ('OF' , 'O1' , 'OB' , 'OG' , 'O4' , 'O5' , 'SQ' , 'O2' , 'O3' , 'O6' , 'O7') 
    //    and sdlnty not like 'T%'
  //      )
  //   SELECT * FROM OrderedChanges where RowNum between 1 and 2
  //      ";

$query .= $ordbyClause . $limitClause . " FOR FETCH ONLY";
//echo $whrClause;
*/
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
echo 'count: ' . $r++;
odbc_close($conn);