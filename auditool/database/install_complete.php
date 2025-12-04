<?php
/**
 * AUDITOR PRO - INSTALADOR COMPLETO
 * Crea TODAS las tablas necesarias SIN datos embebidos
 * EJECUTAR: https://isogestion.conectaerp.com/auditool/database/install_complete.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(600);

require_once('../config/config.php');

// Leer el archivo SQL completo
$sql_file = __DIR__ . '/schema_iso27001.sql';

if (!file_exists($sql_file)) {
    die("ERROR: No se encuentra el archivo schema_iso27001.sql");
}

$sql_content = file_get_contents($sql_file);

// Conectar
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS);

if ($conn->connect_error) {
    die("ERROR DE CONEXIÓN: " . $conn->connect_error);
}

// Crear/seleccionar base de datos
$conn->query("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` DEFAULT CHARACTER SET utf8mb4");
$conn->select_db(DB_NAME);

// Eliminar comentarios y dividir por punto y coma
$sql_content = preg_replace('/--.*$/m', '', $sql_content);
$sql_content = preg_replace('!/\*.*?\*/!s', '', $sql_content);

// Ejecutar cada sentencia
$statements = explode(';', $sql_content);

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Instalación AUDITOR PRO</title>";
echo "<style>body{font-family:Arial;padding:20px;background:#f5f5f5}.success{color:green}.error{color:red}</style></head><body>";
echo "<h1>AUDITOR PRO - Instalación de Base de Datos</h1><hr>";

$success_count = 0;
$error_count = 0;

foreach ($statements as $stmt) {
    $stmt = trim($stmt);
    if (empty($stmt) || strlen($stmt) < 10) continue;

    if ($conn->query($stmt)) {
        if (preg_match('/CREATE TABLE.*`([^`]+)`/i', $stmt, $matches)) {
            echo "<p class='success'>✓ Tabla creada: {$matches[1]}</p>";
            $success_count++;
        }
    } else {
        if (strpos($conn->error, 'already exists') === false) {
            echo "<p class='error'>✗ Error: " . substr($conn->error, 0, 100) . "</p>";
            $error_count++;
        }
    }
}

echo "<hr><h2>RESUMEN:</h2>";
echo "<p class='success'>✓ Tablas creadas: $success_count</p>";
echo "<p class='error'>✗ Errores: $error_count</p>";

if ($error_count == 0) {
    echo "<h2 style='color:green'>¡INSTALACIÓN COMPLETA EXITOSA!</h2>";
    echo "<p><a href='../index.php' style='padding:10px 20px;background:#007bff;color:white;text-decoration:none;border-radius:5px'>Ir al Sistema</a></p>";
} else {
    echo "<h2 style='color:red'>Hubo errores en la instalación</h2>";
    echo "<p>Revisa los permisos de la base de datos</p>";
}

$conn->close();
echo "</body></html>";
?>
