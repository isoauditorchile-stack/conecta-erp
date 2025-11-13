-- =====================================================
-- CONECTA ERP - SCHEMA DE BASE DE DATOS COMPLETO
-- Sistema ERP con 14 Módulos y 106 Submódulos
-- =====================================================

-- TABLA DE USUARIOS (ya existe, aquí está mejorada)
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `firstname` VARCHAR(100) NOT NULL,
  `lastname` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `company_name` VARCHAR(200) NOT NULL,
  `position` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(50) NOT NULL,
  `country` VARCHAR(10) NOT NULL,
  `currency` VARCHAR(10) NOT NULL,
  `language` VARCHAR(10) DEFAULT 'es',
  `employees` VARCHAR(20) NOT NULL,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `is_admin` TINYINT(1) DEFAULT 0,
  `status` ENUM('trial', 'active', 'suspended', 'cancelled') DEFAULT 'trial',
  `trial_days` INT(11) DEFAULT 14,
  `trial_ends_at` DATETIME NULL,
  `requires_approval` TINYINT(1) DEFAULT 1,
  `approved_by_admin` TINYINT(1) DEFAULT 0,
  `approved_at` DATETIME NULL,
  `last_login` DATETIME NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_email` (`email`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLA DE MÓDULOS
CREATE TABLE IF NOT EXISTS `modules` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(10) NOT NULL UNIQUE,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT,
  `icon` VARCHAR(50) NOT NULL,
  `color` VARCHAR(20) NOT NULL,
  `sort_order` INT(11) DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLA DE SUBMÓDULOS
CREATE TABLE IF NOT EXISTS `submodules` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `module_id` INT(11) UNSIGNED NOT NULL,
  `code` VARCHAR(20) NOT NULL UNIQUE,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT,
  `url` VARCHAR(200) NOT NULL,
  `icon` VARCHAR(50) NOT NULL,
  `sort_order` INT(11) DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1,
  `requires_license` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`module_id`) REFERENCES `modules`(`id`) ON DELETE CASCADE,
  INDEX `idx_module` (`module_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLA DE PERMISOS DE USUARIO
CREATE TABLE IF NOT EXISTS `user_permissions` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `submodule_id` INT(11) UNSIGNED NOT NULL,
  `can_view` TINYINT(1) DEFAULT 1,
  `can_create` TINYINT(1) DEFAULT 0,
  `can_edit` TINYINT(1) DEFAULT 0,
  `can_delete` TINYINT(1) DEFAULT 0,
  `granted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`submodule_id`) REFERENCES `submodules`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `user_submodule` (`user_id`, `submodule_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- MÓDULO 1: FINANZAS (FI) - 11 Submódulos
-- =====================================================

-- Contabilidad General
CREATE TABLE IF NOT EXISTS `fi_general_ledger` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `account_code` VARCHAR(20) NOT NULL,
  `account_name` VARCHAR(150) NOT NULL,
  `account_type` ENUM('asset', 'liability', 'equity', 'revenue', 'expense') NOT NULL,
  `currency` VARCHAR(10) NOT NULL,
  `balance` DECIMAL(15,2) DEFAULT 0.00,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`),
  INDEX `idx_account` (`account_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Asientos Contables
