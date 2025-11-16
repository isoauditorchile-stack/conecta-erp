-- =====================================================
-- MÓDULO 18: SISTEMA DE RELOJ CONTROL (Time Clock)
-- Control de asistencia y horarios de empleados
-- Integrado con módulo RRHH
-- =====================================================

USE conectae_conectaerpbd;

-- =====================================================
-- TABLAS PRINCIPALES
-- =====================================================

-- Tabla: Dispositivos de Reloj Control
CREATE TABLE IF NOT EXISTS `reloj_dispositivos` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `codigo` VARCHAR(50) NOT NULL,
  `nombre` VARCHAR(255) NOT NULL,
  `ubicacion` VARCHAR(255),
  `tipo` ENUM('biometrico','rfid','pin','web','mobile') DEFAULT 'biometrico',
  `ip_address` VARCHAR(50),
  `puerto` INT(11),
  `marca` VARCHAR(100) COMMENT 'ZKTeco, HID, Suprema, etc',
  `modelo` VARCHAR(100),
  `numero_serie` VARCHAR(100),
  `activo` TINYINT(1) DEFAULT 1,
  `ultima_sincronizacion` DATETIME,
  `configuracion_json` TEXT COMMENT 'Configuración específica del dispositivo',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_codigo` (`empresa_id`, `codigo`),
  KEY `idx_activo` (`activo`),
  CONSTRAINT `fk_dispositivo_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: Configuración de Horarios de Trabajo
CREATE TABLE IF NOT EXISTS `horarios_trabajo` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `nombre` VARCHAR(255) NOT NULL,
  `descripcion` TEXT,
  `hora_entrada` TIME NOT NULL COMMENT 'HH:MM:SS',
  `hora_salida` TIME NOT NULL COMMENT 'HH:MM:SS',
  `tolerancia_entrada_minutos` INT(11) DEFAULT 0,
  `tolerancia_salida_minutos` INT(11) DEFAULT 0,
  `horas_jornada` DECIMAL(5,2) GENERATED ALWAYS AS (
    TIMESTAMPDIFF(MINUTE, hora_entrada, hora_salida) / 60
  ) STORED,
  `lunes` TINYINT(1) DEFAULT 1,
  `martes` TINYINT(1) DEFAULT 1,
  `miercoles` TINYINT(1) DEFAULT 1,
  `jueves` TINYINT(1) DEFAULT 1,
  `viernes` TINYINT(1) DEFAULT 1,
  `sabado` TINYINT(1) DEFAULT 0,
  `domingo` TINYINT(1) DEFAULT 0,
  `hora_colacion_inicio` TIME,
  `hora_colacion_fin` TIME,
  `activo` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_empresa` (`empresa_id`),
  CONSTRAINT `fk_horario_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: Asignación de Horarios a Empleados
CREATE TABLE IF NOT EXISTS `empleado_horarios` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empleado_id` INT(11) NOT NULL,
  `horario_id` INT(11) NOT NULL,
  `fecha_inicio` DATE NOT NULL,
  `fecha_fin` DATE,
  `activo` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_empleado` (`empleado_id`),
  KEY `idx_horario` (`horario_id`),
  KEY `idx_activo` (`activo`),
  CONSTRAINT `fk_emp_horario_empleado` FOREIGN KEY (`empleado_id`) REFERENCES `empleados` (`id`),
  CONSTRAINT `fk_emp_horario_horario` FOREIGN KEY (`horario_id`) REFERENCES `horarios_trabajo` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: Marcajes de Asistencia (PRINCIPAL)
