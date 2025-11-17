<?php
/**
 * ============================================
 * CONECTA ERP - FUNCIONES AUXILIARES
 * ============================================
 *
 * Archivo centralizado de funciones auxiliares para el sistema ERP
 *
 * @package    CONECTA ERP
 * @version    1.0.0
 * @author     ISO Auditor Chile
 * @copyright  2025 CONECTA ERP
 *
 * Descripción:
 * Este archivo contiene todas las funciones auxiliares comunes utilizadas
 * en todo el sistema ERP. Incluye funciones para:
 * - Gestión de base de datos
 * - Autenticación y autorización
 * - Validación y sanitización
 * - Formateo de datos
 * - Manejo de sesiones
 * - Logging y auditoría
 * - Notificaciones
 * - Utilidades generales
 */

// ============================================
// CONSTANTES GLOBALES DEL SISTEMA
// ============================================

if (!defined('CONECTA_VERSION')) {
    define('CONECTA_VERSION', '1.0.0');
}

if (!defined('CONECTA_ENVIRONMENT')) {
    define('CONECTA_ENVIRONMENT', 'production'); // development, staging, production
}

// ============================================
// FUNCIONES DE BASE DE DATOS
// ============================================

/**
 * Ejecutar query con prepared statements
 *
 * @param mysqli $conn Conexión a la base de datos
 * @param string $sql Query SQL
 * @param array $params Parámetros para bind
 * @param string $types Tipos de parámetros (i=int, s=string, d=double, b=blob)
 * @return mysqli_result|bool
 */
function executeQuery($conn, $sql, $params = [], $types = '') {
    if (empty($params)) {
        return $conn->query($sql);
    }

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        logError("Error preparing statement: " . $conn->error . " | SQL: $sql");
        return false;
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    return $result !== false ? $result : $stmt;
}

/**
 * Obtener un solo registro de la base de datos
 *
 * @param mysqli $conn
 * @param string $sql
 * @param array $params
 * @param string $types
 * @return array|null
 */
function fetchOne($conn, $sql, $params = [], $types = '') {
    $result = executeQuery($conn, $sql, $params, $types);

    if ($result && $result instanceof mysqli_result) {
        return $result->fetch_assoc();
    }

    return null;
}

/**
 * Obtener todos los registros
 *
 * @param mysqli $conn
 * @param string $sql
 * @param array $params
 * @param string $types
 * @return array
 */
function fetchAll($conn, $sql, $params = [], $types = '') {
    $result = executeQuery($conn, $sql, $params, $types);
    $rows = [];

    if ($result && $result instanceof mysqli_result) {
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
    }

    return $rows;
}

/**
 * Insertar registro y retornar ID
 *
 * @param mysqli $conn
 * @param string $table
 * @param array $data
 * @return int|false
 */
function dbInsert($conn, $table, $data) {
    $columns = array_keys($data);
    $values = array_values($data);
    $types = '';

    // Determinar tipos automáticamente
    foreach ($values as $value) {
        if (is_int($value)) {
            $types .= 'i';
        } elseif (is_float($value)) {
            $types .= 'd';
        } else {
            $types .= 's';
        }
    }

    $columnsList = implode(', ', $columns);
    $placeholders = implode(', ', array_fill(0, count($columns), '?'));

    $sql = "INSERT INTO $table ($columnsList) VALUES ($placeholders)";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        logError("Error preparing insert: " . $conn->error);
        return false;
    }

    $stmt->bind_param($types, ...$values);

    if ($stmt->execute()) {
        return $conn->insert_id;
    }

    logError("Error executing insert: " . $stmt->error);
    return false;
}

/**
 * Actualizar registro(s)
 *
 * @param mysqli $conn
 * @param string $table
 * @param array $data
 * @param string $where Condición WHERE
 * @param array $whereParams
 * @return int Número de filas afectadas
 */
