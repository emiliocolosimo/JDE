<?php
session_start();
require_once('/www/php80/htdocs/CRUD/websmart/v13.2/include/WebSmartObject.php');
require_once('/www/php80/htdocs/CRUD/websmart/v13.2/include/xl_functions.php');
require("/www/php80/htdocs/sped/config.inc.php");
require("/www/php80/htdocs/sped/classes/AzureService.class.php");

// Verifica se l'utente è loggato e ha il badge salvato in sessione
/*
if (!isset($_SESSION['redirect_to']) && isset($_SERVER['HTTP_REFERER'])) {
    $_SESSION['redirect_to'] = $_SERVER['HTTP_REFERER'];
}

$utenti = [
    'admin' => 'password123',
    'emilio' => 'emilio'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $utente = $_POST['utente'] ?? '';
    $password = $_POST['password'] ?? '';

    if (isset($utenti[$utente]) && $utenti[$utente] === $password) {
        $_SESSION['autenticato'] = true;
        $_SESSION['utente'] = $utente;
        $redirect = $_SESSION['redirect_to'] ;
        unset($_SESSION['redirect_to']);
        
        header("Location: $redirect");
        exit;
    } else {
        $errore = "Credenziali non valide.";
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit;
}

*/
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


class solleciti extends WebSmartObject
{
    	public function runMain()
	{
		error_reporting(E_ALL);
		ini_set('display_errors', 1);
		date_default_timezone_set('Europe/Rome');

		// Connect to the database
//DB2:
$server="Driver={IBM i Access ODBC Driver};System=127.0.0.1;Uid=".AH_DB2_USER.";Pwd=".AH_DB2_PASS.";TRANSLATE=1;"; 
$user=AH_DB2_USER; 
$pass=AH_DB2_PASS;   
$db_connection=odbc_connect($server,$user,$pass); 
if(!$db_connection) {
	$errMsg = "Errore connessione al database : ".odbc_errormsg();
	error_log($errMsg);
	exit;
}


		$queryprimaria = "SELECT
  F4211A.SDAN8 AS SDAN8,
  F4211A.SDAN8 || ' - ' || COALESCE(F0111B.WWMLNM, '') AS CODCLIENTE,
  F4211A.SDSHAN || ' - ' || COALESCE(F0111A.WWMLNM, '') || ' - ' || 
  COALESCE(F0116.ALCTY1, '') || ' - ' || COALESCE(F0116.ALADDS, '') || ' - ' || COALESCE(F0116.ALCTR, '') AS CODSPED,
  F00365.ONDATE AS DATASPED,
  F4211A.SDPDDJ AS SDPDDJ,
  F4211A.SDSHAN AS SDSHAN,
  (SELECT MAX(W1DTSO) FROM JRGDTA94C.FSOLLSPE0F WHERE W1SHAN = SDSHAN AND W1AN8 = SDAN8 AND W1PDDJ = SDPDDJ)  AS DATASOLLECITO
FROM JRGDTA94C.F4211 AS F4211A
LEFT JOIN JRGDTA94C.F00365 AS F00365 ON F4211A.SDPDDJ = F00365.ONDTEJ
LEFT JOIN JRGDTA94C.F0101 AS F0101 ON F4211A.SDAN8 = F0101.ABAN8
LEFT JOIN JRGDTA94C.F0111 AS F0111A ON F4211A.SDSHAN = F0111A.WWAN8 AND F0111A.WWIDLN = 0
LEFT JOIN JRGDTA94C.F0111 AS F0111B ON F4211A.SDAN8 = F0111B.WWAN8 AND F0111B.WWIDLN = 0
LEFT JOIN JRGDTA94C.F0116 AS F0116 ON F4211A.SDSHAN = F0116.ALAN8
WHERE F4211A.SDDELN = 0 AND F4211A.SDNXTR = '560' AND SDPSN <> 0
GROUP BY 
  F4211A.SDAN8, F4211A.SDSHAN, F00365.ONDATE, F4211A.SDPDDJ, 
  F0111A.WWMLNM, F0111B.WWMLNM, F0116.ALCTY1, F0116.ALADDS, F0116.ALCTR
ORDER BY F4211A.SDPDDJ, F4211A.SDAN8, F4211A.SDSHAN
";
$result = odbc_exec($db_connection, $queryprimaria);
        $rows = [];
        $codiceClienteFiltro = $_GET['codcliente'] ?? '';
        $codiceSpedizioneFiltro = $_GET['codspedizione'] ?? '';
        $dataSpedizioneFiltro = $_GET['dataspedizione'] ?? '';
        if ($dataSpedizioneFiltro) {
            $parts = explode('-', $dataSpedizioneFiltro);
            if (count($parts) === 3) {
                $dataSpedizioneFiltro = $parts[2] . '/' . $parts[1] . '/' . substr($parts[0], -2);
            }
        }
        $dataMaxFiltro = $_GET['datamax'] ?? '';
         if ($dataMaxFiltro) {
            $partsmax = explode('-', $dataMaxFiltro);
            if (count($partsmax) === 3) {
                $dataMaxFiltro = $partsmax[2] . '/' . $partsmax[1] . '/' . substr($partsmax[0], -2);
            }
        }
        while ($row = odbc_fetch_array($result)) {
            if ($codiceClienteFiltro && stripos($row['CODCLIENTE'], $codiceClienteFiltro) === false) {
                continue;
            }
            if ($codiceSpedizioneFiltro && stripos($row['CODSPED'], $codiceSpedizioneFiltro) === false) {
                continue;
            }
            if ($dataSpedizioneFiltro && $row['DATASPED'] !== $dataSpedizioneFiltro) {
                continue;
            }
            if ($dataMaxFiltro) {
                $dataMaxObj = DateTime::createFromFormat('d/m/y', $dataMaxFiltro);
                $dataRigaObj = DateTime::createFromFormat('d/m/y', $row['DATASPED']);
                if ($dataMaxObj && $dataRigaObj && $dataRigaObj > $dataMaxObj) {
                    continue;
                }
            }
            $rows[] = $row;
        }

/*
AND EXISTS (
        SELECT * FROM JRGDTA94C.F4211 AS F4211B
        WHERE F4211A.SDSHAN = F4211B.SDSHAN AND F4211A.SDPDDJ = F4211B.SDPDDJ AND F4211B.SDDELN = 0 AND F4211B.SDNXTR = '560' 
    )
		*/

        // Sanitize for HTML output
        $codiceClienteFiltro = htmlspecialchars($codiceClienteFiltro, ENT_QUOTES);
        $codiceSpedizioneFiltro = htmlspecialchars($codiceSpedizioneFiltro, ENT_QUOTES);

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
  <body>
    <div id="outer-content">
      <div id="page-content" class="container-fluid">
<div class="d-flex justify-content-center align-items-center">
          <img src="img/logo.jpg" alt="Logo" style="max-height: 60px;">
        </div>
      </div>

    <div class="clearfix"></div>
    <div id="contents">
      <form method="get" class="mb-3">
        <div class="input-group mb-2">
          <input type="text" class="form-control" name="codcliente" placeholder="Codice Cliente" value="{$codiceClienteFiltro}">
          <input type="text" class="form-control" name="codspedizione" placeholder="Codice Spedizione" value="{$codiceSpedizioneFiltro}">
          <input type="date" class="form-control" name="dataspedizione" value="{$dataSpedizioneFiltro}">
          <input type="date" class="form-control" name="datamax" placeholder="Data Massima" value="{$dataMaxFiltro}">

        <button type="submit" class="btn btn-outline-primary">Filtra</button>
        <a href="solleciti.php" class="btn btn-outline-secondary ms-2">Reset</a>
        </div>
      </form>
<table class="table table-striped table-bordered table-hover">
<thead class="table-title text-white">
              <tr>
              <th>Codice Cliente</th>
              <th>Codice Spedizione</th>
              <th>Data Spedizione</th>
              <th>Data Sollecito</th>
              <th>Data Spedizione</th>
			  <th></th>
            </tr>
          </thead>
          <tbody>
HTML;

		foreach ($rows as $row) {
			echo "<tr>
      <td>" . htmlspecialchars($row['CODCLIENTE']) . "</td>
      <td>" . htmlspecialchars($row['CODSPED']) . "</td>
      <td>" . htmlspecialchars($row['DATASPED']) . "</td>
      <td>" . htmlspecialchars($row['DATASOLLECITO']) . "</td>
      <td>
<button class='btn btn-sm btn-primary' title=\"Dettagli Ordine\" onclick=\"apriPopup('" . htmlspecialchars($row['SDSHAN']) . "', '" . htmlspecialchars($row['SDPDDJ']) . "')\">
        <i class='bi bi-file-earmark-text'></i>
</button>      
<button class='btn btn-sm btn-secondary' title=\"Visualizza Solleciti\" onclick=\"apriDettaglioSolleciti('" . htmlspecialchars($row['SDSHAN']) . "', '" . htmlspecialchars($row['SDPDDJ']) . "')\">
    <i class='bi bi-send'></i>
</button>
<button class='btn btn-sm btn-success' title=\"Nuovo Sollecito\" onclick=\"apriFormSollecitoConDati('" . htmlspecialchars($row['SDSHAN']) . "', '" . htmlspecialchars($row['SDAN8']) . "', '" . htmlspecialchars($row['SDPDDJ']) . "')\">
    <i class='bi bi-plus-circle'></i>
</button>
<button class='btn btn-sm btn-warning' title=\"Indirizzi Email\" onclick=\"apriIndirizziEmail('"  . htmlspecialchars($row['SDAN8']) .  "', '" . htmlspecialchars($row['SDSHAN']) . "')\">
      <i class='bi bi-envelope'></i>
  </button>
      </td>
    </tr>";
		}

		echo <<<HTML
          </tbody>
        </table>

  <div class="modal fade" id="dettagliModal" tabindex="-1" aria-labelledby="dettagliModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-custom-top">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="dettagliModalLabel">Dettagli Sollecito</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
        </div>
        <div class="modal-body" id="contenutoModal">
          Caricamento...
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="dettaglioSollecitiModal" tabindex="-1" aria-labelledby="dettaglioSollecitiLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-custom-top">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="dettaglioSollecitiLabel">Dettaglio Solleciti</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
        </div>
        <div class="modal-body" id="contenutoSolleciti">
          Caricamento...
        </div>
      </div>
    </div>
  </div>

        <div style="padding-bottom: 150px;"></div>
      </div>
    </div>
    <script>
    let SDSHAN_GLOBAL = "";
    let SDAN8_GLOBAL = "";

    function apriPopup(SDSHAN, SDPDDJ) {
      fetch("dettagli_ordini.php?SDSHAN=" + encodeURIComponent(SDSHAN) + "&SDPDDJ=" + encodeURIComponent(SDPDDJ))
        .then(response => response.text())
        .then(data => {
          document.getElementById('contenutoModal').innerHTML = data;
          var myModal = new bootstrap.Modal(document.getElementById('dettagliModal'));
          myModal.show();
        })
        .catch(error => {
          document.getElementById('contenutoModal').innerHTML = 'Errore nel caricamento dei dati.';
          console.error('Errore:', error);
        });
    }

    function apriDettaglioSolleciti(SDSHAN, SDPDDJ) {
      fetch("dettaglio_solleciti.php?SDSHAN=" + encodeURIComponent(SDSHAN) + "&SDPDDJ=" + encodeURIComponent(SDPDDJ))
        .then(response => response.text())
        .then(data => {
          document.getElementById('contenutoSolleciti').innerHTML = data;
          var myModal = new bootstrap.Modal(document.getElementById('dettaglioSollecitiModal'));
          myModal.show();
        })
        .catch(error => {
          document.getElementById('contenutoSolleciti').innerHTML = 'Errore nel caricamento dei dati.';
          console.error('Errore:', error);
        });
    }

    </script>
  <div class="modal fade" id="modalSollecito" tabindex="-1" aria-labelledby="modalSollecitoLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-custom-top">
      <div class="modal-content">
        <form action="salva_sollecito.php" method="POST" id="formSollecito">
          <div class="modal-header">
            <h5 class="modal-title" id="modalSollecitoLabel">Nuovo Sollecito</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
          </div>
          <div class="modal-body">
<input type="hidden" id="w1shan" name="w1shan">
<input type="hidden" id="w1an8" name="w1an8">
<input type="hidden" id="w1pddj" name="w1pddj">
<input type="hidden" id="w1dtso" name="w1dtso">

            <div class="mb-3">
              <label for="w1note" class="form-label">Note</label>
              <textarea class="form-control" id="w1note" name="w1note"></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-primary">Salva</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <script>
  function apriFormSollecitoConDati(w1shan, w1an8, w1pddj) {
    document.getElementById('w1shan').value = w1shan;
    document.getElementById('w1an8').value = w1an8;
    document.getElementById('w1pddj').value = w1pddj;

    // Data odierna in formato GG/MM/AA
    const oggi = new Date();
    const gg = String(oggi.getDate()).padStart(2, '0');
    const mm = String(oggi.getMonth() + 1).padStart(2, '0');
    const aa = String(oggi.getFullYear()).slice(-2);
    document.getElementById('w1dtso').value = gg + '/' + mm + '/' + aa;

    const modal = new bootstrap.Modal(document.getElementById('modalSollecito'));
    modal.show();
  }
  function apriIndirizziEmail(SDAN8 , SDSHAN) {
    SDSHAN_GLOBAL = SDSHAN;
    SDAN8_GLOBAL = SDAN8;
    // Rimuovi modale esistente se c'è
    const esistente = document.getElementById('modalEmail');
    if (esistente) {
      esistente.parentNode.removeChild(esistente);
    }

    fetch("indirizzi_email.php?SDSHAN=" + encodeURIComponent(SDSHAN) + "&SDAN8=" + encodeURIComponent(SDAN8))
      .then(response => response.text())
      .then(data => {
        const modalDiv = document.createElement("div");
        modalDiv.setAttribute("data-sdshan", SDSHAN);
        modalDiv.setAttribute("data-sdan8", SDAN8);
        modalDiv.innerHTML = `
          <div class="modal fade" id="modalEmail" tabindex="-1" aria-labelledby="modalEmailLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-custom-top">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title" id="modalEmailLabel">Indirizzi Email</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
                </div>
                <div class="modal-body">` + data + `</div>
              </div>
            </div>
          </div>`;
        document.body.appendChild(modalDiv);
        const modal = new bootstrap.Modal(document.getElementById('modalEmail'));
        modal.show();
      })
      .catch(error => {
        alert("Errore nel caricamento degli indirizzi email.");
        console.error(error);
      });
  }
  function filtraEmail() {
    const soloEmail = document.getElementById("filtroEmail").checked ? '1' : '0';

    fetch("indirizzi_email.php?SDSHAN=" + encodeURIComponent(SDSHAN_GLOBAL) + "&SDAN8=" + encodeURIComponent(SDAN8_GLOBAL) + "&soloemail=" + soloEmail)
      .then(response => response.text())
      .then(data => {
        const modalBody = document.querySelector("#modalEmail .modal-body");
        if (modalBody) {
          modalBody.innerHTML = data;
        }
        // Aggiorna lo stato della checkbox in base al valore attuale di soloEmail
        const filtroCheckbox = document.getElementById("filtroEmail");
        filtroCheckbox.checked = (soloEmail === '1');
      })
      .catch(error => {
        alert("Errore nel caricamento degli indirizzi email.");
        console.error(error);
      });
  }
  </script>
  </body>
</html>
HTML;
        }


     }
		header('Content-Type: text/html; charset=iso-8859-1');

xlLoadWebSmartObject(__FILE__, 'solleciti');
?>