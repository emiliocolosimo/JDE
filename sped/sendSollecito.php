<?php
set_time_limit(0);

error_reporting(E_ALL);
ini_set("display_errors",0);
ini_set("log_errors",1);
ini_set("error_log", "/www/php80/htdocs/sped/logs/sendSollecito_".date("Ym").".log");
require("/www/php80/htdocs/sped/config.inc.php");
require("/www/php80/htdocs/sped/classes/AzureService.class.php");
 
$mailSignature = '<p class="MsoNormal" style="margin-bottom:0cm;line-height:normal">
<i><span style="font-family:\'Segoe UI\',sans-serif;color:#1636FC">&nbsp;</span></i>
</p>

<p class="MsoNormal" style="margin-bottom:0cm;line-height:normal">
<b><span style="font-size:9.0pt;font-family:\'Segoe UI\',sans-serif;color:#1636FC">__________________________________</span></b>
<br>
<b><i><span style="font-size:18.0pt;font-family:\'Segoe UI\',sans-serif;color:#1636FC">Elena Lucchini</span></i></b>
<br>
<i><span style="font-size:12.0pt;font-family:\'Segoe UI\',sans-serif;color:#1636FC">Ufficio Spedizioni  / Shipping Department</span></i>
</p>

<table class="MsoNormalTable" border="0" cellspacing="0" cellpadding="0" style="border-collapse:collapse">
<tbody>
<tr style="height:13.9pt">
<td width="598" colspan="2" valign="top" style="width:448.7pt;padding:0cm 5.4pt 0cm 5.4pt;height:13.9pt">
<p class="MsoNormal" style="margin-bottom:0cm;line-height:normal">
<a href="https://www.rgpballs.com/">
<span style="font-family:\'Arial\',sans-serif;color:black;text-decoration:none">
<img border="0" width="278" height="34" style="width:2.8958in;height:.3541in" id="Immagine_x0020_3" src="https://download.rgpballs.com/Downloads/logorgp.png">
</span>
</a>
</p>
</td>
</tr>
<tr style="height:81.55pt">
<td width="598" colspan="2" valign="top" style="width:448.7pt;padding:0cm 5.4pt 0cm 5.4pt;height:81.55pt">
<p class="MsoNormal" style="margin-bottom:0cm;line-height:normal">
<b>
<span style="font-size:9.0pt;font-family:\'Arial\',sans-serif;color:#1636FC">RGPBALLS s.r.l.</span></b>
<br>
<span style="font-size:9.0pt;font-family:\'Arial\',sans-serif;color:#262626">Via E. De Amicis 59/C 96 61/A,</span>
<br>
<span style="font-size:9.0pt;font-family:\'Arial\',sans-serif;color:#262626">20092 Cinisello Balsamo (MI) Italia</span>
<br>
<span style="font-size:9.0pt;font-family:\'Arial\',sans-serif;color:#262626">T. +39 02 6178857&nbsp;</span>
<br> 
<a href="http://www.rgpballs.com/"><b><span style="font-size:9.0pt;font-family:\'Arial\',sans-serif;color:#1636FC">www.rgpballs.com</span></b></a>
</p>
</td>

<td width="7" style="width:5.25pt;padding:0cm 0cm 0cm 0cm;height:81.55pt">
<p class="MsoNormal" style="margin-bottom:0cm;line-height:normal"></p>
</td>
</tr>

<tr>
	<td colspan="2" width="598">
		<p class="MsoNormal" style="margin-bottom:0cm;line-height:normal">
		<a href="https://www.hannovermesse.de/"><img width="367" height="60" src="https://download.rgpballs.com/downloads/fiera.png" /></a>
		</p>
	</td>
</tr>

<tr style="height:13.9pt">
<td width="7" style="width:5.25pt;padding:0cm 0cm 0cm 0cm;height:13.9pt">
<p class="MsoNormal" style="margin-bottom:0cm;line-height:normal"></p>
</td>

<td width="598" colspan="2" valign="top" style="width:448.7pt;padding:0cm 5.4pt 0cm 5.4pt;height:13.9pt">
<p class="MsoNormal" style="margin-bottom:0cm;line-height:normal">
<a href="https://www.rgpballs.com/it/certificazioni-internazionali/">
<span style="font-size:12.0pt;font-family:\'Calibri\',sans-serif;color:#212121;text-decoration:none">
<img border="0" width="51" height="51" style="width:.5312in;height:.5312in" id="Immagine_x0020_10" src="https://download.rgpballs.com/Downloads/logotuv1.png"></span></a>
<a href="https://www.rgpballs.com/it/certificazioni-internazionali/">
<span style="font-size:12.0pt;font-family:\'Calibri\',sans-serif;color:#212121;text-decoration:none">
<img border="0" width="50" height="50" style="width:.5208in;height:.5208in" id="Immagine_x0020_9" src="https://download.rgpballs.com/Downloads/logotuv2.png"></span></a>
<a href="https://www.rgpballs.com/it/certificazioni-internazionali/">
<span style="font-size:12.0pt;font-family:\'Calibri\',sans-serif;color:#212121;text-decoration:none">
<img border="0" width="50" height="50" style="width:.5208in;height:.5208in" id="Immagine_x0020_8" src="https://download.rgpballs.com/Downloads/logotuv3.png"></span></a>
<a href="https://www.rgpballs.com/it/certificazioni-internazionali/"><span style="font-family:\'Calibri\',sans-serif;color:#212121;text-decoration:none">
<img border="0" width="116" height="54" style="width:1.2083in;height:.5625in" id="Immagine_x0020_7" src="https://download.rgpballs.com/Downloads/logotuv4.png"></span></a>
</p>

