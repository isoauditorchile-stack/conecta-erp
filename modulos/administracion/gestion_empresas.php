<?php
/**
 * GESTIÓN DE EMPRESAS - CONECTA ERP
 * Módulo de Administración Central
 * Nivel Empresarial - Estilo SAP/Softland
 */

session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/plan_restrictions.php';

// Verificar autenticación
requireLogin();

// Verificar permisos - Solo super admin y usuarios con permiso de gestión de empresas
$puede_gestionar = ($_SESSION['es_super_admin'] == 1) || verificarPermiso('empresas', 'gestionar');

$mensaje = '';
$tipo_mensaje = '';

// ==========================================
// PROCESAR ACCIONES CRUD
// ==========================================

// CREAR EMPRESA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_empresa'])) {
    if (!$puede_gestionar) {
        $mensaje = 'No tienes permisos para crear empresas';
        $tipo_mensaje = 'danger';
    } else {
        // Validar límite de empresas según plan
        $validacion = puedeCrearEmpresa($conn, $_SESSION['usuario_id'], $_SESSION['plan_id']);

        if (!$validacion['permitido']) {
            $mensaje = $validacion['mensaje'];
            $tipo_mensaje = 'warning';
        } else {
            $rut = limpiarRUT($_POST['rut']);
            $nombre = trim($_POST['nombre']);
            $razon_social = trim($_POST['razon_social']);
            $giro = trim($_POST['giro']);
            $direccion = trim($_POST['direccion']);
            $comuna = trim($_POST['comuna']);
            $ciudad = trim($_POST['ciudad']);
            $region = trim($_POST['region']);
            $pais_id = (int)$_POST['pais_id'];
            $telefono = trim($_POST['telefono']);
            $email = trim($_POST['email']);
            $sitio_web = trim($_POST['sitio_web']);
            $codigo_actividad = trim($_POST['codigo_actividad']);
            $tipo_contribuyente = $_POST['tipo_contribuyente'];

            // Validaciones
            if (empty($rut) || empty($nombre) || empty($razon_social)) {
                $mensaje = 'RUT, nombre y razón social son obligatorios';
                $tipo_mensaje = 'danger';
            } elseif (!validarRUT($rut)) {
                $mensaje = 'RUT inválido';
                $tipo_mensaje = 'danger';
            } else {
                // Verificar si el RUT ya existe
                $stmt = $conn->prepare("SELECT id FROM empresas WHERE rut = ? AND id != ?");
                $stmt->bind_param("si", $rut, $_SESSION['empresa_id']);
                $stmt->execute();
                if ($stmt->get_result()->num_rows > 0) {
                    $mensaje = 'Ya existe una empresa con este RUT';
                    $tipo_mensaje = 'danger';
                } else {
                    // Crear empresa
                    $stmt = $conn->prepare("INSERT INTO empresas (
                        rut, nombre, razon_social, giro, direccion, comuna, ciudad, region, pais_id,
                        telefono, email, sitio_web, codigo_actividad, tipo_contribuyente,
                        estado, fecha_creacion, creado_por
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'activo', NOW(), ?)");

                    $stmt->bind_param("ssssssssisssssi",
                        $rut, $nombre, $razon_social, $giro, $direccion, $comuna, $ciudad, $region, $pais_id,
                        $telefono, $email, $sitio_web, $codigo_actividad, $tipo_contribuyente,
                        $_SESSION['usuario_id']
                    );

                    if ($stmt->execute()) {
                        $nueva_empresa_id = $conn->insert_id;
                        $mensaje = "Empresa '$nombre' creada exitosamente";
                        $tipo_mensaje = 'success';

                        // Log de auditoría
                        logAuditoria('crear', 'empresas', $nueva_empresa_id, null, null,
                            "Empresa creada: $nombre (RUT: " . formatearRUT($rut) . ")");
                    } else {
                        $mensaje = 'Error al crear la empresa: ' . $stmt->error;
                        $tipo_mensaje = 'danger';
                    }
                }
                $stmt->close();
            }
        }
    }
}

