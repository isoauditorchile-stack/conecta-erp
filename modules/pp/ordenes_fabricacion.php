<?php
/**
 * ORDENES DE FABRICACION - MODULO COMPLETO ERP
 * Sistema integral de gestion de ordenes de produccion
 * Sin dependencias externas - Todo en un solo archivo
 */

// Iniciar sesion
session_start();

// Configuracion de base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'conectae_conectaerpbd');
define('DB_USER', 'conectae_conectaerpuser');
define('DB_PASS', 'pt125824caraud');
define('DB_CHARSET', 'utf8mb4');

// Conexion a base de datos
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
} catch (PDOException $e) {
    die("Error de conexion: " . $e->getMessage());
}

// Variables de sesion
$company_id = isset($_SESSION['company_id']) ? $_SESSION['company_id'] : 1;
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;

// Variables de estado
$mensaje = '';
$tipo_mensaje = '';
$modo = isset($_GET['modo']) ? $_GET['modo'] : 'listar';
$orden_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Funciones auxiliares
function ejecutarConsulta($pdo, $sql, $params = []) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error SQL: " . $e->getMessage());
        return null;
    }
}

function ejecutarConsultaMultiple($pdo, $sql, $params = []) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error SQL: " . $e->getMessage());
        return [];
    }
}

function insertarRegistro($pdo, $sql, $params = []) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        error_log("Error INSERT: " . $e->getMessage());
        return false;
    }
}

function actualizarRegistro($pdo, $sql, $params = []) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    } catch (PDOException $e) {
        error_log("Error UPDATE: " . $e->getMessage());
        return false;
    }
}

function registrarAuditoria($pdo, $user_id, $accion, $tabla, $registro_id, $detalles) {
    $sql = "INSERT INTO activity_logs (user_id, action, description, module, ip_address, user_agent, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())";
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $user_id,
            $accion,
            "Tabla: $tabla, ID: $registro_id - $detalles",
            'PP-OF',
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
    } catch (PDOException $e) {
        error_log("Error auditoria: " . $e->getMessage());
    }
}

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    switch($accion) {
        case 'crear_orden':
            // Crear nueva orden de fabricacion
            $sql = "INSERT INTO pp_production_orders (
                company_id, production_order_number, order_type, product_id,
                quantity_to_produce, unit_of_measure, start_date_planned, end_date_planned,
                priority, status, responsible_id, created_by, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'planned', ?, ?, NOW())";

            $numero_orden = 'OF-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

            $id = insertarRegistro($pdo, $sql, [
                $company_id,
                $numero_orden,
                $_POST['tipo_orden'] ?? 'standard',
                $_POST['product_id'] ?? null,
                $_POST['cantidad_planificada'] ?? 0,
                $_POST['unidad_medida'] ?? 'UN',
                $_POST['fecha_inicio_planificada'] ?? date('Y-m-d'),
                $_POST['fecha_termino_planificada'] ?? date('Y-m-d', strtotime('+7 days')),
                $_POST['prioridad'] ?? 'normal',
                $user_id,
                $user_id
            ]);

            if ($id) {
                registrarAuditoria($pdo, $user_id, 'CREATE', 'pp_production_orders', $id, 'Orden creada: ' . $numero_orden);
                $mensaje = "Orden de fabricacion creada exitosamente: $numero_orden";
                $tipo_mensaje = 'success';
                $orden_id = $id;
                $modo = 'editar';
            } else {
                $mensaje = "Error al crear la orden de fabricacion";
                $tipo_mensaje = 'error';
            }
            break;

        case 'actualizar_orden':
            // Actualizar orden existente
            $id = $_POST['orden_id'] ?? 0;

            $sql = "UPDATE pp_production_orders SET
                order_type = ?, product_id = ?, quantity_to_produce = ?,
                unit_of_measure = ?, start_date_planned = ?, end_date_planned = ?,
                priority = ?, status = ?, responsible_id = ?, notes = ?
                WHERE id = ? AND company_id = ?";

            $resultado = actualizarRegistro($pdo, $sql, [
                $_POST['tipo_orden'] ?? 'standard',
                $_POST['product_id'] ?? null,
                $_POST['cantidad_planificada'] ?? 0,
                $_POST['unidad_medida'] ?? 'UN',
                $_POST['fecha_inicio_planificada'] ?? date('Y-m-d'),
                $_POST['fecha_termino_planificada'] ?? date('Y-m-d'),
                $_POST['prioridad'] ?? 'normal',
                $_POST['estado_orden'] ?? 'planned',
                $_POST['responsable_produccion'] ?? null,
                $_POST['observaciones'] ?? '',
                $id,
                $company_id
            ]);

            if ($resultado !== false) {
                registrarAuditoria($pdo, $user_id, 'UPDATE', 'pp_production_orders', $id, 'Orden actualizada');
                $mensaje = "Orden de fabricacion actualizada exitosamente";
                $tipo_mensaje = 'success';
            } else {
                $mensaje = "Error al actualizar la orden";
                $tipo_mensaje = 'error';
            }
            break;

        case 'agregar_material':
            // Agregar material a la orden
            $orden_id = $_POST['orden_id'] ?? 0;

            $sql = "INSERT INTO pp_production_order_materials (
                production_order_id, line_number, product_id, quantity_required,
                unit_of_measure, warehouse_id, issue_status
            ) VALUES (?, ?, ?, ?, ?, ?, 'not_issued')";

            $id = insertarRegistro($pdo, $sql, [
                $orden_id,
                $_POST['line_number'] ?? 1,
                $_POST['material_id'] ?? null,
                $_POST['cantidad_requerida'] ?? 0,
                $_POST['unidad_medida_material'] ?? 'UN',
                $_POST['warehouse_id'] ?? null
            ]);

            if ($id) {
                registrarAuditoria($pdo, $user_id, 'INSERT', 'pp_production_order_materials', $id, 'Material agregado a OF');
                $mensaje = "Material agregado exitosamente";
                $tipo_mensaje = 'success';
            }
            break;

        case 'agregar_operacion':
            // Agregar operacion a la orden
            $orden_id = $_POST['orden_id'] ?? 0;

            $sql = "INSERT INTO pp_production_order_operations (
                production_order_id, operation_number, work_center_id, operation_name,
                setup_time_planned_minutes, run_time_planned_minutes,
                quantity_to_process, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')";

            $id = insertarRegistro($pdo, $sql, [
                $orden_id,
                $_POST['operation_number'] ?? 1,
                $_POST['work_center_id'] ?? null,
                $_POST['operation_name'] ?? '',
                $_POST['tiempo_preparacion'] ?? 0,
                $_POST['tiempo_ejecucion'] ?? 0,
                $_POST['quantity_to_process'] ?? 0
            ]);

            if ($id) {
                registrarAuditoria($pdo, $user_id, 'INSERT', 'pp_production_order_operations', $id, 'Operacion agregada a OF');
                $mensaje = "Operacion agregada exitosamente";
                $tipo_mensaje = 'success';
            }
            break;

        case 'registrar_produccion':
            // Registrar produccion parcial
            $orden_id = $_POST['orden_id'] ?? 0;

            $sql = "UPDATE pp_production_orders SET
                quantity_produced = quantity_produced + ?,
                quantity_scrapped = quantity_scrapped + ?,
                completion_percentage = ?
                WHERE id = ? AND company_id = ?";

            $cantidad_producida = $_POST['cantidad_producida'] ?? 0;
            $cantidad_scrap = $_POST['cantidad_scrap'] ?? 0;
            $porcentaje = $_POST['completion_percentage'] ?? 0;

            actualizarRegistro($pdo, $sql, [$cantidad_producida, $cantidad_scrap, $porcentaje, $orden_id, $company_id]);

            registrarAuditoria($pdo, $user_id, 'UPDATE', 'pp_production_orders', $orden_id, "Produccion registrada: $cantidad_producida unidades");
            $mensaje = "Produccion registrada exitosamente";
            $tipo_mensaje = 'success';
            break;

        case 'registrar_calidad':
            // Registrar inspeccion de calidad
            $sql = "INSERT INTO pp_quality_inspections (
                company_id, inspection_number, inspection_date, inspection_type,
                production_order_id, product_id, quantity_inspected,
                quantity_accepted, quantity_rejected, quantity_rework,
                inspector_id, inspection_result, notes, created_at
            ) VALUES (?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

            $numero_inspeccion = 'INS-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

            $id = insertarRegistro($pdo, $sql, [
                $company_id,
                $numero_inspeccion,
                $_POST['inspection_type'] ?? 'in_process',
                $_POST['orden_id'] ?? 0,
                $_POST['product_id'] ?? null,
                $_POST['quantity_inspected'] ?? 0,
                $_POST['quantity_accepted'] ?? 0,
                $_POST['quantity_rejected'] ?? 0,
                $_POST['quantity_rework'] ?? 0,
                $user_id,
                $_POST['inspection_result'] ?? 'pending',
                $_POST['notes_calidad'] ?? ''
            ]);

            if ($id) {
                registrarAuditoria($pdo, $user_id, 'INSERT', 'pp_quality_inspections', $id, 'Inspeccion registrada');
                $mensaje = "Inspeccion de calidad registrada exitosamente";
                $tipo_mensaje = 'success';
            }
            break;

        case 'cerrar_orden':
            // Cerrar orden de fabricacion
            $orden_id = $_POST['orden_id'] ?? 0;

            $sql = "UPDATE pp_production_orders SET
                status = 'completed',
                end_date_actual = NOW(),
                completion_percentage = 100
                WHERE id = ? AND company_id = ?";

            actualizarRegistro($pdo, $sql, [$orden_id, $company_id]);

            registrarAuditoria($pdo, $user_id, 'CLOSE', 'pp_production_orders', $orden_id, 'Orden cerrada');
            $mensaje = "Orden cerrada exitosamente";
            $tipo_mensaje = 'success';
            break;
    }
}

