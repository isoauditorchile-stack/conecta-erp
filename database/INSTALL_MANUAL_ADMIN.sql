-- =====================================================
-- CONECTA ERP - INSTALACIÓN MANUAL SISTEMA ADMIN
-- =====================================================
-- Ejecutar en phpMyAdmin o cliente MySQL
-- =====================================================

-- Eliminar tablas antiguas (en orden correcto por foreign keys)
DROP TABLE IF EXISTS `historial_suscripciones`;
DROP TABLE IF EXISTS `pagos`;
DROP TABLE IF EXISTS `suscripciones`;
DROP TABLE IF EXISTS `aprobaciones_usuario`;
DROP TABLE IF EXISTS `planes`;

-- =====================================================
-- TABLA: planes
-- =====================================================
CREATE TABLE `planes` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `plan_code` VARCHAR(50) NOT NULL,
  `plan_name` VARCHAR(100) NOT NULL,
  `precio_mensual` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `precio_anual` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `max_usuarios` INT(11) DEFAULT NULL COMMENT 'NULL = ilimitado',
  `max_empresas` INT(11) DEFAULT 1,
  `modulos_incluidos` INT(11) DEFAULT 0 COMMENT 'Número de módulos incluidos',
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `plan_code` (`plan_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar planes
INSERT INTO `planes` (`plan_code`, `plan_name`, `precio_mensual`, `precio_anual`, `max_usuarios`, `max_empresas`, `modulos_incluidos`, `is_active`) VALUES
('TRIAL', 'Trial Gratuito', 0.00, 0.00, 3, 1, 14, 1),
('STARTER', 'Starter', 49.99, 499.99, 5, 1, 5, 1),
('PROFESSIONAL', 'Professional', 149.99, 1499.99, 25, 3, 10, 1),
('ENTERPRISE', 'Enterprise', 499.99, 4999.99, NULL, NULL, 14, 1),
('CUSTOM', 'Custom', 0.00, 0.00, NULL, NULL, 14, 1);

-- =====================================================
-- TABLA: suscripciones
-- =====================================================
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: pagos
-- =====================================================
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: aprobaciones_usuario
-- =====================================================
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: historial_suscripciones
-- =====================================================
CREATE TABLE `historial_suscripciones` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `suscripcion_id` INT(11) UNSIGNED NOT NULL,
  `accion` VARCHAR(100) NOT NULL COMMENT 'created, upgraded, downgraded, renewed, cancelled, suspended, expired',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- VERIFICACIÓN
-- =====================================================
SELECT 'INSTALACIÓN COMPLETADA' as status;
SELECT 'Tablas creadas:' as info;
SHOW TABLES LIKE '%planes%';
SHOW TABLES LIKE '%suscripciones%';
SHOW TABLES LIKE '%pagos%';
SHOW TABLES LIKE '%aprobaciones_usuario%';
SHOW TABLES LIKE '%historial_suscripciones%';

SELECT 'Planes disponibles:' as info;
SELECT plan_code, plan_name, precio_mensual, precio_anual FROM planes;
