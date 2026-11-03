<?php
declare(strict_types=1);

require_once __DIR__ . '/uploads.php';

/* =========================================================
   LIRA FERRER — CRUD genérico para tablas de contenido
   ========================================================= */

function crud_definiciones(): array
{
    return [
        'ficha' => [
            'tabla' => 'ficha_pieza',
            'titulo' => 'Ficha de la pieza',
            'singular' => 'dato de la ficha',
            'orden' => 'orden ASC, id ASC',
            'columnas' => ['etiqueta', 'valor', 'orden', 'activo'],
            'campos' => [
                'etiqueta' => ['label' => 'Etiqueta', 'tipo' => 'text', 'required' => true],
                'valor' => ['label' => 'Valor', 'tipo' => 'textarea', 'required' => true],
                'orden' => ['label' => 'Orden', 'tipo' => 'number', 'default' => 0],
                'activo' => ['label' => 'Visible en el sitio', 'tipo' => 'checkbox', 'default' => 1],
            ],
        ],
        'pilares' => [
            'tabla' => 'pilares',
            'titulo' => 'Pilares de la filosofía',
            'singular' => 'pilar',
            'orden' => 'orden ASC, id ASC',
            'columnas' => ['titulo', 'texto', 'icono', 'orden', 'activo'],
            'campos' => [
                'titulo' => ['label' => 'Título', 'tipo' => 'text', 'required' => true],
                'texto' => ['label' => 'Texto', 'tipo' => 'textarea', 'required' => true],
                'icono' => [
                    'label' => 'Icono',
                    'tipo' => 'select',
                    'opciones' => [
                        'esmeralda' => 'Esmeralda',
                        'orfebre' => 'Orfebre',
                        'cuero' => 'Cuero',
                        'hoja' => 'Hoja / naturaleza',
                        'certificado' => 'Certificado',
                        'colombia' => 'Colombia',
                    ],
                    'default' => 'esmeralda',
                ],
                'orden' => ['label' => 'Orden', 'tipo' => 'number', 'default' => 0],
                'activo' => ['label' => 'Visible en el sitio', 'tipo' => 'checkbox', 'default' => 1],
            ],
        ],
        'prensa' => [
            'tabla' => 'prensa',
            'titulo' => 'Prensa',
            'singular' => 'nota de prensa',
            'orden' => 'orden ASC, id ASC',
            'columnas' => ['medio', 'titulo', 'fecha', 'imagen', 'orden', 'activo'],
            'campos' => [
                'medio' => ['label' => 'Medio', 'tipo' => 'text', 'required' => true],
                'titulo' => ['label' => 'Titular', 'tipo' => 'text', 'required' => true],
                'resumen' => ['label' => 'Resumen', 'tipo' => 'textarea'],
                'fecha' => ['label' => 'Fecha', 'tipo' => 'date'],
                'imagen' => ['label' => 'Imagen de la nota', 'tipo' => 'imagen', 'carpeta' => 'prensa'],
                'url' => ['label' => 'Enlace al artículo', 'tipo' => 'url'],
                'orden' => ['label' => 'Orden', 'tipo' => 'number', 'default' => 0],
                'activo' => ['label' => 'Visible en el sitio', 'tipo' => 'checkbox', 'default' => 1],
            ],
        ],
        'reconocimientos' => [
            'tabla' => 'reconocimientos',
            'titulo' => 'Reconocimientos',
            'singular' => 'reconocimiento',
            'orden' => 'orden ASC, id ASC',
            'columnas' => ['titulo', 'texto', 'imagen', 'orden', 'activo'],
            'campos' => [
                'titulo' => ['label' => 'Título', 'tipo' => 'text', 'required' => true],
                'texto' => ['label' => 'Texto', 'tipo' => 'textarea'],
                'imagen' => ['label' => 'Imagen / logo', 'tipo' => 'imagen', 'carpeta' => 'reconocimientos'],
                'orden' => ['label' => 'Orden', 'tipo' => 'number', 'default' => 0],
                'activo' => ['label' => 'Visible en el sitio', 'tipo' => 'checkbox', 'default' => 1],
            ],
        ],
        'faq' => [
            'tabla' => 'faq',
            'titulo' => 'Preguntas frecuentes',
            'singular' => 'pregunta',
            'orden' => 'orden ASC, id ASC',
            'columnas' => ['pregunta', 'respuesta', 'orden', 'activo'],
            'campos' => [
                'pregunta' => ['label' => 'Pregunta', 'tipo' => 'text', 'required' => true],
                'respuesta' => ['label' => 'Respuesta', 'tipo' => 'textarea', 'required' => true],
                'orden' => ['label' => 'Orden', 'tipo' => 'number', 'default' => 0],
                'activo' => ['label' => 'Visible en el sitio', 'tipo' => 'checkbox', 'default' => 1],
            ],
        ],
        'hitos' => [
            'tabla' => 'hitos',
            'titulo' => 'Trayectoria',
            'singular' => 'hito',
            'orden' => 'orden ASC, id ASC',
            'columnas' => ['anio', 'titulo', 'texto', 'orden', 'activo'],
            'campos' => [
                'anio' => ['label' => 'Año / momento', 'tipo' => 'text', 'required' => true],
                'titulo' => ['label' => 'Título', 'tipo' => 'text', 'required' => true],
                'texto' => ['label' => 'Texto', 'tipo' => 'textarea'],
                'orden' => ['label' => 'Orden', 'tipo' => 'number', 'default' => 0],
                'activo' => ['label' => 'Visible en el sitio', 'tipo' => 'checkbox', 'default' => 1],
            ],
        ],
    ];
}

