# Base de datos · Lira Ferrer v2.0

`lina_ferrer_v2.sql` contiene:

- Estructura de `productos`, `clientes`, `pedidos` y `pedido_items`.
- Estructura del panel: `admin_usuarios`, `admin_intentos_login`, `admin_actividad`.
- Estructura y contenido de `sitio_config`, `ficha_pieza`, `pilares`, `prensa`, `reconocimientos`, `faq` e `hitos`.
- Las 3 piezas base de la colección: Herencia Borgoña, Perla Eterna y Selva Viva.
- Ajustes de tienda, WhatsApp, prensa, FAQ, filosofía, historia y trayectoria.

## Privacidad

**No se incluyen datos reales de clientes, pedidos, usuarios administradores, intentos de login ni auditoría.** Esos datos quedan fuera del repositorio público para proteger información personal.

Para un respaldo privado con datos de producción, usa un repositorio privado o un almacenamiento seguro.

## Importar

```bash
mysql -u root --default-character-set=utf8mb4 < database/lina_ferrer_v2.sql
```

O desde phpMyAdmin: **Importar** → seleccionar `lina_ferrer_v2.sql`.

## Crear el primer administrador

```bash
php backend/_instalar/crear_admin.php
```

El panel está en:

```
http://localhost/lina-ferrer/backend/
```

## Nota

El dump incluye contenido inicial. Si ya editaste textos, prensa o FAQ desde el panel, no vuelvas a importarlo: reemplazaría esos cambios por los valores de la versión 2.
