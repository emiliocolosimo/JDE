<?php

session_start();
require_once('/www/php80/htdocs/CRUD/websmart/v13.2/include/WebSmartObject.php');
require_once('/www/php80/htdocs/CRUD/websmart/v13.2/include/xl_functions.php');
require("/www/php80/htdocs/sped/config.inc.php");
require("/www/php80/htdocs/sped/classes/AzureService.class.php");

// Verifica se l'utente è loggato e ha il badge salvato in sessione
$_SESSION['redirect_to'] = 'tracklotn.php';

if (!function_exists('xlLoadWebSmartObject')) {
	function xlLoadWebSmartObject($file, $class)
	{
		if (realpath($file) !== realpath($_SERVER["SCRIPT_FILENAME"])) {
			return;
		}
		$instance = new $class;
		$instance->runMain();
	}
}


class tracklotn extends WebSmartObject
{
    	public function runMain()
	{
		error_reporting(E_ALL);
		ini_set('display_errors', 1);
		date_default_timezone_set('Europe/Rome');

        $onlyContent = isset($_GET['onlyContent']) && $_GET['onlyContent'] === '1';
        $lottonFiltro = strtoupper(trim($_GET['lottonFiltro'] ?? ''));
        $numfatturaFiltro = strtoupper(trim($_GET['numfattura'] ?? ''));
        $numordineFiltro = strtoupper(trim($_GET['numordine'] ?? ''));

$server="Driver={IBM i Access ODBC Driver};System=127.0.0.1;Uid=".AH_DB2_USER.";Pwd=".AH_DB2_PASS.";TRANSLATE=1;"; 
$user=AH_DB2_USER; 
$pass=AH_DB2_PASS;   
$db_connection=odbc_connect($server,$user,$pass); 
if(!$db_connection) {
	$errMsg = "Errore connessione al database : ".odbc_errormsg();
	error_log($errMsg);
	exit;
}


$queryDatiDipendenti = "SELECT 
COALESCE(TRIM(BDNOME), '') AS BDNOME, 
COALESCE(TRIM(BDCOGN), '') AS BDCOGN,  
COALESCE(TRIM(BDPASS), '') AS BDPASS , 
COALESCE(TRIM(BDCOGN), '')||'.'||COALESCE(TRIM(BDNOME), '') as NOMEUTENTE, 
COALESCE(TRIM(BDAUTH), '') AS BDAUTH,
COALESCE(TRIM(BDEMAI), '') AS BDEMAI,
COALESCE(TRIM(BDCOGE), '') AS BDCOGE,
COALESCE(TRIM(BDBADG), '') AS BDBADG,
COALESCE(TRIM(BDREPA), '') AS BDREPA,
COALESCE(TRIM(BDPREL), '') AS BDPREL,
COALESCE(TRIM(BDCONF), '') AS BDCONF,
COALESCE(TRIM(BDRELI), '') AS BDRELI,
COALESCE(TRIM(BDTIMB), '') AS BDTIMB,
COALESCE(TRIM(BDBDTM), '') AS BDBDTM,
COALESCE(TRIM(BDPASS), '') AS BDPASS 
FROM BCD_DATIV2.BDGDIP0F";
$stmtDipendenti = odbc_exec($db_connection, $queryDatiDipendenti);
$credenziali = [];

while ($riga = odbc_fetch_array($stmtDipendenti)) {
    $nomeutente = strtolower($riga['NOMEUTENTE']);
    $credenziali[$nomeutente] = $riga['BDPASS'];
    $resultsDipendenti[] = $riga;

  if ($riga['BDPREL'] == 'Y') {
    $resultsprelevatori[] = $riga;
    }
  if ($riga['BDCONF'] == 'Y') {
    $resultConfezionatore[] = $riga;
    } 
}
// Campi BCD_DATIV2.BDGDIP0F
$BDNOME = 'BDNOME';       // NOME
$BDCOGN = 'BDCOGN';       // COGNOME
$BDPASS = 'BDPASS';       // PASSWORD
$BDAUTH = 'BDAUTH';       // AUTORIZZAZIONI
$BDEMAI = 'BDEMAI';       // EMAIL
$BDCOGE = 'BDCOGE';       // COD.GESTIONALE
$BDBADG = 'BDBADG';       // BADGE HEX
$BDREPA = 'BDREPA';       // REPARTO
$BDPREL = 'BDPREL';       // PRELEVATORE
$BDCONF = 'BDCONF';       // CONFEZIONATORE
$BDRELI = 'BDRELI';       // RESP. LISTE
$BDTIMB = 'BDTIMB';       // TIMBRATORE DEF
$BDBDTM = 'BDBDTM';       // BADGE HEX TEMP
$BDPASS = 'BDPASS';       // PASSWORD

        // Blocco autenticazione POST aggiornato: verifica utente e password (opzionale)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $utente = strtolower($_POST['utente'] ?? '');
            $password = $_POST['password'] ?? '';

            if (isset($credenziali[$utente])) {
                if ($credenziali[$utente] === '' || $credenziali[$utente] === $password) {
                    $_SESSION['autenticato'] = true;
                    $_SESSION['utente'] = $utente;
                    $redirect = $_SESSION['redirect_to'];
                    unset($_SESSION['redirect_to']);
                    header("Location: $redirect");
                    exit;
                } else {
                    $errore = "Password non corretta.";
                }
            } else {
                $errore = "Utente non valido.";
            }
        }

