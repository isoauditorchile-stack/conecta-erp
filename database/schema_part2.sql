-- =====================================================
-- CONECTA ERP - SCHEMA PARTE 2
-- Continuación de módulos 5-14
-- =====================================================

-- =====================================================
-- MÓDULO 5: PRODUCCIÓN (PP) - 10 Submódulos
-- =====================================================

-- Órdenes de Producción
CREATE TABLE IF NOT EXISTS `pp_production_orders` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `order_number` VARCHAR(50) NOT NULL,
  `product_id` INT(11) UNSIGNED NOT NULL,
  `quantity_planned` DECIMAL(10,2) NOT NULL,
  `quantity_produced` DECIMAL(10,2) DEFAULT 0.00,
  `start_date` DATE NOT NULL,
  `end_date` DATE,
  `actual_end_date` DATE,
  `status` ENUM('planned', 'released', 'in_progress', 'completed', 'cancelled') DEFAULT 'planned',
  `priority` ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- BOM (Bill of Materials)
CREATE TABLE IF NOT EXISTS `pp_bom` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `product_id` INT(11) UNSIGNED NOT NULL,
  `bom_number` VARCHAR(50) NOT NULL,
  `version` VARCHAR(20) DEFAULT '1.0',
  `status` ENUM('draft', 'active', 'obsolete') DEFAULT 'draft',
  `valid_from` DATE NOT NULL,
  `valid_to` DATE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- BOM Items
CREATE TABLE IF NOT EXISTS `pp_bom_items` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `bom_id` INT(11) UNSIGNED NOT NULL,
  `component_id` INT(11) UNSIGNED NOT NULL,
  `quantity` DECIMAL(10,4) NOT NULL,
  `unit_of_measure` VARCHAR(20) NOT NULL,
  `scrap_percentage` DECIMAL(5,2) DEFAULT 0.00,
  `is_critical` TINYINT(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`bom_id`) REFERENCES `pp_bom`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rutinas de Producción
CREATE TABLE IF NOT EXISTS `pp_routing` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `product_id` INT(11) UNSIGNED NOT NULL,
  `routing_number` VARCHAR(50) NOT NULL,
  `description` TEXT,
  `total_time_minutes` INT(11) NOT NULL,
  `status` ENUM('active', 'inactive') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Operaciones de Rutina
CREATE TABLE IF NOT EXISTS `pp_routing_operations` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `routing_id` INT(11) UNSIGNED NOT NULL,
  `operation_number` INT(11) NOT NULL,
  `operation_name` VARCHAR(150) NOT NULL,
  `work_center` VARCHAR(100) NOT NULL,
  `setup_time_minutes` INT(11) DEFAULT 0,
  `run_time_minutes` INT(11) NOT NULL,
  `labor_cost_per_hour` DECIMAL(10,2),
  `machine_cost_per_hour` DECIMAL(10,2),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`routing_id`) REFERENCES `pp_routing`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- MRP II - Planificación Avanzada
CREATE TABLE IF NOT EXISTS `pp_mrp_ii` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `planning_horizon` VARCHAR(50) NOT NULL,
  `run_date` DATETIME NOT NULL,
  `demand_forecast` JSON,
  `capacity_requirements` JSON,
  `material_requirements` JSON,
  `recommendations` JSON,
  `status` ENUM('running', 'completed', 'failed') DEFAULT 'running',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Planificación de Capacidades
CREATE TABLE IF NOT EXISTS `pp_capacity_planning` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `work_center` VARCHAR(100) NOT NULL,
  `period` VARCHAR(20) NOT NULL,
  `available_capacity_hours` DECIMAL(10,2) NOT NULL,
  `required_capacity_hours` DECIMAL(10,2) NOT NULL,
  `utilization_percentage` DECIMAL(5,2) NOT NULL,
  `overload_hours` DECIMAL(10,2) DEFAULT 0.00,
  `status` ENUM('normal', 'warning', 'overload') DEFAULT 'normal',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Control de Calidad
