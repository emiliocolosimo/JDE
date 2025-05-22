<?php
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
    private function renderDropdownMenu($ora, $errStyle, $IdOrin, $DeId, $tipo, $senso)
    {
        if (empty($ora)) {
            return "<td></td>";
        }

        // Descrizione leggibile per il device
        $deviceLabel = match($DeId) {
            '70' => 'Ingresso',
            '71' => 'Magazzino',
            '72' => 'Produzione',
            default => 'Dispositivo sconosciuto'
        };

        $html = <<<HTML
<td {$errStyle} title="ID Origine: {$IdOrin} - {$deviceLabel} - {$tipo} - {$senso}" style="position: relative;">{$ora}
  <div class="dropdown d-inline">
    <i class="bi bi-pencil-square text-secondary dropdown-toggle" style="cursor: pointer;" data-bs-toggle="dropdown" aria-expanded="false" title="Azioni disponibili"></i>
    <ul class="dropdown-menu dropdown-menu-end" style="z-index: 1050;">
HTML;

if ($tipo === '0000') {
    $html .= <<<HTML
      <li><a class="dropdown-item" href="#" onclick="richiediPasswordPerAzione('pause.php?convertiinpausa={$IdOrin}'); return false;">Converti in pausa</a></li>
HTML;
}

if ($tipo === '0001' || ($tipo === '0000' && $senso === 'E')) {
    $html .= <<<HTML
      <li><a class="dropdown-item" href="#" onclick="richiediPasswordPerAzione('pause.php?convertiinuscita={$IdOrin}'); return false;">Converti in uscita</a></li>
HTML;
} 
if ($tipo === '0001' || $tipo === '0000' && $senso === 'U') {
    $html .= <<<HTML
      <li><a class="dropdown-item" href="#" onclick="richiediPasswordPerAzione('pause.php?convertiinentrata={$IdOrin}'); return false;">Converti in entrata</a></li>
HTML;
}

        $html .= <<<HTML
      <li><a class="dropdown-item text-danger" href="#" onclick="richiediPasswordPerAzione('pause.php?elimina={$IdOrin}'); return false;">Elimina</a></li>
    </ul>
  </div>
</td>
HTML;
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
            $IdOrin = $_GET['convertiinpausa'];
            if (!empty($IdOrin)) {
                $query = "UPDATE BCD_DATIV2.SAVTIM0F 
                          SET STRECO = '0001' 
                          WHERE STORIN = ? and STRECO = '0000' and STTRAS=' '";
                $stmt = $this->db_connection->prepare($query);
                $stmt->execute([$IdOrin]);
            }
            header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
            exit;
        }

        // Gestione azione "converti in entrata"
        if (isset($_GET['convertiinentrata'])) {
            $IdOrin = $_GET['convertiinentrata'];
            if (!empty($IdOrin)) {
                $query = "UPDATE BCD_DATIV2.SAVTIM0F 
                          SET STRECO = '0000' , STSENS = 'E' 
                          WHERE STORIN = ? and STTRAS=' '";
                $stmt = $this->db_connection->prepare($query);
                $stmt->execute([$IdOrin]);
            }
            header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
            exit;
        }
        // Gestione azione "converti in uscita"
        if (isset($_GET['convertiinuscita'])) {
            $IdOrin = $_GET['convertiinuscita'];
            if (!empty($IdOrin)) {
                $query = "UPDATE BCD_DATIV2.SAVTIM0F 
                          SET STRECO = '0000' , STSENS = 'U' 
                          WHERE STORIN = ? and STTRAS=' '";
                $stmt = $this->db_connection->prepare($query);
                $stmt->execute([$IdOrin]);
            }
            header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
            exit;
        }
        // Gestione azione "elimina"
        if (isset($_GET['elimina'])) {
            $IdOrin = $_GET['elimina'];
            if (!empty($IdOrin)) {
                $query = "delete BCD_DATIV2.SAVTIM0F 
                           WHERE STTRAS=' ' AND
						   STORIN = ?";
                $stmt = $this->db_connection->prepare($query);
                $stmt->execute([$IdOrin]);
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
			$_SESSION["filtDate"] = $_POST["filtDate"] ?? date("Y-m-d");
			$_SESSION["filtCognome"] = $_POST["filtCognome"] ?? '';
			$_SESSION["filtNome"] = $_POST["filtNome"] ?? '';
			$_SESSION["chkInPausa"] = isset($_POST["chkInPausa"]) ? 1 : 0;
			$_SESSION["chkPausaFatta"] = isset($_POST["chkPausaFatta"]) ? 1 : 0;
			$_SESSION["chkPresenti"] = isset($_POST["chkPresenti"]) ? 1 : 0;
			$_SESSION["chkError"] = isset($_POST["chkError"]) ? 1 : 0;
			header("Location: " . $_SERVER["PHP_SELF"]);
			exit;
		}

		$this->printPageHeader();
		$totPresenti = 0;
		$totpausafatta = 0;
		$totinpausa = 0;

		// Data da visualizzare (dalla sessione o data odierna)
		$filtDate = !empty($_SESSION["filtDate"]) ? $_SESSION["filtDate"] : date("Y-m-d");
		$filtCognome = strtoupper(htmlspecialchars($_SESSION['filtCognome'] ?? ''));
		$filtNome = strtoupper(htmlspecialchars($_SESSION['filtNome'] ?? ''));

		if (!isset($_SESSION["chkInPausa"])) $_SESSION["chkInPausa"] = 1;
		if (!isset($_SESSION["chkPausaFatta"])) $_SESSION["chkPausaFatta"] = 1;
		if (!isset($_SESSION["chkPresenti"])) $_SESSION["chkPresenti"] = 1;
		if (!isset($_SESSION["chkError"])) $_SESSION["chkError"] = 0;

        $query = "
        SELECT 
            A.STDATE,
            VARCHAR(A.STTIME) AS STTIME,
            COALESCE(D.BDCOGN, '') AS BDCOGN,
            COALESCE(D.BDNOME, '') AS BDNOME,
            COALESCE(A.STRECO, '') AS STRECO,
            COALESCE(A.STDEID, '') AS STDEID,
            COALESCE(A.STCDDI, '') AS STCDDI,
            COALESCE(D.BDTIMB, '') AS BDTIMB,
            COALESCE(A.STORIN, '') AS STORIN,
            COALESCE(A.STSENS, '') AS STSENS
        FROM BCD_DATIV2.SAVTIM0F AS A
        LEFT JOIN BCD_DATIV2.BDGDIP0F AS D ON A.STCDDI = D.BDCOGE
        WHERE A.STRECO IN ('0001', '0001')
            AND A.STDATE = '" . $filtDate . "'
            AND D.BDNOME LIKE '%" . $filtNome . "%'
            AND D.BDCOGN LIKE '%" . $filtCognome . "%'
        ORDER BY D.BDNOME, D.BDCOGN, A.STTIME
        ";

        $stmt = $this->db_connection->prepare($query);
        $result = $stmt->execute();
        $y = 0;
        $x = 0;
        $z = 0;

        $raggruppati = [];
        $presentiInSede = [];
        $errpausagen = '';

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            foreach ($row as $key => $value) {
                $row[$key] = htmlspecialchars(rtrim($value));
                $escapedField = xl_fieldEscape($key);
                $$escapedField = $row[$key];
            }
            if (!isset($row['STRECO'])) $row['STRECO'] = '';
            if (!isset($row['STSENS'])) $row['STSENS'] = '';

            $presNome = $BDCOGN;
            $presCogn = $BDNOME;
            $presStreco = $STRECO;
            $presStsens = $STSENS;
            $DeId = $STDEID;
            $IdGest = $STCDDI;
            $IdTimb = $BDTIMB;
            $IdOrin = $STORIN;
            $presDtIn = $this->cvtDateFromDb(str_replace("-", "", $STDATE));
            $presSens = $STSENS;
            $presTipo = $STRECO;
            $chiave = $presNome . '|' . $presCogn . '|' . $presDtIn ;

            // Nuovo: singolo orario per riga
            $orario = preg_match('/^\d{6}$/', $STTIME) ? substr($this->cvtTimeFromDb($STTIME), 0, 5) : substr($STTIME, 0, 5);

            if (!isset($raggruppati[$chiave])) {
                $raggruppati[$chiave] = [
                    'presNome' => $presNome,
                    'presCogn' => $presCogn,
                    'presDtIn' => $presDtIn,
                    'IdGest' => $IdGest,
                    'IdTimb' => $IdTimb,
                    'DeId' => $DeId,
                    'tipo' => [],
                    'senso' => [],
                    'orari' => [],
                    'idorin' => [],
                    'DeIdList' => [],
                ];
            }
            $raggruppati[$chiave]['orari'][] = $orario;
            $raggruppati[$chiave]['idorin'][] = $STORIN;
            $raggruppati[$chiave]['tipo'][] = $STRECO;
            $raggruppati[$chiave]['senso'][] = $STSENS;
            $raggruppati[$chiave]['DeIdList'][] = $STDEID;
        }

		// Stampa le righe raggruppate
		$y = 0;
		$z = 0;
		$inPausaList = [];
		$pausaFattaList = [];
		
		foreach ($raggruppati as $record) {
			$presNome = $record['presNome'];
			$presCogn = $record['presCogn'];
			$presDtIn = $record['presDtIn'];
			$IdGest = $record['IdGest'];
			$IdTimb = $record['IdTimb'];
			$DeId = $record['DeId'];
			$orari = $record['orari'];
			// --- Normalizzazione orari: formato HH:MM ---
			$idorin = $record['idorin'] ?? [];
			$presTipo = $record['tipo'] ?? [];
			$PresSens = $record['senso'] ?? [];
            $this->currentDeviceId = $DeId;
			sort($orari); // Ordina le timbrature in ordine crescente
			$orari = array_slice($orari, 0, 6); // Prendi solo le prime 6
			$idorin = array_slice($idorin, 0, 6); // Allineamento con gli orari
            $deids = array_slice($record['DeIdList'] ?? array_fill(0, 6, $DeId), 0, 6);
			$presTipo = array_slice($presTipo, 0, 6); // Allineamento con gli orari
			$presSens = array_slice($PresSens, 0, 6); // Allineamento con gli orari 
			$DeId1 = $deids[0] ?? '';
			$DeId2 = $deids[1] ?? '';
			$DeId3 = $deids[2] ?? '';
			$DeId4 = $deids[3] ?? '';
			$DeId5 = $deids[4] ?? '';
			$DeId6 = $deids[5] ?? '';
			$ora1 = $orari[0] ?? '';
			$ora2 = $orari[1] ?? '';
			$ora3 = $orari[2] ?? '';
			$ora4 = $orari[3] ?? '';
			$ora5 = $orari[4] ?? '';
			$ora6 = $orari[5] ?? '';
			$IdOrin1 = $idorin[0] ?? '';
			$IdOrin2 = $idorin[1] ?? '';
			$IdOrin3 = $idorin[2] ?? '';
			$IdOrin4 = $idorin[3] ?? '';
			$IdOrin5 = $idorin[4] ?? '';
			$IdOrin6 = $idorin[5] ?? '';
			$presTipo1 = $presTipo[0] ?? '';
			$presTipo2 = $presTipo[1] ?? '';
			$presTipo3 = $presTipo[2] ?? '';
			$presTipo4 = $presTipo[3] ?? '';
			$presTipo5 = $presTipo[4] ?? '';
			$presTipo6 = $presTipo[5] ?? '';
			$presSens1 = $presSens[0] ?? '';
			$presSens2 = $presSens[1] ?? '';
			$presSens3 = $presSens[2] ?? '';
			$presSens4 = $presSens[3] ?? '';
			$presSens5 = $presSens[4] ?? '';
			$presSens6 = $presSens[5] ?? '';

			$errIdTimbDeid = '';
			if ($IdTimb !== $DeId && $IdTimb !== '' && $DeId !== '') {
				$errIdTimbDeid = 'style="background-color: ORANGE;"';
			}
			$totPause = 0;
			$inPausaAttuale = false;
			try {
				$now = new DateTime();
				if (!empty($ora1) && !empty($ora2)) {
					$dt1 = new DateTime($ora1);
					$dt2 = new DateTime($ora2);
					$totPause += abs($dt2->getTimestamp() - $dt1->getTimestamp());
				} elseif (!empty($ora1) && empty($ora2)) {
					$dt1 = new DateTime($ora1);
					$totPause += abs($now->getTimestamp() - $dt1->getTimestamp());
					$inPausaAttuale = true;
				}
				if (!empty($ora3) && !empty($ora4)) {
					$dt3 = new DateTime($ora3);
					$dt4 = new DateTime($ora4);
					$totPause += abs($dt4->getTimestamp() - $dt3->getTimestamp());
				} elseif (!empty($ora3) && empty($ora4)) {
					$dt3 = new DateTime($ora3);
					$totPause += abs($now->getTimestamp() - $dt3->getTimestamp());
					$inPausaAttuale = true;
				}
				if (!empty($ora5) && !empty($ora6)) {
					$dt5 = new DateTime($ora5);
					$dt6 = new DateTime($ora6);
					$totPause += abs($dt6->getTimestamp() - $dt5->getTimestamp());
				} elseif (!empty($ora5) && empty($ora6)) {
					$dt5 = new DateTime($ora5);
					$totPause += abs($now->getTimestamp() - $dt5->getTimestamp());
					$inPausaAttuale = true;
				}
			} catch (Exception $e) {
				$totPause = 0;
				$inPausaAttuale = false;
			}
			$totPauseMinuti = floor($totPause / 60);
			$hh = floor($totPauseMinuti / 60);
			$mm = $totPauseMinuti % 60;
			$totPauseHHMM = sprintf('%dh %02dm', $hh, $mm);
			$analisi = $this->getErrorePausa([$ora1, $ora2, $ora3, $ora4, $ora5, $ora6], $inPausaAttuale);
			extract($analisi);
			$recordData = array_merge(compact(
				'presNome',
				'presCogn',
				'presDtIn',
				'IdGest',
				'ora1',
				'ora2',
				'ora3',
				'ora4',
				'ora5',
				'ora6',
				'totPauseMinuti',
				'totPauseHHMM',
				'inPausaAttuale',
				'DeId',
				'IdTimb',
				'errIdTimbDeid',
				'DeId1',
				'DeId2',
				'DeId3',
				'DeId4',
				'DeId5',
				'DeId6',
				'IdOrin1',
				'IdOrin2',
				'IdOrin3',
				'IdOrin4',
				'IdOrin5',
				'IdOrin6',	
				'presTipo',
				'presSens',
				'presTipo1',
				'presTipo2',
				'presTipo3',
				'presTipo4',
				'presTipo5',
				'presTipo6',
				'presSens1',
				'presSens2',
				'presSens3',
				'presSens4',
				'presSens5',
				'presSens6'
			), $analisi);
			if ($inPausaAttuale) {
				$inPausaList[] = $recordData;
			} else {
				$pausaFattaList[] = $recordData;
			}
		}

		$showInPausa = $_SESSION["chkInPausa"] ?? true;
		$showError = $_SESSION["chkError"] ?? 1;

		// --- PRIMA in pausa ---
		if ($showInPausa && !empty($inPausaList)) {
			$this->writeSegment("titoloinpausa", array_merge(get_object_vars($this), get_defined_vars()));
	foreach ($inPausaList as $record) {
	extract($record);
	$analisi = $this->getErrorePausa([$ora1, $ora2, $ora3, $ora4, $ora5, $ora6], $inPausaAttuale);
	extract($analisi);

	// MOSTRA SOLO RECORD IN ERRORE SE chkError È ATTIVO
	if ($showError && empty($errpausagen)) {
		continue;
	}

	$this->writeSegment("rigainpausa", array_merge(get_object_vars($this), get_defined_vars()));
	$totinpausa++;
}
		}
		
		$showPausaFatta = $_SESSION["chkPausaFatta"] ?? 1;

		// --- POI chi ha fatto pausa ---

		if ($showPausaFatta && !empty($pausaFattaList)) {
			$this->writeSegment("titolopausafatta", array_merge(get_object_vars($this), get_defined_vars()));
foreach ($pausaFattaList as $record) {
	extract($record);
	$analisi = $this->getErrorePausa([$ora1, $ora2, $ora3, $ora4, $ora5, $ora6], $inPausaAttuale);
	extract($analisi);

	// MOSTRA SOLO RECORD IN ERRORE SE chkError È ATTIVO
	if ($showError && empty($errpausagen)) {
		continue;
	}

	$this->writeSegment("rigapausafatta", array_merge(get_object_vars($this), get_defined_vars()));
	$totpausafatta++;
}
		}

		//TOTALE ORE:
		$showPresenti = $_SESSION["chkPresenti"] ?? 1;
		if ($showPresenti && $x == 0) {
			$this->writeSegment("hPresenti", array_merge(get_object_vars($this), get_defined_vars()));
		}

		$query = "SELECT 
    '" . $filtDate . "' AS STDATE,
    COALESCE(D.BDCOGN, '') AS BDCOGN,
    COALESCE(D.BDNOME, '') AS BDNOME,
    COALESCE(D.BDCOGE, '') AS BDCOGE,
    COALESCE(A.STORIN, '') AS STORIN,
    COALESCE(A.STDEID, '') AS STDEID,
    CAST(
        XMLSERIALIZE(
            CONTENT XMLAGG(XMLTEXT(
                A.STTIME || '|' || COALESCE(A.STSENS, '') || '|' || COALESCE(A.STRECO, '') || '|' || COALESCE(A.STDEID, '') || '|' || COALESCE(A.STORIN, '') || ','
            ) ORDER BY A.STTIME)
            AS VARCHAR(3000)
        )
    AS VARCHAR(3000)) AS STINFOS
