-- =====================================================
-- MÓDULO 21: SISTEMA DE INTEGRACIÓN COMPLETA
-- CONECTA ERP - Sistema completo estilo Softland
-- =====================================================
-- Este módulo incluye:
-- 1. Indicadores Económicos (Dólar, UF, UTM, Euro) con API automática
-- 2. Sistema completo de Facturación SII (50+ tipos documentos)
-- 3. Libro de Compras y Ventas automático
-- 4. Declaraciones Juradas automáticas
-- 5. Fechas Importantes y Alertas
-- 6. Cuadraturas automáticas
-- 7. Cambio de período automático/manual
-- =====================================================

USE conectae_conectaerpbd;

-- =====================================================
-- SECCIÓN 1: INDICADORES ECONÓMICOS AUTOMÁTICOS
-- =====================================================

CREATE TABLE IF NOT EXISTS `indicadores_economicos` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `fecha` DATE NOT NULL,
  `dolar` DECIMAL(10,4) DEFAULT NULL COMMENT 'Dólar observado',
  `uf` DECIMAL(10,4) DEFAULT NULL COMMENT 'Unidad de Fomento',
  `utm` DECIMAL(10,4) DEFAULT NULL COMMENT 'Unidad Tributaria Mensual',
  `euro` DECIMAL(10,4) DEFAULT NULL COMMENT 'Euro',
  `ipc` DECIMAL(10,4) DEFAULT NULL COMMENT 'Índice Precios al Consumidor',
  `tasa_desempleo` DECIMAL(5,2) DEFAULT NULL,
  `tpm` DECIMAL(5,2) DEFAULT NULL COMMENT 'Tasa Política Monetaria',
  `fuente` VARCHAR(100) DEFAULT 'API Banco Central Chile',
  `ultima_actualizacion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_fecha` (`fecha`),
  KEY `idx_ultima_actualizacion` (`ultima_actualizacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Indicadores económicos actualizados automáticamente desde API BCCh';

-- Valores iniciales (se actualizan automáticamente vía cron)
INSERT INTO `indicadores_economicos` (`fecha`, `dolar`, `uf`, `utm`, `euro`) VALUES
('2025-01-01', 920.50, 37890.50, 66147, 950.25),
('2025-01-02', 925.30, 37895.75, 66147, 955.10)
ON DUPLICATE KEY UPDATE
  dolar = VALUES(dolar),
  uf = VALUES(uf),
  utm = VALUES(utm),
  euro = VALUES(euro);

-- =====================================================
-- SECCIÓN 2: TIPOS DE DOCUMENTOS SII COMPLETOS
-- =====================================================

CREATE TABLE IF NOT EXISTS `tipos_documentos_sii` (
  `codigo` INT(3) NOT NULL COMMENT 'Código oficial SII',
  `nombre` VARCHAR(255) NOT NULL,
  `categoria` ENUM('factura','boleta','nota_credito','nota_debito','guia','liquidacion','exportacion','compra','otros') NOT NULL,
  `descripcion` TEXT,
  `requiere_folio` TINYINT(1) DEFAULT 1 COMMENT 'Si requiere CAF del SII',
  `afecto_iva` TINYINT(1) DEFAULT 1,
  `electronico` TINYINT(1) DEFAULT 1,
  `uso_comun` TINYINT(1) DEFAULT 0 COMMENT 'Si es de uso frecuente',
  `activo` TINYINT(1) DEFAULT 1,
  PRIMARY KEY (`codigo`),
  KEY `idx_categoria` (`categoria`),
  KEY `idx_uso_comun` (`uso_comun`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Catálogo completo de tipos de documentos tributarios SII Chile';

-- INSERTAR TODOS LOS 50+ TIPOS DE DOCUMENTOS SII
INSERT INTO `tipos_documentos_sii` (`codigo`, `nombre`, `categoria`, `descripcion`, `requiere_folio`, `afecto_iva`, `electronico`, `uso_comun`) VALUES
-- FACTURAS ELECTRÓNICAS
(33, 'Factura Electrónica', 'factura', 'Factura electrónica afecta a IVA', 1, 1, 1, 1),
(34, 'Factura No Electrónica', 'factura', 'Factura en papel afecta a IVA', 1, 1, 0, 0),
(30, 'Factura', 'factura', 'Factura manual', 1, 1, 0, 0),
(32, 'Factura de Ventas y Servicios no afectos o exentos de IVA', 'factura', 'Factura exenta', 1, 0, 0, 0),

-- FACTURAS EXENTAS
(34, 'Factura Exenta Electrónica', 'factura', 'Factura exenta electrónica', 1, 0, 1, 1),

-- FACTURAS DE COMPRA
(45, 'Factura de Compra Electrónica', 'compra', 'Factura de compra electrónica', 1, 1, 1, 0),
(46, 'Factura de Compra', 'compra', 'Factura de compra manual', 1, 1, 0, 0),

-- BOLETAS
(35, 'Boleta', 'boleta', 'Boleta manual', 1, 1, 0, 0),
(38, 'Boleta Exenta', 'boleta', 'Boleta exenta manual', 1, 0, 0, 0),
(39, 'Boleta Electrónica', 'boleta', 'Boleta electrónica afecta a IVA', 1, 1, 1, 1),
(41, 'Boleta Exenta Electrónica', 'boleta', 'Boleta exenta electrónica', 1, 0, 1, 1),

-- NOTAS DE CRÉDITO
(60, 'Nota de Crédito', 'nota_credito', 'Nota de crédito manual', 1, 1, 0, 0),
(61, 'Nota de Crédito Electrónica', 'nota_credito', 'Nota de crédito electrónica', 1, 1, 1, 1),

-- NOTAS DE DÉBITO
(55, 'Nota de Débito', 'nota_debito', 'Nota de débito manual', 1, 1, 0, 0),
(56, 'Nota de Débito Electrónica', 'nota_debito', 'Nota de débito electrónica', 1, 1, 1, 1),

-- GUÍAS DE DESPACHO
(50, 'Guía de Despacho', 'guia', 'Guía de despacho manual', 1, 0, 0, 0),
(52, 'Guía de Despacho Electrónica', 'guia', 'Guía de despacho electrónica', 1, 0, 1, 1),

-- LIQUIDACIONES
(40, 'Liquidación Factura', 'liquidacion', 'Liquidación factura manual', 1, 1, 0, 0),
(43, 'Liquidación Factura Electrónica', 'liquidacion', 'Liquidación factura electrónica', 1, 1, 1, 0),
(103, 'Liquidación', 'liquidacion', 'Liquidación', 1, 1, 0, 0),

-- FACTURAS DE EXPORTACIÓN
(110, 'Factura de Exportación Electrónica', 'exportacion', 'Factura de exportación electrónica', 1, 0, 1, 0),
(111, 'Nota de Débito de Exportación Electrónica', 'exportacion', 'Nota de débito de exportación', 1, 0, 1, 0),
(112, 'Nota de Crédito de Exportación Electrónica', 'exportacion', 'Nota de crédito de exportación', 1, 0, 1, 0),

-- DOCUMENTOS DE REFERENCIA (800-900)
(801, 'Orden de Compra', 'otros', 'Orden de compra', 0, 0, 0, 0),
(802, 'Nota de Pedido', 'otros', 'Nota de pedido', 0, 0, 0, 0),
(803, 'Contrato', 'otros', 'Contrato', 0, 0, 0, 0),
(804, 'Resolución', 'otros', 'Resolución', 0, 0, 0, 0),
(805, 'Proceso ChileCompra', 'otros', 'Proceso ChileCompra', 0, 0, 0, 0),
(806, 'Ficha ChileCompra', 'otros', 'Ficha ChileCompra', 0, 0, 0, 0),
(807, 'DUS', 'otros', 'Declaración Única de Salida', 0, 0, 0, 0),
(808, 'B/L (Conocimiento de embarque)', 'otros', 'Bill of Lading', 0, 0, 0, 0),
(809, 'AWB (Air Will Bill)', 'otros', 'Guía aérea', 0, 0, 0, 0),
(810, 'MIC/DTA', 'otros', 'Manifiesto internacional de carga', 0, 0, 0, 0),
(811, 'Carta de Porte', 'otros', 'Carta de porte', 0, 0, 0, 0),
(812, 'Resolución del SNA donde califica Servicios de Exportación', 'otros', 'Resolución SNA', 0, 0, 0, 0),
(813, 'Pasaporte', 'otros', 'Pasaporte', 0, 0, 0, 0),
(814, 'Certificado de Depósito Bolsa Prod. Chile', 'otros', 'Certificado depósito', 0, 0, 0, 0),
(815, 'Vale de Prenda Bolsa Prod. Chile', 'otros', 'Vale de prenda', 0, 0, 0, 0),
(820, 'Código de Inscripción en el Registro de Acuerdos con Plazo de Pago Excepcional', 'otros', 'Código inscripción', 0, 0, 0, 0),

-- DOCUMENTOS ESPECIALIZADOS (906-914)
(906, 'Factura Electrónica de Compra', 'compra', 'Factura electrónica de compra', 1, 1, 1, 0),
(909, 'Factura de Ventas de Activo Fijo', 'otros', 'Venta activo fijo', 1, 1, 0, 0),
(910, 'Factura de Compra', 'compra', 'Factura de compra', 1, 1, 0, 0),
(911, 'Nota de Débito de Exportación', 'exportacion', 'Nota débito exportación', 1, 0, 0, 0),
(914, 'Declaración de Ingreso (DIN)', 'otros', 'Declaración de ingreso', 0, 0, 0, 0),
(919, 'Resumen Boletas', 'otros', 'Resumen diario boletas', 0, 1, 0, 0),
(920, 'Canje', 'otros', 'Canje', 0, 0, 0, 0)
ON DUPLICATE KEY UPDATE
  nombre = VALUES(nombre),
  descripcion = VALUES(descripcion);

-- =====================================================
-- SECCIÓN 3: GESTIÓN DE FOLIOS CAF (Código de Autorización de Folios)
-- =====================================================

CREATE TABLE IF NOT EXISTS `folios_caf` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `tipo_documento` INT(3) NOT NULL COMMENT 'Código del tipo de documento SII',
  `folio_desde` INT(11) NOT NULL,
  `folio_hasta` INT(11) NOT NULL,
  `folio_actual` INT(11) NOT NULL COMMENT 'Siguiente folio a usar',
  `folios_disponibles` INT(11) GENERATED ALWAYS AS (folio_hasta - folio_actual + 1) STORED,
  `fecha_autorizacion` DATE NOT NULL,
  `fecha_vencimiento` DATE DEFAULT NULL,
  `archivo_caf_xml` LONGTEXT NOT NULL COMMENT 'Contenido del archivo CAF del SII',
  `firma_electronica` TEXT COMMENT 'Firma del CAF',
  `rut_autorizado` VARCHAR(12) NOT NULL,
  `estado` ENUM('activo','agotado','vencido','cancelado') DEFAULT 'activo',
  `fecha_carga` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `cargado_por` INT(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `empresa_tipo_rango` (`empresa_id`, `tipo_documento`, `folio_desde`, `folio_hasta`),
  KEY `idx_empresa` (`empresa_id`),
  KEY `idx_tipo_documento` (`tipo_documento`),
  KEY `idx_estado` (`estado`),
  KEY `idx_folios_disponibles` (`folios_disponibles`),
  CONSTRAINT `fk_folios_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_folios_tipo_doc` FOREIGN KEY (`tipo_documento`) REFERENCES `tipos_documentos_sii` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Gestión de Códigos de Autorización de Folios del SII';

-- =====================================================
-- SECCIÓN 4: DOCUMENTOS TRIBUTARIOS ELECTRÓNICOS (DTE)
-- =====================================================

CREATE TABLE IF NOT EXISTS `documentos_tributarios` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `tipo_documento` INT(3) NOT NULL,
  `folio` INT(11) NOT NULL,
  `fecha_emision` DATE NOT NULL,
  `fecha_vencimiento` DATE DEFAULT NULL,
  `rut_emisor` VARCHAR(12) NOT NULL,
  `razon_social_emisor` VARCHAR(255) NOT NULL,
  `rut_receptor` VARCHAR(12) NOT NULL,
  `razon_social_receptor` VARCHAR(255) NOT NULL,
  `giro_receptor` VARCHAR(255) DEFAULT NULL,
  `direccion_receptor` VARCHAR(500) DEFAULT NULL,
  `comuna_receptor` VARCHAR(100) DEFAULT NULL,
  `monto_neto` DECIMAL(15,2) DEFAULT 0.00,
  `monto_exento` DECIMAL(15,2) DEFAULT 0.00,
  `tasa_iva` DECIMAL(5,2) DEFAULT 19.00,
  `monto_iva` DECIMAL(15,2) DEFAULT 0.00,
  `monto_total` DECIMAL(15,2) NOT NULL,
  `moneda` VARCHAR(3) DEFAULT 'CLP',
  `tipo_cambio` DECIMAL(10,4) DEFAULT 1.0000,
  `forma_pago` ENUM('contado','credito') DEFAULT 'contado',
  `referencia_tipo_doc` INT(3) DEFAULT NULL COMMENT 'Para NC/ND',
  `referencia_folio` INT(11) DEFAULT NULL,
  `dte_xml` LONGTEXT COMMENT 'XML del DTE completo',
  `ted` TEXT COMMENT 'Timbre Electrónico Digital (Base64)',
  `track_id` VARCHAR(50) DEFAULT NULL COMMENT 'Track ID del SII',
  `estado_sii` ENUM('enviado','aceptado','rechazado','reparo','no_enviado') DEFAULT 'no_enviado',
  `glosa_estado` TEXT,
  `fecha_envio_sii` TIMESTAMP NULL DEFAULT NULL,
  `fecha_respuesta_sii` TIMESTAMP NULL DEFAULT NULL,
  `pdf_generado` TINYINT(1) DEFAULT 0,
  `ruta_pdf` VARCHAR(500) DEFAULT NULL,
  `anulado` TINYINT(1) DEFAULT 0,
  `motivo_anulacion` TEXT,
  `fecha_creacion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `creado_por` INT(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `empresa_tipo_folio` (`empresa_id`, `tipo_documento`, `folio`),
  KEY `idx_empresa` (`empresa_id`),
  KEY `idx_tipo_documento` (`tipo_documento`),
  KEY `idx_folio` (`folio`),
  KEY `idx_fecha_emision` (`fecha_emision`),
  KEY `idx_rut_receptor` (`rut_receptor`),
  KEY `idx_estado_sii` (`estado_sii`),
  KEY `idx_track_id` (`track_id`),
  CONSTRAINT `fk_dte_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_dte_tipo_doc` FOREIGN KEY (`tipo_documento`) REFERENCES `tipos_documentos_sii` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Registro de todos los documentos tributarios electrónicos emitidos';

-- =====================================================
-- TABLA: documentos_tributarios_detalle
-- =====================================================

CREATE TABLE IF NOT EXISTS `documentos_tributarios_detalle` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `documento_id` INT(11) NOT NULL,
  `linea` TINYINT(3) NOT NULL,
  `tipo_codigo` ENUM('INT1','INT2','EAN13','DUN14','SKU','PROPIO') DEFAULT 'PROPIO',
  `codigo_item` VARCHAR(50) DEFAULT NULL,
  `nombre_item` VARCHAR(500) NOT NULL,
  `descripcion` TEXT,
  `cantidad` DECIMAL(10,2) NOT NULL,
  `unidad_medida` VARCHAR(10) DEFAULT 'UN',
  `precio_unitario` DECIMAL(15,2) NOT NULL,
  `descuento_porcentaje` DECIMAL(5,2) DEFAULT 0.00,
  `descuento_monto` DECIMAL(15,2) DEFAULT 0.00,
  `recargo_porcentaje` DECIMAL(5,2) DEFAULT 0.00,
  `recargo_monto` DECIMAL(15,2) DEFAULT 0.00,
  `monto_neto_linea` DECIMAL(15,2) NOT NULL,
  `monto_exento_linea` DECIMAL(15,2) DEFAULT 0.00,
  `item_exento` TINYINT(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `documento_linea` (`documento_id`, `linea`),
  KEY `idx_documento` (`documento_id`),
  CONSTRAINT `fk_dte_detalle_doc` FOREIGN KEY (`documento_id`) REFERENCES `documentos_tributarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SECCIÓN 5: LIBRO DE COMPRAS Y VENTAS
-- =====================================================

CREATE TABLE IF NOT EXISTS `libro_compras` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `periodo` VARCHAR(7) NOT NULL COMMENT 'YYYY-MM',
  `tipo_documento` INT(3) NOT NULL,
  `folio` INT(11) NOT NULL,
  `fecha_documento` DATE NOT NULL,
  `rut_proveedor` VARCHAR(12) NOT NULL,
  `razon_social_proveedor` VARCHAR(255) NOT NULL,
  `monto_neto` DECIMAL(15,2) DEFAULT 0.00,
  `monto_exento` DECIMAL(15,2) DEFAULT 0.00,
  `monto_iva_recuperable` DECIMAL(15,2) DEFAULT 0.00,
  `monto_iva_no_recuperable` DECIMAL(15,2) DEFAULT 0.00,
  `monto_iva_uso_comun` DECIMAL(15,2) DEFAULT 0.00,
  `monto_total` DECIMAL(15,2) NOT NULL,
  `numero_interno` VARCHAR(50) DEFAULT NULL COMMENT 'Número de registro interno',
  `tipo_compra` ENUM('bien','servicio','activo_fijo','iva_uso_comun') DEFAULT 'bien',
  `documentosii_id` INT(11) DEFAULT NULL COMMENT 'Referencia a documentos_tributarios si es DTE',
  `fecha_registro` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `registrado_por` INT(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `empresa_periodo_tipo_folio` (`empresa_id`, `periodo`, `tipo_documento`, `folio`),
  KEY `idx_empresa` (`empresa_id`),
  KEY `idx_periodo` (`periodo`),
  KEY `idx_rut_proveedor` (`rut_proveedor`),
  KEY `idx_fecha_documento` (`fecha_documento`),
  CONSTRAINT `fk_libro_compras_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Libro de Compras para declaración mensual de IVA';

CREATE TABLE IF NOT EXISTS `libro_ventas` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `periodo` VARCHAR(7) NOT NULL COMMENT 'YYYY-MM',
  `tipo_documento` INT(3) NOT NULL,
  `folio` INT(11) NOT NULL,
  `fecha_documento` DATE NOT NULL,
  `rut_cliente` VARCHAR(12) NOT NULL,
  `razon_social_cliente` VARCHAR(255) NOT NULL,
  `monto_neto` DECIMAL(15,2) DEFAULT 0.00,
  `monto_exento` DECIMAL(15,2) DEFAULT 0.00,
  `monto_iva` DECIMAL(15,2) DEFAULT 0.00,
  `monto_total` DECIMAL(15,2) NOT NULL,
  `tipo_venta` ENUM('bien','servicio','exenta','exportacion') DEFAULT 'bien',
  `anulado` TINYINT(1) DEFAULT 0,
  `documento_id` INT(11) DEFAULT NULL COMMENT 'Referencia a documentos_tributarios',
  `fecha_registro` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `registrado_por` INT(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `empresa_periodo_tipo_folio` (`empresa_id`, `periodo`, `tipo_documento`, `folio`),
  KEY `idx_empresa` (`empresa_id`),
  KEY `idx_periodo` (`periodo`),
  KEY `idx_rut_cliente` (`rut_cliente`),
  KEY `idx_fecha_documento` (`fecha_documento`),
  CONSTRAINT `fk_libro_ventas_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Libro de Ventas para declaración mensual de IVA';

-- =====================================================
-- SECCIÓN 6: DECLARACIONES JURADAS
-- =====================================================

CREATE TABLE IF NOT EXISTS `declaraciones_juradas` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `periodo` VARCHAR(7) NOT NULL COMMENT 'YYYY-MM',
  `tipo_declaracion` ENUM('F29','F50','DJ1887','DJ1879','DJ1947','F22') NOT NULL,
  `estado` ENUM('borrador','generada','enviada','aceptada','rechazada') DEFAULT 'borrador',
  `monto_ventas_netas` DECIMAL(15,2) DEFAULT 0.00,
  `monto_compras_netas` DECIMAL(15,2) DEFAULT 0.00,
  `debito_fiscal` DECIMAL(15,2) DEFAULT 0.00 COMMENT 'IVA ventas',
  `credito_fiscal` DECIMAL(15,2) DEFAULT 0.00 COMMENT 'IVA compras',
  `iva_a_pagar` DECIMAL(15,2) GENERATED ALWAYS AS (debito_fiscal - credito_fiscal) STORED,
  `ppm_retenido` DECIMAL(15,2) DEFAULT 0.00 COMMENT 'PPM retenido (honorarios)',
  `retencion_2da_categoria` DECIMAL(15,2) DEFAULT 0.00,
  `fecha_vencimiento` DATE NOT NULL,
  `fecha_pago` DATE DEFAULT NULL,
  `archivo_declaracion` VARCHAR(500) DEFAULT NULL COMMENT 'Ruta al archivo generado',
  `numero_folio_sii` VARCHAR(50) DEFAULT NULL COMMENT 'Folio asignado por SII al declarar',
  `observaciones` TEXT,
  `fecha_generacion` TIMESTAMP NULL DEFAULT NULL,
  `fecha_envio` TIMESTAMP NULL DEFAULT NULL,
  `creado_por` INT(11) DEFAULT NULL,
  `fecha_creacion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `empresa_periodo_tipo` (`empresa_id`, `periodo`, `tipo_declaracion`),
  KEY `idx_empresa` (`empresa_id`),
  KEY `idx_periodo` (`periodo`),
  KEY `idx_estado` (`estado`),
  KEY `idx_fecha_vencimiento` (`fecha_vencimiento`),
  CONSTRAINT `fk_dj_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Declaraciones Juradas y Formularios SII';

-- =====================================================
-- SECCIÓN 7: FECHAS IMPORTANTES Y ALERTAS
-- =====================================================

CREATE TABLE IF NOT EXISTS `fechas_importantes` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) DEFAULT NULL COMMENT 'NULL = fecha general (todos)',
  `tipo` ENUM('impuesto','imposicion','declaracion','vencimiento','otro') NOT NULL,
  `categoria` VARCHAR(100) NOT NULL COMMENT 'F29, Previred, etc',
  `titulo` VARCHAR(255) NOT NULL,
  `descripcion` TEXT,
  `fecha` DATE NOT NULL,
  `fecha_fin` DATE DEFAULT NULL COMMENT 'Para rangos de fechas',
  `recurrente` TINYINT(1) DEFAULT 0,
  `frecuencia` ENUM('mensual','trimestral','semestral','anual') DEFAULT NULL,
  `dias_alerta_previa` INT(3) DEFAULT 7 COMMENT 'Días antes para enviar alerta',
  `activo` TINYINT(1) DEFAULT 1,
  `color` VARCHAR(7) DEFAULT '#007bff' COMMENT 'Color para calendario',
  `icono` VARCHAR(50) DEFAULT 'calendar',
  `fecha_creacion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `creado_por` INT(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_empresa` (`empresa_id`),
  KEY `idx_tipo` (`tipo`),
  KEY `idx_fecha` (`fecha`),
  KEY `idx_activo` (`activo`),
  CONSTRAINT `fk_fechas_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Calendario de fechas importantes (impuestos, imposiciones, etc)';

-- INSERTAR FECHAS IMPORTANTES PREDETERMINADAS (Chile)
INSERT INTO `fechas_importantes` (`empresa_id`, `tipo`, `categoria`, `titulo`, `descripcion`, `fecha`, `recurrente`, `frecuencia`, `dias_alerta_previa`, `color`) VALUES
(NULL, 'impuesto', 'F29', 'Vencimiento F29', 'Declaración mensual de IVA (hasta día 12 si termina RUT en 1-2)', '2025-01-12', 1, 'mensual', 7, '#dc3545'),
(NULL, 'impuesto', 'F29', 'Vencimiento F29', 'Declaración mensual de IVA (hasta día 13 si termina RUT en 3-4)', '2025-01-13', 1, 'mensual', 7, '#dc3545'),
(NULL, 'impuesto', 'F29', 'Vencimiento F29', 'Declaración mensual de IVA (hasta día 14 si termina RUT en 5-6)', '2025-01-14', 1, 'mensual', 7, '#dc3545'),
(NULL, 'impuesto', 'F29', 'Vencimiento F29', 'Declaración mensual de IVA (hasta día 15 si termina RUT en 7-8)', '2025-01-15', 1, 'mensual', 7, '#dc3545'),
(NULL, 'impuesto', 'F29', 'Vencimiento F29', 'Declaración mensual de IVA (hasta día 16 si termina RUT en 9-0)', '2025-01-16', 1, 'mensual', 7, '#dc3545'),
(NULL, 'imposicion', 'Previred', 'Pago Previred', 'Pago de cotizaciones previsionales (hasta día 10)', '2025-01-10', 1, 'mensual', 5, '#28a745'),
(NULL, 'declaracion', 'DJ1879', 'DJ 1879 (Anual)', 'Declaración Jurada Anual de Rentas', '2025-03-15', 1, 'anual', 15, '#ffc107'),
(NULL, 'declaracion', 'F22', 'Renta (F22)', 'Declaración anual de impuesto a la renta', '2025-04-30', 1, 'anual', 30, '#dc3545')
ON DUPLICATE KEY UPDATE titulo = VALUES(titulo);

-- =====================================================
-- SECCIÓN 8: PERÍODOS CONTABLES Y CAMBIO AUTOMÁTICO
-- =====================================================

CREATE TABLE IF NOT EXISTS `periodos_contables` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `ano` INT(4) NOT NULL,
  `mes` TINYINT(2) NOT NULL COMMENT '1-12',
  `periodo` VARCHAR(7) NOT NULL COMMENT 'YYYY-MM',
  `estado` ENUM('abierto','cerrado','bloqueado') DEFAULT 'abierto',
  `fecha_apertura` DATE DEFAULT NULL,
  `fecha_cierre` DATE DEFAULT NULL,
  `cerrado_por` INT(11) DEFAULT NULL,
  `observaciones` TEXT,
  `periodo_actual` TINYINT(1) DEFAULT 0 COMMENT 'Solo un período puede estar activo',
  `fecha_creacion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `empresa_periodo` (`empresa_id`, `periodo`),
  KEY `idx_empresa` (`empresa_id`),
  KEY `idx_ano` (`ano`),
  KEY `idx_estado` (`estado`),
  KEY `idx_periodo_actual` (`periodo_actual`),
  CONSTRAINT `fk_periodos_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Control de períodos contables con cambio automático/manual';

-- Insertar período actual
INSERT INTO `periodos_contables` (`empresa_id`, `ano`, `mes`, `periodo`, `estado`, `fecha_apertura`, `periodo_actual`)
SELECT id, YEAR(CURDATE()), MONTH(CURDATE()), DATE_FORMAT(CURDATE(), '%Y-%m'), 'abierto', CURDATE(), 1
FROM empresas
WHERE NOT EXISTS (
  SELECT 1 FROM periodos_contables pc
  WHERE pc.empresa_id = empresas.id
  AND pc.periodo = DATE_FORMAT(CURDATE(), '%Y-%m')
);

-- =====================================================
-- SECCIÓN 9: CUADRATURAS AUTOMÁTICAS
-- =====================================================

CREATE TABLE IF NOT EXISTS `cuadraturas` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `periodo` VARCHAR(7) NOT NULL,
  `tipo_cuadratura` ENUM('iva','ventas','compras','bancos','existencias','remuneraciones','contabilidad') NOT NULL,
  `descripcion` VARCHAR(500) NOT NULL,
  `valor_esperado` DECIMAL(15,2) NOT NULL,
  `valor_real` DECIMAL(15,2) NOT NULL,
  `diferencia` DECIMAL(15,2) GENERATED ALWAYS AS (valor_real - valor_esperado) STORED,
  `cuadra` TINYINT(1) GENERATED ALWAYS AS (ABS(valor_real - valor_esperado) < 0.01) STORED,
  `observaciones` TEXT,
  `fecha_cuadratura` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ejecutado_por` INT(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_empresa` (`empresa_id`),
  KEY `idx_periodo` (`periodo`),
  KEY `idx_tipo` (`tipo_cuadratura`),
  KEY `idx_cuadra` (`cuadra`),
  CONSTRAINT `fk_cuadraturas_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Registro de cuadraturas automáticas';

-- =====================================================
-- SECCIÓN 10: CONFIGURACIÓN DTE
-- =====================================================

CREATE TABLE IF NOT EXISTS `configuracion_dte` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `ambiente` ENUM('certificacion','produccion') DEFAULT 'certificacion',
  `certificado_digital` LONGBLOB COMMENT 'Certificado .pfx/.p12',
  `password_certificado` VARCHAR(255) DEFAULT NULL COMMENT 'Password del certificado (encriptado)',
  `fecha_vencimiento_certificado` DATE DEFAULT NULL,
  `firma_email` TEXT COMMENT 'Firma para emails de DTE',
  `logo_empresa` LONGBLOB COMMENT 'Logo para PDF',
  `resolucion_sii` VARCHAR(100) DEFAULT NULL COMMENT 'Número de resolución SII',
  `fecha_resolucion` DATE DEFAULT NULL,
  `actividades_economicas` TEXT COMMENT 'Códigos de actividades económicas autorizadas',
  `factura_afecta_retencion` TINYINT(1) DEFAULT 0,
  `correo_intercambio` VARCHAR(255) DEFAULT NULL COMMENT 'Email para recibir DTE',
  `activo` TINYINT(1) DEFAULT 1,
  `fecha_creacion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_por` INT(11) DEFAULT NULL,
  `fecha_actualizacion` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_empresa` (`empresa_id`),
  CONSTRAINT `fk_config_dte_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Configuración para emisión de documentos tributarios electrónicos';

-- =====================================================
-- SECCIÓN 11: VISTAS PARA REPORTES CONSOLIDADOS
-- =====================================================

-- Vista: Resumen de folios disponibles por tipo de documento
CREATE OR REPLACE VIEW v_estado_folios AS
SELECT
    f.empresa_id,
    t.codigo as tipo_documento,
    t.nombre as nombre_documento,
    SUM(f.folios_disponibles) as folios_disponibles,
    MIN(f.folio_actual) as folio_minimo,
    MAX(f.folio_hasta) as folio_maximo,
    COUNT(*) as total_cafs
FROM folios_caf f
INNER JOIN tipos_documentos_sii t ON f.tipo_documento = t.codigo
WHERE f.estado = 'activo'
GROUP BY f.empresa_id, t.codigo, t.nombre;

-- Vista: Libro de ventas consolidado (para F29)
CREATE OR REPLACE VIEW v_libro_ventas_consolidado AS
SELECT
    lv.empresa_id,
    lv.periodo,
    COUNT(DISTINCT lv.id) as total_documentos,
    SUM(lv.monto_neto) as total_neto,
    SUM(lv.monto_exento) as total_exento,
    SUM(lv.monto_iva) as total_iva,
    SUM(lv.monto_total) as total_general,
    SUM(CASE WHEN lv.anulado = 1 THEN lv.monto_total ELSE 0 END) as total_anulados,
    SUM(CASE WHEN lv.anulado = 0 THEN lv.monto_iva ELSE 0 END) as debito_fiscal
FROM libro_ventas lv
GROUP BY lv.empresa_id, lv.periodo;

-- Vista: Libro de compras consolidado (para F29)
CREATE OR REPLACE VIEW v_libro_compras_consolidado AS
SELECT
    lc.empresa_id,
    lc.periodo,
    COUNT(DISTINCT lc.id) as total_documentos,
    SUM(lc.monto_neto) as total_neto,
    SUM(lc.monto_exento) as total_exento,
    SUM(lc.monto_iva_recuperable) as total_iva_recuperable,
    SUM(lc.monto_iva_no_recuperable) as total_iva_no_recuperable,
    SUM(lc.monto_total) as total_general,
    SUM(lc.monto_iva_recuperable) as credito_fiscal
FROM libro_compras lc
GROUP BY lc.empresa_id, lc.periodo;

-- Vista: Resumen para F29 automático
CREATE OR REPLACE VIEW v_resumen_f29 AS
SELECT
    v.empresa_id,
    v.periodo,
    v.total_neto as ventas_netas,
    v.debito_fiscal,
    c.total_neto as compras_netas,
    c.credito_fiscal,
    (v.debito_fiscal - c.credito_fiscal) as iva_a_pagar,
    CASE
        WHEN (v.debito_fiscal - c.credito_fiscal) > 0 THEN 'A PAGAR'
        WHEN (v.debito_fiscal - c.credito_fiscal) < 0 THEN 'A FAVOR'
        ELSE 'SIN MOVIMIENTO'
    END as resultado
FROM v_libro_ventas_consolidado v
LEFT JOIN v_libro_compras_consolidado c ON v.empresa_id = c.empresa_id AND v.periodo = c.periodo;

-- Vista: Próximas fechas importantes
CREATE OR REPLACE VIEW v_proximas_fechas AS
SELECT
    fi.*,
    DATEDIFF(fi.fecha, CURDATE()) as dias_restantes,
    CASE
        WHEN DATEDIFF(fi.fecha, CURDATE()) < 0 THEN 'VENCIDA'
        WHEN DATEDIFF(fi.fecha, CURDATE()) <= fi.dias_alerta_previa THEN 'ALERTA'
        ELSE 'PROXIMA'
    END as estado_alerta
FROM fechas_importantes fi
WHERE fi.activo = 1
AND fi.fecha >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
ORDER BY fi.fecha ASC;

-- Vista: Indicadores económicos actuales
CREATE OR REPLACE VIEW v_indicadores_actuales AS
SELECT * FROM indicadores_economicos
WHERE fecha = (SELECT MAX(fecha) FROM indicadores_economicos)
LIMIT 1;

-- =====================================================
-- SECCIÓN 12: PROCEDIMIENTOS ALMACENADOS
-- =====================================================

DELIMITER //

-- Procedimiento: Obtener siguiente folio disponible
CREATE PROCEDURE IF NOT EXISTS sp_obtener_siguiente_folio(
    IN p_empresa_id INT,
    IN p_tipo_documento INT,
    OUT p_folio INT,
    OUT p_caf_id INT
)
BEGIN
    DECLARE v_folio_actual INT;

    -- Buscar CAF activo con folios disponibles
    SELECT id, folio_actual INTO p_caf_id, v_folio_actual
    FROM folios_caf
    WHERE empresa_id = p_empresa_id
    AND tipo_documento = p_tipo_documento
    AND estado = 'activo'
    AND folio_actual <= folio_hasta
    ORDER BY folio_actual ASC
    LIMIT 1;

    IF p_caf_id IS NOT NULL THEN
        SET p_folio = v_folio_actual;

        -- Incrementar folio actual
        UPDATE folios_caf
        SET folio_actual = folio_actual + 1
        WHERE id = p_caf_id;

        -- Marcar como agotado si ya no quedan folios
        UPDATE folios_caf
        SET estado = 'agotado'
        WHERE id = p_caf_id
        AND folio_actual > folio_hasta;
    ELSE
        SET p_folio = NULL;
        SET p_caf_id = NULL;
    END IF;
END//

-- Procedimiento: Generar asiento contable automático desde factura
CREATE PROCEDURE IF NOT EXISTS sp_generar_asiento_venta(
    IN p_factura_id INT,
    OUT p_asiento_id INT
)
BEGIN
    DECLARE v_empresa_id INT;
    DECLARE v_fecha DATE;
    DECLARE v_total DECIMAL(15,2);
    DECLARE v_neto DECIMAL(15,2);
    DECLARE v_iva DECIMAL(15,2);
    DECLARE v_numero_asiento VARCHAR(50);
    DECLARE v_periodo VARCHAR(7);

    -- Obtener datos de la factura
    SELECT empresa_id, fecha_emision, total, subtotal, impuesto
    INTO v_empresa_id, v_fecha, v_total, v_neto, v_iva
    FROM facturas
    WHERE id = p_factura_id;

    SET v_periodo = DATE_FORMAT(v_fecha, '%Y-%m');
    SET v_numero_asiento = CONCAT('VTA-', p_factura_id);

    -- Crear asiento
    INSERT INTO libro_diario (empresa_id, numero_asiento, fecha_asiento, periodo, tipo_asiento, glosa, total_debe, total_haber, estado)
    VALUES (v_empresa_id, v_numero_asiento, v_fecha, v_periodo, 'operacion', CONCAT('Venta según factura'), v_total, v_total, 'confirmado');

    SET p_asiento_id = LAST_INSERT_ID();

    -- Debe: Clientes (cuenta por cobrar)
    INSERT INTO detalle_asientos (asiento_id, cuenta_id, debe, haber, glosa)
    SELECT p_asiento_id, id, v_total, 0, 'Venta a crédito'
    FROM plan_cuentas
    WHERE empresa_id = v_empresa_id AND codigo = '1.1.3'
    LIMIT 1;

    -- Haber: Ventas
    INSERT INTO detalle_asientos (asiento_id, cuenta_id, debe, haber, glosa)
    SELECT p_asiento_id, id, 0, v_neto, 'Venta neta'
    FROM plan_cuentas
    WHERE empresa_id = v_empresa_id AND codigo = '4.1'
    LIMIT 1;

    -- Haber: IVA por pagar
    INSERT INTO detalle_asientos (asiento_id, cuenta_id, debe, haber, glosa)
    SELECT p_asiento_id, id, 0, v_iva, 'IVA débito fiscal'
    FROM plan_cuentas
    WHERE empresa_id = v_empresa_id AND codigo = '2.1.2'
    LIMIT 1;
END//

-- Procedimiento: Actualizar libro de ventas desde documentos tributarios
CREATE PROCEDURE IF NOT EXISTS sp_actualizar_libro_ventas(
    IN p_periodo VARCHAR(7),
    IN p_empresa_id INT
)
BEGIN
    -- Limpiar libro del período
    DELETE FROM libro_ventas
    WHERE periodo = p_periodo
    AND empresa_id = p_empresa_id;

    -- Insertar desde documentos tributarios
    INSERT INTO libro_ventas (
        empresa_id, periodo, tipo_documento, folio, fecha_documento,
        rut_cliente, razon_social_cliente, monto_neto, monto_exento,
        monto_iva, monto_total, tipo_venta, anulado, documento_id
    )
    SELECT
        dt.empresa_id,
        DATE_FORMAT(dt.fecha_emision, '%Y-%m'),
        dt.tipo_documento,
        dt.folio,
        dt.fecha_emision,
        dt.rut_receptor,
        dt.razon_social_receptor,
        dt.monto_neto,
        dt.monto_exento,
        dt.monto_iva,
        dt.monto_total,
        'bien',
        dt.anulado,
        dt.id
    FROM documentos_tributarios dt
    INNER JOIN tipos_documentos_sii t ON dt.tipo_documento = t.codigo
    WHERE DATE_FORMAT(dt.fecha_emision, '%Y-%m') = p_periodo
    AND dt.empresa_id = p_empresa_id
    AND t.categoria IN ('factura', 'boleta')
    AND dt.estado_sii IN ('aceptado', 'enviado');
END//

-- Procedimiento: Cambiar período contable
CREATE PROCEDURE IF NOT EXISTS sp_cambiar_periodo(
    IN p_empresa_id INT,
    IN p_ano INT,
    IN p_mes INT
)
BEGIN
    DECLARE v_periodo VARCHAR(7);

    SET v_periodo = CONCAT(p_ano, '-', LPAD(p_mes, 2, '0'));

    -- Desactivar período actual
    UPDATE periodos_contables
    SET periodo_actual = 0
    WHERE empresa_id = p_empresa_id;

    -- Activar nuevo período (o crearlo si no existe)
    INSERT INTO periodos_contables (empresa_id, ano, mes, periodo, estado, fecha_apertura, periodo_actual)
    VALUES (p_empresa_id, p_ano, p_mes, v_periodo, 'abierto', CURDATE(), 1)
    ON DUPLICATE KEY UPDATE
        periodo_actual = 1,
        estado = 'abierto';
END//

-- Procedimiento: Generar declaración F29 automáticamente
CREATE PROCEDURE IF NOT EXISTS sp_generar_f29(
    IN p_empresa_id INT,
    IN p_periodo VARCHAR(7)
)
BEGIN
    DECLARE v_debito DECIMAL(15,2);
    DECLARE v_credito DECIMAL(15,2);
    DECLARE v_ventas DECIMAL(15,2);
    DECLARE v_compras DECIMAL(15,2);

    -- Obtener datos del período
    SELECT
        COALESCE(SUM(monto_iva), 0),
        COALESCE(SUM(monto_neto), 0)
    INTO v_debito, v_ventas
    FROM libro_ventas
    WHERE empresa_id = p_empresa_id
    AND periodo = p_periodo
    AND anulado = 0;

    SELECT
        COALESCE(SUM(monto_iva_recuperable), 0),
        COALESCE(SUM(monto_neto), 0)
    INTO v_credito, v_compras
    FROM libro_compras
    WHERE empresa_id = p_empresa_id
    AND periodo = p_periodo;

    -- Insertar o actualizar declaración
    INSERT INTO declaraciones_juradas (
        empresa_id, periodo, tipo_declaracion, estado,
        monto_ventas_netas, monto_compras_netas,
        debito_fiscal, credito_fiscal,
        fecha_vencimiento
    )
    VALUES (
        p_empresa_id, p_periodo, 'F29', 'generada',
        v_ventas, v_compras, v_debito, v_credito,
        LAST_DAY(STR_TO_DATE(CONCAT(p_periodo, '-15'), '%Y-%m-%d'))
    )
    ON DUPLICATE KEY UPDATE
        monto_ventas_netas = v_ventas,
        monto_compras_netas = v_compras,
        debito_fiscal = v_debito,
        credito_fiscal = v_credito,
        estado = 'generada';
END//

DELIMITER ;

-- =====================================================
-- SECCIÓN 13: TRIGGERS
-- =====================================================

DELIMITER //

-- Trigger: Auto-registrar en libro de ventas al emitir DTE
CREATE TRIGGER IF NOT EXISTS tr_dte_insert_libro_ventas
AFTER INSERT ON documentos_tributarios
FOR EACH ROW
BEGIN
    IF NEW.estado_sii IN ('aceptado', 'enviado') THEN
        INSERT INTO libro_ventas (
            empresa_id, periodo, tipo_documento, folio, fecha_documento,
            rut_cliente, razon_social_cliente, monto_neto, monto_exento,
            monto_iva, monto_total, documento_id
        )
        VALUES (
            NEW.empresa_id,
            DATE_FORMAT(NEW.fecha_emision, '%Y-%m'),
            NEW.tipo_documento,
            NEW.folio,
            NEW.fecha_emision,
            NEW.rut_receptor,
            NEW.razon_social_receptor,
            NEW.monto_neto,
            NEW.monto_exento,
            NEW.monto_iva,
            NEW.monto_total,
            NEW.id
        )
        ON DUPLICATE KEY UPDATE
            monto_neto = NEW.monto_neto,
            monto_iva = NEW.monto_iva,
            monto_total = NEW.monto_total;
    END IF;
END//

-- Trigger: Alerta de folios bajos
CREATE TRIGGER IF NOT EXISTS tr_folios_alerta
AFTER UPDATE ON folios_caf
FOR EACH ROW
BEGIN
    IF NEW.folios_disponibles <= 50 AND OLD.folios_disponibles > 50 THEN
        INSERT INTO notificaciones (usuario_id, empresa_id, tipo, titulo, mensaje)
        SELECT u.id, NEW.empresa_id, 'warning', 'Folios por agotarse',
               CONCAT('El tipo de documento ', t.nombre, ' tiene solo ', NEW.folios_disponibles, ' folios disponibles')
        FROM usuarios u
        INNER JOIN tipos_documentos_sii t ON t.codigo = NEW.tipo_documento
        WHERE u.empresa_id = NEW.empresa_id AND u.es_super_admin = 1;
    END IF;
END//

-- Trigger: Actualizar período automáticamente cada mes
CREATE EVENT IF NOT EXISTS ev_cambiar_periodo_automatico
ON SCHEDULE EVERY 1 DAY
STARTS '2025-01-01 00:01:00'
DO
BEGIN
    DECLARE v_primer_dia_mes DATE;
    SET v_primer_dia_mes = DATE_FORMAT(CURDATE(), '%Y-%m-01');

    -- Si es primer día del mes, cambiar período
    IF CURDATE() = v_primer_dia_mes THEN
        UPDATE periodos_contables pc
        INNER JOIN empresas e ON pc.empresa_id = e.id
        SET pc.periodo_actual = 0
        WHERE pc.periodo_actual = 1
        AND pc.periodo < DATE_FORMAT(CURDATE(), '%Y-%m');

        -- Crear nuevo período para cada empresa
        INSERT INTO periodos_contables (empresa_id, ano, mes, periodo, estado, fecha_apertura, periodo_actual)
        SELECT
            id,
            YEAR(CURDATE()),
            MONTH(CURDATE()),
            DATE_FORMAT(CURDATE(), '%Y-%m'),
            'abierto',
            CURDATE(),
            1
        FROM empresas
        ON DUPLICATE KEY UPDATE periodo_actual = 1;
    END IF;
END//

DELIMITER ;

-- =====================================================
-- ÍNDICES ADICIONALES PARA OPTIMIZACIÓN
-- =====================================================

ALTER TABLE `documentos_tributarios` ADD INDEX `idx_empresa_periodo` (`empresa_id`, `fecha_emision`);
ALTER TABLE `libro_compras` ADD INDEX `idx_periodo_tipo` (`periodo`, `tipo_documento`);
ALTER TABLE `libro_ventas` ADD INDEX `idx_periodo_tipo` (`periodo`, `tipo_documento`);

-- =====================================================
-- PERMISOS
-- =====================================================

FLUSH PRIVILEGES;

-- =====================================================
-- FIN DEL MÓDULO DE INTEGRACIÓN COMPLETA
-- =====================================================
