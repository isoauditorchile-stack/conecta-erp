-- =====================================================
-- MÓDULO 5: VENTAS (SD) - CONECTA ERP
-- Sales & Distribution
-- =====================================================

USE conectae_conectaerpbd;

-- =====================================================
-- TABLA: pedidos
-- Pedidos de venta
-- =====================================================
CREATE TABLE IF NOT EXISTS `pedidos` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `cliente_id` INT(11) NOT NULL,
  `numero_pedido` VARCHAR(50) NOT NULL,
  `fecha_pedido` DATE NOT NULL,
  `fecha_entrega_estimada` DATE DEFAULT NULL,
  `estado` ENUM('borrador','confirmado','en_proceso','enviado','entregado','cancelado') DEFAULT 'borrador',
  `subtotal` DECIMAL(15,2) DEFAULT 0.00,
  `impuesto` DECIMAL(15,2) DEFAULT 0.00,
  `descuento` DECIMAL(15,2) DEFAULT 0.00,
  `total` DECIMAL(15,2) DEFAULT 0.00,
  `vendedor_id` INT(11) DEFAULT NULL,
  `observaciones` TEXT DEFAULT NULL,
  `direccion_entrega` TEXT DEFAULT NULL,
  `fecha_creacion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `creado_por` INT(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `empresa_numero` (`empresa_id`, `numero_pedido`),
  KEY `idx_cliente` (`cliente_id`),
  KEY `idx_estado` (`estado`),
  KEY `idx_fecha` (`fecha_pedido`),
  CONSTRAINT `fk_pedidos_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pedidos_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: pedido_detalle
-- Líneas de pedido
-- =====================================================
CREATE TABLE IF NOT EXISTS `pedido_detalle` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `pedido_id` INT(11) NOT NULL,
  `producto_id` INT(11) NOT NULL,
  `cantidad` DECIMAL(10,2) NOT NULL,
  `precio_unitario` DECIMAL(15,2) NOT NULL,
  `descuento_porcentaje` DECIMAL(5,2) DEFAULT 0.00,
  `subtotal` DECIMAL(15,2) NOT NULL,
  `total_linea` DECIMAL(15,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pedido` (`pedido_id`),
  KEY `idx_producto` (`producto_id`),
  CONSTRAINT `fk_pedido_detalle_pedido` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pedido_detalle_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: cotizaciones
-- Cotizaciones de venta
-- =====================================================
CREATE TABLE IF NOT EXISTS `cotizaciones` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `cliente_id` INT(11) NOT NULL,
  `numero_cotizacion` VARCHAR(50) NOT NULL,
  `fecha_cotizacion` DATE NOT NULL,
  `fecha_vencimiento` DATE DEFAULT NULL,
  `estado` ENUM('pendiente','aprobada','rechazada','vencida') DEFAULT 'pendiente',
  `total` DECIMAL(15,2) DEFAULT 0.00,
  `vendedor_id` INT(11) DEFAULT NULL,
  `observaciones` TEXT DEFAULT NULL,
  `fecha_creacion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `creado_por` INT(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `empresa_numero` (`empresa_id`, `numero_cotizacion`),
  CONSTRAINT `fk_cotizaciones_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: promociones
-- Promociones y descuentos
-- =====================================================
CREATE TABLE IF NOT EXISTS `promociones` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `codigo` VARCHAR(50) NOT NULL,
  `nombre` VARCHAR(255) NOT NULL,
  `descripcion` TEXT DEFAULT NULL,
  `tipo` ENUM('descuento_porcentaje','descuento_monto','2x1','3x2','regalo') DEFAULT 'descuento_porcentaje',
  `valor` DECIMAL(15,2) DEFAULT 0.00,
  `fecha_inicio` DATE NOT NULL,
  `fecha_fin` DATE NOT NULL,
  `activo` TINYINT(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `empresa_codigo` (`empresa_id`, `codigo`),
  CONSTRAINT `fk_promociones_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: devoluciones
-- Devoluciones de venta
-- =====================================================
CREATE TABLE IF NOT EXISTS `devoluciones` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `factura_id` INT(11) DEFAULT NULL,
  `cliente_id` INT(11) NOT NULL,
  `numero_devolucion` VARCHAR(50) NOT NULL,
  `fecha_devolucion` DATE NOT NULL,
  `motivo` TEXT NOT NULL,
  `total_devuelto` DECIMAL(15,2) DEFAULT 0.00,
  `estado` ENUM('pendiente','aprobada','rechazada','procesada') DEFAULT 'pendiente',
  `fecha_creacion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `creado_por` INT(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `empresa_numero` (`empresa_id`, `numero_devolucion`),
  CONSTRAINT `fk_devoluciones_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- VISTA: Resumen de ventas
-- =====================================================
CREATE OR REPLACE VIEW v_resumen_ventas AS
SELECT
    f.empresa_id,
    DATE_FORMAT(f.fecha_emision, '%Y-%m') as periodo,
    COUNT(*) as total_facturas,
    SUM(f.total) as monto_total,
    AVG(f.total) as ticket_promedio
FROM facturas f
WHERE f.estado IN ('emitida', 'pagada')
GROUP BY f.empresa_id, periodo;

FLUSH PRIVILEGES;
