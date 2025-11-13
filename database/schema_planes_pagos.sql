-- =====================================================
-- CONECTA ERP - Sistema de Planes y Pagos Profesional
-- Sistema REAL de Producción para competir con SAP/Softland
-- =====================================================

-- Tabla de Planes (Básico, Profesional, Empresarial, Corporativo)
CREATE TABLE IF NOT EXISTS `planes` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `codigo` VARCHAR(50) NOT NULL UNIQUE,
  `nombre_es` VARCHAR(100) NOT NULL,
  `nombre_en` VARCHAR(100) NOT NULL,
  `descripcion_es` TEXT,
  `descripcion_en` TEXT,
  `precio_mensual_usd` DECIMAL(10,2) NOT NULL,
  `precio_anual_usd` DECIMAL(10,2) NOT NULL,
  `descuento_anual_porcentaje` DECIMAL(5,2) DEFAULT 0.00,
  `usuarios_maximos` INT(11) DEFAULT NULL COMMENT 'NULL = ilimitado',
  `empresas_maximas` INT(11) DEFAULT 1,
  `almacenamiento_gb` INT(11) DEFAULT 10,
  `soporte_prioridad` ENUM('basico', 'prioritario', 'vip', '24/7') DEFAULT 'basico',
  `modulos_incluidos` TEXT COMMENT 'JSON con IDs de módulos',
  `caracteristicas` TEXT COMMENT 'JSON con características',
  `es_popular` TINYINT(1) DEFAULT 0,
  `orden_visualizacion` INT(11) DEFAULT 0,
  `activo` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_activo` (`activo`),
  INDEX `idx_codigo` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de Suscripciones (reemplaza el trial simple)
CREATE TABLE IF NOT EXISTS `suscripciones` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `plan_id` INT(11) UNSIGNED NOT NULL,
  `estado` ENUM('trial', 'activa', 'suspendida', 'cancelada', 'vencida', 'pendiente_pago') DEFAULT 'trial',
  `fecha_inicio` DATETIME NOT NULL,
  `fecha_fin` DATETIME DEFAULT NULL,
  `dias_trial` INT(11) DEFAULT 14,
  `fecha_fin_trial` DATETIME DEFAULT NULL,
  `es_trial` TINYINT(1) DEFAULT 1,
  `ciclo_pago` ENUM('mensual', 'anual') DEFAULT 'mensual',
  `precio_pactado` DECIMAL(10,2) NOT NULL,
  `moneda` VARCHAR(3) DEFAULT 'USD',
  `auto_renovacion` TINYINT(1) DEFAULT 0,
  `notificacion_dia_13_enviada` TINYINT(1) DEFAULT 0,
  `notificacion_dia_7_enviada` TINYINT(1) DEFAULT 0,
  `notificacion_dia_1_enviada` TINYINT(1) DEFAULT 0,
  `aprobado_por_admin` TINYINT(1) DEFAULT 0,
  `admin_aprobador_id` INT(11) UNSIGNED DEFAULT NULL,
  `fecha_aprobacion` DATETIME DEFAULT NULL,
  `notas_aprobacion` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`plan_id`) REFERENCES `planes` (`id`),
  INDEX `idx_user` (`user_id`),
  INDEX `idx_company` (`company_id`),
  INDEX `idx_estado` (`estado`),
  INDEX `idx_fecha_fin_trial` (`fecha_fin_trial`),
  INDEX `idx_aprobado` (`aprobado_por_admin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de Pagos
