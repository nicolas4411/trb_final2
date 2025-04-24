<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'src/PHPMailer.php';
require 'src/SMTP.php';
require 'src/Exception.php';

function enviarCodigoEmail($destinatario, $codigo) {  
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; 
        $mail->SMTPAuth = true;
      		
		$mail->Username = 'danielcarlosferreiradasilva@gmail.com'; // Preencher com o e-mail para envio
        $mail->Password = 'erum hzzx ywcd ziih'; // Preencher com a senha do app
		
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;
		
		// Define o idioma e o charset
        $mail->setLanguage('pt_br');
        $mail->CharSet = 'UTF-8';

        $mail->setFrom('danielcarlosferreiradasilva@gmail.com', 'Sistema de Login'); // Preencher com e-mail para envio
        $mail->addAddress($destinatario);

        $mail->isHTML(true);
        $mail->Subject = 'Código de Verificação';
        $mail->Body = "Seu código de verificação é: <b>$codigo</b>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}
?>


