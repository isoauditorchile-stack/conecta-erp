<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
requireLogin();
$usuario_id = $_SESSION['usuario_id'];
$empresa_id = $_SESSION['empresa_id'];
$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_pedido'])) {
    $cliente_id = intval($_POST['cliente_id']);
    $fecha_pedido = $_POST['fecha_pedido'];
    
    $stmt = $conn->prepare("SELECT COALESCE(MAX(CAST(SUBSTRING(numero_pedido, 4) AS UNSIGNED)), 0) + 1 as siguiente FROM pedidos WHERE empresa_id = ?");
    $stmt->bind_param("i", $empresa_id);
    $stmt->execute();
    $siguiente = $stmt->get_result()->fetch_assoc()['siguiente'];
    $numero_pedido = sprintf("PED%06d", $siguiente);
    $stmt->close();

    $stmt = $conn->prepare("INSERT INTO pedidos (empresa_id, cliente_id, numero_pedido, fecha_pedido, vendedor_id, creado_por) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iissii", $empresa_id, $cliente_id, $numero_pedido, $fecha_pedido, $usuario_id, $usuario_id);
    if ($stmt->execute()) {
        logAuditoria($conn, $usuario_id, $empresa_id, 'crear', 'pedidos', $conn->insert_id, 'ventas', "Pedido: $numero_pedido");
        $mensaje = "Pedido $numero_pedido creado exitosamente";
    }
    $stmt->close();
}

$stmt = $conn->prepare("SELECT COUNT(*) as total, COALESCE(SUM(total), 0) as monto_total FROM pedidos WHERE empresa_id = ? AND estado != 'cancelado'");
$stmt->bind_param("i", $empresa_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>Pedidos - CONECTA ERP</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>.sidebar{position:fixed;left:0;top:0;width:280px;height:100vh;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:white;padding:20px;}.content{margin-left:280px;padding:30px;}</style>
</head>
<body>
<div class="sidebar"><h3><i class="fas fa-clipboard-list"></i> Pedidos</h3><a href="../../user/dashboard.php" class="btn btn-light btn-sm w-100 mt-3"><i class="fas fa-arrow-left"></i> Dashboard</a></div>
<div class="content">
<h2><i class="fas fa-clipboard-list text-primary"></i> Gestión de Pedidos</h2>
<?php if($mensaje): ?><div class="alert alert-success"><?php echo $mensaje; ?></div><?php endif; ?>
<div class="card">
<div class="card-body">
<table class="table table-striped">
<thead><tr><th>Número</th><th>Cliente</th><th>Fecha</th><th>Total</th><th>Estado</th></tr></thead>
<tbody>
<?php
$stmt = $conn->prepare("SELECT p.numero_pedido, c.razon_social, p.fecha_pedido, p.total, p.estado FROM pedidos p INNER JOIN clientes c ON p.cliente_id = c.id WHERE p.empresa_id = ? ORDER BY p.fecha_pedido DESC LIMIT 50");
$stmt->bind_param("i", $empresa_id);
$stmt->execute();
$result = $stmt->get_result();
while($row = $result->fetch_assoc()):
?>
<tr>
<td><?php echo htmlspecialchars($row['numero_pedido']); ?></td>
<td><?php echo htmlspecialchars($row['razon_social']); ?></td>
<td><?php echo date('d/m/Y', strtotime($row['fecha_pedido'])); ?></td>
<td><?php echo formatearMoneda($row['total']); ?></td>
<td><span class="badge bg-<?php echo $row['estado']=='entregado'?'success':'primary'; ?>"><?php echo ucfirst($row['estado']); ?></span></td>
</tr>
<?php endwhile; $stmt->close(); ?>
</tbody>
</table>
</div>
</div>
</div>
</body>
</html>
