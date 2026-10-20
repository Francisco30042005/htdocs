<?php
/*
  Listado de productos (secciones "tienda" y "rebajados").
  Variables disponibles: $conexion, $pagina, $busqueda (index.php)
*/
$solo_rebajados = ($pagina === 'rebajados');
$productos = obtener_productos($conexion, $solo_rebajados, $busqueda);

$total_todos = contar_productos($conexion);
$total_rebajados = contar_productos($conexion, true);

$nombre_seccion = $solo_rebajados ? 'Rebajados' : 'Productos';

migas([
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
                    <a href="index.php?p=tienda" class="d-flex align-items-center justify-content-between mb-3 text-decoration-none <?= $solo_rebajados ? 'text-body' : 'text-primary font-weight-bold' ?>">
                        <span>Todos los productos</span>
                        <span class="badge border font-weight-normal"><?= $total_todos ?></span>
                    </a>
                    <a href="index.php?p=rebajados" class="d-flex align-items-center justify-content-between text-decoration-none <?= $solo_rebajados ? 'text-primary font-weight-bold' : 'text-body' ?>">
                        <span>Rebajados</span>
                        <span class="badge border font-weight-normal"><?= $total_rebajados ?></span>
                    </a>
                </div>

                <h5 class="section-title position-relative text-uppercase mb-3"><span class="bg-secondary pr-3">Buscar</span></h5>
                <div class="bg-light p-4 mb-30">
                    <form action="index.php" method="get">
                        <input type="hidden" name="p" value="<?= e($solo_rebajados ? 'rebajados' : 'tienda') ?>">
                        <div class="input-group">
                            <input type="text" name="q" class="form-control" placeholder="Nombre o referencia" value="<?= e($busqueda) ?>">
                            <div class="input-group-append">
                                <button type="submit" class="btn btn-primary" title="Buscar"><i class="fa fa-search"></i></button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <!-- Shop Sidebar End -->


            <!-- Shop Product Start -->
            <div class="col-lg-9 col-md-8">
                <div class="row pb-3">
                    <div class="col-12 pb-1">
                        <div class="d-flex align-items-center justify-content-between mb-4">
                            <h5 class="m-0">
                                <?= e($nombre_seccion) ?>
                                <small class="text-muted">(<?= count($productos) ?>)</small>
                            </h5>
                            <?php if ($busqueda !== ''): ?>
                                <div>
                                    Resultados para "<strong><?= e($busqueda) ?></strong>"
                                    <a href="index.php?p=<?= e($pagina) ?>" class="ml-2 text-primary" title="Quitar búsqueda"><i class="fa fa-times"></i></a>
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
                            <?php tarjeta_producto($producto); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <!-- Shop Product End -->
        </div>
    </div>
    <!-- Shop End -->
