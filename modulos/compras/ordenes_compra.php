<?php
session_start();
require_once '../../includes/config.php';
requireLogin();
$empresa_id = $_SESSION['empresa_id'];
$ordenes = $conn->query("SELECT o.*, p.razon_social FROM ordenes_compra o LEFT JOIN proveedores p ON o.proveedor_id = p.id WHERE o.empresa_id = $empresa_id ORDER BY o.id DESC LIMIT 50");
?>
<!DOCTYPE html>
<html><head><title>Órdenes de Compra</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head><body>
<div class="container-fluid p-4">
<h1><i class="fas fa-shopping-bag"></i> Órdenes de Compra</h1>
<table class="table">
<thead><tr><th>N°</th><th>Proveedor</th><th>Fecha</th><th>Total</th><th>Estado</th></tr></thead>
<tbody>
<?php while($o = $ordenes->fetch_assoc()): ?>
<tr>
<td><?php echo $o['numero_orden']; ?></td>
<td><?php echo htmlspecialchars($o['razon_social']); ?></td>
<td><?php echo date('d/m/Y', strtotime($o['fecha_emision'])); ?></td>
<td>$<?php echo number_format($o['total'], 0, ',', '.'); ?></td>
<td><span class="badge bg-info"><?php echo $o['estado']; ?></span></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>

    <!-- Footer Profesional -->
    <?php include '../includes/footer.php'; ?>

</body></html>
