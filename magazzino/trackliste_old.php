<?php
session_start();

// Verifica se l'utente è loggato
if (!isset($_SESSION['nomeutente'])) {
    header('Location: login.php');
    exit;
}

require_once('/www/php80/htdocs/CRUD/websmart/v13.2/include/WebSmartObject.php');
require_once('/www/php80/htdocs/CRUD/websmart/v13.2/include/xl_functions.php');
require("/www/php80/htdocs/sped/config.inc.php");
require("/www/php80/htdocs/sped/classes/AzureService.class.php");

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

class trackliste extends WebSmartObject
{
    public function runMain()
	{
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

        $queryDatiDipendenti = "SELECT 
        TRIM(BDNOME) AS BDNOME, 
        TRIM(BDCOGN) AS BDCOGN,  
        TRIM(BDPASS) AS BDPASS , 
        TRIM(BDCOGN)||'.'||TRIM(BDNOME) as NOMEUTENTE, 
        TRIM(BDAUTH) AS BDAUTH,
        TRIM(BDEMAI) AS BDEMAI,
        TRIM(BDCOGE) AS BDCOGE,
        TRIM(BDNICK) AS BDNICK,
        TRIM(BDBADG) AS BDBADG,
        TRIM(BDREPA) AS BDREPA,
        TRIM(BDPOSI) AS BDPOSI,
        TRIM(BDPREL) AS BDPREL,
        TRIM(BDCONF) AS BDCONF,
        TRIM(BDRELI) AS BDRELI,
        TRIM(BDTIMB) AS BDTIMB,
        TRIM(BDBDTM) AS BDBDTM,
        TRIM(BDPASS) AS BDPASS 
        FROM BCD_DATIV2.BDGDIP0F";
        $stmtDipendenti = odbc_exec($db_connection, $queryDatiDipendenti);
        $resultsDipendenti = [];
        $resultsprelevatori = [];
        $resultConfezionatore = [];

        while ($riga = odbc_fetch_array($stmtDipendenti)) {
            $resultsDipendenti[] = $riga;

            if ($riga['BDPREL'] == 'Y') {
                $resultsprelevatori[] = $riga;
            }
            if ($riga['BDCONF'] == 'Y') {
                $resultConfezionatore[] = $riga;
            } 
        }

        // Gestione logout
        if (isset($_GET['logout'])) {
            session_destroy();
            header("Location: login.php");
            exit;
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
        $soloMaiLette = isset($_GET['soloMaiLette']) && $_GET['soloMaiLette'] ? true : false;

        if ($dataSpedizioneFiltro) {
            $parts = explode('-', $dataSpedizioneFiltro);
            if (count($parts) === 3) {
                $dataSpedizioneFiltro = $parts[2] . '/' . $parts[1] . '/' . substr($parts[0], -2);
            }
        }

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
                $row['UBICAZIONE'] = $locn['W1LOCN'] ?? '';
            } else {
                $row['UBICAZIONE'] = '';
            }
            $rows[] = $row;
        }

        // Applica filtro "solo mai lette" se richiesto
        if ($soloMaiLette) {
            $rows = array_filter($rows, function($r) {
                return empty(trim($r['UBICAZIONE'] ?? ''));
            });
        }

        // Sanitize for HTML output
        $codiceSpedizioneFiltro = htmlspecialchars($codiceSpedizioneFiltro, ENT_QUOTES);
        $listanumeroFiltro = htmlspecialchars($listanumeroFiltro, ENT_QUOTES);
        $filtroConfezionatore = htmlspecialchars($filtroConfezionatore, ENT_QUOTES);
        $filtroInCarico = htmlspecialchars($filtroInCarico, ENT_QUOTES);

        // Trova i dati del dipendente autenticato
        $utente = $_SESSION['nomeutente'];
        $dipendenteAutenticato = array_filter($resultsDipendenti, function ($d) use ($utente) {
            return $d['NOMEUTENTE'] === $utente;
        });
        $dipendenteAutenticato = reset($dipendenteAutenticato) ?: [];
        $nomecompleto = trim(($dipendenteAutenticato['BDNOME'] ?? '') . ' ' . ($dipendenteAutenticato['BDCOGN'] ?? ''));

        // Gestione invioRapido
        $invioRapidoSelezionato = ($_GET['invioRapido'] ?? '') === '1' ? '1' : '0';

        // Imposta il checkbox "assegnami le liste" attivo di default per utenti Gruppo
        if (($dipendenteAutenticato['BDAUTH'] ?? '') === 'GRUPPO') {
            $_GET['chkAssegnami'] = '1';
            $_GET['chkMaiLette'] = '1';
        }

