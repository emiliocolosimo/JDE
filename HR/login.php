<?php
session_start();

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
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
</head>
<body>
    <h2>Login</h2>
    <?php if (isset($errore)) echo "<p style='color:red;'>$errore</p>"; ?>
    <form method="POST">
        <label>Utente: <input type="text" name="utente" required></label><br>
        <label>Password: <input type="password" name="password" required></label><br>
        <button type="submit">Accedi</button>
    </form>
</body>
</html>