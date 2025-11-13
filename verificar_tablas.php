<?php
$host = 'localhost';
$dbname = 'conectae_conectaerpbd';
$user = 'conectae_conectaerpuser';
$pass = 'pt125824caraud';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "🔍 VERIFICACIÓN DE TABLAS CRÍTICAS\n\n";

    $critical_tables = [
        'user_permissions',
        'payment_subscriptions',
        'payment_transactions',
        'system_settings',
        'email_templates',
        'notifications',
        'audit_logs',
        'user_sessions',
        'login_attempts',
        'api_tokens'
    ];

    $all_exist = true;

    foreach ($critical_tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        $exists = $stmt->fetch();

        if ($exists) {
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM `$table`");
            $result = $stmt->fetch();
            echo "✅ $table - {$result['total']} registros\n";
        } else {
            echo "❌ $table - NO EXISTE\n";
            $all_exist = false;
        }
    }

    echo "\n";

    if ($all_exist) {
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "✅ TODAS LAS TABLAS EXISTEN\n";
        echo "🎯 El sistema de registro debería funcionar\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    } else {
        echo "❌ Faltan algunas tablas\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
