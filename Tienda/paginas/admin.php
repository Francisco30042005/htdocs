<?php
/*
  PANEL DE ADMINISTRACIÓN (index.php?p=admin). Solo para administradores.
  Cuatro pestañas, según &seccion= en la URL:
    productos   (la de por defecto) crear, editar y borrar productos, con su foto
    cupones     crear cupones de descuento y activarlos o desactivarlos
    categorias  crear, renombrar y borrar categorías
    pedidos     ver los pedidos de los clientes y cambiar su estado
    publicidad  aprobar o rechazar los anuncios de los clientes y crear anuncios propios

  Primero se procesan los formularios (todos llevan el token de seguridad y, al
  terminar, recargan la página) y después se pinta la pestaña elegida.
*/
if (!es_admin()) {
    header('Location: ' . (tiene_sesion_iniciada() ? 'index.php' : 'index.php?p=login'));
    exit;
}

$seccion = $_GET['seccion'] ?? 'productos';
if (!in_array($seccion, ['productos', 'cupones', 'categorias', 'pedidos', 'publicidad'])) {
    $seccion = 'productos';
}
$accion = $_POST['accion_admin'] ?? '';
$errores_admin = [];   // errores del formulario de producto
$datos_producto = null; // lo escrito en ese formulario, para no perderlo si hay errores

if ($accion !== '' && !tiene_token_csrf_valido()) {
    $errores_admin[] = 'La sesión ha caducado. Vuelve a enviar el formulario.';
    $accion = '';
}

/** Borra la foto de un producto (img/COD-xx.jpg o .png). */
function borrar_fotos_producto(string $referencia): void
{
    foreach (['jpg', 'png'] as $extension) {
        $ruta = 'img/' . $referencia . '.' . $extension;
        if (is_file($ruta)) {
            unlink($ruta);
        }
    }
}


/* ==========================================================================
   FORMULARIOS: PRODUCTOS
   ========================================================================== */

// Crear o modificar un producto
if ($accion === 'guardar') {
    $id = (int) ($_POST['id'] ?? 0);
    $producto_actual = $id > 0 ? obtener_producto($id) : null; // null = producto nuevo

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

    // Comprobaciones
    if ($d['nombre'] === '' || mb_strlen($d['nombre']) > 150) {
        $errores_admin[] = 'El nombre es obligatorio (máximo 150 caracteres).';
    }
    if (!$producto_actual && !preg_match('/^COD-\d{2,}$/', $d['referencia'])) {
        $errores_admin[] = 'La referencia debe tener el formato COD-xx (por ejemplo COD-07).';
    } elseif (!$producto_actual && consultar("SELECT id FROM productos WHERE referencia = ?", [$d['referencia']])) {
        $errores_admin[] = 'Ya existe un producto con la referencia ' . $d['referencia'] . '.';
    }
    if (!consultar("SELECT id FROM categorias WHERE id = ?", [$d['categoria_id']])) {
        $errores_admin[] = 'Elige una categoría.';
    }
    if (!is_numeric($d['precio']) || $d['precio'] <= 0 || $d['precio'] >= 100000000) {
        $errores_admin[] = 'El precio tiene que ser un número mayor que 0.';
    }
    if ($d['precio_rebajado'] !== '' && (!is_numeric($d['precio_rebajado']) || $d['precio_rebajado'] <= 0)) {
        $errores_admin[] = 'El precio rebajado tiene que ser un número mayor que 0 (o déjalo vacío).';
    } elseif ($d['precio_rebajado'] !== '' && is_numeric($d['precio']) && $d['precio_rebajado'] >= $d['precio']) {
        $errores_admin[] = 'El precio rebajado tiene que ser menor que el precio normal.';
    }
    if (!ctype_digit($d['existencias'])) {
        $errores_admin[] = 'Las existencias tienen que ser un número entero (0 o más).';
    }

    // Foto (opcional): solo JPG o PNG de hasta 2 MB. Se mira el contenido real del archivo, no su nombre.
    $foto = $_FILES['foto'] ?? null;
    $extension_foto = null;
    if ($foto && $foto['error'] !== UPLOAD_ERR_NO_FILE) {
        $tipos = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png'];
        $info = $foto['error'] === UPLOAD_ERR_OK ? @getimagesize($foto['tmp_name']) : false;
        if ($foto['error'] !== UPLOAD_ERR_OK || $foto['size'] > 2 * 1024 * 1024) {
            $errores_admin[] = 'No se ha podido subir la foto: tiene que ocupar 2 MB como mucho.';
        } elseif (!$info || !isset($tipos[$info[2]])) {
            $errores_admin[] = 'La foto tiene que ser una imagen JPG o PNG.';
        } else {
            $extension_foto = $tipos[$info[2]];
        }
    }

    // Si todo está bien, se guarda
    if (!$errores_admin) {
        $precio_rebajado = $d['precio_rebajado'] === '' ? null : $d['precio_rebajado'];
        if ($producto_actual) {
            ejecutar("UPDATE productos SET nombre = ?, descripcion = ?, categoria_id = ?, precio = ?, precio_rebajado = ?, existencias = ? WHERE id = ?",
                     [$d['nombre'], $d['descripcion'], $d['categoria_id'], $d['precio'], $precio_rebajado, $d['existencias'], $id]);
            $_SESSION['mensaje_admin'] = 'Producto «' . $d['nombre'] . '» modificado.';
        } else {
            // La valoración no se guarda: es la media de las reseñas (ver SQL_PRODUCTOS en funciones.php)
            ejecutar("INSERT INTO productos (referencia, nombre, descripcion, categoria_id, precio, precio_rebajado, existencias) VALUES (?, ?, ?, ?, ?, ?, ?)",
                     [$d['referencia'], $d['nombre'], $d['descripcion'], $d['categoria_id'], $d['precio'], $precio_rebajado, $d['existencias']]);
            $_SESSION['mensaje_admin'] = 'Producto «' . $d['nombre'] . '» creado con la referencia ' . $d['referencia'] . '.';
        }
        // La foto se guarda como img/COD-xx.jpg (o .png), que es donde la busca la tienda
        if ($extension_foto) {
            borrar_fotos_producto($d['referencia']);
            move_uploaded_file($foto['tmp_name'], 'img/' . $d['referencia'] . '.' . $extension_foto);
        }
        header('Location: index.php?p=admin');
        exit;
    }
}

