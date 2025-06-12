<?php
session_start();


// Verifica se l'utente è loggato e ha il badge salvato in sessione

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

require_once('/www/php80/htdocs/CRUD/websmart/v13.2/include/WebSmartObject.php');
require_once('/www/php80/htdocs/CRUD/websmart/v13.2/include/xl_functions.php');

class PAUSE extends WebSmartObject
{
    private $currentDeviceId = '';
    /**
     * Renderizza una cella orario con menu a discesa azioni.
     */
    private function renderDropdownMenu($ora, $errStyle , $IdOrin , $DeId , $Tipo , $Senso , $STDATE , $IdGest , $trasmessa)
    {
//      $orahhmm = $ora->format('H:i');
        // Assegna i valori agli array globali per evitare warning
        $GLOBALS['STDATE'] = $STDATE;
        if (empty($ora)) {
            return "<td></td>";
        }

        // Normalizza $ora e $STDATE come stringhe nei formati richiesti
        $oraStr = '';
        $dataStr = '';
        // $ora potrebbe essere già formattato, oppure HTML con tag (es: span), quindi estrai solo l'orario se necessario
        if (!empty($ora) && preg_match('/^\d{2}:\d{2}(:\d{2})?$/', strip_tags($ora))) {
            $oraStr = strip_tags($ora);
            if (strlen($oraStr) === 5) $oraStr .= ':00';
        } else {
            // fallback: tenta di estrarre l'orario da una stringa tipo "HH:MM"
            if (preg_match('/(\d{2}:\d{2})(:\d{2})?/', $ora, $matches)) {
                $oraStr = $matches[0];
                if (strlen($oraStr) === 5) $oraStr .= ':00';
            }
        }
        // $STDATE dovrebbe già essere Y-m-d
        if (!empty($STDATE) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $STDATE)) {
            $dataStr = $STDATE;
        } else {
            // fallback: tenta conversione da altri formati
            $dt = DateTime::createFromFormat('d-m-Y', $STDATE);
            if ($dt) $dataStr = $dt->format('Y-m-d');
        }
        // Escaping degli URL per sicurezza (addslashes + ENT_QUOTES)
        $urlConvertiPausa = htmlspecialchars(addslashes("pause.php?convertiinpausa={$IdOrin}&stdate={$dataStr}&sttime={$oraStr}&idgest={$IdGest}"), ENT_QUOTES);
        $urlConvertiUscita = htmlspecialchars(addslashes("pause.php?convertiinuscita={$IdOrin}&stdate={$dataStr}&sttime={$oraStr}&idgest={$IdGest}"), ENT_QUOTES);
        $urlConvertiEntrata = htmlspecialchars(addslashes("pause.php?convertiinentrata={$IdOrin}&stdate={$dataStr}&sttime={$oraStr}&idgest={$IdGest}"), ENT_QUOTES);
        $urlElimina = htmlspecialchars(addslashes("pause.php?elimina={$IdOrin}&stdate={$dataStr}&sttime={$oraStr}&idgest={$IdGest}"), ENT_QUOTES);
        $urlRichiediSistemazione = htmlspecialchars(addslashes("pause.php?richiedisistemazione={$IdOrin}&stdate={$dataStr}&sttime={$oraStr}&idgest={$IdGest}"), ENT_QUOTES);

