<?php
declare(strict_types=1);
require_once __DIR__ . '/config/bootstrap.php';

// Solo por POST y con token CSRF: ningún enlace externo puede cerrarte la sesión
if (es_post() && csrf_verificar($_POST['csrf'] ?? null)) {
    if (admin_logueado()) {
        registrar_actividad('logout');
    }
    cerrar_sesion();
}

redirigir('login.php');
