-- =====================================================
-- CONECTA ERP v2.0.0
-- SEED PARA NUEVA EMPRESA (SISTEMA EN BLANCO)
-- =====================================================
-- Este script crea los datos iniciales para una nueva empresa
-- Se ejecuta automáticamente cuando se registra un nuevo usuario
-- TODAS las tablas están aisladas por company_id
-- =====================================================

-- NOTA: El company_id se reemplaza dinámicamente por el script PHP
-- Usar {{COMPANY_ID}} como placeholder

-- =====================================================
-- MÓDULO FI: FINANZAS
-- =====================================================

-- Plan de Cuentas Base (Adaptable a Chile, USA, Brasil, etc.)
INSERT INTO `fi_chart_of_accounts` (`company_id`, `account_code`, `account_name`, `account_type`, `parent_account_id`, `level`, `is_header`, `currency`, `is_active`) VALUES
({{COMPANY_ID}}, '1', 'ACTIVOS', 'asset', NULL, 1, 1, 'CLP', 1),
({{COMPANY_ID}}, '11', 'ACTIVO CORRIENTE', 'asset', NULL, 2, 1, 'CLP', 1),
({{COMPANY_ID}}, '1101', 'Caja', 'asset', NULL, 3, 0, 'CLP', 1),
({{COMPANY_ID}}, '1102', 'Bancos', 'asset', NULL, 3, 0, 'CLP', 1),
({{COMPANY_ID}}, '1103', 'Cuentas por Cobrar', 'asset', NULL, 3, 0, 'CLP', 1),
({{COMPANY_ID}}, '1104', 'Inventarios', 'asset', NULL, 3, 0, 'CLP', 1),
({{COMPANY_ID}}, '12', 'ACTIVO NO CORRIENTE', 'asset', NULL, 2, 1, 'CLP', 1),
({{COMPANY_ID}}, '1201', 'Propiedad, Planta y Equipo', 'asset', NULL, 3, 0, 'CLP', 1),
({{COMPANY_ID}}, '1202', 'Depreciación Acumulada', 'asset', NULL, 3, 0, 'CLP', 1),
({{COMPANY_ID}}, '1203', 'Intangibles', 'asset', NULL, 3, 0, 'CLP', 1),

({{COMPANY_ID}}, '2', 'PASIVOS', 'liability', NULL, 1, 1, 'CLP', 1),
({{COMPANY_ID}}, '21', 'PASIVO CORRIENTE', 'liability', NULL, 2, 1, 'CLP', 1),
({{COMPANY_ID}}, '2101', 'Cuentas por Pagar', 'liability', NULL, 3, 0, 'CLP', 1),
({{COMPANY_ID}}, '2102', 'Impuestos por Pagar', 'liability', NULL, 3, 0, 'CLP', 1),
({{COMPANY_ID}}, '2103', 'Sueldos por Pagar', 'liability', NULL, 3, 0, 'CLP', 1),
({{COMPANY_ID}}, '22', 'PASIVO NO CORRIENTE', 'liability', NULL, 2, 1, 'CLP', 1),
({{COMPANY_ID}}, '2201', 'Préstamos Bancarios LP', 'liability', NULL, 3, 0, 'CLP', 1),
({{COMPANY_ID}}, '2202', 'Obligaciones LP', 'liability', NULL, 3, 0, 'CLP', 1),

({{COMPANY_ID}}, '3', 'PATRIMONIO', 'equity', NULL, 1, 1, 'CLP', 1),
({{COMPANY_ID}}, '3101', 'Capital', 'equity', NULL, 2, 0, 'CLP', 1),
({{COMPANY_ID}}, '3102', 'Utilidades Retenidas', 'equity', NULL, 2, 0, 'CLP', 1),
({{COMPANY_ID}}, '3103', 'Resultado del Ejercicio', 'equity', NULL, 2, 0, 'CLP', 1),

({{COMPANY_ID}}, '4', 'INGRESOS', 'revenue', NULL, 1, 1, 'CLP', 1),
({{COMPANY_ID}}, '4101', 'Ventas', 'revenue', NULL, 2, 0, 'CLP', 1),
({{COMPANY_ID}}, '4102', 'Servicios', 'revenue', NULL, 2, 0, 'CLP', 1),
({{COMPANY_ID}}, '4103', 'Otros Ingresos', 'revenue', NULL, 2, 0, 'CLP', 1),

({{COMPANY_ID}}, '5', 'COSTO DE VENTAS', 'cost_of_sales', NULL, 1, 1, 'CLP', 1),
({{COMPANY_ID}}, '5101', 'Costo de Mercaderías', 'cost_of_sales', NULL, 2, 0, 'CLP', 1),
({{COMPANY_ID}}, '5102', 'Costo de Servicios', 'cost_of_sales', NULL, 2, 0, 'CLP', 1),

