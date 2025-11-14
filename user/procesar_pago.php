<?php
/**
 * CONECTA ERP - Procesar Pago
 * Procesa la solicitud de pago y crea registros pendientes para verificación por admin
 */

require_once __DIR__ . '/../includes/config.php';

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /user/adquirir_plan.php');
    exit;
}

$db = Database::getInstance();
$user_id = $_SESSION['user_id'];
$company_id = getCurrentCompanyId();

// Obtener datos del formulario
$plan_id = (int)$_POST['plan_id'];
$ciclo_pago = $_POST['ciclo_pago']; // mensual o anual
$metodo_pago = $_POST['metodo_pago']; // transferencia, webpay, paypal, mercadopago

try {
    // Obtener plan
    $plan = $db->fetchOne("SELECT * FROM planes WHERE id = ?", [$plan_id]);

    if (!$plan) {
        throw new Exception("Plan no encontrado");
    }

    // Calcular monto
    $monto = ($ciclo_pago === 'anual') ? $plan['precio_anual'] : $plan['precio_mensual'];

    // Verificar si ya tiene una suscripción activa
    $suscripcion_existente = $db->fetchOne("
        SELECT * FROM suscripciones
        WHERE company_id = ? AND estado IN ('activa', 'trial')
        ORDER BY id DESC
        LIMIT 1
    ", [$company_id]);

    $db->getConnection()->beginTransaction();

    // Crear o actualizar suscripción
    if ($suscripcion_existente) {
        // Actualizar suscripción existente (upgrade/downgrade)
        $db->update("
            UPDATE suscripciones
            SET plan_id = ?,
                ciclo_pago = ?,
                estado = 'pendiente',
                monto_total = ?,
                updated_at = NOW()
            WHERE id = ?
        ", [$plan_id, $ciclo_pago, $monto, $suscripcion_existente['id']]);

        $suscripcion_id = $suscripcion_existente['id'];
    } else {
        // Crear nueva suscripción
        $fecha_inicio = date('Y-m-d');
        $fecha_fin = null;

        if ($ciclo_pago === 'mensual') {
            $fecha_fin = date('Y-m-d', strtotime('+1 month'));
        } elseif ($ciclo_pago === 'anual') {
            $fecha_fin = date('Y-m-d', strtotime('+1 year'));
        }

        $suscripcion_id = $db->insert("
            INSERT INTO suscripciones (
                company_id,
                plan_id,
                estado,
                fecha_inicio,
                fecha_fin,
                fecha_fin_trial,
                ciclo_pago,
                monto_total,
                auto_renovacion,
                created_at
            ) VALUES (?, ?, 'pendiente', ?, ?, NULL, ?, ?, 1, NOW())
        ", [$company_id, $plan_id, $fecha_inicio, $fecha_fin, $ciclo_pago, $monto]);
    }

    // Crear registro de pago pendiente
    $pago_id = $db->insert("
        INSERT INTO pagos (
            suscripcion_id,
            company_id,
            monto,
            moneda,
            metodo_pago,
            estado,
            fecha_pago,
            created_at
        ) VALUES (?, ?, ?, 'CLP', ?, 'pendiente', NOW(), NOW())
    ", [$suscripcion_id, $company_id, $monto, $metodo_pago]);

    // Si es transferencia bancaria, necesita subir comprobante
    if ($metodo_pago === 'transferencia') {
        // Manejar upload de comprobante (si se proporciona)
        if (isset($_FILES['comprobante']) && $_FILES['comprobante']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/../uploads/comprobantes/';

            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $file_extension = pathinfo($_FILES['comprobante']['name'], PATHINFO_EXTENSION);
            $filename = 'comprobante_' . $pago_id . '_' . time() . '.' . $file_extension;
            $filepath = $upload_dir . $filename;

            if (move_uploaded_file($_FILES['comprobante']['tmp_name'], $filepath)) {
                $db->update("
                    UPDATE pagos
                    SET comprobante_url = ?
                    WHERE id = ?
                ", ['/uploads/comprobantes/' . $filename, $pago_id]);
            }
        }
    }

    // Si es WebPay, PayPal, MercadoPago, etc. - aquí iría la integración con el gateway
    // Por ahora, dejamos como pendiente para verificación manual

    $db->getConnection()->commit();

    // Redirigir a página de confirmación
    $_SESSION['payment_success'] = true;
    $_SESSION['payment_id'] = $pago_id;
    $_SESSION['payment_method'] = $metodo_pago;

    header('Location: /user/pago_confirmacion.php');
    exit;

} catch (Exception $e) {
    if ($db->getConnection()->inTransaction()) {
        $db->getConnection()->rollBack();
    }

    error_log("Error al procesar pago: " . $e->getMessage());

    $_SESSION['payment_error'] = 'Hubo un error al procesar tu pago. Por favor, intenta nuevamente.';
    header('Location: /user/adquirir_plan.php');
    exit;
}