CREATE TABLE IF NOT EXISTS `pagos` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `suscripcion_id` INT(11) UNSIGNED NOT NULL,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `plan_id` INT(11) UNSIGNED NOT NULL,
  `metodo_pago` ENUM('paypal', 'transferencia', 'tarjeta', 'otro') NOT NULL,
  `estado` ENUM('pendiente', 'procesando', 'completado', 'fallido', 'reembolsado') DEFAULT 'pendiente',
  `monto` DECIMAL(10,2) NOT NULL,
  `moneda` VARCHAR(3) DEFAULT 'USD',
  `referencia_pago` VARCHAR(255) COMMENT 'PayPal transaction ID, número transferencia, etc.',
  `paypal_order_id` VARCHAR(100),
  `paypal_payer_id` VARCHAR(100),
  `paypal_payer_email` VARCHAR(255),
  `datos_transferencia` TEXT COMMENT 'JSON con datos de transferencia bancaria',
  `comprobante_url` VARCHAR(500) COMMENT 'URL del comprobante subido',
  `fecha_pago` DATETIME DEFAULT NULL,
  `fecha_verificacion` DATETIME DEFAULT NULL,
  `verificado_por_admin` TINYINT(1) DEFAULT 0,
  `admin_verificador_id` INT(11) UNSIGNED DEFAULT NULL,
  `notas_admin` TEXT,
  `factura_generada` TINYINT(1) DEFAULT 0,
  `factura_numero` VARCHAR(50),
  `factura_url` VARCHAR(500),
  `periodo_inicio` DATE,
  `periodo_fin` DATE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`suscripcion_id`) REFERENCES `suscripciones` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`plan_id`) REFERENCES `planes` (`id`),
  INDEX `idx_suscripcion` (`suscripcion_id`),
  INDEX `idx_estado` (`estado`),
  INDEX `idx_metodo` (`metodo_pago`),
  INDEX `idx_fecha_pago` (`fecha_pago`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de Aprobaciones de Usuario (para auditorexchile@gmail.com)
CREATE TABLE IF NOT EXISTS `aprobaciones_usuario` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `estado` ENUM('pendiente', 'aprobado', 'rechazado') DEFAULT 'pendiente',
  `fecha_solicitud` DATETIME NOT NULL,
  `fecha_decision` DATETIME DEFAULT NULL,
  `admin_id` INT(11) UNSIGNED DEFAULT NULL COMMENT 'ID de auditorexchile@gmail.com',
  `razon_rechazo` TEXT,
  `notas_admin` TEXT,
  `ip_registro` VARCHAR(45),
  `user_agent` TEXT,
  `datos_empresa` TEXT COMMENT 'JSON con datos de la empresa al momento del registro',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX `idx_estado` (`estado`),
  INDEX `idx_fecha_solicitud` (`fecha_solicitud`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de Notificaciones de Trial
CREATE TABLE IF NOT EXISTS `notificaciones_trial` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `suscripcion_id` INT(11) UNSIGNED NOT NULL,
  `tipo` ENUM('dia_13', 'dia_7', 'dia_1', 'vencido', 'renovacion') NOT NULL,
  `dias_restantes` INT(11),
  `mensaje` TEXT,
  `enviado` TINYINT(1) DEFAULT 0,
  `fecha_envio` DATETIME DEFAULT NULL,
  `leido` TINYINT(1) DEFAULT 0,
  `fecha_lectura` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`suscripcion_id`) REFERENCES `suscripciones` (`id`) ON DELETE CASCADE,
  INDEX `idx_user` (`user_id`),
  INDEX `idx_enviado` (`enviado`),
  INDEX `idx_leido` (`leido`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de Configuración de Pagos
CREATE TABLE IF NOT EXISTS `configuracion_pagos` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `paypal_client_id` VARCHAR(255),
  `paypal_secret` VARCHAR(255),
  `paypal_mode` ENUM('sandbox', 'live') DEFAULT 'sandbox',
  `paypal_activo` TINYINT(1) DEFAULT 1,
  `banco_nombre` VARCHAR(255),
  `banco_cuenta_numero` VARCHAR(100),
  `banco_cuenta_tipo` VARCHAR(50),
  `banco_titular` VARCHAR(255),
  `banco_rut_titular` VARCHAR(50),
  `banco_email_contacto` VARCHAR(255),
  `banco_activo` TINYINT(1) DEFAULT 1,
  `moneda_principal` VARCHAR(3) DEFAULT 'USD',
  `email_notificaciones` VARCHAR(255),
  `dias_gracia_pago` INT(11) DEFAULT 3,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar configuración inicial de pagos
INSERT INTO `configuracion_pagos` (`id`, `banco_nombre`, `banco_cuenta_numero`, `banco_titular`, `banco_email_contacto`, `email_notificaciones`) VALUES
(1, 'Banco de Chile', 'PENDIENTE_CONFIGURAR', 'CONECTA ERP SpA', 'pagos@conectaerp.com', 'auditorexchile@gmail.com');

-- Insertar planes profesionales
INSERT INTO `planes` (`codigo`, `nombre_es`, `nombre_en`, `descripcion_es`, `descripcion_en`, `precio_mensual_usd`, `precio_anual_usd`, `descuento_anual_porcentaje`, `usuarios_maximos`, `empresas_maximas`, `almacenamiento_gb`, `soporte_prioridad`, `es_popular`, `orden_visualizacion`) VALUES
('TRIAL', 'Prueba Gratuita', 'Free Trial', 'Prueba gratuita por 14 días con acceso a todos los módulos', 'Free 14-day trial with access to all modules', 0.00, 0.00, 0, 2, 1, 5, 'basico', 0, 1),
('BASICO', 'Plan Básico', 'Basic Plan', 'Perfecto para pequeñas empresas y emprendedores', 'Perfect for small businesses and entrepreneurs', 49.00, 470.00, 20, 5, 1, 10, 'basico', 0, 2),
('PROFESIONAL', 'Plan Profesional', 'Professional Plan', 'Para empresas en crecimiento que necesitan más funcionalidades', 'For growing companies that need more features', 99.00, 950.00, 20, 15, 3, 50, 'prioritario', 1, 3),
('EMPRESARIAL', 'Plan Empresarial', 'Enterprise Plan', 'Solución completa para empresas establecidas', 'Complete solution for established companies', 199.00, 1910.00, 20, 50, 10, 200, 'vip', 0, 4),
('CORPORATIVO', 'Plan Corporativo', 'Corporate Plan', 'Máxima capacidad para corporaciones multinacionales', 'Maximum capacity for multinational corporations', 499.00, 4790.00, 20, NULL, NULL, 1000, '24/7', 0, 5);

-- Actualizar tabla users para compatibilidad con nuevo sistema
ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `plan_actual` VARCHAR(50) DEFAULT 'TRIAL',
  ADD COLUMN IF NOT EXISTS `suscripcion_activa_id` INT(11) UNSIGNED DEFAULT NULL,
  ADD INDEX IF NOT EXISTS `idx_plan_actual` (`plan_actual`);

-- Modificar columna status para incluir nuevos estados
ALTER TABLE `users`
  MODIFY COLUMN `status` ENUM('trial', 'active', 'suspended', 'expired', 'pending_approval', 'rejected') DEFAULT 'pending_approval';
