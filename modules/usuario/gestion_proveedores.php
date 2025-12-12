<?php
require_once __DIR__ . '/../../config/config.php';

// Verificar si está logueado
if (!is_logged_in()) {
    redirect('/login.php');
}

$user_id = $_SESSION['user_id'];
$user = db_get_row("SELECT * FROM usuarios WHERE id = ?", [$user_id]);
$empresa_id = $user['empresa_id'] ?? null;

// ========================================
// PROCESAR FORMULARIOS
// ========================================
$success = '';
$error = '';

// Crear proveedor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['razon_social'])) {
    try {
        // 1. Insertar proveedor principal
        db_query("INSERT INTO proveedores (
            empresa_id, tipo_proveedor, tipo_documento, documento, razon_social,
            nombre_fantasia, actividad_economica, pais_id, idioma, sitio_web,
            estado, fecha_creacion, usuario_crea
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)", [
            $empresa_id,
            $_POST['tipo_proveedor'] ?? 'empresa',
            $_POST['tipo_documento'] ?? 'RUT',
            $_POST['documento'] ?? '',
            $_POST['razon_social'],
            $_POST['nombre_fantasia'] ?? '',
            $_POST['actividad_economica'] ?? '',
            $_POST['pais_id'] ?? 1,
            $_POST['idioma'] ?? 'es',
            $_POST['sitio_web'] ?? '',
            $_POST['estado'] ?? 'activo',
            $user_id
        ]);

        $proveedor_id = db_get_var("SELECT LAST_INSERT_ID()");

        // 2. Datos tributarios
        if (isset($_POST['regimen_tributario'])) {
            db_query("INSERT INTO proveedor_datos_tributarios (
                proveedor_id, regimen_tributario, es_afecto, exento, codigo_actividad
            ) VALUES (?, ?, ?, ?, ?)", [
                $proveedor_id,
                $_POST['regimen_tributario'] ?? '',
                isset($_POST['es_afecto']) ? 1 : 0,
                isset($_POST['exento']) ? 1 : 0,
                $_POST['codigo_actividad'] ?? ''
            ]);
        }

        // 3. Contacto
        db_query("INSERT INTO proveedor_contacto (
            proveedor_id, telefono, telefono_alt, email_principal, email_compras,
            email_finanzas, whatsapp, canal_preferente
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)", [
            $proveedor_id,
            $_POST['telefono'] ?? '',
            $_POST['telefono_alt'] ?? '',
            $_POST['email_principal'] ?? '',
            $_POST['email_compras'] ?? '',
            $_POST['email_finanzas'] ?? '',
            $_POST['whatsapp'] ?? '',
            $_POST['canal_preferente'] ?? 'email'
        ]);

        // 4. Dirección
        if (isset($_POST['direccion'])) {
            db_query("INSERT INTO proveedor_direcciones (
                proveedor_id, direccion, numero, complemento, pais, region,
                ciudad, comuna, tipo, latitud, longitud
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", [
                $proveedor_id,
                $_POST['direccion'] ?? '',
                $_POST['numero'] ?? '',
                $_POST['complemento'] ?? '',
                $_POST['pais_direccion'] ?? '',
                $_POST['region'] ?? '',
                $_POST['ciudad'] ?? '',
                $_POST['comuna'] ?? '',
                $_POST['tipo_direccion'] ?? 'fiscal',
                $_POST['latitud'] ?? null,
                $_POST['longitud'] ?? null
            ]);
        }

        // 5. Condiciones comerciales
        db_query("INSERT INTO proveedor_comercial (
            proveedor_id, plazo_pago, forma_pago, limite_credito, moneda_preferente,
            descuento_default, rut_responsable_pago, nombre_responsable_pago
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)", [
            $proveedor_id,
            $_POST['plazo_pago'] ?? '30',
            $_POST['forma_pago'] ?? 'transferencia',
            $_POST['limite_credito'] ?? 0,
            $_POST['moneda_preferente'] ?? 'CLP',
            $_POST['descuento_default'] ?? 0,
            $_POST['rut_responsable_pago'] ?? '',
            $_POST['nombre_responsable_pago'] ?? ''
        ]);

        // Registrar en auditoría
        db_query("INSERT INTO auditoria_proveedores (
            proveedor_id, usuario_id, accion, fecha, ip_usuario,
            campo_modificado, valor_anterior, valor_nuevo
        ) VALUES (?, ?, ?, NOW(), ?, ?, ?, ?)", [
            $proveedor_id,
            $user_id,
            'CREAR',
            $_SERVER['REMOTE_ADDR'] ?? '',
            'proveedor',
            '',
            $_POST['razon_social']
        ]);

        // Redirect para evitar reenvío
        header("Location: " . $_SERVER['PHP_SELF'] . "?success=proveedor_creado");
        exit;

    } catch (Exception $e) {
        $error = "Error al crear proveedor: " . $e->getMessage();
    }
}

