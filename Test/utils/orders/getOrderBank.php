<?php
 
header('Content-Type: application/json; charset=iso-8859-1'); 
 
error_reporting(E_ALL); 

include("config.inc.php"); 
/*
$k = '';
if(isset($_REQUEST['k'])) $k = $_REQUEST["k"];
if($k!="sJHsdwvIFTyhDuGtZoOfevsgG1A1H2s6") { 
	exit;
}
*/
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

/*
SHDOCO (Order Number)
SHDCTO (Order Type)        
SHKCOO (Order Company)
*/

$SHRYIN = '';
if(isset($_REQUEST["SHRYIN"])) $SHRYIN = trim($_REQUEST["SHRYIN"]);
$SHAN8 = '';
if(isset($_REQUEST["SHAN8"])) $SHAN8 = trim($_REQUEST["SHAN8"]);  
$SHCACT = '';
if(isset($_REQUEST["SHCACT"])) $SHCACT = trim($_REQUEST["SHCACT"]);  


if($SHRYIN=='') die('{"stat":"err","msg":"Parametro Payment Method obbligatorio"}');
//if($SHAN8=='') die('{"stat":"err","msg":"Parametro Customer Number obbligatorio"}');
if($SHCACT=='') die('{"stat":"err","msg":"Parametro Bank Code obbligatorio"}');

 
 
/*
2) per quanto riguarda la banca, Emilio voleva che ti indicassi come reperire IBAN e nome  banca così come stampati in conferma d'ordine.
Queste informazioni vengono reperite in 2 diversi modi, a seconda del metodo di pagamento in ordine.
Premessa

- se il cliente paga con Riba (metodo pagamento "R" nel campo SHRYIN del file F4201 (testata ordine):
    IBAN = campo AYIBAN del file F0030 letto per 
     - AYAN8 = SHAN8 (cliente presente nel file testata ordini F4201)
     - AYBKTP = "D"
    se AYIBAN vuoto, allora stampa <ABI> - <CAB> dove <ABI> = caratteri 1-5 di AYTNST e <CAB> = caratteri 6-10 di AYTNST
     NOME BANCA = campo BAIBNO del file F74030 (anagrafica banche) letto per BAIBAB = <ABI> e BAIBCA = <CAB>

- se il cliente paga con metodo pagamento diverso da Riba:  
  IBAN = campo AYIBAN del file F0030 letto per 
     - AYAID = conto banca preso nel campo DRSPHD del file F0005 letto per DRSY = "55  ", DRRT = "BK" 
	 e DRKY = SHCACT (codice banca presente in testata ordine F4201)
     - AYBKTP = "G"
  NOME BANCA = campo BAIBNO del file F74030 (anagrafica banche) letto per BAIBAB = <ABI> (caratteri 1-5 di AYTNST) e BAIBCA = <CAB> (caratteri 6-10 di AYTNST)
*/
 
 

$AYIBAN = '';
$ABI = '';
$CAB = '';
$BAIBNO = '';
$AYCBNK = '';
$AYDL01 = '';
$AYTNST = '';