CREATE TABLE IF NOT EXISTS `fi_journal_entries` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `entry_number` VARCHAR(50) NOT NULL,
  `entry_date` DATE NOT NULL,
  `description` TEXT NOT NULL,
  `total_debit` DECIMAL(15,2) NOT NULL,
  `total_credit` DECIMAL(15,2) NOT NULL,
  `status` ENUM('draft', 'posted', 'reversed') DEFAULT 'draft',
  `created_by` INT(11) UNSIGNED NOT NULL,
  `posted_at` DATETIME NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`),
  INDEX `idx_date` (`entry_date`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Líneas de Asientos
CREATE TABLE IF NOT EXISTS `fi_journal_entry_lines` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `entry_id` INT(11) UNSIGNED NOT NULL,
  `account_id` INT(11) UNSIGNED NOT NULL,
  `debit` DECIMAL(15,2) DEFAULT 0.00,
  `credit` DECIMAL(15,2) DEFAULT 0.00,
  `description` VARCHAR(255),
  `cost_center` VARCHAR(50),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`entry_id`) REFERENCES `fi_journal_entries`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`account_id`) REFERENCES `fi_general_ledger`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cuentas por Cobrar
CREATE TABLE IF NOT EXISTS `fi_accounts_receivable` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `customer_name` VARCHAR(200) NOT NULL,
  `customer_email` VARCHAR(150),
  `invoice_number` VARCHAR(50) NOT NULL,
  `invoice_date` DATE NOT NULL,
  `due_date` DATE NOT NULL,
  `amount` DECIMAL(15,2) NOT NULL,
  `paid_amount` DECIMAL(15,2) DEFAULT 0.00,
  `balance` DECIMAL(15,2) NOT NULL,
  `status` ENUM('pending', 'partial', 'paid', 'overdue', 'cancelled') DEFAULT 'pending',
  `currency` VARCHAR(10) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_due_date` (`due_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cuentas por Pagar
CREATE TABLE IF NOT EXISTS `fi_accounts_payable` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `supplier_name` VARCHAR(200) NOT NULL,
  `supplier_email` VARCHAR(150),
  `invoice_number` VARCHAR(50) NOT NULL,
  `invoice_date` DATE NOT NULL,
  `due_date` DATE NOT NULL,
  `amount` DECIMAL(15,2) NOT NULL,
  `paid_amount` DECIMAL(15,2) DEFAULT 0.00,
  `balance` DECIMAL(15,2) NOT NULL,
  `status` ENUM('pending', 'partial', 'paid', 'overdue', 'cancelled') DEFAULT 'pending',
  `currency` VARCHAR(10) NOT NULL,
  `payment_terms` VARCHAR(100),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tesorería
CREATE TABLE IF NOT EXISTS `fi_treasury` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `account_name` VARCHAR(150) NOT NULL,
  `account_number` VARCHAR(50),
  `bank_name` VARCHAR(100),
  `account_type` ENUM('checking', 'savings', 'investment', 'cash') NOT NULL,
  `currency` VARCHAR(10) NOT NULL,
  `balance` DECIMAL(15,2) DEFAULT 0.00,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Activos Fijos
CREATE TABLE IF NOT EXISTS `fi_fixed_assets` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `asset_code` VARCHAR(50) NOT NULL,
  `asset_name` VARCHAR(200) NOT NULL,
  `category` VARCHAR(100) NOT NULL,
  `purchase_date` DATE NOT NULL,
  `purchase_value` DECIMAL(15,2) NOT NULL,
  `salvage_value` DECIMAL(15,2) DEFAULT 0.00,
  `useful_life_years` INT(11) NOT NULL,
  `depreciation_method` ENUM('straight_line', 'declining_balance', 'units_of_production') DEFAULT 'straight_line',
  `accumulated_depreciation` DECIMAL(15,2) DEFAULT 0.00,
  `book_value` DECIMAL(15,2) NOT NULL,
  `location` VARCHAR(150),
  `status` ENUM('active', 'disposed', 'fully_depreciated') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`),
  UNIQUE KEY `asset_code` (`user_id`, `asset_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bancos y Conciliaciones
CREATE TABLE IF NOT EXISTS `fi_bank_reconciliation` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `treasury_account_id` INT(11) UNSIGNED NOT NULL,
  `reconciliation_date` DATE NOT NULL,
  `book_balance` DECIMAL(15,2) NOT NULL,
  `bank_balance` DECIMAL(15,2) NOT NULL,
  `difference` DECIMAL(15,2) NOT NULL,
  `status` ENUM('in_progress', 'completed', 'reviewed') DEFAULT 'in_progress',
  `notes` TEXT,
  `reconciled_by` INT(11) UNSIGNED,
  `reconciled_at` DATETIME,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`),
  FOREIGN KEY (`treasury_account_id`) REFERENCES `fi_treasury`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Libros Contables
CREATE TABLE IF NOT EXISTS `fi_accounting_books` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `book_type` ENUM('general_ledger', 'sales', 'purchases', 'cash', 'bank') NOT NULL,
  `period` VARCHAR(20) NOT NULL,
  `status` ENUM('open', 'closed', 'audited') DEFAULT 'open',
  `closed_at` DATETIME NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- IFRS Reporting
