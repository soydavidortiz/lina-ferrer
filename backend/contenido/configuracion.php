<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../includes/admin_layout.php';
requerir_admin();

$db = db();
$grupos = [
    'general' => 'Marca y datos generales',
    'contacto' => 'Contacto y WhatsApp',
    'inicio' => 'Portada y galería',
    'manifiesto' => 'Manifiesto',
    'coleccion' => 'Colección',
    'filosofia' => 'Filosofía de marca',
    'historia' => 'Historia y trayectoria',
    'prensa' => 'Prensa y reconocimientos',
    'faq' => 'Preguntas frecuentes',
    'cierre' => 'Llamado final',
    'producto' => 'Página de producto',
    'tienda' => 'Tienda y checkout',
];

$grupo = (string)($_GET['grupo'] ?? 'general');
if (!isset($grupos[$grupo])) $grupo = 'general';
$errores = [];

if (es_post()) {
    requerir_editor();
    if (!csrf_verificar($_POST['csrf'] ?? null)) {
        $errores[] = 'La sesión no es válida. Recarga la página.';
    } else {
        $grupoPost = (string)($_POST['grupo'] ?? $grupo);
        if (!isset($grupos[$grupoPost])) $grupoPost = $grupo;

        $valores = $_POST['valor'] ?? [];
        $archivos = $_FILES['archivo'] ?? [];

        $stConfig = $db->prepare('SELECT clave, tipo FROM sitio_config WHERE grupo = ?');
        $stConfig->execute([$grupoPost]);
        $claves = $stConfig->fetchAll();

        $stUpdate = $db->prepare('UPDATE sitio_config SET valor = ? WHERE clave = ?');

        try {
            $db->beginTransaction();
            foreach ($claves as $c) {
                $clave = $c['clave'];
                $nuevoValor = trim((string)($valores[$clave] ?? ''));

                // Subida de imagen para claves de tipo imagen
                if ($c['tipo'] === 'imagen' && is_array($archivos) && isset($archivos['name'][$clave])) {
                    $archivo = [
                        'name' => $archivos['name'][$clave],
                        'type' => $archivos['type'][$clave] ?? '',
                        'tmp_name' => $archivos['tmp_name'][$clave] ?? '',
                        'error' => (int)($archivos['error'][$clave] ?? UPLOAD_ERR_NO_FILE),
                        'size' => (int)($archivos['size'][$clave] ?? 0),
                    ];
                    if ($archivo['error'] !== UPLOAD_ERR_NO_FILE) {
                        $subida = admin_guardar_imagen($archivo, 'contenido');
                        if (!$subida['ok']) {
                            throw new RuntimeException($subida['error']);
                        }
                        if ($nuevoValor !== '') admin_borrar_imagen($nuevoValor);
                        $nuevoValor = $subida['ruta'];
                    }
                }

                $stUpdate->execute([$nuevoValor, $clave]);
            }
            $db->commit();
            registrar_actividad('contenido_actualizado', 'sitio_config', null, $grupos[$grupoPost]);
            flash('ok', 'Textos guardados. Recarga el sitio para verlos.');
            redirigir('contenido/configuracion.php?grupo=' . urlencode($grupoPost));
        } catch (Throwable $ex) {
            if ($db->inTransaction()) $db->rollBack();
            $errores[] = $ex->getMessage();
        }
    }
}

$st = $db->prepare('SELECT * FROM sitio_config WHERE grupo = ? ORDER BY clave ASC');
$st->execute([$grupo]);
$campos = $st->fetchAll();

admin_header('Textos del sitio', 'contenido', 'Edita el contenido que alimenta la portada, la historia y el pie de página.');
?>

<?php foreach ($errores as $err): ?>
  <div class="admin-flash error"><?= e($err) ?></div>
<?php endforeach; ?>

