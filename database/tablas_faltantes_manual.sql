-- =====================================================
-- CONECTA ERP - TABLAS FALTANTES PARA INSTALACIÓN MANUAL
-- =====================================================
-- Este archivo contiene las tablas que no se instalaron
-- correctamente durante la instalación inicial.
-- Ejecutar en phpMyAdmin o cliente MySQL.
-- =====================================================

USE conectae_conectaerpbd;

-- =====================================================
-- TABLA CRÍTICA: user_permissions
-- =====================================================
-- Esta tabla es esencial para el sistema de permisos
-- Relaciona usuarios con submódulos y sus permisos CRUD

CREATE TABLE IF NOT EXISTS `user_permissions` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `submodule_id` INT(11) UNSIGNED NOT NULL,
  `can_view` TINYINT(1) DEFAULT 1,
  `can_create` TINYINT(1) DEFAULT 0,
  `can_edit` TINYINT(1) DEFAULT 0,
  `can_delete` TINYINT(1) DEFAULT 0,
  `granted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`submodule_id`) REFERENCES `submodules`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `user_submodule` (`user_id`, `submodule_id`),
  INDEX `idx_user` (`user_id`),
  INDEX `idx_submodule` (`submodule_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Permisos granulares de usuarios por submódulo';

-- =====================================================
-- TABLA: payment_subscriptions
-- =====================================================
-- Gestión de suscripciones y pagos de planes

CREATE TABLE IF NOT EXISTS `payment_subscriptions` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `plan_type` ENUM('starter', 'professional', 'enterprise', 'custom') NOT NULL,
  `billing_cycle` ENUM('monthly', 'quarterly', 'yearly') NOT NULL DEFAULT 'monthly',
  `amount` DECIMAL(10,2) NOT NULL,
  `currency` VARCHAR(3) NOT NULL DEFAULT 'USD',
  `status` ENUM('active', 'pending', 'cancelled', 'expired') NOT NULL DEFAULT 'pending',
  `started_at` TIMESTAMP NULL,
  `expires_at` TIMESTAMP NULL,
  `next_billing_date` DATE NULL,
  `payment_method` VARCHAR(50) NULL,
  `payment_gateway` VARCHAR(50) NULL,
  `gateway_subscription_id` VARCHAR(100) NULL,
  `auto_renew` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  INDEX `idx_status` (`status`),
  INDEX `idx_next_billing` (`next_billing_date`),
  INDEX `idx_user_company` (`user_id`, `company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Suscripciones y planes de pago de usuarios';

-- =====================================================
-- TABLA: payment_transactions
-- =====================================================
-- Historial de transacciones de pago

CREATE TABLE IF NOT EXISTS `payment_transactions` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `subscription_id` INT(11) UNSIGNED NULL,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `transaction_type` ENUM('payment', 'refund', 'chargeback', 'upgrade', 'downgrade') NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `currency` VARCHAR(3) NOT NULL DEFAULT 'USD',
  `status` ENUM('pending', 'completed', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
  `payment_method` VARCHAR(50) NULL,
  `payment_gateway` VARCHAR(50) NULL,
  `gateway_transaction_id` VARCHAR(100) NULL,
  `gateway_response` TEXT NULL,
  `description` VARCHAR(255) NULL,
  `invoice_number` VARCHAR(50) NULL,
  `processed_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`subscription_id`) REFERENCES `payment_subscriptions`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  INDEX `idx_status` (`status`),
  INDEX `idx_gateway_transaction` (`gateway_transaction_id`),
  INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Historial de transacciones de pago';

-- =====================================================
-- TABLA: system_settings
-- =====================================================
-- Configuraciones globales del sistema

CREATE TABLE IF NOT EXISTS `system_settings` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(100) NOT NULL,
  `setting_value` TEXT NULL,
  `setting_type` ENUM('string', 'number', 'boolean', 'json', 'array') NOT NULL DEFAULT 'string',
  `category` VARCHAR(50) NOT NULL DEFAULT 'general',
  `description` VARCHAR(255) NULL,
  `is_public` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`),
  INDEX `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Configuraciones globales del sistema ERP';

-- =====================================================
-- TABLA: email_templates
-- =====================================================
-- Plantillas de emails del sistema

