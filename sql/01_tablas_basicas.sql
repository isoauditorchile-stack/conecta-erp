-- ============================================
-- CONECTA ERP - TABLAS BÁSICAS
-- Versión: 1.0.0
-- Descripción: Idiomas, Países, Planes, Traducciones
-- ============================================

-- Idiomas soportados (8 idiomas)
CREATE TABLE IF NOT EXISTS `idiomas` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `codigo` VARCHAR(5) NOT NULL,
  `nombre` VARCHAR(100) NOT NULL,
  `nombre_nativo` VARCHAR(100) NOT NULL,
  `activo` TINYINT(1) DEFAULT 1,
  `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `idiomas` (`codigo`, `nombre`, `nombre_nativo`, `activo`) VALUES
('es', 'Español', 'Español', 1),
('en', 'Inglés', 'English', 1),
('pt', 'Portugués', 'Português', 1),
('fr', 'Francés', 'Français', 1),
('de', 'Alemán', 'Deutsch', 1),
('it', 'Italiano', 'Italiano', 1),
('ru', 'Ruso', 'Русский', 1),
('zh', 'Chino', '中文', 1);

-- Países soportados (9 países)
CREATE TABLE IF NOT EXISTS `paises` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `codigo` VARCHAR(3) NOT NULL,
  `nombre` VARCHAR(100) NOT NULL,
  `tipo_documento` VARCHAR(50) NOT NULL COMMENT 'RUT, DNI, RFC, etc',
  `formato_documento` VARCHAR(100) NOT NULL,
  `validacion_regex` VARCHAR(255),
  `moneda_codigo` VARCHAR(3) DEFAULT 'USD',
  `moneda_simbolo` VARCHAR(10) DEFAULT '$',
  `activo` TINYINT(1) DEFAULT 1,
  `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `paises` (`codigo`, `nombre`, `tipo_documento`, `formato_documento`, `validacion_regex`, `moneda_codigo`, `moneda_simbolo`, `activo`) VALUES
('CL', 'Chile', 'RUT', '##.###.###-#', '^[0-9]{1,2}\\.[0-9]{3}\\.[0-9]{3}-[0-9Kk]$', 'CLP', '$', 1),
('AR', 'Argentina', 'DNI', '##.###.###', '^[0-9]{2}\\.[0-9]{3}\\.[0-9]{3}$', 'ARS', '$', 1),
('BR', 'Brasil', 'CPF', '###.###.###-##', '^[0-9]{3}\\.[0-9]{3}\\.[0-9]{3}-[0-9]{2}$', 'BRL', 'R$', 1),
('PE', 'Perú', 'DNI', '########', '^[0-9]{8}$', 'PEN', 'S/', 1),
('CO', 'Colombia', 'CC', '##########', '^[0-9]{10}$', 'COP', '$', 1),
('MX', 'México', 'RFC', 'XXXX######XXX', '^[A-Z]{4}[0-9]{6}[A-Z0-9]{3}$', 'MXN', '$', 1),
('UY', 'Uruguay', 'CI', '#.###.###-#', '^[0-9]\\.[0-9]{3}\\.[0-9]{3}-[0-9]$', 'UYU', '$', 1),
('EC', 'Ecuador', 'Cédula', '##########', '^[0-9]{10}$', 'USD', '$', 1),
('BO', 'Bolivia', 'CI', '#######', '^[0-9]{7}$', 'BOB', 'Bs', 1);

