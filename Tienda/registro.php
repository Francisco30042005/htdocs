<?php
/*
  Crear cuenta de cliente (index.php?p=registro).
  El formulario lo procesa admin-acciones.php (función registrar_usuario de index.php).
  Variables disponibles: $errores_registro (admin-acciones.php)
*/
mostrar_migas_de_pan([
    'Inicio' => 'index.php',
    'Crear cuenta' => null,
]);
?>
    <!-- Registro Start -->
    <div class="container-fluid">
        <h2 class="section-title position-relative text-uppercase mx-xl-5 mb-4"><span class="bg-secondary pr-3">Crear cuenta</span></h2>
        <div class="row px-xl-5">
            <div class="col-lg-5 col-md-8 mb-5">
                <div class="bg-light p-30">
                    <?php if (!empty($_SESSION['volver_al_carrito'])): ?>
                        <div class="alert alert-info"><i class="fa fa-shopping-cart mr-1"></i> Crea tu cuenta para finalizar la compra. Tu carrito está guardado y volverás a él al registrarte.</div>
                    <?php endif; ?>
                    <?php if ($errores_registro): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0 pl-3">
                                <?php foreach ($errores_registro as $error): ?>
                                    <li><?= escapar_html($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    <form action="index.php?p=registro" method="post">
                        <input type="hidden" name="accion_sesion" value="registrar">
                        <div class="form-group">
                            <label for="usuario">Usuario</label>
                            <input type="text" class="form-control" id="usuario" name="usuario" required autofocus
                                   minlength="3" maxlength="50" pattern="[A-Za-z0-9_.\-]{3,50}"
                                   title="Entre 3 y 50 caracteres: letras sin tildes, números, punto, guion o guion bajo"
                                   autocomplete="username" value="<?= escapar_html($_POST['usuario'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="contrasena">Contraseña <small class="text-muted">(mínimo 6 caracteres)</small></label>
                            <input type="password" class="form-control" id="contrasena" name="contrasena" required minlength="6" autocomplete="new-password">
                        </div>
                        <div class="form-group">
                            <label for="repetir_contrasena">Repite la contraseña</label>
                            <input type="password" class="form-control" id="repetir_contrasena" name="repetir_contrasena" required minlength="6" autocomplete="new-password">
                        </div>
                        <button class="btn btn-primary py-2 px-4" type="submit">
                            <i class="fa fa-user-plus mr-1"></i> Crear cuenta
                        </button>
                    </form>
                    <p class="small mt-3 mb-0">¿Ya tienes cuenta? <a href="index.php?p=login" class="text-primary"><b>Inicia sesión</b></a></p>
                </div>
            </div>
        </div>
    </div>
    <!-- Registro End -->
