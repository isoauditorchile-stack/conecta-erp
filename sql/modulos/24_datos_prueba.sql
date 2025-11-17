-- ==================================================================
-- DATOS DE PRUEBA - Plan de Cuentas Chileno Básico y Productos
-- ==================================================================

USE `conectae_conectaerpbd`;

-- ==================================================================
-- PLAN DE CUENTAS CHILENO BÁSICO
-- ==================================================================
-- Para empresa_id = 1 (modificar según necesidad)
-- ==================================================================

-- ACTIVOS (1000)
INSERT INTO plan_cuentas (empresa_id, codigo, nombre, tipo_cuenta, nivel, cuenta_padre_id, acepta_movimiento, naturaleza, activo) VALUES
(1, '1', 'ACTIVOS', 'activo', 1, NULL, 0, 'deudora', 1),
(1, '11', 'ACTIVO CIRCULANTE', 'activo', 2, (SELECT id FROM plan_cuentas WHERE codigo = '1' AND empresa_id = 1), 0, 'deudora', 1),
(1, '1101', 'Caja', 'activo', 3, (SELECT id FROM plan_cuentas WHERE codigo = '11' AND empresa_id = 1), 1, 'deudora', 1),
(1, '1102', 'Banco Cuenta Corriente', 'activo', 3, (SELECT id FROM plan_cuentas WHERE codigo = '11' AND empresa_id = 1), 1, 'deudora', 1),
(1, '1103', 'Banco Cuenta Ahorro', 'activo', 3, (SELECT id FROM plan_cuentas WHERE codigo = '11' AND empresa_id = 1), 1, 'deudora', 1),
(1, '1201', 'Inversiones Temporales', 'activo', 3, (SELECT id FROM plan_cuentas WHERE codigo = '11' AND empresa_id = 1), 1, 'deudora', 1),
(1, '1301', 'Clientes por Cobrar', 'activo', 3, (SELECT id FROM plan_cuentas WHERE codigo = '11' AND empresa_id = 1), 1, 'deudora', 1),
(1, '1302', 'Documentos por Cobrar', 'activo', 3, (SELECT id FROM plan_cuentas WHERE codigo = '11' AND empresa_id = 1), 1, 'deudora', 1),
(1, '1401', 'Mercaderías', 'activo', 3, (SELECT id FROM plan_cuentas WHERE codigo = '11' AND empresa_id = 1), 1, 'deudora', 1),
(1, '1402', 'IVA Crédito Fiscal', 'activo', 3, (SELECT id FROM plan_cuentas WHERE codigo = '11' AND empresa_id = 1), 1, 'deudora', 1),
(1, '1403', 'Pagos Provisionales Mensuales', 'activo', 3, (SELECT id FROM plan_cuentas WHERE codigo = '11' AND empresa_id = 1), 1, 'deudora', 1);

-- PASIVOS (2000)
INSERT INTO plan_cuentas (empresa_id, codigo, nombre, tipo_cuenta, nivel, cuenta_padre_id, acepta_movimiento, naturaleza, activo) VALUES
(1, '2', 'PASIVOS', 'pasivo', 1, NULL, 0, 'acreedora', 1),
(1, '21', 'PASIVO CIRCULANTE', 'pasivo', 2, (SELECT id FROM plan_cuentas WHERE codigo = '2' AND empresa_id = 1), 0, 'acreedora', 1),
(1, '2101', 'Proveedores por Pagar', 'pasivo', 3, (SELECT id FROM plan_cuentas WHERE codigo = '21' AND empresa_id = 1), 1, 'acreedora', 1),
(1, '2102', 'Documentos por Pagar', 'pasivo', 3, (SELECT id FROM plan_cuentas WHERE codigo = '21' AND empresa_id = 1), 1, 'acreedora', 1),
(1, '2201', 'IVA Débito Fiscal', 'pasivo', 3, (SELECT id FROM plan_cuentas WHERE codigo = '21' AND empresa_id = 1), 1, 'acreedora', 1),
(1, '2202', 'Retenciones por Pagar', 'pasivo', 3, (SELECT id FROM plan_cuentas WHERE codigo = '21' AND empresa_id = 1), 1, 'acreedora', 1),
(1, '2203', 'Sueldos por Pagar', 'pasivo', 3, (SELECT id FROM plan_cuentas WHERE codigo = '21' AND empresa_id = 1), 1, 'acreedora', 1),
(1, '2204', 'Leyes Sociales por Pagar', 'pasivo', 3, (SELECT id FROM plan_cuentas WHERE codigo = '21' AND empresa_id = 1), 1, 'acreedora', 1),
(1, '2205', 'Impuesto Renta por Pagar', 'pasivo', 3, (SELECT id FROM plan_cuentas WHERE codigo = '21' AND empresa_id = 1), 1, 'acreedora', 1);

