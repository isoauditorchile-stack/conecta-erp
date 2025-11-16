-- =====================================================
-- MÓDULO 3: FINANZAS (FI) - CONECTA ERP
-- Tablas para Contabilidad, Cuentas por Pagar/Cobrar, Tesorería
-- =====================================================

USE conectae_conectaerpbd;

-- =====================================================
-- TABLA: plan_cuentas
-- Plan contable de la empresa
-- =====================================================
CREATE TABLE IF NOT EXISTS `plan_cuentas` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `codigo` VARCHAR(50) NOT NULL,
  `nombre` VARCHAR(255) NOT NULL,
  `cuenta_padre_id` INT(11) DEFAULT NULL COMMENT 'Para estructura jerárquica',
  `nivel` TINYINT(2) DEFAULT 1 COMMENT 'Nivel en la jerarquía',
  `tipo_cuenta` ENUM('activo','pasivo','patrimonio','ingreso','egreso','costos') NOT NULL,
  `naturaleza` ENUM('deudora','acreedora') NOT NULL,
  `acepta_movimientos` TINYINT(1) DEFAULT 1 COMMENT 'Si acepta asientos directos',
  `requiere_centro_costo` TINYINT(1) DEFAULT 0,
  `saldo_actual` DECIMAL(15,2) DEFAULT 0.00,
  `activa` TINYINT(1) DEFAULT 1,
  `fecha_creacion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `empresa_codigo` (`empresa_id`, `codigo`),
  KEY `idx_empresa` (`empresa_id`),
  KEY `idx_tipo` (`tipo_cuenta`),
  KEY `idx_padre` (`cuenta_padre_id`),
  CONSTRAINT `fk_plan_cuentas_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: libro_diario
-- Asientos contables
-- =====================================================
CREATE TABLE IF NOT EXISTS `libro_diario` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `numero_asiento` VARCHAR(50) NOT NULL,
  `fecha_asiento` DATE NOT NULL,
  `periodo` VARCHAR(7) NOT NULL COMMENT 'YYYY-MM',
  `tipo_asiento` ENUM('apertura','operacion','ajuste','cierre') DEFAULT 'operacion',
  `glosa` TEXT NOT NULL,
  `documento_referencia` VARCHAR(100) DEFAULT NULL,
  `total_debe` DECIMAL(15,2) DEFAULT 0.00,
  `total_haber` DECIMAL(15,2) DEFAULT 0.00,
  `estado` ENUM('borrador','confirmado','anulado') DEFAULT 'borrador',
  `creado_por` INT(11) DEFAULT NULL,
  `fecha_creacion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `aprobado_por` INT(11) DEFAULT NULL,
  `fecha_aprobacion` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `empresa_numero` (`empresa_id`, `numero_asiento`),
  KEY `idx_empresa` (`empresa_id`),
  KEY `idx_fecha` (`fecha_asiento`),
  KEY `idx_periodo` (`periodo`),
  KEY `idx_estado` (`estado`),
  CONSTRAINT `fk_libro_diario_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: detalle_asientos
-- Detalle de cada asiento contable
-- =====================================================
CREATE TABLE IF NOT EXISTS `detalle_asientos` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `asiento_id` INT(11) NOT NULL,
  `cuenta_id` INT(11) NOT NULL,
  `centro_costo_id` INT(11) DEFAULT NULL,
  `debe` DECIMAL(15,2) DEFAULT 0.00,
  `haber` DECIMAL(15,2) DEFAULT 0.00,
  `glosa` VARCHAR(255) DEFAULT NULL,
  `orden` TINYINT(2) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_asiento` (`asiento_id`),
  KEY `idx_cuenta` (`cuenta_id`),
  CONSTRAINT `fk_detalle_asientos_asiento` FOREIGN KEY (`asiento_id`) REFERENCES `libro_diario` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_detalle_asientos_cuenta` FOREIGN KEY (`cuenta_id`) REFERENCES `plan_cuentas` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: cuentas_por_pagar