function dbUpdate($conn, $table, $data, $where, $whereParams = []) {
    $setParts = [];
    $values = [];
    $types = '';

    foreach ($data as $column => $value) {
        $setParts[] = "$column = ?";
        $values[] = $value;

        if (is_int($value)) {
            $types .= 'i';
        } elseif (is_float($value)) {
            $types .= 'd';
        } else {
            $types .= 's';
        }
    }

    // Agregar parámetros del WHERE
    foreach ($whereParams as $param) {
        $values[] = $param;

        if (is_int($param)) {
            $types .= 'i';
        } elseif (is_float($param)) {
            $types .= 'd';
        } else {
            $types .= 's';
        }
    }

    $setClause = implode(', ', $setParts);
    $sql = "UPDATE $table SET $setClause WHERE $where";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        logError("Error preparing update: " . $conn->error);
        return 0;
    }

    $stmt->bind_param($types, ...$values);
    $stmt->execute();

    return $stmt->affected_rows;
}

/**
 * Eliminar registro(s)
 *
 * @param mysqli $conn
 * @param string $table
 * @param string $where
 * @param array $params
 * @return int
 */
function dbDelete($conn, $table, $where, $params = []) {
    $sql = "DELETE FROM $table WHERE $where";
    $types = str_repeat('s', count($params));

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        logError("Error preparing delete: " . $conn->error);
        return 0;
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();

    return $stmt->affected_rows;
}

/**
 * Verificar si existe un registro
 *
 * @param mysqli $conn
 * @param string $table
 * @param string $where
 * @param array $params
 * @return bool
 */
function recordExists($conn, $table, $where, $params = []) {
    $sql = "SELECT 1 FROM $table WHERE $where LIMIT 1";
    $result = fetchOne($conn, $sql, $params, str_repeat('s', count($params)));

    return $result !== null;
}

/**
 * Contar registros
 *
 * @param mysqli $conn
 * @param string $table
 * @param string $where
 * @param array $params
 * @return int
 */
function countRecords($conn, $table, $where = '1=1', $params = []) {
    $sql = "SELECT COUNT(*) as total FROM $table WHERE $where";
    $result = fetchOne($conn, $sql, $params, str_repeat('s', count($params)));

    return $result ? (int)$result['total'] : 0;
}

// ============================================
// FUNCIONES DE SEGURIDAD
// ============================================

/**
 * Inicializar sesión de forma segura
 */
function initSecureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        // Configuración segura de sesión
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_secure', 0); // Cambiar a 1 en producción con HTTPS
        ini_set('session.cookie_samesite', 'Lax');

        session_name('CONECTA_ERP_SESSION');
        session_start();

        // Regenerar ID de sesión periódicamente
        if (!isset($_SESSION['last_regeneration'])) {
            $_SESSION['last_regeneration'] = time();
        } elseif (time() - $_SESSION['last_regeneration'] > 300) { // 5 minutos
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }

        // Validar IP y User Agent para prevenir session hijacking
        if (!isset($_SESSION['user_ip'])) {
            $_SESSION['user_ip'] = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        } else {
            // Verificar que IP y User Agent no hayan cambiado
            $currentIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $currentUA = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

            if ($_SESSION['user_ip'] !== $currentIP || $_SESSION['user_agent'] !== $currentUA) {
                // Posible session hijacking - destruir sesión
                session_destroy();
                session_start();
            }
        }
    }
}

/**
 * Generar token CSRF
 *
 * @return string
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }

    // Regenerar token cada hora
    if (isset($_SESSION['csrf_token_time']) && (time() - $_SESSION['csrf_token_time']) > 3600) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }

    return $_SESSION['csrf_token'];
}

/**
 * Verificar token CSRF
 *
 * @param string $token
 * @return bool
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Generar contraseña segura
 *
 * @param int $length
 * @return string
 */
function generateSecurePassword($length = 16) {
    $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $lowercase = 'abcdefghijklmnopqrstuvwxyz';
    $numbers = '0123456789';
    $special = '!@#$%^&*()-_=+[]{}|;:,.<>?';

    $all = $uppercase . $lowercase . $numbers . $special;

    $password = '';
    $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
    $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
    $password .= $numbers[random_int(0, strlen($numbers) - 1)];
    $password .= $special[random_int(0, strlen($special) - 1)];

    for ($i = 4; $i < $length; $i++) {
        $password .= $all[random_int(0, strlen($all) - 1)];
    }

    return str_shuffle($password);
}