        if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit;
}
      //if ($lottonFiltro !== '') {
//                $whereConditions[] = "TRIM(IOLOTN) LIKE '%" . addslashes($lottonFiltro) . "%'";
  
//}

        // Costruzione della query anagrafica lotti con filtro su numero fattura, numero ordine e lotto, se presenti
        if ($numfatturaFiltro !== '' || $numordineFiltro !== '' ) {
            $whereConditions = [];

            if ($numfatturaFiltro !== '') {
                $whereConditions[] = "TRIM(PRVRMK) LIKE '%" . addslashes($numfatturaFiltro) . "%'";
            }

            if ($numordineFiltro !== '') {
                $whereConditions[] = "TRIM(PRDOCO) LIKE '%" . addslashes($numordineFiltro) . "%'";
            }

$queryanaglotti = "SELECT
IOLITM AS IOLITM,
COALESCE(TRIM(IOLOTN), '') AS IOLOTN ,
DECIMAL(COALESCE(SUM(LIPQOH/100), 0), 15, 2) AS QUANTITA
FROM JRGDTA94C.F4108 AS F4108 
LEFT JOIN JRGDTA94C.F41021 AS F41021 ON IOLOTN=LILOTN AND IOITM=LIITM
WHERE EXISTS (
    SELECT 1 FROM JRGDTA94C.F43121 AS F43121
    WHERE " . implode(" AND ", $whereConditions) . "
    AND F43121.PRLOTN = F4108.IOLOTN AND F43121.PRLITM = F4108.IOLITM
)
GROUP BY IOLITM, IOLOTN
ORDER BY IOLITM, IOLOTN";
        } else {
            $where = '';
            if ($lottonFiltro !== '') {
                $where = "WHERE TRIM(IOLOTN) LIKE '%" . addslashes($lottonFiltro) . "%'";
            }
            $queryanaglotti = "SELECT
IOLITM AS IOLITM,
COALESCE(TRIM(IOLOTN), '') AS IOLOTN
FROM JRGDTA94C.F4108 AS F4108
{$where}
GROUP BY IOLITM, IOLOTN
ORDER BY IOLITM, IOLOTN
LIMIT 10";
        }
        $result = odbc_exec($db_connection, $queryanaglotti);
        $rowsanaglotti = [];
        while ($rowanaglotti = odbc_fetch_array($result)) {
            $anaglotto = $rowanaglotti['IOLOTN'] ?? '---';
            $rowsanaglotti[$anaglotto][] = $rowanaglotti;
        }



        $utente = htmlspecialchars($_SESSION['utente'] ?? '');
        $dipendenteAutenticato = array_filter($resultsDipendenti, function ($d) use ($utente) {
            return strtolower($d['NOMEUTENTE']) === $utente;
        });
