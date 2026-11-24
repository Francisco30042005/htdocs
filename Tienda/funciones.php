<?php
/*
  FUNCIONES COMUNES DE LA TIENDA
  Las usan index.php, la plantilla (plantilla/) y las páginas (paginas/).

    1. Base de datos       consultar() y ejecutar()
    2. Textos y precios    escapar_html(), formatear_precio(), migas de pan...
    3. Preferencias        modo claro/oscuro e idioma (cookies)
    4. Productos           productos, categorías, fotos, estrellas y tarjeta de producto
    5. Carrito y cupones   el carrito se guarda en $_SESSION['carrito'] = [id_producto => cantidad]
    6. Usuarios            quién ha iniciado sesión y el token de seguridad de los formularios
    7. Pagos con Stripe    crear el pago y finalizar la compra (guarda el pedido)
    8. Pedidos             estados de un pedido y sus productos
    9. Favoritos           el corazón de los productos
   10. Publicidad          anuncios de clientes y de la tienda
   11. Correo              enviar correos con Gmail (formulario de Conócenos)
*/

const MONEDA = '€';
const GASTOS_ENVIO = 10; // euros por pedido


/* ==========================================================================
   1. BASE DE DATOS
   Todas las consultas pasan por aquí. Los valores van aparte (consulta preparada),
   así un texto escrito por el usuario nunca se mezcla con el SQL (inyección SQL).
   ========================================================================== */

/**
 * Ejecuta un SELECT y devuelve todas las filas.
 * Ejemplo: consultar("SELECT * FROM productos WHERE id = ?", [$id])
 */
