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
(3, 'COD-02', 'GTA 6', 99.99, 70.00, 10, NULL, 3, 4.0),
(4, 'COD-03', 'Balón de baloncesto', 49.99, 40.00, 10, NULL, 4, 4.3),
(7, 'COD-05', 'GTA VI', 100.00, 3.00, 67, '', 3, 0.0);

-- --------------------------------------------------------
-- Tabla `usuarios`
-- --------------------------------------------------------

CREATE TABLE `usuarios` (
  `id` int(10) UNSIGNED NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `contrasena` varchar(255) NOT NULL,
  `rol` enum('admin','cliente') NOT NULL DEFAULT 'cliente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `usuarios` (`id`, `usuario`, `contrasena`, `rol`) VALUES
(1, 'admin', 'admin1234', 'admin'),
(2, 'cliente', 'cliente1234', 'cliente'),
(5, 'cacahuete', 'cacaca', 'cliente'),
(6, 'ciruela', 'mbappe', 'cliente');

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

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
