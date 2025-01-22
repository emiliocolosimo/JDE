<?php
// PHP driver: xmlservice_rest.php
// Notes:
// Assumes you have XMLSERVICE REST driver enabled
// HTTP Server (Apache) and a IBM i version match
// XMLSERVICE/XMLCGI.PGM (you compiled).
//
// You may use XMLSERVICE REST driver on IBM i (1-tier)
// or from Linux/Windows to IBM i (2-tier).
// For help:
// http://www.youngiprofessionals.com/wiki/index.php/XMLService

// *** XMLSERVICE call (REST + internal RPG DB2 driver) ***
// Example: /www/zendsvr/conf/httpd.conf
// ScriptAlias /cgi-bin/ /QSYS.LIB/XMLSERVICE.LIB/
// <Directory /QSYS.LIB/XMLSERVICE.LIB/>
//   AllowOverride None
//   order allow,deny
//   allow from all
//   SetHandler cgi-script
//   Options +ExecCGI
// </Directory>
function xmlservice($xml) {
global $fast, $db, $user, $pass, $ipc, $ctl, $conn, $lib, $plug, $plugR, 
       $i5rest, $i5restdb, $i5restuser, $i5restpass, $i5restsz;
  $was = array('"');
  $now = array("'");
  $xmlIn = str_replace($was,$now,$xml);
  $xmlOut = '';
  $postdata = http_build_query(
   array(
     'db2' => $i5restdb,
     'uid' => $i5restuser,
     'pwd' => $i5restpass,
     'ipc' => $ipc,
     'ctl' => $ctl,
     'xmlin' => $xmlIn,
     'xmlout' => $i5restsz    // size expected XML output
   )
  );
  $opts = array('http' =>
   array(
     'method'  => 'POST',
     'header'  => 'Content-type: application/x-www-form-urlencoded',
     'content' => $postdata
   )
  );
  $context  = stream_context_create($opts);
  $xmlOut = file_get_contents($i5rest, false, $context);
  var_dump($xmlOut);
  return driverJunkAway($xmlOut);                         // just in case driver odd
                                                          // ... possible junk end record,
                                                          // record</script>junk
}
?>
