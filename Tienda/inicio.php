<?php
/*
  Página de inicio: carrusel de la plantilla + productos de la base de datos.
  Variables disponibles: $conexion (conexion.php)
*/
$ultimos_productos = obtener_productos($conexion, false, null, 8);
$productos_rebajados = obtener_productos($conexion, true, null, 8);
?>
    <!-- Carousel Start -->
    <div class="container-fluid mb-3">
        <div class="row px-xl-5">
            <div class="col-lg-8">
                <div id="header-carousel" class="carousel slide carousel-fade mb-30 mb-lg-0" data-ride="carousel">
                    <ol class="carousel-indicators">
                        <li data-target="#header-carousel" data-slide-to="0" class="active"></li>
                        <li data-target="#header-carousel" data-slide-to="1"></li>
                        <li data-target="#header-carousel" data-slide-to="2"></li>
                    </ol>
                    <div class="carousel-inner">
                        <div class="carousel-item position-relative active" style="height: 430px;">
                            <img class="position-absolute w-100 h-100" src="img/carousel-1.jpg" style="object-fit: cover;" alt="">
                            <div class="carousel-caption d-flex flex-column align-items-center justify-content-center">
                                <div class="p-3" style="max-width: 700px;">
                                    <h1 class="display-4 text-white mb-3 animate__animated animate__fadeInDown">Bienvenido a Start Shop</h1>
                                    <p class="mx-md-5 px-5 animate__animated animate__bounceIn">Descubre todo nuestro catálogo de productos.</p>
                                    <a class="btn btn-outline-light py-2 px-4 mt-3 animate__animated animate__fadeInUp" href="index.php?p=tienda">Ver productos</a>
                                </div>
                            </div>
                        </div>
                        <div class="carousel-item position-relative" style="height: 430px;">
                            <img class="position-absolute w-100 h-100" src="img/carousel-2.jpg" style="object-fit: cover;" alt="">
                            <div class="carousel-caption d-flex flex-column align-items-center justify-content-center">
                                <div class="p-3" style="max-width: 700px;">
                                    <h1 class="display-4 text-white mb-3 animate__animated animate__fadeInDown">Rebajas</h1>
                                    <p class="mx-md-5 px-5 animate__animated animate__bounceIn">Aprovecha nuestros productos con precio rebajado.</p>
                                    <a class="btn btn-outline-light py-2 px-4 mt-3 animate__animated animate__fadeInUp" href="index.php?p=rebajados">Ver rebajados</a>
                                </div>
                            </div>
                        </div>
                        <div class="carousel-item position-relative" style="height: 430px;">
                            <img class="position-absolute w-100 h-100" src="img/carousel-3.jpg" style="object-fit: cover;" alt="">
                            <div class="carousel-caption d-flex flex-column align-items-center justify-content-center">
                                <div class="p-3" style="max-width: 700px;">
                                    <h1 class="display-4 text-white mb-3 animate__animated animate__fadeInDown">¿Hablamos?</h1>
                                    <p class="mx-md-5 px-5 animate__animated animate__bounceIn">Resolvemos todas tus dudas antes de comprar.</p>
                                    <a class="btn btn-outline-light py-2 px-4 mt-3 animate__animated animate__fadeInUp" href="index.php?p=conocenos">Contacta con nosotros</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="product-offer mb-30" style="height: 200px;">
                    <img class="img-fluid" src="img/offer-1.jpg" alt="">
                    <div class="offer-text">
                        <h6 class="text-white text-uppercase">Precios rebajados</h6>
                        <h3 class="text-white mb-3">Ofertas especiales</h3>
                        <a href="index.php?p=rebajados" class="btn btn-primary">Ver ofertas</a>
                    </div>
                </div>
                <div class="product-offer mb-30" style="height: 200px;">
                    <img class="img-fluid" src="img/offer-2.jpg" alt="">
                    <div class="offer-text">
                        <h6 class="text-white text-uppercase">Todo el catálogo</h6>
                        <h3 class="text-white mb-3">Nuestra tienda</h3>
                        <a href="index.php?p=tienda" class="btn btn-primary">Comprar</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Carousel End -->


    <!-- Featured Start -->
    <div class="container-fluid pt-5">
        <div class="row px-xl-5 pb-3">
            <div class="col-lg-3 col-md-6 col-sm-12 pb-1">
                <div class="d-flex align-items-center bg-light mb-4" style="padding: 30px;">
                    <h1 class="fa fa-check text-primary m-0 mr-3"></h1>
                    <h5 class="font-weight-semi-bold m-0">Productos de calidad</h5>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-12 pb-1">
                <div class="d-flex align-items-center bg-light mb-4" style="padding: 30px;">
                    <h1 class="fa fa-shipping-fast text-primary m-0 mr-2"></h1>
                    <h5 class="font-weight-semi-bold m-0">Envío gratis</h5>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-12 pb-1">
                <div class="d-flex align-items-center bg-light mb-4" style="padding: 30px;">
                    <h1 class="fas fa-exchange-alt text-primary m-0 mr-3"></h1>
                    <h5 class="font-weight-semi-bold m-0">Devolución 14 días</h5>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-12 pb-1">
                <div class="d-flex align-items-center bg-light mb-4" style="padding: 30px;">
                    <h1 class="fa fa-phone-volume text-primary m-0 mr-3"></h1>
                    <h5 class="font-weight-semi-bold m-0">Atención 24/7</h5>
                </div>
            </div>
        </div>
    </div>
    <!-- Featured End -->


    <!-- Products Start -->
    <div class="container-fluid pt-5 pb-3">
        <h2 class="section-title position-relative text-uppercase mx-xl-5 mb-4"><span class="bg-secondary pr-3">Últimos productos</span></h2>
        <div class="row px-xl-5">
            <?php if (!$ultimos_productos): ?>
                <div class="col-12"><p class="text-muted">Todavía no hay productos en la tienda.</p></div>
            <?php endif; ?>
            <?php foreach ($ultimos_productos as $producto): ?>
                <div class="col-lg-3 col-md-4 col-sm-6 pb-1">
                    <?php tarjeta_producto($producto); ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <!-- Products End -->


    <?php if ($productos_rebajados): ?>
    <!-- Products Start -->
    <div class="container-fluid pt-5 pb-3">
        <h2 class="section-title position-relative text-uppercase mx-xl-5 mb-4"><span class="bg-secondary pr-3">Rebajados</span></h2>
        <div class="row px-xl-5">
            <?php foreach ($productos_rebajados as $producto): ?>
                <div class="col-lg-3 col-md-4 col-sm-6 pb-1">
                    <?php tarjeta_producto($producto); ?>
                </div>
            <?php endforeach; ?>
            <div class="col-12 text-center">
                <a href="index.php?p=rebajados" class="btn btn-primary py-2 px-4">Ver todos los rebajados</a>
            </div>
        </div>
    </div>
    <!-- Products End -->
    <?php endif; ?>
