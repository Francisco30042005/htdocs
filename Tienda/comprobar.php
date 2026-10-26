<?php
/*
  Página de diagnóstico de Start Shop.
  Ábrela en http://localhost/Tienda/comprobar.php para ver qué falla.
  Puedes borrarla cuando todo funcione.
*/
error_reporting(E_ALL);
ini_set('display_errors', '1');
mysqli_report(MYSQLI_REPORT_OFF);

$comprobaciones = [];
function comprobar(string $que, bool $ok, string $detalle = '', string $solucion = ''): void
{
    global $comprobaciones;
    $comprobaciones[] = compact('que', 'ok', 'detalle', 'solucion');
}

// 1. Versión de PHP
comprobar('Versión de PHP (mínimo 7.4)', version_compare(PHP_VERSION, '7.4', '>='), PHP_VERSION,
    'Actualiza XAMPP a una versión con PHP 7.4 o superior.');

// 2. Extensión mysqli con mysqlnd (necesaria para get_result / fetch_all)
comprobar('Extensión mysqli', extension_loaded('mysqli'), '',
    'Activa extension=mysqli en C:\\xampp\\php\\php.ini y reinicia Apache.');
comprobar('Driver mysqlnd', function_exists('mysqli_fetch_all'), '',
    'Activa mysqlnd en php.ini (en XAMPP viene activado por defecto).');

// 3. Archivos de la tienda
$archivos = ['conexion.php', 'funciones.php', 'inicio.php', 'products-section.php', 'product-item.php',
             'contacus.php', 'generar-mail.php', 'solicitud-recibida.html', 'error-404.html'];
$faltan = array_filter($archivos, fn($a) => !file_exists(__DIR__ . '/' . $a));
comprobar('Archivos PHP de la tienda', !$faltan, $faltan ? 'Faltan: ' . implode(', ', $faltan) : 'Todos presentes',
    'Copia en esta carpeta los archivos que faltan.');

// 4. Archivos de la plantilla MultiShop
$plantilla = ['css/style.css', 'js/main.js', 'lib/owlcarousel/owl.carousel.min.js', 'lib/easing/easing.min.js',
              'lib/animate/animate.min.css', 'img/carousel-1.jpg'];
$faltan = array_filter($plantilla, fn($a) => !file_exists(__DIR__ . '/' . $a));
comprobar('Estilos e imágenes de la plantilla', !$faltan, $faltan ? 'Faltan: ' . implode(', ', $faltan) : 'Todos presentes',
    'Copia las carpetas css, js, lib e img de la plantilla MultiShop.');

// 5. Imágenes de productos (img/COD-XX.jpg)
$num_imagenes = count(glob(__DIR__ . '/img/COD-*.jpg'));
comprobar('Fotos de productos en img/ (COD-XX.jpg)', $num_imagenes > 0,
    "$num_imagenes imágenes COD-*.jpg",
    'Copia las fotos de los productos en la carpeta img/ con el nombre COD-XX.jpg según su referencia. Mientras tanto se ven imágenes grises de relleno.');

// 6. Conexión con MySQL (mismos datos que conexion.php)
$db_host = "localhost"; $db_usuario = "root"; $db_pass = ""; $db_nombre = "startshop";
$conexion = null;
if (extension_loaded('mysqli')) {
    $servidor = @new mysqli($db_host, $db_usuario, $db_pass);
    $ok_servidor = !$servidor->connect_error;
    comprobar('Servidor MySQL arrancado', $ok_servidor, $servidor->connect_error ?? '',
        'Abre el panel de XAMPP y pulsa "Start" en MySQL.');

    if ($ok_servidor) {
        $existe = $servidor->select_db($db_nombre);
        comprobar("Base de datos «{$db_nombre}»", $existe, $existe ? 'Existe' : 'No existe',
            "Crea la base de datos {$db_nombre} en phpMyAdmin (http://localhost/phpmyadmin) e importa tus productos.");
        if ($existe) {
            $conexion = $servidor;
        }
    }
}

// 7. Tabla productos y sus columnas
if ($conexion) {
    $necesarias = ['id', 'referencia', 'nombre', 'precio', 'precio_rebajado', 'descripcion', 'valoracion'];
    $res = $conexion->query("SHOW COLUMNS FROM productos");
    if (!$res) {
        comprobar('Tabla «productos»', false, $conexion->error, 'Crea la tabla productos en la base de datos startshop.');
    } else {
        $columnas = array_column($res->fetch_all(MYSQLI_ASSOC), 'Field');
        $faltan = array_diff($necesarias, $columnas);
        comprobar('Columnas de la tabla «productos»', !$faltan,
            $faltan ? 'Faltan: ' . implode(', ', $faltan) : 'Todas presentes (' . implode(', ', $columnas) . ')',
            'Añade las columnas que faltan o dime cómo se llaman en tu tabla.');
        $total = $conexion->query("SELECT COUNT(*) FROM productos")->fetch_row()[0] ?? 0;
        comprobar('Productos en la tabla', $total > 0, "$total productos", 'Inserta productos en la tabla.');
    }
}

// 8. Envío de correo (solo informativo, no cuenta como error)
comprobar('Correo del formulario de contacto (informativo)', true,
    'SMTP = ' . ini_get('SMTP') . ':' . ini_get('smtp_port') . '. En local, XAMPP solo envía correos si configuras sendmail/SMTP en php.ini; aun así el formulario lleva a la página de confirmación.');

$errores = count(array_filter($comprobaciones, fn($c) => !$c['ok']));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Diagnóstico | Start Shop</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <style>
        body { font-family: Roboto, Arial, sans-serif; background: #f5f5f5; color: #3d464d; margin: 0; padding: 24px; }
        .caja { max-width: 860px; margin: 0 auto; background: #fff; padding: 24px 28px; }
        h1 { margin-top: 0; } .resumen { padding: 12px 16px; margin-bottom: 20px; font-weight: bold; }
        .bien { background: #e6f4ea; color: #1e7b34; } .mal { background: #fdecea; color: #a12622; }
        table { width: 100%; border-collapse: collapse; } td { padding: 10px 8px; border-top: 1px solid #eee; vertical-align: top; }
        .icono { width: 28px; font-size: 18px; } .det { color: #6c757d; font-size: 14px; } .sol { color: #a12622; font-size: 14px; margin-top: 4px; }
        a { color: #d19c00; }
    </style>
</head>
<body>
<div class="caja">
    <h1>Diagnóstico de Start Shop</h1>
    <div class="resumen <?= $errores ? 'mal' : 'bien' ?>">
        <?= $errores ? "Hay $errores cosa(s) por arreglar (en rojo)." : 'Todo correcto. ' ?>
        <?php if (!$errores): ?><a href="index.php">Ir a la tienda</a><?php endif; ?>
    </div>
    <table>
        <?php foreach ($comprobaciones as $c): ?>
            <tr>
                <td class="icono"><?= $c['ok'] ? '✅' : '❌' ?></td>
                <td>
                    <strong><?= htmlspecialchars($c['que']) ?></strong>
                    <?php if ($c['detalle'] !== ''): ?><div class="det"><?= htmlspecialchars($c['detalle']) ?></div><?php endif; ?>
                    <?php if (!$c['ok'] && $c['solucion']): ?><div class="sol"><?= htmlspecialchars($c['solucion']) ?></div><?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>
</body>
</html>
