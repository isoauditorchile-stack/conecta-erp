-- =====================================================
-- CONECTA ERP v2.0.0 - SCHEMA COMPLETO PARTE 3
-- Continuación MM + PP, HCM, SCM, CRM, LOY, BI
-- Integraciones: Previred, SII, Relojes Control
-- =====================================================

-- =====================================================
-- MÓDULO 6: MATERIALES (MM) - Continuación
-- =====================================================

-- Movimientos de Inventario
CREATE TABLE IF NOT EXISTS `mm_inventory_movements` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `movement_number` VARCHAR(50) NOT NULL,
  `movement_date` DATETIME NOT NULL,
  `movement_type` ENUM('receipt', 'issue', 'transfer', 'adjustment', 'return', 'scrap', 'cycle_count', 'production_receipt', 'production_issue') NOT NULL,
  `product_id` INT(11) UNSIGNED NOT NULL,
  `from_warehouse_id` INT(11) UNSIGNED,
  `from_location_id` INT(11) UNSIGNED,
  `to_warehouse_id` INT(11) UNSIGNED,
  `to_location_id` INT(11) UNSIGNED,
  `quantity` DECIMAL(15,3) NOT NULL,
  `unit_cost` DECIMAL(18,4),
  `total_cost` DECIMAL(18,2),
  `reference_type` VARCHAR(50),
  `reference_id` INT(11) UNSIGNED,
  `reference_number` VARCHAR(50),
  `reason_code` VARCHAR(50),
  `lot_number` VARCHAR(50),
  `serial_number` VARCHAR(50),
  `expiry_date` DATE,
  `notes` TEXT,
  `created_by` INT(11) UNSIGNED NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `mm_products`(`id`),
  FOREIGN KEY (`from_warehouse_id`) REFERENCES `mm_warehouses`(`id`),
  FOREIGN KEY (`to_warehouse_id`) REFERENCES `mm_warehouses`(`id`),
  INDEX `idx_movement_date` (`movement_date`),
  INDEX `idx_movement_type` (`movement_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Proveedores
CREATE TABLE IF NOT EXISTS `mm_suppliers` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `supplier_code` VARCHAR(50) NOT NULL,
  `supplier_type` ENUM('manufacturer', 'distributor', 'wholesaler', 'service_provider') NOT NULL,
  `business_name` VARCHAR(255) NOT NULL,
  `commercial_name` VARCHAR(255),
  `tax_id` VARCHAR(50),
  `email` VARCHAR(150),
  `phone` VARCHAR(50),
  `mobile` VARCHAR(50),
  `website` VARCHAR(255),
  `address` TEXT,
  `city` VARCHAR(100),
  `state_province` VARCHAR(100),
  `postal_code` VARCHAR(20),
  `country_code` VARCHAR(3) DEFAULT 'CL',
  `contact_person` VARCHAR(150),
  `contact_email` VARCHAR(150),
  `contact_phone` VARCHAR(50),
  `payment_terms_days` INT(11) DEFAULT 30,
  `credit_limit` DECIMAL(18,2),
  `currency` VARCHAR(3) DEFAULT 'CLP',
  `bank_name` VARCHAR(100),
  `bank_account_number` VARCHAR(50),
  `bank_account_type` VARCHAR(50),
  `rating` DECIMAL(2,1) DEFAULT 0.0,
  `lead_time_days` INT(11),
  `is_active` TINYINT(1) DEFAULT 1,
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `company_supplier_code` (`company_id`, `supplier_code`),
  INDEX `idx_tax_id` (`tax_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Órdenes de Compra
CREATE TABLE IF NOT EXISTS `mm_purchase_orders` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `po_number` VARCHAR(50) NOT NULL,
  `po_date` DATE NOT NULL,
  `supplier_id` INT(11) UNSIGNED NOT NULL,
  `buyer_id` INT(11) UNSIGNED,
  `currency` VARCHAR(3) DEFAULT 'CLP',
  `exchange_rate` DECIMAL(10,6) DEFAULT 1.000000,
  `payment_terms` VARCHAR(200),
  `delivery_terms` VARCHAR(200),
  `requested_delivery_date` DATE,
  `expected_delivery_date` DATE,
  `warehouse_id` INT(11) UNSIGNED,
  `subtotal` DECIMAL(18,2) NOT NULL,
  `discount_amount` DECIMAL(18,2) DEFAULT 0.00,
  `tax_amount` DECIMAL(18,2) DEFAULT 0.00,
  `shipping_cost` DECIMAL(18,2) DEFAULT 0.00,
  `total_amount` DECIMAL(18,2) NOT NULL,
  `status` ENUM('draft', 'sent', 'confirmed', 'partially_received', 'received', 'cancelled', 'closed') DEFAULT 'draft',
  `approval_status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
  `approved_by` INT(11) UNSIGNED,
  `approved_at` DATETIME,
  `notes` TEXT,
  `created_by` INT(11) UNSIGNED NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`supplier_id`) REFERENCES `mm_suppliers`(`id`),
  FOREIGN KEY (`warehouse_id`) REFERENCES `mm_warehouses`(`id`),
  UNIQUE KEY `company_po_number` (`company_id`, `po_number`),
  INDEX `idx_status` (`status`),
  INDEX `idx_po_date` (`po_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Líneas de Orden de Compra
CREATE TABLE IF NOT EXISTS `mm_purchase_order_lines` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `po_id` INT(11) UNSIGNED NOT NULL,
  `line_number` INT(11) NOT NULL,
  `product_id` INT(11) UNSIGNED NOT NULL,
  `product_code` VARCHAR(50) NOT NULL,
  `product_name` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `quantity_ordered` DECIMAL(15,3) NOT NULL,
  `quantity_received` DECIMAL(15,3) DEFAULT 0.000,
  `quantity_invoiced` DECIMAL(15,3) DEFAULT 0.000,
  `unit_of_measure` VARCHAR(20) NOT NULL,
  `unit_price` DECIMAL(18,4) NOT NULL,
  `discount_percentage` DECIMAL(5,2) DEFAULT 0.00,
  `discount_amount` DECIMAL(18,2) DEFAULT 0.00,
  `tax_percentage` DECIMAL(5,2) DEFAULT 19.00,
  `tax_amount` DECIMAL(18,2) NOT NULL,
  `line_total` DECIMAL(18,2) NOT NULL,
  `requested_delivery_date` DATE,
  `notes` TEXT,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`po_id`) REFERENCES `mm_purchase_orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `mm_products`(`id`),
  INDEX `idx_product` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Recepción de Mercancías
CREATE TABLE IF NOT EXISTS `mm_goods_receipts` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `receipt_number` VARCHAR(50) NOT NULL,
  `receipt_date` DATE NOT NULL,
  `po_id` INT(11) UNSIGNED,
  `supplier_id` INT(11) UNSIGNED NOT NULL,
  `warehouse_id` INT(11) UNSIGNED NOT NULL,
  `delivery_note_number` VARCHAR(50),
  `carrier` VARCHAR(200),
  `tracking_number` VARCHAR(100),
  `status` ENUM('draft', 'in_progress', 'completed', 'cancelled') DEFAULT 'draft',
  `inspection_required` TINYINT(1) DEFAULT 0,
  `inspection_status` ENUM('pending', 'passed', 'failed', 'partial') DEFAULT 'pending',
  `inspection_notes` TEXT,
  `received_by` INT(11) UNSIGNED NOT NULL,
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`po_id`) REFERENCES `mm_purchase_orders`(`id`),
  FOREIGN KEY (`supplier_id`) REFERENCES `mm_suppliers`(`id`),
  FOREIGN KEY (`warehouse_id`) REFERENCES `mm_warehouses`(`id`),
  UNIQUE KEY `company_receipt_number` (`company_id`, `receipt_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Líneas de Recepción
