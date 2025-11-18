<?php
/**
 * MÓDULO FACTURAS - COMPLETO CON SII
 * Sistema completo de facturación electrónica
 */
session_start();
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../clases/SIIClientReal.php';

requireLogin();

$empresa_id = $_SESSION['empresa_id'];
$success = '';
$error = '';

// Inicializar SII
$siiClient = new SIIClientReal($conn, $empresa_id);

// Crear factura
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_factura'])) {
    try {
        $conn->begin_transaction();

        $cliente_id = intval($_POST['cliente_id']);
        $tipo_documento = intval($_POST['tipo_documento']); // 33=Factura, 34=Exenta
        $fecha_emision = $_POST['fecha_emision'];
        $condicion_pago = $_POST['condicion_pago'];
        $subtotal = floatval($_POST['subtotal']);
        $iva = floatval($_POST['iva']);
        $total = floatval($_POST['total']);
        $observaciones = cleanString($conn, $_POST['observaciones'] ?? '');

        // Obtener próximo folio desde el SII
        $folio = $siiClient->obtenerProximoFolio($tipo_documento);

        if (!$folio) {
            throw new Exception("No hay folios disponibles. Solicite folios al SII.");
        }

        // Insertar factura
        $sql = "INSERT INTO facturas
                (empresa_id, cliente_id, tipo_documento, folio, fecha_emision, fecha_vencimiento,
                 subtotal, iva, total, condicion_pago, observaciones, estado, estado_sii, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'emitida', 'pendiente', NOW())";

        $fecha_venc = date('Y-m-d', strtotime($fecha_emision . ' +30 days'));

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiiissdddss",
            $empresa_id, $cliente_id, $tipo_documento, $folio, $fecha_emision, $fecha_venc,
            $subtotal, $iva, $total, $condicion_pago, $observaciones
        );
        $stmt->execute();
        $factura_id = $conn->insert_id;

        // Insertar detalles
        $productos = json_decode($_POST['productos_json'], true);
        foreach ($productos as $prod) {
            $sql_det = "INSERT INTO facturas_detalle
                        (factura_id, producto_id, descripcion, cantidad, precio_unitario, descuento, subtotal)
                        VALUES (?, ?, ?, ?, ?, ?, ?)";

            $stmt_det = $conn->prepare($sql_det);
            $stmt_det->bind_param("iisdddd",
                $factura_id, $prod['producto_id'], $prod['descripcion'],
                $prod['cantidad'], $prod['precio'], $prod['descuento'], $prod['subtotal']
            );
            $stmt_det->execute();
        }

        $conn->commit();

        // Enviar al SII (opcional, puede hacerse después)
        if (isset($_POST['enviar_sii']) && $_POST['enviar_sii'] == '1') {
            $resultado = $siiClient->enviarDTE($factura_id);
            if ($resultado['success']) {
                $success = "Factura #$folio creada y enviada al SII (Track: {$resultado['track_id']})";
            } else {
                $success = "Factura #$folio creada. Error al enviar al SII: {$resultado['error']}";
            }
        } else {
            $success = "Factura #$folio creada correctamente";
        }

        logAuditoria('crear_factura', 'facturas', $factura_id, null, null, 'Factura creada');

    } catch (Exception $e) {
        $conn->rollback();
        $error = "Error: " . $e->getMessage();
    }
}

// Obtener facturas
$facturas = fetchAll($conn,
    "SELECT f.*, c.nombre as cliente_nombre, c.rut as cliente_rut
     FROM facturas f
     LEFT JOIN clientes c ON f.cliente_id = c.id
     WHERE f.empresa_id = ?
     ORDER BY f.created_at DESC
     LIMIT 100",
    [$empresa_id], 'i'
);

// Obtener clientes
$clientes = fetchAll($conn,
    "SELECT id, nombre, razon_social, rut FROM clientes
     WHERE empresa_id = ? AND estado = 'activo'
     ORDER BY nombre",
    [$empresa_id], 'i'
);

