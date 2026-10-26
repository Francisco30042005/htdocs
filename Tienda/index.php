<?php
/*
  Version   : 2.0
  Autor     : Gabriel S.
  Fecha     : 20 Octubre 2026
  Plantilla : MultiShop (HTML Codex) adaptada a Start Shop
*/
$pagina = $_GET['p'] ?? null;
$busqueda = trim($_GET['q'] ?? '');

// Conexión con la bbdd y funciones comunes
require_once 'conexion.php';
require_once 'funciones.php';

// Título de la pestaña según la sección
$titulos = [
    null                   => 'Inicio',
    'conocenos'            => 'Conócenos',
    'tienda'               => 'Tienda',
    'rebajados'            => 'Rebajados',
    'informacion-producto' => 'Producto',
    'solicitud-recibida'   => 'Mensaje enviado',
    'carrito'              => 'Carrito',
];
$titulo = $titulos[$pagina] ?? 'Página no encontrada';

// Si la sección no existe, avisamos al navegador con un error 404
if ($pagina !== null && !isset($titulos[$pagina])) {
    http_response_code(404);
}

// Clase "active" del menú
function activo(bool $condicion): string
{
    return $condicion ? ' active' : '';
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title><?= e($titulo) ?> | Start Shop</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="tienda, start shop" name="keywords">
    <meta content="Start Shop - Tienda online" name="description">

    <!-- Favicon -->
    <link href="img/favicon.ico" rel="icon">

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">

    <!-- Libraries Stylesheet -->
    <link href="lib/animate/animate.min.css" rel="stylesheet">
    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">

    <!-- Customized Bootstrap Stylesheet -->
    <link href="css/style.css" rel="stylesheet">
</head>

<body>
    <!-- Topbar Start -->
    <div class="container-fluid">
        <div class="row bg-secondary py-1 px-xl-5">
            <div class="col-lg-6 d-none d-lg-block">
                <div class="d-inline-flex align-items-center h-100">
                    <a class="text-body mr-3" href="index.php?p=conocenos">Conócenos</a>
                    <a class="text-body mr-3" href="index.php?p=conocenos">Contacto</a>
                </div>
            </div>
            <div class="col-lg-6 text-center text-lg-right">
                <div class="d-inline-flex align-items-center d-block d-lg-none">
                    <a href="index.php?p=carrito" class="btn px-0 ml-2" title="Carrito">
                        <i class="fas fa-shopping-cart text-dark"></i>
                        <span class="badge text-dark border border-dark rounded-circle" style="padding-bottom: 2px;">0</span>
                    </a>
                </div>
            </div>
        </div>
        <div class="row align-items-center bg-light py-3 px-xl-5 d-none d-lg-flex">
            <div class="col-lg-4">
                <a href="index.php" class="text-decoration-none">
                    <span class="h1 text-uppercase text-primary bg-dark px-2">Start</span>
                    <span class="h1 text-uppercase text-dark bg-primary px-2 ml-n1">Shop</span>
                </a>
            </div>
            <div class="col-lg-4 col-6 text-left">
                <!-- Buscador: envía a la tienda con ?q= -->
                <form action="index.php" method="get">
                    <input type="hidden" name="p" value="tienda">
                    <div class="input-group">
                        <input type="text" name="q" class="form-control" placeholder="Buscar productos" value="<?= e($busqueda) ?>">
                        <div class="input-group-append">
                            <button type="submit" class="input-group-text bg-transparent text-primary" title="Buscar">
                                <i class="fa fa-search"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="col-lg-4 col-6 text-right">
                <p class="m-0">Atención al cliente</p>
                <h5 class="m-0">+012 345 6789</h5>
            </div>
        </div>
    </div>
    <!-- Topbar End -->


    <!-- Navbar Start -->
    <div class="container-fluid bg-dark mb-30">
        <div class="row px-xl-5">
            <div class="col-lg-3 d-none d-lg-block">
                <a class="btn d-flex align-items-center justify-content-between bg-primary w-100" data-toggle="collapse" href="#navbar-vertical" style="height: 65px; padding: 0 30px;">
                    <h6 class="text-dark m-0"><i class="fa fa-bars mr-2"></i>Tienda</h6>
                    <i class="fa fa-angle-down text-dark"></i>
                </a>
                <nav class="collapse position-absolute navbar navbar-vertical navbar-light align-items-start p-0 bg-light" id="navbar-vertical" style="width: calc(100% - 30px); z-index: 999;">
                    <div class="navbar-nav w-100">
                        <a href="index.php?p=tienda" class="nav-item nav-link">Productos</a>
                        <a href="index.php?p=rebajados" class="nav-item nav-link">Rebajados</a>
                    </div>
                </nav>
            </div>
            <div class="col-lg-9">
                <nav class="navbar navbar-expand-lg bg-dark navbar-dark py-3 py-lg-0 px-0">
                    <a href="index.php" class="text-decoration-none d-block d-lg-none">
                        <span class="h1 text-uppercase text-dark bg-light px-2">Start</span>
                        <span class="h1 text-uppercase text-light bg-primary px-2 ml-n1">Shop</span>
                    </a>
                    <button type="button" class="navbar-toggler" data-toggle="collapse" data-target="#navbarCollapse" aria-label="Abrir menú de navegación">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <div class="collapse navbar-collapse justify-content-between" id="navbarCollapse">
                        <div class="navbar-nav mr-auto py-0">
                            <a href="index.php" class="nav-item nav-link<?= activo($pagina === null) ?>">Inicio</a>
                            <a href="index.php?p=conocenos" class="nav-item nav-link<?= activo($pagina === 'conocenos') ?>">Conócenos</a>
                            <div class="nav-item dropdown">
                                <a href="#" class="nav-link dropdown-toggle<?= activo(in_array($pagina, ['tienda', 'rebajados', 'informacion-producto', 'carrito'], true)) ?>" data-toggle="dropdown">Tienda <i class="fa fa-angle-down mt-1"></i></a>
                                <div class="dropdown-menu bg-primary rounded-0 border-0 m-0">
                                    <a href="index.php?p=tienda" class="dropdown-item">Productos</a>
                                    <div class="dropdown-divider"></div>
                                    <a href="index.php?p=rebajados" class="dropdown-item">Rebajados</a>
                                    <div class="dropdown-divider"></div>
                                    <a href="index.php?p=carrito" class="dropdown-item">Carrito</a>
                                </div>
                            </div>
                        </div>
                        <div class="navbar-nav ml-auto py-0 d-none d-lg-block">
                            <a href="index.php?p=carrito" class="btn px-0 ml-3" title="Carrito">
                                <i class="fas fa-shopping-cart text-primary"></i>
                                <span class="badge text-secondary border border-secondary rounded-circle" style="padding-bottom: 2px;">0</span>
                            </a>
                        </div>
                    </div>
                </nav>
            </div>
        </div>
    </div>
    <!-- Navbar End -->


    <!-- Contenidos propios de la pagina -->
    <?php
    switch ($pagina) {
        case null:
            include 'inicio.php';
            break;
        case 'conocenos':
            include 'contacus.php';
            break;
        case 'tienda':
        case 'rebajados':
            include 'products-section.php';
            break;
        case 'informacion-producto':
            include 'product-item.php';
            break;
        case 'solicitud-recibida':
            include 'solicitud-recibida.html';
            break;
        case 'carrito':
            include 'carrito.php';
            break;
        default:
            include 'error-404.html';
    }
    ?>


    <!-- Footer Start -->
    <div class="container-fluid bg-dark text-secondary mt-5 pt-5">
        <div class="row px-xl-5 pt-5">
            <div class="col-lg-4 col-md-12 mb-5 pr-3 pr-xl-5">
                <h5 class="text-secondary text-uppercase mb-4">Contacta con nosotros</h5>
                <p class="mb-4">¿Tienes alguna duda sobre un producto o un pedido? Escríbenos y te responderemos lo antes posible.</p>
                <p class="mb-2"><i class="fa fa-map-marker-alt text-primary mr-3"></i>Calle Ejemplo 123, Madrid</p>
                <p class="mb-2"><i class="fa fa-envelope text-primary mr-3"></i>info@startshop.com</p>
                <p class="mb-0"><i class="fa fa-phone-alt text-primary mr-3"></i>+012 345 67890</p>
            </div>
            <div class="col-lg-8 col-md-12">
                <div class="row">
                    <div class="col-md-6 mb-5">
                        <h5 class="text-secondary text-uppercase mb-4">Tienda</h5>
                        <div class="d-flex flex-column justify-content-start">
                            <a class="text-secondary mb-2" href="index.php"><i class="fa fa-angle-right mr-2"></i>Inicio</a>
                            <a class="text-secondary mb-2" href="index.php?p=tienda"><i class="fa fa-angle-right mr-2"></i>Productos</a>
                            <a class="text-secondary mb-2" href="index.php?p=rebajados"><i class="fa fa-angle-right mr-2"></i>Rebajados</a>
                            <a class="text-secondary" href="index.php?p=conocenos"><i class="fa fa-angle-right mr-2"></i>Conócenos</a>
                        </div>
                    </div>
                    <div class="col-md-6 mb-5">
                        <h5 class="text-secondary text-uppercase mb-4">Síguenos</h5>
                        <div class="d-flex">
                            <a class="btn btn-primary btn-square mr-2" href="#"><i class="fab fa-twitter"></i></a>
                            <a class="btn btn-primary btn-square mr-2" href="#"><i class="fab fa-facebook-f"></i></a>
                            <a class="btn btn-primary btn-square mr-2" href="#"><i class="fab fa-linkedin-in"></i></a>
                            <a class="btn btn-primary btn-square" href="#"><i class="fab fa-instagram"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row border-top mx-xl-5 py-4" style="border-color: rgba(256, 256, 256, .1) !important;">
            <div class="col-md-6 px-xl-0">
                <p class="mb-md-0 text-center text-md-left text-secondary">
                    &copy; <?= date('Y') ?> <a class="text-primary" href="index.php">Start Shop</a>. Todos los derechos reservados.
                    Diseño de <a class="text-primary" href="https://htmlcodex.com">HTML Codex</a>
                    <br>Distribuido por: <a href="https://themewagon.com" target="_blank">ThemeWagon</a>
                </p>
            </div>
            <div class="col-md-6 px-xl-0 text-center text-md-right">
                <img class="img-fluid" src="img/payments.png" alt="Métodos de pago">
            </div>
        </div>
    </div>
    <!-- Footer End -->


    <!-- Back to Top -->
    <a href="#" class="btn btn-primary back-to-top"><i class="fa fa-angle-double-up"></i></a>


    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.bundle.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>

    <!-- Template Javascript -->
    <script src="js/main.js"></script>

    <?php
    // Cerramos la conexión al final
    $conexion->close();
    ?>
</body>

</html>