/**
 * Hash de contraseña
 *
 * @param string $password
 * @return string
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_ARGON2ID, [
        'memory_cost' => 65536,
        'time_cost' => 4,
        'threads' => 3
    ]);
}

/**
 * Verificar contraseña
 *
 * @param string $password
 * @param string $hash
 * @return bool
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Validar fortaleza de contraseña
 *
 * @param string $password
 * @return array ['valid' => bool, 'errors' => array]
 */
function validatePasswordStrength($password) {
    $errors = [];

    if (strlen($password) < 8) {
        $errors[] = 'La contraseña debe tener al menos 8 caracteres';
    }

    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Debe contener al menos una letra mayúscula';
    }

    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Debe contener al menos una letra minúscula';
    }

    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Debe contener al menos un número';
    }

    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = 'Debe contener al menos un carácter especial';
    }

    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

// ============================================
// FUNCIONES DE VALIDACIÓN
// ============================================

/**
 * Validar email
 *
 * @param string $email
 * @return bool
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validar URL
 *
 * @param string $url
 * @return bool
 */
function isValidURL($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

/**
 * Validar número de teléfono (formato internacional)
 *
 * @param string $phone
 * @return bool
 */
function isValidPhone($phone) {
    // Eliminar espacios y caracteres especiales
    $phone = preg_replace('/[^0-9+]/', '', $phone);

    // Validar longitud (8-15 dígitos)
    return strlen($phone) >= 8 && strlen($phone) <= 15;
}

/**
 * Validar fecha en formato específico
 *
 * @param string $date
 * @param string $format
 * @return bool
 */
function isValidDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

/**
 * Validar RUT chileno
 * Incluye la función del archivo rut_validator.php
 *
 * @param string $rut
 * @return bool
 */
if (!function_exists('validateChileanRUT')) {
    function validateChileanRUT($rut) {
        if (empty($rut)) return false;

        $rut = strtoupper(preg_replace('/[^0-9kK]/', '', trim($rut)));

        if (strlen($rut) < 2) return false;

        $body = substr($rut, 0, -1);
        $dv = substr($rut, -1);

        if (!is_numeric($body)) return false;
        if (strlen($body) < 7 || strlen($body) > 8) return false;

        $suma = 0;
        $multiplo = 2;

        for ($i = strlen($body) - 1; $i >= 0; $i--) {
            $suma += intval($body[$i]) * $multiplo;
            $multiplo = $multiplo < 7 ? $multiplo + 1 : 2;
        }

        $dvEsperado = 11 - ($suma % 11);
        $dvCalculado = $dvEsperado === 11 ? '0' : ($dvEsperado === 10 ? 'K' : strval($dvEsperado));

        return $dv === $dvCalculado;
    }
}

/**
 * Validar DNI argentino
 *
 * @param string $dni
 * @return bool
 */
function validateArgentineDNI($dni) {
    $dni = preg_replace('/[^0-9]/', '', $dni);
    return strlen($dni) >= 7 && strlen($dni) <= 8 && is_numeric($dni);
}

/**
 * Validar CPF/CNPJ brasileño
 *
 * @param string $cpf_cnpj
 * @return bool
 */
function validateBrazilianCPF_CNPJ($cpf_cnpj) {
    $cpf_cnpj = preg_replace('/[^0-9]/', '', $cpf_cnpj);

    if (strlen($cpf_cnpj) === 11) {
        // Validar CPF
        if (preg_match('/(\d)\1{10}/', $cpf_cnpj)) return false;

        for ($t = 9; $t < 11; $t++) {
            $d = 0;
            for ($c = 0; $c < $t; $c++) {
                $d += $cpf_cnpj[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf_cnpj[$c] != $d) return false;
        }
        return true;
    } elseif (strlen($cpf_cnpj) === 14) {
        // Validar CNPJ
        if (preg_match('/(\d)\1{13}/', $cpf_cnpj)) return false;

        $length = strlen($cpf_cnpj) - 2;
        $numbers = substr($cpf_cnpj, 0, $length);
        $digits = substr($cpf_cnpj, $length);
        $sum = 0;
        $pos = $length - 7;

        for ($i = $length; $i >= 1; $i--) {
            $sum += $numbers[$length - $i] * $pos--;
            if ($pos < 2) $pos = 9;
        }

        $result = $sum % 11 < 2 ? 0 : 11 - $sum % 11;
        if ($result != $digits[0]) return false;

        $length++;
        $numbers = substr($cpf_cnpj, 0, $length);
        $sum = 0;
        $pos = $length - 7;

        for ($i = $length; $i >= 1; $i--) {
            $sum += $numbers[$length - $i] * $pos--;
            if ($pos < 2) $pos = 9;
        }

        $result = $sum % 11 < 2 ? 0 : 11 - $sum % 11;
        return $result == $digits[1];
    }

    return false;
}

// ============================================
// FUNCIONES DE FORMATEO
// ============================================

/**
 * Formatear RUT chileno
 *
 * @param string $rut
 * @return string
 */
if (!function_exists('formatRUT')) {
    function formatRUT($rut) {
        $rut = preg_replace('/[^0-9kK]/', '', $rut);
        if (strlen($rut) < 2) return $rut;

        $dv = substr($rut, -1);
        $numero = substr($rut, 0, -1);
        $numero = number_format($numero, 0, '', '.');

        return $numero . '-' . strtoupper($dv);
    }
}

/**
 * Formatear moneda
 *
 * @param float $amount
 * @param string $currency
 * @param bool $showSymbol
 * @return string
 */
if (!function_exists('formatCurrency')) {
    function formatCurrency($amount, $currency = 'CLP', $showSymbol = true) {
        $symbols = [
            'CLP' => '$',
            'USD' => 'US$',
            'EUR' => '€',
            'GBP' => '£',
            'ARS' => '$',
            'BRL' => 'R$',
            'PEN' => 'S/',
            'COP' => '$',
            'MXN' => '$',
            'UYU' => '$',
            'BOB' => 'Bs',
            'PYG' => '₲'
        ];

        $symbol = $symbols[$currency] ?? $currency . ' ';
        $formatted = number_format($amount, 0, ',', '.');

        return $showSymbol ? $symbol . ' ' . $formatted : $formatted;
    }
}

/**
 * Formatear fecha
 *
 * @param string|int $date
 * @param string $format
 * @return string
 */
if (!function_exists('formatDate')) {
    function formatDate($date, $format = 'd/m/Y') {
        if (empty($date)) return '';
        $timestamp = is_numeric($date) ? $date : strtotime($date);
        return date($format, $timestamp);
    }
}

/**
 * Formatear fecha y hora
 *
 * @param string|int $datetime
 * @param string $format
 * @return string
 */
function formatDateTime($datetime, $format = 'd/m/Y H:i') {
    if (empty($datetime)) return '';
    $timestamp = is_numeric($datetime) ? $datetime : strtotime($datetime);
    return date($format, $timestamp);
}

/**
 * Fecha relativa (hace X tiempo)
 *
 * @param string|int $date
 * @return string
 */
function relativeTime($date) {
    $timestamp = is_numeric($date) ? $date : strtotime($date);
    $diff = time() - $timestamp;

    if ($diff < 60) return 'Hace ' . $diff . ' segundo' . ($diff != 1 ? 's' : '');
    if ($diff < 3600) return 'Hace ' . floor($diff / 60) . ' minuto' . (floor($diff / 60) != 1 ? 's' : '');
    if ($diff < 86400) return 'Hace ' . floor($diff / 3600) . ' hora' . (floor($diff / 3600) != 1 ? 's' : '');
    if ($diff < 604800) return 'Hace ' . floor($diff / 86400) . ' día' . (floor($diff / 86400) != 1 ? 's' : '');
    if ($diff < 2592000) return 'Hace ' . floor($diff / 604800) . ' semana' . (floor($diff / 604800) != 1 ? 's' : '');
    if ($diff < 31536000) return 'Hace ' . floor($diff / 2592000) . ' mes' . (floor($diff / 2592000) != 1 ? 'es' : '');

    return 'Hace ' . floor($diff / 31536000) . ' año' . (floor($diff / 31536000) != 1 ? 's' : '');
}

/**
 * Formatear bytes a tamaño legible
 *
 * @param int $bytes
 * @param int $precision
 * @return string
 */
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];

    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }

    return round($bytes, $precision) . ' ' . $units[$i];
}

