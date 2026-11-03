<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../includes/admin_layout.php';
requerir_admin();

$db = db();
$q = trim((string)($_GET['q'] ?? ''));
$pagina = max(1, (int)($_GET['pagina'] ?? 1));
$porPagina = 20;

$where = '';
$params = [];
if ($q !== '') {
    $where = 'WHERE c.nombre LIKE :q1 OR c.email LIKE :q2 OR c.telefono LIKE :q3 OR c.ciudad LIKE :q4';
    $params[':q1'] = $params[':q2'] = $params[':q3'] = $params[':q4'] = '%' . $q . '%';
}

$st = $db->prepare("SELECT COUNT(*) FROM clientes c $where");
$st->execute($params);
$total = (int)$st->fetchColumn();
$paginas = max(1, (int)ceil($total / $porPagina));
$pagina = min($pagina, $paginas);
$offset = ($pagina - 1) * $porPagina;

$st = $db->prepare(
    "SELECT c.*,
            COUNT(p.id) AS pedidos,
            COALESCE(SUM(CASE WHEN p.estado <> 'cancelado' THEN p.total ELSE 0 END), 0) AS comprado,
            MAX(p.creado_en) AS ultimo_pedido
       FROM clientes c
       LEFT JOIN pedidos p ON p.cliente_id = c.id
       $where
      GROUP BY c.id
      ORDER BY c.creado_en DESC
      LIMIT $porPagina OFFSET $offset"
);
$st->execute($params);
$clientes = $st->fetchAll();

function clientes_pagina_url(int $n): string
{
    $qs = $_GET;
    $qs['pagina'] = $n;
    return url('clientes/index.php') . '?' . http_build_query($qs);
}

admin_header('Clientes', 'clientes', 'Datos de contacto, historial y valor comprado.');
?>

<section class="admin-box">
  <?php admin_box_titulo('Clientes registrados', '<span class="admin-badge no">' . $total . ' cliente(s)</span>'); ?>

  <form class="admin-filtros" method="GET" action="<?= e(url('clientes/index.php')) ?>">
    <div class="admin-campo" style="min-width:260px">
      <label for="q">Buscar</label>
      <input id="q" type="search" name="q" value="<?= e($q) ?>" placeholder="Nombre, correo, teléfono o ciudad">
    </div>
    <button class="admin-btn secundario" type="submit">Filtrar</button>
    <?php if ($q !== ''): ?>
      <a class="admin-btn secundario" href="<?= e(url('clientes/index.php')) ?>">Limpiar</a>
    <?php endif; ?>
  </form>

  <div class="admin-tabla-scroll">
    <table class="admin-tabla">
      <thead>
        <tr>
          <th>Cliente</th>
          <th>Contacto</th>
          <th>Ciudad</th>
          <th>Pedidos</th>
          <th>Comprado</th>
          <th>Último pedido</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$clientes): ?>
          <tr><td colspan="7" class="admin-vacio">No hay clientes con estos filtros.</td></tr>
        <?php endif; ?>
        <?php foreach ($clientes as $c): ?>
          <tr>
            <td>
              <a href="<?= e(url('clientes/ver.php?id=' . (int)$c['id'])) ?>"><strong><?= e($c['nombre']) ?></strong></a>
              <div style="font-size:.72rem;color:var(--gris)">Doc. <?= e($c['documento']) ?></div>
            </td>
            <td>
              <?= e($c['email']) ?>
              <div style="font-size:.72rem;color:var(--gris)"><?= e($c['telefono']) ?></div>
            </td>
            <td><?= e($c['ciudad']) ?><div style="font-size:.72rem;color:var(--gris)"><?= e($c['departamento']) ?></div></td>
            <td class="num"><?= (int)$c['pedidos'] ?></td>
            <td class="num"><?= e(precio_cop($c['comprado'])) ?></td>
            <td class="num"><?= e($c['ultimo_pedido'] ? fecha_legible((string)$c['ultimo_pedido']) : '—') ?></td>
            <td><a class="admin-btn secundario pequeno" href="<?= e(url('clientes/ver.php?id=' . (int)$c['id'])) ?>">Ver</a></td>
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
          <a href="<?= e(clientes_pagina_url($i)) ?>"><?= $i ?></a>
        <?php endif; ?>
      <?php endfor; ?>
    </nav>
  <?php endif; ?>
</section>

<?php admin_footer(); ?>
