<?php
/*
  Version   : 2.0
  Autor     : Gabriel S.
  Fecha     : 20 Octubre 2026
  Plantilla : MultiShop (HTML Codex) adaptada a Start Shop
*/
session_start(); // La sesión guarda el carrito de la compra
$pagina = $_GET['p'] ?? null;
$busqueda = trim($_GET['q'] ?? '');

// Conexión con la bbdd
require_once 'conexion.php';

/* ==========================================================================
   FUNCIONES DE LA TIENDA
   Las usan index.php y las secciones que incluye (inicio, tienda, ficha de
   producto, carrito y conócenos).

  Convención de nombres (verbo + qué hace):
    obtener_*   devuelve datos (de la base de datos, la sesión o la URL)
    contar_*    devuelve un número de elementos
    calcular_*  devuelve un valor calculado
    formatear_* / escapar_*  devuelve texto preparado para mostrar
    generar_*   devuelve un trozo de HTML como texto
    mostrar_*   pinta HTML directamente en la página
    anadir_* / cambiar_*     modifican el carrito
    tiene_*     devuelve true / false
   ========================================================================== */

// Símbolo de moneda que se muestra en los precios
const MONEDA = '€';

// Gastos de envío fijos de cada pedido
const GASTOS_ENVIO = 10;

// Valores permitidos en las cookies de preferencias
const IDIOMAS = ['es', 'en'];
const TEMAS = ['claro', 'oscuro'];

/**
 * Devuelve los productos de la base de datos.
 *
 * @param mysqli      $conexion   Conexión abierta (conexion.php)
 * @param bool        $rebajados  true = solo productos con precio_rebajado
 * @param string|null $busqueda   Texto a buscar en nombre, referencia o descripción
 * @param int|null    $limite     Número máximo de productos (null = todos)
 * @param int|null    $excluir_id Id de producto que no queremos que aparezca
 * @return array Lista de productos (arrays asociativos)
 */
function obtener_productos(mysqli $conexion, bool $rebajados = false, ?string $busqueda = null, ?int $limite = null, ?int $excluir_id = null): array
{
    $sql = "SELECT id, referencia, nombre, precio, precio_rebajado, descripcion, valoracion
            FROM productos";
    $condiciones = [];
    $tipos = '';
    $valores = [];

    if ($rebajados) {
        $condiciones[] = "precio_rebajado IS NOT NULL AND precio_rebajado > 0";
    }
    if ($busqueda !== null && $busqueda !== '') {
        $condiciones[] = "(nombre LIKE ? OR referencia LIKE ? OR descripcion LIKE ?)";
        $patron = '%' . $busqueda . '%';
        $tipos .= 'sss';
        array_push($valores, $patron, $patron, $patron);
    }
    if ($excluir_id !== null) {
        $condiciones[] = "id <> ?";
        $tipos .= 'i';
        $valores[] = $excluir_id;
    }
    if ($condiciones) {
        $sql .= " WHERE " . implode(' AND ', $condiciones);
    }
    $sql .= " ORDER BY id DESC";
    if ($limite !== null) {
        $sql .= " LIMIT " . (int) $limite;
    }

    // Consulta preparada para evitar inyección SQL
    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        return [];
    }
    if ($tipos !== '') {
        $stmt->bind_param($tipos, ...$valores);
    }
    $stmt->execute();
    $productos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $productos;
}

/** Devuelve un producto por su id, o null si no existe. */
function obtener_producto_por_id(mysqli $conexion, int $id): ?array
{
    $stmt = $conexion->prepare("SELECT id, referencia, nombre, precio, precio_rebajado, descripcion, valoracion
                                FROM productos WHERE id = ?");
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $producto = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $producto ?: null;
}

/** Cuenta los productos (todos o solo los rebajados). */
function contar_productos(mysqli $conexion, bool $rebajados = false): int
{
    $sql = "SELECT COUNT(*) AS total FROM productos";
    if ($rebajados) {
        $sql .= " WHERE precio_rebajado IS NOT NULL AND precio_rebajado > 0";
    }
    $resultado = $conexion->query($sql);
    return $resultado ? (int) $resultado->fetch_assoc()['total'] : 0;
}

/** Escapa texto para mostrarlo en HTML. */
function escapar_html(?string $texto): string
{
    return htmlspecialchars($texto ?? '', ENT_QUOTES, 'UTF-8');
}

/** true si el producto tiene precio rebajado. */
function tiene_precio_rebajado(array $producto): bool
{
    return !is_null($producto['precio_rebajado']) && $producto['precio_rebajado'] > 0;
}

/** Formatea un precio al estilo español: 1.234,50 € */
function formatear_precio(float $valor): string
{
    return number_format($valor, 2, ',', '.') . ' ' . MONEDA;
}

/**
 * Ruta de la imagen del producto (img/COD-XX.jpg según la referencia)
 * o una imagen de relleno si no existe.
 * Sirve tanto si la referencia es "COD-01" como si es "01" o "1".
 */
function obtener_imagen_producto(array $producto, string $relleno = '600x600'): string
{
    $referencia = trim((string) $producto['referencia']);
    $nombres = [$referencia, 'COD-' . $referencia];
    if (ctype_digit($referencia)) {
        $nombres[] = 'COD-' . str_pad($referencia, 2, '0', STR_PAD_LEFT);
    }
    foreach ($nombres as $nombre) {
        foreach (['jpg', 'JPG', 'jpeg', 'png'] as $extension) {
            $ruta = 'img/' . $nombre . '.' . $extension;
            if ($nombre !== '' && file_exists(__DIR__ . '/' . $ruta)) {
                // ?v=fecha del archivo: si cambias la foto, el navegador no usa la antigua de la caché
                return $ruta . '?v=' . filemtime(__DIR__ . '/' . $ruta);
            }
        }
    }
    return 'https://dummyimage.com/' . $relleno . '/dee2e6/6c757d.jpg';
}

/** Enlace a la ficha del producto. */
function obtener_url_producto(array $producto): string
{
    return 'index.php?p=informacion-producto&id=' . (int) $producto['id'];
}

/**
 * HTML de las estrellas de valoración (0 a 5, admite medias estrellas).
 * $clase = clases CSS extra de cada estrella.
 */
function generar_estrellas_html(?float $valoracion, string $clase = 'text-primary mr-1'): string
{
    // Redondeamos a la media estrella más cercana
    $valor = round(((float) $valoracion) * 2) / 2;
    $html = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($valor >= $i) {
            $icono = 'fas fa-star';
        } elseif ($valor >= $i - 0.5) {
            $icono = 'fas fa-star-half-alt';
        } else {
            $icono = 'far fa-star';
        }
        $html .= '<small class="' . $icono . ' ' . $clase . '"></small>';
    }
    return $html;
}

