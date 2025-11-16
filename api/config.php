<?php
/**
 * API REST - CONFIGURACIÓN
 * Sistema de API RESTful para CONECTA ERP
 * Seguridad, autenticación y utilidades
 */

// Headers CORS y JSON
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');

// Manejar preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../includes/config.php';

// =====================================================
// CONSTANTES API
// =====================================================
define('API_VERSION', 'v1');
define('API_RATE_LIMIT', 1000); // Requests por hora
define('API_TOKEN_EXPIRY', 3600); // 1 hora

// =====================================================
// FUNCIÓN: Validar API Key
// =====================================================
function validarAPIKey() {
    global $conn;

    $headers = getallheaders();
    $api_key = $headers['X-API-Key'] ?? $_GET['api_key'] ?? null;

    if (!$api_key) {
        enviarError(401, 'API Key requerida', 'MISSING_API_KEY');
    }

    // Validar en base de datos
    $stmt = $conn->prepare("SELECT e.id as empresa_id, e.nombre, ak.permisos
        FROM api_keys ak
        INNER JOIN empresas e ON ak.empresa_id = e.id
        WHERE ak.api_key = ? AND ak.activa = 1 AND ak.fecha_expiracion > NOW()");
    $stmt->bind_param("s", $api_key);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$result) {
        enviarError(401, 'API Key inválida o expirada', 'INVALID_API_KEY');
    }

    // Verificar rate limiting
    verificarRateLimit($api_key);

    // Registrar uso
    registrarUsoAPI($api_key);

    return $result;
}

// =====================================================
// FUNCIÓN: Verificar Rate Limiting
// =====================================================
function verificarRateLimit($api_key) {
    global $conn;

    $stmt = $conn->prepare("SELECT COUNT(*) as requests
        FROM api_logs
        WHERE api_key = ? AND fecha_creacion > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    $stmt->bind_param("s", $api_key);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($result['requests'] >= API_RATE_LIMIT) {
        enviarError(429, 'Rate limit excedido. Máximo ' . API_RATE_LIMIT . ' requests por hora', 'RATE_LIMIT_EXCEEDED');
    }
}

// =====================================================
// FUNCIÓN: Registrar uso de API
// =====================================================
function registrarUsoAPI($api_key) {
    global $conn;

    $metodo = $_SERVER['REQUEST_METHOD'];
    $endpoint = $_SERVER['REQUEST_URI'];
    $ip = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

    $stmt = $conn->prepare("INSERT INTO api_logs
        (api_key, metodo, endpoint, ip_cliente, user_agent)
        VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $api_key, $metodo, $endpoint, $ip, $user_agent);
    $stmt->execute();
    $stmt->close();
}

// =====================================================
// FUNCIÓN: Enviar respuesta exitosa
// =====================================================
function enviarExito($datos, $mensaje = 'Operación exitosa', $codigo = 200) {
    http_response_code($codigo);
    echo json_encode([
        'success' => true,
        'message' => $mensaje,
        'data' => $datos,
        'timestamp' => date('c')
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}

// =====================================================
// FUNCIÓN: Enviar error
// =====================================================
function enviarError($codigo, $mensaje, $error_code = null) {
    http_response_code($codigo);
    echo json_encode([
        'success' => false,
        'error' => $mensaje,
        'error_code' => $error_code,
        'timestamp' => date('c')
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}

// =====================================================
// FUNCIÓN: Validar parámetros requeridos
// =====================================================
function validarParametros($parametros, $requeridos) {
    $faltantes = [];
    foreach ($requeridos as $campo) {
        if (!isset($parametros[$campo]) || $parametros[$campo] === '') {
            $faltantes[] = $campo;
        }
    }

    if (!empty($faltantes)) {
        enviarError(400, 'Parámetros faltantes: ' . implode(', ', $faltantes), 'MISSING_PARAMETERS');
    }
}

// =====================================================
// FUNCIÓN: Obtener datos POST/PUT
// =====================================================
function obtenerDatosInput() {
    $input = file_get_contents('php://input');
    $datos = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        enviarError(400, 'JSON inválido', 'INVALID_JSON');
    }

    return $datos ?: [];
}

// =====================================================
// FUNCIÓN: Paginación
// =====================================================
function obtenerParametrosPaginacion() {
    $pagina = max(1, intval($_GET['page'] ?? 1));
    $por_pagina = min(100, max(1, intval($_GET['per_page'] ?? 50)));
    $offset = ($pagina - 1) * $por_pagina;

    return [
        'page' => $pagina,
        'per_page' => $por_pagina,
        'offset' => $offset
    ];
}

// =====================================================
// FUNCIÓN: Construir respuesta paginada
// =====================================================
function respuestaPaginada($datos, $total, $paginacion) {
    return [
        'items' => $datos,
        'pagination' => [
            'total' => $total,
            'page' => $paginacion['page'],
            'per_page' => $paginacion['per_page'],
            'total_pages' => ceil($total / $paginacion['per_page'])
        ]
    ];
}
