-- =====================================================
-- MÓDULO 6: MATERIALES (MM) - CONECTA ERP
-- Materials Management
-- =====================================================

USE conectae_conectaerpbd;

-- =====================================================
-- TABLA: almacenes
-- Almacenes y bodegas
-- =====================================================
CREATE TABLE IF NOT EXISTS `almacenes` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `codigo` VARCHAR(50) NOT NULL,
  `nombre` VARCHAR(255) NOT NULL,
  `tipo` ENUM('principal','secundario','transito','cuarentena') DEFAULT 'principal',
  `direccion` VARCHAR(255) DEFAULT NULL,
  `responsable_id` INT(11) DEFAULT NULL,
  `activo` TINYINT(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `empresa_codigo` (`empresa_id`, `codigo`),
  CONSTRAINT `fk_almacenes_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: stock_por_almacen
-- Stock por almacén
-- =====================================================
CREATE TABLE IF NOT EXISTS `stock_por_almacen` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `producto_id` INT(11) NOT NULL,
  `almacen_id` INT(11) NOT NULL,
  `cantidad` DECIMAL(10,2) DEFAULT 0.00,
  `ubicacion` VARCHAR(100) DEFAULT NULL COMMENT 'Pasillo-Estante-Nivel',
  `fecha_actualizacion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `producto_almacen` (`producto_id`, `almacen_id`),
  KEY `idx_almacen` (`almacen_id`),
  CONSTRAINT `fk_stock_almacen_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_stock_almacen_almacen` FOREIGN KEY (`almacen_id`) REFERENCES `almacenes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: ordenes_compra
-- Órdenes de compra a proveedores
-- =====================================================
CREATE TABLE IF NOT EXISTS `ordenes_compra` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `proveedor_id` INT(11) NOT NULL,
  `numero_orden` VARCHAR(50) NOT NULL,
  `fecha_orden` DATE NOT NULL,
  `fecha_entrega_estimada` DATE DEFAULT NULL,
  `estado` ENUM('borrador','enviada','confirmada','recibida_parcial','recibida','cancelada') DEFAULT 'borrador',
  `subtotal` DECIMAL(15,2) DEFAULT 0.00,
  `impuesto` DECIMAL(15,2) DEFAULT 0.00,
  `total` DECIMAL(15,2) DEFAULT 0.00,
  `comprador_id` INT(11) DEFAULT NULL,
  `observaciones` TEXT DEFAULT NULL,
  `fecha_creacion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `creado_por` INT(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `empresa_numero` (`empresa_id`, `numero_orden`),
  KEY `idx_proveedor` (`proveedor_id`),
  KEY `idx_estado` (`estado`),
  CONSTRAINT `fk_ordenes_compra_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ordenes_compra_proveedor` FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: orden_compra_detalle
-- Detalle de órdenes de compra
-- =====================================================
CREATE TABLE IF NOT EXISTS `orden_compra_detalle` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `orden_compra_id` INT(11) NOT NULL,
  `producto_id` INT(11) NOT NULL,
  `cantidad_pedida` DECIMAL(10,2) NOT NULL,
  `cantidad_recibida` DECIMAL(10,2) DEFAULT 0.00,
  `precio_unitario` DECIMAL(15,2) NOT NULL,
  `subtotal` DECIMAL(15,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_orden` (`orden_compra_id`),
  KEY `idx_producto` (`producto_id`),
  CONSTRAINT `fk_orden_detalle_orden` FOREIGN KEY (`orden_compra_id`) REFERENCES `ordenes_compra` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_orden_detalle_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: recepciones
-- Recepciones de mercadería
-- =====================================================
CREATE TABLE IF NOT EXISTS `recepciones` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `orden_compra_id` INT(11) DEFAULT NULL,
  `almacen_id` INT(11) NOT NULL,
  `numero_recepcion` VARCHAR(50) NOT NULL,
  `fecha_recepcion` DATE NOT NULL,
  `responsable_id` INT(11) DEFAULT NULL,
  `observaciones` TEXT DEFAULT NULL,
  `fecha_creacion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `creado_por` INT(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `empresa_numero` (`empresa_id`, `numero_recepcion`),
  CONSTRAINT `fk_recepciones_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- VISTA: Stock total por producto
-- =====================================================
CREATE OR REPLACE VIEW v_stock_total AS
SELECT
    p.empresa_id,
    p.id as producto_id,
    p.sku,
    p.nombre,
    COALESCE(SUM(spa.cantidad), 0) as stock_total,
    COUNT(DISTINCT spa.almacen_id) as almacenes_con_stock
FROM productos p
LEFT JOIN stock_por_almacen spa ON p.id = spa.producto_id
GROUP BY p.empresa_id, p.id, p.sku, p.nombre;

FLUSH PRIVILEGES;
