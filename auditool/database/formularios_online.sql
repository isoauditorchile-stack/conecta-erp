-- =====================================================
-- MÓDULO: FORMULARIOS ONLINE
-- Formularios web para ingreso directo a SQL
-- =====================================================

-- Tabla: Definición de formularios
CREATE TABLE IF NOT EXISTS `form_definitions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `form_code` varchar(50) NOT NULL,
  `form_name` varchar(255) NOT NULL,
  `form_category` enum('Activos','Riesgos','Incidentes','Controles','Auditorías','Usuarios','Otro') NOT NULL,
  `description` text DEFAULT NULL,
  `target_table` varchar(100) NOT NULL,
  `form_schema` text NOT NULL COMMENT 'JSON con estructura del formulario',
  `validation_rules` text DEFAULT NULL COMMENT 'JSON con reglas de validación',
  `workflow` text DEFAULT NULL COMMENT 'JSON con flujo de aprobación',
  `success_message` varchar(500) DEFAULT NULL,
  `redirect_url` varchar(255) DEFAULT NULL,
  `email_notifications` text DEFAULT NULL COMMENT 'JSON con configuración de notificaciones',
  `is_public` tinyint(1) DEFAULT 0,
  `requires_auth` tinyint(1) DEFAULT 1,
  `max_submissions_per_user` int(11) DEFAULT NULL,
  `submission_window_start` date DEFAULT NULL,
  `submission_window_end` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_by` int(11) DEFAULT NULL,
  `updated_date` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_form` (`company_id`,`form_code`),
  KEY `company_id` (`company_id`),
  KEY `is_active` (`is_active`),
  CONSTRAINT `fk_form_def_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos iniciales de formularios
INSERT INTO `form_definitions` (`company_id`, `form_code`, `form_name`, `form_category`, `target_table`, `form_schema`, `is_active`) VALUES
(1, 'FRM-INC-001', 'Reporte de Incidente de Seguridad', 'Incidentes', 'iso27001_incidents', '{"fields":[{"name":"incident_type","type":"select","label":"Tipo de Incidente","required":true},{"name":"description","type":"textarea","label":"Descripción","required":true},{"name":"severity","type":"select","label":"Severidad","required":true}]}', 1),
(1, 'FRM-ACT-001', 'Registro de Activo', 'Activos', 'iso27001_assets', '{"fields":[{"name":"asset_code","type":"text","label":"Código","required":true},{"name":"asset_name","type":"text","label":"Nombre","required":true},{"name":"asset_type","type":"select","label":"Tipo","required":true}]}', 1),
(1, 'FRM-RSK-001', 'Identificación de Riesgo', 'Riesgos', 'iso27001_risks', '{"fields":[{"name":"risk_name","type":"text","label":"Nombre del Riesgo","required":true},{"name":"threat","type":"textarea","label":"Amenaza","required":true},{"name":"vulnerability","type":"textarea","label":"Vulnerabilidad","required":true}]}', 1);

-- Tabla: Campos de formularios
CREATE TABLE IF NOT EXISTS `form_fields` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `form_id` int(11) NOT NULL,
  `field_name` varchar(100) NOT NULL,
  `field_label` varchar(255) NOT NULL,
  `field_type` enum('Text','TextArea','Number','Date','DateTime','Email','Tel','URL','Select','Radio','Checkbox','File','Hidden') NOT NULL,
  `field_placeholder` varchar(255) DEFAULT NULL,
  `field_help` varchar(500) DEFAULT NULL,
  `is_required` tinyint(1) DEFAULT 0,
  `default_value` varchar(255) DEFAULT NULL,
  `validation_rule` varchar(500) DEFAULT NULL,
  `options` text DEFAULT NULL COMMENT 'JSON con opciones para select/radio/checkbox',
  `min_value` varchar(50) DEFAULT NULL,
  `max_value` varchar(50) DEFAULT NULL,
  `min_length` int(11) DEFAULT NULL,
  `max_length` int(11) DEFAULT NULL,
  `pattern` varchar(500) DEFAULT NULL,
  `file_extensions` varchar(255) DEFAULT NULL,
  `max_file_size` int(11) DEFAULT NULL COMMENT 'KB',
  `depends_on` varchar(100) DEFAULT NULL COMMENT 'Campo del que depende',
  `depends_condition` varchar(500) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `column_span` tinyint(1) DEFAULT 1 COMMENT '1-12 (Bootstrap grid)',
  PRIMARY KEY (`id`),
  KEY `form_id` (`form_id`),
  CONSTRAINT `fk_form_field_form` FOREIGN KEY (`form_id`) REFERENCES `form_definitions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: Envíos de formularios
CREATE TABLE IF NOT EXISTS `form_submissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `form_id` int(11) NOT NULL,
  `submission_code` varchar(50) DEFAULT NULL,
  `form_data` text NOT NULL COMMENT 'JSON con datos del formulario',
  `files_attached` text DEFAULT NULL COMMENT 'JSON con archivos adjuntos',
  `submitted_by` int(11) DEFAULT NULL,
  `submission_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected','Processing','Completed') DEFAULT 'Pending',
  `reviewed_by` int(11) DEFAULT NULL,
  `review_date` datetime DEFAULT NULL,
  `review_comments` text DEFAULT NULL,
  `target_record_id` int(11) DEFAULT NULL COMMENT 'ID del registro creado en tabla destino',
  `validation_errors` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `submission_code` (`submission_code`),
  KEY `form_id` (`form_id`),
  KEY `submitted_by` (`submitted_by`),
  KEY `status` (`status`),
  KEY `submission_date` (`submission_date`),
  CONSTRAINT `fk_submission_form` FOREIGN KEY (`form_id`) REFERENCES `form_definitions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: Archivos adjuntos de formularios
