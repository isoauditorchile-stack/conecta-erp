-- =====================================================
-- CONECTA ERP v2.0.0 - SCHEMA COMPLETO DE PRODUCCIÓN
-- Sistema ERP Completo con 14 Módulos y 106 Submódulos
-- Compatible con SAP/Softland - Nivel Empresarial
-- =====================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- =====================================================
-- TABLAS DE SISTEMA Y CONFIGURACIÓN
-- =====================================================

-- Tabla de Empresas (Multi-tenant)
CREATE TABLE IF NOT EXISTS `companies` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_name` VARCHAR(255) NOT NULL,
  `legal_name` VARCHAR(255) NOT NULL,
  `tax_id` VARCHAR(50) NOT NULL,
  `tax_id_type` VARCHAR(50) DEFAULT 'RUT',
  `industry` VARCHAR(100),
  `website` VARCHAR(255),
  `phone` VARCHAR(50),
  `email` VARCHAR(150),
  `address` TEXT,
  `city` VARCHAR(100),
  `state_province` VARCHAR(100),
  `postal_code` VARCHAR(20),
  `country_id` INT(11) UNSIGNED DEFAULT 1,
  `currency` VARCHAR(3) DEFAULT 'CLP',
  `language` VARCHAR(2) DEFAULT 'es',
  `timezone` VARCHAR(50) DEFAULT 'America/Santiago',
  `employees_count` VARCHAR(20),
  `fiscal_year_start` INT(2) DEFAULT 1,
  `logo_url` VARCHAR(255),
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tax_id` (`tax_id`),
  INDEX `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de Usuarios
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `firstname` VARCHAR(100) NOT NULL,
  `lastname` VARCHAR(100) NOT NULL,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `tax_id` VARCHAR(50),
  `tax_id_type` VARCHAR(50),
  `position` VARCHAR(100),
  `department` VARCHAR(100),
  `phone` VARCHAR(20),
  `mobile` VARCHAR(20),
  `country` VARCHAR(3) DEFAULT 'CL',
  `language` VARCHAR(2) DEFAULT 'es',
  `timezone` VARCHAR(50) DEFAULT 'America/Santiago',
  `is_admin` TINYINT(1) DEFAULT 0,
  `is_super_admin` TINYINT(1) DEFAULT 0,
  `status` ENUM('trial', 'active', 'suspended', 'expired', 'pending_approval', 'rejected') DEFAULT 'pending_approval',
  `trial_days` INT(11) DEFAULT 14,
  `trial_ends_at` DATETIME DEFAULT NULL,
  `approved_by` INT(11) UNSIGNED DEFAULT NULL,
  `approved_at` DATETIME DEFAULT NULL,
  `last_login` DATETIME DEFAULT NULL,
  `login_count` INT(11) DEFAULT 0,
  `avatar_url` VARCHAR(255),
  `preferences` JSON,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  INDEX `idx_email` (`email`),
  INDEX `idx_company` (`company_id`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de Módulos (14 módulos principales)
CREATE TABLE IF NOT EXISTS `modules` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(10) NOT NULL UNIQUE,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT,
  `icon` VARCHAR(50),
  `color` VARCHAR(20),
  `url` VARCHAR(255),
  `sort_order` INT(11) DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1,
  `requires_license` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar 14 módulos principales
INSERT INTO `modules` (`code`, `name`, `description`, `icon`, `color`, `url`, `sort_order`) VALUES
('FI', 'Finanzas', 'Gestión Financiera y Contable', 'fa-dollar-sign', '#10b981', '/modules/fi/', 1),
('CO', 'Controlling', 'Control de Gestión y Costos', 'fa-chart-pie', '#3b82f6', '/modules/co/', 2),
('SD', 'Ventas', 'Ventas y Distribución', 'fa-shopping-cart', '#ef4444', '/modules/sd/', 3),
('MM', 'Materiales', 'Gestión de Materiales', 'fa-boxes', '#f59e0b', '/modules/mm/', 4),
('PP', 'Producción', 'Planificación de Producción', 'fa-industry', '#8b5cf6', '/modules/pp/', 5),
('HCM', 'RRHH', 'Gestión de Capital Humano', 'fa-users', '#ec4899', '/modules/hcm/', 6),
('SCM', 'Supply Chain', 'Gestión de Cadena de Suministro', 'fa-truck', '#14b8a6', '/modules/scm/', 7),
('CRM', 'CRM', 'Gestión de Relaciones con Clientes', 'fa-handshake', '#f97316', '/modules/crm/', 8),
('LOY', 'Fidelización', 'Programas de Lealtad', 'fa-gift', '#a855f7', '/modules/loy/', 9),
('BI', 'Business Intelligence', 'Inteligencia de Negocios', 'fa-chart-line', '#06b6d4', '/modules/bi/', 10),
('FE', 'Facturación Electrónica', 'Documentos Tributarios Electrónicos', 'fa-file-invoice', '#84cc16', '/modules/fe/', 11),
('PM', 'Proyectos', 'Gestión de Proyectos', 'fa-tasks', '#6366f1', '/modules/pm/', 12),
('ADM', 'Administración', 'Administración del Sistema', 'fa-cog', '#64748b', '/modules/adm/', 13),
('MOB', 'Mobile', 'Aplicaciones Móviles', 'fa-mobile-alt', '#0ea5e9', '/modules/mob/', 14);

-- Tabla de Submódulos (106 submódulos)
CREATE TABLE IF NOT EXISTS `submodules` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `module_id` INT(11) UNSIGNED NOT NULL,
  `code` VARCHAR(20) NOT NULL UNIQUE,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT,
  `url` VARCHAR(255),
  `icon` VARCHAR(50),
  `sort_order` INT(11) DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1,
  `requires_license` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`module_id`) REFERENCES `modules`(`id`) ON DELETE CASCADE,
  INDEX `idx_module` (`module_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de Traducciones para i18n