function crud_def(string $tabla): ?array
{
    $defs = crud_definiciones();
    return $defs[$tabla] ?? null;
}

function crud_listar(array $def): array
{
    $tabla = $def['tabla'];
    $orden = $def['orden'] ?? 'id ASC';
    return db()->query("SELECT * FROM `$tabla` ORDER BY $orden")->fetchAll();
}

function crud_obtener(array $def, int $id): ?array
{
    $tabla = $def['tabla'];
    $st = db()->prepare("SELECT * FROM `$tabla` WHERE id = ? LIMIT 1");
    $st->execute([$id]);
    $fila = $st->fetch();
    return $fila ?: null;
}

/**
 * Guarda una fila. Devuelve ['ok'=>bool, 'id'=>int, 'error'=>string].
 */
function crud_guardar(array $def, ?int $id, array $post): array
{
    $tabla = $def['tabla'];
    $actual = $id ? crud_obtener($def, $id) : null;
    $valores = [];

    foreach ($def['campos'] as $nombre => $campo) {
        $tipo = $campo['tipo'] ?? 'text';

        if ($tipo === 'checkbox') {
            $valores[$nombre] = isset($post[$nombre]) ? 1 : 0;
            continue;
        }

        if ($tipo === 'imagen') {
            $ruta = $actual[$nombre] ?? '';
            $archivos = admin_archivos($nombre);
            $archivo = $archivos[0] ?? null;

            if ($archivo && ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $subida = admin_guardar_imagen($archivo, $campo['carpeta'] ?? 'contenido');
                if (!$subida['ok']) {
                    return ['ok' => false, 'id' => 0, 'error' => $subida['error']];
                }
                if ($ruta !== '') admin_borrar_imagen($ruta);
                $ruta = $subida['ruta'];
            }
            $valores[$nombre] = $ruta;
            continue;
        }

        $valor = trim((string)($post[$nombre] ?? ''));

        if (!empty($campo['required']) && $valor === '') {
            return ['ok' => false, 'id' => 0, 'error' => 'El campo «' . ($campo['label'] ?? $nombre) . '» es obligatorio.'];
        }

        if ($tipo === 'number') $valor = (int)$valor;
        if ($tipo === 'date' && $valor === '') $valor = null;

        $valores[$nombre] = $valor;
    }

    try {
        if ($id) {
            $sets = implode(', ', array_map(fn($c) => "`$c` = :$c", array_keys($valores)));
            $st = db()->prepare("UPDATE `$tabla` SET $sets WHERE id = :id");
            $valores['id'] = $id;
            $st->execute($valores);
            $nuevoId = $id;
        } else {
            $cols = implode(', ', array_map(fn($c) => "`$c`", array_keys($valores)));
            $marcas = implode(', ', array_map(fn($c) => ":$c", array_keys($valores)));
            $st = db()->prepare("INSERT INTO `$tabla` ($cols) VALUES ($marcas)");
            $st->execute($valores);
            $nuevoId = (int)db()->lastInsertId();
        }
        return ['ok' => true, 'id' => $nuevoId, 'error' => ''];
    } catch (Throwable $ex) {
        $mensaje = (string)$ex->getCode() === '23000'
            ? 'Ya existe un registro con ese valor único.'
            : 'No se pudo guardar: ' . $ex->getMessage();
        return ['ok' => false, 'id' => 0, 'error' => $mensaje];
    }
}

function crud_eliminar(array $def, int $id): bool
{
    $tabla = $def['tabla'];
    $st = db()->prepare("DELETE FROM `$tabla` WHERE id = ?");
    $st->execute([$id]);
    return $st->rowCount() > 0;
}

/** Texto corto para tablas. */
function crud_resumen($texto, int $largo = 70): string
{
    $texto = trim(preg_replace('/\s+/', ' ', (string)$texto) ?? '');
    return mb_strlen($texto) > $largo ? mb_substr($texto, 0, $largo) . '…' : $texto;
}
