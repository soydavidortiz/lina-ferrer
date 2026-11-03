<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../includes/admin_layout.php';
requerir_admin();

$db = db();
$id = (int)($_GET['id'] ?? 0);
$errores = [];

$st = $db->prepare('SELECT * FROM clientes WHERE id = ? LIMIT 1');
$st->execute([$id]);
$cliente = $st->fetch();

if (!$cliente) {
    flash('error', 'El cliente no existe.');
    redirigir('clientes/index.php');
}

if (es_post()) {
    requerir_editor();
    if (!csrf_verificar($_POST['csrf'] ?? null)) {
        $errores[] = 'La sesión no es válida. Recarga la página.';
    } else {
        $datos = [
            'nombre' => trim((string)($_POST['nombre'] ?? '')),
            'email' => mb_strtolower(trim((string)($_POST['email'] ?? ''))),
            'telefono' => trim((string)($_POST['telefono'] ?? '')),
            'documento' => trim((string)($_POST['documento'] ?? '')),
            'departamento' => trim((string)($_POST['departamento'] ?? '')),
            'ciudad' => trim((string)($_POST['ciudad'] ?? '')),
            'direccion' => trim((string)($_POST['direccion'] ?? '')),
        ];

        if ($datos['nombre'] === '') $errores[] = 'El nombre es obligatorio.';
        if (!filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) $errores[] = 'El correo no es válido.';

        $st = $db->prepare('SELECT id FROM clientes WHERE email = ? AND id <> ? LIMIT 1');
        $st->execute([$datos['email'], $id]);
        if ($st->fetchColumn()) $errores[] = 'Ya existe otro cliente con ese correo.';

        if (!$errores) {
            try {
                $st = $db->prepare(
                    'UPDATE clientes SET nombre = :nombre, email = :email, telefono = :telefono,
                        documento = :documento, departamento = :departamento, ciudad = :ciudad, direccion = :direccion
                     WHERE id = :id'
                );
                $st->execute($datos + ['id' => $id]);
                registrar_actividad('cliente_actualizado', 'clientes', $id, $datos['nombre']);
                flash('ok', 'Datos del cliente actualizados.');
                redirigir('clientes/ver.php?id=' . $id);
            } catch (Throwable $ex) {
                $errores[] = 'No se pudo guardar: ' . $ex->getMessage();
            }
        } else {
            $cliente = array_merge($cliente, $datos);
        }
    }
}

$st = $db->prepare(
    "SELECT p.*, pr.nombre AS producto
       FROM pedidos p
       JOIN productos pr ON pr.id = p.producto_id
      WHERE p.cliente_id = ?
      ORDER BY p.creado_en DESC, p.id DESC"
);
$st->execute([$id]);
$pedidos = $st->fetchAll();

$totalComprado = 0;
$totalPedidos = count($pedidos);
foreach ($pedidos as $p) {
    if ($p['estado'] !== 'cancelado') $totalComprado += (int)$p['total'];
}

$telefono = preg_replace('/\D+/', '', (string)$cliente['telefono']);
if (strlen($telefono) === 10) $telefono = '57' . $telefono;

admin_header('Cliente · ' . $cliente['nombre'], 'clientes', 'Historial de pedidos y datos de contacto.');
?>

<?php foreach ($errores as $err): ?>
  <div class="admin-flash error"><?= e($err) ?></div>
<?php endforeach; ?>

<div class="admin-acciones-form" style="margin-bottom:18px">
  <a class="admin-btn secundario" href="<?= e(url('clientes/index.php')) ?>">← Volver a clientes</a>
  <a class="admin-btn secundario" href="https://wa.me/<?= e($telefono) ?>" target="_blank" rel="noopener">WhatsApp ↗</a>
  <a class="admin-btn secundario" href="mailto:<?= e($cliente['email']) ?>">Enviar correo</a>
</div>

