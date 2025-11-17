<?php
/**
 * MÓDULO FINANZAS (FI) - Cuentas por Cobrar (AR)
 * Gestión completa de cuentas por cobrar
 * Sistema REAL - Nivel Empresarial
 */

session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

requireLogin();

$usuario_id = $_SESSION['usuario_id'];
$empresa_id = $_SESSION['empresa_id'];

$mensaje = '';
$tipo_mensaje = '';

// =====================================================
// CREAR CUENTA POR COBRAR
// =====================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_cuenta'])) {
    $cliente_id = intval($_POST['cliente_id']);
    $factura_id = isset($_POST['factura_id']) && $_POST['factura_id'] !== '' ? intval($_POST['factura_id']) : NULL;
    $numero_documento = trim($_POST['numero_documento']);
    $tipo_documento = $_POST['tipo_documento'];
    $fecha_emision = $_POST['fecha_emision'];
    $fecha_vencimiento = $_POST['fecha_vencimiento'];
    $monto_total = floatval($_POST['monto_total']);
    $descripcion = trim($_POST['descripcion']);
    $condicion_pago = trim($_POST['condicion_pago']);

    // Validaciones
    if (empty($cliente_id) || empty($numero_documento) || $monto_total <= 0) {
        $mensaje = "Todos los campos obligatorios deben estar completos";
        $tipo_mensaje = "danger";
    } else {
        $stmt = $conn->prepare("INSERT INTO cuentas_por_cobrar
            (empresa_id, cliente_id, factura_id, numero_documento, tipo_documento,
             fecha_emision, fecha_vencimiento, monto_total, saldo_pendiente,
             descripcion, condicion_pago, estado, creado_por)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendiente', ?)");

        $stmt->bind_param("iiissssddssi",
            $empresa_id, $cliente_id, $factura_id, $numero_documento, $tipo_documento,
            $fecha_emision, $fecha_vencimiento, $monto_total, $monto_total,
            $descripcion, $condicion_pago, $usuario_id
        );

        if ($stmt->execute()) {
            $cuenta_id = $conn->insert_id;

            // Registrar auditoría
            logAuditoria($conn, $usuario_id, $empresa_id, 'crear', 'cuentas_por_cobrar', $cuenta_id,
                'finanzas', "Cuenta por cobrar creada: {$numero_documento} - " . formatearMoneda($monto_total));

            $mensaje = "Cuenta por cobrar creada exitosamente";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al crear cuenta por cobrar: " . $stmt->error;
            $tipo_mensaje = "danger";
        }
        $stmt->close();
    }
}

