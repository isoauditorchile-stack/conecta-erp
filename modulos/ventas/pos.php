<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
requireLogin();
$empresa_id = $_SESSION['empresa_id'];
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>Punto de Venta - CONECTA ERP</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>.sidebar{position:fixed;left:0;top:0;width:280px;height:100vh;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:white;padding:20px;}.content{margin-left:280px;padding:30px;}</style>
</head>
<body>
<div class="sidebar"><h3><i class="fas fa-cash-register"></i> POS</h3><a href="../../user/dashboard.php" class="btn btn-light btn-sm w-100 mt-3"><i class="fas fa-arrow-left"></i> Dashboard</a></div>
<div class="content">
<h2><i class="fas fa-cash-register text-primary"></i> Punto de Venta (POS)</h2>
<div class="alert alert-info"><i class="fas fa-info-circle"></i> Sistema POS con integración a lectores código barras e impresoras térmicas</div>
</div>

    <!-- Footer Profesional -->
    <?php include '../includes/footer.php'; ?>

</body>
</html>
