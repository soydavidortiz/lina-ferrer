/* =========================================================
   LIRA FERRER — Inicio
   Pinta la portada con el contenido que viene de la BD.
   ========================================================= */
(async function () {
  'use strict';

  const L = window.Lira;
  const $ = id => document.getElementById(id);

  const texto = (id, valor) => {
    const el = $(id);
    if (el && valor) el.textContent = valor;
  };

  const imagen = (id, src, alt) => {
    const el = $(id);
    if (!el) return;
    const url = L.urlSegura(src);
    if (!url) { el.hidden = true; return; }
    el.src = url;
    el.alt = alt || '';
    el.hidden = false;
  };

  const textoConIcono = (id, valor) => {
    const el = $(id);
    if (!el || !valor) return;
    const svg = el.querySelector('svg');
    el.textContent = valor;
    if (svg) el.appendChild(svg);
  };

  const parrafos = txt => String(txt || '')
    .split(/\n{2,}/)
    .map(p => p.trim())
    .filter(Boolean)
    .map(p => `<p>${L.escapar(p)}</p>`)
    .join('');

  const ICONOS = {
    esmeralda: '<path d="M7 4h10l4 5-9 11L3 9z"/><path d="M3 9h18M9 4l3 5 3-5M12 9v11"/>',
    orfebre: '<circle cx="12" cy="12" r="7"/><circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3"/>',
    cuero: '<path d="M4 9c0-2 1.5-3 3-3h1l1-2h6l1 2h1c1.5 0 3 1 3 3v2c0 1-.5 2-1.5 2.5L18 18h-2l-.5-3h-7L8 18H6l-.5-4.5C4.5 13 4 12 4 11z"/>',
    hoja: '<path d="M20 4C13 4 5 7 5 14c0 3 2 5 5 5 7 0 10-8 10-15z"/><path d="M5 19c2-5 5-8 10-11"/>',
    certificado: '<circle cx="12" cy="9" r="6"/><path d="M8.5 14 7 22l5-3 5 3-1.5-8"/><path d="M9 9l2 2 4-4"/>',
    colombia: '<path d="M12 3l9 7h-3v9H6v-9H3z"/><path d="M9 19v-5h6v5"/>',
    defecto: '<path d="M7 4h10l4 5-9 11L3 9z"/>'
  };

  try {
    await L.iniciarLayout();
  } catch (e) {
    // iniciarLayout ya muestra el estado sin conexión; seguimos para intentar de nuevo.
  }

  let sitio;
  try {
    sitio = await L.cargarSitio();
  } catch (e) {
    $('coleccionEstado').textContent = 'No pudimos cargar el contenido del sitio. Revisa tu conexión e inténtalo de nuevo.';
    return;
  }

  const c = sitio.config || {};

  // ---------- Hero ----------
  texto('heroEtiqueta', c.hero_etiqueta);
  texto('heroTitulo', c.hero_titulo);
  texto('heroPequeno', c.hero_titulo_pequeno);
  texto('heroDestacado', c.hero_destacado);
  texto('heroSub', c.hero_subtitulo);
  textoConIcono('heroCta', c.hero_cta);
  imagen('heroImagen', c.hero_imagen, c.hero_alt);

  // ---------- Sellos ----------
  const sellos = [c.sello_1, c.sello_2, c.sello_3, c.sello_4].filter(Boolean);
  const track = $('franjaTrack');
  if (track && sellos.length) {
    const grupo = `<div class="franja-grupo">${sellos.map(s => `<span>${L.escapar(s)}</span>`).join('')}</div>`;
    track.innerHTML = grupo + grupo.replace('class="franja-grupo"', 'class="franja-grupo" aria-hidden="true"');
  }

  // ---------- La pieza ----------
  texto('introEtiqueta', c.intro_etiqueta);
  texto('introTitulo', c.intro_titulo);
  texto('introTexto1', c.intro_texto_1);
  texto('introTexto2', c.intro_texto_2);
  texto('introFichaTitulo', c.intro_ficha_titulo);

  const ficha = $('introFicha');
  if (ficha && Array.isArray(sitio.ficha)) {
    ficha.innerHTML = sitio.ficha.map(f =>
      `<div><dt>${L.escapar(f.etiqueta)}</dt><dd>${L.escapar(f.valor)}</dd></div>`
    ).join('');
  }

  const cajaBolsos = $('introBolso');
  if (cajaBolsos) {
    [c.intro_bolso_1, c.intro_bolso_2, c.intro_bolso_3].filter(Boolean).forEach((src, i) => {
      const img = document.createElement('img');
      img.src = L.urlSegura(src);
      img.alt = i === 0 ? (c.marca_nombre || 'Lira Ferrer') : '';
      if (i > 0) img.setAttribute('aria-hidden', 'true');
      img.decoding = 'async';
      cajaBolsos.appendChild(img);
    });
  }

  imagen('galeria1', c.galeria_1, c.galeria_1_alt);
  imagen('galeria2', c.galeria_2, c.galeria_2_alt);

  // ---------- Manifiesto ----------
  texto('manifiestoEtiqueta', c.manifiesto_etiqueta);
  texto('manifiestoTitulo', c.manifiesto_titulo);
  texto('manifiestoTexto', c.manifiesto_texto);
  texto('manifiestoLeer', c.manifiesto_leer_completo);

  let idiomaManifiesto = 'es';
  const pintarManifiesto = () => {
    const cont = $('manifiestoCompleto');
    if (!cont) return;
    const txt = idiomaManifiesto === 'es' ? c.manifiesto_completo_es : c.manifiesto_completo_en;
    cont.innerHTML = parrafos(txt);
    document.querySelectorAll('.manifiesto-idiomas [data-idioma]').forEach(b => {
      b.classList.toggle('activo', b.dataset.idioma === idiomaManifiesto);
    });
  };
  document.querySelectorAll('.manifiesto-idiomas [data-idioma]').forEach(boton => {
    boton.addEventListener('click', () => {
      idiomaManifiesto = boton.dataset.idioma === 'en' ? 'en' : 'es';
      pintarManifiesto();
    });
  });
  pintarManifiesto();

  // ---------- Colección ----------
  texto('coleccionEtiqueta', c.coleccion_etiqueta);
  texto('coleccionTitulo', c.coleccion_titulo);
  texto('coleccionTexto', c.coleccion_texto);
  texto('ayudaTitulo', c.coleccion_ayuda_titulo);
  texto('ayudaTexto', c.coleccion_ayuda_texto);
  textoConIcono('ayudaBoton', c.coleccion_ayuda_boton);

  const grid = $('coleccionGrid');
  const estado = $('coleccionEstado');
  let productos = [];
  try {
    productos = await L.obtenerProductos();
  } catch (e) {
    productos = [];
  }

  if (productos.length) {
    grid.innerHTML = productos.map(L.tarjetaPieza).join('');
    if (estado) estado.hidden = true;
    L.vigilarImagenes(grid);
    L.activarReveal(grid);
  } else if (estado) {
    estado.textContent = 'No pudimos cargar la colección. Inténtalo de nuevo más tarde.';
  }

  const ayuda = $('coleccionAyuda');
  if (ayuda && L.whatsappUrl() !== '#') ayuda.hidden = false;

  // ---------- Filosofía ----------
  texto('filosofiaEtiqueta', c.filosofia_etiqueta);
  texto('filosofiaTitulo', c.filosofia_titulo);
  texto('filosofiaTexto', c.filosofia_texto);
  texto('filosofiaCita', c.filosofia_cita);
  texto('filosofiaSostenible', c.filosofia_sostenible);
  imagen('filosofiaImagen', c.filosofia_imagen, c.filosofia_imagen_alt);

  const pilaresGrid = $('pilaresGrid');
  if (pilaresGrid && Array.isArray(sitio.pilares)) {
    pilaresGrid.innerHTML = sitio.pilares.map(p => {
      const icono = ICONOS[p.icono] || ICONOS.defecto;
      return `
        <article class="pilar reveal">
          <div class="pilar-icono">
            <svg viewBox="0 0 24 24" aria-hidden="true">${icono}</svg>
          </div>
          <h3>${L.escapar(p.titulo)}</h3>
          <p>${L.escapar(p.texto)}</p>
        </article>`;
    }).join('');
    L.activarReveal(pilaresGrid);
  }

  // ---------- Historia ----------
  texto('historiaEtiqueta', c.historia_etiqueta);
  texto('historiaTitulo', c.historia_titulo);
  texto('historiaTexto1', c.historia_texto_1);
  texto('historiaTexto2', c.historia_texto_2);
  texto('historiaCita', c.historia_cita);
  imagen('historiaImagen', c.historia_imagen, c.historia_imagen_alt);

  // ---------- Trayectoria ----------
  texto('hitosTitulo', c.historia_hitos_titulo);
  const listaHitos = $('hitosLista');
  if (listaHitos && Array.isArray(sitio.hitos)) {
    listaHitos.innerHTML = sitio.hitos.map(h => `
      <li class="hito reveal">
        <span class="hito-anio">${L.escapar(h.anio)}</span>
        <div class="hito-texto">
          <h3>${L.escapar(h.titulo)}</h3>
          <p>${L.escapar(h.texto)}</p>
        </div>
      </li>`).join('');
    L.activarReveal(listaHitos);
  }

  // ---------- Prensa ----------
  texto('prensaEtiqueta', c.prensa_etiqueta);
  texto('prensaTitulo', c.prensa_titulo);
  texto('prensaTexto', c.prensa_texto);

  const fechaLarga = fecha => {
    if (!fecha) return '';
    const d = new Date(fecha + 'T00:00:00');
    if (Number.isNaN(d.getTime())) return '';
    return d.toLocaleDateString('es-CO', { year: 'numeric', month: 'long' });
  };

  const prensaGrid = $('prensaGrid');
  if (prensaGrid && Array.isArray(sitio.prensa)) {
    prensaGrid.innerHTML = sitio.prensa.map((p, i) => {
      const img = L.urlSegura(p.imagen);
      const url = L.urlSegura(p.url);
      const fecha = fechaLarga(p.fecha);
      const medio = `${L.escapar(p.medio)}${fecha ? ' · ' + L.escapar(fecha) : ''}`;
      const foto = img
        ? `<img src="${L.escapar(img)}" alt="${L.escapar(p.titulo)}" loading="lazy" decoding="async">`
        : `<span class="prensa-monograma" aria-hidden="true">LF</span>`;
      const etiquetaFoto = url
        ? `<a class="prensa-img" href="${L.escapar(url)}" target="_blank" rel="noopener" aria-label="${L.escapar(p.titulo)}">${foto}</a>`
        : `<div class="prensa-img">${foto}</div>`;
      const enlace = url
        ? `<a class="prensa-leer" href="${L.escapar(url)}" target="_blank" rel="noopener">${L.escapar(c.prensa_leer || 'Leer la nota')} →</a>`
        : '';
      return `
        <article class="prensa-card reveal" style="--i:${i}">
          ${etiquetaFoto}
          <div class="prensa-info">
            <span class="prensa-medio">${medio}</span>
            <h3>${L.escapar(p.titulo)}</h3>
            <p>${L.escapar(p.resumen)}</p>
            ${enlace}
          </div>
        </article>`;
    }).join('');
    L.activarReveal(prensaGrid);
  }

  const enlaceCaja = $('prensaEnlaceCaja');
  const enlacePrensa = $('prensaEnlace');
  const urlPrensa = L.urlSegura(c.prensa_enlace_url);
  if (enlaceCaja && enlacePrensa && urlPrensa && c.prensa_enlace_texto) {
    enlacePrensa.href = urlPrensa;
    enlacePrensa.textContent = c.prensa_enlace_texto;
    enlaceCaja.hidden = false;
  }

  // ---------- Reconocimientos ----------
  texto('reconocimientosEtiqueta', c.reconocimientos_etiqueta);
  texto('reconocimientosTitulo', c.reconocimientos_titulo);

  const reconGrid = $('reconocimientosGrid');
  if (reconGrid && Array.isArray(sitio.reconocimientos)) {
    reconGrid.innerHTML = sitio.reconocimientos.map(r => {
      const img = L.urlSegura(r.imagen);
      return `
        <article class="reconocimiento reveal">
          ${img
            ? `<img src="${L.escapar(img)}" alt="${L.escapar(r.titulo)}" loading="lazy" decoding="async">`
            : `<span class="reconocimiento-monograma" aria-hidden="true">LF</span>`}
          <div>
            <h3>${L.escapar(r.titulo)}</h3>
            <p>${L.escapar(r.texto)}</p>
          </div>
        </article>`;
    }).join('');
    L.activarReveal(reconGrid);
  }

  // ---------- FAQ ----------
  texto('faqEtiqueta', c.faq_etiqueta);
  texto('faqTitulo', c.faq_titulo);
  texto('faqTexto', c.faq_texto);
  textoConIcono('faqBoton', c.faq_boton);

  const faqLista = $('faqLista');
  if (faqLista && Array.isArray(sitio.faq)) {
    faqLista.innerHTML = sitio.faq.map((f, i) => `
      <details class="faq-item reveal"${i === 0 ? ' open' : ''}>
        <summary>
          <span>${L.escapar(f.pregunta)}</span>
          <span class="faq-mas" aria-hidden="true">+</span>
        </summary>
        <div class="faq-respuesta">${L.escapar(f.respuesta)}</div>
      </details>`).join('');
    L.activarReveal(faqLista);
  }

  // ---------- Cierre ----------
  texto('ctaEtiqueta', c.cta_etiqueta);
  texto('ctaTitulo', c.cta_titulo);
  texto('ctaTexto', c.cta_texto);
  textoConIcono('ctaBoton', c.cta_boton);

  // ---------- Datos estructurados ----------
  L.jsonLd({
    '@context': 'https://schema.org',
    '@type': 'Organization',
    name: c.marca_nombre || 'Lira Ferrer',
    url: location.origin + location.pathname,
    email: c.email || undefined,
    telephone: c.telefono || undefined,
    address: c.direccion ? {
      '@type': 'PostalAddress',
      streetAddress: c.direccion,
      addressLocality: c.marca_ciudad || undefined,
      addressCountry: 'CO'
    } : undefined,
    sameAs: c.instagram ? [c.instagram] : undefined
  });

  if (productos.length) {
    L.jsonLd({
      '@context': 'https://schema.org',
      '@type': 'ItemList',
      name: c.coleccion_titulo || 'Colección Lira Ferrer',
      itemListElement: productos.map((p, i) => ({
        '@type': 'ListItem',
        position: i + 1,
        url: new URL('producto.html?slug=' + encodeURIComponent(p.slug), location.href).href,
        name: p.nombre,
        image: L.urlSegura(p.imagen) ? new URL(L.urlSegura(p.imagen), location.href).href : undefined
      }))
    });
  }
})();
