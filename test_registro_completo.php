<?php
/**
 * TEST COMPLETO DEL SISTEMA DE REGISTRO
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔍 TEST COMPLETO - Sistema de Registro</h2>";
echo "<pre>";

$host = 'localhost';
$dbname = 'conectae_conectaerpbd';
$user = 'conectae_conectaerpuser';
$pass = 'pt125824caraud';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "✅ Conexión a BD exitosa\n\n";

    // 1. Verificar tablas necesarias
    echo "1️⃣ VERIFICANDO TABLAS NECESARIAS:\n";
    $tables = ['users', 'companies', 'planes', 'suscripciones', 'aprobaciones_usuario'];
    $all_ok = true;

    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->fetch()) {
            echo "   ✅ $table\n";
        } else {
            echo "   ❌ $table FALTA\n";
            $all_ok = false;
        }
    }

    if (!$all_ok) {
        echo "\n❌ FALTAN TABLAS. Ejecuta: http://tu-dominio/autofix_sistema_completo.php\n";
        die();
    }

    // 2. Verificar columnas en companies
    echo "\n2️⃣ VERIFICANDO TABLA COMPANIES:\n";
    $stmt = $pdo->query("DESCRIBE companies");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (in_array('owner_user_id', $columns)) {
        $stmt = $pdo->query("SHOW COLUMNS FROM companies WHERE Field = 'owner_user_id'");
        $col = $stmt->fetch();
        if (strpos($col['Null'], 'YES') !== false || strpos($col['Null'], 'yes') !== false) {
            echo "   ✅ owner_user_id es NULLABLE\n";
        } else {
            echo "   ❌ owner_user_id NO es nullable\n";
            echo "   🔧 Ejecuta: ALTER TABLE companies MODIFY owner_user_id INT(11) UNSIGNED NULL;\n";
        }
    }

    // 3. Verificar columnas en users
    echo "\n3️⃣ VERIFICANDO TABLA USERS:\n";
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('company_id', $columns)) {
        echo "   ❌ Falta company_id\n";
        echo "   🔧 Ejecuta: ALTER TABLE users ADD company_id INT(11) UNSIGNED NULL AFTER username;\n";
    } else {
        echo "   ✅ company_id existe\n";
    }

    // 4. Test de registro simulado
    echo "\n4️⃣ TEST DE REGISTRO SIMULADO:\n";

    $test_email = 'test_' . time() . '@test.com';
    echo "   📧 Email de prueba: $test_email\n\n";

    try {
        $pdo->beginTransaction();

        // Crear empresa
        echo "   🏢 Creando empresa...\n";
        $stmt = $pdo->prepare("INSERT INTO companies (company_name, legal_name, tax_id, country_id, owner_user_id, created_at) VALUES (?, ?, ?, ?, NULL, NOW())");
        $stmt->execute(['Test Company', 'Test Company', '12345678-9', 1]);
        $company_id = $pdo->lastInsertId();
        echo "      ✅ Empresa ID: $company_id\n";

        // Crear usuario
        echo "   👤 Creando usuario...\n";
        $stmt = $pdo->prepare("INSERT INTO users (firstname, lastname, email, username, password, company_id, phone, country, tax_id, status, is_admin, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
            'Test',
            'User',
            $test_email,
            $test_email,
            password_hash('test123', PASSWORD_BCRYPT),
            $company_id,
            '+56912345678',
            'CL',
            '12345678-9',
            'pending_approval',
            0
        ]);
        $user_id = $pdo->lastInsertId();
        echo "      ✅ Usuario ID: $user_id\n";

        // Actualizar owner_user_id
        echo "   🔄 Actualizando owner_user_id...\n";
        $stmt = $pdo->prepare("UPDATE companies SET owner_user_id = ? WHERE id = ?");
        $stmt->execute([$user_id, $company_id]);
        echo "      ✅ Owner actualizado\n";

        // Crear registro de aprobación
        echo "   📋 Creando registro de aprobación...\n";
        $stmt = $pdo->prepare("INSERT INTO aprobaciones_usuario (user_id, estado, fecha_solicitud, ip_registro) VALUES (?, 'pendiente', NOW(), ?)");
        $stmt->execute([$user_id, $_SERVER['REMOTE_ADDR']]);
        echo "      ✅ Aprobación creada\n";

        $pdo->commit();

        echo "\n   ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "   ✅ REGISTRO SIMULADO EXITOSO\n";
        echo "   ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

        // Limpiar
        echo "   🧹 Limpiando datos de prueba...\n";
        $pdo->exec("DELETE FROM aprobaciones_usuario WHERE user_id = $user_id");
        $pdo->exec("DELETE FROM users WHERE id = $user_id");
        $pdo->exec("DELETE FROM companies WHERE id = $company_id");
        echo "      ✅ Limpieza completa\n\n";

        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "✅ SISTEMA FUNCIONANDO CORRECTAMENTE\n\n";
        echo "Puedes registrarte sin problemas en:\n";
        echo "<a href='/index.php' style='color:#4CAF50;font-weight:bold;'>http://tu-dominio/index.php</a>\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

    } catch (Exception $e) {
        $pdo->rollBack();
        echo "\n   ❌ ERROR EN REGISTRO:\n";
        echo "   " . $e->getMessage() . "\n\n";
        echo "   🔧 SOLUCIÓN:\n";

        if (strpos($e->getMessage(), 'owner_user_id') !== false) {
            echo "   Ejecuta: ALTER TABLE companies MODIFY owner_user_id INT(11) UNSIGNED NULL;\n";
        }
        if (strpos($e->getMessage(), 'company_id') !== false) {
            echo "   Ejecuta: ALTER TABLE users ADD company_id INT(11) UNSIGNED NULL AFTER username;\n";
        }
    }

} catch (PDOException $e) {
    echo "❌ Error de conexión: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>
