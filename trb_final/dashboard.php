<?php
session_start();

if (!isset($_SESSION['userid'])) {
    header('Location: login.php');
    exit();
}

$username = $_SESSION['username'];

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="style/css.css">
    <title>Dashboard</title>
</head>
<body>
    <h2>Dashboard</h2>
    <p>Bem-vindo, <?php echo $username; ?>!</p>
    <p><a href="logout.php">Sair</a></p>
</body>
</html>
