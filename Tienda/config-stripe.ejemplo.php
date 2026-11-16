<?php
/*
  Configuración de Stripe Checkout (pagos con tarjeta) - ARCHIVO DE EJEMPLO.

  Copia este archivo como config-stripe.php (ese no se sube a GitHub, está en .gitignore)
  y pon tu clave en la copia.

  1. Crea una cuenta gratuita en https://dashboard.stripe.com/register
  2. Con el "Modo de prueba" activado, ve a Desarrolladores > Claves de API
     (https://dashboard.stripe.com/test/apikeys) y copia la "Clave secreta" (empieza por sk_test_).
  3. Pégala aquí abajo, entre las comillas.

  Para probar los pagos usa la tarjeta de prueba de Stripe:
    número 4242 4242 4242 4242 · cualquier fecha futura · cualquier CVC

  IMPORTANTE: usa solo claves de PRUEBA (sk_test_). Nunca pongas aquí una clave real (sk_live_)
  ni compartas este archivo fuera del equipo.
*/
const STRIPE_CLAVE_SECRETA = '';
