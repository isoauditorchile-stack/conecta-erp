<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/rut_validator.php';

// Verificar autenticación
if (!isAuthenticated()) {
    header('Location: ../login.php');
    exit;
}

// Obtener información del usuario
$db = Database::getInstance();
$user = $db->fetchOne("SELECT u.*, c.name_es as country_name, l.name as language_name
                       FROM users u
                       LEFT JOIN countries c ON u.country_id = c.id
                       LEFT JOIN languages l ON u.language_code = l.code
                       WHERE u.id = ?", [$_SESSION['user_id']]);

if (!$user) {
    session_destroy();
    header('Location: ../login.php');
    exit;
}

// Formatear RUT si es chileno
$formatted_tax_id = $user['tax_id'];
if ($user['country_id'] == 1 && $user['tax_id']) {
    $formatted_tax_id = formatChileanRUT($user['tax_id']);
}

// Calcular días restantes de trial
$trial_days_left = 0;
$trial_expired = false;
if ($user['status'] === 'trial' && $user['trial_ends_at']) {
    $trial_end = strtotime($user['trial_ends_at']);
    $trial_days_left = ceil(($trial_end - time()) / 86400);
    if ($trial_days_left < 0) {
        $trial_expired = true;
        $trial_days_left = 0;
    }
}

// Obtener módulos disponibles para el usuario
$modules = getUserModules($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="<?php echo $user['language_code']; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - CONECTA ERP</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <i class="bi bi-box-seam-fill"></i>
                <span>CONECTA ERP</span>
            </div>
            <button class="sidebar-toggle" id="sidebarToggle">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <div class="sidebar-user">
            <div class="user-avatar">
                <?php echo strtoupper(substr($user['full_name'], 0, 2)); ?>
            </div>
            <div class="user-info">
                <div class="user-name"><?php echo htmlspecialchars($user['full_name']); ?></div>
                <div class="user-role"><?php echo $user['is_admin'] ? 'Administrador' : 'Usuario'; ?></div>
            </div>
        </div>

        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-item active">
                <i class="bi bi-speedometer2"></i>
                <span>Dashboard</span>
            </a>

            <?php if ($user['is_admin']): ?>
                <a href="../admin/dashboard.php" class="nav-item">
                    <i class="bi bi-shield-lock"></i>
                    <span>Panel Admin</span>
                </a>
            <?php endif; ?>

            <div class="nav-divider">Módulos</div>

            <?php foreach ($modules as $module): ?>
                <a href="#" class="nav-item" onclick="showModuleSubmenu('<?php echo $module['module_code']; ?>')">
                    <i class="<?php echo $module['icon']; ?>"></i>
                    <span><?php echo htmlspecialchars($module['module_name']); ?></span>
                    <i class="bi bi-chevron-right ms-auto"></i>
                </a>
            <?php endforeach; ?>

            <div class="nav-divider">Sistema</div>

            <a href="../logout.php" class="nav-item">
                <i class="bi bi-box-arrow-right"></i>
                <span>Cerrar Sesión</span>
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar -->
        <div class="topbar">
            <button class="menu-toggle" id="menuToggle">
                <i class="bi bi-list"></i>
            </button>

            <div class="topbar-search">
                <i class="bi bi-search"></i>
                <input type="text" placeholder="Buscar en CONECTA ERP...">
            </div>

            <div class="topbar-actions">
                <!-- Selector de idioma -->
                <div class="dropdown">
                    <button class="topbar-btn" data-bs-toggle="dropdown">
                        <i class="bi bi-translate"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="#">🇪🇸 Español</a></li>
                        <li><a class="dropdown-item" href="#">🇺🇸 English</a></li>
                        <li><a class="dropdown-item" href="#">🇧🇷 Português</a></li>
                        <li><a class="dropdown-item" href="#">🇫🇷 Français</a></li>
                        <li><a class="dropdown-item" href="#">🇩🇪 Deutsch</a></li>
                        <li><a class="dropdown-item" href="#">🇮🇹 Italiano</a></li>
                        <li><a class="dropdown-item" href="#">🇷🇺 Русский</a></li>
                        <li><a class="dropdown-item" href="#">🇨🇳 中文</a></li>
                        <li><a class="dropdown-item" href="#">🇯🇵 日本語</a></li>
                    </ul>
                </div>

                <!-- Notificaciones -->
                <div class="dropdown">
                    <button class="topbar-btn" data-bs-toggle="dropdown">
                        <i class="bi bi-bell"></i>
                        <span class="badge">3</span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end notifications-dropdown">
                        <h6 class="dropdown-header">Notificaciones</h6>
                        <a class="dropdown-item" href="#">
                            <i class="bi bi-info-circle text-info"></i>
                            <div>
                                <strong>Bienvenido al sistema</strong>
                                <small>Hace 1 hora</small>
                            </div>
                        </a>
                        <a class="dropdown-item" href="#">
                            <i class="bi bi-exclamation-triangle text-warning"></i>
                            <div>
                                <strong>Trial expira en <?php echo $trial_days_left; ?> días</strong>
                                <small>Hace 2 horas</small>
                            </div>
                        </a>
                        <a class="dropdown-item" href="#">
                            <i class="bi bi-check-circle text-success"></i>
                            <div>
                                <strong>Perfil actualizado</strong>
                                <small>Hace 1 día</small>
                            </div>
                        </a>
                        <a class="dropdown-item text-center text-primary" href="#">Ver todas</a>
                    </div>
                </div>

                <!-- Usuario -->
                <div class="dropdown">
                    <button class="topbar-user" data-bs-toggle="dropdown">
                        <div class="user-avatar-sm">
                            <?php echo strtoupper(substr($user['full_name'], 0, 2)); ?>
                        </div>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="#"><i class="bi bi-person me-2"></i> Mi Perfil</a></li>
                        <li><a class="dropdown-item" href="#"><i class="bi bi-gear me-2"></i> Configuración</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../logout.php"><i class="bi bi-box-arrow-right me-2"></i> Cerrar Sesión</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Page Content -->
        <div class="page-content">
            <!-- Bienvenida -->
            <div class="welcome-banner">
                <div class="welcome-content">
                    <h1>¡Bienvenido, <?php echo htmlspecialchars($user['full_name']); ?>! 👋</h1>
                    <p>
                        <strong>RUT:</strong> <?php echo htmlspecialchars($formatted_tax_id); ?>
                        <span class="mx-2">•</span>
                        <strong>País:</strong> <?php echo htmlspecialchars($user['country_name']); ?>
                        <span class="mx-2">•</span>
                        <strong>Idioma:</strong> <?php echo htmlspecialchars($user['language_name']); ?>
                    </p>
                </div>
                <?php if (!$user['is_admin'] && $user['status'] === 'trial'): ?>
                    <div class="trial-badge">
                        <?php if ($trial_expired): ?>
                            <i class="bi bi-exclamation-triangle"></i>
                            Trial Expirado
                        <?php else: ?>
                            <i class="bi bi-clock-history"></i>
                            <?php echo $trial_days_left; ?> días de trial restantes
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($trial_expired): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <strong>Tu período de prueba ha expirado.</strong>
                    Por favor, selecciona un plan para continuar usando CONECTA ERP.
                    <a href="#" class="alert-link">Ver Planes</a>
                </div>
            <?php elseif (!$user['is_admin'] && $trial_days_left <= 3 && $trial_days_left > 0): ?>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-circle-fill me-2"></i>
                    <strong>Tu período de prueba expira en <?php echo $trial_days_left; ?> día<?php echo $trial_days_left > 1 ? 's' : ''; ?>.</strong>
                    Elige un plan para continuar disfrutando de CONECTA ERP sin interrupciones.
                    <a href="#" class="alert-link">Ver Planes</a>
                </div>
            <?php endif; ?>

            <!-- Estadísticas Rápidas -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #3b82f6, #2563eb);">
                        <i class="bi bi-box-seam-fill"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Módulos Disponibles</div>
                        <div class="stat-value"><?php echo count($modules); ?></div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #10b981, #059669);">
                        <i class="bi bi-graph-up-arrow"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Operaciones Hoy</div>
                        <div class="stat-value">24</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Tareas Pendientes</div>
                        <div class="stat-value">8</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Usuarios Activos</div>
                        <div class="stat-value">12</div>
                    </div>
                </div>
            </div>

            <!-- Módulos Principales -->
            <div class="section-header">
                <h2>Módulos del Sistema</h2>
                <p>Accede rápidamente a los módulos disponibles</p>
            </div>

            <div class="modules-grid">
                <?php foreach ($modules as $module): ?>
                    <div class="module-card" onclick="location.href='../modules/<?php echo strtolower($module['module_code']); ?>/index.php'">
                        <div class="module-icon" style="background: <?php echo $module['color']; ?>">
                            <i class="<?php echo $module['icon']; ?>"></i>
                        </div>
                        <div class="module-content">
                            <h3><?php echo htmlspecialchars($module['module_name']); ?></h3>
                            <p><?php echo htmlspecialchars($module['module_description']); ?></p>
                        </div>
                        <div class="module-arrow">
                            <i class="bi bi-arrow-right"></i>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (empty($modules)): ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle-fill me-2"></i>
                    No tienes módulos asignados. Contacta al administrador para obtener acceso.
                </div>
            <?php endif; ?>

            <!-- Actividad Reciente -->
            <div class="section-header mt-5">
                <h2>Actividad Reciente</h2>
                <a href="#" class="btn btn-outline-primary btn-sm">Ver Todo</a>
            </div>

            <div class="activity-list">
                <div class="activity-item">
                    <div class="activity-icon bg-success">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div class="activity-content">
                        <div class="activity-title">Sesión iniciada</div>
                        <div class="activity-time">Hace 5 minutos</div>
                    </div>
                </div>

                <div class="activity-item">
                    <div class="activity-icon bg-primary">
                        <i class="bi bi-file-earmark-text"></i>
                    </div>
                    <div class="activity-content">
                        <div class="activity-title">Documento creado en Finanzas</div>
                        <div class="activity-time">Hace 2 horas</div>
                    </div>
                </div>

                <div class="activity-item">
                    <div class="activity-icon bg-warning">
                        <i class="bi bi-pencil-square"></i>
                    </div>
                    <div class="activity-content">
                        <div class="activity-title">Perfil actualizado</div>
                        <div class="activity-time">Hace 1 día</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle Sidebar
        document.getElementById('menuToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.add('show');
        });

        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.remove('show');
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const menuToggle = document.getElementById('menuToggle');

            if (window.innerWidth <= 768) {
                if (!sidebar.contains(event.target) && !menuToggle.contains(event.target)) {
                    sidebar.classList.remove('show');
                }
            }
        });

        function showModuleSubmenu(moduleCode) {
            // TODO: Implementar navegación a submódulos
            alert('Navegando al módulo: ' + moduleCode);
        }
    </script>
</body>
</html>
