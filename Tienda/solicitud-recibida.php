<?php
/*
  Confirmación de envío del formulario de Conócenos (index.php?p=solicitud-recibida).
*/
mostrar_migas_de_pan([
    'Inicio' => 'index.php',
    'Conócenos' => 'index.php?p=conocenos',
    'Mensaje enviado' => null,
]);
?>
    <!-- Solicitud recibida Start -->
    <div class="container-fluid">
        <div class="row px-xl-5">
            <div class="col">
                <div class="bg-light p-30 text-center">
                    <h1 class="display-3 text-primary mb-0"><i class="fa fa-check-circle"></i></h1>
                    <h2 class="mt-4 mb-3">¡Mensaje enviado!</h2>
                    <p class="lead mb-4 mx-auto" style="max-width: 640px;">
                        Gracias por ponerte en contacto con nosotros. Hemos recibido tu solicitud correctamente y nuestro equipo te responderá lo antes posible.
                    </p>
                    <a href="index.php" class="btn btn-primary py-2 px-4">
                        <i class="fa fa-home mr-1"></i> Volver a la tienda
                    </a>
                </div>
            </div>
        </div>
    </div>
    <!-- Solicitud recibida End -->
