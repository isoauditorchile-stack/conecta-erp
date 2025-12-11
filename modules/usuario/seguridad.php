<?php
require_once __DIR__ . '/../../config/config.php';

// Verificar si está logueado
if (!is_logged_in()) {
    redirect('/login.php');
}

// Validar que solo ADMIN_GLOBAL puede acceder
$user_id = $_SESSION['user_id'];
$user = db_get_row("SELECT * FROM usuarios WHERE id = ?", [$user_id]);

if ($user['tipo_usuario'] !== 'admin_global') {
    redirect('/admin/dashboard.php');
}

// ========================================
// AUTO-CREAR TABLAS DE SEGURIDAD
// ========================================
try {
    // Tabla: roles
    db_query("CREATE TABLE IF NOT EXISTS roles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(100) NOT NULL UNIQUE,
        codigo VARCHAR(50) UNIQUE,
        descripcion TEXT,
        nivel_acceso ENUM('basico', 'intermedio', 'avanzado') DEFAULT 'basico',
        activo TINYINT(1) DEFAULT 1,
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_activo (activo),
        INDEX idx_codigo (codigo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Tabla: permisos
    db_query("CREATE TABLE IF NOT EXISTS permisos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(100) NOT NULL,
        codigo VARCHAR(50) UNIQUE,
        modulo VARCHAR(50) NOT NULL,
        submodulo VARCHAR(50),
        nivel ENUM('lectura', 'escritura', 'eliminacion') DEFAULT 'lectura',
        nivel_riesgo ENUM('low', 'medium', 'high', 'critical') DEFAULT 'low',
        log_requerido TINYINT(1) DEFAULT 0,
        descripcion TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_modulo (modulo),
        INDEX idx_nivel (nivel),
        INDEX idx_riesgo (nivel_riesgo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Tabla: rol_permisos (relación muchos a muchos)
    db_query("CREATE TABLE IF NOT EXISTS rol_permisos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        rol_id INT NOT NULL,
        permiso_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_rol_permiso (rol_id, permiso_id),
        FOREIGN KEY (rol_id) REFERENCES roles(id) ON DELETE CASCADE,
        FOREIGN KEY (permiso_id) REFERENCES permisos(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Tabla: usuario_roles (relación muchos a muchos)
    db_query("CREATE TABLE IF NOT EXISTS usuario_roles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        usuario_id INT NOT NULL,
        rol_id INT NOT NULL,
        empresa_id INT,
        fecha_asignacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        asignado_por INT,
        UNIQUE KEY unique_user_rol_empresa (usuario_id, rol_id, empresa_id),
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
        FOREIGN KEY (rol_id) REFERENCES roles(id) ON DELETE CASCADE,
        INDEX idx_usuario (usuario_id),
        INDEX idx_rol (rol_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Tabla: user_sessions (gestión de sesiones)
    db_query("CREATE TABLE IF NOT EXISTS user_sessions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        session_id VARCHAR(255) NOT NULL UNIQUE,
        user_id INT NOT NULL,
        ip_address VARCHAR(45),
        user_agent TEXT,
        country VARCHAR(2),
        city VARCHAR(100),
        device_type VARCHAR(50),
        browser VARCHAR(50),
        os VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        expires_at TIMESTAMP NULL,
        logout_at TIMESTAMP NULL,
        FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE,
        INDEX idx_session (session_id),
        INDEX idx_user (user_id),
        INDEX idx_expires (expires_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Tabla: audit_logs (auditoría de eventos)
    db_query("CREATE TABLE IF NOT EXISTS audit_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        accion VARCHAR(100) NOT NULL,
        modulo VARCHAR(50),
        submodulo VARCHAR(50),
        entidad_tipo VARCHAR(50),
        entidad_id INT,
        detalles TEXT,
        ip_address VARCHAR(45),
        user_agent TEXT,
        pais VARCHAR(2),
        severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'low',
        resultado ENUM('exito', 'fallo') DEFAULT 'exito',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE SET NULL,
        INDEX idx_user (user_id),
        INDEX idx_accion (accion),
        INDEX idx_severity (severity),
        INDEX idx_fecha (created_at),
        INDEX idx_modulo (modulo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Tabla: blocked_ips (IPs bloqueadas)
    db_query("CREATE TABLE IF NOT EXISTS blocked_ips (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ip_address VARCHAR(45) NOT NULL UNIQUE,
        motivo ENUM('ataque', 'fuerza_bruta', 'spam', 'otro') DEFAULT 'otro',
        detalles TEXT,
        bloqueado_por INT,
        activo TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (bloqueado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
        INDEX idx_ip (ip_address),
        INDEX idx_activo (activo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Tabla: security_alerts (alertas de seguridad)
    db_query("CREATE TABLE IF NOT EXISTS security_alerts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        titulo VARCHAR(255) NOT NULL,
        descripcion TEXT,
        tipo VARCHAR(50),
        severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
        estado ENUM('pendiente', 'revisada', 'resuelta') DEFAULT 'pendiente',
        user_id INT,
        ip_address VARCHAR(45),
        detalles_json JSON,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE SET NULL,
        INDEX idx_estado (estado),
        INDEX idx_severity (severity),
        INDEX idx_fecha (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Tabla: security_policies (políticas de seguridad)
    db_query("CREATE TABLE IF NOT EXISTS security_policies (
        id INT AUTO_INCREMENT PRIMARY KEY,
        clave VARCHAR(100) NOT NULL UNIQUE,
        valor TEXT,
        tipo ENUM('password', 'session', 'auth', 'export', 'general') DEFAULT 'general',
        descripcion TEXT,
        updated_by INT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (updated_by) REFERENCES usuarios(id) ON DELETE SET NULL,
        INDEX idx_tipo (tipo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

} catch (Exception $e) {
    // Si hay error en la creación de tablas, registrarlo pero continuar
    error_log("Error creando tablas de seguridad: " . $e->getMessage());
}

// Obtener estadísticas de seguridad
try {
    $stats = [
        'total_usuarios' => db_get_var("SELECT COUNT(*) FROM usuarios WHERE activo = 1") ?? 0,
        'usuarios_bloqueados' => db_get_var("SELECT COUNT(*) FROM usuarios WHERE estado = 'bloqueado'") ?? 0,
        'sesiones_activas' => db_get_var("SELECT COUNT(DISTINCT session_id) FROM user_sessions WHERE logout_at IS NULL AND expires_at > NOW()") ?? 0,
        'eventos_criticos_hoy' => db_get_var("SELECT COUNT(*) FROM audit_logs WHERE severity = 'critical' AND DATE(created_at) = CURDATE()") ?? 0,
        'total_roles' => db_get_var("SELECT COUNT(*) FROM roles WHERE activo = 1") ?? 0,
        'total_permisos' => db_get_var("SELECT COUNT(*) FROM permisos") ?? 0,
        'intentos_fallidos_hoy' => db_get_var("SELECT COUNT(*) FROM audit_logs WHERE accion = 'login_failed' AND DATE(created_at) = CURDATE()") ?? 0,
        'alertas_pendientes' => db_get_var("SELECT COUNT(*) FROM security_alerts WHERE estado = 'pendiente'") ?? 0
    ];
} catch (Exception $e) {
    $stats = [
        'total_usuarios' => 0,
        'usuarios_bloqueados' => 0,
        'sesiones_activas' => 0,
        'eventos_criticos_hoy' => 0,
        'total_roles' => 0,
        'total_permisos' => 0,
        'intentos_fallidos_hoy' => 0,
        'alertas_pendientes' => 0
    ];
}

// Obtener usuarios
try {
    $usuarios = db_query("SELECT u.*,
                          (SELECT COUNT(*) FROM user_sessions WHERE user_id = u.id AND logout_at IS NULL) as sesiones_activas,
                          (SELECT MAX(created_at) FROM audit_logs WHERE user_id = u.id AND accion = 'login') as ultimo_acceso,
                          (SELECT COUNT(*) FROM audit_logs WHERE user_id = u.id AND accion = 'login_failed' AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)) as intentos_fallidos_24h
                          FROM usuarios u
                          ORDER BY u.created_at DESC");
} catch (Exception $e) {
    $usuarios = [];
}

// Obtener roles
try {
    $roles = db_query("SELECT r.*,
                       (SELECT COUNT(*) FROM usuario_roles WHERE rol_id = r.id) as total_usuarios,
                       (SELECT COUNT(*) FROM rol_permisos WHERE rol_id = r.id) as total_permisos
                       FROM roles r
                       WHERE r.activo = 1
                       ORDER BY r.nombre ASC");
} catch (Exception $e) {
    $roles = [];
}

// Obtener permisos
try {
    $permisos = db_query("SELECT p.*,
                          (SELECT COUNT(*) FROM rol_permisos WHERE permiso_id = p.id) as roles_asignados
                          FROM permisos p
                          ORDER BY p.modulo, p.submodulo, p.nombre ASC");
} catch (Exception $e) {
    $permisos = [];
}

// Obtener eventos críticos recientes
try {
    $eventos_criticos = db_query("SELECT a.*,
                                   u.nombre_completo as usuario_nombre,
                                   u.email as usuario_email
                                   FROM audit_logs a
                                   LEFT JOIN usuarios u ON a.user_id = u.id
                                   WHERE a.severity IN ('critical', 'high')
                                   ORDER BY a.created_at DESC
                                   LIMIT 50");
} catch (Exception $e) {
    $eventos_criticos = [];
}

// Obtener sesiones activas
try {
    $sesiones_activas = db_query("SELECT s.*,
                                   u.nombre_completo,
                                   u.email,
                                   u.documento_numero
                                   FROM user_sessions s
                                   LEFT JOIN usuarios u ON s.user_id = u.id
                                   WHERE s.logout_at IS NULL AND s.expires_at > NOW()
                                   ORDER BY s.created_at DESC");
} catch (Exception $e) {
    $sesiones_activas = [];
}

// Obtener IPs bloqueadas
try {
    $ips_bloqueadas = db_query("SELECT * FROM blocked_ips WHERE activo = 1 ORDER BY created_at DESC");
} catch (Exception $e) {
    $ips_bloqueadas = [];
}

// Obtener alertas de seguridad
try {
    $alertas_seguridad = db_query("SELECT * FROM security_alerts WHERE estado = 'pendiente' ORDER BY created_at DESC LIMIT 20");
} catch (Exception $e) {
    $alertas_seguridad = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Seguridad - CONECTA ERP</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/styles.css">

   <style>
/* ============================================ */
/* CORRECCIÓN DE LAYOUT PRINCIPAL              */
/* ============================================ */
.main-wrapper {
    margin-left: 260px !important;
    width: calc(100% - 260px) !important;
    padding: 0 !important;
}

.main-header {
    width: 100% !important;
    padding: 1rem 2rem !important;
}

.main-content {
    width: 100% !important;
    padding: 2rem !important;
    margin: 0 !important;
}

.card, .alert, .row {
    max-width: 100% !important;
}
/* ============================================ */

/* Botón flotante pantalla completa */
.fullscreen-btn {
    position: fixed;
    top: 80px;
    right: 20px;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
    color: white;
    border: none;
    box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
    cursor: pointer;
    z-index: 10000;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
}
.fullscreen-btn:hover {
    transform: scale(1.15);
    box-shadow: 0 6px 20px rgba(220, 38, 38, 0.4);
}

/* Fullscreen mode */
body.fullscreen-mode .sidebar {
    display: none !important;
}
body.fullscreen-mode .main-wrapper {
    margin-left: 0 !important;
    width: 100vw !important;
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    z-index: 9999;
    background: var(--body-bg);
    overflow: auto;
}
body.fullscreen-mode .fullscreen-btn {
    top: 20px;
    right: 20px;
    z-index: 10000;
}

.stat-card {
    border-left: 4px solid #dc2626;
}

.stat-card.warning {
    border-left-color: #f59e0b;
}

.stat-card.success {
    border-left-color: #10b981;
}

.stat-card.info {
    border-left-color: #3b82f6;
}

.badge-activo { background: #10b981; color: white; }
.badge-inactivo { background: #6b7280; color: white; }
.badge-bloqueado { background: #dc2626; color: white; }
.badge-suspendido { background: #f59e0b; color: white; }
.badge-critical { background: #dc2626; color: white; }
.badge-high { background: #f59e0b; color: white; }
.badge-medium { background: #3b82f6; color: white; }
.badge-low { background: #6b7280; color: white; }
.badge-lectura { background: #3b82f6; color: white; }
.badge-escritura { background: #10b981; color: white; }
.badge-eliminacion { background: #dc2626; color: white; }

.table-security {
    font-size: 0.9rem;
}
.table-security th {
    background: var(--card-bg);
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.5px;
    color: var(--text-secondary);
}

.action-btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
    margin: 0 0.125rem;
}

.form-section {
    background: var(--card-bg);
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    border: 1px solid rgba(0,0,0,0.05);
}

.form-section-title {
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 1rem;
    color: var(--text-primary);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.form-section-title i {
    color: #dc2626;
}

.info-item {
    padding: 0.75rem;
    background: var(--body-bg);
    border-radius: 8px;
    margin-bottom: 0.5rem;
}

.info-item label {
    font-weight: 600;
    color: var(--text-secondary);
    font-size: 0.875rem;
    margin-bottom: 0.25rem;
}

.info-item .value {
    color: var(--text-primary);
}

.audit-log-item {
    border-left: 3px solid #dc2626;
    padding: 0.75rem;
    margin-bottom: 0.5rem;
    background: var(--card-bg);
    border-radius: 8px;
}

.audit-log-item.warning {
    border-left-color: #f59e0b;
}

.audit-log-item.info {
    border-left-color: #3b82f6;
}

.security-alert {
    background: #fef2f2;
    border: 1px solid #fecaca;
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1rem;
}

.security-alert.critical {
    background: #dc2626;
    color: white;
    border-color: #991b1b;
}

.session-card {
    border: 1px solid var(--border-color);
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 0.5rem;
    background: var(--card-bg);
}

.session-card .session-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.permission-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 0.5rem;
}

.permission-item {
    border: 1px solid var(--border-color);
    padding: 0.75rem;
    border-radius: 8px;
    background: var(--card-bg);
}

.permission-item:hover {
    background: var(--body-bg);
    cursor: pointer;
}
</style>
</head>
<body>
    <!-- Sidebar -->
    <?php include __DIR__ . '/../../includes/sidebar_user.php'; ?>

    <!-- Main Content -->
    <div class="main-wrapper" id="mainWrapper">
        <header class="main-header">
            <div class="header-left">
                <h4 class="mb-0" style="color: var(--text-primary);">
                    <i class="fas fa-shield-alt me-2"></i>
                    Gestión de Seguridad
                </h4>
                <small class="text-muted">Solo accesible para ADMIN_GLOBAL</small>
            </div>

            <div class="header-right">
                <button class="theme-toggle" id="themeToggle">
                    <i class="fas fa-moon"></i>
                </button>

                <div class="user-menu">
                    <div class="user-avatar" style="background: #dc2626;">
                        <?php echo strtoupper(substr($user['nombre_completo'], 0, 2)); ?>
                    </div>
                    <div class="user-info">
                        <div class="user-name"><?php echo $user['nombre_completo']; ?></div>
                        <div class="user-role">ADMIN GLOBAL</div>
                    </div>
                </div>
            </div>
        </header>

        <main class="main-content" id="mainContent">
            <!-- Botón Pantalla Completa -->
            <button class="fullscreen-btn" id="fullscreenToggle" title="Pantalla completa">
                <i class="fas fa-expand"></i>
            </button>

            <!-- Estadísticas de Seguridad -->
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="card stat-card info">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <p class="text-muted mb-1" style="font-size: 0.875rem;">Usuarios Activos</p>
                                    <h2 class="mb-0"><?php echo number_format($stats['total_usuarios']); ?></h2>
                                </div>
                                <div class="feature-icon" style="width: 50px; height: 50px; font-size: 1.25rem; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);">
                                    <i class="fas fa-users"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card stat-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <p class="text-muted mb-1" style="font-size: 0.875rem;">Usuarios Bloqueados</p>
                                    <h2 class="mb-0" style="color: #dc2626;"><?php echo number_format($stats['usuarios_bloqueados']); ?></h2>
                                </div>
                                <div class="feature-icon" style="width: 50px; height: 50px; font-size: 1.25rem; background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);">
                                    <i class="fas fa-user-lock"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card stat-card success">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <p class="text-muted mb-1" style="font-size: 0.875rem;">Sesiones Activas</p>
                                    <h2 class="mb-0" style="color: #10b981;"><?php echo number_format($stats['sesiones_activas']); ?></h2>
                                </div>
                                <div class="feature-icon" style="width: 50px; height: 50px; font-size: 1.25rem; background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                                    <i class="fas fa-plug"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card stat-card warning">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <p class="text-muted mb-1" style="font-size: 0.875rem;">Eventos Críticos Hoy</p>
                                    <h2 class="mb-0" style="color: #f59e0b;"><?php echo number_format($stats['eventos_criticos_hoy']); ?></h2>
                                </div>
                                <div class="feature-icon" style="width: 50px; height: 50px; font-size: 1.25rem; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card stat-card info">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <p class="text-muted mb-1" style="font-size: 0.875rem;">Roles Activos</p>
                                    <h2 class="mb-0"><?php echo number_format($stats['total_roles']); ?></h2>
                                </div>
                                <div class="feature-icon" style="width: 50px; height: 50px; font-size: 1.25rem;">
                                    <i class="fas fa-user-tag"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card stat-card info">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <p class="text-muted mb-1" style="font-size: 0.875rem;">Permisos Definidos</p>
                                    <h2 class="mb-0"><?php echo number_format($stats['total_permisos']); ?></h2>
                                </div>
                                <div class="feature-icon" style="width: 50px; height: 50px; font-size: 1.25rem;">
                                    <i class="fas fa-key"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card stat-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <p class="text-muted mb-1" style="font-size: 0.875rem;">Intentos Fallidos Hoy</p>
                                    <h2 class="mb-0" style="color: #dc2626;"><?php echo number_format($stats['intentos_fallidos_hoy']); ?></h2>
                                </div>
                                <div class="feature-icon" style="width: 50px; height: 50px; font-size: 1.25rem; background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);">
                                    <i class="fas fa-ban"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card stat-card warning">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <p class="text-muted mb-1" style="font-size: 0.875rem;">Alertas Pendientes</p>
                                    <h2 class="mb-0" style="color: #f59e0b;"><?php echo number_format($stats['alertas_pendientes']); ?></h2>
                                </div>
                                <div class="feature-icon" style="width: 50px; height: 50px; font-size: 1.25rem; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                                    <i class="fas fa-bell"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs de Navegación -->
            <ul class="nav nav-tabs mb-4" id="seguridadTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="usuarios-tab" data-bs-toggle="tab" data-bs-target="#usuarios" type="button">
                        <i class="fas fa-users me-2"></i>Usuarios
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="roles-tab" data-bs-toggle="tab" data-bs-target="#roles" type="button">
                        <i class="fas fa-user-tag me-2"></i>Roles
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="permisos-tab" data-bs-toggle="tab" data-bs-target="#permisos" type="button">
                        <i class="fas fa-key me-2"></i>Permisos
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="politicas-tab" data-bs-toggle="tab" data-bs-target="#politicas" type="button">
                        <i class="fas fa-shield-alt me-2"></i>Políticas
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="restricciones-tab" data-bs-toggle="tab" data-bs-target="#restricciones" type="button">
                        <i class="fas fa-ban me-2"></i>Restricciones
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="sesiones-tab" data-bs-toggle="tab" data-bs-target="#sesiones" type="button">
                        <i class="fas fa-plug me-2"></i>Sesiones
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="auditoria-tab" data-bs-toggle="tab" data-bs-target="#auditoria" type="button">
                        <i class="fas fa-history me-2"></i>Auditoría
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="alertas-tab" data-bs-toggle="tab" data-bs-target="#alertas" type="button">
                        <i class="fas fa-bell me-2"></i>Alertas
                    </button>
                </li>
            </ul>

            <!-- Contenido de las Tabs -->
            <div class="tab-content" id="seguridadTabContent">
                <!-- TAB 1: GESTIÓN DE USUARIOS -->
                <div class="tab-pane fade show active" id="usuarios" role="tabpanel">
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="row align-items-center g-3">
                                <div class="col-md-3">
                                    <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modalCrearUsuario">
                                        <i class="fas fa-user-plus me-2"></i>
                                        Crear Usuario
                                    </button>
                                </div>
                                <div class="col-md-3">
                                    <select class="form-select" id="filterEstadoUsuario">
                                        <option value="">Todos los estados</option>
                                        <option value="activo">Activos</option>
                                        <option value="bloqueado">Bloqueados</option>
                                        <option value="suspendido">Suspendidos</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <select class="form-select" id="filterTipoUsuario">
                                        <option value="">Todos los tipos</option>
                                        <option value="admin_global">Admin Global</option>
                                        <option value="admin">Admin</option>
                                        <option value="usuario">Usuario</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <input type="text" class="form-control" id="searchUsuarios" placeholder="Buscar usuario...">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-users me-2"></i>
                                Lista de Usuarios del Sistema
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover table-security">
                                    <thead>
                                        <tr>
                                            <th>Usuario</th>
                                            <th>Documento</th>
                                            <th>Tipo</th>
                                            <th>Estado</th>
                                            <th>2FA</th>
                                            <th>Sesiones</th>
                                            <th>Último Acceso</th>
                                            <th>Intentos Fallidos</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($usuarios) > 0): ?>
                                            <?php foreach ($usuarios as $usuario): ?>
                                            <tr>
                                                <td>
                                                    <div>
                                                        <div style="font-weight: 600;"><?php echo htmlspecialchars($usuario['nombre_completo']); ?></div>
                                                        <small class="text-muted"><?php echo htmlspecialchars($usuario['email']); ?></small>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($usuario['documento_numero']); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php echo $usuario['tipo_usuario'] === 'admin_global' ? 'critical' : 'info'; ?>">
                                                        <?php echo strtoupper($usuario['tipo_usuario']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-<?php echo $usuario['estado']; ?>">
                                                        <?php echo ucfirst($usuario['estado']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($usuario['two_factor_enabled']): ?>
                                                        <span class="badge badge-activo"><i class="fas fa-check"></i> Activo</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-inactivo"><i class="fas fa-times"></i> Inactivo</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center"><?php echo $usuario['sesiones_activas']; ?></td>
                                                <td><?php echo $usuario['ultimo_acceso'] ? date('d/m/Y H:i', strtotime($usuario['ultimo_acceso'])) : 'Nunca'; ?></td>
                                                <td class="text-center">
                                                    <?php if ($usuario['intentos_fallidos_24h'] > 0): ?>
                                                        <span class="badge badge-critical"><?php echo $usuario['intentos_fallidos_24h']; ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted">0</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-primary action-btn" onclick="verUsuario(<?php echo $usuario['id']; ?>)" title="Ver">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-warning action-btn" onclick="editarUsuario(<?php echo $usuario['id']; ?>)" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-danger action-btn" onclick="bloquearUsuario(<?php echo $usuario['id']; ?>)" title="Bloquear">
                                                        <i class="fas fa-lock"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="9" class="text-center py-5">
                                                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                                    <p class="text-muted mb-0">No hay usuarios registrados</p>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 2: ROLES -->
                <div class="tab-pane fade" id="roles" role="tabpanel">
                    <div class="card mb-4">
                        <div class="card-body">
                            <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modalCrearRol">
                                <i class="fas fa-plus me-2"></i>
                                Crear Rol
                            </button>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0"><i class="fas fa-user-tag me-2"></i>Roles del Sistema</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover table-security">
                                    <thead>
                                        <tr>
                                            <th>Rol</th>
                                            <th>Descripción</th>
                                            <th>Usuarios Asignados</th>
                                            <th>Permisos</th>
                                            <th>Creado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($roles) > 0): ?>
                                            <?php foreach ($roles as $rol): ?>
                                            <tr>
                                                <td style="font-weight: 600;"><?php echo htmlspecialchars($rol['nombre']); ?></td>
                                                <td><?php echo htmlspecialchars($rol['descripcion']); ?></td>
                                                <td class="text-center"><?php echo $rol['total_usuarios']; ?></td>
                                                <td class="text-center"><?php echo $rol['total_permisos']; ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($rol['created_at'])); ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-primary action-btn" title="Ver">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-warning action-btn" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center py-5">
                                                    <i class="fas fa-user-tag fa-3x text-muted mb-3"></i>
                                                    <p class="text-muted mb-0">No hay roles definidos</p>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 3: PERMISOS -->
                <div class="tab-pane fade" id="permisos" role="tabpanel">
                    <div class="card mb-4">
                        <div class="card-body">
                            <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modalCrearPermiso">
                                <i class="fas fa-plus me-2"></i>
                                Crear Permiso
                            </button>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0"><i class="fas fa-key me-2"></i>Permisos del Sistema</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover table-security">
                                    <thead>
                                        <tr>
                                            <th>Permiso</th>
                                            <th>Módulo</th>
                                            <th>Nivel</th>
                                            <th>Riesgo</th>
                                            <th>Log Requerido</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($permisos) > 0): ?>
                                            <?php foreach ($permisos as $permiso): ?>
                                            <tr>
                                                <td style="font-weight: 600;"><?php echo htmlspecialchars($permiso['nombre']); ?></td>
                                                <td><?php echo htmlspecialchars($permiso['modulo']); ?></td>
                                                <td><span class="badge badge-<?php echo $permiso['nivel']; ?>"><?php echo ucfirst($permiso['nivel']); ?></span></td>
                                                <td><span class="badge badge-<?php echo $permiso['nivel_riesgo']; ?>"><?php echo strtoupper($permiso['nivel_riesgo']); ?></span></td>
                                                <td><?php echo $permiso['log_requerido'] ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-muted"></i>'; ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-primary action-btn" title="Ver">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-warning action-btn" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center py-5">
                                                    <i class="fas fa-key fa-3x text-muted mb-3"></i>
                                                    <p class="text-muted mb-0">No hay permisos definidos</p>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 4: POLÍTICAS -->
                <div class="tab-pane fade" id="politicas" role="tabpanel">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0"><i class="fas fa-shield-alt me-2"></i>Políticas de Seguridad</h5>
                        </div>
                        <div class="card-body">
                            <form id="formPoliticas">
                                <div class="form-section">
                                    <div class="form-section-title"><i class="fas fa-lock"></i>Políticas de Contraseña</div>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label">Longitud Mínima</label>
                                            <input type="number" class="form-control" name="password_min_length" value="8">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Días de Expiración</label>
                                            <input type="number" class="form-control" name="password_expiry" value="90">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Intentos Fallidos Máximos</label>
                                            <input type="number" class="form-control" name="max_failed" value="5">
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-danger">
                                    <i class="fas fa-save me-2"></i>Guardar Políticas
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- TAB 5: RESTRICCIONES -->
                <div class="tab-pane fade" id="restricciones" role="tabpanel">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0"><i class="fas fa-ban me-2"></i>IPs Bloqueadas</h5>
                            <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#modalBloquearIP">
                                <i class="fas fa-plus me-2"></i>Bloquear IP
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover table-security">
                                    <thead>
                                        <tr>
                                            <th>IP</th>
                                            <th>Motivo</th>
                                            <th>Fecha</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($ips_bloqueadas) > 0): ?>
                                            <?php foreach ($ips_bloqueadas as $ip): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($ip['ip_address']); ?></td>
                                                <td><?php echo htmlspecialchars($ip['motivo']); ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($ip['created_at'])); ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-success action-btn" title="Desbloquear">
                                                        <i class="fas fa-unlock"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="4" class="text-center text-muted">No hay IPs bloqueadas</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 6: SESIONES -->
                <div class="tab-pane fade" id="sesiones" role="tabpanel">
                    <div class="card mb-4">
                        <div class="card-body">
                            <button class="btn btn-danger" onclick="cerrarTodasSesiones()">
                                <i class="fas fa-power-off me-2"></i>
                                Cerrar Todas las Sesiones
                            </button>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0"><i class="fas fa-plug me-2"></i>Sesiones Activas (<?php echo count($sesiones_activas); ?>)</h5>
                        </div>
                        <div class="card-body">
                            <?php if (count($sesiones_activas) > 0): ?>
                                <?php foreach ($sesiones_activas as $sesion): ?>
                                <div class="session-card">
                                    <div class="session-header">
                                        <div>
                                            <strong><?php echo htmlspecialchars($sesion['nombre_completo']); ?></strong>
                                            <small class="text-muted ms-2"><?php echo htmlspecialchars($sesion['email']); ?></small>
                                        </div>
                                        <button class="btn btn-sm btn-danger" onclick="cerrarSesion('<?php echo $sesion['session_id']; ?>')">
                                            <i class="fas fa-sign-out-alt me-1"></i>Cerrar
                                        </button>
                                    </div>
                                    <div class="row g-2 mt-2">
                                        <div class="col-md-3">
                                            <small class="text-muted">IP:</small>
                                            <div><?php echo htmlspecialchars($sesion['ip_address']); ?></div>
                                        </div>
                                        <div class="col-md-6">
                                            <small class="text-muted">Navegador:</small>
                                            <div><?php echo htmlspecialchars($sesion['user_agent'] ?? 'Desconocido'); ?></div>
                                        </div>
                                        <div class="col-md-3">
                                            <small class="text-muted">Inicio:</small>
                                            <div><?php echo date('d/m/Y H:i', strtotime($sesion['created_at'])); ?></div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-plug fa-3x text-muted mb-3"></i>
                                    <p class="text-muted mb-0">No hay sesiones activas</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- TAB 7: AUDITORÍA -->
                <div class="tab-pane fade" id="auditoria" role="tabpanel">
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <select class="form-select" id="filterSeverity">
                                        <option value="">Todas las severidades</option>
                                        <option value="critical">Critical</option>
                                        <option value="high">High</option>
                                        <option value="medium">Medium</option>
                                        <option value="low">Low</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <input type="date" class="form-control">
                                </div>
                                <div class="col-md-3">
                                    <button class="btn btn-danger w-100" onclick="exportarAuditoria()">
                                        <i class="fas fa-download me-2"></i>Exportar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0"><i class="fas fa-history me-2"></i>Eventos Críticos</h5>
                        </div>
                        <div class="card-body">
                            <?php if (count($eventos_criticos) > 0): ?>
                                <?php foreach ($eventos_criticos as $evento): ?>
                                <div class="audit-log-item">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <strong><?php echo htmlspecialchars($evento['usuario_nombre'] ?? 'Sistema'); ?></strong>
                                            <span class="text-muted ms-2"><?php echo htmlspecialchars($evento['accion']); ?></span>
                                            <div class="mt-1 small text-muted">
                                                IP: <?php echo htmlspecialchars($evento['ip_address']); ?>
                                                <?php if ($evento['detalles']): ?>
                                                    - <?php echo htmlspecialchars($evento['detalles']); ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge badge-<?php echo $evento['severity']; ?>">
                                                <?php echo strtoupper($evento['severity']); ?>
                                            </span>
                                            <div class="text-muted small"><?php echo date('d/m/Y H:i', strtotime($evento['created_at'])); ?></div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-history fa-3x text-muted mb-3"></i>
                                    <p class="text-muted mb-0">No hay eventos críticos</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- TAB 8: ALERTAS -->
                <div class="tab-pane fade" id="alertas" role="tabpanel">
                    <div class="card mb-4">
                        <div class="card-body">
                            <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modalCrearAlerta">
                                <i class="fas fa-plus me-2"></i>
                                Crear Alerta
                            </button>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0"><i class="fas fa-bell me-2"></i>Alertas de Seguridad</h5>
                        </div>
                        <div class="card-body">
                            <?php if (count($alertas_seguridad) > 0): ?>
                                <?php foreach ($alertas_seguridad as $alerta): ?>
                                <div class="security-alert <?php echo $alerta['severity'] === 'critical' ? 'critical' : ''; ?>">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6><i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($alerta['titulo']); ?></h6>
                                            <p><?php echo htmlspecialchars($alerta['descripcion']); ?></p>
                                            <small><?php echo date('d/m/Y H:i', strtotime($alerta['created_at'])); ?></small>
                                        </div>
                                        <div>
                                            <button class="btn btn-sm btn-success">
                                                <i class="fas fa-check me-1"></i>Resolver
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-bell fa-3x text-muted mb-3"></i>
                                    <p class="text-muted mb-0">No hay alertas pendientes</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- MODALES -->

    <!-- MODAL: BLOQUEAR USUARIO -->
    <div class="modal fade" id="modalBloquearUsuario" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-lock me-2"></i>Bloquear Usuario</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formBloquearUsuario">
                        <div class="mb-3">
                            <label class="form-label">Motivo</label>
                            <select class="form-select" name="motivo" required>
                                <option value="actividad_sospechosa">Actividad Sospechosa</option>
                                <option value="intentos_fallidos">Múltiples Intentos Fallidos</option>
                                <option value="violacion_politicas">Violación de Políticas</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Detalles</label>
                            <textarea class="form-control" name="detalles" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" form="formBloquearUsuario" class="btn btn-danger">
                        <i class="fas fa-lock me-2"></i>Bloquear
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL: CREAR ROL -->
    <div class="modal fade" id="modalCrearRol" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-tag me-2"></i>Crear Rol</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formCrearRol">
                        <div class="mb-3">
                            <label class="form-label">Nombre del Rol</label>
                            <input type="text" class="form-control" name="nombre" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Descripción</label>
                            <textarea class="form-control" name="descripcion" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" form="formCrearRol" class="btn btn-danger">
                        <i class="fas fa-save me-2"></i>Crear
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL: CREAR PERMISO -->
    <div class="modal fade" id="modalCrearPermiso" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-key me-2"></i>Crear Permiso</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formCrearPermiso">
                        <div class="mb-3">
                            <label class="form-label">Nombre del Permiso</label>
                            <input type="text" class="form-control" name="nombre" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Módulo</label>
                            <input type="text" class="form-control" name="modulo" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nivel</label>
                            <select class="form-select" name="nivel">
                                <option value="lectura">Lectura</option>
                                <option value="escritura">Escritura</option>
                                <option value="eliminacion">Eliminación</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" form="formCrearPermiso" class="btn btn-danger">
                        <i class="fas fa-save me-2"></i>Crear
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL: BLOQUEAR IP -->
    <div class="modal fade" id="modalBloquearIP" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-ban me-2"></i>Bloquear IP</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formBloquearIP">
                        <div class="mb-3">
                            <label class="form-label">Dirección IP</label>
                            <input type="text" class="form-control" name="ip_address" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Motivo</label>
                            <select class="form-select" name="motivo">
                                <option value="ataque">Intento de Ataque</option>
                                <option value="fuerza_bruta">Fuerza Bruta</option>
                                <option value="spam">Spam</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" form="formBloquearIP" class="btn btn-danger">
                        <i class="fas fa-ban me-2"></i>Bloquear
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL: CREAR ALERTA -->
    <div class="modal fade" id="modalCrearAlerta" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-bell me-2"></i>Crear Alerta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formCrearAlerta">
                        <div class="mb-3">
                            <label class="form-label">Título</label>
                            <input type="text" class="form-control" name="titulo" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Descripción</label>
                            <textarea class="form-control" name="descripcion" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Severidad</label>
                            <select class="form-select" name="severity">
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                                <option value="critical">Critical</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" form="formCrearAlerta" class="btn btn-danger">
                        <i class="fas fa-save me-2"></i>Crear
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL: CREAR USUARIO -->
    <div class="modal fade" id="modalCrearUsuario" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Crear Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formCrearUsuario">
                        <div class="mb-3">
                            <label class="form-label">Nombre Completo</label>
                            <input type="text" class="form-control" name="nombre_completo" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Documento</label>
                            <input type="text" class="form-control" name="documento_numero" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tipo de Usuario</label>
                            <select class="form-select" name="tipo_usuario">
                                <option value="usuario">Usuario</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" form="formCrearUsuario" class="btn btn-danger">
                        <i class="fas fa-save me-2"></i>Crear
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/main.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Fullscreen toggle
            const fullscreenBtn = document.getElementById('fullscreenToggle');
            const icon = fullscreenBtn.querySelector('i');

            fullscreenBtn.addEventListener('click', function(e) {
                e.preventDefault();
                document.body.classList.toggle('fullscreen-mode');

                if (document.body.classList.contains('fullscreen-mode')) {
                    icon.classList.remove('fa-expand');
                    icon.classList.add('fa-compress');
                    fullscreenBtn.title = 'Salir de pantalla completa';
                } else {
                    icon.classList.remove('fa-compress');
                    icon.classList.add('fa-expand');
                    fullscreenBtn.title = 'Pantalla completa';
                }
            });

            // Close fullscreen with ESC key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && document.body.classList.contains('fullscreen-mode')) {
                    document.body.classList.remove('fullscreen-mode');
                    icon.classList.remove('fa-compress');
                    icon.classList.add('fa-expand');
                    fullscreenBtn.title = 'Pantalla completa';
                }
            });

            // Búsqueda de usuarios
            const searchUsuarios = document.getElementById('searchUsuarios');
            if (searchUsuarios) {
                searchUsuarios.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();
                    const rows = document.querySelectorAll('.table-security tbody tr');
                    rows.forEach(row => {
                        const text = row.textContent.toLowerCase();
                        row.style.display = text.includes(searchTerm) ? '' : 'none';
                    });
                });
            }

            // Filtro por estado
            const filterEstado = document.getElementById('filterEstadoUsuario');
            if (filterEstado) {
                filterEstado.addEventListener('change', function() {
                    const estado = this.value;
                    const rows = document.querySelectorAll('.table-security tbody tr');
                    rows.forEach(row => {
                        if (estado === '') {
                            row.style.display = '';
                        } else {
                            const badge = row.querySelector('.badge-' + estado);
                            row.style.display = badge ? '' : 'none';
                        }
                    });
                });
            }

            // Forms submit
            const forms = ['formBloquearUsuario', 'formCrearRol', 'formCrearPermiso', 'formBloquearIP', 'formCrearAlerta', 'formCrearUsuario', 'formPoliticas'];
            forms.forEach(formId => {
                const form = document.getElementById(formId);
                if (form) {
                    form.addEventListener('submit', function(e) {
                        e.preventDefault();
                        const formData = new FormData(this);
                        console.log(`Formulario ${formId}:`, Object.fromEntries(formData));
                        alert('Funcionalidad en desarrollo');
                        const modal = bootstrap.Modal.getInstance(this.closest('.modal'));
                        if (modal) modal.hide();
                    });
                }
            });
        });

        function verUsuario(id) {
            console.log('Ver usuario:', id);
            alert('Ver usuario: ' + id);
        }

        function editarUsuario(id) {
            console.log('Editar usuario:', id);
            alert('Editar usuario: ' + id);
        }

        function bloquearUsuario(id) {
            if (confirm('¿Bloquear este usuario?')) {
                console.log('Bloquear usuario:', id);
                alert('Usuario bloqueado');
            }
        }

        function cerrarSesion(sessionId) {
            if (confirm('¿Cerrar esta sesión?')) {
                console.log('Cerrar sesión:', sessionId);
                alert('Sesión cerrada');
            }
        }

        function cerrarTodasSesiones() {
            if (confirm('¿Cerrar TODAS las sesiones activas?')) {
                console.log('Cerrar todas las sesiones');
                alert('Todas las sesiones cerradas');
            }
        }

        function exportarAuditoria() {
            console.log('Exportar auditoría');
            alert('Exportando auditoría...');
        }
    </script>
</body>
</html>
