<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Usuario - CONECTA ERP</title>

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

// Verificar autenticación
if (!isAuthenticated()) {
    redirect('../login_new.php');
}

// Si es admin, redirigir a dashboard admin
if (isAdmin()) {
    redirect('../admin/dashboard.php');
}

$db = Database::getInstance();
$currentUser = getCurrentUser();

// Verificar estado del trial
$trialStatus = checkTrialStatus();
if (!$trialStatus) {
    showAlert('Su período de prueba ha expirado. Por favor seleccione un plan de suscripción.', 'warning');
}

// Calcular días restantes del trial
$daysLeft = 0;
if ($currentUser['status'] === 'trial' && $currentUser['trial_ends_at']) {
    $daysLeft = ceil((strtotime($currentUser['trial_ends_at']) - time()) / 86400);
}

// Obtener módulos disponibles para el usuario
$modules = getUserModules($currentUser['id']);

// Obtener indicadores económicos
$indicators = $db->fetchAll("
    SELECT *
    FROM economic_indicators
    WHERE date = (SELECT MAX(date) FROM economic_indicators WHERE indicator_code = economic_indicators.indicator_code)
    ORDER BY indicator_code
");

// Obtener datos del usuario
$userCompanies = $db->fetchAll("
    SELECT * FROM companies
    WHERE owner_user_id = ? AND is_active = 1
    ORDER BY created_at DESC
", [$currentUser['id']]);

// Actividad reciente del usuario
$userActivity = $db->fetchAll("
    SELECT * FROM activity_logs
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 10
", [$currentUser['id']]);
?>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <i class="fas fa-chart-line"></i> CONECTA ERP
            </div>
            <p class="mb-0">Panel de Usuario</p>
        </div>

        <div class="sidebar-user">
            <div class="sidebar-user-avatar">
                <?php echo strtoupper(substr($currentUser['full_name'], 0, 1)); ?>
            </div>
            <div class="sidebar-user-info">
                <h4><?php echo $currentUser['full_name']; ?></h4>
                <p>
                    <?php echo $currentUser['document_number']; ?><br>
                    <span class="badge badge-<?php echo $currentUser['status'] === 'active' ? 'success' : 'warning'; ?>">
                        <?php echo ucfirst($currentUser['status']); ?>
                    </span>
                </p>
            </div>
        </div>

        <ul class="sidebar-menu">
            <li class="sidebar-menu-item">
                <a href="dashboard.php" class="sidebar-menu-link active">
                    <i class="fas fa-home sidebar-menu-icon"></i>
                    Dashboard
                </a>
            </li>

            <!-- Módulos dinámicos -->
            <?php foreach ($modules as $module): ?>
                <li class="sidebar-menu-item">
                    <a href="../modules<?php echo $module['route']; ?>" class="sidebar-menu-link">
                        <i class="<?php echo $module['icon']; ?> sidebar-menu-icon" style="color: <?php echo $module['color']; ?>"></i>
                        <?php echo $module['module_name']; ?>
                    </a>
                </li>
            <?php endforeach; ?>

            <li class="sidebar-menu-item mt-4">
                <a href="profile.php" class="sidebar-menu-link">
                    <i class="fas fa-user sidebar-menu-icon"></i>
                    Mi Perfil
                </a>
            </li>
            <li class="sidebar-menu-item">
                <a href="subscription.php" class="sidebar-menu-link">
                    <i class="fas fa-credit-card sidebar-menu-icon"></i>
                    Mi Suscripción
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
                    <i class="fas fa-home"></i> Bienvenido, <?php echo $currentUser['full_name']; ?>
                </h1>
                <p class="text-muted mb-0">
                    <i class="fas fa-id-card"></i> <?php echo $currentUser['document_type'] ?? 'RUT'; ?>: <?php echo $currentUser['document_number']; ?>
                </p>
            </div>
            <div>
                <?php if ($currentUser['status'] === 'trial'): ?>
                    <div class="alert alert-warning mb-0">
                        <i class="fas fa-clock"></i> Trial: <strong><?php echo $daysLeft; ?> días restantes</strong>
                    </div>
                <?php else: ?>
                    <div class="alert alert-success mb-0">
                        <i class="fas fa-check-circle"></i> Plan: <strong><?php echo $currentUser['plan_name'] ?? 'N/A'; ?></strong>
                    </div>
                <?php endif; ?>
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

        <!-- Alerta de Trial -->
        <?php if ($currentUser['status'] === 'trial' && $daysLeft <= 3): ?>
            <div class="alert alert-warning">
                <h5><i class="fas fa-exclamation-triangle"></i> Su período de prueba está por vencer</h5>
                <p class="mb-2">Le quedan <strong><?php echo $daysLeft; ?> día(s)</strong> de prueba gratis. Para continuar usando CONECTA ERP sin interrupciones, seleccione un plan de suscripción.</p>
                <a href="subscription.php" class="btn btn-warning">
                    <i class="fas fa-shopping-cart"></i> Ver Planes y Suscribirse
                </a>
            </div>
        <?php endif; ?>

        <!-- Información del Usuario -->
        <div class="card mb-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h3 class="mb-3"><i class="fas fa-user-circle"></i> Información de su Cuenta</h3>
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-2"><i class="fas fa-envelope"></i> <strong>Email:</strong> <?php echo $currentUser['email']; ?></p>
                                <p class="mb-2"><i class="fas fa-user"></i> <strong>Usuario:</strong> <?php echo $currentUser['username']; ?></p>
                                <p class="mb-2"><i class="fas fa-id-card"></i> <strong><?php echo $currentUser['document_type'] ?? 'Documento'; ?>:</strong> <?php echo $currentUser['document_number']; ?></p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-2"><i class="fas fa-globe"></i> <strong>País:</strong> <?php echo $currentUser['country_name'] ?? 'N/A'; ?></p>
                                <p class="mb-2"><i class="fas fa-language"></i> <strong>Idioma:</strong> <?php echo $currentUser['language_name'] ?? 'N/A'; ?></p>
                                <p class="mb-2"><i class="fas fa-building"></i> <strong>Empresa:</strong> <?php echo $currentUser['company_name'] ?? 'N/A'; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 text-center">
                        <div class="bg-white bg-opacity-10 p-4 rounded-4">
                            <h2 class="display-4 fw-bold mb-2"><?php echo count($modules); ?></h2>
                            <p class="mb-0">Módulos Disponibles</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Indicadores Económicos -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-chart-line"></i> Indicadores Económicos de Hoy</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($indicators as $indicator): ?>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="card text-center h-100">
                                <div class="card-body">
                                    <h6 class="text-muted mb-2"><?php echo $indicator['indicator_name']; ?></h6>
                                    <h3 class="fw-bold text-primary mb-0">
                                        <?php echo number_format($indicator['value'], 2); ?>
                                    </h3>
                                    <small class="text-muted"><?php echo $indicator['currency']; ?></small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Módulos del Sistema -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-puzzle-piece"></i> Módulos del Sistema</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($modules as $module): ?>
                        <div class="col-lg-3 col-md-4 col-sm-6 mb-3">
                            <a href="../modules<?php echo $module['route']; ?>" class="text-decoration-none">
                                <div class="card h-100 text-center" style="cursor: pointer; transition: all 0.3s;">
                                    <div class="card-body">
                                        <div class="mb-3" style="font-size: 3rem; color: <?php echo $module['color']; ?>">
                                            <i class="<?php echo $module['icon']; ?>"></i>
                                        </div>
                                        <h6 class="fw-bold"><?php echo $module['module_name']; ?></h6>
                                        <p class="text-muted small mb-0"><?php echo $module['description']; ?></p>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Mis Empresas -->
            <div class="col-lg-6">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-building"></i> Mis Empresas</h5>
                        <button class="btn btn-sm btn-primary">
                            <i class="fas fa-plus"></i> Nueva Empresa
                        </button>
                    </div>
                    <div class="card-body">
                        <?php if (empty($userCompanies)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-building fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No tiene empresas registradas</p>
                                <button class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Crear Primera Empresa
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($userCompanies as $company): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-1"><?php echo $company['company_name']; ?></h6>
                                                <small class="text-muted"><?php echo $company['business_name'] ?? 'N/A'; ?></small>
                                            </div>
                                            <button class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Actividad Reciente -->
            <div class="col-lg-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-history"></i> Mi Actividad Reciente</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($userActivity)): ?>
                            <div class="text-center py-4 text-muted">
                                <i class="fas fa-inbox fa-3x mb-3"></i>
                                <p>No hay actividad reciente</p>
                            </div>
                        <?php else: ?>
                            <div style="max-height: 400px; overflow-y: auto;">
                                <?php foreach ($userActivity as $activity): ?>
                                    <div class="border-bottom pb-2 mb-2">
                                        <div class="d-flex justify-content-between">
                                            <strong><?php echo $activity['action']; ?></strong>
                                            <small class="text-muted"><?php echo formatDate($activity['created_at'], 'd/m/Y H:i'); ?></small>
                                        </div>
                                        <p class="mb-0 small text-muted"><?php echo $activity['description']; ?></p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Accesos Rápidos -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-bolt"></i> Accesos Rápidos</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <a href="#" class="btn btn-outline-primary w-100">
                            <i class="fas fa-file-invoice"></i><br>
                            Nueva Factura
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="#" class="btn btn-outline-success w-100">
                            <i class="fas fa-user-plus"></i><br>
                            Nuevo Cliente
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="#" class="btn btn-outline-warning w-100">
                            <i class="fas fa-box"></i><br>
                            Nuevo Producto
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="#" class="btn btn-outline-info w-100">
                            <i class="fas fa-chart-bar"></i><br>
                            Ver Reportes
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
