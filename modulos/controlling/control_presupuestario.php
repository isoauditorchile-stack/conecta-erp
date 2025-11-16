<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
requireLogin();
$usuario_id = $_SESSION['usuario_id'];
$empresa_id = $_SESSION['empresa_id'];

$stmt = $conn->prepare("SELECT
    COALESCE(SUM(monto_presupuestado), 0) as total_presupuesto,
    COALESCE(SUM(monto_ejecutado), 0) as total_ejecutado,
    COUNT(*) as total_presupuestos
    FROM presupuestos WHERE empresa_id = ? AND ano = YEAR(CURDATE())");
$stmt->bind_param("i", $empresa_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();
$disponible = $stats['total_presupuesto'] - $stats['total_ejecutado'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Control Presupuestario - CONECTA ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>.sidebar{position:fixed;left:0;top:0;width:280px;height:100vh;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:white;padding:20px;}.content{margin-left:280px;padding:30px;}.stats-card{background:white;border-radius:12px;padding:25px;margin-bottom:20px;box-shadow:0 2px 10px rgba(0,0,0,0.05);border-left:4px solid #667eea;}</style>
</head>
<body>
    <div class="sidebar"><h3><i class="fas fa-calculator"></i> Presupuestos</h3><a href="../../user/dashboard.php" class="btn btn-light btn-sm w-100 mt-3"><i class="fas fa-arrow-left"></i> Dashboard</a></div>
    <div class="content">
        <h2><i class="fas fa-calculator text-primary"></i> Control Presupuestario</h2>
        <div class="row">
            <div class="col-md-4"><div class="stats-card"><h6 style="color:#667eea">Presupuestado</h6><h3><?php echo formatearMoneda($stats['total_presupuesto']); ?></h3></div></div>
            <div class="col-md-4"><div class="stats-card"><h6 style="color:#ed8936">Ejecutado</h6><h3><?php echo formatearMoneda($stats['total_ejecutado']); ?></h3></div></div>
            <div class="col-md-4"><div class="stats-card"><h6 style="color:#48bb78">Disponible</h6><h3><?php echo formatearMoneda($disponible); ?></h3></div></div>
        </div>
    </div>
</body>
</html>
