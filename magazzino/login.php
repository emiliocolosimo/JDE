<?php
session_start();
require_once 'config.inc.php';

// Se l'utente è già loggato, reindirizza
if (isset($_SESSION['nomeutente'])) {
    header('Location: index.php');
    exit;
}

$errore = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ottieni i dati dei dipendenti
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

    $stmt = odbc_prepare($db_connection, $queryDatiDipendenti);
    if ($stmt === false) {
        error_log("Errore nella preparazione della query: " . odbc_errormsg($db_connection));
        die("Errore di sistema. Contattare l'amministratore.");
    }

    $result = odbc_execute($stmt);
    if ($result === false) {
        error_log("Errore nell'esecuzione della query: " . odbc_errormsg($db_connection));
        die("Errore di sistema. Contattare l'amministratore.");
    }

    $resultsDipendenti = [];
    $credenziali = [];

    while ($riga = odbc_fetch_array($stmt)) {
        $nomeutente = strtolower($riga['NOMEUTENTE']);
        $credenziali[$nomeutente] = $riga['BDPASS'];
        $resultsDipendenti[] = $riga;
    }

    // Verifica login tramite badge
    $badge = strtoupper(substr(trim($_POST['badge'] ?? ''), 0, 16));
    if ($badge !== '') {
        foreach ($resultsDipendenti as $dip) {
            if (strtoupper(trim($dip['BDBADG'])) === $badge || strtoupper(trim($dip['BDBDTM'])) === $badge) {
                foreach ($dip as $key => $value) {
                    $_SESSION[strtolower($key)] = trim($value);
                }
                $_SESSION['nomeutente'] = strtolower(trim($dip['NOMEUTENTE']));
                $_SESSION['nomecompleto'] = ucwords(strtolower(trim($dip['BDNOME']) . ' ' . trim($dip['BDCOGN'])));
                header("Location: index.php");
                exit;
            }
        }
        $errore = "Badge non riconosciuto.";
    } else {
        // Verifica login tramite username/password
        $utente = strtolower(trim($_POST['utente'] ?? ''));
        $password = trim($_POST['password'] ?? '');

        if (isset($credenziali[$utente])) {
            if ($credenziali[$utente] === '' || $credenziali[$utente] === $password) {
                // Trova i dati completi dell'utente
                foreach ($resultsDipendenti as $dip) {
                    if (strtolower($dip['NOMEUTENTE']) === $utente) {
                        foreach ($dip as $key => $value) {
                            $_SESSION[strtolower($key)] = trim($value);
                        }
                        $_SESSION['nomeutente'] = strtolower(trim($dip['NOMEUTENTE']));
                        $_SESSION['nomecompleto'] = ucwords(strtolower(trim($dip['BDNOME']) . ' ' . trim($dip['BDCOGN'])));
                        header("Location: index.php");
                        exit;
                    }
                }
            } else {
                $errore = "Password non corretta.";
            }
        } else {
            $errore = "Utente non valido.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4>Accesso</h4>
                </div>
                <div class="card-body">
                    <?php if ($errore): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($errore) ?></div>
                    <?php endif; ?>

                    <form method="post">
                        <div class="mb-3">
                            <label for="badge" class="form-label">Badge (opzionale)</label>
                            <input type="text" class="form-control" id="badge" name="badge" placeholder="Inserisci badge" autofocus>
                        </div>

                        <div class="mb-3">
                            <label for="utente" class="form-label">Username</label>
                            <input type="text" class="form-control" id="utente" name="utente" placeholder="Inserisci username">
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Inserisci password">
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Accedi</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Inserimento automatico del badge
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
</body>
</html>