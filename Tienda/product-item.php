        <?php
            // Recogemos el id del producto que llega por la URL (index.php?p=informacion-producto&id=X)
            $id_producto = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

            $producto_detalle = null;

            if ($id_producto) {
                // Consulta preparada para evitar inyección SQL
                $sql_detalle = "SELECT id, referencia, nombre, precio,
                                    precio_rebajado, descripcion, valoracion
                                FROM productos
                                WHERE id = ?";

                $stmt = $conexion->prepare($sql_detalle);
                if ($stmt) {
                    $stmt->bind_param("i", $id_producto);
                    $stmt->execute();
                    $resultado = $stmt->get_result();
                    $producto_detalle = $resultado->fetch_assoc();
                    $stmt->close();
                }
            }
        ?>
        <section class="py-5">
            <div class="container px-4 px-lg-5 my-5">
                <?php if (!$producto_detalle): ?>
                    <!-- Producto no encontrado -->
                    <div class="text-center">
                        <h1 class="display-6 fw-bolder">Producto no encontrado</h1>
                        <p class="lead">El producto que buscas no existe o ya no está disponible.</p>
                        <a class="btn btn-outline-dark" href="index.php?p=tienda">Volver a la tienda</a>
                    </div>
                <?php else: ?>
                    <?php
                        // Evaluamos si el producto está rebajado
                        $detalle_rebajado = !is_null($producto_detalle['precio_rebajado']) && $producto_detalle['precio_rebajado'] > 0;
                        // Redondeamos la valoración para pintar las estrellas
                        $detalle_estrellas = round($producto_detalle['valoracion']);
                        // Imagen del producto (o una de relleno si no existe)
                        $detalle_imagen = 'images/' . $producto_detalle['referencia'] . '.jpg';
                        if (!file_exists($detalle_imagen)) {
                            $detalle_imagen = 'https://dummyimage.com/600x700/dee2e6/6c757d.jpg';
                        }
                    ?>
                <div class="row gx-4 gx-lg-5 align-items-center">
                    <div class="col-md-6"><img class="card-img-top mb-5 mb-md-0" src="<?= htmlspecialchars($detalle_imagen) ?>" alt="<?= htmlspecialchars($producto_detalle['nombre']) ?>" /></div>
                    <div class="col-md-6">
                        <div class="small mb-1">SKU: <?= htmlspecialchars($producto_detalle['referencia']) ?></div>
                        <h1 class="display-5 fw-bolder"><?= htmlspecialchars($producto_detalle['nombre']) ?></h1>
                        <!-- Valoración -->
                        <div class="d-flex small text-warning mb-2">
                            <?php
                            for ($i = 1; $i <= 5; $i++) {
                                echo ($i <= $detalle_estrellas) ? '<div class="bi-star-fill"></div>' : '<div class="bi-star"></div>';
                            }
                            ?>
                        </div>
                        <!-- Precio -->
                        <div class="fs-5 mb-5">
                            <?php if ($detalle_rebajado): ?>
                                <span class="text-decoration-line-through">$<?= number_format($producto_detalle['precio'], 2) ?></span>
                                <span>$<?= number_format($producto_detalle['precio_rebajado'], 2) ?></span>
                            <?php else: ?>
                                <span>$<?= number_format($producto_detalle['precio'], 2) ?></span>
                            <?php endif; ?>
                        </div>
                        <!-- Descripción -->
                        <p class="lead"><?= nl2br(htmlspecialchars($producto_detalle['descripcion'] ?? '')) ?></p>
                        <div class="d-flex">
                            <input class="form-control text-center me-3" id="inputQuantity" type="num" value="1" style="max-width: 3rem" />
                            <button class="btn btn-outline-dark flex-shrink-0" type="button">
                                <i class="bi-cart-fill me-1"></i>
                                Add to cart
                            </button>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </section>
