/* =========================================================
   LIRA FERRER — Núcleo compartido
   Todo el contenido de marca viene de la base de datos
   (api/sitio.php y api/productos.php). Aquí no hay datos
   de productos ni textos de marca escritos a mano.
   ========================================================= */
window.Lira = (function () {
  'use strict';

  // ---------- Constantes de interfaz / pedido ----------
  const CONFIG = {
    API: 'api/',
    MAX_UNIDADES: 3,
    ENVIO: 0,
    METODOS: {
      transferencia: 'Transferencia bancaria',
      tarjeta: 'Tarjeta (enlace de pago)',
      asesor: 'Coordinar con una asesora'
    }
  };

  let sitio = null;
  let sitioPromesa = null;

  // ---------- Utilidades ----------
  const formatoCOP = n => '$' + Number(n || 0).toLocaleString('es-CO') + ' COP';

  const escapar = txt => String(txt ?? '')
    .replace(/&/g, '&amp;').replace(/</g, '&lt;')
    .replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');

  const param = nombre => new URLSearchParams(location.search).get(nombre);

  const urlSegura = url => {
    const u = String(url || '').trim();
    if (!u) return '';
    if (/^(https?:\/\/|\/|\.\/|assets\/)/i.test(u)) return u;
    return '';
  };

  const crearError = (mensaje, extra) => Object.assign(new Error(mensaje), extra || {});

  // ---------- API ----------
  async function api(ruta, opciones = {}) {
    if (!location.protocol.startsWith('http')) {
      throw crearError('El sitio debe abrirse desde un servidor (XAMPP, Laragon o hosting).', { sinServidor: true });
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

  function cargarSitio(forzar = false) {
    if (sitio && !forzar) return Promise.resolve(sitio);
    if (!sitioPromesa || forzar) {
      sitioPromesa = api('sitio.php')
        .then(datos => {
          sitio = datos || {};
          return sitio;
        })
        .catch(err => {
          sitioPromesa = null;
          throw err;
        });
    }
    return sitioPromesa;
  }

  function config(clave, porDefecto = '') {
    const valor = sitio && sitio.config ? sitio.config[clave] : '';
    return valor === undefined || valor === null || valor === '' ? porDefecto : valor;
  }

  function whatsappUrl(mensaje) {
    const numero = String(config('whatsapp', '')).replace(/\D+/g, '');
    if (!numero) return '#';
    const texto = mensaje || config('whatsapp_mensaje', '');
    return `https://wa.me/${numero}${texto ? '?text=' + encodeURIComponent(texto) : ''}`;
  }

  async function obtenerProductos() {
    const lista = await api('productos.php');
    return Array.isArray(lista) ? lista : (lista && lista.productos) || [];
  }

  async function obtenerProducto(slug) {
    return api('productos.php?slug=' + encodeURIComponent(slug));
  }

  function precioTexto(producto) {
    const precio = Number(producto && producto.precio) || 0;
    return precio > 0 ? formatoCOP(precio) : config('precio_consultar', 'Precio a convenir');
  }

  // ---------- Aplicar configuración al layout ----------
  function aplicarSitio() {
    const c = (sitio && sitio.config) || {};

    // Ajustes de tienda editables desde el panel
    const envio = Number(c.envio_costo);
    if (Number.isFinite(envio) && envio >= 0) CONFIG.ENVIO = envio;

    const maxUnidades = parseInt(c.max_unidades, 10);
    if (Number.isFinite(maxUnidades) && maxUnidades > 0) CONFIG.MAX_UNIDADES = maxUnidades;

    const metodos = String(c.metodos_pago || '').split(',').map(s => s.trim()).filter(Boolean);
    if (metodos.length) {
      const etiquetas = { ...CONFIG.METODOS };
      CONFIG.METODOS = Object.fromEntries(metodos.map(m => [m, etiquetas[m] || m]));
    }

    document.querySelectorAll('[data-sitio]').forEach(el => {
      const clave = el.dataset.sitio;
      if (Object.prototype.hasOwnProperty.call(c, clave)) el.textContent = c[clave];
    });

    document.querySelectorAll('[data-sitio-mail]').forEach(el => {
      if (!c.email) { el.hidden = true; return; }
      el.href = 'mailto:' + c.email;
      el.textContent = c.email;
      el.hidden = false;
    });

    document.querySelectorAll('[data-sitio-tel]').forEach(el => {
      if (!c.telefono) { el.hidden = true; return; }
      el.href = 'tel:' + c.telefono.replace(/[^\d+]/g, '');
      el.textContent = c.telefono;
      el.hidden = false;
    });

    const instagram = document.querySelectorAll('[data-sitio-instagram]');
    instagram.forEach(el => {
      const url = urlSegura(c.instagram);
      if (!url) { el.hidden = true; return; }
      el.href = url;
      el.hidden = false;
    });

    const enlaceWa = whatsappUrl();
    document.querySelectorAll('[data-whatsapp]').forEach(el => {
      if (enlaceWa === '#') { el.hidden = true; return; }
      el.href = enlaceWa;
      el.target = '_blank';
      el.rel = 'noopener';
      el.hidden = false;
    });

    const waFlotante = document.getElementById('waFlotante');
    if (waFlotante) waFlotante.hidden = enlaceWa === '#';

    // Cintas de sellos (inicio y páginas internas)
    const sellos = [c.sello_1, c.sello_2, c.sello_3, c.sello_4].filter(Boolean);
    document.querySelectorAll('[data-sitio-cinta]').forEach(el => {
      el.innerHTML = sellos.map(s => `<span>${escapar(s)}</span>`).join('');
    });

    // Metadatos básicos
    if (c.marca_nombre) {
      const tituloActual = document.title.split('|')[0].trim();
      if (!tituloActual || tituloActual === 'Lira Ferrer' || tituloActual === 'Pieza') {
        document.title = `${c.marca_nombre} | ${c.marca_tagline || 'Alta marroquinería colombiana'}`;
      }
    }
    const metaDesc = document.getElementById('metaDescripcion');
    if (metaDesc && c.seo_descripcion) metaDesc.setAttribute('content', c.seo_descripcion);
    const ogTitulo = document.getElementById('ogTitulo');
    if (ogTitulo && c.marca_nombre) ogTitulo.setAttribute('content', `${c.marca_nombre} | ${c.marca_tagline || ''}`);
    const ogDesc = document.getElementById('ogDescripcion');
    if (ogDesc && c.seo_descripcion) ogDesc.setAttribute('content', c.seo_descripcion);
    const ogImagen = document.getElementById('ogImagen');
    if (ogImagen && c.hero_imagen) ogImagen.setAttribute('content', new URL(urlSegura(c.hero_imagen), location.href).href);
  }

  // ---------- Componentes ----------
  function tarjetaPieza(p) {
    const url = 'producto.html?slug=' + encodeURIComponent(p.slug);
    const imagen = urlSegura(p.imagen);
    return `
      <article class="pieza reveal">
        <a href="${url}" class="pieza-img" aria-label="Ver ${escapar(p.nombre)}">
          <img src="${escapar(imagen)}" alt="${escapar(p.nombre)}" loading="lazy" decoding="async">
        </a>
        <div class="pieza-info">
          <h3>${escapar(p.nombre)}</h3>
          <p class="pieza-detalle">${escapar(p.detalle)}</p>
          <span class="pieza-precio">${escapar(precioTexto(p))}</span>
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
      }, { threshold: 0.12 });
    }
    elementos.forEach(el => observer.observe(el));
  }

  // ---------- Layout común ----------
  async function iniciarLayout() {
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

    try {
      await cargarSitio();
      aplicarSitio();
    } catch (err) {
      document.querySelectorAll('[data-whatsapp]').forEach(el => { el.hidden = true; });
      const flotante = document.getElementById('waFlotante');
      if (flotante) flotante.hidden = true;
      console.error('[Lira Ferrer] No se pudo cargar el contenido:', err);
    }

    const anio = document.getElementById('anio');
    if (anio) anio.textContent = new Date().getFullYear();

    vigilarImagenes(document);
    activarReveal(document);
  }

  function jsonLd(datos) {
    const script = document.createElement('script');
    script.type = 'application/ld+json';
    script.textContent = JSON.stringify(datos);
    document.head.appendChild(script);
  }

  return {
    CONFIG, formatoCOP, escapar, param, urlSegura, whatsappUrl,
    api, cargarSitio, config, obtenerProductos, obtenerProducto, precioTexto,
    tarjetaPieza, vigilarImagenes, activarReveal, iniciarLayout, jsonLd
  };
})();
