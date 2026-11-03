/* =========================================================
   LIRA FERRER — Detalle de producto
   Los datos vienen de api/productos.php. Los textos de marca
   (garantías, cuidado, stock) vienen de api/sitio.php.
   ========================================================= */
(async function () {
  'use strict';

  const L = window.Lira;
  const $ = id => document.getElementById(id);
  const slug = L.param('slug');

  await L.iniciarLayout();
  await L.cargarSitio();

  const texto = (id, valor) => {
    const el = $(id);
    if (el) el.textContent = valor || '';
  };

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
    const precio = Number(p.precio) || 0;
    const edicion = Number(p.edicion_total) || 0;

    document.title = `${p.nombre} | ${L.config('marca_nombre', 'Lira Ferrer')}`;
    const meta = $('metaDescripcion');
    if (meta && p.descripcion) meta.setAttribute('content', String(p.descripcion).slice(0, 155));

    texto('migaNombre', p.nombre);
    texto('pEdicion', edicion > 0 ? `Edición limitada · ${edicion} piezas` : 'Edición limitada');
    texto('pNombre', p.nombre);
    texto('pDetalle', p.detalle);
    texto('pPrecio', L.precioTexto(p));
    texto('pDescripcion', p.descripcion);

    const specs = $('pSpecs');
    if (specs) {
      specs.innerHTML = (p.especificaciones || []).map(s =>
        `<div><dt>${L.escapar(s.etiqueta)}</dt><dd>${L.escapar(s.valor)}</dd></div>`
      ).join('');
    }

    const imagenes = (p.galeria && p.galeria.length) ? p.galeria : [p.imagen];
    L.Galeria.crear($('productoGaleria'), imagenes, p.nombre);

    texto('garantia1', L.config('garantia_1'));
    texto('garantia2', L.config('garantia_2'));
    texto('garantia3', L.config('garantia_3'));
    texto('pCuidado', L.config('cuidado_texto'));

    const btnComprar = $('btnComprar');
    const menos = $('menos');
    const mas = $('mas');
    const stock = $('pStock');
    const max = Math.min(Number(p.stock) || 0, L.CONFIG.MAX_UNIDADES);
    let cantidad = 1;

    const actualizar = () => {
      $('cantidad').textContent = cantidad;
      menos.disabled = cantidad <= 1;
      mas.disabled = cantidad >= max;
      btnComprar.href = `comprar.html?slug=${encodeURIComponent(p.slug)}&cantidad=${cantidad}`;
    };

    if (precio <= 0) {
      btnComprar.textContent = 'Consultar por WhatsApp';
      btnComprar.href = L.whatsappUrl(`Hola, me interesa la pieza ${p.nombre}. ¿Me pueden dar más información?`);
      btnComprar.target = '_blank';
      btnComprar.rel = 'noopener';
      menos.disabled = mas.disabled = true;
      stock.textContent = '';
    } else if ((Number(p.stock) || 0) <= 0) {
      btnComprar.textContent = 'Agotada';
      btnComprar.classList.add('deshabilitado');
      btnComprar.removeAttribute('href');
      menos.disabled = mas.disabled = true;
      stock.textContent = L.config('stock_agotada');
      stock.classList.add('urgente');
    } else {
      const plantilla = (Number(p.stock) || 0) <= 5
        ? L.config('stock_ultimas', 'Solo quedan {n} piezas de esta edición')
        : L.config('stock_disponible', 'Disponible');
      stock.textContent = plantilla.replace('{n}', String(p.stock));
      stock.classList.toggle('urgente', Number(p.stock) <= 5);
      menos.addEventListener('click', () => { if (cantidad > 1) { cantidad--; actualizar(); } });
      mas.addEventListener('click', () => { if (cantidad < max) { cantidad++; actualizar(); } });
      actualizar();
    }

    const wa = $('btnWhatsapp');
    if (wa) {
      wa.href = L.whatsappUrl(`Hola, me interesa la pieza ${p.nombre} (${L.precioTexto(p)}). ¿Me pueden dar más información?`);
    }

    $('productoDetalle').hidden = false;

    L.jsonLd({
      '@context': 'https://schema.org',
      '@type': 'Product',
      name: p.nombre,
      description: p.descripcion || undefined,
      image: L.urlSegura(p.imagen) ? new URL(L.urlSegura(p.imagen), location.href).href : undefined,
      brand: { '@type': 'Brand', name: L.config('marca_nombre', 'Lira Ferrer') },
      material: 'Piel de vacuno, oro y esmeralda colombiana',
      offers: precio > 0 ? {
        '@type': 'Offer',
        price: precio,
        priceCurrency: 'COP',
        availability: (Number(p.stock) || 0) > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
        url: location.href
      } : undefined
    });
  }

  async function cargarRelacionados(p) {
    let todos = [];
    try {
      todos = await L.obtenerProductos();
    } catch (e) {
      return;
    }
    const otros = todos.filter(x => x.slug !== p.slug);
    if (!otros.length) return;
    const grid = $('relacionadosGrid');
    grid.innerHTML = otros.map(L.tarjetaPieza).join('');
    $('relacionados').hidden = false;
    L.vigilarImagenes(grid);
    L.activarReveal(grid);
  }
})();
