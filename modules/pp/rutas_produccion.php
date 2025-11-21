<?php
/**
 * CONECTA ERP - RUTAS DE PRODUCCION (PRODUCTION ROUTES)
 * Modulo Completo de Gestion de Rutas de Produccion
 * Sistema de operaciones, secuencias, centros de trabajo y tiempos estandar
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
$route_id = $_GET['id'] ?? $_POST['route_id'] ?? null;
$message = '';
$error = '';

// === CREAR/ACTUALIZAR RUTA ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($action, ['create', 'update'])) {
    try {
        $route_code = trim($_POST['route_code']);
        $route_name = trim($_POST['route_name']);
        $product_id = $_POST['product_id'] ?? null;
        $version = trim($_POST['version']);
        $status = $_POST['status'];
        $description = trim($_POST['description'] ?? '');

        if ($action === 'create') {
            $stmt = $pdo->prepare("
                INSERT INTO pp_routings (route_code, route_name, product_id, version, status, description,
                    created_by, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            $stmt->execute([$route_code, $route_name, $product_id, $version, $status, $description, $_SESSION['user_id']]);
            $route_id = $pdo->lastInsertId();
            $message = "Ruta creada exitosamente";
        } else {
            $stmt = $pdo->prepare("
                UPDATE pp_routings
                SET route_code = ?, route_name = ?, product_id = ?, version = ?,
                    status = ?, description = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$route_code, $route_name, $product_id, $version, $status, $description, $route_id]);
            $message = "Ruta actualizada exitosamente";
        }

        $stmt->closeCursor();
    } catch (PDOException $e) {
        $error = "Error al guardar ruta: " . $e->getMessage();
    }
}

// === ELIMINAR RUTA ===
if ($action === 'delete' && $route_id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM pp_routings WHERE id = ?");
        $stmt->execute([$route_id]);
        $stmt->closeCursor();
        $message = "Ruta eliminada exitosamente";
        $route_id = null;
    } catch (PDOException $e) {
        $error = "Error al eliminar ruta: " . $e->getMessage();
    }
}

// === AGREGAR OPERACION ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'add_operation') {
    try {
        $operation_seq = $_POST['operation_seq'];
        $operation_code = trim($_POST['operation_code']);
        $operation_name = trim($_POST['operation_name']);
        $work_center_id = $_POST['work_center_id'];
        $setup_time = $_POST['setup_time'];
        $run_time = $_POST['run_time'];
        $operation_description = trim($_POST['operation_description'] ?? '');
        $tools_required = trim($_POST['tools_required'] ?? '');

        $stmt = $pdo->prepare("
            INSERT INTO pp_routing_operations
            (routing_id, operation_seq, operation_code, operation_name, work_center_id,
             setup_time, run_time, operation_description, tools_required, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $route_id, $operation_seq, $operation_code, $operation_name, $work_center_id,
            $setup_time, $run_time, $operation_description, $tools_required
        ]);
        $stmt->closeCursor();
        $message = "Operacion agregada exitosamente";
    } catch (PDOException $e) {
        $error = "Error al agregar operacion: " . $e->getMessage();
    }
}

// === ELIMINAR OPERACION ===
if ($action === 'delete_operation') {
    $operation_id = $_POST['operation_id'] ?? null;
    if ($operation_id) {
        try {
            $stmt = $pdo->prepare("DELETE FROM pp_routing_operations WHERE id = ?");
            $stmt->execute([$operation_id]);
            $stmt->closeCursor();
            $message = "Operacion eliminada exitosamente";
        } catch (PDOException $e) {
            $error = "Error al eliminar operacion: " . $e->getMessage();
        }
    }
}

// === OBTENER DATOS ===
$routes = [];
$current_route = null;
$operations = [];
$products = [];
$work_centers = [];

try {
    // Obtener productos
    $stmt = $pdo->query("SELECT id, product_code, product_name FROM products WHERE status = 'active' ORDER BY product_name");
    $products = $stmt->fetchAll();
    $stmt->closeCursor();

    // Obtener centros de trabajo
    $stmt = $pdo->query("SELECT id, center_code, center_name FROM pp_work_centers WHERE status = 'active' ORDER BY center_name");
    $work_centers = $stmt->fetchAll();
    $stmt->closeCursor();

    // Obtener rutas
    $stmt = $pdo->query("
        SELECT r.*, p.product_code, p.product_name,
               COUNT(DISTINCT ro.id) as operation_count,
               SUM(ro.setup_time + ro.run_time) as total_time
        FROM pp_routings r
        LEFT JOIN products p ON r.product_id = p.id
        LEFT JOIN pp_routing_operations ro ON r.id = ro.routing_id
        GROUP BY r.id
        ORDER BY r.created_at DESC
    ");
    $routes = $stmt->fetchAll();
    $stmt->closeCursor();

    // Si hay una ruta seleccionada, obtener sus datos
    if ($route_id) {
        $stmt = $pdo->prepare("
            SELECT r.*, p.product_code, p.product_name
            FROM pp_routings r
            LEFT JOIN products p ON r.product_id = p.id
            WHERE r.id = ?
        ");
        $stmt->execute([$route_id]);
        $current_route = $stmt->fetch();
        $stmt->closeCursor();

        // Obtener operaciones de la ruta
        $stmt = $pdo->prepare("
            SELECT ro.*, wc.center_code, wc.center_name
            FROM pp_routing_operations ro
            LEFT JOIN pp_work_centers wc ON ro.work_center_id = wc.id
            WHERE ro.routing_id = ?
            ORDER BY ro.operation_seq
        ");
        $stmt->execute([$route_id]);
        $operations = $stmt->fetchAll();
        $stmt->closeCursor();
    }
} catch (PDOException $e) {
    $error = "Error al cargar datos: " . $e->getMessage();
}

// Calcular estadisticas
$total_routes = count($routes);
$active_routes = count(array_filter($routes, fn($r) => $r['status'] === 'active'));
$total_operations = array_sum(array_column($routes, 'operation_count'));
$avg_time = $total_routes > 0 ? array_sum(array_column($routes, 'total_time')) / $total_routes : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rutas de Produccion - CONECTA ERP</title>
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
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
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
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border-left: 4px solid #3b82f6;
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

        .badge-draft {
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
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
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

        .operations-section {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 2px solid #f1f5f9;
        }

        .operation-card {
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .operation-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
        }

        .operation-seq {
            background: #3b82f6;
            color: white;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }

        .operation-info {
            flex: 1;
            margin-left: 1rem;
        }

        .operation-title {
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 0.25rem;
        }

        .operation-details {
            font-size: 0.813rem;
            color: #64748b;
        }

        .time-badge {
            background: white;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-size: 0.813rem;
            font-weight: 600;
            color: #475569;
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
            color: #3b82f6;
            border-bottom-color: #3b82f6;
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
    </style>
</head>
<body>
    <div class="header">
        <h1>Rutas de Produccion</h1>
        <p>Gestion de rutas, operaciones, secuencias y tiempos estandar</p>
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
                <h3>Total Rutas</h3>
                <div class="value"><?php echo $total_routes; ?></div>
                <div class="label">rutas registradas</div>
            </div>
            <div class="stat-card">
                <h3>Rutas Activas</h3>
                <div class="value"><?php echo $active_routes; ?></div>
                <div class="label">en uso actual</div>
            </div>
            <div class="stat-card">
                <h3>Total Operaciones</h3>
                <div class="value"><?php echo $total_operations; ?></div>
                <div class="label">operaciones definidas</div>
            </div>
            <div class="stat-card">
                <h3>Tiempo Promedio</h3>
                <div class="value"><?php echo number_format($avg_time, 1); ?></div>
                <div class="label">minutos por ruta</div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="tabs">
            <button class="tab <?php echo !$route_id ? 'active' : ''; ?>" onclick="showTab('list')">
                Lista de Rutas
            </button>
            <button class="tab <?php echo $route_id && $action !== 'create' ? 'active' : ''; ?>" onclick="showTab('details')" id="detailsTab" <?php echo !$route_id ? 'style="display:none"' : ''; ?>>
                Detalles de Ruta
            </button>
            <button class="tab <?php echo $action === 'create' ? 'active' : ''; ?>" onclick="showTab('form')">
                <?php echo $route_id && $action === 'update' ? 'Editar Ruta' : 'Nueva Ruta'; ?>
            </button>
        </div>

        <!-- Tab: Lista de Rutas -->
        <div id="listTab" class="tab-content <?php echo !$route_id ? 'active' : ''; ?>">
            <div class="card">
                <div class="card-header">
                    <h2>Rutas de Produccion</h2>
                    <button class="btn btn-primary" onclick="showTab('form')">+ Nueva Ruta</button>
                </div>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Codigo</th>
                                <th>Nombre</th>
                                <th>Producto</th>
                                <th>Version</th>
                                <th>Operaciones</th>
                                <th>Tiempo Total</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($routes as $route): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($route['route_code']); ?></strong></td>
                                <td><?php echo htmlspecialchars($route['route_name']); ?></td>
                                <td><?php echo $route['product_code'] ? htmlspecialchars($route['product_code'] . ' - ' . $route['product_name']) : '-'; ?></td>
                                <td><?php echo htmlspecialchars($route['version']); ?></td>
                                <td><?php echo $route['operation_count']; ?> ops</td>
                                <td><?php echo number_format($route['total_time'], 1); ?> min</td>
                                <td>
                                    <span class="badge badge-<?php echo $route['status'] === 'active' ? 'active' : ($route['status'] === 'inactive' ? 'inactive' : 'draft'); ?>">
                                        <?php echo strtoupper($route['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="?action=view&id=<?php echo $route['id']; ?>" class="btn btn-secondary btn-small">Ver</a>
                                        <a href="?action=update&id=<?php echo $route['id']; ?>" class="btn btn-primary btn-small">Editar</a>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar esta ruta?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="route_id" value="<?php echo $route['id']; ?>">
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

        <!-- Tab: Detalles de Ruta -->
        <div id="detailsTab" class="tab-content <?php echo $route_id && $action !== 'create' && $action !== 'update' ? 'active' : ''; ?>">
            <?php if ($current_route): ?>
            <div class="card">
                <div class="card-header">
                    <h2><?php echo htmlspecialchars($current_route['route_name']); ?></h2>
                    <div class="action-buttons">
                        <a href="?action=update&id=<?php echo $current_route['id']; ?>" class="btn btn-primary">Editar Ruta</a>
                        <a href="?" class="btn btn-secondary">Volver</a>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Codigo de Ruta</label>
                        <input type="text" value="<?php echo htmlspecialchars($current_route['route_code']); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>Producto</label>
                        <input type="text" value="<?php echo $current_route['product_code'] ? htmlspecialchars($current_route['product_code'] . ' - ' . $current_route['product_name']) : '-'; ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>Version</label>
                        <input type="text" value="<?php echo htmlspecialchars($current_route['version']); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>Estado</label>
                        <input type="text" value="<?php echo strtoupper($current_route['status']); ?>" readonly>
                    </div>
                </div>

                <?php if ($current_route['description']): ?>
                <div class="form-group">
                    <label>Descripcion</label>
                    <textarea readonly><?php echo htmlspecialchars($current_route['description']); ?></textarea>
                </div>
                <?php endif; ?>

                <!-- Operaciones -->
                <div class="operations-section">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                        <h3 style="font-size: 1.25rem; color: #1e293b;">Operaciones de la Ruta</h3>
                        <button class="btn btn-success btn-small" onclick="document.getElementById('addOperationForm').style.display='block'">+ Agregar Operacion</button>
                    </div>

                    <?php if (empty($operations)): ?>
                        <p style="color: #64748b; text-align: center; padding: 2rem;">No hay operaciones definidas para esta ruta</p>
                    <?php else: ?>
                        <?php foreach ($operations as $op): ?>
                        <div class="operation-card">
                            <div class="operation-header">
                                <div style="display: flex; align-items: center; flex: 1;">
                                    <div class="operation-seq"><?php echo $op['operation_seq']; ?></div>
                                    <div class="operation-info">
                                        <div class="operation-title">
                                            <?php echo htmlspecialchars($op['operation_code'] . ' - ' . $op['operation_name']); ?>
                                        </div>
                                        <div class="operation-details">
                                            Centro: <?php echo htmlspecialchars($op['center_code'] . ' - ' . $op['center_name']); ?>
                                        </div>
                                    </div>
                                </div>
                                <div style="display: flex; gap: 1rem; align-items: center;">
                                    <div class="time-badge">
                                        Setup: <?php echo $op['setup_time']; ?> min
                                    </div>
                                    <div class="time-badge">
                                        Run: <?php echo $op['run_time']; ?> min
                                    </div>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar operacion?');">
                                        <input type="hidden" name="action" value="delete_operation">
                                        <input type="hidden" name="route_id" value="<?php echo $route_id; ?>">
                                        <input type="hidden" name="operation_id" value="<?php echo $op['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-small">Eliminar</button>
                                    </form>
                                </div>
                            </div>
                            <?php if ($op['operation_description'] || $op['tools_required']): ?>
                            <div style="margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid #e2e8f0; font-size: 0.813rem; color: #64748b;">
                                <?php if ($op['operation_description']): ?>
                                    <div><strong>Descripcion:</strong> <?php echo htmlspecialchars($op['operation_description']); ?></div>
                                <?php endif; ?>
                                <?php if ($op['tools_required']): ?>
                                    <div><strong>Herramientas:</strong> <?php echo htmlspecialchars($op['tools_required']); ?></div>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Formulario Agregar Operacion -->
            <div id="addOperationForm" style="display: none; margin-top: 1.5rem;">
                <div class="card">
                    <div class="card-header">
                        <h2>Agregar Operacion</h2>
                        <button class="btn btn-secondary" onclick="document.getElementById('addOperationForm').style.display='none'">Cancelar</button>
                    </div>

                    <form method="POST">
                        <input type="hidden" name="action" value="add_operation">
                        <input type="hidden" name="route_id" value="<?php echo $route_id; ?>">

                        <div class="form-grid">
                            <div class="form-group">
                                <label>Secuencia *</label>
                                <input type="number" name="operation_seq" required min="1" value="<?php echo count($operations) + 1; ?>">
                            </div>
                            <div class="form-group">
                                <label>Codigo Operacion *</label>
                                <input type="text" name="operation_code" required>
                            </div>
                            <div class="form-group">
                                <label>Nombre Operacion *</label>
                                <input type="text" name="operation_name" required>
                            </div>
                            <div class="form-group">
                                <label>Centro de Trabajo *</label>
                                <select name="work_center_id" required>
                                    <option value="">Seleccionar...</option>
                                    <?php foreach ($work_centers as $wc): ?>
                                    <option value="<?php echo $wc['id']; ?>">
                                        <?php echo htmlspecialchars($wc['center_code'] . ' - ' . $wc['center_name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Tiempo Setup (min) *</label>
                                <input type="number" name="setup_time" required min="0" step="0.01" value="0">
                            </div>
                            <div class="form-group">
                                <label>Tiempo Run (min) *</label>
                                <input type="number" name="run_time" required min="0" step="0.01" value="0">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Descripcion</label>
                            <textarea name="operation_description"></textarea>
                        </div>

                        <div class="form-group">
                            <label>Herramientas Requeridas</label>
                            <textarea name="tools_required"></textarea>
                        </div>

                        <button type="submit" class="btn btn-success">Agregar Operacion</button>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Tab: Formulario -->
        <div id="formTab" class="tab-content <?php echo $action === 'create' || $action === 'update' ? 'active' : ''; ?>">
            <div class="card">
                <div class="card-header">
                    <h2><?php echo $route_id && $action === 'update' ? 'Editar Ruta' : 'Nueva Ruta'; ?></h2>
                    <a href="?" class="btn btn-secondary">Cancelar</a>
                </div>

                <form method="POST">
                    <input type="hidden" name="action" value="<?php echo $route_id && $action === 'update' ? 'update' : 'create'; ?>">
                    <?php if ($route_id && $action === 'update'): ?>
                        <input type="hidden" name="route_id" value="<?php echo $route_id; ?>">
                    <?php endif; ?>

                    <div class="form-grid">
                        <div class="form-group">
                            <label>Codigo de Ruta *</label>
                            <input type="text" name="route_code" required value="<?php echo $current_route ? htmlspecialchars($current_route['route_code']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label>Nombre de Ruta *</label>
                            <input type="text" name="route_name" required value="<?php echo $current_route ? htmlspecialchars($current_route['route_name']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label>Producto</label>
                            <select name="product_id">
                                <option value="">Sin producto especifico</option>
                                <?php foreach ($products as $product): ?>
                                <option value="<?php echo $product['id']; ?>" <?php echo ($current_route && $current_route['product_id'] == $product['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($product['product_code'] . ' - ' . $product['product_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Version *</label>
                            <input type="text" name="version" required value="<?php echo $current_route ? htmlspecialchars($current_route['version']) : '1.0'; ?>">
                        </div>
                        <div class="form-group">
                            <label>Estado *</label>
                            <select name="status" required>
                                <option value="draft" <?php echo ($current_route && $current_route['status'] === 'draft') ? 'selected' : ''; ?>>Borrador</option>
                                <option value="active" <?php echo ($current_route && $current_route['status'] === 'active') ? 'selected' : ''; ?>>Activo</option>
                                <option value="inactive" <?php echo ($current_route && $current_route['status'] === 'inactive') ? 'selected' : ''; ?>>Inactivo</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Descripcion</label>
                        <textarea name="description"><?php echo $current_route ? htmlspecialchars($current_route['description']) : ''; ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-success">
                        <?php echo $route_id && $action === 'update' ? 'Actualizar Ruta' : 'Crear Ruta'; ?>
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
