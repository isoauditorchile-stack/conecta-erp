<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
requireLogin();
$empresa_id = $_SESSION['empresa_id'];

$stmt = $conn->prepare("SELECT COUNT(*) as total, COALESCE(SUM(CASE WHEN tipo='materia_prima' THEN 1 ELSE 0 END), 0) as materias_primas, COALESCE(SUM(CASE WHEN tipo='producto_terminado' THEN 1 ELSE 0 END), 0) as productos_terminados FROM productos WHERE empresa_id = ?");
$stmt->bind_param("i", $empresa_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>Maestro Materiales - CONECTA ERP</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>.sidebar{position:fixed;left:0;top:0;width:280px;height:100vh;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:white;padding:20px;}.content{margin-left:280px;padding:30px;}.stats-card{background:white;border-radius:12px;padding:25px;margin-bottom:20px;box-shadow:0 2px 10px rgba(0,0,0,0.05);border-left:4px solid #667eea;}</style>
</head>
<body>
<div class="sidebar"><h3><i class="fas fa-cubes"></i> Maestro</h3><a href="../../user/dashboard.php" class="btn btn-light btn-sm w-100 mt-3"><i class="fas fa-arrow-left"></i> Dashboard</a></div>
<div class="content">
<h2><i class="fas fa-cubes text-primary"></i> Maestro de Materiales</h2>
<div class="row">
<div class="col-md-4"><div class="stats-card"><h6 style="color:#667eea">Total Materiales</h6><h3><?php echo $stats['total']; ?></h3></div></div>
<div class="col-md-4"><div class="stats-card"><h6 style="color:#48bb78">Materias Primas</h6><h3><?php echo $stats['materias_primas']; ?></h3></div></div>
<div class="col-md-4"><div class="stats-card"><h6 style="color:#9f7aea">Prod. Terminados</h6><h3><?php echo $stats['productos_terminados']; ?></h3></div></div>
</div>
</div>

    <!-- Footer Profesional -->
    <?php include '../includes/footer.php'; ?>

</body>
</html>
