<?php
/**
 * MÓDULO SII - GESTIÓN DE FOLIOS
 * Solicitud y gestión de folios CAF desde el SII
 * Sistema Multiempresa - Multiusuario - Multi idioma
 */
session_start();
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../clases/SIIClientReal.php';

// Verificar autenticación
if (!isset($_SESSION['user_id']) || !isset($_SESSION['empresa_id'])) {
    header('Location: /login.php');
    exit;
}

$empresa_id = (int)$_SESSION['empresa_id'];
$usuario_id = (int)$_SESSION['user_id'];
$conn = getMysqliConnection();

$success = '';
$error = '';

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Solicitar folios al SII
        if (isset($_POST['solicitar_folios'])) {
            $tipo_documento = (int)$_POST['tipo_documento'];
            $cantidad = (int)$_POST['cantidad'];

            if ($cantidad < 1 || $cantidad > 500) {
                throw new Exception("La cantidad debe estar entre 1 y 500");
            }

            // Verificar que exista certificado digital
            $stmt = $conn->prepare("SELECT certificado_path, certificado_password FROM empresas WHERE id = ?");
            $stmt->bind_param('i', $empresa_id);
            $stmt->execute();
            $empresa = $stmt->get_result()->fetch_assoc();

            if (empty($empresa['certificado_path'])) {
                throw new Exception("Debe configurar el certificado digital de la empresa primero");
            }

            // Crear cliente SII
            $siiClient = new SIIClientReal($conn, $empresa_id);

            // Solicitar folios
            $resultado = $siiClient->solicitarFolios($tipo_documento, $cantidad);

            if ($resultado['success']) {
                // Registrar solicitud en la BD
                $stmt = $conn->prepare("
                    INSERT INTO sii_solicitudes_folios
                    (empresa_id, usuario_id, tipo_documento, cantidad, track_id, estado, fecha_solicitud)
                    VALUES (?, ?, ?, ?, ?, 'pendiente', NOW())
                ");
                $stmt->bind_param('iiiis', $empresa_id, $usuario_id, $tipo_documento, $cantidad, $resultado['track_id']);
                $stmt->execute();

                $success = "Solicitud enviada al SII exitosamente. Track ID: " . $resultado['track_id'];

                // Log de auditoría
                logActivity($usuario_id, 'solicitar_folios', "Solicitó $cantidad folios tipo $tipo_documento", 'tributario');
            } else {
                throw new Exception($resultado['error'] ?? 'Error al solicitar folios al SII');
            }
        }

        // Descargar CAF
        if (isset($_POST['descargar_caf'])) {
            $solicitud_id = (int)$_POST['solicitud_id'];

            $stmt = $conn->prepare("
                SELECT track_id, tipo_documento
                FROM sii_solicitudes_folios
                WHERE id = ? AND empresa_id = ?
            ");
            $stmt->bind_param('ii', $solicitud_id, $empresa_id);
            $stmt->execute();
            $solicitud = $stmt->get_result()->fetch_assoc();

            if (!$solicitud) {
                throw new Exception("Solicitud no encontrada");
            }

            $siiClient = new SIIClientReal($conn, $empresa_id);
            $resultado = $siiClient->descargarCAF($solicitud['track_id'], $solicitud['tipo_documento']);

            if ($resultado['success']) {
                // Actualizar estado de solicitud
                $stmt = $conn->prepare("
                    UPDATE sii_solicitudes_folios
                    SET estado = 'aprobada', caf_xml = ?, fecha_descarga = NOW()
                    WHERE id = ?
                ");
                $stmt->bind_param('si', $resultado['caf_xml'], $solicitud_id);
                $stmt->execute();

                // Insertar folios en la tabla
                $folios = $resultado['folios'];
                foreach ($folios as $folio) {
                    $stmt = $conn->prepare("
                        INSERT INTO folios
                        (empresa_id, tipo_documento, folio, estado, fecha_asignacion)
                        VALUES (?, ?, ?, 'disponible', NOW())
                    ");
                    $stmt->bind_param('iii', $empresa_id, $solicitud['tipo_documento'], $folio);
                    $stmt->execute();
                }

                $success = "CAF descargado exitosamente. Se agregaron " . count($folios) . " folios.";
                logActivity($usuario_id, 'descargar_caf', "Descargó CAF con " . count($folios) . " folios", 'tributario');
            } else {
                throw new Exception($resultado['error'] ?? 'Error al descargar CAF');
            }
        }

        // Subir CAF manualmente
        if (isset($_POST['subir_caf'])) {
            if (!isset($_FILES['archivo_caf']) || $_FILES['archivo_caf']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("Error al subir el archivo");
            }

            $tipo_documento = (int)$_POST['tipo_documento_caf'];
            $xml_content = file_get_contents($_FILES['archivo_caf']['tmp_name']);

            // Parsear XML para obtener folios
            $xml = simplexml_load_string($xml_content);
            if ($xml === false) {
                throw new Exception("El archivo no es un XML válido");
            }

            // Extraer rango de folios
            $desde = (int)$xml->CAF->DA->RNG->D ?? 0;
            $hasta = (int)$xml->CAF->DA->RNG->H ?? 0;

            if ($desde === 0 || $hasta === 0) {
                throw new Exception("No se pudo leer el rango de folios del CAF");
            }

            $conn->begin_transaction();

            // Registrar solicitud
            $stmt = $conn->prepare("
                INSERT INTO sii_solicitudes_folios
                (empresa_id, usuario_id, tipo_documento, cantidad, estado, caf_xml, fecha_solicitud, fecha_descarga)
                VALUES (?, ?, ?, ?, 'aprobada', ?, NOW(), NOW())
            ");
            $cantidad = ($hasta - $desde) + 1;
            $stmt->bind_param('iiiis', $empresa_id, $usuario_id, $tipo_documento, $cantidad, $xml_content);
            $stmt->execute();

            // Insertar folios
            for ($folio = $desde; $folio <= $hasta; $folio++) {
                $stmt = $conn->prepare("
                    INSERT INTO folios
                    (empresa_id, tipo_documento, folio, estado, fecha_asignacion)
                    VALUES (?, ?, ?, 'disponible', NOW())
                    ON DUPLICATE KEY UPDATE estado = 'disponible'
                ");
                $stmt->bind_param('iii', $empresa_id, $tipo_documento, $folio);
                $stmt->execute();
            }

            $conn->commit();
            $success = "CAF cargado exitosamente. Se agregaron $cantidad folios (desde $desde hasta $hasta).";
            logActivity($usuario_id, 'subir_caf', "Subió CAF manualmente con $cantidad folios tipo $tipo_documento", 'tributario');
        }

    } catch (Exception $e) {
        if (isset($conn) && $conn->connect_errno === 0) {
            $conn->rollback();
        }
        $error = $e->getMessage();
        error_log("Error en sii_folios.php: " . $error);
    }
}

// Obtener información de la empresa
$stmt = $conn->prepare("
    SELECT nombre, rut, certificado_path, sii_ambiente
    FROM empresas
    WHERE id = ?
");
$stmt->bind_param('i', $empresa_id);
$stmt->execute();
$empresa = $stmt->get_result()->fetch_assoc();

// Obtener folios disponibles por tipo
$stmt = $conn->prepare("
    SELECT
        tipo_documento,
        COUNT(*) as total,
        SUM(CASE WHEN estado = 'disponible' THEN 1 ELSE 0 END) as disponibles,
        SUM(CASE WHEN estado = 'usado' THEN 1 ELSE 0 END) as usados,
        MIN(CASE WHEN estado = 'disponible' THEN folio END) as desde,
        MAX(CASE WHEN estado = 'disponible' THEN folio END) as hasta
    FROM folios
    WHERE empresa_id = ?
    GROUP BY tipo_documento
    ORDER BY tipo_documento
");
$stmt->bind_param('i', $empresa_id);
$stmt->execute();
$folios_por_tipo = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Obtener solicitudes recientes
$stmt = $conn->prepare("
    SELECT
        s.*,
        u.nombre as usuario_nombre,
        CASE s.tipo_documento
            WHEN 33 THEN 'Factura Electrónica'
            WHEN 34 THEN 'Factura Exenta'
            WHEN 39 THEN 'Boleta Electrónica'
            WHEN 41 THEN 'Boleta Exenta'
            WHEN 52 THEN 'Guía de Despacho'
            WHEN 56 THEN 'Nota de Débito'
            WHEN 61 THEN 'Nota de Crédito'
            ELSE CONCAT('Tipo ', s.tipo_documento)
        END as tipo_documento_nombre
    FROM sii_solicitudes_folios s
    LEFT JOIN usuarios u ON s.usuario_id = u.id
    WHERE s.empresa_id = ?
    ORDER BY s.fecha_solicitud DESC
    LIMIT 50
");
$stmt->bind_param('i', $empresa_id);
$stmt->execute();
$solicitudes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Tipos de documentos disponibles
$tipos_documento = [
    33 => 'Factura Electrónica',
    34 => 'Factura Exenta Electrónica',
    39 => 'Boleta Electrónica',
    41 => 'Boleta Exenta Electrónica',
    52 => 'Guía de Despacho Electrónica',
    56 => 'Nota de Débito Electrónica',
    61 => 'Nota de Crédito Electrónica'
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SII - Gestión de Folios | CONECTA ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .gradient-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            margin-bottom: 2rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: all 0.3s;
            border-left: 4px solid #667eea;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        .folio-badge {
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
        }
        .estado-pendiente { background: #ffc107; color: #000; }
        .estado-aprobada { background: #28a745; color: #fff; }
        .estado-rechazada { background: #dc3545; color: #fff; }
        .table-hover tbody tr:hover {
            background-color: #f8f9fa;
            cursor: pointer;
        }
        .certificado-warning {
            background: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 10px;
            padding: 1rem;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid py-4">
        <!-- Header -->
        <div class="gradient-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-2"><i class="fas fa-file-invoice"></i> Gestión de Folios SII</h1>
                    <p class="mb-0 opacity-75">Solicitud y administración de folios CAF desde el SII</p>
                    <small class="d-block mt-2">
                        <i class="fas fa-building"></i> <?= htmlspecialchars($empresa['nombre']) ?> |
                        <i class="fas fa-id-card"></i> RUT: <?= htmlspecialchars($empresa['rut']) ?> |
                        <i class="fas fa-server"></i> Ambiente: <?= $empresa['sii_ambiente'] === 'produccion' ? 'Producción' : 'Certificación' ?>
                    </small>
                </div>
                <div class="col-md-4 text-end">
                    <button class="btn btn-light btn-lg" data-bs-toggle="modal" data-bs-target="#solicitarFoliosModal">
                        <i class="fas fa-plus-circle"></i> Solicitar Folios
                    </button>
                    <button class="btn btn-outline-light btn-lg ms-2" data-bs-toggle="modal" data-bs-target="#subirCafModal">
                        <i class="fas fa-upload"></i> Subir CAF
                    </button>
                </div>
            </div>
        </div>

        <!-- Mensajes -->
        <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> <strong>¡Éxito!</strong> <?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle"></i> <strong>Error:</strong> <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Advertencia de certificado -->
        <?php if (empty($empresa['certificado_path'])): ?>
        <div class="certificado-warning mb-4">
            <h5><i class="fas fa-exclamation-triangle"></i> Certificado Digital no Configurado</h5>
            <p class="mb-2">Para solicitar folios al SII, debe configurar el certificado digital de la empresa.</p>
            <a href="/admin/configuracion.php" class="btn btn-warning">
                <i class="fas fa-cog"></i> Configurar Certificado
            </a>
        </div>
        <?php endif; ?>

        <!-- Estadísticas de Folios -->
        <div class="row mb-4">
            <?php foreach ($folios_por_tipo as $tipo): ?>
            <div class="col-md-3 mb-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h6 class="text-muted mb-1">
                                <?= $tipos_documento[$tipo['tipo_documento']] ?? 'Tipo ' . $tipo['tipo_documento'] ?>
                            </h6>
                            <h3 class="mb-0 text-primary"><?= number_format($tipo['disponibles']) ?></h3>
                            <small class="text-muted">Disponibles</small>
                        </div>
                        <div class="text-end">
                            <i class="fas fa-file-invoice fa-2x text-primary opacity-50"></i>
                        </div>
                    </div>
                    <?php if ($tipo['disponibles'] > 0): ?>
                    <div class="mt-2 pt-2 border-top">
                        <small class="text-muted">
                            Rango: <?= number_format($tipo['desde']) ?> - <?= number_format($tipo['hasta']) ?>
                        </small>
                    </div>
                    <?php endif; ?>
                    <div class="progress mt-2" style="height: 8px;">
                        <?php
                        $porcentaje_disponible = $tipo['total'] > 0 ? ($tipo['disponibles'] / $tipo['total']) * 100 : 0;
                        $color = $porcentaje_disponible > 50 ? 'success' : ($porcentaje_disponible > 20 ? 'warning' : 'danger');
                        ?>
                        <div class="progress-bar bg-<?= $color ?>" style="width: <?= $porcentaje_disponible ?>%"></div>
                    </div>
                    <small class="text-muted">
                        Usados: <?= number_format($tipo['usados']) ?> / Total: <?= number_format($tipo['total']) ?>
                    </small>
                </div>
            </div>
            <?php endforeach; ?>

            <?php if (empty($folios_por_tipo)): ?>
            <div class="col-12">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> No hay folios registrados. Solicite folios al SII o suba un archivo CAF.
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Solicitudes de Folios -->
        <div class="card shadow-sm">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0"><i class="fas fa-history"></i> Historial de Solicitudes</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Fecha Solicitud</th>
                                <th>Tipo Documento</th>
                                <th>Cantidad</th>
                                <th>Track ID</th>
                                <th>Estado</th>
                                <th>Usuario</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($solicitudes as $solicitud): ?>
                            <tr>
                                <td><?= date('d/m/Y H:i', strtotime($solicitud['fecha_solicitud'])) ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($solicitud['tipo_documento_nombre']) ?></strong>
                                    <br><small class="text-muted">Tipo <?= $solicitud['tipo_documento'] ?></small>
                                </td>
                                <td><span class="badge bg-info"><?= number_format($solicitud['cantidad']) ?> folios</span></td>
                                <td>
                                    <?php if ($solicitud['track_id']): ?>
                                    <code class="small"><?= htmlspecialchars($solicitud['track_id']) ?></code>
                                    <?php else: ?>
                                    <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge folio-badge estado-<?= $solicitud['estado'] ?>">
                                        <?= ucfirst($solicitud['estado']) ?>
                                    </span>
                                </td>
                                <td>
                                    <small><?= htmlspecialchars($solicitud['usuario_nombre']) ?></small>
                                </td>
                                <td>
                                    <?php if ($solicitud['estado'] === 'pendiente'): ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="solicitud_id" value="<?= $solicitud['id'] ?>">
                                        <button type="submit" name="descargar_caf" class="btn btn-sm btn-success"
                                                title="Descargar CAF">
                                            <i class="fas fa-download"></i> Descargar CAF
                                        </button>
                                    </form>
                                    <?php elseif ($solicitud['estado'] === 'aprobada'): ?>
                                    <button class="btn btn-sm btn-info"
                                            onclick="verCAF(<?= $solicitud['id'] ?>)"
                                            title="Ver CAF">
                                        <i class="fas fa-eye"></i> Ver CAF
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>

                            <?php if (empty($solicitudes)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                    No hay solicitudes de folios registradas
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Solicitar Folios -->
    <div class="modal fade" id="solicitarFoliosModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-plus-circle"></i> Solicitar Folios al SII</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="formSolicitarFolios">
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> <strong>Importante:</strong> La solicitud será enviada al SII en el ambiente de
                            <strong><?= $empresa['sii_ambiente'] === 'produccion' ? 'Producción' : 'Certificación' ?></strong>.
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Tipo de Documento *</label>
                            <select name="tipo_documento" class="form-select" required>
                                <option value="">Seleccione...</option>
                                <?php foreach ($tipos_documento as $codigo => $nombre): ?>
                                <option value="<?= $codigo ?>"><?= $codigo ?> - <?= $nombre ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Cantidad de Folios *</label>
                            <input type="number" name="cantidad" class="form-control"
                                   min="1" max="500" value="50" required>
                            <small class="text-muted">Mínimo: 1 | Máximo: 500</small>
                        </div>

                        <?php if (empty($empresa['certificado_path'])): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            Debe configurar el certificado digital antes de solicitar folios.
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="solicitar_folios" class="btn btn-primary"
                                <?= empty($empresa['certificado_path']) ? 'disabled' : '' ?>>
                            <i class="fas fa-paper-plane"></i> Enviar Solicitud al SII
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Subir CAF -->
    <div class="modal fade" id="subirCafModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fas fa-upload"></i> Subir Archivo CAF</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> Suba un archivo CAF (XML) descargado desde el sitio del SII.
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Tipo de Documento *</label>
                            <select name="tipo_documento_caf" class="form-select" required>
                                <option value="">Seleccione...</option>
                                <?php foreach ($tipos_documento as $codigo => $nombre): ?>
                                <option value="<?= $codigo ?>"><?= $codigo ?> - <?= $nombre ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Archivo CAF (XML) *</label>
                            <input type="file" name="archivo_caf" class="form-control"
                                   accept=".xml" required>
                            <small class="text-muted">Formato: XML</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="subir_caf" class="btn btn-success">
                            <i class="fas fa-upload"></i> Subir CAF
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function verCAF(solicitudId) {
        alert('Ver detalles del CAF #' + solicitudId);
        // Aquí se puede implementar un modal con los detalles del CAF
    }
    </script>
</body>
</html>
