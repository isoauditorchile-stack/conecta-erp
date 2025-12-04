<?php
/**
 * AUDITOR PRO - Sistema Multi-ISO de Gestión
 * Archivo de Configuración Central
 *
 * @version 1.0
 * @author AUDITOR PRO
 */

// ==========================================
// CONFIGURACIÓN DE BASE DE DATOS
// ==========================================
define('DB_HOST', 'localhost');
define('DB_USER', 'conectae_isogestionuser');
define('DB_PASS', 'pt125824caraud');
define('DB_NAME', 'conectae_isogestionbd');
define('DB_CHARSET', 'utf8mb4');

// ==========================================
// CONFIGURACIÓN DE LA APLICACIÓN
// ==========================================
define('APP_NAME', 'AUDITOR PRO');
define('APP_VERSION', '1.0');
define('APP_URL', '/auditool');
define('APP_TIMEZONE', 'America/Santiago');

// ==========================================
// CONFIGURACIÓN DE SESIÓN
// ==========================================
define('SESSION_TIMEOUT', 3600); // 1 hora en segundos
define('SESSION_NAME', 'AUDITORPRO_SESSION');

// ==========================================
// CONFIGURACIÓN DE IDIOMAS SOPORTADOS
// ==========================================
define('DEFAULT_LANGUAGE', 'es');
define('SUPPORTED_LANGUAGES', json_encode(['es' => 'Español', 'en' => 'English', 'pt' => 'Português']));

// ==========================================
// CONFIGURACIÓN DE PAÍSES SOPORTADOS
// ==========================================
define('DEFAULT_COUNTRY', 'CL');
define('SUPPORTED_COUNTRIES', json_encode([
    'CL' => 'Chile',
    'AR' => 'Argentina',
    'PE' => 'Perú',
    'CO' => 'Colombia',
    'MX' => 'México',
    'ES' => 'España',
    'US' => 'Estados Unidos',
    'BR' => 'Brasil'
]));

// ==========================================
// CONFIGURACIÓN DE ISOs DISPONIBLES
// ==========================================
define('AVAILABLE_ISOS', json_encode([
    'iso_27001' => ['name' => 'ISO 27001:2022', 'title' => 'Seguridad de la Información', 'color' => '#00994d', 'icon' => '🔐'],
    'iso_22301' => ['name' => 'ISO 22301:2019', 'title' => 'Continuidad del Negocio', 'color' => '#e74c3c', 'icon' => '🔄'],
    'iso_37001' => ['name' => 'ISO 37001:2016', 'title' => 'Antisoborno', 'color' => '#9b59b6', 'icon' => '⚖️'],
    'iso_9001' => ['name' => 'ISO 9001:2015', 'title' => 'Gestión de Calidad', 'color' => '#3498db', 'icon' => '✓'],
    'iso_14001' => ['name' => 'ISO 14001:2015', 'title' => 'Gestión Ambiental', 'color' => '#27ae60', 'icon' => '🌱'],
    'iso_45001' => ['name' => 'ISO 45001:2018', 'title' => 'Seguridad y Salud', 'color' => '#e67e22', 'icon' => '👷'],
    'iso_31000' => ['name' => 'ISO 31000:2018', 'title' => 'Gestión de Riesgos', 'color' => '#c0392b', 'icon' => '📊'],
    'iso_50001' => ['name' => 'ISO 50001:2018', 'title' => 'Gestión Energética', 'color' => '#f39c12', 'icon' => '⚡'],
    'iso_20000' => ['name' => 'ISO 20000-1:2018', 'title' => 'Gestión de Servicios TI', 'color' => '#16a085', 'icon' => '💻'],
    'iso_22000' => ['name' => 'ISO 22000:2018', 'title' => 'Seguridad Alimentaria', 'color' => '#d35400', 'icon' => '🍎'],
    'iso_27017' => ['name' => 'ISO 27017:2015', 'title' => 'Seguridad Cloud', 'color' => '#2980b9', 'icon' => '☁️'],
    'iso_27701' => ['name' => 'ISO 27701:2019', 'title' => 'Privacidad', 'color' => '#8e44ad', 'icon' => '🔒'],
    'iso_13485' => ['name' => 'ISO 13485:2016', 'title' => 'Dispositivos Médicos', 'color' => '#e74c3c', 'icon' => '🏥'],
    'iso_28000' => ['name' => 'ISO 28000:2007', 'title' => 'Seguridad Cadena Suministro', 'color' => '#34495e', 'icon' => '🚚']
]));

