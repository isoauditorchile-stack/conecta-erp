<?php
/**
 * MÓDULO FINANZAS - Tesorería (Cash Management)
 * Gestión de caja, bancos y flujos de efectivo
 */
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
requireLogin();

$usuario_id = $_SESSION['usuario_id'];
$empresa_id = $_SESSION['empresa_id'];
$mensaje = '';

// CREAR MOVIMIENTO DE TESORERÍA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_movimiento'])) {
    $cuenta_bancaria_id = intval($_POST['cuenta_bancaria_id']);
    $tipo_movimiento = $_POST['tipo_movimiento'];
    $monto = floatval($_POST['monto']);
    $fecha = $_POST['fecha'];
    $descripcion = trim($_POST['descripcion']);
    $referencia = trim($_POST['referencia']);

    $stmt = $conn->prepare("INSERT INTO movimientos_tesoreria
        (empresa_id, cuenta_bancaria_id, tipo_movimiento, monto, fecha, descripcion, referencia, creado_por)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iisds ssi", $empresa_id, $cuenta_bancaria_id, $tipo_movimiento, $monto, $fecha, $descripcion, $referencia, $usuario_id);
    
    if ($stmt->execute()) {
        $movimiento_id = $conn->insert_id;
        
        // Actualizar saldo de cuenta bancaria
        $operador = $tipo_movimiento == 'ingreso' ? '+' : '-';
        $stmt2 = $conn->prepare("UPDATE cuentas_bancarias SET saldo_actual = saldo_actual $operador ? WHERE id = ? AND empresa_id = ?");
        $stmt2->bind_param("dii", $monto, $cuenta_bancaria_id, $empresa_id);
        $stmt2->execute();
        $stmt2->close();
        
        logAuditoria($conn, $usuario_id, $empresa_id, 'crear', 'movimientos_tesoreria', $movimiento_id, 'finanzas', "Movimiento de tesorería: $tipo_movimiento - " . formatearMoneda($monto));
        $mensaje = "Movimiento registrado exitosamente";
    }
    $stmt->close();
}

// Obtener estadísticas desde SQL
$stmt = $conn->prepare("SELECT
    COALESCE(SUM(saldo_actual), 0) as saldo_total,
    COUNT(*) as total_cuentas,
    COALESCE(SUM(CASE WHEN tipo_cuenta = 'corriente' THEN saldo_actual ELSE 0 END), 0) as saldo_corriente,
    COALESCE(SUM(CASE WHEN tipo_cuenta = 'vista' THEN saldo_actual ELSE 0 END), 0) as saldo_vista
    FROM cuentas_bancarias WHERE empresa_id = ? AND estado = 'activa'");
$stmt->bind_param("i", $empresa_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Tesorería - CONECTA ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root { --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .sidebar { position: fixed; left: 0; top: 0; width: 280px; height: 100vh; background: var(--primary-gradient); color: white; padding: 20px; overflow-y: auto; }
        .content { margin-left: 280px; padding: 30px; }
        .stats-card { background: white; border-radius: 12px; padding: 25px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid #667eea; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h3><i class="fas fa-university"></i> Tesorería</h3>
        <a href="../../user/dashboard.php" class="btn btn-light btn-sm w-100 mt-3"><i class="fas fa-arrow-left"></i> Dashboard</a>
    </div>
    <div class="content">
        <h2><i class="fas fa-university text-primary"></i> Tesorería - Cash Management</h2>
        
        <?php if ($mensaje): ?><div class="alert alert-success"><?php echo $mensaje; ?></div><?php endif; ?>
        
        <div class="row">
            <div class="col-md-3"><div class="stats-card"><h6 style="color:#667eea">Saldo Total</h6><h3><?php echo formatearMoneda($stats['saldo_total']); ?></h3></div></div>
            <div class="col-md-3"><div class="stats-card"><h6 style="color:#48bb78">Total Cuentas</h6><h3><?php echo $stats['total_cuentas']; ?></h3></div></div>
            <div class="col-md-3"><div class="stats-card"><h6 style="color:#ed8936">Cta. Corriente</h6><h3><?php echo formatearMoneda($stats['saldo_corriente']); ?></h3></div></div>
            <div class="col-md-3"><div class="stats-card"><h6 style="color:#9f7aea">Cta. Vista</h6><h3><?php echo formatearMoneda($stats['saldo_vista']); ?></h3></div></div>
        </div>

        <div class="card">
            <div class="card-header"><h5><i class="fas fa-list"></i> Cuentas Bancarias</h5></div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead><tr><th>Banco</th><th>Cuenta</th><th>Tipo</th><th>Saldo</th><th>Estado</th></tr></thead>
                    <tbody>
                        <?php
                        $stmt = $conn->prepare("SELECT banco, numero_cuenta, tipo_cuenta, saldo_actual, estado FROM cuentas_bancarias WHERE empresa_id = ? ORDER BY banco");
                        $stmt->bind_param("i", $empresa_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        while ($row = $result->fetch_assoc()):
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['banco']); ?></td>
                            <td><?php echo htmlspecialchars($row['numero_cuenta']); ?></td>
                            <td><?php echo ucfirst($row['tipo_cuenta']); ?></td>
                            <td><?php echo formatearMoneda($row['saldo_actual']); ?></td>
                            <td><span class="badge bg-<?php echo $row['estado']=='activa'?'success':'secondary'; ?>"><?php echo ucfirst($row['estado']); ?></span></td>
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
