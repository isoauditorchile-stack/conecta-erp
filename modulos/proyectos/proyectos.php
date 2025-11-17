<?php
/**
 * GESTIÓN DE PROYECTOS - CONECTA ERP
 * Sistema completo de gestión de proyectos con metodología PM BOK/Ágil
 * Incluye: proyectos, tareas, hitos, recursos, costos, rentabilidad
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

// ==================================================================
// CREAR PROYECTO
// ==================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_proyecto'])) {
    $codigo = trim($_POST['codigo']);
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $cliente_id = intval($_POST['cliente_id']);
    $responsable_id = intval($_POST['responsable_id']);
    $fecha_inicio = $_POST['fecha_inicio'];
    $fecha_fin_estimada = $_POST['fecha_fin_estimada'];
    $presupuesto = floatval($_POST['presupuesto']);
    $metodologia = $_POST['metodologia'];
    $prioridad = $_POST['prioridad'];

    $stmt = $conn->prepare("INSERT INTO proyectos (empresa_id, codigo, nombre, descripcion, cliente_id, responsable_id, fecha_inicio, fecha_fin_estimada, presupuesto, metodologia, prioridad, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'planificacion')");
    $stmt->bind_param("isssississs", $empresa_id, $codigo, $nombre, $descripcion, $cliente_id, $responsable_id, $fecha_inicio, $fecha_fin_estimada, $presupuesto, $metodologia, $prioridad);

    if ($stmt->execute()) {
        $mensaje = "Proyecto creado exitosamente";
        $tipo_mensaje = "success";
    } else {
        $mensaje = "Error: " . $stmt->error;
        $tipo_mensaje = "danger";
    }
    $stmt->close();
}

// ==================================================================
// CAMBIAR ESTADO
// ==================================================================
if (isset($_GET['cambiar_estado']) && isset($_GET['estado'])) {
    $proyecto_id = intval($_GET['cambiar_estado']);
    $nuevo_estado = $_GET['estado'];

    $conn->query("UPDATE proyectos SET estado = '$nuevo_estado' WHERE id = $proyecto_id AND empresa_id = $empresa_id");
    $mensaje = "Estado actualizado a: " . strtoupper($nuevo_estado);
    $tipo_mensaje = "info";
}

// ==================================================================
// LISTADO DE PROYECTOS
// ==================================================================
$estado_filtro = isset($_GET['estado']) ? $_GET['estado'] : '';
$buscar = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';

$where = "p.empresa_id = $empresa_id";
if ($estado_filtro) {
    $where .= " AND p.estado = '" . $conn->real_escape_string($estado_filtro) . "'";
}
if ($buscar) {
    $buscar_safe = $conn->real_escape_string($buscar);
    $where .= " AND (p.codigo LIKE '%$buscar_safe%' OR p.nombre LIKE '%$buscar_safe%')";
}

$query = "SELECT p.*, c.razon_social as cliente_nombre,
    u.nombre as responsable_nombre,
    (SELECT COUNT(*) FROM tareas_proyecto WHERE proyecto_id = p.id) as total_tareas,
    (SELECT COUNT(*) FROM tareas_proyecto WHERE proyecto_id = p.id AND estado = 'completada') as tareas_completadas,
    (SELECT SUM(costo) FROM tareas_proyecto WHERE proyecto_id = p.id) as costo_real
    FROM proyectos p
    LEFT JOIN clientes c ON p.cliente_id = c.id
    LEFT JOIN usuarios u ON p.responsable_id = u.id
    WHERE $where
    ORDER BY p.fecha_inicio DESC";

$proyectos = $conn->query($query);

// Clientes
$clientes = $conn->query("SELECT id, razon_social FROM clientes WHERE empresa_id = $empresa_id AND activo = 1 ORDER BY razon_social");

// Responsables
$responsables = $conn->query("SELECT id, nombre FROM usuarios WHERE empresa_id = $empresa_id AND activo = 1 ORDER BY nombre");

// Estadísticas
$stats = $conn->query("SELECT
    COUNT(*) as total_proyectos,
    SUM(CASE WHEN estado = 'en_ejecucion' THEN 1 ELSE 0 END) as en_ejecucion,
    SUM(CASE WHEN estado = 'completado' THEN 1 ELSE 0 END) as completados,
    SUM(presupuesto) as presupuesto_total,
    (SELECT SUM(costo) FROM tareas_proyecto tp INNER JOIN proyectos pr ON tp.proyecto_id = pr.id WHERE pr.empresa_id = $empresa_id) as costo_total
    FROM proyectos WHERE empresa_id = $empresa_id")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Proyectos - CONECTA ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .project-card {
            border-left: 4px solid;
            transition: transform 0.2s;
        }
        .project-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        }
        .progress-circle {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 0.9rem;
        }
        .badge-planificacion { background: #6c757d; }
        .badge-en_ejecucion { background: #0d6efd; }
        .badge-pausado { background: #ffc107; color: #000; }
        .badge-completado { background: #198754; }
        .badge-cancelado { background: #dc3545; }
        .badge-alta { background: #dc3545; }
        .badge-media { background: #ffc107; color: #000; }
        .badge-baja { background: #0dcaf0; }
    </style>
</head>
<body class="bg-light">

    <?php include '../includes/sidebar.php'; ?>

    <div class="content-wrapper">
        <div class="container-fluid py-4">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2><i class="fas fa-project-diagram"></i> Gestión de Proyectos</h2>
                    <p class="text-muted">Sistema de gestión de proyectos PMBOK/Ágil</p>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrear">
                    <i class="fas fa-plus"></i> Nuevo Proyecto
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
                            <h6 class="text-muted">Total Proyectos</h6>
                            <h3><?php echo $stats['total_proyectos']; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h6 class="text-muted">En Ejecución</h6>
                            <h3 class="text-primary"><?php echo $stats['en_ejecucion']; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h6 class="text-muted">Presupuesto Total</h6>
                            <h3 class="text-success">$<?php echo number_format($stats['presupuesto_total'] ?? 0, 0, ',', '.'); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h6 class="text-muted">Costo Real</h6>
                            <h3 class="text-danger">$<?php echo number_format($stats['costo_total'] ?? 0, 0, ',', '.'); ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtros -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-5">
                            <input type="text" name="buscar" class="form-control" placeholder="Buscar proyectos..." value="<?php echo htmlspecialchars($buscar); ?>">
                        </div>
                        <div class="col-md-5">
                            <select name="estado" class="form-select">
                                <option value="">Todos los estados</option>
                                <option value="planificacion" <?php echo $estado_filtro === 'planificacion' ? 'selected' : ''; ?>>Planificación</option>
                                <option value="en_ejecucion" <?php echo $estado_filtro === 'en_ejecucion' ? 'selected' : ''; ?>>En Ejecución</option>
                                <option value="pausado" <?php echo $estado_filtro === 'pausado' ? 'selected' : ''; ?>>Pausado</option>
                                <option value="completado" <?php echo $estado_filtro === 'completado' ? 'selected' : ''; ?>>Completado</option>
                                <option value="cancelado" <?php echo $estado_filtro === 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search"></i> Buscar
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Lista de Proyectos -->
            <div class="row">
                <?php if ($proyectos->num_rows > 0): ?>
                    <?php while ($proy = $proyectos->fetch_assoc()):
                        $progreso = $proy['total_tareas'] > 0 ? ($proy['tareas_completadas'] / $proy['total_tareas']) * 100 : 0;
                        $color_borde = '#6c757d';
                        if ($proy['estado'] === 'en_ejecucion') $color_borde = '#0d6efd';
                        if ($proy['estado'] === 'completado') $color_borde = '#198754';
                        if ($proy['estado'] === 'cancelado') $color_borde = '#dc3545';
                    ?>
                        <div class="col-md-6 mb-4">
                            <div class="card project-card shadow-sm h-100" style="border-left-color: <?php echo $color_borde; ?>;">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div>
                                            <h5 class="mb-1"><?php echo htmlspecialchars($proy['nombre']); ?></h5>
                                            <small class="text-muted">
                                                <i class="fas fa-code"></i> <?php echo $proy['codigo']; ?> •
                                                <i class="fas fa-user"></i> <?php echo $proy['cliente_nombre']; ?>
                                            </small>
                                        </div>
                                        <div class="progress-circle bg-light">
                                            <?php echo round($progreso); ?>%
                                        </div>
                                    </div>

                                    <p class="text-muted small mb-3"><?php echo htmlspecialchars($proy['descripcion']); ?></p>

                                    <div class="row mb-3">
                                        <div class="col-6">
                                            <small class="text-muted">Inicio</small><br>
                                            <strong><?php echo date('d-m-Y', strtotime($proy['fecha_inicio'])); ?></strong>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted">Fin Estimado</small><br>
                                            <strong><?php echo date('d-m-Y', strtotime($proy['fecha_fin_estimada'])); ?></strong>
                                        </div>
                                    </div>

                                    <div class="progress mb-3" style="height: 8px;">
                                        <div class="progress-bar bg-success" style="width: <?php echo $progreso; ?>%"></div>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div>
                                            <small class="text-muted">Tareas: <?php echo $proy['tareas_completadas']; ?>/<?php echo $proy['total_tareas']; ?></small>
                                        </div>
                                        <div>
                                            <span class="badge badge-<?php echo $proy['prioridad']; ?>">
                                                <?php echo strtoupper($proy['prioridad']); ?>
                                            </span>
                                            <span class="badge badge-<?php echo $proy['estado']; ?>">
                                                <?php echo strtoupper(str_replace('_', ' ', $proy['estado'])); ?>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <small class="text-muted">Presupuesto: </small>
                                            <strong class="text-success">$<?php echo number_format($proy['presupuesto'], 0, ',', '.'); ?></strong>
                                            <small class="text-muted"> / </small>
                                            <strong class="text-danger">$<?php echo number_format($proy['costo_real'] ?? 0, 0, ',', '.'); ?></strong>
                                        </div>
                                        <div>
                                            <a href="tareas.php?proyecto_id=<?php echo $proy['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-tasks"></i> Tareas
                                            </a>
                                            <?php if ($proy['estado'] !== 'completado' && $proy['estado'] !== 'cancelado'): ?>
                                                <a href="?cambiar_estado=<?php echo $proy['id']; ?>&estado=en_ejecucion" class="btn btn-sm btn-outline-success">
                                                    <i class="fas fa-play"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="card shadow-sm">
                            <div class="card-body text-center py-5">
                                <i class="fas fa-project-diagram fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No hay proyectos registrados</p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <!-- Modal Crear Proyecto -->
    <div class="modal fade" id="modalCrear" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title"><i class="fas fa-plus"></i> Nuevo Proyecto</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Código <span class="text-danger">*</span></label>
                                <input type="text" name="codigo" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Cliente <span class="text-danger">*</span></label>
                                <select name="cliente_id" class="form-select" required>
                                    <option value="">Seleccionar...</option>
                                    <?php
                                    $clientes->data_seek(0);
                                    while ($cli = $clientes->fetch_assoc()):
                                    ?>
                                        <option value="<?php echo $cli['id']; ?>"><?php echo $cli['razon_social']; ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label fw-bold">Nombre del Proyecto <span class="text-danger">*</span></label>
                                <input type="text" name="nombre" class="form-control" required>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label fw-bold">Descripción</label>
                                <textarea name="descripcion" class="form-control" rows="3"></textarea>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Responsable <span class="text-danger">*</span></label>
                                <select name="responsable_id" class="form-select" required>
                                    <option value="">Seleccionar...</option>
                                    <?php
                                    $responsables->data_seek(0);
                                    while ($resp = $responsables->fetch_assoc()):
                                    ?>
                                        <option value="<?php echo $resp['id']; ?>"><?php echo $resp['nombre']; ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Presupuesto <span class="text-danger">*</span></label>
                                <input type="number" name="presupuesto" class="form-control" step="0.01" min="0" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Fecha Inicio <span class="text-danger">*</span></label>
                                <input type="date" name="fecha_inicio" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Fecha Fin Estimada <span class="text-danger">*</span></label>
                                <input type="date" name="fecha_fin_estimada" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Metodología</label>
                                <select name="metodologia" class="form-select">
                                    <option value="pmbok">PMBOK</option>
                                    <option value="agil">Ágil / Scrum</option>
                                    <option value="kanban">Kanban</option>
                                    <option value="tradicional">Tradicional</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Prioridad</label>
                                <select name="prioridad" class="form-select">
                                    <option value="baja">Baja</option>
                                    <option value="media" selected>Media</option>
                                    <option value="alta">Alta</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="crear_proyecto" class="btn btn-primary">
                            <i class="fas fa-save"></i> Crear Proyecto
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
