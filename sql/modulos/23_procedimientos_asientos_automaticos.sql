-- ==================================================================
-- PROCEDIMIENTOS ALMACENADOS PARA ASIENTOS AUTOMÁTICOS
-- ==================================================================
-- Genera asientos contables automáticos desde ventas, compras, pagos
-- ==================================================================

USE `conectae_conectaerpbd`;

DELIMITER //

-- ==================================================================
-- PROCEDIMIENTO: Generar asiento automático desde factura de venta
-- ==================================================================
DROP PROCEDURE IF EXISTS sp_generar_asiento_venta//
CREATE PROCEDURE sp_generar_asiento_venta(
    IN p_factura_id INT,
    IN p_empresa_id INT
)
BEGIN
    DECLARE v_numero_asiento INT;
    DECLARE v_cliente_nombre VARCHAR(300);
    DECLARE v_folio INT;
    DECLARE v_total DECIMAL(15,2);
    DECLARE v_iva DECIMAL(15,2);
    DECLARE v_neto DECIMAL(15,2);
    DECLARE v_fecha DATE;
    DECLARE v_asiento_id INT;

    -- Obtener datos de la factura
    SELECT f.folio, f.total, f.iva, f.subtotal, f.fecha_emision, c.razon_social
    INTO v_folio, v_total, v_iva, v_neto, v_fecha, v_cliente_nombre
    FROM facturas f
    LEFT JOIN clientes c ON f.cliente_id = c.id
    WHERE f.id = p_factura_id;

    -- Obtener siguiente número de asiento
    SELECT COALESCE(MAX(numero_asiento), 0) + 1 INTO v_numero_asiento
    FROM asientos_contables
    WHERE empresa_id = p_empresa_id;

    -- Crear asiento contable
    INSERT INTO asientos_contables (
        empresa_id,
        numero_asiento,
        tipo_asiento,
        fecha,
        glosa,
        documento_origen,
        documento_origen_id,
        total_debe,
        total_haber,
        estado
    ) VALUES (
        p_empresa_id,
        v_numero_asiento,
        'automatico',
        v_fecha,
        CONCAT('Venta factura N° ', v_folio, ' - ', v_cliente_nombre),
        'factura',
        p_factura_id,
        v_total,
        v_total,
        'contabilizado'
    );

    SET v_asiento_id = LAST_INSERT_ID();

    -- Detalle: DEBE - Clientes por cobrar (cuenta 1301)
    INSERT INTO asientos_contables_detalle (asiento_id, cuenta_id, debe, haber, glosa, orden)
    SELECT v_asiento_id, id, v_total, 0, CONCAT('Cliente ', v_cliente_nombre), 1
    FROM plan_cuentas
    WHERE empresa_id = p_empresa_id AND codigo = '1301' LIMIT 1;

    -- Detalle: HABER - Ventas (cuenta 4101)
    INSERT INTO asientos_contables_detalle (asiento_id, cuenta_id, debe, haber, glosa, orden)
    SELECT v_asiento_id, id, 0, v_neto, 'Venta neta', 2
    FROM plan_cuentas
    WHERE empresa_id = p_empresa_id AND codigo = '4101' LIMIT 1;

    -- Detalle: HABER - IVA Débito Fiscal (cuenta 2201)
    INSERT INTO asientos_contables_detalle (asiento_id, cuenta_id, debe, haber, glosa, orden)
    SELECT v_asiento_id, id, 0, v_iva, 'IVA ventas', 3
    FROM plan_cuentas
    WHERE empresa_id = p_empresa_id AND codigo = '2201' LIMIT 1;

END//

