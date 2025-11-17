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

// Crear método de envío
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_metodo'])) {
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $costo_base = floatval($_POST['costo_base']);
    $tiempo_estimado = trim($_POST['tiempo_estimado']);
    $activo = isset($_POST['activo']) ? 1 : 0;
    
    $stmt = $conn->prepare("INSERT INTO metodos_envio (empresa_id, nombre, descripcion, costo_base, tiempo_estimado, activo) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issdsi", $empresa_id, $nombre, $descripcion, $costo_base, $tiempo_estimado, $activo);
    $stmt->execute();
    $success_msg = "Método de envío creado exitosamente";
}

// Obtener métodos de envío
$metodos = $conn->query("SELECT * FROM metodos_envio WHERE empresa_id = $empresa_id ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Métodos de Envío</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head><body class="bg-light">
<?php include '../includes/sidebar.php'; ?>
<div class="content-wrapper"><div class="container-fluid py-4">
<h2><i class="fas fa-shipping-fast"></i> Métodos de Envío</h2>

<?php if (isset($success_msg)): ?>
<div class="alert alert-success"><?= htmlspecialchars($success_msg) ?></div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Nuevo Método de Envío</h5></div>
    <div class="card-body">
        <form method="POST">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label>Nombre *</label>
                    <input type="text" name="nombre" class="form-control" placeholder="Ej: Envío Express" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label>Costo Base ($) *</label>
                    <input type="number" name="costo_base" class="form-control" step="0.01" min="0" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label>Tiempo Estimado</label>
                    <input type="text" name="tiempo_estimado" class="form-control" placeholder="2-3 días hábiles">
                </div>
                <div class="col-md-3 mb-3">
                    <label>&nbsp;</label>
                    <div class="form-check">
                        <input type="checkbox" name="activo" class="form-check-input" id="activo" checked>
                        <label class="form-check-label" for="activo">Activo</label>
                    </div>
                </div>
            </div>
            <div class="mb-3">
                <label>Descripción</label>
                <textarea name="descripcion" class="form-control" rows="2" placeholder="Detalles del método de envío..."></textarea>
            </div>
            <button type="submit" name="crear_metodo" class="btn btn-primary">
                <i class="fas fa-plus"></i> Crear Método
            </button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><h5 class="mb-0">Métodos de Envío Configurados</h5></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Costo Base</th>
                        <th>Tiempo Estimado</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($metodos as $m): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($m['nombre']) ?></strong></td>
                        <td><?= htmlspecialchars($m['descripcion'] ?? '') ?></td>
                        <td><strong> <?= number_format($m['costo_base'], 0, ',', '.') ?></strong></td>
                        <td><?= htmlspecialchars($m['tiempo_estimado'] ?? 'No especificado') ?></td>
                        <td>
                            <?php if ($m['activo']): ?>
                            <span class="badge bg-success">Activo</span>
                            <?php else: ?>
                            <span class="badge bg-secondary">Inactivo</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-primary" onclick="alert('Editar método')">
                                <i class="fas fa-edit"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($metodos)): ?>
                    <tr><td colspan="6" class="text-center">No hay métodos de envío configurados</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header"><h5 class="mb-0">Integraciones de Envío</h5></div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <div class="card border-primary">
                    <div class="card-body text-center">
                        <i class="fas fa-truck fa-3x text-primary mb-3"></i>
                        <h5>Chilexpress</h5>
                        <p class="text-muted">Integración con API de Chilexpress para cotización y seguimiento</p>
                        <button class="btn btn-outline-primary" onclick="alert('Configuración en desarrollo')">Configurar</button>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-success">
                    <div class="card-body text-center">
                        <i class="fas fa-box fa-3x text-success mb-3"></i>
                        <h5>Correos de Chile</h5>
                        <p class="text-muted">Integración con Correos de Chile para envíos nacionales</p>
                        <button class="btn btn-outline-success" onclick="alert('Configuración en desarrollo')">Configurar</button>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-warning">
                    <div class="card-body text-center">
                        <i class="fas fa-shipping-fast fa-3x text-warning mb-3"></i>
                        <h5>Starken</h5>
                        <p class="text-muted">Integración con Starken para envíos express</p>
                        <button class="btn btn-outline-warning" onclick="alert('Configuración en desarrollo')">Configurar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php include '../includes/footer.php'; ?>
</body></html>
