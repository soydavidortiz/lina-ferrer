<?php
declare(strict_types=1);

require_once __DIR__ . '/uploads.php';

/**
 * Cabecera común del panel.
 *
 * @param string $titulo    Título de la página.
 * @param string $activo    Clave del menú activo: panel, productos, pedidos, clientes, informes, contenido, usuarios.
 * @param string $subtitulo Texto de apoyo.
 */
function admin_header(string $titulo, string $activo = '', string $subtitulo = ''): void
{
    $admin = admin_actual();
    $cssVer = (string)(@filemtime(__DIR__ . '/../assets/admin.css') ?: '1');
    $rol = $admin['rol'] ?? 'consulta';
    $esAdmin = $rol === 'admin';
    $puedeEditar = puede_editar();
    $flash = flash_obtener();

    $enlaces = [
        ['clave' => 'panel',     'url' => 'index.php',              'texto' => 'Panel',      'icono' => '◈'],
        ['clave' => 'productos', 'url' => 'productos/index.php',    'texto' => 'Productos',  'icono' => '◆'],
        ['clave' => 'pedidos',   'url' => 'pedidos/index.php',      'texto' => 'Pedidos',    'icono' => '▤'],
        ['clave' => 'clientes',  'url' => 'clientes/index.php',     'texto' => 'Clientes',   'icono' => '◎'],
        ['clave' => 'informes',  'url' => 'informes/index.php',     'texto' => 'Informes',   'icono' => '▥'],
    ];

    $contenido = [
        ['url' => 'contenido/configuracion.php', 'texto' => 'Textos del sitio'],
        ['url' => 'contenido/tabla.php?tabla=ficha', 'texto' => 'Ficha de la pieza'],
        ['url' => 'contenido/tabla.php?tabla=pilares', 'texto' => 'Pilares'],
        ['url' => 'contenido/tabla.php?tabla=prensa', 'texto' => 'Prensa'],
        ['url' => 'contenido/tabla.php?tabla=reconocimientos', 'texto' => 'Reconocimientos'],
        ['url' => 'contenido/tabla.php?tabla=faq', 'texto' => 'Preguntas frecuentes'],
        ['url' => 'contenido/tabla.php?tabla=hitos', 'texto' => 'Trayectoria'],
    ];
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title><?= e($titulo) ?> · <?= e(str_replace(' · Panel', '', APP_NOMBRE)) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(url('assets/admin.css')) ?>?v=<?= e($cssVer) ?>">
</head>
<body>
<div class="admin-shell">

  <aside class="admin-sidebar" id="adminSidebar">
    <a class="admin-brand" href="<?= e(url('index.php')) ?>">
      <img src="<?= e(media_url('assets/img/logo/lira-emblema.webp')) ?>" alt="" width="34" height="51">
      <span>
        <strong>Lira Ferrer</strong>
        <small>Panel de administración</small>
      </span>
    </a>

    <nav class="admin-nav" aria-label="Menú del panel">
      <?php foreach ($enlaces as $enlace): ?>
        <a class="admin-link<?= $activo === $enlace['clave'] ? ' activo' : '' ?>" href="<?= e(url($enlace['url'])) ?>">
          <span class="admin-link-icono" aria-hidden="true"><?= e($enlace['icono']) ?></span>
          <?= e($enlace['texto']) ?>
        </a>
      <?php endforeach; ?>

      <a class="admin-link<?= $activo === 'contenido' ? ' activo' : '' ?>" href="<?= e(url('contenido/configuracion.php')) ?>">
        <span class="admin-link-icono" aria-hidden="true">✎</span>
        Contenido del sitio
      </a>
      <div class="admin-submenu">
        <?php foreach ($contenido as $enlace): ?>
          <a href="<?= e(url($enlace['url'])) ?>"><?= e($enlace['texto']) ?></a>
        <?php endforeach; ?>
      </div>

      <?php if ($esAdmin): ?>
        <a class="admin-link<?= $activo === 'usuarios' ? ' activo' : '' ?>" href="<?= e(url('usuarios/index.php')) ?>">
          <span class="admin-link-icono" aria-hidden="true">♜</span>
          Usuarios
        </a>
      <?php endif; ?>
    </nav>

    <div class="admin-sidebar-pie">
      <span><?= e($admin['nombre'] ?? '') ?></span>
      <small><?= e(ucfirst($rol)) ?></small>
    </div>
  </aside>

  <div class="admin-main">
    <header class="admin-topbar">
      <button class="admin-menu-toggle" id="adminMenuToggle" type="button" aria-label="Abrir menú" aria-controls="adminSidebar">
        <span></span><span></span><span></span>
      </button>

      <div class="admin-topbar-titulo">
        <h1><?= e($titulo) ?></h1>
        <?php if ($subtitulo !== ''): ?><p><?= e($subtitulo) ?></p><?php endif; ?>
      </div>

      <div class="admin-topbar-acciones">
        <a class="admin-ver-sitio" href="<?= e(frontend_url('index.html')) ?>" target="_blank" rel="noopener">Ver sitio ↗</a>
        <form method="post" action="<?= e(url('logout.php')) ?>">
          <?= csrf_campo() ?>
          <button class="admin-salir" type="submit">Salir</button>
        </form>
      </div>
    </header>

    <main class="admin-contenido">
      <?php foreach ($flash as $mensaje): ?>
        <div class="admin-flash <?= e($mensaje['tipo']) ?>" role="status">
          <?= e($mensaje['mensaje']) ?>
        </div>
      <?php endforeach; ?>

      <?php if (!$puedeEditar): ?>
        <div class="admin-aviso-rol">
          Tu perfil es de <strong>consulta</strong>. Puedes revisar la información, pero no modificarla.
        </div>
      <?php endif; ?>
    <?php
}

function admin_footer(): void
{
    $jsVer = (string)(@filemtime(__DIR__ . '/../assets/admin.js') ?: '1');
    ?>
    </main>
  </div>
</div>
<script src="<?= e(url('assets/admin.js')) ?>?v=<?= e($jsVer) ?>"></script>
</body>
</html>
    <?php
}

/** Encabezado de una caja/sección. */
function admin_box_titulo(string $titulo, string $accion = ''): void
{
    ?>
    <div class="admin-box-cabecera">
      <h2><?= e($titulo) ?></h2>
      <?= $accion ?>
    </div>
    <?php
}

/** Badge de estado de pedido. */
function admin_estado_badge(string $estado): string
{
    $permitidos = ['pendiente', 'confirmado', 'enviado', 'entregado', 'cancelado'];
    $estado = in_array($estado, $permitidos, true) ? $estado : 'pendiente';
    return '<span class="admin-badge ' . e($estado) . '">' . e(ucfirst($estado)) . '</span>';
}
