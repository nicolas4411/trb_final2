<?php
session_start();

require_once 'db.php';
require_once 'mail.php';  // Inclui o arquivo de envio de e-mail

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Recupera as variáveis do formulário
    if (isset($_POST['email'])) {
        $email = $_POST['email'];

        // Verifica se o e-mail existe no banco de dados
        $sql_check_email = "SELECT * FROM usuarios WHERE email = ?";
        $stmt = $mysqli->prepare($sql_check_email);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // E-mail encontrado, gera o código de recuperação
            $codigo = rand(100000, 999999);  // Código aleatório de 6 dígitos
            $expiracao = date('Y-m-d H:i:s', strtotime('+15 minutes')); // Expiração do código

            // Atualiza o banco de dados com o código de recuperação e sua expiração
            $sql_update = "UPDATE usuarios SET codigo_recuperacao = ?, expiracao_codigo = ? WHERE email = ?";
            $stmt = $mysqli->prepare($sql_update);
            $stmt->bind_param("sss", $codigo, $expiracao, $email);
            $stmt->execute();

            // Envia o código para o e-mail
            $assunto = "Código de Recuperação de Senha";
            $mensagem = "Seu código de recuperação é: $codigo. Ele expira em 15 minutos.";
            $enviado = enviarCodigoEmail($email, $codigo);

            if ($enviado) {
                $_SESSION['message'] = "Um código de recuperação foi enviado para o seu e-mail.";
                header('Location: nova_senha.php'); // Redireciona para a página de verificação de código
                exit();
            } else {
                $_SESSION['error'] = "Erro ao enviar o e-mail de recuperação. Tente novamente.";
                header('Location: rec_senha.php');
                exit();
            }
        } else {
            $_SESSION['error'] = "E-mail não encontrado no sistema.";
            header('Location: rec_senha.php');
            exit();
        }
    }

    // Caso o formulário de verificação de código seja enviado
    if (isset($_POST['codigo'], $_POST['nova_senha'], $_POST['email'])) {
        $codigo = $_POST['codigo'];
        $nova_senha = $_POST['nova_senha'];
        $email = $_POST['email'];

        // Verifica se o código existe e não expirou
        $sql_check_codigo = "SELECT * FROM usuarios WHERE email = ? AND codigo_recuperacao = ? AND expiracao_codigo > NOW()";
        $stmt = $mysqli->prepare($sql_check_codigo);
        $stmt->bind_param("ss", $email, $codigo);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Verifica se a nova senha é diferente da antiga
            $usuario = $result->fetch_assoc();
            if (password_verify($nova_senha, $usuario['senha'])) {
                $_SESSION['error'] = "A nova senha não pode ser a mesma que a anterior.";
                header('Location: nova_senha.php');
                exit();
            } else {
                // Atualiza a senha no banco
                $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
                $sql_update_senha = "UPDATE usuarios SET senha = ?, codigo_recuperacao = NULL, expiracao_codigo = NULL WHERE email = ?";
                $stmt = $mysqli->prepare($sql_update_senha);
                $stmt->bind_param("ss", $senha_hash, $email);
                $stmt->execute();

                $_SESSION['success'] = "Senha alterada com sucesso!";
                header('Location: login.php'); // Redireciona para a página de login
                exit();
            }
        } else {
            $_SESSION['error'] = "Código inválido ou expirado.";
            header('Location: rec_senha.php');
            exit();
        }
    }
}

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style/css.css">
    <title>Recuperação de Senha</title>
</head>

<body>

    <?php
    // Exibindo mensagens de erro ou sucesso
    if (isset($_SESSION['error'])) {
        echo "<p style='color:red;'>" . $_SESSION['error'] . "</p>";
        unset($_SESSION['error']);
    }
    if (isset($_SESSION['message'])) {
        echo "<p style='color:green;'>" . $_SESSION['message'] . "</p>";
        unset($_SESSION['message']);
    }
    ?>

    

    <!-- Formulário de envio de e-mail para recuperar a senha -->
    <form action="rec_senha.php" method="post">
        <label for="email">Digite seu e-mail:</label><br>
        <input type="email" id="email" name="email" required><br><br>
        <input type="submit" value="Recuperar Senha">
    </form>

    <form action="login.php" method="get">
        <button type="submit">login</button>
    </form>

    <form action="register.php" method="get" style="margin-top: 20px;">
        <button type="submit"
            style="padding: 10px 15px; background: #555; color: white; border: none; border-radius: 4px; cursor: pointer;">registrar-se</button>
    </form>



</body>

</html>