// EDITAR EMPRESA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_empresa'])) {
    if (!$puede_gestionar) {
        $mensaje = 'No tienes permisos para editar empresas';
        $tipo_mensaje = 'danger';
    } else {
        $empresa_id = (int)$_POST['empresa_id'];
        $rut = limpiarRUT($_POST['rut']);
        $nombre = trim($_POST['nombre']);
        $razon_social = trim($_POST['razon_social']);
        $giro = trim($_POST['giro']);
        $direccion = trim($_POST['direccion']);
        $comuna = trim($_POST['comuna']);
        $ciudad = trim($_POST['ciudad']);
        $region = trim($_POST['region']);
        $pais_id = (int)$_POST['pais_id'];
        $telefono = trim($_POST['telefono']);
        $email = trim($_POST['email']);
        $sitio_web = trim($_POST['sitio_web']);
        $codigo_actividad = trim($_POST['codigo_actividad']);
        $tipo_contribuyente = $_POST['tipo_contribuyente'];
        $estado = $_POST['estado'];

        // Obtener datos anteriores para log
        $stmt = $conn->prepare("SELECT nombre, rut FROM empresas WHERE id = ?");
        $stmt->bind_param("i", $empresa_id);
        $stmt->execute();
        $empresa_anterior = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // Actualizar empresa
        $stmt = $conn->prepare("UPDATE empresas SET
            rut = ?, nombre = ?, razon_social = ?, giro = ?, direccion = ?,
            comuna = ?, ciudad = ?, region = ?, pais_id = ?, telefono = ?,
            email = ?, sitio_web = ?, codigo_actividad = ?, tipo_contribuyente = ?,
            estado = ?, modificado_por = ?, fecha_modificacion = NOW()
            WHERE id = ?");

        $stmt->bind_param("ssssssssisssssiii",
            $rut, $nombre, $razon_social, $giro, $direccion, $comuna, $ciudad, $region,
            $pais_id, $telefono, $email, $sitio_web, $codigo_actividad, $tipo_contribuyente,
            $estado, $_SESSION['usuario_id'], $empresa_id
        );

        if ($stmt->execute()) {
            $mensaje = "Empresa '$nombre' actualizada exitosamente";
            $tipo_mensaje = 'success';

            // Log de auditoría
            logAuditoria('editar', 'empresas', $empresa_id,
                json_encode($empresa_anterior),
                json_encode(['nombre' => $nombre, 'rut' => $rut]),
                "Empresa editada: $nombre");
        } else {
            $mensaje = 'Error al actualizar la empresa';
            $tipo_mensaje = 'danger';
        }
        $stmt->close();
    }
}

// ELIMINAR/DESACTIVAR EMPRESA
if (isset($_GET['eliminar']) && $puede_gestionar) {
    $empresa_id = (int)$_GET['eliminar'];

    // Obtener nombre de la empresa
    $stmt = $conn->prepare("SELECT nombre FROM empresas WHERE id = ?");
    $stmt->bind_param("i", $empresa_id);
    $stmt->execute();
    $empresa = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Desactivar en lugar de eliminar (soft delete)
    $stmt = $conn->prepare("UPDATE empresas SET estado = 'inactivo', modificado_por = ?, fecha_modificacion = NOW() WHERE id = ?");
    $stmt->bind_param("ii", $_SESSION['usuario_id'], $empresa_id);

    if ($stmt->execute()) {
        $mensaje = "Empresa '{$empresa['nombre']}' desactivada exitosamente";
        $tipo_mensaje = 'success';

        logAuditoria('eliminar', 'empresas', $empresa_id, null, null,
            "Empresa desactivada: {$empresa['nombre']}");
    } else {
        $mensaje = 'Error al desactivar la empresa';
        $tipo_mensaje = 'danger';
    }
    $stmt->close();
}

// ACTIVAR EMPRESA
if (isset($_GET['activar']) && $puede_gestionar) {
    $empresa_id = (int)$_GET['activar'];

    $stmt = $conn->prepare("SELECT nombre FROM empresas WHERE id = ?");
    $stmt->bind_param("i", $empresa_id);
    $stmt->execute();
    $empresa = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $stmt = $conn->prepare("UPDATE empresas SET estado = 'activo', modificado_por = ?, fecha_modificacion = NOW() WHERE id = ?");
    $stmt->bind_param("ii", $_SESSION['usuario_id'], $empresa_id);

    if ($stmt->execute()) {
        $mensaje = "Empresa '{$empresa['nombre']}' activada exitosamente";
        $tipo_mensaje = 'success';

        logAuditoria('activar', 'empresas', $empresa_id, null, null,
            "Empresa activada: {$empresa['nombre']}");
    }
    $stmt->close();
}

// ==========================================
// OBTENER LISTA DE EMPRESAS
// ==========================================

$filtro_estado = $_GET['estado'] ?? 'todos';
$busqueda = $_GET['buscar'] ?? '';