// Mensaje de éxito
if (isset($_GET['success']) && $_GET['success'] === 'proveedor_creado') {
    $success = "Proveedor creado exitosamente";
}

// ========================================
// AUTO-CREAR TABLAS (sin datos embebidos)
// ========================================
try {
    // Tabla: Idiomas (SOLO estructura)
    db_query("CREATE TABLE IF NOT EXISTS idiomas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        codigo VARCHAR(5) UNIQUE NOT NULL,
        nombre VARCHAR(50) NOT NULL,
        activo TINYINT(1) DEFAULT 1
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Tabla: Plazos de Pago (SOLO estructura)
    db_query("CREATE TABLE IF NOT EXISTS plazos_pago (
        id INT AUTO_INCREMENT PRIMARY KEY,
        codigo VARCHAR(20) UNIQUE NOT NULL,
        nombre VARCHAR(100) NOT NULL,
        dias INT DEFAULT 0,
        activo TINYINT(1) DEFAULT 1
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Tabla: Formas de Pago (SOLO estructura)
    db_query("CREATE TABLE IF NOT EXISTS formas_pago (
        id INT AUTO_INCREMENT PRIMARY KEY,
        codigo VARCHAR(20) UNIQUE NOT NULL,
        nombre VARCHAR(100) NOT NULL,
        activo TINYINT(1) DEFAULT 1
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Tabla: Tipos de Cuenta Bancaria (SOLO estructura)
    db_query("CREATE TABLE IF NOT EXISTS tipos_cuenta_bancaria (
        id INT AUTO_INCREMENT PRIMARY KEY,
        codigo VARCHAR(20) UNIQUE NOT NULL,
        nombre VARCHAR(100) NOT NULL,
        activo TINYINT(1) DEFAULT 1
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Tabla: Canales de Comunicación (SOLO estructura)
    db_query("CREATE TABLE IF NOT EXISTS canales_comunicacion (
        id INT AUTO_INCREMENT PRIMARY KEY,
        codigo VARCHAR(20) UNIQUE NOT NULL,
        nombre VARCHAR(100) NOT NULL,
        icono VARCHAR(50),
        activo TINYINT(1) DEFAULT 1
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Tabla: Tipos de Dirección (SOLO estructura)
    db_query("CREATE TABLE IF NOT EXISTS tipos_direccion (
        id INT AUTO_INCREMENT PRIMARY KEY,
        codigo VARCHAR(20) UNIQUE NOT NULL,
        nombre VARCHAR(100) NOT NULL,
        activo TINYINT(1) DEFAULT 1
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Tabla: Roles de Contacto (SOLO estructura)
    db_query("CREATE TABLE IF NOT EXISTS roles_contacto (
        id INT AUTO_INCREMENT PRIMARY KEY,
        codigo VARCHAR(20) UNIQUE NOT NULL,
        nombre VARCHAR(100) NOT NULL,
        activo TINYINT(1) DEFAULT 1
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Tabla principal: proveedores
    db_query("CREATE TABLE IF NOT EXISTS proveedores (
        id INT AUTO_INCREMENT PRIMARY KEY,
        empresa_id INT NOT NULL,
        tipo_proveedor ENUM('empresa', 'persona') DEFAULT 'empresa',
        tipo_documento VARCHAR(20) DEFAULT 'RUT',
        documento VARCHAR(50) UNIQUE NOT NULL,
        razon_social VARCHAR(255) NOT NULL,
        nombre_fantasia VARCHAR(255),
        actividad_economica VARCHAR(255),
        pais_id INT,
        idioma VARCHAR(5) DEFAULT 'es',
        sitio_web VARCHAR(255),
        estado ENUM('activo', 'inactivo') DEFAULT 'activo',
        fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
        fecha_modificacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        usuario_crea INT,
        usuario_modifica INT,
        INDEX idx_empresa (empresa_id),
        INDEX idx_documento (documento),
        INDEX idx_estado (estado)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Datos tributarios
    db_query("CREATE TABLE IF NOT EXISTS proveedor_datos_tributarios (
        id INT AUTO_INCREMENT PRIMARY KEY,
        proveedor_id INT NOT NULL,
        regimen_tributario VARCHAR(100),
        es_afecto TINYINT(1) DEFAULT 1,
        exento TINYINT(1) DEFAULT 0,
        codigo_actividad VARCHAR(50),
        FOREIGN KEY (proveedor_id) REFERENCES proveedores(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Contacto
    db_query("CREATE TABLE IF NOT EXISTS proveedor_contacto (
        id INT AUTO_INCREMENT PRIMARY KEY,
        proveedor_id INT NOT NULL UNIQUE,
        telefono VARCHAR(50),
        telefono_alt VARCHAR(50),
        email_principal VARCHAR(255),
        email_compras VARCHAR(255),
        email_finanzas VARCHAR(255),
        whatsapp VARCHAR(50),
        canal_preferente ENUM('email', 'telefono', 'whatsapp') DEFAULT 'email',
        FOREIGN KEY (proveedor_id) REFERENCES proveedores(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Direcciones
    db_query("CREATE TABLE IF NOT EXISTS proveedor_direcciones (
        id INT AUTO_INCREMENT PRIMARY KEY,
        proveedor_id INT NOT NULL,
        direccion VARCHAR(255),
        numero VARCHAR(20),
        complemento VARCHAR(100),
        pais VARCHAR(100),
        region VARCHAR(100),
        ciudad VARCHAR(100),
        comuna VARCHAR(100),
        tipo ENUM('fiscal', 'comercial', 'despacho') DEFAULT 'fiscal',
        latitud DECIMAL(10, 8),
        longitud DECIMAL(11, 8),
        FOREIGN KEY (proveedor_id) REFERENCES proveedores(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Sucursales
    db_query("CREATE TABLE IF NOT EXISTS proveedor_sucursales (
        id INT AUTO_INCREMENT PRIMARY KEY,
        proveedor_id INT NOT NULL,
        nombre VARCHAR(255) NOT NULL,
        responsable VARCHAR(255),
        telefono VARCHAR(50),
        email VARCHAR(255),
        direccion TEXT,
        tipo ENUM('principal', 'secundaria') DEFAULT 'secundaria',
        horario VARCHAR(255),
        FOREIGN KEY (proveedor_id) REFERENCES proveedores(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Bancos
    db_query("CREATE TABLE IF NOT EXISTS proveedor_bancos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        proveedor_id INT NOT NULL,
        banco VARCHAR(100),
        tipo_cuenta ENUM('corriente', 'vista', 'ahorro') DEFAULT 'corriente',
        numero_cuenta VARCHAR(50),
        titular VARCHAR(255),
        rut_titular VARCHAR(50),
        moneda VARCHAR(10) DEFAULT 'CLP',
        es_preferida TINYINT(1) DEFAULT 0,
        FOREIGN KEY (proveedor_id) REFERENCES proveedores(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Condiciones comerciales
    db_query("CREATE TABLE IF NOT EXISTS proveedor_comercial (
        id INT AUTO_INCREMENT PRIMARY KEY,
        proveedor_id INT NOT NULL UNIQUE,
        plazo_pago VARCHAR(50) DEFAULT '30',
        forma_pago VARCHAR(50) DEFAULT 'transferencia',
        limite_credito DECIMAL(15, 2) DEFAULT 0,
        moneda_preferente VARCHAR(10) DEFAULT 'CLP',
        descuento_default DECIMAL(5, 2) DEFAULT 0,
        rut_responsable_pago VARCHAR(50),
        nombre_responsable_pago VARCHAR(255),
        FOREIGN KEY (proveedor_id) REFERENCES proveedores(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Contactos internos
    db_query("CREATE TABLE IF NOT EXISTS proveedor_contactos_internos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        proveedor_id INT NOT NULL,
        nombre VARCHAR(255) NOT NULL,
        cargo VARCHAR(100),
        telefono VARCHAR(50),
        email VARCHAR(255),
        rol ENUM('compras', 'finanzas', 'despacho', 'gerente', 'otro') DEFAULT 'otro',
        FOREIGN KEY (proveedor_id) REFERENCES proveedores(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Adjuntos
    db_query("CREATE TABLE IF NOT EXISTS proveedor_adjuntos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        proveedor_id INT NOT NULL,
        nombre_archivo VARCHAR(255) NOT NULL,
        ruta_archivo VARCHAR(500),
        tipo VARCHAR(50),
        fecha_subida DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (proveedor_id) REFERENCES proveedores(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Auditoría
    db_query("CREATE TABLE IF NOT EXISTS auditoria_proveedores (
        id INT AUTO_INCREMENT PRIMARY KEY,
        proveedor_id INT NOT NULL,
        usuario_id INT NOT NULL,
        accion ENUM('CREAR', 'MODIFICAR', 'ELIMINAR', 'ACTIVAR', 'INACTIVAR') NOT NULL,
        fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
        ip_usuario VARCHAR(50),
        campo_modificado VARCHAR(100),
        valor_anterior TEXT,
        valor_nuevo TEXT,
        INDEX idx_proveedor (proveedor_id),
        INDEX idx_fecha (fecha)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

} catch (Exception $e) {
    // Silenciar errores si ya existen
}

// ========================================
// CONSULTAR DATOS
// ========================================

// Consultar proveedores con JOIN a países
try {
    $proveedores = db_query("
        SELECT p.*, pa.nombre as pais_nombre
        FROM proveedores p
        LEFT JOIN paises pa ON p.pais_id = pa.id
        WHERE p.empresa_id = ?
        ORDER BY p.razon_social ASC
    ", [$empresa_id]);
} catch (Exception $e) {
    $proveedores = [];
}

// Consultar países para select
try { $paises = db_query("SELECT * FROM paises ORDER BY nombre ASC"); } catch (Exception $e) { $paises = []; }

// Consultar datos para selects (TODO desde SQL, NADA embebido)
// Si una tabla no existe, devuelve array vacío (el admin debe crearla)
try { $idiomas = db_query("SELECT * FROM idiomas WHERE activo = 1 ORDER BY nombre ASC"); } catch (Exception $e) { $idiomas = []; }
try { $plazos_pago = db_query("SELECT * FROM plazos_pago WHERE activo = 1 ORDER BY dias ASC"); } catch (Exception $e) { $plazos_pago = []; }
try { $formas_pago = db_query("SELECT * FROM formas_pago WHERE activo = 1 ORDER BY nombre ASC"); } catch (Exception $e) { $formas_pago = []; }
try { $monedas = db_query("SELECT * FROM monedas WHERE activo = 1 ORDER BY codigo ASC"); } catch (Exception $e) { $monedas = []; }
try { $tipos_cuenta = db_query("SELECT * FROM tipos_cuenta_bancaria WHERE activo = 1 ORDER BY nombre ASC"); } catch (Exception $e) { $tipos_cuenta = []; }
try { $canales_comunicacion = db_query("SELECT * FROM canales_comunicacion WHERE activo = 1 ORDER BY nombre ASC"); } catch (Exception $e) { $canales_comunicacion = []; }
try { $tipos_direccion = db_query("SELECT * FROM tipos_direccion WHERE activo = 1 ORDER BY nombre ASC"); } catch (Exception $e) { $tipos_direccion = []; }
try { $roles_contacto = db_query("SELECT * FROM roles_contacto WHERE activo = 1 ORDER BY nombre ASC"); } catch (Exception $e) { $roles_contacto = []; }

// Estadísticas
try {
    $stats = [
        'total_proveedores' => db_get_var("SELECT COUNT(*) FROM proveedores WHERE empresa_id = ?", [$empresa_id]) ?? 0,
        'activos' => db_get_var("SELECT COUNT(*) FROM proveedores WHERE empresa_id = ? AND estado = 'activo'", [$empresa_id]) ?? 0,
        'empresas' => db_get_var("SELECT COUNT(*) FROM proveedores WHERE empresa_id = ? AND tipo_proveedor = 'empresa'", [$empresa_id]) ?? 0,
        'personas' => db_get_var("SELECT COUNT(*) FROM proveedores WHERE empresa_id = ? AND tipo_proveedor = 'persona'", [$empresa_id]) ?? 0
    ];
} catch (Exception $e) {
    $stats = [
        'total_proveedores' => 0,
        'activos' => 0,
        'empresas' => 0,
        'personas' => 0
    ];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Proveedores - CONECTA ERP</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/styles.css">

    <style>
/* Layout principal */
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

/* Botón fullscreen */
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
}

body.fullscreen-mode .sidebar { display: none !important; }
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

.stat-card {
    border-left: 4px solid var(--primary-color);
}

.badge-activo { background: #10b981; color: white; }
.badge-inactivo { background: #6b7280; color: white; }

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

.action-btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
    margin: 0 0.125rem;
}

.nav-tabs .nav-link {
    color: var(--text-secondary);
    border: none;
    border-bottom: 2px solid transparent;
}

.nav-tabs .nav-link.active {
    color: var(--primary-color);
    border-bottom: 2px solid var(--primary-color);
    background: transparent;
}

.section-title {
    font-size: 1rem;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid var(--border-color);
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
                    <i class="fas fa-truck-field me-2"></i>
                    Gestión de Proveedores
                </h4>
                <small class="text-muted">Módulo completo de administración de proveedores</small>
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

            <!-- Mensajes -->
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Estadísticas -->
            <div class="row g-3 mb-4">
                <div class="col-lg-3 col-md-6">
                    <div class="card stat-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <p class="text-muted mb-1" style="font-size: 0.875rem;">Total Proveedores</p>
                                    <h2 class="mb-0"><?php echo number_format($stats['total_proveedores']); ?></h2>
                                </div>
                                <div class="feature-icon" style="width: 50px; height: 50px; font-size: 1.25rem;">
                                    <i class="fas fa-truck-field"></i>
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
                                    <p class="text-muted mb-1" style="font-size: 0.875rem;">Proveedores Activos</p>
                                    <h2 class="mb-0"><?php echo number_format($stats['activos']); ?></h2>
                                </div>
                                <div class="feature-icon" style="width: 50px; height: 50px; font-size: 1.25rem;">
                                    <i class="fas fa-check-circle"></i>
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
                                    <p class="text-muted mb-1" style="font-size: 0.875rem;">Empresas</p>
                                    <h2 class="mb-0"><?php echo number_format($stats['empresas']); ?></h2>
                                </div>
                                <div class="feature-icon" style="width: 50px; height: 50px; font-size: 1.25rem;">
                                    <i class="fas fa-building"></i>
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
                                    <p class="text-muted mb-1" style="font-size: 0.875rem;">Personas</p>
                                    <h2 class="mb-0"><?php echo number_format($stats['personas']); ?></h2>
                                </div>
                                <div class="feature-icon" style="width: 50px; height: 50px; font-size: 1.25rem;">
                                    <i class="fas fa-user"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botón Nuevo Proveedor -->
            <div class="mb-3">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrearProveedor">
                    <i class="fas fa-plus me-2"></i>Nuevo Proveedor
                </button>
            </div>

            <!-- Listado de Proveedores -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Razón Social</th>
                                    <th>Documento</th>
                                    <th>País</th>
                                    <th>Email</th>
                                    <th>Teléfono</th>
                                    <th>Tipo</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($proveedores)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center py-4">
                                            <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
                                            <p class="text-muted">No hay proveedores registrados</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($proveedores as $proveedor): ?>
                                        <?php
                                        // Obtener datos de contacto
                                        $contacto = db_query("SELECT * FROM proveedor_contacto WHERE proveedor_id = ?", [$proveedor['id']])[0] ?? null;
                                        ?>
                                        <tr>
                                            <td><strong>#<?php echo $proveedor['id']; ?></strong></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($proveedor['razon_social']); ?></strong>
                                                <?php if ($proveedor['nombre_fantasia']): ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($proveedor['nombre_fantasia']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary"><?php echo htmlspecialchars($proveedor['tipo_documento']); ?></span>
                                                <?php echo htmlspecialchars($proveedor['documento']); ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($proveedor['pais_nombre'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($contacto['email_principal'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($contacto['telefono'] ?? 'N/A'); ?></td>
                                            <td>
                                                <i class="fas fa-<?php echo $proveedor['tipo_proveedor'] === 'empresa' ? 'building' : 'user'; ?> me-1"></i>
                                                <?php echo ucfirst($proveedor['tipo_proveedor']); ?>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?php echo $proveedor['estado']; ?>">
                                                    <?php echo ucfirst($proveedor['estado']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-info action-btn" onclick="verProveedor(<?php echo $proveedor['id']; ?>)"
                                                        title="Ver Ficha">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-warning action-btn" onclick="editarProveedor(<?php echo $proveedor['id']; ?>)"
                                                        title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal: Crear Proveedor -->
    <div class="modal fade" id="modalCrearProveedor" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle me-2"></i>
                        Nuevo Proveedor
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formCrearProveedor" method="POST">
                        <!-- Tabs de Secciones -->
                        <ul class="nav nav-tabs" role="tablist">
                            <li class="nav-item">
                                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#seccion1" type="button">
                                    1. Identificación
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#seccion2" type="button">
                                    2. Tributaria
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#seccion3" type="button">
                                    3. Contacto
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#seccion4" type="button">
                                    4. Dirección
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#seccion5" type="button">
                                    5. Comercial
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content mt-3">
                            <!-- SECCIÓN 1: IDENTIFICACIÓN -->
                            <div class="tab-pane fade show active" id="seccion1">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label">Tipo de Proveedor *</label>
                                        <select class="form-select" name="tipo_proveedor" required>
                                            <option value="empresa">Empresa</option>
                                            <option value="persona">Persona Natural</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">País *</label>
                                        <select class="form-select" name="pais_id" id="paisSelect" required>
                                            <?php foreach ($paises as $pais): ?>
                                                <option value="<?php echo $pais['id']; ?>"
                                                        data-doc="<?php echo htmlspecialchars($pais['tipo_documento'] ?? 'RUT'); ?>">
                                                    <?php echo htmlspecialchars($pais['nombre']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Tipo de Documento *</label>
                                        <input type="text" class="form-control" name="tipo_documento"
                                               id="tipoDocumento" value="RUT" readonly>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Documento *</label>
                                        <input type="text" class="form-control" name="documento" required
                                               placeholder="12.345.678-9">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Razón Social *</label>
                                        <input type="text" class="form-control" name="razon_social" required
                                               placeholder="Nombre legal de la empresa">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Nombre de Fantasía</label>
                                        <input type="text" class="form-control" name="nombre_fantasia"
                                               placeholder="Nombre comercial">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Actividad Económica *</label>
                                        <input type="text" class="form-control" name="actividad_economica" required
                                               placeholder="Ej: Comercio al por mayor">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Idioma</label>
                                        <select class="form-select" name="idioma">
                                            <?php foreach ($idiomas as $idioma): ?>
                                                <option value="<?php echo $idioma['codigo']; ?>">
                                                    <?php echo htmlspecialchars($idioma['nombre']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Estado</label>
                                        <select class="form-select" name="estado">
                                            <option value="activo">Activo</option>
                                            <option value="inactivo">Inactivo</option>
                                        </select>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label">Sitio Web</label>
                                        <input type="url" class="form-control" name="sitio_web"
                                               placeholder="https://www.ejemplo.com">
                                    </div>
                                </div>
                            </div>

                            <!-- SECCIÓN 2: TRIBUTARIA -->
                            <div class="tab-pane fade" id="seccion2">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Régimen Tributario</label>
                                        <input type="text" class="form-control" name="regimen_tributario"
                                               placeholder="Ej: Régimen General">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Código Actividad</label>
                                        <input type="text" class="form-control" name="codigo_actividad"
                                               placeholder="Código SII o equivalente">
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="es_afecto"
                                                   id="esAfecto" checked>
                                            <label class="form-check-label" for="esAfecto">
                                                Es afecto a impuestos
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="exento"
                                                   id="exento">
                                            <label class="form-check-label" for="exento">
                                                Exento de impuestos
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- SECCIÓN 3: CONTACTO -->
                            <div class="tab-pane fade" id="seccion3">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Teléfono Principal *</label>
                                        <input type="tel" class="form-control" name="telefono" required
                                               placeholder="+56 9 1234 5678">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Teléfono Alternativo</label>
                                        <input type="tel" class="form-control" name="telefono_alt"
                                               placeholder="+56 9 8765 4321">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">WhatsApp</label>
                                        <input type="tel" class="form-control" name="whatsapp"
                                               placeholder="+56 9 1234 5678">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Email Principal *</label>
                                        <input type="email" class="form-control" name="email_principal" required
                                               placeholder="contacto@empresa.com">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Email Compras</label>
                                        <input type="email" class="form-control" name="email_compras"
                                               placeholder="compras@empresa.com">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Email Finanzas</label>
                                        <input type="email" class="form-control" name="email_finanzas"
                                               placeholder="finanzas@empresa.com">
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label">Canal de Comunicación Preferente</label>
                                        <select class="form-select" name="canal_preferente">
                                            <?php foreach ($canales_comunicacion as $canal): ?>
                                                <option value="<?php echo $canal['codigo']; ?>">
                                                    <?php echo htmlspecialchars($canal['nombre']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- SECCIÓN 4: DIRECCIÓN -->
                            <div class="tab-pane fade" id="seccion4">
                                <div class="row g-3">
                                    <div class="col-md-8">
                                        <label class="form-label">Dirección</label>
                                        <input type="text" class="form-control" name="direccion"
                                               placeholder="Calle / Avenida">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Número</label>
                                        <input type="text" class="form-control" name="numero"
                                               placeholder="1234">
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label">Complemento</label>
                                        <input type="text" class="form-control" name="complemento"
                                               placeholder="Oficina, Depto, etc.">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">País</label>
                                        <input type="text" class="form-control" name="pais_direccion"
                                               placeholder="Chile">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Región</label>
                                        <input type="text" class="form-control" name="region"
                                               placeholder="Metropolitana">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Ciudad</label>
                                        <input type="text" class="form-control" name="ciudad"
                                               placeholder="Santiago">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Comuna</label>
                                        <input type="text" class="form-control" name="comuna"
                                               placeholder="Las Condes">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Tipo de Dirección</label>
                                        <select class="form-select" name="tipo_direccion">
                                            <?php foreach ($tipos_direccion as $tipo_dir): ?>
                                                <option value="<?php echo $tipo_dir['codigo']; ?>">
                                                    <?php echo htmlspecialchars($tipo_dir['nombre']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Latitud</label>
                                        <input type="text" class="form-control" name="latitud"
                                               placeholder="-33.4489">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Longitud</label>
                                        <input type="text" class="form-control" name="longitud"
                                               placeholder="-70.6693">
                                    </div>
                                </div>
                            </div>

                            <!-- SECCIÓN 5: COMERCIAL -->
                            <div class="tab-pane fade" id="seccion5">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Plazo de Pago</label>
                                        <select class="form-select" name="plazo_pago">
                                            <?php foreach ($plazos_pago as $plazo): ?>
                                                <option value="<?php echo $plazo['codigo']; ?>" <?php echo $plazo['codigo'] === '30' ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($plazo['nombre']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Forma de Pago</label>
                                        <select class="form-select" name="forma_pago">
                                            <?php foreach ($formas_pago as $forma): ?>
                                                <option value="<?php echo $forma['codigo']; ?>">
                                                    <?php echo htmlspecialchars($forma['nombre']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Moneda Preferente</label>
                                        <select class="form-select" name="moneda_preferente">
                                            <?php foreach ($monedas as $moneda): ?>
                                                <option value="<?php echo $moneda['codigo']; ?>">
                                                    <?php echo $moneda['codigo']; ?> - <?php echo htmlspecialchars($moneda['nombre']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Límite de Crédito</label>
                                        <input type="number" class="form-control" name="limite_credito"
                                               value="0" step="0.01">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Descuento por Defecto (%)</label>
                                        <input type="number" class="form-control" name="descuento_default"
                                               value="0" step="0.01" max="100">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">RUT Responsable de Pago</label>
                                        <input type="text" class="form-control" name="rut_responsable_pago"
                                               placeholder="12.345.678-9">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Nombre Responsable de Pago</label>
                                        <input type="text" class="form-control" name="nombre_responsable_pago"
                                               placeholder="Nombre completo">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times me-2"></i>Cancelar
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Guardar Proveedor
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Ver Ficha 360° del Proveedor -->
    <div class="modal fade" id="modalVerProveedor" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-id-card me-2"></i>
                        Ficha Completa del Proveedor
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <ul class="nav nav-tabs" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabDatos">
                                Datos Generales
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabContacto">
                                Contacto
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabComercial">
                                Comercial
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content mt-3">
                        <!-- Tab Datos Generales -->
                        <div class="tab-pane fade show active" id="tabDatos">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <label>Razón Social:</label>
                                        <div class="value" id="detalleRazonSocial">-</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <label>Nombre de Fantasía:</label>
                                        <div class="value" id="detalleNombreFantasia">-</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="info-item">
                                        <label>Documento:</label>
                                        <div class="value" id="detalleDocumento">-</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="info-item">
                                        <label>País:</label>
                                        <div class="value" id="detallePais">-</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="info-item">
                                        <label>Estado:</label>
                                        <div class="value" id="detalleEstado">-</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tab Contacto -->
                        <div class="tab-pane fade" id="tabContacto">
                            <p class="text-muted">Información de contacto del proveedor (en desarrollo)</p>
                        </div>

                        <!-- Tab Comercial -->
                        <div class="tab-pane fade" id="tabComercial">
                            <p class="text-muted">Condiciones comerciales (en desarrollo)</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // ============================================
        // DATOS DEL SERVIDOR (JAVASCRIPT)
        // ============================================
        const proveedoresData = <?php echo json_encode($proveedores); ?>;

        // ============================================
        // FUNCIONES GLOBALES PARA ONCLICK
        // ============================================

        // Ver Ficha Completa
        function verProveedor(id) {
            const proveedor = proveedoresData.find(p => p.id == id);
            if (proveedor) {
                document.getElementById('detalleRazonSocial').textContent = proveedor.razon_social;
                document.getElementById('detalleNombreFantasia').textContent = proveedor.nombre_fantasia || 'N/A';
                document.getElementById('detalleDocumento').textContent = proveedor.tipo_documento + ': ' + proveedor.documento;
                document.getElementById('detallePais').textContent = proveedor.pais_nombre || 'N/A';
                document.getElementById('detalleEstado').innerHTML =
                    '<span class="badge badge-' + proveedor.estado + '">' + proveedor.estado.toUpperCase() + '</span>';

                const modal = new bootstrap.Modal(document.getElementById('modalVerProveedor'));
                modal.show();
            }
        }

        // Editar Proveedor
        function editarProveedor(id) {
            alert('Funcionalidad de edición en desarrollo para ID: ' + id);
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

            // Cambiar tipo de documento según país
            const paisSelect = document.getElementById('paisSelect');
            const tipoDocInput = document.getElementById('tipoDocumento');

            if (paisSelect && tipoDocInput) {
                paisSelect.addEventListener('change', function() {
                    const selectedOption = this.options[this.selectedIndex];
                    const tipoDoc = selectedOption.getAttribute('data-doc');
                    tipoDocInput.value = tipoDoc || 'RUT';
                });
            }
        });
    </script>
</body>
</html>
