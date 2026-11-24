<?php
/*
  MIS FAVORITOS (index.php?p=favoritos)
  - Recibe el botón del corazón de toda la tienda: si el producto ya era favorito
    se quita y, si no, se añade. Después se vuelve a la página donde se pulsó.
  - Muestra los productos favoritos del cliente.
  Hay que haber iniciado sesión.
*/
if (!tiene_sesion_iniciada()) {
    $_SESSION['aviso_login'] = 'Inicia sesión para guardar tus productos favoritos.';
    header('Location: index.php?p=login');
    exit;
}
$id_usuario = $_SESSION['usuario']['id'];

// ---- Botón del corazón ----
if (isset($_POST['id'])) {
    $id_producto = (int) $_POST['id'];
    if (es_favorito($id_producto)) {
        ejecutar("DELETE FROM favoritos WHERE usuario_id = ? AND producto_id = ?", [$id_usuario, $id_producto]);
    } elseif (obtener_producto($id_producto)) {
        ejecutar("INSERT INTO favoritos (usuario_id, producto_id) VALUES (?, ?)", [$id_usuario, $id_producto]);
    }
    // Vuelta a la página donde se pulsó (siempre dentro de index.php)
    $volver = $_POST['volver'] ?? '';
    header('Location: index.php' . ($volver !== '' ? '?' . $volver : ''));
    exit;
}

// ---- Productos favoritos, del último que se añadió al primero ----
$favoritos = consultar(SQL_PRODUCTOS . " JOIN favoritos f ON f.producto_id = p.id
                                         WHERE f.usuario_id = ? ORDER BY f.fecha DESC", [$id_usuario]);

mostrar_migas_de_pan([
    'Inicio' => 'index.php',
    'Mis favoritos' => null,
]);
?>
    <!-- Favoritos Start -->
    <div class="container-fluid pb-3">
        <h2 class="section-title position-relative text-uppercase mx-xl-5 mb-4">
            <span class="bg-secondary pr-3">Mis favoritos <small class="text-muted">(<?= count($favoritos) ?>)</small></span>
        </h2>
        <div class="row px-xl-5">
            <?php if (!$favoritos): ?>
                <div class="col-12">
                    <div class="bg-light p-30 text-center mb-4">
                        <h1 class="display-4 text-primary"><i class="far fa-heart"></i></h1>
                        <h3 class="mb-3">Todavía no tienes favoritos</h3>
                        <p class="mb-4">Pulsa el corazón de cualquier producto para guardarlo aquí.</p>
                        <a class="btn btn-primary py-2 px-4" href="index.php?p=tienda">Ir a la tienda</a>
                    </div>
                </div>
            <?php endif; ?>
            <?php foreach ($favoritos as $producto): ?>
                <div class="col-lg-3 col-md-4 col-sm-6 pb-1">
                    <?php mostrar_tarjeta_producto($producto); ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <!-- Favoritos End -->
