<?php
session_start();
require_once '../includes/config.php';

// Verificar autenticación
requireLogin();

// Verificar periodo de prueba
$usuario_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Calcular días restantes si está en trial
$dias_restantes = 0;
$mostrar_alerta_trial = false;
if ($usuario['en_periodo_prueba'] == 1) {
    $dias_restantes = getDiasRestantesTrial($usuario['fecha_fin_trial']);
    $mostrar_alerta_trial = ($dias_restantes <= 3);
}

// Mensaje de bienvenida si viene del registro
$mostrar_bienvenida = isset($_GET['welcome']) && $_GET['welcome'] == 1;

// Obtener notificaciones no leídas
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM notificaciones WHERE usuario_id = ? AND leido = 0");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$notificaciones_count = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Obtener última actividad
$stmt = $conn->prepare("SELECT * FROM logs_acceso WHERE usuario_id = ? ORDER BY fecha_hora DESC LIMIT 5");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$actividad = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Usuario - CONECTA ERP</title>
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

        .notifications-btn {
            position: relative;
            background: none;
            border: none;
            font-size: 1.3rem;
            color: #333;
            cursor: pointer;
        }

        .notifications-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #dc3545;
            color: white;
            font-size: 0.7rem;
            padding: 2px 6px;
            border-radius: 10px;
            font-weight: 600;
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

        /* Welcome Alert */
        .welcome-alert {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.3);
        }

        .welcome-alert h2 {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .welcome-alert p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        /* Trial Alert */
        .trial-alert {
            background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
            color: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 5px 20px rgba(255, 193, 7, 0.3);
        }

        .trial-alert-content {
            flex: 1;
        }

        .trial-alert h3 {
            font-size: 1.3rem;
            margin-bottom: 5px;
        }

        .trial-alert p {
            margin: 0;
            opacity: 0.9;
        }

        .btn-upgrade {
            background: white;
            color: #ff9800;
            border: none;
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-upgrade:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        /* Quick Access Grid */
        .quick-access-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .quick-access-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            text-decoration: none;
            color: inherit;
        }

        .quick-access-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .quick-access-icon {
            width: 60px;
            height: 60px;
            margin: 0 auto 15px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
        }

        .quick-access-icon.blue {
            background: rgba(102, 126, 234, 0.1);
            color: #667eea;
        }

        .quick-access-icon.green {
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
        }

        .quick-access-icon.orange {
            background: rgba(255, 193, 7, 0.1);
            color: #ffc107;
        }

        .quick-access-icon.red {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }

        .quick-access-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .quick-access-desc {
            font-size: 0.85rem;
            color: #6c757d;
        }

        /* Data Card */
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
            <li><a href="dashboard_user.php" class="active"><i class="fas fa-home"></i> <span>Dashboard</span></a></li>
            <li><a href="perfil.php"><i class="fas fa-user"></i> <span>Mi Perfil</span></a></li>
            <li><a href="mi-empresa.php"><i class="fas fa-building"></i> <span>Mi Empresa</span></a></li>
            <li><a href="suscripcion.php"><i class="fas fa-credit-card"></i> <span>Suscripción</span></a></li>
            <li><a href="notificaciones.php"><i class="fas fa-bell"></i> <span>Notificaciones</span></a></li>
            <li><a href="configuracion.php"><i class="fas fa-cog"></i> <span>Configuración</span></a></li>
            <li><a href="soporte.php"><i class="fas fa-headset"></i> <span>Soporte</span></a></li>
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

            <button class="notifications-btn" onclick="window.location.href='notificaciones.php'">
                <i class="fas fa-bell"></i>
                <?php if ($notificaciones_count > 0): ?>
                    <span class="notifications-badge"><?php echo $notificaciones_count; ?></span>
                <?php endif; ?>
            </button>

            <div class="user-menu">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($_SESSION['nombre'], 0, 1) . substr($_SESSION['apellido'], 0, 1)); ?>
                </div>
                <div>
                    <div style="font-weight: 600; color: #333;"><?php echo htmlspecialchars($_SESSION['nombre'] . ' ' . $_SESSION['apellido']); ?></div>
                    <div style="font-size: 0.85rem; color: #6c757d;"><?php echo htmlspecialchars($_SESSION['nombre_empresa'] ?? 'Usuario'); ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Welcome Message -->
        <?php if ($mostrar_bienvenida): ?>
        <div class="welcome-alert">
            <h2><i class="fas fa-rocket"></i> ¡Bienvenido a CONECTA ERP!</h2>
            <p>Tu cuenta ha sido creada exitosamente. Tienes <strong>14 días de prueba gratis</strong> para explorar todas las funcionalidades del sistema.</p>
        </div>
        <?php endif; ?>

        <!-- Trial Alert -->
        <?php if ($mostrar_alerta_trial): ?>
        <div class="trial-alert">
            <div class="trial-alert-content">
                <h3><i class="fas fa-clock"></i> Tu periodo de prueba está por expirar</h3>
                <p>Te quedan <strong><?php echo $dias_restantes; ?> día<?php echo $dias_restantes != 1 ? 's' : ''; ?></strong> de prueba. Actualiza tu plan para continuar disfrutando de CONECTA ERP.</p>
            </div>
            <button class="btn-upgrade" onclick="window.location.href='suscripcion.php'">
                <i class="fas fa-arrow-up"></i> Actualizar Plan
            </button>
        </div>
        <?php endif; ?>

        <!-- Header -->
        <h1 style="margin-bottom: 30px; color: #333;">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </h1>

        <?php if ($usuario['en_periodo_prueba'] == 1): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            Estás en periodo de prueba. Te quedan <strong><?php echo $dias_restantes; ?> días</strong> para explorar todas las funcionalidades.
        </div>
        <?php endif; ?>

        <!-- Quick Access -->
        <div class="quick-access-grid">
            <a href="modulos.php" class="quick-access-card">
                <div class="quick-access-icon blue">
                    <i class="fas fa-th-large"></i>
                </div>
                <div class="quick-access-title">Módulos</div>
                <div class="quick-access-desc">Acceder a los módulos</div>
            </a>

            <a href="reportes.php" class="quick-access-card">
                <div class="quick-access-icon green">
                    <i class="fas fa-chart-bar"></i>
                </div>
                <div class="quick-access-title">Reportes</div>
                <div class="quick-access-desc">Ver reportes y estadísticas</div>
            </a>

            <a href="documentos.php" class="quick-access-card">
                <div class="quick-access-icon orange">
                    <i class="fas fa-file-alt"></i>
                </div>
                <div class="quick-access-title">Documentos</div>
                <div class="quick-access-desc">Gestionar documentos</div>
            </a>

            <a href="soporte.php" class="quick-access-card">
                <div class="quick-access-icon red">
                    <i class="fas fa-headset"></i>
                </div>
                <div class="quick-access-title">Soporte</div>
                <div class="quick-access-desc">Contactar soporte</div>
            </a>
        </div>

        <!-- Actividad Reciente -->
        <div class="data-card">
            <div class="data-card-header">
                <div class="data-card-title">
                    <i class="fas fa-history"></i> Tu Actividad Reciente
                </div>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Acción</th>
                            <th>Mensaje</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($actividad->num_rows > 0): ?>
                            <?php while ($act = $actividad->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-<?php
                                            echo $act['accion'] == 'login' ? 'success' :
                                                ($act['accion'] == 'logout' ? 'secondary' :
                                                ($act['accion'] == 'registro' ? 'primary' : 'info'));
                                        ?>">
                                            <?php echo htmlspecialchars($act['accion']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($act['mensaje']); ?></td>
                                    <td><?php echo formatDate($act['fecha_hora'], 'd/m/Y H:i:s'); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center">No hay actividad registrada</td>
                            </tr>
                        <?php endif; ?>
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
