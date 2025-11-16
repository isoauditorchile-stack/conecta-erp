<?php
/**
 * GESTIÓN DE SUSCRIPCIONES - Super Administrador
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
$filtro_plan = isset($_GET['plan']) ? $_GET['plan'] : 'todos';
$buscar = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';

// Procesar acciones
if (isset($_GET['accion']) && isset($_GET['id'])) {
    $suscripcion_id = (int)$_GET['id'];
    $accion = $_GET['accion'];

    switch ($accion) {
        case 'activar':
            $stmt = $conn->prepare("UPDATE suscripciones SET estado = 'activo', es_trial = 0 WHERE id = ?");
            $stmt->bind_param("i", $suscripcion_id);
            $stmt->execute();
            $stmt->close();

            // Activar usuario
            $stmt = $conn->prepare("UPDATE usuarios u
                                    INNER JOIN suscripciones s ON u.id = s.usuario_id
                                    SET u.suscripcion_activa = 1, u.en_periodo_prueba = 0
                                    WHERE s.id = ?");
            $stmt->bind_param("i", $suscripcion_id);
            $stmt->execute();
            $stmt->close();

            $success_message = "Suscripción activada correctamente";
            logAuditoria('activar_suscripcion', 'suscripciones', $suscripcion_id, null, null, 'Super admin activó suscripción');
            break;

        case 'suspender':
            $stmt = $conn->prepare("UPDATE suscripciones SET estado = 'suspendido' WHERE id = ?");
            $stmt->bind_param("i", $suscripcion_id);
            $stmt->execute();
            $stmt->close();

            // Suspender usuario
            $stmt = $conn->prepare("UPDATE usuarios u
                                    INNER JOIN suscripciones s ON u.id = s.usuario_id
                                    SET u.suscripcion_activa = 0
                                    WHERE s.id = ?");
            $stmt->bind_param("i", $suscripcion_id);
            $stmt->execute();
            $stmt->close();

            $success_message = "Suscripción suspendida correctamente";
            logAuditoria('suspender_suscripcion', 'suscripciones', $suscripcion_id, null, null, 'Super admin suspendió suscripción');
            break;

        case 'cancelar':
            $stmt = $conn->prepare("UPDATE suscripciones SET estado = 'cancelado', auto_renovar = 0 WHERE id = ?");
            $stmt->bind_param("i", $suscripcion_id);
            $stmt->execute();
            $stmt->close();
            $success_message = "Suscripción cancelada correctamente";
            logAuditoria('cancelar_suscripcion', 'suscripciones', $suscripcion_id, null, null, 'Super admin canceló suscripción');
            break;

        case 'renovar':
            $stmt = $conn->prepare("UPDATE suscripciones SET auto_renovar = 1 WHERE id = ?");
            $stmt->bind_param("i", $suscripcion_id);
            $stmt->execute();
            $stmt->close();
            $success_message = "Auto-renovación activada";
            break;
    }
}

// Construir query de suscripciones
$query = "SELECT s.*,
          u.nombre, u.apellido, u.email, u.username,
          e.nombre_empresa,
          p.nombre_plan, p.precio_mensual,
          DATEDIFF(s.fecha_fin, NOW()) as dias_restantes
          FROM suscripciones s
          INNER JOIN usuarios u ON s.usuario_id = u.id
          LEFT JOIN empresas e ON s.empresa_id = e.id
          INNER JOIN planes p ON s.plan_id = p.id
          WHERE 1=1";

if ($filtro_estado !== 'todos') {
    $query .= " AND s.estado = '" . $conn->real_escape_string($filtro_estado) . "'";
}

if ($filtro_plan !== 'todos') {
    $query .= " AND s.plan_id = " . (int)$filtro_plan;
}

if (!empty($buscar)) {
    $buscar_escaped = $conn->real_escape_string($buscar);
    $query .= " AND (u.nombre LIKE '%$buscar_escaped%' OR u.email LIKE '%$buscar_escaped%' OR e.nombre_empresa LIKE '%$buscar_escaped%')";
}

$query .= " ORDER BY s.fecha_creacion DESC";

$result = $conn->query($query);
$suscripciones = [];
while ($row = $result->fetch_assoc()) {
    $suscripciones[] = $row;
}

// Obtener estadísticas
$stats = [];
$stats['total'] = $conn->query("SELECT COUNT(*) as total FROM suscripciones")->fetch_assoc()['total'];
$stats['trial'] = $conn->query("SELECT COUNT(*) as total FROM suscripciones WHERE estado = 'trial'")->fetch_assoc()['total'];
$stats['activas'] = $conn->query("SELECT COUNT(*) as total FROM suscripciones WHERE estado = 'activo'")->fetch_assoc()['total'];
$stats['suspendidas'] = $conn->query("SELECT COUNT(*) as total FROM suscripciones WHERE estado = 'suspendido'")->fetch_assoc()['total'];
$stats['canceladas'] = $conn->query("SELECT COUNT(*) as total FROM suscripciones WHERE estado = 'cancelado'")->fetch_assoc()['total'];
$stats['proximas_expirar'] = $conn->query("SELECT COUNT(*) as total FROM suscripciones WHERE estado IN ('trial', 'activo') AND DATEDIFF(fecha_fin, NOW()) <= 7")->fetch_assoc()['total'];

// MRR (Monthly Recurring Revenue)
$mrr = $conn->query("SELECT SUM(monto_mensual) as mrr FROM suscripciones WHERE estado = 'activo'")->fetch_assoc()['mrr'] ?? 0;

// Obtener planes para filtro
$planes = $conn->query("SELECT * FROM planes ORDER BY nombre_plan");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Suscripciones - CONECTA ERP</title>
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
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
        .stat-card.trial { border-left: 4px solid #fbbf24; }
        .stat-card.activas { border-left: 4px solid #48bb78; }
        .stat-card.suspendidas { border-left: 4px solid #ed8936; }
        .stat-card.canceladas { border-left: 4px solid #cbd5e0; }
        .stat-card.mrr { border-left: 4px solid #8b5cf6; }

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

        .badge.trial { background: #fef3c7; color: #92400e; }
        .badge.activo { background: #c6f6d5; color: #22543d; }
        .badge.suspendido { background: #fed7d7; color: #742a2a; }
        .badge.cancelado { background: #e2e8f0; color: #2d3748; }
        .badge.expirado { background: #fee2e2; color: #991b1b; }

        .filters {
            background: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .btn-sm {
            padding: 5px 12px;
            font-size: 0.875rem;
        }

        .alert {
            border-radius: 10px;
            border: none;
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
                <li><a href="empresas.php"><i class="fas fa-building"></i><span>Empresas</span></a></li>
                <li><a href="pagos.php"><i class="fas fa-dollar-sign"></i><span>Pagos</span></a></li>
                <li><a href="suscripciones.php" class="active"><i class="fas fa-credit-card"></i><span>Suscripciones</span></a></li>
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
                    <h1><i class="fas fa-credit-card"></i> Gestión de Suscripciones</h1>
                    <p>Administra todas las suscripciones del sistema</p>
                </div>

                <!-- Mensajes -->
                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Estadísticas -->
                <div class="stats-grid">
                    <div class="stat-card total">
                        <div style="color: #667eea;"><i class="fas fa-credit-card"></i> Total Suscripciones</div>
                        <h3><?php echo $stats['total']; ?></h3>
                    </div>
                    <div class="stat-card trial">
                        <div style="color: #f59e0b;"><i class="fas fa-clock"></i> En Prueba</div>
                        <h3><?php echo $stats['trial']; ?></h3>
                    </div>
                    <div class="stat-card activas">
                        <div style="color: #48bb78;"><i class="fas fa-check-circle"></i> Activas</div>
                        <h3><?php echo $stats['activas']; ?></h3>
                    </div>
                    <div class="stat-card suspendidas">
                        <div style="color: #ed8936;"><i class="fas fa-pause-circle"></i> Suspendidas</div>
                        <h3><?php echo $stats['suspendidas']; ?></h3>
                    </div>
                    <div class="stat-card canceladas">
                        <div style="color: #a0aec0;"><i class="fas fa-times-circle"></i> Canceladas</div>
                        <h3><?php echo $stats['canceladas']; ?></h3>
                    </div>
                    <div class="stat-card mrr">
                        <div style="color: #8b5cf6;"><i class="fas fa-dollar-sign"></i> MRR</div>
                        <h3>$<?php echo number_format($mrr, 0, ',', '.'); ?></h3>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="filters">
                    <form method="GET" action="" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Buscar</label>
                            <input type="text" class="form-control" name="buscar" value="<?php echo htmlspecialchars($buscar); ?>"
                                   placeholder="Nombre, email, empresa">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Estado</label>
                            <select class="form-select" name="estado">
                                <option value="todos" <?php echo $filtro_estado === 'todos' ? 'selected' : ''; ?>>Todos</option>
                                <option value="trial" <?php echo $filtro_estado === 'trial' ? 'selected' : ''; ?>>En Prueba</option>
                                <option value="activo" <?php echo $filtro_estado === 'activo' ? 'selected' : ''; ?>>Activas</option>
                                <option value="suspendido" <?php echo $filtro_estado === 'suspendido' ? 'selected' : ''; ?>>Suspendidas</option>
                                <option value="cancelado" <?php echo $filtro_estado === 'cancelado' ? 'selected' : ''; ?>>Canceladas</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Plan</label>
                            <select class="form-select" name="plan">
                                <option value="todos">Todos</option>
                                <?php while ($plan = $planes->fetch_assoc()): ?>
                                    <option value="<?php echo $plan['id']; ?>" <?php echo $filtro_plan == $plan['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($plan['nombre_plan']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search"></i> Filtrar
                            </button>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <a href="suscripciones.php" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-redo"></i> Limpiar
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Tabla de Suscripciones -->
                <div class="card">
                    <div class="card-header">
                        Lista de Suscripciones (<?php echo count($suscripciones); ?>)
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Usuario</th>
                                    <th>Empresa</th>
                                    <th>Plan</th>
                                    <th>Estado</th>
                                    <th>Inicio</th>
                                    <th>Fin</th>
                                    <th>Días Rest.</th>
                                    <th>Monto</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($suscripciones)): ?>
                                    <tr>
                                        <td colspan="10" class="text-center">No hay suscripciones registradas</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($suscripciones as $sub): ?>
                                        <tr>
                                            <td><?php echo $sub['id']; ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($sub['nombre'] . ' ' . $sub['apellido']); ?></strong>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($sub['email']); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($sub['nombre_empresa'] ?? 'N/A'); ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($sub['nombre_plan']); ?></strong>
                                                <?php if ($sub['es_trial'] == 1): ?>
                                                    <br><span class="badge bg-warning">Trial</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><span class="badge <?php echo $sub['estado']; ?>"><?php echo ucfirst($sub['estado']); ?></span></td>
                                            <td><?php echo date('d/m/Y', strtotime($sub['fecha_inicio'])); ?></td>
                                            <td><?php echo $sub['fecha_fin'] ? date('d/m/Y', strtotime($sub['fecha_fin'])) : 'N/A'; ?></td>
                                            <td>
                                                <?php if ($sub['dias_restantes'] !== null): ?>
                                                    <?php if ($sub['dias_restantes'] < 0): ?>
                                                        <span class="text-danger">Expirada</span>
                                                    <?php elseif ($sub['dias_restantes'] <= 7): ?>
                                                        <span class="text-warning"><?php echo $sub['dias_restantes']; ?> días</span>
                                                    <?php else: ?>
                                                        <?php echo $sub['dias_restantes']; ?> días
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    N/A
                                                <?php endif; ?>
                                            </td>
                                            <td>$<?php echo number_format($sub['monto_mensual'], 0, ',', '.'); ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <?php if ($sub['estado'] === 'trial' || $sub['estado'] === 'suspendido'): ?>
                                                        <a href="?accion=activar&id=<?php echo $sub['id']; ?>" class="btn btn-success"
                                                           onclick="return confirm('¿Activar esta suscripción?')">
                                                            <i class="fas fa-check"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    <?php if ($sub['estado'] === 'activo'): ?>
                                                        <a href="?accion=suspender&id=<?php echo $sub['id']; ?>" class="btn btn-warning"
                                                           onclick="return confirm('¿Suspender esta suscripción?')">
                                                            <i class="fas fa-pause"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    <?php if ($sub['estado'] !== 'cancelado'): ?>
                                                        <a href="?accion=cancelar&id=<?php echo $sub['id']; ?>" class="btn btn-danger"
                                                           onclick="return confirm('¿Cancelar esta suscripción?')">
                                                            <i class="fas fa-times"></i>
                                                        </a>
                                                    <?php endif; ?>
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
