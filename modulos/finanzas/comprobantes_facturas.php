<?php
/**
 * MÓDULO FINANZAS - Comprobantes y Facturas
 * Gestión de documentos tributarios electrónicos (DTE)
 */
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
requireLogin();

$usuario_id = $_SESSION['usuario_id'];
$empresa_id = $_SESSION['empresa_id'];
$mensaje = '';

// CREAR FACTURA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_factura'])) {
    $cliente_id = intval($_POST['cliente_id']);
    $tipo_documento = $_POST['tipo_documento'];
    $fecha_emision = $_POST['fecha_emision'];
    $condicion_pago = $_POST['condicion_pago'];

    $conn->begin_transaction();
    try {
        //Obtener siguiente número de factura
        $stmt = $conn->prepare("SELECT COALESCE(MAX(CAST(SUBSTRING(numero_factura, 4) AS UNSIGNED)), 0) + 1 as siguiente FROM facturas WHERE empresa_id = ? AND tipo_documento = ?");
        $stmt->bind_param("is", $empresa_id, $tipo_documento);
        $stmt->execute();
        $siguiente = $stmt->get_result()->fetch_assoc()['siguiente'];
        $numero_factura = sprintf("%s%06d", substr($tipo_documento, 0, 3), $siguiente);
        $stmt->close();

        // Insertar factura
        $stmt = $conn->prepare("INSERT INTO facturas
            (empresa_id, cliente_id, numero_factura, tipo_documento, fecha_emision, condicion_pago, estado, creado_por)
            VALUES (?, ?, ?, ?, ?, ?, 'borrador', ?)");
        $stmt->bind_param("iissssi", $empresa_id, $cliente_id, $numero_factura, $tipo_documento, $fecha_emision, $condicion_pago, $usuario_id);
        $stmt->execute();
        $factura_id = $conn->insert_id;
        $stmt->close();

        $conn->commit();
        logAuditoria($conn, $usuario_id, $empresa_id, 'crear', 'facturas', $factura_id, 'finanzas', "Factura creada: $numero_factura");
        $mensaje = "Factura $numero_factura creada exitosamente";
    } catch (Exception $e) {
        $conn->rollback();
        $mensaje = "Error: " . $e->getMessage();
    }
}

// Estadísticas desde SQL
$stmt = $conn->prepare("SELECT
    COUNT(*) as total_facturas,
    COALESCE(SUM(CASE WHEN estado='emitida' OR estado='pagada' THEN total ELSE 0 END), 0) as monto_total,
    COALESCE(SUM(CASE WHEN estado='emitida' THEN total ELSE 0 END), 0) as pendiente_pago,
    COALESCE(SUM(CASE WHEN estado='borrador' THEN 1 ELSE 0 END), 0) as borradores
    FROM facturas WHERE empresa_id = ? AND MONTH(fecha_emision) = MONTH(CURDATE())");
$stmt->bind_param("i", $empresa_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobantes y Facturas - CONECTA ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .sidebar { position: fixed; left: 0; top: 0; width: 280px; height: 100vh; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; }
        .content { margin-left: 280px; padding: 30px; }
        .stats-card { background: white; border-radius: 12px; padding: 25px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid #667eea; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h3><i class="fas fa-file-invoice"></i> Facturas</h3>
        <a href="../../user/dashboard.php" class="btn btn-light btn-sm w-100 mt-3"><i class="fas fa-arrow-left"></i> Dashboard</a>
    </div>
    <div class="content">
        <h2><i class="fas fa-file-invoice text-primary"></i> Comprobantes y Facturas (DTE)</h2>
        
        <?php if ($mensaje): ?><div class="alert alert-success"><?php echo $mensaje; ?></div><?php endif; ?>
        
        <div class="row">
            <div class="col-md-3"><div class="stats-card"><h6 style="color:#667eea">Total Mes</h6><h3><?php echo $stats['total_facturas']; ?></h3></div></div>
            <div class="col-md-3"><div class="stats-card"><h6 style="color:#48bb78">Monto Total</h6><h3><?php echo formatearMoneda($stats['monto_total']); ?></h3></div></div>
            <div class="col-md-3"><div class="stats-card"><h6 style="color:#ed8936">Pend. Pago</h6><h3><?php echo formatearMoneda($stats['pendiente_pago']); ?></h3></div></div>
            <div class="col-md-3"><div class="stats-card"><h6 style="color:#9f7aea">Borradores</h6><h3><?php echo $stats['borradores']; ?></h3></div></div>
        </div>

        <div class="card">
            <div class="card-header"><h5><i class="fas fa-list"></i> Facturas Recientes</h5></div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead><tr><th>Número</th><th>Cliente</th><th>Fecha</th><th>Total</th><th>Estado</th><th>DTE</th></tr></thead>
                    <tbody>
                        <?php
                        $stmt = $conn->prepare("SELECT f.numero_factura, c.razon_social, f.fecha_emision, f.total, f.estado, f.folio_sii
                            FROM facturas f
                            INNER JOIN clientes c ON f.cliente_id = c.id
                            WHERE f.empresa_id = ?
                            ORDER BY f.fecha_emision DESC LIMIT 50");
                        $stmt->bind_param("i", $empresa_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        while ($row = $result->fetch_assoc()):
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['numero_factura']); ?></td>
                            <td><?php echo htmlspecialchars($row['razon_social']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($row['fecha_emision'])); ?></td>
                            <td><?php echo formatearMoneda($row['total']); ?></td>
                            <td><span class="badge bg-<?php echo $row['estado']=='pagada'?'success':'warning'; ?>"><?php echo ucfirst($row['estado']); ?></span></td>
                            <td><?php echo $row['folio_sii'] ? '<span class="badge bg-success">Timbrado</span>' : '<span class="badge bg-secondary">Sin timbre</span>'; ?></td>
                        </tr>
                        <?php endwhile; $stmt->close(); ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
