<?php
require("/www/php80/htdocs/sped/config.inc.php");

// Parametri GET
$SDSHAN = $_GET['SDSHAN'] ?? '';
$SDAN8 = $_GET['SDAN8'] ?? '';

if (!$SDSHAN || !$SDAN8) {
    echo "Parametri mancanti. $SDAN8 E $SDSHAN";
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
$query = "SELECT WWAN8 , WWMLNM , WWTYC FROM JRGDTA94C.F0111
          WHERE WWAN8 IN ( ? , ? ) and WWMLNM <> ' '
          ORDER BY WWIDLN
          ";

$stmt_mail = odbc_prepare($db_connection, $query);
$success = odbc_execute($stmt_mail, [$SDSHAN, $SDAN8]);

if (!$success) {
    echo "Errore esecuzione query. . . " ;
    exit;
}

// Query dettagliata
$querynote = "SELECT A3AN8 , A3DS80 FROM JRGDTA94C.f01093
          WHERE A3AN8 IN ( ? , ? ) and A3TYDT = 'NP' 
          ORDER BY A3AN8 , A3LIN
          ";

$stmt_note = odbc_prepare($db_connection, $querynote);
$success = odbc_execute($stmt_note, [$SDSHAN, $SDAN8]);

if (!$success) {
    echo "Errore esecuzione query. . . " ;
    exit;
}

$filtroSoloEmail = ($_GET['soloemail'] ?? '1') === '1';

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
  <div class="form-check mb-3">
    <input class="form-check-input" type="checkbox" id="filtroEmail" onclick="filtraEmail()" checked>
    <label class="form-check-label" for="filtroEmail">
      Mostra solo i record con email
    </label>
  </div>
      <h3>Mail per Codice {$SDSHAN} e codice {$SDAN8}</h3>
      <table class="table table-bordered table-sm mt-3">
        <thead class="table-dark">
          <tr>
            <th>Codice</th>
            <th>Mail</th>
            <th>Tipo</th>
          </tr>
        </thead>
HTML;
echo '<tbody id="emailTableBody">';

while ($row = odbc_fetch_array($stmt_mail)) {
    if ($filtroSoloEmail = ($_GET['soloemail'] ?? '1') === '1') {
        $soloEmail = true;
    } else {
        $soloEmail = false;
    }
    $emailRaw = str_replace('§', '@', iconv('ISO-8859-1', 'UTF-8//IGNORE', $row['WWMLNM']));
    $hasEmail = strpos($emailRaw, '@') !== false;
    $ismail = $hasEmail ? 1 : 0;
    $email = $hasEmail
        ? preg_replace('/([^\s<>()]+@[^\s<>()]+)/', '<a href="mailto:$1" class="text-decoration-none fw-bold">$1</a>', htmlspecialchars($emailRaw))
        : htmlspecialchars($emailRaw);

    if (!$soloEmail || $hasEmail) {
        echo "<tr data-has-email=\"" . $ismail . "\">
                <td>" . htmlspecialchars($row['WWAN8']) . "</td>
                <td>$email</td>
                <td>" . htmlspecialchars($row['WWTYC']) . "</td>
              </tr>";
    }
}
echo <<<HTML
        </tbody>
      </table>

      <h3 class="mt-5">Note per Codice: <code>{$SDSHAN} {$SDAN8}</code></h3>
      <table class="table table-bordered table-sm mt-3">
        <thead class="table-dark">
          <tr>
            <th>Codice</th>
            <th>Nota</th>
          </tr>
        </thead>
        <tbody>
HTML;
while ($note = odbc_fetch_array($stmt_note)) {
    $nota = str_replace('§', '@', iconv('ISO-8859-1', 'UTF-8//IGNORE', $note['A3DS80']));
    $nota = htmlspecialchars($nota);
    $hasEmailInNote = strpos($nota, '@') !== false;
    if ($filtroSoloEmail && !$hasEmailInNote) {
        continue;
    }
    if (strpos($nota, '@') !== false) {
        $nota = preg_replace('/([^\s<>()]+@[^\s<>()]+)/', '<a href="mailto:$1" class=\"text-decoration-none fw-bold\">$1</a>', $nota);
    }
    echo "<tr>
            <td>" . htmlspecialchars($note['A3AN8']) . "</td>
            <td>$nota</td>
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
