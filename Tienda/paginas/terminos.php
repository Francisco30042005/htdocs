<?php
/*
  TÉRMINOS Y CONDICIONES (index.php?p=terminos)
  Las normas de uso de la tienda: compras, envíos, devoluciones, opiniones y anuncios.
*/
mostrar_migas_de_pan([
    'Inicio' => 'index.php',
    'Términos y condiciones' => null,
]);
?>
    <div class="container-fluid">
        <h2 class="section-title position-relative text-uppercase mx-xl-5 mb-4"><span class="bg-secondary pr-3">Términos y condiciones</span></h2>
        <div class="row px-xl-5">
            <div class="col-lg-9 mb-5">
                <div class="bg-light p-30">
                    <h5>1. Quiénes somos</h5>
                    <p>Tio Pepe es una tienda online con sede en Calle Ejemplo 123, Madrid (info@tiendatiopepe.es).
                       Al usar la tienda aceptas estas condiciones.</p>

                    <h5>2. Cuenta</h5>
                    <p>Para comprar necesitas una cuenta. Eres responsable de guardar tu contraseña en secreto.
                       Por seguridad, la cuenta se bloquea <?= MINUTOS_BLOQUEO ?> minutos si se falla la contraseña <?= INTENTOS_MAXIMOS ?> veces seguidas.</p>

                    <h5>3. Precios y pago</h5>
                    <p>Los precios incluyen IVA. Los cupones de descuento se aplican en el carrito y no se pueden cambiar por dinero.
                       El pago se hace con tarjeta a través de Stripe y el pedido se confirma cuando el pago se completa.</p>

                    <h5>4. Envíos</h5>
                    <p>Enviamos a la dirección de "Mi cuenta", que debe ser una dirección de España válida.
                       Puedes seguir el estado de cada pedido en "Mi cuenta" → "Mis pedidos".</p>

                    <h5>5. Devoluciones</h5>
                    <p>Tienes 14 días naturales desde que recibes el pedido para devolverlo sin dar explicaciones (derecho de desistimiento).
                       Escríbenos y te diremos cómo enviarlo; te devolvemos el dinero con el mismo método de pago.</p>

                    <h5>6. Opiniones y anuncios</h5>
                    <p class="mb-0">Las opiniones y los anuncios que publiques deben ser verdaderos y respetuosos. No se permite publicidad
                       engañosa, ilegal, ofensiva ni de productos peligrosos. Los anuncios los revisa la administración antes de
                       publicarse y puede rechazarlos o retirarlos en cualquier momento.</p>
                </div>
            </div>
        </div>
    </div>
