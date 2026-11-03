<?php
declare(strict_types=1);
require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/includes/admin_layout.php';
requerir_admin();

$db = db();

$pedidosPendientes = (int)$db->query("SELECT COUNT(*) FROM pedidos WHERE estado = 'pendiente'")->fetchColumn();
$ventasMes = (int)$db->query(
    "SELECT COALESCE(SUM(total), 0) FROM pedidos
      WHERE estado <> 'cancelado'
        AND YEAR(creado_en) = YEAR(CURDATE())
        AND MONTH(creado_en) = MONTH(CURDATE())"
)->fetchColumn();
$clientes = (int)$db->query('SELECT COUNT(*) FROM clientes')->fetchColumn();
$productosActivos = (int)$db->query('SELECT COUNT(*) FROM productos WHERE activo = 1')->fetchColumn();
$stockBajo = (int)$db->query('SELECT COUNT(*) FROM productos WHERE activo = 1 AND stock <= 5')->fetchColumn();

$ultimos = $db->query(
    "SELECT p.id, p.codigo, p.total, p.estado, p.creado_en, c.nombre AS cliente, pr.nombre AS producto
       FROM pedidos p
       JOIN clientes c ON c.id = p.cliente_id
       JOIN productos pr ON pr.id = p.producto_id
      ORDER BY p.creado_en DESC, p.id DESC
      LIMIT 6"
)->fetchAll();

$bajos = $db->query(
    "SELECT id, nombre, stock, imagen
       FROM productos WHERE activo = 1 AND stock <= 5
      ORDER BY stock ASC, nombre ASC LIMIT 5"
)->fetchAll();

$actividad = $db->query(
    "SELECT a.accion, a.entidad, a.detalle, a.creado_en, u.nombre
       FROM admin_actividad a
       LEFT JOIN admin_usuarios u ON u.id = a.usuario_id
      ORDER BY a.creado_en DESC, a.id DESC LIMIT 8"
)->fetchAll();

admin_header('Panel', 'panel', 'Resumen general de la tienda y del contenido del sitio.');
?>

<div class="admin-stats">
  <div class="admin-stat oro">
    <small>Ventas del mes</small>
    <strong><?= e(precio_cop($ventasMes)) ?></strong>
    <span>Pedidos no cancelados</span>
  </div>
  <div class="admin-stat">
    <small>Pedidos pendientes</small>
    <strong><?= e((string)$pedidosPendientes) ?></strong>
    <span>Requieren confirmación</span>
  </div>
  <div class="admin-stat">
    <small>Clientes</small>
    <strong><?= e((string)$clientes) ?></strong>
    <span>Registrados en la tienda</span>
  </div>
  <div class="admin-stat">
    <small>Productos activos</small>
    <strong><?= e((string)$productosActivos) ?></strong>
    <span><?= $stockBajo > 0 ? e((string)$stockBajo) . ' con stock bajo' : 'Stock saludable' ?></span>
  </div>
</div>

<div class="admin-grid-2">
  <section class="admin-box">
    <?php admin_box_titulo('Últimos pedidos', '<a class="admin-btn secundario pequeno" href="' . e(url('pedidos/index.php')) . '">Ver todos</a>'); ?>
    <div class="admin-tabla-scroll">
      <table class="admin-tabla">
        <thead>
          <tr>
            <th>Código</th>
            <th>Cliente</th>
            <th>Pieza</th>
            <th>Total</th>
            <th>Estado</th>
            <th>Fecha</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$ultimos): ?>
            <tr><td colspan="6" class="admin-vacio">Todavía no hay pedidos registrados.</td></tr>
          <?php endif; ?>
          <?php foreach ($ultimos as $p): ?>
            <tr>
              <td><a href="<?= e(url('pedidos/ver.php?id=' . (int)$p['id'])) ?>"><strong><?= e($p['codigo']) ?></strong></a></td>
              <td><?= e($p['cliente']) ?></td>
              <td><?= e($p['producto']) ?></td>
              <td class="num"><?= e(precio_cop($p['total'])) ?></td>
              <td><?= admin_estado_badge((string)$p['estado']) ?></td>
              <td class="num"><?= e(fecha_legible((string)$p['creado_en'])) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>

  <div>
    <section class="admin-box">
      <?php admin_box_titulo('Stock bajo', '<a class="admin-btn secundario pequeno" href="' . e(url('productos/index.php')) . '">Productos</a>'); ?>
      <div class="admin-box-cuerpo">
        <?php if (!$bajos): ?>
          <p class="admin-vacio">No hay piezas con stock bajo.</p>
        <?php else: ?>
          <div class="admin-tabla-scroll">
            <table class="admin-tabla">
              <tbody>
                <?php foreach ($bajos as $p): ?>
                  <tr>
                    <td style="width:58px"><img class="admin-miniatura" src="<?= e(media_url((string)$p['imagen'])) ?>" alt=""></td>
                    <td><a href="<?= e(url('productos/editar.php?id=' . (int)$p['id'])) ?>"><?= e($p['nombre']) ?></a></td>
                    <td class="num"><?= (int)$p['stock'] ?> und.</td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </section>

    <section class="admin-box">
      <?php admin_box_titulo('Actividad reciente'); ?>
      <div class="admin-box-cuerpo">
        <?php if (!$actividad): ?>
          <p class="admin-vacio">Sin actividad registrada.</p>
        <?php else: ?>
          <div class="admin-actividad">
            <?php foreach ($actividad as $a): ?>
              <div class="admin-dato">
                <small><?= e(fecha_legible((string)$a['creado_en'], true)) ?> · <?= e($a['nombre'] ?: 'Sistema') ?></small>
                <strong><?= e($a['accion']) ?><?= $a['entidad'] ? ' · ' . e($a['entidad']) : '' ?></strong>
                <?php if ($a['detalle']): ?><div class="ayuda" style="font-size:.74rem;color:var(--gris)"><?= e($a['detalle']) ?></div><?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </section>
  </div>
</div>

<section class="admin-box">
  <?php admin_box_titulo('Accesos rápidos'); ?>
  <div class="admin-box-cuerpo">
    <div class="admin-grid-3">
      <a class="admin-stat" href="<?= e(url('productos/editar.php')) ?>">
        <small>Catálogo</small>
        <strong style="font-size:1.3rem">Nueva pieza</strong>
        <span>Crear un producto con galería y ficha técnica.</span>
      </a>
      <a class="admin-stat" href="<?= e(url('contenido/configuracion.php')) ?>">
        <small>Contenido</small>
        <strong style="font-size:1.3rem">Textos del sitio</strong>
        <span>Hero, manifiesto, filosofía, historia y contacto.</span>
      </a>
      <a class="admin-stat" href="<?= e(url('contenido/tabla.php?tabla=prensa')) ?>">
        <small>Prensa</small>
        <strong style="font-size:1.3rem">Notas y medios</strong>
        <span>Añadir apariciones y reconocimientos.</span>
      </a>
    </div>
  </div>
</section>

<?php admin_footer(); ?>
