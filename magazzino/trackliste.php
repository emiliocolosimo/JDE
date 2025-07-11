<?php

session_start();
// Leggi dalla sessione o dal GET
if (isset($_GET['mostraAssegnati'])) {
    $_SESSION['mostraAssegnati'] = $_GET['mostraAssegnati'] === '1' ? '1' : '0';
}
if (isset($_GET['invioRapido'])) {
    $_SESSION['invioRapido'] = $_GET['invioRapido'] === '1' ? '1' : '0';
}
if (isset($_GET['SoloMaiLette'])) {
    $_SESSION['SoloMaiLette'] = $_GET['SoloMaiLette'] === '1' ? '1' : '0';
}
$SoloMaiLette = $_SESSION['SoloMaiLette'] ?? '0';
$mostraAssegnati = $_SESSION['mostraAssegnati'] ?? '0';
$invioRapido = $_SESSION['invioRapido'] ?? '0';

header('Content-Type: text/html; charset=iso-8859-1');

// Verifica se l'utente è loggato
if (!isset($_SESSION['nomeutente'])) {
    header('Location: login.php');
    exit;
}

// Gestione logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit;
}

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
$nomecompleto = trim($bdnome . '.' . $bdcogn);
$nomecompletoinfo = [[
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
require_once 'session_vars.php';
require_once('/www/php80/htdocs/CRUD/websmart/v13.2/include/WebSmartObject.php');
require_once('/www/php80/htdocs/CRUD/websmart/v13.2/include/xl_functions.php');
require("/www/php80/htdocs/sped/config.inc.php");
require("/www/php80/htdocs/sped/classes/AzureService.class.php");

        error_reporting(E_ALL);
		ini_set('display_errors', 1);
		date_default_timezone_set('Europe/Rome');

$server="Driver={IBM i Access ODBC Driver};System=127.0.0.1;Uid=".AH_DB2_USER.";Pwd=".AH_DB2_PASS.";TRANSLATE=1;";
$user=AH_DB2_USER;
$pass=AH_DB2_PASS;
$db_connection=odbc_connect($server,$user,$pass);
if(!$db_connection) {
	$errMsg = "Errore connessione al database : ".odbc_errormsg();
	error_log($errMsg);
	exit;
}
$resultincarico = [];
$sqlincarico = "SELECT TRIM(BDNOME) AS BDNOME, TRIM(BDCOGN) AS BDCOGN, TRIM(BDNICK) AS BDNICK, TRIM(BDPOSI) AS BDPOSI, TRIM(BDBADG) AS BDBADG
                      FROM BCD_DATIV2.BDGDIP0F as BDGDIP0F
                                            ORDER BY BDPOSI";
$stmtincarico = odbc_exec($db_connection, $sqlincarico);
while ($r = odbc_fetch_array($stmtincarico)) {
    // $resultincarico[] = $r;
    // Mappa per numero lista: qui serve conoscere il collegamento tra lista e incaricato
    // Ma non abbiamo la lista qui, quindi aggiorneremo dopo (vedi sotto)
    // Per ora, costruiamo una mappa badge => nome completo
    $resultincarico[$r['BDBADG']] = trim($r['BDNOME'] . '.' . $r['BDCOGN']);
}
// Carica elenco confezionatori
$resultConfezionatore = [];
$sqlConfezionatori = "SELECT TRIM(BDNOME) AS BDNOME, TRIM(BDCOGN) AS BDCOGN, TRIM(BDNICK) AS BDNICK, TRIM(BDPOSI) AS BDPOSI, TRIM(BDBADG) AS BDBADG
                      FROM BCD_DATIV2.BDGDIP0F as BDGDIP0F
                      WHERE BDCONF = 'Y'
                      ORDER BY BDPOSI";
$stmtConf = odbc_exec($db_connection, $sqlConfezionatori);
while ($r = odbc_fetch_array($stmtConf)) {
    $resultConfezionatore[] = $r;
}
		$queryprimaria = "SELECT
F4211.SDAN8 AS CODCLIENTE,
COALESCE(F4211.SDAN8, '') || ' - ' ||COALESCE(F4211.SDSHAN, '') || ' - ' || COALESCE(F0111.WWMLNM, '') || ' - ' || 
COALESCE(F0116.ALCTY1, '') || ' - ' || COALESCE(F0116.ALADDS, '') || ' - ' || COALESCE(F0116.ALCTR, '') AS CODSPED,
COALESCE(F0101.ABALPH, '') AS CODSPEDDESCR,
F00365.ONDATE AS DATASPED,
F4211.SDPSN AS NUMLISTA,
F4211.SDPDDJ AS DATAJDE,
REPLACE(VARCHAR_FORMAT(SUM(F4211.SDITWT/100), '999990.99'), '.', ',') AS PESO,
COALESCE(F42522HEA.W1CONF, '') AS CONFEZIONATORE
FROM JRGDTA94C.F4211 AS F4211
LEFT JOIN JRGDTA94C.F0101 AS F0101 ON F4211.SDAN8 = F0101.ABAN8
LEFT JOIN JRGDTA94C.F0111 AS F0111 ON F4211.SDSHAN = F0111.WWAN8 AND F0111.WWIDLN = 0
LEFT JOIN JRGDTA94C.F0116 AS F0116 ON F4211.SDAN8 = F0116.ALAN8
LEFT JOIN JRGDTA94C.F42522HEA AS F42522HEA ON F42522HEA.W1PSN = F4211.SDPSN
LEFT JOIN JRGDTA94C.F00365 AS F00365 ON F00365.ONDTEJ = F4211.SDPDDJ
WHERE F4211.SDDELN = 0 AND F4211.SDNXTR < '570' AND F4211.SDPSN <> 0
GROUP BY 
  F4211.SDAN8, F4211.SDSHAN, F00365.ONDATE, F4211.SDPDDJ, 
  F0111.WWMLNM, F0101.ABALPH , F0116.ALCTY1, F0116.ALADDS, F0116.ALCTR, F4211.SDPSN , F42522HEA.W1CONF
  ORDER BY F4211.SDPDDJ , F4211.SDSHAN, F4211.SDPSN
";
$result = odbc_exec($db_connection, $queryprimaria);
        $rows = [];
        $codiceSpedizioneFiltro = trim($_GET['codspedizione'] ?? '');
        $dataSpedizioneFiltro = trim($_GET['dataspedizione'] ?? '');
        $listanumeroFiltro = trim($_GET['listanumFiltro'] ?? '');
        $filtroConfezionatore = trim($_GET['filtroConfezionatore'] ?? '');
        $filtroInCarico = trim($_GET['filtroInCarico'] ?? '');
        // Nuovo filtro: mostra solo liste mai lette
        $SoloMaiLette = isset($_GET['SoloMaiLette']) && $_GET['SoloMaiLette'] ? true : false;

        if ($dataSpedizioneFiltro) {
            $parts = explode('-', $dataSpedizioneFiltro);
            if (count($parts) === 3) {
                $dataSpedizioneFiltro = $parts[2] . '/' . $parts[1] . '/' . substr($parts[0], -2);
            }
        }
        // Mostra il contenuto di $row per verificare la presenza di BDNOME e BDCOGN
        // Per ogni lista, recupera anche il nome incaricato da badge (IN_CARICO_A)
        $numlista2incarico = [];
        while ($row = odbc_fetch_array($result)) {
            if ($codiceSpedizioneFiltro !== '' && stripos($row['CODSPED'], $codiceSpedizioneFiltro) === false) {
                continue;
            }
            if ($dataSpedizioneFiltro && $row['DATASPED'] !== $dataSpedizioneFiltro) {
                continue;
            }
            if ($listanumeroFiltro !== '' && stripos($row['NUMLISTA'], $listanumeroFiltro) === false) {
                continue;
            }
            // Recupera l'ubicazione più recente per questa lista
            $sqlUbicazione = "SELECT W1LOCN FROM JRGDTA94C.F42522TRK WHERE W1PSN = ? ORDER BY W1YEAR DESC, W1MONT DESC, W1DAY DESC, W1TIMS DESC FETCH FIRST 1 ROWS ONLY";
            $stmtUbicazione = odbc_prepare($db_connection, $sqlUbicazione);
            if (odbc_execute($stmtUbicazione, [$row['NUMLISTA']])) {
                $locn = odbc_fetch_array($stmtUbicazione);
                $row['IN_CARICO_A'] = $locn['W1LOCN'] ?? '';
            } else {
                $row['IN_CARICO_A'] = '';
            }
            // Mappa il nome incaricato per questa lista
            $badgeIncarico = trim($row['IN_CARICO_A']);
            $numlista2incarico[$row['NUMLISTA']] = $resultincarico[$badgeIncarico] ?? '';
            $rows[] = $row;
        }
        // Sostituisci $resultincarico: ora è una mappa lista => nome incaricato
        $resultincarico = $numlista2incarico;

        // Applica filtro "solo mai lette" se richiesto
        if ($SoloMaiLette) {
            $rows = array_filter($rows, function($r) use ($resultincarico) {
                // Considera come "mai lette" le liste che non hanno alcun incaricato (vuoto)
                return empty(trim($resultincarico[$r['NUMLISTA']] ?? ''));
            });
        }

        // Sanitize for HTML output
        $codiceSpedizioneFiltro = htmlspecialchars($codiceSpedizioneFiltro, ENT_QUOTES);
        $listanumeroFiltro = htmlspecialchars($listanumeroFiltro, ENT_QUOTES);
        $filtroConfezionatore = htmlspecialchars($filtroConfezionatore, ENT_QUOTES);
        $filtroInCarico = htmlspecialchars($filtroInCarico, ENT_QUOTES);

        // Imposta il checkbox "assegnami le liste" attivo di default per utenti Gruppo
        if ($bdauth === 'GRUPPO') {
            $_GET['invioRapido'] = '1';
            $_GET['SoloMaiLette'] = '1';
        }
        echo <<<HTML
<!DOCTYPE html>
<html>
  
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>trackliste</title>

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
      /* Effetto blink più evidente */
      @keyframes blink-animation {
        0%   { background-color: #ffc107; color: black; }
        50%  { background-color: #28a745; color: white; }
        100% { background-color: #ffc107; color: black; }
      }
      .modal-custom-top {
        margin-top: 100px; /* Spazio sufficiente per lasciare visibile il logo */
      }
    </style>
  </head>
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
    echo <<<HTML
  <div class="mb-3 position-relative">
    <span class="fs-5">Buongiorno, <strong>$bdcogn $bdnome </strong></span>
    <a href="?logout=1" class="btn btn-sm btn-outline-secondary ms-3">Logout</a>
    <span id="msgAssegnazione" class="ms-3 text-success fw-bold"></span>
    <a id="msgEsito" class="text-danger fw-bold d-none ms-3"></a>
    <div id="notificaAssegnazione" class="alert alert-success d-none position-absolute end-0 top-0 mt-2 me-2" role="alert" style="z-index: 9999;">
    </div>
  </div>

  HTML;
echo <<<HTML
      <form method="get" class="mb-3">
        <div class="input-group mb-2 align-items-center">
          <input type="text" class="form-control" name="codspedizione" placeholder="Codice Spedizione" value="{$codiceSpedizioneFiltro}">
          <input type="text" class="form-control" name="listanumFiltro" placeholder="Lista N" value="{$listanumeroFiltro}">
          <input type="date" class="form-control" name="dataspedizione" value="{$dataSpedizioneFiltro}">
          <input type="text" class="form-control" name="filtroConfezionatore" placeholder="Confezionatore" value="{$filtroConfezionatore}">
          <input type="text" class="form-control" name="filtroInCarico" placeholder="In carico a" value="{$filtroInCarico}">
        </div>
        <div class="form-check form-check-inline">
          <input class="form-check-input" type="checkbox" id="SoloMaiLette" name="SoloMaiLette" value="1" <?= $SoloMaiLette ? 'checked' : '' ?>
          <label class="form-check-label" for="SoloMaiLette">Solo mai lette</label>
        </div>
        <div class="form-check form-check-inline ms-3">
          <input class="form-check-input" type="checkbox" id="invioRapido" name="invioRapido" value="1" <?= $invioRapido == '1' ? 'checked' : '' ?>
          <label class="form-check-label" for="invioRapido">Assegnami le liste lette</label>
        </div>
        <div class="form-check form-check-inline ms-3">
          <input type="checkbox" id="chkMostraAssegnati" name="mostraAssegnati" value="1" <?= $mostraAssegnati === '1' ? 'checked' : '' ?>
          <label class="form-check-label" for="chkMostraAssegnati">Mostra tutte le liste</label>
        </div>
        <button type="submit" class="btn btn-outline-primary">Applica</button>
        <a href="trackliste.php" class="btn btn-outline-secondary ms-2">Reset</a>
      </form>
      <script>
        document.addEventListener("DOMContentLoaded", () => {
          const checkbox = document.getElementById("chkMostraAssegnati");

          // Ripristina stato da localStorage
          if (localStorage.getItem("mostraAssegnati") === "1") {
            checkbox.checked = true;
          }

          // Salva stato e aggiorna URL senza bottone Applica
          checkbox.addEventListener("change", () => {
            localStorage.setItem("mostraAssegnati", checkbox.checked ? "1" : "0");

            const url = new URL(window.location.href);
            if (checkbox.checked) {
              url.searchParams.set("mostraAssegnati", "1");
            } else {
              url.searchParams.delete("mostraAssegnati");
            }
            window.location.href = url.toString();
          });
        });
      </script>
<table class="table table-striped table-bordered table-hover">
<thead class="table-title text-white">
              <tr>
              <th>Cliente</th>
              <th>Data Spedizione</th>
              <th>Numero di lista</th>
        
              <th>Confezionatore</th>
              <th>In carico a</th>
              <th>Azioni</th>
              <th>Peso</th>

            </tr>
          </thead>
          <tbody>
HTML;

        // --- FILTRO: mostra solo le liste del confezionatore loggato se necessario ---
        // Se l'utente è un confezionatore (BDCONF = 'Y'), mostra solo le sue liste assegnate o in carico
        if (strtoupper(trim($bdconf)) === 'Y' && empty($invioRapido)) {
            if (empty($_GET['mostraAssegnati'])) {
                // Mostra solo le liste assegnate o in carico all'utente
                $rows = array_filter($rows, function($r) use ($nomecompleto, $resultincarico) {
                    return trim($r['CONFEZIONATORE'] ?? '') === $nomecompleto
                        || trim($resultincarico[$r['NUMLISTA']] ?? '') === $nomecompleto;
                });
            } else {
                // Mostra tutte le liste (comprese quelle assegnate ad altri)
            }
        }

        // --- FILTRI AGGIUNTIVI PER CONFEZIONATORE E IN CARICO A ---
        $filtroConfezionatore = trim($_GET['filtroConfezionatore'] ?? '');
        $filtroInCarico = trim($_GET['filtroInCarico'] ?? '');

        if ($filtroConfezionatore !== '') {
            $rows = array_filter($rows, function($r) use ($filtroConfezionatore) {
                return stripos($r['CONFEZIONATORE'] ?? '', $filtroConfezionatore) !== false;
            });
        }

        if ($filtroInCarico !== '') {
            $rows = array_filter($rows, function($r) use ($filtroInCarico, $resultincarico) {
                $nomeCompletoFiltro = trim($resultincarico[$r['NUMLISTA']] ?? '');
                return stripos($nomeCompletoFiltro, $filtroInCarico) !== false;
            });
        }

        foreach ($rows as $row) {
            if ($nomecompleto === 'Y' && empty($invioRapido)) {
                $bdbadg = strtoupper(trim($bdbadg));
                if (trim($bdconf) !== $nomecompleto && strtoupper(trim($row['IN_CARICO_A'] ?? '')) !== $bdbadg) {
                    continue;
                }
            }
            echo '<tr data-nomeconf="' . htmlspecialchars($row['NOME_CONFEZIONATORE'] ?? '') . '" data-bdreli="' . htmlspecialchars($bdreli ?? '') . '" data-badgeutente="' . htmlspecialchars($bdbadg ?? '') . '" data-confezionatore="' . htmlspecialchars($row['CONFEZIONATORE'] ?? '') . '">';
            echo " 
      <td>" . htmlspecialchars($row['CODSPED'] ?? '') . "</td>    
      <td>" . htmlspecialchars($row['DATASPED'] ?? '') . "</td>
      <td>" . htmlspecialchars($row['NUMLISTA'] ?? '') .  "</td> "
;

      // --- BLOCCO CONFEZIONATORE ---
      echo "<td>";
      if (!empty(trim($bdconf))) {
          if (count($resultConfezionatore ?? []) > 0) {
              echo "
                    <select class='form-select form-select-sm select2' onchange=\"aggiornaConfezionatore(this, '" . htmlspecialchars($row['NUMLISTA']) . "')\">
                      <option value=''></option>";
              foreach ($resultConfezionatore as $conf) {
                  $nomecompleto = trim($conf['BDNOME'] . '.' . $conf['BDCOGN']);
                  $optionValue = $nomecompleto; // mantiene il valore per coerenza con salvataggi
                  $selected = ($optionValue === trim($row['CONFEZIONATORE'] ?? '')) ? 'selected' : '';
                  // Mostra solo il NICK come testo visibile
                  $nick = trim($conf['BDNICK'] ?? '');
                  echo "<option value=\"" . htmlspecialchars($optionValue) . "\" $selected>" . htmlspecialchars($nick) . "</option>";
              }
              echo "</select>";
          } else {
              // Mostra solo il NICK come testo visibile
              $nick = '';
              foreach ($resultConfezionatore ?? [] as $conf) {
                  $nomecompleto = trim($conf['BDNOME'] . '.' . $conf['BDCOGN']);
                  if ($nomecompleto === trim(trim($bdconf))) {
                      $nick = trim($conf['BDNICK'] ?? '');
                      break;
                  }
              }
              echo htmlspecialchars($nick ?: trim(trim($bdconf)));
          }
      }
      echo "</td>";
      // --- FINE BLOCCO CONFEZIONATORE ---
      // Mostra il nome incaricato usando la mappa $resultincarico
      echo "<td>" . htmlspecialchars(trim($resultincarico[$row['NUMLISTA']] ?? '')) . "</td>
      <td>";


echo "    
<button class='btn btn-sm btn-info' title=\"Trackliste\" onclick=\"apriTracklistePopup('" . htmlspecialchars($row['NUMLISTA']) .  "')\">
    <i class='bi bi-list-ul'></i>
</button>
<button class='btn btn-sm btn-success' title=\"Cambia ubicazione\" onclick=\"scriviTracklist('" . htmlspecialchars($row['NUMLISTA']) . "', '" . htmlspecialchars($row['CODCLIENTE']) . "', '" . htmlspecialchars($nomecompleto) . "')\">
    <i class='bi bi-box-arrow-up-right'></i>
</button>";
if (empty(trim($row['CONFEZIONATORE']))) {
    echo "
<button class='btn btn-sm btn-warning' title=\"Assegna\" onclick=\"apriHEAPopup('" . htmlspecialchars($row['NUMLISTA']) . "')\">
    <i class='bi bi-file-earmark-plus'></i>
</button>
";
}
      echo "
      </td>
            <td>" . htmlspecialchars($row['PESO'] ?? '') .  "</td> 

    </tr>";

        }

		echo <<<HTML
          </tbody>
        </table>

  <div class="modal fade" id="dettagliModal" tabindex="-1" aria-labelledby="dettagliModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-custom-top">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="dettagliModalLabel">Dettagli trackliste</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
        </div>
        <div class="modal-body" id="contenutoModal">
          Caricamento...
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="dettagliotracklisteModal" tabindex="-1" aria-labelledby="dettagliotracklisteLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-custom-top">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="dettagliotracklisteLabel">Dettaglio trackliste</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
        </div>
        <div class="modal-body" id="contenutotrackliste">
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
  // Inserimento automatico del badge anche senza focus
  let badgeBuffer = "";

  document.addEventListener("keydown", function(event) {
    const isNumberOrLetter = /^[a-zA-Z0-9]$/.test(event.key);
    if (!isNumberOrLetter) return;

    badgeBuffer += event.key.toUpperCase();

    const badgeInput = document.getElementById("badge");
    if (badgeInput) {
      badgeInput.value = badgeBuffer;
    }

    if (badgeBuffer.length === 16) {
      const form = badgeInput?.form;
      if (form) form.submit();
      badgeBuffer = "";
    }

    if (badgeBuffer.length > 16) {
      badgeBuffer = "";
      if (badgeInput) badgeInput.value = "";
    }
  });
  </script>
  <script>
document.addEventListener("DOMContentLoaded", () => {
  // Recupera i valori salvati in localStorage
  const codspedizione = localStorage.getItem('filtro_codspedizione');
  const dataspedizione = localStorage.getItem('filtro_dataspedizione');
  const listanumFiltro = localStorage.getItem('filtro_listanumFiltro');
  const SoloMaiLette = localStorage.getItem('filtro_SoloMaiLette');
  const invioRapido = localStorage.getItem('filtro_invioRapido');

  //if (codspedizione) document.querySelector('input[name="codspedizione"]').value = codspedizione;
  //if (dataspedizione) document.querySelector('input[name="dataspedizione"]').value = dataspedizione;
// if (listanumFiltro) document.querySelector('input[name="listanumFiltro"]').value = ' ' + listanumFilt;
  if (SoloMaiLette === '1') document.querySelector('input[name="SoloMaiLette"]').checked = true;
  if (invioRapido === '1') document.querySelector('input[name="invioRapido"]').checked = true;

  // Salva i valori al submit del form
  const form = document.querySelector('form[method="get"]');
  if (form) {
    form.addEventListener('submit', () => {
      localStorage.setItem('filtro_codspedizione', document.querySelector('input[name="codspedizione"]').value);
      localStorage.setItem('filtro_dataspedizione', document.querySelector('input[name="dataspedizione"]').value);
      localStorage.setItem('filtro_listanumFiltro', document.querySelector('input[name="listanumFiltro"]').value);
      localStorage.setItem('filtro_SoloMaiLette', document.querySelector('input[name="SoloMaiLette"]').checked ? '1' : '0');
      localStorage.setItem('filtro_invioRapido', document.querySelector('input[name="invioRapido"]').checked ? '1' : '0');
    });
  }
});
</script>
    <!-- Contenitore per modali dinamiche -->
    <div id="modalContainer"></div>
  </body>
</html>
HTML;

        // Inserisci qui il blocco JSON e le funzioni JS che fanno riferimento a esso
        ?>
<?php if (isset($resultsprelevatori) && is_array($resultsprelevatori)): ?>
  <script id="prelevatoriData" type="application/json">
    <?php echo json_encode($resultsprelevatori, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>
  </script>
<?php else: ?>
  <script id="prelevatoriData" type="application/json">[]</script>
<?php endif; ?>

<?php if (isset($resultConfezionatore) && is_array($resultConfezionatore)): ?>
  <script id="confezionatoriData" type="application/json">
    <?php echo json_encode($resultConfezionatore ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>
  </script>
<?php else: ?>
  <script id="confezionatoriData" type="application/json">[]</script>
<?php endif; ?>

<script>

// ======= Inizizo funzioni HEA =======
// Unifica i dipendenti validi come array di badge (BDBADG) maiuscoli e puliti
const dipendentiValidi = [...new Set([
  ...JSON.parse(document.getElementById("prelevatoriData").textContent || "[]"),
  ...JSON.parse(document.getElementById("confezionatoriData").textContent || "[]")
])].map(d => d.BDBADG?.toUpperCase()?.trim()).filter(Boolean);
// Nuova funzione: comportamento dinamico in base a BDRELI.
function apriHEAPopup(W1PSN) {
  // Trova la riga corrispondente nella tabella
  const riga = Array.from(document.querySelectorAll('tbody tr')).find(tr => {
    const lista = tr.children[2]?.textContent?.trim();
    return lista === W1PSN;
  });

  // Se la lista non esiste, mostra messaggio e ritorna
  if (!riga) {
    alert("Lista inesistente");
    return;
  }

  // Controllo confezionatore solo per responsabile liste
  const confezionatore = <?php echo json_encode($bdconf === 'Y'); ?>;
  const utenteÈResponsabile = <?php echo json_encode($bdreli === 'Y'); ?>;

  if (utenteÈResponsabile && confezionatore) {
    const msgEl = document.getElementById("msgEsito");
    if (msgEl) {
      msgEl.textContent = "Confezionatore assegnato in precedenza.";
      msgEl.classList.remove("d-none");
    }
    return;
  }

  if (utenteÈResponsabile) {
    apriConfezionatoreModal(W1PSN);
  } else {
    scriviTracklist(W1PSN, "", <?php echo json_encode($nomecompleto); ?>);
  }
}

function assegnaPrelevatore(numLista, nomeCompleto) {
  sessionStorage.setItem("prelevatoreAssegnato", nomeCompleto);
  apriConfezionatoreModal(numLista);
}

function apriConfezionatoreModal(W1PSN) {
  // Mappatura dipendenti per posizione
  const confezionatori = JSON.parse(document.getElementById("confezionatoriData").textContent || "[]");
  const posizioni = {};
  confezionatori.forEach(d => {
    const pos = d.BDPOSI?.trim();
    if (pos && /^\d+$/.test(pos)) {
      posizioni[pos] = (d.BDNOME + '.' + d.BDCOGN).trim(); // mantiene valore per coerenza
    }
  });

  // --- INIZIO BLOCCO LISTE PER CONFEZIONATORE ---
  // CLIENTE: prendi solo la parte prima dello spazio, senza substr(0,10)
  const tutteLeListe = <?= json_encode(array_map(function($r) {
    $resto = $r['CODSPEDDESCR'] ?? '';
    $spazio = strpos($resto, ' ');
    $clientestr = $spazio !== false ? substr($resto, 0, $spazio) : $resto;
    return [
      'CLIENTE' => $clientestr,
      'W1CONF' => trim($r['CONFEZIONATORE'] ?? ''),
      'NUMLISTA' => $r['NUMLISTA'],
      'DATASPED' => $r['DATASPED']
    ];
  }, $rows ?? [])); ?>;
  const listePerConfezionatore = {};
  tutteLeListe.forEach(item => {
    if (!listePerConfezionatore[item.W1CONF]) listePerConfezionatore[item.W1CONF] = [];
    listePerConfezionatore[item.W1CONF].push(item.CLIENTE);
    if (!listePerConfezionatore[item.W1CONF + '_PESI']) listePerConfezionatore[item.W1CONF + '_PESI'] = {};
    if (!listePerConfezionatore[item.W1CONF + '_PESI'][item.CLIENTE]) listePerConfezionatore[item.W1CONF + '_PESI'][item.CLIENTE] = 0;
    listePerConfezionatore[item.W1CONF + '_PESI'][item.CLIENTE] += parseFloat((<?= json_encode(array_column($rows ?? [], 'PESO', 'NUMLISTA')) ?>)[item.NUMLISTA]?.replace(',', '.') || 0);
  });
  // Trova la lista attuale
  const listaAttuale = tutteLeListe.find(l => l.NUMLISTA === W1PSN);
  const descrAttuale = listaAttuale?.CLIENTE ?? '';
  // --- FINE BLOCCO LISTE PER CONFEZIONATORE ---

  // --- INIZIO BLOCCO: dipendentiConStessoCliente ---
  const dipendentiConStessoCliente = {};
  Object.entries(listePerConfezionatore).forEach(([nome, clienti]) => {
    if (!Array.isArray(clienti)) return; // Evita errori su _PESI
    if (clienti.includes(descrAttuale)) {
      dipendentiConStessoCliente[nome] = true;
    }
  });
  // --- FINE BLOCCO: dipendentiConStessoCliente ---

  // Rimuovi eventuale modale esistente con stesso ID
  const existingModal = document.getElementById('assegnaConfezionatoreModal');
  if (existingModal && existingModal.parentNode) {
    existingModal.parentNode.remove();
  }

  // Genera HTML delle card per ogni posizione assegnata, con bottone grande, info e box badge delle liste
  let buttonHtml = '';
  for (let i = 1; i <= 15; i++) {
    const pos = String(i);
    if (posizioni[pos]) {
      // Trova il nick per questa posizione (se disponibile)
      let nick = '';
      for (const d of confezionatori) {
        const posConf = d.BDPOSI?.trim();
        if (posConf === pos) {
          nick = (d.BDNICK ?? '').trim();
          break;
        }
      }
      buttonHtml += `
        <div class="position-absolute text-center" style="top: ${i <= 5 ? '5%' : (i <= 14 ? '40%' : '75%')}; left: ${i <= 5 ? (5 + (i - 1) * 18) : (2 + ((i - 6) % 9) * 11)}%; width: 160px;">
          <div class="mb-1 position-relative">
            <button class="btn btn-outline-primary w-100 position-relative ${dipendentiConStessoCliente[posizioni[pos]] ? 'blink' : ''}" style="font-size: 1.4em; padding: 8px 0;" onclick="assegnaConfezionatore('${W1PSN}', '${posizioni[pos]}')">
              ${nick || posizioni[pos]?.split(' ').slice(-1)[0]}
          </div>
          <div class="confezionatore-date-badges bg-light border rounded p-1 mt-2"
               style="display: flex; flex-direction: column; max-height: 200px; overflow-y: scroll; font-size: 12px;">
            ${(() => {
              // nuovo blocco raggruppamento per data
              const gruppiPerData = {};
              (tutteLeListe || []).forEach(l => {
                if (l.W1CONF !== posizioni[pos]) return;
                const data = l.DATASPED || '';
                const key = `${data}`;
                if (!gruppiPerData[key]) gruppiPerData[key] = { peso: 0, liste: [] };
                gruppiPerData[key].peso += parseFloat((<?= json_encode(array_column($rows ?? [], 'PESO', 'NUMLISTA')) ?>)[l.NUMLISTA]?.replace(',', '.') || 0);
                gruppiPerData[key].liste.push(l.NUMLISTA);
              });
              // Ogni badge è un blocco pieno (w-100, d-block)
              return Object.entries(gruppiPerData).map(([data, info]) => {
                const dataIt = data || 'Data mancante';
                const encodedData = encodeURIComponent(data);
                // Passa il nome completo (posizioni[pos]) invece del nick
                const encodedNomeCompleto = encodeURIComponent(posizioni[pos]);
                return `<div class="badge bg-secondary text-light mb-1 w-100 d-block text-wrap" style="font-size: 1rem; white-space: normal; cursor: pointer;" onclick="mostraDettagliGiorno('${encodedNomeCompleto}', '${encodedData}')">${dataIt} - ${info.liste.length} liste - ${info.peso.toFixed(2)}Kg</div>`;
              }).join('');
            })()}
          </div>
        </div>
      `;
    }
  }

// Funzione mostraDettagliGiorno globale
window.mostraDettagliGiorno = function(nomeUtente, dataISO) {
  fetch(`dettagli_giorno.php?utente=${nomeUtente}&data=${dataISO}`)
    .then(resp => resp.text())
    .then(html => {
      const contenuto = `
        <div class="modal fade" id="popupDettagliGiorno" tabindex="-1" aria-labelledby="popupDettagliGiornoLabel" aria-hidden="true" style="z-index: 1060;">
          <div class="modal-dialog modal-lg">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="popupDettagliGiornoLabel"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
              </div>
              <div class="modal-body">${html}</div>
            </div>
          </div>
        </div>
        <div class="modal-backdrop fade show" style="z-index: 1055;"></div>
      `;
      document.getElementById('modalContainer').innerHTML = contenuto;
      // Blocco JS per settare il titolo formattato con data decodificata e formattata
      const dataObj = new Date(decodeURIComponent(dataISO));
      const dataFormattata = dataObj.toLocaleDateString('it-IT', {
        day: '2-digit',
        month: '2-digit',
        year: '2-digit'
      });
      const nomeDecodificato = decodeURIComponent(nomeUtente);
      const titolo = document.getElementById('popupDettagliGiornoLabel');
      if (titolo) {
        titolo.innerHTML = `Dettagli liste di ${nomeDecodificato} - ${dataFormattata}`;
      }
      const modal = new bootstrap.Modal(document.getElementById('popupDettagliGiorno'));
      modal.show();
    })
    .catch(err => alert("Errore nel caricamento dei dettagli."));
}

// Funzione info dipendente (modale dettagliata) - versione globale
window.mostraInfoDipendente = function(nomeDipendente) {
  const liste = listePerConfezionatore[nomeDipendente] || [];
  const pesi = listePerConfezionatore[nomeDipendente + '_PESI'] || {};
  const contenuto = liste.map(lista => {
    const peso = pesi[lista]?.toFixed(2) || '0.00';
    return `<li>${lista} - ${peso} kg</li>`;
  }).join('');

  const html = `
    <div class="modal-backdrop fade show" style="z-index: 1055;"></div>
    <div class="modal fade" id="popupInfoDipendente" tabindex="-1" aria-labelledby="popupInfoDipendenteLabel" aria-hidden="true" style="z-index: 1060;">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="popupInfoDipendenteLabel">Liste di ${nomeDipendente}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
          </div>
          <div class="modal-body">
            <ul>${contenuto}</ul>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button>
          </div>
        </div>
      </div>
    </div>
  `;

  document.getElementById('modalContainer').innerHTML = html;
  const modal = new bootstrap.Modal(document.getElementById('popupInfoDipendente'));
  modal.show();
}

  const modalDiv = document.createElement("div");
  modalDiv.innerHTML = `
    <div class="modal fade" id="assegnaConfezionatoreModal" tabindex="-1" aria-labelledby="assegnaConfezionatoreModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-fullscreen modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="assegnaConfezionatoreModalLabel" style="font-size: 1.6rem;">Lista <span id="heaListaNumero" style="font-size: 1.7rem;"></span> - Peso <span id="heaListaPeso" style="font-size: 1.7rem;"></span> kg -  <span id="heaListaCliente" style="font-size: 1.7rem;"></span></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
          </div>
          <div class="modal-body p-0" style="position: relative; height: 100vh;">
            <div style="height: 100%; width: 100%;">
              <div class="position-relative w-100" style="height: 100%; background-color: #f8f9fa; border: 1px solid #ccc;">
                ${buttonHtml}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>`;
  document.body.appendChild(modalDiv);
  const listaSpan = modalDiv.querySelector('#heaListaNumero');
  if (listaSpan) listaSpan.textContent = W1PSN;
  const pesoAttuale = (<?= json_encode(array_column($rows ?? [], 'PESO', 'NUMLISTA')) ?>)[W1PSN] || '';
  const pesoSpan = modalDiv.querySelector('#heaListaPeso');
  if (pesoSpan && pesoAttuale) pesoSpan.textContent = pesoAttuale;
  const appendedModalElement = document.getElementById('assegnaConfezionatoreModal');
  if (appendedModalElement) {
    const dynamicModal = new bootstrap.Modal(appendedModalElement);
    appendedModalElement.addEventListener('shown.bs.modal', () => {
      appendedModalElement.querySelector('button')?.focus();
    });
    dynamicModal.show();
  }
}

function assegnaConfezionatore(numLista, nomeCompleto) {
  // --- INIZIO BLOCCO: controllo dipendentiConStessoCliente ---
  if (typeof dipendentiConStessoCliente !== 'undefined' && dipendentiConStessoCliente && Object.keys(dipendentiConStessoCliente).length === 1) {
    const unico = Object.keys(dipendentiConStessoCliente)[0];

    fetch('scrivi_f42522hea.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `W1PSN=${encodeURIComponent(numLista)}&W1CONF=${encodeURIComponent(unico)}`
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        sessionStorage.setItem('msgAssegnazione', data.message);
        location.reload();
        document.getElementById('msgAssegnazione').textContent = `Lista ${numLista} assegnata a ${nomeCompleto}`;
      } else {
        alert('Errore durante l\'assegnazione');
      }
    });
    return;
  }
  // --- FINE BLOCCO controllo dipendentiConStessoCliente ---

  const payload = {
    W1PSN: numLista,
    W1PREL: sessionStorage.getItem("prelevatoreAssegnato") || '',
    W1CONF: nomeCompleto,
    W1USER: "<?php echo htmlspecialchars($nomecompleto); ?>"
  };

  fetch("scrivi_f42522hea.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      "X-Requested-With": "XMLHttpRequest"
    },
    body: JSON.stringify(payload)
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      // Mostra messaggio accanto a logout e memorizza per reload
      document.getElementById('msgAssegnazione').textContent = data.message;
      sessionStorage.setItem('msgAssegnazione', data.message);
      location.reload();
      document.getElementById('msgAssegnazione').textContent = `Lista ${numLista} assegnata a ${nomeCompleto}`;
    } else {
      document.getElementById('msgAssegnazione').textContent = 'Errore durante l\'assegnazione';
    }

    const modal = bootstrap.Modal.getInstance(document.getElementById('assegnaConfezionatoreModal'));
    if (modal) modal.hide();
    // Scrolla la pagina in alto dopo aver mostrato il messaggio
    window.scrollTo({ top: 0, behavior: 'smooth' });
    //setTimeout(() => {
    //  document.getElementById('msgAssegnazione').textContent = '';
    //  //location.reload();
    //}, 4000);
  })
  .catch(err => {
    alert("Errore di rete confezionatore");
    console.error(err);
  });
}
</script>
<style>
  @keyframes blink-animation {
    0%   { background-color:rgb(255, 255, 255); color: black; }
    50%  { background-color: #28a745; color: white; }
    100% { background-color:rgb(255, 255, 255); color: black; }
  }

  .blink {
    animation: blink-animation 1s infinite;
    border: 2px solid #28a745;
    font-weight: bold;
  }
</style>
</script>
<script>
// ========== Altre funzioni JS ==============
let SDSHAN_GLOBAL = "";
let SDAN8_GLOBAL = "";

// Funzione per resettare i campi filtro
function resetCampiFiltro() {
  // Svuota i campi filtro
  var listanumFiltro = document.querySelector('input[name="listanumFiltro"]');
  if (listanumFiltro) listanumFiltro.value = '';
  var codspedizione = document.querySelector('input[name="codspedizione"]');
  if (codspedizione) codspedizione.value = '';
  var dataspedizione = document.querySelector('input[name="dataspedizione"]');
  if (dataspedizione) dataspedizione.value = '';
  var filtroConfezionatore = document.querySelector('input[name="filtroConfezionatore"]');
  if (filtroConfezionatore) filtroConfezionatore.value = '';
  var filtroInCarico = document.querySelector('input[name="filtroInCarico"]');
  if (filtroInCarico) filtroInCarico.value = '';
  // Deseleziona tutte le checkbox filtro
  var SoloMaiLette = document.querySelector('input[name="SoloMaiLette"]');
  if (SoloMaiLette) SoloMaiLette.checked = false;
  var invioRapido = document.querySelector('input[name="invioRapido"]');
  if (invioRapido) invioRapido.checked = false;
}

function apriTracklistePopup(NUMLISTA) {
  // Rimuovi eventuale modale esistente con stesso ID
  const existingModal = document.getElementById('tracklistePopup');
  if (existingModal) {
    existingModal.remove();
  }

  fetch("trackliste_popup.php?SDPSN=" + encodeURIComponent(NUMLISTA))
    .then(response => response.text())
    .then(data => {
      const modalDiv = document.createElement("div");
      modalDiv.innerHTML = `
        <div class="modal fade" id="tracklistePopup" tabindex="-1" aria-labelledby="tracklistePopupLabel" aria-hidden="true">
          <div class="modal-dialog modal-xl modal-custom-top">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="tracklistePopupLabel">Trackliste</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
              </div>
              <div class="modal-body">` + data + `</div>
            </div>
          </div>
        </div>`;
      document.body.appendChild(modalDiv);
      const modal = new bootstrap.Modal(document.getElementById('tracklistePopup'));
      modal.show();
    })
    .catch(error => {
      alert("Errore nel caricamento della trackliste.");
      console.error(error);
    });
}

function apriTrackListPopup(NUMLISTA) {
  fetch("track_list.php?SDPSN=" + encodeURIComponent(NUMLISTA))
    .then(response => response.text())
    .then(data => {
      const modalDiv = document.createElement("div");
      modalDiv.innerHTML = `
        <div class="modal fade" id="trackListPopup" tabindex="-1" aria-labelledby="trackListPopupLabel" aria-hidden="true">
          <div class="modal-dialog modal-xl modal-custom-top">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="trackListPopupLabel">Track List</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
              </div>
              <div class="modal-body">` + data + `</div>
            </div>
          </div>
        </div>`;
      document.body.appendChild(modalDiv);
      const modal = new bootstrap.Modal(document.getElementById('trackListPopup'));
      modal.show();
    })
    .catch(error => {
      alert("Errore nel caricamento della Track List.");
      console.error(error);
    });
}
function aggiornaPrelevatore(selectElement, numLista) {
  const nuovoValore = selectElement.value;

  fetch("aggiorna_prelevatore.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json"
    },
    body: JSON.stringify({ NUMLISTA: numLista, W1PREL: nuovoValore })
  })
  .then(response => response.text())
  .then(data => {
    console.log("Aggiornamento riuscito:", data);
    eseguiQueryPrelevatore(numLista);
  })
  .catch(error => {
    console.error("Errore durante l'aggiornamento:", error);
  });
}

function eseguiQueryPrelevatore(numLista) {
  fetch("query_prelevatore.php?NUMLISTA=" + encodeURIComponent(numLista))
    .then(response => response.text())
    .then(data => {
      console.log("Query eseguita:", data);
    })
    .catch(error => {
      console.error("Errore durante l'esecuzione della query:", error);
    });
}

$(document).ready(function() {
  $('.select2').select2({
    width: '100%'  // forza l’allineamento col resto della tabella
  });
  // Focus sull'input "Lista N" invece che sul primo select
  const listaNInput = document.querySelector('input[name="listanumFiltro"]');
  if (listaNInput) {
    listaNInput.focus();
    listaNInput.addEventListener('input', () => {
      const valore = listaNInput.value.trim();
      if (valore.length === 8) {
          const utente = "<?php echo htmlspecialchars($nomecompleto); ?>";
          const responsabile = "<?php echo htmlspecialchars($bdreli); ?>";
          // Trova la riga della tabella corrispondente al valore inserito
          const riga = Array.from(document.querySelectorAll('tbody tr')).find(tr => tr.children[2]?.textContent?.trim() === valore);
          const confezionatore = riga?.dataset.confezionatore?.trim() || '';
          const codiceCliente = riga?.children[0]?.textContent?.trim() || '';
          if (responsabile === 'Y' && !confezionatore.trim()) {
              apriHEAPopup(valore);
          }
      else
          {
          scriviTracklist(valore, codiceCliente, utente);
      } }
    });
  }
});

function aggiornaConfezionatore(selectElement, numLista) {
  const nuovoValore = selectElement.value;

  fetch("aggiorna_confezionatore.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json"
    },
    body: JSON.stringify({ NUMLISTA: numLista, W1CONF: nuovoValore })
  })
  .then(response => response.json())
  .then(data => {
    if (!data.success) {
      alert("Errore aggiornamento confezionatore: " + (data.message || ''));
    }
  })
  .catch(error => {
    alert("Errore rete aggiornamento confezionatore: " + error);
  });
}

