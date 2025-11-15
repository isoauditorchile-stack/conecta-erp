<?php
/**
 * AUDITORPRO - Archivo de Configuración Principal
 * Sistema de Gestión de Normas ISO y Auditoría
 */

// Evitar acceso directo
if (!defined('ACCESO_PERMITIDO')) {
    die('Acceso denegado');
}

// Configuración de Base de Datos
define('BD_HOST', 'localhost');
define('BD_NOMBRE', 'conectae_conectaerpbd');
define('BD_USUARIO', 'conectae_conectaerpuser');
define('BD_CLAVE', 'pt125824caraud');
define('BD_CHARSET', 'utf8mb4');

// Configuración de la Aplicación
define('NOMBRE_APP', 'AuditorPro');
define('VERSION_APP', '1.0.0');
define('URL_APP', 'http://localhost');
define('ZONA_HORARIA', 'America/Santiago');

// Rutas del sistema
define('RUTA_RAIZ', dirname(__DIR__));
define('RUTA_INCLUIR', RUTA_RAIZ . '/incluir');
define('RUTA_USUARIO', RUTA_RAIZ . '/usuario');
define('RUTA_ADMIN', RUTA_RAIZ . '/administrador');
define('RUTA_UPLOADS', RUTA_RAIZ . '/uploads');

// Configuración de Sesión
define('TIEMPO_SESION', 7200); // 2 horas
define('NOMBRE_SESION', 'AUDITORPRO_SESSION');

// Configuración de Archivos
define('DIR_UPLOADS', RUTA_UPLOADS . '/');
define('TAMANO_MAX_UPLOAD', 10485760); // 10MB

// Configuración de Seguridad
define('LONGITUD_MIN_CLAVE', 8);
define('ACTIVAR_2FA', false);
define('MAX_INTENTOS_LOGIN', 5);
define('TIEMPO_BLOQUEO', 900); // 15 minutos

// Configuración de Trial
define('DIAS_TRIAL_DEFAULT', 14);
define('DIAS_AVISO_TRIAL', 3);

// Super Admin Email - ÚNICO USUARIO CON ACCESO TOTAL
define('SUPER_ADMIN_EMAIL', 'auditorexchile@gmail.com');

// Zona horaria
date_default_timezone_set(ZONA_HORARIA);

// Configuración de errores (desactivar en producción)
error_reporting(E_ALL);
ini_set('display_errors', 1);
