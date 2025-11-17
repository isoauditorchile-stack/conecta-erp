<?php
/**
 * ===============================================
 * API REST - ESTADO DE FOLIOS EN TIEMPO REAL
 * ===============================================
 * Endpoint para consultar el estado de folios de todos los documentos
 *
 * USO:
 * GET /modulos/facturacion/api/estado_folios.php
 * GET /modulos/facturacion/api/estado_folios.php?tipo_documento=33
 * GET /modulos/facturacion/api/estado_folios.php?alerta=critico
 *
 * RESPONSE:
 * {
 *   "success": true,
 *   "data": [...],
 *   "resumen": {...},
 *   "alertas": [...]
 * }
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

session_start();
require_once '../../../includes/config.php';

// Inicializar response
$response = [
    'success' => false,
    'data' => [],
    'resumen' => [],
    'alertas' => [],
    'mensaje' => ''
];

// Verificar sesión activa
if (!isset($_SESSION['user_id']) || !isset($_SESSION['empresa_id'])) {
    $response['mensaje'] = 'Sesión no válida';
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}

$empresa_id = $_SESSION['empresa_id'];

// Parámetros opcionales
$tipo_documento = $_GET['tipo_documento'] ?? null;
$nivel_alerta = $_GET['alerta'] ?? null; // critico, bajo, agotado

// Construir query base
$where_clauses = ["f.empresa_id = ?"];
$params = [$empresa_id];
$param_types = "i";

if ($tipo_documento) {
    $where_clauses[] = "t.codigo = ?";
    $params[] = $tipo_documento;
    $param_types .= "i";
}

$where_sql = implode(' AND ', $where_clauses);

// Query principal
$query = "
    SELECT
        t.codigo,
        t.nombre,
        t.categoria,
        t.fase_implementacion,
        COALESCE(SUM(f.folio_hasta - f.folio_actual), 0) as folios_disponibles,
        COALESCE(SUM(f.folio_hasta - f.folio_desde + 1), 0) as folios_totales,
        COUNT(f.id) as cantidad_cafs,
        MIN(f.folio_desde) as primer_folio,
        MAX(f.folio_hasta) as ultimo_folio,
        MIN(f.folio_actual) as primer_folio_usado,
        MAX(f.fecha_autorizacion) as ultima_carga,
        GROUP_CONCAT(f.estado) as estados_cafs,
        CASE
            WHEN SUM(f.folio_hasta - f.folio_actual) = 0 THEN 'agotado'
            WHEN SUM(f.folio_hasta - f.folio_actual) < 50 THEN 'critico'
            WHEN SUM(f.folio_hasta - f.folio_actual) < 100 THEN 'bajo'
            ELSE 'normal'
        END as estado_alerta,
        CASE
            WHEN SUM(f.folio_hasta - f.folio_actual) > 0
            THEN ROUND((SUM(f.folio_hasta - f.folio_actual) / SUM(f.folio_hasta - f.folio_desde + 1)) * 100, 1)
            ELSE 0
        END as porcentaje_disponible
    FROM tipos_documentos_sii t
    LEFT JOIN folios_caf f ON t.codigo = f.tipo_documento AND $where_sql
    WHERE t.electronico = 1
      AND t.activo = 1
      AND t.requiere_folio = 1
    GROUP BY t.codigo, t.nombre, t.categoria, t.fase_implementacion
";

// Si se especificó nivel de alerta, filtrar en PHP después
$stmt = $conn->prepare($query);
$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
$alertas = [];

// Estadísticas de consumo
function obtenerEstadisticasConsumo($tipo_doc, $empresa_id, $conn) {
    $query = "
        SELECT
            COUNT(*) as documentos_emitidos,
            DATEDIFF(NOW(), MIN(fecha_emision)) as dias_operando
        FROM documentos_tributarios
        WHERE empresa_id = ?
          AND tipo_documento = ?
          AND fecha_emision >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $empresa_id, $tipo_doc);
    $stmt->execute();
    $stats = $stmt->get_result()->fetch_assoc();

    $tasa_diaria = 0;
    if ($stats['documentos_emitidos'] > 0 && $stats['dias_operando'] > 0) {
        $tasa_diaria = $stats['documentos_emitidos'] / min($stats['dias_operando'], 30);
    }

    return [
        'documentos_30_dias' => $stats['documentos_emitidos'],
        'tasa_diaria' => round($tasa_diaria, 2),
        'tasa_mensual' => round($tasa_diaria * 30, 0)
    ];
}

// Procesar resultados
while ($row = $result->fetch_assoc()) {
    // Obtener estadísticas de consumo
    $consumo = obtenerEstadisticasConsumo($row['codigo'], $empresa_id, $conn);

    // Calcular días estimados
    $dias_estimados = null;
    if ($consumo['tasa_diaria'] > 0 && $row['folios_disponibles'] > 0) {
        $dias_estimados = floor($row['folios_disponibles'] / $consumo['tasa_diaria']);
    }

    $item = [
        'codigo' => $row['codigo'],
        'nombre' => $row['nombre'],
        'categoria' => $row['categoria'],
        'fase' => $row['fase_implementacion'],
        'folios_disponibles' => (int)$row['folios_disponibles'],
        'folios_totales' => (int)$row['folios_totales'],
        'porcentaje_disponible' => (float)$row['porcentaje_disponible'],
        'cantidad_cafs' => (int)$row['cantidad_cafs'],
        'rango_folios' => [
            'desde' => $row['primer_folio'],
            'hasta' => $row['ultimo_folio']
        ],
        'estado' => $row['estado_alerta'],
        'ultima_carga' => $row['ultima_carga'],
        'consumo' => $consumo,
        'estimacion' => [
            'dias_disponibles' => $dias_estimados,
            'fecha_agotamiento' => $dias_estimados ? date('Y-m-d', strtotime("+{$dias_estimados} days")) : null,
            'requiere_atencion' => $dias_estimados !== null && $dias_estimados <= 15
        ]
    ];

    // Filtrar por nivel de alerta si se especificó
    if ($nivel_alerta && $row['estado_alerta'] !== $nivel_alerta) {
        continue;
    }

    $data[] = $item;

    // Generar alertas automáticas
    if ($row['estado_alerta'] === 'agotado') {
        $alertas[] = [
            'tipo' => 'error',
            'codigo_documento' => $row['codigo'],
            'nombre_documento' => $row['nombre'],
            'mensaje' => "Sin folios disponibles para {$row['nombre']}",
            'accion_recomendada' => 'Solicitar folios al SII urgentemente',
            'prioridad' => 'alta'
        ];
    } elseif ($row['estado_alerta'] === 'critico') {
        $alertas[] = [
            'tipo' => 'warning',
            'codigo_documento' => $row['codigo'],
            'nombre_documento' => $row['nombre'],
            'mensaje' => "Quedan solo {$row['folios_disponibles']} folios para {$row['nombre']}",
            'accion_recomendada' => 'Solicitar más folios al SII en los próximos días',
            'prioridad' => 'alta'
        ];
    } elseif ($dias_estimados !== null && $dias_estimados <= 7) {
        $alertas[] = [
            'tipo' => 'info',
            'codigo_documento' => $row['codigo'],
            'nombre_documento' => $row['nombre'],
            'mensaje' => "Quedan aproximadamente {$dias_estimados} días de folios para {$row['nombre']}",
            'accion_recomendada' => 'Planificar solicitud de folios',
            'prioridad' => 'media'
        ];
    }
}

// Calcular resumen
$resumen = [
    'total_tipos_documento' => count($data),
    'total_folios_disponibles' => array_sum(array_column($data, 'folios_disponibles')),
    'total_cafs_activos' => array_sum(array_column($data, 'cantidad_cafs')),
    'tipos_agotados' => count(array_filter($data, fn($item) => $item['estado'] === 'agotado')),
    'tipos_criticos' => count(array_filter($data, fn($item) => $item['estado'] === 'critico')),
    'tipos_bajos' => count(array_filter($data, fn($item) => $item['estado'] === 'bajo')),
    'tipos_normales' => count(array_filter($data, fn($item) => $item['estado'] === 'normal')),
    'total_alertas' => count($alertas)
];

// Construir response exitoso
$response['success'] = true;
$response['data'] = $data;
$response['resumen'] = $resumen;
$response['alertas'] = $alertas;
$response['mensaje'] = 'Estado de folios obtenido exitosamente';
$response['timestamp'] = date('Y-m-d H:i:s');

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
