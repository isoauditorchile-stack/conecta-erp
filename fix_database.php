<?php
/**
 * Script COMPLETO para arreglar la base de datos de CONECTA ERP
 * Agrega TODAS las columnas faltantes en la tabla users
 */

require_once __DIR__ . '/includes/config.php';

try {
    $db = Database::getInstance()->getConnection();

    echo "<!DOCTYPE html>";
    echo "<html><head>";
    echo "<meta charset='UTF-8'>";
    echo "<title>Arreglar Base de Datos - CONECTA ERP</title>";
    echo "<style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h2 { color: #2563eb; }
        pre { background: #1e293b; color: #fff; padding: 20px; border-radius: 5px; overflow-x: auto; }
        .success { color: #10b981; }
        .error { color: #ef4444; }
        .warning { color: #f59e0b; }
        .info { color: #06b6d4; }
        .btn { background: #2563eb; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; display: inline-block; margin-top: 20px; }
        .btn:hover { background: #1e40af; }
    </style>";
    echo "</head><body><div class='container'>";

    echo "<h2>🔧 Arreglando Base de Datos CONECTA ERP</h2>";
    echo "<pre>";

    // Verificar si la tabla users existe
    $tableExists = $db->query("SHOW TABLES LIKE 'users'")->fetch();

    if (!$tableExists) {
        echo "<span class='error'>❌ La tabla 'users' no existe.</span>\n";
        echo "\n<span class='info'>Debes ejecutar primero: installer.php</span>\n";
        echo "</pre></div></body></html>";
        exit;
    }

    echo "<span class='success'>✓ Tabla 'users' encontrada</span>\n\n";

    // Obtener columnas actuales
    $columns = $db->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
    echo "<span class='info'>Columnas actuales (" . count($columns) . "):</span>\n";
    echo implode(', ', $columns) . "\n\n";

    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "Agregando columnas faltantes...\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

    // Columnas que deben existir (CON company_id)
    $requiredColumns = [
        'company_id' => "ALTER TABLE users ADD COLUMN company_id INT(11) DEFAULT NULL AFTER id",
        'full_name' => "ALTER TABLE users ADD COLUMN full_name VARCHAR(200) NOT NULL DEFAULT '' AFTER password",
        'tax_id' => "ALTER TABLE users ADD COLUMN tax_id VARCHAR(50) DEFAULT NULL AFTER full_name",
        'country_id' => "ALTER TABLE users ADD COLUMN country_id INT(11) DEFAULT 1 AFTER tax_id",
        'language_code' => "ALTER TABLE users ADD COLUMN language_code VARCHAR(5) DEFAULT 'es' AFTER country_id",
        'phone' => "ALTER TABLE users ADD COLUMN phone VARCHAR(50) DEFAULT NULL AFTER language_code",
        'avatar_url' => "ALTER TABLE users ADD COLUMN avatar_url VARCHAR(255) DEFAULT NULL AFTER phone",
        'status' => "ALTER TABLE users ADD COLUMN status ENUM('trial','active','suspended','cancelled') DEFAULT 'trial' AFTER is_active",
        'trial_ends_at' => "ALTER TABLE users ADD COLUMN trial_ends_at DATETIME DEFAULT NULL AFTER status",
        'last_login_at' => "ALTER TABLE users ADD COLUMN last_login_at DATETIME DEFAULT NULL AFTER trial_ends_at",
        'last_login_ip' => "ALTER TABLE users ADD COLUMN last_login_ip VARCHAR(50) DEFAULT NULL AFTER last_login_at",
        'email_verified_at' => "ALTER TABLE users ADD COLUMN email_verified_at DATETIME DEFAULT NULL AFTER last_login_ip"
    ];

    $added = 0;
    $existing = 0;
    $errors = 0;

    foreach ($requiredColumns as $column => $sql) {
        if (!in_array($column, $columns)) {
            try {
                $db->exec($sql);
                echo "<span class='success'>✓</span> Columna '<strong>$column</strong>' agregada correctamente\n";
                $added++;
            } catch (Exception $e) {
                echo "<span class='error'>✗</span> Error al agregar '$column': " . $e->getMessage() . "\n";
                $errors++;
            }
        } else {
            echo "<span class='info'>•</span> Columna '<strong>$column</strong>' ya existe\n";
            $existing++;
        }
    }

    echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "RESUMEN\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

    if ($errors > 0) {
        echo "<span class='error'>❌ Errores encontrados: $errors</span>\n";
    }

    echo "<span class='success'>✓ Columnas agregadas: $added</span>\n";
    echo "<span class='info'>• Columnas existentes: $existing</span>\n";
    echo "<span class='info'>• Total de columnas verificadas: " . count($requiredColumns) . "</span>\n\n";

    if ($errors === 0) {
        echo "<span class='success'>━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━</span>\n";
        echo "<span class='success'>✓ ¡BASE DE DATOS ARREGLADA CORRECTAMENTE!</span>\n";
        echo "<span class='success'>━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━</span>\n\n";
        echo "Ahora puedes usar el sistema de registro sin problemas.\n";
    }

    echo "</pre>";

    if ($errors === 0) {
        echo '<a href="register.php" class="btn">🚀 Ir al Registro</a>';
        echo ' ';
        echo '<a href="login.php" class="btn" style="background: #10b981;">🔐 Ir al Login</a>';
    }

    echo "</div></body></html>";

} catch (Exception $e) {
    echo "<pre style='color: red; background: #fee2e2; padding: 20px; border-radius: 5px;'>";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "❌ ERROR DE CONEXIÓN\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    echo $e->getMessage() . "\n\n";
    echo "Verifica que:\n";
    echo "1. ✓ La base de datos existe\n";
    echo "2. ✓ Las credenciales en includes/config.php son correctas\n";
    echo "3. ✓ El usuario tiene permisos para modificar tablas\n\n";
    echo "Credenciales actuales en config.php:\n";
    echo "• Host: " . DB_HOST . "\n";
    echo "• Database: " . DB_NAME . "\n";
    echo "• User: " . DB_USER . "\n";
    echo "</pre>";
}
?>
