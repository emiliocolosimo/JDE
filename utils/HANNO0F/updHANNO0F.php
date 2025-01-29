<?php
include("config.inc.php");

set_time_limit(0);

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set("log_errors", 1);
ini_set("error_log", "/www/php80/htdocs/utils/HANNO0F/logs/updHANNO0F.txt");

 
$server="Driver={IBM i Access ODBC Driver};System=127.0.0.1;Uid=".DB2_USER.";Pwd=".DB2_PASS.";TRANSLATE=1;"; 
$user=DB2_USER; 
$pass=DB2_PASS; 
 
$conn=odbc_connect($server,$user,$pass); 
if(!$conn) {
	echo odbc_errormsg($conn);
	exit;
}
odbc_setoption ($conn, 1, 103, 240);

$query = "DELETE FROM BCD_DATIV2.BCDST10F WITH NC";
odbc_exec($conn,$query);
$query = "DELETE FROM BCD_DATIV2.BCDST20F WITH NC";
odbc_exec($conn,$query);

$excludeWords = array("&","GMBH","CO.","CO,","KG","LTD","SNC","S.N.C.","SRL","S.R.L.","SPA","S.P.A.");


$query = "SELECT trim(WWAN8) as WWAN8,
trim(WWMLNM) as WWMLNM
FROM jrgdta94c.f0111   
";

$result=odbc_exec($conn,$query);
if(!$result) {
	echo '{"status":"ERROR","errmsg":"'.json_encode(odbc_errormsg()).'"}';
	exit;
}
 

while($row = odbc_fetch_array($result)){
		
		/*
		foreach(array_keys($row) as $key)
		{
			$row[$key] = utf8_encode(trim($row[$key]));
		}
		 
		*/
		
		$WWMLNM = $row["WWMLNM"];
		$WWMLNM = str_replace(array("ä","ü","ö","Ä","Ü","Ö"),array("a","u","o","a","u","o"),$WWMLNM);
		$WWMLNM = strtoupper($WWMLNM);		
		
		$arrWords = explode(" ",$WWMLNM);
		for($i=0;$i<count($arrWords);$i++) {
			$curWord = $arrWords[$i];
			if(trim($curWord)!="" && !in_array($curWord,$excludeWords)) {
				$query = "INSERT INTO BCD_DATIV2.BCDST10F (S1CODI, S1WORD) VALUES(?,?) WITH NC";
				$pstmt = odbc_prepare($conn,$query);
				if($pstmt) {
					$arrParams = array();	
					$arrParams[] = $row["WWAN8"];			
					$arrParams[] = $curWord;		 
					
					$res = odbc_execute($pstmt,$arrParams);
					if(!$res) {
						echo "Errore query S1 : ".odbc_errormsg(); 
					}
				}
			}
		}
}


$query = "SELECT trim(HAORGA) as HAORGA 
FROM jrgdta94c.HANNO0F  
";

$result=odbc_exec($conn,$query);
if(!$result) {
	echo '{"status":"ERROR","errmsg":"'.json_encode(odbc_errormsg()).'"}';
	exit;
}
 
$time_start = microtime(true); 
$r = 0;
while($row = odbc_fetch_array($result)){
		/*
		foreach(array_keys($row) as $key)
		{
			$row[$key] = utf8_encode(trim($row[$key]));
		}
		*/
		
		$HAORGA = $row["HAORGA"];
		$HAORGA = str_replace(array("ä","ü","ö","Ä","Ü","Ö"),array("a","u","o","a","u","o"),$HAORGA);
		$HAORGA = strtoupper($HAORGA);
		 
		$arrWords = explode(" ",$HAORGA);
		for($i=0;$i<count($arrWords);$i++) {
			$curWord = $arrWords[$i];
			if(trim($curWord)!="" && !in_array($curWord,$excludeWords)) {
				$query = "INSERT INTO BCD_DATIV2.BCDST20F (S2ORGA, S2WORD) VALUES(?,?) WITH NC";
				$pstmt = odbc_prepare($conn,$query);
				if($pstmt) {
					$arrParams = array();	
					$arrParams[] = $HAORGA;			
					$arrParams[] = $curWord;		 
					
					$res = odbc_execute($pstmt,$arrParams);
					if(!$res) {
						echo "Errore query S2 : ".odbc_errormsg(); 
					}
				}
			}
		}
}