/**
 * Pinta la tarjeta de un producto con el estilo de la plantilla MultiShop.
 * Se usa en Inicio, Tienda, Rebajados y en "También te puede gustar".
 */
function mostrar_tarjeta_producto(array $producto): void
{
    $rebajado = tiene_precio_rebajado($producto);
    $url = obtener_url_producto($producto);
    ?>
    <div class="product-item bg-light mb-4">
        <div class="product-img position-relative overflow-hidden">
            <img class="img-fluid w-100" src="<?= escapar_html(obtener_imagen_producto($producto)) ?>" alt="<?= escapar_html($producto['nombre']) ?>">
            <?php if ($rebajado): ?>
                <span class="badge badge-primary text-dark position-absolute px-2 py-1" style="top: 10px; right: 10px; z-index: 1;">Rebajado</span>
            <?php endif; ?>
            <div class="product-action">
                <form class="d-inline" action="index.php?p=carrito" method="post">
                    <input type="hidden" name="accion" value="anadir">
                    <input type="hidden" name="id" value="<?= (int) $producto['id'] ?>">
                    <button type="submit" class="btn btn-outline-dark btn-square" title="Añadir al carrito"><i class="fa fa-shopping-cart"></i></button>
                </form>
                <a class="btn btn-outline-dark btn-square" href="<?= escapar_html($url) ?>" title="Ver producto"><i class="fa fa-search"></i></a>
            </div>
        </div>
        <div class="text-center py-4 px-2">
            <a class="h6 text-decoration-none text-truncate d-block" href="<?= escapar_html($url) ?>"><?= escapar_html($producto['nombre']) ?></a>
            <div class="d-flex align-items-center justify-content-center mt-2">
                <?php if ($rebajado): ?>
                    <h5><?= formatear_precio($producto['precio_rebajado']) ?></h5>
                    <h6 class="text-muted ml-2"><del><?= formatear_precio($producto['precio']) ?></del></h6>
                <?php else: ?>
                    <h5><?= formatear_precio($producto['precio']) ?></h5>
                <?php endif; ?>
            </div>
            <div class="d-flex align-items-center justify-content-center mb-1">
                <?= generar_estrellas_html($producto['valoracion']) ?>
            </div>
        </div>
    </div>
    <?php
}


/**
 * Migas de pan (breadcrumb).
 * $migas = ['Texto' => 'url', ..., 'Página actual' => null]
 */
function mostrar_migas_de_pan(array $migas): void
{
    ?>
    <div class="container-fluid">
        <div class="row px-xl-5">
            <div class="col-12">
                <nav class="breadcrumb bg-light mb-30">
                    <?php foreach ($migas as $texto => $url): ?>
                        <?php if ($url): ?>
                            <a class="breadcrumb-item text-dark" href="<?= escapar_html($url) ?>"><?= escapar_html($texto) ?></a>
                        <?php else: ?>
                            <span class="breadcrumb-item active"><?= escapar_html($texto) ?></span>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </nav>
            </div>
        </div>
    </div>
    <?php
}


/* ------------------------------------------------------------------
   Carrito de la compra
   Se guarda en la sesión: $_SESSION['carrito'] = [id_producto => cantidad]
   ------------------------------------------------------------------ */

/** Precio que se cobra del producto (el rebajado si lo tiene). */
function calcular_precio_final(array $producto): float
{
    return tiene_precio_rebajado($producto) ? (float) $producto['precio_rebajado'] : (float) $producto['precio'];
}

