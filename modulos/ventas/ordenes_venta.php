<?php
/**
 * MÓDULO VENTAS - ÓRDENES DE VENTA COMPLETO
 * Sistema completo de gestión de órdenes de venta con tracking
 */
session_start();
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';

requireLogin();

$empresa_id = $_SESSION['empresa_id'];
$success = '';
$error = '';

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['crear_orden'])) {
        try {
            $conn->begin_transaction();

            // Validaciones
            $cliente_id = intval($_POST['cliente_id']);
            if ($cliente_id <= 0) {
                throw new Exception("Debe seleccionar un cliente");
            }

            // Datos de la orden
            $datos_orden = [
                'empresa_id' => $empresa_id,
                'cliente_id' => $cliente_id,
                'numero_orden' => generateUUID(),
                'fecha_orden' => $_POST['fecha_orden'] ?? date('Y-m-d'),
                'fecha_entrega_estimada' => $_POST['fecha_entrega_estimada'],
                'direccion_entrega' => cleanString($conn, $_POST['direccion_entrega']),
                'ciudad_entrega' => cleanString($conn, $_POST['ciudad_entrega']),
                'observaciones' => cleanString($conn, $_POST['observaciones'] ?? ''),
                'condiciones_pago' => cleanString($conn, $_POST['condiciones_pago'] ?? 'Contado'),
                'estado' => 'pendiente',
                'usuario_creador' => $_SESSION['user_id']
            ];

            // Calcular totales desde los productos
            $productos = json_decode($_POST['productos_json'], true);
            if (empty($productos)) {
                throw new Exception("Debe agregar al menos un producto");
            }

            $subtotal = 0;
            foreach ($productos as $prod) {
                $subtotal += floatval($prod['subtotal']);
            }

            $iva = round($subtotal * 0.19, 0);
            $total = $subtotal + $iva;

            $datos_orden['subtotal'] = $subtotal;
            $datos_orden['iva'] = $iva;
            $datos_orden['total'] = $total;

            // Insertar orden
            $columns = implode(', ', array_keys($datos_orden));
            $placeholders = implode(', ', array_fill(0, count($datos_orden), '?'));
            $types = str_repeat('s', count($datos_orden));

            $sql = "INSERT INTO ordenes_venta ($columns) VALUES ($placeholders)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...array_values($datos_orden));
            $stmt->execute();
            $orden_id = $conn->insert_id();

            // Insertar detalle de productos
            foreach ($productos as $prod) {
                $sql_detalle = "INSERT INTO ordenes_venta_detalle
                               (orden_id, producto_id, descripcion, cantidad, precio_unitario, subtotal)
                               VALUES (?, ?, ?, ?, ?, ?)";
                $stmt_detalle = $conn->prepare($sql_detalle);
                $stmt_detalle->bind_param('iisddd',
                    $orden_id,
                    $prod['producto_id'],
                    $prod['descripcion'],
                    $prod['cantidad'],
                    $prod['precio'],
                    $prod['subtotal']
                );
                $stmt_detalle->execute();
            }

            $conn->commit();
            $success = 'Orden de venta creada correctamente: #' . $datos_orden['numero_orden'];

            logAuditoria('crear_orden_venta', 'ordenes_venta', $orden_id, null,
                        json_encode($datos_orden), 'Orden de venta creada');

        } catch (Exception $e) {
            $conn->rollback();
            $error = 'Error: ' . $e->getMessage();
        }
    }

    // Cambiar estado de orden
    if (isset($_POST['cambiar_estado'])) {
        $orden_id = intval($_POST['orden_id']);
        $nuevo_estado = $_POST['nuevo_estado'];

        $estados_validos = ['pendiente', 'confirmada', 'en_produccion', 'enviada', 'entregada', 'cancelada'];
        if (in_array($nuevo_estado, $estados_validos)) {
            executeQuery($conn,
                "UPDATE ordenes_venta SET estado = ?, updated_at = NOW() WHERE id = ? AND empresa_id = ?",
                [$nuevo_estado, $orden_id, $empresa_id], 'sii'
            );
            $success = 'Estado actualizado correctamente';

            logAuditoria('cambiar_estado_orden', 'ordenes_venta', $orden_id,
                        null, $nuevo_estado, 'Estado de orden cambiado');
        }
    }

    // Convertir a factura
    if (isset($_POST['convertir_factura'])) {
        $orden_id = intval($_POST['orden_id']);

        // Obtener datos de la orden
        $orden = fetchOne($conn,
            "SELECT * FROM ordenes_venta WHERE id = ? AND empresa_id = ?",
            [$orden_id, $empresa_id], 'ii'
        );

        if ($orden) {
            // Redirigir a facturas con datos pre-cargados
            $_SESSION['orden_a_facturar'] = $orden_id;
            header('Location: facturas.php?desde_orden=' . $orden_id);
            exit;
        }
    }
}

