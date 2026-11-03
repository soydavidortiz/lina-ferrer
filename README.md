# Lira Ferrer — Web de marca · v2.0

Versión 2 del sitio de Lira Ferrer: portada editorial, colección, prensa, filosofía de marca, historia, trayectoria, preguntas frecuentes y panel de administración completo.

> **Repositorio:** https://github.com/soydavidortiz/lina-ferrer
> **Versión:** `v2.0.0` · rama `v2`

Instalación rápida:

```bash
mysql -u root --default-character-set=utf8mb4 < database/lina_ferrer_v2.sql
php backend/_instalar/crear_admin.php
```

La base de datos pública incluye estructura, contenido del sitio y colección, pero **no incluye clientes, pedidos ni usuarios reales**.

## Qué se hizo

- **Todo el contenido de marca sale de MySQL.** El frontend ya no tiene productos, textos, prensa ni FAQ escritos a mano: los pide a `frontend/api/sitio.php` y `frontend/api/productos.php`.
- **Nueva sección de prensa** con El Heraldo, ANSA (Italia), Revista Dinero y Medellín Style, más una franja de reconocimientos (Mipymes, Presidencia de la República y Proexport).
- **Botón flotante de WhatsApp a la izquierda**, siempre visible, al número **300 333 3333** (`573003333333`).
- **Sección de filosofía de marca** que traduce el documento complejo a un lenguaje web claro: 6 pilares, compromiso ambiental, cita y manifiesto completo en español e inglés (desplegable).
- **Sección “Quién es Lira Ferrer”** con retrato, historia y línea de tiempo (2009–hoy).
- **Sección de preguntas frecuentes** con la historia de la marca, el nombre, el logo de la lira, los materiales y los envíos.
- **Producto Selva Viva** completado en la base de datos: descripción, precio, edición y galería.
- **Imágenes optimizadas a WebP** (el hero pasó de ~3 MB a ~168 KB; los bolsos, de ~2,5 MB a ~150–244 KB).
- **Accesibilidad y SEO**: Lighthouse 100/100/100 en portada, producto, checkout y gracias (accesibilidad, buenas prácticas y SEO).
- Los archivos antiguos con datos de respaldo (`index.js`, `piezas-locales.js`, `efectos.js`) se movieron a `frontend/_respaldo/js/`.

## Panel de administración

El panel está en:

```
http://localhost/lina-ferrer/backend/
```

Incluye:

- **Panel**: ventas del mes, pedidos pendientes, clientes, stock bajo, últimos pedidos y actividad.
- **Productos**: crear, editar, activar/desactivar, precios, stock, edición limitada, galería, especificaciones y subida de imágenes.
- **Pedidos**: filtros por estado, cliente y fechas; detalle completo; cambio de estado con devolución/descuento automático de stock al cancelar o reactivar.
- **Clientes**: búsqueda, datos de contacto, historial y total comprado. Permite corregir los datos del cliente.
- **Informes**: ventas por rango de fechas, ticket promedio, productos más vendidos, estados y ciudades.
- **Contenido del sitio**: edición de todos los textos de la portada, manifiesto, filosofía, historia, prensa, FAQ y página de producto.
- **Tienda y checkout**: costo de envío, máximo de unidades por pedido y métodos de pago disponibles.
- **Usuarios**: creación de administradores, gestores y perfiles de consulta.

### Roles

| Rol | Permisos |
|---|---|
| `admin` | Todo, incluido usuarios |
| `gestor` | Contenido, productos, pedidos y clientes |
| `consulta` | Solo lectura |

El rol `consulta` no puede guardar, eliminar ni cambiar estados.

### Crear un administrador

Si no existe ningún usuario, ejecuta por consola:

```bash
php backend/_instalar/crear_admin.php
```

O importa `backend/_instalar/tablas_admin.sql` y crea el usuario desde phpMyAdmin con un hash de contraseña.

### Imágenes subidas

Las imágenes que se suben desde el panel se guardan en:

```
frontend/assets/img/uploads/
```

Se convierten a WebP y se reducen a un máximo de 1600 px de ancho. Esa carpeta bloquea archivos ejecutables por seguridad.

## Instalación

### Opción rápida (recomendada)

1. Importa el dump completo:
   ```bash
   mysql -u root --default-character-set=utf8mb4 < database/lina_ferrer_v2.sql
   ```
   El archivo crea la base `lina_ferrer`, las tablas, el contenido del sitio y la colección inicial.