// Gestione input ubicazione: $ o Invio per confermare
const ubicazioneInput = document.getElementById('ubicazioneInput');
if (ubicazioneInput) {
  ubicazioneInput.addEventListener('input', (event) => {
    const val = event.target.value;
    if (val.includes('$')) {
      confermaScrittura();
    }
    if (val.length === 16) {
      confermaScrittura();
    }
  });
  ubicazioneInput.addEventListener('keydown', (event) => {
    if (event.key === 'Enter') {
      confermaScrittura();
    }
  });
}

let tracklistParams = {};

function scriviTracklist(W1PSN, W1AN8, W1USER) {
  tracklistParams = { W1PSN, W1AN8, W1USER };

  const usaBadgeCheckbox = document.getElementById("invioRapido");
  if (usaBadgeCheckbox?.checked) {
    const badgeUtente = <?php echo json_encode($bdbadg) ?>;
    if (!badgeUtente) {
      alert("Badge utente non disponibile.");
      return;
    }

    fetch("scrivi_f42522trk.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json"
      },
      body: JSON.stringify({ ...tracklistParams, W1LOCN: badgeUtente })
    })
    .then(response => response.json())
    .then(json => {
      if (json.message) {
        const msgEl = document.getElementById("msgEsito");
        if (msgEl) {
          msgEl.textContent = json.message;
          msgEl.classList.remove("d-none");
        }
      }
      sessionStorage.setItem('ultimoEsito', JSON.stringify(json));
      localStorage.setItem('filtro_invioRapido', usaBadgeCheckbox?.checked ? '1' : '0');
      location.reload();
      const listaNInput = document.querySelector('input[name="listanumFiltro"]');
      if (listaNInput) {
        listaNInput.focus();
      }
    })
    .catch(error => {
      alert("Errore invio rapido: " + error);
      console.error(error);
    });

    return;
  }

  document.getElementById("ubicazioneInput").value = ""; // Pulisce il campo
  const ubicazioneModalEl = document.getElementById('ubicazioneModal');
  // --- Reset del campo filtro Lista N quando si apre la modale ubicazione ---
  const listaNInput = document.querySelector('input[name="listanumFiltro"]');
  if (listaNInput) listaNInput.value = "";
  // --------------------------------------------------
  const ubicazioneModal = new bootstrap.Modal(ubicazioneModalEl);
  ubicazioneModal.show();
  ubicazioneModalEl.addEventListener('shown.bs.modal', () => {
    const input = document.getElementById('ubicazioneInput');
    scritturaConsentita = false;
    setTimeout(() => {
      scritturaConsentita = true;
      input.focus();
    }, 300);
  }, { once: true });
}

