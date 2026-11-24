<?php
/*
  TIENDA (index.php?p=tienda) y REBAJADOS (index.php?p=rebajados).
  Filtros por la URL: &categoria=3 (una categoría) y &q=texto (el buscador).
*/
$solo_rebajados = ($pagina === 'rebajados');
$categorias = obtener_categorias();

// Categoría elegida (si en la URL viene una que existe)
$categoria_id = (int) ($_GET['categoria'] ?? 0);
$categoria_actual = null;
foreach ($categorias as $categoria) {
    if ($categoria['id'] == $categoria_id) {
        $categoria_actual = $categoria;
    }
}

$productos = obtener_productos(rebajados: $solo_rebajados, busqueda: $busqueda, categoria: $categoria_actual ? $categoria_id : null);
$total_todos = count(obtener_productos());
$total_rebajados = count(obtener_productos(rebajados: true));

$nombre_seccion = $categoria_actual['nombre'] ?? ($solo_rebajados ? 'Rebajados' : 'Productos');
$anuncio_lateral = obtener_anuncios_aprobados(1)[0] ?? null; // un anuncio al azar para la barra lateral

mostrar_migas_de_pan([
    'Inicio' => 'index.php',
    'Tienda' => 'index.php?p=tienda',
    $nombre_seccion => null,
]);
?>
    <!-- Shop Start -->
    <div class="container-fluid">
        <div class="row px-xl-5">
            <!-- Shop Sidebar Start -->
            <div class="col-lg-3 col-md-4">
                <h5 class="section-title position-relative text-uppercase mb-3"><span class="bg-secondary pr-3">Secciones</span></h5>
                <div class="bg-light p-4 mb-30">
                    <a href="index.php?p=tienda" class="d-flex align-items-center justify-content-between mb-3 text-decoration-none <?= $solo_rebajados || $categoria_actual ? 'text-body' : 'text-primary font-weight-bold' ?>">
                        <span>Todos los productos</span>
                        <span class="badge border font-weight-normal"><?= $total_todos ?></span>
                    </a>
                    <a href="index.php?p=rebajados" class="d-flex align-items-center justify-content-between text-decoration-none <?= $solo_rebajados ? 'text-primary font-weight-bold' : 'text-body' ?>">
                        <span>Rebajados</span>
                        <span class="badge border font-weight-normal"><?= $total_rebajados ?></span>
                    </a>
                </div>

                <h5 class="section-title position-relative text-uppercase mb-3"><span class="bg-secondary pr-3">Categorías</span></h5>
                <div class="bg-light p-4 mb-30">
                    <?php foreach ($categorias as $categoria): ?>
                        <?php $activa = $categoria_actual && (int) $categoria['id'] === $categoria_id; ?>
                        <a href="index.php?p=tienda&amp;categoria=<?= (int) $categoria['id'] ?>" class="d-flex align-items-center justify-content-between mb-3 text-decoration-none <?= $activa ? 'text-primary font-weight-bold' : 'text-body' ?>">
                            <span><?= escapar_html($categoria['nombre']) ?></span>
                            <span class="badge border font-weight-normal"><?= (int) $categoria['total'] ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>

                <h5 class="section-title position-relative text-uppercase mb-3"><span class="bg-secondary pr-3">Buscar</span></h5>
                <div class="bg-light p-4 mb-30">
                    <form action="index.php" method="get">
                        <input type="hidden" name="p" value="<?= escapar_html($solo_rebajados ? 'rebajados' : 'tienda') ?>">
                        <?php if ($categoria_actual): ?>
                            <!-- Busca dentro de la categoría elegida -->
                            <input type="hidden" name="categoria" value="<?= $categoria_id ?>">
                        <?php endif; ?>
                        <div class="input-group">
                            <input type="text" name="q" class="form-control buscador-productos" placeholder="Nombre o referencia" value="<?= escapar_html($busqueda) ?>"
                                   autocomplete="off">
                            <div class="input-group-append">
                                <button type="submit" class="btn btn-primary" title="Buscar"><i class="fa fa-search"></i></button>
                            </div>
                        </div>
                    </form>
                </div>

                <?php if ($anuncio_lateral): ?>
                    <div class="mb-30">
                        <?php mostrar_anuncio($anuncio_lateral); ?>
                    </div>
                <?php endif; ?>
            </div>
            <!-- Shop Sidebar End -->


            <!-- Shop Product Start -->
            <div class="col-lg-9 col-md-8">
                <div class="row pb-3">
                    <div class="col-12 pb-1">
                        <div class="d-flex align-items-center justify-content-between mb-4">
                            <h5 class="m-0">
                                <?= escapar_html($nombre_seccion) ?>
                                <small class="text-muted">(<?= count($productos) ?>)</small>
                            </h5>
                            <?php if ($busqueda !== ''): ?>
                                <div>
                                    Resultados para "<strong><?= escapar_html($busqueda) ?></strong>"
                                    <a href="index.php?p=<?= escapar_html($pagina) ?>" class="ml-2 text-primary" title="Quitar búsqueda"><i class="fa fa-times"></i></a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (!$productos): ?>
                        <div class="col-12">
                            <div class="bg-light p-30 text-center mb-4">
                                <h5 class="mb-3">No hay productos que mostrar</h5>
                                <a class="btn btn-primary" href="index.php?p=tienda">Ver todos los productos</a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php foreach ($productos as $producto): ?>
                        <div class="col-lg-4 col-md-6 col-sm-6 pb-1">
                            <?php mostrar_tarjeta_producto($producto); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <!-- Shop Product End -->
        </div>
    </div>
    <!-- Shop End -->
