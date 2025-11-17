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

    <!-- Footer Profesional -->
    <?php include '../includes/footer.php'; ?>

</body></html>
