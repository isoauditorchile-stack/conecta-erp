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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_webhook'])) {
    $nombre = trim($_POST['nombre']);
    $url = trim($_POST['url']);
    $evento = $_POST['evento'];
    $metodo = $_POST['metodo'];
    $secret = bin2hex(random_bytes(16));

    $stmt = $conn->prepare("INSERT INTO webhooks (empresa_id, nombre, url, evento, metodo, secret_key, activo) VALUES (?, ?, ?, ?, ?, ?, 1)");
    $stmt->bind_param("isssss", $empresa_id, $nombre, $url, $evento, $metodo, $secret);
    $stmt->execute();
    $mensaje = "Webhook creado. Secret: <code>$secret</code>";
    $stmt->close();
}

$webhooks = $conn->query("SELECT * FROM webhooks WHERE empresa_id = $empresa_id ORDER BY fecha_creacion DESC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Webhooks - CONECTA ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php include '../includes/sidebar.php'; ?>
    <div class="content-wrapper">
        <div class="container-fluid py-4">
            <div class="d-flex justify-content-between mb-4">
                <h2><i class="fas fa-webhook"></i> Webhooks</h2>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrear">
                    <i class="fas fa-plus"></i> Nuevo Webhook
                </button>
            </div>
            <?php if ($mensaje): ?>
                <div class="alert alert-success"><?php echo $mensaje; ?></div>
            <?php endif; ?>
            <div class="card">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nombre</th>
                            <th>URL</th>
                            <th>Evento</th>
                            <th>Método</th>
                            <th>Estado</th>
                            <th>Último Envío</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($w = $webhooks->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $w['nombre']; ?></td>
                                <td><code><?php echo $w['url']; ?></code></td>
                                <td><span class="badge bg-info"><?php echo $w['evento']; ?></span></td>
                                <td><?php echo $w['metodo']; ?></td>
                                <td><?php echo $w['activo'] ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-secondary">Inactivo</span>'; ?></td>
                                <td><?php echo $w['fecha_actualizacion'] ? date('d-m-Y H:i', strtotime($w['fecha_actualizacion'])) : 'Nunca'; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="modal fade" id="modalCrear">
        <div class="modal-dialog">
            <form method="POST" class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5>Nuevo Webhook</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Nombre</label>
                        <input type="text" name="nombre" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>URL Destino</label>
                        <input type="url" name="url" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Evento</label>
                        <select name="evento" class="form-select">
                            <option value="factura_creada">Factura Creada</option>
                            <option value="pago_recibido">Pago Recibido</option>
                            <option value="pedido_nuevo">Pedido Nuevo</option>
                            <option value="producto_actualizado">Producto Actualizado</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Método HTTP</label>
                        <select name="metodo" class="form-select">
                            <option value="POST">POST</option>
                            <option value="GET">GET</option>
                            <option value="PUT">PUT</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="crear_webhook" class="btn btn-primary">Crear</button>
                </div>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>function toggleSidebar() { document.getElementById('sidebar').classList.toggle('collapsed'); }</script>
    <?php include '../includes/footer.php'; ?>
</body>
</html>