<nav class="admin-tabs" aria-label="Grupos de contenido">
  <?php foreach ($grupos as $clave => $nombre): ?>
    <a class="<?= $grupo === $clave ? 'activo' : '' ?>" href="<?= e(url('contenido/configuracion.php?grupo=' . urlencode($clave))) ?>"><?= e($nombre) ?></a>
  <?php endforeach; ?>
</nav>

<form class="admin-form" method="post" enctype="multipart/form-data" action="<?= e(url('contenido/configuracion.php?grupo=' . urlencode($grupo))) ?>">
  <?= csrf_campo() ?>
  <input type="hidden" name="grupo" value="<?= e($grupo) ?>">

  <section class="admin-box">
    <?php admin_box_titulo($grupos[$grupo], '<span class="admin-badge no">' . count($campos) . ' campo(s)</span>'); ?>
    <div class="admin-box-cuerpo">
      <?php if (!$campos): ?>
        <p class="admin-vacio">No hay campos en este grupo.</p>
      <?php endif; ?>

      <div class="admin-form">
        <?php foreach ($campos as $campo): ?>
          <?php $tipo = $campo['tipo'] ?: 'texto'; ?>
          <div class="admin-campo<?= $tipo === 'textarea' ? ' completo' : '' ?>">
            <label for="campo-<?= e($campo['clave']) ?>"><?= e($campo['etiqueta'] ?: $campo['clave']) ?></label>

            <?php if ($tipo === 'textarea'): ?>
              <textarea id="campo-<?= e($campo['clave']) ?>" name="valor[<?= e($campo['clave']) ?>]" rows="5"<?= puede_editar() ? '' : ' disabled' ?>><?= e($campo['valor']) ?></textarea>

            <?php elseif ($tipo === 'imagen'): ?>
              <div style="display:grid;grid-template-columns:minmax(0,1fr) 190px;gap:16px;align-items:start">
                <div>
                  <input id="campo-<?= e($campo['clave']) ?>" type="text" name="valor[<?= e($campo['clave']) ?>]"
                         value="<?= e($campo['valor']) ?>"<?= puede_editar() ? '' : ' disabled' ?>>
                  <span class="ayuda">Ruta o URL de la imagen. También puedes subir una nueva.</span>
                  <?php if (puede_editar()): ?>
                    <input style="margin-top:8px" type="file" name="archivo[<?= e($campo['clave']) ?>]"
                           accept="image/jpeg,image/png,image/webp" data-preview="preview-<?= e($campo['clave']) ?>">
                  <?php endif; ?>
                </div>
                <div class="admin-preview<?= $campo['valor'] ? '' : ' vacia' ?>">
                  <img id="preview-<?= e($campo['clave']) ?>" src="<?= e(media_url((string)$campo['valor'])) ?>" alt=""
                       <?= $campo['valor'] ? '' : 'hidden' ?>>
                  <?php if (!$campo['valor']): ?><span>Sin imagen</span><?php endif; ?>
                </div>
              </div>

            <?php else: ?>
              <?php
                $inputType = match ($tipo) {
                    'email' => 'email',
                    'url' => 'url',
                    'telefono' => 'tel',
                    'numero' => 'number',
                    default => 'text',
                };
              ?>
              <input id="campo-<?= e($campo['clave']) ?>" type="<?= e($inputType) ?>"
                     name="valor[<?= e($campo['clave']) ?>]" value="<?= e($campo['valor']) ?>"
                     <?= $inputType === 'url' ? 'placeholder="https://..."' : '' ?>
                     <?= puede_editar() ? '' : ' disabled' ?>>
            <?php endif; ?>

            <span class="ayuda"><code><?= e($campo['clave']) ?></code></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <?php if (puede_editar()): ?>
    <div class="admin-acciones-form">
      <button class="admin-btn oro" type="submit">Guardar cambios</button>
      <a class="admin-btn secundario" href="<?= e(frontend_url('index.html')) ?>" target="_blank" rel="noopener">Ver el sitio ↗</a>
    </div>
  <?php endif; ?>
</form>

<?php admin_footer(); ?>
