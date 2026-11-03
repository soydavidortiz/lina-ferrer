-- =========================================================
--  LIRA FERRER — Base de datos v2.0
--  Estructura completa + contenido del sitio + colección.
--  NO incluye clientes, pedidos, usuarios ni actividad real.
--  Importar: mysql -u root --default-character-set=utf8mb4 < database/lina_ferrer_v2.sql
-- =========================================================

CREATE DATABASE IF NOT EXISTS `lina_ferrer` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `lina_ferrer`;
SET FOREIGN_KEY_CHECKS = 0;

-- >>> esquema_base.sql >>>
-- LIRA FERRER - Esquema base de la tienda (sin datos)
-- Importar antes de contenido_web.sql

-- MySQL dump 10.13  Distrib 8.3.0, for Win64 (x86_64)
--
-- Host: localhost    Database: lina_ferrer
-- ------------------------------------------------------
-- Server version	8.3.0

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `productos`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `productos` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `color` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `detalle` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `descripcion` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `precio` int unsigned NOT NULL,
  `stock` int unsigned NOT NULL DEFAULT '0',
  `edicion_total` int unsigned NOT NULL DEFAULT '0',
  `imagen` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `galeria` text COLLATE utf8mb4_unicode_ci,
  `especificaciones` text COLLATE utf8mb4_unicode_ci,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `orden` int NOT NULL DEFAULT '0',
  `creado_en` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_productos_slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `clientes`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `clientes` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefono` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `documento` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `departamento` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ciudad` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `direccion` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `creado_en` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_clientes_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pedidos`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `pedidos` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cliente_id` int unsigned NOT NULL,
  `producto_id` int unsigned NOT NULL,
  `cantidad` int unsigned NOT NULL,
  `precio_unitario` int unsigned NOT NULL,
  `envio` int unsigned NOT NULL DEFAULT '0',
  `total` int unsigned NOT NULL,
  `metodo_pago` enum('transferencia','tarjeta','asesor') COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado` enum('pendiente','confirmado','enviado','entregado','cancelado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendiente',
  `departamento` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ciudad` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `direccion` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notas` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_pedidos_codigo` (`codigo`),
  KEY `idx_pedidos_estado` (`estado`),
  KEY `fk_pedidos_cliente` (`cliente_id`),
  KEY `fk_pedidos_producto` (`producto_id`),
  KEY `idx_pedidos_fecha` (`creado_en`),
  KEY `idx_pedidos_metodo` (`metodo_pago`),
  CONSTRAINT `fk_pedidos_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`),
  CONSTRAINT `fk_pedidos_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pedido_items`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `pedido_items` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `pedido_id` int unsigned NOT NULL,
  `producto_id` int unsigned NOT NULL,
  `nombre_producto` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `precio_unitario` decimal(12,2) NOT NULL,
  `cantidad` smallint unsigned NOT NULL DEFAULT '1',
  `subtotal` decimal(12,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_items_pedido` (`pedido_id`),
  KEY `fk_items_producto` (`producto_id`),
  CONSTRAINT `fk_items_pedido` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_items_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-02 13:36:17

-- >>> tablas_admin.sql >>>
-- =========================================================
--  LIRA FERRER — Tablas del panel de administración
--  Ejecutar solo si la base de datos aún no las tiene.
-- =========================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `admin_usuarios` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rol` enum('admin','gestor','consulta') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'gestor',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `ultimo_login` timestamp NULL DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_admin_usuarios_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `admin_intentos_login` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `exitoso` tinyint(1) NOT NULL DEFAULT '0',
  `creado_en` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_intentos_email` (`email`,`creado_en`),
  KEY `idx_intentos_ip` (`ip`,`creado_en`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `admin_actividad` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `usuario_id` int unsigned DEFAULT NULL,
  `accion` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entidad` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `entidad_id` int unsigned DEFAULT NULL,
  `detalle` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_actividad_fecha` (`creado_en`),
  KEY `fk_actividad_usuario` (`usuario_id`),
  CONSTRAINT `fk_actividad_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `admin_usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- >>> contenido_web.sql >>>
-- =========================================================
--  LIRA FERRER — Contenido del sitio web
--  Generado desde la base de datos `lina_ferrer`.
--  Ejecutar: mysql -u root --default-character-set=utf8mb4 lina_ferrer < contenido_web.sql
--  Solo para la instalación inicial: si ya editaste contenido desde el panel, no lo reejecutes.
-- =========================================================

SET NAMES utf8mb4;

-- ---------------------------------------------------------
-- sitio_config
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sitio_config` (
  `clave` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `valor` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `grupo` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'general',
  `etiqueta` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `tipo` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'texto',
  `actualizado_en` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`clave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

REPLACE INTO `sitio_config` (`clave`, `valor`, `grupo`, `etiqueta`, `tipo`, `actualizado_en`) VALUES
('coleccion_ayuda_boton', 'Hablar con una asesora', 'coleccion', 'Ayuda: botón', 'texto', '2026-11-02 11:59:27'),
('coleccion_ayuda_texto', 'Escríbenos y te ayudamos a encontrar la pieza que va contigo.', 'coleccion', 'Ayuda: texto', 'texto', '2026-11-02 11:59:27'),
('coleccion_ayuda_titulo', '¿No sabes cuál elegir?', 'coleccion', 'Ayuda: título', 'texto', '2026-11-02 11:59:27'),
('coleccion_etiqueta', 'La colección', 'coleccion', 'Colección: etiqueta', 'texto', '2026-11-02 11:59:27'),
('coleccion_texto', 'Cada cartera es única: piel de vacuno de primera calidad, trenzado a mano y la lira en filigrana de oro coronada con esmeralda colombiana certificada.', 'coleccion', 'Colección: texto', 'textarea', '2026-11-02 11:59:27'),
('coleccion_titulo', 'Tres piezas, una herencia', 'coleccion', 'Colección: título', 'texto', '2026-11-02 11:59:27'),
('cta_boton', 'Ver la colección', 'cierre', 'CTA: botón', 'texto', '2026-11-02 11:59:27'),
('cta_etiqueta', 'Lira Ferrer', 'cierre', 'CTA: etiqueta', 'texto', '2026-11-02 11:48:27'),
('cta_texto', 'Cada pieza es de edición limitada y está hecha a mano en Colombia. Escríbenos y te ayudamos a elegir la tuya.', 'cierre', 'CTA: texto', 'textarea', '2026-11-02 11:59:27'),
('cta_titulo', 'Descubre la joya dentro de ti', 'cierre', 'CTA: título', 'texto', '2026-11-02 11:59:27'),
('cuidado_texto', 'Guárdala en su bolsa protectora, lejos de la humedad y del sol directo. Límpiala con un paño suave y seco, y evita el contacto prolongado con perfumes o líquidos sobre la piel y la filigrana.', 'producto', 'Cuidado de la pieza', 'textarea', '2026-11-02 11:59:27'),
('direccion', 'Carrera 1 No. 13-38, Cartagena D.T. y C.', 'contacto', 'Dirección', 'texto', '2026-11-02 11:59:27'),
('email', 'hola@liraferrer.com', 'contacto', 'Correo electrónico', 'email', '2026-11-02 11:59:27'),
('envio_costo', '0', 'tienda', 'Costo de envío (0 = gratis)', 'numero', '2026-11-02 13:55:51'),
('faq_boton', 'Pregúntanos por WhatsApp', 'faq', 'FAQ: botón', 'texto', '2026-11-02 11:59:27'),
('faq_etiqueta', 'Preguntas frecuentes', 'faq', 'FAQ: etiqueta', 'texto', '2026-11-02 11:48:27'),
('faq_texto', 'La historia detrás de la marca, sus materiales y cómo adquirir tu pieza.', 'faq', 'FAQ: texto', 'textarea', '2026-11-02 11:59:27'),
('faq_titulo', 'Todo lo que quieres saber', 'faq', 'FAQ: título', 'texto', '2026-11-02 11:59:27'),
('filosofia_cita', 'La belleza de lo irrepetible, hecha joya.', 'filosofia', 'Filosofía: cita', 'texto', '2026-11-02 11:59:27'),
('filosofia_etiqueta', 'Filosofía de marca', 'filosofia', 'Filosofía: etiqueta', 'texto', '2026-11-02 13:45:32'),
('filosofia_imagen', 'assets/img/marca/filosofia.webp', 'filosofia', 'Filosofía: imagen', 'imagen', '2026-11-02 11:59:27'),
('filosofia_imagen_alt', 'Filosofía de marca Lira Ferrer', 'filosofia', 'Filosofía: alt', 'texto', '2026-11-02 11:59:27'),
('filosofia_sostenible', 'Compra bueno, compra bien: fibras naturales y tinturaciones vegetales en el 80 % de nuestras piezas, trabajando de la mano con artesanos colombianos.', 'filosofia', 'Filosofía: compromiso ambiental', 'textarea', '2026-11-02 12:19:12'),
('filosofia_texto', 'Lira Ferrer nace en Cartagena de Indias, entre la belleza del Caribe y la fuerza de nuestra tierra. Es una marca de carteras de lujo artesanal: una joya utilitaria y un activo heredable en el tiempo.', 'filosofia', 'Filosofía: texto', 'textarea', '2026-11-02 11:59:27'),
('filosofia_titulo', 'Más que un accesorio, es un legado', 'filosofia', 'Filosofía: título', 'texto', '2026-11-02 11:59:27'),
('footer_texto', 'Alta marroquinería colombiana. Piezas hechas a mano en Cartagena de Indias, con esmeraldas certificadas y oficios que se heredan.', 'general', 'Texto del pie de página', 'textarea', '2026-11-02 11:59:27'),
('galeria_1', 'assets/img/galeria/galeria-1.webp', 'inicio', 'Galería: imagen 1', 'imagen', '2026-11-02 11:59:27'),
('galeria_1_alt', 'Modelo con vestido blanco y bolso Lira Ferrer', 'inicio', 'Galería: alt 1', 'texto', '2026-11-02 11:59:27'),
('galeria_2', 'assets/img/galeria/galeria-2.webp', 'inicio', 'Galería: imagen 2', 'imagen', '2026-11-02 11:59:27'),
('galeria_2_alt', 'Modelo sosteniendo el bolso Lira Ferrer', 'inicio', 'Galería: alt 2', 'texto', '2026-11-02 11:59:27'),
('garantia_1', 'Envío asegurado gratis a toda Colombia', 'producto', 'Garantía 1', 'texto', '2026-11-02 11:59:27'),
('garantia_2', 'Certificado de autenticidad de la esmeralda', 'producto', 'Garantía 2', 'texto', '2026-11-02 11:59:27'),
('garantia_3', 'Estuche y bolsa protectora Lira Ferrer', 'producto', 'Garantía 3', 'texto', '2026-11-02 11:59:27'),
('hero_alt', 'Modelo con el bolso Perla Eterna de Lira Ferrer', 'inicio', 'Hero: texto alternativo', 'texto', '2026-11-02 11:48:27'),
('hero_cta', 'Descubre la joya dentro de ti', 'inicio', 'Hero: botón', 'texto', '2026-11-02 11:59:27'),
('hero_destacado', 'Lira Ferrer', 'inicio', 'Hero: palabra destacada', 'texto', '2026-11-02 11:48:27'),
('hero_etiqueta', 'Edición limitada · Colombia', 'inicio', 'Hero: etiqueta', 'texto', '2026-11-02 11:59:27'),
('hero_imagen', 'assets/img/hero/hero-modelo.webp', 'inicio', 'Hero: imagen', 'imagen', '2026-11-02 11:48:27'),
('hero_subtitulo', 'Alta marroquinería colombiana', 'inicio', 'Hero: subtítulo', 'texto', '2026-11-02 11:59:27'),
('hero_titulo', 'La joya', 'inicio', 'Hero: título', 'texto', '2026-11-02 11:59:27'),
('hero_titulo_pequeno', 'que se lleva', 'inicio', 'Hero: línea pequeña', 'texto', '2026-11-02 11:59:27'),
('historia_cita', 'Mi padre, músico, estando mi madre embarazada dijo: si es niña, le pondremos Lira. Así se dio. Esa soy Lira Ferrer.', 'historia', 'Historia: cita', 'textarea', '2026-11-02 11:59:27'),
('historia_etiqueta', 'Quién es Lira Ferrer', 'historia', 'Historia: etiqueta', 'texto', '2026-11-02 11:59:27'),
('historia_hitos_titulo', 'Una trayectoria con alma colombiana', 'historia', 'Historia: título de trayectoria', 'texto', '2026-11-02 11:59:27'),
('historia_imagen', 'assets/img/marca/lira-retrato.webp', 'historia', 'Historia: retrato', 'imagen', '2026-11-02 11:48:27'),
('historia_imagen_alt', 'Retrato de Lira Ferrer', 'historia', 'Historia: alt', 'texto', '2026-11-02 11:48:27'),
('historia_texto_1', 'Soy una mujer cartagenera, trabajadora, con mucha fe en nuestro país y en los recursivos y emprendedores que somos. Apasionada por lo que emprendo, persistente y fiel a mis principios.', 'historia', 'Historia: párrafo 1', 'textarea', '2026-11-02 11:59:27'),
('historia_texto_2', 'Provengo de un padre músico, de donde viene mi nombre. Me gusta el arte; más que diseñar, me gusta crear. Le pongo todas las ganas para que cada día surja una nueva idea y cada colección sea un interrogante para el público.', 'historia', 'Historia: párrafo 2', 'textarea', '2026-11-02 11:59:27'),
('historia_titulo', 'Una historia real, hecha de tierra, manos y sueños', 'historia', 'Historia: título', 'texto', '2026-11-02 11:59:27'),
('instagram', '', 'contacto', 'Instagram (URL completa)', 'url', '2026-11-02 11:48:27'),
('intro_bolso_1', 'assets/img/bolsos/bolso-blanco.webp', 'inicio', 'Intro: bolso 1', 'imagen', '2026-11-02 11:48:27'),
('intro_bolso_2', 'assets/img/bolsos/bolso-verde.webp', 'inicio', 'Intro: bolso 2', 'imagen', '2026-11-02 11:48:27'),
('intro_bolso_3', 'assets/img/bolsos/bolso-vinotinto.webp', 'inicio', 'Intro: bolso 3', 'imagen', '2026-11-02 11:48:27'),
('intro_etiqueta', 'La pieza', 'inicio', 'Intro: etiqueta', 'texto', '2026-11-02 11:48:27'),
('intro_ficha_titulo', 'Ficha de la pieza', 'inicio', 'Intro: título de la ficha', 'texto', '2026-11-02 11:59:27'),
('intro_texto_1', 'Cada Lira Ferrer nace de piel de vacuno de primera calidad, teñida con pigmentos vegetales y trabajada a mano en Colombia. En el centro, una lira en filigrana de oro custodia una esmeralda certificada: la firma de la casa.', 'inicio', 'Intro: párrafo 1', 'textarea', '2026-11-02 11:59:27'),
('intro_texto_2', 'Piezas pensadas para durar generaciones, para quien entiende que el verdadero lujo es el que tiene historia.', 'inicio', 'Intro: párrafo 2', 'textarea', '2026-11-02 11:59:27'),
('intro_titulo', 'Una joya que se lleva en la mano', 'inicio', 'Intro: título', 'texto', '2026-11-02 11:59:27'),
('manifiesto_completo_en', 'The Lira Ferrer universe is not an object or an accessory. It is a place, a suspended moment where luxury becomes a living experience. Each creation is a gateway into a world with its own codes: the subtlety of the emerald, the purity of pearl white and the eternal glow of gold.\n\nLira Ferrer lives at the crossroads of tradition and modernity, where architecture, jewelry and art converse. It is a space of golden light and white columns; a stage that belongs to the world of ultra-luxury, exclusivity and timelessness.\n\nIt is not just about clients, but about women who express strength through delicacy, collectors of the unique, guardians of objects that tell a story. The brand does not sell them an accessory: it gives them belonging to a universe with its own rules and rituals.\n\nLira Ferrer is not a brand, it is a universe. Upon entering it, the client leaves behind the ordinary and steps into a space where luxury becomes a philosophy of life.', 'manifiesto', 'Manifiesto completo (EN)', 'textarea', '2026-11-02 11:48:27'),
('manifiesto_completo_es', 'El universo Lira Ferrer no es un objeto ni un accesorio. Es un lugar, un tiempo suspendido donde el lujo se convierte en una experiencia viva. Cada creación es una puerta de entrada a un mundo con códigos propios: la sutileza de la esmeralda, la pureza del blanco perla y el resplandor eterno del oro.\n\nLira Ferrer habita en el cruce entre la tradición y la modernidad, donde la arquitectura, la joyería y el arte dialogan. Es un espacio de luz dorada y columnas blancas; un escenario que pertenece al mundo del ultralujo, la exclusividad y la atemporalidad.\n\nNo se trata solo de clientes, sino de mujeres que expresan su fuerza en lo delicado, coleccionistas de lo único, guardianas de objetos que cuentan una historia. La marca no les vende un accesorio: les entrega la pertenencia a un universo con sus propias reglas y rituales.\n\nLira Ferrer no es una marca, es un universo. Al entrar en él, el cliente deja atrás lo cotidiano y se adentra en un espacio donde el lujo se convierte en filosofía de vida.', 'manifiesto', 'Manifiesto completo (ES)', 'textarea', '2026-11-02 11:59:27'),
('manifiesto_etiqueta', 'Manifiesto', 'manifiesto', 'Manifiesto: etiqueta', 'texto', '2026-11-02 11:48:27'),
('manifiesto_leer_completo', 'Leer el manifiesto completo', 'manifiesto', 'Manifiesto: texto del enlace', 'texto', '2026-11-02 11:51:34'),
('manifiesto_texto', 'Detrás de cada bolso hay talabarteros, orfebres y lapidarios que llevan generaciones perfeccionando su arte. Lira Ferrer los reúne en una sola pieza.', 'manifiesto', 'Manifiesto: texto corto', 'textarea', '2026-11-02 11:59:27'),
('manifiesto_titulo', 'No fabricamos accesorios. Heredamos oficios.', 'manifiesto', 'Manifiesto: título', 'texto', '2026-11-02 11:59:27'),
('marca_ciudad', 'Cartagena de Indias, Colombia', 'general', 'Ciudad', 'texto', '2026-11-02 11:48:27'),
('marca_nombre', 'Lira Ferrer', 'general', 'Nombre de la marca', 'texto', '2026-11-02 11:48:27'),
('marca_tagline', 'Alta marroquinería colombiana', 'general', 'Lema', 'texto', '2026-11-02 11:59:27'),
('max_unidades', '3', 'tienda', 'Máximo de unidades por pedido', 'numero', '2026-11-02 13:55:51'),
('metodos_pago', 'transferencia,tarjeta,asesor', 'tienda', 'Métodos de pago (separados por coma)', 'texto', '2026-11-02 14:07:01'),
('precio_consultar', 'Precio a convenir', 'producto', 'Texto si el precio es 0', 'texto', '2026-11-02 11:48:27'),
('prensa_enlace_texto', 'Leer el artículo en Medellín Style', 'prensa', 'Prensa: enlace', 'texto', '2026-11-02 11:59:27'),
('prensa_enlace_url', 'https://www.medellinstyle.com/carteras-caras-mundo', 'prensa', 'Prensa: URL del enlace', 'url', '2026-11-02 11:48:27'),
('prensa_etiqueta', 'Prensa', 'prensa', 'Prensa: etiqueta', 'texto', '2026-11-02 11:48:27'),
('prensa_leer', 'Leer la nota', 'prensa', 'Texto del enlace de cada nota', 'texto', '2026-11-02 11:51:34'),
('prensa_texto', 'Nuestra historia y nuestras piezas han sido reconocidas por medios nacionales e internacionales.', 'prensa', 'Prensa: texto', 'textarea', '2026-11-02 11:48:27'),
('prensa_titulo', 'Lira Ferrer en los medios', 'prensa', 'Prensa: título', 'texto', '2026-11-02 11:59:27'),
('reconocimientos_etiqueta', 'Reconocimientos', 'prensa', 'Reconocimientos: etiqueta', 'texto', '2026-11-02 11:48:27'),
('reconocimientos_titulo', 'Una marca que representa a Colombia', 'prensa', 'Reconocimientos: título', 'texto', '2026-11-02 11:59:27'),
('sello_1', 'Hecho a mano en Colombia', 'general', 'Sello 1', 'texto', '2026-11-02 11:51:34'),
('sello_2', 'Piel genuina certificada', 'general', 'Sello 2', 'texto', '2026-11-02 11:51:34'),
('sello_3', 'Oro y esmeralda auténticos', 'general', 'Sello 3', 'texto', '2026-11-02 11:59:27'),
('sello_4', 'Edición limitada', 'general', 'Sello 4', 'texto', '2026-11-02 11:59:27'),
('seo_descripcion', 'Carteras Lira Ferrer: alta marroquinería colombiana con esmeralda certificada, lira en filigrana de oro y trabajo artesanal. Piezas únicas y heredables.', 'general', 'Descripción para buscadores', 'textarea', '2026-11-02 11:59:27'),
('stock_agotada', 'Esta pieza está agotada. Escríbenos y te avisamos cuando haya una nueva edición.', 'producto', 'Stock agotado', 'texto', '2026-11-02 11:59:27'),
('stock_disponible', 'Disponible · envío asegurado en 3 a 5 días hábiles', 'producto', 'Stock disponible', 'texto', '2026-11-02 11:59:27'),
('stock_ultimas', 'Solo quedan {n} piezas de esta edición', 'producto', 'Stock bajo (usa {n})', 'texto', '2026-11-02 11:59:27'),
('telefono', '+57 300 333 3333', 'contacto', 'Teléfono visible', 'telefono', '2026-11-02 11:59:27'),
('ver_pieza', 'Ver pieza', 'coleccion', 'Texto del enlace de cada pieza', 'texto', '2026-11-02 11:51:34'),
('whatsapp', '573003333333', 'contacto', 'WhatsApp (57 + número)', 'telefono', '2026-11-02 11:59:27'),
('whatsapp_mensaje', 'Hola, quisiera más información sobre las piezas Lira Ferrer.', 'contacto', 'Mensaje inicial de WhatsApp', 'texto', '2026-11-02 11:59:27');

-- ---------------------------------------------------------
-- ficha_pieza
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ficha_pieza` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `etiqueta` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `valor` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `orden` int NOT NULL DEFAULT '0',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_ficha_etiqueta` (`etiqueta`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

REPLACE INTO `ficha_pieza` (`id`, `etiqueta`, `valor`, `orden`, `activo`) VALUES
('13', 'Origen', 'Cartagena de Indias, Colombia', '1', '1'),
('14', 'Piel', 'Vacuno de primera calidad, trenzado a mano', '2', '1'),
('15', 'Lira', 'Filigrana de oro', '3', '1'),
('16', 'Gema', 'Esmeralda colombiana certificada', '4', '1');

-- ---------------------------------------------------------
-- pilares
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pilares` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `titulo` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `texto` varchar(300) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `icono` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'esmeralda',
  `orden` int NOT NULL DEFAULT '0',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_pilar_titulo` (`titulo`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

REPLACE INTO `pilares` (`id`, `titulo`, `texto`, `icono`, `orden`, `activo`) VALUES
('19', 'Esmeraldas colombianas', 'Cada esmeralda es única, como cada mujer que lleva una de nuestras creaciones.', 'esmeralda', '1', '1'),
('20', 'Orfebres colombianos', 'La joya insignia, en oro y esmeraldas colombianas, es creada una a una por manos expertas de nuestra tierra.', 'orfebre', '2', '1'),
('21', 'Marroquineros artesanos', 'Cueros de alta calidad, con cortes precisos y puntadas que hablan de tradición, hechos en Colombia.', 'cuero', '3', '1'),
('22', 'Materiales responsables', 'En cada creación honramos la naturaleza y las comunidades que hacen posible este legado. El 80 % de nuestras piezas usa fibras naturales y tinturaciones vegetales.', 'hoja', '4', '1'),
('23', 'Certificación CITES', 'Cada esmeralda cuenta con su origen y trazabilidad.', 'certificado', '5', '1'),
('24', 'Hecho en Colombia', 'Con talento local, para el mundo. Apoyamos el desarrollo de nuestra gente y nuestra tierra.', 'colombia', '6', '1');

-- ---------------------------------------------------------
-- prensa
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS `prensa` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `medio` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `titulo` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `resumen` varchar(400) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `fecha` date DEFAULT NULL,
  `imagen` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `orden` int NOT NULL DEFAULT '0',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_prensa_medio_titulo` (`medio`,`titulo`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

REPLACE INTO `prensa` (`id`, `medio`, `titulo`, `resumen`, `fecha`, `imagen`, `url`, `orden`, `activo`) VALUES
('13', 'El Heraldo · Barranquilla', 'Una ‘Lira’ que suena a esmeraldas', 'La esmeralda hace que cada bolso sea exclusivo y no un accesorio producido en serie. Una marca que lleva el nombre de Colombia en cada pieza.', NULL, 'assets/img/prensa/el-heraldo.webp', '', '1', '1'),
('14', 'ANSA · Italia', 'A Bogotá, in passerella dettagli di bellezza', 'La moda colombiana llevó a la pasarela el detalle de la belleza: una creación Lira de Ferrer, con su lira en oro y esmeralda.', '2013-08-01', 'assets/img/prensa/ansa-2013.webp', '', '2', '1'),
('15', 'Revista Dinero · Colombia', 'Estas son las carteras más costosas en Colombia', 'Lira Ferrer figura entre las carteras más costosas del país, junto a las grandes casas de lujo internacionales.', NULL, 'assets/img/prensa/dinero.webp', '', '3', '1'),
('16', 'Medellín Style', 'Las carteras más caras del mundo son colombianas', 'Una marca cartagenera que lleva esmeraldas colombianas al mercado internacional con piezas irrepetibles, hechas a mano por artesanos.', '2013-11-05', 'assets/img/prensa/medellin-style-2013.webp', 'https://www.medellinstyle.com/carteras-caras-mundo', '4', '1');

-- ---------------------------------------------------------
-- reconocimientos
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS `reconocimientos` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `titulo` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `texto` varchar(300) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `imagen` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `orden` int NOT NULL DEFAULT '0',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_reconocimiento_titulo` (`titulo`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

REPLACE INTO `reconocimientos` (`id`, `titulo`, `texto`, `imagen`, `orden`, `activo`) VALUES
('10', 'Mipymes · Ministerio de Comercio, Industria y Turismo', 'Diseño de vestuario · Lira Ferrer', 'assets/img/reconocimientos/mipymes.webp', '1', '1'),
('11', 'Presidencia de la República de Colombia', 'Invitación al Balance del primer año del TLC con EE. UU. · 2013', 'assets/img/reconocimientos/tlc.webp', '2', '1'),
('12', 'Proexport Colombia', 'Marca colombiana con proyección internacional', '', '3', '1');

-- ---------------------------------------------------------
-- faq
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS `faq` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `pregunta` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `respuesta` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `orden` int NOT NULL DEFAULT '0',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_faq_pregunta` (`pregunta`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

REPLACE INTO `faq` (`id`, `pregunta`, `respuesta`, `orden`, `activo`) VALUES
('22', '¿Cómo se te ocurrió la marca?', 'Ese es mi nombre: Lira Ferrer.', '1', '1'),
('23', '¿Por eso lo del logo? ¿Una lira?', 'Sí, idea de mi padre. Él era músico; estando mi madre embarazada dijo: «si es niña, le pondremos Lira». Así se dio. Esa soy Lira Ferrer.', '2', '1'),
('24', '¿De dónde nace la idea?', 'Era un sueño de juventud. Siempre me preguntaba: ¿por qué las mujeres perseguían carteras hermosas y de renombre fuera de mi país, siendo nosotros tan creativos? Quería que este producto tuviera un diferencial y un valor agregado: que salga listo para competir en el mundo y que, donde se vea, inmediatamente se sepa que es colombiano.', '3', '1'),
('25', '¿Dónde se hacen las piezas?', 'En Colombia. Cada cartera se crea con marroquineros artesanos, orfebres y lapidarios que trabajan el cuero, el oro y la esmeralda a mano. No es una producción en serie: cada pieza es única.', '4', '1'),
('26', '¿Qué hace única a una cartera Lira Ferrer?', 'La lira en filigrana de oro con una esmeralda colombiana certificada, la piel de vacuno de primera calidad y el trabajo artesanal. Es una joya utilitaria, pensada para heredarse.', '5', '1'),
('27', '¿Cómo cuido mi pieza?', 'Guárdala en su bolsa protectora, lejos de la humedad y del sol directo. Límpiala con un paño suave y seco, y evita el contacto prolongado con perfumes o líquidos sobre la piel y la filigrana.', '6', '1'),
('28', '¿Hacen envíos?', 'Sí. Enviamos a toda Colombia con envío asegurado y también atendemos pedidos internacionales. Escríbenos por WhatsApp para coordinar tu entrega.', '7', '1');

-- ---------------------------------------------------------
-- hitos
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS `hitos` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `anio` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `titulo` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `texto` varchar(300) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `orden` int NOT NULL DEFAULT '0',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_hito_anio` (`anio`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

REPLACE INTO `hitos` (`id`, `anio`, `titulo`, `texto`, `orden`, `activo`) VALUES
('1', '2009', 'Nace la marca', 'Lira Ferrer se constituye en Cartagena de Indias con el sueño de crear una cartera que llevara la esmeralda colombiana al mundo.', '1', '1'),
('2', '2011', 'Primera colección', 'Se lanza la línea de carteras exclusivas con esmeraldas colombianas, cuero y trabajo artesanal.', '2', '1'),
('3', '2012', 'Producto innovador', 'El Ministerio de Comercio, Industria y Turismo reconoce a Lira Ferrer como producto innovador.', '3', '1'),
('4', '2013', 'Mirada internacional', 'La marca llega a medios como ANSA, El Heraldo y Revista Dinero, y participa en ferias de Dallas, Las Vegas, Nueva York y Los Ángeles.', '4', '1'),
('5', 'Hoy', 'Un legado que continúa', 'Piezas únicas, exportadas y heredables, hechas a mano por artesanos colombianos.', '5', '1');

-- ---------------------------------------------------------
-- Productos base de la colección
-- En una instalación nueva crea las 3 piezas.
-- En una base existente actualiza el contenido pero conserva stock, precio y visibilidad.
-- ---------------------------------------------------------
INSERT INTO `productos` (`slug`, `nombre`, `color`, `detalle`, `descripcion`, `precio`, `stock`, `edicion_total`, `imagen`, `galeria`, `especificaciones`, `activo`, `orden`) VALUES
('herencia-borgona', 'Herencia Borgoña', 'Vino tinto', 'Vino tinto · Lira en filigrana de oro con esmeralda', 'Un vino tinto profundo, del color de las tardes largas del Caribe. Herencia Borgoña nace de una piel de vacuno de primera calidad, trabajada y trenzada a mano por artesanos colombianos, y coronada con la lira en filigrana de oro que sostiene una esmeralda colombiana certificada.', 2850000, 47, 50, 'assets/img/bolsos/bolso-vinotinto.webp', '[\"assets/img/bolsos/bolso-vinotinto.webp\",\"assets/img/galeria/galeria-2.webp\",\"assets/img/galeria/galeria-1.webp\"]', '[{\"etiqueta\":\"Piel\",\"valor\":\"Vacuno de primera calidad, curtido vegetal\"},{\"etiqueta\":\"Asas\",\"valor\":\"Trenzadas a mano\"},{\"etiqueta\":\"Emblema\",\"valor\":\"Lira en filigrana de oro\"},{\"etiqueta\":\"Gema\",\"valor\":\"Esmeralda colombiana certificada\"},{\"etiqueta\":\"Medidas\",\"valor\":\"28 × 22 × 12 cm\"}]', 1, 1),
('perla-eterna', 'Perla Eterna', 'Blanco perla', 'Blanco perla · Lira en filigrana de oro con esmeralda', 'Blanco perla, luminoso y atemporal. Perla Eterna está pensada para acompañarte de generación en generación: piel de vacuno seleccionada, asas trenzadas a mano y la lira de la casa en filigrana de oro con su esmeralda colombiana certificada.', 2850000, 50, 50, 'assets/img/bolsos/bolso-blanco.webp', '[\"assets/img/bolsos/bolso-blanco.webp\",\"assets/img/galeria/galeria-1.webp\",\"assets/img/galeria/galeria-2.webp\"]', '[{\"etiqueta\":\"Piel\",\"valor\":\"Vacuno de primera calidad, curtido vegetal\"},{\"etiqueta\":\"Asas\",\"valor\":\"Trenzadas a mano\"},{\"etiqueta\":\"Emblema\",\"valor\":\"Lira en filigrana de oro\"},{\"etiqueta\":\"Gema\",\"valor\":\"Esmeralda colombiana certificada\"},{\"etiqueta\":\"Medidas\",\"valor\":\"28 × 22 × 12 cm\"}]', 1, 2),
('selva-viva', 'Selva Viva', 'Verde Oliva', 'Verde oliva · Lira en filigrana de oro con esmeralda', 'Selva Viva es el verde oliva de la selva después de la lluvia: profundo, sereno y lleno de vida. Nace de una piel de vacuno de primera calidad, curtida con tintes vegetales y trenzada a mano por marroquineros colombianos. En el centro, la lira en filigrana de oro sostiene una esmeralda colombiana certificada: la firma de la casa y el recordatorio de que cada pieza es única.', 2850000, 50, 50, 'assets/img/bolsos/bolso-verde.webp', '[\"assets/img/bolsos/bolso-verde.webp\",\"assets/img/galeria/galeria-2.webp\",\"assets/img/galeria/galeria-1.webp\"]', '[{\"etiqueta\":\"Piel\",\"valor\":\"Vacuno de primera calidad, curtido vegetal\"},{\"etiqueta\":\"Asas\",\"valor\":\"Trenzadas a mano\"},{\"etiqueta\":\"Emblema\",\"valor\":\"Lira en filigrana de oro\"},{\"etiqueta\":\"Gema\",\"valor\":\"Esmeralda colombiana certificada\"},{\"etiqueta\":\"Medidas\",\"valor\":\"28 × 22 × 12 cm\"}]', 1, 3)
ON DUPLICATE KEY UPDATE
  `nombre` = VALUES(`nombre`),
  `color` = VALUES(`color`),
  `detalle` = VALUES(`detalle`),
  `descripcion` = VALUES(`descripcion`),
  `edicion_total` = VALUES(`edicion_total`),
  `imagen` = VALUES(`imagen`),
  `galeria` = VALUES(`galeria`),
  `especificaciones` = VALUES(`especificaciones`),
  `orden` = VALUES(`orden`);



SET FOREIGN_KEY_CHECKS = 1;

-- =========================================================
--  Datos no incluidos por privacidad:
--    clientes, pedidos, pedido_items, admin_usuarios,
--    admin_intentos_login y admin_actividad.
--  Crea el primer administrador con:
--    php backend/_instalar/crear_admin.php
-- =========================================================
