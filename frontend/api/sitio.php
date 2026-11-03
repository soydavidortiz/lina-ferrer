<?php
declare(strict_types=1);

/* =========================================================
   GET api/sitio.php
   Devuelve textos, prensa, FAQ, pilares y trayectoria.
   ========================================================= */
require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error('Método no permitido.', 405);
}

Response::ok((new Sitio())->todo());
