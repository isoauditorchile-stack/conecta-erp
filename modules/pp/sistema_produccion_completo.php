<?php
/**
 * SISTEMA DE PRODUCCION COMPLETO - TODOS LOS MODULOS PP
 * Rutas, Centros de Trabajo, Control de Planta, Capacidad, Costos, Calidad, Mantenimiento
 * Sin dependencias externas - Todo en un solo archivo
 */

session_start();

// Configuracion BD
define('DB_HOST', 'localhost');
define('DB_NAME', 'conectae_conectaerpbd');
define('DB_USER', 'conectae_conectaerpuser');
define('DB_PASS', 'pt125824caraud');

try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
} catch (PDOException $e) {
    die("Error de conexion: " . $e->getMessage());
}

$company_id = $_SESSION['company_id'] ?? 1;
$user_id = $_SESSION['user_id'] ?? 1;
$modulo = $_GET['mod'] ?? 'rutas';
$modo = $_GET['modo'] ?? 'listar';
$id = $_GET['id'] ?? 0;
$mensaje = '';

// Funciones
function q($pdo, $sql, $params = []) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    } catch(Exception $e) { return null; }
}

function qa($pdo, $sql, $params = []) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch(Exception $e) { return []; }
}

function ins($pdo, $sql, $params = []) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $pdo->lastInsertId();
    } catch(Exception $e) { return false; }
}

function upd($pdo, $sql, $params = []) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    } catch(Exception $e) { return false; }
}

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acc = $_POST['acc'] ?? '';

    switch($acc) {
        case 'crear_ruta':
            $num = 'RUTA-'.date('Ymd').'-'.rand(1000,9999);
            $id = ins($pdo, "INSERT INTO pp_routings (company_id, routing_number, routing_name, product_id, description, routing_version, valid_from, is_active, is_default, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 1, 0, 'draft', NOW())", [
                $company_id, $num, $_POST['routing_name'], $_POST['product_id'], $_POST['description'], $_POST['routing_version'] ?? 1, $_POST['valid_from'] ?? date('Y-m-d')
            ]);
            $mensaje = $id ? "Ruta creada: $num" : "Error al crear ruta";
            break;

        case 'agregar_operacion':
            $id = ins($pdo, "INSERT INTO pp_routing_operations (routing_id, operation_number, operation_name, description, work_center_id, operation_type, setup_time_minutes, run_time_per_unit_minutes, wait_time_minutes, move_time_minutes, is_critical_path, quality_inspection_required, instructions) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", [
                $_POST['routing_id'], $_POST['operation_number'], $_POST['operation_name'], $_POST['description'], $_POST['work_center_id'], $_POST['operation_type'] ?? 'processing', $_POST['setup_time_minutes'] ?? 0, $_POST['run_time_per_unit_minutes'] ?? 0, $_POST['wait_time_minutes'] ?? 0, $_POST['move_time_minutes'] ?? 0, isset($_POST['is_critical_path']) ? 1 : 0, isset($_POST['quality_inspection_required']) ? 1 : 0, $_POST['instructions'] ?? ''
            ]);
            $mensaje = $id ? "Operacion agregada" : "Error";
            break;

        case 'crear_centro':
            $cod = 'CT-'.date('Ymd').'-'.rand(100,999);
            $id = ins($pdo, "INSERT INTO pp_work_centers (company_id, work_center_code, work_center_name, work_center_type, description, capacity_per_hour, capacity_uom, efficiency_percentage, cost_per_hour, setup_time_minutes, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())", [
                $company_id, $cod, $_POST['work_center_name'], $_POST['work_center_type'], $_POST['description'], $_POST['capacity_per_hour'] ?? 0, $_POST['capacity_uom'] ?? 'UN', $_POST['efficiency_percentage'] ?? 100, $_POST['cost_per_hour'] ?? 0, $_POST['setup_time_minutes'] ?? 0
            ]);
            $mensaje = $id ? "Centro creado: $cod" : "Error";
            break;

        case 'registrar_parada':
            $id = ins($pdo, "INSERT INTO pp_downtime_logs (company_id, work_center_id, downtime_date, downtime_start, downtime_end, downtime_minutes, downtime_type, downtime_reason, description, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())", [
                $company_id, $_POST['work_center_id'], date('Y-m-d'), $_POST['downtime_start'], $_POST['downtime_end'], $_POST['downtime_minutes'], $_POST['downtime_type'], $_POST['downtime_reason'], $_POST['description']
            ]);
            $mensaje = $id ? "Parada registrada" : "Error";
            break;

        case 'registrar_inspeccion':
            $num = 'INS-'.date('Ymd').'-'.rand(1000,9999);
            $id = ins($pdo, "INSERT INTO pp_quality_inspections (company_id, inspection_number, inspection_date, inspection_type, production_order_id, product_id, quantity_inspected, quantity_accepted, quantity_rejected, quantity_rework, inspector_id, inspection_result, notes, created_at) VALUES (?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())", [
                $company_id, $num, $_POST['inspection_type'], $_POST['production_order_id'] ?? null, $_POST['product_id'], $_POST['quantity_inspected'], $_POST['quantity_accepted'], $_POST['quantity_rejected'], $_POST['quantity_rework'], $user_id, $_POST['inspection_result'], $_POST['notes']
            ]);
            $mensaje = $id ? "Inspeccion registrada: $num" : "Error";
            break;

        case 'crear_om':
            $num = 'OM-'.date('Ymd').'-'.rand(1000,9999);
            $id = ins($pdo, "INSERT INTO pp_maintenance_orders (company_id, order_number, work_center_id, maintenance_type, priority, problem_description, planned_start_date, planned_end_date, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'scheduled', NOW())", [
                $company_id, $num, $_POST['work_center_id'], $_POST['maintenance_type'], $_POST['priority'] ?? 'normal', $_POST['problem_description'], $_POST['planned_start_date'], $_POST['planned_end_date']
            ]);
            $mensaje = $id ? "Orden mantenimiento creada: $num" : "Error";
            break;
    }
}

