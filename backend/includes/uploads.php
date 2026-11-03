<?php
declare(strict_types=1);

/* =========================================================
   LIRA FERRER — Subida y optimización de imágenes
   Guarda en frontend/assets/img/uploads/<carpeta>/
   ========================================================= */

const ADMIN_UPLOAD_MAX_BYTES = 8 * 1024 * 1024; // 8 MB
const ADMIN_UPLOAD_MAX_ANCHO = 1600;

/**
 * Normaliza $_FILES['campo'] a una lista de archivos.
 * @return array<int, array{name:string,type:string,tmp_name:string,error:int,size:int}>
 */
function admin_archivos(string $campo): array
{
    if (empty($_FILES[$campo])) return [];
    $f = $_FILES[$campo];

    if (!is_array($f['name'])) {
        return [$f];
    }

    $lista = [];
    foreach ($f['name'] as $i => $nombre) {
        $lista[] = [
            'name'     => $nombre,
            'type'     => $f['type'][$i] ?? '',
            'tmp_name' => $f['tmp_name'][$i] ?? '',
            'error'    => (int)($f['error'][$i] ?? UPLOAD_ERR_NO_FILE),
            'size'     => (int)($f['size'][$i] ?? 0),
        ];
    }
    return $lista;
}

/**
 * Guarda una imagen y devuelve la ruta relativa al frontend.
 * Convierte a WebP y reduce a 1600 px de ancho si es posible.
 *
 * @return array{ok:bool, ruta:string, error:string}
 */
function admin_guardar_imagen(array $archivo, string $carpeta): array
{
    if (($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['ok' => false, 'ruta' => '', 'error' => 'No se seleccionó ninguna imagen.'];
    }
    if (($archivo['error'] ?? 0) !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'ruta' => '', 'error' => 'La imagen no se pudo subir. Código ' . (int)$archivo['error'] . '.'];
    }
    if (($archivo['size'] ?? 0) > ADMIN_UPLOAD_MAX_BYTES) {
        return ['ok' => false, 'ruta' => '', 'error' => 'La imagen supera el máximo de 8 MB.'];
    }

    $tmp = (string)($archivo['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        return ['ok' => false, 'ruta' => '', 'error' => 'Archivo temporal inválido.'];
    }

    $info = @getimagesize($tmp);
    if (!$info || empty($info['mime'])) {
        return ['ok' => false, 'ruta' => '', 'error' => 'El archivo no es una imagen válida.'];
    }

    $permitidos = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($info['mime'], $permitidos, true)) {
        return ['ok' => false, 'ruta' => '', 'error' => 'Solo se permiten JPG, PNG o WebP.'];
    }

    $carpeta = trim($carpeta, '/\\');
    if ($carpeta === '' || preg_match('/[^a-z0-9_-]/i', $carpeta)) {
        return ['ok' => false, 'ruta' => '', 'error' => 'Carpeta de destino inválida.'];
    }

    $destinoDir = dirname(__DIR__, 2) . '/frontend/assets/img/uploads/' . $carpeta;
    if (!is_dir($destinoDir) && !mkdir($destinoDir, 0775, true) && !is_dir($destinoDir)) {
        return ['ok' => false, 'ruta' => '', 'error' => 'No se pudo crear la carpeta de imágenes.'];
    }

    $base = date('Ymd-His') . '-' . bin2hex(random_bytes(4));
    $rutaRelativa = 'assets/img/uploads/' . $carpeta . '/' . $base . '.webp';
    $destino = $destinoDir . '/' . $base . '.webp';

    // Intentar convertir a WebP con GD
    if (function_exists('imagewebp') && function_exists('imagecreatefromstring')) {
        $contenido = file_get_contents($tmp);
        $img = $contenido !== false ? @imagecreatefromstring($contenido) : false;

        if ($img) {
            $ancho = imagesx($img);
            $alto  = imagesy($img);

            if ($ancho > ADMIN_UPLOAD_MAX_ANCHO) {
                $nuevoAncho = ADMIN_UPLOAD_MAX_ANCHO;
                $nuevoAlto  = (int)round($alto * ($nuevoAncho / $ancho));
                $lienzo = imagecreatetruecolor($nuevoAncho, $nuevoAlto);
                imagealphablending($lienzo, false);
                imagesavealpha($lienzo, true);
                imagecopyresampled($lienzo, $img, 0, 0, 0, 0, $nuevoAncho, $nuevoAlto, $ancho, $alto);
                imagedestroy($img);
                $img = $lienzo;
            }

            imagealphablending($img, false);
            imagesavealpha($img, true);
            $ok = @imagewebp($img, $destino, 86);
            imagedestroy($img);

            if ($ok) {
                return ['ok' => true, 'ruta' => $rutaRelativa, 'error' => ''];
            }
        }
    }

    // Respaldo: guardar el original con su extensión real
    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$info['mime']];
    $rutaRelativa = 'assets/img/uploads/' . $carpeta . '/' . $base . '.' . $ext;
    $destino = $destinoDir . '/' . $base . '.' . $ext;

    if (!move_uploaded_file($tmp, $destino)) {
        return ['ok' => false, 'ruta' => '', 'error' => 'No se pudo guardar la imagen.'];
    }

    return ['ok' => true, 'ruta' => $rutaRelativa, 'error' => ''];
}

/**
 * Procesa una subida múltiple.
 * @return array{rutas:array<int,string>, errores:array<int,string>}
 */
function admin_guardar_imagenes(string $campo, string $carpeta): array
{
    $rutas = [];
    $errores = [];

    foreach (admin_archivos($campo) as $archivo) {
        if (($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) continue;
        $r = admin_guardar_imagen($archivo, $carpeta);
        if ($r['ok']) $rutas[] = $r['ruta'];
        else $errores[] = $r['error'];
    }

    return ['rutas' => $rutas, 'errores' => $errores];
}

/** Borra una imagen local solo si está dentro de assets/img/uploads. */
function admin_borrar_imagen(string $ruta): void
{
    $ruta = ltrim(trim($ruta), '/');
    if (!str_starts_with($ruta, 'assets/img/uploads/')) return;

    $abs = dirname(__DIR__, 2) . '/frontend/' . $ruta;
    if (is_file($abs)) {
        @unlink($abs);
    }
}
