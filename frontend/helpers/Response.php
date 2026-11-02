<?php
declare(strict_types=1);

/* =========================================================
   Respuestas JSON estándar
   { ok: true,  data: ... }
   { ok: false, mensaje: "...", errores: { campo: "..." } }
   ========================================================= */
final class Response
{
    public static function json(array $payload, int $status = 200): void
    {
        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    public static function ok(mixed $data = null, int $status = 200): void
    {
        self::json(['ok' => true, 'data' => $data], $status);
    }

    public static function error(string $mensaje, int $status = 400, array $errores = []): void
    {
        $payload = ['ok' => false, 'mensaje' => $mensaje];
        if ($errores) {
            $payload['errores'] = $errores;
        }
        self::json($payload, $status);
    }
}