// Borrar un producto (y su foto)
if ($accion === 'borrar') {
    $producto = obtener_producto((int) ($_POST['id'] ?? 0));
    if ($producto) {
        ejecutar("DELETE FROM productos WHERE id = ?", [$producto['id']]);
        borrar_fotos_producto($producto['referencia']);
        $_SESSION['mensaje_admin'] = 'Producto «' . $producto['nombre'] . '» borrado.';
    }
    header('Location: index.php?p=admin');
    exit;
}


/* ==========================================================================
   FORMULARIOS: CUPONES
   ========================================================================== */

// Crear un cupón
if ($accion === 'crear_cupon') {
    $codigo = strtoupper(trim($_POST['codigo'] ?? ''));
    $porcentaje = (int) ($_POST['porcentaje'] ?? 0);
    if (!preg_match('/^[A-Z0-9]{3,30}$/', $codigo) || $porcentaje < 1 || $porcentaje > 100) {
        $_SESSION['mensaje_admin'] = 'Cupón no creado: el código lleva de 3 a 30 letras o números y el descuento va de 1 a 100 %.';
    } elseif (ejecutar("INSERT INTO cupones (codigo, porcentaje) VALUES (?, ?)", [$codigo, $porcentaje])) {
        $_SESSION['mensaje_admin'] = "Cupón $codigo creado.";
    } else {
        $_SESSION['mensaje_admin'] = "Cupón no creado: ya existe el código $codigo.";
    }
    header('Location: index.php?p=admin&seccion=cupones');
    exit;
}

// Activar / desactivar un cupón
if ($accion === 'cambiar_cupon') {
    ejecutar("UPDATE cupones SET activo = NOT activo WHERE codigo = ?", [$_POST['codigo'] ?? '']);
    header('Location: index.php?p=admin&seccion=cupones');
    exit;
}


/* ==========================================================================
   FORMULARIOS: CATEGORÍAS
   ========================================================================== */

// Crear o renombrar una categoría
if ($accion === 'crear_categoria' || $accion === 'renombrar_categoria') {
    $nombre = trim($_POST['nombre'] ?? '');
    if ($nombre === '' || mb_strlen($nombre) > 100) {
        $_SESSION['mensaje_admin'] = 'El nombre de la categoría es obligatorio (máximo 100 caracteres).';
    } else {
        $guardada = $accion === 'crear_categoria'
            ? ejecutar("INSERT INTO categorias (nombre) VALUES (?)", [$nombre])
            : ejecutar("UPDATE categorias SET nombre = ? WHERE id = ?", [$nombre, (int) ($_POST['id'] ?? 0)]);
        // Falla si ya hay otra con ese nombre (la columna "nombre" es UNIQUE)
        $_SESSION['mensaje_admin'] = $guardada ? "Categoría «{$nombre}» guardada." : "Ya existe una categoría llamada «{$nombre}».";
    }
    header('Location: index.php?p=admin&seccion=categorias');
    exit;
}

