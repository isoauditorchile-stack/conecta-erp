<?php
/**
 * GESTIÓN DE SUCURSALES - CONECTA ERP
 * CRUD completo de sucursales con búsqueda, paginación y exportación
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
// CREAR SUCURSAL
// ==================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_sucursal'])) {
    $codigo = trim($_POST['codigo']);
    $nombre = trim($_POST['nombre']);
    $direccion = trim($_POST['direccion']);
    $ciudad = trim($_POST['ciudad']);
    $region = trim($_POST['region']);
    $telefono = trim($_POST['telefono']);
    $email = trim($_POST['email']);
    $responsable = trim($_POST['responsable']);
    $es_principal = isset($_POST['es_principal']) ? 1 : 0;
    $activo = isset($_POST['activo']) ? 1 : 0;

    // Si es principal, desmarcar otras
    if ($es_principal) {
        $conn->query("UPDATE sucursales SET es_principal = 0 WHERE empresa_id = $empresa_id");
    }

    $stmt = $conn->prepare("INSERT INTO sucursales (empresa_id, codigo, nombre, direccion, ciudad, region, telefono, email, responsable, es_principal, activo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssssssii", $empresa_id, $codigo, $nombre, $direccion, $ciudad, $region, $telefono, $email, $responsable, $es_principal, $activo);

    if ($stmt->execute()) {
        $mensaje = "Sucursal creada exitosamente";
        $tipo_mensaje = "success";
    } else {
        $mensaje = "Error al crear sucursal: " . $stmt->error;
        $tipo_mensaje = "danger";
    }
    $stmt->close();
}

// ==================================================================
// EDITAR SUCURSAL
// ==================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_sucursal'])) {
    $id = intval($_POST['sucursal_id']);
    $codigo = trim($_POST['codigo']);
    $nombre = trim($_POST['nombre']);
    $direccion = trim($_POST['direccion']);
    $ciudad = trim($_POST['ciudad']);
    $region = trim($_POST['region']);
    $telefono = trim($_POST['telefono']);
    $email = trim($_POST['email']);
    $responsable = trim($_POST['responsable']);
    $es_principal = isset($_POST['es_principal']) ? 1 : 0;
    $activo = isset($_POST['activo']) ? 1 : 0;

    if ($es_principal) {
        $conn->query("UPDATE sucursales SET es_principal = 0 WHERE empresa_id = $empresa_id AND id != $id");
    }

    $stmt = $conn->prepare("UPDATE sucursales SET codigo = ?, nombre = ?, direccion = ?, ciudad = ?, region = ?, telefono = ?, email = ?, responsable = ?, es_principal = ?, activo = ? WHERE id = ? AND empresa_id = ?");
    $stmt->bind_param("ssssssssiii", $codigo, $nombre, $direccion, $ciudad, $region, $telefono, $email, $responsable, $es_principal, $activo, $id, $empresa_id);

    if ($stmt->execute()) {
        $mensaje = "Sucursal actualizada exitosamente";
        $tipo_mensaje = "success";
    } else {
        $mensaje = "Error al actualizar sucursal: " . $stmt->error;
        $tipo_mensaje = "danger";
    }
    $stmt->close();
}

// ==================================================================
// ELIMINAR SUCURSAL
// ==================================================================
if (isset($_GET['eliminar'])) {
    $id = intval($_GET['eliminar']);

    // Verificar si no es la principal
    $stmt = $conn->prepare("SELECT es_principal FROM sucursales WHERE id = ? AND empresa_id = ?");
    $stmt->bind_param("ii", $id, $empresa_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    if ($result['es_principal']) {
        $mensaje = "No se puede eliminar la sucursal principal";
        $tipo_mensaje = "warning";
    } else {
        $stmt = $conn->prepare("DELETE FROM sucursales WHERE id = ? AND empresa_id = ?");
        $stmt->bind_param("ii", $id, $empresa_id);

        if ($stmt->execute()) {
            $mensaje = "Sucursal eliminada exitosamente";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al eliminar sucursal";
            $tipo_mensaje = "danger";
        }
    }
    $stmt->close();
}

// ==================================================================
// BÚSQUEDA Y PAGINACIÓN
// ==================================================================
$buscar = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

$where = "empresa_id = $empresa_id";
if ($buscar) {
    $buscar_safe = $conn->real_escape_string($buscar);
    $where .= " AND (codigo LIKE '%$buscar_safe%' OR nombre LIKE '%$buscar_safe%' OR ciudad LIKE '%$buscar_safe%')";
}

// Contar total
$total_query = "SELECT COUNT(*) as total FROM sucursales WHERE $where";
$total = $conn->query($total_query)->fetch_assoc()['total'];
$total_pages = ceil($total / $per_page);

// Obtener sucursales
$query = "SELECT * FROM sucursales WHERE $where ORDER BY es_principal DESC, codigo ASC LIMIT $per_page OFFSET $offset";
$sucursales = $conn->query($query);

// Estadísticas
$stats = $conn->query("SELECT
    COUNT(*) as total_sucursales,
    SUM(es_principal) as principal_count,
    SUM(activo) as activas
    FROM sucursales WHERE empresa_id = $empresa_id")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Sucursales - CONECTA ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .stat-card {
            border-left: 4px solid;
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .badge-principal {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
    </style>
</head>
<body class="bg-light">

    <?php include '../includes/sidebar.php'; ?>

    <div class="content-wrapper">
        <div class="container-fluid py-4">

            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2><i class="fas fa-store"></i> Gestión de Sucursales</h2>
                    <p class="text-muted">Administración de sucursales y puntos de venta</p>
                </div>
                <div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrear">
                        <i class="fas fa-plus"></i> Nueva Sucursal
                    </button>
                </div>
            </div>

            <!-- Mensajes -->
            <?php if ($mensaje): ?>
                <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show">
                    <?php echo $mensaje; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Estadísticas -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card stat-card shadow-sm" style="border-left-color: #667eea;">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-1">Total Sucursales</h6>
                                    <h3 class="mb-0"><?php echo $stats['total_sucursales']; ?></h3>
                                </div>
                                <div class="text-primary" style="font-size: 3rem; opacity: 0.3;">
                                    <i class="fas fa-store"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card shadow-sm" style="border-left-color: #48bb78;">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-1">Sucursales Activas</h6>
                                    <h3 class="mb-0"><?php echo $stats['activas']; ?></h3>
                                </div>
                                <div class="text-success" style="font-size: 3rem; opacity: 0.3;">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card shadow-sm" style="border-left-color: #f59e0b;">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-1">Sucursal Principal</h6>
                                    <h3 class="mb-0"><?php echo $stats['principal_count']; ?></h3>
                                </div>
                                <div class="text-warning" style="font-size: 3rem; opacity: 0.3;">
                                    <i class="fas fa-crown"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Búsqueda -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-10">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" name="buscar" class="form-control"
                                       placeholder="Buscar por código, nombre o ciudad..."
                                       value="<?php echo htmlspecialchars($buscar); ?>">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search"></i> Buscar
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tabla de Sucursales -->
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Listado de Sucursales (<?php echo $total; ?>)</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Código</th>
                                    <th>Nombre</th>
                                    <th>Dirección</th>
                                    <th>Ciudad/Región</th>
                                    <th>Contacto</th>
                                    <th>Responsable</th>
                                    <th>Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($sucursales->num_rows > 0): ?>
                                    <?php while ($suc = $sucursales->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($suc['codigo']); ?></strong>
                                                <?php if ($suc['es_principal']): ?>
                                                    <span class="badge badge-principal ms-1">
                                                        <i class="fas fa-crown"></i> Principal
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($suc['nombre']); ?></td>
                                            <td><?php echo htmlspecialchars($suc['direccion']); ?></td>
                                            <td>
                                                <?php echo htmlspecialchars($suc['ciudad']); ?><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($suc['region']); ?></small>
                                            </td>
                                            <td>
                                                <?php if ($suc['telefono']): ?>
                                                    <i class="fas fa-phone text-primary"></i> <?php echo htmlspecialchars($suc['telefono']); ?><br>
                                                <?php endif; ?>
                                                <?php if ($suc['email']): ?>
                                                    <i class="fas fa-envelope text-primary"></i> <?php echo htmlspecialchars($suc['email']); ?>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($suc['responsable'] ?? 'No asignado'); ?></td>
                                            <td>
                                                <?php if ($suc['activo']): ?>
                                                    <span class="badge bg-success">Activa</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Inactiva</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <button class="btn btn-sm btn-outline-primary" onclick='editarSucursal(<?php echo json_encode($suc); ?>)'>
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php if (!$suc['es_principal']): ?>
                                                    <a href="?eliminar=<?php echo $suc['id']; ?>" class="btn btn-sm btn-outline-danger"
                                                       onclick="return confirm('¿Eliminar esta sucursal?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                            <p class="text-muted">No hay sucursales registradas</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php if ($total_pages > 1): ?>
                    <div class="card-footer bg-white">
                        <nav>
                            <ul class="pagination mb-0">
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&buscar=<?php echo urlencode($buscar); ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <!-- Modal Crear Sucursal -->
    <div class="modal fade" id="modalCrear" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title"><i class="fas fa-plus"></i> Nueva Sucursal</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Código <span class="text-danger">*</span></label>
                                <input type="text" name="codigo" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Nombre <span class="text-danger">*</span></label>
                                <input type="text" name="nombre" class="form-control" required>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label fw-bold">Dirección <span class="text-danger">*</span></label>
                                <input type="text" name="direccion" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Ciudad <span class="text-danger">*</span></label>
                                <input type="text" name="ciudad" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Región <span class="text-danger">*</span></label>
                                <select name="region" class="form-select" required>
                                    <option value="">Seleccionar...</option>
                                    <option>Región Metropolitana</option>
                                    <option>Región de Valparaíso</option>
                                    <option>Región del Biobío</option>
                                    <option>Región de La Araucanía</option>
                                    <option>Región de Los Lagos</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Teléfono</label>
                                <input type="text" name="telefono" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Email</label>
                                <input type="email" name="email" class="form-control">
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label fw-bold">Responsable</label>
                                <input type="text" name="responsable" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-check">
                                    <input type="checkbox" name="es_principal" class="form-check-input" id="esPrincipalCrear">
                                    <label class="form-check-label" for="esPrincipalCrear">
                                        <i class="fas fa-crown text-warning"></i> Es Sucursal Principal
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-check">
                                    <input type="checkbox" name="activo" class="form-check-input" id="activoCrear" checked>
                                    <label class="form-check-label" for="activoCrear">
                                        Activa
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="crear_sucursal" class="btn btn-primary">
                            <i class="fas fa-save"></i> Crear Sucursal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar Sucursal -->
    <div class="modal fade" id="modalEditar" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" id="formEditar">
                    <input type="hidden" name="sucursal_id" id="edit_id">
                    <div class="modal-header bg-warning text-dark">
                        <h5 class="modal-title"><i class="fas fa-edit"></i> Editar Sucursal</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Código <span class="text-danger">*</span></label>
                                <input type="text" name="codigo" id="edit_codigo" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Nombre <span class="text-danger">*</span></label>
                                <input type="text" name="nombre" id="edit_nombre" class="form-control" required>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label fw-bold">Dirección <span class="text-danger">*</span></label>
                                <input type="text" name="direccion" id="edit_direccion" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Ciudad <span class="text-danger">*</span></label>
                                <input type="text" name="ciudad" id="edit_ciudad" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Región <span class="text-danger">*</span></label>
                                <input type="text" name="region" id="edit_region" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Teléfono</label>
                                <input type="text" name="telefono" id="edit_telefono" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Email</label>
                                <input type="email" name="email" id="edit_email" class="form-control">
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label fw-bold">Responsable</label>
                                <input type="text" name="responsable" id="edit_responsable" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-check">
                                    <input type="checkbox" name="es_principal" class="form-check-input" id="edit_es_principal">
                                    <label class="form-check-label" for="edit_es_principal">
                                        <i class="fas fa-crown text-warning"></i> Es Sucursal Principal
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-check">
                                    <input type="checkbox" name="activo" class="form-check-input" id="edit_activo">
                                    <label class="form-check-label" for="edit_activo">
                                        Activa
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="editar_sucursal" class="btn btn-warning">
                            <i class="fas fa-save"></i> Actualizar Sucursal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editarSucursal(data) {
            document.getElementById('edit_id').value = data.id;
            document.getElementById('edit_codigo').value = data.codigo;
            document.getElementById('edit_nombre').value = data.nombre;
            document.getElementById('edit_direccion').value = data.direccion;
            document.getElementById('edit_ciudad').value = data.ciudad;
            document.getElementById('edit_region').value = data.region;
            document.getElementById('edit_telefono').value = data.telefono || '';
            document.getElementById('edit_email').value = data.email || '';
            document.getElementById('edit_responsable').value = data.responsable || '';
            document.getElementById('edit_es_principal').checked = data.es_principal == 1;
            document.getElementById('edit_activo').checked = data.activo == 1;

            new bootstrap.Modal(document.getElementById('modalEditar')).show();
        }

        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('collapsed');
        }
    </script>

    <!-- Footer Profesional -->
    <?php include '../includes/footer.php'; ?>

</body>
</html>
