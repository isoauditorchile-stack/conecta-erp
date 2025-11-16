<?php
/**
 * REPORTES Y ANALÍTICAS - Super Administrador
 */
session_start();
require_once '../includes/config.php';

// Verificar sesión y permisos de super admin
requireLogin();
requireSuperAdmin();

// Obtener estadísticas generales
$stats = [];

// Usuarios
$stats['total_usuarios'] = $conn->query("SELECT COUNT(*) as total FROM usuarios WHERE es_super_admin = 0")->fetch_assoc()['total'];
$stats['usuarios_activos'] = $conn->query("SELECT COUNT(*) as total FROM usuarios WHERE estado = 'activo' AND es_super_admin = 0")->fetch_assoc()['total'];
$stats['usuarios_pendientes'] = $conn->query("SELECT COUNT(*) as total FROM usuarios WHERE estado = 'pendiente'")->fetch_assoc()['total'];
$stats['usuarios_mes'] = $conn->query("SELECT COUNT(*) as total FROM usuarios WHERE MONTH(fecha_registro) = MONTH(NOW()) AND YEAR(fecha_registro) = YEAR(NOW())")->fetch_assoc()['total'];

// Empresas
$stats['total_empresas'] = $conn->query("SELECT COUNT(*) as total FROM empresas")->fetch_assoc()['total'];
$stats['empresas_activas'] = $conn->query("SELECT COUNT(*) as total FROM empresas WHERE estado = 'activo'")->fetch_assoc()['total'];

// Suscripciones
$stats['total_suscripciones'] = $conn->query("SELECT COUNT(*) as total FROM suscripciones")->fetch_assoc()['total'];
$stats['suscripciones_activas'] = $conn->query("SELECT COUNT(*) as total FROM suscripciones WHERE estado = 'activo'")->fetch_assoc()['total'];
$stats['trial_activos'] = $conn->query("SELECT COUNT(*) as total FROM suscripciones WHERE estado = 'trial'")->fetch_assoc()['total'];

// Pagos
$stats['total_pagos'] = $conn->query("SELECT COUNT(*) as total FROM pagos")->fetch_assoc()['total'];
$stats['pagos_aprobados'] = $conn->query("SELECT COUNT(*) as total FROM pagos WHERE estado = 'aprobado'")->fetch_assoc()['total'];
$stats['pagos_pendientes'] = $conn->query("SELECT COUNT(*) as total FROM pagos WHERE estado = 'pendiente'")->fetch_assoc()['total'];
$stats['ingresos_mes'] = $conn->query("SELECT COALESCE(SUM(monto), 0) as total FROM pagos WHERE estado = 'aprobado' AND MONTH(fecha_pago) = MONTH(NOW()) AND YEAR(fecha_pago) = YEAR(NOW())")->fetch_assoc()['total'];

// MRR (Monthly Recurring Revenue)
$stats['mrr'] = $conn->query("SELECT COALESCE(SUM(monto_mensual), 0) as mrr FROM suscripciones WHERE estado = 'activo'")->fetch_assoc()['mrr'];

