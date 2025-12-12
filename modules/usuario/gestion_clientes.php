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

// Crear cliente
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['razon_social'])) {
    try {
        db_query("INSERT INTO ma_clientes (
            empresa_id, razon_social, nombre_comercial, documento, tipo_documento,
            pais_id, giro, actividad_economica, direccion_fiscal, direccion_comercial,
            ciudad, region, telefono, email, sitio_web, limite_credito, dias_credito,
            descuento_global, clasificacion_abc, segmento, observaciones, activo, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", [
            $empresa_id,
            $_POST['razon_social'],
            $_POST['nombre_comercial'] ?? null,
            $_POST['documento'],
            $_POST['tipo_documento'],
            $_POST['pais_id'],
            $_POST['giro'],
            $_POST['actividad_economica'] ?? null,
            $_POST['direccion_fiscal'],
            $_POST['direccion_comercial'] ?? null,
            $_POST['ciudad'] ?? null,
            $_POST['region'] ?? null,
            $_POST['telefono'],
            $_POST['email'],
            $_POST['sitio_web'] ?? null,
            $_POST['limite_credito'] ?? 0,
            $_POST['dias_credito'] ?? 0,
            $_POST['descuento_global'] ?? 0,
            $_POST['clasificacion_abc'] ?? 'N/A',
            $_POST['segmento'] ?? null,
            $_POST['observaciones'] ?? null,
            $_POST['activo'] ?? 1,
            $user_id
        ]);

        // Registrar en auditoría
        $cliente_id = db_get_var("SELECT LAST_INSERT_ID()");
        db_query("INSERT INTO ma_auditoria (empresa_id, usuario_id, entidad, entidad_id, accion, ip, user_agent)
                  VALUES (?, ?, 'ma_clientes', ?, 'crear', ?, ?)", [
            $empresa_id, $user_id, $cliente_id, $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);

        // Redirect para evitar reenvío
        header("Location: " . $_SERVER['PHP_SELF'] . "?success=cliente_creado");
        exit;
    } catch (Exception $e) {
        $error = "Error al crear cliente: " . $e->getMessage();
    }
}

// Mensaje de éxito
if (isset($_GET['success']) && $_GET['success'] === 'cliente_creado') {
    $success = "Cliente creado exitosamente";
}

