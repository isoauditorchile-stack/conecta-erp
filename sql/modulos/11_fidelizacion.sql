-- =====================================================
-- MÓDULO 11: FIDELIZACIÓN - CONECTA ERP
-- Customer Loyalty Management
-- =====================================================

USE conectae_conectaerpbd;

-- =====================================================
-- TABLA: programas_fidelizacion
-- Programas de fidelización
-- =====================================================
CREATE TABLE IF NOT EXISTS `programas_fidelizacion` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `codigo` VARCHAR(50) NOT NULL,
  `nombre` VARCHAR(255) NOT NULL,
  `descripcion` TEXT DEFAULT NULL,
  `tipo` ENUM('puntos','descuentos','niveles','cashback') DEFAULT 'puntos',
  `fecha_inicio` DATE NOT NULL,
  `fecha_fin` DATE DEFAULT NULL,
  `puntos_por_compra` DECIMAL(10,2) DEFAULT 1.00 COMMENT 'Puntos por cada $ gastado',
  `activo` TINYINT(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `empresa_codigo` (`empresa_id`, `codigo`),
  CONSTRAINT `fk_prog_fid_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: puntos_clientes
-- Puntos acumulados por cliente
-- =====================================================
CREATE TABLE IF NOT EXISTS `puntos_clientes` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `cliente_id` INT(11) NOT NULL,
  `programa_id` INT(11) NOT NULL,
  `puntos_acumulados` INT(11) DEFAULT 0,
  `puntos_canjeados` INT(11) DEFAULT 0,
  `puntos_disponibles` INT(11) GENERATED ALWAYS AS (puntos_acumulados - puntos_canjeados) STORED,
  `nivel` ENUM('bronce','plata','oro','platino','diamante') DEFAULT 'bronce',
  `fecha_actualizacion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cliente_programa` (`cliente_id`, `programa_id`),
  KEY `idx_empresa` (`empresa_id`),
  CONSTRAINT `fk_puntos_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_puntos_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_puntos_programa` FOREIGN KEY (`programa_id`) REFERENCES `programas_fidelizacion` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: movimientos_puntos
-- Historial de movimientos de puntos
-- =====================================================
CREATE TABLE IF NOT EXISTS `movimientos_puntos` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `puntos_cliente_id` INT(11) NOT NULL,
  `tipo` ENUM('acumulacion','canje','expiracion','ajuste') DEFAULT 'acumulacion',
  `puntos` INT(11) NOT NULL,
  `factura_id` INT(11) DEFAULT NULL,
  `recompensa_id` INT(11) DEFAULT NULL,
  `descripcion` TEXT DEFAULT NULL,
  `fecha_movimiento` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_empresa` (`empresa_id`),
  KEY `idx_puntos_cliente` (`puntos_cliente_id`),
  CONSTRAINT `fk_mov_puntos_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_mov_puntos_cliente` FOREIGN KEY (`puntos_cliente_id`) REFERENCES `puntos_clientes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: recompensas
-- Catálogo de recompensas canjeables
-- =====================================================
CREATE TABLE IF NOT EXISTS `recompensas` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `programa_id` INT(11) NOT NULL,
  `nombre` VARCHAR(255) NOT NULL,
  `descripcion` TEXT DEFAULT NULL,
  `puntos_requeridos` INT(11) NOT NULL,
  `stock_disponible` INT(11) DEFAULT NULL,
  `imagen` VARCHAR(255) DEFAULT NULL,
  `activo` TINYINT(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_empresa` (`empresa_id`),
  KEY `idx_programa` (`programa_id`),
  CONSTRAINT `fk_recompensas_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_recompensas_programa` FOREIGN KEY (`programa_id`) REFERENCES `programas_fidelizacion` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: comentarios_clientes
-- Comentarios y feedback de clientes
-- =====================================================
CREATE TABLE IF NOT EXISTS `comentarios_clientes` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `cliente_id` INT(11) NOT NULL,
  `tipo` ENUM('comentario','queja','sugerencia','felicitacion') DEFAULT 'comentario',
  `asunto` VARCHAR(255) NOT NULL,
  `mensaje` TEXT NOT NULL,
  `calificacion` TINYINT(1) DEFAULT NULL COMMENT '1-5 estrellas',
  `estado` ENUM('pendiente','en_revision','respondido','cerrado') DEFAULT 'pendiente',
  `fecha_creacion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_empresa` (`empresa_id`),
  KEY `idx_cliente` (`cliente_id`),
  CONSTRAINT `fk_comentarios_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_comentarios_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- VISTA: Resumen puntos por cliente
-- =====================================================
CREATE OR REPLACE VIEW v_resumen_puntos AS
SELECT
    pc.empresa_id,
    c.razon_social,
    pf.nombre as programa,
    pc.puntos_acumulados,
    pc.puntos_canjeados,
    pc.puntos_disponibles,
    pc.nivel
FROM puntos_clientes pc
INNER JOIN clientes c ON pc.cliente_id = c.id
INNER JOIN programas_fidelizacion pf ON pc.programa_id = pf.id
WHERE pc.puntos_disponibles > 0;

FLUSH PRIVILEGES;
