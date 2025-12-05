<?php
/**
 * MIGRACIÓN: Agregar columnas faltantes a iso27001_policies
 * EJECUTAR: https://isogestion.conectaerp.com/auditool/database/migrate_policies_columns.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(300);

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Migración: Políticas</title>";
echo "<style>
body { font-family: Arial; padding: 20px; background: #f5f5f5; }
.container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
h1 { color: #333; border-bottom: 3px solid #007bff; padding-bottom: 10px; }
.success { background: #d4edda; padding: 15px; border-left: 5px solid #28a745; margin: 10px 0; }
.error { background: #f8d7da; padding: 15px; border-left: 5px solid #dc3545; margin: 10px 0; }
.info { background: #d1ecf1; padding: 15px; border-left: 5px solid #17a2b8; margin: 10px 0; }
</style></head><body><div class='container'>";

echo "<h1>🔧 Migración: Agregar Columnas a iso27001_policies</h1>";

define('DB_HOST', 'localhost');
define('DB_USER', 'conectae_isogestionuser');
define('DB_PASS', 'pt125824caraud');
define('DB_NAME', 'conectae_isogestionbd');

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    if ($conn->connect_error) {
        throw new Exception("Error de conexión: " . $conn->connect_error);
    }

    echo "<div class='success'>✓ Conectado a la base de datos</div>";

    // Columnas a agregar
    $columns_to_add = [
        "ADD COLUMN `policy_type` enum('politica','procedimiento','directriz','estandar') DEFAULT 'politica' AFTER `policy_category`",
        "ADD COLUMN `responsible` varchar(255) DEFAULT NULL AFTER `next_review`",
        "ADD COLUMN `related_controls` varchar(500) DEFAULT NULL AFTER `responsible`",
        "ADD COLUMN `related_procedures` text DEFAULT NULL AFTER `related_controls`",
        "ADD COLUMN `distribution_list` text DEFAULT NULL AFTER `related_procedures`",
        "ADD COLUMN `revision_history` text DEFAULT NULL AFTER `distribution_list`",
        "ADD COLUMN `compliance_requirements` text DEFAULT NULL AFTER `revision_history`",
        "ADD COLUMN `training_required` enum('no','si') DEFAULT 'no' AFTER `compliance_requirements`",
        "ADD COLUMN `acknowledgment_required` enum('no','si') DEFAULT 'no' AFTER `training_required`"
    ];

    $added = 0;
    $skipped = 0;

    foreach ($columns_to_add as $alter_sql) {
        // Extraer nombre de columna
        preg_match('/ADD COLUMN `([^`]+)`/', $alter_sql, $matches);
        $column_name = $matches[1] ?? 'desconocida';

        // Verificar si la columna ya existe
        $check = $conn->query("SHOW COLUMNS FROM iso27001_policies LIKE '$column_name'");

        if ($check && $check->num_rows > 0) {
            echo "<div class='info'>⚠ Columna ya existe: <strong>$column_name</strong> (omitida)</div>";
            $skipped++;
        } else {
            $full_sql = "ALTER TABLE iso27001_policies $alter_sql";
            if ($conn->query($full_sql)) {
                echo "<div class='success'>✓ Columna agregada: <strong>$column_name</strong></div>";
                $added++;
            } else {
                echo "<div class='error'>✗ Error al agregar <strong>$column_name</strong>: " . $conn->error . "</div>";
            }
        }
    }

    echo "<div class='info'><h3>📊 Resumen:</h3>";
    echo "<p>Columnas agregadas: <strong>$added</strong></p>";
    echo "<p>Columnas omitidas: <strong>$skipped</strong></p>";
    echo "</div>";

    echo "<div class='success'><h3>✓ MIGRACIÓN COMPLETADA</h3>";
    echo "<p>La tabla iso27001_policies ahora tiene todas las columnas necesarias.</p>";
    echo "<p><a href='../iso_27001/politicas.php' style='padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;'>Ir a Políticas →</a></p>";
    echo "</div>";

    $conn->close();

} catch (Exception $e) {
    echo "<div class='error'>✗ ERROR: " . $e->getMessage() . "</div>";
}

echo "</div></body></html>";
?>
