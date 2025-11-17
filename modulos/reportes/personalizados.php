<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
requireLogin();
$usuario_id = $_SESSION['usuario_id'];
$stmt = $conn->prepare("SELECT empresa_id FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$empresa_id = $stmt->get_result()->fetch_assoc()['empresa_id'];

// Crear reporte
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_reporte'])) {
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $categoria = $_POST['categoria'];
    $query_sql = trim($_POST['query_sql']);
    $formato_salida = $_POST['formato_salida'];
    
    $stmt = $conn->prepare("INSERT INTO reportes_personalizados (empresa_id, usuario_id, nombre, descripcion, categoria, query_sql, formato_salida) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iisssss", $empresa_id, $usuario_id, $nombre, $descripcion, $categoria, $query_sql, $formato_salida);
    $stmt->execute();
    $success_msg = "Reporte creado exitosamente";
}

// Obtener reportes
$reportes = $conn->query("SELECT r.*, u.nombre as creador_nombre 
    FROM reportes_personalizados r 
    LEFT JOIN usuarios u ON r.usuario_id = u.id 
    WHERE r.empresa_id = $empresa_id 
    ORDER BY r.categoria, r.nombre")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Reportes Personalizados</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head><body class="bg-light">
<?php include '../includes/sidebar.php'; ?>
<div class="content-wrapper"><div class="container-fluid py-4">
<h2><i class="fas fa-file-alt"></i> Reportes Personalizados</h2>

<?php if (isset($success_msg)): ?>
<div class="alert alert-success"><?= htmlspecialchars($success_msg) ?></div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Crear Nuevo Reporte</h5></div>
    <div class="card-body">
        <form method="POST">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label>Nombre del Reporte *</label>
                    <input type="text" name="nombre" class="form-control" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label>Categoría *</label>
                    <select name="categoria" class="form-control" required>
                        <option value="ventas">Ventas</option>
                        <option value="finanzas">Finanzas</option>
                        <option value="inventario">Inventario</option>
                        <option value="rrhh">RRHH</option>
                        <option value="contabilidad">Contabilidad</option>
                        <option value="tributario">Tributario</option>
                        <option value="proyectos">Proyectos</option>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label>Formato Salida *</label>
                    <select name="formato_salida" class="form-control" required>
                        <option value="pdf">PDF</option>
                        <option value="excel">Excel</option>
                        <option value="csv">CSV</option>
                        <option value="html">HTML</option>
                    </select>
                </div>
                <div class="col-md-2 mb-3">
                    <label>&nbsp;</label>
                    <button type="submit" name="crear_reporte" class="btn btn-primary w-100">
                        <i class="fas fa-plus"></i> Crear
                    </button>
                </div>
            </div>
            <div class="mb-3">
                <label>Descripción</label>
                <input type="text" name="descripcion" class="form-control">
            </div>
            <div class="mb-3">
                <label>Query SQL *</label>
                <textarea name="query_sql" class="form-control" rows="4" required placeholder="SELECT ... FROM ... WHERE ..."></textarea>
                <small class="text-muted">Escriba la consulta SQL que generará los datos del reporte</small>
            </div>
        </form>
    </div>
</div>

<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <a class="nav-link active" data-bs-toggle="tab" href="#mis-reportes">Mis Reportes</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#plantillas">Plantillas</a>
    </li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade show active" id="mis-reportes">
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Categoría</th>
                                <th>Formato</th>
                                <th>Creado por</th>
                                <th>Fecha Creación</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reportes as $r): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($r['nombre']) ?></strong><br><small class="text-muted"><?= htmlspecialchars($r['descripcion'] ?? '') ?></small></td>
                                <td>
                                    <?php
                                    $badges = ['ventas' => 'success', 'finanzas' => 'warning', 'inventario' => 'info', 'rrhh' => 'secondary', 'contabilidad' => 'primary', 'tributario' => 'danger', 'proyectos' => 'dark'];
                                    echo '<span class="badge bg-' . ($badges[$r['categoria']] ?? 'secondary') . '">' . ucfirst($r['categoria']) . '</span>';
                                    ?>
                                </td>
                                <td><code><?= strtoupper($r['formato_salida']) ?></code></td>
                                <td><?= htmlspecialchars($r['creador_nombre']) ?></td>
                                <td><?= date('d/m/Y', strtotime($r['fecha_creacion'])) ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-success" onclick="generarReporte(<?= $r['id'] ?>)">
                                            <i class="fas fa-play"></i> Generar
                                        </button>
                                        <button class="btn btn-primary" onclick="verReporte(<?= $r['id'] ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-warning" onclick="editarReporte(<?= $r['id'] ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($reportes)): ?>
                            <tr><td colspan="6" class="text-center">No hay reportes personalizados. Cree uno nuevo o use las plantillas.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="plantillas">
        <div class="row">
            <div class="col-md-6 mb-3">
                <div class="card">
                    <div class="card-body">
                        <h5><i class="fas fa-chart-line text-success"></i> Reporte de Ventas Mensual</h5>
                        <p class="text-muted">Ventas agrupadas por mes, con totales y comparación año anterior</p>
                        <button class="btn btn-sm btn-primary" onclick="usarPlantilla('ventas_mensual')">Usar Plantilla</button>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="card">
                    <div class="card-body">
                        <h5><i class="fas fa-users text-info"></i> Reporte de Clientes Top</h5>
                        <p class="text-muted">Top 50 clientes por volumen de compra y frecuencia</p>
                        <button class="btn btn-sm btn-primary" onclick="usarPlantilla('clientes_top')">Usar Plantilla</button>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="card">
                    <div class="card-body">
                        <h5><i class="fas fa-boxes text-warning"></i> Reporte de Inventario Valorizado</h5>
                        <p class="text-muted">Stock actual valorizado por producto y categoría</p>
                        <button class="btn btn-sm btn-primary" onclick="usarPlantilla('inventario_valorizado')">Usar Plantilla</button>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="card">
                    <div class="card-body">
                        <h5><i class="fas fa-file-invoice-dollar text-danger"></i> Reporte de Cuentas por Cobrar</h5>
                        <p class="text-muted">Facturas pendientes de pago por cliente y antigüedad</p>
                        <button class="btn btn-sm btn-primary" onclick="usarPlantilla('cxc')">Usar Plantilla</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function generarReporte(id) {
    window.open('generar_reporte.php?id=' + id, '_blank');
}
function verReporte(id) {
    window.location.href = 'ver_reporte.php?id=' + id;
}
function editarReporte(id) {
    alert('Editar reporte #' + id);
}
function usarPlantilla(tipo) {
    alert('Cargar plantilla: ' + tipo);
}
</script>
<?php include '../includes/footer.php'; ?>
</body></html>
