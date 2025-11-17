<?php
session_start();
require_once '../../includes/config.php';
requireLogin();
$empresa_id = $_SESSION['empresa_id'];
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_asiento'])) {
    $fecha = $_POST['fecha'];
    $glosa = $_POST['glosa'];
    
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("INSERT INTO asientos_contables (empresa_id, numero_asiento, fecha, glosa, tipo_asiento) VALUES (?, (SELECT COALESCE(MAX(numero_asiento), 0) + 1 FROM asientos_contables WHERE empresa_id = ?), ?, ?, 'manual')");
        $stmt->bind_param("iiss", $empresa_id, $empresa_id, $fecha, $glosa);
        $stmt->execute();
        $asiento_id = $conn->insert_id;
        
        // Insertar detalle
        foreach ($_POST['cuenta_id'] as $idx => $cuenta_id) {
            $debe = floatval($_POST['debe'][$idx]);
            $haber = floatval($_POST['haber'][$idx]);
            if ($debe > 0 || $haber > 0) {
                $stmt2 = $conn->prepare("INSERT INTO asientos_contables_detalle (asiento_id, cuenta_id, debe, haber, glosa, orden) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt2->bind_param("iiddsi", $asiento_id, $cuenta_id, $debe, $haber, $_POST['glosa_detalle'][$idx], $idx);
                $stmt2->execute();
            }
        }
        
        $conn->commit();
        $msg = '<div class="alert alert-success">Asiento creado correctamente</div>';
    } catch (Exception $e) {
        $conn->rollback();
        $msg = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
    }
}

$asientos = $conn->query("SELECT a.*, (SELECT COUNT(*) FROM asientos_contables_detalle WHERE asiento_id = a.id) as lineas FROM asientos_contables a WHERE empresa_id = $empresa_id ORDER BY fecha DESC, numero_asiento DESC LIMIT 50");
$cuentas = $conn->query("SELECT id, codigo, nombre FROM plan_cuentas WHERE empresa_id = $empresa_id AND acepta_movimiento = 1 AND activo = 1 ORDER BY codigo");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Asientos Contables</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
<div class="container-fluid p-4">
    <h1><i class="fas fa-edit"></i> Asientos Contables</h1>
    <?php echo $msg; ?>
    
    <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#modalAsiento"><i class="fas fa-plus"></i> Nuevo Asiento</button>
    
    <div class="card">
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th>N°</th>
                        <th>Fecha</th>
                        <th>Glosa</th>
                        <th>Debe</th>
                        <th>Haber</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($a = $asientos->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $a['numero_asiento']; ?></td>
                        <td><?php echo date('d/m/Y', strtotime($a['fecha'])); ?></td>
                        <td><?php echo htmlspecialchars($a['glosa']); ?></td>
                        <td>$<?php echo number_format($a['total_debe'], 0, ',', '.'); ?></td>
                        <td>$<?php echo number_format($a['total_haber'], 0, ',', '.'); ?></td>
                        <td><span class="badge bg-<?php echo $a['estado'] == 'contabilizado' ? 'success' : 'warning'; ?>"><?php echo $a['estado']; ?></span></td>
                        <td><button class="btn btn-sm btn-info"><i class="fas fa-eye"></i></button></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalAsiento">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5>Nuevo Asiento Contable</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label>Fecha *</label>
                            <input type="date" name="fecha" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label>Glosa *</label>
                            <input type="text" name="glosa" class="form-control" required>
                        </div>
                    </div>
                    
                    <h6>Detalle del Asiento</h6>
                    <table class="table table-sm" id="tablaDetalle">
                        <thead>
                            <tr>
                                <th style="width:40%">Cuenta</th>
                                <th style="width:20%">Glosa</th>
                                <th style="width:15%">Debe</th>
                                <th style="width:15%">Haber</th>
                                <th style="width:10%">
                                    <button type="button" class="btn btn-sm btn-success" onclick="agregarLinea()">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </th>
                            </tr>
                        </thead>
                        <tbody id="detalleBody">
                            <tr>
                                <td>
                                    <select name="cuenta_id[]" class="form-select form-select-sm" required>
                                        <option value="">Seleccione...</option>
                                        <?php $cuentas->data_seek(0); while($c = $cuentas->fetch_assoc()): ?>
                                        <option value="<?php echo $c['id']; ?>"><?php echo $c['codigo']; ?> - <?php echo $c['nombre']; ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </td>
                                <td><input type="text" name="glosa_detalle[]" class="form-control form-control-sm"></td>
                                <td><input type="number" name="debe[]" class="form-control form-control-sm" step="0.01" value="0" onchange="calcularTotales()"></td>
                                <td><input type="number" name="haber[]" class="form-control form-control-sm" step="0.01" value="0" onchange="calcularTotales()"></td>
                                <td></td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="table-active">
                                <td colspan="2" class="text-end"><strong>TOTALES:</strong></td>
                                <td><strong id="totalDebe">$0</strong></td>
                                <td><strong id="totalHaber">$0</strong></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="crear_asiento" class="btn btn-primary">Guardar Asiento</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function calcularTotales() {
    let totalD = 0, totalH = 0;
    document.querySelectorAll('input[name="debe[]"]').forEach(el => totalD += parseFloat(el.value) || 0);
    document.querySelectorAll('input[name="haber[]"]').forEach(el => totalH += parseFloat(el.value) || 0);
    document.getElementById('totalDebe').textContent = '$' + totalD.toLocaleString('es-CL');
    document.getElementById('totalHaber').textContent = '$' + totalH.toLocaleString('es-CL');
}
</script>
</body>
</html>
