-- =====================================================
-- MÓDULO: AUDITORÍAS
-- Gestión de programa de auditorías internas y externas
-- =====================================================

-- Tabla: Programa anual de auditorías
CREATE TABLE IF NOT EXISTS `audit_program` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `program_year` year NOT NULL,
  `program_name` varchar(255) NOT NULL,
  `program_objective` text DEFAULT NULL,
  `scope` text DEFAULT NULL,
  `total_audits_planned` int(11) DEFAULT 0,
  `total_audits_completed` int(11) DEFAULT 0,
  `status` enum('Draft','Approved','In Progress','Completed','Closed') DEFAULT 'Draft',
  `approved_by` int(11) DEFAULT NULL,
  `approval_date` date DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_by` int(11) DEFAULT NULL,
  `updated_date` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_program` (`company_id`,`program_year`),
  KEY `company_id` (`company_id`),
  CONSTRAINT `fk_audit_program_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos iniciales
INSERT INTO `audit_program` (`company_id`, `program_year`, `program_name`, `status`) VALUES
(1, 2025, 'Programa de Auditorías Internas 2025', 'Approved');

-- Tabla: Auditorías planificadas/ejecutadas
CREATE TABLE IF NOT EXISTS `audits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `program_id` int(11) DEFAULT NULL,
  `audit_code` varchar(50) NOT NULL,
  `audit_name` varchar(255) NOT NULL,
  `audit_type` enum('Interna','Externa','Certificación','Seguimiento','Primera Parte','Segunda Parte','Tercera Parte') NOT NULL,
  `audit_scope` text NOT NULL,
  `audit_objectives` text DEFAULT NULL,
  `audit_criteria` text DEFAULT NULL COMMENT 'ISO 27001:2022, políticas internas, etc.',
  `audit_standard` varchar(100) DEFAULT 'ISO/IEC 27001:2022',
  `audit_methodology` text DEFAULT NULL,
  `planned_date_from` date DEFAULT NULL,
  `planned_date_to` date DEFAULT NULL,
  `actual_date_from` date DEFAULT NULL,
  `actual_date_to` date DEFAULT NULL,
  `lead_auditor` int(11) DEFAULT NULL,
  `audit_team` text DEFAULT NULL COMMENT 'JSON con equipo auditor',
  `audited_areas` text DEFAULT NULL COMMENT 'Áreas/procesos auditados',
  `auditees` text DEFAULT NULL COMMENT 'JSON con auditados',
  `status` enum('Planned','Notified','In Progress','Report Draft','Report Final','Closed','Cancelled') DEFAULT 'Planned',
  `audit_result` enum('Conforme','Conforme con Observaciones','No Conforme','Pendiente') DEFAULT 'Pendiente',
  `findings_major` int(11) DEFAULT 0,
  `findings_minor` int(11) DEFAULT 0,
  `observations` int(11) DEFAULT 0,
  `opportunities` int(11) DEFAULT 0,
  `report_issued_date` date DEFAULT NULL,
  `report_file_path` varchar(500) DEFAULT NULL,
  `follow_up_required` tinyint(1) DEFAULT 0,
  `follow_up_date` date DEFAULT NULL,
  `closure_date` date DEFAULT NULL,
  `closure_approved_by` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_by` int(11) DEFAULT NULL,
  `updated_date` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_audit` (`company_id`,`audit_code`),
  KEY `company_id` (`company_id`),
  KEY `program_id` (`program_id`),
  KEY `status` (`status`),
  KEY `audit_type` (`audit_type`),
  CONSTRAINT `fk_audit_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_audit_program` FOREIGN KEY (`program_id`) REFERENCES `audit_program` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: Checklist de auditoría
