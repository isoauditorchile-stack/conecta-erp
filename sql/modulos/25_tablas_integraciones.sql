-- ==================================================================
-- TABLAS PARA INTEGRACIONES - CONECTA ERP
-- Transbank, EmailManager y otras integraciones
-- ==================================================================

USE `conectae_conectaerpbd`;

-- ==================================================================
-- TRANSACCIONES DE PAGO (Transbank, Mercado Pago, etc)
-- ==================================================================
CREATE TABLE IF NOT EXISTS transacciones_pago (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NOT NULL,
    orden_id VARCHAR(100) COMMENT 'ID de pedido/orden asociada',
    token VARCHAR(200) COMMENT 'Token de transacción',
    monto DECIMAL(15,2) NOT NULL,
    estado ENUM('creada', 'aprobada', 'rechazada', 'anulada', 'pendiente') DEFAULT 'creada',
    metodo_pago VARCHAR(50) COMMENT 'transbank, mercadopago, flow, etc',
    respuesta_json TEXT COMMENT 'Respuesta completa de la pasarela',
    codigo_autorizacion VARCHAR(100),
    tipo_pago VARCHAR(50) COMMENT 'credit, debit, prepaid',
    cuotas INT DEFAULT 0,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_empresa (empresa_id),
    INDEX idx_token (token),
    INDEX idx_orden (orden_id),
    INDEX idx_estado (estado),
    INDEX idx_fecha (fecha_creacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Transacciones de pago online';

-- ==================================================================
-- EMAILS ENVIADOS (Tracking de envíos)
-- ==================================================================
CREATE TABLE IF NOT EXISTS emails_enviados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NOT NULL,
    destinatario VARCHAR(255) NOT NULL,
    asunto VARCHAR(500),
    template VARCHAR(100) COMMENT 'factura, cotizacion, recordatorio_pago, etc',
    documento_tipo VARCHAR(50) COMMENT 'factura, cotizacion, pedido',
    documento_id INT COMMENT 'ID del documento asociado',
    estado ENUM('enviado', 'fallido', 'error', 'rebotado') DEFAULT 'enviado',
    error_mensaje TEXT,
    abierto BOOLEAN DEFAULT FALSE COMMENT 'Si el email fue abierto (tracking)',
    fecha_apertura DATETIME,
    ip_apertura VARCHAR(45),
    fecha_envio DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_empresa (empresa_id),
    INDEX idx_destinatario (destinatario),
    INDEX idx_fecha (fecha_envio),
    INDEX idx_template (template),
    INDEX idx_estado (estado),
    INDEX idx_documento (documento_tipo, documento_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de emails enviados';

-- ==================================================================
-- WEBHOOKS (Para integraciones)
-- ==================================================================
CREATE TABLE IF NOT EXISTS webhooks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NOT NULL,
    nombre VARCHAR(200) NOT NULL,
    url VARCHAR(500) NOT NULL COMMENT 'URL destino del webhook',
    evento VARCHAR(100) NOT NULL COMMENT 'factura_creada, pago_recibido, etc',
    metodo ENUM('POST', 'GET', 'PUT') DEFAULT 'POST',
    headers JSON COMMENT 'Headers HTTP personalizados',
    activo BOOLEAN DEFAULT TRUE,
    secret_key VARCHAR(255) COMMENT 'Para firmar requests',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_empresa (empresa_id),
    INDEX idx_evento (evento),
    INDEX idx_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Webhooks para integraciones externas';

-- ==================================================================
-- HISTORIAL DE WEBHOOKS
-- ==================================================================
CREATE TABLE IF NOT EXISTS webhooks_historial (
    id INT AUTO_INCREMENT PRIMARY KEY,
    webhook_id INT NOT NULL,
    payload JSON COMMENT 'Datos enviados',
    response_status INT COMMENT 'HTTP status code',
    response_body TEXT,
    tiempo_respuesta INT COMMENT 'Milisegundos',
    exitoso BOOLEAN DEFAULT FALSE,
    error_mensaje TEXT,
    fecha_ejecucion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_webhook (webhook_id),
    INDEX idx_fecha (fecha_ejecucion),
    INDEX idx_exitoso (exitoso),
    FOREIGN KEY (webhook_id) REFERENCES webhooks(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Historial de ejecuciones de webhooks';

-- ==================================================================
-- API TOKENS (Para acceso API REST)
-- ==================================================================
CREATE TABLE IF NOT EXISTS api_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NOT NULL,
    usuario_id INT,
    nombre VARCHAR(200) NOT NULL COMMENT 'Nombre descriptivo del token',
    token VARCHAR(255) NOT NULL UNIQUE,
    scopes JSON COMMENT 'Permisos del token [read:facturas, write:productos]',
    ip_permitidas TEXT COMMENT 'IPs permitidas separadas por coma',
    activo BOOLEAN DEFAULT TRUE,
    ultimo_uso DATETIME,
    expira_en DATETIME COMMENT 'Fecha de expiración (opcional)',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_empresa (empresa_id),
    INDEX idx_token (token),
    INDEX idx_activo (activo),
    INDEX idx_usuario (usuario_id),
    FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tokens de acceso para API REST';

-- ==================================================================
-- LOGS DE API (Rate limiting y auditoría)
-- ==================================================================
CREATE TABLE IF NOT EXISTS api_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    token_id INT,
    metodo VARCHAR(10) COMMENT 'GET, POST, PUT, DELETE',
    endpoint VARCHAR(500),
    ip VARCHAR(45),
    user_agent VARCHAR(500),
    request_body TEXT,
    response_status INT,
    response_time INT COMMENT 'Milisegundos',
    fecha_request TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_token (token_id),
    INDEX idx_fecha (fecha_request),
    INDEX idx_ip (ip),
    INDEX idx_endpoint (endpoint),
    FOREIGN KEY (token_id) REFERENCES api_tokens(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Logs de acceso a API REST';

-- ==================================================================
-- NOTIFICACIONES PUSH (Para app mobile)
-- ==================================================================
CREATE TABLE IF NOT EXISTS push_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NOT NULL,
    usuario_id INT,
    titulo VARCHAR(200) NOT NULL,
    mensaje TEXT NOT NULL,
    tipo VARCHAR(50) COMMENT 'factura, pedido, alerta, etc',
    data JSON COMMENT 'Datos adicionales',
    leido BOOLEAN DEFAULT FALSE,
    fecha_lectura DATETIME,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_empresa (empresa_id),
    INDEX idx_usuario (usuario_id),
    INDEX idx_leido (leido),
    INDEX idx_tipo (tipo),
    INDEX idx_fecha (fecha_creacion),
    FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Notificaciones push para usuarios';

-- ==================================================================
-- TOKENS DE DISPOSITIVOS MOBILE
-- ==================================================================
CREATE TABLE IF NOT EXISTS device_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    device_token VARCHAR(500) NOT NULL COMMENT 'Token FCM/APNS',
    plataforma ENUM('android', 'ios', 'web') NOT NULL,
    modelo_dispositivo VARCHAR(200),
    version_app VARCHAR(50),
    activo BOOLEAN DEFAULT TRUE,
    ultimo_acceso DATETIME,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_usuario (usuario_id),
    INDEX idx_token (device_token(255)),
    INDEX idx_activo (activo),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tokens de dispositivos para push notifications';

-- ==================================================================
-- CONFIGURACIONES SMTP POR EMPRESA
-- ==================================================================
ALTER TABLE configuracion_empresa
ADD COLUMN IF NOT EXISTS smtp_host VARCHAR(255) DEFAULT 'smtp.gmail.com',
ADD COLUMN IF NOT EXISTS smtp_port INT DEFAULT 587,
ADD COLUMN IF NOT EXISTS smtp_user VARCHAR(255),
ADD COLUMN IF NOT EXISTS smtp_pass VARCHAR(255),
ADD COLUMN IF NOT EXISTS smtp_encryption VARCHAR(20) DEFAULT 'tls',
ADD COLUMN IF NOT EXISTS modo_transbank ENUM('desarrollo', 'produccion') DEFAULT 'desarrollo',
ADD COLUMN IF NOT EXISTS transbank_commerce_code VARCHAR(100),
ADD COLUMN IF NOT EXISTS transbank_api_key VARCHAR(500);

-- ==================================================================
-- CRON JOBS / TAREAS PROGRAMADAS
-- ==================================================================
CREATE TABLE IF NOT EXISTS cron_jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NOT NULL,
    nombre VARCHAR(200) NOT NULL,
    descripcion TEXT,
    script VARCHAR(500) NOT NULL COMMENT 'Ruta al script PHP',
    frecuencia VARCHAR(100) COMMENT 'Expresión cron: 0 9 * * *',
    activo BOOLEAN DEFAULT TRUE,
    ultima_ejecucion DATETIME,
    proxima_ejecucion DATETIME,
    ejecuciones_total INT DEFAULT 0,
    ejecuciones_exitosas INT DEFAULT 0,
    ejecuciones_fallidas INT DEFAULT 0,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_empresa (empresa_id),
    INDEX idx_activo (activo),
    INDEX idx_proxima (proxima_ejecucion),
    FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tareas programadas (CRON)';

-- ==================================================================
-- HISTORIAL DE CRON JOBS
-- ==================================================================
CREATE TABLE IF NOT EXISTS cron_jobs_historial (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    cron_job_id INT NOT NULL,
    fecha_inicio DATETIME NOT NULL,
    fecha_fin DATETIME,
    duracion INT COMMENT 'Segundos',
    exitoso BOOLEAN DEFAULT FALSE,
    output TEXT COMMENT 'Salida del script',
    error_mensaje TEXT,
    INDEX idx_cron_job (cron_job_id),
    INDEX idx_fecha (fecha_inicio),
    INDEX idx_exitoso (exitoso),
    FOREIGN KEY (cron_job_id) REFERENCES cron_jobs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Historial de ejecuciones de CRON';

-- ==================================================================
-- FIN TABLAS DE INTEGRACIONES
-- ==================================================================
