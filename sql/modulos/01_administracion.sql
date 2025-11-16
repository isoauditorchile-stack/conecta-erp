-- =====================================================
-- MÓDULO 1: ADMINISTRACIÓN CENTRAL - CONECTA ERP
-- Tablas para Gestión de Empresas, Seguridad y Configuración
-- =====================================================

USE conectae_conectaerpbd;

-- =====================================================
-- TABLA: empresas
-- Gestión de empresas multi-tenant
-- =====================================================
CREATE TABLE IF NOT EXISTS `empresas` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `rut` VARCHAR(20) NOT NULL,
  `nombre` VARCHAR(200) NOT NULL,
  `razon_social` VARCHAR(255) NOT NULL,
  `giro` VARCHAR(255) DEFAULT NULL,
  `direccion` VARCHAR(255) DEFAULT NULL,
  `comuna` VARCHAR(100) DEFAULT NULL,
  `ciudad` VARCHAR(100) DEFAULT NULL,
  `region` VARCHAR(100) DEFAULT NULL,
  `pais_id` INT(11) DEFAULT 1,
  `telefono` VARCHAR(50) DEFAULT NULL,
  `email` VARCHAR(255) DEFAULT NULL,
  `sitio_web` VARCHAR(255) DEFAULT NULL,
  `codigo_actividad` VARCHAR(50) DEFAULT NULL COMMENT 'Código actividad económica SII',
  `tipo_contribuyente` ENUM('primera_categoria','segunda_categoria','regimen_simplificado','sin_fines_lucro') DEFAULT 'primera_categoria',
  `logo` VARCHAR(255) DEFAULT NULL,
  `estado` ENUM('activo','inactivo','suspendido') DEFAULT 'activo',
  `fecha_creacion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `creado_por` INT(11) DEFAULT NULL,
  `fecha_modificacion` TIMESTAMP NULL DEFAULT NULL,
  `modificado_por` INT(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rut` (`rut`),
  KEY `idx_estado` (`estado`),
  KEY `idx_pais` (`pais_id`),
  KEY `idx_creado_por` (`creado_por`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: roles
-- Roles del sistema para control de acceso
-- =====================================================
CREATE TABLE IF NOT EXISTS `roles` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(100) NOT NULL,
  `descripcion` TEXT DEFAULT NULL,
  `permisos` JSON DEFAULT NULL COMMENT 'Permisos en formato JSON',
  `activo` TINYINT(1) DEFAULT 1,
  `fecha_creacion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `creado_por` INT(11) DEFAULT NULL,
  `fecha_modificacion` TIMESTAMP NULL DEFAULT NULL,
  `modificado_por` INT(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`),
  KEY `idx_activo` (`activo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: usuario_roles
-- Relación usuarios con roles (muchos a muchos)
-- =====================================================
CREATE TABLE IF NOT EXISTS `usuario_roles` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` INT(11) NOT NULL,
  `rol_id` INT(11) NOT NULL,
  `fecha_asignacion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `asignado_por` INT(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `usuario_rol` (`usuario_id`, `rol_id`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_rol` (`rol_id`),
  CONSTRAINT `fk_usuario_roles_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_usuario_roles_rol` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: logs_acceso
-- Registro de accesos al sistema
-- =====================================================
CREATE TABLE IF NOT EXISTS `logs_acceso` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` INT(11) DEFAULT NULL,
  `email` VARCHAR(255) DEFAULT NULL,
  `accion` VARCHAR(100) NOT NULL COMMENT 'login, logout, intento_fallido',
  `exitoso` TINYINT(1) DEFAULT 1,
  `mensaje` TEXT DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` TEXT DEFAULT NULL,
  `fecha_acceso` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_fecha` (`fecha_acceso`),
  KEY `idx_exitoso` (`exitoso`),
  KEY `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: logs_auditoria
-- Auditoría completa de acciones en el sistema
-- =====================================================
CREATE TABLE IF NOT EXISTS `logs_auditoria` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` INT(11) DEFAULT NULL,
  `empresa_id` INT(11) DEFAULT NULL,
  `accion` VARCHAR(100) NOT NULL COMMENT 'crear, editar, eliminar, etc.',
  `tabla` VARCHAR(100) DEFAULT NULL,
  `registro_id` INT(11) DEFAULT NULL,
  `modulo` VARCHAR(100) DEFAULT NULL,
  `descripcion` TEXT DEFAULT NULL,
  `datos_anteriores` JSON DEFAULT NULL,
  `datos_nuevos` JSON DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` TEXT DEFAULT NULL,
  `fecha_accion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_empresa` (`empresa_id`),
  KEY `idx_tabla` (`tabla`),
  KEY `idx_fecha` (`fecha_accion`),
  KEY `idx_modulo` (`modulo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: sesiones
-- Sesiones activas de usuarios
-- =====================================================
CREATE TABLE IF NOT EXISTS `sesiones` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` INT(11) NOT NULL,
  `token` VARCHAR(255) NOT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` TEXT DEFAULT NULL,
  `activo` TINYINT(1) DEFAULT 1,
  `fecha_inicio` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_expiracion` TIMESTAMP NULL DEFAULT NULL,
  `fecha_ultimo_acceso` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_activo` (`activo`),
  CONSTRAINT `fk_sesiones_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: configuracion
-- Parámetros globales del sistema
-- =====================================================
CREATE TABLE IF NOT EXISTS `configuracion` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `clave` VARCHAR(100) NOT NULL,
  `valor` TEXT DEFAULT NULL,
  `tipo` ENUM('string','integer','boolean','json','text') DEFAULT 'string',
  `grupo` VARCHAR(50) DEFAULT 'general' COMMENT 'general, email, seguridad, suscripcion, etc.',
  `descripcion` VARCHAR(255) DEFAULT NULL,
  `es_editable` TINYINT(1) DEFAULT 1,
  `fecha_modificacion` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `clave` (`clave`),
  KEY `idx_grupo` (`grupo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- INSERTAR CONFIGURACIONES POR DEFECTO
-- =====================================================
INSERT INTO `configuracion` (`clave`, `valor`, `tipo`, `grupo`, `descripcion`, `es_editable`) VALUES
-- General
('nombre_sistema', 'CONECTA ERP', 'string', 'general', 'Nombre del sistema', 1),
('moneda_principal', 'CLP', 'string', 'general', 'Moneda principal del sistema', 1),
('idioma_defecto', 'es', 'string', 'general', 'Idioma por defecto', 1),
('zona_horaria', 'America/Santiago', 'string', 'general', 'Zona horaria', 1),
('formato_fecha', 'd/m/Y', 'string', 'general', 'Formato de fecha', 1),
('formato_hora', 'H:i', 'string', 'general', 'Formato de hora', 1),
('decimales_moneda', '0', 'integer', 'general', 'Decimales para moneda', 1),
('separador_miles', '.', 'string', 'general', 'Separador de miles', 1),
('separador_decimales', ',', 'string', 'general', 'Separador de decimales', 1),

-- Email
('smtp_habilitado', '0', 'boolean', 'email', 'SMTP habilitado', 1),
('smtp_host', 'smtp.gmail.com', 'string', 'email', 'Servidor SMTP', 1),
('smtp_puerto', '587', 'integer', 'email', 'Puerto SMTP', 1),
('smtp_usuario', '', 'string', 'email', 'Usuario SMTP', 1),
('smtp_password', '', 'string', 'email', 'Contraseña SMTP', 1),
('smtp_seguridad', 'tls', 'string', 'email', 'Seguridad SMTP (tls/ssl)', 1),
('email_from', 'noreply@conectaerp.com', 'string', 'email', 'Email remitente', 1),
('email_from_nombre', 'CONECTA ERP', 'string', 'email', 'Nombre remitente', 1),

-- Suscripción
('dias_trial', '14', 'integer', 'suscripcion', 'Días de prueba', 1),
('permitir_registro', '1', 'boolean', 'suscripcion', 'Permitir auto-registro', 1),

-- Seguridad
('max_intentos_login', '5', 'integer', 'seguridad', 'Máximo intentos de login', 1),
('tiempo_bloqueo_login', '15', 'integer', 'seguridad', 'Tiempo de bloqueo (minutos)', 1),
('2fa_habilitado', '0', 'boolean', 'seguridad', '2FA habilitado', 1),
('longitud_minima_password', '8', 'integer', 'seguridad', 'Longitud mínima contraseña', 1),

-- Impuestos
('nombre_impuesto', 'IVA', 'string', 'impuestos', 'Nombre del impuesto', 1),
('porcentaje_impuesto', '19', 'integer', 'impuestos', 'Porcentaje del impuesto', 1),

-- Apariencia
('color_primario', '#667eea', 'string', 'apariencia', 'Color primario', 1),
('color_secundario', '#764ba2', 'string', 'apariencia', 'Color secundario', 1)
ON DUPLICATE KEY UPDATE valor = VALUES(valor);

-- =====================================================
-- INSERTAR ROLES POR DEFECTO
-- =====================================================
INSERT INTO `roles` (`nombre`, `descripcion`, `permisos`, `activo`) VALUES
('Super Administrador', 'Acceso total al sistema', '{"all": true}', 1),
('Administrador', 'Administrador de empresa', '{"usuarios": {"crear": true, "leer": true, "editar": true, "eliminar": true}, "empresas": {"leer": true, "editar": true}, "clientes": {"crear": true, "leer": true, "editar": true, "eliminar": true}, "proveedores": {"crear": true, "leer": true, "editar": true, "eliminar": true}, "productos": {"crear": true, "leer": true, "editar": true, "eliminar": true}, "ventas": {"crear": true, "leer": true, "editar": true}, "compras": {"crear": true, "leer": true, "editar": true}, "inventario": {"crear": true, "leer": true, "editar": true}, "finanzas": {"leer": true, "editar": true}, "reportes": {"leer": true, "exportar": true}}', 1),
('Vendedor', 'Usuario de ventas', '{"clientes": {"crear": true, "leer": true, "editar": true}, "productos": {"leer": true}, "ventas": {"crear": true, "leer": true, "editar": true}, "reportes": {"leer": true}}', 1),
('Contador', 'Usuario contable', '{"finanzas": {"crear": true, "leer": true, "editar": true}, "contabilidad": {"crear": true, "leer": true, "editar": true}, "reportes": {"leer": true, "exportar": true}}', 1),
('Bodeguero', 'Gestión de inventario', '{"productos": {"crear": true, "leer": true, "editar": true}, "inventario": {"crear": true, "leer": true, "editar": true}, "compras": {"leer": true}}', 1),
('Visor', 'Solo lectura', '{"clientes": {"leer": true}, "proveedores": {"leer": true}, "productos": {"leer": true}, "ventas": {"leer": true}, "compras": {"leer": true}, "inventario": {"leer": true}, "reportes": {"leer": true}}', 1)
ON DUPLICATE KEY UPDATE descripcion = VALUES(descripcion);

-- =====================================================
-- ÍNDICES ADICIONALES PARA OPTIMIZACIÓN
-- =====================================================

-- Índices para búsquedas frecuentes
ALTER TABLE `empresas` ADD INDEX `idx_nombre` (`nombre`);
ALTER TABLE `empresas` ADD INDEX `idx_email` (`email`);

-- Índices compuestos para filtros
ALTER TABLE `logs_acceso` ADD INDEX `idx_usuario_fecha` (`usuario_id`, `fecha_acceso`);
ALTER TABLE `logs_auditoria` ADD INDEX `idx_empresa_fecha` (`empresa_id`, `fecha_accion`);

-- =====================================================
-- TRIGGERS PARA AUDITORÍA AUTOMÁTICA
-- =====================================================

-- Trigger: empresas - INSERT
DELIMITER //
CREATE TRIGGER IF NOT EXISTS `tr_empresas_insert` AFTER INSERT ON `empresas`
FOR EACH ROW
BEGIN
    INSERT INTO logs_auditoria (usuario_id, empresa_id, accion, tabla, registro_id, modulo, descripcion, datos_nuevos, ip_address)
    VALUES (NEW.creado_por, NEW.id, 'crear', 'empresas', NEW.id, 'administracion',
            CONCAT('Empresa creada: ', NEW.nombre),
            JSON_OBJECT('rut', NEW.rut, 'nombre', NEW.nombre, 'razon_social', NEW.razon_social),
            COALESCE(@client_ip, '127.0.0.1'));
END//

-- Trigger: empresas - UPDATE
CREATE TRIGGER IF NOT EXISTS `tr_empresas_update` AFTER UPDATE ON `empresas`
FOR EACH ROW
BEGIN
    IF OLD.estado != NEW.estado OR OLD.nombre != NEW.nombre THEN
        INSERT INTO logs_auditoria (usuario_id, empresa_id, accion, tabla, registro_id, modulo, descripcion, datos_anteriores, datos_nuevos, ip_address)
        VALUES (NEW.modificado_por, NEW.id, 'editar', 'empresas', NEW.id, 'administracion',
                CONCAT('Empresa actualizada: ', NEW.nombre),
                JSON_OBJECT('estado', OLD.estado, 'nombre', OLD.nombre),
                JSON_OBJECT('estado', NEW.estado, 'nombre', NEW.nombre),
                COALESCE(@client_ip, '127.0.0.1'));
    END IF;
END//

DELIMITER ;

-- =====================================================
-- PERMISOS Y SEGURIDAD
-- =====================================================

-- Asegurar que solo el usuario de la BD pueda acceder
FLUSH PRIVILEGES;
