-- =====================================================
-- TABLAS ADICIONALES PARA DASHBOARD - CONECTA ERP
-- Tablas de soporte para funcionalidad del dashboard
-- =====================================================

USE conectae_conectaerpbd;

-- =====================================================
-- TABLA: notificaciones
-- Sistema de notificaciones del usuario
-- =====================================================
CREATE TABLE IF NOT EXISTS `notificaciones` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` INT(11) NOT NULL,
  `empresa_id` INT(11) NOT NULL,
  `tipo` ENUM('info','warning','success','error') DEFAULT 'info',
  `titulo` VARCHAR(255) NOT NULL,
  `mensaje` TEXT NOT NULL,
  `url` VARCHAR(255) DEFAULT NULL COMMENT 'URL destino al hacer clic',
  `leida` TINYINT(1) DEFAULT 0,
  `fecha_creacion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_leida` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_empresa` (`empresa_id`),
  KEY `idx_leida` (`leida`),
  KEY `idx_fecha` (`fecha_creacion`),
  KEY `idx_usuario_leida` (`usuario_id`, `leida`),
  CONSTRAINT `fk_notificaciones_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_notificaciones_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: usuario_favoritos
-- Módulos favoritos del usuario para acceso rápido
-- =====================================================
CREATE TABLE IF NOT EXISTS `usuario_favoritos` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` INT(11) NOT NULL,
  `modulo` VARCHAR(100) NOT NULL,
  `url` VARCHAR(255) NOT NULL,
  `icono` VARCHAR(100) DEFAULT 'star',
  `titulo` VARCHAR(255) NOT NULL,
  `orden` TINYINT(2) DEFAULT 0,
  `fecha_creacion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `usuario_modulo` (`usuario_id`, `modulo`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_orden` (`orden`),
  CONSTRAINT `fk_favoritos_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: facturas
-- Facturas de ventas (referenciada en dashboard)
-- =====================================================
CREATE TABLE IF NOT EXISTS `facturas` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `cliente_id` INT(11) NOT NULL,
  `numero_factura` VARCHAR(50) NOT NULL,
  `tipo_documento` ENUM('factura','boleta','nota_credito','nota_debito','factura_exenta') DEFAULT 'factura',
  `fecha_emision` DATE NOT NULL,
  `fecha_vencimiento` DATE DEFAULT NULL,
  `subtotal` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `impuesto` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `total` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `estado` ENUM('borrador','emitida','pagada','anulada','vencida') DEFAULT 'borrador',
  `folio_sii` VARCHAR(50) DEFAULT NULL COMMENT 'Folio asignado por SII',
  `dte_xml` LONGTEXT DEFAULT NULL COMMENT 'XML del DTE firmado',
  `ted` TEXT DEFAULT NULL COMMENT 'Timbre Electrónico',
  `observaciones` TEXT DEFAULT NULL,
  `condicion_pago` VARCHAR(100) DEFAULT 'contado',
  `vendedor_id` INT(11) DEFAULT NULL,
  `moneda` VARCHAR(3) DEFAULT 'CLP',
  `tasa_cambio` DECIMAL(10,4) DEFAULT 1.0000,
  `referencia_id` INT(11) DEFAULT NULL COMMENT 'Referencia a documento original (NC/ND)',
  `fecha_creacion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `creado_por` INT(11) DEFAULT NULL,
  `fecha_modificacion` TIMESTAMP NULL DEFAULT NULL,
  `modificado_por` INT(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `empresa_numero` (`empresa_id`, `numero_factura`),
  KEY `idx_empresa` (`empresa_id`),
  KEY `idx_cliente` (`cliente_id`),
  KEY `idx_fecha_emision` (`fecha_emision`),
  KEY `idx_estado` (`estado`),
  KEY `idx_folio_sii` (`folio_sii`),
  KEY `idx_vendedor` (`vendedor_id`),
  CONSTRAINT `fk_facturas_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_facturas_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_facturas_vendedor` FOREIGN KEY (`vendedor_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: factura_detalle
-- Detalle de líneas de factura
-- =====================================================
CREATE TABLE IF NOT EXISTS `factura_detalle` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `factura_id` INT(11) NOT NULL,
  `producto_id` INT(11) DEFAULT NULL,
  `descripcion` VARCHAR(500) NOT NULL,
  `cantidad` DECIMAL(10,2) NOT NULL,
  `precio_unitario` DECIMAL(15,2) NOT NULL,
  `descuento_porcentaje` DECIMAL(5,2) DEFAULT 0.00,
  `descuento_monto` DECIMAL(15,2) DEFAULT 0.00,
  `impuesto_porcentaje` DECIMAL(5,2) DEFAULT 19.00,
  `subtotal` DECIMAL(15,2) NOT NULL,
  `total_linea` DECIMAL(15,2) NOT NULL,
  `orden` TINYINT(3) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_factura` (`factura_id`),
  KEY `idx_producto` (`producto_id`),
  CONSTRAINT `fk_factura_detalle_factura` FOREIGN KEY (`factura_id`) REFERENCES `facturas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_factura_detalle_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- ÍNDICES ADICIONALES PARA OPTIMIZACIÓN
-- =====================================================

-- Índice compuesto para consultas de notificaciones pendientes
ALTER TABLE `notificaciones` ADD INDEX `idx_usuario_empresa_leida` (`usuario_id`, `empresa_id`, `leida`);

-- Índice para búsquedas de facturas por empresa y fecha
ALTER TABLE `facturas` ADD INDEX `idx_empresa_fecha` (`empresa_id`, `fecha_emision`);

-- =====================================================
-- TRIGGERS PARA AUDITORÍA
-- =====================================================

DELIMITER //

-- Trigger: Marcar fecha de lectura en notificaciones
CREATE TRIGGER IF NOT EXISTS `tr_notificaciones_leida` BEFORE UPDATE ON `notificaciones`
FOR EACH ROW
BEGIN
    IF OLD.leida = 0 AND NEW.leida = 1 THEN
        SET NEW.fecha_leida = NOW();
    END IF;
END//

-- Trigger: Actualizar totales de factura al insertar detalle
CREATE TRIGGER IF NOT EXISTS `tr_factura_detalle_insert` AFTER INSERT ON `factura_detalle`
FOR EACH ROW
BEGIN
    UPDATE facturas SET
        subtotal = (SELECT SUM(subtotal) FROM factura_detalle WHERE factura_id = NEW.factura_id),
        impuesto = (SELECT SUM(total_linea - subtotal) FROM factura_detalle WHERE factura_id = NEW.factura_id),
        total = (SELECT SUM(total_linea) FROM factura_detalle WHERE factura_id = NEW.factura_id)
    WHERE id = NEW.factura_id;
END//

-- Trigger: Actualizar totales de factura al modificar detalle
CREATE TRIGGER IF NOT EXISTS `tr_factura_detalle_update` AFTER UPDATE ON `factura_detalle`
FOR EACH ROW
BEGIN
    UPDATE facturas SET
        subtotal = (SELECT SUM(subtotal) FROM factura_detalle WHERE factura_id = NEW.factura_id),
        impuesto = (SELECT SUM(total_linea - subtotal) FROM factura_detalle WHERE factura_id = NEW.factura_id),
        total = (SELECT SUM(total_linea) FROM factura_detalle WHERE factura_id = NEW.factura_id)
    WHERE id = NEW.factura_id;
END//

-- Trigger: Actualizar totales de factura al eliminar detalle
CREATE TRIGGER IF NOT EXISTS `tr_factura_detalle_delete` AFTER DELETE ON `factura_detalle`
FOR EACH ROW
BEGIN
    UPDATE facturas SET
        subtotal = (SELECT COALESCE(SUM(subtotal), 0) FROM factura_detalle WHERE factura_id = OLD.factura_id),
        impuesto = (SELECT COALESCE(SUM(total_linea - subtotal), 0) FROM factura_detalle WHERE factura_id = OLD.factura_id),
        total = (SELECT COALESCE(SUM(total_linea), 0) FROM factura_detalle WHERE factura_id = OLD.factura_id)
    WHERE id = OLD.factura_id;
END//

DELIMITER ;

-- =====================================================
-- VISTAS PARA CONSULTAS COMUNES
-- =====================================================

-- Vista: Facturas con información completa
CREATE OR REPLACE VIEW v_facturas_completas AS
SELECT
    f.id,
    f.empresa_id,
    e.nombre as empresa_nombre,
    f.cliente_id,
    c.razon_social as cliente_nombre,
    c.rut as cliente_rut,
    f.numero_factura,
    f.tipo_documento,
    f.fecha_emision,
    f.fecha_vencimiento,
    f.subtotal,
    f.impuesto,
    f.total,
    f.estado,
    f.folio_sii,
    f.condicion_pago,
    f.moneda,
    DATEDIFF(CURDATE(), f.fecha_vencimiento) as dias_vencido,
    CASE
        WHEN f.estado = 'pagada' THEN 'Al día'
        WHEN f.fecha_vencimiento < CURDATE() AND f.estado != 'pagada' THEN 'Vencida'
        WHEN DATEDIFF(f.fecha_vencimiento, CURDATE()) <= 7 AND f.estado != 'pagada' THEN 'Por vencer'
        ELSE 'Normal'
    END as clasificacion_pago,
    u.nombre_completo as vendedor_nombre,
    f.fecha_creacion
FROM facturas f
INNER JOIN empresas e ON f.empresa_id = e.id
INNER JOIN clientes c ON f.cliente_id = c.id
LEFT JOIN usuarios u ON f.vendedor_id = u.id;

-- Vista: Resumen de ventas mensuales por empresa
CREATE OR REPLACE VIEW v_ventas_mensuales AS
SELECT
    empresa_id,
    YEAR(fecha_emision) as ano,
    MONTH(fecha_emision) as mes,
    DATE_FORMAT(fecha_emision, '%Y-%m') as periodo,
    COUNT(*) as total_facturas,
    SUM(CASE WHEN estado = 'emitida' OR estado = 'pagada' THEN 1 ELSE 0 END) as facturas_validas,
    SUM(CASE WHEN estado = 'anulada' THEN 1 ELSE 0 END) as facturas_anuladas,
    SUM(CASE WHEN estado = 'pagada' THEN total ELSE 0 END) as monto_pagado,
    SUM(CASE WHEN estado = 'emitida' THEN total ELSE 0 END) as monto_pendiente,
    SUM(CASE WHEN estado != 'anulada' THEN total ELSE 0 END) as monto_total
FROM facturas
GROUP BY empresa_id, YEAR(fecha_emision), MONTH(fecha_emision);

-- =====================================================
-- DATOS DE PRUEBA (solo para desarrollo)
-- Comentar en producción
-- =====================================================

-- Insertar notificaciones de ejemplo (comentar en producción)
/*
INSERT INTO notificaciones (usuario_id, empresa_id, tipo, titulo, mensaje, leida) VALUES
(1, 1, 'warning', 'Facturas por vencer', 'Tienes 3 facturas que vencen en los próximos 7 días', 0),
(1, 1, 'info', 'Stock bajo', 'Hay 5 productos con stock por debajo del mínimo', 0),
(1, 1, 'success', 'Backup completado', 'El respaldo automático se completó correctamente', 1);
*/

-- Insertar favoritos por defecto (comentar en producción)
/*
INSERT INTO usuario_favoritos (usuario_id, modulo, url, icono, titulo, orden) VALUES
(1, 'clientes', '../modulos/entidades/gestion_clientes.php', 'users', 'Clientes', 1),
(1, 'facturas', '../modulos/finanzas/comprobantes_facturas.php', 'file-invoice', 'Facturas', 2),
(1, 'productos', '../modulos/entidades/productos_servicios.php', 'box', 'Productos', 3),
(1, 'inventario', '../modulos/materiales/inventario.php', 'warehouse', 'Inventario', 4);
*/

-- =====================================================
-- PERMISOS Y SEGURIDAD
-- =====================================================

FLUSH PRIVILEGES;
