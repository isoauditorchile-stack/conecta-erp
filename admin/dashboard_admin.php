<?php
session_start();
require_once '../includes/config.php';

// Verificar autenticación y rol de super admin
requireLogin();
requireSuperAdmin();

// Obtener estadísticas del sistema
$stats = [];

// Total de usuarios
$result = $conn->query("SELECT COUNT(*) as total FROM usuarios");
$stats['total_usuarios'] = $result->fetch_assoc()['total'];

// Total de empresas
$result = $conn->query("SELECT COUNT(*) as total FROM empresas WHERE estado = 'activo'");
$stats['total_empresas'] = $result->fetch_assoc()['total'];

// Usuarios en periodo de prueba
$result = $conn->query("SELECT COUNT(*) as total FROM usuarios WHERE en_periodo_prueba = 1 AND estado = 'activo'");
$stats['usuarios_trial'] = $result->fetch_assoc()['total'];

// Usuarios con suscripción activa
$result = $conn->query("SELECT COUNT(*) as total FROM usuarios WHERE suscripcion_activa = 1 AND en_periodo_prueba = 0");
$stats['usuarios_activos'] = $result->fetch_assoc()['total'];

// Usuarios pendientes de aprobación
$result = $conn->query("SELECT COUNT(*) as total FROM usuarios WHERE estado = 'pendiente'");
$stats['usuarios_pendientes'] = $result->fetch_assoc()['total'];

// Ingresos totales (pagos aprobados)
$result = $conn->query("SELECT SUM(monto) as total FROM pagos WHERE estado = 'aprobado'");
$stats['ingresos_totales'] = $result->fetch_assoc()['total'] ?? 0;

// Usuarios por expirar trial (quedan 3 días o menos)
$fecha_limite = date('Y-m-d H:i:s', strtotime('+3 days'));
$result = $conn->query("SELECT COUNT(*) as total FROM usuarios WHERE en_periodo_prueba = 1 AND fecha_fin_trial <= '$fecha_limite' AND fecha_fin_trial >= NOW()");
$stats['usuarios_trial_expirando'] = $result->fetch_assoc()['total'];

// Obtener usuarios en trial próximos a expirar
$usuarios_trial_query = "SELECT u.id, u.nombre, u.apellido, u.email, u.fecha_inicio_trial, u.fecha_fin_trial, e.nombre_empresa
                         FROM usuarios u
                         LEFT JOIN empresas e ON u.empresa_id = e.id
                         WHERE u.en_periodo_prueba = 1
                         AND u.estado = 'activo'
                         AND u.fecha_fin_trial <= '$fecha_limite'
                         AND u.fecha_fin_trial >= NOW()
                         ORDER BY u.fecha_fin_trial ASC
                         LIMIT 10";
$usuarios_trial = $conn->query($usuarios_trial_query);

// Obtener usuarios pendientes de aprobación
$usuarios_pendientes_query = "SELECT u.id, u.nombre, u.apellido, u.email, u.fecha_registro, e.nombre_empresa
                               FROM usuarios u
                               LEFT JOIN empresas e ON u.empresa_id = e.id
                               WHERE u.estado = 'pendiente'
                               ORDER BY u.fecha_registro DESC
                               LIMIT 10";
$usuarios_pendientes = $conn->query($usuarios_pendientes_query);

// Obtener últimos pagos
$pagos_query = "SELECT p.id, p.monto, p.moneda, p.estado, p.fecha_pago, u.nombre, u.apellido, u.email
                FROM pagos p
                INNER JOIN usuarios u ON p.usuario_id = u.id
                ORDER BY p.fecha_pago DESC
                LIMIT 10";
$pagos = $conn->query($pagos_query);

// Obtener actividad reciente
$actividad_query = "SELECT la.accion, la.mensaje, la.fecha_hora, u.nombre, u.apellido, u.email
                    FROM logs_acceso la
                    LEFT JOIN usuarios u ON la.usuario_id = u.id
                    ORDER BY la.fecha_hora DESC
                    LIMIT 15";
