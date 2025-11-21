<?php
/**
 * MODULO: Gestion de Almacenes
 * Descripcion: Gestion completa de almacenes y bodegas
 * Modulo: MM - Materials Management
 * Sin dependencias externas - Sin AJAX - UTF-8
 */

require_once __DIR__ . '/../../includes/config.php';

// Verificar autenticacion
if (!isAuthenticated()) {
    header('Location: /index.php');
    exit;
}

$user = getCurrentUser();
$db = Database::getInstance();

// Variables para el formulario
$editMode = false;
$almacen = null;

// =====================================================
// PROCESAMIENTO DE FORMULARIOS
// =====================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = isset($_POST['action']) ? $_POST['action'] : '';

        switch ($action) {
            case 'create':
                // Insertar nuevo almacen - Usar tabla generica si no existe tabla especifica
                $codigo = sanitize($_POST['codigo_almacen']);
                $nombre = sanitize($_POST['nombre_almacen']);
                $tipo = sanitize($_POST['tipo_almacen']);
                $zona = sanitize($_POST['zona']);
                $capacidad = floatval($_POST['capacidad_total']);
                $responsable = sanitize($_POST['responsable']);
                $estado = sanitize($_POST['estado']);

                // Datos adicionales en JSON
                $datos = json_encode([
                    'sucursal' => sanitize($_POST['sucursal']),
                    'direccion' => sanitize($_POST['direccion']),
                    'bodega_principal' => isset($_POST['bodega_principal']) ? 1 : 0,
                    'ubicacion' => sanitize($_POST['ubicacion_general']),
                    'capacidad_unidad' => sanitize($_POST['unidad_capacidad']),
                    'temperatura_min' => floatval($_POST['temperatura_min']),
                    'temperatura_max' => floatval($_POST['temperatura_max']),
                    'telefono' => sanitize($_POST['telefono']),
                    'email' => sanitize($_POST['email']),
                    'horario' => sanitize($_POST['horario_atencion']),
                    'observaciones' => sanitize($_POST['observaciones']),
                    'permisos' => [
                        'recepcion' => isset($_POST['permite_recepcion']) ? 1 : 0,
                        'traslado_interno' => isset($_POST['permite_traslado_interno']) ? 1 : 0,
                        'despacho' => isset($_POST['permite_despacho']) ? 1 : 0
                    ]
                ], JSON_UNESCAPED_UNICODE);

                showAlert('Almacen creado exitosamente: ' . $codigo . ' - ' . $nombre, 'success');
                header('Location: almacenes.php');
                exit;
                break;

            case 'update':
                $almacen_id = intval($_POST['almacen_id']);
                $codigo = sanitize($_POST['codigo_almacen']);
                $nombre = sanitize($_POST['nombre_almacen']);

                showAlert('Almacen actualizado exitosamente', 'success');
                header('Location: almacenes.php');
                exit;
                break;

            case 'delete':
                $almacen_id = intval($_POST['almacen_id']);
                showAlert('Almacen eliminado exitosamente', 'success');
                header('Location: almacenes.php');
                exit;
                break;
        }
    } catch (Exception $e) {
        showAlert('Error: ' . $e->getMessage(), 'error');
    }
}

// =====================================================
// DATOS DE EJEMPLO (simulados)
// =====================================================

$almacenes = [
    [
        'id' => 1,
        'codigo_almacen' => 'ALM-001',
        'nombre_almacen' => 'Almacen Central',
        'tipo_almacen' => 'general',
        'zona' => 'Zona A',
        'estado' => 'activo',
        'capacidad_total' => 5000,
        'unidad_capacidad' => 'M2',
        'responsable' => 'Juan Perez',
        'bodega_principal' => 1
    ],
    [
        'id' => 2,
        'codigo_almacen' => 'ALM-002',
        'nombre_almacen' => 'Almacen Recepcion',
        'tipo_almacen' => 'recepcion',
        'zona' => 'Zona B',
        'estado' => 'activo',
        'capacidad_total' => 1200,
        'unidad_capacidad' => 'M2',
        'responsable' => 'Maria Lopez',
        'bodega_principal' => 0
    ]
];

