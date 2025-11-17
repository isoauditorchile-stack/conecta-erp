<?php
/**
 * GESTIÓN DE PROVEEDORES - CONECTA ERP
 * CRUD Completo de Proveedores (PROV)
 * Nivel Empresarial - Estilo SAP/Softland
 */

session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Verificar autenticación
requireLogin();

// Verificar permisos
$puede_gestionar = ($_SESSION['es_super_admin'] == 1) || verificarPermiso('proveedores', 'gestionar');
$puede_crear = ($_SESSION['es_super_admin'] == 1) || verificarPermiso('proveedores', 'crear');
$puede_editar = ($_SESSION['es_super_admin'] == 1) || verificarPermiso('proveedores', 'editar');
$puede_eliminar = ($_SESSION['es_super_admin'] == 1) || verificarPermiso('proveedores', 'eliminar');

$mensaje = '';
$tipo_mensaje = '';

// ==========================================
// PROCESAR ACCIONES CRUD
// ==========================================

// CREAR PROVEEDOR
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_proveedor'])) {
    if (!$puede_crear) {
        $mensaje = 'No tienes permisos para crear proveedores';
        $tipo_mensaje = 'danger';
    } else {
        $rut = limpiarRUT($_POST['rut']);
        $codigo = strtoupper(trim($_POST['codigo']));
        $nombre = trim($_POST['nombre']);
        $razon_social = trim($_POST['razon_social']);
        $email = trim($_POST['email']);
        $telefono = trim($_POST['telefono']);
        $direccion = trim($_POST['direccion']);
        $ciudad = trim($_POST['ciudad']);
        $region = trim($_POST['region']);
        $pais_id = (int)$_POST['pais_id'];
        $contacto_nombre = trim($_POST['contacto_nombre']);
        $contacto_cargo = trim($_POST['contacto_cargo']);
        $contacto_telefono = trim($_POST['contacto_telefono']);
        $contacto_email = trim($_POST['contacto_email']);
        $categoria = $_POST['categoria'];
        $condicion_pago = $_POST['condicion_pago'];
        $plazo_entrega = (int)$_POST['plazo_entrega'];
        $evaluacion = (float)$_POST['evaluacion'];

        // Validaciones
        if (empty($codigo) || empty($nombre)) {
            $mensaje = 'Código y nombre son obligatorios';
            $tipo_mensaje = 'danger';
        } elseif (!empty($rut) && !validarRUT($rut)) {
            $mensaje = 'RUT inválido';
            $tipo_mensaje = 'danger';
        } else {
            // Verificar código único
            $stmt = $conn->prepare("SELECT id FROM proveedores WHERE codigo = ? AND empresa_id = ?");
            $stmt->bind_param("si", $codigo, $_SESSION['empresa_id']);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $mensaje = 'Ya existe un proveedor con este código';
                $tipo_mensaje = 'danger';
            } else {
                // Crear proveedor
                $stmt = $conn->prepare("INSERT INTO proveedores (
                    codigo, rut, nombre, razon_social, email, telefono, direccion, ciudad, region, pais_id,
                    contacto_nombre, contacto_cargo, contacto_telefono, contacto_email,
                    categoria, condicion_pago, plazo_entrega, evaluacion, estado,
                    empresa_id, creado_por, fecha_creacion
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'activo', ?, ?, NOW())");

                $stmt->bind_param("sssssssssississsidii",
                    $codigo, $rut, $nombre, $razon_social, $email, $telefono, $direccion, $ciudad, $region, $pais_id,
                    $contacto_nombre, $contacto_cargo, $contacto_telefono, $contacto_email,
                    $categoria, $condicion_pago, $plazo_entrega, $evaluacion,
                    $_SESSION['empresa_id'], $_SESSION['usuario_id']
                );

                if ($stmt->execute()) {
                    $nuevo_id = $conn->insert_id;
                    $mensaje = "Proveedor '$nombre' creado exitosamente con código $codigo";
                    $tipo_mensaje = 'success';

                    logAuditoria('crear', 'proveedores', $nuevo_id, null, null,
                        "Proveedor creado: $nombre ($codigo)");
                } else {
                    $mensaje = 'Error al crear el proveedor: ' . $stmt->error;
                    $tipo_mensaje = 'danger';
                }
            }
            $stmt->close();
        }
    }
}

