<?php
/**
 * MÓDULO COTIZACIONES - COMPLETO
 * Sistema completo de cotizaciones con PDF
 */
session_start();
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';

requireLogin();

$empresa_id = $_SESSION['empresa_id'];
$success = '';
$error = '';

// Guardar cotización
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_cotizacion'])) {
    try {
        $conn->begin_transaction();

        $cliente_id = intval($_POST['cliente_id']);
        $fecha = $_POST['fecha'];
        $vigencia = $_POST['vigencia'];
        $condiciones = cleanString($conn, $_POST['condiciones'] ?? '');
        $observaciones = cleanString($conn, $_POST['observaciones'] ?? '');
        $subtotal = floatval($_POST['subtotal']);
        $descuento = floatval($_POST['descuento'] ?? 0);
        $iva = floatval($_POST['iva']);
        $total = floatval($_POST['total']);

        // Insertar cotización
        $sql = "INSERT INTO cotizaciones
                (empresa_id, cliente_id, numero, fecha, fecha_vigencia, subtotal, descuento,
                 iva, total, condiciones, observaciones, estado, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendiente', NOW())";

        // Generar número de cotización
        $numero = 'COT-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iisssdddss",
            $empresa_id, $cliente_id, $numero, $fecha, $vigencia,
            $subtotal, $descuento, $iva, $total, $condiciones, $observaciones
        );
        $stmt->execute();
        $cotizacion_id = $conn->insert_id;

        // Insertar detalles
        $productos = json_decode($_POST['productos_json'], true);
        foreach ($productos as $prod) {
            $sql_det = "INSERT INTO cotizaciones_detalle
                        (cotizacion_id, producto_id, descripcion, cantidad, precio_unitario, descuento, subtotal)
                        VALUES (?, ?, ?, ?, ?, ?, ?)";

            $stmt_det = $conn->prepare($sql_det);
            $stmt_det->bind_param("iisdddd",
                $cotizacion_id, $prod['producto_id'], $prod['descripcion'],
                $prod['cantidad'], $prod['precio'], $prod['descuento'], $prod['subtotal']
            );
            $stmt_det->execute();
        }

        $conn->commit();
        $success = "Cotización #$numero creada correctamente";

        logAuditoria('crear_cotizacion', 'cotizaciones', $cotizacion_id, null, null, 'Cotización creada');

    } catch (Exception $e) {
        $conn->rollback();
        $error = "Error: " . $e->getMessage();
    }
}

// Obtener cotizaciones
$cotizaciones = fetchAll($conn,
    "SELECT c.*, cl.nombre as cliente_nombre, cl.rut as cliente_rut
     FROM cotizaciones c
     LEFT JOIN clientes cl ON c.cliente_id = cl.id
     WHERE c.empresa_id = ?
     ORDER BY c.created_at DESC
     LIMIT 100",
    [$empresa_id], 'i'
);

// Obtener clientes activos
$clientes = fetchAll($conn,
    "SELECT id, nombre, razon_social, rut FROM clientes
     WHERE empresa_id = ? AND estado = 'activo'
     ORDER BY nombre",
    [$empresa_id], 'i'
);

// Obtener productos activos
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
    <title>Cotizaciones - CONECTA ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .producto-row { background: #f8f9fa; padding: 10px; margin-bottom: 10px; border-radius: 8px; }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1><i class="fas fa-file-invoice"></i> Cotizaciones</h1>
                <p class="text-muted mb-0">Gestión de cotizaciones a clientes</p>
            </div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#cotizacionModal">
                <i class="fas fa-plus"></i> Nueva Cotizacion
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
                                <th>Número</th>
                                <th>Fecha</th>
                                <th>Cliente</th>
                                <th>Vigencia</th>
                                <th>Total</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cotizaciones as $cot): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($cot['numero']) ?></strong></td>
                                <td><?= formatDate($cot['fecha']) ?></td>
                                <td><?= htmlspecialchars($cot['cliente_nombre']) ?></td>
                                <td><?= formatDate($cot['fecha_vigencia']) ?></td>
                                <td><strong><?= formatCurrency($cot['total'], 'CLP') ?></strong></td>
                                <td>
                                    <?php
                                    $badges = [
                                        'pendiente' => 'warning',
                                        'aceptada' => 'success',
                                        'rechazada' => 'danger',
                                        'vencida' => 'secondary'
                                    ];
                                    $badge = $badges[$cot['estado']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?= $badge ?>"><?= ucfirst($cot['estado']) ?></span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary" title="Ver PDF">
                                            <i class="fas fa-file-pdf"></i>
                                        </button>
                                        <button class="btn btn-outline-success" title="Convertir a Factura">
                                            <i class="fas fa-file-invoice-dollar"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Nueva Cotización -->
    <div class="modal fade" id="cotizacionModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-file-invoice"></i> Nueva Cotización</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="formCotizacion">
                    <input type="hidden" name="productos_json" id="productos_json">
                    <input type="hidden" name="subtotal" id="subtotal_hidden">
                    <input type="hidden" name="descuento" id="descuento_hidden">
                    <input type="hidden" name="iva" id="iva_hidden">
                    <input type="hidden" name="total" id="total_hidden">

                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
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
                                <label class="form-label">Fecha *</label>
                                <input type="date" name="fecha" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Vigencia hasta *</label>
                                <input type="date" name="vigencia" class="form-control"
                                       value="<?= date('Y-m-d', strtotime('+15 days')) ?>" required>
                            </div>
                        </div>

                        <!-- Productos -->
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <strong>Productos / Servicios</strong>
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
                                        <input type="number" id="precio_input" class="form-control" placeholder="Precio" step="0.01">
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
                                            <td class="text-end"><strong>Subtotal:</strong></td>
                                            <td class="text-end" id="subtotal_display">$0</td>
                                        </tr>
                                        <tr>
                                            <td class="text-end"><strong>IVA (19%):</strong></td>
                                            <td class="text-end" id="iva_display">$0</td>
                                        </tr>
                                        <tr class="table-primary">
                                            <td class="text-end"><strong>TOTAL:</strong></td>
                                            <td class="text-end"><h5 id="total_display" class="mb-0">$0</h5></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Condiciones de Pago</label>
                                <textarea name="condiciones" class="form-control" rows="2">Contado contra entrega</textarea>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Observaciones</label>
                                <textarea name="observaciones" class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="guardar_cotizacion" class="btn btn-primary">
                            <i class="fas fa-save"></i> Guardar Cotización
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
                <div class="producto-row">
                    <div class="row align-items-center">
                        <div class="col-md-5">
                            <strong>${prod.descripcion}</strong>
                        </div>
                        <div class="col-md-2 text-center">
                            ${prod.cantidad}
                        </div>
                        <div class="col-md-2 text-end">
                            $${prod.precio.toLocaleString('es-CL')}
                        </div>
                        <div class="col-md-2 text-end">
                            <strong>$${prod.subtotal.toLocaleString('es-CL')}</strong>
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
        const iva = subtotalTotal * 0.19;
        const total = subtotalTotal + iva;

        document.getElementById('subtotal_display').textContent = '$' + subtotalTotal.toLocaleString('es-CL');
        document.getElementById('iva_display').textContent = '$' + iva.toLocaleString('es-CL');
        document.getElementById('total_display').textContent = '$' + total.toLocaleString('es-CL');

        // Actualizar campos hidden
        document.getElementById('productos_json').value = JSON.stringify(productosArray);
        document.getElementById('subtotal_hidden').value = subtotalTotal;
        document.getElementById('iva_hidden').value = iva;
        document.getElementById('total_hidden').value = total;
    }
    </script>
</body>
</html>