-- ==================================================================
-- PROCEDIMIENTO: Generar asiento automático desde factura de compra
-- ==================================================================
DROP PROCEDURE IF EXISTS sp_generar_asiento_compra//
CREATE PROCEDURE sp_generar_asiento_compra(
    IN p_factura_compra_id INT,
    IN p_empresa_id INT
)
BEGIN
    DECLARE v_numero_asiento INT;
    DECLARE v_proveedor_nombre VARCHAR(300);
    DECLARE v_folio INT;
    DECLARE v_total DECIMAL(15,2);
    DECLARE v_iva DECIMAL(15,2);
    DECLARE v_neto DECIMAL(15,2);
    DECLARE v_fecha DATE;
    DECLARE v_asiento_id INT;

    -- Obtener datos de la factura de compra
    SELECT fc.folio, fc.total, fc.iva, fc.subtotal, fc.fecha_recepcion, p.razon_social
    INTO v_folio, v_total, v_iva, v_neto, v_fecha, v_proveedor_nombre
    FROM facturas_compra fc
    LEFT JOIN proveedores p ON fc.proveedor_id = p.id
    WHERE fc.id = p_factura_compra_id;

    -- Obtener siguiente número de asiento
    SELECT COALESCE(MAX(numero_asiento), 0) + 1 INTO v_numero_asiento
    FROM asientos_contables
    WHERE empresa_id = p_empresa_id;

    -- Crear asiento contable
    INSERT INTO asientos_contables (
        empresa_id,
        numero_asiento,
        tipo_asiento,
        fecha,
        glosa,
        documento_origen,
        documento_origen_id,
        total_debe,
        total_haber,
        estado
    ) VALUES (
        p_empresa_id,
        v_numero_asiento,
        'automatico',
        v_fecha,
        CONCAT('Compra factura N° ', v_folio, ' - ', v_proveedor_nombre),
        'factura_compra',
        p_factura_compra_id,
        v_total,
        v_total,
        'contabilizado'
    );

    SET v_asiento_id = LAST_INSERT_ID();

    -- Detalle: DEBE - Compras (cuenta 5101)
    INSERT INTO asientos_contables_detalle (asiento_id, cuenta_id, debe, haber, glosa, orden)
    SELECT v_asiento_id, id, v_neto, 0, 'Compra neta', 1
    FROM plan_cuentas
    WHERE empresa_id = p_empresa_id AND codigo = '5101' LIMIT 1;

    -- Detalle: DEBE - IVA Crédito Fiscal (cuenta 1402)
    INSERT INTO asientos_contables_detalle (asiento_id, cuenta_id, debe, haber, glosa, orden)
    SELECT v_asiento_id, id, v_iva, 0, 'IVA compras', 2
    FROM plan_cuentas
    WHERE empresa_id = p_empresa_id AND codigo = '1402' LIMIT 1;

    -- Detalle: HABER - Proveedores por pagar (cuenta 2101)
    INSERT INTO asientos_contables_detalle (asiento_id, cuenta_id, debe, haber, glosa, orden)
    SELECT v_asiento_id, id, 0, v_total, CONCAT('Proveedor ', v_proveedor_nombre), 3
    FROM plan_cuentas
    WHERE empresa_id = p_empresa_id AND codigo = '2101' LIMIT 1;

END//

-- ==================================================================
-- PROCEDIMIENTO: Generar libro de ventas automático
-- ==================================================================
DROP PROCEDURE IF EXISTS sp_generar_libro_ventas//
CREATE PROCEDURE sp_generar_libro_ventas(
    IN p_empresa_id INT,
    IN p_periodo VARCHAR(7)
)
BEGIN
    -- Limpiar libro de ventas del período
    DELETE FROM libro_ventas
    WHERE empresa_id = p_empresa_id
    AND DATE_FORMAT(fecha_emision, '%Y-%m') = p_periodo;

    -- Insertar desde facturas
    INSERT INTO libro_ventas (
        empresa_id,
        tipo_documento,
        folio,
        fecha_emision,
        rut_cliente,
        razon_social_cliente,
        exento,
        neto,
        iva,
        total
    )
    SELECT
        f.empresa_id,
        f.tipo_documento,
        f.folio,
        f.fecha_emision,
        c.rut,
        c.razon_social,
        0, -- exento
        f.subtotal,
        f.iva,
        f.total
    FROM facturas f
    LEFT JOIN clientes c ON f.cliente_id = c.id
    WHERE f.empresa_id = p_empresa_id
    AND DATE_FORMAT(f.fecha_emision, '%Y-%m') = p_periodo
    AND f.estado NOT IN ('anulada', 'rechazada');

END//

-- ==================================================================
-- PROCEDIMIENTO: Generar libro de compras automático
-- ==================================================================
DROP PROCEDURE IF EXISTS sp_generar_libro_compras//
CREATE PROCEDURE sp_generar_libro_compras(
    IN p_empresa_id INT,
    IN p_periodo VARCHAR(7)
)
BEGIN
    -- Limpiar libro de compras del período
    DELETE FROM libro_compras
    WHERE empresa_id = p_empresa_id
    AND DATE_FORMAT(fecha_emision, '%Y-%m') = p_periodo;

    -- Insertar desde facturas de compra
    INSERT INTO libro_compras (
        empresa_id,
        tipo_documento,
        folio,
        fecha_emision,
        rut_proveedor,
        razon_social_proveedor,
        exento,
        neto,
        iva,
        total
    )
    SELECT
        fc.empresa_id,
        fc.tipo_documento,
        fc.folio,
        fc.fecha_recepcion,
        p.rut,
        p.razon_social,
        0, -- exento
        fc.subtotal,
        fc.iva,
        fc.total
    FROM facturas_compra fc
    LEFT JOIN proveedores p ON fc.proveedor_id = p.id
    WHERE fc.empresa_id = p_empresa_id
    AND DATE_FORMAT(fc.fecha_recepcion, '%Y-%m') = p_periodo;

END//

DELIMITER ;

-- ==================================================================
-- FIN PROCEDIMIENTOS ALMACENADOS
-- ==================================================================