</td>
</tr>

<tr style="height:13.9pt">
<td width="7" style="width:5.25pt;padding:0cm 0cm 0cm 0cm;height:13.9pt">
<p class="MsoNormal" style="margin-bottom:0cm;line-height:normal">
<span style="font-size:12.0pt">&nbsp;</span>
</p>

</td>

<td width="598" colspan="2" valign="top" style="width:448.7pt;padding:0cm 5.4pt 0cm 5.4pt;height:13.9pt">
<p class="MsoNormal" style="margin-bottom:0cm;line-height:normal">
<span style="font-size:7.0pt;font-family:\'Arial\',sans-serif;color:#1636FC;background:white">Rispetta l\'ambiente.</span>
<span style="font-size:7.0pt;font-family:\'Arial\',sans-serif;color:#262626;background:white">Hai davvero&nbsp;bisogno di stampare&nbsp;questa email?</span>
<br>
<span style="font-size:7.0pt;font-family:\'Arial\',sans-serif;color:gray">
Le informazioni contenute nella presente comunicazione e i relativi allegati possono essere riservate e sono, comunque, destinate esclusivamente alle persone o alla Società sopraindicati. 
La diffusione, distribuzione e/o copiatura del documento trasmesso da parte di qualsiasi soggetto diverso dal destinatario è proibita, ai sensi del Regolamento Europeo n.679 del 2016. Se 
avete ricevuto questo messaggio per errore, vi preghiamo di distruggerlo e di informare immediatamente il mittente. Per maggiori informazioni riguardo&nbsp; il trattamento dei dati del 
fornitore/cliente consultare:
</span>
<a href="https://www.iubenda.com/privacy-policy/505876">
<span style="font-size:7.0pt;font-family:\'Arial\',sans-serif;color:gray;background:white">https://www.iubenda.com/privacy-policy/505876</span></a>
</p>
</td>
</tr>
<tr height="0">
<td width="7" style="border:none"></td>
<td width="591" style="border:none"></td>
<td width="7" style="border:none"></td>
</tr>

<tr style="height:13.9pt">
<td width="7" style="width:5.25pt;padding:0cm 0cm 0cm 0cm;height:13.9pt">
<p class="MsoNormal" style="margin-bottom:0cm;line-height:normal">
<span style="font-size:12.0pt">&nbsp;</span>
</p>

</td>

<td width="598" colspan="2" valign="top" style="width:448.7pt;padding:0cm 5.4pt 0cm 5.4pt;height:13.9pt">
<p class="MsoNormal" style="margin-bottom:0cm;line-height:normal">
<span style="font-size:7.0pt;font-family:\'Arial\',sans-serif;color:#1636FC;background:white">Please consider the environment.</span>
<span style="font-size:7.0pt;font-family:\'Arial\',sans-serif;color:#262626;background:white">Do you really need to print this email?</span>
<br>
<span style="font-size:7.0pt;font-family:\'Arial\',sans-serif;color:gray">
The information contained in this communication and the relative annexes could be reserved and are, nonetheless, 
intended exclusively for the persons and Company indicated above. The dissemination, distribution and/or copying 
of the transmitted document by anyone other than the addressee is prohibited, pursuant to European Regulation no. 679/2016. 
If you received this message by mistake, please destroy it and immediately notify the sender. 
For further information on treatment of the supplier\'s/customer\'s data, consult: 
</span>
<a href="https://www.iubenda.com/privacy-policy/504010">
<span style="font-size:7.0pt;font-family:\'Arial\',sans-serif;color:gray;background:white">https://www.iubenda.com/privacy-policy/504010</span></a>
</p>
</td>
</tr>
<tr height="0">
<td width="7" style="border:none"></td>
<td width="591" style="border:none"></td>
<td width="7" style="border:none"></td>
</tr>

</tbody>
</table>
<p class="MsoNormal"></p>';
  
error_log("Avvio lettura da MySQL sito web");  
  
$AzureService = new AzureService();
$AzureService->setTenantId(TENANT_ID);
$AzureService->setClientId(CLIENT_ID);
$AzureService->setClientSecret(CLIENT_SECRET);
$res = $AzureService->retrieveAccessToken();
if(!$res) {
	error_log($AzureService->getLastError());
	exit;
}  
  
// Create connection
  
