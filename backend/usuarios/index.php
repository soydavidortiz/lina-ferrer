<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../includes/admin_layout.php';
requerir_rol('admin');

$db = db();
$admin = admin_actual();
$error = '';

if (es_post()) {
    if (!csrf_verificar($_POST['csrf'] ?? null)) {
        $error = 'La sesión no es válida. Recarga la página.';
    } else {
        $accion = (string)($_POST['accion'] ?? '');
        $id = (int)($_POST['id'] ?? 0);

        if ($accion === 'eliminar' && $id > 0) {
            if ($id === (int)$admin['id']) {
                $error = 'No puedes eliminar tu propio usuario.';
            } else {
                $st = $db->prepare('SELECT nombre, email, rol, activo FROM admin_usuarios WHERE id = ?');
                $st->execute([$id]);
                $u = $st->fetch();

                if (!$u) {
                    $error = 'El usuario no existe.';
                } else {
                    $st = $db->prepare("SELECT COUNT(*) FROM admin_usuarios WHERE rol = 'admin' AND activo = 1");
                    $st->execute();
                    $adminsActivos = (int)$st->fetchColumn();

                    if ($u['rol'] === 'admin' && (int)$u['activo'] === 1 && $adminsActivos <= 1) {
                        $error = 'No puedes eliminar al último administrador activo.';
                    } else {
                        $db->prepare('DELETE FROM admin_intentos_login WHERE email = ?')->execute([$u['email']]);
                        $db->prepare('DELETE FROM admin_usuarios WHERE id = ?')->execute([$id]);
                        registrar_actividad('usuario_eliminado', 'admin_usuarios', $id, $u['nombre']);
                        flash('ok', 'Usuario eliminado.');
                        redirigir('usuarios/index.php');
                    }
                }
            }
        }
    }
}

$usuarios = $db->query(
    'SELECT id, nombre, email, rol, activo, ultimo_login, creado_en
       FROM admin_usuarios ORDER BY creado_en ASC, id ASC'
)->fetchAll();

admin_header('Usuarios', 'usuarios', 'Administradores, gestores y perfiles de consulta.');
?>

<?php if ($error): ?><div class="admin-flash error"><?= e($error) ?></div><?php endif; ?>

<section class="admin-box">
  <?php admin_box_titulo('Usuarios del panel', '<a class="admin-btn oro" href="' . e(url('usuarios/editar.php')) . '">+ Nuevo usuario</a>'); ?>
  <div class="admin-tabla-scroll">
    <table class="admin-tabla">
      <thead>
        <tr><th>Nombre</th><th>Correo</th><th>Rol</th><th>Estado</th><th>Último ingreso</th><th>Creado</th><th></th></tr>
      </thead>
      <tbody>
        <?php foreach ($usuarios as $u): ?>
          <tr>
            <td>
              <strong><?= e($u['nombre']) ?></strong>
              <?php if ((int)$u['id'] === (int)$admin['id']): ?><span class="admin-badge si" style="margin-left:6px">Tú</span><?php endif; ?>
            </td>
            <td><?= e($u['email']) ?></td>
            <td><span class="admin-badge <?= $u['rol'] === 'admin' ? 'confirmado' : ($u['rol'] === 'gestor' ? 'enviado' : 'no') ?>"><?= e(ucfirst((string)$u['rol'])) ?></span></td>
            <td><?= (int)$u['activo'] === 1 ? '<span class="admin-badge si">Activo</span>' : '<span class="admin-badge no">Inactivo</span>' ?></td>
            <td class="num"><?= e($u['ultimo_login'] ? fecha_legible((string)$u['ultimo_login'], true) : 'Nunca') ?></td>
            <td class="num"><?= e(fecha_legible((string)$u['creado_en'])) ?></td>
            <td>
              <div class="acciones">
                <a class="admin-btn secundario pequeno" href="<?= e(url('usuarios/editar.php?id=' . (int)$u['id'])) ?>">Editar</a>
                <?php if ((int)$u['id'] !== (int)$admin['id']): ?>
                  <form method="post" action="<?= e(url('usuarios/index.php')) ?>">
                    <?= csrf_campo() ?>
                    <input type="hidden" name="accion" value="eliminar">
                    <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                    <button class="admin-btn peligro pequeno" type="submit" data-confirm="¿Eliminar a «<?= e($u['nombre']) ?>»?">Eliminar</button>
                  </form>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<section class="admin-box">
  <?php admin_box_titulo('Permisos por rol'); ?>
  <div class="admin-box-cuerpo">
    <div class="admin-grid-3">
      <div class="admin-dato">
        <small>Administrador</small>
        <strong>Acceso total: contenido, productos, pedidos, clientes, informes y usuarios.</strong>
      </div>
      <div class="admin-dato">
        <small>Gestor</small>
        <strong>Puede crear y editar contenido, productos, pedidos y clientes. No administra usuarios.</strong>
      </div>
      <div class="admin-dato">
        <small>Consulta</small>
        <strong>Solo lectura. No puede guardar, eliminar ni cambiar estados.</strong>
      </div>
    </div>
  </div>
</section>

<?php admin_footer(); ?>
