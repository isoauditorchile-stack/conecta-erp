<?php
require_once __DIR__ . '/../includes/config.php';

// Verificar autenticación y permisos de admin
if (!isAuthenticated() || !isAdmin()) {
    redirect('/index.php');
}

$user = getCurrentUser();
$db = Database::getInstance();

// Obtener estadísticas del sistema
$stats = [
    'total_users' => $db->fetchOne("SELECT COUNT(*) as count FROM users")['count'],
    'active_users' => $db->fetchOne("SELECT COUNT(*) as count FROM users WHERE status = 'active'")['count'],
    'trial_users' => $db->fetchOne("SELECT COUNT(*) as count FROM users WHERE status = 'trial'")['count'],
    'pending_approval' => $db->fetchOne("SELECT COUNT(*) as count FROM users WHERE requires_approval = 1 AND approved_by_admin = 0")['count']
];

// Usuarios recientes
$recent_users = $db->fetchAll("
    SELECT id, firstname, lastname, email, company_name, status, created_at
    FROM users
    ORDER BY created_at DESC
    LIMIT 10
");

// Obtener todos los módulos
$modules = $db->fetchAll("SELECT * FROM modules ORDER BY sort_order");

// Actividad reciente (si la tabla existe)
$recent_activity = [];
try {
    $recent_activity = $db->fetchAll("
        SELECT al.*, u.firstname, u.lastname
        FROM activity_logs al
        LEFT JOIN users u ON al.user_id = u.id
        ORDER BY al.created_at DESC
        LIMIT 15
    ");
} catch (Exception $e) {
    // Tabla de logs no existe aún
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - CONECTA ERP</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/global.css">
    <style>
        body {
            display: flex;
            min-height: 100vh;
        }

        /* SIDEBAR */
        .sidebar {
            width: 280px;
            background: var(--dark-light);
            border-right: 1px solid rgba(255, 255, 255, 0.08);
            padding: 2rem 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
        }

        .logo {
            padding: 0 1.5rem 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            font-size: 1.5rem;
            font-weight: 900;
            color: var(--text);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            margin-bottom: 2rem;
        }

        .logo i {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            padding: 0.6rem;
            border-radius: 12px;
            color: white;
            box-shadow: 0 5px 15px rgba(99, 102, 241, 0.4);
        }

        .sidebar-menu {
            list-style: none;
        }

        .menu-item {
            margin: 0.25rem 0.75rem;
        }

        .menu-link {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.875rem 1rem;
            color: var(--text-muted);
            text-decoration: none;
            border-radius: 12px;
            transition: var(--transition);
            font-weight: 500;
        }

        .menu-link:hover, .menu-link.active {
            background: rgba(99, 102, 241, 0.1);
            color: var(--primary);
        }

        .menu-link i {
            width: 24px;
            text-align: center;
            font-size: 1.2rem;
        }

        .menu-badge {
            margin-left: auto;
            padding: 0.2rem 0.6rem;
            background: var(--error);
            color: white;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
        }

        /* MAIN CONTENT */
        .main-content {
            flex: 1;
            margin-left: 280px;
            background: var(--dark);
        }

        /* TOPBAR */
        .topbar {
            background: rgba(30, 41, 59, 0.8);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            padding: 1.25rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .topbar-left h1 {
            font-size: 1.75rem;
            margin: 0;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.5rem 1rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50px;
            cursor: pointer;
            transition: var(--transition);
        }

        .user-menu:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: white;
        }

        /* CONTENT */
        .content {
            padding: 2rem;
        }

        /* STATS GRID */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            transition: var(--transition);
        }

        .stat-card:hover {
            background: rgba(255, 255, 255, 0.06);
            transform: translateY(-2px);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 900;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--text-muted);
            font-size: 0.95rem;
        }

        /* MODULES GRID */
        .modules-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .module-card {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            color: var(--text);
        }

        .module-card:hover {
            background: rgba(255, 255, 255, 0.08);
            transform: translateY(-5px);
            border-color: rgba(99, 102, 241, 0.3);
        }

        .module-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: white;
            margin-bottom: 1rem;
        }

        .module-title {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .module-description {
            color: var(--text-muted);
            font-size: 0.9rem;
            line-height: 1.5;
        }

        /* TABLA */
        .users-table {
            margin-top: 2rem;
        }

        .action-btn {
            padding: 0.4rem 0.8rem;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="logo">
            <i class="fas fa-cube"></i>
            <span>CONECTA ERP</span>
        </div>
        <ul class="sidebar-menu">
            <li class="menu-item">
                <a href="dashboard_admin.php" class="menu-link active">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="users.php" class="menu-link">
                    <i class="fas fa-users"></i>
                    <span>Usuarios</span>
                    <?php if ($stats['pending_approval'] > 0): ?>
                        <span class="menu-badge"><?php echo $stats['pending_approval']; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="menu-item">
                <a href="modules.php" class="menu-link">
                    <i class="fas fa-th-large"></i>
                    <span>Módulos</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="analytics.php" class="menu-link">
                    <i class="fas fa-chart-line"></i>
                    <span>Analytics</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="settings.php" class="menu-link">
                    <i class="fas fa-cog"></i>
                    <span>Configuración</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="../logout.php" class="menu-link">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Cerrar Sesión</span>
                </a>
            </li>
        </ul>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <!-- TOPBAR -->
        <div class="topbar">
            <div class="topbar-left">
                <h1><i class="fas fa-chart-pie" style="color: var(--primary);"></i> Panel de Administración</h1>
            </div>
            <div class="topbar-right">
                <div class="user-menu">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($user['firstname'], 0, 1) . substr($user['lastname'], 0, 1)); ?>
                    </div>
                    <div>
                        <div style="font-weight: 600;"><?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?></div>
                        <div style="font-size: 0.85rem; color: var(--text-muted);">Administrador</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- CONTENT -->
        <div class="content">
            <!-- ESTADÍSTICAS -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #3b82f6, #8b5cf6);">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    <div class="stat-number"><?php echo $stats['total_users']; ?></div>
                    <div class="stat-label">Total Usuarios</div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #10b981, #059669);">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                    <div class="stat-number"><?php echo $stats['active_users']; ?></div>
                    <div class="stat-label">Usuarios Activos</div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                    <div class="stat-number"><?php echo $stats['trial_users']; ?></div>
                    <div class="stat-label">En Período de Prueba</div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #ef4444, #dc2626);">
                            <i class="fas fa-user-clock"></i>
                        </div>
                    </div>
                    <div class="stat-number"><?php echo $stats['pending_approval']; ?></div>
                    <div class="stat-label">Pendientes Aprobación</div>
                </div>
            </div>

            <!-- MÓDULOS -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Módulos del Sistema</h2>
                </div>
                <div class="modules-grid">
                    <?php foreach ($modules as $module): ?>
                        <a href="../modules/<?php echo strtolower($module['code']); ?>/index.php" class="module-card">
                            <div class="module-icon" style="background: <?php echo $module['color']; ?>;">
                                <i class="<?php echo $module['icon']; ?>"></i>
                            </div>
                            <div class="module-title"><?php echo htmlspecialchars($module['name']); ?></div>
                            <div class="module-description"><?php echo htmlspecialchars($module['description']); ?></div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- USUARIOS RECIENTES -->
            <div class="card users-table">
                <div class="card-header">
                    <h2 class="card-title">Usuarios Recientes</h2>
                    <a href="users.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-eye"></i> Ver Todos
                    </a>
                </div>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Email</th>
                                <th>Empresa</th>
                                <th>Estado</th>
                                <th>Fecha Registro</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_users as $u): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($u['firstname'] . ' ' . $u['lastname']); ?></td>
                                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                                    <td><?php echo htmlspecialchars($u['company_name']); ?></td>
                                    <td>
                                        <?php
                                        $badge_class = [
                                            'active' => 'badge-success',
                                            'trial' => 'badge-warning',
                                            'suspended' => 'badge-error',
                                            'cancelled' => 'badge-error'
                                        ];
                                        ?>
                                        <span class="badge <?php echo $badge_class[$u['status']] ?? 'badge-info'; ?>">
                                            <?php echo ucfirst($u['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo formatDate($u['created_at'], 'd/m/Y H:i'); ?></td>
                                    <td>
                                        <a href="users.php?edit=<?php echo $u['id']; ?>" class="btn btn-secondary action-btn">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
