<?php
/**
 * ===============================================
 * MÓDULO DE FACTURACIÓN - GESTIÓN DE FOLIOS SII
 * ===============================================
 * Dashboard completo de folios CAF para todos los documentos tributarios
 * - Visualización de folios disponibles por tipo de documento
 * - Alertas de folios bajos
 * - Indicador de días para solicitar más folios
 * - Carga de archivos CAF
 */

session_start();
require_once '../../includes/config.php';
require_once '../../includes/middleware_acceso.php';

// Validar acceso al módulo de ventas (facturación está dentro de ventas)
requiereModulo('ventas');

$empresa_id = $_SESSION['empresa_id'];
$usuario_id = $_SESSION['user_id'];

// Obtener estado de folios por tipo de documento
$query_folios = "
    SELECT
        t.codigo,
        t.nombre,
        t.categoria,
        t.fase_implementacion,
        COALESCE(SUM(f.folio_hasta - f.folio_actual), 0) as folios_disponibles,
        COUNT(f.id) as cantidad_cafs,
        MIN(f.folio_actual) as primer_folio_usado,
        MAX(f.folio_hasta) as ultimo_folio_disponible,
        MAX(f.fecha_autorizacion) as ultima_carga,
        CASE
            WHEN SUM(f.folio_hasta - f.folio_actual) = 0 THEN 'agotado'
            WHEN SUM(f.folio_hasta - f.folio_actual) < 50 THEN 'critico'
            WHEN SUM(f.folio_hasta - f.folio_actual) < 100 THEN 'bajo'
            ELSE 'normal'
        END as estado_alerta
    FROM tipos_documentos_sii t
    LEFT JOIN folios_caf f ON t.codigo = f.tipo_documento
        AND f.empresa_id = ?
        AND f.estado = 'activo'
    WHERE t.electronico = 1
      AND t.activo = 1
      AND t.requiere_folio = 1
    GROUP BY t.codigo, t.nombre, t.categoria, t.fase_implementacion
    ORDER BY
        CASE
            WHEN SUM(f.folio_hasta - f.folio_actual) = 0 THEN 1
            WHEN SUM(f.folio_hasta - f.folio_actual) < 50 THEN 2
            WHEN SUM(f.folio_hasta - f.folio_actual) < 100 THEN 3
            ELSE 4
        END,
        t.fase_implementacion ASC,
        t.codigo ASC
";

$stmt = $conn->prepare($query_folios);
$stmt->bind_param("i", $empresa_id);
$stmt->execute();
$folios_result = $stmt->get_result();

// Calcular estadísticas generales
$stats = [
    'total_tipos' => 0,
    'con_folios' => 0,
    'sin_folios' => 0,
    'criticos' => 0,
    'bajos' => 0,
    'total_folios_disponibles' => 0
];

$folios_data = [];
while ($row = $folios_result->fetch_assoc()) {
    $folios_data[] = $row;
    $stats['total_tipos']++;

    if ($row['folios_disponibles'] > 0) {
        $stats['con_folios']++;
        $stats['total_folios_disponibles'] += $row['folios_disponibles'];
    } else {
        $stats['sin_folios']++;
    }

    if ($row['estado_alerta'] == 'critico' || $row['estado_alerta'] == 'agotado') {
        $stats['criticos']++;
    } elseif ($row['estado_alerta'] == 'bajo') {
        $stats['bajos']++;
    }
}