// EDITAR PROVEEDOR
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_proveedor'])) {
    if (!$puede_editar) {
        $mensaje = 'No tienes permisos para editar proveedores';
        $tipo_mensaje = 'danger';
    } else {
        $proveedor_id = (int)$_POST['proveedor_id'];
        $rut = limpiarRUT($_POST['rut']);
        $codigo = strtoupper(trim($_POST['codigo']));
        $nombre = trim($_POST['nombre']);
        $razon_social = trim($_POST['razon_social']);
        $email = trim($_POST['email']);
        $telefono = trim($_POST['telefono']);
        $direccion = trim($_POST['direccion']);
        $ciudad = trim($_POST['ciudad']);
        $region = trim($_POST['region']);
        $pais_id = (int)$_POST['pais_id'];
        $contacto_nombre = trim($_POST['contacto_nombre']);
        $contacto_cargo = trim($_POST['contacto_cargo']);
        $contacto_telefono = trim($_POST['contacto_telefono']);
        $contacto_email = trim($_POST['contacto_email']);
        $categoria = $_POST['categoria'];
        $condicion_pago = $_POST['condicion_pago'];
        $plazo_entrega = (int)$_POST['plazo_entrega'];
        $evaluacion = (float)$_POST['evaluacion'];
        $estado = $_POST['estado'];

        $stmt = $conn->prepare("UPDATE proveedores SET
            codigo = ?, rut = ?, nombre = ?, razon_social = ?, email = ?, telefono = ?,
            direccion = ?, ciudad = ?, region = ?, pais_id = ?,
            contacto_nombre = ?, contacto_cargo = ?, contacto_telefono = ?, contacto_email = ?,
            categoria = ?, condicion_pago = ?, plazo_entrega = ?, evaluacion = ?, estado = ?,
            modificado_por = ?, fecha_modificacion = NOW()
            WHERE id = ? AND empresa_id = ?");

        $stmt->bind_param("sssssssssississsidisii",
            $codigo, $rut, $nombre, $razon_social, $email, $telefono, $direccion, $ciudad, $region, $pais_id,
            $contacto_nombre, $contacto_cargo, $contacto_telefono, $contacto_email,
            $categoria, $condicion_pago, $plazo_entrega, $evaluacion, $estado,
            $_SESSION['usuario_id'], $proveedor_id, $_SESSION['empresa_id']
        );

        if ($stmt->execute()) {
            $mensaje = "Proveedor '$nombre' actualizado exitosamente";
            $tipo_mensaje = 'success';

            logAuditoria('editar', 'proveedores', $proveedor_id, null, null,
                "Proveedor editado: $nombre ($codigo)");
        } else {
            $mensaje = 'Error al actualizar el proveedor';
            $tipo_mensaje = 'danger';
        }
        $stmt->close();
    }
}