CREATE TABLE IF NOT EXISTS `translations` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `language_code` VARCHAR(2) NOT NULL,
  `translation_key` VARCHAR(255) NOT NULL,
  `translation_value` TEXT NOT NULL,
  `module` VARCHAR(50),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lang_key` (`language_code`, `translation_key`),
  INDEX `idx_language` (`language_code`),
  INDEX `idx_module` (`module`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de Idiomas Soportados
CREATE TABLE IF NOT EXISTS `languages` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(2) NOT NULL UNIQUE,
  `name` VARCHAR(50) NOT NULL,
  `native_name` VARCHAR(50) NOT NULL,
  `flag_emoji` VARCHAR(10),
  `is_active` TINYINT(1) DEFAULT 1,
  `is_default` TINYINT(1) DEFAULT 0,
  `sort_order` INT(11) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar 8 idiomas soportados
INSERT INTO `languages` (`code`, `name`, `native_name`, `flag_emoji`, `is_active`, `is_default`, `sort_order`) VALUES
('es', 'Spanish', 'Español', '🇪🇸', 1, 1, 1),
('en', 'English', 'English', '🇺🇸', 1, 0, 2),
('pt', 'Portuguese', 'Português', '🇧🇷', 1, 0, 3),
('fr', 'French', 'Français', '🇫🇷', 1, 0, 4),
('de', 'German', 'Deutsch', '🇩🇪', 1, 0, 5),
('it', 'Italian', 'Italiano', '🇮🇹', 1, 0, 6),
('ru', 'Russian', 'Русский', '🇷🇺', 1, 0, 7),
('zh', 'Chinese', '中文', '🇨🇳', 1, 0, 8);

-- Tabla de Países
CREATE TABLE IF NOT EXISTS `countries` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(3) NOT NULL UNIQUE,
  `name` VARCHAR(100) NOT NULL,
  `native_name` VARCHAR(100),
  `currency_code` VARCHAR(3),
  `phone_prefix` VARCHAR(10),
  `tax_id_name` VARCHAR(50),
  `tax_id_format` VARCHAR(100),
  `is_active` TINYINT(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar países principales
INSERT INTO `countries` (`code`, `name`, `native_name`, `currency_code`, `phone_prefix`, `tax_id_name`, `tax_id_format`) VALUES
('CL', 'Chile', 'Chile', 'CLP', '+56', 'RUT', '##.###.###-#'),
('AR', 'Argentina', 'Argentina', 'ARS', '+54', 'CUIT', '##-########-#'),
('BR', 'Brazil', 'Brasil', 'BRL', '+55', 'CNPJ', '##.###.###/####-##'),
('MX', 'Mexico', 'México', 'MXN', '+52', 'RFC', 'XXXX######XXX'),
('US', 'United States', 'United States', 'USD', '+1', 'EIN', '##-#######'),
('ES', 'Spain', 'España', 'EUR', '+34', 'NIF', 'X########X'),
('PE', 'Peru', 'Perú', 'PEN', '+51', 'RUC', '###########'),
('CO', 'Colombia', 'Colombia', 'COP', '+57', 'NIT', '#########-#');

-- Tabla de Log de Actividad
CREATE TABLE IF NOT EXISTS `activity_log` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED DEFAULT NULL,
  `company_id` INT(11) UNSIGNED DEFAULT NULL,
  `action` VARCHAR(100) NOT NULL,
  `description` TEXT,
  `module` VARCHAR(50),
  `table_name` VARCHAR(100),
  `record_id` INT(11) UNSIGNED,
  `old_values` JSON,
  `new_values` JSON,
  `ip_address` VARCHAR(45),
  `user_agent` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_user` (`user_id`),
  INDEX `idx_company` (`company_id`),
  INDEX `idx_action` (`action`),
  INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- MÓDULO 1: FINANZAS (FI) - 11 Submódulos
