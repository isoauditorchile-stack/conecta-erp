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

// Crear moneda
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_moneda'])) {
    $codigo = trim(strtoupper($_POST['codigo']));
    $nombre = trim($_POST['nombre']);
    $simbolo = trim($_POST['simbolo']);
    $activo = isset($_POST['activo']) ? 1 : 0;
    
    $stmt = $conn->prepare("INSERT INTO monedas (codigo, nombre, simbolo, activo) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssi", $codigo, $nombre, $simbolo, $activo);
    $stmt->execute();
    $success_msg = "Moneda creada exitosamente";
}

// Obtener monedas
$monedas = $conn->query("SELECT * FROM monedas ORDER BY codigo")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Gestión de Monedas</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head><body class="bg-light">
<?php include '../includes/sidebar.php'; ?>
<div class="content-wrapper"><div class="container-fluid py-4">
<h2><i class="fas fa-money-bill-wave"></i> Gestión de Monedas</h2>

<?php if (isset($success_msg)): ?>
<div class="alert alert-success"><?= htmlspecialchars($success_msg) ?></div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Nueva Moneda</h5></div>
    <div class="card-body">
        <form method="POST">
            <div class="row">
                <div class="col-md-2 mb-3">
                    <label>Código ISO *</label>
                    <input type="text" name="codigo" class="form-control" placeholder="USD" maxlength="3" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label>Nombre *</label>
                    <input type="text" name="nombre" class="form-control" placeholder="Dólar Estadounidense" required>
                </div>
                <div class="col-md-2 mb-3">
                    <label>Símbolo *</label>
                    <input type="text" name="simbolo" class="form-control" placeholder="$" required>
                </div>
                <div class="col-md-2 mb-3">
                    <label>&nbsp;</label>
                    <div class="form-check">
                        <input type="checkbox" name="activo" class="form-check-input" id="activo" checked>
                        <label class="form-check-label" for="activo">Activa</label>
                    </div>
                </div>
                <div class="col-md-2 mb-3">
                    <label>&nbsp;</label>
                    <button type="submit" name="crear_moneda" class="btn btn-primary w-100">
                        <i class="fas fa-plus"></i> Crear
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><h5 class="mb-0">Monedas Registradas</h5></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Nombre</th>
                        <th>Símbolo</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($monedas as $m): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($m['codigo']) ?></strong></td>
                        <td><?= htmlspecialchars($m['nombre']) ?></td>
                        <td><span class="badge bg-secondary"><?= htmlspecialchars($m['simbolo']) ?></span></td>
                        <td>
                            <?php if ($m['activo']): ?>
                            <span class="badge bg-success">Activa</span>
                            <?php else: ?>
                            <span class="badge bg-secondary">Inactiva</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-primary" onclick="alert('Editar moneda')">
                                <i class="fas fa-edit"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($monedas)): ?>
                    <tr><td colspan="5" class="text-center">No hay monedas registradas</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="alert alert-info mt-4">
    <h5><i class="fas fa-info-circle"></i> Monedas Principales</h5>
    <ul class="mb-0">
        <li><strong>CLP</strong> - Peso Chileno ($)</li>
        <li><strong>USD</strong> - Dólar Estadounidense ($)</li>
        <li><strong>EUR</strong> - Euro (€)</li>
        <li><strong>UF</strong> - Unidad de Fomento (UF)</li>
        <li><strong>UTM</strong> - Unidad Tributaria Mensual (UTM)</li>
    </ul>
</div>

</div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php include '../includes/footer.php'; ?>
</body></html>
