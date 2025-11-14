-- =============================================
-- CONECTA ERP - INSTALACIÓN COMPLETA
-- Sistema ERP Empresarial - Versión 1.0.0
-- 14 Módulos | 106 Submódulos | 9 Idiomas | 9 Países
-- =============================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- =============================================
-- TABLAS BASE DEL SISTEMA
-- =============================================

-- Tabla: countries (Países soportados)
DROP TABLE IF EXISTS `countries`;
CREATE TABLE `countries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(3) NOT NULL COMMENT 'Código ISO del país',
  `name_es` varchar(100) NOT NULL,
  `name_en` varchar(100) NOT NULL,
  `currency_code` varchar(3) NOT NULL,
  `currency_symbol` varchar(10) NOT NULL,
  `tax_id_label` varchar(50) NOT NULL COMMENT 'RUT, DNI, RFC, etc',
  `tax_id_format` varchar(100) DEFAULT NULL,
  `tax_id_regex` varchar(255) DEFAULT NULL,
  `phone_code` varchar(10) DEFAULT NULL,
  `date_format` varchar(20) DEFAULT 'Y-m-d',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos iniciales: 9 países
INSERT INTO `countries` VALUES
(1, 'CL', 'Chile', 'Chile', 'CLP', '$', 'RUT', '##.###.###-#', '^[0-9]{1,2}\\.[0-9]{3}\\.[0-9]{3}-[0-9Kk]$', '+56', 'd/m/Y', 1, NOW()),
(2, 'AR', 'Argentina', 'Argentina', 'ARS', '$', 'CUIT/CUIL', '##-########-#', '^[0-9]{2}-[0-9]{8}-[0-9]$', '+54', 'd/m/Y', 1, NOW()),
(3, 'PE', 'Perú', 'Peru', 'PEN', 'S/', 'DNI', '########', '^[0-9]{8}$', '+51', 'd/m/Y', 1, NOW()),
(4, 'CO', 'Colombia', 'Colombia', 'COP', '$', 'NIT', '##########', '^[0-9]{10}$', '+57', 'd/m/Y', 1, NOW()),
(5, 'MX', 'México', 'Mexico', 'MXN', '$', 'RFC', '############', '^[A-Z]{4}[0-9]{6}[A-Z0-9]{3}$', '+52', 'd/m/Y', 1, NOW()),
(6, 'BR', 'Brasil', 'Brazil', 'BRL', 'R$', 'CPF', '###.###.###-##', '^[0-9]{3}\\.[0-9]{3}\\.[0-9]{3}-[0-9]{2}$', '+55', 'd/m/Y', 1, NOW()),
(7, 'US', 'Estados Unidos', 'United States', 'USD', '$', 'SSN', '###-##-####', '^[0-9]{3}-[0-9]{2}-[0-9]{4}$', '+1', 'm/d/Y', 1, NOW()),
(8, 'ES', 'España', 'Spain', 'EUR', '€', 'DNI/NIE', '########-#', '^[0-9]{8}[A-Z]$', '+34', 'd/m/Y', 1, NOW()),
(9, 'UY', 'Uruguay', 'Uruguay', 'UYU', '$', 'CI', '#.###.###-#', '^[0-9]\\.[0-9]{3}\\.[0-9]{3}-[0-9]$', '+598', 'd/m/Y', 1, NOW());

-- Tabla: languages (Idiomas soportados)
DROP TABLE IF EXISTS `languages`;
CREATE TABLE `languages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(5) NOT NULL COMMENT 'Código ISO del idioma',
  `name` varchar(50) NOT NULL,
  `native_name` varchar(50) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `flag_icon` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos: 9 idiomas
INSERT INTO `languages` VALUES
(1, 'es', 'Español', 'Español', 1, 'flag-es', NOW()),
(2, 'en', 'English', 'English', 1, 'flag-us', NOW()),
(3, 'pt', 'Português', 'Português', 1, 'flag-br', NOW()),
(4, 'fr', 'Français', 'Français', 1, 'flag-fr', NOW()),
(5, 'de', 'Deutsch', 'Deutsch', 1, 'flag-de', NOW()),
(6, 'it', 'Italiano', 'Italiano', 1, 'flag-it', NOW()),
(7, 'ru', 'Русский', 'Русский', 1, 'flag-ru', NOW()),
(8, 'zh', '中文', '中文', 1, 'flag-cn', NOW()),
(9, 'ja', '日本語', '日本語', 1, 'flag-jp', NOW());

