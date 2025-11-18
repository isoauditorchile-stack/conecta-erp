<?php
/**
 * PUNTO DE VENTA (POS) - CONECTA ERP
 * Sistema completo de caja para ventas rápidas
 */
session_start();
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../clases/SIIClientReal.php';

requireLogin();

$empresa_id = $_SESSION['empresa_id'];
$usuario_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Procesar venta
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['procesar_venta'])) {
        try {
            $conn->begin_transaction();

            // Datos de la venta
            $productos_json = json_decode($_POST['productos_json'], true);
            if (empty($productos_json)) {
                throw new Exception("No hay productos en el carrito");
            }

            $cliente_id = intval($_POST['cliente_id']) ?: null;
            $tipo_documento = intval($_POST['tipo_documento']); // 39 = Boleta, 33 = Factura
            $metodo_pago = $_POST['metodo_pago'];
            $monto_recibido = floatval($_POST['monto_recibido']);

            // Calcular totales
            $subtotal = 0;
            foreach ($productos_json as $prod) {
                $subtotal += floatval($prod['subtotal']);
            }

            $iva = round($subtotal * 0.19, 0);
            $total = $subtotal + $iva;

            // Validar pago
            if ($metodo_pago === 'efectivo' && $monto_recibido < $total) {
                throw new Exception("El monto recibido es insuficiente");
            }

            $vuelto = ($metodo_pago === 'efectivo') ? ($monto_recibido - $total) : 0;

            // Obtener folio si es necesario
            $folio = null;
            $enviar_sii = false;

            if ($tipo_documento === 39 || $tipo_documento === 33) {
                $siiClient = new SIIClientReal($conn, $empresa_id);
                $folio = $siiClient->obtenerProximoFolio($tipo_documento);

                if (!$folio) {
                    throw new Exception("No hay folios disponibles para este tipo de documento");
                }
            }

            // Insertar venta
            $sql_venta = "INSERT INTO ventas_pos
                         (empresa_id, usuario_id, cliente_id, tipo_documento, folio, fecha_venta,
                          subtotal, iva, total, metodo_pago, monto_recibido, vuelto, estado)
                         VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?, 'completada')";

            $stmt = $conn->prepare($sql_venta);
            $stmt->bind_param('iiiiidddss',
                $empresa_id,
                $usuario_id,
                $cliente_id,
                $tipo_documento,
                $folio,
                $subtotal,
                $iva,
                $total,
                $metodo_pago,
                $monto_recibido,
                $vuelto
            );
            $stmt->execute();
            $venta_id = $conn->insert_id();

            // Insertar detalle de productos
            foreach ($productos_json as $prod) {
                $sql_detalle = "INSERT INTO ventas_pos_detalle
                               (venta_id, producto_id, descripcion, cantidad, precio_unitario, subtotal)
                               VALUES (?, ?, ?, ?, ?, ?)";
                $stmt_detalle = $conn->prepare($sql_detalle);
                $stmt_detalle->bind_param('iisddd',
                    $venta_id,
                    $prod['producto_id'],
                    $prod['descripcion'],
                    $prod['cantidad'],
                    $prod['precio'],
                    $prod['subtotal']
                );
                $stmt_detalle->execute();

                // Actualizar stock
                executeQuery($conn,
                    "UPDATE productos SET stock = stock - ? WHERE id = ?",
                    [$prod['cantidad'], $prod['producto_id']], 'di'
                );
            }

            // Marcar folio como usado
            if ($folio) {
                executeQuery($conn,
                    "UPDATE folios SET estado = 'usado', fecha_uso = NOW() WHERE folio = ? AND tipo_documento = ?",
                    [$folio, $tipo_documento], 'ii'
                );
            }

            // Registrar movimiento de caja
            $sql_caja = "INSERT INTO movimientos_caja
                        (empresa_id, usuario_id, tipo, monto, concepto, referencia_id, fecha)
                        VALUES (?, ?, 'ingreso', ?, ?, ?, NOW())";
            $concepto = "Venta POS #" . $venta_id;
            $stmt_caja = $conn->prepare($sql_caja);
            $stmt_caja->bind_param('iissi',
                $empresa_id,
                $usuario_id,
                $total,
                $concepto,
                $venta_id
            );
            $stmt_caja->execute();

            $conn->commit();

            // Redireccionar a comprobante
            $_SESSION['ultima_venta_id'] = $venta_id;
            header('Location: pos.php?venta_completada=1');
            exit;

        } catch (Exception $e) {
            $conn->rollback();
            $error = 'Error: ' . $e->getMessage();
        }
    }

    // Abrir caja
    if (isset($_POST['abrir_caja'])) {
        $monto_inicial = floatval($_POST['monto_inicial']);

        executeQuery($conn,
            "INSERT INTO cajas (empresa_id, usuario_id, fecha_apertura, monto_inicial, estado)
             VALUES (?, ?, NOW(), ?, 'abierta')",
            [$empresa_id, $usuario_id, $monto_inicial], 'iid'
        );

        $success = 'Caja abierta correctamente';
    }

    // Cerrar caja
    if (isset($_POST['cerrar_caja'])) {
        $caja_id = intval($_POST['caja_id']);

        // Calcular totales del día
        $totales = fetchOne($conn,
            "SELECT SUM(total) as total_ventas, COUNT(*) as num_ventas
             FROM ventas_pos
             WHERE empresa_id = ? AND usuario_id = ? AND DATE(fecha_venta) = CURDATE()",
            [$empresa_id, $usuario_id], 'ii'
        );

        $caja_info = fetchOne($conn,
            "SELECT monto_inicial FROM cajas WHERE id = ?",
            [$caja_id], 'i'
        );

        $monto_final = ($caja_info['monto_inicial'] ?? 0) + ($totales['total_ventas'] ?? 0);

        executeQuery($conn,
            "UPDATE cajas SET fecha_cierre = NOW(), monto_final = ?, total_ventas = ?, num_ventas = ?, estado = 'cerrada'
             WHERE id = ?",
            [$monto_final, $totales['total_ventas'], $totales['num_ventas'], $caja_id], 'ddii'
        );

        $success = 'Caja cerrada. Total ventas: ' . formatCurrency($totales['total_ventas'] ?? 0, 'CLP');
    }
}