/**
 * Formatear número de teléfono
 *
 * @param string $phone
 * @param string $country
 * @return string
 */
function formatPhone($phone, $country = 'CL') {
    $phone = preg_replace('/[^0-9+]/', '', $phone);

    switch ($country) {
        case 'CL': // Chile: +56 9 XXXX XXXX
            if (strlen($phone) === 9) {
                return '+56 ' . substr($phone, 0, 1) . ' ' . substr($phone, 1, 4) . ' ' . substr($phone, 5);
            }
            break;
        case 'AR': // Argentina: +54 9 11 XXXX XXXX
            if (strlen($phone) >= 10) {
                return '+54 9 ' . substr($phone, 0, 2) . ' ' . substr($phone, 2, 4) . ' ' . substr($phone, 6);
            }
            break;
        case 'BR': // Brasil: +55 (XX) XXXXX-XXXX
            if (strlen($phone) === 11) {
                return '+55 (' . substr($phone, 0, 2) . ') ' . substr($phone, 2, 5) . '-' . substr($phone, 7);
            }
            break;
    }

    return $phone;
}

// ============================================
// FUNCIONES DE SANITIZACIÓN
// ============================================

/**
 * Sanitizar entrada de usuario
 *
 * @param mixed $data
 * @return mixed
 */