-- =====================================================

-- Plan de Cuentas (Chart of Accounts)
CREATE TABLE IF NOT EXISTS `fi_chart_of_accounts` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `account_code` VARCHAR(50) NOT NULL,
  `account_name` VARCHAR(200) NOT NULL,
  `account_type` ENUM('asset', 'liability', 'equity', 'revenue', 'expense', 'cost_of_sales') NOT NULL,
  `parent_account_id` INT(11) UNSIGNED NULL,
  `level` INT(2) NOT NULL DEFAULT 1,
  `is_header` TINYINT(1) DEFAULT 0,
  `currency` VARCHAR(3) DEFAULT 'CLP',
  `balance_debit` DECIMAL(18,2) DEFAULT 0.00,
  `balance_credit` DECIMAL(18,2) DEFAULT 0.00,
  `balance` DECIMAL(18,2) DEFAULT 0.00,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`parent_account_id`) REFERENCES `fi_chart_of_accounts`(`id`) ON DELETE SET NULL,
  UNIQUE KEY `company_account` (`company_id`, `account_code`),
  INDEX `idx_type` (`account_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Asientos Contables
CREATE TABLE IF NOT EXISTS `fi_journal_entries` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `entry_number` VARCHAR(50) NOT NULL,
  `entry_date` DATE NOT NULL,
  `posting_date` DATE,
  `period_year` INT(4) NOT NULL,
  `period_month` INT(2) NOT NULL,
  `description` TEXT NOT NULL,
  `reference` VARCHAR(100),
  `total_debit` DECIMAL(18,2) NOT NULL,
  `total_credit` DECIMAL(18,2) NOT NULL,
  `currency` VARCHAR(3) DEFAULT 'CLP',
  `exchange_rate` DECIMAL(10,6) DEFAULT 1.000000,
  `status` ENUM('draft', 'posted', 'reversed', 'cancelled') DEFAULT 'draft',
  `created_by` INT(11) UNSIGNED NOT NULL,
  `approved_by` INT(11) UNSIGNED,
  `posted_at` DATETIME,
  `reversed_by` INT(11) UNSIGNED,
  `reversed_at` DATETIME,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`),
  INDEX `idx_date` (`entry_date`),
  INDEX `idx_status` (`status`),
  INDEX `idx_period` (`period_year`, `period_month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Líneas de Asientos Contables
CREATE TABLE IF NOT EXISTS `fi_journal_entry_lines` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `entry_id` INT(11) UNSIGNED NOT NULL,
  `line_number` INT(11) NOT NULL,
  `account_id` INT(11) UNSIGNED NOT NULL,
  `debit` DECIMAL(18,2) DEFAULT 0.00,
  `credit` DECIMAL(18,2) DEFAULT 0.00,
  `description` VARCHAR(255),
  `cost_center_id` INT(11) UNSIGNED,
  `project_id` INT(11) UNSIGNED,
  `department` VARCHAR(100),
  `tax_code` VARCHAR(20),
  `reference` VARCHAR(100),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`entry_id`) REFERENCES `fi_journal_entries`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`account_id`) REFERENCES `fi_chart_of_accounts`(`id`),
  INDEX `idx_account` (`account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cuentas por Cobrar
CREATE TABLE IF NOT EXISTS `fi_accounts_receivable` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `customer_id` INT(11) UNSIGNED NOT NULL,
  `invoice_number` VARCHAR(50) NOT NULL,
  `invoice_type` ENUM('invoice', 'credit_note', 'debit_note') DEFAULT 'invoice',
  `invoice_date` DATE NOT NULL,
  `due_date` DATE NOT NULL,
  `payment_terms` VARCHAR(100),
  `subtotal` DECIMAL(18,2) NOT NULL,
  `tax_amount` DECIMAL(18,2) DEFAULT 0.00,
  `discount_amount` DECIMAL(18,2) DEFAULT 0.00,
  `total_amount` DECIMAL(18,2) NOT NULL,
  `paid_amount` DECIMAL(18,2) DEFAULT 0.00,
  `balance` DECIMAL(18,2) NOT NULL,
  `currency` VARCHAR(3) DEFAULT 'CLP',
  `exchange_rate` DECIMAL(10,6) DEFAULT 1.000000,
  `status` ENUM('pending', 'partial', 'paid', 'overdue', 'cancelled', 'written_off') DEFAULT 'pending',
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  INDEX `idx_customer` (`customer_id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_due_date` (`due_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pagos de Cuentas por Cobrar
CREATE TABLE IF NOT EXISTS `fi_ar_payments` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `ar_id` INT(11) UNSIGNED NOT NULL,
  `payment_number` VARCHAR(50) NOT NULL,
  `payment_date` DATE NOT NULL,
  `amount` DECIMAL(18,2) NOT NULL,
  `payment_method` ENUM('cash', 'check', 'transfer', 'card', 'mobile', 'other') NOT NULL,
  `reference_number` VARCHAR(100),
  `bank_account_id` INT(11) UNSIGNED,
  `notes` TEXT,
  `created_by` INT(11) UNSIGNED NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`ar_id`) REFERENCES `fi_accounts_receivable`(`id`) ON DELETE CASCADE,
  INDEX `idx_payment_date` (`payment_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cuentas por Pagar
CREATE TABLE IF NOT EXISTS `fi_accounts_payable` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `supplier_id` INT(11) UNSIGNED NOT NULL,
  `invoice_number` VARCHAR(50) NOT NULL,
  `invoice_type` ENUM('invoice', 'credit_note', 'debit_note') DEFAULT 'invoice',
  `invoice_date` DATE NOT NULL,
  `due_date` DATE NOT NULL,
  `payment_terms` VARCHAR(100),
  `subtotal` DECIMAL(18,2) NOT NULL,
  `tax_amount` DECIMAL(18,2) DEFAULT 0.00,
  `discount_amount` DECIMAL(18,2) DEFAULT 0.00,
  `total_amount` DECIMAL(18,2) NOT NULL,
  `paid_amount` DECIMAL(18,2) DEFAULT 0.00,
  `balance` DECIMAL(18,2) NOT NULL,
  `currency` VARCHAR(3) DEFAULT 'CLP',
  `exchange_rate` DECIMAL(10,6) DEFAULT 1.000000,
  `status` ENUM('pending', 'partial', 'paid', 'overdue', 'cancelled') DEFAULT 'pending',
  `purchase_order_id` INT(11) UNSIGNED,
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  INDEX `idx_supplier` (`supplier_id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_due_date` (`due_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pagos de Cuentas por Pagar
CREATE TABLE IF NOT EXISTS `fi_ap_payments` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `ap_id` INT(11) UNSIGNED NOT NULL,
  `payment_number` VARCHAR(50) NOT NULL,
  `payment_date` DATE NOT NULL,
  `amount` DECIMAL(18,2) NOT NULL,
  `payment_method` ENUM('cash', 'check', 'transfer', 'card', 'other') NOT NULL,
  `reference_number` VARCHAR(100),
  `bank_account_id` INT(11) UNSIGNED,
  `notes` TEXT,
  `created_by` INT(11) UNSIGNED NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`ap_id`) REFERENCES `fi_accounts_payable`(`id`) ON DELETE CASCADE,
  INDEX `idx_payment_date` (`payment_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cuentas Bancarias
CREATE TABLE IF NOT EXISTS `fi_bank_accounts` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `account_name` VARCHAR(200) NOT NULL,
  `account_number` VARCHAR(50),
  `bank_name` VARCHAR(100) NOT NULL,
  `bank_branch` VARCHAR(100),
  `account_type` ENUM('checking', 'savings', 'investment', 'credit_line') NOT NULL,
  `currency` VARCHAR(3) DEFAULT 'CLP',
  `opening_balance` DECIMAL(18,2) DEFAULT 0.00,
  `current_balance` DECIMAL(18,2) DEFAULT 0.00,
  `gl_account_id` INT(11) UNSIGNED,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`gl_account_id`) REFERENCES `fi_chart_of_accounts`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Transacciones Bancarias
CREATE TABLE IF NOT EXISTS `fi_bank_transactions` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `bank_account_id` INT(11) UNSIGNED NOT NULL,
  `transaction_date` DATE NOT NULL,
  `transaction_type` ENUM('deposit', 'withdrawal', 'transfer', 'fee', 'interest') NOT NULL,
  `amount` DECIMAL(18,2) NOT NULL,
  `reference` VARCHAR(100),
  `description` TEXT,
  `counterparty` VARCHAR(200),
  `is_reconciled` TINYINT(1) DEFAULT 0,
  `reconciled_at` DATETIME,
  `journal_entry_id` INT(11) UNSIGNED,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`bank_account_id`) REFERENCES `fi_bank_accounts`(`id`) ON DELETE CASCADE,
  INDEX `idx_date` (`transaction_date`),
  INDEX `idx_reconciled` (`is_reconciled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Conciliaciones Bancarias
CREATE TABLE IF NOT EXISTS `fi_bank_reconciliations` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `bank_account_id` INT(11) UNSIGNED NOT NULL,
  `reconciliation_date` DATE NOT NULL,
  `statement_ending_balance` DECIMAL(18,2) NOT NULL,
  `book_balance` DECIMAL(18,2) NOT NULL,
  `adjusted_bank_balance` DECIMAL(18,2) NOT NULL,
  `adjusted_book_balance` DECIMAL(18,2) NOT NULL,
  `difference` DECIMAL(18,2) NOT NULL,
  `status` ENUM('in_progress', 'completed', 'reviewed', 'approved') DEFAULT 'in_progress',
  `notes` TEXT,
  `reconciled_by` INT(11) UNSIGNED,
  `reconciled_at` DATETIME,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`bank_account_id`) REFERENCES `fi_bank_accounts`(`id`),
  INDEX `idx_date` (`reconciliation_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Activos Fijos
CREATE TABLE IF NOT EXISTS `fi_fixed_assets` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `asset_code` VARCHAR(50) NOT NULL,
  `asset_name` VARCHAR(200) NOT NULL,
  `description` TEXT,
  `category` VARCHAR(100) NOT NULL,
  `subcategory` VARCHAR(100),
  `purchase_date` DATE NOT NULL,
  `purchase_value` DECIMAL(18,2) NOT NULL,
  `salvage_value` DECIMAL(18,2) DEFAULT 0.00,
  `useful_life_years` INT(11) NOT NULL,
  `useful_life_months` INT(11) NOT NULL,
  `depreciation_method` ENUM('straight_line', 'declining_balance', 'sum_of_years', 'units_of_production') DEFAULT 'straight_line',
  `accumulated_depreciation` DECIMAL(18,2) DEFAULT 0.00,
  `book_value` DECIMAL(18,2) NOT NULL,
  `location` VARCHAR(200),
  `responsible_person` VARCHAR(150),
  `serial_number` VARCHAR(100),
  `status` ENUM('active', 'disposed', 'sold', 'fully_depreciated', 'under_maintenance') DEFAULT 'active',
  `gl_asset_account_id` INT(11) UNSIGNED,
  `gl_depreciation_account_id` INT(11) UNSIGNED,
  `gl_expense_account_id` INT(11) UNSIGNED,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `company_asset_code` (`company_id`, `asset_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Depreciación de Activos
CREATE TABLE IF NOT EXISTS `fi_asset_depreciation` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `asset_id` INT(11) UNSIGNED NOT NULL,
  `period_year` INT(4) NOT NULL,
  `period_month` INT(2) NOT NULL,
  `depreciation_amount` DECIMAL(18,2) NOT NULL,
  `accumulated_depreciation` DECIMAL(18,2) NOT NULL,
  `book_value` DECIMAL(18,2) NOT NULL,
  `journal_entry_id` INT(11) UNSIGNED,
  `status` ENUM('calculated', 'posted', 'reversed') DEFAULT 'calculated',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`asset_id`) REFERENCES `fi_fixed_assets`(`id`) ON DELETE CASCADE,
  INDEX `idx_period` (`period_year`, `period_month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Períodos Contables
CREATE TABLE IF NOT EXISTS `fi_fiscal_periods` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `period_year` INT(4) NOT NULL,
  `period_month` INT(2) NOT NULL,
  `period_name` VARCHAR(50),
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `status` ENUM('open', 'closing', 'closed', 'locked') DEFAULT 'open',
  `closed_by` INT(11) UNSIGNED,
  `closed_at` DATETIME,
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `company_period` (`company_id`, `period_year`, `period_month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reportes Financieros Guardados
CREATE TABLE IF NOT EXISTS `fi_financial_reports` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `report_type` ENUM('balance_sheet', 'income_statement', 'cash_flow', 'trial_balance', 'general_ledger', 'aged_receivables', 'aged_payables') NOT NULL,
  `report_name` VARCHAR(150),
  `period_start` DATE NOT NULL,
  `period_end` DATE NOT NULL,
  `report_data` JSON,
  `parameters` JSON,
  `generated_by` INT(11) UNSIGNED NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Presupuesto Financiero
CREATE TABLE IF NOT EXISTS `fi_budgets` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `budget_name` VARCHAR(150) NOT NULL,
  `fiscal_year` INT(4) NOT NULL,
  `account_id` INT(11) UNSIGNED NOT NULL,
  `january` DECIMAL(18,2) DEFAULT 0.00,
  `february` DECIMAL(18,2) DEFAULT 0.00,
  `march` DECIMAL(18,2) DEFAULT 0.00,
  `april` DECIMAL(18,2) DEFAULT 0.00,
  `may` DECIMAL(18,2) DEFAULT 0.00,
  `june` DECIMAL(18,2) DEFAULT 0.00,
  `july` DECIMAL(18,2) DEFAULT 0.00,
  `august` DECIMAL(18,2) DEFAULT 0.00,
  `september` DECIMAL(18,2) DEFAULT 0.00,
  `october` DECIMAL(18,2) DEFAULT 0.00,
  `november` DECIMAL(18,2) DEFAULT 0.00,
  `december` DECIMAL(18,2) DEFAULT 0.00,
  `total_annual` DECIMAL(18,2) DEFAULT 0.00,
  `status` ENUM('draft', 'approved', 'active', 'closed') DEFAULT 'draft',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`account_id`) REFERENCES `fi_chart_of_accounts`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- MÓDULO 2: CONTROLLING (CO) - 8 Submódulos
-- =====================================================

-- Centros de Costo
CREATE TABLE IF NOT EXISTS `co_cost_centers` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `code` VARCHAR(50) NOT NULL,
  `name` VARCHAR(200) NOT NULL,
  `description` TEXT,
  `parent_id` INT(11) UNSIGNED NULL,
  `level` INT(2) DEFAULT 1,
  `manager_id` INT(11) UNSIGNED,
  `department` VARCHAR(100),
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`parent_id`) REFERENCES `co_cost_centers`(`id`) ON DELETE SET NULL,
  UNIQUE KEY `company_code` (`company_id`, `code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Presupuestos por Centro de Costo
CREATE TABLE IF NOT EXISTS `co_cost_center_budgets` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `cost_center_id` INT(11) UNSIGNED NOT NULL,
  `fiscal_year` INT(4) NOT NULL,
  `period_month` INT(2) NOT NULL,
  `budgeted_amount` DECIMAL(18,2) NOT NULL,
  `actual_amount` DECIMAL(18,2) DEFAULT 0.00,
  `variance` DECIMAL(18,2) DEFAULT 0.00,
  `variance_percentage` DECIMAL(5,2) DEFAULT 0.00,
  `notes` TEXT,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`cost_center_id`) REFERENCES `co_cost_centers`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `cost_center_period` (`cost_center_id`, `fiscal_year`, `period_month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Órdenes Internas
CREATE TABLE IF NOT EXISTS `co_internal_orders` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `order_number` VARCHAR(50) NOT NULL,
  `order_name` VARCHAR(200) NOT NULL,
  `description` TEXT,
  `cost_center_id` INT(11) UNSIGNED,
  `responsible_id` INT(11) UNSIGNED,
  `order_type` ENUM('investment', 'maintenance', 'overhead', 'project', 'other') NOT NULL,
  `budget_amount` DECIMAL(18,2) NOT NULL,
  `actual_cost` DECIMAL(18,2) DEFAULT 0.00,
  `committed_cost` DECIMAL(18,2) DEFAULT 0.00,
  `available_budget` DECIMAL(18,2) NOT NULL,
  `status` ENUM('planned', 'approved', 'in_progress', 'completed', 'cancelled', 'closed') DEFAULT 'planned',
  `start_date` DATE,
  `end_date` DATE,
  `completion_percentage` DECIMAL(5,2) DEFAULT 0.00,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`cost_center_id`) REFERENCES `co_cost_centers`(`id`),
  UNIQUE KEY `company_order_number` (`company_id`, `order_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Análisis de Rentabilidad
CREATE TABLE IF NOT EXISTS `co_profitability_analysis` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `analysis_name` VARCHAR(200) NOT NULL,
  `analysis_type` ENUM('product', 'customer', 'project', 'division', 'region') NOT NULL,
  `entity_id` INT(11) UNSIGNED,
  `entity_name` VARCHAR(200),
  `period_start` DATE NOT NULL,
  `period_end` DATE NOT NULL,
  `revenue` DECIMAL(18,2) NOT NULL,
  `cost_of_goods_sold` DECIMAL(18,2) NOT NULL,
  `gross_profit` DECIMAL(18,2) NOT NULL,
  `gross_margin_percentage` DECIMAL(5,2) NOT NULL,
  `operating_expenses` DECIMAL(18,2) NOT NULL,
  `operating_profit` DECIMAL(18,2) NOT NULL,
  `operating_margin_percentage` DECIMAL(5,2) NOT NULL,
  `other_income` DECIMAL(18,2) DEFAULT 0.00,
  `other_expenses` DECIMAL(18,2) DEFAULT 0.00,
  `net_profit` DECIMAL(18,2) NOT NULL,
  `net_margin_percentage` DECIMAL(5,2) NOT NULL,
  `roi_percentage` DECIMAL(5,2),
  `analysis_data` JSON,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Control de Gastos
CREATE TABLE IF NOT EXISTS `co_expense_reports` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `report_number` VARCHAR(50) NOT NULL,
  `employee_id` INT(11) UNSIGNED NOT NULL,
  `cost_center_id` INT(11) UNSIGNED,
  `report_date` DATE NOT NULL,
  `purpose` TEXT,
  `total_amount` DECIMAL(18,2) NOT NULL,
  `approved_amount` DECIMAL(18,2) DEFAULT 0.00,
  `status` ENUM('draft', 'submitted', 'approved', 'rejected', 'paid') DEFAULT 'draft',
  `submitted_by` INT(11) UNSIGNED,
  `submitted_at` DATETIME,
  `approved_by` INT(11) UNSIGNED,
  `approved_at` DATETIME,
  `rejection_reason` TEXT,
  `payment_date` DATE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`cost_center_id`) REFERENCES `co_cost_centers`(`id`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Líneas de Gastos
CREATE TABLE IF NOT EXISTS `co_expense_report_lines` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `expense_report_id` INT(11) UNSIGNED NOT NULL,
  `expense_date` DATE NOT NULL,
  `category` VARCHAR(100) NOT NULL,
  `description` TEXT NOT NULL,
  `amount` DECIMAL(18,2) NOT NULL,
  `currency` VARCHAR(3) DEFAULT 'CLP',
  `exchange_rate` DECIMAL(10,6) DEFAULT 1.000000,
  `amount_in_base_currency` DECIMAL(18,2) NOT NULL,
  `receipt_url` VARCHAR(255),
  `is_billable` TINYINT(1) DEFAULT 0,
  `project_id` INT(11) UNSIGNED,
  `notes` TEXT,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`expense_report_id`) REFERENCES `co_expense_reports`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Costeo ABC (Activity Based Costing)
CREATE TABLE IF NOT EXISTS `co_abc_activities` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `activity_code` VARCHAR(50) NOT NULL,
  `activity_name` VARCHAR(200) NOT NULL,
  `description` TEXT,
  `activity_driver` VARCHAR(100) NOT NULL,
  `cost_pool_account_id` INT(11) UNSIGNED,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `company_activity_code` (`company_id`, `activity_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Costeo ABC por Período
CREATE TABLE IF NOT EXISTS `co_abc_costing` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `activity_id` INT(11) UNSIGNED NOT NULL,
  `period_year` INT(4) NOT NULL,
  `period_month` INT(2) NOT NULL,
  `cost_pool_amount` DECIMAL(18,2) NOT NULL,
  `driver_quantity` DECIMAL(10,2) NOT NULL,
  `cost_per_driver_unit` DECIMAL(18,4) NOT NULL,
  `allocated_costs` JSON,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`activity_id`) REFERENCES `co_abc_activities`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `activity_period` (`activity_id`, `period_year`, `period_month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Análisis de Proyectos (Control de Gestión)
CREATE TABLE IF NOT EXISTS `co_project_controlling` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `project_code` VARCHAR(50) NOT NULL,
  `project_name` VARCHAR(200) NOT NULL,
  `project_type` ENUM('internal', 'external', 'investment', 'development') NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE,
  `budgeted_revenue` DECIMAL(18,2) NOT NULL,
  `actual_revenue` DECIMAL(18,2) DEFAULT 0.00,
  `budgeted_cost` DECIMAL(18,2) NOT NULL,
  `actual_cost` DECIMAL(18,2) DEFAULT 0.00,
  `budgeted_profit` DECIMAL(18,2) NOT NULL,
  `actual_profit` DECIMAL(18,2) DEFAULT 0.00,
  `roi_percentage` DECIMAL(5,2) DEFAULT 0.00,
  `completion_percentage` DECIMAL(5,2) DEFAULT 0.00,
  `status` ENUM('planning', 'active', 'on_hold', 'completed', 'cancelled') DEFAULT 'planning',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `company_project_code` (`company_id`, `project_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Control de Inversiones
CREATE TABLE IF NOT EXISTS `co_investments` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `investment_code` VARCHAR(50) NOT NULL,
  `investment_name` VARCHAR(200) NOT NULL,
  `investment_type` ENUM('capex', 'opex', 'rd', 'marketing', 'it', 'infrastructure', 'other') NOT NULL,
  `category` VARCHAR(100),
  `description` TEXT,
  `investment_amount` DECIMAL(18,2) NOT NULL,
  `expected_return_amount` DECIMAL(18,2),
  `expected_return_percentage` DECIMAL(5,2),
  `actual_return_amount` DECIMAL(18,2) DEFAULT 0.00,
  `actual_return_percentage` DECIMAL(5,2) DEFAULT 0.00,
  `investment_date` DATE NOT NULL,
  `maturity_date` DATE,
  `payback_period_months` INT(11),
  `npv` DECIMAL(18,2),
  `irr_percentage` DECIMAL(5,2),
  `status` ENUM('planned', 'approved', 'in_progress', 'completed', 'cancelled') DEFAULT 'planned',
  `responsible_id` INT(11) UNSIGNED,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `company_investment_code` (`company_id`, `investment_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Continue in next message due to character limit...
-- This is Part 1 of the complete schema
-- Part 2 will include: SD, MM, PP, HCM, SCM, CRM, LOY, BI, FE, PM, ADM, MOB modules

COMMIT;
