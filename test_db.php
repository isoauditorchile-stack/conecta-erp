<?php
/**
 * Script de Diagnóstico de Base de Datos
 * Verifica conexión y tablas necesarias
 */

echo "<h2>🔍 Diagnóstico de Base de Datos - CONECTA ERP</h2>";
echo "<pre>";

// Credenciales de config.php
$host = 'localhost';
$dbname = 'conectae_conectaerpbd';
$user = 'conectae_conectaerpuser';
$pass = 'pt125824caraud';

echo "📋 <b>Configuración:</b>\n";
echo "  Host: $host\n";
echo "  Database: $dbname\n";
echo "  User: $user\n\n";

// 1. Probar conexión sin especificar base de datos
echo "1️⃣ <b>Probando conexión al servidor MySQL...</b>\n";
try {
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "   ✅ Conexión al servidor MySQL: <span style='color:green'>EXITOSA</span>\n\n";
} catch (PDOException $e) {
    echo "   ❌ Error de conexión: " . $e->getMessage() . "\n";
    echo "   🔧 <b>SOLUCIÓN:</b> Verifica que MySQL esté corriendo y las credenciales sean correctas.\n";
    die();
}

// 2. Verificar si la base de datos existe
echo "2️⃣ <b>Verificando si existe la base de datos '$dbname'...</b>\n";
try {
    $stmt = $pdo->query("SHOW DATABASES LIKE '$dbname'");
    $exists = $stmt->fetch();

    if ($exists) {
        echo "   ✅ Base de datos '$dbname': <span style='color:green'>EXISTE</span>\n\n";
    } else {
        echo "   ❌ Base de datos '$dbname': <span style='color:red'>NO EXISTE</span>\n";
        echo "   🔧 <b>SOLUCIÓN:</b> Ejecuta el instalador en: <a href='/install.php'>install.php</a>\n";
        die();
    }
} catch (PDOException $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
    die();
}

// 3. Conectar a la base de datos específica
echo "3️⃣ <b>Conectando a la base de datos '$dbname'...</b>\n";
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "   ✅ Conexión a la base de datos: <span style='color:green'>EXITOSA</span>\n\n";
} catch (PDOException $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
    die();
}

// 4. Contar tablas
echo "4️⃣ <b>Verificando tablas instaladas...</b>\n";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM information_schema.tables WHERE table_schema = '$dbname'");
    $result = $stmt->fetch();
    $total_tables = $result['total'];

    echo "   📊 Total de tablas: <b>$total_tables</b>\n";

    if ($total_tables == 0) {
        echo "   ❌ <span style='color:red'>NO HAY TABLAS INSTALADAS</span>\n";
        echo "   🔧 <b>SOLUCIÓN:</b> Debes ejecutar el instalador: <a href='/install.php'>install.php</a>\n\n";
    } else if ($total_tables < 50) {
        echo "   ⚠️  <span style='color:orange'>Pocas tablas instaladas (se esperan 200+)</span>\n";
        echo "   🔧 <b>SOLUCIÓN:</b> Re-ejecuta el instalador: <a href='/install.php'>install.php</a>\n\n";
    } else {
        echo "   ✅ <span style='color:green'>Instalación completa</span>\n\n";
    }
} catch (PDOException $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
    die();
}

// 5. Verificar tablas críticas
echo "5️⃣ <b>Verificando tablas críticas para registro...</b>\n";
$critical_tables = ['users', 'companies', 'modules', 'submodules', 'user_permissions'];
$missing_tables = [];

foreach ($critical_tables as $table) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        $exists = $stmt->fetch();

        if ($exists) {
            // Contar registros
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM `$table`");
            $result = $stmt->fetch();
            $count = $result['total'];
            echo "   ✅ Tabla '$table': <span style='color:green'>EXISTE</span> ($count registros)\n";
        } else {
            echo "   ❌ Tabla '$table': <span style='color:red'>NO EXISTE</span>\n";
            $missing_tables[] = $table;
        }
    } catch (PDOException $e) {
        echo "   ❌ Tabla '$table': Error - " . $e->getMessage() . "\n";
        $missing_tables[] = $table;
    }
}

echo "\n";

// 6. Diagnóstico final
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "🎯 <b>DIAGNÓSTICO FINAL:</b>\n\n";

if (empty($missing_tables) && $total_tables >= 50) {
    echo "✅ <span style='color:green;font-size:18px'><b>SISTEMA LISTO</b></span>\n";
    echo "   La base de datos está correctamente instalada.\n";
    echo "   Puedes registrarte en: <a href='/index.php'>index.php</a>\n";
} else if (!empty($missing_tables)) {
    echo "❌ <span style='color:red;font-size:18px'><b>FALTAN TABLAS CRÍTICAS</b></span>\n";
    echo "   Tablas faltantes: " . implode(', ', $missing_tables) . "\n\n";
    echo "   🔧 <b>SOLUCIÓN:</b>\n";
    echo "   1. Ve a: <a href='/install.php' style='font-size:16px;font-weight:bold'>install.php</a>\n";
    echo "   2. Completa el proceso de instalación\n";
    echo "   3. Verifica que se instalen las 200+ tablas\n";
} else if ($total_tables < 50) {
    echo "⚠️  <span style='color:orange;font-size:18px'><b>INSTALACIÓN INCOMPLETA</b></span>\n";
    echo "   Solo hay $total_tables tablas (se esperan 200+)\n\n";
    echo "   🔧 <b>SOLUCIÓN:</b>\n";
    echo "   1. Elimina la base de datos actual\n";
    echo "   2. Re-ejecuta: <a href='/install.php' style='font-size:16px;font-weight:bold'>install.php</a>\n";
    echo "   3. Verifica que se completen los 5 archivos SQL\n";
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

echo "</pre>";
?>

<style>
    body {
        font-family: 'Courier New', monospace;
        padding: 20px;
        background: #f5f5f5;
    }
    pre {
        background: #1e1e1e;
        color: #d4d4d4;
        padding: 20px;
        border-radius: 8px;
        line-height: 1.6;
    }
    a {
        color: #4fc3f7;
        text-decoration: none;
        font-weight: bold;
    }
    a:hover {
        color: #29b6f6;
        text-decoration: underline;
    }
</style>
