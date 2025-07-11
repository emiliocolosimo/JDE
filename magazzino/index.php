
<?php
session_start();

// Gestione logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit;
}

if (!isset($_SESSION['nomeutente'])) {
    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard Dipendente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-4">
<h3 class="mb-4">Benvenuto, <?= htmlspecialchars($_SESSION['nomeutente']) . htmlspecialchars($_SESSION['bdreli'])?>!</h3>

<div class="row g-3">
    <?php if ($_SESSION['bdauth'] === 'UFFICIOCOMMERCIALE'): ?>
        <div class="col-md-4">
            <a href="sospendiliste.php" class="btn btn-primary w-100">📋 Sospendi Liste</a>
        </div>
    <?php endif; ?>

    <div class="col-md-4">
        <a href="trackliste.php" class="btn btn-success w-100">📦 Track Liste</a>
    </div>

    <div class="col-md-4">
        <a href="presenze.php" class="btn btn-secondary w-100">🕓 Presenze</a>
    </div>

    <div class="col-md-4">
        <a href="?logout=1" class="btn btn-danger w-100">🚪 Logout</a>
    </div>
</div>
</body>
</html>