if (!function_exists('sanitize')) {
    function sanitize($data) {
        if (is_array($data)) {
            return array_map('sanitize', $data);
        }
        return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Limpiar string para uso en SQL (adicional a prepared statements)
 *
 * @param mysqli $conn
 * @param string $string
 * @return string
 */
function cleanString($conn, $string) {
    return $conn->real_escape_string(trim($string));
}

/**
 * Sanitizar nombre de archivo
 *
 * @param string $filename
 * @return string
 */
function sanitizeFilename($filename) {
    // Eliminar caracteres peligrosos
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);

    // Prevenir directory traversal
    $filename = str_replace(['../', '..\\'], '', $filename);

    return $filename;
}

/**
 * Sanitizar HTML (permitir tags seguros)
 *
 * @param string $html
 * @return string
 */
function sanitizeHTML($html) {
    $allowedTags = '<p><br><strong><em><u><h1><h2><h3><ul><ol><li><a><img>';
    return strip_tags($html, $allowedTags);
}

// ============================================
// FUNCIONES DE AUTENTICACIÓN Y AUTORIZACIÓN
// ============================================

/**
 * Verificar si el usuario está autenticado
 *
 * @return bool
 */
if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
}

/**
 * Verificar si es super admin
 *
 * @return bool
 */
if (!function_exists('isSuperAdmin')) {
    function isSuperAdmin() {
        return isset($_SESSION['es_super_admin']) && $_SESSION['es_super_admin'] == 1;
    }
}

/**
 * Verificar si es admin
 *
 * @return bool
 */
if (!function_exists('isAdmin')) {
    function isAdmin() {
        return isset($_SESSION['es_admin']) && $_SESSION['es_admin'] == 1;
    }
}

/**
 * Requerir autenticación
 */
if (!function_exists('requireLogin')) {
    function requireLogin() {
        if (!isLoggedIn()) {
            redirect('/login.php');
        }
    }
}

/**
 * Requerir super admin
 */
if (!function_exists('requireSuperAdmin')) {
    function requireSuperAdmin() {
        requireLogin();
        if (!isSuperAdmin()) {
            redirect('/user/dashboard_user.php');
        }
    }
}

/**
 * Requerir admin
 */
function requireAdmin() {
    requireLogin();
    if (!isAdmin() && !isSuperAdmin()) {
        redirect('/user/dashboard_user.php');
    }
}

/**
 * Obtener usuario actual
 *
 * @param mysqli $conn
 * @return array|null
 */
