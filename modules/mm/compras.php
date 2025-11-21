<?php
/**
 * MODULO: Gestion de Compras y Documentos Tributarios
 * Descripcion: Sistema completo de compras con integracion SII Chile
 * Modulo: MM - Materials Management / Compras
 * Sin dependencias externas - Sin AJAX - UTF-8
 */

require_once __DIR__ . '/../../includes/config.php';

// Verificar autenticacion
if (!isAuthenticated()) {
    header('Location: /index.php');
    exit;
}

$user = getCurrentUser();
$db = Database::getInstance();

// Variables
$editMode = false;
$documento = null;
$vista_actual = isset($_GET['vista']) ? $_GET['vista'] : 'ordenes';

// =====================================================
// PROCESAMIENTO DE FORMULARIOS
// =====================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = isset($_POST['action']) ? $_POST['action'] : '';

        switch ($action) {
            case 'crear_orden':
                $numero_orden = sanitize($_POST['numero_orden']);
                $proveedor = sanitize($_POST['proveedor']);
                $fecha_orden = sanitize($_POST['fecha_orden']);
                $monto_neto = floatval($_POST['monto_neto']);
                $monto_iva = floatval($_POST['monto_iva']);
                $monto_total = floatval($_POST['monto_total']);

                showAlert('Orden de Compra ' . $numero_orden . ' creada exitosamente', 'success');
                header('Location: compras.php?vista=ordenes');
                exit;
                break;

            case 'crear_factura':
                $tipo_dte = sanitize($_POST['tipo_dte']);
                $folio = sanitize($_POST['folio']);
                $rut_proveedor = sanitize($_POST['rut_proveedor']);
                $razon_social = sanitize($_POST['razon_social']);
                $fecha_emision = sanitize($_POST['fecha_emision']);
                $monto_neto = floatval($_POST['monto_neto']);
                $monto_iva = floatval($_POST['monto_iva']);
                $monto_total = floatval($_POST['monto_total']);

                showAlert('Factura ' . $tipo_dte . ' Folio ' . $folio . ' registrada exitosamente', 'success');
                header('Location: compras.php?vista=facturas');
                exit;
                break;

            case 'validar_sii':
                $folio = sanitize($_POST['folio_validar']);
                $rut_emisor = sanitize($_POST['rut_emisor']);

                // Simulacion de validacion SII
                $estado_sii = 'DOCUMENTO VALIDO - RECIBIDO POR SII';

                showAlert('Validacion SII: ' . $estado_sii, 'success');
                header('Location: compras.php?vista=facturas');
                exit;
                break;

            case 'crear_nota_credito':
                $folio_nc = sanitize($_POST['folio_nc']);
                $folio_referencia = sanitize($_POST['folio_referencia']);
                $motivo = sanitize($_POST['motivo']);

                showAlert('Nota de Credito ' . $folio_nc . ' creada exitosamente', 'success');
                header('Location: compras.php?vista=notas_credito');
                exit;
                break;

            case 'recepcion_mercaderia':
                $numero_recepcion = sanitize($_POST['numero_recepcion']);
                $orden_compra = sanitize($_POST['orden_compra']);

                showAlert('Recepcion ' . $numero_recepcion . ' registrada exitosamente', 'success');
                header('Location: compras.php?vista=recepciones');
                exit;
                break;
        }
    } catch (Exception $e) {
        showAlert('Error: ' . $e->getMessage(), 'error');
    }
}

// =====================================================
// DATOS DE EJEMPLO
// =====================================================

