<?php
/*
  Acciones de sesión y del panel de administración.
  Se incluye desde index.php ANTES de pintar la página, porque al terminar
  una acción redirigimos con header('Location: ...').

  Variables que deja preparadas para login.php y admin.php:
    $error_login     Mensaje si el usuario o la contraseña no son correctos
    $errores_admin   Lista de errores del formulario de producto
    $datos_producto  Valores del formulario (para no perder lo escrito si hay errores)
*/

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
