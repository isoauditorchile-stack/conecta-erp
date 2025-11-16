<?php
/**
 * PARAMETRIZACIÓN GLOBAL - CONECTA ERP
 * Configuración Global del Sistema
 * Nivel Empresarial - Estilo SAP/Softland
 */

session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Verificar autenticación
requireLogin();

// Verificar permisos - Solo admin puede configurar
$puede_configurar = ($_SESSION['es_super_admin'] == 1) || verificarPermiso('configuracion', 'editar');

$mensaje = '';
$tipo_mensaje = '';
$tab_activa = $_GET['tab'] ?? 'regional';

// ==========================================
// PROCESAR ACCIONES - GUARDAR CONFIGURACIÓN
// ==========================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_config'])) {
    if (!$puede_configurar) {
        $mensaje = 'No tienes permisos para modificar la configuración';
        $tipo_mensaje = 'danger';
    } else {
        $seccion = $_POST['seccion'];

        foreach ($_POST as $clave => $valor) {
            if ($clave === 'guardar_config' || $clave === 'seccion') continue;

            // Actualizar o insertar configuración
            $stmt = $conn->prepare("INSERT INTO configuracion (clave, valor, empresa_id, modificado_por, fecha_modificacion)
                                    VALUES (?, ?, ?, ?, NOW())
                                    ON DUPLICATE KEY UPDATE
                                    valor = VALUES(valor),
                                    modificado_por = VALUES(modificado_por),
                                    fecha_modificacion = NOW()");

            $valor_json = is_array($valor) ? json_encode($valor) : $valor;
            $stmt->bind_param("ssii", $clave, $valor_json, $_SESSION['empresa_id'], $_SESSION['usuario_id']);
            $stmt->execute();
            $stmt->close();
        }

        $mensaje = "Configuración de $seccion guardada exitosamente";
        $tipo_mensaje = 'success';

        logAuditoria('actualizar', 'configuracion', null, null, null,
                    "Configuración actualizada: $seccion");
    }
}

// CREAR PARÁMETRO PERSONALIZADO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_parametro'])) {
    if (!$puede_configurar) {
        $mensaje = 'No tienes permisos para crear parámetros';
        $tipo_mensaje = 'danger';
    } else {
        $clave = trim($_POST['clave']);
        $valor = trim($_POST['valor']);
        $descripcion = trim($_POST['descripcion']);
        $tipo = $_POST['tipo'];

        $stmt = $conn->prepare("INSERT INTO configuracion (clave, valor, descripcion, tipo, empresa_id, creado_por, fecha_creacion)
                                VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssssii", $clave, $valor, $descripcion, $tipo, $_SESSION['empresa_id'], $_SESSION['usuario_id']);

        if ($stmt->execute()) {
            $mensaje = "Parámetro '$clave' creado exitosamente";
            $tipo_mensaje = 'success';
            logAuditoria('crear', 'configuracion', $conn->insert_id, null, null, "Parámetro creado: $clave");
        } else {
            $mensaje = 'Error al crear el parámetro';
            $tipo_mensaje = 'danger';
        }
        $stmt->close();
    }
}

// ELIMINAR PARÁMETRO
if (isset($_GET['eliminar']) && $puede_configurar) {
    $config_id = (int)$_GET['eliminar'];

    $stmt = $conn->prepare("DELETE FROM configuracion WHERE id = ? AND empresa_id = ?");
    $stmt->bind_param("ii", $config_id, $_SESSION['empresa_id']);

    if ($stmt->execute()) {
        $mensaje = "Parámetro eliminado exitosamente";
        $tipo_mensaje = 'success';
        logAuditoria('eliminar', 'configuracion', $config_id, null, null, "Parámetro eliminado");
    }
    $stmt->close();
}

// ==========================================
// FUNCIONES AUXILIARES
// ==========================================

