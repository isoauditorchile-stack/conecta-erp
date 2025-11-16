<?php
/**
 * CONTABILIDAD GENERAL - CONECTA ERP
 * Dashboard de Contabilidad (CONTA)
 * Nivel Empresarial - Estilo SAP/Softland
 */

session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Verificar autenticación
requireLogin();

// Verificar permisos
$puede_ver = ($_SESSION['es_super_admin'] == 1) || verificarPermiso('contabilidad', 'leer');

if (!$puede_ver) {
    header('Location: ../../user/dashboard.php');
    exit();
}

$tab_activa = $_GET['tab'] ?? 'dashboard';

// ==========================================
// OBTENER DATOS CONTABLES
// ==========================================

// Balance General (Simulado)
$activo_corriente = 15750000;
$activo_no_corriente = 45000000;
$total_activos = $activo_corriente + $activo_no_corriente;

$pasivo_corriente = 8500000;
$pasivo_no_corriente = 22000000;
$total_pasivos = $pasivo_corriente + $pasivo_no_corriente;

$capital = 20000000;
$utilidades_retenidas = 10250000;
$total_patrimonio = $capital + $utilidades_retenidas;

// Estado de Resultados (Simulado)
$ingresos_operacionales = 35000000;
$costos_ventas = 18000000;
$utilidad_bruta = $ingresos_operacionales - $costos_ventas;

$gastos_administrativos = 6000000;
$gastos_ventas = 4500000;
$gastos_financieros = 1250000;
$total_gastos = $gastos_administrativos + $gastos_ventas + $gastos_financieros;

$utilidad_neta = $utilidad_bruta - $total_gastos;

// Ratios Financieros
$ratio_liquidez = $pasivo_corriente > 0 ? ($activo_corriente / $pasivo_corriente) : 0;
$ratio_endeudamiento = $total_activos > 0 ? ($total_pasivos / $total_activos) * 100 : 0;
$margen_bruto = $ingresos_operacionales > 0 ? ($utilidad_bruta / $ingresos_operacionales) * 100 : 0;
$margen_neto = $ingresos_operacionales > 0 ? ($utilidad_neta / $ingresos_operacionales) * 100 : 0;

// Plan de Cuentas (Simplificado)
$plan_cuentas = [
    ['codigo' => '1', 'nombre' => 'ACTIVO', 'tipo' => 'titulo', 'saldo' => $total_activos],
    ['codigo' => '1.1', 'nombre' => 'Activo Corriente', 'tipo' => 'subtitulo', 'saldo' => $activo_corriente],
    ['codigo' => '1.1.01', 'nombre' => 'Caja', 'tipo' => 'cuenta', 'saldo' => 2500000],
    ['codigo' => '1.1.02', 'nombre' => 'Bancos', 'tipo' => 'cuenta', 'saldo' => 8750000],
    ['codigo' => '1.1.03', 'nombre' => 'Cuentas por Cobrar', 'tipo' => 'cuenta', 'saldo' => 4500000],
    ['codigo' => '1.2', 'nombre' => 'Activo No Corriente', 'tipo' => 'subtitulo', 'saldo' => $activo_no_corriente],
    ['codigo' => '1.2.01', 'nombre' => 'Maquinarias y Equipos', 'tipo' => 'cuenta', 'saldo' => 35000000],
    ['codigo' => '1.2.02', 'nombre' => 'Muebles y Enseres', 'tipo' => 'cuenta', 'saldo' => 10000000],
    ['codigo' => '2', 'nombre' => 'PASIVO', 'tipo' => 'titulo', 'saldo' => $total_pasivos],
    ['codigo' => '2.1', 'nombre' => 'Pasivo Corriente', 'tipo' => 'subtitulo', 'saldo' => $pasivo_corriente],
    ['codigo' => '2.1.01', 'nombre' => 'Cuentas por Pagar', 'tipo' => 'cuenta', 'saldo' => 5500000],
    ['codigo' => '2.1.02', 'nombre' => 'Impuestos por Pagar', 'tipo' => 'cuenta', 'saldo' => 3000000],
    ['codigo' => '2.2', 'nombre' => 'Pasivo No Corriente', 'tipo' => 'subtitulo', 'saldo' => $pasivo_no_corriente],
    ['codigo' => '2.2.01', 'nombre' => 'Préstamos Bancarios LP', 'tipo' => 'cuenta', 'saldo' => 22000000],
    ['codigo' => '3', 'nombre' => 'PATRIMONIO', 'tipo' => 'titulo', 'saldo' => $total_patrimonio],
    ['codigo' => '3.1', 'nombre' => 'Capital', 'tipo' => 'cuenta', 'saldo' => $capital],
    ['codigo' => '3.2', 'nombre' => 'Utilidades Retenidas', 'tipo' => 'cuenta', 'saldo' => $utilidades_retenidas],
];