({{COMPANY_ID}}, '6', 'GASTOS', 'expense', NULL, 1, 1, 'CLP', 1),
({{COMPANY_ID}}, '6101', 'Gastos de Administración', 'expense', NULL, 2, 0, 'CLP', 1),
({{COMPANY_ID}}, '6102', 'Gastos de Ventas', 'expense', NULL, 2, 0, 'CLP', 1),
({{COMPANY_ID}}, '6103', 'Gastos Financieros', 'expense', NULL, 2, 0, 'CLP', 1);

-- Períodos Fiscales (Año actual)
INSERT INTO `fi_fiscal_periods` (`company_id`, `year`, `month`, `period_name`, `start_date`, `end_date`, `status`) VALUES
({{COMPANY_ID}}, YEAR(NOW()), 1, 'Enero {{CURRENT_YEAR}}', CONCAT(YEAR(NOW()), '-01-01'), CONCAT(YEAR(NOW()), '-01-31'), 'open'),
({{COMPANY_ID}}, YEAR(NOW()), 2, 'Febrero {{CURRENT_YEAR}}', CONCAT(YEAR(NOW()), '-02-01'), CONCAT(YEAR(NOW()), '-02-28'), 'open'),
({{COMPANY_ID}}, YEAR(NOW()), 3, 'Marzo {{CURRENT_YEAR}}', CONCAT(YEAR(NOW()), '-03-01'), CONCAT(YEAR(NOW()), '-03-31'), 'open'),
({{COMPANY_ID}}, YEAR(NOW()), 4, 'Abril {{CURRENT_YEAR}}', CONCAT(YEAR(NOW()), '-04-01'), CONCAT(YEAR(NOW()), '-04-30'), 'open'),
({{COMPANY_ID}}, YEAR(NOW()), 5, 'Mayo {{CURRENT_YEAR}}', CONCAT(YEAR(NOW()), '-05-01'), CONCAT(YEAR(NOW()), '-05-31'), 'open'),
({{COMPANY_ID}}, YEAR(NOW()), 6, 'Junio {{CURRENT_YEAR}}', CONCAT(YEAR(NOW()), '-06-01'), CONCAT(YEAR(NOW()), '-06-30'), 'open'),
({{COMPANY_ID}}, YEAR(NOW()), 7, 'Julio {{CURRENT_YEAR}}', CONCAT(YEAR(NOW()), '-07-01'), CONCAT(YEAR(NOW()), '-07-31'), 'open'),
({{COMPANY_ID}}, YEAR(NOW()), 8, 'Agosto {{CURRENT_YEAR}}', CONCAT(YEAR(NOW()), '-08-01'), CONCAT(YEAR(NOW()), '-08-31'), 'open'),
({{COMPANY_ID}}, YEAR(NOW()), 9, 'Septiembre {{CURRENT_YEAR}}', CONCAT(YEAR(NOW()), '-09-01'), CONCAT(YEAR(NOW()), '-09-30'), 'open'),
({{COMPANY_ID}}, YEAR(NOW()), 10, 'Octubre {{CURRENT_YEAR}}', CONCAT(YEAR(NOW()), '-10-01'), CONCAT(YEAR(NOW()), '-10-31'), 'open'),
({{COMPANY_ID}}, YEAR(NOW()), 11, 'Noviembre {{CURRENT_YEAR}}', CONCAT(YEAR(NOW()), '-11-01'), CONCAT(YEAR(NOW()), '-11-30'), 'open'),
({{COMPANY_ID}}, YEAR(NOW()), 12, 'Diciembre {{CURRENT_YEAR}}', CONCAT(YEAR(NOW()), '-12-01'), CONCAT(YEAR(NOW()), '-12-31'), 'open');

-- =====================================================
-- MÓDULO CO: CONTROLLING
-- =====================================================

-- Centros de Costo Base
INSERT INTO `co_cost_centers` (`company_id`, `cost_center_code`, `cost_center_name`, `responsible_user_id`, `is_active`) VALUES
({{COMPANY_ID}}, 'ADM', 'Administración', {{OWNER_USER_ID}}, 1),
({{COMPANY_ID}}, 'VEN', 'Ventas', {{OWNER_USER_ID}}, 1),
({{COMPANY_ID}}, 'PRO', 'Producción', {{OWNER_USER_ID}}, 1),
({{COMPANY_ID}}, 'LOG', 'Logística', {{OWNER_USER_ID}}, 1);

-- =====================================================
-- MÓDULO SD: VENTAS Y DISTRIBUCIÓN
-- =====================================================

