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

$proyecto_id = isset($_GET['proyecto']) ? intval($_GET['proyecto']) : 0;

// Crear tarea
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_tarea'])) {
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $fecha_inicio = $_POST['fecha_inicio'];
    $fecha_fin = $_POST['fecha_fin'];
    $asignado_a = intval($_POST['asignado_a']);
    $prioridad = $_POST['prioridad'];
    $proyecto_id_post = intval($_POST['proyecto_id']);
    
    $stmt = $conn->prepare("INSERT INTO tareas_proyecto (proyecto_id, nombre, descripcion, fecha_inicio, fecha_fin, asignado_a, prioridad, estado) VALUES (?, ?, ?, ?, ?, ?, ?, 'pendiente')");
    $stmt->bind_param("isssiis", $proyecto_id_post, $nombre, $descripcion, $fecha_inicio, $fecha_fin, $asignado_a, $prioridad);
    $stmt->execute();
    $success_msg = "Tarea creada exitosamente";
}

// Cambiar estado tarea
if (isset($_GET['cambiar_estado'])) {
    $tarea_id = intval($_GET['cambiar_estado']);
    $nuevo_estado = $_GET['estado'];
    $conn->query("UPDATE tareas_proyecto SET estado = '$nuevo_estado' WHERE id = $tarea_id");
    header("Location: tareas.php" . ($proyecto_id ? "?proyecto=$proyecto_id" : ""));
    exit;
}

