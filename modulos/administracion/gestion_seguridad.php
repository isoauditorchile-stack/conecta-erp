<?php
/**
 * GESTIÓN DE SEGURIDAD - CONECTA ERP
 * Roles, Permisos, Logs de Acceso y Auditoría
 * Nivel Empresarial - Estilo SAP/Softland
 */

session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Verificar autenticación
requireLogin();

// Solo super admin puede acceder
if ($_SESSION['es_super_admin'] != 1) {
    header('Location: ../../user/dashboard.php');
    exit();
}

$mensaje = '';
$tipo_mensaje = '';
$tab_activa = $_GET['tab'] ?? 'roles';

// ==========================================
// PROCESAR ACCIONES - ROLES
// ==========================================

// CREAR ROL
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_rol'])) {
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $permisos = json_encode($_POST['permisos'] ?? []);

    $stmt = $conn->prepare("INSERT INTO roles (nombre, descripcion, permisos, activo, fecha_creacion, creado_por)
                            VALUES (?, ?, ?, 1, NOW(), ?)");
    $stmt->bind_param("sssi", $nombre, $descripcion, $permisos, $_SESSION['usuario_id']);

    if ($stmt->execute()) {
        $mensaje = "Rol '$nombre' creado exitosamente";
        $tipo_mensaje = 'success';
        logAuditoria('crear', 'roles', $conn->insert_id, null, null, "Rol creado: $nombre");
    } else {
        $mensaje = 'Error al crear el rol';
        $tipo_mensaje = 'danger';
    }
    $stmt->close();
}

// EDITAR ROL
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_rol'])) {
    $rol_id = (int)$_POST['rol_id'];
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $permisos = json_encode($_POST['permisos'] ?? []);
    $activo = isset($_POST['activo']) ? 1 : 0;

    $stmt = $conn->prepare("UPDATE roles SET nombre = ?, descripcion = ?, permisos = ?, activo = ?,
                            modificado_por = ?, fecha_modificacion = NOW() WHERE id = ?");
    $stmt->bind_param("sssiii", $nombre, $descripcion, $permisos, $activo, $_SESSION['usuario_id'], $rol_id);

    if ($stmt->execute()) {
        $mensaje = "Rol actualizado exitosamente";
        $tipo_mensaje = 'success';
        logAuditoria('editar', 'roles', $rol_id, null, null, "Rol editado: $nombre");
    } else {
        $mensaje = 'Error al actualizar el rol';
        $tipo_mensaje = 'danger';
    }
    $stmt->close();
}

