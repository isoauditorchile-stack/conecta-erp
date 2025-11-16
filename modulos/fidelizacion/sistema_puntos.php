<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
requireLogin();
$empresa_id = $_SESSION['empresa_id'];

$stmt = $conn->prepare("SELECT
    COALESCE(SUM(puntos_acumulados), 0) as puntos_totales,
    COUNT(DISTINCT cliente_id) as clientes_con_puntos
    FROM puntos_clientes WHERE empresa_id = ?");
$stmt->bind_param("i", $empresa_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>Sistema Puntos - CONECTA ERP</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>.sidebar{position:fixed;left:0;top:0;width:280px;height:100vh;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:white;padding:20px;}.content{margin-left:280px;padding:30px;}.stats-card{background:white;border-radius:12px;padding:25px;margin-bottom:20px;box-shadow:0 2px 10px rgba(0,0,0,0.05);border-left:4px solid #667eea;}</style>
</head>
<body>
<div class="sidebar"><h3><i class="fas fa-star"></i> Puntos</h3><a href="../../user/dashboard.php" class="btn btn-light btn-sm w-100 mt-3"><i class="fas fa-arrow-left"></i> Dashboard</a></div>
<div class="content">
<h2><i class="fas fa-star text-primary"></i> Sistema de Puntos</h2>
<div class="row">
<div class="col-md-6"><div class="stats-card"><h6 style="color:#667eea">Puntos Totales</h6><h3><?php echo number_format($stats['puntos_totales']); ?></h3></div></div>
<div class="col-md-6"><div class="stats-card"><h6 style="color:#48bb78">Clientes con Puntos</h6><h3><?php echo $stats['clientes_con_puntos']; ?></h3></div></div>
</div>
</div>
</body>
</html>
