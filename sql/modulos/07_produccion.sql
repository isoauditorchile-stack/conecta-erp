-- =====================================================
-- MÓDULO 7: PRODUCCIÓN (PP) - CONECTA ERP
-- Production Planning
-- =====================================================

USE conectae_conectaerpbd;

-- =====================================================
-- TABLA: ordenes_produccion
-- Órdenes de producción
-- =====================================================
CREATE TABLE IF NOT EXISTS `ordenes_produccion` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `numero_orden` VARCHAR(50) NOT NULL,
  `producto_id` INT(11) NOT NULL COMMENT 'Producto a fabricar',
  `cantidad_planificada` DECIMAL(10,2) NOT NULL,
  `cantidad_producida` DECIMAL(10,2) DEFAULT 0.00,
  `fecha_inicio_planificada` DATE NOT NULL,
  `fecha_fin_planificada` DATE NOT NULL,
  `fecha_inicio_real` DATE DEFAULT NULL,
  `fecha_fin_real` DATE DEFAULT NULL,
  `estado` ENUM('planificada','en_proceso','completada','cancelada') DEFAULT 'planificada',
  `prioridad` ENUM('baja','media','alta','urgente') DEFAULT 'media',
  `observaciones` TEXT DEFAULT NULL,
  `fecha_creacion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `creado_por` INT(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `empresa_numero` (`empresa_id`, `numero_orden`),
  KEY `idx_producto` (`producto_id`),
  KEY `idx_estado` (`estado`),
  CONSTRAINT `fk_ordenes_prod_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ordenes_prod_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: lista_materiales (BOM - Bill of Materials)
-- Lista de materiales para fabricación
-- =====================================================
CREATE TABLE IF NOT EXISTS `lista_materiales` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `producto_final_id` INT(11) NOT NULL,
  `componente_id` INT(11) NOT NULL,
  `cantidad_necesaria` DECIMAL(10,2) NOT NULL,
  `unidad_medida` VARCHAR(50) DEFAULT 'unidad',
  `es_critico` TINYINT(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `producto_componente` (`producto_final_id`, `componente_id`),
  KEY `idx_componente` (`componente_id`),
  CONSTRAINT `fk_bom_producto` FOREIGN KEY (`producto_final_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_bom_componente` FOREIGN KEY (`componente_id`) REFERENCES `productos` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: centros_trabajo
-- Centros de trabajo / Máquinas
-- =====================================================
CREATE TABLE IF NOT EXISTS `centros_trabajo` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `codigo` VARCHAR(50) NOT NULL,
  `nombre` VARCHAR(255) NOT NULL,
  `tipo` ENUM('manual','semiautomatico','automatico') DEFAULT 'manual',
  `capacidad_hora` DECIMAL(10,2) DEFAULT 0.00,
  `costo_hora` DECIMAL(15,2) DEFAULT 0.00,
  `activo` TINYINT(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `empresa_codigo` (`empresa_id`, `codigo`),
  CONSTRAINT `fk_centros_trabajo_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: rutas_produccion
-- Rutas y procesos de producción
-- =====================================================
CREATE TABLE IF NOT EXISTS `rutas_produccion` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `producto_id` INT(11) NOT NULL,
  `centro_trabajo_id` INT(11) NOT NULL,
  `secuencia` INT(3) NOT NULL,
  `descripcion` VARCHAR(255) NOT NULL,
  `tiempo_preparacion_minutos` INT(5) DEFAULT 0,
  `tiempo_proceso_minutos` INT(5) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `producto_secuencia` (`producto_id`, `secuencia`),
  KEY `idx_centro_trabajo` (`centro_trabajo_id`),
  CONSTRAINT `fk_rutas_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rutas_centro` FOREIGN KEY (`centro_trabajo_id`) REFERENCES `centros_trabajo` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: control_calidad
-- Control de calidad en producción
-- =====================================================
CREATE TABLE IF NOT EXISTS `control_calidad` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `orden_produccion_id` INT(11) NOT NULL,
  `fecha_inspeccion` DATE NOT NULL,
  `inspector_id` INT(11) DEFAULT NULL,
  `unidades_inspeccionadas` DECIMAL(10,2) NOT NULL,
  `unidades_conformes` DECIMAL(10,2) NOT NULL,
  `unidades_defectuosas` DECIMAL(10,2) NOT NULL,
  `resultado` ENUM('aprobado','rechazado','condicional') DEFAULT 'aprobado',
  `observaciones` TEXT DEFAULT NULL,
  `fecha_creacion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `creado_por` INT(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_orden_produccion` (`orden_produccion_id`),
  CONSTRAINT `fk_calidad_orden` FOREIGN KEY (`orden_produccion_id`) REFERENCES `ordenes_produccion` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- VISTA: Eficiencia de producción
-- =====================================================
CREATE OR REPLACE VIEW v_eficiencia_produccion AS
SELECT
    op.empresa_id,
    op.id as orden_id,
    op.numero_orden,
    p.nombre as producto,
    op.cantidad_planificada,
    op.cantidad_producida,
    (op.cantidad_producida / op.cantidad_planificada * 100) as porcentaje_completado,
    DATEDIFF(op.fecha_fin_planificada, op.fecha_inicio_planificada) as dias_planificados,
    DATEDIFF(COALESCE(op.fecha_fin_real, CURDATE()), op.fecha_inicio_real) as dias_reales
FROM ordenes_produccion op
INNER JOIN productos p ON op.producto_id = p.id
WHERE op.estado IN ('en_proceso', 'completada');

FLUSH PRIVILEGES;
