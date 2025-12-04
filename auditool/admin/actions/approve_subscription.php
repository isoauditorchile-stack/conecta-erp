<?php
/**
 * AUDITOR PRO - Admin Actions
 * Aprobar Suscripción
 */

session_start();
require_once('../../config/config.php');

// Verificar sesión y rol de superadmin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'superadmin') {
    header('Location: ../../login.php');
    exit;
}

if (!isset($_GET['id'])) {
    header('Location: ../index.php?error=missing_id');
    exit;
}

$subscription_id = (int)$_GET['id'];
$conn = getDBConnection();

// Actualizar estado de suscripción a 'active'
$sql = "UPDATE subscriptions
        SET status = 'active',
            start_date = NOW(),
            next_billing_date = DATE_ADD(NOW(), INTERVAL 1 MONTH)
        WHERE id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $subscription_id);

if ($stmt->execute()) {
    // Log activity
    logActivity($_SESSION['user_id'], 'approve_subscription', 'admin', 'Suscripción #' . $subscription_id . ' aprobada');
    header('Location: ../index.php?success=subscription_approved');
} else {
    header('Location: ../index.php?error=approval_failed');
}

$stmt->close();
$conn->close();
exit;
?>