$actividad = $conn->query($actividad_query);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Administrador - CONECTA ERP</title>
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

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .dark-mode-toggle {
            background: none;
            border: none;
            font-size: 1.3rem;
            color: #333;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .dark-mode-toggle:hover {
            color: var(--primary-color);
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

        /* Dashboard Cards */
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
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .stat-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
        }

        .stat-card-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .stat-card-icon.blue {
            background: rgba(102, 126, 234, 0.1);
            color: #667eea;
        }

        .stat-card-icon.green {
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
        }

        .stat-card-icon.orange {
            background: rgba(255, 193, 7, 0.1);
            color: #ffc107;
        }

        .stat-card-icon.red {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }

        .stat-card-value {
            font-size: 2rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }

        .stat-card-label {
            color: #6c757d;
            font-size: 0.95rem;
        }

        /* Data Table */
        .data-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }

        .data-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .data-card-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .badge-custom {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }

        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }

        .badge-success {
            background: #d4edda;
            color: #155724;
        }

        .table {
            margin-bottom: 0;
        }

        .btn-view-as-user {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-view-as-user:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .topbar, .main-content {
                margin-left: 0;
                left: 0;
            }
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
            <li><a href="dashboard_admin.php" class="active"><i class="fas fa-home"></i> <span>Dashboard</span></a></li>
            <li><a href="usuarios.php"><i class="fas fa-users"></i> <span>Usuarios</span></a></li>
            <li><a href="empresas.php"><i class="fas fa-building"></i> <span>Empresas</span></a></li>
            <li><a href="suscripciones.php"><i class="fas fa-credit-card"></i> <span>Suscripciones</span></a></li>
            <li><a href="pagos.php"><i class="fas fa-dollar-sign"></i> <span>Pagos</span></a></li>
            <li><a href="modulos.php"><i class="fas fa-th-large"></i> <span>Módulos</span></a></li>
            <li><a href="configuracion.php"><i class="fas fa-cog"></i> <span>Configuración</span></a></li>
            <li><a href="reportes.php"><i class="fas fa-chart-bar"></i> <span>Reportes</span></a></li>
            <li><a href="auditoria.php"><i class="fas fa-shield-alt"></i> <span>Auditoría</span></a></li>
            <li><a href="../user/dashboard_user.php" class="btn-view-as-user" style="margin: 20px 10px;"><i class="fas fa-eye"></i> <span>Ver como Usuario</span></a></li>
            <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Cerrar Sesión</span></a></li>
        </ul>
    </div>

    <!-- Topbar -->
    <div class="topbar">
        <button class="toggle-sidebar" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>

        <div class="topbar-right">
            <button class="dark-mode-toggle" onclick="toggleDarkMode()">
                <i class="fas fa-moon"></i>
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
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <h1 style="margin-bottom: 30px; color: #333;">
            <i class="fas fa-tachometer-alt"></i> Dashboard Administrador
        </h1>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-card-header">
                    <div>
                        <div class="stat-card-value"><?php echo number_format($stats['total_usuarios']); ?></div>
                        <div class="stat-card-label">Total Usuarios</div>
                    </div>
                    <div class="stat-card-icon blue">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-card-header">
                    <div>
                        <div class="stat-card-value"><?php echo number_format($stats['total_empresas']); ?></div>
                        <div class="stat-card-label">Empresas Activas</div>
                    </div>
                    <div class="stat-card-icon green">
                        <i class="fas fa-building"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-card-header">
                    <div>
                        <div class="stat-card-value"><?php echo number_format($stats['usuarios_trial']); ?></div>
                        <div class="stat-card-label">Usuarios en Trial</div>
                    </div>
                    <div class="stat-card-icon orange">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-card-header">
                    <div>
                        <div class="stat-card-value"><?php echo formatCurrency($stats['ingresos_totales']); ?></div>
                        <div class="stat-card-label">Ingresos Totales</div>
                    </div>
                    <div class="stat-card-icon green">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-card-header">
                    <div>
                        <div class="stat-card-value"><?php echo number_format($stats['usuarios_pendientes']); ?></div>
                        <div class="stat-card-label">Pendientes Aprobación</div>
                    </div>
                    <div class="stat-card-icon red">
                        <i class="fas fa-user-clock"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-card-header">
                    <div>
                        <div class="stat-card-value"><?php echo number_format($stats['usuarios_trial_expirando']); ?></div>
                        <div class="stat-card-label">Trials por Expirar</div>
                    </div>
                    <div class="stat-card-icon orange">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Usuarios Trial por Expirar -->
        <?php if ($usuarios_trial->num_rows > 0): ?>
        <div class="data-card">
            <div class="data-card-header">
                <div class="data-card-title">
                    <i class="fas fa-hourglass-half"></i> Usuarios en Trial Próximos a Expirar
                </div>
                <span class="badge-custom badge-warning"><?php echo $usuarios_trial->num_rows; ?> usuarios</span>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Email</th>
                            <th>Empresa</th>
                            <th>Días Restantes</th>
                            <th>Expira</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($user = $usuarios_trial->fetch_assoc()): ?>
                            <?php $dias_restantes = getDiasRestantesTrial($user['fecha_fin_trial']); ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['nombre'] . ' ' . $user['apellido']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['nombre_empresa'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $dias_restantes <= 1 ? 'danger' : 'warning'; ?>">
                                        <?php echo $dias_restantes; ?> día<?php echo $dias_restantes != 1 ? 's' : ''; ?>
                                    </span>
                                </td>
                                <td><?php echo formatDate($user['fecha_fin_trial'], 'd/m/Y H:i'); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-primary">Contactar</button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Usuarios Pendientes de Aprobación -->
        <?php if ($usuarios_pendientes->num_rows > 0): ?>
        <div class="data-card">
            <div class="data-card-header">
                <div class="data-card-title">
                    <i class="fas fa-user-check"></i> Usuarios Pendientes de Aprobación
                </div>
                <span class="badge-custom badge-danger"><?php echo $usuarios_pendientes->num_rows; ?> pendientes</span>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Email</th>
                            <th>Empresa</th>
                            <th>Fecha Registro</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($user = $usuarios_pendientes->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['nombre'] . ' ' . $user['apellido']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['nombre_empresa'] ?? 'N/A'); ?></td>
                                <td><?php echo formatDate($user['fecha_registro'], 'd/m/Y H:i'); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-success">Aprobar</button>
                                    <button class="btn btn-sm btn-danger">Rechazar</button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Actividad Reciente -->
        <div class="data-card">
            <div class="data-card-header">
                <div class="data-card-title">
                    <i class="fas fa-history"></i> Actividad Reciente
                </div>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Acción</th>
                            <th>Mensaje</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($act = $actividad->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($act['nombre'] ? $act['nombre'] . ' ' . $act['apellido'] : 'Sistema'); ?></td>
                                <td>
                                    <span class="badge bg-<?php
                                        echo $act['accion'] == 'login' ? 'success' :
                                            ($act['accion'] == 'logout' ? 'secondary' :
                                            ($act['accion'] == 'intento_fallido' ? 'danger' : 'info'));
                                    ?>">
                                        <?php echo htmlspecialchars($act['accion']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($act['mensaje']); ?></td>
                                <td><?php echo formatDate($act['fecha_hora'], 'd/m/Y H:i:s'); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('collapsed');
        }

        function toggleDarkMode() {
            // TODO: Implementar dark mode
            alert('Dark mode próximamente');
        }
    </script>
</body>
</html>
