<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
requireLogin();
$empresa_id = $_SESSION['empresa_id'];

$stmt = $conn->prepare("SELECT COUNT(*) as total_kpis FROM indicadores_gestion WHERE empresa_id = ? AND activo = 1");
$stmt->bind_param("i", $empresa_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>KPIs - CONECTA ERP</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>.sidebar{position:fixed;left:0;top:0;width:280px;height:100vh;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:white;padding:20px;}.content{margin-left:280px;padding:30px;}</style>
</head>
<body>
<div class="sidebar"><h3><i class="fas fa-tachometer-alt"></i> KPIs</h3><a href="../../user/dashboard.php" class="btn btn-light btn-sm w-100 mt-3"><i class="fas fa-arrow-left"></i> Dashboard</a></div>
<div class="content">
<h2><i class="fas fa-tachometer-alt text-primary"></i> Indicadores KPI</h2>
<div class="card"><div class="card-body">
<table class="table table-striped">
<thead><tr><th>Indicador</th><th>Objetivo</th><th>Real</th><th>%</th></tr></thead>
<tbody>
<?php
$stmt = $conn->prepare("SELECT i.nombre, i.objetivo, m.valor_real, m.cumplimiento_porcentaje FROM indicadores_gestion i LEFT JOIN mediciones_indicadores m ON i.id = m.indicador_id AND m.periodo = DATE_FORMAT(CURDATE(), '%Y-%m-01') WHERE i.empresa_id = ? AND i.activo = 1");
$stmt->bind_param("i", $empresa_id);
$stmt->execute();
$result = $stmt->get_result();
while($row = $result->fetch_assoc()):
?>
<tr>
<td><?php echo htmlspecialchars($row['nombre']); ?></td>
<td><?php echo $row['objetivo']; ?></td>
<td><?php echo $row['valor_real'] ?? '-'; ?></td>
<td><span class="badge bg-<?php echo ($row['cumplimiento_porcentaje'] ?? 0) >= 100 ? 'success' : 'warning'; ?>"><?php echo number_format($row['cumplimiento_porcentaje'] ?? 0, 1); ?>%</span></td>
</tr>
<?php endwhile; $stmt->close(); ?>
</tbody>
</table>
</div></div>
</div>

    <!-- Footer Profesional -->
    <?php include '../includes/footer.php'; ?>

</body>
</html>
