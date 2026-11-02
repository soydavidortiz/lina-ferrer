/* =========================================================
   LIRA FERRER — Efectos de interfaz (mejora progresiva)
   Guardar en: assets/js/pages/efectos.js
   Cargar DESPUÉS de index.js. No depende de él: si falla algo aquí,
   el sitio sigue funcionando; solo se pierden los detalles.
   ========================================================= */
(function () {
  'use strict';

  var reducir = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var puntero = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
  var $ = function (sel, ctx) { return (ctx || document).querySelector(sel); };

  /* ---------- 1. Enlace activo del menú según la sección visible ---------- */
  (function menuActivo() {
    if (!('IntersectionObserver' in window)) return;
    var enlaces = Array.prototype.slice.call(document.querySelectorAll('.nav-links a[href^="#"]'));
    var porId = {};
    enlaces.forEach(function (a) { porId[a.getAttribute('href').slice(1)] = a; });

    var io = new IntersectionObserver(function (entradas) {
      entradas.forEach(function (e) {
        if (!e.isIntersecting) return;
        enlaces.forEach(function (a) { a.classList.remove('activo'); });
        var activo = porId[e.target.id];
        if (activo) activo.classList.add('activo');
      });
    }, { rootMargin: '-45% 0px -50% 0px' });   // franja fina en el centro de la pantalla

    ['inicio', 'coleccion', 'maison', 'oficios', 'contacto'].forEach(function (id) {
      var sec = document.getElementById(id);
      if (sec) io.observe(sec);
    });
  })();

  /* ---------- 2. Accesibilidad del menú móvil ---------- */
  (function menuMovil() {
    var boton = $('#navToggle');
    var lista = $('#navLinks');
    if (!boton || !lista) return;

    boton.setAttribute('aria-expanded', 'false');
    new MutationObserver(function () {
      var abierto = lista.classList.contains('abierto');
      boton.setAttribute('aria-expanded', String(abierto));
      boton.setAttribute('aria-label', abierto ? 'Cerrar menú' : 'Abrir menú');
    }).observe(lista, { attributes: true, attributeFilter: ['class'] });

    lista.addEventListener('click', function (e) {
      if (e.target.closest('a')) lista.classList.remove('abierto');
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && lista.classList.contains('abierto')) {
        lista.classList.remove('abierto');
        boton.focus();
      }
    });
  })();

  /* ---------- 3. WhatsApp: reutiliza el enlace del footer ----------
     index.js ya configura #linkWhatsapp. Aquí se copia a los otros dos
     botones. Si el enlace aún es "#", se mantienen ocultos. */
  (function whatsapp() {
    var origen = $('#linkWhatsapp');
    var boton = $('#linkWhatsapp2');
    var caja = boton && boton.closest('.coleccion-ayuda');
    var flotante = $('#waFlotante');
    if (!origen) return;

    function sincronizar() {
      var href = origen.getAttribute('href');
      var listo = !!href && href !== '#';
      if (boton && listo) boton.setAttribute('href', href);
      if (flotante && listo) flotante.setAttribute('href', href);
      if (caja) caja.hidden = !listo;
      if (flotante) flotante.hidden = !listo;
    }
    new MutationObserver(sincronizar).observe(origen, { attributes: true, attributeFilter: ['href'] });
    sincronizar();
  })();

  /* ---------- 4. Scroll: parallax del hero + botón flotante ---------- */
  (function alDesplazar() {
    var hero = $('.hero');
    var flotante = $('#waFlotante');
    var pendiente = false;

    function actualizar() {
      var y = window.pageYOffset || 0;
      if (flotante) flotante.classList.toggle('visible', y > 600);
      if (hero && !reducir && window.innerWidth > 860 && y < window.innerHeight * 1.2) {
        hero.style.setProperty('--py', Math.min(y * 0.07, 60).toFixed(1));   // foto baja despacio
        hero.style.setProperty('--sx', (-y * 0.12).toFixed(1));              // marca de agua se desliza
      }
      pendiente = false;
    }
    window.addEventListener('scroll', function () {
      if (!pendiente) { pendiente = true; requestAnimationFrame(actualizar); }
    }, { passive: true });
    actualizar();
  })();

  /* ---------- 5. Inclinación 3D sutil del bolso en las tarjetas ----------
     Delegación en document: funciona también con tarjetas que el JS de la API
     inserte después. Solo con ratón y sin "movimiento reducido". */
  if (puntero && !reducir) {
    document.addEventListener('pointermove', function (e) {
      var escenario = e.target.closest && e.target.closest('.pieza-img');
      if (!escenario) return;
      var r = escenario.getBoundingClientRect();
      var x = (e.clientX - r.left) / r.width - 0.5;
      var y = (e.clientY - r.top) / r.height - 0.5;
      escenario.classList.add('moviendo');
      escenario.style.setProperty('--ry', (x * 8).toFixed(2) + 'deg');
      escenario.style.setProperty('--rx', (-y * 6).toFixed(2) + 'deg');
    });
    document.addEventListener('pointerout', function (e) {
      var escenario = e.target.closest && e.target.closest('.pieza-img');
      if (!escenario || escenario.contains(e.relatedTarget)) return;
      escenario.classList.remove('moviendo');
      escenario.style.removeProperty('--rx');
      escenario.style.removeProperty('--ry');
    });
  }

  /* ---------- 6. Aparición al hacer scroll (respaldo) ----------
     Cubre tarjetas añadidas por la API después de que index.js ya observó la página. */
  (function revelar() {
    var todos = function () { return document.querySelectorAll('.reveal:not(.visible)'); };
    if (!('IntersectionObserver' in window)) {
      Array.prototype.forEach.call(todos(), function (el) { el.classList.add('visible'); });
      return;
    }
    var io = new IntersectionObserver(function (entradas) {
      entradas.forEach(function (e) {
        if (e.isIntersecting) { e.target.classList.add('visible'); io.unobserve(e.target); }
      });
    }, { threshold: 0.12 });

    function observar() { Array.prototype.forEach.call(todos(), function (el) { io.observe(el); }); }
    observar();

    var grilla = $('#coleccionGrid');
    if (grilla) new MutationObserver(observar).observe(grilla, { childList: true });
  })();
})();