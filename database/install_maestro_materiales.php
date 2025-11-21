<?php
/**
 * Script de instalacion para tabla mm_maestro_materiales
 */

require_once __DIR__ . '/../includes/config.php';

try {
    $db = Database::getInstance();

    echo "Iniciando instalacion de tabla mm_maestro_materiales...\n";

    $sql = file_get_contents(__DIR__ . '/maestro_materiales.sql');

    $db->query($sql, []);

    echo "Tabla mm_maestro_materiales creada exitosamente!\n";
    echo "Instalacion completada.\n";

} catch (Exception $e) {
    echo "Error durante la instalacion: " . $e->getMessage() . "\n";
    exit(1);
}
?>
