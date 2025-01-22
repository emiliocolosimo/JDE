<?php 
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set("log_errors", 1);
ini_set("error_log", "/www/php80/htdocs/leadchampion/logs/oneTime/getContacts_".date("Ym").".log");

include("/www/php80/htdocs/leadchampion/oneTime/config.inc.php");
include("/www/php80/htdocs/leadchampion/oneTime/classes/ContactsImporter.class.php");

$ContactsImporter = new ContactsImporter();
$ContactsImporter->import();