<?php 
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

require_once __DIR__ . '/../vendor/autoload.php';


$dotenv = Dotenv::createImmutable(dirname(__DIR__), 'passconfig.env');
$dotenv->load();

function enviarBienvenida($correo) {
    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $_ENV['SMTP_HOST'];
        $mail->SMTPAuth = true;
        $mail->Username = $_ENV['SMTP_USER'];  
        $mail->Password = $_ENV['SMTP_PASS'];
        $mail->SMTPSecure = 'tls';
        $mail->Port = $_ENV['SMTP_PORT'];

        $mail->setFrom($_ENV['SMTP_USER'], 'Love Makeup');
        $mail->addAddress($correo);
        $mail->Subject = 'Love Makeup Tienda | Bienvenido';
        $mail->isHTML(true); // Habilitar HTML en el correo

    $mail->Body = "
<html>
  <head>
    <meta charset='UTF-8' />
    <style>
      body {
        background-color: #fff8f4;
        font-family: 'Arial', sans-serif;
        margin: 0;
        padding: 0;
        color: #3b3b3b;
      }

      .container {
        max-width: 600px;
        margin: auto;
        background-color: #ffffff;
        border: 1px solid #f0d5cd;
        padding: 30px;
      }

      .logo {
        text-align: center;
        margin-bottom: 20px;
      }

      .logo img {
        max-width: 150px;
      }

      h1 {
        color: #fc81e7ff;
        text-align: center;
        font-size: 24px;
      }

      p {
        font-size: 16px;
        line-height: 1.6;
        text-align: center;
      }

      .button {
        display: inline-block;
        background-color: #fd44f4ff;
        color: #ffffff;
        padding: 10px 20px;
        margin-top: 20px;
        text-decoration: none;
        border-radius: 4px;
        font-weight: bold;
      }

      .footer {
        margin-top: 30px;
        font-size: 12px;
        text-align: center;
        color: #999999;
      }
    </style>
  </head>
  <body>
    <div class='container'>
     
      <h1>¡Gracias por registrarte!</h1>
      <p>
        Bienvenida a nuestra tienda, donde cada producto está pensado para resaltar lo mejor de ti.
        Pronto recibirás noticias sobre ofertas exclusivas y nuevas colecciones.
      </p>
      <p>
        <a href='https://lovemakeuptienda.com/' class='button'>Explora ahora</a>
      </p>

         <!-- Redes Sociales -->
        <div style='text-align: center; margin-top: 30px;'>
        <a href='https://www.instagram.com/lovemakeupyk/' target='_blank' style='margin: 0 10px;'>
            <img src='https://cdn-icons-png.flaticon.com/24/1384/1384031.png' alt='Instagram' style='vertical-align: middle;'>
        </a>
        <a href='https://www.facebook.com/lovemakeupyk/' target='_blank' style='margin: 0 10px;'>
            <img src='https://cdn-icons-png.flaticon.com/24/1384/1384005.png' alt='Facebook' style='vertical-align: middle;'>
        </a>
        <a href='https://wa.me/584245115414' target='_blank' style='margin: 0 10px;'>
            <img src='https://cdn-icons-png.flaticon.com/24/733/733585.png' alt='WhatsApp' style='vertical-align: middle;'>
        </a>
        </div>

      <div class='footer'>
        Si no reconoces este registro, puedes ignorar este mensaje.<br />
         © 2025 LoveMakeup C.A. Todos los derechos reservados.
      </div>
    </div>
  </body>
</html>
    ";
        $mail->send();
        return ['exito' => true];
    } catch (\Exception $e) {
        error_log("Error al enviar correo de bienvenida: " . $e->getMessage());
        return ['exito' => false];
    }
}

?>