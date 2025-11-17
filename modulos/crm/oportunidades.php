<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
requireLogin();
$usuario_id = $_SESSION['usuario_id'];
$empresa_id = $_SESSION['empresa_id'];
$mensaje = '';
$tipo_mensaje = '';

// CREAR OPORTUNIDAD
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_oportunidad'])) {
    $cliente_id = intval($_POST['cliente_id']);
    $nombre = trim($_POST['nombre']);
    $valor_estimado = floatval($_POST['valor_estimado']);
    $probabilidad = intval($_POST['probabilidad']);
    $etapa = $_POST['etapa'];
    $fecha_cierre_estimada = $_POST['fecha_cierre_estimada'];

    if (empty($nombre) || $valor_estimado <= 0) {
        $mensaje = "Todos los campos obligatorios deben estar completos";
        $tipo_mensaje = "danger";
    } else {
        $stmt = $conn->prepare("INSERT INTO oportunidades 
            (empresa_id, cliente_id, nombre, valor_estimado, probabilidad, etapa, fecha_cierre_estimada, vendedor_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iisdissi", $empresa_id, $cliente_id, $nombre, $valor_estimado, $probabilidad, $etapa, $fecha_cierre_estimada, $usuario_id);
        
        if ($stmt->execute()) {
            $oportunidad_id = $conn->insert_id;
            logAuditoria($conn, $usuario_id, $empresa_id, 'crear', 'oportunidades', $oportunidad_id, 'crm', "Oportunidad: $nombre - " . formatearMoneda($valor_estimado));
            $mensaje = "Oportunidad creada exitosamente";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al crear oportunidad: " . $stmt->error;
            $tipo_mensaje = "danger";
        }
        $stmt->close();
    }
}

// ACTUALIZAR ETAPA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_etapa'])) {
    $oportunidad_id = intval($_POST['oportunidad_id']);
    $nueva_etapa = $_POST['nueva_etapa'];

    $stmt = $conn->prepare("UPDATE oportunidades SET etapa = ? WHERE id = ? AND empresa_id = ?");
    $stmt->bind_param("sii", $nueva_etapa, $oportunidad_id, $empresa_id);
    
    if ($stmt->execute()) {
        // Si se cierra como ganada, registrar fecha
        if ($nueva_etapa === 'cerrado_ganado') {
            $stmt2 = $conn->prepare("UPDATE oportunidades SET fecha_cierre_real = CURDATE() WHERE id = ?");
            $stmt2->bind_param("i", $oportunidad_id);
            $stmt2->execute();
            $stmt2->close();
        }
        
        logAuditoria($conn, $usuario_id, $empresa_id, 'editar', 'oportunidades', $oportunidad_id, 'crm', "Etapa actualizada a: $nueva_etapa");
        $mensaje = "Etapa actualizada exitosamente";
        $tipo_mensaje = "success";
    }
    $stmt->close();
}

