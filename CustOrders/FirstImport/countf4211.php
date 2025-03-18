<?php
include("/www/php80/htdocs/config.inc.php");
include("/www/php80/htdocs/query_helpers.php");

header('Content-Type: application/json; charset=utf-8');
set_time_limit(1200);
ini_set('log_errors', 1);
ini_set("error_log", "/www/php80/htdocs/logs/getCustOrdersFirst/php-error.log");

$k = $_REQUEST['k'] ?? '';
if ($k !== "sJHsdwvIFTyhDuGtZoOfevsgG1A1H2s6")
    exit;

$env = $_REQUEST["env"] ?? 'prod';
$curLib = $envLib[$env];

$postedBody = file_get_contents('php://input');
$resArray = json_decode($postedBody, true);

$server="Driver={IBM i Access ODBC Driver};System=127.0.0.1;Uid=".DB2_USER.";Pwd=".DB2_PASS.";TRANSLATE=1;CONNTYPE=2;CMT=0;BLOCKFETCH=1;BLOCKSIZE=2000"; 
$conn = odbc_connect($server, DB2_USER, DB2_PASS);

if (!$conn) {
    echo json_encode(["status" => "ERROR", "errmsg" => odbc_errormsg()]);
    exit;
}

$whrClause = generateWhereClause($resArray);
$ordbyClause = generateOrderByClause($resArray);
$limitClause = generateLimitClause($resArray);

//query:
$query = "SELECT count(*) FROM(
SELECT sddoco
		FROM JRGDTA94C.F4211
        where sdlttr<>'980' and sduorg<>0 and sddcto in ('OF' , 'O1' , 'OB' , 'OG' , 'O4' , 'O5' , 'SQ' , 'O2' , 'O3' , 'O6' , 'O7') 
        and sdlnty not like 'T%'
    union all




SELECT sddoco
		FROM JRGDTA94C.F42119 
        where sdlttr<>'980' and sduorg<>0 and sddcto in ('OF' , 'O1' , 'OB' , 'OG' , 'O4' , 'O5' , 'SQ' , 'O2' , 'O3' , 'O6' , 'O7') 
        and sdlnty not like 'T%'
        )  AS T
    ";

    //$query .= $whrClause . $ordbyClause . $limitClause . " FOR FETCH ONLY";

$result = odbc_exec($conn, $query);

//echo $whrClause;

if (!$result) {
    echo json_encode(["status" => "ERROR", "errmsg" => odbc_errormsg()]);
    exit;
}

echo '[';
$r = 0;
while ($row = odbc_fetch_array($result)) {

    foreach (array_keys($row) as $key) {
        $row[$key] = utf8_encode($row[$key]);
    }

    if ($r > 0)
        echo ',';
    echo json_encode($row);
    $r++;
}

echo ']';
odbc_close($conn);