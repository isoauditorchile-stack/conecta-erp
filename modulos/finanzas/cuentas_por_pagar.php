<?php
/**
 * CUENTAS POR PAGAR - CONECTA ERP
 * CRUD Completo de Cuentas por Pagar (CP)
 * Nivel Empresarial - Estilo SAP/Softland
 */

session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Verificar autenticación
requireLogin();

// Verificar permisos
$puede_gestionar = ($_SESSION['es_super_admin'] == 1) || verificarPermiso('finanzas', 'gestionar');
$puede_crear = ($_SESSION['es_super_admin'] == 1) || verificarPermiso('finanzas', 'crear');
$puede_editar = ($_SESSION['es_super_admin'] == 1) || verificarPermiso('finanzas', 'editar');
$puede_eliminar = ($_SESSION['es_super_admin'] == 1) || verificarPermiso('finanzas', 'eliminar');

$mensaje = '';
$tipo_mensaje = '';

// ==========================================
// PROCESAR ACCIONES CRUD
// ==========================================

// CREAR CUENTA POR PAGAR
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_cuenta'])) {
    if (!$puede_crear) {
        $mensaje = 'No tienes permisos para crear cuentas por pagar';
        $tipo_mensaje = 'danger';
    } else {
        $numero_documento = trim($_POST['numero_documento']);
        $proveedor_id = (int)$_POST['proveedor_id'];
        $tipo_documento = $_POST['tipo_documento'];
        $fecha_emision = $_POST['fecha_emision'];
        $fecha_vencimiento = $_POST['fecha_vencimiento'];
        $monto = (float)$_POST['monto'];
        $concepto = trim($_POST['concepto']);
        $observaciones = trim($_POST['observaciones']);

        $stmt = $conn->prepare("INSERT INTO cuentas_por_pagar (
            numero_documento, proveedor_id, tipo_documento, fecha_emision, fecha_vencimiento,
            monto, saldo_pendiente, concepto, observaciones, estado,
            empresa_id, creado_por, fecha_creacion
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendiente', ?, ?, NOW())");

        $stmt->bind_param("sisssddssi",
            $numero_documento, $proveedor_id, $tipo_documento, $fecha_emision, $fecha_vencimiento,
            $monto, $monto, $concepto, $observaciones,
            $_SESSION['empresa_id'], $_SESSION['usuario_id']
        );

        if ($stmt->execute()) {
            $nuevo_id = $conn->insert_id;
            $mensaje = "Cuenta por pagar '$numero_documento' registrada exitosamente";
            $tipo_mensaje = 'success';

            logAuditoria('crear', 'cuentas_por_pagar', $nuevo_id, null, null,
                "CxP creada: $numero_documento por $" . number_format($monto));
        } else {
            $mensaje = 'Error al crear la cuenta por pagar: ' . $stmt->error;
            $tipo_mensaje = 'danger';
        }
        $stmt->close();
    }
}

// REGISTRAR PAGO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar_pago'])) {
    if (!$puede_editar) {
        $mensaje = 'No tienes permisos para registrar pagos';
        $tipo_mensaje = 'danger';
    } else {
        $cuenta_id = (int)$_POST['cuenta_id'];
        $monto_pago = (float)$_POST['monto_pago'];
        $fecha_pago = $_POST['fecha_pago'];
        $metodo_pago = $_POST['metodo_pago'];
        $referencia_pago = trim($_POST['referencia_pago']);

        // Obtener saldo actual
        $stmt = $conn->prepare("SELECT saldo_pendiente, monto FROM cuentas_por_pagar WHERE id = ? AND empresa_id = ?");
        $stmt->bind_param("ii", $cuenta_id, $_SESSION['empresa_id']);
        $stmt->execute();
        $cuenta = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($monto_pago > $cuenta['saldo_pendiente']) {
            $mensaje = 'El monto del pago no puede ser mayor al saldo pendiente';
            $tipo_mensaje = 'danger';
        } else {
            // Actualizar saldo
            $nuevo_saldo = $cuenta['saldo_pendiente'] - $monto_pago;
            $nuevo_estado = $nuevo_saldo == 0 ? 'pagado' : 'parcial';

            $stmt = $conn->prepare("UPDATE cuentas_por_pagar SET
                saldo_pendiente = ?, estado = ?, fecha_pago = ?,
                modificado_por = ?, fecha_modificacion = NOW()
                WHERE id = ? AND empresa_id = ?");

            $stmt->bind_param("dssi",
                $nuevo_saldo, $nuevo_estado, $fecha_pago,
                $_SESSION['usuario_id'], $cuenta_id, $_SESSION['empresa_id']
            );

            if ($stmt->execute()) {
                $mensaje = "Pago de $" . number_format($monto_pago) . " registrado exitosamente";
                $tipo_mensaje = 'success';

                logAuditoria('editar', 'cuentas_por_pagar', $cuenta_id, null, null,
                    "Pago registrado: $" . number_format($monto_pago) . " - Saldo: $" . number_format($nuevo_saldo));
            } else {
                $mensaje = 'Error al registrar el pago';
                $tipo_mensaje = 'danger';
            }
            $stmt->close();
        }
    }
}

