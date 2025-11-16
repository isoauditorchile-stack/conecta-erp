<?php
/**
 * GESTIÓN DE CLIENTES - CONECTA ERP
 * CRUD Completo de Clientes (CLIE)
 * Nivel Empresarial - Estilo SAP/Softland
 */

session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Verificar autenticación
requireLogin();

// Verificar permisos
$puede_gestionar = ($_SESSION['es_super_admin'] == 1) || verificarPermiso('clientes', 'gestionar');
$puede_crear = ($_SESSION['es_super_admin'] == 1) || verificarPermiso('clientes', 'crear');
$puede_editar = ($_SESSION['es_super_admin'] == 1) || verificarPermiso('clientes', 'editar');
$puede_eliminar = ($_SESSION['es_super_admin'] == 1) || verificarPermiso('clientes', 'eliminar');

$mensaje = '';
$tipo_mensaje = '';

// ==========================================
// PROCESAR ACCIONES CRUD
// ==========================================

// CREAR CLIENTE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_cliente'])) {
    if (!$puede_crear) {
        $mensaje = 'No tienes permisos para crear clientes';
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
        $clasificacion = $_POST['clasificacion'];
        $limite_credito = (float)$_POST['limite_credito'];
        $dias_credito = (int)$_POST['dias_credito'];

        // Validaciones
        if (empty($codigo) || empty($nombre)) {
            $mensaje = 'Código y nombre son obligatorios';
            $tipo_mensaje = 'danger';
        } elseif (!empty($rut) && !validarRUT($rut)) {
            $mensaje = 'RUT inválido';
            $tipo_mensaje = 'danger';
        } else {
            // Verificar código único
            $stmt = $conn->prepare("SELECT id FROM clientes WHERE codigo = ? AND empresa_id = ?");
            $stmt->bind_param("si", $codigo, $_SESSION['empresa_id']);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $mensaje = 'Ya existe un cliente con este código';
                $tipo_mensaje = 'danger';
            } else {
                // Crear cliente
                $stmt = $conn->prepare("INSERT INTO clientes (
                    codigo, rut, nombre, razon_social, email, telefono, direccion, ciudad, region, pais_id,
                    contacto_nombre, contacto_cargo, contacto_telefono, contacto_email,
                    clasificacion, limite_credito, dias_credito, estado,
                    empresa_id, creado_por, fecha_creacion
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'activo', ?, ?, NOW())");

                $stmt->bind_param("sssssssssississdiii",
                    $codigo, $rut, $nombre, $razon_social, $email, $telefono, $direccion, $ciudad, $region, $pais_id,
                    $contacto_nombre, $contacto_cargo, $contacto_telefono, $contacto_email,
                    $clasificacion, $limite_credito, $dias_credito,
                    $_SESSION['empresa_id'], $_SESSION['usuario_id']
                );

                if ($stmt->execute()) {
                    $nuevo_id = $conn->insert_id;
                    $mensaje = "Cliente '$nombre' creado exitosamente con código $codigo";
                    $tipo_mensaje = 'success';

                    logAuditoria('crear', 'clientes', $nuevo_id, null, null,
                        "Cliente creado: $nombre ($codigo)");
                } else {
                    $mensaje = 'Error al crear el cliente: ' . $stmt->error;
                    $tipo_mensaje = 'danger';
                }
            }
            $stmt->close();
        }
    }
}

// EDITAR CLIENTE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_cliente'])) {
    if (!$puede_editar) {
        $mensaje = 'No tienes permisos para editar clientes';
        $tipo_mensaje = 'danger';
    } else {
        $cliente_id = (int)$_POST['cliente_id'];
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
        $clasificacion = $_POST['clasificacion'];
        $limite_credito = (float)$_POST['limite_credito'];
        $dias_credito = (int)$_POST['dias_credito'];
        $estado = $_POST['estado'];

        $stmt = $conn->prepare("UPDATE clientes SET
            codigo = ?, rut = ?, nombre = ?, razon_social = ?, email = ?, telefono = ?,
            direccion = ?, ciudad = ?, region = ?, pais_id = ?,
            contacto_nombre = ?, contacto_cargo = ?, contacto_telefono = ?, contacto_email = ?,
            clasificacion = ?, limite_credito = ?, dias_credito = ?, estado = ?,
            modificado_por = ?, fecha_modificacion = NOW()
            WHERE id = ? AND empresa_id = ?");

        $stmt->bind_param("sssssssssississdisiii",
            $codigo, $rut, $nombre, $razon_social, $email, $telefono, $direccion, $ciudad, $region, $pais_id,
            $contacto_nombre, $contacto_cargo, $contacto_telefono, $contacto_email,
            $clasificacion, $limite_credito, $dias_credito, $estado,
            $_SESSION['usuario_id'], $cliente_id, $_SESSION['empresa_id']
        );

        if ($stmt->execute()) {
            $mensaje = "Cliente '$nombre' actualizado exitosamente";
            $tipo_mensaje = 'success';

            logAuditoria('editar', 'clientes', $cliente_id, null, null,
                "Cliente editado: $nombre ($codigo)");
        } else {
            $mensaje = 'Error al actualizar el cliente';
            $tipo_mensaje = 'danger';
        }
        $stmt->close();
    }
}

