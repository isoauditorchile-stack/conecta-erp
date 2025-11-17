<?php
/**
 * PLAN DE CUENTAS - Gestión del Plan de Cuentas Contable
 */
session_start();
require_once '../../includes/config.php';
requireLogin();

$empresa_id = $_SESSION['empresa_id'] ?? null;
$usuario_id = $_SESSION['user_id'];

$message = '';
$message_type = '';

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['guardar_cuenta'])) {
        $codigo = trim($_POST['codigo']);
        $nombre = trim($_POST['nombre']);
        $tipo_cuenta = $_POST['tipo_cuenta'];
        $cuenta_padre_id = !empty($_POST['cuenta_padre_id']) ? $_POST['cuenta_padre_id'] : null;
        $naturaleza = $_POST['naturaleza'];
        $acepta_movimiento = isset($_POST['acepta_movimiento']) ? 1 : 0;

        $stmt = $conn->prepare("INSERT INTO plan_cuentas (empresa_id, codigo, nombre, tipo_cuenta, cuenta_padre_id, naturaleza, acepta_movimiento, nivel) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
        $stmt->bind_param("isssisd", $empresa_id, $codigo, $nombre, $tipo_cuenta, $cuenta_padre_id, $naturaleza, $acepta_movimiento);

        if ($stmt->execute()) {
            $message = "Cuenta creada correctamente";
            $message_type = "success";
        } else {
            $message = "Error al crear cuenta: " . $conn->error;
            $message_type = "danger";
        }
    }
}

// Obtener plan de cuentas
$query = "SELECT * FROM plan_cuentas WHERE empresa_id = ? AND activo = 1 ORDER BY codigo";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $empresa_id);
$stmt->execute();
$cuentas = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Plan de Cuentas - CONECTA ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
<div class="container-fluid p-4">
    <h1><i class="fas fa-list-ol"></i> Plan de Cuentas</h1>

    <?php if($message): ?>
    <div class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <span>Cuentas Contables</span>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCuenta">
                <i class="fas fa-plus"></i> Nueva Cuenta
            </button>
        </div>
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Nombre</th>
                        <th>Tipo</th>
                        <th>Naturaleza</th>
                        <th>Saldo</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($cuenta = $cuentas->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($cuenta['codigo']); ?></td>
                        <td><?php echo htmlspecialchars($cuenta['nombre']); ?></td>
                        <td><span class="badge bg-info"><?php echo $cuenta['tipo_cuenta']; ?></span></td>
                        <td><?php echo $cuenta['naturaleza']; ?></td>
                        <td>$<?php echo number_format($cuenta['saldo_actual'], 0, ',', '.'); ?></td>
                        <td>
                            <button class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Nueva Cuenta -->
<div class="modal fade" id="modalCuenta">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5>Nueva Cuenta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Código *</label>
                        <input type="text" name="codigo" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Nombre *</label>
                        <input type="text" name="nombre" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Tipo de Cuenta *</label>
                        <select name="tipo_cuenta" class="form-select" required>
                            <option value="activo">Activo</option>
                            <option value="pasivo">Pasivo</option>
                            <option value="patrimonio">Patrimonio</option>
                            <option value="ingreso">Ingreso</option>
                            <option value="gasto">Gasto</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Naturaleza *</label>
                        <select name="naturaleza" class="form-select" required>
                            <option value="deudora">Deudora</option>
                            <option value="acreedora">Acreedora</option>
                        </select>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="acepta_movimiento" class="form-check-input" id="acepta" checked>
                        <label class="form-check-label" for="acepta">Acepta movimientos</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="guardar_cuenta" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Footer Profesional -->
    <?php include '../includes/footer.php'; ?>

</body>
</html>
