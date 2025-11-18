<?php
/**
 * =====================================================
 * SISTEMA COMPLETO DE DOCUMENTOS TRIBUTARIOS ELECTRÓNICOS (DTE)
 * =====================================================
 * Sistema integral de emisión, envío y gestión de todos los tipos de DTE del SII
 *
 * TIPOS DE DOCUMENTOS SOPORTADOS:
 * --------------------------------
 * FASE 1 - Documentos Esenciales:
 * - 33: Factura Electrónica
 * - 39: Boleta Electrónica
 * - 52: Guía de Despacho Electrónica
 * - 61: Nota de Crédito Electrónica
 * - 56: Nota de Débito Electrónica
 *
 * FASE 2 - Documentos Importantes:
 * - 34: Factura No Afecta o Exenta Electrónica
 * - 41: Boleta No Afecta o Exenta Electrónica
 * - 46: Factura de Compra Electrónica
 *
 * FASE 3 - Documentos de Exportación:
 * - 110: Factura de Exportación Electrónica
 * - 111: Nota de Débito de Exportación Electrónica
 * - 112: Nota de Crédito de Exportación Electrónica
 *
 * FASE 4 - Documentos Especializados:
 * - 43: Liquidación-Factura Electrónica
 * - 48: Comprobante de Pago Electrónico
 * - 103: Liquidación
 * - 801-914: Documentos especializados adicionales
 */

session_start();
require_once '../../includes/config.php';
require_once '../../includes/middleware_acceso.php';

// Validar acceso al módulo de ventas
requiereModulo('ventas');

$empresa_id = $_SESSION['empresa_id'];
$usuario_id = $_SESSION['user_id'];

// Obtener información de la empresa
$query_empresa = "SELECT * FROM empresas WHERE id = ?";
$stmt_empresa = $conn->prepare($query_empresa);
$stmt_empresa->bind_param("i", $empresa_id);
$stmt_empresa->execute();
$empresa = $stmt_empresa->get_result()->fetch_assoc();

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    switch ($action) {
        case 'crear_factura_33':
            // Crear Factura Electrónica (33)
            require_once 'procesos/crear_factura_electronica.php';
            break;

        case 'crear_boleta_39':
            // Crear Boleta Electrónica (39)
            require_once 'procesos/crear_boleta_electronica.php';
            break;

        case 'crear_guia_52':
            // Crear Guía de Despacho (52)
            require_once 'procesos/crear_guia_despacho.php';
            break;

        case 'crear_nota_credito_61':
            // Crear Nota de Crédito (61)
            require_once 'procesos/crear_nota_credito.php';
            break;

        case 'crear_nota_debito_56':
            // Crear Nota de Débito (56)
            require_once 'procesos/crear_nota_debito.php';
            break;

        case 'anular_documento':
            // Anular documento
            $documento_id = $_POST['documento_id'];
            $motivo = $_POST['motivo'];

            $query_anular = "UPDATE documentos_tributarios
                            SET estado = 'anulado',
                                motivo_anulacion = ?,
                                fecha_anulacion = NOW(),
                                anulado_por = ?
                            WHERE id = ? AND empresa_id = ?";
            $stmt_anular = $conn->prepare($query_anular);
            $stmt_anular->bind_param("siii", $motivo, $usuario_id, $documento_id, $empresa_id);

            if ($stmt_anular->execute()) {
                $_SESSION['mensaje'] = "Documento anulado exitosamente";
                $_SESSION['tipo_mensaje'] = "success";
            } else {
                $_SESSION['mensaje'] = "Error al anular documento";
                $_SESSION['tipo_mensaje'] = "error";
            }
            break;

        case 'enviar_sii':
            // Enviar DTE al SII
            require_once 'procesos/enviar_dte_sii.php';
            break;
    }

    header("Location: documentos_dte.php");
    exit;
}

