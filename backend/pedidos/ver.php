<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../includes/admin_layout.php';
requerir_admin();

$db = db();
$id = (int)($_GET['id'] ?? 0);
$error = '';

$st = $db->prepare(
    'SELECT p.*, c.nombre AS cliente, c.email, c.telefono, c.documento,
            pr.nombre AS producto, pr.imagen, pr.slug AS producto_slug
       FROM pedidos p
       JOIN clientes c ON c.id = p.cliente_id
       JOIN productos pr ON pr.id = p.producto_id
      WHERE p.id = ? LIMIT 1'
);
$st->execute([$id]);
$pedido = $st->fetch();

if (!$pedido) {
    flash('error', 'El pedido no existe.');
    redirigir('pedidos/index.php');
}

$estados = ['pendiente', 'confirmado', 'enviado', 'entregado', 'cancelado'];

if (es_post()) {
    requerir_editor();
    if (!csrf_verificar($_POST['csrf'] ?? null)) {
        $error = 'La sesión no es válida. Recarga la página.';
    } else {
        $nuevo = (string)($_POST['estado'] ?? '');
        if (!in_array($nuevo, $estados, true)) {
            $error = 'Estado inválido.';
        } elseif ($nuevo !== $pedido['estado']) {
            try {
                $db->beginTransaction();

                $st = $db->prepare('SELECT * FROM pedidos WHERE id = ? FOR UPDATE');
                $st->execute([$id]);
                $bloqueado = $st->fetch();

                $st = $db->prepare('SELECT stock, nombre FROM productos WHERE id = ? FOR UPDATE');
                $st->execute([$bloqueado['producto_id']]);
                $producto = $st->fetch();

                $actual = (string)$bloqueado['estado'];
                $cantidad = (int)$bloqueado['cantidad'];

                // Cancelar: devolver stock. Reactivar: volver a reservarlo.
                if ($actual !== 'cancelado' && $nuevo === 'cancelado') {
                    $db->prepare('UPDATE productos SET stock = stock + ? WHERE id = ?')
                       ->execute([$cantidad, $bloqueado['producto_id']]);
                } elseif ($actual === 'cancelado' && $nuevo !== 'cancelado') {
                    if ((int)$producto['stock'] < $cantidad) {
                        throw new RuntimeException('No hay stock suficiente para reactivar este pedido. Disponible: ' . (int)$producto['stock'] . '.');
                    }
                    $db->prepare('UPDATE productos SET stock = stock - ? WHERE id = ?')
                       ->execute([$cantidad, $bloqueado['producto_id']]);
                }

                $db->prepare('UPDATE pedidos SET estado = ? WHERE id = ?')->execute([$nuevo, $id]);
                $db->commit();

                registrar_actividad('pedido_estado', 'pedidos', $id, $actual . ' → ' . $nuevo);
                flash('ok', 'Pedido actualizado a «' . $nuevo . '».');
                redirigir('pedidos/ver.php?id=' . $id);
            } catch (Throwable $ex) {
                if ($db->inTransaction()) $db->rollBack();
                $error = $ex->getMessage();
            }
        }
    }
}

// Refrescar datos
$st = $db->prepare(
    'SELECT p.*, c.nombre AS cliente, c.email, c.telefono, c.documento,
            pr.nombre AS producto, pr.imagen, pr.slug AS producto_slug
       FROM pedidos p
       JOIN clientes c ON c.id = p.cliente_id
       JOIN productos pr ON pr.id = p.producto_id
      WHERE p.id = ? LIMIT 1'
);
$st->execute([$id]);
$pedido = $st->fetch();

$items = $db->prepare('SELECT * FROM pedido_items WHERE pedido_id = ? ORDER BY id ASC');
$items->execute([$id]);
$items = $items->fetchAll();

