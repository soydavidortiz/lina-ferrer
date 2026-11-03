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
