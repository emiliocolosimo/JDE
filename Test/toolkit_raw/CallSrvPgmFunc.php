<?php
/* This program is calling a service program (*SRVPGM) function which retrieves QCCSID system value */
include_once 'authorization.php';

// *** XML input script ***
$xmlIn = <<<ENDPROC
<?xml version='1.0' encoding='ISO-8859-1'?>
<script>
<pgm name='ZSXMLSRV' lib='ZENDSVR' func='RTVSYSVAL'>
<parm><data type='1a'/></parm>
<parm><data type='10a'>QCCSID</data></parm>
<parm><data type='1024a'/></parm>
</pgm>
<pgm name='ZSXMLSRV' lib='ZENDSVR' func='RTVSYSVAL'>
<parm><data type='1a'/></parm>
<parm><data type='10a'>QLANGID</data></parm>
<parm><data type='1024a'/></parm>
</pgm>
</script>"
ENDPROC;

// *** call xmlservice
$xmlOut = xmlservice($xmlIn);

// *** output
echo "\n<pre>\n";
$xmlobj = simplexml_load_string($xmlOut);
$allpgms = $xmlobj->xpath('/script/pgm');
foreach ($allpgms as $pgm) {
  $name = $pgm->attributes()->name;
  $lib  = $pgm->attributes()->lib;
  $func = $pgm->attributes()->func;
  $parm = $pgm->xpath('parm');
  $value= (string)$parm[1]->data;
  $data = (string)$parm[2]->data;
  echo "System value $value = $data ($lib/$name $func)\n";
}
echo "\n</pre>\n";
?>