CREATE TABLE IF NOT EXISTS `mm_goods_receipt_lines` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `receipt_id` INT(11) UNSIGNED NOT NULL,
  `line_number` INT(11) NOT NULL,
  `po_line_id` INT(11) UNSIGNED,
  `product_id` INT(11) UNSIGNED NOT NULL,
  `product_code` VARCHAR(50) NOT NULL,
  `product_name` VARCHAR(255) NOT NULL,
  `quantity_ordered` DECIMAL(15,3),
  `quantity_received` DECIMAL(15,3) NOT NULL,
  `quantity_accepted` DECIMAL(15,3),
  `quantity_rejected` DECIMAL(15,3),
  `unit_of_measure` VARCHAR(20) NOT NULL,
  `unit_cost` DECIMAL(18,4),
  `location_id` INT(11) UNSIGNED,
  `lot_number` VARCHAR(50),
  `serial_numbers` JSON,
  `manufacturing_date` DATE,
  `expiry_date` DATE,
  `quality_status` ENUM('pending', 'accepted', 'rejected', 'quarantine') DEFAULT 'pending',
  `rejection_reason` TEXT,
  `notes` TEXT,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`receipt_id`) REFERENCES `mm_goods_receipts`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`po_line_id`) REFERENCES `mm_purchase_order_lines`(`id`),
  FOREIGN KEY (`product_id`) REFERENCES `mm_products`(`id`),
  FOREIGN KEY (`location_id`) REFERENCES `mm_warehouse_locations`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- MRP - Planificación de Necesidades de Materiales
