<?php
$remitente = $_POST['email'];
$destinatario = 'gsebas.rivera@gmail.com'; // E-mail del destinatario
$asunto = 'Mensaje del formulario de Contacta con nosotros (Start Shop)'; // Asunto del e-mail
if (!$_POST){
?>

<?php
}else{
    $cuerpo = "Nombre: " . $_POST["nombre"] . "\r\n";
    $cuerpo .= "Apellidos: " . $_POST["apellidos"] . "\r\n";
    $cuerpo .= "Tel.: " . $_POST["phone"] . "\r\n";
    $cuerpo .= "Email: " . $_POST["email"] . "\r\n";
    $cuerpo .= "Comentarios: " . $_POST["comentarios"] . "\r\n";
	// Las líneas de arriba definen el contenido del e-mail. Las palabras que están dentro de $_POST[""] deben coincidir con el "name" de cada campo.

    $headers  = "MIME-Version: 1.0\n";
    $headers .= "Content-type: text/plain; charset=utf-8\n";
    $headers .= "X-Priority: 3\n";
    $headers .= "X-MSMail-Priority: Normal\n";
    $headers .= "X-Mailer: php\n";
    $headers .= "From: \"".$_POST['nombre']." ".$_POST['apellidos']."\" <".$remitente.">\n";

    mail($destinatario, $asunto, $cuerpo, $headers);
    
    header("Location: index.php?p=solicitud-recibida"); // Página en la que se muestra la confirmación el envío.
    exit();
}
?>