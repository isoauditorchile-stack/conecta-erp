<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
requireLogin();
$usuario_id = $_SESSION['usuario_id'];
$empresa_id = $_SESSION['empresa_id'];

// Estadísticas REALES desde SQL
$stmt = $conn->prepare("SELECT
    (SELECT COUNT(*) FROM centros_costo WHERE empresa_id = ? AND activo = 1) as total_centros,
    (SELECT COALESCE(SUM(monto), 0) FROM imputaciones_costo WHERE empresa_id = ? AND MONTH(fecha_imputacion) = MONTH(CURDATE())) as costos_mes,
    (SELECT COUNT(*) FROM indicadores_gestion WHERE empresa_id = ? AND activo = 1) as total_kpis,
    (SELECT COALESCE(SUM(monto_presupuestado), 0) FROM presupuestos WHERE empresa_id = ? AND ano = YEAR(CURDATE())) as presupuesto_anual");
$stmt->bind_param("iiii", $empresa_id, $empresa_id, $empresa_id, $empresa_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Controlling - CONECTA ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .sidebar{position:fixed;left:0;top:0;width:280px;height:100vh;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:white;padding:20px;overflow-y:auto;}
        .content{margin-left:280px;padding:30px;}
        .stats-card{background:white;border-radius:12px;padding:25px;margin-bottom:20px;box-shadow:0 2px 10px rgba(0,0,0,0.05);border-left:4px solid #667eea;}
    </style>
</head>
<body>
    <div class="sidebar">
        <h3><i class="fas fa-chart-line"></i> Controlling</h3>
        <a href="../../user/dashboard.php" class="btn btn-light btn-sm w-100 mt-3"><i class="fas fa-arrow-left"></i> Dashboard</a>
    </div>
    <div class="content">
        <h2><i class="fas fa-chart-line text-primary"></i> Dashboard Controlling (CO)</h2>
        <div class="row">
            <div class="col-md-3"><div class="stats-card"><h6 style="color:#667eea">Centros Costo</h6><h3><?php echo $stats['total_centros']; ?></h3></div></div>
            <div class="col-md-3"><div class="stats-card"><h6 style="color:#48bb78">Costos Mes</h6><h3><?php echo formatearMoneda($stats['costos_mes']); ?></h3></div></div>
            <div class="col-md-3"><div class="stats-card"><h6 style="color:#ed8936">KPIs Activos</h6><h3><?php echo $stats['total_kpis']; ?></h3></div></div>
            <div class="col-md-3"><div class="stats-card"><h6 style="color:#9f7aea">Presupuesto Anual</h6><h3><?php echo formatearMoneda($stats['presupuesto_anual']); ?></h3></div></div>
        </div>
    </div>

    <!-- Footer Profesional -->
    <?php include '../includes/footer.php'; ?>

</body>
</html>
