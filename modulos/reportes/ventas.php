<?php
session_start();
require_once '../../includes/config.php';
requireLogin();
$empresa_id = $_SESSION['empresa_id'];

$periodo_inicio = $_GET['inicio'] ?? date('Y-m-01');
$periodo_fin = $_GET['fin'] ?? date('Y-m-t');

$stmt = $conn->prepare("SELECT DATE(fecha_emision) as fecha, COUNT(*) as cantidad, SUM(total) as total FROM facturas WHERE empresa_id = ? AND fecha_emision BETWEEN ? AND ? AND estado NOT IN ('anulada') GROUP BY DATE(fecha_emision) ORDER BY fecha DESC");
$stmt->bind_param("iss", $empresa_id, $periodo_inicio, $periodo_fin);
$stmt->execute();
$ventas = $stmt->get_result();
?>
<!DOCTYPE html>
<html><head><title>Reporte de Ventas</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head><body>
<div class="container-fluid p-4">
<h1><i class="fas fa-chart-bar"></i> Reporte de Ventas</h1>
<div class="card">
<div class="card-header">Filtros</div>
<div class="card-body">
<form method="GET" class="row g-3">
<div class="col-md-4"><label>Desde</label><input type="date" name="inicio" class="form-control" value="<?php echo $periodo_inicio; ?>"></div>
<div class="col-md-4"><label>Hasta</label><input type="date" name="fin" class="form-control" value="<?php echo $periodo_fin; ?>"></div>
<div class="col-md-4"><button type="submit" class="btn btn-primary mt-4">Buscar</button></div>
</form>
</div>
</div>
<div class="card mt-3">
<div class="card-body">
<table class="table">
<thead><tr><th>Fecha</th><th>Cantidad Facturas</th><th>Total Ventas</th></tr></thead>
<tbody>
<?php 
$total_general = 0;
while($v = $ventas->fetch_assoc()): 
$total_general += $v['total'];
?>
<tr>
<td><?php echo date('d/m/Y', strtotime($v['fecha'])); ?></td>
<td><?php echo $v['cantidad']; ?></td>
<td>$<?php echo number_format($v['total'], 0, ',', '.'); ?></td>
</tr>
<?php endwhile; ?>
</tbody>
<tfoot><tr class="table-active"><th colspan="2">TOTAL GENERAL</th><th>$<?php echo number_format($total_general, 0, ',', '.'); ?></th></tr></tfoot>
</table>
</div>
</div>
</div>

    <!-- Footer Profesional -->
    <?php include '../includes/footer.php'; ?>

</body></html>
