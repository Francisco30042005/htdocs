<?php
/*
  Version   : 2.0
  Autor     : Gabriel S.
  Fecha     : 20 Octubre 2026
  Funciones auxiliares compartidas por todas las secciones de la tienda
  (consultas de productos y piezas de HTML que se repiten).
*/

// Símbolo de moneda que se muestra en los precios (cámbialo a '€' si lo prefieres)
const MONEDA = '$';

/**
 * Devuelve los productos de la base de datos.
 *
 * @param mysqli      $conexion   Conexión abierta (conexion.php)
 * @param bool        $rebajados  true = solo productos con precio_rebajado
 * @param string|null $busqueda   Texto a buscar en nombre, referencia o descripción
 * @param int|null    $limite     Número máximo de productos (null = todos)
 * @param int|null    $excluir_id Id de producto que no queremos que aparezca
 * @return array Lista de productos (arrays asociativos)
 */
function obtener_productos(mysqli $conexion, bool $rebajados = false, ?string $busqueda = null, ?int $limite = null, ?int $excluir_id = null): array
{
    $sql = "SELECT id, referencia, nombre, precio, precio_rebajado, descripcion, valoracion
            FROM productos";
    $condiciones = [];
    $tipos = '';
    $valores = [];

    if ($rebajados) {
        $condiciones[] = "precio_rebajado IS NOT NULL AND precio_rebajado > 0";
    }
    if ($busqueda !== null && $busqueda !== '') {
        $condiciones[] = "(nombre LIKE ? OR referencia LIKE ? OR descripcion LIKE ?)";
        $patron = '%' . $busqueda . '%';
        $tipos .= 'sss';
        array_push($valores, $patron, $patron, $patron);
    }
    if ($excluir_id !== null) {
        $condiciones[] = "id <> ?";
        $tipos .= 'i';
        $valores[] = $excluir_id;
    }
    if ($condiciones) {
        $sql .= " WHERE " . implode(' AND ', $condiciones);
    }
    $sql .= " ORDER BY id DESC";
    if ($limite !== null) {
        $sql .= " LIMIT " . (int) $limite;
    }

    // Consulta preparada para evitar inyección SQL
    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        return [];
    }
    if ($tipos !== '') {
        $stmt->bind_param($tipos, ...$valores);
    }
    $stmt->execute();
    $productos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $productos;
}

/** Devuelve un producto por su id, o null si no existe. */
function obtener_producto(mysqli $conexion, int $id): ?array
{
    $stmt = $conexion->prepare("SELECT id, referencia, nombre, precio, precio_rebajado, descripcion, valoracion
                                FROM productos WHERE id = ?");
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $producto = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $producto ?: null;
}

/** Cuenta los productos (todos o solo los rebajados). */
function contar_productos(mysqli $conexion, bool $rebajados = false): int
{
    $sql = "SELECT COUNT(*) AS total FROM productos";
    if ($rebajados) {
        $sql .= " WHERE precio_rebajado IS NOT NULL AND precio_rebajado > 0";
    }
    $resultado = $conexion->query($sql);
    return $resultado ? (int) $resultado->fetch_assoc()['total'] : 0;
}

/** Escapa texto para mostrarlo en HTML. */
function e(?string $texto): string
{
    return htmlspecialchars($texto ?? '', ENT_QUOTES, 'UTF-8');
}

/** true si el producto tiene precio rebajado. */
function esta_rebajado(array $producto): bool
{
    return !is_null($producto['precio_rebajado']) && $producto['precio_rebajado'] > 0;
}

/** Formatea un precio: $12.50 */
function precio(float $valor): string
{
    return MONEDA . number_format($valor, 2);
}

/**
 * Ruta de la imagen del producto (images/REFERENCIA.jpg)
 * o una imagen de relleno si no existe.
 */
function imagen_producto(array $producto, string $relleno = '600x600'): string
{
    $ruta = 'images/' . $producto['referencia'] . '.jpg';
    if (!file_exists(__DIR__ . '/' . $ruta)) {
        $ruta = 'https://dummyimage.com/' . $relleno . '/dee2e6/6c757d.jpg';
    }
    return $ruta;
}

/** Enlace a la ficha del producto. */
function url_producto(array $producto): string
{
    return 'index.php?p=informacion-producto&id=' . (int) $producto['id'];
}

/**
 * HTML de las estrellas de valoración (0 a 5, admite medias estrellas).
 * $clase = clases CSS extra de cada estrella.
 */
function estrellas(?float $valoracion, string $clase = 'text-primary mr-1'): string
{
    // Redondeamos a la media estrella más cercana
    $valor = round(((float) $valoracion) * 2) / 2;
    $html = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($valor >= $i) {
            $icono = 'fas fa-star';
        } elseif ($valor >= $i - 0.5) {
            $icono = 'fas fa-star-half-alt';
        } else {
            $icono = 'far fa-star';
        }
        $html .= '<small class="' . $icono . ' ' . $clase . '"></small>';
    }
    return $html;
}

/**
 * Pinta la tarjeta de un producto con el estilo de la plantilla MultiShop.
 * Se usa en Inicio, Tienda, Rebajados y en "También te puede gustar".
 */
function tarjeta_producto(array $producto): void
{
    $rebajado = esta_rebajado($producto);
    $url = url_producto($producto);
    ?>
    <div class="product-item bg-light mb-4">
        <div class="product-img position-relative overflow-hidden">
            <img class="img-fluid w-100" src="<?= e(imagen_producto($producto)) ?>" alt="<?= e($producto['nombre']) ?>">
            <?php if ($rebajado): ?>
                <span class="badge badge-primary text-dark position-absolute px-2 py-1" style="top: 10px; right: 10px; z-index: 1;">Rebajado</span>
            <?php endif; ?>
            <div class="product-action">
                <a class="btn btn-outline-dark btn-square" href="#" title="Añadir al carrito"><i class="fa fa-shopping-cart"></i></a>
                <a class="btn btn-outline-dark btn-square" href="<?= e($url) ?>" title="Ver producto"><i class="fa fa-search"></i></a>
            </div>
        </div>
        <div class="text-center py-4 px-2">
            <a class="h6 text-decoration-none text-truncate d-block" href="<?= e($url) ?>"><?= e($producto['nombre']) ?></a>
            <div class="d-flex align-items-center justify-content-center mt-2">
                <?php if ($rebajado): ?>
                    <h5><?= precio($producto['precio_rebajado']) ?></h5>
                    <h6 class="text-muted ml-2"><del><?= precio($producto['precio']) ?></del></h6>
                <?php else: ?>
                    <h5><?= precio($producto['precio']) ?></h5>
                <?php endif; ?>
            </div>
            <div class="d-flex align-items-center justify-content-center mb-1">
                <?= estrellas($producto['valoracion']) ?>
            </div>
        </div>
    </div>
    <?php
}

/** Migas de pan (breadcrumb) de la plantilla. $migas = ['Texto' => 'url', ..., 'Actual' => null] */
function migas(array $migas): void
{
    ?>
    <div class="container-fluid">
        <div class="row px-xl-5">
            <div class="col-12">
                <nav class="breadcrumb bg-light mb-30">
                    <?php foreach ($migas as $texto => $url): ?>
                        <?php if ($url): ?>
                            <a class="breadcrumb-item text-dark" href="<?= e($url) ?>"><?= e($texto) ?></a>
                        <?php else: ?>
                            <span class="breadcrumb-item active"><?= e($texto) ?></span>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </nav>
            </div>
        </div>
    </div>
    <?php
}