<div class="admin-grid-2">
  <section class="admin-box">
    <?php admin_box_titulo('Historial de pedidos', '<span class="admin-badge no">' . $totalPedidos . ' pedido(s)</span>'); ?>
    <div class="admin-tabla-scroll">
      <table class="admin-tabla">
        <thead>
          <tr><th>Código</th><th>Pieza</th><th>Cant.</th><th>Total</th><th>Estado</th><th>Fecha</th></tr>
        </thead>
        <tbody>
          <?php if (!$pedidos): ?>
            <tr><td colspan="6" class="admin-vacio">Este cliente aún no tiene pedidos.</td></tr>
          <?php endif; ?>
          <?php foreach ($pedidos as $p): ?>
            <tr>
              <td><a href="<?= e(url('pedidos/ver.php?id=' . (int)$p['id'])) ?>"><strong><?= e($p['codigo']) ?></strong></a></td>
              <td><?= e($p['producto']) ?></td>
              <td class="num"><?= (int)$p['cantidad'] ?></td>
              <td class="num"><?= e(precio_cop($p['total'])) ?></td>
              <td><?= admin_estado_badge((string)$p['estado']) ?></td>
              <td class="num"><?= e(fecha_legible((string)$p['creado_en'])) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="admin-box-cuerpo" style="display:flex;justify-content:space-between;border-top:1px solid var(--linea)">
      <strong>Total comprado</strong>
      <strong><?= e(precio_cop($totalComprado)) ?></strong>
    </div>
  </section>

  <section class="admin-box">
    <?php admin_box_titulo('Datos del cliente'); ?>
    <div class="admin-box-cuerpo">
      <form class="admin-form" method="post" action="<?= e(url('clientes/ver.php?id=' . $id)) ?>">
        <?= csrf_campo() ?>
        <div class="admin-campo">
          <label for="nombre">Nombre completo</label>
          <input id="nombre" name="nombre" type="text" maxlength="100" value="<?= e($cliente['nombre']) ?>"<?= puede_editar() ? '' : ' disabled' ?>>
        </div>
        <div class="admin-campo">
          <label for="email">Correo</label>
          <input id="email" name="email" type="email" maxlength="150" value="<?= e($cliente['email']) ?>"<?= puede_editar() ? '' : ' disabled' ?>>
        </div>
        <div class="admin-grid-2-form">
          <div class="admin-campo">
            <label for="telefono">Teléfono</label>
            <input id="telefono" name="telefono" type="text" maxlength="20" value="<?= e($cliente['telefono']) ?>"<?= puede_editar() ? '' : ' disabled' ?>>
          </div>
          <div class="admin-campo">
            <label for="documento">Documento</label>
            <input id="documento" name="documento" type="text" maxlength="20" value="<?= e($cliente['documento']) ?>"<?= puede_editar() ? '' : ' disabled' ?>>
          </div>
        </div>
        <div class="admin-grid-2-form">
          <div class="admin-campo">
            <label for="departamento">Departamento</label>
            <input id="departamento" name="departamento" type="text" maxlength="60" value="<?= e($cliente['departamento']) ?>"<?= puede_editar() ? '' : ' disabled' ?>>
          </div>
          <div class="admin-campo">
            <label for="ciudad">Ciudad</label>
            <input id="ciudad" name="ciudad" type="text" maxlength="80" value="<?= e($cliente['ciudad']) ?>"<?= puede_editar() ? '' : ' disabled' ?>>
          </div>
        </div>
        <div class="admin-campo">
          <label for="direccion">Dirección</label>
          <input id="direccion" name="direccion" type="text" maxlength="200" value="<?= e($cliente['direccion']) ?>"<?= puede_editar() ? '' : ' disabled' ?>>
        </div>
        <div class="admin-campo">
          <span class="ayuda">Registrado el <?= e(fecha_legible((string)$cliente['creado_en'], true)) ?>.</span>
        </div>
        <?php if (puede_editar()): ?>
          <div class="admin-acciones-form" style="padding-top:0">
            <button class="admin-btn oro" type="submit">Guardar cambios</button>
          </div>
        <?php endif; ?>
      </form>
    </div>
  </section>
</div>

<?php admin_footer(); ?>
