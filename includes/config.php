<?php
/**
 * CONECTA ERP - Archivo de Configuración Principal
 * Sistema ERP Completo con 14 Módulos y 106 Submódulos
 */

// Configuración de Base de Datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'conectae_conectaerpbd');
define('DB_USER', 'conectae_conectaerpuser');
define('DB_PASS', 'pt125824caraud');
define('DB_CHARSET', 'utf8mb4');

// Configuración de la Aplicación
define('APP_NAME', 'CONECTA ERP');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost');
define('APP_TIMEZONE', 'America/Santiago');

// Configuración de Sesión
define('SESSION_LIFETIME', 7200); // 2 horas
define('SESSION_NAME', 'CONECTA_ERP_SESSION');

// Configuración de Archivos
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_UPLOAD_SIZE', 10485760); // 10MB

// Configuración de Seguridad
define('PASSWORD_MIN_LENGTH', 8);
define('ENABLE_2FA', false);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 900); // 15 minutos

// Configuración de Trial
define('DEFAULT_TRIAL_DAYS', 14);
define('TRIAL_WARNING_DAYS', 3);

// Zona horaria
date_default_timezone_set(APP_TIMEZONE);

/**
 * Clase de Conexión a Base de Datos
 */
class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
            ];

            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            die("Error de conexión a la base de datos. Por favor, contacte al administrador.");
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->pdo;
    }

    public function query($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Query Error: " . $e->getMessage() . " | SQL: " . $sql);
            throw $e;
        }
    }

    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }

    public function insert($sql, $params = []) {
        $this->query($sql, $params);
        return $this->pdo->lastInsertId();
    }

    public function update($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }

    public function delete($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
}

/**
 * Obtener instancia de base de datos
 */
function getDB() {
    return Database::getInstance()->getConnection();
}

/**
 * Iniciar sesión segura
 */
function initSession() {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_secure', 0); // Cambiar a 1 en producción con HTTPS
        session_name(SESSION_NAME);
        session_start();

        // Regenerar ID de sesión periódicamente
        if (!isset($_SESSION['last_regeneration'])) {
            $_SESSION['last_regeneration'] = time();
        } else if (time() - $_SESSION['last_regeneration'] > 300) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
    }
}

/**
 * Verificar si el usuario está autenticado
 */
function isAuthenticated() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Verificar si el usuario es administrador
 */
function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

/**
 * Obtener información del usuario actual
 */
function getCurrentUser() {
    if (!isAuthenticated()) {
        return null;
    }

    $db = Database::getInstance();
    return $db->fetchOne(
        "SELECT * FROM users WHERE id = ?",
        [$_SESSION['user_id']]
    );
}

/**
 * Verificar estado de trial
 */
function checkTrialStatus($user_id = null) {
    if ($user_id === null && isAuthenticated()) {
        $user_id = $_SESSION['user_id'];
    }

    if ($user_id === null) {
        return false;
    }

    $db = Database::getInstance();
    $user = $db->fetchOne("SELECT status, trial_ends_at, is_admin FROM users WHERE id = ?", [$user_id]);

    if (!$user || $user['is_admin']) {
        return true; // Admin siempre tiene acceso
    }

    if ($user['status'] === 'active') {
        return true;
    }

    if ($user['status'] === 'trial' && $user['trial_ends_at']) {
        return strtotime($user['trial_ends_at']) > time();
    }

    return false;
}

/**
 * Sanitizar entrada de usuario
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Validar email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Generar token CSRF
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verificar token CSRF
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Formatear moneda
 */
function formatCurrency($amount, $currency = 'USD') {
    $symbols = [
        'USD' => '$',
        'CLP' => '$',
        'EUR' => '€',
        'GBP' => '£',
        'ARS' => '$',
        'PEN' => 'S/',
        'COP' => '$',
        'MXN' => '$',
        'BRL' => 'R$'
    ];

    $symbol = $symbols[$currency] ?? '$';
    return $symbol . number_format($amount, 2, '.', ',');
}

/**
 * Formatear fecha
 */
function formatDate($date, $format = 'd/m/Y') {
    if (empty($date)) {
        return '';
    }
    $timestamp = is_numeric($date) ? $date : strtotime($date);
    return date($format, $timestamp);
}

/**
 * Redirigir
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Mostrar alerta
 */
function showAlert($message, $type = 'info') {
    $_SESSION['alert_message'] = $message;
    $_SESSION['alert_type'] = $type;
}

/**
 * Obtener y limpiar alerta
 */
function getAlert() {
    if (isset($_SESSION['alert_message'])) {
        $alert = [
            'message' => $_SESSION['alert_message'],
            'type' => $_SESSION['alert_type'] ?? 'info'
        ];
        unset($_SESSION['alert_message']);
        unset($_SESSION['alert_type']);
        return $alert;
    }
    return null;
}

/**
 * Log de actividad
 */
function logActivity($user_id, $action, $description, $module = null) {
    try {
        $db = Database::getInstance();
        $db->insert(
            "INSERT INTO activity_logs (user_id, action, description, module, ip_address, user_agent, created_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW())",
            [
                $user_id,
                $action,
                $description,
                $module,
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]
        );
    } catch (Exception $e) {
        error_log("Error logging activity: " . $e->getMessage());
    }
}

/**
 * Enviar email (placeholder - implementar con mailer real)
 */
function sendEmail($to, $subject, $body, $from = null) {
    // TODO: Implementar con PHPMailer o servicio SMTP
    $from = $from ?? 'noreply@conectaerp.com';
    $headers = "From: $from\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

    return mail($to, $subject, $body, $headers);
}

/**
 * Obtener módulos del usuario
 */
function getUserModules($user_id) {
    $db = Database::getInstance();

    if (isAdmin()) {
        // Admin tiene acceso a todos los módulos
        return $db->fetchAll("SELECT * FROM modules WHERE is_active = 1 ORDER BY sort_order");
    }

    // Usuarios regulares tienen módulos según permisos
    return $db->fetchAll("
        SELECT DISTINCT m.*
        FROM modules m
        INNER JOIN submodules sm ON m.id = sm.module_id
        INNER JOIN user_permissions up ON sm.id = up.submodule_id
        WHERE up.user_id = ? AND m.is_active = 1
        ORDER BY m.sort_order
    ", [$user_id]);
}

/**
 * Obtener submódulos de un módulo
 */
function getModuleSubmodules($module_id, $user_id = null) {
    $db = Database::getInstance();

    if (isAdmin()) {
        return $db->fetchAll("
            SELECT * FROM submodules
            WHERE module_id = ? AND is_active = 1
            ORDER BY sort_order
        ", [$module_id]);
    }

    if ($user_id === null) {
        $user_id = $_SESSION['user_id'];
    }

    return $db->fetchAll("
        SELECT sm.*
        FROM submodules sm
        INNER JOIN user_permissions up ON sm.id = up.submodule_id
        WHERE sm.module_id = ? AND up.user_id = ? AND sm.is_active = 1 AND up.can_view = 1
        ORDER BY sm.sort_order
    ", [$module_id, $user_id]);
}

// Iniciar sesión automáticamente
initSession();
