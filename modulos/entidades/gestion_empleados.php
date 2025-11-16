<?php
/**
 * GESTIÓN DE EMPLEADOS - CONECTA ERP
 * CRUD Completo de Empleados (EMPL)
 * Nivel Empresarial - Estilo SAP/Softland
 */

session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Verificar autenticación
requireLogin();

// Verificar permisos
$puede_gestionar = ($_SESSION['es_super_admin'] == 1) || verificarPermiso('empleados', 'gestionar');
$puede_crear = ($_SESSION['es_super_admin'] == 1) || verificarPermiso('empleados', 'crear');
$puede_editar = ($_SESSION['es_super_admin'] == 1) || verificarPermiso('empleados', 'editar');
$puede_eliminar = ($_SESSION['es_super_admin'] == 1) || verificarPermiso('empleados', 'eliminar');

$mensaje = '';
$tipo_mensaje = '';

// ==========================================
// PROCESAR ACCIONES CRUD
// ==========================================

// CREAR EMPLEADO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_empleado'])) {
    if (!$puede_crear) {
        $mensaje = 'No tienes permisos para crear empleados';
        $tipo_mensaje = 'danger';
    } else {
        $rut = limpiarRUT($_POST['rut']);
        $codigo = strtoupper(trim($_POST['codigo']));
        $nombre = trim($_POST['nombre']);
        $apellido = trim($_POST['apellido']);
        $email = trim($_POST['email']);
        $telefono = trim($_POST['telefono']);
        $direccion = trim($_POST['direccion']);
        $ciudad = trim($_POST['ciudad']);
        $fecha_nacimiento = $_POST['fecha_nacimiento'];
        $cargo = trim($_POST['cargo']);
        $departamento = trim($_POST['departamento']);
        $fecha_ingreso = $_POST['fecha_ingreso'];
        $tipo_contrato = $_POST['tipo_contrato'];
        $salario = (float)$_POST['salario'];
        $banco = trim($_POST['banco']);
        $tipo_cuenta = $_POST['tipo_cuenta'];
        $numero_cuenta = trim($_POST['numero_cuenta']);

        // Validaciones
        if (empty($rut) || empty($nombre) || empty($apellido)) {
            $mensaje = 'RUT, nombre y apellido son obligatorios';
            $tipo_mensaje = 'danger';
        } elseif (!validarRUT($rut)) {
            $mensaje = 'RUT inválido';
            $tipo_mensaje = 'danger';
        } else {
            // Verificar RUT único
            $stmt = $conn->prepare("SELECT id FROM empleados WHERE rut = ? AND empresa_id = ?");
            $stmt->bind_param("si", $rut, $_SESSION['empresa_id']);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $mensaje = 'Ya existe un empleado con este RUT';
                $tipo_mensaje = 'danger';
            } else {
                // Crear empleado
                $stmt = $conn->prepare("INSERT INTO empleados (
                    codigo, rut, nombre, apellido, email, telefono, direccion, ciudad, fecha_nacimiento,
                    cargo, departamento, fecha_ingreso, tipo_contrato, salario,
                    banco, tipo_cuenta, numero_cuenta, estado,
                    empresa_id, creado_por, fecha_creacion
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'activo', ?, ?, NOW())");

                $stmt->bind_param("sssssssssssssdsssii",
                    $codigo, $rut, $nombre, $apellido, $email, $telefono, $direccion, $ciudad, $fecha_nacimiento,
                    $cargo, $departamento, $fecha_ingreso, $tipo_contrato, $salario,
                    $banco, $tipo_cuenta, $numero_cuenta,
                    $_SESSION['empresa_id'], $_SESSION['usuario_id']
                );

                if ($stmt->execute()) {
                    $nuevo_id = $conn->insert_id;
                    $mensaje = "Empleado '$nombre $apellido' creado exitosamente con código $codigo";
                    $tipo_mensaje = 'success';

                    logAuditoria('crear', 'empleados', $nuevo_id, null, null,
                        "Empleado creado: $nombre $apellido ($codigo)");
                } else {
                    $mensaje = 'Error al crear el empleado: ' . $stmt->error;
                    $tipo_mensaje = 'danger';
                }
            }
            $stmt->close();
        }
    }
}

