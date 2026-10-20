<?php
/*
  Version   : 2.0
  Procesa el formulario de "Contacta con nosotros" (contacus.php) y envía un e-mail.
  Los nombres de $_POST[...] coinciden con el "name" de cada campo del formulario.
*/
$destinatario = 'gsebas.rivera@gmail.com'; // E-mail del destinatario
$asunto = 'Mensaje del formulario de Contacta con nosotros (Start Shop)'; // Asunto del e-mail

// Si se entra a esta página sin enviar el formulario, volvemos a Conócenos
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php?p=conocenos');
    exit();
}

// Recogemos los campos quitando saltos de línea (evita inyectar cabeceras en el e-mail)
function campo(string $nombre): string
{
    return trim(str_replace(["\r", "\n"], ' ', $_POST[$nombre] ?? ''));
}

$nombre      = campo('nombre');
$apellidos   = campo('apellidos');
$telefono    = campo('phone');
$remitente   = filter_var(campo('email'), FILTER_VALIDATE_EMAIL);
$comentarios = trim($_POST['comentarios'] ?? '');

// Validación: campos obligatorios y e-mail correcto
if ($nombre === '' || $apellidos === '' || $telefono === '' || !$remitente) {
    header('Location: index.php?p=conocenos&error=1');
    exit();
}

$cuerpo  = "Nombre: " . $nombre . "\r\n";
$cuerpo .= "Apellidos: " . $apellidos . "\r\n";
$cuerpo .= "Tel.: " . $telefono . "\r\n";
$cuerpo .= "Email: " . $remitente . "\r\n";
$cuerpo .= "Comentarios: " . $comentarios . "\r\n";

$headers  = "MIME-Version: 1.0\n";
$headers .= "Content-type: text/plain; charset=utf-8\n";
$headers .= "X-Priority: 3\n";
$headers .= "X-MSMail-Priority: Normal\n";
$headers .= "X-Mailer: php\n";
$headers .= "From: \"" . str_replace('"', '', $nombre . " " . $apellidos) . "\" <" . $remitente . ">\n";
$headers .= "Reply-To: " . $remitente . "\n";

mail($destinatario, $asunto, $cuerpo, $headers);

header("Location: index.php?p=solicitud-recibida"); // Página en la que se muestra la confirmación del envío.
exit();
