<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
requireLogin();
$usuario_id = $_SESSION['usuario_id'];
$stmt = $conn->prepare("SELECT empresa_id FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$empresa_id = $stmt->get_result()->fetch_assoc()['empresa_id'];

// Crear/actualizar método de pago
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_metodo'])) {
    $nombre = trim($_POST['nombre']);
    $tipo = $_POST['tipo'];
    $descripcion = trim($_POST['descripcion']);
    $config_json = json_encode($_POST['config'] ?? []);
    $activo = isset($_POST['activo']) ? 1 : 0;
    
    $stmt = $conn->prepare("INSERT INTO metodos_pago (empresa_id, nombre, tipo, descripcion, configuracion, activo) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssi", $empresa_id, $nombre, $tipo, $descripcion, $config_json, $activo);
    $stmt->execute();
    $success_msg = "Método de pago creado exitosamente";
}

// Obtener métodos de pago
$metodos = $conn->query("SELECT * FROM metodos_pago WHERE empresa_id = $empresa_id ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Métodos de Pago</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head><body class="bg-light">
<?php include '../includes/sidebar.php'; ?>
<div class="content-wrapper"><div class="container-fluid py-4">
<h2><i class="fas fa-credit-card"></i> Métodos de Pago</h2>

<?php if (isset($success_msg)): ?>
<div class="alert alert-success"><?= htmlspecialchars($success_msg) ?></div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Configurar Método de Pago</h5></div>
    <div class="card-body">
        <form method="POST">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label>Nombre *</label>
                    <input type="text" name="nombre" class="form-control" placeholder="Ej: Transferencia Bancaria" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label>Tipo *</label>
                    <select name="tipo" class="form-control" required>
                        <option value="transferencia">Transferencia Bancaria</option>
                        <option value="webpay">Webpay Plus (Transbank)</option>
                        <option value="mercadopago">MercadoPago</option>
                        <option value="paypal">PayPal</option>
                        <option value="efectivo">Efectivo</option>
                        <option value="credito">Crédito</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label>&nbsp;</label>
                    <div class="form-check">
                        <input type="checkbox" name="activo" class="form-check-input" id="activo" checked>
                        <label class="form-check-label" for="activo">Activo en tienda</label>
                    </div>
                </div>
            </div>
            <div class="mb-3">
                <label>Descripción / Instrucciones</label>
                <textarea name="descripcion" class="form-control" rows="3" placeholder="Instrucciones para el cliente..."></textarea>
            </div>
            <button type="submit" name="guardar_metodo" class="btn btn-primary">
                <i class="fas fa-save"></i> Guardar Método
            </button>
        </form>
    </div>
</div>

<div class="row">
    <?php foreach ($metodos as $m): ?>
    <div class="col-md-6 mb-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h5><?= htmlspecialchars($m['nombre']) ?></h5>
                        <p class="text-muted mb-2">
                            <?php
                            $iconos = [
                                'transferencia' => 'fa-university',
                                'webpay' => 'fa-credit-card',
                                'mercadopago' => 'fa-money-bill-wave',
                                'paypal' => 'fa-paypal',
                                'efectivo' => 'fa-money-bill',
                                'credito' => 'fa-file-invoice-dollar'
                            ];
                            echo '<i class="fas ' . ($iconos[$m['tipo']] ?? 'fa-wallet') . '"></i> ' . ucfirst($m['tipo']);
                            ?>
                        </p>
                        <p><?= nl2br(htmlspecialchars($m['descripcion'])) ?></p>
                    </div>
                    <div>
                        <?php if ($m['activo']): ?>
                        <span class="badge bg-success">Activo</span>
                        <?php else: ?>
                        <span class="badge bg-secondary">Inactivo</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php if (empty($metodos)): ?>
<div class="alert alert-info">
    <i class="fas fa-info-circle"></i> No hay métodos de pago configurados. Agregue al menos uno para habilitar pagos en la tienda.
</div>
<?php endif; ?>

</div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php include '../includes/footer.php'; ?>
</body></html>
