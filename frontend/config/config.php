<?php
declare(strict_types=1);

// Entorno: pon false al subir a producción
define('APP_DEBUG', true);

// Base de datos (valores por defecto de XAMPP / Laragon)
define('DB_HOST', 'localhost');
define('DB_PORT', 3306);
define('DB_NAME', 'lina_ferrer');
define('DB_USER', 'root');
define('DB_PASS', '');

// Negocio
define('WHATSAPP', '573506830028');          // 57 + número
define('ENVIO_COSTO', 0);                    // 0 = envío gratis
define('MAX_UNIDADES_POR_PEDIDO', 3);
define('METODOS_PAGO', ['transferencia', 'tarjeta', 'asesor']);

// CORS: déjalo vacío si el front y la API están en el mismo dominio
define('CORS_ORIGIN', '');

date_default_timezone_set('America/Bogota');
ini_set('display_errors', '0');              // los errores se devuelven en JSON
error_reporting(E_ALL);
