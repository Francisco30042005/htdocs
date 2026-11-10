<?php
/*
  Version   : 1.0
  Fecha     : 10 Noviembre 2026
  Instalación automática de la base de datos (lo incluye conexion.php).

  Cada vez que se abre la tienda se compara sql/startshop.sql con el que se
  importó la última vez en este ordenador (se guarda su huella md5 en la
  tabla `_version_bd`). Si la base de datos no existe o el archivo ha
  cambiado, se borra la base de datos y se importa el archivo de cero.

  Así todo el equipo tiene la misma base de datos sin tocar phpMyAdmin, y
  los cambios que hagas desde la tienda (productos, usuarios...) se
  mantienen mientras nadie cambie sql/startshop.sql.

  Forzar la reimportación: index.php?reinstalar_bd=1
*/

const ARCHIVO_SQL_BD = __DIR__ . '/sql/startshop.sql';

/**
 * Importa sql/startshop.sql si hace falta.
 *
 * @return string|null null si todo va bien, o el mensaje de error
 */
function sincronizar_bd(string $host, string $usuario, string $pass, string $nombre_bd): ?string
{
    if (!is_file(ARCHIVO_SQL_BD)) {
        return null; // Sin archivo no hay nada que importar
    }
    $huella = md5_file(ARCHIVO_SQL_BD);

    $servidor = @new mysqli($host, $usuario, $pass);
    if ($servidor->connect_error) {
        return null; // conexion.php ya mostrará el error de conexión
    }
    $servidor->set_charset('utf8mb4');
    $bd = $servidor->real_escape_string($nombre_bd);

    $forzar = isset($_GET['reinstalar_bd']);
    if (!$forzar && obtener_huella_bd($servidor, $nombre_bd) === $huella) {
        $servidor->close();
        return null; // Ya está al día: lo normal
    }

    // Evita que dos peticiones a la vez importen el archivo dos veces
    $servidor->query("SELECT GET_LOCK('instalar_{$bd}', 30)");
    if (!$forzar && obtener_huella_bd($servidor, $nombre_bd) === $huella) {
        $servidor->query("SELECT RELEASE_LOCK('instalar_{$bd}')");
        $servidor->close();
        return null; // Otra petición lo acaba de importar
    }

    $error = null;
    $id_bd = '`' . str_replace('`', '``', $nombre_bd) . '`';
    if (!$servidor->query("DROP DATABASE IF EXISTS $id_bd")
        || !$servidor->query("CREATE DATABASE $id_bd DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci")
        || !$servidor->select_db($nombre_bd)) {
        $error = $servidor->error;
    } else {
        $error = ejecutar_sql($servidor, file_get_contents(ARCHIVO_SQL_BD));
    }

    if ($error === null) {
        $servidor->select_db($nombre_bd);
        $servidor->query("CREATE TABLE IF NOT EXISTS `_version_bd` (huella CHAR(32) NOT NULL, importado DATETIME NOT NULL)");
        $servidor->query("DELETE FROM `_version_bd`");
        $sentencia = $servidor->prepare("INSERT INTO `_version_bd` (huella, importado) VALUES (?, NOW())");
        $sentencia->bind_param('s', $huella);
        $sentencia->execute();
    }

    $servidor->query("SELECT RELEASE_LOCK('instalar_{$bd}')");
    $servidor->close();
    return $error;
}

/**
 * Devuelve la huella del SQL importado la última vez, o null si no hay.
 */
function obtener_huella_bd(mysqli $servidor, string $nombre_bd): ?string
{
    if (!@$servidor->select_db($nombre_bd)) {
        return null; // La base de datos no existe
    }
    $res = @$servidor->query("SELECT huella FROM `_version_bd` LIMIT 1");
    return $res ? ($res->fetch_row()[0] ?? null) : null;
}

/**
 * Ejecuta un archivo SQL entero (varias sentencias).
 *
 * @return string|null null si va bien, o el mensaje de error
 */
function ejecutar_sql(mysqli $servidor, string $sql): ?string
{
    if (!$servidor->multi_query($sql)) {
        return $servidor->error;
    }
    // Hay que recorrer todos los resultados para que se ejecute todo
    do {
        if ($res = $servidor->store_result()) {
            $res->free();
        }
    } while ($servidor->more_results() && $servidor->next_result());

    return $servidor->errno ? $servidor->error : null;
}
