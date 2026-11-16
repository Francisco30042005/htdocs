-- =====================================================================
-- Start Shop - Base de datos COMPLETA (único archivo SQL del proyecto)
-- =====================================================================
-- Une el volcado de "startshop" (10-11-2026) con los cambios de la
-- sección Administración (antes sql/admin.sql):
--   · tabla categorias
--   · columnas categoria_id y existencias en productos (+ clave foránea)
--   · referencias con formato COD-xx
--   · tabla usuarios (login del panel)
--
-- SE IMPORTA SOLO: conexion.php (instalar-bd.php) lo ejecuta al abrir la
-- tienda cada vez que este archivo cambia. No hace falta phpMyAdmin.
--
-- Para compartir cambios de la base de datos con el equipo:
--   1. phpMyAdmin > startshop > Exportar > SQL
--   2. Sustituye el contenido de este archivo por la exportación
--      (puedes conservar esta cabecera).
--   3. Los demás, al abrir la tienda, reciben la base de datos nueva.
--   ¡OJO! Al importarse se BORRA la base de datos local y se crea de cero.
--
-- Usuarios de prueba (contraseña sin cifrar), entran por index.php?p=login
--   Administrador  admin / admin1234     -> panel de administración
--   Cliente        cliente / cliente1234 -> tienda
-- =====================================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- Por si alguien lo importa a mano en phpMyAdmin
CREATE DATABASE IF NOT EXISTS `startshop` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `startshop`;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `productos`;
DROP TABLE IF EXISTS `categorias`;
DROP TABLE IF EXISTS `usuarios`;
DROP TABLE IF EXISTS `cupones`;
DROP TABLE IF EXISTS `resenas`;
SET FOREIGN_KEY_CHECKS = 1;

-- --------------------------------------------------------
-- Tabla `categorias`
-- --------------------------------------------------------

CREATE TABLE `categorias` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `categorias` (`id`, `nombre`) VALUES
(1, 'Calzado'),
(5, 'Cuidado personal'),
(4, 'Deportes'),
(2, 'Ropa'),
(3, 'Videojuegos');

-- --------------------------------------------------------
-- Tabla `productos`
-- --------------------------------------------------------

