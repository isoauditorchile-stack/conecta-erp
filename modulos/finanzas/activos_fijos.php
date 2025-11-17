<?php
/**
 * MÓDULO FINANZAS - Activos Fijos (Fixed Assets)
 * Gestión de activos fijos y depreciación
 */
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
requireLogin();

$usuario_id = $_SESSION['usuario_id'];
$empresa_id = $_SESSION['empresa_id'];
$mensaje = '';

// CREAR ACTIVO FIJO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_activo'])) {
    $codigo = trim($_POST['codigo']);
    $nombre = trim($_POST['nombre']);
    $categoria_id = intval($_POST['categoria_id']);
    $fecha_adquisicion = $_POST['fecha_adquisicion'];
    $valor_adquisicion = floatval($_POST['valor_adquisicion']);
    $vida_util_anos = intval($_POST['vida_util_anos']);
    $metodo_depreciacion = $_POST['metodo_depreciacion'];
    $ubicacion = trim($_POST['ubicacion']);

    $stmt = $conn->prepare("INSERT INTO activos_fijos
        (empresa_id, codigo, nombre, categoria_id, fecha_adquisicion, valor_adquisicion,
         vida_util_anos, metodo_depreciacion, ubicacion, creado_por)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issisdissi", $empresa_id, $codigo, $nombre, $categoria_id, $fecha_adquisicion,
        $valor_adquisicion, $vida_util_anos, $metodo_depreciacion, $ubicacion, $usuario_id);
    
    if ($stmt->execute()) {
        $activo_id = $conn->insert_id;
        logAuditoria($conn, $usuario_id, $empresa_id, 'crear', 'activos_fijos', $activo_id, 'finanzas', "Activo fijo creado: $codigo - $nombre");
        $mensaje = "Activo fijo creado exitosamente";
    }
    $stmt->close();
}

// Estadísticas desde SQL
$stmt = $conn->prepare("SELECT
    COUNT(*) as total_activos,
    COALESCE(SUM(valor_adquisicion), 0) as valor_total,
    COALESCE(SUM(depreciacion_acumulada), 0) as depreciacion_total,
    COALESCE(SUM(valor_adquisicion - depreciacion_acumulada), 0) as valor_neto
    FROM activos_fijos WHERE empresa_id = ? AND estado = 'activo'");
$stmt->bind_param("i", $empresa_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Activos Fijos - CONECTA ERP</title>
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
        <h3><i class="fas fa-building"></i> Activos Fijos</h3>
        <a href="../../user/dashboard.php" class="btn btn-light btn-sm w-100 mt-3"><i class="fas fa-arrow-left"></i> Dashboard</a>
    </div>
    <div class="content">
        <h2><i class="fas fa-building text-primary"></i> Activos Fijos (FA)</h2>
        
        <?php if ($mensaje): ?><div class="alert alert-success"><?php echo $mensaje; ?></div><?php endif; ?>
        
        <div class="row">
            <div class="col-md-3"><div class="stats-card"><h6 style="color:#667eea">Total Activos</h6><h3><?php echo $stats['total_activos']; ?></h3></div></div>
            <div class="col-md-3"><div class="stats-card"><h6 style="color:#48bb78">Valor Adquisición</h6><h3><?php echo formatearMoneda($stats['valor_total']); ?></h3></div></div>
            <div class="col-md-3"><div class="stats-card"><h6 style="color:#ed8936">Depreciación</h6><h3><?php echo formatearMoneda($stats['depreciacion_total']); ?></h3></div></div>
            <div class="col-md-3"><div class="stats-card"><h6 style="color:#9f7aea">Valor Neto</h6><h3><?php echo formatearMoneda($stats['valor_neto']); ?></h3></div></div>
        </div>

        <div class="card">
            <div class="card-header"><h5><i class="fas fa-list"></i> Listado de Activos Fijos</h5></div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead><tr><th>Código</th><th>Nombre</th><th>Fecha Adq.</th><th>Valor</th><th>Depreciación</th><th>Valor Neto</th><th>Estado</th></tr></thead>
                    <tbody>
                        <?php
                        $stmt = $conn->prepare("SELECT codigo, nombre, fecha_adquisicion, valor_adquisicion, depreciacion_acumulada, estado FROM activos_fijos WHERE empresa_id = ? ORDER BY codigo");
                        $stmt->bind_param("i", $empresa_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        while ($row = $result->fetch_assoc()):
                            $valor_neto = $row['valor_adquisicion'] - $row['depreciacion_acumulada'];
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['codigo']); ?></td>
                            <td><?php echo htmlspecialchars($row['nombre']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($row['fecha_adquisicion'])); ?></td>
                            <td><?php echo formatearMoneda($row['valor_adquisicion']); ?></td>
                            <td><?php echo formatearMoneda($row['depreciacion_acumulada']); ?></td>
                            <td><?php echo formatearMoneda($valor_neto); ?></td>
                            <td><span class="badge bg-<?php echo $row['estado']=='activo'?'success':'secondary'; ?>"><?php echo ucfirst($row['estado']); ?></span></td>
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
