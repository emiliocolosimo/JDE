<?php
 
header('Content-Type: application/json'); 
 
error_reporting(E_ALL); 

include("config.inc.php"); 

$server="Driver={IBM i Access ODBC Driver};System=127.0.0.1;Uid=".DB2_USER.";Pwd=".DB2_PASS.";TRANSLATE=1;"; 
$user=DB2_USER; 
$pass=DB2_PASS; 

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


//estraggo numeratore:
$query = "SELECT NNN001 
FROM ".$curLib.".F0002 
WHERE NNSY = '01' 
";
$res = odbc_exec($conn,$query);
if(!$res) {
	$errMsg = "Errore query estrazione numeratore : ".odbc_errormsg();
	die('{"stat":"err","msg":'.json_encode($errMsg).'}');
}
$row = odbc_fetch_array($res);
$NNN001 = $row["NNN001"];

//incremento numeratore:
$nextNum = $NNN001 + 1;
$query = "UPDATE ".$curLib.".F0002 SET NNN001 = ".$nextNum." WHERE NNSY = '01' WITH NC";
$res = odbc_exec($conn,$query);
if(!$res) {
	$errMsg = "Errore query aggiornamento numeratore : ".odbc_errormsg();
	die('{"stat":"err","msg":'.json_encode($errMsg).'}');
}

echo '{"stat":"OK","NNN001":'.json_encode($NNN001).'}';

odbc_close($conn);
