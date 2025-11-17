<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
requireLogin();
$empresa_id = $_SESSION['empresa_id'];
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>Integración Proveedores - CONECTA ERP</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>.sidebar{position:fixed;left:0;top:0;width:280px;height:100vh;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:white;padding:20px;}.content{margin-left:280px;padding:30px;}</style>
</head>
<body>
<div class="sidebar"><h3><i class="fas fa-handshake"></i> Integración Proveedores</h3><a href="../../user/dashboard.php" class="btn btn-light btn-sm w-100 mt-3"><i class="fas fa-arrow-left"></i> Dashboard</a></div>
<div class="content">
<h2><i class="fas fa-handshake text-primary"></i> Integración Proveedores</h2>
<div class="alert alert-info"><i class="fas fa-info-circle"></i> Módulo de Integración Proveedores para optimización de la cadena de suministro</div>
</div>

    <!-- Footer Profesional -->
    <?php include '../includes/footer.php'; ?>

</body>
</html>
