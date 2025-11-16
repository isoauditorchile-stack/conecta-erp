<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
requireLogin();
$usuario_id = $_SESSION['usuario_id'];
$empresa_id = $_SESSION['empresa_id'];
$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_orden'])) {
    $producto_id = intval($_POST['producto_id']);
    $cantidad_planificada = floatval($_POST['cantidad_planificada']);
    $fecha_inicio = $_POST['fecha_inicio'];
    $fecha_fin = $_POST['fecha_fin'];
    $prioridad = $_POST['prioridad'];

    $stmt = $conn->prepare("SELECT COALESCE(MAX(CAST(SUBSTRING(numero_orden, 3) AS UNSIGNED)), 0) + 1 as siguiente FROM ordenes_produccion WHERE empresa_id = ?");
    $stmt->bind_param("i", $empresa_id);
    $stmt->execute();
    $siguiente = $stmt->get_result()->fetch_assoc()['siguiente'];
    $numero_orden = sprintf("OP%06d", $siguiente);
    $stmt->close();

    $stmt = $conn->prepare("INSERT INTO ordenes_produccion (empresa_id, numero_orden, producto_id, cantidad_planificada, fecha_inicio_planificada, fecha_fin_planificada, prioridad, creado_por) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isidsssi", $empresa_id, $numero_orden, $producto_id, $cantidad_planificada, $fecha_inicio, $fecha_fin, $prioridad, $usuario_id);
    if ($stmt->execute()) {
        logAuditoria($conn, $usuario_id, $empresa_id, 'crear', 'ordenes_produccion', $conn->insert_id, 'produccion', "Orden producción: $numero_orden");
        $mensaje = "Orden de producción $numero_orden creada exitosamente";
    }
    $stmt->close();
}

$stmt = $conn->prepare("SELECT COUNT(*) as total, COALESCE(SUM(cantidad_planificada), 0) as cantidad_total, COALESCE(SUM(cantidad_producida), 0) as cantidad_producida FROM ordenes_produccion WHERE empresa_id = ? AND estado != 'cancelada'");
$stmt->bind_param("i", $empresa_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>Órdenes Fabricación - CONECTA ERP</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>.sidebar{position:fixed;left:0;top:0;width:280px;height:100vh;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:white;padding:20px;}.content{margin-left:280px;padding:30px;}.stats-card{background:white;border-radius:12px;padding:25px;margin-bottom:20px;box-shadow:0 2px 10px rgba(0,0,0,0.05);border-left:4px solid #667eea;}</style>
</head>
<body>
<div class="sidebar"><h3><i class="fas fa-cogs"></i> Órdenes</h3><a href="../../user/dashboard.php" class="btn btn-light btn-sm w-100 mt-3"><i class="fas fa-arrow-left"></i> Dashboard</a></div>
<div class="content">
<h2><i class="fas fa-cogs text-primary"></i> Órdenes de Fabricación</h2>
<?php if($mensaje): ?><div class="alert alert-success"><?php echo $mensaje; ?></div><?php endif; ?>
<div class="row">
<div class="col-md-4"><div class="stats-card"><h6 style="color:#667eea">Total Órdenes</h6><h3><?php echo $stats['total']; ?></h3></div></div>
<div class="col-md-4"><div class="stats-card"><h6 style="color:#48bb78">Cantidad Planificada</h6><h3><?php echo number_format($stats['cantidad_total'], 0); ?></h3></div></div>
<div class="col-md-4"><div class="stats-card"><h6 style="color:#9f7aea">Cantidad Producida</h6><h3><?php echo number_format($stats['cantidad_producida'], 0); ?></h3></div></div>
</div>
<div class="card">
<div class="card-body">
<table class="table table-striped">
<thead><tr><th>Orden</th><th>Producto</th><th>Planificada</th><th>Producida</th><th>Estado</th><th>Prioridad</th></tr></thead>
<tbody>
<?php
$stmt = $conn->prepare("SELECT op.numero_orden, p.nombre, op.cantidad_planificada, op.cantidad_producida, op.estado, op.prioridad FROM ordenes_produccion op INNER JOIN productos p ON op.producto_id = p.id WHERE op.empresa_id = ? ORDER BY op.prioridad DESC, op.fecha_inicio_planificada LIMIT 50");
$stmt->bind_param("i", $empresa_id);
$stmt->execute();
$result = $stmt->get_result();
while($row = $result->fetch_assoc()):
?>
<tr>
<td><?php echo htmlspecialchars($row['numero_orden']); ?></td>
<td><?php echo htmlspecialchars($row['nombre']); ?></td>
<td><?php echo number_format($row['cantidad_planificada'], 0); ?></td>
<td><?php echo number_format($row['cantidad_producida'], 0); ?></td>
<td><span class="badge bg-<?php echo $row['estado']=='completada'?'success':($row['estado']=='en_proceso'?'primary':'secondary'); ?>"><?php echo ucfirst($row['estado']); ?></span></td>
<td><span class="badge bg-<?php echo $row['prioridad']=='urgente'?'danger':($row['prioridad']=='alta'?'warning':'info'); ?>"><?php echo ucfirst($row['prioridad']); ?></span></td>
</tr>
<?php endwhile; $stmt->close(); ?>
</tbody>
</table>
</div>
</div>
</div>
</body>
</html>
