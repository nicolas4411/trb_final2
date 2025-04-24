<?php
session_start();

require_once 'db.php';
require_once 'mail.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $senha = $_POST['senha'];
    $confirm_senha = $_POST['confirm_senha'];
    $aceite_termos = isset($_POST['aceite_termos']) ? 1 : 0;

    // Verifica se o usuário aceitou os termos
    if (!$aceite_termos) {
        $_SESSION['error'] = "Você deve aceitar os termos de uso para se registrar.";
        header('Location: register.php');
        exit();
    }

    // Verifica se as senhas coincidem
    if ($senha !== $confirm_senha) {
        $_SESSION['error'] = "As senhas não coincidem. Por favor, tente novamente.";
        header('Location: register.php');
        exit();
    }

    // Verifica se o usuário já existe pelo username ou email
    $sql_check_user = "SELECT * FROM usuarios WHERE username='$username' OR email='$email'";
    $result_check_user = $mysqli->query($sql_check_user);

    if ($result_check_user->num_rows > 0) {
        $_SESSION['error'] = "Usuário ou e-mail já registrado. Por favor, escolha outro.";
        header('Location: register.php');
        exit();
    }

    // Hash da senha
    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

    // Inserção do usuário no banco de dados
    $sql = "INSERT INTO usuarios (username, email, senha, aceite_termos) VALUES ('$username', '$email', '$senha_hash', $aceite_termos)";
    if ($mysqli->query($sql) === TRUE) {
        $_SESSION['success'] = "Usuário registrado com sucesso!";

        // Verifica se o usuário optou por habilitar autenticação em duas etapas
        if (isset($_POST['autenticacao_duas_etapas']) && $_POST['autenticacao_duas_etapas'] == 1) {
            $userid = $mysqli->insert_id; // Obtém o ID do usuário recém-inserido
            $codigo_autenticacao = rand(100000, 999999); // Gera um código de autenticação aleatório

            // Atualiza o banco de dados para habilitar autenticação em duas etapas
            $sql_update = "UPDATE usuarios SET autenticacao_habilitada=1, codigo_autenticacao='$codigo_autenticacao' WHERE id=$userid";
            $mysqli->query($sql_update);

            // Envia o código de autenticação para o e-mail do usuário
            $enviado = enviarCodigoEmail($email, $codigo_autenticacao);

            $_SESSION['message'] = "Autenticação em duas etapas habilitada. Um código de autenticação foi enviado para você.";
            header('Location: autenticacao.php');
            exit();
        } else {
            // Redireciona diretamente para o login
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
    <title>Registro de Usuário</title>
    <link rel="stylesheet" href="style/register_negro.css">

</head>

<body>
    <h2>Registro de Usuário</h2>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="error"><?php echo $_SESSION['error']; ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['success'])): ?>
        <div class="success"><?php echo $_SESSION['success']; ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <form action="register.php" method="post" onsubmit="return verificarSenha();">
        <label for="username">Nome de Usuário:</label>
        <input type="text" id="username" name="username" required>

        <label for="email">E-mail:</label>
        <input type="email" id="email" name="email" required>

        <div class="termos-container">
            <label class="termos-label">
                <input type="checkbox" name="aceite_termos" id="aceite_termos" required>
                <span class="termos-texto">Eu aceito os <a href="termos.php" class="termos-link" target="_blank">Termos
                        de Uso</a> e Política de Privacidade</span>
            </label>
            
            <button type="button" class="collapse-button" aria-expanded="false" aria-controls="termos-detalhes">
                detalhes
            </button>
            
            <div id="termos-detalhes" class="collapse-content">
                <h3>Detalhes dos Termos de Uso</h3>
                <p>Ao se registrar, você concorda com os seguintes termos:</p>
                <ul>
                    <li>Você é responsável por manter a confidencialidade de sua conta e senha</li>
                    <li>Você concorda em não usar o serviço para atividades ilegais</li>
                    <li>Nós reservamos o direito de modificar estes termos a qualquer momento</li>
                    <li>Seu dados pessoais serão tratados conforme nossa Política de Privacidade</li>
                </ul>
                
            </div>
        </div>

        <label for="senha">Senha:</label>
        <input type="password" id="senha" name="senha" required oninput="verificarSenha();">

        <label for="confirm_senha">Confirme a Senha:</label>
        <input type="password" id="confirm_senha" name="confirm_senha" required oninput="verificarSenha();">
        <span id="mensagem-senha"></span>

        <label>
            <input type="checkbox" name="autenticacao_duas_etapas" value="1"> Habilitar Autenticação em Duas Etapas
        </label>

        <br><br>
        <input type="submit" value="Registrar">
    </form>

    <form action="login.php" method="get" style="margin-top: 20px;">
        <button type="submit"
            style="padding: 10px 15px; background: #555; color: white; border: none; border-radius: 4px; cursor: pointer;">Já
            tem conta? Faça login</button>
    </form>

    

    <script>
        function verificarSenha() {
            var senha = document.getElementById('senha').value;
            var confirmSenha = document.getElementById('confirm_senha').value;
            var mensagem = document.getElementById('mensagem-senha');
            var forte = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;

            // Verifica se o aceite dos termos foi marcado
            if (!document.getElementById('aceite_termos').checked) {
                alert("Você deve aceitar os termos de uso para se registrar.");
                return false;
            }

            if (senha !== confirmSenha) {
                mensagem.style.color = 'red';
                mensagem.textContent = 'As senhas não coincidem.';
                return false;
            }

            if (forte.test(senha)) {
                mensagem.style.color = 'green';
                mensagem.textContent = 'Senha forte.';
                return true;
            } else {
                mensagem.style.color = 'red';
                mensagem.textContent = 'A senha deve ter pelo menos 8 caracteres, incluindo letras maiúsculas, minúsculas, números e caracteres especiais.';
                return false;
            }
        }
        
        // Adiciona funcionalidade ao botão de collapse
        document.addEventListener('DOMContentLoaded', function() {
            const collapseButton = document.querySelector('.collapse-button');
            const collapseContent = document.getElementById('termos-detalhes');
            
            collapseButton.addEventListener('click', function() {
                const isExpanded = this.getAttribute('aria-expanded') === 'true';
                
                // Atualiza o estado ARIA
                this.setAttribute('aria-expanded', !isExpanded);
                
                // Alterna a classe para animação
                collapseContent.classList.toggle('active');
            });
        });
    </script>
</body>

</html>