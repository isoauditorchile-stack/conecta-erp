-- =====================================================
-- CONECTA ERP - INSTALACIÓN DE SUBMÓDULOS
-- Inserción de los 106 Submódulos del Sistema
-- =====================================================

-- MÓDULO 1: FINANZAS (FI) - 11 Submódulos
INSERT INTO `submodules` (`module_id`, `code`, `name`, `description`, `url`, `icon`, `sort_order`) VALUES
((SELECT id FROM modules WHERE code = 'FI'), 'FI-GL', 'Contabilidad General', 'Gestión de plan de cuentas y mayor general', '/modules/fi/general_ledger.php', 'fa-book', 1),
((SELECT id FROM modules WHERE code = 'FI'), 'FI-AR', 'Cuentas por Cobrar', 'Administración de facturas y cobros a clientes', '/modules/fi/accounts_receivable.php', 'fa-hand-holding-usd', 2),
((SELECT id FROM modules WHERE code = 'FI'), 'FI-AP', 'Cuentas por Pagar', 'Gestión de facturas y pagos a proveedores', '/modules/fi/accounts_payable.php', 'fa-file-invoice-dollar', 3),
((SELECT id FROM modules WHERE code = 'FI'), 'FI-TR', 'Tesorería', 'Control de caja, bancos y flujo de efectivo', '/modules/fi/treasury.php', 'fa-university', 4),
((SELECT id FROM modules WHERE code = 'FI'), 'FI-FA', 'Activos Fijos', 'Gestión y depreciación de activos fijos', '/modules/fi/fixed_assets.php', 'fa-building', 5),
((SELECT id FROM modules WHERE code = 'FI'), 'FI-BK', 'Bancos y Conciliaciones', 'Conciliación bancaria automática', '/modules/fi/banks.php', 'fa-landmark', 6),
((SELECT id FROM modules WHERE code = 'FI'), 'FI-AB', 'Libros Contables', 'Libros diario, mayor, balance y auxiliares', '/modules/fi/accounting_books.php', 'fa-book-open', 7),
((SELECT id FROM modules WHERE code = 'FI'), 'FI-IF', 'IFRS Reporting', 'Reportes bajo normas IFRS/NIIF', '/modules/fi/ifrs.php', 'fa-chart-bar', 8),
((SELECT id FROM modules WHERE code = 'FI'), 'FI-JE', 'Asientos Contables', 'Registro y gestión de asientos contables', '/modules/fi/journal_entries.php', 'fa-pen-alt', 9),
((SELECT id FROM modules WHERE code = 'FI'), 'FI-CP', 'Cierres Contables', 'Cierre mensual, trimestral y anual', '/modules/fi/closing.php', 'fa-lock', 10),
((SELECT id FROM modules WHERE code = 'FI'), 'FI-FR', 'Reportes Financieros', 'Estados financieros y reportes ejecutivos', '/modules/fi/reports.php', 'fa-file-pdf', 11);

-- MÓDULO 2: CONTROLLING (CO) - 8 Submódulos
INSERT INTO `submodules` (`module_id`, `code`, `name`, `description`, `url`, `icon`, `sort_order`) VALUES
((SELECT id FROM modules WHERE code = 'CO'), 'CO-CC', 'Centros de Costo', 'Gestión de centros de costo y responsabilidad', '/modules/co/cost_centers.php', 'fa-sitemap', 1),
((SELECT id FROM modules WHERE code = 'CO'), 'CO-IO', 'Órdenes Internas', 'Control de órdenes internas de trabajo', '/modules/co/internal_orders.php', 'fa-clipboard-list', 2),
((SELECT id FROM modules WHERE code = 'CO'), 'CO-PA', 'Análisis de Rentabilidad', 'Análisis de márgenes y rentabilidad', '/modules/co/profitability.php', 'fa-chart-line', 3),
((SELECT id FROM modules WHERE code = 'CO'), 'CO-BU', 'Presupuestos', 'Planificación y control presupuestario', '/modules/co/budgets.php', 'fa-calculator', 4),
((SELECT id FROM modules WHERE code = 'CO'), 'CO-EX', 'Control de Gastos', 'Gestión y aprobación de gastos', '/modules/co/expenses.php', 'fa-receipt', 5),
((SELECT id FROM modules WHERE code = 'CO'), 'CO-ABC', 'Costos ABC', 'Costeo basado en actividades', '/modules/co/abc_costing.php', 'fa-project-diagram', 6),
((SELECT id FROM modules WHERE code = 'CO'), 'CO-PR', 'Análisis de Proyectos', 'Análisis financiero de proyectos', '/modules/co/projects.php', 'fa-tasks', 7),
((SELECT id FROM modules WHERE code = 'CO'), 'CO-IN', 'Control de Inversiones', 'Gestión y ROI de inversiones', '/modules/co/investments.php', 'fa-money-bill-wave', 8);