CREATE TABLE IF NOT EXISTS `email_templates` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `template_key` VARCHAR(100) NOT NULL,
  `subject` VARCHAR(255) NOT NULL,
  `body` TEXT NOT NULL,
  `variables` TEXT NULL COMMENT 'JSON con variables disponibles {user_name}, {company_name}, etc.',
  `language` VARCHAR(5) NOT NULL DEFAULT 'es',
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `template_lang` (`template_key`, `language`),
  INDEX `idx_key` (`template_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Plantillas de correos electrónicos del sistema';

-- =====================================================
-- TABLA: notifications
-- =====================================================
-- Notificaciones del sistema para usuarios

CREATE TABLE IF NOT EXISTS `notifications` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `notification_type` ENUM('info', 'warning', 'error', 'success') NOT NULL DEFAULT 'info',
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `link` VARCHAR(255) NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `read_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_user_unread` (`user_id`, `is_read`),
  INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Notificaciones del sistema para usuarios';

-- =====================================================
-- TABLA: audit_logs
-- =====================================================
-- Logs de auditoría detallados para compliance

CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NULL,
  `company_id` INT(11) UNSIGNED NULL,
  `action` VARCHAR(100) NOT NULL,
  `table_name` VARCHAR(100) NULL,
  `record_id` INT(11) UNSIGNED NULL,
  `old_values` TEXT NULL COMMENT 'JSON con valores anteriores',
  `new_values` TEXT NULL COMMENT 'JSON con valores nuevos',
  `ip_address` VARCHAR(45) NULL,
  `user_agent` VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE SET NULL,
  INDEX `idx_user` (`user_id`),
  INDEX `idx_company` (`company_id`),
  INDEX `idx_action` (`action`),
  INDEX `idx_created` (`created_at`),
  INDEX `idx_table_record` (`table_name`, `record_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Logs detallados de auditoría para compliance y seguridad';

-- =====================================================
-- TABLA: user_sessions
-- =====================================================
-- Gestión avanzada de sesiones de usuario

CREATE TABLE IF NOT EXISTS `user_sessions` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `session_id` VARCHAR(128) NOT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `user_agent` VARCHAR(255) NULL,
  `device_type` VARCHAR(50) NULL,
  `browser` VARCHAR(50) NULL,
  `os` VARCHAR(50) NULL,
  `country` VARCHAR(50) NULL,
  `city` VARCHAR(100) NULL,
  `last_activity` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `expires_at` TIMESTAMP NOT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `session_id` (`session_id`),
  INDEX `idx_user_active` (`user_id`, `is_active`),
  INDEX `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Gestión de sesiones de usuarios con información de dispositivo';

-- =====================================================
-- TABLA: login_attempts
-- =====================================================
-- Control de intentos fallidos de login (seguridad)

CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(150) NOT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `user_agent` VARCHAR(255) NULL,
  `is_successful` TINYINT(1) DEFAULT 0,
  `failure_reason` VARCHAR(100) NULL,
  `attempted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_email` (`email`),
  INDEX `idx_ip` (`ip_address`),
  INDEX `idx_attempted` (`attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Registro de intentos de login para seguridad y bloqueo';

-- =====================================================
-- TABLA: api_tokens
-- =====================================================
-- Tokens de API para integraciones externas

CREATE TABLE IF NOT EXISTS `api_tokens` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `token_name` VARCHAR(100) NOT NULL,
  `token` VARCHAR(128) NOT NULL,
  `scopes` TEXT NULL COMMENT 'JSON con permisos del token',
  `last_used_at` TIMESTAMP NULL,
  `last_used_ip` VARCHAR(45) NULL,
  `expires_at` TIMESTAMP NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `token` (`token`),
  INDEX `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Tokens de API para integraciones y automatización';

-- =====================================================
-- INSERTAR CONFIGURACIONES INICIALES
-- =====================================================

-- Configuraciones del sistema
INSERT IGNORE INTO `system_settings` (`setting_key`, `setting_value`, `setting_type`, `category`, `description`) VALUES
('site_name', 'CONECTA ERP', 'string', 'general', 'Nombre del sitio'),
('site_url', 'http://localhost', 'string', 'general', 'URL base del sitio'),
('maintenance_mode', '0', 'boolean', 'general', 'Modo mantenimiento'),
('allow_registration', '1', 'boolean', 'users', 'Permitir registro de usuarios'),
('require_email_verification', '0', 'boolean', 'users', 'Requiere verificación de email'),
('default_language', 'es', 'string', 'localization', 'Idioma por defecto'),
('default_currency', 'USD', 'string', 'localization', 'Moneda por defecto'),
('default_timezone', 'America/Santiago', 'string', 'localization', 'Zona horaria'),
('trial_days', '14', 'number', 'billing', 'Días de prueba gratuita'),
('session_timeout', '7200', 'number', 'security', 'Timeout de sesión en segundos'),
('max_login_attempts', '5', 'number', 'security', 'Máximo intentos de login'),
('lockout_duration', '900', 'number', 'security', 'Duración bloqueo en segundos');

-- Plantillas de email básicas
INSERT IGNORE INTO `email_templates` (`template_key`, `subject`, `body`, `language`) VALUES
('welcome', 'Bienvenido a CONECTA ERP', '<h1>¡Bienvenido {user_name}!</h1><p>Tu cuenta ha sido creada exitosamente en {company_name}.</p>', 'es'),
('approval_pending', 'Cuenta pendiente de aprobación', '<h1>Hola {user_name}</h1><p>Tu cuenta está pendiente de aprobación. Te notificaremos cuando esté activa.</p>', 'es'),
('account_approved', '¡Tu cuenta ha sido aprobada!', '<h1>¡Felicidades {user_name}!</h1><p>Tu cuenta ha sido aprobada. Ya puedes acceder al sistema.</p>', 'es'),
('trial_expiring', 'Tu periodo de prueba está por vencer', '<h1>Hola {user_name}</h1><p>Tu periodo de prueba vence en {days_left} días.</p>', 'es'),
('subscription_expired', 'Tu suscripción ha expirado', '<h1>Hola {user_name}</h1><p>Tu suscripción ha expirado. Renueva para continuar usando CONECTA ERP.</p>', 'es');

-- =====================================================
-- FIN DEL SCRIPT
-- =====================================================

SELECT '✅ Tablas faltantes creadas exitosamente - 10 tablas críticas instaladas' as mensaje;