// Libro Diario (Últimas transacciones simuladas)
$libro_diario = [
    ['fecha' => '2025-11-15', 'asiento' => '001', 'cuenta' => '1.1.02 - Bancos', 'debe' => 5000000, 'haber' => 0, 'glosa' => 'Depósito cliente ACME'],
    ['fecha' => '2025-11-15', 'asiento' => '001', 'cuenta' => '4.1.01 - Ventas', 'debe' => 0, 'haber' => 5000000, 'glosa' => 'Depósito cliente ACME'],
    ['fecha' => '2025-11-14', 'asiento' => '002', 'cuenta' => '5.1.01 - Gastos Administrativos', 'debe' => 850000, 'haber' => 0, 'glosa' => 'Pago servicios básicos'],
    ['fecha' => '2025-11-14', 'asiento' => '002', 'cuenta' => '1.1.02 - Bancos', 'debe' => 0, 'haber' => 850000, 'glosa' => 'Pago servicios básicos'],
    ['fecha' => '2025-11-13', 'asiento' => '003', 'cuenta' => '1.1.03 - Cuentas por Cobrar', 'debe' => 2500000, 'haber' => 0, 'glosa' => 'Venta a crédito'],
    ['fecha' => '2025-11-13', 'asiento' => '003', 'cuenta' => '4.1.01 - Ventas', 'debe' => 0, 'haber' => 2500000, 'glosa' => 'Venta a crédito'],
    ['fecha' => '2025-11-12', 'asiento' => '004', 'cuenta' => '2.1.01 - Cuentas por Pagar', 'debe' => 1500000, 'haber' => 0, 'glosa' => 'Pago a proveedor'],
    ['fecha' => '2025-11-12', 'asiento' => '004', 'cuenta' => '1.1.02 - Bancos', 'debe' => 0, 'haber' => 1500000, 'glosa' => 'Pago a proveedor'],
];