CREATE TABLE IF NOT EXISTS `mm_mrp_runs` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `run_number` VARCHAR(50) NOT NULL,
  `run_date` DATETIME NOT NULL,
  `planning_horizon_days` INT(11) NOT NULL DEFAULT 90,
  `status` ENUM('running', 'completed', 'failed', 'cancelled') DEFAULT 'running',
  `total_products_analyzed` INT(11) DEFAULT 0,
  `total_requirements_generated` INT(11) DEFAULT 0,
  `total_purchase_requisitions` INT(11) DEFAULT 0,
  `total_production_orders` INT(11) DEFAULT 0,
  `run_parameters` JSON,
  `error_log` TEXT,
  `completed_at` DATETIME,
  `run_by` INT(11) UNSIGNED NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `company_run_number` (`company_id`, `run_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Resultados de MRP
CREATE TABLE IF NOT EXISTS `mm_mrp_requirements` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `mrp_run_id` INT(11) UNSIGNED NOT NULL,
  `product_id` INT(11) UNSIGNED NOT NULL,
  `warehouse_id` INT(11) UNSIGNED,
  `requirement_date` DATE NOT NULL,
  `gross_requirement` DECIMAL(15,3) NOT NULL,
  `scheduled_receipts` DECIMAL(15,3) DEFAULT 0.000,
  `projected_on_hand` DECIMAL(15,3) NOT NULL,
  `net_requirement` DECIMAL(15,3) NOT NULL,
  `planned_order_quantity` DECIMAL(15,3),
  `planned_order_date` DATE,
  `order_type` ENUM('purchase', 'production', 'transfer') NOT NULL,
  `lead_time_days` INT(11),
  `safety_stock` DECIMAL(15,3),
  `status` ENUM('planned', 'converted', 'cancelled') DEFAULT 'planned',
  `purchase_requisition_id` INT(11) UNSIGNED,
  `production_order_id` INT(11) UNSIGNED,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`mrp_run_id`) REFERENCES `mm_mrp_runs`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `mm_products`(`id`),
  FOREIGN KEY (`warehouse_id`) REFERENCES `mm_warehouses`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Trazabilidad de Lotes
CREATE TABLE IF NOT EXISTS `mm_lot_tracking` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `lot_number` VARCHAR(50) NOT NULL,
  `product_id` INT(11) UNSIGNED NOT NULL,
  `supplier_id` INT(11) UNSIGNED,
  `manufacturing_date` DATE,
  `expiry_date` DATE,
  `receipt_date` DATE NOT NULL,
  `receipt_id` INT(11) UNSIGNED,
  `original_quantity` DECIMAL(15,3) NOT NULL,
  `current_quantity` DECIMAL(15,3) NOT NULL,
  `unit_of_measure` VARCHAR(20) NOT NULL,
  `status` ENUM('available', 'reserved', 'quarantine', 'expired', 'consumed', 'recalled') DEFAULT 'available',
  `quality_certificate_url` VARCHAR(255),
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `mm_products`(`id`),
  FOREIGN KEY (`supplier_id`) REFERENCES `mm_suppliers`(`id`),
  UNIQUE KEY `company_lot_number` (`company_id`, `lot_number`, `product_id`),
  INDEX `idx_expiry_date` (`expiry_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Trazabilidad de Números de Serie
CREATE TABLE IF NOT EXISTS `mm_serial_tracking` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `serial_number` VARCHAR(100) NOT NULL,
  `product_id` INT(11) UNSIGNED NOT NULL,
  `lot_number` VARCHAR(50),
  `supplier_id` INT(11) UNSIGNED,
  `manufacturing_date` DATE,
  `warranty_expiry_date` DATE,
  `receipt_date` DATE NOT NULL,
  `receipt_id` INT(11) UNSIGNED,
  `warehouse_id` INT(11) UNSIGNED,
  `location_id` INT(11) UNSIGNED,
  `customer_id` INT(11) UNSIGNED,
  `sold_date` DATE,
  `invoice_id` INT(11) UNSIGNED,
  `status` ENUM('in_stock', 'reserved', 'sold', 'in_service', 'returned', 'scrapped') DEFAULT 'in_stock',
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `mm_products`(`id`),
  FOREIGN KEY (`warehouse_id`) REFERENCES `mm_warehouses`(`id`),
  UNIQUE KEY `company_serial_number` (`company_id`, `serial_number`, `product_id`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Valorización de Inventario
CREATE TABLE IF NOT EXISTS `mm_inventory_valuation` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `valuation_date` DATE NOT NULL,
  `product_id` INT(11) UNSIGNED NOT NULL,
  `warehouse_id` INT(11) UNSIGNED NOT NULL,
  `quantity_on_hand` DECIMAL(15,3) NOT NULL,
  `valuation_method` ENUM('fifo', 'lifo', 'average_cost', 'standard_cost') NOT NULL,
  `unit_cost` DECIMAL(18,4) NOT NULL,
  `total_value` DECIMAL(18,2) NOT NULL,
  `currency` VARCHAR(3) DEFAULT 'CLP',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `mm_products`(`id`),
  FOREIGN KEY (`warehouse_id`) REFERENCES `mm_warehouses`(`id`),
  UNIQUE KEY `company_valuation_date_product_warehouse` (`company_id`, `valuation_date`, `product_id`, `warehouse_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Análisis de Compras
CREATE TABLE IF NOT EXISTS `mm_purchase_analytics` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `analysis_date` DATE NOT NULL,
  `period_type` ENUM('daily', 'weekly', 'monthly', 'quarterly', 'yearly') NOT NULL,
  `total_purchase_orders` INT(11) NOT NULL,
  `total_purchase_value` DECIMAL(18,2) NOT NULL,
  `total_items_received` INT(11) NOT NULL,
  `total_suppliers` INT(11) NOT NULL,
  `average_po_value` DECIMAL(18,2) NOT NULL,
  `average_delivery_time_days` DECIMAL(5,2),
  `on_time_delivery_percentage` DECIMAL(5,2),
  `quality_acceptance_rate` DECIMAL(5,2),
  `cost_savings` DECIMAL(18,2),
  `spend_by_supplier` JSON,
  `spend_by_category` JSON,
  `top_products` JSON,
  `supplier_performance` JSON,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `company_analysis_date_period` (`company_id`, `analysis_date`, `period_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- MÓDULO 7: PRODUCCIÓN (PP) - 10 Submódulos
-- =====================================================

-- Bill of Materials (BOM) - Lista de Materiales
CREATE TABLE IF NOT EXISTS `pp_bom` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `bom_number` VARCHAR(50) NOT NULL,
  `product_id` INT(11) UNSIGNED NOT NULL,
  `bom_version` INT(11) DEFAULT 1,
  `bom_name` VARCHAR(200),
  `description` TEXT,
  `bom_type` ENUM('manufacturing', 'assembly', 'disassembly', 'kit') DEFAULT 'manufacturing',
  `base_quantity` DECIMAL(15,3) NOT NULL DEFAULT 1.000,
  `unit_of_measure` VARCHAR(20) NOT NULL,
  `routing_id` INT(11) UNSIGNED,
  `valid_from` DATE NOT NULL,
  `valid_to` DATE,
  `is_active` TINYINT(1) DEFAULT 1,
  `is_default` TINYINT(1) DEFAULT 0,
  `status` ENUM('draft', 'approved', 'active', 'obsolete') DEFAULT 'draft',
  `approved_by` INT(11) UNSIGNED,
  `approved_at` DATETIME,
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `mm_products`(`id`),
  UNIQUE KEY `company_bom_number` (`company_id`, `bom_number`),
  INDEX `idx_product` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Componentes de BOM
CREATE TABLE IF NOT EXISTS `pp_bom_components` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `bom_id` INT(11) UNSIGNED NOT NULL,
  `line_number` INT(11) NOT NULL,
  `component_product_id` INT(11) UNSIGNED NOT NULL,
  `component_type` ENUM('raw_material', 'semi_finished', 'consumable', 'phantom') DEFAULT 'raw_material',
  `quantity_required` DECIMAL(15,3) NOT NULL,
  `unit_of_measure` VARCHAR(20) NOT NULL,
  `scrap_percentage` DECIMAL(5,2) DEFAULT 0.00,
  `operation_number` INT(11),
  `is_critical` TINYINT(1) DEFAULT 0,
  `substitute_product_id` INT(11) UNSIGNED,
  `notes` TEXT,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`bom_id`) REFERENCES `pp_bom`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`component_product_id`) REFERENCES `mm_products`(`id`),
  FOREIGN KEY (`substitute_product_id`) REFERENCES `mm_products`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Centros de Trabajo
CREATE TABLE IF NOT EXISTS `pp_work_centers` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `work_center_code` VARCHAR(50) NOT NULL,
  `work_center_name` VARCHAR(200) NOT NULL,
  `work_center_type` ENUM('machine', 'assembly_line', 'workstation', 'production_line', 'quality_inspection') NOT NULL,
  `description` TEXT,
  `warehouse_id` INT(11) UNSIGNED,
  `capacity_per_hour` DECIMAL(10,2),
  `capacity_uom` VARCHAR(20),
  `efficiency_percentage` DECIMAL(5,2) DEFAULT 100.00,
  `utilization_percentage` DECIMAL(5,2) DEFAULT 0.00,
  `cost_per_hour` DECIMAL(18,2),
  `setup_time_minutes` INT(11) DEFAULT 0,
  `teardown_time_minutes` INT(11) DEFAULT 0,
  `calendar_id` INT(11) UNSIGNED,
  `responsible_id` INT(11) UNSIGNED,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`warehouse_id`) REFERENCES `mm_warehouses`(`id`),
  UNIQUE KEY `company_work_center_code` (`company_id`, `work_center_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rutas de Producción (Routing)
CREATE TABLE IF NOT EXISTS `pp_routings` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `routing_number` VARCHAR(50) NOT NULL,
  `routing_name` VARCHAR(200),
  `product_id` INT(11) UNSIGNED NOT NULL,
  `description` TEXT,
  `routing_version` INT(11) DEFAULT 1,
  `total_setup_time_minutes` INT(11) DEFAULT 0,
  `total_run_time_minutes` INT(11) DEFAULT 0,
  `valid_from` DATE NOT NULL,
  `valid_to` DATE,
  `is_active` TINYINT(1) DEFAULT 1,
  `is_default` TINYINT(1) DEFAULT 0,
  `status` ENUM('draft', 'approved', 'active', 'obsolete') DEFAULT 'draft',
  `approved_by` INT(11) UNSIGNED,
  `approved_at` DATETIME,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `mm_products`(`id`),
  UNIQUE KEY `company_routing_number` (`company_id`, `routing_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Operaciones de Ruta
CREATE TABLE IF NOT EXISTS `pp_routing_operations` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `routing_id` INT(11) UNSIGNED NOT NULL,
  `operation_number` INT(11) NOT NULL,
  `operation_name` VARCHAR(200) NOT NULL,
  `description` TEXT,
  `work_center_id` INT(11) UNSIGNED NOT NULL,
  `operation_type` ENUM('setup', 'processing', 'inspection', 'transport', 'wait') DEFAULT 'processing',
  `setup_time_minutes` INT(11) DEFAULT 0,
  `run_time_per_unit_minutes` DECIMAL(10,2) NOT NULL,
  `wait_time_minutes` INT(11) DEFAULT 0,
  `move_time_minutes` INT(11) DEFAULT 0,
  `minimum_transfer_quantity` DECIMAL(10,2),
  `is_critical_path` TINYINT(1) DEFAULT 0,
  `quality_inspection_required` TINYINT(1) DEFAULT 0,
  `next_operation_number` INT(11),
  `instructions` TEXT,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`routing_id`) REFERENCES `pp_routings`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`work_center_id`) REFERENCES `pp_work_centers`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Órdenes de Producción
