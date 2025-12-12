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

// Crear empleado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nombres'])) {
    try {
        // Manejo de foto
        $foto_url = null;
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/../../uploads/empleados/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $file_ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
            $allowed_exts = ['jpg', 'jpeg', 'png'];

            if (in_array($file_ext, $allowed_exts) && $_FILES['foto']['size'] <= 5242880) {
                $empleado_temp_id = uniqid();
                $foto_name = 'empleado_' . $empleado_temp_id . '_foto.' . $file_ext;
                $foto_path = $upload_dir . $foto_name;

                if (move_uploaded_file($_FILES['foto']['tmp_name'], $foto_path)) {
                    $foto_url = '/uploads/empleados/' . $foto_name;
                }
            }
        }

        // Insertar empleado
        db_query("INSERT INTO ma_empleados (
            empresa_id, nombres, apellidos, rut, validador_rut, nacionalidad,
            fecha_nacimiento, genero, estado_civil, direccion, ciudad, region,
            telefono, email, contacto_emergencia, telefono_emergencia, relacion_emergencia,
            fecha_ingreso, cargo, area_departamento, tipo_contrato, horario,
            supervisor_id, centro_costo_id, tipo_trabajador, sueldo_base,
            tipo_remuneracion, foto_url, estado, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", [
            $empresa_id,
            $_POST['nombres'],
            $_POST['apellidos'],
            $_POST['rut'],
            $_POST['validador_rut'] ?? '',
            $_POST['nacionalidad'] ?? '',
            $_POST['fecha_nacimiento'] ?? null,
            $_POST['genero'] ?? '',
            $_POST['estado_civil'] ?? '',
            $_POST['direccion'] ?? '',
            $_POST['ciudad'] ?? '',
            $_POST['region'] ?? '',
            $_POST['telefono'] ?? '',
            $_POST['email'] ?? '',
            $_POST['contacto_emergencia'] ?? '',
            $_POST['telefono_emergencia'] ?? '',
            $_POST['relacion_emergencia'] ?? '',
            $_POST['fecha_ingreso'] ?? null,
            $_POST['cargo'] ?? '',
            $_POST['area_departamento'] ?? '',
            $_POST['tipo_contrato'] ?? '',
            $_POST['horario'] ?? '',
            $_POST['supervisor_id'] ?? null,
            $_POST['centro_costo_id'] ?? null,
            $_POST['tipo_trabajador'] ?? '',
            $_POST['sueldo_base'] ?? 0,
            $_POST['tipo_remuneracion'] ?? 'mensual',
            $foto_url,
            'activo',
            $user_id
        ]);

        $empleado_id = db_get_var("SELECT LAST_INSERT_ID()");

        // Registrar en auditoría
        db_query("INSERT INTO emp_auditoria (empleado_id, usuario_id, accion, fecha, ip_usuario, detalles)
                  VALUES (?, ?, 'CREAR', NOW(), ?, ?)", [
            $empleado_id, $user_id, $_SERVER['REMOTE_ADDR'] ?? '',
            'Empleado creado: ' . $_POST['nombres'] . ' ' . $_POST['apellidos']
        ]);

        header("Location: " . $_SERVER['PHP_SELF'] . "?success=empleado_creado");
        exit;

    } catch (Exception $e) {
        $error = "Error al crear empleado: " . $e->getMessage();
    }
}

// Mensaje de éxito
if (isset($_GET['success']) && $_GET['success'] === 'empleado_creado') {
    $success = "Empleado creado exitosamente";
}

