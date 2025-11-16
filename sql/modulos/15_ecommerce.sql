-- =====================================================
-- MÓDULO 14: E-COMMERCE
-- Sistema de comercio electrónico integrado
-- =====================================================

USE conectae_conectaerpbd;

-- Tabla: Catálogo Web
CREATE TABLE IF NOT EXISTS `catalogo_web` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `producto_id` INT(11) NOT NULL,
  `titulo` VARCHAR(255) NOT NULL,
  `descripcion_corta` VARCHAR(500),
  `descripcion_larga` TEXT,
  `slug` VARCHAR(255) NOT NULL,
  `precio_web` DECIMAL(15,2) NOT NULL,
  `precio_oferta` DECIMAL(15,2) DEFAULT NULL,
  `stock_web` INT(11) DEFAULT 0,
  `imagenes` TEXT COMMENT 'JSON array de URLs de imágenes',
  `seo_titulo` VARCHAR(255),
  `seo_descripcion` TEXT,
  `seo_keywords` VARCHAR(500),
  `destacado` TINYINT(1) DEFAULT 0,
  `nuevo` TINYINT(1) DEFAULT 0,
  `oferta` TINYINT(1) DEFAULT 0,
  `visible` TINYINT(1) DEFAULT 1,
  `orden` INT(11) DEFAULT 0,
  `fecha_publicacion` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `visitas` INT(11) DEFAULT 0,
  `ventas_count` INT(11) DEFAULT 0,
  `rating_promedio` DECIMAL(3,2) DEFAULT 0.00,
  `total_reviews` INT(11) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_slug` (`empresa_id`, `slug`),
  KEY `idx_producto` (`producto_id`),
  KEY `idx_visible` (`visible`, `destacado`),
  CONSTRAINT `fk_catalogo_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`),
  CONSTRAINT `fk_catalogo_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: Carritos de Compra
CREATE TABLE IF NOT EXISTS `carritos` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `cliente_id` INT(11) DEFAULT NULL,
  `session_id` VARCHAR(100) NOT NULL,
  `estado` ENUM('activo','abandonado','convertido','expirado') DEFAULT 'activo',
  `subtotal` DECIMAL(15,2) DEFAULT 0.00,
  `descuento` DECIMAL(15,2) DEFAULT 0.00,
  `envio` DECIMAL(15,2) DEFAULT 0.00,
  `impuestos` DECIMAL(15,2) DEFAULT 0.00,
  `total` DECIMAL(15,2) GENERATED ALWAYS AS (`subtotal` - `descuento` + `envio` + `impuestos`) STORED,
  `cupon_codigo` VARCHAR(50) DEFAULT NULL,
  `ip_address` VARCHAR(50),
  `user_agent` VARCHAR(255),
  `fecha_expiracion` DATETIME,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_session` (`session_id`),
  KEY `idx_cliente` (`cliente_id`),
  KEY `idx_estado` (`estado`),
  CONSTRAINT `fk_carrito_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`),
  CONSTRAINT `fk_carrito_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: Detalle de Carritos
CREATE TABLE IF NOT EXISTS `carrito_detalle` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `carrito_id` INT(11) NOT NULL,
  `producto_id` INT(11) NOT NULL,
  `cantidad` INT(11) NOT NULL,
  `precio_unitario` DECIMAL(15,2) NOT NULL,
  `descuento_linea` DECIMAL(15,2) DEFAULT 0.00,
  `total_linea` DECIMAL(15,2) GENERATED ALWAYS AS ((`cantidad` * `precio_unitario`) - `descuento_linea`) STORED,
  `opciones` TEXT COMMENT 'JSON con variantes (talla, color, etc)',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_carrito` (`carrito_id`),
  KEY `idx_producto` (`producto_id`),
  CONSTRAINT `fk_carrito_detalle` FOREIGN KEY (`carrito_id`) REFERENCES `carritos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_carrito_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: Pedidos Online
