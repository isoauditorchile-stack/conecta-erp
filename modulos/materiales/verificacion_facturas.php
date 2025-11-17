<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
requireLogin();
$usuario_id = $_SESSION['usuario_id'];
$empresa_id = $_SESSION['empresa_id'];

$stmt = $conn->prepare("SELECT
    (SELECT COUNT(*) FROM ordenes_compra WHERE empresa_id = ? AND estado = 'recibida') as ordenes_recibidas,
    (SELECT COUNT(*) FROM recepciones WHERE empresa_id = ? AND MONTH(fecha_recepcion) = MONTH(CURDATE())) as recepciones_mes,
    (SELECT COALESCE(SUM(oc.total), 0) FROM ordenes_compra oc WHERE oc.empresa_id = ? AND oc.estado IN ('recibida','recibida_parcial')) as monto_pendiente_verificar");
$stmt->bind_param("iii", $empresa_id, $empresa_id, $empresa_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>Verificación Facturas - CONECTA ERP</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>.sidebar{position:fixed;left:0;top:0;width:280px;height:100vh;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:white;padding:20px;}.content{margin-left:280px;padding:30px;}.stats-card{background:white;border-radius:12px;padding:25px;margin-bottom:20px;box-shadow:0 2px 10px rgba(0,0,0,0.05);border-left:4px solid #667eea;}</style>
</head>
<body>
<div class="sidebar"><h3><i class="fas fa-check-double"></i> Verificación</h3><a href="../../user/dashboard.php" class="btn btn-light btn-sm w-100 mt-3"><i class="fas fa-arrow-left"></i> Dashboard</a></div>
<div class="content">
<h2><i class="fas fa-check-double text-primary"></i> Verificación de Facturas</h2>
<div class="row">
<div class="col-md-4"><div class="stats-card"><h6 style="color:#667eea">Órdenes Recibidas</h6><h3><?php echo $stats['ordenes_recibidas']; ?></h3></div></div>
<div class="col-md-4"><div class="stats-card"><h6 style="color:#48bb78">Recepciones Mes</h6><h3><?php echo $stats['recepciones_mes']; ?></h3></div></div>
<div class="col-md-4"><div class="stats-card"><h6 style="color:#ed8936">Pend. Verificar</h6><h3><?php echo formatearMoneda($stats['monto_pendiente_verificar']); ?></h3></div></div>
</div>
<div class="card">
<div class="card-body">
<table class="table table-striped">
<thead><tr><th>OC</th><th>Proveedor</th><th>Fecha Recepción</th><th>Monto</th><th>Estado</th></tr></thead>
<tbody>
<?php
$stmt = $conn->prepare("SELECT oc.numero_orden, p.razon_social, r.fecha_recepcion, oc.total, oc.estado FROM ordenes_compra oc INNER JOIN proveedores p ON oc.proveedor_id = p.id LEFT JOIN recepciones r ON oc.id = r.orden_compra_id WHERE oc.empresa_id = ? AND oc.estado IN ('recibida','recibida_parcial') ORDER BY r.fecha_recepcion DESC LIMIT 50");
$stmt->bind_param("i", $empresa_id);
$stmt->execute();
$result = $stmt->get_result();
while($row = $result->fetch_assoc()):
?>
<tr>
<td><?php echo htmlspecialchars($row['numero_orden']); ?></td>
<td><?php echo htmlspecialchars($row['razon_social']); ?></td>
<td><?php echo date('d/m/Y', strtotime($row['fecha_recepcion'])); ?></td>
<td><?php echo formatearMoneda($row['total']); ?></td>
<td><span class="badge bg-warning"><?php echo ucfirst($row['estado']); ?></span></td>
</tr>
<?php endwhile; $stmt->close(); ?>
</tbody>
</table>
</div>
</div>
</div>

    <!-- Footer Profesional -->
    <?php include '../includes/footer.php'; ?>

</body>
</html>