/** Añade unidades de un producto al carrito. */
function anadir_al_carrito(int $id, int $cantidad = 1): void
{
    if ($id <= 0 || $cantidad <= 0) {
        return;
    }
    $_SESSION['carrito'][$id] = ($_SESSION['carrito'][$id] ?? 0) + $cantidad;
}

/** Cambia la cantidad de un producto (0 o menos = se quita). */
function cambiar_cantidad_carrito(int $id, int $cantidad): void
{
    if ($cantidad > 0) {
        $_SESSION['carrito'][$id] = $cantidad;
    } else {
        unset($_SESSION['carrito'][$id]);
    }
}

/** Número total de unidades en el carrito (para el contador del icono). */
function contar_unidades_carrito(): int
{
    return array_sum($_SESSION['carrito'] ?? []);
}

/**
 * Líneas del carrito con los datos de cada producto:
 * [['producto' => [...], 'cantidad' => 2, 'precio' => 9.5, 'total' => 19.0], ...]
 * Si un producto ya no existe en la base de datos se quita del carrito.
 */
function obtener_lineas_carrito(mysqli $conexion): array
{
    $lineas = [];
    foreach ($_SESSION['carrito'] ?? [] as $id => $cantidad) {
        $producto = obtener_producto_por_id($conexion, (int) $id);
        if (!$producto) {
            unset($_SESSION['carrito'][$id]);
            continue;
        }
        $precio = calcular_precio_final($producto);
        $lineas[] = [
            'producto' => $producto,
            'cantidad' => (int) $cantidad,
            'precio'   => $precio,
            'total'    => $precio * $cantidad,
        ];
    }
    return $lineas;
}

/** Devuelve el % de descuento del cupón, o 0 si no existe o no está activo. */
function obtener_descuento_cupon(mysqli $conexion, string $codigo): int
{
    $sentencia = $conexion->prepare("SELECT porcentaje FROM cupones WHERE codigo = ? AND activo = 1");
    $sentencia->bind_param('s', $codigo);
    $sentencia->execute();
    $fila = $sentencia->get_result()->fetch_assoc();
    return $fila ? (int) $fila['porcentaje'] : 0;
}

/** Suma el % de todos los cupones aplicados al carrito (como máximo 100 %). */
function obtener_descuento_total(mysqli $conexion): int
{
    $porcentaje = 0;
    foreach ($_SESSION['cupones'] ?? [] as $codigo) {
        $porcentaje += obtener_descuento_cupon($conexion, $codigo);
    }
    return min($porcentaje, 100);
}

/**
 * Finaliza la compra del carrito: descuenta las existencias de cada producto
 * y vacía el carrito. Si de algún producto no hay unidades suficientes no se
 * compra nada (transacción) y el carrito se queda como estaba.
 *
 * @return array ['ok' => bool, 'texto' => mensaje para el cliente]
 */
function finalizar_compra(mysqli $conexion): array
{
    $lineas = obtener_lineas_carrito($conexion);
    if (!$lineas) {
        return ['ok' => false, 'texto' => 'Tu carrito está vacío.'];
    }

    $conexion->begin_transaction();
    $sentencia = $conexion->prepare("UPDATE productos SET existencias = existencias - ? WHERE id = ? AND existencias >= ?");
    $total = 0;
    foreach ($lineas as $linea) {
        $id = (int) $linea['producto']['id'];
        $cantidad = $linea['cantidad'];
        $sentencia->bind_param('iii', $cantidad, $id, $cantidad);
        $sentencia->execute();
        if ($sentencia->affected_rows !== 1) {
            $conexion->rollback();
            $quedan = (int) $linea['producto']['existencias'];
            return ['ok' => false, 'texto' => 'No hay unidades suficientes de «' . $linea['producto']['nombre'] . '»'
                . ($quedan > 0 ? " (quedan $quedan). Cambia la cantidad y vuelve a intentarlo." : ' (está agotado). Quítalo del carrito para continuar.')];
        }
        $total += $linea['total'];
    }
    $conexion->commit();

    // Descuento de los cupones aplicados
    $porcentaje = obtener_descuento_total($conexion);
    $total = $total - $total * $porcentaje / 100;

    $_SESSION['carrito'] = [];
    $_SESSION['cupones'] = [];
    return ['ok' => true, 'texto' => '¡Gracias por tu compra, ' . $_SESSION['usuario']['usuario'] . '! Tu pedido de '
        . formatear_precio($total + GASTOS_ENVIO) . ' (envío incluido) se ha realizado correctamente.'];
}

/* --------------------------------------------------------------------------
   Preferencias del visitante: modo claro/oscuro e idioma.
   Se guardan en las cookies "tema" e "idioma" durante 30 días.
   -------------------------------------------------------------------------- */

/** Idioma elegido: 'es' o 'en' (español si no hay cookie). */
function obtener_idioma_actual(): string
{
    if (!isset($_COOKIE['idioma']) || !in_array($_COOKIE['idioma'], IDIOMAS, true)) {
        return 'es';
    }
    return $_COOKIE['idioma'];
}

