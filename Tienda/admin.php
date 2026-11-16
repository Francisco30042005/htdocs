<?php
/*
  Panel de administración y acciones de sesión: todo en este archivo.
  index.php lo carga con require ANTES de pintar la página, porque al terminar
  una acción redirigimos con header('Location: ...').

  Contenido:
    1. ACCIONES  - iniciar/cerrar sesión, registro, Mi cuenta y los formularios del panel
                   (productos, cupones y categorías).
    2. VISTAS    - las pestañas del panel (index.php?p=admin&seccion=...).
                   index.php llama a mostrar_panel_admin() dentro de la página.

  Variables que deja preparadas:
    $error_login       Mensaje si el usuario o la contraseña no son correctos (login.php)
    $errores_registro  Errores del formulario de registro (registro.php)
    $mensaje_cuenta    Mensaje de Mi cuenta (mi-cuenta.php)
    $errores_admin     Lista de errores del formulario de producto
    $datos_producto    Valores del formulario de producto (para no perder lo escrito si hay errores)
*/

/* ==========================================================================
   1. ACCIONES
   ========================================================================== */

const TAMANO_MAXIMO_FOTO = 2 * 1024 * 1024; // 2 MB
const EXTENSIONES_FOTO = ['jpg', 'JPG', 'jpeg', 'png'];

$error_login = '';
$errores_admin = [];
$datos_producto = null;

/** Borra las fotos de una referencia (img/COD-xx.jpg, .png...). */
function borrar_fotos_producto(string $referencia): void
{
    foreach (EXTENSIONES_FOTO as $extension) {
        $ruta = __DIR__ . '/img/' . $referencia . '.' . $extension;
        if (is_file($ruta)) {
            unlink($ruta);
        }
    }
}

/**
 * Comprueba la foto subida. Devuelve la extensión ('jpg' o 'png'),
 * null si no se ha subido ninguna, o añade un error a $errores.
 */
