-- =====================================================
-- MÓDULO: FACTURACIÓN ELECTRÓNICA (DTE)
-- Sistema Completo de Documentos Tributarios Electrónicos
-- Compatible con SII Chile 2025
-- =====================================================

USE conectae_conectaerpbd;

-- =====================================================
-- TABLA: tipos_documentos_sii
-- Catálogo completo de todos los tipos de documentos del SII
-- =====================================================
CREATE TABLE IF NOT EXISTS `tipos_documentos_sii` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `codigo` INT(11) NOT NULL COMMENT 'Código oficial del SII',
  `nombre` VARCHAR(255) NOT NULL,
  `categoria` ENUM('factura','boleta','nota_credito','nota_debito','guia_despacho','liquidacion','exportacion','otros') NOT NULL,
  `descripcion` TEXT DEFAULT NULL,
  `electronico` TINYINT(1) DEFAULT 1 COMMENT '1=Documento electrónico, 0=Solo papel',
  `requiere_folio` TINYINT(1) DEFAULT 1 COMMENT '1=Requiere folios CAF del SII',
  `afecto_iva` TINYINT(1) DEFAULT 1 COMMENT '1=Afecto a IVA, 0=Exento',
  `permite_credito_fiscal` TINYINT(1) DEFAULT 1,
  `fase_implementacion` TINYINT(1) DEFAULT 1 COMMENT '1=Esencial, 2=Importante, 3=Exportación, 4=Especializado',
  `activo` TINYINT(1) DEFAULT 1,
  `orden` INT(11) DEFAULT 0,
  `fecha_creacion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo_unico` (`codigo`),
  KEY `idx_categoria` (`categoria`),
  KEY `idx_electronico` (`electronico`),
  KEY `idx_fase` (`fase_implementacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Catálogo de tipos de documentos tributarios del SII';

-- =====================================================
-- TABLA: folios_caf
-- Archivos CAF (Código de Autorización de Folios) del SII
-- =====================================================
CREATE TABLE IF NOT EXISTS `folios_caf` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `tipo_documento` INT(11) NOT NULL COMMENT 'Código tipo documento SII',
  `folio_desde` INT(11) NOT NULL,
  `folio_hasta` INT(11) NOT NULL,
  `folio_actual` INT(11) NOT NULL COMMENT 'Próximo folio a usar',
  `fecha_autorizacion` DATE NOT NULL,
  `fecha_vencimiento` DATE DEFAULT NULL,
  `xml_caf` LONGTEXT NOT NULL COMMENT 'XML completo del CAF',
  `rut_autorizado` VARCHAR(20) NOT NULL,
  `firma_electronica` TEXT DEFAULT NULL,
  `estado` ENUM('activo','agotado','suspendido','vencido') DEFAULT 'activo',
  `cantidad_total` INT(11) GENERATED ALWAYS AS ((`folio_hasta` - `folio_desde`) + 1) STORED,
  `cantidad_usados` INT(11) GENERATED ALWAYS AS (`folio_actual` - `folio_desde`) STORED,
  `cantidad_disponible` INT(11) GENERATED ALWAYS AS ((`folio_hasta` - `folio_actual`) + 1) STORED,
  `archivo_nombre` VARCHAR(255) DEFAULT NULL,
  `fecha_carga` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `cargado_por` INT(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `empresa_tipo_rango` (`empresa_id`, `tipo_documento`, `folio_desde`, `folio_hasta`),
  KEY `idx_empresa_tipo` (`empresa_id`, `tipo_documento`),
  KEY `idx_estado` (`estado`),
  KEY `idx_folios` (`folio_actual`, `folio_hasta`),
  CONSTRAINT `fk_folios_caf_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_folios_caf_tipo` FOREIGN KEY (`tipo_documento`) REFERENCES `tipos_documentos_sii` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Folios autorizados por el SII (CAF)';

-- =====================================================
-- TABLA: documentos_tributarios
-- Documentos tributarios electrónicos (DTE) emitidos
-- =====================================================
CREATE TABLE IF NOT EXISTS `documentos_tributarios` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `tipo_documento` INT(11) NOT NULL,
  `folio` INT(11) NOT NULL,
  `folio_caf_id` INT(11) DEFAULT NULL,

  -- Fechas
  `fecha_emision` DATE NOT NULL,
  `fecha_vencimiento` DATE DEFAULT NULL,

  -- Datos del Emisor (se toman de la empresa, pero se replican por seguridad)
  `rut_emisor` VARCHAR(20) NOT NULL,
  `razon_social_emisor` VARCHAR(255) NOT NULL,
  `giro_emisor` VARCHAR(255) DEFAULT NULL,
  `direccion_emisor` VARCHAR(500) DEFAULT NULL,
  `comuna_emisor` VARCHAR(100) DEFAULT NULL,
  `ciudad_emisor` VARCHAR(100) DEFAULT NULL,

  -- Datos del Receptor (Cliente)
  `cliente_id` INT(11) DEFAULT NULL,
  `rut_receptor` VARCHAR(20) NOT NULL,
  `razon_social_receptor` VARCHAR(255) NOT NULL,
  `giro_receptor` VARCHAR(255) DEFAULT NULL,
  `direccion_receptor` VARCHAR(500) DEFAULT NULL,
  `comuna_receptor` VARCHAR(100) DEFAULT NULL,
  `ciudad_receptor` VARCHAR(100) DEFAULT NULL,
  `contacto_receptor` VARCHAR(100) DEFAULT NULL,
  `email_receptor` VARCHAR(255) DEFAULT NULL,

  -- Datos Comerciales
  `forma_pago` TINYINT(1) DEFAULT 1 COMMENT '1=Contado, 2=Crédito, 3=Sin Costo',
  `medio_pago` VARCHAR(50) DEFAULT NULL COMMENT 'Efectivo, Transferencia, Tarjeta, etc',

  -- Montos
  `monto_neto` DECIMAL(15,2) DEFAULT 0.00,
  `monto_exento` DECIMAL(15,2) DEFAULT 0.00,
  `monto_iva` DECIMAL(15,2) DEFAULT 0.00,
  `tasa_iva` DECIMAL(5,2) DEFAULT 19.00,
  `monto_impuestos_adicionales` DECIMAL(15,2) DEFAULT 0.00,
  `monto_total` DECIMAL(15,2) NOT NULL,
  `descuento_global_pct` DECIMAL(5,2) DEFAULT 0.00,
  `descuento_global_monto` DECIMAL(15,2) DEFAULT 0.00,
  `recargo_global_monto` DECIMAL(15,2) DEFAULT 0.00,

  -- Datos Específicos de Guía de Despacho
  `tipo_traslado` TINYINT(1) DEFAULT NULL COMMENT 'Para guías: 1-9 según SII',
  `indicador_traslado` TINYINT(1) DEFAULT NULL COMMENT '1=Vendedor, 2=Comprador',
  `direccion_entrega` VARCHAR(500) DEFAULT NULL,
  `comuna_entrega` VARCHAR(100) DEFAULT NULL,
  `ciudad_entrega` VARCHAR(100) DEFAULT NULL,
  `patente_vehiculo` VARCHAR(20) DEFAULT NULL,
  `rut_transportista` VARCHAR(20) DEFAULT NULL,
  `nombre_transportista` VARCHAR(255) DEFAULT NULL,

  -- Referencias a Otros Documentos
  `referencia_tipo` INT(11) DEFAULT NULL COMMENT 'Tipo de documento referenciado',
  `referencia_folio` VARCHAR(50) DEFAULT NULL,
  `referencia_fecha` DATE DEFAULT NULL,
  `referencia_razon` VARCHAR(255) DEFAULT NULL,
  `referencia_codigo` TINYINT(1) DEFAULT NULL COMMENT '1=Anula, 2=Corrige texto, 3=Corrige monto',

  -- Observaciones y Notas
  `observaciones` TEXT DEFAULT NULL,
  `notas` TEXT DEFAULT NULL,
  `terminos_condiciones` TEXT DEFAULT NULL,

  -- DTE y Firma Digital
  `xml_dte` LONGTEXT DEFAULT NULL COMMENT 'XML del DTE generado',
  `xml_firmado` LONGTEXT DEFAULT NULL COMMENT 'XML firmado',
  `ted` LONGTEXT DEFAULT NULL COMMENT 'Timbre Electrónico (TED)',
  `firma_digital` TEXT DEFAULT NULL,
  `certificado_digital_id` INT(11) DEFAULT NULL,

  -- Estado del Documento
  `estado` ENUM('borrador','timbrado','enviado','aceptado','aceptado_con_reparos','rechazado','anulado','cedido') DEFAULT 'borrador',
  `estado_sii` VARCHAR(50) DEFAULT NULL COMMENT 'Estado según respuesta del SII',
  `glosa_estado_sii` VARCHAR(255) DEFAULT NULL,

  -- Envío al SII
  `track_id_sii` VARCHAR(50) DEFAULT NULL COMMENT 'Track ID del envío al SII',
  `fecha_envio_sii` DATETIME DEFAULT NULL,
  `fecha_recepcion_sii` DATETIME DEFAULT NULL,
  `intentos_envio` INT(11) DEFAULT 0,
  `xml_respuesta_sii` LONGTEXT DEFAULT NULL,

  -- Anulación
  `motivo_anulacion` VARCHAR(500) DEFAULT NULL,
  `fecha_anulacion` DATETIME DEFAULT NULL,
  `anulado_por` INT(11) DEFAULT NULL,

  -- PDF Generado
  `pdf_generado` TINYINT(1) DEFAULT 0,
  `pdf_ruta` VARCHAR(500) DEFAULT NULL,
  `pdf_hash` VARCHAR(64) DEFAULT NULL,

  -- Metadata
  `origen` ENUM('manual','pos','api','importacion','sistema') DEFAULT 'manual',
  `sucursal_id` INT(11) DEFAULT NULL,
  `vendedor_id` INT(11) DEFAULT NULL,
  `proyecto_id` INT(11) DEFAULT NULL,

  -- Auditoría
  `fecha_creacion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_modificacion` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `creado_por` INT(11) DEFAULT NULL,
  `modificado_por` INT(11) DEFAULT NULL,

  PRIMARY KEY (`id`),
  UNIQUE KEY `empresa_tipo_folio` (`empresa_id`, `tipo_documento`, `folio`),
  KEY `idx_empresa_fecha` (`empresa_id`, `fecha_emision`),
  KEY `idx_tipo_documento` (`tipo_documento`),
  KEY `idx_folio` (`folio`),
  KEY `idx_estado` (`estado`),
  KEY `idx_track_sii` (`track_id_sii`),
  KEY `idx_cliente` (`cliente_id`),
  KEY `idx_rut_receptor` (`rut_receptor`),
  KEY `idx_fecha_emision` (`fecha_emision`),
  KEY `idx_monto_total` (`monto_total`),
  KEY `idx_referencia` (`referencia_tipo`, `referencia_folio`),
  CONSTRAINT `fk_dte_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_dte_tipo` FOREIGN KEY (`tipo_documento`) REFERENCES `tipos_documentos_sii` (`codigo`),
  CONSTRAINT `fk_dte_folio_caf` FOREIGN KEY (`folio_caf_id`) REFERENCES `folios_caf` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_dte_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Documentos Tributarios Electrónicos (DTE)';

-- =====================================================
-- TABLA: documentos_tributarios_detalle
-- Detalle de líneas de los documentos (productos/servicios)
-- =====================================================
CREATE TABLE IF NOT EXISTS `documentos_tributarios_detalle` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `documento_id` INT(11) NOT NULL,
  `numero_linea` INT(11) NOT NULL,

  -- Producto/Servicio
  `producto_id` INT(11) DEFAULT NULL,
  `tipo_item` ENUM('producto','servicio','cargo','descuento','otro') DEFAULT 'producto',
  `codigo_item` VARCHAR(100) DEFAULT NULL,
  `nombre` VARCHAR(500) NOT NULL,
  `descripcion` TEXT DEFAULT NULL,

  -- Cantidades y Unidades
  `cantidad` DECIMAL(10,3) NOT NULL DEFAULT 1.000,
  `unidad_medida` VARCHAR(20) DEFAULT 'UN' COMMENT 'UN, KG, LT, MT, etc',

  -- Precios
  `precio_unitario` DECIMAL(15,2) NOT NULL,
  `descuento_pct` DECIMAL(5,2) DEFAULT 0.00,
  `descuento_monto` DECIMAL(15,2) DEFAULT 0.00,
  `recargo_pct` DECIMAL(5,2) DEFAULT 0.00,
  `recargo_monto` DECIMAL(15,2) DEFAULT 0.00,

  -- Montos Calculados
  `monto_subtotal` DECIMAL(15,2) NOT NULL COMMENT 'cantidad * precio_unitario',
  `monto_neto_linea` DECIMAL(15,2) NOT NULL COMMENT 'Después de desc/rec',
  `monto_iva_linea` DECIMAL(15,2) DEFAULT 0.00,
  `monto_total_linea` DECIMAL(15,2) NOT NULL,

  -- Indicadores Tributarios
  `afecto_iva` TINYINT(1) DEFAULT 1,
  `exento` TINYINT(1) DEFAULT 0,
  `codigo_impuesto_adicional` VARCHAR(10) DEFAULT NULL,
  `tasa_impuesto_adicional` DECIMAL(5,2) DEFAULT 0.00,
  `monto_impuesto_adicional` DECIMAL(15,2) DEFAULT 0.00,

  -- Metadata
  `orden` INT(11) DEFAULT 0,
  `notas` TEXT DEFAULT NULL,

  PRIMARY KEY (`id`),
  KEY `idx_documento` (`documento_id`),
  KEY `idx_producto` (`producto_id`),
  KEY `idx_numero_linea` (`documento_id`, `numero_linea`),
  CONSTRAINT `fk_dte_detalle_documento` FOREIGN KEY (`documento_id`) REFERENCES `documentos_tributarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_dte_detalle_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Detalle de líneas de documentos tributarios';

-- =====================================================
-- TABLA: dte_envios_sii
-- Log de envíos al SII
-- =====================================================
CREATE TABLE IF NOT EXISTS `dte_envios_sii` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `documento_id` INT(11) DEFAULT NULL,
  `tipo_envio` ENUM('dte','libro_compras','libro_ventas','cesion','dte_masivo') DEFAULT 'dte',
  `track_id` VARCHAR(50) DEFAULT NULL,
  `xml_envio` LONGTEXT DEFAULT NULL,
  `xml_respuesta` LONGTEXT DEFAULT NULL,
  `estado_envio` ENUM('pendiente','enviado','aceptado','rechazado','error') DEFAULT 'pendiente',
  `codigo_respuesta` VARCHAR(10) DEFAULT NULL,
  `glosa_respuesta` TEXT DEFAULT NULL,
  `fecha_envio` DATETIME NOT NULL,
  `fecha_respuesta` DATETIME DEFAULT NULL,
  `ip_origen` VARCHAR(45) DEFAULT NULL,
  `usuario_id` INT(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_empresa` (`empresa_id`),
  KEY `idx_documento` (`documento_id`),
  KEY `idx_track_id` (`track_id`),
  KEY `idx_estado` (`estado_envio`),
  KEY `idx_fecha_envio` (`fecha_envio`),
  CONSTRAINT `fk_envio_sii_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_envio_sii_documento` FOREIGN KEY (`documento_id`) REFERENCES `documentos_tributarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Log de envíos de DTE al SII';

-- =====================================================
-- TABLA: dte_referencias
-- Referencias entre documentos (para NC, ND, etc)
-- =====================================================
CREATE TABLE IF NOT EXISTS `dte_referencias` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `documento_origen_id` INT(11) NOT NULL COMMENT 'Documento que hace la referencia',
  `numero_linea_ref` INT(11) NOT NULL DEFAULT 1,
  `tipo_documento_ref` INT(11) NOT NULL COMMENT 'Tipo del documento referenciado',
  `folio_ref` VARCHAR(50) NOT NULL,
  `rut_otro` VARCHAR(20) DEFAULT NULL,
  `fecha_documento_ref` DATE DEFAULT NULL,
  `codigo_ref` TINYINT(1) DEFAULT NULL COMMENT '1=Anula, 2=Corrige texto, 3=Corrige monto',
  `razon_referencia` VARCHAR(255) DEFAULT NULL,
  `fecha_creacion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_documento_origen` (`documento_origen_id`),
  KEY `idx_tipo_folio_ref` (`tipo_documento_ref`, `folio_ref`),
  CONSTRAINT `fk_ref_documento_origen` FOREIGN KEY (`documento_origen_id`) REFERENCES `documentos_tributarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Referencias entre documentos tributarios';

-- =====================================================
-- TABLA: certificados_digitales
-- Certificados digitales para firma electrónica
-- =====================================================
CREATE TABLE IF NOT EXISTS `certificados_digitales` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `nombre` VARCHAR(255) NOT NULL,
  `rut_titular` VARCHAR(20) NOT NULL,
  `archivo_pfx` LONGBLOB DEFAULT NULL COMMENT 'Archivo .pfx del certificado',
  `password_encriptado` VARCHAR(500) DEFAULT NULL,
  `fecha_emision` DATE DEFAULT NULL,
  `fecha_vencimiento` DATE NOT NULL,
  `emisor` VARCHAR(255) DEFAULT NULL,
  `serial_number` VARCHAR(100) DEFAULT NULL,
  `activo` TINYINT(1) DEFAULT 1,
  `principal` TINYINT(1) DEFAULT 0 COMMENT '1=Certificado principal de la empresa',
  `fecha_carga` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `cargado_por` INT(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_empresa` (`empresa_id`),
  KEY `idx_activo` (`activo`, `principal`),
  KEY `idx_vencimiento` (`fecha_vencimiento`),
  CONSTRAINT `fk_certificado_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Certificados digitales para firma de DTE';

-- =====================================================
-- INSERTAR DATOS: Tipos de Documentos del SII
-- Catálogo completo de todos los tipos de DTE
-- =====================================================

INSERT INTO `tipos_documentos_sii` (`codigo`, `nombre`, `categoria`, `descripcion`, `electronico`, `requiere_folio`, `afecto_iva`, `permite_credito_fiscal`, `fase_implementacion`, `activo`, `orden`) VALUES

-- FASE 1: DOCUMENTOS ESENCIALES (Más Usados)
(33, 'Factura Electrónica', 'factura', 'Factura afecta a IVA para venta de bienes y servicios', 1, 1, 1, 1, 1, 1, 1),
(34, 'Factura No Afecta o Exenta Electrónica', 'factura', 'Factura exenta de IVA', 1, 1, 0, 0, 2, 1, 2),
(39, 'Boleta Electrónica', 'boleta', 'Boleta afecta a IVA para ventas al consumidor final', 1, 1, 1, 0, 1, 1, 3),
(41, 'Boleta No Afecta o Exenta Electrónica', 'boleta', 'Boleta exenta de IVA', 1, 1, 0, 0, 2, 1, 4),
(52, 'Guía de Despacho Electrónica', 'guia_despacho', 'Guía para traslado de mercaderías', 1, 1, 0, 0, 1, 1, 5),
(56, 'Nota de Débito Electrónica', 'nota_debito', 'Nota de débito para aumentar el monto de una factura', 1, 1, 1, 1, 1, 1, 6),
(61, 'Nota de Crédito Electrónica', 'nota_credito', 'Nota de crédito para corregir o anular facturas', 1, 1, 1, 1, 1, 1, 7),

-- FASE 2: DOCUMENTOS IMPORTANTES
(43, 'Liquidación-Factura Electrónica', 'liquidacion', 'Liquidación de productos agropecuarios, forestales, etc', 1, 1, 1, 1, 2, 1, 8),
(46, 'Factura de Compra Electrónica', 'factura', 'Factura de compra a pequeños productores', 1, 1, 1, 1, 2, 1, 9),
(48, 'Comprobante de Pago Electrónico', 'otros', 'Comprobante de pago de honorarios', 1, 1, 0, 0, 2, 1, 10),

-- FASE 3: DOCUMENTOS DE EXPORTACIÓN
(110, 'Factura de Exportación Electrónica', 'exportacion', 'Factura para ventas de exportación', 1, 1, 0, 0, 3, 1, 11),
(111, 'Nota de Débito de Exportación Electrónica', 'exportacion', 'Nota de débito para facturas de exportación', 1, 1, 0, 0, 3, 1, 12),
(112, 'Nota de Crédito de Exportación Electrónica', 'exportacion', 'Nota de crédito para facturas de exportación', 1, 1, 0, 0, 3, 1, 13),

-- FASE 4: DOCUMENTOS ESPECIALIZADOS
(103, 'Liquidación', 'liquidacion', 'Liquidación de servicios', 1, 1, 1, 1, 4, 1, 14),
(108, 'Factura Electrónica Propuesta (SII)', 'otros', 'Factura propuesta por el SII', 1, 0, 1, 1, 4, 1, 15),
(914, 'Declaración de Ingreso (Zona Franca)', 'otros', 'Declaración de ingreso a zona franca', 1, 1, 0, 0, 4, 1, 16),
(911, 'Declaración de Egreso (Zona Franca)', 'otros', 'Declaración de egreso de zona franca', 1, 1, 0, 0, 4, 1, 17),
(906, 'Factura de Ventas a Empresas del Territorio Preferencial', 'otros', 'Factura para territorio preferencial (Isla de Pascua, Prov. Palena)', 1, 1, 0, 0, 4, 1, 18),
(801, 'Orden de Compra', 'otros', 'Orden de compra electrónica', 1, 0, 0, 0, 4, 1, 19),
(802, 'Nota de Pedido', 'otros', 'Nota de pedido electrónica', 1, 0, 0, 0, 4, 1, 20);

-- =====================================================
-- VISTAS: Para consultas y reportes
-- =====================================================

-- Vista de documentos con datos completos
CREATE OR REPLACE VIEW v_documentos_tributarios AS
SELECT
    dt.id,
    dt.empresa_id,
    e.razon_social as empresa_razon_social,
    e.rut as empresa_rut,
    dt.tipo_documento,
    tds.nombre as tipo_documento_nombre,
    tds.categoria,
    dt.folio,
    CONCAT(tds.codigo, '-', dt.folio) as numero_documento,
    dt.fecha_emision,
    dt.fecha_vencimiento,
    dt.rut_receptor,
    dt.razon_social_receptor,
    dt.monto_neto,
    dt.monto_iva,
    dt.monto_total,
    dt.estado,
    dt.estado_sii,
    dt.track_id_sii,
    dt.fecha_envio_sii,
    dt.pdf_generado,
    CASE
        WHEN dt.estado = 'aceptado' THEN 'success'
        WHEN dt.estado = 'rechazado' THEN 'danger'
        WHEN dt.estado = 'enviado' THEN 'warning'
        WHEN dt.estado = 'timbrado' THEN 'info'
        WHEN dt.estado = 'anulado' THEN 'dark'
        ELSE 'secondary'
    END as badge_color,
    dt.fecha_creacion
FROM documentos_tributarios dt
INNER JOIN empresas e ON dt.empresa_id = e.id
INNER JOIN tipos_documentos_sii tds ON dt.tipo_documento = tds.codigo;

-- Vista de estadísticas por tipo de documento
CREATE OR REPLACE VIEW v_estadisticas_dte_por_tipo AS
SELECT
    dt.empresa_id,
    dt.tipo_documento,
    tds.nombre as tipo_nombre,
    tds.categoria,
    COUNT(*) as total_documentos,
    SUM(CASE WHEN dt.estado = 'aceptado' THEN 1 ELSE 0 END) as total_aceptados,
    SUM(CASE WHEN dt.estado = 'rechazado' THEN 1 ELSE 0 END) as total_rechazados,
    SUM(CASE WHEN dt.estado = 'enviado' THEN 1 ELSE 0 END) as total_enviados,
    SUM(CASE WHEN dt.estado = 'borrador' THEN 1 ELSE 0 END) as total_borradores,
    SUM(CASE WHEN dt.estado = 'anulado' THEN 1 ELSE 0 END) as total_anulados,
    SUM(dt.monto_total) as monto_total,
    AVG(dt.monto_total) as monto_promedio,
    MAX(dt.fecha_emision) as ultima_emision
FROM documentos_tributarios dt
INNER JOIN tipos_documentos_sii tds ON dt.tipo_documento = tds.codigo
GROUP BY dt.empresa_id, dt.tipo_documento, tds.nombre, tds.categoria;

-- Vista de folios disponibles
CREATE OR REPLACE VIEW v_folios_disponibles AS
SELECT
    fc.empresa_id,
    e.razon_social as empresa_razon_social,
    fc.tipo_documento,
    tds.nombre as tipo_documento_nombre,
    COUNT(*) as cantidad_cafs,
    MIN(fc.folio_actual) as primer_folio_disponible,
    MAX(fc.folio_hasta) as ultimo_folio_disponible,
    SUM(fc.cantidad_disponible) as total_folios_disponibles,
    SUM(fc.cantidad_usados) as total_folios_usados,
    MAX(fc.fecha_autorizacion) as ultima_carga,
    CASE
        WHEN SUM(fc.cantidad_disponible) = 0 THEN 'agotado'
        WHEN SUM(fc.cantidad_disponible) < 50 THEN 'critico'
        WHEN SUM(fc.cantidad_disponible) < 100 THEN 'bajo'
        ELSE 'normal'
    END as estado_alerta
FROM folios_caf fc
INNER JOIN empresas e ON fc.empresa_id = e.id
INNER JOIN tipos_documentos_sii tds ON fc.tipo_documento = tds.codigo
WHERE fc.estado = 'activo'
GROUP BY fc.empresa_id, e.razon_social, fc.tipo_documento, tds.nombre;

-- =====================================================
-- TRIGGERS: Automatización
-- =====================================================

-- Trigger: Actualizar folio CAF al crear documento
DELIMITER //
CREATE TRIGGER tr_documentos_tributarios_after_insert
AFTER INSERT ON documentos_tributarios
FOR EACH ROW
BEGIN
    -- Actualizar folio_actual en CAF si el documento fue timbrado
    IF NEW.folio_caf_id IS NOT NULL AND NEW.estado IN ('timbrado', 'enviado', 'aceptado') THEN
        UPDATE folios_caf
        SET folio_actual = NEW.folio + 1,
            estado = CASE
                WHEN folio_actual >= folio_hasta THEN 'agotado'
                ELSE 'activo'
            END
        WHERE id = NEW.folio_caf_id;
    END IF;
END//
DELIMITER ;

-- =====================================================
-- ÍNDICES ADICIONALES PARA OPTIMIZACIÓN
-- =====================================================

-- Índice para búsquedas por fecha y empresa
CREATE INDEX idx_dte_empresa_fecha_tipo ON documentos_tributarios(empresa_id, fecha_emision DESC, tipo_documento);

-- Índice para reportes de ventas
CREATE INDEX idx_dte_ventas ON documentos_tributarios(empresa_id, tipo_documento, estado, fecha_emision);

-- Índice para búsquedas por RUT receptor
CREATE INDEX idx_dte_receptor_fecha ON documentos_tributarios(rut_receptor, fecha_emision DESC);

-- =====================================================
-- PERMISOS Y FINALIZACIÓN
-- =====================================================

FLUSH PRIVILEGES;

-- =====================================================
-- FIN DEL SCRIPT
-- =====================================================