/** Tema elegido: 'claro' u 'oscuro' (claro si no hay cookie). */
function obtener_tema_actual(): string
{
    if (!isset($_COOKIE['tema']) || !in_array($_COOKIE['tema'], TEMAS, true)) {
        return 'claro';
    }
    return $_COOKIE['tema'];
}

/** URL de la página actual con una preferencia añadida (?tema=oscuro, ?idioma=en...). */
function obtener_url_con_preferencia(string $nombre, string $valor): string
{
    $parametros = $_GET;
    $parametros[$nombre] = $valor;
    return 'index.php?' . http_build_query($parametros);
}

/** Devuelve ' active' para marcar la opción actual del menú. */
function obtener_clase_activo(bool $condicion): string
{
    return $condicion ? ' active' : '';
}

/* --------------------------------------------------------------------------
   Usuarios y panel de administración
   El usuario que ha iniciado sesión se guarda en $_SESSION['usuario']
   = ['id' => 1, 'usuario' => 'admin', 'rol' => 'admin']
   -------------------------------------------------------------------------- */

/** true si hay un administrador con la sesión iniciada. */
function es_admin(): bool
{
    return ($_SESSION['usuario']['rol'] ?? '') === 'admin';
}

/** true si hay alguien (cliente o admin) con la sesión iniciada. */
function tiene_sesion_iniciada(): bool
{
    return isset($_SESSION['usuario']);
}

/**
 * Comprueba usuario y contraseña (clientes y administradores usan el mismo login).
 * Devuelve los datos del usuario, con su rol, o null.
 * La contraseña se guarda en la base de datos tal cual (sin cifrar).
 */
function comprobar_login(mysqli $conexion, string $usuario, string $contrasena): ?array
{
    $stmt = $conexion->prepare("SELECT id, usuario, contrasena, rol FROM usuarios WHERE usuario = ?");
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('s', $usuario);
    $stmt->execute();
    $fila = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$fila || $contrasena !== $fila['contrasena']) {
        return null;
    }
    unset($fila['contrasena']); // No guardamos la contraseña en la sesión
    return $fila;
}

/**
 * Token anti-CSRF: un código aleatorio guardado en la sesión que va oculto
 * en los formularios del panel. Así otra web no puede enviar formularios
 * en nombre del administrador.
 */