// Obtener órdenes de venta
$filtro_estado = $_GET['estado'] ?? '';
$filtro_sql = '';
$params = [$empresa_id];
$types = 'i';

if ($filtro_estado && $filtro_estado !== 'todos') {
    $filtro_sql = " AND ov.estado = ?";
    $params[] = $filtro_estado;
    $types .= 's';
}

$ordenes = fetchAll($conn,
    "SELECT ov.*, c.nombre as cliente_nombre, c.rut as cliente_rut,
            u.nombre as creador_nombre
     FROM ordenes_venta ov
     LEFT JOIN clientes c ON ov.cliente_id = c.id
     LEFT JOIN usuarios u ON ov.usuario_creador = u.id
     WHERE ov.empresa_id = ? $filtro_sql
     ORDER BY ov.fecha_orden DESC, ov.id DESC
     LIMIT 100",
    $params, $types
);

// Obtener clientes para el formulario
$clientes = fetchAll($conn,
    "SELECT id, nombre, rut, email FROM clientes WHERE empresa_id = ? AND activo = 1 ORDER BY nombre",
    [$empresa_id], 'i'
);

// Obtener productos para el formulario
$productos = fetchAll($conn,
    "SELECT id, codigo, nombre, precio_venta FROM productos WHERE empresa_id = ? AND activo = 1 ORDER BY nombre",
    [$empresa_id], 'i'
);

