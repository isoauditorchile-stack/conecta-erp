<?php
/**
 * CONECTA ERP - Confirmación de Pago
 * Página que muestra el resultado del proceso de pago
 */

require_once __DIR__ . '/../includes/config.php';

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

if (!isset($_SESSION['payment_success'])) {
    header('Location: /user/dashboard_user.php');
    exit;
}

$db = Database::getInstance();
$user_id = $_SESSION['user_id'];
$company_id = getCurrentCompanyId();

$pago_id = $_SESSION['payment_id'];
$metodo_pago = $_SESSION['payment_method'];

// Limpiar sesión
unset($_SESSION['payment_success']);
unset($_SESSION['payment_id']);
unset($_SESSION['payment_method']);

// Obtener información del pago
$pago = $db->fetchOne("
    SELECT p.*, s.*, pl.plan_name
    FROM pagos p
    INNER JOIN suscripciones s ON p.suscripcion_id = s.id
    INNER JOIN planes pl ON s.plan_id = pl.id
    WHERE p.id = ?
", [$pago_id]);

if (!$pago) {
    header('Location: /user/dashboard_user.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmación de Pago - CONECTA ERP</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .confirmation-card {
            background: white;
            border-radius: 20px;
            padding: 50px;
            max-width: 600px;
            width: 100%;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
        }

        .success-icon {
            font-size: 5rem;
            color: #48bb78;
            margin-bottom: 30px;
        }

        h1 {
            color: #2d3748;
            font-size: 2rem;
            margin-bottom: 15px;
        }

        .subtitle {
            color: #718096;
            font-size: 1.1rem;
            margin-bottom: 40px;
        }

        .payment-details {
            background: #f7fafc;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            text-align: left;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            color: #718096;
            font-weight: 600;
        }

        .detail-value {
            color: #2d3748;
            font-weight: 700;
        }

        .instructions {
            background: #fff5e6;
            border-left: 4px solid #f59e0b;
            padding: 20px;
            margin-bottom: 30px;
            text-align: left;
            border-radius: 8px;
        }

        .instructions h3 {
            color: #744210;
            margin-bottom: 15px;
        }

        .instructions ul {
            color: #744210;
            margin-left: 20px;
        }

        .instructions li {
            margin-bottom: 10px;
        }

        .bank-info {
            background: #f7fafc;
            border-radius: 10px;
            padding: 20px;
            margin-top: 15px;
        }

        .bank-info strong {
            display: block;
            margin-bottom: 8px;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
        }

        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: #edf2f7;
            color: #4a5568;
        }

        .btn-secondary:hover {
            background: #e2e8f0;
        }
    </style>
</head>
<body>
    <div class="confirmation-card">
        <div class="success-icon">
            <i class="fas fa-check-circle"></i>
        </div>

        <h1>¡Solicitud de Pago Recibida!</h1>
        <p class="subtitle">Tu solicitud está siendo procesada</p>

        <div class="payment-details">
            <div class="detail-row">
                <span class="detail-label">Plan Seleccionado:</span>
                <span class="detail-value"><?php echo htmlspecialchars($pago['plan_name']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Ciclo de Pago:</span>
                <span class="detail-value"><?php echo ucfirst($pago['ciclo_pago']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Monto:</span>
                <span class="detail-value">$<?php echo number_format($pago['monto'], 0, ',', '.'); ?> CLP</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Método de Pago:</span>
                <span class="detail-value"><?php echo ucfirst($metodo_pago); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Número de Pago:</span>
                <span class="detail-value">#<?php echo str_pad($pago_id, 8, '0', STR_PAD_LEFT); ?></span>
            </div>
        </div>

        <?php if ($metodo_pago === 'transferencia'): ?>
        <div class="instructions">
            <h3><i class="fas fa-info-circle"></i> Instrucciones para Transferencia Bancaria</h3>
            <ul>
                <li>Realiza la transferencia a los datos bancarios indicados abajo</li>
                <li>En el concepto de la transferencia incluye el número de pago: <strong>#<?php echo str_pad($pago_id, 8, '0', STR_PAD_LEFT); ?></strong></li>
                <li>Sube el comprobante de pago en tu panel de usuario</li>
                <li>Nuestro equipo verificará el pago en menos de 24 horas hábiles</li>
                <li>Recibirás un email cuando tu suscripción sea activada</li>
            </ul>

            <div class="bank-info">
                <strong>Datos Bancarios - CONECTA ERP</strong>
                <p>Banco: Banco Estado</p>
                <p>Tipo de Cuenta: Cuenta Corriente</p>
                <p>Número de Cuenta: 1234567890</p>
                <p>RUT: 76.123.456-7</p>
                <p>Email: pagos@conectaerp.com</p>
            </div>
        </div>
        <?php elseif ($metodo_pago === 'webpay'): ?>
        <div class="instructions">
            <h3><i class="fas fa-info-circle"></i> Próximos Pasos</h3>
            <ul>
                <li>Serás redirigido a WebPay para completar el pago</li>
                <li>Una vez confirmado el pago, tu suscripción será activada automáticamente</li>
                <li>Recibirás un email de confirmación</li>
            </ul>
        </div>
        <?php elseif ($metodo_pago === 'paypal'): ?>
        <div class="instructions">
            <h3><i class="fas fa-info-circle"></i> Próximos Pasos</h3>
            <ul>
                <li>Serás redirigido a PayPal para completar el pago</li>
                <li>Una vez confirmado el pago, tu suscripción será activada automáticamente</li>
                <li>Recibirás un email de confirmación</li>
            </ul>
        </div>
        <?php else: ?>
        <div class="instructions">
            <h3><i class="fas fa-info-circle"></i> Próximos Pasos</h3>
            <ul>
                <li>Tu solicitud de pago ha sido registrada</li>
                <li>Nuestro equipo la verificará en menos de 24 horas hábiles</li>
                <li>Recibirás un email cuando tu suscripción sea activada</li>
            </ul>
        </div>
        <?php endif; ?>

        <div class="action-buttons">
            <a href="/user/dashboard_user.php" class="btn btn-primary">
                <i class="fas fa-home"></i> Ir al Dashboard
            </a>
            <a href="/user/mis_pagos.php" class="btn btn-secondary">
                <i class="fas fa-receipt"></i> Ver Mis Pagos
            </a>
        </div>
    </div>
</body>
</html>