// Datos para gráficos
$meses = ['Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
$ingresos_mensuales = [28000000, 30000000, 32000000, 33000000, 35000000, 37000000];
$gastos_mensuales = [22000000, 23000000, 24000000, 25000000, 27000000, 28000000];

// Estadísticas
$stats = [
    'total_activos' => $total_activos,
    'total_pasivos' => $total_pasivos,
    'total_patrimonio' => $total_patrimonio,
    'utilidad_neta' => $utilidad_neta,
    'ingresos_mes' => $ingresos_operacionales,
    'gastos_mes' => $total_gastos,
    'cuentas_plan' => count($plan_cuentas),
    'asientos_mes' => 127
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contabilidad General - CONECTA ERP</title>

    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
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

        .main-content {
            margin-left: var(--sidebar-width);
            margin-top: var(--topbar-height);
            padding: 30px;
            min-height: calc(100vh - var(--topbar-height));
        }

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

        .stats-number {
            font-size: 28px;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 5px;
        }

        .stats-label {
            color: #718096;
            font-size: 14px;
        }

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

        .nav-tabs .nav-link {
            color: #718096;
            border: none;
            padding: 12px 24px;
            font-weight: 600;
            border-bottom: 3px solid transparent;
        }

        .nav-tabs .nav-link:hover {
            color: #667eea;
            border-color: transparent;
        }

        .nav-tabs .nav-link.active {
            color: #667eea;
            border-bottom: 3px solid #667eea;
            background: none;
        }

        .table thead th {
            background: #f5f7fa;
            color: #2d3748;
            font-weight: 600;
            border: none;
        }

        .table tbody tr:hover {
            background: #f5f7fa;
        }

        .cuenta-titulo {
            font-weight: 700;
            background: #e2e8f0;
        }

        .cuenta-subtitulo {
            font-weight: 600;
            background: #f5f7fa;
            padding-left: 20px;
        }

        .cuenta-detalle {
            padding-left: 40px;
        }

        .chart-container {
            position: relative;
            height: 300px;
            margin-top: 20px;
        }

        .balance-section {
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .balance-section h6 {
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e2e8f0;
        }

        .balance-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #f5f7fa;
        }

        .balance-item.total {
            font-weight: 700;
            font-size: 18px;
            border-top: 2px solid #2d3748;
            border-bottom: 3px double #2d3748;
        }

        .ratio-card {
            background: #f5f7fa;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 15px;
        }

        .ratio-value {
            font-size: 32px;
            font-weight: 700;
            color: #667eea;
        }

        .ratio-label {
            color: #718096;
            font-size: 14px;
            margin-top: 5px;
        }

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
            <small style="color: rgba(255,255,255,0.7);">Contabilidad</small>
        </div>

        <ul class="sidebar-menu">
            <li><a href="../dashboard/index.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="contabilidad_general.php" class="active"><i class="fas fa-chart-line"></i> Contabilidad</a></li>
            <li><a href="cuentas_por_pagar.php"><i class="fas fa-file-invoice-dollar"></i> Cuentas por Pagar</a></li>
            <li><a href="../entidades/entidades_maestras.php"><i class="fas fa-database"></i> Entidades</a></li>
            <li><a href="../../user/dashboard.php"><i class="fas fa-arrow-left"></i> Volver</a></li>
        </ul>
    </div>

    <!-- Topbar -->
    <div class="topbar">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard/index.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="#">Finanzas</a></li>
                <li class="breadcrumb-item active">Contabilidad General</li>
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
                <h1 class="h3 mb-0">Contabilidad General</h1>
                <p class="text-muted mb-0">Información financiera y contable de la empresa</p>
            </div>
            <button class="btn btn-gradient">
                <i class="fas fa-file-export"></i> Exportar Reportes
            </button>
        </div>

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-icon blue">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <div class="stats-number">$<?php echo number_format($total_activos / 1000000, 1); ?>M</div>
                    <div class="stats-label">Total Activos</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-icon orange">
                        <i class="fas fa-file-invoice"></i>
                    </div>
                    <div class="stats-number">$<?php echo number_format($total_pasivos / 1000000, 1); ?>M</div>
                    <div class="stats-label">Total Pasivos</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-icon green">
                        <i class="fas fa-hand-holding-usd"></i>
                    </div>
                    <div class="stats-number">$<?php echo number_format($total_patrimonio / 1000000, 1); ?>M</div>
                    <div class="stats-label">Patrimonio</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-icon red">
                        <i class="fas fa-chart-pie"></i>
                    </div>
                    <div class="stats-number">$<?php echo number_format($utilidad_neta / 1000000, 1); ?>M</div>
                    <div class="stats-label">Utilidad Neta</div>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="content-card">
            <ul class="nav nav-tabs mb-4" role="tablist">
                <li class="nav-item">
                    <a class="nav-link <?php echo $tab_activa === 'dashboard' ? 'active' : ''; ?>" href="?tab=dashboard">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $tab_activa === 'plan_cuentas' ? 'active' : ''; ?>" href="?tab=plan_cuentas">
                        <i class="fas fa-list"></i> Plan de Cuentas
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $tab_activa === 'libro_diario' ? 'active' : ''; ?>" href="?tab=libro_diario">
                        <i class="fas fa-book"></i> Libro Diario
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $tab_activa === 'balance' ? 'active' : ''; ?>" href="?tab=balance">
                        <i class="fas fa-balance-scale"></i> Balance General
                    </a>
                </li>
            </ul>

            <!-- TAB: Dashboard -->
            <?php if ($tab_activa === 'dashboard'): ?>
                <div class="row">
                    <!-- Gráfico de Ingresos vs Gastos -->
                    <div class="col-md-8 mb-3">
                        <div class="content-card-header">
                            <h2 class="content-card-title">Ingresos vs Gastos (Últimos 6 Meses)</h2>
                        </div>
                        <div class="chart-container">
                            <canvas id="chartIngresosGastos"></canvas>
                        </div>
                    </div>

                    <!-- Ratios Financieros -->
                    <div class="col-md-4 mb-3">
                        <div class="content-card-header">
                            <h2 class="content-card-title">Ratios Financieros</h2>
                        </div>
                        <div class="ratio-card">
                            <div class="ratio-value"><?php echo number_format($ratio_liquidez, 2); ?></div>
                            <div class="ratio-label">Ratio de Liquidez</div>
                        </div>
                        <div class="ratio-card">
                            <div class="ratio-value"><?php echo number_format($ratio_endeudamiento, 1); ?>%</div>
                            <div class="ratio-label">Endeudamiento</div>
                        </div>
                        <div class="ratio-card">
                            <div class="ratio-value"><?php echo number_format($margen_bruto, 1); ?>%</div>
                            <div class="ratio-label">Margen Bruto</div>
                        </div>
                        <div class="ratio-card">
                            <div class="ratio-value"><?php echo number_format($margen_neto, 1); ?>%</div>
                            <div class="ratio-label">Margen Neto</div>
                        </div>
                    </div>
                </div>

                <!-- Estado de Resultados Resumido -->
                <div class="row mt-3">
                    <div class="col-md-12">
                        <div class="balance-section">
                            <h6>Estado de Resultados - Mes Actual</h6>
                            <div class="balance-item">
                                <span>Ingresos Operacionales</span>
                                <strong>$<?php echo number_format($ingresos_operacionales, 0, ',', '.'); ?></strong>
                            </div>
                            <div class="balance-item">
                                <span>(-) Costo de Ventas</span>
                                <strong>$<?php echo number_format($costos_ventas, 0, ',', '.'); ?></strong>
                            </div>
                            <div class="balance-item total">
                                <span>Utilidad Bruta</span>
                                <strong>$<?php echo number_format($utilidad_bruta, 0, ',', '.'); ?></strong>
                            </div>
                            <div class="balance-item">
                                <span>(-) Gastos Operacionales</span>
                                <strong>$<?php echo number_format($total_gastos, 0, ',', '.'); ?></strong>
                            </div>
                            <div class="balance-item total" style="color: <?php echo $utilidad_neta >= 0 ? '#48bb78' : '#f56565'; ?>">
                                <span>Utilidad Neta</span>
                                <strong>$<?php echo number_format($utilidad_neta, 0, ',', '.'); ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- TAB: Plan de Cuentas -->
            <?php if ($tab_activa === 'plan_cuentas'): ?>
                <div class="content-card-header">
                    <h2 class="content-card-title">Plan de Cuentas Contable</h2>
                    <button class="btn btn-gradient btn-sm">
                        <i class="fas fa-plus"></i> Nueva Cuenta
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Nombre de Cuenta</th>
                                <th>Tipo</th>
                                <th>Saldo</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($plan_cuentas as $cuenta): ?>
                            <tr class="<?php echo 'cuenta-' . $cuenta['tipo']; ?>">
                                <td><strong><?php echo $cuenta['codigo']; ?></strong></td>
                                <td><?php echo $cuenta['nombre']; ?></td>
                                <td><?php echo ucfirst($cuenta['tipo']); ?></td>
                                <td><strong>$<?php echo number_format($cuenta['saldo'], 0, ',', '.'); ?></strong></td>
                                <td>
                                    <?php if ($cuenta['tipo'] === 'cuenta'): ?>
                                        <button class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <!-- TAB: Libro Diario -->
            <?php if ($tab_activa === 'libro_diario'): ?>
                <div class="content-card-header">
                    <h2 class="content-card-title">Libro Diario - Últimas Transacciones</h2>
                    <button class="btn btn-gradient btn-sm">
                        <i class="fas fa-plus"></i> Nuevo Asiento
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-sm" id="tablaLibroDiario">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Asiento</th>
                                <th>Cuenta</th>
                                <th>Glosa</th>
                                <th>Debe</th>
                                <th>Haber</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($libro_diario as $asiento): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($asiento['fecha'])); ?></td>
                                <td><code><?php echo $asiento['asiento']; ?></code></td>
                                <td><?php echo $asiento['cuenta']; ?></td>
                                <td><?php echo $asiento['glosa']; ?></td>
                                <td><strong><?php echo $asiento['debe'] > 0 ? '$' . number_format($asiento['debe'], 0, ',', '.') : '-'; ?></strong></td>
                                <td><strong><?php echo $asiento['haber'] > 0 ? '$' . number_format($asiento['haber'], 0, ',', '.') : '-'; ?></strong></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <!-- TAB: Balance General -->
            <?php if ($tab_activa === 'balance'): ?>
                <div class="content-card-header">
                    <h2 class="content-card-title">Balance General</h2>
                    <span class="text-muted">Al <?php echo date('d/m/Y'); ?></span>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="balance-section">
                            <h6>ACTIVOS</h6>
                            <div class="balance-item">
                                <span>Activo Corriente</span>
                                <strong>$<?php echo number_format($activo_corriente, 0, ',', '.'); ?></strong>
                            </div>
                            <div class="balance-item">
                                <span>Activo No Corriente</span>
                                <strong>$<?php echo number_format($activo_no_corriente, 0, ',', '.'); ?></strong>
                            </div>
                            <div class="balance-item total">
                                <span>TOTAL ACTIVOS</span>
                                <strong>$<?php echo number_format($total_activos, 0, ',', '.'); ?></strong>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="balance-section">
                            <h6>PASIVOS Y PATRIMONIO</h6>
                            <div class="balance-item">
                                <span>Pasivo Corriente</span>
                                <strong>$<?php echo number_format($pasivo_corriente, 0, ',', '.'); ?></strong>
                            </div>
                            <div class="balance-item">
                                <span>Pasivo No Corriente</span>
                                <strong>$<?php echo number_format($pasivo_no_corriente, 0, ',', '.'); ?></strong>
                            </div>
                            <div class="balance-item">
                                <span>Patrimonio</span>
                                <strong>$<?php echo number_format($total_patrimonio, 0, ',', '.'); ?></strong>
                            </div>
                            <div class="balance-item total">
                                <span>TOTAL PASIVO + PATRIMONIO</span>
                                <strong>$<?php echo number_format($total_pasivos + $total_patrimonio, 0, ',', '.'); ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="../../assets/js/dark_mode.js"></script>

    <script>
        // DataTable
        $('#tablaLibroDiario').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
            },
            order: [[0, 'desc']],
            pageLength: 25
        });

        // Gráfico Ingresos vs Gastos
        <?php if ($tab_activa === 'dashboard'): ?>
        const ctx = document.getElementById('chartIngresosGastos');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($meses); ?>,
                datasets: [
                    {
                        label: 'Ingresos',
                        data: <?php echo json_encode($ingresos_mensuales); ?>,
                        borderColor: '#48bb78',
                        backgroundColor: 'rgba(72, 187, 120, 0.1)',
                        tension: 0.4
                    },
                    {
                        label: 'Gastos',
                        data: <?php echo json_encode($gastos_mensuales); ?>,
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
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + (value / 1000000).toFixed(0) + 'M';
                            }
                        }
                    }
                }
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>
