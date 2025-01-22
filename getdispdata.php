<?php
include("config.inc.php");

header('Content-Type: application/json; charset=utf-8');

set_time_limit(120);

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set("log_errors", 1);
ini_set("error_log", "/www/php80/htdocs/logs/getdispdata/php-error.log");

$k = '';
if (isset($_REQUEST['k']))
    $k = $_REQUEST["k"];
if ($k != "sJHsdwvIFTyhDuGtZoOfevsgG1A1H2s6") {
    exit;
}

$env = '';
if (isset($_REQUEST["env"]))
    $env = $_REQUEST["env"];
if ($env == '') {
    $env = 'prod'; //per retrocompatibilità
}
$curLib = $envLib[$env];

$postedBody = file_get_contents('php://input');

$server = "Driver={IBM i Access ODBC Driver};System=127.0.0.1;Uid=" . DB2_USER . ";Pwd=" . DB2_PASS . ";TRANSLATE=1;CONNTYPE=2;CMT=0;BLOCKFETCH=1;BLOCKSIZE=2000";
$user = DB2_USER;
$pass = DB2_PASS;

//connessione:
$time_start = microtime(true);

$conn = odbc_connect($server, $user, $pass);
if (!$conn) {
    echo odbc_errormsg($conn);
    exit;
}

$time_end = microtime(true);
$execution_time = ($time_end - $time_start);
//echo '<b>Connessione:</b> '.$execution_time.' s';

$whrClause = "";
$ordbyClause = "";
$limitClause = "";
$rowCount = 0;
$resArray = json_decode($postedBody, true);
if ($resArray) {

    if (isset($resArray['filters']) && count($resArray['filters']) > 0) {

        $filterMode = $resArray["filter_mode"];

        if ($whrClause == "")
            $whrClause = " WHERE ";

        $whrClause .= " (";

        $arrFilters = $resArray['filters'];
        for ($i = 0; $i < count($arrFilters); $i++) {
            if ($i > 0)
                $whrClause .= " " . $filterMode . " ";
            $whrClause .= " (";

            $curFilterMode = $arrFilters[$i]["filter_mode"];
            $curFilterFields = $arrFilters[$i]["fields"];

            for ($f = 0; $f < count($curFilterFields); $f++) {

                $curFilterField = $curFilterFields[$f];
                $curFilterFieldName = $curFilterField["field"];
                $curFilterFieldType = $curFilterField["type"];
                $curFilterFieldValue = $curFilterField["value"];

                if ($f > 0)
                    $whrClause .= " " . $curFilterMode;

                if ($curFilterFieldType == "eq")
                    $whrClause .= " (" . $curFilterFieldName . " = '" . $curFilterFieldValue . "') ";
                if ($curFilterFieldType == "neq")
                    $whrClause .= " (" . $curFilterFieldName . " <> '" . $curFilterFieldValue . "') ";
                if ($curFilterFieldType == "lt")
                    $whrClause .= " (" . $curFilterFieldName . " < '" . $curFilterFieldValue . "') ";
                if ($curFilterFieldType == "gt")
                    $whrClause .= " (" . $curFilterFieldName . " > '" . $curFilterFieldValue . "') ";
                if ($curFilterFieldType == "like") 
					$whrClause .= " (upper(" . $curFilterFieldName . ") LIKE '%" . strtoupper($curFilterFieldValue) . "%') ";
                if ($curFilterFieldType=="le") 
					$whrClause .= " (".$curFilterFieldName." <= '".$curFilterFieldValue."') ";
				if ($curFilterFieldType=="ge") 
					$whrClause .= " (".$curFilterFieldName." >= '".$curFilterFieldValue."') ";



            }
            $whrClause .= " ) ";
        }
        $whrClause .= " ) ";
    }

    if (isset($resArray['ordby'])) {
        $arrOrdby = $resArray['ordby'];
        //var_dump($arrOrdby);

        if (isset($arrOrdby[0])) {
            $ordbyClause = " ORDER BY ";
            for ($ob = 0; $ob < count($arrOrdby); $ob++) {
                if ($ob > 0)
                    $ordbyClause .= ",";
                $ordbyClause .= $arrOrdby[$ob]["field"] . " " . $arrOrdby[$ob]["dir"];
            }
        } else {
            $ordbyFields = $arrOrdby['field'];
            $arrOrdbyFields = explode(",", $ordbyFields);
            $ordbyClause = " ORDER BY ";
            for ($ob = 0; $ob < count($arrOrdbyFields); $ob++) {
                if ($ob > 0)
                    $ordbyClause .= ",";
                $ordbyClause .= trim($arrOrdbyFields[$ob]) . " " . $arrOrdby['dir'];
            }
        }
    }

    if (isset($resArray['limit'])) {
        $limitClause = " LIMIT " . $resArray['limit'];
    }

}

