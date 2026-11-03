<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../includes/admin_layout.php';
require_once __DIR__ . '/../includes/crud.php';
requerir_admin();

$db = db();
$tablaClave = (string)($_GET['tabla'] ?? '');
$def = crud_def($tablaClave);

if (!$def) {
    flash('error', 'Sección de contenido no válida.');
    redirigir('contenido/configuracion.php');
}

$errores = [];
$editarId = (int)($_GET['editar'] ?? 0);
$nuevo = isset($_GET['nuevo']);
$fila = $editarId > 0 ? crud_obtener($def, $editarId) : null;

if ($editarId > 0 && !$fila) {
    flash('error', 'El registro no existe.');
    redirigir('contenido/tabla.php?tabla=' . urlencode($tablaClave));
}

$accionUrl = url('contenido/tabla.php?tabla=' . urlencode($tablaClave));

if (es_post()) {
    requerir_editor();
    if (!csrf_verificar($_POST['csrf'] ?? null)) {
        $errores[] = 'La sesión no es válida. Recarga la página.';
    } else {
        $accion = (string)($_POST['accion'] ?? 'guardar');
        $id = (int)($_POST['id'] ?? 0);

        if ($accion === 'eliminar' && $id > 0) {
            $actual = crud_obtener($def, $id);
            if ($actual) {
                foreach ($def['campos'] as $nombre => $campo) {
                    if (($campo['tipo'] ?? '') === 'imagen') admin_borrar_imagen((string)($actual[$nombre] ?? ''));
                }
            }
            crud_eliminar($def, $id);
            registrar_actividad('contenido_eliminado', $def['tabla'], $id);
            flash('ok', 'Registro eliminado.');
            redirigir('contenido/tabla.php?tabla=' . urlencode($tablaClave));
        }

        if ($accion === 'guardar') {
            $resultado = crud_guardar($def, $id > 0 ? $id : null, $_POST);
            if ($resultado['ok']) {
                registrar_actividad(
                    $id > 0 ? 'contenido_actualizado' : 'contenido_creado',
                    $def['tabla'],
                    $resultado['id']
                );
                flash('ok', 'Registro guardado.');
                redirigir('contenido/tabla.php?tabla=' . urlencode($tablaClave));
            }
            $errores[] = $resultado['error'];
            $fila = $id > 0 ? crud_obtener($def, $id) : null;
        }
    }
}

$mostrarFormulario = $nuevo || $editarId > 0;
$filas = $mostrarFormulario ? [] : crud_listar($def);

$valores = [];
foreach ($def['campos'] as $nombre => $campo) {
    $valores[$nombre] = $fila[$nombre] ?? ($campo['default'] ?? '');
}

admin_header(
    $def['titulo'],
    'contenido',
    $mostrarFormulario
        ? ($editarId > 0 ? 'Edita el registro seleccionado.' : 'Crea un nuevo registro para esta sección.')
        : 'Administra el contenido que se muestra en el sitio.'
);
?>

<?php foreach ($errores as $err): ?>
  <div class="admin-flash error"><?= e($err) ?></div>
<?php endforeach; ?>

