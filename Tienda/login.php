<?php
/*
  Iniciar sesión (index.php?p=login). Lo usan clientes y administradores:
  al entrar, los administradores van al panel y los clientes a la tienda.
  El formulario lo procesa admin-acciones.php.
  Variables disponibles: $error_login (admin-acciones.php)
*/
mostrar_migas_de_pan([
    'Inicio' => 'index.php',
    'Acceder' => null,
]);
?>
    <!-- Login Start -->
    <div class="container-fluid mb-5">
        <div class="row px-xl-5 justify-content-center">
            <div class="col-xl-9 col-lg-11">
                <div class="row no-gutters bg-light shadow-sm">

                    <!-- Formulario -->
                    <div class="col-lg-6 p-4 p-md-5">
                        <div class="text-center mb-4">
                            <span class="d-inline-flex align-items-center justify-content-center bg-primary rounded-circle mb-3" style="width: 64px; height: 64px;">
                                <i class="fa fa-user fa-lg text-dark"></i>
                            </span>
                            <h3 class="mb-1">Bienvenido de nuevo</h3>
                            <p class="text-muted mb-0">Inicia sesión para continuar en Start Shop</p>
                        </div>

                        <?php if (!empty($_SESSION['volver_al_carrito'])): ?>
                            <div class="alert alert-info"><i class="fa fa-shopping-cart mr-1"></i> Inicia sesión para finalizar tu compra. Tu carrito está guardado y volverás a él al entrar.</div>
                        <?php endif; ?>
                        <?php if ($error_login): ?>
                            <div class="alert alert-danger"><i class="fa fa-exclamation-circle mr-1"></i> <?= escapar_html($error_login) ?></div>
                        <?php endif; ?>

                        <form action="index.php?p=login" method="post">
                            <input type="hidden" name="accion_sesion" value="entrar">
                            <div class="form-group">
                                <label for="usuario">Usuario</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fa fa-user"></i></span>
                                    </div>
                                    <input type="text" class="form-control" id="usuario" name="usuario" maxlength="50" required autofocus
                                           placeholder="Tu nombre de usuario" autocomplete="username" value="<?= escapar_html($_POST['usuario'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="contrasena">Contraseña</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fa fa-lock"></i></span>
                                    </div>
                                    <input type="password" class="form-control" id="contrasena" name="contrasena" required
                                           placeholder="Tu contraseña" autocomplete="current-password">
                                    <div class="input-group-append">
                                        <!-- Mostrar / ocultar la contraseña -->
                                        <button type="button" class="input-group-text" title="Mostrar contraseña"
                                                onclick="const c = document.getElementById('contrasena');
                                                         c.type = c.type === 'password' ? 'text' : 'password';
                                                         this.querySelector('i').className = c.type === 'password' ? 'fa fa-eye' : 'fa fa-eye-slash';">
                                            <i class="fa fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <button class="btn btn-primary btn-block py-2 mt-4" type="submit">
                                <i class="fa fa-sign-in-alt mr-1"></i> Entrar
                            </button>
                        </form>

                        <hr class="my-4">
                        <p class="text-center mb-2">¿No tienes cuenta?</p>
                        <a href="index.php?p=registro" class="btn btn-outline-dark btn-block py-2">
                            <i class="fa fa-user-plus mr-1"></i> Crear una cuenta
                        </a>
                    </div>

                    <!-- Ventajas de tener cuenta (solo en pantallas grandes) -->
                    <div class="col-lg-6 bg-primary d-none d-lg-flex flex-column justify-content-center p-5">
                        <h3 class="text-dark mb-2">Tu cuenta en Start Shop</h3>
                        <p class="text-dark mb-4">Todo lo que puedes hacer al iniciar sesión:</p>
                        <div class="d-flex mb-4">
                            <i class="fa fa-shopping-cart fa-2x text-dark text-center flex-shrink-0 mr-3" style="width: 40px;"></i>
                            <div>
                                <h6 class="text-dark mb-1">Compra en pocos clics</h6>
                                <p class="text-dark mb-0 small">Tu carrito se guarda mientras inicias sesión.</p>
                            </div>
                        </div>
                        <div class="d-flex mb-4">
                            <i class="fa fa-tags fa-2x text-dark text-center flex-shrink-0 mr-3" style="width: 40px;"></i>
                            <div>
                                <h6 class="text-dark mb-1">Cupones de descuento</h6>
                                <p class="text-dark mb-0 small">Aplica varios códigos a la vez en el carrito.</p>
                            </div>
                        </div>
                        <div class="d-flex">
                            <i class="fa fa-id-card fa-2x text-dark text-center flex-shrink-0 mr-3" style="width: 40px;"></i>
                            <div>
                                <h6 class="text-dark mb-1">Tus datos guardados</h6>
                                <p class="text-dark mb-0 small">Dirección de envío y teléfono siempre a mano en Mi cuenta.</p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
    <!-- Login End -->
