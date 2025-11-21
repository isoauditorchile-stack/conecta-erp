<?php
/**
 * CONECTA ERP - CALIDAD PRODUCCION (QUALITY PRODUCTION)
 * Modulo Completo de Calidad de Produccion
 * Sistema de inspecciones no conformidades acciones correctivas y metricas de calidad
 */

require_once '../../includes/config.php';

if (!isAuthenticated()) {
    header('Location: ../../login.php');
    exit;
}

$db = Database::getInstance();
$pdo = $db->getConnection();

$action = $_GET['action'] ?? $_POST['action'] ?? 'dashboard';
$inspection_id = $_GET['id'] ?? $_POST['inspection_id'] ?? null;
$message = '';
$error = '';

// === CREAR INSPECCION ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create_inspection') {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO pp_quality_inspections
            (order_id, inspection_type, inspection_date, inspector_id, quantity_inspected,
             quantity_approved, quantity_rejected, result, notes, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $_POST['order_id'], $_POST['inspection_type'], $_POST['inspection_date'],
            $_SESSION['user_id'], $_POST['quantity_inspected'], $_POST['quantity_approved'],
            $_POST['quantity_rejected'], $_POST['result'], trim($_POST['notes'] ?? '')
        ]);
        $stmt->closeCursor();
        $message = "Inspeccion registrada exitosamente";
    } catch (PDOException $e) {
        $error = "Error al crear inspeccion: " . $e->getMessage();
    }
}

// === REGISTRAR NO CONFORMIDAD ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create_ncr') {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO pp_non_conformities
            (inspection_id, ncr_number, description, severity, root_cause, corrective_action, status, created_by, created_at)
            VALUES (?, ?, ?, ?, ?, ?, 'open', ?, NOW())
        ");
        $stmt->execute([
            $_POST['inspection_id'], $_POST['ncr_number'], trim($_POST['description']),
            $_POST['severity'], trim($_POST['root_cause'] ?? ''), trim($_POST['corrective_action'] ?? ''),
            $_SESSION['user_id']
        ]);
        $stmt->closeCursor();
        $message = "No conformidad registrada exitosamente";
    } catch (PDOException $e) {
        $error = "Error al registrar no conformidad: " . $e->getMessage();
    }
}

// === OBTENER DATOS ===
$inspections = [];
$ncrs = [];
$production_orders = [];

