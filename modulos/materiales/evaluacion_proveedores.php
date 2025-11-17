<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
requireLogin();
$empresa_id = $_SESSION['empresa_id'];

$stmt = $conn->prepare("SELECT COUNT(*) as total, COALESCE(AVG(evaluacion), 0) as evaluacion_promedio FROM proveedores WHERE empresa_id = ? AND estado = 'activo'");
$stmt->bind_param("i", $empresa_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>Evaluación Proveedores - CONECTA ERP</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>.sidebar{position:fixed;left:0;top:0;width:280px;height:100vh;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:white;padding:20px;}.content{margin-left:280px;padding:30px;}.stats-card{background:white;border-radius:12px;padding:25px;margin-bottom:20px;box-shadow:0 2px 10px rgba(0,0,0,0.05);border-left:4px solid #667eea;}</style>
</head>
<body>
<div class="sidebar"><h3><i class="fas fa-star-half-alt"></i> Evaluación</h3><a href="../../user/dashboard.php" class="btn btn-light btn-sm w-100 mt-3"><i class="fas fa-arrow-left"></i> Dashboard</a></div>
<div class="content">
<h2><i class="fas fa-star-half-alt text-primary"></i> Evaluación de Proveedores</h2>
<div class="row">
<div class="col-md-6"><div class="stats-card"><h6 style="color:#667eea">Proveedores Activos</h6><h3><?php echo $stats['total']; ?></h3></div></div>
<div class="col-md-6"><div class="stats-card"><h6 style="color:#48bb78">Evaluación Promedio</h6><h3><?php echo number_format($stats['evaluacion_promedio'], 1); ?> <i class="fas fa-star" style="color:#fbbf24"></i></h3></div></div>
</div>
<div class="card">
<div class="card-header"><h5><i class="fas fa-list"></i> Ranking Proveedores</h5></div>
<div class="card-body">
<table class="table table-striped">
<thead><tr><th>Proveedor</th><th>RUT</th><th>Evaluación</th><th>Compras Año</th></tr></thead>
<tbody>
<?php
$stmt = $conn->prepare("SELECT p.razon_social, p.rut, p.evaluacion, COALESCE(SUM(oc.total), 0) as compras_ano FROM proveedores p LEFT JOIN ordenes_compra oc ON p.id = oc.proveedor_id AND YEAR(oc.fecha_orden) = YEAR(CURDATE()) WHERE p.empresa_id = ? GROUP BY p.id ORDER BY p.evaluacion DESC, compras_ano DESC LIMIT 20");
$stmt->bind_param("i", $empresa_id);
$stmt->execute();
$result = $stmt->get_result();
while($row = $result->fetch_assoc()):
?>
<tr>
<td><?php echo htmlspecialchars($row['razon_social']); ?></td>
<td><?php echo htmlspecialchars($row['rut']); ?></td>
<td><?php for($i=1;$i<=5;$i++) echo $i<=$row['evaluacion']?'<i class="fas fa-star" style="color:#fbbf24"></i>':'<i class="far fa-star" style="color:#d1d5db"></i>'; ?></td>
<td><?php echo formatearMoneda($row['compras_ano']); ?></td>
</tr>
<?php endwhile; $stmt->close(); ?>
</tbody>
</table>
</div>
</div>
</div>

    <!-- Footer Profesional -->
    <?php include '../includes/footer.php'; ?>

</body>
</html>
