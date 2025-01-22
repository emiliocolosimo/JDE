<?php 
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set("log_errors", 1);

class DomainSearch {
	
    public function __construct()
    {
 
    }	
	
	public function saveInfo($searchDomain, $dataOrigin = '') {
		//error_log("Ricerca info dominio: ".$searchDomain);

		$server="Driver={IBM i Access ODBC Driver};System=127.0.0.1;Uid=".AH_DB2_USER.";Pwd=".AH_DB2_PASS.";TRANSLATE=1;"; 
		$user=AH_DB2_USER; 
		$pass=AH_DB2_PASS;   
		$conn=odbc_connect($server,$user,$pass); 
		if(!$conn) {
			$errMsg = "Errore connessione al database : ".odbc_errormsg($conn);
			error_log($errMsg);
			exit;
		}
		
		$DIDOMA  = $searchDomain;
		$duplicate = false;
		$hasErr = false;
		$query = "SELECT 'S' AS DUP FROM JRGDTA94C.DSINFO0F WHERE DIDOMA = ? FETCH FIRST ROW ONLY";
		$pstmt = odbc_prepare($conn,$query);
		if($pstmt) {
			$arrParams = array();	
			$arrParams[] = $DIDOMA;	
			$res = odbc_execute($pstmt,$arrParams);
			if(!$res) {
				$errMsg = "Errore query 1: ".odbc_errormsg();
				$hasErr = true;
			}		
			$row = odbc_fetch_array($pstmt); 
			if(isset($row) && isset($row["DUP"]) && $row["DUP"]=="S") {
				$duplicate = true;
				//error_log("Salto record duplicato domain:".$DIDOMA);
				return true;
			}
		} else {
			$errMsg = "Errore prepare 1: ".odbc_errormsg();
			$hasErr = true;
		}

		if(!$hasErr && !$duplicate) {

			$curl_handle = curl_init(); 
			$url = "https://api.hunter.io/v2/domain-search?domain=".$searchDomain."&api_key=".AH_API_KEY; 

			curl_setopt($curl_handle, CURLOPT_URL, $url);
			curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl_handle, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, 0);

			$curl_data = curl_exec($curl_handle);
			if(!$curl_data) {
				$errMsg = "Errore chiatam cURL : ".curl_error($curl_handle);
				error_log($errMsg);
				exit;
			}
			curl_close($curl_handle);

			$response_data = json_decode($curl_data,true); 
	 
			if(!isset($response_data["data"])) {  
				$errMsg = "Errore dati di ritorno: ".$curl_data;
				error_log($errMsg);
				exit;
			} 
			 
			$hasErr = false;
			$cntRows = 0;
			$curDomain = $response_data["data"];
			 
			$DIDOMA  = $searchDomain;
			$DIORGA  = "";
			$DILKIN  = "";
			$DICOUN  = "";
			$DISTATE = "";
			$DICITY  = "";
			$DIFBOK  = "";
			$DIINGR  = "";
			$DIMAIL  = "";
			$DIIMDT  = "";
			$DIIMTI  = "";	
			$DIORIG  = $dataOrigin; 
			if(isset($curDomain["organization"])) $DIORGA = utf8_decode($curDomain["organization"]); 
			if(isset($curDomain["linkedin"])) $DILKIN = utf8_decode($curDomain["linkedin"]);
			if(isset($curDomain["country"])) $DICOUN = utf8_decode($curDomain["country"]);
			if(isset($curDomain["state"])) $DISTATE = utf8_decode($curDomain["state"]);
			if(isset($curDomain["city"])) $DICITY = utf8_decode($curDomain["city"]);
			if(isset($curDomain["facebook"])) $DIFBOK = utf8_decode($curDomain["facebook"]);
			if(isset($curDomain["instagram"])) $DIINGR = utf8_decode($curDomain["instagram"]);
			$DIIMDT = date("Ymd");
			$DIIMTI = date("His");

			if(isset($curDomain["emails"])) {
				$curMails = $curDomain["emails"];
				for($j=0;$j<count($curMails);$j++) {
					
					$DIMAIL = utf8_decode($curMails[$j]["value"]);
					
					if(!$hasErr && !$duplicate) {
						$query = "INSERT INTO JRGDTA94C.DSINFO0F 
						(DIDOMA,DIORGA,DILKIN,DICOUN,DISTATE,DICITY,DIFBOK,DIINGR,DIMAIL,DIIMDT,DIIMTI,DIORIG) 
						VALUES(?,?,?,?,?,?,?,?,?,?,?,?)";
						 
						$pstmt = odbc_prepare($conn,$query);
						if($pstmt) {
							$arrParams = array();	
							$arrParams[] = $DIDOMA; 			
							$arrParams[] = $DIORGA; 			
							$arrParams[] = $DILKIN;			
							$arrParams[] = $DICOUN; 			
							$arrParams[] = $DISTATE;			
							$arrParams[] = $DICITY; 			
							$arrParams[] = $DIFBOK; 			
							$arrParams[] = $DIINGR; 			
							$arrParams[] = $DIMAIL; 
							$arrParams[] = $DIIMDT; 
							$arrParams[] = $DIIMTI; 
							$arrParams[] = $DIORIG; 
							 
							$res = odbc_execute($pstmt,$arrParams);
							if(!$res) {
								$errMsg = "Errore query 2: ".odbc_errormsg();
								$hasErr = true;
							}
							$cntRows++;
						} else {
							$errMsg = "Errore prepare 2: ".odbc_errormsg();
							$hasErr = true;
						}
					}	 	
					


				}
				
				if($hasErr) { 
					odbc_rollback($conn);  
					error_log($errMsg);
					odbc_close($conn);
					return false;
				}
				else {
					odbc_commit($conn); 
					error_log("IMPORT DOMAIN OK CNT=".$cntRows);
					odbc_close($conn);
					return true;
				} 
			}
			  

		}
	}
	
}

