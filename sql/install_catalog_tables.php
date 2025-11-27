<?php
/**
 * SCRIPT DE INSTALACION: Tablas de Catalogos para Quick Add
 * Sistema: CONECTA ERP
 * Modulo: Maestro de Clientes
 */

// Conexion a base de datos
$host = 'localhost';
$dbname = 'conectae_conectaerpbd';
$username = 'conectae_conectaerpuser';
$password = 'pt125824caraud';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "==============================================\n";
    echo "INSTALACION DE TABLAS DE CATALOGOS\n";
    echo "==============================================\n\n";

    // Leer el archivo SQL
    $sql_file = __DIR__ . '/create_catalog_tables.sql';
    $sql_content = file_get_contents($sql_file);

    // Separar por comandos (punto y coma)
    $commands = explode(';', $sql_content);

    $success_count = 0;
    $error_count = 0;

    foreach ($commands as $command) {
        $command = trim($command);

        // Saltar lineas vacias y comentarios
        if (empty($command) || strpos($command, '--') === 0) {
            continue;
        }

        try {
            $pdo->exec($command);
            $success_count++;

            // Mostrar que tipo de comando se ejecuto
            if (stripos($command, 'CREATE TABLE') !== false) {
                preg_match('/CREATE TABLE.*?`?(\w+)`?/i', $command, $matches);
                $table_name = $matches[1] ?? 'desconocida';
                echo "✓ Tabla creada: $table_name\n";
            } elseif (stripos($command, 'INSERT') !== false) {
                preg_match('/INSERT.*?INTO\s+`?(\w+)`?/i', $command, $matches);
                $table_name = $matches[1] ?? 'desconocida';
                echo "✓ Datos insertados en: $table_name\n";
            }

        } catch (PDOException $e) {
            $error_count++;
            echo "✗ Error: " . $e->getMessage() . "\n";
        }
    }

    echo "\n==============================================\n";
    echo "RESUMEN:\n";
    echo "- Comandos exitosos: $success_count\n";
    echo "- Errores: $error_count\n";
    echo "==============================================\n";

    // Verificar que las tablas existan
    echo "\n==============================================\n";
    echo "VERIFICACION DE TABLAS:\n";
    echo "==============================================\n";

    $tables_to_check = [
        'cat_tipos_cliente',
        'cat_categorias_cliente',
        'cat_grupos_cliente',
        'cat_condiciones_pago',
        'cat_listas_precio'
    ];

    foreach ($tables_to_check as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        $exists = $stmt->fetch() !== false;

        if ($exists) {
            // Contar registros
            $count_stmt = $pdo->query("SELECT COUNT(*) as total FROM $table");
            $count = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
            echo "✓ $table - OK ($count registros)\n";
        } else {
            echo "✗ $table - NO EXISTE\n";
        }
    }

    echo "\n==============================================\n";
    echo "INSTALACION COMPLETADA\n";
    echo "==============================================\n";

} catch (PDOException $e) {
    echo "ERROR DE CONEXION: " . $e->getMessage() . "\n";
    exit(1);
}
