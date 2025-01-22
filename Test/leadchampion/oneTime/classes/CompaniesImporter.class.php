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
		$date_from = "2000-01-01 00:00:00";
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
		$companies = $response_data["companies"]; 
		$total = (int) $response_data["total"];
		
		$totCompanies = $companies;
		
		error_log("Total records var total: ".$total);
		
		//richiami successivi se il totale dei record supera i 250:
		$cc = 1;
		$end = false;
		//!!IL LORO WEBSERVICE NON FUNZIONA, RITORNA SEMPRE TOTAL UGUALE A LIMIT 
		//!!TOCCA FARE UN TOT DI GIRI A MANO 
		//!!SENZA CONSIDERARE IL TOTALE RITORNATO
		if($total > 250) $end = false;
		//max 1000
		while(!$end && $cc<18) { 
			$offset = $cc*250;
			
			$curl_handle = curl_init();
			$url = "https://api.leadchampion.com/v1/companies?includeISP=false&datefrom=".urlencode($date_from)."&dateto=".urlencode($date_to)."&limit=250&offset=".$offset;

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
			
			$response_data2 = json_decode($curl_data,true); 
			$companies2 = $response_data2["companies"];
			
			for($i=0;$i<count($companies2);$i++) { 
				$totCompanies[] = $companies2[$i];
			}
			
			error_log("Richiamo nr.".($cc+1)." : ".count($totCompanies));
			 
			error_log("Current total : ".count($totCompanies));		 
			
			$total = (int) $response_data2["total"];
			///if($total<=$offset+250) $end = true;
			
			$cc++;
		}	
		//richiami successivi se il totale dei record supera i 250 [f]
		
		error_log("Total records : ".count($totCompanies));		 
		 
		
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
		
		$companies = $totCompanies;
		
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
			if(isset($curCompany["category"]))              $LACATE = substr(utf8_decode($curCompany["category"]),0,250);
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
			 
			if(!$hasErr && !$duplicate) {
				$query = "INSERT INTO JRGDTA94C.LCCOMP0F 
				(LACLNT,LAISP,LACNCO,LAATDE,LAATCO,LALVDT,LALVTI,LACITY,LAEMNU,LATURA,LAPONU,LAISHI,LAEMAI,LAVAT,LAWEBS,LACATE,LAFAX,LACONA,LAGOPL,LABESC,LAFACE,LAADDR,LAEMRA,LATAGS,LARIVA,LAREGI,LALIIN,LATURN,LAID,LACNIT,LAFISC,LACNEN) 
				VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
				 
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
						var_dump($arrParams);
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
			}	 	
		}

		if($hasErr) { 
			odbc_rollback($conn);  
			error_log($errMsg);
		}
		else {
			odbc_commit($conn); 
			error_log("IMPORT OK CNT=".$cntRows);
		} 
	}
}