-- PATRIMONIO (3000)
INSERT INTO plan_cuentas (empresa_id, codigo, nombre, tipo_cuenta, nivel, cuenta_padre_id, acepta_movimiento, naturaleza, activo) VALUES
(1, '3', 'PATRIMONIO', 'patrimonio', 1, NULL, 0, 'acreedora', 1),
(1, '3101', 'Capital', 'patrimonio', 2, (SELECT id FROM plan_cuentas WHERE codigo = '3' AND empresa_id = 1), 1, 'acreedora', 1),
(1, '3201', 'Utilidades Acumuladas', 'patrimonio', 2, (SELECT id FROM plan_cuentas WHERE codigo = '3' AND empresa_id = 1), 1, 'acreedora', 1),
(1, '3202', 'Utilidad del Ejercicio', 'patrimonio', 2, (SELECT id FROM plan_cuentas WHERE codigo = '3' AND empresa_id = 1), 1, 'acreedora', 1),
(1, '3203', 'Pérdida del Ejercicio', 'patrimonio', 2, (SELECT id FROM plan_cuentas WHERE codigo = '3' AND empresa_id = 1), 1, 'deudora', 1);

-- INGRESOS (4000)
INSERT INTO plan_cuentas (empresa_id, codigo, nombre, tipo_cuenta, nivel, cuenta_padre_id, acepta_movimiento, naturaleza, activo) VALUES
(1, '4', 'INGRESOS', 'ingreso', 1, NULL, 0, 'acreedora', 1),
(1, '4101', 'Ventas', 'ingreso', 2, (SELECT id FROM plan_cuentas WHERE codigo = '4' AND empresa_id = 1), 1, 'acreedora', 1),
(1, '4102', 'Ventas de Servicios', 'ingreso', 2, (SELECT id FROM plan_cuentas WHERE codigo = '4' AND empresa_id = 1), 1, 'acreedora', 1),
(1, '4201', 'Otros Ingresos', 'ingreso', 2, (SELECT id FROM plan_cuentas WHERE codigo = '4' AND empresa_id = 1), 1, 'acreedora', 1),
(1, '4202', 'Ingresos Financieros', 'ingreso', 2, (SELECT id FROM plan_cuentas WHERE codigo = '4' AND empresa_id = 1), 1, 'acreedora', 1);

-- GASTOS (5000)
INSERT INTO plan_cuentas (empresa_id, codigo, nombre, tipo_cuenta, nivel, cuenta_padre_id, acepta_movimiento, naturaleza, activo) VALUES
(1, '5', 'GASTOS', 'gasto', 1, NULL, 0, 'deudora', 1),
(1, '5101', 'Costo de Ventas', 'gasto', 2, (SELECT id FROM plan_cuentas WHERE codigo = '5' AND empresa_id = 1), 1, 'deudora', 1),
(1, '5201', 'Gastos de Administración', 'gasto', 2, (SELECT id FROM plan_cuentas WHERE codigo = '5' AND empresa_id = 1), 1, 'deudora', 1),
(1, '5202', 'Sueldos', 'gasto', 2, (SELECT id FROM plan_cuentas WHERE codigo = '5' AND empresa_id = 1), 1, 'deudora', 1),
(1, '5203', 'Leyes Sociales', 'gasto', 2, (SELECT id FROM plan_cuentas WHERE codigo = '5' AND empresa_id = 1), 1, 'deudora', 1),
(1, '5204', 'Arriendo', 'gasto', 2, (SELECT id FROM plan_cuentas WHERE codigo = '5' AND empresa_id = 1), 1, 'deudora', 1),
(1, '5205', 'Servicios Básicos', 'gasto', 2, (SELECT id FROM plan_cuentas WHERE codigo = '5' AND empresa_id = 1), 1, 'deudora', 1),
(1, '5206', 'Depreciación', 'gasto', 2, (SELECT id FROM plan_cuentas WHERE codigo = '5' AND empresa_id = 1), 1, 'deudora', 1),
(1, '5207', 'Gastos Financieros', 'gasto', 2, (SELECT id FROM plan_cuentas WHERE codigo = '5' AND empresa_id = 1), 1, 'deudora', 1);

-- ==================================================================
-- CATEGORÍAS DE PRODUCTOS DE EJEMPLO
-- ==================================================================

