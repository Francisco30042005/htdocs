<?php
/*
  Ficha de producto (index.php?p=informacion-producto&id=X)
  Variables disponibles: $conexion (conexion.php)
*/
// Recogemos el id del producto que llega por la URL
$id_producto = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$producto_detalle = $id_producto ? obtener_producto_por_id($conexion, $id_producto) : null;

mostrar_migas_de_pan([
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
        $detalle_rebajado = tiene_precio_rebajado($producto_detalle);
        $detalle_imagen = obtener_imagen_producto($producto_detalle, '600x700');
        $relacionados = obtener_productos($conexion, false, null, 8, (int) $producto_detalle['id']);

        // Reseñas del producto y la del usuario conectado (si ya escribió una)
        $resenas = obtener_resenas($conexion, (int) $producto_detalle['id']);
        $mi_resena = null;
        foreach ($resenas as $resena) {
            if (tiene_sesion_iniciada() && (int) $resena['usuario_id'] === (int) $_SESSION['usuario']['id']) {
                $mi_resena = $resena;
            }
        }
        $mensaje_resena = $_SESSION['mensaje_resena'] ?? '';
        unset($_SESSION['mensaje_resena']);
    ?>
    <!-- Shop Detail Start -->
    <div class="container-fluid pb-5">
        <div class="row px-xl-5">
            <div class="col-lg-5 mb-30">
                <div class="bg-light position-relative">
                    <img class="w-100 h-100" src="<?= escapar_html($detalle_imagen) ?>" alt="<?= escapar_html($producto_detalle['nombre']) ?>">
                    <?php if ($detalle_rebajado): ?>
                        <span class="badge badge-primary text-dark position-absolute px-3 py-2" style="top: 15px; right: 15px;">Rebajado</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-7 h-auto mb-30">
                <div class="h-100 bg-light p-30">
                    <small class="text-muted">SKU: <?= escapar_html($producto_detalle['referencia']) ?></small>
                    <h3><?= escapar_html($producto_detalle['nombre']) ?></h3>
                    <!-- Valoración -->
                    <div class="d-flex mb-3">
                        <div class="mr-2">
                            <?= generar_estrellas_html($producto_detalle['valoracion'], 'text-primary') ?>
                        </div>
                        <small class="pt-1">(<?= number_format((float) $producto_detalle['valoracion'], 1) ?> / 5) ·
                            <a href="#resenas" class="text-primary"><?= count($resenas) ?> <?= count($resenas) === 1 ? 'reseña' : 'reseñas' ?></a></small>
                    </div>
                    <!-- Precio -->
                    <div class="d-flex align-items-baseline mb-4">
                        <?php if ($detalle_rebajado): ?>
                            <h3 class="font-weight-semi-bold mb-0"><?= formatear_precio($producto_detalle['precio_rebajado']) ?></h3>
                            <h5 class="text-muted ml-3 mb-0"><del><?= formatear_precio($producto_detalle['precio']) ?></del></h5>
                        <?php else: ?>
                            <h3 class="font-weight-semi-bold mb-0"><?= formatear_precio($producto_detalle['precio']) ?></h3>
                        <?php endif; ?>
                    </div>
                    <!-- Descripción -->
                    <p class="mb-4"><?= nl2br(escapar_html($producto_detalle['descripcion'])) ?></p>

                    <form class="d-flex align-items-center mb-4 pt-2" action="index.php?p=carrito" method="post">
                        <input type="hidden" name="accion" value="anadir">
                        <input type="hidden" name="id" value="<?= (int) $producto_detalle['id'] ?>">
                        <div class="input-group quantity mr-3" style="width: 130px;">
                            <div class="input-group-btn">
                                <button class="btn btn-primary btn-minus" type="button">
                                    <i class="fa fa-minus"></i>
                                </button>
                            </div>
                            <input type="text" id="inputQuantity" name="cantidad" class="form-control bg-secondary border-0 text-center" value="1">
                            <div class="input-group-btn">
                                <button class="btn btn-primary btn-plus" type="button">
                                    <i class="fa fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        <button class="btn btn-primary px-3" type="submit"><i class="fa fa-shopping-cart mr-1"></i> Añadir al carrito</button>
                    </form>
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
                            <p><?= nl2br(escapar_html($producto_detalle['descripcion'])) ?></p>
                        </div>
                        <div class="tab-pane fade" id="tab-pane-2">
                            <h4 class="mb-3">Información adicional</h4>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item px-0 bg-transparent"><strong>Referencia:</strong> <?= escapar_html($producto_detalle['referencia']) ?></li>
                                <li class="list-group-item px-0 bg-transparent"><strong>Precio:</strong> <?= formatear_precio($producto_detalle['precio']) ?></li>
                                <?php if ($detalle_rebajado): ?>
                                    <li class="list-group-item px-0 bg-transparent"><strong>Precio rebajado:</strong> <?= formatear_precio($producto_detalle['precio_rebajado']) ?></li>
                                <?php endif; ?>
                                <li class="list-group-item px-0 bg-transparent"><strong>Valoración:</strong> <?= number_format((float) $producto_detalle['valoracion'], 1) ?> / 5</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reseñas -->
        <div class="row px-xl-5 mt-4" id="resenas">
            <div class="col">
                <div class="bg-light p-30">
                    <?php if ($mensaje_resena): ?>
                        <div class="alert alert-success"><?= escapar_html($mensaje_resena) ?></div>
                    <?php endif; ?>
                    <div class="row">
                        <!-- Lista de reseñas -->
                        <div class="col-md-6 mb-4">
                            <h4 class="mb-4"><?= count($resenas) ?> <?= count($resenas) === 1 ? 'reseña' : 'reseñas' ?> de «<?= escapar_html($producto_detalle['nombre']) ?>»</h4>
                            <?php if (!$resenas): ?>
                                <p>Todavía no hay reseñas. ¡Sé el primero en opinar!</p>
                            <?php endif; ?>
                            <?php foreach ($resenas as $resena): ?>
                                <div class="media mb-4">
                                    <span class="d-inline-flex align-items-center justify-content-center bg-primary rounded-circle text-dark font-weight-bold mr-3 flex-shrink-0" style="width: 45px; height: 45px;">
                                        <?= escapar_html(mb_strtoupper(mb_substr($resena['usuario'], 0, 1))) ?>
                                    </span>
                                    <div class="media-body">
                                        <h6 class="mb-1"><span class="notranslate" translate="no"><?= escapar_html($resena['usuario']) ?></span>
                                            <small> - <i><?= date('d/m/Y', strtotime($resena['fecha'])) ?></i></small></h6>
                                        <div class="mb-2"><?= generar_estrellas_html((float) $resena['puntuacion'], 'text-primary mr-1') ?></div>
                                        <?php if ($resena['comentario'] !== null && $resena['comentario'] !== ''): ?>
                                            <p class="mb-0"><?= nl2br(escapar_html($resena['comentario'])) ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Escribir (o cambiar) mi reseña -->
                        <div class="col-md-6">
                            <h4 class="mb-4"><?= $mi_resena ? 'Edita tu reseña' : 'Escribe una reseña' ?></h4>
                            <?php if (tiene_sesion_iniciada()): ?>
                                <form action="index.php?p=informacion-producto&amp;id=<?= (int) $producto_detalle['id'] ?>" method="post">
                                    <input type="hidden" name="accion_resena" value="guardar">
                                    <input type="hidden" name="producto_id" value="<?= (int) $producto_detalle['id'] ?>">
                                    <div class="form-group">
                                        <label for="puntuacion">Tu puntuación *</label>
                                        <select id="puntuacion" name="puntuacion" class="custom-select" required>
                                            <?php $actual = (int) ($mi_resena['puntuacion'] ?? 5); ?>
                                            <?php foreach ([5 => 'Excelente', 4 => 'Muy bueno', 3 => 'Normal', 2 => 'Regular', 1 => 'Malo'] as $valor => $texto): ?>
                                                <option value="<?= $valor ?>"<?= $valor === $actual ? ' selected' : '' ?>><?= str_repeat('★', $valor) . str_repeat('☆', 5 - $valor) ?> <?= $valor ?> - <?= $texto ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="comentario">Tu opinión (opcional)</label>
                                        <textarea id="comentario" name="comentario" class="form-control" rows="5" maxlength="1000"
                                                  placeholder="¿Qué te ha parecido el producto?"><?= escapar_html($mi_resena['comentario'] ?? '') ?></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary px-3"><?= $mi_resena ? 'Guardar cambios' : 'Publicar reseña' ?></button>
                                </form>
                            <?php else: ?>
                                <p>Para escribir una reseña tienes que iniciar sesión.</p>
                                <a href="index.php?p=login" class="btn btn-primary px-3"><i class="fa fa-sign-in-alt mr-1"></i> Iniciar sesión</a>
                            <?php endif; ?>
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
                        <?php mostrar_tarjeta_producto($producto); ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <!-- Products End -->
    <?php endif; ?>
<?php endif; ?>