function obtenerConfig($clave, $default = '') {
    global $conn;
    $stmt = $conn->prepare("SELECT valor FROM configuracion WHERE clave = ? AND empresa_id = ?");
    $stmt->bind_param("si", $clave, $_SESSION['empresa_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        return $row['valor'];
    }
    return $default;
}

// ==========================================
// OBTENER CONFIGURACIONES
// ==========================================

// Configuración Regional
$config_regional = [
    'timezone' => obtenerConfig('timezone', 'America/Santiago'),
    'locale' => obtenerConfig('locale', 'es_CL'),
    'moneda_principal' => obtenerConfig('moneda_principal', 'CLP'),
    'simbolo_moneda' => obtenerConfig('simbolo_moneda', '$'),
    'separador_miles' => obtenerConfig('separador_miles', '.'),
    'separador_decimales' => obtenerConfig('separador_decimales', ','),
    'decimales_moneda' => obtenerConfig('decimales_moneda', '0'),
];

// Configuración de Formatos
$config_formatos = [
    'formato_fecha' => obtenerConfig('formato_fecha', 'dd/mm/YYYY'),
    'formato_hora' => obtenerConfig('formato_hora', 'HH:mm'),
    'primer_dia_semana' => obtenerConfig('primer_dia_semana', '1'),
    'formato_rut' => obtenerConfig('formato_rut', 'con_puntos'),
];

// Configuración de Impuestos
$config_impuestos = [
    'iva_porcentaje' => obtenerConfig('iva_porcentaje', '19'),
    'iva_incluido' => obtenerConfig('iva_incluido', '1'),
    'retencion_honorarios' => obtenerConfig('retencion_honorarios', '11.5'),
    'impuesto_adicional_1' => obtenerConfig('impuesto_adicional_1', '0'),
    'impuesto_adicional_2' => obtenerConfig('impuesto_adicional_2', '0'),
];

// Configuración de Seguridad
$config_seguridad = [
    'sesion_timeout' => obtenerConfig('sesion_timeout', '3600'),
    'intentos_login' => obtenerConfig('intentos_login', '5'),
    'bloqueo_tiempo' => obtenerConfig('bloqueo_tiempo', '900'),
    'requerir_2fa' => obtenerConfig('requerir_2fa', '0'),
    'complejidad_password' => obtenerConfig('complejidad_password', 'media'),
    'duracion_password' => obtenerConfig('duracion_password', '90'),
];

// Configuración SMTP
$config_smtp = [
    'smtp_host' => obtenerConfig('smtp_host', ''),
    'smtp_port' => obtenerConfig('smtp_port', '587'),
    'smtp_usuario' => obtenerConfig('smtp_usuario', ''),
    'smtp_password' => obtenerConfig('smtp_password', ''),
    'smtp_encryption' => obtenerConfig('smtp_encryption', 'tls'),
    'email_from' => obtenerConfig('email_from', ''),
    'email_from_name' => obtenerConfig('email_from_name', 'CONECTA ERP'),
];

// Configuración de Apariencia
$config_apariencia = [
    'tema_default' => obtenerConfig('tema_default', 'light'),
    'logo_url' => obtenerConfig('logo_url', ''),
    'color_primario' => obtenerConfig('color_primario', '#667eea'),
    'color_secundario' => obtenerConfig('color_secundario', '#764ba2'),
    'registros_por_pagina' => obtenerConfig('registros_por_pagina', '25'),
];

// Obtener todos los parámetros personalizados
$parametros_personalizados = $conn->query("SELECT * FROM configuracion
                                           WHERE empresa_id = {$_SESSION['empresa_id']}
                                           AND clave NOT IN ('timezone', 'locale', 'moneda_principal', 'simbolo_moneda',
                                                            'separador_miles', 'separador_decimales', 'decimales_moneda',
                                                            'formato_fecha', 'formato_hora', 'primer_dia_semana', 'formato_rut',
                                                            'iva_porcentaje', 'iva_incluido', 'retencion_honorarios',
                                                            'sesion_timeout', 'intentos_login', 'bloqueo_tiempo', 'requerir_2fa',
                                                            'smtp_host', 'smtp_port', 'smtp_usuario', 'smtp_password',
                                                            'tema_default', 'logo_url', 'color_primario', 'color_secundario')
                                           ORDER BY clave")->fetch_all(MYSQLI_ASSOC);

// Estadísticas
$stats = [
    'total_parametros' => $conn->query("SELECT COUNT(*) as total FROM configuracion WHERE empresa_id = {$_SESSION['empresa_id']}")->fetch_assoc()['total'],
    'modificados_hoy' => $conn->query("SELECT COUNT(*) as total FROM configuracion WHERE empresa_id = {$_SESSION['empresa_id']} AND DATE(fecha_modificacion) = CURDATE()")->fetch_assoc()['total'],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parametrización Global - CONECTA ERP</title>

    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="../../assets/css/dark_mode.css">

    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --sidebar-width: 250px;
            --topbar-height: 60px;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            overflow-x: hidden;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: var(--primary-gradient);
            padding: 20px 0;
            z-index: 1000;
            transition: all 0.3s;
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 0 20px 20px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 20px;
        }

        .sidebar-header h3 {
            color: white;
            font-size: 20px;
            font-weight: 700;
            margin: 0;
        }

        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-menu li {
            margin-bottom: 5px;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .sidebar-menu a i {
            width: 25px;
            margin-right: 10px;
            font-size: 18px;
        }

        /* Topbar */
        .topbar {
            position: fixed;
            top: 0;
            left: var(--sidebar-width);
            right: 0;
            height: var(--topbar-height);
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 0 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            z-index: 999;
        }

        .breadcrumb {
            background: none;
            padding: 0;
            margin: 0;
        }

        .breadcrumb-item a {
            color: #667eea;
            text-decoration: none;
        }

        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            margin-top: var(--topbar-height);
            padding: 30px;
            min-height: calc(100vh - var(--topbar-height));
        }

        /* Stats Cards */
        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s;
        }

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .stats-icon {
            width: 60px;
            height: 60px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 15px;
        }

        .stats-icon.blue { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .stats-icon.green { background: linear-gradient(135deg, #48bb78 0%, #38a169 100%); color: white; }
        .stats-icon.orange { background: linear-gradient(135deg, #ed8936 0%, #dd6b20 100%); color: white; }
        .stats-icon.red { background: linear-gradient(135deg, #f56565 0%, #c53030 100%); color: white; }

        .stats-number {
            font-size: 32px;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 5px;
        }

        .stats-label {
            color: #718096;
            font-size: 14px;
        }

        /* Content Card */
        .content-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 25px;
            margin-top: 20px;
        }

        .content-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f5f7fa;
        }

        .content-card-title {
            font-size: 20px;
            font-weight: 700;
            color: #2d3748;
            margin: 0;
        }

        /* Buttons */
        .btn-gradient {
            background: var(--primary-gradient);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
            color: white;
        }

        /* Nav Tabs */
        .nav-tabs .nav-link {
            color: #718096;
            border: none;
            padding: 12px 24px;
            font-weight: 600;
            border-bottom: 3px solid transparent;
        }

        .nav-tabs .nav-link:hover {
            color: #667eea;
            border-color: transparent;
        }

        .nav-tabs .nav-link.active {
            color: #667eea;
            border-bottom: 3px solid #667eea;
            background: none;
        }

        /* Form */
        .form-label {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 8px;
        }

        .form-control,
        .form-select {
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            padding: 10px 15px;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .config-section {
            background: #f5f7fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .config-section h6 {
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e2e8f0;
        }

        /* Table */
        .table thead th {
            background: #f5f7fa;
            color: #2d3748;
            font-weight: 600;
            border: none;
        }

        .table tbody tr:hover {
            background: #f5f7fa;
        }

        .action-buttons {
            display: flex;
            gap: 5px;
        }

        .action-buttons .btn {
            padding: 5px 10px;
            font-size: 12px;
        }

        /* Modal */
        .modal-header {
            background: var(--primary-gradient);
            color: white;
            border-radius: 10px 10px 0 0;
        }

        .modal-content {
            border-radius: 10px;
            border: none;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                left: -250px;
            }

            .sidebar.active {
                left: 0;
            }

            .main-content {
                margin-left: 0;
            }

            .topbar {
                left: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h3><i class="fas fa-rocket"></i> CONECTA ERP</h3>
            <small style="color: rgba(255,255,255,0.7);">Administración</small>
        </div>

        <ul class="sidebar-menu">
            <li><a href="../dashboard/index.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="gestion_empresas.php"><i class="fas fa-building"></i> Empresas</a></li>
            <li><a href="gestion_seguridad.php"><i class="fas fa-shield-alt"></i> Seguridad</a></li>
            <li><a href="parametrizacion_global.php" class="active"><i class="fas fa-cog"></i> Parametrización</a></li>
            <li><a href="../entidades/entidades_maestras.php"><i class="fas fa-database"></i> Entidades Maestras</a></li>
            <li><a href="../../user/dashboard.php"><i class="fas fa-arrow-left"></i> Volver</a></li>
        </ul>
    </div>

    <!-- Topbar -->
    <div class="topbar">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard/index.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="#">Administración</a></li>
                <li class="breadcrumb-item active">Parametrización Global</li>
            </ol>
        </nav>

        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-sm" data-theme-toggle>
                <i class="fas fa-moon"></i>
            </button>
            <span class="text-muted">
                <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['nombre']); ?>
            </span>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Mensajes -->
        <?php if ($mensaje): ?>
            <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
                <i class="fas fa-<?php echo $tipo_mensaje === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo htmlspecialchars($mensaje); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">Parametrización Global</h1>
                <p class="text-muted mb-0">Configuración centralizada del sistema</p>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-icon blue">
                        <i class="fas fa-cogs"></i>
                    </div>
                    <div class="stats-number"><?php echo number_format($stats['total_parametros']); ?></div>
                    <div class="stats-label">Total Parámetros</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-icon green">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stats-number"><?php echo number_format($stats['modificados_hoy']); ?></div>
                    <div class="stats-label">Modificados Hoy</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-icon orange">
                        <i class="fas fa-globe"></i>
                    </div>
                    <div class="stats-number"><?php echo $config_regional['locale']; ?></div>
                    <div class="stats-label">Configuración Regional</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-icon red">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div class="stats-number"><?php echo $config_seguridad['intentos_login']; ?></div>
                    <div class="stats-label">Máx Intentos Login</div>
                </div>
            </div>
        </div>

        <!-- Content Card -->
        <div class="content-card">
            <!-- Tabs -->
            <ul class="nav nav-tabs mb-4" role="tablist">
                <li class="nav-item">
                    <a class="nav-link <?php echo $tab_activa === 'regional' ? 'active' : ''; ?>" href="?tab=regional">
                        <i class="fas fa-globe"></i> Regional
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $tab_activa === 'formatos' ? 'active' : ''; ?>" href="?tab=formatos">
                        <i class="fas fa-file-alt"></i> Formatos
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $tab_activa === 'impuestos' ? 'active' : ''; ?>" href="?tab=impuestos">
                        <i class="fas fa-percentage"></i> Impuestos
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $tab_activa === 'seguridad' ? 'active' : ''; ?>" href="?tab=seguridad">
                        <i class="fas fa-shield-alt"></i> Seguridad
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $tab_activa === 'smtp' ? 'active' : ''; ?>" href="?tab=smtp">
                        <i class="fas fa-envelope"></i> SMTP
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $tab_activa === 'apariencia' ? 'active' : ''; ?>" href="?tab=apariencia">
                        <i class="fas fa-palette"></i> Apariencia
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $tab_activa === 'personalizados' ? 'active' : ''; ?>" href="?tab=personalizados">
                        <i class="fas fa-wrench"></i> Personalizados
                    </a>
                </li>
            </ul>

            <!-- TAB: Regional -->
            <?php if ($tab_activa === 'regional'): ?>
                <form method="POST" action="">
                    <input type="hidden" name="seccion" value="Regional">
                    <div class="config-section">
                        <h6>Configuración Regional</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Zona Horaria</label>
                                <select name="timezone" class="form-select" <?php echo !$puede_configurar ? 'disabled' : ''; ?>>
                                    <option value="America/Santiago" <?php echo $config_regional['timezone'] === 'America/Santiago' ? 'selected' : ''; ?>>América/Santiago (Chile)</option>
                                    <option value="America/Argentina/Buenos_Aires" <?php echo $config_regional['timezone'] === 'America/Argentina/Buenos_Aires' ? 'selected' : ''; ?>>América/Buenos Aires (Argentina)</option>
                                    <option value="America/Lima" <?php echo $config_regional['timezone'] === 'America/Lima' ? 'selected' : ''; ?>>América/Lima (Perú)</option>
                                    <option value="America/Bogota" <?php echo $config_regional['timezone'] === 'America/Bogota' ? 'selected' : ''; ?>>América/Bogotá (Colombia)</option>
                                    <option value="America/Mexico_City" <?php echo $config_regional['timezone'] === 'America/Mexico_City' ? 'selected' : ''; ?>>América/Ciudad de México</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Configuración Regional (Locale)</label>
                                <select name="locale" class="form-select" <?php echo !$puede_configurar ? 'disabled' : ''; ?>>
                                    <option value="es_CL" <?php echo $config_regional['locale'] === 'es_CL' ? 'selected' : ''; ?>>Español (Chile)</option>
                                    <option value="es_AR" <?php echo $config_regional['locale'] === 'es_AR' ? 'selected' : ''; ?>>Español (Argentina)</option>
                                    <option value="es_PE" <?php echo $config_regional['locale'] === 'es_PE' ? 'selected' : ''; ?>>Español (Perú)</option>
                                    <option value="es_CO" <?php echo $config_regional['locale'] === 'es_CO' ? 'selected' : ''; ?>>Español (Colombia)</option>
                                    <option value="es_MX" <?php echo $config_regional['locale'] === 'es_MX' ? 'selected' : ''; ?>>Español (México)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Moneda Principal</label>
                                <select name="moneda_principal" class="form-select" <?php echo !$puede_configurar ? 'disabled' : ''; ?>>
                                    <option value="CLP" <?php echo $config_regional['moneda_principal'] === 'CLP' ? 'selected' : ''; ?>>CLP - Peso Chileno</option>
                                    <option value="ARS" <?php echo $config_regional['moneda_principal'] === 'ARS' ? 'selected' : ''; ?>>ARS - Peso Argentino</option>
                                    <option value="PEN" <?php echo $config_regional['moneda_principal'] === 'PEN' ? 'selected' : ''; ?>>PEN - Sol Peruano</option>
                                    <option value="COP" <?php echo $config_regional['moneda_principal'] === 'COP' ? 'selected' : ''; ?>>COP - Peso Colombiano</option>
                                    <option value="MXN" <?php echo $config_regional['moneda_principal'] === 'MXN' ? 'selected' : ''; ?>>MXN - Peso Mexicano</option>
                                    <option value="USD" <?php echo $config_regional['moneda_principal'] === 'USD' ? 'selected' : ''; ?>>USD - Dólar Estadounidense</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Símbolo de Moneda</label>
                                <input type="text" name="simbolo_moneda" class="form-control" value="<?php echo htmlspecialchars($config_regional['simbolo_moneda']); ?>" <?php echo !$puede_configurar ? 'disabled' : ''; ?>>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Separador de Miles</label>
                                <select name="separador_miles" class="form-select" <?php echo !$puede_configurar ? 'disabled' : ''; ?>>
                                    <option value="." <?php echo $config_regional['separador_miles'] === '.' ? 'selected' : ''; ?>>Punto (.)</option>
                                    <option value="," <?php echo $config_regional['separador_miles'] === ',' ? 'selected' : ''; ?>>Coma (,)</option>
                                    <option value=" " <?php echo $config_regional['separador_miles'] === ' ' ? 'selected' : ''; ?>>Espacio</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Separador de Decimales</label>
                                <select name="separador_decimales" class="form-select" <?php echo !$puede_configurar ? 'disabled' : ''; ?>>
                                    <option value="," <?php echo $config_regional['separador_decimales'] === ',' ? 'selected' : ''; ?>>Coma (,)</option>
                                    <option value="." <?php echo $config_regional['separador_decimales'] === '.' ? 'selected' : ''; ?>>Punto (.)</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Decimales en Moneda</label>
                                <select name="decimales_moneda" class="form-select" <?php echo !$puede_configurar ? 'disabled' : ''; ?>>
                                    <option value="0" <?php echo $config_regional['decimales_moneda'] === '0' ? 'selected' : ''; ?>>0 decimales</option>
                                    <option value="2" <?php echo $config_regional['decimales_moneda'] === '2' ? 'selected' : ''; ?>>2 decimales</option>
                                    <option value="3" <?php echo $config_regional['decimales_moneda'] === '3' ? 'selected' : ''; ?>>3 decimales</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <?php if ($puede_configurar): ?>
                        <div class="text-end">
                            <button type="submit" name="guardar_config" class="btn btn-gradient">
                                <i class="fas fa-save"></i> Guardar Configuración
                            </button>
                        </div>
                    <?php endif; ?>
                </form>
            <?php endif; ?>

            <!-- TAB: Formatos -->
            <?php if ($tab_activa === 'formatos'): ?>
                <form method="POST" action="">
                    <input type="hidden" name="seccion" value="Formatos">
                    <div class="config-section">
                        <h6>Configuración de Formatos</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Formato de Fecha</label>
                                <select name="formato_fecha" class="form-select" <?php echo !$puede_configurar ? 'disabled' : ''; ?>>
                                    <option value="dd/mm/YYYY" <?php echo $config_formatos['formato_fecha'] === 'dd/mm/YYYY' ? 'selected' : ''; ?>>DD/MM/YYYY (31/12/2025)</option>
                                    <option value="mm/dd/YYYY" <?php echo $config_formatos['formato_fecha'] === 'mm/dd/YYYY' ? 'selected' : ''; ?>>MM/DD/YYYY (12/31/2025)</option>
                                    <option value="YYYY-mm-dd" <?php echo $config_formatos['formato_fecha'] === 'YYYY-mm-dd' ? 'selected' : ''; ?>>YYYY-MM-DD (2025-12-31)</option>
                                    <option value="dd-mm-YYYY" <?php echo $config_formatos['formato_fecha'] === 'dd-mm-YYYY' ? 'selected' : ''; ?>>DD-MM-YYYY (31-12-2025)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Formato de Hora</label>
                                <select name="formato_hora" class="form-select" <?php echo !$puede_configurar ? 'disabled' : ''; ?>>
                                    <option value="HH:mm" <?php echo $config_formatos['formato_hora'] === 'HH:mm' ? 'selected' : ''; ?>>24 horas (23:59)</option>
                                    <option value="hh:mm a" <?php echo $config_formatos['formato_hora'] === 'hh:mm a' ? 'selected' : ''; ?>>12 horas (11:59 PM)</option>
                                    <option value="HH:mm:ss" <?php echo $config_formatos['formato_hora'] === 'HH:mm:ss' ? 'selected' : ''; ?>>24 horas con segundos (23:59:59)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Primer Día de la Semana</label>
                                <select name="primer_dia_semana" class="form-select" <?php echo !$puede_configurar ? 'disabled' : ''; ?>>
                                    <option value="0" <?php echo $config_formatos['primer_dia_semana'] === '0' ? 'selected' : ''; ?>>Domingo</option>
                                    <option value="1" <?php echo $config_formatos['primer_dia_semana'] === '1' ? 'selected' : ''; ?>>Lunes</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Formato de RUT</label>
                                <select name="formato_rut" class="form-select" <?php echo !$puede_configurar ? 'disabled' : ''; ?>>
                                    <option value="con_puntos" <?php echo $config_formatos['formato_rut'] === 'con_puntos' ? 'selected' : ''; ?>>Con puntos (12.345.678-9)</option>
                                    <option value="sin_puntos" <?php echo $config_formatos['formato_rut'] === 'sin_puntos' ? 'selected' : ''; ?>>Sin puntos (12345678-9)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <?php if ($puede_configurar): ?>
                        <div class="text-end">
                            <button type="submit" name="guardar_config" class="btn btn-gradient">
                                <i class="fas fa-save"></i> Guardar Configuración
                            </button>
                        </div>
                    <?php endif; ?>
                </form>
            <?php endif; ?>

            <!-- TAB: Impuestos -->
            <?php if ($tab_activa === 'impuestos'): ?>
                <form method="POST" action="">
                    <input type="hidden" name="seccion" value="Impuestos">
                    <div class="config-section">
                        <h6>Configuración de Impuestos</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">IVA - Porcentaje (%)</label>
                                <input type="number" name="iva_porcentaje" class="form-control" step="0.01" value="<?php echo $config_impuestos['iva_porcentaje']; ?>" <?php echo !$puede_configurar ? 'disabled' : ''; ?>>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">IVA Incluido en Precios</label>
                                <select name="iva_incluido" class="form-select" <?php echo !$puede_configurar ? 'disabled' : ''; ?>>
                                    <option value="1" <?php echo $config_impuestos['iva_incluido'] === '1' ? 'selected' : ''; ?>>Sí</option>
                                    <option value="0" <?php echo $config_impuestos['iva_incluido'] === '0' ? 'selected' : ''; ?>>No</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Retención Honorarios (%)</label>
                                <input type="number" name="retencion_honorarios" class="form-control" step="0.01" value="<?php echo $config_impuestos['retencion_honorarios']; ?>" <?php echo !$puede_configurar ? 'disabled' : ''; ?>>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Impuesto Adicional 1 (%)</label>
                                <input type="number" name="impuesto_adicional_1" class="form-control" step="0.01" value="<?php echo $config_impuestos['impuesto_adicional_1']; ?>" <?php echo !$puede_configurar ? 'disabled' : ''; ?>>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Impuesto Adicional 2 (%)</label>
                                <input type="number" name="impuesto_adicional_2" class="form-control" step="0.01" value="<?php echo $config_impuestos['impuesto_adicional_2']; ?>" <?php echo !$puede_configurar ? 'disabled' : ''; ?>>
                            </div>
                        </div>
                    </div>
                    <?php if ($puede_configurar): ?>
                        <div class="text-end">
                            <button type="submit" name="guardar_config" class="btn btn-gradient">
                                <i class="fas fa-save"></i> Guardar Configuración
                            </button>
                        </div>
                    <?php endif; ?>
                </form>
            <?php endif; ?>

            <!-- TAB: Seguridad -->
            <?php if ($tab_activa === 'seguridad'): ?>
                <form method="POST" action="">
                    <input type="hidden" name="seccion" value="Seguridad">
                    <div class="config-section">
                        <h6>Configuración de Seguridad</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Timeout de Sesión (segundos)</label>
                                <input type="number" name="sesion_timeout" class="form-control" value="<?php echo $config_seguridad['sesion_timeout']; ?>" <?php echo !$puede_configurar ? 'disabled' : ''; ?>>
                                <small class="text-muted">Valor recomendado: 3600 (1 hora)</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Máximo Intentos de Login</label>
                                <input type="number" name="intentos_login" class="form-control" value="<?php echo $config_seguridad['intentos_login']; ?>" <?php echo !$puede_configurar ? 'disabled' : ''; ?>>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tiempo de Bloqueo (segundos)</label>
                                <input type="number" name="bloqueo_tiempo" class="form-control" value="<?php echo $config_seguridad['bloqueo_tiempo']; ?>" <?php echo !$puede_configurar ? 'disabled' : ''; ?>>
                                <small class="text-muted">Valor recomendado: 900 (15 minutos)</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Requerir Autenticación 2FA</label>
                                <select name="requerir_2fa" class="form-select" <?php echo !$puede_configurar ? 'disabled' : ''; ?>>
                                    <option value="0" <?php echo $config_seguridad['requerir_2fa'] === '0' ? 'selected' : ''; ?>>No</option>
                                    <option value="1" <?php echo $config_seguridad['requerir_2fa'] === '1' ? 'selected' : ''; ?>>Sí</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Complejidad de Contraseña</label>
                                <select name="complejidad_password" class="form-select" <?php echo !$puede_configurar ? 'disabled' : ''; ?>>
                                    <option value="baja" <?php echo $config_seguridad['complejidad_password'] === 'baja' ? 'selected' : ''; ?>>Baja (mínimo 6 caracteres)</option>
                                    <option value="media" <?php echo $config_seguridad['complejidad_password'] === 'media' ? 'selected' : ''; ?>>Media (8 caracteres, mayúsculas y números)</option>
                                    <option value="alta" <?php echo $config_seguridad['complejidad_password'] === 'alta' ? 'selected' : ''; ?>>Alta (10 caracteres, mayúsculas, números y símbolos)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Duración Contraseña (días)</label>
                                <input type="number" name="duracion_password" class="form-control" value="<?php echo $config_seguridad['duracion_password']; ?>" <?php echo !$puede_configurar ? 'disabled' : ''; ?>>
                                <small class="text-muted">0 = sin expiración</small>
                            </div>
                        </div>
                    </div>
                    <?php if ($puede_configurar): ?>
                        <div class="text-end">
                            <button type="submit" name="guardar_config" class="btn btn-gradient">
                                <i class="fas fa-save"></i> Guardar Configuración
                            </button>
                        </div>
                    <?php endif; ?>
                </form>
            <?php endif; ?>

            <!-- TAB: SMTP -->
            <?php if ($tab_activa === 'smtp'): ?>
                <form method="POST" action="">
                    <input type="hidden" name="seccion" value="SMTP">
                    <div class="config-section">
                        <h6>Configuración SMTP para Envío de Emails</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Servidor SMTP (Host)</label>
                                <input type="text" name="smtp_host" class="form-control" value="<?php echo htmlspecialchars($config_smtp['smtp_host']); ?>" placeholder="smtp.gmail.com" <?php echo !$puede_configurar ? 'disabled' : ''; ?>>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Puerto SMTP</label>
                                <input type="number" name="smtp_port" class="form-control" value="<?php echo $config_smtp['smtp_port']; ?>" <?php echo !$puede_configurar ? 'disabled' : ''; ?>>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Usuario SMTP</label>
                                <input type="text" name="smtp_usuario" class="form-control" value="<?php echo htmlspecialchars($config_smtp['smtp_usuario']); ?>" <?php echo !$puede_configurar ? 'disabled' : ''; ?>>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Contraseña SMTP</label>
                                <input type="password" name="smtp_password" class="form-control" value="<?php echo htmlspecialchars($config_smtp['smtp_password']); ?>" placeholder="••••••••" <?php echo !$puede_configurar ? 'disabled' : ''; ?>>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tipo de Encriptación</label>
                                <select name="smtp_encryption" class="form-select" <?php echo !$puede_configurar ? 'disabled' : ''; ?>>
                                    <option value="tls" <?php echo $config_smtp['smtp_encryption'] === 'tls' ? 'selected' : ''; ?>>TLS</option>
                                    <option value="ssl" <?php echo $config_smtp['smtp_encryption'] === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email Remitente (From)</label>
                                <input type="email" name="email_from" class="form-control" value="<?php echo htmlspecialchars($config_smtp['email_from']); ?>" <?php echo !$puede_configurar ? 'disabled' : ''; ?>>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Nombre del Remitente</label>
                                <input type="text" name="email_from_name" class="form-control" value="<?php echo htmlspecialchars($config_smtp['email_from_name']); ?>" <?php echo !$puede_configurar ? 'disabled' : ''; ?>>
                            </div>
                        </div>
                    </div>
                    <?php if ($puede_configurar): ?>
                        <div class="text-end">
                            <button type="submit" name="guardar_config" class="btn btn-gradient">
                                <i class="fas fa-save"></i> Guardar Configuración
                            </button>
                        </div>
                    <?php endif; ?>
                </form>
            <?php endif; ?>

            <!-- TAB: Apariencia -->
            <?php if ($tab_activa === 'apariencia'): ?>
                <form method="POST" action="">
                    <input type="hidden" name="seccion" value="Apariencia">
                    <div class="config-section">
                        <h6>Configuración de Apariencia</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Tema por Defecto</label>
                                <select name="tema_default" class="form-select" <?php echo !$puede_configurar ? 'disabled' : ''; ?>>
                                    <option value="light" <?php echo $config_apariencia['tema_default'] === 'light' ? 'selected' : ''; ?>>Claro</option>
                                    <option value="dark" <?php echo $config_apariencia['tema_default'] === 'dark' ? 'selected' : ''; ?>>Oscuro</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Registros por Página</label>
                                <select name="registros_por_pagina" class="form-select" <?php echo !$puede_configurar ? 'disabled' : ''; ?>>
                                    <option value="10" <?php echo $config_apariencia['registros_por_pagina'] === '10' ? 'selected' : ''; ?>>10</option>
                                    <option value="25" <?php echo $config_apariencia['registros_por_pagina'] === '25' ? 'selected' : ''; ?>>25</option>
                                    <option value="50" <?php echo $config_apariencia['registros_por_pagina'] === '50' ? 'selected' : ''; ?>>50</option>
                                    <option value="100" <?php echo $config_apariencia['registros_por_pagina'] === '100' ? 'selected' : ''; ?>>100</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Color Primario</label>
                                <input type="color" name="color_primario" class="form-control form-control-color w-100" value="<?php echo $config_apariencia['color_primario']; ?>" <?php echo !$puede_configurar ? 'disabled' : ''; ?>>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Color Secundario</label>
                                <input type="color" name="color_secundario" class="form-control form-control-color w-100" value="<?php echo $config_apariencia['color_secundario']; ?>" <?php echo !$puede_configurar ? 'disabled' : ''; ?>>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">URL del Logo</label>
                                <input type="url" name="logo_url" class="form-control" value="<?php echo htmlspecialchars($config_apariencia['logo_url']); ?>" placeholder="https://ejemplo.com/logo.png" <?php echo !$puede_configurar ? 'disabled' : ''; ?>>
                            </div>
                        </div>
                    </div>
                    <?php if ($puede_configurar): ?>
                        <div class="text-end">
                            <button type="submit" name="guardar_config" class="btn btn-gradient">
                                <i class="fas fa-save"></i> Guardar Configuración
                            </button>
                        </div>
                    <?php endif; ?>
                </form>
            <?php endif; ?>

            <!-- TAB: Parámetros Personalizados -->
            <?php if ($tab_activa === 'personalizados'): ?>
                <div class="content-card-header">
                    <h2 class="content-card-title">Parámetros Personalizados</h2>
                    <?php if ($puede_configurar): ?>
                        <button class="btn btn-gradient" data-bs-toggle="modal" data-bs-target="#modalCrearParametro">
                            <i class="fas fa-plus"></i> Nuevo Parámetro
                        </button>
                    <?php endif; ?>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover" id="tablaParametros">
                        <thead>
                            <tr>
                                <th>Clave</th>
                                <th>Valor</th>
                                <th>Descripción</th>
                                <th>Tipo</th>
                                <th>Última Modificación</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($parametros_personalizados as $param): ?>
                            <tr>
                                <td><code><?php echo htmlspecialchars($param['clave']); ?></code></td>
                                <td><?php echo htmlspecialchars($param['valor']); ?></td>
                                <td><?php echo htmlspecialchars($param['descripcion'] ?? '-'); ?></td>
                                <td><span class="badge bg-secondary"><?php echo $param['tipo'] ?? 'texto'; ?></span></td>
                                <td><?php echo $param['fecha_modificacion'] ? date('d/m/Y H:i', strtotime($param['fecha_modificacion'])) : '-'; ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <?php if ($puede_configurar): ?>
                                            <button class="btn btn-sm btn-primary" onclick="editarParametro(<?php echo $param['id']; ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <a href="?eliminar=<?php echo $param['id']; ?>&tab=personalizados" class="btn btn-sm btn-danger"
                                               onclick="return confirm('¿Eliminar este parámetro?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal Crear Parámetro -->
    <div class="modal fade" id="modalCrearParametro" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-wrench"></i> Nuevo Parámetro</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Clave *</label>
                            <input type="text" name="clave" class="form-control" required>
                            <small class="text-muted">Identificador único del parámetro (sin espacios)</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Valor *</label>
                            <input type="text" name="valor" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Descripción</label>
                            <textarea name="descripcion" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tipo</label>
                            <select name="tipo" class="form-select">
                                <option value="texto">Texto</option>
                                <option value="numero">Número</option>
                                <option value="boolean">Booleano</option>
                                <option value="json">JSON</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="crear_parametro" class="btn btn-gradient">
                            <i class="fas fa-save"></i> Crear Parámetro
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="../../assets/js/dark_mode.js"></script>

    <script>
        // DataTable
        $('#tablaParametros').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
            },
            order: [[0, 'asc']],
            pageLength: 25
        });

        function editarParametro(id) {
            alert('Función en desarrollo: Editar parámetro ' + id);
        }
    </script>
</body>
</html>