-- MÓDULO 3: VENTAS & DISTRIBUCIÓN (SD) - 9 Submódulos
INSERT INTO `submodules` (`module_id`, `code`, `name`, `description`, `url`, `icon`, `sort_order`) VALUES
((SELECT id FROM modules WHERE code = 'SD'), 'SD-CU', 'Clientes', 'Maestro de clientes y contactos', '/modules/sd/customers.php', 'fa-users', 1),
((SELECT id FROM modules WHERE code = 'SD'), 'SD-QT', 'Cotizaciones', 'Gestión de cotizaciones y presupuestos', '/modules/sd/quotations.php', 'fa-file-alt', 2),
((SELECT id FROM modules WHERE code = 'SD'), 'SD-SO', 'Pedidos de Venta', 'Órdenes y pedidos de clientes', '/modules/sd/sales_orders.php', 'fa-shopping-bag', 3),
((SELECT id FROM modules WHERE code = 'SD'), 'SD-INV', 'Facturación', 'Emisión y gestión de facturas', '/modules/sd/invoices.php', 'fa-file-invoice', 4),
((SELECT id FROM modules WHERE code = 'SD'), 'SD-POS', 'Punto de Venta', 'Terminal POS para ventas directas', '/modules/sd/pos.php', 'fa-cash-register', 5),
((SELECT id FROM modules WHERE code = 'SD'), 'SD-EC', 'E-commerce', 'Gestión de ventas online', '/modules/sd/ecommerce.php', 'fa-store', 6),
((SELECT id FROM modules WHERE code = 'SD'), 'SD-PRC', 'Precios Dinámicos', 'Reglas de precios y descuentos', '/modules/sd/pricing.php', 'fa-tags', 7),
((SELECT id FROM modules WHERE code = 'SD'), 'SD-COM', 'Comisiones', 'Cálculo de comisiones de ventas', '/modules/sd/commissions.php', 'fa-percentage', 8),
((SELECT id FROM modules WHERE code = 'SD'), 'SD-AN', 'Análisis de Ventas', 'Reportes y análisis de ventas', '/modules/sd/analytics.php', 'fa-chart-area', 9);

-- MÓDULO 4: MATERIALES (MM) - 8 Submódulos
INSERT INTO `submodules` (`module_id`, `code`, `name`, `description`, `url`, `icon`, `sort_order`) VALUES
((SELECT id FROM modules WHERE code = 'MM'), 'MM-PRD', 'Productos', 'Maestro de productos y materiales', '/modules/mm/products.php', 'fa-box', 1),
((SELECT id FROM modules WHERE code = 'MM'), 'MM-WH', 'Almacenes', 'Gestión de almacenes multiubicación', '/modules/mm/warehouses.php', 'fa-warehouse', 2),
((SELECT id FROM modules WHERE code = 'MM'), 'MM-INV', 'Inventario', 'Control de stock en tiempo real', '/modules/mm/inventory.php', 'fa-cubes', 3),
((SELECT id FROM modules WHERE code = 'MM'), 'MM-SUP', 'Proveedores', 'Maestro de proveedores', '/modules/mm/suppliers.php', 'fa-truck-loading', 4),
((SELECT id FROM modules WHERE code = 'MM'), 'MM-PO', 'Órdenes de Compra', 'Gestión de compras y licitaciones', '/modules/mm/purchase_orders.php', 'fa-shopping-cart', 5),
((SELECT id FROM modules WHERE code = 'MM'), 'MM-GR', 'Recepción', 'Recepción de mercancías', '/modules/mm/goods_receipt.php', 'fa-dolly', 6),
((SELECT id FROM modules WHERE code = 'MM'), 'MM-MRP', 'MRP', 'Planificación de necesidades de materiales', '/modules/mm/mrp.php', 'fa-cogs', 7),
((SELECT id FROM modules WHERE code = 'MM'), 'MM-TR', 'Trazabilidad', 'Trazabilidad de lotes y series', '/modules/mm/traceability.php', 'fa-barcode', 8);

