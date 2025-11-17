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

// Crear impuesto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_impuesto'])) {
    $nombre = trim($_POST['nombre']);
    $codigo = trim(strtoupper($_POST['codigo']));
    $tasa = floatval($_POST['tasa']);
    $tipo = $_POST['tipo'];
    $activo = isset($_POST['activo']) ? 1 : 0;
    
    $stmt = $conn->prepare("INSERT INTO impuestos (empresa_id, nombre, codigo, tasa, tipo, activo) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issdsi", $empresa_id, $nombre, $codigo, $tasa, $tipo, $activo);
    $stmt->execute();
    $success_msg = "Impuesto creado exitosamente";
}

// Obtener impuestos
$impuestos = $conn->query("SELECT * FROM impuestos WHERE empresa_id = $empresa_id ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Configuración de Impuestos</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head><body class="bg-light">
<?php include '../includes/sidebar.php'; ?>
<div class="content-wrapper"><div class="container-fluid py-4">
<h2><i class="fas fa-percentage"></i> Configuración de Impuestos</h2>

<?php if (isset($success_msg)): ?>
<div class="alert alert-success"><?= htmlspecialchars($success_msg) ?></div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Nuevo Impuesto</h5></div>
    <div class="card-body">
        <form method="POST">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label>Nombre *</label>
                    <input type="text" name="nombre" class="form-control" placeholder="IVA" required>
                </div>
                <div class="col-md-2 mb-3">
                    <label>Código *</label>
                    <input type="text" name="codigo" class="form-control" placeholder="IVA" maxlength="10" required>
                </div>
                <div class="col-md-2 mb-3">
                    <label>Tasa (%) *</label>
                    <input type="number" name="tasa" class="form-control" step="0.01" min="0" max="100" required>
                </div>
                <div class="col-md-2 mb-3">
                    <label>Tipo *</label>
                    <select name="tipo" class="form-control" required>
                        <option value="venta">Venta</option>
                        <option value="compra">Compra</option>
                        <option value="retencion">Retención</option>
                    </select>
                </div>
                <div class="col-md-1 mb-3">
                    <label>&nbsp;</label>
                    <div class="form-check">
                        <input type="checkbox" name="activo" class="form-check-input" id="activo" checked>
                        <label class="form-check-label" for="activo">Activo</label>
                    </div>
                </div>
                <div class="col-md-2 mb-3">
                    <label>&nbsp;</label>
                    <button type="submit" name="crear_impuesto" class="btn btn-primary w-100">
                        <i class="fas fa-plus"></i> Crear
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><h5 class="mb-0">Impuestos Configurados</h5></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Código</th>
                        <th>Tasa</th>
                        <th>Tipo</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($impuestos as $imp): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($imp['nombre']) ?></strong></td>
                        <td><code><?= htmlspecialchars($imp['codigo']) ?></code></td>
                        <td><span class="badge bg-primary"><?= number_format($imp['tasa'], 2) ?>%</span></td>
                        <td>
                            <?php
                            $badges_tipo = ['venta' => 'success', 'compra' => 'info', 'retencion' => 'warning'];
                            echo '<span class="badge bg-' . ($badges_tipo[$imp['tipo']] ?? 'secondary') . '">' . ucfirst($imp['tipo']) . '</span>';
                            ?>
                        </td>
                        <td>
                            <?php if ($imp['activo']): ?>
                            <span class="badge bg-success">Activo</span>
                            <?php else: ?>
                            <span class="badge bg-secondary">Inactivo</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-primary" onclick="alert('Editar impuesto')">
                                <i class="fas fa-edit"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($impuestos)): ?>
                    <tr><td colspan="6" class="text-center">No hay impuestos configurados</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="alert alert-info mt-4">
    <h5><i class="fas fa-info-circle"></i> Impuestos Comunes en Chile</h5>
    <div class="row">
        <div class="col-md-6">
            <ul>
                <li><strong>IVA (19%)</strong> - Impuesto al Valor Agregado</li>
                <li><strong>ILA (15%)</strong> - Impuesto a las Bebidas Alcohólicas</li>
                <li><strong>IABA (Variable)</strong> - Impuesto Adicional Bebidas Analcohólicas</li>
            </ul>
        </div>
        <div class="col-md-6">
            <ul>
                <li><strong>Retención 10%</strong> - Honorarios Profesionales</li>
                <li><strong>PPM</strong> - Pago Provisional Mensual</li>
                <li><strong>IET</strong> - Impuesto Específico a los Combustibles</li>
            </ul>
        </div>
    </div>
</div>

</div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php include '../includes/footer.php'; ?>
</body></html>