// ========================================
// AUTO-CREAR TABLAS COMPLEMENTARIAS
// ========================================
try {
    // NOTA: Tabla ma_clientes YA EXISTE - NO se crea

    // Tabla: Contactos del Cliente
    db_query("CREATE TABLE IF NOT EXISTS clie_contactos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        cliente_id INT NOT NULL,
        empresa_id INT NOT NULL,
        nombre_completo VARCHAR(255) NOT NULL,
        cargo VARCHAR(100),
        email VARCHAR(255),
        telefono VARCHAR(50),
        rol ENUM('compras', 'administracion', 'ti', 'finanzas', 'gerencia', 'otro') DEFAULT 'otro',
        recibe_facturas TINYINT(1) DEFAULT 0,
        responsable_pago TINYINT(1) DEFAULT 0,
        notas TEXT,
        activo TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (cliente_id) REFERENCES ma_clientes(id) ON DELETE CASCADE,
        FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
        INDEX idx_cliente (cliente_id),
        INDEX idx_empresa (empresa_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Tabla: Sucursales del Cliente
    db_query("CREATE TABLE IF NOT EXISTS clie_sucursales (
        id INT AUTO_INCREMENT PRIMARY KEY,
        cliente_id INT NOT NULL,
        empresa_id INT NOT NULL,
        nombre VARCHAR(255) NOT NULL,
        responsable VARCHAR(255),
        direccion TEXT,
        ciudad VARCHAR(100),
        region VARCHAR(100),
        pais_id INT,
        telefono VARCHAR(50),
        email VARCHAR(255),
        tipo ENUM('fiscal', 'comercial', 'despacho', 'servicio') DEFAULT 'comercial',
        horario_atencion TEXT,
        latitud DECIMAL(10, 8),
        longitud DECIMAL(11, 8),
        observaciones TEXT,
        activo TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (cliente_id) REFERENCES ma_clientes(id) ON DELETE CASCADE,
        FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
        FOREIGN KEY (pais_id) REFERENCES paises(id) ON DELETE SET NULL,
        INDEX idx_cliente (cliente_id),
        INDEX idx_tipo (tipo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Tabla: Documentos Electrónicos del Cliente
    db_query("CREATE TABLE IF NOT EXISTS clie_documentos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        cliente_id INT NOT NULL,
        empresa_id INT NOT NULL,
        tipo_documento ENUM('factura', 'boleta', 'nota_credito', 'nota_debito', 'guia_despacho', 'cotizacion') NOT NULL,
        numero_documento VARCHAR(100) NOT NULL,
        fecha_emision DATE NOT NULL,
        fecha_vencimiento DATE,
        monto_neto DECIMAL(18,2) DEFAULT 0,
        monto_iva DECIMAL(18,2) DEFAULT 0,
        monto_total DECIMAL(18,2) DEFAULT 0,
        estado ENUM('emitida', 'pagada', 'vencida', 'anulada') DEFAULT 'emitida',
        observaciones TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (cliente_id) REFERENCES ma_clientes(id) ON DELETE CASCADE,
        FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
        INDEX idx_cliente (cliente_id),
        INDEX idx_tipo (tipo_documento),
        INDEX idx_estado (estado),
        INDEX idx_fecha (fecha_emision)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Tabla: Historial Comercial del Cliente
    db_query("CREATE TABLE IF NOT EXISTS clie_historial (
        id INT AUTO_INCREMENT PRIMARY KEY,
        cliente_id INT NOT NULL,
        empresa_id INT NOT NULL,
        usuario_id INT NOT NULL,
        tipo_evento ENUM('llamada', 'email', 'visita', 'reunion', 'reclamo', 'cotizacion', 'venta', 'pago', 'otro') NOT NULL,
        descripcion TEXT NOT NULL,
        fecha_evento DATETIME NOT NULL,
        resultado VARCHAR(255),
        observaciones TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (cliente_id) REFERENCES ma_clientes(id) ON DELETE CASCADE,
        FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
        INDEX idx_cliente (cliente_id),
        INDEX idx_tipo (tipo_evento),
        INDEX idx_fecha (fecha_evento)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

} catch (Exception $e) {
    error_log("Error creando tablas de clientes: " . $e->getMessage());
}

// Obtener estadísticas
try {
    $where_empresa = $empresa_id ? "empresa_id = " . intval($empresa_id) : "1=1";

    $stats = [
        'total_clientes' => db_get_var("SELECT COUNT(*) FROM ma_clientes WHERE $where_empresa AND activo = 1") ?? 0,
        'nuevos_mes' => db_get_var("SELECT COUNT(*) FROM ma_clientes WHERE $where_empresa AND activo = 1 AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)") ?? 0,
        'inactivos' => db_get_var("SELECT COUNT(*) FROM ma_clientes WHERE $where_empresa AND activo = 0") ?? 0,
        'clase_a' => db_get_var("SELECT COUNT(*) FROM ma_clientes WHERE $where_empresa AND clasificacion_abc = 'A' AND activo = 1") ?? 0
    ];
} catch (Exception $e) {
    $stats = ['total_clientes' => 0, 'nuevos_mes' => 0, 'inactivos' => 0, 'clase_a' => 0];
}

// Obtener datos
try {
    $clientes = db_query("SELECT c.*, p.nombre as pais_nombre
        FROM ma_clientes c
        LEFT JOIN paises p ON c.pais_id = p.id
        WHERE c.$where_empresa
        ORDER BY c.razon_social ASC");
    $paises = db_query("SELECT * FROM paises WHERE activo = 1 ORDER BY nombre ASC");
} catch (Exception $e) {
    $clientes = [];
    $paises = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Clientes - CONECTA ERP</title>

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
.badge-A { background: #10b981; color: white; }
.badge-B { background: #f59e0b; color: white; }
.badge-C { background: #ef4444; color: white; }

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
                    <i class="fas fa-users me-2"></i>
                    Gestión de Clientes
                </h4>
                <small class="text-muted">Módulo completo de administración de clientes</small>
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
                                    <p class="text-muted mb-1" style="font-size: 0.875rem;">Total Clientes</p>
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
                                    <p class="text-muted mb-1" style="font-size: 0.875rem;">Nuevos (30 días)</p>
                                    <h2 class="mb-0"><?php echo number_format($stats['nuevos_mes']); ?></h2>
                                </div>
                                <div class="feature-icon" style="width: 50px; height: 50px; font-size: 1.25rem;">
                                    <i class="fas fa-user-plus"></i>
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
                                    <p class="text-muted mb-1" style="font-size: 0.875rem;">Clase A</p>
                                    <h2 class="mb-0"><?php echo number_format($stats['clase_a']); ?></h2>
                                </div>
                                <div class="feature-icon" style="width: 50px; height: 50px; font-size: 1.25rem;">
                                    <i class="fas fa-star"></i>
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
                                    <p class="text-muted mb-1" style="font-size: 0.875rem;">Inactivos</p>
                                    <h2 class="mb-0"><?php echo number_format($stats['inactivos']); ?></h2>
                                </div>
                                <div class="feature-icon" style="width: 50px; height: 50px; font-size: 1.25rem;">
                                    <i class="fas fa-user-slash"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Barra de acciones -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row align-items-center g-3">
                        <div class="col-md-3">
                            <button class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#modalCrearCliente">
                                <i class="fas fa-plus me-2"></i>Nuevo Cliente
                            </button>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" id="filterClasificacion">
                                <option value="">Todas las clases</option>
                                <option value="A">Clase A</option>
                                <option value="B">Clase B</option>
                                <option value="C">Clase C</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" id="filterPais">
                                <option value="">Todos los países</option>
                                <?php foreach ($paises as $pais): ?>
                                    <option value="<?php echo $pais['id']; ?>"><?php echo htmlspecialchars($pais['nombre']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" id="filterEstado">
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

            <!-- Lista de Clientes -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="fas fa-list me-2"></i>Lista de Clientes</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
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
                                            <button class="btn btn-sm btn-primary action-btn" onclick="verFichaCliente(<?php echo $cliente['id']; ?>)" title="Ver Ficha 360°">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-warning action-btn" onclick="editarCliente(<?php echo $cliente['id']; ?>)" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-info action-btn" onclick="verEstadoCuenta(<?php echo $cliente['id']; ?>)" title="Estado de Cuenta">
                                                <i class="fas fa-file-invoice-dollar"></i>
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
        </main>
    </div>

    <!-- MODALES -->

    <!-- MODAL: CREAR CLIENTE (8 SECCIONES) -->
    <div class="modal fade" id="modalCrearCliente" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Nuevo Cliente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formCrearCliente">
                        <!-- TABS para las 8 secciones -->
                        <ul class="nav nav-tabs mb-3" id="clienteTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="tab1" data-bs-toggle="tab" data-bs-target="#seccion1" type="button">
                                    1. Identificación
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="tab2" data-bs-toggle="tab" data-bs-target="#seccion2" type="button">
                                    2. Tributaria
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="tab3" data-bs-toggle="tab" data-bs-target="#seccion3" type="button">
                                    3. Contacto
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="tab4" data-bs-toggle="tab" data-bs-target="#seccion4" type="button">
                                    4. Dirección
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="tab5" data-bs-toggle="tab" data-bs-target="#seccion5" type="button">
                                    5. Comercial
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content">
                            <!-- SECCIÓN 1: Identificación -->
                            <div class="tab-pane fade show active" id="seccion1">
                                <div class="section-title">Identificación del Cliente</div>
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label">Tipo de Cliente *</label>
                                        <select class="form-select" name="tipo_cliente" required>
                                            <option value="empresa">Empresa</option>
                                            <option value="persona">Persona</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">País *</label>
                                        <select class="form-select" name="pais_id" id="paisSelect" required>
                                            <option value="">Seleccione...</option>
                                            <?php foreach ($paises as $pais): ?>
                                                <option value="<?php echo $pais['id']; ?>" data-doc="<?php echo htmlspecialchars($pais['tipo_documento']); ?>">
                                                    <?php echo htmlspecialchars($pais['nombre']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Tipo Documento *</label>
                                        <input type="text" class="form-control" name="tipo_documento" id="tipoDocumento" value="RUT" readonly>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Documento *</label>
                                        <input type="text" class="form-control" name="documento" id="documento" required>
                                        <small class="text-muted">Ej: 12.345.678-9</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Razón Social *</label>
                                        <input type="text" class="form-control" name="razon_social" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Nombre Comercial / Fantasía</label>
                                        <input type="text" class="form-control" name="nombre_comercial">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Giro / Actividad Económica *</label>
                                        <input type="text" class="form-control" name="giro" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Actividad Económica</label>
                                        <input type="text" class="form-control" name="actividad_economica">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Sitio Web</label>
                                        <input type="url" class="form-control" name="sitio_web">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Clasificación ABC</label>
                                        <select class="form-select" name="clasificacion_abc">
                                            <option value="N/A">N/A</option>
                                            <option value="A">Clase A</option>
                                            <option value="B">Clase B</option>
                                            <option value="C">Clase C</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Estado</label>
                                        <select class="form-select" name="activo">
                                            <option value="1">Activo</option>
                                            <option value="0">Inactivo</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- SECCIÓN 2: Información Tributaria -->
                            <div class="tab-pane fade" id="seccion2">
                                <div class="section-title">Información Tributaria</div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Régimen Tributario</label>
                                        <select class="form-select" name="regimen_tributario">
                                            <option value="">Seleccione...</option>
                                            <option value="general">General</option>
                                            <option value="simplificado">Simplificado</option>
                                            <option value="exento">Exento</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Código Actividad Económica</label>
                                        <input type="text" class="form-control" name="codigo_actividad">
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check mt-4">
                                            <input class="form-check-input" type="checkbox" name="obligado_factura" id="obligadoFactura">
                                            <label class="form-check-label" for="obligadoFactura">
                                                Obligado a Emitir Documentos Electrónicos
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check mt-4">
                                            <input class="form-check-input" type="checkbox" name="exento_iva" id="exentoIVA">
                                            <label class="form-check-label" for="exentoIVA">
                                                Exento de IVA
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label">Observaciones Tributarias</label>
                                        <textarea class="form-control" name="obs_tributarias" rows="3"></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- SECCIÓN 3: Información de Contacto -->
                            <div class="tab-pane fade" id="seccion3">
                                <div class="section-title">Información de Contacto</div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Teléfono Principal *</label>
                                        <input type="text" class="form-control" name="telefono" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Teléfono Alternativo</label>
                                        <input type="text" class="form-control" name="telefono_alt">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Email Principal *</label>
                                        <input type="email" class="form-control" name="email" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Email Facturación</label>
                                        <input type="email" class="form-control" name="email_facturacion">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Email Cobranzas</label>
                                        <input type="email" class="form-control" name="email_cobranzas">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">WhatsApp</label>
                                        <input type="text" class="form-control" name="whatsapp">
                                    </div>
                                </div>
                            </div>

                            <!-- SECCIÓN 4: Dirección -->
                            <div class="tab-pane fade" id="seccion4">
                                <div class="section-title">Dirección Fiscal y Comercial</div>
                                <div class="row g-3">
                                    <div class="col-md-12">
                                        <label class="form-label">Dirección Fiscal *</label>
                                        <textarea class="form-control" name="direccion_fiscal" rows="2" required></textarea>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label">Dirección Comercial</label>
                                        <textarea class="form-control" name="direccion_comercial" rows="2"></textarea>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Ciudad</label>
                                        <input type="text" class="form-control" name="ciudad">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Región / Estado</label>
                                        <input type="text" class="form-control" name="region">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Comuna / Municipio</label>
                                        <input type="text" class="form-control" name="comuna">
                                    </div>
                                </div>
                            </div>

                            <!-- SECCIÓN 5: Condiciones Comerciales -->
                            <div class="tab-pane fade" id="seccion5">
                                <div class="section-title">Condiciones Comerciales</div>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Límite de Crédito</label>
                                        <input type="number" class="form-control" name="limite_credito" step="0.01" value="0">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Días de Crédito</label>
                                        <input type="number" class="form-control" name="dias_credito" value="0">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Descuento Global (%)</label>
                                        <input type="number" class="form-control" name="descuento_global" step="0.01" value="0">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Forma de Pago</label>
                                        <select class="form-select" name="forma_pago">
                                            <option value="contado">Contado</option>
                                            <option value="30_dias">30 días</option>
                                            <option value="60_dias">60 días</option>
                                            <option value="90_dias">90 días</option>
                                            <option value="personalizada">Personalizada</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Segmento</label>
                                        <input type="text" class="form-control" name="segmento" placeholder="Ej: Retail, Mayorista, VIP">
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label">Observaciones</label>
                                        <textarea class="form-control" name="observaciones" rows="3"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" form="formCrearCliente" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Guardar Cliente
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL: FICHA 360° DEL CLIENTE -->
    <div class="modal fade" id="modalFichaCliente" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user me-2"></i>Ficha Completa del Cliente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <ul class="nav nav-tabs mb-3" id="fichaTabs" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#fichaDatos">Datos Generales</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#fichaTributaria">Info Tributaria</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#fichaComercial">Condiciones</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#fichaContactos">Contactos</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#fichaSucursales">Sucursales</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#fichaHistorial">Historial</button>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <!-- Tab: Datos Generales -->
                        <div class="tab-pane fade show active" id="fichaDatos">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <label>Razón Social</label>
                                        <div class="value" id="fichaRazonSocial"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <label>Documento</label>
                                        <div class="value" id="fichaDocumento"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <label>Giro</label>
                                        <div class="value" id="fichaGiro"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <label>País</label>
                                        <div class="value" id="fichaPais"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <label>Email</label>
                                        <div class="value" id="fichaEmail"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <label>Teléfono</label>
                                        <div class="value" id="fichaTelefono"></div>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="info-item">
                                        <label>Dirección Fiscal</label>
                                        <div class="value" id="fichaDireccion"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tab: Info Tributaria -->
                        <div class="tab-pane fade" id="fichaTributaria">
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Información tributaria del cliente
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tab: Condiciones Comerciales -->
                        <div class="tab-pane fade" id="fichaComercial">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="info-item">
                                        <label>Límite de Crédito</label>
                                        <div class="value" id="fichaCredito"></div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="info-item">
                                        <label>Días de Crédito</label>
                                        <div class="value" id="fichaDiasCredito"></div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="info-item">
                                        <label>Clasificación</label>
                                        <div class="value" id="fichaClasificacion"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tab: Contactos -->
                        <div class="tab-pane fade" id="fichaContactos">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Lista de contactos del cliente (funcionalidad en desarrollo)
                            </div>
                        </div>

                        <!-- Tab: Sucursales -->
                        <div class="tab-pane fade" id="fichaSucursales">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Sucursales del cliente (funcionalidad en desarrollo)
                            </div>
                        </div>

                        <!-- Tab: Historial -->
                        <div class="tab-pane fade" id="fichaHistorial">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Historial comercial del cliente (funcionalidad en desarrollo)
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary" onclick="imprimirFicha()">
                        <i class="fas fa-print me-2"></i>Imprimir
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/main.js"></script>
    <script>
        // Datos globales
        const clientesData = <?php echo json_encode($clientes); ?>;
        const paisesData = <?php echo json_encode($paises); ?>;

        // ============================================
        // FUNCIONES GLOBALES (SCOPE GLOBAL para onclick)
        // ============================================

        // Ver Ficha 360° del Cliente
        function verFichaCliente(id) {
            const cliente = clientesData.find(c => c.id == id);
            if (cliente) {
                // Cargar datos en la ficha
                document.getElementById('fichaRazonSocial').textContent = cliente.razon_social || 'N/A';
                document.getElementById('fichaDocumento').textContent = cliente.documento || 'N/A';
                document.getElementById('fichaGiro').textContent = cliente.giro || 'N/A';
                document.getElementById('fichaPais').textContent = cliente.pais_nombre || 'N/A';
                document.getElementById('fichaEmail').textContent = cliente.email || 'N/A';
                document.getElementById('fichaTelefono').textContent = cliente.telefono || 'N/A';
                document.getElementById('fichaDireccion').textContent = cliente.direccion_fiscal || 'N/A';
                document.getElementById('fichaCredito').textContent = '$' + parseFloat(cliente.limite_credito || 0).toLocaleString();
                document.getElementById('fichaDiasCredito').textContent = cliente.dias_credito || '0';
                document.getElementById('fichaClasificacion').innerHTML =
                    '<span class="badge badge-' + cliente.clasificacion_abc + '">' + cliente.clasificacion_abc + '</span>';

                // Mostrar modal
                const modal = new bootstrap.Modal(document.getElementById('modalFichaCliente'));
                modal.show();
            }
        }

        // Editar Cliente
        function editarCliente(id) {
            const cliente = clientesData.find(c => c.id == id);
            if (cliente) {
                alert('Editar cliente: ' + cliente.razon_social + '\nFuncionalidad de edición en desarrollo');
            }
        }

        // Ver Estado de Cuenta
        function verEstadoCuenta(id) {
            const cliente = clientesData.find(c => c.id == id);
            if (cliente) {
                alert('Estado de cuenta de: ' + cliente.razon_social + '\nFuncionalidad en desarrollo');
            }
        }

        // Imprimir Ficha
        function imprimirFicha() {
            window.print();
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

            // Búsqueda
            const searchInput = document.getElementById('searchClientes');
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();
                    const table = document.querySelector('table tbody');
                    const rows = table.querySelectorAll('tr');
                    rows.forEach(row => {
                        const text = row.textContent.toLowerCase();
                        row.style.display = text.includes(searchTerm) ? '' : 'none';
                    });
                });
            }

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
