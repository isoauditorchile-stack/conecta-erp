-- =====================================================
-- CONECTA ERP - SISTEMA COMPLETO DE ADMINISTRACIÓN
-- Tablas para gestión de suscripciones, pagos y aprobaciones
-- Sistema de nivel empresarial (SAP/Softland)
-- =====================================================

USE conectae_conectaerpbd;

-- =====================================================
-- TABLA: planes
-- Sistema de planes de suscripción del ERP
-- =====================================================
CREATE TABLE IF NOT EXISTS `planes` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `codigo` VARCHAR(50) NOT NULL,
  `nombre_es` VARCHAR(100) NOT NULL,
  `nombre_en` VARCHAR(100) NOT NULL,
  `descripcion_es` TEXT,
  `descripcion_en` TEXT,
  `precio_mensual` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `precio_anual` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `moneda` VARCHAR(3) NOT NULL DEFAULT 'USD',
  `max_usuarios` INT(11) DEFAULT NULL COMMENT 'NULL = ilimitado',
  `max_empresas` INT(11) DEFAULT 1,
  `modulos_incluidos` TEXT COMMENT 'JSON array de módulos incluidos',
  `caracteristicas` TEXT COMMENT 'JSON array de características',
  `es_trial` TINYINT(1) DEFAULT 0,
  `dias_trial` INT(11) DEFAULT 14,
  `es_popular` TINYINT(1) DEFAULT 0,
  `orden` INT(11) DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`),
  INDEX `idx_active` (`is_active`),
  INDEX `idx_orden` (`orden`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Planes de suscripción del ERP (Starter, Professional, Enterprise, Custom)';

-- =====================================================
-- TABLA: suscripciones
-- Gestión de suscripciones activas de clientes
-- =====================================================
CREATE TABLE IF NOT EXISTS `suscripciones` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `plan_id` INT(11) UNSIGNED NOT NULL,
  `estado` ENUM('pendiente', 'trial', 'activa', 'suspendida', 'cancelada', 'expirada') NOT NULL DEFAULT 'pendiente',
  `ciclo_pago` ENUM('mensual', 'anual') NOT NULL DEFAULT 'mensual',
  `fecha_inicio` TIMESTAMP NULL,
  `fecha_fin` TIMESTAMP NULL,
  `fecha_fin_trial` TIMESTAMP NULL,
  `dias_trial` INT(11) DEFAULT 14,
  `es_trial` TINYINT(1) DEFAULT 1,
  `auto_renovacion` TINYINT(1) DEFAULT 1,
  `precio_pactado` DECIMAL(10,2) NOT NULL,
  `moneda` VARCHAR(3) NOT NULL DEFAULT 'USD',
  `descuento_porcentaje` DECIMAL(5,2) DEFAULT 0.00,
  `descuento_monto` DECIMAL(10,2) DEFAULT 0.00,
  `codigo_cupon` VARCHAR(50) NULL,
  `proximo_pago` DATE NULL,
  `metodo_pago_preferido` VARCHAR(50) NULL,
  `aprobado_por_admin` TINYINT(1) DEFAULT 0,
  `admin_aprobador_id` INT(11) UNSIGNED NULL,
  `fecha_aprobacion` TIMESTAMP NULL,
  `notas_admin` TEXT NULL,
  `cancelado_por` INT(11) UNSIGNED NULL,
  `fecha_cancelacion` TIMESTAMP NULL,
  `razon_cancelacion` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`plan_id`) REFERENCES `planes`(`id`) ON DELETE RESTRICT,
  INDEX `idx_user` (`user_id`),
  INDEX `idx_company` (`company_id`),
  INDEX `idx_estado` (`estado`),
  INDEX `idx_proximo_pago` (`proximo_pago`),
  INDEX `idx_fecha_fin` (`fecha_fin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Suscripciones activas y trials de clientes';