CREATE TABLE `productos` (
  `id` int(10) UNSIGNED NOT NULL,
  `referencia` varchar(50) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `precio` decimal(10,2) UNSIGNED NOT NULL,
  `precio_rebajado` decimal(10,2) UNSIGNED DEFAULT NULL,
  `existencias` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `descripcion` text DEFAULT NULL,
  `categoria_id` int(10) UNSIGNED DEFAULT NULL,
  `valoracion` decimal(2,1) UNSIGNED NOT NULL DEFAULT 0.0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `productos` (`id`, `referencia`, `nombre`, `precio`, `precio_rebajado`, `existencias`, `descripcion`, `categoria_id`, `valoracion`) VALUES
(1, 'COD-01', 'Zapatillas Nike', 69.99, NULL, 10, 'Zapatillas de estilo clásico con la parte superior de piel en blanco y detalles en azul marino. Su suela gruesa con amortiguación de aire absorbe los impactos al caminar, por lo que son perfectas para el día a día, para ir a clase o para entrenar en el gimnasio.\n\nCaracterísticas:\n- Parte superior de piel con refuerzos en la puntera y el talón\n- Amortiguación de aire en el talón para mayor comodidad\n- Plantilla acolchada y forro interior transpirable\n- Suela de goma con buen agarre en interior y exterior\n- Tallas de la 38 a la 46', 1, 4.7),
(2, 'COD-04', 'Camiseta', 15.00, NULL, 10, 'Camiseta deportiva de manga corta en azul intenso, con un pequeño logotipo bordado en el pecho. Su tejido técnico ligero expulsa el sudor y se seca rápido, así que es tan útil para correr o ir al gimnasio como para el día a día.\n\nCaracterísticas:\n- Tejido técnico 100 % poliéster, ligero y transpirable\n- Secado rápido y tacto suave\n- Cuello redondo y costuras planas que no rozan\n- Corte regular unisex\n- Tallas de la S a la XXL. Lavable a máquina a 30 grados', 2, 4.9),
(3, 'COD-02', 'GTA 6', 99.99, 70.00, 10, 'Grand Theft Auto VI para PlayStation 5. La saga vuelve a Vice City, en el estado de Leonida, con una historia protagonizada por Lucia y Jason. Explora un mundo abierto enorme y lleno de vida, con ciudades, playas, pantanos y carreteras, y disfruta de su modo en línea.\n\nCaracterísticas:\n- Plataforma: PlayStation 5\n- Edición estándar en formato físico\n- Textos y voces en español\n- Modo historia para un jugador y modo en línea\n- Para mayores de 18 años (PEGI 18)', 3, 4.0),
(4, 'COD-03', 'Balón de baloncesto', 49.99, 40.00, 10, 'Balón de baloncesto de talla oficial con cubierta de material compuesto de gran agarre. Mantiene bien la presión y la forma con el uso, y sirve tanto para pista cubierta como para canchas exteriores.\n\nCaracterísticas:\n- Talla 7, la oficial para competición masculina\n- Cubierta de material compuesto con buen agarre\n- Canales profundos para controlar mejor el balón\n- Apto para interior y exterior\n- Se entrega desinflado. Presión recomendada: 0,5 a 0,6 bar', 4, 4.3),
(7, 'COD-05', 'Consola PlayStation 5', 549.99, 499.99, 8, 'Consola PlayStation 5 con lector de discos para jugar tanto en formato físico como digital. Su almacenamiento SSD de alta velocidad reduce los tiempos de carga casi a cero, y el mando DualSense incluido añade vibración háptica y gatillos adaptativos que te hacen sentir cada partida.\n\nCaracterísticas:\n- Lector de discos Blu-ray Ultra HD\n- Almacenamiento SSD de alta velocidad\n- Juego en 4K y hasta 120 fps, con trazado de rayos\n- Mando inalámbrico DualSense incluido\n- Compatible con la mayoría de juegos de PS4\n- Incluye cable HDMI, cable de alimentación y cable USB', 3, 4.8),
(8, 'COD-06', 'Mochila deportiva urbana', 39.99, 29.99, 25, 'Mochila urbana de 25 litros pensada para llevar el portátil y la ropa del gimnasio en el mismo sitio. Su diseño antirrobo esconde las cremalleras contra la espalda y el tejido repelente al agua protege tus cosas de la lluvia.\n\nCaracterísticas:\n- Capacidad de 25 litros\n- Compartimento acolchado para portátil de hasta 15,6 pulgadas\n- Cremalleras ocultas y bolsillo trasero antirrobo\n- Tejido repelente al agua\n- Espalda y tirantes acolchados y transpirables\n- Medidas: 45 x 30 x 15 cm', 4, 4.5),
(9, 'COD-07', 'Auriculares deportivos inalámbricos', 59.99, NULL, 18, 'Auriculares inalámbricos de diseño abierto con gancho para la oreja: no se caen al correr y te dejan oír lo que pasa a tu alrededor, algo muy útil si entrenas por la calle. Son resistentes al sudor y vienen con un estuche que los carga en cualquier parte.\n\nCaracterísticas:\n- Bluetooth 5.3 con conexión estable y rápida\n- Hasta 8 horas de música por carga\n- 24 horas adicionales con el estuche de carga\n- Resistentes al sudor y a salpicaduras (IPX5)\n- Micrófono para llamadas y controles táctiles\n- Carga por USB-C', 4, 4.4),
(10, 'COD-08', 'Gorra deportiva azul', 14.99, NULL, 40, 'Gorra deportiva de algodón en azul con visera curva en negro y un ribete reflectante que te hace más visible al salir a correr con poca luz. Protege del sol y su cierre trasero ajustable se adapta a cualquier cabeza.\n\nCaracterísticas:\n- 100 % algodón, fresca y transpirable\n- Visera curva que protege del sol\n- Ribete reflectante para mayor visibilidad\n- Cierre trasero ajustable\n- Talla única para adulto', 2, 4.2),
(11, 'COD-09', 'Jersey de punto azul', 34.99, 24.99, 22, 'Jersey de punto suave en azul con cuello redondo y puños y bajo de canalé. Es cálido sin pesar y combina igual de bien con vaqueros que con ropa más arreglada, así que es una prenda básica para el otoño y el invierno.\n\nCaracterísticas:\n- 80 % algodón y 20 % poliéster\n- Punto suave y cálido\n- Cuello redondo y puños de canalé\n- Corte regular unisex\n- Tallas de la S a la XL. Lavable a máquina a 30 grados', 2, 4.6),
(12, 'COD-10', 'Blusa negra con encaje', 29.95, NULL, 15, 'Blusa negra holgada con mangas amplias y detalle de encaje calado en los hombros. El escote se cierra con una lazada y el tejido ligero cae con mucha fluidez, por lo que queda bien tanto suelta como metida por dentro.\n\nCaracterísticas:\n- Tejido ligero y fluido 100 % viscosa\n- Encaje calado en hombros y parte alta de las mangas\n- Escote con lazada ajustable\n- Mangas amplias con puño elástico\n- Tallas de la XS a la XL', 2, 4.3),
(13, 'COD-11', 'Zapatillas infantiles Nike', 44.99, 35.99, 30, 'Zapatillas para niños con cierre de velcro, fáciles de poner y quitar sin ayuda. La suela flexible acompaña el movimiento natural del pie y la amortiguación ligera hace que aguanten todo el día de colegio y de parque.\n\nCaracterísticas:\n- Cierre de velcro de doble tira\n- Parte superior de malla transpirable\n- Suela flexible de goma con buen agarre\n- Plantilla acolchada\n- Tallas de la 25 a la 35', 1, 4.8),
(14, 'COD-12', 'Kit de cuidado facial 3 pasos', 32.50, NULL, 20, 'Rutina facial completa en tres pasos para cuidar la piel cada día: un limpiador suave que elimina la suciedad sin resecar, un tratamiento que unifica el tono y una crema hidratante ligera que se absorbe rápido.\n\nCaracterísticas:\n- Paso 1: limpiador facial suave (100 ml)\n- Paso 2: tratamiento diario (50 ml)\n- Paso 3: crema hidratante ligera (50 ml)\n- Para todo tipo de pieles, también las sensibles\n- Sin parabenos ni perfume añadido\n- Se presenta en caja de regalo de cartón reciclado', 5, 4.5),
(15, 'COD-13', 'Reloj inteligente deportivo', 129.00, 99.00, 12, 'Reloj inteligente con pantalla táctil y correa de malla metálica que sirve tanto para hacer deporte como para el día a día. Mide tu actividad, tu ritmo cardíaco y tus rutas, y te avisa de las llamadas y los mensajes del móvil.\n\nCaracterísticas:\n- Pulsómetro y control del sueño\n- GPS integrado para registrar tus rutas\n- Contador de pasos, calorías y más de 50 deportes\n- Notificaciones de llamadas, mensajes y aplicaciones\n- Resistente al agua hasta 50 metros\n- Hasta 2 días de batería con carga magnética', 4, 4.6),
(16, 'COD-14', 'Botas de montaña impermeables', 89.90, NULL, 14, 'Botas de trekking de caña media en ante y tejido técnico, con membrana impermeable y transpirable que mantiene el pie seco aunque llueva o cruces charcos. La suela con tacos profundos agarra bien en tierra, roca y barro.\n\nCaracterísticas:\n- Parte superior de ante y tejido técnico resistente\n- Membrana impermeable y transpirable\n- Caña media que sujeta el tobillo\n- Suela de goma con tacos de agarre\n- Puntera reforzada contra golpes\n- Tallas de la 37 a la 46', 1, 4.7),
(17, 'COD-15', 'Mando inalámbrico para consola', 49.99, 39.99, 35, 'Mando inalámbrico ergonómico con gatillos analógicos y vibración, pensado para largas sesiones de juego. Se conecta por Bluetooth o por cable USB-C y funciona con PC y con las consolas más habituales.\n\nCaracterísticas:\n- Conexión Bluetooth o por cable USB-C\n- Compatible con PC, consolas y móviles\n- Vibración y gatillos analógicos\n- Batería recargable de hasta 20 horas\n- Diseño ergonómico con agarre antideslizante\n- Incluye cable USB-C de 1,5 metros', 3, 4.4),
(18, 'COD-16', 'Auriculares gaming con micrófono', 54.99, NULL, 20, 'Auriculares de diadema para gaming con sonido envolvente 7.1 virtual, que te permite saber de dónde vienen los pasos y los disparos. El micrófono flexible cancela el ruido de fondo y las almohadillas acolchadas son cómodas incluso en partidas largas.\n\nCaracterísticas:\n- Sonido envolvente 7.1 virtual\n- Altavoces de 50 mm\n- Micrófono flexible con cancelación de ruido\n- Almohadillas acolchadas y diadema ajustable\n- Iluminación LED\n- Conexión USB y jack de 3,5 mm (PC, consolas y móvil)', 3, 4.5),
(19, 'COD-17', 'Esterilla de yoga antideslizante', 24.99, 19.99, 28, 'Esterilla de yoga y pilates de 6 mm de grosor que amortigua rodillas y espalda sin perder estabilidad. Es antideslizante por ambas caras y se enrolla fácilmente para llevarla a clase o guardarla en casa.\n\nCaracterísticas:\n- Medidas: 183 x 61 cm\n- Grosor de 6 mm\n- Antideslizante por las dos caras\n- Material TPE sin látex ni PVC\n- Ligera (unos 900 g) y fácil de limpiar', 4, 4.3),
(20, 'COD-18', 'Gel de baño y ducha floral', 4.95, NULL, 60, 'Gel de baño y ducha con aroma floral que limpia la piel con suavidad y la deja hidratada y perfumada. Su fórmula con pH neutro respeta la piel, así que se puede usar a diario por toda la familia.\n\nCaracterísticas:\n- Botella de 500 ml\n- Aroma floral\n- pH neutro\n- Fórmula hidratante\n- Testado dermatológicamente', 5, 4.1),
(21, 'COD-19', 'Cepillo de dientes eléctrico recargable', 39.90, 29.90, 24, 'Cepillo de dientes eléctrico sónico que elimina mucha más placa que un cepillo manual. Tiene tres modos de limpieza y un temporizador que te avisa cuando llevas los dos minutos recomendados por los dentistas.\n\nCaracterísticas:\n- Tecnología sónica de alta frecuencia\n- 3 modos: limpieza, blanqueamiento y encías sensibles\n- Temporizador de 2 minutos con aviso cada 30 segundos\n- Batería de hasta 3 semanas con carga por USB\n- Incluye 2 cabezales y estuche de viaje', 5, 4.6),
(22, 'COD-20', 'Chanclas de playa', 12.99, NULL, 50, 'Chanclas ligeras con plantilla acolchada y tira suave en forma de V que no roza entre los dedos. La suela antideslizante agarra bien en mojado, así que son ideales para la playa, la piscina y las duchas del gimnasio.\n\nCaracterísticas:\n- Plantilla acolchada\n- Tira suave que no roza\n- Suela antideslizante, también en mojado\n- Secado rápido\n- Tallas de la 36 a la 46', 1, 3.9);

-- --------------------------------------------------------
-- Tabla `usuarios`
-- --------------------------------------------------------

CREATE TABLE `usuarios` (
  `id` int(10) UNSIGNED NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `contrasena` varchar(255) NOT NULL,
  `rol` enum('admin','cliente') NOT NULL DEFAULT 'cliente',
  `nombre` varchar(100) DEFAULT NULL,
  `direccion` varchar(200) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `usuarios` (`id`, `usuario`, `contrasena`, `rol`) VALUES
(1, 'admin', 'admin1234', 'admin'),
(2, 'cliente', 'cliente1234', 'cliente'),
(5, 'cacahuete', 'cacaca', 'cliente'),
(6, 'ciruela', 'mbappe', 'cliente');

-- --------------------------------------------------------
-- Tabla `cupones` (códigos de descuento del carrito)
-- --------------------------------------------------------

CREATE TABLE `cupones` (
  `codigo` varchar(30) NOT NULL PRIMARY KEY,
  `porcentaje` int(10) UNSIGNED NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `cupones` (`codigo`, `porcentaje`, `activo`) VALUES
('BIENVENIDA10', 10, 1),
('VERANO20', 20, 1),
('BLACKFRIDAY30', 30, 0);

-- --------------------------------------------------------
-- Índices
-- --------------------------------------------------------

ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

ALTER TABLE `productos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `referencia` (`referencia`),
  ADD KEY `fk_productos_categoria` (`categoria_id`);

ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario` (`usuario`);

-- --------------------------------------------------------
-- AUTO_INCREMENT
-- --------------------------------------------------------

ALTER TABLE `categorias`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

ALTER TABLE `productos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

ALTER TABLE `usuarios`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

-- --------------------------------------------------------
-- Tabla `resenas` (opiniones de los clientes: 1 a 5 estrellas y texto opcional)
-- Cada usuario puede dejar una reseña por producto.
-- --------------------------------------------------------

CREATE TABLE `resenas` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `producto_id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `puntuacion` tinyint(3) UNSIGNED NOT NULL,
  `comentario` text DEFAULT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  UNIQUE KEY `una_por_usuario` (`producto_id`, `usuario_id`),
  CONSTRAINT `fk_resenas_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_resenas_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `resenas` (`producto_id`, `usuario_id`, `puntuacion`, `comentario`, `fecha`) VALUES
(1, 2, 5, 'Muy cómodas, las uso a diario.', '2026-10-08 14:13:00'),
(1, 5, 5, NULL, '2026-10-15 19:26:00'),
(1, 6, 4, 'Buena calidad, aunque tallan un poco grandes.', '2026-10-22 12:39:00'),
(2, 2, 5, 'Tejido muy fresco, ideal para correr.', '2026-10-29 17:52:00'),
(2, 6, 5, 'Perfecta, repetiré.', '2026-11-05 10:05:00'),
(3, 5, 4, 'Gran juego, la historia engancha desde el principio.', '2026-10-03 15:18:00'),
(3, 6, 4, NULL, '2026-10-10 20:31:00'),
(4, 2, 4, 'Bota muy bien en la pista exterior.', '2026-10-17 13:44:00'),
(4, 5, 5, NULL, '2026-10-24 18:57:00'),
(4, 6, 4, NULL, '2026-10-31 11:10:00'),
(7, 2, 5, 'Rapidísima, los juegos cargan al momento.', '2026-11-07 16:23:00'),
(7, 6, 5, 'El mando DualSense es una pasada.', '2026-10-05 09:36:00'),
(8, 5, 4, 'Caben el portátil y la ropa del gimnasio sin problema.', '2026-10-12 14:49:00'),
(8, 6, 5, NULL, '2026-10-19 19:02:00'),
(9, 2, 4, 'Se sujetan bien al correr y la batería cumple.', '2026-10-26 12:15:00'),
(9, 5, 5, NULL, '2026-11-02 17:28:00'),
(10, 6, 4, 'Bonita y se ajusta bien.', '2026-11-09 10:41:00'),
(10, 2, 4, NULL, '2026-10-07 15:54:00'),
(11, 5, 5, 'Muy suave y abriga mucho.', '2026-10-14 20:07:00'),
(11, 6, 4, NULL, '2026-10-21 13:20:00'),
(12, 6, 4, 'Queda muy bien, el encaje es precioso.', '2026-10-28 18:33:00'),
(12, 5, 5, NULL, '2026-11-04 11:46:00'),
(13, 2, 5, 'Mi hijo se las pone solo gracias al velcro.', '2026-10-02 16:59:00'),
(13, 6, 5, NULL, '2026-10-09 09:12:00'),
(14, 5, 4, 'Deja la piel muy suave. Buen precio.', '2026-10-16 14:25:00'),
(14, 6, 5, NULL, '2026-10-23 19:38:00'),
(15, 2, 5, 'El GPS es muy preciso y la batería aguanta bien.', '2026-10-30 12:51:00'),
(15, 5, 4, 'Buena relación calidad-precio.', '2026-11-06 17:04:00'),
(16, 6, 5, 'Probadas con lluvia y no entra nada de agua.', '2026-10-04 10:17:00'),
(16, 2, 5, NULL, '2026-10-11 15:30:00'),
(16, 5, 4, NULL, '2026-10-18 20:43:00'),
(17, 5, 4, 'Cómodo para jugar horas. En el PC funcionó sin instalar nada.', '2026-10-25 13:56:00'),
(17, 6, 5, NULL, '2026-11-01 18:09:00'),
(18, 2, 5, 'Se oyen perfectamente los pasos en los juegos.', '2026-11-08 11:22:00'),
(18, 6, 4, 'El micrófono podría ser algo mejor.', '2026-10-06 16:35:00'),
(19, 6, 4, 'Buen agarre, no resbala nada.', '2026-10-13 09:48:00'),
(19, 5, 5, NULL, '2026-10-20 14:01:00'),
(20, 2, 4, 'Huele muy bien y dura bastante.', '2026-10-27 19:14:00'),
(20, 5, 4, NULL, '2026-11-03 12:27:00'),
(21, 6, 5, 'Los dientes quedan mucho más limpios que con el manual.', '2026-10-01 17:40:00'),
(21, 2, 4, NULL, '2026-10-08 10:53:00'),
(22, 5, 4, 'Cómodas para la piscina.', '2026-10-15 15:06:00'),
(22, 6, 3, 'Rozan un poco los primeros días.', '2026-10-22 20:19:00');

-- La valoración de cada producto es la media de sus reseñas
UPDATE `productos` SET `valoracion` = COALESCE((SELECT ROUND(AVG(`puntuacion`), 1) FROM `resenas` WHERE `resenas`.`producto_id` = `productos`.`id`), 0);

-- --------------------------------------------------------
-- Clave foránea productos -> categorias
-- --------------------------------------------------------

ALTER TABLE `productos`
  ADD CONSTRAINT `fk_productos_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
