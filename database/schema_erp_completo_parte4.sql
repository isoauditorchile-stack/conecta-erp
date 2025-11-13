-- =====================================================
-- CONECTA ERP v2.0.0 - SCHEMA COMPLETO PARTE 4
-- Módulos: HCM (RRHH), SCM, CRM, LOY, BI (15 submódulos)
-- INTEGRACIONES CRÍTICAS: Previred, SII, Relojes Control
-- =====================================================

-- =====================================================
-- MÓDULO 8: GESTIÓN DE CAPITAL HUMANO (HCM/RRHH) - 11 Submódulos
-- INCLUYE: Integración con Previred (Sistema de Nómina Chileno)
-- =====================================================

-- Empleados
CREATE TABLE IF NOT EXISTS `hcm_employees` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `employee_number` VARCHAR(50) NOT NULL,
  `user_id` INT(11) UNSIGNED,
  `first_name` VARCHAR(100) NOT NULL,
  `middle_name` VARCHAR(100),
  `last_name` VARCHAR(100) NOT NULL,
  `second_last_name` VARCHAR(100),
  `full_name` VARCHAR(255) NOT NULL,
  `tax_id` VARCHAR(50) NOT NULL,
  `birth_date` DATE NOT NULL,
  `gender` ENUM('male', 'female', 'other', 'prefer_not_to_say') NOT NULL,
  `marital_status` ENUM('single', 'married', 'divorced', 'widowed', 'other') NOT NULL,
  `nationality` VARCHAR(3) DEFAULT 'CL',
  `email_personal` VARCHAR(150),
  `email_work` VARCHAR(150),
  `phone_personal` VARCHAR(50),
  `phone_work` VARCHAR(50),
  `mobile` VARCHAR(50),
  `address` TEXT,
  `city` VARCHAR(100),
  `state_province` VARCHAR(100),
  `postal_code` VARCHAR(20),
  `country` VARCHAR(3) DEFAULT 'CL',
  `emergency_contact_name` VARCHAR(150),
  `emergency_contact_phone` VARCHAR(50),
  `emergency_contact_relationship` VARCHAR(50),
  `hire_date` DATE NOT NULL,
  `termination_date` DATE,
  `seniority_date` DATE,
  `department` VARCHAR(100),
  `position` VARCHAR(100) NOT NULL,
  `job_title` VARCHAR(150),
  `employment_type` ENUM('full_time', 'part_time', 'contractor', 'temporary', 'intern') DEFAULT 'full_time',
  `contract_type` ENUM('indefinite', 'fixed_term', 'project_based', 'seasonal') DEFAULT 'indefinite',
  `work_schedule` VARCHAR(50),
  `manager_id` INT(11) UNSIGNED,
  `cost_center_id` INT(11) UNSIGNED,
  `status` ENUM('active', 'on_leave', 'suspended', 'terminated') DEFAULT 'active',
  `photo_url` VARCHAR(255),
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`),
  FOREIGN KEY (`manager_id`) REFERENCES `hcm_employees`(`id`),
  UNIQUE KEY `company_employee_number` (`company_id`, `employee_number`),
  UNIQUE KEY `company_tax_id` (`company_id`, `tax_id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_department` (`department`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Contratos de Trabajo
CREATE TABLE IF NOT EXISTS `hcm_employment_contracts` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `employee_id` INT(11) UNSIGNED NOT NULL,
  `contract_number` VARCHAR(50) NOT NULL,
  `contract_type` ENUM('indefinite', 'fixed_term', 'project_based', 'seasonal') NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE,
  `job_title` VARCHAR(150) NOT NULL,
  `department` VARCHAR(100),
  `work_location` VARCHAR(200),
  `weekly_hours` DECIMAL(5,2) DEFAULT 45.00,
  `probation_period_days` INT(11) DEFAULT 90,
  `notice_period_days` INT(11) DEFAULT 30,
  `contract_terms` TEXT,
  `signed_date` DATE,
  `signed_by_employee` TINYINT(1) DEFAULT 0,
  `signed_by_employer` TINYINT(1) DEFAULT 0,
  `contract_document_url` VARCHAR(255),
  `status` ENUM('draft', 'active', 'expired', 'terminated', 'renewed') DEFAULT 'draft',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`employee_id`) REFERENCES `hcm_employees`(`id`) ON DELETE CASCADE,
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Compensaciones y Salarios
CREATE TABLE IF NOT EXISTS `hcm_compensation` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `employee_id` INT(11) UNSIGNED NOT NULL,
  `effective_date` DATE NOT NULL,
  `salary_type` ENUM('monthly', 'hourly', 'daily', 'commission') DEFAULT 'monthly',
  `base_salary` DECIMAL(18,2) NOT NULL,
  `currency` VARCHAR(3) DEFAULT 'CLP',
  `hourly_rate` DECIMAL(18,2),
  `overtime_rate` DECIMAL(18,2),
  `payment_frequency` ENUM('weekly', 'biweekly', 'monthly', 'semimonthly') DEFAULT 'monthly',
  `payment_method` ENUM('bank_transfer', 'check', 'cash') DEFAULT 'bank_transfer',
  `bank_name` VARCHAR(100),
  `bank_account_type` ENUM('checking', 'savings') DEFAULT 'checking',
  `bank_account_number` VARCHAR(50),
  `afp_name` VARCHAR(100),
  `afp_percentage` DECIMAL(5,2) DEFAULT 10.00,
  `health_insurance_name` VARCHAR(100),
  `health_insurance_type` ENUM('fonasa', 'isapre') DEFAULT 'fonasa',
  `health_insurance_percentage` DECIMAL(5,2) DEFAULT 7.00,
  `health_insurance_uf` DECIMAL(5,2),
  `unemployment_insurance` TINYINT(1) DEFAULT 1,
  `status` ENUM('active', 'superseded', 'cancelled') DEFAULT 'active',
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`employee_id`) REFERENCES `hcm_employees`(`id`) ON DELETE CASCADE,
  INDEX `idx_effective_date` (`effective_date`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Nómina (Payroll)
CREATE TABLE IF NOT EXISTS `hcm_payroll_periods` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `period_name` VARCHAR(100) NOT NULL,
  `period_type` ENUM('weekly', 'biweekly', 'monthly', 'semimonthly') DEFAULT 'monthly',
  `period_year` INT(4) NOT NULL,
  `period_month` INT(2) NOT NULL,
  `period_number` INT(2) NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `payment_date` DATE NOT NULL,
  `total_employees` INT(11) DEFAULT 0,
  `total_gross_pay` DECIMAL(18,2) DEFAULT 0.00,
  `total_deductions` DECIMAL(18,2) DEFAULT 0.00,
  `total_net_pay` DECIMAL(18,2) DEFAULT 0.00,
  `total_employer_contributions` DECIMAL(18,2) DEFAULT 0.00,
  `status` ENUM('open', 'calculating', 'calculated', 'approved', 'paid', 'closed', 'cancelled') DEFAULT 'open',
  `calculated_at` DATETIME,
  `approved_by` INT(11) UNSIGNED,
  `approved_at` DATETIME,
  `paid_at` DATETIME,
  `previred_sent` TINYINT(1) DEFAULT 0,
  `previred_sent_at` DATETIME,
  `previred_response` TEXT,
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `company_period` (`company_id`, `period_year`, `period_month`, `period_number`),
  INDEX `idx_status` (`status`),
  INDEX `idx_payment_date` (`payment_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Liquidaciones de Sueldo