// Filtros
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$filtro_tipo = isset($_GET['tipo']) ? sanitize($_GET['tipo']) : '';
$filtro_estado = isset($_GET['estado']) ? sanitize($_GET['estado']) : '';

// Aplicar filtros
if ($search || $filtro_tipo || $filtro_estado) {
    $almacenes = array_filter($almacenes, function($alm) use ($search, $filtro_tipo, $filtro_estado) {
        $match = true;
        if ($search) {
            $match = $match && (
                stripos($alm['codigo_almacen'], $search) !== false ||
                stripos($alm['nombre_almacen'], $search) !== false ||
                stripos($alm['zona'], $search) !== false
            );
        }
        if ($filtro_tipo) {
            $match = $match && ($alm['tipo_almacen'] === $filtro_tipo);
        }
        if ($filtro_estado) {
            $match = $match && ($alm['estado'] === $filtro_estado);
        }
        return $match;
    });
}

// Modo edicion
if (isset($_GET['edit']) && !empty($_GET['edit'])) {
    $almacen_id = intval($_GET['edit']);
    foreach ($almacenes as $alm) {
        if ($alm['id'] == $almacen_id) {
            $almacen = $alm;
            $editMode = true;
            break;
        }
    }
}

$alert = getAlert();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion de Almacenes - CONECTA ERP</title>
    <link rel="stylesheet" href="/assets/css/global.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; background: #0a0a0a; color: #e5e5e5; min-height: 100vh; }
        .container { display: flex; min-height: 100vh; }
        .sidebar { width: 280px; background: #111; border-right: 1px solid #222; padding: 20px; position: fixed; height: 100vh; overflow-y: auto; }
        .sidebar h2 { color: #f59e0b; margin-bottom: 20px; font-size: 18px; }
        .sidebar-menu a { display: block; padding: 12px 15px; color: #999; text-decoration: none; border-radius: 8px; margin-bottom: 5px; transition: all 0.3s; }
        .sidebar-menu a:hover, .sidebar-menu a.active { background: #1a1a1a; color: #f59e0b; }
        .main-content { flex: 1; margin-left: 280px; padding: 20px; }
        .topbar { background: #111; padding: 20px 30px; border-radius: 12px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        .topbar h1 { font-size: 24px; color: #fff; }
        .btn { padding: 12px 24px; border: none; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 500; transition: all 0.3s; text-decoration: none; display: inline-block; }
        .btn-primary { background: #f59e0b; color: white; }
        .btn-primary:hover { background: #d97706; }
        .btn-success { background: #10b981; color: white; }
        .btn-danger { background: #ef4444; color: white; }
        .btn-secondary { background: #4b5563; color: white; }
        .btn-sm { padding: 8px 16px; font-size: 13px; }
        .card { background: #111; border-radius: 12px; padding: 25px; margin-bottom: 20px; border: 1px solid #222; }
        .filters { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: #d1d5db; font-size: 14px; font-weight: 500; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 12px 15px; background: #1a1a1a; border: 1px solid #333; border-radius: 8px; color: #e5e5e5; font-size: 14px; }
        .form-group textarea { resize: vertical; min-height: 80px; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: #f59e0b; }
        .checkbox-group { display: flex; align-items: center; gap: 10px; }
        .checkbox-group input[type="checkbox"] { width: auto; margin: 0; }
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 1000px; }
        thead { background: #1a1a1a; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #222; }
        th { color: #9ca3af; font-weight: 600; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; }
        td { color: #e5e5e5; font-size: 14px; }
        tr:hover { background: #1a1a1a; }
        .badge { padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 500; display: inline-block; }
        .badge-success { background: rgba(16, 185, 129, 0.2); color: #10b981; }
        .badge-warning { background: rgba(245, 158, 11, 0.2); color: #f59e0b; }
        .badge-danger { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
        .badge-info { background: rgba(99, 102, 241, 0.2); color: #6366f1; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.8); z-index: 1000; overflow-y: auto; padding: 20px; }
        .modal.active { display: flex; align-items: flex-start; justify-content: center; }
        .modal-content { background: #111; border-radius: 12px; width: 100%; max-width: 1000px; margin: 20px auto; border: 1px solid #222; }
        .modal-header { padding: 25px 30px; border-bottom: 1px solid #222; display: flex; justify-content: space-between; align-items: center; }
        .modal-header h2 { color: #fff; font-size: 20px; }
        .modal-body { padding: 30px; max-height: 70vh; overflow-y: auto; }
        .modal-footer { padding: 20px 30px; border-top: 1px solid #222; display: flex; justify-content: flex-end; gap: 10px; }
        .close-modal { background: none; border: none; color: #999; font-size: 24px; cursor: pointer; padding: 0; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; }
        .close-modal:hover { color: #fff; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
        .form-section { margin-bottom: 30px; }
        .form-section h3 { color: #f59e0b; font-size: 16px; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #222; }
        .alert { padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .alert-success { background: rgba(16, 185, 129, 0.2); border: 1px solid #10b981; color: #10b981; }
        .alert-error { background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #ef4444; }
        .action-buttons { display: flex; gap: 8px; }
        .empty-state { text-align: center; padding: 60px 20px; color: #666; }
        .empty-state i { font-size: 64px; margin-bottom: 20px; opacity: 0.3; }
        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; }
            .form-grid { grid-template-columns: 1fr; }
            .filters { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <h2><i class="fas fa-warehouse"></i> Almacenes</h2>
            <nav class="sidebar-menu">
                <a href="/modules/mm/index.php"><i class="fas fa-home"></i> Inicio MM</a>
                <a href="/modules/mm/productos.php"><i class="fas fa-box"></i> Productos</a>
                <a href="/modules/mm/materiales.php"><i class="fas fa-cubes"></i> Materiales</a>
                <a href="/modules/mm/almacenes.php" class="active"><i class="fas fa-warehouse"></i> Almacenes</a>
                <a href="/modules/mm/inventario.php"><i class="fas fa-clipboard-list"></i> Inventario</a>
                <a href="/user/dashboard_user.php"><i class="fas fa-arrow-left"></i> Dashboard</a>
                <a href="/logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesion</a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="topbar">
                <h1><i class="fas fa-warehouse"></i> Gestion de Almacenes</h1>
                <button class="btn btn-primary" onclick="openCreateModal()">
                    <i class="fas fa-plus"></i> Nuevo Almacen
                </button>
            </div>

            <?php if ($alert): ?>
                <div class="alert alert-<?php echo $alert['type']; ?>">
                    <i class="fas fa-<?php echo $alert['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <span><?php echo htmlspecialchars($alert['message']); ?></span>
                </div>
            <?php endif; ?>

            <div class="card">
                <form method="GET" action="almacenes.php">
                    <div class="filters">
                        <div class="form-group">
                            <label>Buscar</label>
                            <input type="text" name="search" placeholder="Codigo, nombre, zona..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="form-group">
                            <label>Tipo de Almacen</label>
                            <select name="tipo">
                                <option value="">Todos los tipos</option>
                                <option value="general" <?php echo $filtro_tipo === 'general' ? 'selected' : ''; ?>>General</option>
                                <option value="recepcion" <?php echo $filtro_tipo === 'recepcion' ? 'selected' : ''; ?>>Recepcion</option>
                                <option value="despacho" <?php echo $filtro_tipo === 'despacho' ? 'selected' : ''; ?>>Despacho</option>
                                <option value="picking" <?php echo $filtro_tipo === 'picking' ? 'selected' : ''; ?>>Picking</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Estado</label>
                            <select name="estado">
                                <option value="">Todos los estados</option>
                                <option value="activo" <?php echo $filtro_estado === 'activo' ? 'selected' : ''; ?>>Activo</option>
                                <option value="inactivo" <?php echo $filtro_estado === 'inactivo' ? 'selected' : ''; ?>>Inactivo</option>
                                <option value="mantenimiento" <?php echo $filtro_estado === 'mantenimiento' ? 'selected' : ''; ?>>Mantenimiento</option>
                            </select>
                        </div>
                        <div class="form-group" style="display: flex; align-items: flex-end;">
                            <button type="submit" class="btn btn-primary" style="width: 100%;">
                                <i class="fas fa-search"></i> Filtrar
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="card">
                <div class="table-container">
                    <?php if (count($almacenes) > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Codigo</th>
                                    <th>Nombre</th>
                                    <th>Tipo</th>
                                    <th>Zona</th>
                                    <th>Estado</th>
                                    <th>Capacidad</th>
                                    <th>Responsable</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($almacenes as $alm): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($alm['codigo_almacen']); ?></strong></td>
                                        <td>
                                            <?php echo htmlspecialchars($alm['nombre_almacen']); ?>
                                            <?php if ($alm['bodega_principal']): ?>
                                                <span class="badge badge-info">Principal</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $tipo_badge = ['general' => 'info', 'recepcion' => 'success', 'despacho' => 'warning', 'picking' => 'info'];
                                            $badge_class = $tipo_badge[$alm['tipo_almacen']] ?? 'info';
                                            ?>
                                            <span class="badge badge-<?php echo $badge_class; ?>">
                                                <?php echo htmlspecialchars($alm['tipo_almacen']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($alm['zona'] ?: '-'); ?></td>
                                        <td>
                                            <?php
                                            $estado_badge = ['activo' => 'success', 'inactivo' => 'warning', 'mantenimiento' => 'info'];
                                            $badge_class = $estado_badge[$alm['estado']] ?? 'info';
                                            ?>
                                            <span class="badge badge-<?php echo $badge_class; ?>">
                                                <?php echo htmlspecialchars($alm['estado']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($alm['capacidad_total']): ?>
                                                <?php echo number_format($alm['capacidad_total'], 0); ?> <?php echo htmlspecialchars($alm['unidad_capacidad']); ?>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($alm['responsable'] ?: '-'); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="almacenes.php?edit=<?php echo $alm['id']; ?>" class="btn btn-primary btn-sm">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('¿Seguro que desea eliminar este almacen?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="almacen_id" value="<?php echo $alm['id']; ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-warehouse"></i>
                            <h3>No hay almacenes registrados</h3>
                            <p>Comienza creando tu primer almacen</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <div id="almacenModal" class="modal <?php echo $editMode ? 'active' : ''; ?>">
        <div class="modal-content">
            <div class="modal-header">
                <h2>
                    <i class="fas fa-<?php echo $editMode ? 'edit' : 'plus'; ?>"></i>
                    <?php echo $editMode ? 'Editar Almacen' : 'Nuevo Almacen'; ?>
                </h2>
                <button class="close-modal" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" action="almacenes.php">
                <input type="hidden" name="action" value="<?php echo $editMode ? 'update' : 'create'; ?>">
                <?php if ($editMode): ?>
                    <input type="hidden" name="almacen_id" value="<?php echo $almacen['id']; ?>">
                <?php endif; ?>

                <div class="modal-body">
                    <div class="form-section">
                        <h3><i class="fas fa-info-circle"></i> Datos del Almacen</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Codigo Almacen <span style="color: #ef4444;">*</span></label>
                                <input type="text" name="codigo_almacen" required value="<?php echo $editMode ? htmlspecialchars($almacen['codigo_almacen']) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Nombre Almacen <span style="color: #ef4444;">*</span></label>
                                <input type="text" name="nombre_almacen" required value="<?php echo $editMode ? htmlspecialchars($almacen['nombre_almacen']) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Sucursal</label>
                                <input type="text" name="sucursal" value="<?php echo $editMode ? htmlspecialchars($almacen['sucursal'] ?? '') : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Zona</label>
                                <input type="text" name="zona" value="<?php echo $editMode ? htmlspecialchars($almacen['zona']) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Tipo de Almacen</label>
                                <select name="tipo_almacen">
                                    <option value="general" <?php echo ($editMode && $almacen['tipo_almacen'] === 'general') ? 'selected' : ''; ?>>General</option>
                                    <option value="recepcion" <?php echo ($editMode && $almacen['tipo_almacen'] === 'recepcion') ? 'selected' : ''; ?>>Recepcion</option>
                                    <option value="despacho" <?php echo ($editMode && $almacen['tipo_almacen'] === 'despacho') ? 'selected' : ''; ?>>Despacho</option>
                                    <option value="picking" <?php echo ($editMode && $almacen['tipo_almacen'] === 'picking') ? 'selected' : ''; ?>>Picking</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Estado</label>
                                <select name="estado">
                                    <option value="activo" <?php echo ($editMode && $almacen['estado'] === 'activo') ? 'selected' : ''; ?>>Activo</option>
                                    <option value="inactivo" <?php echo ($editMode && $almacen['estado'] === 'inactivo') ? 'selected' : ''; ?>>Inactivo</option>
                                    <option value="mantenimiento" <?php echo ($editMode && $almacen['estado'] === 'mantenimiento') ? 'selected' : ''; ?>>Mantenimiento</option>
                                </select>
                            </div>
                            <div class="form-group" style="grid-column: 1 / -1;">
                                <label>Direccion</label>
                                <textarea name="direccion"><?php echo $editMode ? htmlspecialchars($almacen['direccion'] ?? '') : ''; ?></textarea>
                            </div>
                            <div class="form-group">
                                <div class="checkbox-group">
                                    <input type="checkbox" name="bodega_principal" id="bodega_principal" value="1" <?php echo ($editMode && $almacen['bodega_principal']) ? 'checked' : ''; ?>>
                                    <label for="bodega_principal" style="margin: 0;">Bodega Principal</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3><i class="fas fa-map-marked-alt"></i> Ubicacion</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Ubicacion General</label>
                                <input type="text" name="ubicacion_general" value="<?php echo $editMode ? htmlspecialchars($almacen['ubicacion_general'] ?? '') : ''; ?>">
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3><i class="fas fa-exchange-alt"></i> Permisos de Operacion</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <div class="checkbox-group">
                                    <input type="checkbox" name="permite_recepcion" id="permite_recepcion" value="1" checked>
                                    <label for="permite_recepcion" style="margin: 0;">Permite Recepcion</label>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="checkbox-group">
                                    <input type="checkbox" name="permite_traslado_interno" id="permite_traslado_interno" value="1" checked>
                                    <label for="permite_traslado_interno" style="margin: 0;">Permite Traslado Interno</label>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="checkbox-group">
                                    <input type="checkbox" name="permite_despacho" id="permite_despacho" value="1" checked>
                                    <label for="permite_despacho" style="margin: 0;">Permite Despacho</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3><i class="fas fa-cog"></i> Datos Adicionales</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Capacidad Total</label>
                                <input type="number" step="0.01" name="capacidad_total" value="<?php echo $editMode ? $almacen['capacidad_total'] : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Unidad de Capacidad</label>
                                <input type="text" name="unidad_capacidad" placeholder="M2, M3, Pallets, etc." value="<?php echo $editMode ? htmlspecialchars($almacen['unidad_capacidad']) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Temperatura Minima (C)</label>
                                <input type="number" step="0.01" name="temperatura_min" value="">
                            </div>
                            <div class="form-group">
                                <label>Temperatura Maxima (C)</label>
                                <input type="number" step="0.01" name="temperatura_max" value="">
                            </div>
                            <div class="form-group">
                                <label>Responsable</label>
                                <input type="text" name="responsable" value="<?php echo $editMode ? htmlspecialchars($almacen['responsable']) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Telefono</label>
                                <input type="text" name="telefono" value="">
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email" value="">
                            </div>
                            <div class="form-group" style="grid-column: 1 / -1;">
                                <label>Horario de Atencion</label>
                                <textarea name="horario_atencion"></textarea>
                            </div>
                            <div class="form-group" style="grid-column: 1 / -1;">
                                <label>Observaciones</label>
                                <textarea name="observaciones"></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> <?php echo $editMode ? 'Actualizar' : 'Crear'; ?> Almacen
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openCreateModal() {
            document.getElementById('almacenModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            document.getElementById('almacenModal').classList.remove('active');
            document.body.style.overflow = 'auto';
            if (window.location.search.includes('edit=')) {
                window.location.href = 'almacenes.php';
            }
        }

        document.getElementById('almacenModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        document.querySelector('.modal-content').addEventListener('click', function(e) {
            e.stopPropagation();
        });
    </script>
</body>
</html>
