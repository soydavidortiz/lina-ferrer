<?php
declare(strict_types=1);

/* =========================================================
   POST api/pedidos.php  → crea un pedido
   El precio y el total SIEMPRE se calculan en el servidor.
   ========================================================= */
require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Método no permitido.', 405);
}

$datos = leerJson();

// Antispam (campo invisible)
if (!empty($datos['empresa'])) {
    Response::error('Solicitud inválida.', 400);
}

// ---------- Validación ----------
$v = (new Validator($datos))
    ->requerido('nombre', 'Escribe tu nombre completo.')
    ->longitud('nombre', 3, 100, 'El nombre debe tener entre 3 y 100 caracteres.')
    ->patron('nombre', "/^[\\p{L}' .-]+$/u", 'El nombre solo puede tener letras.')
    ->requerido('email', 'Escribe tu correo.')
    ->email('email', 'Escribe un correo válido.')
    ->longitud('email', 5, 150, 'El correo es demasiado largo.')
    ->requerido('telefono', 'Escribe tu celular.')
    ->telefono('telefono', 'Escribe un celular válido.')
    ->requerido('documento', 'Escribe tu documento.')
    ->patron('documento', '/^[A-Za-z0-9.\-]{5,20}$/', 'Escribe un documento válido.')
    ->requerido('departamento', 'Selecciona el departamento.')
    ->longitud('departamento', 2, 60, 'Departamento inválido.')
    ->requerido('ciudad', 'Escribe la ciudad.')
    ->longitud('ciudad', 2, 80, 'Ciudad inválida.')
    ->requerido('direccion', 'Escribe la dirección.')
    ->longitud('direccion', 6, 200, 'La dirección debe tener entre 6 y 200 caracteres.')
    ->longitud('notas', 0, 500, 'Las notas admiten máximo 500 caracteres.')
    ->requerido('slug', 'No hay una pieza seleccionada.')
    ->patron('slug', '/^[a-z0-9-]{1,80}$/', 'Pieza inválida.')
    ->entero('cantidad', 1, MAX_UNIDADES_POR_PEDIDO, 'Cantidad inválida.')
    ->requerido('metodo_pago', 'Elige cómo quieres pagar.')
    ->enLista('metodo_pago', METODOS_PAGO, 'Método de pago inválido.')
    ->aceptado('terminos', 'Debes aceptar los términos para continuar.');

if ($v->falla()) {
    Response::error('Revisa los datos del formulario.', 422, $v->errores());
}

$cantidad = (int) $datos['cantidad'];
$notas    = $v->get('notas');

$cliente = [
    'nombre'       => $v->get('nombre'),
    'email'        => mb_strtolower($v->get('email')),
    'telefono'     => preg_replace('/\D+/', '', $v->get('telefono')),
    'documento'    => $v->get('documento'),
    'departamento' => $v->get('departamento'),
    'ciudad'       => $v->get('ciudad'),
    'direccion'    => $v->get('direccion'),
];

$db        = Database::conexion();
$productos = new Producto();
$clientes  = new Cliente();
$pedidos   = new Pedido();

try {
    $db->beginTransaction();

    // 1) Bloquear la pieza para que nadie más la compre al mismo tiempo
    $producto = $productos->bloquearPorSlug($v->get('slug'));
    if ($producto === null || !$producto['activo']) {
        $db->rollBack();
        Response::error('Esta pieza ya no está disponible.', 404);
    }

    // 2) Verificar stock
    if ($producto['stock'] < $cantidad) {
        $db->rollBack();
        $msg = $producto['stock'] > 0
            ? "Solo quedan {$producto['stock']} unidades de esta pieza."
            : 'Esta pieza se ha agotado.';
        Response::error($msg, 409, ['cantidad' => $msg]);
    }

    // 3) Descontar stock
    if (!$productos->descontarStock($producto['id'], $cantidad)) {
        throw new RuntimeException('No se pudo reservar la pieza.');
    }

    // 4) Crear o actualizar cliente
    $clienteId = $clientes->guardar($cliente);

    // 5) Calcular totales en el servidor
    $envio = (int) ENVIO_COSTO;
    $total = $producto['precio'] * $cantidad + $envio;

    // 6) Crear el pedido
    $nuevo = $pedidos->crear([
        'cliente_id'      => $clienteId,
        'producto_id'     => $producto['id'],
        'cantidad'        => $cantidad,
        'precio_unitario' => $producto['precio'],
        'envio'           => $envio,
        'total'           => $total,
        'metodo_pago'     => $v->get('metodo_pago'),
        'departamento'    => $cliente['departamento'],
        'ciudad'          => $cliente['ciudad'],
        'direccion'       => $cliente['direccion'],
        'notas'           => $notas,
    ]);

    $db->commit();
} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    throw $e; // lo atrapa el manejador de bootstrap.php y responde JSON 500
}

// ---------- Respuesta para gracias.html ----------
$primerNombre = explode(' ', $cliente['nombre'])[0];

Response::ok([
    'codigo'      => $nuevo['codigo'],
    'cliente'     => $primerNombre,
    'producto'    => $producto['nombre'],
    'cantidad'    => $cantidad,
    'precio'      => $producto['precio'],
    'envio'       => $envio,
    'total'       => $total,
    'metodo_pago' => $v->get('metodo_pago'),
    'whatsapp'    => WHATSAPP,
], 201);
