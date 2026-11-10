-- =====================================================================
-- Start Shop - Cambios en la base de datos para la sección Administración
-- Ejecutar UNA vez en phpMyAdmin (base de datos "startshop" > pestaña SQL)
-- =====================================================================

USE startshop;

-- 1. Tabla de categorías -------------------------------------------------
CREATE TABLE IF NOT EXISTS categorias (
    id     INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO categorias (nombre) VALUES
    ('Calzado'), ('Ropa'), ('Videojuegos'), ('Deportes'), ('Cuidado personal');

-- 2. Columnas nuevas en productos: categoría y existencias (stock) -------
ALTER TABLE productos
    ADD COLUMN categoria_id INT UNSIGNED NULL AFTER descripcion,
    ADD COLUMN existencias  INT UNSIGNED NOT NULL DEFAULT 0 AFTER precio_rebajado,
    ADD CONSTRAINT fk_productos_categoria
        FOREIGN KEY (categoria_id) REFERENCES categorias (id)
        ON DELETE SET NULL ON UPDATE CASCADE;

-- 3. Referencias con el formato COD-xx (01 -> COD-01) ---------------------
UPDATE productos
SET referencia = CONCAT('COD-', LPAD(referencia, 2, '0'))
WHERE referencia REGEXP '^[0-9]+$';

-- Categoría y stock de los productos que ya existían
UPDATE productos SET categoria_id = (SELECT id FROM categorias WHERE nombre = 'Calzado')     WHERE referencia = 'COD-01';
UPDATE productos SET categoria_id = (SELECT id FROM categorias WHERE nombre = 'Videojuegos') WHERE referencia = 'COD-02';
UPDATE productos SET categoria_id = (SELECT id FROM categorias WHERE nombre = 'Deportes')    WHERE referencia = 'COD-03';
UPDATE productos SET categoria_id = (SELECT id FROM categorias WHERE nombre = 'Ropa')        WHERE referencia = 'COD-04';
UPDATE productos SET existencias = 10;

-- 4. Usuarios (login del panel de administración) ------------------------
-- La contraseña se guarda tal cual (sin cifrar).
CREATE TABLE IF NOT EXISTS usuarios (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario    VARCHAR(50)  NOT NULL,
    contrasena VARCHAR(255) NOT NULL,
    rol        ENUM('admin', 'cliente') NOT NULL DEFAULT 'cliente',
    PRIMARY KEY (id),
    UNIQUE KEY usuario (usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Los dos entran por index.php?p=login
-- Administrador  (admin / admin1234)     -> va al panel de administración
-- Cliente prueba (cliente / cliente1234) -> va a la tienda
INSERT IGNORE INTO usuarios (usuario, contrasena, rol) VALUES
    ('admin', 'admin1234', 'admin'),
    ('cliente', 'cliente1234', 'cliente');