if($SHRYIN=='R') {
	$query = "SELECT TRIM(AYIBAN) AS AYIBAN, 
	TRIM(SUBSTR(AYTNST, 1, 5)) AS ABI, 
	TRIM(SUBSTR(AYTNST, 6, 10)) AS CAB, 
	TRIM(BAIBNO) AS BAIBNO, 
	TRIM(AYCBNK) AS AYCBNK, 
	TRIM(AYDL01) AS AYDL01,
	TRIM(SUBSTR(AYTNST, 1, 10)) AS AYTNST 
	FROM JRGDTA94C.F0030 
	JOIN JRGDTA94C.F74030 ON BAIBAB = SUBSTR(AYTNST, 1, 5) AND BAIBCA = SUBSTR(AYTNST, 6, 10) 
	WHERE AYBKTP = 'D' 
	";
	if($SHAN8!="") $query .= " AND AYAN8 = ? ";
	
	$pstmt = odbc_prepare($conn,$query);
	if($pstmt) {
		$arrParams = array();	
		if($SHAN8!="") $arrParams[] = trim($SHAN8); 
		 
		$res = odbc_execute($pstmt,$arrParams);
		if($res) {
			$rp = 0;
			if($SHAN8=="") echo '[';
			while($row = odbc_fetch_array($pstmt)) {
				if(isset($row["AYIBAN"])) $AYIBAN = utf8_encode($row["AYIBAN"]);
				if(isset($row["ABI"])) $ABI = utf8_encode($row["ABI"]);
				if(isset($row["CAB"])) $CAB = utf8_encode($row["CAB"]);
				if(isset($row["BAIBNO"])) $BAIBNO = utf8_encode($row["BAIBNO"]);
				if(isset($row["AYCBNK"])) $AYCBNK = utf8_encode($row["AYCBNK"]);
				if(isset($row["AYDL01"])) $AYDL01 = utf8_encode($row["AYDL01"]);
				if(isset($row["AYTNST"])) $AYTNST = utf8_encode($row["AYTNST"]);
				
				if($rp>0) echo ',';
				
				echo '{';
				echo '"payment_method":'.json_encode($SHRYIN);
				if($AYIBAN!="") echo ', "IBAN_descr":'.json_encode($AYIBAN);
				else echo ', "IBAN_descr":'.json_encode($ABI.' - '.$CAB);
				echo ', "bank_descr":'.json_encode($BAIBNO); 
				echo ', "AYCBNK":'.json_encode($AYCBNK);
				echo ', "AYDL01":'.json_encode($AYDL01);
				echo ', "AYTNST":'.json_encode($AYTNST);
				echo '}';
				
				$rp++;
				 
			}
			if($SHAN8=="") echo ']';
		}
	}
	

}
else 
{
 
	//conto banca preso nel campo DRSPHD del file F0005 letto per DRSY = "55  ", DRRT = "BK" e DRKY = SHCACT (codice banca presente in testata ordine F4201)
	$DRSPHD = '';
	$query = "SELECT TRIM(DRSPHD) AS DRSPHD
	FROM JRGCOM94T.F0005 
	WHERE DRSY='55' 
	AND DRRT='BK' 
	AND DRKY=?
	";
	$pstmt = odbc_prepare($conn,$query);
	if($pstmt) {
		$arrParams = array();	
		$arrParams[] = trim($SHCACT); 
		 
		$res = odbc_execute($pstmt,$arrParams);
		if($res) {
			$row = odbc_fetch_array($pstmt);
			if(isset($row["DRSPHD"])) $DRSPHD = utf8_encode($row["DRSPHD"]);
			
			$query = "SELECT TRIM(AYIBAN) AS AYIBAN, 
			TRIM(SUBSTR(AYTNST, 1, 5)) AS ABI, 
			TRIM(SUBSTR(AYTNST, 6, 10)) AS CAB, 
			TRIM(BAIBNO) AS BAIBNO 
			FROM JRGDTA94C.F0030 
			JOIN JRGDTA94C.F74030 ON BAIBAB = SUBSTR(AYTNST, 1, 5) AND BAIBCA = SUBSTR(AYTNST, 6, 10) 
			WHERE AYAID = ? 
			AND AYBKTP = 'G' 
			";
			$pstmt2 = odbc_prepare($conn,$query);
			if($pstmt2) {
				$arrParams = array();	
				$arrParams[] = trim($DRSPHD); 
				 
				$res2 = odbc_execute($pstmt2,$arrParams);
				if($res2) {
					$row2 = odbc_fetch_array($pstmt2);
					if(isset($row2["AYIBAN"])) $AYIBAN = utf8_encode($row2["AYIBAN"]);
					if(isset($row2["ABI"])) $ABI = utf8_encode($row2["ABI"]);
					if(isset($row2["CAB"])) $CAB = utf8_encode($row2["CAB"]);
					if(isset($row2["BAIBNO"])) $BAIBNO = utf8_encode($row2["BAIBNO"]);
				}
			}
			
			echo '{';
			echo '"payment_method":'.json_encode($SHRYIN);
			if($AYIBAN!="") echo ', "IBAN_descr":'.json_encode($AYIBAN);
			else echo ', "IBAN_descr":'.json_encode($ABI.' - '.$CAB);
			echo ', "bank_descr":'.json_encode($BAIBNO);
			echo '}';
			
		}
	}
}
  
odbc_close($conn);
