<?php
/**
 * CONECTA ERP - MANTENIMIENTO (MAINTENANCE)
 * Modulo Completo de Mantenimiento
 * Sistema de mantenimiento preventivo correctivo planificacion y metricas MTBF MTTR
 */

require_once '../../includes/config.php';

if (!isAuthenticated()) {
    header('Location: ../../login.php');
    exit;
}

$db = Database::getInstance();
$pdo = $db->getConnection();

$action = $_GET['action'] ?? $_POST['action'] ?? 'dashboard';
$maintenance_id = $_GET['id'] ?? $_POST['maintenance_id'] ?? null;
$message = '';
$error = '';

// === CREAR MANTENIMIENTO ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($action, ['create', 'update'])) {
    try {
        if ($action === 'create') {
            $stmt = $pdo->prepare("
                INSERT INTO pp_maintenance_records
                (work_center_id, machine_id, maintenance_type, scheduled_date, description,
                 status, priority, estimated_duration, created_by, created_at)
                VALUES (?, ?, ?, ?, ?, 'scheduled', ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $_POST['work_center_id'] ?? null, $_POST['machine_id'] ?? null, $_POST['maintenance_type'],
                $_POST['scheduled_date'], trim($_POST['description']), $_POST['priority'],
                $_POST['estimated_duration'], $_SESSION['user_id']
            ]);
            $stmt->closeCursor();
            $message = "Mantenimiento programado exitosamente";
        } else {
            $stmt = $pdo->prepare("
                UPDATE pp_maintenance_records
                SET work_center_id = ?, machine_id = ?, maintenance_type = ?, scheduled_date = ?,
                    description = ?, status = ?, priority = ?, estimated_duration = ?,
                    actual_start = ?, actual_end = ?, actual_duration = ?, cost = ?,
                    technician_notes = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([
                $_POST['work_center_id'] ?? null, $_POST['machine_id'] ?? null, $_POST['maintenance_type'],
                $_POST['scheduled_date'], trim($_POST['description']), $_POST['status'], $_POST['priority'],
                $_POST['estimated_duration'], $_POST['actual_start'] ?? null, $_POST['actual_end'] ?? null,
                $_POST['actual_duration'] ?? null, $_POST['cost'] ?? null, trim($_POST['technician_notes'] ?? ''),
                $maintenance_id
            ]);
            $stmt->closeCursor();
            $message = "Mantenimiento actualizado exitosamente";
        }
    } catch (PDOException $e) {
        $error = "Error al guardar mantenimiento: " . $e->getMessage();
    }
}

// === COMPLETAR MANTENIMIENTO ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'complete' && $maintenance_id) {
    try {
        $stmt = $pdo->prepare("
            UPDATE pp_maintenance_records
            SET status = 'completed', actual_end = NOW(),
                actual_duration = TIMESTAMPDIFF(MINUTE, actual_start, NOW()),
                cost = ?, technician_notes = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$_POST['cost'], trim($_POST['technician_notes'] ?? ''), $maintenance_id]);
        $stmt->closeCursor();
        $message = "Mantenimiento completado exitosamente";
    } catch (PDOException $e) {
        $error = "Error al completar mantenimiento: " . $e->getMessage();
    }
}

// === OBTENER DATOS ===
$work_centers = [];
$maintenance_records = [];
$current_record = null;