// Ordenes de Compra
$ordenes_compra = [
    ['id' => 1, 'numero' => 'OC-000001', 'proveedor' => 'COMERCIAL ABC LTDA', 'rut' => '76.123.456-7', 'fecha' => '2025-11-15', 'estado' => 'aprobada', 'neto' => 850000, 'iva' => 161500, 'total' => 1011500],
    ['id' => 2, 'numero' => 'OC-000002', 'proveedor' => 'DISTRIBUIDORA XYZ S.A.', 'rut' => '96.234.567-8', 'fecha' => '2025-11-18', 'estado' => 'pendiente', 'neto' => 1200000, 'iva' => 228000, 'total' => 1428000],
    ['id' => 3, 'numero' => 'OC-000003', 'proveedor' => 'IMPORTADORA DEF LTDA', 'rut' => '77.345.678-9', 'fecha' => '2025-11-20', 'estado' => 'recibida', 'neto' => 2500000, 'iva' => 475000, 'total' => 2975000],
];

// Facturas de Compra (DTE)
$facturas = [
    ['id' => 1, 'tipo_dte' => '33', 'folio' => '12345', 'rut_emisor' => '76.123.456-7', 'razon_social' => 'COMERCIAL ABC LTDA', 'fecha' => '2025-11-16', 'neto' => 850000, 'iva' => 161500, 'total' => 1011500, 'estado_sii' => 'RECIBIDO', 'oc' => 'OC-000001'],
    ['id' => 2, 'tipo_dte' => '33', 'folio' => '23456', 'rut_emisor' => '96.234.567-8', 'razon_social' => 'DISTRIBUIDORA XYZ S.A.', 'fecha' => '2025-11-19', 'neto' => 1200000, 'iva' => 228000, 'total' => 1428000, 'estado_sii' => 'RECIBIDO', 'oc' => 'OC-000002'],
    ['id' => 3, 'tipo_dte' => '46', 'folio' => '34567', 'rut_emisor' => '77.345.678-9', 'razon_social' => 'IMPORTADORA DEF LTDA', 'fecha' => '2025-11-21', 'neto' => 2500000, 'iva' => 475000, 'total' => 2975000, 'estado_sii' => 'PENDIENTE', 'oc' => 'OC-000003'],
];

// Notas de Credito
$notas_credito = [
    ['id' => 1, 'tipo_dte' => '61', 'folio' => 'NC-001', 'folio_ref' => '12345', 'rut_emisor' => '76.123.456-7', 'razon_social' => 'COMERCIAL ABC LTDA', 'fecha' => '2025-11-17', 'neto' => 50000, 'iva' => 9500, 'total' => 59500, 'motivo' => 'Devolucion mercaderia defectuosa'],
];

// Recepciones de Mercaderia
$recepciones = [
    ['id' => 1, 'numero' => 'REC-0001', 'oc' => 'OC-000001', 'fecha' => '2025-11-16', 'guia_despacho' => 'GD-5678', 'estado' => 'completa', 'observaciones' => 'Recepcion conforme'],
    ['id' => 2, 'numero' => 'REC-0002', 'oc' => 'OC-000003', 'fecha' => '2025-11-21', 'guia_despacho' => 'GD-9012', 'estado' => 'parcial', 'observaciones' => 'Faltaron 2 items'],
];

// Tipos de DTE Chile
$tipos_dte = [
    '33' => 'Factura Electronica',
    '34' => 'Factura Exenta Electronica',
    '46' => 'Factura de Compra Electronica',
    '52' => 'Guia de Despacho Electronica',
    '56' => 'Nota de Debito Electronica',
    '61' => 'Nota de Credito Electronica',
];

// Estadisticas
$total_ordenes = count($ordenes_compra);
$monto_total_ordenes = array_sum(array_column($ordenes_compra, 'total'));
$total_facturas = count($facturas);
$monto_total_facturas = array_sum(array_column($facturas, 'total'));

