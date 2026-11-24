<?php
/*
  POLÍTICA DE PRIVACIDAD (index.php?p=privacidad)
  Página de solo texto: qué datos guarda la tienda, para qué y qué derechos tiene el cliente.
*/
mostrar_migas_de_pan([
    'Inicio' => 'index.php',
    'Política de privacidad' => null,
]);
?>
    <div class="container-fluid">
        <h2 class="section-title position-relative text-uppercase mx-xl-5 mb-4"><span class="bg-secondary pr-3">Política de privacidad</span></h2>
        <div class="row px-xl-5">
            <div class="col-lg-9 mb-5">
                <div class="bg-light p-30">
                    <p class="text-muted small">Última actualización: <?= date('d/m/Y', filemtime('paginas/privacidad.php')) ?></p>

                    <h5>1. Responsable</h5>
                    <p>Tio Pepe, Calle Ejemplo 123, Madrid. Correo: info@tiendatiopepe.es.</p>

                    <h5>2. Qué datos guardamos</h5>
                    <ul>
                        <li><b>Cuenta:</b> nombre de usuario y contraseña (siempre cifrada; nadie, ni siquiera nosotros, puede leerla).</li>
                        <li><b>Envío:</b> nombre, dirección y teléfono que escribes en "Mi cuenta".</li>
                        <li><b>Compras:</b> pedidos, productos, importes y tus favoritos.</li>
                        <li><b>Opiniones y anuncios</b> que publiques en la tienda.</li>
                    </ul>
                    <p>Los datos de la tarjeta <b>no</b> pasan por Tio Pepe: el pago se hace en Stripe, que es quien los trata.</p>

                    <h5>3. Para qué los usamos</h5>
                    <p>Solo para gestionar tu cuenta, enviarte los pedidos, atender tus consultas y mostrar tus opiniones y anuncios.
                       No los vendemos ni los cedemos a nadie, salvo a Stripe para cobrar y cuando lo exija la ley.</p>

                    <h5>4. Cuánto tiempo</h5>
                    <p>Mientras tengas la cuenta. Los pedidos se guardan el tiempo que obliga la ley (facturación).</p>

                    <h5>5. Tus derechos</h5>
                    <p>Puedes pedir ver, corregir o borrar tus datos, o limitar su uso, escribiendo a info@tiendatiopepe.es.
                       Tu dirección y tu contraseña puedes cambiarlas tú mismo en "Mi cuenta".
                       Si crees que no tratamos bien tus datos, puedes reclamar ante la Agencia Española de Protección de Datos (aepd.es).</p>

                    <h5>6. Seguridad</h5>
                    <p class="mb-0">Las contraseñas se guardan cifradas, la cuenta se bloquea <?= MINUTOS_BLOQUEO ?> minutos tras
                       <?= INTENTOS_MAXIMOS ?> intentos fallidos y la sesión usa cookies protegidas.</p>
                </div>
            </div>
        </div>
    </div>
