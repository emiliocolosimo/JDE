<?php
 /*
RPG program parameters definition
               PLIST                                          
               PARM                    CODE             10    
               PARM                    NAME             10   
 
 */
 
include_once 'authorization.php';

// *** XML input script ***
$code    = $_POST ['code'];
$desc    = '-';
$xmlIn   = "<?xml version='1.0' encoding='ISO-8859-1'?>
<script>
<pgm name='COMMONPGM' lib='ZENDSVR'>
<parm><data type='10a'>$code</data></parm>
<parm><data type='10a'>$desc</data></parm>
</pgm>
</script>";


// *** call xmlservice
$xmlOut = xmlservice($xmlIn);

// *** output
$out = simpleData($xmlOut);
echo "<table border='1'>\n";
echo "<th>Parameter name</th><th>Parameter Value</th>\n";
echo "<tr><td>CODE</td><td>{$out['CODE']}</td></tr>\n";
echo "<tr><td>NAME</td><td>{$out['NAME']}</td></tr>\n";
echo "</table>\n";

function simpleData($xml) {
  $xmlobj = simplexml_load_string($xml);
  $allpgms = $xmlobj->xpath('/script/pgm');
  $pgm  = $allpgms[0];
  $name = $pgm->attributes()->name;
  $lib  = $pgm->attributes()->lib;
  $parm = $pgm->xpath('parm');
  $out  = array();
  $out['CODE'] = (string)$parm[0]->data;
  $out['NAME'] = (string)$parm[1]->data;
  return $out;
}

?>
