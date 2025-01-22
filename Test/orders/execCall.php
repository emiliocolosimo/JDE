<?php
//richiamare solo per test
exit;

date_default_timezone_set("Europe/Rome");

//header('Content-Type: application/json'); 
error_reporting(E_ALL); 
ini_set("display_errors",1);

require("envs.order.php");
require("fields.order.php");
require("classes/headerInserter.class.php"); 
require("classes/detailInserter.class.php"); 
require("classes/noteInserter.class.php"); 
include("config.inc.php"); 
require_once('Toolkit.php');
require_once('ToolkitService.php');

//ESEGUO LA CALL:
$tkconn = ToolkitService::getInstance('*LOCAL', DB2_USER, DB2_PASS);
if(!$tkconn) {
	$errMsg = 'Error connecting toolkitService. Code: ' . $tkconn->getErrorCode() . ' Msg: ' . $tkconn->getErrorMsg();
	echo '{"stat":"error","msg":'.json_encode($errMsg).'}';
	exit;
}
$tkconn->setOptions(array('stateless' => true));

var_dump($envLibList['test']);

$res = $tkconn->CLCommand("CHGLIBL LIBL(".$envLibList['test'].")"); 
if (!$res) {
	$errMsg = 'Error setting library list. Code: ' . $tkconn->getErrorCode() . ' Msg: ' . $tkconn->getErrorMsg();
	die('{"stat":"err", "errors": [{"field":"", "msg":'.json_encode($errMsg).'}]}');
	exit;			
} 		

$res = $tkconn->CLCommand("CALL J40211Z ('P40211Z' 'RG0004CRM')"); 
if (!$res) {
	$errMsg = 'Error calling J40211Z. Code: ' . $tkconn->getErrorCode() . ' Msg: ' . $tkconn->getErrorMsg();
	die('{"stat":"err", "errors": [{"field":"", "msg":'.json_encode($errMsg).'}]}');
	exit;			
} 	
