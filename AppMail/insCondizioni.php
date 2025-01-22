<?php

include("/www/php80/htdocs/config.inc.php");
require_once('Toolkit.php');
require_once('ToolkitService.php');

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set("log_errors", 1);
ini_set("error_log", "/www/php80/htdocs/logs/insCondizioni/php-error.log");

header('Content-Type: application/json; charset=utf-8');

$k = '';
if (isset($_REQUEST['k']))
    $k = $_REQUEST["k"];
if ($k != "sJHsdwvIFTyhDuGtZoOfevsgG1A1H2s6") {
    exit;
}

$env = '';
if(isset($_REQUEST["env"])) $env = $_REQUEST["env"];
if($env=='') {
	$env='prod'; //per retrocompatibilità
}
$curLib = $envLib[$env];  


$postedBody = file_get_contents('php://input');

$server = "Driver={IBM i Access ODBC Driver};System=127.0.0.1;Uid=" . DB2_USER . ";Pwd=" . DB2_PASS . ";TRANSLATE=1;";
$user = DB2_USER;
$pass = DB2_PASS;
$conn = odbc_connect($server, $user, $pass);
if (!$conn) {
    $errMsg = "Errore connessione al database : " . odbc_errormsg($conn);
    echo '{"stat":"ERR","msg":' . json_encode($errMsg) . '}';
    exit;
}

$hasErr = false;

$resArray = json_decode($postedBody, true);
if ($resArray) {
    if (isset($resArray['details'])) {
        $arrDetails = $resArray['details'];

        for ($i = 0; $i < count($arrDetails) && !$hasErr; $i++) {
            // Valorizzazione campi con i valori richiesti
            $A2TYDT = 'CV';
            $A2UPDJ = 0;
            $A2AT1 = 'C';
            $A2AN8 = $arrDetails[$i]['A2AN8'] ?? '';
            $A2KY = 'INVIATE IL';

            // Calcolo della data odierna in formato giuliano
            $today = new DateTime();
            $A2EFT = intval($today->format('z')) + 1 + (($today->format('Y') - 1900) * 1000);

            $A2EFTE = 0;
            $A2AMTU = 0;
            $A2RMK = 'INVIATE AUTOMATICAMENTE';
            $A2RMK2 = '  ';

            $query = "INSERT INTO ".$curLib.".F01092 
                      (A2TYDT, A2UPDJ, A2AT1, A2AN8, A2KY, A2EFT, A2EFTE, A2AMTU, A2RMK, A2RMK2) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $pstmt = odbc_prepare($conn, $query);
            if ($pstmt) {
                $arrParams = [
                    $A2TYDT, $A2UPDJ, $A2AT1, $A2AN8, $A2KY,
                    $A2EFT, $A2EFTE, $A2AMTU, $A2RMK, $A2RMK2
                ];

                $res = odbc_execute($pstmt, $arrParams);
                if (!$res) {
                    $errMsg = "Errore query dettaglio riga " . $i . " : " . odbc_errormsg($conn);
                    $hasErr = true;
                }
            } else {
                $errMsg = "Errore preparazione query riga " . $i . " : " . odbc_errormsg($conn);
                $hasErr = true;
            }
        }

        if ($hasErr) {
            odbc_rollback($conn);
            echo '{"stat":"ERR","msg":' . json_encode($errMsg) . '}';
            error_log($errMsg);
        } else {
            odbc_commit($conn);
            echo '{"stat":"OK","msg":"Dati inseriti con successo"}';
        }
    } else {
        echo '{"stat":"ERR","msg":"Dati mancanti nella richiesta"}';
    }
}

odbc_close($conn);