-- MÓDULO 5: PRODUCCIÓN (PP) - 10 Submódulos
INSERT INTO `submodules` (`module_id`, `code`, `name`, `description`, `url`, `icon`, `sort_order`) VALUES
((SELECT id FROM modules WHERE code = 'PP'), 'PP-PO', 'Órdenes de Producción', 'Órdenes de fabricación y manufactura', '/modules/pp/production_orders.php', 'fa-industry', 1),
((SELECT id FROM modules WHERE code = 'PP'), 'PP-BOM', 'BOM', 'Lista de materiales y estructuras', '/modules/pp/bom.php', 'fa-list-alt', 2),
((SELECT id FROM modules WHERE code = 'PP'), 'PP-RT', 'Rutinas', 'Rutinas y secuencias de producción', '/modules/pp/routing.php', 'fa-route', 3),
((SELECT id FROM modules WHERE code = 'PP'), 'PP-MRP2', 'MRP II', 'Planificación avanzada de recursos', '/modules/pp/mrp_ii.php', 'fa-project-diagram', 4),
((SELECT id FROM modules WHERE code = 'PP'), 'PP-CAP', 'Capacidades', 'Planificación de capacidad productiva', '/modules/pp/capacity.php', 'fa-chart-pie', 5),
((SELECT id FROM modules WHERE code = 'PP'), 'PP-QC', 'Control de Calidad', 'Inspección y control de calidad', '/modules/pp/quality.php', 'fa-check-double', 6),
((SELECT id FROM modules WHERE code = 'PP'), 'PP-MT', 'Mantenimiento', 'Mantenimiento preventivo y correctivo', '/modules/pp/maintenance.php', 'fa-tools', 7),
((SELECT id FROM modules WHERE code = 'PP'), 'PP-FM', 'Fórmulas', 'Fórmulas y recetas de producción', '/modules/pp/formulas.php', 'fa-flask', 8),
((SELECT id FROM modules WHERE code = 'PP'), 'PP-CS', 'Costos', 'Costos de producción y varianzas', '/modules/pp/costs.php', 'fa-dollar-sign', 9),
((SELECT id FROM modules WHERE code = 'PP'), 'PP-WC', 'Centros de Trabajo', 'Gestión de centros de trabajo', '/modules/pp/work_centers.php', 'fa-industry', 10);