// Obtener datos segun modulo
$datos = [];
switch($modulo) {
    case 'rutas':
        $datos = qa($pdo, "SELECT r.*, p.product_name, p.product_code FROM pp_routings r LEFT JOIN mm_products p ON r.product_id = p.id WHERE r.company_id = ? ORDER BY r.created_at DESC LIMIT 100", [$company_id]);
        break;
    case 'centros':
        $datos = qa($pdo, "SELECT * FROM pp_work_centers WHERE company_id = ? ORDER BY work_center_name LIMIT 100", [$company_id]);
        break;
    case 'control':
        $datos = qa($pdo, "SELECT po.*, p.product_name FROM pp_production_orders po LEFT JOIN mm_products p ON po.product_id = p.id WHERE po.company_id = ? AND po.status IN ('released', 'in_progress') ORDER BY po.start_date_planned LIMIT 50", [$company_id]);
        break;
    case 'capacidad':
        $datos = qa($pdo, "SELECT cp.*, wc.work_center_name FROM pp_capacity_planning cp LEFT JOIN pp_work_centers wc ON cp.work_center_id = wc.id WHERE cp.company_id = ? ORDER BY cp.planning_date DESC LIMIT 100", [$company_id]);
        break;
    case 'costos':
        $datos = qa($pdo, "SELECT pc.*, po.production_order_number FROM pp_production_costs pc LEFT JOIN pp_production_orders po ON pc.production_order_id = po.id WHERE po.company_id = ? ORDER BY pc.created_at DESC LIMIT 100", [$company_id]);
        break;
    case 'calidad':
        $datos = qa($pdo, "SELECT qi.*, p.product_name FROM pp_quality_inspections qi LEFT JOIN mm_products p ON qi.product_id = p.id WHERE qi.company_id = ? ORDER BY qi.inspection_date DESC LIMIT 100", [$company_id]);
        break;
    case 'mantenimiento':
        $datos = qa($pdo, "SELECT mo.*, wc.work_center_name FROM pp_maintenance_orders mo LEFT JOIN pp_work_centers wc ON mo.work_center_id = wc.id WHERE mo.company_id = ? ORDER BY mo.created_at DESC LIMIT 100", [$company_id]);
        break;
}

