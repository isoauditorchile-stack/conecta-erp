<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
requireLogin();
$empresa_id = $_SESSION['empresa_id'];

$stmt = $conn->prepare("SELECT COUNT(*) as total, COALESCE(SUM(total), 0) as monto FROM facturas WHERE empresa_id = ? AND MONTH(fecha_emision) = MONTH(CURDATE())");
$stmt->bind_param("i", $empresa_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>Facturación - CONECTA ERP</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>.sidebar{position:fixed;left:0;top:0;width:280px;height:100vh;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:white;padding:20px;}.content{margin-left:280px;padding:30px;}.stats-card{background:white;border-radius:12px;padding:25px;margin-bottom:20px;box-shadow:0 2px 10px rgba(0,0,0,0.05);border-left:4px solid #667eea;}</style>
</head>
<body>
<div class="sidebar"><h3><i class="fas fa-file-invoice"></i> Facturación</h3><a href="../../user/dashboard.php" class="btn btn-light btn-sm w-100 mt-3"><i class="fas fa-arrow-left"></i> Dashboard</a></div>
<div class="content">
<h2><i class="fas fa-file-invoice text-primary"></i> Facturación Electrónica</h2>
<div class="row">
<div class="col-md-6"><div class="stats-card"><h6 style="color:#667eea">Facturas Mes</h6><h3><?php echo $stats['total']; ?></h3></div></div>
<div class="col-md-6"><div class="stats-card"><h6 style="color:#48bb78">Monto Total</h6><h3><?php echo formatearMoneda($stats['monto']); ?></h3></div></div>
</div>
<div class="alert alert-info"><i class="fas fa-info-circle"></i> Integración REAL con SII para timbraje DTE</div>
</div>
</body>
</html>
