<?php
declare(strict_types=1);
require_once __DIR__ . '/config/bootstrap.php';

if (admin_logueado()) {
    redirigir('index.php');
}

$error = '';
$aviso = isset($_GET['expirada']) ? 'Tu sesión expiró. Ingresa de nuevo.' : '';

if (es_post()) {
    $email = mb_strtolower(trim((string)($_POST['email'] ?? '')));
    $pass  = (string)($_POST['password'] ?? '');

    if (!csrf_verificar($_POST['csrf'] ?? null)) {
        $error = 'La sesión no es válida. Recarga la página.';
    } elseif (($espera = login_bloqueado($email)) > 0) {
        $error = 'Demasiados intentos. Intenta de nuevo en ' . (int)ceil($espera / 60) . ' minuto(s).';
    } elseif (intentar_login($email, $pass)) {
        redirigir('index.php');
    } else {
        usleep(500000); // frena ataques de fuerza bruta
        $error = 'Correo o contraseña incorrectos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title>Acceso · <?= e(APP_NOMBRE) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600&family=Inter:wght@300;400;500&display=swap" rel="stylesheet">
  <style>
    :root { --oro:#C9A961; --negro:#1a1a1a; --crema:#FAF7F2; }
    * { box-sizing:border-box; margin:0; padding:0; }
    body { font-family:'Inter',sans-serif; background:var(--crema); min-height:100vh;
           display:flex; align-items:center; justify-content:center; padding:20px; }
    .caja { background:#fff; width:100%; max-width:400px; padding:48px 40px;
            border:1px solid rgba(201,169,97,.4); position:relative; }
    .caja::before { content:''; position:absolute; inset:8px; border:1px solid rgba(201,169,97,.2); pointer-events:none; }
    h1 { font-family:'Cormorant Garamond',serif; font-weight:600; font-size:2rem; text-align:center; margin-bottom:6px; }
    .sub { text-align:center; color:#888; font-size:.85rem; letter-spacing:2px; text-transform:uppercase; margin-bottom:32px; }
    label { display:block; font-size:.8rem; color:#555; margin-bottom:6px; letter-spacing:1px; }
    input { width:100%; padding:12px 14px; border:1px solid #ddd; font-size:1rem; margin-bottom:20px; font-family:inherit; }
    input:focus { outline:none; border-color:var(--oro); }
    button { width:100%; padding:14px; background:var(--negro); color:#fff; border:none; font-size:.85rem;
             letter-spacing:3px; text-transform:uppercase; cursor:pointer; transition:background .3s; }
    button:hover { background:var(--oro); }
    .msg { padding:10px 14px; font-size:.85rem; margin-bottom:20px; }
    .error { background:#fdecec; color:#a33; border-left:3px solid #a33; }
    .aviso { background:#fdf8ec; color:#8a6d1f; border-left:3px solid var(--oro); }
    .volver { text-align:center; margin-top:20px; font-size:.75rem; letter-spacing:.08em; text-transform:uppercase; }
    .volver a { color:#777; text-decoration:none; border-bottom:1px solid #ccc; padding-bottom:2px; }
    .volver a:hover { color:var(--oro); border-color:var(--oro); }
  </style>
</head>
<body>
  <form class="caja" method="POST" action="<?= e(url('login.php')) ?>" autocomplete="off">
    <h1>Lira Ferrer</h1>
    <p class="sub">Acceso privado</p>

    <?php if ($aviso): ?><div class="msg aviso"><?= e($aviso) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="msg error"><?= e($error) ?></div><?php endif; ?>

    <?= csrf_campo() ?>

    <label for="email">Correo</label>
    <input type="email" id="email" name="email" required autofocus value="<?= e($_POST['email'] ?? '') ?>">

    <label for="password">Contraseña</label>
    <input type="password" id="password" name="password" required>

    <button type="submit">Ingresar</button>
    <p class="volver"><a href="<?= e(frontend_url('index.html')) ?>">← Volver al sitio</a></p>
  </form>
</body>
</html>
