<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../includes/admin_layout.php';
requerir_admin();

$db = db();
$error = '';

if (es_post()) {
    requerir_editor();
    if (!csrf_verificar($_POST['csrf'] ?? null)) {
        $error = 'La sesión no es válida. Recarga la página.';
    } else {
        $accion = (string)($_POST['accion'] ?? '');
        $id = (int)($_POST['id'] ?? 0);

        if ($id > 0 && $accion === 'activar') {
            $db->prepare('UPDATE productos SET activo = 1 - activo WHERE id = ?')->execute([$id]);
            registrar_actividad('producto_estado', 'productos', $id, 'Cambió visibilidad');
            flash('ok', 'Visibilidad del producto actualizada.');
        }

        if ($id > 0 && $accion === 'eliminar') {
            $st = $db->prepare('SELECT COUNT(*) FROM pedidos WHERE producto_id = ?');
            $st->execute([$id]);
            $conPedidos = (int)$st->fetchColumn();

            if ($conPedidos > 0) {
                $db->prepare('UPDATE productos SET activo = 0 WHERE id = ?')->execute([$id]);
                registrar_actividad('producto_desactivado', 'productos', $id, 'Tenía pedidos asociados');
                flash('aviso', 'La pieza tiene pedidos asociados. En lugar de borrarla, se desactivó.');
            } else {
                $st = $db->prepare('SELECT imagen, galeria FROM productos WHERE id = ?');
                $st->execute([$id]);
                $p = $st->fetch();
                if ($p) {
                    admin_borrar_imagen((string)$p['imagen']);
                    $gal = json_decode((string)$p['galeria'], true);
                    if (is_array($gal)) foreach ($gal as $img) admin_borrar_imagen((string)$img);
                }
                $db->prepare('DELETE FROM productos WHERE id = ?')->execute([$id]);
                registrar_actividad('producto_eliminado', 'productos', $id);
                flash('ok', 'Producto eliminado.');
            }
        }

        if (!$error) redirigir('productos/index.php');
    }
}

$q = trim((string)($_GET['q'] ?? ''));
$estado = (string)($_GET['estado'] ?? '');
$stock = (string)($_GET['stock'] ?? '');
$pagina = max(1, (int)($_GET['pagina'] ?? 1));
$porPagina = 20;

$where = [];
$params = [];

if ($q !== '') {
    $where[] = '(nombre LIKE :q1 OR slug LIKE :q2 OR color LIKE :q3)';
    $params[':q1'] = $params[':q2'] = $params[':q3'] = '%' . $q . '%';
}
if ($estado === 'activos') $where[] = 'activo = 1';
if ($estado === 'inactivos') $where[] = 'activo = 0';
if ($stock === 'bajo') $where[] = 'stock <= 5';
if ($stock === 'agotado') $where[] = 'stock = 0';

$sqlWhere = $where ? ' WHERE ' . implode(' AND ', $where) : '';

$st = $db->prepare("SELECT COUNT(*) FROM productos$sqlWhere");
$st->execute($params);
$total = (int)$st->fetchColumn();
$paginas = max(1, (int)ceil($total / $porPagina));
$pagina = min($pagina, $paginas);
$offset = ($pagina - 1) * $porPagina;

$st = $db->prepare(
    "SELECT p.*,
            (SELECT COUNT(*) FROM pedidos pe WHERE pe.producto_id = p.id) AS pedidos
       FROM productos p
       $sqlWhere
      ORDER BY p.orden ASC, p.id ASC
      LIMIT $porPagina OFFSET $offset"
);
$st->execute($params);
$productos = $st->fetchAll();

function pagina_url(int $n): string
{
    $qs = $_GET;
    $qs['pagina'] = $n;
    return url('productos/index.php') . '?' . http_build_query($qs);
}

admin_header('Productos', 'productos', 'Catálogo, precios, stock y edición limitada.');
?>

<?php if ($error): ?><div class="admin-flash error"><?= e($error) ?></div><?php endif; ?>

