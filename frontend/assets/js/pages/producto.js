/* =========================================================
   LIRA FERRER — producto.js
   ========================================================= */
(async function () {
  'use strict';
  const L = window.Lira;
  L.iniciarLayout();

  const $ = id => document.getElementById(id);
  const slug = L.param('slug');

  const producto = slug ? await L.obtenerProducto(slug).catch(() => null) : null;
  $('estadoCarga').hidden = true;

  if (!producto) {
    $('estadoError').hidden = false;
    document.title = 'Pieza no encontrada | Lira Ferrer';
    return;
  }

  pintar(producto);
  cargarRelacionados(producto);

  function pintar(p) {
    document.title = `${p.nombre} | Lira Ferrer`;
    $('migaNombre').textContent = p.nombre;
    $('pEdicion').textContent = `Edición limitada · ${p.edicion_total} piezas`;
    $('pNombre').textContent = p.nombre;
    $('pDetalle').textContent = p.detalle;
    $('pPrecio').textContent = L.formatoCOP(p.precio);
    $('pDescripcion').textContent = p.descripcion;
    $('pSpecs').innerHTML = (p.especificaciones || []).map(s =>
      `<div><dt>${L.escapar(s.etiqueta)}</dt><dd>${L.escapar(s.valor)}</dd></div>`
    ).join('');

    const imagenes = (p.galeria && p.galeria.length) ? p.galeria : [p.imagen];
    L.Galeria.crear($('productoGaleria'), imagenes, p.nombre);

    // ----- Cantidad y stock -----
    const btnComprar = $('btnComprar');
    const menos = $('menos');
    const mas = $('mas');
    const stock = $('pStock');
    const max = Math.min(p.stock, L.CONFIG.MAX_UNIDADES);
    let cantidad = 1;

    const actualizar = () => {
      $('cantidad').textContent = cantidad;
      menos.disabled = cantidad <= 1;
      mas.disabled = cantidad >= max;
      btnComprar.href = `comprar.html?slug=${encodeURIComponent(p.slug)}&cantidad=${cantidad}`;
    };

    if (p.stock <= 0) {
      btnComprar.textContent = 'Agotada';
      btnComprar.classList.add('deshabilitado');
      btnComprar.removeAttribute('href');
      menos.disabled = mas.disabled = true;
      stock.textContent = 'Esta pieza está agotada. Escríbenos y te avisamos cuando haya una nueva edición.';
      stock.classList.add('urgente');
    } else {
      stock.textContent = p.stock <= 5
        ? `Solo quedan ${p.stock} piezas de esta edición`
        : 'Disponible · envío asegurado en 3 a 5 días hábiles';
      stock.classList.toggle('urgente', p.stock <= 5);
      menos.addEventListener('click', () => { if (cantidad > 1) { cantidad--; actualizar(); } });
      mas.addEventListener('click', () => { if (cantidad < max) { cantidad++; actualizar(); } });
      actualizar();
    }

    $('btnWhatsapp').href = L.whatsappUrl(
      `Hola, me interesa la pieza ${p.nombre} (${L.formatoCOP(p.precio)}). ¿Me pueden dar más información?`
    );

    $('productoDetalle').hidden = false;
  }

  async function cargarRelacionados(p) {
    const todos = await L.obtenerProductos();
    const otros = todos.filter(x => x.slug !== p.slug);
    if (!otros.length) return;
    const grid = $('relacionadosGrid');
    grid.innerHTML = otros.map(L.tarjetaPieza).join('');
    $('relacionados').hidden = false;
    L.vigilarImagenes(grid);
    L.activarReveal(grid);
  }
})();
