# Changelog

## v2.0.0 — 2026-11-02

### Frontend

- Nueva portada editorial con secciones de prensa, filosofía, historia, trayectoria y preguntas frecuentes.
- Todo el contenido de marca se carga desde MySQL mediante `api/sitio.php` y `api/productos.php`.
- Sección de prensa con El Heraldo, ANSA, Revista Dinero y Medellín Style.
- Reconocimientos: Mipymes, Presidencia de la República y Proexport.
- Botón flotante de WhatsApp a la izquierda, número 300 333 3333.
- Manifiesto completo en español e inglés.
- Página de producto con galería, ficha técnica, garantías y selector de color.
- Checkout y página de gracias corregidos.
- Imágenes optimizadas a WebP.
- Ajustes de tienda (envío, máximo de unidades y métodos de pago) leídos desde la base de datos.
- Accesibilidad y SEO: Lighthouse 100/100/100 en las páginas principales.

### Backend

- Panel completo: dashboard, productos, pedidos, clientes, informes y usuarios.
- CRUD de contenido: textos del sitio, ficha, pilares, prensa, reconocimientos, FAQ y trayectoria.
- Subida de imágenes con conversión a WebP y límite de 1600 px.
- Roles `admin`, `gestor` y `consulta`.
- Cambio de estado de pedidos con devolución/descuento automático de stock.
- Detalle de pedidos en `pedido_items`.
- Auditoría de acciones y protección CSRF.
- Filtros y paginación en productos, pedidos y clientes.
- Informes por rango de fechas, ticket promedio, productos y ciudades.
- Instaladores SQL: esquema base, tablas del panel y contenido.
- Dump completo de base de datos sin datos personales en `database/lina_ferrer_v2.sql`.

### Correcciones

- Selva Viva completada: descripción, precio, edición y galería.
- Precios de la colección restaurados.
- Filtros PDO con parámetros repetidos.
- Consulta ambigua de `creado_en` en informes.
- Estilos de checkout y galería.
- Nombre del panel corregido a Lira Ferrer.
- `crear_admin.php` con rutas correctas.
