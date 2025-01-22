<?php
//TEST: 
/*
{
	"fields":
	{
		"C1AN8" :"1012917",	
		"C1AT1" :"C",
		"C1MLNM":"Jaeger Gummi und Kunststoff GmbH",
		"C1ADD2":"Lohweg 1",
		"C1ADDZ":"30559",
		"C1CTY1":"Hannover",
		"C1ADDS":"",
		"C1LNGP":"E",
		"C1CTR" :"DE",
		"C1AC05":"E",
		"C1TRAR":"900",
		"C1RYIN":"B",
		"C1TXA1":"NI41",
		"C1CRCD":"EUR",
		"C1ARC" :"CCEE",
		"C1TAX" :"DE 813 314 161",
		"C1TX2" :"",
		"C1TAXC":"",
		"C1RMK":"",
		"C1FRTH":"EW",
		"C1CARS":"0",
		"C1HOLD":"",
		"C1CACT":"INT C/C",
		"C1RMK1":"",
		"C1KY2" :"",
		"C1KY3" :"",
		"C1KY4" :"",
		"C1KY5" :"",
		"C1INMG":"",
		"C1AC15":"RGP",
		"C1AC10":"",
		"C1AC11":"SI",
		"C1AC16":"",
		"C1AC17":""
	}
}
*/ 
 
header('Content-Type: application/json'); 
 
error_reporting(E_ALL); 
ini_set("display_errors",0);

include("config.inc.php"); 

$server="Driver={IBM i Access ODBC Driver};System=127.0.0.1;Uid=".DB2_USER.";Pwd=".DB2_PASS.";TRANSLATE=1;"; 
$user=DB2_USER; 
$pass=DB2_PASS; 

$postedBody = file_get_contents('php://input');

//connessione:
$time_start = microtime(true); 

$conn=odbc_connect($server,$user,$pass); 
if(!$conn) {
	echo odbc_errormsg($conn);
	exit;
}

$env = '';
if(isset($_REQUEST["env"])) $env = $_REQUEST["env"];
if($env=='') {
	die('{"stat":"err","msg":"parametro ambiente mancante"}');
}
if(!isset($envLib[$env])) {
	die('{"stat":"err","msg":"parametro ambiente errato"}');
}
$curLib = $envLib[$env];
  
$C1AN8 = 0;  
$resArray = json_decode($postedBody, true);
if($resArray) {
	if(isset($resArray['fields'])) {
		$C1AN8  = substr($resArray['fields']['C1AN8'],0,8);
		$C1AT1  = substr($resArray['fields']['C1AT1'],0,3);
		$C1MLNM = substr($resArray['fields']['C1MLNM'],0,40);
		$C1ADD2 = substr($resArray['fields']['C1ADD2'],0,40);
		$C1ADDZ = substr($resArray['fields']['C1ADDZ'],0,12);
		$C1CTY1 = substr($resArray['fields']['C1CTY1'],0,25);
		$C1ADDS = substr($resArray['fields']['C1ADDS'],0,3);
		$C1LNGP = substr($resArray['fields']['C1LNGP'],0,2);
		$C1CTR  = substr($resArray['fields']['C1CTR'],0,3);
		$C1AC05 = substr($resArray['fields']['C1AC05'],0,3);
		$C1TRAR = substr($resArray['fields']['C1TRAR'],0,3);
		$C1RYIN = substr($resArray['fields']['C1RYIN'],0,1);
		$C1TXA1 = substr($resArray['fields']['C1TXA1'],0,10);
		$C1CRCD = substr($resArray['fields']['C1CRCD'],0,3);
		$C1ARC  = substr($resArray['fields']['C1ARC'],0,4);
		$C1TAX  = substr($resArray['fields']['C1TAX'],0,20);
		$C1TX2  = substr($resArray['fields']['C1TX2'],0,20);
		$C1TAXC = substr($resArray['fields']['C1TAXC'],0,1);
		$C1RMK  = substr($resArray['fields']['C1RMK'],0,30);
		$C1FRTH = substr($resArray['fields']['C1FRTH'],0,3);
		$C1CARS = substr($resArray['fields']['C1CARS'],0,8);
		$C1HOLD = substr($resArray['fields']['C1HOLD'],0,2);
		$C1CACT = substr($resArray['fields']['C1CACT'],0,25);
		$C1RMK1 = substr($resArray['fields']['C1RMK1'],0,30);
		$C1KY2  = substr($resArray['fields']['C1KY2'],0,20);
		$C1KY3  = substr($resArray['fields']['C1KY3'],0,10);
		$C1KY4  = substr($resArray['fields']['C1KY4'],0,10);
		$C1KY5  = substr($resArray['fields']['C1KY5'],0,10);
		$C1INMG = substr($resArray['fields']['C1INMG'],0,10);
		$C1AC15 = substr($resArray['fields']['C1AC15'],0,3);
		$C1AC10 = substr($resArray['fields']['C1AC10'],0,3);
		$C1AC11 = substr($resArray['fields']['C1AC11'],0,3);
		$C1AC16 = substr($resArray['fields']['C1AC16'],0,3);
		$C1AC17 = substr($resArray['fields']['C1AC17'],0,3); 
	} else {
		$errMsg = "Invalid json";
		die('{"stat":"err","msg":'.json_encode($errMsg).'}');
	}
} else {
	$errMsg = "Invalid json";
	die('{"stat":"err","msg":'.json_encode($errMsg).'}');
}
 
