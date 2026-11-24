<?php
/*
  CABECERA de todas las páginas: <head>, barra superior (modo oscuro, idioma, buscador)
  y menú de navegación. La incluye index.php antes del contenido de la página.
*/
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title><?= escapar_html($titulo) ?> | Tio Pepe</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="tienda, tio pepe" name="keywords">
    <meta content="Tio Pepe - Tienda online" name="description">

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
    <!-- ?v= cambia cada vez que se modifica el archivo, así el navegador no usa una copia antigua -->
    <link href="css/style.css?v=<?= filemtime('css/style.css') ?>" rel="stylesheet">
</head>

<body<?= obtener_tema_actual() === 'oscuro' ? ' class="tema-oscuro"' : '' ?>>
    <!-- Topbar Start -->
    <div class="container-fluid">
        <div class="row bg-secondary py-1 px-xl-5">
            <div class="col-lg-6 d-none d-lg-block">
                <div class="d-inline-flex align-items-center h-100">
                    <a class="text-body mr-3" href="index.php?p=conocenos">Conócenos</a>
                </div>
            </div>
            <div class="col-lg-6 text-center text-lg-right">
                <!-- Preferencias del visitante: modo claro/oscuro e idioma (cookies de sesión) -->
                <div class="d-inline-flex align-items-center">
                    <?php if (obtener_tema_actual() === 'oscuro'): ?>
                        <a class="text-body mr-3" href="<?= escapar_html(obtener_url_con_preferencia('tema', 'claro')) ?>"><i class="fa fa-sun mr-1"></i>Modo claro</a>
                    <?php else: ?>
                        <a class="text-body mr-3" href="<?= escapar_html(obtener_url_con_preferencia('tema', 'oscuro')) ?>"><i class="fa fa-moon mr-1"></i>Modo oscuro</a>
                    <?php endif; ?>
                    <!-- "notranslate": Google no traduce los botones ES / EN -->
                    <div class="btn-group notranslate" role="group" aria-label="Cambiar idioma">
                        <?php foreach (['es', 'en'] as $codigo_idioma): ?>
                            <a class="btn btn-sm py-0 <?= obtener_idioma_actual() === $codigo_idioma ? 'btn-primary' : 'btn-outline-dark' ?>" href="<?= escapar_html(obtener_url_con_preferencia('idioma', $codigo_idioma)) ?>"><?= strtoupper($codigo_idioma) ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="d-inline-flex align-items-center d-block d-lg-none">
                    <?php mostrar_boton_usuario('text-dark'); ?>
                    <a href="index.php?p=favoritos" class="btn px-0 ml-3" title="Mis favoritos">
                        <i class="fas fa-heart text-dark"></i>
                        <span class="badge text-dark border border-dark rounded-circle" style="padding-bottom: 2px;"><?= count(obtener_ids_favoritos()) ?></span>
                    </a>
                    <a href="index.php?p=carrito" class="btn px-0 ml-3" title="Carrito">
                        <i class="fas fa-shopping-cart text-dark"></i>
                        <span class="badge text-dark border border-dark rounded-circle" style="padding-bottom: 2px;"><?= contar_unidades_carrito() ?></span>
                    </a>
                </div>
            </div>
        </div>
        <div class="row align-items-center bg-light py-3 px-xl-5 d-none d-lg-flex">
            <div class="col-lg-4">
                <a href="index.php" class="text-decoration-none">
                    <span class="notranslate h1 text-uppercase text-primary bg-dark px-2">TIO</span>
                    <span class="notranslate h1 text-uppercase text-dark bg-primary px-2 ml-n1">PEPE</span>
                </a>
            </div>
            <div class="col-lg-4 col-6 text-left">
                <!-- Buscador: envía a la tienda con ?q= -->
                <form action="index.php" method="get">
                    <input type="hidden" name="p" value="tienda">
                    <div class="input-group">
                        <input type="text" name="q" class="form-control buscador-productos" placeholder="Buscar productos" value="<?= escapar_html($busqueda) ?>"
                               autocomplete="off">
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
                        <?php foreach (obtener_categorias() as $categoria_menu): ?>
                            <a href="index.php?p=tienda&amp;categoria=<?= (int) $categoria_menu['id'] ?>" class="nav-item nav-link"><?= escapar_html($categoria_menu['nombre']) ?></a>
                        <?php endforeach; ?>
                    </div>
                </nav>
            </div>
            <div class="col-lg-9">
                <nav class="navbar navbar-expand-lg bg-dark navbar-dark py-3 py-lg-0 px-0">
                    <a href="index.php" class="text-decoration-none d-block d-lg-none">
                        <span class="notranslate h1 text-uppercase text-dark bg-light px-2">Tio</span>
                        <span class="notranslate h1 text-uppercase text-light bg-primary px-2 ml-n1">Pepe</span>
                    </a>
                    <button type="button" class="navbar-toggler" data-toggle="collapse" data-target="#navbarCollapse" aria-label="Abrir menú de navegación">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <div class="collapse navbar-collapse justify-content-between" id="navbarCollapse">
                        <div class="navbar-nav mr-auto py-0">
                            <a href="index.php" class="nav-item nav-link<?= obtener_clase_activo($pagina === '') ?>">Inicio</a>
                            <a href="index.php?p=conocenos" class="nav-item nav-link<?= obtener_clase_activo($pagina === 'conocenos') ?>">Conócenos</a>
                            <div class="nav-item dropdown">
                                <a href="#" class="nav-link dropdown-toggle<?= obtener_clase_activo(in_array($pagina, ['tienda', 'rebajados', 'informacion-producto'], true)) ?>" data-toggle="dropdown">Tienda<i class="fa fa-angle-down mt-1 ml-1"></i></a>
                                <div class="dropdown-menu bg-primary rounded-0 border-0 m-0">
                                    <a href="index.php?p=tienda" class="dropdown-item">Productos</a>
                                    <div class="dropdown-divider"></div>
                                    <a href="index.php?p=rebajados" class="dropdown-item">Rebajados</a>
                                </div>
                            </div>
                            <?php if (es_admin()): ?>
                                <!-- El panel solo aparece en el menú para los administradores -->
                                <a href="index.php?p=admin" class="nav-item nav-link<?= obtener_clase_activo($pagina === 'admin') ?>">Administración</a>
                            <?php endif; ?>
                        </div>
                        <div class="navbar-nav ml-auto py-0 d-none d-lg-block">
                            <?php mostrar_boton_usuario('text-primary'); ?>
                            <a href="index.php?p=favoritos" class="btn px-0 ml-3" title="Mis favoritos">
                                <i class="fas fa-heart text-primary"></i>
                                <span class="badge text-secondary border border-secondary rounded-circle" style="padding-bottom: 2px;"><?= count(obtener_ids_favoritos()) ?></span>
                            </a>
                            <a href="index.php?p=carrito" class="btn px-0 ml-3" title="Carrito">
                                <i class="fas fa-shopping-cart text-primary"></i>
                                <span class="badge text-secondary border border-secondary rounded-circle" style="padding-bottom: 2px;"><?= contar_unidades_carrito() ?></span>
                            </a>
                        </div>
                    </div>
                </nav>
            </div>
        </div>
    </div>
    <!-- Navbar End -->
