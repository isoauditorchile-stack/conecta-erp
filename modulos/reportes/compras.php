<?php
session_start();
require_once '../../includes/config.php';
requireLogin();
$empresa_id = $_SESSION['empresa_id'];
$periodo_inicio = $_GET['inicio'] ?? date('Y-m-01');
$periodo_fin = $_GET['fin'] ?? date('Y-m-t');
$stmt = $conn->prepare("SELECT DATE(fecha_recepcion) as fecha, COUNT(*) as cantidad, SUM(total) as total FROM facturas_compra WHERE empresa_id = ? AND fecha_recepcion BETWEEN ? AND ? GROUP BY DATE(fecha_recepcion) ORDER BY fecha DESC");
$stmt->bind_param("iss", $empresa_id, $periodo_inicio, $periodo_fin);
$stmt->execute();
$compras = $stmt->get_result();
?>
<!DOCTYPE html>
<html><head><title>Reporte de Compras</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head><body>
<div class="container-fluid p-4">
<h1><i class="fas fa-shopping-cart"></i> Reporte de Compras</h1>
<div class="card">
<div class="card-body">
<table class="table">
<thead><tr><th>Fecha</th><th>Cantidad Facturas</th><th>Total Compras</th></tr></thead>
<tbody>
<?php $total = 0; while($c = $compras->fetch_assoc()): $total += $c['total']; ?>
<tr><td><?php echo date('d/m/Y', strtotime($c['fecha'])); ?></td><td><?php echo $c['cantidad']; ?></td><td>$<?php echo number_format($c['total'], 0, ',', '.'); ?></td></tr>
<?php endwhile; ?>
</tbody>
<tfoot><tr class="table-active"><th colspan="2">TOTAL</th><th>$<?php echo number_format($total, 0, ',', '.'); ?></th></tr></tfoot>
</table>
</div>
</div>
</div>
</body></html>
