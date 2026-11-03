<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../includes/admin_layout.php';
requerir_admin();

$db = db();

$q = trim((string)($_GET['q'] ?? ''));
$estado = (string)($_GET['estado'] ?? '');
$desde = trim((string)($_GET['desde'] ?? ''));
$hasta = trim((string)($_GET['hasta'] ?? ''));
$pagina = max(1, (int)($_GET['pagina'] ?? 1));
$porPagina = 20;

$where = [];
$params = [];

if ($q !== '') {
    $where[] = '(p.codigo LIKE :q1 OR c.nombre LIKE :q2 OR c.email LIKE :q3 OR c.telefono LIKE :q4)';
    $params[':q1'] = $params[':q2'] = $params[':q3'] = $params[':q4'] = '%' . $q . '%';
}
if (in_array($estado, ['pendiente', 'confirmado', 'enviado', 'entregado', 'cancelado'], true)) {
    $where[] = 'p.estado = :estado';
    $params[':estado'] = $estado;
}
if ($desde !== '') {
    $where[] = 'p.creado_en >= :desde';
    $params[':desde'] = $desde . ' 00:00:00';
}
if ($hasta !== '') {
    $where[] = 'p.creado_en <= :hasta';
    $params[':hasta'] = $hasta . ' 23:59:59';
}

$sqlWhere = $where ? ' WHERE ' . implode(' AND ', $where) : '';

$st = $db->prepare(
    "SELECT COUNT(*) FROM pedidos p JOIN clientes c ON c.id = p.cliente_id$sqlWhere"
);
$st->execute($params);
$total = (int)$st->fetchColumn();
$paginas = max(1, (int)ceil($total / $porPagina));
$pagina = min($pagina, $paginas);
$offset = ($pagina - 1) * $porPagina;

$st = $db->prepare(
    "SELECT p.*, c.nombre AS cliente, c.email, c.telefono, pr.nombre AS producto
       FROM pedidos p
       JOIN clientes c ON c.id = p.cliente_id
       JOIN productos pr ON pr.id = p.producto_id
       $sqlWhere
      ORDER BY p.creado_en DESC, p.id DESC
      LIMIT $porPagina OFFSET $offset"
);
$st->execute($params);
$pedidos = $st->fetchAll();

$conteos = [];
foreach ($db->query('SELECT estado, COUNT(*) n FROM pedidos GROUP BY estado')->fetchAll() as $f) {
    $conteos[$f['estado']] = (int)$f['n'];
}
$totalPedidos = array_sum($conteos);

function pedidos_pagina_url(int $n): string
{
    $qs = $_GET;
    $qs['pagina'] = $n;
    return url('pedidos/index.php') . '?' . http_build_query($qs);
}

admin_header('Pedidos', 'pedidos', 'Seguimiento, confirmación y cambio de estado de cada pedido.');
?>

<div class="admin-stats">
  <div class="admin-stat">
    <small>Total</small>
    <strong><?= $totalPedidos ?></strong>
    <span>Todos los pedidos</span>
  </div>
  <div class="admin-stat oro">
    <small>Pendientes</small>
    <strong><?= (int)($conteos['pendiente'] ?? 0) ?></strong>
    <span>Por confirmar</span>
  </div>
  <div class="admin-stat">
    <small>En proceso</small>
    <strong><?= (int)($conteos['confirmado'] ?? 0) + (int)($conteos['enviado'] ?? 0) ?></strong>
    <span>Confirmados y enviados</span>
  </div>
  <div class="admin-stat">
    <small>Entregados</small>
    <strong><?= (int)($conteos['entregado'] ?? 0) ?></strong>
    <span>Pedidos completados</span>
  </div>
</div>

<section class="admin-box">
  <?php admin_box_titulo('Listado de pedidos', '<span class="admin-badge no">' . $total . ' resultado(s)</span>'); ?>

  <form class="admin-filtros" method="GET" action="<?= e(url('pedidos/index.php')) ?>">
    <div class="admin-campo" style="min-width:240px">
      <label for="q">Buscar</label>
      <input id="q" type="search" name="q" value="<?= e($q) ?>" placeholder="Código, cliente, correo o teléfono">
    </div>
    <div class="admin-campo">
      <label for="estado">Estado</label>
      <select id="estado" name="estado">
        <option value="">Todos</option>
        <?php foreach (['pendiente', 'confirmado', 'enviado', 'entregado', 'cancelado'] as $e): ?>
          <option value="<?= e($e) ?>"<?= $estado === $e ? ' selected' : '' ?>><?= e(ucfirst($e)) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="admin-campo">
      <label for="desde">Desde</label>
      <input id="desde" type="date" name="desde" value="<?= e($desde) ?>">
    </div>
    <div class="admin-campo">
      <label for="hasta">Hasta</label>
      <input id="hasta" type="date" name="hasta" value="<?= e($hasta) ?>">
    </div>
    <button class="admin-btn secundario" type="submit">Filtrar</button>
    <?php if ($q !== '' || $estado !== '' || $desde !== '' || $hasta !== ''): ?>
      <a class="admin-btn secundario" href="<?= e(url('pedidos/index.php')) ?>">Limpiar</a>
    <?php endif; ?>
  </form>

  <div class="admin-tabla-scroll">
    <table class="admin-tabla">
      <thead>
        <tr>
          <th>Código</th>
          <th>Cliente</th>
          <th>Pieza</th>
          <th>Cant.</th>
          <th>Total</th>
          <th>Pago</th>
          <th>Estado</th>
          <th>Fecha</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$pedidos): ?>
          <tr><td colspan="9" class="admin-vacio">No hay pedidos con estos filtros.</td></tr>
        <?php endif; ?>
        <?php foreach ($pedidos as $p): ?>
          <tr>
            <td><a href="<?= e(url('pedidos/ver.php?id=' . (int)$p['id'])) ?>"><strong><?= e($p['codigo']) ?></strong></a></td>
            <td>
              <?= e($p['cliente']) ?>
              <div style="font-size:.72rem;color:var(--gris)"><?= e($p['telefono']) ?></div>
            </td>
            <td><?= e($p['producto']) ?></td>
            <td class="num"><?= (int)$p['cantidad'] ?></td>
            <td class="num"><?= e(precio_cop($p['total'])) ?></td>
            <td><?= e(ucfirst((string)$p['metodo_pago'])) ?></td>
            <td><?= admin_estado_badge((string)$p['estado']) ?></td>
            <td class="num"><?= e(fecha_legible((string)$p['creado_en'])) ?></td>
            <td><a class="admin-btn secundario pequeno" href="<?= e(url('pedidos/ver.php?id=' . (int)$p['id'])) ?>">Ver</a></td>
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
          <a href="<?= e(pedidos_pagina_url($i)) ?>"><?= $i ?></a>
        <?php endif; ?>
      <?php endfor; ?>
    </nav>
  <?php endif; ?>
</section>

<?php admin_footer(); ?>
