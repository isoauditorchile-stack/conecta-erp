<?php
/**
 * PREVIRED - GENERACIÓN ARCHIVO .REM
 * Sistema automático de generación de archivo para Previred
 */
session_start();
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../clases/PreviredManager.php';

requireLogin();

$empresa_id = $_SESSION['empresa_id'];
$success = '';
$error = '';

// Inicializar Previred Manager
$previred = new PreviredManager($conn, $empresa_id);

// Generar archivo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generar_archivo'])) {
    $periodo = $_POST['periodo']; // YYYY-MM

    try {
        $archivo_path = $previred->generarArchivoPrevired($periodo);

        // Validar archivo
        $validacion = $previred->validarArchivo($archivo_path);

        if ($validacion['valido']) {
            $success = 'Archivo Previred generado correctamente: ' . basename($archivo_path);

            // Ofrecer descarga
            if (isset($_POST['descargar'])) {
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . basename($archivo_path) . '"');
                header('Content-Length: ' . filesize($archivo_path));
                readfile($archivo_path);
                exit;
            }
        } else {
            $error = 'El archivo tiene errores: ' . implode(', ', $validacion['errores']);
        }

    } catch (Exception $e) {
        $error = 'Error al generar archivo: ' . $e->getMessage();
    }
}

// Obtener archivos generados
$archivos_previred = fetchAll($conn,
    "SELECT * FROM archivos_previred
     WHERE empresa_id = ?
     ORDER BY fecha_generacion DESC
     LIMIT 12",
    [$empresa_id], 'i'
);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Previred - CONECTA ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .gradient-header {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
            padding: 2rem;
            margin-bottom: 2rem;
            border-radius: 15px;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid py-4">
        <div class="gradient-header">
            <h1><i class="fas fa-hand-holding-usd"></i> Generación Archivo Previred</h1>
            <p class="mb-0">Sistema automático de generación de archivos .REM para Previred</p>
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
            <!-- Generar Archivo -->
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-file-alt"></i> Generar Archivo .REM</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Período</label>
                                <input type="month" name="periodo" class="form-control"
                                       value="<?= date('Y-m') ?>" required>
                            </div>

                            <button type="submit" name="generar_archivo" class="btn btn-success w-100 mb-2">
                                <i class="fas fa-cogs"></i> Generar Archivo
                            </button>

                            <button type="submit" name="generar_archivo" value="1" class="btn btn-outline-success w-100">
                                <i class="fas fa-download"></i> Generar y Descargar
                            </button>
                        </form>

                        <div class="alert alert-info mt-3">
                            <small>
                                <strong>El archivo .REM incluye:</strong><br>
                                • Datos de la empresa<br>
                                • Datos de trabajadores<br>
                                • Cotizaciones AFP<br>
                                • Cotizaciones Salud<br>
                                • SIS y AFC<br>
                                • Totales y validaciones
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Info Tasas -->
                <div class="card shadow-sm mt-3">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-percentage"></i> Tasas 2025</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm mb-0">
                            <tr>
                                <td>Salud (FONASA)</td>
                                <td class="text-end"><strong>7.0%</strong></td>
                            </tr>
                            <tr>
                                <td>SIS</td>
                                <td class="text-end"><strong>0.93%</strong></td>
                            </tr>
                            <tr>
                                <td>AFC Trabajador</td>
                                <td class="text-end"><strong>2.4%</strong></td>
                            </tr>
                            <tr>
                                <td>AFC Empleador</td>
                                <td class="text-end"><strong>3.0%</strong></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Archivos Generados -->
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-folder-open"></i> Archivos Generados</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Período</th>
                                        <th>Fecha Generación</th>
                                        <th>Archivo</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($archivos_previred as $archivo): ?>
                                    <tr>
                                        <td><strong><?= $archivo['periodo'] ?></strong></td>
                                        <td><?= formatDateTime($archivo['fecha_generacion']) ?></td>
                                        <td>
                                            <code><?= basename($archivo['ruta_archivo']) ?></code>
                                        </td>
                                        <td>
                                            <a href="<?= htmlspecialchars($archivo['ruta_archivo']) ?>"
                                               class="btn btn-sm btn-primary" download>
                                                <i class="fas fa-download"></i> Descargar
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>

                                    <?php if (empty($archivos_previred)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">
                                            No hay archivos generados aún
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Información -->
                <div class="alert alert-warning mt-3">
                    <h6><i class="fas fa-info-circle"></i> Instrucciones de Uso</h6>
                    <ol class="mb-0 small">
                        <li>Seleccione el período (mes y año) que desea procesar</li>
                        <li>Haga clic en "Generar Archivo" para crear el archivo .REM</li>
                        <li>El sistema calculará automáticamente todas las cotizaciones</li>
                        <li>Descargue el archivo y súbalo a la plataforma de Previred</li>
                        <li>Previred validará el archivo y procesará el pago</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../../user/includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
