-- =====================================================
-- MÓDULO 4: CONTROLLING (CO) - CONECTA ERP
-- Control de Gestión y Centros de Costo
-- =====================================================

USE conectae_conectaerpbd;

-- =====================================================
-- TABLA: centros_costo
-- Centros de costo para distribución de gastos
-- =====================================================
CREATE TABLE IF NOT EXISTS `centros_costo` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `codigo` VARCHAR(50) NOT NULL,
  `nombre` VARCHAR(255) NOT NULL,
  `descripcion` TEXT DEFAULT NULL,
  `centro_padre_id` INT(11) DEFAULT NULL COMMENT 'Jerarquía de centros',
  `tipo` ENUM('operativo','administrativo','ventas','produccion','servicio') DEFAULT 'operativo',
  `responsable_id` INT(11) DEFAULT NULL,
  `activo` TINYINT(1) DEFAULT 1,
  `fecha_creacion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `creado_por` INT(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `empresa_codigo` (`empresa_id`, `codigo`),
  KEY `idx_empresa` (`empresa_id`),
  KEY `idx_responsable` (`responsable_id`),
  KEY `idx_padre` (`centro_padre_id`),
  CONSTRAINT `fk_centros_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_centros_responsable` FOREIGN KEY (`responsable_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_centros_padre` FOREIGN KEY (`centro_padre_id`) REFERENCES `centros_costo` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: imputaciones_costo
-- Imputación de costos a centros de costo
-- =====================================================
CREATE TABLE IF NOT EXISTS `imputaciones_costo` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `centro_costo_id` INT(11) NOT NULL,
  `cuenta_contable_id` INT(11) DEFAULT NULL,
  `fecha_imputacion` DATE NOT NULL,
  `concepto` VARCHAR(255) NOT NULL,
  `monto` DECIMAL(15,2) NOT NULL,
  `tipo` ENUM('directo','indirecto') DEFAULT 'directo',
  `documento_referencia` VARCHAR(100) DEFAULT NULL,
  `observaciones` TEXT DEFAULT NULL,
  `fecha_creacion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `creado_por` INT(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_empresa` (`empresa_id`),
  KEY `idx_centro_costo` (`centro_costo_id`),
  KEY `idx_fecha` (`fecha_imputacion`),
  CONSTRAINT `fk_imputaciones_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_imputaciones_centro` FOREIGN KEY (`centro_costo_id`) REFERENCES `centros_costo` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: indicadores_gestion
-- KPIs y métricas de gestión
-- =====================================================
CREATE TABLE IF NOT EXISTS `indicadores_gestion` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `codigo` VARCHAR(50) NOT NULL,
  `nombre` VARCHAR(255) NOT NULL,
  `descripcion` TEXT DEFAULT NULL,
  `formula` TEXT DEFAULT NULL COMMENT 'Fórmula de cálculo',
  `unidad_medida` VARCHAR(50) DEFAULT NULL,
  `frecuencia` ENUM('diario','semanal','mensual','trimestral','anual') DEFAULT 'mensual',
  `objetivo` DECIMAL(15,2) DEFAULT NULL,
  `activo` TINYINT(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `empresa_codigo` (`empresa_id`, `codigo`),
  CONSTRAINT `fk_indicadores_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: mediciones_indicadores
-- Valores medidos de indicadores
-- =====================================================
CREATE TABLE IF NOT EXISTS `mediciones_indicadores` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `indicador_id` INT(11) NOT NULL,
  `periodo` DATE NOT NULL,
  `valor_real` DECIMAL(15,2) NOT NULL,
  `valor_objetivo` DECIMAL(15,2) DEFAULT NULL,
  `cumplimiento_porcentaje` DECIMAL(5,2) GENERATED ALWAYS AS (CASE WHEN valor_objetivo > 0 THEN (valor_real / valor_objetivo) * 100 ELSE 0 END) STORED,
  `observaciones` TEXT DEFAULT NULL,
  `fecha_creacion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `creado_por` INT(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `indicador_periodo` (`indicador_id`, `periodo`),
  KEY `idx_periodo` (`periodo`),
  CONSTRAINT `fk_mediciones_indicador` FOREIGN KEY (`indicador_id`) REFERENCES `indicadores_gestion` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- VISTA: Resumen de costos por centro
-- =====================================================
CREATE OR REPLACE VIEW v_costos_por_centro AS
SELECT
    cc.empresa_id,
    cc.id as centro_costo_id,
    cc.codigo,
    cc.nombre,
    COUNT(ic.id) as total_imputaciones,
    COALESCE(SUM(ic.monto), 0) as total_costos
FROM centros_costo cc
LEFT JOIN imputaciones_costo ic ON cc.id = ic.centro_costo_id
GROUP BY cc.empresa_id, cc.id, cc.codigo, cc.nombre;

-- =====================================================
-- ÍNDICES ADICIONALES
-- =====================================================
ALTER TABLE `imputaciones_costo` ADD INDEX `idx_centro_fecha` (`centro_costo_id`, `fecha_imputacion`);
ALTER TABLE `mediciones_indicadores` ADD INDEX `idx_indicador_periodo` (`indicador_id`, `periodo`);

FLUSH PRIVILEGES;
