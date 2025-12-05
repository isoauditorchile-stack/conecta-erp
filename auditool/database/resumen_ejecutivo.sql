-- =====================================================
-- MÓDULO: RESUMEN EJECUTIVO
-- Dashboard con KPIs y nivel de madurez del SGSI
-- =====================================================

-- Tabla: KPIs del SGSI
CREATE TABLE IF NOT EXISTS `sgsi_kpis` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `kpi_code` varchar(50) NOT NULL,
  `kpi_name` varchar(255) NOT NULL,
  `kpi_category` enum('Seguridad','Operacional','Cumplimiento','Financiero','RRHH') NOT NULL,
  `description` text DEFAULT NULL,
  `measurement_unit` varchar(50) DEFAULT NULL,
  `target_value` decimal(10,2) DEFAULT NULL,
  `threshold_green` decimal(10,2) DEFAULT NULL COMMENT 'Umbral para semáforo verde',
  `threshold_yellow` decimal(10,2) DEFAULT NULL COMMENT 'Umbral para semáforo amarillo',
  `threshold_red` decimal(10,2) DEFAULT NULL COMMENT 'Umbral para semáforo rojo',
  `calculation_method` enum('Manual','Automated','Formula') DEFAULT 'Manual',
  `sql_query` text DEFAULT NULL,
  `formula` varchar(500) DEFAULT NULL,
  `frequency` enum('Diaria','Semanal','Mensual','Trimestral','Anual') DEFAULT 'Mensual',
  `responsible` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `display_order` int(11) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_date` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_kpi` (`company_id`,`kpi_code`),
  KEY `company_id` (`company_id`),
  CONSTRAINT `fk_kpi_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos iniciales de KPIs
INSERT INTO `sgsi_kpis` (`company_id`, `kpi_code`, `kpi_name`, `kpi_category`, `measurement_unit`, `target_value`, `threshold_green`, `threshold_yellow`, `threshold_red`, `frequency`) VALUES
(1, 'KPI-001', 'Tiempo Promedio Resolución Incidentes', 'Seguridad', 'Horas', 24.00, 24.00, 48.00, 72.00, 'Mensual'),
(1, 'KPI-002', 'Porcentaje Usuarios Capacitados', 'RRHH', '%', 100.00, 90.00, 70.00, 50.00, 'Trimestral'),
(1, 'KPI-003', 'Disponibilidad Sistemas Críticos', 'Operacional', '%', 99.90, 99.90, 99.50, 99.00, 'Mensual'),
(1, 'KPI-004', 'Vulnerabilidades Críticas Sin Parchar', 'Seguridad', 'Número', 0.00, 0.00, 2.00, 5.00, 'Mensual'),
(1, 'KPI-005', 'Incidentes de Seguridad', 'Seguridad', 'Número', 0.00, 5.00, 10.00, 20.00, 'Mensual'),
(1, 'KPI-006', 'Cumplimiento de Políticas', 'Cumplimiento', '%', 100.00, 95.00, 85.00, 70.00, 'Trimestral'),
(1, 'KPI-007', 'Tiempo Medio Entre Fallos (MTBF)', 'Operacional', 'Horas', 720.00, 720.00, 480.00, 240.00, 'Mensual'),
(1, 'KPI-008', 'Porcentaje Controles Implementados', 'Cumplimiento', '%', 100.00, 90.00, 75.00, 50.00, 'Trimestral');

-- Tabla: Valores históricos de KPIs
CREATE TABLE IF NOT EXISTS `kpi_values` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kpi_id` int(11) NOT NULL,
  `measurement_date` date NOT NULL,
  `value` decimal(10,2) NOT NULL,
  `status` enum('Verde','Amarillo','Rojo') DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `evidence` varchar(500) DEFAULT NULL,
  `measured_by` int(11) DEFAULT NULL,
  `created_date` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `kpi_id` (`kpi_id`),
  KEY `measurement_date` (`measurement_date`),
  CONSTRAINT `fk_kpi_values_kpi` FOREIGN KEY (`kpi_id`) REFERENCES `sgsi_kpis` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: Nivel de madurez del SGSI
CREATE TABLE IF NOT EXISTS `sgsi_maturity` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `assessment_date` date NOT NULL,
  `overall_level` tinyint(1) DEFAULT NULL COMMENT '1-5: Inicial, Gestionado, Definido, Cuantitativo, Optimizado',
  `domain_scores` text DEFAULT NULL COMMENT 'JSON con scores por dominio',
  `strengths` text DEFAULT NULL,
  `weaknesses` text DEFAULT NULL,
  `opportunities` text DEFAULT NULL,
  `threats` text DEFAULT NULL,
  `action_plan` text DEFAULT NULL,
  `next_assessment` date DEFAULT NULL,
  `assessed_by` int(11) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `created_date` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`),
  KEY `assessment_date` (`assessment_date`),
  CONSTRAINT `fk_maturity_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: Dominios de madurez