CREATE TABLE IF NOT EXISTS `fi_ifrs_reports` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `report_type` VARCHAR(100) NOT NULL,
  `period` VARCHAR(20) NOT NULL,
  `report_data` JSON,
  `generated_at` DATETIME NOT NULL,
  `generated_by` INT(11) UNSIGNED NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cierres Contables
CREATE TABLE IF NOT EXISTS `fi_closing_periods` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `period_year` INT(11) NOT NULL,
  `period_month` INT(11) NOT NULL,
  `status` ENUM('open', 'closing', 'closed') DEFAULT 'open',
  `closed_by` INT(11) UNSIGNED,
  `closed_at` DATETIME,
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`),
  UNIQUE KEY `period` (`user_id`, `period_year`, `period_month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reportes Financieros
CREATE TABLE IF NOT EXISTS `fi_financial_reports` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `report_type` ENUM('balance_sheet', 'income_statement', 'cash_flow', 'trial_balance', 'general_ledger') NOT NULL,
  `period_start` DATE NOT NULL,
  `period_end` DATE NOT NULL,
  `report_data` JSON,
  `generated_by` INT(11) UNSIGNED NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- MÓDULO 2: CONTROLLING (CO) - 8 Submódulos
-- =====================================================

-- Centros de Costo
CREATE TABLE IF NOT EXISTS `co_cost_centers` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `code` VARCHAR(50) NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `description` TEXT,
  `parent_id` INT(11) UNSIGNED NULL,
  `manager_name` VARCHAR(100),
  `budget_amount` DECIMAL(15,2) DEFAULT 0.00,
  `actual_amount` DECIMAL(15,2) DEFAULT 0.00,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`),
  FOREIGN KEY (`parent_id`) REFERENCES `co_cost_centers`(`id`) ON DELETE SET NULL,
  UNIQUE KEY `code` (`user_id`, `code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Órdenes Internas
CREATE TABLE IF NOT EXISTS `co_internal_orders` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `order_number` VARCHAR(50) NOT NULL,
  `description` TEXT NOT NULL,
  `cost_center_id` INT(11) UNSIGNED,
  `budget_amount` DECIMAL(15,2) NOT NULL,
  `actual_cost` DECIMAL(15,2) DEFAULT 0.00,
  `status` ENUM('planned', 'approved', 'in_progress', 'completed', 'cancelled') DEFAULT 'planned',
  `start_date` DATE,
  `end_date` DATE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`),
  FOREIGN KEY (`cost_center_id`) REFERENCES `co_cost_centers`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Análisis de Rentabilidad
CREATE TABLE IF NOT EXISTS `co_profitability_analysis` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `analysis_name` VARCHAR(150) NOT NULL,
  `period_start` DATE NOT NULL,
  `period_end` DATE NOT NULL,
  `revenue` DECIMAL(15,2) NOT NULL,
  `cost_of_goods` DECIMAL(15,2) NOT NULL,
  `operating_expenses` DECIMAL(15,2) NOT NULL,
  `gross_profit` DECIMAL(15,2) NOT NULL,
  `net_profit` DECIMAL(15,2) NOT NULL,
  `profit_margin` DECIMAL(5,2) NOT NULL,
  `analysis_data` JSON,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Presupuestos
CREATE TABLE IF NOT EXISTS `co_budgets` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `budget_name` VARCHAR(150) NOT NULL,
  `fiscal_year` INT(11) NOT NULL,
  `cost_center_id` INT(11) UNSIGNED,
  `category` VARCHAR(100) NOT NULL,
  `budgeted_amount` DECIMAL(15,2) NOT NULL,
  `actual_amount` DECIMAL(15,2) DEFAULT 0.00,
  `variance` DECIMAL(15,2) DEFAULT 0.00,
  `variance_percentage` DECIMAL(5,2) DEFAULT 0.00,
  `status` ENUM('draft', 'approved', 'active', 'closed') DEFAULT 'draft',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`),
  FOREIGN KEY (`cost_center_id`) REFERENCES `co_cost_centers`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Control de Gastos
