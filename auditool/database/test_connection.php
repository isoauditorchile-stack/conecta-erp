<?php
/**
 * AUDITOR PRO - Test de Conexión a Base de Datos
 * EJECUTAR desde navegador: /auditool/database/test_connection.php
 */

echo "<h1>🔍 AUDITOR PRO - Test de Conexión SQL</h1>";
echo "<hr>";

// Test 1: Verificar config.php
echo "<h2>1️⃣ Verificando archivo de configuración...</h2>";
if (file_exists('../config/config.php')) {
    echo "<p style='color: green;'>✓ config.php encontrado</p>";
    require_once('../config/config.php');
} else {
    die("<p style='color: red;'>✗ ERROR: config.php no encontrado</p>");
}

// Test 2: Mostrar credenciales configuradas
echo "<h2>2️⃣ Credenciales configuradas:</h2>";
echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
echo "<tr><td><strong>DB_HOST:</strong></td><td>" . DB_HOST . "</td></tr>";
echo "<tr><td><strong>DB_USER:</strong></td><td>" . DB_USER . "</td></tr>";
echo "<tr><td><strong>DB_PASS:</strong></td><td>" . str_repeat('*', strlen(DB_PASS)) . " (oculto)</td></tr>";
echo "<tr><td><strong>DB_NAME:</strong></td><td>" . DB_NAME . "</td></tr>";
echo "</table><br>";

// Test 3: Probar conexión directa con mysqli
echo "<h2>3️⃣ Probando conexión directa con mysqli...</h2>";
$conn_test = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn_test->connect_error) {
    echo "<p style='color: red; font-weight: bold;'>✗ ERROR DE CONEXIÓN:</p>";
    echo "<pre style='background: #ffe6e6; padding: 15px; border-left: 5px solid red;'>";
    echo "Error Code: " . $conn_test->connect_errno . "\n";
    echo "Error Message: " . $conn_test->connect_error;
    echo "</pre>";
    echo "<h3>Posibles causas:</h3>";
    echo "<ul>";
    echo "<li><strong>Credenciales incorrectas:</strong> Verifica usuario y contraseña en cPanel > MySQL Databases</li>";
    echo "<li><strong>Base de datos no existe:</strong> Crea la base de datos 'conectae_isogestionbd' en cPanel</li>";
    echo "<li><strong>Permisos insuficientes:</strong> Asegúrate que el usuario tenga permisos en la base de datos</li>";
    echo "</ul>";
    die();
} else {
    echo "<p style='color: green; font-weight: bold;'>✓ Conexión exitosa a MySQL</p>";
}

// Test 4: Verificar base de datos seleccionada
echo "<h2>4️⃣ Verificando base de datos seleccionada...</h2>";
$result = $conn_test->query("SELECT DATABASE() as db_name");
if ($result) {
    $row = $result->fetch_assoc();
    echo "<p style='color: green;'>✓ Base de datos activa: <strong>" . $row['db_name'] . "</strong></p>";
} else {
    echo "<p style='color: red;'>✗ No se pudo verificar la base de datos</p>";
}

// Test 5: Listar tablas existentes
echo "<h2>5️⃣ Tablas encontradas en la base de datos:</h2>";
$result = $conn_test->query("SHOW TABLES");