$telefono = preg_replace('/\D+/', '', (string)$pedido['telefono']);
if (strlen($telefono) === 10) $telefono = '57' . $telefono;
$wa = 'https://wa.me/' . $telefono . '?text=' . rawurlencode(
    'Hola ' . $pedido['cliente'] . ', te escribimos de Lira Ferrer por tu pedido ' . $pedido['codigo'] . '.'
);

admin_header('Pedido ' . $pedido['codigo'], 'pedidos', 'Detalle, cliente, envío y estado del pedido.');
?>

<?php if ($error): ?><div class="admin-flash error"><?= e($error) ?></div><?php endif; ?>

<div class="admin-acciones-form" style="margin-bottom:18px">
  <a class="admin-btn secundario" href="<?= e(url('pedidos/index.php')) ?>">← Volver a pedidos</a>
  <a class="admin-btn secundario" href="<?= e($wa) ?>" target="_blank" rel="noopener">WhatsApp al cliente ↗</a>
  <a class="admin-btn secundario" href="mailto:<?= e($pedido['email']) ?>?subject=<?= rawurlencode('Pedido ' . $pedido['codigo'] . ' · Lira Ferrer') ?>">Enviar correo</a>
  <span style="margin-left:auto"><?= admin_estado_badge((string)$pedido['estado']) ?></span>
</div>

