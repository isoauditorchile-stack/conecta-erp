<?php
/**
 * MODULO: Evaluacion de Proveedores
 * Descripcion: Sistema completo de evaluacion y calificacion de proveedores
 * Sin dependencias - Sin AJAX - Sin sidebar - UTF-8
 * Conexion directa a SQL
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
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die('Error de conexion: ' . $e->getMessage());
}

session_start();

$mensaje = '';
$tipo_mensaje = '';
$vista = isset($_GET['vista']) ? $_GET['vista'] : 'dashboard';
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;

// =====================================================
// CREAR TABLAS
// =====================================================

$sql_tablas = "
CREATE TABLE IF NOT EXISTS eval_proveedores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo_proveedor VARCHAR(50) NOT NULL,
    nombre_proveedor VARCHAR(255) NOT NULL,
    rut_proveedor VARCHAR(20),
    categoria_proveedor VARCHAR(100),
    pais VARCHAR(100) DEFAULT 'Chile',
    region VARCHAR(100),
    tipo_proveedor ENUM('insumo','producto','servicio','importador','distribuidor') DEFAULT 'producto',
    estado_proveedor ENUM('activo','inactivo','bloqueado') DEFAULT 'activo',
    direccion TEXT,
    telefono VARCHAR(50),
    email VARCHAR(255),
    contacto_principal VARCHAR(255),
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_codigo (codigo_proveedor),
    INDEX idx_estado (estado_proveedor)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS eval_criterios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) NOT NULL,
    nombre VARCHAR(255) NOT NULL,
    descripcion TEXT,
    peso DECIMAL(5,2) DEFAULT 10.00,
    activo TINYINT(1) DEFAULT 1,
    orden INT DEFAULT 0,
    UNIQUE KEY idx_codigo (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS eval_evaluaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    proveedor_id INT NOT NULL,
    periodo VARCHAR(20),
    tipo_evaluacion ENUM('mensual','trimestral','anual','especial') DEFAULT 'mensual',
    fecha_evaluacion DATE NOT NULL,
    evaluador_id INT,
    nota_calidad DECIMAL(5,2) DEFAULT 0,
    nota_precio DECIMAL(5,2) DEFAULT 0,
    nota_plazo DECIMAL(5,2) DEFAULT 0,
    nota_cantidad DECIMAL(5,2) DEFAULT 0,
    nota_servicio DECIMAL(5,2) DEFAULT 0,
    nota_documentacion DECIMAL(5,2) DEFAULT 0,
    nota_facturacion DECIMAL(5,2) DEFAULT 0,
    promedio_final DECIMAL(5,2) DEFAULT 0,
    clasificacion ENUM('A','B','C','D') DEFAULT 'C',
    observaciones TEXT,
    estado ENUM('borrador','confirmada','anulada') DEFAULT 'borrador',
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (proveedor_id) REFERENCES eval_proveedores(id) ON DELETE CASCADE,
    INDEX idx_proveedor (proveedor_id),
    INDEX idx_fecha (fecha_evaluacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS eval_kpis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    proveedor_id INT NOT NULL,
    periodo VARCHAR(20),
    entregas_totales INT DEFAULT 0,
    entregas_a_tiempo INT DEFAULT 0,
    entregas_fuera_plazo INT DEFAULT 0,
    porcentaje_puntualidad DECIMAL(5,2) DEFAULT 0,
    recepciones_totales INT DEFAULT 0,
    rechazos_calidad INT DEFAULT 0,
    porcentaje_rechazo DECIMAL(5,2) DEFAULT 0,
    devoluciones INT DEFAULT 0,
    porcentaje_devoluciones DECIMAL(5,2) DEFAULT 0,
    facturas_totales INT DEFAULT 0,
    facturas_observadas INT DEFAULT 0,
    facturas_rechazadas INT DEFAULT 0,
    cumplimiento_documental DECIMAL(5,2) DEFAULT 0,
    diferencia_precio_contrato DECIMAL(15,2) DEFAULT 0,
    fecha_calculo DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (proveedor_id) REFERENCES eval_proveedores(id) ON DELETE CASCADE,
    INDEX idx_proveedor (proveedor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS eval_no_conformidades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    proveedor_id INT NOT NULL,
    numero_nc VARCHAR(50) NOT NULL,
    fecha_nc DATE NOT NULL,
    tipo_nc ENUM('calidad','plazo','cantidad','documentacion','precio','otro') DEFAULT 'calidad',
    descripcion TEXT NOT NULL,
    impacto ENUM('bajo','medio','alto','critico') DEFAULT 'medio',
    estado ENUM('abierta','en_proceso','cerrada','anulada') DEFAULT 'abierta',
    accion_correctiva TEXT,
    fecha_cierre DATE,
    responsable VARCHAR(255),
    FOREIGN KEY (proveedor_id) REFERENCES eval_proveedores(id) ON DELETE CASCADE,
    INDEX idx_proveedor (proveedor_id),
    INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS eval_certificaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    proveedor_id INT NOT NULL,
    tipo_documento ENUM('certificado_calidad','contrato','ficha_proveedor','documento_legal','certificacion','otro') DEFAULT 'certificado_calidad',
    nombre_documento VARCHAR(255) NOT NULL,
    numero_documento VARCHAR(100),
    fecha_emision DATE,
    fecha_vencimiento DATE,
    estado ENUM('vigente','por_vencer','vencido') DEFAULT 'vigente',
    observaciones TEXT,
    archivo VARCHAR(255),
    FOREIGN KEY (proveedor_id) REFERENCES eval_proveedores(id) ON DELETE CASCADE,
    INDEX idx_proveedor (proveedor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS eval_alertas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    proveedor_id INT,
    tipo_alerta ENUM('no_certificado','baja_calificacion','nc_abiertas','diferencias','retrasos','bloqueado') NOT NULL,
    mensaje TEXT NOT NULL,
    severidad ENUM('baja','media','alta','critica') DEFAULT 'media',
    estado ENUM('activa','leida','resuelta') DEFAULT 'activa',
    fecha_alerta DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS eval_ponderaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_configuracion VARCHAR(100) DEFAULT 'default',
    peso_calidad DECIMAL(5,2) DEFAULT 20.00,
    peso_precio DECIMAL(5,2) DEFAULT 15.00,
    peso_plazo DECIMAL(5,2) DEFAULT 20.00,
    peso_cantidad DECIMAL(5,2) DEFAULT 10.00,
    peso_servicio DECIMAL(5,2) DEFAULT 15.00,
    peso_documentacion DECIMAL(5,2) DEFAULT 10.00,
    peso_facturacion DECIMAL(5,2) DEFAULT 10.00,
    activa TINYINT(1) DEFAULT 1,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS eval_historial (
    id INT AUTO_INCREMENT PRIMARY KEY,
    proveedor_id INT,
    accion VARCHAR(100) NOT NULL,
    descripcion TEXT,
    usuario_id INT,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    datos_anteriores TEXT,
    datos_nuevos TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

try {
    $pdo->exec($sql_tablas);

    // Insertar ponderacion por defecto si no existe
    $stmt = $pdo->query("SELECT COUNT(*) as c FROM eval_ponderaciones");
    if ($stmt->fetch()['c'] == 0) {
        $pdo->exec("INSERT INTO eval_ponderaciones (nombre_configuracion) VALUES ('default')");
    }
} catch (PDOException $e) {
    // Tablas ya existen
}

// =====================================================
// PROCESAMIENTO
// =====================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    switch ($action) {
        case 'crear_proveedor':
            $codigo = trim($_POST['codigo_proveedor']);
            $nombre = trim($_POST['nombre_proveedor']);
            $rut = trim($_POST['rut_proveedor']);
            $categoria = trim($_POST['categoria_proveedor']);
            $pais = trim($_POST['pais']);
            $region = trim($_POST['region']);
            $tipo = $_POST['tipo_proveedor'];
            $direccion = trim($_POST['direccion']);
            $telefono = trim($_POST['telefono']);
            $email = trim($_POST['email']);
            $contacto = trim($_POST['contacto_principal']);

            $stmt = $pdo->prepare("INSERT INTO eval_proveedores (codigo_proveedor, nombre_proveedor, rut_proveedor, categoria_proveedor, pais, region, tipo_proveedor, direccion, telefono, email, contacto_principal) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$codigo, $nombre, $rut, $categoria, $pais, $region, $tipo, $direccion, $telefono, $email, $contacto]);

            $mensaje = 'Proveedor creado exitosamente';
            $tipo_mensaje = 'success';
            break;

        case 'crear_evaluacion':
            $proveedor_id = intval($_POST['proveedor_id']);
            $periodo = trim($_POST['periodo']);
            $tipo_eval = $_POST['tipo_evaluacion'];
            $fecha = $_POST['fecha_evaluacion'];
            $nota_calidad = floatval($_POST['nota_calidad']);
            $nota_precio = floatval($_POST['nota_precio']);
            $nota_plazo = floatval($_POST['nota_plazo']);
            $nota_cantidad = floatval($_POST['nota_cantidad']);
            $nota_servicio = floatval($_POST['nota_servicio']);
            $nota_documentacion = floatval($_POST['nota_documentacion']);
            $nota_facturacion = floatval($_POST['nota_facturacion']);
            $observaciones = trim($_POST['observaciones']);

            // Obtener ponderaciones
            $stmt = $pdo->query("SELECT * FROM eval_ponderaciones WHERE activa = 1 LIMIT 1");
            $pesos = $stmt->fetch();

            // Calcular promedio ponderado
            $suma_pesos = $pesos['peso_calidad'] + $pesos['peso_precio'] + $pesos['peso_plazo'] + $pesos['peso_cantidad'] + $pesos['peso_servicio'] + $pesos['peso_documentacion'] + $pesos['peso_facturacion'];

            $promedio = (
                ($nota_calidad * $pesos['peso_calidad']) +
                ($nota_precio * $pesos['peso_precio']) +
                ($nota_plazo * $pesos['peso_plazo']) +
                ($nota_cantidad * $pesos['peso_cantidad']) +
                ($nota_servicio * $pesos['peso_servicio']) +
                ($nota_documentacion * $pesos['peso_documentacion']) +
                ($nota_facturacion * $pesos['peso_facturacion'])
            ) / $suma_pesos;

            // Clasificacion
            if ($promedio >= 90) $clasificacion = 'A';
            elseif ($promedio >= 75) $clasificacion = 'B';
            elseif ($promedio >= 60) $clasificacion = 'C';
            else $clasificacion = 'D';

            $stmt = $pdo->prepare("INSERT INTO eval_evaluaciones (proveedor_id, periodo, tipo_evaluacion, fecha_evaluacion, evaluador_id, nota_calidad, nota_precio, nota_plazo, nota_cantidad, nota_servicio, nota_documentacion, nota_facturacion, promedio_final, clasificacion, observaciones, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'confirmada')");
            $stmt->execute([$proveedor_id, $periodo, $tipo_eval, $fecha, $user_id, $nota_calidad, $nota_precio, $nota_plazo, $nota_cantidad, $nota_servicio, $nota_documentacion, $nota_facturacion, $promedio, $clasificacion, $observaciones]);

            // Alerta si clasificacion baja
            if ($clasificacion == 'D') {
                $stmt = $pdo->prepare("INSERT INTO eval_alertas (proveedor_id, tipo_alerta, mensaje, severidad) VALUES (?, 'baja_calificacion', ?, 'alta')");
                $stmt->execute([$proveedor_id, "Proveedor con clasificacion D - Promedio: $promedio"]);
            }

            $mensaje = "Evaluacion registrada. Promedio: $promedio - Clasificacion: $clasificacion";
            $tipo_mensaje = 'success';
            break;

        case 'registrar_nc':
            $proveedor_id = intval($_POST['proveedor_id']);
            $numero_nc = trim($_POST['numero_nc']);
            $fecha_nc = $_POST['fecha_nc'];
            $tipo_nc = $_POST['tipo_nc'];
            $descripcion = trim($_POST['descripcion']);
            $impacto = $_POST['impacto'];

            $stmt = $pdo->prepare("INSERT INTO eval_no_conformidades (proveedor_id, numero_nc, fecha_nc, tipo_nc, descripcion, impacto) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$proveedor_id, $numero_nc, $fecha_nc, $tipo_nc, $descripcion, $impacto]);

            // Alerta
            $stmt = $pdo->prepare("INSERT INTO eval_alertas (proveedor_id, tipo_alerta, mensaje, severidad) VALUES (?, 'nc_abiertas', ?, ?)");
            $stmt->execute([$proveedor_id, "Nueva NC registrada: $numero_nc - $tipo_nc", $impacto == 'critico' ? 'critica' : 'media']);

            $mensaje = 'No conformidad registrada';
            $tipo_mensaje = 'success';
            break;

        case 'cerrar_nc':
            $nc_id = intval($_POST['nc_id']);
            $accion = trim($_POST['accion_correctiva']);

            $stmt = $pdo->prepare("UPDATE eval_no_conformidades SET estado = 'cerrada', accion_correctiva = ?, fecha_cierre = CURDATE() WHERE id = ?");
            $stmt->execute([$accion, $nc_id]);

            $mensaje = 'NC cerrada exitosamente';
            $tipo_mensaje = 'success';
            break;

        case 'registrar_certificacion':
            $proveedor_id = intval($_POST['proveedor_id']);
            $tipo_doc = $_POST['tipo_documento'];
            $nombre_doc = trim($_POST['nombre_documento']);
            $numero_doc = trim($_POST['numero_documento']);
            $fecha_emision = $_POST['fecha_emision'];
            $fecha_vencimiento = $_POST['fecha_vencimiento'];
            $observaciones = trim($_POST['observaciones']);

            $stmt = $pdo->prepare("INSERT INTO eval_certificaciones (proveedor_id, tipo_documento, nombre_documento, numero_documento, fecha_emision, fecha_vencimiento, observaciones) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$proveedor_id, $tipo_doc, $nombre_doc, $numero_doc, $fecha_emision, $fecha_vencimiento, $observaciones]);

            $mensaje = 'Certificacion registrada';
            $tipo_mensaje = 'success';
            break;

        case 'registrar_kpi':
            $proveedor_id = intval($_POST['proveedor_id']);
            $periodo = trim($_POST['periodo']);
            $entregas_totales = intval($_POST['entregas_totales']);
            $entregas_tiempo = intval($_POST['entregas_a_tiempo']);
            $recepciones = intval($_POST['recepciones_totales']);
            $rechazos = intval($_POST['rechazos_calidad']);
            $devoluciones = intval($_POST['devoluciones']);
            $facturas = intval($_POST['facturas_totales']);
            $fac_observadas = intval($_POST['facturas_observadas']);
            $fac_rechazadas = intval($_POST['facturas_rechazadas']);

            $pct_puntualidad = $entregas_totales > 0 ? ($entregas_tiempo / $entregas_totales) * 100 : 0;
            $pct_rechazo = $recepciones > 0 ? ($rechazos / $recepciones) * 100 : 0;
            $pct_devolucion = $recepciones > 0 ? ($devoluciones / $recepciones) * 100 : 0;
            $entregas_fuera = $entregas_totales - $entregas_tiempo;

            $stmt = $pdo->prepare("INSERT INTO eval_kpis (proveedor_id, periodo, entregas_totales, entregas_a_tiempo, entregas_fuera_plazo, porcentaje_puntualidad, recepciones_totales, rechazos_calidad, porcentaje_rechazo, devoluciones, porcentaje_devoluciones, facturas_totales, facturas_observadas, facturas_rechazadas) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$proveedor_id, $periodo, $entregas_totales, $entregas_tiempo, $entregas_fuera, $pct_puntualidad, $recepciones, $rechazos, $pct_rechazo, $devoluciones, $pct_devolucion, $facturas, $fac_observadas, $fac_rechazadas]);

            $mensaje = 'KPIs registrados exitosamente';
            $tipo_mensaje = 'success';
            break;

        case 'guardar_ponderaciones':
            $peso_calidad = floatval($_POST['peso_calidad']);
            $peso_precio = floatval($_POST['peso_precio']);
            $peso_plazo = floatval($_POST['peso_plazo']);
            $peso_cantidad = floatval($_POST['peso_cantidad']);
            $peso_servicio = floatval($_POST['peso_servicio']);
            $peso_documentacion = floatval($_POST['peso_documentacion']);
            $peso_facturacion = floatval($_POST['peso_facturacion']);

            $stmt = $pdo->prepare("UPDATE eval_ponderaciones SET peso_calidad = ?, peso_precio = ?, peso_plazo = ?, peso_cantidad = ?, peso_servicio = ?, peso_documentacion = ?, peso_facturacion = ? WHERE activa = 1");
            $stmt->execute([$peso_calidad, $peso_precio, $peso_plazo, $peso_cantidad, $peso_servicio, $peso_documentacion, $peso_facturacion]);

            $mensaje = 'Ponderaciones actualizadas';
            $tipo_mensaje = 'success';
            break;

        case 'cambiar_estado_proveedor':
            $proveedor_id = intval($_POST['proveedor_id']);
            $nuevo_estado = $_POST['nuevo_estado'];

            $stmt = $pdo->prepare("UPDATE eval_proveedores SET estado_proveedor = ? WHERE id = ?");
            $stmt->execute([$nuevo_estado, $proveedor_id]);

            if ($nuevo_estado == 'bloqueado') {
                $stmt = $pdo->prepare("INSERT INTO eval_alertas (proveedor_id, tipo_alerta, mensaje, severidad) VALUES (?, 'bloqueado', 'Proveedor bloqueado para compras', 'critica')");
                $stmt->execute([$proveedor_id]);
            }

            $mensaje = 'Estado actualizado';
            $tipo_mensaje = 'success';
            break;

        case 'eliminar':
            $tabla = $_POST['tabla'];
            $id = intval($_POST['id']);
            $tablas_ok = ['eval_proveedores', 'eval_evaluaciones', 'eval_no_conformidades', 'eval_certificaciones', 'eval_kpis', 'eval_alertas'];
            if (in_array($tabla, $tablas_ok)) {
                $stmt = $pdo->prepare("DELETE FROM $tabla WHERE id = ?");
                $stmt->execute([$id]);
                $mensaje = 'Registro eliminado';
                $tipo_mensaje = 'success';
            }
            break;
    }

    if ($mensaje) {
        $_SESSION['mensaje'] = $mensaje;
        $_SESSION['tipo_mensaje'] = $tipo_mensaje;
        header("Location: evaluacion_proveedores.php?vista=$vista");
        exit;
    }
}

if (isset($_SESSION['mensaje'])) {
    $mensaje = $_SESSION['mensaje'];
    $tipo_mensaje = $_SESSION['tipo_mensaje'];
    unset($_SESSION['mensaje'], $_SESSION['tipo_mensaje']);
}

// =====================================================
// CONSULTAS
// =====================================================

// Estadisticas
$stmt = $pdo->query("SELECT COUNT(*) as t FROM eval_proveedores WHERE estado_proveedor = 'activo'");
$total_proveedores = $stmt->fetch()['t'];

$stmt = $pdo->query("SELECT COUNT(*) as t FROM eval_evaluaciones WHERE YEAR(fecha_evaluacion) = YEAR(CURDATE())");
$evaluaciones_anio = $stmt->fetch()['t'];

$stmt = $pdo->query("SELECT COUNT(*) as t FROM eval_no_conformidades WHERE estado = 'abierta'");
$nc_abiertas = $stmt->fetch()['t'];

$stmt = $pdo->query("SELECT COUNT(*) as t FROM eval_alertas WHERE estado = 'activa'");
$alertas_activas = $stmt->fetch()['t'];

$stmt = $pdo->query("SELECT AVG(promedio_final) as p FROM eval_evaluaciones WHERE fecha_evaluacion >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)");
$promedio_general = round($stmt->fetch()['p'] ?? 0, 2);

// Datos segun vista
switch ($vista) {
    case 'proveedores':
        $stmt = $pdo->query("SELECT * FROM eval_proveedores ORDER BY nombre_proveedor");
        $proveedores = $stmt->fetchAll();
        break;

    case 'evaluaciones':
        $stmt = $pdo->query("SELECT e.*, p.nombre_proveedor, p.codigo_proveedor FROM eval_evaluaciones e LEFT JOIN eval_proveedores p ON e.proveedor_id = p.id ORDER BY e.fecha_evaluacion DESC");
        $evaluaciones = $stmt->fetchAll();
        break;

    case 'no_conformidades':
        $stmt = $pdo->query("SELECT nc.*, p.nombre_proveedor FROM eval_no_conformidades nc LEFT JOIN eval_proveedores p ON nc.proveedor_id = p.id ORDER BY nc.fecha_nc DESC");
        $no_conformidades = $stmt->fetchAll();
        break;

    case 'kpis':
        $stmt = $pdo->query("SELECT k.*, p.nombre_proveedor FROM eval_kpis k LEFT JOIN eval_proveedores p ON k.proveedor_id = p.id ORDER BY k.fecha_calculo DESC");
        $kpis = $stmt->fetchAll();
        break;

    case 'certificaciones':
        $stmt = $pdo->query("SELECT c.*, p.nombre_proveedor FROM eval_certificaciones c LEFT JOIN eval_proveedores p ON c.proveedor_id = p.id ORDER BY c.fecha_vencimiento");
        $certificaciones = $stmt->fetchAll();
        break;

    case 'alertas':
        $stmt = $pdo->query("SELECT a.*, p.nombre_proveedor FROM eval_alertas a LEFT JOIN eval_proveedores p ON a.proveedor_id = p.id ORDER BY a.fecha_alerta DESC");
        $alertas = $stmt->fetchAll();
        break;

    case 'ponderaciones':
        $stmt = $pdo->query("SELECT * FROM eval_ponderaciones WHERE activa = 1 LIMIT 1");
        $ponderaciones = $stmt->fetch();
        break;

    case 'ranking':
        $stmt = $pdo->query("SELECT p.id, p.codigo_proveedor, p.nombre_proveedor, p.estado_proveedor,
            (SELECT promedio_final FROM eval_evaluaciones WHERE proveedor_id = p.id ORDER BY fecha_evaluacion DESC LIMIT 1) as ultima_nota,
            (SELECT clasificacion FROM eval_evaluaciones WHERE proveedor_id = p.id ORDER BY fecha_evaluacion DESC LIMIT 1) as clasificacion,
            (SELECT COUNT(*) FROM eval_no_conformidades WHERE proveedor_id = p.id AND estado = 'abierta') as nc_abiertas
            FROM eval_proveedores p WHERE p.estado_proveedor = 'activo' ORDER BY ultima_nota DESC");
        $ranking = $stmt->fetchAll();
        break;
}

// Lista proveedores para selects
$stmt = $pdo->query("SELECT id, codigo_proveedor, nombre_proveedor FROM eval_proveedores WHERE estado_proveedor = 'activo' ORDER BY nombre_proveedor");
$lista_proveedores = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evaluacion de Proveedores - CONECTA ERP</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8fafc; color: #1e293b; min-height: 100vh; }
        .header { background: linear-gradient(135deg, #059669 0%, #10b981 100%); padding: 20px 40px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .header h1 { font-size: 24px; display: flex; align-items: center; gap: 10px; }
        .header a { color: white; text-decoration: none; padding: 10px 20px; background: rgba(255,255,255,0.2); border-radius: 8px; }
        .nav-tabs { background: #ffffff; padding: 0 40px; display: flex; gap: 5px; overflow-x: auto; border-bottom: 2px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .nav-tab { padding: 15px 20px; color: #64748b; text-decoration: none; border-bottom: 3px solid transparent; white-space: nowrap; font-size: 14px; font-weight: 500; }
        .nav-tab:hover, .nav-tab.active { color: #059669; border-bottom-color: #059669; background: rgba(5,150,105,0.05); }
        .container { max-width: 1600px; margin: 0 auto; padding: 30px 40px; }
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: #ffffff; padding: 24px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1), 0 1px 2px rgba(0,0,0,0.06); border-left: 4px solid #10b981; }
        .stat-card.azul { border-left-color: #3b82f6; }
        .stat-card.naranja { border-left-color: #f59e0b; }
        .stat-card.rojo { border-left-color: #ef4444; }
        .stat-card.morado { border-left-color: #8b5cf6; }
        .stat-card h3 { font-size: 32px; margin: 8px 0; color: #1e293b; }
        .stat-card p { color: #64748b; font-size: 13px; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px; }
        .card { background: #ffffff; border-radius: 12px; padding: 25px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1), 0 1px 2px rgba(0,0,0,0.06); border: 1px solid #e2e8f0; }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #e2e8f0; }
        .card-title { font-size: 18px; font-weight: 600; display: flex; align-items: center; gap: 10px; color: #1e293b; }
        .btn { padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 500; transition: all 0.3s; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary { background: #10b981; color: white; }
        .btn-primary:hover { background: #059669; }
        .btn-success { background: #10b981; color: white; }
        .btn-danger { background: #ef4444; color: white; }
        .btn-warning { background: #f59e0b; color: white; }
        .btn-secondary { background: #475569; color: white; }
        .btn-sm { padding: 6px 12px; font-size: 13px; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 8px; color: #475569; font-size: 14px; font-weight: 500; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 12px; background: #ffffff; border: 1px solid #d1d5db; border-radius: 8px; color: #1e293b; font-size: 14px; }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: #10b981; box-shadow: 0 0 0 3px rgba(16,185,129,0.1); }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        th { background: #f8fafc; color: #64748b; font-weight: 600; font-size: 12px; text-transform: uppercase; }
        tr:hover { background: #f0fdf4; }
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .badge-a { background: #dcfce7; color: #166534; }
        .badge-b { background: #dbeafe; color: #1e40af; }
        .badge-c { background: #fef3c7; color: #92400e; }
        .badge-d { background: #fee2e2; color: #991b1b; }
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .badge-info { background: #dbeafe; color: #1e40af; }
        .alert { padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .alert-success { background: #dcfce7; border: 1px solid #86efac; color: #166534; }
        .alert-error { background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; overflow-y: auto; }
        .modal.active { display: flex; align-items: flex-start; justify-content: center; padding: 40px 20px; }
        .modal-content { background: #ffffff; border-radius: 12px; width: 100%; max-width: 700px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); }
        .modal-header { padding: 20px 25px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
        .modal-body { padding: 25px; max-height: 70vh; overflow-y: auto; }
        .modal-footer { padding: 20px 25px; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 10px; }
        .close-modal { background: none; border: none; color: #64748b; font-size: 24px; cursor: pointer; }
        .empty-state { text-align: center; padding: 60px 20px; color: #64748b; }
        .empty-state i { font-size: 48px; margin-bottom: 15px; opacity: 0.5; }
        .nota-input { width: 80px; text-align: center; }
        .clasificacion { font-size: 24px; font-weight: bold; padding: 5px 15px; border-radius: 8px; }
        .clasificacion-A { background: #10b981; color: white; }
        .clasificacion-B { background: #3b82f6; color: white; }
        .clasificacion-C { background: #f59e0b; color: white; }
        .clasificacion-D { background: #ef4444; color: white; }
        @media (max-width: 768px) {
            .container { padding: 20px; }
            .header { padding: 15px 20px; }
            .nav-tabs { padding: 0 20px; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><i class="fas fa-user-check"></i> Evaluacion de Proveedores</h1>
        <a href="/modules/mm/index.php"><i class="fas fa-arrow-left"></i> Volver</a>
    </div>

    <nav class="nav-tabs">
        <a href="?vista=dashboard" class="nav-tab <?php echo $vista === 'dashboard' ? 'active' : ''; ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="?vista=proveedores" class="nav-tab <?php echo $vista === 'proveedores' ? 'active' : ''; ?>"><i class="fas fa-truck"></i> Proveedores</a>
        <a href="?vista=evaluaciones" class="nav-tab <?php echo $vista === 'evaluaciones' ? 'active' : ''; ?>"><i class="fas fa-star"></i> Evaluaciones</a>
        <a href="?vista=ranking" class="nav-tab <?php echo $vista === 'ranking' ? 'active' : ''; ?>"><i class="fas fa-trophy"></i> Ranking</a>
        <a href="?vista=kpis" class="nav-tab <?php echo $vista === 'kpis' ? 'active' : ''; ?>"><i class="fas fa-chart-bar"></i> KPIs</a>
        <a href="?vista=no_conformidades" class="nav-tab <?php echo $vista === 'no_conformidades' ? 'active' : ''; ?>"><i class="fas fa-exclamation-triangle"></i> No Conformidades</a>
        <a href="?vista=certificaciones" class="nav-tab <?php echo $vista === 'certificaciones' ? 'active' : ''; ?>"><i class="fas fa-certificate"></i> Certificaciones</a>
        <a href="?vista=ponderaciones" class="nav-tab <?php echo $vista === 'ponderaciones' ? 'active' : ''; ?>"><i class="fas fa-sliders-h"></i> Ponderaciones</a>
        <a href="?vista=alertas" class="nav-tab <?php echo $vista === 'alertas' ? 'active' : ''; ?>"><i class="fas fa-bell"></i> Alertas</a>
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
                    <p><i class="fas fa-truck"></i> Proveedores Activos</p>
                    <h3><?php echo $total_proveedores; ?></h3>
                </div>
                <div class="stat-card azul">
                    <p><i class="fas fa-star"></i> Evaluaciones <?php echo date('Y'); ?></p>
                    <h3><?php echo $evaluaciones_anio; ?></h3>
                </div>
                <div class="stat-card morado">
                    <p><i class="fas fa-percentage"></i> Promedio General</p>
                    <h3><?php echo $promedio_general; ?>%</h3>
                </div>
                <div class="stat-card naranja">
                    <p><i class="fas fa-exclamation-triangle"></i> NC Abiertas</p>
                    <h3><?php echo $nc_abiertas; ?></h3>
                </div>
                <div class="stat-card rojo">
                    <p><i class="fas fa-bell"></i> Alertas Activas</p>
                    <h3><?php echo $alertas_activas; ?></h3>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-bolt"></i> Acciones Rapidas</h3>
                </div>
                <div class="form-grid">
                    <a href="?vista=proveedores" class="btn btn-primary" style="justify-content: center; padding: 20px;">
                        <i class="fas fa-plus"></i> Nuevo Proveedor
                    </a>
                    <a href="?vista=evaluaciones" class="btn btn-success" style="justify-content: center; padding: 20px;">
                        <i class="fas fa-star"></i> Nueva Evaluacion
                    </a>
                    <a href="?vista=no_conformidades" class="btn btn-warning" style="justify-content: center; padding: 20px;">
                        <i class="fas fa-exclamation-triangle"></i> Registrar NC
                    </a>
                    <a href="?vista=ranking" class="btn btn-secondary" style="justify-content: center; padding: 20px;">
                        <i class="fas fa-trophy"></i> Ver Ranking
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <!-- PROVEEDORES -->
        <?php if ($vista === 'proveedores'): ?>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-truck"></i> Proveedores</h3>
                    <button class="btn btn-primary" onclick="openModal('modalProveedor')"><i class="fas fa-plus"></i> Nuevo Proveedor</button>
                </div>
                <?php if (empty($proveedores)): ?>
                    <div class="empty-state"><i class="fas fa-truck"></i><h3>No hay proveedores</h3></div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Codigo</th>
                                <th>Nombre</th>
                                <th>RUT</th>
                                <th>Tipo</th>
                                <th>Categoria</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($proveedores as $p): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($p['codigo_proveedor']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($p['nombre_proveedor']); ?></td>
                                    <td><?php echo htmlspecialchars($p['rut_proveedor']); ?></td>
                                    <td><?php echo strtoupper($p['tipo_proveedor']); ?></td>
                                    <td><?php echo htmlspecialchars($p['categoria_proveedor']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $p['estado_proveedor'] === 'activo' ? 'success' : ($p['estado_proveedor'] === 'bloqueado' ? 'danger' : 'warning'); ?>">
                                            <?php echo strtoupper($p['estado_proveedor']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Eliminar?');">
                                            <input type="hidden" name="action" value="eliminar">
                                            <input type="hidden" name="tabla" value="eval_proveedores">
                                            <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
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

        <!-- EVALUACIONES -->
        <?php if ($vista === 'evaluaciones'): ?>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-star"></i> Evaluaciones de Proveedores</h3>
                    <button class="btn btn-primary" onclick="openModal('modalEvaluacion')"><i class="fas fa-plus"></i> Nueva Evaluacion</button>
                </div>
                <?php if (empty($evaluaciones)): ?>
                    <div class="empty-state"><i class="fas fa-star"></i><h3>No hay evaluaciones</h3></div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Proveedor</th>
                                <th>Periodo</th>
                                <th>Calidad</th>
                                <th>Precio</th>
                                <th>Plazo</th>
                                <th>Servicio</th>
                                <th>Promedio</th>
                                <th>Clasif.</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($evaluaciones as $e): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($e['fecha_evaluacion'])); ?></td>
                                    <td><?php echo htmlspecialchars($e['nombre_proveedor']); ?></td>
                                    <td><?php echo htmlspecialchars($e['periodo']); ?></td>
                                    <td><?php echo $e['nota_calidad']; ?></td>
                                    <td><?php echo $e['nota_precio']; ?></td>
                                    <td><?php echo $e['nota_plazo']; ?></td>
                                    <td><?php echo $e['nota_servicio']; ?></td>
                                    <td><strong><?php echo number_format($e['promedio_final'], 1); ?></strong></td>
                                    <td><span class="badge badge-<?php echo strtolower($e['clasificacion']); ?>"><?php echo $e['clasificacion']; ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- RANKING -->
        <?php if ($vista === 'ranking'): ?>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-trophy"></i> Ranking de Proveedores</h3>
                </div>
                <?php if (empty($ranking)): ?>
                    <div class="empty-state"><i class="fas fa-trophy"></i><h3>Sin datos de ranking</h3></div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Codigo</th>
                                <th>Proveedor</th>
                                <th>Ultima Nota</th>
                                <th>Clasificacion</th>
                                <th>NC Abiertas</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $pos = 1; foreach ($ranking as $r): ?>
                                <tr>
                                    <td><strong><?php echo $pos++; ?></strong></td>
                                    <td><?php echo htmlspecialchars($r['codigo_proveedor']); ?></td>
                                    <td><?php echo htmlspecialchars($r['nombre_proveedor']); ?></td>
                                    <td><strong><?php echo $r['ultima_nota'] ? number_format($r['ultima_nota'], 1) : '-'; ?></strong></td>
                                    <td>
                                        <?php if ($r['clasificacion']): ?>
                                            <span class="clasificacion clasificacion-<?php echo $r['clasificacion']; ?>"><?php echo $r['clasificacion']; ?></span>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($r['nc_abiertas'] > 0): ?>
                                            <span class="badge badge-danger"><?php echo $r['nc_abiertas']; ?></span>
                                        <?php else: ?>
                                            <span class="badge badge-success">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge badge-<?php echo $r['estado_proveedor'] === 'activo' ? 'success' : 'danger'; ?>"><?php echo strtoupper($r['estado_proveedor']); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- KPIs -->
        <?php if ($vista === 'kpis'): ?>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-chart-bar"></i> Indicadores KPI</h3>
                    <button class="btn btn-primary" onclick="openModal('modalKPI')"><i class="fas fa-plus"></i> Registrar KPIs</button>
                </div>
                <?php if (empty($kpis)): ?>
                    <div class="empty-state"><i class="fas fa-chart-bar"></i><h3>No hay KPIs registrados</h3></div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Proveedor</th>
                                <th>Periodo</th>
                                <th>Puntualidad</th>
                                <th>Rechazo</th>
                                <th>Devoluciones</th>
                                <th>Fac. Observadas</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($kpis as $k): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($k['nombre_proveedor']); ?></td>
                                    <td><?php echo htmlspecialchars($k['periodo']); ?></td>
                                    <td><span class="badge badge-<?php echo $k['porcentaje_puntualidad'] >= 90 ? 'success' : ($k['porcentaje_puntualidad'] >= 70 ? 'warning' : 'danger'); ?>"><?php echo number_format($k['porcentaje_puntualidad'], 1); ?>%</span></td>
                                    <td><span class="badge badge-<?php echo $k['porcentaje_rechazo'] <= 2 ? 'success' : ($k['porcentaje_rechazo'] <= 5 ? 'warning' : 'danger'); ?>"><?php echo number_format($k['porcentaje_rechazo'], 1); ?>%</span></td>
                                    <td><?php echo number_format($k['porcentaje_devoluciones'], 1); ?>%</td>
                                    <td><?php echo $k['facturas_observadas']; ?>/<?php echo $k['facturas_totales']; ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($k['fecha_calculo'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- NO CONFORMIDADES -->
        <?php if ($vista === 'no_conformidades'): ?>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-exclamation-triangle"></i> No Conformidades</h3>
                    <button class="btn btn-primary" onclick="openModal('modalNC')"><i class="fas fa-plus"></i> Registrar NC</button>
                </div>
                <?php if (empty($no_conformidades)): ?>
                    <div class="empty-state"><i class="fas fa-exclamation-triangle"></i><h3>No hay NC registradas</h3></div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Numero</th>
                                <th>Proveedor</th>
                                <th>Fecha</th>
                                <th>Tipo</th>
                                <th>Impacto</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($no_conformidades as $nc): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($nc['numero_nc']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($nc['nombre_proveedor']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($nc['fecha_nc'])); ?></td>
                                    <td><?php echo strtoupper($nc['tipo_nc']); ?></td>
                                    <td><span class="badge badge-<?php echo $nc['impacto'] === 'critico' ? 'danger' : ($nc['impacto'] === 'alto' ? 'warning' : 'info'); ?>"><?php echo strtoupper($nc['impacto']); ?></span></td>
                                    <td><span class="badge badge-<?php echo $nc['estado'] === 'cerrada' ? 'success' : ($nc['estado'] === 'abierta' ? 'danger' : 'warning'); ?>"><?php echo strtoupper($nc['estado']); ?></span></td>
                                    <td>
                                        <?php if ($nc['estado'] === 'abierta'): ?>
                                            <button class="btn btn-sm btn-success" onclick="openModal('modalCerrarNC'); document.getElementById('nc_id_cerrar').value=<?php echo $nc['id']; ?>"><i class="fas fa-check"></i></button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- CERTIFICACIONES -->
        <?php if ($vista === 'certificaciones'): ?>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-certificate"></i> Certificaciones y Documentos</h3>
                    <button class="btn btn-primary" onclick="openModal('modalCertificacion')"><i class="fas fa-plus"></i> Nueva Certificacion</button>
                </div>
                <?php if (empty($certificaciones)): ?>
                    <div class="empty-state"><i class="fas fa-certificate"></i><h3>No hay certificaciones</h3></div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Proveedor</th>
                                <th>Tipo</th>
                                <th>Documento</th>
                                <th>Numero</th>
                                <th>Vencimiento</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($certificaciones as $c): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($c['nombre_proveedor']); ?></td>
                                    <td><?php echo strtoupper(str_replace('_', ' ', $c['tipo_documento'])); ?></td>
                                    <td><?php echo htmlspecialchars($c['nombre_documento']); ?></td>
                                    <td><?php echo htmlspecialchars($c['numero_documento']); ?></td>
                                    <td><?php echo $c['fecha_vencimiento'] ? date('d/m/Y', strtotime($c['fecha_vencimiento'])) : '-'; ?></td>
                                    <td><span class="badge badge-<?php echo $c['estado'] === 'vigente' ? 'success' : ($c['estado'] === 'por_vencer' ? 'warning' : 'danger'); ?>"><?php echo strtoupper(str_replace('_', ' ', $c['estado'])); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- PONDERACIONES -->
        <?php if ($vista === 'ponderaciones'): ?>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-sliders-h"></i> Configuracion de Ponderaciones</h3>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="guardar_ponderaciones">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Peso Calidad (%)</label>
                            <input type="number" step="0.01" name="peso_calidad" value="<?php echo $ponderaciones['peso_calidad'] ?? 20; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Peso Precio (%)</label>
                            <input type="number" step="0.01" name="peso_precio" value="<?php echo $ponderaciones['peso_precio'] ?? 15; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Peso Plazo (%)</label>
                            <input type="number" step="0.01" name="peso_plazo" value="<?php echo $ponderaciones['peso_plazo'] ?? 20; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Peso Cantidad (%)</label>
                            <input type="number" step="0.01" name="peso_cantidad" value="<?php echo $ponderaciones['peso_cantidad'] ?? 10; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Peso Servicio (%)</label>
                            <input type="number" step="0.01" name="peso_servicio" value="<?php echo $ponderaciones['peso_servicio'] ?? 15; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Peso Documentacion (%)</label>
                            <input type="number" step="0.01" name="peso_documentacion" value="<?php echo $ponderaciones['peso_documentacion'] ?? 10; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Peso Facturacion (%)</label>
                            <input type="number" step="0.01" name="peso_facturacion" value="<?php echo $ponderaciones['peso_facturacion'] ?? 10; ?>" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success" style="margin-top: 20px;"><i class="fas fa-save"></i> Guardar Ponderaciones</button>
                </form>
            </div>
        <?php endif; ?>

        <!-- ALERTAS -->
        <?php if ($vista === 'alertas'): ?>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-bell"></i> Alertas</h3>
                </div>
                <?php if (empty($alertas)): ?>
                    <div class="empty-state"><i class="fas fa-bell"></i><h3>No hay alertas</h3></div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Tipo</th>
                                <th>Proveedor</th>
                                <th>Mensaje</th>
                                <th>Severidad</th>
                                <th>Fecha</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($alertas as $a): ?>
                                <tr>
                                    <td><?php echo strtoupper(str_replace('_', ' ', $a['tipo_alerta'])); ?></td>
                                    <td><?php echo htmlspecialchars($a['nombre_proveedor']); ?></td>
                                    <td><?php echo htmlspecialchars($a['mensaje']); ?></td>
                                    <td><span class="badge badge-<?php echo $a['severidad'] === 'critica' ? 'danger' : ($a['severidad'] === 'alta' ? 'warning' : 'info'); ?>"><?php echo strtoupper($a['severidad']); ?></span></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($a['fecha_alerta'])); ?></td>
                                    <td><span class="badge badge-<?php echo $a['estado'] === 'activa' ? 'danger' : 'success'; ?>"><?php echo strtoupper($a['estado']); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- MODALES -->
    <div id="modalProveedor" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-truck"></i> Nuevo Proveedor</h3>
                <button class="close-modal" onclick="closeModal('modalProveedor')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="crear_proveedor">
                <div class="modal-body">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Codigo</label>
                            <input type="text" name="codigo_proveedor" required>
                        </div>
                        <div class="form-group">
                            <label>RUT</label>
                            <input type="text" name="rut_proveedor" placeholder="76.123.456-7">
                        </div>
                        <div class="form-group" style="grid-column: 1/-1;">
                            <label>Nombre / Razon Social</label>
                            <input type="text" name="nombre_proveedor" required>
                        </div>
                        <div class="form-group">
                            <label>Tipo</label>
                            <select name="tipo_proveedor">
                                <option value="producto">Producto</option>
                                <option value="insumo">Insumo</option>
                                <option value="servicio">Servicio</option>
                                <option value="importador">Importador</option>
                                <option value="distribuidor">Distribuidor</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Categoria</label>
                            <input type="text" name="categoria_proveedor">
                        </div>
                        <div class="form-group">
                            <label>Pais</label>
                            <input type="text" name="pais" value="Chile">
                        </div>
                        <div class="form-group">
                            <label>Region</label>
                            <input type="text" name="region">
                        </div>
                        <div class="form-group">
                            <label>Telefono</label>
                            <input type="text" name="telefono">
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email">
                        </div>
                        <div class="form-group">
                            <label>Contacto Principal</label>
                            <input type="text" name="contacto_principal">
                        </div>
                        <div class="form-group" style="grid-column: 1/-1;">
                            <label>Direccion</label>
                            <textarea name="direccion"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('modalProveedor')">Cancelar</button>
                    <button type="submit" class="btn btn-success">Crear Proveedor</button>
                </div>
            </form>
        </div>
    </div>

    <div id="modalEvaluacion" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-star"></i> Nueva Evaluacion</h3>
                <button class="close-modal" onclick="closeModal('modalEvaluacion')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="crear_evaluacion">
                <div class="modal-body">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Proveedor</label>
                            <select name="proveedor_id" required>
                                <option value="">Seleccionar...</option>
                                <?php foreach ($lista_proveedores as $p): ?>
                                    <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['nombre_proveedor']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Periodo</label>
                            <input type="text" name="periodo" placeholder="2025-11" required>
                        </div>
                        <div class="form-group">
                            <label>Tipo Evaluacion</label>
                            <select name="tipo_evaluacion">
                                <option value="mensual">Mensual</option>
                                <option value="trimestral">Trimestral</option>
                                <option value="anual">Anual</option>
                                <option value="especial">Especial</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Fecha Evaluacion</label>
                            <input type="date" name="fecha_evaluacion" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    <h4 style="margin: 20px 0 15px; color: #10b981;">Notas (0-100)</h4>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Calidad</label>
                            <input type="number" name="nota_calidad" min="0" max="100" value="80" required>
                        </div>
                        <div class="form-group">
                            <label>Precio</label>
                            <input type="number" name="nota_precio" min="0" max="100" value="80" required>
                        </div>
                        <div class="form-group">
                            <label>Plazo</label>
                            <input type="number" name="nota_plazo" min="0" max="100" value="80" required>
                        </div>
                        <div class="form-group">
                            <label>Cantidad</label>
                            <input type="number" name="nota_cantidad" min="0" max="100" value="80" required>
                        </div>
                        <div class="form-group">
                            <label>Servicio</label>
                            <input type="number" name="nota_servicio" min="0" max="100" value="80" required>
                        </div>
                        <div class="form-group">
                            <label>Documentacion</label>
                            <input type="number" name="nota_documentacion" min="0" max="100" value="80" required>
                        </div>
                        <div class="form-group">
                            <label>Facturacion</label>
                            <input type="number" name="nota_facturacion" min="0" max="100" value="80" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Observaciones</label>
                        <textarea name="observaciones"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('modalEvaluacion')">Cancelar</button>
                    <button type="submit" class="btn btn-success">Guardar Evaluacion</button>
                </div>
            </form>
        </div>
    </div>

    <div id="modalNC" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-exclamation-triangle"></i> Registrar No Conformidad</h3>
                <button class="close-modal" onclick="closeModal('modalNC')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="registrar_nc">
                <div class="modal-body">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Proveedor</label>
                            <select name="proveedor_id" required>
                                <option value="">Seleccionar...</option>
                                <?php foreach ($lista_proveedores as $p): ?>
                                    <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['nombre_proveedor']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Numero NC</label>
                            <input type="text" name="numero_nc" required>
                        </div>
                        <div class="form-group">
                            <label>Fecha</label>
                            <input type="date" name="fecha_nc" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Tipo</label>
                            <select name="tipo_nc">
                                <option value="calidad">Calidad</option>
                                <option value="plazo">Plazo</option>
                                <option value="cantidad">Cantidad</option>
                                <option value="documentacion">Documentacion</option>
                                <option value="precio">Precio</option>
                                <option value="otro">Otro</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Impacto</label>
                            <select name="impacto">
                                <option value="bajo">Bajo</option>
                                <option value="medio">Medio</option>
                                <option value="alto">Alto</option>
                                <option value="critico">Critico</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Descripcion</label>
                        <textarea name="descripcion" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('modalNC')">Cancelar</button>
                    <button type="submit" class="btn btn-success">Registrar NC</button>
                </div>
            </form>
        </div>
    </div>

    <div id="modalCerrarNC" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-check"></i> Cerrar NC</h3>
                <button class="close-modal" onclick="closeModal('modalCerrarNC')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="cerrar_nc">
                <input type="hidden" name="nc_id" id="nc_id_cerrar">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Accion Correctiva</label>
                        <textarea name="accion_correctiva" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('modalCerrarNC')">Cancelar</button>
                    <button type="submit" class="btn btn-success">Cerrar NC</button>
                </div>
            </form>
        </div>
    </div>

    <div id="modalKPI" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-chart-bar"></i> Registrar KPIs</h3>
                <button class="close-modal" onclick="closeModal('modalKPI')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="registrar_kpi">
                <div class="modal-body">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Proveedor</label>
                            <select name="proveedor_id" required>
                                <option value="">Seleccionar...</option>
                                <?php foreach ($lista_proveedores as $p): ?>
                                    <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['nombre_proveedor']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Periodo</label>
                            <input type="text" name="periodo" placeholder="2025-11" required>
                        </div>
                        <div class="form-group">
                            <label>Entregas Totales</label>
                            <input type="number" name="entregas_totales" value="0">
                        </div>
                        <div class="form-group">
                            <label>Entregas a Tiempo</label>
                            <input type="number" name="entregas_a_tiempo" value="0">
                        </div>
                        <div class="form-group">
                            <label>Recepciones Totales</label>
                            <input type="number" name="recepciones_totales" value="0">
                        </div>
                        <div class="form-group">
                            <label>Rechazos Calidad</label>
                            <input type="number" name="rechazos_calidad" value="0">
                        </div>
                        <div class="form-group">
                            <label>Devoluciones</label>
                            <input type="number" name="devoluciones" value="0">
                        </div>
                        <div class="form-group">
                            <label>Facturas Totales</label>
                            <input type="number" name="facturas_totales" value="0">
                        </div>
                        <div class="form-group">
                            <label>Facturas Observadas</label>
                            <input type="number" name="facturas_observadas" value="0">
                        </div>
                        <div class="form-group">
                            <label>Facturas Rechazadas</label>
                            <input type="number" name="facturas_rechazadas" value="0">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('modalKPI')">Cancelar</button>
                    <button type="submit" class="btn btn-success">Guardar KPIs</button>
                </div>
            </form>
        </div>
    </div>

    <div id="modalCertificacion" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-certificate"></i> Nueva Certificacion</h3>
                <button class="close-modal" onclick="closeModal('modalCertificacion')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="registrar_certificacion">
                <div class="modal-body">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Proveedor</label>
                            <select name="proveedor_id" required>
                                <option value="">Seleccionar...</option>
                                <?php foreach ($lista_proveedores as $p): ?>
                                    <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['nombre_proveedor']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Tipo Documento</label>
                            <select name="tipo_documento">
                                <option value="certificado_calidad">Certificado Calidad</option>
                                <option value="contrato">Contrato</option>
                                <option value="ficha_proveedor">Ficha Proveedor</option>
                                <option value="documento_legal">Documento Legal</option>
                                <option value="certificacion">Certificacion</option>
                                <option value="otro">Otro</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Nombre Documento</label>
                            <input type="text" name="nombre_documento" required>
                        </div>
                        <div class="form-group">
                            <label>Numero</label>
                            <input type="text" name="numero_documento">
                        </div>
                        <div class="form-group">
                            <label>Fecha Emision</label>
                            <input type="date" name="fecha_emision">
                        </div>
                        <div class="form-group">
                            <label>Fecha Vencimiento</label>
                            <input type="date" name="fecha_vencimiento">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Observaciones</label>
                        <textarea name="observaciones"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('modalCertificacion')">Cancelar</button>
                    <button type="submit" class="btn btn-success">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(id) { document.getElementById(id).classList.add('active'); }
        function closeModal(id) { document.getElementById(id).classList.remove('active'); }
        document.querySelectorAll('.modal').forEach(m => m.addEventListener('click', function(e) { if (e.target === this) closeModal(this.id); }));
    </script>
</body>
</html>
