-- =====================================================
-- CONECTA ERP v2.0.0 - SCHEMA COMPLETO PARTE 5 FINAL
-- Módulos: SCM, CRM, LOY, BI (15 submódulos)
-- INTEGRACIÓN SII (Servicio de Impuestos Internos - Chile)
-- Configuración y Administración
-- =====================================================

-- =====================================================
-- MÓDULO 9: SUPPLY CHAIN MANAGEMENT (SCM) - 10 Submódulos
-- =====================================================

-- Rutas de Distribución
CREATE TABLE IF NOT EXISTS `scm_routes` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `route_code` VARCHAR(50) NOT NULL,
  `route_name` VARCHAR(200) NOT NULL,
  `route_type` ENUM('delivery', 'pickup', 'mixed', 'express') DEFAULT 'delivery',
  `origin_warehouse_id` INT(11) UNSIGNED,
  `origin_address` TEXT,
  `total_distance_km` DECIMAL(10,2),
  `estimated_duration_minutes` INT(11),
  `max_stops` INT(11),
  `max_weight_kg` DECIMAL(10,2),
  `max_volume_m3` DECIMAL(10,2),
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`origin_warehouse_id`) REFERENCES `mm_warehouses`(`id`),
  UNIQUE KEY `company_route_code` (`company_id`, `route_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Paradas de Ruta
CREATE TABLE IF NOT EXISTS `scm_route_stops` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `route_id` INT(11) UNSIGNED NOT NULL,
  `stop_sequence` INT(11) NOT NULL,
  `stop_type` ENUM('delivery', 'pickup', 'both') NOT NULL,
  `customer_id` INT(11) UNSIGNED,
  `address` TEXT NOT NULL,
  `city` VARCHAR(100),
  `postal_code` VARCHAR(20),
  `latitude` DECIMAL(10,8),
  `longitude` DECIMAL(11,8),
  `estimated_time_minutes` INT(11),
  `notes` TEXT,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`route_id`) REFERENCES `scm_routes`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`customer_id`) REFERENCES `sd_customers`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Vehículos/Flota
CREATE TABLE IF NOT EXISTS `scm_vehicles` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `vehicle_code` VARCHAR(50) NOT NULL,
  `license_plate` VARCHAR(20) NOT NULL,
  `vehicle_type` ENUM('truck', 'van', 'motorcycle', 'bicycle', 'car') NOT NULL,
  `make` VARCHAR(100),
  `model` VARCHAR(100),
  `year` INT(4),
  `vin` VARCHAR(50),
  `color` VARCHAR(50),
  `fuel_type` ENUM('gasoline', 'diesel', 'electric', 'hybrid', 'lpg') DEFAULT 'gasoline',
  `capacity_weight_kg` DECIMAL(10,2),
  `capacity_volume_m3` DECIMAL(10,2),
  `current_mileage_km` DECIMAL(10,2) DEFAULT 0.00,
  `fuel_consumption_km_per_liter` DECIMAL(5,2),
  `status` ENUM('active', 'in_maintenance', 'out_of_service', 'sold', 'retired') DEFAULT 'active',
  `assigned_driver_id` INT(11) UNSIGNED,
  `gps_device_id` VARCHAR(100),
  `insurance_company` VARCHAR(150),
  `insurance_policy_number` VARCHAR(100),
  `insurance_expiry_date` DATE,
  `registration_expiry_date` DATE,
  `last_maintenance_date` DATE,
  `next_maintenance_date` DATE,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `company_vehicle_code` (`company_id`, `vehicle_code`),
  UNIQUE KEY `license_plate` (`license_plate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Conductores
CREATE TABLE IF NOT EXISTS `scm_drivers` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `employee_id` INT(11) UNSIGNED,
  `driver_code` VARCHAR(50) NOT NULL,
  `first_name` VARCHAR(100) NOT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `tax_id` VARCHAR(50),
  `license_number` VARCHAR(50) NOT NULL,
  `license_type` VARCHAR(20) NOT NULL,
  `license_expiry_date` DATE NOT NULL,
  `phone` VARCHAR(50),
  `mobile` VARCHAR(50) NOT NULL,
  `email` VARCHAR(150),
  `status` ENUM('active', 'on_leave', 'suspended', 'terminated') DEFAULT 'active',
  `rating` DECIMAL(2,1),
  `total_deliveries` INT(11) DEFAULT 0,
  `on_time_percentage` DECIMAL(5,2),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`employee_id`) REFERENCES `hcm_employees`(`id`),
  UNIQUE KEY `company_driver_code` (`company_id`, `driver_code`),
  UNIQUE KEY `license_number` (`license_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Despachos/Envíos
CREATE TABLE IF NOT EXISTS `scm_shipments` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `shipment_number` VARCHAR(50) NOT NULL,
  `shipment_date` DATETIME NOT NULL,
  `shipment_type` ENUM('outbound', 'inbound', 'transfer', 'return') DEFAULT 'outbound',
  `order_id` INT(11) UNSIGNED,
  `customer_id` INT(11) UNSIGNED,
  `origin_warehouse_id` INT(11) UNSIGNED NOT NULL,
  `destination_type` ENUM('customer', 'warehouse', 'supplier', 'other') NOT NULL,
  `destination_warehouse_id` INT(11) UNSIGNED,
  `destination_address` TEXT,
  `destination_city` VARCHAR(100),
  `destination_postal_code` VARCHAR(20),
  `destination_country` VARCHAR(3) DEFAULT 'CL',
  `contact_name` VARCHAR(150),
  `contact_phone` VARCHAR(50),
  `total_packages` INT(11) DEFAULT 1,
  `total_weight_kg` DECIMAL(10,2),
  `total_volume_m3` DECIMAL(10,2),
  `carrier` VARCHAR(200),
  `tracking_number` VARCHAR(100),
  `vehicle_id` INT(11) UNSIGNED,
  `driver_id` INT(11) UNSIGNED,
  `route_id` INT(11) UNSIGNED,
  `planned_pickup_date` DATETIME,
  `actual_pickup_date` DATETIME,
  `planned_delivery_date` DATETIME,
  `actual_delivery_date` DATETIME,
  `status` ENUM('draft', 'pending', 'assigned', 'in_transit', 'out_for_delivery', 'delivered', 'failed_delivery', 'returned', 'cancelled') DEFAULT 'draft',
  `delivery_proof_url` VARCHAR(255),
  `signature_url` VARCHAR(255),
  `delivered_to` VARCHAR(150),
  `shipping_cost` DECIMAL(18,2),
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`order_id`) REFERENCES `sd_sales_orders`(`id`),
  FOREIGN KEY (`customer_id`) REFERENCES `sd_customers`(`id`),
  FOREIGN KEY (`origin_warehouse_id`) REFERENCES `mm_warehouses`(`id`),
  FOREIGN KEY (`destination_warehouse_id`) REFERENCES `mm_warehouses`(`id`),
  FOREIGN KEY (`vehicle_id`) REFERENCES `scm_vehicles`(`id`),
  FOREIGN KEY (`driver_id`) REFERENCES `scm_drivers`(`id`),
  FOREIGN KEY (`route_id`) REFERENCES `scm_routes`(`id`),
  UNIQUE KEY `company_shipment_number` (`company_id`, `shipment_number`),
  INDEX `idx_tracking_number` (`tracking_number`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Paquetes de Envío
CREATE TABLE IF NOT EXISTS `scm_shipment_packages` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `shipment_id` INT(11) UNSIGNED NOT NULL,
  `package_number` INT(11) NOT NULL,
  `tracking_number` VARCHAR(100),
  `package_type` ENUM('box', 'pallet', 'envelope', 'tube', 'other') DEFAULT 'box',
  `length_cm` DECIMAL(8,2),
  `width_cm` DECIMAL(8,2),
  `height_cm` DECIMAL(8,2),
  `weight_kg` DECIMAL(10,2),
  `volume_m3` DECIMAL(10,3),
  `barcode` VARCHAR(100),
  `contents_description` TEXT,
  `declared_value` DECIMAL(18,2),
  `is_fragile` TINYINT(1) DEFAULT 0,
  `requires_signature` TINYINT(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`shipment_id`) REFERENCES `scm_shipments`(`id`) ON DELETE CASCADE,
  INDEX `idx_tracking_number` (`tracking_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Items de Paquete
CREATE TABLE IF NOT EXISTS `scm_package_items` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `package_id` INT(11) UNSIGNED NOT NULL,
  `product_id` INT(11) UNSIGNED NOT NULL,
  `quantity` DECIMAL(10,2) NOT NULL,
  `serial_numbers` JSON,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`package_id`) REFERENCES `scm_shipment_packages`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `mm_products`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seguimiento GPS