// Borrar una categoría (sus productos se quedan sin categoría)
if ($accion === 'borrar_categoria') {
    ejecutar("DELETE FROM categorias WHERE id = ?", [(int) ($_POST['id'] ?? 0)]);
    $_SESSION['mensaje_admin'] = 'Categoría borrada. Sus productos se han quedado sin categoría.';
    header('Location: index.php?p=admin&seccion=categorias');
    exit;
}


/* ==========================================================================
   FORMULARIOS: PEDIDOS
   ========================================================================== */

// Cambiar el estado de un pedido (pendiente, enviado, entregado o cancelado)
if ($accion === 'cambiar_estado') {
    $estado = $_POST['estado'] ?? '';
    if (isset(ESTADOS_PEDIDO[$estado])) {
        ejecutar("UPDATE pedidos SET estado = ? WHERE id = ?", [$estado, (int) ($_POST['id'] ?? 0)]);
        $_SESSION['mensaje_admin'] = 'Pedido #' . (int) $_POST['id'] . ' marcado como «' . $estado . '».';
    }
    header('Location: index.php?p=admin&seccion=pedidos');
    exit;
}


/* ==========================================================================
   FORMULARIOS: PUBLICIDAD
   ========================================================================== */

// Crear un anuncio de la tienda: se publica directamente (estado "aprobado")
$error_anuncio = '';
if ($accion === 'crear_anuncio') {
    $error_anuncio = guardar_anuncio($_SESSION['usuario']['id'], 'aprobado');
    if ($error_anuncio === '') {
        $_SESSION['mensaje_admin'] = 'Anuncio creado y publicado.';
        header('Location: index.php?p=admin&seccion=publicidad');
        exit;
    }
}

// Aprobar o rechazar un anuncio
if ($accion === 'estado_anuncio') {
    $estado = $_POST['estado'] ?? '';
    if (isset(ESTADOS_ANUNCIO[$estado])) {
        ejecutar("UPDATE anuncios SET estado = ? WHERE id = ?", [$estado, (int) ($_POST['id'] ?? 0)]);
        $_SESSION['mensaje_admin'] = 'Anuncio marcado como «' . $estado . '».';
    }
    header('Location: index.php?p=admin&seccion=publicidad');
    exit;
}

// Borrar un anuncio (y su imagen)
if ($accion === 'borrar_anuncio') {
    $anuncio = consultar("SELECT * FROM anuncios WHERE id = ?", [(int) ($_POST['id'] ?? 0)])[0] ?? null;
    if ($anuncio) {
        borrar_anuncio($anuncio);
        $_SESSION['mensaje_admin'] = 'Anuncio «' . $anuncio['titulo'] . '» borrado.';
    }
    header('Location: index.php?p=admin&seccion=publicidad');
    exit;
}


/* ==========================================================================
   DATOS PARA PINTAR LA PESTAÑA ELEGIDA
   ========================================================================== */

// Aviso de la última acción ("Producto creado", "Cupón activado"...). Se muestra una sola vez.
$mensaje_admin = $_SESSION['mensaje_admin'] ?? '';
unset($_SESSION['mensaje_admin']);
$token = obtener_token_csrf();

