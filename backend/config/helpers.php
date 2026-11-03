<?php
declare(strict_types=1);

/** Escapa texto para mostrarlo en HTML (previene XSS). */
function e($valor): string
{
    return htmlspecialchars((string)($valor ?? ''), ENT_QUOTES, 'UTF-8');
}

/** Ruta absoluta dentro del backend: url('productos/index.php') */
function url(string $ruta = ''): string
{
    return BASE_URL . '/' . ltrim($ruta, '/');
}

/** Redirige dentro del backend y detiene la ejecución. */
function redirigir(string $ruta): void
{
    header('Location: ' . url($ruta));
    exit;
}

/** URL pública del frontend: frontend_url('index.html') */
function frontend_url(string $ruta = ''): string
{
    return FRONTEND_URL . '/' . ltrim($ruta, '/');
}

/** URL de una imagen guardada en el frontend (o URL absoluta). */
function media_url(string $ruta): string
{
    $ruta = trim($ruta);
    if ($ruta === '') return '';
    if (preg_match('#^https?://#i', $ruta)) return $ruta;
    return frontend_url($ruta);
}

/** Fecha legible en español: 02 nov 2026, 11:45 a. m. */
function fecha_legible(?string $fecha, bool $conHora = false): string
{
    if (!$fecha) return '—';
    $ts = strtotime($fecha);
    if (!$ts) return '—';

    $meses = [
        1 => 'ene', 'feb', 'mar', 'abr', 'may', 'jun',
        'jul', 'ago', 'sep', 'oct', 'nov', 'dic',
    ];
    $texto = date('d', $ts) . ' ' . $meses[(int)date('n', $ts)] . ' ' . date('Y', $ts);

    if ($conHora) {
        $texto .= ', ' . date('g:i', $ts) . (date('a', $ts) === 'am' ? ' a. m.' : ' p. m.');
    }
    return $texto;
}

/** Precio en pesos colombianos. */
function precio_cop($valor): string
{
    return '$' . number_format((float)($valor ?? 0), 0, ',', '.') . ' COP';
}

/** Slug sencillo a partir de un nombre. */
function slugificar(string $texto): string
{
    $texto = mb_strtolower(trim($texto), 'UTF-8');
    $texto = strtr($texto, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n']);
    $texto = preg_replace('/[^a-z0-9]+/', '-', $texto) ?? '';
    return trim($texto, '-') ?: 'pieza';
}

function es_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
}

/** IP del visitante (para intentos de login y auditoría). */
function ip_cliente(): string
{
    return substr((string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45);
}

// ===== CSRF =====
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

/** Campo oculto listo para pegar dentro de cualquier <form>. */
function csrf_campo(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_verificar($token): bool
{
    return is_string($token) && hash_equals($_SESSION['csrf'] ?? '', $token);
}

// ===== Mensajes flash (se muestran una sola vez) =====
function flash(string $tipo, string $mensaje): void
{
    $_SESSION['flash'][] = ['tipo' => $tipo, 'mensaje' => $mensaje];
}

function flash_obtener(): array
{
    $mensajes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $mensajes;
}
