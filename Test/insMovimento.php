<?php
/*
in:

{"details":
	[
		{"MJEDLN":"1000","MJLITM":"articolo 1","MJLOCN":"ubicazione 1","MJLOTN":"lotto 1","MJTRQT":"100","MJUNCS":"1000000"},
		{"MJEDLN":"2000","MJLITM":"articolo 2","MJLOCN":"ubicazione 2","MJLOTN":"lotto 2","MJTRQT":"100","MJUNCS":"1000000"},
		{"MJEDLN":"3000","MJLITM":"articolo 3","MJLOCN":"ubicazione 3","MJLOTN":"lotto 3","MJTRQT":"100","MJUNCS":"1000000"}
	]
}
*/
  
include("config.inc.php");
require_once('Toolkit.php');
require_once('ToolkitService.php');

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set("log_errors", 1);
ini_set("error_log", "/www/php80/htdocs/logs/ins/php-error.log");

header('Content-Type: application/json; charset=utf-8');

$k = '';
if(isset($_REQUEST['k'])) $k = $_REQUEST["k"];
if($k!="sJHsdwvIFTyhDuGtZoOfevsgG1A1H2s6") { 
	exit;
}

$postedBody = file_get_contents('php://input');


$server="Driver={IBM i Access ODBC Driver};System=127.0.0.1;Uid=".DB2_USER.";Pwd=".DB2_PASS.";TRANSLATE=1;"; 
$user=DB2_USER; 
$pass=DB2_PASS;   
$conn=odbc_connect($server,$user,$pass); 
if(!$conn) {
	$errMsg = "Errore connessione al database : ".odbc_errormsg($conn);
	echo '{"stat":"ERR","msg":'.json_encode($errMsg).'}';
	exit;
}

$hasErr = false;

