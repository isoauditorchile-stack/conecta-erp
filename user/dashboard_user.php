<?php
require_once __DIR__ . '/../includes/config.php';

// Verificar autenticación
if (!isAuthenticated()) {
    redirect('/index.php');
}

// Verificar estado del trial
if (!checkTrialStatus()) {
    redirect('/trial_expired.php');
}

$user = getCurrentUser();
$db = Database::getInstance();

// Obtener módulos del usuario
$modules = getUserModules($_SESSION['user_id']);

// Calcular días restantes de trial
$days_remaining = 0;
$trial_warning = false;
if ($user['status'] === 'trial' && $user['trial_ends_at']) {
    $trial_end = strtotime($user['trial_ends_at']);
    $now = time();
    $days_remaining = max(0, ceil(($trial_end - $now) / 86400));
    $trial_warning = $days_remaining <= TRIAL_WARNING_DAYS;
}
?>
<!DOCTYPE html>
<html lang="<?php echo $user['language'] ?? 'es'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - CONECTA ERP</title>
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

        .trial-banner {
            margin: 0 1rem 1.5rem;
            padding: 1rem;
            background: rgba(245, 158, 11, 0.1);
            border: 1px solid rgba(245, 158, 11, 0.3);
            border-radius: 12px;
            text-align: center;
        }

        .trial-banner.warning {
            background: rgba(239, 68, 68, 0.1);
            border-color: rgba(239, 68, 68, 0.3);
        }

        .trial-days {
            font-size: 2rem;
            font-weight: 900;
            color: var(--warning);
            margin-bottom: 0.5rem;
        }

        .trial-banner.warning .trial-days {
            color: var(--error);
        }

        .trial-text {
            font-size: 0.9rem;
            color: var(--text-muted);
        }

        .upgrade-btn {
            margin-top: 1rem;
            width: 100%;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 0.75rem;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            cursor: pointer;
            transition: var(--transition);
        }

        .upgrade-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.4);
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

        .welcome-card {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(139, 92, 246, 0.1));
            border: 1px solid rgba(99, 102, 241, 0.3);
            border-radius: var(--border-radius-lg);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .welcome-title {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
        }

        .welcome-subtitle {
            color: var(--text-muted);
            font-size: 1.1rem;
        }

        /* MODULES GRID */
        .modules-section {
            margin-top: 3rem;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .section-title i {
            color: var(--primary);
        }

        .modules-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
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
            position: relative;
            overflow: hidden;
        }

        .module-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            transform: scaleX(0);
            transition: transform 0.4s;
        }

        .module-card:hover {
            background: rgba(255, 255, 255, 0.08);
            transform: translateY(-5px);
            border-color: rgba(99, 102, 241, 0.3);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .module-card:hover::before {
            transform: scaleX(1);
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
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.3);
        }

        .module-title {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .module-description {
            color: var(--text-muted);
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .quick-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .stat-box {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            text-align: center;
        }

        .stat-icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: 800;
            margin-bottom: 0.25rem;
        }

        .stat-label {
            color: var(--text-muted);
            font-size: 0.9rem;
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

        <?php if ($user['status'] === 'trial'): ?>
            <div class="trial-banner <?php echo $trial_warning ? 'warning' : ''; ?>">
                <div class="trial-days"><?php echo $days_remaining; ?></div>
                <div class="trial-text">días restantes de prueba</div>
                <button class="upgrade-btn" onclick="window.location.href='upgrade.php'">
                    <i class="fas fa-rocket"></i> Actualizar Plan
                </button>
            </div>
        <?php endif; ?>

        <ul class="sidebar-menu">
            <li class="menu-item">
                <a href="dashboard_user.php" class="menu-link active">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="profile.php" class="menu-link">
                    <i class="fas fa-user"></i>
                    <span>Mi Perfil</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="company.php" class="menu-link">
                    <i class="fas fa-building"></i>
                    <span>Mi Empresa</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="settings.php" class="menu-link">
                    <i class="fas fa-cog"></i>
                    <span>Configuración</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="support.php" class="menu-link">
                    <i class="fas fa-life-ring"></i>
                    <span>Soporte</span>
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
                <h1>👋 Bienvenido, <?php echo htmlspecialchars($user['firstname']); ?></h1>
            </div>
            <div class="topbar-right">
                <div class="user-menu">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($user['firstname'], 0, 1) . substr($user['lastname'], 0, 1)); ?>
                    </div>
                    <div>
                        <div style="font-weight: 600;"><?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?></div>
                        <div style="font-size: 0.85rem; color: var(--text-muted);"><?php echo htmlspecialchars($user['company_name']); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- CONTENT -->
        <div class="content">
            <!-- WELCOME CARD -->
            <div class="welcome-card">
                <div class="welcome-title">
                    🚀 Bienvenido a CONECTA ERP
                </div>
                <div class="welcome-subtitle">
                    Sistema de Gestión Empresarial Completo - <?php echo htmlspecialchars($user['company_name']); ?>
                </div>
            </div>

            <!-- QUICK STATS -->
            <div class="quick-stats">
                <div class="stat-box">
                    <div class="stat-icon" style="color: var(--primary);">
                        <i class="fas fa-th-large"></i>
                    </div>
                    <div class="stat-value"><?php echo count($modules); ?></div>
                    <div class="stat-label">Módulos Disponibles</div>
                </div>
                <div class="stat-box">
                    <div class="stat-icon" style="color: var(--success);">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-value"><?php echo ucfirst($user['status']); ?></div>
                    <div class="stat-label">Estado de Cuenta</div>
                </div>
                <div class="stat-box">
                    <div class="stat-icon" style="color: var(--warning);">
                        <i class="fas fa-globe"></i>
                    </div>
                    <div class="stat-value"><?php echo strtoupper($user['language']); ?></div>
                    <div class="stat-label">Idioma</div>
                </div>
                <div class="stat-box">
                    <div class="stat-icon" style="color: var(--info);">
                        <i class="fas fa-coins"></i>
                    </div>
                    <div class="stat-value"><?php echo $user['currency']; ?></div>
                    <div class="stat-label">Moneda</div>
                </div>
            </div>

            <!-- MÓDULOS -->
            <div class="modules-section">
                <h2 class="section-title">
                    <i class="fas fa-th-large"></i>
                    Tus Módulos
                </h2>
                <div class="modules-grid">
                    <?php if (empty($modules)): ?>
                        <div class="card" style="grid-column: 1 / -1;">
                            <div style="text-align: center; padding: 3rem;">
                                <i class="fas fa-inbox" style="font-size: 4rem; color: var(--text-dark); margin-bottom: 1rem;"></i>
                                <h3>No tienes módulos asignados</h3>
                                <p style="color: var(--text-muted);">Contacta con el administrador para obtener acceso a los módulos.</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($modules as $module): ?>
                            <a href="../modules/<?php echo strtolower($module['code']); ?>/index.php" class="module-card">
                                <div class="module-icon" style="background: <?php echo $module['color']; ?>;">
                                    <i class="<?php echo $module['icon']; ?>"></i>
                                </div>
                                <div class="module-title"><?php echo htmlspecialchars($module['name']); ?></div>
                                <div class="module-description"><?php echo htmlspecialchars($module['description']); ?></div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