CREATE TABLE IF NOT EXISTS `asistencia_marcajes` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `empleado_id` INT(11) NOT NULL,
  `dispositivo_id` INT(11),
  `fecha` DATE NOT NULL,
  `hora` TIME(0) NOT NULL COMMENT 'Precisión de segundos HH:MM:SS',
  `timestamp_marca` DATETIME(0) NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora exacta con segundos',
  `tipo` ENUM('entrada','salida','colacion_inicio','colacion_fin') NOT NULL,
  `metodo` ENUM('biometrico','rfid','pin','web','mobile','manual') DEFAULT 'biometrico',
  `verificado` TINYINT(1) DEFAULT 1,
  `latitud` DECIMAL(10,8) COMMENT 'Para marcajes móviles',
  `longitud` DECIMAL(11,8) COMMENT 'Para marcajes móviles',
  `observaciones` TEXT,
  `foto_captura` VARCHAR(500) COMMENT 'Ruta a foto de verificación',
  `ip_origen` VARCHAR(50),
  `registrado_por` INT(11) COMMENT 'Usuario que registró manualmente',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_empleado_fecha` (`empleado_id`, `fecha`),
  KEY `idx_fecha` (`fecha`),
  KEY `idx_dispositivo` (`dispositivo_id`),
  KEY `idx_tipo` (`tipo`),
  KEY `idx_timestamp` (`timestamp_marca`),
  CONSTRAINT `fk_marcaje_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`),
  CONSTRAINT `fk_marcaje_empleado` FOREIGN KEY (`empleado_id`) REFERENCES `empleados` (`id`),
  CONSTRAINT `fk_marcaje_dispositivo` FOREIGN KEY (`dispositivo_id`) REFERENCES `reloj_dispositivos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: Resumen Diario de Asistencia
CREATE TABLE IF NOT EXISTS `asistencia_resumen_diario` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `empleado_id` INT(11) NOT NULL,
  `fecha` DATE NOT NULL,
  `horario_id` INT(11),
  `hora_entrada_programada` TIME,
  `hora_salida_programada` TIME,
  `hora_entrada_real` TIME(0),
  `hora_salida_real` TIME(0),
  `minutos_tarde_entrada` INT(11) DEFAULT 0,
  `minutos_tarde_salida` INT(11) DEFAULT 0,
  `horas_trabajadas` DECIMAL(5,2) DEFAULT 0.00,
  `horas_extras` DECIMAL(5,2) DEFAULT 0.00,
  `estado` ENUM('presente','ausente','tarde','falta','justificado','permiso','vacaciones','licencia_medica') DEFAULT 'presente',
  `observaciones` TEXT,
  `aprobado_por` INT(11),
  `fecha_aprobacion` DATETIME,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_empleado_fecha` (`empleado_id`, `fecha`),
  KEY `idx_empresa_fecha` (`empresa_id`, `fecha`),
  KEY `idx_estado` (`estado`),
  CONSTRAINT `fk_resumen_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`),
  CONSTRAINT `fk_resumen_empleado` FOREIGN KEY (`empleado_id`) REFERENCES `empleados` (`id`),
  CONSTRAINT `fk_resumen_horario` FOREIGN KEY (`horario_id`) REFERENCES `horarios_trabajo` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: Justificaciones y Permisos
CREATE TABLE IF NOT EXISTS `asistencia_justificaciones` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `empleado_id` INT(11) NOT NULL,
  `fecha_inicio` DATE NOT NULL,
  `fecha_fin` DATE NOT NULL,
  `tipo` ENUM('permiso','vacaciones','licencia_medica','falta_justificada','capacitacion','otro') NOT NULL,
  `motivo` TEXT NOT NULL,
  `documento_adjunto` VARCHAR(500),
  `aprobado` TINYINT(1) DEFAULT 0,
  `aprobado_por` INT(11),
  `fecha_aprobacion` DATETIME,
  `observaciones` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_empleado_fecha` (`empleado_id`, `fecha_inicio`),
  KEY `idx_tipo` (`tipo`),
  KEY `idx_aprobado` (`aprobado`),
  CONSTRAINT `fk_just_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`),
  CONSTRAINT `fk_just_empleado` FOREIGN KEY (`empleado_id`) REFERENCES `empleados` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: Alertas de Asistencia
