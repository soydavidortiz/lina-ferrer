<?php
declare(strict_types=1);

// ===== Aplicación =====
const APP_ENV          = 'local';                  // 'local' o 'produccion'
const APP_NOMBRE       = 'Lina Ferrer · Panel';
const APP_ZONA_HORARIA = 'America/Bogota';

// URL del backend, sin barra final:
// Local:      http://localhost/lina-ferrer/backend  → '/lina-ferrer/backend'
// Producción: https://midominio.com/backend         → '/backend'
const BASE_URL = '/lina-ferrer/backend';

// ===== Base de datos =====
const DB_HOST    = 'localhost';
const DB_PORT    = 3306;
const DB_NAME    = 'lina_ferrer';                  // ← verificar en HeidiSQL
const DB_USER    = 'root';
const DB_PASS    = '';
const DB_CHARSET = 'utf8mb4';

// ===== Tablas del panel =====
const TABLA_ADMIN     = 'admin_usuarios';
const TABLA_INTENTOS  = 'admin_intentos_login';
const TABLA_ACTIVIDAD = 'admin_actividad';

// ===== Seguridad =====
const SESION_NOMBRE         = 'LFADMSESS';
const SESION_TIMEOUT        = 1800;                // 30 min de inactividad
const LOGIN_MAX_INTENTOS    = 5;                   // fallos por correo
const LOGIN_MAX_INTENTOS_IP = 20;                  // fallos por IP
const LOGIN_BLOQUEO_SEG     = 900;                 // 15 min