function getCurrentUser($conn) {
    if (!isLoggedIn()) {
        return null;
    }

    return fetchOne($conn, "SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']], 'i');
}

/**
 * Verificar permiso de usuario en un módulo
 *
 * @param mysqli $conn
 * @param int $userId
 * @param int $moduleId
 * @param string $permission (view, create, edit, delete)
 * @return bool
 */
function hasPermission($conn, $userId, $moduleId, $permission = 'view') {
    if (isSuperAdmin()) {
        return true;
    }

    $permissionColumn = 'can_' . $permission;

    $sql = "SELECT $permissionColumn FROM user_permissions
            WHERE user_id = ? AND module_id = ? LIMIT 1";

    $result = fetchOne($conn, $sql, [$userId, $moduleId], 'ii');

    return $result && $result[$permissionColumn] == 1;
}

// ============================================
// FUNCIONES DE NAVEGACIÓN
// ============================================

/**
 * Redirigir a una URL
 *
 * @param string $url
 */
if (!function_exists('redirect')) {
    function redirect($url) {
        header("Location: $url");
        exit();
    }
}

/**
 * Obtener URL actual
 *
 * @return string
 */
function getCurrentURL() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $uri = $_SERVER['REQUEST_URI'];

    return $protocol . '://' . $host . $uri;
}

/**
 * Obtener URL base
 *
 * @return string
 */
function getBaseURL() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];

    return $protocol . '://' . $host;
}

// ============================================
// FUNCIONES DE MENSAJES Y NOTIFICACIONES
// ============================================

/**
 * Establecer mensaje flash
 *
 * @param string $message
 * @param string $type (success, error, warning, info)
 */
function setFlashMessage($message, $type = 'info') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

/**
 * Obtener y limpiar mensaje flash
 *
 * @return array|null
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $flash = [
            'message' => $_SESSION['flash_message'],
            'type' => $_SESSION['flash_type'] ?? 'info'
        ];

        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);

        return $flash;
    }

    return null;
}

/**
 * Mostrar mensaje flash (HTML)
 *
 * @return string
 */
function displayFlashMessage() {
    $flash = getFlashMessage();

    if (!$flash) {
        return '';
    }

    $typeClass = [
        'success' => 'alert-success',
        'error' => 'alert-danger',
        'warning' => 'alert-warning',
        'info' => 'alert-info'
    ];

    $class = $typeClass[$flash['type']] ?? 'alert-info';

    return '<div class="alert ' . $class . ' alert-dismissible fade show" role="alert">
                ' . htmlspecialchars($flash['message']) . '
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>';
}

/**
 * Crear notificación para usuario
 *
 * @param mysqli $conn
 * @param int $userId
 * @param string $type
 * @param string $title
 * @param string $message
 * @param string|null $link
 * @return int|false
 */
if (!function_exists('createNotification')) {
    function createNotification($conn, $userId, $type, $title, $message, $link = null) {
        return dbInsert($conn, 'notificaciones', [
            'usuario_id' => $userId,
            'tipo' => $type,
            'titulo' => $title,
            'mensaje' => $message,
            'enlace' => $link,
            'leida' => 0,
            'fecha_creacion' => date('Y-m-d H:i:s')
        ]);
    }
}

/**
 * Obtener notificaciones no leídas
 *
 * @param mysqli $conn
 * @param int $userId
 * @return array
 */
function getUnreadNotifications($conn, $userId) {
    return fetchAll($conn,
        "SELECT * FROM notificaciones
         WHERE usuario_id = ? AND leida = 0
         ORDER BY fecha_creacion DESC",
        [$userId], 'i'
    );
}

/**
 * Marcar notificación como leída
 *
 * @param mysqli $conn
 * @param int $notificationId
 * @return int
 */
function markNotificationAsRead($conn, $notificationId) {
    return dbUpdate($conn, 'notificaciones',
        ['leida' => 1],
        'id = ?',
        [$notificationId]
    );
}

// ============================================
// FUNCIONES DE LOGGING Y AUDITORÍA
// ============================================

/**
 * Log de auditoría
 *
 * @param mysqli $conn
 * @param string $action
 * @param string|null $table
 * @param int|null $recordId
 * @param string|null $oldValues
 * @param string|null $newValues
 * @param string|null $description
 * @return int|false
 */
