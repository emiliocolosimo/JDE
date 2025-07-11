<?php
session_start();
if ($_SESSION['bdauth'] !== 'UFFICIOCOMMERCIALE') die("Accesso negato");
include("config.inc.php");

/*
define('DB2_USER', 'JDESPYTEST');
define('DB2_PASS', 'JBALLS18');

error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Europe/Rome');

$server="Driver={IBM i Access ODBC Driver};System=127.0.0.1;Uid=".DB2_USER.";Pwd=".DB2_PASS.";TRANSLATE=1;";
$user=DB2_USER;
$pass=DB2_PASS;
$db_connection=odbc_connect($server,$user,$pass);

if(!$db_connection) {
    $errMsg = "Errore connessione al database : ".odbc_errormsg();
    error_log($errMsg);
    exit;
}
*/
// Costruzione filtri dinamici
$filtri = [];
if (!empty($_GET['numlista'])) $filtri[] = "F4211.SDPSN LIKE '%" . addslashes($_GET['numlista']) . "%'";
if (!empty($_GET['cliente'])) $filtri[] = "F0101.ABALPH LIKE '%" . addslashes($_GET['cliente']) . "%'";

$where = "F4211.SDDELN = 0 AND F4211.SDNXTR < '570' AND F4211.SDPSN <> 0";
if (count($filtri)) $where .= " AND " . implode(" AND ", $filtri);

$sql = "SELECT
  F4211.SDAN8 AS CODCLIENTE,
  COALESCE(F4211.SDAN8, '') || ' - ' || COALESCE(F4211.SDSHAN, '') || ' - ' || COALESCE(F0111.WWMLNM, '') || ' - ' || 
  COALESCE(F0116.ALCTY1, '') || ' - ' || COALESCE(F0116.ALADDS, '') || ' - ' || COALESCE(F0116.ALCTR, '') AS CODSPED,
  COALESCE(F0101.ABALPH, '') AS CODSPEDDESCR,
  F00365.ONDATE AS DATASPED,
  F4211.SDPSN AS NUMLISTA,
  F4211.SDPDDJ AS DATAJDE,
REPLACE(VARCHAR_FORMAT(SUM(F4211.SDITWT / 100), '999990.99'), '.', ',') AS PESO,
COALESCE(F42522HEA.W1CONF, '') AS CONFEZIONATORE,
(SELECT 
    COALESCE(MAX(CASE WHEN M.W1MSAT = '1' THEN 'SI' ELSE 'NO' END), 'NO') 
 FROM JRGDTA94C.F42522MSG M 
 WHERE M.W1PSN = F4211.SDPSN
) AS MSGATT
FROM JRGDTA94C.F4211 AS F4211
LEFT JOIN JRGDTA94C.F0101 AS F0101 ON F4211.SDAN8 = F0101.ABAN8
LEFT JOIN JRGDTA94C.F0111 AS F0111 ON F4211.SDSHAN = F0111.WWAN8 AND F0111.WWIDLN = 0
LEFT JOIN JRGDTA94C.F0116 AS F0116 ON F4211.SDAN8 = F0116.ALAN8
LEFT JOIN JRGDTA94C.F42522HEA as F42522HEA ON F42522HEA.W1PSN = F4211.SDPSN
LEFT JOIN JRGDTA94C.F00365 AS F00365 ON F00365.ONDTEJ = F4211.SDPDDJ
WHERE $where
GROUP BY 
  F4211.SDAN8, F4211.SDSHAN, F00365.ONDATE, F4211.SDPDDJ, 
  F0111.WWMLNM, F0101.ABALPH , F0116.ALCTY1, F0116.ALADDS, F0116.ALCTR, F4211.SDPSN , F42522HEA.W1CONF 
ORDER BY F4211.SDPDDJ , F4211.SDSHAN, F4211.SDPSN
";
$liste = [];
$stmt = odbc_exec($db_connection, $sql);

if (!$stmt) {
    die("Errore nella query: " . odbc_errormsg($conn));
}

while ($riga = odbc_fetch_array($stmt)) {
    $liste[] = $riga;
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Sospensione Liste</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="container mt-4">
<h3>Sospensione Liste</h3>

<form class="row g-2 mb-3" method="get">
    <div class="col-md-3">
        <input type="text" name="numlista" class="form-control" placeholder="Numero lista" value="<?= htmlspecialchars($_GET['numlista'] ?? '') ?>">
    </div>
    <div class="col-md-3">
        <input type="text" name="cliente" class="form-control" placeholder="Cliente" value="<?= htmlspecialchars($_GET['cliente'] ?? '') ?>">
    </div>
    <div class="col-md-3">
        <button class="btn btn-primary" type="submit">Cerca</button>
    </div>
</form>

<table class="table table-bordered table-sm">
    <thead>
    <tr>
        <th>Num. Lista</th>
        <th>Cliente</th>
        <th>Data Spedizione</th>
        <th>Peso</th>
        <th>Confezionatore</th>
        <th>Messaggi Attivi</th>
        <th>Azione</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($liste as $r): ?>
        <tr>
            <td><?= $r['NUMLISTA'] ?></td>
            <td><?= $r['CODSPEDDESCR'] ?></td>
            <td><?= $r['DATASPED'] ?></td>
            <td><?= $r['PESO'] ?> kg</td>
            <td><?= $r['CONFEZIONATORE'] ?></td>
            <td><?= $r['MSGATT'] == 1 ? 'Sì' : 'No' ?></td>
            <td>
                <?php if ($r['MSGATT'] != 1): ?>
                    <form method="post" action="scrivisospensione.php">
                        <input type="hidden" name="numlista" value="<?= $r['NUMLISTA'] ?>">
                        <button class="btn btn-warning btn-sm" onclick="return confirm('Confermi la sospensione della lista?')">Sospendi</button>
                    </form>
                <?php else: ?>
                    <span class="text-muted">Già sospesa</span>
                <?php endif ?>
            </td>
        </tr>
    <?php endforeach ?>
    </tbody>
</table>
</body>
</html>