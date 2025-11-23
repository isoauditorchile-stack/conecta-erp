-- Script SQL para crear tablas faltantes del módulo de clientes
-- Database: conectae_conectaerpbd

-- Tabla: clientes_contactos (para contactos de clientes)
CREATE TABLE IF NOT EXISTS `clientes_contactos` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `cliente_id` INT(11) NOT NULL,
  `nombre` VARCHAR(100) NOT NULL,
  `apellido` VARCHAR(100) NOT NULL,
  `cargo` VARCHAR(100) DEFAULT NULL,
  `departamento` VARCHAR(100) DEFAULT NULL,
  `email` VARCHAR(150) DEFAULT NULL,
  `telefono` VARCHAR(50) DEFAULT NULL,
  `telefono_movil` VARCHAR(50) DEFAULT NULL,
  `telefono_directo` VARCHAR(50) DEFAULT NULL,
  `rol_contacto` ENUM('PRINCIPAL','COMERCIAL','ADMINISTRATIVO','TECNICO','FINANCIERO','OTROS') DEFAULT 'OTROS',
  `es_principal` TINYINT(1) DEFAULT 0,
  `recibe_notificaciones` TINYINT(1) DEFAULT 1,
  `recibe_facturacion` TINYINT(1) DEFAULT 0,
  `linkedin` VARCHAR(200) DEFAULT NULL,
  `fecha_nacimiento` DATE DEFAULT NULL,
  `observaciones` TEXT,
  `estado` ENUM('ACTIVO','INACTIVO') DEFAULT 'ACTIVO',
  `created_by` INT(11) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_by` INT(11) DEFAULT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cliente_id` (`cliente_id`),
  KEY `idx_estado` (`estado`),
  KEY `idx_es_principal` (`es_principal`),
  CONSTRAINT `fk_contactos_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: clientes_contratos (para contratos con clientes)
CREATE TABLE IF NOT EXISTS `clientes_contratos` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `cliente_id` INT(11) NOT NULL,
  `numero_contrato` VARCHAR(50) NOT NULL UNIQUE,
  `tipo_contrato` ENUM('SERVICIOS','SUMINISTRO','MANTENIMIENTO','ARRENDAMIENTO','CONSULTORIA','MARCO','OTROS') DEFAULT 'SERVICIOS',
  `descripcion` TEXT,
  `fecha_inicio` DATE NOT NULL,
  `fecha_fin` DATE DEFAULT NULL,
  `duracion_meses` INT(11) DEFAULT NULL,
  `monto_total` DECIMAL(15,2) DEFAULT 0.00,
  `moneda` VARCHAR(10) DEFAULT 'CLP',
  `forma_pago` VARCHAR(100) DEFAULT NULL,
  `periodicidad_facturacion` ENUM('MENSUAL','TRIMESTRAL','SEMESTRAL','ANUAL','UNICO','OTROS') DEFAULT 'MENSUAL',
  `condiciones_especiales` TEXT,
  `clausulas_importantes` TEXT,
  `penalidades` TEXT,
  `renovacion_automatica` TINYINT(1) DEFAULT 0,
  `dias_preaviso_renovacion` INT(11) DEFAULT 30,
  `estado_contrato` ENUM('BORRADOR','VIGENTE','SUSPENDIDO','VENCIDO','CANCELADO','RENOVADO') DEFAULT 'BORRADOR',
  `responsable_empresa` VARCHAR(100) DEFAULT NULL,
  `responsable_cliente` VARCHAR(100) DEFAULT NULL,
  `archivo_contrato` VARCHAR(255) DEFAULT NULL,
  `observaciones` TEXT,
  `created_by` INT(11) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_by` INT(11) DEFAULT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cliente_id` (`cliente_id`),
  KEY `idx_numero_contrato` (`numero_contrato`),
  KEY `idx_estado_contrato` (`estado_contrato`),
  KEY `idx_fecha_inicio` (`fecha_inicio`),
  KEY `idx_fecha_fin` (`fecha_fin`),
  CONSTRAINT `fk_contratos_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: clientes_acuerdos (para acuerdos comerciales con clientes)
CREATE TABLE IF NOT EXISTS `clientes_acuerdos` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `cliente_id` INT(11) NOT NULL,
  `codigo_acuerdo` VARCHAR(50) NOT NULL UNIQUE,
  `nombre_acuerdo` VARCHAR(200) NOT NULL,
  `descripcion` TEXT,
  `tipo_acuerdo` ENUM('DESCUENTO_VOLUMEN','DESCUENTO_PRODUCTO','PRECIO_ESPECIAL','BONIFICACION','RAPPEL','CONDICIONES_PAGO','OTROS') DEFAULT 'DESCUENTO_VOLUMEN',
  `tipo_descuento` ENUM('PORCENTAJE','MONTO_FIJO','PRECIO_ESPECIAL') DEFAULT 'PORCENTAJE',
  `valor_descuento` DECIMAL(10,2) DEFAULT 0.00,
  `fecha_inicio` DATE NOT NULL,
  `fecha_fin` DATE DEFAULT NULL,
  `productos_aplicables` TEXT COMMENT 'JSON o lista de IDs de productos',
  `categorias_aplicables` TEXT COMMENT 'JSON o lista de categorías',
  `cantidad_minima` INT(11) DEFAULT NULL,
  `monto_minimo` DECIMAL(15,2) DEFAULT NULL,
  `frecuencia_compra` ENUM('DIARIA','SEMANAL','QUINCENAL','MENSUAL','TRIMESTRAL','SEMESTRAL','ANUAL','SIN_RESTRICCION') DEFAULT 'SIN_RESTRICCION',
  `acumulable_otros_descuentos` TINYINT(1) DEFAULT 0,
  `requiere_autorizacion` TINYINT(1) DEFAULT 0,
  `nivel_autorizacion` VARCHAR(100) DEFAULT NULL,
  `prioridad` INT(11) DEFAULT 1 COMMENT '1=mas alta, 10=mas baja',
  `condiciones_aplicacion` TEXT,
  `estado` ENUM('BORRADOR','ACTIVO','SUSPENDIDO','VENCIDO','CANCELADO') DEFAULT 'BORRADOR',
  `observaciones` TEXT,
  `created_by` INT(11) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_by` INT(11) DEFAULT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cliente_id` (`cliente_id`),
  KEY `idx_codigo_acuerdo` (`codigo_acuerdo`),
  KEY `idx_estado` (`estado`),
  KEY `idx_fecha_inicio` (`fecha_inicio`),
  KEY `idx_fecha_fin` (`fecha_fin`),
  KEY `idx_tipo_acuerdo` (`tipo_acuerdo`),
  CONSTRAINT `fk_acuerdos_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Indices adicionales para optimización
CREATE INDEX idx_clientes_contactos_email ON clientes_contactos(email);
CREATE INDEX idx_clientes_contactos_rol ON clientes_contactos(rol_contacto);
CREATE INDEX idx_clientes_contratos_tipo ON clientes_contratos(tipo_contrato);
CREATE INDEX idx_clientes_acuerdos_tipo_descuento ON clientes_acuerdos(tipo_descuento);