-- MÓDULO 6: CAPITAL HUMANO (HCM) - 11 Submódulos
INSERT INTO `submodules` (`module_id`, `code`, `name`, `description`, `url`, `icon`, `sort_order`) VALUES
((SELECT id FROM modules WHERE code = 'HCM'), 'HCM-EMP', 'Empleados', 'Maestro de empleados y personal', '/modules/hcm/employees.php', 'fa-id-card', 1),
((SELECT id FROM modules WHERE code = 'HCM'), 'HCM-PAY', 'Nómina', 'Procesamiento de nómina y liquidaciones', '/modules/hcm/payroll.php', 'fa-money-check-alt', 2),
((SELECT id FROM modules WHERE code = 'HCM'), 'HCM-REC', 'Reclutamiento', 'Gestión de vacantes y selección', '/modules/hcm/recruitment.php', 'fa-user-plus', 3),
((SELECT id FROM modules WHERE code = 'HCM'), 'HCM-ONB', 'Onboarding', 'Incorporación de nuevos empleados', '/modules/hcm/onboarding.php', 'fa-handshake', 4),
((SELECT id FROM modules WHERE code = 'HCM'), 'HCM-PER', 'Evaluación', 'Evaluación de desempeño', '/modules/hcm/performance.php', 'fa-star', 5),
((SELECT id FROM modules WHERE code = 'HCM'), 'HCM-TRN', 'Capacitación', 'Programas de formación y desarrollo', '/modules/hcm/training.php', 'fa-graduation-cap', 6),
((SELECT id FROM modules WHERE code = 'HCM'), 'HCM-ORG', 'Desarrollo Org.', 'Desarrollo organizacional', '/modules/hcm/org_development.php', 'fa-sitemap', 7),
((SELECT id FROM modules WHERE code = 'HCM'), 'HCM-ATT', 'Asistencia', 'Control de asistencia y horarios', '/modules/hcm/attendance.php', 'fa-clock', 8),
((SELECT id FROM modules WHERE code = 'HCM'), 'HCM-BEN', 'Beneficios', 'Gestión de beneficios y compensaciones', '/modules/hcm/benefits.php', 'fa-gift', 9),
((SELECT id FROM modules WHERE code = 'HCM'), 'HCM-LEV', 'Vacaciones', 'Gestión de vacaciones y permisos', '/modules/hcm/leaves.php', 'fa-plane', 10),
((SELECT id FROM modules WHERE code = 'HCM'), 'HCM-RPT', 'Reportes RRHH', 'Reportes de recursos humanos', '/modules/hcm/reports.php', 'fa-file-excel', 11);

-- MÓDULO 7: SUPPLY CHAIN (SCM) - 10 Submódulos
INSERT INTO `submodules` (`module_id`, `code`, `name`, `description`, `url`, `icon`, `sort_order`) VALUES
((SELECT id FROM modules WHERE code = 'SCM'), 'SCM-LOG', 'Logística', 'Gestión logística integral', '/modules/scm/logistics.php', 'fa-shipping-fast', 1),
((SELECT id FROM modules WHERE code = 'SCM'), 'SCM-TRN', 'Transporte', 'Gestión de transporte y flota', '/modules/scm/transport.php', 'fa-truck', 2),
((SELECT id FROM modules WHERE code = 'SCM'), 'SCM-RT', 'Rutas', 'Optimización de rutas de entrega', '/modules/scm/routes.php', 'fa-route', 3),
((SELECT id FROM modules WHERE code = 'SCM'), 'SCM-SHP', 'Despachos', 'Gestión de despachos y envíos', '/modules/scm/shipments.php', 'fa-box-open', 4),
((SELECT id FROM modules WHERE code = 'SCM'), 'SCM-GPS', 'Seguimiento GPS', 'Tracking en tiempo real', '/modules/scm/tracking.php', 'fa-map-marked-alt', 5),
((SELECT id FROM modules WHERE code = 'SCM'), 'SCM-WMS', 'WMS', 'Sistema de gestión de almacenes', '/modules/scm/wms.php', 'fa-warehouse', 6),
((SELECT id FROM modules WHERE code = 'SCM'), 'SCM-3PL', 'Proveedores 3PL', 'Gestión de operadores logísticos', '/modules/scm/3pl.php', 'fa-handshake', 7),
((SELECT id FROM modules WHERE code = 'SCM'), 'SCM-FRT', 'Fletes', 'Gestión de fletes y tarifas', '/modules/scm/freight.php', 'fa-dollar-sign', 8),
((SELECT id FROM modules WHERE code = 'SCM'), 'SCM-DEM', 'Planificación Demanda', 'Forecast y planificación de demanda', '/modules/scm/demand.php', 'fa-chart-line', 9),
((SELECT id FROM modules WHERE code = 'SCM'), 'SCM-RPT', 'Reportes SCM', 'Reportes de cadena de suministro', '/modules/scm/reports.php', 'fa-chart-bar', 10);