-- Cuentas por pagar a proveedores (CP)
-- =====================================================
CREATE TABLE IF NOT EXISTS `cuentas_por_pagar` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `proveedor_id` INT(11) NOT NULL,
  `tipo_documento` ENUM('factura','boleta','nota_credito','nota_debito','otros') DEFAULT 'factura',
  `numero_documento` VARCHAR(100) NOT NULL,
  `fecha_emision` DATE NOT NULL,
  `fecha_vencimiento` DATE NOT NULL,
  `monto_neto` DECIMAL(15,2) DEFAULT 0.00,
  `monto_impuesto` DECIMAL(15,2) DEFAULT 0.00,
  `monto_total` DECIMAL(15,2) NOT NULL,
  `monto_pagado` DECIMAL(15,2) DEFAULT 0.00,
  `saldo_pendiente` DECIMAL(15,2) NOT NULL,
  `moneda` VARCHAR(3) DEFAULT 'CLP',
  `glosa` TEXT DEFAULT NULL,
  `estado` ENUM('pendiente','pagada_parcial','pagada','vencida','anulada') DEFAULT 'pendiente',
  `dias_vencidos` INT(11) GENERATED ALWAYS AS (DATEDIFF(CURDATE(), fecha_vencimiento)) STORED,
  `fecha_creacion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `creado_por` INT(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_empresa` (`empresa_id`),
  KEY `idx_proveedor` (`proveedor_id`),
  KEY `idx_estado` (`estado`),
  KEY `idx_vencimiento` (`fecha_vencimiento`),
  CONSTRAINT `fk_cp_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cp_proveedor` FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: pagos_proveedores
-- Registro de pagos a proveedores
-- =====================================================
CREATE TABLE IF NOT EXISTS `pagos_proveedores` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `cuenta_por_pagar_id` INT(11) NOT NULL,
  `fecha_pago` DATE NOT NULL,
  `monto_pago` DECIMAL(15,2) NOT NULL,
  `metodo_pago` ENUM('efectivo','transferencia','cheque','tarjeta','otro') NOT NULL,
  `numero_documento` VARCHAR(100) DEFAULT NULL COMMENT 'Número cheque, transferencia, etc.',
  `cuenta_bancaria_id` INT(11) DEFAULT NULL,
  `observaciones` TEXT DEFAULT NULL,
  `creado_por` INT(11) DEFAULT NULL,
  `fecha_creacion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cp` (`cuenta_por_pagar_id`),
  KEY `idx_fecha` (`fecha_pago`),
  CONSTRAINT `fk_pagos_cp` FOREIGN KEY (`cuenta_por_pagar_id`) REFERENCES `cuentas_por_pagar` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: cuentas_por_cobrar
-- Cuentas por cobrar a clientes (CTACOBRAR)
-- =====================================================
CREATE TABLE IF NOT EXISTS `cuentas_por_cobrar` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `cliente_id` INT(11) NOT NULL,
  `tipo_documento` ENUM('factura','boleta','nota_credito','nota_debito','otros') DEFAULT 'factura',
  `numero_documento` VARCHAR(100) NOT NULL,
  `fecha_emision` DATE NOT NULL,
  `fecha_vencimiento` DATE NOT NULL,
  `monto_neto` DECIMAL(15,2) DEFAULT 0.00,
  `monto_impuesto` DECIMAL(15,2) DEFAULT 0.00,
  `monto_total` DECIMAL(15,2) NOT NULL,
  `monto_cobrado` DECIMAL(15,2) DEFAULT 0.00,
  `saldo_pendiente` DECIMAL(15,2) NOT NULL,
  `moneda` VARCHAR(3) DEFAULT 'CLP',
  `glosa` TEXT DEFAULT NULL,
  `estado` ENUM('pendiente','cobrada_parcial','cobrada','vencida','incobrable','anulada') DEFAULT 'pendiente',
  `dias_vencidos` INT(11) GENERATED ALWAYS AS (DATEDIFF(CURDATE(), fecha_vencimiento)) STORED,
  `fecha_creacion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `creado_por` INT(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_empresa` (`empresa_id`),
  KEY `idx_cliente` (`cliente_id`),
  KEY `idx_estado` (`estado`),
  KEY `idx_vencimiento` (`fecha_vencimiento`),
  CONSTRAINT `fk_cc_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cc_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: cobranzas
-- Registro de cobranzas a clientes
-- =====================================================
CREATE TABLE IF NOT EXISTS `cobranzas` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `cuenta_por_cobrar_id` INT(11) NOT NULL,
  `fecha_cobranza` DATE NOT NULL,
  `monto_cobrado` DECIMAL(15,2) NOT NULL,
  `metodo_pago` ENUM('efectivo','transferencia','cheque','tarjeta','otro') NOT NULL,
  `numero_documento` VARCHAR(100) DEFAULT NULL,
  `cuenta_bancaria_id` INT(11) DEFAULT NULL,
  `observaciones` TEXT DEFAULT NULL,
  `creado_por` INT(11) DEFAULT NULL,
  `fecha_creacion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cc` (`cuenta_por_cobrar_id`),
  KEY `idx_fecha` (`fecha_cobranza`),
  CONSTRAINT `fk_cobranzas_cc` FOREIGN KEY (`cuenta_por_cobrar_id`) REFERENCES `cuentas_por_cobrar` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: cuentas_bancarias
-- Cuentas bancarias de la empresa (TESORERÍA)
-- =====================================================
CREATE TABLE IF NOT EXISTS `cuentas_bancarias` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `banco` VARCHAR(100) NOT NULL,
  `tipo_cuenta` ENUM('corriente','vista','ahorro') NOT NULL,
  `numero_cuenta` VARCHAR(50) NOT NULL,
  `moneda` VARCHAR(3) DEFAULT 'CLP',
  `saldo_actual` DECIMAL(15,2) DEFAULT 0.00,
  `saldo_contable` DECIMAL(15,2) DEFAULT 0.00,
  `cuenta_contable_id` INT(11) DEFAULT NULL COMMENT 'Cuenta del plan contable',
  `activa` TINYINT(1) DEFAULT 1,
  `fecha_creacion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_empresa` (`empresa_id`),
  KEY `idx_activa` (`activa`),
  CONSTRAINT `fk_cuentas_bancarias_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: movimientos_bancarios