CREATE TABLE IF NOT EXISTS `pedidos_online` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `numero_pedido` VARCHAR(50) NOT NULL,
  `cliente_id` INT(11) NOT NULL,
  `carrito_id` INT(11),
  `estado` ENUM('pendiente','confirmado','procesando','enviado','entregado','cancelado','devuelto') DEFAULT 'pendiente',
  `estado_pago` ENUM('pendiente','pagado','fallido','reembolsado') DEFAULT 'pendiente',
  `metodo_pago` VARCHAR(50),
  `subtotal` DECIMAL(15,2) NOT NULL,
  `descuento` DECIMAL(15,2) DEFAULT 0.00,
  `envio` DECIMAL(15,2) DEFAULT 0.00,
  `impuestos` DECIMAL(15,2) NOT NULL,
  `total` DECIMAL(15,2) NOT NULL,
  `direccion_envio` TEXT,
  `direccion_facturacion` TEXT,
  `notas_cliente` TEXT,
  `tracking_number` VARCHAR(100),
  `transportista` VARCHAR(100),
  `fecha_pedido` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `fecha_pago` DATETIME,
  `fecha_envio` DATETIME,
  `fecha_entrega` DATETIME,
  `ip_address` VARCHAR(50),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_numero_pedido` (`empresa_id`, `numero_pedido`),
  KEY `idx_cliente` (`cliente_id`),
  KEY `idx_estado` (`estado`),
  KEY `idx_fecha` (`fecha_pedido`),
  CONSTRAINT `fk_pedido_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`),
  CONSTRAINT `fk_pedido_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`),
  CONSTRAINT `fk_pedido_carrito` FOREIGN KEY (`carrito_id`) REFERENCES `carritos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: Detalle de Pedidos Online
CREATE TABLE IF NOT EXISTS `pedido_online_detalle` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `pedido_id` INT(11) NOT NULL,
  `linea` INT(11) NOT NULL,
  `producto_id` INT(11) NOT NULL,
  `nombre_producto` VARCHAR(255) NOT NULL,
  `cantidad` INT(11) NOT NULL,
  `precio_unitario` DECIMAL(15,2) NOT NULL,
  `descuento` DECIMAL(15,2) DEFAULT 0.00,
  `total_linea` DECIMAL(15,2) NOT NULL,
  `opciones` TEXT COMMENT 'JSON con variantes',
  PRIMARY KEY (`id`),
  KEY `idx_pedido` (`pedido_id`),
  KEY `idx_producto` (`producto_id`),
  CONSTRAINT `fk_pedido_detalle` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos_online` (`id`),
  CONSTRAINT `fk_pedido_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: Transacciones de Pago
CREATE TABLE IF NOT EXISTS `transacciones_pago` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `pedido_id` INT(11) NOT NULL,
  `pasarela` VARCHAR(50) NOT NULL COMMENT 'transbank, mercadopago, paypal, stripe',
  `transaction_id` VARCHAR(255),
  `monto` DECIMAL(15,2) NOT NULL,
  `moneda` VARCHAR(3) DEFAULT 'CLP',
  `estado` ENUM('pendiente','aprobado','rechazado','error','reembolsado') DEFAULT 'pendiente',
  `codigo_autorizacion` VARCHAR(100),
  `tipo_tarjeta` VARCHAR(50),
  `ultimos_4_digitos` VARCHAR(4),
  `cuotas` INT(11) DEFAULT 1,
  `request_data` TEXT COMMENT 'JSON request',
  `response_data` TEXT COMMENT 'JSON response',
  `error_message` TEXT,
  `ip_address` VARCHAR(50),
  `fecha_transaccion` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pedido` (`pedido_id`),
  KEY `idx_transaction` (`transaction_id`),
  KEY `idx_estado` (`estado`),
  CONSTRAINT `fk_transaccion_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`),
  CONSTRAINT `fk_transaccion_pedido` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos_online` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: Cupones de Descuento
