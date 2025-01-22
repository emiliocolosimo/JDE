<?php 
 
class ContactsImporter {
	
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
		$url = "https://api.leadchampion.com/v1/contacts?datefrom=".urlencode($date_from)."&dateto=".urlencode($date_to)."&limit=250&offset=0";

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

		if(!isset($response_data["contacts"])) {  
			$errMsg = "Errore dati di ritorno: ".$curl_data;
			error_log($errMsg);
			exit;
		} 
		 
		$hasErr = false;
		$cntRows = 0;
		$contacts = $response_data["contacts"];
		for($i=0;$i<count($contacts);$i++) {
			$curContact = $contacts[$i]; 
			
			$LCMAIL = "";
			$LCVIAG = "";
			$LCINDT = 0;
			$LCINTI = 0;
			$LCSEEQ = "";
			$LCPAUR = "";
			$LCNAME = "";
			$LCSURN = "";
			$LCAGE  = 0;
			$LCCOMP = "";
			$LCLOCA = "";
			$LCPHON = "";
			$LCPRIV = "";
			$LCCAID = 0;
			$LCCANA = "";
			$LCSIUR = "";
			$LCSILA = "";
			$LCEXID = 0;
			$LCEXTY = "";
			$LCSEEN = "";
			$LCININ = "";
			$LCWVID = "";
			$LCVIID = "";
			$LCNELE = "";
			$LCSIID = 0;
			$LCWERE = "";
			$LCID   = 0;
			$LCWRCL = "";
			$LCIMDT = 0;
			$LCIMTI = 0;
			$LCMESS = "";
			
			
			if(isset($curContact["insertDatetime"])) {
				$secTs = (int) $curContact["insertDatetime"] / 1000;	
				$dt_utc = new DateTime('now', new DateTimeZone('UTC'));
				$dt_utc->setTimestamp($secTs);
				$dt_rome = $dt_utc->setTimezone(new DateTimeZone('Europe/Rome'));
				$LCINDT = $dt_rome->format('Ymd');
				$LCINTI = $dt_rome->format('His');
			}				
			
			if(isset($curContact["email"])) $LCMAIL = utf8_decode($curContact["email"]);
			if(isset($curContact["visitorAgent"])) $LCVIAG = $curContact["visitorAgent"];  
			if(isset($curContact["searchEngineQuery"])) $LCSEEQ = $curContact["searchEngineQuery"];
			if(isset($curContact["pageUrl"])) $LCPAUR = $curContact["pageUrl"];
			if(isset($curContact["firstName"])) $LCNAME =  utf8_decode($curContact["firstName"]);
			if(isset($curContact["lastName"])) $LCSURN =  utf8_decode($curContact["lastName"]);
			if(isset($curContact["age"])) $LCAGE  = $curContact["age"];
			if(isset($curContact["company"])) $LCCOMP = utf8_decode($curContact["company"]);
			if(isset($curContact["location"])) $LCLOCA = utf8_decode($curContact["location"]);
			if(isset($curContact["phone"])) $LCPHON = utf8_decode($curContact["phone"]);
			if(isset($curContact["privacy"])) $LCPRIV = $curContact["privacy"];
			if(isset($curContact["campaingID"])) $LCCAID = $curContact["campaingID"];
			if(isset($curContact["campaignName"])) $LCCANA = utf8_decode($curContact["campaignName"]);
			if(isset($curContact["siteUrl"])) $LCSIUR = $curContact["siteUrl"];
			if(isset($curContact["siteLabel"])) $LCSILA = $curContact["siteLabel"];
			if(isset($curContact["experienceID"])) $LCEXID = $curContact["experienceID"];
			if(isset($curContact["experienceType"])) $LCEXTY = $curContact["experienceType"];
			if(isset($curContact["searchEngine"])) $LCSEEN = $curContact["searchEngine"];
			if(isset($curContact["interestedIn"])) $LCININ = $curContact["interestedIn"];
			if(isset($curContact["webVisitID"])) $LCWVID = $curContact["webVisitID"];
			if(isset($curContact["visitorId"])) $LCVIID = $curContact["visitorId"];
			if(isset($curContact["newsletter"])) $LCNELE = $curContact["newsletter"];
			if(isset($curContact["siteID"])) $LCSIID = $curContact["siteID"];
			if(isset($curContact["webReferrer"])) $LCWERE = utf8_decode($curContact["webReferrer"]);
			if(isset($curContact["id"])) $LCID   = $curContact["id"];
			if(isset($curContact["webReferrerClean"])) $LCWRCL = utf8_decode($curContact["webReferrerClean"]);
			$LCIMDT = date("Ymd");
			$LCIMTI = date("His");

			//extra fields:
			$LCMESS = "";
			$extraFields = $curContact["extraFields"];
			if(isset($extraFields)) {
				for($j=0;$j<count($extraFields);$j++) {
				  if($extraFields[$j]["fieldName"]=="MESSAGGIO") $LCMESS = utf8_decode($extraFields[$j]["fieldValue"]);
				  //..
				}
			}
			
			if(!is_numeric($LCINDT)) $LCINDT = 0;
			if(!is_numeric($LCINTI)) $LCINTI = 0;	 
			if(!is_numeric($LCCAID)) $LCCAID = 0;
			if(!is_numeric($LCEXID)) $LCEXID = 0;  
			if(!is_numeric($LCSIID)) $LCSIID = 0; 
			if(!is_numeric($LCID)) $LCID   = 0; 	
			
			$duplicate = false;
			$query = "SELECT 'S' AS DUP FROM JRGDTA94C.LCCONT0F WHERE LCID = ?";
			$pstmt = odbc_prepare($conn,$query);
			if($pstmt) {
				$arrParams = array();	
				$arrParams[] = $LCID;	
				$res = odbc_execute($pstmt,$arrParams);
				if(!$res) {
					$errMsg = "Errore query 1: ".odbc_errormsg();
					$hasErr = true;
				}		
				$row = odbc_fetch_array($pstmt); 
				if(isset($row) && isset($row["DUP"]) && $row["DUP"]=="S") {
					$duplicate = true;
					error_log("Salto record duplicato id:".$LCID);
				}
			} else {
				$errMsg = "Errore prepare 1: ".odbc_errormsg();
				$hasErr = true;
			}
			 
			if(!$hasErr && !$duplicate) {
				$query = "INSERT INTO JRGDTA94C.LCCONT0F 
				(LCMAIL,LCVIAG,LCINDT,LCINTI,LCSEEQ,LCPAUR,LCNAME,LCSURN,LCAGE ,LCCOMP,LCLOCA,LCPHON,LCPRIV,LCCAID,LCCANA,LCSIUR,LCSILA,LCEXID,LCEXTY,LCSEEN,LCININ,LCWVID,LCVIID,LCNELE,LCSIID,LCWERE,LCID,LCWRCL,LCIMDT,LCIMTI,LCMESS) 
				VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
				 
				$pstmt = odbc_prepare($conn,$query);
				if($pstmt) {
					$arrParams = array();	
					$arrParams[] = $LCMAIL;			
					$arrParams[] = $LCVIAG;			
					$arrParams[] = $LCINDT;			
					$arrParams[] = $LCINTI;			
					$arrParams[] = $LCSEEQ;			
					$arrParams[] = $LCPAUR;			
					$arrParams[] = $LCNAME;			
					$arrParams[] = $LCSURN;			
					$arrParams[] = $LCAGE;
					$arrParams[] = $LCCOMP;
					$arrParams[] = $LCLOCA;
					$arrParams[] = $LCPHON;
					$arrParams[] = $LCPRIV;
					$arrParams[] = $LCCAID;
					$arrParams[] = $LCCANA;
					$arrParams[] = $LCSIUR;
					$arrParams[] = $LCSILA;
					$arrParams[] = $LCEXID;
					$arrParams[] = $LCEXTY;
					$arrParams[] = $LCSEEN;
					$arrParams[] = $LCININ;
					$arrParams[] = $LCWVID;
					$arrParams[] = $LCVIID;
					$arrParams[] = $LCNELE;
					$arrParams[] = $LCSIID;
					$arrParams[] = $LCWERE;
					$arrParams[] = $LCID;
					$arrParams[] = $LCWRCL;
					$arrParams[] = $LCIMDT;
					$arrParams[] = $LCIMTI;
					$arrParams[] = $LCMESS;
					 
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
		}
		else {
			odbc_commit($conn); 
			error_log("IMPORT OK CNT=".$cntRows);
		} 
	}
	
}