function validar_foto_subida(array &$errores): ?string
{
    $foto = $_FILES['foto'] ?? null;
    if (!$foto || $foto['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($foto['error'] !== UPLOAD_ERR_OK) {
        $errores[] = 'No se ha podido subir la foto (código de error ' . (int) $foto['error'] . ').';
        return null;
    }
    if ($foto['size'] > TAMANO_MAXIMO_FOTO) {
        $errores[] = 'La foto no puede ocupar más de 2 MB.';
        return null;
    }
    // Miramos el contenido real del archivo, no el nombre (se puede falsear)
    $info = @getimagesize($foto['tmp_name']);
    $tipos = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png'];
    if (!$info || !isset($tipos[$info[2]])) {
        $errores[] = 'La foto tiene que ser una imagen JPG o PNG.';
        return null;
    }
    return $tipos[$info[2]];
}

/**
 * Adónde ir después de iniciar sesión o registrarse:
 * al carrito si se pulsó "Finalizar compra" sin sesión (el carrito se conserva),
 * al panel si es administrador y a la tienda si es cliente.
 */
function obtener_destino_tras_entrar(): string
{
    if (!empty($_SESSION['volver_al_carrito'])) {
        unset($_SESSION['volver_al_carrito']);
        return 'index.php?p=carrito';
    }
    return 'index.php' . (es_admin() ? '?p=admin' : '');
}

/* --------------------------------------------------------------------------
   Iniciar y cerrar sesión (index.php?p=login)
   Un solo login para todos: los administradores van al panel
   y los clientes a la tienda.
   -------------------------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion_sesion'] ?? '') === 'salir' && tiene_token_csrf_valido()) {
    unset($_SESSION['usuario'], $_SESSION['token_csrf']);
    session_regenerate_id(true);
    header('Location: index.php');
    exit;
}

if ($pagina === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion_sesion'] ?? '') === 'entrar') {
    $usuario = comprobar_login($conexion, trim($_POST['usuario'] ?? ''), (string) ($_POST['contrasena'] ?? ''));
    if ($usuario) {
        session_regenerate_id(true); // Nuevo id de sesión al entrar (evita robo de sesión)
        $_SESSION['usuario'] = $usuario;
        header('Location: ' . obtener_destino_tras_entrar());
        exit;
    }
    $error_login = 'Usuario o contraseña incorrectos.';
}

// Registro de clientes (index.php?p=registro): al crear la cuenta entra directamente
$errores_registro = [];
if ($pagina === 'registro' && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion_sesion'] ?? '') === 'registrar') {
    $usuario_nuevo = trim($_POST['usuario'] ?? '');
    $errores_registro = registrar_usuario($conexion, $usuario_nuevo, (string) ($_POST['contrasena'] ?? ''), (string) ($_POST['repetir_contrasena'] ?? ''));
    if (!$errores_registro) {
        session_regenerate_id(true);
        $_SESSION['usuario'] = ['id' => $conexion->insert_id, 'usuario' => $usuario_nuevo, 'rol' => 'cliente'];
        header('Location: ' . obtener_destino_tras_entrar());
        exit;
    }
}

/* --------------------------------------------------------------------------
   Mi cuenta (index.php?p=mi-cuenta): datos personales y contraseña
   -------------------------------------------------------------------------- */
$mensaje_cuenta = '';
if ($pagina === 'mi-cuenta') {
    if (!tiene_sesion_iniciada()) {
        header('Location: index.php?p=login');
        exit;
    }
    $id_usuario = $_SESSION['usuario']['id'];

    // Guardar mis datos
    if (($_POST['accion_cuenta'] ?? '') === 'datos') {
        $nombre = trim($_POST['nombre'] ?? '');
        $direccion = trim($_POST['direccion'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $stmt = $conexion->prepare("UPDATE usuarios SET nombre = ?, direccion = ?, telefono = ? WHERE id = ?");
        $stmt->bind_param('sssi', $nombre, $direccion, $telefono, $id_usuario);
        $stmt->execute();
        $mensaje_cuenta = 'Datos guardados.';
    }

    // Cambiar contraseña
    if (($_POST['accion_cuenta'] ?? '') === 'contrasena') {
        $stmt = $conexion->prepare("SELECT contrasena FROM usuarios WHERE id = ?");
        $stmt->bind_param('i', $id_usuario);
        $stmt->execute();
        $actual = $stmt->get_result()->fetch_assoc()['contrasena'];

        $nueva = $_POST['nueva'] ?? '';
        if (($_POST['actual'] ?? '') !== $actual) {
            $mensaje_cuenta = 'La contraseña actual no es correcta.';
        } elseif (strlen($nueva) < 6) {
            $mensaje_cuenta = 'La nueva contraseña debe tener al menos 6 caracteres.';
        } elseif ($nueva !== ($_POST['repetir'] ?? '')) {
            $mensaje_cuenta = 'Las contraseñas nuevas no coinciden.';
        } else {
            $stmt = $conexion->prepare("UPDATE usuarios SET contrasena = ? WHERE id = ?");
            $stmt->bind_param('si', $nueva, $id_usuario);
            $stmt->execute();
            $mensaje_cuenta = 'Contraseña cambiada.';
        }
    }
}

// Si ya ha iniciado sesión, no tiene sentido volver a ver el login ni el registro
if (in_array($pagina, ['login', 'registro'], true) && tiene_sesion_iniciada()) {
    header('Location: index.php' . (es_admin() ? '?p=admin' : ''));
    exit;
}

/* --------------------------------------------------------------------------
   Panel de administración (index.php?p=admin)
   -------------------------------------------------------------------------- */
if ($pagina === 'admin') {
    // Solo pueden entrar los administradores:
    // sin sesión -> al login; cliente con sesión -> a la tienda
    if (!es_admin()) {
        header('Location: index.php' . (tiene_sesion_iniciada() ? '' : '?p=login'));
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion_admin'])) {
        if (!tiene_token_csrf_valido()) {
            $errores_admin[] = 'La sesión ha caducado. Vuelve a enviar el formulario.';
        } elseif ($_POST['accion_admin'] === 'guardar') {
            /* ---- Crear o modificar un producto ---- */
            $id = (int) ($_POST['id'] ?? 0);
            $producto_actual = $id > 0 ? obtener_producto_por_id($conexion, $id) : null;
            if ($id > 0 && !$producto_actual) {
                $errores_admin[] = 'El producto que intentas modificar ya no existe.';
            }

            // Lo escrito en el formulario (se vuelve a mostrar si hay errores)
            $datos_producto = [
                'id'              => $producto_actual ? $id : 0,
                'referencia'      => $producto_actual ? $producto_actual['referencia'] : strtoupper(trim($_POST['referencia'] ?? '')),
                'nombre'          => trim($_POST['nombre'] ?? ''),
                'descripcion'     => trim($_POST['descripcion'] ?? ''),
                'categoria_id'    => (int) ($_POST['categoria_id'] ?? 0),
                'precio'          => str_replace(',', '.', trim($_POST['precio'] ?? '')),
                'precio_rebajado' => str_replace(',', '.', trim($_POST['precio_rebajado'] ?? '')),
                'existencias'     => trim($_POST['existencias'] ?? ''),
            ];
            $d = $datos_producto;

            // Validaciones
            if ($d['nombre'] === '' || mb_strlen($d['nombre']) > 150) {
                $errores_admin[] = 'El nombre es obligatorio (máximo 150 caracteres).';
            }
            if (!$producto_actual) {
                if (!preg_match('/^COD-\d{2,}$/', $d['referencia'])) {
                    $errores_admin[] = 'La referencia debe tener el formato COD-xx (por ejemplo COD-07).';
                } else {
                    $stmt = $conexion->prepare("SELECT id FROM productos WHERE referencia = ?");
                    $stmt->bind_param('s', $d['referencia']);
                    $stmt->execute();
                    if ($stmt->get_result()->fetch_assoc()) {
                        $errores_admin[] = 'Ya existe un producto con la referencia ' . $d['referencia'] . '.';
                    }
                    $stmt->close();
                }
            }
            if (!in_array($d['categoria_id'], array_map('intval', array_column(obtener_categorias($conexion), 'id')), true)) {
                $errores_admin[] = 'Elige una categoría.';
            }
            if (!is_numeric($d['precio']) || (float) $d['precio'] <= 0 || (float) $d['precio'] >= 100000000) {
                $errores_admin[] = 'El precio tiene que ser un número mayor que 0.';
            }
            $precio_rebajado = null;
            if ($d['precio_rebajado'] !== '') {
                if (!is_numeric($d['precio_rebajado']) || (float) $d['precio_rebajado'] <= 0) {
                    $errores_admin[] = 'El precio rebajado tiene que ser un número mayor que 0 (o déjalo vacío).';
                } elseif (is_numeric($d['precio']) && (float) $d['precio_rebajado'] >= (float) $d['precio']) {
                    $errores_admin[] = 'El precio rebajado tiene que ser menor que el precio normal.';
                } else {
                    $precio_rebajado = (float) $d['precio_rebajado'];
                }
            }
            $existencias = filter_var($d['existencias'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
            if ($existencias === false) {
                $errores_admin[] = 'Las existencias tienen que ser un número entero (0 o más).';
            }
            $extension_foto = validar_foto_subida($errores_admin);

            // Si todo está bien, guardamos en la base de datos
            if (!$errores_admin) {
                $precio = (float) $d['precio'];
                if ($producto_actual) {
                    $stmt = $conexion->prepare(
                        "UPDATE productos
                         SET nombre = ?, descripcion = ?, categoria_id = ?, precio = ?, precio_rebajado = ?, existencias = ?
                         WHERE id = ?"
                    );
                    $stmt->bind_param('ssiddii', $d['nombre'], $d['descripcion'], $d['categoria_id'], $precio, $precio_rebajado, $existencias, $id);
                } else {
                    // La valoración no se pide: empieza en 0 y saldrá de las opiniones de los clientes
                    $stmt = $conexion->prepare(
                        "INSERT INTO productos (referencia, nombre, descripcion, categoria_id, precio, precio_rebajado, existencias)
                         VALUES (?, ?, ?, ?, ?, ?, ?)"
                    );
                    $stmt->bind_param('sssiddi', $d['referencia'], $d['nombre'], $d['descripcion'], $d['categoria_id'], $precio, $precio_rebajado, $existencias);
                }

                if ($stmt && $stmt->execute()) {
                    $stmt->close();
                    // La foto se guarda como img/COD-xx.jpg (o .png), que es donde la busca la tienda
                    if ($extension_foto) {
                        borrar_fotos_producto($d['referencia']);
                        move_uploaded_file($_FILES['foto']['tmp_name'], __DIR__ . '/img/' . $d['referencia'] . '.' . $extension_foto);
                    }
                    $_SESSION['mensaje_admin'] = $producto_actual
                        ? 'Producto «' . $d['nombre'] . '» modificado.'
                        : 'Producto «' . $d['nombre'] . '» creado con la referencia ' . $d['referencia'] . '.';
                    header('Location: index.php?p=admin');
                    exit;
                }
                $errores_admin[] = $conexion->errno === 1062
                    ? 'Ya existe un producto con la referencia ' . $d['referencia'] . '.'
                    : 'No se ha podido guardar el producto: ' . $conexion->error;
            }
        } elseif ($_POST['accion_admin'] === 'crear_categoria' || $_POST['accion_admin'] === 'renombrar_categoria') {
            /* ---- Crear o renombrar una categoría ---- */
            $id = (int) ($_POST['id'] ?? 0);
            $nombre = trim($_POST['nombre'] ?? '');
            if ($nombre === '' || mb_strlen($nombre) > 100) {
                $_SESSION['mensaje_admin'] = 'El nombre de la categoría es obligatorio (máximo 100 caracteres).';
            } else {
                if ($_POST['accion_admin'] === 'crear_categoria') {
                    $stmt = $conexion->prepare("INSERT INTO categorias (nombre) VALUES (?)");
                    $stmt->bind_param('s', $nombre);
                } else {
                    $stmt = $conexion->prepare("UPDATE categorias SET nombre = ? WHERE id = ?");
                    $stmt->bind_param('si', $nombre, $id);
                }
                $_SESSION['mensaje_admin'] = @$stmt->execute()
                    ? "Categoría «{$nombre}» guardada."
                    : "Ya existe una categoría llamada «{$nombre}».";
            }
            header('Location: index.php?p=admin&seccion=categorias');
            exit;
        } elseif ($_POST['accion_admin'] === 'borrar_categoria') {
            /* ---- Borrar una categoría (sus productos se quedan sin categoría) ---- */
            $id = (int) ($_POST['id'] ?? 0);
            $stmt = $conexion->prepare("DELETE FROM categorias WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $_SESSION['mensaje_admin'] = 'Categoría borrada. Sus productos se han quedado sin categoría.';
            header('Location: index.php?p=admin&seccion=categorias');
            exit;
        } elseif ($_POST['accion_admin'] === 'crear_cupon') {
            /* ---- Crear un cupón ---- */
            $codigo = strtoupper(trim($_POST['codigo'] ?? ''));
            $porcentaje = (int) ($_POST['porcentaje'] ?? 0);
            if (!preg_match('/^[A-Z0-9]{3,30}$/', $codigo) || $porcentaje < 1 || $porcentaje > 100) {
                $_SESSION['mensaje_admin'] = 'Cupón no creado: el código lleva de 3 a 30 letras o números y el descuento va de 1 a 100 %.';
            } else {
                $stmt = $conexion->prepare("INSERT INTO cupones (codigo, porcentaje) VALUES (?, ?)");
                $stmt->bind_param('si', $codigo, $porcentaje);
                $_SESSION['mensaje_admin'] = @$stmt->execute()
                    ? "Cupón $codigo creado."
                    : "Cupón no creado: ya existe el código $codigo.";
            }
            header('Location: index.php?p=admin&seccion=cupones');
            exit;
        } elseif ($_POST['accion_admin'] === 'cambiar_cupon') {
            /* ---- Activar / desactivar un cupón ---- */
            $stmt = $conexion->prepare("UPDATE cupones SET activo = NOT activo WHERE codigo = ?");
            $stmt->bind_param('s', $_POST['codigo']);
            $stmt->execute();
            header('Location: index.php?p=admin&seccion=cupones');
            exit;
        } elseif ($_POST['accion_admin'] === 'borrar') {
            /* ---- Borrar un producto ---- */
            $producto_borrar = obtener_producto_por_id($conexion, (int) ($_POST['id'] ?? 0));
            if ($producto_borrar) {
                $stmt = $conexion->prepare("DELETE FROM productos WHERE id = ?");
                $stmt->bind_param('i', $producto_borrar['id']);
                if ($stmt->execute()) {
                    borrar_fotos_producto($producto_borrar['referencia']);
                    $_SESSION['mensaje_admin'] = 'Producto «' . $producto_borrar['nombre'] . '» borrado.';
                } else {
                    $_SESSION['mensaje_admin'] = 'No se ha podido borrar el producto: ' . $conexion->error;
                }
                $stmt->close();
            }
            header('Location: index.php?p=admin');
            exit;
        }
    }
}


/* ==========================================================================
   2. VISTAS DEL PANEL (index.php?p=admin)
   ========================================================================== */

/** Pinta la pestaña del panel que toca según ?seccion= (productos si no hay ninguna). */
function mostrar_panel_admin(): void
{
    $seccion = $_GET['seccion'] ?? '';
    if ($seccion === 'cupones') {
        mostrar_admin_cupones();
    } elseif ($seccion === 'categorias') {
        mostrar_admin_categorias();
    } else {
        mostrar_admin_productos();
    }
}

/** Botones de las pestañas del panel. $activa = 'productos', 'cupones' o 'categorias'. */
function mostrar_pestanas_admin(string $activa): void
{
    $pestanas = [
        'productos'  => ['Productos', 'index.php?p=admin'],
        'cupones'    => ['Cupones', 'index.php?p=admin&amp;seccion=cupones'],
        'categorias' => ['Categorías', 'index.php?p=admin&amp;seccion=categorias'],
    ];
    ?>
            <!-- Pestañas del panel -->
            <div class="mb-4">
                <?php foreach ($pestanas as $clave => [$nombre, $url]): ?>
                    <a class="btn <?= $clave === $activa ? 'btn-dark active' : 'btn-outline-dark' ?> py-2 px-3 mr-1 mb-2" href="<?= $url ?>"><?= $nombre ?></a>
                <?php endforeach; ?>
                <span class="btn btn-outline-dark py-2 px-3 mb-2 disabled" title="Próximamente">Pedidos</span>
            </div>
    <?php
}

/** Pestaña Productos: formulario para crear/editar y lista de productos. */
function mostrar_admin_productos(): void
{
    global $conexion, $errores_admin, $datos_producto;

// Mensaje de "Producto creado / modificado / borrado" (se muestra una sola vez)
$mensaje_admin = $_SESSION['mensaje_admin'] ?? '';
unset($_SESSION['mensaje_admin']);

$categorias = obtener_categorias($conexion);
$productos_admin = obtener_productos_admin($conexion);

// ¿Qué hay en el formulario?
//  1) Lo que se acaba de enviar con errores, 2) el producto a editar, 3) uno nuevo vacío
$editar_id = filter_input(INPUT_GET, 'editar', FILTER_VALIDATE_INT);
if ($datos_producto === null && $editar_id) {
    foreach ($productos_admin as $p) {
        if ((int) $p['id'] === $editar_id) {
            $datos_producto = $p;
        }
    }
}
if ($datos_producto === null) {
    $datos_producto = [
        'id' => 0, 'referencia' => generar_siguiente_referencia($conexion), 'nombre' => '', 'descripcion' => '',
        'categoria_id' => 0, 'precio' => '', 'precio_rebajado' => '', 'existencias' => '0',
    ];
}
$editando = (int) $datos_producto['id'] > 0;

$token = obtener_token_csrf();

mostrar_migas_de_pan([
    'Inicio' => 'index.php',
    'Administración' => null,
]);
?>
    <!-- Administración Start -->
    <div class="container-fluid">
        <div class="px-xl-5">
            <h2 class="mb-3">Administración</h2>

            <?php mostrar_pestanas_admin('productos'); ?>

            <?php if ($mensaje_admin): ?>
                <div class="alert alert-success"><?= escapar_html($mensaje_admin) ?></div>
            <?php endif; ?>
            <?php if ($errores_admin): ?>
                <div class="alert alert-danger">
                    <strong>Revisa el formulario:</strong>
                    <ul class="mb-0">
                        <?php foreach ($errores_admin as $error): ?>
                            <li><?= escapar_html($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Formulario: nuevo producto / editar producto -->
            <h4 class="mb-3"><?= $editando ? 'Editar producto ' . escapar_html($datos_producto['referencia']) : 'Nuevo producto' ?></h4>
            <div class="bg-light p-30 mb-5">
                <!-- enctype multipart/form-data: obligatorio para poder subir la foto -->
                <form action="index.php?p=admin<?= $editando ? '&amp;editar=' . (int) $datos_producto['id'] : '' ?>" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="accion_admin" value="guardar">
                    <input type="hidden" name="token_csrf" value="<?= escapar_html($token) ?>">
                    <input type="hidden" name="id" value="<?= (int) $datos_producto['id'] ?>">

                    <div class="form-row">
                        <div class="form-group col-md-8">
                            <label for="nombre">Nombre</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" maxlength="150" required
                                   value="<?= escapar_html($datos_producto['nombre']) ?>">
                        </div>
                        <div class="form-group col-md-4">
                            <label for="referencia">Referencia</label>
                            <?php if ($editando): ?>
                                <!-- La referencia no se cambia: de ella depende el nombre de la foto -->
                                <input type="text" class="form-control" id="referencia" value="<?= escapar_html($datos_producto['referencia']) ?>" readonly>
                            <?php else: ?>
                                <input type="text" class="form-control" id="referencia" name="referencia" required
                                       pattern="COD-[0-9]{2,}" title="Formato COD-xx, por ejemplo COD-07"
                                       value="<?= escapar_html($datos_producto['referencia']) ?>">
                                <small class="form-text text-muted">Formato COD-xx. Se propone la siguiente libre.</small>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="descripcion">Descripción</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="5"><?= escapar_html($datos_producto['descripcion']) ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="categoria_id">Categoría</label>
                        <select class="custom-select" id="categoria_id" name="categoria_id" required>
                            <?php foreach ($categorias as $categoria): ?>
                                <option value="<?= (int) $categoria['id'] ?>"<?= (int) $categoria['id'] === (int) $datos_producto['categoria_id'] ? ' selected' : '' ?>>
                                    <?= escapar_html($categoria['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="precio">Precio en euros, IVA incluido</label>
                            <input type="number" class="form-control" id="precio" name="precio" min="0.01" step="0.01" required
                                   value="<?= escapar_html((string) $datos_producto['precio']) ?>">
                        </div>
                        <div class="form-group col-md-4">
                            <label for="precio_rebajado">Precio rebajado <small class="text-muted">(opcional)</small></label>
                            <input type="number" class="form-control" id="precio_rebajado" name="precio_rebajado" min="0.01" step="0.01"
                                   value="<?= escapar_html((string) $datos_producto['precio_rebajado']) ?>">
                            <small class="form-text text-muted">Déjalo vacío si no está rebajado.</small>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="existencias">Existencias</label>
                            <input type="number" class="form-control" id="existencias" name="existencias" min="0" step="1" required
                                   value="<?= escapar_html((string) $datos_producto['existencias']) ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="foto">Foto <small class="text-muted">(JPG o PNG, máximo 2 MB<?= $editando ? '; si no eliges ninguna se mantiene la actual' : '' ?>)</small></label>
                        <input type="file" class="form-control-file" id="foto" name="foto" accept="image/jpeg,image/png">
                    </div>

                    <button class="btn btn-primary py-2 px-4" type="submit">
                        <i class="fa fa-save mr-1"></i> <?= $editando ? 'Guardar cambios' : 'Crear producto' ?>
                    </button>
                    <?php if ($editando): ?>
                        <a class="btn btn-outline-dark py-2 px-4 ml-2" href="index.php?p=admin">Cancelar</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Lista de productos -->
            <h4 class="mb-3">Productos (<?= count($productos_admin) ?>)</h4>
            <div class="table-responsive mb-5">
                <table class="table table-light table-borderless table-hover mb-0">
                    <thead class="thead-dark">
                        <tr>
                            <th>Foto</th>
                            <th>Referencia</th>
                            <th>Nombre</th>
                            <th>Categoría</th>
                            <th class="text-right">Precio</th>
                            <th class="text-right">Rebajado</th>
                            <th class="text-right">Existencias</th>
                            <th class="text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($productos_admin as $p): ?>
                            <tr>
                                <td class="align-middle"><img src="<?= escapar_html(obtener_imagen_producto($p, '50x50')) ?>" alt="" style="width: 50px;"></td>
                                <td class="align-middle"><?= escapar_html($p['referencia']) ?></td>
                                <td class="align-middle"><a class="text-dark" href="<?= escapar_html(obtener_url_producto($p)) ?>"><?= escapar_html($p['nombre']) ?></a></td>
                                <td class="align-middle"><?= escapar_html($p['categoria'] ?? '—') ?></td>
                                <td class="align-middle text-right text-nowrap"><?= formatear_precio((float) $p['precio']) ?></td>
                                <td class="align-middle text-right text-nowrap"><?= tiene_precio_rebajado($p) ? formatear_precio((float) $p['precio_rebajado']) : '—' ?></td>
                                <td class="align-middle text-right<?= (int) $p['existencias'] === 0 ? ' text-danger font-weight-bold' : '' ?>"><?= (int) $p['existencias'] ?></td>
                                <td class="align-middle text-right text-nowrap">
                                    <a class="btn btn-sm btn-outline-dark" href="index.php?p=admin&amp;editar=<?= (int) $p['id'] ?>" title="Editar"><i class="fa fa-pen"></i></a>
                                    <form class="d-inline" action="index.php?p=admin" method="post"
                                          onsubmit="return confirm('¿Borrar el producto «<?= escapar_html(addslashes($p['nombre'])) ?>»? No se puede deshacer.');">
                                        <input type="hidden" name="accion_admin" value="borrar">
                                        <input type="hidden" name="token_csrf" value="<?= escapar_html($token) ?>">
                                        <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" title="Borrar"><i class="fa fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- Administración End -->
<?php
}

/** Pestaña Cupones: crear cupones y activarlos o desactivarlos. */
function mostrar_admin_cupones(): void
{
    global $conexion;

$mensaje_admin = $_SESSION['mensaje_admin'] ?? '';
unset($_SESSION['mensaje_admin']);

$cupones = $conexion->query("SELECT * FROM cupones ORDER BY codigo")->fetch_all(MYSQLI_ASSOC);
$token = obtener_token_csrf();

mostrar_migas_de_pan([
    'Inicio' => 'index.php',
    'Administración' => 'index.php?p=admin',
    'Cupones' => null,
]);
?>
    <!-- Cupones Start -->
    <div class="container-fluid">
        <div class="px-xl-5">
            <h2 class="mb-3">Administración</h2>

            <?php mostrar_pestanas_admin('cupones'); ?>

            <?php if ($mensaje_admin): ?>
                <div class="alert alert-info"><?= escapar_html($mensaje_admin) ?></div>
            <?php endif; ?>

            <!-- Nuevo cupón -->
            <h4 class="mb-3">Nuevo cupón</h4>
            <form action="index.php?p=admin&amp;seccion=cupones" method="post" class="bg-light p-30 mb-5 form-inline">
                <input type="hidden" name="accion_admin" value="crear_cupon">
                <input type="hidden" name="token_csrf" value="<?= escapar_html($token) ?>">
                <input type="text" name="codigo" class="form-control mr-2 mb-2" placeholder="Código (p. ej. NAVIDAD15)" maxlength="30" required>
                <input type="number" name="porcentaje" class="form-control mr-2 mb-2" placeholder="% descuento" min="1" max="100" required>
                <button type="submit" class="btn btn-primary mb-2"><i class="fa fa-plus mr-1"></i> Crear</button>
            </form>

            <!-- Lista de cupones -->
            <h4 class="mb-3">Cupones</h4>
            <div class="table-responsive mb-5">
                <table class="table table-light table-borderless table-hover mb-0">
                    <thead class="thead-dark">
                        <tr><th>Código</th><th>Descuento</th><th>Estado</th><th></th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cupones as $cupon): ?>
                        <tr>
                            <td class="align-middle"><b><?= escapar_html($cupon['codigo']) ?></b></td>
                            <td class="align-middle"><?= (int) $cupon['porcentaje'] ?> %</td>
                            <td class="align-middle"><?= $cupon['activo'] ? 'Activo' : 'Desactivado' ?></td>
                            <td class="align-middle text-right">
                                <form action="index.php?p=admin&amp;seccion=cupones" method="post">
                                    <input type="hidden" name="accion_admin" value="cambiar_cupon">
                                    <input type="hidden" name="token_csrf" value="<?= escapar_html($token) ?>">
                                    <input type="hidden" name="codigo" value="<?= escapar_html($cupon['codigo']) ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-dark"><?= $cupon['activo'] ? 'Desactivar' : 'Activar' ?></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- Cupones End -->
<?php
}

/** Pestaña Categorías: crear, renombrar y borrar categorías. */
function mostrar_admin_categorias(): void
{
    global $conexion;

$mensaje_admin = $_SESSION['mensaje_admin'] ?? '';
unset($_SESSION['mensaje_admin']);

$categorias = obtener_categorias($conexion); // id, nombre y total de productos
$token = obtener_token_csrf();

mostrar_migas_de_pan([
    'Inicio' => 'index.php',
    'Administración' => 'index.php?p=admin',
    'Categorías' => null,
]);
?>
    <!-- Categorías Start -->
    <div class="container-fluid">
        <div class="px-xl-5">
            <h2 class="mb-3">Administración</h2>

            <?php mostrar_pestanas_admin('categorias'); ?>

            <?php if ($mensaje_admin): ?>
                <div class="alert alert-info"><?= escapar_html($mensaje_admin) ?></div>
            <?php endif; ?>

            <!-- Nueva categoría -->
            <h4 class="mb-3">Nueva categoría</h4>
            <form action="index.php?p=admin&amp;seccion=categorias" method="post" class="bg-light p-30 mb-5 form-inline">
                <input type="hidden" name="accion_admin" value="crear_categoria">
                <input type="hidden" name="token_csrf" value="<?= escapar_html($token) ?>">
                <input type="text" name="nombre" class="form-control mr-2 mb-2" placeholder="Nombre (p. ej. Accesorios)" maxlength="100" required>
                <button type="submit" class="btn btn-primary mb-2"><i class="fa fa-plus mr-1"></i> Crear</button>
            </form>

            <!-- Lista de categorías -->
            <h4 class="mb-3">Categorías</h4>
            <div class="table-responsive mb-5">
                <table class="table table-light table-borderless table-hover mb-0">
                    <thead class="thead-dark">
                        <tr><th>Nombre</th><th>Productos</th><th></th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categorias as $categoria): ?>
                        <tr>
                            <!-- Renombrar: se cambia el nombre en la propia tabla y se pulsa Guardar -->
                            <td class="align-middle">
                                <form action="index.php?p=admin&amp;seccion=categorias" method="post" class="form-inline">
                                    <input type="hidden" name="accion_admin" value="renombrar_categoria">
                                    <input type="hidden" name="token_csrf" value="<?= escapar_html($token) ?>">
                                    <input type="hidden" name="id" value="<?= (int) $categoria['id'] ?>">
                                    <input type="text" name="nombre" class="form-control form-control-sm mr-2" maxlength="100" required value="<?= escapar_html($categoria['nombre']) ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-dark">Guardar</button>
                                </form>
                            </td>
                            <td class="align-middle">
                                <a href="index.php?p=tienda&amp;categoria=<?= (int) $categoria['id'] ?>" class="text-primary" title="Ver en la tienda"><?= (int) $categoria['total'] ?></a>
                            </td>
                            <td class="align-middle text-right">
                                <form action="index.php?p=admin&amp;seccion=categorias" method="post"
                                      onsubmit="return confirm('¿Borrar la categoría «<?= escapar_html(addslashes($categoria['nombre'])) ?>»? Sus <?= (int) $categoria['total'] ?> productos se quedarán sin categoría.');">
                                    <input type="hidden" name="accion_admin" value="borrar_categoria">
                                    <input type="hidden" name="token_csrf" value="<?= escapar_html($token) ?>">
                                    <input type="hidden" name="id" value="<?= (int) $categoria['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger" title="Borrar categoría"><i class="fa fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- Categorías End -->
<?php
}