// ANULAR CUENTA
if (isset($_GET['anular']) && $puede_eliminar) {
    $cuenta_id = (int)$_GET['anular'];

    $stmt = $conn->prepare("UPDATE cuentas_por_pagar SET estado = 'anulado', modificado_por = ?, fecha_modificacion = NOW()
                            WHERE id = ? AND empresa_id = ?");
    $stmt->bind_param("iii", $_SESSION['usuario_id'], $cuenta_id, $_SESSION['empresa_id']);

    if ($stmt->execute()) {
        $mensaje = "Cuenta por pagar anulada exitosamente";
        $tipo_mensaje = 'success';

        logAuditoria('eliminar', 'cuentas_por_pagar', $cuenta_id, null, null,
            "CxP anulada");
    }
    $stmt->close();
}

// ==========================================
// OBTENER LISTA DE CUENTAS POR PAGAR
// ==========================================

$filtro_estado = $_GET['estado'] ?? 'todos';
$filtro_vencimiento = $_GET['vencimiento'] ?? 'todos';
$busqueda = $_GET['buscar'] ?? '';

$query = "SELECT cp.*, p.nombre as proveedor_nombre, p.rut as proveedor_rut
          FROM cuentas_por_pagar cp
          LEFT JOIN proveedores p ON cp.proveedor_id = p.id
          WHERE cp.empresa_id = {$_SESSION['empresa_id']}";

// Filtros
if ($filtro_estado !== 'todos') {
    $query .= " AND cp.estado = '" . $conn->real_escape_string($filtro_estado) . "'";
}

if ($filtro_vencimiento === 'vencidos') {
    $query .= " AND cp.fecha_vencimiento < CURDATE() AND cp.estado != 'pagado' AND cp.estado != 'anulado'";
} elseif ($filtro_vencimiento === 'por_vencer') {
    $query .= " AND cp.fecha_vencimiento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND cp.estado != 'pagado' AND cp.estado != 'anulado'";
}

if (!empty($busqueda)) {
    $busqueda_escape = $conn->real_escape_string($busqueda);
    $query .= " AND (cp.numero_documento LIKE '%{$busqueda_escape}%'
                 OR p.nombre LIKE '%{$busqueda_escape}%'
                 OR cp.concepto LIKE '%{$busqueda_escape}%')";
}

$query .= " ORDER BY cp.fecha_vencimiento ASC";

$result = $conn->query($query);
$cuentas = $result->fetch_all(MYSQLI_ASSOC);

// Obtener proveedores
$proveedores = $conn->query("SELECT id, nombre, rut FROM proveedores
                              WHERE empresa_id = {$_SESSION['empresa_id']} AND estado = 'activo'
                              ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);

