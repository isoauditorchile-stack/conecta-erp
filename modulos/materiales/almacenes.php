<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
requireLogin();
$usuario_id = $_SESSION['usuario_id'];
$empresa_id = $_SESSION['empresa_id'];
$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_almacen'])) {
    $codigo = trim($_POST['codigo']);
    $nombre = trim($_POST['nombre']);
    $tipo = $_POST['tipo'];

    $stmt = $conn->prepare("INSERT INTO almacenes (empresa_id, codigo, nombre, tipo, creado_por) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("isssi", $empresa_id, $codigo, $nombre, $tipo, $usuario_id);
    if ($stmt->execute()) {
        logAuditoria($conn, $usuario_id, $empresa_id, 'crear', 'almacenes', $conn->insert_id, 'materiales', "Almacén: $codigo");
        $mensaje = "Almacén creado exitosamente";
    }
    $stmt->close();
}

$stmt = $conn->prepare("SELECT COUNT(*) as total, COALESCE(SUM(CASE WHEN activo=1 THEN 1 ELSE 0 END), 0) as activos FROM almacenes WHERE empresa_id = ?");
$stmt->bind_param("i", $empresa_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>Almacenes - CONECTA ERP</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>.sidebar{position:fixed;left:0;top:0;width:280px;height:100vh;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:white;padding:20px;}.content{margin-left:280px;padding:30px;}.stats-card{background:white;border-radius:12px;padding:25px;margin-bottom:20px;box-shadow:0 2px 10px rgba(0,0,0,0.05);border-left:4px solid #667eea;}</style>
</head>
<body>
<div class="sidebar"><h3><i class="fas fa-building"></i> Almacenes</h3><a href="../../user/dashboard.php" class="btn btn-light btn-sm w-100 mt-3"><i class="fas fa-arrow-left"></i> Dashboard</a></div>
<div class="content">
<h2><i class="fas fa-building text-primary"></i> Gestión de Almacenes</h2>
<?php if($mensaje): ?><div class="alert alert-success"><?php echo $mensaje; ?></div><?php endif; ?>
<div class="row">
<div class="col-md-6"><div class="stats-card"><h6 style="color:#667eea">Total Almacenes</h6><h3><?php echo $stats['total']; ?></h3></div></div>
<div class="col-md-6"><div class="stats-card"><h6 style="color:#48bb78">Almacenes Activos</h6><h3><?php echo $stats['activos']; ?></h3></div></div>
</div>
<div class="card">
<div class="card-header"><h5><i class="fas fa-list"></i> Listado de Almacenes</h5></div>
<div class="card-body">
<table class="table table-striped">
<thead><tr><th>Código</th><th>Nombre</th><th>Tipo</th><th>Dirección</th><th>Estado</th></tr></thead>
<tbody>
<?php
$stmt = $conn->prepare("SELECT codigo, nombre, tipo, direccion, activo FROM almacenes WHERE empresa_id = ? ORDER BY codigo");
$stmt->bind_param("i", $empresa_id);
$stmt->execute();
$result = $stmt->get_result();
while($row = $result->fetch_assoc()):
?>
<tr>
<td><?php echo htmlspecialchars($row['codigo']); ?></td>
<td><?php echo htmlspecialchars($row['nombre']); ?></td>
<td><?php echo ucfirst($row['tipo']); ?></td>
<td><?php echo htmlspecialchars($row['direccion']); ?></td>
<td><span class="badge bg-<?php echo $row['activo']?'success':'secondary'; ?>"><?php echo $row['activo']?'Activo':'Inactivo'; ?></span></td>
</tr>
<?php endwhile; $stmt->close(); ?>
</tbody>
</table>
</div>
</div>
<div class="card mt-4">
<div class="card-header"><h5><i class="fas fa-boxes"></i> Stock por Almacén</h5></div>
<div class="card-body">
<table class="table table-striped">
<thead><tr><th>Almacén</th><th>Producto</th><th>Cantidad</th><th>Ubicación</th></tr></thead>
<tbody>
<?php
$stmt = $conn->prepare("SELECT a.nombre as almacen, p.nombre as producto, spa.cantidad, spa.ubicacion FROM stock_por_almacen spa INNER JOIN almacenes a ON spa.almacen_id = a.id INNER JOIN productos p ON spa.producto_id = p.id WHERE spa.empresa_id = ? ORDER BY a.nombre, p.nombre LIMIT 50");
$stmt->bind_param("i", $empresa_id);
$stmt->execute();
$result = $stmt->get_result();
while($row = $result->fetch_assoc()):
?>
<tr>
<td><?php echo htmlspecialchars($row['almacen']); ?></td>
<td><?php echo htmlspecialchars($row['producto']); ?></td>
<td><?php echo number_format($row['cantidad'], 2); ?></td>
<td><?php echo htmlspecialchars($row['ubicacion']); ?></td>
</tr>
<?php endwhile; $stmt->close(); ?>
</tbody>
</table>
</div>
</div>
</div>
</body>
</html>