if ($seccion === 'productos') {
    $categorias = obtener_categorias();
    $productos_admin = consultar("SELECT p.*, c.nombre AS categoria
                                  FROM productos p LEFT JOIN categorias c ON c.id = p.categoria_id
                                  ORDER BY p.referencia");

    // ¿Qué va en el formulario? 1) lo enviado con errores, 2) el producto a editar o 3) uno nuevo vacío
    if ($datos_producto === null && isset($_GET['editar'])) {
        $datos_producto = obtener_producto((int) $_GET['editar']);
    }
    if ($datos_producto === null) {
        // Referencia propuesta: la siguiente libre (si la mayor es COD-20, COD-21)
        $mayor = consultar("SELECT MAX(CAST(SUBSTRING(referencia, 5) AS UNSIGNED)) AS mayor FROM productos WHERE referencia LIKE 'COD-%'")[0]['mayor'];
        $datos_producto = [
            'id' => 0, 'referencia' => 'COD-' . str_pad($mayor + 1, 2, '0', STR_PAD_LEFT), 'nombre' => '', 'descripcion' => '',
            'categoria_id' => 0, 'precio' => '', 'precio_rebajado' => '', 'existencias' => '0',
        ];
    }
    $editando = $datos_producto['id'] > 0;
}
if ($seccion === 'cupones') {
    $cupones = consultar("SELECT * FROM cupones ORDER BY codigo");
}
if ($seccion === 'categorias') {
    $categorias = obtener_categorias(); // con el número de productos de cada una
}
if ($seccion === 'pedidos') {
    // Del más nuevo al más antiguo, con el nombre de usuario del cliente
    $pedidos = consultar("SELECT pe.*, u.usuario FROM pedidos pe JOIN usuarios u ON u.id = pe.usuario_id ORDER BY pe.fecha DESC");
}

if ($seccion === 'publicidad') {
    // Primero los pendientes de revisar, después del más nuevo al más antiguo
    $anuncios = consultar("SELECT a.*, u.usuario, u.rol FROM anuncios a JOIN usuarios u ON u.id = a.usuario_id
                           ORDER BY a.estado = 'pendiente' DESC, a.fecha DESC");
}

// En la pestaña Publicidad se ve cuántos anuncios faltan por revisar
$pendientes = consultar("SELECT COUNT(*) AS total FROM anuncios WHERE estado = 'pendiente'")[0]['total'];
$pestanas = ['productos' => 'Productos', 'cupones' => 'Cupones', 'categorias' => 'Categorías', 'pedidos' => 'Pedidos',
             'publicidad' => 'Publicidad' . ($pendientes ? " ($pendientes)" : '')];

mostrar_migas_de_pan([
    'Inicio' => 'index.php',
    'Administración' => null,
]);
?>
    <!-- Administración Start -->
    <div class="container-fluid">
        <div class="px-xl-5">
            <h2 class="mb-3">Administración</h2>

            <!-- Pestañas del panel -->
            <div class="mb-4">
                <?php foreach ($pestanas as $clave => $nombre): ?>
                    <a class="btn <?= $clave === $seccion ? 'btn-dark active' : 'btn-outline-dark' ?> py-2 px-3 mr-1 mb-2" href="index.php?p=admin&amp;seccion=<?= $clave ?>"><?= $nombre ?></a>
                <?php endforeach; ?>
            </div>

            <?php if ($mensaje_admin): ?>
                <div class="alert alert-info"><?= escapar_html($mensaje_admin) ?></div>
            <?php endif; ?>

            <?php if ($seccion === 'productos'): ?>
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
                                    <td class="align-middle"><img src="<?= escapar_html(obtener_imagen_producto($p)) ?>" alt="" style="width: 50px;"></td>
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

            <?php elseif ($seccion === 'cupones'): ?>
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

            <?php elseif ($seccion === 'categorias'): ?>
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

            <?php elseif ($seccion === 'pedidos'): ?>
                <!-- Lista de pedidos -->
                <h4 class="mb-3">Pedidos (<?= count($pedidos) ?>)</h4>
                <?php if (!$pedidos): ?>
                    <p>Todavía no hay pedidos.</p>
                <?php else: ?>
                <div class="table-responsive mb-5">
                    <table class="table table-light table-borderless table-hover mb-0">
                        <thead class="thead-dark">
                            <tr><th>Pedido</th><th>Fecha</th><th>Cliente</th><th>Productos</th><th class="text-right">Total</th><th>Estado</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pedidos as $pedido): ?>
                            <tr>
                                <td class="align-middle"><b>#<?= $pedido['id'] ?></b></td>
                                <td class="align-middle text-nowrap"><?= date('d/m/Y H:i', strtotime($pedido['fecha'])) ?></td>
                                <td class="align-middle">
                                    <?= escapar_html($pedido['usuario']) ?><br>
                                    <small class="text-muted"><?= escapar_html($pedido['direccion'] ?: 'Sin dirección de envío') ?></small>
                                </td>
                                <td class="align-middle small">
                                    <?php foreach (obtener_lineas_pedido($pedido['id']) as $linea): ?>
                                        <?= $linea['cantidad'] ?> × <?= escapar_html($linea['nombre']) ?> (<?= formatear_precio($linea['precio']) ?>)<br>
                                    <?php endforeach; ?>
                                    <?php if ($pedido['descuento']): ?>
                                        <span class="text-muted">Cupones: −<?= $pedido['descuento'] ?> %</span>
                                    <?php endif; ?>
                                </td>
                                <td class="align-middle text-right text-nowrap"><?= formatear_precio($pedido['total']) ?></td>
                                <td class="align-middle">
                                    <!-- Cambiar el estado: se elige en la lista y se pulsa Guardar -->
                                    <form action="index.php?p=admin&amp;seccion=pedidos" method="post" class="form-inline flex-nowrap">
                                        <input type="hidden" name="accion_admin" value="cambiar_estado">
                                        <input type="hidden" name="token_csrf" value="<?= escapar_html($token) ?>">
                                        <input type="hidden" name="id" value="<?= $pedido['id'] ?>">
                                        <select name="estado" class="custom-select custom-select-sm mr-2">
                                            <?php foreach (ESTADOS_PEDIDO as $estado => $color): ?>
                                                <option value="<?= $estado ?>"<?= $estado === $pedido['estado'] ? ' selected' : '' ?>><?= ucfirst($estado) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-outline-dark">Guardar</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>

            <?php elseif ($seccion === 'publicidad'): ?>
                <!-- Crear un anuncio de la tienda -->
                <h4 class="mb-3">Nuevo anuncio <small class="text-muted">(se publica directamente)</small></h4>
                <?php if ($error_anuncio): ?>
                    <div class="alert alert-danger"><?= escapar_html($error_anuncio) ?></div>
                <?php endif; ?>
                <div class="bg-light p-30 mb-5">
                    <?php mostrar_formulario_anuncio('index.php?p=admin&amp;seccion=publicidad', 'accion_admin', 'crear_anuncio', 'Publicar anuncio'); ?>
                </div>

                <!-- Todos los anuncios -->
                <h4 class="mb-3">Anuncios</h4>
                <?php if (!$anuncios): ?>
                    <p>Todavía no hay anuncios.</p>
                <?php else: ?>
                <div class="table-responsive mb-5">
                    <table class="table table-light table-borderless table-hover mb-0">
                        <thead class="thead-dark">
                            <tr><th>Imagen</th><th>Anuncio</th><th>Autor</th><th>Fecha</th><th>Estado</th><th>Acciones</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($anuncios as $anuncio): ?>
                            <tr>
                                <td class="align-middle">
                                    <?php if ($anuncio['imagen']): ?>
                                        <img src="<?= escapar_html($anuncio['imagen']) ?>" alt="" style="width: 70px; height: 50px; object-fit: cover;">
                                    <?php else: ?>
                                        <i class="fa fa-bullhorn fa-2x text-muted"></i>
                                    <?php endif; ?>
                                </td>
                                <td class="align-middle">
                                    <b><?= escapar_html($anuncio['titulo']) ?></b><br>
                                    <small><?= escapar_html($anuncio['texto']) ?></small>
                                    <?php if ($anuncio['enlace']): ?>
                                        <br><small class="text-muted"><i class="fa fa-link mr-1"></i><?= escapar_html($anuncio['enlace']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="align-middle"><?= escapar_html($anuncio['usuario']) ?><?= $anuncio['rol'] === 'admin' ? ' <small class="text-muted">(tienda)</small>' : '' ?></td>
                                <td class="align-middle text-nowrap"><?= date('d/m/Y', strtotime($anuncio['fecha'])) ?></td>
                                <td class="align-middle"><?php mostrar_estado_anuncio($anuncio['estado']); ?></td>
                                <td class="align-middle text-nowrap">
                                    <!-- Aprobar / Rechazar: solo se ofrece el cambio que tiene sentido -->
                                    <?php foreach (['aprobado' => ['Aprobar', 'btn-success'], 'rechazado' => ['Rechazar', 'btn-outline-warning']] as $estado => [$texto_boton, $clase]): ?>
                                        <?php if ($anuncio['estado'] !== $estado): ?>
                                            <form action="index.php?p=admin&amp;seccion=publicidad" method="post" class="d-inline">
                                                <input type="hidden" name="accion_admin" value="estado_anuncio">
                                                <input type="hidden" name="token_csrf" value="<?= escapar_html($token) ?>">
                                                <input type="hidden" name="id" value="<?= $anuncio['id'] ?>">
                                                <input type="hidden" name="estado" value="<?= $estado ?>">
                                                <button type="submit" class="btn btn-sm <?= $clase ?>"><?= $texto_boton ?></button>
                                            </form>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                    <form action="index.php?p=admin&amp;seccion=publicidad" method="post" class="d-inline" onsubmit="return confirm('¿Borrar este anuncio?');">
                                        <input type="hidden" name="accion_admin" value="borrar_anuncio">
                                        <input type="hidden" name="token_csrf" value="<?= escapar_html($token) ?>">
                                        <input type="hidden" name="id" value="<?= $anuncio['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Borrar"><i class="fa fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    <!-- Administración End -->
