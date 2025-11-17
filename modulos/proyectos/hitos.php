<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
requireLogin();
$usuario_id = $_SESSION['usuario_id'];
$stmt = $conn->prepare("SELECT empresa_id FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$empresa_id = $stmt->get_result()->fetch_assoc()['empresa_id'];
?>
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Hitos de Proyectos</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head><body class="bg-light">
<?php include '../includes/sidebar.php'; ?>
<div class="content-wrapper"><div class="container-fluid py-4">
<h2><i class="fas fa-flag-checkered"></i> Hitos de Proyectos</h2>
<div class="card mt-4"><div class="card-body">
<p>Módulo de Hitos y Entregables - En desarrollo</p>
<ul>
<li>Definición de hitos clave</li>
<li>Entregables por hito</li>
<li>Timeline de proyecto</li>
<li>Seguimiento de cumplimiento</li>
<li>Notificaciones de vencimiento</li>
</ul>
</div></div>
</div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php include '../includes/footer.php'; ?>
</body></html>
