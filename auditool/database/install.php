<?php
/**
 * AUDITOR PRO - INSTALADOR AUTOMÁTICO DE BASE DE DATOS
 * EJECUTAR UNA SOLA VEZ: https://isogestion.conectaerp.com/auditool/database/install.php
 *
 * Este script creará TODAS las tablas necesarias automáticamente
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(300); // 5 minutos

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>AUDITOR PRO - Instalador</title>";
echo "<style>
body { font-family: Arial; padding: 20px; background: #f5f5f5; }
.container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
h1 { color: #333; border-bottom: 3px solid #007bff; padding-bottom: 10px; }
.success { background: #d4edda; padding: 15px; border-left: 5px solid #28a745; margin: 10px 0; }
.error { background: #f8d7da; padding: 15px; border-left: 5px solid #dc3545; margin: 10px 0; }
.warning { background: #fff3cd; padding: 15px; border-left: 5px solid #ffc107; margin: 10px 0; }
.info { background: #d1ecf1; padding: 15px; border-left: 5px solid #17a2b8; margin: 10px 0; }
.step { background: #e7f3ff; padding: 10px; margin: 10px 0; border-radius: 5px; }
.btn { display: inline-block; padding: 12px 30px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
.btn:hover { background: #0056b3; }
</style></head><body><div class='container'>";

echo "<h1>🚀 AUDITOR PRO - Instalador Automático de Base de Datos</h1>";

require_once('../config/config.php');

// Conectar a la base de datos
echo "<div class='step'><strong>Paso 1:</strong> Conectando a la base de datos...</div>";

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS);

    if ($conn->connect_error) {
        throw new Exception("Error de conexión: " . $conn->connect_error);
    }

    echo "<div class='success'>✓ Conexión exitosa a MySQL</div>";

    // Crear base de datos si no existe
    echo "<div class='step'><strong>Paso 2:</strong> Verificando/creando base de datos " . DB_NAME . "...</div>";

    $conn->query("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $conn->select_db(DB_NAME);

    echo "<div class='success'>✓ Base de datos seleccionada correctamente</div>";

} catch (Exception $e) {
    echo "<div class='error'>✗ ERROR: " . $e->getMessage() . "</div>";
    echo "<p><strong>SOLUCIÓN:</strong> Verifica las credenciales en config.php</p>";
    die("</div></body></html>");
}

// SQL para crear todas las tablas
echo "<div class='step'><strong>Paso 3:</strong> Creando tablas del sistema...</div>";

$tables_created = 0;
$tables_skipped = 0;

// Array con todas las sentencias CREATE TABLE
$sql_statements = [
    // Tabla companies
    "CREATE TABLE IF NOT EXISTS `companies` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `company_name` varchar(255) NOT NULL,
      `company_rut` varchar(50) DEFAULT NULL,
      `company_address` text DEFAULT NULL,
      `company_city` varchar(100) DEFAULT NULL,
      `company_country` varchar(50) DEFAULT 'CL',
      `company_phone` varchar(50) DEFAULT NULL,
      `company_email` varchar(255) DEFAULT NULL,
      `company_logo` varchar(255) DEFAULT NULL,
      `active_isos` text DEFAULT NULL,
      `status` enum('active','inactive','suspended') DEFAULT 'active',
      `created_date` datetime DEFAULT CURRENT_TIMESTAMP,
      `updated_date` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `company_rut` (`company_rut`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Tabla users
    "CREATE TABLE IF NOT EXISTS `users` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `company_id` int(11) NOT NULL,
      `username` varchar(100) NOT NULL,
      `email` varchar(255) NOT NULL,
      `password` varchar(255) NOT NULL,
      `full_name` varchar(255) NOT NULL,
      `role` enum('superadmin','admin','auditor','user','readonly') DEFAULT 'user',
      `language` varchar(5) DEFAULT 'es',
      `timezone` varchar(50) DEFAULT 'America/Santiago',
      `last_login` datetime DEFAULT NULL,
      `login_attempts` int(11) DEFAULT 0,
      `locked_until` datetime DEFAULT NULL,
      `status` enum('active','inactive','locked') DEFAULT 'active',
      `created_date` datetime DEFAULT CURRENT_TIMESTAMP,
      `updated_date` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `username` (`username`),
      UNIQUE KEY `email` (`email`),
      KEY `company_id` (`company_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Tabla subscriptions
    "CREATE TABLE IF NOT EXISTS `subscriptions` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `company_id` int(11) NOT NULL,
      `plan_name` enum('basic','professional','enterprise','custom') NOT NULL,
      `plan_price` decimal(10,2) NOT NULL,
      `plan_currency` varchar(5) DEFAULT 'USD',
      `billing_cycle` enum('monthly','yearly') DEFAULT 'monthly',
      `status` enum('trial','active','suspended','cancelled','expired','pending') DEFAULT 'pending',
      `start_date` date DEFAULT NULL,
      `end_date` date DEFAULT NULL,
      `trial_start_date` date DEFAULT NULL,
      `trial_end_date` date DEFAULT NULL,
      `next_billing_date` date DEFAULT NULL,
      `max_users` int(11) NOT NULL DEFAULT 5,
      `max_companies` int(11) NOT NULL DEFAULT 1,
      `max_isos` int(11) NOT NULL DEFAULT 3,
      `payment_method` varchar(50) DEFAULT NULL,
      `payment_status` enum('pending','paid','failed','refunded') DEFAULT 'pending',
      `last_payment_date` date DEFAULT NULL,
      `notes` text DEFAULT NULL,
      `created_date` datetime DEFAULT CURRENT_TIMESTAMP,
      `updated_date` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `company_id` (`company_id`),
      KEY `status` (`status`),
      KEY `plan_name` (`plan_name`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Tabla payments
    "CREATE TABLE IF NOT EXISTS `payments` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `subscription_id` int(11) NOT NULL,
      `company_id` int(11) NOT NULL,
      `payment_amount` decimal(10,2) NOT NULL,
      `payment_currency` varchar(5) DEFAULT 'USD',
      `payment_method` varchar(50) DEFAULT NULL,
      `payment_status` enum('pending','completed','failed','refunded') DEFAULT 'pending',
      `transaction_id` varchar(255) DEFAULT NULL,
      `payment_date` datetime DEFAULT NULL,
      `payment_details` text DEFAULT NULL,
      `created_date` datetime DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `subscription_id` (`subscription_id`),
      KEY `company_id` (`company_id`),
      KEY `payment_status` (`payment_status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Tabla activity_logs
    "CREATE TABLE IF NOT EXISTS `activity_logs` (
      `id` bigint(20) NOT NULL AUTO_INCREMENT,
      `user_id` int(11) DEFAULT NULL,
      `action` varchar(100) NOT NULL,
      `module` varchar(100) NOT NULL,
      `details` text DEFAULT NULL,
      `ip_address` varchar(45) DEFAULT NULL,
      `user_agent` varchar(255) DEFAULT NULL,
      `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `user_id` (`user_id`),
      KEY `created_at` (`created_at`),
      KEY `module` (`module`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Tabla iso27001_assets
    "CREATE TABLE IF NOT EXISTS `iso27001_assets` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `company_id` int(11) NOT NULL,
      `asset_id` varchar(50) NOT NULL,
      `asset_name` varchar(255) NOT NULL,
      `asset_type` enum('Hardware','Software','Información','Servicios','Personal','Instalaciones','Otros') NOT NULL,
      `asset_category` varchar(100) DEFAULT NULL,
      `asset_owner` varchar(255) NOT NULL,
      `asset_custodian` varchar(255) DEFAULT NULL,
      `asset_location` varchar(255) DEFAULT NULL,
      `asset_description` text DEFAULT NULL,
      `criticality` enum('Muy Alta','Alta','Media','Baja','Muy Baja') DEFAULT 'Media',
      `confidentiality` tinyint(4) DEFAULT 1,
      `integrity` tinyint(4) DEFAULT 1,
      `availability` tinyint(4) DEFAULT 1,
      `data_classification` enum('Público','Interno','Confidencial','Secreto') DEFAULT 'Interno',
      `acquisition_date` date DEFAULT NULL,
      `estimated_value` decimal(15,2) DEFAULT NULL,
      `replacement_cost` decimal(15,2) DEFAULT NULL,
      `recovery_time` varchar(50) DEFAULT NULL,
      `legal_requirements` text DEFAULT NULL,
      `last_review` date DEFAULT NULL,
      `next_review` date DEFAULT NULL,
      `status` enum('Activo','Inactivo','En Mantenimiento','Retirado') DEFAULT 'Activo',
      `created_by` int(11) DEFAULT NULL,
      `created_date` datetime DEFAULT CURRENT_TIMESTAMP,
      `updated_by` int(11) DEFAULT NULL,
      `updated_date` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `unique_asset` (`company_id`,`asset_id`),
      KEY `company_id` (`company_id`),
      KEY `asset_type` (`asset_type`),
      KEY `criticality` (`criticality`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Continúa en siguiente mensaje...
];

echo "<p><strong>Creando " . count($sql_statements) . " tablas base del sistema...</strong></p>";

foreach ($sql_statements as $index => $sql) {
    // Extraer nombre de la tabla del SQL
    preg_match('/CREATE TABLE.*`([^`]+)`/', $sql, $matches);
    $table_name = $matches[1] ?? "tabla_" . ($index + 1);

    if ($conn->query($sql)) {
        echo "<div class='info'>✓ Tabla creada: <strong>$table_name</strong></div>";
        $tables_created++;
    } else {
        // Si falla, probablemente ya existe
        if (strpos($conn->error, 'already exists') !== false || strpos($conn->error, 'Duplicate') !== false) {
            echo "<div class='warning'>⚠ Tabla ya existe: <strong>$table_name</strong> (omitida)</div>";
            $tables_skipped++;
        } else {
            echo "<div class='error'>✗ Error en tabla <strong>$table_name</strong>: " . $conn->error . "</div>";
        }
    }
}

echo "<p><strong>CONTINÚA EN LA SIGUIENTE PARTE...</strong></p>";
echo "<p><a href='install_part2.php' class='btn'>Continuar con Tablas ISO →</a></p>";

$conn->close();

echo "</div></body></html>";
?>
