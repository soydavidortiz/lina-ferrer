<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

// URL pública del frontend (hermana de /backend)
define('FRONTEND_URL', preg_replace('#/backend/?$#', '/frontend', BASE_URL));

date_default_timezone_set(APP_ZONA_HORARIA);

// ===== Errores =====
error_reporting(E_ALL);
if (APP_ENV === 'local') {
    ini_set('display_errors', '1');
} else {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    set_exception_handler(function (Throwable $ex): void {
        error_log((string)$ex);
        http_response_code(500);
        echo 'Ocurrió un error. Intenta más tarde.';
    });
}

// ===== Cabeceras de seguridad =====
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');

// ===== Sesión =====
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    session_name(SESION_NOMBRE);
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => BASE_URL . '/',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

// ===== Carga de módulos =====
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