-- Movimientos de cuentas bancarias
-- =====================================================
CREATE TABLE IF NOT EXISTS `movimientos_bancarios` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `cuenta_bancaria_id` INT(11) NOT NULL,
  `tipo_movimiento` ENUM('ingreso','egreso','transferencia') NOT NULL,
  `fecha_movimiento` DATE NOT NULL,
  `monto` DECIMAL(15,2) NOT NULL,
  `saldo_anterior` DECIMAL(15,2) DEFAULT 0.00,
  `saldo_nuevo` DECIMAL(15,2) DEFAULT 0.00,
  `concepto` VARCHAR(255) NOT NULL,
  `documento_referencia` VARCHAR(100) DEFAULT NULL,
  `conciliado` TINYINT(1) DEFAULT 0,
  `fecha_conciliacion` DATE DEFAULT NULL,
  `creado_por` INT(11) DEFAULT NULL,
  `fecha_creacion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cuenta` (`cuenta_bancaria_id`),
  KEY `idx_fecha` (`fecha_movimiento`),
  KEY `idx_conciliado` (`conciliado`),
  CONSTRAINT `fk_mov_bancarios_cuenta` FOREIGN KEY (`cuenta_bancaria_id`) REFERENCES `cuentas_bancarias` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: activos_fijos
-- Gestión de activos fijos (AF)
-- =====================================================
CREATE TABLE IF NOT EXISTS `activos_fijos` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `codigo` VARCHAR(50) NOT NULL,
  `nombre` VARCHAR(255) NOT NULL,
  `descripcion` TEXT DEFAULT NULL,
  `categoria` VARCHAR(100) DEFAULT NULL COMMENT 'Muebles, equipos, vehículos, etc.',
  `fecha_adquisicion` DATE NOT NULL,
  `valor_adquisicion` DECIMAL(15,2) NOT NULL,
  `proveedor_id` INT(11) DEFAULT NULL,
  `factura_numero` VARCHAR(100) DEFAULT NULL,
  `vida_util_anos` INT(11) DEFAULT 0,
  `valor_residual` DECIMAL(15,2) DEFAULT 0.00,
  `metodo_depreciacion` ENUM('lineal','acelerada','unidades_produccion') DEFAULT 'lineal',
  `depreciacion_acumulada` DECIMAL(15,2) DEFAULT 0.00,
  `valor_libro` DECIMAL(15,2) DEFAULT 0.00,
  `ubicacion` VARCHAR(255) DEFAULT NULL,
  `responsable_id` INT(11) DEFAULT NULL COMMENT 'Empleado responsable',
  `estado` ENUM('activo','vendido','dado_baja','en_reparacion') DEFAULT 'activo',
  `observaciones` TEXT DEFAULT NULL,
  `fecha_creacion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `creado_por` INT(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `empresa_codigo` (`empresa_id`, `codigo`),
  KEY `idx_empresa` (`empresa_id`),
  KEY `idx_estado` (`estado`),
  KEY `idx_categoria` (`categoria`),
  CONSTRAINT `fk_activos_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: depreciaciones
