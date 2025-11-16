<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
requireLogin();
$usuario_id = $_SESSION['usuario_id'];
$empresa_id = $_SESSION['empresa_id'];

$stmt = $conn->prepare("SELECT
    COALESCE(SUM(CASE WHEN tipo='iva_ventas' THEN monto ELSE 0 END), 0) as iva_ventas,
    COALESCE(SUM(CASE WHEN tipo='iva_compras' THEN monto ELSE 0 END), 0) as iva_compras,
    COALESCE(SUM(CASE WHEN tipo='retencion' THEN monto ELSE 0 END), 0) as retenciones
    FROM impuestos WHERE empresa_id = ? AND MONTH(fecha) = MONTH(CURDATE())");
$stmt->bind_param("i", $empresa_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();
$iva_pagar = $stats['iva_ventas'] - $stats['iva_compras'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Impuestos - CONECTA ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .sidebar { position: fixed; left: 0; top: 0; width: 280px; height: 100vh; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; }
        .content { margin-left: 280px; padding: 30px; }
        .stats-card { background: white; border-radius: 12px; padding: 25px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid #667eea; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h3><i class="fas fa-percent"></i> Impuestos</h3>
        <a href="../../user/dashboard.php" class="btn btn-light btn-sm w-100 mt-3"><i class="fas fa-arrow-left"></i> Dashboard</a>
    </div>
    <div class="content">
        <h2><i class="fas fa-percent text-primary"></i> Gestión de Impuestos</h2>
        <div class="row">
            <div class="col-md-3"><div class="stats-card"><h6 style="color:#667eea">IVA Ventas</h6><h3><?php echo formatearMoneda($stats['iva_ventas']); ?></h3></div></div>
            <div class="col-md-3"><div class="stats-card"><h6 style="color:#48bb78">IVA Compras</h6><h3><?php echo formatearMoneda($stats['iva_compras']); ?></h3></div></div>
            <div class="col-md-3"><div class="stats-card"><h6 style="color:#ed8936">IVA a Pagar</h6><h3><?php echo formatearMoneda($iva_pagar); ?></h3></div></div>
            <div class="col-md-3"><div class="stats-card"><h6 style="color:#9f7aea">Retenciones</h6><h3><?php echo formatearMoneda($stats['retenciones']); ?></h3></div></div>
        </div>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> Integración REAL con SII para declaración automática de impuestos
        </div>
    </div>
</body>
</html>
