<?php
date_default_timezone_set('Europe/Rome');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['json_response'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'ok', 'message' => 'Operazione completata']);
    exit;
}
require("/www/php80/htdocs/sped/config.inc.php");

// Parametri GET
$SDPSN = $_GET['SDPSN'] ?? '';

if (!$SDPSN) {
    echo "Parametri mancanti.";
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
$query = "SELECT W1PSN , 
          W1AN8, 
          W1USER, 
          W1TIME, 
          W1DATE, 
          W1HOUR, 
          W1MINU, 
          W1SECO, 
          W1DAY, 
          W1MONT, 
          W1YEAR, 
          W1TIMS, 
          W1LOCN,
          BDCOGN,
          BDNOME
FROM JRGDTA94C.F42522TRK
LEFT JOIN BCD_DATIV2.BDGDIP0F ON W1LOCN = BDBADG
          WHERE W1PSN = ?
          ";

$stmt = odbc_prepare($db_connection, $query);
$success = odbc_execute($stmt, [$SDPSN]);

if (!$success) {
    echo "Errore esecuzione query. . . " ;
    exit;
}

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
      .lampeggia {
        animation: lampeggia 1.5s infinite;
      }
      @keyframes lampeggia {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.3; }
      }
    </style>
  </head>
  <body>
        <div class="container mt-4">
      <h3>Dettagli per lista N°: <code>$SDPSN</code></h3>
      <table class="table table-bordered table-sm mt-3">
        <thead class="table-dark">
          <tr>
            <th>Posizione</th>
            <th>Data e Ora</th>
            <th>Utente</th>
            <th>Tempo Trascorso</th>
          </tr>
        </thead>
        <tbody>
HTML;
$prevDateTime = null;
while ($row = odbc_fetch_array($stmt)) {
    $currentDate = $row['W1DATE'];
    $currentTime = $row['W1TIME'];
    $currentDateTime = null;
    $tempoTrascorso = '';

    if ($currentDate && $currentTime) {
        $currentDateTime = DateTime::createFromFormat('Y-m-d H:i:s', "$currentDate $currentTime");
        if ($currentDateTime && $prevDateTime) {
            $tempoTrascorso = $prevDateTime->diff($currentDateTime)->format('%H:%I');
        }
    }

    // Stampa la riga precedente (se già esiste)
    if (isset($prevRow)) {
                      echo "<!-- W1DATE: $currentDate | W1TIME: $currentTime -->";

        echo "<tr>
                <td>" . htmlspecialchars($prevRow['BDNOME']) . " " . htmlspecialchars($prevRow['BDCOGN']) . "</td>
                <td>" . htmlspecialchars($prevRow['W1DATE']) . " " . htmlspecialchars($prevRow['W1TIME']) . "</td>
                <td>" . htmlspecialchars($prevRow['W1USER']) . "</td>
                <td>" . htmlspecialchars($tempoTrascorso) . "</td>
              </tr>";
    }

    $prevDateTime = $currentDateTime;
    $prevRow = $row;
}
if (isset($prevRow)) {
    // Usa il nome già calcolato nel ciclo precedente

    $tempoFinale = '';
    if ($prevDateTime) {
        $adesso = new DateTime();
        $interval = $prevDateTime->diff($adesso);
        $tempoFinale = str_pad($interval->h, 2, "0", STR_PAD_LEFT) . ":" . str_pad($interval->i, 2, "0", STR_PAD_LEFT);
    }

    echo "<tr>
            <td>" . htmlspecialchars($prevRow['BDNOME']) . " " . htmlspecialchars($prevRow['BDCOGN']) . "</td>
            <td>" . htmlspecialchars($prevRow['W1DATE']) . " " . htmlspecialchars($prevRow['W1TIME']) . "</td>
            <td>" . htmlspecialchars($prevRow['W1USER']) . "</td>
            <td class=\"lampeggia\" style=\"background-color:rgb(0, 202, 128);\">" . htmlspecialchars($tempoFinale) . "</td>
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