CREATE TABLE IF NOT EXISTS `hcm_payroll_slips` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `payroll_period_id` INT(11) UNSIGNED NOT NULL,
  `employee_id` INT(11) UNSIGNED NOT NULL,
  `slip_number` VARCHAR(50) NOT NULL,
  `days_worked` DECIMAL(5,2) NOT NULL,
  `hours_worked` DECIMAL(8,2),
  `overtime_hours` DECIMAL(8,2) DEFAULT 0.00,
  `base_salary` DECIMAL(18,2) NOT NULL,
  `overtime_pay` DECIMAL(18,2) DEFAULT 0.00,
  `bonuses` DECIMAL(18,2) DEFAULT 0.00,
  `commissions` DECIMAL(18,2) DEFAULT 0.00,
  `allowances` DECIMAL(18,2) DEFAULT 0.00,
  `gross_pay` DECIMAL(18,2) NOT NULL,
  `afp_deduction` DECIMAL(18,2) DEFAULT 0.00,
  `health_insurance_deduction` DECIMAL(18,2) DEFAULT 0.00,
  `income_tax` DECIMAL(18,2) DEFAULT 0.00,
  `other_deductions` DECIMAL(18,2) DEFAULT 0.00,
  `total_deductions` DECIMAL(18,2) NOT NULL,
  `net_pay` DECIMAL(18,2) NOT NULL,
  `employer_afp_contribution` DECIMAL(18,2) DEFAULT 0.00,
  `employer_health_contribution` DECIMAL(18,2) DEFAULT 0.00,
  `employer_unemployment_insurance` DECIMAL(18,2) DEFAULT 0.00,
  `employer_accident_insurance` DECIMAL(18,2) DEFAULT 0.00,
  `total_employer_cost` DECIMAL(18,2) NOT NULL,
  `payment_method` ENUM('bank_transfer', 'check', 'cash') DEFAULT 'bank_transfer',
  `payment_reference` VARCHAR(100),
  `status` ENUM('draft', 'calculated', 'approved', 'paid', 'cancelled') DEFAULT 'draft',
  `pdf_url` VARCHAR(255),
  `sent_to_employee` TINYINT(1) DEFAULT 0,
  `sent_at` DATETIME,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`payroll_period_id`) REFERENCES `hcm_payroll_periods`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`employee_id`) REFERENCES `hcm_employees`(`id`),
  UNIQUE KEY `period_employee` (`payroll_period_id`, `employee_id`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Detalles de Liquidación (Haberes y Descuentos)
CREATE TABLE IF NOT EXISTS `hcm_payroll_slip_details` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `payroll_slip_id` INT(11) UNSIGNED NOT NULL,
  `line_type` ENUM('earning', 'deduction', 'employer_contribution') NOT NULL,
  `concept_code` VARCHAR(50) NOT NULL,
  `concept_name` VARCHAR(200) NOT NULL,
  `calculation_basis` DECIMAL(18,2),
  `rate_percentage` DECIMAL(5,2),
  `units` DECIMAL(10,2),
  `amount` DECIMAL(18,2) NOT NULL,
  `is_taxable` TINYINT(1) DEFAULT 1,
  `is_imponible` TINYINT(1) DEFAULT 1,
  `notes` TEXT,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`payroll_slip_id`) REFERENCES `hcm_payroll_slips`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== INTEGRACIÓN PREVIRED =====
