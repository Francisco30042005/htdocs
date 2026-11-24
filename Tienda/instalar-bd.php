<?php
/*
  INSTALACIÓN AUTOMÁTICA DE LA BASE DE DATOS (la usa conexion.php)

  Al abrir la tienda se mira si sql/startshop.sql ha cambiado desde la última vez
  que se ejecutó en este ordenador (su huella md5 se guarda en la tabla `_version_bd`).
  Si ha cambiado, o si la base de datos todavía no existe, se ejecuta el archivo.

  El archivo NUNCA borra datos: solo crea lo que falta (tablas, columnas y datos de
  ejemplo). Así, lo que hagas en la aplicación se conserva aunque un compañero cambie
  el SQL y tú hagas "git pull".

  Para empezar de cero (borra TODO y vuelve a los datos de ejemplo):
  http://localhost/Tienda/index.php?reinstalar_bd=1   (solo desde el propio ordenador)
*/

const ARCHIVO_SQL_BD = __DIR__ . '/sql/startshop.sql';

/**
 * Ejecuta sql/startshop.sql si hace falta.
 * Devuelve null si todo va bien, o el mensaje de error.
 */
function sincronizar_bd(string $host, string $usuario, string $pass, string $nombre_bd): ?string
{
    if (!is_file(ARCHIVO_SQL_BD)) {
        return null; // sin archivo no hay nada que hacer
    }
    $servidor = @new mysqli($host, $usuario, $pass);
    if ($servidor->connect_error) {
        return null; // conexion.php ya avisará de que MySQL no está arrancado
    }
    $servidor->set_charset('utf8mb4');

    $huella = md5_file(ARCHIVO_SQL_BD);
    // "Empezar de cero" solo se acepta desde el propio ordenador (localhost)
    $empezar_de_cero = isset($_GET['reinstalar_bd']) && in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1']);

    // Lo normal: el archivo no ha cambiado y no hay nada que hacer
    if (!$empezar_de_cero && obtener_huella_bd($servidor, $nombre_bd) === $huella) {
        $servidor->close();
        return null;
    }

    if ($empezar_de_cero) {
        $servidor->query("DROP DATABASE IF EXISTS `$nombre_bd`");
    }
    $servidor->query("CREATE DATABASE IF NOT EXISTS `$nombre_bd` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    $servidor->select_db($nombre_bd);

    $error = ejecutar_sql($servidor, file_get_contents(ARCHIVO_SQL_BD));

    // Se apunta la huella del archivo para no volver a ejecutarlo hasta que cambie
    if ($error === null) {
        $servidor->select_db($nombre_bd);
        $servidor->query("CREATE TABLE IF NOT EXISTS `_version_bd` (huella CHAR(32) NOT NULL, importado DATETIME NOT NULL)");
        $servidor->query("DELETE FROM `_version_bd`");
        $servidor->query("INSERT INTO `_version_bd` (huella, importado) VALUES ('$huella', NOW())");
    }
    $servidor->close();
    return $error;
}

/** Huella del SQL ejecutado la última vez, o null si la base de datos no existe. */
function obtener_huella_bd(mysqli $servidor, string $nombre_bd): ?string
{
    if (!@$servidor->select_db($nombre_bd)) {
        return null;
    }
    $resultado = @$servidor->query("SELECT huella FROM `_version_bd` LIMIT 1");
    return $resultado ? ($resultado->fetch_row()[0] ?? null) : null;
}

/** Ejecuta un archivo SQL entero (varias sentencias). Devuelve null si va bien o el error. */
function ejecutar_sql(mysqli $servidor, string $sql): ?string
{
    if (!$servidor->multi_query($sql)) {
        return $servidor->error;
    }
    // Hay que recorrer todos los resultados para que se ejecuten todas las sentencias
    do {
        if ($resultado = $servidor->store_result()) {
            $resultado->free();
        }
    } while ($servidor->more_results() && $servidor->next_result());

    return $servidor->errno ? $servidor->error : null;
}
