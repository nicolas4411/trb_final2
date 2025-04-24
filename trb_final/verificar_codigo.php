<?php
session_start();

if (!isset($_SESSION['userid'])) {
    header('Location: login.php');
    exit();
}

require_once 'db.php';

$userid = $_SESSION['userid'];
$codigo = $_POST['codigo'];

// Verifica código de autenticação no banco de dados
$sql = "SELECT codigo_autenticacao FROM usuarios WHERE id=$userid";
$result = $mysqli->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $codigo_autenticacao_bd = $row['codigo_autenticacao'];

    if ($codigo == $codigo_autenticacao_bd) {
        // Código correto, conclui autenticação em duas etapas
        $sql_update = "UPDATE usuarios SET codigo_autenticacao=NULL WHERE id=$userid";
        if ($mysqli->query($sql_update) === TRUE) {
            $_SESSION['message'] = "Autenticação em duas etapas concluída!";
            header('Location: dashboard.php');
            exit();
        } else {
            $_SESSION['error'] = "Erro ao concluir autenticação em duas etapas: " . $mysqli->error;
        }
    } else {
        $_SESSION['error'] = "Código de autenticação incorreto!";
    }
} else {
    $_SESSION['error'] = "Erro ao verificar código de autenticação.";
}

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style/css.css">
    <title>Verificar Código de Autenticação</title>
</head>
<body>
    <h2>Verificar Código de Autenticação</h2>
    <?php if (isset($_SESSION['error'])): ?>
        <p style="color: red;"><?php echo $_SESSION['error']; ?></p>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['message'])): ?>
        <p style="color: green;"><?php echo $_SESSION['message']; ?></p>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>
</body>
</html>
