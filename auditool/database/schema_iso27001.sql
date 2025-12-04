-- ============================================
-- AUDITOR PRO - Sistema Multi-ISO
-- Schema SQL Completo para ISO 27001:2022
-- Base de Datos: conectae_isogestionbd
-- ============================================

-- Establecer charset
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================
-- TABLA: companies (Empresas)
-- ============================================
CREATE TABLE IF NOT EXISTS `companies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_name` varchar(255) NOT NULL,
  `company_rut` varchar(50) DEFAULT NULL,
  `company_address` text DEFAULT NULL,
  `company_city` varchar(100) DEFAULT NULL,
  `company_country` varchar(50) DEFAULT 'CL',
  `company_phone` varchar(50) DEFAULT NULL,
  `company_email` varchar(255) DEFAULT NULL,
  `company_logo` varchar(255) DEFAULT NULL,
  `active_isos` text DEFAULT NULL COMMENT 'JSON con ISOs activas',
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `created_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_date` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_rut` (`company_rut`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: users (Usuarios)
-- ============================================
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `role` enum('superadmin','admin','auditor','user','readonly') DEFAULT 'user',
  `language` varchar(5) DEFAULT 'es',
  `timezone` varchar(50) DEFAULT 'America/Santiago',
  `last_login` datetime DEFAULT NULL,
  `login_attempts` int(11) DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `status` enum('active','inactive','locked') DEFAULT 'active',
  `created_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_date` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `company_id` (`company_id`),
  CONSTRAINT `fk_users_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: activity_logs (Registro de Actividad)
-- ============================================
CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `module` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `created_at` (`created_at`),
  KEY `module` (`module`),
  CONSTRAINT `fk_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: iso27001_assets (Activos de Información)
-- ============================================
CREATE TABLE IF NOT EXISTS `iso27001_assets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `asset_id` varchar(50) NOT NULL,
  `asset_name` varchar(255) NOT NULL,
  `asset_type` enum('Hardware','Software','Información','Servicios','Personal','Instalaciones','Otros') NOT NULL,
  `asset_category` varchar(100) DEFAULT NULL,
  `asset_owner` varchar(255) NOT NULL,
  `asset_custodian` varchar(255) DEFAULT NULL,
  `asset_location` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `criticality` enum('Muy Alta','Alta','Media','Baja','Muy Baja') DEFAULT 'Media',
  `confidentiality` tinyint(4) DEFAULT 1 CHECK (`confidentiality` BETWEEN 1 AND 5),
  `integrity` tinyint(4) DEFAULT 1 CHECK (`integrity` BETWEEN 1 AND 5),
  `availability` tinyint(4) DEFAULT 1 CHECK (`availability` BETWEEN 1 AND 5),
  `data_classification` enum('Público','Interno','Confidencial','Secreto') DEFAULT 'Interno',
  `replacement_cost` decimal(15,2) DEFAULT NULL,
  `legal_requirements` text DEFAULT NULL,
  `last_review` date DEFAULT NULL,
  `next_review` date DEFAULT NULL,
  `status` enum('Activo','Inactivo','En Mantenimiento','Retirado') DEFAULT 'Activo',
  `created_by` int(11) DEFAULT NULL,
  `created_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_by` int(11) DEFAULT NULL,
  `updated_date` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_asset` (`company_id`,`asset_id`),
  KEY `company_id` (`company_id`),
  KEY `asset_type` (`asset_type`),
  KEY `criticality` (`criticality`),
  CONSTRAINT `fk_assets_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: iso27001_risks (Análisis de Riesgos)
