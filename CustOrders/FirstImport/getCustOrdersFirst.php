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
$query = "SELECT * FROM(
SELECT RRN(JRGDTA94C.F4211) AS ID_ORDER ,
	    'OPEN' AS SOURCE_FILE ,
	CASE 
        WHEN SDDCTO IN ('SQ', 'OF') THEN 'Offerta'
        WHEN SDDCTO IN ('OB') THEN 'Richiamo'
        WHEN SDDCTO IN ('O1' , 'OG' , 'O4' , 'O5' , 'O2' , 'O3' , 'O6' , 'O7') THEN 'Ordine'
        ELSE 'Da Verificare'
        END AS TIPO, 
    CASE 
        WHEN SDDCTO IN ('OF' , 'O1' , 'OB' , 'OG' , 'O4' , 'O5') THEN 'Italia'
        WHEN SDDCTO IN ('SQ' , 'O2' , 'O3' , 'O6' , 'O7') THEN 'Estero'
        ELSE 'Non definito'
        END AS UFFICIO,
        TRIM(SDLITM) AS SDLITM,
        TRIM(SDFRGD) AS SDFRGD,
        TRIM(SDEUSE) AS SDEUSE,
        TRIM(VARCHAR_FORMAT((DECIMAL(SDUPRC/100, 10, 2)),'9999999990.00')) AS SDUPRC,
        TRIM(VARCHAR_FORMAT((DECIMAL(SDFUP/100, 10, 2)),'9999999990.00')) AS SDFUP,
        TRIM(SDUOM4) AS SDUOM4,
        TRIM(SDCRCD) AS SDCRCD,
        TRIM(SDPDDJ) AS SDPDDJ,
        TRIM(SDADDJ) AS SDADDJ,
        TRIM(SDAN8) AS SDAN8,
        TRIM(SDASN) AS SDASN, 
        TRIM(SDDOCO) AS SDDOCO,
        TRIM(SDDOC) AS SDDOC,
        TRIM(SDDCT) AS SDDCT,
        TRIM(SDDELN) AS SDDELN,
        trim(coalesce((select min(WWMLNM) from JRGDTA94C.F0111 where JRGDTA94C.F4211.SDCARS=JRGDTA94C.F0111.WWAN8 and JRGDTA94C.F0111.WWIDLN=0), '')) as SDCARS,
        TRIM(VARCHAR_FORMAT((DECIMAL(SDLNID/100, 10, 2)),'9999999990.00')) AS SDLNID,
        TRIM(VARCHAR_FORMAT((DECIMAL(SDSOQS/100, 10, 2)),'9999999990.00')) AS SDSOQS,
        TRIM(VARCHAR_FORMAT((DECIMAL(SDAEXP/100, 10, 2)),'9999999990.00')) AS SDAEXP,
        TRIM(VARCHAR_FORMAT((DECIMAL(SDFEA/100, 10, 2)),'9999999990.00')) AS SDFEA
		FROM JRGDTA94C.F4211
        where sdlttr<>'980' and sduorg<>0 and sddcto in ('OF' , 'O1' , 'OB' , 'OG' , 'O4' , 'O5' , 'SQ' , 'O2' , 'O3' , 'O6' , 'O7') 
        and sdlnty not like 'T%'

    union all

		SELECT RRN(JRGDTA94C.F42119) AS ID_ORDER ,
	    'CLOSED' AS SOURCE_FILE ,
	CASE 
        WHEN SDDCTO IN ('SQ', 'OF') THEN 'Offerta'
        WHEN SDDCTO IN ('OB') THEN 'Richiamo'
        WHEN SDDCTO IN ('O1' , 'OG' , 'O4' , 'O5' , 'O2' , 'O3' , 'O6' , 'O7') THEN 'Ordine'
        ELSE 'Da Cancellare'
        END AS TIPO, 
    CASE 
        WHEN SDDCTO IN ('OF' , 'O1' , 'OB' , 'OG' , 'O4' , 'O5') THEN 'Italia'
        WHEN SDDCTO IN ('SQ' , 'O2' , 'O3' , 'O6' , 'O7') THEN 'Estero'
        ELSE 'Da Verificare'
        END AS UFFICIO,
        TRIM(SDLITM) AS SDLITM,
        TRIM(SDFRGD) AS SDFRGD,
        TRIM(SDEUSE) AS SDEUSE,
        TRIM(VARCHAR_FORMAT((DECIMAL(SDUPRC/100, 10, 2)),'9999999990.00')) AS SDUPRC,
        TRIM(VARCHAR_FORMAT((DECIMAL(SDFUP/100, 10, 2)),'9999999990.00')) AS SDFUP,
        TRIM(SDUOM4) AS SDUOM4,
        TRIM(SDCRCD) AS SDCRCD,
        TRIM(SDPDDJ) AS SDPDDJ,
        TRIM(SDADDJ) AS SDADDJ,
        TRIM(SDAN8) AS SDAN8,
        TRIM(SDASN) AS SDASN, 
        TRIM(SDDOCO) AS SDDOCO,
        TRIM(SDDOC) AS SDDOC,
        TRIM(SDDCT) AS SDDCT,
        TRIM(SDDELN) AS SDDELN,
        trim(coalesce((select min(WWMLNM) from JRGDTA94C.F0111 where JRGDTA94C.F42119.SDCARS=JRGDTA94C.F0111.WWAN8 and JRGDTA94C.F0111.WWIDLN=0), '')) as SDCARS,
        TRIM(VARCHAR_FORMAT((DECIMAL(SDLNID/100, 10, 2)),'9999999990.00')) AS SDLNID,
        TRIM(VARCHAR_FORMAT((DECIMAL(SDSOQS/100, 10, 2)),'9999999990.00')) AS SDSOQS,
        TRIM(VARCHAR_FORMAT((DECIMAL(SDAEXP/100, 10, 2)),'9999999990.00')) AS SDAEXP,
        TRIM(VARCHAR_FORMAT((DECIMAL(SDFEA/100, 10, 2)),'9999999990.00')) AS SDFEA
		FROM JRGDTA94C.F42119 
        where sdlttr<>'980' and sduorg<>0 and sddcto in ('OF' , 'O1' , 'OB' , 'OG' , 'O4' , 'O5' , 'SQ' , 'O2' , 'O3' , 'O6' , 'O7') 
        and sdlnty not like 'T%'
        )  AS T
    ";

    $query .= $whrClause . $ordbyClause . $limitClause . " FOR FETCH ONLY";

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
//echo 'count: ' . $r++;
odbc_close($conn);