CREATE TABLE IF NOT EXISTS `pp_quality_control` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `inspection_number` VARCHAR(50) NOT NULL,
  `production_order_id` INT(11) UNSIGNED,
  `product_id` INT(11) UNSIGNED NOT NULL,
  `inspection_date` DATE NOT NULL,
  `quantity_inspected` DECIMAL(10,2) NOT NULL,
  `quantity_approved` DECIMAL(10,2) NOT NULL,
  `quantity_rejected` DECIMAL(10,2) NOT NULL,
  `defect_type` VARCHAR(150),
  `inspector_name` VARCHAR(100) NOT NULL,
  `status` ENUM('passed', 'failed', 'conditional') NOT NULL,
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`),
  FOREIGN KEY (`production_order_id`) REFERENCES `pp_production_orders`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Mantenimiento Preventivo
CREATE TABLE IF NOT EXISTS `pp_preventive_maintenance` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `equipment_id` VARCHAR(50) NOT NULL,
  `equipment_name` VARCHAR(150) NOT NULL,
  `maintenance_type` ENUM('preventive', 'corrective', 'predictive') NOT NULL,
  `scheduled_date` DATE NOT NULL,
  `completed_date` DATE,
  `frequency_days` INT(11) NOT NULL,
  `last_maintenance` DATE,
  `next_maintenance` DATE NOT NULL,
  `technician_name` VARCHAR(100),
  `status` ENUM('scheduled', 'in_progress', 'completed', 'overdue') DEFAULT 'scheduled',
  `cost` DECIMAL(15,2),
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Fórmulas de Producción
CREATE TABLE IF NOT EXISTS `pp_formulas` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `formula_code` VARCHAR(50) NOT NULL,
  `product_id` INT(11) UNSIGNED NOT NULL,
  `batch_size` DECIMAL(10,2) NOT NULL,
  `unit_of_measure` VARCHAR(20) NOT NULL,
  `version` VARCHAR(20) DEFAULT '1.0',
  `formula_data` JSON,
  `status` ENUM('active', 'inactive') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Costos de Producción
CREATE TABLE IF NOT EXISTS `pp_production_costs` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `production_order_id` INT(11) UNSIGNED NOT NULL,
  `material_cost` DECIMAL(15,2) DEFAULT 0.00,
  `labor_cost` DECIMAL(15,2) DEFAULT 0.00,
  `overhead_cost` DECIMAL(15,2) DEFAULT 0.00,
  `total_cost` DECIMAL(15,2) NOT NULL,
  `cost_per_unit` DECIMAL(15,2) NOT NULL,
  `variance` DECIMAL(15,2) DEFAULT 0.00,
  `calculation_date` DATE NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`),
  FOREIGN KEY (`production_order_id`) REFERENCES `pp_production_orders`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- MÓDULO 6: CAPITAL HUMANO (HCM) - 11 Submódulos
-- =====================================================

-- Empleados
CREATE TABLE IF NOT EXISTS `hcm_employees` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `employee_number` VARCHAR(50) NOT NULL,
  `first_name` VARCHAR(100) NOT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150),
  `phone` VARCHAR(50),
  `date_of_birth` DATE,
  `gender` ENUM('male', 'female', 'other'),
  `nationality` VARCHAR(50),
  `tax_id` VARCHAR(50),
  `hire_date` DATE NOT NULL,
  `termination_date` DATE,
  `department` VARCHAR(100),
  `position` VARCHAR(100) NOT NULL,
  `employment_type` ENUM('full_time', 'part_time', 'contract', 'intern') NOT NULL,
  `status` ENUM('active', 'inactive', 'terminated', 'on_leave') DEFAULT 'active',
  `salary` DECIMAL(15,2),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`),
  UNIQUE KEY `employee_number` (`user_id`, `employee_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Nómina
