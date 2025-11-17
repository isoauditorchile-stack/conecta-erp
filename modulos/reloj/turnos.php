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

// Crear turno
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_turno'])) {
    $nombre = trim($_POST['nombre']);
    $hora_entrada = $_POST['hora_entrada'];
    $hora_salida = $_POST['hora_salida'];
    $dias_semana = isset($_POST['dias_semana']) ? implode(',', $_POST['dias_semana']) : '';
    $color = $_POST['color'];
    
    $stmt = $conn->prepare("INSERT INTO turnos (empresa_id, nombre, hora_entrada, hora_salida, dias_semana, color, activo) VALUES (?, ?, ?, ?, ?, ?, 1)");
    $stmt->bind_param("isssss", $empresa_id, $nombre, $hora_entrada, $hora_salida, $dias_semana, $color);
    $stmt->execute();
    $success_msg = "Turno creado exitosamente";
}

// Asignar turno a empleado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['asignar_turno'])) {
    $empleado_id = intval($_POST['empleado_id']);
    $turno_id = intval($_POST['turno_id']);
    $fecha_inicio = $_POST['fecha_inicio'];
    $fecha_fin = $_POST['fecha_fin'] ?? null;
    
    $stmt = $conn->prepare("INSERT INTO empleados_turnos (empleado_id, turno_id, fecha_inicio, fecha_fin) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", $empleado_id, $turno_id, $fecha_inicio, $fecha_fin);
    $stmt->execute();
    $success_msg = "Turno asignado exitosamente";
}

// Obtener turnos
$turnos = $conn->query("SELECT t.*, 
    (SELECT COUNT(*) FROM empleados_turnos et WHERE et.turno_id = t.id AND (et.fecha_fin IS NULL OR et.fecha_fin >= CURDATE())) as empleados_asignados
    FROM turnos t 
    WHERE t.empresa_id = $empresa_id 
    ORDER BY t.nombre ASC")->fetch_all(MYSQLI_ASSOC);

// Obtener empleados para asignación
$empleados = $conn->query("SELECT id, CONCAT(nombres, ' ', apellidos) as nombre_completo FROM empleados WHERE empresa_id = $empresa_id AND activo = 1 ORDER BY nombres")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Gestión de Turnos</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head><body class="bg-light">
<?php include '../includes/sidebar.php'; ?>
<div class="content-wrapper"><div class="container-fluid py-4">
<h2><i class="fas fa-clock"></i> Gestión de Turnos</h2>

<?php if (isset($success_msg)): ?>
<div class="alert alert-success"><?= htmlspecialchars($success_msg) ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0">Crear Nuevo Turno</h5></div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label>Nombre del Turno *</label>
                        <input type="text" name="nombre" class="form-control" placeholder="Turno Mañana" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Hora Entrada *</label>
                            <input type="time" name="hora_entrada" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Hora Salida *</label>
                            <input type="time" name="hora_salida" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label>Días de la Semana</label>
                        <div class="row">
                            <?php 
                            $dias = ['L' => 'Lunes', 'M' => 'Martes', 'X' => 'Miércoles', 'J' => 'Jueves', 'V' => 'Viernes', 'S' => 'Sábado', 'D' => 'Domingo'];
                            foreach ($dias as $key => $dia): ?>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input type="checkbox" name="dias_semana[]" value="<?= $key ?>" class="form-check-input" id="dia_<?= $key ?>">
                                    <label class="form-check-label" for="dia_<?= $key ?>"><?= $dia ?></label>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label>Color Identificador</label>
                        <input type="color" name="color" class="form-control" value="#3498db">
                    </div>
                    <button type="submit" name="crear_turno" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Crear Turno
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0">Asignar Turno a Empleado</h5></div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label>Empleado *</label>
                        <select name="empleado_id" class="form-control" required>
                            <option value="">Seleccione empleado...</option>
                            <?php foreach ($empleados as $emp): ?>
                            <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['nombre_completo']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Turno *</label>
                        <select name="turno_id" class="form-control" required>
                            <option value="">Seleccione turno...</option>
                            <?php foreach ($turnos as $t): ?>
                            <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['nombre']) ?> (<?= $t['hora_entrada'] ?> - <?= $t['hora_salida'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Fecha Inicio *</label>
                            <input type="date" name="fecha_inicio" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Fecha Fin (opcional)</label>
                            <input type="date" name="fecha_fin" class="form-control">
                        </div>
                    </div>
                    <button type="submit" name="asignar_turno" class="btn btn-success">
                        <i class="fas fa-user-clock"></i> Asignar Turno
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h5 class="mb-0">Turnos Registrados</h5></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Color</th>
                        <th>Nombre</th>
                        <th>Horario</th>
                        <th>Días</th>
                        <th>Empleados Asignados</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($turnos as $t): ?>
                    <tr>
                        <td><div style="width:30px;height:30px;background:<?= htmlspecialchars($t['color']) ?>;border-radius:4px;"></div></td>
                        <td><strong><?= htmlspecialchars($t['nombre']) ?></strong></td>
                        <td><?= $t['hora_entrada'] ?> - <?= $t['hora_salida'] ?></td>
                        <td><?= $t['dias_semana'] ? htmlspecialchars($t['dias_semana']) : 'Todos' ?></td>
                        <td><span class="badge bg-primary"><?= $t['empleados_asignados'] ?> empleados</span></td>
                        <td>
                            <?php if ($t['activo']): ?>
                            <span class="badge bg-success">Activo</span>
                            <?php else: ?>
                            <span class="badge bg-secondary">Inactivo</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($turnos)): ?>
                    <tr><td colspan="6" class="text-center">No hay turnos registrados</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php include '../includes/footer.php'; ?>
</body></html>
