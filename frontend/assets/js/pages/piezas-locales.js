/* =========================================================
   LIRA FERRER — Piezas locales (respaldo mientras la API no las trae)
   Guardar en: assets/js/pages/piezas-locales.js
   Cargar en index.html (después de index.js) y en producto.html
   (después de producto.js).

   Qué hace:
   - INICIO:  si la API no devuelve una pieza "local", agrega su tarjeta al final.
   - PRODUCTO: si se abre una pieza local y la API responde "no encontrada",
               dibuja la ficha con los datos de aquí. También completa los
               "relacionados" para que el selector de color muestre las 3.
   - Cuando la pieza exista en la base de datos, la API manda: este archivo
     detecta que ya está y no hace nada. Se puede borrar sin romper nada.
   ========================================================= */
(function () {
  'use strict';

  var IMG = 'assets/img/bolsos/';

  /* Solo las piezas con local:true se inyectan. Las demás se usan únicamente
     para armar tarjetas de "relacionados" cuando se muestra una pieza local. */
  var CATALOGO = [
    {
      slug: 'herencia-borgona',
      nombre: 'Herencia Borgoña',
      detalle: 'Vino tinto · Lira en filigrana de oro con esmeralda',
      precio: '$2.850.000 COP',
      imagen: IMG + 'bolso-vinotinto.png'
    },
    {
      slug: 'perla-eterna',
      nombre: 'Perla Eterna',
      detalle: 'Blanco perla · Lira en filigrana de oro con esmeralda',
      precio: '$2.850.000 COP',
      imagen: IMG + 'bolso-blanco.png'
    },
    {
      /* ← NOMBRE Y SLUG PROVISIONALES: cámbialos por los reales */
      local: true,
      slug: 'selva-viva',
      nombre: 'Selva Viva',
      detalle: 'Verde oliva · Lira en filigrana de oro con esmeralda',
      precio: '$2.850.000 COP',
      imagen: IMG + 'bolso-verde.png',
      edicion: 'Edición limitada',
      descripcion: 'Un verde oliva sereno, del color de la selva después de la lluvia. Selva Viva nace de una piel de vacuno de primera calidad, trenzada a mano por artesanos colombianos y coronada con la lira en filigrana de oro que sostiene una esmeralda colombiana certificada.',
      stock: 'Disponible · envío asegurado en 3 a 5 días hábiles',
      specs: [
        ['Piel', 'Vacuno de primera calidad, curtido vegetal'],
        ['Asas', 'Trenzadas a mano'],
        ['Emblema', 'Lira en filigrana de oro'],
        ['Gema', 'Esmeralda colombiana certificada'],
        ['Medidas', '28 × 22 × 12 cm']
      ]
    }
  ];

  var $ = function (s, c) { return (c || document).querySelector(s); };
  function porSlug(slug) { return CATALOGO.filter(function (p) { return p.slug === slug; })[0]; }
  function visible(el) { return !!el && !el.hidden && window.getComputedStyle(el).display !== 'none'; }

  /* Misma estructura que las tarjetas que dibuja la API */
  function tarjeta(p) {
    var url = 'producto.html?slug=' + encodeURIComponent(p.slug);
    var a = document.createElement('article');
    a.className = 'pieza reveal';
    a.innerHTML =
      '<a href="' + url + '" class="pieza-img" aria-label="Ver ' + p.nombre + '">' +
        '<img src="' + p.imagen + '" alt="' + p.nombre + '" loading="lazy" decoding="async">' +
      '</a>' +
      '<div class="pieza-info">' +
        '<h3>' + p.nombre + '</h3>' +
        '<p class="pieza-detalle">' + p.detalle + '</p>' +
        '<span class="pieza-precio">' + p.precio + '</span>' +
        '<a href="' + url + '" class="btn btn-linea">Ver pieza</a>' +
      '</div>';
    return a;
  }

  function enlaceDe(grid, slug) { return grid.querySelector('a[href*="slug=' + slug + '"]'); }

  /* Agrega (o deja al final) las piezas locales que falten en una grilla */
  function completar(grid, excluir) {
    CATALOGO.forEach(function (p) {
      if (!p.local || p.slug === excluir) return;
      var ya = enlaceDe(grid, p.slug);
      if (!ya) { grid.appendChild(tarjeta(p)); return; }
      var carta = ya.closest('.pieza');
      if (carta && grid.lastElementChild !== carta) grid.appendChild(carta);   // siempre al final
    });
  }

  /* WhatsApp: reutiliza el enlace que app.js / index.js ya configuran */
  function baseWhatsapp() {
    var el = $('[data-whatsapp]') || $('#linkWhatsapp');
    var href = el && el.getAttribute('href');
    return href && href !== '#' ? href : '';
  }
  function conMensaje(base, texto) {
    if (!base) return '';
    if (/[?&]text=/.test(base)) return base;
    return base + (base.indexOf('?') > -1 ? '&' : '?') + 'text=' + encodeURIComponent(texto);
  }

  /* ================= INICIO ================= */
  var grilla = $('#coleccionGrid');
  if (grilla) {
    new MutationObserver(function () { completar(grilla); }).observe(grilla, { childList: true });
    completar(grilla);
  }

  /* ================= PRODUCTO ================= */
  var detalle = $('#productoDetalle');
  if (detalle) {
    var slug = new URLSearchParams(window.location.search).get('slug') || '';
    var actual = porSlug(slug);
    var rel = $('#relacionadosGrid');
    var modoLocal = false;

    function relacionados() {
      if (!rel) return;
      if (modoLocal) {
        CATALOGO.forEach(function (p) {
          if (p.slug !== slug && !enlaceDe(rel, p.slug)) rel.appendChild(tarjeta(p));
        });
      } else if (!actual || !actual.local) {
        completar(rel, slug);   // pieza de la API: añade la local a "relacionados"
      }
      var seccion = $('#relacionados');
      if (seccion && rel.children.length) seccion.hidden = false;
    }
    if (rel) {
      new MutationObserver(relacionados).observe(rel, { childList: true });
      relacionados();
    }

    function dibujarLocal(p) {
      modoLocal = true;
      function texto(id, t) { var el = $('#' + id); if (el) el.textContent = t; }
      texto('pEdicion', p.edicion);
      texto('pNombre', p.nombre);
      texto('pDetalle', p.detalle);
      texto('pPrecio', p.precio);
      texto('pDescripcion', p.descripcion);
      texto('pStock', p.stock);
      texto('migaNombre', p.nombre);
      document.title = p.nombre + ' | Lira Ferrer';

      var gal = $('#productoGaleria');
      if (gal) gal.innerHTML = '<img src="' + p.imagen + '" alt="' + p.nombre + '">';
      var specs = $('#pSpecs');
      if (specs) specs.innerHTML = p.specs.map(function (s) {
        return '<div><dt>' + s[0] + '</dt><dd>' + s[1] + '</dd></div>';
      }).join('');

      // Cantidad
      var cant = 1;
      var elCant = $('#cantidad');
      function pintar() { if (elCant) elCant.textContent = String(cant); }
      var menos = $('#menos'), mas = $('#mas');
      if (menos) menos.onclick = function () { cant = Math.max(1, cant - 1); pintar(); };
      if (mas) mas.onclick = function () { cant = Math.min(10, cant + 1); pintar(); };

      // Compra y consulta: por WhatsApp
      function abrir(texto) {
        var url = conMensaje(baseWhatsapp(), texto);
        if (url) window.open(url, '_blank', 'noopener');
      }
      var comprar = $('#btnComprar');
      if (comprar) comprar.onclick = function (e) {
        e.preventDefault();
        abrir('Hola, quiero comprar ' + p.nombre + ' (cantidad: ' + cant + ').');
      };
      var consulta = $('#btnWhatsapp');
      if (consulta) consulta.onclick = function (e) {
        e.preventDefault();
        abrir('Hola, quiero más información sobre ' + p.nombre + '.');
      };

      var carga = $('#estadoCarga'), error = $('#estadoError');
      if (carga) carga.hidden = true;
      if (error) error.hidden = true;
      detalle.hidden = false;
      relacionados();
    }

    /* Solo actúa si piden una pieza local y la API no la encontró */
    if (actual && actual.local) {
      var hecho = false;
      var revisar = function (forzar) {
        if (hecho || visible(detalle)) { hecho = hecho || visible(detalle); return; }   // la API sí la trajo
        if (forzar || visible($('#estadoError'))) { hecho = true; dibujarLocal(actual); }
      };
      ['#estadoError', '#estadoCarga', '#productoDetalle'].forEach(function (sel) {
        var el = $(sel);
        if (el) new MutationObserver(function () { revisar(false); })
          .observe(el, { attributes: true, attributeFilter: ['hidden', 'class', 'style'] });
      });
      window.addEventListener('load', function () { setTimeout(function () { revisar(false); }, 600); });
      setTimeout(function () { revisar(true); }, 6000);   // último recurso si la API no responde
    }
  }
})();