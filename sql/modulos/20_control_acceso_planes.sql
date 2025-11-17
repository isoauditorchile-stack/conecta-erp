-- ===============================================
-- CONECTA ERP - SISTEMA DE CONTROL DE ACCESO
-- Módulo: Planes, Módulos y Control de Suscripciones
-- ===============================================

-- ===============================================
-- TABLA: planes
-- Catálogo de planes disponibles
-- ===============================================
CREATE TABLE IF NOT EXISTS `planes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) NOT NULL,
  `descripcion` TEXT NULL,
  `precio_mensual` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `precio_anual` DECIMAL(10,2) NULL DEFAULT NULL COMMENT 'NULL si no aplica',
  `max_usuarios` INT NULL DEFAULT NULL COMMENT 'NULL = ilimitado',
  `max_empresas` INT NULL DEFAULT NULL COMMENT 'NULL = ilimitado',
  `dias_trial` INT NOT NULL DEFAULT 14,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `caracteristicas` JSON NULL COMMENT 'Features adicionales del plan',
  `orden` INT NOT NULL DEFAULT 0,
  `destacado` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Plan recomendado',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_slug` (`slug`),
  KEY `idx_activo` (`activo`),
  KEY `idx_orden` (`orden`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Planes de suscripción disponibles';

-- ===============================================
-- TABLA: modulos
-- Catálogo de módulos y submódulos del sistema
-- ===============================================
CREATE TABLE IF NOT EXISTS `modulos` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) NOT NULL COMMENT 'URL slug para routing',
  `descripcion` TEXT NULL,
  `icono` VARCHAR(50) NULL DEFAULT 'fas fa-cube',
  `ruta` VARCHAR(255) NULL COMMENT 'Ruta del archivo del módulo',
  `orden` INT NOT NULL DEFAULT 0,
  `parent_id` INT UNSIGNED NULL DEFAULT NULL COMMENT 'ID del módulo padre (para submódulos)',
  `requiere_licencia` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '0=Acceso libre, 1=Requiere plan',
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_slug` (`slug`),
  KEY `idx_parent` (`parent_id`),
  KEY `idx_activo` (`activo`),
  KEY `idx_orden` (`orden`),
  CONSTRAINT `fk_modulos_parent` FOREIGN KEY (`parent_id`)
    REFERENCES `modulos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Catálogo de módulos y submódulos del sistema';

-- ===============================================
-- TABLA: plan_modulos
-- Relación de qué módulos incluye cada plan
-- ===============================================
CREATE TABLE IF NOT EXISTS `plan_modulos` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `plan_id` INT UNSIGNED NOT NULL,
  `modulo_id` INT UNSIGNED NOT NULL,
  `acceso_completo` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=Acceso completo, 0=Acceso limitado',
  `limite_registros` INT NULL DEFAULT NULL COMMENT 'NULL=ilimitado, N=máximo de registros',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_plan_modulo` (`plan_id`, `modulo_id`),
  KEY `idx_plan` (`plan_id`),
  KEY `idx_modulo` (`modulo_id`),
  CONSTRAINT `fk_plan_modulos_plan` FOREIGN KEY (`plan_id`)
    REFERENCES `planes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_plan_modulos_modulo` FOREIGN KEY (`modulo_id`)
    REFERENCES `modulos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Módulos incluidos en cada plan';

