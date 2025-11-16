<?php
/**
 * AUDITORÍA - Logs del sistema
 */
session_start();
require_once '../includes/config.php';

// Verificar sesión y permisos de super admin
requireLogin();
requireSuperAdmin();

// Obtener filtros
$filtro_accion = isset($_GET['accion']) ? $_GET['accion'] : 'todos';
$filtro_tabla = isset($_GET['tabla']) ? $_GET['tabla'] : 'todos';
$filtro_usuario = isset($_GET['usuario']) ? $_GET['usuario'] : 'todos';
$fecha_desde = isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : date('Y-m-d', strtotime('-7 days'));
$fecha_hasta = isset($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : date('Y-m-d');
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;

// Construir query de auditoría
$query = "SELECT la.*, u.nombre, u.apellido, u.email
          FROM logs_auditoria la
          LEFT JOIN usuarios u ON la.usuario_id = u.id
          WHERE DATE(la.fecha_accion) BETWEEN ? AND ?";

$params = [$fecha_desde, $fecha_hasta];
$types = "ss";

if ($filtro_accion !== 'todos') {
    $query .= " AND la.accion = ?";
    $params[] = $filtro_accion;
    $types .= "s";
}

if ($filtro_tabla !== 'todos') {
    $query .= " AND la.tabla = ?";
    $params[] = $filtro_tabla;
    $types .= "s";
}

if ($filtro_usuario !== 'todos') {
    $query .= " AND la.usuario_id = ?";
    $params[] = (int)$filtro_usuario;
    $types .= "i";
}

$query .= " ORDER BY la.fecha_accion DESC LIMIT ?";
$params[] = $limit;
$types .= "i";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$logs = [];
while ($row = $result->fetch_assoc()) {
    $logs[] = $row;
}
$stmt->close();

// Obtener acciones únicas
$acciones = [];
$result = $conn->query("SELECT DISTINCT accion FROM logs_auditoria ORDER BY accion");
while ($row = $result->fetch_assoc()) {
    $acciones[] = $row['accion'];
}

// Obtener tablas únicas
$tablas = [];
$result = $conn->query("SELECT DISTINCT tabla FROM logs_auditoria WHERE tabla IS NOT NULL ORDER BY tabla");
while ($row = $result->fetch_assoc()) {
    $tablas[] = $row['tabla'];
}

// Obtener usuarios que han realizado acciones
$usuarios_log = [];
$result = $conn->query("SELECT DISTINCT u.id, u.nombre, u.apellido, u.email
                        FROM logs_auditoria la
                        INNER JOIN usuarios u ON la.usuario_id = u.id
                        ORDER BY u.nombre");
while ($row = $result->fetch_assoc()) {
    $usuarios_log[] = $row;
}

// Estadísticas
$total_logs = $conn->query("SELECT COUNT(*) as total FROM logs_auditoria")->fetch_assoc()['total'];
$logs_hoy = $conn->query("SELECT COUNT(*) as total FROM logs_auditoria WHERE DATE(fecha_accion) = CURDATE()")->fetch_assoc()['total'];
$logs_mes = $conn->query("SELECT COUNT(*) as total FROM logs_auditoria WHERE MONTH(fecha_accion) = MONTH(NOW()) AND YEAR(fecha_accion) = YEAR(NOW())")->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auditoría del Sistema - CONECTA ERP</title>
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
        .stat-card.hoy { border-left: 4px solid #48bb78; }
        .stat-card.mes { border-left: 4px solid #ed8936; }

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

        .filters {
            background: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .table th {
            background: #f7fafc;
            font-weight: 600;
            color: #4a5568;
            font-size: 0.875rem;
        }

        .table td {
            font-size: 0.875rem;
        }

        .log-item {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            border-left: 4px solid #667eea;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .log-item.crear { border-left-color: #48bb78; }
        .log-item.actualizar { border-left-color: #3b82f6; }
        .log-item.eliminar { border-left-color: #ef4444; }
        .log-item.aprobar { border-left-color: #10b981; }
        .log-item.rechazar { border-left-color: #f59e0b; }

        .log-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .log-accion {
            font-weight: 600;
            color: #2d3748;
        }

        .log-fecha {
            color: #718096;
            font-size: 0.875rem;
        }

        .log-details {
            color: #4a5568;
            font-size: 0.875rem;
        }

        .log-user {
            color: #667eea;
            font-weight: 600;
        }

        .badge {
            padding: 4px 10px;
            font-size: 0.75rem;
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
                <li><a href="suscripciones.php"><i class="fas fa-credit-card"></i><span>Suscripciones</span></a></li>
                <li><a href="configuracion.php"><i class="fas fa-cog"></i><span>Configuración</span></a></li>
                <li><a href="reportes.php"><i class="fas fa-chart-bar"></i><span>Reportes</span></a></li>
                <li><a href="auditoria.php" class="active"><i class="fas fa-history"></i><span>Auditoría</span></a></li>
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
                    <h1><i class="fas fa-history"></i> Auditoría del Sistema</h1>
                    <p>Logs y rastreo de acciones del sistema</p>
                </div>

                <!-- Estadísticas -->
                <div class="stats-grid">
                    <div class="stat-card total">
                        <div style="color: #667eea;"><i class="fas fa-database"></i> Total de Logs</div>
                        <h3><?php echo number_format($total_logs); ?></h3>
                    </div>
                    <div class="stat-card hoy">
                        <div style="color: #48bb78;"><i class="fas fa-calendar-day"></i> Logs Hoy</div>
                        <h3><?php echo $logs_hoy; ?></h3>
                    </div>
                    <div class="stat-card mes">
                        <div style="color: #ed8936;"><i class="fas fa-calendar-alt"></i> Logs Este Mes</div>
                        <h3><?php echo number_format($logs_mes); ?></h3>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="filters">
                    <form method="GET" action="" class="row g-3">
                        <div class="col-md-2">
                            <label class="form-label">Fecha Desde</label>
                            <input type="date" class="form-control" name="fecha_desde" value="<?php echo $fecha_desde; ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Fecha Hasta</label>
                            <input type="date" class="form-control" name="fecha_hasta" value="<?php echo $fecha_hasta; ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Acción</label>
                            <select class="form-select" name="accion">
                                <option value="todos">Todas</option>
                                <?php foreach ($acciones as $accion): ?>
                                    <option value="<?php echo $accion; ?>" <?php echo $filtro_accion == $accion ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($accion); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Tabla</label>
                            <select class="form-select" name="tabla">
                                <option value="todos">Todas</option>
                                <?php foreach ($tablas as $tabla): ?>
                                    <option value="<?php echo $tabla; ?>" <?php echo $filtro_tabla == $tabla ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($tabla); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Registros</label>
                            <select class="form-select" name="limit">
                                <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50</option>
                                <option value="100" <?php echo $limit == 100 ? 'selected' : ''; ?>>100</option>
                                <option value="500" <?php echo $limit == 500 ? 'selected' : ''; ?>>500</option>
                                <option value="1000" <?php echo $limit == 1000 ? 'selected' : ''; ?>>1000</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search"></i> Filtrar
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Lista de Logs -->
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-list"></i> Logs de Auditoría (Mostrando <?php echo count($logs); ?> registros)
                    </div>
                    <div class="card-body">
                        <?php if (empty($logs)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No hay logs para mostrar con los filtros seleccionados</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <div class="log-item <?php echo htmlspecialchars($log['accion']); ?>">
                                    <div class="log-header">
                                        <div>
                                            <span class="log-accion">
                                                <i class="fas fa-<?php
                                                echo str_contains($log['accion'], 'crear') ? 'plus-circle' :
                                                    (str_contains($log['accion'], 'actualizar') || str_contains($log['accion'], 'editar') ? 'edit' :
                                                    (str_contains($log['accion'], 'eliminar') ? 'trash' :
                                                    (str_contains($log['accion'], 'aprobar') ? 'check-circle' :
                                                    (str_contains($log['accion'], 'rechazar') ? 'times-circle' : 'cog'))));
                                                ?>"></i>
                                                <?php echo htmlspecialchars($log['accion']); ?>
                                            </span>
                                            <?php if ($log['tabla']): ?>
                                                <span class="badge bg-info"><?php echo htmlspecialchars($log['tabla']); ?></span>
                                            <?php endif; ?>
                                            <?php if ($log['registro_id']): ?>
                                                <span class="badge bg-secondary">ID: <?php echo $log['registro_id']; ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="log-fecha">
                                            <i class="fas fa-clock"></i>
                                            <?php echo date('d/m/Y H:i:s', strtotime($log['fecha_accion'])); ?>
                                        </div>
                                    </div>
                                    <div class="log-details">
                                        <strong>Usuario:</strong>
                                        <span class="log-user">
                                            <?php
                                            if ($log['nombre']) {
                                                echo htmlspecialchars($log['nombre'] . ' ' . $log['apellido']) . ' (' . htmlspecialchars($log['email']) . ')';
                                            } else {
                                                echo 'Sistema';
                                            }
                                            ?>
                                        </span>
                                        <?php if ($log['ip_address']): ?>
                                            | <strong>IP:</strong> <?php echo htmlspecialchars($log['ip_address']); ?>
                                        <?php endif; ?>
                                        <?php if ($log['descripcion']): ?>
                                            <br><strong>Descripción:</strong> <?php echo htmlspecialchars($log['descripcion']); ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
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