// Obtener filtros
$filtro_tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';
$filtro_estado = isset($_GET['estado']) ? $_GET['estado'] : '';
$filtro_fecha_desde = isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : date('Y-m-01');
$filtro_fecha_hasta = isset($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : date('Y-m-d');

// Construir query de documentos
$query_docs = "
    SELECT
        dt.id,
        dt.tipo_documento,
        tds.nombre as tipo_nombre,
        tds.codigo as tipo_codigo,
        dt.folio,
        dt.fecha_emision,
        dt.razon_social_receptor,
        dt.rut_receptor,
        dt.monto_neto,
        dt.monto_iva,
        dt.monto_total,
        dt.estado,
        dt.track_id_sii,
        dt.fecha_envio_sii,
        dt.fecha_recepcion_sii,
        dt.estado_sii,
        dt.glosa_estado_sii,
        dt.referencia_tipo,
        dt.referencia_folio,
        CASE
            WHEN dt.estado = 'borrador' THEN 'secondary'
            WHEN dt.estado = 'timbrado' THEN 'info'
            WHEN dt.estado = 'enviado' THEN 'warning'
            WHEN dt.estado = 'aceptado' THEN 'success'
            WHEN dt.estado = 'rechazado' THEN 'danger'
            WHEN dt.estado = 'anulado' THEN 'dark'
            ELSE 'secondary'
        END as badge_color
    FROM documentos_tributarios dt
    INNER JOIN tipos_documentos_sii tds ON dt.tipo_documento = tds.codigo
    WHERE dt.empresa_id = ?
";

$params = [$empresa_id];
$types = "i";

if ($filtro_tipo != '') {
    $query_docs .= " AND dt.tipo_documento = ?";
    $params[] = $filtro_tipo;
    $types .= "i";
}

if ($filtro_estado != '') {
    $query_docs .= " AND dt.estado = ?";
    $params[] = $filtro_estado;
    $types .= "s";
}

if ($filtro_fecha_desde != '') {
    $query_docs .= " AND dt.fecha_emision >= ?";
    $params[] = $filtro_fecha_desde;
    $types .= "s";
}

if ($filtro_fecha_hasta != '') {
    $query_docs .= " AND dt.fecha_emision <= ?";
    $params[] = $filtro_fecha_hasta;
    $types .= "s";
}

$query_docs .= " ORDER BY dt.fecha_emision DESC, dt.folio DESC LIMIT 100";

$stmt_docs = $conn->prepare($query_docs);
$stmt_docs->bind_param($types, ...$params);
$stmt_docs->execute();
$documentos = $stmt_docs->get_result();

// Estadísticas del mes actual
$query_stats = "
    SELECT
        COUNT(*) as total_documentos,
        SUM(CASE WHEN estado = 'aceptado' THEN 1 ELSE 0 END) as documentos_aceptados,
        SUM(CASE WHEN estado = 'rechazado' THEN 1 ELSE 0 END) as documentos_rechazados,
        SUM(CASE WHEN estado IN ('aceptado', 'enviado') THEN monto_total ELSE 0 END) as monto_total_mes
    FROM documentos_tributarios
    WHERE empresa_id = ?
      AND MONTH(fecha_emision) = MONTH(CURRENT_DATE())
      AND YEAR(fecha_emision) = YEAR(CURRENT_DATE())
";
$stmt_stats = $conn->prepare($query_stats);
$stmt_stats->bind_param("i", $empresa_id);
$stmt_stats->execute();
$stats = $stmt_stats->get_result()->fetch_assoc();

// Obtener tipos de documentos disponibles
$query_tipos = "
    SELECT codigo, nombre, categoria, fase_implementacion, descripcion
    FROM tipos_documentos_sii
    WHERE electronico = 1
      AND activo = 1
    ORDER BY fase_implementacion ASC, codigo ASC
";
$tipos_documentos = $conn->query($query_tipos);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documentos Tributarios Electrónicos (DTE) - CONECTA ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --success-gradient: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            --danger-gradient: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);
            --warning-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
        }

        .page-header {
            background: var(--primary-gradient);
            color: white;
            padding: 40px 0;
            margin-bottom: 30px;
            border-radius: 0 0 30px 30px;
            box-shadow: 0 10px 40px rgba(102, 126, 234, 0.3);
        }

        .page-header h1 {
            font-weight: 800;
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .page-header p {
            opacity: 0.95;
            font-size: 1.1rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            border-left: 5px solid;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
        }

        .stat-card.blue { border-left-color: #667eea; }
        .stat-card.green { border-left-color: #38ef7d; }
        .stat-card.red { border-left-color: #f45c43; }
        .stat-card.purple { border-left-color: #764ba2; }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 800;
            margin: 10px 0;
        }

        .stat-card.blue .stat-value { color: #667eea; }
        .stat-card.green .stat-value { color: #38ef7d; }
        .stat-card.red .stat-value { color: #f45c43; }
        .stat-card.purple .stat-value { color: #764ba2; }

        .stat-label {
            color: #6c757d;
            font-size: 0.95rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-icon {
            font-size: 2.5rem;
            opacity: 0.2;
            float: right;
        }

        .action-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .btn-action {
            padding: 15px 20px;
            border-radius: 15px;
            border: none;
            color: white;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .btn-action:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 25px rgba(0,0,0,0.15);
            color: white;
        }

        .btn-action i {
            font-size: 1.3rem;
        }

        .btn-factura { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .btn-boleta { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .btn-guia { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .btn-nota-credito { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
        .btn-nota-debito { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }
        .btn-exportacion { background: linear-gradient(135deg, #30cfd0 0%, #330867 100%); }

        .filters-card {
            background: white;
            padding: 25px;
            border-radius: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }

        .documents-table {
            background: white;
            padding: 25px;
            border-radius: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }

        .table {
            margin-bottom: 0;
        }

        .table thead {
            background: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
        }

        .table thead th {
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            padding: 15px 10px;
            border: none;
        }

        .table tbody td {
            padding: 15px 10px;
            vertical-align: middle;
        }

        .badge-status {
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
        }

        .btn-table {
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 0.85rem;
            border: none;
            transition: all 0.2s ease;
        }

        .btn-table:hover {
            transform: scale(1.05);
        }

        .modal-header {
            background: var(--primary-gradient);
            color: white;
            border-radius: 15px 15px 0 0;
        }

        .modal-content {
            border-radius: 15px;
            border: none;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }

        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 8px;
        }

        .form-control, .form-select {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 10px 15px;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .tipo-documento-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 700;
            margin-left: 8px;
        }

        .fase-1 { background: #667eea; color: white; }
        .fase-2 { background: #38ef7d; color: white; }
        .fase-3 { background: #4facfe; color: white; }
        .fase-4 { background: #f5576c; color: white; }

        .alert-custom {
            border-radius: 15px;
            border: none;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }

        .detalle-row {
            background: #f8f9fa;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 8px;
            border-left: 3px solid #667eea;
        }

        .btn-add-item {
            background: var(--success-gradient);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-add-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(56, 239, 125, 0.3);
        }

        .total-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 15px;
            margin-top: 20px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            font-size: 1.1rem;
        }

        .total-row.grand-total {
            border-top: 2px solid #667eea;
            margin-top: 10px;
            padding-top: 15px;
            font-weight: 800;
            font-size: 1.5rem;
            color: #667eea;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="page-header">
        <div class="container">
            <h1><i class="fas fa-file-invoice"></i> Documentos Tributarios Electrónicos</h1>
            <p class="mb-0">
                <i class="fas fa-building"></i> <?php echo htmlspecialchars($empresa['razon_social']); ?>
                | <i class="fas fa-id-card"></i> RUT: <?php echo htmlspecialchars($empresa['rut']); ?>
            </p>
        </div>
    </div>

    <div class="container">
        <!-- Mensajes -->
        <?php if (isset($_SESSION['mensaje'])): ?>
        <div class="alert alert-<?php echo $_SESSION['tipo_mensaje']; ?> alert-custom alert-dismissible fade show">
            <i class="fas fa-<?php echo $_SESSION['tipo_mensaje'] == 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
            <?php echo $_SESSION['mensaje']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php
            unset($_SESSION['mensaje']);
            unset($_SESSION['tipo_mensaje']);
        endif;
        ?>

        <!-- Estadísticas -->
        <div class="stats-grid">
            <div class="stat-card blue">
                <i class="fas fa-file-invoice stat-icon"></i>
                <div class="stat-label">Documentos del Mes</div>
                <div class="stat-value"><?php echo number_format($stats['total_documentos']); ?></div>
            </div>
            <div class="stat-card green">
                <i class="fas fa-check-circle stat-icon"></i>
                <div class="stat-label">Aceptados</div>
                <div class="stat-value"><?php echo number_format($stats['documentos_aceptados']); ?></div>
            </div>
            <div class="stat-card red">
                <i class="fas fa-times-circle stat-icon"></i>
                <div class="stat-label">Rechazados</div>
                <div class="stat-value"><?php echo number_format($stats['documentos_rechazados']); ?></div>
            </div>
            <div class="stat-card purple">
                <i class="fas fa-dollar-sign stat-icon"></i>
                <div class="stat-label">Monto Total Mes</div>
                <div class="stat-value">$<?php echo number_format($stats['monto_total_mes'], 0, ',', '.'); ?></div>
            </div>
        </div>

        <!-- Botones de Acción -->
        <div class="action-buttons">
            <button class="btn-action btn-factura" onclick="abrirModalDocumento(33)">
                <i class="fas fa-file-invoice"></i>
                <span>Factura Electrónica (33)</span>
            </button>
            <button class="btn-action btn-boleta" onclick="abrirModalDocumento(39)">
                <i class="fas fa-receipt"></i>
                <span>Boleta Electrónica (39)</span>
            </button>
            <button class="btn-action btn-guia" onclick="abrirModalDocumento(52)">
                <i class="fas fa-truck"></i>
                <span>Guía de Despacho (52)</span>
            </button>
            <button class="btn-action btn-nota-credito" onclick="abrirModalDocumento(61)">
                <i class="fas fa-undo"></i>
                <span>Nota de Crédito (61)</span>
            </button>
            <button class="btn-action btn-nota-debito" onclick="abrirModalDocumento(56)">
                <i class="fas fa-plus-circle"></i>
                <span>Nota de Débito (56)</span>
            </button>
            <button class="btn-action btn-exportacion" onclick="mostrarMasTipos()">
                <i class="fas fa-ellipsis-h"></i>
                <span>Más Tipos de DTE</span>
            </button>
        </div>

        <!-- Filtros -->
        <div class="filters-card">
            <h5 class="mb-3"><i class="fas fa-filter"></i> Filtros</h5>
            <form method="GET" action="">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Tipo de Documento</label>
                        <select name="tipo" class="form-select">
                            <option value="">Todos los tipos</option>
                            <?php
                            $tipos_documentos->data_seek(0);
                            while ($tipo = $tipos_documentos->fetch_assoc()):
                            ?>
                                <option value="<?php echo $tipo['codigo']; ?>"
                                        <?php echo $filtro_tipo == $tipo['codigo'] ? 'selected' : ''; ?>>
                                    <?php echo $tipo['codigo']; ?> - <?php echo $tipo['nombre']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Estado</label>
                        <select name="estado" class="form-select">
                            <option value="">Todos los estados</option>
                            <option value="borrador" <?php echo $filtro_estado == 'borrador' ? 'selected' : ''; ?>>Borrador</option>
                            <option value="timbrado" <?php echo $filtro_estado == 'timbrado' ? 'selected' : ''; ?>>Timbrado</option>
                            <option value="enviado" <?php echo $filtro_estado == 'enviado' ? 'selected' : ''; ?>>Enviado</option>
                            <option value="aceptado" <?php echo $filtro_estado == 'aceptado' ? 'selected' : ''; ?>>Aceptado</option>
                            <option value="rechazado" <?php echo $filtro_estado == 'rechazado' ? 'selected' : ''; ?>>Rechazado</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Fecha Desde</label>
                        <input type="date" name="fecha_desde" class="form-control" value="<?php echo $filtro_fecha_desde; ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Fecha Hasta</label>
                        <input type="date" name="fecha_hasta" class="form-control" value="<?php echo $filtro_fecha_hasta; ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i> Buscar
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Tabla de Documentos -->
        <div class="documents-table">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0"><i class="fas fa-list"></i> Listado de Documentos</h4>
                <div>
                    <button class="btn btn-outline-primary btn-sm" onclick="exportarExcel()">
                        <i class="fas fa-file-excel"></i> Exportar
                    </button>
                    <a href="folios.php" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-receipt"></i> Gestión de Folios
                    </a>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Tipo</th>
                            <th>Folio</th>
                            <th>Fecha</th>
                            <th>Receptor</th>
                            <th>RUT</th>
                            <th class="text-end">Neto</th>
                            <th class="text-end">IVA</th>
                            <th class="text-end">Total</th>
                            <th>Estado</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($documentos->num_rows == 0): ?>
                        <tr>
                            <td colspan="10" class="text-center py-5">
                                <i class="fas fa-inbox" style="font-size: 4rem; color: #dee2e6; margin-bottom: 20px; display: block;"></i>
                                <p class="text-muted">No hay documentos registrados con los filtros seleccionados</p>
                                <button class="btn btn-primary mt-3" onclick="abrirModalDocumento(33)">
                                    <i class="fas fa-plus"></i> Crear Primer Documento
                                </button>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php while ($doc = $documentos->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <strong><?php echo $doc['tipo_codigo']; ?></strong>
                                    <br><small class="text-muted"><?php echo $doc['tipo_nombre']; ?></small>
                                </td>
                                <td><strong><?php echo $doc['folio']; ?></strong></td>
                                <td><?php echo date('d/m/Y', strtotime($doc['fecha_emision'])); ?></td>
                                <td><?php echo htmlspecialchars(substr($doc['razon_social_receptor'], 0, 30)); ?></td>
                                <td><?php echo htmlspecialchars($doc['rut_receptor']); ?></td>
                                <td class="text-end">$<?php echo number_format($doc['monto_neto'], 0, ',', '.'); ?></td>
                                <td class="text-end">$<?php echo number_format($doc['monto_iva'], 0, ',', '.'); ?></td>
                                <td class="text-end"><strong>$<?php echo number_format($doc['monto_total'], 0, ',', '.'); ?></strong></td>
                                <td>
                                    <span class="badge bg-<?php echo $doc['badge_color']; ?> badge-status">
                                        <?php echo strtoupper($doc['estado']); ?>
                                    </span>
                                    <?php if ($doc['estado_sii']): ?>
                                        <br><small class="text-muted"><?php echo $doc['estado_sii']; ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-info btn-table" onclick="verDocumento(<?php echo $doc['id']; ?>)" title="Ver">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-primary btn-table" onclick="descargarPDF(<?php echo $doc['id']; ?>)" title="PDF">
                                            <i class="fas fa-file-pdf"></i>
                                        </button>
                                        <?php if ($doc['estado'] == 'timbrado'): ?>
                                        <button class="btn btn-success btn-table" onclick="enviarSII(<?php echo $doc['id']; ?>)" title="Enviar al SII">
                                            <i class="fas fa-paper-plane"></i>
                                        </button>
                                        <?php endif; ?>
                                        <?php if (in_array($doc['estado'], ['borrador', 'timbrado'])): ?>
                                        <button class="btn btn-danger btn-table" onclick="anularDocumento(<?php echo $doc['id']; ?>)" title="Anular">
                                            <i class="fas fa-ban"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Información Útil -->
        <div class="row mt-4 mb-4">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h6><i class="fas fa-info-circle text-primary"></i> Estados de Documentos</h6>
                        <ul class="small mb-0">
                            <li><strong>Borrador:</strong> Documento creado, no timbrado</li>
                            <li><strong>Timbrado:</strong> Timbre SII generado, listo para enviar</li>
                            <li><strong>Enviado:</strong> Enviado al SII, esperando respuesta</li>
                            <li><strong>Aceptado:</strong> Aceptado por el SII</li>
                            <li><strong>Rechazado:</strong> Rechazado por el SII</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h6><i class="fas fa-lightbulb text-warning"></i> Recomendaciones</h6>
                        <ul class="small mb-0">
                            <li>Verifica los datos antes de timbrar</li>
                            <li>Los documentos timbrados no se pueden editar</li>
                            <li>Envía los DTE al SII el mismo día</li>
                            <li>Mantén folios disponibles</li>
                            <li>Guarda los XML generados</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h6><i class="fas fa-headset text-success"></i> Soporte</h6>
                        <p class="small mb-2">¿Necesitas ayuda?</p>
                        <a href="https://www.sii.cl/servicios_online/1039-3151.html" target="_blank" class="btn btn-sm btn-outline-primary w-100 mb-2">
                            <i class="fas fa-book"></i> Documentación SII
                        </a>
                        <a href="folios.php" class="btn btn-sm btn-outline-secondary w-100">
                            <i class="fas fa-receipt"></i> Gestionar Folios
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center mb-4">
            <a href="../ventas/" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Volver a Ventas
            </a>
        </div>
    </div>

    <!-- Modal: Seleccionar Tipo de Documento -->
    <div class="modal fade" id="modalTiposDocumento" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-list"></i> Seleccionar Tipo de Documento</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <?php
                        $tipos_documentos->data_seek(0);
                        while ($tipo = $tipos_documentos->fetch_assoc()):
                        ?>
                        <div class="col-md-6">
                            <div class="card h-100" style="cursor: pointer;" onclick="abrirModalDocumento(<?php echo $tipo['codigo']; ?>)">
                                <div class="card-body">
                                    <h6>
                                        <strong><?php echo $tipo['codigo']; ?></strong> - <?php echo $tipo['nombre']; ?>
                                        <span class="tipo-documento-badge fase-<?php echo $tipo['fase_implementacion']; ?>">
                                            Fase <?php echo $tipo['fase_implementacion']; ?>
                                        </span>
                                    </h6>
                                    <p class="small text-muted mb-0"><?php echo $tipo['descripcion']; ?></p>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Incluir modales de cada tipo de documento -->
    <?php include 'modales/modal_factura_33.php'; ?>
    <?php include 'modales/modal_boleta_39.php'; ?>
    <?php include 'modales/modal_guia_despacho_52.php'; ?>
    <?php include 'modales/modal_nota_credito_61.php'; ?>
    <?php include 'modales/modal_nota_debito_56.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="../../assets/js/dte_common.js"></script>
    <script>
        function abrirModalDocumento(tipo) {
            // Cerrar modal de selección si está abierto
            const modalTipos = bootstrap.Modal.getInstance(document.getElementById('modalTiposDocumento'));
            if (modalTipos) modalTipos.hide();

            // Abrir modal específico según tipo
            const modalMap = {
                33: 'modalFactura33',
                39: 'modalBoleta39',
                52: 'modalGuiaDespacho52',
                61: 'modalNotaCredito61',
                56: 'modalNotaDebito56',
                34: 'modalFacturaExenta34',
                41: 'modalBoletaExenta41',
                110: 'modalFacturaExportacion110',
                111: 'modalNotaDebitoExportacion111',
                112: 'modalNotaCreditoExportacion112',
                46: 'modalFacturaCompra46',
                43: 'modalLiquidacionFactura43'
            };

            const modalId = modalMap[tipo];
            if (modalId) {
                const modal = new bootstrap.Modal(document.getElementById(modalId));
                modal.show();
            } else {
                alert('Tipo de documento no implementado aún: ' + tipo);
            }
        }

        function mostrarMasTipos() {
            const modal = new bootstrap.Modal(document.getElementById('modalTiposDocumento'));
            modal.show();
        }

        function verDocumento(id) {
            window.location.href = 'ver_documento.php?id=' + id;
        }

        function descargarPDF(id) {
            window.open('generar_pdf.php?id=' + id, '_blank');
        }

        function enviarSII(id) {
            if (confirm('¿Enviar este documento al SII?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="enviar_sii">
                    <input type="hidden" name="documento_id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function anularDocumento(id) {
            const motivo = prompt('Ingrese el motivo de anulación:');
            if (motivo && motivo.trim() !== '') {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="anular_documento">
                    <input type="hidden" name="documento_id" value="${id}">
                    <input type="hidden" name="motivo" value="${motivo}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function exportarExcel() {
            window.location.href = 'exportar_documentos.php?' + window.location.search.substring(1);
        }

        // Auto-refresh cada 5 minutos para actualizar estados
        setTimeout(() => {
            location.reload();
        }, 300000);
    </script>

    <!-- Footer -->
    <?php include '../../includes/footer.php'; ?>
</body>
</html>