$dipendenteAutenticato = reset($dipendenteAutenticato) ?: [];
$nomecompleto = ($dipendenteAutenticato['BDNOME'] ?? ' ') . ' ' . ($dipendenteAutenticato['BDCOGN'] ?? ' ');

        echo <<<HTML
<!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>tracklotn</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.3/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.3/jquery-ui.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

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
.table-title {
  background-color: #0037FF !important;
  color: white !important;
}
.table-title th {
  background-color: #0037FF !important;
  color: white !important;
}

      .placeholder {
        color: rgba(0, 0, 255, 0.05);
      }
      @keyframes lampeggia {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.3; }
      }
      .modal-custom-top {
        margin-top: 100px; /* Spazio sufficiente per lasciare visibile il logo */
      }
    </style>
  </head>
HTML;
  if (!$onlyContent) {

echo <<<HTML
  <body>
    <div id="outer-content">
      <div id="page-content" class="container-fluid">
<div class="d-flex justify-content-center align-items-center">
          <img src="img/logo.jpg" alt="Logo" style="max-height: 60px;">
        </div>
      </div>

    <div class="clearfix"></div>
    <div id="contents">
HTML;
         
       // Blocco autenticazione/login
     
if (isset($_SESSION['autenticato']) && $_SESSION['autenticato']) {
    echo <<<HTML
  <div class="mb-3">
    <span class="fs-5">Buongiorno, <strong>$nomecompleto</strong></span>
    <a href="?logout=1" class="btn btn-sm btn-outline-secondary ms-3">Logout</a>
  <a id="msgEsito" class="text-danger fw-bold d-none ms-3"></a>
    </div>

  HTML;
} 
else 
{
  echo <<<HTML
  <form method="post" class="mb-3">
    <div class="row g-2 align-items-center mb-2">
      <div class="col-auto">
       <span class="text-danger"></span>
        <input type="text" name="utente" class="form-control" placeholder="Utente" required>
      </div>
      <div class="col-auto">
        <input type="password" name="password" class="form-control" placeholder="Password" autocomplete="current-password">
      </div>
      <div class="col-auto">
        <button type="submit" class="btn btn-primary">Login</button>
      </div>
    </div>
  </form>
  HTML;
}
  } 
echo <<<HTML
      <form method="get" class="mb-3">

        <div class="input-group mb-2">
          <input type="text" class="form-control" name="lottonFiltro" placeholder="Lotto N" value="{$lottonFiltro}">
          <input type="text" class="form-control" name="numfattura" placeholder="Numero Fattura" value="{$numfatturaFiltro}">
          <input type="text" class="form-control" name="numordine" placeholder="Ordine N" value="{$numordineFiltro}">

        <button type="submit" class="btn btn-outline-primary">Filtra</button>
        <a href="tracklotn.php" class="btn btn-outline-secondary ms-2">Reset</a>
        </div>
      </form>
<table class="table table-striped table-bordered table-hover">
<thead class="table-title text-white">
              <tr>
                <th>LOTTO</th>
                <th>Azione</th>
              </tr>
          </thead>
          <tbody>
