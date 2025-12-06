<?php
/**
 * AUDITOR PRO - API v1
 * Configuración General de la API
 *
 * @package AuditorPRO
 * @version 1.0
 */

// Configuración JWT
define('JWT_SECRET_KEY', 'AUDITOR_PRO_SECRET_KEY_CHANGE_IN_PRODUCTION_2024_ISO27001');
define('JWT_ALGORITHM', 'HS256');
define('JWT_EXPIRATION_TIME', 3600 * 8); // 8 horas
define('JWT_REFRESH_EXPIRATION_TIME', 3600 * 24 * 7); // 7 días

// Configuración API
define('API_VERSION', 'v1');
define('API_RATE_LIMIT', 100); // Requests por minuto
define('API_RATE_LIMIT_WINDOW', 60); // Segundos

// Configuración CORS
define('CORS_ALLOWED_ORIGINS', '*'); // Cambiar en producción
define('CORS_ALLOWED_METHODS', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
define('CORS_ALLOWED_HEADERS', 'Content-Type, Authorization, X-Requested-With');

// Configuración de Paginación
define('DEFAULT_PAGE_SIZE', 20);
define('MAX_PAGE_SIZE', 100);

// Configuración de Logs
define('LOG_API_REQUESTS', true);
define('LOG_FILE_PATH', __DIR__ . '/../../../logs/api.log');

// Timezone
date_default_timezone_set('America/Santiago');

// Error reporting (solo en desarrollo)
error_reporting(E_ALL);
ini_set('display_errors', 0); // No mostrar errores en producción
ini_set('log_errors', 1);
