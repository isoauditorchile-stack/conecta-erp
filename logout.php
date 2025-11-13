<?php
/**
 * CONECTA ERP - Cerrar Sesión
 */

session_start();

// Registrar logout en logs si existe la tabla
if (isset($_SESSION['user_id'])) {
    try {
        require_once __DIR__ . '/includes/config.php';
        logActivity($_SESSION['user_id'], 'logout', 'Usuario cerró sesión');
    } catch (Exception $e) {
        // Ignorar errores en logs
    }
}

// Destruir sesión
session_unset();
session_destroy();

// Limpiar cookie de sesión
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Redirigir a la página principal
header('Location: /index.php');
exit;