CREATE TABLE IF NOT EXISTS `co_expense_control` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `expense_number` VARCHAR(50) NOT NULL,
  `cost_center_id` INT(11) UNSIGNED,
  `category` VARCHAR(100) NOT NULL,
  `description` TEXT NOT NULL,
  `amount` DECIMAL(15,2) NOT NULL,
  `expense_date` DATE NOT NULL,
  `submitted_by` INT(11) UNSIGNED NOT NULL,
  `approved_by` INT(11) UNSIGNED,
  `status` ENUM('submitted', 'approved', 'rejected', 'paid') DEFAULT 'submitted',
  `receipt_url` VARCHAR(255),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`),
  FOREIGN KEY (`cost_center_id`) REFERENCES `co_cost_centers`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Costos ABC
CREATE TABLE IF NOT EXISTS `co_abc_costing` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `activity_name` VARCHAR(150) NOT NULL,
  `activity_driver` VARCHAR(100) NOT NULL,
  `cost_pool` DECIMAL(15,2) NOT NULL,
  `driver_quantity` DECIMAL(10,2) NOT NULL,
  `cost_per_driver` DECIMAL(15,2) NOT NULL,
  `period` VARCHAR(20) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Análisis de Proyectos
CREATE TABLE IF NOT EXISTS `co_project_analysis` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `project_name` VARCHAR(150) NOT NULL,
  `project_code` VARCHAR(50) NOT NULL,
  `budgeted_cost` DECIMAL(15,2) NOT NULL,
  `actual_cost` DECIMAL(15,2) DEFAULT 0.00,
  `budgeted_revenue` DECIMAL(15,2) NOT NULL,
  `actual_revenue` DECIMAL(15,2) DEFAULT 0.00,
  `roi` DECIMAL(5,2) DEFAULT 0.00,
  `completion_percentage` DECIMAL(5,2) DEFAULT 0.00,
  `status` ENUM('planning', 'active', 'completed', 'cancelled') DEFAULT 'planning',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Control de Inversiones
CREATE TABLE IF NOT EXISTS `co_investment_control` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `investment_name` VARCHAR(150) NOT NULL,
  `investment_type` ENUM('capex', 'opex', 'rd', 'marketing', 'other') NOT NULL,
  `amount` DECIMAL(15,2) NOT NULL,
  `expected_return` DECIMAL(15,2),
  `actual_return` DECIMAL(15,2) DEFAULT 0.00,
  `investment_date` DATE NOT NULL,
  `maturity_date` DATE,
  `status` ENUM('planned', 'approved', 'executed', 'completed') DEFAULT 'planned',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- MÓDULO 3: VENTAS & DISTRIBUCIÓN (SD) - 9 Submódulos
-- =====================================================

-- Clientes
CREATE TABLE IF NOT EXISTS `sd_customers` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `customer_code` VARCHAR(50) NOT NULL,
  `customer_name` VARCHAR(200) NOT NULL,
  `tax_id` VARCHAR(50),
  `email` VARCHAR(150),
  `phone` VARCHAR(50),
  `address` TEXT,
  `city` VARCHAR(100),
  `country` VARCHAR(100),
  `credit_limit` DECIMAL(15,2) DEFAULT 0.00,
  `payment_terms` VARCHAR(100),
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`),
  UNIQUE KEY `customer_code` (`user_id`, `customer_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cotizaciones
CREATE TABLE IF NOT EXISTS `sd_quotations` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `quotation_number` VARCHAR(50) NOT NULL,
  `customer_id` INT(11) UNSIGNED NOT NULL,
  `quotation_date` DATE NOT NULL,
  `valid_until` DATE NOT NULL,
  `subtotal` DECIMAL(15,2) NOT NULL,
  `tax_amount` DECIMAL(15,2) NOT NULL,
  `discount_amount` DECIMAL(15,2) DEFAULT 0.00,
  `total_amount` DECIMAL(15,2) NOT NULL,
  `currency` VARCHAR(10) NOT NULL,
  `status` ENUM('draft', 'sent', 'accepted', 'rejected', 'expired') DEFAULT 'draft',
  `notes` TEXT,
  `created_by` INT(11) UNSIGNED NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`),
  FOREIGN KEY (`customer_id`) REFERENCES `sd_customers`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pedidos de Venta
