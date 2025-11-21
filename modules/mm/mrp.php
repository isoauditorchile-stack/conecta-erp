<?php
/**
 * MODULO: Planificacion MRP (Material Requirements Planning)
 * Descripcion: Sistema completo de planificacion de requerimientos de materiales
 * Sin dependencias - Sin AJAX - Sin sidebar - UTF-8
 * Conexion directa a SQL
 */

// Configuracion de conexion a base de datos
$db_host = 'localhost';
$db_name = 'conectae_conectaerpbd';
$db_user = 'conectae_conectaerpuser';
$db_pass = 'pt125824caraud';

try {
    $pdo = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
        $db_user,
        $db_pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die('Error de conexion: ' . $e->getMessage());
}

// Iniciar sesion
session_start();

// Variables
$mensaje = '';
$tipo_mensaje = '';
$vista = isset($_GET['vista']) ? $_GET['vista'] : 'dashboard';
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;

// =====================================================
// CREAR TABLAS SI NO EXISTEN
// =====================================================

$sql_crear_tablas = "
-- Tabla: Lista de Materiales (BOM)
CREATE TABLE IF NOT EXISTS mrp_lista_materiales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo_bom VARCHAR(50) NOT NULL,
    producto_padre_id INT NOT NULL,
    descripcion VARCHAR(255),
    version VARCHAR(20) DEFAULT '1.0',
    estado ENUM('activo','inactivo','revision') DEFAULT 'activo',
    fecha_vigencia_desde DATE,
    fecha_vigencia_hasta DATE,
    creado_por INT,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_producto (producto_padre_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla: Componentes BOM
CREATE TABLE IF NOT EXISTS mrp_bom_componentes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bom_id INT NOT NULL,
    material_id INT NOT NULL,
    cantidad DECIMAL(15,4) NOT NULL,
    unidad_medida VARCHAR(20),
    nivel INT DEFAULT 1,
    posicion VARCHAR(20),
    merma_porcentaje DECIMAL(5,2) DEFAULT 0,
    es_critico TINYINT(1) DEFAULT 0,
    alternativo_id INT,
    FOREIGN KEY (bom_id) REFERENCES mrp_lista_materiales(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla: Centros de Trabajo
CREATE TABLE IF NOT EXISTS mrp_centros_trabajo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) NOT NULL,
    nombre VARCHAR(255) NOT NULL,
    tipo ENUM('maquina','linea','manual','externo') DEFAULT 'maquina',
    capacidad_hora DECIMAL(10,2),
    capacidad_diaria DECIMAL(10,2),
    costo_hora DECIMAL(15,2),
    estado ENUM('activo','mantenimiento','inactivo') DEFAULT 'activo',
    eficiencia DECIMAL(5,2) DEFAULT 100.00,
    INDEX idx_codigo (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla: Rutas de Produccion
CREATE TABLE IF NOT EXISTS mrp_rutas_produccion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo_ruta VARCHAR(50) NOT NULL,
    producto_id INT NOT NULL,
    descripcion VARCHAR(255),
    version VARCHAR(20) DEFAULT '1.0',
    estado ENUM('activo','inactivo') DEFAULT 'activo',
    INDEX idx_producto (producto_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla: Operaciones de Ruta
CREATE TABLE IF NOT EXISTS mrp_operaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ruta_id INT NOT NULL,
    secuencia INT NOT NULL,
    centro_trabajo_id INT NOT NULL,
    descripcion VARCHAR(255),
    tiempo_preparacion DECIMAL(10,2) DEFAULT 0,
    tiempo_proceso DECIMAL(10,2) DEFAULT 0,
    tiempo_espera DECIMAL(10,2) DEFAULT 0,
    unidad_tiempo VARCHAR(20) DEFAULT 'minutos',
    FOREIGN KEY (ruta_id) REFERENCES mrp_rutas_produccion(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla: Demanda
CREATE TABLE IF NOT EXISTS mrp_demanda (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo ENUM('pedido_cliente','demanda_interna','proyeccion','reserva') DEFAULT 'pedido_cliente',
    producto_id INT NOT NULL,
    cantidad DECIMAL(15,4) NOT NULL,
    fecha_requerida DATE NOT NULL,
    prioridad INT DEFAULT 5,
    estado ENUM('pendiente','confirmada','en_proceso','completada','cancelada') DEFAULT 'pendiente',
    referencia VARCHAR(100),
    cliente_id INT,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_producto (producto_id),
    INDEX idx_fecha (fecha_requerida)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla: Calculo MRP (Necesidades)
CREATE TABLE IF NOT EXISTS mrp_necesidades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    calculo_id INT NOT NULL,
    producto_id INT NOT NULL,
    periodo DATE NOT NULL,
    necesidad_bruta DECIMAL(15,4) DEFAULT 0,
    recepciones_programadas DECIMAL(15,4) DEFAULT 0,
    inventario_proyectado DECIMAL(15,4) DEFAULT 0,
    necesidad_neta DECIMAL(15,4) DEFAULT 0,
    recepcion_planeada DECIMAL(15,4) DEFAULT 0,
    liberacion_planeada DECIMAL(15,4) DEFAULT 0,
    INDEX idx_calculo (calculo_id),
    INDEX idx_producto (producto_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla: Ordenes Sugeridas
CREATE TABLE IF NOT EXISTS mrp_ordenes_sugeridas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    calculo_id INT NOT NULL,
    tipo ENUM('compra','produccion','traslado') NOT NULL,
    producto_id INT NOT NULL,
    cantidad DECIMAL(15,4) NOT NULL,
    fecha_inicio DATE,
    fecha_fin DATE,
    proveedor_id INT,
    centro_trabajo_id INT,
    estado ENUM('sugerida','confirmada','convertida','cancelada') DEFAULT 'sugerida',
    prioridad INT DEFAULT 5,
    observaciones TEXT,
    INDEX idx_tipo (tipo),
    INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla: Plan Maestro Produccion (MPS)
CREATE TABLE IF NOT EXISTS mrp_plan_maestro (
    id INT AUTO_INCREMENT PRIMARY KEY,
    producto_id INT NOT NULL,
    periodo DATE NOT NULL,
    cantidad_planificada DECIMAL(15,4) DEFAULT 0,
    cantidad_confirmada DECIMAL(15,4) DEFAULT 0,
    cantidad_producida DECIMAL(15,4) DEFAULT 0,
    capacidad_requerida DECIMAL(10,2) DEFAULT 0,
    estado ENUM('borrador','confirmado','en_proceso','completado') DEFAULT 'borrador',
    INDEX idx_producto (producto_id),
    INDEX idx_periodo (periodo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla: Parametros MRP
CREATE TABLE IF NOT EXISTS mrp_parametros (
    id INT AUTO_INCREMENT PRIMARY KEY,
    producto_id INT NOT NULL,
    lead_time_compra INT DEFAULT 0,
    lead_time_produccion INT DEFAULT 0,
    stock_seguridad DECIMAL(15,4) DEFAULT 0,
    lote_minimo DECIMAL(15,4) DEFAULT 1,
    lote_maximo DECIMAL(15,4),
    lote_multiplo DECIMAL(15,4) DEFAULT 1,
    politica_lotificacion ENUM('lote_fijo','lote_multiplo','lote_por_lote','eoq') DEFAULT 'lote_por_lote',
    punto_reorden DECIMAL(15,4) DEFAULT 0,
    UNIQUE KEY idx_producto (producto_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla: Historial Calculos MRP
CREATE TABLE IF NOT EXISTS mrp_historial_calculos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fecha_calculo DATETIME DEFAULT CURRENT_TIMESTAMP,
    usuario_id INT,
    horizonte_inicio DATE,
    horizonte_fin DATE,
    tipo_calculo ENUM('completo','parcial','regeneracion') DEFAULT 'completo',
    estado ENUM('en_proceso','completado','error') DEFAULT 'en_proceso',
    productos_procesados INT DEFAULT 0,
    ordenes_generadas INT DEFAULT 0,
    tiempo_ejecucion INT DEFAULT 0,
    observaciones TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla: Alertas MRP
CREATE TABLE IF NOT EXISTS mrp_alertas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo ENUM('falta_material','capacidad_insuficiente','retraso_proveedor','retraso_produccion','orden_vencida','sobrecarga') NOT NULL,
    producto_id INT,
    centro_trabajo_id INT,
    mensaje TEXT NOT NULL,
    severidad ENUM('baja','media','alta','critica') DEFAULT 'media',
    fecha_alerta DATETIME DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('activa','leida','resuelta') DEFAULT 'activa',
    INDEX idx_tipo (tipo),
    INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla: Capacidad Centros de Trabajo
CREATE TABLE IF NOT EXISTS mrp_capacidad (
    id INT AUTO_INCREMENT PRIMARY KEY,
    centro_trabajo_id INT NOT NULL,
    fecha DATE NOT NULL,
    capacidad_disponible DECIMAL(10,2) DEFAULT 0,
    capacidad_utilizada DECIMAL(10,2) DEFAULT 0,
    capacidad_reservada DECIMAL(10,2) DEFAULT 0,
    turno VARCHAR(20) DEFAULT 'normal',
    UNIQUE KEY idx_centro_fecha (centro_trabajo_id, fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

// Ejecutar creacion de tablas
try {
    $pdo->exec($sql_crear_tablas);
} catch (PDOException $e) {
    // Tablas ya existen o error menor
}

// =====================================================
// PROCESAMIENTO DE FORMULARIOS
// =====================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    switch ($action) {
        // Crear BOM
        case 'crear_bom':
            $codigo = trim($_POST['codigo_bom']);
            $producto_id = intval($_POST['producto_id']);
            $descripcion = trim($_POST['descripcion']);
            $version = trim($_POST['version']);

            $stmt = $pdo->prepare("INSERT INTO mrp_lista_materiales (codigo_bom, producto_padre_id, descripcion, version, creado_por) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$codigo, $producto_id, $descripcion, $version, $user_id]);

            $mensaje = 'BOM creado exitosamente';
            $tipo_mensaje = 'success';
            break;

        // Agregar componente a BOM
        case 'agregar_componente':
            $bom_id = intval($_POST['bom_id']);
            $material_id = intval($_POST['material_id']);
            $cantidad = floatval($_POST['cantidad']);
            $nivel = intval($_POST['nivel']);
            $merma = floatval($_POST['merma']);

            $stmt = $pdo->prepare("INSERT INTO mrp_bom_componentes (bom_id, material_id, cantidad, nivel, merma_porcentaje) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$bom_id, $material_id, $cantidad, $nivel, $merma]);

            $mensaje = 'Componente agregado exitosamente';
            $tipo_mensaje = 'success';
            break;

        // Crear Centro de Trabajo
        case 'crear_centro_trabajo':
            $codigo = trim($_POST['codigo']);
            $nombre = trim($_POST['nombre']);
            $tipo = $_POST['tipo'];
            $capacidad_hora = floatval($_POST['capacidad_hora']);
            $costo_hora = floatval($_POST['costo_hora']);

            $stmt = $pdo->prepare("INSERT INTO mrp_centros_trabajo (codigo, nombre, tipo, capacidad_hora, costo_hora) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$codigo, $nombre, $tipo, $capacidad_hora, $costo_hora]);

            $mensaje = 'Centro de trabajo creado exitosamente';
            $tipo_mensaje = 'success';
            break;

        // Registrar Demanda
        case 'registrar_demanda':
            $tipo = $_POST['tipo_demanda'];
            $producto_id = intval($_POST['producto_id']);
            $cantidad = floatval($_POST['cantidad']);
            $fecha_req = $_POST['fecha_requerida'];
            $prioridad = intval($_POST['prioridad']);
            $referencia = trim($_POST['referencia']);

            $stmt = $pdo->prepare("INSERT INTO mrp_demanda (tipo, producto_id, cantidad, fecha_requerida, prioridad, referencia) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$tipo, $producto_id, $cantidad, $fecha_req, $prioridad, $referencia]);

            $mensaje = 'Demanda registrada exitosamente';
            $tipo_mensaje = 'success';
            break;

        // Ejecutar Calculo MRP
        case 'ejecutar_mrp':
            $horizonte_inicio = $_POST['horizonte_inicio'];
            $horizonte_fin = $_POST['horizonte_fin'];
            $tipo_calculo = $_POST['tipo_calculo'];

            // Crear registro de calculo
            $stmt = $pdo->prepare("INSERT INTO mrp_historial_calculos (usuario_id, horizonte_inicio, horizonte_fin, tipo_calculo) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user_id, $horizonte_inicio, $horizonte_fin, $tipo_calculo]);
            $calculo_id = $pdo->lastInsertId();

            // Obtener demandas del periodo
            $stmt = $pdo->prepare("SELECT * FROM mrp_demanda WHERE fecha_requerida BETWEEN ? AND ? AND estado IN ('pendiente','confirmada')");
            $stmt->execute([$horizonte_inicio, $horizonte_fin]);
            $demandas = $stmt->fetchAll();

            $ordenes_generadas = 0;
            $productos_procesados = 0;

            foreach ($demandas as $demanda) {
                $producto_id = $demanda['producto_id'];
                $cantidad_requerida = $demanda['cantidad'];
                $fecha_req = $demanda['fecha_requerida'];

                // Obtener parametros del producto
                $stmt = $pdo->prepare("SELECT * FROM mrp_parametros WHERE producto_id = ?");
                $stmt->execute([$producto_id]);
                $params = $stmt->fetch();

                $lead_time = $params ? $params['lead_time_compra'] : 7;
                $stock_seg = $params ? $params['stock_seguridad'] : 0;
                $lote_min = $params ? $params['lote_minimo'] : 1;

                // Calcular necesidad neta (simplificado)
                $necesidad_neta = max(0, $cantidad_requerida + $stock_seg);

                // Ajustar a lote minimo
                if ($necesidad_neta > 0 && $necesidad_neta < $lote_min) {
                    $necesidad_neta = $lote_min;
                }

                if ($necesidad_neta > 0) {
                    // Registrar necesidad
                    $stmt = $pdo->prepare("INSERT INTO mrp_necesidades (calculo_id, producto_id, periodo, necesidad_bruta, necesidad_neta, liberacion_planeada) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$calculo_id, $producto_id, $fecha_req, $cantidad_requerida, $necesidad_neta, $necesidad_neta]);

                    // Generar orden sugerida
                    $fecha_inicio = date('Y-m-d', strtotime($fecha_req . " -$lead_time days"));
                    $stmt = $pdo->prepare("INSERT INTO mrp_ordenes_sugeridas (calculo_id, tipo, producto_id, cantidad, fecha_inicio, fecha_fin) VALUES (?, 'compra', ?, ?, ?, ?)");
                    $stmt->execute([$calculo_id, $producto_id, $necesidad_neta, $fecha_inicio, $fecha_req]);

                    $ordenes_generadas++;
                }
                $productos_procesados++;
            }

            // Actualizar registro de calculo
            $stmt = $pdo->prepare("UPDATE mrp_historial_calculos SET estado = 'completado', productos_procesados = ?, ordenes_generadas = ? WHERE id = ?");
            $stmt->execute([$productos_procesados, $ordenes_generadas, $calculo_id]);

            $mensaje = "Calculo MRP completado. Productos: $productos_procesados, Ordenes generadas: $ordenes_generadas";
            $tipo_mensaje = 'success';
            break;

        // Confirmar Orden Sugerida
        case 'confirmar_orden':
            $orden_id = intval($_POST['orden_id']);
            $stmt = $pdo->prepare("UPDATE mrp_ordenes_sugeridas SET estado = 'confirmada' WHERE id = ?");
            $stmt->execute([$orden_id]);

            $mensaje = 'Orden confirmada exitosamente';
            $tipo_mensaje = 'success';
            break;

        // Crear Plan Maestro
        case 'crear_plan_maestro':
            $producto_id = intval($_POST['producto_id']);
            $periodo = $_POST['periodo'];
            $cantidad = floatval($_POST['cantidad_planificada']);

            $stmt = $pdo->prepare("INSERT INTO mrp_plan_maestro (producto_id, periodo, cantidad_planificada) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE cantidad_planificada = ?");
            $stmt->execute([$producto_id, $periodo, $cantidad, $cantidad]);

            $mensaje = 'Plan maestro actualizado';
            $tipo_mensaje = 'success';
            break;

        // Configurar Parametros MRP
        case 'configurar_parametros':
            $producto_id = intval($_POST['producto_id']);
            $lead_compra = intval($_POST['lead_time_compra']);
            $lead_prod = intval($_POST['lead_time_produccion']);
            $stock_seg = floatval($_POST['stock_seguridad']);
            $lote_min = floatval($_POST['lote_minimo']);
            $politica = $_POST['politica'];

            $stmt = $pdo->prepare("INSERT INTO mrp_parametros (producto_id, lead_time_compra, lead_time_produccion, stock_seguridad, lote_minimo, politica_lotificacion)
                                   VALUES (?, ?, ?, ?, ?, ?)
                                   ON DUPLICATE KEY UPDATE lead_time_compra = ?, lead_time_produccion = ?, stock_seguridad = ?, lote_minimo = ?, politica_lotificacion = ?");
            $stmt->execute([$producto_id, $lead_compra, $lead_prod, $stock_seg, $lote_min, $politica, $lead_compra, $lead_prod, $stock_seg, $lote_min, $politica]);

            $mensaje = 'Parametros configurados exitosamente';
            $tipo_mensaje = 'success';
            break;

        // Eliminar registros
        case 'eliminar':
            $tabla = $_POST['tabla'];
            $id = intval($_POST['id']);
            $tablas_permitidas = ['mrp_lista_materiales', 'mrp_centros_trabajo', 'mrp_demanda', 'mrp_ordenes_sugeridas'];
            if (in_array($tabla, $tablas_permitidas)) {
                $stmt = $pdo->prepare("DELETE FROM $tabla WHERE id = ?");
                $stmt->execute([$id]);
                $mensaje = 'Registro eliminado';
                $tipo_mensaje = 'success';
            }
            break;
    }

    // Redirigir para evitar reenvio
    if ($mensaje) {
        $_SESSION['mensaje'] = $mensaje;
        $_SESSION['tipo_mensaje'] = $tipo_mensaje;
        header("Location: mrp.php?vista=$vista");
        exit;
    }
}

// Recuperar mensaje de sesion
if (isset($_SESSION['mensaje'])) {
    $mensaje = $_SESSION['mensaje'];
    $tipo_mensaje = $_SESSION['tipo_mensaje'];
    unset($_SESSION['mensaje'], $_SESSION['tipo_mensaje']);
}

// =====================================================
// CONSULTAS PARA MOSTRAR DATOS
// =====================================================

// Dashboard - Estadisticas
$stmt = $pdo->query("SELECT COUNT(*) as total FROM mrp_lista_materiales WHERE estado = 'activo'");
$total_bom = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM mrp_demanda WHERE estado IN ('pendiente','confirmada')");
$demanda_pendiente = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM mrp_ordenes_sugeridas WHERE estado = 'sugerida'");
$ordenes_sugeridas = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM mrp_alertas WHERE estado = 'activa'");
$alertas_activas = $stmt->fetch()['total'];

// Obtener datos segun vista
switch ($vista) {
    case 'bom':
        $stmt = $pdo->query("SELECT * FROM mrp_lista_materiales ORDER BY fecha_creacion DESC");
        $lista_bom = $stmt->fetchAll();
        break;

    case 'centros_trabajo':
        $stmt = $pdo->query("SELECT * FROM mrp_centros_trabajo ORDER BY codigo");
        $centros = $stmt->fetchAll();
        break;

    case 'demanda':
        $stmt = $pdo->query("SELECT * FROM mrp_demanda ORDER BY fecha_requerida, prioridad");
        $demandas = $stmt->fetchAll();
        break;

    case 'ordenes':
        $stmt = $pdo->query("SELECT * FROM mrp_ordenes_sugeridas ORDER BY fecha_fin, prioridad");
        $ordenes = $stmt->fetchAll();
        break;

    case 'plan_maestro':
        $stmt = $pdo->query("SELECT * FROM mrp_plan_maestro ORDER BY periodo, producto_id");
        $plan = $stmt->fetchAll();
        break;

    case 'historial':
        $stmt = $pdo->query("SELECT * FROM mrp_historial_calculos ORDER BY fecha_calculo DESC LIMIT 50");
        $historial = $stmt->fetchAll();
        break;

    case 'alertas':
        $stmt = $pdo->query("SELECT * FROM mrp_alertas ORDER BY fecha_alerta DESC");
        $alertas = $stmt->fetchAll();
        break;

    case 'parametros':
        $stmt = $pdo->query("SELECT * FROM mrp_parametros ORDER BY producto_id");
        $parametros = $stmt->fetchAll();
        break;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planificacion MRP - CONECTA ERP</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #0f172a; color: #e2e8f0; min-height: 100vh; }
        .header { background: linear-gradient(135deg, #1e40af 0%, #7c3aed 100%); padding: 20px 40px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 24px; display: flex; align-items: center; gap: 10px; }
        .header a { color: white; text-decoration: none; padding: 10px 20px; background: rgba(255,255,255,0.1); border-radius: 8px; }
        .nav-tabs { background: #1e293b; padding: 0 40px; display: flex; gap: 5px; overflow-x: auto; border-bottom: 1px solid #334155; }
        .nav-tab { padding: 15px 20px; color: #94a3b8; text-decoration: none; border-bottom: 3px solid transparent; white-space: nowrap; }
        .nav-tab:hover, .nav-tab.active { color: #60a5fa; border-bottom-color: #60a5fa; background: rgba(96,165,250,0.1); }
        .container { max-width: 1600px; margin: 0 auto; padding: 30px 40px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%); padding: 25px; border-radius: 12px; }
        .stat-card.verde { background: linear-gradient(135deg, #059669 0%, #10b981 100%); }
        .stat-card.naranja { background: linear-gradient(135deg, #d97706 0%, #f59e0b 100%); }
        .stat-card.rojo { background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%); }
        .stat-card h3 { font-size: 36px; margin: 10px 0; }
        .stat-card p { opacity: 0.9; font-size: 14px; }
        .card { background: #1e293b; border-radius: 12px; padding: 25px; margin-bottom: 20px; border: 1px solid #334155; }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #334155; }
        .card-title { font-size: 18px; font-weight: 600; display: flex; align-items: center; gap: 10px; }
        .btn { padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 500; transition: all 0.3s; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary { background: #3b82f6; color: white; }
        .btn-primary:hover { background: #2563eb; }
        .btn-success { background: #10b981; color: white; }
        .btn-success:hover { background: #059669; }
        .btn-danger { background: #ef4444; color: white; }
        .btn-danger:hover { background: #dc2626; }
        .btn-warning { background: #f59e0b; color: white; }
        .btn-sm { padding: 6px 12px; font-size: 13px; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 8px; color: #94a3b8; font-size: 14px; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 12px; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: #e2e8f0; font-size: 14px; }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: #3b82f6; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #334155; }
        th { background: #0f172a; color: #94a3b8; font-weight: 600; font-size: 13px; text-transform: uppercase; }
        tr:hover { background: rgba(59, 130, 246, 0.1); }
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 500; }
        .badge-success { background: rgba(16, 185, 129, 0.2); color: #10b981; }
        .badge-warning { background: rgba(245, 158, 11, 0.2); color: #f59e0b; }
        .badge-danger { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
        .badge-info { background: rgba(59, 130, 246, 0.2); color: #60a5fa; }
        .badge-secondary { background: rgba(148, 163, 184, 0.2); color: #94a3b8; }
        .alert { padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .alert-success { background: rgba(16, 185, 129, 0.2); border: 1px solid #10b981; color: #10b981; }
        .alert-error { background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #ef4444; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 1000; overflow-y: auto; }
        .modal.active { display: flex; align-items: flex-start; justify-content: center; padding: 40px 20px; }
        .modal-content { background: #1e293b; border-radius: 12px; width: 100%; max-width: 700px; border: 1px solid #334155; }
        .modal-header { padding: 20px 25px; border-bottom: 1px solid #334155; display: flex; justify-content: space-between; align-items: center; }
        .modal-body { padding: 25px; }
        .modal-footer { padding: 20px 25px; border-top: 1px solid #334155; display: flex; justify-content: flex-end; gap: 10px; }
        .close-modal { background: none; border: none; color: #94a3b8; font-size: 24px; cursor: pointer; }
        .close-modal:hover { color: white; }
        .empty-state { text-align: center; padding: 60px 20px; color: #64748b; }
        .empty-state i { font-size: 48px; margin-bottom: 15px; opacity: 0.5; }
        .action-btns { display: flex; gap: 5px; }
        @media (max-width: 768px) {
            .container { padding: 20px; }
            .header { padding: 15px 20px; }
            .nav-tabs { padding: 0 20px; }
            .form-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><i class="fas fa-project-diagram"></i> Planificacion MRP</h1>
        <a href="/modules/mm/index.php"><i class="fas fa-arrow-left"></i> Volver</a>
    </div>

    <nav class="nav-tabs">
        <a href="?vista=dashboard" class="nav-tab <?php echo $vista === 'dashboard' ? 'active' : ''; ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="?vista=bom" class="nav-tab <?php echo $vista === 'bom' ? 'active' : ''; ?>"><i class="fas fa-sitemap"></i> BOM</a>
        <a href="?vista=centros_trabajo" class="nav-tab <?php echo $vista === 'centros_trabajo' ? 'active' : ''; ?>"><i class="fas fa-industry"></i> Centros Trabajo</a>
        <a href="?vista=demanda" class="nav-tab <?php echo $vista === 'demanda' ? 'active' : ''; ?>"><i class="fas fa-chart-line"></i> Demanda</a>
        <a href="?vista=calculo" class="nav-tab <?php echo $vista === 'calculo' ? 'active' : ''; ?>"><i class="fas fa-calculator"></i> Ejecutar MRP</a>
        <a href="?vista=ordenes" class="nav-tab <?php echo $vista === 'ordenes' ? 'active' : ''; ?>"><i class="fas fa-clipboard-list"></i> Ordenes Sugeridas</a>
        <a href="?vista=plan_maestro" class="nav-tab <?php echo $vista === 'plan_maestro' ? 'active' : ''; ?>"><i class="fas fa-calendar-alt"></i> Plan Maestro</a>
        <a href="?vista=parametros" class="nav-tab <?php echo $vista === 'parametros' ? 'active' : ''; ?>"><i class="fas fa-cog"></i> Parametros</a>
        <a href="?vista=alertas" class="nav-tab <?php echo $vista === 'alertas' ? 'active' : ''; ?>"><i class="fas fa-bell"></i> Alertas</a>
        <a href="?vista=historial" class="nav-tab <?php echo $vista === 'historial' ? 'active' : ''; ?>"><i class="fas fa-history"></i> Historial</a>
    </nav>

    <div class="container">
        <?php if ($mensaje): ?>
            <div class="alert alert-<?php echo $tipo_mensaje; ?>">
                <i class="fas fa-<?php echo $tipo_mensaje === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <!-- DASHBOARD -->
        <?php if ($vista === 'dashboard'): ?>
            <div class="stats-grid">
                <div class="stat-card">
                    <p><i class="fas fa-sitemap"></i> Lista de Materiales (BOM)</p>
                    <h3><?php echo $total_bom; ?></h3>
                    <p>Activas</p>
                </div>
                <div class="stat-card verde">
                    <p><i class="fas fa-chart-line"></i> Demanda Pendiente</p>
                    <h3><?php echo $demanda_pendiente; ?></h3>
                    <p>Por procesar</p>
                </div>
                <div class="stat-card naranja">
                    <p><i class="fas fa-clipboard-list"></i> Ordenes Sugeridas</p>
                    <h3><?php echo $ordenes_sugeridas; ?></h3>
                    <p>Por confirmar</p>
                </div>
                <div class="stat-card rojo">
                    <p><i class="fas fa-bell"></i> Alertas Activas</p>
                    <h3><?php echo $alertas_activas; ?></h3>
                    <p>Requieren atencion</p>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-info-circle"></i> Acciones Rapidas</h3>
                </div>
                <div class="form-grid">
                    <a href="?vista=calculo" class="btn btn-primary" style="justify-content: center; padding: 20px;">
                        <i class="fas fa-calculator"></i> Ejecutar Calculo MRP
                    </a>
                    <a href="?vista=demanda" class="btn btn-success" style="justify-content: center; padding: 20px;">
                        <i class="fas fa-plus"></i> Registrar Demanda
                    </a>
                    <a href="?vista=ordenes" class="btn btn-warning" style="justify-content: center; padding: 20px;">
                        <i class="fas fa-clipboard-check"></i> Revisar Ordenes
                    </a>
                    <a href="?vista=alertas" class="btn btn-danger" style="justify-content: center; padding: 20px;">
                        <i class="fas fa-bell"></i> Ver Alertas
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <!-- BOM -->
        <?php if ($vista === 'bom'): ?>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-sitemap"></i> Lista de Materiales (BOM)</h3>
                    <button class="btn btn-primary" onclick="openModal('modalBOM')"><i class="fas fa-plus"></i> Nueva BOM</button>
                </div>
                <?php if (empty($lista_bom)): ?>
                    <div class="empty-state">
                        <i class="fas fa-sitemap"></i>
                        <h3>No hay BOM registradas</h3>
                        <p>Crea tu primera lista de materiales</p>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Codigo</th>
                                <th>Producto ID</th>
                                <th>Descripcion</th>
                                <th>Version</th>
                                <th>Estado</th>
                                <th>Fecha</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lista_bom as $bom): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($bom['codigo_bom']); ?></strong></td>
                                    <td><?php echo $bom['producto_padre_id']; ?></td>
                                    <td><?php echo htmlspecialchars($bom['descripcion']); ?></td>
                                    <td><?php echo htmlspecialchars($bom['version']); ?></td>
                                    <td><span class="badge badge-<?php echo $bom['estado'] === 'activo' ? 'success' : 'warning'; ?>"><?php echo strtoupper($bom['estado']); ?></span></td>
                                    <td><?php echo date('d/m/Y', strtotime($bom['fecha_creacion'])); ?></td>
                                    <td class="action-btns">
                                        <button class="btn btn-sm btn-primary"><i class="fas fa-eye"></i></button>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Eliminar?');">
                                            <input type="hidden" name="action" value="eliminar">
                                            <input type="hidden" name="tabla" value="mrp_lista_materiales">
                                            <input type="hidden" name="id" value="<?php echo $bom['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- CENTROS DE TRABAJO -->
        <?php if ($vista === 'centros_trabajo'): ?>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-industry"></i> Centros de Trabajo</h3>
                    <button class="btn btn-primary" onclick="openModal('modalCentro')"><i class="fas fa-plus"></i> Nuevo Centro</button>
                </div>
                <?php if (empty($centros)): ?>
                    <div class="empty-state">
                        <i class="fas fa-industry"></i>
                        <h3>No hay centros de trabajo</h3>
                        <p>Registra tu primer centro de trabajo</p>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Codigo</th>
                                <th>Nombre</th>
                                <th>Tipo</th>
                                <th>Capacidad/Hora</th>
                                <th>Costo/Hora</th>
                                <th>Eficiencia</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($centros as $centro): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($centro['codigo']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($centro['nombre']); ?></td>
                                    <td><?php echo strtoupper($centro['tipo']); ?></td>
                                    <td><?php echo number_format($centro['capacidad_hora'], 2); ?></td>
                                    <td>$<?php echo number_format($centro['costo_hora'], 0, ',', '.'); ?></td>
                                    <td><?php echo $centro['eficiencia']; ?>%</td>
                                    <td><span class="badge badge-<?php echo $centro['estado'] === 'activo' ? 'success' : 'warning'; ?>"><?php echo strtoupper($centro['estado']); ?></span></td>
                                    <td class="action-btns">
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Eliminar?');">
                                            <input type="hidden" name="action" value="eliminar">
                                            <input type="hidden" name="tabla" value="mrp_centros_trabajo">
                                            <input type="hidden" name="id" value="<?php echo $centro['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- DEMANDA -->
        <?php if ($vista === 'demanda'): ?>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-chart-line"></i> Gestion de Demanda</h3>
                    <button class="btn btn-primary" onclick="openModal('modalDemanda')"><i class="fas fa-plus"></i> Registrar Demanda</button>
                </div>
                <?php if (empty($demandas)): ?>
                    <div class="empty-state">
                        <i class="fas fa-chart-line"></i>
                        <h3>No hay demanda registrada</h3>
                        <p>Registra la demanda para ejecutar el MRP</p>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Tipo</th>
                                <th>Producto ID</th>
                                <th>Cantidad</th>
                                <th>Fecha Requerida</th>
                                <th>Prioridad</th>
                                <th>Referencia</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($demandas as $dem): ?>
                                <tr>
                                    <td><?php echo strtoupper(str_replace('_', ' ', $dem['tipo'])); ?></td>
                                    <td><?php echo $dem['producto_id']; ?></td>
                                    <td><strong><?php echo number_format($dem['cantidad'], 2); ?></strong></td>
                                    <td><?php echo date('d/m/Y', strtotime($dem['fecha_requerida'])); ?></td>
                                    <td><span class="badge badge-<?php echo $dem['prioridad'] <= 3 ? 'danger' : ($dem['prioridad'] <= 6 ? 'warning' : 'info'); ?>"><?php echo $dem['prioridad']; ?></span></td>
                                    <td><?php echo htmlspecialchars($dem['referencia']); ?></td>
                                    <td><span class="badge badge-<?php echo $dem['estado'] === 'completada' ? 'success' : ($dem['estado'] === 'pendiente' ? 'warning' : 'info'); ?>"><?php echo strtoupper($dem['estado']); ?></span></td>
                                    <td class="action-btns">
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Eliminar?');">
                                            <input type="hidden" name="action" value="eliminar">
                                            <input type="hidden" name="tabla" value="mrp_demanda">
                                            <input type="hidden" name="id" value="<?php echo $dem['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- EJECUTAR MRP -->
        <?php if ($vista === 'calculo'): ?>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-calculator"></i> Ejecutar Calculo MRP</h3>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="ejecutar_mrp">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Horizonte Inicio</label>
                            <input type="date" name="horizonte_inicio" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Horizonte Fin</label>
                            <input type="date" name="horizonte_fin" value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Tipo de Calculo</label>
                            <select name="tipo_calculo" required>
                                <option value="completo">Completo (Todos los productos)</option>
                                <option value="parcial">Parcial (Solo demanda nueva)</option>
                                <option value="regeneracion">Regeneracion (Recalcular todo)</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success" style="margin-top: 20px;">
                        <i class="fas fa-play"></i> Ejecutar Calculo MRP
                    </button>
                </form>
            </div>
        <?php endif; ?>

        <!-- ORDENES SUGERIDAS -->
        <?php if ($vista === 'ordenes'): ?>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-clipboard-list"></i> Ordenes Sugeridas</h3>
                </div>
                <?php if (empty($ordenes)): ?>
                    <div class="empty-state">
                        <i class="fas fa-clipboard-list"></i>
                        <h3>No hay ordenes sugeridas</h3>
                        <p>Ejecuta el calculo MRP para generar ordenes</p>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Tipo</th>
                                <th>Producto ID</th>
                                <th>Cantidad</th>
                                <th>Fecha Inicio</th>
                                <th>Fecha Fin</th>
                                <th>Prioridad</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ordenes as $orden): ?>
                                <tr>
                                    <td><span class="badge badge-<?php echo $orden['tipo'] === 'compra' ? 'info' : ($orden['tipo'] === 'produccion' ? 'success' : 'warning'); ?>"><?php echo strtoupper($orden['tipo']); ?></span></td>
                                    <td><?php echo $orden['producto_id']; ?></td>
                                    <td><strong><?php echo number_format($orden['cantidad'], 2); ?></strong></td>
                                    <td><?php echo $orden['fecha_inicio'] ? date('d/m/Y', strtotime($orden['fecha_inicio'])) : '-'; ?></td>
                                    <td><?php echo $orden['fecha_fin'] ? date('d/m/Y', strtotime($orden['fecha_fin'])) : '-'; ?></td>
                                    <td><?php echo $orden['prioridad']; ?></td>
                                    <td><span class="badge badge-<?php echo $orden['estado'] === 'confirmada' ? 'success' : ($orden['estado'] === 'sugerida' ? 'warning' : 'secondary'); ?>"><?php echo strtoupper($orden['estado']); ?></span></td>
                                    <td class="action-btns">
                                        <?php if ($orden['estado'] === 'sugerida'): ?>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="confirmar_orden">
                                                <input type="hidden" name="orden_id" value="<?php echo $orden['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-success"><i class="fas fa-check"></i></button>
                                            </form>
                                        <?php endif; ?>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Eliminar?');">
                                            <input type="hidden" name="action" value="eliminar">
                                            <input type="hidden" name="tabla" value="mrp_ordenes_sugeridas">
                                            <input type="hidden" name="id" value="<?php echo $orden['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- PLAN MAESTRO -->
        <?php if ($vista === 'plan_maestro'): ?>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-calendar-alt"></i> Plan Maestro de Produccion (MPS)</h3>
                    <button class="btn btn-primary" onclick="openModal('modalPlan')"><i class="fas fa-plus"></i> Agregar Plan</button>
                </div>
                <?php if (empty($plan)): ?>
                    <div class="empty-state">
                        <i class="fas fa-calendar-alt"></i>
                        <h3>No hay plan maestro</h3>
                        <p>Crea tu plan de produccion</p>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Producto ID</th>
                                <th>Periodo</th>
                                <th>Planificada</th>
                                <th>Confirmada</th>
                                <th>Producida</th>
                                <th>Capacidad Req.</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($plan as $p): ?>
                                <tr>
                                    <td><?php echo $p['producto_id']; ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($p['periodo'])); ?></td>
                                    <td><?php echo number_format($p['cantidad_planificada'], 2); ?></td>
                                    <td><?php echo number_format($p['cantidad_confirmada'], 2); ?></td>
                                    <td><?php echo number_format($p['cantidad_producida'], 2); ?></td>
                                    <td><?php echo number_format($p['capacidad_requerida'], 2); ?> hrs</td>
                                    <td><span class="badge badge-<?php echo $p['estado'] === 'completado' ? 'success' : ($p['estado'] === 'confirmado' ? 'info' : 'warning'); ?>"><?php echo strtoupper($p['estado']); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- PARAMETROS -->
        <?php if ($vista === 'parametros'): ?>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-cog"></i> Parametros MRP por Producto</h3>
                    <button class="btn btn-primary" onclick="openModal('modalParametros')"><i class="fas fa-plus"></i> Configurar</button>
                </div>
                <?php if (empty($parametros)): ?>
                    <div class="empty-state">
                        <i class="fas fa-cog"></i>
                        <h3>No hay parametros configurados</h3>
                        <p>Configura los parametros MRP para tus productos</p>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Producto ID</th>
                                <th>Lead Time Compra</th>
                                <th>Lead Time Prod.</th>
                                <th>Stock Seguridad</th>
                                <th>Lote Minimo</th>
                                <th>Politica</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($parametros as $param): ?>
                                <tr>
                                    <td><?php echo $param['producto_id']; ?></td>
                                    <td><?php echo $param['lead_time_compra']; ?> dias</td>
                                    <td><?php echo $param['lead_time_produccion']; ?> dias</td>
                                    <td><?php echo number_format($param['stock_seguridad'], 2); ?></td>
                                    <td><?php echo number_format($param['lote_minimo'], 2); ?></td>
                                    <td><?php echo strtoupper(str_replace('_', ' ', $param['politica_lotificacion'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- ALERTAS -->
        <?php if ($vista === 'alertas'): ?>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-bell"></i> Alertas y Excepciones</h3>
                </div>
                <?php if (empty($alertas)): ?>
                    <div class="empty-state">
                        <i class="fas fa-bell"></i>
                        <h3>No hay alertas activas</h3>
                        <p>El sistema esta funcionando correctamente</p>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Tipo</th>
                                <th>Mensaje</th>
                                <th>Severidad</th>
                                <th>Fecha</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($alertas as $alerta): ?>
                                <tr>
                                    <td><?php echo strtoupper(str_replace('_', ' ', $alerta['tipo'])); ?></td>
                                    <td><?php echo htmlspecialchars($alerta['mensaje']); ?></td>
                                    <td><span class="badge badge-<?php echo $alerta['severidad'] === 'critica' ? 'danger' : ($alerta['severidad'] === 'alta' ? 'warning' : 'info'); ?>"><?php echo strtoupper($alerta['severidad']); ?></span></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($alerta['fecha_alerta'])); ?></td>
                                    <td><span class="badge badge-<?php echo $alerta['estado'] === 'activa' ? 'danger' : 'success'; ?>"><?php echo strtoupper($alerta['estado']); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- HISTORIAL -->
        <?php if ($vista === 'historial'): ?>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-history"></i> Historial de Calculos MRP</h3>
                </div>
                <?php if (empty($historial)): ?>
                    <div class="empty-state">
                        <i class="fas fa-history"></i>
                        <h3>No hay historial</h3>
                        <p>Ejecuta el calculo MRP para ver el historial</p>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Fecha Calculo</th>
                                <th>Horizonte</th>
                                <th>Tipo</th>
                                <th>Productos</th>
                                <th>Ordenes</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($historial as $h): ?>
                                <tr>
                                    <td><?php echo $h['id']; ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($h['fecha_calculo'])); ?></td>
                                    <td><?php echo date('d/m', strtotime($h['horizonte_inicio'])); ?> - <?php echo date('d/m/Y', strtotime($h['horizonte_fin'])); ?></td>
                                    <td><?php echo strtoupper($h['tipo_calculo']); ?></td>
                                    <td><?php echo $h['productos_procesados']; ?></td>
                                    <td><?php echo $h['ordenes_generadas']; ?></td>
                                    <td><span class="badge badge-<?php echo $h['estado'] === 'completado' ? 'success' : ($h['estado'] === 'error' ? 'danger' : 'warning'); ?>"><?php echo strtoupper($h['estado']); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- MODALES -->
    <!-- Modal BOM -->
    <div id="modalBOM" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-sitemap"></i> Nueva Lista de Materiales (BOM)</h3>
                <button class="close-modal" onclick="closeModal('modalBOM')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="crear_bom">
                <div class="modal-body">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Codigo BOM</label>
                            <input type="text" name="codigo_bom" required>
                        </div>
                        <div class="form-group">
                            <label>Producto Padre ID</label>
                            <input type="number" name="producto_id" required>
                        </div>
                        <div class="form-group">
                            <label>Version</label>
                            <input type="text" name="version" value="1.0">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Descripcion</label>
                        <textarea name="descripcion" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('modalBOM')">Cancelar</button>
                    <button type="submit" class="btn btn-success">Crear BOM</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Centro de Trabajo -->
    <div id="modalCentro" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-industry"></i> Nuevo Centro de Trabajo</h3>
                <button class="close-modal" onclick="closeModal('modalCentro')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="crear_centro_trabajo">
                <div class="modal-body">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Codigo</label>
                            <input type="text" name="codigo" required>
                        </div>
                        <div class="form-group">
                            <label>Nombre</label>
                            <input type="text" name="nombre" required>
                        </div>
                        <div class="form-group">
                            <label>Tipo</label>
                            <select name="tipo" required>
                                <option value="maquina">Maquina</option>
                                <option value="linea">Linea</option>
                                <option value="manual">Manual</option>
                                <option value="externo">Externo</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Capacidad/Hora</label>
                            <input type="number" step="0.01" name="capacidad_hora" value="0">
                        </div>
                        <div class="form-group">
                            <label>Costo/Hora</label>
                            <input type="number" step="0.01" name="costo_hora" value="0">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('modalCentro')">Cancelar</button>
                    <button type="submit" class="btn btn-success">Crear Centro</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Demanda -->
    <div id="modalDemanda" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-chart-line"></i> Registrar Demanda</h3>
                <button class="close-modal" onclick="closeModal('modalDemanda')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="registrar_demanda">
                <div class="modal-body">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Tipo Demanda</label>
                            <select name="tipo_demanda" required>
                                <option value="pedido_cliente">Pedido Cliente</option>
                                <option value="demanda_interna">Demanda Interna</option>
                                <option value="proyeccion">Proyeccion</option>
                                <option value="reserva">Reserva Stock</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Producto ID</label>
                            <input type="number" name="producto_id" required>
                        </div>
                        <div class="form-group">
                            <label>Cantidad</label>
                            <input type="number" step="0.01" name="cantidad" required>
                        </div>
                        <div class="form-group">
                            <label>Fecha Requerida</label>
                            <input type="date" name="fecha_requerida" required>
                        </div>
                        <div class="form-group">
                            <label>Prioridad (1-10)</label>
                            <input type="number" name="prioridad" min="1" max="10" value="5">
                        </div>
                        <div class="form-group">
                            <label>Referencia</label>
                            <input type="text" name="referencia" placeholder="Numero pedido, etc.">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('modalDemanda')">Cancelar</button>
                    <button type="submit" class="btn btn-success">Registrar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Plan Maestro -->
    <div id="modalPlan" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-calendar-alt"></i> Plan Maestro Produccion</h3>
                <button class="close-modal" onclick="closeModal('modalPlan')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="crear_plan_maestro">
                <div class="modal-body">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Producto ID</label>
                            <input type="number" name="producto_id" required>
                        </div>
                        <div class="form-group">
                            <label>Periodo (Fecha)</label>
                            <input type="date" name="periodo" required>
                        </div>
                        <div class="form-group">
                            <label>Cantidad Planificada</label>
                            <input type="number" step="0.01" name="cantidad_planificada" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('modalPlan')">Cancelar</button>
                    <button type="submit" class="btn btn-success">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Parametros -->
    <div id="modalParametros" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-cog"></i> Configurar Parametros MRP</h3>
                <button class="close-modal" onclick="closeModal('modalParametros')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="configurar_parametros">
                <div class="modal-body">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Producto ID</label>
                            <input type="number" name="producto_id" required>
                        </div>
                        <div class="form-group">
                            <label>Lead Time Compra (dias)</label>
                            <input type="number" name="lead_time_compra" value="7">
                        </div>
                        <div class="form-group">
                            <label>Lead Time Produccion (dias)</label>
                            <input type="number" name="lead_time_produccion" value="3">
                        </div>
                        <div class="form-group">
                            <label>Stock Seguridad</label>
                            <input type="number" step="0.01" name="stock_seguridad" value="0">
                        </div>
                        <div class="form-group">
                            <label>Lote Minimo</label>
                            <input type="number" step="0.01" name="lote_minimo" value="1">
                        </div>
                        <div class="form-group">
                            <label>Politica Lotificacion</label>
                            <select name="politica">
                                <option value="lote_por_lote">Lote por Lote</option>
                                <option value="lote_fijo">Lote Fijo</option>
                                <option value="lote_multiplo">Lote Multiplo</option>
                                <option value="eoq">EOQ (Economico)</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('modalParametros')">Cancelar</button>
                    <button type="submit" class="btn btn-success">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(id) {
            document.getElementById(id).classList.add('active');
        }
        function closeModal(id) {
            document.getElementById(id).classList.remove('active');
        }
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) closeModal(this.id);
            });
        });
    </script>
</body>
</html>
