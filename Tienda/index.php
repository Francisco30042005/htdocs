<?php
/*
  TIO PEPE - Punto de entrada de la tienda
  Plantilla: MultiShop (HTML Codex) adaptada a Tio Pepe

  Todas las páginas se abren desde aquí: index.php?p=nombre (por ejemplo index.php?p=carrito).
  Lo que hace, en orden:
    1. Arranca la sesión (guarda el carrito y el usuario) y conecta con la base de datos.
    2. Si se ha pulsado "Modo oscuro" o "ES / EN", guarda la preferencia.
    3. Prepara la página pedida (carpeta paginas/). Cada página procesa sus propios
       formularios y, si hace falta, redirige antes de que se pinte nada.
    4. Pinta la página completa: cabecera + contenido + pie (carpeta plantilla/).
*/

// 1. Sesión, base de datos y funciones
// Seguridad de la cookie de sesión:
//   httponly -> JavaScript no puede leerla (si alguien cuela un script, no roba la sesión)
//   samesite -> el navegador no la envía desde formularios de otras webs
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();

// Cabeceras de seguridad que se mandan con todas las páginas
header('X-Frame-Options: SAMEORIGIN');        // nadie puede meter la tienda dentro de su web (iframe)
header('X-Content-Type-Options: nosniff');    // el navegador no "adivina" el tipo de los archivos
header('Referrer-Policy: same-origin');       // no se envía la dirección de la tienda a otras webs
require 'conexion.php';
require 'funciones.php';

// Cuenta de Gmail para enviar correos (config-correo.php no se sube a GitHub)
if (is_file('config-correo.php')) {
    require 'config-correo.php';
}

// Clave de Stripe para pagar con tarjeta (config-stripe.php no se sube a GitHub)
if (is_file('config-stripe.php')) {
    require 'config-stripe.php';
}
if (!defined('STRIPE_CLAVE_SECRETA')) {
    define('STRIPE_CLAVE_SECRETA', '');
}

// 2. Modo claro/oscuro e idioma (llegan por la URL: ?tema=oscuro, ?idioma=en)
if (isset($_GET['tema']) || isset($_GET['idioma'])) {
    if (isset($_GET['tema'])) {
        guardar_cookie('tema', $_GET['tema'] === 'oscuro' ? 'oscuro' : 'claro');
    }
    if (isset($_GET['idioma'])) {
        $idioma = $_GET['idioma'] === 'en' ? 'en' : 'es';
        guardar_cookie('idioma', $idioma);
        // "googtrans" es la cookie que lee el traductor de Google para traducir la página
        guardar_cookie('googtrans', $idioma === 'en' ? '/es/en' : '');
    }
    // Volvemos a la misma página sin ese parámetro en la URL
    $parametros = $_GET;
    unset($parametros['tema'], $parametros['idioma']);
    header('Location: index.php' . ($parametros ? '?' . http_build_query($parametros) : ''));
    exit;
}

// 3. Páginas de la tienda:  ?p=...  =>  [archivo de la carpeta paginas/, título de la pestaña]
$paginas = [
    ''                     => ['inicio.php',          'Inicio'],
    'conocenos'            => ['conocenos.php',       'Conócenos'],
    'solicitud-recibida'   => ['mensaje-enviado.php', 'Mensaje enviado'],
    'tienda'               => ['tienda.php',          'Tienda'],
    'rebajados'            => ['tienda.php',          'Rebajados'],
    'informacion-producto' => ['producto.php',        'Producto'],
    'carrito'              => ['carrito.php',         'Carrito'],
    'login'                => ['login.php',           'Acceder'],
    'registro'             => ['registro.php',        'Crear cuenta'],
    'mi-cuenta'            => ['mi-cuenta.php',       'Mi cuenta'],
    'favoritos'            => ['favoritos.php',       'Mis favoritos'],
    'privacidad'           => ['privacidad.php',      'Política de privacidad'],
    'cookies'              => ['cookies.php',         'Política de cookies'],
    'terminos'             => ['terminos.php',        'Términos y condiciones'],
    'admin'                => ['admin.php',           'Administración'],
];
$pagina = $_GET['p'] ?? '';
$busqueda = trim($_GET['q'] ?? ''); // texto del buscador

if (isset($paginas[$pagina])) {
    [$archivo, $titulo] = $paginas[$pagina];
} else {
    [$archivo, $titulo] = ['error-404.php', 'Página no encontrada'];
    http_response_code(404);
}

// La página se prepara "en borrador" (ob_start) y se guarda en $contenido.
// Como todavía no se ha enviado nada al navegador, la página puede redirigir con header().
ob_start();
include 'paginas/' . $archivo;
$contenido = ob_get_clean();

// 4. Página completa
include 'plantilla/cabecera.php';
echo $contenido;
include 'plantilla/pie.php';