-- ============================================
CREATE TABLE IF NOT EXISTS `iso27001_risks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `risk_id` varchar(50) NOT NULL,
  `risk_name` varchar(255) NOT NULL,
  `risk_category` enum('Tecnológico','Operacional','Legal','Estratégico','Financiero','Reputacional','Otros') NOT NULL,
  `related_asset` varchar(100) DEFAULT NULL,
  `threat` text NOT NULL,
  `vulnerability` text NOT NULL,
  `probability` tinyint(4) NOT NULL CHECK (`probability` BETWEEN 1 AND 5),
  `impact` tinyint(4) NOT NULL CHECK (`impact` BETWEEN 1 AND 5),
  `risk_score` int(11) GENERATED ALWAYS AS (`probability` * `impact`) STORED,
  `risk_level` enum('Crítico','Alto','Medio','Bajo') NOT NULL,
  `existing_controls` text DEFAULT NULL,
  `treatment_option` enum('Mitigar','Transferir','Aceptar','Evitar') DEFAULT NULL,
  `treatment_plan` text DEFAULT NULL,
  `treatment_responsible` varchar(255) DEFAULT NULL,
  `treatment_deadline` date DEFAULT NULL,
  `treatment_status` enum('Pendiente','En Proceso','Completado','Cancelado') DEFAULT 'Pendiente',
  `treatment_cost` decimal(15,2) DEFAULT NULL,
  `residual_probability` tinyint(4) DEFAULT 1 CHECK (`residual_probability` BETWEEN 1 AND 5),
  `residual_impact` tinyint(4) DEFAULT 1 CHECK (`residual_impact` BETWEEN 1 AND 5),
  `residual_score` int(11) GENERATED ALWAYS AS (`residual_probability` * `residual_impact`) STORED,
  `residual_level` enum('Crítico','Alto','Medio','Bajo') DEFAULT 'Bajo',
  `review_frequency` enum('Mensual','Trimestral','Semestral','Anual') DEFAULT 'Semestral',
  `last_review` date DEFAULT NULL,
  `next_review` date DEFAULT NULL,
  `status` enum('Activo','Cerrado','En Revisión') DEFAULT 'Activo',
  `created_by` int(11) DEFAULT NULL,
  `created_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_by` int(11) DEFAULT NULL,
  `updated_date` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_risk` (`company_id`,`risk_id`),
  KEY `company_id` (`company_id`),
  KEY `risk_level` (`risk_level`),
  KEY `treatment_status` (`treatment_status`),
  CONSTRAINT `fk_risks_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: iso27001_controls (Controles Anexo A)
-- ============================================
CREATE TABLE IF NOT EXISTS `iso27001_controls` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `control_id` varchar(20) NOT NULL COMMENT 'Ej: 5.1, A.8.2',
  `control_name` varchar(500) NOT NULL,
  `control_category` varchar(100) DEFAULT NULL,
  `applicable` enum('Si','No') DEFAULT 'Si',
  `justification` text DEFAULT NULL COMMENT 'Justificación si No Aplica',
  `implementation_status` enum('Implementado','Parcialmente Implementado','Planeado','No Implementado') DEFAULT 'Planeado',
  `implementation_description` text DEFAULT NULL,
  `responsible` varchar(255) DEFAULT NULL,
  `effectiveness` enum('Efectivo','Parcialmente Efectivo','Inefectivo','No Evaluado') DEFAULT 'No Evaluado',
  `test_date` date DEFAULT NULL,
  `test_result` text DEFAULT NULL,
  `evidence` text DEFAULT NULL,
  `observations` text DEFAULT NULL,
  `improvement_plan` text DEFAULT NULL,
  `review_frequency` enum('Mensual','Trimestral','Semestral','Anual') DEFAULT 'Anual',
  `last_review` date DEFAULT NULL,
  `next_review` date DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_by` int(11) DEFAULT NULL,
  `updated_date` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_control` (`company_id`,`control_id`),
  KEY `company_id` (`company_id`),
  KEY `implementation_status` (`implementation_status`),
  KEY `applicable` (`applicable`),
  CONSTRAINT `fk_controls_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: iso27001_incidents (Incidentes de Seguridad)
