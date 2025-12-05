-- =====================================================
-- MÓDULO: NO CONFORMIDADES
-- Registro y seguimiento de hallazgos de auditoría
-- =====================================================

-- Tabla: No conformidades
CREATE TABLE IF NOT EXISTS `nonconformities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `nc_code` varchar(50) NOT NULL,
  `nc_title` varchar(255) NOT NULL,
  `nc_type` enum('Mayor','Menor','Observación') NOT NULL,
  `source` enum('Auditoría Interna','Auditoría Externa','Auto-evaluación','Incidente','Queja Cliente','Revisión Dirección','Otro') NOT NULL,
  `source_reference` varchar(255) DEFAULT NULL COMMENT 'Código de auditoría, incidente, etc.',
  `detection_date` date NOT NULL,
  `detected_by` int(11) DEFAULT NULL,
  `affected_process` varchar(255) DEFAULT NULL,
  `affected_area` varchar(255) DEFAULT NULL,
  `requirement_violated` text NOT NULL COMMENT 'Cláusula ISO, política, procedimiento',
  `nc_description` text NOT NULL,
  `objective_evidence` text DEFAULT NULL,
  `immediate_action` text DEFAULT NULL COMMENT 'Acción inmediata tomada',
  `root_cause_analysis` text DEFAULT NULL,
  `root_cause_method` enum('5 Porqués','Ishikawa','Pareto','Otro','No Aplicado') DEFAULT NULL,
  `corrective_action` text DEFAULT NULL,
  `preventive_action` text DEFAULT NULL,
  `responsible` int(11) DEFAULT NULL,
  `target_date` date DEFAULT NULL,
  `implementation_date` date DEFAULT NULL,
  `status` enum('Abierta','En Análisis','Acción Planificada','En Implementación','En Verificación','Cerrada','Reabierta') DEFAULT 'Abierta',
  `effectiveness_verified` tinyint(1) DEFAULT 0,
  `verification_method` varchar(500) DEFAULT NULL,
  `verification_date` date DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `verification_comments` text DEFAULT NULL,
  `closure_date` date DEFAULT NULL,
  `closure_approved_by` int(11) DEFAULT NULL,
  `priority` enum('Baja','Media','Alta','Crítica') DEFAULT 'Media',
  `cost_impact` decimal(15,2) DEFAULT NULL,
  `recurrence` tinyint(1) DEFAULT 0 COMMENT 'Es recurrente',
  `related_nc` int(11) DEFAULT NULL COMMENT 'NC relacionada/previa',
  `attachments` text DEFAULT NULL COMMENT 'JSON con archivos adjuntos',
  `created_by` int(11) DEFAULT NULL,
  `created_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_by` int(11) DEFAULT NULL,
  `updated_date` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_nc` (`company_id`,`nc_code`),
  KEY `company_id` (`company_id`),
  KEY `nc_type` (`nc_type`),
  KEY `status` (`status`),
  KEY `priority` (`priority`),
  KEY `related_nc` (`related_nc`),
  CONSTRAINT `fk_nc_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_nc_related` FOREIGN KEY (`related_nc`) REFERENCES `nonconformities` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: Acciones correctivas y preventivas (CAPA)
CREATE TABLE IF NOT EXISTS `corrective_actions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `action_code` varchar(50) NOT NULL,
  `action_type` enum('Correctiva','Preventiva','Mejora') NOT NULL,
  `nc_id` int(11) DEFAULT NULL,
  `action_title` varchar(255) NOT NULL,
  `action_description` text NOT NULL,
  `root_cause` text DEFAULT NULL,
  `expected_result` text DEFAULT NULL,
  `responsible` int(11) NOT NULL,
  `support_team` text DEFAULT NULL COMMENT 'JSON con equipo de apoyo',
  `target_date` date NOT NULL,
  `completion_date` date DEFAULT NULL,
  `status` enum('Planificada','En Progreso','Completada','Verificada','Cerrada','Cancelada') DEFAULT 'Planificada',
  `progress_percentage` int(11) DEFAULT 0 COMMENT '0-100',
  `cost_estimated` decimal(15,2) DEFAULT NULL,
  `cost_actual` decimal(15,2) DEFAULT NULL,
  `effectiveness_criteria` text DEFAULT NULL,
  `effectiveness_verified` tinyint(1) DEFAULT 0,
  `verification_date` date DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `verification_evidence` text DEFAULT NULL,
  `closure_date` date DEFAULT NULL,
  `closure_approved_by` int(11) DEFAULT NULL,
  `lessons_learned` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_by` int(11) DEFAULT NULL,
  `updated_date` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_action` (`company_id`,`action_code`),
  KEY `company_id` (`company_id`),
  KEY `nc_id` (`nc_id`),
  KEY `status` (`status`),
  CONSTRAINT `fk_action_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_action_nc` FOREIGN KEY (`nc_id`) REFERENCES `nonconformities` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: Tareas de acciones correctivas
CREATE TABLE IF NOT EXISTS `action_tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `action_id` int(11) NOT NULL,
  `task_number` int(11) NOT NULL,
  `task_description` text NOT NULL,
  `assigned_to` int(11) NOT NULL,
  `due_date` date NOT NULL,
  `completion_date` date DEFAULT NULL,
  `status` enum('Pending','In Progress','Completed','Blocked') DEFAULT 'Pending',
  `progress_notes` text DEFAULT NULL,
  `evidence` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `action_id` (`action_id`),
  KEY `assigned_to` (`assigned_to`),
  CONSTRAINT `fk_task_action` FOREIGN KEY (`action_id`) REFERENCES `corrective_actions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: Seguimiento de acciones
CREATE TABLE IF NOT EXISTS `action_follow_up` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `action_id` int(11) NOT NULL,
  `follow_up_date` date NOT NULL,
  `progress_status` enum('Sin Iniciar','En Riesgo','En Progreso','Completada','Bloqueada') DEFAULT 'En Progreso',
  `progress_percentage` int(11) DEFAULT NULL COMMENT '0-100',
  `activities_completed` text DEFAULT NULL,
  `activities_pending` text DEFAULT NULL,
  `issues_identified` text DEFAULT NULL,
  `support_needed` text DEFAULT NULL,
  `next_steps` text DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `followed_by` int(11) NOT NULL,
  `created_date` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `action_id` (`action_id`),
  KEY `follow_up_date` (`follow_up_date`),
  CONSTRAINT `fk_followup_action` FOREIGN KEY (`action_id`) REFERENCES `corrective_actions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: Análisis de causa raíz
