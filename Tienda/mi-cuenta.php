<?php
/*
  Mi cuenta (index.php?p=mi-cuenta). Solo con sesión iniciada.
  Los formularios los procesa admin.php.
  Variables disponibles: $conexion, $mensaje_cuenta
*/
$stmt = $conexion->prepare("SELECT usuario, nombre, direccion, telefono FROM usuarios WHERE id = ?");
$stmt->bind_param('i', $_SESSION['usuario']['id']);
$stmt->execute();
$yo = $stmt->get_result()->fetch_assoc();

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
                    <form action="index.php" method="post">
                        <input type="hidden" name="accion_sesion" value="salir">
                        <input type="hidden" name="token_csrf" value="<?= escapar_html(obtener_token_csrf()) ?>">
                        <button type="submit" class="btn btn-outline-dark"><i class="fa fa-sign-out-alt mr-1"></i> Cerrar sesión</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="row px-xl-5">
            <div class="col-12">
                <?php if ($mensaje_cuenta): ?>
                    <div class="alert alert-info"><?= escapar_html($mensaje_cuenta) ?></div>
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
                            <input type="text" id="direccion" name="direccion" class="form-control" maxlength="200" value="<?= escapar_html($yo['direccion'] ?? '') ?>">
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
                            <input type="password" id="nueva" name="nueva" class="form-control" minlength="6" required autocomplete="new-password">
                        </div>
                        <div class="form-group">
                            <label for="repetir">Repite la nueva contraseña</label>
                            <input type="password" id="repetir" name="repetir" class="form-control" minlength="6" required autocomplete="new-password">
                        </div>
                        <button type="submit" class="btn btn-primary">Cambiar contraseña</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- Mi cuenta End -->
