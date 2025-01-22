<?php
// *** default DB2 ***
$fast    = false;            // persistent db2 connection
$plug    = "iPLUG5M";        // iPLUGxxx various sizes
$plugR   = "iPLUGR5M";       // iPLUGRxxx various sizes (result set)
$lib     = 'ZENDSVR';        // library XMLSERVICE compiled 
// *** default parameters xmlservice ***
// $ctl     = "*here";       // stateless run (xmlservice in-process)
$ctl     = "*sbmjob";        // state full run (xmlservice seperate process)
$ipc     = "/tmp/packers";   // ipc ignored $ctl="*here"
$xmlIn   = "";               // *** XML input script ***
$xmlOut  = "";               // *** XML output returned ***
// *** which xmlservice transport driver? ***
// http://ibmi/test.php?driver=ibm_db2

if (isset($_GET['driver'])) $driver = $_GET['driver'];
if (isset($_POST['driver'])) $driver = $_POST['driver'];
switch($driver) {
case 'rest':
  require_once 'xmlservice_rest.php';    // $xmlOut = xmlservice($xmlIn)
  break;
case 'odbc':
  $ctl .= " *hack";                      // quirky odbc drivers (result set) 
  require_once 'xmlservice_odbc.php';    // $xmlOut = xmlservice($xmlIn)
  break;
case 'pdo_ibm':
  require_once 'xmlservice_pdo_ibm.php'; // $xmlOut = xmlservice($xmlIn)
  break;
case 'ibm_db2':
default:
  require_once 'xmlservice_ibm_db2.php'; // $xmlOut = xmlservice($xmlIn)
  break;
}
echo "<h3>======== Driver: $driver ======== </h3>\n";
?>