-- ============================================
CREATE TABLE IF NOT EXISTS `iso27001_incidents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `incident_id` varchar(50) NOT NULL,
  `incident_title` varchar(255) NOT NULL,
  `incident_type` enum('Acceso no autorizado','Pérdida de datos','Malware','Phishing','DDoS','Fuga de información','Otro') NOT NULL,
  `incident_date` datetime NOT NULL,
  `detection_date` datetime NOT NULL,
  `reported_date` datetime NOT NULL,
  `reported_by` varchar(255) NOT NULL,
  `affected_systems` text DEFAULT NULL,
  `affected_data` text DEFAULT NULL,
  `affected_users` int(11) DEFAULT NULL,
  `severity` enum('Crítica','Alta','Media','Baja') NOT NULL,
  `description` text NOT NULL,
  `initial_assessment` text DEFAULT NULL,
  `root_cause` text DEFAULT NULL,
  `immediate_actions` text DEFAULT NULL,
  `containment_actions` text DEFAULT NULL,
  `eradication_actions` text DEFAULT NULL,
  `recovery_actions` text DEFAULT NULL,
  `corrective_actions` text DEFAULT NULL,
  `preventive_actions` text DEFAULT NULL,
  `lessons_learned` text DEFAULT NULL,
  `assigned_to` varchar(255) DEFAULT NULL,
  `incident_status` enum('Abierto','En Investigación','Contenido','Erradicado','Recuperado','Cerrado') DEFAULT 'Abierto',
  `resolution_date` datetime DEFAULT NULL,
  `downtime_minutes` int(11) DEFAULT NULL,
  `financial_impact` decimal(15,2) DEFAULT NULL,
  `regulatory_report_required` enum('Si','No') DEFAULT 'No',
  `regulatory_report_date` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_by` int(11) DEFAULT NULL,
  `updated_date` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_incident` (`company_id`,`incident_id`),
  KEY `company_id` (`company_id`),
  KEY `severity` (`severity`),
  KEY `incident_status` (`incident_status`),
  KEY `incident_date` (`incident_date`),
  CONSTRAINT `fk_incidents_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: iso27001_policies (Políticas)
-- ============================================
CREATE TABLE IF NOT EXISTS `iso27001_policies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `policy_id` varchar(50) NOT NULL,
  `policy_name` varchar(255) NOT NULL,
  `policy_category` varchar(100) DEFAULT NULL,
  `policy_version` varchar(20) DEFAULT '1.0',
  `policy_objective` text DEFAULT NULL,
  `policy_scope` text DEFAULT NULL,
  `policy_content` longtext DEFAULT NULL,
  `approval_status` enum('Borrador','En Revisión','Aprobado','Obsoleto') DEFAULT 'Borrador',
  `approved_by` varchar(255) DEFAULT NULL,
  `approval_date` date DEFAULT NULL,
  `effective_date` date DEFAULT NULL,
  `review_frequency` enum('Mensual','Trimestral','Semestral','Anual','Bianual') DEFAULT 'Anual',
  `last_review` date DEFAULT NULL,
  `next_review` date DEFAULT NULL,
  `document_link` varchar(500) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_by` int(11) DEFAULT NULL,
  `updated_date` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_policy` (`company_id`,`policy_id`),
  KEY `company_id` (`company_id`),
  KEY `approval_status` (`approval_status`),
  CONSTRAINT `fk_policies_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: iso27001_audits (Auditorías)
-- ============================================
CREATE TABLE IF NOT EXISTS `iso27001_audits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `audit_id` varchar(50) NOT NULL,
  `audit_type` enum('Interna','Externa','Certificación','Seguimiento') NOT NULL,
  `audit_scope` text NOT NULL,
  `audit_date_from` date NOT NULL,
  `audit_date_to` date NOT NULL,
  `lead_auditor` varchar(255) NOT NULL,
  `audit_team` text DEFAULT NULL COMMENT 'Lista de auditores separados por coma',
  `audited_areas` text NOT NULL,
  `audit_standard` varchar(100) DEFAULT 'ISO/IEC 27001:2022',
  `audit_objective` text DEFAULT NULL,
  `audit_status` enum('Planificada','En Proceso','Completada','Cancelada') DEFAULT 'Planificada',
  `audit_result` enum('Conforme','Conforme con Hallazgos','No Conforme') DEFAULT NULL,
  `findings_summary` text DEFAULT NULL,
  `opportunities_improvement` text DEFAULT NULL,
  `report_link` varchar(500) DEFAULT NULL,
  `follow_up_date` date DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_by` int(11) DEFAULT NULL,
  `updated_date` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_audit` (`company_id`,`audit_id`),
  KEY `company_id` (`company_id`),
  KEY `audit_type` (`audit_type`),
  KEY `audit_status` (`audit_status`),
  KEY `audit_date_from` (`audit_date_from`),
  CONSTRAINT `fk_audits_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: iso27001_nonconformities (No Conformidades)
