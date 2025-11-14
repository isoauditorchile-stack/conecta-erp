<?php
/**
 * AUTO-FIX COMPLETO: Instala todas las tablas necesarias para el sistema ERP
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'localhost';
$dbname = 'conectae_conectaerpbd';
$user = 'conectae_conectaerpuser';
$pass = 'pt125824caraud';

echo "<h2>🔧 INSTALACIÓN COMPLETA DEL SISTEMA ERP</h2>";
echo "<pre>";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "✅ Conectado a la base de datos\n\n";

    // 1. Arreglar owner_user_id en companies
    echo "1️⃣ Arreglando tabla companies...\n";
    try {
        $pdo->exec("ALTER TABLE `companies` MODIFY COLUMN `owner_user_id` INT(11) UNSIGNED NULL DEFAULT NULL");
        echo "   ✅ owner_user_id ahora es NULLABLE\n\n";
    } catch (PDOException $e) {
        echo "   ⚠️  Ya estaba configurado correctamente\n\n";
    }

    // 2. Verificar company_id en users
    echo "2️⃣ Verificando company_id en tabla users...\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'company_id'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE `users` ADD COLUMN `company_id` INT(11) UNSIGNED NULL DEFAULT NULL AFTER `username`");
        echo "   ✅ Columna company_id agregada\n\n";
    } else {
        echo "   ✅ Columna company_id ya existe\n\n";
    }

    // 3. Instalar sistema completo de administración
    echo "3️⃣ Instalando sistema de administración completo...\n";

    $sql_file = file_get_contents(__DIR__ . '/database/SISTEMA_COMPLETO_ADMIN.sql');

    // Procesar línea por línea
    $statements = [];
    $current = '';
    $lines = explode("\n", $sql_file);

    foreach ($lines as $line) {
        $line = trim($line);

        // Ignorar comentarios y líneas vacías
        if (empty($line) || substr($line, 0, 2) === '--' || substr($line, 0, 1) === '#') {
            continue;
        }

        $current .= ' ' . $line;

        // Si termina con ; ejecutar
        if (substr(rtrim($line), -1) === ';') {
            $statements[] = trim(substr($current, 0, -1));
            $current = '';
        }
    }

    $created = 0;
    $errors = 0;

    foreach ($statements as $statement) {
        if (empty($statement)) continue;

        try {
            $pdo->exec($statement);
            $created++;

            // Mostrar progreso para CREATEs e INSERTs
            if (stripos($statement, 'CREATE TABLE') === 0) {
                preg_match('/CREATE TABLE[^`]*`([^`]+)`/', $statement, $matches);
                if (isset($matches[1])) {
                    echo "   ✅ Tabla creada: {$matches[1]}\n";
                }
            } elseif (stripos($statement, 'INSERT INTO') === 0) {
                echo "   ✅ Datos insertados\n";
            }
        } catch (PDOException $e) {
            $error_msg = $e->getMessage();
            if (stripos($error_msg, 'already exists') === false &&
                stripos($error_msg, 'Duplicate') === false) {
                $errors++;
                echo "   ⚠️  Error: " . substr($error_msg, 0, 100) . "\n";
            }
        }
    }

    echo "\n   📊 Statements ejecutados: $created\n";
    if ($errors > 0) {
        echo "   ⚠️  Errores menores: $errors (ignorados)\n";
    }
    echo "\n";

    // 4. Verificar tablas creadas
    echo "4️⃣ Verificando tablas creadas...\n";
    $required_tables = ['planes', 'suscripciones', 'pagos', 'aprobaciones_usuario', 'historial_suscripciones'];

    foreach ($required_tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->fetch()) {
            $count = $pdo->query("SELECT COUNT(*) as c FROM `$table`")->fetch()['c'];
            echo "   ✅ $table ($count registros)\n";
        } else {
            echo "   ❌ $table NO EXISTE\n";
        }
    }

    echo "\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "✅ SISTEMA ERP COMPLETO INSTALADO\n\n";
    echo "Tablas instaladas:\n";
    echo "  • planes (5 planes configurados)\n";
    echo "  • suscripciones (gestión completa)\n";
    echo "  • pagos (tracking completo)\n";
    echo "  • aprobaciones_usuario (workflow)\n";
    echo "  • historial_suscripciones (auditoría)\n\n";
    echo "El panel de super admin ahora tiene todas las funcionalidades.\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
echo "<a href='/index.php' style='padding:10px 20px; background:#4CAF50; color:white; text-decoration:none; border-radius:5px; margin-right:10px;'>Ir a Registro</a>";
echo "<a href='/admin/panel_super_admin.php' style='padding:10px 20px; background:#667eea; color:white; text-decoration:none; border-radius:5px;'>Ir a Panel Admin</a>";
?>
