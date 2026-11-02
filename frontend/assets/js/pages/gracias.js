/* =========================================================
   LIRA FERRER — gracias.js
   ========================================================= */
(function () {
  'use strict';
  const L = window.Lira;
  L.iniciarLayout();

  const $ = id => document.getElementById(id);
  const codigoUrl = L.param('codigo') || '';

  let pedido = null;
  try { pedido = JSON.parse(sessionStorage.getItem('lira_pedido') || 'null'); } catch (e) { pedido = null; }
  if (pedido && codigoUrl && pedido.codigo !== codigoUrl) pedido = null;

  const codigo = codigoUrl || (pedido && pedido.codigo) || '';

  if (codigo) $('gCodigo').textContent = codigo;
  else $('gCodigoCaja').hidden = true;

  if (pedido) {
    if (pedido.cliente) $('gNombre').textContent = ', ' + pedido.cliente;
    const filas = [
      ['Pieza', pedido.producto],
      ['Cantidad', pedido.cantidad],
      ['Pago', L.CONFIG.METODOS[pedido.metodo_pago] || pedido.metodo_pago],
      ['Envío', pedido.envio > 0 ? L.formatoCOP(pedido.envio) : 'Gratis']
    ];
    $('gResumen').innerHTML =
      filas.map(([k, v]) => `<div class="resumen-fila"><span>${k}</span><span>${L.escapar(v)}</span></div>`).join('') +
      `<div class="resumen-fila resumen-total"><span>Total</span><span>${L.formatoCOP(pedido.total)}</span></div>`;
    $('gResumen').hidden = false;
  }

  $('gWhatsapp').href = L.whatsappUrl(
    codigo
      ? `Hola, acabo de hacer el pedido ${codigo} en Lira Ferrer. Quisiera confirmar el pago.`
      : 'Hola, acabo de hacer un pedido en Lira Ferrer.'
  );
})();
