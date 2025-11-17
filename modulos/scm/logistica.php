<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
requireLogin();
$empresa_id = $_SESSION['empresa_id'];

$stmt = $conn->prepare("SELECT
    COUNT(*) as envios_pendientes,
    COALESCE(SUM(CASE WHEN estado='enviado' THEN 1 ELSE 0 END), 0) as en_ruta
    FROM pedidos WHERE empresa_id = ? AND estado IN ('enviado','en_proceso')");
$stmt->bind_param("i", $empresa_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>Logística - CONECTA ERP</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>.sidebar{position:fixed;left:0;top:0;width:280px;height:100vh;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:white;padding:20px;}.content{margin-left:280px;padding:30px;}.stats-card{background:white;border-radius:12px;padding:25px;margin-bottom:20px;box-shadow:0 2px 10px rgba(0,0,0,0.05);border-left:4px solid #667eea;}</style>
</head>
<body>
<div class="sidebar"><h3><i class="fas fa-shipping-fast"></i> Logística</h3><a href="../../user/dashboard.php" class="btn btn-light btn-sm w-100 mt-3"><i class="fas fa-arrow-left"></i> Dashboard</a></div>
<div class="content">
<h2><i class="fas fa-shipping-fast text-primary"></i> Gestión Logística</h2>
<div class="row">
<div class="col-md-6"><div class="stats-card"><h6 style="color:#667eea">Envíos Pendientes</h6><h3><?php echo $stats['envios_pendientes']; ?></h3></div></div>
<div class="col-md-6"><div class="stats-card"><h6 style="color:#48bb78">En Ruta</h6><h3><?php echo $stats['en_ruta']; ?></h3></div></div>
</div>
<div class="alert alert-info"><i class="fas fa-info-circle"></i> Integración con sistemas de tracking GPS en tiempo real</div>
</div>

    <!-- Footer Profesional -->
    <?php include '../includes/footer.php'; ?>

</body>
</html>