-- ============================================
CREATE TABLE IF NOT EXISTS `iso27001_nonconformities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `nc_id` varchar(50) NOT NULL,
  `nc_type` enum('Mayor','Menor','Observación') NOT NULL,
  `nc_title` varchar(255) NOT NULL,
  `nc_description` text NOT NULL,
  `related_clause` varchar(50) DEFAULT NULL COMMENT 'Ej: 6.1.2, A.5.1',
  `detected_in` enum('Auditoría Interna','Auditoría Externa','Auditoría de Certificación','Revisión por la Dirección','Autoevaluación','Incidente') NOT NULL,
  `detected_date` date NOT NULL,
  `responsible` varchar(255) NOT NULL,
  `root_cause` text DEFAULT NULL,
  `root_cause_method` enum('5 Porqués','Ishikawa','Análisis de Pareto','Otro') DEFAULT NULL,
  `corrective_action` text DEFAULT NULL,
  `preventive_action` text DEFAULT NULL,
  `action_responsible` varchar(255) DEFAULT NULL,
  `action_deadline` date DEFAULT NULL,
  `action_completion_date` date DEFAULT NULL,
  `verification_method` text DEFAULT NULL,
  `verification_date` date DEFAULT NULL,
  `verified_by` varchar(255) DEFAULT NULL,
  `verification_result` enum('Efectiva','Parcialmente Efectiva','Inefectiva') DEFAULT NULL,
  `nc_status` enum('Abierta','En Análisis','En Corrección','En Verificación','Cerrada','Reabierta') DEFAULT 'Abierta',
  `closure_date` date DEFAULT NULL,
  `closure_evidence` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_by` int(11) DEFAULT NULL,
  `updated_date` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_nc` (`company_id`,`nc_id`),
  KEY `company_id` (`company_id`),
  KEY `nc_type` (`nc_type`),
  KEY `nc_status` (`nc_status`),
  KEY `detected_date` (`detected_date`),
  CONSTRAINT `fk_nc_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: iso27001_templates (Biblioteca de Plantillas)
-- ============================================
CREATE TABLE IF NOT EXISTS `iso27001_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `template_name` varchar(255) NOT NULL,
  `template_category` enum('formatos_excel','documentos_word','anexos','matrices','politicas','otros') NOT NULL,
  `template_description` text DEFAULT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_type` varchar(10) NOT NULL COMMENT 'xlsx, docx, pdf',
  `file_size` int(11) DEFAULT NULL,
  `version` varchar(20) DEFAULT '1.0',
  `uploaded_by` int(11) DEFAULT NULL,
  `uploaded_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `download_count` int(11) DEFAULT 0,
  `last_download` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`),
  KEY `template_category` (`template_category`),
  KEY `uploaded_by` (`uploaded_by`),
  CONSTRAINT `fk_templates_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_templates_user` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: subscriptions (Suscripciones)
-- ============================================
CREATE TABLE IF NOT EXISTS `subscriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `plan_name` enum('basic','professional','enterprise','custom') NOT NULL,
  `plan_price` decimal(10,2) NOT NULL,
  `plan_currency` varchar(5) DEFAULT 'USD',
  `billing_cycle` enum('monthly','yearly') DEFAULT 'monthly',
  `status` enum('trial','active','suspended','cancelled','expired','pending') DEFAULT 'pending',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `trial_start_date` date DEFAULT NULL,
  `trial_end_date` date DEFAULT NULL,
  `next_billing_date` date DEFAULT NULL,
  `max_users` int(11) NOT NULL DEFAULT 5,
  `max_companies` int(11) NOT NULL DEFAULT 1,
  `max_isos` int(11) NOT NULL DEFAULT 3,
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_status` enum('pending','paid','failed','refunded') DEFAULT 'pending',
  `last_payment_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_date` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`),
  KEY `status` (`status`),
  KEY `plan_name` (`plan_name`),
  CONSTRAINT `fk_subscriptions_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: payments (Pagos)
