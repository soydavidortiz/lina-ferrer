<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../includes/admin_layout.php';
requerir_admin();

$db = db();
$id = (int)($_GET['id'] ?? 0);
$producto = null;
$errores = [];

if ($id > 0) {
    $st = $db->prepare('SELECT * FROM productos WHERE id = ? LIMIT 1');
    $st->execute([$id]);
    $producto = $st->fetch() ?: null;
    if (!$producto) {
        flash('error', 'El producto no existe.');
        redirigir('productos/index.php');
    }
}

$valores = [
    'slug' => $producto['slug'] ?? '',
    'nombre' => $producto['nombre'] ?? '',
    'color' => $producto['color'] ?? '',
    'detalle' => $producto['detalle'] ?? '',
    'descripcion' => $producto['descripcion'] ?? '',
    'precio' => $producto['precio'] ?? 0,
    'stock' => $producto['stock'] ?? 0,
    'edicion_total' => $producto['edicion_total'] ?? 0,
    'imagen' => $producto['imagen'] ?? '',
    'galeria' => $producto['galeria'] ?? '[]',
    'especificaciones' => $producto['especificaciones'] ?? '[]',
    'activo' => $producto['activo'] ?? 1,
    'orden' => $producto['orden'] ?? 0,
];

if ($id === 0) {
    $siguiente = (int)$db->query('SELECT COALESCE(MAX(orden), 0) + 1 FROM productos')->fetchColumn();
    $valores['orden'] = $siguiente;
}

$galeria = json_decode((string)$valores['galeria'], true);
if (!is_array($galeria)) $galeria = [];

$specs = json_decode((string)$valores['especificaciones'], true);
if (!is_array($specs)) $specs = [];

$specsTexto = implode("\n", array_map(
    fn($s) => trim((string)($s['etiqueta'] ?? '')) . ' | ' . trim((string)($s['valor'] ?? '')),
    $specs
));

