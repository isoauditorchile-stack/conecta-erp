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
$stmt->close();

$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_token'])) {
    $nombre = trim($_POST['nombre']);
    $scopes = json_encode($_POST['scopes'] ?? []);
    $expira_dias = intval($_POST['expira_dias']);
    $token = bin2hex(random_bytes(32));
    $expira_en = $expira_dias > 0 ? date('Y-m-d H:i:s', strtotime("+$expira_dias days")) : null;

    $stmt = $conn->prepare("INSERT INTO api_tokens (empresa_id, usuario_id, nombre, token, scopes, expira_en, activo) VALUES (?, ?, ?, ?, ?, ?, 1)");
    $stmt->bind_param("iissss", $empresa_id, $usuario_id, $nombre, $token, $scopes, $expira_en);

    if ($stmt->execute()) {
        $mensaje = "Token creado: <code>$token</code>";
        $tipo_mensaje = "success";
    } else {
        $mensaje = "Error: " . $stmt->error;
        $tipo_mensaje = "danger";
    }
    $stmt->close();
}

if (isset($_GET['toggle'])) {
    $id = intval($_GET['toggle']);
    $conn->query("UPDATE api_tokens SET activo = NOT activo WHERE id = $id AND empresa_id = $empresa_id");
}

$tokens = $conn->query("SELECT t.*, u.nombre as usuario_nombre FROM api_tokens t LEFT JOIN usuarios u ON t.usuario_id = u.id WHERE t.empresa_id = $empresa_id ORDER BY t.fecha_creacion DESC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Tokens - CONECTA ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php include '../includes/sidebar.php'; ?>
    <div class="content-wrapper">
        <div class="container-fluid py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2><i class="fas fa-key"></i> API Tokens</h2>
                    <p class="text-muted">Gestión de tokens de acceso a la API REST</p>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrear">
                    <i class="fas fa-plus"></i> Nuevo Token
                </button>
            </div>

            <?php if ($mensaje): ?>
                <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show">
                    <?php echo $mensaje; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nombre</th>
                                    <th>Token</th>
                                    <th>Usuario</th>
                                    <th>Permisos</th>
                                    <th>Último Uso</th>
                                    <th>Expira</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($t = $tokens->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($t['nombre']); ?></strong></td>
                                        <td><code><?php echo substr($t['token'], 0, 20); ?>...</code></td>
                                        <td><?php echo $t['usuario_nombre']; ?></td>
                                        <td><?php echo count(json_decode($t['scopes'] ?? '[]')); ?> permisos</td>
                                        <td><?php echo $t['ultimo_uso'] ? date('d-m-Y H:i', strtotime($t['ultimo_uso'])) : 'Nunca'; ?></td>
                                        <td><?php echo $t['expira_en'] ? date('d-m-Y', strtotime($t['expira_en'])) : 'Sin expiración'; ?></td>
                                        <td>
                                            <?php if ($t['activo']): ?>
                                                <span class="badge bg-success">Activo</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Inactivo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="?toggle=<?php echo $t['id']; ?>" class="btn btn-sm btn-outline-warning">
                                                <i class="fas fa-power-off"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalCrear" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">Nuevo Token API</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nombre</label>
                            <input type="text" name="nombre" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Permisos</label>
                            <select name="scopes[]" class="form-select" multiple>
                                <option value="read:productos">Leer Productos</option>
                                <option value="write:productos">Escribir Productos</option>
                                <option value="read:facturas">Leer Facturas</option>
                                <option value="write:facturas">Escribir Facturas</option>
                                <option value="read:clientes">Leer Clientes</option>
                                <option value="write:clientes">Escribir Clientes</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Expira en (días)</label>
                            <input type="number" name="expira_dias" class="form-control" value="365">
                            <small class="text-muted">0 = Sin expiración</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="crear_token" class="btn btn-primary">Crear Token</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>function toggleSidebar() { document.getElementById('sidebar').classList.toggle('collapsed'); }</script>
    <?php include '../includes/footer.php'; ?>
</body>
</html>
