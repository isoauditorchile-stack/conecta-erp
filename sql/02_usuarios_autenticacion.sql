-- ============================================
-- CONECTA ERP - USUARIOS Y AUTENTICACIÓN
-- Versión: 1.0.0
-- Descripción: Empresas, Usuarios, Roles, Permisos, Sesiones, Suscripciones
-- ============================================

-- Empresas (Multi-empresa)
CREATE TABLE IF NOT EXISTS `empresas` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `nombre_empresa` VARCHAR(255) NOT NULL,
  `razon_social` VARCHAR(255),
  `rut` VARCHAR(50) NOT NULL,
  `pais_id` INT(11) NOT NULL,
  `direccion` VARCHAR(255),
  `ciudad` VARCHAR(100),
  `telefono` VARCHAR(50),
  `email` VARCHAR(255),
  `sitio_web` VARCHAR(255),
  `logo` VARCHAR(255),
  `estado` ENUM('activo','inactivo','suspendido') DEFAULT 'activo',
  `fecha_registro` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rut` (`rut`),
  KEY `fk_empresas_pais` (`pais_id`),
  CONSTRAINT `fk_empresas_pais` FOREIGN KEY (`pais_id`) REFERENCES `paises` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Usuarios del sistema
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11),
  `nombre` VARCHAR(100) NOT NULL,
  `apellido` VARCHAR(100) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `username` VARCHAR(100) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `rut` VARCHAR(50),
  `telefono` VARCHAR(50),
  `avatar` VARCHAR(255),
  `plan_id` INT(11),
  `idioma_preferido` VARCHAR(5) DEFAULT 'es',
  `es_admin` TINYINT(1) DEFAULT 0,
  `es_super_admin` TINYINT(1) DEFAULT 0,
  `estado` ENUM('activo','inactivo','pendiente','suspendido') DEFAULT 'activo',
  `en_periodo_prueba` TINYINT(1) DEFAULT 1,
  `fecha_inicio_trial` TIMESTAMP NULL DEFAULT NULL,
  `fecha_fin_trial` TIMESTAMP NULL DEFAULT NULL,
  `suscripcion_activa` TINYINT(1) DEFAULT 1,
  `ultimo_acceso` TIMESTAMP NULL DEFAULT NULL,
  `remember_token` VARCHAR(255),
  `reset_token` VARCHAR(255),
  `reset_token_expira` TIMESTAMP NULL DEFAULT NULL,
  `fecha_registro` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `username` (`username`),
  KEY `fk_usuarios_empresa` (`empresa_id`),
  KEY `fk_usuarios_plan` (`plan_id`),
  KEY `fk_usuarios_idioma` (`idioma_preferido`),
  CONSTRAINT `fk_usuarios_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_usuarios_plan` FOREIGN KEY (`plan_id`) REFERENCES `planes` (`id`),
  CONSTRAINT `fk_usuarios_idioma` FOREIGN KEY (`idioma_preferido`) REFERENCES `idiomas` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar SUPER ADMIN (auditorexchile@gmail.com)
INSERT INTO `usuarios` (
  `nombre`, `apellido`, `email`, `username`, `password`,
  `es_admin`, `es_super_admin`, `estado`, `plan_id`, `idioma_preferido`,
  `en_periodo_prueba`, `suscripcion_activa`
) VALUES (
  'Auditorex', 'Chile', 'auditorexchile@gmail.com', 'auditorex chile',
  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password
  1, 1, 'activo', 3, 'es', 0, 1
);

-- Roles del sistema
CREATE TABLE IF NOT EXISTS `roles` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(100) NOT NULL,
  `descripcion` TEXT,
  `es_sistema` TINYINT(1) DEFAULT 0 COMMENT '1 = no se puede eliminar',
  `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `roles` (`nombre`, `descripcion`, `es_sistema`) VALUES
('Super Administrador', 'Acceso total al sistema', 1),
('Administrador', 'Administrador de empresa', 1),
('Usuario', 'Usuario estándar', 1),
('Contador', 'Acceso a módulos financieros', 1),
('Vendedor', 'Acceso a módulos de ventas', 1),
('Comprador', 'Acceso a módulos de compras', 1);

-- Permisos del sistema
CREATE TABLE IF NOT EXISTS `permisos` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `modulo` VARCHAR(100) NOT NULL,
  `submodulo` VARCHAR(100),
  `accion` VARCHAR(50) NOT NULL COMMENT 'crear, leer, actualizar, eliminar, exportar',
  `descripcion` TEXT,
  `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `modulo_submodulo_accion` (`modulo`, `submodulo`, `accion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Permisos por rol
CREATE TABLE IF NOT EXISTS `rol_permisos` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `rol_id` INT(11) NOT NULL,
  `permiso_id` INT(11) NOT NULL,
  `fecha_asignacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rol_permiso` (`rol_id`, `permiso_id`),
  KEY `fk_rol_permisos_permiso` (`permiso_id`),
  CONSTRAINT `fk_rol_permisos_rol` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rol_permisos_permiso` FOREIGN KEY (`permiso_id`) REFERENCES `permisos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Roles por usuario
CREATE TABLE IF NOT EXISTS `usuario_roles` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` INT(11) NOT NULL,
  `rol_id` INT(11) NOT NULL,
  `fecha_asignacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `usuario_rol` (`usuario_id`, `rol_id`),
  KEY `fk_usuario_roles_rol` (`rol_id`),
  CONSTRAINT `fk_usuario_roles_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_usuario_roles_rol` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Permisos específicos por usuario (override de rol)
