<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
requireLogin();
$usuario_id = $_SESSION['usuario_id'];
$empresa_id = $_SESSION['empresa_id'];

$stmt = $conn->prepare("SELECT
    COALESCE(SUM(monto_presupuestado), 0) as presupuesto_total,
    COALESCE(SUM(monto_ejecutado), 0) as ejecutado_total,
    COUNT(*) as total_presupuestos
    FROM presupuestos WHERE empresa_id = ? AND ano = YEAR(CURDATE())");
$stmt->bind_param("i", $empresa_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Presupuestos - CONECTA ERP</title>
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
        <h3><i class="fas fa-calculator"></i> Presupuestos</h3>
        <a href="../../user/dashboard.php" class="btn btn-light btn-sm w-100 mt-3"><i class="fas fa-arrow-left"></i> Dashboard</a>
    </div>
    <div class="content">
        <h2><i class="fas fa-calculator text-primary"></i> Gestión de Presupuestos</h2>
        <div class="row">
            <div class="col-md-4"><div class="stats-card"><h6 style="color:#667eea">Presupuesto Total</h6><h3><?php echo formatearMoneda($stats['presupuesto_total']); ?></h3></div></div>
            <div class="col-md-4"><div class="stats-card"><h6 style="color:#48bb78">Ejecutado</h6><h3><?php echo formatearMoneda($stats['ejecutado_total']); ?></h3></div></div>
            <div class="col-md-4"><div class="stats-card"><h6 style="color:#ed8936">Disponible</h6><h3><?php echo formatearMoneda($stats['presupuesto_total'] - $stats['ejecutado_total']); ?></h3></div></div>
        </div>
        <div class="card">
            <div class="card-header"><h5><i class="fas fa-list"></i> Presupuestos <?php echo date('Y'); ?></h5></div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead><tr><th>Centro Costo</th><th>Presupuestado</th><th>Ejecutado</th><th>%</th></tr></thead>
                    <tbody>
                        <?php
                        $stmt = $conn->prepare("SELECT cc.nombre, p.monto_presupuestado, p.monto_ejecutado FROM presupuestos p INNER JOIN centros_costo cc ON p.centro_costo_id = cc.id WHERE p.empresa_id = ? AND p.ano = YEAR(CURDATE())");
                        $stmt->bind_param("i", $empresa_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        while ($row = $result->fetch_assoc()):
                            $porcentaje = $row['monto_presupuestado'] > 0 ? ($row['monto_ejecutado'] / $row['monto_presupuestado']) * 100 : 0;
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['nombre']); ?></td>
                            <td><?php echo formatearMoneda($row['monto_presupuestado']); ?></td>
                            <td><?php echo formatearMoneda($row['monto_ejecutado']); ?></td>
                            <td><span class="badge bg-<?php echo $porcentaje>90?'danger':($porcentaje>70?'warning':'success'); ?>"><?php echo number_format($porcentaje, 1); ?>%</span></td>
                        </tr>
                        <?php endwhile; $stmt->close(); ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Footer Profesional -->
    <?php include '../includes/footer.php'; ?>

</body>
</html>
