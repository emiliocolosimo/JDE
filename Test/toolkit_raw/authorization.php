<?php
session_start();
 
$db = 'DRIVER=IBM i Access ODBC Driver;SYSTEM=localhost;UID=MATTIASEC;PWD=M4TT14__;Naming=1;DefaultLibraries=BCDCRM;Decimal=1';           //i5 side
$user = 'MATTIASEC';
$pass = 'M4TT14__';
$test_lib = 'QGPL';

if (isset( $_SESSION['dbname']))//S0663764
	$db =   $_SESSION['dbname'];
if (isset( $_SESSION['user']))
	$user = $_SESSION['user'];   

if (isset( $_SESSION['pass']))
	$pass = $_SESSION['pass'];
	  
if (isset( $_SESSION['tmplib']))
	$test_lib = $_SESSION['tmplib'];


// *** /cgi-bin/xmlcgi.pgm (REST interface) ***
$i5rest   = "http://174.79.32.155/cgi-bin/xmlcgi.pgm";
$i5restdb = $db;          // only *LOCAL tested
if ($user == '') {
  $i5restuser = '*NONE';  // *NONE not allowed by default compile
  $i5restpass = '*NONE';  // *NONE not allowed by default compile
}
else {
  $i5restuser = $user;
  $i5restpass = $pass;
}
$i5restsz   = "512000";    // size expected XML output

require_once 'xmlservice_drivers.php';
require_once 'xmlservice_junk_away.php';
?>