// Estadísticas
$stats = $conn->query("SELECT
    COUNT(*) as total,
    SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
    SUM(CASE WHEN estado = 'parcial' THEN 1 ELSE 0 END) as parciales,
    SUM(CASE WHEN estado = 'pagado' THEN 1 ELSE 0 END) as pagados,
    SUM(CASE WHEN fecha_vencimiento < CURDATE() AND estado != 'pagado' AND estado != 'anulado' THEN 1 ELSE 0 END) as vencidos,
    SUM(saldo_pendiente) as saldo_total,
    SUM(CASE WHEN estado != 'anulado' THEN monto ELSE 0 END) as monto_total
    FROM cuentas_por_pagar
    WHERE empresa_id = {$_SESSION['empresa_id']}")->fetch_assoc();

// Próximos vencimientos (7 días)
$proximos_vencimientos = $conn->query("SELECT cp.*, p.nombre as proveedor_nombre
                                        FROM cuentas_por_pagar cp
                                        LEFT JOIN proveedores p ON cp.proveedor_id = p.id
                                        WHERE cp.empresa_id = {$_SESSION['empresa_id']}
                                        AND cp.fecha_vencimiento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                                        AND cp.estado != 'pagado' AND cp.estado != 'anulado'
                                        ORDER BY cp.fecha_vencimiento ASC
                                        LIMIT 5")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cuentas por Pagar - CONECTA ERP</title>

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

        .badge-pendiente { background: #ed8936; }
        .badge-parcial { background: #667eea; }
        .badge-pagado { background: #48bb78; }
        .badge-vencido { background: #f56565; }
        .badge-anulado { background: #a0aec0; }

        .table thead th {
            background: #f5f7fa;
            color: #2d3748;
            font-weight: 600;
            border: none;
        }

        .table tbody tr:hover {
            background: #f5f7fa;
        }

        .table tbody tr.vencido {
            background: rgba(245, 101, 101, 0.05);
        }

        .action-buttons {
            display: flex;
            gap: 5px;
        }

        .action-buttons .btn {
            padding: 5px 10px;
            font-size: 12px;
        }

        .modal-header {
            background: var(--primary-gradient);
            color: white;
            border-radius: 10px 10px 0 0;
        }

        .modal-content {
            border-radius: 10px;
            border: none;
        }

        .form-label {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 8px;
        }

        .form-control,
        .form-select {
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            padding: 10px 15px;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .filters {
            background: #f5f7fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-item {
            padding: 15px;
            border-left: 4px solid #ed8936;
            margin-bottom: 10px;
            background: #fff7ed;
            border-radius: 0 5px 5px 0;
        }

        .alert-item.vencido {
            border-left-color: #f56565;
            background: #fff5f5;
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
            <small style="color: rgba(255,255,255,0.7);">Finanzas</small>
        </div>

        <ul class="sidebar-menu">
            <li><a href="../dashboard/index.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="contabilidad_general.php"><i class="fas fa-chart-line"></i> Contabilidad</a></li>
            <li><a href="cuentas_por_pagar.php" class="active"><i class="fas fa-file-invoice-dollar"></i> Cuentas por Pagar</a></li>
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
                <li class="breadcrumb-item active">Cuentas por Pagar</li>
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
        <!-- Mensajes -->
        <?php if ($mensaje): ?>
            <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
                <i class="fas fa-<?php echo $tipo_mensaje === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo htmlspecialchars($mensaje); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">Cuentas por Pagar</h1>
                <p class="text-muted mb-0">Gestión de facturas y pagos a proveedores</p>
            </div>
            <?php if ($puede_crear): ?>
                <button class="btn btn-gradient" data-bs-toggle="modal" data-bs-target="#modalCrearCuenta">
                    <i class="fas fa-plus"></i> Nueva Cuenta por Pagar
                </button>
            <?php endif; ?>
        </div>

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-icon blue">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </div>
                    <div class="stats-number"><?php echo number_format($stats['total']); ?></div>
                    <div class="stats-label">Total Cuentas</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-icon orange">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stats-number"><?php echo number_format($stats['pendientes']); ?></div>
                    <div class="stats-label">Pendientes</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-icon red">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="stats-number"><?php echo number_format($stats['vencidos']); ?></div>
                    <div class="stats-label">Vencidos</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-icon green">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stats-number">$<?php echo number_format($stats['saldo_total'] / 1000000, 1); ?>M</div>
                    <div class="stats-label">Saldo Total</div>
                </div>
            </div>
        </div>

        <!-- Alertas de Vencimiento -->
        <?php if (count($proximos_vencimientos) > 0): ?>
        <div class="content-card mb-3">
            <div class="content-card-header">
                <h2 class="content-card-title">
                    <i class="fas fa-bell text-warning"></i> Próximos Vencimientos (7 días)
                </h2>
            </div>
            <?php foreach ($proximos_vencimientos as $venc): ?>
                <?php
                $dias_vencimiento = (strtotime($venc['fecha_vencimiento']) - strtotime(date('Y-m-d'))) / 86400;
                $es_vencido = $dias_vencimiento < 0;
                ?>
                <div class="alert-item <?php echo $es_vencido ? 'vencido' : ''; ?>">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong><?php echo htmlspecialchars($venc['proveedor_nombre']); ?></strong>
                            <br>
                            <small class="text-muted">
                                Doc: <?php echo htmlspecialchars($venc['numero_documento']); ?> -
                                Vence: <?php echo date('d/m/Y', strtotime($venc['fecha_vencimiento'])); ?>
                                <?php if ($es_vencido): ?>
                                    <span class="badge badge-vencido">VENCIDO</span>
                                <?php else: ?>
                                    (<?php echo round($dias_vencimiento); ?> días)
                                <?php endif; ?>
                            </small>
                        </div>
                        <div>
                            <strong class="text-danger">$<?php echo number_format($venc['saldo_pendiente'], 0, ',', '.'); ?></strong>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Content Card -->
        <div class="content-card">
            <div class="content-card-header">
                <h2 class="content-card-title">Listado de Cuentas por Pagar</h2>
            </div>

            <!-- Filtros -->
            <div class="filters">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Estado</label>
                        <select name="estado" class="form-select" onchange="this.form.submit()">
                            <option value="todos" <?php echo $filtro_estado === 'todos' ? 'selected' : ''; ?>>Todos</option>
                            <option value="pendiente" <?php echo $filtro_estado === 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                            <option value="parcial" <?php echo $filtro_estado === 'parcial' ? 'selected' : ''; ?>>Pago Parcial</option>
                            <option value="pagado" <?php echo $filtro_estado === 'pagado' ? 'selected' : ''; ?>>Pagado</option>
                            <option value="anulado" <?php echo $filtro_estado === 'anulado' ? 'selected' : ''; ?>>Anulado</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Vencimiento</label>
                        <select name="vencimiento" class="form-select" onchange="this.form.submit()">
                            <option value="todos" <?php echo $filtro_vencimiento === 'todos' ? 'selected' : ''; ?>>Todos</option>
                            <option value="vencidos" <?php echo $filtro_vencimiento === 'vencidos' ? 'selected' : ''; ?>>Vencidos</option>
                            <option value="por_vencer" <?php echo $filtro_vencimiento === 'por_vencer' ? 'selected' : ''; ?>>Por Vencer (7 días)</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Buscar</label>
                        <input type="text" name="buscar" class="form-control" placeholder="Documento, proveedor..." value="<?php echo htmlspecialchars($busqueda); ?>">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-gradient w-100">
                            <i class="fas fa-search"></i> Buscar
                        </button>
                    </div>
                </form>
            </div>

            <!-- Tabla -->
            <div class="table-responsive">
                <table class="table table-hover" id="tablaCuentas">
                    <thead>
                        <tr>
                            <th>N° Documento</th>
                            <th>Proveedor</th>
                            <th>Tipo</th>
                            <th>Fecha Emisión</th>
                            <th>Vencimiento</th>
                            <th>Monto Total</th>
                            <th>Saldo Pendiente</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cuentas as $cuenta): ?>
                        <?php
                            $dias_vencimiento = (strtotime($cuenta['fecha_vencimiento']) - strtotime(date('Y-m-d'))) / 86400;
                            $es_vencido = $dias_vencimiento < 0 && $cuenta['estado'] != 'pagado' && $cuenta['estado'] != 'anulado';
                        ?>
                        <tr class="<?php echo $es_vencido ? 'vencido' : ''; ?>">
                            <td><strong><code><?php echo htmlspecialchars($cuenta['numero_documento']); ?></code></strong></td>
                            <td>
                                <?php echo htmlspecialchars($cuenta['proveedor_nombre']); ?>
                                <br><small class="text-muted"><?php echo $cuenta['proveedor_rut'] ? formatearRUT($cuenta['proveedor_rut']) : ''; ?></small>
                            </td>
                            <td><?php echo strtoupper($cuenta['tipo_documento']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($cuenta['fecha_emision'])); ?></td>
                            <td>
                                <?php echo date('d/m/Y', strtotime($cuenta['fecha_vencimiento'])); ?>
                                <?php if ($es_vencido): ?>
                                    <br><span class="badge badge-vencido"><i class="fas fa-exclamation-triangle"></i> Vencido</span>
                                <?php endif; ?>
                            </td>
                            <td><strong>$<?php echo number_format($cuenta['monto'], 0, ',', '.'); ?></strong></td>
                            <td><strong class="text-danger">$<?php echo number_format($cuenta['saldo_pendiente'], 0, ',', '.'); ?></strong></td>
                            <td>
                                <span class="badge badge-<?php echo $cuenta['estado']; ?>">
                                    <?php echo ucfirst($cuenta['estado']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <?php if ($puede_editar && $cuenta['estado'] != 'pagado' && $cuenta['estado'] != 'anulado'): ?>
                                        <button class="btn btn-sm btn-success" onclick="alert('Registrar pago para cuenta <?php echo $cuenta['id']; ?>')">
                                            <i class="fas fa-dollar-sign"></i>
                                        </button>
                                    <?php endif; ?>
                                    <button class="btn btn-sm btn-info" onclick="alert('Ver detalle')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <?php if ($puede_eliminar && $cuenta['estado'] != 'anulado' && $cuenta['estado'] != 'pagado'): ?>
                                        <a href="?anular=<?php echo $cuenta['id']; ?>" class="btn btn-sm btn-danger"
                                           onclick="return confirm('¿Anular esta cuenta por pagar?')">
                                            <i class="fas fa-ban"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Crear Cuenta por Pagar -->
    <div class="modal fade" id="modalCrearCuenta" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-file-invoice-dollar"></i> Nueva Cuenta por Pagar</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Número de Documento *</label>
                                <input type="text" name="numero_documento" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tipo de Documento *</label>
                                <select name="tipo_documento" class="form-select" required>
                                    <option value="factura">Factura</option>
                                    <option value="boleta">Boleta</option>
                                    <option value="nota_credito">Nota de Crédito</option>
                                    <option value="orden_compra">Orden de Compra</option>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Proveedor *</label>
                                <select name="proveedor_id" class="form-select" required>
                                    <option value="">Seleccione un proveedor</option>
                                    <?php foreach ($proveedores as $prov): ?>
                                        <option value="<?php echo $prov['id']; ?>">
                                            <?php echo htmlspecialchars($prov['nombre']); ?>
                                            <?php echo $prov['rut'] ? '(' . formatearRUT($prov['rut']) . ')' : ''; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Fecha Emisión *</label>
                                <input type="date" name="fecha_emision" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Fecha Vencimiento *</label>
                                <input type="date" name="fecha_vencimiento" class="form-control" value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Monto Total *</label>
                                <input type="number" name="monto" class="form-control" step="0.01" required>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Concepto *</label>
                                <input type="text" name="concepto" class="form-control" required>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Observaciones</label>
                                <textarea name="observaciones" class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="crear_cuenta" class="btn btn-gradient">
                            <i class="fas fa-save"></i> Crear Cuenta
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="../../assets/js/dark_mode.js"></script>

    <script>
        $('#tablaCuentas').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
            },
            order: [[4, 'asc']],
            pageLength: 25
        });
    </script>

    <!-- Footer Profesional -->
    <?php include '../includes/footer.php'; ?>

</body>
</html>
