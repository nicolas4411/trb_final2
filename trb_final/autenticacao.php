<?php
session_start();

require_once 'db.php';
require_once 'mail.php';  // Inclui o arquivo de envio de e-mail

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $senha = $_POST['senha'];
    $confirm_senha = $_POST['confirm_senha'];

    // Verifica se as senhas coincidem
    if ($senha !== $confirm_senha) {
        $_SESSION['error'] = "As senhas não coincidem. Por favor, tente novamente.";
        header('Location: register.php');
        exit();
    }

    $user_id = $_SESSION['user_id'];
    $codigo_autenticacao = null;

    $sql = "SELECT codigo_autenticacao FROM usuarios WHERE id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($codigo_autenticacao);
    $stmt->fetch();
    $stmt->close();

    // Verifica se o usuário já existe
    $sql_check_user = "SELECT * FROM usuarios WHERE username=? OR email=?";
    $stmt_check = $mysqli->prepare($sql_check_user);
    $stmt_check->bind_param("ss", $username, $email);
    $stmt_check->execute();
    $result_check_user = $stmt_check->get_result();

    if ($result_check_user->num_rows > 0) {
        $_SESSION['error'] = "Usuário ou e-mail já registrado. Por favor, escolha outro.";
        header('Location: register.php');
        exit();
    }

    // Hash da senha
    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

    // Inserção do usuário
    $sql_insert = "INSERT INTO usuarios (username, email, senha) VALUES (?, ?, ?)";
    $stmt_insert = $mysqli->prepare($sql_insert);
    $stmt_insert->bind_param("sss", $username, $email, $senha_hash);

    if ($stmt_insert->execute()) {
        $userid = $stmt_insert->insert_id;

        // Cria sessão de login automaticamente
        $_SESSION['user_id'] = $userid;
        $_SESSION['username'] = $username;

        // Se autenticação em duas etapas estiver ativada
        if (isset($_POST['autenticacao_duas_etapas']) && $_POST['autenticacao_duas_etapas'] == 1) {
            $codigo_autenticacao = rand(100000, 999999);

            $sql_update = "UPDATE usuarios SET autenticacao_habilitada=1, codigo_autenticacao=? WHERE id=?";
            $stmt_update = $mysqli->prepare($sql_update);
            $stmt_update->bind_param("ii", $codigo_autenticacao, $userid);
            $stmt_update->execute();

            // Envia o código
            $enviado = enviarCodigoEmail($email, $codigo_autenticacao);

            if ($enviado) {
                $_SESSION['message'] = "Autenticação em duas etapas habilitada. Um código foi enviado ao seu e-mail.";
                header('Location: autenticacao.php');
                exit();
            } else {
                $_SESSION['error'] = "Erro ao enviar o e-mail de verificação.";
                header('Location: register.php');
                exit();
            }
        } else {
            $_SESSION['success'] = "Usuário registrado com sucesso. Faça login.";
            header('Location: login.php');
            exit();
        }
    } else {
        $_SESSION['error'] = "Erro ao registrar o usuário: " . $mysqli->error;
    }
}

$mysqli->close();
?>


<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Autenticação em Duas Etapas</title>
    <!-- CSS do modal (pode usar um framework como Bootstrap para facilitar) -->
    <link rel="stylesheet" href="style/css.css">
    <style>
        /* Estilo para centralizar o modal */
        .modal-dialog-centered {
            display: flex;
            align-items: center;
            min-height: calc(100% - 1rem);
        }
    </style>
</head>

<body>
    <div class="container mt-5">
        <h2>Autenticação em Duas Etapas</h2>
        <?php if (isset($_SESSION['error'])): ?>
            <p style="color: red;"><?php echo $_SESSION['error']; ?></p>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['message'])): ?>
            <p style="color: green;"><?php echo $_SESSION['message']; ?></p>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>
        <p>Um código de autenticação foi enviado para você. Por favor, insira o código abaixo:</p>
        <form action="verificar_codigo.php" method="post">
            <label for="codigo">Código de Autenticação:</label><br>
            <input type="text" id="codigo" name="codigo" required><br><br>
            <input type="submit" value="Verificar Código">
            <!-- Botão para abrir o modal -->

        </form>

        <form action="login.php" method="get">
            <button type="submit">login</button>
        </form>
    </div>

    <!-- Modal -->

    <div class="modal fade" id="modalCodigo" tabindex="-1" role="dialog" aria-labelledby="modalCodigoLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                   

                </div>
 

            </div>
        </div>
    </div>




    <!-- JavaScript do Bootstrap (necessário para funcionamento do modal) -->

</body>

</html>

