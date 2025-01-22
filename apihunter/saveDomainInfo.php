<?php 
exit;

header('Content-Type: application/json; charset=utf-8');

include("/www/php80/htdocs/apihunter/config.inc.php");
include("/www/php80/htdocs/apihunter/classes/DomainSearch.class.php");

ini_set("error_log", "/www/php80/htdocs/apihunter/logs/getDomain_".date("Ym").".log");

$domain = "";
if(isset($_REQUEST["domain"])) $domain = $_REQUEST["domain"];
	 
if($domain=="") {
	echo '{"stat":"ERR","msg":"Specificare il parametro domain"}';
	exit;
}

if(strpos($domain,"@") == true || strpos($domain,".") == false) {
	echo '{"stat":"ERR","msg":"Parametro domain non valido"}';
	exit;
}

$DomainSearch = new DomainSearch();
$res = $DomainSearch->saveInfo($domain);
if($res) echo '{"stat":"OK"}';
else echo '{"stat":"ERR","msg":"Errore nel salvataggio dei dati"}';