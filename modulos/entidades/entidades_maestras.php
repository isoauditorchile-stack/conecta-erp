<?php
/**
 * ENTIDADES MAESTRAS - CONECTA ERP
 * Dashboard de Entidades Maestras
 * Nivel Empresarial - Estilo SAP/Softland
 */

session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Verificar autenticación
requireLogin();

// Verificar permisos
$puede_gestionar = ($_SESSION['es_super_admin'] == 1) ||
                   verificarPermiso('clientes', 'leer') ||
                   verificarPermiso('proveedores', 'leer') ||
                   verificarPermiso('empleados', 'leer') ||
                   verificarPermiso('productos', 'leer');

if (!$puede_gestionar) {
    header('Location: ../../user/dashboard.php');
    exit();
}

// ==========================================
// OBTENER ESTADÍSTICAS
// ==========================================

// Estadísticas de Clientes
$stats_clientes = $conn->query("SELECT
    COUNT(*) as total,
    SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END) as activos,
    SUM(CASE WHEN estado = 'inactivo' THEN 1 ELSE 0 END) as inactivos,
    SUM(CASE WHEN clasificacion = 'A' THEN 1 ELSE 0 END) as clase_a,
    SUM(CASE WHEN clasificacion = 'B' THEN 1 ELSE 0 END) as clase_b,
    SUM(CASE WHEN clasificacion = 'C' THEN 1 ELSE 0 END) as clase_c
    FROM clientes
    WHERE empresa_id = {$_SESSION['empresa_id']}")->fetch_assoc();

// Estadísticas de Proveedores
$stats_proveedores = $conn->query("SELECT
    COUNT(*) as total,
    SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END) as activos,
    SUM(CASE WHEN estado = 'inactivo' THEN 1 ELSE 0 END) as inactivos,
    SUM(CASE WHEN evaluacion >= 4 THEN 1 ELSE 0 END) as excelentes,
    SUM(CASE WHEN evaluacion < 3 THEN 1 ELSE 0 END) as deficientes
    FROM proveedores
    WHERE empresa_id = {$_SESSION['empresa_id']}")->fetch_assoc();

// Estadísticas de Empleados
$stats_empleados = $conn->query("SELECT
    COUNT(*) as total,
    SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END) as activos,
    SUM(CASE WHEN estado = 'inactivo' THEN 1 ELSE 0 END) as inactivos,
    SUM(CASE WHEN estado = 'vacaciones' THEN 1 ELSE 0 END) as vacaciones,
    COUNT(DISTINCT departamento) as departamentos
    FROM empleados
    WHERE empresa_id = {$_SESSION['empresa_id']}")->fetch_assoc();

// Estadísticas de Productos
$stats_productos = $conn->query("SELECT
    COUNT(*) as total,
    SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END) as activos,
    SUM(CASE WHEN stock <= stock_minimo THEN 1 ELSE 0 END) as stock_bajo,
    SUM(CASE WHEN tipo = 'producto' THEN 1 ELSE 0 END) as productos,
    SUM(CASE WHEN tipo = 'servicio' THEN 1 ELSE 0 END) as servicios
    FROM productos
    WHERE empresa_id = {$_SESSION['empresa_id']}")->fetch_assoc();

