<?php
/* This program retrieves spool file list current and job displays the content (<max). */
include_once 'authorization.php';

$ctl .= " *cdata";
$max_out = 3;

// *** call IBM i XMLSERVICE (collect spool files) ***
$xmlIn = <<<ENDPROC
<?xml version='1.0' encoding='ISO-8859-1'?>
<script>
<sh rows='on'>/QOpenSys/usr/bin/system -i 'WRKSPLF'</sh>
</script>
ENDPROC;
$conn = false;
$spool = array();
$xmlOut = xmlservice($xmlIn); 
$xmlobj = simplexml_load_string($xmlOut);
$sh = $xmlobj->xpath('/script/sh');
foreach ($sh[0]->row as $row) {
  // WRKSPLF output:
  // QPJOBLOG QTMHHTTP PRT01 XTOOLKIT RDY || 5 1 *STD 5 02/14/12 10:23:38 2 XTOOLKIT 201253 ...
  // 0        1        2     3        4   || 0 1 2    3 4        5        6 7        8
  // |        |                           ||                              | |        |
  // SPOOLNAM JOBUSR                      ||                              | JOBNAME  JOBNBR
  //                                      ||                              SPOOLNBR
  $line = (string)$row;
  if (strpos($line,' *STD ')<1) continue;
  if (strpos($line,' RDY ')<1) continue;
  $entry= array();
  $half = explode (' RDY ',$line);
  if (!isset($half[1])) continue; 
  $data = array();
  $d = explode (' ',$half[0]);
  foreach($d as $t) if (trim($t)!='') array_push($data,$t);
  $entry["SPOOLNAME"]=$data[0];
  $entry["JOBUSR"]   =$data[1];
  $data = array();
  $d = explode (' ',$half[1]);
  foreach($d as $t) if (trim($t)!='') array_push($data,$t);
  $entry["SPOOLNBR"] =$data[6];
  $entry["JOBNAME"]  =$data[7];
  $entry["JOBNBR"]   =$data[8];
  array_push($spool,$entry);
  if (count($spool) > $max_out) break;
}


// *** call IBM i XMLSERVICE (display spool files) ***
$xml = <<<ENDPROC
<?xml version='1.0' encoding='ISO-8859-1'?>
<script>
<cmd exec='system'>CPYSPLF FILE(SPOOLNAME) TOFILE(QTEMP/QOUT) JOB(JOBNBR/JOBUSR/JOBNAME) SPLNBR(SPOOLNBR) TOMBR(*FIRST) MBROPT(*REPLACE)</cmd>
<sh rows='on'>/QOpenSys/usr/bin/system -i "QSH CMD('catsplf -j JOBNBR/JOBUSR/JOBNAME SPOOLNAME SPOOLNBR')"</sh>
</script>
ENDPROC;
echo "<table border='1'>\n";
foreach($spool as $entry) {
  $was = array("SPOOLNAME","JOBNAME","JOBUSR","JOBNBR","SPOOLNBR");
  $now = array($entry["SPOOLNAME"],$entry["JOBNAME"],$entry["JOBUSR"],$entry["JOBNBR"],$entry["SPOOLNBR"]);
  $title = "FILE(SPOOLNAME) JOB(JOBNBR/JOBUSR/JOBNAME) SPLNBR(SPOOLNBR)";
  $title = str_replace($was,$now,$title);
  $xmlIn = str_replace($was,$now,$xml);
  $xmlOut = xmlservice($xmlIn); 
  $xmlobj = simplexml_load_string($xmlOut);
  $sh = $xmlobj->xpath('/script/sh');
  if (!$sh) die("Missing XML sh info");
  echo "<th>$title</th>\n";
  echo "<tr><td><pre>\n";
  foreach ($sh[0]->row as $row) echo (string)$row."\n";
  echo "</pre></td></tr>\n";
}
echo "</table>\n";
?>
