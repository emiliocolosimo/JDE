<?php 
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set("log_errors", 1);
ini_set("error_log", "/www/php80/htdocs/leadchampion/logs/getVisits_".date("Ym").".log");

include("/www/php80/htdocs/leadchampion/config.inc.php");
include("/www/php80/htdocs/leadchampion/classes/VisitsImporter.class.php");

$VisitsImporter = new VisitsImporter();
$VisitsImporter->import();