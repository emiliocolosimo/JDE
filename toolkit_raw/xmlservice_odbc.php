<?php
// PHP driver: xmlservice_odbc.php
// Notes:
// Assumes you have PHP odbc driver enabled in Zend Server.
// You may use PHP odbc driver on IBM i (1-tier)
// or from Linux/Windows to IBM i (2-tier).
// For help:
// http://www.youngiprofessionals.com/wiki/index.php/PHP/DB2
if (!extension_loaded('odbc')) {
  die('Error: odbc extension not loaded, use Zend Server GUI to enable.');
}

// *** XMLSERVICE call (DB2 driver) ***
// Note: 
// Connection ($conn) is global to avoid looping
// re-open/re-close that errors most drivers 
function xmlservice($xml) {
global $fast, $db, $user, $pass, $ipc, $ctl, $conn, $lib, $plug, $plugR;

  $lib = "QXMLSERV";

  $xmlIn = $xml;
  $xmlOut = '';
  if (!$conn) {
    if ($fast) $conn = odbc_pconnect($db, $user, $pass);  // persistent/pooled connection
    else $conn = odbc_connect($db, $user, $pass);         // full open/close connection
  }
  $stmt = odbc_prepare($conn, "call $lib.$plugR(?,?,?)"); // Call XMLSERVICE 
                                                          // stored procedure interface
                                                          // result set return (fetch)
                                                          // sizes: iPLUGR4K - iPLUGR15M
  if (!$stmt) die("Bad prepare: ".odbc_errormsg());
  $options = array($ipc,                                  // ? - /tmp/raw_$user (*sbmjob)
                   $ctl,                                  // ?- *here or *sbmjob
                   $xmlIn);                               // ?- XML input script
  // bad behavior odbc extension 
  // ... IBM i result set warning???
  error_reporting(~E_ALL);                                // bad behavior odbc extension
                                                          // ... IBM i result set warning??? 
  $ret=odbc_execute($stmt,$options);
  if (!$ret) die("Bad execute: ".odbc_errormsg());
  error_reporting(E_ALL); 
  while(odbc_fetch_row($stmt)) {
    $xmlOut .= driverJunkAway(odbc_result($stmt, 1));     // bad behavior odbc extension
                                                          // ... possible junk end record,
                                                          // xmlservice provided $ctl='*hack' 
                                                          // record</hack>junk
  }
  return $xmlOut;
}
?>
