<?php 
//CALL PGM(JRGOBJ94P/LCIMPCON)

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set("log_errors", 1);

include("/www/php80/htdocs/leadchampion/config.inc.php");
ini_set("error_log", "/www/php80/htdocs/leadchampion/logs/getCompanies_".date("Ym").".log");
include("/www/php80/htdocs/leadchampion/classes/CompaniesImporter.class.php");
$CompaniesImporter = new CompaniesImporter();
$CompaniesImporter->import();

ini_set("error_log", "/www/php80/htdocs/leadchampion/logs/getContacts_".date("Ym").".log");
include("/www/php80/htdocs/leadchampion/classes/ContactsImporter.class.php");
$ContactsImporter = new ContactsImporter();
$ContactsImporter->import();

ini_set("error_log", "/www/php80/htdocs/leadchampion/logs/getVisits_".date("Ym").".log");
include("/www/php80/htdocs/leadchampion/classes/VisitsImporter.class.php");
$VisitsImporter = new VisitsImporter();
$VisitsImporter->import();
/*
ini_set("error_log", "/www/php80/htdocs/leadchampion/logs/getWebsiteDomains_".date("Ym").".log");
include("/www/php80/htdocs/leadchampion/classes/WebsiteDomains.class.php");
$WebsiteDomains = new WebsiteDomains();
$WebsiteDomains->import();
*/