//DB2:
$server="Driver={IBM i Access ODBC Driver};System=127.0.0.1;Uid=".AH_DB2_USER.";Pwd=".AH_DB2_PASS.";TRANSLATE=1;"; 
$user=AH_DB2_USER; 
$pass=AH_DB2_PASS;   
$db2conn=odbc_connect($server,$user,$pass); 
if(!$db2conn) {
	$errMsg = "Errore connessione al database : ".odbc_errormsg($db2conn);
	error_log($errMsg);
	exit;
}

$query = "
SELECT HAMAIL 
FROM JRGDTA94C.HANNO0F 
LIMIT 1, 300
";
$res = odbc_exec($db2conn,$query);
if(!$res) {
  $errMsg = "Error mysql query: " . odbc_errormsg($db2conn);
  error_log($errMsg);
  exit;
}

$toArray = array();
while($row = odbc_fetch_array($res)) {
	$curMailStr = trim($row["HAMAIL"]);
	$curMailArr = explode(";",$curMailStr);
	for($m=0;$m<count($curMailArr);$m++) {
		$curMail = str_replace(array("\n","\r","\t"),array("","",""),$curMailArr[$m]);
		if(!in_array($curMail,$toArray)) $toArray[] = $curMail;
	}
}
 
$sentMailsCounter = 0;
$errorMailsCounter = 0;
for($m=0;$m<count($toArray);$m++) { //
	$curMail = trim($toArray[$m]);
	  
	//log delle mail già inviate:
	$query = "SELECT 'S' AS CHKINS FROM BCD_DATIV2.WLGMAHA0F WHERE MHMAIL = ? ";
	$pstmt = odbc_prepare($db2conn,$query);
	if($pstmt) {
		$arrParams = array();	
		$arrParams[] = $curMail; 			
		 
		$resChk = odbc_execute($pstmt,$arrParams);
		if(!$resChk) {
			$errMsg = "Errore query 1: ".odbc_errormsg($db2conn);
			error_log($errMsg);
			exit;
		}
	} else {
		$errMsg = "Errore prepare 1: ".odbc_errormsg($db2conn);
		error_log($errMsg);
		exit;
	}	
	$rowChk = odbc_fetch_array($pstmt); 
	$chkIns = "";
	if(isset($rowChk["CHKINS"])) $chkIns = $rowChk["CHKINS"];
	
	if($chkIns!="S") {
		if(strpos($curMail,"@")!==false && strpos($curMail,".")!==false) {
			//nuovo contatto, invio mail:
			 
			$mailSubject = "Hannover Messe (22-26 April 2024)";
			$mailBody = '<p>Buongiorno,</p>
			<p>with this E-mail we would like to invite you to visit our Stand at the International Trade Fair HANNOVER MESSE (22-26 April 2024).</p>
			<p>It will be a pleasure for us to meet your staff and talk about our business relationship.</p>
			<p>Kind regards,</p> 
			'.$mailSignature;
			  
			$from = "giorgia.belotti@rgpballs.com";
			
			error_log('Invio mail a:'.$curMail);  
			 
			//$to = array("mattia.marsura@bigblue.it","emilio.colosimo@rgpballs.com","emiglio84@gmail.com","gabrielebarzaghi@icloud.com","duilio_canta@yahoo.com");
			//$to = array("mattia.marsura@bigblue.it");
			$to = array($curMail);
			 
			$resMail = $AzureService->sendMail($from,$to,$mailSubject,$mailBody);
			if(!$resMail) {
				error_log($AzureService->getLastError()); 
				
				$errorMailsCounter++; 
			} else {
				error_log("Mail inviata a ".$curMail);
				
				//dopo aver inviato (con successo) inserisco: 
				$query = "INSERT INTO BCD_DATIV2.WLGMAHA0F (MHMAIL, MHDTIN, MHORIN) VALUES (?,?,?)";		
				$pstmtIns = odbc_prepare($db2conn,$query);
				if($pstmtIns) {
					$arrParams = array();	 
					$arrParams[] = $curMail;  
					$arrParams[] = date("Ymd"); 
					$arrParams[] = date("His"); 
					 
					$resIns = odbc_execute($pstmtIns,$arrParams);
					if(!$resIns) {
						$errMsg = "Errore query 2: ".odbc_errormsg($db2conn);
						error_log($errMsg);
						exit;
					}
				} else {
					$errMsg = "Errore prepare 2: ".odbc_errormsg($db2conn);
					error_log($errMsg);
					exit;
				}			
				
				$sentMailsCounter++;
				
				sleep(1);
				
			}
		
		} else {
			error_log("Salto indirizzo mail:".$curMail.":indirizzo mail non valido"); 
			$errorMailsCounter++; 
		}
	} 
	  
}
 
//
error_log("Totale mail inviate: ".$sentMailsCounter);
error_log("Totale errori: ".$errorMailsCounter);
//error_log("Totale mail inviate: ".count($sentMailsCounter));
  
odbc_close($db2conn);