try {
    // Ordenes de produccion
    $stmt = $pdo->query("SELECT id, order_number FROM pp_production_orders WHERE status IN ('in_progress', 'completed') ORDER BY order_number DESC LIMIT 100");
    $production_orders = $stmt->fetchAll();
    $stmt->closeCursor();

    // Inspecciones
    $stmt = $pdo->query("
        SELECT qi.*, po.order_number, u.username as inspector_name
        FROM pp_quality_inspections qi
        INNER JOIN pp_production_orders po ON qi.order_id = po.id
        LEFT JOIN users u ON qi.inspector_id = u.id
        ORDER BY qi.inspection_date DESC
        LIMIT 100
    ");
    $inspections = $stmt->fetchAll();
    $stmt->closeCursor();

    // No conformidades
    $stmt = $pdo->query("
        SELECT nc.*, qi.inspection_type, po.order_number
        FROM pp_non_conformities nc
        LEFT JOIN pp_quality_inspections qi ON nc.inspection_id = qi.id
        LEFT JOIN pp_production_orders po ON qi.order_id = po.id
        ORDER BY nc.created_at DESC
        LIMIT 50
    ");
    $ncrs = $stmt->fetchAll();
    $stmt->closeCursor();
} catch (PDOException $e) {
    $error = "Error al cargar datos: " . $e->getMessage();
}

$total_inspections = count($inspections);
$passed_inspections = count(array_filter($inspections, fn($i) => $i['result'] === 'Passed'));
$total_rejected = array_sum(array_column($inspections, 'quantity_rejected'));
$total_inspected = array_sum(array_column($inspections, 'quantity_inspected'));
$quality_rate = $total_inspected > 0 ? (($total_inspected - $total_rejected) / $total_inspected) * 100 : 0;
$open_ncrs = count(array_filter($ncrs, fn($n) => $n['status'] === 'open'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calidad Produccion - CONECTA ERP</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f8fafc; color: #1e293b; line-height: 1.6; }
        .header { background: linear-gradient(135deg, #ec4899 0%, #db2777 100%); color: white; padding: 2rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .header h1 { font-size: 2rem; font-weight: 700; margin-bottom: 0.5rem; }
        .header p { opacity: 0.9; font-size: 0.95rem; }
        .container { max-width: 1400px; margin: 0 auto; padding: 2rem; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-left: 4px solid #ec4899; }
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
        .btn-small { padding: 0.375rem 0.75rem; font-size: 0.813rem; }
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f8fafc; padding: 0.75rem; text-align: left; font-weight: 600; color: #475569; font-size: 0.875rem; border-bottom: 2px solid #e2e8f0; }
        td { padding: 0.75rem; border-bottom: 1px solid #f1f5f9; font-size: 0.875rem; }
        tr:hover { background: #f8fafc; }
        .badge { padding: 0.25rem 0.75rem; border-radius: 6px; font-size: 0.75rem; font-weight: 600; display: inline-block; }
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; color: #475569; font-size: 0.875rem; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 0.625rem; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 0.875rem; }
        .alert { padding: 1rem 1.25rem; border-radius: 8px; margin-bottom: 1.5rem; font-weight: 500; }
        .alert-success { background: #dcfce7; color: #166534; border-left: 4px solid #10b981; }
        .alert-error { background: #fee2e2; color: #991b1b; border-left: 4px solid #ef4444; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Calidad Produccion</h1>
        <p>Inspecciones no conformidades acciones correctivas y metricas de calidad</p>
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
                <h3>Tasa de Calidad</h3>
                <div class="value"><?php echo number_format($quality_rate, 1); ?>%</div>
                <div class="label">productos aprobados</div>
            </div>
            <div class="stat-card">
                <h3>Inspecciones</h3>
                <div class="value"><?php echo $total_inspections; ?></div>
                <div class="label"><?php echo $passed_inspections; ?> aprobadas</div>
            </div>
            <div class="stat-card">
                <h3>Rechazos</h3>
                <div class="value"><?php echo $total_rejected; ?></div>
                <div class="label">unidades rechazadas</div>
            </div>
            <div class="stat-card">
                <h3>NCRs Abiertas</h3>
                <div class="value"><?php echo $open_ncrs; ?></div>
                <div class="label">no conformidades</div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>Inspecciones de Calidad</h2>
                <button class="btn btn-success" onclick="document.getElementById('inspectionForm').style.display='block'">+ Nueva Inspeccion</button>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Orden</th>
                            <th>Tipo</th>
                            <th>Inspeccionado</th>
                            <th>Aprobado</th>
                            <th>Rechazado</th>
                            <th>Resultado</th>
                            <th>Inspector</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inspections as $insp): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($insp['inspection_date'])); ?></td>
                            <td><?php echo htmlspecialchars($insp['order_number']); ?></td>
                            <td><?php echo htmlspecialchars($insp['inspection_type']); ?></td>
                            <td><?php echo $insp['quantity_inspected']; ?></td>
                            <td><?php echo $insp['quantity_approved']; ?></td>
                            <td><?php echo $insp['quantity_rejected']; ?></td>
                            <td>
                                <span class="badge badge-<?php echo $insp['result'] === 'Passed' ? 'success' : 'danger'; ?>">
                                    <?php echo strtoupper($insp['result']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($insp['inspector_name'] ?? 'N/A'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>No Conformidades (NCRs)</h2>
                <button class="btn btn-success" onclick="document.getElementById('ncrForm').style.display='block'">+ Registrar NCR</button>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>NCR</th>
                            <th>Orden</th>
                            <th>Descripcion</th>
                            <th>Severidad</th>
                            <th>Causa Raiz</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ncrs as $ncr): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($ncr['ncr_number']); ?></strong></td>
                            <td><?php echo htmlspecialchars($ncr['order_number'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($ncr['description']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $ncr['severity'] === 'Critical' ? 'danger' : ($ncr['severity'] === 'Major' ? 'warning' : 'success'); ?>">
                                    <?php echo strtoupper($ncr['severity']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($ncr['root_cause'] ?? '-'); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $ncr['status'] === 'closed' ? 'success' : 'warning'; ?>">
                                    <?php echo strtoupper($ncr['status']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="inspectionForm" style="display: none;">
            <div class="card">
                <div class="card-header">
                    <h2>Nueva Inspeccion</h2>
                    <button class="btn btn-secondary" onclick="document.getElementById('inspectionForm').style.display='none'">Cancelar</button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="create_inspection">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Orden de Produccion *</label>
                            <select name="order_id" required>
                                <option value="">Seleccionar...</option>
                                <?php foreach ($production_orders as $order): ?>
                                <option value="<?php echo $order['id']; ?>"><?php echo htmlspecialchars($order['order_number']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Tipo de Inspeccion *</label>
                            <select name="inspection_type" required>
                                <option value="Receiving">Recepcion</option>
                                <option value="In-Process">En Proceso</option>
                                <option value="Final">Final</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Fecha *</label>
                            <input type="date" name="inspection_date" required value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="form-group">
                            <label>Cantidad Inspeccionada *</label>
                            <input type="number" name="quantity_inspected" required min="0">
                        </div>
                        <div class="form-group">
                            <label>Cantidad Aprobada *</label>
                            <input type="number" name="quantity_approved" required min="0">
                        </div>
                        <div class="form-group">
                            <label>Cantidad Rechazada *</label>
                            <input type="number" name="quantity_rejected" required min="0" value="0">
                        </div>
                        <div class="form-group">
                            <label>Resultado *</label>
                            <select name="result" required>
                                <option value="Passed">Aprobado</option>
                                <option value="Failed">Rechazado</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Notas</label>
                        <textarea name="notes"></textarea>
                    </div>
                    <button type="submit" class="btn btn-success">Registrar Inspeccion</button>
                </form>
            </div>
        </div>

        <div id="ncrForm" style="display: none;">
            <div class="card">
                <div class="card-header">
                    <h2>Registrar No Conformidad</h2>
                    <button class="btn btn-secondary" onclick="document.getElementById('ncrForm').style.display='none'">Cancelar</button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="create_ncr">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Numero NCR *</label>
                            <input type="text" name="ncr_number" required value="NCR-<?php echo date('Ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT); ?>">
                        </div>
                        <div class="form-group">
                            <label>Inspeccion Relacionada</label>
                            <select name="inspection_id">
                                <option value="">Seleccionar...</option>
                                <?php foreach ($inspections as $insp): ?>
                                <option value="<?php echo $insp['id']; ?>"><?php echo htmlspecialchars($insp['order_number'] . ' - ' . date('d/m/Y', strtotime($insp['inspection_date']))); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Severidad *</label>
                            <select name="severity" required>
                                <option value="Minor">Menor</option>
                                <option value="Major">Mayor</option>
                                <option value="Critical">Critica</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Descripcion *</label>
                        <textarea name="description" required></textarea>
                    </div>
                    <div class="form-group">
                        <label>Causa Raiz</label>
                        <textarea name="root_cause"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Accion Correctiva</label>
                        <textarea name="corrective_action"></textarea>
                    </div>
                    <button type="submit" class="btn btn-success">Registrar NCR</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