HTML;
 
        foreach ($rowsanaglotti as $lotto => $dettagliLotto) {
            $selected = (isset($_GET['lotto_dettaglio']) && $_GET['lotto_dettaglio'] === $lotto) ? 'table-warning' : 'table-primary';
            echo "<tr class=\"$selected fw-bold\">
        <td>" . htmlspecialchars($lotto) . " - " . htmlspecialchars($dettagliLotto[0]['QUANTITA'] ?? '') . "</td>
        <td>";
            // Build URL with current filters plus lotto_dettaglio and anchor to collapse
            $params = [];
            if (!empty($_GET['lottonFiltro'])) {
                $params['lottonFiltro'] = $_GET['lottonFiltro'];
            }
            if (!empty($_GET['numfattura'])) {
                $params['numfattura'] = $_GET['numfattura'];
            }
            if (!empty($_GET['numordine'])) {
                $params['numordine'] = $_GET['numordine'];
            }
            $params['lotto_dettaglio'] = $lotto;
            $queryString = http_build_query($params);
            $url = "tracklotn.php?$queryString#collapse" . urlencode($lotto);
            echo "<a href=\"$url\" class=\"btn btn-sm btn-secondary ms-2\">Mostra/Nascondi Dettagli</a>";
            echo "</td>
      </tr>";

      echo "<tr class=\"collapse\" id=\"collapse{$lotto}\">
        <td colspan=\"2\">";
        
if (isset($_GET['lotto_dettaglio']) && trim($_GET['lotto_dettaglio']) === trim($lotto)) {

        $lottoDettaglio = strtoupper(trim($_GET['lotto_dettaglio']));
        $subQuery = "SELECT DISTINCT ILDCT, ILDOC FROM JRGDTA94C.F4111 WHERE ILLOTN = '" . addslashes($lottoDettaglio) . "'";
        $resultSub = odbc_exec($db_connection, $subQuery);
        $docConditions = [];
        while ($row = odbc_fetch_array($resultSub)) {
        $docConditions[] = "(ILDCT = '" . addslashes($row['ILDCT']) . "' AND ILDOC = '" . addslashes($row['ILDOC']) . "')";
        }
     
     
     $where = $docConditions ? "WHERE (" . implode(" OR ", $docConditions) . ")" : "WHERE ILLOTN = '" . addslashes($lottoDettaglio) . "'";

        // Il filtro su numero fattura è ora applicato a livello dell'anagrafica lotti, quindi non va più aggiunto qui
        $queryprimaria = "SELECT
        COALESCE(TRIM(ILLITM), '') AS ARTICOLO,
        COALESCE(TRIM(ILLOTN), '') AS LOTTO,
        COALESCE(TRIM(ILFRTO), '') AS FRTO,
        COALESCE(TRIM(ILLOTG), '') AS GRADO,
        COALESCE(TRIM(ILTREX), '') AS DESCRIZIONE,
        COALESCE(TRIM(ILDCT), '') AS TIPODOCO,
        COALESCE(TRIM(ILDOC), '') AS NUMERODOCO
        FROM JRGDTA94C.F4111
        $where
        AND ILDCT NOT IN ('IZ' , 'IB' , 'IT' , 'A0' )
        ORDER BY ILDOCO||ILDCT";

        $resultDettaglio = odbc_exec($db_connection, $queryprimaria);
        $rowsDettaglio = [];
        while ($row = odbc_fetch_array($resultDettaglio)) {
            $rowsDettaglio[] = $row;
        }
        echo '<h5>Dettagli per il lotto: ' . htmlspecialchars($lottoDettaglio) . '</h5>';
        echo '<table class="table table-striped table-bordered table-hover mb-0">';
        echo '<thead class="table-title text-white">';
        echo '<tr>
                <th>DESCRIZIONE</th>
                <th></th>
              </tr>';
        echo '</thead><tbody>';
// Raggruppa per descrizione
$descrizioneGroup = [];
foreach ($rowsDettaglio as $row) {
    $descrizione = $row['DESCRIZIONE'] ?? '---';
    $descrizioneGroup[$descrizione][] = $row;
}

foreach ($descrizioneGroup as $descrizione => $righe) {
    $collapseId = 'desc' . md5($descrizione);
    $prima = $righe[0];
    echo '<tr data-bs-toggle="collapse" data-bs-target="#' . $collapseId . '" class="cursor-pointer">';
    echo '<td>' . htmlspecialchars($descrizione) . '</td>';
    echo '<td colspan="2"><button class="btn btn-sm btn-primary">+</button></td>';
    echo '</tr>';

    // Dettagli collassabili

    echo '<tr class="collapse" id="' . $collapseId . '"><td colspan="7">';
        echo '<table class="table table-striped table-bordered table-hover mb-0">';
        echo '<thead class="table-title text-white">';
        echo '<tr>
                <th>ARTICOLO</th>
                <th>LOTTO</th>
                <th>FRTO</th>
                <th>GRADO</th>
                <th>QUANTITA</th>
                <th></th>
              </tr>';
        echo '</thead><tbody>';;
    foreach ($righe as $r) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($r['ARTICOLO'] ?? '') . '</td>';
        $lottoTrim = trim($r['LOTTO'] ?? '');
        $collapseIdSub = 'subCollapse' . md5($lottoTrim . uniqid());
        echo '<td>';
        echo htmlspecialchars($lottoTrim);
        echo ' <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#' . $collapseIdSub . '" aria-expanded="false" aria-controls="' . $collapseIdSub . '">+</button>';
        echo '</td>';
        echo '<td>' . htmlspecialchars($r['FRTO'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($r['GRADO'] ?? '') . '</td>';
        echo '</tr>';

        // Riga collassabile per rieseguire tutte le query con il lotto selezionato
        echo '<tr class="collapse" id="' . $collapseIdSub . '"><td colspan="7">';
        $params = [
          'lottonFiltro' => $lottoTrim,
          'onlyContent' => '1'
        ];
        $src = 'tracklotn.php?' . http_build_query($params);
        echo '<iframe src="' . $src . '" class="w-100 border-0" style="height:600px;"></iframe>';
        echo '</td></tr>';
    }
    echo '</tbody></table>';
    echo '</td></tr>';
}
        echo '</tbody></table>';
      }
      echo "</td></tr>";
        }

echo "</tbody></table>";

echo <<<HTML
  <div class="modal fade" id="dettagliModal" tabindex="-1" aria-labelledby="dettagliModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-custom-top">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="dettagliModalLabel">Dettagli tracklotn</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
        </div>
        <div class="modal-body" id="contenutoModal">
          Caricamento...
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="dettagliotracklotnModal" tabindex="-1" aria-labelledby="dettagliotracklotnLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-custom-top">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="dettagliotracklotnLabel">Dettaglio tracklotn</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
        </div>
        <div class="modal-body" id="contenutotracklotn">
          Caricamento...
        </div>
      </div>
    </div>
  </div>

        <div style="padding-bottom: 150px;"></div>
      </div>
    </div>
    <!-- Modale richiesta ubicazione -->
    <div class="modal fade" id="ubicazioneModal" tabindex="-1" aria-labelledby="ubicazioneModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="ubicazioneModalLabel">Inserisci Ubicazione</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
          </div>
          <div class="modal-body">
            <input type="text" class="form-control" id="ubicazioneInput" placeholder="Inserisci ubicazione...">
            <div class="modal-footer">
              <button type="button" class="btn btn-primary" onclick="confermaScrittura()">Conferma</button>
            </div>
          </div> 
        </div>
      </div>
    </div>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const lottoDettaglio = urlParams.get('lotto_dettaglio');
    if (lottoDettaglio) {
      const collapseElement = document.getElementById('collapse' + lottoDettaglio);
      if (collapseElement) {
        const bsCollapse = new bootstrap.Collapse(collapseElement, { toggle: true });
      }
    }
  });
</script>
  </body>
</html>
HTML;
        }


     }
		header('Content-Type: text/html; charset=iso-8859-1');

xlLoadWebSmartObject(__FILE__, 'tracklotn');
?> 