CREATE TABLE IF NOT EXISTS `cupones` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `codigo` VARCHAR(50) NOT NULL,
  `descripcion` VARCHAR(255),
  `tipo_descuento` ENUM('porcentaje','monto_fijo','envio_gratis') DEFAULT 'porcentaje',
  `valor_descuento` DECIMAL(15,2) NOT NULL,
  `monto_minimo` DECIMAL(15,2) DEFAULT 0.00,
  `usos_maximos` INT(11),
  `usos_por_cliente` INT(11) DEFAULT 1,
  `usos_actuales` INT(11) DEFAULT 0,
  `fecha_inicio` DATE NOT NULL,
  `fecha_fin` DATE NOT NULL,
  `activo` TINYINT(1) DEFAULT 1,
  `productos_aplicables` TEXT COMMENT 'JSON array de IDs de productos',
  `categorias_aplicables` TEXT COMMENT 'JSON array de IDs de categorías',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_codigo` (`empresa_id`, `codigo`),
  CONSTRAINT `fk_cupon_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: Reviews de Productos
CREATE TABLE IF NOT EXISTS `producto_reviews` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `producto_id` INT(11) NOT NULL,
  `cliente_id` INT(11) NOT NULL,
  `pedido_id` INT(11),
  `rating` INT(1) NOT NULL CHECK (`rating` >= 1 AND `rating` <= 5),
  `titulo` VARCHAR(255),
  `comentario` TEXT,
  `verificado` TINYINT(1) DEFAULT 0 COMMENT 'Compra verificada',
  `aprobado` TINYINT(1) DEFAULT 0,
  `util_count` INT(11) DEFAULT 0,
  `fecha_review` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_producto` (`producto_id`),
  KEY `idx_cliente` (`cliente_id`),
  KEY `idx_aprobado` (`aprobado`),
  CONSTRAINT `fk_review_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`),
  CONSTRAINT `fk_review_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`),
  CONSTRAINT `fk_review_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`),
  CONSTRAINT `fk_review_pedido` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos_online` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- VISTAS
-- =====================================================

-- Vista: Productos más vendidos
CREATE OR REPLACE VIEW `v_productos_mas_vendidos` AS
SELECT
  p.id,
  p.nombre,
  p.codigo,
  c.titulo as titulo_web,
  SUM(pod.cantidad) as total_vendido,
  SUM(pod.total_linea) as total_ingresos,
  AVG(pod.precio_unitario) as precio_promedio,
  COUNT(DISTINCT po.id) as total_pedidos,
  c.rating_promedio,
  c.total_reviews
FROM productos p
INNER JOIN catalogo_web c ON p.id = c.producto_id
INNER JOIN pedido_online_detalle pod ON p.id = pod.producto_id
INNER JOIN pedidos_online po ON pod.pedido_id = po.id
WHERE po.estado NOT IN ('cancelado', 'devuelto')
GROUP BY p.id
ORDER BY total_vendido DESC;

-- Vista: Estadísticas de Carritos Abandonados
CREATE OR REPLACE VIEW `v_carritos_abandonados` AS
SELECT
  c.empresa_id,
  DATE(c.created_at) as fecha,
  COUNT(*) as total_abandonados,
  SUM(c.total) as valor_perdido,
  AVG(c.total) as ticket_promedio,
  COUNT(DISTINCT c.cliente_id) as clientes_unicos
FROM carritos c
WHERE c.estado = 'abandonado'
  AND c.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY c.empresa_id, DATE(c.created_at);

-- Vista: Dashboard E-commerce
CREATE OR REPLACE VIEW `v_dashboard_ecommerce` AS
SELECT
  po.empresa_id,
  COUNT(*) as total_pedidos,
  SUM(CASE WHEN po.estado_pago = 'pagado' THEN po.total ELSE 0 END) as ventas_totales,
  AVG(CASE WHEN po.estado_pago = 'pagado' THEN po.total ELSE NULL END) as ticket_promedio,
  COUNT(DISTINCT po.cliente_id) as clientes_unicos,
  SUM(CASE WHEN po.estado = 'pendiente' THEN 1 ELSE 0 END) as pedidos_pendientes,
  SUM(CASE WHEN po.estado = 'procesando' THEN 1 ELSE 0 END) as pedidos_procesando,
  SUM(CASE WHEN po.estado = 'enviado' THEN 1 ELSE 0 END) as pedidos_enviados,
  SUM(CASE WHEN po.estado = 'entregado' THEN 1 ELSE 0 END) as pedidos_entregados,
  SUM(CASE WHEN po.estado = 'cancelado' THEN 1 ELSE 0 END) as pedidos_cancelados
