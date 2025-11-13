<?php
/**
 * Script para detectar tablas faltantes
 */

$host = 'localhost';
$dbname = 'conectae_conectaerpbd';
$user = 'conectae_conectaerpuser';
$pass = 'pt125824caraud';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Obtener todas las tablas existentes
    $stmt = $pdo->query("SHOW TABLES");
    $existing_tables = [];
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        $existing_tables[] = $row[0];
    }

    echo "Tablas existentes en la BD: " . count($existing_tables) . "\n\n";

    // Leer archivos SQL y extraer nombres de tablas
    $sql_files = [
        'database/schema_erp_completo.sql',
        'database/schema_erp_completo_parte2.sql',
        'database/schema_erp_completo_parte3.sql',
        'database/schema_erp_completo_parte4.sql',
        'database/schema_erp_completo_parte5_final.sql'
    ];

    $expected_tables = [];

    foreach ($sql_files as $file) {
        if (file_exists($file)) {
            $content = file_get_contents($file);
            preg_match_all('/CREATE TABLE (?:IF NOT EXISTS )?`?([a-zA-Z0-9_]+)`?/i', $content, $matches);
            $expected_tables = array_merge($expected_tables, $matches[1]);
        }
    }

    // También verificar schema.sql antiguo
    if (file_exists('database/schema.sql')) {
        $content = file_get_contents('database/schema.sql');
        preg_match_all('/CREATE TABLE (?:IF NOT EXISTS )?`?([a-zA-Z0-9_]+)`?/i', $content, $matches);
        foreach ($matches[1] as $table) {
            if (!in_array($table, $expected_tables)) {
                $expected_tables[] = $table;
            }
        }
    }

    $expected_tables = array_unique($expected_tables);
    sort($expected_tables);

    echo "Tablas esperadas según archivos SQL: " . count($expected_tables) . "\n\n";

    // Encontrar tablas faltantes
    $missing_tables = array_diff($expected_tables, $existing_tables);

    if (empty($missing_tables)) {
        echo "✅ No faltan tablas!\n";
    } else {
        echo "❌ Tablas faltantes (" . count($missing_tables) . "):\n";
        foreach ($missing_tables as $table) {
            echo "  - $table\n";
        }
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
