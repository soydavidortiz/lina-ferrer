<?php
/* =========================================================
   LIRA FERRER — Diagnóstico del servidor
   Abrir:  http://localhost/lira-ferrer/api/diagnostico.php
   ⚠️ BORRA ESTE ARCHIVO cuando todo funcione.
   ========================================================= */
header('Content-Type: text/html; charset=utf-8');
ini_set('display_errors', '1');
error_reporting(E_ALL);

$resultados = [];
function revisar(string $nombre, bool $ok, string $detalle = ''): bool
{
    global $resultados;
    $resultados[] = [$nombre, $ok, $detalle];
    return $ok;
}

$raiz = dirname(__DIR__);

// ---------- 1. PHP ----------
revisar('PHP 8.0 o superior', version_compare(PHP_VERSION, '8.0.0', '>='),
    'Versión actual: ' . PHP_VERSION . (version_compare(PHP_VERSION, '8.0.0', '<') ? ' → actualiza XAMPP/Laragon a PHP 8' : ''));
revisar('Extensión pdo_mysql', extension_loaded('pdo_mysql'), extension_loaded('pdo_mysql') ? '' : 'En php.ini quita el ; de: extension=pdo_mysql');
revisar('Extensión mbstring', extension_loaded('mbstring'), extension_loaded('mbstring') ? '' : 'En php.ini quita el ; de: extension=mbstring');

// ---------- 2. Archivos ----------
$archivos = [
    'config/config.php', 'config/Database.php',
    'helpers/Response.php', 'helpers/Validator.php',
    'models/Producto.php', 'models/Cliente.php', 'models/Pedido.php',
    'api/bootstrap.php', 'api/productos.php', 'api/pedidos.php',
];
$faltan = array_filter($archivos, fn($a) => !is_file($raiz . '/' . $a));
revisar('Archivos del backend', !$faltan, $faltan ? 'Faltan: ' . implode(', ', $faltan) : 'Todos presentes');

// ---------- 3. Configuración ----------
if (is_file($raiz . '/config/config.php')) {
    require_once $raiz . '/config/config.php';
}
$hayConfig = revisar('Constantes de config.php', defined('DB_HOST') && defined('DB_NAME') && defined('METODOS_PAGO'),
    defined('DB_HOST') ? 'Host: ' . DB_HOST . ':' . DB_PORT . ' · Base: ' . DB_NAME . ' · Usuario: ' . DB_USER : 'config.php no define las constantes');

// ---------- 4. Base de datos ----------
$esperadas = [
    'productos' => ['id', 'slug', 'nombre', 'color', 'detalle', 'descripcion', 'precio', 'stock', 'edicion_total', 'imagen', 'galeria', 'especificaciones', 'activo', 'orden'],
    'clientes'  => ['id', 'nombre', 'email', 'telefono', 'documento', 'departamento', 'ciudad', 'direccion'],
    'pedidos'   => ['id', 'codigo', 'cliente_id', 'producto_id', 'cantidad', 'precio_unitario', 'envio', 'total', 'metodo_pago', 'estado', 'departamento', 'ciudad', 'direccion', 'notas', 'ip'],
];

