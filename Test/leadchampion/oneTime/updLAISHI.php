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

$query = "SELECT LAID, UPPER(LACONA) AS LACONA  
FROM JRGDTA94C.LCCOMP0F 
WHERE LAISHI <> '1' 
";
$res = odbc_exec($conn,$query);
while($row = odbc_fetch_array($res)) {
	$LAID = $row["LAID"];
	$LACONA = $row["LACONA"];
	 
	$arrWordsHide = array("UNIVERSIT","ECOLE","SCHULE","SCUOLA","ISTITUT","INSTITUT");
	$containsWordHide = false;
	for($wo=0;$wo<count($arrWordsHide) && !$containsWordHide;$wo++) {
		if(strpos($LACONA, $arrWordsHide[$wo])!==false) $containsWordHide = true;
	}	
	if($containsWordHide) { 
		$query = "UPDATE JRGDTA94C.LCCOMP0F SET LAISHI = '1' WHERE LAID = ".$LAID; 
		$resUpd = odbc_exec($conn,$query);
		if($resUpd) echo 'Aggiornato ID'.$LAID;
		else echo 'Errore aggiornamento ID'.$LAID;
	} 
}
		 