CREATE TABLE IF NOT EXISTS `scm_gps_tracking` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `vehicle_id` INT(11) UNSIGNED NOT NULL,
  `shipment_id` INT(11) UNSIGNED,
  `timestamp` DATETIME NOT NULL,
  `latitude` DECIMAL(10,8) NOT NULL,
  `longitude` DECIMAL(11,8) NOT NULL,
  `altitude_m` DECIMAL(8,2),
  `speed_kmh` DECIMAL(6,2),
  `heading_degrees` DECIMAL(5,2),
  `address` VARCHAR(255),
  `ignition_on` TINYINT(1),
  `fuel_level_percentage` DECIMAL(5,2),
  `temperature_celsius` DECIMAL(5,2),
  `event_type` ENUM('position', 'stop', 'start', 'speeding', 'geofence_enter', 'geofence_exit', 'sos', 'harsh_braking', 'rapid_acceleration'),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`vehicle_id`) REFERENCES `scm_vehicles`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`shipment_id`) REFERENCES `scm_shipments`(`id`),
  INDEX `idx_timestamp` (`timestamp`),
  INDEX `idx_vehicle_timestamp` (`vehicle_id`, `timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Proveedores Logísticos (3PL)
CREATE TABLE IF NOT EXISTS `scm_logistics_providers` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `provider_code` VARCHAR(50) NOT NULL,
  `provider_name` VARCHAR(200) NOT NULL,
  `provider_type` ENUM('courier', '3pl', 'freight_forwarder', 'customs_broker') NOT NULL,
  `tax_id` VARCHAR(50),
  `contact_name` VARCHAR(150),
  `email` VARCHAR(150),
  `phone` VARCHAR(50),
  `website` VARCHAR(255),
  `address` TEXT,
  `city` VARCHAR(100),
  `country` VARCHAR(3) DEFAULT 'CL',
  `api_endpoint` VARCHAR(255),
  `api_key` VARCHAR(255),
  `integration_enabled` TINYINT(1) DEFAULT 0,
  `rating` DECIMAL(2,1),
  `performance_score` DECIMAL(5,2),
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `company_provider_code` (`company_id`, `provider_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tarifas de Fletes
CREATE TABLE IF NOT EXISTS `scm_freight_rates` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `provider_id` INT(11) UNSIGNED NOT NULL,
  `rate_name` VARCHAR(200) NOT NULL,
  `origin_zone` VARCHAR(100),
  `destination_zone` VARCHAR(100),
  `service_type` ENUM('express', 'standard', 'economy', 'freight') NOT NULL,
  `weight_min_kg` DECIMAL(10,2),
  `weight_max_kg` DECIMAL(10,2),
  `base_rate` DECIMAL(18,2) NOT NULL,
  `rate_per_kg` DECIMAL(18,4),
  `rate_per_km` DECIMAL(18,4),
  `fuel_surcharge_percentage` DECIMAL(5,2),
  `currency` VARCHAR(3) DEFAULT 'CLP',
  `valid_from` DATE NOT NULL,
  `valid_to` DATE,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`provider_id`) REFERENCES `scm_logistics_providers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Planificación de Demanda
CREATE TABLE IF NOT EXISTS `scm_demand_forecast` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `product_id` INT(11) UNSIGNED NOT NULL,
  `warehouse_id` INT(11) UNSIGNED,
  `forecast_date` DATE NOT NULL,
  `forecast_period` ENUM('daily', 'weekly', 'monthly') NOT NULL,
  `historical_demand` DECIMAL(15,3),
  `forecast_demand` DECIMAL(15,3) NOT NULL,
  `forecast_method` ENUM('moving_average', 'exponential_smoothing', 'regression', 'ml_model', 'manual') NOT NULL,
  `confidence_level` DECIMAL(5,2),
  `safety_stock_recommended` DECIMAL(15,3),
  `reorder_point_recommended` DECIMAL(15,3),
  `forecast_accuracy_percentage` DECIMAL(5,2),
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `mm_products`(`id`),
  FOREIGN KEY (`warehouse_id`) REFERENCES `mm_warehouses`(`id`),
  UNIQUE KEY `company_product_warehouse_date_period` (`company_id`, `product_id`, `warehouse_id`, `forecast_date`, `forecast_period`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- KPIs de SCM
CREATE TABLE IF NOT EXISTS `scm_analytics` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `analysis_date` DATE NOT NULL,
  `period_type` ENUM('daily', 'weekly', 'monthly', 'quarterly', 'yearly') NOT NULL,
  `total_shipments` INT(11) NOT NULL,
  `on_time_deliveries` INT(11) NOT NULL,
  `late_deliveries` INT(11) NOT NULL,
  `failed_deliveries` INT(11) NOT NULL,
  `on_time_delivery_percentage` DECIMAL(5,2) NOT NULL,
  `average_delivery_time_hours` DECIMAL(8,2),
  `total_distance_km` DECIMAL(12,2),
  `total_fuel_cost` DECIMAL(18,2),
  `cost_per_km` DECIMAL(10,4),
  `cost_per_shipment` DECIMAL(18,2),
  `vehicle_utilization_percentage` DECIMAL(5,2),
  `inventory_turnover_ratio` DECIMAL(8,4),
  `perfect_order_rate` DECIMAL(5,2),
  `kpis_data` JSON,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `company_analysis_date_period` (`company_id`, `analysis_date`, `period_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- MÓDULO 10: CRM - 8 Submódulos
