<?php
/*
  PIE de todas las páginas: contacto, enlaces, scripts y el traductor de Google (solo en inglés).
  La incluye index.php después del contenido de la página.
*/
?>
    <!-- Footer Start -->
    <div class="container-fluid bg-dark text-secondary mt-5 pt-5">
        <div class="row px-xl-5 pt-5">
            <div class="col-lg-4 col-md-12 mb-5 pr-3 pr-xl-5">
                <h5 class="text-secondary text-uppercase mb-4">Contacta con nosotros</h5>
                <p class="mb-4">¿Tienes alguna duda sobre un producto o un pedido? Escríbenos y te responderemos lo antes posible.</p>
                <p class="mb-2"><i class="fa fa-map-marker-alt text-primary mr-3"></i>Calle Ejemplo 123, Madrid</p>
                <p class="mb-2"><i class="fa fa-envelope text-primary mr-3"></i>info@tiendatiopepe.es</p>
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
                </div>
            </div>
        </div>
        <div class="row border-top mx-xl-5 py-4" style="border-color: rgba(256, 256, 256, .1) !important;">
            <div class="col-md-6 px-xl-0">
                <p class="mb-md-0 text-center text-md-left text-secondary">
                    &copy; <?= date('Y') ?> <a class="text-primary" href="index.php">Tio Pepe</a>. Todos los derechos reservados.
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

    <!-- Autocompletado del buscador: lista de productos para js/buscador.js -->
    <?php
    $productos_buscador = [];
    foreach (obtener_productos() as $producto_buscador) {
        $productos_buscador[] = [
            'nombre' => $producto_buscador['nombre'],
            'url'    => obtener_url_producto($producto_buscador),
            'imagen' => obtener_imagen_producto($producto_buscador),
            'precio' => formatear_precio(calcular_precio_final($producto_buscador)),
        ];
    }
    ?>
    <script>const PRODUCTOS_BUSCADOR = <?= json_encode($productos_buscador, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;</script>
    <script src="js/buscador.js?v=<?= filemtime('js/buscador.js') ?>"></script>

    <?php if (obtener_idioma_actual() === 'en'): ?>
    <!-- Traducción automática de toda la página con Google Translate.
         Lee la cookie "googtrans" (/es/en) y traduce al cargar, sin diccionario. -->
    <div id="google_translate_element" style="display: none;"></div>
    <script>
        function googleTranslateElementInit() {
            new google.translate.TranslateElement({ pageLanguage: 'es', includedLanguages: 'en', autoDisplay: false }, 'google_translate_element');
        }
    </script>
    <script src="https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
    <?php endif; ?>

</body>

</html>
