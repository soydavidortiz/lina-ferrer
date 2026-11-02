/* =========================================================
   LIRA FERRER — Paleta por pieza (página de producto)
   Guardar en: assets/js/pages/producto-tema.js
   Cargar DESPUÉS de producto.js. No modifica sus datos: solo observa el DOM
   que producto.js rellena y, a partir de ahí:
     1. detecta el color de la pieza y aplica data-tema en <body>
     2. copia el nombre al banner
     3. clasifica la galería (una imagen / varias)
     4. construye el selector "Color" con las demás piezas
   ========================================================= */
(function () {
  'use strict';

  var $ = function (s, c) { return (c || document).querySelector(s); };

  /* Colores conocidos. Para añadir uno nuevo: agrega aquí su expresión y
     una paleta body[data-tema="..."] en paginas.css. */
  var TEMAS = [
    { id: 'perla', re: /blanc|perla|marfil|crema/i,        color: '#ece4d6' },
    { id: 'vino',  re: /vino|borgo|burdeos|granate/i,      color: '#6d1a28' },
    { id: 'oliva', re: /verde|oliva|musgo|bosque/i, color: '#4b5c2c' }
  ];

  function detectar(texto) {
    if (!texto) return null;
    for (var i = 0; i < TEMAS.length; i++) {
      if (TEMAS[i].re.test(texto)) return TEMAS[i];
    }
    return null;
  }

  /* ---------- 1 y 2. Tema + nombre en el banner ---------- */
  function aplicarTema() {
    var detalle = $('#pDetalle');
    var nombre = $('#pNombre');
    var slug = new URLSearchParams(window.location.search).get('slug') || '';
    var img = $('#productoGaleria img');

    // Prioridad: línea de color > nombre > slug > archivo de imagen
    var tema =
      detectar(detalle && detalle.textContent) ||
      detectar(nombre && nombre.textContent) ||
      detectar(slug) ||
      detectar(img && (img.getAttribute('src') || ''));

    if (tema) document.body.setAttribute('data-tema', tema.id);

    var marca = $('#bannerMarca');
    if (marca && nombre) marca.textContent = nombre.textContent;
  }

  /* ---------- 3. Galería ---------- */
  function clasificarGaleria() {
    var g = $('#productoGaleria');
    if (!g) return;
    var n = g.querySelectorAll('img').length;
    g.classList.toggle('galeria-simple', n === 1);
    g.classList.toggle('galeria-multiple', n > 1);
    aplicarTema();   // por si el tema se dedujo de la imagen
  }

  /* ---------- 4. Selector de color con las demás piezas ---------- */
  function construirColores() {
    var detalle = $('#pDetalle');
    var tarjetas = document.querySelectorAll('#relacionadosGrid .pieza');
    if (!detalle || !tarjetas.length) return;

    var actual = detectar(detalle.textContent) || detectar(($('#pNombre') || {}).textContent) ||
                 detectar(new URLSearchParams(window.location.search).get('slug'));

    var items = [];
    if (actual) items.push({ tema: actual, href: window.location.href, nombre: ($('#pNombre') || {}).textContent || '', activo: true });

    Array.prototype.forEach.call(tarjetas, function (t) {
      var enlace = t.querySelector('a[href]');
      var info = t.querySelector('.pieza-detalle');
      var titulo = t.querySelector('h3');
      var tema = detectar(info && info.textContent) || detectar(titulo && titulo.textContent);
      if (enlace && tema && !items.some(function (i) { return i.tema.id === tema.id; })) {
        items.push({ tema: tema, href: enlace.href, nombre: titulo ? titulo.textContent : '', activo: false });
      }
    });
    if (items.length < 2) return;

    // Orden fijo para que los puntos no cambien de lugar entre páginas
    items.sort(function (a, b) { return TEMAS.indexOf(a.tema) - TEMAS.indexOf(b.tema); });

    var previo = $('.producto-colores');
    if (previo) previo.remove();

    var caja = document.createElement('div');
    caja.className = 'producto-colores';
    caja.innerHTML = '<span>Color</span>';
    items.forEach(function (it) {
      var a = document.createElement('a');
      a.href = it.href;
      a.style.setProperty('--c', it.tema.color);
      a.setAttribute('aria-label', it.nombre || it.tema.id);
      a.title = it.nombre || it.tema.id;
      if (it.activo) { a.className = 'activo'; a.setAttribute('aria-current', 'true'); }
      caja.appendChild(a);
    });
    detalle.insertAdjacentElement('afterend', caja);
  }

  /* ---------- Observadores: producto.js rellena todo de forma asíncrona ---------- */
  function observar(sel, opciones, fn) {
    var el = $(sel);
    if (el && 'MutationObserver' in window) new MutationObserver(fn).observe(el, opciones);
  }
  observar('#pDetalle', { childList: true, characterData: true, subtree: true }, function () { aplicarTema(); construirColores(); });
  observar('#pNombre', { childList: true, characterData: true, subtree: true }, aplicarTema);
  observar('#productoGaleria', { childList: true, subtree: true }, clasificarGaleria);
  observar('#relacionadosGrid', { childList: true }, construirColores);

  aplicarTema();
  clasificarGaleria();
  construirColores();
})();