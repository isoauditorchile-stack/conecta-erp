<?php
/**
 * Script de Debug para Registro
 * Muestra errores detallados del proceso de registro
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/i18n.php';
initSession();

echo "<h2>🔍 DEBUG - Sistema de Registro</h2>";
echo "<pre>";

// 1. Verificar conexión a BD
echo "1️⃣ <b>Verificando conexión a base de datos...</b>\n";
try {
    $db = Database::getInstance();
    echo "   ✅ Conexión exitosa\n\n";
} catch (Exception $e) {
    echo "   ❌ Error de conexión: " . $e->getMessage() . "\n\n";
    die();
}

// 2. Verificar tablas necesarias
echo "2️⃣ <b>Verificando tablas necesarias...</b>\n";
$required_tables = ['users', 'companies', 'user_permissions'];
$all_tables_exist = true;

foreach ($required_tables as $table) {
    try {
        $result = $db->fetchOne("SHOW TABLES LIKE '$table'");
        if ($result) {
            echo "   ✅ Tabla '$table' existe\n";
        } else {
            echo "   ❌ Tabla '$table' NO EXISTE\n";
            $all_tables_exist = false;
        }
    } catch (Exception $e) {
        echo "   ❌ Error verificando '$table': " . $e->getMessage() . "\n";
        $all_tables_exist = false;
    }
}
echo "\n";

if (!$all_tables_exist) {
    echo "⚠️ Faltan tablas necesarias. El registro no funcionará.\n";
    die();
}

// 3. Test de registro simulado
echo "3️⃣ <b>Simulando proceso de registro...</b>\n";

$test_data = [
    'firstname' => 'Test',
    'lastname' => 'Usuario',
    'email' => 'test_' . time() . '@example.com',
    'password' => 'Test123456',
    'company_name' => 'Empresa Test',
    'phone' => '+56912345678',
    'country' => 'CL',
    'tax_id' => '12345678-9'
];

echo "   📋 Datos de prueba:\n";
echo "   - Email: {$test_data['email']}\n";
echo "   - Empresa: {$test_data['company_name']}\n\n";

try {
    // Verificar si el email ya existe
    echo "   🔍 Verificando email duplicado...\n";
    $existing = $db->fetchOne("SELECT id FROM users WHERE email = ?", [$test_data['email']]);
    if ($existing) {
        echo "   ⚠️ Email ya existe (esto es normal si ya ejecutaste este test)\n\n";
    } else {
        echo "   ✅ Email disponible\n\n";
    }

    // Iniciar transacción
    echo "   🔄 Iniciando transacción...\n";
    $db->getConnection()->beginTransaction();
    echo "   ✅ Transacción iniciada\n\n";

    // Insertar empresa
    echo "   🏢 Insertando empresa...\n";
    $company_id = $db->insert(
        "INSERT INTO companies (company_name, legal_name, tax_id, country_id, created_at) VALUES (?, ?, ?, ?, NOW())",
        [$test_data['company_name'], $test_data['company_name'], $test_data['tax_id'], 1]
    );
    echo "   ✅ Empresa creada con ID: $company_id\n\n";

    // Insertar usuario
    echo "   👤 Insertando usuario...\n";
    $user_id = $db->insert(
        "INSERT INTO users (firstname, lastname, email, username, password, company_id, phone, country, tax_id, status, is_admin, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
        [
            $test_data['firstname'],
            $test_data['lastname'],
            $test_data['email'],
            $test_data['email'],
            password_hash($test_data['password'], PASSWORD_BCRYPT),
            $company_id,
            $test_data['phone'],
            $test_data['country'],
            $test_data['tax_id'],
            'pending_approval',
            0
        ]
    );
    echo "   ✅ Usuario creado con ID: $user_id\n\n";

    // Commit
    echo "   💾 Haciendo commit...\n";
    $db->getConnection()->commit();
    echo "   ✅ Transacción completada exitosamente\n\n";

    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "✅ <b>REGISTRO EXITOSO</b>\n";
    echo "   El sistema de registro está funcionando correctamente.\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

    // Limpiar el usuario de prueba
    echo "🧹 Limpiando datos de prueba...\n";
    $db->delete("DELETE FROM users WHERE id = ?", [$user_id]);
    $db->delete("DELETE FROM companies WHERE id = ?", [$company_id]);
    echo "✅ Datos de prueba eliminados\n\n";

} catch (Exception $e) {
    if ($db->getConnection()->inTransaction()) {
        $db->getConnection()->rollBack();
        echo "   ⚠️ Rollback ejecutado\n\n";
    }

    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "❌ <b>ERROR EN EL REGISTRO</b>\n\n";
    echo "   <b>Mensaje de error:</b>\n";
    echo "   " . $e->getMessage() . "\n\n";
    echo "   <b>Stack trace:</b>\n";
    echo "   " . $e->getTraceAsString() . "\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

    echo "🔧 <b>POSIBLES SOLUCIONES:</b>\n";
    echo "   - Verifica que la tabla 'companies' tenga la columna 'country_id'\n";
    echo "   - Verifica que la tabla 'users' tenga todas las columnas necesarias\n";
    echo "   - Verifica que no haya foreign keys faltantes\n";
}

echo "</pre>";

echo "<hr>";
echo "<h3>📝 Prueba el formulario de registro real:</h3>";
echo "<a href='/index.php' style='padding: 10px 20px; background: #4CAF50; color: white; text-decoration: none; border-radius: 5px;'>Ir a Index.php</a>";
