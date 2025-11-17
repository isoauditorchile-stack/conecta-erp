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

// Crear métrica personalizada
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_metrica'])) {
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $query_sql = trim($_POST['query_sql']);
    $tipo_grafico = $_POST['tipo_grafico'];
    $frecuencia = $_POST['frecuencia'];
    
    $stmt = $conn->prepare("INSERT INTO metricas_personalizadas (empresa_id, nombre, descripcion, query_sql, tipo_grafico, frecuencia_actualizacion) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssss", $empresa_id, $nombre, $descripcion, $query_sql, $tipo_grafico, $frecuencia);
    $stmt->execute();
    $success_msg = "Métrica creada exitosamente";
}

// Obtener métricas
$metricas = $conn->query("SELECT * FROM metricas_personalizadas WHERE empresa_id = $empresa_id ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);

// Métricas predefinidas en tiempo real
$metricas_real = [
    'ventas_hoy' => $conn->query("SELECT COALESCE(SUM(total), 0) as valor FROM facturas WHERE empresa_id = $empresa_id AND DATE(fecha_emision) = CURDATE()")->fetch_assoc()['valor'],
    'ventas_mes' => $conn->query("SELECT COALESCE(SUM(total), 0) as valor FROM facturas WHERE empresa_id = $empresa_id AND MONTH(fecha_emision) = MONTH(CURDATE()) AND YEAR(fecha_emision) = YEAR(CURDATE())")->fetch_assoc()['valor'],
    'clientes_nuevos_mes' => $conn->query("SELECT COUNT(*) as valor FROM clientes WHERE empresa_id = $empresa_id AND MONTH(fecha_registro) = MONTH(CURDATE())")->fetch_assoc()['valor'],
    'productos_bajo_stock' => $conn->query("SELECT COUNT(*) as valor FROM productos WHERE empresa_id = $empresa_id AND stock < stock_minimo")->fetch_assoc()['valor'],
    'pedidos_pendientes' => $conn->query("SELECT COUNT(*) as valor FROM pedidos_web WHERE empresa_id = $empresa_id AND estado = 'pendiente'")->fetch_assoc()['valor'],
    'facturas_vencidas' => $conn->query("SELECT COUNT(*) as valor FROM facturas WHERE empresa_id = $empresa_id AND estado_pago = 'pendiente' AND fecha_vencimiento < CURDATE()")->fetch_assoc()['valor']
];
?>
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Métricas Avanzadas</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head><body class="bg-light">
<?php include '../includes/sidebar.php'; ?>
<div class="content-wrapper"><div class="container-fluid py-4">
<h2><i class="fas fa-chart-line"></i> Métricas Avanzadas</h2>

<?php if (isset($success_msg)): ?>
<div class="alert alert-success"><?= htmlspecialchars($success_msg) ?></div>
<?php endif; ?>

<h4 class="mt-4">Métricas en Tiempo Real</h4>
<div class="row mb-4">
    <div class="col-md-2">
        <div class="card bg-primary text-white">
            <div class="card-body text-center">
                <h6>Ventas Hoy</h6>
                <h3> <?= number_format($metricas_real['ventas_hoy'], 0, ',', '.') ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <h6>Ventas Este Mes</h6>
                <h3> <?= number_format($metricas_real['ventas_mes'], 0, ',', '.') ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-info text-white">
            <div class="card-body text-center">
                <h6>Clientes Nuevos</h6>
                <h3><?= $metricas_real['clientes_nuevos_mes'] ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-warning text-white">
            <div class="card-body text-center">
                <h6>Stock Bajo</h6>
                <h3><?= $metricas_real['productos_bajo_stock'] ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-secondary text-white">
            <div class="card-body text-center">
                <h6>Pedidos Pendientes</h6>
                <h3><?= $metricas_real['pedidos_pendientes'] ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-danger text-white">
            <div class="card-body text-center">
                <h6>Facturas Vencidas</h6>
                <h3><?= $metricas_real['facturas_vencidas'] ?></h3>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h5>Ventas Últimos 7 Días</h5>
                <canvas id="chartVentas7Dias"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h5>Top 5 Productos Más Vendidos</h5>
                <canvas id="chartTopProductos"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Crear Métrica Personalizada</h5></div>
    <div class="card-body">
        <form method="POST">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label>Nombre *</label>
                    <input type="text" name="nombre" class="form-control" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label>Tipo de Gráfico *</label>
                    <select name="tipo_grafico" class="form-control" required>
                        <option value="line">Línea</option>
                        <option value="bar">Barras</option>
                        <option value="pie">Torta</option>
                        <option value="doughnut">Dona</option>
                        <option value="number">Número</option>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label>Frecuencia Actualización *</label>
                    <select name="frecuencia" class="form-control" required>
                        <option value="tiempo_real">Tiempo Real</option>
                        <option value="horaria">Cada Hora</option>
                        <option value="diaria">Diaria</option>
                        <option value="semanal">Semanal</option>
                    </select>
                </div>
                <div class="col-md-2 mb-3">
                    <label>&nbsp;</label>
                    <button type="submit" name="crear_metrica" class="btn btn-primary w-100">
                        <i class="fas fa-plus"></i> Crear
                    </button>
                </div>
            </div>
            <div class="mb-3">
                <label>Descripción</label>
                <input type="text" name="descripcion" class="form-control">
            </div>
            <div class="mb-3">
                <label>Query SQL</label>
                <textarea name="query_sql" class="form-control" rows="3" placeholder="SELECT COUNT(*) as valor FROM ..."></textarea>
                <small class="text-muted">Query debe retornar columna 'valor' con el resultado numérico</small>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><h5 class="mb-0">Métricas Personalizadas</h5></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Tipo Gráfico</th>
                        <th>Frecuencia</th>
                        <th>Última Actualización</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($metricas as $m): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($m['nombre']) ?></strong></td>
                        <td><?= htmlspecialchars($m['descripcion'] ?? '') ?></td>
                        <td><span class="badge bg-info"><?= ucfirst($m['tipo_grafico']) ?></span></td>
                        <td><?= ucfirst(str_replace('_', ' ', $m['frecuencia_actualizacion'])) ?></td>
                        <td><?= $m['ultima_actualizacion'] ? date('d/m/Y H:i', strtotime($m['ultima_actualizacion'])) : 'Nunca' ?></td>
                        <td>
                            <button class="btn btn-sm btn-success" onclick="alert('Ejecutar métrica')">
                                <i class="fas fa-play"></i>
                            </button>
                            <button class="btn btn-sm btn-primary" onclick="alert('Ver gráfico')">
                                <i class="fas fa-chart-bar"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($metricas)): ?>
                    <tr><td colspan="6" class="text-center">No hay métricas personalizadas</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Ventas últimos 7 días
