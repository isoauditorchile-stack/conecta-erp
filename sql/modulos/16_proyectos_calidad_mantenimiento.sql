-- =====================================================
-- MÓDULO 15: GESTIÓN DE PROYECTOS
-- MÓDULO 16: CONTROL DE CALIDAD
-- MÓDULO 17: MANTENIMIENTO
-- =====================================================

USE conectae_conectaerpbd;

-- =====================================================
-- MÓDULO 15: GESTIÓN DE PROYECTOS (3 submódulos)
-- =====================================================

-- Tabla: Proyectos
CREATE TABLE IF NOT EXISTS `proyectos` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `codigo_proyecto` VARCHAR(50) NOT NULL,
  `nombre` VARCHAR(255) NOT NULL,
  `descripcion` TEXT,
  `cliente_id` INT(11),
  `responsable_id` INT(11) NOT NULL,
  `tipo` ENUM('interno','externo','investigacion','desarrollo') DEFAULT 'externo',
  `estado` ENUM('planificacion','en_curso','en_pausa','completado','cancelado') DEFAULT 'planificacion',
  `prioridad` ENUM('baja','media','alta','urgente') DEFAULT 'media',
  `fecha_inicio` DATE NOT NULL,
  `fecha_fin_estimada` DATE NOT NULL,
  `fecha_fin_real` DATE,
  `presupuesto` DECIMAL(15,2) DEFAULT 0.00,
  `costo_real` DECIMAL(15,2) DEFAULT 0.00,
  `margen` DECIMAL(15,2) GENERATED ALWAYS AS (`presupuesto` - `costo_real`) STORED,
  `porcentaje_avance` DECIMAL(5,2) DEFAULT 0.00,
  `horas_estimadas` DECIMAL(10,2) DEFAULT 0.00,
  `horas_reales` DECIMAL(10,2) DEFAULT 0.00,
  `notas` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_codigo` (`empresa_id`, `codigo_proyecto`),
  KEY `idx_responsable` (`responsable_id`),
  KEY `idx_cliente` (`cliente_id`),
  KEY `idx_estado` (`estado`),
  CONSTRAINT `fk_proyecto_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`),
  CONSTRAINT `fk_proyecto_responsable` FOREIGN KEY (`responsable_id`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `fk_proyecto_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: Tareas de Proyectos
