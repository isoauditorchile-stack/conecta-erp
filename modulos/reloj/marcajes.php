<?php
/**
 * MARCAJES DE ASISTENCIA - CONECTA ERP
 * Control de marcajes de entrada/salida de empleados
 * Integración con dispositivos biométricos
 */

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
$tipo_mensaje = '';

// CREAR MARCAJE MANUAL
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_marcaje'])) {
    $empleado_id = intval($_POST['empleado_id']);
    $fecha_hora = $_POST['fecha'] . ' ' . $_POST['hora'];
    $tipo = $_POST['tipo'];
    $dispositivo_id = intval($_POST['dispositivo_id']);
    $observaciones = trim($_POST['observaciones']);

    $stmt = $conn->prepare("INSERT INTO marcajes (empresa_id, empleado_id, fecha_hora, tipo, dispositivo_id, origen, observaciones) VALUES (?, ?, ?, ?, ?, 'manual', ?)");
    $stmt->bind_param("iissis", $empresa_id, $empleado_id, $fecha_hora, $tipo, $dispositivo_id, $observaciones);

    if ($stmt->execute()) {
        $mensaje = "Marcaje registrado exitosamente";
        $tipo_mensaje = "success";
    } else {
        $mensaje = "Error: " . $stmt->error;
        $tipo_mensaje = "danger";
    }
    $stmt->close();
}

// FILTROS
$fecha = isset($_GET['fecha']) ? $_GET['fecha'] : date('Y-m-d');
$empleado_filtro = isset($_GET['empleado']) ? intval($_GET['empleado']) : 0;

$where = "m.empresa_id = $empresa_id AND DATE(m.fecha_hora) = '$fecha'";
if ($empleado_filtro) {
    $where .= " AND m.empleado_id = $empleado_filtro";
}

// OBTENER MARCAJES
$query = "SELECT m.*, e.nombre as empleado_nombre, e.rut,
    d.nombre as dispositivo_nombre
    FROM marcajes m
    LEFT JOIN empleados e ON m.empleado_id = e.id
    LEFT JOIN dispositivos_biometricos d ON m.dispositivo_id = d.id
    WHERE $where
    ORDER BY m.fecha_hora DESC";

$marcajes = $conn->query($query);

// EMPLEADOS
$empleados = $conn->query("SELECT id, rut, nombre FROM empleados WHERE empresa_id = $empresa_id AND activo = 1 ORDER BY nombre");

// DISPOSITIVOS
$dispositivos = $conn->query("SELECT id, nombre FROM dispositivos_biometricos WHERE empresa_id = $empresa_id AND activo = 1");

// ESTADÍSTICAS DEL DÍA
$stats = $conn->query("SELECT
    COUNT(DISTINCT empleado_id) as empleados_presentes,
    COUNT(*) as total_marcajes,
    SUM(CASE WHEN tipo = 'entrada' THEN 1 ELSE 0 END) as entradas,
    SUM(CASE WHEN tipo = 'salida' THEN 1 ELSE 0 END) as salidas
    FROM marcajes WHERE $where")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marcajes de Asistencia - CONECTA ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    <?php include '../includes/sidebar.php'; ?>

    <div class="content-wrapper">
        <div class="container-fluid py-4">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2><i class="fas fa-clock"></i> Marcajes de Asistencia</h2>
                    <p class="text-muted">Control de entrada/salida de empleados</p>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrear">
                    <i class="fas fa-plus"></i> Registrar Marcaje Manual
                </button>
            </div>

            <?php if ($mensaje): ?>
                <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show">
                    <?php echo $mensaje; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Estadísticas -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h6 class="text-muted">Empleados Presentes</h6>
                            <h3><?php echo $stats['empleados_presentes']; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h6 class="text-muted">Total Marcajes</h6>
                            <h3><?php echo $stats['total_marcajes']; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h6 class="text-muted">Entradas</h6>
                            <h3 class="text-success"><?php echo $stats['entradas']; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h6 class="text-muted">Salidas</h6>
                            <h3 class="text-danger"><?php echo $stats['salidas']; ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtros -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label">Fecha</label>
                            <input type="date" name="fecha" class="form-control" value="<?php echo $fecha; ?>">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Empleado</label>
                            <select name="empleado" class="form-select">
                                <option value="0">Todos</option>
                                <?php
                                $empleados->data_seek(0);
                                while ($emp = $empleados->fetch_assoc()):
                                ?>
                                    <option value="<?php echo $emp['id']; ?>" <?php echo $empleado_filtro === $emp['id'] ? 'selected' : ''; ?>>
                                        <?php echo $emp['nombre'] . ' (' . $emp['rut'] . ')'; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-filter"></i> Filtrar
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tabla de Marcajes -->
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Marcajes del <?php echo date('d-m-Y', strtotime($fecha)); ?></h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Empleado</th>
                                    <th>Fecha y Hora</th>
                                    <th>Tipo</th>
                                    <th>Dispositivo</th>
                                    <th>Origen</th>
                                    <th>Observaciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($marcajes->num_rows > 0): ?>
                                    <?php while ($m = $marcajes->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo $m['empleado_nombre']; ?></strong><br>
                                                <small class="text-muted"><?php echo $m['rut']; ?></small>
                                            </td>
                                            <td><?php echo date('d-m-Y H:i:s', strtotime($m['fecha_hora'])); ?></td>
                                            <td>
                                                <?php if ($m['tipo'] === 'entrada'): ?>
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-sign-in-alt"></i> ENTRADA
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">
                                                        <i class="fas fa-sign-out-alt"></i> SALIDA
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $m['dispositivo_nombre'] ?? 'N/A'; ?></td>
                                            <td>
                                                <?php if ($m['origen'] === 'biometrico'): ?>
                                                    <span class="badge bg-info">
                                                        <i class="fas fa-fingerprint"></i> Biométrico
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">
                                                        <i class="fas fa-user"></i> Manual
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($m['observaciones'] ?? ''); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4">
                                            <i class="fas fa-clock fa-3x text-muted mb-3"></i>
                                            <p class="text-muted">No hay marcajes registrados</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Modal Crear Marcaje -->
    <div class="modal fade" id="modalCrear" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title"><i class="fas fa-plus"></i> Registrar Marcaje Manual</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Empleado <span class="text-danger">*</span></label>
                            <select name="empleado_id" class="form-select" required>
                                <option value="">Seleccionar...</option>
                                <?php
                                $empleados->data_seek(0);
                                while ($emp = $empleados->fetch_assoc()):
                                ?>
                                    <option value="<?php echo $emp['id']; ?>">
                                        <?php echo $emp['nombre'] . ' (' . $emp['rut'] . ')'; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Fecha <span class="text-danger">*</span></label>
                                <input type="date" name="fecha" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Hora <span class="text-danger">*</span></label>
                                <input type="time" name="hora" class="form-control" value="<?php echo date('H:i'); ?>" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Tipo <span class="text-danger">*</span></label>
                            <select name="tipo" class="form-select" required>
                                <option value="entrada">Entrada</option>
                                <option value="salida">Salida</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Dispositivo</label>
                            <select name="dispositivo_id" class="form-select">
                                <option value="0">Manual</option>
                                <?php while ($disp = $dispositivos->fetch_assoc()): ?>
                                    <option value="<?php echo $disp['id']; ?>"><?php echo $disp['nombre']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Observaciones</label>
                            <textarea name="observaciones" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="crear_marcaje" class="btn btn-primary">
                            <i class="fas fa-save"></i> Registrar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('collapsed');
        }
    </script>

    <?php include '../includes/footer.php'; ?>

</body>
</html>