$resArray = json_decode($postedBody, true); 
if($resArray) {
	if(isset($resArray['details'])) {
		//call per recupero progressivo:
		$tkconn = ToolkitService::getInstance('*LOCAL', DB2_USER, DB2_PASS);
		if(!$tkconn) {
			$errMsg = 'Error connecting toolkitService. Code: ' . $conn->getErrorCode() . ' Msg: ' . $conn->getErrorMsg();
			echo '{"stat":"error","msg":'.json_encode($errMsg).'}';
			exit;
		}
		$tkconn->setOptions(array('stateless' => true));
		
		$res = $tkconn->CLCommand("CHGLIBL LIBL(QGPL QTEMP JRGOBJ94P JRGDTA94T JRGSRC94P JRGSEC94 JDWOBJ94 JDWSRC94 JRGCOM94T JRGSPE94C JRGPFIL JRGPPGM)");
		if (!$res) {
			$errMsg = 'Error setting library list. Code: ' . $conn->getErrorCode() . ' Msg: ' . $conn->getErrorMsg();
			echo '{"stat":"error","msg":'.json_encode($errMsg).'}';
			exit;			
		} 		
		
		$params = []; 
		$params[] = $tkconn->AddParameterChar('both', 4,'PSSY', 'PSSY', '47  ');
		$params[] = $tkconn->AddParameterChar('both', 2,'PSIDX', 'PSIDX', '01'); 
		$params[] = $tkconn->AddParameterPackDec('both', 8, 0, 'NXTNO', 'NXTNO', 0); 
		  
		$res = $tkconn->PgmCall('X0010', 'JDWOBJ94', $params, null, null);
		 
		if (!$res) {
			$errMsg = 'Error calling program. Code: ' . $tkconn->getErrorCode() . ' Msg: ' . $tkconn->getErrorMsg();
			echo '{"stat":"error","msg":'.json_encode($errMsg).'}';
			exit;
		}
		 
		$M1EDOC = $res["io_param"]["NXTNO"];
		if(!is_numeric($M1EDOC) || $M1EDOC==0) {
			$errMsg = 'Errore nel recupero del progressivo. M1EDOC='.$M1EDOC;
			echo '{"stat":"error","msg":'.json_encode($errMsg).'}';
			exit;
		}
		 
		//dati di testata:
		$M1EDTY = "H";     
		$M1EKCO = "00001";    
		$M1EDCT = "IL";  
		$M1EDLN = "1000";
		$M1EDST = "852"; 
		$M1EDER = "R";
		$M1THCD = "H";
		$M1AN8 = "1";
		$M1VR01 = "Lavorazione Interna";
		$M1USER = "RGPSPY"; 

		//scrittura testata:
		$headOk = false;
		//scrittura testata:
		$query = "INSERT INTO JRGDTA94T.F47121 
		(M1EDOC,M1EDTY,M1EKCO,M1EDCT,M1EDLN,M1EDST,M1EDER,M1THCD,M1AN8,M1VR01,M1USER) 
		VALUES(?,?,?,?,?,?,?,?,?,?,?)";

		$pstmt = odbc_prepare($conn,$query);
		if($pstmt) {
			$arrParams = array();	
			$arrParams[] = $M1EDOC;			
			$arrParams[] = $M1EDTY;			
			$arrParams[] = $M1EKCO;			
			$arrParams[] = $M1EDCT;			
			$arrParams[] = $M1EDLN;			
			$arrParams[] = $M1EDST;			
			$arrParams[] = $M1EDER;			
			$arrParams[] = $M1THCD;			
			$arrParams[] = $M1AN8;	
			$arrParams[] = $M1VR01;
			$arrParams[] = $M1USER;
			
			$res = odbc_execute($pstmt,$arrParams);
			if($res) {
				$headOk = true;
			} else {
				$errMsg = "Errore query testata : ".odbc_errormsg();
				$hasErr = true;
			}
		}
		 
		//scrittura righe: 
		if(!$hasErr) {
			$arrDetails = $resArray['details'];
			for($i=0;$i<count($arrDetails) && !$hasErr;$i++) {
				
				$MJEDTY="D";
				$MJEKCO="00001";
				$MJEDOC=$M1EDOC;
				$MJEDCT="IL";
				$MJEDLN=$arrDetails[$i]["MJEDLN"];
				$MJEDST="852";
				$MJEDER="R";
				$MJPACD="QO";
				$MJKSEQ="1";
				$MJMCU ="      RGPM01";
				$MJLITM=$arrDetails[$i]["MJLITM"];
				$MJLOCN=$arrDetails[$i]["MJLOCN"];
				$MJLOTN=$arrDetails[$i]["MJLOTN"];
				$MJTRQT=$arrDetails[$i]["MJTRQT"];
				$MJUNCS=$arrDetails[$i]["MJUNCS"];
				$MJTREX="Lavorazione Interna";
				$MJUSER="RGPSPY";
				
				$query = "INSERT INTO JRGDTA94T.F47122  
				(MJEDTY,MJEKCO,MJEDOC,MJEDCT,MJEDLN,MJEDST,MJEDER,MJPACD,MJKSEQ,MJMCU, MJLITM,MJLOCN,MJLOTN,MJTRQT,MJUNCS,MJTREX,MJUSER) 
				VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
				
				$pstmt = odbc_prepare($conn,$query);
				if($pstmt) {
					$arrParams = array();	 
					$arrParams[] = 	$MJEDTY;	
					$arrParams[] = 	$MJEKCO;	
					$arrParams[] = 	$MJEDOC;	
					$arrParams[] = 	$MJEDCT;	
					$arrParams[] = 	$MJEDLN;	
					$arrParams[] = 	$MJEDST;	
					$arrParams[] = 	$MJEDER;	
					$arrParams[] = 	$MJPACD;	
					$arrParams[] =  $MJKSEQ;
					$arrParams[] =  $MJMCU;
					$arrParams[] =  $MJLITM;
					$arrParams[] =  $MJLOCN;
					$arrParams[] =  $MJLOTN;
					$arrParams[] =  $MJTRQT;
					$arrParams[] =  $MJUNCS;
					$arrParams[] =  $MJTREX;
					$arrParams[] =  $MJUSER;
					 
					$res = odbc_execute($pstmt,$arrParams);
					if($res) {
						$insCount++;
					} else {
						$errMsg = "Errore query dettaglio riga ".$i." : ".odbc_errormsg();
						$hasErr = true;
					}
					 
				}
				
			}
		}
		  
		if($hasErr) { 
			odbc_rollback($conn); 
			echo '{"stat":"ERR","msg":'.json_encode($errMsg).'}';
			error_log($errMsg);
		}
		else {
			odbc_commit($conn); 
			
			$params = []; 
			$params[] = $tkconn->AddParameterChar('both', 10,'P5PID', 'P5PID', 'P47121');
			$params[] = $tkconn->AddParameterChar('both', 10,'P5VERS', 'P5VERS', 'SPY1001'); 
			   
			$res = $tkconn->PgmCall('J47121', 'JDWOBJ94', $params, null, null);  
			  
			if (!$res) {
				$errMsg = 'Error calling program J47121. Code: ' . $conn->getErrorCode() . ' Msg: ' . $conn->getErrorMsg();
				echo '{"stat":"ERR","msg":'.json_encode($errMsg).'}';
				exit;
			}
			
			echo '{"stat":"OK","documentNumber":'.json_encode($M1EDOC).'}';
		} 
	
		$tkconn->disconnect();
	
	}
}

odbc_close($conn);
	