        echo <<<HTML
<!DOCTYPE html>
<html>
<head>
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
        font-size: 18px;
        padding: 12px;
      }
      form .form-label {
        font-weight: 500;
        font-size: 18px;
      }
      #contents {
        padding: 20px;
      }
      .btn {
        font-size: 18px;
        padding: 12px 20px;
      }
      .btn-sm {
        font-size: 16px;
        padding: 10px 16px;
      }
      .btn i {
        font-size: 20px;
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
        font-size: 18px;
        padding: 15px;
      }
      .table td {
        font-size: 16px;
        padding: 12px;
      }
      .form-check-label {
        font-size: 16px;
      }
      .form-check-input {
        transform: scale(1.3);
        margin-right: 8px;
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
      /* Bottoni azioni più grandi */
      .action-buttons .btn {
        margin: 2px;
        min-width: 50px;
        height: 45px;
      }
      .action-buttons .btn i {
        font-size: 22px;
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
            <div class="mb-3 position-relative">
                <span class="fs-5">Buongiorno, <strong>$nomecompleto</strong></span>
                <a href="?logout=1" class="btn btn-sm btn-outline-secondary ms-3">Logout</a>
                <a href="index.php" class="btn btn-sm btn-outline-primary ms-3">Dashboard</a>
                <span id="msgAssegnazione" class="ms-3 text-success fw-bold"></span>
                <a id="msgEsito" class="text-danger fw-bold d-none ms-3"></a>
                <div id="notificaAssegnazione" class="alert alert-success d-none position-absolute end-0 top-0 mt-2 me-2" role="alert" style="z-index: 9999;">
                </div>
            </div>

            <form method="get" class="mb-3">
                <div class="input-group mb-2 align-items-center">
                    <input type="text" class="form-control" name="codspedizione" placeholder="Codice Spedizione" value="{$codiceSpedizioneFiltro}">
                    <input type="text" class="form-control" name="listanumFiltro" placeholder="Lista N" value="{$listanumeroFiltro}">
                    <input type="date" class="form-control" name="dataspedizione" value="{$dataSpedizioneFiltro}">
                    <input type="text" class="form-control" name="filtroConfezionatore" placeholder="Confezionatore" value="{$filtroConfezionatore}">
                    <input type="text" class="form-control" name="filtroInCarico" placeholder="In carico a" value="{$filtroInCarico}">
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" id="chkSoloMaiLette" name="soloMaiLette" value="1" <?= $soloMaiLette ? 'checked' : '' ?>
                    <label class="form-check-label" for="chkSoloMaiLette">Solo mai lette</label>
                </div>
                <div class="form-check form-check-inline ms-3">
                    <input class="form-check-input" type="checkbox" id="invioRapido" name="invioRapido" value="1" <?= $invioRapidoSelezionato == '1' ? 'checked' : '' ?>
                    <label class="form-check-label" for="invioRapido">Assegnami le liste lette</label>
                </div>
                <button type="submit" class="btn btn-outline-primary">Applica</button>
                <a href="trackliste.php" class="btn btn-outline-secondary ms-2">Reset</a>
            </form>

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

        // Applica filtri aggiuntivi
        if ($filtroConfezionatore !== '') {
            $rows = array_filter($rows, function($r) use ($filtroConfezionatore) {
                return stripos($r['CONFEZIONATORE'] ?? '', $filtroConfezionatore) !== false;
            });
        }

        if ($filtroInCarico !== '') {
            $rows = array_filter($rows, function($r) use ($filtroInCarico, $resultsDipendenti) {
                $nome = array_column(array_filter($resultsDipendenti, function($d) use ($r) {
                    return strtoupper(trim($d['BDBADG'])) === strtoupper(trim($r['UBICAZIONE'] ?? ''));
                }), 'BDNOME')[0] ?? '';

                $cognome = array_column(array_filter($resultsDipendenti, function($d) use ($r) {
                    return strtoupper(trim($d['BDBADG'])) === strtoupper(trim($r['UBICAZIONE'] ?? ''));
                }), 'BDCOGN')[0] ?? '';

                $nomeCompleto = trim($nome . ' ' . $cognome);
                return stripos($nomeCompleto, $filtroInCarico) !== false;
            });
        }

        foreach ($rows as $row) {
            if (($dipendenteAutenticato['BDCONF'] ?? '') === 'Y' && empty($invioRapidoSelezionato)) {
                $nomeCompletoUtente = trim(($dipendenteAutenticato['BDNOME'] ?? '') . ' ' . ($dipendenteAutenticato['BDCOGN'] ?? ''));
                $badgeUtente = strtoupper(trim($dipendenteAutenticato['BDBADG'] ?? ''));
                if (trim($row['CONFEZIONATORE']) !== $nomeCompletoUtente && strtoupper(trim($row['UBICAZIONE'] ?? '')) !== $badgeUtente) {
                    continue;
                }
            }

            echo "<tr> 
            <td>" . htmlspecialchars($row['CODSPED'] ?? '') . "</td>    
            <td>" . htmlspecialchars($row['DATASPED'] ?? '') . "</td>
            <td>" . htmlspecialchars($row['NUMLISTA'] ?? '') .  "</td>";

            // Blocco confezionatore
            echo "<td>";
            if (!empty(trim($row['CONFEZIONATORE']))) {
                if (count($resultConfezionatore ?? []) > 0) {
                    echo "<select class='form-select form-select-sm select2' onchange=\"aggiornaConfezionatore(this, '" . htmlspecialchars($row['NUMLISTA']) . "')\">
                            <option value=''></option>";
                    foreach ($resultConfezionatore as $conf) {
                        $nomeCompleto = trim($conf['BDNOME'] . ' ' . $conf['BDCOGN']);
                        $optionValue = $nomeCompleto;
                        $selected = ($nomeCompleto === trim($row['CONFEZIONATORE'])) ? 'selected' : '';
                        $nick = trim($conf['BDNICK'] ?? '');
                        echo "<option value=\"" . htmlspecialchars($optionValue) . "\" $selected>" . htmlspecialchars($nick) . "</option>";
                    }
                    echo "</select>";
                } else {
                    $nick = '';
                    foreach ($resultConfezionatore ?? [] as $conf) {
                        $nomeCompleto = trim($conf['BDNOME'] . ' ' . $conf['BDCOGN']);
                        if ($nomeCompleto === trim($row['CONFEZIONATORE'])) {
                            $nick = trim($conf['BDNICK'] ?? '');
                            break;
                        }
                    }
                    echo htmlspecialchars($nick ?: trim($row['CONFEZIONATORE']));
                }
            }
            echo "</td>";

            echo "<td>" . htmlspecialchars(array_column(array_filter($resultsDipendenti, function($d) use ($row) {
                return strtoupper(trim($d['BDBADG'])) === strtoupper(trim($row['UBICAZIONE'] ?? ''));
            }), 'BDNOME')[0] ?? '') . " " . 
            htmlspecialchars(array_column(array_filter($resultsDipendenti, function($d) use ($row) {
                return strtoupper(trim($d['BDBADG'])) === strtoupper(trim($row['UBICAZIONE'] ?? ''));
            }), 'BDCOGN')[0] ?? $row['UBICAZIONE']) . "</td>
            <td class='action-buttons'>";

            echo "<button class='btn btn-info' title=\"Trackliste\" onclick=\"apriTracklistePopup('" . htmlspecialchars($row['NUMLISTA']) .  "')\">
                    <i class='bi bi-list-ul'></i>
                  </button>
                  <button class='btn btn-success' title=\"Cambia ubicazione\" onclick=\"scriviTracklist('" . htmlspecialchars($row['NUMLISTA']) . "', '" . htmlspecialchars($row['CODCLIENTE']) . "', '" . htmlspecialchars($utente) . "')\">
                    <i class='bi bi-box-arrow-up-right'></i>
                  </button>";

            // Bottone sospensione solo per utenti Ufficio
            if (($dipendenteAutenticato['BDAUTH'] ?? '') === 'Ufficio') {
                echo "<button class='btn btn-warning' title=\"Sospendi Lista\" onclick=\"sospendiLista('" . htmlspecialchars($row['NUMLISTA']) . "')\">
                        <i class='bi bi-pause-circle'></i>
                      </button>";
            }

            if (empty(trim($row['CONFEZIONATORE'])) && ($dipendenteAutenticato['BDRELI'] ?? '') === 'Y') {
                echo "<button class='btn btn-primary' title=\"Assegna\" onclick=\"apriHEAPopup('" . htmlspecialchars($row['NUMLISTA']) . "')\">
                        <i class='bi bi-file-earmark-plus'></i>
                      </button>";
            }

            echo "</td>
            <td>" . htmlspecialchars($row['PESO'] ?? '') .  "</td> 
            </tr>";
        }

        echo <<<HTML
                </tbody>
            </table>

            <!-- Modal per dettagli trackliste -->
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

            <div style="padding-bottom: 150px;"></div>
        </div>
    </div>

    <script>
    document.addEventListener("DOMContentLoaded", () => {
        // Recupera i valori salvati in localStorage
        const soloMaiLette = localStorage.getItem('filtro_soloMaiLette');
        const invioRapido = localStorage.getItem('filtro_invioRapido');

        if (soloMaiLette === '1') document.querySelector('input[name="soloMaiLette"]').checked = true;
        if (invioRapido === '1') document.querySelector('input[name="invioRapido"]').checked = true;

        // Salva i valori al submit del form
        const form = document.querySelector('form[method="get"]');
        if (form) {
            form.addEventListener('submit', () => {
                localStorage.setItem('filtro_codspedizione', document.querySelector('input[name="codspedizione"]').value);
                localStorage.setItem('filtro_dataspedizione', document.querySelector('input[name="dataspedizione"]').value);
                localStorage.setItem('filtro_listanumFiltro', document.querySelector('input[name="listanumFiltro"]').value);
                localStorage.setItem('filtro_soloMaiLette', document.querySelector('input[name="soloMaiLette"]').checked ? '1' : '0');
                localStorage.setItem('filtro_invioRapido', document.querySelector('input[name="invioRapido"]').checked ? '1' : '0');
            });
        }
    });
    </script>
</body>
</html>
HTML;
    }
}

xlLoadWebSmartObject(__FILE__, 'trackliste');
?>