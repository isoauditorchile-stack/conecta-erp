<?php
session_start();
require_once '../includes/config.php';

// Verificar autenticación y rol de super admin
requireLogin();
requireSuperAdmin();

$message = '';
$message_type = '';

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['usuario_id'])) {
        $usuario_id = intval($_POST['usuario_id']);
        $action = $_POST['action'];

        switch ($action) {
            case 'aprobar':
                $stmt = $conn->prepare("UPDATE usuarios SET estado = 'activo' WHERE id = ?");
                $stmt->bind_param("i", $usuario_id);
                if ($stmt->execute()) {
                    $message = "Usuario aprobado exitosamente";
                    $message_type = "success";

                    // Log de auditoría
                    logAuditoria('aprobar_usuario', 'usuarios', $usuario_id, null, null, 'Usuario aprobado por super admin');
                }
                $stmt->close();
                break;

            case 'rechazar':
                $stmt = $conn->prepare("UPDATE usuarios SET estado = 'rechazado' WHERE id = ?");
                $stmt->bind_param("i", $usuario_id);
                if ($stmt->execute()) {
                    $message = "Usuario rechazado";
                    $message_type = "warning";

                    logAuditoria('rechazar_usuario', 'usuarios', $usuario_id, null, null, 'Usuario rechazado por super admin');
                }
                $stmt->close();
                break;

            case 'suspender':
                $stmt = $conn->prepare("UPDATE usuarios SET estado = 'suspendido', suscripcion_activa = 0 WHERE id = ?");
                $stmt->bind_param("i", $usuario_id);
                if ($stmt->execute()) {
                    $message = "Usuario suspendido";
                    $message_type = "warning";

                    logAuditoria('suspender_usuario', 'usuarios', $usuario_id, null, null, 'Usuario suspendido por super admin');
                }
                $stmt->close();
                break;

            case 'activar':
                $stmt = $conn->prepare("UPDATE usuarios SET estado = 'activo', suscripcion_activa = 1 WHERE id = ?");
                $stmt->bind_param("i", $usuario_id);
                if ($stmt->execute()) {
                    $message = "Usuario activado";
                    $message_type = "success";

                    logAuditoria('activar_usuario', 'usuarios', $usuario_id, null, null, 'Usuario activado por super admin');
                }
                $stmt->close();
                break;

            case 'eliminar':
                $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ? AND es_super_admin = 0");
                $stmt->bind_param("i", $usuario_id);
                if ($stmt->execute()) {
                    $message = "Usuario eliminado permanentemente";
                    $message_type = "danger";

                    logAuditoria('eliminar_usuario', 'usuarios', $usuario_id, null, null, 'Usuario eliminado por super admin');
                }
                $stmt->close();
                break;

            case 'cambiar_plan':
                if (isset($_POST['plan_id'])) {
                    $plan_id = intval($_POST['plan_id']);
                    $stmt = $conn->prepare("UPDATE usuarios SET plan_id = ? WHERE id = ?");
                    $stmt->bind_param("ii", $plan_id, $usuario_id);
                    if ($stmt->execute()) {
                        $message = "Plan actualizado exitosamente";
                        $message_type = "success";

                        logAuditoria('cambiar_plan', 'usuarios', $usuario_id, null, $plan_id, 'Plan cambiado por super admin');
                    }
                    $stmt->close();
                }
                break;
        }
    }
}

// Filtros
$filtro_estado = $_GET['estado'] ?? 'todos';
$filtro_trial = $_GET['trial'] ?? 'todos';
$buscar = $_GET['buscar'] ?? '';

// Construir query
$where_clauses = ["u.es_super_admin = 0"];

if ($filtro_estado !== 'todos') {
    $where_clauses[] = "u.estado = '$filtro_estado'";
}

if ($filtro_trial === 'si') {
    $where_clauses[] = "u.en_periodo_prueba = 1";
} elseif ($filtro_trial === 'no') {
    $where_clauses[] = "u.en_periodo_prueba = 0";
}

if (!empty($buscar)) {
    $buscar_safe = $conn->real_escape_string($buscar);
    $where_clauses[] = "(u.nombre LIKE '%$buscar_safe%' OR u.apellido LIKE '%$buscar_safe%' OR u.email LIKE '%$buscar_safe%' OR u.username LIKE '%$buscar_safe%' OR e.nombre_empresa LIKE '%$buscar_safe%')";
}