// Obtener datos segun modo
$orden_actual = null;
$materiales_orden = [];
$operaciones_orden = [];
$inspecciones_orden = [];
$costos_orden = [];

if ($modo == 'editar' || $modo == 'ver') {
    // Obtener datos de la orden
    $sql = "SELECT po.*, p.product_name, p.product_code
            FROM pp_production_orders po
            LEFT JOIN mm_products p ON po.product_id = p.id
            WHERE po.id = ? AND po.company_id = ?";
    $orden_actual = ejecutarConsulta($pdo, $sql, [$orden_id, $company_id]);

    if ($orden_actual) {
        // Obtener materiales
        $sql = "SELECT pom.*, p.product_name, p.product_code
                FROM pp_production_order_materials pom
                LEFT JOIN mm_products p ON pom.product_id = p.id
                WHERE pom.production_order_id = ?
                ORDER BY pom.line_number";
        $materiales_orden = ejecutarConsultaMultiple($pdo, $sql, [$orden_id]);

        // Obtener operaciones
        $sql = "SELECT poo.*, wc.work_center_name
                FROM pp_production_order_operations poo
                LEFT JOIN pp_work_centers wc ON poo.work_center_id = wc.id
                WHERE poo.production_order_id = ?
                ORDER BY poo.operation_number";
        $operaciones_orden = ejecutarConsultaMultiple($pdo, $sql, [$orden_id]);

        // Obtener inspecciones
        $sql = "SELECT * FROM pp_quality_inspections
                WHERE production_order_id = ?
                ORDER BY inspection_date DESC";
        $inspecciones_orden = ejecutarConsultaMultiple($pdo, $sql, [$orden_id]);

        // Obtener costos
        $sql = "SELECT * FROM pp_production_costs
                WHERE production_order_id = ?
                ORDER BY cost_type";
        $costos_orden = ejecutarConsultaMultiple($pdo, $sql, [$orden_id]);
    }
}

