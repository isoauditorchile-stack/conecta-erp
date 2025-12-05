-- =====================================================
-- MÓDULO: IMPORTADOR DE DATOS
-- Importación masiva desde archivos CSV/Excel
-- =====================================================

-- Tabla: Plantillas de importación
CREATE TABLE IF NOT EXISTS `import_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `template_code` varchar(50) NOT NULL,
  `template_name` varchar(255) NOT NULL,
  `entity_type` enum('Activos','Riesgos','Controles','Usuarios','Políticas','Incidentes','Otro') NOT NULL,
  `description` text DEFAULT NULL,
  `file_format` enum('CSV','Excel','JSON','XML') NOT NULL,
  `column_mapping` text NOT NULL COMMENT 'JSON con mapeo de columnas',
  `validation_rules` text DEFAULT NULL COMMENT 'JSON con reglas de validación',
  `transformation_rules` text DEFAULT NULL COMMENT 'JSON con reglas de transformación',
  `sample_file_path` varchar(500) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_by` int(11) DEFAULT NULL,
  `updated_date` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_template` (`company_id`,`template_code`),
  KEY `company_id` (`company_id`),
  CONSTRAINT `fk_import_template_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos iniciales de plantillas
INSERT INTO `import_templates` (`company_id`, `template_code`, `template_name`, `entity_type`, `file_format`, `column_mapping`) VALUES
(1, 'IMP-ACT-001', 'Importación de Activos', 'Activos', 'Excel', '{"asset_code":"A","asset_name":"B","asset_type":"C","owner":"D","criticality":"E"}'),
(1, 'IMP-RSK-001', 'Importación de Riesgos', 'Riesgos', 'Excel', '{"risk_code":"A","risk_name":"B","probability":"C","impact":"D","treatment":"E"}'),
(1, 'IMP-CTL-001', 'Importación de Controles SOA', 'Controles', 'CSV', '{"control_id":"A","implementation_status":"B","responsible":"C","target_date":"D"}'),
(1, 'IMP-USR-001', 'Importación de Usuarios', 'Usuarios', 'Excel', '{"username":"A","full_name":"B","email":"C","role":"D","department":"E"}');

-- Tabla: Trabajos de importación
CREATE TABLE IF NOT EXISTS `import_jobs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `template_id` int(11) DEFAULT NULL,
  `job_name` varchar(255) NOT NULL,
  `entity_type` enum('Activos','Riesgos','Controles','Usuarios','Políticas','Incidentes','Otro') NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(11) DEFAULT NULL,
  `file_format` enum('CSV','Excel','JSON','XML') NOT NULL,
  `status` enum('Pending','Validating','Processing','Completed','Failed','Cancelled') DEFAULT 'Pending',
  `progress` int(11) DEFAULT 0 COMMENT 'Porcentaje 0-100',
  `total_rows` int(11) DEFAULT 0,
  `rows_processed` int(11) DEFAULT 0,
  `rows_success` int(11) DEFAULT 0,
  `rows_failed` int(11) DEFAULT 0,
  `rows_skipped` int(11) DEFAULT 0,
  `validation_errors` int(11) DEFAULT 0,
  `import_mode` enum('Insert','Update','Upsert','Delete') DEFAULT 'Insert',
  `duplicate_handling` enum('Skip','Update','Error') DEFAULT 'Skip',
  `started_by` int(11) NOT NULL,
  `started_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `completed_at` datetime DEFAULT NULL,
  `execution_time` int(11) DEFAULT NULL COMMENT 'Segundos',
  `error_log` text DEFAULT NULL,
  `result_summary` text DEFAULT NULL COMMENT 'JSON con resumen de resultados',
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`),
  KEY `template_id` (`template_id`),
  KEY `status` (`status`),
  CONSTRAINT `fk_import_job_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_import_job_template` FOREIGN KEY (`template_id`) REFERENCES `import_templates` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: Errores de importación
CREATE TABLE IF NOT EXISTS `import_errors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `job_id` int(11) NOT NULL,
  `row_number` int(11) NOT NULL,
  `error_type` enum('Validation','Transformation','Database','Duplicate','Missing','Other') NOT NULL,
  `error_message` text NOT NULL,
  `field_name` varchar(100) DEFAULT NULL,
  `field_value` text DEFAULT NULL,
  `raw_data` text DEFAULT NULL COMMENT 'JSON con datos de la fila',
  `severity` enum('Error','Warning','Info') DEFAULT 'Error',
  `created_date` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `job_id` (`job_id`),
  KEY `error_type` (`error_type`),
  CONSTRAINT `fk_import_error_job` FOREIGN KEY (`job_id`) REFERENCES `import_jobs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: Datos pre-validados (staging)
CREATE TABLE IF NOT EXISTS `import_staging` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `job_id` int(11) NOT NULL,
  `row_number` int(11) NOT NULL,
  `raw_data` text NOT NULL COMMENT 'JSON con datos originales',
  `transformed_data` text DEFAULT NULL COMMENT 'JSON con datos transformados',
  `validation_status` enum('Pending','Valid','Invalid','Warning') DEFAULT 'Pending',
  `validation_errors` text DEFAULT NULL COMMENT 'JSON con errores de validación',
  `is_processed` tinyint(1) DEFAULT 0,
  `processed_date` datetime DEFAULT NULL,
  `target_id` int(11) DEFAULT NULL COMMENT 'ID del registro creado/actualizado',
  PRIMARY KEY (`id`),
  KEY `job_id` (`job_id`),
  KEY `validation_status` (`validation_status`),
  KEY `is_processed` (`is_processed`),
  CONSTRAINT `fk_import_staging_job` FOREIGN KEY (`job_id`) REFERENCES `import_jobs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: Mapeo de campos
