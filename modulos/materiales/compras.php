<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
requireLogin();
$usuario_id = $_SESSION['usuario_id'];
$empresa_id = $_SESSION['empresa_id'];
$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_orden'])) {
    $proveedor_id = intval($_POST['proveedor_id']);
    $fecha_orden = $_POST['fecha_orden'];
    
    $stmt = $conn->prepare("SELECT COALESCE(MAX(CAST(SUBSTRING(numero_orden, 3) AS UNSIGNED)), 0) + 1 as siguiente FROM ordenes_compra WHERE empresa_id = ?");
    $stmt->bind_param("i", $empresa_id);
    $stmt->execute();
    $siguiente = $stmt->get_result()->fetch_assoc()['siguiente'];
    $numero_orden = sprintf("OC%06d", $siguiente);
    $stmt->close();

    $stmt = $conn->prepare("INSERT INTO ordenes_compra (empresa_id, proveedor_id, numero_orden, fecha_orden, comprador_id, creado_por) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iissii", $empresa_id, $proveedor_id, $numero_orden, $fecha_orden, $usuario_id, $usuario_id);
    if ($stmt->execute()) {
        logAuditoria($conn, $usuario_id, $empresa_id, 'crear', 'ordenes_compra', $conn->insert_id, 'materiales', "Orden compra: $numero_orden");
        $mensaje = "Orden de compra $numero_orden creada exitosamente";
    }
    $stmt->close();
}

$stmt = $conn->prepare("SELECT COUNT(*) as total, COALESCE(SUM(total), 0) as monto FROM ordenes_compra WHERE empresa_id = ? AND estado IN ('enviada','confirmada')");
$stmt->bind_param("i", $empresa_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>Compras - CONECTA ERP</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>.sidebar{position:fixed;left:0;top:0;width:280px;height:100vh;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:white;padding:20px;}.content{margin-left:280px;padding:30px;}.stats-card{background:white;border-radius:12px;padding:25px;margin-bottom:20px;box-shadow:0 2px 10px rgba(0,0,0,0.05);border-left:4px solid #667eea;}</style>
</head>
<body>
<div class="sidebar"><h3><i class="fas fa-shopping-basket"></i> Compras</h3><a href="../../user/dashboard.php" class="btn btn-light btn-sm w-100 mt-3"><i class="fas fa-arrow-left"></i> Dashboard</a></div>
<div class="content">
<h2><i class="fas fa-shopping-basket text-primary"></i> Gestión de Compras</h2>
<?php if($mensaje): ?><div class="alert alert-success"><?php echo $mensaje; ?></div><?php endif; ?>
<div class="row">
<div class="col-md-6"><div class="stats-card"><h6 style="color:#667eea">Órdenes Activas</h6><h3><?php echo $stats['total']; ?></h3></div></div>
<div class="col-md-6"><div class="stats-card"><h6 style="color:#48bb78">Monto Total</h6><h3><?php echo formatearMoneda($stats['monto']); ?></h3></div></div>
</div>
<div class="card">
<div class="card-body">
<table class="table table-striped">
<thead><tr><th>Número</th><th>Proveedor</th><th>Fecha</th><th>Total</th><th>Estado</th></tr></thead>
<tbody>
<?php
$stmt = $conn->prepare("SELECT oc.numero_orden, p.razon_social, oc.fecha_orden, oc.total, oc.estado FROM ordenes_compra oc INNER JOIN proveedores p ON oc.proveedor_id = p.id WHERE oc.empresa_id = ? ORDER BY oc.fecha_orden DESC LIMIT 50");
$stmt->bind_param("i", $empresa_id);
$stmt->execute();
$result = $stmt->get_result();
while($row = $result->fetch_assoc()):
?>
<tr>
<td><?php echo htmlspecialchars($row['numero_orden']); ?></td>
<td><?php echo htmlspecialchars($row['razon_social']); ?></td>
<td><?php echo date('d/m/Y', strtotime($row['fecha_orden'])); ?></td>
<td><?php echo formatearMoneda($row['total']); ?></td>
<td><span class="badge bg-<?php echo $row['estado']=='recibida'?'success':'primary'; ?>"><?php echo ucfirst($row['estado']); ?></span></td>
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
