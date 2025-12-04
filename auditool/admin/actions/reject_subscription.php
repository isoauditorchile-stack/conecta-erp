<?php
/**
 * AUDITOR PRO - Admin Actions
 * Rechazar Suscripción
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

// Actualizar estado de suscripción a 'cancelled'
$sql = "UPDATE subscriptions
        SET status = 'cancelled'
        WHERE id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $subscription_id);

if ($stmt->execute()) {
    // Log activity
    logActivity($_SESSION['user_id'], 'reject_subscription', 'admin', 'Suscripción #' . $subscription_id . ' rechazada');
    header('Location: ../index.php?success=subscription_rejected');
} else {
    header('Location: ../index.php?error=rejection_failed');
}

$stmt->close();
$conn->close();
exit;
?>
