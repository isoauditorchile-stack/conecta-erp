<?php
// ============================================
// CONECTA ERP - GESTIÓN DE PROVEEDORES
// Módulo completo de gestión de proveedores
// ============================================

require_once '../../config/database.php';
require_once '../../config/auth.php';

is_logged_in();

$usuario_actual = $_SESSION['usuario_id'] ?? 1;
$empresa_id = $_SESSION['empresa_id'] ?? 1;

// ============================================
// PROCESAR FORMULARIO DE CREACIÓN
// ============================================
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['razon_social'])) {
    try {
        db_begin_transaction();

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
            $usuario_actual
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

        // 6. Auditoría
        db_query("INSERT INTO auditoria_proveedores (
            proveedor_id, usuario_id, accion, fecha, ip_usuario,
            campo_modificado, valor_anterior, valor_nuevo
        ) VALUES (?, ?, ?, NOW(), ?, ?, ?, ?)", [
            $proveedor_id,
            $usuario_actual,
            'CREAR',
            $_SERVER['REMOTE_ADDR'] ?? '',
            'proveedor',
            '',
            $_POST['razon_social']
        ]);

        db_commit();

        header("Location: " . $_SERVER['PHP_SELF'] . "?success=proveedor_creado");
        exit;

    } catch (Exception $e) {
        db_rollback();
        $error = "Error al crear proveedor: " . $e->getMessage();
    }
}

// Mensaje de éxito
if (isset($_GET['success']) && $_GET['success'] === 'proveedor_creado') {
    $success = "¡Proveedor creado exitosamente!";
}

// ============================================
// AUTO-CREAR TABLAS SQL
// ============================================
try {
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
    // Silenciar errores de creación de tablas si ya existen
}

// ============================================
// CONSULTAR DATOS
// ============================================