-- ===============================================
-- TABLA: pagos
-- Historial de pagos de suscripciones
-- ===============================================
CREATE TABLE IF NOT EXISTS `pagos` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `suscripcion_id` INT UNSIGNED NOT NULL,
  `empresa_id` INT UNSIGNED NOT NULL,
  `usuario_id` INT UNSIGNED NOT NULL COMMENT 'Usuario que realizó el pago',
  `monto` DECIMAL(10,2) NOT NULL,
  `moneda` VARCHAR(3) NOT NULL DEFAULT 'CLP',
  `metodo_pago` VARCHAR(50) NOT NULL COMMENT 'Transbank, PayPal, MercadoPago, Stripe, etc',
  `estado` ENUM('pendiente', 'completado', 'fallido', 'reembolsado', 'cancelado') NOT NULL DEFAULT 'pendiente',
  `transaccion_id` VARCHAR(255) NULL COMMENT 'ID de transacción del procesador',
  `orden_compra` VARCHAR(100) NULL COMMENT 'Número de orden',
  `fecha_pago` DATETIME NULL DEFAULT NULL,
  `periodo_desde` DATE NOT NULL,
  `periodo_hasta` DATE NOT NULL,
  `datos_pago` JSON NULL COMMENT 'Datos adicionales del procesador de pago',
  `ip_address` VARCHAR(45) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_suscripcion` (`suscripcion_id`),
  KEY `idx_empresa` (`empresa_id`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_estado` (`estado`),
  KEY `idx_fecha_pago` (`fecha_pago`),
  KEY `idx_transaccion` (`transaccion_id`),
  CONSTRAINT `fk_pagos_suscripcion` FOREIGN KEY (`suscripcion_id`)
    REFERENCES `suscripciones` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_pagos_empresa` FOREIGN KEY (`empresa_id`)
    REFERENCES `empresas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_pagos_usuario` FOREIGN KEY (`usuario_id`)
    REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Historial de pagos de suscripciones';

-- ===============================================
-- TABLA: logs_acceso_denegado
-- Registro de intentos de acceso a módulos sin permisos
-- ===============================================
CREATE TABLE IF NOT EXISTS `logs_acceso_denegado` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id` INT UNSIGNED NOT NULL,
  `empresa_id` INT UNSIGNED NOT NULL,
  `modulo_slug` VARCHAR(100) NOT NULL,
  `razon` VARCHAR(255) NOT NULL COMMENT 'plan_no_incluye, suscripcion_expirada, trial_expirado, etc',
  `ip_address` VARCHAR(45) NULL,
  `user_agent` VARCHAR(255) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_empresa` (`empresa_id`),
  KEY `idx_modulo` (`modulo_slug`),
  KEY `idx_created` (`created_at`),
  CONSTRAINT `fk_logs_acceso_usuario` FOREIGN KEY (`usuario_id`)
    REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_logs_acceso_empresa` FOREIGN KEY (`empresa_id`)
    REFERENCES `empresas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Log de intentos de acceso denegado a módulos';

-- ===============================================
-- TABLA: logs_cambio_plan
-- Historial de cambios de plan (upgrades/downgrades)
-- ===============================================
CREATE TABLE IF NOT EXISTS `logs_cambio_plan` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `empresa_id` INT UNSIGNED NOT NULL,
  `usuario_id` INT UNSIGNED NOT NULL COMMENT 'Usuario que hizo el cambio',
  `plan_anterior_id` INT UNSIGNED NULL,
  `plan_nuevo_id` INT UNSIGNED NOT NULL,
  `tipo_cambio` ENUM('upgrade', 'downgrade', 'migracion', 'trial_a_pago') NOT NULL,
  `razon` VARCHAR(255) NULL,
  `monto_prorata` DECIMAL(10,2) NULL COMMENT 'Ajuste proporcional si aplica',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_empresa` (`empresa_id`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_plan_anterior` (`plan_anterior_id`),
  KEY `idx_plan_nuevo` (`plan_nuevo_id`),
  CONSTRAINT `fk_logs_cambio_empresa` FOREIGN KEY (`empresa_id`)
    REFERENCES `empresas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_logs_cambio_usuario` FOREIGN KEY (`usuario_id`)
    REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_logs_cambio_plan_anterior` FOREIGN KEY (`plan_anterior_id`)
    REFERENCES `planes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_logs_cambio_plan_nuevo` FOREIGN KEY (`plan_nuevo_id`)
    REFERENCES `planes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Historial de cambios de plan';

-- ===============================================
-- DATOS INICIALES: PLANES
-- ===============================================
INSERT INTO `planes` (`id`, `nombre`, `slug`, `descripcion`, `precio_mensual`, `precio_anual`, `max_usuarios`, `max_empresas`, `dias_trial`, `activo`, `orden`, `destacado`, `caracteristicas`) VALUES
(1, 'Básico', 'basico', 'Ideal para pequeñas empresas que están comenzando', 49990.00, 499990.00, 5, 1, 14, 1, 1, 0,
  JSON_OBJECT(
    'storage_gb', 10,
    'api_calls_mes', 1000,
    'soporte', 'email',
    'reportes_personalizados', false,
    'integraciones_externas', false,
    'usuarios_simultaneos', 3
  )
),
(2, 'Profesional', 'profesional', 'Para empresas en crecimiento que necesitan más poder', 99990.00, 999990.00, 25, 3, 14, 1, 2, 1,
  JSON_OBJECT(
    'storage_gb', 50,
    'api_calls_mes', 10000,
    'soporte', 'prioritario',
    'reportes_personalizados', true,
    'integraciones_externas', true,
    'usuarios_simultaneos', 15
  )
),
(3, 'Empresarial', 'empresarial', 'Para grandes empresas con necesidades avanzadas', 199990.00, 1999990.00, NULL, NULL, 14, 1, 3, 0,
  JSON_OBJECT(
    'storage_gb', 500,
    'api_calls_mes', 100000,
    'soporte', '24/7',
    'reportes_personalizados', true,
    'integraciones_externas', true,
    'usuarios_simultaneos', 999,
    'capacitacion_incluida', true,
    'gerente_cuenta', true
  )
),
(4, 'Personalizado', 'personalizado', 'Soluciones diseñadas específicamente para tu negocio', 0.00, NULL, NULL, NULL, 30, 1, 4, 0,
  JSON_OBJECT(
    'storage_gb', 9999,
    'api_calls_mes', 999999,
    'soporte', 'dedicado',
    'reportes_personalizados', true,
    'integraciones_externas', true,
    'desarrollo_medida', true,
    'servidor_dedicado', true,
    'sla_garantizado', true
  )
);

-- ===============================================
-- DATOS INICIALES: MÓDULOS
-- ===============================================
INSERT INTO `modulos` (`id`, `nombre`, `slug`, `descripcion`, `icono`, `ruta`, `orden`, `parent_id`, `requiere_licencia`) VALUES
-- Módulos principales (17)
(1, 'Administración', 'administracion', 'Gestión de usuarios, empresas, roles y permisos', 'fas fa-cog', '/modulos/admin/', 1, NULL, 0),
(2, 'Ventas', 'ventas', 'Gestión completa de ventas, cotizaciones y facturas', 'fas fa-shopping-cart', '/modulos/ventas/', 2, NULL, 1),
(3, 'Compras', 'compras', 'Gestión de órdenes de compra y proveedores', 'fas fa-shopping-bag', '/modulos/compras/', 3, NULL, 1),
(4, 'Inventario', 'inventario', 'Control de stock, bodegas y movimientos', 'fas fa-boxes', '/modulos/inventario/', 4, NULL, 1),
(5, 'Contabilidad', 'contabilidad', 'Plan de cuentas, asientos y balances', 'fas fa-calculator', '/modulos/contabilidad/', 5, NULL, 1),
(6, 'Recursos Humanos', 'rrhh', 'Gestión de empleados, contratos y remuneraciones', 'fas fa-users', '/modulos/rrhh/', 6, NULL, 1),
(7, 'CRM', 'crm', 'Gestión de clientes, leads y oportunidades', 'fas fa-handshake', '/modulos/crm/', 7, NULL, 1),
(8, 'Producción', 'produccion', 'Órdenes de producción y control de procesos', 'fas fa-industry', '/modulos/produccion/', 8, NULL, 1),
(9, 'Finanzas', 'finanzas', 'Flujo de caja, bancos y presupuestos', 'fas fa-chart-line', '/modulos/finanzas/', 9, NULL, 1),
(10, 'Logística', 'logistica', 'Transporte, rutas y flotas', 'fas fa-truck', '/modulos/logistica/', 10, NULL, 1),
(11, 'Marketing', 'marketing', 'Campañas, segmentación y automatización', 'fas fa-bullhorn', '/modulos/marketing/', 11, NULL, 1),
(12, 'Business Intelligence', 'bi', 'Dashboards, análisis y reportes avanzados', 'fas fa-chart-pie', '/modulos/bi/', 12, NULL, 1),
(13, 'Configuración Avanzada', 'configuracion', 'Parámetros del sistema y personalizaciones', 'fas fa-sliders-h', '/modulos/config/', 13, NULL, 1),
(14, 'E-Commerce', 'ecommerce', 'Catálogo web, carrito y pagos online', 'fas fa-store', '/modulos/ecommerce/', 14, NULL, 1),
(15, 'Proyectos', 'proyectos', 'Gestión de proyectos, tareas y costos', 'fas fa-project-diagram', '/modulos/proyectos/', 15, NULL, 1),
(16, 'Calidad', 'calidad', 'Inspecciones y control de calidad', 'fas fa-award', '/modulos/calidad/', 16, NULL, 1),
(17, 'Mantenimiento', 'mantenimiento', 'Órdenes de mantenimiento preventivo/correctivo', 'fas fa-tools', '/modulos/mantenimiento/', 17, NULL, 1);

-- ===============================================
-- DATOS INICIALES: PLAN_MODULOS
-- Asignación de módulos por plan
-- ===============================================

-- Plan BÁSICO (6 módulos)
INSERT INTO `plan_modulos` (`plan_id`, `modulo_id`, `acceso_completo`, `limite_registros`) VALUES
(1, 1, 1, NULL),   -- Administración (completo)
(1, 2, 0, 100),    -- Ventas (limitado: 100 facturas/mes)
(1, 3, 0, 50),     -- Compras (limitado: 50 órdenes/mes)
(1, 4, 1, 500),    -- Inventario (500 productos)
(1, 5, 0, NULL),   -- Contabilidad básica (sin cierre contable)
(1, 7, 0, 100);    -- CRM básico (100 clientes)

-- Plan PROFESIONAL (12 módulos)
INSERT INTO `plan_modulos` (`plan_id`, `modulo_id`, `acceso_completo`, `limite_registros`) VALUES
(2, 1, 1, NULL),   -- Administración
(2, 2, 1, NULL),   -- Ventas (completo)
(2, 3, 1, NULL),   -- Compras (completo)
(2, 4, 1, NULL),   -- Inventario (completo)
(2, 5, 1, NULL),   -- Contabilidad (completo)
(2, 6, 1, NULL),   -- RRHH (completo)
(2, 7, 1, NULL),   -- CRM (completo)
(2, 8, 1, NULL),   -- Producción
(2, 9, 1, NULL),   -- Finanzas
(2, 10, 1, NULL),  -- Logística
(2, 11, 1, NULL),  -- Marketing
(2, 12, 0, NULL);  -- BI (limitado: sin ML)

-- Plan EMPRESARIAL (17 módulos - TODOS)
INSERT INTO `plan_modulos` (`plan_id`, `modulo_id`, `acceso_completo`, `limite_registros`)
SELECT 3, id, 1, NULL FROM `modulos` WHERE parent_id IS NULL;

-- Plan PERSONALIZADO (17 módulos - TODOS)
INSERT INTO `plan_modulos` (`plan_id`, `modulo_id`, `acceso_completo`, `limite_registros`)
SELECT 4, id, 1, NULL FROM `modulos` WHERE parent_id IS NULL;

-- ===============================================
-- VISTAS ÚTILES
-- ===============================================

-- Vista: Resumen de planes con módulos incluidos
CREATE OR REPLACE VIEW `v_planes_resumen` AS
SELECT
    p.id,
    p.nombre,
    p.slug,
    p.precio_mensual,
    p.max_usuarios,
    p.max_empresas,
    COUNT(pm.modulo_id) as total_modulos,
    SUM(CASE WHEN pm.acceso_completo = 1 THEN 1 ELSE 0 END) as modulos_completos,
    SUM(CASE WHEN pm.acceso_completo = 0 THEN 1 ELSE 0 END) as modulos_limitados,
    p.activo,
    p.destacado
FROM `planes` p
LEFT JOIN `plan_modulos` pm ON p.id = pm.plan_id
GROUP BY p.id
ORDER BY p.orden;

-- Vista: Módulos con información de acceso por plan
CREATE OR REPLACE VIEW `v_modulos_por_plan` AS
SELECT
    p.id as plan_id,
    p.nombre as plan_nombre,
    m.id as modulo_id,
    m.nombre as modulo_nombre,
    m.slug as modulo_slug,
    m.icono,
    pm.acceso_completo,
    pm.limite_registros,
    m.requiere_licencia
FROM `planes` p
LEFT JOIN `plan_modulos` pm ON p.id = pm.plan_id
LEFT JOIN `modulos` m ON pm.modulo_id = m.id
WHERE m.parent_id IS NULL AND m.activo = 1
ORDER BY p.orden, m.orden;

-- Vista: Estadísticas de pagos por empresa
CREATE OR REPLACE VIEW `v_pagos_estadisticas` AS
SELECT
    e.id as empresa_id,
    e.nombre_empresa,
    COUNT(pg.id) as total_pagos,
    SUM(CASE WHEN pg.estado = 'completado' THEN 1 ELSE 0 END) as pagos_exitosos,
    SUM(CASE WHEN pg.estado = 'fallido' THEN 1 ELSE 0 END) as pagos_fallidos,
    SUM(CASE WHEN pg.estado = 'completado' THEN pg.monto ELSE 0 END) as total_pagado,
    MAX(pg.fecha_pago) as ultimo_pago,
    p.nombre as plan_actual
FROM `empresas` e
LEFT JOIN `pagos` pg ON e.id = pg.empresa_id
LEFT JOIN `suscripciones` s ON e.id = s.empresa_id AND s.estado IN ('trial', 'activa')
LEFT JOIN `planes` p ON s.plan_id = p.id
GROUP BY e.id
ORDER BY total_pagado DESC;

-- ===============================================
-- STORED PROCEDURES
-- ===============================================

-- Procedure: Validar si empresa tiene acceso a módulo
DROP PROCEDURE IF EXISTS `sp_validar_acceso_modulo`;

DELIMITER $$
CREATE PROCEDURE `sp_validar_acceso_modulo`(
    IN p_empresa_id INT,
    IN p_modulo_slug VARCHAR(100),
    OUT p_tiene_acceso INT,
    OUT p_acceso_completo INT,
    OUT p_limite_registros INT,
    OUT p_mensaje VARCHAR(255)
)
BEGIN
    DECLARE v_suscripcion_activa INT DEFAULT 0;
    DECLARE v_plan_id INT;
    DECLARE v_modulo_id INT;
    DECLARE v_requiere_licencia INT;

    -- Inicializar variables de salida
    SET p_tiene_acceso = 0;
    SET p_acceso_completo = 0;
    SET p_limite_registros = NULL;
    SET p_mensaje = '';

    -- 1. Verificar si hay suscripción activa
    SELECT COUNT(*), s.plan_id
    INTO v_suscripcion_activa, v_plan_id
    FROM `suscripciones` s
    WHERE s.empresa_id = p_empresa_id
      AND s.estado IN ('trial', 'activa')
      AND (s.fecha_fin IS NULL OR s.fecha_fin >= NOW())
    LIMIT 1;

    IF v_suscripcion_activa = 0 THEN
        SET p_mensaje = 'Suscripción no activa o expirada';
        LEAVE sp_validar_acceso_modulo;
    END IF;

    -- 2. Verificar si el módulo existe y obtener su ID
    SELECT m.id, m.requiere_licencia
    INTO v_modulo_id, v_requiere_licencia
    FROM `modulos` m
    WHERE m.slug = p_modulo_slug AND m.activo = 1
    LIMIT 1;

    IF v_modulo_id IS NULL THEN
        SET p_mensaje = 'Módulo no encontrado';
        LEAVE sp_validar_acceso_modulo;
    END IF;

    -- 3. Si el módulo no requiere licencia, dar acceso automático
    IF v_requiere_licencia = 0 THEN
        SET p_tiene_acceso = 1;
        SET p_acceso_completo = 1;
        SET p_mensaje = 'Acceso permitido (módulo libre)';
        LEAVE sp_validar_acceso_modulo;
    END IF;

    -- 4. Verificar si el plan incluye este módulo
    SELECT pm.acceso_completo, pm.limite_registros
    INTO p_acceso_completo, p_limite_registros
    FROM `plan_modulos` pm
    WHERE pm.plan_id = v_plan_id
      AND pm.modulo_id = v_modulo_id
    LIMIT 1;

    IF p_acceso_completo IS NULL THEN
        SET p_mensaje = 'Módulo no incluido en el plan actual';
        LEAVE sp_validar_acceso_modulo;
    END IF;

    -- 5. Acceso permitido
    SET p_tiene_acceso = 1;
    SET p_mensaje = 'Acceso permitido';

END$$
DELIMITER ;

-- Procedure: Migrar empresa a otro plan
DROP PROCEDURE IF EXISTS `sp_migrar_plan`;

DELIMITER $$
CREATE PROCEDURE `sp_migrar_plan`(
    IN p_empresa_id INT,
    IN p_nuevo_plan_id INT,
    IN p_usuario_id INT,
    IN p_tipo_cambio VARCHAR(50),
    OUT p_exito INT,
    OUT p_mensaje VARCHAR(255)
)
BEGIN
    DECLARE v_plan_anterior_id INT;
    DECLARE v_suscripcion_id INT;
    DECLARE v_precio_nuevo DECIMAL(10,2);

    -- Inicializar
    SET p_exito = 0;
    SET p_mensaje = '';

    START TRANSACTION;

    -- 1. Obtener suscripción actual
    SELECT s.id, s.plan_id
    INTO v_suscripcion_id, v_plan_anterior_id
    FROM `suscripciones` s
    WHERE s.empresa_id = p_empresa_id
      AND s.estado IN ('trial', 'activa')
    ORDER BY s.id DESC
    LIMIT 1;

    IF v_suscripcion_id IS NULL THEN
        SET p_mensaje = 'No hay suscripción activa';
        ROLLBACK;
        LEAVE sp_migrar_plan;
    END IF;

    -- 2. Obtener precio del nuevo plan
    SELECT precio_mensual INTO v_precio_nuevo
    FROM `planes`
    WHERE id = p_nuevo_plan_id;

    -- 3. Actualizar suscripción
    UPDATE `suscripciones`
    SET plan_id = p_nuevo_plan_id,
        monto_mensual = v_precio_nuevo,
        es_trial = 0,
        estado = 'activa',
        updated_at = NOW()
    WHERE id = v_suscripcion_id;

    -- 4. Registrar cambio de plan
    INSERT INTO `logs_cambio_plan` (empresa_id, usuario_id, plan_anterior_id, plan_nuevo_id, tipo_cambio, created_at)
    VALUES (p_empresa_id, p_usuario_id, v_plan_anterior_id, p_nuevo_plan_id, p_tipo_cambio, NOW());

    COMMIT;

    SET p_exito = 1;
    SET p_mensaje = 'Plan actualizado exitosamente';

END$$
DELIMITER ;

-- ===============================================
-- EVENTS AUTOMÁTICOS
-- ===============================================

-- Event: Verificar suscripciones expiradas (cada hora)
DROP EVENT IF EXISTS `ev_verificar_suscripciones_expiradas`;

DELIMITER $$
CREATE EVENT `ev_verificar_suscripciones_expiradas`
ON SCHEDULE EVERY 1 HOUR
STARTS NOW()
DO
BEGIN
    -- Marcar suscripciones de pago expiradas
    UPDATE `suscripciones`
    SET estado = 'expirada'
    WHERE estado = 'activa'
      AND es_trial = 0
      AND fecha_fin < NOW();

    -- Marcar trials expirados
    UPDATE `suscripciones`
    SET estado = 'expirada'
    WHERE estado = 'trial'
      AND fecha_fin < NOW();
END$$
DELIMITER ;

-- ===============================================
-- TRIGGERS
-- ===============================================

-- Trigger: Registrar log cuando se deniega acceso
DROP TRIGGER IF EXISTS `after_insert_logs_acceso_denegado`;

DELIMITER $$
CREATE TRIGGER `after_insert_logs_acceso_denegado`
AFTER INSERT ON `logs_acceso_denegado`
FOR EACH ROW
BEGIN
    -- Podrías enviar notificación al usuario o admin aquí
    -- Por ahora solo registramos en auditoría
    INSERT INTO `logs_auditoria` (usuario_id, accion, tabla, registro_id, descripcion, ip_address)
    VALUES (NEW.usuario_id, 'ACCESO_DENEGADO', 'logs_acceso_denegado', NEW.id,
            CONCAT('Acceso denegado a módulo: ', NEW.modulo_slug, ' - Razón: ', NEW.razon),
            NEW.ip_address);
END$$
DELIMITER ;

-- ===============================================
-- INDICES ADICIONALES PARA OPTIMIZACIÓN
-- ===============================================
ALTER TABLE `suscripciones`
ADD INDEX `idx_empresa_estado` (`empresa_id`, `estado`),
ADD INDEX `idx_fecha_fin` (`fecha_fin`);

-- ===============================================
-- COMENTARIOS Y DOCUMENTACIÓN
-- ===============================================

/*
SISTEMA DE CONTROL DE ACCESO POR MÓDULOS Y PLANES

Este módulo SQL implementa el control completo de acceso basado en suscripciones.

TABLAS PRINCIPALES:
1. planes: Catálogo de planes (Básico, Profesional, Empresarial, Personalizado)
2. modulos: Los 17 módulos principales del sistema
3. plan_modulos: Relación de qué módulos incluye cada plan
4. pagos: Historial de transacciones de pago
5. logs_acceso_denegado: Auditoría de intentos de acceso bloqueados
6. logs_cambio_plan: Historial de upgrades/downgrades

PLANES INCLUIDOS:
- Básico ($49,990/mes): 5 usuarios, 1 empresa, 6 módulos limitados
- Profesional ($99,990/mes): 25 usuarios, 3 empresas, 12 módulos completos
- Empresarial ($199,990/mes): Ilimitado, 17 módulos (107 submódulos)
- Personalizado: A cotizar, todo personalizado

MÓDULOS POR PLAN:
Plan Básico: Administración, Ventas (limitado), Compras (limitado), Inventario, Contabilidad (básica), CRM (básico)
Plan Profesional: Todos del Básico + RRHH, Producción, Finanzas, Logística, Marketing, BI (limitado)
Plan Empresarial: Todos los 17 módulos con acceso completo

STORED PROCEDURES:
- sp_validar_acceso_modulo: Valida si una empresa puede acceder a un módulo
- sp_migrar_plan: Cambia el plan de una empresa (upgrade/downgrade)

EVENTS:
- ev_verificar_suscripciones_expiradas: Ejecuta cada hora para marcar suscripciones vencidas

VISTAS:
- v_planes_resumen: Resumen de planes con conteo de módulos
- v_modulos_por_plan: Listado de módulos disponibles por plan
- v_pagos_estadisticas: Estadísticas de pagos por empresa

USO DESDE PHP:
// Validar acceso
CALL sp_validar_acceso_modulo(123, 'ventas', @tiene_acceso, @acceso_completo, @limite, @mensaje);
SELECT @tiene_acceso, @acceso_completo, @limite, @mensaje;

// Migrar plan
CALL sp_migrar_plan(123, 2, 456, 'upgrade', @exito, @mensaje);
SELECT @exito, @mensaje;
*/
