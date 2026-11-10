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
(1, 'COD-01', 'Zapatillas Nike', 69.99, NULL, 10, NULL, 1, 4.7),
(2, 'COD-04', 'Camiseta', 15.00, NULL, 10, NULL, 2, 4.9),
(3, 'COD-02', 'GTA 6', 99.99, 70.00, 10, 'Grand Theft Auto VI para PlayStation 5. La saga vuelve a Vice City, en el estado de Leonida, con dos protagonistas: Lucia y Jason. Edición estándar en español. Para mayores de 18 años.', 3, 4.0),
(4, 'COD-03', 'Balón de baloncesto', 49.99, 40.00, 10, NULL, 4, 4.3),
(7, 'COD-05', 'Consola PlayStation 5', 549.99, 499.99, 8, 'Consola PS5 con lector de discos, almacenamiento SSD ultrarrápido y mando inalámbrico DualSense incluido. Juega en 4K y hasta 120 fps.', 3, 4.8),
(8, 'COD-06', 'Mochila deportiva urbana', 39.99, 29.99, 25, 'Mochila de 25 litros con compartimento acolchado para portátil de hasta 15,6 pulgadas, bolsillo trasero antirrobo y tejido repelente al agua. Ideal para el gimnasio, la universidad o el día a día.', 4, 4.5),
(9, 'COD-07', 'Auriculares deportivos inalámbricos', 59.99, NULL, 18, 'Auriculares Bluetooth 5.3 de diseño abierto con gancho de oreja, resistentes al sudor (IPX5) y hasta 8 horas de música por carga. El estuche de carga ofrece 24 horas adicionales.', 4, 4.4),
(10, 'COD-08', 'Gorra deportiva azul', 14.99, NULL, 40, 'Gorra de algodón con visera curva, ribete reflectante y cierre ajustable. Talla única.', 2, 4.2),
(11, 'COD-09', 'Jersey de punto azul', 34.99, 24.99, 22, 'Jersey unisex de punto suave con cuello redondo y puños de canalé. 80 % algodón, 20 % poliéster. Disponible de la S a la XL.', 2, 4.6),
(12, 'COD-10', 'Blusa negra con encaje', 29.95, NULL, 15, 'Blusa holgada de mangas amplias con detalle de encaje en hombros y escote con lazada. Tejido ligero y fluido.', 2, 4.3),
(13, 'COD-11', 'Zapatillas infantiles Nike', 44.99, 35.99, 30, 'Zapatillas para niños con cierre de velcro, suela flexible y amortiguación ligera. Fáciles de poner y quitar. Tallas de la 25 a la 35.', 1, 4.8),
(14, 'COD-12', 'Kit de cuidado facial 3 pasos', 32.50, NULL, 20, 'Rutina completa con limpiador suave, tratamiento y crema hidratante. Para todo tipo de pieles, sin parabenos ni perfume.', 5, 4.5),
(15, 'COD-13', 'Reloj inteligente deportivo', 129.00, 99.00, 12, 'Smartwatch con pulsómetro, GPS, contador de pasos, notificaciones del móvil y correa de malla metálica. Hasta 2 días de batería y resistente al agua.', 4, 4.6),
(16, 'COD-14', 'Botas de montaña impermeables', 89.90, NULL, 14, 'Botas de trekking de ante y tejido técnico con membrana impermeable y transpirable, suela de goma con tacos de agarre y puntera reforzada.', 1, 4.7),
(17, 'COD-15', 'Mando inalámbrico para consola', 49.99, 39.99, 35, 'Mando inalámbrico ergonómico con vibración, batería recargable de 20 horas y conexión por Bluetooth o cable USB-C. Compatible con PC y consolas.', 3, 4.4),
(18, 'COD-16', 'Auriculares gaming con micrófono', 54.99, NULL, 20, 'Auriculares de diadema con sonido envolvente 7.1, micrófono flexible con cancelación de ruido y almohadillas acolchadas para largas partidas.', 3, 4.5),
(19, 'COD-17', 'Esterilla de yoga antideslizante', 24.99, 19.99, 28, 'Esterilla de 183 x 61 cm y 6 mm de grosor, antideslizante por ambas caras. Se enrolla fácilmente para llevarla a clase.', 4, 4.3),
(20, 'COD-18', 'Gel de baño y ducha floral', 4.95, NULL, 60, 'Gel de baño y ducha con aroma floral y pH neutro que hidrata y respeta la piel. Botella de 500 ml.', 5, 4.1),
(21, 'COD-19', 'Cepillo de dientes eléctrico recargable', 39.90, 29.90, 24, 'Cepillo sónico con 3 modos de limpieza, temporizador de 2 minutos y estuche de viaje. Carga por USB y batería de hasta 3 semanas.', 5, 4.6),
(22, 'COD-20', 'Chanclas de playa', 12.99, NULL, 50, 'Chanclas ligeras con plantilla acolchada y tira suave que no roza. Perfectas para la playa y la piscina.', 1, 3.9);

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
-- Clave foránea productos -> categorias
-- --------------------------------------------------------

ALTER TABLE `productos`
  ADD CONSTRAINT `fk_productos_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
