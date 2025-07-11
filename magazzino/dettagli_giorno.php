<?php
require("/www/php80/htdocs/sped/config.inc.php");

$utente = urldecode($_GET['utente'] ?? '');
$data = $_GET['data'] ?? '';

// Connessione al DB
$server = "Driver={IBM i Access ODBC Driver};System=127.0.0.1;Uid=".AH_DB2_USER.";Pwd=".AH_DB2_PASS.";TRANSLATE=1;";
$db_connection = odbc_connect($server, AH_DB2_USER, AH_DB2_PASS);
if (!$db_connection) {
    http_response_code(500);
    echo "Errore connessione al database.";
    exit;
}

$sql = "SELECT 
  F42522HEA.W1CONF AS CONFEZIONATORE,
  F4211.SDPSN AS NUMLISTA,
  COALESCE(F4211.SDAN8, '') || ' - ' ||COALESCE(F4211.SDSHAN, '') || ' - ' ||COALESCE(F0111.WWMLNM, ' ') AS CODSPED,
  F00365.ONDATE AS DATASPED,
  REPLACE(VARCHAR_FORMAT(SUM(F4211.SDITWT/100), '999990.99'), '.', ',') AS PESO
FROM JRGDTA94C.F4211 AS F4211
LEFT JOIN JRGDTA94C.F00365 AS F00365 ON F00365.ONDTEJ = F4211.SDPDDJ
LEFT JOIN JRGDTA94C.F42522HEA AS F42522HEA ON F42522HEA.W1PSN = F4211.SDPSN
LEFT JOIN JRGDTA94C.F0111 AS F0111 ON F4211.SDSHAN = F0111.WWAN8 AND F0111.WWIDLN = 0

WHERE 
  F4211.SDDELN = 0
  AND F4211.SDNXTR = '550'
  AND TRIM(F42522HEA.W1CONF) = ?
  AND F00365.ONDATE = ?
GROUP BY 
  F42522HEA.W1CONF, F4211.SDPSN, F4211.SDAN8, F4211.SDSHAN, F0111.WWMLNM , F00365.ONDATE
ORDER BY F4211.SDPSN
";

$stmt = odbc_prepare($db_connection, $sql);
if (!odbc_execute($stmt, [$utente, $data])) {
    echo "Errore esecuzione query.";
    exit;
}

echo "<table class='table table-sm table-bordered'>";
echo "<thead><tr><th></th><th>Codice Spedizione</th><th>Data</th><th>Lista</th><th>Peso</th></tr></thead><tbody>";
while ($row = odbc_fetch_array($stmt)) {
    $idDettaglio = 'dettaglio_' . htmlspecialchars($row['NUMLISTA']);
    $w1psn = htmlspecialchars($row['NUMLISTA']);
 $pattern = "\\\\cloudshare\\GED\\gedrgp_balls\\ListePrelievo\\*{$w1psn}.PDF";
    $fileMatches = glob($pattern);
    $pathPdf = (!empty($fileMatches)) 
        ? "http://cloudshare/ged/gedrgp_balls/ListePrelievo/" . basename($fileMatches[0])
        : "#";

    echo "<tr>
        <td>
          <button class='btn btn-sm btn-primary me-1' data-bs-toggle='collapse' data-bs-target='#$idDettaglio' aria-expanded='false' aria-controls='$idDettaglio'>+</button>
          <a class='btn btn-sm btn-outline-secondary' href='$pathPdf' target='_blank' title='Apri PDF'>PDF $pathPdf</a>
        </td>
        <td>" . htmlspecialchars($row['CODSPED']) . "</td>
        <td>" . htmlspecialchars($row['DATASPED']) . "</td>
        <td>" . htmlspecialchars($row['NUMLISTA']) . "</td>
        <td>" . htmlspecialchars($row['PESO']) . " Kg</td>
      </tr>
      <tr class='collapse' id='$idDettaglio'>
        <td colspan='5'>
          <table class='table table-sm table-bordered mb-0'>
            <thead><tr><th>Articolo</th><th>Quantità</th><th>Peso</th></tr></thead>
            <tbody>";

    $sqlDettagli = "
      SELECT SDLITM, SUM(SDUORG)/100 AS QTA, SUM(SDITWT)/100 AS PESO
      FROM JRGDTA94C.F4211
      WHERE SDPSN = ? AND SDLNTY = 'S'
      GROUP BY SDLITM
      ORDER BY SDLITM";

    $stmtDettagli = odbc_prepare($db_connection, $sqlDettagli);
    if (odbc_execute($stmtDettagli, [$row['NUMLISTA']])) {
        while ($det = odbc_fetch_array($stmtDettagli)) {
            echo "<tr>
                <td>" . htmlspecialchars($det['SDLITM']) . "</td>
                <td>" . number_format($det['QTA'], 2, ',', '.') . "</td>
                <td>" . number_format($det['PESO'], 2, ',', '.') . " Kg</td>
            </tr>";
        }
    }

    echo "  </tbody>
          </table>
        </td>
      </tr>";
}
echo "</tbody></table>";
?>