CREATE TABLE IF NOT EXISTS `import_field_mappings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `template_id` int(11) NOT NULL,
  `source_field` varchar(100) NOT NULL COMMENT 'Nombre de columna en archivo',
  `target_field` varchar(100) NOT NULL COMMENT 'Nombre de columna en DB',
  `data_type` enum('String','Integer','Decimal','Date','DateTime','Boolean','JSON') DEFAULT 'String',
  `is_required` tinyint(1) DEFAULT 0,
  `default_value` varchar(255) DEFAULT NULL,
  `transformation` varchar(500) DEFAULT NULL COMMENT 'Función de transformación',
  `validation_rule` varchar(500) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `template_id` (`template_id`),
  CONSTRAINT `fk_field_mapping_template` FOREIGN KEY (`template_id`) REFERENCES `import_templates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: Reglas de validación
CREATE TABLE IF NOT EXISTS `import_validation_rules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rule_code` varchar(50) NOT NULL,
  `rule_name` varchar(255) NOT NULL,
  `rule_type` enum('Required','Format','Range','Length','Unique','Reference','Custom') NOT NULL,
  `description` text DEFAULT NULL,
  `validation_expression` varchar(500) DEFAULT NULL,
  `error_message` varchar(500) NOT NULL,
  `severity` enum('Error','Warning') DEFAULT 'Error',
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rule_code` (`rule_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos iniciales de reglas de validación
INSERT INTO `import_validation_rules` (`rule_code`, `rule_name`, `rule_type`, `validation_expression`, `error_message`) VALUES
('REQ-001', 'Campo Requerido', 'Required', 'NOT NULL AND NOT EMPTY', 'El campo es obligatorio'),
('FMT-EMAIL', 'Formato Email', 'Format', 'REGEX: ^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\\.[a-zA-Z]{2,}$', 'Formato de email inválido'),
('FMT-DATE', 'Formato Fecha', 'Format', 'DATE: YYYY-MM-DD', 'Formato de fecha inválido (use YYYY-MM-DD)'),
('RNG-PROB', 'Rango Probabilidad', 'Range', 'BETWEEN 1 AND 5', 'La probabilidad debe estar entre 1 y 5'),
('RNG-IMP', 'Rango Impacto', 'Range', 'BETWEEN 1 AND 5', 'El impacto debe estar entre 1 y 5'),
('LEN-CODE', 'Longitud Código', 'Length', 'LENGTH <= 50', 'El código no puede exceder 50 caracteres'),
('UNQ-CODE', 'Código Único', 'Unique', 'UNIQUE IN TABLE', 'El código ya existe en el sistema');