if ($result && $result->num_rows > 0) {
    echo "<p style='color: green;'>✓ Se encontraron " . $result->num_rows . " tablas:</p>";
    echo "<ul>";
    while ($row = $result->fetch_array()) {
        $table_name = $row[0];

        // Contar registros en la tabla
        $count_result = $conn_test->query("SELECT COUNT(*) as total FROM `$table_name`");
        $count = $count_result ? $count_result->fetch_assoc()['total'] : 0;

        // Marcar tablas ISO
        $is_iso = strpos($table_name, 'iso27001_') === 0 ? ' style="color: blue; font-weight: bold;"' : '';

        echo "<li$is_iso>$table_name ($count registros)</li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color: red; font-weight: bold;'>✗ NO SE ENCONTRARON TABLAS</p>";
    echo "<p>La base de datos está vacía. Necesitas importar el schema SQL:</p>";
    echo "<ol>";
    echo "<li>Ve a phpMyAdmin</li>";
    echo "<li>Selecciona la base de datos: <strong>conectae_isogestionbd</strong></li>";
    echo "<li>Click en 'Importar'</li>";
    echo "<li>Sube el archivo: <strong>/auditool/database/schema_iso27001.sql</strong></li>";
    echo "</ol>";
}

// Test 6: Verificar tablas ISO específicas
echo "<h2>6️⃣ Verificando tablas ISO requeridas:</h2>";
$required_tables = [
    'companies',
    'users',
    'subscriptions',
    'payments',
    'activity_logs',
    'iso27001_assets',
    'iso27001_risks',
    'iso27001_controls',
    'iso27001_incidents',
    'iso27001_policies',
    'iso27001_audits',
    'iso27001_nonconformities',
    'iso27001_templates'
];

$missing_tables = [];
foreach ($required_tables as $table) {
    $check = $conn_test->query("SHOW TABLES LIKE '$table'");
    if ($check && $check->num_rows > 0) {
        echo "<p style='color: green;'>✓ $table</p>";
    } else {
        echo "<p style='color: red;'>✗ $table <strong>(FALTANTE)</strong></p>";
        $missing_tables[] = $table;
    }
}

// Test 7: Verificar columnas de iso27001_assets
if (empty($missing_tables) || !in_array('iso27001_assets', $missing_tables)) {
    echo "<h2>7️⃣ Verificando estructura de iso27001_assets:</h2>";
    $result = $conn_test->query("DESCRIBE iso27001_assets");

    $required_columns = ['asset_description', 'acquisition_date', 'estimated_value', 'recovery_time'];
    $existing_columns = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $existing_columns[] = $row['Field'];
        }

        $missing_columns = array_diff($required_columns, $existing_columns);

        if (empty($missing_columns)) {
            echo "<p style='color: green; font-weight: bold;'>✓ Todas las columnas requeridas están presentes</p>";
        } else {
            echo "<p style='color: red; font-weight: bold;'>✗ Columnas faltantes:</p>";
            echo "<ul>";
            foreach ($missing_columns as $col) {
                echo "<li style='color: red;'>$col</li>";
            }
            echo "</ul>";
            echo "<p><strong>SOLUCIÓN:</strong> Ejecuta el script de migración:</p>";
            echo "<p><a href='migrate.php' style='padding: 10px 20px; background: #dc3545; color: white; text-decoration: none; border-radius: 5px;'>🔧 Ejecutar Migración Ahora</a></p>";
        }
    }
}

// Test 8: Probar getDBConnection()
echo "<h2>8️⃣ Probando función getDBConnection()...</h2>";
try {
    $conn_func = getDBConnection();
    if ($conn_func) {
        echo "<p style='color: green;'>✓ getDBConnection() funciona correctamente</p>";
        $conn_func->close();
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error con getDBConnection(): " . $e->getMessage() . "</p>";
}

$conn_test->close();

// Resumen final
echo "<hr>";
echo "<h2>📊 RESUMEN DEL DIAGNÓSTICO</h2>";

if (empty($missing_tables)) {
    echo "<div style='background: #d4edda; padding: 20px; border-left: 5px solid green; margin: 20px 0;'>";
    echo "<h3 style='color: green;'>✓ CONEXIÓN EXITOSA</h3>";
    echo "<p>Todas las tablas necesarias están presentes.</p>";
    echo "<p>Si aún tienes ERROR 500, ejecuta la migración para agregar columnas faltantes:</p>";
    echo "<p><a href='migrate.php' style='padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 5px;'>🔧 Ejecutar Migración</a></p>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; padding: 20px; border-left: 5px solid red; margin: 20px 0;'>";
    echo "<h3 style='color: red;'>✗ FALTAN " . count($missing_tables) . " TABLAS</h3>";
    echo "<p>Debes importar el schema SQL completo.</p>";
    echo "<p><strong>Archivo a importar:</strong> /auditool/database/schema_iso27001.sql</p>";
    echo "</div>";
}

echo "<p><a href='../index.php' style='padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;'>← Volver al Sistema</a></p>";
?>
