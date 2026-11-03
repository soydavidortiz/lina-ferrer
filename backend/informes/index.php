<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../includes/admin_layout.php';
requerir_admin();

$db = db();
$desde = trim((string)($_GET['desde'] ?? ''));
$hasta = trim((string)($_GET['hasta'] ?? ''));

$where = ["estado <> 'cancelado'"];
$params = [];
if ($desde !== '') {
    $where[] = 'creado_en >= :desde';
    $params[':desde'] = $desde . ' 00:00:00';
}
if ($hasta !== '') {
    $where[] = 'creado_en <= :hasta';
    $params[':hasta'] = $hasta . ' 23:59:59';
}
$sqlWhere = ' WHERE ' . implode(' AND ', $where);

$st = $db->prepare("SELECT COUNT(*) n, COALESCE(SUM(total),0) total, COALESCE(AVG(total),0) ticket
                      FROM pedidos$sqlWhere");
$st->execute($params);
$resumen = $st->fetch();

$st = $db->prepare("SELECT COUNT(DISTINCT cliente_id) FROM pedidos$sqlWhere");
$st->execute($params);
$clientesConPedido = (int)$st->fetchColumn();

$cancelWhere = str_replace("estado <> 'cancelado'", "estado = 'cancelado'", $sqlWhere);
$st = $db->prepare("SELECT COUNT(*) n, COALESCE(SUM(total),0) total FROM pedidos$cancelWhere");
$st->execute($params);
$cancelados = $st->fetch();

// ---------- Ventas por mes ----------
$porMes = [];
if ($desde !== '' || $hasta !== '') {
    $st = $db->prepare(
        "SELECT DATE_FORMAT(creado_en, '%Y-%m') mes, COUNT(*) n, SUM(total) total
           FROM pedidos$sqlWhere
          GROUP BY mes ORDER BY mes ASC"
    );
    $st->execute($params);
    foreach ($st->fetchAll() as $f) $porMes[$f['mes']] = $f;
    $meses = array_keys($porMes);
} else {
    $st = $db->query(
        "SELECT DATE_FORMAT(creado_en, '%Y-%m') mes, COUNT(*) n, SUM(total) total
           FROM pedidos
          WHERE estado <> 'cancelado'
            AND creado_en >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH)
          GROUP BY mes ORDER BY mes ASC"
    );
    foreach ($st->fetchAll() as $f) $porMes[$f['mes']] = $f;

    $meses = [];
    $cursor = new DateTime('first day of this month');
    $cursor->modify('-11 months');
    for ($i = 0; $i < 12; $i++) {
        $meses[] = $cursor->format('Y-m');
        $cursor->modify('+1 month');
    }
}
$maxMes = 0;
foreach ($meses as $m) $maxMes = max($maxMes, (int)($porMes[$m]['total'] ?? 0));

// ---------- Top productos ----------
// La tabla productos también tiene `creado_en`: calificamos las columnas de pedidos.
$sqlWhereTop = str_replace(
    ['estado <>', 'creado_en >='],
    ['p.estado <>', 'p.creado_en >='],
    $sqlWhere
);
$sqlWhereTop = str_replace('creado_en <=', 'p.creado_en <=', $sqlWhereTop);
$st = $db->prepare(
    "SELECT pr.nombre, SUM(p.cantidad) unidades, SUM(p.total) total
       FROM pedidos p
       JOIN productos pr ON pr.id = p.producto_id
       $sqlWhereTop
      GROUP BY pr.id, pr.nombre
      ORDER BY unidades DESC, total DESC
      LIMIT 8"
);
$st->execute($params);
$topProductos = $st->fetchAll();
$maxUnidades = 0;
foreach ($topProductos as $p) $maxUnidades = max($maxUnidades, (int)$p['unidades']);

// ---------- Estados ----------
$estados = $db->query('SELECT estado, COUNT(*) n FROM pedidos GROUP BY estado ORDER BY n DESC')->fetchAll();

// ---------- Ciudades ----------
$st = $db->prepare(
    "SELECT ciudad, departamento, COUNT(*) n, SUM(total) total
       FROM pedidos$sqlWhere
      GROUP BY ciudad, departamento
      ORDER BY total DESC
      LIMIT 6"
);
$st->execute($params);
$ciudades = $st->fetchAll();

admin_header('Informes', 'informes', 'Ventas, ticket promedio, productos y ciudades.');
?>

<section class="admin-box">
  <form class="admin-filtros" method="GET" action="<?= e(url('informes/index.php')) ?>">
    <div class="admin-campo">
      <label for="desde">Desde</label>
      <input id="desde" type="date" name="desde" value="<?= e($desde) ?>">
    </div>
    <div class="admin-campo">
      <label for="hasta">Hasta</label>
      <input id="hasta" type="date" name="hasta" value="<?= e($hasta) ?>">
    </div>
    <button class="admin-btn secundario" type="submit">Aplicar</button>
    <?php if ($desde !== '' || $hasta !== ''): ?>
      <a class="admin-btn secundario" href="<?= e(url('informes/index.php')) ?>">Ver todo</a>
    <?php endif; ?>
    <span class="ayuda" style="margin-left:auto"><?= ($desde === '' && $hasta === '') ? 'Mostrando todo el historial' : 'Rango seleccionado' ?></span>
  </form>
