<?php
include("config.inc.php");

header('Content-Type: application/json; charset=utf-8');

set_time_limit(120);

ini_set('display_errors', 1);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set("log_errors", 1);
ini_set("error_log", "/www/php80/htdocs/logs/getNoteCliente/php-error.log");

$env = '';
if(isset($_REQUEST["env"])) $env = $_REQUEST["env"];
if($env=='') {
	$env='prod'; //per retrocompatibilità
}
$curLib = $envLib[$env];

$codCli = '';
if(isset($_REQUEST["codCli"])) $A3AN8 = $_REQUEST["codCli"];

$server="Driver={IBM i Access ODBC Driver};System=127.0.0.1;Uid=".DB2_USER.";Pwd=".DB2_PASS.";TRANSLATE=1;CONNTYPE=2;CMT=0;BLOCKFETCH=1;BLOCKSIZE=2000"; 
$user=DB2_USER; 
$pass=DB2_PASS; 
 
$conn=odbc_connect($server,$user,$pass); 
if(!$conn) {
	echo odbc_errormsg($conn);
	exit;
}

//query:
$arrNote = array();
$arrNote["note_tecniche"] = array();

$query = "select A3DS80 from ".$curLib.".F01093 where A3AN8=? and A3TYDT='NA' order by A3LIN FOR FETCH ONLY";
$pstmt = odbc_prepare($conn,$query);
if($pstmt) {
	$arrParams = array();	
	$arrParams[] = trim($A3AN8);	 
	
	$res = odbc_execute($pstmt,$arrParams);
	if($res) {
		while($row = odbc_fetch_array($pstmt)) { 
			$curNota = mb_convert_encoding($row["A3DS80"],"UTF-8","ISO-8859-9");
			$curNota = str_replace("§","@",$curNota); 
			$arrNote["note_tecniche"][] = trim($curNota); 
		}
	
	}
} 

$arrNote["note_magazzino"] = array();
$query = "select A3DS80 from ".$curLib.".F01093 where A3AN8=? and A3TYDT='NP' order by A3LIN";
$pstmt = odbc_prepare($conn,$query);
if($pstmt) {
	$arrParams = array();	
	$arrParams[] = trim($A3AN8);	 
	
	$res = odbc_execute($pstmt,$arrParams);
	if($res) {
		while($row = odbc_fetch_array($pstmt)) {
			$curNota = mb_convert_encoding($row["A3DS80"],"UTF-8","ISO-8859-9");
			$curNota = str_replace("§","@",$curNota); 
			$arrNote["note_magazzino"][] = trim($curNota);  
		}
	
	}
}

$arrNote["note_cliente"] = array();
$query = "select CYWTXT 
          from ".$curLib.".F00163 
          left join ".$curLib.".F0016 
          on ".$curLib.".F0016.CYSERK = ".$curLib.".F00163.C5SERK 
          where CAST(SUBSTRING(C5CKEY, 1, 8) AS INT) = ? 
          and C5WAPP = '*ADDNOTE'";

$pstmt = odbc_prepare($conn, $query);
if ($pstmt) {
    $arrParams = array();    
    $arrParams[] = (int) trim($A3AN8); // Converti in numero intero
    
    $res = odbc_execute($pstmt, $arrParams);
    if ($res) {
        while ($row = odbc_fetch_array($pstmt)) {
            $curNota = mb_convert_encoding($row["CYWTXT"], "UTF-8", "ISO-8859-9");
            $curNota = str_replace("§", "@", $curNota); 
            $arrNote["note_cliente"][] = trim($curNota);   
        } 
    } 
}
 
echo json_encode($arrNote);

odbc_close($conn);