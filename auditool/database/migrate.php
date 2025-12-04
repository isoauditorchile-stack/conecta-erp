<?php
/**
 * AUDITOR PRO - Script de Migración
 * Agregar columnas faltantes a tablas existentes
 * EJECUTAR UNA SOLA VEZ desde el navegador: /auditool/database/migrate.php
 */

require_once('../config/config.php');

echo "<h1>AUDITOR PRO - Migración de Base de Datos</h1>";
echo "<p>Agregando columnas faltantes...</p><br>";

$conn = getDBConnection();

// Lista de migraciones a ejecutar
$migrations = [];

// 1. Renombrar column description a asset_description en iso27001_assets
$migrations[] = [
    'descripcion' => 'Renombrar description a asset_description en iso27001_assets',
    'query' => "ALTER TABLE `iso27001_assets` CHANGE COLUMN `description` `asset_description` text DEFAULT NULL"
];

// 2. Agregar acquisition_date si no existe
$migrations[] = [
    'descripcion' => 'Agregar columna acquisition_date',
    'query' => "ALTER TABLE `iso27001_assets` ADD COLUMN `acquisition_date` date DEFAULT NULL AFTER `data_classification`"
];

// 3. Agregar estimated_value si no existe
$migrations[] = [
    'descripcion' => 'Agregar columna estimated_value',
    'query' => "ALTER TABLE `iso27001_assets` ADD COLUMN `estimated_value` decimal(15,2) DEFAULT NULL AFTER `acquisition_date`"
];

// 4. Agregar recovery_time si no existe
$migrations[] = [
    'descripcion' => 'Agregar columna recovery_time',
    'query' => "ALTER TABLE `iso27001_assets` ADD COLUMN `recovery_time` varchar(50) DEFAULT NULL AFTER `replacement_cost`"
];

$exitosos = 0;
$errores = 0;

foreach ($migrations as $migration) {
    echo "<div style='margin: 10px 0; padding: 10px; background: #f0f0f0; border-left: 4px solid #007bff;'>";
    echo "<strong>" . $migration['descripcion'] . "</strong><br>";

    try {
        if ($conn->query($migration['query'])) {
            echo "<span style='color: green;'>✓ Ejecutado exitosamente</span>";
            $exitosos++;
        } else {
            // Si falla, puede ser porque ya existe
            echo "<span style='color: orange;'>⚠ " . $conn->error . "</span>";
        }
    } catch (Exception $e) {
        echo "<span style='color: orange;'>⚠ " . $e->getMessage() . "</span>";
    }

    echo "</div>";
}

echo "<br><hr><br>";
echo "<h2>Resumen de Migración:</h2>";
echo "<p><strong>Migraciones exitosas:</strong> $exitosos</p>";
echo "<p><strong>Advertencias/Errores:</strong> " . (count($migrations) - $exitosos) . "</p>";

// Verificar estructura final
echo "<br><h2>Estructura actual de iso27001_assets:</h2>";
$result = $conn->query("DESCRIBE iso27001_assets");
echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
echo "<tr style='background: #333; color: white;'><th>Campo</th><th>Tipo</th><th>Null</th><th>Default</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td><strong>" . $row['Field'] . "</strong></td>";
    echo "<td>" . $row['Type'] . "</td>";
    echo "<td>" . $row['Null'] . "</td>";
    echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
    echo "</tr>";
}
echo "</table>";

$conn->close();

echo "<br><hr><br>";
echo "<h2 style='color: green;'>✓ Migración Completada</h2>";
echo "<p><a href='../index.php' style='padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;'>← Volver al Sistema</a></p>";
?>
