#!/bin/bash

# MÓDULO REPORTES - 4 archivos
cat > /home/user/conecta-erp/modulos/reportes/ventas.php << 'PHPEOF'
<?php
session_start();
require_once '../../includes/config.php';
requireLogin();
$empresa_id = $_SESSION['empresa_id'];

$periodo_inicio = $_GET['inicio'] ?? date('Y-m-01');
$periodo_fin = $_GET['fin'] ?? date('Y-m-t');

$stmt = $conn->prepare("SELECT DATE(fecha_emision) as fecha, COUNT(*) as cantidad, SUM(total) as total FROM facturas WHERE empresa_id = ? AND fecha_emision BETWEEN ? AND ? AND estado NOT IN ('anulada') GROUP BY DATE(fecha_emision) ORDER BY fecha DESC");
$stmt->bind_param("iss", $empresa_id, $periodo_inicio, $periodo_fin);
$stmt->execute();
$ventas = $stmt->get_result();
?>
<!DOCTYPE html>
<html><head><title>Reporte de Ventas</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head><body>
<div class="container-fluid p-4">
<h1><i class="fas fa-chart-bar"></i> Reporte de Ventas</h1>
<div class="card">
<div class="card-header">Filtros</div>
<div class="card-body">
<form method="GET" class="row g-3">
<div class="col-md-4"><label>Desde</label><input type="date" name="inicio" class="form-control" value="<?php echo $periodo_inicio; ?>"></div>
<div class="col-md-4"><label>Hasta</label><input type="date" name="fin" class="form-control" value="<?php echo $periodo_fin; ?>"></div>
<div class="col-md-4"><button type="submit" class="btn btn-primary mt-4">Buscar</button></div>
</form>
</div>
</div>
<div class="card mt-3">
<div class="card-body">
<table class="table">
<thead><tr><th>Fecha</th><th>Cantidad Facturas</th><th>Total Ventas</th></tr></thead>
<tbody>
<?php 
$total_general = 0;
while($v = $ventas->fetch_assoc()): 
$total_general += $v['total'];
?>
<tr>
<td><?php echo date('d/m/Y', strtotime($v['fecha'])); ?></td>
<td><?php echo $v['cantidad']; ?></td>
<td>$<?php echo number_format($v['total'], 0, ',', '.'); ?></td>
</tr>
<?php endwhile; ?>
</tbody>
<tfoot><tr class="table-active"><th colspan="2">TOTAL GENERAL</th><th>$<?php echo number_format($total_general, 0, ',', '.'); ?></th></tr></tfoot>
</table>
</div>
</div>
</div>
</body></html>
PHPEOF

cat > /home/user/conecta-erp/modulos/reportes/compras.php << 'PHPEOF'
<?php
session_start();
require_once '../../includes/config.php';
requireLogin();
$empresa_id = $_SESSION['empresa_id'];
$periodo_inicio = $_GET['inicio'] ?? date('Y-m-01');
$periodo_fin = $_GET['fin'] ?? date('Y-m-t');
$stmt = $conn->prepare("SELECT DATE(fecha_recepcion) as fecha, COUNT(*) as cantidad, SUM(total) as total FROM facturas_compra WHERE empresa_id = ? AND fecha_recepcion BETWEEN ? AND ? GROUP BY DATE(fecha_recepcion) ORDER BY fecha DESC");
$stmt->bind_param("iss", $empresa_id, $periodo_inicio, $periodo_fin);
$stmt->execute();
$compras = $stmt->get_result();
?>
<!DOCTYPE html>
<html><head><title>Reporte de Compras</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head><body>
<div class="container-fluid p-4">
<h1><i class="fas fa-shopping-cart"></i> Reporte de Compras</h1>
<div class="card">
<div class="card-body">
<table class="table">
<thead><tr><th>Fecha</th><th>Cantidad Facturas</th><th>Total Compras</th></tr></thead>
<tbody>
<?php $total = 0; while($c = $compras->fetch_assoc()): $total += $c['total']; ?>
<tr><td><?php echo date('d/m/Y', strtotime($c['fecha'])); ?></td><td><?php echo $c['cantidad']; ?></td><td>$<?php echo number_format($c['total'], 0, ',', '.'); ?></td></tr>
<?php endwhile; ?>
</tbody>
<tfoot><tr class="table-active"><th colspan="2">TOTAL</th><th>$<?php echo number_format($total, 0, ',', '.'); ?></th></tr></tfoot>
</table>
</div>
</div>
</div>
</body></html>
PHPEOF

# MÓDULO VENTAS - Archivos principales
cat > /home/user/conecta-erp/modulos/ventas/cotizaciones.php << 'PHPEOF'
<?php
session_start();
require_once '../../includes/config.php';
requireLogin();
$empresa_id = $_SESSION['empresa_id'];
$cotizaciones = $conn->query("SELECT c.*, cl.razon_social FROM cotizaciones c LEFT JOIN clientes cl ON c.cliente_id = cl.id WHERE c.empresa_id = $empresa_id ORDER BY c.id DESC LIMIT 50");
?>
<!DOCTYPE html>
<html><head><title>Cotizaciones</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head><body>
<div class="container-fluid p-4">
<h1><i class="fas fa-file-alt"></i> Cotizaciones</h1>
<button class="btn btn-primary mb-3"><i class="fas fa-plus"></i> Nueva Cotización</button>
<table class="table">
<thead><tr><th>N°</th><th>Cliente</th><th>Fecha</th><th>Total</th><th>Estado</th><th>Acciones</th></tr></thead>
<tbody>
<?php while($cot = $cotizaciones->fetch_assoc()): ?>
<tr>
<td><?php echo $cot['numero_cotizacion']; ?></td>
<td><?php echo htmlspecialchars($cot['razon_social']); ?></td>
<td><?php echo date('d/m/Y', strtotime($cot['fecha_emision'])); ?></td>
<td>$<?php echo number_format($cot['total'], 0, ',', '.'); ?></td>
<td><span class="badge bg-<?php echo $cot['estado'] == 'aprobada' ? 'success' : 'warning'; ?>"><?php echo $cot['estado']; ?></span></td>
<td><button class="btn btn-sm btn-info"><i class="fas fa-eye"></i></button></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
</body></html>
PHPEOF

