<?php
/*
  Panel de administración (index.php?p=admin). Solo para administradores
  (admin-acciones.php redirige al login si no lo eres).

  Pestañas: Productos · Cupones (admin-cupones.php) · Categorías y Pedidos (pendientes).
  Los formularios los procesa admin-acciones.php.

  Variables disponibles: $conexion, $errores_admin, $datos_producto
*/

// Pestaña Cupones (index.php?p=admin&seccion=cupones)
if (($_GET['seccion'] ?? '') === 'cupones') {
    include 'admin-cupones.php';
    return;
}

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

            <!-- Pestañas del panel -->
            <div class="mb-4">
                <a class="btn btn-dark py-2 px-3 mr-1 mb-2 active" href="index.php?p=admin">Productos</a>
                <a class="btn btn-outline-dark py-2 px-3 mr-1 mb-2" href="index.php?p=admin&amp;seccion=cupones">Cupones</a>
                <span class="btn btn-outline-dark py-2 px-3 mr-1 mb-2 disabled" title="Próximamente">Categorías</span>
                <span class="btn btn-outline-dark py-2 px-3 mb-2 disabled" title="Próximamente">Pedidos</span>
            </div>

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
