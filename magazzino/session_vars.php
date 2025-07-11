<?php
// session_vars.php
$bdnome    = $_SESSION['bdnome']    ?? '';
$bdcogn    = $_SESSION['bdcogn']    ?? '';
$bdnick    = $_SESSION['bdnick']    ?? '';
$bdauth    = $_SESSION['bdauth']    ?? '';
$bdemai    = $_SESSION['bdemai']    ?? '';
$bdrepa    = $_SESSION['bdrepa']    ?? '';
$bdposi    = $_SESSION['bdposi']    ?? '';
$bdcoge    = $_SESSION['bdcoge']    ?? '';
$bdreli    = $_SESSION['bdreli']    ?? '';
$bdconf    = $_SESSION['bdconf']    ?? '';
$bdtimb    = $_SESSION['bdtimb']    ?? '';
$bdbdtm    = $_SESSION['bdbdtm']    ?? '';
$bdbadg    = $_SESSION['bdbadg']    ?? '';

$utenteinfo = [[
    'BDNOME' => $bdnome,
    'BDCOGN' => $bdcogn,
    'BDNICK' => $bdnick,
    'BDAUTH' => $bdauth,
    'BDEMAI' => $bdemai,
    'BDREPA' => $bdrepa,
    'BDPOSI' => $bdposi,
    'BDCOGE' => $bdcoge,
    'BDRELI' => $bdreli,
    'BDCONF' => $bdconf,
    'BDTIMB' => $bdtimb,
    'BDBDTM' => $bdbdtm,
    'BDBADG' => $bdbadg
]];