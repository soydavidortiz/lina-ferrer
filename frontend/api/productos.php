<?php
declare(strict_types=1);

/* =========================================================
   GET api/productos.php              → lista de piezas
   GET api/productos.php?slug=xxxx    → una pieza
   ========================================================= */
require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error('Método no permitido.', 405);
}

$modelo = new Producto();
$slug = trim((string) ($_GET['slug'] ?? ''));

if ($slug !== '') {
    if (!preg_match('/^[a-z0-9-]{1,80}$/', $slug)) {
        Response::error('Identificador de pieza inválido.', 400);
    }
    $producto = $modelo->porSlug($slug);
    if ($producto === null) {
        Response::error('Pieza no encontrada.', 404);
    }
    Response::ok($producto);
}

Response::ok($modelo->todos());
