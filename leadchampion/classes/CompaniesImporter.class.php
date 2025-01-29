<?php 
include("/www/php80/htdocs/apihunter/config.inc.php");
include("/www/php80/htdocs/apihunter/classes/DomainSearch.class.php");

class CompaniesImporter {
    public function __construct()
    {
 
    }	
	
	public function import() { 
		error_log("Avvio importazione ".date("Ymd His"));

		$server="Driver={IBM i Access ODBC Driver};System=127.0.0.1;Uid=".DB2_USER.";Pwd=".DB2_PASS.";TRANSLATE=1;"; 
		$user=DB2_USER; 
		$pass=DB2_PASS;   
		$conn=odbc_connect($server,$user,$pass); 
		if(!$conn) {
			$errMsg = "Errore connessione al database : ".odbc_errormsg($conn);
			error_log($errMsg);
			exit;
		}

		$curl_handle = curl_init();
		$date_from = date("Y-m-d",strtotime("-3 days"))." 00:00:00";
		$date_to = date("Y-m-d")." 23:59:59";
		$url = "https://api.leadchampion.com/v1/companies?includeISP=false&datefrom=".urlencode($date_from)."&dateto=".urlencode($date_to)."&limit=250&offset=0";

		curl_setopt($curl_handle, CURLOPT_URL, $url);
		curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl_handle, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($curl_handle, CURLOPT_HTTPHEADER, ['x-api-key: '.LC_API_KEY]);

		$curl_data = curl_exec($curl_handle);
		if(!$curl_data) {
			$errMsg = "Errore chiatam cURL : ".curl_error($curl_handle);
			error_log($errMsg);
			exit;
		}
		curl_close($curl_handle);

		$response_data = json_decode($curl_data,true); 
		 
		
		if(isset($response_data["message"])) {  
			$errMsg = $response_data["message"];
			error_log($errMsg);
			exit;
		} 

		if(!isset($response_data["companies"])) {  
			$errMsg = "Errore dati di ritorno: ".$curl_data;
			error_log($errMsg);
			exit;
		} 
		 
		$hasErr = false;
		$cntRows = 0;
		$cntRowsUpd = 0;
		$companies = $response_data["companies"];
		for($i=0;$i<count($companies);$i++) {
			$curCompany = $companies[$i]; 
			
			$LACLNT = "";
			$LAISP  = "";
			$LACNCO = "";
			$LAATDE = "";
			$LAATCO = "";
			$LALVDT = 0;
			$LALVTI = 0;
			$LACITY = "";
			$LAEMNU = "";
			$LATURA = "";
			$LAPONU = "";
			$LAISHI = "";
			$LAEMAI = "";
			$LAVAT  = "";
			$LAWEBS = "";
			$LACATE = "";
			$LAFAX  = "";
			$LACONA = "";
			$LAGOPL = "";
			$LABESC = 0;
			$LAFACE = "";
			$LAADDR = "";
			$LAEMRA = "";
			$LATAGS = "";
			$LARIVA = "";
			$LAREGI = "";
			$LALIIN = "";
			$LATURN = "";
			$LAID   = 0;
			$LACNIT = "";
			$LAFISC = 0;
			$LACNEN = "";
			 
			if(isset($curCompany["lastVisit"])) {
				$secTs = (int) $curCompany["lastVisit"] / 1000;	  
				$dt_utc = new DateTime('now', new DateTimeZone('UTC'));
				$dt_utc->setTimestamp($secTs);
				$dt_rome = $dt_utc->setTimezone(new DateTimeZone('Europe/Rome'));
				$LALVDT = $dt_rome->format('Ymd');
				$LALVTI = $dt_rome->format('His');
			}			
			 
			if(isset($curCompany["isClient"]))				$LACLNT = utf8_decode($curCompany["isClient"]);
			if(isset($curCompany["isISP"]))                 $LAISP  = utf8_decode($curCompany["isISP"]);
			if(isset($curCompany["countryCode"]))           $LACNCO = utf8_decode($curCompany["countryCode"]);
			if(isset($curCompany["atecoDescription"]))      $LAATDE = utf8_decode($curCompany["atecoDescription"]);
			if(isset($curCompany["atecoCode"]))             $LAATCO = utf8_decode($curCompany["atecoCode"]); 
			if(isset($curCompany["city"]))                  $LACITY = utf8_decode($curCompany["city"]);
			if(isset($curCompany["employeesNumber"]))       $LAEMNU = utf8_decode($curCompany["employeesNumber"]);
			if(isset($curCompany["turnoverRange"]))         $LATURA = utf8_decode($curCompany["turnoverRange"]);
			if(isset($curCompany["phoneNumber"]))           $LAPONU = utf8_decode($curCompany["phoneNumber"]);
			if(isset($curCompany["isHidden"]))              $LAISHI = utf8_decode($curCompany["isHidden"]);
			if(isset($curCompany["email"]))                 $LAEMAI = utf8_decode($curCompany["email"]);
			if(isset($curCompany["vat"]))                   $LAVAT  = utf8_decode($curCompany["vat"]);
			if(isset($curCompany["website"]))               $LAWEBS = utf8_decode($curCompany["website"]);
			if(isset($curCompany["category"]))              $LACATE = strtolower(utf8_decode($curCompany["category"]));
			if(isset($curCompany["fax"]))                   $LAFAX  = utf8_decode($curCompany["fax"]);
			if(isset($curCompany["companyName"]))           $LACONA = utf8_decode($curCompany["companyName"]);
			if(isset($curCompany["googlePlus"]))            $LAGOPL = utf8_decode($curCompany["googlePlus"]);
			if(isset($curCompany["behaviouralScore"]))      $LABESC = $curCompany["behaviouralScore"];
			if(isset($curCompany["facebook"]))              $LAFACE = utf8_decode($curCompany["facebook"]);
			if(isset($curCompany["address"]))               $LAADDR = utf8_decode($curCompany["address"]);
			if(isset($curCompany["employeesRange"]))        $LAEMRA = utf8_decode($curCompany["employeesRange"]);
			if(isset($curCompany["tags"]))                  $LATAGS = utf8_decode($curCompany["tags"]);
			if(isset($curCompany["riskValutation"]))        $LARIVA = utf8_decode($curCompany["riskValutation"]);
			if(isset($curCompany["region"]))                $LAREGI = utf8_decode($curCompany["region"]);
			if(isset($curCompany["linkedIn"]))              $LALIIN = utf8_decode($curCompany["linkedIn"]);
			if(isset($curCompany["turnover"]))              $LATURN = utf8_decode($curCompany["turnover"]);
			if(isset($curCompany["id"]))                    $LAID   = $curCompany["id"];
			if(isset($curCompany["countryNameIT"]))         $LACNIT = utf8_decode($curCompany["countryNameIT"]);
			if(isset($curCompany["firmographicScore"]))     $LAFISC = $curCompany["firmographicScore"];
			if(isset($curCompany["countryNameEN"]))         $LACNEN = utf8_decode($curCompany["countryNameEN"]);
			$LAIMDT = date("Ymd");
			$LAIMTI = date("His");
			
			/**/
			$arrWordsHide = array("UNIVERSIT","ECOLE","SCHULE","SCUOLA","ISTITUT","INSTITUT");
			$containsWordHide = false;
			for($wo=0;$wo<count($arrWordsHide) && !$containsWordHide;$wo++) {
				if(strpos($LACONA, $arrWordsHide[$wo])!==false) $containsWordHide = true;
			}	
			if($containsWordHide) $LAISHI = '1';
			/**/
			 
			if(!is_numeric($LALVDT)) $LALVDT = 0;
			if(!is_numeric($LALVTI)) $LALVTI = 0;	  
			if(!is_numeric($LABESC)) $LABESC = 0;  
			if(!is_numeric($LAID)) $LAID = 0; 
			if(!is_numeric($LAFISC)) $LAFISC   = 0; 	
			
			$duplicate = false;
			$query = "SELECT 'S' AS DUP FROM JRGDTA94C.LCCOMP0F WHERE LAID = ?";
			$pstmt = odbc_prepare($conn,$query);
			if($pstmt) {
				$arrParams = array();	
				$arrParams[] = $LAID;	
				$res = odbc_execute($pstmt,$arrParams);
				if(!$res) {
					$errMsg = "Errore query 1: ".odbc_errormsg();
					$hasErr = true;
				}		
				$row = odbc_fetch_array($pstmt); 
				if(isset($row) && isset($row["DUP"]) && $row["DUP"]=="S") {
					$duplicate = true;
					//error_log("Salto record duplicato id:".$LAID);
				}
			} else {
				$errMsg = "Errore prepare 1: ".odbc_errormsg();
				$hasErr = true;
			}
			 
			if(!$hasErr) {
				if(!$duplicate) {
					/* devo sostituire la categoria con una associata? */
					$query = "SELECT ACASCA, ACAVIS  
					FROM JRGDTA94C.LCASCA0F 
					WHERE LOWER(ACLCCA) = ? 
					AND ACASCA <> '' 
					FETCH FIRST ROW ONLY
					";
					$pstmtAssCat = odbc_prepare($conn,$query);
					if($pstmtAssCat) {
						$arrParams = array();	
						$arrParams[] = $LACATE;	
						$resAssCat = odbc_execute($pstmtAssCat,$arrParams);
						if(!$resAssCat) {
							$errMsg = "Errore query 1: ".odbc_errormsg();
							$hasErr = true;
						}		
						$rowAssCat = odbc_fetch_array($pstmtAssCat); 
						if(isset($rowAssCat) && isset($rowAssCat["ACASCA"])) {
							error_log("Sostituita categoria:".$LACATE.">".$rowAssCat["ACASCA"].":".$rowAssCat["ACAVIS"]);
							
							$LACATE = strtolower($rowAssCat["ACASCA"]);
							$LAISHI = $rowAssCat["ACAVIS"];
						}
					}
					/**/				
					 
					$query = "INSERT INTO JRGDTA94C.LCCOMP0F 
					(LACLNT,LAISP,LACNCO,LAATDE,LAATCO,LALVDT,LALVTI,LACITY,LAEMNU,LATURA,LAPONU,LAISHI,LAEMAI,LAVAT,LAWEBS,LACATE,LAFAX,LACONA,LAGOPL,LABESC,LAFACE,LAADDR,LAEMRA,LATAGS,LARIVA,LAREGI,LALIIN,LATURN,LAID,LACNIT,LAFISC,LACNEN,LAIMDT,LAIMTI) 
					VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"; 
					$pstmt = odbc_prepare($conn,$query);
					if($pstmt) {
						$arrParams = array();	
						$arrParams[] = $LACLNT;			
						$arrParams[] = $LAISP ;			
						$arrParams[] = $LACNCO;			
						$arrParams[] = $LAATDE;			
						$arrParams[] = $LAATCO;			
						$arrParams[] = $LALVDT;			
						$arrParams[] = $LALVTI;			
						$arrParams[] = $LACITY;			
						$arrParams[] = $LAEMNU;
						$arrParams[] = $LATURA;
						$arrParams[] = $LAPONU;
						$arrParams[] = $LAISHI;
						$arrParams[] = $LAEMAI;
						$arrParams[] = $LAVAT ;
						$arrParams[] = $LAWEBS;
						$arrParams[] = $LACATE;
						$arrParams[] = $LAFAX ;
						$arrParams[] = $LACONA;
						$arrParams[] = $LAGOPL;
						$arrParams[] = $LABESC;
						$arrParams[] = $LAFACE;
						$arrParams[] = $LAADDR;
						$arrParams[] = $LAEMRA;
						$arrParams[] = $LATAGS;
						$arrParams[] = $LARIVA;
						$arrParams[] = $LAREGI;
						$arrParams[] = $LALIIN;
						$arrParams[] = $LATURN;
						$arrParams[] = $LAID  ;
						$arrParams[] = $LACNIT;
						$arrParams[] = $LAFISC;
						$arrParams[] = $LACNEN;
						$arrParams[] = $LAIMDT;
						$arrParams[] = $LAIMTI;
						
						
						$res = odbc_execute($pstmt,$arrParams);
						if(!$res) {
							$errMsg = "Errore query 2: ".odbc_errormsg();
							$hasErr = true;
						} else {
							//recupero info dominio:
							if($LAEMAI!="" && strpos($LAEMAI,"@") !== false && strpos($LAEMAI,".") !== false) { 
								$curDomain = substr($LAEMAI, strpos($LAEMAI, '@') + 1);
								$DomainSearch = new DomainSearch();
								$resDomain = $DomainSearch->saveInfo($curDomain);
								if(!$resDomain) {
									error_log("Errore nel recupero info dominio: ".$curDomain);
								}
							}
						}
						$cntRows++;
					} else {
						$errMsg = "Errore prepare 2: ".odbc_errormsg();
						$hasErr = true;
					}
				} else {
					//aggiornamento del record:
					 
					$query = "
					UPDATE JRGDTA94C.LCCOMP0F SET LACLNT = ?, LAISP  = ?, LACNCO = ?, LAATDE = ?, LAATCO = ?, LALVDT = ?, LALVTI = ?, LACITY = ?, LAEMNU = ?, LATURA = ?, LAPONU = ?, LAISHI = ?, LAEMAI = ?, LAVAT = ?, LAWEBS = ?, LACATE = ?, LAFAX  = ?, LACONA = ?, LAGOPL = ?, LABESC = ?, LAFACE = ?, LAADDR = ?, LAEMRA = ?, LATAGS = ?, LARIVA = ?, LAREGI = ?, LALIIN = ?, LATURN = ?, LACNIT = ?, LAFISC = ?, LACNEN = ?, LAIMDT = ?, LAIMTI = ?
					WHERE LAID = ?
					"; 
					$pstmt = odbc_prepare($conn,$query);
					if($pstmt) {
						$arrParams = array();	
						$arrParams[] = $LACLNT;			
						$arrParams[] = $LAISP ;			
						$arrParams[] = $LACNCO;			
						$arrParams[] = $LAATDE;			
						$arrParams[] = $LAATCO;			
						$arrParams[] = $LALVDT;			
						$arrParams[] = $LALVTI;			
						$arrParams[] = $LACITY;			
						$arrParams[] = $LAEMNU;
						$arrParams[] = $LATURA;
						$arrParams[] = $LAPONU;
						$arrParams[] = $LAISHI;
						$arrParams[] = $LAEMAI;
						$arrParams[] = $LAVAT ;
						$arrParams[] = $LAWEBS;
						$arrParams[] = $LACATE;
						$arrParams[] = $LAFAX ;
						$arrParams[] = $LACONA;
						$arrParams[] = $LAGOPL;
						$arrParams[] = $LABESC;
						$arrParams[] = $LAFACE;
						$arrParams[] = $LAADDR;
						$arrParams[] = $LAEMRA;
						$arrParams[] = $LATAGS;
						$arrParams[] = $LARIVA;
						$arrParams[] = $LAREGI;
						$arrParams[] = $LALIIN;
						$arrParams[] = $LATURN; 
						$arrParams[] = $LACNIT;
						$arrParams[] = $LAFISC;
						$arrParams[] = $LACNEN;
						$arrParams[] = $LAIMDT;
						$arrParams[] = $LAIMTI;
						$arrParams[] = $LAID  ;
						
						$res = odbc_execute($pstmt,$arrParams);
						if(!$res) {
							$errMsg = "Errore query 2a: ".odbc_errormsg();
							$hasErr = true;
						} else {
							//recupero info dominio:
							 
							if($LAEMAI!="" && strpos($LAEMAI,"@") !== false && strpos($LAEMAI,".") !== false) { 
								$curDomain = substr($LAEMAI, strpos($LAEMAI, '@') + 1);
								$DomainSearch = new DomainSearch();
								$resDomain = $DomainSearch->saveInfo($curDomain);
								if(!$resDomain) {
									error_log("Errore nel recupero info dominio: ".$curDomain);
								}
							}
							 
						}
						
						$cntRowsUpd++;
					} else {
						$errMsg = "Errore prepare 2a: ".odbc_errormsg();
						$hasErr = true;
					}
					  
				}
			}	 	
		}

		if($hasErr) { 
			odbc_rollback($conn);  
			error_log($errMsg);
		}
		else {
			odbc_commit($conn); 
			error_log("IMPORT OK CNT ADD=".$cntRows);
			error_log("IMPORT OK CNT UPD=".$cntRowsUpd);
		} 
	}
}

