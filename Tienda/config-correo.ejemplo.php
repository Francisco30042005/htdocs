<?php
/*
  Configuración del correo (formulario de Conócenos) - ARCHIVO DE EJEMPLO.

  Copia este archivo como config-correo.php (ese no se sube a GitHub, está en .gitignore)
  y rellena la copia. La tienda envía los correos a través de una cuenta de Gmail.

  1. Usa una cuenta de Gmail para la tienda (mejor una creada para la práctica).
  2. Activa la verificación en dos pasos: https://myaccount.google.com/security
  3. Crea una "contraseña de aplicación": https://myaccount.google.com/apppasswords
     (ponle de nombre "Tio Pepe"). Google te da 16 letras: cópialas aquí abajo.
     NO es la contraseña normal de la cuenta, y no la compartas fuera del equipo.
*/
const CORREO_TIENDA = '';            // la cuenta de Gmail que envía, por ejemplo tiendatiopepe@gmail.com
const CORREO_CONTRASENA_APP = '';    // la contraseña de aplicación (16 letras)
const CORREO_DESTINO = 'FranciscoDHE30@gmail.com'; // quién recibe los mensajes de Conócenos