-- Exportación a Previred
CREATE TABLE IF NOT EXISTS `hcm_previred_exports` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `payroll_period_id` INT(11) UNSIGNED NOT NULL,
  `export_number` VARCHAR(50) NOT NULL,
  `export_date` DATETIME NOT NULL,
  `period_year` INT(4) NOT NULL,
  `period_month` INT(2) NOT NULL,
  `total_employees` INT(11) NOT NULL,
  `total_remuneration` DECIMAL(18,2) NOT NULL,
  `total_afp` DECIMAL(18,2) NOT NULL,
  `total_health` DECIMAL(18,2) NOT NULL,
  `total_unemployment` DECIMAL(18,2) NOT NULL,
  `file_format` ENUM('previred_txt', 'previred_excel') DEFAULT 'previred_txt',
  `file_path` VARCHAR(255),
  `file_hash` VARCHAR(64),
  `status` ENUM('generated', 'validated', 'sent', 'accepted', 'rejected', 'error') DEFAULT 'generated',
  `validation_errors` JSON,
  `sent_to_previred_at` DATETIME,
  `previred_response_code` VARCHAR(50),
  `previred_response_message` TEXT,
  `previred_folio` VARCHAR(50),
  `created_by` INT(11) UNSIGNED NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`payroll_period_id`) REFERENCES `hcm_payroll_periods`(`id`),
  UNIQUE KEY `company_export_number` (`company_id`, `export_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Detalle Empleados Previred
CREATE TABLE IF NOT EXISTS `hcm_previred_employee_data` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `previred_export_id` INT(11) UNSIGNED NOT NULL,
  `employee_id` INT(11) UNSIGNED NOT NULL,
  `rut` VARCHAR(12) NOT NULL,
  `apellido_paterno` VARCHAR(50) NOT NULL,
  `apellido_materno` VARCHAR(50),
  `nombres` VARCHAR(100) NOT NULL,
  `sexo` CHAR(1) NOT NULL,
  `nacionalidad` VARCHAR(50),
  `tipo_trabajador` VARCHAR(10) NOT NULL,
  `fecha_inicio_actividades` DATE,
  `fecha_termino` DATE,
  `region` VARCHAR(50),
  `comuna` VARCHAR(50),
  `afp_codigo` VARCHAR(10) NOT NULL,
  `afp_monto` DECIMAL(18,2) NOT NULL,
  `sis_monto` DECIMAL(18,2) DEFAULT 0.00,
  `isapre_fonasa_codigo` VARCHAR(10),
  `isapre_fonasa_monto` DECIMAL(18,2) NOT NULL,
  `cotizacion_trabajo_pesado` DECIMAL(18,2) DEFAULT 0.00,
  `seguro_cesantia_trabajador` DECIMAL(18,2) NOT NULL,
  `seguro_cesantia_empleador` DECIMAL(18,2) NOT NULL,
  `dias_trabajados` INT(11) NOT NULL,
  `dias_licencia` INT(11) DEFAULT 0,
  `subsidio` DECIMAL(18,2) DEFAULT 0.00,
  `renta_imponible` DECIMAL(18,2) NOT NULL,
  `renta_tributable` DECIMAL(18,2) NOT NULL,
  `asignacion_familiar_monto` DECIMAL(18,2) DEFAULT 0.00,
  `asignacion_familiar_cargas` INT(11) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`previred_export_id`) REFERENCES `hcm_previred_exports`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`employee_id`) REFERENCES `hcm_employees`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Asistencia (Control de Horas)
CREATE TABLE IF NOT EXISTS `hcm_attendance` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `employee_id` INT(11) UNSIGNED NOT NULL,
  `attendance_date` DATE NOT NULL,
  `clock_in` DATETIME,
  `clock_out` DATETIME,
  `break_start` DATETIME,
  `break_end` DATETIME,
  `total_hours_worked` DECIMAL(8,2),
  `regular_hours` DECIMAL(8,2),
  `overtime_hours` DECIMAL(8,2) DEFAULT 0.00,
  `late_minutes` INT(11) DEFAULT 0,
  `early_departure_minutes` INT(11) DEFAULT 0,
  `attendance_type` ENUM('present', 'absent', 'half_day', 'on_leave', 'holiday', 'weekend', 'sick') DEFAULT 'present',
  `work_shift_id` INT(11) UNSIGNED,
  `location` VARCHAR(200),
  `ip_address` VARCHAR(45),
  `device_id` VARCHAR(100),
  `time_clock_id` INT(11) UNSIGNED,
  `approved_by` INT(11) UNSIGNED,
  `status` ENUM('pending', 'approved', 'rejected', 'auto_approved') DEFAULT 'pending',
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`employee_id`) REFERENCES `hcm_employees`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `employee_attendance_date` (`employee_id`, `attendance_date`),
  INDEX `idx_attendance_date` (`attendance_date`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== INTEGRACIÓN RELOJES CONTROL =====
-- Dispositivos de Control de Asistencia (Relojes Biométricos)
CREATE TABLE IF NOT EXISTS `hcm_time_clocks` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `device_id` VARCHAR(100) NOT NULL,
  `device_name` VARCHAR(200) NOT NULL,
  `device_type` ENUM('biometric_fingerprint', 'biometric_face', 'rfid_card', 'pin_code', 'mobile_app', 'web_browser') NOT NULL,
  `manufacturer` VARCHAR(100),
  `model` VARCHAR(100),
  `serial_number` VARCHAR(100),
  `ip_address` VARCHAR(45),
  `port` INT(11),
  `location` VARCHAR(200),
  `connection_type` ENUM('lan', 'wifi', 'cloud', 'usb') DEFAULT 'lan',
  `api_endpoint` VARCHAR(255),
  `api_key` VARCHAR(255),
  `sync_frequency_minutes` INT(11) DEFAULT 15,
  `last_sync_at` DATETIME,
  `last_sync_status` ENUM('success', 'failed', 'partial') DEFAULT 'success',
  `is_active` TINYINT(1) DEFAULT 1,
  `firmware_version` VARCHAR(50),
  `configuration` JSON,
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `company_device_id` (`company_id`, `device_id`),
  INDEX `idx_last_sync` (`last_sync_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Registros de Reloj Control (Raw Data)
CREATE TABLE IF NOT EXISTS `hcm_time_clock_logs` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `time_clock_id` INT(11) UNSIGNED NOT NULL,
  `employee_id` INT(11) UNSIGNED,
  `employee_code` VARCHAR(50),
  `timestamp` DATETIME NOT NULL,
  `event_type` ENUM('clock_in', 'clock_out', 'break_start', 'break_end', 'unknown') NOT NULL,
  `verification_method` ENUM('fingerprint', 'face', 'rfid', 'pin', 'mobile', 'web') NOT NULL,
  `verification_quality` INT(11),
  `temperature` DECIMAL(4,1),
  `photo_url` VARCHAR(255),
  `raw_data` JSON,
  `is_processed` TINYINT(1) DEFAULT 0,
  `processed_at` DATETIME,
  `attendance_id` INT(11) UNSIGNED,
  `sync_batch_id` VARCHAR(50),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`time_clock_id`) REFERENCES `hcm_time_clocks`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`employee_id`) REFERENCES `hcm_employees`(`id`),
  FOREIGN KEY (`attendance_id`) REFERENCES `hcm_attendance`(`id`),
  INDEX `idx_timestamp` (`timestamp`),
  INDEX `idx_employee` (`employee_id`),
  INDEX `idx_is_processed` (`is_processed`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sincronización de Relojes
CREATE TABLE IF NOT EXISTS `hcm_time_clock_sync` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `time_clock_id` INT(11) UNSIGNED NOT NULL,
  `sync_batch_id` VARCHAR(50) NOT NULL,
  `sync_started_at` DATETIME NOT NULL,
  `sync_completed_at` DATETIME,
  `records_fetched` INT(11) DEFAULT 0,
  `records_processed` INT(11) DEFAULT 0,
  `records_failed` INT(11) DEFAULT 0,
  `status` ENUM('in_progress', 'completed', 'failed', 'partial') DEFAULT 'in_progress',
  `error_message` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`time_clock_id`) REFERENCES `hcm_time_clocks`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `sync_batch_id` (`sync_batch_id`),
  INDEX `idx_sync_started` (`sync_started_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ausencias y Licencias
CREATE TABLE IF NOT EXISTS `hcm_leaves` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `employee_id` INT(11) UNSIGNED NOT NULL,
  `leave_type` ENUM('vacation', 'sick_leave', 'personal_leave', 'maternity_leave', 'paternity_leave', 'unpaid_leave', 'compensatory_time', 'bereavement') NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `total_days` DECIMAL(5,2) NOT NULL,
  `reason` TEXT,
  `medical_certificate_url` VARCHAR(255),
  `status` ENUM('pending', 'approved', 'rejected', 'cancelled') DEFAULT 'pending',
  `requested_at` DATETIME NOT NULL,
  `approved_by` INT(11) UNSIGNED,
  `approved_at` DATETIME,
  `rejection_reason` TEXT,
  `notes` TEXT,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`employee_id`) REFERENCES `hcm_employees`(`id`) ON DELETE CASCADE,
  INDEX `idx_start_date` (`start_date`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Balance de Vacaciones
CREATE TABLE IF NOT EXISTS `hcm_leave_balances` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `employee_id` INT(11) UNSIGNED NOT NULL,
  `year` INT(4) NOT NULL,
  `leave_type` ENUM('vacation', 'sick_leave', 'personal_leave', 'compensatory_time') NOT NULL,
  `days_entitled` DECIMAL(5,2) NOT NULL,
  `days_accrued` DECIMAL(5,2) DEFAULT 0.00,
  `days_taken` DECIMAL(5,2) DEFAULT 0.00,
  `days_pending` DECIMAL(5,2) DEFAULT 0.00,
  `days_available` DECIMAL(5,2) NOT NULL,
  `carry_forward_days` DECIMAL(5,2) DEFAULT 0.00,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`employee_id`) REFERENCES `hcm_employees`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `employee_year_type` (`employee_id`, `year`, `leave_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reclutamiento
CREATE TABLE IF NOT EXISTS `hcm_job_positions` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `position_code` VARCHAR(50) NOT NULL,
  `job_title` VARCHAR(200) NOT NULL,
  `department` VARCHAR(100),
  `employment_type` ENUM('full_time', 'part_time', 'contractor', 'temporary', 'intern') DEFAULT 'full_time',
  `number_of_positions` INT(11) DEFAULT 1,
  `salary_min` DECIMAL(18,2),
  `salary_max` DECIMAL(18,2),
  `currency` VARCHAR(3) DEFAULT 'CLP',
  `job_description` TEXT,
  `requirements` TEXT,
  `responsibilities` TEXT,
  `benefits` TEXT,
  `location` VARCHAR(200),
  `status` ENUM('draft', 'open', 'on_hold', 'filled', 'cancelled') DEFAULT 'draft',
  `published_date` DATE,
  `closing_date` DATE,
  `hiring_manager_id` INT(11) UNSIGNED,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `company_position_code` (`company_id`, `position_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Candidatos
CREATE TABLE IF NOT EXISTS `hcm_candidates` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `job_position_id` INT(11) UNSIGNED NOT NULL,
  `candidate_number` VARCHAR(50) NOT NULL,
  `first_name` VARCHAR(100) NOT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(50),
  `tax_id` VARCHAR(50),
  `resume_url` VARCHAR(255),
  `cover_letter` TEXT,
  `linkedin_url` VARCHAR(255),
  `source` ENUM('direct_application', 'referral', 'job_board', 'linkedin', 'recruiter', 'other') NOT NULL,
  `referrer_id` INT(11) UNSIGNED,
  `application_date` DATETIME NOT NULL,
  `status` ENUM('new', 'screening', 'interviewing', 'testing', 'offer_made', 'hired', 'rejected', 'withdrawn') DEFAULT 'new',
  `current_stage` VARCHAR(100),
  `expected_salary` DECIMAL(18,2),
  `available_from` DATE,
  `rating` DECIMAL(2,1),
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`job_position_id`) REFERENCES `hcm_job_positions`(`id`),
  UNIQUE KEY `company_candidate_number` (`company_id`, `candidate_number`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Entrevistas
CREATE TABLE IF NOT EXISTS `hcm_interviews` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `candidate_id` INT(11) UNSIGNED NOT NULL,
  `interview_round` INT(11) NOT NULL,
  `interview_type` ENUM('phone_screening', 'video', 'in_person', 'technical', 'panel') NOT NULL,
  `scheduled_date` DATETIME NOT NULL,
  `duration_minutes` INT(11) DEFAULT 60,
  `interviewer_id` INT(11) UNSIGNED,
  `additional_interviewers` JSON,
  `location` VARCHAR(200),
  `meeting_url` VARCHAR(255),
  `status` ENUM('scheduled', 'completed', 'cancelled', 'no_show') DEFAULT 'scheduled',
  `candidate_showed` TINYINT(1),
  `rating` DECIMAL(2,1),
  `feedback` TEXT,
  `recommendation` ENUM('strong_hire', 'hire', 'maybe', 'no_hire', 'strong_no_hire'),
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`candidate_id`) REFERENCES `hcm_candidates`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`interviewer_id`) REFERENCES `hcm_employees`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Evaluaciones de Desempeño
CREATE TABLE IF NOT EXISTS `hcm_performance_reviews` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `employee_id` INT(11) UNSIGNED NOT NULL,
  `review_period_id` INT(11) UNSIGNED,
  `review_type` ENUM('annual', 'quarterly', 'probation', 'project_based', 'ad_hoc') NOT NULL,
  `review_date` DATE NOT NULL,
  `period_start` DATE NOT NULL,
  `period_end` DATE NOT NULL,
  `reviewer_id` INT(11) UNSIGNED NOT NULL,
  `self_assessment_completed` TINYINT(1) DEFAULT 0,
  `manager_assessment_completed` TINYINT(1) DEFAULT 0,
  `overall_rating` DECIMAL(3,2),
  `strengths` TEXT,
  `areas_for_improvement` TEXT,
  `goals_achieved` TEXT,
  `goals_next_period` TEXT,
  `development_plan` TEXT,
  `salary_adjustment_recommended` TINYINT(1) DEFAULT 0,
  `promotion_recommended` TINYINT(1) DEFAULT 0,
  `status` ENUM('draft', 'self_assessment', 'manager_review', 'hr_review', 'completed', 'acknowledged') DEFAULT 'draft',
  `employee_acknowledged_at` DATETIME,
  `hr_approved_by` INT(11) UNSIGNED,
  `hr_approved_at` DATETIME,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`employee_id`) REFERENCES `hcm_employees`(`id`),
  FOREIGN KEY (`reviewer_id`) REFERENCES `hcm_employees`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Competencias Evaluadas
CREATE TABLE IF NOT EXISTS `hcm_review_competencies` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `review_id` INT(11) UNSIGNED NOT NULL,
  `competency_name` VARCHAR(150) NOT NULL,
  `competency_category` ENUM('technical', 'behavioral', 'leadership', 'core_values') NOT NULL,
  `self_rating` DECIMAL(3,2),
  `manager_rating` DECIMAL(3,2),
  `final_rating` DECIMAL(3,2),
  `comments` TEXT,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`review_id`) REFERENCES `hcm_performance_reviews`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Capacitación
CREATE TABLE IF NOT EXISTS `hcm_training_programs` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `program_code` VARCHAR(50) NOT NULL,
  `program_name` VARCHAR(200) NOT NULL,
  `description` TEXT,
  `program_type` ENUM('onboarding', 'technical', 'soft_skills', 'leadership', 'compliance', 'safety') NOT NULL,
  `delivery_method` ENUM('in_person', 'virtual', 'e_learning', 'on_the_job', 'hybrid') NOT NULL,
  `duration_hours` DECIMAL(6,2),
  `cost_per_participant` DECIMAL(18,2),
  `currency` VARCHAR(3) DEFAULT 'CLP',
  `provider` VARCHAR(200),
  `instructor` VARCHAR(150),
  `max_participants` INT(11),
  `certification_offered` TINYINT(1) DEFAULT 0,
  `certification_name` VARCHAR(200),
  `is_mandatory` TINYINT(1) DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `company_program_code` (`company_id`, `program_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sesiones de Capacitación
CREATE TABLE IF NOT EXISTS `hcm_training_sessions` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `training_program_id` INT(11) UNSIGNED NOT NULL,
  `session_number` VARCHAR(50) NOT NULL,
  `session_name` VARCHAR(200),
  `start_date` DATETIME NOT NULL,
  `end_date` DATETIME NOT NULL,
  `location` VARCHAR(200),
  `meeting_url` VARCHAR(255),
  `instructor` VARCHAR(150),
  `max_participants` INT(11),
  `enrolled_count` INT(11) DEFAULT 0,
  `status` ENUM('planned', 'open_for_registration', 'full', 'in_progress', 'completed', 'cancelled') DEFAULT 'planned',
  `cost_total` DECIMAL(18,2),
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`training_program_id`) REFERENCES `hcm_training_programs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inscripciones a Capacitación
CREATE TABLE IF NOT EXISTS `hcm_training_enrollments` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `training_session_id` INT(11) UNSIGNED NOT NULL,
  `employee_id` INT(11) UNSIGNED NOT NULL,
  `enrollment_date` DATETIME NOT NULL,
  `status` ENUM('enrolled', 'waitlisted', 'attended', 'completed', 'failed', 'cancelled', 'no_show') DEFAULT 'enrolled',
  `attendance_percentage` DECIMAL(5,2),
  `final_score` DECIMAL(5,2),
  `passed` TINYINT(1),
  `certification_issued` TINYINT(1) DEFAULT 0,
  `certification_date` DATE,
  `certification_url` VARCHAR(255),
  `feedback` TEXT,
  `rating` DECIMAL(2,1),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`training_session_id`) REFERENCES `hcm_training_sessions`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`employee_id`) REFERENCES `hcm_employees`(`id`),
  UNIQUE KEY `session_employee` (`training_session_id`, `employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Beneficios
CREATE TABLE IF NOT EXISTS `hcm_benefits` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `benefit_code` VARCHAR(50) NOT NULL,
  `benefit_name` VARCHAR(200) NOT NULL,
  `benefit_type` ENUM('health_insurance', 'life_insurance', 'dental', 'vision', 'retirement', 'transportation', 'meal_voucher', 'gym', 'education', 'other') NOT NULL,
  `description` TEXT,
  `provider` VARCHAR(200),
  `cost_employee` DECIMAL(18,2) DEFAULT 0.00,
  `cost_employer` DECIMAL(18,2) DEFAULT 0.00,
  `currency` VARCHAR(3) DEFAULT 'CLP',
  `is_mandatory` TINYINT(1) DEFAULT 0,
  `eligibility_rules` TEXT,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `company_benefit_code` (`company_id`, `benefit_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inscripción a Beneficios
CREATE TABLE IF NOT EXISTS `hcm_employee_benefits` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `employee_id` INT(11) UNSIGNED NOT NULL,
  `benefit_id` INT(11) UNSIGNED NOT NULL,
  `enrollment_date` DATE NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE,
  `coverage_level` ENUM('employee_only', 'employee_spouse', 'employee_children', 'family') DEFAULT 'employee_only',
  `dependents_count` INT(11) DEFAULT 0,
  `employee_contribution` DECIMAL(18,2) DEFAULT 0.00,
  `employer_contribution` DECIMAL(18,2) DEFAULT 0.00,
  `status` ENUM('active', 'waived', 'pending', 'cancelled') DEFAULT 'pending',
  `waiver_reason` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`employee_id`) REFERENCES `hcm_employees`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`benefit_id`) REFERENCES `hcm_benefits`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Continue in Part 5 with: SCM, CRM, LOY, BI (15 submódulos), SII Integration, and final tables