// EDITAR EMPLEADO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_empleado'])) {
    if (!$puede_editar) {
        $mensaje = 'No tienes permisos para editar empleados';
        $tipo_mensaje = 'danger';
    } else {
        $empleado_id = (int)$_POST['empleado_id'];
        $codigo = strtoupper(trim($_POST['codigo']));
        $nombre = trim($_POST['nombre']);
        $apellido = trim($_POST['apellido']);
        $email = trim($_POST['email']);
        $telefono = trim($_POST['telefono']);
        $direccion = trim($_POST['direccion']);
        $ciudad = trim($_POST['ciudad']);
        $cargo = trim($_POST['cargo']);
        $departamento = trim($_POST['departamento']);
        $tipo_contrato = $_POST['tipo_contrato'];
        $salario = (float)$_POST['salario'];
        $estado = $_POST['estado'];

        $stmt = $conn->prepare("UPDATE empleados SET
            codigo = ?, nombre = ?, apellido = ?, email = ?, telefono = ?,
            direccion = ?, ciudad = ?, cargo = ?, departamento = ?, tipo_contrato = ?,
            salario = ?, estado = ?, modificado_por = ?, fecha_modificacion = NOW()
            WHERE id = ? AND empresa_id = ?");

        $stmt->bind_param("ssssssssssdsiid",
            $codigo, $nombre, $apellido, $email, $telefono, $direccion, $ciudad,
            $cargo, $departamento, $tipo_contrato, $salario, $estado,
            $_SESSION['usuario_id'], $empleado_id, $_SESSION['empresa_id']
        );

        if ($stmt->execute()) {
            $mensaje = "Empleado '$nombre $apellido' actualizado exitosamente";
            $tipo_mensaje = 'success';

            logAuditoria('editar', 'empleados', $empleado_id, null, null,
                "Empleado editado: $nombre $apellido");
        } else {
            $mensaje = 'Error al actualizar el empleado';
            $tipo_mensaje = 'danger';
        }
        $stmt->close();
    }
}

