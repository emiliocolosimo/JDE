<?php
// PHP driver: xmlservice_pdo_ibm.php
// Notes:
// Assumes you have PHP pdo_ibm driver enabled in Zend Server.
// You may use PHP pdo_ibm driver on IBM i (1-tier)
// or from Linux/Windows to IBM i (2-tier).
// For help:
// http://www.youngiprofessionals.com/wiki/index.php/PHP/DB2
if (!extension_loaded('pdo_ibm')) {
  die('Error: pdo_ibm extension not loaded, use Zend Server GUI to enable.');
}

// *** XMLSERVICE call (DB2 driver) ***
// Note: 
// Connection ($conn) is global to avoid looping
// re-open/re-close that errors most drivers 
function xmlservice($xml) {
global $fast, $db, $user, $pass, $ipc, $ctl, $conn, $lib, $plug, $plugR;
  $xmlIn = $xml;
  $xmlOut = '';
  if (!$conn) {
    $database = "ibm:".$db;
    try {
      if ($fast) $opt = array(PDO::ATTR_PERSISTENT=>true, PDO::ATTR_AUTOCOMMIT=>true);  // persistent/pooled connection
      else $opt = array(PDO::ATTR_AUTOCOMMIT=>true);                                    // full open/close connection
      $conn = new PDO($database, strtoupper($user), strtoupper($pass), $opt);
      if (!$conn) throw new Exception("Bad");
    } catch( Exception $e ) { 
      die("Bad connect: $database, $user"); 
    }
  }
  try {
    $stmt = $conn->prepare("call $lib.$plug(?,?,?,?)");   // Call XMLSERVICE 
                                                          // stored procedure interface
                                                          // in/out parameter (xmlOut)
                                                          // sizes: iPLUG4K - iPLUG15M
    if (!$stmt) throw new Exception('Bad');
  } catch( Exception $e ) { 
    $err = $conn->errorInfo();
    $cod = $conn->errorCode();
    die("Bad prepare: ".$cod." ".$err[0]." ".$err[1]." ".$err[2]);
  }
  try {
    $r1=$stmt->bindParam(1,$ipc, PDO::PARAM_STR);
    $r2=$stmt->bindParam(2,$ctl, PDO::PARAM_STR);
    $r3=$stmt->bindParam(3,$xmlIn, PDO::PARAM_STR);
    $r4=$stmt->bindParam(4,$xmlOut, PDO::PARAM_STR|PDO::PARAM_INPUT_OUTPUT);
    $ret = $stmt->execute();
    if (!$ret) throw new Exception('Bad');
  } catch( Exception $e ) {
    $err = $stmt->errorInfo();
    $cod = $stmt->errorCode();
    die("Bad execute: ".$cod." ".$err[0]." ".$err[1]." ".$err[2]);
  }
  return driverJunkAway($xmlOut);                         // just in case driver odd
                                                          // ... possible junk end record,
                                                          // record</script>junk
}
?>
