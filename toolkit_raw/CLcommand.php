<pre>
This program runs following CL commands:
 - ADDLIBLE ZENDSVR
 - DSPLIBL
This is what happens under the covers of new PHP Toolkit,
you can use this XML "raw" interface without Toolkit.
</pre>

<?php
include_once 'authorization.php';

// *** XML input script WRKSYSVAL OUTPUT(*PRINT) ***
$xmlIn   = <<<ENDPROC
<?xml version="1.0" encoding="ISO-8859-1"?>
<script>
<cmd>addlible BCD_DATIV2</cmd>
<sh rows='on'>/QOpenSys/usr/bin/system -i 'DSPLIBL'</sh>
</script>
ENDPROC;

// *** call xmlservice
$ctl .= " *cdata";
$xmlOut = xmlservice($xmlIn);

// *** output
echo "\n<pre>\n";
$xmlobj = simplexml_load_string($xmlOut);
$sh = $xmlobj->xpath('/script/sh');
foreach ($sh[0]->row as $row) {
  $line = (string)$row;
  echo "$line\n";
}
echo "\n</pre>\n";

?>