// =====================================================
// REGISTRAR COBRO / PAGO
// =====================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar_cobro'])) {
    $cuenta_id = intval($_POST['cuenta_id']);
    $monto_cobro = floatval($_POST['monto_cobro']);
    $fecha_cobro = $_POST['fecha_cobro'];
    $forma_pago = $_POST['forma_pago'];
    $referencia = trim($_POST['referencia']);
    $cuenta_bancaria_id = isset($_POST['cuenta_bancaria_id']) && $_POST['cuenta_bancaria_id'] !== '' ? intval($_POST['cuenta_bancaria_id']) : NULL;

    if ($monto_cobro <= 0) {
        $mensaje = "El monto del cobro debe ser mayor a cero";
        $tipo_mensaje = "danger";
    } else {
        // Obtener saldo actual
        $stmt = $conn->prepare("SELECT saldo_pendiente, monto_total FROM cuentas_por_cobrar WHERE id = ? AND empresa_id = ?");
        $stmt->bind_param("ii", $cuenta_id, $empresa_id);
        $stmt->execute();
        $cuenta = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$cuenta) {
            $mensaje = "Cuenta no encontrada";
            $tipo_mensaje = "danger";
        } elseif ($monto_cobro > $cuenta['saldo_pendiente']) {
            $mensaje = "El monto del cobro no puede ser mayor al saldo pendiente";
            $tipo_mensaje = "danger";
        } else {
            // Registrar cobro
            $stmt = $conn->prepare("INSERT INTO cobros
                (empresa_id, cuenta_cobrar_id, monto, fecha_cobro, forma_pago,
                 referencia, cuenta_bancaria_id, creado_por)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

            $stmt->bind_param("iidsssii",
                $empresa_id, $cuenta_id, $monto_cobro, $fecha_cobro, $forma_pago,
                $referencia, $cuenta_bancaria_id, $usuario_id
            );

            if ($stmt->execute()) {
                $cobro_id = $conn->insert_id;

                // Actualizar saldo de la cuenta
                $nuevo_saldo = $cuenta['saldo_pendiente'] - $monto_cobro;
                $nuevo_estado = $nuevo_saldo == 0 ? 'cobrada' : 'cobrada_parcial';

                $stmt2 = $conn->prepare("UPDATE cuentas_por_cobrar
                    SET saldo_pendiente = ?, estado = ?, fecha_modificacion = NOW(), modificado_por = ?
                    WHERE id = ?");
                $stmt2->bind_param("dsii", $nuevo_saldo, $nuevo_estado, $usuario_id, $cuenta_id);
                $stmt2->execute();
                $stmt2->close();

                // Si hay cuenta bancaria, actualizar saldo
                if ($cuenta_bancaria_id) {
                    $stmt3 = $conn->prepare("UPDATE cuentas_bancarias
                        SET saldo_actual = saldo_actual + ?
                        WHERE id = ? AND empresa_id = ?");
                    $stmt3->bind_param("dii", $monto_cobro, $cuenta_bancaria_id, $empresa_id);
                    $stmt3->execute();
                    $stmt3->close();
                }

                logAuditoria($conn, $usuario_id, $empresa_id, 'crear', 'cobros', $cobro_id,
                    'finanzas', "Cobro registrado: " . formatearMoneda($monto_cobro));

                $mensaje = "Cobro registrado exitosamente";
                $tipo_mensaje = "success";
            } else {
                $mensaje = "Error al registrar cobro: " . $stmt->error;
                $tipo_mensaje = "danger";
            }
            $stmt->close();
        }
    }
}

// Obtener estadísticas desde SQL
$stmt = $conn->prepare("SELECT
    COUNT(*) as total_cuentas,
    COALESCE(SUM(saldo_pendiente), 0) as saldo_total,
    COALESCE(SUM(CASE WHEN estado = 'vencida' THEN saldo_pendiente ELSE 0 END), 0) as saldo_vencido,
    COALESCE(SUM(CASE WHEN estado = 'vencida' THEN 1 ELSE 0 END), 0) as cuentas_vencidas
    FROM cuentas_por_cobrar
    WHERE empresa_id = ? AND estado IN ('pendiente', 'cobrada_parcial', 'vencida')");
$stmt->bind_param("i", $empresa_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cuentas por Cobrar - CONECTA ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <style>
        :root { --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f7fa; }
        .sidebar { position: fixed; left: 0; top: 0; width: 280px; height: 100vh; background: var(--primary-gradient); color: white; padding: 20px; overflow-y: auto; z-index: 1000; }
        .content { margin-left: 280px; padding: 30px; }
        .stats-card { background: white; border-radius: 12px; padding: 25px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid; }
        .stats-card.blue { border-left-color: #667eea; }
        .stats-card.green { border-left-color: #48bb78; }
        .stats-card.red { border-left-color: #f56565; }
        .estado-badge { padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .estado-pendiente { background: #fef3c7; color: #92400e; }
        .estado-cobrada { background: #d1fae5; color: #065f46; }
        .estado-vencida { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h3 class="mb-4"><i class="fas fa-money-bill-wave"></i> Cuentas por Cobrar</h3>
        <a href="../../user/dashboard.php" class="btn btn-light btn-sm w-100 mb-3">
            <i class="fas fa-arrow-left"></i> Volver al Dashboard
        </a>
    </div>

    <div class="content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-money-bill-wave text-primary"></i> Cuentas por Cobrar (AR)</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrear">
                <i class="fas fa-plus"></i> Nueva Cuenta
            </button>
        </div>

        <?php if ($mensaje): ?>
        <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show">
            <?php echo $mensaje; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-3"><div class="stats-card blue"><h6 style="color:#667eea">Total Cuentas</h6><h3><?php echo number_format($stats['total_cuentas']); ?></h3></div></div>
            <div class="col-md-3"><div class="stats-card green"><h6 style="color:#48bb78">Saldo Pendiente</h6><h3><?php echo formatearMoneda($stats['saldo_total']); ?></h3></div></div>
            <div class="col-md-3"><div class="stats-card red"><h6 style="color:#f56565">Saldo Vencido</h6><h3><?php echo formatearMoneda($stats['saldo_vencido']); ?></h3></div></div>
            <div class="col-md-3"><div class="stats-card blue"><h6 style="color:#ed8936">Cuentas Vencidas</h6><h3><?php echo number_format($stats['cuentas_vencidas']); ?></h3></div></div>
        </div>

        <div class="card">
            <div class="card-body">
                <table id="tablaCuentas" class="table table-striped">
                    <thead>
                        <tr>
                            <th>Documento</th>
                            <th>Cliente</th>
                            <th>Vencimiento</th>
                            <th>Monto Total</th>
                            <th>Saldo</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $conn->prepare("SELECT
                            cc.id, cc.numero_documento, c.razon_social,
                            cc.fecha_vencimiento, cc.monto_total, cc.saldo_pendiente, cc.estado
                            FROM cuentas_por_cobrar cc
                            INNER JOIN clientes c ON cc.cliente_id = c.id
                            WHERE cc.empresa_id = ?
                            ORDER BY cc.fecha_vencimiento");
                        $stmt->bind_param("i", $empresa_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        while ($row = $result->fetch_assoc()):
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['numero_documento']); ?></td>
                            <td><?php echo htmlspecialchars($row['razon_social']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($row['fecha_vencimiento'])); ?></td>
                            <td><?php echo formatearMoneda($row['monto_total']); ?></td>
                            <td><?php echo formatearMoneda($row['saldo_pendiente']); ?></td>
                            <td><span class="estado-badge estado-<?php echo $row['estado']; ?>"><?php echo ucfirst($row['estado']); ?></span></td>
                            <td>
                                <?php if ($row['saldo_pendiente'] > 0): ?>
                                <button class="btn btn-success btn-sm" onclick="cobrar(<?php echo $row['id']; ?>, <?php echo $row['saldo_pendiente']; ?>)">Cobrar</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; $stmt->close(); ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#tablaCuentas').DataTable({
                language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' },
                order: [[2, 'asc']]
            });
        });
    </script>

    <!-- Footer Profesional -->
    <?php include '../includes/footer.php'; ?>

</body>
</html>