CREATE TABLE IF NOT EXISTS `proyecto_tareas` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `proyecto_id` INT(11) NOT NULL,
  `tarea_padre_id` INT(11) DEFAULT NULL COMMENT 'Para subtareas',
  `nombre` VARCHAR(255) NOT NULL,
  `descripcion` TEXT,
  `asignado_a` INT(11),
  `estado` ENUM('pendiente','en_progreso','revision','completada','cancelada') DEFAULT 'pendiente',
  `prioridad` ENUM('baja','media','alta','urgente') DEFAULT 'media',
  `fecha_inicio` DATE,
  `fecha_fin_estimada` DATE,
  `fecha_fin_real` DATE,
  `horas_estimadas` DECIMAL(10,2) DEFAULT 0.00,
  `horas_trabajadas` DECIMAL(10,2) DEFAULT 0.00,
  `porcentaje_avance` DECIMAL(5,2) DEFAULT 0.00,
  `costo_estimado` DECIMAL(15,2) DEFAULT 0.00,
  `costo_real` DECIMAL(15,2) DEFAULT 0.00,
  `dependencias` TEXT COMMENT 'JSON array de IDs de tareas prerequisito',
  `archivos_adjuntos` TEXT COMMENT 'JSON array de rutas',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_proyecto` (`proyecto_id`),
  KEY `idx_asignado` (`asignado_a`),
  KEY `idx_tarea_padre` (`tarea_padre_id`),
  KEY `idx_estado` (`estado`),
  CONSTRAINT `fk_tarea_proyecto` FOREIGN KEY (`proyecto_id`) REFERENCES `proyectos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tarea_asignado` FOREIGN KEY (`asignado_a`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `fk_tarea_padre` FOREIGN KEY (`tarea_padre_id`) REFERENCES `proyecto_tareas` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: Registro de Horas del Proyecto
CREATE TABLE IF NOT EXISTS `proyecto_horas` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `proyecto_id` INT(11) NOT NULL,
  `tarea_id` INT(11),
  `usuario_id` INT(11) NOT NULL,
  `fecha` DATE NOT NULL,
  `horas` DECIMAL(10,2) NOT NULL,
  `descripcion` TEXT,
  `tipo_actividad` VARCHAR(100),
  `facturable` TINYINT(1) DEFAULT 1,
  `tarifa_hora` DECIMAL(15,2) DEFAULT 0.00,
  `monto` DECIMAL(15,2) GENERATED ALWAYS AS (`horas` * `tarifa_hora`) STORED,
  `aprobado` TINYINT(1) DEFAULT 0,
  `aprobado_por` INT(11),
  `fecha_aprobacion` DATETIME,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_proyecto` (`proyecto_id`),
  KEY `idx_tarea` (`tarea_id`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_fecha` (`fecha`),
  CONSTRAINT `fk_horas_proyecto` FOREIGN KEY (`proyecto_id`) REFERENCES `proyectos` (`id`),
  CONSTRAINT `fk_horas_tarea` FOREIGN KEY (`tarea_id`) REFERENCES `proyecto_tareas` (`id`),
  CONSTRAINT `fk_horas_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: Gastos del Proyecto
CREATE TABLE IF NOT EXISTS `proyecto_gastos` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `proyecto_id` INT(11) NOT NULL,
  `fecha` DATE NOT NULL,
  `concepto` VARCHAR(255) NOT NULL,
  `categoria` VARCHAR(100),
  `monto` DECIMAL(15,2) NOT NULL,
  `proveedor_id` INT(11),
  `factura_referencia` VARCHAR(100),
  `descripcion` TEXT,
  `aprobado` TINYINT(1) DEFAULT 0,
  `aprobado_por` INT(11),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_proyecto` (`proyecto_id`),
  KEY `idx_proveedor` (`proveedor_id`),
  CONSTRAINT `fk_gasto_proyecto` FOREIGN KEY (`proyecto_id`) REFERENCES `proyectos` (`id`),
  CONSTRAINT `fk_gasto_proveedor` FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- MÓDULO 16: CONTROL DE CALIDAD (2 submódulos)
-- =====================================================

-- Tabla: Inspecciones de Calidad
CREATE TABLE IF NOT EXISTS `inspecciones_calidad` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `numero_inspeccion` VARCHAR(50) NOT NULL,
  `tipo` ENUM('materia_prima','proceso','producto_terminado','proveedor') DEFAULT 'producto_terminado',
  `fecha_inspeccion` DATETIME NOT NULL,
  `inspector_id` INT(11) NOT NULL,
  `producto_id` INT(11),
  `lote` VARCHAR(100),
  `cantidad_inspeccionada` DECIMAL(10,2),
  `cantidad_aprobada` DECIMAL(10,2),
  `cantidad_rechazada` DECIMAL(10,2),
  `porcentaje_aprobacion` DECIMAL(5,2) GENERATED ALWAYS AS (
    CASE WHEN `cantidad_inspeccionada` > 0
    THEN (`cantidad_aprobada` / `cantidad_inspeccionada` * 100)
    ELSE 0 END
  ) STORED,
  `resultado` ENUM('aprobado','rechazado','condicional') DEFAULT 'aprobado',
  `observaciones` TEXT,
  `acciones_correctivas` TEXT,
  `norma_aplicada` VARCHAR(100),
  `archivos_adjuntos` TEXT COMMENT 'JSON array de rutas',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_numero` (`empresa_id`, `numero_inspeccion`),
  KEY `idx_inspector` (`inspector_id`),
  KEY `idx_producto` (`producto_id`),
  KEY `idx_resultado` (`resultado`),
  CONSTRAINT `fk_inspeccion_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`),
  CONSTRAINT `fk_inspeccion_inspector` FOREIGN KEY (`inspector_id`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `fk_inspeccion_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: Criterios de Inspección
CREATE TABLE IF NOT EXISTS `inspeccion_criterios` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `inspeccion_id` INT(11) NOT NULL,
  `criterio` VARCHAR(255) NOT NULL,
  `valor_esperado` VARCHAR(255),
  `valor_medido` VARCHAR(255),
  `conforme` TINYINT(1) DEFAULT 1,
  `observaciones` TEXT,
  PRIMARY KEY (`id`),
  KEY `idx_inspeccion` (`inspeccion_id`),
  CONSTRAINT `fk_criterio_inspeccion` FOREIGN KEY (`inspeccion_id`) REFERENCES `inspecciones_calidad` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: No Conformidades
CREATE TABLE IF NOT EXISTS `no_conformidades` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `numero_nc` VARCHAR(50) NOT NULL,
  `tipo` ENUM('producto','proceso','sistema','cliente','proveedor') DEFAULT 'producto',
  `gravedad` ENUM('menor','mayor','critica') DEFAULT 'menor',
  `estado` ENUM('abierta','en_analisis','en_correccion','cerrada','rechazada') DEFAULT 'abierta',
  `fecha_deteccion` DATETIME NOT NULL,
  `detectado_por` INT(11) NOT NULL,
  `area_responsable` VARCHAR(100),
  `responsable_id` INT(11),
  `descripcion` TEXT NOT NULL,
  `causa_raiz` TEXT,
  `accion_inmediata` TEXT,
  `accion_correctiva` TEXT,
  `accion_preventiva` TEXT,
  `fecha_cierre_objetivo` DATE,
  `fecha_cierre_real` DATE,
  `costo_estimado` DECIMAL(15,2) DEFAULT 0.00,
  `costo_real` DECIMAL(15,2) DEFAULT 0.00,
  `verificado_por` INT(11),
  `fecha_verificacion` DATE,
  `eficaz` TINYINT(1) DEFAULT NULL,
  `observaciones` TEXT,
  `archivos_adjuntos` TEXT COMMENT 'JSON array',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_numero_nc` (`empresa_id`, `numero_nc`),
  KEY `idx_estado` (`estado`),
  KEY `idx_gravedad` (`gravedad`),
  KEY `idx_detectado_por` (`detectado_por`),
  KEY `idx_responsable` (`responsable_id`),
  CONSTRAINT `fk_nc_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`),
  CONSTRAINT `fk_nc_detectado` FOREIGN KEY (`detectado_por`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `fk_nc_responsable` FOREIGN KEY (`responsable_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- MÓDULO 17: MANTENIMIENTO (2 submódulos)
-- =====================================================

-- Tabla: Órdenes de Mantenimiento
CREATE TABLE IF NOT EXISTS `ordenes_mantenimiento` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `numero_orden` VARCHAR(50) NOT NULL,
  `tipo` ENUM('preventivo','correctivo','predictivo','mejora') DEFAULT 'preventivo',
  `prioridad` ENUM('baja','media','alta','urgente') DEFAULT 'media',
  `estado` ENUM('programada','en_proceso','completada','cancelada') DEFAULT 'programada',
  `activo_id` INT(11) COMMENT 'Referencia a activos_fijos',
  `equipo` VARCHAR(255) NOT NULL,
  `ubicacion` VARCHAR(255),
  `descripcion_trabajo` TEXT NOT NULL,
  `fecha_programada` DATETIME NOT NULL,
  `fecha_inicio` DATETIME,
  `fecha_fin` DATETIME,
  `duracion_estimada_horas` DECIMAL(10,2) DEFAULT 0.00,
  `duracion_real_horas` DECIMAL(10,2) DEFAULT 0.00,
  `responsable_id` INT(11),
  `tecnico_asignado_id` INT(11),
  `costo_estimado` DECIMAL(15,2) DEFAULT 0.00,
  `costo_mano_obra` DECIMAL(15,2) DEFAULT 0.00,
  `costo_materiales` DECIMAL(15,2) DEFAULT 0.00,
  `costo_total` DECIMAL(15,2) GENERATED ALWAYS AS (`costo_mano_obra` + `costo_materiales`) STORED,
  `trabajo_realizado` TEXT,
  `observaciones` TEXT,
  `requiere_parada` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_numero_orden` (`empresa_id`, `numero_orden`),
  KEY `idx_activo` (`activo_id`),
  KEY `idx_responsable` (`responsable_id`),
  KEY `idx_tecnico` (`tecnico_asignado_id`),
  KEY `idx_estado` (`estado`),
  KEY `idx_tipo` (`tipo`),
  CONSTRAINT `fk_om_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`),
  CONSTRAINT `fk_om_responsable` FOREIGN KEY (`responsable_id`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `fk_om_tecnico` FOREIGN KEY (`tecnico_asignado_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: Materiales Usados en Mantenimiento
CREATE TABLE IF NOT EXISTS `mantenimiento_materiales` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `orden_mantenimiento_id` INT(11) NOT NULL,
  `producto_id` INT(11),
  `descripcion` VARCHAR(255) NOT NULL,
  `cantidad` DECIMAL(10,2) NOT NULL,
  `costo_unitario` DECIMAL(15,2) NOT NULL,
  `costo_total` DECIMAL(15,2) GENERATED ALWAYS AS (`cantidad` * `costo_unitario`) STORED,
  PRIMARY KEY (`id`),
  KEY `idx_orden` (`orden_mantenimiento_id`),
  KEY `idx_producto` (`producto_id`),
  CONSTRAINT `fk_mat_orden` FOREIGN KEY (`orden_mantenimiento_id`) REFERENCES `ordenes_mantenimiento` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_mat_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: Plan de Mantenimiento Preventivo
CREATE TABLE IF NOT EXISTS `plan_mantenimiento_preventivo` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `activo_id` INT(11),
  `equipo` VARCHAR(255) NOT NULL,
  `descripcion` TEXT,
  `frecuencia_tipo` ENUM('diaria','semanal','mensual','trimestral','semestral','anual','horas','kilometros') DEFAULT 'mensual',
  `frecuencia_valor` INT(11) NOT NULL COMMENT 'Cada N días/horas/km',
  `duracion_estimada_horas` DECIMAL(10,2) DEFAULT 0.00,
  `responsable_id` INT(11),
  `procedimiento` TEXT,
  `checklist` TEXT COMMENT 'JSON array de items',
  `materiales_necesarios` TEXT COMMENT 'JSON array',
  `activo` TINYINT(1) DEFAULT 1,
  `ultima_ejecucion` DATETIME,
  `proxima_ejecucion` DATETIME,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_activo` (`activo_id`),
  KEY `idx_responsable` (`responsable_id`),
  KEY `idx_proxima` (`proxima_ejecucion`),
  CONSTRAINT `fk_plan_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`),
  CONSTRAINT `fk_plan_responsable` FOREIGN KEY (`responsable_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- VISTAS
-- =====================================================

-- Vista: Dashboard de Proyectos
CREATE OR REPLACE VIEW `v_dashboard_proyectos` AS
SELECT
  p.empresa_id,
  COUNT(*) as total_proyectos,
  SUM(CASE WHEN p.estado = 'en_curso' THEN 1 ELSE 0 END) as proyectos_activos,
  SUM(CASE WHEN p.estado = 'completado' THEN 1 ELSE 0 END) as proyectos_completados,
  SUM(p.presupuesto) as presupuesto_total,
  SUM(p.costo_real) as costo_real_total,
  SUM(p.margen) as margen_total,
  AVG(p.porcentaje_avance) as avance_promedio,
  SUM(p.horas_estimadas) as horas_estimadas_total,
  SUM(p.horas_reales) as horas_reales_total
FROM proyectos p
WHERE p.estado NOT IN ('cancelado')
GROUP BY p.empresa_id;

-- Vista: Dashboard de Calidad
CREATE OR REPLACE VIEW `v_dashboard_calidad` AS
SELECT
  i.empresa_id,
  COUNT(*) as total_inspecciones,
  SUM(CASE WHEN i.resultado = 'aprobado' THEN 1 ELSE 0 END) as inspecciones_aprobadas,
  SUM(CASE WHEN i.resultado = 'rechazado' THEN 1 ELSE 0 END) as inspecciones_rechazadas,
  AVG(i.porcentaje_aprobacion) as tasa_aprobacion_promedio,
  (SELECT COUNT(*) FROM no_conformidades nc WHERE nc.empresa_id = i.empresa_id AND nc.estado = 'abierta') as nc_abiertas,
  (SELECT COUNT(*) FROM no_conformidades nc WHERE nc.empresa_id = i.empresa_id AND nc.gravedad = 'critica') as nc_criticas
FROM inspecciones_calidad i
WHERE i.fecha_inspeccion >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY i.empresa_id;

-- Vista: Dashboard de Mantenimiento
CREATE OR REPLACE VIEW `v_dashboard_mantenimiento` AS
SELECT
  om.empresa_id,
  COUNT(*) as total_ordenes,
  SUM(CASE WHEN om.tipo = 'preventivo' THEN 1 ELSE 0 END) as preventivas,
  SUM(CASE WHEN om.tipo = 'correctivo' THEN 1 ELSE 0 END) as correctivas,
  SUM(CASE WHEN om.estado = 'completada' THEN 1 ELSE 0 END) as completadas,
  SUM(CASE WHEN om.estado = 'programada' THEN 1 ELSE 0 END) as programadas,
  SUM(om.costo_total) as costo_total_mantenimiento,
  AVG(om.duracion_real_horas) as duracion_promedio_horas
FROM ordenes_mantenimiento om
WHERE om.fecha_programada >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY om.empresa_id;

-- =====================================================
-- TRIGGERS
-- =====================================================

-- Trigger: Auto-numeración de proyectos
DELIMITER $$
CREATE TRIGGER `tr_proyecto_numero` BEFORE INSERT ON `proyectos`
FOR EACH ROW
BEGIN
  DECLARE siguiente INT;
  SELECT COALESCE(MAX(CAST(SUBSTRING(codigo_proyecto, 4) AS UNSIGNED)), 0) + 1
  INTO siguiente
  FROM proyectos
  WHERE empresa_id = NEW.empresa_id;
  SET NEW.codigo_proyecto = CONCAT('PRY', LPAD(siguiente, 6, '0'));
END$$
DELIMITER ;

-- Trigger: Actualizar horas reales del proyecto
DELIMITER $$
CREATE TRIGGER `tr_actualizar_horas_proyecto` AFTER INSERT ON `proyecto_horas`
FOR EACH ROW
BEGIN
  UPDATE proyectos
  SET horas_reales = (
    SELECT COALESCE(SUM(horas), 0)
    FROM proyecto_horas
    WHERE proyecto_id = NEW.proyecto_id
  )
  WHERE id = NEW.proyecto_id;
END$$
DELIMITER ;

-- Trigger: Auto-numeración de inspecciones
DELIMITER $$
CREATE TRIGGER `tr_inspeccion_numero` BEFORE INSERT ON `inspecciones_calidad`
FOR EACH ROW
BEGIN
  DECLARE siguiente INT;
  SELECT COALESCE(MAX(CAST(SUBSTRING(numero_inspeccion, 4) AS UNSIGNED)), 0) + 1
  INTO siguiente
  FROM inspecciones_calidad
  WHERE empresa_id = NEW.empresa_id;
  SET NEW.numero_inspeccion = CONCAT('INS', LPAD(siguiente, 6, '0'));
END$$
DELIMITER ;

-- Trigger: Auto-numeración de no conformidades
DELIMITER $$
CREATE TRIGGER `tr_nc_numero` BEFORE INSERT ON `no_conformidades`
FOR EACH ROW
BEGIN
  DECLARE siguiente INT;
  SELECT COALESCE(MAX(CAST(SUBSTRING(numero_nc, 3) AS UNSIGNED)), 0) + 1
  INTO siguiente
  FROM no_conformidades
  WHERE empresa_id = NEW.empresa_id;
  SET NEW.numero_nc = CONCAT('NC', LPAD(siguiente, 6, '0'));
END$$
DELIMITER ;

-- Trigger: Auto-numeración de órdenes de mantenimiento
DELIMITER $$
CREATE TRIGGER `tr_om_numero` BEFORE INSERT ON `ordenes_mantenimiento`
FOR EACH ROW
BEGIN
  DECLARE siguiente INT;
  SELECT COALESCE(MAX(CAST(SUBSTRING(numero_orden, 3) AS UNSIGNED)), 0) + 1
  INTO siguiente
  FROM ordenes_mantenimiento
  WHERE empresa_id = NEW.empresa_id;
  SET NEW.numero_orden = CONCAT('OM', LPAD(siguiente, 6, '0'));
END$$
DELIMITER ;

-- =====================================================
-- EVENTOS PROGRAMADOS
-- =====================================================

-- Evento: Generar órdenes de mantenimiento preventivo
DELIMITER $$
CREATE EVENT IF NOT EXISTS `ev_generar_mantenimiento_preventivo`
ON SCHEDULE EVERY 1 DAY
STARTS '2025-01-01 06:00:00'
DO
BEGIN
  INSERT INTO ordenes_mantenimiento (empresa_id, tipo, equipo, descripcion_trabajo, fecha_programada, activo_id, responsable_id)
  SELECT
    pmp.empresa_id,
    'preventivo',
    pmp.equipo,
    pmp.descripcion,
    pmp.proxima_ejecucion,
    pmp.activo_id,
    pmp.responsable_id
  FROM plan_mantenimiento_preventivo pmp
  WHERE pmp.activo = 1
    AND pmp.proxima_ejecucion <= DATE_ADD(NOW(), INTERVAL 7 DAY)
    AND pmp.proxima_ejecucion >= NOW()
    AND NOT EXISTS (
      SELECT 1 FROM ordenes_mantenimiento om
      WHERE om.activo_id = pmp.activo_id
      AND om.tipo = 'preventivo'
      AND om.estado IN ('programada', 'en_proceso')
    );
END$$
DELIMITER ;
