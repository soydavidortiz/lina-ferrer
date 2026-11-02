<?php
declare(strict_types=1);
require_once __DIR__ . '/config/bootstrap.php';
requerir_admin();

$admin = admin_actual();
$modulos = [
    ['ruta' => 'productos/index.php', 'titulo' => 'Productos', 'desc' => 'Crear, editar y numerar piezas.'],
    ['ruta' => 'pedidos/index.php',   'titulo' => 'Pedidos',   'desc' => 'Seguimiento y estados de pedidos.'],
    ['ruta' => 'clientes/index.php',  'titulo' => 'Clientes',  'desc' => 'Datos e historial de clientes.'],
    ['ruta' => 'informes/index.php',  'titulo' => 'Informes',  'desc' => 'Ventas y estadísticas.'],
    ['ruta' => 'usuarios/index.php',  'titulo' => 'Usuarios',  'desc' => 'Administradores del panel.'],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title>Panel · <?= e(APP_NOMBRE) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600&family=Inter:wght@300;400;500&display=swap" rel="stylesheet">
  <style>
    :root { --oro:#C9A961; --negro:#1a1a1a; --crema:#FAF7F2; }
    * { box-sizing:border-box; margin:0; padding:0; }
    body { font-family:'Inter',sans-serif; background:var(--crema); color:var(--negro); }
    header { background:var(--negro); padding:18px 32px; display:flex; justify-content:space-between; align-items:center; }
    header h1 { font-family:'Cormorant Garamond',serif; font-size:1.5rem; color:var(--oro); }
    header button { background:none; border:1px solid var(--oro); color:var(--oro); padding:8px 18px;
                    cursor:pointer; letter-spacing:2px; font-size:.75rem; text-transform:uppercase; }
    header button:hover { background:var(--oro); color:var(--negro); }
    main { max-width:1100px; margin:48px auto; padding:0 24px; }
    .saludo { font-family:'Cormorant Garamond',serif; font-size:2.2rem; margin-bottom:32px; }
    .grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(240px,1fr)); gap:24px; }
    .card { background:#fff; padding:32px; border:1px solid rgba(201,169,97,.3); text-decoration:none;
            color:inherit; transition:transform .2s, box-shadow .2s; }
    .card:hover { transform:translateY(-3px); box-shadow:0 10px 30px rgba(0,0,0,.06); }
    .card h3 { font-family:'Cormorant Garamond',serif; font-size:1.4rem; margin-bottom:8px; }
    .card p { font-size:.9rem; color:#777; }
  </style>
</head>
<body>
  <header>
    <h1><?= e(APP_NOMBRE) ?></h1>
    <form method="POST" action="<?= e(url('logout.php')) ?>">
      <?= csrf_campo() ?>
      <button type="submit">Cerrar sesión</button>
    </form>
  </header>

  <main>
    <p class="saludo">Bienvenido, <?= e($admin['nombre'] ?? 'Admin') ?></p>
    <div class="grid">
      <?php foreach ($modulos as $m): ?>
        <a class="card" href="<?= e(url($m['ruta'])) ?>">
          <h3><?= e($m['titulo']) ?></h3>
          <p><?= e($m['desc']) ?></p>
        </a>
      <?php endforeach; ?>
    </div>
  </main>
</body>
</html>
