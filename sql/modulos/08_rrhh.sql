-- =====================================================
-- MÓDULO 8: RRHH (HCM) - CONECTA ERP
-- Human Capital Management
-- =====================================================

USE conectae_conectaerpbd;

-- =====================================================
-- TABLA: nomina
-- Nóminas de pago mensuales
-- =====================================================
CREATE TABLE IF NOT EXISTS `nomina` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `empleado_id` INT(11) NOT NULL,
  `periodo` DATE NOT NULL COMMENT 'Primer día del mes',
  `salario_base` DECIMAL(15,2) NOT NULL,
  `horas_extras` DECIMAL(10,2) DEFAULT 0.00,
  `bonos` DECIMAL(15,2) DEFAULT 0.00,
  `descuentos` DECIMAL(15,2) DEFAULT 0.00,
  `afp` DECIMAL(15,2) DEFAULT 0.00,
  `salud` DECIMAL(15,2) DEFAULT 0.00,
  `liquido` DECIMAL(15,2) NOT NULL,
  `estado` ENUM('borrador','calculada','pagada','anulada') DEFAULT 'borrador',
  `fecha_pago` DATE DEFAULT NULL,
  `fecha_creacion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `empleado_periodo` (`empleado_id`, `periodo`),
  KEY `idx_empresa` (`empresa_id`),
  KEY `idx_periodo` (`periodo`),
  CONSTRAINT `fk_nomina_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_nomina_empleado` FOREIGN KEY (`empleado_id`) REFERENCES `empleados` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: asistencia
-- Control de asistencia y marcas
-- =====================================================
CREATE TABLE IF NOT EXISTS `asistencia` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `empleado_id` INT(11) NOT NULL,
  `fecha` DATE NOT NULL,
  `hora_entrada` TIME DEFAULT NULL,
  `hora_salida` TIME DEFAULT NULL,
  `horas_trabajadas` DECIMAL(5,2) GENERATED ALWAYS AS (TIMESTAMPDIFF(MINUTE, CONCAT(fecha, ' ', hora_entrada), CONCAT(fecha, ' ', hora_salida)) / 60) STORED,
  `tipo` ENUM('normal','extra','falta','permiso','licencia') DEFAULT 'normal',
  `observaciones` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `empleado_fecha` (`empleado_id`, `fecha`),
  KEY `idx_empresa` (`empresa_id`),
  KEY `idx_fecha` (`fecha`),
  CONSTRAINT `fk_asistencia_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_asistencia_empleado` FOREIGN KEY (`empleado_id`) REFERENCES `empleados` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: vacaciones
-- Solicitudes y registro de vacaciones
-- =====================================================
CREATE TABLE IF NOT EXISTS `vacaciones` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `empleado_id` INT(11) NOT NULL,
  `fecha_desde` DATE NOT NULL,
  `fecha_hasta` DATE NOT NULL,
  `dias_solicitados` INT(3) NOT NULL,
  `dias_disponibles` INT(3) DEFAULT 15,
  `estado` ENUM('solicitada','aprobada','rechazada','cancelada') DEFAULT 'solicitada',
  `aprobado_por` INT(11) DEFAULT NULL,
  `fecha_solicitud` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_aprobacion` TIMESTAMP NULL DEFAULT NULL,
  `observaciones` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_empresa` (`empresa_id`),
  KEY `idx_empleado` (`empleado_id`),
  KEY `idx_estado` (`estado`),
  CONSTRAINT `fk_vacaciones_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_vacaciones_empleado` FOREIGN KEY (`empleado_id`) REFERENCES `empleados` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: capacitaciones
-- Registro de capacitaciones
-- =====================================================
CREATE TABLE IF NOT EXISTS `capacitaciones` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `nombre` VARCHAR(255) NOT NULL,
  `descripcion` TEXT DEFAULT NULL,
  `fecha_inicio` DATE NOT NULL,
  `fecha_fin` DATE NOT NULL,
  `horas` INT(3) DEFAULT 0,
  `instructor` VARCHAR(255) DEFAULT NULL,
  `costo` DECIMAL(15,2) DEFAULT 0.00,
  `estado` ENUM('planificada','en_curso','completada','cancelada') DEFAULT 'planificada',
  PRIMARY KEY (`id`),
  KEY `idx_empresa` (`empresa_id`),
  CONSTRAINT `fk_capacitaciones_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: capacitacion_asistentes
-- Empleados inscritos en capacitaciones
-- =====================================================
CREATE TABLE IF NOT EXISTS `capacitacion_asistentes` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `capacitacion_id` INT(11) NOT NULL,
  `empleado_id` INT(11) NOT NULL,
  `asistio` TINYINT(1) DEFAULT 1,
  `nota` DECIMAL(3,1) DEFAULT NULL,
  `aprobado` TINYINT(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `capacitacion_empleado` (`capacitacion_id`, `empleado_id`),
  CONSTRAINT `fk_cap_asist_capacitacion` FOREIGN KEY (`capacitacion_id`) REFERENCES `capacitaciones` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cap_asist_empleado` FOREIGN KEY (`empleado_id`) REFERENCES `empleados` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: evaluaciones_desempeno
-- Evaluaciones de desempeño
-- =====================================================
CREATE TABLE IF NOT EXISTS `evaluaciones_desempeno` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `empleado_id` INT(11) NOT NULL,
  `evaluador_id` INT(11) NOT NULL,
  `periodo` VARCHAR(10) NOT NULL COMMENT 'Ej: 2025-Q1',
  `puntaje_total` DECIMAL(5,2) DEFAULT 0.00,
  `comentarios` TEXT DEFAULT NULL,
  `fecha_evaluacion` DATE NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `empleado_periodo` (`empleado_id`, `periodo`),
  KEY `idx_empresa` (`empresa_id`),
  CONSTRAINT `fk_eval_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_eval_empleado` FOREIGN KEY (`empleado_id`) REFERENCES `empleados` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- VISTA: Resumen nómina por mes
-- =====================================================
CREATE OR REPLACE VIEW v_nomina_resumen AS
SELECT
    n.empresa_id,
    DATE_FORMAT(n.periodo, '%Y-%m') as periodo,
    COUNT(DISTINCT n.empleado_id) as total_empleados,
    SUM(n.salario_base) as total_salarios,
    SUM(n.bonos) as total_bonos,
    SUM(n.descuentos) as total_descuentos,
    SUM(n.liquido) as total_liquido
FROM nomina n
GROUP BY n.empresa_id, periodo;

FLUSH PRIVILEGES;