-- Tabla: translations (Traducciones del sistema)
DROP TABLE IF EXISTS `translations`;
CREATE TABLE `translations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `language_code` varchar(5) NOT NULL,
  `translation_key` varchar(255) NOT NULL,
  `translation_value` text NOT NULL,
  `module` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lang_key_unique` (`language_code`, `translation_key`),
  KEY `language_code` (`language_code`),
  CONSTRAINT `fk_translation_language` FOREIGN KEY (`language_code`) REFERENCES `languages` (`code`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: plans (Planes del sistema)
DROP TABLE IF EXISTS `plans`;
CREATE TABLE `plans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `plan_code` varchar(50) NOT NULL,
  `plan_name` varchar(100) NOT NULL,
  `plan_description` text,
  `price_monthly` decimal(10,2) DEFAULT 0.00,
  `price_yearly` decimal(10,2) DEFAULT 0.00,
  `max_users` int(11) DEFAULT 1,
  `max_companies` int(11) DEFAULT 1,
  `max_storage_gb` int(11) DEFAULT 5,
  `features` text COMMENT 'JSON con características',
  `is_custom` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `plan_code` (`plan_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos: 4 planes
INSERT INTO `plans` VALUES
(1, 'BASIC', 'Plan Básico', 'Plan ideal para empresas pequeñas y emprendedores', 29.99, 299.90, 5, 1, 10, '{"modules": ["FI", "SD", "MM"], "support": "email"}', 0, 1, 1, NOW()),
(2, 'PROFESSIONAL', 'Plan Profesional', 'Plan completo para empresas en crecimiento', 79.99, 799.90, 20, 3, 50, '{"modules": ["FI", "SD", "MM", "PP", "HCM"], "support": "email_phone"}', 0, 1, 2, NOW()),
(3, 'ENTERPRISE', 'Plan Empresarial', 'Plan avanzado con todas las funcionalidades', 199.99, 1999.90, 100, 10, 200, '{"modules": "all", "support": "24/7", "custom_modules": true}', 0, 1, 3, NOW()),
(4, 'CUSTOM', 'Plan Personalizado', 'Plan diseñado según las necesidades específicas de tu empresa', 0.00, 0.00, 999, 999, 999, '{"custom": true, "contact": true}', 1, 1, 4, NOW());

-- Tabla: companies (Empresas/Organizaciones)
DROP TABLE IF EXISTS `companies`;
CREATE TABLE `companies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_code` varchar(50) NOT NULL,
  `company_name` varchar(200) NOT NULL,
  `legal_name` varchar(200) DEFAULT NULL,
  `tax_id` varchar(50) NOT NULL COMMENT 'RUT/DNI/RFC según país',
  `country_id` int(11) NOT NULL,
  `industry` varchar(100) DEFAULT NULL,
  `address` text,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `logo_url` varchar(255) DEFAULT NULL,
  `owner_user_id` int(11) DEFAULT NULL,
  `plan_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_code` (`company_code`),
  UNIQUE KEY `tax_id` (`tax_id`),
  KEY `country_id` (`country_id`),
  KEY `plan_id` (`plan_id`),
  KEY `owner_user_id` (`owner_user_id`),
  CONSTRAINT `fk_company_country` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`),
  CONSTRAINT `fk_company_plan` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: users (Usuarios del sistema)
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) DEFAULT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(200) NOT NULL,
  `tax_id` varchar(50) DEFAULT NULL COMMENT 'RUT/DNI personal',
  `country_id` int(11) DEFAULT NULL,
  `language_code` varchar(5) DEFAULT 'es',
  `phone` varchar(50) DEFAULT NULL,
  `avatar_url` varchar(255) DEFAULT NULL,
  `is_admin` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `status` enum('trial','active','suspended','cancelled') DEFAULT 'trial',
  `trial_ends_at` datetime DEFAULT NULL,
  `last_login_at` datetime DEFAULT NULL,
  `last_login_ip` varchar(50) DEFAULT NULL,
  `email_verified_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `username` (`username`),
  KEY `company_id` (`company_id`),
  KEY `country_id` (`country_id`),
  KEY `language_code` (`language_code`),
  CONSTRAINT `fk_user_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_user_country` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`),
  CONSTRAINT `fk_user_language` FOREIGN KEY (`language_code`) REFERENCES `languages` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Usuario administrador principal
INSERT INTO `users` (`username`, `email`, `password`, `full_name`, `is_admin`, `is_active`, `status`, `country_id`, `language_code`) VALUES
('auditorex chile', 'auditorexchile@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Auditorex Chile', 1, 1, 'active', 1, 'es');
-- Contraseña: admin123 (Debe cambiarse después del primer login)

-- Tabla: modules (Módulos principales del sistema)
DROP TABLE IF EXISTS `modules`;
CREATE TABLE `modules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `module_code` varchar(50) NOT NULL,
  `module_name` varchar(100) NOT NULL,
  `module_description` text,
  `icon` varchar(50) DEFAULT NULL,
  `color` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `module_code` (`module_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos: 14 módulos principales
INSERT INTO `modules` VALUES
(1, 'ADMIN', 'Administración Central', 'Gestión de empresas, seguridad y parametrización global', 'bi-gear-fill', '#ef4444', 1, 1, NOW()),
(2, 'ENT', 'Gestión de Entidades', 'Maestros de clientes, proveedores, empleados y productos', 'bi-people-fill', '#3b82f6', 1, 2, NOW()),
(3, 'FI', 'Finanzas', 'Contabilidad, tesorería, activos fijos y reporting financiero', 'bi-cash-stack', '#10b981', 1, 3, NOW()),
(4, 'CO', 'Controlling', 'Centros de costo, rentabilidad y análisis de resultados', 'bi-graph-up', '#f59e0b', 1, 4, NOW()),
(5, 'SD', 'Ventas', 'Pedidos, facturación, punto de venta y análisis de ventas', 'bi-cart-fill', '#8b5cf6', 1, 5, NOW()),
(6, 'MM', 'Materiales', 'Inventario, compras, almacenes y valoración', 'bi-box-seam-fill', '#06b6d4', 1, 6, NOW()),
(7, 'PP', 'Producción', 'Órdenes de producción, MRP, calidad y trazabilidad', 'bi-gear-wide-connected', '#ec4899', 1, 7, NOW()),
(8, 'HCM', 'Recursos Humanos', 'Personal, nómina, reclutamiento, evaluación y capacitación', 'bi-person-badge-fill', '#14b8a6', 1, 8, NOW()),
(9, 'SCM', 'Supply Chain', 'Logística, transporte, rutas y cadena de suministro', 'bi-truck', '#f97316', 1, 9, NOW()),
(10, 'CRM', 'CRM', 'Clientes, oportunidades, leads y pipeline de ventas', 'bi-heart-fill', '#6366f1', 1, 10, NOW()),
(11, 'FID', 'Fidelización', 'Programas de lealtad, puntos y recompensas', 'bi-star-fill', '#eab308', 1, 11, NOW()),
(12, 'BI', 'Business Intelligence', 'Dashboards, KPIs, reportes y análisis predictivo', 'bi-bar-chart-fill', '#a855f7', 1, 12, NOW()),
(13, 'CONFIG', 'Configuración', 'Configuración general del sistema', 'bi-sliders', '#64748b', 1, 13, NOW()),
(14, 'DASHBOARD', 'Dashboard Principal', 'Navegación principal y estadísticas', 'bi-speedometer2', '#0ea5e9', 1, 14, NOW());

-- Tabla: submodules (Submódulos del sistema - 106 en total)
DROP TABLE IF EXISTS `submodules`;
CREATE TABLE `submodules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `module_id` int(11) NOT NULL,
  `submodule_code` varchar(50) NOT NULL,
  `submodule_name` varchar(100) NOT NULL,
  `submodule_description` text,
  `url` varchar(255) DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `submodule_code` (`submodule_code`),
  KEY `module_id` (`module_id`),
  CONSTRAINT `fk_submodule_module` FOREIGN KEY (`module_id`) REFERENCES `modules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- SUBMÓDULOS - MÓDULO 1: ADMINISTRACIÓN (3)
-- =============================================
INSERT INTO `submodules` (module_id, submodule_code, submodule_name, submodule_description, url, icon, sort_order) VALUES
(1, 'ADMIN_EMPRESAS', 'Gestión de Empresas', 'Administración de empresas y organizaciones', 'modules/admin/companies.php', 'bi-building', 1),
(1, 'ADMIN_SECURITY', 'Gestión de Seguridad', 'Usuarios, roles y permisos', 'modules/admin/security.php', 'bi-shield-lock', 2),
(1, 'ADMIN_PARAMS', 'Parametrización Global', 'Configuración de parámetros del sistema', 'modules/admin/parameters.php', 'bi-sliders', 3);

-- =============================================
-- SUBMÓDULOS - MÓDULO 2: GESTIÓN DE ENTIDADES (5)
-- =============================================
INSERT INTO `submodules` (module_id, submodule_code, submodule_name, submodule_description, url, icon, sort_order) VALUES
(2, 'ENT_MAESTROS', 'Entidades Maestras', 'Gestión de entidades maestras', 'modules/ent/maestros.php', 'bi-database', 1),
(2, 'ENT_CLIENTES', 'Gestión de Clientes', 'Administración de clientes', 'modules/ent/clientes.php', 'bi-person-check', 2),
(2, 'ENT_PROVEEDORES', 'Gestión de Proveedores', 'Administración de proveedores', 'modules/ent/proveedores.php', 'bi-person-lines-fill', 3),
(2, 'ENT_EMPLEADOS', 'Gestión de Empleados', 'Administración de empleados', 'modules/ent/empleados.php', 'bi-person-badge', 4),
(2, 'ENT_PRODUCTOS', 'Productos y Servicios', 'Catálogo de productos y servicios', 'modules/ent/productos.php', 'bi-box', 5);

-- =============================================
-- SUBMÓDULOS - MÓDULO 3: FINANZAS (11)
-- =============================================
INSERT INTO `submodules` (module_id, submodule_code, submodule_name, submodule_description, url, icon, sort_order) VALUES
(3, 'FI_CONTABILIDAD', 'Contabilidad General', 'Libro mayor, asientos contables', 'modules/fi/contabilidad.php', 'bi-journal-text', 1),
(3, 'FI_CXP', 'Cuentas por Pagar', 'Gestión de pagos a proveedores', 'modules/fi/cuentas_pagar.php', 'bi-wallet2', 2),
(3, 'FI_CXC', 'Cuentas por Cobrar', 'Gestión de cobros a clientes', 'modules/fi/cuentas_cobrar.php', 'bi-cash-coin', 3),
(3, 'FI_TESORERIA', 'Tesorería', 'Gestión de caja y bancos', 'modules/fi/tesoreria.php', 'bi-bank', 4),
(3, 'FI_ACTIVOS', 'Activos Fijos', 'Gestión de activos fijos y depreciación', 'modules/fi/activos_fijos.php', 'bi-building-gear', 5),
(3, 'FI_COMPROBANTES', 'Comprobantes y Facturas', 'Emisión de comprobantes', 'modules/fi/comprobantes.php', 'bi-receipt', 6),
(3, 'FI_IFRS', 'IFRS', 'Estándares internacionales de contabilidad', 'modules/fi/ifrs.php', 'bi-globe', 7),
(3, 'FI_CONSOLIDACION', 'Consolidación', 'Consolidación de estados financieros', 'modules/fi/consolidacion.php', 'bi-diagram-3', 8),
(3, 'FI_REPORTING', 'Reporting Financiero', 'Informes y reportes financieros', 'modules/fi/reporting.php', 'bi-file-earmark-bar-graph', 9),
(3, 'FI_PRESUPUESTOS', 'Presupuestos', 'Gestión de presupuestos', 'modules/fi/presupuestos.php', 'bi-calculator', 10),
(3, 'FI_IMPUESTOS', 'Impuestos', 'Gestión de impuestos y declaraciones', 'modules/fi/impuestos.php', 'bi-percent', 11);

-- =============================================
-- SUBMÓDULOS - MÓDULO 4: CONTROLLING (8)
-- =============================================
INSERT INTO `submodules` (module_id, submodule_code, submodule_name, submodule_description, url, icon, sort_order) VALUES
(4, 'CO_CENTROS_COSTO', 'Centros de Costo', 'Gestión de centros de costo', 'modules/co/centros_costo.php', 'bi-diagram-2', 1),
(4, 'CO_RENTABILIDAD', 'Análisis de Rentabilidad', 'Análisis de rentabilidad por producto/servicio', 'modules/co/rentabilidad.php', 'bi-graph-up-arrow', 2),
(4, 'CO_ORDENES_INT', 'Contabilidad de Órdenes Internas', 'Gestión de órdenes internas', 'modules/co/ordenes_internas.php', 'bi-file-earmark-text', 3),
(4, 'CO_PROYECTOS', 'Contabilidad de Proyectos', 'Gestión contable de proyectos', 'modules/co/proyectos.php', 'bi-kanban', 4),
(4, 'CO_GASTOS', 'Control de Gastos Generales', 'Control y análisis de gastos', 'modules/co/gastos.php', 'bi-credit-card', 5),
(4, 'CO_COSTOS_PROD', 'Costos del Producto', 'Cálculo de costos de productos', 'modules/co/costos_producto.php', 'bi-tag', 6),
(4, 'CO_RESULTADOS', 'Análisis de Resultados', 'Análisis de resultados financieros', 'modules/co/resultados.php', 'bi-pie-chart', 7),
(4, 'CO_PLANIFICACION', 'Planificación y Presupuestos', 'Planificación financiera', 'modules/co/planificacion.php', 'bi-calendar-check', 8);

-- =============================================
-- SUBMÓDULOS - MÓDULO 5: VENTAS (9)
-- =============================================
INSERT INTO `submodules` (module_id, submodule_code, submodule_name, submodule_description, url, icon, sort_order) VALUES
(5, 'SD_PEDIDOS', 'Pedidos', 'Gestión de pedidos de venta', 'modules/sd/pedidos.php', 'bi-clipboard-check', 1),
(5, 'SD_FACTURACION', 'Facturación', 'Emisión de facturas de venta', 'modules/sd/facturacion.php', 'bi-receipt-cutoff', 2),
(5, 'SD_POS', 'Punto de Venta (POS)', 'Sistema de punto de venta', 'modules/sd/pos.php', 'bi-shop', 3),
(5, 'SD_CAJAS', 'Configuración de Cajas', 'Configuración de cajas registradoras', 'modules/sd/cajas.php', 'bi-cash-register', 4),
(5, 'SD_PRECIOS', 'Gestión de Precios', 'Listas de precios y descuentos', 'modules/sd/precios.php', 'bi-tags', 5),
(5, 'SD_ANALISIS', 'Análisis de Ventas', 'Reportes y análisis de ventas', 'modules/sd/analisis.php', 'bi-graph-up', 6),
(5, 'SD_DEVOLUCIONES', 'Devoluciones', 'Gestión de devoluciones', 'modules/sd/devoluciones.php', 'bi-arrow-return-left', 7),
(5, 'SD_CATALOGO', 'Catálogo de Productos', 'Catálogo de productos para clientes', 'modules/sd/catalogo.php', 'bi-grid-3x3', 8),
(5, 'SD_PROMOCIONES', 'Promociones', 'Gestión de promociones y ofertas', 'modules/sd/promociones.php', 'bi-gift', 9);

-- =============================================
-- SUBMÓDULOS - MÓDULO 6: MATERIALES (8)
-- =============================================
INSERT INTO `submodules` (module_id, submodule_code, submodule_name, submodule_description, url, icon, sort_order) VALUES
(6, 'MM_INVENTARIO', 'Inventario', 'Gestión de inventario y stock', 'modules/mm/inventario.php', 'bi-boxes', 1),
(6, 'MM_COMPRAS', 'Compras', 'Gestión de compras a proveedores', 'modules/mm/compras.php', 'bi-cart-plus', 2),
(6, 'MM_ALMACENES', 'Gestión de Almacenes', 'Administración de almacenes y bodegas', 'modules/mm/almacenes.php', 'bi-building', 3),
(6, 'MM_VERIFICACION', 'Verificación de Facturas', 'Verificación y validación de facturas', 'modules/mm/verificacion.php', 'bi-check-circle', 4),
(6, 'MM_PLANIFICACION', 'Planificación de Necesidades', 'MRP de materiales', 'modules/mm/planificacion.php', 'bi-calendar2-event', 5),
(6, 'MM_CALIDAD', 'Gestión de Calidad', 'Control de calidad de materiales', 'modules/mm/calidad.php', 'bi-award', 6),
(6, 'MM_VALORACION', 'Valoración de Inventario', 'Métodos de valoración de inventario', 'modules/mm/valoracion.php', 'bi-currency-dollar', 7),
(6, 'MM_ANALISIS', 'Análisis de Compras', 'Reportes y análisis de compras', 'modules/mm/analisis.php', 'bi-graph-down', 8);

-- =============================================
-- SUBMÓDULOS - MÓDULO 7: PRODUCCIÓN (10)
-- =============================================
INSERT INTO `submodules` (module_id, submodule_code, submodule_name, submodule_description, url, icon, sort_order) VALUES
(7, 'PP_ORDENES', 'Órdenes de Producción', 'Gestión de órdenes de producción', 'modules/pp/ordenes.php', 'bi-clipboard-data', 1),
(7, 'PP_MRP', 'MRP', 'Planificación de requerimientos de materiales', 'modules/pp/mrp.php', 'bi-diagram-3-fill', 2),
(7, 'PP_PLANIFICACION', 'Planificación de Producción', 'Planificación y programación', 'modules/pp/planificacion.php', 'bi-calendar3', 3),
(7, 'PP_CONTROL', 'Control de Planta', 'Monitoreo de planta productiva', 'modules/pp/control.php', 'bi-speedometer', 4),
(7, 'PP_CALIDAD', 'Gestión de Calidad', 'Control de calidad en producción', 'modules/pp/calidad.php', 'bi-patch-check', 5),
(7, 'PP_MANTENIMIENTO', 'Mantenimiento', 'Mantenimiento de maquinaria', 'modules/pp/mantenimiento.php', 'bi-tools', 6),
(7, 'PP_RECURSOS', 'Gestión de Recursos', 'Administración de recursos productivos', 'modules/pp/recursos.php', 'bi-cpu', 7),
(7, 'PP_OPTIMIZACION', 'Optimización de Procesos', 'Mejora continua de procesos', 'modules/pp/optimizacion.php', 'bi-lightning', 8),
(7, 'PP_COSTOS', 'Costos de Producción', 'Cálculo de costos de producción', 'modules/pp/costos.php', 'bi-coin', 9),
(7, 'PP_TRAZABILIDAD', 'Trazabilidad', 'Trazabilidad de lotes y productos', 'modules/pp/trazabilidad.php', 'bi-upc-scan', 10);

-- =============================================
-- SUBMÓDULOS - MÓDULO 8: RECURSOS HUMANOS (11)
-- =============================================
INSERT INTO `submodules` (module_id, submodule_code, submodule_name, submodule_description, url, icon, sort_order) VALUES
(8, 'HCM_PERSONAL', 'Personal', 'Gestión de personal y empleados', 'modules/hcm/personal.php', 'bi-people', 1),
(8, 'HCM_NOMINA', 'Nómina', 'Cálculo y procesamiento de nómina', 'modules/hcm/nomina.php', 'bi-cash-stack', 2),
(8, 'HCM_RECLUTAMIENTO', 'Reclutamiento', 'Proceso de reclutamiento y selección', 'modules/hcm/reclutamiento.php', 'bi-person-plus', 3),
(8, 'HCM_EVALUACION', 'Evaluación de Desempeño', 'Evaluación y desempeño de empleados', 'modules/hcm/evaluacion.php', 'bi-clipboard-check', 4),
(8, 'HCM_CAPACITACION', 'Capacitación', 'Gestión de capacitación y desarrollo', 'modules/hcm/capacitacion.php', 'bi-mortarboard', 5),
(8, 'HCM_TALENTO', 'Gestión de Talento', 'Desarrollo y retención de talento', 'modules/hcm/talento.php', 'bi-star', 6),
(8, 'HCM_BENEFICIOS', 'Beneficios y Compensaciones', 'Administración de beneficios', 'modules/hcm/beneficios.php', 'bi-gift', 7),
(8, 'HCM_ASISTENCIA', 'Control de Asistencia', 'Marcación y control de asistencia con relojes control', 'modules/hcm/asistencia.php', 'bi-clock-history', 8),
(8, 'HCM_VACACIONES', 'Vacaciones y Licencias', 'Gestión de vacaciones y licencias', 'modules/hcm/vacaciones.php', 'bi-calendar-event', 9),
(8, 'HCM_SALUD', 'Salud Ocupacional', 'Gestión de salud y seguridad laboral', 'modules/hcm/salud.php', 'bi-heart-pulse', 10),
(8, 'HCM_ANALISIS', 'Análisis de Personal', 'Reportes y análisis de RRHH', 'modules/hcm/analisis.php', 'bi-bar-chart-line', 11);

-- =============================================
-- SUBMÓDULOS - MÓDULO 9: SCM (10)
-- =============================================
INSERT INTO `submodules` (module_id, submodule_code, submodule_name, submodule_description, url, icon, sort_order) VALUES
(9, 'SCM_LOGISTICA', 'Logística', 'Gestión logística integral', 'modules/scm/logistica.php', 'bi-box-arrow-right', 1),
(9, 'SCM_TRANSPORTE', 'Transporte', 'Gestión de transporte', 'modules/scm/transporte.php', 'bi-truck', 2),
(9, 'SCM_RUTAS', 'Gestión de Rutas', 'Planificación de rutas de distribución', 'modules/scm/rutas.php', 'bi-map', 3),
(9, 'SCM_ENTREGAS', 'Planificación de Entregas', 'Programación de entregas', 'modules/scm/entregas.php', 'bi-calendar-check', 4),
(9, 'SCM_FLOTILLA', 'Control de Flotilla', 'Gestión de vehículos', 'modules/scm/flotilla.php', 'bi-minecart-loaded', 5),
(9, 'SCM_OPTIMIZACION', 'Optimización de Rutas', 'Optimización de rutas de transporte', 'modules/scm/optimizacion.php', 'bi-bezier2', 6),
(9, 'SCM_ALMACENES', 'Gestión de Almacenes SCM', 'Almacenes de la cadena de suministro', 'modules/scm/almacenes.php', 'bi-building', 7),
(9, 'SCM_TRAZABILIDAD', 'Trazabilidad de Envíos', 'Seguimiento de envíos', 'modules/scm/trazabilidad.php', 'bi-geo-alt', 8),
(9, 'SCM_ANALISIS', 'Análisis de Cadena de Suministro', 'KPIs y análisis de SCM', 'modules/scm/analisis.php', 'bi-graph-up-arrow', 9),
(9, 'SCM_PROVEEDORES', 'Gestión de Proveedores SCM', 'Relación con proveedores logísticos', 'modules/scm/proveedores.php', 'bi-person-lines-fill', 10);

-- =============================================
-- SUBMÓDULOS - MÓDULO 10: CRM (8)
-- =============================================
INSERT INTO `submodules` (module_id, submodule_code, submodule_name, submodule_description, url, icon, sort_order) VALUES
(10, 'CRM_CLIENTES', 'Gestión de Clientes', 'Base de datos de clientes', 'modules/crm/clientes.php', 'bi-person-circle', 1),
(10, 'CRM_OPORTUNIDADES', 'Oportunidades', 'Gestión de oportunidades de venta', 'modules/crm/oportunidades.php', 'bi-bullseye', 2),
(10, 'CRM_LEADS', 'Leads', 'Gestión de prospectos y leads', 'modules/crm/leads.php', 'bi-person-plus-fill', 3),
(10, 'CRM_PIPELINE', 'Pipeline de Ventas', 'Embudo de ventas y conversión', 'modules/crm/pipeline.php', 'bi-funnel', 4),
(10, 'CRM_CONTACTOS', 'Contactos', 'Gestión de contactos', 'modules/crm/contactos.php', 'bi-person-rolodex', 5),
(10, 'CRM_CAMPAÑAS', 'Campañas', 'Campañas de marketing', 'modules/crm/campanas.php', 'bi-megaphone', 6),
(10, 'CRM_CUENTAS', 'Cuentas', 'Gestión de cuentas corporativas', 'modules/crm/cuentas.php', 'bi-building', 7),
(10, 'CRM_COTIZACIONES', 'Cotizaciones', 'Gestión de cotizaciones', 'modules/crm/cotizaciones.php', 'bi-file-earmark-text', 8);

-- =============================================
-- SUBMÓDULOS - MÓDULO 11: FIDELIZACIÓN (7)
-- =============================================
INSERT INTO `submodules` (module_id, submodule_code, submodule_name, submodule_description, url, icon, sort_order) VALUES
(11, 'FID_PROGRAMAS', 'Programas de Lealtad', 'Programas de fidelización', 'modules/fid/programas.php', 'bi-award', 1),
(11, 'FID_PUNTOS', 'Sistema de Puntos', 'Acumulación y canje de puntos', 'modules/fid/puntos.php', 'bi-star-fill', 2),
(11, 'FID_RECOMPENSAS', 'Gestión de Recompensas', 'Catálogo de recompensas', 'modules/fid/recompensas.php', 'bi-gift-fill', 3),
(11, 'FID_CAMPAÑAS', 'Campañas Personalizadas', 'Campañas de marketing personalizado', 'modules/fid/campanas.php', 'bi-envelope-heart', 4),
(11, 'FID_ANALISIS', 'Análisis de Fidelización', 'Métricas de fidelización', 'modules/fid/analisis.php', 'bi-graph-up', 5),
(11, 'FID_COMENTARIOS', 'Comentarios de Clientes', 'Feedback y valoraciones', 'modules/fid/comentarios.php', 'bi-chat-square-text', 6),
(11, 'FID_SEGMENTACION', 'Segmentación de Clientes', 'Segmentación y targeting', 'modules/fid/segmentacion.php', 'bi-pie-chart-fill', 7);

-- =============================================
-- SUBMÓDULOS - MÓDULO 12: BUSINESS INTELLIGENCE (15)
-- =============================================
INSERT INTO `submodules` (module_id, submodule_code, submodule_name, submodule_description, url, icon, sort_order) VALUES
(12, 'BI_DASHBOARDS', 'Dashboards', 'Dashboards ejecutivos', 'modules/bi/dashboards.php', 'bi-grid-1x2', 1),
(12, 'BI_KPIS', 'KPIs', 'Indicadores clave de desempeño', 'modules/bi/kpis.php', 'bi-speedometer2', 2),
(12, 'BI_INFORMES', 'Informes Ejecutivos', 'Informes para dirección', 'modules/bi/informes.php', 'bi-file-earmark-bar-graph', 3),
(12, 'BI_PREDICTIVO', 'Análisis Predictivo', 'Análisis predictivo y forecasting', 'modules/bi/predictivo.php', 'bi-crystal-ball', 4),
(12, 'BI_REP_FI', 'Reportes Financieros', 'Reportes del módulo financiero', 'modules/bi/rep_financieros.php', 'bi-cash', 5),
(12, 'BI_REP_SD', 'Reportes de Ventas', 'Reportes del módulo de ventas', 'modules/bi/rep_ventas.php', 'bi-cart', 6),
(12, 'BI_REP_MM', 'Reportes de Inventario', 'Reportes de materiales e inventario', 'modules/bi/rep_inventario.php', 'bi-box', 7),
(12, 'BI_REP_PP', 'Reportes de Producción', 'Reportes del módulo de producción', 'modules/bi/rep_produccion.php', 'bi-gear', 8),
(12, 'BI_REP_HCM', 'Reportes de RRHH', 'Reportes de recursos humanos', 'modules/bi/rep_rrhh.php', 'bi-people', 9),
(12, 'BI_REP_COMPRAS', 'Reportes de Compras', 'Reportes de compras', 'modules/bi/rep_compras.php', 'bi-cart-plus', 10),
(12, 'BI_REP_CALIDAD', 'Reportes de Calidad', 'Reportes de calidad', 'modules/bi/rep_calidad.php', 'bi-award', 11),
(12, 'BI_REP_MANT', 'Reportes de Mantenimiento', 'Reportes de mantenimiento', 'modules/bi/rep_mantenimiento.php', 'bi-tools', 12),
(12, 'BI_REP_LOG', 'Reportes de Logística', 'Reportes logísticos', 'modules/bi/rep_logistica.php', 'bi-truck', 13),
(12, 'BI_REP_PROY', 'Reportes de Proyectos', 'Reportes de proyectos', 'modules/bi/rep_proyectos.php', 'bi-kanban', 14),
(12, 'BI_REP_CUSTOM', 'Reportes Personalizados', 'Constructor de reportes', 'modules/bi/rep_personalizados.php', 'bi-sliders', 15);

-- =============================================
-- SUBMÓDULOS - MÓDULO 13: CONFIGURACIÓN (1)
-- =============================================
INSERT INTO `submodules` (module_id, submodule_code, submodule_name, submodule_description, url, icon, sort_order) VALUES
(13, 'CONFIG_GENERAL', 'Configuración General', 'Configuración del sistema', 'modules/config/general.php', 'bi-gear-fill', 1);

-- =============================================
-- SUBMÓDULOS - MÓDULO 14: DASHBOARD (1)
-- =============================================
INSERT INTO `submodules` (module_id, submodule_code, submodule_name, submodule_description, url, icon, sort_order) VALUES
(14, 'DASHBOARD_HOME', 'Dashboard Principal', 'Panel principal de navegación', 'user/dashboard.php', 'bi-house-door', 1);

-- Tabla: user_permissions (Permisos de usuarios por submódulo)
DROP TABLE IF EXISTS `user_permissions`;
CREATE TABLE `user_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `submodule_id` int(11) NOT NULL,
  `can_view` tinyint(1) DEFAULT 1,
  `can_create` tinyint(1) DEFAULT 0,
  `can_edit` tinyint(1) DEFAULT 0,
  `can_delete` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_submodule_unique` (`user_id`, `submodule_id`),
  KEY `user_id` (`user_id`),
  KEY `submodule_id` (`submodule_id`),
  CONSTRAINT `fk_perm_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_perm_submodule` FOREIGN KEY (`submodule_id`) REFERENCES `submodules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: payments (Pagos de usuarios)
DROP TABLE IF EXISTS `payments`;
CREATE TABLE `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `plan_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) DEFAULT 'USD',
  `payment_method` varchar(50) DEFAULT NULL,
  `transaction_id` varchar(255) DEFAULT NULL,
  `status` enum('pending','completed','failed','refunded') DEFAULT 'pending',
  `billing_period` enum('monthly','yearly') DEFAULT 'monthly',
  `payment_date` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `plan_id` (`plan_id`),
  CONSTRAINT `fk_payment_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_payment_plan` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: activity_logs (Logs de actividad)
DROP TABLE IF EXISTS `activity_logs`;
CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `description` text,
  `module` varchar(50) DEFAULT NULL,
  `ip_address` varchar(50) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_log_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: exchange_rates (Tipos de cambio - UF, USD, UTM, etc.)
DROP TABLE IF EXISTS `exchange_rates`;
CREATE TABLE `exchange_rates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rate_code` varchar(10) NOT NULL COMMENT 'UF, USD, EUR, UTM, etc',
  `rate_name` varchar(50) NOT NULL,
  `rate_value` decimal(15,4) NOT NULL,
  `currency` varchar(3) DEFAULT 'CLP',
  `rate_date` date NOT NULL,
  `source` varchar(100) DEFAULT NULL COMMENT 'API o fuente de datos',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `rate_code_date` (`rate_code`, `rate_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: time_clocks (Relojes control de asistencia para RRHH)
DROP TABLE IF EXISTS `time_clocks`;
CREATE TABLE `time_clocks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `clock_name` varchar(100) NOT NULL,
  `clock_type` varchar(50) NOT NULL COMMENT 'Marca del reloj: ZKTeco, Suprema, Anviz, etc',
  `ip_address` varchar(50) DEFAULT NULL,
  `port` int(11) DEFAULT 4370,
  `serial_number` varchar(100) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `api_endpoint` varchar(255) DEFAULT NULL,
  `api_key` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `last_sync_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`),
  CONSTRAINT `fk_clock_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: attendance_records (Registros de asistencia desde relojes control)
DROP TABLE IF EXISTS `attendance_records`;
CREATE TABLE `attendance_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `time_clock_id` int(11) DEFAULT NULL,
  `check_in` datetime DEFAULT NULL,
  `check_out` datetime DEFAULT NULL,
  `work_date` date NOT NULL,
  `total_hours` decimal(5,2) DEFAULT 0.00,
  `overtime_hours` decimal(5,2) DEFAULT 0.00,
  `status` enum('present','absent','late','half_day') DEFAULT 'present',
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`),
  KEY `time_clock_id` (`time_clock_id`),
  KEY `work_date` (`work_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: sii_integration (Integración con SII Chile)
DROP TABLE IF EXISTS `sii_integration`;
CREATE TABLE `sii_integration` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `rut_empresa` varchar(20) NOT NULL,
  `certificado_digital` text COMMENT 'Certificado digital en formato PEM',
  `password_certificado` varchar(255) DEFAULT NULL,
  `ambiente` enum('certificacion','produccion') DEFAULT 'certificacion',
  `ultima_sincronizacion` datetime DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`),
  CONSTRAINT `fk_sii_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: previred_integration (Integración con Previred Chile)
DROP TABLE IF EXISTS `previred_integration`;
CREATE TABLE `previred_integration` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `rut_empresa` varchar(20) NOT NULL,
  `usuario_previred` varchar(100) DEFAULT NULL,
  `clave_previred` varchar(255) DEFAULT NULL,
  `ultima_sincronizacion` datetime DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`),
  CONSTRAINT `fk_previred_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: system_settings (Configuración general del sistema)
DROP TABLE IF EXISTS `system_settings`;
CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text,
  `setting_type` varchar(50) DEFAULT 'string',
  `category` varchar(50) DEFAULT 'general',
  `description` text,
  `is_public` tinyint(1) DEFAULT 0,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Configuraciones iniciales
INSERT INTO `system_settings` (setting_key, setting_value, setting_type, category, description) VALUES
('app_name', 'CONECTA ERP', 'string', 'general', 'Nombre de la aplicación'),
('app_version', '1.0.0', 'string', 'general', 'Versión del sistema'),
('trial_days', '14', 'integer', 'billing', 'Días de prueba gratuita'),
('trial_warning_days', '3', 'integer', 'billing', 'Días antes de enviar aviso de fin de trial'),
('default_language', 'es', 'string', 'localization', 'Idioma por defecto'),
('default_currency', 'CLP', 'string', 'localization', 'Moneda por defecto'),
('default_country', 'CL', 'string', 'localization', 'País por defecto'),
('email_from', 'auditorexchile@gmail.com', 'string', 'email', 'Email remitente del sistema'),
('email_from_name', 'Auditorex Chile', 'string', 'email', 'Nombre del remitente'),
('auto_update_exchange_rates', '1', 'boolean', 'integration', 'Actualizar automáticamente tasas de cambio'),
('sii_integration_enabled', '1', 'boolean', 'integration', 'Habilitar integración con SII'),
('previred_integration_enabled', '1', 'boolean', 'integration', 'Habilitar integración con Previred');

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================
-- FIN DE LA INSTALACIÓN
-- =============================================