$where_sql = implode(' AND ', $where_clauses);

// Obtener usuarios
$query = "SELECT u.*,
          e.nombre_empresa, e.rut as empresa_rut,
          p.nombre_plan as plan_nombre, p.precio_mensual,
          DATEDIFF(u.fecha_fin_trial, NOW()) as dias_restantes_trial
          FROM usuarios u
          LEFT JOIN empresas e ON u.empresa_id = e.id
          LEFT JOIN planes p ON u.plan_id = p.id
          WHERE $where_sql
          ORDER BY u.fecha_registro DESC";

$usuarios = $conn->query($query);

// Obtener estadísticas
$stats = [];
$stats['total'] = $conn->query("SELECT COUNT(*) as total FROM usuarios WHERE es_super_admin = 0")->fetch_assoc()['total'];
$stats['activos'] = $conn->query("SELECT COUNT(*) as total FROM usuarios WHERE estado = 'activo' AND es_super_admin = 0")->fetch_assoc()['total'];
$stats['pendientes'] = $conn->query("SELECT COUNT(*) as total FROM usuarios WHERE estado = 'pendiente' AND es_super_admin = 0")->fetch_assoc()['total'];
$stats['trial'] = $conn->query("SELECT COUNT(*) as total FROM usuarios WHERE en_periodo_prueba = 1 AND es_super_admin = 0")->fetch_assoc()['total'];
$stats['suspendidos'] = $conn->query("SELECT COUNT(*) as total FROM usuarios WHERE estado = 'suspendido' AND es_super_admin = 0")->fetch_assoc()['total'];