CREATE TABLE IF NOT EXISTS `root_cause_analysis` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nc_id` int(11) NOT NULL,
  `analysis_method` enum('5 Porqués','Ishikawa','Pareto','Árbol de Fallas','Otro') NOT NULL,
  `problem_statement` text NOT NULL,
  `analysis_data` text DEFAULT NULL COMMENT 'JSON con datos del análisis',
  `root_causes_identified` text NOT NULL,
  `contributing_factors` text DEFAULT NULL,
  `evidence` text DEFAULT NULL,
  `analyzed_by` int(11) DEFAULT NULL,
  `analysis_date` date DEFAULT NULL,
  `validated_by` int(11) DEFAULT NULL,
  `validation_date` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `nc_id` (`nc_id`),
  CONSTRAINT `fk_rca_nc` FOREIGN KEY (`nc_id`) REFERENCES `nonconformities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: Efectividad de acciones
CREATE TABLE IF NOT EXISTS `action_effectiveness` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `action_id` int(11) NOT NULL,
  `evaluation_date` date NOT NULL,
  `evaluation_method` varchar(500) NOT NULL,
  `kpis_measured` text DEFAULT NULL COMMENT 'JSON con KPIs medidos',
  `is_effective` tinyint(1) DEFAULT NULL,
  `effectiveness_level` enum('No Efectiva','Parcialmente Efectiva','Efectiva','Altamente Efectiva') DEFAULT NULL,
  `evidence` text DEFAULT NULL,
  `findings` text DEFAULT NULL,
  `additional_actions_needed` text DEFAULT NULL,
  `evaluated_by` int(11) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approval_date` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `action_id` (`action_id`),
  CONSTRAINT `fk_effectiveness_action` FOREIGN KEY (`action_id`) REFERENCES `corrective_actions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: Notificaciones de no conformidades
CREATE TABLE IF NOT EXISTS `nc_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nc_id` int(11) NOT NULL,
  `notification_type` enum('Asignación','Vencimiento','Recordatorio','Escalamiento','Cierre') NOT NULL,
  `recipient_id` int(11) NOT NULL,
  `notification_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `read_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `nc_id` (`nc_id`),
  KEY `recipient_id` (`recipient_id`),
  CONSTRAINT `fk_nc_notif_nc` FOREIGN KEY (`nc_id`) REFERENCES `nonconformities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: Plantillas de análisis de causa raíz
CREATE TABLE IF NOT EXISTS `rca_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `template_name` varchar(255) NOT NULL,
  `method` enum('5 Porqués','Ishikawa','Pareto','Árbol de Fallas','Otro') NOT NULL,
  `template_structure` text NOT NULL COMMENT 'JSON con estructura de la plantilla',
  `instructions` text DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos iniciales de plantillas RCA
INSERT INTO `rca_templates` (`template_name`, `method`, `template_structure`) VALUES
('Plantilla 5 Porqués', '5 Porqués', '{"questions":["¿Por qué ocurrió el problema?","¿Por qué sucedió eso?","¿Por qué fue así?","¿Por qué no se previno?","¿Por qué falla el sistema?"]}'),
('Plantilla Ishikawa', 'Ishikawa', '{"categories":["Personas","Procesos","Tecnología","Materiales","Métodos","Ambiente"]}');

-- Tabla: Estadísticas de no conformidades
CREATE TABLE IF NOT EXISTS `nc_statistics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `period_year` year NOT NULL,
  `period_month` tinyint(2) DEFAULT NULL,
  `nc_total` int(11) DEFAULT 0,
  `nc_major` int(11) DEFAULT 0,
  `nc_minor` int(11) DEFAULT 0,
  `nc_observations` int(11) DEFAULT 0,
  `nc_opened` int(11) DEFAULT 0,
  `nc_closed` int(11) DEFAULT 0,
  `nc_in_progress` int(11) DEFAULT 0,
  `avg_closure_time` decimal(10,2) DEFAULT NULL COMMENT 'Días promedio',
  `on_time_closure_rate` decimal(5,2) DEFAULT NULL COMMENT 'Porcentaje',
  `recurrence_rate` decimal(5,2) DEFAULT NULL COMMENT 'Porcentaje',
  `calculated_date` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_period` (`company_id`,`period_year`,`period_month`),
  KEY `company_id` (`company_id`),
  CONSTRAINT `fk_nc_stats_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