// Stato per evitare doppio invio della scrittura ubicazione
let scritturaInCorso = false;
let scritturaConsentita = false;

function confermaScrittura() {
  if (!scritturaConsentita) return;
  if (scritturaInCorso) return; // evita doppio invio
  scritturaInCorso = true;
  const ubicazione = document.getElementById("ubicazioneInput").value;
  const ubicazionePulita = ubicazione.replaceAll('$', '').replaceAll('\u0024', '');
  if (!ubicazione.trim()) {
    alert("Per favore inserisci un'ubicazione.");
    scritturaInCorso = false;
    return;
  }
  // Controllo che l'ubicazione sia un badge valido di dipendente usando la stessa logica PHP
  const badgeValido = <?php echo json_encode(array_map(function($d) {
    return strtoupper(trim($d['BDBADG']));
  }, $nomecompletoinfo)); ?>;

  if (!badgeValido.includes(ubicazionePulita.toUpperCase())) {
    alert("Ubicazione non valida. Inserisci un badge abilitato.");
    scritturaInCorso = false;
    return;
  }

  fetch("scrivi_f42522trk.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json"
    },
    body: JSON.stringify({ ...tracklistParams, W1LOCN: ubicazionePulita })
  })
  .then(response => response.json())
  .then(json => {
    bootstrap.Modal.getInstance(document.getElementById('ubicazioneModal')).hide();
    sessionStorage.setItem('ultimoEsito', JSON.stringify(json));
    location.reload();
    const listaNInput = document.querySelector('input[name="listanumFiltro"]');
    if (listaNInput) {
      listaNInput.focus();
    }
    if (json.message) {
      const msgEl = document.getElementById("msgEsito");
      if (msgEl) {
        msgEl.textContent = json.message;
        msgEl.classList.remove("d-none");
      }
    }
  })
  .catch(error => {
    alert("Errore nella scrittura: " + error + JSON.stringify(tracklistParams));
    console.error(error);
  })
  .finally(() => {
    scritturaInCorso = false;
  });
}