-- Registro de depreciaciones mensuales
-- =====================================================
CREATE TABLE IF NOT EXISTS `depreciaciones` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `activo_fijo_id` INT(11) NOT NULL,
  `periodo` VARCHAR(7) NOT NULL COMMENT 'YYYY-MM',
  `monto_depreciacion` DECIMAL(15,2) NOT NULL,
  `depreciacion_acumulada` DECIMAL(15,2) NOT NULL,
  `valor_libro` DECIMAL(15,2) NOT NULL,
  `asiento_id` INT(11) DEFAULT NULL COMMENT 'Asiento contable generado',
  `fecha_calculo` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `activo_periodo` (`activo_fijo_id`, `periodo`),
  KEY `idx_activo` (`activo_fijo_id`),
  KEY `idx_periodo` (`periodo`),
  CONSTRAINT `fk_depreciaciones_activo` FOREIGN KEY (`activo_fijo_id`) REFERENCES `activos_fijos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: presupuestos
-- Presupuestos anuales por centro de costo
-- =====================================================
CREATE TABLE IF NOT EXISTS `presupuestos` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `ano` INT(4) NOT NULL,
  `mes` TINYINT(2) DEFAULT NULL COMMENT 'NULL = anual, 1-12 = mensual',
  `centro_costo_id` INT(11) DEFAULT NULL,
  `cuenta_id` INT(11) NOT NULL,
  `monto_presupuestado` DECIMAL(15,2) NOT NULL,
  `monto_ejecutado` DECIMAL(15,2) DEFAULT 0.00,
  `porcentaje_ejecucion` DECIMAL(5,2) GENERATED ALWAYS AS ((monto_ejecutado / monto_presupuestado) * 100) STORED,
  `observaciones` TEXT DEFAULT NULL,
  `fecha_creacion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `creado_por` INT(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_empresa` (`empresa_id`),
  KEY `idx_ano` (`ano`),
  KEY `idx_cuenta` (`cuenta_id`),
  CONSTRAINT `fk_presupuestos_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- VISTAS ÚTILES
-- =====================================================

-- Vista: Balance General
CREATE OR REPLACE VIEW `v_balance_general` AS
SELECT
    pc.empresa_id,
    pc.tipo_cuenta,
    SUM(CASE WHEN pc.naturaleza = 'deudora' THEN pc.saldo_actual ELSE -pc.saldo_actual END) as saldo
FROM plan_cuentas pc
WHERE pc.activa = 1
GROUP BY pc.empresa_id, pc.tipo_cuenta;

-- Vista: Cuentas por pagar vencidas
CREATE OR REPLACE VIEW `v_cp_vencidas` AS
SELECT
    cp.*,
    p.razon_social as proveedor_nombre,
    DATEDIFF(CURDATE(), cp.fecha_vencimiento) as dias_vencidos
FROM cuentas_por_pagar cp
INNER JOIN proveedores p ON cp.proveedor_id = p.id
WHERE cp.estado IN ('pendiente', 'pagada_parcial')
AND cp.fecha_vencimiento < CURDATE();

-- Vista: Cuentas por cobrar vencidas
CREATE OR REPLACE VIEW `v_cc_vencidas` AS
SELECT
    cc.*,
    c.razon_social as cliente_nombre,
    DATEDIFF(CURDATE(), cc.fecha_vencimiento) as dias_vencidos
FROM cuentas_por_cobrar cc
INNER JOIN clientes c ON cc.cliente_id = c.id
WHERE cc.estado IN ('pendiente', 'cobrada_parcial')
AND cc.fecha_vencimiento < CURDATE();

-- =====================================================
-- TRIGGERS
-- =====================================================

DELIMITER //

-- Trigger: actualizar saldo CP al pagar
CREATE TRIGGER IF NOT EXISTS `tr_pagos_proveedores_insert` AFTER INSERT ON `pagos_proveedores`
FOR EACH ROW
BEGIN
    UPDATE cuentas_por_pagar
    SET monto_pagado = monto_pagado + NEW.monto_pago,
        saldo_pendiente = monto_total - (monto_pagado + NEW.monto_pago),
        estado = CASE
            WHEN (monto_pagado + NEW.monto_pago) >= monto_total THEN 'pagada'
            WHEN (monto_pagado + NEW.monto_pago) > 0 THEN 'pagada_parcial'
            ELSE 'pendiente'
        END
    WHERE id = NEW.cuenta_por_pagar_id;
END//

-- Trigger: actualizar saldo CC al cobrar
CREATE TRIGGER IF NOT EXISTS `tr_cobranzas_insert` AFTER INSERT ON `cobranzas`
FOR EACH ROW
BEGIN
    UPDATE cuentas_por_cobrar
    SET monto_cobrado = monto_cobrado + NEW.monto_cobrado,
        saldo_pendiente = monto_total - (monto_cobrado + NEW.monto_cobrado),
        estado = CASE
            WHEN (monto_cobrado + NEW.monto_cobrado) >= monto_total THEN 'cobrada'
            WHEN (monto_cobrado + NEW.monto_cobrado) > 0 THEN 'cobrada_parcial'
            ELSE 'pendiente'
        END
    WHERE id = NEW.cuenta_por_cobrar_id;
END//

-- Trigger: actualizar saldo bancario
CREATE TRIGGER IF NOT EXISTS `tr_mov_bancarios_insert` AFTER INSERT ON `movimientos_bancarios`
FOR EACH ROW
BEGIN
    UPDATE cuentas_bancarias
    SET saldo_actual = CASE
        WHEN NEW.tipo_movimiento IN ('ingreso', 'transferencia') THEN saldo_actual + NEW.monto
        ELSE saldo_actual - NEW.monto
    END
    WHERE id = NEW.cuenta_bancaria_id;
END//

DELIMITER ;

-- =====================================================
-- INSERTAR PLAN DE CUENTAS BÁSICO
-- =====================================================

INSERT INTO `plan_cuentas` (`empresa_id`, `codigo`, `nombre`, `tipo_cuenta`, `naturaleza`, `nivel`, `acepta_movimientos`) VALUES
(1, '1', 'ACTIVO', 'activo', 'deudora', 1, 0),
(1, '1.1', 'ACTIVO CIRCULANTE', 'activo', 'deudora', 2, 0),
(1, '1.1.1', 'Caja', 'activo', 'deudora', 3, 1),
(1, '1.1.2', 'Banco', 'activo', 'deudora', 3, 1),
(1, '1.1.3', 'Clientes', 'activo', 'deudora', 3, 1),
(1, '1.1.4', 'Inventario', 'activo', 'deudora', 3, 1),
(1, '1.2', 'ACTIVO FIJO', 'activo', 'deudora', 2, 0),
(1, '1.2.1', 'Muebles y Útiles', 'activo', 'deudora', 3, 1),
(1, '1.2.2', 'Equipos', 'activo', 'deudora', 3, 1),
(1, '1.2.3', 'Vehículos', 'activo', 'deudora', 3, 1),
(1, '2', 'PASIVO', 'pasivo', 'acreedora', 1, 0),
(1, '2.1', 'PASIVO CIRCULANTE', 'pasivo', 'acreedora', 2, 0),
(1, '2.1.1', 'Proveedores', 'pasivo', 'acreedora', 3, 1),
(1, '2.1.2', 'IVA por Pagar', 'pasivo', 'acreedora', 3, 1),
(1, '3', 'PATRIMONIO', 'patrimonio', 'acreedora', 1, 0),
(1, '3.1', 'Capital', 'patrimonio', 'acreedora', 2, 1),
(1, '4', 'INGRESOS', 'ingreso', 'acreedora', 1, 0),
(1, '4.1', 'Ventas', 'ingreso', 'acreedora', 2, 1),
(1, '5', 'EGRESOS', 'egreso', 'deudora', 1, 0),
(1, '5.1', 'Costo de Ventas', 'egreso', 'deudora', 2, 1),
(1, '5.2', 'Gastos Operacionales', 'egreso', 'deudora', 2, 1)
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);

FLUSH PRIVILEGES;