-- MÓDULO 8: CRM - 9 Submódulos
INSERT INTO `submodules` (`module_id`, `code`, `name`, `description`, `url`, `icon`, `sort_order`) VALUES
((SELECT id FROM modules WHERE code = 'CRM'), 'CRM-CNT', 'Contactos', 'Base de datos de contactos 360°', '/modules/crm/contacts.php', 'fa-address-book', 1),
((SELECT id FROM modules WHERE code = 'CRM'), 'CRM-LEA', 'Leads', 'Gestión de prospectos y leads', '/modules/crm/leads.php', 'fa-user-plus', 2),
((SELECT id FROM modules WHERE code = 'CRM'), 'CRM-OPP', 'Oportunidades', 'Pipeline de oportunidades de venta', '/modules/crm/opportunities.php', 'fa-trophy', 3),
((SELECT id FROM modules WHERE code = 'CRM'), 'CRM-ACT', 'Actividades', 'Seguimiento de actividades y tareas', '/modules/crm/activities.php', 'fa-tasks', 4),
((SELECT id FROM modules WHERE code = 'CRM'), 'CRM-CAM', 'Campañas', 'Marketing automation y campañas', '/modules/crm/campaigns.php', 'fa-bullhorn', 5),
((SELECT id FROM modules WHERE code = 'CRM'), 'CRM-TIC', 'Tickets', 'Sistema de tickets de soporte', '/modules/crm/tickets.php', 'fa-ticket-alt', 6),
((SELECT id FROM modules WHERE code = 'CRM'), 'CRM-EML', 'Email Marketing', 'Campañas de email marketing', '/modules/crm/email_marketing.php', 'fa-envelope', 7),
((SELECT id FROM modules WHERE code = 'CRM'), 'CRM-AN', 'Análisis Predictivo', 'Análisis predictivo de ventas', '/modules/crm/analytics.php', 'fa-brain', 8),
((SELECT id FROM modules WHERE code = 'CRM'), 'CRM-SAT', 'Satisfacción', 'Encuestas de satisfacción NPS', '/modules/crm/satisfaction.php', 'fa-smile', 9);

-- MÓDULO 9: FIDELIZACIÓN (LOY) - 7 Submódulos
INSERT INTO `submodules` (`module_id`, `code`, `name`, `description`, `url`, `icon`, `sort_order`) VALUES
((SELECT id FROM modules WHERE code = 'LOY'), 'LOY-PRG', 'Programas', 'Programas de lealtad y fidelización', '/modules/loy/programs.php', 'fa-award', 1),
((SELECT id FROM modules WHERE code = 'LOY'), 'LOY-PTS', 'Puntos', 'Gestión de puntos y acumulación', '/modules/loy/points.php', 'fa-coins', 2),
((SELECT id FROM modules WHERE code = 'LOY'), 'LOY-REW', 'Recompensas', 'Catálogo de recompensas y premios', '/modules/loy/rewards.php', 'fa-gift', 3),
((SELECT id FROM modules WHERE code = 'LOY'), 'LOY-MEM', 'Membresías', 'Niveles y membresías premium', '/modules/loy/memberships.php', 'fa-crown', 4),
((SELECT id FROM modules WHERE code = 'LOY'), 'LOY-GAM', 'Gamificación', 'Sistema de gamificación', '/modules/loy/gamification.php', 'fa-gamepad', 5),
((SELECT id FROM modules WHERE code = 'LOY'), 'LOY-CPN', 'Cupones', 'Cupones y descuentos digitales', '/modules/loy/coupons.php', 'fa-ticket-alt', 6),
((SELECT id FROM modules WHERE code = 'LOY'), 'LOY-AN', 'Análisis', 'Análisis de comportamiento de clientes', '/modules/loy/analytics.php', 'fa-chart-line', 7);

