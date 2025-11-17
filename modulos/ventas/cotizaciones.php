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
