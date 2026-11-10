<?php
/*
  Panel de administración > Cupones (index.php?p=admin&seccion=cupones).
  Los formularios los procesa admin-acciones.php.
  Variables disponibles: $conexion
*/
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

            <!-- Pestañas del panel -->
            <div class="mb-4">
                <a class="btn btn-outline-dark py-2 px-3 mr-1 mb-2" href="index.php?p=admin">Productos</a>
                <a class="btn btn-dark py-2 px-3 mr-1 mb-2 active" href="index.php?p=admin&amp;seccion=cupones">Cupones</a>
                <span class="btn btn-outline-dark py-2 px-3 mr-1 mb-2 disabled" title="Próximamente">Categorías</span>
                <span class="btn btn-outline-dark py-2 px-3 mb-2 disabled" title="Próximamente">Pedidos</span>
            </div>

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