$productos = qa($pdo, "SELECT id, product_code, product_name FROM mm_products WHERE company_id = ? AND is_active = 1 ORDER BY product_name LIMIT 500", [$company_id]);
$centros = qa($pdo, "SELECT id, work_center_code, work_center_name FROM pp_work_centers WHERE company_id = ? AND is_active = 1 ORDER BY work_center_name LIMIT 100", [$company_id]);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema Produccion Completo - CONECTA ERP</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; background: #f8fafc; color: #1e293b; min-height: 100vh; padding: 20px; }
        .container { max-width: 1600px; margin: 0 auto; }
        .header { background: #fff; border-radius: 15px; padding: 20px 30px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.08); display: flex; justify-content: space-between; align-items: center; border-left: 4px solid #3b82f6; }
        .header h1 { font-size: 24px; display: flex; align-items: center; gap: 10px; color: #0f172a; }
        .nav { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 20px; }
        .nav a { padding: 10px 20px; background: #fff; border-radius: 8px; text-decoration: none; color: #475569; font-weight: 600; transition: all 0.3s; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #e2e8f0; }
        .nav a:hover { background: #f1f5f9; color: #3b82f6; transform: translateY(-2px); box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .nav a.active { background: #3b82f6; color: #fff; border-color: #2563eb; }
        .section { background: #fff; border-radius: 15px; padding: 20px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.08); }
        .section-title { font-size: 18px; font-weight: 700; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #e2e8f0; color: #0f172a; }
        .btn { padding: 10px 20px; border-radius: 8px; border: none; font-weight: 600; cursor: pointer; transition: all 0.3s; text-decoration: none; display: inline-block; font-size: 14px; }
        .btn-primary { background: #3b82f6; color: #fff; }
        .btn-primary:hover { background: #2563eb; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(59,130,246,0.4); }
        .btn-success { background: #10b981; color: #fff; }
        .btn-success:hover { background: #059669; transform: translateY(-2px); }
        .btn-sm { padding: 6px 12px; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th { background: #f8fafc; padding: 10px; text-align: left; font-weight: 700; font-size: 12px; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; color: #475569; }
        td { padding: 10px; border-bottom: 1px solid #f1f5f9; font-size: 14px; color: #334155; }
        tr:hover { background: #f8fafc; }
        .form-group { display: flex; flex-direction: column; gap: 8px; margin-bottom: 15px; }
        .form-group label { font-size: 13px; font-weight: 600; text-transform: uppercase; color: #475569; }
        .form-group input, .form-group select, .form-group textarea { padding: 10px; border-radius: 8px; border: 1px solid #cbd5e1; background: #fff; color: #1e293b; font-size: 14px; outline: none; transition: all 0.3s; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
        .form-group select option { background: #fff; color: #1e293b; }
        .grid-2 { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px; }
        .grid-3 { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; }
        .card { background: #f8fafc; border-radius: 10px; padding: 15px; border: 1px solid #e2e8f0; }
        .card-title { font-size: 16px; font-weight: 700; margin-bottom: 10px; padding-bottom: 8px; border-bottom: 1px solid #e2e8f0; color: #0f172a; }
        .info-row { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #f1f5f9; color: #475569; }
        .status-badge { display: inline-block; padding: 4px 10px; border-radius: 15px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .badge-active { background: #dcfce7; color: #166534; border: 1px solid #86efac; }
        .badge-inactive { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }
        .badge-draft { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }
        .badge-approved { background: #dbeafe; color: #1e40af; border: 1px solid #93c5fd; }
        .badge-scheduled { background: #fef3c7; color: #92400e; border: 1px solid #fde047; }
        .badge-completed { background: #dcfce7; color: #166534; border: 1px solid #86efac; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: #fff; border-radius: 15px; padding: 25px; max-width: 600px; width: 90%; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 25px rgba(0,0,0,0.15); }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .modal-header h3 { color: #0f172a; }
        .close-modal { background: #f1f5f9; border: none; color: #475569; font-size: 24px; cursor: pointer; width: 30px; height: 30px; border-radius: 50%; transition: all 0.3s; }
        .close-modal:hover { background: #e2e8f0; }
        .alert { padding: 12px 15px; border-radius: 8px; margin-bottom: 15px; border-left: 4px solid; }
        .alert-success { background: #dcfce7; border-color: #22c55e; color: #166534; }
        @media (max-width: 768px) { .grid-2, .grid-3 { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><span>🏭</span> SISTEMA DE PRODUCCION COMPLETO</h1>
        </div>

        <?php if($mensaje): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($mensaje); ?></div>
        <?php endif; ?>

        <div class="nav">
            <a href="?mod=rutas" class="<?php echo $modulo=='rutas'?'active':''; ?>">🔀 Rutas de Produccion</a>
            <a href="?mod=centros" class="<?php echo $modulo=='centros'?'active':''; ?>">🏢 Centros de Trabajo</a>
            <a href="?mod=control" class="<?php echo $modulo=='control'?'active':''; ?>">📊 Control de Planta</a>
            <a href="?mod=capacidad" class="<?php echo $modulo=='capacidad'?'active':''; ?>">📈 Capacidad (CRP)</a>
            <a href="?mod=costos" class="<?php echo $modulo=='costos'?'active':''; ?>">💰 Costos</a>
            <a href="?mod=calidad" class="<?php echo $modulo=='calidad'?'active':''; ?>">✨ Calidad</a>
            <a href="?mod=mantenimiento" class="<?php echo $modulo=='mantenimiento'?'active':''; ?>">🔧 Mantenimiento</a>
        </div>

        <?php if($modulo == 'rutas'): ?>
        <div class="section">
            <div class="section-title">
                Rutas de Produccion (<?php echo count($datos); ?>)
                <button onclick="mostrarModal('modalRuta')" class="btn btn-primary" style="float:right;">+ Nueva Ruta</button>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Codigo</th>
                        <th>Nombre</th>
                        <th>Producto</th>
                        <th>Version</th>
                        <th>Estado</th>
                        <th>Valido Desde</th>
                        <th>Operaciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($datos)): ?>
                    <tr><td colspan="7" style="text-align:center;padding:30px;">No hay rutas registradas</td></tr>
                    <?php else: ?>
                        <?php foreach($datos as $d):
                            $ops = qa($pdo, "SELECT COUNT(*) as total FROM pp_routing_operations WHERE routing_id = ?", [$d['id']]);
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($d['routing_number']); ?></strong></td>
                            <td><?php echo htmlspecialchars($d['routing_name']); ?></td>
                            <td><?php echo htmlspecialchars($d['product_code'].' - '.$d['product_name']); ?></td>
                            <td>V<?php echo $d['routing_version']; ?></td>
                            <td><span class="status-badge badge-<?php echo $d['status']; ?>"><?php echo strtoupper($d['status']); ?></span></td>
                            <td><?php echo date('d/m/Y', strtotime($d['valid_from'])); ?></td>
                            <td><?php echo $ops[0]['total']; ?> operaciones</td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php elseif($modulo == 'centros'): ?>
        <div class="section">
            <div class="section-title">
                Centros de Trabajo (<?php echo count($datos); ?>)
                <button onclick="mostrarModal('modalCentro')" class="btn btn-primary" style="float:right;">+ Nuevo Centro</button>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Codigo</th>
                        <th>Nombre</th>
                        <th>Tipo</th>
                        <th>Capacidad/Hora</th>
                        <th>Eficiencia</th>
                        <th>Costo/Hora</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($datos)): ?>
                    <tr><td colspan="7" style="text-align:center;padding:30px;">No hay centros registrados</td></tr>
                    <?php else: ?>
                        <?php foreach($datos as $d): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($d['work_center_code']); ?></strong></td>
                            <td><?php echo htmlspecialchars($d['work_center_name']); ?></td>
                            <td><?php echo htmlspecialchars($d['work_center_type']); ?></td>
                            <td><?php echo number_format($d['capacity_per_hour'], 2); ?> <?php echo $d['capacity_uom']; ?></td>
                            <td><?php echo number_format($d['efficiency_percentage'], 1); ?>%</td>
                            <td>$<?php echo number_format($d['cost_per_hour'], 2); ?></td>
                            <td><span class="status-badge badge-<?php echo $d['is_active']?'active':'inactive'; ?>"><?php echo $d['is_active']?'ACTIVO':'INACTIVO'; ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php elseif($modulo == 'control'): ?>
        <div class="section">
            <div class="section-title">Control de Planta - Ordenes en Ejecucion (<?php echo count($datos); ?>)</div>
            <div class="grid-3" style="margin-bottom:20px;">
                <?php
                $total_ops = count($datos);
                $en_proceso = count(array_filter($datos, function($d){ return $d['status']=='in_progress'; }));
                $produccion_total = array_sum(array_column($datos, 'quantity_produced'));
                ?>
                <div class="card">
                    <div class="card-title">OPs Activas</div>
                    <div style="font-size:32px;font-weight:800;color:#10b981;"><?php echo $total_ops; ?></div>
                </div>
                <div class="card">
                    <div class="card-title">En Proceso</div>
                    <div style="font-size:32px;font-weight:800;color:#f59e0b;"><?php echo $en_proceso; ?></div>
                </div>
                <div class="card">
                    <div class="card-title">Produccion Acumulada</div>
                    <div style="font-size:32px;font-weight:800;color:#3b82f6;"><?php echo number_format($produccion_total, 0); ?></div>
                </div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Orden</th>
                        <th>Producto</th>
                        <th>Cantidad</th>
                        <th>Producido</th>
                        <th>Progreso</th>
                        <th>Estado</th>
                        <th>Inicio Plan</th>
                        <th>Fin Plan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($datos)): ?>
                    <tr><td colspan="8" style="text-align:center;padding:30px;">No hay ordenes activas</td></tr>
                    <?php else: ?>
                        <?php foreach($datos as $d): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($d['production_order_number']); ?></strong></td>
                            <td><?php echo htmlspecialchars($d['product_name']); ?></td>
                            <td><?php echo number_format($d['quantity_to_produce'], 2); ?></td>
                            <td><?php echo number_format($d['quantity_produced'], 2); ?></td>
                            <td>
                                <div style="background:rgba(255,255,255,0.1);border-radius:10px;height:20px;overflow:hidden;">
                                    <div style="background:#10b981;height:100%;width:<?php echo min($d['completion_percentage'], 100); ?>%;"></div>
                                </div>
                                <small><?php echo number_format($d['completion_percentage'], 1); ?>%</small>
                            </td>
                            <td><span class="status-badge badge-<?php echo $d['status']; ?>"><?php echo strtoupper($d['status']); ?></span></td>
                            <td><?php echo date('d/m/Y', strtotime($d['start_date_planned'])); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($d['end_date_planned'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php elseif($modulo == 'capacidad'): ?>
        <div class="section">
            <div class="section-title">Planificacion de Capacidad - CRP (<?php echo count($datos); ?>)</div>
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Centro de Trabajo</th>
                        <th>Cap. Disponible</th>
                        <th>Cap. Requerida</th>
                        <th>Cap. Utilizada</th>
                        <th>Variacion</th>
                        <th>Utilizacion %</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($datos)): ?>
                    <tr><td colspan="8" style="text-align:center;padding:30px;">No hay datos de capacidad</td></tr>
                    <?php else: ?>
                        <?php foreach($datos as $d): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($d['planning_date'])); ?></td>
                            <td><?php echo htmlspecialchars($d['work_center_name']); ?></td>
                            <td><?php echo number_format($d['available_capacity_hours'], 2); ?> hrs</td>
                            <td><?php echo number_format($d['required_capacity_hours'], 2); ?> hrs</td>
                            <td><?php echo number_format($d['utilized_capacity_hours'], 2); ?> hrs</td>
                            <td style="color:<?php echo $d['capacity_variance_hours']>=0?'#10b981':'#ef4444'; ?>">
                                <?php echo number_format($d['capacity_variance_hours'], 2); ?> hrs
                            </td>
                            <td><?php echo number_format($d['utilization_percentage'], 1); ?>%</td>
                            <td><span class="status-badge badge-<?php echo $d['status']=='optimal'?'active':'inactive'; ?>"><?php echo strtoupper($d['status']); ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php elseif($modulo == 'costos'): ?>
        <div class="section">
            <div class="section-title">Costos de Produccion (<?php echo count($datos); ?>)</div>
            <?php
            $total_costos = array_sum(array_column($datos, 'actual_cost'));
            $costos_por_tipo = [];
            foreach($datos as $d) {
                if(!isset($costos_por_tipo[$d['cost_type']])) $costos_por_tipo[$d['cost_type']] = 0;
                $costos_por_tipo[$d['cost_type']] += $d['actual_cost'];
            }
            ?>
            <div class="grid-2" style="margin-bottom:20px;">
                <div class="card">
                    <div class="card-title">Costo Total Acumulado</div>
                    <div style="font-size:32px;font-weight:800;color:#10b981;">$<?php echo number_format($total_costos, 2); ?></div>
                </div>
                <div class="card">
                    <div class="card-title">Desglose por Tipo</div>
                    <?php foreach($costos_por_tipo as $tipo => $monto): ?>
                    <div class="info-row">
                        <span><?php echo ucfirst($tipo); ?>:</span>
                        <span style="font-weight:700;">$<?php echo number_format($monto, 2); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>OF</th>
                        <th>Tipo Costo</th>
                        <th>Elemento</th>
                        <th>Costo Estandar</th>
                        <th>Costo Real</th>
                        <th>Variacion</th>
                        <th>Cantidad</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($datos)): ?>
                    <tr><td colspan="7" style="text-align:center;padding:30px;">No hay costos registrados</td></tr>
                    <?php else: ?>
                        <?php foreach($datos as $d): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($d['production_order_number']); ?></td>
                            <td><?php echo htmlspecialchars($d['cost_type']); ?></td>
                            <td><?php echo htmlspecialchars($d['cost_element']); ?></td>
                            <td>$<?php echo number_format($d['standard_cost'], 2); ?></td>
                            <td>$<?php echo number_format($d['actual_cost'], 2); ?></td>
                            <td style="color:<?php echo $d['cost_variance']>=0?'#ef4444':'#10b981'; ?>">
                                $<?php echo number_format($d['cost_variance'], 2); ?>
                            </td>
                            <td><?php echo number_format($d['quantity'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php elseif($modulo == 'calidad'): ?>
        <div class="section">
            <div class="section-title">
                Control de Calidad (<?php echo count($datos); ?>)
                <button onclick="mostrarModal('modalInspeccion')" class="btn btn-primary" style="float:right;">+ Nueva Inspeccion</button>
            </div>
            <?php
            $total_inspeccionado = array_sum(array_column($datos, 'quantity_inspected'));
            $total_aprobado = array_sum(array_column($datos, 'quantity_accepted'));
            $total_rechazado = array_sum(array_column($datos, 'quantity_rejected'));
            $calidad_pct = $total_inspeccionado > 0 ? ($total_aprobado / $total_inspeccionado * 100) : 100;
            ?>
            <div class="grid-3" style="margin-bottom:20px;">
                <div class="card">
                    <div class="card-title">Total Inspeccionado</div>
                    <div style="font-size:28px;font-weight:800;"><?php echo number_format($total_inspeccionado, 0); ?></div>
                </div>
                <div class="card">
                    <div class="card-title">Aprobado</div>
                    <div style="font-size:28px;font-weight:800;color:#10b981;"><?php echo number_format($total_aprobado, 0); ?></div>
                </div>
                <div class="card">
                    <div class="card-title">Rechazado</div>
                    <div style="font-size:28px;font-weight:800;color:#ef4444;"><?php echo number_format($total_rechazado, 0); ?></div>
                </div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Numero</th>
                        <th>Fecha</th>
                        <th>Tipo</th>
                        <th>Producto</th>
                        <th>Inspeccionado</th>
                        <th>Aprobado</th>
                        <th>Rechazado</th>
                        <th>Reproceso</th>
                        <th>Resultado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($datos)): ?>
                    <tr><td colspan="9" style="text-align:center;padding:30px;">No hay inspecciones</td></tr>
                    <?php else: ?>
                        <?php foreach($datos as $d): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($d['inspection_number']); ?></strong></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($d['inspection_date'])); ?></td>
                            <td><?php echo htmlspecialchars($d['inspection_type']); ?></td>
                            <td><?php echo htmlspecialchars($d['product_name']); ?></td>
                            <td><?php echo number_format($d['quantity_inspected'], 2); ?></td>
                            <td style="color:#10b981;"><?php echo number_format($d['quantity_accepted'], 2); ?></td>
                            <td style="color:#ef4444;"><?php echo number_format($d['quantity_rejected'], 2); ?></td>
                            <td><?php echo number_format($d['quantity_rework'], 2); ?></td>
                            <td><span class="status-badge badge-<?php echo $d['inspection_result']=='passed'?'completed':'inactive'; ?>"><?php echo strtoupper($d['inspection_result']); ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php elseif($modulo == 'mantenimiento'): ?>
        <div class="section">
            <div class="section-title">
                Ordenes de Mantenimiento (<?php echo count($datos); ?>)
                <button onclick="mostrarModal('modalOM')" class="btn btn-primary" style="float:right;">+ Nueva OM</button>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Numero</th>
                        <th>Centro de Trabajo</th>
                        <th>Tipo</th>
                        <th>Prioridad</th>
                        <th>Inicio Plan</th>
                        <th>Fin Plan</th>
                        <th>Estado</th>
                        <th>Duracion</th>
                        <th>Costo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($datos)): ?>
                    <tr><td colspan="9" style="text-align:center;padding:30px;">No hay ordenes de mantenimiento</td></tr>
                    <?php else: ?>
                        <?php foreach($datos as $d): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($d['order_number']); ?></strong></td>
                            <td><?php echo htmlspecialchars($d['work_center_name']); ?></td>
                            <td><?php echo htmlspecialchars($d['maintenance_type']); ?></td>
                            <td><?php echo htmlspecialchars($d['priority']); ?></td>
                            <td><?php echo $d['planned_start_date'] ? date('d/m/Y', strtotime($d['planned_start_date'])) : '-'; ?></td>
                            <td><?php echo $d['planned_end_date'] ? date('d/m/Y', strtotime($d['planned_end_date'])) : '-'; ?></td>
                            <td><span class="status-badge badge-<?php echo $d['status']; ?>"><?php echo strtoupper($d['status']); ?></span></td>
                            <td><?php echo number_format($d['duration_hours'], 2); ?> hrs</td>
                            <td>$<?php echo number_format($d['total_cost'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php endif; ?>
    </div>

    <!-- Modal Nueva Ruta -->
    <div id="modalRuta" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Nueva Ruta de Produccion</h3>
                <button class="close-modal" onclick="cerrarModal('modalRuta')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="acc" value="crear_ruta">
                <div class="form-group">
                    <label>Nombre Ruta *</label>
                    <input type="text" name="routing_name" required>
                </div>
                <div class="form-group">
                    <label>Producto *</label>
                    <select name="product_id" required>
                        <option value="">Seleccione</option>
                        <?php foreach($productos as $p): ?>
                        <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['product_code'].' - '.$p['product_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Version</label>
                    <input type="number" name="routing_version" value="1" min="1">
                </div>
                <div class="form-group">
                    <label>Descripcion</label>
                    <textarea name="description"></textarea>
                </div>
                <button type="submit" class="btn btn-success">Crear Ruta</button>
            </form>
        </div>
    </div>

    <!-- Modal Nuevo Centro -->
    <div id="modalCentro" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Nuevo Centro de Trabajo</h3>
                <button class="close-modal" onclick="cerrarModal('modalCentro')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="acc" value="crear_centro">
                <div class="form-group">
                    <label>Nombre Centro *</label>
                    <input type="text" name="work_center_name" required>
                </div>
                <div class="form-group">
                    <label>Tipo *</label>
                    <select name="work_center_type" required>
                        <option value="machine">Maquina</option>
                        <option value="assembly_line">Linea Ensamblaje</option>
                        <option value="workstation">Puesto de Trabajo</option>
                        <option value="production_line">Linea Produccion</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Capacidad por Hora</label>
                    <input type="number" name="capacity_per_hour" step="0.01">
                </div>
                <div class="form-group">
                    <label>Eficiencia %</label>
                    <input type="number" name="efficiency_percentage" value="100" step="0.01">
                </div>
                <div class="form-group">
                    <label>Costo por Hora</label>
                    <input type="number" name="cost_per_hour" step="0.01">
                </div>
                <button type="submit" class="btn btn-success">Crear Centro</button>
            </form>
        </div>
    </div>

    <!-- Modal Nueva Inspeccion -->
    <div id="modalInspeccion" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Nueva Inspeccion de Calidad</h3>
                <button class="close-modal" onclick="cerrarModal('modalInspeccion')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="acc" value="registrar_inspeccion">
                <div class="form-group">
                    <label>Tipo *</label>
                    <select name="inspection_type" required>
                        <option value="in_process">En Proceso</option>
                        <option value="final">Final</option>
                        <option value="random">Aleatoria</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Producto *</label>
                    <select name="product_id" required>
                        <option value="">Seleccione</option>
                        <?php foreach($productos as $p): ?>
                        <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['product_code'].' - '.$p['product_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Cantidad Inspeccionada *</label>
                    <input type="number" name="quantity_inspected" step="0.01" required>
                </div>
                <div class="form-group">
                    <label>Cantidad Aprobada *</label>
                    <input type="number" name="quantity_accepted" step="0.01" required>
                </div>
                <div class="form-group">
                    <label>Cantidad Rechazada</label>
                    <input type="number" name="quantity_rejected" step="0.01" value="0">
                </div>
                <div class="form-group">
                    <label>Cantidad Reproceso</label>
                    <input type="number" name="quantity_rework" step="0.01" value="0">
                </div>
                <div class="form-group">
                    <label>Resultado *</label>
                    <select name="inspection_result" required>
                        <option value="passed">Aprobado</option>
                        <option value="failed">Rechazado</option>
                        <option value="partial">Parcial</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Observaciones</label>
                    <textarea name="notes"></textarea>
                </div>
                <button type="submit" class="btn btn-success">Registrar Inspeccion</button>
            </form>
        </div>
    </div>

    <!-- Modal Nueva OM -->
    <div id="modalOM" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Nueva Orden de Mantenimiento</h3>
                <button class="close-modal" onclick="cerrarModal('modalOM')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="acc" value="crear_om">
                <div class="form-group">
                    <label>Centro de Trabajo *</label>
                    <select name="work_center_id" required>
                        <option value="">Seleccione</option>
                        <?php foreach($centros as $c): ?>
                        <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['work_center_code'].' - '.$c['work_center_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Tipo *</label>
                    <select name="maintenance_type" required>
                        <option value="preventive">Preventivo</option>
                        <option value="corrective">Correctivo</option>
                        <option value="predictive">Predictivo</option>
                        <option value="breakdown">Averia</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Prioridad *</label>
                    <select name="priority" required>
                        <option value="low">Baja</option>
                        <option value="normal">Normal</option>
                        <option value="high">Alta</option>
                        <option value="urgent">Urgente</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Descripcion Problema *</label>
                    <textarea name="problem_description" required></textarea>
                </div>
                <div class="form-group">
                    <label>Fecha Inicio Planeada</label>
                    <input type="date" name="planned_start_date" value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="form-group">
                    <label>Fecha Fin Planeada</label>
                    <input type="date" name="planned_end_date" value="<?php echo date('Y-m-d'); ?>">
                </div>
                <button type="submit" class="btn btn-success">Crear OM</button>
            </form>
        </div>
    </div>

    <script>
        function mostrarModal(id) { document.getElementById(id).classList.add('active'); }
        function cerrarModal(id) { document.getElementById(id).classList.remove('active'); }
        window.onclick = function(e) { if(e.target.classList.contains('modal')) e.target.classList.remove('active'); }
    </script>
</body>
</html>
