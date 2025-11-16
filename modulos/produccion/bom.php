<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
requireLogin();
$empresa_id = $_SESSION['empresa_id'];

$stmt = $conn->prepare("SELECT COUNT(DISTINCT producto_final_id) as total_bom, COUNT(*) as total_componentes FROM lista_materiales WHERE empresa_id = ?");
$stmt->bind_param("i", $empresa_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>BOM - Lista Materiales - CONECTA ERP</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>.sidebar{position:fixed;left:0;top:0;width:280px;height:100vh;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:white;padding:20px;}.content{margin-left:280px;padding:30px;}.stats-card{background:white;border-radius:12px;padding:25px;margin-bottom:20px;box-shadow:0 2px 10px rgba(0,0,0,0.05);border-left:4px solid #667eea;}</style>
</head>
<body>
<div class="sidebar"><h3><i class="fas fa-list-alt"></i> BOM</h3><a href="../../user/dashboard.php" class="btn btn-light btn-sm w-100 mt-3"><i class="fas fa-arrow-left"></i> Dashboard</a></div>
<div class="content">
<h2><i class="fas fa-list-alt text-primary"></i> Lista de Materiales (BOM)</h2>
<div class="row">
<div class="col-md-6"><div class="stats-card"><h6 style="color:#667eea">Productos con BOM</h6><h3><?php echo $stats['total_bom']; ?></h3></div></div>
<div class="col-md-6"><div class="stats-card"><h6 style="color:#48bb78">Total Componentes</h6><h3><?php echo $stats['total_componentes']; ?></h3></div></div>
</div>
<div class="card">
<div class="card-body">
<table class="table table-striped">
<thead><tr><th>Producto Final</th><th>Componente</th><th>Cantidad</th><th>Unidad</th><th>Crítico</th></tr></thead>
<tbody>
<?php
$stmt = $conn->prepare("SELECT pf.nombre as producto_final, pc.nombre as componente, lm.cantidad_necesaria, lm.unidad_medida, lm.es_critico FROM lista_materiales lm INNER JOIN productos pf ON lm.producto_final_id = pf.id INNER JOIN productos pc ON lm.componente_id = pc.id WHERE lm.empresa_id = ? LIMIT 50");
$stmt->bind_param("i", $empresa_id);
$stmt->execute();
$result = $stmt->get_result();
while($row = $result->fetch_assoc()):
?>
<tr>
<td><?php echo htmlspecialchars($row['producto_final']); ?></td>
<td><?php echo htmlspecialchars($row['componente']); ?></td>
<td><?php echo number_format($row['cantidad_necesaria'], 2); ?></td>
<td><?php echo htmlspecialchars($row['unidad_medida']); ?></td>
<td><?php echo $row['es_critico']?'<span class="badge bg-danger">Crítico</span>':'<span class="badge bg-secondary">Normal</span>'; ?></td>
</tr>
<?php endwhile; $stmt->close(); ?>
</tbody>
</table>
</div>
</div>
</div>
</body>
</html>
