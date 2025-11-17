<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
requireLogin();
$empresa_id = $_SESSION['empresa_id'];

$stmt = $conn->prepare("SELECT
    (SELECT COUNT(*) FROM productos WHERE empresa_id = ? AND stock_actual < stock_minimo) as productos_criticos,
    (SELECT COUNT(*) FROM ordenes_compra WHERE empresa_id = ? AND estado = 'enviada') as ordenes_pendientes");
$stmt->bind_param("ii", $empresa_id, $empresa_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>Planificación Necesidades - CONECTA ERP</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>.sidebar{position:fixed;left:0;top:0;width:280px;height:100vh;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:white;padding:20px;}.content{margin-left:280px;padding:30px;}.stats-card{background:white;border-radius:12px;padding:25px;margin-bottom:20px;box-shadow:0 2px 10px rgba(0,0,0,0.05);border-left:4px solid #667eea;}</style>
</head>
<body>
<div class="sidebar"><h3><i class="fas fa-calendar-alt"></i> Planificación</h3><a href="../../user/dashboard.php" class="btn btn-light btn-sm w-100 mt-3"><i class="fas fa-arrow-left"></i> Dashboard</a></div>
<div class="content">
<h2><i class="fas fa-calendar-alt text-primary"></i> Planificación de Necesidades (MRP)</h2>
<div class="row">
<div class="col-md-6"><div class="stats-card"><h6 style="color:#ed8936">Productos Críticos</h6><h3><?php echo $stats['productos_criticos']; ?></h3></div></div>
<div class="col-md-6"><div class="stats-card"><h6 style="color:#667eea">Órdenes Pendientes</h6><h3><?php echo $stats['ordenes_pendientes']; ?></h3></div></div>
</div>
<div class="alert alert-info"><i class="fas fa-info-circle"></i> Sistema MRP (Material Requirements Planning) con cálculo automático de necesidades</div>
</div>

    <!-- Footer Profesional -->
    <?php include '../includes/footer.php'; ?>

</body>
</html>