-- ============================================
CREATE TABLE IF NOT EXISTS `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subscription_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `payment_amount` decimal(10,2) NOT NULL,
  `payment_currency` varchar(5) DEFAULT 'USD',
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_status` enum('pending','completed','failed','refunded') DEFAULT 'pending',
  `transaction_id` varchar(255) DEFAULT NULL,
  `payment_date` datetime DEFAULT NULL,
  `payment_details` text DEFAULT NULL COMMENT 'JSON con detalles del pago',
  `created_date` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `subscription_id` (`subscription_id`),
  KEY `company_id` (`company_id`),
  KEY `payment_status` (`payment_status`),
  CONSTRAINT `fk_payments_subscription` FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_payments_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- INSERTAR DATOS INICIALES
-- ============================================

-- Empresa AuditorEx Chile (Superadmin)
INSERT INTO `companies` (`id`, `company_name`, `company_rut`, `company_country`, `company_email`, `active_isos`, `status`) VALUES
(1, 'AuditorEx Chile', '76.123.456-7', 'CL', 'auditorexchile@gmail.com', '["iso_27001","iso_22301","iso_37001","iso_9001","iso_14001","iso_45001","iso_31000","iso_50001","iso_20000","iso_22000","iso_27017","iso_27701","iso_13485","iso_28000"]', 'active');

-- Usuario Superadmin de AuditorEx Chile (email: auditorexchile@gmail.com, password: Sistemas40&)
INSERT INTO `users` (`id`, `company_id`, `username`, `email`, `password`, `full_name`, `role`, `language`, `status`) VALUES
(1, 1, 'auditorexchile', 'auditorexchile@gmail.com', '$2y$10$VQC8JCPZmVz.mEKqwZE3W.xJR7Y6HZK5vGBhQXqJ9FXzqN0JjKVZe', 'AuditorEx Chile Administrador', 'superadmin', 'es', 'active');

-- Suscripción Enterprise para AuditorEx Chile (acceso total)
INSERT INTO `subscriptions` (`id`, `company_id`, `plan_name`, `plan_price`, `status`, `start_date`, `max_users`, `max_companies`, `max_isos`) VALUES
(1, 1, 'enterprise', 999.00, 'active', CURDATE(), 999, 999, 14);

-- Empresa de ejemplo
INSERT INTO `companies` (`id`, `company_name`, `company_rut`, `company_country`, `company_email`, `active_isos`, `status`) VALUES
(2, 'Empresa Demo', '12345678-9', 'CL', 'info@empresademo.cl', '["iso_27001"]', 'active');

-- Usuario administrador de ejemplo (password: Admin123!)
INSERT INTO `users` (`id`, `company_id`, `username`, `email`, `password`, `full_name`, `role`, `language`, `status`) VALUES
(2, 2, 'admin', 'admin@empresademo.cl', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador', 'admin', 'es', 'active');

-- Suscripción Trial de 5 días para Empresa Demo
INSERT INTO `subscriptions` (`id`, `company_id`, `plan_name`, `plan_price`, `status`, `trial_start_date`, `trial_end_date`, `max_users`, `max_companies`, `max_isos`) VALUES
(2, 2, 'basic', 99.00, 'trial', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 5 DAY), 5, 1, 3);

-- ============================================
-- ÍNDICES ADICIONALES PARA OPTIMIZACIÓN
-- ============================================

