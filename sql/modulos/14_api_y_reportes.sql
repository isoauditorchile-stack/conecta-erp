-- =====================================================
-- TABLAS PARA API REST Y REPORTES
-- Sistema de API Keys, Logging y Reportes Mensuales
-- =====================================================

USE conectae_conectaerpbd;

-- =====================================================
-- TABLA: api_keys
-- Gestión de claves de API para autenticación externa
-- =====================================================
CREATE TABLE IF NOT EXISTS `api_keys` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `nombre` VARCHAR(100) NOT NULL COMMENT 'Nombre descriptivo de la API key',
  `api_key` VARCHAR(64) NOT NULL COMMENT 'Clave de API (hash SHA256)',
  `api_secret` VARCHAR(128) DEFAULT NULL COMMENT 'Secret adicional (encriptado)',
  `permisos` TEXT DEFAULT NULL COMMENT 'JSON con permisos específicos',
  `ip_permitidas` TEXT DEFAULT NULL COMMENT 'Lista de IPs permitidas (opcional)',
  `rate_limit_por_hora` INT(6) DEFAULT 1000 COMMENT 'Límite de requests por hora',
  `activa` TINYINT(1) DEFAULT 1,
  `fecha_creacion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_expiracion` TIMESTAMP NULL DEFAULT NULL,
  `fecha_ultimo_uso` TIMESTAMP NULL DEFAULT NULL,
  `total_requests` INT(11) DEFAULT 0 COMMENT 'Total de requests realizados',
  `creado_por` INT(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `api_key_unique` (`api_key`),
  KEY `idx_empresa` (`empresa_id`),
  KEY `idx_activa` (`activa`),
  KEY `idx_expiracion` (`fecha_expiracion`),
  CONSTRAINT `fk_api_keys_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_api_keys_creado` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: api_logs
-- Log completo de todas las peticiones a la API
-- =====================================================
CREATE TABLE IF NOT EXISTS `api_logs` (
  `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
  `api_key` VARCHAR(64) NOT NULL,
  `endpoint` VARCHAR(255) NOT NULL COMMENT 'URL del endpoint solicitado',
  `metodo` ENUM('GET','POST','PUT','DELETE','PATCH','OPTIONS') NOT NULL,
  `ip_cliente` VARCHAR(45) NOT NULL COMMENT 'IP del cliente',
  `user_agent` VARCHAR(500) DEFAULT NULL,
  `parametros_get` TEXT DEFAULT NULL COMMENT 'JSON con parámetros GET',
  `parametros_post` TEXT DEFAULT NULL COMMENT 'JSON con parámetros POST/PUT',
  `codigo_respuesta` INT(3) DEFAULT NULL COMMENT 'Código HTTP de respuesta',
  `tiempo_respuesta_ms` INT(6) DEFAULT NULL COMMENT 'Tiempo de respuesta en milisegundos',
  `mensaje_error` TEXT DEFAULT NULL,
  `fecha_creacion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_api_key` (`api_key`),
  KEY `idx_endpoint` (`endpoint`),
  KEY `idx_metodo` (`metodo`),
  KEY `idx_fecha` (`fecha_creacion`),
  KEY `idx_codigo` (`codigo_respuesta`),
  CONSTRAINT `fk_api_logs_key` FOREIGN KEY (`api_key`) REFERENCES `api_keys` (`api_key`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: reportes_mensuales
-- Reportes generados automáticamente por CRON
-- =====================================================
CREATE TABLE IF NOT EXISTS `reportes_mensuales` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `periodo` VARCHAR(7) NOT NULL COMMENT 'YYYY-MM',

  -- Métricas de Ventas
  `total_facturas` INT(11) DEFAULT 0,
  `total_ventas` DECIMAL(15,2) DEFAULT 0.00,
  `total_cobrado` DECIMAL(15,2) DEFAULT 0.00,
  `total_pendiente` DECIMAL(15,2) DEFAULT 0.00,
  `clientes_unicos` INT(11) DEFAULT 0,
  `ticket_promedio` DECIMAL(15,2) DEFAULT 0.00,

  -- Métricas de Compras
  `total_compras` DECIMAL(15,2) DEFAULT 0.00,
  `proveedores_unicos` INT(11) DEFAULT 0,

  -- Métricas de Inventario
  `total_productos` INT(11) DEFAULT 0,
  `productos_stock_bajo` INT(11) DEFAULT 0,
  `valor_inventario` DECIMAL(15,2) DEFAULT 0.00,

  -- Métricas Financieras
  `ingresos` DECIMAL(15,2) DEFAULT 0.00,
  `egresos` DECIMAL(15,2) DEFAULT 0.00,
  `utilidad` DECIMAL(15,2) GENERATED ALWAYS AS (ingresos - egresos) STORED,
  `margen_porcentaje` DECIMAL(5,2) GENERATED ALWAYS AS (
    CASE WHEN ingresos > 0 THEN ((ingresos - egresos) / ingresos * 100) ELSE 0 END
  ) STORED,

  -- Métricas RRHH
  `empleados_activos` INT(11) DEFAULT 0,
  `masa_salarial` DECIMAL(15,2) DEFAULT 0.00,
  `nuevos_empleados` INT(11) DEFAULT 0,
  `empleados_salidos` INT(11) DEFAULT 0,

  -- Métricas Producción
  `ordenes_produccion` INT(11) DEFAULT 0,
  `unidades_producidas` INT(11) DEFAULT 0,
  `eficiencia_produccion` DECIMAL(5,2) DEFAULT 0.00,

  -- Metadatos
  `fecha_generacion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `generado_por_cron` TINYINT(1) DEFAULT 1,

  PRIMARY KEY (`id`),
  UNIQUE KEY `empresa_periodo` (`empresa_id`, `periodo`),
  KEY `idx_empresa` (`empresa_id`),
  KEY `idx_periodo` (`periodo`),
  KEY `idx_fecha_generacion` (`fecha_generacion`),
  CONSTRAINT `fk_reportes_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: webhooks
-- Configuración de webhooks para eventos del sistema
-- =====================================================
CREATE TABLE IF NOT EXISTS `webhooks` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `nombre` VARCHAR(100) NOT NULL,
  `url` VARCHAR(500) NOT NULL COMMENT 'URL del webhook',
  `evento` VARCHAR(100) NOT NULL COMMENT 'factura_creada, pago_recibido, etc.',
  `metodo` ENUM('POST','PUT','PATCH') DEFAULT 'POST',
  `headers` TEXT DEFAULT NULL COMMENT 'JSON con headers HTTP adicionales',
  `secreto` VARCHAR(128) DEFAULT NULL COMMENT 'Secret para firma HMAC',
  `activo` TINYINT(1) DEFAULT 1,
  `reintentos_maximos` TINYINT(2) DEFAULT 3,
  `timeout_segundos` TINYINT(3) DEFAULT 30,
  `fecha_creacion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_modificacion` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_empresa` (`empresa_id`),
  KEY `idx_evento` (`evento`),
  KEY `idx_activo` (`activo`),
  CONSTRAINT `fk_webhooks_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: webhooks_logs
-- Log de ejecuciones de webhooks
-- =====================================================
CREATE TABLE IF NOT EXISTS `webhooks_logs` (
  `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
  `webhook_id` INT(11) NOT NULL,
  `evento_id` INT(11) DEFAULT NULL COMMENT 'ID del registro que disparó el evento',
  `evento_tipo` VARCHAR(50) DEFAULT NULL,
  `payload` LONGTEXT DEFAULT NULL COMMENT 'JSON enviado',
  `respuesta_codigo` INT(3) DEFAULT NULL,
  `respuesta_body` TEXT DEFAULT NULL,
  `tiempo_respuesta_ms` INT(6) DEFAULT NULL,
  `intento_numero` TINYINT(2) DEFAULT 1,
  `exitoso` TINYINT(1) DEFAULT 0,
  `error_mensaje` TEXT DEFAULT NULL,
  `fecha_creacion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_webhook` (`webhook_id`),
  KEY `idx_exitoso` (`exitoso`),
  KEY `idx_fecha` (`fecha_creacion`),
  CONSTRAINT `fk_webhooks_logs_webhook` FOREIGN KEY (`webhook_id`) REFERENCES `webhooks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- ÍNDICES ADICIONALES PARA OPTIMIZACIÓN
-- =====================================================

-- Optimizar búsquedas de logs por rango de fechas
ALTER TABLE `api_logs` ADD INDEX `idx_api_key_fecha` (`api_key`, `fecha_creacion`);
ALTER TABLE `webhooks_logs` ADD INDEX `idx_webhook_fecha` (`webhook_id`, `fecha_creacion`);

-- Optimizar conteo de requests por hora (rate limiting)
ALTER TABLE `api_logs` ADD INDEX `idx_key_fecha_hora` (`api_key`, `fecha_creacion`);

-- =====================================================
-- TRIGGERS
-- =====================================================

DELIMITER //

-- Trigger: Actualizar contador de requests en api_keys
CREATE TRIGGER IF NOT EXISTS `tr_api_logs_increment_counter` AFTER INSERT ON `api_logs`
FOR EACH ROW
BEGIN
    UPDATE api_keys
    SET total_requests = total_requests + 1,
        fecha_ultimo_uso = NEW.fecha_creacion
    WHERE api_key = NEW.api_key;
END//

-- Trigger: Limpiar logs antiguos automáticamente (mantener 90 días)
CREATE EVENT IF NOT EXISTS `ev_limpiar_api_logs_antiguos`
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_TIMESTAMP
DO
BEGIN
    DELETE FROM api_logs WHERE fecha_creacion < DATE_SUB(NOW(), INTERVAL 90 DAY);
    DELETE FROM webhooks_logs WHERE fecha_creacion < DATE_SUB(NOW(), INTERVAL 90 DAY);
END//

DELIMITER ;

-- =====================================================
-- VISTAS
-- =====================================================

-- Vista: Estadísticas de uso de API por empresa
CREATE OR REPLACE VIEW v_api_stats_empresa AS
SELECT
    ak.empresa_id,
    ak.api_key,
    ak.nombre as api_key_nombre,
    COUNT(al.id) as total_requests,
    COUNT(CASE WHEN al.codigo_respuesta < 400 THEN 1 END) as requests_exitosos,
    COUNT(CASE WHEN al.codigo_respuesta >= 400 THEN 1 END) as requests_error,
    ROUND(AVG(al.tiempo_respuesta_ms), 2) as tiempo_respuesta_promedio_ms,
    MAX(al.fecha_creacion) as ultimo_uso
FROM api_keys ak
LEFT JOIN api_logs al ON ak.api_key = al.api_key
WHERE ak.activa = 1
GROUP BY ak.empresa_id, ak.api_key, ak.nombre;

-- Vista: Requests por hora (para rate limiting)
CREATE OR REPLACE VIEW v_api_requests_por_hora AS
SELECT
    api_key,
    COUNT(*) as requests_ultima_hora
FROM api_logs
WHERE fecha_creacion > DATE_SUB(NOW(), INTERVAL 1 HOUR)
GROUP BY api_key;

-- Vista: Resumen reportes mensuales empresa
CREATE OR REPLACE VIEW v_reportes_mensuales_resumen AS
SELECT
    e.id as empresa_id,
    e.nombre as empresa_nombre,
    r.periodo,
    r.total_ventas,
    r.total_compras,
    r.utilidad,
    r.margen_porcentaje,
    r.clientes_unicos,
    r.ticket_promedio,
    r.valor_inventario,
    r.empleados_activos,
    r.fecha_generacion
FROM empresas e
INNER JOIN reportes_mensuales r ON e.id = r.empresa_id
ORDER BY r.periodo DESC;

-- Vista: Análisis de webhooks
CREATE OR REPLACE VIEW v_webhooks_performance AS
SELECT
    w.empresa_id,
    w.nombre as webhook_nombre,
    w.evento,
    w.activo,
    COUNT(wl.id) as total_ejecuciones,
    COUNT(CASE WHEN wl.exitoso = 1 THEN 1 END) as ejecuciones_exitosas,
    COUNT(CASE WHEN wl.exitoso = 0 THEN 1 END) as ejecuciones_fallidas,
    ROUND(COUNT(CASE WHEN wl.exitoso = 1 THEN 1 END) / COUNT(wl.id) * 100, 2) as tasa_exito,
    ROUND(AVG(wl.tiempo_respuesta_ms), 2) as tiempo_respuesta_promedio_ms,
    MAX(wl.fecha_creacion) as ultima_ejecucion
FROM webhooks w
LEFT JOIN webhooks_logs wl ON w.id = wl.webhook_id
GROUP BY w.empresa_id, w.id, w.nombre, w.evento, w.activo;

-- =====================================================
-- FUNCIÓN: Generar API Key aleatoria
-- =====================================================

DELIMITER //

CREATE FUNCTION IF NOT EXISTS `fn_generar_api_key`() RETURNS VARCHAR(64)
DETERMINISTIC
BEGIN
    DECLARE api_key VARCHAR(64);
    SET api_key = SHA2(CONCAT(UUID(), RAND(), NOW()), 256);
    RETURN api_key;
END//

DELIMITER ;

-- =====================================================
-- PROCEDIMIENTO: Crear API Key para empresa
-- =====================================================

DELIMITER //

CREATE PROCEDURE IF NOT EXISTS `sp_crear_api_key`(
    IN p_empresa_id INT,
    IN p_nombre VARCHAR(100),
    IN p_creado_por INT,
    IN p_fecha_expiracion TIMESTAMP,
    OUT p_api_key VARCHAR(64)
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_api_key = NULL;
    END;

    START TRANSACTION;

    -- Generar API key única
    SET p_api_key = fn_generar_api_key();

    -- Insertar API key
    INSERT INTO api_keys (empresa_id, nombre, api_key, fecha_expiracion, creado_por, activa)
    VALUES (p_empresa_id, p_nombre, p_api_key, p_fecha_expiracion, p_creado_por, 1);

    COMMIT;
END//

DELIMITER ;

-- =====================================================
-- DATOS INICIALES DE EJEMPLO
-- =====================================================

-- Ejemplo de cómo crear una API key (comentado)
-- CALL sp_crear_api_key(1, 'API Key Principal', 1, DATE_ADD(NOW(), INTERVAL 1 YEAR), @nueva_key);
-- SELECT @nueva_key;

-- =====================================================
-- PERMISOS
-- =====================================================

FLUSH PRIVILEGES;