// Obtener estadísticas REALES desde SQL
$stmt = $conn->prepare("SELECT
    COUNT(*) as total_oportunidades,
    COALESCE(SUM(valor_estimado), 0) as valor_total,
    COALESCE(SUM(valor_estimado * probabilidad / 100), 0) as valor_ponderado,
    COUNT(CASE WHEN etapa = 'cerrado_ganado' THEN 1 END) as ganadas,
    COUNT(CASE WHEN etapa = 'cerrado_perdido' THEN 1 END) as perdidas
    FROM oportunidades 
    WHERE empresa_id = ? AND MONTH(fecha_creacion) = MONTH(CURDATE())");
$stmt->bind_param("i", $empresa_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Calcular tasa de conversión
$total_cerradas = $stats['ganadas'] + $stats['perdidas'];
$tasa_conversion = $total_cerradas > 0 ? ($stats['ganadas'] / $total_cerradas * 100) : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Oportunidades - CONECTA ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <style>
        :root { --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f7fa; }
        .sidebar { position: fixed; left: 0; top: 0; width: 280px; height: 100vh; background: var(--primary-gradient); color: white; padding: 20px; overflow-y: auto; z-index: 1000; }
        .content { margin-left: 280px; padding: 30px; }
        .stats-card { background: white; border-radius: 12px; padding: 25px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid #667eea; }
        .etapa-badge { padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .etapa-prospecto { background: #e0e7ff; color: #3730a3; }
        .etapa-calificacion { background: #dbeafe; color: #1e40af; }
        .etapa-propuesta { background: #fef3c7; color: #92400e; }
        .etapa-negociacion { background: #fed7aa; color: #9a3412; }
        .etapa-cerrado_ganado { background: #d1fae5; color: #065f46; }
        .etapa-cerrado_perdido { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h3 class="mb-4"><i class="fas fa-bullseye"></i> Oportunidades</h3>
        <a href="../../user/dashboard.php" class="btn btn-light btn-sm w-100 mb-3">
            <i class="fas fa-arrow-left"></i> Volver al Dashboard
        </a>
        <hr style="border-color: rgba(255,255,255,0.2);">
        <div class="mt-3">
            <p class="mb-1" style="opacity: 0.8; font-size: 13px;">Módulo</p>
            <h6>CRM - Oportunidades</h6>
        </div>
    </div>

    <div class="content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-bullseye text-primary"></i> Gestión de Oportunidades</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrear">
                <i class="fas fa-plus"></i> Nueva Oportunidad
            </button>
        </div>

        <?php if ($mensaje): ?>
        <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show">
            <?php echo $mensaje; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="row">
            <div class="col-md-3">
                <div class="stats-card">
                    <h6 style="color: #667eea; margin-bottom: 10px;">Total Oportunidades</h6>
                    <h3 class="mb-0"><?php echo number_format($stats['total_oportunidades']); ?></h3>
                    <small class="text-muted">Este mes</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <h6 style="color: #48bb78; margin-bottom: 10px;">Valor Total</h6>
                    <h3 class="mb-0"><?php echo formatearMoneda($stats['valor_total']); ?></h3>
                    <small class="text-muted">Pipeline completo</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <h6 style="color: #ed8936; margin-bottom: 10px;">Valor Ponderado</h6>
                    <h3 class="mb-0"><?php echo formatearMoneda($stats['valor_ponderado']); ?></h3>
                    <small class="text-muted">Por probabilidad</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <h6 style="color: #9f7aea; margin-bottom: 10px;">Tasa Conversión</h6>
                    <h3 class="mb-0"><?php echo number_format($tasa_conversion, 1); ?>%</h3>
                    <small class="text-muted"><?php echo $stats['ganadas']; ?> ganadas / <?php echo $total_cerradas; ?> totales</small>
                </div>
            </div>
        </div>

        <!-- Tabla de Oportunidades -->
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-list"></i> Pipeline de Ventas</h5>
            </div>
            <div class="card-body">
                <table id="tablaOportunidades" class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Cliente</th>
                            <th>Valor</th>
                            <th>Probabilidad</th>
                            <th>Valor Ponderado</th>
                            <th>Etapa</th>
                            <th>Cierre Estimado</th>
                            <th>Vendedor</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $conn->prepare("SELECT
                            o.id, o.nombre, c.razon_social as cliente,
                            o.valor_estimado, o.probabilidad,
                            (o.valor_estimado * o.probabilidad / 100) as valor_ponderado,
                            o.etapa, o.fecha_cierre_estimada,
                            u.nombre_completo as vendedor
                            FROM oportunidades o
                            INNER JOIN clientes c ON o.cliente_id = c.id
                            LEFT JOIN usuarios u ON o.vendedor_id = u.id
                            WHERE o.empresa_id = ?
                            ORDER BY o.valor_estimado DESC");
                        $stmt->bind_param("i", $empresa_id);
                        $stmt->execute();
                        $result = $stmt->get_result();

                        while ($row = $result->fetch_assoc()):
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($row['nombre']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['cliente']); ?></td>
                            <td><?php echo formatearMoneda($row['valor_estimado']); ?></td>
                            <td>
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar bg-success" style="width: <?php echo $row['probabilidad']; ?>%">
                                        <?php echo $row['probabilidad']; ?>%
                                    </div>
                                </div>
                            </td>
                            <td><?php echo formatearMoneda($row['valor_ponderado']); ?></td>
                            <td>
                                <span class="etapa-badge etapa-<?php echo $row['etapa']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $row['etapa'])); ?>
                                </span>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($row['fecha_cierre_estimada'])); ?></td>
                            <td><?php echo htmlspecialchars($row['vendedor']); ?></td>
                            <td>
                                <button class="btn btn-sm btn-info" onclick="verDetalle(<?php echo $row['id']; ?>)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endwhile;
                        $stmt->close();
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Crear Oportunidad -->
    <div class="modal fade" id="modalCrear" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus"></i> Nueva Oportunidad</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Nombre de la Oportunidad *</label>
                                <input type="text" name="nombre" class="form-control" required placeholder="Ej: Venta Software ERP">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Cliente *</label>
                                <select name="cliente_id" class="form-select" required>
                                    <option value="">Seleccione cliente</option>
                                    <?php
                                    $stmt = $conn->prepare("SELECT id, razon_social FROM clientes WHERE empresa_id = ? AND estado = 'activo' ORDER BY razon_social");
                                    $stmt->bind_param("i", $empresa_id);
                                    $stmt->execute();
                                    $clientes = $stmt->get_result();
                                    while ($cliente = $clientes->fetch_assoc()):
                                    ?>
                                    <option value="<?php echo $cliente['id']; ?>">
                                        <?php echo htmlspecialchars($cliente['razon_social']); ?>
                                    </option>
                                    <?php endwhile;
                                    $stmt->close();
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Valor Estimado *</label>
                                <input type="number" name="valor_estimado" class="form-control" step="0.01" min="0" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Probabilidad (%)</label>
                                <input type="number" name="probabilidad" class="form-control" min="0" max="100" value="50">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Etapa</label>
                                <select name="etapa" class="form-select">
                                    <option value="prospecto">Prospecto</option>
                                    <option value="calificacion">Calificación</option>
                                    <option value="propuesta">Propuesta</option>
                                    <option value="negociacion">Negociación</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Cierre Estimado</label>
                                <input type="date" name="fecha_cierre_estimada" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="crear_oportunidad" class="btn btn-primary">
                            <i class="fas fa-save"></i> Crear Oportunidad
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#tablaOportunidades').DataTable({
                language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' },
                order: [[2, 'desc']], // Ordenar por valor
                pageLength: 25
            });
        });

        function verDetalle(id) {
            window.location.href = '?detalle=' + id;
        }
    </script>

    <!-- Footer Profesional -->
    <?php include '../includes/footer.php'; ?>

</body>
</html>