// Verificar si hay caja abierta
$caja_abierta = fetchOne($conn,
    "SELECT * FROM cajas WHERE empresa_id = ? AND usuario_id = ? AND estado = 'abierta' AND DATE(fecha_apertura) = CURDATE()",
    [$empresa_id, $usuario_id], 'ii'
);

// Obtener productos para venta rápida
$productos = fetchAll($conn,
    "SELECT id, codigo, nombre, precio_venta, stock, categoria
     FROM productos
     WHERE empresa_id = ? AND activo = 1
     ORDER BY nombre",
    [$empresa_id], 'i'
);

// Obtener clientes frecuentes
$clientes = fetchAll($conn,
    "SELECT id, nombre, rut, email FROM clientes WHERE empresa_id = ? AND activo = 1 ORDER BY nombre LIMIT 50",
    [$empresa_id], 'i'
);

// Ventas del día
$ventas_hoy = fetchAll($conn,
    "SELECT v.*, c.nombre as cliente_nombre
     FROM ventas_pos v
     LEFT JOIN clientes c ON v.cliente_id = c.id
     WHERE v.empresa_id = ? AND DATE(v.fecha_venta) = CURDATE()
     ORDER BY v.id DESC
     LIMIT 20",
    [$empresa_id], 'i'
);

// Estadísticas del día
$stats_hoy = fetchOne($conn,
    "SELECT
        COUNT(*) as num_ventas,
        SUM(total) as total_ventas,
        AVG(total) as promedio_venta
     FROM ventas_pos
     WHERE empresa_id = ? AND DATE(fecha_venta) = CURDATE()",
    [$empresa_id], 'i'
);

