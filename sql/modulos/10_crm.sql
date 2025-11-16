-- =====================================================
-- MÓDULO 10: CRM - CONECTA ERP
-- Customer Relationship Management
-- =====================================================

USE conectae_conectaerpbd;

-- =====================================================
-- TABLA: oportunidades
-- Oportunidades de venta
-- =====================================================
CREATE TABLE IF NOT EXISTS `oportunidades` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `cliente_id` INT(11) NOT NULL,
  `nombre` VARCHAR(255) NOT NULL,
  `valor_estimado` DECIMAL(15,2) DEFAULT 0.00,
  `probabilidad` TINYINT(3) DEFAULT 50 COMMENT 'Porcentaje 0-100',
  `etapa` ENUM('prospecto','calificacion','propuesta','negociacion','cerrado_ganado','cerrado_perdido') DEFAULT 'prospecto',
  `fecha_cierre_estimada` DATE DEFAULT NULL,
  `fecha_cierre_real` DATE DEFAULT NULL,
  `vendedor_id` INT(11) DEFAULT NULL,
  `notas` TEXT DEFAULT NULL,
  `fecha_creacion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_empresa` (`empresa_id`),
  KEY `idx_cliente` (`cliente_id`),
  KEY `idx_etapa` (`etapa`),
  CONSTRAINT `fk_oportunidades_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_oportunidades_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: actividades_crm
-- Actividades (llamadas, reuniones, emails)
-- =====================================================
CREATE TABLE IF NOT EXISTS `actividades_crm` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `tipo` ENUM('llamada','reunion','email','tarea','nota') DEFAULT 'tarea',
  `asunto` VARCHAR(255) NOT NULL,
  `descripcion` TEXT DEFAULT NULL,
  `cliente_id` INT(11) DEFAULT NULL,
  `oportunidad_id` INT(11) DEFAULT NULL,
  `fecha_actividad` DATETIME NOT NULL,
  `duracion_minutos` INT(5) DEFAULT 0,
  `completada` TINYINT(1) DEFAULT 0,
  `asignado_a` INT(11) DEFAULT NULL,
  `fecha_creacion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_empresa` (`empresa_id`),
  KEY `idx_cliente` (`cliente_id`),
  KEY `idx_completada` (`completada`),
  CONSTRAINT `fk_actividades_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: campanas_crm
-- Campañas de marketing
-- =====================================================
CREATE TABLE IF NOT EXISTS `campanas_crm` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `nombre` VARCHAR(255) NOT NULL,
  `descripcion` TEXT DEFAULT NULL,
  `tipo` ENUM('email','sms','telefono','redes_sociales','evento') DEFAULT 'email',
  `fecha_inicio` DATE NOT NULL,
  `fecha_fin` DATE DEFAULT NULL,
  `presupuesto` DECIMAL(15,2) DEFAULT 0.00,
  `costo_real` DECIMAL(15,2) DEFAULT 0.00,
  `contactos_objetivo` INT(11) DEFAULT 0,
  `contactos_alcanzados` INT(11) DEFAULT 0,
  `conversiones` INT(11) DEFAULT 0,
  `estado` ENUM('planificada','activa','completada','cancelada') DEFAULT 'planificada',
  PRIMARY KEY (`id`),
  KEY `idx_empresa` (`empresa_id`),
  CONSTRAINT `fk_campanas_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: contactos_crm
-- Contactos dentro de clientes
-- =====================================================
CREATE TABLE IF NOT EXISTS `contactos_crm` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `cliente_id` INT(11) NOT NULL,
  `nombre` VARCHAR(255) NOT NULL,
  `cargo` VARCHAR(150) DEFAULT NULL,
  `email` VARCHAR(150) DEFAULT NULL,
  `telefono` VARCHAR(50) DEFAULT NULL,
  `es_principal` TINYINT(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_empresa` (`empresa_id`),
  KEY `idx_cliente` (`cliente_id`),
  CONSTRAINT `fk_contactos_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_contactos_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- VISTA: Pipeline de ventas
-- =====================================================
CREATE OR REPLACE VIEW v_pipeline_ventas AS
SELECT
    o.empresa_id,
    o.etapa,
    COUNT(*) as total_oportunidades,
    SUM(o.valor_estimado) as valor_total,
    SUM(o.valor_estimado * o.probabilidad / 100) as valor_ponderado,
    AVG(o.probabilidad) as probabilidad_promedio
FROM oportunidades o
WHERE o.etapa NOT IN ('cerrado_ganado', 'cerrado_perdido')
GROUP BY o.empresa_id, o.etapa;

FLUSH PRIVILEGES;
