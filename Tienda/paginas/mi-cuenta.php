<?php
/*
  MI CUENTA (index.php?p=mi-cuenta): datos personales, cambiar la contraseña, mis pedidos y cerrar sesión.
  Solo con la sesión iniciada.
*/
if (!tiene_sesion_iniciada()) {
    header('Location: index.php?p=login');
    exit;
}
$id_usuario = $_SESSION['usuario']['id'];
$accion = $_POST['accion_cuenta'] ?? '';
$mensaje_cuenta = '';

// Cerrar sesión (lleva el token de seguridad para que un simple enlace no pueda cerrarla)
if ($accion === 'salir' && tiene_token_csrf_valido()) {
    unset($_SESSION['usuario'], $_SESSION['token_csrf']);
    session_regenerate_id(true);
    header('Location: index.php');
    exit;
}

// Guardar mis datos
$error_direccion = false;
if ($accion === 'datos') {
    $direccion = trim($_POST['direccion'] ?? '');
    // La dirección puede quedar vacía (entonces no se puede pagar), pero si se escribe tiene que ser correcta
    if ($direccion !== '' && !es_direccion_valida($direccion)) {
        $error_direccion = true;
        $mensaje_cuenta = 'No se han guardado los datos: la dirección de envío no tiene un formato correcto.';
    } else {
        ejecutar("UPDATE usuarios SET nombre = ?, direccion = ?, telefono = ? WHERE id = ?",
                 [trim($_POST['nombre'] ?? ''), $direccion, trim($_POST['telefono'] ?? ''), $id_usuario]);
        $mensaje_cuenta = 'Datos guardados.';
    }

    // Si venía del carrito a poner la dirección para pagar, vuelve al carrito
    if (!$error_direccion && !empty($_SESSION['volver_al_carrito']) && obtener_direccion_envio() !== '') {
        unset($_SESSION['volver_al_carrito']);
        header('Location: index.php?p=carrito');
        exit;
    }
}

// Cambiar la contraseña
if ($accion === 'contrasena') {
    $actual = consultar("SELECT contrasena FROM usuarios WHERE id = ?", [$id_usuario])[0]['contrasena'];
    $nueva = $_POST['nueva'] ?? '';
    if (($_POST['actual'] ?? '') !== $actual) {
        $mensaje_cuenta = 'La contraseña actual no es correcta.';
    } elseif (comprobar_contrasena($nueva) !== '') {
        $mensaje_cuenta = 'No se ha cambiado la contraseña. ' . comprobar_contrasena($nueva);
    } elseif ($nueva !== ($_POST['repetir'] ?? '')) {
        $mensaje_cuenta = 'Las contraseñas nuevas no coinciden.';
    } else {
        ejecutar("UPDATE usuarios SET contrasena = ? WHERE id = ?", [$nueva, $id_usuario]);
        $mensaje_cuenta = 'Contraseña cambiada.';
    }
}

$yo = consultar("SELECT usuario, nombre, direccion, telefono FROM usuarios WHERE id = ?", [$id_usuario])[0];
if ($error_direccion) {
    // Se vuelve a mostrar lo que había escrito, para que pueda corregirlo
    $yo['nombre'] = trim($_POST['nombre'] ?? '');
    $yo['direccion'] = trim($_POST['direccion'] ?? '');
    $yo['telefono'] = trim($_POST['telefono'] ?? '');
}
$mis_pedidos = consultar("SELECT * FROM pedidos WHERE usuario_id = ? ORDER BY fecha DESC", [$id_usuario]);