function controllaUbicazione(input, event) {
  const val = input.value.trim();
  if (event && (event.key === '$' || event.keyCode === 36)) {
    confermaScrittura();
  }
}
// --- Mostra messaggio di esito dopo reload ---
document.addEventListener("DOMContentLoaded", () => {
  // Mostra eventuale messaggio di assegnazione dopo reload
  const msg = sessionStorage.getItem('msgAssegnazione');
  if (msg) {
    const el = document.getElementById('msgAssegnazione');
    if (el) {
      el.textContent = msg;
      sessionStorage.removeItem('msgAssegnazione');
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
  }
  // Messaggio esito da scrivi_f42522trk
  const ultimoEsito = sessionStorage.getItem('ultimoEsito');
  if (ultimoEsito) {
    try {
      const esito = JSON.parse(ultimoEsito);
      if (esito.message) {
        const msgEl = document.getElementById("msgEsito");
        if (msgEl) {
          msgEl.textContent = esito.message;
          msgEl.classList.remove("d-none");
        }
      }
    } catch (e) {
      console.error("Errore parsing esito:", );
    }
    sessionStorage.removeItem('ultimoEsito');
  }
});
document.querySelectorAll('form').forEach(form => {
  form.addEventListener('submit', function () {
    setTimeout(() => resetCampiFiltro(), 100);
  });
});
</script>