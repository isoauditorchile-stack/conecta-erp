<?php
/**
 * AUDITOR PRO - Sistema Multi-ISO de Gestión
 * Módulo de Cierre de Sesión
 *
 * @version 1.0
 * @author AUDITOR PRO
 */

session_start();
require_once('config/config.php');

// Registrar actividad de logout si existe usuario en sesión
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    logActivity($user_id, 'logout', 'authentication', 'Usuario cerró sesión');
}

// Destruir todas las variables de sesión
$_SESSION = array();

// Destruir la cookie de sesión si existe
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destruir la sesión
session_destroy();

// Redirigir a la página de login con mensaje
header('Location: login.php?logout=success');
exit;
?>
