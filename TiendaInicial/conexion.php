<!--
  Version   : 1.0
  Autor     : Gabriel S.
  Fecha     : 05 Octubre 2026
-->
<?php
// Archivo de conexión a la base de datos, usando MySQLi (PHP 7+)

// 1. Definir las credenciales de la base de datos
// (Estos son los valores por defecto de XAMPP)
$db_host = "localhost";   // Servidor
$db_usuario = "root";     // Usuario de la BD
$db_pass = "";        // Contraseña (en XAMPP por defecto es vacía)
$db_nombre = "startshop";  // Nombre de tu base de datos

// 2. Crear la conexión
// Usamos el constructor de la clase mysqli (conexión orientada a objetos)
$conexion = new mysqli($db_host, $db_usuario, $db_pass, $db_nombre);

// 3. Verificar si hubo un error en la conexión
if ($conexion->connect_error) {
    /*
     * Si hay un error, matamos la ejecución de la página
     * y mostramos el mensaje de error.
     * Esto es útil en desarrollo. En producción (una web real),
     * deberíamos mostrar un mensaje más amigable.
     */
    die("Error de conexión: " . $conexion->connect_error);
}

// 4. Establecer el juego de caracteres a UTF-8 (¡MUY IMPORTANTE!)
// Esto asegura que las tildes, 'ñ' y otros caracteres especiales
// se guarden y se muestren correctamente en la web.
if (!$conexion->set_charset("utf8mb4")) {
    // Si falla, también mostramos un error
    die("Error al cargar el set de caracteres utf8mb4: " . $conexion->error);
}

/*
 * ¡Conexión exitosa!
 *
 * No cerramos la conexión con $conexion->close() aquí.
 * Dejamos que el script que incluye este archivo la utilice
 * y PHP se encargará de cerrarla automáticamente al finalizar.
 *
 * Tampoco ponemos un echo 'Conectado'; porque este archivo
 * será incluido en todas las páginas, y no queremos ese mensaje siempre.
 */
?>