// ELIMINAR ROL
if (isset($_GET['eliminar_rol'])) {
    $rol_id = (int)$_GET['eliminar_rol'];

    // Verificar que no haya usuarios con este rol
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM usuario_roles WHERE rol_id = ?");
    $stmt->bind_param("i", $rol_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    if ($result['total'] > 0) {
        $mensaje = "No se puede eliminar el rol porque tiene usuarios asignados";
        $tipo_mensaje = 'warning';
    } else {
        $stmt = $conn->prepare("DELETE FROM roles WHERE id = ?");
        $stmt->bind_param("i", $rol_id);
        if ($stmt->execute()) {
            $mensaje = "Rol eliminado exitosamente";
            $tipo_mensaje = 'success';
            logAuditoria('eliminar', 'roles', $rol_id, null, null, "Rol eliminado");
        }
    }
    $stmt->close();
}

// ==========================================
// OBTENER DATOS
// ==========================================

// Obtener roles
$roles = $conn->query("SELECT r.*, u.nombre as creador_nombre, u.apellido as creador_apellido,
                       (SELECT COUNT(*) FROM usuario_roles WHERE rol_id = r.id) as usuarios_count
                       FROM roles r
                       LEFT JOIN usuarios u ON r.creado_por = u.id
                       ORDER BY r.nombre")->fetch_all(MYSQLI_ASSOC);

// Obtener logs de acceso (últimos 100)
$logs_acceso = $conn->query("SELECT la.*, u.nombre, u.apellido
                             FROM logs_acceso la
                             LEFT JOIN usuarios u ON la.usuario_id = u.id
                             ORDER BY la.fecha_acceso DESC
                             LIMIT 100")->fetch_all(MYSQLI_ASSOC);

// Obtener logs de auditoría (últimos 100)
$logs_auditoria = $conn->query("SELECT la.*, u.nombre, u.apellido
                                FROM logs_auditoria la
                                LEFT JOIN usuarios u ON la.usuario_id = u.id
                                ORDER BY la.fecha_accion DESC
                                LIMIT 100")->fetch_all(MYSQLI_ASSOC);

// Estadísticas de seguridad
$stats_seguridad = $conn->query("SELECT
    (SELECT COUNT(*) FROM logs_acceso WHERE DATE(fecha_acceso) = CURDATE()) as accesos_hoy,
    (SELECT COUNT(*) FROM logs_acceso WHERE exitoso = 0 AND DATE(fecha_acceso) = CURDATE()) as intentos_fallidos_hoy,
    (SELECT COUNT(*) FROM roles WHERE activo = 1) as roles_activos,
    (SELECT COUNT(*) FROM sesiones WHERE activo = 1) as sesiones_activas
")->fetch_assoc();

// Módulos del sistema para permisos
$modulos_sistema = [
    'usuarios' => 'Usuarios',
    'empresas' => 'Empresas',
    'clientes' => 'Clientes',
    'proveedores' => 'Proveedores',
    'productos' => 'Productos',
    'ventas' => 'Ventas',
    'compras' => 'Compras',
    'inventario' => 'Inventario',
    'finanzas' => 'Finanzas',
    'contabilidad' => 'Contabilidad',
    'rrhh' => 'Recursos Humanos',
    'reportes' => 'Reportes',
    'configuracion' => 'Configuración'
];

$acciones_permisos = ['crear', 'leer', 'editar', 'eliminar', 'exportar'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Seguridad - CONECTA ERP</title>

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
            font-size: 32px;
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

        .permisos-grid {
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            overflow: hidden;
        }

        .permisos-header {
            background: #f5f7fa;
            padding: 15px;
            font-weight: 600;
            display: grid;
            grid-template-columns: 200px repeat(5, 1fr);
            gap: 10px;
            border-bottom: 2px solid #e2e8f0;
        }

        .permisos-row {
            padding: 15px;
            display: grid;
            grid-template-columns: 200px repeat(5, 1fr);
            gap: 10px;
            border-bottom: 1px solid #e2e8f0;
        }

        .permisos-row:hover {
            background: #f5f7fa;
        }

        .permisos-row:last-child {
            border-bottom: none;
        }

        .form-check-input:checked {
            background-color: #667eea;
            border-color: #667eea;
        }

        .log-item {
            padding: 15px;
            border-left: 4px solid #e2e8f0;
            margin-bottom: 10px;
            background: #f5f7fa;
            border-radius: 0 5px 5px 0;
        }

        .log-item.exitoso {
            border-left-color: #48bb78;
        }

        .log-item.fallido {
            border-left-color: #f56565;
        }

        .log-meta {
            font-size: 12px;
            color: #718096;
            margin-top: 5px;
        }

        .badge-activo { background: #48bb78; }
        .badge-inactivo { background: #a0aec0; }
        .badge-exitoso { background: #48bb78; }
        .badge-fallido { background: #f56565; }

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
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h3><i class="fas fa-rocket"></i> CONECTA ERP</h3>
            <small style="color: rgba(255,255,255,0.7);">Seguridad</small>
        </div>

        <ul class="sidebar-menu">
            <li><a href="../dashboard/index.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="gestion_empresas.php"><i class="fas fa-building"></i> Empresas</a></li>
            <li><a href="gestion_seguridad.php" class="active"><i class="fas fa-shield-alt"></i> Seguridad</a></li>
            <li><a href="parametrizacion_global.php"><i class="fas fa-cog"></i> Parametrización</a></li>
            <li><a href="../../user/dashboard.php"><i class="fas fa-arrow-left"></i> Volver</a></li>
        </ul>
    </div>

    <!-- Topbar -->
    <div class="topbar">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard/index.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="#">Administración</a></li>
                <li class="breadcrumb-item active">Gestión de Seguridad</li>
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
                <h1 class="h3 mb-0">Gestión de Seguridad</h1>
                <p class="text-muted mb-0">Control de acceso, roles, permisos y auditoría</p>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-icon blue">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div class="stats-number"><?php echo number_format($stats_seguridad['roles_activos']); ?></div>
                    <div class="stats-label">Roles Activos</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-icon green">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stats-number"><?php echo number_format($stats_seguridad['sesiones_activas']); ?></div>
                    <div class="stats-label">Sesiones Activas</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-icon orange">
                        <i class="fas fa-sign-in-alt"></i>
                    </div>
                    <div class="stats-number"><?php echo number_format($stats_seguridad['accesos_hoy']); ?></div>
                    <div class="stats-label">Accesos Hoy</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-icon red">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="stats-number"><?php echo number_format($stats_seguridad['intentos_fallidos_hoy']); ?></div>
                    <div class="stats-label">Intentos Fallidos</div>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="content-card">
            <ul class="nav nav-tabs mb-4" role="tablist">
                <li class="nav-item">
                    <a class="nav-link <?php echo $tab_activa === 'roles' ? 'active' : ''; ?>"
                       href="?tab=roles">
                        <i class="fas fa-user-shield"></i> Roles y Permisos
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $tab_activa === 'logs_acceso' ? 'active' : ''; ?>"
                       href="?tab=logs_acceso">
                        <i class="fas fa-history"></i> Logs de Acceso
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $tab_activa === 'auditoria' ? 'active' : ''; ?>"
                       href="?tab=auditoria">
                        <i class="fas fa-clipboard-list"></i> Auditoría
                    </a>
                </li>
            </ul>

            <!-- TAB: Roles y Permisos -->
            <?php if ($tab_activa === 'roles'): ?>
                <div class="content-card-header">
                    <h2 class="content-card-title">Roles del Sistema</h2>
                    <button class="btn btn-gradient" data-bs-toggle="modal" data-bs-target="#modalCrearRol">
                        <i class="fas fa-plus"></i> Nuevo Rol
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover" id="tablaRoles">
                        <thead>
                            <tr>
                                <th>Rol</th>
                                <th>Descripción</th>
                                <th>Usuarios</th>
                                <th>Estado</th>
                                <th>Fecha Creación</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($roles as $rol): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($rol['nombre']); ?></strong></td>
                                <td><?php echo htmlspecialchars($rol['descripcion']); ?></td>
                                <td><span class="badge bg-primary"><?php echo $rol['usuarios_count']; ?></span></td>
                                <td>
                                    <span class="badge badge-<?php echo $rol['activo'] ? 'activo' : 'inactivo'; ?>">
                                        <?php echo $rol['activo'] ? 'Activo' : 'Inactivo'; ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($rol['fecha_creacion'])); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="editarRol(<?php echo $rol['id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php if ($rol['usuarios_count'] == 0): ?>
                                        <a href="?eliminar_rol=<?php echo $rol['id']; ?>" class="btn btn-sm btn-danger"
                                           onclick="return confirm('¿Eliminar este rol?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <!-- TAB: Logs de Acceso -->
            <?php if ($tab_activa === 'logs_acceso'): ?>
                <div class="content-card-header">
                    <h2 class="content-card-title">Registro de Accesos</h2>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-sm" id="tablaLogsAcceso">
                        <thead>
                            <tr>
                                <th>Fecha/Hora</th>
                                <th>Usuario</th>
                                <th>Email</th>
                                <th>Acción</th>
                                <th>IP</th>
                                <th>Estado</th>
                                <th>Mensaje</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs_acceso as $log): ?>
                            <tr>
                                <td><?php echo date('d/m/Y H:i:s', strtotime($log['fecha_acceso'])); ?></td>
                                <td><?php echo htmlspecialchars($log['nombre'] . ' ' . $log['apellido']); ?></td>
                                <td><?php echo htmlspecialchars($log['email']); ?></td>
                                <td><?php echo htmlspecialchars($log['accion']); ?></td>
                                <td><code><?php echo htmlspecialchars($log['ip_address']); ?></code></td>
                                <td>
                                    <span class="badge badge-<?php echo $log['exitoso'] ? 'exitoso' : 'fallido'; ?>">
                                        <?php echo $log['exitoso'] ? 'Exitoso' : 'Fallido'; ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($log['mensaje']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <!-- TAB: Auditoría -->
            <?php if ($tab_activa === 'auditoria'): ?>
                <div class="content-card-header">
                    <h2 class="content-card-title">Auditoría del Sistema</h2>
                </div>

                <div>
                    <?php foreach ($logs_auditoria as $log): ?>
                        <div class="log-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong><?php echo htmlspecialchars($log['accion']); ?></strong>
                                    en <code><?php echo htmlspecialchars($log['tabla']); ?></code>
                                    <?php if ($log['registro_id']): ?>
                                        (ID: <?php echo $log['registro_id']; ?>)
                                    <?php endif; ?>
                                </div>
                                <small class="text-muted"><?php echo date('d/m/Y H:i:s', strtotime($log['fecha_accion'])); ?></small>
                            </div>
                            <?php if ($log['descripcion']): ?>
                                <div class="mt-2"><?php echo htmlspecialchars($log['descripcion']); ?></div>
                            <?php endif; ?>
                            <div class="log-meta">
                                <i class="fas fa-user"></i> <?php echo htmlspecialchars($log['nombre'] . ' ' . $log['apellido']); ?>
                                &nbsp;•&nbsp;
                                <i class="fas fa-network-wired"></i> <?php echo htmlspecialchars($log['ip_address']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal Crear Rol -->
    <div class="modal fade" id="modalCrearRol" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-shield"></i> Nuevo Rol</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Nombre del Rol *</label>
                                <input type="text" name="nombre" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Descripción</label>
                                <input type="text" name="descripcion" class="form-control">
                            </div>
                        </div>

                        <h6 class="mb-3">Permisos del Rol</h6>
                        <div class="permisos-grid">
                            <div class="permisos-header">
                                <div>Módulo</div>
                                <?php foreach ($acciones_permisos as $accion): ?>
                                    <div class="text-center"><?php echo ucfirst($accion); ?></div>
                                <?php endforeach; ?>
                            </div>
                            <?php foreach ($modulos_sistema as $modulo_key => $modulo_nombre): ?>
                                <div class="permisos-row">
                                    <div><strong><?php echo $modulo_nombre; ?></strong></div>
                                    <?php foreach ($acciones_permisos as $accion): ?>
                                        <div class="text-center">
                                            <input type="checkbox" class="form-check-input"
                                                   name="permisos[<?php echo $modulo_key; ?>][<?php echo $accion; ?>]"
                                                   value="1">
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="crear_rol" class="btn btn-gradient">
                            <i class="fas fa-save"></i> Crear Rol
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
        // DataTables
        $('#tablaRoles, #tablaLogsAcceso').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
            },
            order: [[0, 'asc']],
            pageLength: 25
        });

        function editarRol(id) {
            alert('Función en desarrollo: Editar rol ' + id);
        }
    </script>
</body>
</html>