// Verificar si se completó una venta
$venta_completada = isset($_GET['venta_completada']) && isset($_SESSION['ultima_venta_id']);
$ultima_venta = null;
if ($venta_completada) {
    $ultima_venta = fetchOne($conn,
        "SELECT v.*, c.nombre as cliente_nombre, c.rut as cliente_rut
         FROM ventas_pos v
         LEFT JOIN clientes c ON v.cliente_id = c.id
         WHERE v.id = ?",
        [$_SESSION['ultima_venta_id']], 'i'
    );

    $ultima_venta_detalle = fetchAll($conn,
        "SELECT * FROM ventas_pos_detalle WHERE venta_id = ?",
        [$_SESSION['ultima_venta_id']], 'i'
    );
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Punto de Venta - CONECTA ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .pos-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .producto-card {
            cursor: pointer;
            transition: all 0.2s;
            border: 2px solid transparent;
            height: 100%;
        }
        .producto-card:hover {
            border-color: #667eea;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        .carrito-section {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            position: sticky;
            top: 20px;
        }
        .total-display {
            font-size: 2.5rem;
            font-weight: bold;
            color: #667eea;
        }
        .btn-finalizar {
            font-size: 1.2rem;
            padding: 1rem;
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            border: none;
            box-shadow: 0 4px 15px rgba(17, 153, 142, 0.3);
        }
        .btn-finalizar:hover {
            background: linear-gradient(135deg, #0d7a70 0%, #2dd164 100%);
            box-shadow: 0 6px 20px rgba(17, 153, 142, 0.4);
        }
        .producto-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 1rem;
        }
        .caja-cerrada-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }
        @media print {
            body * {
                visibility: hidden;
            }
            #comprobanteImpresion, #comprobanteImpresion * {
                visibility: visible;
            }
            #comprobanteImpresion {
                position: absolute;
                left: 0;
                top: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Header POS -->
    <div class="pos-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-md-3">
                    <h4 class="mb-0"><i class="fas fa-cash-register"></i> PUNTO DE VENTA</h4>
                </div>
                <div class="col-md-6 text-center">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar-day"></i> <?= formatDate(date('Y-m-d')) ?>
                        <span class="ms-3"><i class="fas fa-clock"></i> <span id="reloj"></span></span>
                    </h5>
                </div>
                <div class="col-md-3 text-end">
                    <?php if ($caja_abierta): ?>
                    <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#cerrarCajaModal">
                        <i class="fas fa-lock"></i> Cerrar Caja
                    </button>
                    <?php endif; ?>
                    <a href="/user/dashboard_user.php" class="btn btn-light ms-2">
                        <i class="fas fa-home"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php if (!$caja_abierta && !$venta_completada): ?>
    <!-- Overlay Caja Cerrada -->
    <div class="caja-cerrada-overlay">
        <div class="card" style="max-width: 500px; width: 90%;">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-lock-open"></i> Abrir Caja</h5>
            </div>
            <div class="card-body">
                <p>Para comenzar a trabajar, debe abrir la caja del día.</p>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Monto Inicial en Caja</label>
                        <input type="number" name="monto_inicial" class="form-control form-control-lg"
                               placeholder="0" min="0" step="1000" required autofocus>
                    </div>
                    <button type="submit" name="abrir_caja" class="btn btn-primary w-100 btn-lg">
                        <i class="fas fa-lock-open"></i> Abrir Caja
                    </button>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($venta_completada && $ultima_venta): ?>
    <!-- Comprobante de Venta -->
    <div class="container mt-4">
        <div class="card">
            <div class="card-body text-center">
                <div class="mb-4">
                    <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                    <h2 class="mt-3">¡Venta Completada!</h2>
                </div>

                <div id="comprobanteImpresion" style="max-width: 400px; margin: 0 auto; text-align: left;">
                    <div class="text-center mb-3">
                        <h5><strong>CONECTA ERP</strong></h5>
                        <p class="mb-1"><?php
                        $empresa_info = fetchOne($conn, "SELECT nombre, rut FROM empresas WHERE id = ?", [$empresa_id], 'i');
                        echo htmlspecialchars($empresa_info['nombre'] ?? 'Empresa');
                        ?></p>
                        <p class="mb-1">RUT: <?= formatRUT($empresa_info['rut'] ?? '') ?></p>
                        <hr>
                    </div>

                    <p><strong>Documento:</strong> <?= $ultima_venta['tipo_documento'] == 39 ? 'Boleta' : 'Factura' ?>
                       <?= $ultima_venta['folio'] ? '#' . $ultima_venta['folio'] : '' ?></p>
                    <p><strong>Fecha:</strong> <?= formatDateTime($ultima_venta['fecha_venta']) ?></p>
                    <?php if ($ultima_venta['cliente_nombre']): ?>
                    <p><strong>Cliente:</strong> <?= htmlspecialchars($ultima_venta['cliente_nombre']) ?></p>
                    <?php endif; ?>
                    <hr>

                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th class="text-end">Cant.</th>
                                <th class="text-end">Precio</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ultima_venta_detalle as $det): ?>
                            <tr>
                                <td><?= htmlspecialchars($det['descripcion']) ?></td>
                                <td class="text-end"><?= $det['cantidad'] ?></td>
                                <td class="text-end"><?= formatCurrency($det['precio_unitario'], 'CLP') ?></td>
                                <td class="text-end"><?= formatCurrency($det['subtotal'], 'CLP') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3"><strong>Subtotal:</strong></td>
                                <td class="text-end"><strong><?= formatCurrency($ultima_venta['subtotal'], 'CLP') ?></strong></td>
                            </tr>
                            <tr>
                                <td colspan="3"><strong>IVA (19%):</strong></td>
                                <td class="text-end"><strong><?= formatCurrency($ultima_venta['iva'], 'CLP') ?></strong></td>
                            </tr>
                            <tr>
                                <td colspan="3"><strong>TOTAL:</strong></td>
                                <td class="text-end"><strong><?= formatCurrency($ultima_venta['total'], 'CLP') ?></strong></td>
                            </tr>
                            <tr>
                                <td colspan="3">Pago (<?= ucfirst($ultima_venta['metodo_pago']) ?>):</td>
                                <td class="text-end"><?= formatCurrency($ultima_venta['monto_recibido'], 'CLP') ?></td>
                            </tr>
                            <?php if ($ultima_venta['vuelto'] > 0): ?>
                            <tr>
                                <td colspan="3"><strong>Vuelto:</strong></td>
                                <td class="text-end"><strong><?= formatCurrency($ultima_venta['vuelto'], 'CLP') ?></strong></td>
                            </tr>
                            <?php endif; ?>
                        </tfoot>
                    </table>

                    <div class="text-center mt-3">
                        <p class="mb-0">¡Gracias por su compra!</p>
                    </div>
                </div>

                <div class="mt-4">
                    <button onclick="window.print()" class="btn btn-primary btn-lg me-2">
                        <i class="fas fa-print"></i> Imprimir
                    </button>
                    <a href="pos.php" class="btn btn-success btn-lg">
                        <i class="fas fa-plus"></i> Nueva Venta
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php elseif ($caja_abierta): ?>
    <!-- Interfaz POS -->
    <div class="container-fluid mt-4">
        <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible">
            <i class="fas fa-check"></i> <?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible">
            <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="row">
            <!-- Columna de Productos -->
            <div class="col-md-8">
                <!-- Buscador -->
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="input-group input-group-lg">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" id="buscadorProducto" class="form-control"
                                   placeholder="Buscar por código o nombre..." autofocus>
                        </div>
                    </div>
                </div>

                <!-- Grid de Productos -->
                <div class="producto-grid" id="productosGrid">
                    <?php foreach ($productos as $prod): ?>
                    <div class="card producto-card"
                         onclick="agregarAlCarrito(<?= $prod['id'] ?>, '<?= htmlspecialchars($prod['nombre']) ?>', <?= $prod['precio_venta'] ?>, <?= $prod['stock'] ?>)"
                         data-nombre="<?= strtolower($prod['nombre']) ?>"
                         data-codigo="<?= strtolower($prod['codigo']) ?>">
                        <div class="card-body text-center p-2">
                            <div class="mb-2">
                                <i class="fas fa-box fa-2x text-primary"></i>
                            </div>
                            <h6 class="mb-1" style="font-size: 0.85rem;"><?= htmlspecialchars($prod['nombre']) ?></h6>
                            <p class="mb-1"><small class="text-muted"><?= htmlspecialchars($prod['codigo']) ?></small></p>
                            <p class="mb-1"><strong class="text-success"><?= formatCurrency($prod['precio_venta'], 'CLP') ?></strong></p>
                            <small class="text-muted">Stock: <?= $prod['stock'] ?></small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Columna de Carrito -->
            <div class="col-md-4">
                <div class="carrito-section p-3">
                    <h5 class="mb-3"><i class="fas fa-shopping-cart"></i> Carrito de Compra</h5>

                    <!-- Lista de productos en carrito -->
                    <div id="carritoLista" style="max-height: 300px; overflow-y: auto;">
                        <p class="text-muted text-center" id="carritoVacio">
                            <i class="fas fa-shopping-cart fa-3x mb-2 d-block"></i>
                            Carrito vacío
                        </p>
                    </div>

                    <hr>

                    <!-- Totales -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal:</span>
                            <strong id="subtotalDisplay">$0</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>IVA (19%):</span>
                            <strong id="ivaDisplay">$0</strong>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between">
                            <h4>TOTAL:</h4>
                            <h4 class="total-display" id="totalDisplay">$0</h4>
                        </div>
                    </div>

                    <!-- Botones de acción -->
                    <button class="btn btn-danger w-100 mb-2" onclick="limpiarCarrito()">
                        <i class="fas fa-trash"></i> Limpiar
                    </button>
                    <button class="btn btn-finalizar w-100" onclick="abrirModalPago()" id="btnFinalizar" disabled>
                        <i class="fas fa-check-circle"></i> FINALIZAR VENTA
                    </button>
                </div>

                <!-- Ventas del día -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-chart-line"></i> Resumen del Día</h6>
                    </div>
                    <div class="card-body">
                        <p class="mb-1"><strong>Ventas:</strong> <?= $stats_hoy['num_ventas'] ?? 0 ?></p>
                        <p class="mb-0"><strong>Total:</strong> <?= formatCurrency($stats_hoy['total_ventas'] ?? 0, 'CLP') ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Pago -->
    <div class="modal fade" id="pagoModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fas fa-money-bill-wave"></i> Procesar Pago</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="formPago">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Cliente (Opcional)</label>
                                <select name="cliente_id" class="form-select">
                                    <option value="">Consumidor Final</option>
                                    <?php foreach ($clientes as $cli): ?>
                                    <option value="<?= $cli['id'] ?>">
                                        <?= htmlspecialchars($cli['nombre']) ?> - <?= formatRUT($cli['rut']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tipo de Documento *</label>
                                <select name="tipo_documento" class="form-select" required>
                                    <option value="39">Boleta Electrónica (39)</option>
                                    <option value="33">Factura Electrónica (33)</option>
                                </select>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Método de Pago *</label>
                                <select name="metodo_pago" id="metodoPago" class="form-select" required>
                                    <option value="efectivo">Efectivo</option>
                                    <option value="tarjeta_debito">Tarjeta de Débito</option>
                                    <option value="tarjeta_credito">Tarjeta de Crédito</option>
                                    <option value="transferencia">Transferencia</option>
                                </select>
                            </div>
                            <div class="col-md-12" id="efectivoSection">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h5 class="mb-3">Total a Pagar: <span class="text-primary" id="totalPagar">$0</span></h5>
                                        <div class="mb-3">
                                            <label class="form-label">Monto Recibido</label>
                                            <input type="number" name="monto_recibido" id="montoRecibido"
                                                   class="form-control form-control-lg" min="0" step="100"
                                                   placeholder="0">
                                        </div>
                                        <div class="alert alert-success" id="vueltoSection" style="display:none;">
                                            <h4 class="mb-0">Vuelto: <span id="vueltoDisplay">$0</span></h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <input type="hidden" name="productos_json" id="productosJsonPago">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="procesar_venta" class="btn btn-success btn-lg">
                            <i class="fas fa-check"></i> CONFIRMAR VENTA
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Cerrar Caja -->
    <div class="modal fade" id="cerrarCajaModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title"><i class="fas fa-lock"></i> Cerrar Caja</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="caja_id" value="<?= $caja_abierta['id'] ?? '' ?>">
                        <p>¿Está seguro que desea cerrar la caja?</p>
                        <div class="alert alert-info">
                            <p class="mb-1"><strong>Ventas del día:</strong> <?= $stats_hoy['num_ventas'] ?? 0 ?></p>
                            <p class="mb-0"><strong>Total:</strong> <?= formatCurrency($stats_hoy['total_ventas'] ?? 0, 'CLP') ?></p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="cerrar_caja" class="btn btn-warning">
                            <i class="fas fa-lock"></i> Cerrar Caja
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php include __DIR__ . '/../../user/includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Reloj
    function actualizarReloj() {
        const ahora = new Date();
        const horas = String(ahora.getHours()).padStart(2, '0');
        const minutos = String(ahora.getMinutes()).padStart(2, '0');
        const segundos = String(ahora.getSeconds()).padStart(2, '0');
        document.getElementById('reloj').textContent = `${horas}:${minutos}:${segundos}`;
    }
    setInterval(actualizarReloj, 1000);
    actualizarReloj();

    // Carrito
    let carritoItems = [];

    function agregarAlCarrito(id, nombre, precio, stock) {
        // Verificar si ya existe en el carrito
        const existente = carritoItems.find(item => item.id === id);

        if (existente) {
            if (existente.cantidad >= stock) {
                alert('Stock insuficiente');
                return;
            }
            existente.cantidad++;
            existente.subtotal = existente.cantidad * existente.precio;
        } else {
            carritoItems.push({
                id: id,
                nombre: nombre,
                precio: precio,
                cantidad: 1,
                subtotal: precio,
                stock: stock
            });
        }

        actualizarCarrito();
    }

    function eliminarDelCarrito(index) {
        carritoItems.splice(index, 1);
        actualizarCarrito();
    }

    function cambiarCantidad(index, delta) {
        const item = carritoItems[index];
        const nuevaCantidad = item.cantidad + delta;

        if (nuevaCantidad <= 0) {
            eliminarDelCarrito(index);
            return;
        }

        if (nuevaCantidad > item.stock) {
            alert('Stock insuficiente');
            return;
        }

        item.cantidad = nuevaCantidad;
        item.subtotal = item.cantidad * item.precio;
        actualizarCarrito();
    }

    function actualizarCarrito() {
        const lista = document.getElementById('carritoLista');
        const vacio = document.getElementById('carritoVacio');

        if (carritoItems.length === 0) {
            vacio.style.display = 'block';
            lista.innerHTML = vacio.outerHTML;
            document.getElementById('btnFinalizar').disabled = true;
        } else {
            vacio.style.display = 'none';
            let html = '';

            carritoItems.forEach((item, index) => {
                html += `
                    <div class="d-flex align-items-center mb-2 pb-2 border-bottom">
                        <div class="flex-grow-1">
                            <h6 class="mb-0">${item.nombre}</h6>
                            <small class="text-muted">${formatCurrency(item.precio)}</small>
                        </div>
                        <div class="btn-group btn-group-sm me-2">
                            <button class="btn btn-outline-secondary" onclick="cambiarCantidad(${index}, -1)">-</button>
                            <button class="btn btn-outline-secondary" disabled>${item.cantidad}</button>
                            <button class="btn btn-outline-secondary" onclick="cambiarCantidad(${index}, 1)">+</button>
                        </div>
                        <div class="text-end" style="width: 80px;">
                            <strong>${formatCurrency(item.subtotal)}</strong>
                        </div>
                        <button class="btn btn-sm btn-danger ms-2" onclick="eliminarDelCarrito(${index})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                `;
            });

            lista.innerHTML = html;
            document.getElementById('btnFinalizar').disabled = false;
        }

        // Calcular totales
        const subtotal = carritoItems.reduce((sum, item) => sum + item.subtotal, 0);
        const iva = Math.round(subtotal * 0.19);
        const total = subtotal + iva;

        document.getElementById('subtotalDisplay').textContent = formatCurrency(subtotal);
        document.getElementById('ivaDisplay').textContent = formatCurrency(iva);
        document.getElementById('totalDisplay').textContent = formatCurrency(total);
    }

    function limpiarCarrito() {
        if (carritoItems.length === 0) return;
        if (confirm('¿Limpiar el carrito?')) {
            carritoItems = [];
            actualizarCarrito();
        }
    }

    function formatCurrency(amount) {
        return '$' + Math.round(amount).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }

    // Buscador de productos
    document.getElementById('buscadorProducto').addEventListener('input', function() {
        const busqueda = this.value.toLowerCase();
        const productos = document.querySelectorAll('.producto-card');

        productos.forEach(producto => {
            const nombre = producto.getAttribute('data-nombre');
            const codigo = producto.getAttribute('data-codigo');

            if (nombre.includes(busqueda) || codigo.includes(busqueda)) {
                producto.style.display = '';
            } else {
                producto.style.display = 'none';
            }
        });
    });

    // Modal de pago
    function abrirModalPago() {
        const subtotal = carritoItems.reduce((sum, item) => sum + item.subtotal, 0);
        const iva = Math.round(subtotal * 0.19);
        const total = subtotal + iva;

        document.getElementById('totalPagar').textContent = formatCurrency(total);
        document.getElementById('productosJsonPago').value = JSON.stringify(carritoItems.map(item => ({
            producto_id: item.id,
            descripcion: item.nombre,
            cantidad: item.cantidad,
            precio: item.precio,
            subtotal: item.subtotal
        })));

        new bootstrap.Modal(document.getElementById('pagoModal')).show();
    }

    // Método de pago
    document.getElementById('metodoPago').addEventListener('change', function() {
        const efectivoSection = document.getElementById('efectivoSection');
        const montoRecibido = document.getElementById('montoRecibido');

        if (this.value === 'efectivo') {
            efectivoSection.style.display = 'block';
            montoRecibido.required = true;
        } else {
            efectivoSection.style.display = 'none';
            montoRecibido.required = false;
            const subtotal = carritoItems.reduce((sum, item) => sum + item.subtotal, 0);
            const iva = Math.round(subtotal * 0.19);
            const total = subtotal + iva;
            montoRecibido.value = total;
        }
    });

    // Calcular vuelto
    document.getElementById('montoRecibido').addEventListener('input', function() {
        const subtotal = carritoItems.reduce((sum, item) => sum + item.subtotal, 0);
        const iva = Math.round(subtotal * 0.19);
        const total = subtotal + iva;
        const recibido = parseFloat(this.value) || 0;
        const vuelto = recibido - total;

        const vueltoSection = document.getElementById('vueltoSection');
        const vueltoDisplay = document.getElementById('vueltoDisplay');

        if (vuelto >= 0) {
            vueltoDisplay.textContent = formatCurrency(vuelto);
            vueltoSection.style.display = 'block';
        } else {
            vueltoSection.style.display = 'none';
        }
    });
    </script>
</body>
</html>
