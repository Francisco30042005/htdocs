<?php
/*
  CREAR CUENTA (index.php?p=registro): los clientes nuevos se registran aquí y entran directamente.
  Los administradores se crean en la base de datos (rol 'admin').
*/
if (tiene_sesion_iniciada()) {
    header('Location: ' . (es_admin() ? 'index.php?p=admin' : 'index.php'));
    exit;
}

$errores_registro = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $contrasena = $_POST['contrasena'] ?? '';

    if (!preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $usuario)) {
        $errores_registro[] = 'El usuario debe tener entre 3 y 50 caracteres: letras sin tildes, números, punto, guion o guion bajo.';
    }
    if (comprobar_contrasena($contrasena) !== '') {
        $errores_registro[] = comprobar_contrasena($contrasena);
    }
    if ($contrasena !== ($_POST['repetir_contrasena'] ?? '')) {
        $errores_registro[] = 'Las dos contraseñas no coinciden.';
    }

    if (!$errores_registro) {
        // Falla si el nombre de usuario ya existe (la columna "usuario" es UNIQUE)
        if (ejecutar("INSERT INTO usuarios (usuario, contrasena, rol) VALUES (?, ?, 'cliente')", [$usuario, $contrasena])) {
            session_regenerate_id(true);
            $id_nuevo = consultar("SELECT LAST_INSERT_ID() AS id")[0]['id']; // id del usuario recién creado
            $_SESSION['usuario'] = ['id' => $id_nuevo, 'usuario' => $usuario, 'rol' => 'cliente'];
            header('Location: ' . obtener_destino_tras_entrar());
            exit;
        }
        $errores_registro[] = 'Ese nombre de usuario ya está en uso. Elige otro.';
    }
}

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
                            <label for="contrasena">Contraseña</label>
                            <input type="password" class="form-control" id="contrasena" name="contrasena" required minlength="9"
                                   pattern="<?= escapar_html(PATRON_CONTRASENA) ?>" title="<?= escapar_html(REQUISITOS_CONTRASENA) ?>" autocomplete="new-password">
                            <small class="form-text text-muted"><?= escapar_html(REQUISITOS_CONTRASENA) ?>.</small>
                        </div>
                        <div class="form-group">
                            <label for="repetir_contrasena">Repite la contraseña</label>
                            <input type="password" class="form-control" id="repetir_contrasena" name="repetir_contrasena" required minlength="9" autocomplete="new-password">
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
