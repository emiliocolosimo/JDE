<?php

date_default_timezone_set("Europe/Rome");

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set("display_errors", 0);

require("envs.order.php");
require("fields.order.php");
require("classes/utils.class.php");
require("classes/headerInserter.class.php");
require("classes/detailInserter.class.php");
require("classes/noteInserter.class.php");
include("config.inc.php");
require_once('Toolkit.php');
require_once('ToolkitService.php');

$server = "Driver={IBM i Access ODBC Driver};System=127.0.0.1;Uid=" . DB2_USER . ";Pwd=" . DB2_PASS . ";TRANSLATE=1;";
$user = DB2_USER;
$pass = DB2_USER;
$conn = odbc_connect($server, $user, $pass);

if (!$conn) {
    die('{"stat":"err", "errors": [{"field":"", "msg":"connection error"}]}');
}
odbc_autocommit($conn, false);

$postedBody = file_get_contents('php://input');

//file_put_contents("/www/php80/htdocs/orders/debug/".date("Ymd His").".txt",$postedBody);

$env = '';
$hasError = false;
$resArray = json_decode($postedBody, true);
if (!$resArray) {
    die('{"stat":"err", "errors": [{"field":"", "msg":"invalid json"}]}');
}
if (!isset($resArray['F47011'])) {
    die('{"stat":"err", "errors": [{"field":"", "msg":"missing F0111"}]}');
}

/********** CONTROLLI: ************/

$headerInserter = new headerInserter();
$headerInserter->setConnection($conn);
$headerInserter->setInputFields($inputFields["F47011"], $fieldType["F47011"], $fieldSize["F47011"]);
$headerInserter->setCostantFields($costantFields["F47011"]);
$headerInserter->setMandatoryFields($mandatoryFields["F47011"]);
$headerInserter->setFieldsReference($fieldsReference["F47011"]);
$headerInserter->setEnvLib($envLib);
$headerInserter->setValidator($validator["F47011"]);

$headerInserter->setEnv($resArray['env']);
$res = $headerInserter->checkMandatoryFields($resArray['F47011']['fields']);
if ($res["hasErrors"]) {
    die('{"stat":"err", "errors":' . json_encode($res["arrErrors"]) . '}');
}

$res = $headerInserter->validateFields();
if ($res["hasErrors"]) {
    die('{"stat":"err", "errors":' . json_encode($res["arrErrors"]) . '}');
}

//righe:
for ($i = 0; $i < count($resArray['F47012']); $i++) {
    $currentResArray = $resArray['F47012'][$i];

    $detailInserter = new detailInserter();
    $detailInserter->setConnection($conn);
    $detailInserter->setHeaderFields($resArray['F47011']['fields']);
    $detailInserter->setInputFields($inputFields["F47012"], $fieldType["F47012"], $fieldSize["F47012"]);
    $detailInserter->setCostantFields($costantFields["F47012"]);
    $detailInserter->setMandatoryFields($mandatoryFields["F47012"]);
    $detailInserter->setFieldsReference($fieldsReference["F47012"]);
    $detailInserter->setEnvLib($envLib);
    $detailInserter->setValidator($validator["F47012"]);

    $detailInserter->setEnv($resArray['env']);
    $res = $detailInserter->checkMandatoryFields($currentResArray);
    if ($res["hasErrors"]) {
        die('{"stat":"err", "errors":' . json_encode($res["arrErrors"]) . '}');
    }

    $res = $detailInserter->validateFields();
    if ($res["hasErrors"]) {
        die('{"stat":"err", "errors":' . json_encode($res["arrErrors"]) . '}');
    }
}

//note di riga:
for ($i = 0; $i < count($resArray['F4715']); $i++) {
    $currentResArray = $resArray['F4715'][$i];

    $noteInserter = new noteInserter();
    $noteInserter->setConnection($conn);
    $noteInserter->setHeaderFields($resArray['F47011']['fields']);
    $noteInserter->setInputFields($inputFields["F4715"], $fieldType["F4715"], $fieldSize["F4715"]);
    $noteInserter->setCostantFields($costantFields["F4715"]);
    $noteInserter->setMandatoryFields($mandatoryFields["F4715"]);
    $noteInserter->setFieldsReference($fieldsReference["F4715"]);
    $noteInserter->setEnvLib($envLib);
    $noteInserter->setValidator($validator["F4715"]);

    $noteInserter->setEnv($resArray['env']);
    $res = $noteInserter->checkMandatoryFields($currentResArray);
    if ($res["hasErrors"]) {
        die('{"stat":"err", "errors":' . json_encode($res["arrErrors"]) . '}');
    }

    $res = $noteInserter->validateFields();
    if ($res["hasErrors"]) {
        die('{"stat":"err", "errors":' . json_encode($res["arrErrors"]) . '}');
    }
}

