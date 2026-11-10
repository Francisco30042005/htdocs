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
    <div class="container-fluid">
        <h2 class="section-title position-relative text-uppercase mx-xl-5 mb-4"><span class="bg-secondary pr-3">Acceder</span></h2>
        <div class="row px-xl-5">
            <div class="col-lg-5 col-md-8 mb-5">
                <div class="bg-light p-30">
                    <?php if (!empty($_SESSION['volver_al_carrito'])): ?>
                        <div class="alert alert-info"><i class="fa fa-shopping-cart mr-1"></i> Inicia sesión para finalizar tu compra. Tu carrito está guardado y volverás a él al entrar.</div>
                    <?php endif; ?>
                    <?php if ($error_login): ?>
                        <div class="alert alert-danger"><?= escapar_html($error_login) ?></div>
                    <?php endif; ?>
                    <form action="index.php?p=login" method="post">
                        <input type="hidden" name="accion_sesion" value="entrar">
                        <div class="form-group">
                            <label for="usuario">Usuario</label>
                            <input type="text" class="form-control" id="usuario" name="usuario" maxlength="50" required autofocus
                                   autocomplete="username" value="<?= escapar_html($_POST['usuario'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="contrasena">Contraseña</label>
                            <input type="password" class="form-control" id="contrasena" name="contrasena" required autocomplete="current-password">
                        </div>
                        <button class="btn btn-primary py-2 px-4" type="submit">
                            <i class="fa fa-sign-in-alt mr-1"></i> Entrar
                        </button>
                    </form>
                    <p class="small mt-3 mb-0">¿No tienes cuenta? <a href="index.php?p=registro" class="text-primary"><b>Regístrate</b></a></p>
                </div>
            </div>
        </div>
    </div>
    <!-- Login End -->
