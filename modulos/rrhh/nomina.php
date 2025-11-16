<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
requireLogin();
$empresa_id = $_SESSION['usuario_id'];

$stmt = $conn->prepare("SELECT
    COUNT(*) as total_empleados,
    COALESCE(SUM(salario_base), 0) as total_salarios
    FROM empleados WHERE empresa_id = ? AND estado = 'activo'");
$stmt->bind_param("i", $empresa_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>Nómina - CONECTA ERP</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>.sidebar{position:fixed;left:0;top:0;width:280px;height:100vh;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:white;padding:20px;}.content{margin-left:280px;padding:30px;}.stats-card{background:white;border-radius:12px;padding:25px;margin-bottom:20px;box-shadow:0 2px 10px rgba(0,0,0,0.05);border-left:4px solid #667eea;}</style>
</head>
<body>
<div class="sidebar"><h3><i class="fas fa-money-check-alt"></i> Nómina</h3><a href="../../user/dashboard.php" class="btn btn-light btn-sm w-100 mt-3"><i class="fas fa-arrow-left"></i> Dashboard</a></div>
<div class="content">
<h2><i class="fas fa-money-check-alt text-primary"></i> Gestión de Nómina</h2>
<div class="row">
<div class="col-md-6"><div class="stats-card"><h6 style="color:#667eea">Empleados en Nómina</h6><h3><?php echo $stats['total_empleados']; ?></h3></div></div>
<div class="col-md-6"><div class="stats-card"><h6 style="color:#48bb78">Total Salarios Base</h6><h3><?php echo formatearMoneda($stats['total_salarios']); ?></h3></div></div>
</div>
<div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> Integración REAL con Previred para cálculo de imposiciones AFP/ISAPRE/Fonasa</div>
</div>
</body>
</html>
