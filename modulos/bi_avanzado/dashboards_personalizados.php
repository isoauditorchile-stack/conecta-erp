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

// Crear dashboard
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_dashboard'])) {
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $tipo = $_POST['tipo'];
    
    $stmt = $conn->prepare("INSERT INTO dashboards_personalizados (empresa_id, usuario_id, nombre, descripcion, tipo) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iisss", $empresa_id, $usuario_id, $nombre, $descripcion, $tipo);
    $stmt->execute();
    $success_msg = "Dashboard creado exitosamente";
}

// Obtener dashboards
$dashboards = $conn->query("SELECT d.*, u.nombre as creador_nombre 
    FROM dashboards_personalizados d 
    LEFT JOIN usuarios u ON d.usuario_id = u.id 
    WHERE d.empresa_id = $empresa_id 
    ORDER BY d.fecha_creacion DESC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Dashboards Personalizados</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
.dashboard-card { border-left: 4px solid #3498db; transition: all 0.3s; cursor: pointer; }
.dashboard-card:hover { transform: translateY(-5px); box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
</style>
</head><body class="bg-light">
<?php include '../includes/sidebar.php'; ?>
<div class="content-wrapper"><div class="container-fluid py-4">
<h2><i class="fas fa-tachometer-alt"></i> Dashboards Personalizados</h2>

<?php if (isset($success_msg)): ?>
<div class="alert alert-success"><?= htmlspecialchars($success_msg) ?></div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Crear Nuevo Dashboard</h5></div>
    <div class="card-body">
        <form method="POST">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label>Nombre del Dashboard *</label>
                    <input type="text" name="nombre" class="form-control" placeholder="Dashboard Ventas" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label>Tipo *</label>
                    <select name="tipo" class="form-control" required>
                        <option value="ventas">Ventas</option>
                        <option value="finanzas">Finanzas</option>
                        <option value="inventario">Inventario</option>
                        <option value="rrhh">RRHH</option>
                        <option value="proyectos">Proyectos</option>
                        <option value="personalizado">Personalizado</option>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label>Descripción</label>
                    <input type="text" name="descripcion" class="form-control">
                </div>
                <div class="col-md-2 mb-3">
                    <label>&nbsp;</label>
                    <button type="submit" name="crear_dashboard" class="btn btn-primary w-100">
                        <i class="fas fa-plus"></i> Crear
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<h4>Plantillas de Dashboard</h4>
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card dashboard-card" onclick="alert('Crear desde plantilla: Dashboard Ejecutivo')">
            <div class="card-body">
                <h5><i class="fas fa-chart-line text-primary"></i> Dashboard Ejecutivo</h5>
                <p class="text-muted">Vista general de KPIs principales: ventas, gastos, utilidad, clientes</p>
                <span class="badge bg-primary">Recomendado</span>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card dashboard-card" onclick="alert('Crear desde plantilla: Dashboard Comercial')">
            <div class="card-body">
                <h5><i class="fas fa-shopping-cart text-success"></i> Dashboard Comercial</h5>
                <p class="text-muted">Ventas por vendedor, productos, regiones y canales de venta</p>
                <span class="badge bg-success">Ventas</span>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card dashboard-card" onclick="alert('Crear desde plantilla: Dashboard Financiero')">
            <div class="card-body">
                <h5><i class="fas fa-dollar-sign text-warning"></i> Dashboard Financiero</h5>
                <p class="text-muted">Flujo de caja, cuentas por cobrar/pagar, rentabilidad</p>
                <span class="badge bg-warning">Finanzas</span>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card dashboard-card" onclick="alert('Crear desde plantilla: Dashboard Inventario')">
            <div class="card-body">
                <h5><i class="fas fa-boxes text-info"></i> Dashboard Inventario</h5>
                <p class="text-muted">Stock actual, rotación, productos críticos, valorización</p>
                <span class="badge bg-info">Operaciones</span>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card dashboard-card" onclick="alert('Crear desde plantilla: Dashboard RRHH')">
            <div class="card-body">
                <h5><i class="fas fa-users text-secondary"></i> Dashboard RRHH</h5>
                <p class="text-muted">Asistencia, horas trabajadas, vacaciones, rotación de personal</p>
                <span class="badge bg-secondary">RRHH</span>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card dashboard-card" onclick="alert('Crear desde plantilla: Dashboard Proyectos')">
            <div class="card-body">
                <h5><i class="fas fa-tasks text-danger"></i> Dashboard Proyectos</h5>
                <p class="text-muted">Avance de proyectos, presupuesto vs real, tareas por estado</p>
                <span class="badge bg-danger">Proyectos</span>
            </div>
        </div>
    </div>
</div>

<h4>Mis Dashboards</h4>
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Tipo</th>
                        <th>Descripción</th>
                        <th>Creado por</th>
                        <th>Fecha Creación</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dashboards as $d): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($d['nombre']) ?></strong></td>
                        <td>
                            <?php
                            $badges = ['ventas' => 'success', 'finanzas' => 'warning', 'inventario' => 'info', 'rrhh' => 'secondary', 'proyectos' => 'danger', 'personalizado' => 'primary'];
                            echo '<span class="badge bg-' . ($badges[$d['tipo']] ?? 'primary') . '">' . ucfirst($d['tipo']) . '</span>';
                            ?>
                        </td>
                        <td><?= htmlspecialchars($d['descripcion'] ?? '') ?></td>
                        <td><?= htmlspecialchars($d['creador_nombre']) ?></td>
                        <td><?= date('d/m/Y', strtotime($d['fecha_creacion'])) ?></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-primary" onclick="verDashboard(<?= $d['id'] ?>)">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-warning" onclick="editarDashboard(<?= $d['id'] ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-info" onclick="compartirDashboard(<?= $d['id'] ?>)">
                                    <i class="fas fa-share-alt"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($dashboards)): ?>
                    <tr><td colspan="6" class="text-center">No hay dashboards personalizados. Cree uno usando las plantillas de arriba.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function verDashboard(id) {
    window.location.href = 'dashboard_view.php?id=' + id;
}
function editarDashboard(id) {
    alert('Editar dashboard #' + id + ' - Constructor de dashboards en desarrollo');
}
function compartirDashboard(id) {
    alert('Compartir dashboard con otros usuarios');
}
</script>
<?php include '../includes/footer.php'; ?>
</body></html>
