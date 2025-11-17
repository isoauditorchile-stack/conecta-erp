<?php
/**
 * MÓDULO DE ASISTENCIA Y RELOJ CONTROL
 * Dashboard principal de asistencia de empleados
 */

session_start();
require_once '../../../includes/config.php';
require_once '../../../includes/functions.php';

requireLogin();

$usuario_id = $_SESSION['usuario_id'];
$empresa_id = $_SESSION['empresa_id'];

// Obtener fecha de consulta (por defecto hoy)
$fecha_consulta = $_GET['fecha'] ?? date('Y-m-d');

// Obtener estadísticas del día
$stmt = $conn->prepare("SELECT
    COUNT(DISTINCT am.empleado_id) as total_marcajes,
    COUNT(DISTINCT CASE WHEN am.tipo = 'entrada' THEN am.empleado_id END) as entradas_registradas,
    COUNT(DISTINCT CASE WHEN am.tipo = 'salida' THEN am.empleado_id END) as salidas_registradas,
    (SELECT COUNT(*) FROM empleados WHERE empresa_id = ? AND activo = 1) as total_empleados
    FROM asistencia_marcajes am
    WHERE am.empresa_id = ? AND am.fecha = ?");
$stmt->bind_param("iis", $empresa_id, $empresa_id, $fecha_consulta);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Empleados sin salida
$stmt = $conn->prepare("SELECT COUNT(*) as sin_salida FROM v_empleados_sin_salida WHERE empresa_id = ?");
$stmt->bind_param("i", $empresa_id);
$stmt->execute();
$sin_salida = $stmt->get_result()->fetch_assoc()['sin_salida'];
$stmt->close();

// Obtener marcajes del día
$stmt = $conn->prepare("SELECT * FROM v_marcajes_hoy WHERE rut IN (
    SELECT rut FROM empleados WHERE empresa_id = ?) ORDER BY hora_marcaje DESC LIMIT 50");
$stmt->bind_param("i", $empresa_id);
$stmt->execute();
$marcajes_hoy = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Obtener alertas pendientes
$stmt = $conn->prepare("SELECT a.*, CONCAT(e.nombres, ' ', e.apellidos) as empleado_nombre
    FROM asistencia_alertas a
    INNER JOIN empleados e ON a.empleado_id = e.id
    WHERE a.empresa_id = ? AND a.leida = 0 AND a.fecha >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    ORDER BY a.created_at DESC LIMIT 10");
$stmt->bind_param("i", $empresa_id);
$stmt->execute();
$alertas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asistencia y Reloj Control - CONECTA ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .stat-card h3 {
            font-size: 2.5rem;
            margin: 0;
        }
        .stat-card p {
            margin: 5px 0 0 0;
            opacity: 0.9;
        }
        .alert-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #ff4444;
            color: white;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        .marcaje-row {
            padding: 12px;
            border-bottom: 1px solid #eee;
            transition: all 0.3s;
        }
        .marcaje-row:hover {
            background: #f8f9fa;
        }
        .badge-entrada {
            background: #28a745;
        }
        .badge-salida {
            background: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container-fluid mt-4">
        <div class="row mb-4">
            <div class="col-12">
                <h1><i class="fas fa-clock"></i> Control de Asistencia</h1>
                <p class="text-muted">Sistema de reloj control y gestión de asistencia</p>
                <a href="../../dashboard.php" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i> Volver al Dashboard
                </a>
            </div>
        </div>

        <!-- Estadísticas -->
        <div class="row">
            <div class="col-md-3">
                <div class="stat-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <h3><?php echo $stats['total_empleados']; ?></h3>
                    <p><i class="fas fa-users"></i> Total Empleados</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                    <h3><?php echo $stats['entradas_registradas']; ?></h3>
                    <p><i class="fas fa-sign-in-alt"></i> Entradas Hoy</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" style="background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%);">
                    <h3><?php echo $stats['salidas_registradas']; ?></h3>
                    <p><i class="fas fa-sign-out-alt"></i> Salidas Hoy</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" style="background: linear-gradient(135deg, #ffc107 0%, #ff6b6b 100%);">
                    <h3><?php echo $sin_salida; ?></h3>
                    <p><i class="fas fa-exclamation-triangle"></i> Sin Salida</p>
                    <?php if ($sin_salida > 0): ?>
                        <div class="alert-badge"><?php echo $sin_salida; ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Acciones Rápidas -->
            <div class="col-md-3">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-bolt"></i> Acciones Rápidas</h5>
                    </div>
                    <div class="card-body">
                        <a href="registrar_marcaje.php" class="btn btn-success btn-block mb-2 w-100">
                            <i class="fas fa-fingerprint"></i> Registrar Marcaje
                        </a>
                        <a href="reporte_diario.php" class="btn btn-info btn-block mb-2 w-100">
                            <i class="fas fa-file-alt"></i> Reporte Diario
                        </a>
                        <a href="reporte_excel.php" class="btn btn-warning btn-block mb-2 w-100">
                            <i class="fas fa-file-excel"></i> Exportar Excel
                        </a>
                        <a href="configuracion_horarios.php" class="btn btn-secondary btn-block w-100">
                            <i class="fas fa-cog"></i> Config. Horarios
                        </a>
                    </div>
                </div>

                <!-- Alertas Pendientes -->
                <?php if (count($alertas) > 0): ?>
                <div class="card shadow-sm mt-3">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="fas fa-bell"></i> Alertas Pendientes</h5>
                    </div>
                    <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                        <?php foreach ($alertas as $alerta): ?>
                        <div class="alert alert-<?php echo $alerta['gravedad'] == 'error' ? 'danger' : 'warning'; ?> alert-dismissible fade show p-2 mb-2" role="alert">
                            <small>
                                <strong><?php echo htmlspecialchars($alerta['empleado_nombre']); ?></strong><br>
                                <?php echo htmlspecialchars($alerta['mensaje']); ?><br>
                                <span class="text-muted"><?php echo date('d/m/Y', strtotime($alerta['fecha'])); ?></span>
                            </small>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Marcajes de Hoy -->
            <div class="col-md-9">
                <div class="card shadow-sm">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0"><i class="fas fa-list"></i> Marcajes de Hoy (<?php echo date('d/m/Y', strtotime($fecha_consulta)); ?>)</h5>
                    </div>
                    <div class="card-body p-0" style="max-height: 600px; overflow-y: auto;">
                        <?php if (count($marcajes_hoy) > 0): ?>
                            <?php foreach ($marcajes_hoy as $marcaje): ?>
                            <div class="marcaje-row d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">
                                        <strong><?php echo htmlspecialchars($marcaje['nombre_completo']); ?></strong>
                                        <small class="text-muted">- <?php echo htmlspecialchars($marcaje['rut']); ?></small>
                                    </h6>
                                    <p class="mb-0 text-muted small">
                                        <i class="fas fa-briefcase"></i> <?php echo htmlspecialchars($marcaje['cargo']); ?> |
                                        <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($marcaje['dispositivo'] ?? 'N/A'); ?>
                                    </p>
                                </div>
                                <div class="text-end">
                                    <span class="badge <?php echo $marcaje['tipo'] == 'entrada' ? 'badge-entrada' : 'badge-salida'; ?> fs-6">
                                        <i class="fas fa-<?php echo $marcaje['tipo'] == 'entrada' ? 'sign-in-alt' : 'sign-out-alt'; ?>"></i>
                                        <?php echo strtoupper($marcaje['tipo']); ?>
                                    </span>
                                    <div class="mt-1">
                                        <strong class="fs-5"><?php echo $marcaje['hora_marcaje']; ?></strong>
                                    </div>
                                    <small class="text-muted"><?php echo ucfirst($marcaje['metodo']); ?></small>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center p-5">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No hay marcajes registrados para hoy</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Gráfico de Asistencia Semanal (placeholder) -->
                <div class="card shadow-sm mt-3">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Resumen Semanal</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="chartAsistencia" style="max-height: 300px;"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        // Auto-refresh cada 30 segundos
        setTimeout(function() {
            window.location.reload();
        }, 30000);

        // Gráfico de ejemplo
        const ctx = document.getElementById('chartAsistencia');
        if (ctx) {
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'],
                    datasets: [{
                        label: 'Asistencia',
                        data: [95, 92, 98, 90, 85, 45, 10],
                        backgroundColor: 'rgba(102, 126, 234, 0.5)',
                        borderColor: 'rgba(102, 126, 234, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100
                        }
                    }
                }
            });
        }
    </script>

    <!-- Footer Profesional -->
    <?php include '../includes/footer.php'; ?>

</body>
</html>