-- =====================================================

-- Contactos 360°
CREATE TABLE IF NOT EXISTS `crm_contacts` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `contact_type` ENUM('lead', 'prospect', 'customer', 'partner', 'vendor') DEFAULT 'lead',
  `customer_id` INT(11) UNSIGNED,
  `first_name` VARCHAR(100) NOT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `full_name` VARCHAR(255) NOT NULL,
  `job_title` VARCHAR(150),
  `company_name` VARCHAR(255),
  `email_primary` VARCHAR(150),
  `email_secondary` VARCHAR(150),
  `phone_primary` VARCHAR(50),
  `phone_secondary` VARCHAR(50),
  `mobile` VARCHAR(50),
  `linkedin_url` VARCHAR(255),
  `twitter_handle` VARCHAR(100),
  `address` TEXT,
  `city` VARCHAR(100),
  `country` VARCHAR(3) DEFAULT 'CL',
  `source` ENUM('website', 'referral', 'social_media', 'cold_call', 'event', 'advertisement', 'other') NOT NULL,
  `lead_score` INT(11) DEFAULT 0,
  `lifecycle_stage` ENUM('subscriber', 'lead', 'mql', 'sql', 'opportunity', 'customer', 'evangelist', 'other') DEFAULT 'lead',
  `assigned_to` INT(11) UNSIGNED,
  `status` ENUM('active', 'inactive', 'do_not_contact', 'bounced') DEFAULT 'active',
  `tags` JSON,
  `custom_fields` JSON,
  `last_contacted_at` DATETIME,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`customer_id`) REFERENCES `sd_customers`(`id`),
  INDEX `idx_email_primary` (`email_primary`),
  INDEX `idx_lifecycle_stage` (`lifecycle_stage`),
  INDEX `idx_assigned_to` (`assigned_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Leads
CREATE TABLE IF NOT EXISTS `crm_leads` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `contact_id` INT(11) UNSIGNED NOT NULL,
  `lead_number` VARCHAR(50) NOT NULL,
  `lead_source` ENUM('website', 'referral', 'social_media', 'cold_call', 'event', 'advertisement', 'other') NOT NULL,
  `lead_status` ENUM('new', 'contacted', 'qualified', 'unqualified', 'converted', 'lost') DEFAULT 'new',
  `lead_score` INT(11) DEFAULT 0,
  `qualification_criteria` JSON,
  `budget_range` VARCHAR(100),
  `decision_timeframe` ENUM('immediate', '1_month', '3_months', '6_months', '1_year', 'unknown'),
  `pain_points` TEXT,
  `interests` TEXT,
  `assigned_to` INT(11) UNSIGNED,
  `converted_to_opportunity_id` INT(11) UNSIGNED,
  `converted_at` DATETIME,
  `lost_reason` TEXT,
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`contact_id`) REFERENCES `crm_contacts`(`id`),
  UNIQUE KEY `company_lead_number` (`company_id`, `lead_number`),
  INDEX `idx_lead_status` (`lead_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Oportunidades
CREATE TABLE IF NOT EXISTS `crm_opportunities` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `opportunity_number` VARCHAR(50) NOT NULL,
  `opportunity_name` VARCHAR(200) NOT NULL,
  `contact_id` INT(11) UNSIGNED NOT NULL,
  `customer_id` INT(11) UNSIGNED,
  `lead_id` INT(11) UNSIGNED,
  `opportunity_type` ENUM('new_business', 'existing_business', 'upsell', 'cross_sell', 'renewal') NOT NULL,
  `stage` ENUM('prospecting', 'qualification', 'needs_analysis', 'proposal', 'negotiation', 'closed_won', 'closed_lost') DEFAULT 'prospecting',
  `probability_percentage` DECIMAL(5,2) DEFAULT 0.00,
  `expected_revenue` DECIMAL(18,2) NOT NULL,
  `weighted_revenue` DECIMAL(18,2),
  `currency` VARCHAR(3) DEFAULT 'CLP',
  `expected_close_date` DATE NOT NULL,
  `actual_close_date` DATE,
  `decision_maker` VARCHAR(150),
  `competitors` TEXT,
  `next_step` VARCHAR(255),
  `loss_reason` TEXT,
  `assigned_to` INT(11) UNSIGNED,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`contact_id`) REFERENCES `crm_contacts`(`id`),
  FOREIGN KEY (`customer_id`) REFERENCES `sd_customers`(`id`),
  FOREIGN KEY (`lead_id`) REFERENCES `crm_leads`(`id`),
  UNIQUE KEY `company_opportunity_number` (`company_id`, `opportunity_number`),
  INDEX `idx_stage` (`stage`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Productos en Oportunidad
CREATE TABLE IF NOT EXISTS `crm_opportunity_products` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `opportunity_id` INT(11) UNSIGNED NOT NULL,
  `product_id` INT(11) UNSIGNED NOT NULL,
  `quantity` DECIMAL(10,2) NOT NULL,
  `unit_price` DECIMAL(18,4) NOT NULL,
  `discount_percentage` DECIMAL(5,2) DEFAULT 0.00,
  `total_price` DECIMAL(18,2) NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`opportunity_id`) REFERENCES `crm_opportunities`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `mm_products`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Actividades CRM
