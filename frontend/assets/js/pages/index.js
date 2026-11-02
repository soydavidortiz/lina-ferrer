/* =========================================================
   LIRA FERRER — index.js
   ========================================================= */
(function () {
  'use strict';

  // ---------- CONFIGURACIÓN ----------
  const WHATSAPP = '573000000000';        // ← 57 + tu número, sin espacios
  const API_PRODUCTOS = 'api/productos.php';

  // Productos de respaldo (se usan si la API no responde)
  const PRODUCTOS_LOCAL = [
    {
      slug: 'herencia-borgona',
      nombre: 'Herencia Borgoña',
      detalle: 'Vino tinto · Lira en filigrana de oro con esmeralda',
      precio: 2850000,
      imagen: 'assets/img/bolsos/bolso-vinotinto.png'
    },
    {
      slug: 'perla-eterna',
      nombre: 'Perla Eterna',
      detalle: 'Blanco perla · Lira en filigrana de oro con esmeralda',
      precio: 2850000,
      imagen: 'assets/img/bolsos/bolso-blanco.png'
    }
  ];

  // ---------- UTILIDADES ----------
  const formatoCOP = n => '$' + Number(n).toLocaleString('es-CO') + ' COP';

  const escapar = txt => String(txt ?? '')
    .replace(/&/g, '&amp;').replace(/</g, '&lt;')
    .replace(/>/g, '&gt;').replace(/"/g, '&quot;');

  // ---------- 1. NAV: fondo al hacer scroll ----------
  const nav = document.getElementById('nav');
  const alScroll = () => nav.classList.toggle('scrolled', window.scrollY > 60);
  window.addEventListener('scroll', alScroll, { passive: true });
  alScroll();

  // ---------- 2. MENÚ MÓVIL ----------
  const toggle = document.getElementById('navToggle');
  const links = document.getElementById('navLinks');
  toggle.addEventListener('click', () => links.classList.toggle('abierto'));
  links.querySelectorAll('a').forEach(a =>
    a.addEventListener('click', () => links.classList.remove('abierto'))
  );

  // ---------- 3. IMÁGENES FALTANTES (evita texto encima) ----------
  function vigilarImagenes(scope) {
    scope.querySelectorAll('img').forEach(img => {
      const marcar = () => img.classList.add('img-falta');
      if (img.complete && img.naturalWidth === 0) marcar();
      img.addEventListener('error', marcar);
    });
  }
  vigilarImagenes(document);

  // ---------- 4. ANIMACIONES DE APARICIÓN ----------
  let observer = null;
  function activarReveal(scope) {
    const elementos = scope.querySelectorAll('.reveal:not(.visible)');
    if (!('IntersectionObserver' in window)) {
      elementos.forEach(el => el.classList.add('visible'));
      return;
    }
    if (!observer) {
      observer = new IntersectionObserver(entries => {
        entries.forEach(e => {
          if (e.isIntersecting) {
            e.target.classList.add('visible');
            observer.unobserve(e.target);
          }
        });
      }, { threshold: 0.15 });
    }
    elementos.forEach(el => observer.observe(el));
  }
  activarReveal(document);

  // ---------- 5. COLECCIÓN DESDE LA API ----------
  const grid = document.getElementById('coleccionGrid');

  function renderProductos(lista) {
    grid.innerHTML = lista.map(p => {
      const slug = escapar(p.slug || p.id);
      const url = `producto.html?slug=${encodeURIComponent(p.slug || p.id)}`;
      const img = escapar(p.imagen || p.imagen_url || '');
      return `
        <article class="pieza reveal">
          <a href="${url}" class="pieza-img">
            <img src="${img}" alt="${escapar(p.nombre)}" loading="lazy">
          </a>
          <div class="pieza-info">
            <h3>${escapar(p.nombre)}</h3>
            <p class="pieza-detalle">${escapar(p.detalle || p.descripcion_corta || '')}</p>
            <span class="pieza-precio">${formatoCOP(p.precio)}</span>
            <a href="${url}" class="btn btn-linea" data-slug="${slug}">Ver pieza</a>
          </div>
        </article>`;
    }).join('');
    vigilarImagenes(grid);
    activarReveal(grid);
  }

  // Solo intenta la API si el sitio corre en un servidor (no con doble clic)
  if (location.protocol.startsWith('http')) {
    fetch(API_PRODUCTOS, { headers: { Accept: 'application/json' } })
      .then(r => (r.ok ? r.json() : Promise.reject(r.status)))
      .then(data => {
        const lista = Array.isArray(data) ? data : (data.data || data.productos || []);
        if (lista.length) renderProductos(lista);
      })
      .catch(() => { /* se queda el HTML con PRODUCTOS_LOCAL */ });
  }

  // ---------- 6. WHATSAPP DEL FOOTER ----------
  const wa = document.getElementById('linkWhatsapp');
  if (wa) {
    const msg = 'Hola, quisiera más información sobre las piezas Lira Ferrer';
    wa.href = `https://wa.me/${WHATSAPP}?text=${encodeURIComponent(msg)}`;
  }

  // ---------- 7. AÑO AUTOMÁTICO ----------
  const anio = document.getElementById('anio');
  if (anio) anio.textContent = new Date().getFullYear();

  // Exponer datos locales por si otras páginas los necesitan
  window.LIRA_PRODUCTOS_LOCAL = PRODUCTOS_LOCAL;
})();