if (!function_exists('logAuditoria')) {
    function logAuditoria($conn, $action, $table = null, $recordId = null, $oldValues = null, $newValues = null, $description = null) {
        $userId = $_SESSION['user_id'] ?? null;
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $modulo = $_SESSION['modulo_actual'] ?? null;

        return dbInsert($conn, 'logs_auditoria', [
            'usuario_id' => $userId,
            'accion' => $action,
            'tabla' => $table,
            'registro_id' => $recordId,
            'valores_anteriores' => $oldValues,
            'valores_nuevos' => $newValues,
            'modulo' => $modulo,
            'descripcion' => $description,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'fecha_hora' => date('Y-m-d H:i:s')
        ]);
    }
}

/**
 * Log de errores personalizado
 *
 * @param string $message
 * @param string $level (error, warning, info)
 */
function logError($message, $level = 'error') {
    $logFile = __DIR__ . '/../logs/error.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] [$level] $message\n";

    // Crear directorio de logs si no existe
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }

    @error_log($logMessage, 3, $logFile);

    // También log en error_log de PHP
    error_log("CONECTA ERP [$level]: $message");
}

// ============================================
// FUNCIONES DE ARCHIVOS Y UPLOADS
// ============================================

/**
 * Procesar upload de archivo
 *
 * @param array $file $_FILES['field']
 * @param string $uploadDir
 * @param array $allowedTypes
 * @param int $maxSize En bytes
 * @return array ['success' => bool, 'filename' => string|null, 'error' => string|null]
 */
function processFileUpload($file, $uploadDir, $allowedTypes = [], $maxSize = 10485760) {
    // Verificar si hay errores
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return [
            'success' => false,
            'filename' => null,
            'error' => 'Error al subir el archivo'
        ];
    }

    // Verificar tamaño
    if ($file['size'] > $maxSize) {
        return [
            'success' => false,
            'filename' => null,
            'error' => 'El archivo excede el tamaño máximo permitido (' . formatBytes($maxSize) . ')'
        ];
    }

    // Verificar tipo de archivo
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!empty($allowedTypes) && !in_array($fileExtension, $allowedTypes)) {
        return [
            'success' => false,
            'filename' => null,
            'error' => 'Tipo de archivo no permitido'
        ];
    }

    // Generar nombre único
    $newFilename = uniqid() . '_' . sanitizeFilename($file['name']);
    $destination = $uploadDir . '/' . $newFilename;

    // Crear directorio si no existe
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0755, true);
    }

    // Mover archivo
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return [
            'success' => true,
            'filename' => $newFilename,
            'error' => null
        ];
    }

    return [
        'success' => false,
        'filename' => null,
        'error' => 'No se pudo guardar el archivo'
    ];
}

/**
 * Eliminar archivo de forma segura
 *
 * @param string $filepath
 * @return bool
 */
function deleteFile($filepath) {
    if (file_exists($filepath) && is_file($filepath)) {
        return @unlink($filepath);
    }
    return false;
}

// ============================================
// FUNCIONES DE UTILIDADES GENERALES
// ============================================

/**
 * Generar UUID v4
 *
 * @return string
 */
function generateUUID() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

/**
 * Truncar texto
 *
 * @param string $text
 * @param int $length
 * @param string $suffix
 * @return string
 */
function truncateText($text, $length = 100, $suffix = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }

    return substr($text, 0, $length) . $suffix;
}

/**
 * Slugify texto (convertir a URL-friendly)
 *
 * @param string $text
 * @return string
 */
function slugify($text) {
    // Reemplazar caracteres especiales
    $text = str_replace(['á', 'é', 'í', 'ó', 'ú', 'ñ'], ['a', 'e', 'i', 'o', 'u', 'n'], strtolower($text));

    // Eliminar todo excepto letras, números y espacios
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);

    // Reemplazar espacios y múltiples guiones con un solo guión
    $text = preg_replace('/[\s-]+/', '-', $text);

    return trim($text, '-');
}

/**
 * Generar código aleatorio
 *
 * @param int $length
 * @param bool $uppercase
 * @return string
 */
