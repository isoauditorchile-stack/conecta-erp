<?php
/**
 * SII - DESCARGA AUTOMÁTICA DE FOLIOS
 * Sistema REAL de conexión con SII Chile
 */
session_start();
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../clases/SIIClientReal.php';

requireLogin();

$empresa_id = $_SESSION['empresa_id'];
$success = '';
$error = '';

// Inicializar cliente SII
$siiClient = new SIIClientReal($conn, $empresa_id);

// Procesar solicitud de folios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['solicitar_folios'])) {
        $tipo_documento = intval($_POST['tipo_documento']);
        $cantidad = intval($_POST['cantidad']);

        $resultado = $siiClient->solicitarFolios($tipo_documento, $cantidad);

        if ($resultado['success']) {
            $success = $resultado['mensaje'] . ' (Track ID: ' . $resultado['track_id'] . ')';
        } else {
            $error = $resultado['error'];
        }
    }

    if (isset($_POST['descargar_caf'])) {
        $track_id = $_POST['track_id'];

        $resultado = $siiClient->descargarCAF($track_id);

        if ($resultado['success']) {
            $success = 'Folios descargados: ' . $resultado['folio_desde'] . ' al ' . $resultado['folio_hasta'];
        } else {
            $error = $resultado['error'];
        }
    }
}

// Obtener folios disponibles
$folios_disponibles = fetchAll($conn,
    "SELECT tipo_documento, COUNT(*) as cantidad, MIN(folio) as desde, MAX(folio) as hasta
     FROM folios
     WHERE empresa_id = ? AND estado = 'disponible'
     GROUP BY tipo_documento",
    [$empresa_id], 'i'
);

// Obtener solicitudes pendientes
$solicitudes = fetchAll($conn,
    "SELECT * FROM solicitudes_folios
     WHERE empresa_id = ?
     ORDER BY fecha_solicitud DESC
     LIMIT 20",
    [$empresa_id], 'i'
);

$tipos_documento = [
    33 => 'Factura Electrónica',
    34 => 'Factura Exenta',
    39 => 'Boleta Electrónica',
    41 => 'Boleta Exenta',
    52 => 'Guía de Despacho',
    56 => 'Nota de Débito',
    61 => 'Nota de Crédito'
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SII - Gestión de Folios - CONECTA ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .gradient-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            margin-bottom: 2rem;
            border-radius: 15px;
        }
        .folio-card {
            border-left: 4px solid #667eea;
            transition: all 0.3s;
        }
        .folio-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid py-4">
        <div class="gradient-header">
            <h1><i class="fas fa-file-invoice"></i> SII - Gestión de Folios Electrónicos</h1>
            <p class="mb-0">Descarga automática desde el Servicio de Impuestos Internos</p>
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

        <div class="row">
            <!-- Solicitar Folios -->
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-download"></i> Solicitar Folios al SII</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Tipo de Documento</label>
                                <select name="tipo_documento" class="form-select" required>
                                    <?php foreach ($tipos_documento as $codigo => $nombre): ?>
                                    <option value="<?= $codigo ?>"><?= $codigo ?> - <?= $nombre ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Cantidad de Folios</label>
                                <select name="cantidad" class="form-select" required>
                                    <option value="50">50 folios</option>
                                    <option value="100" selected>100 folios</option>
                                    <option value="200">200 folios</option>
                                    <option value="500">500 folios</option>
                                </select>
                            </div>
                            <button type="submit" name="solicitar_folios" class="btn btn-primary w-100">
                                <i class="fas fa-paper-plane"></i> Solicitar al SII
                            </button>
                        </form>

                        <div class="alert alert-info mt-3">
                            <small>
                                <i class="fas fa-info-circle"></i> El sistema se conecta automáticamente al SII usando certificado digital.
                                Los folios se descargan en formato CAF y se almacenan en el sistema.
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Folios Disponibles -->
            <div class="col-md-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-clipboard-list"></i> Folios Disponibles</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($folios_disponibles)): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> No hay folios disponibles. Solicite folios al SII.
                        </div>
                        <?php else: ?>
                        <div class="row">
                            <?php foreach ($folios_disponibles as $folio): ?>
                            <div class="col-md-6 mb-3">
                                <div class="card folio-card">
                                    <div class="card-body">
                                        <h6><?= $tipos_documento[$folio['tipo_documento']] ?? 'Doc ' . $folio['tipo_documento'] ?></h6>
                                        <p class="mb-1"><strong class="text-success"><?= number_format($folio['cantidad']) ?> folios</strong></p>
                                        <p class="text-muted small mb-0">Desde <?= $folio['desde'] ?> hasta <?= $folio['hasta'] ?></p>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Solicitudes Recientes -->
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-history"></i> Solicitudes Recientes</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Tipo Doc</th>
                                        <th>Cantidad</th>
                                        <th>Track ID</th>
                                        <th>Estado</th>
                                        <th>Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($solicitudes as $sol): ?>
                                    <tr>
                                        <td><?= formatDateTime($sol['fecha_solicitud']) ?></td>
                                        <td><?= $tipos_documento[$sol['tipo_documento']] ?? $sol['tipo_documento'] ?></td>
                                        <td><?= $sol['cantidad'] ?></td>
                                        <td><code><?= htmlspecialchars($sol['track_id']) ?></code></td>
                                        <td>
                                            <?php if ($sol['estado'] === 'pendiente'): ?>
                                            <span class="badge bg-warning">Pendiente</span>
                                            <?php elseif ($sol['estado'] === 'descargado'): ?>
                                            <span class="badge bg-success">Descargado</span>
                                            <?php else: ?>
                                            <span class="badge bg-secondary"><?= $sol['estado'] ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($sol['estado'] === 'pendiente'): ?>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="track_id" value="<?= htmlspecialchars($sol['track_id']) ?>">
                                                <button type="submit" name="descargar_caf" class="btn btn-sm btn-success">
                                                    <i class="fas fa-download"></i> Descargar
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>

                                    <?php if (empty($solicitudes)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">No hay solicitudes registradas</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../../user/includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