        // Descrizione leggibile per il device
        $deviceLabel = match($DeId) {
            '70' => 'Ingresso',
            '71' => 'Magazzino',
            '72' => 'Produzione',
            default => 'Dispositivo sconosciuto'
        };

If (isset($_SESSION['autenticato']) && $_SESSION['autenticato'] === true) {

        $html = <<<HTML
<td {$errStyle} title="ID Origine: {$IdOrin} - {$deviceLabel} - {$Tipo} - {$Senso}" style="position: relative;">
  {$ora}
  <div class="dropdown d-inline">
HTML;

        if (trim($trasmessa ?? '') !== '') {
            $html .= <<<HTML
   <i class="bi bi-slash-circle text-muted" style="font-size: 0.9rem;" title="Timbratura già trasferita" disabled></i>
HTML;
        } else 
        
        {
            $html .= <<<HTML
   <i class="bi bi-pencil text-secondary dropdown-toggle" style="font-size: 0.9rem;" style="cursor: pointer;" data-bs-toggle="dropdown" aria-expanded="false" title="Azioni disponibili"></i>
HTML;
        }
        $html .= <<<HTML
    <ul class="dropdown-menu dropdown-menu-end" style="z-index: 1050;">
HTML;
        // Unifica la gestione delle voci del menu
        if ($Tipo === '0000') {
            // Link "Converti in Pausa" AGGIORNATO secondo istruzioni
            $html .= <<<HTML
      <li>
        <a href="?convertiinpausa=1&idorin={$IdOrin}&idgest={$IdGest}&sttime={$oraStr}&stdate={$dataStr}" class="dropdown-item text-warning">
            <i class="bi bi-pause-circle-fill"></i> Converti in Pausa
        </a>
      </li>
HTML;
            if ($Senso === 'E') {
                $html .= <<<HTML
      <li>
        <a href="?convertiinuscita=1&idorin={$IdOrin}&idgest={$IdGest}&sttime={$oraStr}&stdate={$dataStr}" class="dropdown-item text-primary">
            <i class="bi bi-box-arrow-right"></i> Converti in Uscita
        </a>
      </li>
HTML;
            }
            if ($Senso === 'U') {
                $html .= <<<HTML
      <li>
        <a href="?convertiinentrata=1&idorin={$IdOrin}&idgest={$IdGest}&sttime={$oraStr}&stdate={$dataStr}" class="dropdown-item text-success">
            <i class="bi bi-box-arrow-in-right"></i> Converti in Entrata
        </a>
      </li>
HTML;
            }
        } elseif ($Tipo === '0001') {
            $html .= <<<HTML
      <li>
        <a href="?convertiinuscita=1&idorin={$IdOrin}&idgest={$IdGest}&sttime={$oraStr}&stdate={$dataStr}" class="dropdown-item text-primary">
            <i class="bi bi-box-arrow-right"></i> Converti in Uscita
        </a>
      </li>
      <li>
        <a href="?convertiinentrata=1&idorin={$IdOrin}&idgest={$IdGest}&sttime={$oraStr}&stdate={$dataStr}" class="dropdown-item text-success">
            <i class="bi bi-box-arrow-in-right"></i> Converti in Entrata
        </a>
      </li>
HTML;
        }

        $html .= <<<HTML
      <li>
        <a href="?elimina=1&idorin={$IdOrin}&idgest={$IdGest}&sttime={$oraStr}&stdate={$dataStr}" class="dropdown-item text-danger">
            <i class="bi bi-trash3-fill"></i> Elimina
        </a>
      </li>
      <li>
        <a href="?richiedisistemazione=1&idorin={$IdOrin}&idgest={$IdGest}&sttime={$oraStr}&stdate={$dataStr}" class="dropdown-item text-secondary">
            <i class="bi bi-wrench-adjustable-circle-fill"></i> Richiedi Sistemazione
        </a>
      </li>
    </ul>
  </div>
</td>
HTML; } 

else {
    $oraHHMM = '';
    if (!empty($ora)) {
        $oraPulita = strip_tags($ora);
        if (preg_match('/^\d{2}:\d{2}/', $oraPulita, $matches)) {
            $oraHHMM = $matches[0];
        }
    }
    $html = <<<HTML
<td {$errStyle} title="ID Origine: {$IdOrin} - {$deviceLabel} - {$Tipo} - {$Senso}" style="position: relative;">
  {$oraHHMM}
  <div class="dropdown d-inline">
        <a href="?richiedisistemazione=1&idorin={$IdOrin}&idgest={$IdGest}&sttime={$oraStr}&stdate={$dataStr}" title="Richiedi sistemazione timbratura" style="font-size: 0.9rem;">
          <i class="bi bi-person-check-fill" style="font-size: 0.9rem;"></i> </a>
HTML;
}
        return $html;
    }     

	public function runMain()
	{
		error_reporting(E_ALL);
		ini_set('display_errors', 1);
		date_default_timezone_set('Europe/Rome');

		// Connect to the database
		try {
			// Defaults
			// pf_db2OdbcDsn: DSN=*LOCAL
			// pf_db2OdbcLibraryList: ;DBQ=, <<libraries, space separated, build from files included, add ',' at the beginning to not have a default schema>>
			// pf_db2OdbcOptions: ;NAM=1;TSFT=1 -> setting system naming and timestamp type to IBM standards
			$this->db_connection = new PDO(
				'odbc:' . $this->defaults['pf_db2OdbcDsn'] . $this->defaults['pf_db2OdbcLibraryList'] . $this->defaults['pf_db2OdbcOptions'],
				$this->defaults['pf_db2OdbcUserID'],
				$this->defaults['pf_db2OdbcPassword'],
				$this->defaults['pf_db2PDOOdbcOptions']
			);
		} catch (PDOException $ex) {
			die('Could not connect to database: ' . $ex->getMessage());
		}
        // Gestione azione "converti in pausa"
        if (isset($_GET['convertiinpausa'])) {
            // Assicura che $ora e $STDATE siano definite
            $rawDate = $_GET['stdate'] ?? '';
            $dt = DateTime::createFromFormat('Y-m-d', $rawDate);
            $STDATE = $dt ? $dt->format('Y-m-d') : '';
            $ora = $_GET['sttime'] ?? '';
            $IdOrin = $_GET['idorin'] ?? '';
            $IdGest = $_GET['idgest'] ?? '';
            if (!empty($IdOrin)) {
                $query = "UPDATE BCD_DATIV2.SAVTIM0F 
                          SET STRECO = '0001' , STTYPE = '9'
                          WHERE STORIN = ? and STTRAS=' ' AND STCDDI = ? AND STTIME = ? AND STDATE = ?";
                $stmt = $this->db_connection->prepare($query);
                $stmt->execute([$IdOrin, $IdGest, $ora, $STDATE]);
            }
            header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
            exit;
        }

        // Gestione azione "converti in entrata"
        if (isset($_GET['convertiinentrata'])) {
            $rawDate = $_GET['stdate'] ?? '';
            $dt = DateTime::createFromFormat('Y-m-d', $rawDate);
            $STDATE = $dt ? $dt->format('Y-m-d') : '';
            $ora = $_GET['sttime'] ?? '';
            $IdOrin = $_GET['idorin'] ?? '';
            $IdGest = $_GET['idgest'] ?? '';
            if (!empty($IdOrin)) {
                $query = "UPDATE BCD_DATIV2.SAVTIM0F 
                          SET STRECO = '0000' , STSENS = 'E' , STTYPE = '9'
                          WHERE STORIN = ? and STTRAS=' ' AND STCDDI = ? AND STTIME = ? AND STDATE = ?";
                $stmt = $this->db_connection->prepare($query);
                $stmt->execute([$IdOrin, $IdGest, $ora, $STDATE]);
            }
            header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
            exit;
        }
        // Gestione azione "converti in uscita"
        if (isset($_GET['convertiinuscita'])) {
            $rawDate = $_GET['stdate'] ?? '';
            $dt = DateTime::createFromFormat('Y-m-d', $rawDate);
            $STDATE = $dt ? $dt->format('Y-m-d') : '';
            $ora = $_GET['sttime'] ?? '';
            $IdOrin = $_GET['idorin'] ?? '';
            $IdGest = $_GET['idgest'] ?? '';
            if (!empty($IdOrin)) {
                $query = "UPDATE BCD_DATIV2.SAVTIM0F 
                          SET STRECO = '0000' , STSENS = 'U' , STTYPE = '9'
                          WHERE STORIN = ? and STTRAS=' ' AND STCDDI = ? AND STTIME = ? AND STDATE = ?";
                $stmt = $this->db_connection->prepare($query);
                $stmt->execute([$IdOrin, $IdGest, $ora, $STDATE]);
            }
            header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
            exit;
        }
        //AND STCDDI = ? AND STTIME = ? AND STDATE = ?
        // Gestione azione "elimina"
        if (isset($_GET['elimina'])) {
            $rawDate = $_GET['stdate'] ?? '';
            $dt = DateTime::createFromFormat('Y-m-d', $rawDate);
            $STDATE = $dt ? $dt->format('Y-m-d') : '';
            $ora = $_GET['sttime'] ?? '';    
            $IdOrin = $_GET['idorin'] ?? '';
            $IdGest = $_GET['idgest'] ?? '';
            if (!empty($IdOrin)) {
                $query = "UPDATE BCD_DATIV2.SAVTIM0F 
                          SET STRECO = '0002' , STTYPE = '9'
                           WHERE STORIN = ? and STTRAS=' ' AND STCDDI = ? AND STTIME = ? AND STDATE = ?";
                $stmt = $this->db_connection->prepare($query);
                $stmt->execute([$IdOrin, $IdGest, $ora, $STDATE]);
            }
            header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
            exit;
        }

        // Gestione azione "richiedi sistemazione"
        if (isset($_GET['richiedisistemazione'])) {
            $rawDate = $_GET['stdate'] ?? '';
            $dt = DateTime::createFromFormat('Y-m-d', $rawDate);
            $STDATE = $dt ? $dt->format('Y-m-d') : '';
            $ora = $_GET['sttime'] ?? '';
            $IdOrin = $_GET['richiedisistemazione'];
            $IdGest = $_GET['idgest'] ?? '';
            if (!empty($IdOrin)) {
                $query = "UPDATE BCD_DATIV2.SAVTIM0F 
                          SET STTYPE = '5' 
                          WHERE STORIN = ? and STTRAS=' ' AND STCDDI = ? AND STTIME = ? AND STDATE = ?";
                $stmt = $this->db_connection->prepare($query);
                $stmt->execute([$IdOrin, $IdGest, $ora, $STDATE]);
             /*   echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ccc;'>
QUERY: {$query}
STORIN: {$IdOrin}
STCDDI: {$IdGest}
STTIME: {$ora}
STDATE: {$STDATE}
</pre>";
                exit; */ 
            }
            header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
            exit;
        }



		header('Content-Type: text/html; charset=iso-8859-1');

		// Run the specified task (place additional task calls here)
		switch ($this->pf_task) {
			// Display the main list
			case 'default':
				$this->dspListaPresenti();
				break;

		}
	}

	protected function dspListaPresenti()
	{
		if ($_SERVER["REQUEST_METHOD"] === "POST") {
			$_SESSION["filtDateFrom"] = $_POST["filtDateFrom"] ?? $_SESSION["filtDateFrom"] ?? date("Y-m-d");
      $_SESSION["filtDateTo"] = $_POST["filtDateTo"] ?? $_SESSION["filtDateTo"] ?? date("Y-m-d");
			$_SESSION["filtCognome"] = $_POST["filtCognome"] ?? $_SESSION["filtCognome"] ?? '';
			$_SESSION["filtNome"] = $_POST["filtNome"] ?? $_SESSION["filtNome"] ?? '';
			$_SESSION["filtroTipo"] = $_POST["filtroTipo"] ?? $_SESSION["filtroTipo"] ?? 'presenti';
			$_SESSION["chkError"] = isset($_POST["chkError"]) ? 1 : 0;
			$_SESSION["chkMancanti"] = isset($_POST["chkMancanti"]) ? 1 : 0;
			header("Location: " . $_SERVER["PHP_SELF"]);
			exit;
		}

		$this->printPageHeader();
		$totPresenti = 0;
		$totpausafatta = 0;
		$totinpausa = 0;

		// Data da visualizzare (dalla sessione o data odierna)
		$filtDateFrom = !empty($_SESSION["filtDateFrom"]) ? $_SESSION["filtDateFrom"] : date("Y-m-d");
    $filtDateTo = !empty($_SESSION["filtDateTo"]) ? $_SESSION["filtDateTo"] : date("Y-m-d");
		$filtCognome = strtoupper(htmlspecialchars($_SESSION['filtCognome'] ?? ''));
		$filtNome = strtoupper(htmlspecialchars($_SESSION['filtNome'] ?? ''));

		if (!isset($_SESSION["filtroTipo"])) $_SESSION["filtroTipo"] = 'presenti';
		if (!isset($_SESSION["chkError"])) $_SESSION["chkError"] = 0;

        // Attiva filtro errori se richiesto
        $chkError = $_SESSION["chkError"] ?? 0;

		$query = "SELECT
    COALESCE(D.BDCOGN, '') AS BDCOGN,
    COALESCE(D.BDNOME, '') AS BDNOME,
    COALESCE(D.BDPASS, '') AS BDPASS,
    COALESCE(D.BDAUTH, '') AS BDAUTH,
    COALESCE(D.BDCOGE, '') AS BDCOGE,
    COALESCE(A.STORIN, '') AS STORIN,
    COALESCE(A.STDEID, '') AS STDEID,
    COALESCE(A.STSENS, '') AS STSENS,
    COALESCE(A.STRECO, '') AS STRECO,
    COALESCE(A.STTYPE, '') AS STTYPE,
    COALESCE(A.STTRAS, '') AS STTRAS,
    COALESCE(CHAR(A.STDATE), '') AS STDATE,
    COALESCE(CHAR(A.STTIME), '') AS STTIME
FROM BCD_DATIV2.BDGDIP0F AS D
LEFT JOIN BCD_DATIV2.SAVTIM0F AS A
    ON D.BDCOGE = A.STCDDI AND A.STDATE between '" . $filtDateFrom . "' and  '" . $filtDateTo . "' and
    D.BDNOME LIKE '%" . $filtNome . "%'
    AND D.BDCOGN LIKE '%" . $filtCognome . "%'
ORDER BY 
    D.BDNOME, D.BDCOGN";
		// Forza che anche i record senza timbrature abbiano STTIME valorizzato (anche se vuoto)
		$stmt = $this->db_connection->prepare($query);
		$result = $stmt->execute();
		// Inizializza variabili prima del ciclo
		$presentiInSede = [];
		$datipause = [];
  
		while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			foreach (array_keys($row) as $key) {
				$row[$key] = htmlspecialchars(rtrim($row[$key]));
				$escapedField = xl_fieldEscape($key);
				$$escapedField = $row[$key];
			}
			// Assicurati che STTYPE sia sempre presente
			if (!isset($row['STTYPE'])) {
				$STTYPE = '';
			}

			$presNome = $BDCOGN;
			$presCogn = $BDNOME;
 //     $AUTH = $BDAUTH;
   //   $PASS = $BDPASS;
			$IdGest = $BDCOGE;
			$DeId = $STDEID;
			$Senso = $STSENS;
			$Tipo = $STRECO;
      $Trasmessa = $STTRAS;
			$presDtIn = $this->cvtDateFromDb(str_replace("-", "", $STDATE));
			$presDtInTime = $this->cvtTimeFromDb(str_replace("-", "", $STTIME));
			$chiave = $presNome . '|' . $presCogn . '|' . $presDtIn;

			try {
			    if (!empty($STTIME)) {
			        $dt = new DateTime($STTIME);
			        $ora = $dt->format('H:i:s');
			    } else {
			        $ora = ''; // valore vuoto per record senza timbrature
			    }

			    if ($Tipo === '0000') {
			        if (!isset($presentiInSede[$chiave])) {
			            $presentiInSede[$chiave] = [
			                'nome' => $presNome,
			                'cognome' => $presCogn,
			                'IdGest' => $IdGest,
                      'data' => [],
			                'orarilavoro' => [],
			                'idorin' => [],
			                'DeId' => [],
			                'senso' => [],
			                'tipo' => [],
                      'trasmessa' => [],
			                'dasistemare' => []
			            ];
			        }
			        if (!empty($ora)) {
			            $presentiInSede[$chiave]['orarilavoro'][] = $ora;
                  $presentiInSede[$chiave]['data'][] = $presDtIn;
			            $presentiInSede[$chiave]['idorin'][] = $STORIN;
			            $presentiInSede[$chiave]['DeId'][] = $DeId;
			            $presentiInSede[$chiave]['senso'][] = $Senso;
			            $presentiInSede[$chiave]['tipo'][] = $Tipo;
                  $presentiInSede[$chiave]['trasmessa'][] = $Trasmessa;
			            $presentiInSede[$chiave]['dasistemare'][] = $STTYPE;
			        }
			    } elseif ($Tipo === '0001') {
			        if (!isset($datipause[$chiave])) {
			            $datipause[$chiave] = [
			                'nome' => $presNome,
			                'cognome' => $presCogn,
			                'IdGest' => $IdGest,
                      'data' => [],
			                'oraripause' => [],
			                'idorin' => [],
			                'DeId' => [],
			                'senso' => [],
			                'tipo' => [],
                      'trasmessa' => [],
			                'dasistemare' => []
			            ];
			        }
			        if (!empty($ora)) {
			            $datipause[$chiave]['oraripause'][] = $ora;
                  $datipause[$chiave]['data'][] = $presDtIn;
			            $datipause[$chiave]['idorin'][] = $STORIN;
			            $datipause[$chiave]['DeId'][] = $DeId;
			            $datipause[$chiave]['senso'][] = $Senso;
			            $datipause[$chiave]['tipo'][] = $Tipo;
                  $datipause[$chiave]['trasmessa'][] = $Trasmessa;
			            $datipause[$chiave]['dasistemare'][] = $STTYPE;
			        }
			    } elseif (empty($Tipo)) {
			        // Nessuna timbratura ma presente in anagrafica
			        if (!isset($presentiInSede[$chiave])) {
			            $presentiInSede[$chiave] = [
			                'nome' => $presNome,
			                'cognome' => $presCogn,
			                'IdGest' => $IdGest,
                      'data' => [],
			                'orarilavoro' => [],
			                'oraripause' => [],
			                'idorin' => [],
			                'DeId' => [],
			                'senso' => [],
			                'tipo' => [],
                      'trasmessa' => [],
			                'dasistemare' => []
			            ];
			        }
			    }
			} catch (Exception $e) {
			    // Anche in caso di errore, includi il dipendente con dati vuoti
			    if (!isset($presentiInSede[$chiave])) {
			        $presentiInSede[$chiave] = [
			            'nome' => $presNome,
			            'cognome' => $presCogn,
                  'data' => [],
			            'IdGest' => $IdGest,
			            'orarilavoro' => [],
			            'oraripause' => [],
			            'idorin' => [],
			            'DeId' => [],
			            'senso' => [],
			            'tipo' => [],
                                        'trasmessa' => [],

                   'dasistemare' => []
			        ];
			    }
			}
			if ($Tipo === '0000') {
				$this->ordinaSegmento($presentiInSede, $chiave, 'orarilavoro');
				$this->currentDeviceId = $DeId;
				$totPresenti++;
			} elseif ($Tipo === '0001') {
				$this->ordinaSegmento($datipause, $chiave, 'oraripause');
			}
		}

		// Inizializza $datipause se non esiste
		if (!isset($datipause)) {
		    $datipause = [];
		}
		// Fusione completa: anche chi ha solo pause (0001)
		$chiaviTotali = array_unique(array_merge(array_keys($presentiInSede), array_keys($datipause)));

		foreach ($chiaviTotali as $chiave) {
		    if (!isset($presentiInSede[$chiave])) {
		        $presentiInSede[$chiave] = $datipause[$chiave];
		    } elseif (isset($datipause[$chiave])) {
		        $presentiInSede[$chiave]['oraripause'] = $datipause[$chiave]['oraripause'];
            $presentiInSede[$chiave]['data'] = array_merge($presentiInSede[$chiave]['data'], $datipause[$chiave]['data']);
		        $presentiInSede[$chiave]['idorin'] = array_merge($presentiInSede[$chiave]['idorin'], $datipause[$chiave]['idorin']);
		        $presentiInSede[$chiave]['DeId'] = array_merge($presentiInSede[$chiave]['DeId'], $datipause[$chiave]['DeId']);
		        $presentiInSede[$chiave]['senso'] = array_merge($presentiInSede[$chiave]['senso'], $datipause[$chiave]['senso']);
		        $presentiInSede[$chiave]['tipo'] = array_merge($presentiInSede[$chiave]['tipo'], $datipause[$chiave]['tipo']);
            $presentiInSede[$chiave]['trasmessa'] = array_merge($presentiInSede[$chiave]['trasmessa'], $datipause[$chiave]['trasmessa']);
		        $presentiInSede[$chiave]['dasistemare'] = array_merge($presentiInSede[$chiave]['dasistemare'], $datipause[$chiave]['dasistemare']);
		    }
		}

        // Filtro errori pause (se attivo)
        if ($chkError) {
            foreach ($presentiInSede as $chiave => $dati) {
                $oraripause = $dati['oraripause'] ?? [];
                $errore = false;
                for ($i = 0; $i < 6; $i += 2) {
                    $inizio = $oraripause[$i] ?? '';
                    $fine = $oraripause[$i + 1] ?? '';
                    if (!empty($inizio)) {
                        try {
                            $dt1 = new DateTime($inizio);
                            $dt2 = !empty($fine) ? new DateTime($fine) : new DateTime();
                            $durata = abs($dt2->getTimestamp() - $dt1->getTimestamp()) / 60;
                            if ($durata > 11) {
                                $errore = true;
                                break;
                            }
                        } catch (Exception $e) {}
                    }
                }
                if (!$errore) {
                    unset($presentiInSede[$chiave]);
                }
            }
        }

        // FILTRI RADIO: applica filtro in base a filtroTipo
        if ($_SESSION["filtroTipo"] === 'inpausa') {
            // Filtro: mostra solo chi è attualmente in pausa (almeno una pausa iniziata e non conclusa)
            foreach ($presentiInSede as $chiave => $dati) {
                $oraripause = $dati['oraripause'] ?? [];
                $inPausa = false;
                for ($i = 0; $i < count($oraripause); $i += 2) {
                    if (!empty($oraripause[$i]) && empty($oraripause[$i + 1])) {
                        $inPausa = true;
                        break;
                    }
                }
                if (!$inPausa) {
                    unset($presentiInSede[$chiave]);
                }
            }
        } elseif ($_SESSION["filtroTipo"] === 'pausafatta') {
            // Filtro: mostra solo chi ha effettuato almeno una pausa completa (inizio e fine)
            foreach ($presentiInSede as $chiave => $dati) {
                $oraripause = $dati['oraripause'] ?? [];
                $pausaFatta = false;
                for ($i = 0; $i < count($oraripause); $i += 2) {
                    if (!empty($oraripause[$i]) && !empty($oraripause[$i + 1])) {
                        $pausaFatta = true;
                        break;
                    }
                }
                if (!$pausaFatta) {
                    unset($presentiInSede[$chiave]);
                }
            }
        } elseif ($_SESSION["filtroTipo"] === 'nopausa') {
            // Filtro: mostra solo chi non ha effettuato alcuna pausa
            foreach ($presentiInSede as $chiave => $dati) {
                $oraripause = $dati['oraripause'] ?? [];
                $nessunaPausa = true;
                for ($i = 0; $i < count($oraripause); $i += 2) {
                    if (!empty($oraripause[$i])) {
                        $nessunaPausa = false;
                        break;
                    }
                }
                if (!$nessunaPausa) {
                    unset($presentiInSede[$chiave]);
                }
            }
        } elseif ($_SESSION["filtroTipo"] === 'presenti') {
            // Filtro: mostra solo chi ha almeno una timbratura di lavoro
            foreach ($presentiInSede as $chiave => $dati) {
                $orarilavoro = $dati['orarilavoro'] ?? [];
                $haTimbrature = false;
                foreach ($orarilavoro as $orario) {
                    if (!empty($orario)) {
                        $haTimbrature = true;
                        break;
                    }
                }
                if (!$haTimbrature) {
                    unset($presentiInSede[$chiave]);
                }
            }
            // Filtro timbrature mancanti: solo se manca l'orario 2 (fine primo turno) dopo le 12:30
            // o manca l'orario 4 (fine secondo turno) dopo le 17:00
            if (isset($_SESSION["chkMancanti"]) && $_SESSION["chkMancanti"]) {
                foreach ($presentiInSede as $chiave => $dati) {
                    $orarilavoro = $dati['orarilavoro'] ?? [];
                    $mancante = false;

                    // Orario 2 (index 1) richiede Orario 1 (index 0) presente
                    if (!empty($orarilavoro[0]) && empty($orarilavoro[1])) {
                        $now = new DateTime();
                        $limite = new DateTime($now->format('Y-m-d') . ' 12:30:00');
                        if ($now >= $limite) {
                            $mancante = true;
                        }
                    }

                    // Orario 4 (index 3) richiede Orario 3 (index 2) presente
                    if (!empty($orarilavoro[2]) && empty($orarilavoro[3])) {
                        $now = new DateTime();
                        $limite = new DateTime($now->format('Y-m-d') . ' 17:00:00');
                        if ($now >= $limite) {
                            $mancante = true;
                        }
                    }

                    if (!$mancante) {
                        unset($presentiInSede[$chiave]);
                    }
                }
            }
        }

        // --- AGGIUNTA: imposta presentiDaVisualizzare ---
        $presentiDaVisualizzare = $presentiInSede;

        // --- SOSTITUZIONE BLOCCO ---
        // Mostra sempre i presenti filtrati dal radio (tutti/inpausa/pausafatta)
        $chiaviFiltrate = array_keys($presentiDaVisualizzare);
        if (!empty($chiaviFiltrate)) {
            $this->writeSegment("hpresenti", array_merge(get_object_vars($this), ['presentiInSede' => $presentiDaVisualizzare, 'chiaviTotali' => $chiaviFiltrate]));
            $this->writeSegment("totpresenti", array_merge(get_object_vars($this), ['presentiInSede' => $presentiDaVisualizzare, 'chiaviTotali' => $chiaviFiltrate]));
        } else {
            $this->writeSegment("nessunpresente", array_merge(get_object_vars($this), ['presentiInSede' => [], 'chiaviTotali' => []]));
        }
        $this->printPageFooter();

}
    private function ordinaSegmento(&$array, $chiave, $campoOrari) {
        if (!isset($array[$chiave][$campoOrari])) return;

        $combined = [];
        foreach ($array[$chiave][$campoOrari] as $i => $time) {
            $combined[] = [
                'time' => $time,
                'idorin' => $array[$chiave]['idorin'][$i] ?? '',
                'data' => $array[$chiave]['data'][$i] ?? '',
                'DeId' => $array[$chiave]['DeId'][$i] ?? '',
                'senso' => $array[$chiave]['senso'][$i] ?? '',
                'tipo' => $array[$chiave]['tipo'][$i] ?? '',
                'trasmessa' => $array[$chiave]['trasmessa'][$i] ?? ''
            ];
        }

        usort($combined, fn($a, $b) => strcmp($a['time'], $b['time']));

        $array[$chiave][$campoOrari] = array_column($combined, 'time');
        $array[$chiave]['idorin'] = array_column($combined, 'idorin');
        $array[$chiave]['data'] = array_column($combined, 'data');
        $array[$chiave]['DeId'] = array_column($combined, 'DeId');
        $array[$chiave]['senso'] = array_column($combined, 'senso');
        $array[$chiave]['tipo'] = array_column($combined, 'tipo');
        $array[$chiave]['trasmessa'] = array_column($combined, 'trasmessa');
    }

	protected function cvtDateFromDb($date)
	{
		return substr($date, 6, 2) . "-" . substr($date, 4, 2) . "-" . substr($date, 0, 4);
	}

	protected function cvtTimeFromDb($time)
	{
		$time = str_pad($time, 6, "0", STR_PAD_LEFT);
		return substr($time, 0, 2) . ":" . substr($time, 2, 2);
	}

    private function getErrorePausa(array $oraripause, bool $inPausaAttuale = false)
    {
        $errpausa1lunga = '';
        $errpausa2lunga = '';
        $errtroppepausa = '';
        $errtotPauseMinuti = '';
        $errinpausaattuale = '';
        $errpausagen = '';
        $totPause = 0;
        $orapausa1 = $oraripause[0] ?? '';
        $orapausa2 = $oraripause[1] ?? '';
        $orapausa3 = $oraripause[2] ?? '';
        $orapausa4 = $oraripause[3] ?? '';
        $orapausa5 = $oraripause[4] ?? '';
        $orapausa6 = $oraripause[5] ?? '';
        $durataPausa1 = ($oraripause[1] - $oraripause[0]) ?? 0;
        try {
            $now = new DateTime();

            if (!empty($orapausa1) && !empty($orapausa2)) {
                $dt1 = new DateTime($orapausa1);
                $dt2 = new DateTime($orapausa2);
                $intervallo1 = abs($dt2->getTimestamp() - $dt1->getTimestamp());
                $totPause += $intervallo1;
                if ($intervallo1 > 660) $errpausa1lunga = 'style="background-color: red;"';
            } elseif (!empty($orapausa1)) {
                $dt1 = new DateTime($orapausa1);
                $totPause += abs($now->getTimestamp() - $dt1->getTimestamp());
                $inPausaAttuale = true;
                if ($totPause > 660) $errpausa1lunga = 'style="background-color: red;"';
            }

            if (!empty($orapausa3) && !empty($orapausa4)) {
                $dt3 = new DateTime($orapausa3);
                $dt4 = new DateTime($orapausa4);
                $intervallo2 = abs($dt4->getTimestamp() - $dt3->getTimestamp());
                $totPause += $intervallo2;
                if ($intervallo2 > 660) $errpausa2lunga = 'style="background-color: red;"';
            } elseif (!empty($orapausa3)) {
                $dt3 = new DateTime($orapausa3);
                $durataPausa2 = 0;
                $durataPausa2 += abs($now->getTimestamp() - $dt3->getTimestamp());
                $totPause += abs($now->getTimestamp() - $dt3->getTimestamp());
                $inPausaAttuale = true;
                if ($durataPausa2 > 660) $errpausa2lunga = 'style="background-color: red;"';
            }

            if (!empty($orapausa5)) {
                if (!empty($orapausa6)) {
                    $dt5 = new DateTime($orapausa5);
                    $dt6 = new DateTime($orapausa6);
                    $intervallo3 = abs($dt6->getTimestamp() - $dt5->getTimestamp());
                    $totPause += $intervallo3;
                } else {
                    $dt5 = new DateTime($orapausa5);
                    $totPause += abs($now->getTimestamp() - $dt5->getTimestamp());
                    $inPausaAttuale = true;

                }
                $errtroppepausa = 'style="background-color: orange;"';
            }

            $totPauseMinuti = floor($totPause / 60);
            if ($totPauseMinuti > 20) $errtotPauseMinuti = 'style="background-color: red;"';
            if ($inPausaAttuale) $errinpausaattuale = 'style="background-color: yellow;"';

            if (
                !empty($errpausa1lunga) ||
                !empty($errpausa2lunga) ||
                !empty($errtroppepausa) ||
                !empty($errtotPauseMinuti)
            ) {
                $errpausagen = 'style="background-color: green;"';
            }

            return [
                'errpausa1lunga' => $errpausa1lunga,
                'errpausa2lunga' => $errpausa2lunga,
                'errtroppepausa' => $errtroppepausa,
                'errtotPauseMinuti' => $errtotPauseMinuti,
                'errinpausaattuale' => $errinpausaattuale,
                'errpausagen' => $errpausagen,
                'inPausaAttuale' => $inPausaAttuale,
                'totPauseMinuti' => $totPauseMinuti,
                'totPauseHHMM' => sprintf('%dh %02dm', floor($totPauseMinuti / 60), $totPauseMinuti % 60)
            ];
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Controlla se c'è una timbratura di entrata lavoro senza uscita dopo le 12:30.
     * @param array $orarilavoro Array di orari di lavoro (max 6: inizio1, fine1, inizio2, fine2, inizio3, fine3)
     * @return array Array con eventuali errori di lavoro
     */
    /**
     * Verifica se una stringa rappresenta un orario valido nel formato HH:MM:SS.
     */


    /**
     * Calcola la durata di ciascuna delle 3 possibili pause singole (in minuti).
     * @param array $oraripause Array di orari delle pause (max 6: inizio1, fine1, inizio2, fine2, inizio3, fine3)
     * @return array Array di 3 stringhe (es. "10 min") o '' se non calcolabile
     */
    private function calcolaDuratePauseSingole(array $oraripause): array {
        $durate = [];
        for ($i = 0; $i < 6; $i += 2) {
            $inizio = $oraripause[$i] ?? '';
            $fine = $oraripause[$i + 1] ?? '';
            if (!empty($inizio) && !empty($fine)) {
                try {
                    $dt1 = new DateTime($inizio);
                    $dt2 = new DateTime($fine);
                    $secondi = abs($dt2->getTimestamp() - $dt1->getTimestamp());
                    $minuti = floor($secondi / 60);
                    $durate[] = "{$minuti} min";
                } catch (Exception $e) {
                    $durate[] = '';
                }
            } else {
                try {
                    $dt1 = new DateTime($inizio);
                    $dt2 = new DateTime(); // ora attuale
                    $secondi = abs($dt2->getTimestamp() - $dt1->getTimestamp());
                    $minuti = floor($secondi / 60);
                    $durate[] = "{$minuti} min";
                } catch (Exception $e) {
                    $durate[] = '';
                }
            }
        }
        return $durate;
    }

    /**
     * Calcola la durata di ciascuna delle 3 possibili fasce di lavoro singole (in formato HH:MM).
     * @param array $orarilavoro Array di orari di lavoro (max 6: inizio1, fine1, inizio2, fine2, inizio3, fine3)
     * @return array Array di 3 stringhe (es. "01:30") o '' se non calcolabile
     */
    private function calcolaDurateLavoroSingole(array $orarilavoro): array {
        $durate = [];
        for ($i = 0; $i < 6; $i += 2) {
            $inizio = $orarilavoro[$i] ?? '';
            $fine = $orarilavoro[$i + 1] ?? '';
            if (!empty($inizio) && !empty($fine)) {
                try {
                    $dt1 = new DateTime($inizio);
                    $dt2 = new DateTime($fine);
                    $secondi = abs($dt2->getTimestamp() - $dt1->getTimestamp());
                    $durate[] = gmdate('H:i', $secondi);
                } catch (Exception $e) {
                    $durate[] = '';
                }
            } else {
                try {
                    $dt1 = new DateTime($inizio);
                    $dt2 = new DateTime(); // ora attuale
                    $secondi = abs($dt2->getTimestamp() - $dt1->getTimestamp());
                    $durate[] = gmdate('H:i', $secondi);
                } catch (Exception $e) {
                    $durate[] = '';
                }
            }
        }
        return $durate;
    }

	protected function printPageHeader()
	{
		$filtNome = strtoupper($_SESSION['filtNome'] ?? '');
		$filtCognome = strtoupper($_SESSION['filtCognome'] ?? '');
		$filtDateFrom = $_SESSION['filtDateFrom'] ?? date('Y-m-d');
    $filtDateTo = $_SESSION['filtDaTeto'] ?? date('Y-m-d');
		$chkError = isset($_SESSION['chkError']) && $_SESSION['chkError'] ? 'checked' : '';
		$chkErrorBtnClass = isset($_SESSION['chkError']) && $_SESSION['chkError'] ? 'btn-danger' : 'btn-outline-danger';
		$chkMancanti = isset($_SESSION['chkMancanti']) && $_SESSION['chkMancanti'] ? 'checked' : '';
    $chkMancantiBtnClass = !empty($_SESSION['chkMancanti']) ? 'btn-danger active' : 'btn-outline-danger';
		$filtroTipo = $_SESSION['filtroTipo'] ?? 'presenti';
		// Dynamic classes for radio buttons
		$btnInPausaClass = ($filtroTipo === 'inpausa') ? 'btn-primary' : 'btn-outline-primary';
		$btnPausaFattaClass = ($filtroTipo === 'pausafatta') ? 'btn-primary' : 'btn-outline-primary';
		$btnPresentiClass = ($filtroTipo === 'presenti') ? 'btn-primary' : 'btn-outline-primary';
		$btnNoPausaClass = ($filtroTipo === 'nopausa') ? 'btn-primary' : 'btn-outline-primary';
		$btnTuttiClass = ($filtroTipo === 'tutti') ? 'btn-primary' : 'btn-outline-primary';


		echo <<<SEGDATA
	<!DOCTYPE html>
	<html>
	  <head>

	  <meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>Pause</title>
	

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.3/themes/base/jquery-ui.css">
<link rel="stylesheet" href="/www/php80/htdocs/CRUD/websmart/v13.2/Responsive/css/screen.css">
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
    		<div id="outer-content">
<div id="page-content" class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mt-0 mb-1 px-2">

    <div>
      <button id="printButton" type="button" onclick="window.print();" class="btn btn-outline-secondary">
          <i class="bi bi-printer"></i>
      </button>
    </div>

   
    <img src="include/logo.jpg" alt="Logo" style="max-height: 60px;">

    


    
    <div class="dropdown">
   <button class="btn btn-light dropdown-toggle" type="button" id="menuDropdown" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="bi bi-three-dots-vertical" style="font-size: 1.5rem;"></i>
      </button>
      <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="menuDropdown">
        <li>
          <a class="dropdown-item" href="#" onclick="window.open('https://jde.rgpballs.com/HR/gestdipe.php', '_blank'); return false;">
            <i class="bi bi-person-lines-fill me-2"></i>Gestione Dipendente
          </a>
        </li>
        <li>
          <a class="dropdown-item" href="#" onclick="openPopupTimbratura(''); return false;">
            <i class="bi bi-plus-circle me-3"></i>Inserisci Timbratura
          </a>
        </li>
        <li>
          <a class="dropdown-item" href="#" onclick="window.open('https://jde.rgpballs.com/timbrature/transfer.php', '_blank'); return false;">
            <i class="bi bi-person-lines-fill me-2"></i>Invia Timbrature a Commercialista
          </a>
        </li>
      </ul>
    </div>
</td>   
  </div>
      </div>
<div class="clearfix"></div>
<div id="contents">
  <div class="row mb-1">
    <div class="col-12">
      <form method="POST" id="filter-form" class="mb-0">
        <div class="row align-items-center g-0 mb-3">
          <!-- Colonna 1: Radio Button -->
          <div class="col-md-4">
            <div class="btn-group flex-wrap gap-2" role="group" aria-label="Toggle segmenti">
              <input type="radio" class="btn-check" name="filtroTipo" id="filtroInPausa" value="inpausa" ($filtroTipo === 'inpausa') ? 'checked' : ''  onchange="this.form.submit();">
              <label class="btn {$btnInPausaClass} btn-sm d-inline-flex align-items-center gap-2 px-2 py-1" for="filtroInPausa">
                <i class="bi bi-pause-circle"></i> In Pausa
              </label>

              <input type="radio" class="btn-check" name="filtroTipo" id="filtroPausaFatta" value="pausafatta" ($filtroTipo === 'pausafatta') ? 'checked' : ''  onchange="this.form.submit();">
              <label class="btn {$btnPausaFattaClass} btn-sm d-inline-flex align-items-center gap-2 px-2 py-1" for="filtroPausaFatta">
                <i class="bi bi-check-circle"></i> Pause Fatte
              </label>

              <input type="radio" class="btn-check" name="filtroTipo" id="filtroPresenti" value="presenti" ($filtroTipo === 'presenti') ? 'checked' : '' onchange="this.form.submit();">
              <label class="btn {$btnPresentiClass} btn-sm d-inline-flex align-items-center gap-2 px-2 py-1" for="filtroPresenti">
                <i class="bi bi-person-fill-check"></i> Presenti
              </label>

              <input type="radio" class="btn-check" name="filtroTipo" id="filtroNoPausa" value="nopausa" ($filtroTipo === 'nopausa') ? 'checked' : '' onchange="this.form.submit();">
              <label class="btn {$btnNoPausaClass} btn-sm d-inline-flex align-items-center gap-2 px-2 py-1" for="filtroNoPausa">
                <i class="bi bi-x-circle"></i> Nessuna Pausa
              </label>

              <input type="radio" class="btn-check" name="filtroTipo" id="filtroTutti" value="tutti" ($filtroTipo === 'tutti') ? 'checked' : '' onchange="this.form.submit();">
              <label class="btn {$btnTuttiClass} btn-sm d-inline-flex align-items-center gap-2 px-2 py-1" for="filtroTutti">
                <i class="bi bi-list-ul"></i> Tutti
              </label>

              <input type="checkbox" class="btn-check" id="chkError" name="chkError" value="1" {$chkError} autocomplete="off" onchange="this.form.submit();">
              <label class="btn {$chkErrorBtnClass} btn-sm d-inline-flex align-items-center gap-2 px-2 py-1" for="chkError">
                <i class="bi bi-exclamation-triangle-fill"></i> Errori Pause
              </label>
SEGDATA;
If (isset($_SESSION['autenticato']) && $_SESSION['autenticato'] === true) {
  $utente = $_SESSION['utente'] ?? 'Utente';
    echo <<<SEGDATA
      <input type="checkbox" class="btn-check" id="chkMancanti" name="chkMancanti" value="1" {$chkMancanti} autocomplete="off" onchange="this.form.submit();">
              <label class="btn {$chkMancantiBtnClass} btn-sm d-inline-flex align-items-center gap-2 px-2 py-1 ms-2" for="chkMancanti">
                <i class="bi bi-plus-circle-fill"></i> Errori Entrata/Uscita 
              </label>
              </div>
          </div>
          <!-- Colonna 2: Totali -->
          <div class="col-md-4 d-flex justify-content-center">
            <div class="bg-light border rounded shadow-sm px-3 py-1 text-primary fw-bold m-0" style="font-size: 1.20rem;">
              <div class="d-flex flex-row gap-2">

               <div><a>Benvenuto {$utente}</a><br>
                             </div>
            <div class="bg-light border rounded shadow-sm px-1 py-1 text-secondary" style="font-size: 0.7rem;">

               <a href="?logout=1" class="text-decoration-none fw-bold">Logout</a>
              </div>

              </div>
            </div>
          </div>

SEGDATA;
} else {
  echo <<<SEGDATA

              </div>
          </div>
<form method="POST">
    <div class="col-md-4 d-flex justify-content-center">
            <div class="bg-light border rounded shadow-sm px-2 py-1 text-primary fw-bold m-0" style="font-size: 0.9rem;">
              <div class="d-flex flex-row gap-2">
      <input type="text" name="utente" placeholder="Utente" class="form-control" required>
    </div>
      <input type="password" name="password" placeholder="Password" class="form-control" required>
  <div class="row justify-content-center">
    <div class="col-md-4 text-center">
      <button type="submit" class="btn btn-primary">Accedi</button>
    </div>
  </div>
    </div>
    </div>
</form>
      
SEGDATA;
}
    echo <<<SEGDATA

          <!-- Colonna 3: Filtri e bottoni -->
          <div class="col-md-4 d-flex align-items-end">
            <div class="row w-100 g-0 align-items-end" style="line-height: 0;">
              <div class="col-md-4" style="max-width: 150px;">
                <input type="date" id="filtDateFrom" name="filtDateFrom" class="form-control" style="width: 100%;" value="{$filtDateFrom}" onchange="this.form.submit();" placeholder="Data da">
              </div>
              <div class="col-md-4 ps-1" style="max-width: 150px;">
                <input type="date" id="filtDateTo" name="filtDateTo" class="form-control" style="width: 100%;" value="{$filtDateTo}" onchange="this.form.submit();" placeholder="Data a">
              </div>
              <div class="col-md-4 ps-1" style="max-width: 150px;">
                <input type="text" id="filtNome" name="filtNome" class="form-control text-uppercase" placeholder="Cognome" value="{$filtNome}" onchange="this.form.submit();">
              </div>
              <div class="col-md-4 ps-1" style="max-width: 150px;">
                <input type="text" id="filtCognome" name="filtCognome" placeholder="Nome" class="form-control text-uppercase"  value="{$filtCognome}" onchange="this.form.submit();">
              </div>
              <div class="col-md-4 ps-1" style="max-width: 150px;">
                <label class="form-label invisible">Azioni</label>
                <div class="d-flex gap-2 mt-auto">
                  <button type="submit" class="btn btn-primary w-50">Filtra</button>
                  <button type="button" class="btn btn-success w-50" onclick="resetForm()">Reset</button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </form>
				<div class="table-responsive">
					<table class="table table-striped table-bordered">
						<thead> 



SEGDATA;
		return;
	}
	protected function printPageFooter()
	{
		echo <<<SEGDATA
		</tbody>
		</table>
		</div> <!-- table-responsive -->
		<!-- Spazio extra fondo pagina per evitare taglio dropdown -->
		<div style="padding-bottom: 150px;"></div>
		</div> <!-- contents -->
		</div> <!-- page-content -->
		</div> <!-- outer-content -->
		</body>
		</html>
		SEGDATA;
		return;
	}

	function writeSegment($xlSegmentToWrite, $segmentVars = array())
	{
		foreach ($segmentVars as $arraykey => $arrayvalue) {
			${$arraykey} = $arrayvalue;
		}
        // Assicura che $filtDate sia valorizzato per questo segmento
        $filtDateFrom = $_SESSION['filtDateFrom'] ?? date('Y-m-d');		// Make sure it's case insensitive
        $filtDateTo = $_SESSION['filtDateTo'] ?? date('Y-m-d');
	$xlSegmentToWrite = strtolower($xlSegmentToWrite);
		if ($xlSegmentToWrite == "hpresenti") {

			echo <<<SEGDTA
				<thead>
						<tr style="background-color:#92a2a8;color:white;">
 				<th></th>

</tr>
			<tr>
SEGDTA;

if (isset($_SESSION['autenticato']) && $_SESSION['autenticato'] === true) {

		echo '<th style="width: 50px;">Dipendenti: </th>';

}
			echo <<<SEGDTA

      <th class="text-start">Cognome Nome</th>
				<th>Orario 1</th>
				<th>Orario 2</th>
				<th>Orario 3</th>
				<th>Orario 4</th>
				<th>Orario 5</th>
				<th>Orario 6</th>
        <th>Totale 1</th>
      	<th>Totale 2</th>
    		<th>Totale 3</th>
              
			</tr>
		</thead>
		<tbody>
SEGDTA;
			return;
		}

        if ($xlSegmentToWrite == "totpresenti") {
            // Fallback per evitare warning su variabili non definite
            $totPresenti = $totPresenti ?? 0;
            $totpausafatta = $totpausafatta ?? 0;
            $totinpausa = $totinpausa ?? 0;
            if (!empty($chiaviTotali)) {
                foreach ($chiaviTotali as $chiave) {
                    if (!isset($presentiInSede[$chiave])) {
                        continue;
                    }
                    $presente = $presentiInSede[$chiave];
                    $presNome = htmlspecialchars($presente['nome']);
                    $presCogn = htmlspecialchars($presente['cognome']);
                    $IdGest = htmlspecialchars($presente['IdGest']);
                    $presDtIn = $presente['data'][0] ?? '';
                    $oraripause = array_pad($presente['oraripause'] ?? [], 6, '');
                    $orarilavoro = array_pad($presente['orarilavoro'] ?? [], 6, '');

                    $countLavoro = count($presente['orarilavoro'] ?? []);
                    $countPausa = count($presente['oraripause'] ?? []);

                    $IdOrinLavoro = array_pad(array_slice($presente['idorin'], 0, $countLavoro), 6, '');
                    $IdOrinPausa  = array_pad(array_slice($presente['idorin'], $countLavoro, $countPausa), 6, '');

                    $DeIdLavoro = array_pad(array_slice($presente['DeId'], 0, $countLavoro), 6, '');
                    $DeIdPausa  = array_pad(array_slice($presente['DeId'], $countLavoro, $countPausa), 6, '');

                    $SensoLavoro = array_pad(array_slice($presente['senso'], 0, $countLavoro), 6, '');
                    $SensoPausa  = array_pad(array_slice($presente['senso'], $countLavoro, $countPausa), 6, '');

                    $TipoLavoro = array_pad(array_slice($presente['tipo'], 0, $countLavoro), 6, '');
                    $TipoPausa  = array_pad(array_slice($presente['tipo'], $countLavoro, $countPausa), 6, '');

                    $DaSistemareLavoro = array_pad(array_slice($presente['dasistemare'], 0, $countLavoro), 6, '');
                    $DaSistemarePausa  = array_pad(array_slice($presente['dasistemare'], $countLavoro, $countPausa), 6, '');

                    $trasmessaLavoro = array_pad(array_slice($presente['trasmessa'], 0, $countLavoro), 6, '');
                    $trasmessaPausa  = array_pad(array_slice($presente['trasmessa'], $countLavoro, $countPausa), 6, '');



                    // Recupera STTYPE per questa persona (se presente), altrimenti stringa vuota
                    $STTYPE = '';
                    if (isset($presente['STTYPE'])) {
                        $STTYPE = $presente['STTYPE'];
                    } elseif (isset($presente['sttype'])) {
                        $STTYPE = $presente['sttype'];
                    } elseif (isset($presente['Sttype'])) {
                        $STTYPE = $presente['Sttype'];
                    }

                    // Calcola le durate delle pause singole
                    $durateSingole = $this->calcolaDuratePauseSingole($oraripause);
                    // Determina stili per la durata della prima pausa
                    $oraPausa1 = $oraripause[0] ?? '';
                    $oraPausa2 = $oraripause[1] ?? '';
                    $styleDurata1 = '';
                    if (!empty($oraPausa1)) {
                        try {
                            $dt1 = new DateTime($oraPausa1);
                            $dt2 = !empty($oraPausa2) ? new DateTime($oraPausa2) : new DateTime(); // se in corso, confronta con ora attuale
                            $secondi = abs($dt2->getTimestamp() - $dt1->getTimestamp());
                            $minuti = floor($secondi / 60);
                            if (empty($oraPausa2)) {
                                if ($minuti > 12) {
                                    $styleDurata1 = 'style="background-color: red; font-weight: bold; animation: lampeggia 1s infinite;"';
                                } else {
                                    $styleDurata1 = 'style="background-color: #fff3cd; font-weight: bold;"';
                                }
                            } elseif ($minuti <= 10) {
                                $styleDurata1 = 'style="color: green; font-weight: bold;"';
                            } elseif ($minuti == 11) {
                                $styleDurata1 = 'style="color: orange; font-weight: bold;"';
                            } elseif ($minuti >= 12) {
                                $styleDurata1 = 'style="color: red; font-weight: bold;"';
                            }
                        } catch (Exception $e) {}
                    }
                    // Durata 2
                    $styleDurata2 = '';
                    $oraPausa3 = $oraripause[2] ?? '';
                    $oraPausa4 = $oraripause[3] ?? '';
                    if (!empty($oraPausa3)) {
                        try {
                            $dt1 = new DateTime($oraPausa3);
                            $dt2 = !empty($oraPausa4) ? new DateTime($oraPausa4) : new DateTime();
                            $secondi = abs($dt2->getTimestamp() - $dt1->getTimestamp());
                            $minuti = floor($secondi / 60);
                            if (empty($oraPausa4)) {
                                if ($minuti > 12) {
                                    $styleDurata2 = 'style="background-color: red; font-weight: bold; animation: lampeggia 1s infinite;"';
                                } else {
                                    $styleDurata2 = 'style="background-color: #fff3cd; font-weight: bold;"';
                                }
                            } elseif ($minuti <= 10) {
                                $styleDurata2 = 'style="color: green; font-weight: bold;"';
                            } elseif ($minuti == 11) {
                                $styleDurata2 = 'style="color: orange; font-weight: bold;"';
                            } elseif ($minuti >= 12) {
                                $styleDurata2 = 'style="color: red; font-weight: bold;"';
                            }
                        } catch (Exception $e) {}
                    }
                    // Durata 3
                    $styleDurata3 = '';
                    $oraPausa5 = $oraripause[4] ?? '';
                    $oraPausa6 = $oraripause[5] ?? '';
                    if (!empty($oraPausa5)) {
                        try {
                            $dt1 = new DateTime($oraPausa5);
                            $dt2 = !empty($oraPausa6) ? new DateTime($oraPausa6) : new DateTime();
                            $secondi = abs($dt2->getTimestamp() - $dt1->getTimestamp());
                            $minuti = floor($secondi / 60);
                            if (empty($oraPausa6)) {
                                if ($minuti > 12) {
                                    $styleDurata3 = 'style="background-color: red; font-weight: bold; animation: lampeggia 1s infinite;"';
                                } else {
                                    $styleDurata3 = 'style="background-color: #fff3cd; font-weight: bold;"';
                                }
                            } elseif ($minuti <= 10) {
                                $styleDurata3 = 'style="color: green; font-weight: bold;"';
                            } elseif ($minuti == 11) {
                                $styleDurata3 = 'style="color: orange; font-weight: bold;"';
                            } elseif ($minuti >= 12) {
                                $styleDurata3 = 'style="color: red; font-weight: bold;"';
                            }
                        } catch (Exception $e) {}
                    }
                    // Calcola le durate del lavoro singole
                    $durateLavoro = $this->calcolaDurateLavoroSingole($orarilavoro);

                    // AGGIUNTA: calcola stili errore lavoro

                    // Riga 1 - tipo 0000
        echo "<tr style='background-color: #e9f7ef;'>";
                if (isset($_SESSION['autenticato']) && $_SESSION['autenticato'] === true) {

        echo "<td class='text-center'>";
            echo "<button onclick=\"openGestioneDipendente('{$IdGest}')\" class='btn btn-primary btn-sm px-2 py-0' title='Gestione Dipendente'>
                    <i class='bi bi-credit-card'></i>
                  </button>
                  <button onclick=\"openPopupTimbratura('{$IdGest}')\" class='btn btn-warning btn-sm px-2 py-0' title='Aggiungi timbratura'>
                    <i class='bi bi-plus-circle'></i>
                  </button>";
  echo "</td>";        }

                         if (isset($_SESSION['autenticato']) && $_SESSION['autenticato'] === true) {
                    echo "<td><div class='d-flex justify-content-between fw-bold' style='gap: 0.15rem;'><span>{$presCogn} {$presNome} - Badge: {$IdGest}</span><span>Ingressi:</span></div></td>";
                        } else {
                            echo "<td><div class='d-flex justify-content-between fw-bold' style='gap: 0.15rem;'><span>{$presCogn} {$presNome}</span><span>Ingressi:</span></div></td>";
                        }
                    for ($i = 0; $i < 6; $i++) {
                        // Gestione pulsante "aggiungi timbratura" in base all'indice orario e ora limite
                        if ($i % 2 === 1 && !empty($orarilavoro[$i - 1]) && empty($orarilavoro[$i])) {
                            $now = new DateTime();
                            $oraLimite = match ($i) {
                                1 => new DateTime($now->format('Y-m-d') . ' 12:30:00'),
                                3 => new DateTime($now->format('Y-m-d') . ' 17:00:00'),
                                default => new DateTime('tomorrow') // disabilita altri
                            };
                            if ($now >= $oraLimite) {
                                $chkMancanti = true;
                                $oraRenderizzata = "<button onclick=\"openPopupTimbratura('{$IdGest}')\" class='btn btn-warning btn-sm px-2 py-0' title='Aggiungi timbratura'>
                                    <i class='bi bi-plus-circle'></i>
                                </button>";
                            } else {
                                $oraRenderizzata = '';
                            }
                        } else {
                            $oraRenderizzata = $orarilavoro[$i];
                            if ($TipoLavoro[$i] === '0000' && $DaSistemareLavoro[$i] === '5' && !empty($oraRenderizzata)) {
                                $oraRenderizzata = "<span style='text-decoration: underline; text-decoration-color: red; font-weight: bold;'>{$oraRenderizzata}</span>";
                            } elseif ($TipoLavoro[$i] === '0000' && $DaSistemareLavoro[$i] === '9' && !empty($oraRenderizzata)) {
                                $oraRenderizzata = "<span style='text-decoration: underline; text-decoration-color: green;'>{$oraRenderizzata}</span>";
                            }
                        }
                        echo $this->renderDropdownMenu($oraRenderizzata, '', $IdOrinLavoro[$i], $DeIdLavoro[$i], $TipoLavoro[$i], $SensoLavoro[$i], $filtDateFrom,  $IdGest , $trasmessaLavoro[$i]);
                    }
                                             if (isset($_SESSION['autenticato']) && $_SESSION['autenticato'] === true) {

                    echo "<td>" . ($durateLavoro[0] !== "00:00" ? $durateLavoro[0] . ' h' : '') . "</td>";
                    echo "<td>" . ($durateLavoro[1] !== "00:00" ? $durateLavoro[1] . ' h' : '') . "</td>";
                    echo "<td>" . ($durateLavoro[2] !== "00:00" ? $durateLavoro[2] . ' h' : '') . "</td>";
                    echo "</tr>";
                    }

                    // Riga 2 - tipo 0001
                    echo "<tr style='background-color: #fffaf0;'>";
                       if (isset($_SESSION['autenticato']) && $_SESSION['autenticato'] === true) {
                    echo "<td colspan='2' style='text-align: right; font-weight: bold;'>Pause: {$presDtIn}</td>";   }
                    else {
                        echo "<td colspan='1' style='text-align: right; font-weight: bold;'>Pause:</td>";
                    }

                    for ($i = 0; $i < 6; $i++) {
                        $oraRenderizzata = $oraripause[$i];
                        if ($TipoPausa[$i] === '0001' && $DaSistemarePausa[$i] === '5' && !empty($oraRenderizzata)) {
                            $oraRenderizzata = "<span style='text-decoration: underline; text-decoration-color: red; font-weight: bold;'>{$oraRenderizzata}</span>";
                        }
                        elseif ($TipoPausa[$i] === '0001' && $DaSistemarePausa[$i] === '9' && !empty($oraRenderizzata)) {
                            $oraRenderizzata = "<span style='text-decoration: underline; text-decoration-color: green; '>{$oraRenderizzata}</span>";
                        }
                        // Assicura che $STTIME sia valorizzato per evitare warning
                        echo $this->renderDropdownMenu($oraRenderizzata, '', $IdOrinPausa[$i], $DeIdPausa[$i], $TipoPausa[$i], $SensoPausa[$i], $filtDateFrom, $IdGest , $trasmessaPausa[$i]);
                    }
                    echo "<td {$styleDurata1}>" . (!empty($oraPausa1) ? $durateSingole[0] : '') . "</td>";
                    echo "<td {$styleDurata2}>" . (!empty($oraPausa3) ? $durateSingole[1] : '') . "</td>";
                    echo "<td {$styleDurata3}>" . (!empty($oraPausa5) ? $durateSingole[2] : '') . "</td>";
                    echo "</tr>";

                    // Riga separatrice
                    echo "<tr><td colspan='11' style='height: 8px; background-color: transparent;'></td></tr>";
                }
            }
            // Aggiungi riga vuota alla fine della tabella per risolvere bug grafico menù
            echo "<tr style='border: none !important;'><td colspan='11' style='height: 150px; border: none !important; background-color: transparent !important;'></td></tr>";
            // JS per contatori
            echo <<<JS
    <script> 
        $("#totPresenti").html("$totPresenti");
        $("#totpausafatta").html("$totpausafatta");
        $("#totinpausa").html("$totinpausa");
    </script>
    JS;
            return;
        }



		if ($xlSegmentToWrite == "nessunpresente") {

			echo <<<SEGDTA
<h4 class="text-center">Nessun in pausa trovato</h4>
SEGDTA;
			return;
		}

		// If we reach here, the segment is not found
		echo ("Segment $xlSegmentToWrite is not defined! ");
	}

	// return a segment's content instead of outputting it to the browser
	function getSegment($xlSegmentToWrite, $segmentVars = array())
	{
		ob_start();

		$this->writeSegment($xlSegmentToWrite, $segmentVars);

		return ob_get_clean();
	}

	function __construct()
	{

		$this->pf_liblLibs[1] = 'BCD_DATIV2';

		parent::__construct();

		$this->pf_scriptname = 'pause.php';
		$this->pf_wcm_set = 'PRODUZIONE';

		$this->xl_set_env($this->pf_wcm_set);

	}
}

xlLoadWebSmartObject(__FILE__, 'PAUSE'); ?>

<script>
  function resetForm() {
    const form = document.getElementById('filter-form');
    if (!form) return;

    document.getElementById('filtDateFrom').value = new Date().toISOString().split('T')[0];
    document.getElementById('filtDateTo').value = new Date().toISOString().split('T')[0];
    document.getElementById('filtNome').value = '';
    document.getElementById('filtCognome').value = '';

    // Reset radio buttons (filtroTipo)
    const radioButtons = document.querySelectorAll('input[name="filtroTipo"]');
    radioButtons.forEach(rb => rb.checked = false);
    const defaultRadio = document.getElementById('filtroPresenti');
    if (defaultRadio) defaultRadio.checked = true;

    // Reset checkbox errori
    const chkError = document.getElementById('chkError');
    if (chkError) chkError.checked = false;

    form.submit();
  }

  // Gestione indipendente del checkbox "Errori" (chkError)
  document.addEventListener('DOMContentLoaded', function () {
    const chkError = document.getElementById('chkError');
    if (chkError) {
      chkError.addEventListener('change', function () {
        this.form.submit();
      });
    }
  });

  // Rendi chkInPausa e chkPausaFatta mutuamente esclusivi e invia il form
  document.addEventListener('DOMContentLoaded', function () {
    const chkInPausa = document.getElementById('chkInPausa');
    const chkPausaFatta = document.getElementById('chkPausaFatta');

    if (chkInPausa && chkPausaFatta) {
      chkInPausa.addEventListener('change', function () {
        if (this.checked) {
          chkPausaFatta.checked = false;
        }
        this.form.submit();
      });

      chkPausaFatta.addEventListener('change', function () {
        if (this.checked) {
          chkInPausa.checked = false;
        }
        this.form.submit();
      });
    }
  });

  setTimeout(function() {
    location.reload();
}, 30000); // 30000 millisecondi = 30 secondi

function openGestioneDipendente(BDBADG) {
  // Apertura diretta popup gestione dipendente senza richiesta password
  const width = 600;
  const height = 900;
  const left = (window.screen.width - width) / 2;
  const top = (window.screen.height - height) / 2;
  const url = 'https://jde.rgpballs.com/HR/gestdipe.php?BDBADG=' + encodeURIComponent(BDBADG);
  window.open(url, 'popupGestioneDipendenti', `width=${width},height=${height},left=${left},top=${top},resizable=yes,scrollbars=yes`);
}

function openPopupTimbratura(BDBADG) {
  // Apertura diretta popup timbratura senza richiesta password
  const width = 600;
  const height = 900;
  const left = (window.screen.width - width) / 2;
  const top = (window.screen.height - height) / 2;
  const url = 'https://jde.rgpballs.com/HR/savManTimbratura.php?BDBADG=' + encodeURIComponent(BDBADG);
  window.open(url, 'popupTimbratura', `width=${width},height=${height},left=${left},top=${top},resizable=yes,scrollbars=yes`);
}
</script>