// Distribución de usuarios por plan
$usuarios_por_plan = [];
$result = $conn->query("SELECT p.nombre_plan, COUNT(u.id) as total
                        FROM usuarios u
                        INNER JOIN planes p ON u.plan_id = p.id
                        WHERE u.es_super_admin = 0
                        GROUP BY u.plan_id, p.nombre_plan
                        ORDER BY total DESC");
while ($row = $result->fetch_assoc()) {
    $usuarios_por_plan[] = $row;
}

// Crecimiento de usuarios por mes (últimos 6 meses)
$crecimiento_usuarios = [];
$result = $conn->query("SELECT DATE_FORMAT(fecha_registro, '%Y-%m') as mes, COUNT(*) as total
                        FROM usuarios
                        WHERE fecha_registro >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                        GROUP BY DATE_FORMAT(fecha_registro, '%Y-%m')
                        ORDER BY mes ASC");
while ($row = $result->fetch_assoc()) {
    $crecimiento_usuarios[] = $row;
}

// Usuarios por país
$usuarios_por_pais = [];
$result = $conn->query("SELECT pa.nombre as pais, COUNT(DISTINCT u.id) as total
                        FROM usuarios u
                        INNER JOIN empresas e ON u.empresa_id = e.id
                        INNER JOIN paises pa ON e.pais_id = pa.id
                        WHERE u.es_super_admin = 0
                        GROUP BY pa.id, pa.nombre
                        ORDER BY total DESC
                        LIMIT 10");
while ($row = $result->fetch_assoc()) {
    $usuarios_por_pais[] = $row;
}

// Tasa de conversión de trial a pago
$trial_total = $conn->query("SELECT COUNT(*) as total FROM suscripciones WHERE es_trial = 1")->fetch_assoc()['total'];
$trial_convertidos = $conn->query("SELECT COUNT(*) as total FROM suscripciones WHERE es_trial = 0 AND estado = 'activo'")->fetch_assoc()['total'];
$tasa_conversion = $trial_total > 0 ? round(($trial_convertidos / $trial_total) * 100, 2) : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes y Analíticas - CONECTA ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --sidebar-width: 280px;
            --sidebar-collapsed-width: 70px;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
        }

        .wrapper {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: var(--sidebar-width);
            background: var(--primary-gradient);
            color: white;
            transition: all 0.3s ease;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
        }

        .sidebar.collapsed {
            width: var(--sidebar-collapsed-width);
        }

        .sidebar-header {
            padding: 25px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-header h3 {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0;
        }

        .sidebar.collapsed .sidebar-header h3 span {
            display: none;
        }

        .sidebar-menu {
            list-style: none;
            padding: 20px 0;
            margin: 0;
        }

        .sidebar-menu li {
            margin: 5px 0;
        }

        .sidebar-menu a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            padding: 12px 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            transition: all 0.3s ease;
        }

        .sidebar-menu a:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .sidebar-menu a.active {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border-left: 4px solid white;
        }

        .sidebar-menu a i {
            font-size: 1.2rem;
            width: 25px;
            text-align: center;
        }

        .sidebar.collapsed .sidebar-menu a span {
            display: none;
        }

        .main-content {
            margin-left: var(--sidebar-width);
            flex: 1;
            transition: margin-left 0.3s ease;
        }

        .sidebar.collapsed ~ .main-content {
            margin-left: var(--sidebar-collapsed-width);
        }

        .topbar {
            background: white;
            padding: 15px 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .toggle-sidebar {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #667eea;
            cursor: pointer;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        .content-area {
            padding: 30px;
        }

        .page-header {
            margin-bottom: 30px;
        }

        .page-header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 10px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .stat-card h3 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .stat-card.usuarios { border-left: 4px solid #667eea; }
        .stat-card.empresas { border-left: 4px solid #48bb78; }
        .stat-card.suscripciones { border-left: 4px solid #ed8936; }
        .stat-card.pagos { border-left: 4px solid #8b5cf6; }
        .stat-card.mrr { border-left: 4px solid #ec4899; }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }

        .card-header {
            background: white;
            border-bottom: 1px solid #e2e8f0;
            padding: 20px;
            font-weight: 600;
            font-size: 1.1rem;
            color: #2d3748;
        }

        .card-body {
            padding: 25px;
        }

        .chart-container {
            position: relative;
            height: 300px;
        }

        .table th {
            background: #f7fafc;
            font-weight: 600;
            color: #4a5568;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h3>
                    <i class="fas fa-user-shield"></i>
                    <span>ADMIN</span>
                </h3>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard_admin.php"><i class="fas fa-home"></i><span>Dashboard</span></a></li>
                <li><a href="usuarios.php"><i class="fas fa-users"></i><span>Usuarios</span></a></li>
                <li><a href="empresas.php"><i class="fas fa-building"></i><span>Empresas</span></a></li>
                <li><a href="pagos.php"><i class="fas fa-dollar-sign"></i><span>Pagos</span></a></li>
                <li><a href="suscripciones.php"><i class="fas fa-credit-card"></i><span>Suscripciones</span></a></li>
                <li><a href="configuracion.php"><i class="fas fa-cog"></i><span>Configuración</span></a></li>
                <li><a href="reportes.php" class="active"><i class="fas fa-chart-bar"></i><span>Reportes</span></a></li>
                <li><a href="auditoria.php"><i class="fas fa-history"></i><span>Auditoría</span></a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i><span>Cerrar Sesión</span></a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Topbar -->
            <div class="topbar">
                <button class="toggle-sidebar" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="user-info">
                    <div>
                        <strong>Super Administrador</strong>
                        <div style="font-size: 0.875rem; color: #718096;">Administrador</div>
                    </div>
                    <div class="user-avatar">SA</div>
                </div>
            </div>

            <!-- Content Area -->
            <div class="content-area">
                <div class="page-header">
                    <h1><i class="fas fa-chart-bar"></i> Reportes y Analíticas</h1>
                    <p>Métricas y estadísticas del sistema</p>
                </div>

                <!-- Estadísticas Principales -->
                <div class="stats-grid">
                    <div class="stat-card usuarios">
                        <div style="color: #667eea;"><i class="fas fa-users"></i> Usuarios Totales</div>
                        <h3><?php echo $stats['total_usuarios']; ?></h3>
                        <small class="text-muted"><?php echo $stats['usuarios_mes']; ?> este mes</small>
                    </div>
                    <div class="stat-card empresas">
                        <div style="color: #48bb78;"><i class="fas fa-building"></i> Empresas</div>
                        <h3><?php echo $stats['total_empresas']; ?></h3>
                        <small class="text-muted"><?php echo $stats['empresas_activas']; ?> activas</small>
                    </div>
                    <div class="stat-card suscripciones">
                        <div style="color: #ed8936;"><i class="fas fa-credit-card"></i> Suscripciones</div>
                        <h3><?php echo $stats['total_suscripciones']; ?></h3>
                        <small class="text-muted"><?php echo $stats['suscripciones_activas']; ?> activas</small>
                    </div>
                    <div class="stat-card pagos">
                        <div style="color: #8b5cf6;"><i class="fas fa-dollar-sign"></i> Pagos Aprobados</div>
                        <h3><?php echo $stats['pagos_aprobados']; ?></h3>
                        <small class="text-muted"><?php echo $stats['pagos_pendientes']; ?> pendientes</small>
                    </div>
                    <div class="stat-card mrr">
                        <div style="color: #ec4899;"><i class="fas fa-chart-line"></i> MRR</div>
                        <h3>$<?php echo number_format($stats['mrr'], 0, ',', '.'); ?></h3>
                        <small class="text-muted">Ingresos recurrentes</small>
                    </div>
                </div>

                <!-- Gráficos -->
                <div class="row">
                    <!-- Usuarios por Plan -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-chart-pie"></i> Distribución por Plan
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="chartPlanes"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Crecimiento de Usuarios -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-chart-line"></i> Crecimiento de Usuarios (Últimos 6 Meses)
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="chartCrecimiento"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tablas de Reportes -->
                <div class="row">
                    <!-- Usuarios por País -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-globe"></i> Usuarios por País
                            </div>
                            <div class="card-body">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>País</th>
                                            <th class="text-end">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($usuarios_por_pais as $item): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($item['pais']); ?></td>
                                                <td class="text-end"><strong><?php echo $item['total']; ?></strong></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Métricas Clave -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-key"></i> Métricas Clave
                            </div>
                            <div class="card-body">
                                <table class="table table-hover">
                                    <tr>
                                        <td><strong>Tasa de Conversión (Trial → Pago)</strong></td>
                                        <td class="text-end"><span class="badge bg-success"><?php echo $tasa_conversion; ?>%</span></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Usuarios en Trial Activo</strong></td>
                                        <td class="text-end"><strong><?php echo $stats['trial_activos']; ?></strong></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Ingresos del Mes</strong></td>
                                        <td class="text-end"><strong>$<?php echo number_format($stats['ingresos_mes'], 0, ',', '.'); ?></strong></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Usuarios Pendientes Aprobación</strong></td>
                                        <td class="text-end"><strong><?php echo $stats['usuarios_pendientes']; ?></strong></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('collapsed');
        }

        // Gráfico de Distribución por Plan
        const ctxPlanes = document.getElementById('chartPlanes').getContext('2d');
        new Chart(ctxPlanes, {
            type: 'doughnut',
            data: {
                labels: [<?php echo implode(',', array_map(function($item) { return "'" . $item['nombre_plan'] . "'"; }, $usuarios_por_plan)); ?>],
                datasets: [{
                    data: [<?php echo implode(',', array_map(function($item) { return $item['total']; }, $usuarios_por_plan)); ?>],
                    backgroundColor: ['#667eea', '#48bb78', '#ed8936', '#8b5cf6'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Gráfico de Crecimiento
        const ctxCrecimiento = document.getElementById('chartCrecimiento').getContext('2d');
        new Chart(ctxCrecimiento, {
            type: 'line',
            data: {
                labels: [<?php echo implode(',', array_map(function($item) { return "'" . $item['mes'] . "'"; }, $crecimiento_usuarios)); ?>],
                datasets: [{
                    label: 'Nuevos Usuarios',
                    data: [<?php echo implode(',', array_map(function($item) { return $item['total']; }, $crecimiento_usuarios)); ?>],
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>