if (es_post()) {
    requerir_editor();
    if (!csrf_verificar($_POST['csrf'] ?? null)) {
        $errores[] = 'La sesión no es válida. Recarga la página.';
    } else {
        $accion = (string)($_POST['accion'] ?? 'guardar');

        if ($accion === 'eliminar' && $id > 0) {
            $st = $db->prepare('SELECT COUNT(*) FROM pedidos WHERE producto_id = ?');
            $st->execute([$id]);
            $conPedidos = (int)$st->fetchColumn();

            if ($conPedidos > 0) {
                $db->prepare('UPDATE productos SET activo = 0 WHERE id = ?')->execute([$id]);
                registrar_actividad('producto_desactivado', 'productos', $id, 'Tenía pedidos asociados');
                flash('aviso', 'La pieza tiene pedidos asociados. Se desactivó en lugar de borrarse.');
            } else {
                admin_borrar_imagen((string)$producto['imagen']);
                foreach ($galeria as $img) admin_borrar_imagen((string)$img);
                $db->prepare('DELETE FROM productos WHERE id = ?')->execute([$id]);
                registrar_actividad('producto_eliminado', 'productos', $id);
                flash('ok', 'Producto eliminado.');
            }
            redirigir('productos/index.php');
        }

        // ---------- Datos del formulario ----------
        $valores['nombre'] = trim((string)($_POST['nombre'] ?? ''));
        $valores['color'] = trim((string)($_POST['color'] ?? ''));
        $valores['detalle'] = trim((string)($_POST['detalle'] ?? ''));
        $valores['descripcion'] = trim((string)($_POST['descripcion'] ?? ''));
        $valores['precio'] = max(0, (int)($_POST['precio'] ?? 0));
        $valores['stock'] = max(0, (int)($_POST['stock'] ?? 0));
        $valores['edicion_total'] = max(0, (int)($_POST['edicion_total'] ?? 0));
        $valores['activo'] = isset($_POST['activo']) ? 1 : 0;
        $valores['orden'] = (int)($_POST['orden'] ?? 0);

        $slug = slugificar((string)($_POST['slug'] ?? ''));
        if ($slug === '') $slug = slugificar($valores['nombre']);

        if ($valores['nombre'] === '') $errores[] = 'El nombre es obligatorio.';
        if ($valores['detalle'] === '') $errores[] = 'El detalle corto es obligatorio.';
        // Precio 0 se permite: el frontend lo muestra como «Precio a convenir».

        // Slug único
        $st = $db->prepare('SELECT id FROM productos WHERE slug = ? AND id <> ? LIMIT 1');
        $st->execute([$slug, $id]);
        if ($st->fetchColumn()) {
            $base = $slug;
            $n = 2;
            do {
                $slug = $base . '-' . $n++;
                $st->execute([$slug, $id]);
            } while ($st->fetchColumn());
        }
        $valores['slug'] = $slug;

        // Imagen principal
        $archivoImagen = admin_archivos('imagen')[0] ?? null;
        if ($archivoImagen && ($archivoImagen['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $subida = admin_guardar_imagen($archivoImagen, 'productos');
            if ($subida['ok']) {
                if ($valores['imagen'] !== '') admin_borrar_imagen((string)$valores['imagen']);
                $valores['imagen'] = $subida['ruta'];
            } else {
                $errores[] = $subida['error'];
            }
        }

        // Galería: líneas del textarea + archivos nuevos
        $lineas = preg_split('/\r\n|\r|\n/', (string)($_POST['galeria'] ?? '')) ?: [];
        $nuevaGaleria = [];
        foreach ($lineas as $linea) {
            $linea = trim($linea);
            if ($linea !== '') $nuevaGaleria[] = $linea;
        }

        $subidas = admin_guardar_imagenes('galeria_nuevas', 'productos');
        foreach ($subidas['errores'] as $err) $errores[] = $err;
        $nuevaGaleria = array_values(array_unique(array_merge($nuevaGaleria, $subidas['rutas'])));

        if ($subidas['rutas']) {
            // La imagen principal también entra a la galería si estaba vacía
            if (!$nuevaGaleria) $nuevaGaleria = $subidas['rutas'];
        }

        // Borrar archivos que se quitaron de la galería
        foreach (array_diff($galeria, $nuevaGaleria) as $vieja) admin_borrar_imagen((string)$vieja);

        $valores['galeria'] = json_encode($nuevaGaleria, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $galeria = $nuevaGaleria;

        // Especificaciones: "Etiqueta | Valor" por línea
        $specsNuevas = [];
        $lineasSpecs = preg_split('/\r\n|\r|\n/', (string)($_POST['especificaciones'] ?? '')) ?: [];
        foreach ($lineasSpecs as $linea) {
            $linea = trim($linea);
            if ($linea === '') continue;
            if (!str_contains($linea, '|')) {
                $errores[] = 'Cada especificación debe tener el formato «Etiqueta | Valor».';
                break;
            }
            [$etiqueta, $valor] = array_map('trim', explode('|', $linea, 2));
            if ($etiqueta !== '' && $valor !== '') {
                $specsNuevas[] = ['etiqueta' => $etiqueta, 'valor' => $valor];
            }
        }
        $valores['especificaciones'] = json_encode($specsNuevas, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $specsTexto = implode("\n", array_map(fn($s) => $s['etiqueta'] . ' | ' . $s['valor'], $specsNuevas));

        if (!$errores) {
            try {
                if ($id > 0) {
                    $st = $db->prepare(
                        'UPDATE productos SET
                            slug = :slug, nombre = :nombre, color = :color, detalle = :detalle,
                            descripcion = :descripcion, precio = :precio, stock = :stock,
                            edicion_total = :edicion_total, imagen = :imagen, galeria = :galeria,
                            especificaciones = :especificaciones, activo = :activo, orden = :orden
                         WHERE id = :id'
                    );
                    $st->execute($valores + ['id' => $id]);
                    registrar_actividad('producto_actualizado', 'productos', $id, $valores['nombre']);
                    flash('ok', 'Producto actualizado.');
                    redirigir('productos/editar.php?id=' . $id);
                } else {
                    $st = $db->prepare(
                        'INSERT INTO productos
                            (slug, nombre, color, detalle, descripcion, precio, stock, edicion_total,
                             imagen, galeria, especificaciones, activo, orden)
                         VALUES
                            (:slug, :nombre, :color, :detalle, :descripcion, :precio, :stock, :edicion_total,
                             :imagen, :galeria, :especificaciones, :activo, :orden)'
                    );
                    $st->execute($valores);
                    $nuevoId = (int)$db->lastInsertId();
                    registrar_actividad('producto_creado', 'productos', $nuevoId, $valores['nombre']);
                    flash('ok', 'Producto creado.');
                    redirigir('productos/editar.php?id=' . $nuevoId);
                }
            } catch (Throwable $ex) {
                $errores[] = 'No se pudo guardar: ' . $ex->getMessage();
            }
        }
    }
}

admin_header(
    $id > 0 ? 'Editar producto' : 'Nuevo producto',
    'productos',
    $id > 0 ? 'Modifica la ficha, el precio, el stock y las imágenes de la pieza.' : 'Crea una nueva pieza para la colección.'
);
?>

<?php foreach ($errores as $err): ?>
  <div class="admin-flash error"><?= e($err) ?></div>
<?php endforeach; ?>

<form method="post" enctype="multipart/form-data" action="<?= e(url('productos/editar.php' . ($id > 0 ? '?id=' . $id : ''))) ?>">
  <?= csrf_campo() ?>
  <input type="hidden" name="accion" value="guardar">

  <div class="admin-grid-2">
    <section class="admin-box">
      <?php admin_box_titulo('Datos de la pieza'); ?>
      <div class="admin-box-cuerpo">
        <div class="admin-form">
          <div class="admin-grid-2-form">
            <div class="admin-campo">
              <label for="nombre">Nombre *</label>
              <input id="nombre" name="nombre" type="text" maxlength="120" required
                     data-slug-source value="<?= e($valores['nombre']) ?>">
            </div>
            <div class="admin-campo">
              <label for="slug">Slug (URL)</label>
              <input id="slug" name="slug" type="text" maxlength="80" data-slug-target
                     value="<?= e($valores['slug']) ?>" placeholder="se-genera-automatico">
              <span class="ayuda">Se usa en producto.html?slug=...</span>
            </div>
          </div>

          <div class="admin-grid-2-form">
            <div class="admin-campo">
              <label for="color">Color</label>
              <input id="color" name="color" type="text" maxlength="60" value="<?= e($valores['color']) ?>">
            </div>
            <div class="admin-campo">
              <label for="detalle">Detalle corto *</label>
              <input id="detalle" name="detalle" type="text" maxlength="200" required
                     value="<?= e($valores['detalle']) ?>" placeholder="Vino tinto · Lira en filigrana de oro con esmeralda">
            </div>
          </div>

          <div class="admin-campo">
            <label for="descripcion">Descripción</label>
            <textarea id="descripcion" name="descripcion" rows="7"><?= e($valores['descripcion']) ?></textarea>
          </div>

          <div class="admin-grid-2-form">
            <div class="admin-campo">
              <label for="precio">Precio (COP) *</label>
              <input id="precio" name="precio" type="number" min="0" step="1000" required value="<?= (int)$valores['precio'] ?>">
            </div>
            <div class="admin-campo">
              <label for="edicion_total">Total de la edición</label>
              <input id="edicion_total" name="edicion_total" type="number" min="0" value="<?= (int)$valores['edicion_total'] ?>">
            </div>
          </div>

          <div class="admin-grid-2-form">
            <div class="admin-campo">
              <label for="stock">Stock disponible</label>
              <input id="stock" name="stock" type="number" min="0" value="<?= (int)$valores['stock'] ?>">
            </div>
            <div class="admin-campo">
              <label for="orden">Orden en la colección</label>
              <input id="orden" name="orden" type="number" value="<?= (int)$valores['orden'] ?>">
            </div>
          </div>

          <div class="admin-campo">
            <label class="admin-check">
              <input type="checkbox" name="activo" value="1"<?= (int)$valores['activo'] === 1 ? ' checked' : '' ?>>
              <span>Visible en el sitio</span>
            </label>
          </div>
        </div>
      </div>
    </section>

    <div>
      <section class="admin-box">
        <?php admin_box_titulo('Imagen principal'); ?>
        <div class="admin-box-cuerpo">
          <div class="admin-campo">
            <label for="imagen">Subir imagen</label>
            <input id="imagen" name="imagen" type="file" accept="image/jpeg,image/png,image/webp" data-preview="previewPrincipal">
            <span class="ayuda">JPG, PNG o WebP · máximo 8 MB. Se convierte a WebP.</span>
          </div>
          <div class="admin-preview<?= $valores['imagen'] ? '' : ' vacia' ?>">
            <img id="previewPrincipal" src="<?= e(media_url((string)$valores['imagen'])) ?>" alt=""
                 <?= $valores['imagen'] ? '' : 'hidden' ?>>
            <?php if (!$valores['imagen']): ?><span>Sin imagen</span><?php endif; ?>
          </div>
          <?php if ($valores['imagen']): ?>
            <p class="ayuda" style="margin-top:8px"><?= e($valores['imagen']) ?></p>
          <?php endif; ?>
        </div>
      </section>

      <section class="admin-box">
        <?php admin_box_titulo('Galería'); ?>
        <div class="admin-box-cuerpo">
          <div class="admin-campo">
            <label for="galeria">Rutas de imágenes (una por línea)</label>
            <textarea id="galeria" name="galeria" rows="5"><?= e(implode("\n", $galeria)) ?></textarea>
          </div>
          <div class="admin-campo">
            <label for="galeria_nuevas">Añadir imágenes</label>
            <input id="galeria_nuevas" name="galeria_nuevas[]" type="file" multiple
                   accept="image/jpeg,image/png,image/webp">
          </div>
          <?php if ($galeria): ?>
            <div class="admin-galeria-mini">
              <?php foreach ($galeria as $img): ?>
                <img class="admin-miniatura" src="<?= e(media_url((string)$img)) ?>" alt="">
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </section>
    </div>
  </div>

  <section class="admin-box">
    <?php admin_box_titulo('Especificaciones'); ?>
    <div class="admin-box-cuerpo">
      <div class="admin-campo">
        <label for="especificaciones">Una por línea, con formato «Etiqueta | Valor»</label>
        <textarea id="especificaciones" name="especificaciones" rows="6" placeholder="Piel | Vacuno de primera calidad&#10;Asas | Trenzadas a mano&#10;Medidas | 28 × 22 × 12 cm"><?= e($specsTexto) ?></textarea>
      </div>
    </div>
  </section>

  <div class="admin-acciones-form">
    <button class="admin-btn oro" type="submit"><?= $id > 0 ? 'Guardar cambios' : 'Crear producto' ?></button>
    <a class="admin-btn secundario" href="<?= e(url('productos/index.php')) ?>">Cancelar</a>
    <?php if ($id > 0): ?>
      <a class="admin-btn secundario" href="<?= e(frontend_url('producto.html?slug=' . urlencode((string)$valores['slug']))) ?>" target="_blank" rel="noopener">Ver en el sitio ↗</a>
      <button class="admin-btn peligro" type="submit" name="accion" value="eliminar"
              data-confirm="¿Eliminar «<?= e($valores['nombre']) ?>»? Si tiene pedidos, solo se desactivará.">Eliminar</button>
    <?php endif; ?>
  </div>
</form>

<?php admin_footer(); ?>