CREATE TABLE IF NOT EXISTS `usuario_permisos` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` INT(11) NOT NULL,
  `permiso_id` INT(11) NOT NULL,
  `permitido` TINYINT(1) DEFAULT 1,
  `fecha_asignacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `usuario_permiso` (`usuario_id`, `permiso_id`),
  KEY `fk_usuario_permisos_permiso` (`permiso_id`),
  CONSTRAINT `fk_usuario_permisos_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_usuario_permisos_permiso` FOREIGN KEY (`permiso_id`) REFERENCES `permisos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sesiones activas
CREATE TABLE IF NOT EXISTS `sesiones` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` INT(11) NOT NULL,
  `token` VARCHAR(255) NOT NULL,
  `ip_address` VARCHAR(45),
  `user_agent` TEXT,
  `activo` TINYINT(1) DEFAULT 1,
  `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `fecha_expiracion` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `fk_sesiones_usuario` (`usuario_id`),
  CONSTRAINT `fk_sesiones_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Logs de acceso
CREATE TABLE IF NOT EXISTS `logs_acceso` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` INT(11),
  `email` VARCHAR(255),
  `accion` VARCHAR(50) NOT NULL COMMENT 'login, logout, intento_fallido',
  `exitoso` TINYINT(1) DEFAULT 1,
  `mensaje` TEXT,
  `ip_address` VARCHAR(45),
  `user_agent` TEXT,
  `fecha_hora` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_logs_acceso_usuario` (`usuario_id`),
  KEY `idx_fecha_hora` (`fecha_hora`),
  CONSTRAINT `fk_logs_acceso_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Auditoría completa del sistema
CREATE TABLE IF NOT EXISTS `logs_auditoria` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` INT(11),
  `accion` VARCHAR(100) NOT NULL COMMENT 'crear, actualizar, eliminar, aprobar, etc',
  `tabla` VARCHAR(100),
  `registro_id` INT(11),
  `valores_anteriores` TEXT COMMENT 'JSON',
  `valores_nuevos` TEXT COMMENT 'JSON',
  `modulo` VARCHAR(100),
  `descripcion` TEXT,
  `ip_address` VARCHAR(45),
  `fecha_accion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_logs_auditoria_usuario` (`usuario_id`),
  KEY `idx_tabla_registro` (`tabla`, `registro_id`),
  KEY `idx_fecha_accion` (`fecha_accion`),
  CONSTRAINT `fk_logs_auditoria_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Suscripciones
CREATE TABLE IF NOT EXISTS `suscripciones` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` INT(11) NOT NULL,
  `empresa_id` INT(11),
  `plan_id` INT(11) NOT NULL,
  `estado` ENUM('trial','activo','suspendido','cancelado','expirado') DEFAULT 'trial',
  `fecha_inicio` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `fecha_fin` TIMESTAMP NULL DEFAULT NULL,
  `es_trial` TINYINT(1) DEFAULT 1,
  `auto_renovar` TINYINT(1) DEFAULT 1,
  `monto_mensual` DECIMAL(10,2) DEFAULT 0.00,
  `fecha_proximo_pago` DATE NULL DEFAULT NULL,
  `metodo_pago` VARCHAR(50),
  `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_suscripciones_usuario` (`usuario_id`),
  KEY `fk_suscripciones_empresa` (`empresa_id`),
  KEY `fk_suscripciones_plan` (`plan_id`),
  CONSTRAINT `fk_suscripciones_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_suscripciones_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_suscripciones_plan` FOREIGN KEY (`plan_id`) REFERENCES `planes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pagos
CREATE TABLE IF NOT EXISTS `pagos` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `suscripcion_id` INT(11) NOT NULL,
  `usuario_id` INT(11) NOT NULL,
  `monto` DECIMAL(10,2) NOT NULL,
  `moneda` VARCHAR(3) DEFAULT 'CLP',
  `metodo_pago` VARCHAR(50) COMMENT 'transferencia, tarjeta, webpay, etc',
  `estado` ENUM('pendiente','aprobado','rechazado','reembolsado') DEFAULT 'pendiente',
  `referencia_externa` VARCHAR(255) COMMENT 'ID de transacción externa',
  `comprobante` VARCHAR(255),
  `fecha_pago` TIMESTAMP NULL DEFAULT NULL,
  `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_pagos_suscripcion` (`suscripcion_id`),
  KEY `fk_pagos_usuario` (`usuario_id`),
  CONSTRAINT `fk_pagos_suscripcion` FOREIGN KEY (`suscripcion_id`) REFERENCES `suscripciones` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pagos_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notificaciones
CREATE TABLE IF NOT EXISTS `notificaciones` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` INT(11) NOT NULL,
  `tipo` VARCHAR(50) COMMENT 'info, advertencia, error, exito',
  `titulo` VARCHAR(255) NOT NULL,
  `mensaje` TEXT NOT NULL,
  `enlace` VARCHAR(255),
  `leido` TINYINT(1) DEFAULT 0,
  `fecha_lectura` TIMESTAMP NULL DEFAULT NULL,
  `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_notificaciones_usuario` (`usuario_id`),
  KEY `idx_leido` (`leido`),
  CONSTRAINT `fk_notificaciones_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Emails enviados (para tracking)
CREATE TABLE IF NOT EXISTS `emails_enviados` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `destinatario` VARCHAR(255) NOT NULL,
  `asunto` VARCHAR(255) NOT NULL,
  `tipo` VARCHAR(50) COMMENT 'bienvenida, trial, pago, etc',
  `estado` ENUM('enviado','fallido','pendiente') DEFAULT 'pendiente',
  `fecha_envio` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `error` TEXT,
  PRIMARY KEY (`id`),
  KEY `idx_destinatario` (`destinatario`),
  KEY `idx_tipo` (`tipo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
