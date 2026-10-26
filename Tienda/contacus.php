<?php
/*
  Conócenos / Contacta con nosotros.
  El formulario se envía a generar-mail.php (mismos campos que la versión anterior).
*/
$error_envio = isset($_GET['error']);

cookies([
    'Inicio' => 'index.php',
    'Conócenos' => null,
]);
?>
    <!-- Contact Start -->
    <div class="container-fluid">
        <h2 class="section-title position-relative text-uppercase mx-xl-5 mb-4"><span class="bg-secondary pr-3">Contacta con nosotros</span></h2>
        <div class="row px-xl-5">
            <div class="col-lg-7 mb-5">
                <div class="contact-form bg-light p-30">
                    <?php if ($error_envio): ?>
                        <div class="alert alert-danger">
                            Revisa los datos: todos los campos con * son obligatorios y el correo electrónico debe ser válido.
                        </div>
                    <?php endif; ?>
                    <form action="generar-mail.php" method="post">
                        <div class="form-row">
                            <!-- input NOMBRE -->
                            <div class="form-group col-md-6">
                                <input type="text" class="form-control" name="nombre" maxlength="40" placeholder="Nombre *" required>
                            </div>
                            <!-- input APELLIDOS -->
                            <div class="form-group col-md-6">
                                <input type="text" class="form-control" name="apellidos" maxlength="80" placeholder="Apellidos *" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <!-- input TELÉFONO -->
                            <div class="form-group col-md-6">
                                <input type="tel" class="form-control" name="phone" maxlength="30" placeholder="Teléfono *" required>
                            </div>
                            <!-- input CORREO ELECTRÓNICO -->
                            <div class="form-group col-md-6">
                                <input type="email" class="form-control" name="email" maxlength="100" placeholder="Correo electrónico *" required>
                            </div>
                        </div>
                        <!-- textarea COMENTARIOS -->
                        <div class="form-group">
                            <textarea class="form-control" name="comentarios" rows="8" placeholder="Indica aquí tus comentarios."></textarea>
                        </div>
                        <!-- botones de ENVIAR & RESTABLECER -->
                        <div>
                            <button class="btn btn-primary py-2 px-4" type="submit" title="Enviar">
                                <i class="fa fa-paper-plane mr-1"></i> Enviar
                            </button>
                            <button class="btn btn-outline-dark py-2 px-4 ml-2" type="reset" title="Restablecer">
                                <i class="fa fa-eraser mr-1"></i> Restablecer
                            </button>
                        </div>
                        <!-- Letra pequeña -->
                        <p class="small mt-3 mb-0">
                            Al enviar tu consulta, aceptas nuestras Condiciones de uso.
                            <a href="index.php" class="text-primary">¿No lo tienes claro? <b>Volver a Inicio</b></a>.
                        </p>
                    </form>
                </div>
            </div>
            <div class="col-lg-5 mb-5">
                <div class="bg-light p-30 mb-30">
                    <iframe style="width: 100%; height: 250px; border: 0;"
                    src="https://www.google.com/maps?q=Madrid&output=embed"
                    allowfullscreen="" aria-hidden="false" tabindex="0" title="Mapa"></iframe>
                </div>
                <div class="bg-light p-30 mb-3">
                    <h5 class="mb-3">START SHOP</h5>
                    <p class="mb-2"><i class="fa fa-map-marker-alt text-primary mr-3"></i>Calle Ejemplo 123, Madrid</p>
                    <p class="mb-2"><i class="fa fa-envelope text-primary mr-3"></i>info@startshop.com</p>
                    <p class="mb-2"><i class="fa fa-phone-alt text-primary mr-3"></i>+012 345 67890</p>
                </div>
            </div>
        </div>
    </div>
    <!-- Contact End -->
