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

$query = "SELECT ICLITM 
FROM JRGDTA94C.F41166 
WHERE ICLITM like '%+%' or ICLITM like '%-%'  
";
/*
IMDSC1 like '%+%' or IMDSC1 like '%-%'  
AND
*/

$res = odbc_exec($conn,$query);

$cntOk = 0;
$cntErr = 0;

while($row = odbc_fetch_array($res)) {

	//posizione simbolo: 
	$itemDesc = trim($row["ICLITM"]);
	$posSimbolo = strrpos($itemDesc,"+");
	if($posSimbolo!==false) $simbolo = "+";
	if($posSimbolo==false) {
		$simbolo = "-";
		$posSimbolo = strrpos($itemDesc,"-");
	}
	
	//prendi i 6 numeri prima del simbolo e metti una virgola tra il terzo e il quarto
	$num6 = substr($itemDesc,$posSimbolo-6,6);
	//echo "num6=".$num6."<br>";
	$num6v = filter_var(substr($num6,0,3), FILTER_SANITIZE_NUMBER_INT).",".filter_var(substr($num6,3), FILTER_SANITIZE_NUMBER_INT);
	$num6v = ltrim($num6v, '0');
	//poi metti la dicitura “MM”
	$num6MM = $num6v." MM";
	//e infine il simbolo 
	$num6MM = $num6MM." ".$simbolo;
	//seguito dai numeri dopo il simbolo e infine il simbolo µ
	$numSimb = (int) filter_var(substr($itemDesc,$posSimbolo+1), FILTER_SANITIZE_NUMBER_INT);;
	$num6MM = $num6MM.$numSimb."µ";
	//echo utf8_decode($num6MM)."<br>";
	
	$ICR3UD = utf8_decode($num6MM); 
	$ICLITM = $itemDesc;
	$query = "UPDATE JRGDTA94C.F41166 SET ICR3UD = ? WHERE ICLITM = ? WITH NC";
	$pstmt = odbc_prepare($conn,$query);
	if($pstmt) {
		$arrParams = array();	
		$arrParams[] = trim($ICR3UD);			
		$arrParams[] = $ICLITM;		 
		
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
