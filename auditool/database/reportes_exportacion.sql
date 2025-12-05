-- =====================================================
-- MÓDULO: REPORTES Y EXPORTACIÓN
-- Reportes personalizados y exportación de datos
-- =====================================================

-- Tabla: Definición de reportes
CREATE TABLE IF NOT EXISTS `report_definitions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `report_code` varchar(50) NOT NULL,
  `report_name` varchar(255) NOT NULL,
  `report_category` enum('Operacional','Gerencial','Ejecutivo','Auditoría','Cumplimiento','Técnico') NOT NULL,
  `description` text DEFAULT NULL,
  `sql_query` text DEFAULT NULL,
  `data_source` varchar(100) DEFAULT NULL,
  `parameters` text DEFAULT NULL COMMENT 'JSON con parámetros del reporte',
  `columns` text DEFAULT NULL COMMENT 'JSON con definición de columnas',
  `filters` text DEFAULT NULL COMMENT 'JSON con filtros disponibles',
  `grouping` varchar(255) DEFAULT NULL,
  `sorting` varchar(255) DEFAULT NULL,
  `chart_type` enum('None','Bar','Line','Pie','Donut','Area','Scatter') DEFAULT 'None',
  `chart_config` text DEFAULT NULL COMMENT 'JSON con configuración de gráfico',
  `format_options` text DEFAULT NULL COMMENT 'JSON con opciones de formato',
  `is_public` tinyint(1) DEFAULT 0,
  `is_scheduled` tinyint(1) DEFAULT 0,
  `schedule_frequency` enum('Diario','Semanal','Mensual','Trimestral','Anual') DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_by` int(11) DEFAULT NULL,
  `updated_date` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_report` (`company_id`,`report_code`),
  KEY `company_id` (`company_id`),
  KEY `report_category` (`report_category`),
  CONSTRAINT `fk_report_def_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos iniciales de reportes
INSERT INTO `report_definitions` (`company_id`, `report_code`, `report_name`, `report_category`, `description`) VALUES
(1, 'REP-001', 'Resumen de Activos por Criticidad', 'Operacional', 'Distribución de activos según nivel de criticidad'),
(1, 'REP-002', 'Matriz de Riesgos Completa', 'Gerencial', 'Reporte completo de todos los riesgos identificados'),
(1, 'REP-003', 'Estado de Controles ISO 27001', 'Auditoría', 'Estado de implementación de controles del Anexo A'),
(1, 'REP-004', 'Incidentes de Seguridad Mensual', 'Gerencial', 'Resumen mensual de incidentes de seguridad'),
(1, 'REP-005', 'Cumplimiento de Políticas', 'Cumplimiento', 'Nivel de cumplimiento de políticas de seguridad'),
(1, 'REP-006', 'Dashboard Ejecutivo SGSI', 'Ejecutivo', 'KPIs principales del Sistema de Gestión');

-- Tabla: Ejecuciones de reportes
CREATE TABLE IF NOT EXISTS `report_executions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `report_id` int(11) NOT NULL,
  `executed_by` int(11) NOT NULL,
  `execution_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `parameters_used` text DEFAULT NULL COMMENT 'JSON con parámetros utilizados',
  `filters_applied` text DEFAULT NULL,
  `execution_time` decimal(10,3) DEFAULT NULL COMMENT 'Tiempo en segundos',
  `rows_returned` int(11) DEFAULT NULL,
  `output_format` enum('HTML','PDF','Excel','CSV','JSON') DEFAULT 'HTML',
  `file_path` varchar(500) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `status` enum('Running','Completed','Failed','Cancelled') DEFAULT 'Running',
  `error_message` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `report_id` (`report_id`),
  KEY `executed_by` (`executed_by`),
  KEY `execution_date` (`execution_date`),
  CONSTRAINT `fk_report_exec_report` FOREIGN KEY (`report_id`) REFERENCES `report_definitions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: Programación de reportes
CREATE TABLE IF NOT EXISTS `report_schedules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `report_id` int(11) NOT NULL,
  `schedule_name` varchar(255) NOT NULL,
  `frequency` enum('Diario','Semanal','Mensual','Trimestral','Anual') NOT NULL,
  `day_of_week` tinyint(1) DEFAULT NULL COMMENT '1=Lunes, 7=Domingo',
  `day_of_month` tinyint(2) DEFAULT NULL COMMENT '1-31',
  `time_of_day` time DEFAULT '08:00:00',
  `parameters` text DEFAULT NULL COMMENT 'JSON con parámetros predefinidos',
  `output_format` enum('PDF','Excel','CSV') DEFAULT 'PDF',
  `recipients` text NOT NULL COMMENT 'JSON con lista de emails',
  `is_active` tinyint(1) DEFAULT 1,
  `last_run` datetime DEFAULT NULL,
  `next_run` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_date` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `report_id` (`report_id`),
  KEY `is_active` (`is_active`),
  KEY `next_run` (`next_run`),
  CONSTRAINT `fk_report_sched_report` FOREIGN KEY (`report_id`) REFERENCES `report_definitions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: Exportaciones masivas