if ($hayConfig && extension_loaded('pdo_mysql')) {
    try {
        $pdo = new PDO(sprintf('mysql:host=%s;port=%d;charset=utf8mb4', DB_HOST, DB_PORT), DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        revisar('Conexión a MySQL', true, 'Conectado');

        $existe = (bool) $pdo->query('SHOW DATABASES LIKE ' . $pdo->quote(DB_NAME))->fetchColumn();
        if (revisar('Base de datos "' . DB_NAME . '"', $existe, $existe ? 'Existe' : 'No existe → importa database/lira_ferrer.sql en phpMyAdmin')) {
            $pdo->exec('USE `' . DB_NAME . '`');
            $tablasOk = true;

            foreach ($esperadas as $tabla => $cols) {
                $hay = (bool) $pdo->query('SHOW TABLES LIKE ' . $pdo->quote($tabla))->fetchColumn();
                if (!$hay) {
                    $tablasOk = revisar("Tabla $tabla", false, 'No existe → importa el SQL') && $tablasOk;
                    continue;
                }
                $reales = array_column($pdo->query("SHOW COLUMNS FROM `$tabla`")->fetchAll(), 'Field');
                $sin = array_diff($cols, $reales);
                $tablasOk = revisar("Tabla $tabla", !$sin, $sin
                    ? 'Faltan columnas: ' . implode(', ', $sin) . ' → la tabla es de una versión anterior, vuelve a importar el SQL'
                    : 'Estructura correcta') && $tablasOk;
            }

            if ($tablasOk) {
                $prods = $pdo->query('SELECT id, slug, stock, precio FROM productos WHERE activo = 1')->fetchAll();
                revisar('Piezas en la base', count($prods) > 0,
                    $prods ? implode(' · ', array_map(fn($p) => "{$p['slug']} (stock {$p['stock']})", $prods)) : 'No hay piezas → importa el SQL');

                // ---------- 5. Prueba real de guardado (se deshace) ----------
                if ($prods) {
                    try {
                        $pdo->beginTransaction();
                        $pdo->prepare('INSERT INTO clientes (nombre, email, telefono, documento, departamento, ciudad, direccion) VALUES (?,?,?,?,?,?,?)')
                            ->execute(['Prueba Diagnóstico', 'diagnostico+' . time() . '@test.com', '3000000000', '12345678', 'Atlántico', 'Barranquilla', 'Calle 1 # 2-3']);
                        $clienteId = (int) $pdo->lastInsertId();
                        $pdo->prepare('INSERT INTO pedidos (codigo, cliente_id, producto_id, cantidad, precio_unitario, envio, total, metodo_pago, departamento, ciudad, direccion, notas, ip) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)')
                            ->execute(['LF-TEST-' . substr((string) time(), -6), $clienteId, $prods[0]['id'], 1, $prods[0]['precio'], 0, $prods[0]['precio'], 'transferencia', 'Atlántico', 'Barranquilla', 'Calle 1 # 2-3', null, '127.0.0.1']);
                        $pdo->rollBack();
                        revisar('Prueba de guardado de pedido', true, 'Se pudo guardar (y se deshizo, no quedó nada en la base)');
                    } catch (Throwable $e) {
                        if ($pdo->inTransaction()) $pdo->rollBack();
                        revisar('Prueba de guardado de pedido', false, $e->getMessage());
                    }
                }
            }
        }
    } catch (PDOException $e) {
        $m = $e->getMessage();
        $pista = str_contains($m, '1045') ? ' → usuario o contraseña incorrectos en config.php'
             : (str_contains($m, '2002') ? ' → MySQL está apagado o el puerto no es ' . DB_PORT . ' (en XAMPP a veces es 3307)' : '');
        revisar('Conexión a MySQL', false, $m . $pista);
    }
}

// ---------- 6. Carga del backend real ----------
if (!$faltan && version_compare(PHP_VERSION, '8.0.0', '>=')) {
    try {
        foreach (['config/Database.php', 'helpers/Response.php', 'helpers/Validator.php', 'models/Producto.php', 'models/Cliente.php', 'models/Pedido.php'] as $a) {
            require_once $raiz . '/' . $a;
        }
        revisar('Clases PHP cargan sin errores', true);
    } catch (Throwable $e) {
        revisar('Clases PHP cargan sin errores', false, $e->getMessage() . ' en ' . basename($e->getFile()) . ':' . $e->getLine());
    }
}

$todoOk = !in_array(false, array_column($resultados, 1), true);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Diagnóstico | Lira Ferrer</title>
  <style>
    body { font-family: Arial, sans-serif; background: #f5f0e8; color: #2b2b2b; padding: 40px 20px; }
    .caja { max-width: 860px; margin: 0 auto; background: #fff; padding: 30px; border-top: 4px solid #0c3f2d; }
    h1 { color: #0c3f2d; margin: 0 0 6px; font-size: 1.6rem; }
    .resumen { padding: 14px 18px; margin: 20px 0; font-weight: bold; }
    .bien { background: #e8f5ee; color: #0c3f2d; }
    .mal  { background: #fdecec; color: #9b2c2c; }
    table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
    td { padding: 10px; border-bottom: 1px solid #eee; vertical-align: top; }
    td:first-child { width: 30px; font-size: 1.1rem; }
    td:nth-child(2) { width: 230px; font-weight: bold; }
    .det { color: #666; word-break: break-word; }
    tr.falla .det { color: #9b2c2c; }
  </style>
</head>
<body>
  <div class="caja">
    <h1>Diagnóstico Lira Ferrer</h1>
    <small>Borra este archivo cuando todo funcione.</small>
    <div class="resumen <?= $todoOk ? 'bien' : 'mal' ?>">
      <?= $todoOk ? '✅ Todo está bien. El backend puede guardar pedidos.' : '❌ Hay problemas. Corrige los marcados en rojo, empezando por el primero.' ?>
    </div>
    <table>
      <?php foreach ($resultados as [$nombre, $ok, $detalle]): ?>
        <tr class="<?= $ok ? '' : 'falla' ?>">
          <td><?= $ok ? '✅' : '❌' ?></td>
          <td><?= htmlspecialchars($nombre) ?></td>
          <td class="det"><?= htmlspecialchars($detalle) ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
  </div>
</body>
</html>