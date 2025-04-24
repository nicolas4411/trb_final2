<?php
session_start(); // Inicia a sessão para usar variáveis de sessão

require_once 'db.php'; // Conexão com MySQLi
require_once 'mail.php';

/**
 * Atualiza a senha do usuário no banco de dados.
 *
 * @param mysqli $mysqli Conexão MySQLi
 * @param string $email E-mail do usuário
 * @param string $senha_hash Senha já criptografada
 * @return bool True se a atualização foi bem-sucedida, False caso contrário
 */
function atualizarSenha($mysqli, $email, $senha_hash)
{
    $updateQuery = "UPDATE usuarios SET senha = ?, codigo_recuperacao = NULL, expiracao_codigo = NULL WHERE email = ?";
    $stmt = $mysqli->prepare($updateQuery);

    if ($stmt) {
        $stmt->bind_param("ss", $senha_hash, $email);
        $resultado = $stmt->execute();
        $stmt->close();
        return $resultado;
    }

    return false;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $codigo = trim($_POST['codigo']);
    $nova_senha = trim($_POST['nova_senha']);
    $confirm_senha = trim($_POST['confirm_senha']);
    $email = trim($_POST['email']);

    // Validação dos campos
    if (empty($codigo) || empty($nova_senha) || empty($confirm_senha) || empty($email)) {
        $_SESSION['erro'] = "Todos os campos são obrigatórios.";
        header('Location: nova_senha.php');
        exit();
    }

    // Verifica se as senhas coincidem
    if ($nova_senha !== $confirm_senha) {
        $_SESSION['erro'] = "As senhas não coincidem.";
        header('Location: nova_senha.php');
        exit();
    }

    // Verifica força da senha (opcional)
    if (strlen($nova_senha) < 8) {
        $_SESSION['erro'] = "A senha deve ter pelo menos 8 caracteres.";
        header('Location: nova_senha.php');
        exit();
    }

    $query = "SELECT * FROM usuarios WHERE email = ? AND codigo_recuperacao = ? AND expiracao_codigo > NOW()";
    $stmt = $mysqli->prepare($query);

    if ($stmt) {
        $stmt->bind_param("ss", $email, $codigo);
        $stmt->execute();
        $result = $stmt->get_result();
        $usuario = $result->fetch_assoc();
        $stmt->close();
    } else {
        $_SESSION['erro'] = "Erro ao preparar a consulta.";
        header('Location: nova_senha.php');
        exit();
    }

    if ($usuario) {
        // Verifica se a nova senha é igual à antiga
        if (password_verify($nova_senha, $usuario['senha'])) {
            $_SESSION['erro'] = "A nova senha não pode ser a mesma que a antiga.";
            header('Location: nova_senha.php');
            exit();
        }

        // Cria o hash da nova senha
        $senha_hash = password_hash($nova_senha, PASSWORD_BCRYPT);

        if (atualizarSenha($mysqli, $email, $senha_hash)) {
            $_SESSION['sucesso'] = "Senha alterada com sucesso! Agora você pode fazer login.";
            header('Location: login.php');
            exit();
        } else {
            $_SESSION['erro'] = "Erro ao atualizar a senha. Tente novamente.";
            header('Location: nova_senha.php');
            exit();
        }
    } else {
        $_SESSION['erro'] = "Código inválido ou expirado.";
        header('Location: nova_senha.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperação de Senha - Nova Senha</title>
    <link rel="stylesheet" href="style/css.css">
</head>
<body>
    <div class="container">
        <h1>Criar Nova Senha</h1>
        
        <?php if (isset($_SESSION['erro'])): ?>
            <div class="erro"><?php echo $_SESSION['erro']; unset($_SESSION['erro']); ?></div>
        <?php endif; ?>

        <?php if (isset($_SESSION['sucesso'])): ?>
            <div class="sucesso"><?php echo $_SESSION['sucesso']; unset($_SESSION['sucesso']); ?></div>
        <?php endif; ?>

        <form action="nova_senha.php" method="post">
            <div class="form-group">
                <label for="email">E-mail:</label>
                <input type="email" id="email" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="codigo">Código de Recuperação:</label>
                <input type="text" id="codigo" name="codigo" required>
            </div>

            <div class="form-group">
                <label for="nova_senha">Nova Senha:</label>
                <input type="password" id="nova_senha" name="nova_senha" required oninput="verificarSenha()">
                <div id="senha-feedback"></div>
            </div>

            <div class="form-group">
                <label for="confirm_senha">Confirme a Senha:</label>
                <input type="password" id="confirm_senha" name="confirm_senha" required oninput="verificarSenha()">
                <div id="confirmacao-senha"></div>
            </div>

            <input type="submit" value="Alterar Senha">
        </form>

        <div class="links">
            <a href="login.php" class="btn-login">Voltar para Login</a>
        </div>
    </div>

    <script>
        function verificarSenha() {
            const senha = document.getElementById('nova_senha').value;
            const confirmacao = document.getElementById('confirm_senha').value;
            const feedback = document.getElementById('senha-feedback');
            const confirmacaoMsg = document.getElementById('confirmacao-senha');

            // Verificação de força da senha
            if (senha.length > 0) {
                let forca = 0;
                if (senha.length >= 8) forca++;
                if (senha.match(/[A-Z]/)) forca++;
                if (senha.match(/[0-9]/)) forca++;
                if (senha.match(/[^A-Za-z0-9]/)) forca++;

                feedback.style.display = 'block';
                if (forca < 2) {
                    feedback.textContent = 'Senha fraca';
                    feedback.className = 'senha-fraca';
                } else if (forca < 4) {
                    feedback.textContent = 'Senha média';
                    feedback.className = 'senha-media';
                } else {
                    feedback.textContent = 'Senha forte';
                    feedback.className = 'senha-forte';
                }
            } else {
                feedback.style.display = 'none';
            }

            // Verificação de confirmação
            if (confirmacao.length > 0) {
                if (senha === confirmacao && senha.length > 0) {
                    confirmacaoMsg.textContent = 'Senhas coincidem';
                    confirmacaoMsg.className = 'senha-forte';
                } else if (senha.length > 0) {
                    confirmacaoMsg.textContent = 'Senhas não coincidem';
                    confirmacaoMsg.className = 'senha-fraca';
                }
            } else {
                confirmacaoMsg.textContent = '';
            }
        }
    </script>
</body>
</html>