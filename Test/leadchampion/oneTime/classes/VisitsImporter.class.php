<?php  


class VisitsImporter {
	
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
		$url = "https://api.leadchampion.com/v1/visits?includeISP=false&datefrom=".urlencode($date_from)."&dateto=".urlencode($date_to)."&pageviews=true&events=false&limit=250&offset=0";

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

		error_log("Richiamo nr.1 : ".count($response_data["visits"]));

		if(isset($response_data["message"])) {  
			$errMsg = $response_data["message"];
			error_log($errMsg);
			exit;
		} 

		if(!isset($response_data["total"])) {  
			$errMsg = "Errore dati di ritorno: ".$curl_data;
			error_log($errMsg);
			exit;
		} 
		
		$total = (int) $response_data["total"];
		$visits = $response_data["visits"];
		$totVisits = $visits;
		error_log("Total records var total: ".$total);
		
		//richiami successivi se il totale dei record supera i 250:
		$cc = 1;
		$end = true;
		if($total > 250) $end = false;
		//max 1000
		while(!$end && $cc<50) {
			$offset = $cc*250;
			
			$curl_handle = curl_init();
			$url = "https://api.leadchampion.com/v1/visits?includeISP=false&datefrom=".urlencode($date_from)."&dateto=".urlencode($date_to)."&pageviews=true&events=false&limit=250&offset=".$offset;

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
			$visits2 = $response_data2["visits"];
			error_log("Richiamo nr.".($cc+1)." : ".count($visits2));
			
			for($i=0;$i<count($companies2);$i++) { 
				$totVisits[] = $visits2[$i];
			}
			
			$total = (int) $response_data2["total"];
			if($total<=$offset+250) $end = true;
			
			$cc++;
		}	
		//richiami successivi se il totale dei record supera i 250 [f]
		
		error_log("Total records : ".count($totVisits));
		
