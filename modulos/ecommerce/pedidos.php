<?php
/**
 * PEDIDOS WEB - CONECTA ERP
 * Gestión de pedidos provenientes de la tienda online
 * Incluye estados, procesamiento y generación de facturas
 */

session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

requireLogin();

$usuario_id = $_SESSION['usuario_id'];
$stmt = $conn->prepare("SELECT empresa_id FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$empresa_id = $stmt->get_result()->fetch_assoc()['empresa_id'];
$stmt->close();

$mensaje = '';
$tipo_mensaje = '';

// ==================================================================
// CAMBIAR ESTADO DE PEDIDO
// ==================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_estado'])) {
    $pedido_id = intval($_POST['pedido_id']);
    $nuevo_estado = $_POST['nuevo_estado'];
    $notas = trim($_POST['notas']);

    $stmt = $conn->prepare("UPDATE pedidos_web SET estado = ?, notas_admin = ? WHERE id = ? AND empresa_id = ?");
    $stmt->bind_param("ssii", $nuevo_estado, $notas, $pedido_id, $empresa_id);

    if ($stmt->execute()) {
        // Registrar en historial
        $conn->query("INSERT INTO historial_pedidos_web (pedido_id, estado_anterior, estado_nuevo, notas, usuario_id)
                      SELECT '$pedido_id', estado, '$nuevo_estado', '$notas', $usuario_id FROM pedidos_web WHERE id = $pedido_id");

        $mensaje = "Estado actualizado a: " . strtoupper($nuevo_estado);
        $tipo_mensaje = "success";
    } else {
        $mensaje = "Error al actualizar estado";
        $tipo_mensaje = "danger";
    }
    $stmt->close();
}

// ==================================================================
// GENERAR FACTURA DESDE PEDIDO WEB
// ==================================================================
if (isset($_GET['generar_factura'])) {
    $pedido_id = intval($_GET['generar_factura']);

    // Obtener datos del pedido
    $pedido = $conn->query("SELECT * FROM pedidos_web WHERE id = $pedido_id AND empresa_id = $empresa_id")->fetch_assoc();

    if ($pedido) {
        // Crear factura
        $conn->begin_transaction();
        try {
            // Insertar factura
            $stmt = $conn->prepare("INSERT INTO facturas (empresa_id, cliente_id, tipo_documento, folio, fecha_emision, subtotal, iva, total, estado, origen) VALUES (?, ?, '33', (SELECT COALESCE(MAX(folio), 0) + 1 FROM facturas WHERE empresa_id = ? AND tipo_documento = '33'), NOW(), ?, ?, ?, 'emitida', 'pedido_web')");

            $cliente_id = $pedido['cliente_id'];
            $subtotal = $pedido['subtotal'];
            $iva = $pedido['iva'];
            $total = $pedido['total'];

            $stmt->bind_param("iiiddd", $empresa_id, $cliente_id, $empresa_id, $subtotal, $iva, $total);
            $stmt->execute();
            $factura_id = $conn->insert_id;

            // Insertar detalle desde pedido_web_detalle
            $detalle = $conn->query("SELECT * FROM pedidos_web_detalle WHERE pedido_id = $pedido_id");
            while ($item = $detalle->fetch_assoc()) {
                $stmt = $conn->prepare("INSERT INTO facturas_detalle (factura_id, producto_id, descripcion, cantidad, precio_unitario, subtotal, iva, total) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iisddddd", $factura_id, $item['producto_id'], $item['descripcion'], $item['cantidad'], $item['precio_unitario'], $item['subtotal'], $item['iva'], $item['total']);
                $stmt->execute();
            }

            // Actualizar pedido web
            $conn->query("UPDATE pedidos_web SET factura_id = $factura_id, estado = 'facturado' WHERE id = $pedido_id");

            $conn->commit();
            $mensaje = "Factura generada exitosamente (ID: $factura_id)";
            $tipo_mensaje = "success";
        } catch (Exception $e) {
            $conn->rollback();
            $mensaje = "Error al generar factura: " . $e->getMessage();
            $tipo_mensaje = "danger";
        }
    }
}

// ==================================================================
// LISTADO DE PEDIDOS
// ==================================================================
$estado_filtro = isset($_GET['estado']) ? $_GET['estado'] : '';
$fecha_desde = isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : date('Y-m-01');
$fecha_hasta = isset($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : date('Y-m-d');

$where = "pw.empresa_id = $empresa_id AND pw.fecha_pedido BETWEEN '$fecha_desde' AND '$fecha_hasta 23:59:59'";
if ($estado_filtro) {
    $where .= " AND pw.estado = '" . $conn->real_escape_string($estado_filtro) . "'";
}

$query = "SELECT pw.*, c.razon_social, c.email, c.telefono,
    (SELECT COUNT(*) FROM pedidos_web_detalle WHERE pedido_id = pw.id) as items_count
    FROM pedidos_web pw
    LEFT JOIN clientes c ON pw.cliente_id = c.id
    WHERE $where
    ORDER BY pw.fecha_pedido DESC
    LIMIT 50";

$pedidos = $conn->query($query);

// Estadísticas
$stats = $conn->query("SELECT
    COUNT(*) as total_pedidos,
    SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
    SUM(CASE WHEN estado = 'procesando' THEN 1 ELSE 0 END) as procesando,
    SUM(CASE WHEN estado = 'completado' THEN 1 ELSE 0 END) as completados,
    SUM(total) as ventas_totales
    FROM pedidos_web WHERE $where")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedidos Web - CONECTA ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .badge-pendiente { background: #f59e0b; }
        .badge-procesando { background: #3b82f6; }
        .badge-enviado { background: #8b5cf6; }
        .badge-completado { background: #10b981; }
        .badge-cancelado { background: #ef4444; }
        .badge-facturado { background: #06b6d4; }
    </style>
</head>
<body class="bg-light">

    <?php include '../includes/sidebar.php'; ?>

    <div class="content-wrapper">
        <div class="container-fluid py-4">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2><i class="fas fa-shopping-bag"></i> Pedidos Web</h2>
                    <p class="text-muted">Gestión de pedidos de la tienda online</p>
                </div>
            </div>

            <?php if ($mensaje): ?>
                <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show">
                    <?php echo $mensaje; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Estadísticas -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h6 class="text-muted">Total Pedidos</h6>
                            <h3><?php echo number_format($stats['total_pedidos']); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h6 class="text-muted">Pendientes</h6>
                            <h3 class="text-warning"><?php echo number_format($stats['pendientes']); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h6 class="text-muted">Procesando</h6>
                            <h3 class="text-primary"><?php echo number_format($stats['procesando']); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h6 class="text-muted">Ventas Totales</h6>
                            <h3 class="text-success">$<?php echo number_format($stats['ventas_totales'] ?? 0, 0, ',', '.'); ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtros -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Estado</label>
                            <select name="estado" class="form-select">
                                <option value="">Todos</option>
                                <option value="pendiente" <?php echo $estado_filtro === 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                                <option value="procesando" <?php echo $estado_filtro === 'procesando' ? 'selected' : ''; ?>>Procesando</option>
                                <option value="enviado" <?php echo $estado_filtro === 'enviado' ? 'selected' : ''; ?>>Enviado</option>
                                <option value="completado" <?php echo $estado_filtro === 'completado' ? 'selected' : ''; ?>>Completado</option>
                                <option value="cancelado" <?php echo $estado_filtro === 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Desde</label>
                            <input type="date" name="fecha_desde" class="form-control" value="<?php echo $fecha_desde; ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Hasta</label>
                            <input type="date" name="fecha_hasta" class="form-control" value="<?php echo $fecha_hasta; ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-filter"></i> Filtrar
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tabla de Pedidos -->
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Listado de Pedidos</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#Pedido</th>
                                    <th>Fecha</th>
                                    <th>Cliente</th>
                                    <th class="text-center">Items</th>
                                    <th class="text-end">Total</th>
                                    <th>Método Pago</th>
                                    <th>Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($pedidos->num_rows > 0): ?>
                                    <?php while ($ped = $pedidos->fetch_assoc()): ?>
                                        <tr>
                                            <td><strong>#<?php echo str_pad($ped['id'], 6, '0', STR_PAD_LEFT); ?></strong></td>
                                            <td><?php echo date('d-m-Y H:i', strtotime($ped['fecha_pedido'])); ?></td>
                                            <td>
                                                <?php echo htmlspecialchars($ped['razon_social']); ?><br>
                                                <small class="text-muted"><?php echo $ped['email']; ?></small>
                                            </td>
                                            <td class="text-center"><?php echo $ped['items_count']; ?></td>
                                            <td class="text-end"><strong>$<?php echo number_format($ped['total'], 0, ',', '.'); ?></strong></td>
                                            <td><?php echo strtoupper($ped['metodo_pago']); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo $ped['estado']; ?>">
                                                    <?php echo strtoupper($ped['estado']); ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <button class="btn btn-sm btn-outline-primary" onclick='verDetalle(<?php echo json_encode($ped); ?>)'>
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-warning" onclick='cambiarEstado(<?php echo $ped['id']; ?>)'>
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php if ($ped['estado'] !== 'facturado' && !$ped['factura_id']): ?>
                                                    <a href="?generar_factura=<?php echo $ped['id']; ?>" class="btn btn-sm btn-outline-success"
                                                       onclick="return confirm('¿Generar factura para este pedido?')">
                                                        <i class="fas fa-file-invoice"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <i class="fas fa-shopping-bag fa-3x text-muted mb-3"></i>
                                            <p class="text-muted">No hay pedidos en este período</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Modal Cambiar Estado -->
    <div class="modal fade" id="modalEstado" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" id="formEstado">
                    <input type="hidden" name="pedido_id" id="estado_pedido_id">
                    <div class="modal-header bg-warning">
                        <h5 class="modal-title"><i class="fas fa-edit"></i> Cambiar Estado</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nuevo Estado</label>
                            <select name="nuevo_estado" class="form-select" required>
                                <option value="pendiente">Pendiente</option>
                                <option value="procesando">Procesando</option>
                                <option value="enviado">Enviado</option>
                                <option value="completado">Completado</option>
                                <option value="cancelado">Cancelado</option>
                                <option value="facturado">Facturado</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Notas</label>
                            <textarea name="notas" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="cambiar_estado" class="btn btn-warning">
                            <i class="fas fa-save"></i> Actualizar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function cambiarEstado(pedidoId) {
            document.getElementById('estado_pedido_id').value = pedidoId;
            new bootstrap.Modal(document.getElementById('modalEstado')).show();
        }

        function verDetalle(pedido) {
            alert('Detalle del pedido #' + pedido.id + '\nTotal: $' + pedido.total.toLocaleString());
        }

        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('collapsed');
        }
    </script>

    <?php include '../includes/footer.php'; ?>

</body>
</html>
