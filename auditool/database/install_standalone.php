<?php
/**
 * AUDITOR PRO - INSTALADOR STANDALONE
 * NO requiere config.php - Credenciales directas
 * EJECUTAR: https://isogestion.conectaerp.com/auditool/database/install_standalone.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(600);

// CREDENCIALES DIRECTAS
define('DB_HOST', 'localhost');
define('DB_USER', 'conectae_isogestionuser');
define('DB_PASS', 'pt125824caraud');
define('DB_NAME', 'conectae_isogestionbd');

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>AUDITOR PRO - Instalador</title>";
echo "<style>
body { font-family: Arial, sans-serif; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
.container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
h1 { color: #333; border-bottom: 3px solid #667eea; padding-bottom: 15px; margin-bottom: 20px; }
.success { background: #d4edda; color: #155724; padding: 12px; border-left: 5px solid #28a745; margin: 10px 0; border-radius: 5px; }
.error { background: #f8d7da; color: #721c24; padding: 12px; border-left: 5px solid #dc3545; margin: 10px 0; border-radius: 5px; }
.info { background: #d1ecf1; color: #0c5460; padding: 12px; border-left: 5px solid #17a2b8; margin: 10px 0; border-radius: 5px; }
.warning { background: #fff3cd; color: #856404; padding: 12px; border-left: 5px solid #ffc107; margin: 10px 0; border-radius: 5px; }
.btn { display: inline-block; padding: 15px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 8px; font-weight: bold; margin: 10px 5px; transition: all 0.3s; }
.btn:hover { background: #764ba2; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
.progress { background: #e9ecef; height: 30px; border-radius: 15px; margin: 20px 0; overflow: hidden; }
.progress-bar { height: 100%; background: linear-gradient(90deg, #667eea 0%, #764ba2 100%); line-height: 30px; color: white; text-align: center; font-weight: bold; transition: width 0.3s; }
</style></head><body><div class='container'>";

echo "<h1>🚀 AUDITOR PRO - Instalación de Base de Datos</h1>";

// Paso 1: Conexión
echo "<div class='info'><strong>Paso 1:</strong> Conectando a MySQL...</div>";

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS);

if ($conn->connect_error) {
    echo "<div class='error'>✗ ERROR DE CONEXIÓN: " . $conn->connect_error . "</div>";
    echo "<h3>Verifica las credenciales:</h3>";
    echo "<ul><li>Usuario: " . DB_USER . "</li><li>Host: " . DB_HOST . "</li><li>Base de datos: " . DB_NAME . "</li></ul>";
    die("</div></body></html>");
}

echo "<div class='success'>✓ Conexión exitosa a MySQL</div>";

// Paso 2: Crear/Seleccionar base de datos
echo "<div class='info'><strong>Paso 2:</strong> Creando/seleccionando base de datos...</div>";

$conn->query("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

if (!$conn->select_db(DB_NAME)) {
    echo "<div class='error'>✗ No se pudo seleccionar la base de datos: " . DB_NAME . "</div>";
    die("</div></body></html>");
}

echo "<div class='success'>✓ Base de datos seleccionada: " . DB_NAME . "</div>";

// Paso 3: Leer archivo SQL
echo "<div class='info'><strong>Paso 3:</strong> Leyendo archivo SQL...</div>";

$sql_file = __DIR__ . '/schema_iso27001.sql';

if (!file_exists($sql_file)) {
    echo "<div class='error'>✗ ERROR: No se encuentra el archivo schema_iso27001.sql en: " . __DIR__ . "</div>";
    die("</div></body></html>");
}

$sql_content = file_get_contents($sql_file);
echo "<div class='success'>✓ Archivo SQL cargado (" . number_format(strlen($sql_content)) . " caracteres)</div>";

// Paso 4: Limpiar y preparar SQL
echo "<div class='info'><strong>Paso 4:</strong> Procesando sentencias SQL...</div>";

// Eliminar comentarios
$sql_content = preg_replace('/--.*$/m', '', $sql_content);
$sql_content = preg_replace('!/\*.*?\*/!s', '', $sql_content);

// Dividir por punto y coma (pero no dentro de comillas)
$statements = [];
$current = '';
$in_string = false;
$string_char = '';

for ($i = 0; $i < strlen($sql_content); $i++) {
    $char = $sql_content[$i];

    if (($char === "'" || $char === '"') && ($i == 0 || $sql_content[$i-1] !== '\\')) {
        if (!$in_string) {
            $in_string = true;
            $string_char = $char;
        } elseif ($char === $string_char) {
            $in_string = false;
        }
    }

    if ($char === ';' && !$in_string) {
        $current = trim($current);
        if (!empty($current) && strlen($current) > 10) {
            $statements[] = $current;
        }
        $current = '';
    } else {
        $current .= $char;
    }
}

// Agregar última sentencia si existe
$current = trim($current);
if (!empty($current) && strlen($current) > 10) {
    $statements[] = $current;
}

echo "<div class='success'>✓ " . count($statements) . " sentencias SQL encontradas</div>";

// Paso 5: Ejecutar sentencias
echo "<div class='info'><strong>Paso 5:</strong> Ejecutando sentencias SQL...</div>";
echo "<div class='progress'><div class='progress-bar' id='progress' style='width: 0%'>0%</div></div>";
echo "<div id='results'>";

