<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Administrador - CONECTA ERP</title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>

<?php
require_once '../includes/config.php';

// Verificar autenticación y permisos de administrador
if (!isAuthenticated() || !isAdmin()) {
    redirect('../login_new.php');
}

$db = Database::getInstance();
$currentUser = getCurrentUser();

// Obtener estadísticas
$totalUsers = $db->fetchOne("SELECT COUNT(*) as count FROM users WHERE is_admin = 0")['count'];
$pendingUsers = $db->fetchOne("SELECT COUNT(*) as count FROM users WHERE is_approved = 0 AND is_admin = 0")['count'];
$activeUsers = $db->fetchOne("SELECT COUNT(*) as count FROM users WHERE status = 'active' AND is_admin = 0")['count'];
$trialUsers = $db->fetchOne("SELECT COUNT(*) as count FROM users WHERE status = 'trial' AND is_admin = 0")['count'];

$totalCompanies = $db->fetchOne("SELECT COUNT(*) as count FROM companies WHERE is_active = 1")['count'];
$totalPayments = $db->fetchOne("SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE payment_status = 'completed'")['total'];
$thisMonthPayments = $db->fetchOne("SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE payment_status = 'completed' AND MONTH(payment_date) = MONTH(CURRENT_DATE()) AND YEAR(payment_date) = YEAR(CURRENT_DATE())")['total'];

