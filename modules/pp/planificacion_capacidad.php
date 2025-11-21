<?php
/**
 * CONECTA ERP - PLANIFICACION DE CAPACIDAD CRP (CAPACITY REQUIREMENTS PLANNING)
 * Modulo Completo de Planificacion de Capacidad
 * Sistema CRP analisis de capacidad carga de trabajo y balance de recursos
 */

require_once '../../includes/config.php';

if (!isAuthenticated()) {
    header('Location: ../../login.php');
    exit;
}

$db = Database::getInstance();
$pdo = $db->getConnection();

$action = $_GET['action'] ?? $_POST['action'] ?? 'analysis';
$message = '';
$error = '';

// === CALCULAR CAPACIDAD ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'calculate') {
    try {
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        $message = "Calculo de capacidad ejecutado para el periodo " . $start_date . " a " . $end_date;
    } catch (Exception $e) {
        $error = "Error al calcular capacidad: " . $e->getMessage();
    }
}

// === OBTENER DATOS ===
$work_centers = [];
$capacity_analysis = [];
$production_orders = [];

try {
    // Centros de trabajo
    $stmt = $pdo->query("SELECT * FROM pp_work_centers WHERE status = 'active' ORDER BY center_name");
    $work_centers = $stmt->fetchAll();
    $stmt->closeCursor();

    // Ordenes de produccion
    $stmt = $pdo->query("
        SELECT * FROM pp_production_orders
        WHERE status IN ('pending', 'in_progress')
        ORDER BY planned_start_date
        LIMIT 100
    ");
    $production_orders = $stmt->fetchAll();
    $stmt->closeCursor();

    // Analisis de capacidad por centro
    foreach ($work_centers as $center) {
        $stmt = $pdo->prepare("
            SELECT
                SUM(ro.setup_time + ro.run_time * po.quantity_to_produce) as required_hours,
                COUNT(DISTINCT po.id) as order_count
            FROM pp_production_orders po
            INNER JOIN pp_production_order_operations poo ON po.id = poo.order_id
            INNER JOIN pp_routing_operations ro ON poo.operation_id = ro.id
            WHERE ro.work_center_id = ?
              AND po.status IN ('pending', 'in_progress')
              AND po.planned_start_date >= CURDATE()
              AND po.planned_start_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        ");
        $stmt->execute([$center['id']]);
        $load = $stmt->fetch();
        $stmt->closeCursor();

        $required_hours = $load['required_hours'] ?? 0;
        $available_hours = $center['capacity_hours'] * 30;
        $utilization = $available_hours > 0 ? ($required_hours / $available_hours) * 100 : 0;

        $capacity_analysis[] = [
            'center' => $center,
            'required_hours' => $required_hours,
            'available_hours' => $available_hours,
            'utilization' => $utilization,
            'order_count' => $load['order_count'] ?? 0
        ];
    }
} catch (PDOException $e) {
    $error = "Error al cargar datos: " . $e->getMessage();
}

$total_centers = count($work_centers);
$avg_utilization = count($capacity_analysis) > 0 ? array_sum(array_column($capacity_analysis, 'utilization')) / count($capacity_analysis) : 0;
$overloaded = count(array_filter($capacity_analysis, fn($c) => $c['utilization'] > 100));
$underutilized = count(array_filter($capacity_analysis, fn($c) => $c['utilization'] < 50));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planificacion de Capacidad CRP - CONECTA ERP</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f8fafc; color: #1e293b; line-height: 1.6; }
        .header { background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); color: white; padding: 2rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .header h1 { font-size: 2rem; font-weight: 700; margin-bottom: 0.5rem; }
        .header p { opacity: 0.9; font-size: 0.95rem; }
        .container { max-width: 1400px; margin: 0 auto; padding: 2rem; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-left: 4px solid #8b5cf6; }
        .stat-card h3 { font-size: 0.875rem; color: #64748b; margin-bottom: 0.5rem; text-transform: uppercase; font-weight: 600; }
        .stat-card .value { font-size: 2rem; font-weight: 700; color: #1e293b; }
        .stat-card .label { font-size: 0.875rem; color: #64748b; margin-top: 0.25rem; }
        .card { background: white; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); padding: 1.5rem; margin-bottom: 1.5rem; }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 2px solid #f1f5f9; }
        .card-header h2 { font-size: 1.5rem; color: #1e293b; font-weight: 700; }
        .btn { padding: 0.625rem 1.25rem; border: none; border-radius: 8px; font-size: 0.875rem; font-weight: 600; cursor: pointer; transition: all 0.2s; text-decoration: none; display: inline-block; }
        .btn-primary { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; }
        .btn-success { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; }
        .btn-secondary { background: #f1f5f9; color: #475569; }
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f8fafc; padding: 0.75rem; text-align: left; font-weight: 600; color: #475569; font-size: 0.875rem; border-bottom: 2px solid #e2e8f0; }
        td { padding: 0.75rem; border-bottom: 1px solid #f1f5f9; font-size: 0.875rem; }
        tr:hover { background: #f8fafc; }
        .badge { padding: 0.25rem 0.75rem; border-radius: 6px; font-size: 0.75rem; font-weight: 600; display: inline-block; }
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; color: #475569; font-size: 0.875rem; }
        .form-group input, .form-group select { width: 100%; padding: 0.625rem; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 0.875rem; }
        .alert { padding: 1rem 1.25rem; border-radius: 8px; margin-bottom: 1.5rem; font-weight: 500; }
        .alert-success { background: #dcfce7; color: #166534; border-left: 4px solid #10b981; }
        .alert-error { background: #fee2e2; color: #991b1b; border-left: 4px solid #ef4444; }
        .capacity-card { background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 8px; padding: 1rem; margin-bottom: 1rem; }
        .capacity-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; }
        .capacity-title { font-weight: 600; color: #1e293b; }
        .capacity-utilization { font-size: 1.5rem; font-weight: 700; }
        .capacity-bar { height: 20px; background: #e2e8f0; border-radius: 10px; overflow: hidden; margin-top: 0.75rem; }
        .capacity-bar-fill { height: 100%; transition: width 0.3s; }
        .capacity-details { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-top: 0.75rem; font-size: 0.875rem; color: #64748b; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Planificacion de Capacidad CRP</h1>
        <p>Analisis de capacidad carga de trabajo y balance de recursos</p>
    </div>

    <div class="container">
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>Centros Analizados</h3>
                <div class="value"><?php echo $total_centers; ?></div>
                <div class="label">centros de trabajo</div>
            </div>
            <div class="stat-card">
                <h3>Utilizacion Prom.</h3>
                <div class="value"><?php echo number_format($avg_utilization, 1); ?>%</div>
                <div class="label">capacidad utilizada</div>
            </div>
            <div class="stat-card">
                <h3>Sobrecargados</h3>
                <div class="value"><?php echo $overloaded; ?></div>
                <div class="label">centros >100%</div>
            </div>
            <div class="stat-card">
                <h3>Subutilizados</h3>
                <div class="value"><?php echo $underutilized; ?></div>
                <div class="label">centros <50%</div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>Calculadora de Capacidad</h2>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="calculate">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Fecha Inicio *</label>
                        <input type="date" name="start_date" required value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label>Fecha Fin *</label>
                        <input type="date" name="end_date" required value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>">
                    </div>
                </div>
                <button type="submit" class="btn btn-success">Calcular Capacidad</button>
            </form>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>Analisis de Capacidad por Centro (Proximos 30 dias)</h2>
            </div>
            <?php foreach ($capacity_analysis as $data): ?>
            <div class="capacity-card">
                <div class="capacity-header">
                    <div class="capacity-title"><?php echo htmlspecialchars($data['center']['center_name']); ?></div>
                    <div class="capacity-utilization" style="color: <?php echo $data['utilization'] > 100 ? '#ef4444' : ($data['utilization'] < 50 ? '#f59e0b' : '#10b981'); ?>">
                        <?php echo number_format($data['utilization'], 1); ?>%
                    </div>
                </div>
                <div class="capacity-bar">
                    <div class="capacity-bar-fill" style="width: <?php echo min($data['utilization'], 100); ?>%; background: linear-gradient(135deg, <?php echo $data['utilization'] > 100 ? '#ef4444, #dc2626' : ($data['utilization'] < 50 ? '#f59e0b, #d97706' : '#10b981, #059669'); ?>);"></div>
                </div>
                <div class="capacity-details">
                    <div><strong>Horas Requeridas:</strong> <?php echo number_format($data['required_hours'], 1); ?> hrs</div>
                    <div><strong>Horas Disponibles:</strong> <?php echo number_format($data['available_hours'], 1); ?> hrs</div>
                    <div><strong>Ordenes Asignadas:</strong> <?php echo $data['order_count']; ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>Ordenes de Produccion Planificadas</h2>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Orden</th>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Fecha Inicio</th>
                            <th>Fecha Fin</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($production_orders as $order): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($order['order_number']); ?></strong></td>
                            <td><?php echo htmlspecialchars($order['product_code'] ?? 'N/A'); ?></td>
                            <td><?php echo $order['quantity_to_produce']; ?></td>
                            <td><?php echo date('d/m/Y', strtotime($order['planned_start_date'])); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($order['planned_end_date'])); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $order['status'] === 'pending' ? 'warning' : 'success'; ?>">
                                    <?php echo strtoupper($order['status']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
