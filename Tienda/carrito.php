<?php
/*
  Carrito de la compra (sección "carrito").
  Muestra los productos guardados en la sesión ($_SESSION['carrito']).
  Variables disponibles: $conexion, $pagina (index.php)
*/
$lineas = obtener_lineas_carrito($conexion);
$subtotal = array_sum(array_column($lineas, 'total'));
$porcentaje = obtener_descuento_total($conexion);
$descuento = $subtotal * $porcentaje / 100;
$envio = $lineas ? GASTOS_ENVIO : 0;

// Resultado de "Finalizar compra" (se muestra una sola vez)
$mensaje_carrito = $_SESSION['mensaje_carrito'] ?? null;
unset($_SESSION['mensaje_carrito']);

mostrar_migas_de_pan([
    'Inicio' => 'index.php',
    'Tienda' => 'index.php?p=tienda',
    'Carrito' => null,
]);
?>
    <!-- Carrito Start -->
    <div class="container-fluid">
        <?php if ($mensaje_carrito): ?>
        <div class="row px-xl-5">
            <div class="col">
                <div class="alert alert-<?= $mensaje_carrito['ok'] ? 'success' : 'danger' ?>"><?= escapar_html($mensaje_carrito['texto']) ?></div>
            </div>
        </div>
        <?php endif; ?>
        <?php if (!$lineas): ?>
        <div class="row px-xl-5">
            <div class="col">
                <div class="bg-light p-30 mb-5 text-center">
                    <h1 class="display-4 text-primary"><i class="fa fa-shopping-cart"></i></h1>
                    <h3 class="mb-3">Tu carrito está vacío</h3>
                    <p class="mb-4">Añade productos desde la tienda y aparecerán aquí.</p>
                    <a class="btn btn-primary py-2 px-4" href="index.php?p=tienda">Ir a la tienda</a>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="row px-xl-5">
            <div class="col-lg-8 table-responsive mb-5">
                <form id="form-carrito" action="index.php?p=carrito" method="post">
                    <input type="hidden" name="accion" value="actualizar">
                    <!-- Botón por defecto: al pulsar Intro en una cantidad se actualiza el carrito -->
                    <button type="submit" style="position: absolute; left: -9999px;" tabindex="-1" aria-hidden="true"></button>
                    <table class="table table-light table-borderless table-hover text-center mb-0">
                        <thead class="thead-dark">
                            <tr>
                                <th>Productos</th>
                                <th>Precio</th>
                                <th>Cantidad</th>
                                <th>Total</th>
                                <th>Quitar</th>
                            </tr>
                        </thead>
                        <tbody class="align-middle">
                            <?php foreach ($lineas as $linea): ?>
                                <?php $producto = $linea['producto']; $id = (int) $producto['id']; ?>
                            <tr>
                                <td class="align-middle text-left">
                                    <a href="<?= escapar_html(obtener_url_producto($producto)) ?>" class="text-dark">
                                        <img src="<?= escapar_html(obtener_imagen_producto($producto, '100x100')) ?>" alt="<?= escapar_html($producto['nombre']) ?>" style="width: 50px;">
                                        <?= escapar_html($producto['nombre']) ?>
                                    </a>
                                </td>
                                <td class="align-middle"><?= formatear_precio($linea['precio']) ?></td>
                                <td class="align-middle">
                                    <div class="input-group quantity mx-auto" style="width: 100px;">
                                        <div class="input-group-btn">
                                            <button class="btn btn-sm btn-primary btn-minus" type="button">
                                            <i class="fa fa-minus"></i>
                                            </button>
                                        </div>
                                        <input type="text" name="cantidad[<?= $id ?>]" class="form-control form-control-sm bg-secondary border-0 text-center" value="<?= $linea['cantidad'] ?>">
                                        <div class="input-group-btn">
                                            <button class="btn btn-sm btn-primary btn-plus" type="button">
                                                <i class="fa fa-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                </td>
                                <td class="align-middle"><?= formatear_precio($linea['total']) ?></td>
                                <td class="align-middle"><button type="submit" name="quitar" value="<?= $id ?>" class="btn btn-sm btn-danger" title="Quitar del carrito"><i class="fa fa-times"></i></button></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div class="d-flex justify-content-between mt-3">
                        <button type="submit" class="btn btn-primary"><i class="fa fa-sync-alt mr-1"></i> Actualizar carrito</button>
                        <button type="submit" name="accion" value="vaciar" class="btn btn-outline-dark"><i class="fa fa-trash mr-1"></i> Vaciar carrito</button>
                    </div>
                </form>
            </div>
            <div class="col-lg-4">
                <h5 class="section-title position-relative text-uppercase mb-3"><span class="bg-secondary pr-3">Resumen del carrito</span></h5>
                <div class="bg-light p-30 mb-5">
                    <!-- Cupones aplicados (cada uno con su botón Quitar) -->
                    <?php foreach ($_SESSION['cupones'] ?? [] as $codigo): ?>
                        <form action="index.php?p=carrito" method="post" class="input-group mb-2">
                            <input type="hidden" name="codigo" value="<?= escapar_html($codigo) ?>">
                            <span class="form-control">Cupón <b><?= escapar_html($codigo) ?></b> (−<?= obtener_descuento_cupon($conexion, $codigo) ?> %)</span>
                            <div class="input-group-append">
                                <button type="submit" name="accion" value="quitar_cupon" class="btn btn-outline-dark">Quitar</button>
                            </div>
                        </form>
                    <?php endforeach; ?>
                    <!-- Añadir otro cupón -->
                    <form action="index.php?p=carrito" method="post" class="input-group mb-3">
                        <input type="text" name="codigo" class="form-control" placeholder="Código de descuento">
                        <div class="input-group-append">
                            <button type="submit" name="accion" value="aplicar_cupon" class="btn btn-primary">Aplicar</button>
                        </div>
                    </form>
                    <div class="border-bottom pb-2">
                        <div class="d-flex justify-content-between mb-3">
                            <h6>Subtotal</h6>
                            <h6><?= formatear_precio($subtotal) ?></h6>
                        </div>
                        <?php if ($descuento > 0): ?>
                        <div class="d-flex justify-content-between mb-3">
                            <h6>Descuento (<?= $porcentaje ?> %)</h6>
                            <h6>−<?= formatear_precio($descuento) ?></h6>
                        </div>
                        <?php endif; ?>
                        <div class="d-flex justify-content-between">
                            <h6 class="font-weight-medium">Envío</h6>
                            <h6 class="font-weight-medium"><?= formatear_precio($envio) ?></h6>
                        </div>
                    </div>
                    <div class="pt-2">
                        <div class="d-flex justify-content-between mt-2">
                            <h5>Total</h5>
                            <h5><?= formatear_precio($subtotal - $descuento + $envio) ?></h5>
                        </div>
                        <?php // Va unido al formulario del carrito (form="form-carrito") para guardar también las cantidades cambiadas ?>
                        <?php if (tiene_sesion_iniciada()): ?>
                            <button class="btn btn-block btn-primary font-weight-bold my-3 py-3" type="submit" form="form-carrito" name="accion" value="finalizar">Finalizar compra</button>
                        <?php else: ?>
                            <button class="btn btn-block btn-primary font-weight-bold mt-3 mb-2 py-3" type="submit" form="form-carrito" name="accion" value="finalizar">
                                <i class="fa fa-sign-in-alt mr-1"></i> Inicia sesión para comprar
                            </button>
                            <p class="small text-center mb-0">Tu carrito se guardará mientras inicias sesión.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <!-- Carrito End -->
