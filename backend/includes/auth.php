<?php
declare(strict_types=1);

// Tablas usadas (nombres definidos en config/config.php):
//   TABLA_ADMIN     = admin_usuarios
//   TABLA_INTENTOS  = admin_intentos_login
//   TABLA_ACTIVIDAD = admin_actividad

// =========================================================
//  ESTADO DE SESIÓN
// =========================================================
function admin_logueado(): bool
{
    return isset($_SESSION['admin_id']);
}

function admin_actual(): ?array
{
    if (!admin_logueado()) {
        return null;
    }
    return [
        'id'     => (int)$_SESSION['admin_id'],
        'nombre' => $_SESSION['admin_nombre'] ?? '',
        'rol'    => $_SESSION['admin_rol'] ?? 'consulta',
    ];
}

/** Va al inicio de cada página privada. */
function requerir_admin(): void
{
    if (!admin_logueado()) {
        redirigir('login.php');
    }

    // Expiró por inactividad
    if (time() - ($_SESSION['ultimo_acceso'] ?? 0) > SESION_TIMEOUT) {
        cerrar_sesion();
        header('Location: ' . url('login.php') . '?expirada=1');
        exit;
    }

    // Si desactivaron al usuario o le cambiaron el rol mientras tenía la sesión abierta
    $st = db()->prepare('SELECT activo, rol FROM ' . TABLA_ADMIN . ' WHERE id = ? LIMIT 1');
    $st->execute([$_SESSION['admin_id']]);
    $u = $st->fetch();

    if (!$u || (int)$u['activo'] !== 1) {
        cerrar_sesion();
        redirigir('login.php');
    }

    $_SESSION['admin_rol']     = $u['rol'];
    $_SESSION['ultimo_acceso'] = time();
}

/** Restringe una página a ciertos roles: requerir_rol('admin', 'gestor'); */
function requerir_rol(string ...$roles): void
{
    requerir_admin();

    if (!in_array($_SESSION['admin_rol'] ?? '', $roles, true)) {
        http_response_code(403);
        exit('No tienes permiso para ver esta sección.');
    }
}

// =========================================================
//  LOGIN
// =========================================================
function intentar_login(string $email, string $password): bool
{
    if ($email === '' || $password === '') {
        return false;
    }

    $st = db()->prepare(
        'SELECT id, nombre, password_hash, rol, activo
           FROM ' . TABLA_ADMIN . '
          WHERE email = ?
          LIMIT 1'
    );
    $st->execute([$email]);
    $u = $st->fetch();

    // Hash falso: si el correo no existe, se tarda lo mismo
    // y así no se puede adivinar qué correos están registrados
    $hashFalso = '$2y$10$usesomesillystringfore7hnbRJHxXVLeakoG8K30oukPsA.ztMG';
    $ok = password_verify($password, $u['password_hash'] ?? $hashFalso);

    if (!$u || !$ok || (int)$u['activo'] !== 1) {
        registrar_intento($email, false);
        return false;
    }

    // Actualiza el hash si PHP cambió el algoritmo recomendado
    if (password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT)) {
        db()->prepare('UPDATE ' . TABLA_ADMIN . ' SET password_hash = ? WHERE id = ?')
            ->execute([password_hash($password, PASSWORD_DEFAULT), $u['id']]);
    }

    db()->prepare('UPDATE ' . TABLA_ADMIN . ' SET ultimo_login = NOW() WHERE id = ?')
        ->execute([$u['id']]);

    session_regenerate_id(true); // evita la fijación de sesión
    $_SESSION['admin_id']      = (int)$u['id'];
    $_SESSION['admin_nombre']  = $u['nombre'];
    $_SESSION['admin_rol']     = $u['rol'];
    $_SESSION['ultimo_acceso'] = time();

    registrar_intento($email, true);
    registrar_actividad('login');

    return true;
}

