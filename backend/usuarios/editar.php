<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../includes/admin_layout.php';
requerir_rol('admin');

$db = db();
$admin = admin_actual();
$id = (int)($_GET['id'] ?? 0);
$usuario = null;
$errores = [];

if ($id > 0) {
    $st = $db->prepare('SELECT * FROM admin_usuarios WHERE id = ? LIMIT 1');
    $st->execute([$id]);
    $usuario = $st->fetch() ?: null;
    if (!$usuario) {
        flash('error', 'El usuario no existe.');
        redirigir('usuarios/index.php');
    }
}

$valores = [
    'nombre' => $usuario['nombre'] ?? '',
    'email' => $usuario['email'] ?? '',
    'rol' => $usuario['rol'] ?? 'gestor',
    'activo' => $usuario['activo'] ?? 1,
];

if (es_post()) {
    if (!csrf_verificar($_POST['csrf'] ?? null)) {
        $errores[] = 'La sesión no es válida. Recarga la página.';
    } else {
        $valores['nombre'] = trim((string)($_POST['nombre'] ?? ''));
        $valores['email'] = mb_strtolower(trim((string)($_POST['email'] ?? '')));
        $valores['rol'] = (string)($_POST['rol'] ?? 'gestor');
        $valores['activo'] = isset($_POST['activo']) ? 1 : 0;
        $password = (string)($_POST['password'] ?? '');

        if ($valores['nombre'] === '') $errores[] = 'El nombre es obligatorio.';
        if (!filter_var($valores['email'], FILTER_VALIDATE_EMAIL)) $errores[] = 'El correo no es válido.';
        if (!in_array($valores['rol'], ['admin', 'gestor', 'consulta'], true)) $errores[] = 'Rol inválido.';

        if ($id === 0 && $password === '') {
            $errores[] = 'La contraseña es obligatoria para un usuario nuevo.';
        }
        if ($password !== '') {
            if (strlen($password) < 10) $errores[] = 'La contraseña debe tener al menos 10 caracteres.';
            if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
                $errores[] = 'La contraseña debe combinar letras y números.';
            }
        }

        // Correo único
        $st = $db->prepare('SELECT id FROM admin_usuarios WHERE email = ? AND id <> ? LIMIT 1');
        $st->execute([$valores['email'], $id]);
        if ($st->fetchColumn()) $errores[] = 'Ya existe otro usuario con ese correo.';

        // Protecciones del último administrador / uno mismo
        if ($id > 0) {
            if ($id === (int)$admin['id'] && $valores['activo'] === 0) {
                $errores[] = 'No puedes desactivar tu propio usuario.';
            }
            $st = $db->prepare("SELECT COUNT(*) FROM admin_usuarios WHERE rol = 'admin' AND activo = 1");
            $st->execute();
            $adminsActivos = (int)$st->fetchColumn();
            $eraAdminActivo = $usuario['rol'] === 'admin' && (int)$usuario['activo'] === 1;
            $sigueAdminActivo = $valores['rol'] === 'admin' && $valores['activo'] === 1;
            if ($eraAdminActivo && !$sigueAdminActivo && $adminsActivos <= 1) {
                $errores[] = 'Debe quedar al menos un administrador activo.';
            }
        }

        if (!$errores) {
            try {
                if ($id > 0) {
                    $sql = 'UPDATE admin_usuarios SET nombre = :nombre, email = :email, rol = :rol, activo = :activo';
                    $params = $valores + ['id' => $id];
                    if ($password !== '') {
                        $sql .= ', password_hash = :password_hash';
                        $params['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
                    }
                    $sql .= ' WHERE id = :id';
                    $db->prepare($sql)->execute($params);
                    registrar_actividad('usuario_actualizado', 'admin_usuarios', $id, $valores['nombre']);
                    flash('ok', 'Usuario actualizado.');
                    redirigir('usuarios/editar.php?id=' . $id);
                } else {
                    $st = $db->prepare(
                        'INSERT INTO admin_usuarios (nombre, email, password_hash, rol, activo)
                         VALUES (:nombre, :email, :password_hash, :rol, :activo)'
                    );
                    $st->execute([
                        'nombre' => $valores['nombre'],
                        'email' => $valores['email'],
                        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                        'rol' => $valores['rol'],
                        'activo' => $valores['activo'],
                    ]);
                    $nuevoId = (int)$db->lastInsertId();
                    registrar_actividad('usuario_creado', 'admin_usuarios', $nuevoId, $valores['nombre']);
                    flash('ok', 'Usuario creado.');
                    redirigir('usuarios/index.php');
                }
            } catch (Throwable $ex) {
                $errores[] = 'No se pudo guardar: ' . $ex->getMessage();
            }
        }
    }
}

admin_header(
    $id > 0 ? 'Editar usuario' : 'Nuevo usuario',
    'usuarios',
    'Define el acceso y el rol dentro del panel.'
);
?>

<?php foreach ($errores as $err): ?>
  <div class="admin-flash error"><?= e($err) ?></div>
<?php endforeach; ?>

<form class="admin-form" method="post" action="<?= e(url('usuarios/editar.php' . ($id > 0 ? '?id=' . $id : ''))) ?>">
  <?= csrf_campo() ?>

  <section class="admin-box">
    <?php admin_box_titulo('Datos del usuario'); ?>
    <div class="admin-box-cuerpo">
      <div class="admin-grid-2-form">
        <div class="admin-campo">
          <label for="nombre">Nombre completo *</label>
          <input id="nombre" name="nombre" type="text" maxlength="100" required value="<?= e($valores['nombre']) ?>">
        </div>
        <div class="admin-campo">
          <label for="email">Correo *</label>
          <input id="email" name="email" type="email" maxlength="150" required value="<?= e($valores['email']) ?>">
        </div>
      </div>

      <div class="admin-grid-2-form">
        <div class="admin-campo">
          <label for="rol">Rol *</label>
          <select id="rol" name="rol" required>
            <option value="admin"<?= $valores['rol'] === 'admin' ? ' selected' : '' ?>>Administrador (acceso total)</option>
            <option value="gestor"<?= $valores['rol'] === 'gestor' ? ' selected' : '' ?>>Gestor (contenido y pedidos)</option>
            <option value="consulta"<?= $valores['rol'] === 'consulta' ? ' selected' : '' ?>>Consulta (solo lectura)</option>
          </select>
        </div>
        <div class="admin-campo">
          <label for="password">Contraseña <?= $id === 0 ? '*' : '(dejar vacío para conservar)' ?></label>
          <input id="password" name="password" type="password" minlength="10" autocomplete="new-password"<?= $id === 0 ? ' required' : '' ?>>
          <span class="ayuda">Mínimo 10 caracteres, con letras y números.</span>
        </div>
      </div>

      <div class="admin-campo">
        <label class="admin-check">
          <input type="checkbox" name="activo" value="1"<?= (int)$valores['activo'] === 1 ? ' checked' : '' ?>>
          <span>Usuario activo</span>
        </label>
      </div>
    </div>
  </section>

  <div class="admin-acciones-form">
    <button class="admin-btn oro" type="submit"><?= $id > 0 ? 'Guardar cambios' : 'Crear usuario' ?></button>
    <a class="admin-btn secundario" href="<?= e(url('usuarios/index.php')) ?>">Cancelar</a>
  </div>
</form>

<?php admin_footer(); ?>