cat > /home/user/conecta-erp/modulos/ventas/pedidos.php << 'PHPEOF'
<?php
session_start();
require_once '../../includes/config.php';
requireLogin();
$empresa_id = $_SESSION['empresa_id'];
$pedidos = $conn->query("SELECT p.*, c.razon_social FROM pedidos p LEFT JOIN clientes c ON p.cliente_id = c.id WHERE p.empresa_id = $empresa_id ORDER BY p.id DESC LIMIT 50");
?>
<!DOCTYPE html>
<html><head><title>Pedidos</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head><body>
<div class="container-fluid p-4">
<h1><i class="fas fa-clipboard-list"></i> Pedidos</h1>
<table class="table">
<thead><tr><th>N°</th><th>Cliente</th><th>Fecha</th><th>Total</th><th>Estado</th></tr></thead>
<tbody>
<?php while($p = $pedidos->fetch_assoc()): ?>
<tr>
<td><?php echo $p['numero_pedido']; ?></td>
<td><?php echo htmlspecialchars($p['razon_social']); ?></td>
<td><?php echo date('d/m/Y', strtotime($p['fecha_pedido'])); ?></td>
<td>$<?php echo number_format($p['total'], 0, ',', '.'); ?></td>
<td><span class="badge bg-<?php echo $p['estado'] == 'entregado' ? 'success' : 'warning'; ?>"><?php echo $p['estado']; ?></span></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
</body></html>
PHPEOF

# MÓDULO COMPRAS
cat > /home/user/conecta-erp/modulos/compras/ordenes_compra.php << 'PHPEOF'
<?php
session_start();
require_once '../../includes/config.php';
requireLogin();
$empresa_id = $_SESSION['empresa_id'];
$ordenes = $conn->query("SELECT o.*, p.razon_social FROM ordenes_compra o LEFT JOIN proveedores p ON o.proveedor_id = p.id WHERE o.empresa_id = $empresa_id ORDER BY o.id DESC LIMIT 50");
?>
<!DOCTYPE html>
<html><head><title>Órdenes de Compra</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head><body>
<div class="container-fluid p-4">
<h1><i class="fas fa-shopping-bag"></i> Órdenes de Compra</h1>
<table class="table">
<thead><tr><th>N°</th><th>Proveedor</th><th>Fecha</th><th>Total</th><th>Estado</th></tr></thead>
<tbody>
<?php while($o = $ordenes->fetch_assoc()): ?>
<tr>
<td><?php echo $o['numero_orden']; ?></td>
<td><?php echo htmlspecialchars($o['razon_social']); ?></td>
<td><?php echo date('d/m/Y', strtotime($o['fecha_emision'])); ?></td>
<td>$<?php echo number_format($o['total'], 0, ',', '.'); ?></td>
<td><span class="badge bg-info"><?php echo $o['estado']; ?></span></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
</body></html>
PHPEOF

# MÓDULO INVENTARIO
cat > /home/user/conecta-erp/modulos/inventario/productos.php << 'PHPEOF'
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
</body></html>
PHPEOF

# MÓDULO TRIBUTARIO
cat > /home/user/conecta-erp/modulos/tributario/folios.php << 'PHPEOF'
<?php
session_start();
require_once '../../includes/config.php';
requireLogin();
$empresa_id = $_SESSION['empresa_id'];
$folios = $conn->query("SELECT f.*, t.nombre FROM folios_caf f LEFT JOIN tipos_documentos_sii t ON f.tipo_documento = t.codigo WHERE f.empresa_id = $empresa_id ORDER BY f.fecha_creacion DESC");
?>
<!DOCTYPE html>
<html><head><title>Folios CAF</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head><body>
<div class="container-fluid p-4">
<h1><i class="fas fa-barcode"></i> Gestión de Folios CAF</h1>
<button class="btn btn-primary mb-3"><i class="fas fa-upload"></i> Cargar CAF</button>
<table class="table">
<thead><tr><th>Tipo Doc</th><th>Desde</th><th>Hasta</th><th>Actual</th><th>Disponibles</th><th>Estado</th></tr></thead>
<tbody>
<?php while($f = $folios->fetch_assoc()): ?>
<tr>
<td><?php echo htmlspecialchars($f['nombre']); ?> (<?php echo $f['tipo_documento']; ?>)</td>
<td><?php echo $f['folio_desde']; ?></td>
<td><?php echo $f['folio_hasta']; ?></td>
<td><?php echo $f['folio_actual']; ?></td>
<td><strong><?php echo $f['folios_disponibles']; ?></strong></td>
<td><span class="badge bg-<?php echo $f['folios_disponibles'] > 10 ? 'success' : 'danger'; ?>"><?php echo $f['estado']; ?></span></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
</body></html>
PHPEOF

echo "Todos los archivos de módulos creados correctamente!"
