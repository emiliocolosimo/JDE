<?php
 
header('Content-Type: text/html; charset=iso-8859-1'); 
 
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

$query = "SELECT IMITM,IMDSC1 
FROM JRGDTA94C.f4101 
WHERE IMDSC1 like '%/%'  
";
$res = odbc_exec($conn,$query);

$cntOk = 0;
$cntErr = 0;

while($row = odbc_fetch_array($res)) {
	$itemCode =  trim($row["IMITM"]);
	$itemDesc = trim($row["IMDSC1"]);
	$carPos = strrpos($itemDesc,"/");
	$rightBlank = strpos($itemDesc," ",$carPos);
	if($rightBlank==0) $rightBlank = strlen($itemDesc);
	 
	$leftBlank = strrpos(substr($itemDesc,0,$carPos)," ");
	
	//per quelli che hanno i doppi apici mi fermo li:
	$doubleQuotesPos = strpos($itemDesc,"\"",$leftBlank);
	if($doubleQuotesPos>0) {
		if($doubleQuotesPos<$rightBlank) $rightBlank = $doubleQuotesPos + 1;
	}
	
	$ICR2UD = substr($itemDesc,$leftBlank,$rightBlank-$leftBlank);
	$ICITM = $itemCode;
	$query = "UPDATE JRGDTA94C.F41166 SET ICR2UD = ? WHERE ICITM = ? WITH NC";
	$pstmt = odbc_prepare($conn,$query);
	if($pstmt) {
		$arrParams = array();	
		$arrParams[] = trim($ICR2UD);			
		$arrParams[] = $ICITM;		 
		
		$resIns = odbc_execute($pstmt,$arrParams);
		if(!$resIns) {
			$errMsg = "Errore query testata : ".odbc_errormsg();
			echo $errMsg;
			var_dump($arrParams);
			$cntErr++;
		} else {
			$cntOk++;
		}
	}
	 
}

echo 'Righe ok:'.$cntOk.', Righe err:'.$cntErr;

odbc_close($conn);