CREATE TABLE IF NOT EXISTS `crm_activities` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `activity_type` ENUM('call', 'email', 'meeting', 'task', 'note', 'whatsapp', 'sms') NOT NULL,
  `subject` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `contact_id` INT(11) UNSIGNED,
  `lead_id` INT(11) UNSIGNED,
  `opportunity_id` INT(11) UNSIGNED,
  `customer_id` INT(11) UNSIGNED,
  `assigned_to` INT(11) UNSIGNED,
  `due_date` DATETIME,
  `completed_at` DATETIME,
  `duration_minutes` INT(11),
  `priority` ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
  `status` ENUM('planned', 'in_progress', 'completed', 'cancelled') DEFAULT 'planned',
  `outcome` ENUM('successful', 'unsuccessful', 'no_answer', 'left_voicemail', 'reschedule'),
  `next_action` VARCHAR(255),
  `created_by` INT(11) UNSIGNED NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`contact_id`) REFERENCES `crm_contacts`(`id`),
  FOREIGN KEY (`lead_id`) REFERENCES `crm_leads`(`id`),
  FOREIGN KEY (`opportunity_id`) REFERENCES `crm_opportunities`(`id`),
  FOREIGN KEY (`customer_id`) REFERENCES `sd_customers`(`id`),
  INDEX `idx_due_date` (`due_date`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Campañas de Marketing
CREATE TABLE IF NOT EXISTS `crm_campaigns` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `campaign_code` VARCHAR(50) NOT NULL,
  `campaign_name` VARCHAR(200) NOT NULL,
  `campaign_type` ENUM('email', 'social_media', 'webinar', 'event', 'direct_mail', 'advertising', 'mixed') NOT NULL,
  `description` TEXT,
  `start_date` DATE NOT NULL,
  `end_date` DATE,
  `budget` DECIMAL(18,2),
  `actual_cost` DECIMAL(18,2) DEFAULT 0.00,
  `currency` VARCHAR(3) DEFAULT 'CLP',
  `target_audience_size` INT(11),
  `total_sent` INT(11) DEFAULT 0,
  `total_delivered` INT(11) DEFAULT 0,
  `total_opened` INT(11) DEFAULT 0,
  `total_clicked` INT(11) DEFAULT 0,
  `total_converted` INT(11) DEFAULT 0,
  `conversion_rate_percentage` DECIMAL(5,2) DEFAULT 0.00,
  `roi_percentage` DECIMAL(8,2),
  `status` ENUM('draft', 'scheduled', 'active', 'paused', 'completed', 'cancelled') DEFAULT 'draft',
  `assigned_to` INT(11) UNSIGNED,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `company_campaign_code` (`company_id`, `campaign_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Miembros de Campaña
CREATE TABLE IF NOT EXISTS `crm_campaign_members` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `campaign_id` INT(11) UNSIGNED NOT NULL,
  `contact_id` INT(11) UNSIGNED NOT NULL,
  `status` ENUM('sent', 'delivered', 'opened', 'clicked', 'bounced', 'unsubscribed', 'converted') DEFAULT 'sent',
  `sent_at` DATETIME,
  `delivered_at` DATETIME,
  `opened_at` DATETIME,
  `first_click_at` DATETIME,
  `converted_at` DATETIME,
  `bounce_reason` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`campaign_id`) REFERENCES `crm_campaigns`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`contact_id`) REFERENCES `crm_contacts`(`id`),
  UNIQUE KEY `campaign_contact` (`campaign_id`, `contact_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tickets de Soporte
CREATE TABLE IF NOT EXISTS `crm_tickets` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `ticket_number` VARCHAR(50) NOT NULL,
  `subject` VARCHAR(255) NOT NULL,
  `description` TEXT NOT NULL,
  `contact_id` INT(11) UNSIGNED,
  `customer_id` INT(11) UNSIGNED,
  `ticket_type` ENUM('question', 'problem', 'feature_request', 'bug', 'complaint', 'other') DEFAULT 'question',
  `priority` ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
  `status` ENUM('new', 'open', 'pending', 'on_hold', 'solved', 'closed') DEFAULT 'new',
  `channel` ENUM('email', 'phone', 'chat', 'web_form', 'social_media') NOT NULL,
  `category` VARCHAR(100),
  `product_id` INT(11) UNSIGNED,
  `assigned_to` INT(11) UNSIGNED,
  `assigned_team` VARCHAR(100),
  `first_response_at` DATETIME,
  `resolved_at` DATETIME,
  `closed_at` DATETIME,
  `satisfaction_rating` ENUM('very_satisfied', 'satisfied', 'neutral', 'dissatisfied', 'very_dissatisfied'),
  `satisfaction_comment` TEXT,
  `created_by` INT(11) UNSIGNED,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`contact_id`) REFERENCES `crm_contacts`(`id`),
  FOREIGN KEY (`customer_id`) REFERENCES `sd_customers`(`id`),
  FOREIGN KEY (`product_id`) REFERENCES `mm_products`(`id`),
  UNIQUE KEY `company_ticket_number` (`company_id`, `ticket_number`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Respuestas de Tickets
CREATE TABLE IF NOT EXISTS `crm_ticket_replies` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `ticket_id` INT(11) UNSIGNED NOT NULL,
  `reply_type` ENUM('note', 'reply', 'forward') DEFAULT 'reply',
  `message` TEXT NOT NULL,
  `is_internal` TINYINT(1) DEFAULT 0,
  `attachments` JSON,
  `created_by` INT(11) UNSIGNED NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`ticket_id`) REFERENCES `crm_tickets`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Análisis Predictivo CRM
CREATE TABLE IF NOT EXISTS `crm_predictive_analytics` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `analysis_date` DATE NOT NULL,
  `lead_score_model_accuracy` DECIMAL(5,2),
  `churn_prediction_accuracy` DECIMAL(5,2),
  `next_best_action_recommendations` JSON,
  `customer_lifetime_value_predictions` JSON,
  `sales_forecast` JSON,
  `model_version` VARCHAR(50),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- MÓDULO 11: FIDELIZACIÓN (LOY) - 7 Submódulos
-- =====================================================

-- Programas de Fidelización
CREATE TABLE IF NOT EXISTS `loy_programs` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `program_code` VARCHAR(50) NOT NULL,
  `program_name` VARCHAR(200) NOT NULL,
  `description` TEXT,
  `program_type` ENUM('points', 'tiers', 'cashback', 'stamps', 'referral') DEFAULT 'points',
  `currency_ratio` DECIMAL(10,4) DEFAULT 1.0000,
  `points_per_currency_unit` DECIMAL(10,2) DEFAULT 1.00,
  `min_redemption_points` INT(11) DEFAULT 100,
  `points_expiry_days` INT(11),
  `tier_upgrade_points` JSON,
  `is_active` TINYINT(1) DEFAULT 1,
  `start_date` DATE NOT NULL,
  `end_date` DATE,
  `terms_and_conditions` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `company_program_code` (`company_id`, `program_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Miembros del Programa
CREATE TABLE IF NOT EXISTS `loy_members` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `program_id` INT(11) UNSIGNED NOT NULL,
  `customer_id` INT(11) UNSIGNED NOT NULL,
  `member_number` VARCHAR(50) NOT NULL,
  `enrollment_date` DATE NOT NULL,
  `current_points_balance` INT(11) DEFAULT 0,
  `lifetime_points_earned` INT(11) DEFAULT 0,
  `lifetime_points_redeemed` INT(11) DEFAULT 0,
  `current_tier` ENUM('bronze', 'silver', 'gold', 'platinum', 'diamond') DEFAULT 'bronze',
  `tier_valid_until` DATE,
  `status` ENUM('active', 'inactive', 'suspended', 'cancelled') DEFAULT 'active',
  `last_activity_date` DATE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`program_id`) REFERENCES `loy_programs`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`customer_id`) REFERENCES `sd_customers`(`id`),
  UNIQUE KEY `program_customer` (`program_id`, `customer_id`),
  UNIQUE KEY `member_number` (`member_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Transacciones de Puntos
CREATE TABLE IF NOT EXISTS `loy_point_transactions` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `member_id` INT(11) UNSIGNED NOT NULL,
  `transaction_date` DATETIME NOT NULL,
  `transaction_type` ENUM('earned', 'redeemed', 'expired', 'adjusted', 'refunded') NOT NULL,
  `points` INT(11) NOT NULL,
  `balance_after` INT(11) NOT NULL,
  `source_type` ENUM('purchase', 'bonus', 'referral', 'review', 'birthday', 'manual', 'redemption') NOT NULL,
  `source_id` INT(11) UNSIGNED,
  `source_reference` VARCHAR(100),
  `description` VARCHAR(255),
  `expiry_date` DATE,
  `created_by` INT(11) UNSIGNED,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`member_id`) REFERENCES `loy_members`(`id`) ON DELETE CASCADE,
  INDEX `idx_transaction_date` (`transaction_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Catálogo de Recompensas
CREATE TABLE IF NOT EXISTS `loy_rewards` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `program_id` INT(11) UNSIGNED NOT NULL,
  `reward_code` VARCHAR(50) NOT NULL,
  `reward_name` VARCHAR(200) NOT NULL,
  `description` TEXT,
  `reward_type` ENUM('discount_percentage', 'discount_fixed', 'free_product', 'free_shipping', 'gift_card', 'experience') NOT NULL,
  `points_required` INT(11) NOT NULL,
  `tier_requirement` ENUM('bronze', 'silver', 'gold', 'platinum', 'diamond'),
  `discount_percentage` DECIMAL(5,2),
  `discount_amount` DECIMAL(18,2),
  `product_id` INT(11) UNSIGNED,
  `quantity_available` INT(11),
  `quantity_redeemed` INT(11) DEFAULT 0,
  `max_redemptions_per_member` INT(11),
  `valid_from` DATE NOT NULL,
  `valid_to` DATE,
  `is_active` TINYINT(1) DEFAULT 1,
  `image_url` VARCHAR(255),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`program_id`) REFERENCES `loy_programs`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `mm_products`(`id`),
  UNIQUE KEY `program_reward_code` (`program_id`, `reward_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Canjes de Recompensas
CREATE TABLE IF NOT EXISTS `loy_redemptions` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `member_id` INT(11) UNSIGNED NOT NULL,
  `reward_id` INT(11) UNSIGNED NOT NULL,
  `redemption_number` VARCHAR(50) NOT NULL,
  `redemption_date` DATETIME NOT NULL,
  `points_redeemed` INT(11) NOT NULL,
  `coupon_code` VARCHAR(50),
  `order_id` INT(11) UNSIGNED,
  `status` ENUM('pending', 'approved', 'fulfilled', 'cancelled', 'expired') DEFAULT 'pending',
  `expiry_date` DATE,
  `used_at` DATETIME,
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`member_id`) REFERENCES `loy_members`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`reward_id`) REFERENCES `loy_rewards`(`id`),
  FOREIGN KEY (`order_id`) REFERENCES `sd_sales_orders`(`id`),
  UNIQUE KEY `redemption_number` (`redemption_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Gamificación
CREATE TABLE IF NOT EXISTS `loy_achievements` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `program_id` INT(11) UNSIGNED NOT NULL,
  `achievement_code` VARCHAR(50) NOT NULL,
  `achievement_name` VARCHAR(200) NOT NULL,
  `description` TEXT,
  `achievement_type` ENUM('badge', 'milestone', 'challenge', 'streak') NOT NULL,
  `criteria` JSON,
  `points_reward` INT(11) DEFAULT 0,
  `icon_url` VARCHAR(255),
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`program_id`) REFERENCES `loy_programs`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `program_achievement_code` (`program_id`, `achievement_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Logros de Miembros
CREATE TABLE IF NOT EXISTS `loy_member_achievements` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `member_id` INT(11) UNSIGNED NOT NULL,
  `achievement_id` INT(11) UNSIGNED NOT NULL,
  `earned_date` DATETIME NOT NULL,
  `progress_percentage` DECIMAL(5,2) DEFAULT 100.00,
  `points_awarded` INT(11),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`member_id`) REFERENCES `loy_members`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`achievement_id`) REFERENCES `loy_achievements`(`id`),
  UNIQUE KEY `member_achievement` (`member_id`, `achievement_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cupones Digitales
CREATE TABLE IF NOT EXISTS `loy_coupons` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `program_id` INT(11) UNSIGNED NOT NULL,
  `coupon_code` VARCHAR(50) NOT NULL,
  `coupon_name` VARCHAR(200) NOT NULL,
  `description` TEXT,
  `discount_type` ENUM('percentage', 'fixed_amount', 'free_shipping', 'buy_x_get_y') NOT NULL,
  `discount_percentage` DECIMAL(5,2),
  `discount_amount` DECIMAL(18,2),
  `min_purchase_amount` DECIMAL(18,2),
  `max_discount_amount` DECIMAL(18,2),
  `applicable_products` JSON,
  `applicable_categories` JSON,
  `valid_from` DATETIME NOT NULL,
  `valid_to` DATETIME NOT NULL,
  `usage_limit` INT(11),
  `usage_limit_per_customer` INT(11) DEFAULT 1,
  `current_usage_count` INT(11) DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`program_id`) REFERENCES `loy_programs`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `coupon_code` (`coupon_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- MÓDULO 12: BUSINESS INTELLIGENCE (BI) - 15 Submódulos
-- =====================================================

-- Dashboards
CREATE TABLE IF NOT EXISTS `bi_dashboards` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `dashboard_code` VARCHAR(50) NOT NULL,
  `dashboard_name` VARCHAR(200) NOT NULL,
  `description` TEXT,
  `dashboard_type` ENUM('executive', 'operational', 'analytical', 'tactical', 'custom') NOT NULL,
  `category` ENUM('sales', 'finance', 'operations', 'hr', 'inventory', 'production', 'scm', 'crm', 'custom') NOT NULL,
  `layout_config` JSON,
  `refresh_frequency_minutes` INT(11) DEFAULT 60,
  `is_public` TINYINT(1) DEFAULT 0,
  `allowed_users` JSON,
  `allowed_roles` JSON,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_by` INT(11) UNSIGNED NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `company_dashboard_code` (`company_id`, `dashboard_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Widgets de Dashboard
CREATE TABLE IF NOT EXISTS `bi_dashboard_widgets` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `dashboard_id` INT(11) UNSIGNED NOT NULL,
  `widget_type` ENUM('kpi_card', 'chart_line', 'chart_bar', 'chart_pie', 'chart_donut', 'table', 'gauge', 'map', 'funnel', 'heatmap') NOT NULL,
  `widget_title` VARCHAR(200) NOT NULL,
  `data_source` VARCHAR(200) NOT NULL,
  `query` TEXT,
  `chart_config` JSON,
  `position_x` INT(11) DEFAULT 0,
  `position_y` INT(11) DEFAULT 0,
  `width` INT(11) DEFAULT 4,
  `height` INT(11) DEFAULT 3,
  `refresh_frequency_seconds` INT(11) DEFAULT 300,
  `is_drilldown_enabled` TINYINT(1) DEFAULT 0,
  `drilldown_config` JSON,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`dashboard_id`) REFERENCES `bi_dashboards`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- KPIs Definidos
