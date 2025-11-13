<?php
/**
 * CONECTA ERP - INSTALADOR COMPLETO DEL SISTEMA
 * Ejecuta TODOS los scripts SQL en el orden correcto
 *
 * IMPORTANTE: Ejecutar UNA SOLA VEZ para instalar el sistema desde cero
 */

echo "╔═══════════════════════════════════════════════════════════╗\n";
echo "║       CONECTA ERP v2.0.0 - INSTALADOR COMPLETO           ║\n";
echo "║       Sistema de Producción REAL                         ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n\n";

// Configuración de conexión
$host = 'localhost';
$database = 'conectae_conectaerpbd';
$username = 'conectae_conectaerpuser';
$password = 'pt125824caraud';

echo "Conectando a la base de datos...\n";
echo "  Host: $host\n";
echo "  Base de datos: $database\n";
echo "  Usuario: $username\n\n";

try {
    // Conectar a MySQL
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Crear base de datos si no existe
    echo "1. Verificando base de datos '$database'...\n";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$database`");
    echo "   ✓ Base de datos lista\n\n";

    // Lista de scripts SQL en orden
    $scripts = [
        'database/schema_completo_base.sql' => 'Tablas base (modules, submodules, users)',
        'database/schema_improvements.sql' => 'Mejoras multi-país/idioma',
        'database/schema_planes_pagos.sql' => 'Sistema de planes y pagos'
    ];

    $total_scripts = count($scripts);
    $ejecutados = 0;
    $errores = 0;

    foreach ($scripts as $file => $descripcion) {
        echo "═══════════════════════════════════════════════════════════\n";
        echo "Script " . ($ejecutados + 1) . "/$total_scripts: $descripcion\n";
        echo "Archivo: $file\n";
        echo "═══════════════════════════════════════════════════════════\n";

        $filepath = __DIR__ . '/' . $file;

        if (!file_exists($filepath)) {
            echo "⚠ ADVERTENCIA: Archivo no encontrado - $file\n";
            echo "   Continuando con siguiente script...\n\n";
            continue;
        }

        // Leer archivo SQL
        $sql = file_get_contents($filepath);

        // Separar por declaraciones (punto y coma)
        $statements = array_filter(
            array_map('trim', preg_split('/;[\r\n]+/', $sql)),
            function($stmt) {
                // Filtrar comentarios y líneas vacías
                return !empty($stmt)
                    && !preg_match('/^--/', $stmt)
                    && !preg_match('/^\/\*/', $stmt)
                    && strlen(trim($stmt)) > 0;
            }
        );

        $total_stmt = count($statements);
        $success = 0;
        $skip = 0;

        echo "  Total de declaraciones: $total_stmt\n";
        echo "  Ejecutando";

        foreach ($statements as $statement) {
            try {
                // Limpiar statement
                $statement = trim($statement);
                if (empty($statement)) continue;

                $pdo->exec($statement);
                echo ".";
                $success++;
            } catch (PDOException $e) {
                // Ignorar errores de "ya existe"
                if (
                    strpos($e->getMessage(), 'already exists') !== false ||
                    strpos($e->getMessage(), 'Duplicate entry') !== false ||
                    strpos($e->getMessage(), 'Duplicate key') !== false
                ) {
                    echo "s";
                    $skip++;
                } else {
                    echo "\n  ✗ Error: " . $e->getMessage() . "\n";
                    echo "  Statement: " . substr($statement, 0, 100) . "...\n";
                    $errores++;
                }
            }
        }

        echo "\n";
        echo "  ✓ Ejecutadas exitosas: $success\n";
        if ($skip > 0) echo "  - Omitidas (ya existen): $skip\n";
        if ($errores > 0) echo "  ✗ Errores: $errores\n";
        echo "\n";

        $ejecutados++;
    }

    echo "╔═══════════════════════════════════════════════════════════╗\n";
    echo "║  RESUMEN DE INSTALACIÓN                                  ║\n";
    echo "╠═══════════════════════════════════════════════════════════╣\n";
    echo "║  Scripts ejecutados:    " . str_pad($ejecutados, 4, ' ', STR_PAD_LEFT) . "                                 ║\n";

    if ($errores > 0) {
        echo "║  Errores encontrados:   " . str_pad($errores, 4, ' ', STR_PAD_LEFT) . "                                 ║\n";
    }

    echo "╚═══════════════════════════════════════════════════════════╝\n\n";

    // Verificar instalación
    echo "═══════════════════════════════════════════════════════════\n";
    echo "VERIFICACIÓN DE INSTALACIÓN\n";
    echo "═══════════════════════════════════════════════════════════\n";

    $tablas_esperadas = [
        'modules' => 'Módulos principales',
        'submodules' => 'Submódulos',
        'users' => 'Usuarios',
        'countries' => 'Países',
        'companies' => 'Empresas',
        'planes' => 'Planes de suscripción',
        'suscripciones' => 'Suscripciones',
        'pagos' => 'Pagos',
        'aprobaciones_usuario' => 'Aprobaciones',
        'notificaciones_trial' => 'Notificaciones',
        'translations' => 'Traducciones'
    ];

    $tablas_ok = 0;
    $tablas_faltantes = 0;

    foreach ($tablas_esperadas as $tabla => $descripcion) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM `$tabla`");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $count = $row['total'];
            echo "  ✓ $tabla: $count registros - $descripcion\n";
            $tablas_ok++;
        } catch (PDOException $e) {
            echo "  ✗ $tabla: NO EXISTE - $descripcion\n";
            $tablas_faltantes++;
        }
    }

    echo "\n";

    if ($tablas_faltantes === 0) {
        echo "╔═══════════════════════════════════════════════════════════╗\n";
        echo "║  ✅ INSTALACIÓN COMPLETADA EXITOSAMENTE                  ║\n";
        echo "╠═══════════════════════════════════════════════════════════╣\n";
        echo "║  Todas las tablas fueron creadas correctamente          ║\n";
        echo "║                                                          ║\n";
        echo "║  PRÓXIMOS PASOS:                                         ║\n";
        echo "║                                                          ║\n";
        echo "║  1. Accede a: http://tu-dominio/index_con_planes.php    ║\n";
        echo "║  2. Regístrate como auditorexchile@gmail.com            ║\n";
        echo "║  3. Accede al panel: /admin/panel_super_admin.php       ║\n";
        echo "║                                                          ║\n";
        echo "║  El sistema está listo para producción!                 ║\n";
        echo "╚═══════════════════════════════════════════════════════════╝\n\n";
    } else {
        echo "╔═══════════════════════════════════════════════════════════╗\n";
        echo "║  ⚠ INSTALACIÓN INCOMPLETA                                ║\n";
        echo "╠═══════════════════════════════════════════════════════════╣\n";
        echo "║  Faltan $tablas_faltantes tablas por crear                            ║\n";
        echo "║  Revisa los errores arriba                               ║\n";
        echo "╚═══════════════════════════════════════════════════════════╝\n\n";
    }

} catch (PDOException $e) {
    echo "\n╔═══════════════════════════════════════════════════════════╗\n";
    echo "║  ✗ ERROR DE CONEXIÓN                                     ║\n";
    echo "╠═══════════════════════════════════════════════════════════╣\n";
    echo "║  " . str_pad($e->getMessage(), 58) . "║\n";
    echo "║                                                          ║\n";
    echo "║  Verifica:                                               ║\n";
    echo "║  • Host MySQL está corriendo                             ║\n";
    echo "║  • Credenciales son correctas                            ║\n";
    echo "║  • Usuario tiene permisos CREATE DATABASE                ║\n";
    echo "╚═══════════════════════════════════════════════════════════╝\n\n";
    exit(1);
}