function obtener_token_csrf(): string
{
    if (empty($_SESSION['token_csrf'])) {
        $_SESSION['token_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['token_csrf'];
}

/** true si el token enviado por el formulario es el de la sesión. */
function tiene_token_csrf_valido(): bool
{
    return isset($_POST['token_csrf']) && hash_equals(obtener_token_csrf(), (string) $_POST['token_csrf']);
}

/**
 * Botón con el icono de usuario (a la izquierda del carrito).
 * Sin sesión: lleva al login. Con sesión: muestra el nombre del usuario y lleva a "Mi cuenta".
 * $color = clase del color del icono (text-primary en el menú oscuro, text-dark en móvil).
 */
function mostrar_boton_usuario(string $color): void
{
    if (tiene_sesion_iniciada()) {
        ?>
        <a href="index.php?p=mi-cuenta" class="btn px-0 ml-3" title="Mi cuenta">
            <i class="fas fa-user <?= $color ?>"></i>
            <!-- "notranslate": el traductor de Google no cambia el nombre del usuario -->
            <span class="notranslate <?= $color ?> ml-1" translate="no"><?= escapar_html($_SESSION['usuario']['usuario']) ?></span>
        </a>
        <?php
    } else {
        ?>
        <a href="index.php?p=login" class="btn px-0 ml-3" title="Acceder">
            <i class="fas fa-user <?= $color ?>"></i>
        </a>
        <?php
    }
}

/**
 * Crea una cuenta de cliente. Devuelve la lista de errores
 * (vacía si se ha creado bien). La contraseña se guarda tal cual (sin cifrar).
 */
function registrar_usuario(mysqli $conexion, string $usuario, string $contrasena, string $repetir): array
{
    $errores = [];
    if (!preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $usuario)) {
        $errores[] = 'El usuario debe tener entre 3 y 50 caracteres: letras sin tildes, números, punto, guion o guion bajo.';
    }
    if (strlen($contrasena) < 6) {
        $errores[] = 'La contraseña debe tener al menos 6 caracteres.';
    }
    if ($contrasena !== $repetir) {
        $errores[] = 'Las dos contraseñas no coinciden.';
    }
    if ($errores) {
        return $errores;
    }

    // Los clientes nuevos siempre tienen rol 'cliente' (los admins se crean en phpMyAdmin)
    $stmt = $conexion->prepare("INSERT INTO usuarios (usuario, contrasena, rol) VALUES (?, ?, 'cliente')");
    if (!$stmt) {
        return ['No se ha podido crear la cuenta: ' . $conexion->error];
    }
    $stmt->bind_param('ss', $usuario, $contrasena);
    if (!$stmt->execute()) {
        // 1062 = valor repetido en una columna UNIQUE (el usuario ya existe)
        $errores[] = $conexion->errno === 1062
            ? 'Ese nombre de usuario ya está en uso. Elige otro.'
            : 'No se ha podido crear la cuenta: ' . $conexion->error;
    }
    $stmt->close();
    return $errores;
}

/** Lista de categorías ordenadas por nombre. */
function obtener_categorias(mysqli $conexion): array
{
    $resultado = $conexion->query("SELECT id, nombre FROM categorias ORDER BY nombre");
    return $resultado ? $resultado->fetch_all(MYSQLI_ASSOC) : [];
}

/** Productos con el nombre de su categoría y sus existencias (para el panel). */
function obtener_productos_admin(mysqli $conexion): array
{
    $resultado = $conexion->query(
        "SELECT p.id, p.referencia, p.nombre, p.descripcion, p.precio, p.precio_rebajado,
                p.existencias, p.categoria_id, c.nombre AS categoria
         FROM productos p
         LEFT JOIN categorias c ON c.id = p.categoria_id
         ORDER BY p.referencia"
    );
    return $resultado ? $resultado->fetch_all(MYSQLI_ASSOC) : [];
}

/**
 * Siguiente referencia libre con el formato COD-xx
 * (si la mayor es COD-04, devuelve COD-05).
 */
function generar_siguiente_referencia(mysqli $conexion): string
{
    $resultado = $conexion->query(
        "SELECT MAX(CAST(SUBSTRING(referencia, 5) AS UNSIGNED)) AS mayor
         FROM productos WHERE referencia REGEXP '^COD-[0-9]+$'"
    );
    $mayor = $resultado ? (int) $resultado->fetch_assoc()['mayor'] : 0;
    return 'COD-' . str_pad((string) ($mayor + 1), 2, '0', STR_PAD_LEFT);
}

/* ======================= FIN DE LAS FUNCIONES ============================= */

// Cambio de modo claro/oscuro o de idioma (llega por la URL: ?tema=oscuro, ?idioma=en)
// Las cookies se crean con setcookie(nombre, valor, caducidad, ruta), como en
// https://www.w3schools.com/php/php_cookies.asp  (86400 segundos = 1 día)
if (isset($_GET['tema']) || isset($_GET['idioma'])) {
    $carpeta = dirname($_SERVER['SCRIPT_NAME']); // "/Tienda"
    if (isset($_GET['tema']) && in_array($_GET['tema'], TEMAS, true)) {
        $cookie_name = "tema";
        $cookie_value = $_GET['tema'];
        setcookie($cookie_name, "", time() - 3600, $carpeta);       // Borrar las cookies antiguas
        setcookie($cookie_name, "", time() - 3600, $carpeta . "/"); // guardadas solo en /Tienda
        setcookie($cookie_name, $cookie_value, time() + (86400 * 30), "/"); // 30 días
    }
    if (isset($_GET['idioma']) && in_array($_GET['idioma'], IDIOMAS, true)) {
        $cookie_name = "idioma";
        $cookie_value = $_GET['idioma'];
        setcookie($cookie_name, "", time() - 3600, $carpeta);       // Borrar las cookies antiguas
        setcookie($cookie_name, "", time() - 3600, $carpeta . "/"); // guardadas solo en /Tienda
        setcookie($cookie_name, $cookie_value, time() + (86400 * 30), "/"); // 30 días

        // "googtrans" es la cookie que lee el traductor de Google para traducir la página sola
        if ($cookie_value === 'en') {
            setcookie("googtrans", "/es/en", time() + (86400 * 30), "/");
        } else {
            setcookie("googtrans", "", time() - 3600, "/"); // Borrar la cookie (caducidad en el pasado)
        }
    }
    // Volvemos a la misma página quitando ese parámetro de la URL
    $parametros = $_GET;
    unset($parametros['tema'], $parametros['idioma']);
    header('Location: index.php' . ($parametros ? '?' . http_build_query($parametros) : ''));
    exit;
}

// Inicio y cierre de sesión, y acciones del panel de administración.
// Van antes que el carrito porque pueden redirigir (header) antes de pintar la página.
require 'admin-acciones.php';

// Acciones del carrito (llegan por POST desde los botones "Añadir al carrito" y la página del carrito)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    switch ($_POST['accion']) {
        case 'anadir':
            $id_anadir = (int) ($_POST['id'] ?? 0);
            if (obtener_producto_por_id($conexion, $id_anadir)) { // Solo productos que existen
                anadir_al_carrito($id_anadir, max(1, (int) ($_POST['cantidad'] ?? 1)));
            }
            break;
        case 'actualizar':
        case 'finalizar':
            // Primero se guardan las cantidades que haya en el formulario del carrito
            foreach ((array) ($_POST['cantidad'] ?? []) as $id => $cantidad) {
                cambiar_cantidad_carrito((int) $id, (int) $cantidad);
            }
            if (isset($_POST['quitar'])) {
                cambiar_cantidad_carrito((int) $_POST['quitar'], 0);
            }
            if ($_POST['accion'] === 'finalizar') {
                if (!tiene_sesion_iniciada()) {
                    // Para comprar hay que iniciar sesión. El carrito sigue en la sesión
                    // y, al entrar (o registrarse), se vuelve a esta página con todo igual.
                    $_SESSION['volver_al_carrito'] = true;
                    header('Location: index.php?p=login');
                    exit;
                }
                $_SESSION['mensaje_carrito'] = finalizar_compra($conexion);
            }
            break;
        case 'vaciar':
            $_SESSION['carrito'] = [];
            break;
        case 'aplicar_cupon':
            $codigo = strtoupper(trim($_POST['codigo'] ?? ''));
            if (in_array($codigo, $_SESSION['cupones'] ?? [], true)) {
                $_SESSION['mensaje_carrito'] = ['ok' => false, 'texto' => 'Ese cupón ya está aplicado.'];
            } elseif (obtener_descuento_cupon($conexion, $codigo) > 0) {
                $_SESSION['cupones'][] = $codigo;
            } else {
                $_SESSION['mensaje_carrito'] = ['ok' => false, 'texto' => 'Ese código de descuento no es válido.'];
            }
            break;
        case 'quitar_cupon':
            $_SESSION['cupones'] = array_diff($_SESSION['cupones'] ?? [], [$_POST['codigo'] ?? '']);
            break;
    }
    // Redirigimos para que al recargar la página no se repita la acción
    header('Location: index.php?p=carrito');
    exit;
}