CREATE TABLE IF NOT EXISTS `bi_kpis` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `kpi_code` VARCHAR(50) NOT NULL,
  `kpi_name` VARCHAR(200) NOT NULL,
  `description` TEXT,
  `category` ENUM('sales', 'finance', 'operations', 'hr', 'customer', 'inventory', 'production', 'quality', 'scm', 'custom') NOT NULL,
  `calculation_formula` TEXT,
  `data_source` VARCHAR(200),
  `unit_of_measure` VARCHAR(50),
  `target_value` DECIMAL(18,2),
  `warning_threshold` DECIMAL(18,2),
  `critical_threshold` DECIMAL(18,2),
  `higher_is_better` TINYINT(1) DEFAULT 1,
  `refresh_frequency` ENUM('realtime', 'hourly', 'daily', 'weekly', 'monthly') DEFAULT 'daily',
  `owner_id` INT(11) UNSIGNED,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `company_kpi_code` (`company_id`, `kpi_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Valores de KPIs
CREATE TABLE IF NOT EXISTS `bi_kpi_values` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `kpi_id` INT(11) UNSIGNED NOT NULL,
  `measurement_date` DATE NOT NULL,
  `measurement_timestamp` DATETIME NOT NULL,
  `actual_value` DECIMAL(18,4) NOT NULL,
  `target_value` DECIMAL(18,4),
  `variance` DECIMAL(18,4),
  `variance_percentage` DECIMAL(8,4),
  `status` ENUM('excellent', 'good', 'warning', 'critical') NOT NULL,
  `trend` ENUM('up', 'down', 'stable') NOT NULL,
  `period_type` ENUM('daily', 'weekly', 'monthly', 'quarterly', 'yearly') NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`kpi_id`) REFERENCES `bi_kpis`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `kpi_measurement_date` (`kpi_id`, `measurement_date`, `period_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reportes Guardados
CREATE TABLE IF NOT EXISTS `bi_saved_reports` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `report_code` VARCHAR(50) NOT NULL,
  `report_name` VARCHAR(200) NOT NULL,
  `report_type` ENUM('sales', 'financial', 'inventory', 'production', 'hr', 'purchases', 'quality', 'maintenance', 'logistics', 'projects', 'custom') NOT NULL,
  `description` TEXT,
  `query` TEXT,
  `parameters_schema` JSON,
  `default_parameters` JSON,
  `grouping_fields` JSON,
  `sorting_fields` JSON,
  `filters` JSON,
  `columns_config` JSON,
  `export_formats` JSON,
  `scheduling_config` JSON,
  `is_scheduled` TINYINT(1) DEFAULT 0,
  `created_by` INT(11) UNSIGNED NOT NULL,
  `is_public` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `company_report_code` (`company_id`, `report_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ejecuciones de Reportes
CREATE TABLE IF NOT EXISTS `bi_report_executions` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `report_id` INT(11) UNSIGNED NOT NULL,
  `executed_by` INT(11) UNSIGNED,
  `execution_date` DATETIME NOT NULL,
  `parameters_used` JSON,
  `rows_returned` INT(11),
  `execution_time_ms` INT(11),
  `file_path` VARCHAR(255),
  `file_format` ENUM('pdf', 'excel', 'csv', 'html', 'json') NOT NULL,
  `file_size_bytes` INT(11),
  `status` ENUM('success', 'failed', 'timeout') DEFAULT 'success',
  `error_message` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`report_id`) REFERENCES `bi_saved_reports`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Modelos de Machine Learning
CREATE TABLE IF NOT EXISTS `bi_ml_models` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `model_code` VARCHAR(50) NOT NULL,
  `model_name` VARCHAR(200) NOT NULL,
  `model_type` ENUM('regression', 'classification', 'clustering', 'forecasting', 'anomaly_detection', 'recommendation') NOT NULL,
  `use_case` ENUM('sales_forecast', 'demand_forecast', 'churn_prediction', 'lead_scoring', 'price_optimization', 'inventory_optimization', 'quality_prediction', 'custom') NOT NULL,
  `algorithm` VARCHAR(100),
  `training_dataset_size` INT(11),
  `features` JSON,
  `hyperparameters` JSON,
  `accuracy_score` DECIMAL(5,4),
  `precision_score` DECIMAL(5,4),
  `recall_score` DECIMAL(5,4),
  `f1_score` DECIMAL(5,4),
  `model_path` VARCHAR(255),
  `training_date` DATETIME,
  `last_prediction_date` DATETIME,
  `total_predictions` INT(11) DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `company_model_code` (`company_id`, `model_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Predicciones
CREATE TABLE IF NOT EXISTS `bi_predictions` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `model_id` INT(11) UNSIGNED NOT NULL,
  `entity_type` VARCHAR(100),
  `entity_id` INT(11) UNSIGNED,
  `prediction_date` DATETIME NOT NULL,
  `input_features` JSON,
  `predicted_value` DECIMAL(18,4),
  `predicted_class` VARCHAR(100),
  `confidence_score` DECIMAL(5,4),
  `actual_value` DECIMAL(18,4),
  `is_accurate` TINYINT(1),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`model_id`) REFERENCES `bi_ml_models`(`id`) ON DELETE CASCADE,
  INDEX `idx_prediction_date` (`prediction_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data Mining Rules
CREATE TABLE IF NOT EXISTS `bi_data_mining_rules` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `rule_code` VARCHAR(50) NOT NULL,
  `rule_name` VARCHAR(200) NOT NULL,
  `rule_type` ENUM('association', 'sequence', 'classification', 'clustering', 'pattern_discovery') NOT NULL,
  `description` TEXT,
  `algorithm` VARCHAR(100),
  `confidence_percentage` DECIMAL(5,2),
  `support_percentage` DECIMAL(5,2),
  `lift` DECIMAL(10,4),
  `rule_definition` JSON,
  `discovered_date` DATETIME,
  `last_validated_date` DATETIME,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `company_rule_code` (`company_id`, `rule_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Visualizaciones Personalizadas
CREATE TABLE IF NOT EXISTS `bi_custom_visualizations` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `viz_code` VARCHAR(50) NOT NULL,
  `viz_name` VARCHAR(200) NOT NULL,
  `viz_type` ENUM('custom_chart', 'network_graph', 'sankey', 'treemap', 'sunburst', 'chord', 'force_directed') NOT NULL,
  `data_source` VARCHAR(200),
  `query` TEXT,
  `viz_config` JSON,
  `interactions_config` JSON,
  `is_public` TINYINT(1) DEFAULT 0,
  `created_by` INT(11) UNSIGNED NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `company_viz_code` (`company_id`, `viz_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Balanced Scorecard (BSC)
CREATE TABLE IF NOT EXISTS `bi_balanced_scorecard` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `scorecard_name` VARCHAR(200) NOT NULL,
  `fiscal_year` INT(4) NOT NULL,
  `perspective` ENUM('financial', 'customer', 'internal_processes', 'learning_growth') NOT NULL,
  `strategic_objective` VARCHAR(255) NOT NULL,
  `kpi_id` INT(11) UNSIGNED,
  `target_value` DECIMAL(18,2),
  `weight_percentage` DECIMAL(5,2),
  `owner_id` INT(11) UNSIGNED,
  `status` ENUM('not_started', 'in_progress', 'achieved', 'at_risk', 'failed') DEFAULT 'not_started',
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`kpi_id`) REFERENCES `bi_kpis`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ETL Jobs
CREATE TABLE IF NOT EXISTS `bi_etl_jobs` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `job_name` VARCHAR(200) NOT NULL,
  `job_type` ENUM('extract', 'transform', 'load', 'full_etl') NOT NULL,
  `source_system` VARCHAR(100),
  `source_connection` VARCHAR(255),
  `destination_system` VARCHAR(100),
  `destination_connection` VARCHAR(255),
  `transformation_logic` TEXT,
  `schedule_cron` VARCHAR(100),
  `is_enabled` TINYINT(1) DEFAULT 1,
  `last_run_at` DATETIME,
  `last_run_status` ENUM('success', 'failed', 'running', 'cancelled'),
  `last_run_duration_seconds` INT(11),
  `last_run_records_processed` INT(11),
  `last_run_error` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reportes por Módulo (15 tablas específicas no es necesario crear todas, esto es el índice)

-- =====================================================
-- INTEGRACIÓN SII (Servicio de Impuestos Internos - Chile)
-- Facturación Electrónica
-- =====================================================

-- DTE - Documentos Tributarios Electrónicos
CREATE TABLE IF NOT EXISTS `sii_dte` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `invoice_id` INT(11) UNSIGNED,
  `dte_type` ENUM('33', '34', '39', '41', '43', '46', '52', '56', '61') NOT NULL COMMENT '33=Factura, 34=Ex, 39=Boleta, 41=ExBoleta, 43=LiqFac, 46=Ex_LiqFac, 52=GuiaDesp, 56=NotaDébito, 61=NotaCrédito',
  `folio` INT(11) NOT NULL,
  `emission_date` DATE NOT NULL,
  `rut_emisor` VARCHAR(12) NOT NULL,
  `razon_social_emisor` VARCHAR(255) NOT NULL,
  `rut_receptor` VARCHAR(12) NOT NULL,
  `razon_social_receptor` VARCHAR(255) NOT NULL,
  `monto_neto` DECIMAL(18,2) NOT NULL,
  `monto_exento` DECIMAL(18,2) DEFAULT 0.00,
  `iva` DECIMAL(18,2) NOT NULL,
  `monto_total` DECIMAL(18,2) NOT NULL,
  `xml_content` LONGTEXT,
  `ted` TEXT COMMENT 'Timbre Electrónico',
  `firma_digital` TEXT,
  `estado_sii` ENUM('generado', 'enviado', 'aceptado', 'rechazado', 'anulado', 'cedido') DEFAULT 'generado',
  `track_id` VARCHAR(100),
  `fecha_envio_sii` DATETIME,
  `fecha_respuesta_sii` DATETIME,
  `codigo_respuesta_sii` VARCHAR(10),
  `glosa_respuesta_sii` TEXT,
  `pdf_url` VARCHAR(255),
  `email_sent` TINYINT(1) DEFAULT 0,
  `email_sent_at` DATETIME,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`invoice_id`) REFERENCES `sd_invoices`(`id`),
  UNIQUE KEY `company_dte_type_folio` (`company_id`, `dte_type`, `folio`),
  INDEX `idx_rut_receptor` (`rut_receptor`),
  INDEX `idx_estado_sii` (`estado_sii`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Libros de Compra y Venta (Registro IECV)
CREATE TABLE IF NOT EXISTS `sii_libro_compra_venta` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `periodo_tributario` VARCHAR(7) NOT NULL COMMENT 'YYYY-MM',
  `tipo_libro` ENUM('compra', 'venta') NOT NULL,
  `operacion` ENUM('venta', 'compra') NOT NULL,
  `tipo_docto` VARCHAR(10) NOT NULL,
  `folio` INT(11) NOT NULL,
  `fecha_docto` DATE NOT NULL,
  `rut_contraparte` VARCHAR(12) NOT NULL,
  `razon_social` VARCHAR(255) NOT NULL,
  `monto_neto` DECIMAL(18,2) NOT NULL,
  `monto_exento` DECIMAL(18,2) DEFAULT 0.00,
  `iva` DECIMAL(18,2) NOT NULL,
  `iva_no_recuperable` DECIMAL(18,2) DEFAULT 0.00,
  `iva_uso_comun` DECIMAL(18,2) DEFAULT 0.00,
  `monto_total` DECIMAL(18,2) NOT NULL,
  `estado` ENUM('pendiente', 'declarado', 'rectificado', 'anulado') DEFAULT 'pendiente',
  `fecha_declaracion` DATE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  INDEX `idx_periodo` (`periodo_tributario`),
  INDEX `idx_tipo_libro` (`tipo_libro`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Certificados Digitales
CREATE TABLE IF NOT EXISTS `sii_certificados` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `rut_titular` VARCHAR(12) NOT NULL,
  `nombre_titular` VARCHAR(255) NOT NULL,
  `tipo_certificado` ENUM('firma', 'tributario') NOT NULL,
  `certificado_pfx` MEDIUMBLOB,
  `certificado_pem` TEXT,
  `clave_privada` TEXT,
  `password_encrypted` VARCHAR(255),
  `fecha_emision` DATE NOT NULL,
  `fecha_expiracion` DATE NOT NULL,
  `estado` ENUM('vigente', 'proximo_vencimiento', 'vencido', 'revocado') DEFAULT 'vigente',
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  INDEX `idx_fecha_expiracion` (`fecha_expiracion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CAF (Código de Autorización de Folios)
CREATE TABLE IF NOT EXISTS `sii_caf` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `tipo_dte` ENUM('33', '34', '39', '41', '43', '46', '52', '56', '61') NOT NULL,
  `folio_desde` INT(11) NOT NULL,
  `folio_hasta` INT(11) NOT NULL,
  `fecha_autorizacion` DATE NOT NULL,
  `fecha_vencimiento` DATE,
  `caf_xml` MEDIUMTEXT NOT NULL,
  `folios_utilizados` INT(11) DEFAULT 0,
  `folios_disponibles` INT(11) NOT NULL,
  `estado` ENUM('vigente', 'proximo_agotarse', 'agotado', 'vencido') DEFAULT 'vigente',
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  INDEX `idx_tipo_dte` (`tipo_dte`),
  INDEX `idx_estado` (`estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cesión de Documentos (Factoring)
CREATE TABLE IF NOT EXISTS `sii_cesiones` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `dte_id` INT(11) UNSIGNED NOT NULL,
  `rut_cedente` VARCHAR(12) NOT NULL,
  `rut_cesionario` VARCHAR(12) NOT NULL,
  `monto_cedido` DECIMAL(18,2) NOT NULL,
  `fecha_cesion` DATE NOT NULL,
  `aec_xml` LONGTEXT COMMENT 'Archivo Electrónico de Cesión',
  `estado` ENUM('generado', 'enviado', 'aceptado', 'rechazado') DEFAULT 'generado',
  `track_id_sii` VARCHAR(100),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`dte_id`) REFERENCES `sii_dte`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Declaraciones Juradas (F29, F50, etc.)
CREATE TABLE IF NOT EXISTS `sii_declaraciones` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `tipo_declaracion` ENUM('F29', 'F50', 'F22', 'F1879', 'F1887') NOT NULL,
  `periodo_tributario` VARCHAR(7) NOT NULL COMMENT 'YYYY-MM',
  `monto_ventas` DECIMAL(18,2),
  `iva_debito_fiscal` DECIMAL(18,2),
  `iva_credito_fiscal` DECIMAL(18,2),
  `iva_a_pagar` DECIMAL(18,2),
  `ppm` DECIMAL(18,2),
  `total_a_pagar` DECIMAL(18,2),
  `fecha_declaracion` DATE,
  `fecha_vencimiento` DATE,
  `estado` ENUM('borrador', 'declarado', 'pagado', 'vencido') DEFAULT 'borrador',
  `numero_operacion` VARCHAR(50),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  INDEX `idx_periodo` (`periodo_tributario`),
  INDEX `idx_tipo_declaracion` (`tipo_declaracion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Configuración de Sistema
CREATE TABLE IF NOT EXISTS `system_settings` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) UNSIGNED,
  `setting_key` VARCHAR(100) NOT NULL,
  `setting_value` TEXT,
  `setting_type` ENUM('string', 'number', 'boolean', 'json', 'encrypted') DEFAULT 'string',
  `category` VARCHAR(50),
  `description` TEXT,
  `is_editable` TINYINT(1) DEFAULT 1,
  `updated_by` INT(11) UNSIGNED,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_setting_key` (`company_id`, `setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;

-- =====================================================
-- FIN DEL SCHEMA COMPLETO
-- Total tablas creadas: 200+ tablas para un ERP completo
-- Incluye: 14 módulos, 107 submódulos
-- Integraciones: Previred, SII, Relojes Control
-- Capacidades: Multi-tenant, Multi-currency, Multi-language
-- =====================================================
