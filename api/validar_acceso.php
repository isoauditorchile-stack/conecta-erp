<?php
/**
 * ===============================================
 * CONECTA ERP - API REST: VALIDAR ACCESO A MÓDULO
 * ===============================================
 *
 * Endpoint para validar si un usuario tiene acceso a un módulo
 * Útil para AJAX y validaciones desde JavaScript
 *
 * USO:
 * GET /api/validar_acceso.php?modulo=ventas
 *
 * RESPONSE:
 * {
 *   "success": true,
 *   "tiene_acceso": true,
 *   "mensaje": "Acceso permitido",
 *   "datos": {
 *     "plan": "Profesional",
 *     "modulo": "Ventas",
 *     "acceso_completo": true,
 *     "limite_registros": null,
 *     "es_trial": false,
 *     "dias_restantes": null
 *   }
 * }
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');

session_start();
require_once '../includes/config.php';

// Inicializar response
$response = [
    'success' => false,
    'tiene_acceso' => false,
    'mensaje' => '',
    'datos' => []
];

// Verificar sesión activa
if (!isset($_SESSION['user_id']) || !isset($_SESSION['empresa_id'])) {
    $response['mensaje'] = 'Sesión no válida o expirada';
    $response['datos']['accion_requerida'] = 'login';
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit();
}

// Obtener parámetros
$modulo_slug = $_GET['modulo'] ?? '';
$empresa_id = $_SESSION['empresa_id'];
$usuario_id = $_SESSION['user_id'];

// Validar parámetro obligatorio
if (empty($modulo_slug)) {
    $response['mensaje'] = 'Parámetro "modulo" es requerido';
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit();
}

// 1. Obtener información del plan activo
$stmt = $conn->prepare("
    SELECT
        s.plan_id,
        s.estado,
        s.es_trial,
        s.fecha_fin,
        p.nombre as plan_nombre,
        p.max_usuarios
    FROM suscripciones s
    INNER JOIN planes p ON s.plan_id = p.id
    WHERE s.empresa_id = ?
      AND s.estado IN ('trial', 'activa')
      AND (s.fecha_fin IS NULL OR s.fecha_fin >= NOW())
    ORDER BY s.id DESC
    LIMIT 1
");

$stmt->bind_param("i", $empresa_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $response['mensaje'] = 'No hay suscripción activa o ha expirado';
    $response['datos']['accion_requerida'] = 'renovar_suscripcion';
    $stmt->close();
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit();
}

$suscripcion = $result->fetch_assoc();
$plan_id = $suscripcion['plan_id'];
$plan_nombre = $suscripcion['plan_nombre'];
$es_trial = $suscripcion['es_trial'];
$fecha_fin = $suscripcion['fecha_fin'];
$stmt->close();

// 2. Verificar si el módulo existe
$stmt = $conn->prepare("
    SELECT id, nombre, requiere_licencia
    FROM modulos
    WHERE slug = ? AND activo = 1
    LIMIT 1
");

$stmt->bind_param("s", $modulo_slug);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $response['mensaje'] = 'Módulo no encontrado';
    $stmt->close();
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit();
}

$modulo = $result->fetch_assoc();
$modulo_id = $modulo['id'];
$modulo_nombre = $modulo['nombre'];
$requiere_licencia = $modulo['requiere_licencia'];
$stmt->close();

// 3. Si el módulo no requiere licencia, dar acceso automático
if ($requiere_licencia == 0) {
    $response['success'] = true;
    $response['tiene_acceso'] = true;
    $response['mensaje'] = 'Acceso permitido (módulo libre)';
    $response['datos'] = [
        'plan' => $plan_nombre,
        'modulo' => $modulo_nombre,
        'acceso_completo' => true,
        'limite_registros' => null,
        'es_trial' => $es_trial == 1,
        'dias_restantes' => null
    ];
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit();
}

// 4. Verificar si el plan incluye este módulo
$stmt = $conn->prepare("
    SELECT acceso_completo, limite_registros
    FROM plan_modulos
    WHERE plan_id = ?
      AND modulo_id = ?
    LIMIT 1
");

$stmt->bind_param("ii", $plan_id, $modulo_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $response['mensaje'] = 'Módulo no incluido en tu plan actual';
    $response['datos']['plan_actual'] = $plan_nombre;
    $response['datos']['modulo_solicitado'] = $modulo_nombre;
    $response['datos']['accion_requerida'] = 'upgrade_plan';
    $stmt->close();
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit();
}

$acceso = $result->fetch_assoc();
$acceso_completo = $acceso['acceso_completo'];
$limite_registros = $acceso['limite_registros'];
$stmt->close();

// 5. Calcular días restantes si es trial
$dias_restantes = null;
if ($es_trial == 1 && $fecha_fin) {
    $fecha_fin_ts = strtotime($fecha_fin);
    $hoy_ts = time();
    $dias_restantes = ceil(($fecha_fin_ts - $hoy_ts) / 86400);
    $dias_restantes = max(0, $dias_restantes);
}

// 6. Acceso PERMITIDO - construir response exitoso
$response['success'] = true;
$response['tiene_acceso'] = true;
$response['mensaje'] = 'Acceso permitido';
$response['datos'] = [
    'plan' => $plan_nombre,
    'modulo' => $modulo_nombre,
    'acceso_completo' => $acceso_completo == 1,
    'limite_registros' => $limite_registros,
    'es_trial' => $es_trial == 1,
    'dias_restantes' => $dias_restantes
];

// Advertencia si está en trial y faltan pocos días
if ($es_trial == 1 && $dias_restantes !== null && $dias_restantes <= 3) {
    $response['advertencia'] = "Tu período de prueba finaliza en {$dias_restantes} día(s)";
}

// Advertencia si el acceso es limitado
if ($acceso_completo == 0) {
    $response['advertencia_acceso'] = "Tienes acceso limitado a este módulo. Mejora tu plan para acceso completo.";
    if ($limite_registros !== null) {
        $response['advertencia_acceso'] .= " Límite: {$limite_registros} registros.";
    }
}

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
