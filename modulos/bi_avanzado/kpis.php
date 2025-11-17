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
$stmt->close();

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_kpi'])) {
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $formula = trim($_POST['formula']);
    $meta = floatval($_POST['meta']);
    $frecuencia = $_POST['frecuencia'];

    $stmt = $conn->prepare("INSERT INTO kpis (empresa_id, nombre, descripcion, formula, meta, frecuencia, activo) VALUES (?, ?, ?, ?, ?, ?, 1)");
    $stmt->bind_param("isssds", $empresa_id, $nombre, $descripcion, $formula, $meta, $frecuencia);
    $stmt->execute();
    $mensaje = "KPI creado exitosamente";
    $stmt->close();
}

$kpis = $conn->query("SELECT * FROM kpis WHERE empresa_id = $empresa_id ORDER BY fecha_creacion DESC");

$kpi_data = [];
$result = $kpis;
while ($row = $result->fetch_assoc()) {
    $valor_actual = 0;
    if ($row['formula'] === 'ventas_mes') {
        $valor_actual = $conn->query("SELECT COALESCE(SUM(total), 0) as total FROM facturas WHERE empresa_id = $empresa_id AND MONTH(fecha_emision) = MONTH(CURDATE())")->fetch_assoc()['total'];
    } elseif ($row['formula'] === 'clientes_nuevos') {
        $valor_actual = $conn->query("SELECT COUNT(*) as total FROM clientes WHERE empresa_id = $empresa_id AND MONTH(fecha_creacion) = MONTH(CURDATE())")->fetch_assoc()['total'];
    }
    $row['valor_actual'] = $valor_actual;
    $row['cumplimiento'] = $row['meta'] > 0 ? ($valor_actual / $row['meta']) * 100 : 0;
    $kpi_data[] = $row;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>KPIs - CONECTA ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php include '../includes/sidebar.php'; ?>
    <div class="content-wrapper">
        <div class="container-fluid py-4">
            <div class="d-flex justify-content-between mb-4">
                <h2><i class="fas fa-chart-line"></i> KPIs - Indicadores Clave</h2>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrear">
                    <i class="fas fa-plus"></i> Nuevo KPI
                </button>
            </div>
            <?php if ($mensaje): ?>
                <div class="alert alert-success"><?php echo $mensaje; ?></div>
            <?php endif; ?>
            <div class="row">
                <?php foreach ($kpi_data as $kpi): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <h5><?php echo $kpi['nombre']; ?></h5>
                                <p class="text-muted small"><?php echo $kpi['descripcion']; ?></p>
                                <div class="d-flex justify-content-between align-items-end mb-3">
                                    <div>
                                        <h3 class="mb-0"><?php echo number_format($kpi['valor_actual'], 0, ',', '.'); ?></h3>
                                        <small class="text-muted">Meta: <?php echo number_format($kpi['meta'], 0, ',', '.'); ?></small>
                                    </div>
                                    <div>
                                        <h4 class="mb-0 <?php echo $kpi['cumplimiento'] >= 100 ? 'text-success' : 'text-warning'; ?>">
                                            <?php echo round($kpi['cumplimiento']); ?>%
                                        </h4>
                                    </div>
                                </div>
                                <div class="progress" style="height: 10px;">
                                    <div class="progress-bar <?php echo $kpi['cumplimiento'] >= 100 ? 'bg-success' : 'bg-warning'; ?>"
                                         style="width: <?php echo min($kpi['cumplimiento'], 100); ?>%"></div>
                                </div>
                                <div class="mt-3">
                                    <small class="text-muted">
                                        <i class="fas fa-clock"></i> <?php echo ucfirst($kpi['frecuencia']); ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="modal fade" id="modalCrear">
        <div class="modal-dialog">
            <form method="POST" class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5>Nuevo KPI</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Nombre</label>
                        <input type="text" name="nombre" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Descripción</label>
                        <textarea name="descripcion" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label>Fórmula/Tipo</label>
                        <select name="formula" class="form-select">
                            <option value="ventas_mes">Ventas del Mes</option>
                            <option value="clientes_nuevos">Clientes Nuevos</option>
                            <option value="margen_bruto">Margen Bruto</option>
                            <option value="rotacion_inventario">Rotación Inventario</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Meta</label>
                        <input type="number" name="meta" class="form-control" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label>Frecuencia</label>
                        <select name="frecuencia" class="form-select">
                            <option value="diario">Diario</option>
                            <option value="semanal">Semanal</option>
                            <option value="mensual">Mensual</option>
                            <option value="anual">Anual</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="crear_kpi" class="btn btn-primary">Crear KPI</button>
                </div>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>function toggleSidebar() { document.getElementById('sidebar').classList.toggle('collapsed'); }</script>
    <?php include '../includes/footer.php'; ?>
</body>
</html>