-- =====================================================
-- TABLA: pagos
-- Registro de todos los pagos recibidos
-- =====================================================
CREATE TABLE IF NOT EXISTS `pagos` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `suscripcion_id` INT(11) UNSIGNED NULL,
  `plan_id` INT(11) UNSIGNED NOT NULL,
  `monto` DECIMAL(10,2) NOT NULL,
  `moneda` VARCHAR(3) NOT NULL DEFAULT 'USD',
  `estado` ENUM('pendiente', 'procesando', 'completado', 'fallido', 'reembolsado') NOT NULL DEFAULT 'pendiente',
  `metodo_pago` ENUM('transferencia', 'webpay', 'paypal', 'stripe', 'mercadopago', 'otro') NOT NULL,
  `referencia_pago` VARCHAR(255) NULL COMMENT 'Número de transferencia o transaction ID',
  `comprobante_url` VARCHAR(500) NULL COMMENT 'URL del comprobante subido',
  `gateway_transaction_id` VARCHAR(255) NULL,
  `gateway_response` TEXT NULL COMMENT 'JSON response del gateway',
  `fecha_pago` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `fecha_procesamiento` TIMESTAMP NULL,
  `fecha_verificacion` TIMESTAMP NULL,
  `verificado_por_admin` TINYINT(1) DEFAULT 0,
  `admin_verificador_id` INT(11) UNSIGNED NULL,
  `notas_admin` TEXT NULL,
  `invoice_number` VARCHAR(50) NULL,
  `invoice_url` VARCHAR(500) NULL,
  `periodo_desde` DATE NULL,
  `periodo_hasta` DATE NULL,
  `es_renovacion` TINYINT(1) DEFAULT 0,
  `pago_relacionado_id` INT(11) UNSIGNED NULL COMMENT 'ID del pago anterior si es renovación',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`suscripcion_id`) REFERENCES `suscripciones`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`plan_id`) REFERENCES `planes`(`id`) ON DELETE RESTRICT,
  INDEX `idx_user` (`user_id`),
  INDEX `idx_estado` (`estado`),
  INDEX `idx_metodo_pago` (`metodo_pago`),
  INDEX `idx_fecha_pago` (`fecha_pago`),
  INDEX `idx_verificado` (`verificado_por_admin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Registro completo de pagos y transacciones';

-- =====================================================
-- TABLA: aprobaciones_usuario
-- Workflow de aprobación de nuevos usuarios
-- =====================================================
CREATE TABLE IF NOT EXISTS `aprobaciones_usuario` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `estado` ENUM('pendiente', 'aprobado', 'rechazado') NOT NULL DEFAULT 'pendiente',
  `fecha_solicitud` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `fecha_decision` TIMESTAMP NULL,
  `admin_id` INT(11) UNSIGNED NULL COMMENT 'Admin que tomó la decisión',
  `notas_admin` TEXT NULL,
  `razon_rechazo` TEXT NULL,
  `ip_registro` VARCHAR(45) NULL,
  `user_agent` VARCHAR(500) NULL,
  `datos_registro` TEXT NULL COMMENT 'JSON con datos adicionales del registro',
  `revisiones` INT(11) DEFAULT 0 COMMENT 'Número de veces que se revisó',
  `prioridad` ENUM('baja', 'normal', 'alta', 'urgente') DEFAULT 'normal',
  `notas_internas` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_estado` (`estado`),
  INDEX `idx_fecha_solicitud` (`fecha_solicitud`),
  INDEX `idx_prioridad` (`prioridad`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Sistema de aprobación de usuarios por administrador';

-- =====================================================
-- TABLA: historial_suscripciones
-- Auditoría de cambios en suscripciones
-- =====================================================
CREATE TABLE IF NOT EXISTS `historial_suscripciones` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `suscripcion_id` INT(11) UNSIGNED NOT NULL,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `accion` VARCHAR(100) NOT NULL COMMENT 'created, upgraded, downgraded, renewed, cancelled, suspended',
  `plan_anterior_id` INT(11) UNSIGNED NULL,
  `plan_nuevo_id` INT(11) UNSIGNED NULL,
  `estado_anterior` VARCHAR(50) NULL,
  `estado_nuevo` VARCHAR(50) NULL,
  `realizado_por` INT(11) UNSIGNED NULL COMMENT 'User ID quien realizó el cambio',
  `es_automatico` TINYINT(1) DEFAULT 0,
  `detalles` TEXT NULL COMMENT 'JSON con detalles del cambio',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`suscripcion_id`) REFERENCES `suscripciones`(`id`) ON DELETE CASCADE,
  INDEX `idx_suscripcion` (`suscripcion_id`),
  INDEX `idx_fecha` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Auditoría completa de cambios en suscripciones';

-- =====================================================
-- INSERTAR DATOS INICIALES
-- =====================================================

-- Planes de suscripción
INSERT INTO `planes` (`codigo`, `nombre_es`, `nombre_en`, `descripcion_es`, `descripcion_en`, `precio_mensual`, `precio_anual`, `moneda`, `max_usuarios`, `max_empresas`, `es_trial`, `dias_trial`, `orden`, `is_active`) VALUES
('TRIAL', 'Trial Gratuito', 'Free Trial', 'Prueba gratuita de 14 días con acceso completo', '14-day free trial with full access', 0.00, 0.00, 'USD', 3, 1, 1, 14, 0, 1),
('STARTER', 'Starter', 'Starter', 'Ideal para pequeñas empresas que inician', 'Ideal for small businesses starting out', 49.99, 499.99, 'USD', 5, 1, 0, 0, 1, 1),
('PROFESSIONAL', 'Professional', 'Professional', 'Para empresas en crecimiento con necesidades avanzadas', 'For growing businesses with advanced needs', 149.99, 1499.99, 'USD', 25, 3, 0, 0, 2, 1),
('ENTERPRISE', 'Enterprise', 'Enterprise', 'Solución completa para grandes empresas', 'Complete solution for large enterprises', 499.99, 4999.99, 'USD', NULL, NULL, 0, 0, 3, 1),
('CUSTOM', 'Custom', 'Custom', 'Plan personalizado según tus necesidades', 'Custom plan tailored to your needs', 0.00, 0.00, 'USD', NULL, NULL, 0, 0, 4, 1);

-- =====================================================
-- VERIFICACIÓN FINAL
-- =====================================================

SELECT 'Sistema de administración completo instalado correctamente' as resultado;

SELECT
    'planes' as tabla,
    COUNT(*) as registros
FROM planes
UNION ALL
SELECT 'suscripciones', COUNT(*) FROM suscripciones
UNION ALL
SELECT 'pagos', COUNT(*) FROM pagos
UNION ALL
SELECT 'aprobaciones_usuario', COUNT(*) FROM aprobaciones_usuario
UNION ALL
SELECT 'historial_suscripciones', COUNT(*) FROM historial_suscripciones;