try {
    // Centros de trabajo
    $stmt = $pdo->query("SELECT id, center_code, center_name FROM pp_work_centers WHERE status = 'active' ORDER BY center_name");
    $work_centers = $stmt->fetchAll();
    $stmt->closeCursor();

    // Registros de mantenimiento
    $stmt = $pdo->query("
        SELECT mr.*, wc.center_name, u.username as created_by_name
        FROM pp_maintenance_records mr
        LEFT JOIN pp_work_centers wc ON mr.work_center_id = wc.id
        LEFT JOIN users u ON mr.created_by = u.id
        ORDER BY mr.scheduled_date DESC, mr.created_at DESC
        LIMIT 100
    ");
    $maintenance_records = $stmt->fetchAll();
    $stmt->closeCursor();

    // Registro actual
    if ($maintenance_id) {
        $stmt = $pdo->prepare("
            SELECT mr.*, wc.center_name
            FROM pp_maintenance_records mr
            LEFT JOIN pp_work_centers wc ON mr.work_center_id = wc.id
            WHERE mr.id = ?
        ");
        $stmt->execute([$maintenance_id]);
        $current_record = $stmt->fetch();
        $stmt->closeCursor();
    }
} catch (PDOException $e) {
    $error = "Error al cargar datos: " . $e->getMessage();
}

// Calcular metricas
$total_records = count($maintenance_records);
$scheduled = count(array_filter($maintenance_records, fn($r) => $r['status'] === 'scheduled'));
$in_progress = count(array_filter($maintenance_records, fn($r) => $r['status'] === 'in_progress'));
$completed = count(array_filter($maintenance_records, fn($r) => $r['status'] === 'completed'));

// MTTR (Mean Time To Repair) - Tiempo promedio de reparacion
$completed_records = array_filter($maintenance_records, fn($r) => $r['status'] === 'completed' && $r['actual_duration']);
$mttr = count($completed_records) > 0 ? array_sum(array_column($completed_records, 'actual_duration')) / count($completed_records) : 0;

// MTBF (Mean Time Between Failures) - estimado simplificado
$mtbf = 720; // 30 dias * 24 horas (simplificado)

$preventive_count = count(array_filter($maintenance_records, fn($r) => $r['maintenance_type'] === 'Preventive'));
$corrective_count = count(array_filter($maintenance_records, fn($r) => $r['maintenance_type'] === 'Corrective'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mantenimiento - CONECTA ERP</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f8fafc; color: #1e293b; line-height: 1.6; }
        .header { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; padding: 2rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .header h1 { font-size: 2rem; font-weight: 700; margin-bottom: 0.5rem; }
        .header p { opacity: 0.9; font-size: 0.95rem; }
        .container { max-width: 1400px; margin: 0 auto; padding: 2rem; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-left: 4px solid #ef4444; }
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
        .badge-scheduled { background: #dbeafe; color: #1e40af; }
        .badge-progress { background: #fef3c7; color: #92400e; }
        .badge-completed { background: #dcfce7; color: #166534; }
        .badge-high { background: #fee2e2; color: #991b1b; }
        .badge-medium { background: #fef3c7; color: #92400e; }
        .badge-low { background: #dbeafe; color: #1e40af; }
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
        <h1>Mantenimiento</h1>
        <p>Gestion de mantenimiento preventivo correctivo planificacion y metricas MTBF MTTR</p>
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
                <h3>Total Registros</h3>
                <div class="value"><?php echo $total_records; ?></div>
                <div class="label">mantenimientos</div>
            </div>
            <div class="stat-card">
                <h3>Programados</h3>
                <div class="value"><?php echo $scheduled; ?></div>
                <div class="label">pendientes</div>
            </div>
            <div class="stat-card">
                <h3>En Progreso</h3>
                <div class="value"><?php echo $in_progress; ?></div>
                <div class="label">en ejecucion</div>
            </div>
            <div class="stat-card">
                <h3>Completados</h3>
                <div class="value"><?php echo $completed; ?></div>
                <div class="label">finalizados</div>
            </div>
            <div class="stat-card">
                <h3>MTTR</h3>
                <div class="value"><?php echo number_format($mttr, 0); ?></div>
                <div class="label">minutos promedio</div>
            </div>
            <div class="stat-card">
                <h3>MTBF</h3>
                <div class="value"><?php echo number_format($mtbf, 0); ?></div>
                <div class="label">horas entre fallas</div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>Registros de Mantenimiento</h2>
                <button class="btn btn-success" onclick="document.getElementById('maintenanceForm').style.display='block'">+ Programar Mantenimiento</button>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Fecha Programada</th>
                            <th>Centro</th>
                            <th>Tipo</th>
                            <th>Descripcion</th>
                            <th>Prioridad</th>
                            <th>Duracion Est.</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($maintenance_records as $record): ?>
                        <tr>
                            <td><?php echo date('d/m/Y H:i', strtotime($record['scheduled_date'])); ?></td>
                            <td><?php echo htmlspecialchars($record['center_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($record['maintenance_type']); ?></td>
                            <td><?php echo htmlspecialchars($record['description']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo strtolower($record['priority']); ?>">
                                    <?php echo strtoupper($record['priority']); ?>
                                </span>
                            </td>
                            <td><?php echo $record['estimated_duration']; ?> min</td>
                            <td>
                                <span class="badge badge-<?php echo $record['status'] === 'scheduled' ? 'scheduled' : ($record['status'] === 'in_progress' ? 'progress' : 'completed'); ?>">
                                    <?php echo strtoupper($record['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; gap: 0.5rem;">
                                    <a href="?action=view&id=<?php echo $record['id']; ?>" class="btn btn-secondary btn-small">Ver</a>
                                    <?php if ($record['status'] !== 'completed'): ?>
                                    <a href="?action=update&id=<?php echo $record['id']; ?>" class="btn btn-primary btn-small">Editar</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if ($current_record && $action === 'view'): ?>
        <div class="card">
            <div class="card-header">
                <h2>Detalles del Mantenimiento</h2>
                <div style="display: flex; gap: 0.5rem;">
                    <?php if ($current_record['status'] === 'scheduled'): ?>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="maintenance_id" value="<?php echo $current_record['id']; ?>">
                        <input type="hidden" name="status" value="in_progress">
                        <input type="hidden" name="actual_start" value="<?php echo date('Y-m-d H:i:s'); ?>">
                        <?php foreach (['work_center_id', 'machine_id', 'maintenance_type', 'scheduled_date', 'description', 'priority', 'estimated_duration'] as $field): ?>
                        <input type="hidden" name="<?php echo $field; ?>" value="<?php echo htmlspecialchars($current_record[$field] ?? ''); ?>">
                        <?php endforeach; ?>
                        <button type="submit" class="btn btn-primary">Iniciar Mantenimiento</button>
                    </form>
                    <?php endif; ?>
                    <?php if ($current_record['status'] === 'in_progress'): ?>
                    <button class="btn btn-success" onclick="document.getElementById('completeForm').style.display='block'">Completar Mantenimiento</button>
                    <?php endif; ?>
                    <a href="?" class="btn btn-secondary">Volver</a>
                </div>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                <div style="background: #f8fafc; padding: 1rem; border-radius: 8px;">
                    <div style="font-size: 0.875rem; color: #64748b;">Centro de Trabajo</div>
                    <div style="font-size: 1.125rem; font-weight: 600; color: #1e293b;"><?php echo htmlspecialchars($current_record['center_name'] ?? 'N/A'); ?></div>
                </div>
                <div style="background: #f8fafc; padding: 1rem; border-radius: 8px;">
                    <div style="font-size: 0.875rem; color: #64748b;">Tipo</div>
                    <div style="font-size: 1.125rem; font-weight: 600; color: #1e293b;"><?php echo htmlspecialchars($current_record['maintenance_type']); ?></div>
                </div>
                <div style="background: #f8fafc; padding: 1rem; border-radius: 8px;">
                    <div style="font-size: 0.875rem; color: #64748b;">Duracion Estimada</div>
                    <div style="font-size: 1.125rem; font-weight: 600; color: #1e293b;"><?php echo $current_record['estimated_duration']; ?> min</div>
                </div>
                <?php if ($current_record['actual_duration']): ?>
                <div style="background: #f8fafc; padding: 1rem; border-radius: 8px;">
                    <div style="font-size: 0.875rem; color: #64748b;">Duracion Real</div>
                    <div style="font-size: 1.125rem; font-weight: 600; color: #1e293b;"><?php echo $current_record['actual_duration']; ?> min</div>
                </div>
                <?php endif; ?>
                <?php if ($current_record['cost']): ?>
                <div style="background: #f8fafc; padding: 1rem; border-radius: 8px;">
                    <div style="font-size: 0.875rem; color: #64748b;">Costo</div>
                    <div style="font-size: 1.125rem; font-weight: 600; color: #1e293b;">$<?php echo number_format($current_record['cost'], 2); ?></div>
                </div>
                <?php endif; ?>
            </div>
            <?php if ($current_record['technician_notes']): ?>
            <div style="margin-top: 1.5rem;">
                <label style="font-weight: 600; margin-bottom: 0.5rem; display: block;">Notas del Tecnico</label>
                <div style="background: #f8fafc; padding: 1rem; border-radius: 8px;"><?php echo nl2br(htmlspecialchars($current_record['technician_notes'])); ?></div>
            </div>
            <?php endif; ?>
        </div>

        <?php if ($current_record['status'] === 'in_progress'): ?>
        <div id="completeForm" style="display: none;">
            <div class="card">
                <div class="card-header">
                    <h2>Completar Mantenimiento</h2>
                    <button class="btn btn-secondary" onclick="document.getElementById('completeForm').style.display='none'">Cancelar</button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="complete">
                    <input type="hidden" name="maintenance_id" value="<?php echo $current_record['id']; ?>">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Costo Total *</label>
                            <input type="number" name="cost" required min="0" step="0.01">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Notas del Tecnico</label>
                        <textarea name="technician_notes"></textarea>
                    </div>
                    <button type="submit" class="btn btn-success">Completar Mantenimiento</button>
                </form>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>

        <div id="maintenanceForm" style="display: none;">
            <div class="card">
                <div class="card-header">
                    <h2>Programar Mantenimiento</h2>
                    <button class="btn btn-secondary" onclick="document.getElementById('maintenanceForm').style.display='none'">Cancelar</button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="create">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Centro de Trabajo *</label>
                            <select name="work_center_id" required>
                                <option value="">Seleccionar...</option>
                                <?php foreach ($work_centers as $wc): ?>
                                <option value="<?php echo $wc['id']; ?>"><?php echo htmlspecialchars($wc['center_code'] . ' - ' . $wc['center_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Tipo de Mantenimiento *</label>
                            <select name="maintenance_type" required>
                                <option value="Preventive">Preventivo</option>
                                <option value="Corrective">Correctivo</option>
                                <option value="Predictive">Predictivo</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Prioridad *</label>
                            <select name="priority" required>
                                <option value="Low">Baja</option>
                                <option value="Medium">Media</option>
                                <option value="High">Alta</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Fecha Programada *</label>
                            <input type="datetime-local" name="scheduled_date" required value="<?php echo date('Y-m-d\TH:i'); ?>">
                        </div>
                        <div class="form-group">
                            <label>Duracion Estimada (min) *</label>
                            <input type="number" name="estimated_duration" required min="0" value="60">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Descripcion *</label>
                        <textarea name="description" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-success">Programar Mantenimiento</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