2. Crea el primer administrador:
   ```bash
   php backend/_instalar/crear_admin.php
   ```

### Opción por partes

1. Si la base de datos está vacía, importa en este orden:
   ```bash
   mysql -u root --default-character-set=utf8mb4 lina_ferrer < backend/_instalar/esquema_base.sql
   mysql -u root --default-character-set=utf8mb4 lina_ferrer < backend/_instalar/tablas_admin.sql
   mysql -u root --default-character-set=utf8mb4 lina_ferrer < backend/_instalar/contenido_web.sql
   ```
   - `esquema_base.sql`: productos, clientes, pedidos y detalle de pedidos.
   - `tablas_admin.sql`: usuarios, intentos de login y auditoría.
   - `contenido_web.sql`: textos, prensa, FAQ, pilares, trayectoria y datos de la colección.

   Desde phpMyAdmin: seleccionar `lina_ferrer` → Importar, uno por uno.
   **No vuelvas a ejecutar `contenido_web.sql` después de editar desde el panel**: reemplaza los textos por los valores iniciales.
2. Abre el sitio:
   ```
   http://localhost/lina-ferrer/frontend/
   ```
3. Si cambias textos o imágenes en la base de datos, recarga el navegador. La API no usa caché (`Cache-Control: no-store`).

## Dónde está cada cosa

| Contenido | Tabla |
|---|---|
| Textos, hero, contacto, WhatsApp, sellos, manifiesto, historia, CTA | `sitio_config` |
| Ficha técnica del inicio | `ficha_pieza` |
| Pilares de la filosofía | `pilares` |
| Prensa | `prensa` |
| Reconocimientos institucionales | `reconocimientos` |
| Preguntas frecuentes | `faq` |
| Línea de tiempo | `hitos` |
| Colección | `productos` (ya existente) |

### Cambiar el WhatsApp

```sql
UPDATE sitio_config SET valor = '573003333333' WHERE clave = 'whatsapp';
```

El enlace se actualiza en el botón flotante, el pie de página, la página de producto y el checkout.

### Añadir una nota de prensa

```sql
INSERT INTO prensa (medio, titulo, resumen, fecha, imagen, url, orden, activo)
VALUES ('Medio', 'Título', 'Resumen breve', '2026-01-01',
        'assets/img/prensa/mi-imagen.webp', 'https://...', 5, 1);
```

### Añadir una pregunta frecuente

```sql
INSERT INTO faq (pregunta, respuesta, orden, activo)
VALUES ('¿Pregunta?', 'Respuesta.', 8, 1);
```

### Cambiar el número de edición o el precio de una pieza

```sql
UPDATE productos
SET precio = 2850000, edicion_total = 50
WHERE slug = 'selva-viva';
```

## Estructura del frontend

```
frontend/
├─ index.html                 Portada
├─ producto.html              Detalle de pieza
├─ comprar.html               Checkout
├─ gracias.html               Confirmación
├─ api/
│  ├─ sitio.php               Textos, prensa, FAQ, pilares, hitos
│  ├─ productos.php           Colección
│  └─ pedidos.php             Creación de pedidos
├─ models/
│  ├─ Sitio.php               Nuevo modelo de contenido
│  ├─ Producto.php
│  ├─ Cliente.php
│  └─ Pedido.php
├─ assets/
│  ├─ css/styles.css          Base y diseño general
│  ├─ css/secciones.css       Prensa, filosofía, historia, FAQ
│  ├─ css/paginas.css         Producto, checkout, gracias
│  ├─ js/core/app.js          Configuración, API, WhatsApp, layout
│  ├─ js/pages/inicio.js      Render de la portada
│  ├─ js/pages/producto.js    Render del producto
│  └─ img/                    Imágenes optimizadas en WebP
```

## Notas

- El sitio necesita JavaScript para pintar el contenido que viene de la base de datos.
- Las imágenes de prensa y marca se extrajeron de los archivos entregados por la clienta y se guardaron en `assets/img/prensa`, `assets/img/marca` y `assets/img/reconocimientos`.
- El texto completo del manifiesto está en `sitio_config` (`manifiesto_completo_es` y `manifiesto_completo_en`) y se muestra en el desplegable de la sección Manifiesto.
- La información de la clienta sobre el nombre, la lira y el origen de la idea quedó en `faq`.
