<?php
/*
  Ficha de producto (index.php?p=informacion-producto&id=X)
  Variables disponibles: $conexion (conexion.php)
*/
// Recogemos el id del producto que llega por la URL
$id_producto = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$producto_detalle = $id_producto ? obtener_producto($conexion, $id_producto) : null;

migas([
    'Inicio' => 'index.php',
    'Tienda' => 'index.php?p=tienda',
    ($producto_detalle['nombre'] ?? 'Producto') => null,
]);
?>
<?php if (!$producto_detalle): ?>
    <!-- Producto no encontrado -->
    <div class="container-fluid pb-5">
        <div class="row px-xl-5">
            <div class="col">
                <div class="bg-light p-30 text-center">
                    <h1 class="display-4 text-primary"><i class="fa fa-search"></i></h1>
                    <h3 class="mb-3">Producto no encontrado</h3>
                    <p class="mb-4">El producto que buscas no existe o ya no está disponible.</p>
                    <a class="btn btn-primary py-2 px-4" href="index.php?p=tienda">Volver a la tienda</a>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <?php
        $detalle_rebajado = esta_rebajado($producto_detalle);
        $detalle_imagen = imagen_producto($producto_detalle, '600x700');
        $relacionados = obtener_productos($conexion, false, null, 8, (int) $producto_detalle['id']);
    ?>
    <!-- Shop Detail Start -->
    <div class="container-fluid pb-5">
        <div class="row px-xl-5">
            <div class="col-lg-5 mb-30">
                <div class="bg-light position-relative">
                    <img class="w-100 h-100" src="<?= e($detalle_imagen) ?>" alt="<?= e($producto_detalle['nombre']) ?>">
                    <?php if ($detalle_rebajado): ?>
                        <span class="badge badge-primary text-dark position-absolute px-3 py-2" style="top: 15px; right: 15px;">Rebajado</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-7 h-auto mb-30">
                <div class="h-100 bg-light p-30">
                    <small class="text-muted">SKU: <?= e($producto_detalle['referencia']) ?></small>
                    <h3><?= e($producto_detalle['nombre']) ?></h3>
                    <!-- Valoración -->
                    <div class="d-flex mb-3">
                        <div class="mr-2">
                            <?= estrellas($producto_detalle['valoracion'], 'text-primary') ?>
                        </div>
                        <small class="pt-1">(<?= number_format((float) $producto_detalle['valoracion'], 1) ?> / 5)</small>
                    </div>
                    <!-- Precio -->
                    <div class="d-flex align-items-baseline mb-4">
                        <?php if ($detalle_rebajado): ?>
                            <h3 class="font-weight-semi-bold mb-0"><?= precio($producto_detalle['precio_rebajado']) ?></h3>
                            <h5 class="text-muted ml-3 mb-0"><del><?= precio($producto_detalle['precio']) ?></del></h5>
                        <?php else: ?>
                            <h3 class="font-weight-semi-bold mb-0"><?= precio($producto_detalle['precio']) ?></h3>
                        <?php endif; ?>
                    </div>
                    <!-- Descripción -->
                    <p class="mb-4"><?= nl2br(e($producto_detalle['descripcion'])) ?></p>

                    <div class="d-flex align-items-center mb-4 pt-2">
                        <div class="input-group quantity mr-3" style="width: 130px;">
                            <div class="input-group-btn">
                                <button class="btn btn-primary btn-minus" type="button">
                                    <i class="fa fa-minus"></i>
                                </button>
                            </div>
                            <input type="text" id="inputQuantity" class="form-control bg-secondary border-0 text-center" value="1">
                            <div class="input-group-btn">
                                <button class="btn btn-primary btn-plus" type="button">
                                    <i class="fa fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        <button class="btn btn-primary px-3" type="button"><i class="fa fa-shopping-cart mr-1"></i> Añadir al carrito</button>
                    </div>
                    <div class="d-flex pt-2">
                        <strong class="text-dark mr-2">Compartir:</strong>
                        <div class="d-inline-flex">
                            <a class="text-dark px-2" href="#"><i class="fab fa-facebook-f"></i></a>
                            <a class="text-dark px-2" href="#"><i class="fab fa-twitter"></i></a>
                            <a class="text-dark px-2" href="#"><i class="fab fa-linkedin-in"></i></a>
                            <a class="text-dark px-2" href="#"><i class="fab fa-pinterest"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row px-xl-5">
            <div class="col">
                <div class="bg-light p-30">
                    <div class="nav nav-tabs mb-4">
                        <a class="nav-item nav-link text-dark active" data-toggle="tab" href="#tab-pane-1">Descripción</a>
                        <a class="nav-item nav-link text-dark" data-toggle="tab" href="#tab-pane-2">Información</a>
                    </div>
                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="tab-pane-1">
                            <h4 class="mb-3">Descripción del producto</h4>
                            <p><?= nl2br(e($producto_detalle['descripcion'])) ?></p>
                        </div>
                        <div class="tab-pane fade" id="tab-pane-2">
                            <h4 class="mb-3">Información adicional</h4>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item px-0 bg-transparent"><strong>Referencia:</strong> <?= e($producto_detalle['referencia']) ?></li>
                                <li class="list-group-item px-0 bg-transparent"><strong>Precio:</strong> <?= precio($producto_detalle['precio']) ?></li>
                                <?php if ($detalle_rebajado): ?>
                                    <li class="list-group-item px-0 bg-transparent"><strong>Precio rebajado:</strong> <?= precio($producto_detalle['precio_rebajado']) ?></li>
                                <?php endif; ?>
                                <li class="list-group-item px-0 bg-transparent"><strong>Valoración:</strong> <?= number_format((float) $producto_detalle['valoracion'], 1) ?> / 5</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Shop Detail End -->


    <?php if ($relacionados): ?>
    <!-- Products Start -->
    <div class="container-fluid py-5">
        <h2 class="section-title position-relative text-uppercase mx-xl-5 mb-4"><span class="bg-secondary pr-3">También te puede gustar</span></h2>
        <div class="row px-xl-5">
            <div class="col">
                <div class="owl-carousel related-carousel">
                    <?php foreach ($relacionados as $producto): ?>
                        <?php tarjeta_producto($producto); ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <!-- Products End -->
    <?php endif; ?>
<?php endif; ?>