</section>

<div class="admin-stats">
  <div class="admin-stat oro">
    <small>Total vendido</small>
    <strong><?= e(precio_cop($resumen['total'] ?? 0)) ?></strong>
    <span>Pedidos no cancelados</span>
  </div>
  <div class="admin-stat">
    <small>Pedidos</small>
    <strong><?= (int)($resumen['n'] ?? 0) ?></strong>
    <span><?= $clientesConPedido ?> cliente(s) con compras</span>
  </div>
  <div class="admin-stat">
    <small>Ticket promedio</small>
    <strong><?= e(precio_cop($resumen['ticket'] ?? 0)) ?></strong>
    <span>Por pedido</span>
  </div>
  <div class="admin-stat">
    <small>Cancelados</small>
    <strong><?= (int)($cancelados['n'] ?? 0) ?></strong>
    <span><?= e(precio_cop($cancelados['total'] ?? 0)) ?> no cobrados</span>
  </div>
</div>

<div class="admin-grid-2">
  <section class="admin-box">
    <?php admin_box_titulo('Ventas por mes'); ?>
    <div class="admin-box-cuerpo">
      <?php if (!$meses): ?>
        <p class="admin-vacio">Sin ventas en el periodo.</p>
      <?php else: ?>
        <div class="admin-barras">
          <?php foreach ($meses as $m): ?>
            <?php
              $total = (int)($porMes[$m]['total'] ?? 0);
              $n = (int)($porMes[$m]['n'] ?? 0);
              $pct = $maxMes > 0 ? max(2, round($total * 100 / $maxMes)) : 2;
              $partes = explode('-', $m);
              $mesesCortos = ['', 'ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
              $etiqueta = ($partes[1] ?? '') !== '' ? ($mesesCortos[(int)$partes[1]] . ' ' . ($partes[0] ?? '')) : $m;
            ?>
            <div class="admin-barra">
              <span><?= e($etiqueta) ?></span>
              <span class="pista"><span class="relleno" style="width:<?= $pct ?>%"></span></span>
              <span class="valor"><?= e(precio_cop($total)) ?><br><small style="font-weight:400;color:var(--gris)"><?= $n ?> pedido(s)</small></span>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <section class="admin-box">
    <?php admin_box_titulo('Estados'); ?>
    <div class="admin-box-cuerpo">
      <?php if (!$estados): ?>
        <p class="admin-vacio">Sin pedidos.</p>
      <?php else: ?>
        <div class="admin-detalle" style="grid-template-columns:1fr">
          <?php foreach ($estados as $e): ?>
            <div class="admin-dato" style="display:flex;justify-content:space-between;align-items:center">
              <?= admin_estado_badge((string)$e['estado']) ?>
              <strong><?= (int)$e['n'] ?></strong>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </section>
</div>

<div class="admin-grid-2">
  <section class="admin-box">
    <?php admin_box_titulo('Productos más vendidos'); ?>
    <div class="admin-box-cuerpo">
      <?php if (!$topProductos): ?>
        <p class="admin-vacio">Sin ventas en el periodo.</p>
      <?php else: ?>
        <div class="admin-barras">
          <?php foreach ($topProductos as $p): ?>
            <?php $pct = $maxUnidades > 0 ? max(2, round((int)$p['unidades'] * 100 / $maxUnidades)) : 2; ?>
            <div class="admin-barra" style="grid-template-columns:1.2fr 1fr 90px">
              <span><?= e($p['nombre']) ?></span>
              <span class="pista"><span class="relleno" style="width:<?= $pct ?>%"></span></span>
              <span class="valor"><?= (int)$p['unidades'] ?> und.<br><small style="font-weight:400;color:var(--gris)"><?= e(precio_cop($p['total'])) ?></small></span>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <section class="admin-box">
    <?php admin_box_titulo('Ciudades con más ventas'); ?>
    <div class="admin-tabla-scroll">
      <table class="admin-tabla">
        <thead><tr><th>Ciudad</th><th>Pedidos</th><th>Total</th></tr></thead>
        <tbody>
          <?php if (!$ciudades): ?>
            <tr><td colspan="3" class="admin-vacio">Sin datos.</td></tr>
          <?php endif; ?>
          <?php foreach ($ciudades as $c): ?>
            <tr>
              <td><?= e($c['ciudad']) ?><div style="font-size:.72rem;color:var(--gris)"><?= e($c['departamento']) ?></div></td>
              <td class="num"><?= (int)$c['n'] ?></td>
              <td class="num"><?= e(precio_cop($c['total'])) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
</div>

<?php admin_footer(); ?>