// Calcular días promedio de consumo para estimación
function calcularDiasParaRenovar($tipo_documento, $folios_disponibles, $conn, $empresa_id) {
    // Obtener cantidad de documentos emitidos en últimos 30 días
    $query = "
        SELECT COUNT(*) as total
        FROM documentos_tributarios
        WHERE empresa_id = ?
          AND tipo_documento = ?
          AND fecha_emision >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $empresa_id, $tipo_documento);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $emitidos_30_dias = $result['total'];

    if ($emitidos_30_dias == 0) {
        return ['dias_estimados' => null, 'tasa_diaria' => 0, 'mensaje' => 'Sin movimiento'];
    }

    $tasa_diaria = $emitidos_30_dias / 30;

    if ($tasa_diaria > 0) {
        $dias_estimados = floor($folios_disponibles / $tasa_diaria);
        return [
            'dias_estimados' => $dias_estimados,
            'tasa_diaria' => round($tasa_diaria, 1),
            'mensaje' => $dias_estimados . ' días aprox.'
        ];
    }

    return ['dias_estimados' => null, 'tasa_diaria' => 0, 'mensaje' => 'Uso bajo'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Folios CAF - CONECTA ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            margin-bottom: 30px;
            border-radius: 0 0 20px 20px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 10px 0;
        }

        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .stat-card.blue .stat-value { color: #667eea; }
        .stat-card.green .stat-value { color: #28a745; }
        .stat-card.red .stat-value { color: #dc3545; }
        .stat-card.orange .stat-value { color: #ffc107; }

        .folios-table {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .estado-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-block;
        }

        .estado-normal {
            background: #d4edda;
            color: #155724;
        }

        .estado-bajo {
            background: #fff3cd;
            color: #856404;
        }

        .estado-critico {
            background: #f8d7da;
            color: #721c24;
        }

        .estado-agotado {
            background: #343a40;
            color: white;
        }

        .progress-folios {
            height: 25px;
            border-radius: 10px;
            background: #e9ecef;
        }

        .progress-bar-folios {
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.85rem;
            border-radius: 10px;
            transition: width 0.3s ease;
        }

        .alert-banner {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
            color: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(238, 90, 111, 0.3);
        }

        .alert-banner h4 {
            margin: 0;
            font-weight: 700;
        }

        .btn-cargar-caf {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-cargar-caf:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            color: white;
        }

        .fase-badge {
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-left: 10px;
        }

        .fase-1 { background: #667eea; color: white; }
        .fase-2 { background: #28a745; color: white; }
        .fase-3 { background: #17a2b8; color: white; }
        .fase-4 { background: #6c757d; color: white; }

        .tooltip-custom {
            position: relative;
            display: inline-block;
            cursor: help;
        }

        .days-indicator {
            font-weight: 600;
            padding: 5px 10px;
            border-radius: 8px;
            font-size: 0.85rem;
        }

        .days-indicator.urgent {
            background: #f8d7da;
            color: #721c24;
        }

        .days-indicator.warning {
            background: #fff3cd;
            color: #856404;
        }

        .days-indicator.good {
            background: #d4edda;
            color: #155724;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <h1><i class="fas fa-receipt"></i> Gestión de Folios CAF</h1>
            <p class="mb-0">Administración de folios autorizados por el SII para facturación electrónica</p>
        </div>
    </div>

    <div class="container">
        <!-- Alertas de Folios Críticos -->
        <?php if ($stats['criticos'] > 0): ?>
        <div class="alert-banner">
            <h4><i class="fas fa-exclamation-triangle"></i> ¡ATENCIÓN! Folios Críticos o Agotados</h4>
            <p class="mb-0">
                Tienes <strong><?php echo $stats['criticos']; ?> tipo(s) de documento</strong>
                con folios agotados o en nivel crítico. Debes solicitar folios urgentemente al SII.
            </p>
        </div>
        <?php elseif ($stats['bajos'] > 0): ?>
        <div class="alert alert-warning">
            <h5><i class="fas fa-info-circle"></i> Folios en Nivel Bajo</h5>
            <p class="mb-0">
                Tienes <strong><?php echo $stats['bajos']; ?> tipo(s) de documento</strong> con folios bajos.
                Considera solicitar más folios al SII próximamente.
            </p>
        </div>
        <?php endif; ?>

        <!-- Estadísticas Generales -->
        <div class="stats-grid">
            <div class="stat-card blue">
                <i class="fas fa-file-invoice" style="font-size: 2rem; opacity: 0.3;"></i>
                <div class="stat-value"><?php echo $stats['total_tipos']; ?></div>
                <div class="stat-label">Tipos de Documentos</div>
            </div>

            <div class="stat-card green">
                <i class="fas fa-check-circle" style="font-size: 2rem; opacity: 0.3;"></i>
                <div class="stat-value"><?php echo number_format($stats['total_folios_disponibles']); ?></div>
                <div class="stat-label">Folios Disponibles Total</div>
            </div>

            <div class="stat-card red">
                <i class="fas fa-exclamation-circle" style="font-size: 2rem; opacity: 0.3;"></i>
                <div class="stat-value"><?php echo $stats['criticos']; ?></div>
                <div class="stat-label">Tipos en Estado Crítico</div>
            </div>

            <div class="stat-card orange">
                <i class="fas fa-clock" style="font-size: 2rem; opacity: 0.3;"></i>
                <div class="stat-value"><?php echo $stats['sin_folios']; ?></div>
                <div class="stat-label">Tipos Sin Folios</div>
            </div>
        </div>

        <!-- Botón Cargar CAF -->
        <div class="text-end mb-3">
            <a href="cargar_caf.php" class="btn-cargar-caf">
                <i class="fas fa-upload"></i> Cargar Archivo CAF
            </a>
        </div>

        <!-- Tabla de Folios por Tipo de Documento -->
        <div class="folios-table">
            <h3 class="mb-4"><i class="fas fa-list"></i> Estado de Folios por Tipo de Documento</h3>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Tipo Documento</th>
                            <th>Código</th>
                            <th class="text-center">Folios Disponibles</th>
                            <th class="text-center">Estado</th>
                            <th class="text-center">CAFs Activos</th>
                            <th class="text-center">Días Estimados</th>
                            <th class="text-center">Última Carga</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($folios_data as $folio): ?>
                            <?php
                            $estimacion = calcularDiasParaRenovar(
                                $folio['codigo'],
                                $folio['folios_disponibles'],
                                $conn,
                                $empresa_id
                            );

                            // Determinar clase de días
                            $dias_class = 'good';
                            if ($estimacion['dias_estimados'] !== null) {
                                if ($estimacion['dias_estimados'] <= 7) {
                                    $dias_class = 'urgent';
                                } elseif ($estimacion['dias_estimados'] <= 15) {
                                    $dias_class = 'warning';
                                }
                            }
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($folio['nombre']); ?></strong>
                                    <?php if ($folio['fase_implementacion']): ?>
                                        <span class="fase-badge fase-<?php echo $folio['fase_implementacion']; ?>">
                                            Fase <?php echo $folio['fase_implementacion']; ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-secondary"><?php echo $folio['codigo']; ?></span>
                                </td>
                                <td class="text-center">
                                    <strong style="font-size: 1.2rem;">
                                        <?php echo number_format($folio['folios_disponibles']); ?>
                                    </strong>
                                    <?php if ($folio['folios_disponibles'] > 0): ?>
                                        <br>
                                        <small class="text-muted">
                                            Hasta folio: <?php echo number_format($folio['ultimo_folio_disponible']); ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="estado-badge estado-<?php echo $folio['estado_alerta']; ?>">
                                        <?php
                                        echo match($folio['estado_alerta']) {
                                            'normal' => 'Normal',
                                            'bajo' => 'Bajo',
                                            'critico' => 'Crítico',
                                            'agotado' => 'Agotado',
                                            default => 'Desconocido'
                                        };
                                        ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <?php echo $folio['cantidad_cafs']; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($estimacion['dias_estimados'] !== null): ?>
                                        <span class="days-indicator <?php echo $dias_class; ?>">
                                            <i class="fas fa-calendar-alt"></i>
                                            <?php echo $estimacion['dias_estimados']; ?> días
                                        </span>
                                        <br>
                                        <small class="text-muted">
                                            ~<?php echo $estimacion['tasa_diaria']; ?> doc/día
                                        </small>
                                    <?php else: ?>
                                        <span class="text-muted">
                                            <?php echo $estimacion['mensaje']; ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($folio['ultima_carga']): ?>
                                        <small>
                                            <?php echo date('d/m/Y', strtotime($folio['ultima_carga'])); ?>
                                        </small>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($folio['folios_disponibles'] < 100): ?>
                                        <a href="cargar_caf.php?tipo=<?php echo $folio['codigo']; ?>"
                                           class="btn btn-sm btn-primary"
                                           title="Cargar más folios">
                                            <i class="fas fa-plus"></i> CAF
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($folio['cantidad_cafs'] > 0): ?>
                                        <button class="btn btn-sm btn-info"
                                                onclick="verDetalle(<?php echo $folio['codigo']; ?>)"
                                                title="Ver detalle">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Información Útil -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5><i class="fas fa-info-circle"></i> ¿Cómo Solicitar Folios al SII?</h5>
                        <ol>
                            <li>Ingresa a <a href="https://www4.sii.cl/registrosc/" target="_blank">Portal SII</a></li>
                            <li>Login con RUT + Clave Tributaria</li>
                            <li>Ir a "Documentos Tributarios Electrónicos"</li>
                            <li>Seleccionar "Solicitar Folios"</li>
                            <li>Elegir tipo de documento y cantidad</li>
                            <li>Descargar archivo CAF (XML)</li>
                            <li>Cargar CAF en este sistema</li>
                        </ol>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5><i class="fas fa-lightbulb"></i> Recomendaciones</h5>
                        <ul>
                            <li><strong>Factura Electrónica (33):</strong> Solicitar 1000-5000 folios</li>
                            <li><strong>Boleta Electrónica (39):</strong> Solicitar 5000-10000 folios</li>
                            <li><strong>Guía Despacho (52):</strong> Solicitar 1000-3000 folios</li>
                            <li><strong>Nota Crédito (61):</strong> Solicitar 500-1000 folios</li>
                            <li>Renovar cuando queden <strong>menos de 100 folios</strong></li>
                            <li>Los CAF <strong>no expiran</strong>, pero deben usarse en orden</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center mt-4 mb-4">
            <a href="../ventas/" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Volver a Ventas
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function verDetalle(tipodocumento) {
            // Aquí podrías abrir un modal con detalle de CAFs para este tipo
            alert('Detalle de CAFs para tipo de documento ' + tipo_documento + ' (próximamente)');
        }

        // Auto-refresh cada 5 minutos
        setTimeout(() => {
            location.reload();
        }, 300000);
    </script>

    <!-- Footer Profesional -->
    <?php include '../includes/footer.php'; ?>

</body>
</html>