function generateCode($length = 8, $uppercase = true) {
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $code = '';

    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[random_int(0, strlen($characters) - 1)];
    }

    return $uppercase ? strtoupper($code) : $code;
}

/**
 * Calcular días restantes de trial
 *
 * @param string $endDate
 * @return int
 */
if (!function_exists('getDiasRestantesTrial')) {
    function getDiasRestantesTrial($endDate) {
        if (empty($endDate)) return 0;

        $end = strtotime($endDate);
        $now = time();
        $diff = $end - $now;

        return max(0, ceil($diff / 86400));
    }
}

/**
 * Verificar si está en trial
 *
 * @param mysqli $conn
 * @param int $userId
 * @return bool
 */
function isInTrial($conn, $userId) {
    $user = fetchOne($conn, "SELECT status, trial_ends_at FROM users WHERE id = ?", [$userId], 'i');

    if (!$user) {
        return false;
    }

    return $user['status'] === 'trial' && strtotime($user['trial_ends_at']) > time();
}

/**
 * Obtener IP del cliente
 *
 * @return string
 */
function getClientIP() {
    $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];

    foreach ($ipKeys as $key) {
        if (isset($_SERVER[$key]) && filter_var($_SERVER[$key], FILTER_VALIDATE_IP)) {
            return $_SERVER[$key];
        }
    }

    return 'unknown';
}

/**
 * Respuesta JSON
 *
 * @param mixed $data
 * @param int $statusCode
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * Debugear variable (solo en desarrollo)
 *
 * @param mixed $var
 * @param bool $die
 */
function debug($var, $die = false) {
    if (CONECTA_ENVIRONMENT !== 'production') {
        echo '<pre>';
        var_dump($var);
        echo '</pre>';

        if ($die) {
            die();
        }
    }
}

/**
 * Generar breadcrumb
 *
 * @param array $items [['title' => 'Home', 'url' => '/'], ...]
 * @return string
 */
function generateBreadcrumb($items) {
    $html = '<nav aria-label="breadcrumb"><ol class="breadcrumb">';

    $count = count($items);
    foreach ($items as $index => $item) {
        $isLast = ($index === $count - 1);

        if ($isLast) {
            $html .= '<li class="breadcrumb-item active" aria-current="page">' . htmlspecialchars($item['title']) . '</li>';
        } else {
            $html .= '<li class="breadcrumb-item"><a href="' . htmlspecialchars($item['url']) . '">' . htmlspecialchars($item['title']) . '</a></li>';
        }
    }

    $html .= '</ol></nav>';

    return $html;
}

// ============================================
// FUNCIONES DE CONFIGURACIÓN
// ============================================

/**
 * Obtener valor de configuración
 *
 * @param mysqli $conn
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
if (!function_exists('getConfig')) {
    function getConfig($conn, $key, $default = null) {
        $result = fetchOne($conn, "SELECT valor FROM configuracion WHERE clave = ?", [$key], 's');
        return $result ? $result['valor'] : $default;
    }
}

/**
 * Establecer valor de configuración
 *
 * @param mysqli $conn
 * @param string $key
 * @param mixed $value
 * @return bool
 */
if (!function_exists('setConfig')) {
    function setConfig($conn, $key, $value) {
        $exists = recordExists($conn, 'configuracion', 'clave = ?', [$key]);

        if ($exists) {
            return dbUpdate($conn, 'configuracion', ['valor' => $value], 'clave = ?', [$key]) > 0;
        } else {
            return dbInsert($conn, 'configuracion', ['clave' => $key, 'valor' => $value]) > 0;
        }
    }
}

// ============================================
// AUTO-INICIALIZACIÓN
// ============================================

// Iniciar sesión segura automáticamente
if (session_status() === PHP_SESSION_NONE) {
    initSecureSession();
}

// Cargar archivos de includes adicionales si existen
$includesPath = __DIR__;

// Cargar RUT validator si existe
if (file_exists($includesPath . '/rut_validator.php')) {
    require_once $includesPath . '/rut_validator.php';
}

// Cargar i18n si existe
if (file_exists($includesPath . '/i18n.php')) {
    require_once $includesPath . '/i18n.php';
}

// ============================================
// FIN DEL ARCHIVO
// ============================================
?>