-- MÓDULO 10: BUSINESS INTELLIGENCE (BI) - 8 Submódulos
INSERT INTO `submodules` (`module_id`, `code`, `name`, `description`, `url`, `icon`, `sort_order`) VALUES
((SELECT id FROM modules WHERE code = 'BI'), 'BI-DSH', 'Dashboards', 'Dashboards interactivos ejecutivos', '/modules/bi/dashboards.php', 'fa-tachometer-alt', 1),
((SELECT id FROM modules WHERE code = 'BI'), 'BI-KPI', 'KPIs', 'Indicadores clave de rendimiento', '/modules/bi/kpis.php', 'fa-bullseye', 2),
((SELECT id FROM modules WHERE code = 'BI'), 'BI-RPT', 'Reportes', 'Generador de reportes avanzados', '/modules/bi/reports.php', 'fa-file-alt', 3),
((SELECT id FROM modules WHERE code = 'BI'), 'BI-PRE', 'Análisis Predictivo', 'Machine learning y predicciones', '/modules/bi/predictive.php', 'fa-chart-line', 4),
((SELECT id FROM modules WHERE code = 'BI'), 'BI-DM', 'Data Mining', 'Minería de datos y patrones', '/modules/bi/data_mining.php', 'fa-database', 5),
((SELECT id FROM modules WHERE code = 'BI'), 'BI-VIS', 'Visualización', 'Gráficos y visualización de datos', '/modules/bi/visualization.php', 'fa-chart-pie', 6),
((SELECT id FROM modules WHERE code = 'BI'), 'BI-CMD', 'Cuadro de Mando', 'Balanced Scorecard (BSC)', '/modules/bi/scorecard.php', 'fa-clipboard-list', 7),
((SELECT id FROM modules WHERE code = 'BI'), 'BI-ETL', 'ETL', 'Extracción, transformación y carga', '/modules/bi/etl.php', 'fa-exchange-alt', 8);

-- MÓDULO 11: FACTURACIÓN ELECTRÓNICA (FE) - 6 Submódulos
INSERT INTO `submodules` (`module_id`, `code`, `name`, `description`, `url`, `icon`, `sort_order`) VALUES
((SELECT id FROM modules WHERE code = 'FE'), 'FE-DTE', 'DTE/SII', 'Documentos tributarios electrónicos', '/modules/fe/dte.php', 'fa-file-invoice', 1),
((SELECT id FROM modules WHERE code = 'FE'), 'FE-NCR', 'Notas de Crédito', 'Emisión de notas de crédito', '/modules/fe/credit_notes.php', 'fa-file-minus', 2),
((SELECT id FROM modules WHERE code = 'FE'), 'FE-NDB', 'Notas de Débito', 'Emisión de notas de débito', '/modules/fe/debit_notes.php', 'fa-file-plus', 3),
((SELECT id FROM modules WHERE code = 'FE'), 'FE-GDE', 'Guías de Despacho', 'Guías electrónicas de despacho', '/modules/fe/dispatch_guides.php', 'fa-truck', 4),
((SELECT id FROM modules WHERE code = 'FE'), 'FE-CERT', 'Certificación', 'Certificados digitales y firma', '/modules/fe/certification.php', 'fa-certificate', 5),
((SELECT id FROM modules WHERE code = 'FE'), 'FE-TRB', 'Cumplimiento', 'Cumplimiento tributario y reportes', '/modules/fe/compliance.php', 'fa-shield-alt', 6);

-- MÓDULO 12: GESTIÓN DE PROYECTOS (PM) - 8 Submódulos
INSERT INTO `submodules` (`module_id`, `code`, `name`, `description`, `url`, `icon`, `sort_order`) VALUES
((SELECT id FROM modules WHERE code = 'PM'), 'PM-PRJ', 'Proyectos', 'Cartera de proyectos', '/modules/pm/projects.php', 'fa-project-diagram', 1),
((SELECT id FROM modules WHERE code = 'PM'), 'PM-TSK', 'Tareas', 'Gestión de tareas y subtareas', '/modules/pm/tasks.php', 'fa-tasks', 2),
((SELECT id FROM modules WHERE code = 'PM'), 'PM-GNT', 'Gantt', 'Diagrama de Gantt interactivo', '/modules/pm/gantt.php', 'fa-chart-bar', 3),
((SELECT id FROM modules WHERE code = 'PM'), 'PM-RES', 'Recursos', 'Asignación de recursos', '/modules/pm/resources.php', 'fa-users', 4),
((SELECT id FROM modules WHERE code = 'PM'), 'PM-BUD', 'Presupuesto', 'Control presupuestario de proyectos', '/modules/pm/budget.php', 'fa-calculator', 5),
((SELECT id FROM modules WHERE code = 'PM'), 'PM-RSK', 'Riesgos', 'Gestión de riesgos', '/modules/pm/risks.php', 'fa-exclamation-triangle', 6),
((SELECT id FROM modules WHERE code = 'PM'), 'PM-DOC', 'Documentación', 'Repositorio de documentos', '/modules/pm/documents.php', 'fa-folder', 7),
((SELECT id FROM modules WHERE code = 'PM'), 'PM-COL', 'Colaboración', 'Herramientas de colaboración', '/modules/pm/collaboration.php', 'fa-comments', 8);

