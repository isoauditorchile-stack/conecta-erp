-- =====================================================
-- MÓDULO 9: SCM - CONECTA ERP
-- Supply Chain Management
-- =====================================================

USE conectae_conectaerpbd;

-- =====================================================
-- TABLA: transportistas
-- Empresas transportistas
-- =====================================================
CREATE TABLE IF NOT EXISTS `transportistas` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `rut` VARCHAR(20) NOT NULL,
  `razon_social` VARCHAR(255) NOT NULL,
  `telefono` VARCHAR(50) DEFAULT NULL,
  `email` VARCHAR(150) DEFAULT NULL,
  `evaluacion` TINYINT(1) DEFAULT 3 COMMENT '1-5 estrellas',
  `estado` ENUM('activo','inactivo') DEFAULT 'activo',
  PRIMARY KEY (`id`),
  UNIQUE KEY `empresa_rut` (`empresa_id`, `rut`),
  CONSTRAINT `fk_transportistas_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: envios
-- Envíos y despachos
-- =====================================================
CREATE TABLE IF NOT EXISTS `envios` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `pedido_id` INT(11) DEFAULT NULL,
  `numero_envio` VARCHAR(50) NOT NULL,
  `transportista_id` INT(11) DEFAULT NULL,
  `fecha_envio` DATE NOT NULL,
  `fecha_entrega_estimada` DATE DEFAULT NULL,
  `fecha_entrega_real` DATE DEFAULT NULL,
  `direccion_destino` TEXT NOT NULL,
  `numero_guia` VARCHAR(100) DEFAULT NULL,
  `estado` ENUM('preparando','enviado','en_transito','entregado','devuelto') DEFAULT 'preparando',
  `observaciones` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `empresa_numero` (`empresa_id`, `numero_envio`),
  KEY `idx_pedido` (`pedido_id`),
  KEY `idx_transportista` (`transportista_id`),
  KEY `idx_estado` (`estado`),
  CONSTRAINT `fk_envios_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_envios_transportista` FOREIGN KEY (`transportista_id`) REFERENCES `transportistas` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: rutas
-- Rutas de distribución
-- =====================================================
CREATE TABLE IF NOT EXISTS `rutas` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `codigo` VARCHAR(50) NOT NULL,
  `nombre` VARCHAR(255) NOT NULL,
  `descripcion` TEXT DEFAULT NULL,
  `origen` VARCHAR(255) DEFAULT NULL,
  `destino` VARCHAR(255) DEFAULT NULL,
  `distancia_km` DECIMAL(10,2) DEFAULT 0.00,
  `tiempo_estimado_minutos` INT(5) DEFAULT 0,
  `activo` TINYINT(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `empresa_codigo` (`empresa_id`, `codigo`),
  CONSTRAINT `fk_rutas_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: vehiculos
-- Flota de vehículos
-- =====================================================
CREATE TABLE IF NOT EXISTS `vehiculos` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `patente` VARCHAR(20) NOT NULL,
  `marca` VARCHAR(100) DEFAULT NULL,
  `modelo` VARCHAR(100) DEFAULT NULL,
  `ano` INT(4) DEFAULT NULL,
  `tipo` ENUM('camion','camioneta','furgon','auto') DEFAULT 'camioneta',
  `capacidad_kg` DECIMAL(10,2) DEFAULT 0.00,
  `estado` ENUM('disponible','en_ruta','mantenimiento','fuera_servicio') DEFAULT 'disponible',
  PRIMARY KEY (`id`),
  UNIQUE KEY `empresa_patente` (`empresa_id`, `patente`),
  CONSTRAINT `fk_vehiculos_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- VISTA: Envíos en tránsito
-- =====================================================
CREATE OR REPLACE VIEW v_envios_transito AS
SELECT
    e.empresa_id,
    e.numero_envio,
    e.numero_guia,
    t.razon_social as transportista,
    e.fecha_envio,
    e.fecha_entrega_estimada,
    DATEDIFF(e.fecha_entrega_estimada, CURDATE()) as dias_restantes,
    e.estado
FROM envios e
LEFT JOIN transportistas t ON e.transportista_id = t.id
WHERE e.estado IN ('enviado', 'en_transito');

FLUSH PRIVILEGES;