// Obtener planes
$planes = $conn->query("SELECT * FROM planes ORDER BY precio_mensual");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - CONECTA ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --sidebar-width: 280px;
            --sidebar-collapsed-width: 70px;
            --topbar-height: 70px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            transition: all 0.3s ease;
            z-index: 1000;
            overflow-y: auto;
        }

        .sidebar.collapsed {
            width: var(--sidebar-collapsed-width);
        }

        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-header h2 {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0;
        }

        .sidebar.collapsed .sidebar-header h2 .text {
            display: none;
        }

        .sidebar-menu {
            list-style: none;
            padding: 20px 0;
        }

        .sidebar-menu li {
            margin: 5px 0;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .sidebar-menu a:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .sidebar-menu a.active {
            background: rgba(255, 255, 255, 0.2);
            border-left: 4px solid white;
        }

        .sidebar-menu a i {
            font-size: 1.2rem;
            min-width: 30px;
        }

        .sidebar-menu a span {
            margin-left: 10px;
        }

        .sidebar.collapsed .sidebar-menu a span {
            display: none;
        }

        /* Topbar */
        .topbar {
            position: fixed;
            left: var(--sidebar-width);
            top: 0;
            right: 0;
            height: var(--topbar-height);
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
            transition: all 0.3s ease;
            z-index: 999;
        }

        .sidebar.collapsed ~ .topbar {
            left: var(--sidebar-collapsed-width);
        }

        .toggle-sidebar {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #333;
            cursor: pointer;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            margin-top: var(--topbar-height);
            padding: 30px;
            transition: all 0.3s ease;
            min-height: calc(100vh - var(--topbar-height));
        }

        .sidebar.collapsed ~ .main-content {
            margin-left: var(--sidebar-collapsed-width);
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .stat-card-value {
            font-size: 2rem;
            font-weight: 700;
            color: #333;
        }

        .stat-card-label {
            color: #6c757d;
            font-size: 0.9rem;
        }

        /* Filters */
        .filters-card {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }

        /* Table */
        .table-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .table {
            margin-bottom: 0;
        }

        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .btn-action {
            padding: 5px 10px;
            font-size: 0.85rem;
            margin: 2px;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2><i class="fas fa-rocket"></i> <span class="text">CONECTA ERP</span></h2>
        </div>
        <ul class="sidebar-menu">
            <li><a href="dashboard_admin.php"><i class="fas fa-home"></i> <span>Dashboard</span></a></li>
            <li><a href="usuarios.php" class="active"><i class="fas fa-users"></i> <span>Usuarios</span></a></li>
            <li><a href="empresas.php"><i class="fas fa-building"></i> <span>Empresas</span></a></li>
            <li><a href="suscripciones.php"><i class="fas fa-credit-card"></i> <span>Suscripciones</span></a></li>
            <li><a href="pagos.php"><i class="fas fa-dollar-sign"></i> <span>Pagos</span></a></li>
            <li><a href="configuracion.php"><i class="fas fa-cog"></i> <span>Configuración</span></a></li>
            <li><a href="reportes.php"><i class="fas fa-chart-bar"></i> <span>Reportes</span></a></li>
            <li><a href="auditoria.php"><i class="fas fa-shield-alt"></i> <span>Auditoría</span></a></li>
            <li><a href="../user/dashboard_user.php" style="margin: 20px 10px; background: rgba(255,255,255,0.2); border-radius: 10px;"><i class="fas fa-eye"></i> <span>Ver como Usuario</span></a></li>
            <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Cerrar Sesión</span></a></li>
        </ul>
    </div>

    <!-- Topbar -->
    <div class="topbar">
        <button class="toggle-sidebar" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>

        <div class="user-menu">
            <div class="user-avatar">
                <?php echo strtoupper(substr($_SESSION['nombre'], 0, 1) . substr($_SESSION['apellido'], 0, 1)); ?>
            </div>
            <div>
                <div style="font-weight: 600; color: #333;"><?php echo htmlspecialchars($_SESSION['nombre'] . ' ' . $_SESSION['apellido']); ?></div>
                <div style="font-size: 0.85rem; color: #6c757d;">Super Administrador</div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <h1 style="margin-bottom: 30px; color: #333;">
            <i class="fas fa-users"></i> Gestión de Usuarios
        </h1>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Estadísticas -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-card-value"><?php echo number_format($stats['total']); ?></div>
                <div class="stat-card-label">Total Usuarios</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-value text-success"><?php echo number_format($stats['activos']); ?></div>
                <div class="stat-card-label">Activos</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-value text-warning"><?php echo number_format($stats['pendientes']); ?></div>
                <div class="stat-card-label">Pendientes</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-value text-info"><?php echo number_format($stats['trial']); ?></div>
                <div class="stat-card-label">En Trial</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-value text-danger"><?php echo number_format($stats['suspendidos']); ?></div>
                <div class="stat-card-label">Suspendidos</div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="filters-card">
            <form method="GET" action="">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Estado</label>
                        <select name="estado" class="form-select" onchange="this.form.submit()">
                            <option value="todos" <?php echo $filtro_estado === 'todos' ? 'selected' : ''; ?>>Todos</option>
                            <option value="activo" <?php echo $filtro_estado === 'activo' ? 'selected' : ''; ?>>Activos</option>
                            <option value="pendiente" <?php echo $filtro_estado === 'pendiente' ? 'selected' : ''; ?>>Pendientes</option>
                            <option value="suspendido" <?php echo $filtro_estado === 'suspendido' ? 'selected' : ''; ?>>Suspendidos</option>
                            <option value="rechazado" <?php echo $filtro_estado === 'rechazado' ? 'selected' : ''; ?>>Rechazados</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Trial</label>
                        <select name="trial" class="form-select" onchange="this.form.submit()">
                            <option value="todos" <?php echo $filtro_trial === 'todos' ? 'selected' : ''; ?>>Todos</option>
                            <option value="si" <?php echo $filtro_trial === 'si' ? 'selected' : ''; ?>>En Trial</option>
                            <option value="no" <?php echo $filtro_trial === 'no' ? 'selected' : ''; ?>>Sin Trial</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Buscar</label>
                        <div class="input-group">
                            <input type="text" name="buscar" class="form-control" placeholder="Nombre, email, empresa..." value="<?php echo htmlspecialchars($buscar); ?>">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Buscar</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Tabla de Usuarios -->
        <div class="table-card">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Usuario</th>
                            <th>Email</th>
                            <th>Empresa</th>
                            <th>Plan</th>
                            <th>Estado</th>
                            <th>Trial</th>
                            <th>Registro</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($usuarios && $usuarios->num_rows > 0): ?>
                            <?php while ($usuario = $usuarios->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $usuario['id']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']); ?></strong><br>
                                        <small class="text-muted">@<?php echo htmlspecialchars($usuario['username']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($usuario['nombre_empresa'] ?? 'N/A'); ?><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($usuario['empresa_rut'] ?? ''); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?php echo htmlspecialchars($usuario['plan_nombre'] ?? 'N/A'); ?></span>
                                    </td>
                                    <td>
                                        <?php
                                        $badge_class = match($usuario['estado']) {
                                            'activo' => 'success',
                                            'pendiente' => 'warning',
                                            'suspendido' => 'danger',
                                            'rechazado' => 'secondary',
                                            default => 'secondary'
                                        };
                                        ?>
                                        <span class="badge bg-<?php echo $badge_class; ?>"><?php echo ucfirst($usuario['estado']); ?></span>
                                    </td>
                                    <td>
                                        <?php if ($usuario['en_periodo_prueba'] == 1): ?>
                                            <span class="badge bg-warning">
                                                <?php echo $usuario['dias_restantes_trial']; ?> días
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">No</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo formatDate($usuario['fecha_registro'], 'd/m/Y'); ?></td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-primary dropdown-toggle btn-action" data-bs-toggle="dropdown">
                                                <i class="fas fa-cog"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <?php if ($usuario['estado'] === 'pendiente'): ?>
                                                    <li>
                                                        <form method="POST" style="display:inline;">
                                                            <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">
                                                            <input type="hidden" name="action" value="aprobar">
                                                            <button type="submit" class="dropdown-item text-success">
                                                                <i class="fas fa-check"></i> Aprobar
                                                            </button>
                                                        </form>
                                                    </li>
                                                    <li>
                                                        <form method="POST" style="display:inline;">
                                                            <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">
                                                            <input type="hidden" name="action" value="rechazar">
                                                            <button type="submit" class="dropdown-item text-warning">
                                                                <i class="fas fa-times"></i> Rechazar
                                                            </button>
                                                        </form>
                                                    </li>
                                                <?php endif; ?>

                                                <?php if ($usuario['estado'] === 'activo'): ?>
                                                    <li>
                                                        <form method="POST" style="display:inline;">
                                                            <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">
                                                            <input type="hidden" name="action" value="suspender">
                                                            <button type="submit" class="dropdown-item text-warning">
                                                                <i class="fas fa-pause"></i> Suspender
                                                            </button>
                                                        </form>
                                                    </li>
                                                <?php endif; ?>

                                                <?php if ($usuario['estado'] === 'suspendido'): ?>
                                                    <li>
                                                        <form method="POST" style="display:inline;">
                                                            <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">
                                                            <input type="hidden" name="action" value="activar">
                                                            <button type="submit" class="dropdown-item text-success">
                                                                <i class="fas fa-play"></i> Activar
                                                            </button>
                                                        </form>
                                                    </li>
                                                <?php endif; ?>

                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#cambiarPlanModal<?php echo $usuario['id']; ?>">
                                                        <i class="fas fa-exchange-alt"></i> Cambiar Plan
                                                    </a>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <form method="POST" style="display:inline;" onsubmit="return confirm('¿Seguro que deseas eliminar este usuario?');">
                                                        <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">
                                                        <input type="hidden" name="action" value="eliminar">
                                                        <button type="submit" class="dropdown-item text-danger">
                                                            <i class="fas fa-trash"></i> Eliminar
                                                        </button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>

                                        <!-- Modal Cambiar Plan -->
                                        <div class="modal fade" id="cambiarPlanModal<?php echo $usuario['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Cambiar Plan</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form method="POST">
                                                        <div class="modal-body">
                                                            <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">
                                                            <input type="hidden" name="action" value="cambiar_plan">
                                                            <label class="form-label">Seleccionar Plan</label>
                                                            <select name="plan_id" class="form-select" required>
                                                                <?php
                                                                $planes->data_seek(0);
                                                                while ($plan = $planes->fetch_assoc()):
                                                                ?>
                                                                    <option value="<?php echo $plan['id']; ?>" <?php echo $plan['id'] == $usuario['plan_id'] ? 'selected' : ''; ?>>
                                                                        <?php echo htmlspecialchars($plan['nombre']); ?> - <?php echo formatCurrency($plan['precio_mensual']); ?>
                                                                    </option>
                                                                <?php endwhile; ?>
                                                            </select>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                            <button type="submit" class="btn btn-primary">Cambiar Plan</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center">No hay usuarios registrados</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
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