// Título de la pestaña según la sección
$titulos = [
    null                   => 'Inicio',
    'conocenos'            => 'Conócenos',
    'tienda'               => 'Tienda',
    'rebajados'            => 'Rebajados',
    'informacion-producto' => 'Producto',
    'solicitud-recibida'   => 'Mensaje enviado',
    'carrito'              => 'Carrito',
    'login'                => 'Acceder',
    'mi-cuenta'            => 'Mi cuenta',
    'registro'             => 'Crear cuenta',
    'admin'                => 'Administración',
];
$titulo = $titulos[$pagina] ?? 'Página no encontrada';

// Si la sección no existe, avisamos al navegador con un error 404
if ($pagina !== null && !isset($titulos[$pagina])) {
    http_response_code(404);
}

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title><?= escapar_html($titulo) ?> | Start Shop</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="tienda, start shop" name="keywords">
    <meta content="Start Shop - Tienda online" name="description">

    <!-- Favicon -->
    <link href="img/favicon.ico" rel="icon">

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">

    <!-- Libraries Stylesheet -->
    <link href="lib/animate/animate.min.css" rel="stylesheet">
    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">

    <!-- Customized Bootstrap Stylesheet -->
    <!-- ?v= cambia cada vez que se modifica el archivo, así el navegador no usa una copia antigua -->
    <link href="css/style.css?v=<?= filemtime(__DIR__ . '/css/style.css') ?>" rel="stylesheet">
</head>

