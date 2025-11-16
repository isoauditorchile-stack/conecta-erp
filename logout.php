<?php
session_start();
require_once 'includes/config.php';

// Registrar logout en logs si el usuario está autenticado
if (isset($_SESSION['user_id'])) {
    $usuario_id = $_SESSION['user_id'];
    $email = $_SESSION['email'] ?? 'unknown';
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

    // Registrar en logs_acceso
    $stmt = $conn->prepare("INSERT INTO logs_acceso (usuario_id, email, accion, exitoso, mensaje, ip_address, user_agent) VALUES (?, ?, 'logout', 1, 'Usuario cerró sesión', ?, ?)");
    $stmt->bind_param("isss", $usuario_id, $email, $ip_address, $user_agent);
    $stmt->execute();
    $stmt->close();

    // Desactivar sesión en la tabla sesiones
    if (isset($_SESSION['session_token'])) {
        $stmt = $conn->prepare("UPDATE sesiones SET activo = 0 WHERE token = ?");
        $stmt->bind_param("s", $_SESSION['session_token']);
        $stmt->execute();
        $stmt->close();
    }
}

// Destruir la sesión
session_unset();
session_destroy();

// Limpiar cookie de sesión
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Redirigir al login
header("Location: login.php");
exit();
?>
