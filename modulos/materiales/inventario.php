<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
requireLogin();
$usuario_id = $_SESSION['usuario_id'];
$empresa_id = $_SESSION['empresa_id'];

$stmt = $conn->prepare("SELECT
    (SELECT COUNT(*) FROM productos WHERE empresa_id = ?) as total_productos,
    (SELECT COALESCE(SUM(stock_actual), 0) FROM productos WHERE empresa_id = ?) as stock_total,
    (SELECT COUNT(*) FROM productos WHERE empresa_id = ? AND stock_actual < stock_minimo) as productos_bajo_stock,
    (SELECT COUNT(*) FROM almacenes WHERE empresa_id = ? AND activo = 1) as almacenes_activos");
$stmt->bind_param("iiii", $empresa_id, $empresa_id, $empresa_id, $empresa_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>Inventario - CONECTA ERP</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>.sidebar{position:fixed;left:0;top:0;width:280px;height:100vh;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:white;padding:20px;}.content{margin-left:280px;padding:30px;}.stats-card{background:white;border-radius:12px;padding:25px;margin-bottom:20px;box-shadow:0 2px 10px rgba(0,0,0,0.05);border-left:4px solid #667eea;}</style>
</head>
<body>
<div class="sidebar"><h3><i class="fas fa-warehouse"></i> Inventario</h3><a href="../../user/dashboard.php" class="btn btn-light btn-sm w-100 mt-3"><i class="fas fa-arrow-left"></i> Dashboard</a></div>
<div class="content">
<h2><i class="fas fa-warehouse text-primary"></i> Gestión de Inventario</h2>
<div class="row">
<div class="col-md-3"><div class="stats-card"><h6 style="color:#667eea">Total Productos</h6><h3><?php echo number_format($stats['total_productos']); ?></h3></div></div>
<div class="col-md-3"><div class="stats-card"><h6 style="color:#48bb78">Stock Total</h6><h3><?php echo number_format($stats['stock_total'], 2); ?></h3></div></div>
<div class="col-md-3"><div class="stats-card"><h6 style="color:#ed8936">Bajo Stock</h6><h3><?php echo number_format($stats['productos_bajo_stock']); ?></h3></div></div>
<div class="col-md-3"><div class="stats-card"><h6 style="color:#9f7aea">Almacenes</h6><h3><?php echo $stats['almacenes_activos']; ?></h3></div></div>
</div>
<div class="card">
<div class="card-header"><h5><i class="fas fa-list"></i> Productos con Stock Bajo</h5></div>
<div class="card-body">
<table class="table table-striped">
<thead><tr><th>SKU</th><th>Producto</th><th>Stock Actual</th><th>Stock Mínimo</th><th>Déficit</th></tr></thead>
<tbody>
<?php
$stmt = $conn->prepare("SELECT sku, nombre, stock_actual, stock_minimo, (stock_minimo - stock_actual) as deficit FROM productos WHERE empresa_id = ? AND stock_actual < stock_minimo ORDER BY deficit DESC LIMIT 20");
$stmt->bind_param("i", $empresa_id);
$stmt->execute();
$result = $stmt->get_result();
while($row = $result->fetch_assoc()):
?>
<tr>
<td><?php echo htmlspecialchars($row['sku']); ?></td>
<td><?php echo htmlspecialchars($row['nombre']); ?></td>
<td><span class="badge bg-danger"><?php echo number_format($row['stock_actual'], 2); ?></span></td>
<td><?php echo number_format($row['stock_minimo'], 2); ?></td>
<td><?php echo number_format($row['deficit'], 2); ?></td>
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
