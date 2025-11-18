<?php
/**
 * MÓDULO FACTURACIÓN ELECTRÓNICA - EMITIR DTE
 * Sistema completo de emisión de Documentos Tributarios Electrónicos
 * Multiempresa - Multiusuario - Conexión REAL al SII
 */
session_start();
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../clases/SIIClientReal.php';

// Verificar autenticación
if (!isset($_SESSION['user_id']) || !isset($_SESSION['empresa_id'])) {
    header('Location: /login.php');
    exit;
}

$empresa_id = (int)$_SESSION['empresa_id'];
$usuario_id = (int)$_SESSION['user_id'];
$conn = getMysqliConnection();

$success = '';
$error = '';

// Procesar emisión de DTE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['emitir_dte'])) {
    try {
        $conn->begin_transaction();

        $tipo_dte = (int)$_POST['tipo_dte'];
        $cliente_id = (int)$_POST['cliente_id'];
        $fecha_emision = $_POST['fecha_emision'];
        $forma_pago = $_POST['forma_pago'];
        $productos = json_decode($_POST['productos_json'], true);

        if (empty($productos)) {
            throw new Exception("Debe agregar al menos un producto");
        }

        // Obtener folio disponible
        $siiClient = new SIIClientReal($conn, $empresa_id);
        $folio = $siiClient->obtenerProximoFolio($tipo_dte);

        if (!$folio) {
            throw new Exception("No hay folios disponibles para el tipo de documento $tipo_dte. Solicite folios al SII.");
        }

        // Calcular totales
        $neto = 0;
        foreach ($productos as $prod) {
            $neto += floatval($prod['subtotal']);
        }

        $exento = ($tipo_dte == 34 || $tipo_dte == 41) ? $neto : 0;
        $iva = ($tipo_dte == 33 || $tipo_dte == 39) ? round($neto * 0.19) : 0;
        $total = $neto + $iva;

        // Insertar DTE
        $stmt = $conn->prepare("
            INSERT INTO documentos_tributarios
            (empresa_id, usuario_id, cliente_id, tipo_dte, folio, fecha_emision,
             neto, iva, exento, total, forma_pago, estado, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'emitido', NOW())
        ");
        $stmt->bind_param('iiiiisdddds',
            $empresa_id, $usuario_id, $cliente_id, $tipo_dte, $folio,
            $fecha_emision, $neto, $iva, $exento, $total, $forma_pago
        );
        $stmt->execute();
        $dte_id = $conn->insert_id;

        // Insertar detalle
        foreach ($productos as $prod) {
            $stmt = $conn->prepare("
                INSERT INTO documentos_tributarios_detalle
                (dte_id, descripcion, cantidad, precio_unitario, subtotal)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->bind_param('isddd',
                $dte_id, $prod['descripcion'], $prod['cantidad'],
                $prod['precio'], $prod['subtotal']
            );
            $stmt->execute();
        }

        // Marcar folio como usado
        $stmt = $conn->prepare("
            UPDATE folios SET estado = 'usado', fecha_uso = NOW()
            WHERE empresa_id = ? AND tipo_documento = ? AND folio = ?
        ");
        $stmt->bind_param('iii', $empresa_id, $tipo_dte, $folio);
        $stmt->execute();

        // Enviar al SII si está configurado
        if (isset($_POST['enviar_sii']) && $_POST['enviar_sii'] == '1') {
            $resultado_sii = $siiClient->enviarDTE($dte_id);

            if ($resultado_sii['success']) {
                $stmt = $conn->prepare("
                    UPDATE documentos_tributarios
                    SET estado = 'enviado_sii', track_id_sii = ?, fecha_envio_sii = NOW()
                    WHERE id = ?
                ");
                $stmt->bind_param('si', $resultado_sii['track_id'], $dte_id);
                $stmt->execute();
            }
        }

        $conn->commit();
        $success = "DTE emitido exitosamente. Folio: $folio";
        logActivity($usuario_id, 'emitir_dte', "Emitió DTE tipo $tipo_dte folio $folio", 'facturacion');

    } catch (Exception $e) {
        $conn->rollback();
        $error = $e->getMessage();
        error_log("Error emitir_dte.php: " . $error);
    }
}

// Obtener info empresa
$stmt = $conn->prepare("SELECT nombre, rut, giro, direccion, comuna, ciudad FROM empresas WHERE id = ?");
$stmt->bind_param('i', $empresa_id);
$stmt->execute();
$empresa = $stmt->get_result()->fetch_assoc();

// Obtener clientes
$stmt = $conn->prepare("SELECT id, nombre, rut, direccion, email FROM clientes WHERE empresa_id = ? AND activo = 1 ORDER BY nombre");
$stmt->bind_param('i', $empresa_id);
$stmt->execute();
$clientes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Obtener productos
$stmt = $conn->prepare("SELECT id, codigo, nombre, precio_venta, stock FROM productos WHERE empresa_id = ? AND activo = 1 ORDER BY nombre");
$stmt->bind_param('i', $empresa_id);
$stmt->execute();
$productos_disponibles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Obtener DTEs recientes
$stmt = $conn->prepare("
    SELECT dt.*, c.nombre as cliente_nombre, c.rut as cliente_rut,
           CASE dt.tipo_dte
               WHEN 33 THEN 'Factura Electrónica'
               WHEN 34 THEN 'Factura Exenta'
               WHEN 39 THEN 'Boleta Electrónica'
               WHEN 41 THEN 'Boleta Exenta'
               WHEN 52 THEN 'Guía Despacho'
               WHEN 56 THEN 'Nota Débito'
               WHEN 61 THEN 'Nota Crédito'
           END as tipo_dte_nombre
    FROM documentos_tributarios dt
    LEFT JOIN clientes c ON dt.cliente_id = c.id
    WHERE dt.empresa_id = ?
    ORDER BY dt.created_at DESC
    LIMIT 50
");
$stmt->bind_param('i', $empresa_id);
$stmt->execute();
$dtes_recientes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$tipos_dte = [
    33 => 'Factura Electrónica',
    34 => 'Factura Exenta Electrónica',
    39 => 'Boleta Electrónica',
    41 => 'Boleta Exenta Electrónica',
    52 => 'Guía de Despacho Electrónica',
    56 => 'Nota de Débito Electrónica',
    61 => 'Nota de Crédito Electrónica'
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emitir DTE | CONECTA ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .gradient-header {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
            padding: 2rem;
            margin-bottom: 2rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(17, 153, 142, 0.3);
        }
        .producto-item {
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 0.5rem;
            background: #f8f9fa;
        }
        .estado-emitido { background: #17a2b8; color: white; }
        .estado-enviado_sii { background: #28a745; color: white; }
        .estado-rechazado { background: #dc3545; color: white; }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid py-4">
        <div class="gradient-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-2"><i class="fas fa-file-invoice-dollar"></i> Emisión de Documentos Tributarios Electrónicos</h1>
                    <p class="mb-0 opacity-75">Sistema completo de facturación electrónica con envío al SII</p>
                    <small class="d-block mt-2">
                        <i class="fas fa-building"></i> <?= htmlspecialchars($empresa['nombre']) ?> |
                        <i class="fas fa-id-card"></i> <?= htmlspecialchars($empresa['rut']) ?>
                    </small>
                </div>
                <div class="col-md-4 text-end">
                    <button class="btn btn-light btn-lg" data-bs-toggle="modal" data-bs-target="#emitirDteModal">
                        <i class="fas fa-plus-circle"></i> Emitir DTE
                    </button>
                </div>
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

        <!-- DTEs Recientes -->
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-list"></i> Documentos Emitidos</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Folio</th>
                                <th>Tipo</th>
                                <th>Cliente</th>
                                <th>Fecha</th>
                                <th>Total</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dtes_recientes as $dte): ?>
                            <tr>
                                <td><strong><?= $dte['folio'] ?></strong></td>
                                <td><?= $dte['tipo_dte_nombre'] ?></td>
                                <td>
                                    <?= htmlspecialchars($dte['cliente_nombre']) ?><br>
                                    <small class="text-muted"><?= htmlspecialchars($dte['cliente_rut']) ?></small>
                                </td>
                                <td><?= date('d/m/Y', strtotime($dte['fecha_emision'])) ?></td>
                                <td><strong>$<?= number_format($dte['total'], 0, ',', '.') ?></strong></td>
                                <td>
                                    <span class="badge estado-<?= $dte['estado'] ?>">
                                        <?= ucfirst(str_replace('_', ' ', $dte['estado'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="verDTE(<?= $dte['id'] ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-success" onclick="imprimirDTE(<?= $dte['id'] ?>)">
                                        <i class="fas fa-print"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>

                            <?php if (empty($dtes_recientes)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                    No hay documentos emitidos
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Emitir DTE -->
    <div class="modal fade" id="emitirDteModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fas fa-plus-circle"></i> Emitir Documento Tributario Electrónico</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="formEmitirDte">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Tipo de Documento *</label>
                                <select name="tipo_dte" id="tipoDte" class="form-select" required>
                                    <option value="">Seleccione...</option>
                                    <?php foreach ($tipos_dte as $codigo => $nombre): ?>
                                    <option value="<?= $codigo ?>"><?= $codigo ?> - <?= $nombre ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Cliente *</label>
                                <select name="cliente_id" class="form-select" required>
                                    <option value="">Seleccione...</option>
                                    <?php foreach ($clientes as $cli): ?>
                                    <option value="<?= $cli['id'] ?>"><?= htmlspecialchars($cli['nombre']) ?> - <?= htmlspecialchars($cli['rut']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label class="form-label">Fecha Emisión *</label>
                                <input type="date" name="fecha_emision" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label class="form-label">Forma Pago</label>
                                <select name="forma_pago" class="form-select">
                                    <option value="Contado">Contado</option>
                                    <option value="Credito">Crédito</option>
                                </select>
                            </div>
                        </div>

                        <hr>
                        <h6><i class="fas fa-box"></i> Productos/Servicios</h6>

                        <div class="row mb-3">
                            <div class="col-md-5">
                                <select id="productoSelect" class="form-select">
                                    <option value="">Seleccione producto...</option>
                                    <?php foreach ($productos_disponibles as $prod): ?>
                                    <option value="<?= $prod['id'] ?>"
                                            data-nombre="<?= htmlspecialchars($prod['nombre']) ?>"
                                            data-precio="<?= $prod['precio_venta'] ?>"
                                            data-stock="<?= $prod['stock'] ?>">
                                        <?= htmlspecialchars($prod['codigo']) ?> - <?= htmlspecialchars($prod['nombre']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <input type="number" id="cantidadInput" class="form-control" placeholder="Cantidad" min="1" value="1">
                            </div>
                            <div class="col-md-3">
                                <input type="number" id="precioInput" class="form-control" placeholder="Precio" min="0">
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-primary w-100" onclick="agregarProducto()">
                                    <i class="fas fa-plus"></i> Agregar
                                </button>
                            </div>
                        </div>

                        <div id="productosLista"></div>

                        <div class="row mt-3">
                            <div class="col-md-8"></div>
                            <div class="col-md-4">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <span>Neto:</span>
                                            <strong id="netoDisplay">$0</strong>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span>IVA (19%):</span>
                                            <strong id="ivaDisplay">$0</strong>
                                        </div>
                                        <hr>
                                        <div class="d-flex justify-content-between">
                                            <h5>TOTAL:</h5>
                                            <h5 class="text-primary" id="totalDisplay">$0</h5>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-check mt-3">
                            <input class="form-check-input" type="checkbox" name="enviar_sii" value="1" id="enviarSii">
                            <label class="form-check-label" for="enviarSii">
                                <i class="fas fa-paper-plane"></i> Enviar automáticamente al SII
                            </label>
                        </div>

                        <input type="hidden" name="productos_json" id="productosJson">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="emitir_dte" class="btn btn-success btn-lg">
                            <i class="fas fa-check-circle"></i> Emitir DTE
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    let productosArray = [];

    document.getElementById('productoSelect').addEventListener('change', function() {
        const option = this.options[this.selectedIndex];
        if (option.value) {
            document.getElementById('precioInput').value = option.getAttribute('data-precio');
        }
    });

    function agregarProducto() {
        const select = document.getElementById('productoSelect');
        const option = select.options[select.selectedIndex];

        if (!option.value) {
            alert('Seleccione un producto');
            return;
        }

        const descripcion = option.getAttribute('data-nombre');
        const cantidad = parseFloat(document.getElementById('cantidadInput').value) || 1;
        const precio = parseFloat(document.getElementById('precioInput').value) || 0;
        const subtotal = cantidad * precio;

        productosArray.push({
            descripcion: descripcion,
            cantidad: cantidad,
            precio: precio,
            subtotal: subtotal
        });

        actualizarLista();
        select.value = '';
        document.getElementById('cantidadInput').value = '1';
        document.getElementById('precioInput').value = '';
    }

    function eliminarProducto(index) {
        productosArray.splice(index, 1);
        actualizarLista();
    }

    function actualizarLista() {
        const lista = document.getElementById('productosLista');
        let html = '';

        productosArray.forEach((prod, index) => {
            html += `
                <div class="producto-item d-flex justify-content-between align-items-center">
                    <div>
                        <strong>${prod.descripcion}</strong><br>
                        <small>Cantidad: ${prod.cantidad} × $${formatNumber(prod.precio)}</small>
                    </div>
                    <div class="d-flex align-items-center">
                        <strong class="me-3">$${formatNumber(prod.subtotal)}</strong>
                        <button type="button" class="btn btn-sm btn-danger" onclick="eliminarProducto(${index})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `;
        });

        lista.innerHTML = html;

        // Calcular totales
        const tipoDte = parseInt(document.getElementById('tipoDte').value) || 0;
        const neto = productosArray.reduce((sum, p) => sum + p.subtotal, 0);
        const esExento = (tipoDte === 34 || tipoDte === 41);
        const iva = esExento ? 0 : Math.round(neto * 0.19);
        const total = neto + iva;

        document.getElementById('netoDisplay').textContent = '$' + formatNumber(neto);
        document.getElementById('ivaDisplay').textContent = '$' + formatNumber(iva);
        document.getElementById('totalDisplay').textContent = '$' + formatNumber(total);

        document.getElementById('productosJson').value = JSON.stringify(productosArray);
    }

    document.getElementById('tipoDte').addEventListener('change', actualizarLista);

    function formatNumber(num) {
        return Math.round(num).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }

    function verDTE(id) {
        alert('Ver DTE #' + id);
    }

    function imprimirDTE(id) {
        window.open('/modulos/facturacion/imprimir_dte.php?id=' + id, '_blank');
    }

    document.getElementById('formEmitirDte').addEventListener('submit', function(e) {
        if (productosArray.length === 0) {
            e.preventDefault();
            alert('Debe agregar al menos un producto');
        }
    });
    </script>
</body>
</html>