<body<?= obtener_tema_actual() === 'oscuro' ? ' class="tema-oscuro"' : '' ?>>
    <!-- Topbar Start -->
    <div class="container-fluid">
        <div class="row bg-secondary py-1 px-xl-5">
            <div class="col-lg-6 d-none d-lg-block">
                <div class="d-inline-flex align-items-center h-100">
                    <a class="text-body mr-3" href="index.php?p=conocenos">Conócenos</a>
                </div>
            </div>
            <div class="col-lg-6 text-center text-lg-right">
                <!-- Preferencias del visitante: modo claro/oscuro e idioma (cookies de sesión) -->
                <div class="d-inline-flex align-items-center">
                    <?php if (obtener_tema_actual() === 'oscuro'): ?>
                        <a class="text-body mr-3" href="<?= escapar_html(obtener_url_con_preferencia('tema', 'claro')) ?>"><i class="fa fa-sun mr-1"></i>Modo claro</a>
                    <?php else: ?>
                        <a class="text-body mr-3" href="<?= escapar_html(obtener_url_con_preferencia('tema', 'oscuro')) ?>"><i class="fa fa-moon mr-1"></i>Modo oscuro</a>
                    <?php endif; ?>
                    <!-- "notranslate": Google no traduce los botones ES / EN -->
                    <div class="btn-group notranslate" role="group" aria-label="Cambiar idioma">
                        <?php foreach (IDIOMAS as $codigo_idioma): ?>
                            <a class="btn btn-sm py-0 <?= obtener_idioma_actual() === $codigo_idioma ? 'btn-primary' : 'btn-outline-dark' ?>" href="<?= escapar_html(obtener_url_con_preferencia('idioma', $codigo_idioma)) ?>"><?= strtoupper($codigo_idioma) ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="d-inline-flex align-items-center d-block d-lg-none">
                    <?php mostrar_boton_usuario('text-dark'); ?>
                    <a href="index.php?p=carrito" class="btn px-0 ml-3" title="Carrito">
                        <i class="fas fa-shopping-cart text-dark"></i>
                        <span class="badge text-dark border border-dark rounded-circle" style="padding-bottom: 2px;"><?= contar_unidades_carrito() ?></span>
                    </a>
                </div>
            </div>
        </div>
        <div class="row align-items-center bg-light py-3 px-xl-5 d-none d-lg-flex">
            <div class="col-lg-4">
                <a href="index.php" class="text-decoration-none">
                    <span class="notranslate h1 text-uppercase text-primary bg-dark px-2">Start</span>
                    <span class="notranslate h1 text-uppercase text-dark bg-primary px-2 ml-n1">Shop</span>
                </a>
            </div>
            <div class="col-lg-4 col-6 text-left">
                <!-- Buscador: envía a la tienda con ?q= -->
                <form action="index.php" method="get">
                    <input type="hidden" name="p" value="tienda">
                    <div class="input-group">
                        <input type="text" name="q" class="form-control" placeholder="Buscar productos" value="<?= escapar_html($busqueda) ?>">
                        <div class="input-group-append">
                            <button type="submit" class="input-group-text bg-transparent text-primary" title="Buscar">
                                <i class="fa fa-search"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="col-lg-4 col-6 text-right">
                <p class="m-0">Atención al cliente</p>
                <h5 class="m-0">+012 345 6789</h5>
            </div>
        </div>
    </div>
    <!-- Topbar End -->


    <!-- Navbar Start -->
    <div class="container-fluid bg-dark mb-30">
        <div class="row px-xl-5">
            <div class="col-lg-3 d-none d-lg-block">
                <a class="btn d-flex align-items-center justify-content-between bg-primary w-100" data-toggle="collapse" href="#navbar-vertical" style="height: 65px; padding: 0 30px;">
                    <h6 class="text-dark m-0"><i class="fa fa-bars mr-2"></i>Tienda</h6>
                    <i class="fa fa-angle-down text-dark"></i>
                </a>
                <nav class="collapse position-absolute navbar navbar-vertical navbar-light align-items-start p-0 bg-light" id="navbar-vertical" style="width: calc(100% - 30px); z-index: 999;">
                    <div class="navbar-nav w-100">
                        <a href="index.php?p=tienda" class="nav-item nav-link">Productos</a>
                        <a href="index.php?p=rebajados" class="nav-item nav-link">Rebajados</a>
                    </div>
                </nav>
            </div>
            <div class="col-lg-9">
                <nav class="navbar navbar-expand-lg bg-dark navbar-dark py-3 py-lg-0 px-0">
                    <a href="index.php" class="text-decoration-none d-block d-lg-none">
                        <span class="notranslate h1 text-uppercase text-dark bg-light px-2">Start</span>
                        <span class="notranslate h1 text-uppercase text-light bg-primary px-2 ml-n1">Shop</span>
                    </a>
                    <button type="button" class="navbar-toggler" data-toggle="collapse" data-target="#navbarCollapse" aria-label="Abrir menú de navegación">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <div class="collapse navbar-collapse justify-content-between" id="navbarCollapse">
                        <div class="navbar-nav mr-auto py-0">
                            <a href="index.php" class="nav-item nav-link<?= obtener_clase_activo($pagina === null) ?>">Inicio</a>
                            <a href="index.php?p=conocenos" class="nav-item nav-link<?= obtener_clase_activo($pagina === 'conocenos') ?>">Conócenos</a>
                            <div class="nav-item dropdown">
                                <a href="#" class="nav-link dropdown-toggle<?= obtener_clase_activo(in_array($pagina, ['tienda', 'rebajados', 'informacion-producto'], true)) ?>" data-toggle="dropdown">Tienda<i class="fa fa-angle-down mt-1 ml-1"></i></a>
                                <div class="dropdown-menu bg-primary rounded-0 border-0 m-0">
                                    <a href="index.php?p=tienda" class="dropdown-item">Productos</a>
                                    <div class="dropdown-divider"></div>
                                    <a href="index.php?p=rebajados" class="dropdown-item">Rebajados</a>
                                </div>
                            </div>
                            <?php if (es_admin()): ?>
                                <!-- El panel solo aparece en el menú para los administradores -->
                                <a href="index.php?p=admin" class="nav-item nav-link<?= obtener_clase_activo($pagina === 'admin') ?>">Administración</a>
                            <?php endif; ?>
                            <?php if (tiene_sesion_iniciada()): ?>
                                <!-- Cerrar sesión va por POST (con token) para que un simple enlace no pueda cerrarla -->
                                <form class="nav-item d-flex align-items-center" action="index.php?p=login" method="post">
                                    <input type="hidden" name="accion_sesion" value="salir">
                                    <input type="hidden" name="token_csrf" value="<?= escapar_html(obtener_token_csrf()) ?>">
                                    <button type="submit" class="btn btn-primary btn-sm ml-lg-2 my-2 my-lg-0">Cerrar sesión</button>
                                </form>
                            <?php endif; ?>
                        </div>
                        <div class="navbar-nav ml-auto py-0 d-none d-lg-block">
                            <?php mostrar_boton_usuario('text-primary'); ?>
                            <a href="index.php?p=carrito" class="btn px-0 ml-3" title="Carrito">
                                <i class="fas fa-shopping-cart text-primary"></i>
                                <span class="badge text-secondary border border-secondary rounded-circle" style="padding-bottom: 2px;"><?= contar_unidades_carrito() ?></span>
                            </a>
                        </div>
                    </div>
                </nav>
            </div>
        </div>
    </div>
    <!-- Navbar End -->


    <!-- Contenidos propios de la pagina -->
    <?php
    switch ($pagina) {
        case null:
            include 'inicio.php';
            break;
        case 'conocenos':
            include 'contacus.php';
            break;
        case 'tienda':
        case 'rebajados':
            include 'products-section.php';
            break;
        case 'informacion-producto':
            include 'product-item.php';
            break;
        case 'solicitud-recibida':
            include 'solicitud-recibida.php';
            break;
        case 'carrito':
            include 'carrito.php';
            break;
        case 'mi-cuenta':
            include 'mi-cuenta.php';
            break;
        case 'login':
            include 'login.php';
            break;
        case 'registro':
            include 'registro.php';
            break;
        case 'admin':
            include 'admin.php';
            break;
        default:
            include 'error-404.php';
    }
    ?>


    <!-- Footer Start -->
    <div class="container-fluid bg-dark text-secondary mt-5 pt-5">
        <div class="row px-xl-5 pt-5">
            <div class="col-lg-4 col-md-12 mb-5 pr-3 pr-xl-5">
                <h5 class="text-secondary text-uppercase mb-4">Contacta con nosotros</h5>
                <p class="mb-4">¿Tienes alguna duda sobre un producto o un pedido? Escríbenos y te responderemos lo antes posible.</p>
                <p class="mb-2"><i class="fa fa-map-marker-alt text-primary mr-3"></i>Calle Ejemplo 123, Madrid</p>
                <p class="mb-2"><i class="fa fa-envelope text-primary mr-3"></i>info@startshop.com</p>
                <p class="mb-0"><i class="fa fa-phone-alt text-primary mr-3"></i>+012 345 67890</p>
            </div>
            <div class="col-lg-8 col-md-12">
                <div class="row">
                    <div class="col-md-6 mb-5">
                        <h5 class="text-secondary text-uppercase mb-4">Tienda</h5>
                        <div class="d-flex flex-column justify-content-start">
                            <a class="text-secondary mb-2" href="index.php"><i class="fa fa-angle-right mr-2"></i>Inicio</a>
                            <a class="text-secondary mb-2" href="index.php?p=tienda"><i class="fa fa-angle-right mr-2"></i>Productos</a>
                            <a class="text-secondary mb-2" href="index.php?p=rebajados"><i class="fa fa-angle-right mr-2"></i>Rebajados</a>
                            <a class="text-secondary" href="index.php?p=conocenos"><i class="fa fa-angle-right mr-2"></i>Conócenos</a>
                        </div>
                    </div>
                    <div class="col-md-6 mb-5">
                        <h5 class="text-secondary text-uppercase mb-4">Síguenos</h5>
                        <div class="d-flex">
                            <a class="btn btn-primary btn-square mr-2" href="#"><i class="fab fa-twitter"></i></a>
                            <a class="btn btn-primary btn-square mr-2" href="#"><i class="fab fa-facebook-f"></i></a>
                            <a class="btn btn-primary btn-square mr-2" href="#"><i class="fab fa-linkedin-in"></i></a>
                            <a class="btn btn-primary btn-square" href="#"><i class="fab fa-instagram"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row border-top mx-xl-5 py-4" style="border-color: rgba(256, 256, 256, .1) !important;">
            <div class="col-md-6 px-xl-0">
                <p class="mb-md-0 text-center text-md-left text-secondary">
                    &copy; <?= date('Y') ?> <a class="text-primary" href="index.php">Start Shop</a>. Todos los derechos reservados.
                    Diseño de <a class="text-primary" href="https://htmlcodex.com">HTML Codex</a>
                    <br>Distribuido por: <a href="https://themewagon.com" target="_blank">ThemeWagon</a>
                </p>
            </div>
            <div class="col-md-6 px-xl-0 text-center text-md-right">
                <img class="img-fluid" src="img/payments.png" alt="Métodos de pago">
            </div>
        </div>
    </div>
    <!-- Footer End -->


    <!-- Back to Top -->
    <a href="#" class="btn btn-primary back-to-top"><i class="fa fa-angle-double-up"></i></a>


    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.bundle.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>

    <!-- Template Javascript -->
    <script src="js/main.js"></script>

    <?php if (obtener_idioma_actual() === 'en'): ?>
    <!-- Traducción automática de toda la página con Google Translate.
         Lee la cookie "googtrans" (/es/en) y traduce al cargar, sin diccionario. -->
    <div id="google_translate_element" style="display: none;"></div>
    <script>
        function googleTranslateElementInit() {
            new google.translate.TranslateElement({ pageLanguage: 'es', includedLanguages: 'en', autoDisplay: false }, 'google_translate_element');
        }
    </script>
    <script src="https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
    <?php endif; ?>

    <?php
    // Cerramos la conexión al final
    $conexion->close();
    ?>
</body>

</html>
