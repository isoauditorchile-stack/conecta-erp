-- =====================================================
-- MÓDULO 12: BUSINESS INTELLIGENCE - CONECTA ERP
-- Analytics y Reporting
-- =====================================================

USE conectae_conectaerpbd;

-- =====================================================
-- TABLA: reportes_personalizados
-- Definición de reportes personalizados
-- =====================================================
CREATE TABLE IF NOT EXISTS `reportes_personalizados` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `usuario_id` INT(11) NOT NULL,
  `nombre` VARCHAR(255) NOT NULL,
  `descripcion` TEXT DEFAULT NULL,
  `tipo` ENUM('tabla','grafico','dashboard','kpi') DEFAULT 'tabla',
  `query_sql` TEXT NOT NULL COMMENT 'Query SQL del reporte',
  `parametros` JSON DEFAULT NULL COMMENT 'Parámetros configurables',
  `formato_salida` ENUM('pdf','excel','csv','html') DEFAULT 'pdf',
  `compartido` TINYINT(1) DEFAULT 0,
  `fecha_creacion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_empresa` (`empresa_id`),
  KEY `idx_usuario` (`usuario_id`),
  CONSTRAINT `fk_reportes_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_reportes_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: kpis_empresariales
-- Definición de KPIs configurables
-- =====================================================
CREATE TABLE IF NOT EXISTS `kpis_empresariales` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `codigo` VARCHAR(50) NOT NULL,
  `nombre` VARCHAR(255) NOT NULL,
  `descripcion` TEXT DEFAULT NULL,
  `categoria` ENUM('ventas','financiero','operacional','rrhh','produccion','calidad') DEFAULT 'operacional',
  `formula` TEXT NOT NULL COMMENT 'Fórmula de cálculo',
  `unidad_medida` VARCHAR(50) DEFAULT NULL,
  `meta` DECIMAL(15,2) DEFAULT NULL,
  `frecuencia_calculo` ENUM('diario','semanal','mensual','trimestral','anual') DEFAULT 'mensual',
  `activo` TINYINT(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `empresa_codigo` (`empresa_id`, `codigo`),
  CONSTRAINT `fk_kpis_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: mediciones_kpis
-- Valores medidos de KPIs
-- =====================================================
CREATE TABLE IF NOT EXISTS `mediciones_kpis` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `kpi_id` INT(11) NOT NULL,
  `periodo` DATE NOT NULL,
  `valor_real` DECIMAL(15,2) NOT NULL,
  `valor_meta` DECIMAL(15,2) DEFAULT NULL,
  `cumplimiento` DECIMAL(5,2) GENERATED ALWAYS AS (
    CASE WHEN valor_meta > 0 THEN (valor_real / valor_meta * 100) ELSE 0 END
  ) STORED,
  `observaciones` TEXT DEFAULT NULL,
  `calculado_automaticamente` TINYINT(1) DEFAULT 1,
  `fecha_creacion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kpi_periodo` (`kpi_id`, `periodo`),
  KEY `idx_periodo` (`periodo`),
  CONSTRAINT `fk_mediciones_kpi` FOREIGN KEY (`kpi_id`) REFERENCES `kpis_empresariales` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: cubos_olap
-- Definición de cubos OLAP para análisis multidimensional
-- =====================================================
CREATE TABLE IF NOT EXISTS `cubos_olap` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `nombre` VARCHAR(255) NOT NULL,
  `descripcion` TEXT DEFAULT NULL,
  `dimensiones` JSON NOT NULL COMMENT 'Dimensiones del cubo',
  `metricas` JSON NOT NULL COMMENT 'Métricas a analizar',
  `tabla_hechos` VARCHAR(100) NOT NULL,
  `fecha_actualizacion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_empresa` (`empresa_id`),
  CONSTRAINT `fk_cubos_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- VISTAS DE ANÁLISIS EMPRESARIAL
-- =====================================================

-- Vista: Análisis de ventas por período
CREATE OR REPLACE VIEW v_analisis_ventas_periodo AS
SELECT
    f.empresa_id,
    DATE_FORMAT(f.fecha_emision, '%Y') as ano,
    DATE_FORMAT(f.fecha_emision, '%m') as mes,
    DATE_FORMAT(f.fecha_emision, '%Y-%m') as periodo,
    COUNT(DISTINCT f.id) as total_facturas,
    COUNT(DISTINCT f.cliente_id) as clientes_unicos,
    SUM(f.subtotal) as subtotal,
    SUM(f.impuesto) as impuesto,
    SUM(f.total) as total_ventas,
    AVG(f.total) as ticket_promedio,
    SUM(CASE WHEN f.estado = 'pagada' THEN f.total ELSE 0 END) as ventas_pagadas,
    SUM(CASE WHEN f.estado = 'emitida' THEN f.total ELSE 0 END) as ventas_pendientes
FROM facturas f
WHERE f.estado != 'anulada'
GROUP BY f.empresa_id, ano, mes, periodo;

-- Vista: Top productos vendidos
CREATE OR REPLACE VIEW v_top_productos_vendidos AS
SELECT
    fd.empresa_id,
    p.id as producto_id,
    p.nombre as producto,
    p.sku,
    COUNT(DISTINCT fd.factura_id) as num_facturas,
    SUM(fd.cantidad) as cantidad_vendida,
    SUM(fd.total_linea) as ventas_totales,
    AVG(fd.precio_unitario) as precio_promedio
FROM factura_detalle fd
INNER JOIN productos p ON fd.producto_id = p.id
INNER JOIN facturas f ON fd.factura_id = f.id
WHERE f.estado != 'anulada'
GROUP BY fd.empresa_id, p.id, p.nombre, p.sku
ORDER BY ventas_totales DESC;

-- Vista: Análisis ABC de clientes
CREATE OR REPLACE VIEW v_analisis_abc_clientes AS
SELECT
    c.empresa_id,
    c.id as cliente_id,
    c.razon_social,
    c.clasificacion,
    COUNT(DISTINCT f.id) as total_compras,
    SUM(f.total) as ventas_totales,
    AVG(f.total) as ticket_promedio,
    MAX(f.fecha_emision) as ultima_compra,
    DATEDIFF(CURDATE(), MAX(f.fecha_emision)) as dias_sin_comprar
FROM clientes c
LEFT JOIN facturas f ON c.id = f.cliente_id AND f.estado != 'anulada'
GROUP BY c.empresa_id, c.id, c.razon_social, c.clasificacion;

-- Vista: Margen de contribución por producto
CREATE OR REPLACE VIEW v_margen_contribucion_productos AS
SELECT
    p.empresa_id,
    p.id as producto_id,
    p.nombre,
    p.sku,
    p.precio_venta,
    p.costo_promedio,
    (p.precio_venta - p.costo_promedio) as margen_unitario,
    ((p.precio_venta - p.costo_promedio) / p.precio_venta * 100) as porcentaje_margen,
    p.stock_actual,
    (p.stock_actual * p.costo_promedio) as valor_inventario
FROM productos p
WHERE p.precio_venta > 0;

FLUSH PRIVILEGES;
