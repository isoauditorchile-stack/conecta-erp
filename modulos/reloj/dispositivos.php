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

// Crear dispositivo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_dispositivo'])) {
    $codigo = trim($_POST['codigo']);
    $nombre = trim($_POST['nombre']);
    $tipo = $_POST['tipo'];
    $ip = trim($_POST['ip']);
    $ubicacion = trim($_POST['ubicacion']);
    
    $stmt = $conn->prepare("INSERT INTO dispositivos_biometricos (empresa_id, codigo, nombre, tipo, ip, ubicacion, activo) VALUES (?, ?, ?, ?, ?, ?, 1)");
    $stmt->bind_param("isssss", $empresa_id, $codigo, $nombre, $tipo, $ip, $ubicacion);
    $stmt->execute();
    $success_msg = "Dispositivo creado exitosamente";
}

// Cambiar estado
if (isset($_GET['toggle'])) {
    $id = intval($_GET['toggle']);
    $conn->query("UPDATE dispositivos_biometricos SET activo = NOT activo WHERE id = $id AND empresa_id = $empresa_id");
    header("Location: dispositivos.php");
    exit;
}

// Obtener dispositivos
$dispositivos = $conn->query("SELECT d.*, 
    (SELECT COUNT(*) FROM marcajes m WHERE m.dispositivo_id = d.id AND DATE(m.fecha_hora) = CURDATE()) as marcajes_hoy
    FROM dispositivos_biometricos d 
    WHERE d.empresa_id = $empresa_id 
    ORDER BY d.activo DESC, d.nombre ASC")->fetch_all(MYSQLI_ASSOC);

$stats = $conn->query("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN activo = 1 THEN 1 ELSE 0 END) as activos,
    (SELECT COUNT(*) FROM marcajes WHERE empresa_id = $empresa_id AND DATE(fecha_hora) = CURDATE() AND origen = 'biometrico') as marcajes_hoy
    FROM dispositivos_biometricos WHERE empresa_id = $empresa_id")->fetch_assoc();
?>
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Dispositivos Biométricos</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head><body class="bg-light">
<?php include '../includes/sidebar.php'; ?>
<div class="content-wrapper"><div class="container-fluid py-4">
<h2><i class="fas fa-fingerprint"></i> Dispositivos Biométricos</h2>

<?php if (isset($success_msg)): ?>
<div class="alert alert-success"><?= htmlspecialchars($success_msg) ?></div>
<?php endif; ?>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h5>Total Dispositivos</h5>
                <h2><?= $stats['total'] ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h5>Dispositivos Activos</h5>
                <h2><?= $stats['activos'] ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-info text-white">
            <div class="card-body">
                <h5>Marcajes Hoy (Biométricos)</h5>
                <h2><?= $stats['marcajes_hoy'] ?></h2>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Nuevo Dispositivo</h5>
    </div>
    <div class="card-body">
        <form method="POST">
            <div class="row">
                <div class="col-md-3">
                    <label>Código *</label>
                    <input type="text" name="codigo" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label>Nombre *</label>
                    <input type="text" name="nombre" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <label>Tipo *</label>
                    <select name="tipo" class="form-control" required>
                        <option value="huella">Huella Digital</option>
                        <option value="facial">Reconocimiento Facial</option>
                        <option value="tarjeta">Tarjeta RFID</option>
                        <option value="hibrido">Híbrido</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label>IP</label>
                    <input type="text" name="ip" class="form-control" placeholder="192.168.1.100">
                </div>
                <div class="col-md-2">
                    <label>&nbsp;</label>
                    <button type="submit" name="crear_dispositivo" class="btn btn-primary w-100">
                        <i class="fas fa-plus"></i> Crear
                    </button>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-md-12">
                    <label>Ubicación</label>
                    <input type="text" name="ubicacion" class="form-control" placeholder="Entrada principal, Oficina, etc.">
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><h5 class="mb-0">Dispositivos Registrados</h5></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Nombre</th>
                        <th>Tipo</th>
                        <th>IP</th>
                        <th>Ubicación</th>
                        <th>Marcajes Hoy</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dispositivos as $d): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($d['codigo']) ?></strong></td>
                        <td><?= htmlspecialchars($d['nombre']) ?></td>
                        <td>
                            <?php
                            $iconos = ['huella' => 'fa-fingerprint', 'facial' => 'fa-user', 'tarjeta' => 'fa-id-card', 'hibrido' => 'fa-layer-group'];
                            echo '<i class="fas ' . $iconos[$d['tipo']] . '"></i> ' . ucfirst($d['tipo']);
                            ?>
                        </td>
                        <td><code><?= htmlspecialchars($d['ip'] ?? 'N/A') ?></code></td>
                        <td><?= htmlspecialchars($d['ubicacion'] ?? 'Sin especificar') ?></td>
                        <td><span class="badge bg-info"><?= $d['marcajes_hoy'] ?></span></td>
                        <td>
                            <?php if ($d['activo']): ?>
                            <span class="badge bg-success">Activo</span>
                            <?php else: ?>
                            <span class="badge bg-secondary">Inactivo</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="?toggle=<?= $d['id'] ?>" class="btn btn-sm btn-warning">
                                <i class="fas fa-power-off"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($dispositivos)): ?>
                    <tr><td colspan="8" class="text-center">No hay dispositivos registrados</td></tr>
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
