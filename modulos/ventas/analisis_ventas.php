<?php
/**
 * ANÁLISIS DE VENTAS - Sistema Completo con Gráficos Avanzados
 * Reportes detallados con Chart.js y exportación
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

// Filtros
$fecha_desde = $_GET['fecha_desde'] ?? date('Y-m-01');
$fecha_hasta = $_GET['fecha_hasta'] ?? date('Y-m-d');
$tipo_reporte = $_GET['tipo'] ?? 'general';

// Ventas por período
$stmt = $conn->prepare("
    SELECT
        DATE(fecha_emision) as fecha,
        COUNT(*) as cantidad,
        SUM(total) as monto,
        AVG(total) as promedio
    FROM documentos_tributarios
    WHERE empresa_id = ? AND fecha_emision BETWEEN ? AND ?
    AND tipo_dte IN (33, 39) AND estado != 'anulado'
    GROUP BY DATE(fecha_emision)
    ORDER BY fecha
");
$stmt->bind_param('iss', $empresa_id, $fecha_desde, $fecha_hasta);
$stmt->execute();
$ventas_periodo = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Ventas por producto
$stmt = $conn->prepare("
    SELECT p.nombre, p.codigo,
           SUM(dtd.cantidad) as cantidad_vendida,
           SUM(dtd.subtotal) as monto_total,
           COUNT(DISTINCT dt.id) as num_documentos
    FROM documentos_tributarios_detalle dtd
    INNER JOIN documentos_tributarios dt ON dtd.dte_id = dt.id
    LEFT JOIN productos p ON dtd.producto_id = p.id
    WHERE dt.empresa_id = ? AND dt.fecha_emision BETWEEN ? AND ?
    AND dt.estado != 'anulado'
    GROUP BY p.id, p.nombre, p.codigo
    ORDER BY monto_total DESC
    LIMIT 20
");
$stmt->bind_param('iss', $empresa_id, $fecha_desde, $fecha_hasta);
$stmt->execute();
$ventas_producto = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Ventas por vendedor
$stmt = $conn->prepare("
    SELECT u.nombre, u.email,
           COUNT(dt.id) as num_ventas,
           SUM(dt.total) as monto_total,
           AVG(dt.total) as ticket_promedio
    FROM documentos_tributarios dt
    INNER JOIN usuarios u ON dt.usuario_id = u.id
    WHERE dt.empresa_id = ? AND dt.fecha_emision BETWEEN ? AND ?
    AND dt.estado != 'anulado'
    GROUP BY u.id, u.nombre, u.email
    ORDER BY monto_total DESC
");
$stmt->bind_param('iss', $empresa_id, $fecha_desde, $fecha_hasta);
$stmt->execute();
$ventas_vendedor = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Resumen general
$stmt = $conn->prepare("
    SELECT
        COUNT(*) as total_documentos,
        SUM(total) as total_monto,
        AVG(total) as ticket_promedio,
        SUM(CASE WHEN tipo_dte = 33 THEN 1 ELSE 0 END) as facturas,
        SUM(CASE WHEN tipo_dte = 39 THEN 1 ELSE 0 END) as boletas
    FROM documentos_tributarios
    WHERE empresa_id = ? AND fecha_emision BETWEEN ? AND ?
    AND estado != 'anulado'
");
$stmt->bind_param('iss', $empresa_id, $fecha_desde, $fecha_hasta);
$stmt->execute();
$resumen = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Análisis de Ventas | CONECTA ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        .gradient-header {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            color: white;
            padding: 2rem;
            margin-bottom: 2rem;
            border-radius: 15px;
        }
        .chart-container {
            position: relative;
            height: 400px;
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            height: 100%;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid py-4">
        <div class="gradient-header">
            <h1><i class="fas fa-chart-pie"></i> Análisis de Ventas</h1>
            <p class="mb-0 opacity-75">Reportes detallados y estadísticas avanzadas</p>
        </div>

        <!-- Filtros -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Desde</label>
                        <input type="date" name="fecha_desde" class="form-control" value="<?= $fecha_desde ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Hasta</label>
                        <input type="date" name="fecha_hasta" class="form-control" value="<?= $fecha_hasta ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tipo de Reporte</label>
                        <select name="tipo" class="form-select">
                            <option value="general">General</option>
                            <option value="productos">Por Producto</option>
                            <option value="vendedores">Por Vendedor</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i> Filtrar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- KPIs -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card border-start border-5 border-primary">
                    <h6 class="text-muted mb-1">Total Documentos</h6>
                    <h2 class="mb-0 text-primary"><?= number_format($resumen['total_documentos']) ?></h2>
                    <small class="text-muted">Facturas: <?= number_format($resumen['facturas']) ?> | Boletas: <?= number_format($resumen['boletas']) ?></small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card border-start border-5 border-success">
                    <h6 class="text-muted mb-1">Monto Total</h6>
                    <h2 class="mb-0 text-success">$<?= number_format($resumen['total_monto'], 0, ',', '.') ?></h2>
                    <small class="text-muted">CLP</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card border-start border-5 border-info">
                    <h6 class="text-muted mb-1">Ticket Promedio</h6>
                    <h2 class="mb-0 text-info">$<?= number_format($resumen['ticket_promedio'], 0, ',', '.') ?></h2>
                    <small class="text-muted">CLP</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card border-start border-5 border-warning">
                    <h6 class="text-muted mb-1">Período</h6>
                    <h2 class="mb-0 text-warning"><?= count($ventas_periodo) ?></h2>
                    <small class="text-muted">Días con ventas</small>
                </div>
            </div>
        </div>

        <!-- Gráficos -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="chart-container">
                    <h5 class="mb-3"><i class="fas fa-chart-area text-primary"></i> Evolución de Ventas</h5>
                    <canvas id="ventasChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Tablas -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-box text-primary"></i> Top 10 Productos</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Producto</th>
                                        <th class="text-end">Cantidad</th>
                                        <th class="text-end">Monto</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($ventas_producto, 0, 10) as $prod): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($prod['nombre'] ?? 'Sin nombre') ?></strong><br>
                                            <small class="text-muted"><?= htmlspecialchars($prod['codigo']) ?></small>
                                        </td>
                                        <td class="text-end"><?= number_format($prod['cantidad_vendida']) ?></td>
                                        <td class="text-end text-success">$<?= number_format($prod['monto_total'], 0, ',', '.') ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-user-tie text-primary"></i> Rendimiento Vendedores</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Vendedor</th>
                                        <th class="text-end">Ventas</th>
                                        <th class="text-end">Monto</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($ventas_vendedor as $vend): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($vend['nombre']) ?></strong><br>
                                            <small class="text-muted"><?= htmlspecialchars($vend['email']) ?></small>
                                        </td>
                                        <td class="text-end"><?= number_format($vend['num_ventas']) ?></td>
                                        <td class="text-end text-success">$<?= number_format($vend['monto_total'], 0, ',', '.') ?></td>
                                    </tr>
                                    <?php endforeach; ?>
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
    const ventasData = <?= json_encode($ventas_periodo) ?>;
    const fechas = ventasData.map(v => {
        const fecha = new Date(v.fecha);
        return fecha.toLocaleDateString('es-CL', { day: '2-digit', month: 'short' });
    });
    const montos = ventasData.map(v => parseFloat(v.monto));

    new Chart(document.getElementById('ventasChart'), {
        type: 'bar',
        data: {
            labels: fechas,
            datasets: [{
                label: 'Ventas (CLP)',
                data: montos,
                backgroundColor: 'rgba(250, 112, 154, 0.8)',
                borderColor: 'rgba(250, 112, 154, 1)',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return '$' + context.parsed.y.toLocaleString('es-CL');
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + value.toLocaleString('es-CL');
                        }
                    }
                }
            }
        }
    });
    </script>
</body>
</html>