// Obtener proyectos
$proyectos = $conn->query("SELECT id, nombre FROM proyectos WHERE empresa_id = $empresa_id ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);

// Obtener usuarios
$usuarios = $conn->query("SELECT id, nombre FROM usuarios WHERE empresa_id = $empresa_id ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);

// Obtener tareas
$where_proyecto = $proyecto_id ? "AND t.proyecto_id = $proyecto_id" : "";
$tareas = $conn->query("SELECT t.*, p.nombre as proyecto_nombre, u.nombre as asignado_nombre 
    FROM tareas_proyecto t 
    LEFT JOIN proyectos p ON t.proyecto_id = p.id 
    LEFT JOIN usuarios u ON t.asignado_a = u.id 
    WHERE p.empresa_id = $empresa_id $where_proyecto 
    ORDER BY t.fecha_fin ASC")->fetch_all(MYSQLI_ASSOC);

$stats = $conn->query("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
    SUM(CASE WHEN estado = 'en_proceso' THEN 1 ELSE 0 END) as en_proceso,
    SUM(CASE WHEN estado = 'completada' THEN 1 ELSE 0 END) as completadas
    FROM tareas_proyecto t 
    LEFT JOIN proyectos p ON t.proyecto_id = p.id 
    WHERE p.empresa_id = $empresa_id $where_proyecto")->fetch_assoc();
?>
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Tareas de Proyectos</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
.gantt-bar { height: 30px; background: #3498db; border-radius: 4px; position: relative; }
.kanban-column { min-height: 400px; background: #f8f9fa; padding: 15px; border-radius: 8px; }
.task-card { background: white; padding: 10px; margin-bottom: 10px; border-radius: 4px; cursor: move; border-left: 4px solid #3498db; }
</style>
</head><body class="bg-light">
<?php include '../includes/sidebar.php'; ?>
<div class="content-wrapper"><div class="container-fluid py-4">
<h2><i class="fas fa-tasks"></i> Tareas de Proyectos</h2>

<?php if (isset($success_msg)): ?>
<div class="alert alert-success"><?= htmlspecialchars($success_msg) ?></div>
<?php endif; ?>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-secondary text-white">
            <div class="card-body"><h5>Total Tareas</h5><h2><?= $stats['total'] ?></h2></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body"><h5>Pendientes</h5><h2><?= $stats['pendientes'] ?></h2></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body"><h5>En Proceso</h5><h2><?= $stats['en_proceso'] ?></h2></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body"><h5>Completadas</h5><h2><?= $stats['completadas'] ?></h2></div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Nueva Tarea</h5></div>
    <div class="card-body">
        <form method="POST">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label>Proyecto *</label>
                    <select name="proyecto_id" class="form-control" required>
                        <option value="">Seleccione...</option>
                        <?php foreach ($proyectos as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= $p['id'] == $proyecto_id ? 'selected' : '' ?>><?= htmlspecialchars($p['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label>Nombre Tarea *</label>
                    <input type="text" name="nombre" class="form-control" required>
                </div>
                <div class="col-md-2 mb-3">
                    <label>Fecha Inicio *</label>
                    <input type="date" name="fecha_inicio" class="form-control" required>
                </div>
                <div class="col-md-2 mb-3">
                    <label>Fecha Fin *</label>
                    <input type="date" name="fecha_fin" class="form-control" required>
                </div>
                <div class="col-md-2 mb-3">
                    <label>Prioridad *</label>
                    <select name="prioridad" class="form-control" required>
                        <option value="baja">Baja</option>
                        <option value="media" selected>Media</option>
                        <option value="alta">Alta</option>
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label>Descripción</label>
                    <textarea name="descripcion" class="form-control" rows="2"></textarea>
                </div>
                <div class="col-md-4 mb-3">
                    <label>Asignado a *</label>
                    <select name="asignado_a" class="form-control" required>
                        <option value="">Seleccione usuario...</option>
                        <?php foreach ($usuarios as $u): ?>
                        <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 mb-3">
                    <label>&nbsp;</label>
                    <button type="submit" name="crear_tarea" class="btn btn-primary w-100">
                        <i class="fas fa-plus"></i> Crear
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <a class="nav-link active" data-bs-toggle="tab" href="#lista">Lista</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#kanban">Kanban</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#gantt">Gantt</a>
    </li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade show active" id="lista">
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Proyecto</th>
                                <th>Tarea</th>
                                <th>Asignado</th>
                                <th>Prioridad</th>
                                <th>Fecha Inicio</th>
                                <th>Fecha Fin</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tareas as $t): ?>
                            <tr>
                                <td><?= htmlspecialchars($t['proyecto_nombre']) ?></td>
                                <td><strong><?= htmlspecialchars($t['nombre']) ?></strong><br><small><?= htmlspecialchars($t['descripcion'] ?? '') ?></small></td>
                                <td><?= htmlspecialchars($t['asignado_nombre']) ?></td>
                                <td>
                                    <?php
                                    $badges_prioridad = ['baja' => 'secondary', 'media' => 'warning', 'alta' => 'danger'];
                                    echo '<span class="badge bg-' . $badges_prioridad[$t['prioridad']] . '">' . ucfirst($t['prioridad']) . '</span>';
                                    ?>
                                </td>
                                <td><?= date('d/m/Y', strtotime($t['fecha_inicio'])) ?></td>
                                <td><?= date('d/m/Y', strtotime($t['fecha_fin'])) ?></td>
                                <td>
                                    <?php
                                    $badges_estado = ['pendiente' => 'warning', 'en_proceso' => 'info', 'completada' => 'success', 'cancelada' => 'secondary'];
                                    echo '<span class="badge bg-' . $badges_estado[$t['estado']] . '">' . ucfirst(str_replace('_', ' ', $t['estado'])) . '</span>';
                                    ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <?php if ($t['estado'] === 'pendiente'): ?>
                                        <a href="?cambiar_estado=<?= $t['id'] ?>&estado=en_proceso&proyecto=<?= $proyecto_id ?>" class="btn btn-info" title="Iniciar">
                                            <i class="fas fa-play"></i>
                                        </a>
                                        <?php endif; ?>
                                        <?php if ($t['estado'] === 'en_proceso'): ?>
                                        <a href="?cambiar_estado=<?= $t['id'] ?>&estado=completada&proyecto=<?= $proyecto_id ?>" class="btn btn-success" title="Completar">
                                            <i class="fas fa-check"></i>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="kanban">
        <div class="row">
            <div class="col-md-4">
                <h5>Pendientes</h5>
                <div class="kanban-column">
                    <?php foreach ($tareas as $t): if ($t['estado'] === 'pendiente'): ?>
                    <div class="task-card">
                        <strong><?= htmlspecialchars($t['nombre']) ?></strong><br>
                        <small><?= htmlspecialchars($t['proyecto_nombre']) ?></small><br>
                        <small class="text-muted"><?= date('d/m/Y', strtotime($t['fecha_fin'])) ?></small>
                    </div>
                    <?php endif; endforeach; ?>
                </div>
            </div>
            <div class="col-md-4">
                <h5>En Proceso</h5>
                <div class="kanban-column">
                    <?php foreach ($tareas as $t): if ($t['estado'] === 'en_proceso'): ?>
                    <div class="task-card" style="border-left-color: #f39c12;">
                        <strong><?= htmlspecialchars($t['nombre']) ?></strong><br>
                        <small><?= htmlspecialchars($t['proyecto_nombre']) ?></small><br>
                        <small class="text-muted"><?= date('d/m/Y', strtotime($t['fecha_fin'])) ?></small>
                    </div>
                    <?php endif; endforeach; ?>
                </div>
            </div>
            <div class="col-md-4">
                <h5>Completadas</h5>
                <div class="kanban-column">
                    <?php foreach ($tareas as $t): if ($t['estado'] === 'completada'): ?>
                    <div class="task-card" style="border-left-color: #27ae60;">
                        <strong><?= htmlspecialchars($t['nombre']) ?></strong><br>
                        <small><?= htmlspecialchars($t['proyecto_nombre']) ?></small><br>
                        <small class="text-muted"><?= date('d/m/Y', strtotime($t['fecha_fin'])) ?></small>
                    </div>
                    <?php endif; endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="gantt">
        <div class="card">
            <div class="card-body">
                <p class="text-muted">Vista Gantt - Diagrama simplificado de tareas</p>
                <?php foreach ($tareas as $t): ?>
                <div class="mb-2">
                    <small><strong><?= htmlspecialchars($t['nombre']) ?></strong></small>
                    <div class="gantt-bar" style="width: <?= rand(20, 90) ?>%;">
                        <small class="text-white ps-2"><?= date('d/m', strtotime($t['fecha_inicio'])) ?> - <?= date('d/m', strtotime($t['fecha_fin'])) ?></small>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

</div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php include '../includes/footer.php'; ?>
</body></html>
