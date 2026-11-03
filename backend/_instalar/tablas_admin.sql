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
