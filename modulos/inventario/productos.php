<?php
session_start();
require_once '../../includes/config.php';
requireLogin();
$empresa_id = $_SESSION['empresa_id'];
$productos = $conn->query("SELECT p.*, c.nombre as categoria FROM productos p LEFT JOIN categorias_productos c ON p.categoria_id = c.id WHERE p.empresa_id = $empresa_id AND p.activo = 1 ORDER BY p.nombre LIMIT 100");
?>
<!DOCTYPE html>
<html><head><title>Productos</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head><body>
<div class="container-fluid p-4">
<h1><i class="fas fa-cube"></i> Productos</h1>
<button class="btn btn-primary mb-3"><i class="fas fa-plus"></i> Nuevo Producto</button>
<table class="table">
<thead><tr><th>Código</th><th>Nombre</th><th>Categoría</th><th>Precio Venta</th><th>Stock</th><th>Estado</th></tr></thead>
<tbody>
<?php while($prod = $productos->fetch_assoc()): 
$clase_stock = $prod['stock_actual'] <= $prod['stock_minimo'] ? 'text-danger' : 'text-success';
?>
<tr>
<td><?php echo htmlspecialchars($prod['codigo']); ?></td>
<td><?php echo htmlspecialchars($prod['nombre']); ?></td>
<td><?php echo htmlspecialchars($prod['categoria']); ?></td>
<td>$<?php echo number_format($prod['precio_venta'], 0, ',', '.'); ?></td>
<td class="<?php echo $clase_stock; ?>"><strong><?php echo $prod['stock_actual']; ?></strong></td>
<td><span class="badge bg-<?php echo $prod['stock_actual'] > $prod['stock_minimo'] ? 'success' : 'danger'; ?>">
<?php echo $prod['stock_actual'] > $prod['stock_minimo'] ? 'OK' : 'Stock Bajo'; ?>
</span></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>

    <!-- Footer Profesional -->
    <?php include '../includes/footer.php'; ?>

</body></html>