function consultar(string $sql, array $valores = []): array
{
    global $conexion;
    $sentencia = $conexion->prepare($sql);
    if ($valores) {
        $sentencia->bind_param(str_repeat('s', count($valores)), ...$valores);
    }
    $sentencia->execute();
    return $sentencia->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Ejecuta un INSERT, UPDATE o DELETE.
 * Devuelve false si falla (por ejemplo, si el valor ya existe en una columna UNIQUE).
 */
function ejecutar(string $sql, array $valores = []): bool
{
    global $conexion;
    $sentencia = $conexion->prepare($sql);
    if ($valores) {
        $sentencia->bind_param(str_repeat('s', count($valores)), ...$valores);
    }
    return $sentencia->execute();
}


/* ==========================================================================
   2. TEXTOS Y PRECIOS
   ========================================================================== */

/** Prepara un texto para mostrarlo en HTML (evita que se cuele código). */
function escapar_html(?string $texto): string
{
    return htmlspecialchars($texto ?? '', ENT_QUOTES, 'UTF-8');
}

/** Precio al estilo español: 1.234,50 € */
function formatear_precio(float $valor): string
{
    return number_format($valor, 2, ',', '.') . ' ' . MONEDA;
}

/** Devuelve ' active' para marcar la opción actual del menú. */
function obtener_clase_activo(bool $condicion): string
{
    return $condicion ? ' active' : '';
}

/**
 * Pinta las migas de pan (Inicio / Tienda / ...).
 * $migas = ['Texto' => 'enlace', ..., 'Página actual' => null]
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


/* ==========================================================================
   3. PREFERENCIAS DEL VISITANTE (cookies "tema" e "idioma")
   ========================================================================== */

/** 'oscuro' o 'claro' (claro si no se ha elegido). */
function obtener_tema_actual(): string
{
    return ($_COOKIE['tema'] ?? '') === 'oscuro' ? 'oscuro' : 'claro';
}

/** 'en' o 'es' (español si no se ha elegido). */
function obtener_idioma_actual(): string
{
    return ($_COOKIE['idioma'] ?? '') === 'en' ? 'en' : 'es';
}

/** Enlace a la página actual con una preferencia añadida (?tema=oscuro, ?idioma=en...). */
function obtener_url_con_preferencia(string $nombre, string $valor): string
{
    $parametros = $_GET;
    $parametros[$nombre] = $valor;
    return 'index.php?' . http_build_query($parametros);
}

/** Guarda una cookie durante 30 días para toda la web. */
function guardar_cookie(string $nombre, string $valor): void
{
    // Versiones anteriores la guardaban solo en /Tienda: se borra para que no estorbe
    setcookie($nombre, '', time() - 3600, dirname($_SERVER['SCRIPT_NAME']));
    setcookie($nombre, $valor, time() + 30 * 24 * 60 * 60, '/');
}


/* ==========================================================================
   4. PRODUCTOS
   ========================================================================== */

/*
  Consulta base de los productos. La valoración no se guarda en la tabla:
  se calcula como la media de las puntuaciones de sus reseñas (redondeada a 1 decimal).
  Si un producto no tiene reseñas, la valoración es NULL (0 estrellas).
*/
const SQL_PRODUCTOS = "SELECT p.*, (SELECT ROUND(AVG(r.puntuacion), 1) FROM resenas r WHERE r.producto_id = p.id) AS valoracion
                       FROM productos p";

/**
 * Productos de la tienda, del más nuevo al más antiguo. Todos los filtros son opcionales:
 *   obtener_productos()                              todos
 *   obtener_productos(rebajados: true, limite: 8)    los 8 últimos rebajados
 *   obtener_productos(busqueda: 'mando')             los que contienen "mando"
 *   obtener_productos(categoria: 3)                  los de la categoría 3
 *   obtener_productos(excluir: 7)                    todos menos el producto 7
 */
function obtener_productos(bool $rebajados = false, string $busqueda = '', ?int $categoria = null, ?int $limite = null, ?int $excluir = null): array
{
    $sql = SQL_PRODUCTOS . " WHERE 1 = 1";
    $valores = [];

    if ($rebajados) {
        $sql .= " AND precio_rebajado > 0";
    }
    if ($busqueda !== '') {
        $sql .= " AND (nombre LIKE ? OR referencia LIKE ? OR descripcion LIKE ?)";
        array_push($valores, "%$busqueda%", "%$busqueda%", "%$busqueda%");
    }
    if ($categoria !== null) {
        $sql .= " AND categoria_id = ?";
        $valores[] = $categoria;
    }
    if ($excluir !== null) {
        $sql .= " AND id <> ?";
        $valores[] = $excluir;
    }
    $sql .= " ORDER BY id DESC";
    if ($limite !== null) {
        $sql .= " LIMIT " . (int) $limite;
    }
    return consultar($sql, $valores);
}

/** Un producto por su id, o null si no existe. */
function obtener_producto(int $id): ?array
{
    return consultar(SQL_PRODUCTOS . " WHERE id = ?", [$id])[0] ?? null;
}

/** Categorías ordenadas por nombre, con "total" = número de productos de cada una. */
function obtener_categorias(): array
{
    return consultar("SELECT c.id, c.nombre, COUNT(p.id) AS total
                      FROM categorias c LEFT JOIN productos p ON p.categoria_id = c.id
                      GROUP BY c.id, c.nombre ORDER BY c.nombre");
}

/** true si el producto tiene precio rebajado. */
function tiene_precio_rebajado(array $producto): bool
{
    return $producto['precio_rebajado'] > 0;
}

/** Precio que se cobra: el rebajado si lo tiene, si no el normal. */
function calcular_precio_final(array $producto): float
{
    return tiene_precio_rebajado($producto) ? (float) $producto['precio_rebajado'] : (float) $producto['precio'];
}

/** Enlace a la ficha del producto. */
function obtener_url_producto(array $producto): string
{
    return 'index.php?p=informacion-producto&id=' . (int) $producto['id'];
}

/**
 * Foto del producto: img/REFERENCIA.jpg (o .png). Si no tiene, una imagen gris de relleno.
 * El ?v=fecha hace que el navegador no use una foto antigua guardada en su caché.
 */
function obtener_imagen_producto(array $producto): string
{
    foreach (['jpg', 'png'] as $extension) {
        $ruta = 'img/' . $producto['referencia'] . '.' . $extension;
        if (is_file(__DIR__ . '/' . $ruta)) {
            return $ruta . '?v=' . filemtime(__DIR__ . '/' . $ruta);
        }
    }
    return 'https://dummyimage.com/600x600/dee2e6/6c757d.jpg';
}

/** HTML de las 5 estrellas de una valoración (de 0 a 5, admite medias estrellas). */
function generar_estrellas_html(?float $valoracion, string $clase = 'text-primary mr-1'): string
{
    $valor = round((float) $valoracion * 2) / 2; // redondeo a la media estrella
    $html = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($valor >= $i) {
            $icono = 'fas fa-star';           // llena
        } elseif ($valor >= $i - 0.5) {
            $icono = 'fas fa-star-half-alt';  // media
        } else {
            $icono = 'far fa-star';           // vacía
        }
        $html .= '<small class="' . $icono . ' ' . $clase . '"></small>';
    }
    return $html;
}

/** Pinta la tarjeta de un producto (Inicio, Tienda y "También te puede gustar"). */
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
                <?php mostrar_boton_favorito($producto, 'btn btn-outline-dark btn-square'); ?>
            </div>
            <?php if (es_favorito($producto['id'])): ?>
                <span class="position-absolute text-danger" style="top: 10px; left: 12px; z-index: 1;" title="En tus favoritos"><i class="fas fa-heart"></i></span>
            <?php endif; ?>
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


/* ==========================================================================
   5. CARRITO Y CUPONES
   Carrito:  $_SESSION['carrito'] = [id_producto => cantidad]
   Cupones:  $_SESSION['cupones'] = ['VERANO20', ...]
   ========================================================================== */

/** Número total de unidades en el carrito (el numerito del icono). */
function contar_unidades_carrito(): int
{
    return array_sum($_SESSION['carrito'] ?? []);
}

/** Cambia la cantidad de un producto del carrito (0 o menos = se quita). */
function cambiar_cantidad_carrito(int $id, int $cantidad): void
{
    if ($cantidad > 0) {
        $_SESSION['carrito'][$id] = $cantidad;
    } else {
        unset($_SESSION['carrito'][$id]);
    }
}

/**
 * Líneas del carrito con los datos de cada producto:
 * [['producto' => [...], 'cantidad' => 2, 'precio' => 9.5, 'total' => 19.0], ...]
 * Si un producto ya no existe en la base de datos, se quita del carrito.
 */
function obtener_lineas_carrito(): array
{
    $lineas = [];
    foreach ($_SESSION['carrito'] ?? [] as $id => $cantidad) {
        $producto = obtener_producto($id);
        if (!$producto) {
            unset($_SESSION['carrito'][$id]);
            continue;
        }
        $precio = calcular_precio_final($producto);
        $lineas[] = ['producto' => $producto, 'cantidad' => $cantidad, 'precio' => $precio, 'total' => $precio * $cantidad];
    }
    return $lineas;
}

/** % de descuento de un cupón, o 0 si no existe o está desactivado. */
function obtener_descuento_cupon(string $codigo): int
{
    $cupon = consultar("SELECT porcentaje FROM cupones WHERE codigo = ? AND activo = 1", [$codigo]);
    return $cupon ? (int) $cupon[0]['porcentaje'] : 0;
}

/** Suma de los % de todos los cupones aplicados (como máximo 100 %). */
function obtener_descuento_total(): int
{
    $porcentaje = 0;
    foreach ($_SESSION['cupones'] ?? [] as $codigo) {
        $porcentaje += obtener_descuento_cupon($codigo);
    }
    return min($porcentaje, 100);
}


/* ==========================================================================
   6. USUARIOS
   El usuario que ha iniciado sesión se guarda en
   $_SESSION['usuario'] = ['id' => 1, 'usuario' => 'admin', 'rol' => 'admin']
   ========================================================================== */

/** true si hay alguien (cliente o administrador) con la sesión iniciada. */
function tiene_sesion_iniciada(): bool
{
    return isset($_SESSION['usuario']);
}

/** true si el que ha iniciado sesión es administrador. */
function es_admin(): bool
{
    return ($_SESSION['usuario']['rol'] ?? '') === 'admin';
}

/**
 * Icono de usuario de la cabecera: sin sesión lleva al login; con sesión muestra
 * el nombre y lleva a "Mi cuenta". $color = clase del color del icono.
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
 * true si la dirección tiene el formato de una dirección española:
 *   tipo de vía + nombre + número [, piso/puerta], código postal + ciudad
 *   Ejemplos: "Calle Mayor 12, 28013 Madrid"   "Avda. de la Paz 23, 3º B, 41001 Sevilla"
 * Tipos de vía: Calle (C/), Avenida (Avda./Av.), Plaza (Pza.), Paseo, Camino, Carretera (Ctra.),
 * Ronda, Travesía, Glorieta, Vía y Urbanización (Urb.). El número puede ser "s/n".
 * El código postal tiene 5 cifras y empieza por 01-52 (las provincias españolas).
 */
function es_direccion_valida(string $direccion): bool
{
    $tipo   = '(calle|c\/|avenida|avda\.?|av\.?|plaza|pza\.?|paseo|camino|carretera|ctra\.?|ronda|traves[ií]a|glorieta|v[ií]a|urbanizaci[oó]n|urb\.?)';
    $nombre = '[\p{L}][\p{L}\s.\'ºª-]*';             // letras (con tildes), espacios, puntos...
    $numero = '(n[ºo]\.?\s*)?(\d{1,4}\s?[a-z]?|s\/n)'; // 12, 12B, nº 12 o s/n
    $piso   = '(,\s*[\p{L}\d\sºª.\/-]{1,20})?';       // opcional: ", 3º B"
    $cp     = '(0[1-9]|[1-4]\d|5[0-2])\d{3}';         // 01000 a 52999
    $ciudad = '[\p{L}][\p{L}\s.\'-]*';
    // i = da igual mayúsculas o minúsculas; u = letras con tildes y ñ
    return preg_match("/^$tipo\s*$nombre,?\s+$numero$piso,\s*$cp\s+$ciudad$/iu", trim($direccion)) === 1;
}

/*
  Contraseñas seguras: al menos 9 caracteres, con mayúscula, minúscula, número y carácter especial.
  PATRON_CONTRASENA es la misma regla para el atributo pattern de los formularios
  (así el navegador avisa antes de enviar); la comprobación de verdad es comprobar_contrasena().
*/
const PATRON_CONTRASENA = '(?=.*\p{Lu})(?=.*\p{Ll})(?=.*\d)(?=.*[^\p{L}\p{N}]).{9,}';
const REQUISITOS_CONTRASENA = 'Mínimo 9 caracteres, con una mayúscula, una minúscula, un número y un carácter especial (! ? @ # $ % & * . -)';

/**
 * Comprueba que una contraseña sea segura.
 * Devuelve '' si cumple todo, o un mensaje con lo que le falta, por ejemplo:
 * "La contraseña debe tener una letra mayúscula y un carácter especial."
 */
function comprobar_contrasena(string $contrasena): string
{
    $falta = [];
    if (mb_strlen($contrasena) < 9) {
        $falta[] = 'al menos 9 caracteres';
    }
    if (!preg_match('/\p{Lu}/u', $contrasena)) {   // \p{Lu} = letra mayúscula (también Ñ, Á...)
        $falta[] = 'una letra mayúscula';
    }
    if (!preg_match('/\p{Ll}/u', $contrasena)) {   // \p{Ll} = letra minúscula
        $falta[] = 'una letra minúscula';
    }
    if (!preg_match('/\d/', $contrasena)) {
        $falta[] = 'un número';
    }
    if (!preg_match('/[^\p{L}\p{N}]/u', $contrasena)) { // algo que no sea letra ni número
        $falta[] = 'un carácter especial (por ejemplo ! ? @ # $ % & * . -)';
    }
    if (!$falta) {
        return '';
    }
    $ultimo = array_pop($falta); // "a, b y c"
    return 'La contraseña debe tener ' . ($falta ? implode(', ', $falta) . ' y ' : '') . $ultimo . '.';
}

/*
  CONTRASEÑAS CIFRADAS
  En la base de datos nunca se guarda la contraseña, sino su "hash" (password_hash):
  un texto del que no se puede sacar la contraseña original. Para comprobarla
  se usa password_verify(lo que ha escrito, hash guardado).
*/

/** Cifra una contraseña para guardarla en la base de datos. */
function cifrar_contrasena(string $contrasena): string
{
    return password_hash($contrasena, PASSWORD_DEFAULT);
}

/**
 * true si la contraseña escrita es la del usuario.
 * Las cuentas antiguas pueden tener la contraseña sin cifrar: si coincide,
 * se cifra en ese momento y a partir de ahí ya queda cifrada.
 */
function es_contrasena_correcta(int $id_usuario, string $escrita, string $guardada): bool
{
    if (password_verify($escrita, $guardada)) {
        return true;
    }
    $sin_cifrar = !str_starts_with($guardada, '$2y$'); // los hash de password_hash empiezan por $2y$
    if ($sin_cifrar && hash_equals($guardada, $escrita)) {
        ejecutar("UPDATE usuarios SET contrasena = ? WHERE id = ?", [cifrar_contrasena($escrita), $id_usuario]);
        return true;
    }
    return false;
}

/*
  BLOQUEO TRAS VARIOS FALLOS
  Si alguien falla la contraseña INTENTOS_MAXIMOS veces seguidas, la cuenta
  no deja entrar durante MINUTOS_BLOQUEO minutos (frena a quien prueba contraseñas al azar).
*/
const INTENTOS_MAXIMOS = 5;
const MINUTOS_BLOQUEO = 15;

/** Apunta un fallo y, si ya van INTENTOS_MAXIMOS, bloquea la cuenta. */
function anotar_intento_fallido(int $id_usuario): void
{
    ejecutar("UPDATE usuarios SET intentos_fallidos = intentos_fallidos + 1 WHERE id = ?", [$id_usuario]);
    ejecutar("UPDATE usuarios SET bloqueado_hasta = NOW() + INTERVAL ? MINUTE, intentos_fallidos = 0
              WHERE id = ? AND intentos_fallidos >= ?", [MINUTOS_BLOQUEO, $id_usuario, INTENTOS_MAXIMOS]);
}

/** Dirección de envío del cliente conectado (la de "Mi cuenta"), o '' si no tiene una válida. */
function obtener_direccion_envio(): string
{
    $direccion = consultar("SELECT direccion FROM usuarios WHERE id = ?", [$_SESSION['usuario']['id']])[0]['direccion'] ?? '';
    return es_direccion_valida((string) $direccion) ? trim($direccion) : '';
}

/**
 * Adónde ir después de iniciar sesión o registrarse:
 * al carrito si venía de "Pagar" (su carrito sigue guardado), al panel si es admin
 * y a la tienda si es cliente.
 */
function obtener_destino_tras_entrar(): string
{
    if (!empty($_SESSION['volver_al_carrito'])) {
        unset($_SESSION['volver_al_carrito']);
        return 'index.php?p=carrito';
    }
    return es_admin() ? 'index.php?p=admin' : 'index.php';
}

/**
 * Token de seguridad (anti-CSRF): un código aleatorio de la sesión que va oculto en los
 * formularios del panel y de cerrar sesión. Así otra web no puede enviarlos en tu nombre.
 */
function obtener_token_csrf(): string
{
    if (empty($_SESSION['token_csrf'])) {
        $_SESSION['token_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['token_csrf'];
}

/** true si el formulario enviado lleva el token correcto. */
function tiene_token_csrf_valido(): bool
{
    return hash_equals(obtener_token_csrf(), $_POST['token_csrf'] ?? '');
}


/* ==========================================================================
   7. PAGOS CON STRIPE
   El cliente paga en la página de Stripe y vuelve a index.php?p=carrito&session_id=...
   La clave secreta está en config-stripe.php (ese archivo no se sube a GitHub).
   ========================================================================== */

/**
 * Llama a la API de Stripe y devuelve su respuesta como array.
 * Si algo falla, la respuesta trae ['error' => ['message' => ...]].
 */
function llamar_stripe(string $metodo, string $ruta, array $datos = []): array
{
    $contexto = stream_context_create(['http' => [
        'method'        => $metodo,
        'header'        => "Authorization: Bearer " . STRIPE_CLAVE_SECRETA . "\r\n"
                         . "Content-Type: application/x-www-form-urlencoded\r\n",
        'content'       => http_build_query($datos),
        'ignore_errors' => true, // para poder leer también los mensajes de error de Stripe
        'timeout'       => 20,
    ]]);
    $respuesta = @file_get_contents('https://api.stripe.com/v1/' . $ruta, false, $contexto);
    if ($respuesta === false) {
        return ['error' => ['message' => 'No se ha podido conectar con Stripe.']];
    }
    return json_decode($respuesta, true);
}

/**
 * Crea el pago en Stripe con los productos del carrito (con el descuento de los cupones)
 * y los gastos de envío. Devuelve la dirección de la página de pago, o un mensaje de error.
 */
function crear_pago_stripe(): array
{
    $lineas = obtener_lineas_carrito();
    if (!$lineas) {
        return ['error' => 'Tu carrito está vacío.'];
    }
    // Antes de cobrar, comprobamos que hay unidades de todo
    foreach ($lineas as $linea) {
        if ($linea['cantidad'] > $linea['producto']['existencias']) {
            return ['error' => 'No hay unidades suficientes de «' . $linea['producto']['nombre'] . '» (quedan '
                . $linea['producto']['existencias'] . '). Cambia la cantidad y vuelve a intentarlo.'];
        }
    }

    // Cada producto con su precio ya descontado (Stripe trabaja en céntimos)
    $porcentaje = obtener_descuento_total();
    $productos = [];
    foreach ($lineas as $linea) {
        $precio = $linea['precio'] - $linea['precio'] * $porcentaje / 100;
        $nombre = $linea['producto']['nombre'] . ($porcentaje ? " (−$porcentaje % con cupón)" : '');
        $productos[] = [
            'price_data' => ['currency' => 'eur', 'unit_amount' => (int) round($precio * 100), 'product_data' => ['name' => $nombre]],
            'quantity'   => $linea['cantidad'],
        ];
    }
    $productos[] = [
        'price_data' => ['currency' => 'eur', 'unit_amount' => GASTOS_ENVIO * 100, 'product_data' => ['name' => 'Gastos de envío']],
        'quantity'   => 1,
    ];

    // Dirección de la tienda (p. ej. http://localhost/Tienda/) para volver desde Stripe
    $web = 'http://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/';

    $pago = llamar_stripe('POST', 'checkout/sessions', [
        'mode'        => 'payment',
        'locale'      => 'es',
        'line_items'  => $productos,
        'success_url' => $web . 'index.php?p=carrito&session_id={CHECKOUT_SESSION_ID}',
        'cancel_url'  => $web . 'index.php?p=carrito',
    ]);
    if (isset($pago['error'])) {
        return ['error' => 'No se ha podido iniciar el pago: ' . $pago['error']['message']];
    }
    $_SESSION['pago_stripe'] = $pago['id']; // para comprobarlo cuando el cliente vuelva
    return ['url' => $pago['url']];
}

/**
 * Termina la compra cuando Stripe confirma el pago:
 *   1. descuenta las existencias de cada producto,
 *   2. guarda el pedido y sus productos,
 *   3. vacía el carrito y los cupones.
 * Todo va en una transacción: si de algún producto no quedan unidades suficientes,
 * no se guarda nada y devuelve false.
 * $total = lo que ha cobrado Stripe; $id_pago = el id del pago en Stripe.
 */
function finalizar_compra(float $total, string $id_pago): bool
{
    global $conexion;
    $lineas = obtener_lineas_carrito();
    $id_usuario = $_SESSION['usuario']['id'];

    $conexion->begin_transaction();

    // 1. Existencias (solo se descuenta si quedan unidades suficientes)
    foreach ($lineas as $linea) {
        ejecutar("UPDATE productos SET existencias = existencias - ? WHERE id = ? AND existencias >= ?",
                 [$linea['cantidad'], $linea['producto']['id'], $linea['cantidad']]);
        if (consultar("SELECT ROW_COUNT() AS filas")[0]['filas'] != 1) { // ROW_COUNT() = filas cambiadas
            $conexion->rollback();
            return false;
        }
    }

    // 2. El pedido, con la dirección que tenga el cliente en "Mi cuenta", y sus productos
    $direccion = consultar("SELECT direccion FROM usuarios WHERE id = ?", [$id_usuario])[0]['direccion'];
    ejecutar("INSERT INTO pedidos (usuario_id, total, descuento, direccion, pago_stripe) VALUES (?, ?, ?, ?, ?)",
             [$id_usuario, $total, obtener_descuento_total(), $direccion, $id_pago]);
    $id_pedido = consultar("SELECT LAST_INSERT_ID() AS id")[0]['id']; // id del pedido recién creado
    foreach ($lineas as $linea) {
        ejecutar("INSERT INTO pedido_lineas (pedido_id, producto_id, nombre, precio, cantidad) VALUES (?, ?, ?, ?, ?)",
                 [$id_pedido, $linea['producto']['id'], $linea['producto']['nombre'], $linea['precio'], $linea['cantidad']]);
    }

    $conexion->commit();

    // 3. Carrito vacío
    $_SESSION['carrito'] = [];
    $_SESSION['cupones'] = [];
    return true;
}


/* ==========================================================================
   8. PEDIDOS
   ========================================================================== */

/** Estados de un pedido y el color de su etiqueta (clases "badge" de Bootstrap). */
const ESTADOS_PEDIDO = [
    'pendiente' => 'warning',
    'enviado'   => 'info',
    'entregado' => 'success',
    'cancelado' => 'danger',
];

/** Productos de un pedido: nombre, precio por unidad y cantidad. */
function obtener_lineas_pedido(int $id_pedido): array
{
    return consultar("SELECT nombre, precio, cantidad FROM pedido_lineas WHERE pedido_id = ?", [$id_pedido]);
}

/** Etiqueta de color con el estado del pedido (Pendiente, Enviado...). */
function mostrar_estado_pedido(string $estado): void
{
    echo '<span class="badge badge-' . ESTADOS_PEDIDO[$estado] . ' px-2 py-1">' . ucfirst($estado) . '</span>';
}


/* ==========================================================================
   9. FAVORITOS
   Los productos que cada cliente marca con el corazón (tabla favoritos).
   ========================================================================== */

/** ids de los productos favoritos del cliente conectado ([] si no ha iniciado sesión). */
function obtener_ids_favoritos(): array
{
    static $ids = null; // "static": se consulta una sola vez por página aunque haya muchas tarjetas
    if ($ids === null) {
        $ids = [];
        if (tiene_sesion_iniciada()) {
            $filas = consultar("SELECT producto_id FROM favoritos WHERE usuario_id = ?", [$_SESSION['usuario']['id']]);
            $ids = array_column($filas, 'producto_id');
        }
    }
    return $ids;
}

/** true si el producto está en los favoritos del cliente conectado. */
function es_favorito(int $id_producto): bool
{
    return in_array($id_producto, obtener_ids_favoritos());
}

/**
 * Botón del corazón para añadir o quitar un producto de favoritos.
 * Lleno y en rojo si ya es favorito. $con_texto = true pone también el texto (ficha de producto).
 */
function mostrar_boton_favorito(array $producto, string $clase, bool $con_texto = false): void
{
    $favorito = es_favorito($producto['id']);
    ?>
    <form class="d-inline" action="index.php?p=favoritos" method="post">
        <input type="hidden" name="id" value="<?= (int) $producto['id'] ?>">
        <!-- Para volver a la misma página después de pulsar -->
        <input type="hidden" name="volver" value="<?= escapar_html($_SERVER['QUERY_STRING'] ?? '') ?>">
        <button type="submit" class="<?= $clase ?><?= $favorito ? ' favorito' : '' ?>" title="<?= $favorito ? 'Quitar de favoritos' : 'Añadir a favoritos' ?>">
            <i class="<?= $favorito ? 'fas' : 'far' ?> fa-heart"></i><?= $con_texto ? ($favorito ? ' En favoritos' : ' Añadir a favoritos') : '' ?>
        </button>
    </form>
    <?php
}


/* ==========================================================================
   10. PUBLICIDAD
   Cualquier cliente puede proponer un anuncio desde "Mi cuenta"; queda "pendiente"
   hasta que un administrador lo aprueba o lo rechaza en el panel. El administrador
   también puede crear anuncios, que se publican directamente.
   En la tienda solo se ven los "aprobado" (inicio y barra lateral de la tienda).
   ========================================================================== */

// Estado del anuncio => color de Bootstrap para su etiqueta
const ESTADOS_ANUNCIO = ['pendiente' => 'warning', 'aprobado' => 'success', 'rechazado' => 'danger'];

/**
 * Guarda el anuncio del formulario (campos titulo, texto, enlace e imagen).
 * Lo usan "Mi cuenta" (estado 'pendiente') y el panel de administración ('aprobado').
 * Devuelve '' si se ha guardado o el texto del error.
 */
function guardar_anuncio(int $usuario_id, string $estado): string
{
    $titulo = trim($_POST['titulo'] ?? '');
    $texto = trim($_POST['texto'] ?? '');
    $enlace = trim($_POST['enlace'] ?? '');

    if ($titulo === '' || mb_strlen($titulo) > 80) {
        return 'El título es obligatorio (máximo 80 caracteres).';
    }
    if ($texto === '' || mb_strlen($texto) > 300) {
        return 'El texto es obligatorio (máximo 300 caracteres).';
    }
    // El enlace es opcional: una web (http:// o https://) o una página de la tienda (index.php?...)
    $es_web = filter_var($enlace, FILTER_VALIDATE_URL) && preg_match('#^https?://#i', $enlace);
    $es_de_la_tienda = preg_match('#^index\.php(\?[\w=&%.\-]*)?$#', $enlace);
    if ($enlace !== '' && (!$es_web && !$es_de_la_tienda || mb_strlen($enlace) > 300)) {
        return 'El enlace tiene que empezar por http:// o https:// (o ser una página de la tienda, index.php?...).';
    }

    // Imagen (opcional): JPG o PNG de hasta 2 MB, igual que las fotos de producto
    $imagen = null;
    $archivo = $_FILES['imagen'] ?? null;
    if ($archivo && $archivo['error'] !== UPLOAD_ERR_NO_FILE) {
        $tipos = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png'];
        $info = $archivo['error'] === UPLOAD_ERR_OK ? @getimagesize($archivo['tmp_name']) : false;
        if ($archivo['error'] !== UPLOAD_ERR_OK || $archivo['size'] > 2 * 1024 * 1024) {
            return 'No se ha podido subir la imagen: tiene que ocupar 2 MB como mucho.';
        }
        if (!$info || !isset($tipos[$info[2]])) {
            return 'La imagen tiene que ser JPG o PNG.';
        }
        // Nombre único (uniqid) para que dos anuncios no se pisen la imagen
        if (!is_dir('img/anuncios')) {
            mkdir('img/anuncios');
        }
        $imagen = 'img/anuncios/anuncio-' . uniqid() . '.' . $tipos[$info[2]];
        move_uploaded_file($archivo['tmp_name'], $imagen);
    }

    ejecutar("INSERT INTO anuncios (usuario_id, titulo, texto, imagen, enlace, estado) VALUES (?, ?, ?, ?, ?, ?)",
             [$usuario_id, $titulo, $texto, $imagen, $enlace !== '' ? $enlace : null, $estado]);
    return '';
}

/** Borra un anuncio y su imagen (si se subió con el anuncio). */
function borrar_anuncio(array $anuncio): void
{
    if ($anuncio['imagen'] && str_starts_with($anuncio['imagen'], 'img/anuncios/') && is_file($anuncio['imagen'])) {
        unlink($anuncio['imagen']);
    }
    ejecutar("DELETE FROM anuncios WHERE id = ?", [$anuncio['id']]);
}

/** Anuncios aprobados, en orden aleatorio (así van rotando). */
function obtener_anuncios_aprobados(int $limite): array
{
    return consultar("SELECT * FROM anuncios WHERE estado = 'aprobado' ORDER BY RAND() LIMIT " . $limite);
}

/** Pinta un anuncio como una tarjeta, con la etiqueta "Publicidad". */
function mostrar_anuncio(array $anuncio): void
{
    // Los enlaces a otras webs se abren en una pestaña nueva
    $externo = $anuncio['enlace'] && preg_match('#^https?://#i', $anuncio['enlace']);
    ?>
    <div class="bg-light h-100 d-flex flex-column position-relative">
        <span class="badge badge-dark position-absolute" style="top: 10px; right: 10px; z-index: 1;">Publicidad</span>
        <?php if ($anuncio['imagen']): ?>
            <img class="w-100" src="<?= escapar_html($anuncio['imagen']) ?>" alt="" style="height: 160px; object-fit: cover;">
        <?php else: ?>
            <div class="bg-primary d-flex align-items-center justify-content-center" style="height: 160px;">
                <i class="fa fa-bullhorn fa-3x text-dark"></i>
            </div>
        <?php endif; ?>
        <div class="p-3 d-flex flex-column flex-grow-1">
            <h6 class="mb-2"><?= escapar_html($anuncio['titulo']) ?></h6>
            <p class="small mb-3"><?= escapar_html($anuncio['texto']) ?></p>
            <?php if ($anuncio['enlace']): ?>
                <a class="btn btn-sm btn-primary mt-auto align-self-start" href="<?= escapar_html($anuncio['enlace']) ?>"
                   <?= $externo ? 'target="_blank" rel="noopener nofollow sponsored"' : '' ?>>Ver más</a>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

/** Etiqueta de color con el estado del anuncio. */
function mostrar_estado_anuncio(string $estado): void
{
    echo '<span class="badge badge-' . ESTADOS_ANUNCIO[$estado] . ' px-2 py-1">' . ucfirst($estado) . '</span>';
}

/**
 * Formulario para crear un anuncio (Mi cuenta y panel de administración).
 * $accion_campo / $accion_valor: el campo oculto que identifica el formulario en cada página.
 */
function mostrar_formulario_anuncio(string $destino, string $accion_campo, string $accion_valor, string $boton): void
{
    ?>
    <!-- enctype multipart/form-data: obligatorio para poder subir la imagen -->
    <form action="<?= $destino ?>" method="post" enctype="multipart/form-data">
        <input type="hidden" name="<?= $accion_campo ?>" value="<?= $accion_valor ?>">
        <input type="hidden" name="token_csrf" value="<?= escapar_html(obtener_token_csrf()) ?>">
        <div class="form-group">
            <label for="titulo">Título</label>
            <input type="text" id="titulo" name="titulo" class="form-control" maxlength="80" required value="<?= escapar_html($_POST['titulo'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="texto">Texto <small class="text-muted">(máximo 300 caracteres)</small></label>
            <textarea id="texto" name="texto" class="form-control" rows="3" maxlength="300" required><?= escapar_html($_POST['texto'] ?? '') ?></textarea>
        </div>
        <div class="form-group">
            <label for="enlace">Enlace <small class="text-muted">(opcional: https://... o index.php?...)</small></label>
            <input type="text" id="enlace" name="enlace" class="form-control" maxlength="300" placeholder="https://www.ejemplo.com" value="<?= escapar_html($_POST['enlace'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="imagen">Imagen <small class="text-muted">(opcional: JPG o PNG, máximo 2 MB)</small></label>
            <input type="file" id="imagen" name="imagen" class="form-control-file" accept="image/jpeg,image/png">
        </div>
        <button type="submit" class="btn btn-primary"><i class="fa fa-bullhorn mr-1"></i> <?= $boton ?></button>
    </form>
    <?php
}


/* ==========================================================================
   11. CORREO
   XAMPP no trae servidor de correo, así que mail() no funciona. En su lugar la tienda
   se conecta a Gmail (SMTP) con la cuenta de config-correo.php y le pide que envíe el correo.
   Una conversación SMTP es texto: el programa escribe una orden y el servidor contesta
   con un número (2xx o 3xx = bien, 4xx o 5xx = error).
   ========================================================================== */

/** true si config-correo.php existe y está rellenado. */
function correo_configurado(): bool
{
    return defined('CORREO_TIENDA') && CORREO_TIENDA !== '' && CORREO_CONTRASENA_APP !== '';
}

/**
 * Envía un correo de texto desde la cuenta de la tienda.
 * $responder_a: a quién se responde al pulsar "Responder" (opcional).
 * Devuelve true si Gmail lo ha aceptado.
 */
function enviar_correo(string $para, string $asunto, string $texto, string $responder_a = ''): bool
{
    if (!correo_configurado()) {
        return false;
    }
    // Conexión segura (SSL) con el servidor de Gmail; si en 10 segundos no contesta, se abandona
    $servidor = @stream_socket_client('ssl://smtp.gmail.com:465', $num_error, $error, 10);
    if (!$servidor) {
        return false;
    }

    // Manda una orden y comprueba que la respuesta empieza por 2 o 3
    $orden = function (?string $linea) use ($servidor): bool {
        if ($linea !== null) {
            fwrite($servidor, $linea . "\r\n");
        }
        // La respuesta puede tener varias líneas: "250-..." sigue, "250 ..." es la última
        do {
            $respuesta = fgets($servidor, 1024);
        } while ($respuesta !== false && ($respuesta[3] ?? ' ') === '-');
        return $respuesta !== false && in_array($respuesta[0], ['2', '3']);
    };

    // Cabeceras del correo. El asunto se codifica en base64 para que salgan bien las tildes.
    $cabeceras = "From: Tio Pepe <" . CORREO_TIENDA . ">\r\n"
               . "To: <$para>\r\n"
               . ($responder_a !== '' ? "Reply-To: <$responder_a>\r\n" : '')
               . "Subject: =?UTF-8?B?" . base64_encode($asunto) . "?=\r\n"
               . "Date: " . date('r') . "\r\n"
               . "MIME-Version: 1.0\r\n"
               . "Content-Type: text/plain; charset=utf-8\r\n"
               . "Content-Transfer-Encoding: 8bit\r\n";
    // Una línea que sea solo "." termina el correo: por eso se dobla el punto al principio de línea
    $cuerpo = preg_replace('/^\./m', '..', str_replace(["\r\n", "\r"], "\n", $texto));
    $cuerpo = str_replace("\n", "\r\n", $cuerpo);

    $enviado = $orden(null)                                        // saludo del servidor
        && $orden('EHLO localhost')                                // "hola"
        && $orden('AUTH LOGIN')                                    // iniciar sesión...
        && $orden(base64_encode(CORREO_TIENDA))                    // ...usuario
        && $orden(base64_encode(str_replace(' ', '', CORREO_CONTRASENA_APP))) // ...contraseña de aplicación
        && $orden('MAIL FROM:<' . CORREO_TIENDA . '>')             // quién lo envía
        && $orden("RCPT TO:<$para>")                               // a quién
        && $orden('DATA')                                          // ahora va el correo
        && $orden($cabeceras . "\r\n" . $cuerpo . "\r\n.");        // cabeceras + texto + "." final
    $orden('QUIT');
    fclose($servidor);
    return $enviado;
}
