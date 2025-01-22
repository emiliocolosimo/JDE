<?php

error_reporting(E_ALL);
ini_set("display_errors",0);
ini_set("log_errors",1);
ini_set("error_log", "/www/php80/htdocs/azureApps/logs/sendMail_".date("Ym").".log");
require("/www/php80/htdocs/azureApps/config.inc.php");
require("/www/php80/htdocs/azureApps/classes/AzureService.class.php");

$AzureService = new AzureService();
$AzureService->setTenantId(TENANT_ID);
$AzureService->setClientId(CLIENT_ID);
$AzureService->setClientSecret(CLIENT_SECRET);
$res = $AzureService->retrieveAccessToken();
if(!$res) {
	error_log($AzureService->getLastError());
	exit;
}

$originalMailSignature = $mailSignature;

$userLocale = 'de_DE';
if(!isset($mailSubjectText[$userLocale])) {
	error_log("Errore recupero oggetto mail:".$userLocale);
	exit;
}
if(!isset($mailBodyText[$userLocale])) {
	error_log("Errore recupero corpo mail:".$userLocale);
	exit;
}
$mailSubject = "Prova"; //$mailSubjectText[$userLocale];
$mailLanguageText = $mailBodyText[$userLocale];
$mailLanguageText = str_replace(array("[-USER_NAME-]","[-USER_SURNAME-]"),array("Mattia","Marsura"),$mailLanguageText);

if($curLocale=="it_IT") {
	$from = "beatrice.barcella@rgpballs.com";
	$mailSignature = str_replace("[-NOME_FIRMA-]","Beatrice Barcella",$originalMailSignature);
}
else {
	$from = "viktoriia.gorzova@rgpballs.com";
	$mailSignature = str_replace("[-NOME_FIRMA-]","Viktoriia Gorzova",$originalMailSignature);
}
			 

$mailBody = '
<div style="font-size:14px">
<p>
	<img width="270" height="270" style="width:2.8125in;height:2.8125in" src="https://download.rgpballs.com/Downloads/foto1.jpg" >&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
	<img width="270" height="270" style="width:2.8125in;height:2.8125in" src="https://download.rgpballs.com/Downloads/foto2.jpg" >&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
	<img width="270" height="270" style="width:2.8125in;height:2.8125in" src="https://download.rgpballs.com/Downloads/foto3.jpg" >
</p>
<p>
	<img width="662" height="323" style="width:6.8958in;height:3.3645in" src="https://download.rgpballs.com/Downloads/foto4.png" >
</p>	 
'.$mailLanguageText.' 
</div> 
'.$mailSignature;

$to = array("mattia.marsura@bigblue.it"); 


$res = $AzureService->sendMail($from,$to,$mailSubject,$mailBody);
if(!$res) {
	error_log($AzureService->getLastError());
	exit;
} else {
	error_log("Mail inviata a ".$to);
}
  