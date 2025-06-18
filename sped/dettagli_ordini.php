<?php
require("/www/php80/htdocs/sped/config.inc.php");

// Parametri GET
$SDSHAN = $_GET['SDSHAN'] ?? '';
$SDPDDJ = $_GET['SDPDDJ'] ?? '';

if (!$SDSHAN || !$SDPDDJ) {
    echo "Parametri mancanti. $codsped E $datagiu";
    exit;
}

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
$query = "SELECT SDPSN  , SDVR01 , SDDOCO , SDDCTO , ONDATE FROM JRGDTA94C.F4211
LEFT JOIN JRGDTA94C.F00365 ON SDURDT = ONDTEJ
          WHERE SDSHAN = ? AND SDPDDJ = ? AND SDDELN = 0 AND SDNXTR = '560'
          GROUP BY SDPSN  , SDVR01 , SDDOCO , SDDCTO , ONDATE
          ";

$stmt = odbc_prepare($db_connection, $query);
$success = odbc_execute($stmt, [$SDSHAN, $SDPDDJ]);

if (!$success) {
    echo "Errore esecuzione query. . . " ;
    exit;
}
 /*
// Output risultati
echo "<ul class='list-group'>";
while ($row = odbc_fetch_array($stmt)) {
    echo "<li class='list-group-item'>" . htmlspecialchars($row['SDPSN']) . "</li>";
}
echo "</ul>";
*/


		echo <<<HTML
<!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pause</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.3/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.3/jquery-ui.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <style>
      h1 {
        font-size: 42px;
      }
      form .form-control {
        font-size: 16px;
      }
      form .form-label {
        font-weight: 500;
      }
      #contents {
        padding: 20px;
      }
      .btn {
        font-size: 16px;
      }
      body {
        padding-bottom: 600px;
      }
      @media print {
        #printButton, form {
          display: none;
        }
        table.table tr td, table.table tr th {
          font-size: 12px !important;
          padding: 2px !important;
        }
      }
      .placeholder {
        color: rgba(0, 0, 255, 0.05);
      }
      @keyframes lampeggia {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.3; }
      }
    </style>
  </head>
  <body>
        <div class="container mt-4">
      <h3>Dettagli per Codice Spedizione: <code>{$SDSHAN}</code> - Del:   <code> </code></h3>
      <table class="table table-bordered table-sm mt-3">
        <thead class="table-dark">
          <tr>
            <th>Prebolla</th>
            <th>Ordine RGP</th>
            <th>Ordine Cliente</th>
            <th> </th>
          </tr>
        </thead>
        <tbody>
HTML;
while ($row = odbc_fetch_array($stmt)) {
    echo "<tr>
            <td>" . htmlspecialchars($row['SDPSN']) . "</td>
                        <td>" . htmlspecialchars($row['SDDOCO']) . " - " . htmlspecialchars($row['SDDCTO']) . "</td>

            <td>" . htmlspecialchars($row['SDVR01']) . "del " . htmlspecialchars($row['ONDATE']) . "</td>
                  <td><button class=\"btn btn-sm btn-primary\" onclick=\"apriPopup('" . htmlspecialchars($row['SDPSN']) . "')\">Invia</button></td>

          </tr>";
}
echo <<<HTML
        </tbody>
      </table>
    </div>
  </body>
</html>

HTML;



odbc_close($db_connection);
?>