// Últimas transacciones (simulado)
$ultimas_transacciones = $conn->query("SELECT
    la.*, u.nombre, u.apellido
    FROM logs_auditoria la
    LEFT JOIN usuarios u ON la.usuario_id = u.id
    WHERE la.empresa_id = {$_SESSION['empresa_id']}
    AND la.tabla IN ('clientes', 'proveedores', 'empleados', 'productos')
    ORDER BY la.fecha_accion DESC
    LIMIT 10")->fetch_all(MYSQLI_ASSOC);

// Datos para gráficos (últimos 6 meses)
$meses_labels = [];
$clientes_data = [];
$proveedores_data = [];
$productos_data = [];

for ($i = 5; $i >= 0; $i--) {
    $mes = date('Y-m', strtotime("-$i months"));
    $mes_nombre = date('M', strtotime("-$i months"));
    $meses_labels[] = ucfirst($mes_nombre);

    // Clientes por mes
    $clientes_mes = $conn->query("SELECT COUNT(*) as total FROM clientes
                                   WHERE empresa_id = {$_SESSION['empresa_id']}
                                   AND DATE_FORMAT(fecha_creacion, '%Y-%m') = '$mes'")->fetch_assoc()['total'];
    $clientes_data[] = $clientes_mes;

    // Proveedores por mes
    $proveedores_mes = $conn->query("SELECT COUNT(*) as total FROM proveedores
                                      WHERE empresa_id = {$_SESSION['empresa_id']}
                                      AND DATE_FORMAT(fecha_creacion, '%Y-%m') = '$mes'")->fetch_assoc()['total'];
    $proveedores_data[] = $proveedores_mes;

    // Productos por mes
    $productos_mes = $conn->query("SELECT COUNT(*) as total FROM productos
                                    WHERE empresa_id = {$_SESSION['empresa_id']}
                                    AND DATE_FORMAT(fecha_creacion, '%Y-%m') = '$mes'")->fetch_assoc()['total'];
    $productos_data[] = $productos_mes;
}

// Top 5 clientes (simulado con datos ficticios)
$top_clientes = $conn->query("SELECT codigo, nombre, clasificacion, estado
                               FROM clientes
                               WHERE empresa_id = {$_SESSION['empresa_id']}
                               ORDER BY fecha_creacion DESC
                               LIMIT 5")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entidades Maestras - CONECTA ERP</title>

    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/dark_mode.css">

    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --sidebar-width: 250px;
            --topbar-height: 60px;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            overflow-x: hidden;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: var(--primary-gradient);
            padding: 20px 0;
            z-index: 1000;
            transition: all 0.3s;
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 0 20px 20px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 20px;
        }

        .sidebar-header h3 {
            color: white;
            font-size: 20px;
            font-weight: 700;
            margin: 0;
        }

        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-menu li {
            margin-bottom: 5px;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .sidebar-menu a i {
            width: 25px;
            margin-right: 10px;
            font-size: 18px;
        }

        /* Topbar */
        .topbar {
            position: fixed;
            top: 0;
            left: var(--sidebar-width);
            right: 0;
            height: var(--topbar-height);
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 0 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            z-index: 999;
        }

        .breadcrumb {
            background: none;
            padding: 0;
            margin: 0;
        }

        .breadcrumb-item a {
            color: #667eea;
            text-decoration: none;
        }

        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            margin-top: var(--topbar-height);
            padding: 30px;
            min-height: calc(100vh - var(--topbar-height));
        }

        /* Stats Cards */
        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s;
        }

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .stats-icon {
            width: 60px;
            height: 60px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 15px;
        }

        .stats-icon.blue { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .stats-icon.green { background: linear-gradient(135deg, #48bb78 0%, #38a169 100%); color: white; }
        .stats-icon.orange { background: linear-gradient(135deg, #ed8936 0%, #dd6b20 100%); color: white; }
        .stats-icon.red { background: linear-gradient(135deg, #f56565 0%, #c53030 100%); color: white; }
        .stats-icon.purple { background: linear-gradient(135deg, #9f7aea 0%, #805ad5 100%); color: white; }
        .stats-icon.teal { background: linear-gradient(135deg, #38b2ac 0%, #319795 100%); color: white; }

        .stats-number {
            font-size: 32px;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 5px;
        }

        .stats-label {
            color: #718096;
            font-size: 14px;
        }

        /* Entity Cards */
        .entity-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s;
            cursor: pointer;
            border-left: 5px solid transparent;
            height: 100%;
        }

        .entity-card:hover {
            transform: translateX(10px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .entity-card.clientes { border-left-color: #667eea; }
        .entity-card.proveedores { border-left-color: #48bb78; }
        .entity-card.empleados { border-left-color: #ed8936; }
        .entity-card.productos { border-left-color: #f56565; }

        .entity-card h5 {
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 10px;
        }

        .entity-card .entity-total {
            font-size: 48px;
            font-weight: 700;
            margin: 15px 0;
        }

        .entity-card .entity-stats {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid #f5f7fa;
        }

        .entity-stat {
            text-align: center;
        }

        .entity-stat-value {
            font-size: 20px;
            font-weight: 700;
            color: #2d3748;
        }

        .entity-stat-label {
            font-size: 12px;
            color: #718096;
        }

        /* Content Card */
        .content-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 25px;
            margin-top: 20px;
        }

        .content-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f5f7fa;
        }

        .content-card-title {
            font-size: 20px;
            font-weight: 700;
            color: #2d3748;
            margin: 0;
        }

        /* Buttons */
        .btn-gradient {
            background: var(--primary-gradient);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
            color: white;
        }

        /* Chart Container */
        .chart-container {
            position: relative;
            height: 300px;
            margin-top: 20px;
        }

        /* Timeline */
        .timeline-item {
            padding: 15px;
            border-left: 3px solid #e2e8f0;
            margin-bottom: 15px;
            position: relative;
            padding-left: 30px;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -7px;
            top: 20px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #667eea;
        }

        .timeline-item.crear::before { background: #48bb78; }
        .timeline-item.editar::before { background: #ed8936; }
        .timeline-item.eliminar::before { background: #f56565; }

        .timeline-time {
            font-size: 12px;
            color: #718096;
        }

        .timeline-content {
            margin-top: 5px;
            color: #2d3748;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                left: -250px;
            }

            .sidebar.active {
                left: 0;
            }

            .main-content {
                margin-left: 0;
            }

            .topbar {
                left: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h3><i class="fas fa-rocket"></i> CONECTA ERP</h3>
            <small style="color: rgba(255,255,255,0.7);">Entidades Maestras</small>
        </div>

        <ul class="sidebar-menu">
            <li><a href="../dashboard/index.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="entidades_maestras.php" class="active"><i class="fas fa-database"></i> Entidades Maestras</a></li>
            <li><a href="gestion_clientes.php"><i class="fas fa-users"></i> Clientes</a></li>
            <li><a href="gestion_proveedores.php"><i class="fas fa-truck"></i> Proveedores</a></li>
            <li><a href="gestion_empleados.php"><i class="fas fa-user-tie"></i> Empleados</a></li>
            <li><a href="productos_servicios.php"><i class="fas fa-boxes"></i> Productos y Servicios</a></li>
            <li><a href="../administracion/gestion_empresas.php"><i class="fas fa-building"></i> Empresas</a></li>
            <li><a href="../../user/dashboard.php"><i class="fas fa-arrow-left"></i> Volver</a></li>
        </ul>
    </div>

    <!-- Topbar -->
    <div class="topbar">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard/index.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Entidades Maestras</li>
            </ol>
        </nav>

        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-sm" data-theme-toggle>
                <i class="fas fa-moon"></i>
            </button>
            <span class="text-muted">
                <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['nombre']); ?>
            </span>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">Entidades Maestras</h1>
                <p class="text-muted mb-0">Dashboard centralizado de datos maestros del sistema</p>
            </div>
        </div>

        <!-- Stats Cards Principales -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-icon blue">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stats-number"><?php echo number_format($stats_clientes['total']); ?></div>
                    <div class="stats-label">Total Clientes</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-icon green">
                        <i class="fas fa-truck"></i>
                    </div>
                    <div class="stats-number"><?php echo number_format($stats_proveedores['total']); ?></div>
                    <div class="stats-label">Total Proveedores</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-icon orange">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div class="stats-number"><?php echo number_format($stats_empleados['total']); ?></div>
                    <div class="stats-label">Total Empleados</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-icon red">
                        <i class="fas fa-boxes"></i>
                    </div>
                    <div class="stats-number"><?php echo number_format($stats_productos['total']); ?></div>
                    <div class="stats-label">Total Productos</div>
                </div>
            </div>
        </div>

        <!-- Entity Cards -->
        <div class="row mb-4">
            <!-- Clientes -->
            <div class="col-md-6 mb-3">
                <div class="entity-card clientes" onclick="window.location.href='gestion_clientes.php'">
                    <h5><i class="fas fa-users"></i> Clientes</h5>
                    <div class="entity-total" style="color: #667eea;"><?php echo number_format($stats_clientes['total']); ?></div>
                    <div class="entity-stats">
                        <div class="entity-stat">
                            <div class="entity-stat-value" style="color: #48bb78;"><?php echo $stats_clientes['activos']; ?></div>
                            <div class="entity-stat-label">Activos</div>
                        </div>
                        <div class="entity-stat">
                            <div class="entity-stat-value"><?php echo $stats_clientes['clase_a']; ?></div>
                            <div class="entity-stat-label">Clase A</div>
                        </div>
                        <div class="entity-stat">
                            <div class="entity-stat-value"><?php echo $stats_clientes['clase_b']; ?></div>
                            <div class="entity-stat-label">Clase B</div>
                        </div>
                        <div class="entity-stat">
                            <div class="entity-stat-value" style="color: #a0aec0;"><?php echo $stats_clientes['inactivos']; ?></div>
                            <div class="entity-stat-label">Inactivos</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Proveedores -->
            <div class="col-md-6 mb-3">
                <div class="entity-card proveedores" onclick="window.location.href='gestion_proveedores.php'">
                    <h5><i class="fas fa-truck"></i> Proveedores</h5>
                    <div class="entity-total" style="color: #48bb78;"><?php echo number_format($stats_proveedores['total']); ?></div>
                    <div class="entity-stats">
                        <div class="entity-stat">
                            <div class="entity-stat-value" style="color: #48bb78;"><?php echo $stats_proveedores['activos']; ?></div>
                            <div class="entity-stat-label">Activos</div>
                        </div>
                        <div class="entity-stat">
                            <div class="entity-stat-value" style="color: #667eea;"><?php echo $stats_proveedores['excelentes']; ?></div>
                            <div class="entity-stat-label">Excelentes</div>
                        </div>
                        <div class="entity-stat">
                            <div class="entity-stat-value" style="color: #f56565;"><?php echo $stats_proveedores['deficientes']; ?></div>
                            <div class="entity-stat-label">Deficientes</div>
                        </div>
                        <div class="entity-stat">
                            <div class="entity-stat-value" style="color: #a0aec0;"><?php echo $stats_proveedores['inactivos']; ?></div>
                            <div class="entity-stat-label">Inactivos</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empleados -->
            <div class="col-md-6 mb-3">
                <div class="entity-card empleados" onclick="window.location.href='gestion_empleados.php'">
                    <h5><i class="fas fa-user-tie"></i> Empleados</h5>
                    <div class="entity-total" style="color: #ed8936;"><?php echo number_format($stats_empleados['total']); ?></div>
                    <div class="entity-stats">
                        <div class="entity-stat">
                            <div class="entity-stat-value" style="color: #48bb78;"><?php echo $stats_empleados['activos']; ?></div>
                            <div class="entity-stat-label">Activos</div>
                        </div>
                        <div class="entity-stat">
                            <div class="entity-stat-value" style="color: #667eea;"><?php echo $stats_empleados['departamentos']; ?></div>
                            <div class="entity-stat-label">Departamentos</div>
                        </div>
                        <div class="entity-stat">
                            <div class="entity-stat-value" style="color: #ed8936;"><?php echo $stats_empleados['vacaciones']; ?></div>
                            <div class="entity-stat-label">Vacaciones</div>
                        </div>
                        <div class="entity-stat">
                            <div class="entity-stat-value" style="color: #a0aec0;"><?php echo $stats_empleados['inactivos']; ?></div>
                            <div class="entity-stat-label">Inactivos</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Productos -->
            <div class="col-md-6 mb-3">
                <div class="entity-card productos" onclick="window.location.href='productos_servicios.php'">
                    <h5><i class="fas fa-boxes"></i> Productos y Servicios</h5>
                    <div class="entity-total" style="color: #f56565;"><?php echo number_format($stats_productos['total']); ?></div>
                    <div class="entity-stats">
                        <div class="entity-stat">
                            <div class="entity-stat-value" style="color: #48bb78;"><?php echo $stats_productos['activos']; ?></div>
                            <div class="entity-stat-label">Activos</div>
                        </div>
                        <div class="entity-stat">
                            <div class="entity-stat-value"><?php echo $stats_productos['productos']; ?></div>
                            <div class="entity-stat-label">Productos</div>
                        </div>
                        <div class="entity-stat">
                            <div class="entity-stat-value"><?php echo $stats_productos['servicios']; ?></div>
                            <div class="entity-stat-label">Servicios</div>
                        </div>
                        <div class="entity-stat">
                            <div class="entity-stat-value" style="color: #f56565;"><?php echo $stats_productos['stock_bajo']; ?></div>
                            <div class="entity-stat-label">Stock Bajo</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráficos y Actividad Reciente -->
        <div class="row">
            <!-- Gráfico de Tendencias -->
            <div class="col-md-8 mb-3">
                <div class="content-card">
                    <div class="content-card-header">
                        <h2 class="content-card-title">Tendencia de Creación (Últimos 6 Meses)</h2>
                    </div>
                    <div class="chart-container">
                        <canvas id="chartTendencias"></canvas>
                    </div>
                </div>
            </div>

            <!-- Actividad Reciente -->
            <div class="col-md-4 mb-3">
                <div class="content-card">
                    <div class="content-card-header">
                        <h2 class="content-card-title">Actividad Reciente</h2>
                    </div>
                    <div style="max-height: 350px; overflow-y: auto;">
                        <?php foreach ($ultimas_transacciones as $trans): ?>
                            <div class="timeline-item <?php echo $trans['accion']; ?>">
                                <div class="timeline-time">
                                    <?php echo date('d/m/Y H:i', strtotime($trans['fecha_accion'])); ?>
                                </div>
                                <div class="timeline-content">
                                    <strong><?php echo ucfirst($trans['accion']); ?></strong> en <?php echo $trans['tabla']; ?>
                                    <br>
                                    <small class="text-muted">
                                        por <?php echo htmlspecialchars($trans['nombre'] . ' ' . $trans['apellido']); ?>
                                    </small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Clientes -->
        <div class="content-card mt-3">
            <div class="content-card-header">
                <h2 class="content-card-title">Top 5 Clientes Recientes</h2>
                <a href="gestion_clientes.php" class="btn btn-gradient btn-sm">Ver Todos</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Nombre</th>
                            <th>Clasificación</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_clientes as $cliente): ?>
                        <tr>
                            <td><code><?php echo htmlspecialchars($cliente['codigo']); ?></code></td>
                            <td><?php echo htmlspecialchars($cliente['nombre']); ?></td>
                            <td>
                                <span class="badge" style="background: <?php echo $cliente['clasificacion'] === 'A' ? '#48bb78' : ($cliente['clasificacion'] === 'B' ? '#ed8936' : '#a0aec0'); ?>">
                                    Clase <?php echo $cliente['clasificacion']; ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge" style="background: <?php echo $cliente['estado'] === 'activo' ? '#48bb78' : '#a0aec0'; ?>">
                                    <?php echo ucfirst($cliente['estado']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="gestion_clientes.php" class="btn btn-sm btn-primary">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="../../assets/js/dark_mode.js"></script>

    <script>
        // Gráfico de Tendencias
        const ctx = document.getElementById('chartTendencias');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($meses_labels); ?>,
                datasets: [
                    {
                        label: 'Clientes',
                        data: <?php echo json_encode($clientes_data); ?>,
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        tension: 0.4
                    },
                    {
                        label: 'Proveedores',
                        data: <?php echo json_encode($proveedores_data); ?>,
                        borderColor: '#48bb78',
                        backgroundColor: 'rgba(72, 187, 120, 0.1)',
                        tension: 0.4
                    },
                    {
                        label: 'Productos',
                        data: <?php echo json_encode($productos_data); ?>,
                        borderColor: '#f56565',
                        backgroundColor: 'rgba(245, 101, 101, 0.1)',
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
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

    <!-- Footer Profesional -->
    <?php include '../includes/footer.php'; ?>

</body>
</html>