// ELIMINAR CLIENTE
if (isset($_GET['eliminar']) && $puede_eliminar) {
    $cliente_id = (int)$_GET['eliminar'];

    $stmt = $conn->prepare("SELECT nombre FROM clientes WHERE id = ? AND empresa_id = ?");
    $stmt->bind_param("ii", $cliente_id, $_SESSION['empresa_id']);
    $stmt->execute();
    $cliente = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Soft delete
    $stmt = $conn->prepare("UPDATE clientes SET estado = 'inactivo', modificado_por = ?, fecha_modificacion = NOW()
                            WHERE id = ? AND empresa_id = ?");
    $stmt->bind_param("iii", $_SESSION['usuario_id'], $cliente_id, $_SESSION['empresa_id']);

    if ($stmt->execute()) {
        $mensaje = "Cliente '{$cliente['nombre']}' desactivado exitosamente";
        $tipo_mensaje = 'success';

        logAuditoria('eliminar', 'clientes', $cliente_id, null, null,
            "Cliente desactivado: {$cliente['nombre']}");
    }
    $stmt->close();
}

// ACTIVAR CLIENTE
if (isset($_GET['activar']) && $puede_editar) {
    $cliente_id = (int)$_GET['activar'];

    $stmt = $conn->prepare("UPDATE clientes SET estado = 'activo', modificado_por = ?, fecha_modificacion = NOW()
                            WHERE id = ? AND empresa_id = ?");
    $stmt->bind_param("iii", $_SESSION['usuario_id'], $cliente_id, $_SESSION['empresa_id']);

    if ($stmt->execute()) {
        $mensaje = "Cliente activado exitosamente";
        $tipo_mensaje = 'success';
    }
    $stmt->close();
}

// ==========================================
// OBTENER LISTA DE CLIENTES
// ==========================================

$filtro_estado = $_GET['estado'] ?? 'todos';
$filtro_clasificacion = $_GET['clasificacion'] ?? 'todos';
$busqueda = $_GET['buscar'] ?? '';

$query = "SELECT c.*, p.nombre as pais_nombre,
          u1.nombre as creador_nombre, u1.apellido as creador_apellido
          FROM clientes c
          LEFT JOIN paises p ON c.pais_id = p.id
          LEFT JOIN usuarios u1 ON c.creado_por = u1.id
          WHERE c.empresa_id = {$_SESSION['empresa_id']}";

// Filtros
if ($filtro_estado !== 'todos') {
    $query .= " AND c.estado = '" . $conn->real_escape_string($filtro_estado) . "'";
}

if ($filtro_clasificacion !== 'todos') {
    $query .= " AND c.clasificacion = '" . $conn->real_escape_string($filtro_clasificacion) . "'";
}

if (!empty($busqueda)) {
    $busqueda_escape = $conn->real_escape_string($busqueda);
    $query .= " AND (c.codigo LIKE '%{$busqueda_escape}%'
                 OR c.nombre LIKE '%{$busqueda_escape}%'
                 OR c.rut LIKE '%{$busqueda_escape}%'
                 OR c.email LIKE '%{$busqueda_escape}%')";
}

$query .= " ORDER BY c.fecha_creacion DESC";

$result = $conn->query($query);
$clientes = $result->fetch_all(MYSQLI_ASSOC);

// Obtener países
$paises = $conn->query("SELECT id, nombre, codigo FROM paises WHERE activo = 1 ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);

// Estadísticas
$stats = $conn->query("SELECT
    COUNT(*) as total,
    SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END) as activos,
    SUM(CASE WHEN estado = 'inactivo' THEN 1 ELSE 0 END) as inactivos,
    SUM(CASE WHEN clasificacion = 'A' THEN 1 ELSE 0 END) as clase_a,
    SUM(CASE WHEN clasificacion = 'B' THEN 1 ELSE 0 END) as clase_b,
    SUM(CASE WHEN clasificacion = 'C' THEN 1 ELSE 0 END) as clase_c,
    SUM(limite_credito) as credito_total
    FROM clientes
    WHERE empresa_id = {$_SESSION['empresa_id']}")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Clientes - CONECTA ERP</title>

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
        .badge-clase-a { background: #48bb78; }
        .badge-clase-b { background: #ed8936; }
        .badge-clase-c { background: #f56565; }

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
            <small style="color: rgba(255,255,255,0.7);">Clientes</small>
        </div>

        <ul class="sidebar-menu">
            <li><a href="../dashboard/index.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="entidades_maestras.php"><i class="fas fa-database"></i> Entidades Maestras</a></li>
            <li><a href="gestion_clientes.php" class="active"><i class="fas fa-users"></i> Clientes</a></li>
            <li><a href="gestion_proveedores.php"><i class="fas fa-truck"></i> Proveedores</a></li>
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
                <li class="breadcrumb-item active">Clientes</li>
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
                <h1 class="h3 mb-0">Gestión de Clientes</h1>
                <p class="text-muted mb-0">Administra la cartera de clientes de tu empresa</p>
            </div>
            <?php if ($puede_crear): ?>
                <button class="btn btn-gradient" data-bs-toggle="modal" data-bs-target="#modalCrearCliente">
                    <i class="fas fa-plus"></i> Nuevo Cliente
                </button>
            <?php endif; ?>
        </div>

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-icon blue">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stats-number"><?php echo number_format($stats['total']); ?></div>
                    <div class="stats-label">Total Clientes</div>
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
                    <div class="stats-number"><?php echo number_format($stats['clase_a']); ?></div>
                    <div class="stats-label">Clase A</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-icon red">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stats-number">$<?php echo number_format($stats['credito_total'], 0, ',', '.'); ?></div>
                    <div class="stats-label">Crédito Total</div>
                </div>
            </div>
        </div>

        <!-- Content Card -->
        <div class="content-card">
            <div class="content-card-header">
                <h2 class="content-card-title">Listado de Clientes</h2>
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
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Clasificación</label>
                        <select name="clasificacion" class="form-select" onchange="this.form.submit()">
                            <option value="todos" <?php echo $filtro_clasificacion === 'todos' ? 'selected' : ''; ?>>Todas</option>
                            <option value="A" <?php echo $filtro_clasificacion === 'A' ? 'selected' : ''; ?>>Clase A</option>
                            <option value="B" <?php echo $filtro_clasificacion === 'B' ? 'selected' : ''; ?>>Clase B</option>
                            <option value="C" <?php echo $filtro_clasificacion === 'C' ? 'selected' : ''; ?>>Clase C</option>
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
                <table class="table table-hover" id="tablaClientes">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>RUT</th>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Teléfono</th>
                            <th>Ciudad</th>
                            <th>Clasificación</th>
                            <th>Límite Crédito</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clientes as $cliente): ?>
                        <tr>
                            <td><strong><code><?php echo htmlspecialchars($cliente['codigo']); ?></code></strong></td>
                            <td><?php echo $cliente['rut'] ? formatearRUT($cliente['rut']) : '-'; ?></td>
                            <td><?php echo htmlspecialchars($cliente['nombre']); ?></td>
                            <td><?php echo htmlspecialchars($cliente['email']); ?></td>
                            <td><?php echo htmlspecialchars($cliente['telefono']); ?></td>
                            <td><?php echo htmlspecialchars($cliente['ciudad']); ?></td>
                            <td>
                                <span class="badge badge-clase-<?php echo strtolower($cliente['clasificacion']); ?>">
                                    Clase <?php echo $cliente['clasificacion']; ?>
                                </span>
                            </td>
                            <td>$<?php echo number_format($cliente['limite_credito'], 0, ',', '.'); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $cliente['estado']; ?>">
                                    <?php echo ucfirst($cliente['estado']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <?php if ($puede_editar): ?>
                                        <button class="btn btn-sm btn-primary" onclick="alert('Editar cliente <?php echo $cliente['id']; ?>')">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($puede_eliminar): ?>
                                        <?php if ($cliente['estado'] === 'activo'): ?>
                                            <a href="?eliminar=<?php echo $cliente['id']; ?>" class="btn btn-sm btn-danger"
                                               onclick="return confirm('¿Desactivar este cliente?')">
                                                <i class="fas fa-ban"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="?activar=<?php echo $cliente['id']; ?>" class="btn btn-sm btn-success">
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

    <!-- Modal Crear Cliente -->
    <div class="modal fade" id="modalCrearCliente" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-users"></i> Nuevo Cliente</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Código Cliente *</label>
                                <input type="text" name="codigo" class="form-control" placeholder="CLIE-001" required>
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
                                <label class="form-label">Clasificación</label>
                                <select name="clasificacion" class="form-select">
                                    <option value="A">Clase A - Premium</option>
                                    <option value="B" selected>Clase B - Estándar</option>
                                    <option value="C">Clase C - Básico</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Límite de Crédito</label>
                                <input type="number" name="limite_credito" class="form-control" value="0" step="0.01">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Días de Crédito</label>
                                <input type="number" name="dias_credito" class="form-control" value="30">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="crear_cliente" class="btn btn-gradient">
                            <i class="fas fa-save"></i> Crear Cliente
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
        $('#tablaClientes').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
            },
            order: [[0, 'asc']],
            pageLength: 25
        });
    </script>
</body>
</html>