CREATE TABLE IF NOT EXISTS `maturity_domains` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `domain_code` varchar(20) NOT NULL,
  `domain_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `weight` decimal(5,2) DEFAULT 1.00 COMMENT 'Peso en el cálculo global',
  `sort_order` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `domain_code` (`domain_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos iniciales de dominios
INSERT INTO `maturity_domains` (`domain_code`, `domain_name`, `description`, `weight`, `sort_order`) VALUES
('GOV', 'Gobierno y Liderazgo', 'Compromiso de la dirección y estructura de gobierno', 1.20, 1),
('POL', 'Políticas y Procedimientos', 'Documentación del SGSI', 1.00, 2),
('RISK', 'Gestión de Riesgos', 'Identificación, análisis y tratamiento de riesgos', 1.30, 3),
('CTL', 'Controles de Seguridad', 'Implementación de controles técnicos y organizacionales', 1.10, 4),
('INC', 'Gestión de Incidentes', 'Respuesta y recuperación ante incidentes', 1.00, 5),
('AWARE', 'Concientización', 'Capacitación y cultura de seguridad', 0.90, 6),
('MON', 'Monitoreo y Mejora', 'Supervisión continua y mejora del SGSI', 1.10, 7),
('COMP', 'Cumplimiento', 'Alineación con regulaciones y estándares', 1.00, 8);

-- Tabla: Resumen de cumplimiento por cláusula ISO 27001
CREATE TABLE IF NOT EXISTS `iso_compliance_summary` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `clause_number` varchar(10) NOT NULL,
  `clause_title` varchar(255) NOT NULL,
  `compliance_level` decimal(5,2) DEFAULT 0.00 COMMENT 'Porcentaje 0-100',
  `status` enum('No Iniciado','En Proceso','Cumple Parcialmente','Cumple Totalmente') DEFAULT 'No Iniciado',
  `evidence_count` int(11) DEFAULT 0,
  `findings_count` int(11) DEFAULT 0,
  `last_assessment` date DEFAULT NULL,
  `assessed_by` int(11) DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `updated_date` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_clause` (`company_id`,`clause_number`),
  KEY `company_id` (`company_id`),
  CONSTRAINT `fk_iso_compliance_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos iniciales de cláusulas ISO 27001
INSERT INTO `iso_compliance_summary` (`company_id`, `clause_number`, `clause_title`) VALUES
(1, '4.1', 'Comprensión de la organización y su contexto'),
(1, '4.2', 'Comprensión de las necesidades y expectativas de las partes interesadas'),
(1, '4.3', 'Determinación del alcance del SGSI'),
(1, '4.4', 'Sistema de gestión de seguridad de la información'),
(1, '5.1', 'Liderazgo y compromiso'),
(1, '5.2', 'Política'),
(1, '5.3', 'Roles, responsabilidades y autoridades'),
(1, '6.1', 'Acciones para abordar riesgos y oportunidades'),
(1, '6.2', 'Objetivos de seguridad de la información'),
(1, '7.1', 'Recursos'),
(1, '7.2', 'Competencia'),
(1, '7.3', 'Toma de conciencia'),
(1, '7.4', 'Comunicación'),
(1, '7.5', 'Información documentada'),
(1, '8.1', 'Planificación y control operacional'),
(1, '8.2', 'Evaluación de riesgos de seguridad'),
(1, '8.3', 'Tratamiento de riesgos'),
(1, '9.1', 'Seguimiento, medición, análisis y evaluación'),
(1, '9.2', 'Auditoría interna'),
(1, '9.3', 'Revisión por la dirección'),
(1, '10.1', 'No conformidad y acción correctiva'),
(1, '10.2', 'Mejora continua');

-- Tabla: Alertas del SGSI
CREATE TABLE IF NOT EXISTS `sgsi_alerts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `alert_type` enum('KPI','Vencimiento','Incidente','Auditoría','Riesgo','Otro') NOT NULL,
  `severity` enum('Info','Warning','Critical') NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `alert_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `expiry_date` datetime DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `read_by` int(11) DEFAULT NULL,
  `read_date` datetime DEFAULT NULL,
  `is_dismissed` tinyint(1) DEFAULT 0,
  `dismissed_by` int(11) DEFAULT NULL,
  `dismissed_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`),
  KEY `alert_type` (`alert_type`),
  KEY `severity` (`severity`),
  KEY `is_read` (`is_read`),
  CONSTRAINT `fk_alerts_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