$total = count($statements);
$success_count = 0;
$skip_count = 0;
$error_count = 0;

foreach ($statements as $index => $stmt) {
    $stmt = trim($stmt);
    if (empty($stmt)) continue;

    $progress = round((($index + 1) / $total) * 100);

    // Ejecutar sentencia
    if ($conn->query($stmt)) {
        // Detectar tipo de sentencia
        if (preg_match('/CREATE TABLE.*`([^`]+)`/i', $stmt, $matches)) {
            echo "<div class='success'>✓ Tabla creada: <strong>" . $matches[1] . "</strong></div>";
            $success_count++;
        } elseif (preg_match('/INSERT INTO.*`([^`]+)`/i', $stmt, $matches)) {
            echo "<div class='info'>→ Datos insertados en: " . $matches[1] . "</div>";
            $success_count++;
        } elseif (preg_match('/CREATE.*TRIGGER.*`([^`]+)`/i', $stmt, $matches)) {
            echo "<div class='success'>✓ Trigger creado: " . $matches[1] . "</div>";
            $success_count++;
        } elseif (preg_match('/CREATE.*VIEW.*`([^`]+)`/i', $stmt, $matches)) {
            echo "<div class='success'>✓ Vista creada: " . $matches[1] . "</div>";
            $success_count++;
        }
    } else {
        // Verificar si el error es porque ya existe
        if (stripos($conn->error, 'already exists') !== false ||
            stripos($conn->error, 'Duplicate') !== false ||
            stripos($conn->error, 'Table') !== false && stripos($conn->error, 'already exists') !== false) {
            echo "<div class='warning'>⚠ Ya existe (omitido)</div>";
            $skip_count++;
        } else {
            echo "<div class='error'>✗ Error: " . substr($conn->error, 0, 150) . "</div>";
            $error_count++;
        }
    }

    // Actualizar barra de progreso
    echo "<script>document.getElementById('progress').style.width = '{$progress}%'; document.getElementById('progress').innerText = '{$progress}%';</script>";
    flush();
}

echo "</div>"; // Cierre de results

// Paso 6: Resumen
echo "<hr><h2>📊 RESUMEN DE INSTALACIÓN</h2>";
echo "<table style='width:100%; border-collapse: collapse;'>";
echo "<tr><td style='padding:10px; background:#d4edda;'><strong>✓ Exitosas:</strong></td><td style='padding:10px; background:#d4edda;'><strong>" . $success_count . "</strong></td></tr>";
echo "<tr><td style='padding:10px; background:#fff3cd;'><strong>⚠ Omitidas (ya existen):</strong></td><td style='padding:10px; background:#fff3cd;'><strong>" . $skip_count . "</strong></td></tr>";
echo "<tr><td style='padding:10px; background:#f8d7da;'><strong>✗ Errores:</strong></td><td style='padding:10px; background:#f8d7da;'><strong>" . $error_count . "</strong></td></tr>";
echo "</table>";

// Paso 7: Verificar tablas creadas
echo "<hr><h2>📋 TABLAS CREADAS EN LA BASE DE DATOS</h2>";

$result = $conn->query("SHOW TABLES");
if ($result && $result->num_rows > 0) {
    echo "<p><strong>Total de tablas: " . $result->num_rows . "</strong></p>";
    echo "<ul style='column-count: 2; column-gap: 20px;'>";
    while ($row = $result->fetch_array()) {
        $table = $row[0];
        $count_result = $conn->query("SELECT COUNT(*) as total FROM `$table`");
        $count = $count_result ? $count_result->fetch_assoc()['total'] : 0;

        $is_iso = strpos($table, 'iso27001_') === 0;
        $style = $is_iso ? "color: #007bff; font-weight: bold;" : "color: #666;";

        echo "<li style='$style'>$table <span style='color:#999;'>($count registros)</span></li>";
    }
    echo "</ul>";
}

$conn->close();

// Resultado final
if ($error_count == 0) {
    echo "<div class='success' style='font-size: 18px; text-align: center; padding: 20px;'>";
    echo "<h2 style='color: #28a745; margin: 0;'>✅ ¡INSTALACIÓN COMPLETADA EXITOSAMENTE!</h2>";
    echo "<p>Todas las tablas fueron creadas correctamente.</p>";
    echo "</div>";
    echo "<p style='text-align: center;'>";
    echo "<a href='../index.php' class='btn' style='background: #28a745;'>🏠 Ir a la Portada</a> ";
    echo "<a href='../login.php' class='btn'>🔐 Iniciar Sesión</a> ";
    echo "<a href='test_connection.php' class='btn' style='background: #17a2b8;'>🔍 Verificar Sistema</a>";
    echo "</p>";
} else {
    echo "<div class='error' style='font-size: 18px; text-align: center; padding: 20px;'>";
    echo "<h2 style='color: #dc3545; margin: 0;'>⚠️ INSTALACIÓN COMPLETADA CON ERRORES</h2>";
    echo "<p>Algunas tablas no pudieron crearse. Revisa los errores arriba.</p>";
    echo "</div>";
    echo "<p style='text-align: center;'>";
    echo "<a href='test_connection.php' class='btn' style='background: #dc3545;'>🔍 Diagnóstico Completo</a>";
    echo "</p>";
}

echo "</div></body></html>";
?>