mostrar_migas_de_pan([
    'Inicio' => 'index.php',
    'Mi cuenta' => null,
]);
?>
    <!-- Mi cuenta Start -->
    <div class="container-fluid">
        <h2 class="section-title position-relative text-uppercase mx-xl-5 mb-4"><span class="bg-secondary pr-3">Mi cuenta</span></h2>

        <!-- Saludo y cerrar sesión -->
        <div class="row px-xl-5">
            <div class="col-12 mb-4">
                <div class="bg-light p-4 d-flex flex-wrap align-items-center justify-content-between">
                    <div class="d-flex align-items-center mb-2 mb-sm-0">
                        <span class="d-inline-flex align-items-center justify-content-center bg-primary rounded-circle mr-3" style="width: 48px; height: 48px;">
                            <i class="fa fa-user text-dark"></i>
                        </span>
                        <div>
                            <h5 class="mb-0">Hola, <span class="notranslate" translate="no"><?= escapar_html($yo['nombre'] ?: $yo['usuario']) ?></span></h5>
                            <small class="text-muted"><?= es_admin() ? 'Administrador' : 'Cliente' ?> · usuario <span class="notranslate" translate="no"><?= escapar_html($yo['usuario']) ?></span></small>
                        </div>
                    </div>
                    <!-- Cerrar sesión va por POST (con token) para que un simple enlace no pueda cerrarla -->
                    <form action="index.php?p=mi-cuenta" method="post">
                        <input type="hidden" name="accion_cuenta" value="salir">
                        <input type="hidden" name="token_csrf" value="<?= escapar_html(obtener_token_csrf()) ?>">
                        <button type="submit" class="btn btn-outline-dark"><i class="fa fa-sign-out-alt mr-1"></i> Cerrar sesión</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="row px-xl-5">
            <div class="col-12">
                <?php if ($mensaje_cuenta): ?>
                    <div class="alert alert-<?= in_array($mensaje_cuenta, ['Datos guardados.', 'Contraseña cambiada.']) ? 'info' : 'danger' ?>"><?= escapar_html($mensaje_cuenta) ?></div>
                <?php endif; ?>
                <?php if (!empty($_SESSION['volver_al_carrito'])): ?>
                    <div class="alert alert-warning">
                        <i class="fa fa-truck mr-1"></i> Para pagar tu pedido necesitamos una <b>dirección de envío</b>.
                        Escríbela en «Mis datos» y pulsa Guardar: volverás a tu carrito.
                    </div>
                <?php endif; ?>
            </div>

            <!-- Mis datos -->
            <div class="col-lg-6 mb-5">
                <div class="bg-light p-30">
                    <h5 class="mb-3">Mis datos</h5>
                    <form action="index.php?p=mi-cuenta" method="post">
                        <input type="hidden" name="accion_cuenta" value="datos">
                        <div class="form-group">
                            <label>Usuario</label>
                            <input type="text" class="form-control" value="<?= escapar_html($yo['usuario']) ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label for="nombre">Nombre y apellidos</label>
                            <input type="text" id="nombre" name="nombre" class="form-control" maxlength="100" value="<?= escapar_html($yo['nombre'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="direccion">Dirección de envío</label>
                            <input type="text" id="direccion" name="direccion" class="form-control<?= $error_direccion ? ' is-invalid' : '' ?>" maxlength="200"
                                   placeholder="Calle Mayor 12, 3º B, 28013 Madrid" value="<?= escapar_html($yo['direccion'] ?? '') ?>">
                            <small class="form-text text-muted">
                                Tipo de vía (Calle, C/, Avenida, Plaza, Paseo...), nombre y número; si quieres, piso y puerta;
                                y al final el código postal y la ciudad, separados por comas.
                            </small>
                        </div>
                        <div class="form-group">
                            <label for="telefono">Teléfono</label>
                            <input type="tel" id="telefono" name="telefono" class="form-control" maxlength="20" value="<?= escapar_html($yo['telefono'] ?? '') ?>">
                        </div>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </form>
                </div>
            </div>

            <!-- Cambiar contraseña -->
            <div class="col-lg-6 mb-5">
                <div class="bg-light p-30">
                    <h5 class="mb-3">Cambiar contraseña</h5>
                    <form action="index.php?p=mi-cuenta" method="post">
                        <input type="hidden" name="accion_cuenta" value="contrasena">
                        <div class="form-group">
                            <label for="actual">Contraseña actual</label>
                            <input type="password" id="actual" name="actual" class="form-control" required autocomplete="current-password">
                        </div>
                        <div class="form-group">
                            <label for="nueva">Nueva contraseña</label>
                            <input type="password" id="nueva" name="nueva" class="form-control" minlength="9" required autocomplete="new-password"
                                   pattern="<?= escapar_html(PATRON_CONTRASENA) ?>" title="<?= escapar_html(REQUISITOS_CONTRASENA) ?>">
                            <small class="form-text text-muted"><?= escapar_html(REQUISITOS_CONTRASENA) ?>.</small>
                        </div>
                        <div class="form-group">
                            <label for="repetir">Repite la nueva contraseña</label>
                            <input type="password" id="repetir" name="repetir" class="form-control" minlength="9" required autocomplete="new-password">
                        </div>
                        <button type="submit" class="btn btn-primary">Cambiar contraseña</button>
                    </form>
                </div>
            </div>

            <!-- Mis pedidos -->
            <div class="col-12 mb-5">
                <div class="bg-light p-30">
                    <h5 class="mb-3">Mis pedidos</h5>
                    <?php if (!$mis_pedidos): ?>
                        <p class="mb-0">Todavía no has hecho ningún pedido. <a href="index.php?p=tienda" class="text-primary">Ir a la tienda</a></p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-light table-borderless table-hover mb-0">
                                <thead class="thead-dark">
                                    <tr><th>Pedido</th><th>Fecha</th><th>Productos</th><th class="text-right">Total</th><th>Estado</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($mis_pedidos as $pedido): ?>
                                    <tr>
                                        <td class="align-middle"><b>#<?= $pedido['id'] ?></b></td>
                                        <td class="align-middle text-nowrap"><?= date('d/m/Y', strtotime($pedido['fecha'])) ?></td>
                                        <td class="align-middle small">
                                            <?php foreach (obtener_lineas_pedido($pedido['id']) as $linea): ?>
                                                <?= $linea['cantidad'] ?> × <?= escapar_html($linea['nombre']) ?><br>
                                            <?php endforeach; ?>
                                            <?php if ($pedido['descuento']): ?>
                                                <span class="text-muted">Cupones: −<?= $pedido['descuento'] ?> %</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="align-middle text-right text-nowrap"><?= formatear_precio($pedido['total']) ?></td>
                                        <td class="align-middle"><?php mostrar_estado_pedido($pedido['estado']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <!-- Mi cuenta End -->