// Usuarios pendientes de aprobación
$pendingUsersList = $db->fetchAll("
    SELECT u.*, c.name as country_name, sp.plan_name
    FROM users u
    LEFT JOIN countries c ON u.country_id = c.id
    LEFT JOIN subscription_plans sp ON u.plan_id = sp.id
    WHERE u.is_approved = 0 AND u.is_admin = 0
    ORDER BY u.created_at DESC
    LIMIT 10
");

// Usuarios recientes
$recentUsers = $db->fetchAll("
    SELECT u.*, c.name as country_name, sp.plan_name
    FROM users u
    LEFT JOIN countries c ON u.country_id = c.id
    LEFT JOIN subscription_plans sp ON u.plan_id = sp.id
    WHERE u.is_admin = 0
    ORDER BY u.created_at DESC
    LIMIT 10
");

// Actividad reciente
$recentActivity = $db->fetchAll("
    SELECT al.*, u.full_name, u.email
    FROM activity_logs al
    LEFT JOIN users u ON al.user_id = u.id
    ORDER BY al.created_at DESC
    LIMIT 15
");

// Indicadores económicos
$indicators = $db->fetchAll("
    SELECT *
    FROM economic_indicators
    WHERE date = (SELECT MAX(date) FROM economic_indicators WHERE indicator_code = economic_indicators.indicator_code)
    ORDER BY indicator_code
");

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        $userId = intval($_POST['user_id'] ?? 0);

        if ($action === 'approve' && $userId > 0) {
            $db->update("UPDATE users SET is_approved = 1 WHERE id = ?", [$userId]);
            $user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);

            // Log
            logActivity($_SESSION['user_id'], 'approve_user', "Aprobó usuario: {$user['email']}", 'admin');

            // Enviar email (opcional)
            // sendEmail($user['email'], 'Cuenta aprobada', 'Su cuenta ha sido aprobada...');

            showAlert('Usuario aprobado exitosamente', 'success');
            header('Location: dashboard.php');
            exit;
        } elseif ($action === 'reject' && $userId > 0) {
            $db->update("UPDATE users SET status = 'cancelled' WHERE id = ?", [$userId]);
            logActivity($_SESSION['user_id'], 'reject_user', "Rechazó usuario ID: $userId", 'admin');
            showAlert('Usuario rechazado', 'warning');
            header('Location: dashboard.php');
            exit;
        } elseif ($action === 'suspend' && $userId > 0) {
            $db->update("UPDATE users SET status = 'suspended' WHERE id = ?", [$userId]);
            logActivity($_SESSION['user_id'], 'suspend_user', "Suspendió usuario ID: $userId", 'admin');
            showAlert('Usuario suspendido', 'warning');
            header('Location: dashboard.php');
            exit;
        } elseif ($action === 'activate' && $userId > 0) {
            $db->update("UPDATE users SET status = 'active' WHERE id = ?", [$userId]);
            logActivity($_SESSION['user_id'], 'activate_user', "Activó usuario ID: $userId", 'admin');
            showAlert('Usuario activado', 'success');
            header('Location: dashboard.php');
            exit;
        }
    }
}
?>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <i class="fas fa-chart-line"></i> CONECTA ERP
            </div>
            <p class="mb-0">Panel de Administración</p>
        </div>

        <div class="sidebar-user">
            <div class="sidebar-user-avatar">
                <?php echo strtoupper(substr($currentUser['full_name'], 0, 1)); ?>
            </div>
            <div class="sidebar-user-info">
                <h4><?php echo $currentUser['full_name']; ?></h4>
                <p><i class="fas fa-crown text-warning"></i> Administrador</p>
            </div>
        </div>

        <ul class="sidebar-menu">
            <li class="sidebar-menu-item">
                <a href="dashboard.php" class="sidebar-menu-link active">
                    <i class="fas fa-home sidebar-menu-icon"></i>
                    Dashboard
                </a>
            </li>
            <li class="sidebar-menu-item">
                <a href="users.php" class="sidebar-menu-link">
                    <i class="fas fa-users sidebar-menu-icon"></i>
                    Gestión de Usuarios
                    <?php if ($pendingUsers > 0): ?>
                        <span class="badge badge-warning ms-2"><?php echo $pendingUsers; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="sidebar-menu-item">
                <a href="companies.php" class="sidebar-menu-link">
                    <i class="fas fa-building sidebar-menu-icon"></i>
                    Empresas
                </a>
            </li>
            <li class="sidebar-menu-item">
                <a href="plans.php" class="sidebar-menu-link">
                    <i class="fas fa-tags sidebar-menu-icon"></i>
                    Planes y Precios
                </a>
            </li>
            <li class="sidebar-menu-item">
                <a href="payments.php" class="sidebar-menu-link">
                    <i class="fas fa-dollar-sign sidebar-menu-icon"></i>
                    Pagos
                </a>
            </li>
            <li class="sidebar-menu-item">
                <a href="modules.php" class="sidebar-menu-link">
                    <i class="fas fa-puzzle-piece sidebar-menu-icon"></i>
                    Módulos
                </a>
            </li>
            <li class="sidebar-menu-item">
                <a href="indicators.php" class="sidebar-menu-link">
                    <i class="fas fa-chart-bar sidebar-menu-icon"></i>
                    Indicadores Económicos
                </a>
            </li>
            <li class="sidebar-menu-item">
                <a href="activity_logs.php" class="sidebar-menu-link">
                    <i class="fas fa-history sidebar-menu-icon"></i>
                    Logs de Actividad
                </a>
            </li>
            <li class="sidebar-menu-item">
                <a href="settings.php" class="sidebar-menu-link">
                    <i class="fas fa-cog sidebar-menu-icon"></i>
                    Configuración
                </a>
            </li>
            <li class="sidebar-menu-item mt-4">
                <a href="../user/dashboard.php" class="sidebar-menu-link">
                    <i class="fas fa-external-link-alt sidebar-menu-icon"></i>
                    Ver Dashboard Usuario
                </a>
            </li>
            <li class="sidebar-menu-item">
                <a href="../logout.php" class="sidebar-menu-link">
                    <i class="fas fa-sign-out-alt sidebar-menu-icon"></i>
                    Cerrar Sesión
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1 class="page-title">
                    <i class="fas fa-home"></i> Dashboard de Administración
                </h1>
                <p class="text-muted mb-0">Bienvenido, <?php echo $currentUser['full_name']; ?></p>
            </div>
            <div>
                <button class="btn btn-primary" onclick="location.reload()">
                    <i class="fas fa-sync-alt"></i> Actualizar
                </button>
            </div>
        </div>

        <?php
        $alert = getAlert();
        if ($alert):
        ?>
            <div class="alert alert-<?php echo $alert['type']; ?> alert-dismissible fade show">
                <i class="fas fa-info-circle"></i> <?php echo $alert['message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Estadísticas -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon primary">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-label">Total Usuarios</div>
                <div class="stat-value"><?php echo number_format($totalUsers); ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-icon warning">
                    <i class="fas fa-user-clock"></i>
                </div>
                <div class="stat-label">Pendientes Aprobación</div>
                <div class="stat-value"><?php echo number_format($pendingUsers); ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-icon success">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="stat-label">Usuarios Activos</div>
                <div class="stat-value"><?php echo number_format($activeUsers); ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-icon info">
                    <i class="fas fa-hourglass-half"></i>
                </div>
                <div class="stat-label">En Período de Prueba</div>
                <div class="stat-value"><?php echo number_format($trialUsers); ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-icon primary">
                    <i class="fas fa-building"></i>
                </div>
                <div class="stat-label">Empresas Activas</div>
                <div class="stat-value"><?php echo number_format($totalCompanies); ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-icon success">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="stat-label">Ingresos Totales</div>
                <div class="stat-value">$<?php echo number_format($totalPayments, 0); ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-icon success">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="stat-label">Ingresos Este Mes</div>
                <div class="stat-value">$<?php echo number_format($thisMonthPayments, 0); ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-icon info">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-label">Indicadores</div>
                <div class="stat-value"><?php echo count($indicators); ?></div>
            </div>
        </div>

        <!-- Indicadores Económicos -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-chart-line"></i> Indicadores Económicos Actualizados</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($indicators as $indicator): ?>
                        <div class="col-md-3 mb-3">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h6 class="text-muted mb-1"><?php echo $indicator['indicator_name']; ?></h6>
                                    <h4 class="fw-bold text-primary mb-0">
                                        <?php echo number_format($indicator['value'], 2); ?>
                                        <small class="text-muted"><?php echo $indicator['currency']; ?></small>
                                    </h4>
                                    <small class="text-muted"><?php echo formatDate($indicator['date']); ?></small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Usuarios Pendientes de Aprobación -->
            <div class="col-lg-6">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-user-clock"></i> Usuarios Pendientes
                            <?php if ($pendingUsers > 0): ?>
                                <span class="badge badge-warning"><?php echo $pendingUsers; ?></span>
                            <?php endif; ?>
                        </h5>
                        <a href="users.php" class="btn btn-sm btn-primary">Ver todos</a>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($pendingUsersList)): ?>
                            <div class="p-4 text-center text-muted">
                                <i class="fas fa-check-circle fa-3x mb-3"></i>
                                <p>No hay usuarios pendientes de aprobación</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Usuario</th>
                                            <th>País</th>
                                            <th>Plan</th>
                                            <th>Fecha</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pendingUsersList as $user): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo $user['full_name']; ?></strong><br>
                                                    <small class="text-muted"><?php echo $user['email']; ?></small>
                                                </td>
                                                <td><?php echo $user['country_name']; ?></td>
                                                <td><span class="badge badge-info"><?php echo $user['plan_name']; ?></span></td>
                                                <td><?php echo formatDate($user['created_at']); ?></td>
                                                <td>
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="action" value="approve">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-success" title="Aprobar">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    </form>
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="action" value="reject">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger" title="Rechazar"
                                                                onclick="return confirm('¿Está seguro de rechazar este usuario?')">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Actividad Reciente -->
            <div class="col-lg-6">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-history"></i> Actividad Reciente</h5>
                        <a href="activity_logs.php" class="btn btn-sm btn-primary">Ver todo</a>
                    </div>
                    <div class="card-body p-0">
                        <div style="max-height: 400px; overflow-y: auto;">
                            <?php foreach ($recentActivity as $activity): ?>
                                <div class="p-3 border-bottom">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <strong><?php echo $activity['full_name'] ?? 'Sistema'; ?></strong>
                                            <small class="text-muted ms-2"><?php echo $activity['action']; ?></small>
                                        </div>
                                        <small class="text-muted"><?php echo formatDate($activity['created_at'], 'd/m/Y H:i'); ?></small>
                                    </div>
                                    <p class="mb-0 mt-1 text-muted small"><?php echo $activity['description']; ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Usuarios Recientes -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-users"></i> Usuarios Registrados Recientemente</h5>
                <a href="users.php" class="btn btn-sm btn-primary">Ver todos</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre Completo</th>
                                <th>Email</th>
                                <th>País</th>
                                <th>Plan</th>
                                <th>Estado</th>
                                <th>Aprobado</th>
                                <th>Fecha Registro</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentUsers as $user): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo $user['full_name']; ?></td>
                                    <td><?php echo $user['email']; ?></td>
                                    <td><?php echo $user['country_name']; ?></td>
                                    <td><span class="badge badge-info"><?php echo $user['plan_name']; ?></span></td>
                                    <td>
                                        <?php
                                        $statusBadge = [
                                            'trial' => 'warning',
                                            'active' => 'success',
                                            'suspended' => 'danger',
                                            'cancelled' => 'secondary',
                                            'pending' => 'info'
                                        ];
                                        $badgeClass = $statusBadge[$user['status']] ?? 'secondary';
                                        ?>
                                        <span class="badge badge-<?php echo $badgeClass; ?>"><?php echo ucfirst($user['status']); ?></span>
                                    </td>
                                    <td>
                                        <?php if ($user['is_approved']): ?>
                                            <span class="badge badge-success"><i class="fas fa-check"></i> Sí</span>
                                        <?php else: ?>
                                            <span class="badge badge-warning"><i class="fas fa-clock"></i> No</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo formatDate($user['created_at']); ?></td>
                                    <td>
                                        <a href="user_detail.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-info" title="Ver detalles">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
