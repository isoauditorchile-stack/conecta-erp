-- =====================================================
-- CONECTA ERP - INSTALACIÓN COMPLETA DESDE CERO
-- Sistema de Producción REAL v2.0.0
-- =====================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- =====================================================
-- TABLA: modules (14 módulos principales)
-- =====================================================
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
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_code` (`code`),
  INDEX `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar 14 módulos principales
INSERT INTO `modules` (`code`, `name`, `description`, `icon`, `color`, `url`, `sort_order`) VALUES
('FI', 'Finanzas', 'Gestión Financiera y Contable', 'fa-dollar-sign', '#10b981', '/modules/fi/', 1),
('CO', 'Controlling', 'Control de Gestión y Costos', 'fa-chart-pie', '#3b82f6', '/modules/co/', 2),
('SD', 'Ventas', 'Ventas y Distribución', 'fa-shopping-cart', '#ef4444', '/modules/sd/', 3),
('MM', 'Materiales', 'Gestión de Materiales', 'fa-boxes', '#f59e0b', '/modules/mm/', 4),
('PP', 'Producción', 'Planificación de Producción', 'fa-industry', '#8b5cf6', '/modules/pp/', 5),
('HCM', 'RRHH', 'Gestión de Capital Humano', 'fa-users', '#ec4899', '/modules/hcm/', 6),
('SCM', 'Cadena de Suministro', 'Supply Chain Management', 'fa-truck', '#14b8a6', '/modules/scm/', 7),
('CRM', 'CRM', 'Gestión de Relaciones con Clientes', 'fa-handshake', '#f97316', '/modules/crm/', 8),
('LOY', 'Fidelización', 'Programas de Lealtad', 'fa-gift', '#a855f7', '/modules/loy/', 9),
('BI', 'Inteligencia de Negocios', 'Business Intelligence', 'fa-chart-line', '#06b6d4', '/modules/bi/', 10),
('FE', 'Facturación Electrónica', 'Documentos Tributarios Electrónicos', 'fa-file-invoice', '#84cc16', '/modules/fe/', 11),
('PM', 'Proyectos', 'Gestión de Proyectos', 'fa-tasks', '#6366f1', '/modules/pm/', 12),
('ADM', 'Administración', 'Administración del Sistema', 'fa-cog', '#64748b', '/modules/adm/', 13),
('MOB', 'Mobile', 'Aplicaciones Móviles', 'fa-mobile-alt', '#0ea5e9', '/modules/mob/', 14);

