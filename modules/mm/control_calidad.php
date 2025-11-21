<?php
/**
 * MODULO: Control de Calidad
 * Sistema completo de gestion de calidad ERP
 * Sin dependencias - Sin AJAX - Sin sidebar - UTF-8
 */

$db_host = 'localhost';
$db_name = 'conectae_conectaerpbd';
$db_user = 'conectae_conectaerpuser';
$db_pass = 'pt125824caraud';

try {
    $pdo = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
        $db_user,
        $db_pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    die('Error de conexion: ' . $e->getMessage());
}

session_start();

// Crear tablas
$pdo->exec("
CREATE TABLE IF NOT EXISTS qc_parametros (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo_control ENUM('entrada','proceso','salida') DEFAULT 'entrada',
    politica_control ENUM('AQL','muestreo','100_inspeccion') DEFAULT 'muestreo',
    norma_calidad VARCHAR(100),
    frecuencia_inspeccion VARCHAR(100),
    nivel_inspeccion VARCHAR(50),
    criterios_aprobacion TEXT,
    criterios_rechazo TEXT,
    activo TINYINT(1) DEFAULT 1,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS qc_inspeccion_entrada (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_inspeccion VARCHAR(50) NOT NULL,
    proveedor VARCHAR(255),
    numero_oc VARCHAR(50),
    documento_recepcion VARCHAR(50),
    lote VARCHAR(100),
    numero_serie VARCHAR(100),
    fecha_recepcion DATE,
    cantidad_recepcionada DECIMAL(15,2) DEFAULT 0,
    cantidad_rechazada DECIMAL(15,2) DEFAULT 0,
    causa_rechazo TEXT,
    inspeccion_visual ENUM('OK','NO_OK','NA') DEFAULT 'NA',
    inspeccion_dimensional ENUM('OK','NO_OK','NA') DEFAULT 'NA',
    inspeccion_funcional ENUM('OK','NO_OK','NA') DEFAULT 'NA',
    resultado ENUM('aprobado','rechazado','reproceso') DEFAULT 'aprobado',
    estado_lote ENUM('liberado','bloqueado','cuarentena') DEFAULT 'cuarentena',
    observaciones TEXT,
    usuario_inspeccion INT,
    fecha_inspeccion DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_lote (lote),
    INDEX idx_resultado (resultado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS qc_inspeccion_proceso (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_inspeccion VARCHAR(50) NOT NULL,
    orden_produccion VARCHAR(50),
    etapa_proceso VARCHAR(100),
    punto_control VARCHAR(255),
    muestras_turno INT DEFAULT 0,
    scrap_generado DECIMAL(15,2) DEFAULT 0,
    reproceso DECIMAL(15,2) DEFAULT 0,
    causas_reproceso TEXT,
    medidas_correctivas TEXT,
    inspeccion_linea ENUM('OK','NO_OK') DEFAULT 'OK',
    aprobacion_parcial TINYINT(1) DEFAULT 0,
    bloqueo_parcial TINYINT(1) DEFAULT 0,
    resultado ENUM('aprobado','rechazado','reproceso') DEFAULT 'aprobado',
    usuario_inspeccion INT,
    fecha_inspeccion DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_orden (orden_produccion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS qc_inspeccion_salida (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_inspeccion VARCHAR(50) NOT NULL,
    lote_produccion VARCHAR(100),
    producto_terminado VARCHAR(255),
    verificacion_funcionamiento ENUM('OK','NO_OK') DEFAULT 'OK',
    verificacion_embalaje ENUM('OK','NO_OK') DEFAULT 'OK',
    verificacion_rotulado ENUM('OK','NO_OK') DEFAULT 'OK',
    muestreo_cantidad INT DEFAULT 0,
    resultado_inspeccion ENUM('OK','NO_OK') DEFAULT 'OK',
    liberacion_despacho TINYINT(1) DEFAULT 0,
    retencion_calidad TINYINT(1) DEFAULT 0,
    observaciones TEXT,
    usuario_inspeccion INT,
    fecha_inspeccion DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_lote (lote_produccion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS qc_no_conformidades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo_nc VARCHAR(50) NOT NULL,
    fecha_nc DATE NOT NULL,
    tipo_nc ENUM('leve','grave','critica') DEFAULT 'leve',
    descripcion_nc TEXT NOT NULL,
    producto VARCHAR(255),
    proveedor VARCHAR(255),
    lote VARCHAR(100),
    cantidad_afectada DECIMAL(15,2) DEFAULT 0,
    etapa_detectada VARCHAR(100),
    usuario_responsable INT,
    estado_nc ENUM('abierta','en_proceso','cerrada') DEFAULT 'abierta',
    fecha_cierre DATE,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_codigo (codigo_nc),
    INDEX idx_estado (estado_nc)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS qc_acciones_correctivas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nc_id INT,
    tipo_accion ENUM('correctiva','preventiva') DEFAULT 'correctiva',
    causa_raiz TEXT,
    verificacion_causa TEXT,
    descripcion_accion TEXT NOT NULL,
    responsable VARCHAR(255),
    fecha_implementacion DATE,
    seguimiento TEXT,
    estado ENUM('pendiente','en_proceso','cerrada') DEFAULT 'pendiente',
    fecha_cierre DATE,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (nc_id) REFERENCES qc_no_conformidades(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS qc_documentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo_documento ENUM('ficha_tecnica','hoja_seguridad','certificado_calidad','certificado_origen','manual','informe_laboratorio','ensayo','otro') DEFAULT 'otro',
    nombre_documento VARCHAR(255) NOT NULL,
    producto VARCHAR(255),
    lote VARCHAR(100),
    fecha_emision DATE,
    fecha_vencimiento DATE,
    estado ENUM('vigente','vencido','por_vencer') DEFAULT 'vigente',
    observaciones TEXT,
    archivo VARCHAR(255),
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS qc_reprocesos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_reproceso VARCHAR(50) NOT NULL,
    lote VARCHAR(100),
    producto VARCHAR(255),
    cantidad_reproceso DECIMAL(15,2) DEFAULT 0,
    motivo_reproceso TEXT,
    plan_reprocesamiento TEXT,
    consumos_adicionales TEXT,
    costo_reproceso DECIMAL(15,2) DEFAULT 0,
    orden_reproceso VARCHAR(50),
    resultado_final ENUM('OK','rechazo_final') DEFAULT 'OK',
    fecha_inicio DATE,
    fecha_fin DATE,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_lote (lote)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS qc_bloqueos_stock (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lote_bloqueado VARCHAR(100) NOT NULL,
    producto VARCHAR(255),
    cantidad_bloqueada DECIMAL(15,2) DEFAULT 0,
    motivo_bloqueo TEXT,
    fecha_bloqueo DATE NOT NULL,
    bloqueo_calidad TINYINT(1) DEFAULT 1,
    cuarentena TINYINT(1) DEFAULT 0,
    derivacion_servicio_tecnico TINYINT(1) DEFAULT 0,
    levantamiento_bloqueo TINYINT(1) DEFAULT 0,
    fecha_levantamiento DATE,
    motivo_levantamiento TEXT,
    usuario_bloqueo INT,
    usuario_levantamiento INT,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_lote (lote_bloqueado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS qc_historial (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tabla_origen VARCHAR(100),
    registro_id INT,
    accion VARCHAR(50),
    datos_anteriores TEXT,
    datos_nuevos TEXT,
    usuario_id INT,
    fecha_accion DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$vista = isset($_GET['vista']) ? $_GET['vista'] : 'dashboard';
$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = isset($_POST['accion']) ? $_POST['accion'] : '';

    // Inspeccion Entrada
    if ($accion === 'guardar_inspeccion_entrada') {
        $stmt = $pdo->prepare("INSERT INTO qc_inspeccion_entrada (numero_inspeccion, proveedor, numero_oc, documento_recepcion, lote, numero_serie, fecha_recepcion, cantidad_recepcionada, cantidad_rechazada, causa_rechazo, inspeccion_visual, inspeccion_dimensional, inspeccion_funcional, resultado, estado_lote, observaciones) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $numero = 'IE-' . date('Ymd') . '-' . rand(1000, 9999);
        $stmt->execute([$numero, $_POST['proveedor'], $_POST['numero_oc'], $_POST['documento_recepcion'], $_POST['lote'], $_POST['numero_serie'], $_POST['fecha_recepcion'], $_POST['cantidad_recepcionada'], $_POST['cantidad_rechazada'], $_POST['causa_rechazo'], $_POST['inspeccion_visual'], $_POST['inspeccion_dimensional'], $_POST['inspeccion_funcional'], $_POST['resultado'], $_POST['estado_lote'], $_POST['observaciones']]);
        $mensaje = 'Inspeccion de entrada registrada correctamente';
        $tipo_mensaje = 'success';
    }

    // Inspeccion Proceso
    if ($accion === 'guardar_inspeccion_proceso') {
        $stmt = $pdo->prepare("INSERT INTO qc_inspeccion_proceso (numero_inspeccion, orden_produccion, etapa_proceso, punto_control, muestras_turno, scrap_generado, reproceso, causas_reproceso, medidas_correctivas, inspeccion_linea, resultado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $numero = 'IP-' . date('Ymd') . '-' . rand(1000, 9999);
        $stmt->execute([$numero, $_POST['orden_produccion'], $_POST['etapa_proceso'], $_POST['punto_control'], $_POST['muestras_turno'], $_POST['scrap_generado'], $_POST['reproceso'], $_POST['causas_reproceso'], $_POST['medidas_correctivas'], $_POST['inspeccion_linea'], $_POST['resultado']]);
        $mensaje = 'Inspeccion de proceso registrada correctamente';
        $tipo_mensaje = 'success';
    }

    // Inspeccion Salida
    if ($accion === 'guardar_inspeccion_salida') {
        $stmt = $pdo->prepare("INSERT INTO qc_inspeccion_salida (numero_inspeccion, lote_produccion, producto_terminado, verificacion_funcionamiento, verificacion_embalaje, verificacion_rotulado, muestreo_cantidad, resultado_inspeccion, liberacion_despacho, retencion_calidad, observaciones) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $numero = 'IS-' . date('Ymd') . '-' . rand(1000, 9999);
        $stmt->execute([$numero, $_POST['lote_produccion'], $_POST['producto_terminado'], $_POST['verificacion_funcionamiento'], $_POST['verificacion_embalaje'], $_POST['verificacion_rotulado'], $_POST['muestreo_cantidad'], $_POST['resultado_inspeccion'], isset($_POST['liberacion_despacho']) ? 1 : 0, isset($_POST['retencion_calidad']) ? 1 : 0, $_POST['observaciones']]);
        $mensaje = 'Inspeccion de salida registrada correctamente';
        $tipo_mensaje = 'success';
    }

    // No Conformidades
    if ($accion === 'guardar_nc') {
        $stmt = $pdo->prepare("INSERT INTO qc_no_conformidades (codigo_nc, fecha_nc, tipo_nc, descripcion_nc, producto, proveedor, lote, cantidad_afectada, etapa_detectada, estado_nc) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $codigo = 'NC-' . date('Ymd') . '-' . rand(1000, 9999);
        $stmt->execute([$codigo, $_POST['fecha_nc'], $_POST['tipo_nc'], $_POST['descripcion_nc'], $_POST['producto'], $_POST['proveedor'], $_POST['lote'], $_POST['cantidad_afectada'], $_POST['etapa_detectada'], $_POST['estado_nc']]);
        $mensaje = 'No conformidad registrada correctamente';
        $tipo_mensaje = 'success';
    }

    if ($accion === 'cerrar_nc') {
        $stmt = $pdo->prepare("UPDATE qc_no_conformidades SET estado_nc = 'cerrada', fecha_cierre = CURDATE() WHERE id = ?");
        $stmt->execute([$_POST['nc_id']]);
        $mensaje = 'NC cerrada correctamente';
        $tipo_mensaje = 'success';
    }

    // Acciones Correctivas
    if ($accion === 'guardar_accion') {
        $stmt = $pdo->prepare("INSERT INTO qc_acciones_correctivas (nc_id, tipo_accion, causa_raiz, verificacion_causa, descripcion_accion, responsable, fecha_implementacion, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_POST['nc_id'], $_POST['tipo_accion'], $_POST['causa_raiz'], $_POST['verificacion_causa'], $_POST['descripcion_accion'], $_POST['responsable'], $_POST['fecha_implementacion'], $_POST['estado']]);
        $mensaje = 'Accion registrada correctamente';
        $tipo_mensaje = 'success';
    }

    // Reprocesos
    if ($accion === 'guardar_reproceso') {
        $stmt = $pdo->prepare("INSERT INTO qc_reprocesos (numero_reproceso, lote, producto, cantidad_reproceso, motivo_reproceso, plan_reprocesamiento, costo_reproceso, fecha_inicio, resultado_final) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $numero = 'RP-' . date('Ymd') . '-' . rand(1000, 9999);
        $stmt->execute([$numero, $_POST['lote'], $_POST['producto'], $_POST['cantidad_reproceso'], $_POST['motivo_reproceso'], $_POST['plan_reprocesamiento'], $_POST['costo_reproceso'], $_POST['fecha_inicio'], $_POST['resultado_final']]);
        $mensaje = 'Reproceso registrado correctamente';
        $tipo_mensaje = 'success';
    }

    // Bloqueos
    if ($accion === 'guardar_bloqueo') {
        $stmt = $pdo->prepare("INSERT INTO qc_bloqueos_stock (lote_bloqueado, producto, cantidad_bloqueada, motivo_bloqueo, fecha_bloqueo, cuarentena) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_POST['lote_bloqueado'], $_POST['producto'], $_POST['cantidad_bloqueada'], $_POST['motivo_bloqueo'], $_POST['fecha_bloqueo'], isset($_POST['cuarentena']) ? 1 : 0]);
        $mensaje = 'Bloqueo registrado correctamente';
        $tipo_mensaje = 'success';
    }

    if ($accion === 'levantar_bloqueo') {
        $stmt = $pdo->prepare("UPDATE qc_bloqueos_stock SET levantamiento_bloqueo = 1, fecha_levantamiento = CURDATE(), motivo_levantamiento = ? WHERE id = ?");
        $stmt->execute([$_POST['motivo_levantamiento'], $_POST['bloqueo_id']]);
        $mensaje = 'Bloqueo levantado correctamente';
        $tipo_mensaje = 'success';
    }

    // Documentos
    if ($accion === 'guardar_documento') {
        $stmt = $pdo->prepare("INSERT INTO qc_documentos (tipo_documento, nombre_documento, producto, lote, fecha_emision, fecha_vencimiento, estado, observaciones) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_POST['tipo_documento'], $_POST['nombre_documento'], $_POST['producto'], $_POST['lote'], $_POST['fecha_emision'], $_POST['fecha_vencimiento'], $_POST['estado'], $_POST['observaciones']]);
        $mensaje = 'Documento registrado correctamente';
        $tipo_mensaje = 'success';
    }

    // Parametros
    if ($accion === 'guardar_parametro') {
        $stmt = $pdo->prepare("INSERT INTO qc_parametros (tipo_control, politica_control, norma_calidad, frecuencia_inspeccion, nivel_inspeccion, criterios_aprobacion, criterios_rechazo) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_POST['tipo_control'], $_POST['politica_control'], $_POST['norma_calidad'], $_POST['frecuencia_inspeccion'], $_POST['nivel_inspeccion'], $_POST['criterios_aprobacion'], $_POST['criterios_rechazo']]);
        $mensaje = 'Parametro guardado correctamente';
        $tipo_mensaje = 'success';
    }

    header("Location: control_calidad.php?vista=$vista&msg=" . urlencode($mensaje) . "&tipo=$tipo_mensaje");
    exit;
}

if (isset($_GET['msg'])) {
    $mensaje = $_GET['msg'];
    $tipo_mensaje = isset($_GET['tipo']) ? $_GET['tipo'] : 'success';
}

// KPIs
$total_inspecciones_entrada = $pdo->query("SELECT COUNT(*) FROM qc_inspeccion_entrada")->fetchColumn();
$total_nc_abiertas = $pdo->query("SELECT COUNT(*) FROM qc_no_conformidades WHERE estado_nc = 'abierta'")->fetchColumn();
$total_bloqueos_activos = $pdo->query("SELECT COUNT(*) FROM qc_bloqueos_stock WHERE levantamiento_bloqueo = 0")->fetchColumn();
$total_rechazos = $pdo->query("SELECT COUNT(*) FROM qc_inspeccion_entrada WHERE resultado = 'rechazado'")->fetchColumn();
$tasa_rechazo = $total_inspecciones_entrada > 0 ? round(($total_rechazos / $total_inspecciones_entrada) * 100, 1) : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control de Calidad - CONECTA ERP</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8fafc; color: #1e293b; min-height: 100vh; }
        .header { background: linear-gradient(135deg, #7c3aed 0%, #8b5cf6 100%); padding: 20px 40px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .header h1 { font-size: 24px; display: flex; align-items: center; gap: 10px; color: white; }
        .header a { color: white; text-decoration: none; padding: 10px 20px; background: rgba(255,255,255,0.2); border-radius: 8px; }
        .nav-tabs { background: #ffffff; padding: 0 40px; display: flex; gap: 5px; overflow-x: auto; border-bottom: 2px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .nav-tab { padding: 15px 20px; color: #64748b; text-decoration: none; border-bottom: 3px solid transparent; white-space: nowrap; font-size: 14px; font-weight: 500; }
        .nav-tab:hover, .nav-tab.active { color: #7c3aed; border-bottom-color: #7c3aed; background: rgba(124,58,237,0.05); }
        .container { max-width: 1600px; margin: 0 auto; padding: 30px 40px; }
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: #ffffff; padding: 24px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-left: 4px solid #7c3aed; }
        .stat-card.azul { border-left-color: #3b82f6; }
        .stat-card.naranja { border-left-color: #f59e0b; }
        .stat-card.rojo { border-left-color: #ef4444; }
        .stat-card h3 { font-size: 32px; margin: 8px 0; color: #1e293b; }
        .stat-card p { color: #64748b; font-size: 13px; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px; }
        .card { background: #ffffff; border-radius: 12px; padding: 25px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #e2e8f0; }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #e2e8f0; }
        .card-title { font-size: 18px; font-weight: 600; color: #1e293b; }
        .btn { padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 500; transition: all 0.3s; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary { background: #7c3aed; color: white; }
        .btn-primary:hover { background: #6d28d9; }
        .btn-success { background: #10b981; color: white; }
        .btn-danger { background: #ef4444; color: white; }
        .btn-warning { background: #f59e0b; color: white; }
        .btn-secondary { background: #64748b; color: white; }
        .btn-sm { padding: 6px 12px; font-size: 13px; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 8px; color: #475569; font-size: 14px; font-weight: 500; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 12px; background: #ffffff; border: 1px solid #d1d5db; border-radius: 8px; color: #1e293b; font-size: 14px; }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: #7c3aed; box-shadow: 0 0 0 3px rgba(124,58,237,0.1); }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        th { background: #f8fafc; color: #64748b; font-weight: 600; font-size: 12px; text-transform: uppercase; }
        tr:hover { background: #faf5ff; }
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .badge-info { background: #dbeafe; color: #1e40af; }
        .badge-leve { background: #fef3c7; color: #92400e; }
        .badge-grave { background: #fed7aa; color: #c2410c; }
        .badge-critica { background: #fee2e2; color: #991b1b; }
        .alert { padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: #dcfce7; border: 1px solid #86efac; color: #166534; }
        .alert-error { background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; overflow-y: auto; }
        .modal.active { display: flex; align-items: flex-start; justify-content: center; padding: 40px 20px; }
        .modal-content { background: #ffffff; border-radius: 12px; width: 100%; max-width: 800px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); }
        .modal-header { padding: 20px 25px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
        .modal-body { padding: 25px; max-height: 70vh; overflow-y: auto; }
        .modal-footer { padding: 20px 25px; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 10px; }
        .close-modal { background: none; border: none; color: #64748b; font-size: 24px; cursor: pointer; }
        .empty-state { text-align: center; padding: 60px 20px; color: #64748b; }
        .resultado-ok { color: #10b981; font-weight: 600; }
        .resultado-nok { color: #ef4444; font-weight: 600; }
        @media (max-width: 768px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .container { padding: 20px; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><i class="fas fa-clipboard-check"></i> Control de Calidad</h1>
        <a href="index.php"><i class="fas fa-arrow-left"></i> Volver</a>
    </div>

    <div class="nav-tabs">
        <a href="?vista=dashboard" class="nav-tab <?php echo $vista === 'dashboard' ? 'active' : ''; ?>">Dashboard</a>
        <a href="?vista=entrada" class="nav-tab <?php echo $vista === 'entrada' ? 'active' : ''; ?>">Inspeccion Entrada</a>
        <a href="?vista=proceso" class="nav-tab <?php echo $vista === 'proceso' ? 'active' : ''; ?>">Inspeccion Proceso</a>
        <a href="?vista=salida" class="nav-tab <?php echo $vista === 'salida' ? 'active' : ''; ?>">Inspeccion Salida</a>
        <a href="?vista=nc" class="nav-tab <?php echo $vista === 'nc' ? 'active' : ''; ?>">No Conformidades</a>
        <a href="?vista=acciones" class="nav-tab <?php echo $vista === 'acciones' ? 'active' : ''; ?>">Acciones CAPA</a>
        <a href="?vista=reprocesos" class="nav-tab <?php echo $vista === 'reprocesos' ? 'active' : ''; ?>">Reprocesos</a>
        <a href="?vista=bloqueos" class="nav-tab <?php echo $vista === 'bloqueos' ? 'active' : ''; ?>">Bloqueos Stock</a>
        <a href="?vista=documentos" class="nav-tab <?php echo $vista === 'documentos' ? 'active' : ''; ?>">Documentos</a>
        <a href="?vista=kpis" class="nav-tab <?php echo $vista === 'kpis' ? 'active' : ''; ?>">KPIs</a>
        <a href="?vista=parametros" class="nav-tab <?php echo $vista === 'parametros' ? 'active' : ''; ?>">Parametros</a>
    </div>

    <div class="container">
        <?php if ($mensaje): ?>
            <div class="alert alert-<?php echo $tipo_mensaje === 'success' ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <?php if ($vista === 'dashboard'): ?>
            <div class="stats-grid">
                <div class="stat-card">
                    <p>Inspecciones Entrada</p>
                    <h3><?php echo $total_inspecciones_entrada; ?></h3>
                </div>
                <div class="stat-card rojo">
                    <p>NC Abiertas</p>
                    <h3><?php echo $total_nc_abiertas; ?></h3>
                </div>
                <div class="stat-card naranja">
                    <p>Bloqueos Activos</p>
                    <h3><?php echo $total_bloqueos_activos; ?></h3>
                </div>
                <div class="stat-card azul">
                    <p>Tasa Rechazo</p>
                    <h3><?php echo $tasa_rechazo; ?>%</h3>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <span class="card-title">Ultimas No Conformidades</span>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Codigo</th>
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th>Producto</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $ncs = $pdo->query("SELECT * FROM qc_no_conformidades ORDER BY fecha_creacion DESC LIMIT 10")->fetchAll();
                        foreach ($ncs as $nc):
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($nc['codigo_nc']); ?></td>
                            <td><?php echo $nc['fecha_nc']; ?></td>
                            <td><span class="badge badge-<?php echo $nc['tipo_nc']; ?>"><?php echo ucfirst($nc['tipo_nc']); ?></span></td>
                            <td><?php echo htmlspecialchars($nc['producto']); ?></td>
                            <td><span class="badge badge-<?php echo $nc['estado_nc'] === 'abierta' ? 'danger' : ($nc['estado_nc'] === 'en_proceso' ? 'warning' : 'success'); ?>"><?php echo ucfirst(str_replace('_', ' ', $nc['estado_nc'])); ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($ncs)): ?>
                        <tr><td colspan="5" class="empty-state">No hay no conformidades registradas</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($vista === 'entrada'): ?>
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Inspecciones de Entrada</span>
                    <button class="btn btn-primary" onclick="document.getElementById('modalEntrada').classList.add('active')"><i class="fas fa-plus"></i> Nueva Inspeccion</button>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Numero</th>
                            <th>Proveedor</th>
                            <th>OC</th>
                            <th>Lote</th>
                            <th>Cantidad</th>
                            <th>Rechazado</th>
                            <th>Resultado</th>
                            <th>Estado Lote</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $inspecciones = $pdo->query("SELECT * FROM qc_inspeccion_entrada ORDER BY fecha_inspeccion DESC")->fetchAll();
                        foreach ($inspecciones as $i):
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($i['numero_inspeccion']); ?></td>
                            <td><?php echo htmlspecialchars($i['proveedor']); ?></td>
                            <td><?php echo htmlspecialchars($i['numero_oc']); ?></td>
                            <td><?php echo htmlspecialchars($i['lote']); ?></td>
                            <td><?php echo number_format($i['cantidad_recepcionada'], 0); ?></td>
                            <td><?php echo number_format($i['cantidad_rechazada'], 0); ?></td>
                            <td><span class="badge badge-<?php echo $i['resultado'] === 'aprobado' ? 'success' : ($i['resultado'] === 'rechazado' ? 'danger' : 'warning'); ?>"><?php echo ucfirst($i['resultado']); ?></span></td>
                            <td><span class="badge badge-<?php echo $i['estado_lote'] === 'liberado' ? 'success' : ($i['estado_lote'] === 'bloqueado' ? 'danger' : 'warning'); ?>"><?php echo ucfirst($i['estado_lote']); ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($inspecciones)): ?>
                        <tr><td colspan="8" class="empty-state">No hay inspecciones registradas</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div id="modalEntrada" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Nueva Inspeccion de Entrada</h3>
                        <button class="close-modal" onclick="document.getElementById('modalEntrada').classList.remove('active')">&times;</button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="accion" value="guardar_inspeccion_entrada">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Proveedor</label>
                                    <input type="text" name="proveedor" required>
                                </div>
                                <div class="form-group">
                                    <label>Numero OC</label>
                                    <input type="text" name="numero_oc">
                                </div>
                                <div class="form-group">
                                    <label>Documento Recepcion</label>
                                    <input type="text" name="documento_recepcion">
                                </div>
                                <div class="form-group">
                                    <label>Lote</label>
                                    <input type="text" name="lote" required>
                                </div>
                                <div class="form-group">
                                    <label>Numero Serie</label>
                                    <input type="text" name="numero_serie">
                                </div>
                                <div class="form-group">
                                    <label>Fecha Recepcion</label>
                                    <input type="date" name="fecha_recepcion" value="<?php echo date('Y-m-d'); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Cantidad Recepcionada</label>
                                    <input type="number" name="cantidad_recepcionada" step="0.01" required>
                                </div>
                                <div class="form-group">
                                    <label>Cantidad Rechazada</label>
                                    <input type="number" name="cantidad_rechazada" step="0.01" value="0">
                                </div>
                                <div class="form-group">
                                    <label>Inspeccion Visual</label>
                                    <select name="inspeccion_visual">
                                        <option value="OK">OK</option>
                                        <option value="NO_OK">NO OK</option>
                                        <option value="NA">N/A</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Inspeccion Dimensional</label>
                                    <select name="inspeccion_dimensional">
                                        <option value="OK">OK</option>
                                        <option value="NO_OK">NO OK</option>
                                        <option value="NA">N/A</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Inspeccion Funcional</label>
                                    <select name="inspeccion_funcional">
                                        <option value="OK">OK</option>
                                        <option value="NO_OK">NO OK</option>
                                        <option value="NA">N/A</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Resultado</label>
                                    <select name="resultado">
                                        <option value="aprobado">Aprobado</option>
                                        <option value="rechazado">Rechazado</option>
                                        <option value="reproceso">Reproceso</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Estado Lote</label>
                                    <select name="estado_lote">
                                        <option value="cuarentena">Cuarentena</option>
                                        <option value="liberado">Liberado</option>
                                        <option value="bloqueado">Bloqueado</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Causa Rechazo</label>
                                <textarea name="causa_rechazo" rows="2"></textarea>
                            </div>
                            <div class="form-group">
                                <label>Observaciones</label>
                                <textarea name="observaciones" rows="2"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" onclick="document.getElementById('modalEntrada').classList.remove('active')">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Guardar</button>
                        </div>
                    </form>
                </div>
            </div>

        <?php elseif ($vista === 'proceso'): ?>
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Inspecciones en Proceso</span>
                    <button class="btn btn-primary" onclick="document.getElementById('modalProceso').classList.add('active')"><i class="fas fa-plus"></i> Nueva Inspeccion</button>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Numero</th>
                            <th>Orden Produccion</th>
                            <th>Etapa</th>
                            <th>Punto Control</th>
                            <th>Scrap</th>
                            <th>Reproceso</th>
                            <th>Resultado</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $inspecciones = $pdo->query("SELECT * FROM qc_inspeccion_proceso ORDER BY fecha_inspeccion DESC")->fetchAll();
                        foreach ($inspecciones as $i):
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($i['numero_inspeccion']); ?></td>
                            <td><?php echo htmlspecialchars($i['orden_produccion']); ?></td>
                            <td><?php echo htmlspecialchars($i['etapa_proceso']); ?></td>
                            <td><?php echo htmlspecialchars($i['punto_control']); ?></td>
                            <td><?php echo number_format($i['scrap_generado'], 2); ?></td>
                            <td><?php echo number_format($i['reproceso'], 2); ?></td>
                            <td><span class="badge badge-<?php echo $i['resultado'] === 'aprobado' ? 'success' : ($i['resultado'] === 'rechazado' ? 'danger' : 'warning'); ?>"><?php echo ucfirst($i['resultado']); ?></span></td>
                            <td><?php echo date('d/m/Y', strtotime($i['fecha_inspeccion'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($inspecciones)): ?>
                        <tr><td colspan="8" class="empty-state">No hay inspecciones registradas</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div id="modalProceso" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Nueva Inspeccion en Proceso</h3>
                        <button class="close-modal" onclick="document.getElementById('modalProceso').classList.remove('active')">&times;</button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="accion" value="guardar_inspeccion_proceso">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Orden Produccion</label>
                                    <input type="text" name="orden_produccion" required>
                                </div>
                                <div class="form-group">
                                    <label>Etapa Proceso</label>
                                    <input type="text" name="etapa_proceso" required>
                                </div>
                                <div class="form-group">
                                    <label>Punto de Control</label>
                                    <input type="text" name="punto_control">
                                </div>
                                <div class="form-group">
                                    <label>Muestras por Turno</label>
                                    <input type="number" name="muestras_turno" value="0">
                                </div>
                                <div class="form-group">
                                    <label>Scrap Generado</label>
                                    <input type="number" name="scrap_generado" step="0.01" value="0">
                                </div>
                                <div class="form-group">
                                    <label>Reproceso</label>
                                    <input type="number" name="reproceso" step="0.01" value="0">
                                </div>
                                <div class="form-group">
                                    <label>Inspeccion en Linea</label>
                                    <select name="inspeccion_linea">
                                        <option value="OK">OK</option>
                                        <option value="NO_OK">NO OK</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Resultado</label>
                                    <select name="resultado">
                                        <option value="aprobado">Aprobado</option>
                                        <option value="rechazado">Rechazado</option>
                                        <option value="reproceso">Reproceso</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Causas Reproceso</label>
                                <textarea name="causas_reproceso" rows="2"></textarea>
                            </div>
                            <div class="form-group">
                                <label>Medidas Correctivas</label>
                                <textarea name="medidas_correctivas" rows="2"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" onclick="document.getElementById('modalProceso').classList.remove('active')">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Guardar</button>
                        </div>
                    </form>
                </div>
            </div>

        <?php elseif ($vista === 'salida'): ?>
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Inspecciones de Salida (Producto Terminado)</span>
                    <button class="btn btn-primary" onclick="document.getElementById('modalSalida').classList.add('active')"><i class="fas fa-plus"></i> Nueva Inspeccion</button>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Numero</th>
                            <th>Lote Produccion</th>
                            <th>Producto</th>
                            <th>Funcionamiento</th>
                            <th>Embalaje</th>
                            <th>Rotulado</th>
                            <th>Resultado</th>
                            <th>Liberado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $inspecciones = $pdo->query("SELECT * FROM qc_inspeccion_salida ORDER BY fecha_inspeccion DESC")->fetchAll();
                        foreach ($inspecciones as $i):
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($i['numero_inspeccion']); ?></td>
                            <td><?php echo htmlspecialchars($i['lote_produccion']); ?></td>
                            <td><?php echo htmlspecialchars($i['producto_terminado']); ?></td>
                            <td class="<?php echo $i['verificacion_funcionamiento'] === 'OK' ? 'resultado-ok' : 'resultado-nok'; ?>"><?php echo $i['verificacion_funcionamiento']; ?></td>
                            <td class="<?php echo $i['verificacion_embalaje'] === 'OK' ? 'resultado-ok' : 'resultado-nok'; ?>"><?php echo $i['verificacion_embalaje']; ?></td>
                            <td class="<?php echo $i['verificacion_rotulado'] === 'OK' ? 'resultado-ok' : 'resultado-nok'; ?>"><?php echo $i['verificacion_rotulado']; ?></td>
                            <td><span class="badge badge-<?php echo $i['resultado_inspeccion'] === 'OK' ? 'success' : 'danger'; ?>"><?php echo $i['resultado_inspeccion']; ?></span></td>
                            <td><?php echo $i['liberacion_despacho'] ? '<i class="fas fa-check-circle" style="color:#10b981"></i>' : '<i class="fas fa-times-circle" style="color:#ef4444"></i>'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($inspecciones)): ?>
                        <tr><td colspan="8" class="empty-state">No hay inspecciones registradas</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div id="modalSalida" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Nueva Inspeccion de Salida</h3>
                        <button class="close-modal" onclick="document.getElementById('modalSalida').classList.remove('active')">&times;</button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="accion" value="guardar_inspeccion_salida">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Lote Produccion</label>
                                    <input type="text" name="lote_produccion" required>
                                </div>
                                <div class="form-group">
                                    <label>Producto Terminado</label>
                                    <input type="text" name="producto_terminado" required>
                                </div>
                                <div class="form-group">
                                    <label>Verificacion Funcionamiento</label>
                                    <select name="verificacion_funcionamiento">
                                        <option value="OK">OK</option>
                                        <option value="NO_OK">NO OK</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Verificacion Embalaje</label>
                                    <select name="verificacion_embalaje">
                                        <option value="OK">OK</option>
                                        <option value="NO_OK">NO OK</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Verificacion Rotulado</label>
                                    <select name="verificacion_rotulado">
                                        <option value="OK">OK</option>
                                        <option value="NO_OK">NO OK</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Cantidad Muestreo</label>
                                    <input type="number" name="muestreo_cantidad" value="0">
                                </div>
                                <div class="form-group">
                                    <label>Resultado Inspeccion</label>
                                    <select name="resultado_inspeccion">
                                        <option value="OK">OK</option>
                                        <option value="NO_OK">NO OK</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label><input type="checkbox" name="liberacion_despacho" value="1"> Liberacion para Despacho</label>
                                </div>
                                <div class="form-group">
                                    <label><input type="checkbox" name="retencion_calidad" value="1"> Retencion por Calidad</label>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Observaciones</label>
                                <textarea name="observaciones" rows="2"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" onclick="document.getElementById('modalSalida').classList.remove('active')">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Guardar</button>
                        </div>
                    </form>
                </div>
            </div>

        <?php elseif ($vista === 'nc'): ?>
            <div class="card">
                <div class="card-header">
                    <span class="card-title">No Conformidades</span>
                    <button class="btn btn-primary" onclick="document.getElementById('modalNC').classList.add('active')"><i class="fas fa-plus"></i> Nueva NC</button>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Codigo</th>
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th>Producto</th>
                            <th>Proveedor</th>
                            <th>Lote</th>
                            <th>Cantidad</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $ncs = $pdo->query("SELECT * FROM qc_no_conformidades ORDER BY fecha_creacion DESC")->fetchAll();
                        foreach ($ncs as $nc):
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($nc['codigo_nc']); ?></strong></td>
                            <td><?php echo $nc['fecha_nc']; ?></td>
                            <td><span class="badge badge-<?php echo $nc['tipo_nc']; ?>"><?php echo ucfirst($nc['tipo_nc']); ?></span></td>
                            <td><?php echo htmlspecialchars($nc['producto']); ?></td>
                            <td><?php echo htmlspecialchars($nc['proveedor']); ?></td>
                            <td><?php echo htmlspecialchars($nc['lote']); ?></td>
                            <td><?php echo number_format($nc['cantidad_afectada'], 0); ?></td>
                            <td><span class="badge badge-<?php echo $nc['estado_nc'] === 'abierta' ? 'danger' : ($nc['estado_nc'] === 'en_proceso' ? 'warning' : 'success'); ?>"><?php echo ucfirst(str_replace('_', ' ', $nc['estado_nc'])); ?></span></td>
                            <td>
                                <?php if ($nc['estado_nc'] !== 'cerrada'): ?>
                                <form method="POST" style="display:inline">
                                    <input type="hidden" name="accion" value="cerrar_nc">
                                    <input type="hidden" name="nc_id" value="<?php echo $nc['id']; ?>">
                                    <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Cerrar esta NC?')">Cerrar</button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($ncs)): ?>
                        <tr><td colspan="9" class="empty-state">No hay no conformidades registradas</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div id="modalNC" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Nueva No Conformidad</h3>
                        <button class="close-modal" onclick="document.getElementById('modalNC').classList.remove('active')">&times;</button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="accion" value="guardar_nc">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Fecha NC</label>
                                    <input type="date" name="fecha_nc" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Tipo NC</label>
                                    <select name="tipo_nc">
                                        <option value="leve">Leve</option>
                                        <option value="grave">Grave</option>
                                        <option value="critica">Critica</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Producto</label>
                                    <input type="text" name="producto">
                                </div>
                                <div class="form-group">
                                    <label>Proveedor</label>
                                    <input type="text" name="proveedor">
                                </div>
                                <div class="form-group">
                                    <label>Lote</label>
                                    <input type="text" name="lote">
                                </div>
                                <div class="form-group">
                                    <label>Cantidad Afectada</label>
                                    <input type="number" name="cantidad_afectada" step="0.01" value="0">
                                </div>
                                <div class="form-group">
                                    <label>Etapa Detectada</label>
                                    <input type="text" name="etapa_detectada">
                                </div>
                                <div class="form-group">
                                    <label>Estado</label>
                                    <select name="estado_nc">
                                        <option value="abierta">Abierta</option>
                                        <option value="en_proceso">En Proceso</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Descripcion NC</label>
                                <textarea name="descripcion_nc" rows="3" required></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" onclick="document.getElementById('modalNC').classList.remove('active')">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Guardar</button>
                        </div>
                    </form>
                </div>
            </div>

        <?php elseif ($vista === 'acciones'): ?>
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Acciones Correctivas y Preventivas (CAPA)</span>
                    <button class="btn btn-primary" onclick="document.getElementById('modalAccion').classList.add('active')"><i class="fas fa-plus"></i> Nueva Accion</button>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>NC Asociada</th>
                            <th>Tipo</th>
                            <th>Descripcion</th>
                            <th>Responsable</th>
                            <th>Fecha Impl.</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $acciones = $pdo->query("SELECT a.*, n.codigo_nc FROM qc_acciones_correctivas a LEFT JOIN qc_no_conformidades n ON a.nc_id = n.id ORDER BY a.fecha_creacion DESC")->fetchAll();
                        foreach ($acciones as $a):
                        ?>
                        <tr>
                            <td><?php echo $a['id']; ?></td>
                            <td><?php echo htmlspecialchars($a['codigo_nc']); ?></td>
                            <td><span class="badge badge-<?php echo $a['tipo_accion'] === 'correctiva' ? 'danger' : 'info'; ?>"><?php echo ucfirst($a['tipo_accion']); ?></span></td>
                            <td><?php echo htmlspecialchars(substr($a['descripcion_accion'], 0, 50)); ?>...</td>
                            <td><?php echo htmlspecialchars($a['responsable']); ?></td>
                            <td><?php echo $a['fecha_implementacion']; ?></td>
                            <td><span class="badge badge-<?php echo $a['estado'] === 'cerrada' ? 'success' : ($a['estado'] === 'en_proceso' ? 'warning' : 'danger'); ?>"><?php echo ucfirst(str_replace('_', ' ', $a['estado'])); ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($acciones)): ?>
                        <tr><td colspan="7" class="empty-state">No hay acciones registradas</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div id="modalAccion" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Nueva Accion CAPA</h3>
                        <button class="close-modal" onclick="document.getElementById('modalAccion').classList.remove('active')">&times;</button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="accion" value="guardar_accion">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>NC Asociada</label>
                                    <select name="nc_id">
                                        <option value="">Sin NC</option>
                                        <?php
                                        $ncs_list = $pdo->query("SELECT id, codigo_nc FROM qc_no_conformidades WHERE estado_nc != 'cerrada' ORDER BY fecha_creacion DESC")->fetchAll();
                                        foreach ($ncs_list as $nc): ?>
                                        <option value="<?php echo $nc['id']; ?>"><?php echo htmlspecialchars($nc['codigo_nc']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Tipo Accion</label>
                                    <select name="tipo_accion">
                                        <option value="correctiva">Correctiva</option>
                                        <option value="preventiva">Preventiva</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Responsable</label>
                                    <input type="text" name="responsable" required>
                                </div>
                                <div class="form-group">
                                    <label>Fecha Implementacion</label>
                                    <input type="date" name="fecha_implementacion">
                                </div>
                                <div class="form-group">
                                    <label>Estado</label>
                                    <select name="estado">
                                        <option value="pendiente">Pendiente</option>
                                        <option value="en_proceso">En Proceso</option>
                                        <option value="cerrada">Cerrada</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Causa Raiz</label>
                                <textarea name="causa_raiz" rows="2"></textarea>
                            </div>
                            <div class="form-group">
                                <label>Verificacion Causa</label>
                                <textarea name="verificacion_causa" rows="2"></textarea>
                            </div>
                            <div class="form-group">
                                <label>Descripcion de la Accion</label>
                                <textarea name="descripcion_accion" rows="3" required></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" onclick="document.getElementById('modalAccion').classList.remove('active')">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Guardar</button>
                        </div>
                    </form>
                </div>
            </div>

        <?php elseif ($vista === 'reprocesos'): ?>
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Reprocesos</span>
                    <button class="btn btn-primary" onclick="document.getElementById('modalReproceso').classList.add('active')"><i class="fas fa-plus"></i> Nuevo Reproceso</button>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Numero</th>
                            <th>Lote</th>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Motivo</th>
                            <th>Costo</th>
                            <th>Resultado</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $reprocesos = $pdo->query("SELECT * FROM qc_reprocesos ORDER BY fecha_creacion DESC")->fetchAll();
                        foreach ($reprocesos as $r):
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($r['numero_reproceso']); ?></td>
                            <td><?php echo htmlspecialchars($r['lote']); ?></td>
                            <td><?php echo htmlspecialchars($r['producto']); ?></td>
                            <td><?php echo number_format($r['cantidad_reproceso'], 0); ?></td>
                            <td><?php echo htmlspecialchars(substr($r['motivo_reproceso'], 0, 30)); ?>...</td>
                            <td>$<?php echo number_format($r['costo_reproceso'], 0); ?></td>
                            <td><span class="badge badge-<?php echo $r['resultado_final'] === 'OK' ? 'success' : 'danger'; ?>"><?php echo $r['resultado_final']; ?></span></td>
                            <td><?php echo $r['fecha_inicio']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($reprocesos)): ?>
                        <tr><td colspan="8" class="empty-state">No hay reprocesos registrados</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div id="modalReproceso" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Nuevo Reproceso</h3>
                        <button class="close-modal" onclick="document.getElementById('modalReproceso').classList.remove('active')">&times;</button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="accion" value="guardar_reproceso">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Lote</label>
                                    <input type="text" name="lote" required>
                                </div>
                                <div class="form-group">
                                    <label>Producto</label>
                                    <input type="text" name="producto" required>
                                </div>
                                <div class="form-group">
                                    <label>Cantidad Reproceso</label>
                                    <input type="number" name="cantidad_reproceso" step="0.01" required>
                                </div>
                                <div class="form-group">
                                    <label>Costo Reproceso</label>
                                    <input type="number" name="costo_reproceso" step="0.01" value="0">
                                </div>
                                <div class="form-group">
                                    <label>Fecha Inicio</label>
                                    <input type="date" name="fecha_inicio" value="<?php echo date('Y-m-d'); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Resultado Final</label>
                                    <select name="resultado_final">
                                        <option value="OK">OK</option>
                                        <option value="rechazo_final">Rechazo Final</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Motivo Reproceso</label>
                                <textarea name="motivo_reproceso" rows="2" required></textarea>
                            </div>
                            <div class="form-group">
                                <label>Plan de Reprocesamiento</label>
                                <textarea name="plan_reprocesamiento" rows="2"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" onclick="document.getElementById('modalReproceso').classList.remove('active')">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Guardar</button>
                        </div>
                    </form>
                </div>
            </div>

        <?php elseif ($vista === 'bloqueos'): ?>
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Bloqueos de Stock</span>
                    <button class="btn btn-primary" onclick="document.getElementById('modalBloqueo').classList.add('active')"><i class="fas fa-plus"></i> Nuevo Bloqueo</button>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Lote</th>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Motivo</th>
                            <th>Fecha Bloqueo</th>
                            <th>Cuarentena</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $bloqueos = $pdo->query("SELECT * FROM qc_bloqueos_stock ORDER BY fecha_creacion DESC")->fetchAll();
                        foreach ($bloqueos as $b):
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($b['lote_bloqueado']); ?></strong></td>
                            <td><?php echo htmlspecialchars($b['producto']); ?></td>
                            <td><?php echo number_format($b['cantidad_bloqueada'], 0); ?></td>
                            <td><?php echo htmlspecialchars(substr($b['motivo_bloqueo'], 0, 30)); ?>...</td>
                            <td><?php echo $b['fecha_bloqueo']; ?></td>
                            <td><?php echo $b['cuarentena'] ? '<i class="fas fa-check" style="color:#f59e0b"></i>' : '-'; ?></td>
                            <td><span class="badge badge-<?php echo $b['levantamiento_bloqueo'] ? 'success' : 'danger'; ?>"><?php echo $b['levantamiento_bloqueo'] ? 'Liberado' : 'Bloqueado'; ?></span></td>
                            <td>
                                <?php if (!$b['levantamiento_bloqueo']): ?>
                                <button class="btn btn-success btn-sm" onclick="document.getElementById('modalLevantar<?php echo $b['id']; ?>').classList.add('active')">Levantar</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($bloqueos)): ?>
                        <tr><td colspan="8" class="empty-state">No hay bloqueos registrados</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php foreach ($bloqueos as $b): ?>
            <?php if (!$b['levantamiento_bloqueo']): ?>
            <div id="modalLevantar<?php echo $b['id']; ?>" class="modal">
                <div class="modal-content" style="max-width:500px">
                    <div class="modal-header">
                        <h3>Levantar Bloqueo</h3>
                        <button class="close-modal" onclick="document.getElementById('modalLevantar<?php echo $b['id']; ?>').classList.remove('active')">&times;</button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="accion" value="levantar_bloqueo">
                            <input type="hidden" name="bloqueo_id" value="<?php echo $b['id']; ?>">
                            <div class="form-group">
                                <label>Motivo del Levantamiento</label>
                                <textarea name="motivo_levantamiento" rows="3" required></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" onclick="document.getElementById('modalLevantar<?php echo $b['id']; ?>').classList.remove('active')">Cancelar</button>
                            <button type="submit" class="btn btn-success">Confirmar Levantamiento</button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>
            <?php endforeach; ?>

            <div id="modalBloqueo" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Nuevo Bloqueo de Stock</h3>
                        <button class="close-modal" onclick="document.getElementById('modalBloqueo').classList.remove('active')">&times;</button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="accion" value="guardar_bloqueo">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Lote a Bloquear</label>
                                    <input type="text" name="lote_bloqueado" required>
                                </div>
                                <div class="form-group">
                                    <label>Producto</label>
                                    <input type="text" name="producto">
                                </div>
                                <div class="form-group">
                                    <label>Cantidad Bloqueada</label>
                                    <input type="number" name="cantidad_bloqueada" step="0.01" required>
                                </div>
                                <div class="form-group">
                                    <label>Fecha Bloqueo</label>
                                    <input type="date" name="fecha_bloqueo" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label><input type="checkbox" name="cuarentena" value="1"> Cuarentena</label>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Motivo del Bloqueo</label>
                                <textarea name="motivo_bloqueo" rows="3" required></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" onclick="document.getElementById('modalBloqueo').classList.remove('active')">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Guardar</button>
                        </div>
                    </form>
                </div>
            </div>

        <?php elseif ($vista === 'documentos'): ?>
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Documentos de Calidad</span>
                    <button class="btn btn-primary" onclick="document.getElementById('modalDocumento').classList.add('active')"><i class="fas fa-plus"></i> Nuevo Documento</button>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Tipo</th>
                            <th>Nombre</th>
                            <th>Producto</th>
                            <th>Lote</th>
                            <th>Emision</th>
                            <th>Vencimiento</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $documentos = $pdo->query("SELECT * FROM qc_documentos ORDER BY fecha_creacion DESC")->fetchAll();
                        foreach ($documentos as $d):
                        ?>
                        <tr>
                            <td><?php echo ucfirst(str_replace('_', ' ', $d['tipo_documento'])); ?></td>
                            <td><?php echo htmlspecialchars($d['nombre_documento']); ?></td>
                            <td><?php echo htmlspecialchars($d['producto']); ?></td>
                            <td><?php echo htmlspecialchars($d['lote']); ?></td>
                            <td><?php echo $d['fecha_emision']; ?></td>
                            <td><?php echo $d['fecha_vencimiento']; ?></td>
                            <td><span class="badge badge-<?php echo $d['estado'] === 'vigente' ? 'success' : ($d['estado'] === 'por_vencer' ? 'warning' : 'danger'); ?>"><?php echo ucfirst(str_replace('_', ' ', $d['estado'])); ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($documentos)): ?>
                        <tr><td colspan="7" class="empty-state">No hay documentos registrados</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div id="modalDocumento" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Nuevo Documento</h3>
                        <button class="close-modal" onclick="document.getElementById('modalDocumento').classList.remove('active')">&times;</button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="accion" value="guardar_documento">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Tipo Documento</label>
                                    <select name="tipo_documento">
                                        <option value="ficha_tecnica">Ficha Tecnica</option>
                                        <option value="hoja_seguridad">Hoja de Seguridad</option>
                                        <option value="certificado_calidad">Certificado Calidad</option>
                                        <option value="certificado_origen">Certificado Origen</option>
                                        <option value="manual">Manual</option>
                                        <option value="informe_laboratorio">Informe Laboratorio</option>
                                        <option value="ensayo">Ensayo</option>
                                        <option value="otro">Otro</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Nombre Documento</label>
                                    <input type="text" name="nombre_documento" required>
                                </div>
                                <div class="form-group">
                                    <label>Producto</label>
                                    <input type="text" name="producto">
                                </div>
                                <div class="form-group">
                                    <label>Lote</label>
                                    <input type="text" name="lote">
                                </div>
                                <div class="form-group">
                                    <label>Fecha Emision</label>
                                    <input type="date" name="fecha_emision">
                                </div>
                                <div class="form-group">
                                    <label>Fecha Vencimiento</label>
                                    <input type="date" name="fecha_vencimiento">
                                </div>
                                <div class="form-group">
                                    <label>Estado</label>
                                    <select name="estado">
                                        <option value="vigente">Vigente</option>
                                        <option value="por_vencer">Por Vencer</option>
                                        <option value="vencido">Vencido</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Observaciones</label>
                                <textarea name="observaciones" rows="2"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" onclick="document.getElementById('modalDocumento').classList.remove('active')">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Guardar</button>
                        </div>
                    </form>
                </div>
            </div>

        <?php elseif ($vista === 'kpis'): ?>
            <div class="stats-grid">
                <div class="stat-card">
                    <p>Total Inspecciones</p>
                    <h3><?php echo $pdo->query("SELECT COUNT(*) FROM qc_inspeccion_entrada")->fetchColumn() + $pdo->query("SELECT COUNT(*) FROM qc_inspeccion_proceso")->fetchColumn() + $pdo->query("SELECT COUNT(*) FROM qc_inspeccion_salida")->fetchColumn(); ?></h3>
                </div>
                <div class="stat-card azul">
                    <p>Tasa Aprobacion</p>
                    <h3><?php
                    $aprobados = $pdo->query("SELECT COUNT(*) FROM qc_inspeccion_entrada WHERE resultado = 'aprobado'")->fetchColumn();
                    echo $total_inspecciones_entrada > 0 ? round(($aprobados / $total_inspecciones_entrada) * 100, 1) : 0; ?>%</h3>
                </div>
                <div class="stat-card naranja">
                    <p>NC Este Mes</p>
                    <h3><?php echo $pdo->query("SELECT COUNT(*) FROM qc_no_conformidades WHERE MONTH(fecha_nc) = MONTH(CURDATE())")->fetchColumn(); ?></h3>
                </div>
                <div class="stat-card rojo">
                    <p>Costo Reprocesos</p>
                    <h3>$<?php echo number_format($pdo->query("SELECT COALESCE(SUM(costo_reproceso), 0) FROM qc_reprocesos")->fetchColumn(), 0); ?></h3>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <span class="card-title">Indicadores de Calidad (KPIs)</span>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Indicador</th>
                            <th>Valor</th>
                            <th>Descripcion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Indice de No Conformidades</td>
                            <td><strong><?php echo $total_nc_abiertas; ?></strong></td>
                            <td>NC abiertas actualmente</td>
                        </tr>
                        <tr>
                            <td>Porcentaje Scrap</td>
                            <td><strong><?php
                            $total_scrap = $pdo->query("SELECT COALESCE(SUM(scrap_generado), 0) FROM qc_inspeccion_proceso")->fetchColumn();
                            echo number_format($total_scrap, 2); ?></strong></td>
                            <td>Unidades de scrap generadas</td>
                        </tr>
                        <tr>
                            <td>Porcentaje Reproceso</td>
                            <td><strong><?php echo $pdo->query("SELECT COUNT(*) FROM qc_reprocesos")->fetchColumn(); ?></strong></td>
                            <td>Total de reprocesos</td>
                        </tr>
                        <tr>
                            <td>Tasa Rechazo Entrada</td>
                            <td><strong><?php echo $tasa_rechazo; ?>%</strong></td>
                            <td>Rechazos sobre inspecciones de entrada</td>
                        </tr>
                        <tr>
                            <td>Lotes Bloqueados</td>
                            <td><strong><?php echo $total_bloqueos_activos; ?></strong></td>
                            <td>Lotes actualmente bloqueados</td>
                        </tr>
                        <tr>
                            <td>Acciones CAPA Pendientes</td>
                            <td><strong><?php echo $pdo->query("SELECT COUNT(*) FROM qc_acciones_correctivas WHERE estado != 'cerrada'")->fetchColumn(); ?></strong></td>
                            <td>Acciones correctivas/preventivas abiertas</td>
                        </tr>
                    </tbody>
                </table>
            </div>

        <?php elseif ($vista === 'parametros'): ?>
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Parametros de Control de Calidad</span>
                    <button class="btn btn-primary" onclick="document.getElementById('modalParametro').classList.add('active')"><i class="fas fa-plus"></i> Nuevo Parametro</button>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Tipo Control</th>
                            <th>Politica</th>
                            <th>Norma</th>
                            <th>Frecuencia</th>
                            <th>Nivel</th>
                            <th>Activo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $parametros = $pdo->query("SELECT * FROM qc_parametros ORDER BY fecha_creacion DESC")->fetchAll();
                        foreach ($parametros as $p):
                        ?>
                        <tr>
                            <td><?php echo ucfirst($p['tipo_control']); ?></td>
                            <td><?php echo str_replace('_', ' ', $p['politica_control']); ?></td>
                            <td><?php echo htmlspecialchars($p['norma_calidad']); ?></td>
                            <td><?php echo htmlspecialchars($p['frecuencia_inspeccion']); ?></td>
                            <td><?php echo htmlspecialchars($p['nivel_inspeccion']); ?></td>
                            <td><?php echo $p['activo'] ? '<i class="fas fa-check" style="color:#10b981"></i>' : '<i class="fas fa-times" style="color:#ef4444"></i>'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($parametros)): ?>
                        <tr><td colspan="6" class="empty-state">No hay parametros configurados</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div id="modalParametro" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Nuevo Parametro</h3>
                        <button class="close-modal" onclick="document.getElementById('modalParametro').classList.remove('active')">&times;</button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="accion" value="guardar_parametro">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Tipo Control</label>
                                    <select name="tipo_control">
                                        <option value="entrada">Entrada</option>
                                        <option value="proceso">Proceso</option>
                                        <option value="salida">Salida</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Politica Control</label>
                                    <select name="politica_control">
                                        <option value="AQL">AQL</option>
                                        <option value="muestreo">Muestreo</option>
                                        <option value="100_inspeccion">100% Inspeccion</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Norma Calidad</label>
                                    <input type="text" name="norma_calidad" placeholder="ISO 9001, HACCP, GMP...">
                                </div>
                                <div class="form-group">
                                    <label>Frecuencia Inspeccion</label>
                                    <input type="text" name="frecuencia_inspeccion" placeholder="Diaria, Semanal, Por lote...">
                                </div>
                                <div class="form-group">
                                    <label>Nivel Inspeccion</label>
                                    <input type="text" name="nivel_inspeccion" placeholder="I, II, III...">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Criterios de Aprobacion</label>
                                <textarea name="criterios_aprobacion" rows="2"></textarea>
                            </div>
                            <div class="form-group">
                                <label>Criterios de Rechazo</label>
                                <textarea name="criterios_rechazo" rows="2"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" onclick="document.getElementById('modalParametro').classList.remove('active')">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Guardar</button>
                        </div>
                    </form>
                </div>
            </div>

        <?php endif; ?>
    </div>
</body>
</html>
