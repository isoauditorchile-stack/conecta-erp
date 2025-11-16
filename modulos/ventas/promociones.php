<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
requireLogin();
$empresa_id = $_SESSION['empresa_id'];

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM promociones WHERE empresa_id = ? AND activo = 1 AND CURDATE() BETWEEN fecha_inicio AND fecha_fin");
$stmt->bind_param("i", $empresa_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>Promociones - CONECTA ERP</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>.sidebar{position:fixed;left:0;top:0;width:280px;height:100vh;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:white;padding:20px;}.content{margin-left:280px;padding:30px;}</style>
</head>
<body>
<div class="sidebar"><h3><i class="fas fa-gift"></i> Promociones</h3><a href="../../user/dashboard.php" class="btn btn-light btn-sm w-100 mt-3"><i class="fas fa-arrow-left"></i> Dashboard</a></div>
<div class="content">
<h2><i class="fas fa-gift text-primary"></i> Promociones y Descuentos</h2>
<div class="card"><div class="card-body">
<table class="table table-striped">
<thead><tr><th>Código</th><th>Nombre</th><th>Tipo</th><th>Vigencia</th></tr></thead>
<tbody>
<?php
$stmt = $conn->prepare("SELECT codigo, nombre, tipo, fecha_inicio, fecha_fin FROM promociones WHERE empresa_id = ? ORDER BY fecha_inicio DESC LIMIT 50");
$stmt->bind_param("i", $empresa_id);
$stmt->execute();
$result = $stmt->get_result();
while($row = $result->fetch_assoc()):
?>
<tr>
<td><?php echo htmlspecialchars($row['codigo']); ?></td>
<td><?php echo htmlspecialchars($row['nombre']); ?></td>
<td><?php echo ucfirst(str_replace('_', ' ', $row['tipo'])); ?></td>
<td><?php echo date('d/m/Y', strtotime($row['fecha_inicio'])) . ' - ' . date('d/m/Y', strtotime($row['fecha_fin'])); ?></td>
</tr>
<?php endwhile; $stmt->close(); ?>
</tbody>
</table>
</div></div>
</div>
</body>
</html>