INSERT INTO categorias_productos (empresa_id, nombre, descripcion, orden, activo) VALUES
(1, 'Electrónica', 'Productos electrónicos y tecnología', 1, 1),
(1, 'Oficina', 'Artículos de oficina', 2, 1),
(1, 'Hogar', 'Artículos para el hogar', 3, 1);

-- ==================================================================
-- PRODUCTOS DE EJEMPLO
-- ==================================================================

INSERT INTO productos (empresa_id, codigo, nombre, descripcion, tipo, categoria_id, unidad_medida_id, precio_compra, precio_venta, stock_actual, stock_minimo, activo) VALUES
(1, 'PROD-001', 'Notebook HP 15"', 'Notebook HP 15 pulgadas, Intel i5, 8GB RAM', 'producto', (SELECT id FROM categorias_productos WHERE nombre = 'Electrónica' LIMIT 1), (SELECT id FROM unidades_medida WHERE codigo = 'UN' LIMIT 1), 450000, 550000, 10, 3, 1),
(1, 'PROD-002', 'Mouse Inalámbrico', 'Mouse inalámbrico óptico', 'producto', (SELECT id FROM categorias_productos WHERE nombre = 'Electrónica' LIMIT 1), (SELECT id FROM unidades_medida WHERE codigo = 'UN' LIMIT 1), 5000, 8000, 25, 5, 1),
(1, 'PROD-003', 'Teclado USB', 'Teclado USB estándar', 'producto', (SELECT id FROM categorias_productos WHERE nombre = 'Electrónica' LIMIT 1), (SELECT id FROM unidades_medida WHERE codigo = 'UN' LIMIT 1), 7000, 12000, 15, 5, 1),
(1, 'PROD-004', 'Resma Papel Carta', 'Resma papel tamaño carta, 500 hojas', 'producto', (SELECT id FROM categorias_productos WHERE nombre = 'Oficina' LIMIT 1), (SELECT id FROM unidades_medida WHERE codigo = 'UN' LIMIT 1), 2500, 4000, 50, 10, 1),
(1, 'PROD-005', 'Lámpara LED Escritorio', 'Lámpara LED para escritorio', 'producto', (SELECT id FROM categorias_productos WHERE nombre = 'Hogar' LIMIT 1), (SELECT id FROM unidades_medida WHERE codigo = 'UN' LIMIT 1), 8000, 15000, 8, 3, 1);

-- ==================================================================
-- ALMACÉN DE EJEMPLO
-- ==================================================================

INSERT INTO almacenes (empresa_id, codigo, nombre, tipo, activo) VALUES
(1, 'ALM-PRIN', 'Almacén Principal', 'principal', 1),
(1, 'ALM-SEC', 'Almacén Secundario', 'secundario', 1);

-- Inicializar stock en almacén principal
INSERT INTO stock_almacen (almacen_id, producto_id, cantidad)
SELECT
    (SELECT id FROM almacenes WHERE codigo = 'ALM-PRIN' AND empresa_id = 1 LIMIT 1),
    id,
    stock_actual
FROM productos
WHERE empresa_id = 1;

-- ==================================================================
-- INDICADORES ECONÓMICOS INICIALES
-- ==================================================================

INSERT INTO indicadores_economicos (fecha, dolar, uf, utm, euro) VALUES
(CURDATE(), 920.50, 36824.50, 65000.00, 1020.30)
ON DUPLICATE KEY UPDATE
    dolar = VALUES(dolar),
    uf = VALUES(uf),
    utm = VALUES(utm),
    euro = VALUES(euro);

-- ==================================================================
-- FECHAS IMPORTANTES INICIALES
-- ==================================================================

INSERT INTO fechas_importantes (tipo, categoria, titulo, descripcion, fecha, recurrente, dias_alerta_previa) VALUES
('impuesto', 'IVA', 'Declaración y Pago IVA (F29)', 'Declaración mensual de IVA', DATE_ADD(LAST_DAY(CURDATE()), INTERVAL 12 DAY), 1, 7),
('imposicion', 'Previred', 'Pago Previred', 'Pago mensual de imposiciones', DATE_ADD(LAST_DAY(CURDATE()), INTERVAL 10 DAY), 1, 5),
('declaracion', 'Renta', 'Declaración Renta Anual', 'Declaración de impuestos anuales', CONCAT(YEAR(CURDATE()), '-04-30'), 1, 30);

-- ==================================================================
-- FIN DATOS DE PRUEBA
-- ==================================================================