// =========================================================
//  ANTI FUERZA BRUTA (admin_intentos_login)
//  Columnas: id, email, ip, exitoso, creado_en
// =========================================================
function registrar_intento(string $email, bool $exitoso): void
{
    db()->prepare('INSERT INTO ' . TABLA_INTENTOS . ' (email, ip, exitoso) VALUES (?, ?, ?)')
        ->execute([mb_substr($email, 0, 150), ip_cliente(), $exitoso ? 1 : 0]);
}

/**
 * Devuelve los segundos de bloqueo que faltan (0 = puede intentar).
 * - Por correo: 5 fallos en 15 min (sin contar los anteriores al último ingreso correcto)
 * - Por IP: 20 fallos en 15 min (frena a quien prueba muchos correos)
 */
function login_bloqueado(string $email): int
{
    $ventana = LOGIN_BLOQUEO_SEG;

    // Último ingreso correcto de este correo
    $st = db()->prepare(
        'SELECT MAX(creado_en) FROM ' . TABLA_INTENTOS . ' WHERE email = ? AND exitoso = 1'
    );
    $st->execute([$email]);
    $ultimoExito = $st->fetchColumn() ?: '1970-01-02 00:00:00';

    // --- Por correo ---
    $st = db()->prepare(
        'SELECT COUNT(*) AS n, UNIX_TIMESTAMP(MAX(creado_en)) AS ultimo
           FROM ' . TABLA_INTENTOS . '
          WHERE email = ?
            AND exitoso = 0
            AND creado_en > NOW() - INTERVAL ? SECOND
            AND creado_en > ?'
    );
    $st->execute([$email, $ventana, $ultimoExito]);
    $porEmail = $st->fetch();

    // --- Por IP ---
    $st = db()->prepare(
        'SELECT COUNT(*) AS n, UNIX_TIMESTAMP(MAX(creado_en)) AS ultimo
           FROM ' . TABLA_INTENTOS . '
          WHERE ip = ?
            AND exitoso = 0
            AND creado_en > NOW() - INTERVAL ? SECOND'
    );
    $st->execute([ip_cliente(), $ventana]);
    $porIp = $st->fetch();

    $espera = 0;
    if ((int)$porEmail['n'] >= LOGIN_MAX_INTENTOS) {
        $espera = max($espera, (int)$porEmail['ultimo'] + $ventana - time());
    }
    if ((int)$porIp['n'] >= LOGIN_MAX_INTENTOS_IP) {
        $espera = max($espera, (int)$porIp['ultimo'] + $ventana - time());
    }

    return max(0, $espera);
}

// =========================================================
//  AUDITORÍA (admin_actividad)
//  Columnas: id, usuario_id, accion, entidad, entidad_id, detalle, ip, creado_en
// =========================================================
function registrar_actividad(
    string $accion,
    ?string $entidad = null,
    ?int $entidadId = null,
    ?string $detalle = null
): void {
    try {
        db()->prepare(
            'INSERT INTO ' . TABLA_ACTIVIDAD . '
                    (usuario_id, accion, entidad, entidad_id, detalle, ip)
             VALUES (?, ?, ?, ?, ?, ?)'
        )->execute([
            $_SESSION['admin_id'] ?? null,
            mb_substr($accion, 0, 50),
            $entidad !== null ? mb_substr($entidad, 0, 50) : null,
            $entidadId,
            $detalle !== null ? mb_substr($detalle, 0, 255) : null,
            ip_cliente(),
        ]);
    } catch (Throwable $ex) {
        // Si falla la auditoría no se interrumpe la acción del usuario
        error_log('Auditoría: ' . $ex->getMessage());
    }
}

// =========================================================
//  CERRAR SESIÓN
// =========================================================
function cerrar_sesion(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires'  => time() - 42000,
            'path'     => $p['path'],
            'domain'   => $p['domain'],
            'secure'   => $p['secure'],
            'httponly' => $p['httponly'],
            'samesite' => $p['samesite'] ?? 'Strict',
        ]);
    }

    session_destroy();
}