CREATE TABLE IF NOT EXISTS `hcm_payroll` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `employee_id` INT(11) UNSIGNED NOT NULL,
  `period` VARCHAR(20) NOT NULL,
  `pay_date` DATE NOT NULL,
  `basic_salary` DECIMAL(15,2) NOT NULL,
  `bonuses` DECIMAL(15,2) DEFAULT 0.00,
  `overtime_pay` DECIMAL(15,2) DEFAULT 0.00,
  `commissions` DECIMAL(15,2) DEFAULT 0.00,
  `gross_pay` DECIMAL(15,2) NOT NULL,
  `tax_deductions` DECIMAL(15,2) DEFAULT 0.00,
  `social_security` DECIMAL(15,2) DEFAULT 0.00,
  `other_deductions` DECIMAL(15,2) DEFAULT 0.00,
  `total_deductions` DECIMAL(15,2) NOT NULL,
  `net_pay` DECIMAL(15,2) NOT NULL,
  `currency` VARCHAR(10) NOT NULL,
  `status` ENUM('draft', 'approved', 'paid') DEFAULT 'draft',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`),
  FOREIGN KEY (`employee_id`) REFERENCES `hcm_employees`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reclutamiento
CREATE TABLE IF NOT EXISTS `hcm_recruitment` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `job_title` VARCHAR(150) NOT NULL,
  `department` VARCHAR(100) NOT NULL,
  `job_description` TEXT NOT NULL,
  `requirements` TEXT NOT NULL,
  `vacancies` INT(11) DEFAULT 1,
  `posted_date` DATE NOT NULL,
  `closing_date` DATE,
  `employment_type` ENUM('full_time', 'part_time', 'contract', 'intern') NOT NULL,
  `salary_range_min` DECIMAL(15,2),
  `salary_range_max` DECIMAL(15,2),
  `status` ENUM('open', 'closed', 'on_hold', 'filled') DEFAULT 'open',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Candidatos
CREATE TABLE IF NOT EXISTS `hcm_candidates` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `job_id` INT(11) UNSIGNED NOT NULL,
  `first_name` VARCHAR(100) NOT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(50),
  `resume_url` VARCHAR(255),
  `application_date` DATE NOT NULL,
  `current_stage` ENUM('applied', 'screening', 'interview', 'assessment', 'offer', 'hired', 'rejected') DEFAULT 'applied',
  `rating` DECIMAL(2,1),
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`job_id`) REFERENCES `hcm_recruitment`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Onboarding
CREATE TABLE IF NOT EXISTS `hcm_onboarding` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `employee_id` INT(11) UNSIGNED NOT NULL,
  `start_date` DATE NOT NULL,
  `onboarding_checklist` JSON,
  `completion_percentage` DECIMAL(5,2) DEFAULT 0.00,
  `mentor_id` INT(11) UNSIGNED,
  `status` ENUM('not_started', 'in_progress', 'completed') DEFAULT 'not_started',
  `completed_at` DATE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`),
  FOREIGN KEY (`employee_id`) REFERENCES `hcm_employees`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Evaluación de Desempeño