<div class="admin-grid-2">
  <div>
    <section class="admin-box">
      <?php admin_box_titulo('Resumen del pedido'); ?>
      <div class="admin-box-cuerpo">
        <div class="admin-detalle">
          <div class="admin-dato"><small>Código</small><strong><?= e($pedido['codigo']) ?></strong></div>
          <div class="admin-dato"><small>Fecha</small><strong><?= e(fecha_legible((string)$pedido['creado_en'], true)) ?></strong></div>
          <div class="admin-dato"><small>Método de pago</small><strong><?= e(ucfirst((string)$pedido['metodo_pago'])) ?></strong></div>
          <div class="admin-dato"><small>IP</small><strong><?= e($pedido['ip'] ?: '—') ?></strong></div>
        </div>

        <div style="display:flex;gap:16px;align-items:center;margin-top:20px;padding-top:20px;border-top:1px solid var(--linea)">
          <?php if ($pedido['imagen']): ?>
            <img class="admin-miniatura" style="width:74px;height:74px" src="<?= e(media_url((string)$pedido['imagen'])) ?>" alt="">
          <?php endif; ?>
          <div style="flex:1">
            <strong style="font-family:var(--serif);font-size:1.2rem"><?= e($pedido['producto']) ?></strong>
            <div style="font-size:.8rem;color:var(--gris)"><?= (int)$pedido['cantidad'] ?> × <?= e(precio_cop($pedido['precio_unitario'])) ?></div>
          </div>
          <strong><?= e(precio_cop((int)$pedido['precio_unitario'] * (int)$pedido['cantidad'])) ?></strong>
        </div>

        <?php if ($items): ?>
          <div class="admin-tabla-scroll" style="margin-top:16px">
            <table class="admin-tabla">
              <thead><tr><th>Artículo</th><th>Cant.</th><th>Precio</th><th>Subtotal</th></tr></thead>
              <tbody>
                <?php foreach ($items as $it): ?>
                  <tr>
                    <td><?= e($it['nombre_producto']) ?></td>
                    <td class="num"><?= (int)$it['cantidad'] ?></td>
                    <td class="num"><?= e(precio_cop($it['precio_unitario'])) ?></td>
                    <td class="num"><?= e(precio_cop($it['subtotal'])) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>

        <div style="margin-top:18px;padding-top:16px;border-top:1px solid var(--linea)">
          <div style="display:flex;justify-content:space-between;padding:5px 0"><span>Subtotal</span><span><?= e(precio_cop((int)$pedido['precio_unitario'] * (int)$pedido['cantidad'])) ?></span></div>
          <div style="display:flex;justify-content:space-between;padding:5px 0"><span>Envío</span><span><?= (int)$pedido['envio'] > 0 ? e(precio_cop($pedido['envio'])) : 'Gratis' ?></span></div>
          <div style="display:flex;justify-content:space-between;padding:10px 0 0;margin-top:6px;border-top:1px solid var(--linea);font-weight:600;font-size:1.1rem"><span>Total</span><span><?= e(precio_cop($pedido['total'])) ?></span></div>
        </div>
      </div>
    </section>

    <section class="admin-box">
      <?php admin_box_titulo('Datos de envío'); ?>
      <div class="admin-box-cuerpo">
        <div class="admin-detalle">
          <div class="admin-dato completo"><small>Dirección</small><strong><?= e($pedido['direccion']) ?></strong></div>
          <div class="admin-dato"><small>Ciudad</small><strong><?= e($pedido['ciudad']) ?></strong></div>
          <div class="admin-dato"><small>Departamento</small><strong><?= e($pedido['departamento']) ?></strong></div>
          <div class="admin-dato completo"><small>Notas del cliente</small><strong><?= $pedido['notas'] ? nl2br(e($pedido['notas'])) : '—' ?></strong></div>
        </div>
      </div>
    </section>
  </div>

  <div>
    <section class="admin-box">
      <?php admin_box_titulo('Cliente'); ?>
      <div class="admin-box-cuerpo">
        <div class="admin-detalle" style="grid-template-columns:1fr">
          <div class="admin-dato"><small>Nombre</small><strong><a href="<?= e(url('clientes/ver.php?id=' . (int)$pedido['cliente_id'])) ?>"><?= e($pedido['cliente']) ?></a></strong></div>
          <div class="admin-dato"><small>Correo</small><strong><a href="mailto:<?= e($pedido['email']) ?>"><?= e($pedido['email']) ?></a></strong></div>
          <div class="admin-dato"><small>Teléfono</small><strong><?= e($pedido['telefono']) ?></strong></div>
          <div class="admin-dato"><small>Documento</small><strong><?= e($pedido['documento']) ?></strong></div>
        </div>
      </div>
    </section>

    <section class="admin-box">
      <?php admin_box_titulo('Cambiar estado'); ?>
      <div class="admin-box-cuerpo">
        <form class="admin-form" method="post" action="<?= e(url('pedidos/ver.php?id=' . $id)) ?>">
          <?= csrf_campo() ?>
          <div class="admin-campo">
            <label for="estado">Estado del pedido</label>
            <select id="estado" name="estado"<?= puede_editar() ? '' : ' disabled' ?>>
              <?php foreach ($estados as $e): ?>
                <option value="<?= e($e) ?>"<?= $pedido['estado'] === $e ? ' selected' : '' ?>><?= e(ucfirst($e)) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="admin-campo">
            <span class="ayuda">Al cancelar se devuelve el stock. Al reactivar se vuelve a descontar.</span>
          </div>
          <?php if (puede_editar()): ?>
            <div class="admin-acciones-form" style="padding-top:0">
              <button class="admin-btn oro" type="submit">Actualizar estado</button>
            </div>
          <?php endif; ?>
        </form>
      </div>
    </section>

    <section class="admin-box">
      <?php admin_box_titulo('Trazabilidad'); ?>
      <div class="admin-box-cuerpo">
        <div class="admin-dato"><small>Creado</small><strong><?= e(fecha_legible((string)$pedido['creado_en'], true)) ?></strong></div>
        <div class="admin-dato"><small>Actualizado</small><strong><?= e(fecha_legible((string)$pedido['actualizado_en'], true)) ?></strong></div>
        <div class="admin-dato" style="border-bottom:0"><small>Ver la pieza</small><strong><a href="<?= e(frontend_url('producto.html?slug=' . urlencode((string)$pedido['producto_slug']))) ?>" target="_blank" rel="noopener">Abrir en el sitio ↗</a></strong></div>
      </div>
    </section>
  </div>
</div>

<?php admin_footer(); ?>
