<?php
require("/www/php80/htdocs/sped/config.inc.php");

// Parametri GET
$SDSHAN = $_GET['SDSHAN'] ?? '';
$SDPDDJ = $_GET['SDPDDJ'] ?? '';
/*
if (!$SDSHAN || !$SDPDDJ) {
    echo "Parametri mancanti. $codsped E $datagiu";
    exit;
}
*/
// Connessione ODBC
$server = "Driver={IBM i Access ODBC Driver};System=127.0.0.1;Uid=" . AH_DB2_USER . ";Pwd=" . AH_DB2_PASS . ";TRANSLATE=1;";
$user = AH_DB2_USER;
$pass = AH_DB2_PASS;
$db_connection = odbc_connect($server, $user, $pass);

if (!$db_connection) {
    echo "Errore connessione DB: " . odbc_errormsg();
    exit;
}

// Query dettagliata
$query = "SELECT 
W1AN8, 
W1SHAN,
W1PDDJ,
W1DTSO,
W1USER,
W1NOTE
FROM JRGDTA94C.FSOLLSPE0F
LEFT JOIN JRGDTA94C.F0116 ON W1SHAN = ALAN8
WHERE W1SHAN = ? AND W1PDDJ = ?
ORDER BY W1DTSO DESC
 ";

$stmt = odbc_prepare($db_connection, $query);
$success = odbc_execute($stmt, [$SDSHAN, $SDPDDJ]);

if (!$success) {
    echo "Errore esecuzione query. . . " ;
    exit;
}

echo '<div class="container mt-4">';
echo '<h3>Dettagli per Codice Spedizione: <code>' . htmlspecialchars($SDSHAN) . '</code> - Del: <code> </code></h3>';
echo '<table class="table table-bordered table-sm mt-3">';
echo '<thead class="table-dark">';
echo '<tr>';
echo '<th>Codice Cliente</th>';
echo '<th>Data Sollecito</th>';
echo '<th>Utente</th>';
echo '<th>Note</th>';
echo '<th> </th>';
echo '</tr>';
echo '</thead>';
echo '<tbody>';
while ($row = odbc_fetch_array($stmt)) {
    echo "<tr>
            <td>" . htmlspecialchars($row['W1AN8']) . "</td>
            <td>" . htmlspecialchars($row['W1DTSO']) ."</td>
            <td>" . htmlspecialchars($row['W1USER']) . "</td>
            <td>" . htmlspecialchars($row['W1NOTE']) . "</td>
          </tr>";
}
echo '</tbody>';
echo '</table>';
echo '</div>';

odbc_close($db_connection);
?>