CREATE TABLE IF NOT EXISTS `sd_sales_orders` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `order_number` VARCHAR(50) NOT NULL,
  `customer_id` INT(11) UNSIGNED NOT NULL,
  `order_date` DATE NOT NULL,
  `delivery_date` DATE,
  `subtotal` DECIMAL(15,2) NOT NULL,
  `tax_amount` DECIMAL(15,2) NOT NULL,
  `shipping_cost` DECIMAL(15,2) DEFAULT 0.00,
  `total_amount` DECIMAL(15,2) NOT NULL,
  `currency` VARCHAR(10) NOT NULL,
  `status` ENUM('pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
  `payment_status` ENUM('unpaid', 'partial', 'paid') DEFAULT 'unpaid',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`),
  FOREIGN KEY (`customer_id`) REFERENCES `sd_customers`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Líneas de Pedido
CREATE TABLE IF NOT EXISTS `sd_order_lines` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT(11) UNSIGNED NOT NULL,
  `product_code` VARCHAR(50) NOT NULL,
  `product_name` VARCHAR(200) NOT NULL,
  `quantity` DECIMAL(10,2) NOT NULL,
  `unit_price` DECIMAL(15,2) NOT NULL,
  `discount_percentage` DECIMAL(5,2) DEFAULT 0.00,
  `tax_percentage` DECIMAL(5,2) DEFAULT 0.00,
  `line_total` DECIMAL(15,2) NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`order_id`) REFERENCES `sd_sales_orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Facturación
CREATE TABLE IF NOT EXISTS `sd_invoices` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `invoice_number` VARCHAR(50) NOT NULL,
  `order_id` INT(11) UNSIGNED,
  `customer_id` INT(11) UNSIGNED NOT NULL,
  `invoice_date` DATE NOT NULL,
  `due_date` DATE NOT NULL,
  `subtotal` DECIMAL(15,2) NOT NULL,
  `tax_amount` DECIMAL(15,2) NOT NULL,
  `total_amount` DECIMAL(15,2) NOT NULL,
  `paid_amount` DECIMAL(15,2) DEFAULT 0.00,
  `balance` DECIMAL(15,2) NOT NULL,
  `currency` VARCHAR(10) NOT NULL,
  `status` ENUM('draft', 'issued', 'paid', 'overdue', 'cancelled') DEFAULT 'draft',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`),
  FOREIGN KEY (`customer_id`) REFERENCES `sd_customers`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Punto de Venta (POS)
CREATE TABLE IF NOT EXISTS `sd_pos_transactions` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `transaction_number` VARCHAR(50) NOT NULL,
  `terminal_id` VARCHAR(50) NOT NULL,
  `cashier_id` INT(11) UNSIGNED NOT NULL,
  `customer_id` INT(11) UNSIGNED,
  `transaction_date` DATETIME NOT NULL,
  `subtotal` DECIMAL(15,2) NOT NULL,
  `tax_amount` DECIMAL(15,2) NOT NULL,
  `total_amount` DECIMAL(15,2) NOT NULL,
  `payment_method` ENUM('cash', 'card', 'transfer', 'mobile', 'mixed') NOT NULL,
  `status` ENUM('completed', 'cancelled', 'refunded') DEFAULT 'completed',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- E-commerce
CREATE TABLE IF NOT EXISTS `sd_ecommerce_orders` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `order_number` VARCHAR(50) NOT NULL,
  `customer_email` VARCHAR(150) NOT NULL,
  `customer_name` VARCHAR(200) NOT NULL,
  `shipping_address` TEXT NOT NULL,
  `order_date` DATETIME NOT NULL,
  `total_amount` DECIMAL(15,2) NOT NULL,
  `payment_method` VARCHAR(50) NOT NULL,
  `payment_status` ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
  `shipping_status` ENUM('pending', 'processing', 'shipped', 'delivered') DEFAULT 'pending',
  `tracking_number` VARCHAR(100),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Precios Dinámicos
CREATE TABLE IF NOT EXISTS `sd_dynamic_pricing` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `product_code` VARCHAR(50) NOT NULL,
  `customer_segment` VARCHAR(100),
  `base_price` DECIMAL(15,2) NOT NULL,
  `rule_type` ENUM('volume', 'time', 'seasonal', 'customer', 'competitor') NOT NULL,
  `discount_percentage` DECIMAL(5,2) DEFAULT 0.00,
  `final_price` DECIMAL(15,2) NOT NULL,
  `valid_from` DATE NOT NULL,
  `valid_to` DATE NOT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Comisiones
CREATE TABLE IF NOT EXISTS `sd_commissions` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `sales_person_id` INT(11) UNSIGNED NOT NULL,
  `order_id` INT(11) UNSIGNED,
  `sale_amount` DECIMAL(15,2) NOT NULL,
  `commission_percentage` DECIMAL(5,2) NOT NULL,
  `commission_amount` DECIMAL(15,2) NOT NULL,
  `period` VARCHAR(20) NOT NULL,
  `status` ENUM('pending', 'approved', 'paid') DEFAULT 'pending',
  `paid_at` DATETIME,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Análisis de Ventas
CREATE TABLE IF NOT EXISTS `sd_sales_analytics` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `period` VARCHAR(20) NOT NULL,
  `total_sales` DECIMAL(15,2) NOT NULL,
  `total_orders` INT(11) NOT NULL,
  `average_order_value` DECIMAL(15,2) NOT NULL,
  `top_product` VARCHAR(200),
  `top_customer` VARCHAR(200),
  `sales_by_channel` JSON,
  `sales_by_region` JSON,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- MÓDULO 4: MATERIALES (MM) - 8 Submódulos
-- =====================================================

-- Productos/Materiales
CREATE TABLE IF NOT EXISTS `mm_products` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `product_code` VARCHAR(50) NOT NULL,
  `product_name` VARCHAR(200) NOT NULL,
  `description` TEXT,
  `category` VARCHAR(100) NOT NULL,
  `unit_of_measure` VARCHAR(20) NOT NULL,
  `standard_cost` DECIMAL(15,2) NOT NULL,
  `selling_price` DECIMAL(15,2) NOT NULL,
  `min_stock_level` DECIMAL(10,2) DEFAULT 0.00,
  `max_stock_level` DECIMAL(10,2) DEFAULT 0.00,
  `reorder_point` DECIMAL(10,2) DEFAULT 0.00,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`),
  UNIQUE KEY `product_code` (`user_id`, `product_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Almacenes
CREATE TABLE IF NOT EXISTS `mm_warehouses` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `warehouse_code` VARCHAR(50) NOT NULL,
  `warehouse_name` VARCHAR(150) NOT NULL,
  `address` TEXT,
  `city` VARCHAR(100),
  `country` VARCHAR(100),
  `manager_name` VARCHAR(100),
  `capacity` DECIMAL(10,2),
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`),
  UNIQUE KEY `warehouse_code` (`user_id`, `warehouse_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inventario
CREATE TABLE IF NOT EXISTS `mm_inventory` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `product_id` INT(11) UNSIGNED NOT NULL,
  `warehouse_id` INT(11) UNSIGNED NOT NULL,
  `quantity_on_hand` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `quantity_reserved` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `quantity_available` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `last_movement_date` DATETIME,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`),
  FOREIGN KEY (`product_id`) REFERENCES `mm_products`(`id`),
  FOREIGN KEY (`warehouse_id`) REFERENCES `mm_warehouses`(`id`),
  UNIQUE KEY `product_warehouse` (`product_id`, `warehouse_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Movimientos de Inventario
CREATE TABLE IF NOT EXISTS `mm_inventory_movements` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `product_id` INT(11) UNSIGNED NOT NULL,
  `warehouse_id` INT(11) UNSIGNED NOT NULL,
  `movement_type` ENUM('receipt', 'issue', 'transfer', 'adjustment', 'return') NOT NULL,
  `quantity` DECIMAL(10,2) NOT NULL,
  `unit_cost` DECIMAL(15,2),
  `reference_number` VARCHAR(50),
  `movement_date` DATETIME NOT NULL,
  `notes` TEXT,
  `created_by` INT(11) UNSIGNED NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`),
  FOREIGN KEY (`product_id`) REFERENCES `mm_products`(`id`),
  FOREIGN KEY (`warehouse_id`) REFERENCES `mm_warehouses`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Proveedores
