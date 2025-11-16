<?php
/**
 * DASHBOARD USUARIO - CONECTA ERP
 * Dashboard principal con sidebar colapsable de todos los módulos
 * Nivel Empresarial - Estilo SAP/Softland
 */

session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/dashboard_functions.php';

// Verificar autenticación
requireLogin();

$usuario_id = $_SESSION['usuario_id'];

// Obtener datos del usuario
$stmt = $conn->prepare("SELECT u.*, e.nombre as nombre_empresa, p.nombre as plan_nombre
                        FROM usuarios u
                        LEFT JOIN empresas e ON u.empresa_id = e.id
                        LEFT JOIN planes p ON u.plan_id = p.id
                        WHERE u.id = ?");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();
$stmt->close();

$empresa_id = $usuario['empresa_id'];

// Calcular días restantes de trial
$dias_restantes_trial = 0;
$mostrar_alerta_trial = false;
if ($usuario['en_periodo_prueba'] == 1) {
    $fecha_fin = strtotime($usuario['fecha_fin_trial']);
    $hoy = time();
    $dias_restantes_trial = ceil(($fecha_fin - $hoy) / 86400);
    $mostrar_alerta_trial = ($dias_restantes_trial <= 3 && $dias_restantes_trial > 0);
}

// Obtener notificaciones no leídas (usando función)
$notificaciones_count = getContadorNotificaciones($conn, $usuario_id, $empresa_id);
$notificaciones = getNotificacionesPendientes($conn, $usuario_id, $empresa_id);

// Obtener estadísticas desde la base de datos (SIN datos hardcodeados)
$stats = getDashboardStats($conn, $empresa_id);
$trends = getDashboardTrends($conn, $empresa_id);

// Obtener información adicional
$empresa_info = getEmpresaInfo($conn, $empresa_id);
$actividad_reciente = getActividadReciente($conn, $usuario_id, $empresa_id, 5);
$accesos_rapidos = getAccesosRapidos($conn, $usuario_id);
$productos_stock_bajo = getProductosStockBajo($conn, $empresa_id, 5);
$cuentas_cobrar = getCuentasPorCobrarVencidas($conn, $empresa_id);
$cuentas_pagar = getCuentasPorPagarProximas($conn, $empresa_id);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - CONECTA ERP</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/dark_mode.css">

    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --sidebar-width: 280px;
            --topbar-height: 70px;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            overflow-x: hidden;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: var(--primary-gradient);
            color: white;
            z-index: 1050;
            overflow-y: auto;
            overflow-x: hidden;
            box-shadow: 4px 0 10px rgba(0,0,0,0.1);
        }

        .sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.3);
            border-radius: 3px;
        }

        .sidebar-header {
            padding: 25px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
        }

        .sidebar-header h2 {
            font-size: 24px;
            font-weight: 700;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .sidebar-header .empresa-badge {
            background: rgba(255,255,255,0.2);
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 11px;
            margin-top: 8px;
            display: inline-block;
        }

        /* Menú Principal */
        .sidebar-menu {
            list-style: none;
            padding: 15px 0;
            margin: 0;
        }

        .menu-item {
            margin: 3px 10px;
        }

        .menu-link {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 15px;
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s;
            cursor: pointer;
        }

        .menu-link:hover {
            background: rgba(255, 255, 255, 0.15);
            color: white;
        }

        .menu-link.active {
            background: rgba(255, 255, 255, 0.25);
            color: white;
            font-weight: 600;
        }

        .menu-link-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .menu-icon {
            width: 22px;
            text-align: center;
            font-size: 18px;
        }

        .menu-title {
            font-size: 14px;
            font-weight: 500;
        }

        .menu-badge {
            background: rgba(255,255,255,0.3);
            color: white;
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 10px;
            font-weight: 600;
        }

        .menu-arrow {
            transition: transform 0.3s;
            font-size: 12px;
        }

        .menu-link[aria-expanded="true"] .menu-arrow {
            transform: rotate(90deg);
        }

        /* Submenú */
        .submenu {
            list-style: none;
            padding: 5px 0 10px 0;
            margin: 0;
            background: rgba(0, 0, 0, 0.1);
            border-radius: 0 0 8px 8px;
        }

        .submenu-item {
            margin: 2px 10px;
        }

        .submenu-link {
            display: flex;
            align-items: center;
            padding: 10px 15px 10px 50px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            font-size: 13px;
            border-radius: 6px;
            transition: all 0.2s;
        }

        .submenu-link:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            padding-left: 55px;
        }

        .submenu-link.active {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            font-weight: 600;
        }

        .submenu-icon {
            width: 18px;
            margin-right: 8px;
            font-size: 12px;
        }

        /* Topbar */
        .topbar {
            position: fixed;
            left: var(--sidebar-width);
            top: 0;
            right: 0;
            height: var(--topbar-height);
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
            z-index: 1040;
        }

        .topbar-left h1 {
            font-size: 24px;
            font-weight: 700;
            color: #2d3748;
            margin: 0;
        }

        .topbar-subtitle {
            color: #718096;
            font-size: 14px;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .topbar-btn {
            background: none;
            border: none;
            color: #718096;
            font-size: 20px;
            cursor: pointer;
            position: relative;
            transition: color 0.3s;
        }

        .topbar-btn:hover {
            color: #667eea;
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #f56565;
            color: white;
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 10px;
            font-weight: 700;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            padding: 8px 15px;
            border-radius: 8px;
            transition: background 0.3s;
        }

        .user-menu:hover {
            background: #f5f7fa;
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
            font-weight: 700;
            font-size: 16px;
        }

        .user-info {
            text-align: left;
        }

        .user-name {
            font-weight: 600;
            color: #2d3748;
            font-size: 14px;
        }

        .user-role {
            font-size: 12px;
            color: #718096;
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
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s, box-shadow 0.2s;
            border-left: 4px solid transparent;
        }

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .stats-card.blue { border-left-color: #667eea; }
        .stats-card.green { border-left-color: #48bb78; }
        .stats-card.orange { border-left-color: #ed8936; }
        .stats-card.purple { border-left-color: #9f7aea; }

        .stats-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            margin-bottom: 15px;
        }

        .stats-icon.blue { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .stats-icon.green { background: linear-gradient(135deg, #48bb78 0%, #38a169 100%); color: white; }
        .stats-icon.orange { background: linear-gradient(135deg, #ed8936 0%, #dd6b20 100%); color: white; }
        .stats-icon.purple { background: linear-gradient(135deg, #9f7aea 0%, #805ad5 100%); color: white; }

        .stats-number {
            font-size: 32px;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 5px;
        }

        .stats-label {
            color: #718096;
            font-size: 14px;
            font-weight: 500;
        }

        .stats-trend {
            font-size: 12px;
            margin-top: 10px;
        }

        .stats-trend.up {
            color: #48bb78;
        }

        .stats-trend.down {
            color: #f56565;
        }

        /* Welcome Card */
        .welcome-card {
            background: var(--primary-gradient);
            color: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.3);
            margin-bottom: 30px;
        }

        .welcome-card h2 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .welcome-card p {
            font-size: 16px;
            opacity: 0.9;
        }

        /* Alert Trial */
        .alert-trial {
            background: #fffaf0;
            border-left: 4px solid #ed8936;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                left: -280px;
            }

            .sidebar.active {
                left: 0;
            }

            .topbar {
                left: 0;
            }

            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>
                <i class="fas fa-rocket"></i>
                <span>CONECTA ERP</span>
            </h2>
            <div class="empresa-badge"><?php echo htmlspecialchars($usuario['nombre_empresa']); ?></div>
        </div>

        <ul class="sidebar-menu">
            <!-- Dashboard -->
            <li class="menu-item">
                <a href="dashboard.php" class="menu-link active">
                    <div class="menu-link-left">
                        <i class="fas fa-home menu-icon"></i>
                        <span class="menu-title">Dashboard</span>
                    </div>
                </a>
            </li>

            <!-- MÓDULO 1: Administración -->
            <li class="menu-item">
                <a class="menu-link" data-bs-toggle="collapse" href="#menuAdministracion" role="button">
                    <div class="menu-link-left">
                        <i class="fas fa-cog menu-icon"></i>
                        <span class="menu-title">Administración</span>
                    </div>
                    <div>
                        <span class="menu-badge">3</span>
                        <i class="fas fa-chevron-right menu-arrow"></i>
                    </div>
                </a>
                <div class="collapse" id="menuAdministracion">
                    <ul class="submenu">
                        <li class="submenu-item">
                            <a href="../modulos/administracion/gestion_empresas.php" class="submenu-link">
                                <i class="fas fa-building submenu-icon"></i> Gestión de Empresas
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/administracion/gestion_seguridad.php" class="submenu-link">
                                <i class="fas fa-shield-alt submenu-icon"></i> Gestión de Seguridad
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/administracion/parametrizacion_global.php" class="submenu-link">
                                <i class="fas fa-sliders-h submenu-icon"></i> Parametrización Global
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <!-- MÓDULO 2: Entidades -->
            <li class="menu-item">
                <a class="menu-link" data-bs-toggle="collapse" href="#menuEntidades" role="button">
                    <div class="menu-link-left">
                        <i class="fas fa-database menu-icon"></i>
                        <span class="menu-title">Entidades</span>
                    </div>
                    <div>
                        <span class="menu-badge">5</span>
                        <i class="fas fa-chevron-right menu-arrow"></i>
                    </div>
                </a>
                <div class="collapse" id="menuEntidades">
                    <ul class="submenu">
                        <li class="submenu-item">
                            <a href="../modulos/entidades/entidades_maestras.php" class="submenu-link">
                                <i class="fas fa-th submenu-icon"></i> Dashboard Entidades
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/entidades/gestion_clientes.php" class="submenu-link">
                                <i class="fas fa-users submenu-icon"></i> Clientes
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/entidades/gestion_proveedores.php" class="submenu-link">
                                <i class="fas fa-truck submenu-icon"></i> Proveedores
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/entidades/gestion_empleados.php" class="submenu-link">
                                <i class="fas fa-user-tie submenu-icon"></i> Empleados
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/entidades/productos_servicios.php" class="submenu-link">
                                <i class="fas fa-box submenu-icon"></i> Productos y Servicios
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <!-- MÓDULO 3: Finanzas (FI) -->
            <li class="menu-item">
                <a class="menu-link" data-bs-toggle="collapse" href="#menuFinanzas" role="button">
                    <div class="menu-link-left">
                        <i class="fas fa-dollar-sign menu-icon"></i>
                        <span class="menu-title">Finanzas (FI)</span>
                    </div>
                    <div>
                        <span class="menu-badge">11</span>
                        <i class="fas fa-chevron-right menu-arrow"></i>
                    </div>
                </a>
                <div class="collapse" id="menuFinanzas">
                    <ul class="submenu">
                        <li class="submenu-item">
                            <a href="../modulos/finanzas/contabilidad_general.php" class="submenu-link">
                                <i class="fas fa-book submenu-icon"></i> Contabilidad General
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/finanzas/cuentas_por_pagar.php" class="submenu-link">
                                <i class="fas fa-file-invoice-dollar submenu-icon"></i> Cuentas por Pagar
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/finanzas/cuentas_por_cobrar.php" class="submenu-link">
                                <i class="fas fa-hand-holding-usd submenu-icon"></i> Cuentas por Cobrar
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/finanzas/tesoreria.php" class="submenu-link">
                                <i class="fas fa-university submenu-icon"></i> Tesorería
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/finanzas/activos_fijos.php" class="submenu-link">
                                <i class="fas fa-building submenu-icon"></i> Activos Fijos
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/finanzas/comprobantes_facturas.php" class="submenu-link">
                                <i class="fas fa-file-alt submenu-icon"></i> Comprobantes y Facturas
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/finanzas/ifrs.php" class="submenu-link">
                                <i class="fas fa-balance-scale submenu-icon"></i> IFRS
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/finanzas/consolidacion.php" class="submenu-link">
                                <i class="fas fa-layer-group submenu-icon"></i> Consolidación
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/finanzas/reporting_financiero.php" class="submenu-link">
                                <i class="fas fa-chart-line submenu-icon"></i> Reporting Financiero
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/finanzas/presupuestos.php" class="submenu-link">
                                <i class="fas fa-calculator submenu-icon"></i> Presupuestos
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/finanzas/impuestos.php" class="submenu-link">
                                <i class="fas fa-percent submenu-icon"></i> Impuestos
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <!-- MÓDULO 4: Controlling (CO) -->
            <li class="menu-item">
                <a class="menu-link" data-bs-toggle="collapse" href="#menuControlling" role="button">
                    <div class="menu-link-left">
                        <i class="fas fa-chart-pie menu-icon"></i>
                        <span class="menu-title">Controlling (CO)</span>
                    </div>
                    <div>
                        <span class="menu-badge">8</span>
                        <i class="fas fa-chevron-right menu-arrow"></i>
                    </div>
                </a>
                <div class="collapse" id="menuControlling">
                    <ul class="submenu">
                        <li class="submenu-item">
                            <a href="../modulos/controlling/dashboard_controlling.php" class="submenu-link">
                                <i class="fas fa-tachometer-alt submenu-icon"></i> Dashboard CO
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/controlling/centros_costo.php" class="submenu-link">
                                <i class="fas fa-sitemap submenu-icon"></i> Centros de Costo
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/controlling/rentabilidad.php" class="submenu-link">
                                <i class="fas fa-chart-bar submenu-icon"></i> Análisis Rentabilidad
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/controlling/ordenes_internas.php" class="submenu-link">
                                <i class="fas fa-tasks submenu-icon"></i> Órdenes Internas
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/controlling/proyectos.php" class="submenu-link">
                                <i class="fas fa-project-diagram submenu-icon"></i> Contabilidad Proyectos
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/controlling/gastos_generales.php" class="submenu-link">
                                <i class="fas fa-money-check-alt submenu-icon"></i> Gastos Generales
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/controlling/costos_producto.php" class="submenu-link">
                                <i class="fas fa-tags submenu-icon"></i> Costos del Producto
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/controlling/resultados.php" class="submenu-link">
                                <i class="fas fa-file-invoice submenu-icon"></i> Análisis Resultados
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <!-- MÓDULO 5: Ventas (SD) -->
            <li class="menu-item">
                <a class="menu-link" data-bs-toggle="collapse" href="#menuVentas" role="button">
                    <div class="menu-link-left">
                        <i class="fas fa-shopping-cart menu-icon"></i>
                        <span class="menu-title">Ventas (SD)</span>
                    </div>
                    <div>
                        <span class="menu-badge">9</span>
                        <i class="fas fa-chevron-right menu-arrow"></i>
                    </div>
                </a>
                <div class="collapse" id="menuVentas">
                    <ul class="submenu">
                        <li class="submenu-item">
                            <a href="../modulos/ventas/dashboard_ventas.php" class="submenu-link">
                                <i class="fas fa-tachometer-alt submenu-icon"></i> Dashboard Ventas
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/ventas/pedidos.php" class="submenu-link">
                                <i class="fas fa-clipboard-list submenu-icon"></i> Pedidos
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/ventas/facturacion.php" class="submenu-link">
                                <i class="fas fa-file-invoice submenu-icon"></i> Facturación
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/ventas/pos.php" class="submenu-link">
                                <i class="fas fa-cash-register submenu-icon"></i> Punto de Venta (POS)
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/ventas/config_cajas.php" class="submenu-link">
                                <i class="fas fa-cog submenu-icon"></i> Configuración Cajas
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/ventas/precios.php" class="submenu-link">
                                <i class="fas fa-tag submenu-icon"></i> Gestión de Precios
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/ventas/analisis.php" class="submenu-link">
                                <i class="fas fa-chart-area submenu-icon"></i> Análisis de Ventas
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/ventas/devoluciones.php" class="submenu-link">
                                <i class="fas fa-undo submenu-icon"></i> Devoluciones
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/ventas/promociones.php" class="submenu-link">
                                <i class="fas fa-gift submenu-icon"></i> Promociones
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <!-- MÓDULO 6: Materiales (MM) -->
            <li class="menu-item">
                <a class="menu-link" data-bs-toggle="collapse" href="#menuMateriales" role="button">
                    <div class="menu-link-left">
                        <i class="fas fa-boxes menu-icon"></i>
                        <span class="menu-title">Materiales (MM)</span>
                    </div>
                    <div>
                        <span class="menu-badge">8</span>
                        <i class="fas fa-chevron-right menu-arrow"></i>
                    </div>
                </a>
                <div class="collapse" id="menuMateriales">
                    <ul class="submenu">
                        <li class="submenu-item">
                            <a href="../modulos/materiales/inventario.php" class="submenu-link">
                                <i class="fas fa-warehouse submenu-icon"></i> Inventario
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/materiales/compras.php" class="submenu-link">
                                <i class="fas fa-shopping-basket submenu-icon"></i> Compras
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/materiales/almacenes.php" class="submenu-link">
                                <i class="fas fa-building submenu-icon"></i> Gestión Almacenes
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/materiales/verificacion_facturas.php" class="submenu-link">
                                <i class="fas fa-check-double submenu-icon"></i> Verificación Facturas
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/materiales/planificacion.php" class="submenu-link">
                                <i class="fas fa-calendar-alt submenu-icon"></i> Planificación Necesidades
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/materiales/calidad.php" class="submenu-link">
                                <i class="fas fa-star submenu-icon"></i> Gestión Calidad
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/materiales/valoracion.php" class="submenu-link">
                                <i class="fas fa-dollar-sign submenu-icon"></i> Valoración Inventario
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/materiales/analisis_compras.php" class="submenu-link">
                                <i class="fas fa-chart-line submenu-icon"></i> Análisis Compras
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <!-- MÓDULO 7: Producción (PP) -->
            <li class="menu-item">
                <a class="menu-link" data-bs-toggle="collapse" href="#menuProduccion" role="button">
                    <div class="menu-link-left">
                        <i class="fas fa-industry menu-icon"></i>
                        <span class="menu-title">Producción (PP)</span>
                    </div>
                    <div>
                        <span class="menu-badge">10</span>
                        <i class="fas fa-chevron-right menu-arrow"></i>
                    </div>
                </a>
                <div class="collapse" id="menuProduccion">
                    <ul class="submenu">
                        <li class="submenu-item">
                            <a href="../modulos/produccion/ordenes.php" class="submenu-link">
                                <i class="fas fa-file-alt submenu-icon"></i> Órdenes de Producción
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/produccion/mrp.php" class="submenu-link">
                                <i class="fas fa-cogs submenu-icon"></i> MRP
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/produccion/planificacion.php" class="submenu-link">
                                <i class="fas fa-calendar-check submenu-icon"></i> Planificación
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/produccion/control_planta.php" class="submenu-link">
                                <i class="fas fa-th-large submenu-icon"></i> Control de Planta
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/produccion/calidad.php" class="submenu-link">
                                <i class="fas fa-check-circle submenu-icon"></i> Gestión Calidad
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/produccion/mantenimiento.php" class="submenu-link">
                                <i class="fas fa-tools submenu-icon"></i> Mantenimiento
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/produccion/recursos.php" class="submenu-link">
                                <i class="fas fa-users-cog submenu-icon"></i> Gestión Recursos
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/produccion/optimizacion.php" class="submenu-link">
                                <i class="fas fa-tachometer-alt submenu-icon"></i> Optimización Procesos
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/produccion/costos.php" class="submenu-link">
                                <i class="fas fa-money-bill-wave submenu-icon"></i> Costos Producción
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/produccion/trazabilidad.php" class="submenu-link">
                                <i class="fas fa-route submenu-icon"></i> Trazabilidad
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <!-- MÓDULO 8: RRHH (HCM) -->
            <li class="menu-item">
                <a class="menu-link" data-bs-toggle="collapse" href="#menuRRHH" role="button">
                    <div class="menu-link-left">
                        <i class="fas fa-users menu-icon"></i>
                        <span class="menu-title">RRHH (HCM)</span>
                    </div>
                    <div>
                        <span class="menu-badge">11</span>
                        <i class="fas fa-chevron-right menu-arrow"></i>
                    </div>
                </a>
                <div class="collapse" id="menuRRHH">
                    <ul class="submenu">
                        <li class="submenu-item">
                            <a href="../modulos/rrhh/personal.php" class="submenu-link">
                                <i class="fas fa-user-friends submenu-icon"></i> Personal
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/rrhh/nomina.php" class="submenu-link">
                                <i class="fas fa-file-invoice-dollar submenu-icon"></i> Nómina
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/rrhh/reclutamiento.php" class="submenu-link">
                                <i class="fas fa-user-plus submenu-icon"></i> Reclutamiento
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/rrhh/desempeno.php" class="submenu-link">
                                <i class="fas fa-chart-line submenu-icon"></i> Evaluación Desempeño
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/rrhh/capacitacion.php" class="submenu-link">
                                <i class="fas fa-graduation-cap submenu-icon"></i> Capacitación
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/rrhh/talento.php" class="submenu-link">
                                <i class="fas fa-trophy submenu-icon"></i> Gestión Talento
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/rrhh/beneficios.php" class="submenu-link">
                                <i class="fas fa-gift submenu-icon"></i> Beneficios
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/rrhh/asistencia.php" class="submenu-link">
                                <i class="fas fa-clock submenu-icon"></i> Control Asistencia
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/rrhh/vacaciones.php" class="submenu-link">
                                <i class="fas fa-umbrella-beach submenu-icon"></i> Vacaciones y Licencias
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/rrhh/salud_ocupacional.php" class="submenu-link">
                                <i class="fas fa-heartbeat submenu-icon"></i> Salud Ocupacional
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/rrhh/analisis.php" class="submenu-link">
                                <i class="fas fa-chart-bar submenu-icon"></i> Análisis Personal
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <!-- MÓDULO 9: SCM -->
            <li class="menu-item">
                <a class="menu-link" data-bs-toggle="collapse" href="#menuSCM" role="button">
                    <div class="menu-link-left">
                        <i class="fas fa-shipping-fast menu-icon"></i>
                        <span class="menu-title">SCM</span>
                    </div>
                    <div>
                        <span class="menu-badge">10</span>
                        <i class="fas fa-chevron-right menu-arrow"></i>
                    </div>
                </a>
                <div class="collapse" id="menuSCM">
                    <ul class="submenu">
                        <li class="submenu-item">
                            <a href="../modulos/scm/logistica.php" class="submenu-link">
                                <i class="fas fa-dolly submenu-icon"></i> Logística
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/scm/transporte.php" class="submenu-link">
                                <i class="fas fa-truck submenu-icon"></i> Transporte
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/scm/rutas.php" class="submenu-link">
                                <i class="fas fa-route submenu-icon"></i> Gestión Rutas
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/scm/entregas.php" class="submenu-link">
                                <i class="fas fa-shipping-fast submenu-icon"></i> Planificación Entregas
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/scm/flotilla.php" class="submenu-link">
                                <i class="fas fa-car submenu-icon"></i> Control Flotilla
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/scm/optimizacion_rutas.php" class="submenu-link">
                                <i class="fas fa-map-marked-alt submenu-icon"></i> Optimización Rutas
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/scm/almacenes_scm.php" class="submenu-link">
                                <i class="fas fa-warehouse submenu-icon"></i> Almacenes SCM
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/scm/trazabilidad_envios.php" class="submenu-link">
                                <i class="fas fa-search-location submenu-icon"></i> Trazabilidad Envíos
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/scm/analisis_cadena.php" class="submenu-link">
                                <i class="fas fa-chart-area submenu-icon"></i> Análisis Cadena
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/scm/proveedores_scm.php" class="submenu-link">
                                <i class="fas fa-handshake submenu-icon"></i> Proveedores SCM
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <!-- MÓDULO 10: CRM -->
            <li class="menu-item">
                <a class="menu-link" data-bs-toggle="collapse" href="#menuCRM" role="button">
                    <div class="menu-link-left">
                        <i class="fas fa-user-friends menu-icon"></i>
                        <span class="menu-title">CRM</span>
                    </div>
                    <div>
                        <span class="menu-badge">8</span>
                        <i class="fas fa-chevron-right menu-arrow"></i>
                    </div>
                </a>
                <div class="collapse" id="menuCRM">
                    <ul class="submenu">
                        <li class="submenu-item">
                            <a href="../modulos/crm/dashboard_crm.php" class="submenu-link">
                                <i class="fas fa-tachometer-alt submenu-icon"></i> Dashboard CRM
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/crm/leads.php" class="submenu-link">
                                <i class="fas fa-user-plus submenu-icon"></i> Leads
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/crm/oportunidades.php" class="submenu-link">
                                <i class="fas fa-bullseye submenu-icon"></i> Oportunidades
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/crm/pipeline.php" class="submenu-link">
                                <i class="fas fa-stream submenu-icon"></i> Pipeline Ventas
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/crm/contactos.php" class="submenu-link">
                                <i class="fas fa-address-book submenu-icon"></i> Contactos
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/crm/actividades.php" class="submenu-link">
                                <i class="fas fa-tasks submenu-icon"></i> Actividades
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/crm/campanas.php" class="submenu-link">
                                <i class="fas fa-bullhorn submenu-icon"></i> Campañas
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/crm/cuentas.php" class="submenu-link">
                                <i class="fas fa-building submenu-icon"></i> Cuentas
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <!-- MÓDULO 11: Fidelización -->
            <li class="menu-item">
                <a class="menu-link" data-bs-toggle="collapse" href="#menuFidelizacion" role="button">
                    <div class="menu-link-left">
                        <i class="fas fa-heart menu-icon"></i>
                        <span class="menu-title">Fidelización</span>
                    </div>
                    <div>
                        <span class="menu-badge">7</span>
                        <i class="fas fa-chevron-right menu-arrow"></i>
                    </div>
                </a>
                <div class="collapse" id="menuFidelizacion">
                    <ul class="submenu">
                        <li class="submenu-item">
                            <a href="../modulos/fidelizacion/programas_lealtad.php" class="submenu-link">
                                <i class="fas fa-award submenu-icon"></i> Programas Lealtad
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/fidelizacion/sistema_puntos.php" class="submenu-link">
                                <i class="fas fa-star submenu-icon"></i> Sistema Puntos
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/fidelizacion/recompensas.php" class="submenu-link">
                                <i class="fas fa-gift submenu-icon"></i> Recompensas
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/fidelizacion/campanas_personalizadas.php" class="submenu-link">
                                <i class="fas fa-envelope submenu-icon"></i> Campañas Personalizadas
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/fidelizacion/analisis_fidelizacion.php" class="submenu-link">
                                <i class="fas fa-chart-pie submenu-icon"></i> Análisis Fidelización
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/fidelizacion/comentarios.php" class="submenu-link">
                                <i class="fas fa-comments submenu-icon"></i> Comentarios Clientes
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/fidelizacion/segmentacion.php" class="submenu-link">
                                <i class="fas fa-th-list submenu-icon"></i> Segmentación
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <!-- MÓDULO 12: Business Intelligence -->
            <li class="menu-item">
                <a class="menu-link" data-bs-toggle="collapse" href="#menuBI" role="button">
                    <div class="menu-link-left">
                        <i class="fas fa-chart-line menu-icon"></i>
                        <span class="menu-title">Business Intelligence</span>
                    </div>
                    <div>
                        <span class="menu-badge">15</span>
                        <i class="fas fa-chevron-right menu-arrow"></i>
                    </div>
                </a>
                <div class="collapse" id="menuBI">
                    <ul class="submenu">
                        <li class="submenu-item">
                            <a href="../modulos/bi/dashboards.php" class="submenu-link">
                                <i class="fas fa-tv submenu-icon"></i> Dashboards
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/bi/kpis.php" class="submenu-link">
                                <i class="fas fa-tachometer-alt submenu-icon"></i> KPIs
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/bi/informes_ejecutivos.php" class="submenu-link">
                                <i class="fas fa-file-pdf submenu-icon"></i> Informes Ejecutivos
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/bi/analisis_predictivo.php" class="submenu-link">
                                <i class="fas fa-brain submenu-icon"></i> Análisis Predictivo
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/bi/reportes_financieros.php" class="submenu-link">
                                <i class="fas fa-dollar-sign submenu-icon"></i> Reportes Financieros
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/bi/reportes_ventas.php" class="submenu-link">
                                <i class="fas fa-shopping-cart submenu-icon"></i> Reportes Ventas
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/bi/reportes_inventario.php" class="submenu-link">
                                <i class="fas fa-boxes submenu-icon"></i> Reportes Inventario
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/bi/reportes_produccion.php" class="submenu-link">
                                <i class="fas fa-industry submenu-icon"></i> Reportes Producción
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/bi/reportes_rrhh.php" class="submenu-link">
                                <i class="fas fa-users submenu-icon"></i> Reportes RRHH
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/bi/reportes_compras.php" class="submenu-link">
                                <i class="fas fa-shopping-basket submenu-icon"></i> Reportes Compras
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/bi/reportes_calidad.php" class="submenu-link">
                                <i class="fas fa-star submenu-icon"></i> Reportes Calidad
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/bi/reportes_mantenimiento.php" class="submenu-link">
                                <i class="fas fa-wrench submenu-icon"></i> Reportes Mantenimiento
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/bi/reportes_logistica.php" class="submenu-link">
                                <i class="fas fa-truck submenu-icon"></i> Reportes Logística
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/bi/reportes_proyectos.php" class="submenu-link">
                                <i class="fas fa-project-diagram submenu-icon"></i> Reportes Proyectos
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="../modulos/bi/reportes_personalizados.php" class="submenu-link">
                                <i class="fas fa-cogs submenu-icon"></i> Reportes Personalizados
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <!-- MÓDULO 13: Configuración -->
            <li class="menu-item">
                <a class="menu-link" data-bs-toggle="collapse" href="#menuConfiguracion" role="button">
                    <div class="menu-link-left">
                        <i class="fas fa-wrench menu-icon"></i>
                        <span class="menu-title">Configuración</span>
                    </div>
                    <div>
                        <i class="fas fa-chevron-right menu-arrow"></i>
                    </div>
                </a>
                <div class="collapse" id="menuConfiguracion">
                    <ul class="submenu">
                        <li class="submenu-item">
                            <a href="../modulos/configuracion/index.php" class="submenu-link">
                                <i class="fas fa-sliders-h submenu-icon"></i> Panel Configuración
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <hr style="border-color: rgba(255,255,255,0.1); margin: 15px 0;">

            <!-- Mi Perfil -->
            <li class="menu-item">
                <a href="perfil.php" class="menu-link">
                    <div class="menu-link-left">
                        <i class="fas fa-user menu-icon"></i>
                        <span class="menu-title">Mi Perfil</span>
                    </div>
                </a>
            </li>

            <!-- Notificaciones -->
            <li class="menu-item">
                <a href="notificaciones.php" class="menu-link">
                    <div class="menu-link-left">
                        <i class="fas fa-bell menu-icon"></i>
                        <span class="menu-title">Notificaciones</span>
                    </div>
                    <?php if ($notificaciones_count > 0): ?>
                        <span class="menu-badge"><?php echo $notificaciones_count; ?></span>
                    <?php endif; ?>
                </a>
            </li>

            <!-- Cerrar Sesión -->
            <li class="menu-item">
                <a href="../logout.php" class="menu-link" onclick="return confirm('¿Cerrar sesión?')">
                    <div class="menu-link-left">
                        <i class="fas fa-sign-out-alt menu-icon"></i>
                        <span class="menu-title">Cerrar Sesión</span>
                    </div>
                </a>
            </li>
        </ul>
    </div>

    <!-- Topbar -->
    <div class="topbar">
        <div class="topbar-left">
            <h1>Bienvenido, <?php echo htmlspecialchars($usuario['nombre']); ?>!</h1>
            <div class="topbar-subtitle">
                <?php echo date('l, d F Y'); ?> •
                <?php echo htmlspecialchars($usuario['plan_nombre']); ?>
                <?php if ($usuario['en_periodo_prueba'] == 1): ?>
                    • Trial: <?php echo $dias_restantes_trial; ?> días restantes
                <?php endif; ?>
            </div>
        </div>
        <div class="topbar-right">
            <button class="topbar-btn" data-theme-toggle title="Dark Mode">
                <i class="fas fa-moon"></i>
            </button>
            <button class="topbar-btn" onclick="window.location.href='notificaciones.php'" title="Notificaciones">
                <i class="fas fa-bell"></i>
                <?php if ($notificaciones_count > 0): ?>
                    <span class="notification-badge"><?php echo $notificaciones_count; ?></span>
                <?php endif; ?>
            </button>
            <div class="user-menu" onclick="window.location.href='perfil.php'" title="Mi Perfil">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($usuario['nombre'], 0, 1) . substr($usuario['apellido'], 0, 1)); ?>
                </div>
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']); ?></div>
                    <div class="user-role"><?php echo $usuario['es_super_admin'] ? 'Super Admin' : 'Usuario'; ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Alerta Trial -->
        <?php if ($mostrar_alerta_trial): ?>
            <div class="alert-trial">
                <div class="d-flex align-items-center">
                    <i class="fas fa-exclamation-triangle fa-2x text-warning me-3"></i>
                    <div>
                        <strong>⏰ Tu periodo de prueba expira en <?php echo $dias_restantes_trial; ?> días</strong>
                        <p class="mb-0">Actualiza tu plan para continuar disfrutando de todas las funcionalidades.</p>
                    </div>
                    <a href="suscripcion.php" class="btn btn-warning ms-auto">Ver Planes</a>
                </div>
            </div>
        <?php endif; ?>

        <!-- Welcome Card -->
        <div class="welcome-card">
            <h2>¡Hola <?php echo htmlspecialchars($usuario['nombre']); ?>! 👋</h2>
            <p>Bienvenido a tu panel de control de CONECTA ERP. Aquí podrás gestionar toda tu empresa desde un solo lugar.</p>
        </div>

        <!-- Stats Cards -->
        <div class="row">
            <div class="col-md-3 mb-4">
                <div class="stats-card blue">
                    <div class="stats-icon blue">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stats-number"><?php echo number_format($stats['total_clientes']); ?></div>
                    <div class="stats-label">Clientes</div>
                    <div class="stats-trend <?php echo $trends['clientes_change']['direccion']; ?>">
                        <?php if ($trends['clientes_change']['direccion'] == 'up'): ?>
                            <i class="fas fa-arrow-up"></i> <?php echo $trends['clientes_change']['porcentaje']; ?>%
                        <?php elseif ($trends['clientes_change']['direccion'] == 'down'): ?>
                            <i class="fas fa-arrow-down"></i> <?php echo $trends['clientes_change']['porcentaje']; ?>%
                        <?php else: ?>
                            <i class="fas fa-minus"></i> Sin cambios
                        <?php endif; ?>
                        vs mes anterior
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="stats-card green">
                    <div class="stats-icon green">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="stats-number"><?php echo number_format($stats['total_productos']); ?></div>
                    <div class="stats-label">Productos</div>
                    <div class="stats-trend <?php echo $trends['productos_change']['direccion']; ?>">
                        <?php if ($trends['productos_change']['direccion'] == 'up'): ?>
                            <i class="fas fa-arrow-up"></i> <?php echo $trends['productos_change']['porcentaje']; ?>%
                        <?php elseif ($trends['productos_change']['direccion'] == 'down'): ?>
                            <i class="fas fa-arrow-down"></i> <?php echo $trends['productos_change']['porcentaje']; ?>%
                        <?php else: ?>
                            <i class="fas fa-minus"></i> Sin cambios
                        <?php endif; ?>
                        vs mes anterior
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="stats-card orange">
                    <div class="stats-icon orange">
                        <i class="fas fa-file-invoice"></i>
                    </div>
                    <div class="stats-number"><?php echo number_format($stats['facturas_mes']); ?></div>
                    <div class="stats-label">Facturas Este Mes</div>
                    <div class="stats-trend <?php echo $trends['facturas_change']['direccion']; ?>">
                        <?php if ($trends['facturas_change']['direccion'] == 'up'): ?>
                            <i class="fas fa-arrow-up"></i> <?php echo $trends['facturas_change']['porcentaje']; ?>%
                        <?php elseif ($trends['facturas_change']['direccion'] == 'down'): ?>
                            <i class="fas fa-arrow-down"></i> <?php echo $trends['facturas_change']['porcentaje']; ?>%
                        <?php else: ?>
                            <i class="fas fa-minus"></i> Sin cambios
                        <?php endif; ?>
                        vs mes anterior
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="stats-card purple">
                    <div class="stats-icon purple">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stats-number">$<?php echo number_format($stats['ventas_mes'], 0, ',', '.'); ?></div>
                    <div class="stats-label">Ventas Este Mes</div>
                    <div class="stats-trend <?php echo $trends['ventas_change']['direccion']; ?>">
                        <?php if ($trends['ventas_change']['direccion'] == 'up'): ?>
                            <i class="fas fa-arrow-up"></i> <?php echo $trends['ventas_change']['porcentaje']; ?>%
                        <?php elseif ($trends['ventas_change']['direccion'] == 'down'): ?>
                            <i class="fas fa-arrow-down"></i> <?php echo $trends['ventas_change']['porcentaje']; ?>%
                        <?php else: ?>
                            <i class="fas fa-minus"></i> Sin cambios
                        <?php endif; ?>
                        vs mes anterior
                    </div>
                </div>
            </div>
        </div>

        <!-- Content Cards -->
        <div class="row">
            <div class="col-md-12">
                <div style="background: white; border-radius: 12px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                    <h3 style="margin-bottom: 20px;">Accesos Rápidos</h3>
                    <div class="row">
                        <?php
                        $colores_gradientes = [
                            'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                            'linear-gradient(135deg, #48bb78 0%, #38a169 100%)',
                            'linear-gradient(135deg, #ed8936 0%, #dd6b20 100%)',
                            'linear-gradient(135deg, #9f7aea 0%, #805ad5 100%)',
                            'linear-gradient(135deg, #3182ce 0%, #2c5282 100%)',
                            'linear-gradient(135deg, #e53e3e 0%, #c53030 100%)'
                        ];

                        foreach ($accesos_rapidos as $idx => $acceso):
                            $color = $colores_gradientes[$idx % count($colores_gradientes)];
                        ?>
                        <div class="col-md-3 mb-3">
                            <a href="<?php echo htmlspecialchars($acceso['url']); ?>" style="display: block; padding: 20px; background: <?php echo $color; ?>; color: white; border-radius: 10px; text-decoration: none; text-align: center;">
                                <i class="fas fa-<?php echo htmlspecialchars($acceso['icono']); ?> fa-3x mb-3"></i>
                                <div style="font-weight: 600;"><?php echo htmlspecialchars($acceso['titulo']); ?></div>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/dark_mode.js"></script>

    <script>
        // Mantener submenu abierto si hay una página activa dentro
        document.addEventListener('DOMContentLoaded', function() {
            const activeSubmenuLink = document.querySelector('.submenu-link.active');
            if (activeSubmenuLink) {
                const collapseParent = activeSubmenuLink.closest('.collapse');
                if (collapseParent) {
                    const bsCollapse = new bootstrap.Collapse(collapseParent, {
                        toggle: true
                    });
                }
            }
        });
    </script>
</body>
</html>
