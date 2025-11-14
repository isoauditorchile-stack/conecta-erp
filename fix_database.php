<?php
/**
 * Script para arreglar la base de datos de CONECTA ERP
 * Agrega las columnas faltantes en la tabla users
 */

require_once __DIR__ . '/includes/config.php';

try {
    $db = Database::getInstance()->getConnection();

    echo "<h2>Arreglando Base de Datos CONECTA ERP</h2>";
    echo "<pre>";

    // Verificar si la tabla users existe
    $tableExists = $db->query("SHOW TABLES LIKE 'users'")->fetch();

    if (!$tableExists) {
        echo "❌ La tabla 'users' no existe. Ejecuta el instalador primero: installer.php\n";
        exit;
    }

    echo "✓ Tabla 'users' encontrada\n\n";

    // Obtener columnas actuales
    $columns = $db->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
    echo "Columnas actuales: " . implode(', ', $columns) . "\n\n";

    // Columnas que deben existir
    $requiredColumns = [
        'full_name' => "ALTER TABLE users ADD COLUMN full_name VARCHAR(200) NOT NULL AFTER password",
        'tax_id' => "ALTER TABLE users ADD COLUMN tax_id VARCHAR(50) DEFAULT NULL AFTER full_name",
        'country_id' => "ALTER TABLE users ADD COLUMN country_id INT(11) DEFAULT NULL AFTER tax_id",
        'language_code' => "ALTER TABLE users ADD COLUMN language_code VARCHAR(5) DEFAULT 'es' AFTER country_id",
        'phone' => "ALTER TABLE users ADD COLUMN phone VARCHAR(50) DEFAULT NULL AFTER language_code",
        'trial_ends_at' => "ALTER TABLE users ADD COLUMN trial_ends_at DATETIME DEFAULT NULL AFTER status",
        'last_login_at' => "ALTER TABLE users ADD COLUMN last_login_at DATETIME DEFAULT NULL",
        'last_login_ip' => "ALTER TABLE users ADD COLUMN last_login_ip VARCHAR(50) DEFAULT NULL"
    ];

    $added = 0;

    foreach ($requiredColumns as $column => $sql) {
        if (!in_array($column, $columns)) {
            try {
                $db->exec($sql);
                echo "✓ Columna '$column' agregada correctamente\n";
                $added++;
            } catch (Exception $e) {
                echo "⚠ Error al agregar '$column': " . $e->getMessage() . "\n";
            }
        } else {
            echo "• Columna '$column' ya existe\n";
        }
    }

    echo "\n";
    echo "======================================\n";
    echo "✓ Base de datos arreglada correctamente\n";
    echo "✓ Columnas agregadas: $added\n";
    echo "======================================\n";
    echo "\nAhora puedes usar el sistema de registro sin problemas.\n";
    echo "</pre>";

    echo '<br><a href="register.php" style="background: #2563eb; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 20px;">Ir al Registro</a>';

} catch (Exception $e) {
    echo "<pre style='color: red;'>";
    echo "❌ ERROR: " . $e->getMessage() . "\n\n";
    echo "Verifica que:\n";
    echo "1. La base de datos exista\n";
    echo "2. Las credenciales en includes/config.php sean correctas\n";
    echo "3. El usuario tenga permisos para modificar tablas\n";
    echo "</pre>";
}
?>