// Consultar proveedores con JOIN a países
$proveedores = db_query("
    SELECT p.*, pa.nombre as pais_nombre
    FROM proveedores p
    LEFT JOIN paises pa ON p.pais_id = pa.id
    WHERE p.empresa_id = ?
    ORDER BY p.razon_social ASC
", [$empresa_id]);

// Consultar países para select
$paises = db_query("SELECT * FROM paises ORDER BY nombre ASC");

// Estadísticas
$stats_total = db_get_var("SELECT COUNT(*) FROM proveedores WHERE empresa_id = ?", [$empresa_id]) ?? 0;
$stats_activos = db_get_var("SELECT COUNT(*) FROM proveedores WHERE empresa_id = ? AND estado = 'activo'", [$empresa_id]) ?? 0;
$stats_empresas = db_get_var("SELECT COUNT(*) FROM proveedores WHERE empresa_id = ? AND tipo_proveedor = 'empresa'", [$empresa_id]) ?? 0;
$stats_personas = db_get_var("SELECT COUNT(*) FROM proveedores WHERE empresa_id = ? AND tipo_proveedor = 'persona'", [$empresa_id]) ?? 0;

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Proveedores - ConectaERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --sidebar-width: 250px;
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --success-color: #27ae60;
            --danger-color: #e74c3c;
            --warning-color: #f39c12;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #ecf0f1;
            overflow-x: hidden;
        }

        .main-content {
            margin-left: var(--sidebar-width);
            padding: 20px;
            transition: margin-left 0.3s ease;
        }

        body.fullscreen-mode .main-content {
            margin-left: 0;
        }

        /* Header */
        .page-header {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-title {
            font-size: 28px;
            font-weight: 600;
            color: var(--primary-color);
            margin: 0;
        }

        /* Stats Cards */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            font-size: 40px;
            width: 70px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            margin-right: 15px;
        }

        .stat-card.total .stat-icon { background: #3498db20; color: #3498db; }
        .stat-card.activos .stat-icon { background: #27ae6020; color: #27ae60; }
        .stat-card.empresas .stat-icon { background: #9b59b620; color: #9b59b6; }
        .stat-card.personas .stat-icon { background: #e67e2220; color: #e67e22; }

        .stat-info h3 {
            font-size: 32px;
            font-weight: 700;
            margin: 0;
            color: var(--primary-color);
        }

        .stat-info p {
            margin: 0;
            color: #7f8c8d;
            font-size: 14px;
        }

        /* Content Box */
        .content-box {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 25px;
        }

        /* Tabs */
        .nav-tabs {
            border-bottom: 2px solid #e0e0e0;
            margin-bottom: 20px;
        }

        .nav-tabs .nav-link {
            border: none;
            color: #7f8c8d;
            font-weight: 500;
            padding: 12px 25px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .nav-tabs .nav-link:hover {
            color: var(--secondary-color);
            border-color: transparent;
        }

        .nav-tabs .nav-link.active {
            color: var(--secondary-color);
            border-bottom: 3px solid var(--secondary-color);
            background: transparent;
        }

        /* Buttons */
        .btn-primary {
            background: var(--secondary-color);
            border: none;
            padding: 10px 20px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
        }

        /* Table */
        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
        }

        table {
            margin-bottom: 0;
        }

        table thead {
            background: var(--primary-color);
            color: white;
        }

        table thead th {
            border: none;
            padding: 15px;
            font-weight: 500;
        }

        table tbody tr {
            transition: background 0.3s ease;
        }

        table tbody tr:hover {
            background: #f8f9fa;
        }

        table tbody td {
            padding: 12px 15px;
            vertical-align: middle;
        }

        /* Badges */
        .badge {
            padding: 6px 12px;
            font-weight: 500;
            font-size: 12px;
        }

        .badge.bg-success { background: var(--success-color) !important; }
        .badge.bg-danger { background: var(--danger-color) !important; }

        /* Action Buttons */
        .btn-action {
            width: 32px;
            height: 32px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            margin: 0 2px;
        }

        .btn-action.btn-view {
            background: #3498db;
            color: white;
        }

        .btn-action.btn-view:hover {
            background: #2980b9;
            transform: scale(1.1);
        }

        .btn-action.btn-edit {
            background: #f39c12;
            color: white;
        }

        .btn-action.btn-edit:hover {
            background: #e67e22;
            transform: scale(1.1);
        }

        /* Form Sections */
        .form-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .form-section h5 {
            color: var(--primary-color);
            margin-bottom: 15px;
            font-weight: 600;
        }

        /* Modal */
        .modal-header {
            background: var(--primary-color);
            color: white;
        }

        .modal-header .btn-close {
            filter: brightness(0) invert(1);
        }

        /* Alerts */
        .alert {
            border-radius: 8px;
            border: none;
        }

        /* Search */
        .search-box {
            position: relative;
            margin-bottom: 20px;
        }

        .search-box input {
            padding-left: 40px;
        }

        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #7f8c8d;
        }
    </style>
</head>
<body>
    <?php include '../../includes/sidebar_user.php'; ?>

    <div class="main-content">
        <!-- Header -->
        <div class="page-header">
            <div>
                <h1 class="page-title">
                    <i class="fas fa-truck-field me-2"></i>
                    Gestión de Proveedores
                </h1>
                <p class="text-muted mb-0">Administración completa de proveedores estilo Softland/SAP</p>
            </div>
            <div>
                <button class="btn btn-outline-primary me-2" id="fullscreenToggle">
                    <i class="fas fa-expand"></i>
                </button>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrearProveedor">
                    <i class="fas fa-plus me-2"></i>Nuevo Proveedor
                </button>
            </div>
        </div>

        <!-- Mensajes -->
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i>
                <?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Estadísticas -->
        <div class="stats-container">
            <div class="stat-card total">
                <div class="stat-icon"><i class="fas fa-truck-field"></i></div>
                <div class="stat-info">
                    <h3><?= $stats_total ?></h3>
                    <p>Total Proveedores</p>
                </div>
            </div>
            <div class="stat-card activos">
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                <div class="stat-info">
                    <h3><?= $stats_activos ?></h3>
                    <p>Proveedores Activos</p>
                </div>
            </div>
            <div class="stat-card empresas">
                <div class="stat-icon"><i class="fas fa-building"></i></div>
                <div class="stat-info">
                    <h3><?= $stats_empresas ?></h3>
                    <p>Empresas</p>
                </div>
            </div>
            <div class="stat-card personas">
                <div class="stat-icon"><i class="fas fa-user"></i></div>
                <div class="stat-info">
                    <h3><?= $stats_personas ?></h3>
                    <p>Personas</p>
                </div>
            </div>
        </div>

        <!-- Listado de Proveedores -->
        <div class="content-box">
            <!-- Búsqueda -->
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" class="form-control" id="searchProveedores"
                       placeholder="Buscar por razón social, documento, email...">
            </div>

            <!-- Tabla -->
            <div class="table-responsive">
                <table class="table">
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
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
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
                                    <td><strong>#<?= $proveedor['id'] ?></strong></td>
                                    <td>
                                        <strong><?= htmlspecialchars($proveedor['razon_social']) ?></strong>
                                        <?php if ($proveedor['nombre_fantasia']): ?>
                                            <br><small class="text-muted"><?= htmlspecialchars($proveedor['nombre_fantasia']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?= htmlspecialchars($proveedor['tipo_documento']) ?></span>
                                        <?= htmlspecialchars($proveedor['documento']) ?>
                                    </td>
                                    <td><?= htmlspecialchars($proveedor['pais_nombre'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($contacto['email_principal'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($contacto['telefono'] ?? 'N/A') ?></td>
                                    <td>
                                        <i class="fas fa-<?= $proveedor['tipo_proveedor'] === 'empresa' ? 'building' : 'user' ?> me-1"></i>
                                        <?= ucfirst($proveedor['tipo_proveedor']) ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $proveedor['estado'] === 'activo' ? 'success' : 'danger' ?>">
                                            <?= ucfirst($proveedor['estado']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn-action btn-view" onclick="verProveedor(<?= $proveedor['id'] ?>)"
                                                title="Ver Ficha Completa">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn-action btn-edit" onclick="editarProveedor(<?= $proveedor['id'] ?>)"
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
                                <div class="form-section">
                                    <h5><i class="fas fa-id-card me-2"></i>Identificación del Proveedor</h5>
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
                                                    <option value="<?= $pais['id'] ?>"
                                                            data-doc="<?= htmlspecialchars($pais['tipo_documento'] ?? 'RUT') ?>">
                                                        <?= htmlspecialchars($pais['nombre']) ?>
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
                                                <option value="es">Español</option>
                                                <option value="en">English</option>
                                                <option value="pt">Português</option>
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
                            </div>

                            <!-- SECCIÓN 2: TRIBUTARIA -->
                            <div class="tab-pane fade" id="seccion2">
                                <div class="form-section">
                                    <h5><i class="fas fa-file-invoice-dollar me-2"></i>Información Tributaria</h5>
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
                            </div>

                            <!-- SECCIÓN 3: CONTACTO -->
                            <div class="tab-pane fade" id="seccion3">
                                <div class="form-section">
                                    <h5><i class="fas fa-address-book me-2"></i>Información de Contacto</h5>
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
                                                <option value="email">Email</option>
                                                <option value="telefono">Teléfono</option>
                                                <option value="whatsapp">WhatsApp</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- SECCIÓN 4: DIRECCIÓN -->
                            <div class="tab-pane fade" id="seccion4">
                                <div class="form-section">
                                    <h5><i class="fas fa-map-marker-alt me-2"></i>Dirección</h5>
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
                                                <option value="fiscal">Fiscal</option>
                                                <option value="comercial">Comercial</option>
                                                <option value="despacho">Despacho</option>
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
                            </div>

                            <!-- SECCIÓN 5: COMERCIAL -->
                            <div class="tab-pane fade" id="seccion5">
                                <div class="form-section">
                                    <h5><i class="fas fa-handshake me-2"></i>Condiciones Comerciales</h5>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label">Plazo de Pago</label>
                                            <select class="form-select" name="plazo_pago">
                                                <option value="contado">Contado</option>
                                                <option value="30" selected>30 días</option>
                                                <option value="60">60 días</option>
                                                <option value="90">90 días</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Forma de Pago</label>
                                            <select class="form-select" name="forma_pago">
                                                <option value="transferencia">Transferencia</option>
                                                <option value="cheque">Cheque</option>
                                                <option value="efectivo">Efectivo</option>
                                                <option value="tarjeta">Tarjeta</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Moneda Preferente</label>
                                            <select class="form-select" name="moneda_preferente">
                                                <option value="CLP">CLP - Peso Chileno</option>
                                                <option value="USD">USD - Dólar</option>
                                                <option value="EUR">EUR - Euro</option>
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
                        <li class="nav-item">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabSucursales">
                                Sucursales
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabBancos">
                                Bancos
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabAuditoria">
                                Auditoría
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content mt-3">
                        <!-- Tab Datos Generales -->
                        <div class="tab-pane fade show active" id="tabDatos">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <strong>Razón Social:</strong>
                                    <p id="detalleRazonSocial" class="mb-2">-</p>
                                </div>
                                <div class="col-md-6">
                                    <strong>Nombre de Fantasía:</strong>
                                    <p id="detalleNombreFantasia" class="mb-2">-</p>
                                </div>
                                <div class="col-md-4">
                                    <strong>Documento:</strong>
                                    <p id="detalleDocumento" class="mb-2">-</p>
                                </div>
                                <div class="col-md-4">
                                    <strong>País:</strong>
                                    <p id="detallePais" class="mb-2">-</p>
                                </div>
                                <div class="col-md-4">
                                    <strong>Estado:</strong>
                                    <p id="detalleEstado" class="mb-2">-</p>
                                </div>
                            </div>
                        </div>

                        <!-- Tab Contacto -->
                        <div class="tab-pane fade" id="tabContacto">
                            <p>Información de contacto del proveedor (en desarrollo)</p>
                        </div>

                        <!-- Tab Comercial -->
                        <div class="tab-pane fade" id="tabComercial">
                            <p>Condiciones comerciales (en desarrollo)</p>
                        </div>

                        <!-- Tab Sucursales -->
                        <div class="tab-pane fade" id="tabSucursales">
                            <p>Listado de sucursales (en desarrollo)</p>
                        </div>

                        <!-- Tab Bancos -->
                        <div class="tab-pane fade" id="tabBancos">
                            <p>Cuentas bancarias (en desarrollo)</p>
                        </div>

                        <!-- Tab Auditoría -->
                        <div class="tab-pane fade" id="tabAuditoria">
                            <p>Historial de cambios (en desarrollo)</p>
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
                document.getElementById('detalleDocumento').textContent =
                    proveedor.tipo_documento + ': ' + proveedor.documento;
                document.getElementById('detallePais').textContent = proveedor.pais_nombre || 'N/A';
                document.getElementById('detalleEstado').textContent =
                    '<span class="badge bg-' + (proveedor.estado === 'activo' ? 'success' : 'danger') + '">' +
                    proveedor.estado.toUpperCase() + '</span>';

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

            // Búsqueda
            const searchInput = document.getElementById('searchProveedores');
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
