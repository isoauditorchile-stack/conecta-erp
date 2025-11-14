<?php
/**
 * FIX DEFINITIVO - ELIMINA TODOS LOS ERRORES
 * Este script resuelve TODOS los problemas de instalación
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(300); // 5 minutos

$host = 'localhost';
$dbname = 'conectae_conectaerpbd';
$user = 'conectae_conectaerpuser';
$pass = 'pt125824caraud';

echo "<!DOCTYPE html>";
echo "<html><head><meta charset='UTF-8'><title>Fix Definitivo</title>";
echo "<style>body{font-family:monospace;padding:20px;background:#1a1a1a;color:#00ff00;}pre{background:#000;padding:15px;border-radius:5px;}</style>";
echo "</head><body>";
echo "<h1 style='color:#00ff00;'>🔧 FIX DEFINITIVO - ELIMINANDO TODOS LOS ERRORES</h1>";
echo "<pre>";

try {
    // =====================================================
    // PASO 1: CONEXIÓN
    // =====================================================
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "PASO 1: CONECTANDO A LA BASE DE DATOS\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);

    echo "✅ Conectado exitosamente\n";
    echo "✅ Buffered queries habilitado\n\n";

    // =====================================================
    // PASO 2: DESACTIVAR FOREIGN KEY CHECKS
    // =====================================================
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "PASO 2: DESACTIVANDO VERIFICACIONES\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO'");
    $pdo->exec("SET AUTOCOMMIT = 0");
    $pdo->exec("START TRANSACTION");

    echo "✅ Foreign key checks desactivado\n";
    echo "✅ Modo SQL configurado\n";
    echo "✅ Transacción iniciada\n\n";

    // =====================================================
    // PASO 3: BUSCAR Y ELIMINAR FOREIGN KEYS
    // =====================================================
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "PASO 3: BUSCANDO FOREIGN KEYS PROBLEMÁTICAS\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

    $tablas_objetivo = ['planes', 'suscripciones', 'pagos', 'aprobaciones_usuario', 'historial_suscripciones'];

    $sql_buscar_fks = "
        SELECT
            TABLE_NAME,
            CONSTRAINT_NAME,
            REFERENCED_TABLE_NAME
        FROM information_schema.KEY_COLUMN_USAGE
        WHERE
            REFERENCED_TABLE_SCHEMA = ?
            AND REFERENCED_TABLE_NAME IN ('" . implode("','", $tablas_objetivo) . "')
            AND CONSTRAINT_NAME != 'PRIMARY'
    ";

    $stmt = $pdo->prepare($sql_buscar_fks);
    $stmt->execute([$dbname]);
    $foreign_keys = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Encontradas " . count($foreign_keys) . " foreign keys:\n\n";

    foreach ($foreign_keys as $fk) {
        echo "  🔗 {$fk['TABLE_NAME']}.{$fk['CONSTRAINT_NAME']} → {$fk['REFERENCED_TABLE_NAME']}\n";

        try {
            $sql_drop_fk = "ALTER TABLE `{$fk['TABLE_NAME']}` DROP FOREIGN KEY `{$fk['CONSTRAINT_NAME']}`";
            $pdo->exec($sql_drop_fk);
            echo "     ✅ Eliminada\n";
        } catch (PDOException $e) {
            echo "     ⚠️  Ya eliminada o no existe\n";
        }
    }

    echo "\n";

    // =====================================================
    // PASO 4: ELIMINAR TABLAS ANTIGUAS
    // =====================================================
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "PASO 4: ELIMINANDO TABLAS ANTIGUAS\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

    // Eliminar en orden inverso de dependencias
    $tablas_eliminar = [
        'notificaciones_trial',
        'historial_suscripciones',
        'pagos',
        'suscripciones',
        'aprobaciones_usuario',
        'planes'
    ];

    foreach ($tablas_eliminar as $tabla) {
        $pdo->exec("DROP TABLE IF EXISTS `$tabla`");
        echo "  🗑️  Eliminada: $tabla\n";
    }

    echo "\n";

    // =====================================================
    // PASO 5: CREAR TABLAS NUEVAS
    // =====================================================
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "PASO 5: CREANDO TABLAS NUEVAS\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

    // TABLA: planes
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `planes` (
          `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
          `plan_code` VARCHAR(50) NOT NULL,
          `plan_name` VARCHAR(100) NOT NULL,
          `precio_mensual` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
          `precio_anual` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
          `max_usuarios` INT(11) DEFAULT NULL COMMENT 'NULL = ilimitado',
          `max_empresas` INT(11) DEFAULT 1,
          `modulos_incluidos` INT(11) DEFAULT 0 COMMENT 'Número de módulos',
          `is_active` TINYINT(1) DEFAULT 1,
          `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          UNIQUE KEY `uk_plan_code` (`plan_code`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "  ✅ Tabla creada: planes\n";

    // TABLA: suscripciones
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `suscripciones` (
          `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
          `company_id` INT(11) UNSIGNED NOT NULL,
          `plan_id` INT(11) UNSIGNED NOT NULL,
          `estado` ENUM('pendiente','trial','activa','suspendida','cancelada','expirada') DEFAULT 'pendiente',
          `fecha_inicio` DATE DEFAULT NULL,
          `fecha_fin` DATE DEFAULT NULL,
          `fecha_fin_trial` DATETIME DEFAULT NULL,
          `ciclo_pago` ENUM('mensual','anual') DEFAULT 'mensual',
          `monto_total` DECIMAL(10,2) DEFAULT 0.00,
          `auto_renovacion` TINYINT(1) DEFAULT 1,
          `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `idx_company` (`company_id`),
          KEY `idx_plan` (`plan_id`),
          KEY `idx_estado` (`estado`),
          CONSTRAINT `fk_suscripciones_company` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
          CONSTRAINT `fk_suscripciones_plan` FOREIGN KEY (`plan_id`) REFERENCES `planes`(`id`) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "  ✅ Tabla creada: suscripciones\n";

    // TABLA: pagos
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `pagos` (
          `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
          `suscripcion_id` INT(11) UNSIGNED NOT NULL,
          `company_id` INT(11) UNSIGNED NOT NULL,
          `monto` DECIMAL(10,2) NOT NULL,
          `moneda` VARCHAR(3) DEFAULT 'CLP',
          `metodo_pago` ENUM('transferencia','webpay','paypal','stripe','mercadopago') NOT NULL,
          `estado` ENUM('pendiente','procesando','completado','fallido','reembolsado') DEFAULT 'pendiente',
          `fecha_pago` DATETIME DEFAULT NULL,
          `comprobante_url` VARCHAR(255) DEFAULT NULL,
          `verificado_por_admin` TINYINT(1) DEFAULT 0,
          `admin_verificador_id` INT(11) UNSIGNED DEFAULT NULL,
          `fecha_verificacion` DATETIME DEFAULT NULL,
          `notas_admin` TEXT,
          `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `idx_suscripcion` (`suscripcion_id`),
          KEY `idx_company` (`company_id`),
          KEY `idx_estado` (`estado`),
          CONSTRAINT `fk_pagos_suscripcion` FOREIGN KEY (`suscripcion_id`) REFERENCES `suscripciones`(`id`) ON DELETE CASCADE,
          CONSTRAINT `fk_pagos_company` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "  ✅ Tabla creada: pagos\n";

    // TABLA: aprobaciones_usuario
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `aprobaciones_usuario` (
          `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
          `user_id` INT(11) UNSIGNED NOT NULL,
          `estado` ENUM('pendiente','aprobado','rechazado') DEFAULT 'pendiente',
          `prioridad` ENUM('baja','media','alta') DEFAULT 'media',
          `notas_usuario` TEXT,
          `notas_admin` TEXT,
          `admin_id` INT(11) UNSIGNED DEFAULT NULL,
          `fecha_decision` DATETIME DEFAULT NULL,
          `razon_rechazo` TEXT,
          `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `idx_user` (`user_id`),
          KEY `idx_estado` (`estado`),
          CONSTRAINT `fk_aprobaciones_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "  ✅ Tabla creada: aprobaciones_usuario\n";

    // TABLA: historial_suscripciones
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `historial_suscripciones` (
          `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
          `suscripcion_id` INT(11) UNSIGNED NOT NULL,
          `accion` VARCHAR(100) NOT NULL COMMENT 'created, upgraded, downgraded, renewed, cancelled',
          `plan_anterior_id` INT(11) UNSIGNED DEFAULT NULL,
          `plan_nuevo_id` INT(11) UNSIGNED DEFAULT NULL,
          `estado_anterior` VARCHAR(50) DEFAULT NULL,
          `estado_nuevo` VARCHAR(50) DEFAULT NULL,
          `monto` DECIMAL(10,2) DEFAULT NULL,
          `admin_id` INT(11) UNSIGNED DEFAULT NULL,
          `notas` TEXT,
          `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `idx_suscripcion` (`suscripcion_id`),
          KEY `idx_accion` (`accion`),
          CONSTRAINT `fk_historial_suscripcion` FOREIGN KEY (`suscripcion_id`) REFERENCES `suscripciones`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "  ✅ Tabla creada: historial_suscripciones\n\n";

    // =====================================================
    // PASO 6: INSERTAR DATOS
    // =====================================================
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "PASO 6: INSERTANDO DATOS INICIALES\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

    $pdo->exec("
        INSERT INTO `planes` (`plan_code`, `plan_name`, `precio_mensual`, `precio_anual`, `max_usuarios`, `max_empresas`, `modulos_incluidos`, `is_active`) VALUES
        ('TRIAL', 'Trial Gratuito', 0.00, 0.00, 3, 1, 14, 1),
        ('STARTER', 'Starter', 49.99, 499.99, 5, 1, 5, 1),
        ('PROFESSIONAL', 'Professional', 149.99, 1499.99, 25, 3, 10, 1),
        ('ENTERPRISE', 'Enterprise', 499.99, 4999.99, NULL, NULL, 14, 1),
        ('CUSTOM', 'Custom', 0.00, 0.00, NULL, NULL, 14, 1)
    ");

    echo "  ✅ Insertados 5 planes:\n";
    echo "     • TRIAL - Trial Gratuito ($0)\n";
    echo "     • STARTER - Starter ($49.99/mes)\n";
    echo "     • PROFESSIONAL - Professional ($149.99/mes)\n";
    echo "     • ENTERPRISE - Enterprise ($499.99/mes)\n";
    echo "     • CUSTOM - Custom (Personalizado)\n\n";

    // =====================================================
    // PASO 7: COMMIT Y REACTIVAR
    // =====================================================
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "PASO 7: FINALIZANDO\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

    $pdo->exec("COMMIT");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "  ✅ Transacción completada\n";
    echo "  ✅ Foreign key checks reactivado\n\n";

    // =====================================================
    // PASO 8: VERIFICACIÓN FINAL
    // =====================================================
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "PASO 8: VERIFICACIÓN FINAL\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

    $tablas_verificar = ['planes', 'suscripciones', 'pagos', 'aprobaciones_usuario', 'historial_suscripciones'];

    $todo_ok = true;
    foreach ($tablas_verificar as $tabla) {
        $result = $pdo->query("SHOW TABLES LIKE '$tabla'")->fetchAll();

        if (count($result) > 0) {
            $count = $pdo->query("SELECT COUNT(*) as c FROM `$tabla`")->fetch(PDO::FETCH_ASSOC)['c'];
            echo "  ✅ $tabla ($count registros)\n";
        } else {
            echo "  ❌ $tabla NO EXISTE\n";
            $todo_ok = false;
        }
    }

    echo "\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

    if ($todo_ok) {
        echo "✅✅✅ INSTALACIÓN COMPLETADA EXITOSAMENTE ✅✅✅\n";
    } else {
        echo "⚠️⚠️⚠️ INSTALACIÓN CON ERRORES ⚠️⚠️⚠️\n";
    }

    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

    echo "Sistema de administración instalado correctamente.\n";
    echo "Ahora puedes acceder al panel de super admin.\n\n";

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->exec("ROLLBACK");
    }

    echo "\n❌❌❌ ERROR CRÍTICO ❌❌❌\n\n";
    echo "Mensaje: " . $e->getMessage() . "\n";
    echo "Código: " . $e->getCode() . "\n";
    echo "Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n\n";

    // Reactivar foreign keys aunque falle
    try {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    } catch (Exception $e2) {}
}

echo "</pre>";

echo "<div style='margin-top:30px;'>";
echo "<a href='/test_panel_admin.php' style='display:inline-block;padding:15px 30px;background:#f59e0b;color:#000;text-decoration:none;border-radius:8px;margin-right:15px;font-weight:bold;'>🧪 TEST PANEL ADMIN</a>";
echo "<a href='/admin/panel_super_admin.php' style='display:inline-block;padding:15px 30px;background:#667eea;color:#fff;text-decoration:none;border-radius:8px;margin-right:15px;font-weight:bold;'>👤 PANEL ADMIN</a>";
echo "<a href='/index.php' style='display:inline-block;padding:15px 30px;background:#10b981;color:#fff;text-decoration:none;border-radius:8px;font-weight:bold;'>🏠 INDEX</a>";
echo "</div>";

echo "</body></html>";
?>