$time_start = microtime(true);
//query:
$query = "SELECT * FROM TABLE(
	SELECT T1.* 
	FROM TABLE(
		SELECT 
		TRIM('V') AS TIPO, 
		TRIM(SDLITM) AS LITM, 
		TRIM(SDMCU) AS MCU, 
		TRIM(VARCHAR_FORMAT((DECIMAL((SDSOQS/100*-1), 10, 2)),'9999999990.00')) AS UORG, 
		TRIM(ONDATE) AS DATA, 
		TRIM(' ') AS PS09, 
		TRIM(SDDOCO) AS DOCO, 
		TRIM(SDDCTO) AS DCTO, 
		TRIM(SDLNID) AS LNID, 
		TRIM(SDAN8) AS AN8, 
		TRIM(ABALPH) AS ALPH, 
		TRIM(SDFRGD) AS FRGD, 
		TRIM(' ') AS MOT, 
		TRIM(' ') AS PDP5, 
		TRIM('  ') AS FRTH,
		TRIM(SDPSN) AS PSN, 
		TRIM(COALESCE(SDLOTN, '')) AS LOTN,
		TRIM('  ') AS VRMK,
		TRIM('  ') AS TRAN
		FROM ".$curLib.".F4211 
		JOIN ".$curLib.".F0101 ON SDAN8=ABAN8            
		LEFT JOIN ".$curLib.".F00365 ON ONDTEJ=SDPDDJ                                  
		WHERE SDDCTO IN ('O1','O2','O3','O4','O5','O6','O7','O8','O9','ST','OB') 
		AND SDLNTY='S' AND SDNXTR<'561' AND SDSOQS<>0 
	) T1 
";
if ($whrClause != "")
    $query .= $whrClause;
 
$query .= "

UNION ALL 

	SELECT T2.* 
		FROM TABLE(
		SELECT 
		TRIM('A') AS TIPO, 
		TRIM(PDLITM) AS LITM, 
		TRIM(PDMCU) AS MCU, 
		CASE 
				WHEN COALESCE(PRUREC, 0)<>0 THEN TRIM(VARCHAR_FORMAT((DECIMAL(PRUREC/100, 10, 2)),'9999999990.00'))
                ELSE TRIM(VARCHAR_FORMAT((DECIMAL(PDUORG/100, 10, 2)),'9999999990.00'))
			END AS UORG,
		TRIM(ONDATE) AS DATA, 
		TRIM(PDPS09) AS PS09, 
		TRIM(PDDOCO) AS DOCO, 
		TRIM(PDDCTO) AS DCTO, 
		TRIM(PDLNID) AS LNID,            
		TRIM(PDAN8) AS AN8, 
		TRIM(ABALPH) AS ALPH, 
		TRIM(PDFRGD) AS FRGD, 
		TRIM(PDMOT) AS MOT,
		TRIM(PDPDP5) AS PDP5, 
		TRIM(PDFRTH) AS FRTH,
		TRIM(0) AS PSN, 
		TRIM(PRLOTN) AS LOTN,
		TRIM(PRVRMK) AS VRMK,
		CASE 
				WHEN PDUREC<>0 THEN TRIM('Y')
				ELSE TRIM('N')
			END AS TRAN
		FROM ".$curLib.".F4311 
		JOIN ".$curLib.".F0101 ON PDAN8=ABAN8 
		LEFT JOIN ".$curLib.".F43121 ON PRMATC='1' AND PRDOCO=PDDOCO AND PRDCTO=PDDCTO AND PRKCOO=PDKCOO AND PRLNID=PDLNID AND PRUREC<>0                                    
		LEFT JOIN ".$curLib.".F00365 ON ONDTEJ=PDPDDJ                                  
		WHERE PDDCTO IN ('AB','AC','AI','AL','AM')                         
		AND PDLNTY='S' AND PDNXTR<'401' AND PDUORG<>0        
	) T2 
";
if ($whrClause != "")
    $query .= $whrClause;

$query .= ") T  ";


if ($ordbyClause != "")
    $query .= $ordbyClause;
if ($limitClause != "")
    $query .= $limitClause;
$query .= " FOR FETCH ONLY";
 

$result = odbc_exec($conn, $query);
if (!$result) {
    echo '{"status":"ERROR","errmsg":' . json_encode(odbc_errormsg()) . '}';
    exit;
}
$time_end = microtime(true);
$execution_time = ($time_end - $time_start);
//echo '<b>Query:</b> '.$execution_time.' s';

echo '[';

$time_start = microtime(true);
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

$time_end = microtime(true);
$execution_time = ($time_end - $time_start);
//echo '<b>Ciclo:</b> '.$execution_time.' s';

odbc_close($conn);