-- Tipos de Documento de Ventas
INSERT INTO `sd_document_types` (`company_id`, `doc_type_code`, `doc_type_name`, `prefix`, `is_active`) VALUES
({{COMPANY_ID}}, 'QUOT', 'Cotización', 'COT', 1),
({{COMPANY_ID}}, 'SORD', 'Orden de Venta', 'OV', 1),
({{COMPANY_ID}}, 'INV', 'Factura', 'FAC', 1),
({{COMPANY_ID}}, 'DN', 'Nota de Débito', 'ND', 1),
({{COMPANY_ID}}, 'CN', 'Nota de Crédito', 'NC', 1);

-- Condiciones de Pago
INSERT INTO `sd_payment_terms` (`company_id`, `term_code`, `term_name`, `days`, `is_active`) VALUES
({{COMPANY_ID}}, 'CASH', 'Contado', 0, 1),
({{COMPANY_ID}}, 'NET30', 'Neto 30 días', 30, 1),
({{COMPANY_ID}}, 'NET60', 'Neto 60 días', 60, 1),
({{COMPANY_ID}}, 'NET90', 'Neto 90 días', 90, 1);

-- =====================================================
-- MÓDULO MM: GESTIÓN DE MATERIALES
-- =====================================================

-- Tipos de Movimiento de Inventario
INSERT INTO `mm_movement_types` (`company_id`, `movement_code`, `movement_name`, `movement_direction`, `is_active`) VALUES
({{COMPANY_ID}}, 'PURCHASE', 'Compra', 'in', 1),
({{COMPANY_ID}}, 'SALE', 'Venta', 'out', 1),
({{COMPANY_ID}}, 'ADJUST_IN', 'Ajuste Entrada', 'in', 1),
({{COMPANY_ID}}, 'ADJUST_OUT', 'Ajuste Salida', 'out', 1),
({{COMPANY_ID}}, 'TRANSFER', 'Transferencia', 'neutral', 1),
({{COMPANY_ID}}, 'RETURN', 'Devolución', 'in', 1);

-- Categorías de Producto
INSERT INTO `mm_categories` (`company_id`, `category_code`, `category_name`, `is_active`) VALUES
({{COMPANY_ID}}, 'PROD', 'Productos Terminados', 1),
({{COMPANY_ID}}, 'MAT', 'Materias Primas', 1),
({{COMPANY_ID}}, 'SERV', 'Servicios', 1),
({{COMPANY_ID}}, 'SUMIN', 'Suministros', 1);

-- =====================================================
-- MÓDULO PP: PLANIFICACIÓN DE LA PRODUCCIÓN
-- =====================================================

-- Tipos de Centro de Trabajo
INSERT INTO `pp_work_center_types` (`company_id`, `type_code`, `type_name`, `is_active`) VALUES
({{COMPANY_ID}}, 'MACH', 'Maquinaria', 1),
({{COMPANY_ID}}, 'MANUAL', 'Manual', 1),
({{COMPANY_ID}}, 'AUTO', 'Automatizado', 1);

-- =====================================================
-- MÓDULO QM: GESTIÓN DE CALIDAD
-- =====================================================

-- Tipos de Inspección
INSERT INTO `qm_inspection_types` (`company_id`, `type_code`, `type_name`, `is_active`) VALUES
({{COMPANY_ID}}, 'IQC', 'Control Calidad Entrada', 1),
({{COMPANY_ID}}, 'IPQC', 'Control Calidad Proceso', 1),
({{COMPANY_ID}}, 'FQC', 'Control Calidad Final', 1);

-- =====================================================
-- MÓDULO PM: MANTENIMIENTO
-- =====================================================

-- Tipos de Mantenimiento
INSERT INTO `pm_maintenance_types` (`company_id`, `type_code`, `type_name`, `is_active`) VALUES
({{COMPANY_ID}}, 'PREV', 'Preventivo', 1),
({{COMPANY_ID}}, 'CORR', 'Correctivo', 1),
({{COMPANY_ID}}, 'PRED', 'Predictivo', 1);

-- =====================================================
-- MÓDULO HR: RECURSOS HUMANOS
-- =====================================================

-- Departamentos
INSERT INTO `hr_departments` (`company_id`, `department_code`, `department_name`, `is_active`) VALUES
({{COMPANY_ID}}, 'ADM', 'Administración', 1),
({{COMPANY_ID}}, 'VEN', 'Ventas', 1),
({{COMPANY_ID}}, 'OPE', 'Operaciones', 1),
({{COMPANY_ID}}, 'TI', 'Tecnología', 1);

-- Tipos de Contrato
INSERT INTO `hr_contract_types` (`company_id`, `type_code`, `type_name`, `is_active`) VALUES
({{COMPANY_ID}}, 'INDEF', 'Indefinido', 1),
({{COMPANY_ID}}, 'FIJO', 'Plazo Fijo', 1),
({{COMPANY_ID}}, 'TEMP', 'Temporal', 1),
({{COMPANY_ID}}, 'PRAC', 'Práctica', 1);

