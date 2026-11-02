/* =========================================================
   LIRA FERRER — Componente: galería de producto
   ========================================================= */
(function () {
  'use strict';
  const L = window.Lira;

  function crear(contenedor, imagenes, alt) {
    const lista = [...new Set(imagenes.filter(Boolean))];
    if (!lista.length) { contenedor.innerHTML = '<div class="galeria-principal"></div>'; return; }

    contenedor.innerHTML = `
      <div class="galeria-principal">
        <img src="${L.escapar(lista[0])}" alt="${L.escapar(alt)}">
      </div>
      ${lista.length > 1 ? `
        <div class="galeria-miniaturas">
          ${lista.map((src, i) => `
            <button type="button" class="miniatura${i === 0 ? ' activa' : ''}" data-src="${L.escapar(src)}" aria-label="Ver imagen ${i + 1}">
              <img src="${L.escapar(src)}" alt="" loading="lazy">
            </button>`).join('')}
        </div>` : ''}`;

    const principal = contenedor.querySelector('.galeria-principal img');
    const marcarPng = src => principal.classList.toggle('es-png', /\.png$/i.test(src));
    marcarPng(lista[0]);

    principal.addEventListener('error', () => principal.classList.add('img-falta'));
    principal.addEventListener('load', () => principal.classList.remove('img-falta'));

    const quitarSiSobra = () => {
      const mini = contenedor.querySelector('.galeria-miniaturas');
      if (mini && mini.children.length < 2) mini.remove();
    };

    contenedor.querySelectorAll('.miniatura').forEach(btn => {
      // Si la miniatura no existe en la carpeta, se elimina sola
      btn.querySelector('img').addEventListener('error', () => { btn.remove(); quitarSiSobra(); });

      btn.addEventListener('click', () => {
        if (btn.classList.contains('activa')) return;
        contenedor.querySelectorAll('.miniatura').forEach(b => b.classList.remove('activa'));
        btn.classList.add('activa');
        principal.classList.add('cambiando');
        setTimeout(() => {
          principal.src = btn.dataset.src;
          marcarPng(btn.dataset.src);
          principal.classList.remove('cambiando');
        }, 200);
      });
    });
  }

  L.Galeria = { crear };
})();
