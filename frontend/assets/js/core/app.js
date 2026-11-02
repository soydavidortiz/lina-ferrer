/* =========================================================
   LIRA FERRER — Núcleo compartido (producto, comprar, gracias)
   ========================================================= */
window.Lira = (function () {
  'use strict';

  // ---------- CONFIGURACIÓN ----------
  const CONFIG = {
    WHATSAPP: '573000000000',   // ← 57 + tu número, sin espacios
    API: 'api/',
    MAX_UNIDADES: 3,
    ENVIO: 0,
    METODOS: {
      transferencia: 'Transferencia bancaria',
      tarjeta: 'Tarjeta (enlace de pago)',
      asesor: 'Coordinar con una asesora'
    }
  };

  // ---------- PRODUCTOS DE RESPALDO (si no hay servidor/BD) ----------
  const PRODUCTOS_LOCAL = [
    {
      id: 1,
      slug: 'herencia-borgona',
      nombre: 'Herencia Borgoña',
      color: 'Vino tinto',
      detalle: 'Vino tinto · Lira en filigrana de oro con esmeralda',
      descripcion: 'Un vino tinto profundo, del color de las tardes largas del Caribe. Herencia Borgoña nace de una piel de vacuno de primera calidad, trabajada y trenzada a mano por artesanos colombianos, y coronada con la lira en filigrana de oro que sostiene una esmeralda colombiana certificada.',
      precio: 2850000,
      stock: 50,
      edicion_total: 50,
      imagen: 'assets/img/bolsos/bolso-vinotinto.png',
      galeria: [
        'assets/img/bolsos/bolso-vinotinto.png',
        'assets/img/galeria/borgona-1.jpg',
        'assets/img/galeria/borgona-2.jpg'
      ],
      especificaciones: [
        { etiqueta: 'Piel', valor: 'Vacuno de primera calidad, curtido vegetal' },
        { etiqueta: 'Asas', valor: 'Trenzadas a mano' },
        { etiqueta: 'Emblema', valor: 'Lira en filigrana de oro' },
        { etiqueta: 'Gema', valor: 'Esmeralda colombiana certificada' },
        { etiqueta: 'Medidas', valor: '28 × 22 × 12 cm' }
      ]
    },
    {
      id: 2,
      slug: 'perla-eterna',
      nombre: 'Perla Eterna',
      color: 'Blanco perla',
      detalle: 'Blanco perla · Lira en filigrana de oro con esmeralda',
      descripcion: 'Blanco perla, luminoso y atemporal. Perla Eterna está pensada para acompañarte de generación en generación: piel de vacuno seleccionada, asas trenzadas a mano y la lira de la casa en filigrana de oro con su esmeralda colombiana certificada.',
      precio: 2850000,
      stock: 50,
      edicion_total: 50,
      imagen: 'assets/img/bolsos/bolso-blanco.png',
      galeria: [
        'assets/img/bolsos/bolso-blanco.png',
        'assets/img/galeria/galeria-2.jpg',
        'assets/img/galeria/galeria-1.jpg'
      ],
      especificaciones: [
        { etiqueta: 'Piel', valor: 'Vacuno de primera calidad, curtido vegetal' },
        { etiqueta: 'Asas', valor: 'Trenzadas a mano' },
        { etiqueta: 'Emblema', valor: 'Lira en filigrana de oro' },
        { etiqueta: 'Gema', valor: 'Esmeralda colombiana certificada' },
        { etiqueta: 'Medidas', valor: '28 × 22 × 12 cm' }
      ]
    }
  ];

  const enServidor = location.protocol.startsWith('http');

  // ---------- UTILIDADES ----------
  const formatoCOP = n => '$' + Number(n || 0).toLocaleString('es-CO') + ' COP';
  const escapar = txt => String(txt ?? '')
    .replace(/&/g, '&amp;').replace(/</g, '&lt;')
    .replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
  const param = nombre => new URLSearchParams(location.search).get(nombre);
  const whatsappUrl = msg => `https://wa.me/${CONFIG.WHATSAPP}?text=${encodeURIComponent(msg)}`;
  const crearError = (mensaje, extra) => Object.assign(new Error(mensaje), extra || {});

  // ---------- API ----------
  async function api(ruta, opciones = {}) {
    if (!enServidor) {
      throw crearError('Para enviar pedidos, el sitio debe abrirse desde un servidor (XAMPP, Laragon o hosting).', { sinServidor: true });
    }
    let res;
    try {
      res = await fetch(CONFIG.API + ruta, {
        headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
        ...opciones
      });
    } catch (e) {
      throw crearError('No pudimos conectar con el servidor.', { sinServidor: true });
    }

    let json;
    try {
      json = await res.json();
    } catch (e) {
      throw crearError('El servidor respondió de forma inesperada.', { sinServidor: true, status: res.status });
    }

    if (!res.ok || json.ok === false) {
      throw crearError(json.mensaje || 'Ocurrió un error.', { status: res.status, errores: json.errores || {} });
    }
    return json.data;
  }

  async function obtenerProductos() {
    if (!enServidor) return PRODUCTOS_LOCAL;
    try {
      const lista = await api('productos.php');
      return Array.isArray(lista) && lista.length ? lista : PRODUCTOS_LOCAL;
    } catch (e) {
      return PRODUCTOS_LOCAL;
    }
  }

  async function obtenerProducto(slug) {
    const local = () => PRODUCTOS_LOCAL.find(p => p.slug === slug) || null;
    if (!enServidor) return local();
    try {
      return await api('productos.php?slug=' + encodeURIComponent(slug));
    } catch (e) {
      if (e.status === 404) return null;   // existe la API pero no la pieza
      return local();                      // API caída → datos locales
    }
  }

  // ---------- COMPONENTES SIMPLES ----------
  function tarjetaPieza(p) {
    const url = 'producto.html?slug=' + encodeURIComponent(p.slug);
    return `
      <article class="pieza reveal">
        <a href="${url}" class="pieza-img">
          <img src="${escapar(p.imagen)}" alt="${escapar(p.nombre)}" loading="lazy">
        </a>
        <div class="pieza-info">
          <h3>${escapar(p.nombre)}</h3>
          <p class="pieza-detalle">${escapar(p.detalle)}</p>
          <span class="pieza-precio">${formatoCOP(p.precio)}</span>
          <a href="${url}" class="btn btn-linea">Ver pieza</a>
        </div>
      </article>`;
  }

  function vigilarImagenes(scope) {
    scope.querySelectorAll('img').forEach(img => {
      if (img.dataset.vigilada) return;
      img.dataset.vigilada = '1';
      const marcar = () => img.classList.add('img-falta');
      if (img.complete && img.naturalWidth === 0) marcar();
      img.addEventListener('error', marcar);
      img.addEventListener('load', () => img.classList.remove('img-falta'));
    });
  }

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

  // ---------- LAYOUT COMÚN ----------
  function iniciarLayout() {
    const nav = document.getElementById('nav');
    if (nav && !nav.classList.contains('nav-solida')) {
      const alScroll = () => nav.classList.toggle('scrolled', window.scrollY > 60);
      window.addEventListener('scroll', alScroll, { passive: true });
      alScroll();
    }

    const toggle = document.getElementById('navToggle');
    const links = document.getElementById('navLinks');
    if (toggle && links) {
      toggle.addEventListener('click', () => links.classList.toggle('abierto'));
      links.querySelectorAll('a').forEach(a =>
        a.addEventListener('click', () => links.classList.remove('abierto'))
      );
    }

    document.querySelectorAll('[data-whatsapp]').forEach(a => {
      a.href = whatsappUrl(a.dataset.whatsapp || 'Hola, quisiera más información sobre las piezas Lira Ferrer');
      a.target = '_blank';
      a.rel = 'noopener';
    });

    const anio = document.getElementById('anio');
    if (anio) anio.textContent = new Date().getFullYear();

    vigilarImagenes(document);
    activarReveal(document);
  }

  return {
    CONFIG, enServidor, formatoCOP, escapar, param, whatsappUrl,
    api, obtenerProductos, obtenerProducto,
    tarjetaPieza, vigilarImagenes, activarReveal, iniciarLayout
  };
})();