/********** INSERIMENTO: ************/
$res = $headerInserter->execInsert();
if ($res["hasErrors"]) {
    odbc_rollback($conn);
    odbc_close($conn);
    die('{"stat":"err", "errors":' . json_encode($res["arrErrors"]) . '}');
}

$orderNumber = $res["orderNumber"];

for ($i = 0; $i < count($resArray['F47012']); $i++) {
    $currentResArray = $resArray['F47012'][$i];

    $detailInserter = new detailInserter();
    $detailInserter->setConnection($conn);
    $detailInserter->setOrderNumber($orderNumber);
    $detailInserter->setHeaderFields($resArray['F47011']['fields']);
    $detailInserter->setInputFields($inputFields["F47012"], $fieldType["F47012"], $fieldSize["F47012"]);
    $detailInserter->setCostantFields($costantFields["F47012"]);
    $detailInserter->setMandatoryFields($mandatoryFields["F47012"]);
    $detailInserter->setFieldsReference($fieldsReference["F47012"]);
    $detailInserter->setEnvLib($envLib);
    $detailInserter->setValidator($validator["F47012"]);

    $detailInserter->setEnv($resArray['env']);
    $res = $detailInserter->checkMandatoryFields($currentResArray);
    $res = $detailInserter->validateFields();
    $res = $detailInserter->execInsert();
    if ($res["hasErrors"]) {
        odbc_rollback($conn);
        odbc_close($conn);
        die('{"stat":"err", "errors":' . json_encode($res["arrErrors"]) . '}');
    }
}

if (isset($resArray['F4715'])) {
    for ($i = 0; $i < count($resArray['F4715']); $i++) {
        $currentResArray = $resArray['F4715'][$i];

        $noteInserter = new noteInserter();
        $noteInserter->setOrderNumber($orderNumber);
        $noteInserter->setConnection($conn);
        $noteInserter->setHeaderFields($resArray['F47011']['fields']);
        $noteInserter->setInputFields($inputFields["F4715"], $fieldType["F4715"], $fieldSize["F4715"]);
        $noteInserter->setCostantFields($costantFields["F4715"]);
        $noteInserter->setMandatoryFields($mandatoryFields["F4715"]);
        $noteInserter->setFieldsReference($fieldsReference["F4715"]);
        $noteInserter->setEnvLib($envLib);
        $noteInserter->setValidator($validator["F4715"]);

        $noteInserter->setEnv($resArray['env']);
        $res = $noteInserter->checkMandatoryFields($currentResArray);
        $res = $noteInserter->validateFields();
        $res = $noteInserter->execInsert();
        if ($res["hasErrors"]) {
            odbc_rollback($conn);
            odbc_close($conn);
            die('{"stat":"err", "errors":' . json_encode($res["arrErrors"]) . '}');
        }
    }
}

odbc_commit($conn);
odbc_close($conn);


//ESEGUO LA CALL:
$tkconn = ToolkitService::getInstance('*LOCAL', DB2_USER, DB2_PASS);
if (!$tkconn) {
    $errMsg = 'Error connecting toolkitService. Code: ' . $tkconn->getErrorCode() . ' Msg: ' . $tkconn->getErrorMsg();
    die('{"stat":"OK", "warnings": [{"field":"", "msg":' . json_encode($errMsg) . '}]}');
    exit;
}
$tkconn->setOptions(array('stateless' => true));

$res = $tkconn->CLCommand("CHGLIBL LIBL(" . $envLibList[$resArray['env']] . ")");
if (!$res) {
    $errMsg = 'Error setting library list. Code: ' . $tkconn->getErrorCode() . ' Msg: ' . $tkconn->getErrorMsg();
    die('{"stat":"OK", "warnings": [{"field":"", "msg":' . json_encode($errMsg) . '}]}');
    exit;
}

$res = $tkconn->CLCommand("CALL J40211Z ('P40211Z' 'RG0004CRM')");
if (!$res) {
    $errMsg = 'Error calling J40211Z. Code: ' . $tkconn->getErrorCode() . ' Msg: ' . $tkconn->getErrorMsg();
    die('{"stat":"OK", "warnings": [{"field":"", "msg":' . json_encode($errMsg) . '}]}');
    exit;
}

echo '{"stat":"OK" ,' . '"OrderNumber": "' . $orderNumber . '"}';