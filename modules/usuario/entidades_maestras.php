<?php
require_once __DIR__ . '/../../config/config.php';

// Verificar si está logueado
if (!is_logged_in()) {
    redirect('/login.php');
}

$user_id = $_SESSION['user_id'];
$user = db_get_row("SELECT * FROM usuarios WHERE id = ?", [$user_id]);

// Obtener empresa del usuario
$empresa_id = $user['empresa_id'] ?? null;

// ========================================
// AUTO-CREAR TABLAS DE ENTIDADES MAESTRAS
// ========================================
try {
    // Tabla: Auditoría de entidades maestras
    db_query("CREATE TABLE IF NOT EXISTS ma_auditoria (
        id INT AUTO_INCREMENT PRIMARY KEY,
        empresa_id INT NOT NULL,
        usuario_id INT NOT NULL,
        entidad VARCHAR(100) NOT NULL,
        entidad_id INT NOT NULL,
        accion ENUM('crear', 'editar', 'eliminar', 'desactivar', 'activar') NOT NULL,
        campo VARCHAR(100),
        valor_anterior TEXT,
        valor_nuevo TEXT,
        ip VARCHAR(45),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_empresa (empresa_id),
        INDEX idx_entidad (entidad, entidad_id),
        INDEX idx_usuario (usuario_id),
        INDEX idx_fecha (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Tabla: MA_CLIENTES
    db_query("CREATE TABLE IF NOT EXISTS ma_clientes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        empresa_id INT NOT NULL,
        codigo VARCHAR(50),
        razon_social VARCHAR(255) NOT NULL,
        nombre_comercial VARCHAR(255),
        documento VARCHAR(50) NOT NULL,
        tipo_documento VARCHAR(20),
        pais_id INT,
        giro VARCHAR(255),
        actividad_economica VARCHAR(255),
        direccion_fiscal TEXT,
        direccion_comercial TEXT,
        ciudad VARCHAR(100),
        region VARCHAR(100),
        telefono VARCHAR(50),
        email VARCHAR(255),
        sitio_web VARCHAR(255),
        lista_precios_id INT,
        limite_credito DECIMAL(18,2) DEFAULT 0,
        dias_credito INT DEFAULT 0,
        descuento_global DECIMAL(5,2) DEFAULT 0,
        clasificacion_abc ENUM('A', 'B', 'C', 'N/A') DEFAULT 'N/A',
        segmento VARCHAR(50),
        observaciones TEXT,
        activo TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        created_by INT,
        FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
        FOREIGN KEY (pais_id) REFERENCES paises(id) ON DELETE SET NULL,
        UNIQUE KEY unique_documento_empresa (documento, empresa_id),
        INDEX idx_empresa (empresa_id),
        INDEX idx_codigo (codigo),
        INDEX idx_clasificacion (clasificacion_abc),
        INDEX idx_activo (activo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Tabla: MA_PROVEEDORES
    db_query("CREATE TABLE IF NOT EXISTS ma_proveedores (
        id INT AUTO_INCREMENT PRIMARY KEY,
        empresa_id INT NOT NULL,
        codigo VARCHAR(50),
        razon_social VARCHAR(255) NOT NULL,
        nombre_comercial VARCHAR(255),
        documento VARCHAR(50) NOT NULL,
        tipo_documento VARCHAR(20),
        pais_id INT,
        giro VARCHAR(255),
        actividad_economica VARCHAR(255),
        rubro VARCHAR(100),
        direccion TEXT,
        ciudad VARCHAR(100),
        region VARCHAR(100),
        telefono VARCHAR(50),
        email VARCHAR(255),
        sitio_web VARCHAR(255),
        dias_credito INT DEFAULT 0,
        descuento_volumen DECIMAL(5,2) DEFAULT 0,
        calificacion DECIMAL(3,1) DEFAULT 0,
        banco VARCHAR(100),
        tipo_cuenta VARCHAR(50),
        numero_cuenta VARCHAR(100),
        observaciones TEXT,
        activo TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        created_by INT,
        FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
        FOREIGN KEY (pais_id) REFERENCES paises(id) ON DELETE SET NULL,
        UNIQUE KEY unique_documento_empresa (documento, empresa_id),
        INDEX idx_empresa (empresa_id),
        INDEX idx_rubro (rubro),
        INDEX idx_activo (activo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Tabla: MA_PRODUCTOS
    db_query("CREATE TABLE IF NOT EXISTS ma_productos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        empresa_id INT NOT NULL,
        codigo VARCHAR(100) NOT NULL,
        codigo_barras VARCHAR(100),
        sku VARCHAR(100),
        nombre VARCHAR(255) NOT NULL,
        descripcion TEXT,
        categoria_id INT,
        subcategoria_id INT,
        marca VARCHAR(100),
        unidad_medida VARCHAR(50),
        tipo_item ENUM('bien', 'servicio', 'insumo', 'producto_terminado', 'kit') DEFAULT 'bien',
        costo_estandar DECIMAL(18,4) DEFAULT 0,
        costo_reposicion DECIMAL(18,4) DEFAULT 0,
        costo_promedio DECIMAL(18,4) DEFAULT 0,
        precio_venta DECIMAL(18,4) DEFAULT 0,
        stock_minimo DECIMAL(18,4) DEFAULT 0,
        stock_maximo DECIMAL(18,4) DEFAULT 0,
        stock_actual DECIMAL(18,4) DEFAULT 0,
        iva_aplicable TINYINT(1) DEFAULT 1,
        maneja_lote TINYINT(1) DEFAULT 0,
        maneja_serie TINYINT(1) DEFAULT 0,
        observaciones TEXT,
        activo TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        created_by INT,
        FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
        UNIQUE KEY unique_codigo_empresa (codigo, empresa_id),
        INDEX idx_empresa (empresa_id),
        INDEX idx_categoria (categoria_id),
        INDEX idx_tipo (tipo_item),
        INDEX idx_activo (activo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Tabla: MA_SERVICIOS
    db_query("CREATE TABLE IF NOT EXISTS ma_servicios (
        id INT AUTO_INCREMENT PRIMARY KEY,
        empresa_id INT NOT NULL,
        codigo VARCHAR(100) NOT NULL,
        nombre VARCHAR(255) NOT NULL,
        descripcion TEXT,
        tipo_servicio VARCHAR(100),
        horas_estimadas DECIMAL(10,2),
        tarifa_hora DECIMAL(18,4),
        tarifa_fija DECIMAL(18,4),
        unidad_medida VARCHAR(50),
        iva_aplicable TINYINT(1) DEFAULT 1,
        responsable_id INT,
        observaciones TEXT,
        activo TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        created_by INT,
        FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
        UNIQUE KEY unique_codigo_empresa (codigo, empresa_id),
        INDEX idx_empresa (empresa_id),
        INDEX idx_tipo (tipo_servicio),
        INDEX idx_activo (activo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Tabla: MA_EMPLEADOS
    db_query("CREATE TABLE IF NOT EXISTS ma_empleados (
        id INT AUTO_INCREMENT PRIMARY KEY,
        empresa_id INT NOT NULL,
        codigo VARCHAR(50),
        documento VARCHAR(50) NOT NULL,
        tipo_documento VARCHAR(20),
        pais_id INT,
        nombres VARCHAR(255) NOT NULL,
        apellidos VARCHAR(255) NOT NULL,
        fecha_nacimiento DATE,
        direccion TEXT,
        ciudad VARCHAR(100),
        telefono VARCHAR(50),
        email VARCHAR(255),
        cargo VARCHAR(100),
        centro_costo_id INT,
        fecha_ingreso DATE,
        fecha_termino DATE,
        tipo_contrato VARCHAR(50),
        sueldo_base DECIMAL(18,2),
        horario VARCHAR(100),
        afp VARCHAR(100),
        salud VARCHAR(100),
        banco VARCHAR(100),
        tipo_cuenta VARCHAR(50),
        numero_cuenta VARCHAR(100),
        observaciones TEXT,
        activo TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        created_by INT,
        FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
        FOREIGN KEY (pais_id) REFERENCES paises(id) ON DELETE SET NULL,
        UNIQUE KEY unique_documento_empresa (documento, empresa_id),
        INDEX idx_empresa (empresa_id),
        INDEX idx_cargo (cargo),
        INDEX idx_activo (activo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Tabla: MA_BANCOS
    db_query("CREATE TABLE IF NOT EXISTS ma_bancos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        empresa_id INT NOT NULL,
        nombre VARCHAR(255) NOT NULL,
        codigo VARCHAR(50),
        pais_id INT,
        tipo_cuenta VARCHAR(50),
        numero_cuenta VARCHAR(100),
        saldo_actual DECIMAL(18,2) DEFAULT 0,
        formato_pago VARCHAR(100),
        api_integracion VARCHAR(255),
        observaciones TEXT,
        activo TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        created_by INT,
        FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
        FOREIGN KEY (pais_id) REFERENCES paises(id) ON DELETE SET NULL,
        INDEX idx_empresa (empresa_id),
        INDEX idx_activo (activo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Tabla: MA_CENTROS_COSTO
    db_query("CREATE TABLE IF NOT EXISTS ma_centros_costo (
        id INT AUTO_INCREMENT PRIMARY KEY,
        empresa_id INT NOT NULL,
        codigo VARCHAR(50) NOT NULL,
        nombre VARCHAR(255) NOT NULL,
        descripcion TEXT,
        centro_padre_id INT,
        responsable_id INT,
        area VARCHAR(100),
        proyecto_id INT,
        activo TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        created_by INT,
        FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
        FOREIGN KEY (centro_padre_id) REFERENCES ma_centros_costo(id) ON DELETE SET NULL,
        UNIQUE KEY unique_codigo_empresa (codigo, empresa_id),
        INDEX idx_empresa (empresa_id),
        INDEX idx_activo (activo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Tabla: MA_BODEGAS
    db_query("CREATE TABLE IF NOT EXISTS ma_bodegas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        empresa_id INT NOT NULL,
        codigo VARCHAR(50) NOT NULL,
        nombre VARCHAR(255) NOT NULL,
        direccion TEXT,
        ciudad VARCHAR(100),
        tipo ENUM('principal', 'secundaria', 'produccion', 'suministros') DEFAULT 'secundaria',
        responsable_id INT,
        capacidad DECIMAL(18,2),
        observaciones TEXT,
        activo TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        created_by INT,
        FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
        UNIQUE KEY unique_codigo_empresa (codigo, empresa_id),
        INDEX idx_empresa (empresa_id),
        INDEX idx_tipo (tipo),
        INDEX idx_activo (activo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Tablas suplementarias
    db_query("CREATE TABLE IF NOT EXISTS ma_categorias_productos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        empresa_id INT NOT NULL,
        nombre VARCHAR(100) NOT NULL,
        descripcion TEXT,
        activo TINYINT(1) DEFAULT 1,
        FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
        INDEX idx_empresa (empresa_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    db_query("CREATE TABLE IF NOT EXISTS ma_archivos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        empresa_id INT NOT NULL,
        entidad VARCHAR(100) NOT NULL,
        entidad_id INT NOT NULL,
        nombre_archivo VARCHAR(255) NOT NULL,
        ruta_archivo VARCHAR(500) NOT NULL,
        tipo_archivo VARCHAR(50),
        tamanio INT,
        descripcion TEXT,
        uploaded_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
        INDEX idx_entidad (entidad, entidad_id),
        INDEX idx_empresa (empresa_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

} catch (Exception $e) {
    error_log("Error creando tablas de entidades maestras: " . $e->getMessage());
}

// Obtener estadísticas
try {
    $where_empresa = $empresa_id ? "empresa_id = " . intval($empresa_id) : "1=1";

    $stats = [
        'total_clientes' => db_get_var("SELECT COUNT(*) FROM ma_clientes WHERE $where_empresa AND activo = 1") ?? 0,
        'total_proveedores' => db_get_var("SELECT COUNT(*) FROM ma_proveedores WHERE $where_empresa AND activo = 1") ?? 0,
        'total_productos' => db_get_var("SELECT COUNT(*) FROM ma_productos WHERE $where_empresa AND activo = 1") ?? 0,
        'total_servicios' => db_get_var("SELECT COUNT(*) FROM ma_servicios WHERE $where_empresa AND activo = 1") ?? 0,
        'total_empleados' => db_get_var("SELECT COUNT(*) FROM ma_empleados WHERE $where_empresa AND activo = 1") ?? 0,
        'total_bancos' => db_get_var("SELECT COUNT(*) FROM ma_bancos WHERE $where_empresa AND activo = 1") ?? 0,
        'total_centros_costo' => db_get_var("SELECT COUNT(*) FROM ma_centros_costo WHERE $where_empresa AND activo = 1") ?? 0,
        'total_bodegas' => db_get_var("SELECT COUNT(*) FROM ma_bodegas WHERE $where_empresa AND activo = 1") ?? 0
    ];
} catch (Exception $e) {
    $stats = [
        'total_clientes' => 0,
        'total_proveedores' => 0,
        'total_productos' => 0,
        'total_servicios' => 0,
        'total_empleados' => 0,
        'total_bancos' => 0,
        'total_centros_costo' => 0,
        'total_bodegas' => 0
    ];
}

// Obtener datos
try {
    $clientes = db_query("SELECT c.*, p.nombre as pais_nombre FROM ma_clientes c LEFT JOIN paises p ON c.pais_id = p.id WHERE c.$where_empresa ORDER BY c.razon_social ASC");
    $proveedores = db_query("SELECT pr.*, p.nombre as pais_nombre FROM ma_proveedores pr LEFT JOIN paises p ON pr.pais_id = p.id WHERE pr.$where_empresa ORDER BY pr.razon_social ASC");
    $productos = db_query("SELECT * FROM ma_productos WHERE $where_empresa ORDER BY nombre ASC");
    $servicios = db_query("SELECT * FROM ma_servicios WHERE $where_empresa ORDER BY nombre ASC");
    $empleados = db_query("SELECT e.*, p.nombre as pais_nombre FROM ma_empleados e LEFT JOIN paises p ON e.pais_id = p.id WHERE e.$where_empresa ORDER BY apellidos, nombres ASC");
    $bancos = db_query("SELECT b.*, p.nombre as pais_nombre FROM ma_bancos b LEFT JOIN paises p ON b.pais_id = p.id WHERE b.$where_empresa ORDER BY b.nombre ASC");
    $centros_costo = db_query("SELECT * FROM ma_centros_costo WHERE $where_empresa ORDER BY codigo ASC");
    $bodegas = db_query("SELECT * FROM ma_bodegas WHERE $where_empresa ORDER BY nombre ASC");
    $paises = db_query("SELECT * FROM paises WHERE activo = 1 ORDER BY nombre ASC");
} catch (Exception $e) {
    $clientes = [];
    $proveedores = [];
    $productos = [];
    $servicios = [];
    $empleados = [];
    $bancos = [];
    $centros_costo = [];
    $bodegas = [];
    $paises = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entidades Maestras - CONECTA ERP</title>

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
.badge-A { background: #10b981; color: white; }
.badge-B { background: #f59e0b; color: white; }
.badge-C { background: #ef4444; color: white; }

.table-ma {
    font-size: 0.9rem;
}
.table-ma th {
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
    display: block;
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
                    <i class="fas fa-database me-2"></i>
                    Entidades Maestras
                </h4>
                <small class="text-muted">Base estructural del ERP - ADN del sistema</small>
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
                        <div class="user-role">Usuario</div>
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
            <div class="row g-3 mb-4">
                <div class="col-lg-3 col-md-6">
                    <div class="card stat-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <p class="text-muted mb-1" style="font-size: 0.875rem;">Clientes</p>
                                    <h2 class="mb-0"><?php echo number_format($stats['total_clientes']); ?></h2>
                                </div>
                                <div class="feature-icon" style="width: 50px; height: 50px; font-size: 1.25rem;">
                                    <i class="fas fa-users"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <div class="card stat-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <p class="text-muted mb-1" style="font-size: 0.875rem;">Proveedores</p>
                                    <h2 class="mb-0"><?php echo number_format($stats['total_proveedores']); ?></h2>
                                </div>
                                <div class="feature-icon" style="width: 50px; height: 50px; font-size: 1.25rem;">
                                    <i class="fas fa-truck"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <div class="card stat-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <p class="text-muted mb-1" style="font-size: 0.875rem;">Productos</p>
                                    <h2 class="mb-0"><?php echo number_format($stats['total_productos']); ?></h2>
                                </div>
                                <div class="feature-icon" style="width: 50px; height: 50px; font-size: 1.25rem;">
                                    <i class="fas fa-box"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <div class="card stat-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <p class="text-muted mb-1" style="font-size: 0.875rem;">Empleados</p>
                                    <h2 class="mb-0"><?php echo number_format($stats['total_empleados']); ?></h2>
                                </div>
                                <div class="feature-icon" style="width: 50px; height: 50px; font-size: 1.25rem;">
                                    <i class="fas fa-id-card"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs de Navegación -->
            <ul class="nav nav-tabs mb-4" id="entidadesTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="clientes-tab" data-bs-toggle="tab" data-bs-target="#clientes" type="button">
                        <i class="fas fa-users me-2"></i>Clientes
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="proveedores-tab" data-bs-toggle="tab" data-bs-target="#proveedores" type="button">
                        <i class="fas fa-truck me-2"></i>Proveedores
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="productos-tab" data-bs-toggle="tab" data-bs-target="#productos" type="button">
                        <i class="fas fa-box me-2"></i>Productos
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="servicios-tab" data-bs-toggle="tab" data-bs-target="#servicios" type="button">
                        <i class="fas fa-concierge-bell me-2"></i>Servicios
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="empleados-tab" data-bs-toggle="tab" data-bs-target="#empleados" type="button">
                        <i class="fas fa-id-card me-2"></i>Empleados
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="bancos-tab" data-bs-toggle="tab" data-bs-target="#bancos" type="button">
                        <i class="fas fa-university me-2"></i>Bancos
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="centros-tab" data-bs-toggle="tab" data-bs-target="#centros" type="button">
                        <i class="fas fa-sitemap me-2"></i>Centros de Costo
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="bodegas-tab" data-bs-toggle="tab" data-bs-target="#bodegas" type="button">
                        <i class="fas fa-warehouse me-2"></i>Bodegas
                    </button>
                </li>
            </ul>

            <!-- Contenido de las Tabs -->
            <div class="tab-content" id="entidadesTabContent">
                <!-- TAB 1: CLIENTES -->
                <div class="tab-pane fade show active" id="clientes" role="tabpanel">
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="row align-items-center g-3">
                                <div class="col-md-3">
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrearCliente">
                                        <i class="fas fa-plus me-2"></i>Nuevo Cliente
                                    </button>
                                </div>
                                <div class="col-md-3">
                                    <select class="form-select" id="filterClasifCliente">
                                        <option value="">Todas las clasificaciones</option>
                                        <option value="A">Clase A</option>
                                        <option value="B">Clase B</option>
                                        <option value="C">Clase C</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <select class="form-select" id="filterEstadoCliente">
                                        <option value="">Todos</option>
                                        <option value="1">Activos</option>
                                        <option value="0">Inactivos</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <input type="text" class="form-control" id="searchClientes" placeholder="Buscar cliente...">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0"><i class="fas fa-users me-2"></i>Lista de Clientes</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover table-ma">
                                    <thead>
                                        <tr>
                                            <th>Código</th>
                                            <th>Razón Social</th>
                                            <th>Documento</th>
                                            <th>País</th>
                                            <th>Email</th>
                                            <th>Clasificación</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($clientes) > 0): ?>
                                            <?php foreach ($clientes as $cliente): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($cliente['codigo'] ?? 'N/A'); ?></td>
                                                <td style="font-weight: 600;"><?php echo htmlspecialchars($cliente['razon_social']); ?></td>
                                                <td><?php echo htmlspecialchars($cliente['documento']); ?></td>
                                                <td><?php echo htmlspecialchars($cliente['pais_nombre'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($cliente['email'] ?? 'N/A'); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php echo $cliente['clasificacion_abc']; ?>">
                                                        <?php echo $cliente['clasificacion_abc']; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-<?php echo $cliente['activo'] ? 'activo' : 'inactivo'; ?>">
                                                        <?php echo $cliente['activo'] ? 'Activo' : 'Inactivo'; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-primary action-btn" onclick="verCliente(<?php echo $cliente['id']; ?>)" title="Ver">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-warning action-btn" onclick="editarCliente(<?php echo $cliente['id']; ?>)" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-info action-btn" onclick="verHistorial(<?php echo $cliente['id']; ?>)" title="Historial">
                                                        <i class="fas fa-history"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="8" class="text-center py-5">
                                                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                                    <p class="text-muted mb-0">No hay clientes registrados</p>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 2: PROVEEDORES -->
                <div class="tab-pane fade" id="proveedores" role="tabpanel">
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="row align-items-center g-3">
                                <div class="col-md-4">
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrearProveedor">
                                        <i class="fas fa-plus me-2"></i>Nuevo Proveedor
                                    </button>
                                </div>
                                <div class="col-md-4">
                                    <select class="form-select" id="filterRubro">
                                        <option value="">Todos los rubros</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <input type="text" class="form-control" id="searchProveedores" placeholder="Buscar proveedor...">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0"><i class="fas fa-truck me-2"></i>Lista de Proveedores</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover table-ma">
                                    <thead>
                                        <tr>
                                            <th>Código</th>
                                            <th>Razón Social</th>
                                            <th>Documento</th>
                                            <th>Rubro</th>
                                            <th>País</th>
                                            <th>Calificación</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($proveedores) > 0): ?>
                                            <?php foreach ($proveedores as $proveedor): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($proveedor['codigo'] ?? 'N/A'); ?></td>
                                                <td style="font-weight: 600;"><?php echo htmlspecialchars($proveedor['razon_social']); ?></td>
                                                <td><?php echo htmlspecialchars($proveedor['documento']); ?></td>
                                                <td><?php echo htmlspecialchars($proveedor['rubro'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($proveedor['pais_nombre'] ?? 'N/A'); ?></td>
                                                <td><?php echo number_format($proveedor['calificacion'], 1); ?> ⭐</td>
                                                <td>
                                                    <span class="badge badge-<?php echo $proveedor['activo'] ? 'activo' : 'inactivo'; ?>">
                                                        <?php echo $proveedor['activo'] ? 'Activo' : 'Inactivo'; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-primary action-btn" onclick="verProveedor(<?php echo $proveedor['id']; ?>)" title="Ver">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-warning action-btn" onclick="editarProveedor(<?php echo $proveedor['id']; ?>)" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="8" class="text-center py-5">
                                                    <i class="fas fa-truck fa-3x text-muted mb-3"></i>
                                                    <p class="text-muted mb-0">No hay proveedores registrados</p>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 3: PRODUCTOS -->
                <div class="tab-pane fade" id="productos" role="tabpanel">
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="row align-items-center g-3">
                                <div class="col-md-3">
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrearProducto">
                                        <i class="fas fa-plus me-2"></i>Nuevo Producto
                                    </button>
                                </div>
                                <div class="col-md-3">
                                    <select class="form-select" id="filterTipoProducto">
                                        <option value="">Todos los tipos</option>
                                        <option value="bien">Bien</option>
                                        <option value="servicio">Servicio</option>
                                        <option value="insumo">Insumo</option>
                                        <option value="producto_terminado">Producto Terminado</option>
                                        <option value="kit">KIT/Combo</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <select class="form-select" id="filterCategoriaProducto">
                                        <option value="">Todas las categorías</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <input type="text" class="form-control" id="searchProductos" placeholder="Buscar producto...">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0"><i class="fas fa-box me-2"></i>Lista de Productos</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover table-ma">
                                    <thead>
                                        <tr>
                                            <th>Código</th>
                                            <th>Nombre</th>
                                            <th>Tipo</th>
                                            <th>Stock</th>
                                            <th>Precio Venta</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($productos) > 0): ?>
                                            <?php foreach ($productos as $producto): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($producto['codigo']); ?></td>
                                                <td style="font-weight: 600;"><?php echo htmlspecialchars($producto['nombre']); ?></td>
                                                <td><?php echo ucfirst(str_replace('_', ' ', $producto['tipo_item'])); ?></td>
                                                <td><?php echo number_format($producto['stock_actual'], 2); ?></td>
                                                <td>$<?php echo number_format($producto['precio_venta'], 2); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php echo $producto['activo'] ? 'activo' : 'inactivo'; ?>">
                                                        <?php echo $producto['activo'] ? 'Activo' : 'Inactivo'; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-primary action-btn" onclick="verProducto(<?php echo $producto['id']; ?>)" title="Ver">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-warning action-btn" onclick="editarProducto(<?php echo $producto['id']; ?>)" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="7" class="text-center py-5">
                                                    <i class="fas fa-box fa-3x text-muted mb-3"></i>
                                                    <p class="text-muted mb-0">No hay productos registrados</p>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 4: SERVICIOS -->
                <div class="tab-pane fade" id="servicios" role="tabpanel">
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="row align-items-center g-3">
                                <div class="col-md-6">
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrearServicio">
                                        <i class="fas fa-plus me-2"></i>Nuevo Servicio
                                    </button>
                                </div>
                                <div class="col-md-6">
                                    <input type="text" class="form-control" id="searchServicios" placeholder="Buscar servicio...">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0"><i class="fas fa-concierge-bell me-2"></i>Lista de Servicios</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover table-ma">
                                    <thead>
                                        <tr>
                                            <th>Código</th>
                                            <th>Nombre</th>
                                            <th>Tipo</th>
                                            <th>Tarifa/Hora</th>
                                            <th>Tarifa Fija</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($servicios) > 0): ?>
                                            <?php foreach ($servicios as $servicio): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($servicio['codigo']); ?></td>
                                                <td style="font-weight: 600;"><?php echo htmlspecialchars($servicio['nombre']); ?></td>
                                                <td><?php echo htmlspecialchars($servicio['tipo_servicio'] ?? 'N/A'); ?></td>
                                                <td>$<?php echo number_format($servicio['tarifa_hora'] ?? 0, 2); ?></td>
                                                <td>$<?php echo number_format($servicio['tarifa_fija'] ?? 0, 2); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php echo $servicio['activo'] ? 'activo' : 'inactivo'; ?>">
                                                        <?php echo $servicio['activo'] ? 'Activo' : 'Inactivo'; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-primary action-btn" onclick="verServicio(<?php echo $servicio['id']; ?>)" title="Ver">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-warning action-btn" onclick="editarServicio(<?php echo $servicio['id']; ?>)" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="7" class="text-center py-5">
                                                    <i class="fas fa-concierge-bell fa-3x text-muted mb-3"></i>
                                                    <p class="text-muted mb-0">No hay servicios registrados</p>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 5: EMPLEADOS -->
                <div class="tab-pane fade" id="empleados" role="tabpanel">
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="row align-items-center g-3">
                                <div class="col-md-4">
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrearEmpleado">
                                        <i class="fas fa-plus me-2"></i>Nuevo Empleado
                                    </button>
                                </div>
                                <div class="col-md-4">
                                    <select class="form-select" id="filterCargo">
                                        <option value="">Todos los cargos</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <input type="text" class="form-control" id="searchEmpleados" placeholder="Buscar empleado...">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0"><i class="fas fa-id-card me-2"></i>Lista de Empleados</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover table-ma">
                                    <thead>
                                        <tr>
                                            <th>Código</th>
                                            <th>Nombre Completo</th>
                                            <th>Documento</th>
                                            <th>Cargo</th>
                                            <th>Email</th>
                                            <th>Fecha Ingreso</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($empleados) > 0): ?>
                                            <?php foreach ($empleados as $empleado): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($empleado['codigo'] ?? 'N/A'); ?></td>
                                                <td style="font-weight: 600;"><?php echo htmlspecialchars($empleado['apellidos'] . ', ' . $empleado['nombres']); ?></td>
                                                <td><?php echo htmlspecialchars($empleado['documento']); ?></td>
                                                <td><?php echo htmlspecialchars($empleado['cargo'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($empleado['email'] ?? 'N/A'); ?></td>
                                                <td><?php echo $empleado['fecha_ingreso'] ? date('d/m/Y', strtotime($empleado['fecha_ingreso'])) : 'N/A'; ?></td>
                                                <td>
                                                    <span class="badge badge-<?php echo $empleado['activo'] ? 'activo' : 'inactivo'; ?>">
                                                        <?php echo $empleado['activo'] ? 'Activo' : 'Inactivo'; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-primary action-btn" onclick="verEmpleado(<?php echo $empleado['id']; ?>)" title="Ver">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-warning action-btn" onclick="editarEmpleado(<?php echo $empleado['id']; ?>)" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="8" class="text-center py-5">
                                                    <i class="fas fa-id-card fa-3x text-muted mb-3"></i>
                                                    <p class="text-muted mb-0">No hay empleados registrados</p>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 6: BANCOS -->
                <div class="tab-pane fade" id="bancos" role="tabpanel">
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="row align-items-center g-3">
                                <div class="col-md-6">
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrearBanco">
                                        <i class="fas fa-plus me-2"></i>Nueva Cuenta Bancaria
                                    </button>
                                </div>
                                <div class="col-md-6">
                                    <input type="text" class="form-control" id="searchBancos" placeholder="Buscar banco...">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0"><i class="fas fa-university me-2"></i>Cuentas Bancarias</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover table-ma">
                                    <thead>
                                        <tr>
                                            <th>Banco</th>
                                            <th>Código</th>
                                            <th>Tipo Cuenta</th>
                                            <th>N° Cuenta</th>
                                            <th>Saldo</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($bancos) > 0): ?>
                                            <?php foreach ($bancos as $banco): ?>
                                            <tr>
                                                <td style="font-weight: 600;"><?php echo htmlspecialchars($banco['nombre']); ?></td>
                                                <td><?php echo htmlspecialchars($banco['codigo'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($banco['tipo_cuenta'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($banco['numero_cuenta'] ?? 'N/A'); ?></td>
                                                <td>$<?php echo number_format($banco['saldo_actual'], 2); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php echo $banco['activo'] ? 'activo' : 'inactivo'; ?>">
                                                        <?php echo $banco['activo'] ? 'Activo' : 'Inactivo'; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-primary action-btn" onclick="verBanco(<?php echo $banco['id']; ?>)" title="Ver">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-warning action-btn" onclick="editarBanco(<?php echo $banco['id']; ?>)" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="7" class="text-center py-5">
                                                    <i class="fas fa-university fa-3x text-muted mb-3"></i>
                                                    <p class="text-muted mb-0">No hay cuentas bancarias registradas</p>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 7: CENTROS DE COSTO -->
                <div class="tab-pane fade" id="centros" role="tabpanel">
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="row align-items-center g-3">
                                <div class="col-md-6">
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrearCentro">
                                        <i class="fas fa-plus me-2"></i>Nuevo Centro de Costo
                                    </button>
                                </div>
                                <div class="col-md-6">
                                    <input type="text" class="form-control" id="searchCentros" placeholder="Buscar centro...">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0"><i class="fas fa-sitemap me-2"></i>Centros de Costo</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover table-ma">
                                    <thead>
                                        <tr>
                                            <th>Código</th>
                                            <th>Nombre</th>
                                            <th>Área</th>
                                            <th>Descripción</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($centros_costo) > 0): ?>
                                            <?php foreach ($centros_costo as $centro): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($centro['codigo']); ?></td>
                                                <td style="font-weight: 600;"><?php echo htmlspecialchars($centro['nombre']); ?></td>
                                                <td><?php echo htmlspecialchars($centro['area'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars(substr($centro['descripcion'] ?? '', 0, 50)); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php echo $centro['activo'] ? 'activo' : 'inactivo'; ?>">
                                                        <?php echo $centro['activo'] ? 'Activo' : 'Inactivo'; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-primary action-btn" onclick="verCentro(<?php echo $centro['id']; ?>)" title="Ver">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-warning action-btn" onclick="editarCentro(<?php echo $centro['id']; ?>)" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center py-5">
                                                    <i class="fas fa-sitemap fa-3x text-muted mb-3"></i>
                                                    <p class="text-muted mb-0">No hay centros de costo registrados</p>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 8: BODEGAS -->
                <div class="tab-pane fade" id="bodegas" role="tabpanel">
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="row align-items-center g-3">
                                <div class="col-md-4">
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrearBodega">
                                        <i class="fas fa-plus me-2"></i>Nueva Bodega
                                    </button>
                                </div>
                                <div class="col-md-4">
                                    <select class="form-select" id="filterTipoBodega">
                                        <option value="">Todos los tipos</option>
                                        <option value="principal">Principal</option>
                                        <option value="secundaria">Secundaria</option>
                                        <option value="produccion">Producción</option>
                                        <option value="suministros">Suministros</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <input type="text" class="form-control" id="searchBodegas" placeholder="Buscar bodega...">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0"><i class="fas fa-warehouse me-2"></i>Lista de Bodegas</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover table-ma">
                                    <thead>
                                        <tr>
                                            <th>Código</th>
                                            <th>Nombre</th>
                                            <th>Tipo</th>
                                            <th>Ciudad</th>
                                            <th>Capacidad</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($bodegas) > 0): ?>
                                            <?php foreach ($bodegas as $bodega): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($bodega['codigo']); ?></td>
                                                <td style="font-weight: 600;"><?php echo htmlspecialchars($bodega['nombre']); ?></td>
                                                <td><?php echo ucfirst($bodega['tipo']); ?></td>
                                                <td><?php echo htmlspecialchars($bodega['ciudad'] ?? 'N/A'); ?></td>
                                                <td><?php echo number_format($bodega['capacidad'] ?? 0, 2); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php echo $bodega['activo'] ? 'activo' : 'inactivo'; ?>">
                                                        <?php echo $bodega['activo'] ? 'Activo' : 'Inactivo'; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-primary action-btn" onclick="verBodega(<?php echo $bodega['id']; ?>)" title="Ver">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-warning action-btn" onclick="editarBodega(<?php echo $bodega['id']; ?>)" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="7" class="text-center py-5">
                                                    <i class="fas fa-warehouse fa-3x text-muted mb-3"></i>
                                                    <p class="text-muted mb-0">No hay bodegas registradas</p>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- MODALES -->

    <!-- MODAL: CREAR CLIENTE -->
    <div class="modal fade" id="modalCrearCliente" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-users me-2"></i>Nuevo Cliente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formCrearCliente">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Razón Social *</label>
                                <input type="text" class="form-control" name="razon_social" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nombre Comercial</label>
                                <input type="text" class="form-control" name="nombre_comercial">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">País *</label>
                                <select class="form-select" name="pais_id" required>
                                    <option value="">Seleccione...</option>
                                    <?php foreach ($paises as $pais): ?>
                                        <option value="<?php echo $pais['id']; ?>"><?php echo htmlspecialchars($pais['nombre']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Tipo Documento *</label>
                                <input type="text" class="form-control" name="tipo_documento" value="RUT">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Documento *</label>
                                <input type="text" class="form-control" name="documento" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Teléfono</label>
                                <input type="text" class="form-control" name="telefono">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Giro</label>
                                <input type="text" class="form-control" name="giro">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Dirección Fiscal</label>
                                <textarea class="form-control" name="direccion_fiscal" rows="2"></textarea>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" form="formCrearCliente" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Guardar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL: VER CLIENTE -->
    <div class="modal fade" id="modalVerCliente" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-users me-2"></i>Ficha del Cliente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="info-item">
                                <label>Razón Social</label>
                                <div class="value" id="detalleClienteRazonSocial"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <label>Documento</label>
                                <div class="value" id="detalleClienteDocumento"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <label>Email</label>
                                <div class="value" id="detalleClienteEmail"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <label>Teléfono</label>
                                <div class="value" id="detalleClienteTelefono"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <label>Clasificación ABC</label>
                                <div class="value" id="detalleClienteClasif"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <label>Límite de Crédito</label>
                                <div class="value" id="detalleClienteCredito"></div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="info-item">
                                <label>Dirección Fiscal</label>
                                <div class="value" id="detalleClienteDireccion"></div>
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

    <!-- MODAL: VER PROVEEDOR -->
    <div class="modal fade" id="modalVerProveedor" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-truck me-2"></i>Ficha del Proveedor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="info-item">
                                <label>Razón Social</label>
                                <div class="value" id="detalleProveedorRazonSocial"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <label>Documento</label>
                                <div class="value" id="detalleProveedorDocumento"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <label>Rubro</label>
                                <div class="value" id="detalleProveedorRubro"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <label>Calificación</label>
                                <div class="value" id="detalleProveedorCalif"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <label>Email</label>
                                <div class="value" id="detalleProveedorEmail"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <label>Teléfono</label>
                                <div class="value" id="detalleProveedorTelefono"></div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="info-item">
                                <label>Dirección</label>
                                <div class="value" id="detalleProveedorDireccion"></div>
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

    <!-- MODAL: VER PRODUCTO -->
    <div class="modal fade" id="modalVerProducto" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-box me-2"></i>Ficha del Producto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="info-item">
                                <label>Código</label>
                                <div class="value" id="detalleProductoCodigo"></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-item">
                                <label>SKU</label>
                                <div class="value" id="detalleProductoSKU"></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-item">
                                <label>Código Barras</label>
                                <div class="value" id="detalleProductoBarras"></div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="info-item">
                                <label>Nombre</label>
                                <div class="value" id="detalleProductoNombre"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <label>Tipo</label>
                                <div class="value" id="detalleProductoTipo"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <label>Unidad Medida</label>
                                <div class="value" id="detalleProductoUnidad"></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-item">
                                <label>Stock Actual</label>
                                <div class="value" id="detalleProductoStock"></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-item">
                                <label>Costo Promedio</label>
                                <div class="value" id="detalleProductoCosto"></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-item">
                                <label>Precio Venta</label>
                                <div class="value" id="detalleProductoPrecio"></div>
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

    <!-- MODAL: VER EMPLEADO -->
    <div class="modal fade" id="modalVerEmpleado" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-id-card me-2"></i>Ficha del Empleado</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="info-item">
                                <label>Nombre Completo</label>
                                <div class="value" id="detalleEmpleadoNombre"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <label>Documento</label>
                                <div class="value" id="detalleEmpleadoDocumento"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <label>Cargo</label>
                                <div class="value" id="detalleEmpleadoCargo"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <label>Email</label>
                                <div class="value" id="detalleEmpleadoEmail"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <label>Fecha Ingreso</label>
                                <div class="value" id="detalleEmpleadoIngreso"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <label>Tipo Contrato</label>
                                <div class="value" id="detalleEmpleadoContrato"></div>
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
        // Datos para modales (scope global)
        const clientesData = <?php echo json_encode($clientes); ?>;
        const proveedoresData = <?php echo json_encode($proveedores); ?>;
        const productosData = <?php echo json_encode($productos); ?>;
        const serviciosData = <?php echo json_encode($servicios); ?>;
        const empleadosData = <?php echo json_encode($empleados); ?>;
        const bancosData = <?php echo json_encode($bancos); ?>;
        const centrosData = <?php echo json_encode($centros_costo); ?>;
        const bodegasData = <?php echo json_encode($bodegas); ?>;

        // ============================================
        // FUNCIONES GLOBALES PARA ONCLICK
        // ============================================

        // Funciones para clientes
        function verCliente(id) {
            const cliente = clientesData.find(c => c.id == id);
            if (cliente) {
                document.getElementById('detalleClienteRazonSocial').textContent = cliente.razon_social || 'N/A';
                document.getElementById('detalleClienteDocumento').textContent = cliente.documento || 'N/A';
                document.getElementById('detalleClienteEmail').textContent = cliente.email || 'N/A';
                document.getElementById('detalleClienteTelefono').textContent = cliente.telefono || 'N/A';
                document.getElementById('detalleClienteClasif').innerHTML =
                    '<span class="badge badge-' + cliente.clasificacion_abc + '">' + cliente.clasificacion_abc + '</span>';
                document.getElementById('detalleClienteCredito').textContent = '$' + parseFloat(cliente.limite_credito || 0).toLocaleString();
                document.getElementById('detalleClienteDireccion').textContent = cliente.direccion_fiscal || 'N/A';

                const modal = new bootstrap.Modal(document.getElementById('modalVerCliente'));
                modal.show();
            }
        }

        function editarCliente(id) {
            const cliente = clientesData.find(c => c.id == id);
            if (cliente) {
                alert('Editar cliente: ' + cliente.razon_social + '\nFuncionalidad en desarrollo');
            }
        }

        function verHistorial(id) {
            alert('Ver historial comercial del cliente ID: ' + id + '\nFuncionalidad en desarrollo');
        }

        // Funciones para proveedores
        function verProveedor(id) {
            const proveedor = proveedoresData.find(p => p.id == id);
            if (proveedor) {
                document.getElementById('detalleProveedorRazonSocial').textContent = proveedor.razon_social || 'N/A';
                document.getElementById('detalleProveedorDocumento').textContent = proveedor.documento || 'N/A';
                document.getElementById('detalleProveedorRubro').textContent = proveedor.rubro || 'N/A';
                document.getElementById('detalleProveedorCalif').textContent = parseFloat(proveedor.calificacion || 0).toFixed(1) + ' ⭐';
                document.getElementById('detalleProveedorEmail').textContent = proveedor.email || 'N/A';
                document.getElementById('detalleProveedorTelefono').textContent = proveedor.telefono || 'N/A';
                document.getElementById('detalleProveedorDireccion').textContent = proveedor.direccion || 'N/A';

                const modal = new bootstrap.Modal(document.getElementById('modalVerProveedor'));
                modal.show();
            }
        }

        function editarProveedor(id) {
            const proveedor = proveedoresData.find(p => p.id == id);
            if (proveedor) {
                alert('Editar proveedor: ' + proveedor.razon_social + '\nFuncionalidad en desarrollo');
            }
        }

        // Funciones para productos
        function verProducto(id) {
            const producto = productosData.find(p => p.id == id);
            if (producto) {
                document.getElementById('detalleProductoCodigo').textContent = producto.codigo || 'N/A';
                document.getElementById('detalleProductoSKU').textContent = producto.sku || 'N/A';
                document.getElementById('detalleProductoBarras').textContent = producto.codigo_barras || 'N/A';
                document.getElementById('detalleProductoNombre').textContent = producto.nombre || 'N/A';
                document.getElementById('detalleProductoTipo').textContent = producto.tipo_item ? producto.tipo_item.replace('_', ' ').toUpperCase() : 'N/A';
                document.getElementById('detalleProductoUnidad').textContent = producto.unidad_medida || 'N/A';
                document.getElementById('detalleProductoStock').textContent = parseFloat(producto.stock_actual || 0).toFixed(2);
                document.getElementById('detalleProductoCosto').textContent = '$' + parseFloat(producto.costo_promedio || 0).toLocaleString();
                document.getElementById('detalleProductoPrecio').textContent = '$' + parseFloat(producto.precio_venta || 0).toLocaleString();

                const modal = new bootstrap.Modal(document.getElementById('modalVerProducto'));
                modal.show();
            }
        }

        function editarProducto(id) {
            const producto = productosData.find(p => p.id == id);
            if (producto) {
                alert('Editar producto: ' + producto.nombre + '\nFuncionalidad en desarrollo');
            }
        }

        // Funciones para servicios
        function verServicio(id) {
            const servicio = serviciosData.find(s => s.id == id);
            if (servicio) {
                alert('Ver servicio: ' + servicio.nombre + '\n\nCódigo: ' + servicio.codigo + '\nTipo: ' + (servicio.tipo_servicio || 'N/A') + '\nTarifa/Hora: $' + parseFloat(servicio.tarifa_hora || 0).toLocaleString() + '\nTarifa Fija: $' + parseFloat(servicio.tarifa_fija || 0).toLocaleString());
            }
        }

        function editarServicio(id) {
            const servicio = serviciosData.find(s => s.id == id);
            if (servicio) {
                alert('Editar servicio: ' + servicio.nombre + '\nFuncionalidad en desarrollo');
            }
        }

        // Funciones para empleados
        function verEmpleado(id) {
            const empleado = empleadosData.find(e => e.id == id);
            if (empleado) {
                document.getElementById('detalleEmpleadoNombre').textContent = (empleado.apellidos + ', ' + empleado.nombres) || 'N/A';
                document.getElementById('detalleEmpleadoDocumento').textContent = empleado.documento || 'N/A';
                document.getElementById('detalleEmpleadoCargo').textContent = empleado.cargo || 'N/A';
                document.getElementById('detalleEmpleadoEmail').textContent = empleado.email || 'N/A';
                document.getElementById('detalleEmpleadoIngreso').textContent = empleado.fecha_ingreso ? new Date(empleado.fecha_ingreso).toLocaleDateString('es-CL') : 'N/A';
                document.getElementById('detalleEmpleadoContrato').textContent = empleado.tipo_contrato || 'N/A';

                const modal = new bootstrap.Modal(document.getElementById('modalVerEmpleado'));
                modal.show();
            }
        }

        function editarEmpleado(id) {
            const empleado = empleadosData.find(e => e.id == id);
            if (empleado) {
                alert('Editar empleado: ' + empleado.apellidos + ', ' + empleado.nombres + '\nFuncionalidad en desarrollo');
            }
        }

        // Funciones para bancos
        function verBanco(id) {
            const banco = bancosData.find(b => b.id == id);
            if (banco) {
                alert('Ver banco: ' + banco.nombre + '\n\nCódigo: ' + (banco.codigo || 'N/A') + '\nTipo Cuenta: ' + (banco.tipo_cuenta || 'N/A') + '\nN° Cuenta: ' + (banco.numero_cuenta || 'N/A') + '\nSaldo: $' + parseFloat(banco.saldo_actual || 0).toLocaleString());
            }
        }

        function editarBanco(id) {
            const banco = bancosData.find(b => b.id == id);
            if (banco) {
                alert('Editar banco: ' + banco.nombre + '\nFuncionalidad en desarrollo');
            }
        }

        // Funciones para centros de costo
        function verCentro(id) {
            const centro = centrosData.find(c => c.id == id);
            if (centro) {
                alert('Ver centro de costo: ' + centro.nombre + '\n\nCódigo: ' + centro.codigo + '\nÁrea: ' + (centro.area || 'N/A') + '\nDescripción: ' + (centro.descripcion || 'N/A'));
            }
        }

        function editarCentro(id) {
            const centro = centrosData.find(c => c.id == id);
            if (centro) {
                alert('Editar centro de costo: ' + centro.nombre + '\nFuncionalidad en desarrollo');
            }
        }

        // Funciones para bodegas
        function verBodega(id) {
            const bodega = bodegasData.find(b => b.id == id);
            if (bodega) {
                alert('Ver bodega: ' + bodega.nombre + '\n\nCódigo: ' + bodega.codigo + '\nTipo: ' + bodega.tipo + '\nCiudad: ' + (bodega.ciudad || 'N/A') + '\nCapacidad: ' + parseFloat(bodega.capacidad || 0).toFixed(2));
            }
        }

        function editarBodega(id) {
            const bodega = bodegasData.find(b => b.id == id);
            if (bodega) {
                alert('Editar bodega: ' + bodega.nombre + '\nFuncionalidad en desarrollo');
            }
        }

        // ============================================
        // DOM READY
        // ============================================
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

            // Búsqueda en tablas
            const searches = ['searchClientes', 'searchProveedores', 'searchProductos', 'searchServicios',
                             'searchEmpleados', 'searchBancos', 'searchCentros', 'searchBodegas'];
            searches.forEach(searchId => {
                const input = document.getElementById(searchId);
                if (input) {
                    input.addEventListener('input', function() {
                        const searchTerm = this.value.toLowerCase();
                        const table = this.closest('.card').querySelector('table tbody');
                        const rows = table.querySelectorAll('tr');
                        rows.forEach(row => {
                            const text = row.textContent.toLowerCase();
                            row.style.display = text.includes(searchTerm) ? '' : 'none';
                        });
                    });
                }
            });

            // Forms
            const form = document.getElementById('formCrearCliente');
            if (form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const formData = new FormData(this);
                    console.log('Crear cliente:', Object.fromEntries(formData));
                    alert('Funcionalidad de guardado en desarrollo');
                });
            }
        });
    </script>
</body>
</html>