<section class="admin-box">
  <?php admin_box_titulo('Catálogo', '<a class="admin-btn oro" href="' . e(url('productos/editar.php')) . '">+ Nueva pieza</a>'); ?>

  <form class="admin-filtros" method="GET" action="<?= e(url('productos/index.php')) ?>">
    <div class="admin-campo">
      <label for="q">Buscar</label>
      <input id="q" type="search" name="q" value="<?= e($q) ?>" placeholder="Nombre, slug o color">
    </div>
    <div class="admin-campo">
      <label for="estado">Estado</label>
      <select id="estado" name="estado">
        <option value="">Todos</option>
        <option value="activos"<?= $estado === 'activos' ? ' selected' : '' ?>>Activos</option>
        <option value="inactivos"<?= $estado === 'inactivos' ? ' selected' : '' ?>>Inactivos</option>
      </select>
    </div>
    <div class="admin-campo">
      <label for="stock">Stock</label>
      <select id="stock" name="stock">
        <option value="">Todos</option>
        <option value="bajo"<?= $stock === 'bajo' ? ' selected' : '' ?>>5 o menos</option>
        <option value="agotado"<?= $stock === 'agotado' ? ' selected' : '' ?>>Agotados</option>
      </select>
    </div>
    <button class="admin-btn secundario" type="submit">Filtrar</button>
    <?php if ($q !== '' || $estado !== '' || $stock !== ''): ?>
      <a class="admin-btn secundario" href="<?= e(url('productos/index.php')) ?>">Limpiar</a>
    <?php endif; ?>
  </form>

  <div class="admin-tabla-scroll">
    <table class="admin-tabla">
      <thead>
        <tr>
          <th></th>
          <th>Pieza</th>
          <th>Precio</th>
          <th>Stock</th>
          <th>Edición</th>
          <th>Pedidos</th>
          <th>Orden</th>
          <th>Estado</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$productos): ?>
          <tr><td colspan="9" class="admin-vacio">No hay productos con estos filtros.</td></tr>
        <?php endif; ?>
        <?php foreach ($productos as $p): ?>
          <tr>
            <td style="width:62px">
              <?php if ($p['imagen']): ?>
                <img class="admin-miniatura" src="<?= e(media_url((string)$p['imagen'])) ?>" alt="">
              <?php endif; ?>
            </td>
            <td>
              <a href="<?= e(url('productos/editar.php?id=' . (int)$p['id'])) ?>"><strong><?= e($p['nombre']) ?></strong></a>
              <div style="font-size:.72rem;color:var(--gris)"><?= e($p['slug']) ?> · <?= e($p['color']) ?></div>
            </td>
            <td class="num"><?= e(precio_cop($p['precio'])) ?></td>
            <td class="num"><?= (int)$p['stock'] ?></td>
            <td class="num"><?= (int)$p['edicion_total'] > 0 ? (int)$p['edicion_total'] : '—' ?></td>
            <td class="num"><?= (int)$p['pedidos'] ?></td>
            <td class="num"><?= (int)$p['orden'] ?></td>
            <td><?= (int)$p['activo'] === 1 ? '<span class="admin-badge si">Activo</span>' : '<span class="admin-badge no">Inactivo</span>' ?></td>
            <td>
              <div class="acciones">
                <a class="admin-btn secundario pequeno" href="<?= e(url('productos/editar.php?id=' . (int)$p['id'])) ?>">Editar</a>
                <form method="post" action="<?= e(url('productos/index.php')) ?>">
                  <?= csrf_campo() ?>
                  <input type="hidden" name="accion" value="activar">
                  <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                  <button class="admin-btn secundario pequeno" type="submit"><?= (int)$p['activo'] === 1 ? 'Ocultar' : 'Mostrar' ?></button>
                </form>
                <form method="post" action="<?= e(url('productos/index.php')) ?>">
                  <?= csrf_campo() ?>
                  <input type="hidden" name="accion" value="eliminar">
                  <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                  <button class="admin-btn peligro pequeno" type="submit" data-confirm="¿Eliminar «<?= e($p['nombre']) ?>»? Si tiene pedidos, solo se desactivará.">Eliminar</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if ($paginas > 1): ?>
    <nav class="admin-paginacion" aria-label="Paginación">
      <?php for ($i = 1; $i <= $paginas; $i++): ?>
        <?php if ($i === $pagina): ?>
          <span class="actual"><?= $i ?></span>
        <?php else: ?>
          <a href="<?= e(pagina_url($i)) ?>"><?= $i ?></a>
        <?php endif; ?>
      <?php endfor; ?>
    </nav>
  <?php endif; ?>
</section>

<?php admin_footer(); ?>
