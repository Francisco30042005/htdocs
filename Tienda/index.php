<!--
  Version   : 1.0
  Autor     : Gabriel S.
  Fecha     : 05 Octubre 2026
-->
<?php
  $pagina = $_GET['p'] ?? null;
  // Conexión con la bbdd
  require_once 'conexion.php';
?>

<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="" />
        <meta name="author" content="" />
        <title>Mi tienda</title>
        <!-- Favicon-->
        <link rel="icon" type="image/x-icon" href="assets/favicon.ico" />
        <!-- Bootstrap icons-->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css" rel="stylesheet" />
        <!-- Core theme CSS (includes Bootstrap)-->
        <link href="css/styles.css" rel="stylesheet" />
    </head>
    <body>
        <!-- Navegación -->
        <nav class="navbar navbar-expand-lg navbar-light bg-light">
            <div class="container px-4 px-lg-5">

                <a class="navbar-brand" href="index.php">Mi tienda</a>

                <button class="navbar-toggler"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#navbarSupportedContent"
                        aria-controls="navbarSupportedContent"
                        aria-expanded="false"
                        aria-label="Abrir menú de navegación">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">

                    <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-4">

                        <li class="nav-item">
                            <a class="nav-link <?php if ($pagina === null) echo 'active'; ?>"
                            href="index.php">
                                Inicio
                            </a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link"
                            href="index.php?p=informacion">
                                Información
                            </a>
                        </li>

                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle
                            <?php if (in_array($pagina, ['tienda', 'rebajados'])) echo 'active'; ?>"
                            id="navbarDropdown"
                            href="#"
                            role="button"
                            data-bs-toggle="dropdown"
                            aria-expanded="false">
                                Tienda
                            </a>

                            <ul class="dropdown-menu" aria-labelledby="navbarDropdown">

                                <li>
                                    <a class="dropdown-item"
                                    href="index.php?p=tienda">
                                        Productos
                                    </a>
                                </li>

                                <li>
                                    <hr class="dropdown-divider">
                                </li>

                                <li>
                                    <a class="dropdown-item"
                                    href="index.php?p=rebajados">
                                        Rebajados
                                    </a>
                                </li>

                            </ul>
                        </li>

                    </ul>

                    <form class="d-flex">
                        <button class="btn btn-outline-dark" type="button">
                            <i class="bi-cart-fill me-1"></i>
                            Carrito
                            <span class="badge bg-dark text-white ms-1 rounded-pill">0</span>
                        </button>
                    </form>

                </div>
            </div>
        </nav>

        <!-- Contenidos propios de la pagina -->
        <?php
        switch ($pagina) {
            case null:
                include 'product-item.html';
                include 'products-section.php';
                break;
            case "informacion-producto":
                include 'product-item.html';
                break;
            case "tienda":
                include 'products-section.php';
                break;
            case "rebajados":
                include 'products-section.php';
                break;
            case "informacion-producto":
                include 'product-item.html';
                break;
            default:
                //include 'error-404.html';
            }
        ?>


        <!-- Footer-->
        <footer class="py-5 bg-dark">
            <div class="container"><p class="m-0 text-center text-white">Copyright &copy; Your Website 2023</p></div>
        </footer>
        <!-- Bootstrap core JS-->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
        <!-- Core theme JS-->
        <script src="js/scripts.js"></script>

        <?php
            // Cerramos la conexión al final
            $conexion->close();
        ?>
    </body>
</html>
