<?php
require_once __DIR__ . '/../../config/config.php';

// Verificar si está logueado
if (!is_logged_in()) {
    redirect('/login.php');
}

$user_id = $_SESSION['user_id'];
$user = db_get_row("SELECT * FROM usuarios WHERE id = ?", [$user_id]);

// ========================================
// AUTO-CREAR TABLAS DE PARAMETRIZACIÓN
// ========================================
// NOTA: Tabla paises YA EXISTE - NO se crea ni se insertan datos
try {

    // Tabla: monedas
    db_query("CREATE TABLE IF NOT EXISTS monedas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(100) NOT NULL,
        codigo VARCHAR(3) NOT NULL UNIQUE,
        simbolo VARCHAR(10),
        decimales TINYINT DEFAULT 2,
        redondeo DECIMAL(10,4) DEFAULT 1.0000,
        tipo_cambio DECIMAL(18,4) DEFAULT 1.0000,
        activo TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_codigo (codigo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Tabla: idiomas
    db_query("CREATE TABLE IF NOT EXISTS idiomas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(50) NOT NULL,
        codigo VARCHAR(2) NOT NULL UNIQUE,
        activo TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_codigo (codigo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Tabla: unidades_medida
    db_query("CREATE TABLE IF NOT EXISTS unidades_medida (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(50) NOT NULL,
        codigo VARCHAR(10) NOT NULL UNIQUE,
        tipo ENUM('inventario', 'produccion', 'tiempo', 'contable') DEFAULT 'inventario',
        factor_conversion DECIMAL(18,6) DEFAULT 1.000000,
        activo TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_tipo (tipo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Tabla: impuestos_globales
    db_query("CREATE TABLE IF NOT EXISTS impuestos_globales (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(100) NOT NULL,
        codigo VARCHAR(20) NOT NULL UNIQUE,
        pais_id INT,
        tipo ENUM('iva', 'retencion', 'percepcion', 'especial') DEFAULT 'iva',
        tasa DECIMAL(10,4),
        formula TEXT,
        activo TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (pais_id) REFERENCES paises(id) ON DELETE SET NULL,
        INDEX idx_pais (pais_id),
        INDEX idx_tipo (tipo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Tabla: parametros_contables
    db_query("CREATE TABLE IF NOT EXISTS parametros_contables (
        id INT AUTO_INCREMENT PRIMARY KEY,
        clave VARCHAR(100) NOT NULL UNIQUE,
        valor TEXT,
        descripcion TEXT,
        tipo ENUM('ejercicio', 'periodo', 'plan_cuentas', 'ifrs', 'depreciacion') DEFAULT 'ejercicio',
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_tipo (tipo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Tabla: parametros_facturacion
    db_query("CREATE TABLE IF NOT EXISTS parametros_facturacion (
        id INT AUTO_INCREMENT PRIMARY KEY,
        clave VARCHAR(100) NOT NULL UNIQUE,
        valor TEXT,
        descripcion TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Tabla: parametros_rrhh
    db_query("CREATE TABLE IF NOT EXISTS parametros_rrhh (
        id INT AUTO_INCREMENT PRIMARY KEY,
        clave VARCHAR(100) NOT NULL UNIQUE,
        valor TEXT,
        descripcion TEXT,
        pais_id INT,
        tipo ENUM('afp', 'salud', 'topes', 'horas', 'sueldo_minimo') DEFAULT 'afp',
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (pais_id) REFERENCES paises(id) ON DELETE SET NULL,
        INDEX idx_tipo (tipo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Tabla: parametros_inventario
    db_query("CREATE TABLE IF NOT EXISTS parametros_inventario (
        id INT AUTO_INCREMENT PRIMARY KEY,
        clave VARCHAR(100) NOT NULL UNIQUE,
        valor TEXT,
        descripcion TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Tabla: parametros_produccion
    db_query("CREATE TABLE IF NOT EXISTS parametros_produccion (
        id INT AUTO_INCREMENT PRIMARY KEY,
        clave VARCHAR(100) NOT NULL UNIQUE,
        valor TEXT,
        descripcion TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Tabla: parametros_ventas
    db_query("CREATE TABLE IF NOT EXISTS parametros_ventas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        clave VARCHAR(100) NOT NULL UNIQUE,
        valor TEXT,
        descripcion TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Tabla: integraciones_globales
    db_query("CREATE TABLE IF NOT EXISTS integraciones_globales (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(100) NOT NULL,
        tipo ENUM('sii', 'previred', 'api', 'banco', 'webhook') DEFAULT 'api',
        endpoint TEXT,
        api_key TEXT,
        activo TINYINT(1) DEFAULT 0,
        ultima_sincronizacion TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_tipo (tipo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Tabla: personalizacion_ui
    db_query("CREATE TABLE IF NOT EXISTS personalizacion_ui (
        id INT AUTO_INCREMENT PRIMARY KEY,
        clave VARCHAR(100) NOT NULL UNIQUE,
        valor TEXT,
        tipo ENUM('color', 'logo', 'tema', 'formato') DEFAULT 'color',
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_tipo (tipo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Tabla: configuracion_general
    db_query("CREATE TABLE IF NOT EXISTS configuracion_general (
        id INT AUTO_INCREMENT PRIMARY KEY,
        clave VARCHAR(100) NOT NULL UNIQUE,
        valor TEXT,
        descripcion TEXT,
        tipo ENUM('sistema', 'backup', 'exportacion', 'zona_horaria', 'limites') DEFAULT 'sistema',
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_tipo (tipo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Tabla: actualizaciones_economicas (UF, Dólar, UTM, IPC)
    db_query("CREATE TABLE IF NOT EXISTS actualizaciones_economicas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        indicador VARCHAR(50) NOT NULL,
        valor DECIMAL(18,4),
        fecha DATE NOT NULL,
        pais_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (pais_id) REFERENCES paises(id) ON DELETE CASCADE,
        UNIQUE KEY unique_indicador_fecha (indicador, fecha, pais_id),
        INDEX idx_indicador (indicador),
        INDEX idx_fecha (fecha)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

} catch (Exception $e) {
    error_log("Error creando tablas de parametrización: " . $e->getMessage());
}

// Obtener estadísticas
try {
    $stats = [
        'total_paises' => db_get_var("SELECT COUNT(*) FROM paises WHERE activo = 1") ?? 0,
        'total_monedas' => db_get_var("SELECT COUNT(*) FROM monedas WHERE activo = 1") ?? 0,
        'total_idiomas' => db_get_var("SELECT COUNT(*) FROM idiomas WHERE activo = 1") ?? 0,
        'total_impuestos' => db_get_var("SELECT COUNT(*) FROM impuestos_globales WHERE activo = 1") ?? 0,
        'total_unidades' => db_get_var("SELECT COUNT(*) FROM unidades_medida WHERE activo = 1") ?? 0,
        'integraciones_activas' => db_get_var("SELECT COUNT(*) FROM integraciones_globales WHERE activo = 1") ?? 0
    ];
} catch (Exception $e) {
    $stats = [
        'total_paises' => 0,
        'total_monedas' => 0,
        'total_idiomas' => 0,
        'total_impuestos' => 0,
        'total_unidades' => 0,
        'integraciones_activas' => 0
    ];
}

// Obtener datos
try {
    $paises = db_query("SELECT * FROM paises ORDER BY nombre ASC");
    $monedas = db_query("SELECT * FROM monedas ORDER BY nombre ASC");
    $idiomas = db_query("SELECT * FROM idiomas ORDER BY nombre ASC");
    $unidades_medida = db_query("SELECT * FROM unidades_medida ORDER BY tipo, nombre ASC");
    $impuestos = db_query("SELECT i.*, p.nombre as pais_nombre FROM impuestos_globales i LEFT JOIN paises p ON i.pais_id = p.id ORDER BY i.nombre ASC");
    $integraciones = db_query("SELECT * FROM integraciones_globales ORDER BY nombre ASC");
} catch (Exception $e) {
    $paises = [];
    $monedas = [];
    $idiomas = [];
    $unidades_medida = [];
    $impuestos = [];
    $integraciones = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parametrización Global - CONECTA ERP</title>

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
    background: linear-gradient(135deg, var(--primary-color) 0%, #1e40af 100%);
    color: white;
    border: none;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
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
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
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
    border-left: 4px solid var(--primary-color);
}

.badge-activo { background: #10b981; color: white; }
.badge-inactivo { background: #6b7280; color: white; }

.table-params {
    font-size: 0.9rem;
}
.table-params th {
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
    color: var(--primary-color);
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
                    <i class="fas fa-cogs me-2"></i>
                    Parametrización Global
                </h4>
                <small class="text-muted">Configuración transversal del ERP</small>
            </div>

            <div class="header-right">
                <button class="theme-toggle" id="themeToggle">
                    <i class="fas fa-moon"></i>
                </button>

                <div class="user-menu">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($user['nombre_completo'], 0, 2)); ?>
                    </div>
                    <div class="user-info">
                        <div class="user-name"><?php echo $user['nombre_completo']; ?></div>
                        <div class="user-role">Administrador</div>
                    </div>
                </div>
            </div>
        </header>

        <main class="main-content" id="mainContent">
            <!-- Botón Pantalla Completa -->
            <button class="fullscreen-btn" id="fullscreenToggle" title="Pantalla completa">
                <i class="fas fa-expand"></i>
            </button>

            <!-- Estadísticas -->
            <div class="row g-4 mb-4">
                <div class="col-md-2">
                    <div class="card stat-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <p class="text-muted mb-1" style="font-size: 0.875rem;">Países</p>
                                    <h2 class="mb-0"><?php echo number_format($stats['total_paises']); ?></h2>
                                </div>
                                <div class="feature-icon" style="width: 50px; height: 50px; font-size: 1.25rem;">
                                    <i class="fas fa-globe"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="card stat-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <p class="text-muted mb-1" style="font-size: 0.875rem;">Monedas</p>
                                    <h2 class="mb-0"><?php echo number_format($stats['total_monedas']); ?></h2>
                                </div>
                                <div class="feature-icon" style="width: 50px; height: 50px; font-size: 1.25rem;">
                                    <i class="fas fa-dollar-sign"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="card stat-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <p class="text-muted mb-1" style="font-size: 0.875rem;">Idiomas</p>
                                    <h2 class="mb-0"><?php echo number_format($stats['total_idiomas']); ?></h2>
                                </div>
                                <div class="feature-icon" style="width: 50px; height: 50px; font-size: 1.25rem;">
                                    <i class="fas fa-language"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="card stat-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <p class="text-muted mb-1" style="font-size: 0.875rem;">Impuestos</p>
                                    <h2 class="mb-0"><?php echo number_format($stats['total_impuestos']); ?></h2>
                                </div>
                                <div class="feature-icon" style="width: 50px; height: 50px; font-size: 1.25rem;">
                                    <i class="fas fa-percentage"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="card stat-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <p class="text-muted mb-1" style="font-size: 0.875rem;">Unidades</p>
                                    <h2 class="mb-0"><?php echo number_format($stats['total_unidades']); ?></h2>
                                </div>
                                <div class="feature-icon" style="width: 50px; height: 50px; font-size: 1.25rem;">
                                    <i class="fas fa-ruler"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="card stat-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <p class="text-muted mb-1" style="font-size: 0.875rem;">Integraciones</p>
                                    <h2 class="mb-0"><?php echo number_format($stats['integraciones_activas']); ?></h2>
                                </div>
                                <div class="feature-icon" style="width: 50px; height: 50px; font-size: 1.25rem;">
                                    <i class="fas fa-plug"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs de Navegación -->
            <ul class="nav nav-tabs mb-4" id="parametrosTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="paises-tab" data-bs-toggle="tab" data-bs-target="#paises" type="button">
                        <i class="fas fa-globe me-2"></i>Países
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="monedas-tab" data-bs-toggle="tab" data-bs-target="#monedas" type="button">
                        <i class="fas fa-dollar-sign me-2"></i>Monedas
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="idiomas-tab" data-bs-toggle="tab" data-bs-target="#idiomas" type="button">
                        <i class="fas fa-language me-2"></i>Idiomas
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="unidades-tab" data-bs-toggle="tab" data-bs-target="#unidades" type="button">
                        <i class="fas fa-ruler me-2"></i>Unidades
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="impuestos-tab" data-bs-toggle="tab" data-bs-target="#impuestos" type="button">
                        <i class="fas fa-percentage me-2"></i>Impuestos
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="contables-tab" data-bs-toggle="tab" data-bs-target="#contables" type="button">
                        <i class="fas fa-calculator me-2"></i>Contables
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="facturacion-tab" data-bs-toggle="tab" data-bs-target="#facturacion" type="button">
                        <i class="fas fa-file-invoice me-2"></i>Facturación
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="rrhh-tab" data-bs-toggle="tab" data-bs-target="#rrhh" type="button">
                        <i class="fas fa-users me-2"></i>RRHH
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="inventario-tab" data-bs-toggle="tab" data-bs-target="#inventario" type="button">
                        <i class="fas fa-boxes me-2"></i>Inventario
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="produccion-tab" data-bs-toggle="tab" data-bs-target="#produccion" type="button">
                        <i class="fas fa-industry me-2"></i>Producción
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="ventas-tab" data-bs-toggle="tab" data-bs-target="#ventas" type="button">
                        <i class="fas fa-shopping-cart me-2"></i>Ventas
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="integraciones-tab" data-bs-toggle="tab" data-bs-target="#integraciones" type="button">
                        <i class="fas fa-plug me-2"></i>Integraciones
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="ui-tab" data-bs-toggle="tab" data-bs-target="#ui" type="button">
                        <i class="fas fa-palette me-2"></i>UI
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button">
                        <i class="fas fa-cog me-2"></i>General
                    </button>
                </li>
            </ul>

            <!-- Contenido de las Tabs -->
            <div class="tab-content" id="parametrosTabContent">
                <!-- TAB 1: PAÍSES -->
                <div class="tab-pane fade show active" id="paises" role="tabpanel">
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="row align-items-center g-3">
                                <div class="col-md-4">
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrearPais">
                                        <i class="fas fa-plus me-2"></i>
                                        Agregar País
                                    </button>
                                </div>
                                <div class="col-md-4">
                                    <select class="form-select" id="filterEstadoPais">
                                        <option value="">Todos los estados</option>
                                        <option value="1">Activos</option>
                                        <option value="0">Inactivos</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <input type="text" class="form-control" id="searchPaises" placeholder="Buscar país...">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-globe me-2"></i>
                                Lista de Países
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover table-params">
                                    <thead>
                                        <tr>
                                            <th>País</th>
                                            <th>Código</th>
                                            <th>Tipo Documento</th>
                                            <th>Formato</th>
                                            <th>Moneda</th>
                                            <th>Idioma</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($paises) > 0): ?>
                                            <?php foreach ($paises as $pais): ?>
                                            <tr>
                                                <td style="font-weight: 600;"><?php echo htmlspecialchars($pais['nombre']); ?></td>
                                                <td><?php echo htmlspecialchars($pais['codigo']); ?></td>
                                                <td><?php echo htmlspecialchars($pais['tipo_documento'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($pais['formato_documento'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($pais['moneda'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($pais['idioma_default'] ?? 'N/A'); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php echo $pais['activo'] ? 'activo' : 'inactivo'; ?>">
                                                        <?php echo $pais['activo'] ? 'Activo' : 'Inactivo'; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-primary action-btn" onclick="verPais(<?php echo $pais['id']; ?>)" title="Ver">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-warning action-btn" onclick="editarPais(<?php echo $pais['id']; ?>)" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="8" class="text-center py-5">
                                                    <i class="fas fa-globe fa-3x text-muted mb-3"></i>
                                                    <p class="text-muted mb-0">No hay países registrados</p>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 2: MONEDAS -->
                <div class="tab-pane fade" id="monedas" role="tabpanel">
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="row align-items-center g-3">
                                <div class="col-md-4">
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrearMoneda">
                                        <i class="fas fa-plus me-2"></i>
                                        Agregar Moneda
                                    </button>
                                </div>
                                <div class="col-md-4">
                                    <button class="btn btn-success">
                                        <i class="fas fa-sync me-2"></i>
                                        Actualizar Tipos de Cambio
                                    </button>
                                </div>
                                <div class="col-md-4">
                                    <input type="text" class="form-control" id="searchMonedas" placeholder="Buscar moneda...">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0"><i class="fas fa-dollar-sign me-2"></i>Lista de Monedas</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover table-params">
                                    <thead>
                                        <tr>
                                            <th>Moneda</th>
                                            <th>Código</th>
                                            <th>Símbolo</th>
                                            <th>Decimales</th>
                                            <th>Tipo Cambio</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($monedas) > 0): ?>
                                            <?php foreach ($monedas as $moneda): ?>
                                            <tr>
                                                <td style="font-weight: 600;"><?php echo htmlspecialchars($moneda['nombre']); ?></td>
                                                <td><?php echo htmlspecialchars($moneda['codigo']); ?></td>
                                                <td><?php echo htmlspecialchars($moneda['simbolo']); ?></td>
                                                <td><?php echo $moneda['decimales']; ?></td>
                                                <td><?php echo number_format($moneda['tipo_cambio'], 4); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php echo $moneda['activo'] ? 'activo' : 'inactivo'; ?>">
                                                        <?php echo $moneda['activo'] ? 'Activo' : 'Inactivo'; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-primary action-btn" onclick="verMoneda(<?php echo $moneda['id']; ?>)" title="Ver">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-warning action-btn" onclick="editarMoneda(<?php echo $moneda['id']; ?>)" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="7" class="text-center py-5">
                                                    <i class="fas fa-dollar-sign fa-3x text-muted mb-3"></i>
                                                    <p class="text-muted mb-0">No hay monedas registradas</p>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 3: IDIOMAS -->
                <div class="tab-pane fade" id="idiomas" role="tabpanel">
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="row align-items-center g-3">
                                <div class="col-md-6">
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrearIdioma">
                                        <i class="fas fa-plus me-2"></i>
                                        Agregar Idioma
                                    </button>
                                </div>
                                <div class="col-md-6">
                                    <input type="text" class="form-control" id="searchIdiomas" placeholder="Buscar idioma...">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0"><i class="fas fa-language me-2"></i>Lista de Idiomas</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover table-params">
                                    <thead>
                                        <tr>
                                            <th>Idioma</th>
                                            <th>Código</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($idiomas) > 0): ?>
                                            <?php foreach ($idiomas as $idioma): ?>
                                            <tr>
                                                <td style="font-weight: 600;"><?php echo htmlspecialchars($idioma['nombre']); ?></td>
                                                <td><?php echo htmlspecialchars($idioma['codigo']); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php echo $idioma['activo'] ? 'activo' : 'inactivo'; ?>">
                                                        <?php echo $idioma['activo'] ? 'Activo' : 'Inactivo'; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-primary action-btn" onclick="verIdioma(<?php echo $idioma['id']; ?>)" title="Ver">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-warning action-btn" onclick="editarIdioma(<?php echo $idioma['id']; ?>)" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="4" class="text-center py-5">
                                                    <i class="fas fa-language fa-3x text-muted mb-3"></i>
                                                    <p class="text-muted mb-0">No hay idiomas registrados</p>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 4: UNIDADES DE MEDIDA -->
                <div class="tab-pane fade" id="unidades" role="tabpanel">
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="row align-items-center g-3">
                                <div class="col-md-4">
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrearUnidad">
                                        <i class="fas fa-plus me-2"></i>
                                        Agregar Unidad
                                    </button>
                                </div>
                                <div class="col-md-4">
                                    <select class="form-select" id="filterTipoUnidad">
                                        <option value="">Todos los tipos</option>
                                        <option value="inventario">Inventario</option>
                                        <option value="produccion">Producción</option>
                                        <option value="tiempo">Tiempo</option>
                                        <option value="contable">Contable</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <input type="text" class="form-control" id="searchUnidades" placeholder="Buscar unidad...">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0"><i class="fas fa-ruler me-2"></i>Unidades de Medida</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover table-params">
                                    <thead>
                                        <tr>
                                            <th>Unidad</th>
                                            <th>Código</th>
                                            <th>Tipo</th>
                                            <th>Factor Conversión</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($unidades_medida) > 0): ?>
                                            <?php foreach ($unidades_medida as $unidad): ?>
                                            <tr>
                                                <td style="font-weight: 600;"><?php echo htmlspecialchars($unidad['nombre']); ?></td>
                                                <td><?php echo htmlspecialchars($unidad['codigo']); ?></td>
                                                <td><?php echo ucfirst($unidad['tipo']); ?></td>
                                                <td><?php echo number_format($unidad['factor_conversion'], 6); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php echo $unidad['activo'] ? 'activo' : 'inactivo'; ?>">
                                                        <?php echo $unidad['activo'] ? 'Activo' : 'Inactivo'; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-primary action-btn" onclick="verUnidad(<?php echo $unidad['id']; ?>)" title="Ver">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-warning action-btn" onclick="editarUnidad(<?php echo $unidad['id']; ?>)" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center py-5">
                                                    <i class="fas fa-ruler fa-3x text-muted mb-3"></i>
                                                    <p class="text-muted mb-0">No hay unidades de medida registradas</p>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 5: IMPUESTOS GLOBALES -->
                <div class="tab-pane fade" id="impuestos" role="tabpanel">
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="row align-items-center g-3">
                                <div class="col-md-4">
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrearImpuesto">
                                        <i class="fas fa-plus me-2"></i>
                                        Agregar Impuesto
                                    </button>
                                </div>
                                <div class="col-md-4">
                                    <select class="form-select" id="filterTipoImpuesto">
                                        <option value="">Todos los tipos</option>
                                        <option value="iva">IVA</option>
                                        <option value="retencion">Retención</option>
                                        <option value="percepcion">Percepción</option>
                                        <option value="especial">Especial</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <input type="text" class="form-control" id="searchImpuestos" placeholder="Buscar impuesto...">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0"><i class="fas fa-percentage me-2"></i>Impuestos Globales</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover table-params">
                                    <thead>
                                        <tr>
                                            <th>Impuesto</th>
                                            <th>Código</th>
                                            <th>País</th>
                                            <th>Tipo</th>
                                            <th>Tasa</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($impuestos) > 0): ?>
                                            <?php foreach ($impuestos as $impuesto): ?>
                                            <tr>
                                                <td style="font-weight: 600;"><?php echo htmlspecialchars($impuesto['nombre']); ?></td>
                                                <td><?php echo htmlspecialchars($impuesto['codigo']); ?></td>
                                                <td><?php echo htmlspecialchars($impuesto['pais_nombre'] ?? 'Global'); ?></td>
                                                <td><?php echo ucfirst($impuesto['tipo']); ?></td>
                                                <td><?php echo $impuesto['tasa'] ? number_format($impuesto['tasa'], 2) . '%' : 'N/A'; ?></td>
                                                <td>
                                                    <span class="badge badge-<?php echo $impuesto['activo'] ? 'activo' : 'inactivo'; ?>">
                                                        <?php echo $impuesto['activo'] ? 'Activo' : 'Inactivo'; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-primary action-btn" onclick="verImpuesto(<?php echo $impuesto['id']; ?>)" title="Ver">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-warning action-btn" onclick="editarImpuesto(<?php echo $impuesto['id']; ?>)" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="7" class="text-center py-5">
                                                    <i class="fas fa-percentage fa-3x text-muted mb-3"></i>
                                                    <p class="text-muted mb-0">No hay impuestos registrados</p>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 6-14: Resto de secciones (simplificadas por espacio) -->
                <div class="tab-pane fade" id="contables" role="tabpanel">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0"><i class="fas fa-calculator me-2"></i>Parámetros Contables</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">Configuración de ejercicio contable, períodos, plan de cuentas, IFRS y depreciación.</p>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="facturacion" role="tabpanel">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0"><i class="fas fa-file-invoice me-2"></i>Parámetros de Facturación</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">Series internas, correlativos no fiscales y formatos de impresión.</p>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="rrhh" role="tabpanel">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0"><i class="fas fa-users me-2"></i>Parámetros de RRHH</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">Tablas AFP, salud, topes imponibles, sueldo mínimo y cálculos de remuneraciones.</p>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="inventario" role="tabpanel">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0"><i class="fas fa-boxes me-2"></i>Parámetros de Inventario</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">Costeo (FIFO, LIFO, PMP), trazabilidad, lotes y alertas de stock.</p>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="produccion" role="tabpanel">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0"><i class="fas fa-industry me-2"></i>Parámetros de Producción</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">Fórmulas de producción, rendimientos, scrap y tiempos estándar.</p>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="ventas" role="tabpanel">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0"><i class="fas fa-shopping-cart me-2"></i>Parámetros de Ventas</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">Políticas de descuento, límites por vendedor y configuración de POS.</p>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="integraciones" role="tabpanel">
                    <div class="card mb-4">
                        <div class="card-body">
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrearIntegracion">
                                <i class="fas fa-plus me-2"></i>
                                Agregar Integración
                            </button>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0"><i class="fas fa-plug me-2"></i>Integraciones Globales</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover table-params">
                                    <thead>
                                        <tr>
                                            <th>Integración</th>
                                            <th>Tipo</th>
                                            <th>Estado</th>
                                            <th>Última Sincronización</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($integraciones) > 0): ?>
                                            <?php foreach ($integraciones as $integracion): ?>
                                            <tr>
                                                <td style="font-weight: 600;"><?php echo htmlspecialchars($integracion['nombre']); ?></td>
                                                <td><?php echo strtoupper($integracion['tipo']); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php echo $integracion['activo'] ? 'activo' : 'inactivo'; ?>">
                                                        <?php echo $integracion['activo'] ? 'Activo' : 'Inactivo'; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo $integracion['ultima_sincronizacion'] ? date('d/m/Y H:i', strtotime($integracion['ultima_sincronizacion'])) : 'Nunca'; ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-success action-btn" onclick="probarIntegracion(<?php echo $integracion['id']; ?>)" title="Probar">
                                                        <i class="fas fa-plug"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-warning action-btn" onclick="editarIntegracion(<?php echo $integracion['id']; ?>)" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" class="text-center py-5">
                                                    <i class="fas fa-plug fa-3x text-muted mb-3"></i>
                                                    <p class="text-muted mb-0">No hay integraciones configuradas</p>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="ui" role="tabpanel">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0"><i class="fas fa-palette me-2"></i>Personalización UI</h5>
                        </div>
                        <div class="card-body">
                            <form>
                                <div class="form-section">
                                    <div class="form-section-title"><i class="fas fa-image"></i>Logo Global</div>
                                    <div class="row g-3">
                                        <div class="col-md-12">
                                            <input type="file" class="form-control" accept="image/*">
                                        </div>
                                    </div>
                                </div>
                                <div class="form-section">
                                    <div class="form-section-title"><i class="fas fa-palette"></i>Colores</div>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label">Color Primario</label>
                                            <input type="color" class="form-control" value="#2563eb">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Color Secundario</label>
                                            <input type="color" class="form-control" value="#64748b">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Tema</label>
                                            <select class="form-select">
                                                <option value="light">Claro</option>
                                                <option value="dark">Oscuro</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Guardar Personalización
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="general" role="tabpanel">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0"><i class="fas fa-cog me-2"></i>Configuración General</h5>
                        </div>
                        <div class="card-body">
                            <form>
                                <div class="form-section">
                                    <div class="form-section-title"><i class="fas fa-clock"></i>Zona Horaria</div>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Zona Horaria Global</label>
                                            <select class="form-select">
                                                <option value="America/Santiago">Santiago (UTC-3)</option>
                                                <option value="America/Buenos_Aires">Buenos Aires (UTC-3)</option>
                                                <option value="America/Mexico_City">México (UTC-6)</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Formato de Fecha</label>
                                            <select class="form-select">
                                                <option value="d/m/Y">DD/MM/AAAA</option>
                                                <option value="m/d/Y">MM/DD/AAAA</option>
                                                <option value="Y-m-d">AAAA-MM-DD</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Guardar Configuración
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- MODALES -->

    <!-- MODAL: CREAR PAÍS -->
    <div class="modal fade" id="modalCrearPais" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-globe me-2"></i>Agregar País</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formCrearPais">
                        <div class="mb-3">
                            <label class="form-label">Nombre del País</label>
                            <input type="text" class="form-control" name="nombre" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Código (ISO)</label>
                            <input type="text" class="form-control" name="codigo" maxlength="3" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Documento Principal</label>
                            <input type="text" class="form-control" name="documento_principal" placeholder="RUT, DNI, RFC, etc.">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" form="formCrearPais" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Guardar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL: CREAR MONEDA -->
    <div class="modal fade" id="modalCrearMoneda" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-dollar-sign me-2"></i>Agregar Moneda</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formCrearMoneda">
                        <div class="mb-3">
                            <label class="form-label">Nombre de la Moneda</label>
                            <input type="text" class="form-control" name="nombre" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Código (ISO)</label>
                            <input type="text" class="form-control" name="codigo" maxlength="3" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Símbolo</label>
                            <input type="text" class="form-control" name="simbolo">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Decimales</label>
                            <input type="number" class="form-control" name="decimales" value="2">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" form="formCrearMoneda" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Guardar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL: CREAR IDIOMA -->
    <div class="modal fade" id="modalCrearIdioma" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-language me-2"></i>Agregar Idioma</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formCrearIdioma">
                        <div class="mb-3">
                            <label class="form-label">Nombre del Idioma</label>
                            <input type="text" class="form-control" name="nombre" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Código (ISO)</label>
                            <input type="text" class="form-control" name="codigo" maxlength="2" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" form="formCrearIdioma" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Guardar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL: CREAR UNIDAD -->
    <div class="modal fade" id="modalCrearUnidad" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-ruler me-2"></i>Agregar Unidad de Medida</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formCrearUnidad">
                        <div class="mb-3">
                            <label class="form-label">Nombre de la Unidad</label>
                            <input type="text" class="form-control" name="nombre" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Código</label>
                            <input type="text" class="form-control" name="codigo" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tipo</label>
                            <select class="form-select" name="tipo">
                                <option value="inventario">Inventario</option>
                                <option value="produccion">Producción</option>
                                <option value="tiempo">Tiempo</option>
                                <option value="contable">Contable</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" form="formCrearUnidad" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Guardar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL: CREAR IMPUESTO -->
    <div class="modal fade" id="modalCrearImpuesto" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-percentage me-2"></i>Agregar Impuesto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formCrearImpuesto">
                        <div class="mb-3">
                            <label class="form-label">Nombre del Impuesto</label>
                            <input type="text" class="form-control" name="nombre" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Código</label>
                            <input type="text" class="form-control" name="codigo" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tipo</label>
                            <select class="form-select" name="tipo">
                                <option value="iva">IVA</option>
                                <option value="retencion">Retención</option>
                                <option value="percepcion">Percepción</option>
                                <option value="especial">Especial</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tasa (%)</label>
                            <input type="number" step="0.01" class="form-control" name="tasa">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" form="formCrearImpuesto" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Guardar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL: CREAR INTEGRACIÓN -->
    <div class="modal fade" id="modalCrearIntegracion" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plug me-2"></i>Agregar Integración</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formCrearIntegracion">
                        <div class="mb-3">
                            <label class="form-label">Nombre de la Integración</label>
                            <input type="text" class="form-control" name="nombre" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tipo</label>
                            <select class="form-select" name="tipo">
                                <option value="sii">SII</option>
                                <option value="previred">Previred</option>
                                <option value="api">API Externa</option>
                                <option value="banco">Banco</option>
                                <option value="webhook">Webhook</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Endpoint</label>
                            <input type="url" class="form-control" name="endpoint">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">API Key</label>
                            <input type="text" class="form-control" name="api_key">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" form="formCrearIntegracion" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Guardar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL: VER PAÍS -->
    <div class="modal fade" id="modalVerPais" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-globe me-2"></i>Detalles del País</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="info-item">
                                <label>País</label>
                                <div class="value" id="detallePaisNombre"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <label>Código ISO</label>
                                <div class="value" id="detallePaisCodigo"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <label>Tipo de Documento</label>
                                <div class="value" id="detallePaisTipoDoc"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <label>Formato de Documento</label>
                                <div class="value" id="detallePaisFormatoDoc"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <label>Moneda</label>
                                <div class="value" id="detallePaisMoneda"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <label>Idioma por Defecto</label>
                                <div class="value" id="detallePaisIdioma"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <label>Código Telefónico</label>
                                <div class="value" id="detallePaisTelefono"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <label>Estado</label>
                                <div class="value" id="detallePaisEstado"></div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="info-item">
                                <label>Validación Regex</label>
                                <div class="value" id="detallePaisRegex" style="font-family: monospace; font-size: 0.875rem;"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/main.js"></script>
    <script>
        // Datos de países para modales
        const paisesData = <?php echo json_encode($paises); ?>;

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
                } else {
                    icon.classList.remove('fa-compress');
                    icon.classList.add('fa-expand');
                }
            });

            // ESC key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && document.body.classList.contains('fullscreen-mode')) {
                    document.body.classList.remove('fullscreen-mode');
                    icon.classList.remove('fa-compress');
                    icon.classList.add('fa-expand');
                }
            });

            // Búsqueda
            const searches = ['searchPaises', 'searchMonedas', 'searchIdiomas', 'searchUnidades', 'searchImpuestos'];
            searches.forEach(searchId => {
                const input = document.getElementById(searchId);
                if (input) {
                    input.addEventListener('input', function() {
                        const searchTerm = this.value.toLowerCase();
                        const rows = this.closest('.card').querySelectorAll('tbody tr');
                        rows.forEach(row => {
                            const text = row.textContent.toLowerCase();
                            row.style.display = text.includes(searchTerm) ? '' : 'none';
                        });
                    });
                }
            });

            // Forms
            const forms = ['formCrearPais', 'formCrearMoneda', 'formCrearIdioma', 'formCrearUnidad', 'formCrearImpuesto', 'formCrearIntegracion'];
            forms.forEach(formId => {
                const form = document.getElementById(formId);
                if (form) {
                    form.addEventListener('submit', function(e) {
                        e.preventDefault();
                        const formData = new FormData(this);
                        console.log(`${formId}:`, Object.fromEntries(formData));
                        alert('Funcionalidad en desarrollo');
                        const modal = bootstrap.Modal.getInstance(this.closest('.modal'));
                        if (modal) modal.hide();
                    });
                }
            });

            // Filtros
            const filters = ['filterEstadoPais', 'filterTipoUnidad', 'filterTipoImpuesto'];
            filters.forEach(filterId => {
                const select = document.getElementById(filterId);
                if (select) {
                    select.addEventListener('change', function() {
                        console.log(`Filtro ${filterId}:`, this.value);
                    });
                }
            });
        });

        // Funciones para países
        function verPais(id) {
            const pais = paisesData.find(p => p.id == id);
            if (pais) {
                document.getElementById('detallePaisNombre').textContent = pais.nombre || 'N/A';
                document.getElementById('detallePaisCodigo').textContent = pais.codigo || 'N/A';
                document.getElementById('detallePaisTipoDoc').textContent = pais.tipo_documento || 'N/A';
                document.getElementById('detallePaisFormatoDoc').textContent = pais.formato_documento || 'N/A';
                document.getElementById('detallePaisMoneda').textContent = pais.moneda || 'N/A';
                document.getElementById('detallePaisIdioma').textContent = pais.idioma_default || 'N/A';
                document.getElementById('detallePaisTelefono').textContent = pais.codigo_telefono || 'N/A';
                document.getElementById('detallePaisEstado').innerHTML = pais.activo ?
                    '<span class="badge badge-activo">Activo</span>' :
                    '<span class="badge badge-inactivo">Inactivo</span>';
                document.getElementById('detallePaisRegex').textContent = pais.validacion_regex || 'N/A';

                const modal = new bootstrap.Modal(document.getElementById('modalVerPais'));
                modal.show();
            }
        }

        function editarPais(id) {
            const pais = paisesData.find(p => p.id == id);
            if (pais) {
                alert('Funcionalidad de edición en desarrollo para: ' + pais.nombre);
            }
        }

        // Funciones para monedas
        function verMoneda(id) {
            console.log('Ver moneda:', id);
            alert('Ver moneda ID: ' + id);
        }

        function editarMoneda(id) {
            console.log('Editar moneda:', id);
            alert('Editar moneda ID: ' + id);
        }

        // Funciones para idiomas
        function verIdioma(id) {
            console.log('Ver idioma:', id);
            alert('Ver idioma ID: ' + id);
        }

        function editarIdioma(id) {
            console.log('Editar idioma:', id);
            alert('Editar idioma ID: ' + id);
        }

        // Funciones para unidades
        function verUnidad(id) {
            console.log('Ver unidad:', id);
            alert('Ver unidad ID: ' + id);
        }

        function editarUnidad(id) {
            console.log('Editar unidad:', id);
            alert('Editar unidad ID: ' + id);
        }

        // Funciones para impuestos
        function verImpuesto(id) {
            console.log('Ver impuesto:', id);
            alert('Ver impuesto ID: ' + id);
        }

        function editarImpuesto(id) {
            console.log('Editar impuesto:', id);
            alert('Editar impuesto ID: ' + id);
        }

        // Funciones para integraciones
        function verIntegracion(id) {
            console.log('Ver integración:', id);
            alert('Ver integración ID: ' + id);
        }

        function editarIntegracion(id) {
            console.log('Editar integración:', id);
            alert('Editar integración ID: ' + id);
        }

        function probarIntegracion(id) {
            if (confirm('¿Probar conexión de esta integración?')) {
                console.log('Probar integración:', id);
                alert('Probando integración...');
            }
        }
    </script>
</body>
</html>
