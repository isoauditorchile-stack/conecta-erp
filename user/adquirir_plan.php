<?php
/**
 * CONECTA ERP - Adquirir Plan
 * Interfaz completa para selección y compra de planes
 */

require_once __DIR__ . '/../includes/config.php';

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

// Super admin no necesita comprar planes
if (isAdmin()) {
    header('Location: /admin/panel_super_admin.php');
    exit;
}

$db = Database::getInstance();
$user_id = $_SESSION['user_id'];
$company_id = getCurrentCompanyId();

// Obtener información del usuario y empresa
$user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$user_id]);
$company = $db->fetchOne("SELECT * FROM companies WHERE id = ?", [$company_id]);

// Obtener suscripción actual
$suscripcion_actual = $db->fetchOne("
    SELECT s.*, p.plan_name, p.plan_code
    FROM suscripciones s
    INNER JOIN planes p ON s.plan_id = p.id
    WHERE s.company_id = ? AND s.estado IN ('trial', 'activa')
    ORDER BY s.id DESC
    LIMIT 1
", [$company_id]);

// Obtener todos los planes disponibles
$planes = $db->fetchAll("SELECT * FROM planes WHERE is_active = 1 ORDER BY
    CASE plan_code
        WHEN 'TRIAL' THEN 1
        WHEN 'STARTER' THEN 2
        WHEN 'PROFESSIONAL' THEN 3
        WHEN 'ENTERPRISE' THEN 4
        WHEN 'CUSTOM' THEN 5
    END");

$alert = null;
$selected_plan = null;

// Procesar selección de plan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['select_plan'])) {
    $plan_id = (int)$_POST['plan_id'];
    $ciclo_pago = $_POST['ciclo_pago']; // mensual o anual

    $selected_plan = $db->fetchOne("SELECT * FROM planes WHERE id = ?", [$plan_id]);

    if (!$selected_plan) {
        $alert = ['type' => 'danger', 'message' => 'Plan no encontrado'];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adquirir Plan - CONECTA ERP</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            color: white;
            margin-bottom: 50px;
        }

        .header h1 {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 15px;
        }

        .header p {
            font-size: 1.2rem;
            opacity: 0.9;
        }

        .current-plan {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 40px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }

        .current-plan h2 {
            color: #667eea;
            margin-bottom: 20px;
        }

        .plan-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 50px;
        }

        .plan-card {
            background: white;
            border-radius: 20px;
            padding: 40px 30px;
            position: relative;
            transition: all 0.3s ease;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .plan-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 50px rgba(0,0,0,0.2);
        }

        .plan-card.popular {
            border: 3px solid #667eea;
            transform: scale(1.05);
        }

        .plan-badge {
            position: absolute;
            top: -15px;
            right: 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 8px 20px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 700;
        }

        .plan-name {
            font-size: 1.8rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 15px;
        }

        .plan-price {
            font-size: 3rem;
            font-weight: 800;
            color: #667eea;
            margin-bottom: 10px;
        }

        .plan-price small {
            font-size: 1rem;
            color: #718096;
            font-weight: 400;
        }

        .plan-description {
            color: #718096;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .plan-features {
            list-style: none;
            margin-bottom: 30px;
        }

        .plan-features li {
            padding: 12px 0;
            color: #4a5568;
            display: flex;
            align-items: center;
        }

        .plan-features li i {
            color: #48bb78;
            margin-right: 12px;
            font-size: 1.2rem;
        }

        .plan-button {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .plan-button.primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .plan-button.primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }

        .plan-button.secondary {
            background: #edf2f7;
            color: #4a5568;
        }

        .plan-button.secondary:hover {
            background: #e2e8f0;
        }

        .plan-button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .payment-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .payment-modal.active {
            display: flex;
        }

        .payment-content {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .payment-header {
            margin-bottom: 30px;
        }

        .payment-header h2 {
            color: #2d3748;
            margin-bottom: 10px;
        }

        .cycle-selector {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
        }

        .cycle-option {
            flex: 1;
            padding: 20px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            cursor: pointer;
            text-align: center;
            transition: all 0.3s ease;
        }

        .cycle-option:hover {
            border-color: #667eea;
        }

        .cycle-option.selected {
            border-color: #667eea;
            background: #f7fafc;
        }

        .cycle-option input[type="radio"] {
            display: none;
        }

        .cycle-label {
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 5px;
        }

        .cycle-price {
            font-size: 1.5rem;
            color: #667eea;
            font-weight: 800;
        }

        .payment-methods {
            margin-bottom: 30px;
        }

        .payment-methods h3 {
            color: #2d3748;
            margin-bottom: 15px;
        }

        .method-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .method-option {
            padding: 20px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            cursor: pointer;
            text-align: center;
            transition: all 0.3s ease;
        }

        .method-option:hover {
            border-color: #667eea;
        }

        .method-option.selected {
            border-color: #667eea;
            background: #f7fafc;
        }

        .method-option input[type="radio"] {
            display: none;
        }

        .method-icon {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .submit-payment {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .submit-payment:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }

        .back-link {
            display: inline-block;
            color: white;
            text-decoration: none;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .alert-success { background: #c6f6d5; color: #22543d; }
        .alert-danger { background: #fed7d7; color: #742a2a; }
        .alert-warning { background: #feebc8; color: #744210; }

        .savings-badge {
            background: #48bb78;
            color: white;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 700;
            margin-left: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="/user/dashboard_user.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Volver al Dashboard
        </a>

        <div class="header">
            <h1>Adquirir Plan</h1>
            <p>Selecciona el plan perfecto para tu empresa</p>
        </div>

        <?php if ($alert): ?>
        <div class="alert alert-<?php echo $alert['type']; ?>">
            <?php echo htmlspecialchars($alert['message']); ?>
        </div>
        <?php endif; ?>

        <?php if ($suscripcion_actual): ?>
        <div class="current-plan">
            <h2><i class="fas fa-star"></i> Tu Plan Actual</h2>
            <p>
                <strong><?php echo htmlspecialchars($suscripcion_actual['plan_name']); ?></strong> -
                Estado: <strong><?php echo $suscripcion_actual['estado']; ?></strong>
                <?php if ($suscripcion_actual['fecha_fin_trial']): ?>
                    | Trial expira: <?php echo date('d/m/Y', strtotime($suscripcion_actual['fecha_fin_trial'])); ?>
                <?php endif; ?>
            </p>
        </div>
        <?php endif; ?>

        <div class="plan-grid">
            <?php foreach ($planes as $plan): ?>
                <?php if ($plan['plan_code'] === 'TRIAL') continue; // No mostrar Trial en compra ?>
            <div class="plan-card <?php echo $plan['plan_code'] === 'PROFESSIONAL' ? 'popular' : ''; ?>">
                <?php if ($plan['plan_code'] === 'PROFESSIONAL'): ?>
                <div class="plan-badge">Más Popular</div>
                <?php endif; ?>

                <div class="plan-name"><?php echo htmlspecialchars($plan['plan_name']); ?></div>

                <div class="plan-price">
                    $<?php echo number_format($plan['precio_mensual'], 0, ',', '.'); ?>
                    <small>/mes</small>
                </div>

                <div class="plan-description">
                    <?php
                    if ($plan['plan_code'] === 'STARTER') {
                        echo 'Ideal para pequeñas empresas que están comenzando';
                    } elseif ($plan['plan_code'] === 'PROFESSIONAL') {
                        echo 'Para empresas en crecimiento que necesitan más capacidades';
                    } elseif ($plan['plan_code'] === 'ENTERPRISE') {
                        echo 'Para grandes empresas con necesidades ilimitadas';
                    } else {
                        echo 'Plan personalizado según tus necesidades específicas';
                    }
                    ?>
                </div>

                <ul class="plan-features">
                    <li><i class="fas fa-check-circle"></i> <?php echo $plan['max_usuarios'] ?: 'Ilimitado'; ?> usuarios</li>
                    <li><i class="fas fa-check-circle"></i> <?php echo $plan['max_empresas'] ?: 'Ilimitadas'; ?> empresas</li>
                    <li><i class="fas fa-check-circle"></i> <?php echo $plan['modulos_incluidos'] == 14 ? 'Todos' : $plan['modulos_incluidos']; ?> módulos</li>
                    <li><i class="fas fa-check-circle"></i> Multi-país, Multi-moneda</li>
                    <li><i class="fas fa-check-circle"></i> Soporte <?php echo $plan['plan_code'] === 'ENTERPRISE' ? '24/7 Prioritario' : 'Email'; ?></li>
                    <?php if ($plan['plan_code'] === 'ENTERPRISE'): ?>
                    <li><i class="fas fa-check-circle"></i> Servidor Dedicado</li>
                    <li><i class="fas fa-check-circle"></i> Personalización Completa</li>
                    <?php endif; ?>
                </ul>

                <?php if ($plan['plan_code'] === 'CUSTOM'): ?>
                <button class="plan-button secondary" onclick="alert('Por favor contacta a ventas@conectaerp.com')">
                    Contactar Ventas
                </button>
                <?php else: ?>
                <form method="POST">
                    <input type="hidden" name="plan_id" value="<?php echo $plan['id']; ?>">
                    <input type="hidden" name="ciclo_pago" value="mensual">
                    <button type="submit" name="select_plan" class="plan-button primary">
                        Seleccionar Plan
                    </button>
                </form>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Modal de Pago -->
    <?php if ($selected_plan): ?>
    <div class="payment-modal active" id="paymentModal">
        <div class="payment-content">
            <div class="payment-header">
                <h2>Confirmar Plan: <?php echo htmlspecialchars($selected_plan['plan_name']); ?></h2>
                <p>Selecciona el ciclo de pago y método de pago</p>
            </div>

            <form action="/user/procesar_pago.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="plan_id" value="<?php echo $selected_plan['id']; ?>">
                <input type="hidden" name="company_id" value="<?php echo $company_id; ?>">

                <!-- Ciclo de Pago -->
                <div class="cycle-selector">
                    <label class="cycle-option selected">
                        <input type="radio" name="ciclo_pago" value="mensual" checked>
                        <div class="cycle-label">Mensual</div>
                        <div class="cycle-price">$<?php echo number_format($selected_plan['precio_mensual'], 0, ',', '.'); ?></div>
                    </label>

                    <label class="cycle-option">
                        <input type="radio" name="ciclo_pago" value="anual">
                        <div class="cycle-label">Anual</div>
                        <div class="cycle-price">
                            $<?php echo number_format($selected_plan['precio_anual'], 0, ',', '.'); ?>
                            <span class="savings-badge">Ahorra 2 meses</span>
                        </div>
                    </label>
                </div>

                <!-- Métodos de Pago -->
                <div class="payment-methods">
                    <h3>Método de Pago</h3>
                    <div class="method-grid">
                        <label class="method-option selected">
                            <input type="radio" name="metodo_pago" value="transferencia" checked>
                            <div class="method-icon"><i class="fas fa-university"></i></div>
                            <div>Transferencia Bancaria</div>
                        </label>

                        <label class="method-option">
                            <input type="radio" name="metodo_pago" value="webpay">
                            <div class="method-icon"><i class="fab fa-cc-visa"></i></div>
                            <div>WebPay</div>
                        </label>

                        <label class="method-option">
                            <input type="radio" name="metodo_pago" value="paypal">
                            <div class="method-icon"><i class="fab fa-paypal"></i></div>
                            <div>PayPal</div>
                        </label>

                        <label class="method-option">
                            <input type="radio" name="metodo_pago" value="mercadopago">
                            <div class="method-icon"><i class="fas fa-money-bill-wave"></i></div>
                            <div>MercadoPago</div>
                        </label>
                    </div>
                </div>

                <button type="submit" class="submit-payment">
                    Proceder al Pago
                </button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <script>
        // Manejo de selección de ciclo
        document.querySelectorAll('.cycle-option').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.cycle-option').forEach(o => o.classList.remove('selected'));
                this.classList.add('selected');
                this.querySelector('input[type="radio"]').checked = true;
            });
        });

        // Manejo de selección de método de pago
        document.querySelectorAll('.method-option').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.method-option').forEach(o => o.classList.remove('selected'));
                this.classList.add('selected');
                this.querySelector('input[type="radio"]').checked = true;
            });
        });
    </script>
</body>
</html>
