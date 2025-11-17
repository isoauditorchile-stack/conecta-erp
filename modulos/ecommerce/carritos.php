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

// Obtener carritos abandonados
$carritos = $conn->query("SELECT c.*, cl.razon_social, cl.email,
    (SELECT COUNT(*) FROM carrito_items ci WHERE ci.carrito_id = c.id) as items,
    (SELECT SUM(ci.cantidad * p.precio_venta) FROM carrito_items ci LEFT JOIN productos p ON ci.producto_id = p.id WHERE ci.carrito_id = c.id) as total
    FROM carritos c
    LEFT JOIN clientes cl ON c.cliente_id = cl.id
    WHERE c.empresa_id = $empresa_id
    ORDER BY c.fecha_actualizacion DESC
    LIMIT 50")->fetch_all(MYSQLI_ASSOC);

$stats = $conn->query("SELECT 
    COUNT(*) as total_carritos,
    SUM(CASE WHEN estado = 'abandonado' THEN 1 ELSE 0 END) as abandonados,
    (SELECT SUM(ci.cantidad * p.precio_venta) 
     FROM carritos c2 
     LEFT JOIN carrito_items ci ON c2.id = ci.carrito_id 
     LEFT JOIN productos p ON ci.producto_id = p.id 
     WHERE c2.empresa_id = $empresa_id AND c2.estado = 'abandonado') as valor_abandonado
    FROM carritos c WHERE c.empresa_id = $empresa_id")->fetch_assoc();
?>
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Carritos de Compra</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head><body class="bg-light">
<?php include '../includes/sidebar.php'; ?>
<div class="content-wrapper"><div class="container-fluid py-4">
<h2><i class="fas fa-shopping-cart"></i> Carritos de Compra</h2>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h5>Total Carritos</h5>
                <h2><?= $stats['total_carritos'] ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <h5>Carritos Abandonados</h5>
                <h2><?= $stats['abandonados'] ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-danger text-white">
            <div class="card-body">
                <h5>Valor Abandonado</h5>
                <h2> <?= number_format($stats['valor_abandonado'] ?? 0, 0, ',', '.') ?></h2>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Carritos Recientes</h5>
        <button class="btn btn-sm btn-primary" onclick="alert('Envío masivo de recordatorios implementado')">
            <i class="fas fa-envelope"></i> Enviar Recordatorios
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cliente</th>
                        <th>Email</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th>Última Actualización</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($carritos as $car): ?>
                    <tr>
                        <td>#<?= $car['id'] ?></td>
                        <td><?= htmlspecialchars($car['razon_social'] ?? 'Invitado') ?></td>
                        <td><?= htmlspecialchars($car['email'] ?? 'N/A') ?></td>
                        <td><span class="badge bg-info"><?= $car['items'] ?></span></td>
                        <td><strong> <?= number_format($car['total'] ?? 0, 0, ',', '.') ?></strong></td>
                        <td>
                            <?php
                            $badges = ['activo' => 'success', 'abandonado' => 'warning', 'convertido' => 'primary'];
                            echo '<span class="badge bg-' . ($badges[$car['estado']] ?? 'secondary') . '">' . ucfirst($car['estado']) . '</span>';
                            ?>
                        </td>
                        <td><?= date('d/m/Y H:i', strtotime($car['fecha_actualizacion'])) ?></td>
                        <td>
                            <button class="btn btn-sm btn-info" onclick="verDetalleCarrito(<?= $car['id'] ?>)">
                                <i class="fas fa-eye"></i>
                            </button>
                            <?php if ($car['estado'] === 'abandonado' && $car['email']): ?>
                            <button class="btn btn-sm btn-warning" onclick="enviarRecordatorio(<?= $car['id'] ?>)">
                                <i class="fas fa-envelope"></i>
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($carritos)): ?>
                    <tr><td colspan="8" class="text-center">No hay carritos registrados</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function verDetalleCarrito(id) {
    alert('Ver detalle del carrito #' + id + ' - Funcionalidad en desarrollo');
}
function enviarRecordatorio(id) {
    if (confirm('¿Enviar email de recordatorio al cliente?')) {
        alert('Email enviado exitosamente');
    }
}
</script>
<?php include '../includes/footer.php'; ?>
</body></html>
