<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
 
include("/www/php80/htdocs/leadchampion/config.inc.php"); 

$server="Driver={IBM i Access ODBC Driver};System=127.0.0.1;Uid=".DB2_USER.";Pwd=".DB2_PASS.";TRANSLATE=1;"; 
$user=DB2_USER; 
$pass=DB2_PASS;   
$conn=odbc_connect($server,$user,$pass); 
if(!$conn) {
	$errMsg = "Errore connessione al database : ".odbc_errormsg($conn);
	echo $errMsg;
	exit;
}

if(isset($_REQUEST["ID"])) $LAID = $_REQUEST["ID"];
else die('{"stat":"ERR","errTxt":"Specificare il parametro ID"}');

$LAID = (int) $LAID;

$query = "UPDATE JRGDTA94C.LCCOMP0F SET LAISHI = '1' WHERE LAID = ".$LAID;
$resUpd = odbc_exec($conn,$query);
if($resUpd) echo '{"stat":"OK"}';
else echo '{"stat":"ERR","errTxt":"'.odbc_errormsg($conn).'"}';