CREATE TABLE IF NOT EXISTS `audit_checklists` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `checklist_code` varchar(50) NOT NULL,
  `checklist_name` varchar(255) NOT NULL,
  `audit_type` enum('Interna','Externa','Certificación','Seguimiento') DEFAULT NULL,
  `standard` varchar(100) DEFAULT 'ISO/IEC 27001:2022',
  `description` text DEFAULT NULL,
  `checklist_items` text NOT NULL COMMENT 'JSON con items del checklist',
  `is_template` tinyint(1) DEFAULT 1,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_date` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_checklist` (`company_id`,`checklist_code`),
  KEY `company_id` (`company_id`),
  CONSTRAINT `fk_checklist_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: Items de checklist evaluados
CREATE TABLE IF NOT EXISTS `audit_checklist_evaluations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `audit_id` int(11) NOT NULL,
  `checklist_id` int(11) NOT NULL,
  `item_number` varchar(20) NOT NULL,
  `question` text NOT NULL,
  `reference` varchar(255) DEFAULT NULL,
  `result` enum('Conforme','No Conforme','No Aplica','Observación') DEFAULT NULL,
  `evidence` text DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `auditor` int(11) DEFAULT NULL,
  `evaluation_date` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `audit_id` (`audit_id`),
  KEY `checklist_id` (`checklist_id`),
  CONSTRAINT `fk_eval_audit` FOREIGN KEY (`audit_id`) REFERENCES `audits` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_eval_checklist` FOREIGN KEY (`checklist_id`) REFERENCES `audit_checklists` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: Hallazgos de auditoría
CREATE TABLE IF NOT EXISTS `audit_findings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `audit_id` int(11) NOT NULL,
  `finding_code` varchar(50) NOT NULL,
  `finding_type` enum('No Conformidad Mayor','No Conformidad Menor','Observación','Oportunidad de Mejora') NOT NULL,
  `clause` varchar(50) DEFAULT NULL,
  `requirement` text NOT NULL,
  `finding_description` text NOT NULL,
  `objective_evidence` text DEFAULT NULL,
  `audited_area` varchar(255) DEFAULT NULL,
  `auditee` varchar(255) DEFAULT NULL,
  `root_cause` text DEFAULT NULL,
  `risk_level` enum('Bajo','Medio','Alto','Crítico') DEFAULT NULL,
  `status` enum('Open','Corrective Action Planned','In Progress','Closed','Verified') DEFAULT 'Open',
  `responsible` int(11) DEFAULT NULL,
  `target_date` date DEFAULT NULL,
  `completion_date` date DEFAULT NULL,
  `corrective_action` text DEFAULT NULL,
  `effectiveness_verification` text DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `verification_date` date DEFAULT NULL,
  `attachments` text DEFAULT NULL COMMENT 'JSON con archivos adjuntos',
  `created_by` int(11) DEFAULT NULL,
  `created_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_by` int(11) DEFAULT NULL,
  `updated_date` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_finding` (`audit_id`,`finding_code`),
  KEY `audit_id` (`audit_id`),
  KEY `finding_type` (`finding_type`),
  KEY `status` (`status`),
  CONSTRAINT `fk_finding_audit` FOREIGN KEY (`audit_id`) REFERENCES `audits` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: Reuniones de auditoría
CREATE TABLE IF NOT EXISTS `audit_meetings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `audit_id` int(11) NOT NULL,
  `meeting_type` enum('Apertura','Cierre','Seguimiento','Aclaración','Otra') NOT NULL,
  `meeting_date` datetime NOT NULL,
  `duration` int(11) DEFAULT NULL COMMENT 'Minutos',
  `location` varchar(255) DEFAULT NULL,
  `attendees` text DEFAULT NULL COMMENT 'JSON con asistentes',
  `agenda` text DEFAULT NULL,
  `minutes` text DEFAULT NULL,
  `agreements` text DEFAULT NULL,
  `next_steps` text DEFAULT NULL,
  `minutes_file_path` varchar(500) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_date` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `audit_id` (`audit_id`),
  CONSTRAINT `fk_meeting_audit` FOREIGN KEY (`audit_id`) REFERENCES `audits` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: Auditores (internos y externos)
CREATE TABLE IF NOT EXISTS `auditors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `auditor_code` varchar(50) DEFAULT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `auditor_type` enum('Interno','Externo','Lead Auditor','Auditor','Auditor en Formación') NOT NULL,
  `certifications` text DEFAULT NULL COMMENT 'JSON con certificaciones',
  `specializations` text DEFAULT NULL COMMENT 'JSON con especializaciones',
  `qualification_date` date DEFAULT NULL,
  `requalification_date` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_date` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`),
  KEY `auditor_type` (`auditor_type`),
  CONSTRAINT `fk_auditor_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: Evidencias de auditoría
CREATE TABLE IF NOT EXISTS `audit_evidence` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `audit_id` int(11) NOT NULL,
  `finding_id` int(11) DEFAULT NULL,
  `evidence_type` enum('Documento','Foto','Video','Registro','Entrevista','Observación','Otro') NOT NULL,
  `evidence_description` text NOT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `collected_by` int(11) DEFAULT NULL,
  `collection_date` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `audit_id` (`audit_id`),
  KEY `finding_id` (`finding_id`),
  CONSTRAINT `fk_evidence_audit` FOREIGN KEY (`audit_id`) REFERENCES `audits` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_evidence_finding` FOREIGN KEY (`finding_id`) REFERENCES `audit_findings` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: Comentarios/notas de auditoría
CREATE TABLE IF NOT EXISTS `audit_notes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `audit_id` int(11) NOT NULL,
  `note_type` enum('General','Observación','Punto Fuerte','Área de Mejora','Seguimiento') DEFAULT 'General',
  `note_text` text NOT NULL,
  `is_private` tinyint(1) DEFAULT 0 COMMENT 'Solo visible para equipo auditor',
  `created_by` int(11) NOT NULL,
  `created_date` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `audit_id` (`audit_id`),
  CONSTRAINT `fk_note_audit` FOREIGN KEY (`audit_id`) REFERENCES `audits` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