CREATE TABLE IF NOT EXISTS `asistencia_alertas` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `empleado_id` INT(11) NOT NULL,
  `fecha` DATE NOT NULL,
  `tipo_alerta` ENUM('ausencia','retraso','salida_temprana','horas_extras_excesivas','falta_marcaje_salida') NOT NULL,
  `gravedad` ENUM('info','warning','error') DEFAULT 'warning',
  `mensaje` TEXT,
  `leida` TINYINT(1) DEFAULT 0,
  `leida_por` INT(11),
  `fecha_lectura` DATETIME,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_empresa_fecha` (`empresa_id`, `fecha`),
  KEY `idx_empleado` (`empleado_id`),
  KEY `idx_leida` (`leida`),
  CONSTRAINT `fk_alerta_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`),
  CONSTRAINT `fk_alerta_empleado` FOREIGN KEY (`empleado_id`) REFERENCES `empleados` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- VISTAS PARA REPORTES
-- =====================================================

-- Vista: Reporte Completo de Asistencia Diaria
CREATE OR REPLACE VIEW `v_reporte_asistencia_diaria` AS
SELECT
  e.empresa_id,
  e.rut,
  e.nombres,
  e.apellidos,
  CONCAT(e.nombres, ' ', e.apellidos) as nombre_completo,
  e.cargo,
  d.nombre as departamento,
  ard.fecha,
  ard.hora_entrada_programada,
  ard.hora_salida_programada,
  ard.hora_entrada_real,
  ard.hora_salida_real,
  ard.minutos_tarde_entrada,
  ard.minutos_tarde_salida,
  ard.horas_trabajadas,
  ard.horas_extras,
  ard.estado,
  ard.observaciones,
  h.nombre as horario_nombre,
  -- Marcaje de entrada
  (SELECT TIME(timestamp_marca) FROM asistencia_marcajes
   WHERE empleado_id = e.id AND fecha = ard.fecha AND tipo = 'entrada'
   ORDER BY timestamp_marca ASC LIMIT 1) as marca_entrada_exacta,
  -- Marcaje de salida
  (SELECT TIME(timestamp_marca) FROM asistencia_marcajes
   WHERE empleado_id = e.id AND fecha = ard.fecha AND tipo = 'salida'
   ORDER BY timestamp_marca DESC LIMIT 1) as marca_salida_exacta
FROM asistencia_resumen_diario ard
INNER JOIN empleados e ON ard.empleado_id = e.id
LEFT JOIN departamentos d ON e.departamento_id = d.id
LEFT JOIN horarios_trabajo h ON ard.horario_id = h.id
ORDER BY ard.fecha DESC, e.apellidos, e.nombres;

-- Vista: Marcajes del Día
CREATE OR REPLACE VIEW `v_marcajes_hoy` AS
SELECT
  e.rut,
  CONCAT(e.nombres, ' ', e.apellidos) as nombre_completo,
  e.cargo,
  am.fecha,
  TIME(am.timestamp_marca) as hora_marcaje,
  am.tipo,
  am.metodo,
  rd.nombre as dispositivo,
  rd.ubicacion as ubicacion_dispositivo,
  am.verificado
FROM asistencia_marcajes am
INNER JOIN empleados e ON am.empleado_id = e.id
LEFT JOIN reloj_dispositivos rd ON am.dispositivo_id = rd.id
WHERE am.fecha = CURDATE()
ORDER BY am.timestamp_marca DESC;

-- Vista: Estadísticas Mensuales por Empleado
CREATE OR REPLACE VIEW `v_estadisticas_asistencia_mensual` AS
SELECT
  e.empresa_id,
  e.id as empleado_id,
  e.rut,
  CONCAT(e.nombres, ' ', e.apellidos) as nombre_completo,
  e.cargo,
  YEAR(ard.fecha) as anio,
  MONTH(ard.fecha) as mes,
  COUNT(*) as dias_trabajados,
  SUM(CASE WHEN ard.estado = 'presente' THEN 1 ELSE 0 END) as dias_presente,
  SUM(CASE WHEN ard.estado = 'tarde' THEN 1 ELSE 0 END) as dias_tarde,
  SUM(CASE WHEN ard.estado = 'ausente' OR ard.estado = 'falta' THEN 1 ELSE 0 END) as dias_ausente,
  SUM(ard.minutos_tarde_entrada) as total_minutos_tarde,
  SUM(ard.horas_trabajadas) as total_horas_trabajadas,
  SUM(ard.horas_extras) as total_horas_extras,
  AVG(ard.horas_trabajadas) as promedio_horas_diarias
FROM asistencia_resumen_diario ard
INNER JOIN empleados e ON ard.empleado_id = e.id
GROUP BY e.empresa_id, e.id, YEAR(ard.fecha), MONTH(ard.fecha);

-- Vista: Empleados sin marcaje de salida
CREATE OR REPLACE VIEW `v_empleados_sin_salida` AS
SELECT
  e.empresa_id,
  e.id as empleado_id,
  e.rut,
  CONCAT(e.nombres, ' ', e.apellidos) as nombre_completo,
  e.cargo,
  am_entrada.fecha,
  TIME(am_entrada.timestamp_marca) as hora_entrada,
  rd.nombre as dispositivo_entrada
FROM asistencia_marcajes am_entrada
INNER JOIN empleados e ON am_entrada.empleado_id = e.id
LEFT JOIN reloj_dispositivos rd ON am_entrada.dispositivo_id = rd.id
WHERE am_entrada.tipo = 'entrada'
  AND am_entrada.fecha = CURDATE()
  AND NOT EXISTS (
    SELECT 1 FROM asistencia_marcajes am_salida
    WHERE am_salida.empleado_id = am_entrada.empleado_id
      AND am_salida.fecha = am_entrada.fecha
      AND am_salida.tipo = 'salida'
  )
ORDER BY am_entrada.timestamp_marca;

-- =====================================================
-- PROCEDIMIENTOS ALMACENADOS
-- =====================================================

-- Procedimiento: Generar resumen diario de asistencia
DELIMITER $$
CREATE PROCEDURE `sp_generar_resumen_asistencia`(
  IN p_empresa_id INT,
  IN p_fecha DATE
)
BEGIN
  DECLARE v_empleado_id INT;
  DECLARE v_horario_id INT;
  DECLARE v_hora_entrada_prog TIME;
  DECLARE v_hora_salida_prog TIME;
  DECLARE v_tolerancia_entrada INT;
  DECLARE v_hora_entrada_real TIME;
  DECLARE v_hora_salida_real TIME;
  DECLARE v_minutos_tarde INT;
  DECLARE v_horas_trabajadas DECIMAL(5,2);
  DECLARE v_estado VARCHAR(50);
  DECLARE done INT DEFAULT FALSE;

  DECLARE cur CURSOR FOR
    SELECT e.id, eh.horario_id
    FROM empleados e
    INNER JOIN empleado_horarios eh ON e.id = eh.empleado_id
    WHERE e.empresa_id = p_empresa_id
      AND eh.activo = 1
      AND eh.fecha_inicio <= p_fecha
      AND (eh.fecha_fin IS NULL OR eh.fecha_fin >= p_fecha);

  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

  OPEN cur;

  read_loop: LOOP
    FETCH cur INTO v_empleado_id, v_horario_id;
    IF done THEN
      LEAVE read_loop;
    END IF;

    -- Obtener configuración de horario
    SELECT hora_entrada, hora_salida, tolerancia_entrada_minutos
    INTO v_hora_entrada_prog, v_hora_salida_prog, v_tolerancia_entrada
    FROM horarios_trabajo
    WHERE id = v_horario_id;

    -- Obtener marcajes reales
    SELECT TIME(MIN(timestamp_marca))
    INTO v_hora_entrada_real
    FROM asistencia_marcajes
    WHERE empleado_id = v_empleado_id
      AND fecha = p_fecha
      AND tipo = 'entrada';

    SELECT TIME(MAX(timestamp_marca))
    INTO v_hora_salida_real
    FROM asistencia_marcajes
    WHERE empleado_id = v_empleado_id
      AND fecha = p_fecha
      AND tipo = 'salida';

    -- Calcular estado
    IF v_hora_entrada_real IS NULL THEN
      SET v_estado = 'ausente';
      SET v_minutos_tarde = 0;
      SET v_horas_trabajadas = 0;
    ELSE
      SET v_minutos_tarde = TIMESTAMPDIFF(MINUTE, v_hora_entrada_prog, v_hora_entrada_real);
      IF v_minutos_tarde < 0 THEN SET v_minutos_tarde = 0; END IF;

      IF v_minutos_tarde > v_tolerancia_entrada THEN
        SET v_estado = 'tarde';
      ELSE
        SET v_estado = 'presente';
      END IF;

      IF v_hora_salida_real IS NOT NULL THEN
        SET v_horas_trabajadas = TIMESTAMPDIFF(MINUTE, v_hora_entrada_real, v_hora_salida_real) / 60;
      ELSE
        SET v_horas_trabajadas = 0;
      END IF;
    END IF;

    -- Insertar o actualizar resumen
    INSERT INTO asistencia_resumen_diario (
      empresa_id, empleado_id, fecha, horario_id,
      hora_entrada_programada, hora_salida_programada,
      hora_entrada_real, hora_salida_real,
      minutos_tarde_entrada, horas_trabajadas, estado
    ) VALUES (
      p_empresa_id, v_empleado_id, p_fecha, v_horario_id,
      v_hora_entrada_prog, v_hora_salida_prog,
      v_hora_entrada_real, v_hora_salida_real,
      v_minutos_tarde, v_horas_trabajadas, v_estado
    )
    ON DUPLICATE KEY UPDATE
      hora_entrada_real = v_hora_entrada_real,
      hora_salida_real = v_hora_salida_real,
      minutos_tarde_entrada = v_minutos_tarde,
      horas_trabajadas = v_horas_trabajadas,
      estado = v_estado;

  END LOOP;

  CLOSE cur;
END$$
DELIMITER ;

-- Procedimiento: Exportar reporte de asistencia (preparado para Excel)
DELIMITER $$
CREATE PROCEDURE `sp_reporte_asistencia_excel`(
  IN p_empresa_id INT,
  IN p_fecha_inicio DATE,
  IN p_fecha_fin DATE
)
BEGIN
  SELECT
    e.rut as 'RUT',
    CONCAT(e.nombres, ' ', e.apellidos) as 'NOMBRE_COMPLETO',
    e.cargo as 'CARGO',
    d.nombre as 'DEPARTAMENTO',
    ard.fecha as 'FECHA',
    DAYNAME(ard.fecha) as 'DIA_SEMANA',
    TIME_FORMAT(ard.hora_entrada_real, '%H:%i:%s') as 'HORA_ENTRADA',
    TIME_FORMAT(ard.hora_salida_real, '%H:%i:%s') as 'HORA_SALIDA',
    ard.horas_trabajadas as 'HORAS_TRABAJADAS',
    ard.horas_extras as 'HORAS_EXTRAS',
    ard.minutos_tarde_entrada as 'MINUTOS_TARDE',
    ard.estado as 'ESTADO'
  FROM asistencia_resumen_diario ard
  INNER JOIN empleados e ON ard.empleado_id = e.id
  LEFT JOIN departamentos d ON e.departamento_id = d.id
  WHERE ard.empresa_id = p_empresa_id
    AND ard.fecha BETWEEN p_fecha_inicio AND p_fecha_fin
  ORDER BY ard.fecha, e.apellidos, e.nombres;
END$$
DELIMITER ;

-- =====================================================
-- TRIGGERS
-- =====================================================

-- Trigger: Actualizar timestamp de marca al insertar
DELIMITER $$
CREATE TRIGGER `tr_marcaje_timestamp` BEFORE INSERT ON `asistencia_marcajes`
FOR EACH ROW
BEGIN
  IF NEW.timestamp_marca IS NULL OR NEW.timestamp_marca = '0000-00-00 00:00:00' THEN
    SET NEW.timestamp_marca = CONCAT(NEW.fecha, ' ', NEW.hora);
  END IF;
END$$
DELIMITER ;

-- Trigger: Generar alerta si empleado llega tarde
DELIMITER $$
CREATE TRIGGER `tr_alerta_retraso` AFTER INSERT ON `asistencia_marcajes`
FOR EACH ROW
BEGIN
  DECLARE v_hora_entrada_prog TIME;
  DECLARE v_tolerancia INT;
  DECLARE v_minutos_tarde INT;

  IF NEW.tipo = 'entrada' THEN
    -- Obtener horario programado
    SELECT h.hora_entrada, h.tolerancia_entrada_minutos
    INTO v_hora_entrada_prog, v_tolerancia
    FROM empleado_horarios eh
    INNER JOIN horarios_trabajo h ON eh.horario_id = h.id
    WHERE eh.empleado_id = NEW.empleado_id
      AND eh.activo = 1
      AND eh.fecha_inicio <= NEW.fecha
      AND (eh.fecha_fin IS NULL OR eh.fecha_fin >= NEW.fecha)
    LIMIT 1;

    IF v_hora_entrada_prog IS NOT NULL THEN
      SET v_minutos_tarde = TIMESTAMPDIFF(MINUTE, v_hora_entrada_prog, NEW.hora);

      IF v_minutos_tarde > v_tolerancia THEN
        INSERT INTO asistencia_alertas (empresa_id, empleado_id, fecha, tipo_alerta, gravedad, mensaje)
        VALUES (NEW.empresa_id, NEW.empleado_id, NEW.fecha, 'retraso', 'warning',
          CONCAT('Empleado llegó ', v_minutos_tarde, ' minutos tarde'));
      END IF;
    END IF;
  END IF;
END$$
DELIMITER ;

-- =====================================================
-- EVENTOS PROGRAMADOS
-- =====================================================

-- Evento: Generar resumen diario de asistencia (diario a las 23:00)
DELIMITER $$
CREATE EVENT IF NOT EXISTS `ev_generar_resumen_asistencia_diario`
ON SCHEDULE EVERY 1 DAY
STARTS CONCAT(CURDATE(), ' 23:00:00')
DO
BEGIN
  DECLARE done INT DEFAULT FALSE;
  DECLARE v_empresa_id INT;
  DECLARE cur CURSOR FOR SELECT id FROM empresas WHERE activo = 1;
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

  OPEN cur;

  read_loop: LOOP
    FETCH cur INTO v_empresa_id;
    IF done THEN
      LEAVE read_loop;
    END IF;

    CALL sp_generar_resumen_asistencia(v_empresa_id, CURDATE());
  END LOOP;

  CLOSE cur;
END$$
DELIMITER ;

-- Evento: Detectar empleados sin marcaje de salida (diario a las 20:00)
DELIMITER $$
CREATE EVENT IF NOT EXISTS `ev_alerta_sin_marcaje_salida`
ON SCHEDULE EVERY 1 DAY
STARTS CONCAT(CURDATE(), ' 20:00:00')
DO
BEGIN
  INSERT INTO asistencia_alertas (empresa_id, empleado_id, fecha, tipo_alerta, gravedad, mensaje)
  SELECT
    e.empresa_id,
    e.id,
    CURDATE(),
    'falta_marcaje_salida',
    'warning',
    'Empleado no ha marcado salida'
  FROM asistencia_marcajes am
  INNER JOIN empleados e ON am.empleado_id = e.id
  WHERE am.tipo = 'entrada'
    AND am.fecha = CURDATE()
    AND NOT EXISTS (
      SELECT 1 FROM asistencia_marcajes am2
      WHERE am2.empleado_id = am.empleado_id
        AND am2.fecha = am.fecha
        AND am2.tipo = 'salida'
    );
END$$
DELIMITER ;

-- =====================================================
-- DATOS INICIALES
-- =====================================================

-- Insertar horarios de trabajo predeterminados
INSERT INTO `horarios_trabajo` (`empresa_id`, `nombre`, `descripcion`, `hora_entrada`, `hora_salida`, `tolerancia_entrada_minutos`, `hora_colacion_inicio`, `hora_colacion_fin`) VALUES
(1, 'Jornada Completa 09:00-18:00', 'Horario administrativo estándar', '09:00:00', '18:00:00', 15, '13:00:00', '14:00:00'),
(1, 'Jornada Completa 08:00-17:00', 'Horario operativo', '08:00:00', '17:00:00', 10, '12:30:00', '13:30:00'),
(1, 'Turno Mañana', 'Turno matutino 06:00-14:00', '06:00:00', '14:00:00', 5, '10:00:00', '10:30:00'),
(1, 'Turno Tarde', 'Turno vespertino 14:00-22:00', '14:00:00', '22:00:00', 5, '18:00:00', '18:30:00'),
(1, 'Turno Noche', 'Turno nocturno 22:00-06:00', '22:00:00', '06:00:00', 5, '02:00:00', '02:30:00');

-- Insertar dispositivo de ejemplo
INSERT INTO `reloj_dispositivos` (`empresa_id`, `codigo`, `nombre`, `ubicacion`, `tipo`, `marca`, `activo`) VALUES
(1, 'RC-001', 'Reloj Control Entrada Principal', 'Recepción - Piso 1', 'biometrico', 'ZKTeco', 1),
(1, 'RC-002', 'Reloj Control Producción', 'Planta - Sector A', 'rfid', 'HID', 1);
