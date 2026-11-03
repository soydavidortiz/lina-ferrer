<?php
declare(strict_types=1);

/* =========================================================
   Arranque común de la API
   ========================================================= */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../helpers/Validator.php';
require_once __DIR__ . '/../models/Producto.php';
require_once __DIR__ . '/../models/Sitio.php';
require_once __DIR__ . '/../models/Cliente.php';
require_once __DIR__ . '/../models/Pedido.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

if (CORS_ORIGIN !== '') {
    header('Access-Control-Allow-Origin: ' . CORS_ORIGIN);
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Accept');
}
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Warnings y notices → excepciones (así nunca rompen el JSON)
set_error_handler(function (int $nivel, string $mensaje, string $archivo = '', int $linea = 0): bool {
    if (!(error_reporting() & $nivel)) return false;
    throw new ErrorException($mensaje, 0, $nivel, $archivo, $linea);
});

set_exception_handler(function (Throwable $e): void {
    error_log('[Lira Ferrer] ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
    Response::error(
        APP_DEBUG ? $e->getMessage() : 'Ocurrió un error en el servidor. Intenta de nuevo.',
        500
    );
});

/** Lee el cuerpo JSON de la petición (o $_POST si viene como formulario) */
function leerJson(): array
{
    $crudo = file_get_contents('php://input') ?: '';
    if ($crudo === '') return $_POST;
    if (strlen($crudo) > 20000) Response::error('Solicitud demasiado grande.', 413);

    $datos = json_decode($crudo, true);
    if (!is_array($datos)) Response::error('Formato de datos inválido.', 400);
    return $datos;
}