CREATE TABLE IF NOT EXISTS `pp_production_orders` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `production_order_number` VARCHAR(50) NOT NULL,
  `order_type` ENUM('standard', 'rework', 'batch', 'continuous') DEFAULT 'standard',
  `product_id` INT(11) UNSIGNED NOT NULL,
  `bom_id` INT(11) UNSIGNED,
  `routing_id` INT(11) UNSIGNED,
  `warehouse_id` INT(11) UNSIGNED,
  `sales_order_id` INT(11) UNSIGNED,
  `quantity_to_produce` DECIMAL(15,3) NOT NULL,
  `quantity_produced` DECIMAL(15,3) DEFAULT 0.000,
  `quantity_scrapped` DECIMAL(15,3) DEFAULT 0.000,
  `unit_of_measure` VARCHAR(20) NOT NULL,
  `start_date_planned` DATE NOT NULL,
  `end_date_planned` DATE NOT NULL,
  `start_date_actual` DATE,
  `end_date_actual` DATE,
  `priority` ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
  `status` ENUM('planned', 'released', 'in_progress', 'on_hold', 'completed', 'cancelled') DEFAULT 'planned',
  `completion_percentage` DECIMAL(5,2) DEFAULT 0.00,
  `standard_cost` DECIMAL(18,2),
  `actual_cost` DECIMAL(18,2) DEFAULT 0.00,
  `cost_variance` DECIMAL(18,2),
  `responsible_id` INT(11) UNSIGNED,
  `notes` TEXT,
  `created_by` INT(11) UNSIGNED NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `mm_products`(`id`),
  FOREIGN KEY (`bom_id`) REFERENCES `pp_bom`(`id`),
  FOREIGN KEY (`routing_id`) REFERENCES `pp_routings`(`id`),
  FOREIGN KEY (`warehouse_id`) REFERENCES `mm_warehouses`(`id`),
  UNIQUE KEY `company_production_order_number` (`company_id`, `production_order_number`),
  INDEX `idx_status` (`status`),
  INDEX `idx_start_date` (`start_date_planned`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Materiales Requeridos para Producción
CREATE TABLE IF NOT EXISTS `pp_production_order_materials` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `production_order_id` INT(11) UNSIGNED NOT NULL,
  `line_number` INT(11) NOT NULL,
  `product_id` INT(11) UNSIGNED NOT NULL,
  `quantity_required` DECIMAL(15,3) NOT NULL,
  `quantity_issued` DECIMAL(15,3) DEFAULT 0.000,
  `quantity_returned` DECIMAL(15,3) DEFAULT 0.000,
  `unit_of_measure` VARCHAR(20) NOT NULL,
  `warehouse_id` INT(11) UNSIGNED,
  `standard_cost` DECIMAL(18,4),
  `actual_cost` DECIMAL(18,4),
  `issue_status` ENUM('not_issued', 'partially_issued', 'fully_issued') DEFAULT 'not_issued',
  PRIMARY KEY (`id`),
  FOREIGN KEY (`production_order_id`) REFERENCES `pp_production_orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `mm_products`(`id`),
  FOREIGN KEY (`warehouse_id`) REFERENCES `mm_warehouses`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Operaciones de Orden de Producción
CREATE TABLE IF NOT EXISTS `pp_production_order_operations` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `production_order_id` INT(11) UNSIGNED NOT NULL,
  `operation_number` INT(11) NOT NULL,
  `routing_operation_id` INT(11) UNSIGNED NOT NULL,
  `work_center_id` INT(11) UNSIGNED NOT NULL,
  `operation_name` VARCHAR(200) NOT NULL,
  `setup_time_planned_minutes` INT(11),
  `run_time_planned_minutes` INT(11),
  `setup_time_actual_minutes` INT(11),
  `run_time_actual_minutes` INT(11),
  `quantity_to_process` DECIMAL(15,3) NOT NULL,
  `quantity_processed` DECIMAL(15,3) DEFAULT 0.000,
  `quantity_scrapped` DECIMAL(15,3) DEFAULT 0.000,
  `start_date_planned` DATETIME,
  `end_date_planned` DATETIME,
  `start_date_actual` DATETIME,
  `end_date_actual` DATETIME,
  `status` ENUM('pending', 'in_progress', 'completed', 'on_hold', 'cancelled') DEFAULT 'pending',
  `operator_id` INT(11) UNSIGNED,
  `notes` TEXT,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`production_order_id`) REFERENCES `pp_production_orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`routing_operation_id`) REFERENCES `pp_routing_operations`(`id`),
  FOREIGN KEY (`work_center_id`) REFERENCES `pp_work_centers`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Control de Calidad en Producción