// ELIMINAR PROVEEDOR
if (isset($_GET['eliminar']) && $puede_eliminar) {
    $proveedor_id = (int)$_GET['eliminar'];

    $stmt = $conn->prepare("SELECT nombre FROM proveedores WHERE id = ? AND empresa_id = ?");
    $stmt->bind_param("ii", $proveedor_id, $_SESSION['empresa_id']);
    $stmt->execute();
    $proveedor = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Soft delete
    $stmt = $conn->prepare("UPDATE proveedores SET estado = 'inactivo', modificado_por = ?, fecha_modificacion = NOW()
                            WHERE id = ? AND empresa_id = ?");
    $stmt->bind_param("iii", $_SESSION['usuario_id'], $proveedor_id, $_SESSION['empresa_id']);

    if ($stmt->execute()) {
        $mensaje = "Proveedor '{$proveedor['nombre']}' desactivado exitosamente";
        $tipo_mensaje = 'success';

        logAuditoria('eliminar', 'proveedores', $proveedor_id, null, null,
            "Proveedor desactivado: {$proveedor['nombre']}");
    }
    $stmt->close();
}

// ACTIVAR PROVEEDOR
if (isset($_GET['activar']) && $puede_editar) {
    $proveedor_id = (int)$_GET['activar'];

    $stmt = $conn->prepare("UPDATE proveedores SET estado = 'activo', modificado_por = ?, fecha_modificacion = NOW()
                            WHERE id = ? AND empresa_id = ?");
    $stmt->bind_param("iii", $_SESSION['usuario_id'], $proveedor_id, $_SESSION['empresa_id']);

    if ($stmt->execute()) {
        $mensaje = "Proveedor activado exitosamente";
        $tipo_mensaje = 'success';
    }
    $stmt->close();
}

// ==========================================
// OBTENER LISTA DE PROVEEDORES
// ==========================================

$filtro_estado = $_GET['estado'] ?? 'todos';
$filtro_categoria = $_GET['categoria'] ?? 'todos';
$filtro_evaluacion = $_GET['evaluacion'] ?? 'todos';
$busqueda = $_GET['buscar'] ?? '';

$query = "SELECT p.*, pa.nombre as pais_nombre,
          u1.nombre as creador_nombre, u1.apellido as creador_apellido
          FROM proveedores p
          LEFT JOIN paises pa ON p.pais_id = pa.id
          LEFT JOIN usuarios u1 ON p.creado_por = u1.id
          WHERE p.empresa_id = {$_SESSION['empresa_id']}";

// Filtros
if ($filtro_estado !== 'todos') {
    $query .= " AND p.estado = '" . $conn->real_escape_string($filtro_estado) . "'";
}

if ($filtro_categoria !== 'todos') {
    $query .= " AND p.categoria = '" . $conn->real_escape_string($filtro_categoria) . "'";
}

if ($filtro_evaluacion !== 'todos') {
    if ($filtro_evaluacion === 'excelente') {
        $query .= " AND p.evaluacion >= 4";
    } elseif ($filtro_evaluacion === 'bueno') {
        $query .= " AND p.evaluacion >= 3 AND p.evaluacion < 4";
    } elseif ($filtro_evaluacion === 'regular') {
        $query .= " AND p.evaluacion >= 2 AND p.evaluacion < 3";
    } elseif ($filtro_evaluacion === 'deficiente') {
        $query .= " AND p.evaluacion < 2";
    }
}

if (!empty($busqueda)) {
    $busqueda_escape = $conn->real_escape_string($busqueda);
    $query .= " AND (p.codigo LIKE '%{$busqueda_escape}%'
                 OR p.nombre LIKE '%{$busqueda_escape}%'
                 OR p.rut LIKE '%{$busqueda_escape}%'
                 OR p.email LIKE '%{$busqueda_escape}%')";
}

$query .= " ORDER BY p.fecha_creacion DESC";

$result = $conn->query($query);
$proveedores = $result->fetch_all(MYSQLI_ASSOC);

// Obtener países
$paises = $conn->query("SELECT id, nombre, codigo FROM paises WHERE activo = 1 ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);

// Estadísticas
$stats = $conn->query("SELECT
    COUNT(*) as total,
    SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END) as activos,
    SUM(CASE WHEN estado = 'inactivo' THEN 1 ELSE 0 END) as inactivos,
    SUM(CASE WHEN evaluacion >= 4 THEN 1 ELSE 0 END) as excelentes,
    SUM(CASE WHEN evaluacion >= 3 AND evaluacion < 4 THEN 1 ELSE 0 END) as buenos,
    SUM(CASE WHEN evaluacion < 3 THEN 1 ELSE 0 END) as deficientes,
    AVG(evaluacion) as promedio_evaluacion
    FROM proveedores
    WHERE empresa_id = {$_SESSION['empresa_id']}")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Proveedores - CONECTA ERP</title>

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

        .badge-activo { background: #48bb78; }
        .badge-inactivo { background: #a0aec0; }

        .table thead th {
            background: #f5f7fa;
            color: #2d3748;
            font-weight: 600;
            border: none;
        }

        .table tbody tr:hover {
            background: #f5f7fa;
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

        .rating-stars {
            color: #ed8936;
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
            <small style="color: rgba(255,255,255,0.7);">Proveedores</small>
        </div>

        <ul class="sidebar-menu">
            <li><a href="../dashboard/index.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="entidades_maestras.php"><i class="fas fa-database"></i> Entidades Maestras</a></li>
            <li><a href="gestion_clientes.php"><i class="fas fa-users"></i> Clientes</a></li>
            <li><a href="gestion_proveedores.php" class="active"><i class="fas fa-truck"></i> Proveedores</a></li>
            <li><a href="gestion_empleados.php"><i class="fas fa-user-tie"></i> Empleados</a></li>
            <li><a href="productos_servicios.php"><i class="fas fa-boxes"></i> Productos</a></li>
            <li><a href="../../user/dashboard.php"><i class="fas fa-arrow-left"></i> Volver</a></li>
        </ul>
    </div>

    <!-- Topbar -->
    <div class="topbar">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard/index.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="entidades_maestras.php">Entidades</a></li>
                <li class="breadcrumb-item active">Proveedores</li>
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
                <h1 class="h3 mb-0">Gestión de Proveedores</h1>
                <p class="text-muted mb-0">Administra la red de proveedores de tu empresa</p>
            </div>
            <?php if ($puede_crear): ?>
                <button class="btn btn-gradient" data-bs-toggle="modal" data-bs-target="#modalCrearProveedor">
                    <i class="fas fa-plus"></i> Nuevo Proveedor
                </button>
            <?php endif; ?>
        </div>

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-icon blue">
                        <i class="fas fa-truck"></i>
                    </div>
                    <div class="stats-number"><?php echo number_format($stats['total']); ?></div>
                    <div class="stats-label">Total Proveedores</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-icon green">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stats-number"><?php echo number_format($stats['activos']); ?></div>
                    <div class="stats-label">Activos</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-icon orange">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stats-number"><?php echo number_format($stats['excelentes']); ?></div>
                    <div class="stats-label">Excelentes (4+)</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-icon red">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stats-number"><?php echo number_format($stats['promedio_evaluacion'], 1); ?></div>
                    <div class="stats-label">Evaluación Promedio</div>
                </div>
            </div>
        </div>

        <!-- Content Card -->
        <div class="content-card">
            <div class="content-card-header">
                <h2 class="content-card-title">Listado de Proveedores</h2>
            </div>

            <!-- Filtros -->
            <div class="filters">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label">Estado</label>
                        <select name="estado" class="form-select" onchange="this.form.submit()">
                            <option value="todos" <?php echo $filtro_estado === 'todos' ? 'selected' : ''; ?>>Todos</option>
                            <option value="activo" <?php echo $filtro_estado === 'activo' ? 'selected' : ''; ?>>Activos</option>
                            <option value="inactivo" <?php echo $filtro_estado === 'inactivo' ? 'selected' : ''; ?>>Inactivos</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Categoría</label>
                        <select name="categoria" class="form-select" onchange="this.form.submit()">
                            <option value="todos" <?php echo $filtro_categoria === 'todos' ? 'selected' : ''; ?>>Todas</option>
                            <option value="materias_primas" <?php echo $filtro_categoria === 'materias_primas' ? 'selected' : ''; ?>>Materias Primas</option>
                            <option value="servicios" <?php echo $filtro_categoria === 'servicios' ? 'selected' : ''; ?>>Servicios</option>
                            <option value="equipamiento" <?php echo $filtro_categoria === 'equipamiento' ? 'selected' : ''; ?>>Equipamiento</option>
                            <option value="otros" <?php echo $filtro_categoria === 'otros' ? 'selected' : ''; ?>>Otros</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Evaluación</label>
                        <select name="evaluacion" class="form-select" onchange="this.form.submit()">
                            <option value="todos" <?php echo $filtro_evaluacion === 'todos' ? 'selected' : ''; ?>>Todas</option>
                            <option value="excelente" <?php echo $filtro_evaluacion === 'excelente' ? 'selected' : ''; ?>>Excelente (4+)</option>
                            <option value="bueno" <?php echo $filtro_evaluacion === 'bueno' ? 'selected' : ''; ?>>Bueno (3-4)</option>
                            <option value="regular" <?php echo $filtro_evaluacion === 'regular' ? 'selected' : ''; ?>>Regular (2-3)</option>
                            <option value="deficiente" <?php echo $filtro_evaluacion === 'deficiente' ? 'selected' : ''; ?>>Deficiente (&lt;2)</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Buscar</label>
                        <input type="text" name="buscar" class="form-control" placeholder="Código, nombre, RUT..." value="<?php echo htmlspecialchars($busqueda); ?>">
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
                <table class="table table-hover" id="tablaProveedores">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>RUT</th>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Teléfono</th>
                            <th>Categoría</th>
                            <th>Evaluación</th>
                            <th>Plazo</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($proveedores as $proveedor): ?>
                        <tr>
                            <td><strong><code><?php echo htmlspecialchars($proveedor['codigo']); ?></code></strong></td>
                            <td><?php echo $proveedor['rut'] ? formatearRUT($proveedor['rut']) : '-'; ?></td>
                            <td><?php echo htmlspecialchars($proveedor['nombre']); ?></td>
                            <td><?php echo htmlspecialchars($proveedor['email']); ?></td>
                            <td><?php echo htmlspecialchars($proveedor['telefono']); ?></td>
                            <td><?php echo ucwords(str_replace('_', ' ', $proveedor['categoria'])); ?></td>
                            <td>
                                <span class="rating-stars">
                                    <?php
                                    $rating = $proveedor['evaluacion'];
                                    for ($i = 1; $i <= 5; $i++) {
                                        if ($i <= $rating) {
                                            echo '<i class="fas fa-star"></i>';
                                        } elseif ($i - 0.5 <= $rating) {
                                            echo '<i class="fas fa-star-half-alt"></i>';
                                        } else {
                                            echo '<i class="far fa-star"></i>';
                                        }
                                    }
                                    ?>
                                </span>
                                <small class="text-muted">(<?php echo number_format($rating, 1); ?>)</small>
                            </td>
                            <td><?php echo $proveedor['plazo_entrega']; ?> días</td>
                            <td>
                                <span class="badge badge-<?php echo $proveedor['estado']; ?>">
                                    <?php echo ucfirst($proveedor['estado']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <?php if ($puede_editar): ?>
                                        <button class="btn btn-sm btn-primary" onclick="alert('Editar proveedor <?php echo $proveedor['id']; ?>')">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($puede_eliminar): ?>
                                        <?php if ($proveedor['estado'] === 'activo'): ?>
                                            <a href="?eliminar=<?php echo $proveedor['id']; ?>" class="btn btn-sm btn-danger"
                                               onclick="return confirm('¿Desactivar este proveedor?')">
                                                <i class="fas fa-ban"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="?activar=<?php echo $proveedor['id']; ?>" class="btn btn-sm btn-success">
                                                <i class="fas fa-check"></i>
                                            </a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <button class="btn btn-sm btn-info" onclick="alert('Ver detalle')">
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
    </div>

    <!-- Modal Crear Proveedor -->
    <div class="modal fade" id="modalCrearProveedor" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-truck"></i> Nuevo Proveedor</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Código Proveedor *</label>
                                <input type="text" name="codigo" class="form-control" placeholder="PROV-001" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">RUT</label>
                                <input type="text" name="rut" class="form-control" data-rut>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nombre *</label>
                                <input type="text" name="nombre" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Razón Social</label>
                                <input type="text" name="razon_social" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Teléfono</label>
                                <input type="text" name="telefono" class="form-control">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Dirección</label>
                                <input type="text" name="direccion" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Ciudad</label>
                                <input type="text" name="ciudad" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Región</label>
                                <input type="text" name="region" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">País</label>
                                <select name="pais_id" class="form-select">
                                    <?php foreach ($paises as $pais): ?>
                                        <option value="<?php echo $pais['id']; ?>" <?php echo $pais['codigo'] === 'CL' ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($pais['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Contacto - Nombre</label>
                                <input type="text" name="contacto_nombre" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Contacto - Cargo</label>
                                <input type="text" name="contacto_cargo" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Contacto - Teléfono</label>
                                <input type="text" name="contacto_telefono" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Contacto - Email</label>
                                <input type="email" name="contacto_email" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Categoría</label>
                                <select name="categoria" class="form-select">
                                    <option value="materias_primas">Materias Primas</option>
                                    <option value="servicios">Servicios</option>
                                    <option value="equipamiento">Equipamiento</option>
                                    <option value="otros">Otros</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Condición de Pago</label>
                                <select name="condicion_pago" class="form-select">
                                    <option value="contado">Contado</option>
                                    <option value="credito_30">Crédito 30 días</option>
                                    <option value="credito_60">Crédito 60 días</option>
                                    <option value="credito_90">Crédito 90 días</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Plazo Entrega (días)</label>
                                <input type="number" name="plazo_entrega" class="form-control" value="7">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Evaluación (1-5)</label>
                                <input type="number" name="evaluacion" class="form-control" value="3" min="1" max="5" step="0.5">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="crear_proveedor" class="btn btn-gradient">
                            <i class="fas fa-save"></i> Crear Proveedor
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
    <script src="../../assets/js/rut_validator.js"></script>

    <script>
        $('#tablaProveedores').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
            },
            order: [[0, 'asc']],
            pageLength: 25
        });
    </script>

    <!-- Footer Profesional -->
    <?php include '../includes/footer.php'; ?>

</body>
</html>