-- Índices para búsquedas frecuentes
CREATE INDEX idx_assets_owner ON iso27001_assets(asset_owner);
CREATE INDEX idx_risks_treatment ON iso27001_risks(treatment_responsible, treatment_deadline);
CREATE INDEX idx_controls_responsible ON iso27001_controls(responsible);
CREATE INDEX idx_incidents_severity_status ON iso27001_incidents(severity, incident_status);
CREATE INDEX idx_audits_date_range ON iso27001_audits(audit_date_from, audit_date_to);
CREATE INDEX idx_nc_deadline ON iso27001_nonconformities(action_deadline, nc_status);

-- ============================================
-- VISTAS ÚTILES
-- ============================================

-- Vista de Riesgos Críticos
CREATE OR REPLACE VIEW v_critical_risks AS
SELECT r.*, c.company_name
FROM iso27001_risks r
JOIN companies c ON r.company_id = c.id
WHERE r.risk_level IN ('Crítico', 'Alto')
  AND r.status = 'Activo'
ORDER BY r.risk_score DESC;

-- Vista de Controles No Implementados
CREATE OR REPLACE VIEW v_pending_controls AS
SELECT c.*, co.company_name
FROM iso27001_controls c
JOIN companies co ON c.company_id = co.id
WHERE c.applicable = 'Si'
  AND c.implementation_status IN ('Planeado', 'No Implementado')
ORDER BY c.control_id;

-- Vista de Incidentes Abiertos
CREATE OR REPLACE VIEW v_open_incidents AS
SELECT i.*, c.company_name
FROM iso27001_incidents i
JOIN companies c ON i.company_id = c.id
WHERE i.incident_status IN ('Abierto', 'En Investigación', 'Contenido')
ORDER BY i.severity DESC, i.incident_date DESC;

-- Vista de No Conformidades Pendientes
CREATE OR REPLACE VIEW v_pending_nc AS
SELECT n.*, c.company_name
FROM iso27001_nonconformities n
JOIN companies c ON n.company_id = c.id
WHERE n.nc_status IN ('Abierta', 'En Análisis', 'En Corrección')
  AND (n.action_deadline IS NULL OR n.action_deadline >= CURDATE())
ORDER BY n.detected_date DESC;

-- ============================================
-- TRIGGERS
-- ============================================

-- Trigger para calcular nivel de riesgo automáticamente
DELIMITER //
CREATE TRIGGER tr_risk_level_before_insert
BEFORE INSERT ON iso27001_risks
FOR EACH ROW
BEGIN
    DECLARE score INT;
    SET score = NEW.probability * NEW.impact;
    SET NEW.risk_level = CASE
        WHEN score >= 15 THEN 'Crítico'
        WHEN score >= 8 THEN 'Alto'
        WHEN score >= 4 THEN 'Medio'
        ELSE 'Bajo'
    END;

    SET score = NEW.residual_probability * NEW.residual_impact;
    SET NEW.residual_level = CASE
        WHEN score >= 15 THEN 'Crítico'
        WHEN score >= 8 THEN 'Alto'
        WHEN score >= 4 THEN 'Medio'
        ELSE 'Bajo'
    END;
END//

CREATE TRIGGER tr_risk_level_before_update
BEFORE UPDATE ON iso27001_risks
FOR EACH ROW
BEGIN
    DECLARE score INT;
    SET score = NEW.probability * NEW.impact;
    SET NEW.risk_level = CASE
        WHEN score >= 15 THEN 'Crítico'
        WHEN score >= 8 THEN 'Alto'
        WHEN score >= 4 THEN 'Medio'
        ELSE 'Bajo'
    END;

    SET score = NEW.residual_probability * NEW.residual_impact;
    SET NEW.residual_level = CASE
        WHEN score >= 15 THEN 'Crítico'
        WHEN score >= 8 THEN 'Alto'
        WHEN score >= 4 THEN 'Medio'
        ELSE 'Bajo'
    END;
END//
DELIMITER ;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- FIN DEL SCRIPT
-- ============================================

-- Verificar tablas creadas
SHOW TABLES;

-- Mostrar estructura de tabla de ejemplo
-- DESCRIBE iso27001_assets;
