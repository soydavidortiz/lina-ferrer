/* =========================================================
   LIRA FERRER — JS del panel
   ========================================================= */
(function () {
  'use strict';

  // Menú móvil
  var shell = document.querySelector('.admin-shell');
  var toggle = document.getElementById('adminMenuToggle');
  if (shell && toggle) {
    toggle.addEventListener('click', function () {
      shell.classList.toggle('menu-abierto');
    });
  }

  // Confirmaciones de borrado / acciones delicadas
  document.querySelectorAll('[data-confirm]').forEach(function (el) {
    el.addEventListener('click', function (e) {
      if (!window.confirm(el.getAttribute('data-confirm'))) e.preventDefault();
    });
  });

  // Slug automático en el formulario de producto
  var nombre = document.querySelector('[data-slug-source]');
  var slug = document.querySelector('[data-slug-target]');
  if (nombre && slug) {
    var tocado = slug.value.trim() !== '';
    slug.addEventListener('input', function () { tocado = true; });
    nombre.addEventListener('input', function () {
      if (tocado) return;
      var texto = nombre.value.toLowerCase();
      texto = texto.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
      texto = texto.replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
      slug.value = texto;
    });
  }

  // Vista previa de imágenes subidas
  document.querySelectorAll('input[type="file"][data-preview]').forEach(function (input) {
    var img = document.getElementById(input.getAttribute('data-preview'));
    if (!img) return;
    input.addEventListener('change', function () {
      var archivo = input.files && input.files[0];
      if (!archivo) return;
      var url = URL.createObjectURL(archivo);
      img.src = url;
      img.hidden = false;
      var caja = img.closest('.admin-preview');
      if (caja) caja.classList.remove('vacia');
    });
  });

  // Cerrar el menú al navegar en móvil
  document.querySelectorAll('.admin-nav a').forEach(function (a) {
    a.addEventListener('click', function () {
      if (shell && window.innerWidth <= 860) shell.classList.remove('menu-abierto');
    });
  });
})();
