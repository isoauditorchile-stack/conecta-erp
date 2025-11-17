<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
requireLogin();
$usuario_id = $_SESSION['usuario_id'];
$empresa_id = $_SESSION['empresa_id'];

$stmt = $conn->prepare("SELECT
    (SELECT COUNT(*) FROM ordenes_produccion WHERE empresa_id = ? AND estado IN ('planificada','en_proceso')) as ordenes_activas,
    (SELECT COALESCE(SUM(cantidad_producida), 0) FROM ordenes_produccion WHERE empresa_id = ? AND MONTH(fecha_inicio_real) = MONTH(CURDATE())) as produccion_mes,
    (SELECT COUNT(*) FROM centros_trabajo WHERE empresa_id = ? AND activo = 1) as centros_activos,
    (SELECT COUNT(*) FROM control_calidad WHERE empresa_id = ? AND resultado = 'aprobado' AND MONTH(fecha_inspeccion) = MONTH(CURDATE())) as inspecciones_mes");
$stmt->bind_param("iiii", $empresa_id, $empresa_id, $empresa_id, $empresa_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>Dashboard Producción - CONECTA ERP</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>.sidebar{position:fixed;left:0;top:0;width:280px;height:100vh;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:white;padding:20px;}.content{margin-left:280px;padding:30px;}.stats-card{background:white;border-radius:12px;padding:25px;margin-bottom:20px;box-shadow:0 2px 10px rgba(0,0,0,0.05);border-left:4px solid #667eea;}</style>
</head>
<body>
<div class="sidebar"><h3><i class="fas fa-industry"></i> Producción</h3><a href="../../user/dashboard.php" class="btn btn-light btn-sm w-100 mt-3"><i class="fas fa-arrow-left"></i> Dashboard</a></div>
<div class="content">
<h2><i class="fas fa-industry text-primary"></i> Dashboard Producción (PP)</h2>
<div class="row">
<div class="col-md-3"><div class="stats-card"><h6 style="color:#667eea">Órdenes Activas</h6><h3><?php echo $stats['ordenes_activas']; ?></h3></div></div>
<div class="col-md-3"><div class="stats-card"><h6 style="color:#48bb78">Producción Mes</h6><h3><?php echo number_format($stats['produccion_mes'], 0); ?></h3></div></div>
<div class="col-md-3"><div class="stats-card"><h6 style="color:#ed8936">Centros Trabajo</h6><h3><?php echo $stats['centros_activos']; ?></h3></div></div>
<div class="col-md-3"><div class="stats-card"><h6 style="color:#9f7aea">Inspecciones Mes</h6><h3><?php echo $stats['inspecciones_mes']; ?></h3></div></div>
</div>
</div>

    <!-- Footer Profesional -->
    <?php include '../includes/footer.php'; ?>

</body>
</html>