FROM BCD_DATIV2.BDGDIP0F AS D
LEFT JOIN BCD_DATIV2.SAVTIM0F AS A 
    ON D.BDCOGE = A.STCDDI 
    AND A.STRECO = '0000'
    AND A.STDATE = '" . $filtDate . "'
		WHERE D.BDNOME LIKE '%" . $filtNome . "%'
  AND D.BDCOGN LIKE '%" . $filtCognome . "%'
GROUP BY 
    D.BDCOGN, D.BDNOME, D.BDCOGE, A.STORIN, A.STDEID
ORDER BY 
    D.BDNOME, D.BDCOGN
		";
		$stmt = $this->db_connection->prepare($query);
		$result = $stmt->execute();
		while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			/*
			AND NOT EXISTS(
				SELECT 1 
				FROM BCD_DATIV2.SAVTIM0F AS B  
				WHERE B.STTIMS > A.STTIMS 
				AND B.STCDDI = A.STCDDI  
				AND B.STSENS = 'U'  
				AND A.STRECO = '0000' 
				AND A.STDATE = '" . $filtDate . "' 
				FETCH FIRST ROW ONLY
			*/
			foreach (array_keys($row) as $key) {
				$row[$key] = htmlspecialchars(rtrim($row[$key]));
				$escapedField = xl_fieldEscape($key);
				$$escapedField = $row[$key];
			}

			$presNome = $BDCOGN;
			$presCogn = $BDNOME;
			$IdGest = $BDCOGE;
			$IdOrin = $STORIN;
			$DeId = $STDEID;
			$presDtIn = $this->cvtDateFromDb(str_replace("-", "", $STDATE));
			$chiave = $presNome . '|' . $presCogn . '|' . $presDtIn;

			// Parsing di STINFOS per recuperare orari, senso, tipo, idorin, DeId
			$infoArray = explode(',', $STINFOS);
			foreach ($infoArray as $record) {
				if (empty($record)) continue;
				list($time, $senso, $tipo, $deid, $idorin) = explode('|', $record);
				try {
					$dt = new DateTime($time);
					$ora = $dt->format('H:i');
					if (!isset($presentiInSede[$chiave])) {
						$presentiInSede[$chiave] = [
							'nome' => $presNome,
							'cognome' => $presCogn,
							'IdGest' => $IdGest,
							'orari' => [],
							'idorin' => [],
							'DeId' => [],
							'senso' => [],
							'tipo' => []
						];
					}
					$presentiInSede[$chiave]['orari'][] = $ora;
					$presentiInSede[$chiave]['idorin'][] = $idorin;
					$presentiInSede[$chiave]['DeId'][] = $deid;
					$presentiInSede[$chiave]['senso'][] = $senso;
					$presentiInSede[$chiave]['tipo'][] = $tipo;
				} catch (Exception $e) {
					continue;
				}
			}
			// Ordina orari e riallinea gli altri array associati
			if (isset($presentiInSede[$chiave])) {
				$combined = [];
				foreach ($presentiInSede[$chiave]['orari'] as $i => $time) {
					$combined[] = [
						'time' => $time,
						'idorin' => $presentiInSede[$chiave]['idorin'][$i] ?? '',
						'DeId' => $presentiInSede[$chiave]['DeId'][$i] ?? '',
						'senso' => $presentiInSede[$chiave]['senso'][$i] ?? '',
						'tipo' => $presentiInSede[$chiave]['tipo'][$i] ?? '',
					];
				}
				usort($combined, fn($a, $b) => strcmp($a['time'], $b['time']));
				$presentiInSede[$chiave]['orari'] = array_column($combined, 'time');
				$presentiInSede[$chiave]['idorin'] = array_column($combined, 'idorin');
				$presentiInSede[$chiave]['DeId'] = array_column($combined, 'DeId');
				$presentiInSede[$chiave]['senso'] = array_column($combined, 'senso');
				$presentiInSede[$chiave]['tipo'] = array_column($combined, 'tipo');
			}
			// Assegna currentDeviceId anche per i presenti
			$this->currentDeviceId = $DeId;
			$totPresenti++;
			$x++;
		}