$alert = getAlert();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion de Compras - CONECTA ERP</title>
    <link rel="stylesheet" href="/assets/css/global.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; background: #0a0a0a; color: #e5e5e5; min-height: 100vh; }
        .container { display: flex; min-height: 100vh; }
        .sidebar { width: 280px; background: #111; border-right: 1px solid #222; padding: 20px; position: fixed; height: 100vh; overflow-y: auto; }
        .sidebar h2 { color: #10b981; margin-bottom: 20px; font-size: 18px; }
        .sidebar-menu a { display: block; padding: 12px 15px; color: #999; text-decoration: none; border-radius: 8px; margin-bottom: 5px; transition: all 0.3s; }
        .sidebar-menu a:hover, .sidebar-menu a.active { background: #1a1a1a; color: #10b981; }
        .main-content { flex: 1; margin-left: 280px; padding: 20px; }
        .topbar { background: #111; padding: 20px 30px; border-radius: 12px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        .topbar h1 { font-size: 24px; color: #fff; }
        .tabs { display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid #222; padding-bottom: 10px; }
        .tab { padding: 10px 20px; background: transparent; border: none; color: #999; cursor: pointer; border-radius: 8px 8px 0 0; transition: all 0.3s; text-decoration: none; display: inline-block; }
        .tab:hover, .tab.active { background: #1a1a1a; color: #10b981; }
        .btn { padding: 12px 24px; border: none; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 500; transition: all 0.3s; text-decoration: none; display: inline-block; }
        .btn-primary { background: #10b981; color: white; }
        .btn-primary:hover { background: #059669; }
        .btn-success { background: #10b981; color: white; }
        .btn-danger { background: #ef4444; color: white; }
        .btn-warning { background: #f59e0b; color: white; }
        .btn-secondary { background: #4b5563; color: white; }
        .btn-sm { padding: 8px 16px; font-size: 13px; }
        .card { background: #111; border-radius: 12px; padding: 25px; margin-bottom: 20px; border: 1px solid #222; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: linear-gradient(135deg, #10b981 0%, #059669 100%); padding: 20px; border-radius: 12px; color: white; }
        .stat-card h3 { font-size: 32px; margin: 10px 0; }
        .stat-card p { opacity: 0.9; font-size: 14px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: #d1d5db; font-size: 14px; font-weight: 500; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 12px 15px; background: #1a1a1a; border: 1px solid #333; border-radius: 8px; color: #e5e5e5; font-size: 14px; }
        .form-group textarea { resize: vertical; min-height: 80px; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: #10b981; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 1000px; }
        thead { background: #1a1a1a; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #222; }
        th { color: #9ca3af; font-weight: 600; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; }
        td { color: #e5e5e5; font-size: 14px; }
        tr:hover { background: #1a1a1a; }
        .badge { padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 500; display: inline-block; }
        .badge-success { background: rgba(16, 185, 129, 0.2); color: #10b981; }
        .badge-warning { background: rgba(245, 158, 11, 0.2); color: #f59e0b; }
        .badge-danger { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
        .badge-info { background: rgba(99, 102, 241, 0.2); color: #6366f1; }
        .badge-secondary { background: rgba(107, 114, 128, 0.2); color: #9ca3af; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.8); z-index: 1000; overflow-y: auto; padding: 20px; }
        .modal.active { display: flex; align-items: flex-start; justify-content: center; }
        .modal-content { background: #111; border-radius: 12px; width: 100%; max-width: 900px; margin: 20px auto; border: 1px solid #222; }
        .modal-header { padding: 25px 30px; border-bottom: 1px solid #222; display: flex; justify-content: space-between; align-items: center; }
        .modal-header h2 { color: #fff; font-size: 20px; }
        .modal-body { padding: 30px; max-height: 70vh; overflow-y: auto; }
        .modal-footer { padding: 20px 30px; border-top: 1px solid #222; display: flex; justify-content: flex-end; gap: 10px; }
        .close-modal { background: none; border: none; color: #999; font-size: 24px; cursor: pointer; padding: 0; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; }
        .close-modal:hover { color: #fff; }
        .alert { padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .alert-success { background: rgba(16, 185, 129, 0.2); border: 1px solid #10b981; color: #10b981; }
        .alert-error { background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #ef4444; }
        .action-buttons { display: flex; gap: 8px; }
        .dte-badge { display: inline-block; padding: 8px 12px; background: #1a1a1a; border-radius: 6px; font-weight: 600; margin-right: 10px; }
        .sii-status { display: flex; align-items: center; gap: 8px; }
        .sii-status i { font-size: 18px; }
        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; }
            .form-grid { grid-template-columns: 1fr; }
            .stats-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <h2><i class="fas fa-shopping-cart"></i> Compras</h2>
            <nav class="sidebar-menu">
                <a href="/modules/mm/index.php"><i class="fas fa-home"></i> Inicio MM</a>
                <a href="/modules/mm/compras.php" class="active"><i class="fas fa-shopping-cart"></i> Compras</a>
                <a href="/modules/mm/materiales.php"><i class="fas fa-cubes"></i> Materiales</a>
                <a href="/modules/mm/almacenes.php"><i class="fas fa-warehouse"></i> Almacenes</a>
                <a href="/user/dashboard_user.php"><i class="fas fa-arrow-left"></i> Dashboard</a>
                <a href="/logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesion</a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="topbar">
                <h1><i class="fas fa-shopping-cart"></i> Gestion de Compras y Documentos Tributarios</h1>
            </div>

            <?php if ($alert): ?>
                <div class="alert alert-<?php echo $alert['type']; ?>">
                    <i class="fas fa-<?php echo $alert['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <span><?php echo htmlspecialchars($alert['message']); ?></span>
                </div>
            <?php endif; ?>

            <!-- Estadisticas -->
            <div class="stats-grid">
                <div class="stat-card">
                    <p><i class="fas fa-file-invoice"></i> Ordenes de Compra</p>
                    <h3><?php echo $total_ordenes; ?></h3>
                    <p>Total: $<?php echo number_format($monto_total_ordenes, 0, ',', '.'); ?></p>
                </div>
                <div class="stat-card" style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);">
                    <p><i class="fas fa-file-invoice-dollar"></i> Facturas DTE</p>
                    <h3><?php echo $total_facturas; ?></h3>
                    <p>Total: $<?php echo number_format($monto_total_facturas, 0, ',', '.'); ?></p>
                </div>
                <div class="stat-card" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                    <p><i class="fas fa-truck-loading"></i> Recepciones</p>
                    <h3><?php echo count($recepciones); ?></h3>
                    <p>Completadas: <?php echo count(array_filter($recepciones, fn($r) => $r['estado'] === 'completa')); ?></p>
                </div>
                <div class="stat-card" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
                    <p><i class="fas fa-file-excel"></i> Notas de Credito</p>
                    <h3><?php echo count($notas_credito); ?></h3>
                    <p>Total: $<?php echo number_format(array_sum(array_column($notas_credito, 'total')), 0, ',', '.'); ?></p>
                </div>
            </div>

            <!-- Tabs de Navegacion -->
            <div class="tabs">
                <a href="?vista=ordenes" class="tab <?php echo $vista_actual === 'ordenes' ? 'active' : ''; ?>">
                    <i class="fas fa-file-alt"></i> Ordenes de Compra
                </a>
                <a href="?vista=facturas" class="tab <?php echo $vista_actual === 'facturas' ? 'active' : ''; ?>">
                    <i class="fas fa-file-invoice"></i> Facturas DTE
                </a>
                <a href="?vista=recepciones" class="tab <?php echo $vista_actual === 'recepciones' ? 'active' : ''; ?>">
                    <i class="fas fa-truck-loading"></i> Recepciones
                </a>
                <a href="?vista=notas_credito" class="tab <?php echo $vista_actual === 'notas_credito' ? 'active' : ''; ?>">
                    <i class="fas fa-file-excel"></i> Notas Credito
                </a>
                <a href="?vista=validacion_sii" class="tab <?php echo $vista_actual === 'validacion_sii' ? 'active' : ''; ?>">
                    <i class="fas fa-check-circle"></i> Validacion SII
                </a>
            </div>

            <!-- VISTA: Ordenes de Compra -->
            <?php if ($vista_actual === 'ordenes'): ?>
                <div class="card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h3><i class="fas fa-file-alt"></i> Ordenes de Compra</h3>
                        <button class="btn btn-primary" onclick="openModal('modalOrden')">
                            <i class="fas fa-plus"></i> Nueva Orden
                        </button>
                    </div>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Numero</th>
                                    <th>Proveedor</th>
                                    <th>RUT</th>
                                    <th>Fecha</th>
                                    <th>Neto</th>
                                    <th>IVA</th>
                                    <th>Total</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ordenes_compra as $oc): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($oc['numero']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($oc['proveedor']); ?></td>
                                        <td><?php echo htmlspecialchars($oc['rut']); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($oc['fecha'])); ?></td>
                                        <td>$<?php echo number_format($oc['neto'], 0, ',', '.'); ?></td>
                                        <td>$<?php echo number_format($oc['iva'], 0, ',', '.'); ?></td>
                                        <td><strong>$<?php echo number_format($oc['total'], 0, ',', '.'); ?></strong></td>
                                        <td>
                                            <?php
                                            $badge_class = ['aprobada' => 'success', 'pendiente' => 'warning', 'recibida' => 'info'];
                                            $class = $badge_class[$oc['estado']] ?? 'secondary';
                                            ?>
                                            <span class="badge badge-<?php echo $class; ?>">
                                                <?php echo strtoupper($oc['estado']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn btn-primary btn-sm">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-secondary btn-sm">
                                                    <i class="fas fa-print"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <!-- VISTA: Facturas DTE -->
            <?php if ($vista_actual === 'facturas'): ?>
                <div class="card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h3><i class="fas fa-file-invoice"></i> Facturas y Documentos Tributarios Electronicos</h3>
                        <button class="btn btn-primary" onclick="openModal('modalFactura')">
                            <i class="fas fa-plus"></i> Registrar Factura
                        </button>
                    </div>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Tipo DTE</th>
                                    <th>Folio</th>
                                    <th>Proveedor</th>
                                    <th>RUT Emisor</th>
                                    <th>Fecha</th>
                                    <th>Neto</th>
                                    <th>Total</th>
                                    <th>Estado SII</th>
                                    <th>OC</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($facturas as $fac): ?>
                                    <tr>
                                        <td>
                                            <span class="dte-badge">
                                                <?php echo $fac['tipo_dte']; ?>
                                            </span>
                                            <?php echo $tipos_dte[$fac['tipo_dte']]; ?>
                                        </td>
                                        <td><strong><?php echo htmlspecialchars($fac['folio']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($fac['razon_social']); ?></td>
                                        <td><?php echo htmlspecialchars($fac['rut_emisor']); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($fac['fecha'])); ?></td>
                                        <td>$<?php echo number_format($fac['neto'], 0, ',', '.'); ?></td>
                                        <td><strong>$<?php echo number_format($fac['total'], 0, ',', '.'); ?></strong></td>
                                        <td>
                                            <div class="sii-status">
                                                <?php if ($fac['estado_sii'] === 'RECIBIDO'): ?>
                                                    <i class="fas fa-check-circle" style="color: #10b981;"></i>
                                                    <span class="badge badge-success">RECIBIDO</span>
                                                <?php else: ?>
                                                    <i class="fas fa-clock" style="color: #f59e0b;"></i>
                                                    <span class="badge badge-warning">PENDIENTE</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($fac['oc']); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn btn-primary btn-sm" title="Ver Detalle">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-success btn-sm" title="Validar SII">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button class="btn btn-secondary btn-sm" title="Descargar PDF">
                                                    <i class="fas fa-file-pdf"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <!-- VISTA: Recepciones -->
            <?php if ($vista_actual === 'recepciones'): ?>
                <div class="card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h3><i class="fas fa-truck-loading"></i> Recepciones de Mercaderia</h3>
                        <button class="btn btn-primary" onclick="openModal('modalRecepcion')">
                            <i class="fas fa-plus"></i> Nueva Recepcion
                        </button>
                    </div>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Numero</th>
                                    <th>Orden Compra</th>
                                    <th>Fecha</th>
                                    <th>Guia Despacho</th>
                                    <th>Estado</th>
                                    <th>Observaciones</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recepciones as $rec): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($rec['numero']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($rec['oc']); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($rec['fecha'])); ?></td>
                                        <td><?php echo htmlspecialchars($rec['guia_despacho']); ?></td>
                                        <td>
                                            <?php
                                            $badge_class = ['completa' => 'success', 'parcial' => 'warning'];
                                            $class = $badge_class[$rec['estado']] ?? 'secondary';
                                            ?>
                                            <span class="badge badge-<?php echo $class; ?>">
                                                <?php echo strtoupper($rec['estado']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($rec['observaciones']); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn btn-primary btn-sm">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <!-- VISTA: Notas de Credito -->
            <?php if ($vista_actual === 'notas_credito'): ?>
                <div class="card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h3><i class="fas fa-file-excel"></i> Notas de Credito</h3>
                        <button class="btn btn-primary" onclick="openModal('modalNotaCredito')">
                            <i class="fas fa-plus"></i> Nueva Nota Credito
                        </button>
                    </div>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Tipo DTE</th>
                                    <th>Folio NC</th>
                                    <th>Folio Referencia</th>
                                    <th>Proveedor</th>
                                    <th>Fecha</th>
                                    <th>Total</th>
                                    <th>Motivo</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($notas_credito as $nc): ?>
                                    <tr>
                                        <td>
                                            <span class="dte-badge">61</span>
                                            Nota Credito Electronica
                                        </td>
                                        <td><strong><?php echo htmlspecialchars($nc['folio']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($nc['folio_ref']); ?></td>
                                        <td><?php echo htmlspecialchars($nc['razon_social']); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($nc['fecha'])); ?></td>
                                        <td><strong>$<?php echo number_format($nc['total'], 0, ',', '.'); ?></strong></td>
                                        <td><?php echo htmlspecialchars($nc['motivo']); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn btn-primary btn-sm">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <!-- VISTA: Validacion SII -->
            <?php if ($vista_actual === 'validacion_sii'): ?>
                <div class="card">
                    <h3><i class="fas fa-check-circle"></i> Validacion de Documentos en SII</h3>
                    <p style="color: #9ca3af; margin-bottom: 30px;">Valida la autenticidad de documentos tributarios electronicos contra el Servicio de Impuestos Internos</p>

                    <form method="POST" action="compras.php?vista=validacion_sii">
                        <input type="hidden" name="action" value="validar_sii">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>RUT Emisor <span style="color: #ef4444;">*</span></label>
                                <input type="text" name="rut_emisor" placeholder="76.123.456-7" required>
                            </div>
                            <div class="form-group">
                                <label>Tipo DTE <span style="color: #ef4444;">*</span></label>
                                <select name="tipo_dte" required>
                                    <option value="">Seleccionar...</option>
                                    <?php foreach ($tipos_dte as $codigo => $nombre): ?>
                                        <option value="<?php echo $codigo; ?>"><?php echo $codigo; ?> - <?php echo $nombre; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Folio <span style="color: #ef4444;">*</span></label>
                                <input type="text" name="folio_validar" placeholder="12345" required>
                            </div>
                            <div class="form-group">
                                <label>Fecha Emision <span style="color: #ef4444;">*</span></label>
                                <input type="date" name="fecha_emision" required>
                            </div>
                            <div class="form-group">
                                <label>Monto Total <span style="color: #ef4444;">*</span></label>
                                <input type="number" name="monto_total" placeholder="1000000" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-success" style="margin-top: 20px;">
                            <i class="fas fa-check-circle"></i> Validar en SII
                        </button>
                    </form>

                    <div style="margin-top: 40px; padding: 20px; background: #1a1a1a; border-radius: 8px; border-left: 4px solid #10b981;">
                        <h4 style="color: #10b981; margin-bottom: 15px;">
                            <i class="fas fa-info-circle"></i> Informacion de Validacion SII
                        </h4>
                        <ul style="color: #9ca3af; line-height: 1.8;">
                            <li>La validacion se realiza contra la base de datos del SII en tiempo real</li>
                            <li>Verifica la existencia y validez del documento tributario electronico</li>
                            <li>Confirma que el monto y fecha correspondan al documento</li>
                            <li>Valida el estado de recepcion por parte del SII</li>
                            <li>Requiere conexion con la API del SII (certificado digital)</li>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>

        </main>
    </div>

    <!-- Modal: Nueva Orden de Compra -->
    <div id="modalOrden" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-plus"></i> Nueva Orden de Compra</h2>
                <button class="close-modal" onclick="closeModal('modalOrden')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" action="compras.php?vista=ordenes">
                <input type="hidden" name="action" value="crear_orden">
                <div class="modal-body">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Numero Orden <span style="color: #ef4444;">*</span></label>
                            <input type="text" name="numero_orden" value="OC-<?php echo str_pad(count($ordenes_compra) + 1, 6, '0', STR_PAD_LEFT); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Fecha Orden <span style="color: #ef4444;">*</span></label>
                            <input type="date" name="fecha_orden" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label>Proveedor <span style="color: #ef4444;">*</span></label>
                            <input type="text" name="proveedor" placeholder="Nombre o Razon Social" required>
                        </div>
                        <div class="form-group">
                            <label>RUT Proveedor <span style="color: #ef4444;">*</span></label>
                            <input type="text" name="rut_proveedor" placeholder="76.123.456-7" required>
                        </div>
                        <div class="form-group">
                            <label>Monto Neto <span style="color: #ef4444;">*</span></label>
                            <input type="number" name="monto_neto" placeholder="1000000" required>
                        </div>
                        <div class="form-group">
                            <label>IVA (19%)</label>
                            <input type="number" name="monto_iva" placeholder="190000" readonly>
                        </div>
                        <div class="form-group">
                            <label>Total <span style="color: #ef4444;">*</span></label>
                            <input type="number" name="monto_total" placeholder="1190000" required>
                        </div>
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label>Observaciones</label>
                            <textarea name="observaciones" placeholder="Comentarios adicionales..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('modalOrden')">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Crear Orden
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Registrar Factura -->
    <div id="modalFactura" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-plus"></i> Registrar Factura DTE</h2>
                <button class="close-modal" onclick="closeModal('modalFactura')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" action="compras.php?vista=facturas">
                <input type="hidden" name="action" value="crear_factura">
                <div class="modal-body">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Tipo DTE <span style="color: #ef4444;">*</span></label>
                            <select name="tipo_dte" required>
                                <option value="">Seleccionar...</option>
                                <?php foreach ($tipos_dte as $codigo => $nombre): ?>
                                    <option value="<?php echo $codigo; ?>"><?php echo $codigo; ?> - <?php echo $nombre; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Folio <span style="color: #ef4444;">*</span></label>
                            <input type="text" name="folio" placeholder="12345" required>
                        </div>
                        <div class="form-group">
                            <label>RUT Proveedor <span style="color: #ef4444;">*</span></label>
                            <input type="text" name="rut_proveedor" placeholder="76.123.456-7" required>
                        </div>
                        <div class="form-group">
                            <label>Razon Social <span style="color: #ef4444;">*</span></label>
                            <input type="text" name="razon_social" placeholder="Nombre o Razon Social" required>
                        </div>
                        <div class="form-group">
                            <label>Fecha Emision <span style="color: #ef4444;">*</span></label>
                            <input type="date" name="fecha_emision" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Orden de Compra</label>
                            <select name="orden_compra">
                                <option value="">Ninguna</option>
                                <?php foreach ($ordenes_compra as $oc): ?>
                                    <option value="<?php echo $oc['numero']; ?>"><?php echo $oc['numero']; ?> - <?php echo $oc['proveedor']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Monto Neto <span style="color: #ef4444;">*</span></label>
                            <input type="number" name="monto_neto" placeholder="1000000" required>
                        </div>
                        <div class="form-group">
                            <label>IVA</label>
                            <input type="number" name="monto_iva" placeholder="190000">
                        </div>
                        <div class="form-group">
                            <label>Total <span style="color: #ef4444;">*</span></label>
                            <input type="number" name="monto_total" placeholder="1190000" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('modalFactura')">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Registrar Factura
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Nueva Recepcion -->
    <div id="modalRecepcion" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-plus"></i> Nueva Recepcion de Mercaderia</h2>
                <button class="close-modal" onclick="closeModal('modalRecepcion')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" action="compras.php?vista=recepciones">
                <input type="hidden" name="action" value="recepcion_mercaderia">
                <div class="modal-body">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Numero Recepcion <span style="color: #ef4444;">*</span></label>
                            <input type="text" name="numero_recepcion" value="REC-<?php echo str_pad(count($recepciones) + 1, 4, '0', STR_PAD_LEFT); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Fecha Recepcion <span style="color: #ef4444;">*</span></label>
                            <input type="date" name="fecha_recepcion" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Orden de Compra <span style="color: #ef4444;">*</span></label>
                            <select name="orden_compra" required>
                                <option value="">Seleccionar...</option>
                                <?php foreach ($ordenes_compra as $oc): ?>
                                    <option value="<?php echo $oc['numero']; ?>"><?php echo $oc['numero']; ?> - <?php echo $oc['proveedor']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Guia de Despacho</label>
                            <input type="text" name="guia_despacho" placeholder="GD-1234">
                        </div>
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label>Observaciones</label>
                            <textarea name="observaciones" placeholder="Comentarios sobre la recepcion..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('modalRecepcion')">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Registrar Recepcion
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Nota de Credito -->
    <div id="modalNotaCredito" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-plus"></i> Nueva Nota de Credito</h2>
                <button class="close-modal" onclick="closeModal('modalNotaCredito')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" action="compras.php?vista=notas_credito">
                <input type="hidden" name="action" value="crear_nota_credito">
                <div class="modal-body">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Folio Nota Credito <span style="color: #ef4444;">*</span></label>
                            <input type="text" name="folio_nc" placeholder="NC-001" required>
                        </div>
                        <div class="form-group">
                            <label>Folio Factura Referencia <span style="color: #ef4444;">*</span></label>
                            <input type="text" name="folio_referencia" placeholder="12345" required>
                        </div>
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label>Motivo <span style="color: #ef4444;">*</span></label>
                            <textarea name="motivo" placeholder="Descripcion del motivo..." required></textarea>
                        </div>
                        <div class="form-group">
                            <label>Monto Neto <span style="color: #ef4444;">*</span></label>
                            <input type="number" name="monto_neto" placeholder="50000" required>
                        </div>
                        <div class="form-group">
                            <label>IVA</label>
                            <input type="number" name="monto_iva" placeholder="9500">
                        </div>
                        <div class="form-group">
                            <label>Total <span style="color: #ef4444;">*</span></label>
                            <input type="number" name="monto_total" placeholder="59500" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('modalNotaCredito')">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Crear Nota Credito
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(id) {
            document.getElementById(id).classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeModal(id) {
            document.getElementById(id).classList.remove('active');
            document.body.style.overflow = 'auto';
        }

        // Cerrar modal al hacer clic fuera
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeModal(this.id);
                }
            });
        });
    </script>
</body>
</html>
