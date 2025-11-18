<?php
/**
 * DASHBOARD DE VENTAS - Sistema Completo con Gráficos y KPIs
 * Multiempresa - Conexión SQL Real
 */
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['empresa_id'])) {
    header('Location: /login.php');
    exit;
}

$empresa_id = (int)$_SESSION['empresa_id'];
$usuario_id = (int)$_SESSION['user_id'];

// Obtener conexión a base de datos
if (!isset($conn)) {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Error de conexión: " . $conn->connect_error);
    }
    $conn->set_charset('utf8mb4');
}

// Obtener estadísticas de ventas
$stmt = $conn->prepare("
    SELECT
        COUNT(*) as total_ventas,
        SUM(total) as monto_total,
        AVG(total) as ticket_promedio
    FROM documentos_tributarios
    WHERE empresa_id = ? AND tipo_dte IN (33, 39) AND estado != 'anulado'
    AND MONTH(fecha_emision) = MONTH(CURDATE()) AND YEAR(fecha_emision) = YEAR(CURDATE())
");
$stmt->bind_param('i', $empresa_id);
$stmt->execute();
$stats_mes = $stmt->get_result()->fetch_assoc();

// Ventas por día (últimos 30 días)
$stmt = $conn->prepare("
    SELECT DATE(fecha_emision) as fecha, COUNT(*) as cantidad, SUM(total) as monto
    FROM documentos_tributarios
    WHERE empresa_id = ? AND tipo_dte IN (33, 39) AND estado != 'anulado'
    AND fecha_emision >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY DATE(fecha_emision)
    ORDER BY fecha
");
$stmt->bind_param('i', $empresa_id);
$stmt->execute();
$ventas_diarias = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Top 10 productos más vendidos
$stmt = $conn->prepare("
    SELECT p.nombre, SUM(dtd.cantidad) as cantidad_vendida, SUM(dtd.subtotal) as monto_total
    FROM documentos_tributarios_detalle dtd
    INNER JOIN documentos_tributarios dt ON dtd.dte_id = dt.id
    LEFT JOIN productos p ON dtd.producto_id = p.id
    WHERE dt.empresa_id = ? AND dt.tipo_dte IN (33, 39) AND dt.estado != 'anulado'
    AND MONTH(dt.fecha_emision) = MONTH(CURDATE())
    GROUP BY p.id, p.nombre
    ORDER BY cantidad_vendida DESC
    LIMIT 10
");
$stmt->bind_param('i', $empresa_id);
$stmt->execute();
$top_productos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Top clientes
$stmt = $conn->prepare("
    SELECT c.nombre, COUNT(dt.id) as num_compras, SUM(dt.total) as monto_total
    FROM documentos_tributarios dt
    INNER JOIN clientes c ON dt.cliente_id = c.id
    WHERE dt.empresa_id = ? AND dt.tipo_dte IN (33, 39) AND dt.estado != 'anulado'
    AND MONTH(dt.fecha_emision) = MONTH(CURDATE())
    GROUP BY c.id, c.nombre
    ORDER BY monto_total DESC
    LIMIT 10
");
$stmt->bind_param('i', $empresa_id);
$stmt->execute();
$top_clientes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Ventas del día
$stmt = $conn->prepare("
    SELECT COUNT(*) as ventas_hoy, COALESCE(SUM(total), 0) as monto_hoy
    FROM documentos_tributarios
    WHERE empresa_id = ? AND DATE(fecha_emision) = CURDATE() AND tipo_dte IN (33, 39) AND estado != 'anulado'
");
$stmt->bind_param('i', $empresa_id);
$stmt->execute();
$stats_hoy = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Ventas | CONECTA ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        .gradient-header {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 2rem;
            margin-bottom: 2rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(240, 147, 251, 0.3);
        }
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: all 0.3s;
            height: 100%;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            line-height: 1;
        }
        .chart-container {
            position: relative;
            height: 300px;
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid py-4">
        <div class="gradient-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1><i class="fas fa-chart-line"></i> Dashboard de Ventas</h1>
                    <p class="mb-0 opacity-75">Análisis en tiempo real de ventas y rendimiento</p>
                </div>
                <div>
                    <a href="/user/dashboard_user.php" class="btn btn-light btn-lg">
                        <i class="fas fa-arrow-left"></i> Volver al Dashboard
                    </a>
                </div>
            </div>
        </div>

        <!-- KPIs del Día -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card border-start border-5 border-success">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Ventas Hoy</h6>
                            <div class="stat-number text-success"><?= number_format($stats_hoy['ventas_hoy'] ?? 0) ?></div>
                            <small class="text-muted">Documentos</small>
                        </div>
                        <i class="fas fa-shopping-cart fa-3x text-success opacity-25"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card border-start border-5 border-primary">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Monto Hoy</h6>
                            <div class="stat-number text-primary">$<?= number_format($stats_hoy['monto_hoy'] ?? 0, 0, ',', '.') ?></div>
                            <small class="text-muted">CLP</small>
                        </div>
                        <i class="fas fa-dollar-sign fa-3x text-primary opacity-25"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card border-start border-5 border-warning">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Ventas del Mes</h6>
                            <div class="stat-number text-warning"><?= number_format($stats_mes['total_ventas'] ?? 0) ?></div>
                            <small class="text-muted">Documentos</small>
                        </div>
                        <i class="fas fa-chart-bar fa-3x text-warning opacity-25"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card border-start border-5 border-info">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Ticket Promedio</h6>
                            <div class="stat-number text-info">$<?= number_format($stats_mes['ticket_promedio'] ?? 0, 0, ',', '.') ?></div>
                            <small class="text-muted">CLP</small>
                        </div>
                        <i class="fas fa-receipt fa-3x text-info opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráficos -->
        <div class="row mb-4">
            <div class="col-md-8">
                <div class="chart-container">
                    <h5 class="mb-3"><i class="fas fa-chart-line text-primary"></i> Ventas Últimos 30 Días</h5>
                    <canvas id="ventasDiariasChart"></canvas>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <h5 class="mb-3"><i class="fas fa-trophy text-warning"></i> Top 5 Productos</h5>
                    <div class="list-group list-group-flush">
                        <?php foreach (array_slice($top_productos, 0, 5) as $idx => $prod): ?>
                        <div class="list-group-item px-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="badge bg-primary me-2">#<?= $idx + 1 ?></span>
                                    <strong><?= htmlspecialchars($prod['nombre'] ?? 'Sin nombre') ?></strong>
                                </div>
                                <div class="text-end">
                                    <div class="text-success fw-bold"><?= number_format($prod['cantidad_vendida']) ?> uds</div>
                                    <small class="text-muted">$<?= number_format($prod['monto_total'], 0, ',', '.') ?></small>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php if (empty($top_productos)): ?>
                        <div class="text-center text-muted py-3">
                            <i class="fas fa-inbox fa-2x mb-2"></i>
                            <p>No hay datos disponibles</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Clientes -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-users text-primary"></i> Top 10 Clientes del Mes</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Cliente</th>
                                        <th class="text-center">Compras</th>
                                        <th class="text-end">Monto Total</th>
                                        <th class="text-end">Promedio</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($top_clientes as $idx => $cliente): ?>
                                    <tr>
                                        <td><span class="badge bg-primary"><?= $idx + 1 ?></span></td>
                                        <td><strong><?= htmlspecialchars($cliente['nombre']) ?></strong></td>
                                        <td class="text-center"><?= number_format($cliente['num_compras']) ?></td>
                                        <td class="text-end text-success fw-bold">$<?= number_format($cliente['monto_total'], 0, ',', '.') ?></td>
                                        <td class="text-end text-muted">$<?= number_format($cliente['monto_total'] / $cliente['num_compras'], 0, ',', '.') ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($top_clientes)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">
                                            <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                            No hay datos de clientes este mes
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Gráfico de ventas diarias
    const ctx = document.getElementById('ventasDiariasChart').getContext('2d');

    const ventasDiariasData = <?= json_encode($ventas_diarias) ?>;
    const fechas = ventasDiariasData.map(v => {
        const fecha = new Date(v.fecha);
        return fecha.toLocaleDateString('es-CL', { day: '2-digit', month: '2-digit' });
    });
    const montos = ventasDiariasData.map(v => parseFloat(v.monto));
    const cantidades = ventasDiariasData.map(v => parseInt(v.cantidad));

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: fechas,
            datasets: [{
                label: 'Monto (CLP)',
                data: montos,
                borderColor: '#f5576c',
                backgroundColor: 'rgba(245, 87, 108, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                yAxisID: 'y'
            }, {
                label: 'Cantidad Ventas',
                data: cantidades,
                borderColor: '#667eea',
                backgroundColor: 'rgba(102, 126, 234, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                yAxisID: 'y1'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                if (context.datasetIndex === 0) {
                                    label += '$' + context.parsed.y.toLocaleString('es-CL');
                                } else {
                                    label += context.parsed.y + ' ventas';
                                }
                            }
                            return label;
                        }
                    }
                }
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Monto (CLP)'
                    },
                    ticks: {
                        callback: function(value) {
                            return '$' + value.toLocaleString('es-CL');
                        }
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Cantidad'
                    },
                    grid: {
                        drawOnChartArea: false,
                    },
                }
            }
        }
    });
    </script>
</body>
</html>