-- =====================================================
-- MÓDULO CRM: GESTIÓN DE RELACIONES CON CLIENTES
-- =====================================================

-- Etapas de Oportunidad
INSERT INTO `crm_opportunity_stages` (`company_id`, `stage_code`, `stage_name`, `probability`, `sort_order`, `is_active`) VALUES
({{COMPANY_ID}}, 'LEAD', 'Lead', 10, 1, 1),
({{COMPANY_ID}}, 'QUAL', 'Calificado', 25, 2, 1),
({{COMPANY_ID}}, 'PROP', 'Propuesta', 50, 3, 1),
({{COMPANY_ID}}, 'NEG', 'Negociación', 75, 4, 1),
({{COMPANY_ID}}, 'WON', 'Ganado', 100, 5, 1),
({{COMPANY_ID}}, 'LOST', 'Perdido', 0, 6, 1);

-- Tipos de Actividad CRM
INSERT INTO `crm_activity_types` (`company_id`, `type_code`, `type_name`, `is_active`) VALUES
({{COMPANY_ID}}, 'CALL', 'Llamada', 1),
({{COMPANY_ID}}, 'EMAIL', 'Email', 1),
({{COMPANY_ID}}, 'MEET', 'Reunión', 1),
({{COMPANY_ID}}, 'TASK', 'Tarea', 1);

-- =====================================================
-- MÓDULO SCM: GESTIÓN DE CADENA DE SUMINISTRO
-- =====================================================

-- Modos de Transporte
INSERT INTO `scm_transport_modes` (`company_id`, `mode_code`, `mode_name`, `is_active`) VALUES
({{COMPANY_ID}}, 'LAND', 'Terrestre', 1),
({{COMPANY_ID}}, 'SEA', 'Marítimo', 1),
({{COMPANY_ID}}, 'AIR', 'Aéreo', 1),
({{COMPANY_ID}}, 'RAIL', 'Ferroviario', 1);

-- =====================================================
-- MÓDULO PS: GESTIÓN DE PROYECTOS
-- =====================================================

-- Estados de Proyecto
INSERT INTO `ps_project_statuses` (`company_id`, `status_code`, `status_name`, `is_active`) VALUES
({{COMPANY_ID}}, 'PLAN', 'Planificación', 1),
({{COMPANY_ID}}, 'EXEC', 'Ejecución', 1),
({{COMPANY_ID}}, 'HOLD', 'En Espera', 1),
({{COMPANY_ID}}, 'COMP', 'Completado', 1),
({{COMPANY_ID}}, 'CANC', 'Cancelado', 1);

-- =====================================================
-- MÓDULO BI: BUSINESS INTELLIGENCE
-- =====================================================

-- Dashboard por defecto (se configura después según el plan)
-- Los dashboards se crean dinámicamente

-- =====================================================
-- MÓDULO WM: GESTIÓN DE ALMACENES
-- =====================================================

-- Tipos de Almacén
INSERT INTO `wm_warehouse_types` (`company_id`, `type_code`, `type_name`, `is_active`) VALUES
({{COMPANY_ID}}, 'MAIN', 'Principal', 1),
({{COMPANY_ID}}, 'TRANS', 'Tránsito', 1),
({{COMPANY_ID}}, 'QUAR', 'Cuarentena', 1),
({{COMPANY_ID}}, 'RET', 'Devoluciones', 1);

-- Almacén Principal por defecto
INSERT INTO `wm_warehouses` (`company_id`, `warehouse_code`, `warehouse_name`, `warehouse_type`, `is_active`) VALUES
({{COMPANY_ID}}, 'WH01', 'Almacén Principal', 'MAIN', 1);

-- =====================================================
-- CONFIGURACIONES GENERALES
-- =====================================================

-- Configuración de la empresa
INSERT INTO `company_settings` (`company_id`, `setting_key`, `setting_value`) VALUES
({{COMPANY_ID}}, 'currency', 'CLP'),
({{COMPANY_ID}}, 'language', 'es'),
({{COMPANY_ID}}, 'timezone', 'America/Santiago'),
({{COMPANY_ID}}, 'date_format', 'd/m/Y'),
({{COMPANY_ID}}, 'tax_rate', '19'),
({{COMPANY_ID}}, 'fiscal_year_start', '01-01'),
({{COMPANY_ID}}, 'decimal_places', '2'),
({{COMPANY_ID}}, 'thousands_separator', '.'),
({{COMPANY_ID}}, 'decimal_separator', ',');

-- =====================================================
-- FIN DEL SEED
-- =====================================================