$query = "SELECT e.*, p.nombre as pais_nombre,
          u1.nombre as creador_nombre, u1.apellido as creador_apellido,
          u2.nombre as modificador_nombre, u2.apellido as modificador_apellido,
          (SELECT COUNT(*) FROM usuarios WHERE empresa_id = e.id) as total_usuarios
          FROM empresas e
          LEFT JOIN paises p ON e.pais_id = p.id
          LEFT JOIN usuarios u1 ON e.creado_por = u1.id
          LEFT JOIN usuarios u2 ON e.modificado_por = u2.id
          WHERE 1=1";

// Filtro de estado
if ($filtro_estado !== 'todos') {
    $query .= " AND e.estado = '" . $conn->real_escape_string($filtro_estado) . "'";
}

// Búsqueda
if (!empty($busqueda)) {
    $busqueda_escape = $conn->real_escape_string($busqueda);
    $query .= " AND (e.nombre LIKE '%{$busqueda_escape}%'
                 OR e.razon_social LIKE '%{$busqueda_escape}%'
                 OR e.rut LIKE '%{$busqueda_escape}%'
                 OR e.email LIKE '%{$busqueda_escape}%')";
}

$query .= " ORDER BY e.fecha_creacion DESC";

$result = $conn->query($query);
$empresas = $result->fetch_all(MYSQLI_ASSOC);

// Obtener países para el formulario
$paises = $conn->query("SELECT id, nombre, codigo FROM paises WHERE activo = 1 ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);

// Estadísticas
$stats_query = "SELECT
    COUNT(*) as total,
    SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END) as activas,
    SUM(CASE WHEN estado = 'inactivo' THEN 1 ELSE 0 END) as inactivas,
    SUM(CASE WHEN estado = 'suspendido' THEN 1 ELSE 0 END) as suspendidas
    FROM empresas";