if($C1AN8=='' || $C1AN8==0) {
	$errMsg = "Campo C1AN8 obbligatorio";
	die('{"stat":"err","msg":'.json_encode($errMsg).'}');
} 

//controllo che il record esista:
$query = "SELECT 1 AS CHKEXI FROM ".$curLib.".F55CLIEN WHERE C1AN8 = ? FETCH FIRST ROW ONLY"; 
$pstmt = odbc_prepare($conn,$query);
if($pstmt) {
	$arrParams = array();	
	$arrParams[] = trim($C1AN8);
	$resChk = odbc_execute($pstmt,$arrParams);
	if(!$resChk) {
		$errMsg = "Errore esecuzione query CHKEXI: ".odbc_errormsg();
		die('{"stat":"err","msg":'.json_encode($errMsg).'}');
	}
	$rowChk = odbc_fetch_array($pstmt);
	if(!isset($rowChk) || !isset($rowChk["CHKEXI"]) || $rowChk["CHKEXI"]!='1') {
		$errMsg = "Record non trovato per update";
		die('{"stat":"err","msg":'.json_encode($errMsg).'}');
	} 
}
//controllo che il record esista [f]
 
//inserimento:
$query = "UPDATE ".$curLib.".F55CLIEN SET 
C1AT1  = ?,
C1MLNM = ?,
C1ADD2 = ?,
C1ADDZ = ?,
C1CTY1 = ?,
C1ADDS = ?,
C1LNGP = ?,
C1CTR  = ?,
C1AC05 = ?,
C1TRAR = ?,
C1RYIN = ?,
C1TXA1 = ?,
C1CRCD = ?,
C1ARC  = ?,
C1TAX  = ?,
C1TX2  = ?,
C1TAXC = ?,
C1RMK  = ?,
C1FRTH = ?,
C1CARS = ?,
C1HOLD = ?,
C1CACT = ?,
C1RMK1 = ?,
C1KY2  = ?,
C1KY3  = ?,
C1KY4  = ?,
C1KY5  = ?,
C1INMG = ?,
C1AC15 = ?,
C1AC10 = ?,
C1AC11 = ?,
C1AC16 = ?,
C1AC17 = ?
WHERE C1AN8 = ?
WITH NC
";
$pstmt = odbc_prepare($conn,$query);
if($pstmt) {
	$arrParams = array();	
	$arrParams[] = trim($C1AT1);
	$arrParams[] = trim($C1MLNM);
	$arrParams[] = trim($C1ADD2);
	$arrParams[] = trim($C1ADDZ);
	$arrParams[] = trim($C1CTY1);
	$arrParams[] = trim($C1ADDS);
	$arrParams[] = trim($C1LNGP);
	$arrParams[] = trim($C1CTR);
	$arrParams[] = trim($C1AC05);
	$arrParams[] = trim($C1TRAR);
	$arrParams[] = trim($C1RYIN);
	$arrParams[] = trim($C1TXA1);
	$arrParams[] = trim($C1CRCD);
	$arrParams[] = trim($C1ARC);
	$arrParams[] = trim($C1TAX);
	$arrParams[] = trim($C1TX2);
	$arrParams[] = trim($C1TAXC);
	$arrParams[] = trim($C1RMK);
	$arrParams[] = trim($C1FRTH);
	$arrParams[] = trim($C1CARS);
	$arrParams[] = trim($C1HOLD);
	$arrParams[] = trim($C1CACT);
	$arrParams[] = trim($C1RMK1);
	$arrParams[] = trim($C1KY2);
	$arrParams[] = trim($C1KY3);
	$arrParams[] = trim($C1KY4);
	$arrParams[] = trim($C1KY5);
	$arrParams[] = trim($C1INMG);
	$arrParams[] = trim($C1AC15);
	$arrParams[] = trim($C1AC10);
	$arrParams[] = trim($C1AC11);
	$arrParams[] = trim($C1AC16);
	$arrParams[] = trim($C1AC17);
	$arrParams[] = trim($C1AN8);
	 
	$resIns = odbc_execute($pstmt,$arrParams);
	if(!$resIns) {
		$errMsg = "Errore esecuzione query : ".odbc_errormsg();
		die('{"stat":"err","msg":'.json_encode($errMsg).'}');
	}  
} else {
	$errMsg = "Errore prepare query : ".odbc_errormsg();
	die('{"stat":"err","msg":'.json_encode($errMsg).'}');
}
 
echo '{"stat":"OK"}';

odbc_close($conn); 