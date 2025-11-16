<?php
/**
 * GESTIÓN DE EMPRESAS - Super Administrador
 */
session_start();
require_once '../includes/config.php';

// Verificar sesión y permisos de super admin
requireLogin();
requireSuperAdmin();

$success_message = '';
$errors = [];

// Obtener filtros
$filtro_estado = isset($_GET['estado']) ? $_GET['estado'] : 'todos';
$buscar = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';

// Procesar acciones
if (isset($_GET['accion']) && isset($_GET['id'])) {
    $empresa_id = (int)$_GET['id'];
    $accion = $_GET['accion'];

    switch ($accion) {
        case 'activar':
            $stmt = $conn->prepare("UPDATE empresas SET estado = 'activo' WHERE id = ?");
            $stmt->bind_param("i", $empresa_id);
            $stmt->execute();
            $stmt->close();
            $success_message = "Empresa activada correctamente";
            logAuditoria('activar_empresa', 'empresas', $empresa_id, null, null, 'Super admin activó empresa');
            break;

        case 'suspender':
            $stmt = $conn->prepare("UPDATE empresas SET estado = 'suspendido' WHERE id = ?");
            $stmt->bind_param("i", $empresa_id);
            $stmt->execute();
            $stmt->close();
            $success_message = "Empresa suspendida correctamente";
            logAuditoria('suspender_empresa', 'empresas', $empresa_id, null, null, 'Super admin suspendió empresa');
            break;

        case 'desactivar':
            $stmt = $conn->prepare("UPDATE empresas SET estado = 'inactivo' WHERE id = ?");
            $stmt->bind_param("i", $empresa_id);
            $stmt->execute();
            $stmt->close();
            $success_message = "Empresa desactivada correctamente";
            logAuditoria('desactivar_empresa', 'empresas', $empresa_id, null, null, 'Super admin desactivó empresa');
            break;

        case 'eliminar':
            // Verificar si tiene usuarios asociados
            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM usuarios WHERE empresa_id = ?");
            $stmt->bind_param("i", $empresa_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $count = $result->fetch_assoc()['total'];
            $stmt->close();

            if ($count > 0) {
                $errors[] = "No se puede eliminar la empresa porque tiene $count usuarios asociados";
            } else {
                $stmt = $conn->prepare("DELETE FROM empresas WHERE id = ?");
                $stmt->bind_param("i", $empresa_id);
                $stmt->execute();
                $stmt->close();
                $success_message = "Empresa eliminada correctamente";
                logAuditoria('eliminar_empresa', 'empresas', $empresa_id, null, null, 'Super admin eliminó empresa');
            }
            break;
    }
}

// Construir query de empresas
$query = "SELECT e.*, p.nombre as pais_nombre, COUNT(DISTINCT u.id) as total_usuarios
          FROM empresas e
          LEFT JOIN paises p ON e.pais_id = p.id
          LEFT JOIN usuarios u ON e.id = u.empresa_id
          WHERE 1=1";

if ($filtro_estado !== 'todos') {
    $query .= " AND e.estado = '" . $conn->real_escape_string($filtro_estado) . "'";
}

if (!empty($buscar)) {
    $buscar_escaped = $conn->real_escape_string($buscar);
    $query .= " AND (e.nombre_empresa LIKE '%$buscar_escaped%' OR e.rut LIKE '%$buscar_escaped%' OR e.email LIKE '%$buscar_escaped%')";
}

$query .= " GROUP BY e.id ORDER BY e.fecha_registro DESC";

$result = $conn->query($query);
$empresas = [];
while ($row = $result->fetch_assoc()) {
    $empresas[] = $row;
}

// Obtener estadísticas
$stats = [];
$stats['total'] = $conn->query("SELECT COUNT(*) as total FROM empresas")->fetch_assoc()['total'];
$stats['activas'] = $conn->query("SELECT COUNT(*) as total FROM empresas WHERE estado = 'activo'")->fetch_assoc()['total'];
$stats['suspendidas'] = $conn->query("SELECT COUNT(*) as total FROM empresas WHERE estado = 'suspendido'")->fetch_assoc()['total'];
$stats['inactivas'] = $conn->query("SELECT COUNT(*) as total FROM empresas WHERE estado = 'inactivo'")->fetch_assoc()['total'];

$usuario_admin = $_SESSION;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Empresas - CONECTA ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --sidebar-width: 280px;
            --sidebar-collapsed-width: 70px;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
        }

        .wrapper {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: var(--sidebar-width);
            background: var(--primary-gradient);
            color: white;
            transition: all 0.3s ease;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
        }

        .sidebar.collapsed {
            width: var(--sidebar-collapsed-width);
        }

        .sidebar-header {
            padding: 25px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-header h3 {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0;
        }

        .sidebar.collapsed .sidebar-header h3 span {
            display: none;
        }

        .sidebar-menu {
            list-style: none;
            padding: 20px 0;
            margin: 0;
        }

        .sidebar-menu li {
            margin: 5px 0;
        }

        .sidebar-menu a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            padding: 12px 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            transition: all 0.3s ease;
        }

        .sidebar-menu a:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .sidebar-menu a.active {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border-left: 4px solid white;
        }

        .sidebar-menu a i {
            font-size: 1.2rem;
            width: 25px;
            text-align: center;
        }

        .sidebar.collapsed .sidebar-menu a span {
            display: none;
        }

        .main-content {
            margin-left: var(--sidebar-width);
            flex: 1;
            transition: margin-left 0.3s ease;
        }

        .sidebar.collapsed ~ .main-content {
            margin-left: var(--sidebar-collapsed-width);
        }

        .topbar {
            background: white;
            padding: 15px 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .toggle-sidebar {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #667eea;
            cursor: pointer;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
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
            font-weight: 600;
        }

        .content-area {
            padding: 30px;
        }

        .page-header {
            margin-bottom: 30px;
        }

        .page-header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 10px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .stat-card h3 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .stat-card.total { border-left: 4px solid #667eea; }
        .stat-card.activas { border-left: 4px solid #48bb78; }
        .stat-card.suspendidas { border-left: 4px solid #ed8936; }
        .stat-card.inactivas { border-left: 4px solid #cbd5e0; }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }

        .card-header {
            background: white;
            border-bottom: 1px solid #e2e8f0;
            padding: 20px;
            font-weight: 600;
            font-size: 1.1rem;
            color: #2d3748;
        }

        .table-responsive {
            border-radius: 10px;
        }

        .table {
            margin: 0;
        }

        .table th {
            background: #f7fafc;
            font-weight: 600;
            color: #4a5568;
            border: none;
        }

        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.875rem;
        }

        .badge.activo {
            background: #c6f6d5;
            color: #22543d;
        }

        .badge.inactivo {
            background: #e2e8f0;
            color: #2d3748;
        }

        .badge.suspendido {
            background: #fed7d7;
            color: #742a2a;
        }

        .btn-sm {
            padding: 5px 12px;
            font-size: 0.875rem;
        }

        .filters {
            background: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h3>
                    <i class="fas fa-user-shield"></i>
                    <span>ADMIN</span>
                </h3>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard_admin.php"><i class="fas fa-home"></i><span>Dashboard</span></a></li>
                <li><a href="usuarios.php"><i class="fas fa-users"></i><span>Usuarios</span></a></li>
                <li><a href="empresas.php" class="active"><i class="fas fa-building"></i><span>Empresas</span></a></li>
                <li><a href="pagos.php"><i class="fas fa-dollar-sign"></i><span>Pagos</span></a></li>
                <li><a href="suscripciones.php"><i class="fas fa-credit-card"></i><span>Suscripciones</span></a></li>
                <li><a href="configuracion.php"><i class="fas fa-cog"></i><span>Configuración</span></a></li>
                <li><a href="reportes.php"><i class="fas fa-chart-bar"></i><span>Reportes</span></a></li>
                <li><a href="auditoria.php"><i class="fas fa-history"></i><span>Auditoría</span></a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i><span>Cerrar Sesión</span></a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Topbar -->
            <div class="topbar">
                <button class="toggle-sidebar" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="user-info">
                    <div>
                        <strong>Super Administrador</strong>
                        <div style="font-size: 0.875rem; color: #718096;">Administrador</div>
                    </div>
                    <div class="user-avatar">SA</div>
                </div>
            </div>

            <!-- Content Area -->
            <div class="content-area">
                <div class="page-header">
                    <h1><i class="fas fa-building"></i> Gestión de Empresas</h1>
                    <p>Administra todas las empresas del sistema</p>
                </div>

                <!-- Mensajes -->
                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- Estadísticas -->
                <div class="stats-grid">
                    <div class="stat-card total">
                        <div style="color: #667eea;"><i class="fas fa-building"></i> Total Empresas</div>
                        <h3><?php echo $stats['total']; ?></h3>
                    </div>
                    <div class="stat-card activas">
                        <div style="color: #48bb78;"><i class="fas fa-check-circle"></i> Activas</div>
                        <h3><?php echo $stats['activas']; ?></h3>
                    </div>
                    <div class="stat-card suspendidas">
                        <div style="color: #ed8936;"><i class="fas fa-pause-circle"></i> Suspendidas</div>
                        <h3><?php echo $stats['suspendidas']; ?></h3>
                    </div>
                    <div class="stat-card inactivas">
                        <div style="color: #a0aec0;"><i class="fas fa-times-circle"></i> Inactivas</div>
                        <h3><?php echo $stats['inactivas']; ?></h3>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="filters">
                    <form method="GET" action="" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Buscar</label>
                            <input type="text" class="form-control" name="buscar" value="<?php echo htmlspecialchars($buscar); ?>"
                                   placeholder="Nombre, RUT o email">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Estado</label>
                            <select class="form-select" name="estado">
                                <option value="todos" <?php echo $filtro_estado === 'todos' ? 'selected' : ''; ?>>Todos</option>
                                <option value="activo" <?php echo $filtro_estado === 'activo' ? 'selected' : ''; ?>>Activos</option>
                                <option value="suspendido" <?php echo $filtro_estado === 'suspendido' ? 'selected' : ''; ?>>Suspendidos</option>
                                <option value="inactivo" <?php echo $filtro_estado === 'inactivo' ? 'selected' : ''; ?>>Inactivos</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search"></i> Filtrar
                            </button>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <a href="empresas.php" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-redo"></i> Limpiar
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Tabla de Empresas -->
                <div class="card">
                    <div class="card-header">
                        Lista de Empresas (<?php echo count($empresas); ?>)
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Empresa</th>
                                    <th>RUT</th>
                                    <th>País</th>
                                    <th>Usuarios</th>
                                    <th>Estado</th>
                                    <th>Fecha Registro</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($empresas)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center">No hay empresas registradas</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($empresas as $empresa): ?>
                                        <tr>
                                            <td><?php echo $empresa['id']; ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($empresa['nombre_empresa']); ?></strong>
                                                <?php if ($empresa['razon_social']): ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($empresa['razon_social']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($empresa['rut']); ?></td>
                                            <td><?php echo htmlspecialchars($empresa['pais_nombre'] ?? 'N/A'); ?></td>
                                            <td><span class="badge bg-info"><?php echo $empresa['total_usuarios']; ?></span></td>
                                            <td><span class="badge <?php echo $empresa['estado']; ?>"><?php echo ucfirst($empresa['estado']); ?></span></td>
                                            <td><?php echo date('d/m/Y', strtotime($empresa['fecha_registro'])); ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <?php if ($empresa['estado'] === 'activo'): ?>
                                                        <a href="?accion=suspender&id=<?php echo $empresa['id']; ?>" class="btn btn-warning"
                                                           onclick="return confirm('¿Suspender esta empresa?')">
                                                            <i class="fas fa-pause"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <a href="?accion=activar&id=<?php echo $empresa['id']; ?>" class="btn btn-success"
                                                           onclick="return confirm('¿Activar esta empresa?')">
                                                            <i class="fas fa-check"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    <a href="?accion=eliminar&id=<?php echo $empresa['id']; ?>" class="btn btn-danger"
                                                       onclick="return confirm('¿ELIMINAR esta empresa? Esta acción no se puede deshacer.')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('collapsed');
        }
    </script>
</body>
</html>
