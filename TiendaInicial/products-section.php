<?php
try {
    // 2. Consultar todos los productos de la tabla
    $productos = $conexion->query("SELECT * FROM productos");
} catch (PDOException $e) {
    die("Error al consultar productos: " . $e->getMessage());
}
?>
<!-- Related items section-->
<section class="py-5 bg-light">
    <div class="container px-4 px-lg-5 mt-5">
        <h2 class="fw-bolder mb-4">Productos relacionados</h2>
        <!-- 3. Contenedor HTML donde se renderizarán los productos -->
        <div class="row gx-4 gx-lg-5 row-cols-2 row-cols-md-3 row-cols-xl-4 justify-content-center">
            <?php foreach ($productos as $producto): ?>
                <?php 
                    // Evaluamos si el producto está rebajado (si tiene un valor en precio_rebajado)
                    $esta_rebajado = !is_null($producto['precio_rebajado']) && $producto['precio_rebajado'] > 0;
                    // Redondeamos la valoración para pintar las estrellas enteras
                    $estrellas = round($producto['valoracion']);
                ?>
                
                <div class="col mb-5">
                    <div class="card h-100">
                        <!-- Sale badge (Solo se muestra si está rebajado) -->
                        <?php if ($esta_rebajado): ?>
                            <div class="badge bg-dark text-white position-absolute" style="top: 0.5rem; right: 0.5rem">Sale</div>
                        <?php endif; ?>
                        
                        <!-- Product image (De momento estática como tu ejemplo) -->

                        <img 
                            class="card-img-top" 
                            src="imag   enes/<?= htmlspecialchars($producto['referencia']) ?>.jpg" 
                            alt="<?= htmlspecialchars($producto['nombre']) ?>" 
                        />
                        
                        <!-- Product details -->
                        <div class="card-body p-4">
                            <div class="text-center">
                                <!-- Product name -->
                                <h5 class="fw-bolder"><?= htmlspecialchars($producto['nombre']) ?></h5>
                                
                                <!-- Product reviews (Estrellas dinámicas usando Bootstrap Icons) -->
                                <div class="d-flex justify-content-center small text-warning mb-2">
                                    <?php 
                                    // Pintamos tantas estrellas rellenas como marque su valoración (máximo 5)
                                    for ($i = 1; $i <= 5; $i++) {
                                        if ($i <= $estrellas) {
                                            echo '<div class="bi-star-fill"></div>';
                                        } else {
                                            // Opcional: estrella vacía si no llega a la puntuación
                                            echo '<div class="bi-star"></div>'; 
                                        }
                                    }
                                    ?>
                                </div>
                                
                                <!-- Product price -->
                                <?php if ($esta_rebajado): ?>
                                    <!-- Si está rebajado, muestra el precio original tachado y el de oferta -->
                                    <span class="text-muted text-decoration-line-through">$<?= number_format($producto['precio'], 2) ?></span>
                                    $<?= number_format($producto['precio_rebajado'], 2) ?>
                                <?php else: ?>
                                    <!-- Si no está rebajado, solo muestra el precio normal -->
                                    $<?= number_format($producto['precio'], 2) ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Product actions -->
                        <div class="card-footer p-4 pt-0 border-top-0 bg-transparent">
                            <!-- Pasamos el ID del producto por la URL para poder capturarlo en la página de detalles -->
                            <div class="text-center">
                                <a class="btn btn-outline-dark mt-auto" href="index.php?p=informacion-producto&id=<?= $producto['id'] ?>">
                                    Ver Producto
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>