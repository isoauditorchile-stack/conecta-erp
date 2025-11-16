<?php
/**
 * NOTIFICACIONES - Centro de notificaciones del usuario
 */
session_start();
require_once '../includes/config.php';

// Verificar sesión
requireLogin();

$usuario_id = $_SESSION['user_id'];
$success_message = '';

// Marcar notificación como leída
if (isset($_GET['marcar_leida']) && is_numeric($_GET['marcar_leida'])) {
    $notif_id = (int)$_GET['marcar_leida'];
    $stmt = $conn->prepare("UPDATE notificaciones SET leido = 1, fecha_lectura = NOW()
                            WHERE id = ? AND usuario_id = ?");
    $stmt->bind_param("ii", $notif_id, $usuario_id);
    $stmt->execute();
    $stmt->close();
    $success_message = "Notificación marcada como leída";
}

// Marcar todas como leídas
if (isset($_GET['marcar_todas'])) {
    $stmt = $conn->prepare("UPDATE notificaciones SET leido = 1, fecha_lectura = NOW()
                            WHERE usuario_id = ? AND leido = 0");
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $stmt->close();
    $success_message = "Todas las notificaciones marcadas como leídas";
}

// Eliminar notificación
if (isset($_GET['eliminar']) && is_numeric($_GET['eliminar'])) {
    $notif_id = (int)$_GET['eliminar'];
    $stmt = $conn->prepare("DELETE FROM notificaciones WHERE id = ? AND usuario_id = ?");
    $stmt->bind_param("ii", $notif_id, $usuario_id);
    $stmt->execute();
    $stmt->close();
    $success_message = "Notificación eliminada";
}

// Filtro de notificaciones
$filtro = isset($_GET['filtro']) ? $_GET['filtro'] : 'todas';

// Obtener notificaciones
$query = "SELECT * FROM notificaciones WHERE usuario_id = ?";
if ($filtro === 'no_leidas') {
    $query .= " AND leido = 0";
} elseif ($filtro === 'leidas') {
    $query .= " AND leido = 1";
}
$query .= " ORDER BY fecha_creacion DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();
$notificaciones = [];
while ($row = $result->fetch_assoc()) {
    $notificaciones[] = $row;
}
$stmt->close();

// Contar notificaciones no leídas
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM notificaciones WHERE usuario_id = ? AND leido = 0");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();
$no_leidas = $result->fetch_assoc()['total'];
$stmt->close();

// Obtener información del usuario
$stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();
$usuario = $result->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificaciones - CONECTA ERP</title>
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

        /* Sidebar */
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

        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            flex: 1;
            transition: margin-left 0.3s ease;
        }

        .sidebar.collapsed ~ .main-content {
            margin-left: var(--sidebar-collapsed-width);
        }

        /* Topbar */
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

        /* Content Area */
        .content-area {
            padding: 30px;
        }

        .page-header {
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #2d3748;
            margin: 0;
        }

        .page-header .badge {
            font-size: 1rem;
            padding: 8px 15px;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }

        .filter-tabs {
            background: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .filter-tabs .btn {
            margin-right: 10px;
            border-radius: 8px;
        }

        .notification-item {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            position: relative;
        }

        .notification-item:hover {
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .notification-item.unread {
            border-left: 4px solid #667eea;
            background: #f7faff;
        }

        .notification-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-right: 15px;
        }

        .notification-icon.info {
            background: #e6f7ff;
            color: #1890ff;
        }

        .notification-icon.advertencia {
            background: #fff7e6;
            color: #fa8c16;
        }

        .notification-icon.error {
            background: #fff1f0;
            color: #f5222d;
        }

        .notification-icon.exito {
            background: #f6ffed;
            color: #52c41a;
        }

        .notification-content {
            flex: 1;
        }

        .notification-title {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 5px;
            font-size: 1.1rem;
        }

        .notification-message {
            color: #718096;
            margin-bottom: 10px;
        }

        .notification-date {
            font-size: 0.875rem;
            color: #a0aec0;
        }

        .notification-actions {
            display: flex;
            gap: 10px;
        }

        .notification-actions .btn {
            font-size: 0.875rem;
            padding: 5px 15px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-state i {
            font-size: 4rem;
            color: #cbd5e0;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            color: #4a5568;
            margin-bottom: 10px;
        }

        .empty-state p {
            color: #718096;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h3>
                    <i class="fas fa-chart-line"></i>
                    <span>CONECTA ERP</span>
                </h3>
            </div>
            <ul class="sidebar-menu">
                <li>
                    <a href="dashboard_user.php">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="mi-empresa.php">
                        <i class="fas fa-building"></i>
                        <span>Mi Empresa</span>
                    </a>
                </li>
                <li>
                    <a href="perfil.php">
                        <i class="fas fa-user"></i>
                        <span>Mi Perfil</span>
                    </a>
                </li>
                <li>
                    <a href="suscripcion.php">
                        <i class="fas fa-credit-card"></i>
                        <span>Suscripción</span>
                    </a>
                </li>
                <li>
                    <a href="notificaciones.php" class="active">
                        <i class="fas fa-bell"></i>
                        <span>Notificaciones</span>
                        <?php if ($no_leidas > 0): ?>
                            <span class="badge bg-danger"><?php echo $no_leidas; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li>
                    <a href="configuracion.php">
                        <i class="fas fa-cog"></i>
                        <span>Configuración</span>
                    </a>
                </li>
                <li>
                    <a href="soporte.php">
                        <i class="fas fa-headset"></i>
                        <span>Soporte</span>
                    </a>
                </li>
                <li>
                    <a href="../logout.php">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Cerrar Sesión</span>
                    </a>
                </li>
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
                        <strong><?php echo htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']); ?></strong>
                        <div style="font-size: 0.875rem; color: #718096;">Usuario</div>
                    </div>
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($usuario['nombre'], 0, 1) . substr($usuario['apellido'], 0, 1)); ?>
                    </div>
                </div>
            </div>

            <!-- Content Area -->
            <div class="content-area">
                <div class="page-header">
                    <div>
                        <h1><i class="fas fa-bell"></i> Notificaciones</h1>
                    </div>
                    <div>
                        <?php if ($no_leidas > 0): ?>
                            <span class="badge bg-danger"><?php echo $no_leidas; ?> sin leer</span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Mensaje de éxito -->
                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Filtros y acciones -->
                <div class="filter-tabs">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <a href="?filtro=todas" class="btn btn-<?php echo $filtro === 'todas' ? 'primary' : 'outline-primary'; ?>">
                                <i class="fas fa-list"></i> Todas (<?php echo count($notificaciones); ?>)
                            </a>
                            <a href="?filtro=no_leidas" class="btn btn-<?php echo $filtro === 'no_leidas' ? 'primary' : 'outline-primary'; ?>">
                                <i class="fas fa-envelope"></i> No leídas (<?php echo $no_leidas; ?>)
                            </a>
                            <a href="?filtro=leidas" class="btn btn-<?php echo $filtro === 'leidas' ? 'primary' : 'outline-primary'; ?>">
                                <i class="fas fa-envelope-open"></i> Leídas
                            </a>
                        </div>
                        <?php if ($no_leidas > 0): ?>
                            <a href="?marcar_todas=1" class="btn btn-success" onclick="return confirm('¿Marcar todas como leídas?')">
                                <i class="fas fa-check-double"></i> Marcar todas como leídas
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Lista de notificaciones -->
                <?php if (empty($notificaciones)): ?>
                    <div class="empty-state">
                        <i class="fas fa-bell-slash"></i>
                        <h3>No hay notificaciones</h3>
                        <p>Cuando recibas notificaciones, aparecerán aquí</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($notificaciones as $notif): ?>
                        <div class="notification-item <?php echo $notif['leido'] == 0 ? 'unread' : ''; ?>">
                            <div class="d-flex">
                                <div class="notification-icon <?php echo htmlspecialchars($notif['tipo']); ?>">
                                    <?php
                                    $iconos = [
                                        'info' => 'fa-info-circle',
                                        'advertencia' => 'fa-exclamation-triangle',
                                        'error' => 'fa-times-circle',
                                        'exito' => 'fa-check-circle'
                                    ];
                                    $icono = $iconos[$notif['tipo']] ?? 'fa-bell';
                                    ?>
                                    <i class="fas <?php echo $icono; ?>"></i>
                                </div>
                                <div class="notification-content">
                                    <div class="notification-title">
                                        <?php echo htmlspecialchars($notif['titulo']); ?>
                                        <?php if ($notif['leido'] == 0): ?>
                                            <span class="badge bg-primary">Nuevo</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="notification-message">
                                        <?php echo htmlspecialchars($notif['mensaje']); ?>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="notification-date">
                                            <i class="fas fa-clock"></i>
                                            <?php
                                            $fecha = new DateTime($notif['fecha_creacion']);
                                            echo $fecha->format('d/m/Y H:i');
                                            ?>
                                        </div>
                                        <div class="notification-actions">
                                            <?php if ($notif['enlace']): ?>
                                                <a href="<?php echo htmlspecialchars($notif['enlace']); ?>" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-external-link-alt"></i> Ver
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($notif['leido'] == 0): ?>
                                                <a href="?marcar_leida=<?php echo $notif['id']; ?>" class="btn btn-sm btn-success">
                                                    <i class="fas fa-check"></i> Marcar leída
                                                </a>
                                            <?php endif; ?>
                                            <a href="?eliminar=<?php echo $notif['id']; ?>" class="btn btn-sm btn-danger"
                                               onclick="return confirm('¿Eliminar esta notificación?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
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