// Obtener productos
$productos = fetchAll($conn,
    "SELECT id, codigo, nombre, precio_venta FROM productos
     WHERE empresa_id = ? AND estado = 'activo'
     ORDER BY nombre",
    [$empresa_id], 'i'
);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facturas - CONECTA ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="bg-light">
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1><i class="fas fa-file-invoice-dollar"></i> Facturas Electrónicas</h1>
                <p class="text-muted mb-0">Facturación con integración SII</p>
            </div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#facturaModal">
                <i class="fas fa-plus"></i> Nueva Factura
            </button>
        </div>

        <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible">
            <i class="fas fa-check"></i> <?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible">
            <i class="fas fa-times"></i> <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Tabla -->
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Folio</th>
                                <th>Tipo</th>
                                <th>Fecha</th>
                                <th>Cliente</th>
                                <th>Total</th>
                                <th>Estado</th>
                                <th>SII</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($facturas as $fac): ?>
                            <tr>
                                <td><strong>#<?= $fac['folio'] ?></strong></td>
                                <td>
                                    <?php
                                    $tipos = [33 => 'Factura', 34 => 'Exenta', 39 => 'Boleta'];
                                    echo $tipos[$fac['tipo_documento']] ?? $fac['tipo_documento'];
                                    ?>
                                </td>
                                <td><?= formatDate($fac['fecha_emision']) ?></td>
                                <td>
                                    <?= htmlspecialchars($fac['cliente_nombre']) ?><br>
                                    <small><?= formatRUT($fac['cliente_rut']) ?></small>
                                </td>
                                <td><strong><?= formatCurrency($fac['total'], 'CLP') ?></strong></td>
                                <td>
                                    <?php
                                    $badges_estado = [
                                        'emitida' => 'info',
                                        'pagada' => 'success',
                                        'anulada' => 'danger',
                                        'vencida' => 'warning'
                                    ];
                                    $badge = $badges_estado[$fac['estado']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?= $badge ?>"><?= ucfirst($fac['estado']) ?></span>
                                </td>
                                <td>
                                    <?php
                                    $badges_sii = [
                                        'pendiente' => 'warning',
                                        'enviado_sii' => 'info',
                                        'aceptado' => 'success',
                                        'rechazado' => 'danger'
                                    ];
                                    $badge_sii = $badges_sii[$fac['estado_sii']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?= $badge_sii ?>"><?= ucfirst($fac['estado_sii']) ?></span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary" title="Ver PDF">
                                            <i class="fas fa-file-pdf"></i>
                                        </button>
                                        <button class="btn btn-outline-info" title="Enviar al SII">
                                            <i class="fas fa-paper-plane"></i>
                                        </button>
                                        <button class="btn btn-outline-success" title="Registrar Pago">
                                            <i class="fas fa-dollar-sign"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>

                            <?php if (empty($facturas)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    No hay facturas registradas
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Nueva Factura -->
    <div class="modal fade" id="facturaModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fas fa-file-invoice-dollar"></i> Nueva Factura Electrónica</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="formFactura">
                    <input type="hidden" name="productos_json" id="productos_json">
                    <input type="hidden" name="subtotal" id="subtotal_hidden">
                    <input type="hidden" name="iva" id="iva_hidden">
                    <input type="hidden" name="total" id="total_hidden">

                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> El sistema asignará automáticamente un folio desde el SII
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Tipo Documento *</label>
                                <select name="tipo_documento" class="form-select" required>
                                    <option value="33">Factura Electrónica (33)</option>
                                    <option value="34">Factura Exenta (34)</option>
                                    <option value="39">Boleta Electrónica (39)</option>
                                </select>
                            </div>
                            <div class="col-md-5 mb-3">
                                <label class="form-label">Cliente *</label>
                                <select name="cliente_id" class="form-select" required>
                                    <option value="">Seleccione...</option>
                                    <?php foreach ($clientes as $cli): ?>
                                    <option value="<?= $cli['id'] ?>">
                                        <?= htmlspecialchars($cli['nombre']) ?> - <?= formatRUT($cli['rut']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Fecha Emisión *</label>
                                <input type="date" name="fecha_emision" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>
                        </div>

                        <!-- Productos -->
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <strong>Detalle de la Factura</strong>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-md-5">
                                        <select id="producto_select" class="form-select">
                                            <option value="">Seleccionar producto...</option>
                                            <?php foreach ($productos as $prod): ?>
                                            <option value="<?= $prod['id'] ?>"
                                                    data-nombre="<?= htmlspecialchars($prod['nombre']) ?>"
                                                    data-precio="<?= $prod['precio_venta'] ?>">
                                                <?= htmlspecialchars($prod['codigo'] . ' - ' . $prod['nombre']) ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <input type="number" id="cantidad_input" class="form-control" placeholder="Cant" value="1" min="1">
                                    </div>
                                    <div class="col-md-3">
                                        <input type="number" id="precio_input" class="form-control" placeholder="Precio" step="1">
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-success w-100" onclick="agregarProducto()">
                                            <i class="fas fa-plus"></i> Agregar
                                        </button>
                                    </div>
                                </div>

                                <div id="productos_lista"></div>

                                <div class="text-end mt-3">
                                    <table class="table table-sm w-auto ms-auto">
                                        <tr>
                                            <td class="text-end"><strong>Subtotal (Neto):</strong></td>
                                            <td class="text-end" id="subtotal_display">$0</td>
                                        </tr>
                                        <tr>
                                            <td class="text-end"><strong>IVA (19%):</strong></td>
                                            <td class="text-end" id="iva_display">$0</td>
                                        </tr>
                                        <tr class="table-success">
                                            <td class="text-end"><strong>TOTAL:</strong></td>
                                            <td class="text-end"><h4 id="total_display" class="mb-0 text-success">$0</h4></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Condición de Pago</label>
                                <select name="condicion_pago" class="form-select">
                                    <option value="contado">Contado</option>
                                    <option value="credito">Crédito</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Observaciones</label>
                                <textarea name="observaciones" class="form-control" rows="2"></textarea>
                            </div>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="enviar_sii" value="1" id="enviar_sii" checked>
                            <label class="form-check-label" for="enviar_sii">
                                <i class="fas fa-paper-plane text-primary"></i> Enviar automáticamente al SII
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="guardar_factura" class="btn btn-success">
                            <i class="fas fa-save"></i> Emitir Factura
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

    document.getElementById('producto_select').addEventListener('change', function() {
        const option = this.options[this.selectedIndex];
        if (option.value) {
            document.getElementById('precio_input').value = option.dataset.precio;
        }
    });

    function agregarProducto() {
        const select = document.getElementById('producto_select');
        const option = select.options[select.selectedIndex];

        if (!option.value) {
            alert('Seleccione un producto');
            return;
        }

        const cantidad = parseFloat(document.getElementById('cantidad_input').value);
        const precio = parseFloat(document.getElementById('precio_input').value);

        if (cantidad <= 0 || precio <= 0) {
            alert('Cantidad y precio deben ser mayores a 0');
            return;
        }

        const subtotal = cantidad * precio;

        productosArray.push({
            producto_id: option.value,
            descripcion: option.dataset.nombre,
            cantidad: cantidad,
            precio: precio,
            descuento: 0,
            subtotal: subtotal
        });

        renderProductos();
        select.value = '';
        document.getElementById('cantidad_input').value = 1;
        document.getElementById('precio_input').value = '';
    }

    function eliminarProducto(index) {
        productosArray.splice(index, 1);
        renderProductos();
    }

    function renderProductos() {
        const lista = document.getElementById('productos_lista');
        lista.innerHTML = '';

        let subtotalTotal = 0;

        productosArray.forEach((prod, index) => {
            subtotalTotal += prod.subtotal;

            lista.innerHTML += `
                <div class="alert alert-light">
                    <div class="row align-items-center">
                        <div class="col-md-5">
                            <strong>${prod.descripcion}</strong>
                        </div>
                        <div class="col-md-2 text-center">
                            ${prod.cantidad}
                        </div>
                        <div class="col-md-2 text-end">
                            $${Math.round(prod.precio).toLocaleString('es-CL')}
                        </div>
                        <div class="col-md-2 text-end">
                            <strong>$${Math.round(prod.subtotal).toLocaleString('es-CL')}</strong>
                        </div>
                        <div class="col-md-1 text-center">
                            <button type="button" class="btn btn-sm btn-danger" onclick="eliminarProducto(${index})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
        });

        // Calcular totales
        const iva = Math.round(subtotalTotal * 0.19);
        const total = subtotalTotal + iva;

        document.getElementById('subtotal_display').textContent = '$' + Math.round(subtotalTotal).toLocaleString('es-CL');
        document.getElementById('iva_display').textContent = '$' + iva.toLocaleString('es-CL');
        document.getElementById('total_display').textContent = '$' + total.toLocaleString('es-CL');

        // Actualizar campos hidden
        document.getElementById('productos_json').value = JSON.stringify(productosArray);
        document.getElementById('subtotal_hidden').value = Math.round(subtotalTotal);
        document.getElementById('iva_hidden').value = iva;
        document.getElementById('total_hidden').value = total;
    }
    </script>
</body>
</html>