// Obtener listado de ordenes
$filtro_estado = isset($_GET['filtro_estado']) ? $_GET['filtro_estado'] : '';
$filtro_tipo = isset($_GET['filtro_tipo']) ? $_GET['filtro_tipo'] : '';
$filtro_fecha_desde = isset($_GET['filtro_fecha_desde']) ? $_GET['filtro_fecha_desde'] : '';
$filtro_fecha_hasta = isset($_GET['filtro_fecha_hasta']) ? $_GET['filtro_fecha_hasta'] : '';

$sql = "SELECT po.*, p.product_name, p.product_code
        FROM pp_production_orders po
        LEFT JOIN mm_products p ON po.product_id = p.id
        WHERE po.company_id = ?";
$params = [$company_id];

if ($filtro_estado) {
    $sql .= " AND po.status = ?";
    $params[] = $filtro_estado;
}
if ($filtro_tipo) {
    $sql .= " AND po.order_type = ?";
    $params[] = $filtro_tipo;
}
if ($filtro_fecha_desde) {
    $sql .= " AND po.created_at >= ?";
    $params[] = $filtro_fecha_desde;
}
if ($filtro_fecha_hasta) {
    $sql .= " AND po.created_at <= ?";
    $params[] = $filtro_fecha_hasta . ' 23:59:59';
}

$sql .= " ORDER BY po.created_at DESC LIMIT 100";
$ordenes = ejecutarConsultaMultiple($pdo, $sql, $params);