FROM pedidos_online po
WHERE po.fecha_pedido >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY po.empresa_id;

-- =====================================================
-- TRIGGERS
-- =====================================================

-- Trigger: Actualizar stock web cuando se crea un pedido
DELIMITER $$
CREATE TRIGGER `tr_pedido_actualizar_stock` AFTER INSERT ON `pedido_online_detalle`
FOR EACH ROW
BEGIN
  UPDATE catalogo_web
  SET stock_web = stock_web - NEW.cantidad,
      ventas_count = ventas_count + 1
  WHERE producto_id = NEW.producto_id;
END$$
DELIMITER ;

-- Trigger: Actualizar rating promedio del producto
DELIMITER $$
CREATE TRIGGER `tr_review_update_rating` AFTER INSERT ON `producto_reviews`
FOR EACH ROW
BEGIN
  IF NEW.aprobado = 1 THEN
    UPDATE catalogo_web
    SET rating_promedio = (
      SELECT AVG(rating)
      FROM producto_reviews
      WHERE producto_id = NEW.producto_id AND aprobado = 1
    ),
    total_reviews = (
      SELECT COUNT(*)
      FROM producto_reviews
      WHERE producto_id = NEW.producto_id AND aprobado = 1
    )
    WHERE producto_id = NEW.producto_id;
  END IF;
END$$
DELIMITER ;

-- Trigger: Auto-numeración de pedidos
DELIMITER $$
CREATE TRIGGER `tr_pedido_numero` BEFORE INSERT ON `pedidos_online`
FOR EACH ROW
BEGIN
  DECLARE siguiente INT;

  SELECT COALESCE(MAX(CAST(SUBSTRING(numero_pedido, 3) AS UNSIGNED)), 0) + 1
  INTO siguiente
  FROM pedidos_online
  WHERE empresa_id = NEW.empresa_id;

  SET NEW.numero_pedido = CONCAT('PO', LPAD(siguiente, 6, '0'));
END$$
DELIMITER ;

-- =====================================================
-- EVENTOS PROGRAMADOS
-- =====================================================

-- Evento: Marcar carritos como abandonados (24 horas inactivos)
DELIMITER $$
CREATE EVENT IF NOT EXISTS `ev_marcar_carritos_abandonados`
ON SCHEDULE EVERY 1 HOUR
DO
BEGIN
  UPDATE carritos
  SET estado = 'abandonado'
  WHERE estado = 'activo'
    AND updated_at < DATE_SUB(NOW(), INTERVAL 24 HOUR);
END$$
DELIMITER ;

-- Evento: Limpiar carritos expirados (30 días)
DELIMITER $$
CREATE EVENT IF NOT EXISTS `ev_limpiar_carritos_expirados`
ON SCHEDULE EVERY 1 DAY
DO
BEGIN
  DELETE FROM carritos
  WHERE estado IN ('abandonado', 'expirado')
    AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
END$$
DELIMITER ;

-- =====================================================
-- DATOS INICIALES
-- =====================================================

-- Insertar cupones de ejemplo
INSERT INTO `cupones` (`empresa_id`, `codigo`, `descripcion`, `tipo_descuento`, `valor_descuento`, `monto_minimo`, `usos_maximos`, `fecha_inicio`, `fecha_fin`) VALUES
(1, 'BIENVENIDO10', 'Descuento 10% nuevos clientes', 'porcentaje', 10.00, 10000, 100, '2025-01-01', '2025-12-31'),
(1, 'ENVIOGRATIS', 'Envío gratis compras sobre $50.000', 'envio_gratis', 0.00, 50000, NULL, '2025-01-01', '2025-12-31'),
(1, 'VERANO2025', 'Descuento $5.000 temporada verano', 'monto_fijo', 5000.00, 30000, 500, '2025-01-01', '2025-03-31');
