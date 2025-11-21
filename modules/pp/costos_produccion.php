<?php
/**
 * CONECTA ERP - COSTOS DE PRODUCCION (PRODUCTION COSTS)
 * Modulo Completo de Costos de Produccion
 * Sistema de analisis de costos materiales mano de obra overhead y rentabilidad
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

$action = $_GET['action'] ?? $_POST['action'] ?? 'dashboard';
$order_id = $_GET['id'] ?? null;
$message = '';
$error = '';

// === CALCULAR COSTOS ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'calculate_costs' && $order_id) {
    try {
        // Calcular costos de materiales
        $stmt = $pdo->prepare("
            UPDATE pp_production_orders
            SET material_cost = (
                SELECT COALESCE(SUM(pom.quantity_required * pom.unit_cost), 0)
                FROM pp_production_order_materials pom
                WHERE pom.order_id = ?
            )
            WHERE id = ?
        ");
        $stmt->execute([$order_id, $order_id]);
        $stmt->closeCursor();

        // Calcular costos de mano de obra
        $stmt = $pdo->prepare("
            UPDATE pp_production_orders
            SET labor_cost = (
                SELECT COALESCE(SUM(poo.actual_time * wc.labor_cost), 0)
                FROM pp_production_order_operations poo
                INNER JOIN pp_routing_operations ro ON poo.operation_id = ro.id
                INNER JOIN pp_work_centers wc ON ro.work_center_id = wc.id
                WHERE poo.order_id = ?
            )
            WHERE id = ?
        ");
        $stmt->execute([$order_id, $order_id]);
        $stmt->closeCursor();

        $message = "Costos calculados exitosamente";
    } catch (PDOException $e) {
        $error = "Error al calcular costos: " . $e->getMessage();
    }
}

// === OBTENER DATOS ===
$orders = [];
$cost_analysis = [];

try {
    // Ordenes de produccion
    $stmt = $pdo->query("
        SELECT po.*, p.product_name,
               COALESCE(po.material_cost, 0) as material_cost,
               COALESCE(po.labor_cost, 0) as labor_cost,
               COALESCE(po.overhead_cost, 0) as overhead_cost,
               (COALESCE(po.material_cost, 0) + COALESCE(po.labor_cost, 0) + COALESCE(po.overhead_cost, 0)) as total_cost
        FROM pp_production_orders po
        LEFT JOIN products p ON po.product_id = p.id
        WHERE po.status IN ('in_progress', 'completed')
        ORDER BY po.created_at DESC
        LIMIT 100
    ");
    $orders = $stmt->fetchAll();
    $stmt->closeCursor();

    // Analisis de costos
    $stmt = $pdo->query("
        SELECT
            SUM(COALESCE(material_cost, 0)) as total_materials,
            SUM(COALESCE(labor_cost, 0)) as total_labor,
            SUM(COALESCE(overhead_cost, 0)) as total_overhead,
            SUM(COALESCE(material_cost, 0) + COALESCE(labor_cost, 0) + COALESCE(overhead_cost, 0)) as total_costs,
            COUNT(*) as order_count
        FROM pp_production_orders
        WHERE status = 'completed'
          AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    ");
    $cost_analysis = $stmt->fetch();
    $stmt->closeCursor();
} catch (PDOException $e) {
    $error = "Error al cargar datos: " . $e->getMessage();
}

$total_materials = $cost_analysis['total_materials'] ?? 0;
$total_labor = $cost_analysis['total_labor'] ?? 0;
$total_overhead = $cost_analysis['total_overhead'] ?? 0;
$total_costs = $cost_analysis['total_costs'] ?? 0;
$order_count = $cost_analysis['order_count'] ?? 0;
$avg_cost = $order_count > 0 ? $total_costs / $order_count : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Costos de Produccion - CONECTA ERP</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f8fafc; color: #1e293b; line-height: 1.6; }
        .header { background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%); color: white; padding: 2rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .header h1 { font-size: 2rem; font-weight: 700; margin-bottom: 0.5rem; }
        .header p { opacity: 0.9; font-size: 0.95rem; }
        .container { max-width: 1400px; margin: 0 auto; padding: 2rem; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-left: 4px solid #06b6d4; }
        .stat-card h3 { font-size: 0.875rem; color: #64748b; margin-bottom: 0.5rem; text-transform: uppercase; font-weight: 600; }
        .stat-card .value { font-size: 2rem; font-weight: 700; color: #1e293b; }
        .stat-card .label { font-size: 0.875rem; color: #64748b; margin-top: 0.25rem; }
        .card { background: white; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); padding: 1.5rem; margin-bottom: 1.5rem; }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 2px solid #f1f5f9; }
        .card-header h2 { font-size: 1.5rem; color: #1e293b; font-weight: 700; }
        .btn { padding: 0.625rem 1.25rem; border: none; border-radius: 8px; font-size: 0.875rem; font-weight: 600; cursor: pointer; transition: all 0.2s; text-decoration: none; display: inline-block; }
        .btn-primary { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; }
        .btn-small { padding: 0.375rem 0.75rem; font-size: 0.813rem; }
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f8fafc; padding: 0.75rem; text-align: left; font-weight: 600; color: #475569; font-size: 0.875rem; border-bottom: 2px solid #e2e8f0; }
        td { padding: 0.75rem; border-bottom: 1px solid #f1f5f9; font-size: 0.875rem; }
        tr:hover { background: #f8fafc; }
        .badge { padding: 0.25rem 0.75rem; border-radius: 6px; font-size: 0.75rem; font-weight: 600; display: inline-block; }
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .alert { padding: 1rem 1.25rem; border-radius: 8px; margin-bottom: 1.5rem; font-weight: 500; }
        .alert-success { background: #dcfce7; color: #166534; border-left: 4px solid #10b981; }
        .alert-error { background: #fee2e2; color: #991b1b; border-left: 4px solid #ef4444; }
        .cost-breakdown { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-top: 1rem; }
        .cost-item { background: #f8fafc; padding: 1rem; border-radius: 8px; text-align: center; }
        .cost-item-label { font-size: 0.875rem; color: #64748b; margin-bottom: 0.5rem; }
        .cost-item-value { font-size: 1.5rem; font-weight: 700; color: #1e293b; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Costos de Produccion</h1>
        <p>Analisis de costos materiales mano de obra overhead y rentabilidad</p>
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
                <h3>Costo Total</h3>
                <div class="value">$<?php echo number_format($total_costs, 2); ?></div>
                <div class="label">ultimos 30 dias</div>
            </div>
            <div class="stat-card">
                <h3>Costo Promedio</h3>
                <div class="value">$<?php echo number_format($avg_cost, 2); ?></div>
                <div class="label">por orden</div>
            </div>
            <div class="stat-card">
                <h3>Materiales</h3>
                <div class="value">$<?php echo number_format($total_materials, 2); ?></div>
                <div class="label"><?php echo $total_costs > 0 ? number_format(($total_materials / $total_costs) * 100, 1) : 0; ?>%</div>
            </div>
            <div class="stat-card">
                <h3>Mano de Obra</h3>
                <div class="value">$<?php echo number_format($total_labor, 2); ?></div>
                <div class="label"><?php echo $total_costs > 0 ? number_format(($total_labor / $total_costs) * 100, 1) : 0; ?>%</div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>Ordenes de Produccion - Analisis de Costos</h2>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Orden</th>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Materiales</th>
                            <th>Mano Obra</th>
                            <th>Overhead</th>
                            <th>Total</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($order['order_number']); ?></strong></td>
                            <td><?php echo htmlspecialchars($order['product_name'] ?? 'N/A'); ?></td>
                            <td><?php echo $order['quantity_to_produce']; ?></td>
                            <td>$<?php echo number_format($order['material_cost'], 2); ?></td>
                            <td>$<?php echo number_format($order['labor_cost'], 2); ?></td>
                            <td>$<?php echo number_format($order['overhead_cost'], 2); ?></td>
                            <td><strong>$<?php echo number_format($order['total_cost'], 2); ?></strong></td>
                            <td>
                                <span class="badge badge-<?php echo $order['status'] === 'completed' ? 'success' : 'warning'; ?>">
                                    <?php echo strtoupper($order['status']); ?>
                                </span>
                            </td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="calculate_costs">
                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                    <button type="submit" class="btn btn-primary btn-small">Calcular</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>Distribucion de Costos</h2>
            </div>
            <div class="cost-breakdown">
                <div class="cost-item">
                    <div class="cost-item-label">Materiales</div>
                    <div class="cost-item-value" style="color: #06b6d4;">
                        <?php echo $total_costs > 0 ? number_format(($total_materials / $total_costs) * 100, 1) : 0; ?>%
                    </div>
                </div>
                <div class="cost-item">
                    <div class="cost-item-label">Mano de Obra</div>
                    <div class="cost-item-value" style="color: #8b5cf6;">
                        <?php echo $total_costs > 0 ? number_format(($total_labor / $total_costs) * 100, 1) : 0; ?>%
                    </div>
                </div>
                <div class="cost-item">
                    <div class="cost-item-label">Overhead</div>
                    <div class="cost-item-value" style="color: #f59e0b;">
                        <?php echo $total_costs > 0 ? number_format(($total_overhead / $total_costs) * 100, 1) : 0; ?>%
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