CREATE TABLE IF NOT EXISTS `pp_quality_inspections` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `inspection_number` VARCHAR(50) NOT NULL,
  `inspection_date` DATETIME NOT NULL,
  `inspection_type` ENUM('incoming', 'in_process', 'final', 'random') NOT NULL,
  `production_order_id` INT(11) UNSIGNED,
  `operation_id` INT(11) UNSIGNED,
  `product_id` INT(11) UNSIGNED NOT NULL,
  `lot_number` VARCHAR(50),
  `quantity_inspected` DECIMAL(15,3) NOT NULL,
  `quantity_accepted` DECIMAL(15,3) DEFAULT 0.000,
  `quantity_rejected` DECIMAL(15,3) DEFAULT 0.000,
  `quantity_rework` DECIMAL(15,3) DEFAULT 0.000,
  `inspector_id` INT(11) UNSIGNED NOT NULL,
  `inspection_result` ENUM('passed', 'failed', 'partial', 'pending') DEFAULT 'pending',
  `defect_codes` JSON,
  `corrective_actions` TEXT,
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`production_order_id`) REFERENCES `pp_production_orders`(`id`),
  FOREIGN KEY (`product_id`) REFERENCES `mm_products`(`id`),
  UNIQUE KEY `company_inspection_number` (`company_id`, `inspection_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Defectos de Calidad
CREATE TABLE IF NOT EXISTS `pp_quality_defects` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `inspection_id` INT(11) UNSIGNED NOT NULL,
  `defect_code` VARCHAR(50) NOT NULL,
  `defect_description` VARCHAR(255) NOT NULL,
  `quantity` DECIMAL(15,3) NOT NULL,
  `severity` ENUM('minor', 'major', 'critical') NOT NULL,
  `disposition` ENUM('rework', 'scrap', 'use_as_is', 'return_to_supplier') NOT NULL,
  `notes` TEXT,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`inspection_id`) REFERENCES `pp_quality_inspections`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Mantenimiento de Equipos
CREATE TABLE IF NOT EXISTS `pp_maintenance_schedules` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `schedule_number` VARCHAR(50) NOT NULL,
  `work_center_id` INT(11) UNSIGNED NOT NULL,
  `maintenance_type` ENUM('preventive', 'corrective', 'predictive', 'breakdown') NOT NULL,
  `frequency` ENUM('daily', 'weekly', 'monthly', 'quarterly', 'yearly', 'hours_based', 'cycles_based') NOT NULL,
  `frequency_value` INT(11),
  `description` TEXT,
  `estimated_duration_hours` DECIMAL(5,2),
  `responsible_id` INT(11) UNSIGNED,
  `last_maintenance_date` DATE,
  `next_maintenance_date` DATE,
  `status` ENUM('active', 'completed', 'overdue', 'cancelled') DEFAULT 'active',
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`work_center_id`) REFERENCES `pp_work_centers`(`id`),
  UNIQUE KEY `company_schedule_number` (`company_id`, `schedule_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Órdenes de Mantenimiento
CREATE TABLE IF NOT EXISTS `pp_maintenance_orders` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `order_number` VARCHAR(50) NOT NULL,
  `schedule_id` INT(11) UNSIGNED,
  `work_center_id` INT(11) UNSIGNED NOT NULL,
  `maintenance_type` ENUM('preventive', 'corrective', 'predictive', 'breakdown') NOT NULL,
  `priority` ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
  `problem_description` TEXT,
  `planned_start_date` DATETIME,
  `planned_end_date` DATETIME,
  `actual_start_date` DATETIME,
  `actual_end_date` DATETIME,
  `duration_hours` DECIMAL(5,2),
  `downtime_hours` DECIMAL(5,2),
  `labor_cost` DECIMAL(18,2),
  `parts_cost` DECIMAL(18,2),
  `total_cost` DECIMAL(18,2),
  `assigned_to` INT(11) UNSIGNED,
  `status` ENUM('scheduled', 'in_progress', 'completed', 'cancelled') DEFAULT 'scheduled',
  `work_performed` TEXT,
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`schedule_id`) REFERENCES `pp_maintenance_schedules`(`id`),
  FOREIGN KEY (`work_center_id`) REFERENCES `pp_work_centers`(`id`),
  UNIQUE KEY `company_order_number` (`company_id`, `order_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Planificación de Capacidad
CREATE TABLE IF NOT EXISTS `pp_capacity_planning` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `planning_date` DATE NOT NULL,
  `work_center_id` INT(11) UNSIGNED NOT NULL,
  `available_capacity_hours` DECIMAL(10,2) NOT NULL,
  `required_capacity_hours` DECIMAL(10,2) NOT NULL,
  `utilized_capacity_hours` DECIMAL(10,2) DEFAULT 0.00,
  `capacity_variance_hours` DECIMAL(10,2),
  `utilization_percentage` DECIMAL(5,2),
  `status` ENUM('under_capacity', 'optimal', 'over_capacity') NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`work_center_id`) REFERENCES `pp_work_centers`(`id`),
  UNIQUE KEY `company_planning_date_work_center` (`company_id`, `planning_date`, `work_center_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Costos de Producción
CREATE TABLE IF NOT EXISTS `pp_production_costs` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `production_order_id` INT(11) UNSIGNED NOT NULL,
  `cost_type` ENUM('material', 'labor', 'machine', 'overhead', 'scrap', 'rework') NOT NULL,
  `cost_element` VARCHAR(100),
  `standard_cost` DECIMAL(18,2),
  `actual_cost` DECIMAL(18,2) NOT NULL,
  `cost_variance` DECIMAL(18,2),
  `quantity` DECIMAL(15,3),
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`production_order_id`) REFERENCES `pp_production_orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Fórmulas (para industrias de proceso)
CREATE TABLE IF NOT EXISTS `pp_formulas` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `formula_number` VARCHAR(50) NOT NULL,
  `formula_name` VARCHAR(200) NOT NULL,
  `product_id` INT(11) UNSIGNED NOT NULL,
  `formula_version` INT(11) DEFAULT 1,
  `batch_size` DECIMAL(15,3) NOT NULL,
  `batch_uom` VARCHAR(20) NOT NULL,
  `yield_percentage` DECIMAL(5,2) DEFAULT 100.00,
  `processing_time_minutes` INT(11),
  `temperature_celsius` DECIMAL(5,2),
  `pressure_bar` DECIMAL(5,2),
  `ph_level` DECIMAL(4,2),
  `mixing_speed_rpm` INT(11),
  `instructions` TEXT,
  `safety_notes` TEXT,
  `valid_from` DATE NOT NULL,
  `valid_to` DATE,
  `is_active` TINYINT(1) DEFAULT 1,
  `status` ENUM('draft', 'approved', 'active', 'obsolete') DEFAULT 'draft',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `mm_products`(`id`),
  UNIQUE KEY `company_formula_number` (`company_id`, `formula_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ingredientes de Fórmula
CREATE TABLE IF NOT EXISTS `pp_formula_ingredients` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `formula_id` INT(11) UNSIGNED NOT NULL,
  `line_number` INT(11) NOT NULL,
  `ingredient_product_id` INT(11) UNSIGNED NOT NULL,
  `quantity_required` DECIMAL(15,3) NOT NULL,
  `unit_of_measure` VARCHAR(20) NOT NULL,
  `percentage_of_total` DECIMAL(5,2),
  `tolerance_percentage` DECIMAL(5,2) DEFAULT 0.00,
  `addition_stage` VARCHAR(100),
  `addition_temperature` DECIMAL(5,2),
  `is_active_ingredient` TINYINT(1) DEFAULT 0,
  `notes` TEXT,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`formula_id`) REFERENCES `pp_formulas`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`ingredient_product_id`) REFERENCES `mm_products`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Continue in Part 4 with HCM, SCM, CRM, LOY, BI, and integrations (Previred, SII, Time Clocks)