-- MÓDULO 13: CONFIGURACIÓN & ADMIN (ADM) - 10 Submódulos
INSERT INTO `submodules` (`module_id`, `code`, `name`, `description`, `url`, `icon`, `sort_order`) VALUES
((SELECT id FROM modules WHERE code = 'ADM'), 'ADM-USR', 'Usuarios', 'Gestión de usuarios del sistema', '/modules/adm/users.php', 'fa-users', 1),
((SELECT id FROM modules WHERE code = 'ADM'), 'ADM-ROL', 'Roles y Permisos', 'Configuración de roles y permisos', '/modules/adm/roles.php', 'fa-user-shield', 2),
((SELECT id FROM modules WHERE code = 'ADM'), 'ADM-SEC', 'Seguridad', 'Configuración de seguridad', '/modules/adm/security.php', 'fa-lock', 3),
((SELECT id FROM modules WHERE code = 'ADM'), 'ADM-AUD', 'Auditoría', 'Logs de auditoría y trazabilidad', '/modules/adm/audit.php', 'fa-history', 4),
((SELECT id FROM modules WHERE code = 'ADM'), 'ADM-WF', 'Workflows', 'Configuración de flujos de trabajo', '/modules/adm/workflows.php', 'fa-sitemap', 5),
((SELECT id FROM modules WHERE code = 'ADM'), 'ADM-AUTO', 'Automatizaciones', 'Reglas de automatización', '/modules/adm/automations.php', 'fa-robot', 6),
((SELECT id FROM modules WHERE code = 'ADM'), 'ADM-API', 'API', 'Integración API y webhooks', '/modules/adm/api.php', 'fa-code', 7),
((SELECT id FROM modules WHERE code = 'ADM'), 'ADM-BKP', 'Respaldos', 'Backup y recuperación', '/modules/adm/backup.php', 'fa-download', 8),
((SELECT id FROM modules WHERE code = 'ADM'), 'ADM-CUS', 'Personalización', 'Personalización de la interfaz', '/modules/adm/customization.php', 'fa-paint-brush', 9),
((SELECT id FROM modules WHERE code = 'ADM'), 'ADM-SYS', 'Sistema', 'Configuración general del sistema', '/modules/adm/system.php', 'fa-cogs', 10);

-- MÓDULO 14: MOBILE APPS (MOB) - 5 Submódulos
INSERT INTO `submodules` (`module_id`, `code`, `name`, `description`, `url`, `icon`, `sort_order`) VALUES
((SELECT id FROM modules WHERE code = 'MOB'), 'MOB-IOS', 'App iOS', 'Aplicación nativa para iPhone/iPad', '/modules/mob/ios.php', 'fa-apple', 1),
((SELECT id FROM modules WHERE code = 'MOB'), 'MOB-AND', 'App Android', 'Aplicación nativa para Android', '/modules/mob/android.php', 'fa-android', 2),
((SELECT id FROM modules WHERE code = 'MOB'), 'MOB-OFF', 'Modo Offline', 'Sincronización y modo offline', '/modules/mob/offline.php', 'fa-sync', 3),
((SELECT id FROM modules WHERE code = 'MOB'), 'MOB-PUSH', 'Notificaciones', 'Push notifications y alertas', '/modules/mob/notifications.php', 'fa-bell', 4),
((SELECT id FROM modules WHERE code = 'MOB'), 'MOB-GEO', 'Geolocalización', 'Servicios basados en ubicación', '/modules/mob/geolocation.php', 'fa-map-marker-alt', 5);