CREATE TABLE IF NOT EXISTS `hcm_performance_reviews` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `employee_id` INT(11) UNSIGNED NOT NULL,
  `review_period` VARCHAR(20) NOT NULL,
  `reviewer_id` INT(11) UNSIGNED NOT NULL,
  `review_date` DATE NOT NULL,
  `overall_rating` DECIMAL(2,1) NOT NULL,
  `competencies_rating` JSON,
  `goals_achieved` TEXT,
  `areas_of_improvement` TEXT,
  `comments` TEXT,
  `status` ENUM('draft', 'submitted', 'reviewed', 'completed') DEFAULT 'draft',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`),
  FOREIGN KEY (`employee_id`) REFERENCES `hcm_employees`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Capacitación
CREATE TABLE IF NOT EXISTS `hcm_training` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `training_name` VARCHAR(150) NOT NULL,
  `description` TEXT,
  `trainer_name` VARCHAR(100),
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `duration_hours` INT(11) NOT NULL,
  `max_participants` INT(11),
  `cost_per_participant` DECIMAL(15,2),
  `status` ENUM('scheduled', 'in_progress', 'completed', 'cancelled') DEFAULT 'scheduled',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Participantes de Capacitación
CREATE TABLE IF NOT EXISTS `hcm_training_participants` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `training_id` INT(11) UNSIGNED NOT NULL,
  `employee_id` INT(11) UNSIGNED NOT NULL,
  `enrollment_date` DATE NOT NULL,
  `completion_status` ENUM('enrolled', 'in_progress', 'completed', 'dropped') DEFAULT 'enrolled',
  `completion_date` DATE,
  `score` DECIMAL(5,2),
  `certificate_url` VARCHAR(255),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`training_id`) REFERENCES `hcm_training`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`employee_id`) REFERENCES `hcm_employees`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Desarrollo Organizacional
CREATE TABLE IF NOT EXISTS `hcm_org_development` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `initiative_name` VARCHAR(150) NOT NULL,
  `description` TEXT NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE,
  `budget` DECIMAL(15,2),
  `target_audience` VARCHAR(200),
  `expected_outcomes` TEXT,
  `actual_outcomes` TEXT,
  `status` ENUM('planning', 'in_progress', 'completed', 'on_hold') DEFAULT 'planning',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Asistencia
CREATE TABLE IF NOT EXISTS `hcm_attendance` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `employee_id` INT(11) UNSIGNED NOT NULL,
  `attendance_date` DATE NOT NULL,
  `check_in_time` TIME,
  `check_out_time` TIME,
  `total_hours` DECIMAL(4,2),
  `overtime_hours` DECIMAL(4,2) DEFAULT 0.00,
  `status` ENUM('present', 'absent', 'late', 'half_day', 'leave') NOT NULL,
  `notes` VARCHAR(255),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`),
  FOREIGN KEY (`employee_id`) REFERENCES `hcm_employees`(`id`),
  UNIQUE KEY `employee_date` (`employee_id`, `attendance_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Beneficios
CREATE TABLE IF NOT EXISTS `hcm_benefits` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `benefit_name` VARCHAR(150) NOT NULL,
  `benefit_type` ENUM('health_insurance', 'life_insurance', 'retirement', 'vacation', 'bonus', 'other') NOT NULL,
  `description` TEXT,
  `cost_per_employee` DECIMAL(15,2),
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Asignación de Beneficios
CREATE TABLE IF NOT EXISTS `hcm_employee_benefits` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `employee_id` INT(11) UNSIGNED NOT NULL,
  `benefit_id` INT(11) UNSIGNED NOT NULL,
  `enrollment_date` DATE NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE,
  `status` ENUM('active', 'inactive', 'expired') DEFAULT 'active',
  PRIMARY KEY (`id`),
  FOREIGN KEY (`employee_id`) REFERENCES `hcm_employees`(`id`),
  FOREIGN KEY (`benefit_id`) REFERENCES `hcm_benefits`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- MÓDULO 7-14 continuarán en los archivos PHP de implementación
-- =====================================================

-- Insertar Módulos Principales
INSERT INTO `modules` (`code`, `name`, `description`, `icon`, `color`, `sort_order`) VALUES
('FI', 'Finanzas', 'Contabilidad, cuentas por pagar/cobrar, tesorería y reportes financieros', 'fa-chart-line', '#3b82f6', 1),
('CO', 'Controlling', 'Centros de costo, presupuestos y análisis de rentabilidad', 'fa-chart-pie', '#8b5cf6', 2),
('SD', 'Ventas & Distribución', 'Gestión de ventas, pedidos, facturación y CRM', 'fa-shopping-cart', '#10b981', 3),
('MM', 'Materiales', 'Inventario, compras y gestión de almacenes', 'fa-boxes', '#f59e0b', 4),
('PP', 'Producción', 'Órdenes de producción, MRP y control de calidad', 'fa-industry', '#ef4444', 5),
('HCM', 'Capital Humano', 'Gestión de personal, nómina y desarrollo organizacional', 'fa-user-tie', '#ec4899', 6),
('SCM', 'Supply Chain', 'Logística, transporte y gestión de cadena de suministro', 'fa-truck', '#14b8a6', 7),
('CRM', 'CRM', 'Gestión de clientes y relaciones comerciales', 'fa-handshake', '#f97316', 8),
('LOY', 'Fidelización', 'Programas de lealtad y recompensas', 'fa-gift', '#a855f7', 9),
('BI', 'Business Intelligence', 'Dashboards, KPIs y análisis predictivo', 'fa-brain', '#06b6d4', 10),
('FE', 'Facturación Electrónica', 'Integración SII/SUNAT/DIAN y documentos tributarios', 'fa-file-invoice', '#84cc16', 11),
('PM', 'Gestión de Proyectos', 'Planificación, seguimiento y control de proyectos', 'fa-building', '#6366f1', 12),
('ADM', 'Configuración & Admin', 'Usuarios, permisos, seguridad y configuración', 'fa-cogs', '#64748b', 13),
('MOB', 'Mobile Apps', 'Aplicaciones móviles nativas iOS y Android', 'fa-mobile-alt', '#0ea5e9', 14);