// Obtener listas para selects
$productos = ejecutarConsultaMultiple($pdo, "SELECT id, product_code, product_name FROM mm_products WHERE company_id = ? AND is_active = 1 ORDER BY product_name LIMIT 500", [$company_id]);
$centros_trabajo = ejecutarConsultaMultiple($pdo, "SELECT id, work_center_code, work_center_name FROM pp_work_centers WHERE company_id = ? AND is_active = 1 ORDER BY work_center_name LIMIT 100", [$company_id]);
$bodegas = ejecutarConsultaMultiple($pdo, "SELECT id, warehouse_code, warehouse_name FROM mm_warehouses WHERE company_id = ? AND is_active = 1 ORDER BY warehouse_name LIMIT 100", [$company_id]);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ordenes de Fabricacion - CONECTA ERP</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1600px;
            margin: 0 auto;
        }

        .header {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 25px 30px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 28px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
        }

        .btn-primary {
            background: rgba(255, 255, 255, 0.9);
            color: #667eea;
        }

        .btn-primary:hover {
            background: #fff;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.2);
            color: #fff;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .btn-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: #fff;
        }

        .btn-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: #fff;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }

        .section {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .section-title {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid rgba(255, 255, 255, 0.2);
        }

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: rgba(34, 197, 94, 0.2);
            border: 2px solid rgba(34, 197, 94, 0.5);
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.2);
            border: 2px solid rgba(239, 68, 68, 0.5);
        }

        .filters {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group label {
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 10px 15px;
            border-radius: 8px;
            border: 2px solid rgba(255, 255, 255, 0.2);
            background: rgba(255, 255, 255, 0.15);
            color: #fff;
            font-size: 14px;
            outline: none;
            transition: all 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: rgba(255, 255, 255, 0.5);
            background: rgba(255, 255, 255, 0.2);
        }

        .form-group select option {
            background: #667eea;
            color: #fff;
        }

        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }

        .table-container {
            overflow-x: auto;
            margin-top: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: rgba(255, 255, 255, 0.15);
            padding: 12px;
            text-align: left;
            font-weight: 700;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid rgba(255, 255, 255, 0.2);
        }

        td {
            padding: 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 14px;
        }

        tr:hover {
            background: rgba(255, 255, 255, 0.05);
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-planned { background: rgba(59, 130, 246, 0.3); border: 2px solid rgba(59, 130, 246, 0.5); }
        .badge-released { background: rgba(168, 85, 247, 0.3); border: 2px solid rgba(168, 85, 247, 0.5); }
        .badge-in-progress { background: rgba(234, 179, 8, 0.3); border: 2px solid rgba(234, 179, 8, 0.5); }
        .badge-on-hold { background: rgba(249, 115, 22, 0.3); border: 2px solid rgba(249, 115, 22, 0.5); }
        .badge-completed { background: rgba(34, 197, 94, 0.3); border: 2px solid rgba(34, 197, 94, 0.5); }
        .badge-cancelled { background: rgba(239, 68, 68, 0.3); border: 2px solid rgba(239, 68, 68, 0.5); }

        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid rgba(255, 255, 255, 0.2);
            flex-wrap: wrap;
        }

        .tab {
            padding: 12px 24px;
            background: transparent;
            border: none;
            color: rgba(255, 255, 255, 0.7);
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }

        .tab:hover {
            color: #fff;
            background: rgba(255, 255, 255, 0.05);
        }

        .tab.active {
            color: #fff;
            border-bottom-color: #fff;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .grid-3 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }

        .card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 20px;
            border: 2px solid rgba(255, 255, 255, 0.1);
        }

        .card-title {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .info-label {
            font-weight: 600;
            opacity: 0.8;
        }

        .info-value {
            font-weight: 700;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            padding: 30px;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-title {
            font-size: 22px;
            font-weight: 700;
        }

        .close-modal {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: #fff;
            font-size: 24px;
            cursor: pointer;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
            }

            .grid-2, .grid-3 {
                grid-template-columns: 1fr;
            }

            .tabs {
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>
                <span>🏭</span>
                ORDENES DE FABRICACION
            </h1>
            <div class="actions">
                <?php if ($modo != 'crear' && $modo != 'editar'): ?>
                <a href="?modo=crear" class="btn btn-primary">+ Nueva Orden</a>
                <?php endif; ?>
                <?php if ($modo != 'listar'): ?>
                <a href="?modo=listar" class="btn btn-secondary">← Volver al Listado</a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Mensajes -->
        <?php if ($mensaje): ?>
        <div class="alert alert-<?php echo $tipo_mensaje; ?>">
            <span><?php echo $tipo_mensaje == 'success' ? '✓' : '✗'; ?></span>
            <span><?php echo htmlspecialchars($mensaje); ?></span>
        </div>
        <?php endif; ?>

        <!-- MODO: LISTAR -->
        <?php if ($modo == 'listar'): ?>
        <div class="section">
            <div class="section-title">Filtros de Busqueda</div>
            <form method="GET" action="">
                <input type="hidden" name="modo" value="listar">
                <div class="filters">
                    <div class="form-group">
                        <label>Estado</label>
                        <select name="filtro_estado">
                            <option value="">Todos</option>
                            <option value="planned" <?php echo $filtro_estado == 'planned' ? 'selected' : ''; ?>>Planificada</option>
                            <option value="released" <?php echo $filtro_estado == 'released' ? 'selected' : ''; ?>>Liberada</option>
                            <option value="in_progress" <?php echo $filtro_estado == 'in_progress' ? 'selected' : ''; ?>>En Proceso</option>
                            <option value="on_hold" <?php echo $filtro_estado == 'on_hold' ? 'selected' : ''; ?>>Pausada</option>
                            <option value="completed" <?php echo $filtro_estado == 'completed' ? 'selected' : ''; ?>>Terminada</option>
                            <option value="cancelled" <?php echo $filtro_estado == 'cancelled' ? 'selected' : ''; ?>>Anulada</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Tipo</label>
                        <select name="filtro_tipo">
                            <option value="">Todos</option>
                            <option value="standard" <?php echo $filtro_tipo == 'standard' ? 'selected' : ''; ?>>Por Stock</option>
                            <option value="rework" <?php echo $filtro_tipo == 'rework' ? 'selected' : ''; ?>>Reproceso</option>
                            <option value="batch" <?php echo $filtro_tipo == 'batch' ? 'selected' : ''; ?>>Lote</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Fecha Desde</label>
                        <input type="date" name="filtro_fecha_desde" value="<?php echo htmlspecialchars($filtro_fecha_desde); ?>">
                    </div>
                    <div class="form-group">
                        <label>Fecha Hasta</label>
                        <input type="date" name="filtro_fecha_hasta" value="<?php echo htmlspecialchars($filtro_fecha_hasta); ?>">
                    </div>
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary">Buscar</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="section">
            <div class="section-title">Listado de Ordenes de Fabricacion (<?php echo count($ordenes); ?>)</div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Numero Orden</th>
                            <th>Tipo</th>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Estado</th>
                            <th>Prioridad</th>
                            <th>F. Inicio</th>
                            <th>F. Termino</th>
                            <th>Progreso</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($ordenes)): ?>
                        <tr>
                            <td colspan="10" style="text-align: center; padding: 40px;">
                                <span style="font-size: 48px; display: block; margin-bottom: 10px;">📦</span>
                                No hay ordenes de fabricacion registradas
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($ordenes as $orden): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($orden['production_order_number']); ?></strong></td>
                                <td><?php echo htmlspecialchars($orden['order_type']); ?></td>
                                <td>
                                    <?php if ($orden['product_code']): ?>
                                        <div><?php echo htmlspecialchars($orden['product_code']); ?></div>
                                        <div style="font-size: 12px; opacity: 0.8;"><?php echo htmlspecialchars($orden['product_name']); ?></div>
                                    <?php else: ?>
                                        <span style="opacity: 0.5;">Sin producto</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo number_format($orden['quantity_to_produce'], 2); ?> <?php echo htmlspecialchars($orden['unit_of_measure']); ?></td>
                                <td>
                                    <span class="status-badge badge-<?php echo $orden['status']; ?>">
                                        <?php echo strtoupper($orden['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $priority_colors = ['low' => '🟢', 'normal' => '🟡', 'high' => '🟠', 'urgent' => '🔴'];
                                    echo $priority_colors[$orden['priority']] ?? '';
                                    echo ' ' . ucfirst($orden['priority']);
                                    ?>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($orden['start_date_planned'])); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($orden['end_date_planned'])); ?></td>
                                <td>
                                    <div style="background: rgba(255,255,255,0.1); border-radius: 10px; height: 20px; overflow: hidden;">
                                        <div style="background: linear-gradient(90deg, #10b981 0%, #34d399 100%); height: 100%; width: <?php echo min($orden['completion_percentage'], 100); ?>%;"></div>
                                    </div>
                                    <div style="font-size: 11px; margin-top: 2px;"><?php echo number_format($orden['completion_percentage'], 1); ?>%</div>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 5px;">
                                        <a href="?modo=ver&id=<?php echo $orden['id']; ?>" class="btn btn-secondary btn-sm">Ver</a>
                                        <a href="?modo=editar&id=<?php echo $orden['id']; ?>" class="btn btn-primary btn-sm">Editar</a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- MODO: CREAR -->
        <?php if ($modo == 'crear'): ?>
        <div class="section">
            <div class="section-title">Nueva Orden de Fabricacion</div>
            <form method="POST" action="">
                <input type="hidden" name="accion" value="crear_orden">

                <div class="grid-3">
                    <div class="form-group">
                        <label>Tipo de Orden *</label>
                        <select name="tipo_orden" required>
                            <option value="standard">Por Stock</option>
                            <option value="rework">Reproceso</option>
                            <option value="batch">Lote</option>
                            <option value="continuous">Continuo</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Producto *</label>
                        <select name="product_id" required>
                            <option value="">Seleccione producto</option>
                            <?php foreach ($productos as $prod): ?>
                            <option value="<?php echo $prod['id']; ?>">
                                <?php echo htmlspecialchars($prod['product_code'] . ' - ' . $prod['product_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Cantidad Planificada *</label>
                        <input type="number" name="cantidad_planificada" step="0.01" required>
                    </div>

                    <div class="form-group">
                        <label>Unidad Medida *</label>
                        <input type="text" name="unidad_medida" value="UN" required>
                    </div>

                    <div class="form-group">
                        <label>Fecha Inicio Planificada *</label>
                        <input type="date" name="fecha_inicio_planificada" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Fecha Termino Planificada *</label>
                        <input type="date" name="fecha_termino_planificada" value="<?php echo date('Y-m-d', strtotime('+7 days')); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Prioridad</label>
                        <select name="prioridad">
                            <option value="low">Baja</option>
                            <option value="normal" selected>Normal</option>
                            <option value="high">Alta</option>
                            <option value="urgent">Urgente</option>
                        </select>
                    </div>
                </div>

                <div style="margin-top: 20px;">
                    <button type="submit" class="btn btn-success">Crear Orden de Fabricacion</button>
                    <a href="?modo=listar" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- MODO: EDITAR / VER -->
        <?php if (($modo == 'editar' || $modo == 'ver') && $orden_actual): ?>

        <!-- Informacion General -->
        <div class="section">
            <div class="section-title">
                Orden: <?php echo htmlspecialchars($orden_actual['production_order_number']); ?>
                <span class="status-badge badge-<?php echo $orden_actual['status']; ?>" style="float: right;">
                    <?php echo strtoupper($orden_actual['status']); ?>
                </span>
            </div>

            <div class="grid-2">
                <div class="card">
                    <div class="card-title">Datos Generales</div>
                    <div class="info-row">
                        <span class="info-label">Numero Orden:</span>
                        <span class="info-value"><?php echo htmlspecialchars($orden_actual['production_order_number']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Tipo:</span>
                        <span class="info-value"><?php echo htmlspecialchars($orden_actual['order_type']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Estado:</span>
                        <span class="info-value"><?php echo strtoupper($orden_actual['status']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Prioridad:</span>
                        <span class="info-value"><?php echo ucfirst($orden_actual['priority']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Fecha Creacion:</span>
                        <span class="info-value"><?php echo date('d/m/Y H:i', strtotime($orden_actual['created_at'])); ?></span>
                    </div>
                </div>

                <div class="card">
                    <div class="card-title">Producto</div>
                    <div class="info-row">
                        <span class="info-label">Codigo:</span>
                        <span class="info-value"><?php echo htmlspecialchars($orden_actual['product_code'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Descripcion:</span>
                        <span class="info-value"><?php echo htmlspecialchars($orden_actual['product_name'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Cantidad Planificada:</span>
                        <span class="info-value"><?php echo number_format($orden_actual['quantity_to_produce'], 2); ?> <?php echo $orden_actual['unit_of_measure']; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Cantidad Producida:</span>
                        <span class="info-value"><?php echo number_format($orden_actual['quantity_produced'], 2); ?> <?php echo $orden_actual['unit_of_measure']; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Progreso:</span>
                        <span class="info-value"><?php echo number_format($orden_actual['completion_percentage'], 1); ?>%</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs de Secciones -->
        <div class="section">
            <div class="tabs">
                <button class="tab active" onclick="cambiarTab(event, 'tab-datos')">📋 Datos</button>
                <button class="tab" onclick="cambiarTab(event, 'tab-materiales')">📦 Materiales (<?php echo count($materiales_orden); ?>)</button>
                <button class="tab" onclick="cambiarTab(event, 'tab-operaciones')">⚙ Operaciones (<?php echo count($operaciones_orden); ?>)</button>
                <button class="tab" onclick="cambiarTab(event, 'tab-produccion')">📊 Produccion</button>
                <button class="tab" onclick="cambiarTab(event, 'tab-calidad')">✨ Calidad (<?php echo count($inspecciones_orden); ?>)</button>
                <button class="tab" onclick="cambiarTab(event, 'tab-costos')">💰 Costos</button>
                <button class="tab" onclick="cambiarTab(event, 'tab-cierre')">✅ Cierre</button>
            </div>

            <!-- TAB: Datos -->
            <div id="tab-datos" class="tab-content active">
                <?php if ($modo == 'editar'): ?>
                <form method="POST" action="">
                    <input type="hidden" name="accion" value="actualizar_orden">
                    <input type="hidden" name="orden_id" value="<?php echo $orden_id; ?>">

                    <div class="grid-3">
                        <div class="form-group">
                            <label>Tipo de Orden</label>
                            <select name="tipo_orden">
                                <option value="standard" <?php echo $orden_actual['order_type'] == 'standard' ? 'selected' : ''; ?>>Por Stock</option>
                                <option value="rework" <?php echo $orden_actual['order_type'] == 'rework' ? 'selected' : ''; ?>>Reproceso</option>
                                <option value="batch" <?php echo $orden_actual['order_type'] == 'batch' ? 'selected' : ''; ?>>Lote</option>
                                <option value="continuous" <?php echo $orden_actual['order_type'] == 'continuous' ? 'selected' : ''; ?>>Continuo</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Estado</label>
                            <select name="estado_orden">
                                <option value="planned" <?php echo $orden_actual['status'] == 'planned' ? 'selected' : ''; ?>>Planificada</option>
                                <option value="released" <?php echo $orden_actual['status'] == 'released' ? 'selected' : ''; ?>>Liberada</option>
                                <option value="in_progress" <?php echo $orden_actual['status'] == 'in_progress' ? 'selected' : ''; ?>>En Proceso</option>
                                <option value="on_hold" <?php echo $orden_actual['status'] == 'on_hold' ? 'selected' : ''; ?>>Pausada</option>
                                <option value="completed" <?php echo $orden_actual['status'] == 'completed' ? 'selected' : ''; ?>>Terminada</option>
                                <option value="cancelled" <?php echo $orden_actual['status'] == 'cancelled' ? 'selected' : ''; ?>>Anulada</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Prioridad</label>
                            <select name="prioridad">
                                <option value="low" <?php echo $orden_actual['priority'] == 'low' ? 'selected' : ''; ?>>Baja</option>
                                <option value="normal" <?php echo $orden_actual['priority'] == 'normal' ? 'selected' : ''; ?>>Normal</option>
                                <option value="high" <?php echo $orden_actual['priority'] == 'high' ? 'selected' : ''; ?>>Alta</option>
                                <option value="urgent" <?php echo $orden_actual['priority'] == 'urgent' ? 'selected' : ''; ?>>Urgente</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Producto</label>
                            <select name="product_id">
                                <option value="">Seleccione producto</option>
                                <?php foreach ($productos as $prod): ?>
                                <option value="<?php echo $prod['id']; ?>" <?php echo $prod['id'] == $orden_actual['product_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($prod['product_code'] . ' - ' . $prod['product_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Cantidad Planificada</label>
                            <input type="number" name="cantidad_planificada" step="0.01" value="<?php echo $orden_actual['quantity_to_produce']; ?>">
                        </div>

                        <div class="form-group">
                            <label>Unidad Medida</label>
                            <input type="text" name="unidad_medida" value="<?php echo htmlspecialchars($orden_actual['unit_of_measure']); ?>">
                        </div>

                        <div class="form-group">
                            <label>Fecha Inicio Planificada</label>
                            <input type="date" name="fecha_inicio_planificada" value="<?php echo $orden_actual['start_date_planned']; ?>">
                        </div>

                        <div class="form-group">
                            <label>Fecha Termino Planificada</label>
                            <input type="date" name="fecha_termino_planificada" value="<?php echo $orden_actual['end_date_planned']; ?>">
                        </div>

                        <div class="form-group">
                            <label>Responsable</label>
                            <input type="number" name="responsable_produccion" value="<?php echo $orden_actual['responsible_id']; ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Observaciones</label>
                        <textarea name="observaciones"><?php echo htmlspecialchars($orden_actual['notes'] ?? ''); ?></textarea>
                    </div>

                    <div style="margin-top: 20px;">
                        <button type="submit" class="btn btn-success">Guardar Cambios</button>
                    </div>
                </form>
                <?php endif; ?>
            </div>

            <!-- TAB: Materiales -->
            <div id="tab-materiales" class="tab-content">
                <div style="margin-bottom: 20px;">
                    <button onclick="mostrarModal('modalMaterial')" class="btn btn-primary">+ Agregar Material</button>
                </div>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Linea</th>
                                <th>Codigo</th>
                                <th>Descripcion</th>
                                <th>Cant. Requerida</th>
                                <th>Cant. Emitida</th>
                                <th>Cant. Consumida</th>
                                <th>Estado</th>
                                <th>Bodega</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($materiales_orden)): ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 30px;">
                                    No hay materiales agregados a esta orden
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($materiales_orden as $material): ?>
                                <tr>
                                    <td><?php echo $material['line_number']; ?></td>
                                    <td><?php echo htmlspecialchars($material['product_code'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($material['product_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo number_format($material['quantity_required'], 2); ?> <?php echo $material['unit_of_measure']; ?></td>
                                    <td><?php echo number_format($material['quantity_issued'], 2); ?></td>
                                    <td><?php echo number_format($material['quantity_issued'], 2); ?></td>
                                    <td>
                                        <span class="status-badge badge-<?php echo $material['issue_status'] == 'fully_issued' ? 'completed' : 'planned'; ?>">
                                            <?php echo strtoupper($material['issue_status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $material['warehouse_id'] ?? 'N/A'; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB: Operaciones -->
            <div id="tab-operaciones" class="tab-content">
                <div style="margin-bottom: 20px;">
                    <button onclick="mostrarModal('modalOperacion')" class="btn btn-primary">+ Agregar Operacion</button>
                </div>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Num.</th>
                                <th>Operacion</th>
                                <th>Centro Trabajo</th>
                                <th>T. Preparacion</th>
                                <th>T. Ejecucion</th>
                                <th>Cantidad</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($operaciones_orden)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 30px;">
                                    No hay operaciones agregadas a esta orden
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($operaciones_orden as $operacion): ?>
                                <tr>
                                    <td><?php echo $operacion['operation_number']; ?></td>
                                    <td><?php echo htmlspecialchars($operacion['operation_name']); ?></td>
                                    <td><?php echo htmlspecialchars($operacion['work_center_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo $operacion['setup_time_planned_minutes']; ?> min</td>
                                    <td><?php echo $operacion['run_time_planned_minutes']; ?> min</td>
                                    <td><?php echo number_format($operacion['quantity_to_process'], 2); ?></td>
                                    <td>
                                        <span class="status-badge badge-<?php echo $operacion['status']; ?>">
                                            <?php echo strtoupper($operacion['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB: Produccion -->
            <div id="tab-produccion" class="tab-content">
                <form method="POST" action="">
                    <input type="hidden" name="accion" value="registrar_produccion">
                    <input type="hidden" name="orden_id" value="<?php echo $orden_id; ?>">

                    <div class="grid-3">
                        <div class="form-group">
                            <label>Cantidad Producida</label>
                            <input type="number" name="cantidad_producida" step="0.01" required>
                        </div>

                        <div class="form-group">
                            <label>Cantidad Scrap</label>
                            <input type="number" name="cantidad_scrap" step="0.01" value="0">
                        </div>

                        <div class="form-group">
                            <label>Porcentaje Completado</label>
                            <input type="number" name="completion_percentage" step="0.01" min="0" max="100" value="<?php echo $orden_actual['completion_percentage']; ?>">
                        </div>
                    </div>

                    <div style="margin-top: 20px;">
                        <button type="submit" class="btn btn-success">Registrar Produccion</button>
                    </div>
                </form>

                <div style="margin-top: 30px;" class="card">
                    <div class="card-title">Resumen de Produccion</div>
                    <div class="info-row">
                        <span class="info-label">Cantidad Planificada:</span>
                        <span class="info-value"><?php echo number_format($orden_actual['quantity_to_produce'], 2); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Cantidad Producida:</span>
                        <span class="info-value"><?php echo number_format($orden_actual['quantity_produced'], 2); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Cantidad Scrap:</span>
                        <span class="info-value"><?php echo number_format($orden_actual['quantity_scrapped'], 2); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Porcentaje Completado:</span>
                        <span class="info-value"><?php echo number_format($orden_actual['completion_percentage'], 1); ?>%</span>
                    </div>
                </div>
            </div>

            <!-- TAB: Calidad -->
            <div id="tab-calidad" class="tab-content">
                <div style="margin-bottom: 20px;">
                    <button onclick="mostrarModal('modalCalidad')" class="btn btn-primary">+ Nueva Inspeccion</button>
                </div>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Numero</th>
                                <th>Fecha</th>
                                <th>Tipo</th>
                                <th>Inspeccionado</th>
                                <th>Aprobado</th>
                                <th>Rechazado</th>
                                <th>Reproceso</th>
                                <th>Resultado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($inspecciones_orden)): ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 30px;">
                                    No hay inspecciones registradas
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($inspecciones_orden as $inspeccion): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($inspeccion['inspection_number']); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($inspeccion['inspection_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($inspeccion['inspection_type']); ?></td>
                                    <td><?php echo number_format($inspeccion['quantity_inspected'], 2); ?></td>
                                    <td><?php echo number_format($inspeccion['quantity_accepted'], 2); ?></td>
                                    <td><?php echo number_format($inspeccion['quantity_rejected'], 2); ?></td>
                                    <td><?php echo number_format($inspeccion['quantity_rework'], 2); ?></td>
                                    <td>
                                        <span class="status-badge badge-<?php echo $inspeccion['inspection_result'] == 'passed' ? 'completed' : 'cancelled'; ?>">
                                            <?php echo strtoupper($inspeccion['inspection_result']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB: Costos -->
            <div id="tab-costos" class="tab-content">
                <div class="grid-2">
                    <div class="card">
                        <div class="card-title">Resumen de Costos</div>
                        <div class="info-row">
                            <span class="info-label">Costo Estandar:</span>
                            <span class="info-value">$<?php echo number_format($orden_actual['standard_cost'] ?? 0, 2); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Costo Real:</span>
                            <span class="info-value">$<?php echo number_format($orden_actual['actual_cost'] ?? 0, 2); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Variacion:</span>
                            <span class="info-value" style="color: <?php echo ($orden_actual['cost_variance'] ?? 0) > 0 ? '#ef4444' : '#10b981'; ?>">
                                $<?php echo number_format($orden_actual['cost_variance'] ?? 0, 2); ?>
                            </span>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-title">Desglose por Tipo</div>
                        <?php
                        $total_costos = 0;
                        foreach ($costos_orden as $costo) {
                            $total_costos += $costo['actual_cost'];
                        }
                        ?>
                        <?php if (empty($costos_orden)): ?>
                            <p style="opacity: 0.7;">No hay costos registrados</p>
                        <?php else: ?>
                            <?php foreach ($costos_orden as $costo): ?>
                            <div class="info-row">
                                <span class="info-label"><?php echo ucfirst($costo['cost_type']); ?>:</span>
                                <span class="info-value">$<?php echo number_format($costo['actual_cost'], 2); ?></span>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- TAB: Cierre -->
            <div id="tab-cierre" class="tab-content">
                <div class="card">
                    <div class="card-title">Cierre de Orden de Fabricacion</div>

                    <div style="margin-bottom: 20px;">
                        <h4 style="margin-bottom: 10px;">Verificaciones Previas al Cierre</h4>
                        <div class="info-row">
                            <span class="info-label">✓ Materiales consumidos registrados</span>
                            <span class="info-value"><?php echo count($materiales_orden); ?> materiales</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">✓ Operaciones completadas</span>
                            <span class="info-value"><?php echo count($operaciones_orden); ?> operaciones</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">✓ Inspecciones de calidad realizadas</span>
                            <span class="info-value"><?php echo count($inspecciones_orden); ?> inspecciones</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">✓ Produccion completada</span>
                            <span class="info-value"><?php echo number_format($orden_actual['completion_percentage'], 1); ?>%</span>
                        </div>
                    </div>

                    <?php if ($orden_actual['status'] != 'completed'): ?>
                    <form method="POST" action="" onsubmit="return confirm('Esta seguro de cerrar esta orden de fabricacion? Esta accion no se puede deshacer.');">
                        <input type="hidden" name="accion" value="cerrar_orden">
                        <input type="hidden" name="orden_id" value="<?php echo $orden_id; ?>">

                        <div class="form-group">
                            <label>Observaciones de Cierre</label>
                            <textarea name="observaciones_cierre" placeholder="Ingrese comentarios u observaciones sobre el cierre de esta orden"></textarea>
                        </div>

                        <div style="margin-top: 20px;">
                            <button type="submit" class="btn btn-success">Cerrar Orden de Fabricacion</button>
                        </div>
                    </form>
                    <?php else: ?>
                    <div class="alert alert-success">
                        Esta orden ya ha sido cerrada el <?php echo date('d/m/Y H:i', strtotime($orden_actual['end_date_actual'])); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php endif; ?>
    </div>

    <!-- Modal Agregar Material -->
    <div id="modalMaterial" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title">Agregar Material</div>
                <button class="close-modal" onclick="cerrarModal('modalMaterial')">&times;</button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="accion" value="agregar_material">
                <input type="hidden" name="orden_id" value="<?php echo $orden_id; ?>">

                <div class="form-group">
                    <label>Numero de Linea</label>
                    <input type="number" name="line_number" value="<?php echo count($materiales_orden) + 1; ?>" required>
                </div>

                <div class="form-group">
                    <label>Material</label>
                    <select name="material_id" required>
                        <option value="">Seleccione material</option>
                        <?php foreach ($productos as $prod): ?>
                        <option value="<?php echo $prod['id']; ?>">
                            <?php echo htmlspecialchars($prod['product_code'] . ' - ' . $prod['product_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Cantidad Requerida</label>
                    <input type="number" name="cantidad_requerida" step="0.01" required>
                </div>

                <div class="form-group">
                    <label>Unidad de Medida</label>
                    <input type="text" name="unidad_medida_material" value="UN" required>
                </div>

                <div class="form-group">
                    <label>Bodega</label>
                    <select name="warehouse_id">
                        <option value="">Seleccione bodega</option>
                        <?php foreach ($bodegas as $bodega): ?>
                        <option value="<?php echo $bodega['id']; ?>">
                            <?php echo htmlspecialchars($bodega['warehouse_code'] . ' - ' . $bodega['warehouse_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="margin-top: 20px;">
                    <button type="submit" class="btn btn-success">Agregar Material</button>
                    <button type="button" class="btn btn-secondary" onclick="cerrarModal('modalMaterial')">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Agregar Operacion -->
    <div id="modalOperacion" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title">Agregar Operacion</div>
                <button class="close-modal" onclick="cerrarModal('modalOperacion')">&times;</button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="accion" value="agregar_operacion">
                <input type="hidden" name="orden_id" value="<?php echo $orden_id; ?>">

                <div class="form-group">
                    <label>Numero de Operacion</label>
                    <input type="number" name="operation_number" value="<?php echo count($operaciones_orden) + 1; ?>" required>
                </div>

                <div class="form-group">
                    <label>Nombre de Operacion</label>
                    <input type="text" name="operation_name" required>
                </div>

                <div class="form-group">
                    <label>Centro de Trabajo</label>
                    <select name="work_center_id" required>
                        <option value="">Seleccione centro de trabajo</option>
                        <?php foreach ($centros_trabajo as $centro): ?>
                        <option value="<?php echo $centro['id']; ?>">
                            <?php echo htmlspecialchars($centro['work_center_code'] . ' - ' . $centro['work_center_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Tiempo Preparacion (minutos)</label>
                    <input type="number" name="tiempo_preparacion" value="0" required>
                </div>

                <div class="form-group">
                    <label>Tiempo Ejecucion (minutos)</label>
                    <input type="number" name="tiempo_ejecucion" value="0" required>
                </div>

                <div class="form-group">
                    <label>Cantidad a Procesar</label>
                    <input type="number" name="quantity_to_process" step="0.01" required>
                </div>

                <div style="margin-top: 20px;">
                    <button type="submit" class="btn btn-success">Agregar Operacion</button>
                    <button type="button" class="btn btn-secondary" onclick="cerrarModal('modalOperacion')">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Registrar Calidad -->
    <div id="modalCalidad" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title">Nueva Inspeccion de Calidad</div>
                <button class="close-modal" onclick="cerrarModal('modalCalidad')">&times;</button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="accion" value="registrar_calidad">
                <input type="hidden" name="orden_id" value="<?php echo $orden_id; ?>">
                <input type="hidden" name="product_id" value="<?php echo $orden_actual['product_id'] ?? ''; ?>">

                <div class="form-group">
                    <label>Tipo de Inspeccion</label>
                    <select name="inspection_type" required>
                        <option value="in_process">En Proceso</option>
                        <option value="final">Final</option>
                        <option value="random">Aleatoria</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Cantidad Inspeccionada</label>
                    <input type="number" name="quantity_inspected" step="0.01" required>
                </div>

                <div class="form-group">
                    <label>Cantidad Aprobada</label>
                    <input type="number" name="quantity_accepted" step="0.01" required>
                </div>

                <div class="form-group">
                    <label>Cantidad Rechazada</label>
                    <input type="number" name="quantity_rejected" step="0.01" value="0">
                </div>

                <div class="form-group">
                    <label>Cantidad a Reprocesar</label>
                    <input type="number" name="quantity_rework" step="0.01" value="0">
                </div>

                <div class="form-group">
                    <label>Resultado</label>
                    <select name="inspection_result" required>
                        <option value="passed">Aprobado</option>
                        <option value="failed">Rechazado</option>
                        <option value="partial">Parcial</option>
                        <option value="pending">Pendiente</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Observaciones</label>
                    <textarea name="notes_calidad"></textarea>
                </div>

                <div style="margin-top: 20px;">
                    <button type="submit" class="btn btn-success">Registrar Inspeccion</button>
                    <button type="button" class="btn btn-secondary" onclick="cerrarModal('modalCalidad')">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Funcion para cambiar tabs
        function cambiarTab(evt, tabId) {
            // Ocultar todos los contenidos
            var contents = document.getElementsByClassName('tab-content');
            for (var i = 0; i < contents.length; i++) {
                contents[i].classList.remove('active');
            }

            // Desactivar todos los tabs
            var tabs = document.getElementsByClassName('tab');
            for (var i = 0; i < tabs.length; i++) {
                tabs[i].classList.remove('active');
            }

            // Mostrar el contenido seleccionado
            document.getElementById(tabId).classList.add('active');
            evt.currentTarget.classList.add('active');
        }

        // Funciones para modales
        function mostrarModal(modalId) {
            document.getElementById(modalId).classList.add('active');
        }

        function cerrarModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }

        // Cerrar modal al hacer clic fuera
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('active');
            }
        }
    </script>
</body>
</html>