CREATE TABLE IF NOT EXISTS `mm_suppliers` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `supplier_code` VARCHAR(50) NOT NULL,
  `supplier_name` VARCHAR(200) NOT NULL,
  `tax_id` VARCHAR(50),
  `email` VARCHAR(150),
  `phone` VARCHAR(50),
  `address` TEXT,
  `city` VARCHAR(100),
  `country` VARCHAR(100),
  `payment_terms` VARCHAR(100),
  `rating` DECIMAL(2,1) DEFAULT 0.0,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`),
  UNIQUE KEY `supplier_code` (`user_id`, `supplier_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Órdenes de Compra
CREATE TABLE IF NOT EXISTS `mm_purchase_orders` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `po_number` VARCHAR(50) NOT NULL,
  `supplier_id` INT(11) UNSIGNED NOT NULL,
  `order_date` DATE NOT NULL,
  `expected_delivery_date` DATE,
  `subtotal` DECIMAL(15,2) NOT NULL,
  `tax_amount` DECIMAL(15,2) NOT NULL,
  `total_amount` DECIMAL(15,2) NOT NULL,
  `currency` VARCHAR(10) NOT NULL,
  `status` ENUM('draft', 'sent', 'confirmed', 'partial', 'received', 'cancelled') DEFAULT 'draft',
  `created_by` INT(11) UNSIGNED NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`),
  FOREIGN KEY (`supplier_id`) REFERENCES `mm_suppliers`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Recepción de Mercancías
CREATE TABLE IF NOT EXISTS `mm_goods_receipt` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `receipt_number` VARCHAR(50) NOT NULL,
  `po_id` INT(11) UNSIGNED,
  `warehouse_id` INT(11) UNSIGNED NOT NULL,
  `receipt_date` DATE NOT NULL,
  `status` ENUM('draft', 'completed', 'cancelled') DEFAULT 'draft',
  `notes` TEXT,
  `received_by` INT(11) UNSIGNED NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`),
  FOREIGN KEY (`po_id`) REFERENCES `mm_purchase_orders`(`id`),
  FOREIGN KEY (`warehouse_id`) REFERENCES `mm_warehouses`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- MRP - Planificación de Necesidades
