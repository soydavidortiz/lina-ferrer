/* =========================================================
   LIRA FERRER — comprar.js
   ========================================================= */
(async function () {
  'use strict';
  const L = window.Lira;
  L.iniciarLayout();

  const $ = id => document.getElementById(id);
  const form = $('formCompra');
  const btn = $('btnEnviar');
  const alerta = $('alertaForm');

  // ---------- Departamentos ----------
  const DEPARTAMENTOS = [
    'Amazonas', 'Antioquia', 'Arauca', 'Atlántico', 'Bogotá D.C.', 'Bolívar', 'Boyacá',
    'Caldas', 'Caquetá', 'Casanare', 'Cauca', 'Cesar', 'Chocó', 'Córdoba', 'Cundinamarca',
    'Guainía', 'Guaviare', 'Huila', 'La Guajira', 'Magdalena', 'Meta', 'Nariño',
    'Norte de Santander', 'Putumayo', 'Quindío', 'Risaralda', 'San Andrés y Providencia',
    'Santander', 'Sucre', 'Tolima', 'Valle del Cauca', 'Vaupés', 'Vichada'
  ];
  DEPARTAMENTOS.forEach(d => form.departamento.add(new Option(d, d)));

  // ---------- Producto ----------
  const slug = L.param('slug');
  const producto = slug ? await L.obtenerProducto(slug).catch(() => null) : null;
  $('estadoCarga').hidden = true;

  if (!producto || producto.stock <= 0) {
    $('estadoError').hidden = false;
    return;
  }
  $('checkout').hidden = false;

  const max = Math.max(1, Math.min(producto.stock, L.CONFIG.MAX_UNIDADES));
  let cantidad = Math.min(Math.max(parseInt(L.param('cantidad'), 10) || 1, 1), max);

  $('rImg').src = producto.imagen;
  $('rImg').alt = producto.nombre;
  $('rNombre').textContent = producto.nombre;
  $('rDetalle').textContent = producto.color || '';
  $('rPrecio').textContent = L.formatoCOP(producto.precio);
  $('rEnvio').textContent = L.CONFIG.ENVIO > 0 ? L.formatoCOP(L.CONFIG.ENVIO) : 'Gratis';
  L.vigilarImagenes($('rImg').parentElement);

  function pintarTotales() {
    const subtotal = producto.precio * cantidad;
    $('rCantidad').textContent = cantidad;
    $('rSubtotal').textContent = L.formatoCOP(subtotal);
    $('rTotal').textContent = L.formatoCOP(subtotal + L.CONFIG.ENVIO);
    $('menos').disabled = cantidad <= 1;
    $('mas').disabled = cantidad >= max;
  }
  $('menos').addEventListener('click', () => { if (cantidad > 1) { cantidad--; pintarTotales(); } });
  $('mas').addEventListener('click', () => { if (cantidad < max) { cantidad++; pintarTotales(); } });
  pintarTotales();

  // ---------- Validación ----------
  const valor = nombre => {
    const el = form.elements[nombre];
    if (!el) return '';
    if (nombre === 'terminos') return el.checked ? '1' : '';
    return String(el.value || '').trim();
  };

  const REGLAS = {
    nombre: v => v.length < 3 ? 'Escribe tu nombre completo.'
      : !/^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ' .-]+$/.test(v) ? 'El nombre solo puede tener letras.' : '',
    email: v => /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v) ? '' : 'Escribe un correo válido.',
    telefono: v => /^\d{7,15}$/.test(v.replace(/[\s()+-]/g, '')) ? '' : 'Escribe un celular válido.',
    documento: v => /^[A-Za-z0-9.-]{5,20}$/.test(v) ? '' : 'Escribe un documento válido.',
    departamento: v => v ? '' : 'Selecciona el departamento.',
    ciudad: v => v.length >= 2 ? '' : 'Escribe la ciudad.',
    direccion: v => v.length >= 6 ? '' : 'Escribe la dirección completa.',
    notas: v => v.length <= 500 ? '' : 'Máximo 500 caracteres.',
    metodo_pago: v => v ? '' : 'Elige cómo quieres pagar.',
    terminos: v => v ? '' : 'Debes aceptar los términos para continuar.'
  };

  function mostrarError(nombre, mensaje) {
    const caja = form.querySelector(`[data-campo="${nombre}"]`);
    if (!caja) return;
    caja.classList.toggle('con-error', !!mensaje);
    const msg = caja.querySelector('.error-msg');
    if (msg) msg.textContent = mensaje || '';
  }

  function validarCampo(nombre) {
    const mensaje = REGLAS[nombre](valor(nombre));
    mostrarError(nombre, mensaje);
    return mensaje;
  }

  form.addEventListener('focusout', e => {
    if (REGLAS[e.target.name] && e.target.type !== 'radio' && e.target.type !== 'checkbox') validarCampo(e.target.name);
  });
  ['input', 'change'].forEach(evento =>
    form.addEventListener(evento, e => {
      const caja = e.target.closest('[data-campo]');
      if (caja && caja.classList.contains('con-error')) validarCampo(caja.dataset.campo);
    })
  );

  // ---------- Alertas ----------
  function mostrarAlerta(html) {
    alerta.innerHTML = html;
    alerta.hidden = false;
    alerta.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }

  function mensajeWhatsapp(d) {
    return [
      'Hola, quiero hacer este pedido en Lira Ferrer:',
      `• ${producto.nombre} x${cantidad}`,
      `• Total: ${L.formatoCOP(producto.precio * cantidad + L.CONFIG.ENVIO)}`,
      '',
      `Nombre: ${d.nombre}`,
      `Celular: ${d.telefono}`,
      `Correo: ${d.email}`,
      `Ciudad: ${d.ciudad}, ${d.departamento}`,
      `Dirección: ${d.direccion}`,
      `Pago: ${L.CONFIG.METODOS[d.metodo_pago] || d.metodo_pago}`,
      d.notas ? `Notas: ${d.notas}` : ''
    ].filter(Boolean).join('\n');
  }

  // ---------- Envío ----------
  form.addEventListener('submit', async e => {
    e.preventDefault();
    alerta.hidden = true;

    let primerError = null;
    Object.keys(REGLAS).forEach(n => { if (validarCampo(n) && !primerError) primerError = n; });
    if (primerError) {
      const el = form.elements[primerError];
      (el.length ? el[0] : el).focus();
      mostrarAlerta('Revisa los campos marcados en rojo.');
      return;
    }

    const datos = {
      nombre: valor('nombre'),
      email: valor('email'),
      telefono: valor('telefono'),
      documento: valor('documento'),
      departamento: valor('departamento'),
      ciudad: valor('ciudad'),
      direccion: valor('direccion'),
      notas: valor('notas'),
      metodo_pago: valor('metodo_pago'),
      terminos: true,
      empresa: form.empresa.value,
      slug: producto.slug,
      cantidad
    };

    btn.disabled = true;
    btn.textContent = 'Procesando…';

    try {
      const pedido = await L.api('pedidos.php', { method: 'POST', body: JSON.stringify(datos) });
      sessionStorage.setItem('lira_pedido', JSON.stringify(pedido));
      location.href = 'gracias.html?codigo=' + encodeURIComponent(pedido.codigo);
    } catch (err) {
      Object.entries(err.errores || {}).forEach(([campo, msg]) => mostrarError(campo, msg));
      if (err.sinServidor) {
        mostrarAlerta(`${L.escapar(err.message)} También puedes <a href="${L.whatsappUrl(mensajeWhatsapp(datos))}" target="_blank" rel="noopener">enviar tu pedido por WhatsApp</a>.`);
      } else {
        mostrarAlerta(L.escapar(err.message));
      }
      btn.disabled = false;
      btn.textContent = 'Confirmar pedido';
    }
  });
})();
