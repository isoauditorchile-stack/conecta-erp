<?php
// ============================================
// CONECTA ERP - CONFIGURACIÓN PRINCIPAL
// ============================================

// Configuración de zona horaria
date_default_timezone_set('America/Santiago');

// Configuración de errores (desactivar en producción)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configuración de base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'conectae_conectaerpbd');
define('DB_USER', 'conectae_conectaerpuser');
define('DB_PASS', 'pt125824caraud');
define('DB_CHARSET', 'utf8mb4');

// Configuración del sistema
define('SITE_NAME', 'CONECTA ERP');
define('SITE_URL', 'https://conectaerp.com');
define('SITE_VERSION', '1.0.0');

// Super Admin
define('SUPER_ADMIN_EMAIL', 'auditorexchile@gmail.com');
define('SUPER_ADMIN_USERNAME', 'auditorex chile');

// Conexión a la base de datos
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Verificar conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Establecer charset
$conn->set_charset(DB_CHARSET);

// Funciones auxiliares
if (!function_exists('formatDate')) {
    function formatDate($date, $format = 'd/m/Y') {
        if (empty($date)) return '';
        $timestamp = is_numeric($date) ? $date : strtotime($date);
        return date($format, $timestamp);
    }
}

if (!function_exists('formatCurrency')) {
    function formatCurrency($amount, $currency = 'CLP') {
        $symbols = [
            'CLP' => '$',
            'USD' => 'US$',
            'EUR' => '€',
            'ARS' => '$',
            'BRL' => 'R$',
            'PEN' => 'S/',
            'COP' => '$',
            'MXN' => '$',
            'UYU' => '$',
            'BOB' => 'Bs'
        ];

        $symbol = $symbols[$currency] ?? '$';
        return $symbol . ' ' . number_format($amount, 0, ',', '.');
    }
}

if (!function_exists('sanitize')) {
    function sanitize($data) {
        if (is_array($data)) {
            return array_map('sanitize', $data);
        }
        return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('redirect')) {
    function redirect($url) {
        header("Location: $url");
        exit();
    }
}

if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
}

if (!function_exists('isSuperAdmin')) {
    function isSuperAdmin() {
        return isset($_SESSION['es_super_admin']) && $_SESSION['es_super_admin'] == 1;
    }
}

if (!function_exists('isAdmin')) {
    function isAdmin() {
        return isset($_SESSION['es_admin']) && $_SESSION['es_admin'] == 1;
    }
}

if (!function_exists('requireLogin')) {
    function requireLogin() {
        if (!isLoggedIn()) {
            redirect('/login.php');
        }
    }
}

if (!function_exists('requireSuperAdmin')) {
    function requireSuperAdmin() {
        if (!isSuperAdmin()) {
            redirect('/user/dashboard_user.php');
        }
    }
}

if (!function_exists('getConfig')) {
    function getConfig($key, $default = null) {
        global $conn;
        $stmt = $conn->prepare("SELECT valor FROM configuracion WHERE clave = ?");
        $stmt->bind_param("s", $key);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            return $row['valor'];
        }

        return $default;
    }
}

if (!function_exists('setConfig')) {
    function setConfig($key, $value) {
        global $conn;
        $stmt = $conn->prepare("UPDATE configuracion SET valor = ? WHERE clave = ?");
        $stmt->bind_param("ss", $value, $key);
        return $stmt->execute();
    }
}

if (!function_exists('logAuditoria')) {
    function logAuditoria($accion, $tabla = null, $registro_id = null, $valores_anteriores = null, $valores_nuevos = null, $descripcion = null) {
        global $conn;

        $usuario_id = $_SESSION['user_id'] ?? null;
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $modulo = $_SESSION['modulo_actual'] ?? null;

        $stmt = $conn->prepare("INSERT INTO logs_auditoria (usuario_id, accion, tabla, registro_id, valores_anteriores, valores_nuevos, modulo, descripcion, ip_address) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->bind_param("issississs",
            $usuario_id,
            $accion,
            $tabla,
            $registro_id,
            $valores_anteriores,
            $valores_nuevos,
            $modulo,
            $descripcion,
            $ip_address
        );

        return $stmt->execute();
    }
}

if (!function_exists('createNotification')) {
    function createNotification($usuario_id, $tipo, $titulo, $mensaje, $enlace = null) {
        global $conn;

        $stmt = $conn->prepare("INSERT INTO notificaciones (usuario_id, tipo, titulo, mensaje, enlace) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $usuario_id, $tipo, $titulo, $mensaje, $enlace);

        return $stmt->execute();
    }
}

if (!function_exists('getDiasRestantesTrial')) {
    function getDiasRestantesTrial($fecha_fin_trial) {
        if (empty($fecha_fin_trial)) return 0;

        $fecha_fin = strtotime($fecha_fin_trial);
        $hoy = time();
        $diferencia = $fecha_fin - $hoy;

        return max(0, ceil($diferencia / 86400));
    }
}

if (!function_exists('formatRUT')) {
    function formatRUT($rut) {
        // Limpiar el RUT
        $rut = preg_replace('/[^0-9kK]/', '', $rut);
        if (strlen($rut) < 2) return $rut;

        // Separar dígito verificador
        $dv = substr($rut, -1);
        $numero = substr($rut, 0, -1);

        // Formatear con puntos
        $numero = number_format($numero, 0, '', '.');

        return $numero . '-' . $dv;
    }
}
?>
