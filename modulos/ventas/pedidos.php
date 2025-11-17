<?php
session_start();
require_once '../../includes/config.php';
requireLogin();
$empresa_id = $_SESSION['empresa_id'];
$pedidos = $conn->query("SELECT p.*, c.razon_social FROM pedidos p LEFT JOIN clientes c ON p.cliente_id = c.id WHERE p.empresa_id = $empresa_id ORDER BY p.id DESC LIMIT 50");
?>
<!DOCTYPE html>
<html><head><title>Pedidos</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head><body>
<div class="container-fluid p-4">
<h1><i class="fas fa-clipboard-list"></i> Pedidos</h1>
<table class="table">
<thead><tr><th>N°</th><th>Cliente</th><th>Fecha</th><th>Total</th><th>Estado</th></tr></thead>
<tbody>
<?php while($p = $pedidos->fetch_assoc()): ?>
<tr>
<td><?php echo $p['numero_pedido']; ?></td>
<td><?php echo htmlspecialchars($p['razon_social']); ?></td>
<td><?php echo date('d/m/Y', strtotime($p['fecha_pedido'])); ?></td>
<td>$<?php echo number_format($p['total'], 0, ',', '.'); ?></td>
<td><span class="badge bg-<?php echo $p['estado'] == 'entregado' ? 'success' : 'warning'; ?>"><?php echo $p['estado']; ?></span></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>

    <!-- Footer Profesional -->
    <?php include '../includes/footer.php'; ?>

</body></html>