// ========================================
// AUTO-CREAR TABLAS (sin datos embebidos)
// ========================================
try {
    // Tabla: Nacionalidades
    db_query("CREATE TABLE IF NOT EXISTS nacionalidades (
        id INT AUTO_INCREMENT PRIMARY KEY,
        codigo VARCHAR(5) UNIQUE NOT NULL,
        nombre VARCHAR(100) NOT NULL,
        gentilicio VARCHAR(100),
        activo TINYINT(1) DEFAULT 1
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Tabla: Estados Civiles
    db_query("CREATE TABLE IF NOT EXISTS estados_civiles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        codigo VARCHAR(20) UNIQUE NOT NULL,
        nombre VARCHAR(50) NOT NULL,
        activo TINYINT(1) DEFAULT 1
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Tabla: Tipos de Contrato
    db_query("CREATE TABLE IF NOT EXISTS tipos_contrato (
        id INT AUTO_INCREMENT PRIMARY KEY,
        codigo VARCHAR(20) UNIQUE NOT NULL,
        nombre VARCHAR(100) NOT NULL,
        activo TINYINT(1) DEFAULT 1
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Tabla: Tipos de Trabajador
    db_query("CREATE TABLE IF NOT EXISTS tipos_trabajador (
        id INT AUTO_INCREMENT PRIMARY KEY,
        codigo VARCHAR(20) UNIQUE NOT NULL,
        nombre VARCHAR(100) NOT NULL,
        activo TINYINT(1) DEFAULT 1
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Tabla: AFPs
    db_query("CREATE TABLE IF NOT EXISTS afps (
        id INT AUTO_INCREMENT PRIMARY KEY,
        codigo VARCHAR(20) UNIQUE NOT NULL,
        nombre VARCHAR(100) NOT NULL,
        porcentaje DECIMAL(5,2),
        activo TINYINT(1) DEFAULT 1
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Tabla: ISAPREs
    db_query("CREATE TABLE IF NOT EXISTS isapres (
        id INT AUTO_INCREMENT PRIMARY KEY,
        codigo VARCHAR(20) UNIQUE NOT NULL,
        nombre VARCHAR(100) NOT NULL,
        activo TINYINT(1) DEFAULT 1
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Tabla principal: ma_empleados (ya existe, pero aseguramos estructura)
    db_query("CREATE TABLE IF NOT EXISTS ma_empleados (
        id INT AUTO_INCREMENT PRIMARY KEY,
        empresa_id INT NOT NULL,
        nombres VARCHAR(255) NOT NULL,
        apellidos VARCHAR(255) NOT NULL,
        rut VARCHAR(50) UNIQUE NOT NULL,
        validador_rut VARCHAR(10),
        nacionalidad VARCHAR(100),
        fecha_nacimiento DATE,
        genero ENUM('masculino', 'femenino', 'otro') DEFAULT 'masculino',
        estado_civil VARCHAR(50),
        direccion TEXT,
        ciudad VARCHAR(100),
        region VARCHAR(100),
        telefono VARCHAR(50),
        email VARCHAR(255),
        contacto_emergencia VARCHAR(255),
        telefono_emergencia VARCHAR(50),
        relacion_emergencia VARCHAR(100),
        fecha_ingreso DATE,
        fecha_termino DATE,
        cargo VARCHAR(100),
        area_departamento VARCHAR(100),
        tipo_contrato VARCHAR(50),
        horario VARCHAR(100),
        supervisor_id INT,
        centro_costo_id INT,
        tipo_trabajador VARCHAR(50),
        sueldo_base DECIMAL(15,2) DEFAULT 0,
        tipo_remuneracion ENUM('mensual', 'diaria', 'hora') DEFAULT 'mensual',
        foto_url VARCHAR(500),
        estado ENUM('activo', 'inactivo', 'suspendido') DEFAULT 'activo',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        created_by INT,
        updated_by INT,
        INDEX idx_empresa (empresa_id),
        INDEX idx_rut (rut),
        INDEX idx_estado (estado)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Documentos del empleado
    db_query("CREATE TABLE IF NOT EXISTS emp_documentos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        empleado_id INT NOT NULL,
        tipo_documento VARCHAR(100) NOT NULL,
        nombre_archivo VARCHAR(255) NOT NULL,
        ruta_archivo VARCHAR(500) NOT NULL,
        fecha_subida DATETIME DEFAULT CURRENT_TIMESTAMP,
        fecha_vencimiento DATE,
        subido_por INT,
        FOREIGN KEY (empleado_id) REFERENCES ma_empleados(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Remuneraciones
    db_query("CREATE TABLE IF NOT EXISTS emp_remuneraciones (
        id INT AUTO_INCREMENT PRIMARY KEY,
        empleado_id INT NOT NULL,
        periodo VARCHAR(7),
        sueldo_base DECIMAL(15,2),
        gratificacion DECIMAL(15,2),
        horas_extra DECIMAL(15,2),
        bonos DECIMAL(15,2),
        comisiones DECIMAL(15,2),
        descuentos DECIMAL(15,2),
        anticipos DECIMAL(15,2),
        total_haberes DECIMAL(15,2),
        total_descuentos DECIMAL(15,2),
        liquido_pagar DECIMAL(15,2),
        FOREIGN KEY (empleado_id) REFERENCES ma_empleados(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Historial laboral
    db_query("CREATE TABLE IF NOT EXISTS emp_historial (
        id INT AUTO_INCREMENT PRIMARY KEY,
        empleado_id INT NOT NULL,
        fecha DATE NOT NULL,
        tipo_evento VARCHAR(100) NOT NULL,
        detalle TEXT,
        usuario_registro INT,
        archivo_adjunto VARCHAR(500),
        FOREIGN KEY (empleado_id) REFERENCES ma_empleados(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Carga familiar
    db_query("CREATE TABLE IF NOT EXISTS emp_carga_familiar (
        id INT AUTO_INCREMENT PRIMARY KEY,
        empleado_id INT NOT NULL,
        nombre VARCHAR(255) NOT NULL,
        rut VARCHAR(50),
        parentesco VARCHAR(50),
        fecha_nacimiento DATE,
        escolaridad VARCHAR(100),
        documento_url VARCHAR(500),
        FOREIGN KEY (empleado_id) REFERENCES ma_empleados(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Cuentas bancarias
    db_query("CREATE TABLE IF NOT EXISTS emp_cuentas_bancarias (
        id INT AUTO_INCREMENT PRIMARY KEY,
        empleado_id INT NOT NULL,
        banco VARCHAR(100),
        tipo_cuenta VARCHAR(50),
        numero_cuenta VARCHAR(50),
        titular VARCHAR(255),
        rut_titular VARCHAR(50),
        comprobante_url VARCHAR(500),
        FOREIGN KEY (empleado_id) REFERENCES ma_empleados(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // AFP / Previsión
    db_query("CREATE TABLE IF NOT EXISTS emp_prevision (
        id INT AUTO_INCREMENT PRIMARY KEY,
        empleado_id INT NOT NULL UNIQUE,
        afp VARCHAR(100),
        porcentaje_afp DECIMAL(5,2),
        numero_afiliacion_afp VARCHAR(50),
        fecha_afiliacion_afp DATE,
        salud VARCHAR(100),
        plan_salud VARCHAR(100),
        monto_salud DECIMAL(15,2),
        afc_afiliado TINYINT(1) DEFAULT 0,
        afc_tramo VARCHAR(50),
        afc_monto DECIMAL(15,2),
        FOREIGN KEY (empleado_id) REFERENCES ma_empleados(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Adjuntos generales
    db_query("CREATE TABLE IF NOT EXISTS emp_adjuntos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        empleado_id INT NOT NULL,
        nombre_archivo VARCHAR(255) NOT NULL,
        ruta_archivo VARCHAR(500) NOT NULL,
        comentario TEXT,
        fecha_subida DATETIME DEFAULT CURRENT_TIMESTAMP,
        subido_por INT,
        FOREIGN KEY (empleado_id) REFERENCES ma_empleados(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Auditoría
    db_query("CREATE TABLE IF NOT EXISTS emp_auditoria (
        id INT AUTO_INCREMENT PRIMARY KEY,
        empleado_id INT,
        usuario_id INT NOT NULL,
        accion VARCHAR(100) NOT NULL,
        fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
        ip_usuario VARCHAR(50),
        navegador VARCHAR(255),
        detalles TEXT,
        INDEX idx_empleado (empleado_id),
        INDEX idx_fecha (fecha)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

} catch (Exception $e) {
    // Silenciar errores si ya existen
}

// ========================================
// CONSULTAR DATOS
// ========================================

// Consultar empleados
try {
    $empleados = db_query("
        SELECT e.*,
               TIMESTAMPDIFF(YEAR, e.fecha_ingreso, CURDATE()) as anos_empresa,
               TIMESTAMPDIFF(YEAR, e.fecha_nacimiento, CURDATE()) as edad
        FROM ma_empleados e
        WHERE e.empresa_id = ?
        ORDER BY e.apellidos ASC, e.nombres ASC
    ", [$empresa_id]);
} catch (Exception $e) {
    $empleados = [];
}

// Consultar datos para selects (TODO desde SQL, NADA embebido)
try { $nacionalidades = db_query("SELECT * FROM nacionalidades WHERE activo = 1 ORDER BY nombre ASC"); } catch (Exception $e) { $nacionalidades = []; }
try { $estados_civiles = db_query("SELECT * FROM estados_civiles WHERE activo = 1 ORDER BY nombre ASC"); } catch (Exception $e) { $estados_civiles = []; }
try { $tipos_contrato = db_query("SELECT * FROM tipos_contrato WHERE activo = 1 ORDER BY nombre ASC"); } catch (Exception $e) { $tipos_contrato = []; }
try { $tipos_trabajador = db_query("SELECT * FROM tipos_trabajador WHERE activo = 1 ORDER BY nombre ASC"); } catch (Exception $e) { $tipos_trabajador = []; }
try { $afps = db_query("SELECT * FROM afps WHERE activo = 1 ORDER BY nombre ASC"); } catch (Exception $e) { $afps = []; }
try { $isapres = db_query("SELECT * FROM isapres WHERE activo = 1 ORDER BY nombre ASC"); } catch (Exception $e) { $isapres = []; }
try { $centros_costo = db_query("SELECT * FROM ma_centros_costo WHERE empresa_id = ? ORDER BY nombre ASC", [$empresa_id]); } catch (Exception $e) { $centros_costo = []; }

// Estadísticas
try {
    $stats = [
        'total_empleados' => db_get_var("SELECT COUNT(*) FROM ma_empleados WHERE empresa_id = ?", [$empresa_id]) ?? 0,
        'activos' => db_get_var("SELECT COUNT(*) FROM ma_empleados WHERE empresa_id = ? AND estado = 'activo'", [$empresa_id]) ?? 0,
        'hombres' => db_get_var("SELECT COUNT(*) FROM ma_empleados WHERE empresa_id = ? AND genero = 'masculino'", [$empresa_id]) ?? 0,
        'mujeres' => db_get_var("SELECT COUNT(*) FROM ma_empleados WHERE empresa_id = ? AND genero = 'femenino'", [$empresa_id]) ?? 0
    ];
} catch (Exception $e) {
    $stats = [
        'total_empleados' => 0,
        'activos' => 0,
        'hombres' => 0,
        'mujeres' => 0
    ];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Empleados - CONECTA ERP</title>

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
.badge-suspendido { background: #f59e0b; color: white; }

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

.foto-preview {
    width: 180px;
    height: 180px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid var(--primary-color);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.avatar-default {
    width: 180px;
    height: 180px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary-color), #1e40af);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 4rem;
    color: white;
    font-weight: 700;
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
                    Gestión de Empleados
                </h4>
                <small class="text-muted">Módulo completo de administración de empleados</small>
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
                                    <p class="text-muted mb-1" style="font-size: 0.875rem;">Total Empleados</p>
                                    <h2 class="mb-0"><?php echo number_format($stats['total_empleados']); ?></h2>
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
                                    <p class="text-muted mb-1" style="font-size: 0.875rem;">Empleados Activos</p>
                                    <h2 class="mb-0"><?php echo number_format($stats['activos']); ?></h2>
                                </div>
                                <div class="feature-icon" style="width: 50px; height: 50px; font-size: 1.25rem;">
                                    <i class="fas fa-user-check"></i>
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
                                    <p class="text-muted mb-1" style="font-size: 0.875rem;">Hombres</p>
                                    <h2 class="mb-0"><?php echo number_format($stats['hombres']); ?></h2>
                                </div>
                                <div class="feature-icon" style="width: 50px; height: 50px; font-size: 1.25rem;">
                                    <i class="fas fa-male"></i>
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
                                    <p class="text-muted mb-1" style="font-size: 0.875rem;">Mujeres</p>
                                    <h2 class="mb-0"><?php echo number_format($stats['mujeres']); ?></h2>
                                </div>
                                <div class="feature-icon" style="width: 50px; height: 50px; font-size: 1.25rem;">
                                    <i class="fas fa-female"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botón Nuevo Empleado -->
            <div class="mb-3">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrearEmpleado">
                    <i class="fas fa-plus me-2"></i>Nuevo Empleado
                </button>
            </div>

            <!-- Listado de Empleados -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Foto</th>
                                    <th>Nombre Completo</th>
                                    <th>RUT</th>
                                    <th>Cargo</th>
                                    <th>Fecha Ingreso</th>
                                    <th>Años Empresa</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($empleados)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
                                            <p class="text-muted">No hay empleados registrados</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($empleados as $empleado): ?>
                                        <tr>
                                            <td>
                                                <?php if ($empleado['foto_url']): ?>
                                                    <img src="<?php echo htmlspecialchars($empleado['foto_url']); ?>"
                                                         alt="Foto" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                                                <?php else: ?>
                                                    <div style="width: 40px; height: 40px; border-radius: 50%; background: #3b82f6; color: white; display: flex; align-items: center; justify-content: center; font-weight: 600;">
                                                        <?php echo strtoupper(substr($empleado['nombres'], 0, 1) . substr($empleado['apellidos'], 0, 1)); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($empleado['nombres'] . ' ' . $empleado['apellidos']); ?></strong>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($empleado['email'] ?? 'Sin email'); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($empleado['rut']); ?></td>
                                            <td><?php echo htmlspecialchars($empleado['cargo'] ?? 'N/A'); ?></td>
                                            <td><?php echo $empleado['fecha_ingreso'] ? date('d/m/Y', strtotime($empleado['fecha_ingreso'])) : 'N/A'; ?></td>
                                            <td><?php echo $empleado['anos_empresa'] ?? 0; ?> años</td>
                                            <td>
                                                <span class="badge badge-<?php echo $empleado['estado']; ?>">
                                                    <?php echo ucfirst($empleado['estado']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-info action-btn" onclick="verEmpleado(<?php echo $empleado['id']; ?>)"
                                                        title="Ver Ficha">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-warning action-btn" onclick="editarEmpleado(<?php echo $empleado['id']; ?>)"
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

    <!-- Modal: Crear Empleado -->
    <div class="modal fade" id="modalCrearEmpleado" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user-plus me-2"></i>
                        Nuevo Empleado
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formCrearEmpleado" method="POST" enctype="multipart/form-data">
                        <!-- Tabs de Secciones -->
                        <ul class="nav nav-tabs" role="tablist">
                            <li class="nav-item">
                                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#seccion1" type="button">
                                    1. Personal
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#seccion2" type="button">
                                    2. Laboral
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#seccion3" type="button">
                                    3. Foto
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content mt-3">
                            <!-- SECCIÓN 1: INFORMACIÓN PERSONAL -->
                            <div class="tab-pane fade show active" id="seccion1">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Nombres *</label>
                                        <input type="text" class="form-control" name="nombres" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Apellidos *</label>
                                        <input type="text" class="form-control" name="apellidos" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">RUT *</label>
                                        <input type="text" class="form-control" name="rut" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Validador</label>
                                        <input type="text" class="form-control" name="validador_rut" maxlength="1">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Fecha Nacimiento *</label>
                                        <input type="date" class="form-control" name="fecha_nacimiento" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Género *</label>
                                        <select class="form-select" name="genero" required>
                                            <option value="masculino">Masculino</option>
                                            <option value="femenino">Femenino</option>
                                            <option value="otro">Otro</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Nacionalidad</label>
                                        <input type="text" class="form-control" name="nacionalidad">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Estado Civil</label>
                                        <input type="text" class="form-control" name="estado_civil">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" name="email">
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label">Dirección</label>
                                        <input type="text" class="form-control" name="direccion">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Teléfono</label>
                                        <input type="tel" class="form-control" name="telefono">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Ciudad</label>
                                        <input type="text" class="form-control" name="ciudad">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Región</label>
                                        <input type="text" class="form-control" name="region">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Contacto Emergencia</label>
                                        <input type="text" class="form-control" name="contacto_emergencia">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Teléfono Emergencia</label>
                                        <input type="tel" class="form-control" name="telefono_emergencia">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Relación</label>
                                        <input type="text" class="form-control" name="relacion_emergencia">
                                    </div>
                                </div>
                            </div>

                            <!-- SECCIÓN 2: INFORMACIÓN LABORAL -->
                            <div class="tab-pane fade" id="seccion2">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Fecha Ingreso *</label>
                                        <input type="date" class="form-control" name="fecha_ingreso" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Cargo *</label>
                                        <input type="text" class="form-control" name="cargo" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Área/Departamento</label>
                                        <input type="text" class="form-control" name="area_departamento">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Tipo de Contrato</label>
                                        <input type="text" class="form-control" name="tipo_contrato">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Tipo de Trabajador</label>
                                        <input type="text" class="form-control" name="tipo_trabajador">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Horario</label>
                                        <input type="text" class="form-control" name="horario">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Sueldo Base</label>
                                        <input type="number" class="form-control" name="sueldo_base" value="0" step="0.01">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Tipo Remuneración</label>
                                        <select class="form-select" name="tipo_remuneracion">
                                            <option value="mensual">Mensual</option>
                                            <option value="diaria">Diaria</option>
                                            <option value="hora">Por Hora</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- SECCIÓN 3: FOTO -->
                            <div class="tab-pane fade" id="seccion3">
                                <div class="row g-3">
                                    <div class="col-md-12 text-center">
                                        <label class="form-label d-block">Foto del Empleado</label>
                                        <p class="text-muted small">Formatos: JPG, JPEG, PNG | Tamaño máximo: 5 MB</p>
                                        <input type="file" class="form-control" name="foto" accept="image/jpeg,image/jpg,image/png">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times me-2"></i>Cancelar
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Guardar Empleado
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Ver Ficha del Empleado -->
    <div class="modal fade" id="modalVerEmpleado" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-id-card me-2"></i>
                        Ficha del Empleado
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-3 text-center">
                            <div id="empleadoFoto"></div>
                            <h5 class="mt-3" id="empleadoNombre"></h5>
                            <p class="text-muted" id="empleadoCargo"></p>
                        </div>
                        <div class="col-md-9">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <label>RUT:</label>
                                        <div class="value" id="empleadoRut">-</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <label>Email:</label>
                                        <div class="value" id="empleadoEmail">-</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="info-item">
                                        <label>Fecha Ingreso:</label>
                                        <div class="value" id="empleadoFechaIngreso">-</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="info-item">
                                        <label>Años en Empresa:</label>
                                        <div class="value" id="empleadoAnos">-</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="info-item">
                                        <label>Estado:</label>
                                        <div class="value" id="empleadoEstado">-</div>
                                    </div>
                                </div>
                            </div>
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
        const empleadosData = <?php echo json_encode($empleados); ?>;

        // ============================================
        // FUNCIONES GLOBALES PARA ONCLICK
        // ============================================

        // Ver Ficha Completa
        function verEmpleado(id) {
            const empleado = empleadosData.find(e => e.id == id);
            if (empleado) {
                // Foto
                const fotoDiv = document.getElementById('empleadoFoto');
                if (empleado.foto_url) {
                    fotoDiv.innerHTML = '<img src="' + empleado.foto_url + '" class="foto-preview" alt="Foto">';
                } else {
                    const iniciales = (empleado.nombres.substring(0,1) + empleado.apellidos.substring(0,1)).toUpperCase();
                    fotoDiv.innerHTML = '<div class="avatar-default">' + iniciales + '</div>';
                }

                // Datos
                document.getElementById('empleadoNombre').textContent = empleado.nombres + ' ' + empleado.apellidos;
                document.getElementById('empleadoCargo').textContent = empleado.cargo || 'Sin cargo';
                document.getElementById('empleadoRut').textContent = empleado.rut;
                document.getElementById('empleadoEmail').textContent = empleado.email || 'N/A';
                document.getElementById('empleadoFechaIngreso').textContent = empleado.fecha_ingreso || 'N/A';
                document.getElementById('empleadoAnos').textContent = (empleado.anos_empresa || 0) + ' años';
                document.getElementById('empleadoEstado').innerHTML =
                    '<span class="badge badge-' + empleado.estado + '">' + empleado.estado.toUpperCase() + '</span>';

                const modal = new bootstrap.Modal(document.getElementById('modalVerEmpleado'));
                modal.show();
            }
        }

        // Editar Empleado
        function editarEmpleado(id) {
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
        });
    </script>
</body>
</html>