CREATE TABLE IF NOT EXISTS `form_attachments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `submission_id` int(11) NOT NULL,
  `field_name` varchar(100) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_original_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(11) NOT NULL COMMENT 'Bytes',
  `file_type` varchar(100) NOT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `upload_date` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `submission_id` (`submission_id`),
  CONSTRAINT `fk_attachment_submission` FOREIGN KEY (`submission_id`) REFERENCES `form_submissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: Flujo de aprobación de formularios
CREATE TABLE IF NOT EXISTS `form_workflow` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `form_id` int(11) NOT NULL,
  `workflow_name` varchar(255) NOT NULL,
  `workflow_steps` text NOT NULL COMMENT 'JSON con pasos del flujo',
  `is_active` tinyint(1) DEFAULT 1,
  `created_date` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `form_id` (`form_id`),
  CONSTRAINT `fk_workflow_form` FOREIGN KEY (`form_id`) REFERENCES `form_definitions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: Pasos de aprobación de envíos
CREATE TABLE IF NOT EXISTS `submission_approval_steps` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `submission_id` int(11) NOT NULL,
  `step_number` int(11) NOT NULL,
  `step_name` varchar(255) NOT NULL,
  `approver_role` varchar(100) DEFAULT NULL,
  `approver_user` int(11) DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected','Skipped') DEFAULT 'Pending',
  `decision_date` datetime DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `notified_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `submission_id` (`submission_id`),
  KEY `approver_user` (`approver_user`),
  CONSTRAINT `fk_approval_step_submission` FOREIGN KEY (`submission_id`) REFERENCES `form_submissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: Plantillas de formularios
CREATE TABLE IF NOT EXISTS `form_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `template_code` varchar(50) NOT NULL,
  `template_name` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `template_schema` text NOT NULL COMMENT 'JSON con estructura predefinida',
  `preview_image` varchar(500) DEFAULT NULL,
  `is_public` tinyint(1) DEFAULT 1,
  `download_count` int(11) DEFAULT 0,
  `created_date` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `template_code` (`template_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos iniciales de plantillas
INSERT INTO `form_templates` (`template_code`, `template_name`, `category`, `template_schema`) VALUES
('TPL-CONTACT', 'Formulario de Contacto Básico', 'General', '{"fields":[{"name":"nombre","type":"text","label":"Nombre","required":true},{"name":"email","type":"email","label":"Email","required":true},{"name":"mensaje","type":"textarea","label":"Mensaje","required":true}]}'),
('TPL-INCIDENT', 'Reporte de Incidente Completo', 'Seguridad', '{"fields":[{"name":"tipo","type":"select","label":"Tipo de Incidente"},{"name":"fecha","type":"datetime","label":"Fecha y Hora"},{"name":"descripcion","type":"textarea","label":"Descripción Detallada"},{"name":"impacto","type":"select","label":"Nivel de Impacto"},{"name":"evidencia","type":"file","label":"Adjuntar Evidencia"}]}'),
('TPL-ASSET', 'Registro de Activo de TI', 'Activos', '{"fields":[{"name":"codigo","type":"text","label":"Código del Activo"},{"name":"nombre","type":"text","label":"Nombre"},{"name":"tipo","type":"select","label":"Tipo"},{"name":"propietario","type":"text","label":"Propietario"},{"name":"ubicacion","type":"text","label":"Ubicación"},{"name":"criticidad","type":"select","label":"Criticidad"}]}');

-- Tabla: Configuración de notificaciones de formularios
CREATE TABLE IF NOT EXISTS `form_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `form_id` int(11) NOT NULL,
  `trigger_event` enum('OnSubmit','OnApprove','OnReject','OnUpdate','OnDelete') NOT NULL,
  `recipient_type` enum('User','Role','Email','Submitter','Approver') NOT NULL,
  `recipient_value` varchar(255) NOT NULL,
  `subject` varchar(500) NOT NULL,
  `message_template` text NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `form_id` (`form_id`),
  CONSTRAINT `fk_notification_form` FOREIGN KEY (`form_id`) REFERENCES `form_definitions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