CREATE TABLE IF NOT EXISTS `bulk_exports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `export_type` enum('Activos','Riesgos','Controles','Políticas','Incidentes','Auditorías','Completo') NOT NULL,
  `export_format` enum('Excel','CSV','JSON','XML','PDF') NOT NULL,
  `filters` text DEFAULT NULL COMMENT 'JSON con filtros aplicados',
  `requested_by` int(11) NOT NULL,
  `request_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `status` enum('Pending','Processing','Completed','Failed') DEFAULT 'Pending',
  `progress` int(11) DEFAULT 0 COMMENT 'Porcentaje 0-100',
  `records_exported` int(11) DEFAULT 0,
  `file_path` varchar(500) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `download_count` int(11) DEFAULT 0,
  `expires_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`),
  KEY `status` (`status`),
  KEY `requested_by` (`requested_by`),
  CONSTRAINT `fk_bulk_export_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: Favoritos de reportes
CREATE TABLE IF NOT EXISTS `report_favorites` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `report_id` int(11) NOT NULL,
  `favorite_name` varchar(255) DEFAULT NULL,
  `saved_parameters` text DEFAULT NULL COMMENT 'JSON con parámetros guardados',
  `saved_filters` text DEFAULT NULL,
  `created_date` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_favorite` (`user_id`,`report_id`),
  KEY `report_id` (`report_id`),
  CONSTRAINT `fk_report_fav_report` FOREIGN KEY (`report_id`) REFERENCES `report_definitions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: Dashboards personalizados
CREATE TABLE IF NOT EXISTS `custom_dashboards` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `dashboard_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `layout` text DEFAULT NULL COMMENT 'JSON con configuración de widgets',
  `widgets` text DEFAULT NULL COMMENT 'JSON con widgets y configuración',
  `is_default` tinyint(1) DEFAULT 0,
  `is_public` tinyint(1) DEFAULT 0,
  `refresh_interval` int(11) DEFAULT 300 COMMENT 'Segundos',
  `created_by` int(11) DEFAULT NULL,
  `created_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_date` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_dashboard_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: Widgets de dashboard
CREATE TABLE IF NOT EXISTS `dashboard_widgets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `widget_code` varchar(50) NOT NULL,
  `widget_name` varchar(255) NOT NULL,
  `widget_type` enum('Chart','Table','KPI','Gauge','Map','List','Card') NOT NULL,
  `data_source` varchar(100) DEFAULT NULL,
  `sql_query` text DEFAULT NULL,
  `configuration` text DEFAULT NULL COMMENT 'JSON con configuración del widget',
  `default_size` varchar(20) DEFAULT '4x4',
  `category` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_date` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `widget_code` (`widget_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos iniciales de widgets
INSERT INTO `dashboard_widgets` (`widget_code`, `widget_name`, `widget_type`, `category`, `default_size`) VALUES
('WDG-001', 'Total de Activos', 'KPI', 'Activos', '2x2'),
('WDG-002', 'Riesgos por Nivel', 'Chart', 'Riesgos', '4x3'),
('WDG-003', 'Incidentes del Mes', 'KPI', 'Incidentes', '2x2'),
('WDG-004', 'Controles por Estado', 'Chart', 'Controles', '4x3'),
('WDG-005', 'Nivel de Madurez SGSI', 'Gauge', 'General', '3x3'),
('WDG-006', 'Próximas Auditorías', 'List', 'Auditorías', '4x2');