-- =====================================================
-- TABLA: submodules (106 submódulos)
-- =====================================================
CREATE TABLE IF NOT EXISTS `submodules` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `module_id` INT(11) UNSIGNED NOT NULL,
  `code` VARCHAR(20) NOT NULL UNIQUE,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT,
  `url` VARCHAR(255),
  `file` VARCHAR(255),
  `icon` VARCHAR(50),
  `sort_order` INT(11) DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`module_id`) REFERENCES `modules` (`id`) ON DELETE CASCADE,
  INDEX `idx_module` (`module_id`),
  INDEX `idx_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar submódulos FI (11)
INSERT INTO `submodules` (`module_id`, `code`, `name`, `description`, `file`, `icon`, `sort_order`) VALUES
((SELECT id FROM modules WHERE code = 'FI'), 'FI-GL', 'Libro Mayor', 'Gestión de plan de cuentas y mayor general', 'libro_mayor.php', 'fa-book', 1),
((SELECT id FROM modules WHERE code = 'FI'), 'FI-AR', 'Cuentas por Cobrar', 'Administración de facturas y cobros a clientes', 'cuentas_por_cobrar.php', 'fa-hand-holding-usd', 2),
((SELECT id FROM modules WHERE code = 'FI'), 'FI-AP', 'Cuentas por Pagar', 'Gestión de facturas y pagos a proveedores', 'cuentas_por_pagar.php', 'fa-file-invoice-dollar', 3),
((SELECT id FROM modules WHERE code = 'FI'), 'FI-FA', 'Activos Fijos', 'Control de activos fijos y depreciación', 'activos_fijos.php', 'fa-building', 4),
((SELECT id FROM modules WHERE code = 'FI'), 'FI-BK', 'Bancos', 'Conciliación bancaria y gestión de cuentas', 'bancos.php', 'fa-university', 5),
((SELECT id FROM modules WHERE code = 'FI'), 'FI-TR', 'Tesorería', 'Control de caja, bancos y flujo de efectivo', 'tesoreria.php', 'fa-cash-register', 6),
((SELECT id FROM modules WHERE code = 'FI'), 'FI-CL', 'Cierre Contable', 'Procesos de cierre mensual y anual', 'cierre_contable.php', 'fa-calendar-check', 7),
((SELECT id FROM modules WHERE code = 'FI'), 'FI-JE', 'Asientos Contables', 'Registro y gestión de asientos contables', 'asientos_contables.php', 'fa-pencil-alt', 8),
((SELECT id FROM modules WHERE code = 'FI'), 'FI-RP', 'Reportes Financieros', 'Balance, Estado de Resultados, etc.', 'reportes.php', 'fa-file-pdf', 9),
((SELECT id FROM modules WHERE code = 'FI'), 'FI-IF', 'NIIF/IFRS', 'Estándares Internacionales de Información Financiera', 'niif.php', 'fa-globe', 10),
((SELECT id FROM modules WHERE code = 'FI'), 'FI-AB', 'Libros Contables', 'Libros auxiliares y registros oficiales', 'libros_contables.php', 'fa-books', 11);

-- Insertar submódulos CO (8)
INSERT INTO `submodules` (`module_id`, `code`, `name`, `description`, `file`, `icon`, `sort_order`) VALUES
((SELECT id FROM modules WHERE code = 'CO'), 'CO-CC', 'Centros de Costo', 'Definición y control de centros de costo', 'centros_costo.php', 'fa-sitemap', 1),
((SELECT id FROM modules WHERE code = 'CO'), 'CO-IO', 'Órdenes Internas', 'Gestión de órdenes internas', 'ordenes_internas.php', 'fa-clipboard-list', 2),
((SELECT id FROM modules WHERE code = 'CO'), 'CO-PR', 'Rentabilidad', 'Análisis de rentabilidad por segmento', 'rentabilidad.php', 'fa-chart-line', 3),
((SELECT id FROM modules WHERE code = 'CO'), 'CO-PJ', 'Proyectos', 'Contabilidad de proyectos', 'proyectos.php', 'fa-project-diagram', 4),
((SELECT id FROM modules WHERE code = 'CO'), 'CO-BG', 'Presupuestos', 'Planificación y control presupuestario', 'presupuestos.php', 'fa-calculator', 5),
((SELECT id FROM modules WHERE code = 'CO'), 'CO-EX', 'Gastos', 'Control de gastos operacionales', 'gastos.php', 'fa-money-bill-wave', 6),
((SELECT id FROM modules WHERE code = 'CO'), 'CO-IN', 'Inversiones', 'Gestión de inversiones', 'inversiones.php', 'fa-piggy-bank', 7),
((SELECT id FROM modules WHERE code = 'CO'), 'CO-ABC', 'Costeo ABC', 'Activity Based Costing', 'costeo_abc.php', 'fa-cubes', 8);

-- Insertar submódulos SD (9)
INSERT INTO `submodules` (`module_id`, `code`, `name`, `description`, `file`, `icon`, `sort_order`) VALUES
((SELECT id FROM modules WHERE code = 'SD'), 'SD-CU', 'Clientes', 'Maestro de clientes y segmentación', 'clientes.php', 'fa-user-tie', 1),
((SELECT id FROM modules WHERE code = 'SD'), 'SD-QT', 'Cotizaciones', 'Gestión de cotizaciones y propuestas', 'cotizaciones.php', 'fa-file-alt', 2),
((SELECT id FROM modules WHERE code = 'SD'), 'SD-SO', 'Órdenes de Venta', 'Procesamiento de pedidos', 'ordenes_venta.php', 'fa-receipt', 3),
((SELECT id FROM modules WHERE code = 'SD'), 'SD-IV', 'Facturas', 'Facturación y notas de crédito', 'facturas.php', 'fa-file-invoice', 4),
((SELECT id FROM modules WHERE code = 'SD'), 'SD-PR', 'Precios', 'Listas de precios y condiciones', 'precios.php', 'fa-tags', 5),
((SELECT id FROM modules WHERE code = 'SD'), 'SD-POS', 'Punto de Venta', 'Sistema POS para ventas directas', 'punto_venta.php', 'fa-cash-register', 6),
((SELECT id FROM modules WHERE code = 'SD'), 'SD-EC', 'Comercio Electrónico', 'Integración con tiendas online', 'comercio_electronico.php', 'fa-shopping-basket', 7),
((SELECT id FROM modules WHERE code = 'SD'), 'SD-CM', 'Comisiones', 'Cálculo de comisiones de ventas', 'comisiones.php', 'fa-percentage', 8),
((SELECT id FROM modules WHERE code = 'SD'), 'SD-AN', 'Analítica de Ventas', 'Dashboards y KPIs de ventas', 'analitica.php', 'fa-chart-bar', 9);

-- Insertar submódulos MM (8)
INSERT INTO `submodules` (`module_id`, `code`, `name`, `description`, `file`, `icon`, `sort_order`) VALUES
((SELECT id FROM modules WHERE code = 'MM'), 'MM-PR', 'Productos', 'Maestro de productos y SKUs', 'productos.php', 'fa-box-open', 1),
((SELECT id FROM modules WHERE code = 'MM'), 'MM-PO', 'Órdenes de Compra', 'Gestión de compras', 'ordenes_compra.php', 'fa-shopping-cart', 2),
((SELECT id FROM modules WHERE code = 'MM'), 'MM-GR', 'Recepción de Mercadería', 'Entrada de materiales', 'recepcion_mercaderia.php', 'fa-truck-loading', 3),
((SELECT id FROM modules WHERE code = 'MM'), 'MM-IV', 'Inventario', 'Control de stock y valorización', 'inventario.php', 'fa-warehouse', 4),
((SELECT id FROM modules WHERE code = 'MM'), 'MM-WH', 'Almacenes', 'Gestión de bodegas y ubicaciones', 'almacenes.php', 'fa-pallet', 5),
((SELECT id FROM modules WHERE code = 'MM'), 'MM-SU', 'Proveedores', 'Maestro de proveedores', 'proveedores.php', 'fa-truck', 6),
((SELECT id FROM modules WHERE code = 'MM'), 'MM-MRP', 'Planificación de Materiales', 'MRP y requerimientos', 'planificacion_materiales.php', 'fa-calendar-alt', 7),
((SELECT id FROM modules WHERE code = 'MM'), 'MM-TR', 'Trazabilidad', 'Lotes, series y trazabilidad', 'trazabilidad.php', 'fa-barcode', 8);

-- Insertar submódulos PP (10)
INSERT INTO `submodules` (`module_id`, `code`, `name`, `description`, `file`, `icon`, `sort_order`) VALUES
((SELECT id FROM modules WHERE code = 'PP'), 'PP-PO', 'Órdenes de Producción', 'Planificación y ejecución de producción', 'ordenes_produccion.php', 'fa-industry', 1),
((SELECT id FROM modules WHERE code = 'PP'), 'PP-BOM', 'Lista de Materiales (BOM)', 'Bill of Materials', 'lista_materiales.php', 'fa-list-ol', 2),
((SELECT id FROM modules WHERE code = 'PP'), 'PP-RT', 'Rutas de Producción', 'Secuencia de operaciones', 'rutas.php', 'fa-route', 3),
((SELECT id FROM modules WHERE code = 'PP'), 'PP-WC', 'Centros de Trabajo', 'Máquinas y recursos productivos', 'centros_trabajo.php', 'fa-cogs', 4),
((SELECT id FROM modules WHERE code = 'PP'), 'PP-CP', 'Capacidad', 'Planificación de capacidad', 'capacidad.php', 'fa-tachometer-alt', 5),
((SELECT id FROM modules WHERE code = 'PP'), 'PP-QC', 'Control de Calidad', 'Inspecciones y no conformidades', 'calidad.php', 'fa-check-circle', 6),
((SELECT id FROM modules WHERE code = 'PP'), 'PP-MRP', 'Planificación Avanzada (MRP II)', 'Manufacturing Resource Planning', 'planificacion_avanzada.php', 'fa-brain', 7),
((SELECT id FROM modules WHERE code = 'PP'), 'PP-CT', 'Costos de Producción', 'Costos reales vs estándar', 'costos.php', 'fa-dollar-sign', 8),
((SELECT id FROM modules WHERE code = 'PP'), 'PP-MT', 'Mantenimiento', 'Mantenimiento preventivo y correctivo', 'mantenimiento.php', 'fa-wrench', 9),
((SELECT id FROM modules WHERE code = 'PP'), 'PP-FM', 'Fórmulas', 'Recetas y fórmulas de producción', 'formulas.php', 'fa-flask', 10);

-- Insertar submódulos HCM (11)
INSERT INTO `submodules` (`module_id`, `code`, `name`, `description`, `file`, `icon`, `sort_order`) VALUES
((SELECT id FROM modules WHERE code = 'HCM'), 'HCM-EMP', 'Empleados', 'Maestro de empleados', 'empleados.php', 'fa-id-card', 1),
((SELECT id FROM modules WHERE code = 'HCM'), 'HCM-REC', 'Reclutamiento', 'Procesos de selección', 'reclutamiento.php', 'fa-user-plus', 2),
((SELECT id FROM modules WHERE code = 'HCM'), 'HCM-ONB', 'Incorporación', 'Onboarding de nuevos empleados', 'incorporacion.php', 'fa-user-check', 3),
((SELECT id FROM modules WHERE code = 'HCM'), 'HCM-PAY', 'Nómina', 'Cálculo de remuneraciones', 'nomina.php', 'fa-money-check-alt', 4),
((SELECT id FROM modules WHERE code = 'HCM'), 'HCM-ATT', 'Asistencia', 'Control de asistencia y horas', 'asistencia.php', 'fa-clock', 5),
((SELECT id FROM modules WHERE code = 'HCM'), 'HCM-LV', 'Ausencias y Licencias', 'Vacaciones, permisos, licencias', 'ausencias.php', 'fa-calendar-times', 6),
((SELECT id FROM modules WHERE code = 'HCM'), 'HCM-BEN', 'Beneficios', 'Seguros, bonos, beneficios', 'beneficios.php', 'fa-gift', 7),
((SELECT id FROM modules WHERE code = 'HCM'), 'HCM-PER', 'Desempeño', 'Evaluaciones de desempeño', 'desempeno.php', 'fa-star', 8),
((SELECT id FROM modules WHERE code = 'HCM'), 'HCM-TRN', 'Capacitación', 'Planes de formación', 'capacitacion.php', 'fa-graduation-cap', 9),
((SELECT id FROM modules WHERE code = 'HCM'), 'HCM-ORG', 'Desarrollo Organizacional', 'Organigrama y desarrollo', 'desarrollo_organizacional.php', 'fa-sitemap', 10),
((SELECT id FROM modules WHERE code = 'HCM'), 'HCM-RPT', 'Reportes de RRHH', 'Informes y estadísticas', 'reportes.php', 'fa-file-excel', 11);

-- =====================================================
-- TABLA: users (usuarios del sistema)
-- =====================================================
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `firstname` VARCHAR(100) NOT NULL,
  `lastname` VARCHAR(100) NOT NULL,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `company_name` VARCHAR(255),
  `company_id` INT(11) UNSIGNED DEFAULT NULL,
  `tax_id` VARCHAR(50),
  `tax_id_type` VARCHAR(50),
  `position` VARCHAR(100),
  `industry` VARCHAR(100),
  `phone` VARCHAR(20),
  `website` VARCHAR(255),
  `address` VARCHAR(255),
  `city` VARCHAR(100),
  `state_province` VARCHAR(100),
  `postal_code` VARCHAR(20),
  `country` VARCHAR(3),
  `currency` VARCHAR(3) DEFAULT 'USD',
  `language` VARCHAR(2) DEFAULT 'es',
  `preferred_language` VARCHAR(2) DEFAULT 'es',
  `timezone` VARCHAR(50) DEFAULT 'America/Santiago',
  `employees` VARCHAR(20),
  `plan_actual` VARCHAR(50) DEFAULT 'TRIAL',
  `suscripcion_activa_id` INT(11) UNSIGNED DEFAULT NULL,
  `is_admin` TINYINT(1) DEFAULT 0,
  `status` ENUM('trial', 'active', 'suspended', 'expired', 'pending_approval', 'rejected') DEFAULT 'pending_approval',
  `trial_days` INT(11) DEFAULT 14,
  `trial_ends_at` DATETIME DEFAULT NULL,
  `requires_approval` TINYINT(1) DEFAULT 1,
  `approved_by_admin` TINYINT(1) DEFAULT 0,
  `last_login` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_email` (`email`),
  INDEX `idx_username` (`username`),
  INDEX `idx_status` (`status`),
  INDEX `idx_plan_actual` (`plan_actual`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: activity_log (log de actividad)
-- =====================================================
CREATE TABLE IF NOT EXISTS `activity_log` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED DEFAULT NULL,
  `action` VARCHAR(100) NOT NULL,
  `description` TEXT,
  `module` VARCHAR(50),
  `ip_address` VARCHAR(45),
  `user_agent` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_user` (`user_id`),
  INDEX `idx_action` (`action`),
  INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;