<?php if ($mostrarFormulario): ?>

  <div class="admin-acciones-form" style="margin-bottom:18px">
    <a class="admin-btn secundario" href="<?= e($accionUrl) ?>">← Volver al listado</a>
  </div>

  <form class="admin-form" method="post" enctype="multipart/form-data" action="<?= e($accionUrl . ($editarId > 0 ? '&editar=' . $editarId : '')) ?>">
    <?= csrf_campo() ?>
    <input type="hidden" name="accion" value="guardar">
    <?php if ($editarId > 0): ?><input type="hidden" name="id" value="<?= $editarId ?>"><?php endif; ?>

    <section class="admin-box">
      <?php admin_box_titulo($editarId > 0 ? 'Editar registro' : 'Nuevo registro'); ?>
      <div class="admin-box-cuerpo">
        <div class="admin-form">
          <div class="admin-grid-2-form">
            <?php foreach ($def['campos'] as $nombre => $campo): ?>
              <?php
                $tipo = $campo['tipo'] ?? 'text';
                $label = $campo['label'] ?? $nombre;
                $valor = $valores[$nombre] ?? '';
              ?>
              <div class="admin-campo<?= in_array($tipo, ['textarea', 'imagen'], true) ? ' completo' : '' ?>">
                <label for="campo-<?= e($nombre) ?>"><?= e($label) ?><?= !empty($campo['required']) ? ' *' : '' ?></label>

                <?php if ($tipo === 'textarea'): ?>
                  <textarea id="campo-<?= e($nombre) ?>" name="<?= e($nombre) ?>" rows="5"<?= puede_editar() ? '' : ' disabled' ?>><?= e($valor) ?></textarea>

                <?php elseif ($tipo === 'select'): ?>
                  <select id="campo-<?= e($nombre) ?>" name="<?= e($nombre) ?>"<?= puede_editar() ? '' : ' disabled' ?>>
                    <?php foreach (($campo['opciones'] ?? []) as $opcion => $texto): ?>
                      <option value="<?= e($opcion) ?>"<?= (string)$valor === (string)$opcion ? ' selected' : '' ?>><?= e($texto) ?></option>
                    <?php endforeach; ?>
                  </select>

                <?php elseif ($tipo === 'checkbox'): ?>
                  <label class="admin-check">
                    <input type="checkbox" id="campo-<?= e($nombre) ?>" name="<?= e($nombre) ?>" value="1"
                           <?= (int)$valor === 1 ? 'checked' : '' ?><?= puede_editar() ? '' : ' disabled' ?>>
                    <span>Activo</span>
                  </label>

                <?php elseif ($tipo === 'imagen'): ?>
                  <div style="display:grid;grid-template-columns:minmax(0,1fr) 190px;gap:16px;align-items:start">
                    <div>
                      <?php if (puede_editar()): ?>
                        <input id="campo-<?= e($nombre) ?>" type="file" name="<?= e($nombre) ?>"
                               accept="image/jpeg,image/png,image/webp" data-preview="preview-<?= e($nombre) ?>">
                        <span class="ayuda">JPG, PNG o WebP · máximo 8 MB. Se convierte a WebP.</span>
                      <?php endif; ?>
                      <?php if ($valor): ?><div class="ayuda" style="margin-top:8px"><?= e($valor) ?></div><?php endif; ?>
                    </div>
                    <div class="admin-preview<?= $valor ? '' : ' vacia' ?>">
                      <img id="preview-<?= e($nombre) ?>" src="<?= e(media_url((string)$valor)) ?>" alt=""
                           <?= $valor ? '' : 'hidden' ?>>
                      <?php if (!$valor): ?><span>Sin imagen</span><?php endif; ?>
                    </div>
                  </div>

                <?php else: ?>
                  <?php
                    $inputType = match ($tipo) {
                        'number' => 'number',
                        'date' => 'date',
                        'url' => 'url',
                        default => 'text',
                    };
                  ?>
                  <input id="campo-<?= e($nombre) ?>" type="<?= e($inputType) ?>" name="<?= e($nombre) ?>"
                         value="<?= e($valor) ?>"<?= !empty($campo['required']) ? ' required' : '' ?>
                         <?= puede_editar() ? '' : ' disabled' ?>>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </section>

    <?php if (puede_editar()): ?>
      <div class="admin-acciones-form">
        <button class="admin-btn oro" type="submit">Guardar</button>
        <a class="admin-btn secundario" href="<?= e($accionUrl) ?>">Cancelar</a>
      </div>
    <?php endif; ?>
  </form>

<?php else: ?>

  <section class="admin-box">
    <?php admin_box_titulo($def['titulo'], '<a class="admin-btn oro" href="' . e($accionUrl . '&nuevo=1') . '">+ Nuevo</a>'); ?>

    <div class="admin-tabla-scroll">
      <table class="admin-tabla">
        <thead>
          <tr>
            <th>ID</th>
            <?php foreach ($def['columnas'] as $col): ?>
              <th><?= e($def['campos'][$col]['label'] ?? $col) ?></th>
            <?php endforeach; ?>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$filas): ?>
            <tr><td colspan="<?= count($def['columnas']) + 2 ?>" class="admin-vacio">Todavía no hay registros.</td></tr>
          <?php endif; ?>
          <?php foreach ($filas as $f): ?>
            <tr>
              <td class="num"><?= (int)$f['id'] ?></td>
              <?php foreach ($def['columnas'] as $col): ?>
                <?php $tipo = $def['campos'][$col]['tipo'] ?? 'text'; ?>
                <td>
                  <?php if ($tipo === 'imagen'): ?>
                    <?php if ($f[$col]): ?>
                      <img class="admin-miniatura" src="<?= e(media_url((string)$f[$col])) ?>" alt="">
                    <?php else: ?>
                      —
                    <?php endif; ?>
                  <?php elseif ($tipo === 'checkbox'): ?>
                    <?= (int)$f[$col] === 1 ? '<span class="admin-badge si">Sí</span>' : '<span class="admin-badge no">No</span>' ?>
                  <?php elseif ($tipo === 'date'): ?>
                    <?= e($f[$col] ? fecha_legible((string)$f[$col]) : '—') ?>
                  <?php elseif ($tipo === 'textarea'): ?>
                    <?= e(crud_resumen($f[$col], 80)) ?>
                  <?php else: ?>
                    <?= e($f[$col]) ?>
                  <?php endif; ?>
                </td>
              <?php endforeach; ?>
              <td>
                <div class="acciones">
                  <a class="admin-btn secundario pequeno" href="<?= e($accionUrl . '&editar=' . (int)$f['id']) ?>">Editar</a>
                  <form method="post" action="<?= e($accionUrl) ?>">
                    <?= csrf_campo() ?>
                    <input type="hidden" name="accion" value="eliminar">
                    <input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
                    <button class="admin-btn peligro pequeno" type="submit" data-confirm="¿Eliminar este registro?">Eliminar</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>

<?php endif; ?>

<?php admin_footer(); ?>