-- Planes de suscripción
CREATE TABLE IF NOT EXISTS `planes` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `nombre_plan` VARCHAR(100) NOT NULL,
  `descripcion` TEXT,
  `precio_mensual` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `precio_anual` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `max_usuarios` INT(11) DEFAULT NULL COMMENT 'NULL = ilimitado',
  `max_empresas` INT(11) DEFAULT 1,
  `modulos_incluidos` TEXT COMMENT 'JSON con IDs de módulos',
  `caracteristicas` TEXT COMMENT 'JSON con características',
  `trial_dias` INT(11) DEFAULT 14,
  `activo` TINYINT(1) DEFAULT 1,
  `es_personalizado` TINYINT(1) DEFAULT 0,
  `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `planes` (`nombre_plan`, `descripcion`, `precio_mensual`, `precio_anual`, `max_usuarios`, `max_empresas`, `modulos_incluidos`, `caracteristicas`, `trial_dias`, `activo`, `es_personalizado`) VALUES
('Básico', 'Ideal para pequeñas empresas que están comenzando', 49990.00, 539890.00, 5, 1,
'[1,2,3,5,6,13]',
'["Hasta 5 usuarios","6 módulos básicos","1 empresa","Soporte email","Actualizaciones incluidas"]',
14, 1, 0),

('Profesional', 'Para empresas en crecimiento que necesitan más poder', 99990.00, 1079890.00, 25, 3,
'[1,2,3,4,5,6,7,8,9,10,13,14]',
'["Hasta 25 usuarios","12 módulos completos","3 empresas","Soporte prioritario","Reportes avanzados","Integraciones SII/Previred"]',
14, 1, 0),

('Empresarial', 'Para grandes empresas con necesidades avanzadas', 199990.00, 2159890.00, NULL, NULL,
'[1,2,3,4,5,6,7,8,9,10,11,12,13,14]',
'["Usuarios ilimitados","14 módulos + 106 submódulos","Empresas ilimitadas","Soporte 24/7","Business Intelligence","API completa","Capacitación incluida"]',
14, 1, 0),

('Personalizado', 'Soluciones diseñadas específicamente para tu negocio', 0.00, 0.00, NULL, NULL,
'[1,2,3,4,5,6,7,8,9,10,11,12,13,14]',
'["Todo de Empresarial","Desarrollo a medida","Módulos personalizados","Servidor dedicado","Gerente de cuenta","SLA garantizado"]',
14, 1, 1);

-- Traducciones del sistema
CREATE TABLE IF NOT EXISTS `traducciones` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `clave` VARCHAR(255) NOT NULL,
  `idioma_codigo` VARCHAR(5) NOT NULL,
  `texto` TEXT NOT NULL,
  `modulo` VARCHAR(100) DEFAULT 'general',
  `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `clave_idioma` (`clave`, `idioma_codigo`),
  KEY `fk_traducciones_idioma` (`idioma_codigo`),
  CONSTRAINT `fk_traducciones_idioma` FOREIGN KEY (`idioma_codigo`) REFERENCES `idiomas` (`codigo`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Configuración general del sistema
CREATE TABLE IF NOT EXISTS `configuracion` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `clave` VARCHAR(100) NOT NULL,
  `valor` TEXT,
  `tipo` ENUM('string','number','boolean','json') DEFAULT 'string',
  `descripcion` TEXT,
  `grupo` VARCHAR(50) DEFAULT 'general',
  `es_editable` TINYINT(1) DEFAULT 1,
  `fecha_actualizacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `clave` (`clave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `configuracion` (`clave`, `valor`, `tipo`, `descripcion`, `grupo`, `es_editable`) VALUES
('sistema_nombre', 'CONECTA ERP', 'string', 'Nombre del sistema', 'general', 0),
('sistema_version', '1.0.0', 'string', 'Versión del sistema', 'general', 0),
('admin_email', 'auditorexchile@gmail.com', 'string', 'Email del super administrador', 'admin', 0),
('admin_username', 'auditorex chile', 'string', 'Username del super administrador', 'admin', 0),
('trial_dias', '14', 'number', 'Días de periodo de prueba', 'suscripcion', 1),
('trial_aviso_dia', '13', 'number', 'Día en que se envía aviso de expiración', 'suscripcion', 1),
('email_remitente', 'auditorexchile@gmail.com', 'string', 'Email remitente de notificaciones', 'email', 1),
('email_nombre', 'CONECTA ERP', 'string', 'Nombre del remitente de emails', 'email', 1),
('moneda_base', 'CLP', 'string', 'Moneda base del sistema', 'finanzas', 1),
('idioma_default', 'es', 'string', 'Idioma por defecto', 'general', 1),
('pais_default', 'CL', 'string', 'País por defecto', 'general', 1);

-- Tabla para almacenar valores de UF, Dólar, UTM
CREATE TABLE IF NOT EXISTS `indicadores_economicos` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `tipo` ENUM('UF','DOLAR','UTM','EURO') NOT NULL,
  `valor` DECIMAL(15,4) NOT NULL,
  `fecha` DATE NOT NULL,
  `fuente` VARCHAR(255) DEFAULT NULL,
  `fecha_actualizacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tipo_fecha` (`tipo`, `fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