$stats = $conn->query($stats_query)->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Empresas - CONECTA ERP</title>

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

        /* Badges */
        .badge-activo { background: #48bb78; }
        .badge-inactivo { background: #a0aec0; }
        .badge-suspendido { background: #f56565; }

        /* Table */
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

        /* Modal */
        .modal-header {
            background: var(--primary-gradient);
            color: white;
            border-radius: 10px 10px 0 0;
        }

        .modal-content {
            border-radius: 10px;
            border: none;
        }

        /* Form */
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

        /* Filters */
        .filters {
            background: #f5f7fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
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
            <small style="color: rgba(255,255,255,0.7);">Administración</small>
        </div>

        <ul class="sidebar-menu">
            <li><a href="../dashboard/index.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="gestion_empresas.php" class="active"><i class="fas fa-building"></i> Empresas</a></li>
            <li><a href="gestion_seguridad.php"><i class="fas fa-shield-alt"></i> Seguridad</a></li>
            <li><a href="parametrizacion_global.php"><i class="fas fa-cog"></i> Parametrización</a></li>
            <li><a href="../entidades/entidades_maestras.php"><i class="fas fa-database"></i> Entidades Maestras</a></li>
            <li><a href="../../user/dashboard.php"><i class="fas fa-arrow-left"></i> Volver</a></li>
        </ul>
    </div>

    <!-- Topbar -->
    <div class="topbar">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard/index.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="#">Administración</a></li>
                <li class="breadcrumb-item active">Gestión de Empresas</li>
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
                <h1 class="h3 mb-0">Gestión de Empresas</h1>
                <p class="text-muted mb-0">Administra todas las empresas del sistema</p>
            </div>
            <?php if ($puede_gestionar): ?>
                <button class="btn btn-gradient" data-bs-toggle="modal" data-bs-target="#modalCrearEmpresa">
                    <i class="fas fa-plus"></i> Nueva Empresa
                </button>
            <?php endif; ?>
        </div>

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-icon blue">
                        <i class="fas fa-building"></i>
                    </div>
                    <div class="stats-number"><?php echo number_format($stats['total']); ?></div>
                    <div class="stats-label">Total Empresas</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-icon green">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stats-number"><?php echo number_format($stats['activas']); ?></div>
                    <div class="stats-label">Activas</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-icon orange">
                        <i class="fas fa-pause-circle"></i>
                    </div>
                    <div class="stats-number"><?php echo number_format($stats['inactivas']); ?></div>
                    <div class="stats-label">Inactivas</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-icon red">
                        <i class="fas fa-ban"></i>
                    </div>
                    <div class="stats-number"><?php echo number_format($stats['suspendidas']); ?></div>
                    <div class="stats-label">Suspendidas</div>
                </div>
            </div>
        </div>

        <!-- Content Card -->
        <div class="content-card">
            <div class="content-card-header">
                <h2 class="content-card-title">Listado de Empresas</h2>
            </div>

            <!-- Filtros -->
            <div class="filters">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Estado</label>
                        <select name="estado" class="form-select" onchange="this.form.submit()">
                            <option value="todos" <?php echo $filtro_estado === 'todos' ? 'selected' : ''; ?>>Todos</option>
                            <option value="activo" <?php echo $filtro_estado === 'activo' ? 'selected' : ''; ?>>Activo</option>
                            <option value="inactivo" <?php echo $filtro_estado === 'inactivo' ? 'selected' : ''; ?>>Inactivo</option>
                            <option value="suspendido" <?php echo $filtro_estado === 'suspendido' ? 'selected' : ''; ?>>Suspendido</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Buscar</label>
                        <input type="text" name="buscar" class="form-control" placeholder="Nombre, RUT, email..." value="<?php echo htmlspecialchars($busqueda); ?>">
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
                <table class="table table-hover" id="tablaDatos">
                    <thead>
                        <tr>
                            <th>RUT</th>
                            <th>Nombre</th>
                            <th>Razón Social</th>
                            <th>Ciudad</th>
                            <th>Teléfono</th>
                            <th>Email</th>
                            <th>Usuarios</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($empresas as $empresa): ?>
                        <tr>
                            <td><strong><?php echo formatearRUT($empresa['rut']); ?></strong></td>
                            <td><?php echo htmlspecialchars($empresa['nombre']); ?></td>
                            <td><?php echo htmlspecialchars($empresa['razon_social']); ?></td>
                            <td><?php echo htmlspecialchars($empresa['ciudad']); ?></td>
                            <td><?php echo htmlspecialchars($empresa['telefono']); ?></td>
                            <td><?php echo htmlspecialchars($empresa['email']); ?></td>
                            <td><span class="badge bg-primary"><?php echo $empresa['total_usuarios']; ?></span></td>
                            <td>
                                <span class="badge badge-<?php echo $empresa['estado']; ?>">
                                    <?php echo ucfirst($empresa['estado']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <?php if ($puede_gestionar): ?>
                                        <button class="btn btn-sm btn-primary" onclick="editarEmpresa(<?php echo $empresa['id']; ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php if ($empresa['estado'] === 'activo'): ?>
                                            <a href="?eliminar=<?php echo $empresa['id']; ?>" class="btn btn-sm btn-danger"
                                               onclick="return confirm('¿Desactivar esta empresa?')">
                                                <i class="fas fa-ban"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="?activar=<?php echo $empresa['id']; ?>" class="btn btn-sm btn-success">
                                                <i class="fas fa-check"></i>
                                            </a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <button class="btn btn-sm btn-info" onclick="verDetalle(<?php echo $empresa['id']; ?>)">
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

    <!-- Modal Crear Empresa -->
    <div class="modal fade" id="modalCrearEmpresa" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-building"></i> Nueva Empresa</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">RUT *</label>
                                <input type="text" name="rut" class="form-control" data-rut required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nombre Comercial *</label>
                                <input type="text" name="nombre" class="form-control" required>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Razón Social *</label>
                                <input type="text" name="razon_social" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Giro</label>
                                <input type="text" name="giro" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Código Actividad</label>
                                <input type="text" name="codigo_actividad" class="form-control">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Dirección</label>
                                <input type="text" name="direccion" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Comuna</label>
                                <input type="text" name="comuna" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Ciudad</label>
                                <input type="text" name="ciudad" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Región</label>
                                <input type="text" name="region" class="form-control">
                            </div>
                            <div class="col-md-6">
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
                                <label class="form-label">Tipo Contribuyente</label>
                                <select name="tipo_contribuyente" class="form-select">
                                    <option value="primera_categoria">Primera Categoría</option>
                                    <option value="segunda_categoria">Segunda Categoría</option>
                                    <option value="regimen_simplificado">Régimen Simplificado</option>
                                    <option value="sin_fines_lucro">Sin Fines de Lucro</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Teléfono</label>
                                <input type="text" name="telefono" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Sitio Web</label>
                                <input type="url" name="sitio_web" class="form-control" placeholder="https://">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="crear_empresa" class="btn btn-gradient">
                            <i class="fas fa-save"></i> Crear Empresa
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
        // DataTable
        $('#tablaDatos').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
            },
            order: [[0, 'asc']],
            pageLength: 25
        });

        // Funciones
        function editarEmpresa(id) {
            // TODO: Implementar modal de edición con AJAX
            alert('Función en desarrollo: Editar empresa ' + id);
        }

        function verDetalle(id) {
            // TODO: Implementar modal de detalle
            alert('Función en desarrollo: Ver detalle ' + id);
        }
    </script>

    <!-- Footer Profesional -->
    <?php include '../includes/footer.php'; ?>

</body>
</html>