// Estadísticas
$stats = fetchOne($conn,
    "SELECT
        COUNT(*) as total_ordenes,
        SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
        SUM(CASE WHEN estado = 'confirmada' THEN 1 ELSE 0 END) as confirmadas,
        SUM(CASE WHEN estado = 'en_produccion' THEN 1 ELSE 0 END) as en_produccion,
        SUM(CASE WHEN estado = 'enviada' THEN 1 ELSE 0 END) as enviadas,
        SUM(CASE WHEN estado = 'entregada' THEN 1 ELSE 0 END) as entregadas,
        SUM(total) as total_ventas
     FROM ordenes_venta
     WHERE empresa_id = ?",
    [$empresa_id], 'i'
);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Órdenes de Venta - CONECTA ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .gradient-header {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 2rem;
            margin-bottom: 2rem;
            border-radius: 15px;
        }
        .estado-badge {
            font-size: 0.85rem;
            padding: 0.4rem 0.8rem;
        }
        .orden-card {
            transition: all 0.3s;
            border-left: 4px solid #f093fb;
        }
        .orden-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        .stat-card {
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid py-4">
        <div class="gradient-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1><i class="fas fa-shopping-bag"></i> Órdenes de Venta</h1>
                    <p class="mb-0">Gestión completa de órdenes de venta con tracking de estados</p>
                </div>
                <button class="btn btn-light btn-lg" data-bs-toggle="modal" data-bs-target="#nuevaOrdenModal">
                    <i class="fas fa-plus"></i> Nueva Orden
                </button>
            </div>
        </div>

        <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible">
            <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Estadísticas -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Total Órdenes</h6>
                            <h3 class="mb-0"><?= number_format($stats['total_ordenes'] ?? 0) ?></h3>
                        </div>
                        <i class="fas fa-shopping-bag fa-2x text-primary"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Pendientes</h6>
                            <h3 class="mb-0"><?= number_format($stats['pendientes'] ?? 0) ?></h3>
                        </div>
                        <i class="fas fa-clock fa-2x text-warning"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">En Producción</h6>
                            <h3 class="mb-0"><?= number_format($stats['en_produccion'] ?? 0) ?></h3>
                        </div>
                        <i class="fas fa-cogs fa-2x text-info"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Total Ventas</h6>
                            <h3 class="mb-0"><?= formatCurrency($stats['total_ventas'] ?? 0, 'CLP') ?></h3>
                        </div>
                        <i class="fas fa-dollar-sign fa-2x text-success"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Filtrar por Estado</label>
                        <select name="estado" class="form-select" onchange="this.form.submit()">
                            <option value="todos" <?= $filtro_estado === 'todos' ? 'selected' : '' ?>>Todos los estados</option>
                            <option value="pendiente" <?= $filtro_estado === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                            <option value="confirmada" <?= $filtro_estado === 'confirmada' ? 'selected' : '' ?>>Confirmada</option>
                            <option value="en_produccion" <?= $filtro_estado === 'en_produccion' ? 'selected' : '' ?>>En Producción</option>
                            <option value="enviada" <?= $filtro_estado === 'enviada' ? 'selected' : '' ?>>Enviada</option>
                            <option value="entregada" <?= $filtro_estado === 'entregada' ? 'selected' : '' ?>>Entregada</option>
                            <option value="cancelada" <?= $filtro_estado === 'cancelada' ? 'selected' : '' ?>>Cancelada</option>
                        </select>
                    </div>
                </form>
            </div>
        </div>

        <!-- Lista de Órdenes -->
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-list"></i> Órdenes de Venta</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Número</th>
                                <th>Fecha</th>
                                <th>Cliente</th>
                                <th>Entrega Estimada</th>
                                <th>Total</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ordenes as $orden): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($orden['numero_orden']) ?></strong>
                                </td>
                                <td><?= formatDate($orden['fecha_orden']) ?></td>
                                <td>
                                    <div><?= htmlspecialchars($orden['cliente_nombre']) ?></div>
                                    <small class="text-muted"><?= formatRUT($orden['cliente_rut']) ?></small>
                                </td>
                                <td><?= formatDate($orden['fecha_entrega_estimada']) ?></td>
                                <td><strong><?= formatCurrency($orden['total'], 'CLP') ?></strong></td>
                                <td>
                                    <?php
                                    $badge_class = [
                                        'pendiente' => 'bg-warning',
                                        'confirmada' => 'bg-info',
                                        'en_produccion' => 'bg-primary',
                                        'enviada' => 'bg-success',
                                        'entregada' => 'bg-dark',
                                        'cancelada' => 'bg-danger'
                                    ];
                                    $estado_texto = [
                                        'pendiente' => 'Pendiente',
                                        'confirmada' => 'Confirmada',
                                        'en_produccion' => 'En Producción',
                                        'enviada' => 'Enviada',
                                        'entregada' => 'Entregada',
                                        'cancelada' => 'Cancelada'
                                    ];
                                    ?>
                                    <span class="badge estado-badge <?= $badge_class[$orden['estado']] ?? 'bg-secondary' ?>">
                                        <?= $estado_texto[$orden['estado']] ?? $orden['estado'] ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary"
                                                onclick="verDetalleOrden(<?= $orden['id'] ?>)"
                                                title="Ver detalle">
                                            <i class="fas fa-eye"></i>
                                        </button>

                                        <?php if ($orden['estado'] !== 'entregada' && $orden['estado'] !== 'cancelada'): ?>
                                        <button class="btn btn-outline-success"
                                                onclick="cambiarEstadoOrden(<?= $orden['id'] ?>, '<?= $orden['estado'] ?>')"
                                                title="Cambiar estado">
                                            <i class="fas fa-exchange-alt"></i>
                                        </button>
                                        <?php endif; ?>

                                        <?php if ($orden['estado'] === 'confirmada' || $orden['estado'] === 'entregada'): ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="orden_id" value="<?= $orden['id'] ?>">
                                            <button type="submit" name="convertir_factura"
                                                    class="btn btn-outline-info btn-sm"
                                                    title="Convertir a factura">
                                                <i class="fas fa-file-invoice"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>

                            <?php if (empty($ordenes)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                    No hay órdenes de venta registradas
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Nueva Orden -->
    <div class="modal fade" id="nuevaOrdenModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-plus"></i> Nueva Orden de Venta</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="formNuevaOrden">
                    <div class="modal-body">
                        <div class="row">
                            <!-- Cliente y Fechas -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Cliente *</label>
                                <select name="cliente_id" id="clienteSelect" class="form-select" required>
                                    <option value="">Seleccione un cliente...</option>
                                    <?php foreach ($clientes as $cli): ?>
                                    <option value="<?= $cli['id'] ?>"
                                            data-nombre="<?= htmlspecialchars($cli['nombre']) ?>"
                                            data-rut="<?= htmlspecialchars($cli['rut']) ?>">
                                        <?= htmlspecialchars($cli['nombre']) ?> - <?= formatRUT($cli['rut']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Fecha Orden</label>
                                <input type="date" name="fecha_orden" class="form-control"
                                       value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Entrega Estimada *</label>
                                <input type="date" name="fecha_entrega_estimada" class="form-control"
                                       value="<?= date('Y-m-d', strtotime('+7 days')) ?>" required>
                            </div>

                            <!-- Dirección de Entrega -->
                            <div class="col-md-8 mb-3">
                                <label class="form-label">Dirección de Entrega *</label>
                                <input type="text" name="direccion_entrega" class="form-control"
                                       placeholder="Calle, número, depto/oficina" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Ciudad *</label>
                                <input type="text" name="ciudad_entrega" class="form-control"
                                       placeholder="Ciudad" required>
                            </div>

                            <!-- Condiciones de Pago -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Condiciones de Pago</label>
                                <select name="condiciones_pago" class="form-select">
                                    <option value="Contado">Contado</option>
                                    <option value="30 días">30 días</option>
                                    <option value="60 días">60 días</option>
                                    <option value="90 días">90 días</option>
                                </select>
                            </div>

                            <!-- Productos -->
                            <div class="col-12">
                                <hr>
                                <h6><i class="fas fa-box"></i> Productos</h6>
                            </div>
                            <div class="col-12 mb-3">
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <select id="productoSelect" class="form-select">
                                            <option value="">Seleccione producto...</option>
                                            <?php foreach ($productos as $prod): ?>
                                            <option value="<?= $prod['id'] ?>"
                                                    data-codigo="<?= htmlspecialchars($prod['codigo']) ?>"
                                                    data-nombre="<?= htmlspecialchars($prod['nombre']) ?>"
                                                    data-precio="<?= $prod['precio_venta'] ?>">
                                                <?= htmlspecialchars($prod['codigo']) ?> - <?= htmlspecialchars($prod['nombre']) ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <input type="number" id="cantidadInput" class="form-control"
                                               placeholder="Cantidad" min="1" value="1">
                                    </div>
                                    <div class="col-md-3">
                                        <input type="number" id="precioInput" class="form-control"
                                               placeholder="Precio" min="0" step="1">
                                    </div>
                                    <div class="col-md-3">
                                        <button type="button" class="btn btn-success w-100" onclick="agregarProducto()">
                                            <i class="fas fa-plus"></i> Agregar
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Tabla de productos agregados -->
                            <div class="col-12">
                                <div class="table-responsive">
                                    <table class="table table-sm" id="tablaProductos">
                                        <thead>
                                            <tr>
                                                <th>Producto</th>
                                                <th class="text-end">Cantidad</th>
                                                <th class="text-end">Precio Unit.</th>
                                                <th class="text-end">Subtotal</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody id="productosBody">
                                            <tr id="noProductos">
                                                <td colspan="5" class="text-center text-muted">
                                                    No hay productos agregados
                                                </td>
                                            </tr>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td colspan="3" class="text-end"><strong>Subtotal:</strong></td>
                                                <td class="text-end"><strong id="subtotalDisplay">$0</strong></td>
                                                <td></td>
                                            </tr>
                                            <tr>
                                                <td colspan="3" class="text-end"><strong>IVA (19%):</strong></td>
                                                <td class="text-end"><strong id="ivaDisplay">$0</strong></td>
                                                <td></td>
                                            </tr>
                                            <tr>
                                                <td colspan="3" class="text-end"><strong>TOTAL:</strong></td>
                                                <td class="text-end"><strong class="text-primary" id="totalDisplay">$0</strong></td>
                                                <td></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>

                            <!-- Observaciones -->
                            <div class="col-12 mb-3">
                                <label class="form-label">Observaciones</label>
                                <textarea name="observaciones" class="form-control" rows="2"
                                          placeholder="Notas adicionales sobre la orden"></textarea>
                            </div>
                        </div>

                        <input type="hidden" name="productos_json" id="productosJson">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="crear_orden" class="btn btn-primary">
                            <i class="fas fa-save"></i> Crear Orden
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Cambiar Estado -->
    <div class="modal fade" id="cambiarEstadoModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Cambiar Estado de Orden</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="orden_id" id="modalOrdenId">
                        <div class="mb-3">
                            <label class="form-label">Nuevo Estado</label>
                            <select name="nuevo_estado" id="modalNuevoEstado" class="form-select" required>
                                <option value="pendiente">Pendiente</option>
                                <option value="confirmada">Confirmada</option>
                                <option value="en_produccion">En Producción</option>
                                <option value="enviada">Enviada</option>
                                <option value="entregada">Entregada</option>
                                <option value="cancelada">Cancelada</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="cambiar_estado" class="btn btn-primary">
                            Actualizar Estado
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../../user/includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    let productosArray = [];

    // Actualizar precio cuando se selecciona producto
    document.getElementById('productoSelect').addEventListener('change', function() {
        const option = this.options[this.selectedIndex];
        const precio = option.getAttribute('data-precio');
        if (precio) {
            document.getElementById('precioInput').value = precio;
        }
    });

    function agregarProducto() {
        const select = document.getElementById('productoSelect');
        const option = select.options[select.selectedIndex];

        if (!option.value) {
            alert('Seleccione un producto');
            return;
        }

        const producto_id = option.value;
        const codigo = option.getAttribute('data-codigo');
        const nombre = option.getAttribute('data-nombre');
        const descripcion = codigo + ' - ' + nombre;
        const cantidad = parseFloat(document.getElementById('cantidadInput').value) || 1;
        const precio = parseFloat(document.getElementById('precioInput').value) || 0;
        const subtotal = cantidad * precio;

        // Agregar al array
        productosArray.push({
            producto_id: producto_id,
            descripcion: descripcion,
            cantidad: cantidad,
            precio: precio,
            subtotal: subtotal
        });

        actualizarTablaProductos();

        // Limpiar campos
        select.value = '';
        document.getElementById('cantidadInput').value = '1';
        document.getElementById('precioInput').value = '';
    }

    function eliminarProducto(index) {
        productosArray.splice(index, 1);
        actualizarTablaProductos();
    }

    function actualizarTablaProductos() {
        const tbody = document.getElementById('productosBody');
        const noProductos = document.getElementById('noProductos');

        if (productosArray.length === 0) {
            noProductos.style.display = '';
            tbody.innerHTML = noProductos.outerHTML;
        } else {
            let html = '';
            let subtotalTotal = 0;

            productosArray.forEach((prod, index) => {
                subtotalTotal += prod.subtotal;
                html += `
                    <tr>
                        <td>${prod.descripcion}</td>
                        <td class="text-end">${prod.cantidad}</td>
                        <td class="text-end">$${formatNumber(prod.precio)}</td>
                        <td class="text-end">$${formatNumber(prod.subtotal)}</td>
                        <td class="text-end">
                            <button type="button" class="btn btn-sm btn-danger" onclick="eliminarProducto(${index})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });

            tbody.innerHTML = html;
        }

        // Calcular totales
        const subtotal = productosArray.reduce((sum, p) => sum + p.subtotal, 0);
        const iva = Math.round(subtotal * 0.19);
        const total = subtotal + iva;

        document.getElementById('subtotalDisplay').textContent = '$' + formatNumber(subtotal);
        document.getElementById('ivaDisplay').textContent = '$' + formatNumber(iva);
        document.getElementById('totalDisplay').textContent = '$' + formatNumber(total);

        // Actualizar JSON hidden
        document.getElementById('productosJson').value = JSON.stringify(productosArray);
    }

    function formatNumber(num) {
        return Math.round(num).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }

    function cambiarEstadoOrden(ordenId, estadoActual) {
        document.getElementById('modalOrdenId').value = ordenId;
        document.getElementById('modalNuevoEstado').value = estadoActual;
        new bootstrap.Modal(document.getElementById('cambiarEstadoModal')).show();
    }

    function verDetalleOrden(ordenId) {
        // Aquí podrías implementar un modal con el detalle completo
        alert('Ver detalle de orden #' + ordenId);
    }

    // Validar formulario antes de enviar
    document.getElementById('formNuevaOrden').addEventListener('submit', function(e) {
        if (productosArray.length === 0) {
            e.preventDefault();
            alert('Debe agregar al menos un producto');
            return false;
        }
    });
    </script>
</body>
</html>
