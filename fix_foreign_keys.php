<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'localhost';
$dbname = 'conectae_conectaerpbd';
$user = 'conectae_conectaerpuser';
$pass = 'pt125824caraud';

echo "<h2>🔧 LIMPIEZA DE FOREIGN KEYS Y REINSTALACIÓN</h2>";
echo "<pre>";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);

    echo "✅ Conectado a la base de datos\n\n";

    // Tablas que queremos eliminar
    $tables_to_drop = ['planes', 'suscripciones', 'pagos', 'aprobaciones_usuario', 'historial_suscripciones'];

    // 1. Buscar TODAS las foreign keys que apuntan a estas tablas
    echo "1️⃣ Buscando foreign keys que referencian a las tablas...\n";

    $foreign_keys_to_drop = [];

    foreach ($tables_to_drop as $table) {
        // Buscar constraints que referencian esta tabla
        $sql = "
            SELECT
                TABLE_NAME,
                CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE
                REFERENCED_TABLE_SCHEMA = '$dbname'
                AND REFERENCED_TABLE_NAME = '$table'
                AND TABLE_NAME != '$table'
        ";

        $result = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        foreach ($result as $row) {
            $fk_table = $row['TABLE_NAME'];
            $fk_constraint = $row['CONSTRAINT_NAME'];

            $foreign_keys_to_drop[] = [
                'table' => $fk_table,
                'constraint' => $fk_constraint,
                'references' => $table
            ];

            echo "   🔗 Encontrado: {$fk_table}.{$fk_constraint} → {$table}\n";
        }
    }

    echo "\n   Total foreign keys encontradas: " . count($foreign_keys_to_drop) . "\n\n";

    // 2. Eliminar foreign keys
    if (count($foreign_keys_to_drop) > 0) {
        echo "2️⃣ Eliminando foreign keys...\n";

        foreach ($foreign_keys_to_drop as $fk) {
            try {
                $sql = "ALTER TABLE `{$fk['table']}` DROP FOREIGN KEY `{$fk['constraint']}`";
                $pdo->exec($sql);
                echo "   ✅ Eliminado: {$fk['table']}.{$fk['constraint']}\n";
            } catch (PDOException $e) {
                echo "   ⚠️  Error al eliminar {$fk['table']}.{$fk['constraint']}: " . $e->getMessage() . "\n";
            }
        }
        echo "\n";
    } else {
        echo "2️⃣ No hay foreign keys que eliminar\n\n";
    }

    // 3. Ahora intentar eliminar las tablas
    echo "3️⃣ Eliminando tablas antiguas...\n";

    // Desactivar foreign key checks por si acaso
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    // Orden correcto: de más dependiente a menos dependiente
    $drop_order = [
        'historial_suscripciones',
        'pagos',
        'suscripciones',
        'aprobaciones_usuario',
        'planes'
    ];

    foreach ($drop_order as $table) {
        try {
            $pdo->exec("DROP TABLE IF EXISTS `$table`");
            echo "   🗑️  Tabla eliminada: $table\n";
        } catch (PDOException $e) {
            echo "   ❌ Error al eliminar $table: " . $e->getMessage() . "\n";
        }
    }

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "\n";

    // 4. Crear nuevas tablas
    echo "4️⃣ Creando nuevas tablas...\n";

    // Tabla planes
    $pdo->exec("
        CREATE TABLE `planes` (
          `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
          `plan_code` VARCHAR(50) NOT NULL,
          `plan_name` VARCHAR(100) NOT NULL,
          `precio_mensual` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
          `precio_anual` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
          `max_usuarios` INT(11) DEFAULT NULL,
          `max_empresas` INT(11) DEFAULT 1,
          `modulos_incluidos` INT(11) DEFAULT 0,
          `is_active` TINYINT(1) DEFAULT 1,
          `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          UNIQUE KEY `plan_code` (`plan_code`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "   ✅ Tabla creada: planes\n";

    // Insertar planes
    $pdo->exec("
        INSERT INTO `planes` (`plan_code`, `plan_name`, `precio_mensual`, `precio_anual`, `max_usuarios`, `max_empresas`, `modulos_incluidos`, `is_active`) VALUES
        ('TRIAL', 'Trial Gratuito', 0.00, 0.00, 3, 1, 14, 1),
        ('STARTER', 'Starter', 49.99, 499.99, 5, 1, 5, 1),
        ('PROFESSIONAL', 'Professional', 149.99, 1499.99, 25, 3, 10, 1),
        ('ENTERPRISE', 'Enterprise', 499.99, 4999.99, NULL, NULL, 14, 1),
        ('CUSTOM', 'Custom', 0.00, 0.00, NULL, NULL, 14, 1)
    ");
    echo "   ✅ Datos insertados en planes (5 registros)\n";

    // Tabla suscripciones
    $pdo->exec("
        CREATE TABLE `suscripciones` (
          `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
          `company_id` INT(11) UNSIGNED NOT NULL,
          `plan_id` INT(11) UNSIGNED NOT NULL,
          `estado` ENUM('pendiente', 'trial', 'activa', 'suspendida', 'cancelada', 'expirada') DEFAULT 'pendiente',
          `fecha_inicio` DATE,
          `fecha_fin` DATE,
          `fecha_fin_trial` DATETIME,
          `ciclo_pago` ENUM('mensual', 'anual') DEFAULT 'mensual',
          `monto_total` DECIMAL(10,2) DEFAULT 0.00,
          `auto_renovacion` TINYINT(1) DEFAULT 1,
          `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `idx_company` (`company_id`),
          KEY `idx_plan` (`plan_id`),
          KEY `idx_estado` (`estado`),
          CONSTRAINT `fk_suscripciones_company` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
          CONSTRAINT `fk_suscripciones_plan` FOREIGN KEY (`plan_id`) REFERENCES `planes`(`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "   ✅ Tabla creada: suscripciones\n";

    // Tabla pagos
    $pdo->exec("
        CREATE TABLE `pagos` (
          `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
          `suscripcion_id` INT(11) UNSIGNED NOT NULL,
          `company_id` INT(11) UNSIGNED NOT NULL,
          `monto` DECIMAL(10,2) NOT NULL,
          `moneda` VARCHAR(3) DEFAULT 'CLP',
          `metodo_pago` ENUM('transferencia', 'webpay', 'paypal', 'stripe', 'mercadopago') NOT NULL,
          `estado` ENUM('pendiente', 'procesando', 'completado', 'fallido', 'reembolsado') DEFAULT 'pendiente',
          `fecha_pago` DATETIME,
          `comprobante_url` VARCHAR(255),
          `verificado_por_admin` TINYINT(1) DEFAULT 0,
          `admin_verificador_id` INT(11) UNSIGNED,
          `fecha_verificacion` DATETIME,
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
    echo "   ✅ Tabla creada: pagos\n";

    // Tabla aprobaciones_usuario
    $pdo->exec("
        CREATE TABLE `aprobaciones_usuario` (
          `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
          `user_id` INT(11) UNSIGNED NOT NULL,
          `estado` ENUM('pendiente', 'aprobado', 'rechazado') DEFAULT 'pendiente',
          `prioridad` ENUM('baja', 'media', 'alta') DEFAULT 'media',
          `notas_usuario` TEXT,
          `notas_admin` TEXT,
          `admin_id` INT(11) UNSIGNED,
          `fecha_decision` DATETIME,
          `razon_rechazo` TEXT,
          `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `idx_user` (`user_id`),
          KEY `idx_estado` (`estado`),
          CONSTRAINT `fk_aprobaciones_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "   ✅ Tabla creada: aprobaciones_usuario\n";

    // Tabla historial_suscripciones
    $pdo->exec("
        CREATE TABLE `historial_suscripciones` (
          `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
          `suscripcion_id` INT(11) UNSIGNED NOT NULL,
          `accion` VARCHAR(100) NOT NULL,
          `plan_anterior_id` INT(11) UNSIGNED,
          `plan_nuevo_id` INT(11) UNSIGNED,
          `estado_anterior` VARCHAR(50),
          `estado_nuevo` VARCHAR(50),
          `monto` DECIMAL(10,2),
          `admin_id` INT(11) UNSIGNED,
          `notas` TEXT,
          `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `idx_suscripcion` (`suscripcion_id`),
          KEY `idx_accion` (`accion`),
          CONSTRAINT `fk_historial_suscripcion` FOREIGN KEY (`suscripcion_id`) REFERENCES `suscripciones`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "   ✅ Tabla creada: historial_suscripciones\n\n";

    // 5. Verificar
    echo "5️⃣ Verificando instalación...\n";
    $required_tables = ['planes', 'suscripciones', 'pagos', 'aprobaciones_usuario', 'historial_suscripciones'];

    foreach ($required_tables as $table) {
        $result = $pdo->query("SHOW TABLES LIKE '$table'")->fetchAll();
        if (count($result) > 0) {
            $count = $pdo->query("SELECT COUNT(*) as c FROM `$table`")->fetch()['c'];
            echo "   ✅ $table ($count registros)\n";
        } else {
            echo "   ❌ $table NO EXISTE\n";
        }
    }

    echo "\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "✅ INSTALACIÓN COMPLETADA EXITOSAMENTE\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

    echo "Tablas instaladas:\n";
    echo "  • planes (5 planes configurados)\n";
    echo "  • suscripciones (gestión completa)\n";
    echo "  • pagos (tracking completo)\n";
    echo "  • aprobaciones_usuario (workflow)\n";
    echo "  • historial_suscripciones (auditoría)\n\n";

    echo "Ahora puedes:\n";
    echo "1. Ejecutar test_panel_admin.php para verificar\n";
    echo "2. Acceder al panel de super admin\n\n";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "   Traza: " . $e->getTraceAsString() . "\n";
}

echo "</pre>";
echo "<a href='/test_panel_admin.php' style='padding:10px 20px; background:#f59e0b; color:white; text-decoration:none; border-radius:5px; margin-right:10px;'>Test Panel Admin</a>";
echo "<a href='/admin/panel_super_admin.php' style='padding:10px 20px; background:#667eea; color:white; text-decoration:none; border-radius:5px; margin-right:10px;'>Panel Admin</a>";
echo "<a href='/index.php' style='padding:10px 20px; background:#4CAF50; color:white; text-decoration:none; border-radius:5px;'>Index</a>";
?>