// ELIMINAR EMPLEADO
if (isset($_GET['eliminar']) && $puede_eliminar) {
    $empleado_id = (int)$_GET['eliminar'];

    $stmt = $conn->prepare("SELECT nombre, apellido FROM empleados WHERE id = ? AND empresa_id = ?");
    $stmt->bind_param("ii", $empleado_id, $_SESSION['empresa_id']);
    $stmt->execute();
    $empleado = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Soft delete
    $stmt = $conn->prepare("UPDATE empleados SET estado = 'inactivo', modificado_por = ?, fecha_modificacion = NOW()
                            WHERE id = ? AND empresa_id = ?");
    $stmt->bind_param("iii", $_SESSION['usuario_id'], $empleado_id, $_SESSION['empresa_id']);

    if ($stmt->execute()) {
        $mensaje = "Empleado '{$empleado['nombre']} {$empleado['apellido']}' desactivado exitosamente";
        $tipo_mensaje = 'success';

        logAuditoria('eliminar', 'empleados', $empleado_id, null, null,
            "Empleado desactivado: {$empleado['nombre']} {$empleado['apellido']}");
    }
    $stmt->close();
}

// ACTIVAR EMPLEADO
if (isset($_GET['activar']) && $puede_editar) {
    $empleado_id = (int)$_GET['activar'];

    $stmt = $conn->prepare("UPDATE empleados SET estado = 'activo', modificado_por = ?, fecha_modificacion = NOW()
                            WHERE id = ? AND empresa_id = ?");
    $stmt->bind_param("iii", $_SESSION['usuario_id'], $empleado_id, $_SESSION['empresa_id']);

    if ($stmt->execute()) {
        $mensaje = "Empleado activado exitosamente";
        $tipo_mensaje = 'success';
    }
    $stmt->close();
}

// ==========================================
// OBTENER LISTA DE EMPLEADOS
// ==========================================

$filtro_estado = $_GET['estado'] ?? 'todos';
$filtro_departamento = $_GET['departamento'] ?? 'todos';
$busqueda = $_GET['buscar'] ?? '';

$query = "SELECT e.*,
          u1.nombre as creador_nombre, u1.apellido as creador_apellido
          FROM empleados e
          LEFT JOIN usuarios u1 ON e.creado_por = u1.id
          WHERE e.empresa_id = {$_SESSION['empresa_id']}";

// Filtros
if ($filtro_estado !== 'todos') {
    $query .= " AND e.estado = '" . $conn->real_escape_string($filtro_estado) . "'";
}

if ($filtro_departamento !== 'todos') {
    $query .= " AND e.departamento = '" . $conn->real_escape_string($filtro_departamento) . "'";
}

if (!empty($busqueda)) {
    $busqueda_escape = $conn->real_escape_string($busqueda);
    $query .= " AND (e.codigo LIKE '%{$busqueda_escape}%'
                 OR e.nombre LIKE '%{$busqueda_escape}%'
                 OR e.apellido LIKE '%{$busqueda_escape}%'
                 OR e.rut LIKE '%{$busqueda_escape}%'
                 OR e.email LIKE '%{$busqueda_escape}%'
                 OR e.cargo LIKE '%{$busqueda_escape}%')";
}

$query .= " ORDER BY e.fecha_creacion DESC";

$result = $conn->query($query);
$empleados = $result->fetch_all(MYSQLI_ASSOC);

// Obtener departamentos únicos
$departamentos = $conn->query("SELECT DISTINCT departamento FROM empleados
                               WHERE empresa_id = {$_SESSION['empresa_id']}
                               AND departamento IS NOT NULL AND departamento != ''
                               ORDER BY departamento")->fetch_all(MYSQLI_ASSOC);

// Estadísticas
$stats = $conn->query("SELECT
    COUNT(*) as total,
    SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END) as activos,
    SUM(CASE WHEN estado = 'inactivo' THEN 1 ELSE 0 END) as inactivos,
    SUM(CASE WHEN estado = 'vacaciones' THEN 1 ELSE 0 END) as vacaciones,
    SUM(CASE WHEN estado = 'licencia' THEN 1 ELSE 0 END) as licencias,
    COUNT(DISTINCT departamento) as total_departamentos,
    SUM(salario) as masa_salarial
    FROM empleados
    WHERE empresa_id = {$_SESSION['empresa_id']}")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Empleados - CONECTA ERP</title>

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
        .badge-vacaciones { background: #ed8936; }
        .badge-licencia { background: #f56565; }

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
            <small style="color: rgba(255,255,255,0.7);">RRHH</small>
        </div>

        <ul class="sidebar-menu">
            <li><a href="../dashboard/index.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="entidades_maestras.php"><i class="fas fa-database"></i> Entidades Maestras</a></li>
            <li><a href="gestion_clientes.php"><i class="fas fa-users"></i> Clientes</a></li>
            <li><a href="gestion_proveedores.php"><i class="fas fa-truck"></i> Proveedores</a></li>
            <li><a href="gestion_empleados.php" class="active"><i class="fas fa-user-tie"></i> Empleados</a></li>
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
                <li class="breadcrumb-item active">Empleados</li>
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
                <h1 class="h3 mb-0">Gestión de Empleados</h1>
                <p class="text-muted mb-0">Administra el personal de tu empresa</p>
            </div>
            <?php if ($puede_crear): ?>
                <button class="btn btn-gradient" data-bs-toggle="modal" data-bs-target="#modalCrearEmpleado">
                    <i class="fas fa-plus"></i> Nuevo Empleado
                </button>
            <?php endif; ?>
        </div>

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-icon blue">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div class="stats-number"><?php echo number_format($stats['total']); ?></div>
                    <div class="stats-label">Total Empleados</div>
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
                        <i class="fas fa-sitemap"></i>
                    </div>
                    <div class="stats-number"><?php echo number_format($stats['total_departamentos']); ?></div>
                    <div class="stats-label">Departamentos</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-icon red">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stats-number">$<?php echo number_format($stats['masa_salarial'], 0, ',', '.'); ?></div>
                    <div class="stats-label">Masa Salarial</div>
                </div>
            </div>
        </div>

        <!-- Content Card -->
        <div class="content-card">
            <div class="content-card-header">
                <h2 class="content-card-title">Listado de Empleados</h2>
            </div>

            <!-- Filtros -->
            <div class="filters">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Estado</label>
                        <select name="estado" class="form-select" onchange="this.form.submit()">
                            <option value="todos" <?php echo $filtro_estado === 'todos' ? 'selected' : ''; ?>>Todos</option>
                            <option value="activo" <?php echo $filtro_estado === 'activo' ? 'selected' : ''; ?>>Activos</option>
                            <option value="inactivo" <?php echo $filtro_estado === 'inactivo' ? 'selected' : ''; ?>>Inactivos</option>
                            <option value="vacaciones" <?php echo $filtro_estado === 'vacaciones' ? 'selected' : ''; ?>>En Vacaciones</option>
                            <option value="licencia" <?php echo $filtro_estado === 'licencia' ? 'selected' : ''; ?>>En Licencia</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Departamento</label>
                        <select name="departamento" class="form-select" onchange="this.form.submit()">
                            <option value="todos" <?php echo $filtro_departamento === 'todos' ? 'selected' : ''; ?>>Todos</option>
                            <?php foreach ($departamentos as $dept): ?>
                                <option value="<?php echo htmlspecialchars($dept['departamento']); ?>"
                                        <?php echo $filtro_departamento === $dept['departamento'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept['departamento']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Buscar</label>
                        <input type="text" name="buscar" class="form-control" placeholder="Nombre, RUT, cargo..." value="<?php echo htmlspecialchars($busqueda); ?>">
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
                <table class="table table-hover" id="tablaEmpleados">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>RUT</th>
                            <th>Nombre Completo</th>
                            <th>Email</th>
                            <th>Cargo</th>
                            <th>Departamento</th>
                            <th>Fecha Ingreso</th>
                            <th>Salario</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($empleados as $empleado): ?>
                        <tr>
                            <td><strong><code><?php echo htmlspecialchars($empleado['codigo']); ?></code></strong></td>
                            <td><?php echo formatearRUT($empleado['rut']); ?></td>
                            <td><?php echo htmlspecialchars($empleado['nombre'] . ' ' . $empleado['apellido']); ?></td>
                            <td><?php echo htmlspecialchars($empleado['email']); ?></td>
                            <td><?php echo htmlspecialchars($empleado['cargo']); ?></td>
                            <td><?php echo htmlspecialchars($empleado['departamento']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($empleado['fecha_ingreso'])); ?></td>
                            <td>$<?php echo number_format($empleado['salario'], 0, ',', '.'); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $empleado['estado']; ?>">
                                    <?php echo ucfirst($empleado['estado']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <?php if ($puede_editar): ?>
                                        <button class="btn btn-sm btn-primary" onclick="alert('Editar empleado <?php echo $empleado['id']; ?>')">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($puede_eliminar): ?>
                                        <?php if ($empleado['estado'] === 'activo'): ?>
                                            <a href="?eliminar=<?php echo $empleado['id']; ?>" class="btn btn-sm btn-danger"
                                               onclick="return confirm('¿Desactivar este empleado?')">
                                                <i class="fas fa-ban"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="?activar=<?php echo $empleado['id']; ?>" class="btn btn-sm btn-success">
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

    <!-- Modal Crear Empleado -->
    <div class="modal fade" id="modalCrearEmpleado" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-tie"></i> Nuevo Empleado</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Código Empleado *</label>
                                <input type="text" name="codigo" class="form-control" placeholder="EMP-001" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">RUT *</label>
                                <input type="text" name="rut" class="form-control" data-rut required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Fecha Nacimiento</label>
                                <input type="date" name="fecha_nacimiento" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nombre *</label>
                                <input type="text" name="nombre" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Apellido *</label>
                                <input type="text" name="apellido" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Teléfono</label>
                                <input type="text" name="telefono" class="form-control">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Dirección</label>
                                <input type="text" name="direccion" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Ciudad</label>
                                <input type="text" name="ciudad" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Cargo *</label>
                                <input type="text" name="cargo" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Departamento *</label>
                                <input type="text" name="departamento" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Fecha Ingreso *</label>
                                <input type="date" name="fecha_ingreso" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Tipo de Contrato</label>
                                <select name="tipo_contrato" class="form-select">
                                    <option value="indefinido">Indefinido</option>
                                    <option value="plazo_fijo">Plazo Fijo</option>
                                    <option value="honorarios">Honorarios</option>
                                    <option value="part_time">Part Time</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Salario Bruto</label>
                                <input type="number" name="salario" class="form-control" value="0" step="1000">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Banco</label>
                                <input type="text" name="banco" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Tipo de Cuenta</label>
                                <select name="tipo_cuenta" class="form-select">
                                    <option value="corriente">Cuenta Corriente</option>
                                    <option value="vista">Cuenta Vista</option>
                                    <option value="ahorro">Cuenta de Ahorro</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Número de Cuenta</label>
                                <input type="text" name="numero_cuenta" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="crear_empleado" class="btn btn-gradient">
                            <i class="fas fa-save"></i> Crear Empleado
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
        $('#tablaEmpleados').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
            },
            order: [[0, 'asc']],
            pageLength: 25
        });
    </script>
</body>
</html>
