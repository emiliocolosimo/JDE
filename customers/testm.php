<?php

header('Content-Type: application/json'); 
error_reporting(E_ALL); 
ini_set("display_errors",1);


include("config.inc.php"); 
 
 
$server="Driver={IBM i Access ODBC Driver};System=127.0.0.1;Uid=".DB2_USER.";Pwd=".DB2_PASS.";TRANSLATE=1;"; 
$user=DB2_USER; 
$pass=DB2_USER;   
$conn=odbc_connect($server,$user,$pass); 
//!!! disabilitare autocommit
if(!$conn) {
	die('{"stat":"err", "error":"Connection error"}');
}
odbc_autocommit($conn,false); 
  
$postedBody = file_get_contents('php://input');  
$resArray = json_decode($postedBody, true);
  
$mail = $resArray["mail"]; 

$query = "INSERT INTO BCD_DATIV2.TESTM (MAILA) VALUES(Cast(Cast(? As Char(100) CCSID 65535)       
   As Char(100) CCSID 37)) WITH NC";
$pstmt = odbc_prepare($conn,$query);
$arrParams = array();	
$arrParams[] = mb_convert_encoding($mail, "ISO-8859-1", "UTF-8");   
$res = odbc_execute($pstmt,$arrParams);