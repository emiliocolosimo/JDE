<?php
/*
RPG program parameters definition
 INCHARA        S              1a       
 INCHARB        S              1a       
 INDEC1         S              7p 4     
 INDEC2         S             12p 2     
 INDS1          DS                      
  DSCHARA                      1a       
  DSCHARB                      1a       
  DSDEC1                       7p 4     
  DSDEC2                      12p 2 
*/
include_once 'authorization.php';

// *** XML input script ***
$xmlIn   = "<?xml version='1.0' encoding='ISO-8859-1'?>
<script>
<pgm name='ZZCALL' lib='ZENDSVR'>
<parm><data type='1a'>Y</data></parm>
<parm><data type='1a'>Z</data></parm>
<parm><data type='7p4'>001.0001</data></parm>
<parm><data type='12p2'>0000000003.04</data></parm>
<parm>
 <ds>
  <data type='1a'>A</data>
  <data type='1a'>B</data>
  <data type='7p4'>005.0007</data>
  <data type='12p2'>0000000006.08</data>
 </ds>
</parm>
</pgm>
</script>";

// *** call xmlservice
$xmlOut = xmlservice($xmlIn);

// *** output
$in = simpleData($xmlIn);
$out = simpleData($xmlOut);
echo "<table border='1'>\n";
echo "<th>Parameter name</th><th>Input value</th><th>Output value</th>\n";
echo "<tr><td>INCHARA</td><td>{$in['INCHARA']}</td><td>{$out['INCHARA']}</td></tr>\n";
echo "<tr><td>INCHARB</td><td>{$in['INCHARB']}</td><td>{$out['INCHARB']}</td></tr>\n";
echo "<tr><td>INDEC1</td><td>{$in['INDEC1']}</td><td>{$out['INDEC1']}</td></tr>\n";
echo "<tr><td>INDEC2</td><td>{$in['INDEC2']}</td><td>{$out['INDEC2']}</td></tr>\n";
echo "<tr><td>INDS1.DSCHARA</td><td>{$in['INDS1']['DSCHARA']}</td><td>{$out['INDS1']['DSCHARA']}</td></tr>\n";
echo "<tr><td>INDS1.DSCHARB</td><td>{$in['INDS1']['DSCHARB']}</td><td>{$out['INDS1']['DSCHARB']}</td></tr>\n";
echo "<tr><td>INDS1.DSDEC1</td><td>{$in['INDS1']['DSDEC1']}</td><td>{$out['INDS1']['DSDEC1']}</td></tr>\n";
echo "<tr><td>INDS1.DSDEC2</td><td>{$in['INDS1']['DSDEC2']}</td><td>{$out['INDS1']['DSDEC2']}</td></tr>\n";
echo "</table>\n";

function simpleData($xml) {
  $xmlobj = simplexml_load_string($xml);
  $allpgms = $xmlobj->xpath('/script/pgm');
  $pgm  = $allpgms[0];
  $name = $pgm->attributes()->name;
  $lib  = $pgm->attributes()->lib;
  $parm = $pgm->xpath('parm');
  $out  = array();
  $out['INCHARA'] = (string)$parm[0]->data;
  $out['INCHARB'] = (string)$parm[1]->data;
  $out['INDEC1']  = (string)$parm[2]->data;
  $out['INDEC2']  = (string)$parm[3]->data;
  $ds             =         $parm[4]->ds;
  $out['INDS1']['DSCHARA'] = (string)$ds->data[0];
  $out['INDS1']['DSCHARB'] = (string)$ds->data[1];
  $out['INDS1']['DSDEC1']  = (string)$ds->data[2];
  $out['INDS1']['DSDEC2']  = (string)$ds->data[3];
  return $out;
}
?>