		$hasErr = false;
		$cntRows = 0;
		$visits = $totVisits;
		for($i=0;$i<count($visits);$i++) {
			$curVisit = $visits[$i]; 
			
			$LVVIID = "";
			$LVVSID = "";
			$LVCOID = 0;
			$LVISP  = "";
			$LVSIID = "";
			$LVVIDT = 0;
			$LVVITI = 0;
			$LVLAPA = "";
			$LVBOUN = "";
			$LVCLTZ = "";
			$LVCLSC = "";
			$LVDURA = 0;
			$LVSCOR = 0;
			$LVPVCO = 0;
			$LVFTVI = "";
			$LVCLDE = "";
			$LVREFE = "";
			$LVKEWO = "";
			$LVEVEN = "";
			$LVTAGS = "";
			$LVIMDT = 0;
			$LVIMTI = 0;
			 
			
			if(isset($curVisit["timestamp"])) {
				$secTs = $curVisit["timestamp"];	  
				$dt_utc = new DateTime('now', new DateTimeZone('UTC'));
				$dt_utc->setTimestamp($secTs);
				$dt_rome = $dt_utc->setTimezone(new DateTimeZone('Europe/Rome'));
				$LVVIDT = $dt_rome->format('Ymd');
				$LVVITI = $dt_rome->format('His');
			}
			
			if(isset($curVisit["visitId"])) $LVVIID = utf8_decode($curVisit["visitId"]);
			if(isset($curVisit["visitorId"])) $LVVSID = utf8_decode($curVisit["visitorId"]);
			if(isset($curVisit["companyId"])) $LVCOID = utf8_decode($curVisit["companyId"]);
			if(isset($curVisit["isISP"])) $LVISP  = utf8_decode($curVisit["isISP"]);
			if(isset($curVisit["siteId"])) $LVSIID = $curVisit["siteId"];
			if(isset($curVisit["landingPage"])) $LVLAPA = utf8_decode($curVisit["landingPage"]);
			if(isset($curVisit["bounce"])) $LVBOUN = utf8_decode($curVisit["bounce"]);
			if(isset($curVisit["clientTimeZone"])) $LVCLTZ = utf8_decode($curVisit["clientTimeZone"]);
			if(isset($curVisit["clientScreen"])) $LVCLSC = utf8_decode($curVisit["clientScreen"]);
			if(isset($curVisit["duration"])) $LVDURA = $curVisit["duration"];
			if(isset($curVisit["score"])) $LVSCOR = $curVisit["score"];
			if(isset($curVisit["pageviewsCount"])) $LVPVCO = $curVisit["pageviewsCount"];
			if(isset($curVisit["firstTimeVisitor"])) $LVFTVI = utf8_decode($curVisit["firstTimeVisitor"]);
			if(isset($curVisit["colorDepth"])) $LVCLDE = utf8_decode($curVisit["colorDepth"]);
			if(isset($curVisit["referrer"])) $LVREFE = utf8_decode($curVisit["referrer"]);
			if(isset($curVisit["keyWord"])) $LVKEWO = utf8_decode($curVisit["keyWord"]);
			if(isset($curVisit["events"])) $LVEVEN = utf8_decode($curVisit["events"]);
			if(isset($curVisit["tags"])) $LVTAGS = $curVisit["tags"]; 
			$LVIMDT = date("Ymd");
			$LVIMTI = date("His");
			
			if(!is_numeric($LVVIDT)) $LVVIDT = 0;
			if(!is_numeric($LVVITI)) $LVVITI = 0;
			if(!is_numeric($LVSIID)) $LVSIID = 0;
			if(!is_numeric($LVDURA)) $LVDURA = 0;	  
			if(!is_numeric($LVSCOR)) $LVSCOR = 0;  
			if(!is_numeric($LVPVCO)) $LVPVCO = 0;  
			
			$duplicate = false;
			$query = "SELECT 'S' AS DUP FROM JRGDTA94C.LCVISI0F WHERE LVVIID = ?";
			$pstmt = odbc_prepare($conn,$query);
			if($pstmt) {
				$arrParams = array();	
				$arrParams[] = $LVVIID;	
				$res = odbc_execute($pstmt,$arrParams);
				if(!$res) {
					$errMsg = "Errore query 1: ".odbc_errormsg();
					$hasErr = true;
				}		
				$row = odbc_fetch_array($pstmt); 
				if(isset($row) && isset($row["DUP"]) && $row["DUP"]=="S") {
					$duplicate = true;
					//error_log("Salto record duplicato id:".$LVVIID);
				}
			} else {
				$errMsg = "Errore prepare 1: ".odbc_errormsg();
				$hasErr = true;
			}
			 
			if(!$hasErr && !$duplicate) {
				$query = "INSERT INTO JRGDTA94C.LCVISI0F 
				(LVVIID,LVVSID,LVCOID,LVISP ,LVSIID,LVVIDT,LVVITI,LVLAPA,LVBOUN,LVCLTZ,LVCLSC,LVDURA,LVSCOR,LVPVCO,LVFTVI,LVCLDE,LVREFE,LVKEWO,LVEVEN,LVTAGS,LVIMDT,LVIMTI) 
				VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
				 
				$pstmt = odbc_prepare($conn,$query);
				if($pstmt) {
					$arrParams = array();	
					$arrParams[] = $LVVIID;			
					$arrParams[] = $LVVSID;			
					$arrParams[] = $LVCOID;			
					$arrParams[] = $LVISP ;			
					$arrParams[] = $LVSIID;			
					$arrParams[] = $LVVIDT;			
					$arrParams[] = $LVVITI;			
					$arrParams[] = $LVLAPA;			
					$arrParams[] = $LVBOUN;
					$arrParams[] = $LVCLTZ;
					$arrParams[] = $LVCLSC;
					$arrParams[] = $LVDURA;
					$arrParams[] = $LVSCOR;
					$arrParams[] = $LVPVCO;
					$arrParams[] = $LVFTVI;
					$arrParams[] = $LVCLDE;
					$arrParams[] = $LVREFE;
					$arrParams[] = $LVKEWO;
					$arrParams[] = $LVEVEN;
					$arrParams[] = $LVTAGS;
					$arrParams[] = $LVIMDT;
					$arrParams[] = $LVIMTI; 
					 
					$res = odbc_execute($pstmt,$arrParams);
					if(!$res) { 
						$errMsg = "Errore query 2: ".odbc_errormsg();
						$hasErr = true;
					} else {
						//page views:
						if(isset($curVisit["pageviews"])) {
							$pageViews = $curVisit["pageviews"];
							for($j=0;$j<count($pageViews);$j++) {
								$curPageView = $pageViews[$j];
								 
								$LPVIID = $LVVIID;
								$LPLABL = "";
								$LPPVDT = 0;
								$LPPVTI = 0;
								$LPDURA = 0;
								$LPURL  = ""; 
								 
								if(isset($curPageView["timestamp"])) {
									$secTs = (int) $curPageView["timestamp"];	  
									$dt_utc = new DateTime('now', new DateTimeZone('UTC'));
									$dt_utc->setTimestamp($secTs);
									$dt_rome = $dt_utc->setTimezone(new DateTimeZone('Europe/Rome'));
									$LPPVDT = $dt_rome->format('Ymd');
									$LPPVTI = $dt_rome->format('His');
								}
								
								if(isset($curPageView["label"])) $LPLABL = utf8_decode($curPageView["label"]); 
								if(isset($curPageView["duration"])) $LPDURA = $curPageView["duration"];
								if(isset($curPageView["url"])) $LPURL = utf8_decode($curPageView["url"]);
								$LPIMDT = date("Ymd");
								$LPIMTI = date("His");
								 
								if(!is_numeric($LPPVDT)) $LPPVDT = 0;
								if(!is_numeric($LPPVTI)) $LPPVTI = 0;
								if(!is_numeric($LPDURA)) $LPDURA = 0;
								 
								$query = "INSERT INTO JRGDTA94C.LCPAVI0F 
								(LPVIID,LPLABL,LPPVDT,LPPVTI,LPDURA,LPURL,LPIMDT,LPIMTI) 
								VALUES(?,?,?,?,?,?,?,?)";
								
								$pstmt = odbc_prepare($conn,$query);
								if($pstmt) {
									$arrParams = array();	
									$arrParams[] = $LPVIID;			
									$arrParams[] = $LPLABL;			
									$arrParams[] = $LPPVDT;			
									$arrParams[] = $LPPVTI;			
									$arrParams[] = $LPDURA;			
									$arrParams[] = $LPURL ;			
									$arrParams[] = $LPIMDT;			
									$arrParams[] = $LPIMTI;		
									
									$res = odbc_execute($pstmt,$arrParams);
									if(!$res) { 
										$errMsg = "Errore query 3: ".odbc_errormsg();
										$hasErr = true;
									} 
									
								}
								
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