CREATE TABLE IF NOT EXISTS `mm_mrp` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `product_id` INT(11) UNSIGNED NOT NULL,
  `planning_period` VARCHAR(20) NOT NULL,
  `forecasted_demand` DECIMAL(10,2) NOT NULL,
  `current_inventory` DECIMAL(10,2) NOT NULL,
  `safety_stock` DECIMAL(10,2) NOT NULL,
  `planned_orders` DECIMAL(10,2) NOT NULL,
  `order_date` DATE,
  `delivery_date` DATE,
  `status` ENUM('planned', 'ordered', 'received') DEFAULT 'planned',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`),
  FOREIGN KEY (`product_id`) REFERENCES `mm_products`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Trazabilidad
CREATE TABLE IF NOT EXISTS `mm_traceability` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `product_id` INT(11) UNSIGNED NOT NULL,
  `batch_number` VARCHAR(50) NOT NULL,
  `serial_number` VARCHAR(50),
  `manufacturing_date` DATE,
  `expiry_date` DATE,
  `warehouse_id` INT(11) UNSIGNED NOT NULL,
  `quantity` DECIMAL(10,2) NOT NULL,
  `status` ENUM('available', 'reserved', 'sold', 'expired') DEFAULT 'available',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`),
  FOREIGN KEY (`product_id`) REFERENCES `mm_products`(`id`),
  FOREIGN KEY (`warehouse_id`) REFERENCES `mm_warehouses`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- MÓDULO 5: PRODUCCIÓN (PP) - 10 Submódulos
-- (Continuará en la siguiente parte debido al límite de caracteres)
-- =====================================================

-- Aquí continuaré con los módulos restantes...
-- Este es un schema parcial. El archivo completo tendrá todas las tablas.