$showPresenti = $_SESSION["chkPresenti"] ?? 1;
if ($showPresenti) {
            $this->writeSegment("totpresenti", array_merge(get_object_vars($this), [
                'errpausagen' => $errpausagen,
                'presentiInSede' => $presentiInSede,
                'totPresenti' => $totPresenti,
                'totpausafatta' => $totpausafatta,
                'totinpausa' => $totinpausa
            ]));
}
		$this->printPageFooter();
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

	private function getErrorePausa(array $orari, bool $inPausaAttuale = false)
{
	$errpausa1lunga = '';
	$errpausa2lunga = '';
	$errtroppepausa = '';
	$errtotPauseMinuti = '';
	$errinpausaattuale = '';
	$errpausagen = '';
	$totPause = 0;
	$ora1 = $orari[0] ?? '';
	$ora2 = $orari[1] ?? '';
	$ora3 = $orari[2] ?? '';
	$ora4 = $orari[3] ?? '';
	$ora5 = $orari[4] ?? '';
	$ora6 = $orari[5] ?? '';

	try {
		$now = new DateTime();

		if (!empty($ora1) && !empty($ora2)) {
			$dt1 = new DateTime($ora1);
			$dt2 = new DateTime($ora2);
			$intervallo1 = abs($dt2->getTimestamp() - $dt1->getTimestamp());
			$totPause += $intervallo1;
			if ($intervallo1 > 600) $errpausa1lunga = 'style="background-color: red;"';
		} elseif (!empty($ora1)) {
			$dt1 = new DateTime($ora1);
			$totPause += abs($now->getTimestamp() - $dt1->getTimestamp());
			$inPausaAttuale = true;
			if ($totPause > 600) $errpausa1lunga = 'style="background-color: red;"';
		}

		if (!empty($ora3) && !empty($ora4)) {
			$dt3 = new DateTime($ora3);
			$dt4 = new DateTime($ora4);
			$intervallo2 = abs($dt4->getTimestamp() - $dt3->getTimestamp());
			$totPause += $intervallo2;
			if ($intervallo2 > 600) $errpausa2lunga = 'style="background-color: red;"';
		} elseif (!empty($ora3)) {
			$dt3 = new DateTime($ora3);
			$durataPausa2 = 0;
			$durataPausa2 += abs($now->getTimestamp() - $dt3->getTimestamp());
			$totPause += abs($now->getTimestamp() - $dt3->getTimestamp());
			$inPausaAttuale = true;
			if ($durataPausa2 > 600) $errpausa2lunga = 'style="background-color: red;"';
		}

		if (!empty($ora5)) {
			if (!empty($ora6)) {
				$dt5 = new DateTime($ora5);
				$dt6 = new DateTime($ora6);
				$intervallo3 = abs($dt6->getTimestamp() - $dt5->getTimestamp());
				$totPause += $intervallo3;
			} else {
				$dt5 = new DateTime($ora5);
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

	protected function printPageHeader()
	{
		$filtNome = strtoupper($_SESSION['filtNome'] ?? '');
		$filtCognome = strtoupper($_SESSION['filtCognome'] ?? '');
		$filtDate = $_SESSION['filtDate'] ?? date('Y-m-d');
		$chkInPausaChecked = isset($_SESSION['chkInPausa']) && $_SESSION['chkInPausa'] ? 'checked' : '';
		$chkPausaFattaChecked = isset($_SESSION['chkPausaFatta']) && $_SESSION['chkPausaFatta'] ? 'checked' : '';
		$chkPresentiChecked = isset($_SESSION['chkPresenti']) && $_SESSION['chkPresenti'] ? 'checked' : '';
		$chkError = isset($_SESSION['chkError']) && $_SESSION['chkError'] ? 'checked' : '';

		$chkInPausaBtnClass = isset($_SESSION['chkInPausa']) && $_SESSION['chkInPausa'] ? 'btn-primary' : 'btn-outline-primary';
		$chkPausaFattaBtnClass = isset($_SESSION['chkPausaFatta']) && $_SESSION['chkPausaFatta'] ? 'btn-primary' : 'btn-outline-primary';
		$chkPresentiBtnClass = isset($_SESSION['chkPresenti']) && $_SESSION['chkPresenti'] ? 'btn-primary' : 'btn-outline-primary';
		$chkErrorBtnClass = isset($_SESSION['chkError']) && $_SESSION['chkError'] ? 'btn-danger' : 'btn-outline-danger';

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
        <a class="dropdown-item" href="#" onclick="apriPopupTransfer(); return false;">
  <i class="bi bi-person-lines-fill me-2"></i>Invia Timbrature a Commercialista
</a>
        </li>
      </ul>
    </div>
  </div>
<div class="clearfix"></div>
<div id="contents">
  <div class="row mb-1">
    <div class="col-12">
      <form method="POST" id="filter-form" class="mb-0">
        <div class="row align-items-center g-0 mb-3">
          <!-- Colonna 1: Checkbox -->
          <div class="col-md-4">
            <div class="btn-group flex-wrap gap-2" role="group" aria-label="Toggle segmenti">
              <input type="checkbox" class="btn-check" id="chkInPausa" name="chkInPausa" value="1" {$chkInPausaChecked} autocomplete="off" onchange="this.form.submit();">
              <label class="btn {$chkInPausaBtnClass} btn-sm d-inline-flex align-items-center gap-2 px-2 py-1" for="chkInPausa">
                <i class="bi bi-pause-circle"></i> In Pausa
              </label>
              <input type="checkbox" class="btn-check" id="chkPausaFatta" name="chkPausaFatta" value="1" {$chkPausaFattaChecked} autocomplete="off" onchange="this.form.submit();">
              <label class="btn {$chkPausaFattaBtnClass} btn-sm d-inline-flex align-items-center gap-2 px-2 py-1" for="chkPausaFatta">
                <i class="bi bi-check-circle"></i> Pause Fatte:
              </label>
              <input type="checkbox" class="btn-check" id="chkPresenti" name="chkPresenti" value="1" {$chkPresentiChecked} autocomplete="off" onchange="this.form.submit();">
              <label class="btn {$chkPresentiBtnClass} btn-sm d-inline-flex align-items-center gap-2 px-2 py-1" for="chkPresenti">
                <i class="bi bi-person-fill-check"></i> Presenti
              </label>
              <input type="checkbox" class="btn-check" id="chkError" name="chkError" value="1" {$chkError} autocomplete="off" onchange="this.form.submit();">
              <label class="btn {$chkErrorBtnClass} btn-sm d-inline-flex align-items-center gap-2 px-2 py-1" for="chkError">
                <i class="bi bi-exclamation-triangle-fill"></i> Errori
              </label>
            </div>
          </div>
          <!-- Colonna 2: Totali -->
          <div class="col-md-4 d-flex justify-content-center">
            <div class="bg-light border rounded shadow-sm px-3 py-1 text-primary fw-bold m-0" style="font-size: 1.20rem;">
              <div class="d-flex flex-row gap-2">
                <div><strong>Attualmente In Pausa:</strong> <span id="totinpausa"></span></div>
              </div>
            </div>
          </div>
		  <div class="col-md-4">              </div>

          <!-- Colonna 3: Filtri e bottoni -->
          <div class="col-md-4 d-flex align-items-end">
            <div class="row w-100 g-0 align-items-end" style="line-height: 0;">
              <div class="col-md-4" style="max-width: 150px;">
                <input type="date" id="filtDate" name="filtDate" class="form-control" style="width: 100%;" value="{$filtDate}" onchange="this.form.submit();">
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
		// Make sure it's case insensitive
		$xlSegmentToWrite = strtolower($xlSegmentToWrite);

		// Output the requested segment:

if ($xlSegmentToWrite == "titoloinpausa") {

			echo <<<SEGDTA
				<thead>
		<tr> 
		<tr style="background-color:#92a2a8;color:white;">
 <!----	<td colspan="10"><strong>Attualmente In Pausa:</strong></td> ----->
</tr>
<th style="width: 110px;">In Pausa:</th>		
		<th>Cognome Nome</th>
		<th>ID Badge</th>
		<th>Inizio Pausa 1</th>
		<th>Fine Pausa 1</th>
		<th>Inizio Pausa 2</th>
		<th>Fine Pausa 2</th>
		<th>Inizio Pausa 3</th>
		<th>Fine Pausa 3</th>
		<th>Totale Pausa</th>
					</tr>
				</thead>
				<tbody>
SEGDTA;
			return;
		}

		if ($xlSegmentToWrite == "titolopausafatta") {

			echo <<<SEGDTA
				<thead>
		<tr> 
		<tr style="background-color:#92a2a8;color:white;">
				<th></th>

  <!----	<td colspan="10"><strong>Che hanno fatto Pausa:</strong></td> ----->
</tr>
<th style="width: 110px;">Pause Fatte:</th>		
<th>Cognome Nome</th>
		<th>ID Badge</th>
		<th>Inizio Pausa 1</th>
		<th>Fine Pausa 1</th>
		<th>Inizio Pausa 2</th>
		<th>Fine Pausa 2</th>
		<th>Inizio Pausa 3</th>
		<th>Fine Pausa 3</th>
		<th>Totale Pausa</th>
					</tr>
				</thead>
				<tbody>
SEGDTA;
			return;
		}


		if ($xlSegmentToWrite == "hpresenti") {

			echo <<<SEGDTA
				<thead>
						<tr style="background-color:#92a2a8;color:white;">
 <!----	<td colspan="9"><strong>Presenti: </strong></td> ----->
 				<th></th>

</tr>
			<tr>
			<th style="width: 50px;">Dipendenti:</th>		
				<th>Cognome Nome</th>
				<th>ID Badge</th>
				<th>Orario 1</th>
				<th>Orario 2</th>
				<th>Orario 3</th>
				<th>Orario 4</th>
				<th>Orario 5</th>
				<th>Orario 6</th>
			</tr>
		</thead>
		<tbody>
SEGDTA;
			return;
		}

        if ($xlSegmentToWrite == "rigainpausa") {
            // idorin: array (può essere non definito per vecchi record)
            // Inizializza gli array a 6 elementi
            $idOrin = array_pad($idorin ?? [], 6, '');
            $senso = array_pad($presSens ?? $senso ?? [], 6, '');
            $tipo = array_pad($presTipo ?? $tipo ?? [], 6, '');
            $tipo1 = $tipo[0] ?? '';
            $tipo2 = $tipo[1] ?? '';
            $tipo3 = $tipo[2] ?? '';
            $tipo4 = $tipo[3] ?? '';
            $tipo5 = $tipo[4] ?? '';
            $tipo6 = $tipo[5] ?? '';
            $senso1 = $senso[0] ?? '';
            $senso2 = $senso[1] ?? '';
            $senso3 = $senso[2] ?? '';
            $senso4 = $senso[3] ?? '';
            $senso5 = $senso[4] ?? '';
            $senso6 = $senso[5] ?? '';
            // Safe access for fixed array indexes
            $senso3 = $senso[2] ?? '';
            $senso4 = $senso[3] ?? '';
            $senso5 = $senso[4] ?? '';
            $senso6 = $senso[5] ?? '';
            $tipo3 = $tipo[2] ?? '';
            $tipo4 = $tipo[3] ?? '';
            $tipo5 = $tipo[4] ?? '';
            $tipo6 = $tipo[5] ?? '';
            $IdOrin3 = $idOrin[2] ?? '';
            $IdOrin4 = $idOrin[3] ?? '';
            $IdOrin5 = $idOrin[4] ?? '';
            $IdOrin6 = $idOrin[5] ?? '';
            echo "<tr>
    <td>
        <button onclick=\"openGestioneDipendente('{$IdGest}')\" title=\"Gestione Dipendente\"
            class=\"btn btn-primary btn-sm px-2 py-0\" style=\"font-size: 1.35rem; line-height: 1;\">
            <i class=\"bi bi-credit-card\"></i>
        </button>
        <button onclick=\"openPopupTimbratura('{$IdGest}')\" title=\"Aggiungi timbratura\"
            class=\"btn btn-warning btn-sm px-2 py-0\" style=\"font-size: 1.35rem; line-height: 1;\">
            <i class=\"bi bi-plus-circle\"></i>
        </button>
    </td>
<td>
  <a href=\"#\" onclick=\"filtraPerNomeCognome('{$presCogn}', '{$presNome}'); return false;\" title=\"Filtra per questo utente\">
    {$presCogn} {$presNome} 
  </a>
</td>
<td>{$IdGest}</td>
    {$this->renderDropdownMenu($ora1, "$errpausa1lunga $errIdTimbDeid", $idOrin[0], $DeId1, $tipo1, $senso1)}
    {$this->renderDropdownMenu($ora2, "$errpausa1lunga $errIdTimbDeid", $idOrin[1], $DeId2, $tipo2, $senso2)}
    {$this->renderDropdownMenu($ora3, "$errpausa2lunga $errIdTimbDeid", $idOrin[2], $DeId3, $tipo3, $senso3)}
    {$this->renderDropdownMenu($ora4, "$errpausa2lunga $errIdTimbDeid", $idOrin[3], $DeId4, $tipo4, $senso4)}
    {$this->renderDropdownMenu($ora5, "$errtroppepausa $errIdTimbDeid", $idOrin[4], $DeId5, $tipo5, $senso5)}
    {$this->renderDropdownMenu($ora6, "$errtroppepausa $errIdTimbDeid", $idOrin[5], $DeId6, $tipo6, $senso6)}
    <td $errtotPauseMinuti>{$totPauseMinuti}</td>
</tr>";
            return;
        }

        if ($xlSegmentToWrite == "rigapausafatta") {
            // Inizializza le variabili di errore per evitare undefined variable
            $errinpausaattuale = $errpausa1lunga = $errpausa2lunga = $errtroppepausa = $errtotPauseMinuti = '';
            $idOrin = array_pad($idorin ?? [], 6, '');
            $senso = array_pad($presSens ?? $senso ?? [], 6, '');
            $tipo = array_pad($presTipo ?? $tipo ?? [], 6, '');
            $tipo1 = $tipo[0] ?? '';
            $tipo2 = $tipo[1] ?? '';
            $tipo3 = $tipo[2] ?? '';
            $tipo4 = $tipo[3] ?? '';
            $tipo5 = $tipo[4] ?? '';
            $tipo6 = $tipo[5] ?? '';
            $senso1 = $senso[0] ?? '';
            $senso2 = $senso[1] ?? '';
            $senso3 = $senso[2] ?? '';
            $senso4 = $senso[3] ?? '';
            $senso5 = $senso[4] ?? '';
            $senso6 = $senso[5] ?? '';
            // Safe access for fixed array indexes
            $senso3 = $senso[2] ?? '';
            $senso4 = $senso[3] ?? '';
            $senso5 = $senso[4] ?? '';
            $senso6 = $senso[5] ?? '';
            $tipo3 = $tipo[2] ?? '';
            $tipo4 = $tipo[3] ?? '';
            $tipo5 = $tipo[4] ?? '';
            $tipo6 = $tipo[5] ?? '';
            $IdOrin3 = $idOrin[2] ?? '';
            $IdOrin4 = $idOrin[3] ?? '';
            $IdOrin5 = $idOrin[4] ?? '';
            $IdOrin6 = $idOrin[5] ?? '';
            echo "<tr>
    <td>
        <button onclick=\"openGestioneDipendente('{$IdGest}')\" title=\"Gestione Dipendente\"
            class=\"btn btn-primary btn-sm px-2 py-0\" style=\"font-size: 1.35rem; line-height: 1;\">
            <i class=\"bi bi-credit-card\"></i>
        </button>
        <button onclick=\"openPopupTimbratura('{$IdGest}')\" title=\"Aggiungi timbratura\"
            class=\"btn btn-warning btn-sm px-2 py-0\" style=\"font-size: 1.35rem; line-height: 1;\">
            <i class=\"bi bi-plus-circle\"></i>
        </button>
    </td>
<td $errinpausaattuale>
  <a href=\"#\" onclick=\"filtraPerNomeCognome('{$presCogn}', '{$presNome}'); return false;\" title=\"Filtra per questo utente\">
    {$presCogn} {$presNome} {$inPausaAttuale}
	  </a>
</td>
<td>{$IdGest}</td>  
    {$this->renderDropdownMenu($ora1, "$errpausa1lunga $errIdTimbDeid", $idOrin[0], $DeId1, $tipo1, $senso1)}
    {$this->renderDropdownMenu($ora2, "$errpausa1lunga $errIdTimbDeid", $idOrin[1], $DeId2, $tipo2, $senso2)}
    {$this->renderDropdownMenu($ora3, "$errpausa2lunga $errIdTimbDeid", $idOrin[2], $DeId3, $tipo3, $senso3)}
    {$this->renderDropdownMenu($ora4, "$errpausa2lunga $errIdTimbDeid", $idOrin[3], $DeId4, $tipo4, $senso4)}
    {$this->renderDropdownMenu($ora5, "$errtroppepausa $errIdTimbDeid", $idOrin[4], $DeId5, $tipo5, $senso5)}
    {$this->renderDropdownMenu($ora6, "$errtroppepausa $errIdTimbDeid", $idOrin[5], $DeId6, $tipo6, $senso6)}
    <td $errtotPauseMinuti>{$totPauseMinuti}</td>
</tr>";
            return;
        }
        if ($xlSegmentToWrite == "inerrore") {
            $idOrin = array_pad($idorin ?? [], 6, '');
            $senso = array_pad($presSens ?? $senso ?? [], 6, '');
            $tipo = array_pad($presTipo ?? $tipo ?? [], 6, '');
            echo "<tr>
    <td>
        <button onclick=\"openGestioneDipendente('{$IdGest}')\" title=\"Gestione Dipendente\"
            class=\"btn btn-primary btn-sm px-2 py-0\" style=\"font-size: 1.35rem; line-height: 1;\">
            <i class=\"bi bi-credit-card\"></i>
        </button>
        <button onclick=\"openPopupTimbratura('{$IdGest}')\" title=\"Aggiungi timbratura\"
            class=\"btn btn-warning btn-sm px-2 py-0\" style=\"font-size: 1.35rem; line-height: 1;\">
            <i class=\"bi bi-plus-circle\"></i>
        </button>
    </td>
<td $errinpausaattuale>
  <a href=\"#\" onclick=\"filtraPerNomeCognome('{$presCogn}', '{$presNome}'); return false;\" title=\"Filtra per questo utente\">
    {$presCogn} {$presNome} {$inPausaAttuale}
  </a>
</td>
<td>{$IdGest}</td>
    {$this->renderDropdownMenu($ora1, "$errpausa1lunga $errIdTimbDeid", $idOrin[0], $DeId1, $tipo[0], $senso[0])}
    {$this->renderDropdownMenu($ora2, "$errpausa1lunga $errIdTimbDeid", $idOrin[1], $DeId2, $tipo[1], $senso[1])}
    {$this->renderDropdownMenu($ora3, "$errpausa2lunga $errIdTimbDeid", $idOrin[2], $DeId3, $tipo[2], $senso[2])}
    {$this->renderDropdownMenu($ora4, "$errpausa2lunga $errIdTimbDeid", $idOrin[3], $DeId4, $tipo[3], $senso[3])}
    {$this->renderDropdownMenu($ora5, "$errtroppepausa $errIdTimbDeid", $idOrin[4], $DeId5, $tipo[4], $senso[4])}
    {$this->renderDropdownMenu($ora6, "$errtroppepausa $errIdTimbDeid", $idOrin[5], $DeId6, $tipo[5], $senso[5])}
    <td $errtotPauseMinuti>{$totPauseMinuti}</td>
</tr>";
            return;
        }

        if ($xlSegmentToWrite == "totpresenti") {
            if (!empty($presentiInSede)) {
                foreach ($presentiInSede as $presente) {
                    $presNome = htmlspecialchars($presente['nome']);
                    $presCogn = htmlspecialchars($presente['cognome']);
                    $IdGest = htmlspecialchars($presente['IdGest']);
                    $orari = $presente['orari'] ?? [];
                    // Normalizza tutti gli array a 6 elementi
                    $idorin = array_pad($presente['idorin'] ?? [], 6, '');
                    $senso = array_pad($presente['senso'] ?? [], 6, '');
                    $tipo = array_pad($presente['tipo'] ?? [], 6, '');
                    $DeIdArr = array_pad($presente['DeId'] ?? [], 6, '');
                    // Se DeId non è array, crea array con valore ripetuto
                    if (!is_array($presente['DeId'])) {
                        $DeIdArr = array_fill(0, 6, $presente['DeId'] ?? '');
                    }
                    $DeId1 = $DeIdArr[0] ?? '';
                    $DeId2 = $DeIdArr[1] ?? '';
                    $DeId3 = $DeIdArr[2] ?? '';
                    $DeId4 = $DeIdArr[3] ?? '';
                    $DeId5 = $DeIdArr[4] ?? '';
                    $DeId6 = $DeIdArr[5] ?? '';
                    $ora1 = $orari[0] ?? '';
                    $ora2 = $orari[1] ?? '';
                    $ora3 = $orari[2] ?? '';
                    $ora4 = $orari[3] ?? '';
                    $ora5 = $orari[4] ?? '';
                    $ora6 = $orari[5] ?? '';
                    $IdOrin1 = $idorin[0] ?? '';
                    $IdOrin2 = $idorin[1] ?? '';
                    $IdOrin3 = $idorin[2] ?? '';
                    $IdOrin4 = $idorin[3] ?? '';
                    $IdOrin5 = $idorin[4] ?? '';
                    $IdOrin6 = $idorin[5] ?? '';
                    $tipo1 = $tipo[0] ?? '';
                    $tipo2 = $tipo[1] ?? '';
                    $tipo3 = $tipo[2] ?? '';
                    $tipo4 = $tipo[3] ?? '';
                    $tipo5 = $tipo[4] ?? '';
                    $tipo6 = $tipo[5] ?? '';
                    $senso1 = $senso[0] ?? '';
                    $senso2 = $senso[1] ?? '';
                    $senso3 = $senso[2] ?? '';
                    $senso4 = $senso[3] ?? '';
                    $senso5 = $senso[4] ?? '';
                    $senso6 = $senso[5] ?? '';
                    $senso3 = $senso[2] ?? '';
                    $senso4 = $senso[3] ?? '';
                    $senso5 = $senso[4] ?? '';
                    $senso6 = $senso[5] ?? '';
                    $tipo3 = $tipo[2] ?? '';
                    $tipo4 = $tipo[3] ?? '';
                    $tipo5 = $tipo[4] ?? '';
                    $tipo6 = $tipo[5] ?? '';
                    $IdOrin3 = $idorin[2] ?? '';
                    $IdOrin4 = $idorin[3] ?? '';
                    $IdOrin5 = $idorin[4] ?? '';
                    $IdOrin6 = $idorin[5] ?? '';
                    echo "<tr>
    <td>
        <button onclick=\"openGestioneDipendente('{$IdGest}')\" title=\"Gestione Dipendente\"
            class=\"btn btn-primary btn-sm px-2 py-0\" style=\"font-size: 1.35rem; line-height: 1;\">
            <i class=\"bi bi-credit-card\"></i>
        </button>
        <button onclick=\"openPopupTimbratura('{$IdGest}')\" title=\"Aggiungi timbratura\" 
            class=\"btn btn-warning btn-sm px-2 py-0\" style=\"font-size: 1.35rem; line-height: 1;\">
            <i class=\"bi bi-plus-circle\"></i>
        </button>
    </td>
<td>
  <a href=\"#\" onclick=\"filtraPerNomeCognome('{$presCogn}', '{$presNome}'); return false;\" title=\"Filtra per questo utente\">
    {$presCogn} {$presNome}
  </a>
</td>
<td>{$IdGest}</td>
    {$this->renderDropdownMenu($ora1, '', $IdOrin1, $DeId1, $tipo1, $senso1)}
    {$this->renderDropdownMenu($ora2, '', $IdOrin2, $DeId2, $tipo2, $senso2)}
    {$this->renderDropdownMenu($ora3, '', $IdOrin3, $DeId3, $tipo3, $senso3)}
    {$this->renderDropdownMenu($ora4, '', $IdOrin4, $DeId4, $tipo4, $senso4)}
    {$this->renderDropdownMenu($ora5, '', $IdOrin5, $DeId5, $tipo5, $senso5)}
    {$this->renderDropdownMenu($ora6, '', $IdOrin6, $DeId6, $tipo6, $senso6)}    
</tr>
";
                }
            }
            // Aggiungi riga vuota alla fine della tabella per risolvere bug grafico menù
            echo "<tr style='border: none !important;'><td colspan='10' style='height: 150px; border: none !important; background-color: transparent !important;'></td></tr>";
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
    document.getElementById('filtDate').value = new Date().toISOString().split('T')[0];
    document.getElementById('filtCognome').value = '';
    document.getElementById('filtNome').value = '';
	document.getElementById('chkInPausa').checked = true;
    document.getElementById('chkPausaFatta').checked = true;
    document.getElementById('chkPresenti').checked = true;
	document.getElementById('chkError').checked = false;
    document.forms[0].submit();
  }

  setTimeout(function() {
    location.reload();
}, 30000); // 30000 millisecondi = 30 secondi

function openGestioneDipendente(BDCOGE) {
  azioneInAttesa = {
    tipo: 'gestione',
    valore: BDCOGE
  };
  const modal = new bootstrap.Modal(document.getElementById('passwordModal'));
  document.getElementById('inputPasswordModal').value = '';
  modal.show();
  document.getElementById('passwordModal').addEventListener('shown.bs.modal', function () {
    document.getElementById('inputPasswordModal').focus();
  }, { once: true });
}

function openPopupTimbratura(BDBADG) {
  azioneInAttesa = {
    tipo: 'timbratura',
    valore: BDBADG
  };
  const modal = new bootstrap.Modal(document.getElementById('passwordModal'));
  document.getElementById('inputPasswordModal').value = '';
  modal.show();
  document.getElementById('passwordModal').addEventListener('shown.bs.modal', function () {
    document.getElementById('inputPasswordModal').focus();
  }, { once: true });
}
// --- Funzione per conversione timbratura ---
// --- Funzione per conversione timbratura ---
</script>
</script>
    <!-- Modal per richiesta password -->
    <div class="modal fade" id="passwordModal" tabindex="-1" aria-labelledby="passwordModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Inserisci password</h5>
          </div>
          <div class="modal-body">
            <input type="password" id="inputPasswordModal" class="form-control" placeholder="Password">
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
            <button type="button" class="btn btn-primary" onclick="confermaPassword()">Conferma</button>
          </div>
        </div>
      </div>
    </div>

<script>
let azioneInAttesa = null;
let idOrinInAttesa = null;

function verificaPasswordMenu(icon, idOrin) {
  const menu = icon.nextElementSibling;

  if (!menu || !menu.classList.contains("dropdown-menu")) return;

  // Rimuove istanza dropdown se esistente
  const existingDropdown = bootstrap.Dropdown.getInstance(icon);
  if (existingDropdown) {
    existingDropdown.dispose();
  }

  // Rimuove show e data-toggle per reset
  icon.removeAttribute("data-bs-toggle");
  menu.classList.remove("show");

  // Aggiunge toggle e mostra
  icon.setAttribute("data-bs-toggle", "dropdown");
  menu.classList.remove("d-none");

  const dropdown = bootstrap.Dropdown.getOrCreateInstance(icon);
  dropdown.show();
}

function confermaPassword() {
  const pw = document.getElementById('inputPasswordModal').value;
  if (pw !== "rgp123") {
    alert("Password errata.");
    return;
  }

  if (typeof azioneInAttesa === 'string') {
    window.location.href = azioneInAttesa;
  } else if (typeof azioneInAttesa === 'object' && azioneInAttesa !== null) {
    const tipo = azioneInAttesa.tipo;
    const valore = azioneInAttesa.valore;

    if (tipo === 'gestione') {
      const rnd = Math.floor(Math.random() * 99999);
      const url = 'https://jde.rgpballs.com/HR/gestdipe.php?task=beginchange&BDCOGE=' + encodeURIComponent(valore) + '&rnd=' + rnd;
      const width = 700;
      const height = 750;
      const left = (window.screen.width - width) / 2;
      const top = (window.screen.height - height) / 2;
      window.open(url, 'popupGestioneDipendente', `width=${width},height=${height},left=${left},top=${top},resizable=yes,scrollbars=yes`);
    }

    if (tipo === 'timbratura') {
      const width = 600;
      const height = 900;
      const left = (window.screen.width - width) / 2;
      const top = (window.screen.height - height) / 2;
      const url = 'https://jde.rgpballs.com/HR/savManTimbratura.php?BDBADG=' + encodeURIComponent(valore);
      window.open(url, 'popupTimbratura', `width=${width},height=${height},left=${left},top=${top},resizable=yes,scrollbars=yes`);
    }

    if (tipo === 'transfer') {
      const width = 600;
      const height = 600;
      const left = (window.screen.width - width) / 2;
      const top = (window.screen.height - height) / 2;
      const url = 'https://jde.rgpballs.com/timbrature/transfer.php';
      window.open(url, 'popupTransfer', `width=${width},height=${height},left=${left},top=${top},resizable=yes,scrollbars=yes`);
    }
  }

  bootstrap.Modal.getInstance(document.getElementById('passwordModal')).hide();
}

function richiediPasswordPerAzione(url) {
  azioneInAttesa = url;
  const modal = new bootstrap.Modal(document.getElementById('passwordModal'));
  document.getElementById('inputPasswordModal').value = '';
  modal.show();
  document.getElementById('passwordModal').addEventListener('shown.bs.modal', function () {
    document.getElementById('inputPasswordModal').focus();
  }, { once: true });
}

// Gestione pressione INVIO nella modale password
document.addEventListener('DOMContentLoaded', function () {
  const passwordInput = document.getElementById('inputPasswordModal');
  if (passwordInput) {
    passwordInput.addEventListener('keydown', function (event) {
      if (event.key === 'Enter') {
        event.preventDefault();
        confermaPassword();
      }
    });
  }
});

function filtraPerNomeCognome(cognome, nome) {
  document.getElementById('filtNome').value = cognome;
  document.getElementById('filtCognome').value = nome;
  document.forms[0].submit();
}

function apriPopupTransfer() {
  azioneInAttesa = {
    tipo: 'transfer'
  };
  const modal = new bootstrap.Modal(document.getElementById('passwordModal'));
  document.getElementById('inputPasswordModal').value = '';
  modal.show();
  document.getElementById('passwordModal').addEventListener('shown.bs.modal', function () {
    document.getElementById('inputPasswordModal').focus();
  }, { once: true });
}

</script>