// ==========================================
// CONFIGURACIÓN DE ARCHIVOS Y UPLOADS
// ==========================================
define('UPLOAD_MAX_SIZE', 10485760); // 10MB en bytes
define('UPLOAD_ALLOWED_TYPES', json_encode(['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png']));
define('UPLOAD_PATH', __DIR__ . '/../uploads/');

// ==========================================
// CONFIGURACIÓN DE EXPORTACIÓN
// ==========================================
define('EXPORT_TEMP_PATH', __DIR__ . '/../exports/temp/');
define('EXPORT_COMPANY_LOGO_PATH', __DIR__ . '/../assets/logos/');

// ==========================================
// CONFIGURACIÓN DE SEGURIDAD
// ==========================================
define('PASSWORD_MIN_LENGTH', 8);
define('PASSWORD_REQUIRE_SPECIAL', true);
define('PASSWORD_REQUIRE_NUMBER', true);
define('PASSWORD_REQUIRE_UPPERCASE', true);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutos

// ==========================================
// CONFIGURACIÓN DE EMAIL
// ==========================================
define('SMTP_HOST', 'localhost');
define('SMTP_PORT', 587);
define('SMTP_USER', '');
define('SMTP_PASS', '');
define('SMTP_FROM_EMAIL', 'noreply@auditorpro.com');
define('SMTP_FROM_NAME', 'AUDITOR PRO');

// ==========================================
// CONFIGURACIÓN DE LOGS
// ==========================================
define('LOG_PATH', __DIR__ . '/../logs/');
define('LOG_LEVEL', 'INFO'); // DEBUG, INFO, WARNING, ERROR
define('LOG_MAX_SIZE', 5242880); // 5MB

// ==========================================
// ZONA HORARIA
// ==========================================
date_default_timezone_set(APP_TIMEZONE);

// ==========================================
// FUNCIÓN DE CONEXIÓN A BASE DE DATOS
// ==========================================
function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    if ($conn->connect_error) {
        error_log("Error de conexión DB: " . $conn->connect_error);
        die("Error de conexión a la base de datos. Por favor, contacte al administrador.");
    }

    $conn->set_charset(DB_CHARSET);
    return $conn;
}

// ==========================================
// FUNCIÓN PARA OBTENER CONFIGURACIÓN DE ISO
// ==========================================
function getISOConfig($iso_code) {
    $isos = json_decode(AVAILABLE_ISOS, true);
    return isset($isos[$iso_code]) ? $isos[$iso_code] : null;
}

// ==========================================
// FUNCIÓN PARA VERIFICAR SESIÓN
// ==========================================
function checkSession() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /auditool/login.php');
        exit;
    }

    // Verificar timeout de sesión
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        session_unset();
        session_destroy();
        header('Location: /auditool/login.php?timeout=1');
        exit;
    }

    $_SESSION['last_activity'] = time();
}

// ==========================================
// FUNCIÓN PARA SANITIZAR ENTRADA
// ==========================================
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// ==========================================
// FUNCIÓN PARA REGISTRAR LOGS
// ==========================================
function logActivity($user_id, $action, $module, $details = '') {
    $conn = getDBConnection();

    $sql = "INSERT INTO activity_logs (user_id, action, module, details, ip_address, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())";

    $stmt = $conn->prepare($sql);
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmt->bind_param("issss", $user_id, $action, $module, $details, $ip);
    $stmt->execute();
    $stmt->close();
    $conn->close();
}

// ==========================================
// INICIALIZAR SESIÓN SI NO ESTÁ INICIADA
// ==========================================
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}
?>
