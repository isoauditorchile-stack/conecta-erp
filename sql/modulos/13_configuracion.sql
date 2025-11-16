-- =====================================================
-- MÓDULO 13: CONFIGURACIÓN AVANZADA DEL SISTEMA
-- Sistema de configuración empresarial nivel SAP
-- =====================================================

USE conectae_conectaerpbd;

-- =====================================================
-- TABLA: configuracion_sistema
-- Parámetros globales configurables del sistema
-- =====================================================
CREATE TABLE IF NOT EXISTS `configuracion_sistema` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `categoria` ENUM('general','facturacion','contabilidad','inventario','rrhh','notificaciones','seguridad','integraciones') DEFAULT 'general',
  `clave` VARCHAR(100) NOT NULL COMMENT 'Identificador único del parámetro',
  `valor` TEXT NOT NULL COMMENT 'Valor del parámetro (puede ser JSON)',
  `tipo_dato` ENUM('string','integer','decimal','boolean','json','date') DEFAULT 'string',
  `descripcion` VARCHAR(500) DEFAULT NULL,
  `es_sistema` TINYINT(1) DEFAULT 0 COMMENT 'No editable por usuarios',
  `fecha_creacion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_modificacion` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `modificado_por` INT(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `empresa_clave` (`empresa_id`, `clave`),
  KEY `idx_empresa` (`empresa_id`),
  KEY `idx_categoria` (`categoria`),
  KEY `idx_clave` (`clave`),
  CONSTRAINT `fk_config_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_config_modificado` FOREIGN KEY (`modificado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: configuracion_facturacion
-- Configuración específica de facturación electrónica
-- =====================================================
CREATE TABLE IF NOT EXISTS `configuracion_facturacion` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `tipo_dte` ENUM('factura_electronica','boleta_electronica','factura_exenta','nota_credito','nota_debito','guia_despacho') DEFAULT 'factura_electronica',
  `folio_inicio` INT(11) NOT NULL DEFAULT 1,
  `folio_fin` INT(11) NOT NULL DEFAULT 1000,
  `folio_actual` INT(11) NOT NULL DEFAULT 1,
  `caf_xml` LONGTEXT DEFAULT NULL COMMENT 'Archivo CAF (Código de Autorización de Folios)',
  `fecha_caf_emision` DATE DEFAULT NULL,
  `fecha_caf_vencimiento` DATE DEFAULT NULL,
  `certificado_digital` LONGTEXT DEFAULT NULL COMMENT 'Certificado digital para firma',
  `clave_certificado` VARCHAR(255) DEFAULT NULL COMMENT 'Password certificado (encriptado)',
  `ambiente` ENUM('certificacion','produccion') DEFAULT 'certificacion',
  `activo` TINYINT(1) DEFAULT 1,
  `fecha_creacion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_modificacion` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `empresa_tipo_dte` (`empresa_id`, `tipo_dte`),
  KEY `idx_empresa` (`empresa_id`),
  KEY `idx_activo` (`activo`),
  CONSTRAINT `fk_config_fac_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: configuracion_impuestos
-- Configuración de impuestos y tasas por país
-- =====================================================
CREATE TABLE IF NOT EXISTS `configuracion_impuestos` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `nombre` VARCHAR(100) NOT NULL COMMENT 'IVA, IEPS, ISR, etc.',
  `codigo` VARCHAR(20) NOT NULL COMMENT 'Código del impuesto',
  `tipo` ENUM('iva','retencion','percepcion','especial','municipal') DEFAULT 'iva',
  `porcentaje` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `base_imponible` ENUM('neto','bruto','subtotal') DEFAULT 'neto',
  `aplica_ventas` TINYINT(1) DEFAULT 1,
  `aplica_compras` TINYINT(1) DEFAULT 1,
  `cuenta_contable` VARCHAR(50) DEFAULT NULL COMMENT 'Cuenta contable asociada',
  `pais_codigo` VARCHAR(2) DEFAULT 'CL' COMMENT 'ISO 3166-1 alpha-2',
  `activo` TINYINT(1) DEFAULT 1,
  `fecha_vigencia_inicio` DATE NOT NULL,
  `fecha_vigencia_fin` DATE DEFAULT NULL,
  `fecha_creacion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `empresa_codigo` (`empresa_id`, `codigo`),
  KEY `idx_empresa` (`empresa_id`),
  KEY `idx_activo` (`activo`),
  KEY `idx_pais` (`pais_codigo`),
  CONSTRAINT `fk_config_imp_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: plantillas_documentos
-- Plantillas HTML/PDF para documentos (facturas, OC, etc.)
-- =====================================================
CREATE TABLE IF NOT EXISTS `plantillas_documentos` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `nombre` VARCHAR(100) NOT NULL,
  `tipo_documento` ENUM('factura','boleta','orden_compra','orden_venta','cotizacion','guia_despacho','nota_credito','recibo') NOT NULL,
  `contenido_html` LONGTEXT NOT NULL COMMENT 'Template HTML con placeholders',
  `contenido_css` TEXT DEFAULT NULL,
  `variables_disponibles` TEXT DEFAULT NULL COMMENT 'JSON con variables disponibles',
  `orientacion` ENUM('portrait','landscape') DEFAULT 'portrait',
  `tamano_papel` VARCHAR(10) DEFAULT 'letter' COMMENT 'letter, legal, A4, A5',
  `margenes` VARCHAR(50) DEFAULT '10,10,10,10' COMMENT 'top,right,bottom,left en mm',
  `encabezado_html` TEXT DEFAULT NULL,
  `pie_html` TEXT DEFAULT NULL,
  `logo_url` VARCHAR(255) DEFAULT NULL,
  `es_predeterminada` TINYINT(1) DEFAULT 0,
  `activa` TINYINT(1) DEFAULT 1,
  `fecha_creacion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `creado_por` INT(11) DEFAULT NULL,
  `fecha_modificacion` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `modificado_por` INT(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_empresa` (`empresa_id`),
  KEY `idx_tipo` (`tipo_documento`),
  KEY `idx_predeterminada` (`es_predeterminada`),
  CONSTRAINT `fk_plantillas_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_plantillas_creado` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: configuracion_notificaciones
-- Configuración de notificaciones por evento
-- =====================================================
CREATE TABLE IF NOT EXISTS `configuracion_notificaciones` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `evento` VARCHAR(100) NOT NULL COMMENT 'factura_creada, pago_recibido, etc.',
  `canal` ENUM('email','sms','push','interno','whatsapp') NOT NULL,
  `destinatarios` TEXT NOT NULL COMMENT 'JSON con roles/usuarios/emails',
  `asunto_template` VARCHAR(255) DEFAULT NULL,
  `mensaje_template` TEXT DEFAULT NULL COMMENT 'Template con placeholders',
  `condiciones` TEXT DEFAULT NULL COMMENT 'JSON con condiciones de disparo',
  `activa` TINYINT(1) DEFAULT 1,
  `prioridad` TINYINT(1) DEFAULT 3 COMMENT '1=crítica, 5=baja',
  `fecha_creacion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_modificacion` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_empresa` (`empresa_id`),
  KEY `idx_evento` (`evento`),
  KEY `idx_activa` (`activa`),
  CONSTRAINT `fk_config_notif_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: configuracion_integraciones
-- Credenciales y configuración de integraciones externas
-- =====================================================
CREATE TABLE IF NOT EXISTS `configuracion_integraciones` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `servicio` ENUM('sii','previred','transbank','webpay','mercadopago','flow','paypal','stripe','sendgrid','twilio','whatsapp_business') NOT NULL,
  `nombre_display` VARCHAR(100) NOT NULL,
  `credenciales` TEXT NOT NULL COMMENT 'JSON con API keys, tokens, etc (ENCRIPTADO)',
  `configuracion` TEXT DEFAULT NULL COMMENT 'JSON con parámetros adicionales',
  `ambiente` ENUM('sandbox','produccion') DEFAULT 'sandbox',
  `activa` TINYINT(1) DEFAULT 0,
  `ultima_sincronizacion` TIMESTAMP NULL DEFAULT NULL,
  `estado_conexion` ENUM('ok','error','pendiente','desconectado') DEFAULT 'desconectado',
  `mensaje_estado` VARCHAR(500) DEFAULT NULL,
  `fecha_creacion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_modificacion` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `creado_por` INT(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `empresa_servicio` (`empresa_id`, `servicio`),
  KEY `idx_empresa` (`empresa_id`),
  KEY `idx_activa` (`activa`),
  CONSTRAINT `fk_config_int_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_config_int_creado` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: secuencias_numeracion
-- Control de secuencias de numeración automática
-- =====================================================
CREATE TABLE IF NOT EXISTS `secuencias_numeracion` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `tipo_documento` VARCHAR(50) NOT NULL COMMENT 'factura, orden_compra, empleado, etc.',
  `prefijo` VARCHAR(10) DEFAULT '' COMMENT 'FAC, OC, EMP, etc.',
  `sufijo` VARCHAR(10) DEFAULT '',
  `longitud_numero` TINYINT(2) DEFAULT 6 COMMENT 'Cantidad de dígitos',
  `valor_actual` INT(11) NOT NULL DEFAULT 0,
  `valor_inicial` INT(11) DEFAULT 1,
  `incremento` INT(11) DEFAULT 1,
  `separador` VARCHAR(5) DEFAULT '' COMMENT 'Separador entre prefijo y número',
  `ejemplo` VARCHAR(50) GENERATED ALWAYS AS (
    CONCAT(prefijo, separador, LPAD(valor_actual, longitud_numero, '0'), sufijo)
  ) STORED,
  `activa` TINYINT(1) DEFAULT 1,
  `fecha_creacion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_modificacion` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `empresa_tipo` (`empresa_id`, `tipo_documento`),
  KEY `idx_empresa` (`empresa_id`),
  CONSTRAINT `fk_secuencias_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: logs_integraciones
-- Registro de llamadas a servicios externos
-- =====================================================
CREATE TABLE IF NOT EXISTS `logs_integraciones` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `integracion_id` INT(11) NOT NULL,
  `servicio` VARCHAR(100) NOT NULL,
  `operacion` VARCHAR(100) NOT NULL COMMENT 'enviar_dte, consultar_estado, etc.',
  `request_data` LONGTEXT DEFAULT NULL COMMENT 'JSON con datos enviados',
  `response_data` LONGTEXT DEFAULT NULL COMMENT 'JSON con respuesta',
  `http_status` INT(3) DEFAULT NULL,
  `tiempo_respuesta_ms` INT(6) DEFAULT NULL,
  `estado` ENUM('exitoso','error','timeout','rechazado') NOT NULL,
  `mensaje_error` TEXT DEFAULT NULL,
  `ip_origen` VARCHAR(45) DEFAULT NULL,
  `usuario_id` INT(11) DEFAULT NULL,
  `fecha_creacion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_empresa` (`empresa_id`),
  KEY `idx_integracion` (`integracion_id`),
  KEY `idx_servicio` (`servicio`),
  KEY `idx_estado` (`estado`),
  KEY `idx_fecha` (`fecha_creacion`),
  CONSTRAINT `fk_logs_int_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_logs_integracion` FOREIGN KEY (`integracion_id`) REFERENCES `configuracion_integraciones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- ÍNDICES ADICIONALES PARA OPTIMIZACIÓN
-- =====================================================

ALTER TABLE `configuracion_sistema` ADD INDEX `idx_empresa_categoria` (`empresa_id`, `categoria`);
ALTER TABLE `configuracion_facturacion` ADD INDEX `idx_empresa_activo` (`empresa_id`, `activo`);
ALTER TABLE `logs_integraciones` ADD INDEX `idx_empresa_fecha` (`empresa_id`, `fecha_creacion`);

-- =====================================================
-- TRIGGERS
-- =====================================================

DELIMITER //

-- Trigger: Validar folio actual dentro de rango
CREATE TRIGGER IF NOT EXISTS `tr_config_fac_folio_check` BEFORE UPDATE ON `configuracion_facturacion`
FOR EACH ROW
BEGIN
    IF NEW.folio_actual < NEW.folio_inicio OR NEW.folio_actual > NEW.folio_fin THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Folio actual fuera de rango autorizado';
    END IF;
END//

-- Trigger: Incrementar automáticamente secuencia
CREATE TRIGGER IF NOT EXISTS `tr_secuencias_increment` BEFORE UPDATE ON `secuencias_numeracion`
FOR EACH ROW
BEGIN
    IF NEW.valor_actual <> OLD.valor_actual THEN
        SET NEW.fecha_modificacion = NOW();
    END IF;
END//

DELIMITER ;

-- =====================================================
-- VISTAS
-- =====================================================

-- Vista: Resumen de configuración por empresa
CREATE OR REPLACE VIEW v_configuracion_resumen AS
SELECT
    e.id as empresa_id,
    e.nombre as empresa_nombre,
    (SELECT COUNT(*) FROM configuracion_sistema WHERE empresa_id = e.id) as total_parametros,
    (SELECT COUNT(*) FROM configuracion_facturacion WHERE empresa_id = e.id AND activo = 1) as dte_activos,
    (SELECT COUNT(*) FROM configuracion_impuestos WHERE empresa_id = e.id AND activo = 1) as impuestos_activos,
    (SELECT COUNT(*) FROM plantillas_documentos WHERE empresa_id = e.id AND activa = 1) as plantillas_activas,
    (SELECT COUNT(*) FROM configuracion_integraciones WHERE empresa_id = e.id AND activa = 1) as integraciones_activas,
    (SELECT COUNT(*) FROM configuracion_notificaciones WHERE empresa_id = e.id AND activa = 1) as notificaciones_activas
FROM empresas e;

-- Vista: Estado de integraciones
CREATE OR REPLACE VIEW v_estado_integraciones AS
SELECT
    ci.empresa_id,
    ci.servicio,
    ci.nombre_display,
    ci.ambiente,
    ci.activa,
    ci.estado_conexion,
    ci.ultima_sincronizacion,
    TIMESTAMPDIFF(HOUR, ci.ultima_sincronizacion, NOW()) as horas_sin_sync,
    (SELECT COUNT(*) FROM logs_integraciones WHERE integracion_id = ci.id AND estado = 'exitoso' AND DATE(fecha_creacion) = CURDATE()) as llamadas_exitosas_hoy,
    (SELECT COUNT(*) FROM logs_integraciones WHERE integracion_id = ci.id AND estado = 'error' AND DATE(fecha_creacion) = CURDATE()) as llamadas_error_hoy
FROM configuracion_integraciones ci;

-- Vista: Folios disponibles
CREATE OR REPLACE VIEW v_folios_disponibles AS
SELECT
    empresa_id,
    tipo_dte,
    folio_inicio,
    folio_fin,
    folio_actual,
    (folio_fin - folio_actual) as folios_disponibles,
    ROUND((folio_actual - folio_inicio) / (folio_fin - folio_inicio) * 100, 2) as porcentaje_uso,
    fecha_caf_vencimiento,
    DATEDIFF(fecha_caf_vencimiento, CURDATE()) as dias_vigencia,
    CASE
        WHEN fecha_caf_vencimiento < CURDATE() THEN 'vencido'
        WHEN DATEDIFF(fecha_caf_vencimiento, CURDATE()) <= 30 THEN 'por_vencer'
        WHEN (folio_fin - folio_actual) < 100 THEN 'folios_bajos'
        ELSE 'ok'
    END as estado_alerta
FROM configuracion_facturacion
WHERE activo = 1;

-- =====================================================
-- DATOS INICIALES
-- Parámetros de configuración por defecto
-- =====================================================

-- Función para insertar configuración inicial
DELIMITER //

CREATE PROCEDURE IF NOT EXISTS sp_crear_configuracion_inicial(IN p_empresa_id INT)
BEGIN
    -- Parámetros generales
    INSERT IGNORE INTO configuracion_sistema (empresa_id, categoria, clave, valor, tipo_dato, descripcion, es_sistema) VALUES
    (p_empresa_id, 'general', 'zona_horaria', 'America/Santiago', 'string', 'Zona horaria de la empresa', 0),
    (p_empresa_id, 'general', 'moneda_principal', 'CLP', 'string', 'Moneda principal de operación', 0),
    (p_empresa_id, 'general', 'idioma_principal', 'es', 'string', 'Idioma principal del sistema', 0),
    (p_empresa_id, 'general', 'formato_fecha', 'd/m/Y', 'string', 'Formato de visualización de fechas', 0),
    (p_empresa_id, 'facturacion', 'numeracion_automatica', 'true', 'boolean', 'Activar numeración automática', 0),
    (p_empresa_id, 'facturacion', 'validar_stock', 'true', 'boolean', 'Validar stock al facturar', 0),
    (p_empresa_id, 'facturacion', 'dias_vencimiento_defecto', '30', 'integer', 'Días de vencimiento por defecto', 0),
    (p_empresa_id, 'contabilidad', 'periodo_fiscal_inicio', '01-01', 'string', 'Inicio del período fiscal (MM-DD)', 0),
    (p_empresa_id, 'contabilidad', 'periodo_fiscal_fin', '12-31', 'string', 'Fin del período fiscal (MM-DD)', 0),
    (p_empresa_id, 'inventario', 'metodo_valoracion', 'FIFO', 'string', 'Método de valoración de inventario', 0),
    (p_empresa_id, 'inventario', 'permitir_stock_negativo', 'false', 'boolean', 'Permitir ventas con stock negativo', 0),
    (p_empresa_id, 'rrhh', 'moneda_nomina', 'CLP', 'string', 'Moneda para nómina', 0),
    (p_empresa_id, 'notificaciones', 'email_remitente', '[email protected]', 'string', 'Email remitente por defecto', 0),
    (p_empresa_id, 'seguridad', 'sesion_timeout_minutos', '120', 'integer', 'Timeout de sesión en minutos', 1),
    (p_empresa_id, 'seguridad', 'password_min_length', '8', 'integer', 'Longitud mínima de contraseña', 1);

    -- Impuestos Chile (IVA 19%)
    INSERT IGNORE INTO configuracion_impuestos (empresa_id, nombre, codigo, tipo, porcentaje, base_imponible, aplica_ventas, aplica_compras, pais_codigo, fecha_vigencia_inicio) VALUES
    (p_empresa_id, 'IVA', 'IVA19', 'iva', 19.00, 'neto', 1, 1, 'CL', '2000-01-01');

    -- Secuencias de numeración
    INSERT IGNORE INTO secuencias_numeracion (empresa_id, tipo_documento, prefijo, longitud_numero, valor_actual, valor_inicial) VALUES
    (p_empresa_id, 'factura', 'FAC', 6, 1, 1),
    (p_empresa_id, 'orden_compra', 'OC', 6, 1, 1),
    (p_empresa_id, 'orden_venta', 'OV', 6, 1, 1),
    (p_empresa_id, 'cotizacion', 'COT', 6, 1, 1),
    (p_empresa_id, 'empleado', 'EMP', 5, 1, 1),
    (p_empresa_id, 'producto', 'PRO', 6, 1, 1);
END//

DELIMITER ;

-- =====================================================
-- PERMISOS
-- =====================================================

FLUSH PRIVILEGES;