<?php
$ventas_7dias = $conn->query("SELECT DATE(fecha_emision) as fecha, COALESCE(SUM(total), 0) as total 
    FROM facturas 
    WHERE empresa_id = $empresa_id AND fecha_emision >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(fecha_emision) 
    ORDER BY fecha")->fetch_all(MYSQLI_ASSOC);
$labels_ventas = json_encode(array_column($ventas_7dias, 'fecha'));
$data_ventas = json_encode(array_column($ventas_7dias, 'total'));
?>
new Chart(document.getElementById('chartVentas7Dias'), {
    type: 'line',
    data: {
        labels: <?= $labels_ventas ?>,
        datasets: [{
            label: 'Ventas ($)',
            data: <?= $data_ventas ?>,
            borderColor: '#3498db',
            backgroundColor: 'rgba(52, 152, 219, 0.1)',
            tension: 0.4
        }]
    },
    options: { responsive: true }
});

// Top 5 productos
<?php
$top_productos = $conn->query("SELECT p.nombre, SUM(fd.cantidad) as total_vendido 
    FROM facturas_detalle fd 
    LEFT JOIN productos p ON fd.producto_id = p.id 
    LEFT JOIN facturas f ON fd.factura_id = f.id
    WHERE f.empresa_id = $empresa_id 
    GROUP BY p.id 
    ORDER BY total_vendido DESC 
    LIMIT 5")->fetch_all(MYSQLI_ASSOC);
$labels_productos = json_encode(array_column($top_productos, 'nombre'));
$data_productos = json_encode(array_column($top_productos, 'total_vendido'));
?>
new Chart(document.getElementById('chartTopProductos'), {
    type: 'bar',
    data: {
        labels: <?= $labels_productos ?>,
        datasets: [{
            label: 'Unidades Vendidas',
            data: <?= $data_productos ?>,
            backgroundColor: ['#3498db', '#2ecc71', '#f39c12', '#e74c3c', '#9b59b6']
        }]
    },
    options: { responsive: true }
});
</script>
<?php include '../includes/footer.php'; ?>
</body></html>
