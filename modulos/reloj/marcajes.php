<?php
/**
 * RELOJ CONTROL - MARCAJES
 * Control de asistencia con marcaje de entrada/salida
 * Sistema Multiempresa - SQL Completo
 */
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['empresa_id'])) {
    header('Location: /login.php');
    exit;
}

$empresa_id = (int)$_SESSION['empresa_id'];
$usuario_id = (int)$_SESSION['user_id'];

// Obtener conexión a base de datos
if (!isset($conn)) {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Error de conexión: " . $conn->connect_error);
    }
    $conn->set_charset('utf8mb4');
}

$success = '';
$error = '';

// Procesar marcaje
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['marcar'])) {
    try {
        $empleado_id = (int)$_POST['empleado_id'];
        $tipo = $_POST['tipo']; // entrada, salida, entrada_almuerzo, salida_almuerzo
        $observaciones = trim($_POST['observaciones'] ?? '');

        // Validar que existe el empleado
        $stmt = $conn->prepare("SELECT id FROM empleados WHERE id = ? AND empresa_id = ?");
        $stmt->bind_param('ii', $empleado_id, $empresa_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            throw new Exception("Empleado no encontrado");
        }

        // Insertar marcaje
        $stmt = $conn->prepare("
            INSERT INTO marcajes (empresa_id, empleado_id, fecha_hora, tipo, observaciones)
            VALUES (?, ?, NOW(), ?, ?)
        ");
        $stmt->bind_param('iiss', $empresa_id, $empleado_id, $tipo, $observaciones);
        $stmt->execute();

        $success = "Marcaje registrado exitosamente";
        logActivity($usuario_id, 'marcaje', "Registró marcaje tipo $tipo para empleado $empleado_id", 'reloj');

    } catch (Exception $e) {
        $error = $e->getMessage();
        error_log("Error en marcajes.php: " . $error);
    }
}

// Obtener empleados activos
$stmt = $conn->prepare("
    SELECT id, rut, nombre, apellido_paterno, cargo
    FROM empleados
    WHERE empresa_id = ? AND estado = 'activo'
    ORDER BY nombre, apellido_paterno
");
$stmt->bind_param('i', $empresa_id);
$stmt->execute();
$empleados = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Obtener marcajes del día actual
$fecha_hoy = date('Y-m-d');
$stmt = $conn->prepare("
    SELECT m.*, e.nombre, e.apellido_paterno, e.cargo, e.rut
    FROM marcajes m
    INNER JOIN empleados e ON m.empleado_id = e.id
    WHERE m.empresa_id = ? AND DATE(m.fecha_hora) = ?
    ORDER BY m.fecha_hora DESC
");
$stmt->bind_param('is', $empresa_id, $fecha_hoy);
$stmt->execute();
$marcajes_hoy = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Estadísticas del día
$stmt = $conn->prepare("
    SELECT
        COUNT(DISTINCT empleado_id) as empleados_presentes,
        COUNT(CASE WHEN tipo = 'entrada' THEN 1 END) as entradas,
        COUNT(CASE WHEN tipo = 'salida' THEN 1 END) as salidas
    FROM marcajes
    WHERE empresa_id = ? AND DATE(fecha_hora) = ?
");
$stmt->bind_param('is', $empresa_id, $fecha_hoy);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

// Total de empleados activos
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM empleados WHERE empresa_id = ? AND estado = 'activo'");
$stmt->bind_param('i', $empresa_id);
$stmt->execute();
$total_empleados = $stmt->get_result()->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marcajes | CONECTA ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .gradient-header {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            padding: 2rem;
            margin-bottom: 2rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(79, 172, 254, 0.3);
        }
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: all 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        .marcaje-entrada { color: #28a745; }
        .marcaje-salida { color: #dc3545; }
        .marcaje-entrada_almuerzo { color: #ffc107; }
        .marcaje-salida_almuerzo { color: #17a2b8; }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid py-4">
        <div class="gradient-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1><i class="fas fa-clock"></i> Control de Marcajes</h1>
                    <p class="mb-0 opacity-75">Registro de asistencia y control de horarios</p>
                    <small class="d-block mt-2">Fecha: <?= date('d/m/Y') ?> - <?= date('H:i:s') ?></small>
                </div>
                <div>
                    <button class="btn btn-light btn-lg" data-bs-toggle="modal" data-bs-target="#marcarModal">
                        <i class="fas fa-fingerprint"></i> Registrar Marcaje
                    </button>
                </div>
            </div>
        </div>

        <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible">
            <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Estadísticas -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card border-start border-5 border-primary">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Empleados Totales</h6>
                            <h2 class="mb-0 text-primary"><?= number_format($total_empleados) ?></h2>
                        </div>
                        <i class="fas fa-users fa-3x text-primary opacity-25"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card border-start border-5 border-success">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Presentes Hoy</h6>
                            <h2 class="mb-0 text-success"><?= number_format($stats['empleados_presentes']) ?></h2>
                        </div>
                        <i class="fas fa-user-check fa-3x text-success opacity-25"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card border-start border-5 border-info">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Entradas</h6>
                            <h2 class="mb-0 text-info"><?= number_format($stats['entradas']) ?></h2>
                        </div>
                        <i class="fas fa-sign-in-alt fa-3x text-info opacity-25"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card border-start border-5 border-warning">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Salidas</h6>
                            <h2 class="mb-0 text-warning"><?= number_format($stats['salidas']) ?></h2>
                        </div>
                        <i class="fas fa-sign-out-alt fa-3x text-warning opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Marcajes del día -->
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-list"></i> Marcajes de Hoy - <?= date('d/m/Y') ?></h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Hora</th>
                                <th>RUT</th>
                                <th>Empleado</th>
                                <th>Cargo</th>
                                <th>Tipo Marcaje</th>
                                <th>Observaciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($marcajes_hoy as $marcaje): ?>
                            <tr>
                                <td><strong><?= date('H:i:s', strtotime($marcaje['fecha_hora'])) ?></strong></td>
                                <td><?= htmlspecialchars($marcaje['rut']) ?></td>
                                <td><?= htmlspecialchars($marcaje['nombre'] . ' ' . $marcaje['apellido_paterno']) ?></td>
                                <td><small class="text-muted"><?= htmlspecialchars($marcaje['cargo']) ?></small></td>
                                <td>
                                    <?php
                                    $tipo_texto = [
                                        'entrada' => 'Entrada',
                                        'salida' => 'Salida',
                                        'entrada_almuerzo' => 'Entrada Almuerzo',
                                        'salida_almuerzo' => 'Salida Almuerzo'
                                    ];
                                    $tipo_icono = [
                                        'entrada' => 'fa-sign-in-alt',
                                        'salida' => 'fa-sign-out-alt',
                                        'entrada_almuerzo' => 'fa-utensils',
                                        'salida_almuerzo' => 'fa-utensils'
                                    ];
                                    ?>
                                    <span class="marcaje-<?= $marcaje['tipo'] ?>">
                                        <i class="fas <?= $tipo_icono[$marcaje['tipo']] ?>"></i>
                                        <?= $tipo_texto[$marcaje['tipo']] ?>
                                    </span>
                                </td>
                                <td><small><?= htmlspecialchars($marcaje['observaciones']) ?></small></td>
                            </tr>
                            <?php endforeach; ?>

                            <?php if (empty($marcajes_hoy)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                    No hay marcajes registrados hoy
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Marcar -->
    <div class="modal fade" id="marcarModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-fingerprint"></i> Registrar Marcaje</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="fas fa-clock"></i> Hora actual: <strong id="horaActual"><?= date('H:i:s') ?></strong>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Empleado *</label>
                            <select name="empleado_id" class="form-select" required autofocus>
                                <option value="">Seleccione empleado...</option>
                                <?php foreach ($empleados as $emp): ?>
                                <option value="<?= $emp['id'] ?>">
                                    <?= htmlspecialchars($emp['rut']) ?> - <?= htmlspecialchars($emp['nombre'] . ' ' . $emp['apellido_paterno']) ?>
                                    <?php if ($emp['cargo']): ?>(<?= htmlspecialchars($emp['cargo']) ?>)<?php endif; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Tipo de Marcaje *</label>
                            <select name="tipo" class="form-select" required>
                                <option value="entrada">Entrada</option>
                                <option value="salida">Salida</option>
                                <option value="salida_almuerzo">Salida a Almuerzo</option>
                                <option value="entrada_almuerzo">Regreso de Almuerzo</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Observaciones</label>
                            <textarea name="observaciones" class="form-control" rows="2"
                                      placeholder="Opcional"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="marcar" class="btn btn-primary btn-lg">
                            <i class="fas fa-check-circle"></i> Registrar Marcaje
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Actualizar hora actual cada segundo
    setInterval(() => {
        const now = new Date();
        const horaStr = now.toLocaleTimeString('es-CL');
        document.getElementById('horaActual').textContent = horaStr;
    }, 1000);
    </script>
</body>
</html>
