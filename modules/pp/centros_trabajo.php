<?php
/**
 * CONECTA ERP - CENTROS DE TRABAJO (WORK CENTERS)
 * Modulo Completo de Gestion de Centros de Trabajo
 * Sistema de capacidad, eficiencia, costos, recursos y mantenimiento
 */

require_once '../../includes/config.php';

// Verificar autenticacion
if (!isAuthenticated()) {
    header('Location: ../../login.php');
    exit;
}

$db = Database::getInstance();
$pdo = $db->getConnection();

// Procesar acciones
$action = $_GET['action'] ?? $_POST['action'] ?? 'list';
$center_id = $_GET['id'] ?? $_POST['center_id'] ?? null;
$message = '';
$error = '';

// === CREAR/ACTUALIZAR CENTRO ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($action, ['create', 'update'])) {
    try {
        $center_code = trim($_POST['center_code']);
        $center_name = trim($_POST['center_name']);
        $center_type = $_POST['center_type'];
        $department = trim($_POST['department'] ?? '');
        $capacity_hours = $_POST['capacity_hours'];
        $efficiency = $_POST['efficiency'];
        $cost_per_hour = $_POST['cost_per_hour'];
        $setup_cost = $_POST['setup_cost'];
        $labor_cost = $_POST['labor_cost'];
        $num_operators = $_POST['num_operators'];
        $status = $_POST['status'];
        $location = trim($_POST['location'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($action === 'create') {
            $stmt = $pdo->prepare("
                INSERT INTO pp_work_centers
                (center_code, center_name, center_type, department, capacity_hours, efficiency,
                 cost_per_hour, setup_cost, labor_cost, num_operators, status, location, description,
                 created_by, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            $stmt->execute([
                $center_code, $center_name, $center_type, $department, $capacity_hours, $efficiency,
                $cost_per_hour, $setup_cost, $labor_cost, $num_operators, $status, $location, $description,
                $_SESSION['user_id']
            ]);
            $center_id = $pdo->lastInsertId();
            $message = "Centro de trabajo creado exitosamente";
        } else {
            $stmt = $pdo->prepare("
                UPDATE pp_work_centers
                SET center_code = ?, center_name = ?, center_type = ?, department = ?,
                    capacity_hours = ?, efficiency = ?, cost_per_hour = ?, setup_cost = ?,
                    labor_cost = ?, num_operators = ?, status = ?, location = ?, description = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([
                $center_code, $center_name, $center_type, $department, $capacity_hours, $efficiency,
                $cost_per_hour, $setup_cost, $labor_cost, $num_operators, $status, $location, $description,
                $center_id
            ]);
            $message = "Centro de trabajo actualizado exitosamente";
        }

        $stmt->closeCursor();
    } catch (PDOException $e) {
        $error = "Error al guardar centro: " . $e->getMessage();
    }
}

// === ELIMINAR CENTRO ===
if ($action === 'delete' && $center_id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM pp_work_centers WHERE id = ?");
        $stmt->execute([$center_id]);
        $stmt->closeCursor();
        $message = "Centro de trabajo eliminado exitosamente";
        $center_id = null;
    } catch (PDOException $e) {
        $error = "Error al eliminar centro: " . $e->getMessage();
    }
}

// === AGREGAR RECURSO ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'add_resource') {
    try {
        $resource_type = $_POST['resource_type'];
        $resource_name = trim($_POST['resource_name']);
        $quantity = $_POST['quantity'];
        $resource_cost = $_POST['resource_cost'];
        $notes = trim($_POST['notes'] ?? '');

        $stmt = $pdo->prepare("
            INSERT INTO pp_work_center_resources
            (work_center_id, resource_type, resource_name, quantity, resource_cost, notes, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$center_id, $resource_type, $resource_name, $quantity, $resource_cost, $notes]);
        $stmt->closeCursor();
        $message = "Recurso agregado exitosamente";
    } catch (PDOException $e) {
        $error = "Error al agregar recurso: " . $e->getMessage();
    }
}

// === ELIMINAR RECURSO ===
if ($action === 'delete_resource') {
    $resource_id = $_POST['resource_id'] ?? null;
    if ($resource_id) {
        try {
            $stmt = $pdo->prepare("DELETE FROM pp_work_center_resources WHERE id = ?");
            $stmt->execute([$resource_id]);
            $stmt->closeCursor();
            $message = "Recurso eliminado exitosamente";
        } catch (PDOException $e) {
            $error = "Error al eliminar recurso: " . $e->getMessage();
        }
    }
}

// === REGISTRAR DISPONIBILIDAD ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'add_availability') {
    try {
        $shift_date = $_POST['shift_date'];
        $shift_type = $_POST['shift_type'];
        $available_hours = $_POST['available_hours'];
        $downtime_hours = $_POST['downtime_hours'];
        $downtime_reason = trim($_POST['downtime_reason'] ?? '');

        $stmt = $pdo->prepare("
            INSERT INTO pp_work_center_availability
            (work_center_id, shift_date, shift_type, available_hours, downtime_hours, downtime_reason, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$center_id, $shift_date, $shift_type, $available_hours, $downtime_hours, $downtime_reason]);
        $stmt->closeCursor();
        $message = "Disponibilidad registrada exitosamente";
    } catch (PDOException $e) {
        $error = "Error al registrar disponibilidad: " . $e->getMessage();
    }
}

// === OBTENER DATOS ===
$centers = [];
$current_center = null;
$resources = [];
$availability = [];

try {
    // Obtener centros de trabajo
    $stmt = $pdo->query("
        SELECT wc.*,
               COUNT(DISTINCT wcr.id) as resource_count,
               AVG(wca.available_hours) as avg_availability,
               SUM(wca.downtime_hours) as total_downtime
        FROM pp_work_centers wc
        LEFT JOIN pp_work_center_resources wcr ON wc.id = wcr.work_center_id
        LEFT JOIN pp_work_center_availability wca ON wc.id = wca.work_center_id
            AND wca.shift_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY wc.id
        ORDER BY wc.created_at DESC
    ");
    $centers = $stmt->fetchAll();
    $stmt->closeCursor();

    // Si hay un centro seleccionado, obtener sus datos
    if ($center_id) {
        $stmt = $pdo->prepare("SELECT * FROM pp_work_centers WHERE id = ?");
        $stmt->execute([$center_id]);
        $current_center = $stmt->fetch();
        $stmt->closeCursor();

        // Obtener recursos del centro
        $stmt = $pdo->prepare("
            SELECT * FROM pp_work_center_resources
            WHERE work_center_id = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$center_id]);
        $resources = $stmt->fetchAll();
        $stmt->closeCursor();

        // Obtener disponibilidad reciente
        $stmt = $pdo->prepare("
            SELECT * FROM pp_work_center_availability
            WHERE work_center_id = ?
            ORDER BY shift_date DESC, shift_type
            LIMIT 30
        ");
        $stmt->execute([$center_id]);
        $availability = $stmt->fetchAll();
        $stmt->closeCursor();
    }
} catch (PDOException $e) {
    $error = "Error al cargar datos: " . $e->getMessage();
}

// Calcular estadisticas
$total_centers = count($centers);
$active_centers = count(array_filter($centers, fn($c) => $c['status'] === 'active'));
$total_capacity = array_sum(array_column($centers, 'capacity_hours'));
$avg_efficiency = $total_centers > 0 ? array_sum(array_column($centers, 'efficiency')) / $total_centers : 0;
$total_resources = array_sum(array_column($centers, 'resource_count'));
$avg_availability = 0;
$availability_count = 0;
foreach ($centers as $c) {
    if ($c['avg_availability']) {
        $avg_availability += $c['avg_availability'];
        $availability_count++;
    }
}
$avg_availability = $availability_count > 0 ? $avg_availability / $availability_count : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Centros de Trabajo - CONECTA ERP</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f8fafc;
            color: #1e293b;
            line-height: 1.6;
        }

        .header {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 2rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .header h1 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .header p {
            opacity: 0.9;
            font-size: 0.95rem;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border-left: 4px solid #10b981;
        }

        .stat-card h3 {
            font-size: 0.875rem;
            color: #64748b;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .stat-card .value {
            font-size: 2rem;
            font-weight: 700;
            color: #1e293b;
        }

        .stat-card .label {
            font-size: 0.875rem;
            color: #64748b;
            margin-top: 0.25rem;
        }

        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f1f5f9;
        }

        .card-header h2 {
            font-size: 1.5rem;
            color: #1e293b;
            font-weight: 700;
        }

        .btn {
            padding: 0.625rem 1.25rem;
            border: none;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
        }

        .btn-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
        }

        .btn-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }

        .btn-secondary {
            background: #f1f5f9;
            color: #475569;
        }

        .btn-small {
            padding: 0.375rem 0.75rem;
            font-size: 0.813rem;
        }

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #f8fafc;
            padding: 0.75rem;
            text-align: left;
            font-weight: 600;
            color: #475569;
            font-size: 0.875rem;
            border-bottom: 2px solid #e2e8f0;
        }

        td {
            padding: 0.75rem;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.875rem;
        }

        tr:hover {
            background: #f8fafc;
        }

        .badge {
            padding: 0.25rem 0.75rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
        }

        .badge-active {
            background: #dcfce7;
            color: #166534;
        }

        .badge-inactive {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-maintenance {
            background: #fef3c7;
            color: #92400e;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #475569;
            font-size: 0.875rem;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.625rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.875rem;
            transition: all 0.2s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }

        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }

        .alert {
            padding: 1rem 1.25rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
            border-left: 4px solid #10b981;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }

        .tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 2rem;
            border-bottom: 2px solid #e2e8f0;
        }

        .tab {
            padding: 0.75rem 1.5rem;
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            color: #64748b;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .tab.active {
            color: #10b981;
            border-bottom-color: #10b981;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .info-item {
            background: #f8fafc;
            padding: 1rem;
            border-radius: 8px;
            border-left: 3px solid #10b981;
        }

        .info-item label {
            font-size: 0.813rem;
            color: #64748b;
            display: block;
            margin-bottom: 0.25rem;
        }

        .info-item .value {
            font-size: 1.125rem;
            font-weight: 600;
            color: #1e293b;
        }

        .resource-card {
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .resource-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .resource-info {
            flex: 1;
        }

        .resource-title {
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 0.25rem;
        }

        .resource-details {
            font-size: 0.813rem;
            color: #64748b;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Centros de Trabajo</h1>
        <p>Gestion de centros de trabajo, capacidad, eficiencia y recursos</p>
    </div>

    <div class="container">
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Estadisticas -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Centros</h3>
                <div class="value"><?php echo $total_centers; ?></div>
                <div class="label">centros registrados</div>
            </div>
            <div class="stat-card">
                <h3>Centros Activos</h3>
                <div class="value"><?php echo $active_centers; ?></div>
                <div class="label">operativos</div>
            </div>
            <div class="stat-card">
                <h3>Capacidad Total</h3>
                <div class="value"><?php echo number_format($total_capacity, 0); ?></div>
                <div class="label">horas/dia</div>
            </div>
            <div class="stat-card">
                <h3>Eficiencia Prom.</h3>
                <div class="value"><?php echo number_format($avg_efficiency, 1); ?>%</div>
                <div class="label">eficiencia global</div>
            </div>
            <div class="stat-card">
                <h3>Total Recursos</h3>
                <div class="value"><?php echo $total_resources; ?></div>
                <div class="label">recursos asignados</div>
            </div>
            <div class="stat-card">
                <h3>Disponibilidad</h3>
                <div class="value"><?php echo number_format($avg_availability, 1); ?></div>
                <div class="label">hrs/dia promedio</div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="tabs">
            <button class="tab <?php echo !$center_id ? 'active' : ''; ?>" onclick="showTab('list')">
                Lista de Centros
            </button>
            <button class="tab <?php echo $center_id && $action !== 'create' ? 'active' : ''; ?>" onclick="showTab('details')" id="detailsTab" <?php echo !$center_id ? 'style="display:none"' : ''; ?>>
                Detalles del Centro
            </button>
            <button class="tab <?php echo $action === 'create' ? 'active' : ''; ?>" onclick="showTab('form')">
                <?php echo $center_id && $action === 'update' ? 'Editar Centro' : 'Nuevo Centro'; ?>
            </button>
        </div>

        <!-- Tab: Lista de Centros -->
        <div id="listTab" class="tab-content <?php echo !$center_id ? 'active' : ''; ?>">
            <div class="card">
                <div class="card-header">
                    <h2>Centros de Trabajo</h2>
                    <button class="btn btn-primary" onclick="showTab('form')">+ Nuevo Centro</button>
                </div>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Codigo</th>
                                <th>Nombre</th>
                                <th>Tipo</th>
                                <th>Departamento</th>
                                <th>Capacidad</th>
                                <th>Eficiencia</th>
                                <th>Costo/Hr</th>
                                <th>Recursos</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($centers as $center): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($center['center_code']); ?></strong></td>
                                <td><?php echo htmlspecialchars($center['center_name']); ?></td>
                                <td><?php echo htmlspecialchars($center['center_type']); ?></td>
                                <td><?php echo htmlspecialchars($center['department']); ?></td>
                                <td><?php echo $center['capacity_hours']; ?> hrs/dia</td>
                                <td><?php echo $center['efficiency']; ?>%</td>
                                <td>$<?php echo number_format($center['cost_per_hour'], 2); ?></td>
                                <td><?php echo $center['resource_count']; ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $center['status'] === 'active' ? 'active' : ($center['status'] === 'maintenance' ? 'maintenance' : 'inactive'); ?>">
                                        <?php echo strtoupper($center['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="?action=view&id=<?php echo $center['id']; ?>" class="btn btn-secondary btn-small">Ver</a>
                                        <a href="?action=update&id=<?php echo $center['id']; ?>" class="btn btn-primary btn-small">Editar</a>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar este centro?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="center_id" value="<?php echo $center['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-small">Eliminar</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tab: Detalles del Centro -->
        <div id="detailsTab" class="tab-content <?php echo $center_id && $action !== 'create' && $action !== 'update' ? 'active' : ''; ?>">
            <?php if ($current_center): ?>
            <div class="card">
                <div class="card-header">
                    <h2><?php echo htmlspecialchars($current_center['center_name']); ?></h2>
                    <div class="action-buttons">
                        <a href="?action=update&id=<?php echo $current_center['id']; ?>" class="btn btn-primary">Editar Centro</a>
                        <a href="?" class="btn btn-secondary">Volver</a>
                    </div>
                </div>

                <div class="info-grid">
                    <div class="info-item">
                        <label>Codigo</label>
                        <div class="value"><?php echo htmlspecialchars($current_center['center_code']); ?></div>
                    </div>
                    <div class="info-item">
                        <label>Tipo</label>
                        <div class="value"><?php echo htmlspecialchars($current_center['center_type']); ?></div>
                    </div>
                    <div class="info-item">
                        <label>Departamento</label>
                        <div class="value"><?php echo htmlspecialchars($current_center['department']); ?></div>
                    </div>
                    <div class="info-item">
                        <label>Capacidad</label>
                        <div class="value"><?php echo $current_center['capacity_hours']; ?> hrs/dia</div>
                    </div>
                    <div class="info-item">
                        <label>Eficiencia</label>
                        <div class="value"><?php echo $current_center['efficiency']; ?>%</div>
                    </div>
                    <div class="info-item">
                        <label>Costo por Hora</label>
                        <div class="value">$<?php echo number_format($current_center['cost_per_hour'], 2); ?></div>
                    </div>
                    <div class="info-item">
                        <label>Costo Setup</label>
                        <div class="value">$<?php echo number_format($current_center['setup_cost'], 2); ?></div>
                    </div>
                    <div class="info-item">
                        <label>Costo Mano Obra</label>
                        <div class="value">$<?php echo number_format($current_center['labor_cost'], 2); ?></div>
                    </div>
                    <div class="info-item">
                        <label>Num. Operadores</label>
                        <div class="value"><?php echo $current_center['num_operators']; ?></div>
                    </div>
                    <div class="info-item">
                        <label>Ubicacion</label>
                        <div class="value"><?php echo htmlspecialchars($current_center['location']); ?></div>
                    </div>
                    <div class="info-item">
                        <label>Estado</label>
                        <div class="value">
                            <span class="badge badge-<?php echo $current_center['status'] === 'active' ? 'active' : ($current_center['status'] === 'maintenance' ? 'maintenance' : 'inactive'); ?>">
                                <?php echo strtoupper($current_center['status']); ?>
                            </span>
                        </div>
                    </div>
                </div>

                <?php if ($current_center['description']): ?>
                <div class="form-group">
                    <label>Descripcion</label>
                    <textarea readonly><?php echo htmlspecialchars($current_center['description']); ?></textarea>
                </div>
                <?php endif; ?>

                <!-- Recursos -->
                <div style="margin-top: 2rem; padding-top: 2rem; border-top: 2px solid #f1f5f9;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                        <h3 style="font-size: 1.25rem; color: #1e293b;">Recursos del Centro</h3>
                        <button class="btn btn-success btn-small" onclick="document.getElementById('addResourceForm').style.display='block'">+ Agregar Recurso</button>
                    </div>

                    <?php if (empty($resources)): ?>
                        <p style="color: #64748b; text-align: center; padding: 2rem;">No hay recursos asignados a este centro</p>
                    <?php else: ?>
                        <?php foreach ($resources as $resource): ?>
                        <div class="resource-card">
                            <div class="resource-header">
                                <div class="resource-info">
                                    <div class="resource-title">
                                        <?php echo htmlspecialchars($resource['resource_name']); ?>
                                    </div>
                                    <div class="resource-details">
                                        Tipo: <?php echo htmlspecialchars($resource['resource_type']); ?> |
                                        Cantidad: <?php echo $resource['quantity']; ?> |
                                        Costo: $<?php echo number_format($resource['resource_cost'], 2); ?>
                                        <?php if ($resource['notes']): ?>
                                            | <?php echo htmlspecialchars($resource['notes']); ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar recurso?');">
                                    <input type="hidden" name="action" value="delete_resource">
                                    <input type="hidden" name="center_id" value="<?php echo $center_id; ?>">
                                    <input type="hidden" name="resource_id" value="<?php echo $resource['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-small">Eliminar</button>
                                </form>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Disponibilidad -->
                <div style="margin-top: 2rem; padding-top: 2rem; border-top: 2px solid #f1f5f9;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                        <h3 style="font-size: 1.25rem; color: #1e293b;">Disponibilidad Reciente</h3>
                        <button class="btn btn-success btn-small" onclick="document.getElementById('addAvailabilityForm').style.display='block'">+ Registrar Disponibilidad</button>
                    </div>

                    <?php if (empty($availability)): ?>
                        <p style="color: #64748b; text-align: center; padding: 2rem;">No hay registros de disponibilidad</p>
                    <?php else: ?>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Turno</th>
                                        <th>Horas Disponibles</th>
                                        <th>Horas Inactividad</th>
                                        <th>Razon Inactividad</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($availability as $avail): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y', strtotime($avail['shift_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($avail['shift_type']); ?></td>
                                        <td><?php echo $avail['available_hours']; ?> hrs</td>
                                        <td><?php echo $avail['downtime_hours']; ?> hrs</td>
                                        <td><?php echo htmlspecialchars($avail['downtime_reason']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Formulario Agregar Recurso -->
            <div id="addResourceForm" style="display: none; margin-top: 1.5rem;">
                <div class="card">
                    <div class="card-header">
                        <h2>Agregar Recurso</h2>
                        <button class="btn btn-secondary" onclick="document.getElementById('addResourceForm').style.display='none'">Cancelar</button>
                    </div>

                    <form method="POST">
                        <input type="hidden" name="action" value="add_resource">
                        <input type="hidden" name="center_id" value="<?php echo $center_id; ?>">

                        <div class="form-grid">
                            <div class="form-group">
                                <label>Tipo de Recurso *</label>
                                <select name="resource_type" required>
                                    <option value="">Seleccionar...</option>
                                    <option value="Machine">Maquina</option>
                                    <option value="Tool">Herramienta</option>
                                    <option value="Equipment">Equipo</option>
                                    <option value="Fixture">Dispositivo</option>
                                    <option value="Other">Otro</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Nombre del Recurso *</label>
                                <input type="text" name="resource_name" required>
                            </div>
                            <div class="form-group">
                                <label>Cantidad *</label>
                                <input type="number" name="quantity" required min="1" value="1">
                            </div>
                            <div class="form-group">
                                <label>Costo del Recurso *</label>
                                <input type="number" name="resource_cost" required min="0" step="0.01" value="0">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Notas</label>
                            <textarea name="notes"></textarea>
                        </div>

                        <button type="submit" class="btn btn-success">Agregar Recurso</button>
                    </form>
                </div>
            </div>

            <!-- Formulario Registrar Disponibilidad -->
            <div id="addAvailabilityForm" style="display: none; margin-top: 1.5rem;">
                <div class="card">
                    <div class="card-header">
                        <h2>Registrar Disponibilidad</h2>
                        <button class="btn btn-secondary" onclick="document.getElementById('addAvailabilityForm').style.display='none'">Cancelar</button>
                    </div>

                    <form method="POST">
                        <input type="hidden" name="action" value="add_availability">
                        <input type="hidden" name="center_id" value="<?php echo $center_id; ?>">

                        <div class="form-grid">
                            <div class="form-group">
                                <label>Fecha *</label>
                                <input type="date" name="shift_date" required value="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Turno *</label>
                                <select name="shift_type" required>
                                    <option value="Morning">Mañana</option>
                                    <option value="Afternoon">Tarde</option>
                                    <option value="Night">Noche</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Horas Disponibles *</label>
                                <input type="number" name="available_hours" required min="0" step="0.01" value="8">
                            </div>
                            <div class="form-group">
                                <label>Horas Inactividad *</label>
                                <input type="number" name="downtime_hours" required min="0" step="0.01" value="0">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Razon de Inactividad</label>
                            <textarea name="downtime_reason"></textarea>
                        </div>

                        <button type="submit" class="btn btn-success">Registrar Disponibilidad</button>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Tab: Formulario -->
        <div id="formTab" class="tab-content <?php echo $action === 'create' || $action === 'update' ? 'active' : ''; ?>">
            <div class="card">
                <div class="card-header">
                    <h2><?php echo $center_id && $action === 'update' ? 'Editar Centro' : 'Nuevo Centro'; ?></h2>
                    <a href="?" class="btn btn-secondary">Cancelar</a>
                </div>

                <form method="POST">
                    <input type="hidden" name="action" value="<?php echo $center_id && $action === 'update' ? 'update' : 'create'; ?>">
                    <?php if ($center_id && $action === 'update'): ?>
                        <input type="hidden" name="center_id" value="<?php echo $center_id; ?>">
                    <?php endif; ?>

                    <div class="form-grid">
                        <div class="form-group">
                            <label>Codigo de Centro *</label>
                            <input type="text" name="center_code" required value="<?php echo $current_center ? htmlspecialchars($current_center['center_code']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label>Nombre del Centro *</label>
                            <input type="text" name="center_name" required value="<?php echo $current_center ? htmlspecialchars($current_center['center_name']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label>Tipo de Centro *</label>
                            <select name="center_type" required>
                                <option value="">Seleccionar...</option>
                                <option value="Machine" <?php echo ($current_center && $current_center['center_type'] === 'Machine') ? 'selected' : ''; ?>>Maquina</option>
                                <option value="Assembly" <?php echo ($current_center && $current_center['center_type'] === 'Assembly') ? 'selected' : ''; ?>>Ensamblaje</option>
                                <option value="Testing" <?php echo ($current_center && $current_center['center_type'] === 'Testing') ? 'selected' : ''; ?>>Pruebas</option>
                                <option value="Packaging" <?php echo ($current_center && $current_center['center_type'] === 'Packaging') ? 'selected' : ''; ?>>Empaque</option>
                                <option value="Quality" <?php echo ($current_center && $current_center['center_type'] === 'Quality') ? 'selected' : ''; ?>>Calidad</option>
                                <option value="Other" <?php echo ($current_center && $current_center['center_type'] === 'Other') ? 'selected' : ''; ?>>Otro</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Departamento</label>
                            <input type="text" name="department" value="<?php echo $current_center ? htmlspecialchars($current_center['department']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label>Capacidad (hrs/dia) *</label>
                            <input type="number" name="capacity_hours" required min="0" step="0.01" value="<?php echo $current_center ? $current_center['capacity_hours'] : '8'; ?>">
                        </div>
                        <div class="form-group">
                            <label>Eficiencia (%) *</label>
                            <input type="number" name="efficiency" required min="0" max="100" step="0.01" value="<?php echo $current_center ? $current_center['efficiency'] : '85'; ?>">
                        </div>
                        <div class="form-group">
                            <label>Costo por Hora *</label>
                            <input type="number" name="cost_per_hour" required min="0" step="0.01" value="<?php echo $current_center ? $current_center['cost_per_hour'] : '0'; ?>">
                        </div>
                        <div class="form-group">
                            <label>Costo de Setup *</label>
                            <input type="number" name="setup_cost" required min="0" step="0.01" value="<?php echo $current_center ? $current_center['setup_cost'] : '0'; ?>">
                        </div>
                        <div class="form-group">
                            <label>Costo Mano de Obra *</label>
                            <input type="number" name="labor_cost" required min="0" step="0.01" value="<?php echo $current_center ? $current_center['labor_cost'] : '0'; ?>">
                        </div>
                        <div class="form-group">
                            <label>Numero de Operadores *</label>
                            <input type="number" name="num_operators" required min="0" value="<?php echo $current_center ? $current_center['num_operators'] : '1'; ?>">
                        </div>
                        <div class="form-group">
                            <label>Ubicacion</label>
                            <input type="text" name="location" value="<?php echo $current_center ? htmlspecialchars($current_center['location']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label>Estado *</label>
                            <select name="status" required>
                                <option value="active" <?php echo ($current_center && $current_center['status'] === 'active') ? 'selected' : ''; ?>>Activo</option>
                                <option value="inactive" <?php echo ($current_center && $current_center['status'] === 'inactive') ? 'selected' : ''; ?>>Inactivo</option>
                                <option value="maintenance" <?php echo ($current_center && $current_center['status'] === 'maintenance') ? 'selected' : ''; ?>>Mantenimiento</option>
            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Descripcion</label>
                        <textarea name="description"><?php echo $current_center ? htmlspecialchars($current_center['description']) : ''; ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-success">
                        <?php echo $center_id && $action === 'update' ? 'Actualizar Centro' : 'Crear Centro'; ?>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function showTab(tabName) {
            // Ocultar todos los tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });

            // Mostrar el tab seleccionado
            if (tabName === 'list') {
                document.getElementById('listTab').classList.add('active');
                document.querySelectorAll('.tab')[0].classList.add('active');
            } else if (tabName === 'details') {
                document.getElementById('detailsTab').classList.add('active');
                document.getElementById('detailsTab').style.display = 'block';
                document.querySelectorAll('.tab')[1].classList.add('active');
            } else if (tabName === 'form') {
                document.getElementById('formTab').classList.add('active');
                document.querySelectorAll('.tab')[2].classList.add('active');
            }
        }
    </script>
</body>
</html>
