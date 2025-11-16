-- =====================================================
-- MÓDULO 2: GESTIÓN DE ENTIDADES - CONECTA ERP
-- Tablas para Clientes, Proveedores, Empleados y Productos
-- =====================================================

USE conectae_conectaerpbd;

-- =====================================================
-- TABLA: clientes
-- Gestión de clientes (CLIE)
-- =====================================================
CREATE TABLE IF NOT EXISTS `clientes` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL COMMENT 'Multi-tenancy',
  `rut` VARCHAR(20) NOT NULL,
  `razon_social` VARCHAR(255) NOT NULL,
  `nombre_fantasia` VARCHAR(255) DEFAULT NULL,
  `giro` VARCHAR(255) DEFAULT NULL,
  `direccion` VARCHAR(255) DEFAULT NULL,
  `comuna` VARCHAR(100) DEFAULT NULL,
  `ciudad` VARCHAR(100) DEFAULT NULL,
  `region` VARCHAR(100) DEFAULT NULL,
  `pais_id` INT(11) DEFAULT 1,
  `telefono` VARCHAR(50) DEFAULT NULL,
  `email` VARCHAR(255) DEFAULT NULL,
  `sitio_web` VARCHAR(255) DEFAULT NULL,
  `contacto_nombre` VARCHAR(255) DEFAULT NULL,
  `contacto_cargo` VARCHAR(100) DEFAULT NULL,
  `contacto_telefono` VARCHAR(50) DEFAULT NULL,
  `contacto_email` VARCHAR(255) DEFAULT NULL,
  `clasificacion` ENUM('A','B','C') DEFAULT 'C' COMMENT 'Clasificación ABC',
  `limite_credito` DECIMAL(15,2) DEFAULT 0.00,
  `dias_credito` INT(11) DEFAULT 0,
  `descuento_defecto` DECIMAL(5,2) DEFAULT 0.00,
  `observaciones` TEXT DEFAULT NULL,
  `estado` ENUM('activo','inactivo','moroso','bloqueado') DEFAULT 'activo',
  `fecha_creacion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `creado_por` INT(11) DEFAULT NULL,
  `fecha_modificacion` TIMESTAMP NULL DEFAULT NULL,
  `modificado_por` INT(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `empresa_rut` (`empresa_id`, `rut`),
  KEY `idx_empresa` (`empresa_id`),
  KEY `idx_estado` (`estado`),
  KEY `idx_clasificacion` (`clasificacion`),
  KEY `idx_nombre` (`nombre_fantasia`),
  KEY `idx_email` (`email`),
  CONSTRAINT `fk_clientes_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: proveedores
-- Gestión de proveedores (PROV)
-- =====================================================
CREATE TABLE IF NOT EXISTS `proveedores` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL COMMENT 'Multi-tenancy',
  `rut` VARCHAR(20) NOT NULL,
  `razon_social` VARCHAR(255) NOT NULL,
  `nombre_fantasia` VARCHAR(255) DEFAULT NULL,
  `giro` VARCHAR(255) DEFAULT NULL,
  `direccion` VARCHAR(255) DEFAULT NULL,
  `comuna` VARCHAR(100) DEFAULT NULL,
  `ciudad` VARCHAR(100) DEFAULT NULL,
  `region` VARCHAR(100) DEFAULT NULL,
  `pais_id` INT(11) DEFAULT 1,
  `telefono` VARCHAR(50) DEFAULT NULL,
  `email` VARCHAR(255) DEFAULT NULL,
  `sitio_web` VARCHAR(255) DEFAULT NULL,
  `contacto_nombre` VARCHAR(255) DEFAULT NULL,
  `contacto_cargo` VARCHAR(100) DEFAULT NULL,
  `contacto_telefono` VARCHAR(50) DEFAULT NULL,
  `contacto_email` VARCHAR(255) DEFAULT NULL,
  `categoria` VARCHAR(100) DEFAULT NULL COMMENT 'Materias primas, servicios, etc.',
  `condicion_pago` VARCHAR(100) DEFAULT 'contado' COMMENT 'contado, credito_30, credito_60, etc.',
  `plazo_entrega_dias` INT(11) DEFAULT 0,
  `evaluacion` TINYINT(1) DEFAULT 3 COMMENT 'Evaluación 1-5 estrellas',
  `banco` VARCHAR(100) DEFAULT NULL,
  `tipo_cuenta` ENUM('corriente','vista','ahorro') DEFAULT NULL,
  `numero_cuenta` VARCHAR(50) DEFAULT NULL,
  `observaciones` TEXT DEFAULT NULL,
  `estado` ENUM('activo','inactivo','bloqueado') DEFAULT 'activo',
  `fecha_creacion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `creado_por` INT(11) DEFAULT NULL,
  `fecha_modificacion` TIMESTAMP NULL DEFAULT NULL,
  `modificado_por` INT(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `empresa_rut` (`empresa_id`, `rut`),
  KEY `idx_empresa` (`empresa_id`),
  KEY `idx_estado` (`estado`),
  KEY `idx_categoria` (`categoria`),
  KEY `idx_evaluacion` (`evaluacion`),
  CONSTRAINT `fk_proveedores_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: empleados
-- Gestión de empleados (EMPL)
-- =====================================================
CREATE TABLE IF NOT EXISTS `empleados` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL COMMENT 'Multi-tenancy',
  `rut` VARCHAR(20) NOT NULL,
  `nombre` VARCHAR(100) NOT NULL,
  `apellido` VARCHAR(100) NOT NULL,
  `email` VARCHAR(255) DEFAULT NULL,
  `telefono` VARCHAR(50) DEFAULT NULL,
  `celular` VARCHAR(50) DEFAULT NULL,
  `direccion` VARCHAR(255) DEFAULT NULL,
  `comuna` VARCHAR(100) DEFAULT NULL,
  `ciudad` VARCHAR(100) DEFAULT NULL,
  `region` VARCHAR(100) DEFAULT NULL,
  `fecha_nacimiento` DATE DEFAULT NULL,
  `genero` ENUM('masculino','femenino','otro','prefiero_no_decir') DEFAULT NULL,
  `estado_civil` ENUM('soltero','casado','divorciado','viudo','union_civil') DEFAULT NULL,
  `cargo` VARCHAR(150) DEFAULT NULL,
  `departamento` VARCHAR(150) DEFAULT NULL,
  `area` VARCHAR(100) DEFAULT NULL,
  `fecha_ingreso` DATE DEFAULT NULL,
  `fecha_termino` DATE DEFAULT NULL,
  `tipo_contrato` ENUM('indefinido','plazo_fijo','honorarios','practica') DEFAULT 'indefinido',
  `jornada` ENUM('completa','media','por_horas') DEFAULT 'completa',
  `salario_base` DECIMAL(15,2) DEFAULT 0.00,
  `banco` VARCHAR(100) DEFAULT NULL,
  `tipo_cuenta` ENUM('corriente','vista','ahorro') DEFAULT NULL,
  `numero_cuenta` VARCHAR(50) DEFAULT NULL,
  `afp` VARCHAR(100) DEFAULT NULL,
  `isapre_fonasa` VARCHAR(100) DEFAULT NULL,
  `contacto_emergencia_nombre` VARCHAR(255) DEFAULT NULL,
  `contacto_emergencia_telefono` VARCHAR(50) DEFAULT NULL,
  `contacto_emergencia_relacion` VARCHAR(100) DEFAULT NULL,
  `observaciones` TEXT DEFAULT NULL,
  `estado` ENUM('activo','inactivo','vacaciones','licencia','despedido') DEFAULT 'activo',
  `fecha_creacion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `creado_por` INT(11) DEFAULT NULL,
  `fecha_modificacion` TIMESTAMP NULL DEFAULT NULL,
  `modificado_por` INT(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `empresa_rut` (`empresa_id`, `rut`),
  KEY `idx_empresa` (`empresa_id`),
  KEY `idx_estado` (`estado`),
  KEY `idx_cargo` (`cargo`),
  KEY `idx_departamento` (`departamento`),
  KEY `idx_nombre` (`nombre`, `apellido`),
  CONSTRAINT `fk_empleados_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: productos
-- Gestión de productos y servicios (PROD)
-- =====================================================
CREATE TABLE IF NOT EXISTS `productos` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL COMMENT 'Multi-tenancy',
  `sku` VARCHAR(100) NOT NULL,
  `codigo_barra` VARCHAR(100) DEFAULT NULL,
  `nombre` VARCHAR(255) NOT NULL,
  `descripcion` TEXT DEFAULT NULL,
  `categoria_id` INT(11) DEFAULT NULL,
  `tipo` ENUM('producto','servicio') DEFAULT 'producto',
  `unidad_medida` VARCHAR(50) DEFAULT 'UND' COMMENT 'UND, KG, LT, M, etc.',
  `precio_compra` DECIMAL(15,2) DEFAULT 0.00,
  `precio_venta` DECIMAL(15,2) DEFAULT 0.00,
  `precio_oferta` DECIMAL(15,2) DEFAULT NULL,
  `costo_promedio` DECIMAL(15,2) DEFAULT 0.00,
  `margen_minimo` DECIMAL(5,2) DEFAULT 0.00,
  `impuesto_aplicable` TINYINT(1) DEFAULT 1 COMMENT 'Aplica IVA/impuesto',
  `porcentaje_impuesto` DECIMAL(5,2) DEFAULT 19.00,
  `stock_actual` DECIMAL(10,2) DEFAULT 0.00,
  `stock_minimo` DECIMAL(10,2) DEFAULT 0.00,
  `stock_maximo` DECIMAL(10,2) DEFAULT 0.00,
  `punto_reorden` DECIMAL(10,2) DEFAULT 0.00,
  `ubicacion_bodega` VARCHAR(100) DEFAULT NULL,
  `proveedor_principal_id` INT(11) DEFAULT NULL,
  `imagen` VARCHAR(255) DEFAULT NULL,
  `peso` DECIMAL(10,2) DEFAULT NULL COMMENT 'Peso en KG',
  `dimensiones` VARCHAR(100) DEFAULT NULL COMMENT 'Largo x Ancho x Alto en cm',
  `garantia_dias` INT(11) DEFAULT NULL,
  `observaciones` TEXT DEFAULT NULL,
  `estado` ENUM('activo','inactivo','descontinuado') DEFAULT 'activo',
  `fecha_creacion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `creado_por` INT(11) DEFAULT NULL,
  `fecha_modificacion` TIMESTAMP NULL DEFAULT NULL,
  `modificado_por` INT(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `empresa_sku` (`empresa_id`, `sku`),
  KEY `idx_empresa` (`empresa_id`),
  KEY `idx_estado` (`estado`),
  KEY `idx_tipo` (`tipo`),
  KEY `idx_categoria` (`categoria_id`),
  KEY `idx_nombre` (`nombre`),
  KEY `idx_codigo_barra` (`codigo_barra`),
  CONSTRAINT `fk_productos_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: categorias_productos
-- Categorías para clasificar productos
-- =====================================================
CREATE TABLE IF NOT EXISTS `categorias_productos` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `nombre` VARCHAR(150) NOT NULL,
  `descripcion` TEXT DEFAULT NULL,
  `categoria_padre_id` INT(11) DEFAULT NULL COMMENT 'Para jerarquía de categorías',
  `activo` TINYINT(1) DEFAULT 1,
  `fecha_creacion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_empresa` (`empresa_id`),
  KEY `idx_padre` (`categoria_padre_id`),
  CONSTRAINT `fk_categorias_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: movimientos_inventario
-- Registro de movimientos de inventario
-- =====================================================
CREATE TABLE IF NOT EXISTS `movimientos_inventario` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `producto_id` INT(11) NOT NULL,
  `tipo_movimiento` ENUM('entrada','salida','ajuste','transferencia','devolucion') NOT NULL,
  `cantidad` DECIMAL(10,2) NOT NULL,
  `cantidad_anterior` DECIMAL(10,2) DEFAULT 0.00,
  `cantidad_nueva` DECIMAL(10,2) DEFAULT 0.00,
  `costo_unitario` DECIMAL(15,2) DEFAULT 0.00,
  `motivo` VARCHAR(255) DEFAULT NULL,
  `documento_referencia` VARCHAR(100) DEFAULT NULL COMMENT 'Número de factura, guía, etc.',
  `usuario_id` INT(11) DEFAULT NULL,
  `fecha_movimiento` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_empresa` (`empresa_id`),
  KEY `idx_producto` (`producto_id`),
  KEY `idx_tipo` (`tipo_movimiento`),
  KEY `idx_fecha` (`fecha_movimiento`),
  CONSTRAINT `fk_movimientos_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_movimientos_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- INSERTAR CATEGORÍAS POR DEFECTO
-- =====================================================
INSERT INTO `categorias_productos` (`empresa_id`, `nombre`, `descripcion`) VALUES
(1, 'General', 'Categoría general para productos sin clasificar'),
(1, 'Materias Primas', 'Materiales y materias primas'),
(1, 'Productos Terminados', 'Productos terminados listos para venta'),
(1, 'Servicios', 'Servicios prestados'),
(1, 'Suministros', 'Suministros y materiales de oficina')
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);

-- =====================================================
-- VISTAS ÚTILES
-- =====================================================

-- Vista: Clientes con saldo pendiente
CREATE OR REPLACE VIEW `v_clientes_con_saldo` AS
SELECT
    c.id,
    c.empresa_id,
    c.rut,
    c.razon_social,
    c.clasificacion,
    c.limite_credito,
    COALESCE(SUM(CASE WHEN f.estado = 'pendiente' THEN f.total ELSE 0 END), 0) as saldo_pendiente,
    c.estado
FROM clientes c
LEFT JOIN facturas f ON c.id = f.cliente_id
GROUP BY c.id;

-- Vista: Productos con stock bajo
CREATE OR REPLACE VIEW `v_productos_stock_bajo` AS
SELECT
    p.id,
    p.empresa_id,
    p.sku,
    p.nombre,
    p.stock_actual,
    p.stock_minimo,
    p.punto_reorden,
    CASE
        WHEN p.stock_actual <= 0 THEN 'critico'
        WHEN p.stock_actual <= p.stock_minimo THEN 'bajo'
        WHEN p.stock_actual <= p.punto_reorden THEN 'reorden'
        ELSE 'normal'
    END as nivel_alerta
FROM productos p
WHERE p.estado = 'activo'
AND p.tipo = 'producto'
AND p.stock_actual <= p.punto_reorden;

-- =====================================================
-- TRIGGERS PARA AUDITORÍA
-- =====================================================

DELIMITER //

-- Trigger: productos - actualizar stock automáticamente
CREATE TRIGGER IF NOT EXISTS `tr_productos_movimiento_stock` AFTER INSERT ON `movimientos_inventario`
FOR EACH ROW
BEGIN
    DECLARE nuevo_stock DECIMAL(10,2);

    IF NEW.tipo_movimiento IN ('entrada', 'devolucion') THEN
        UPDATE productos
        SET stock_actual = stock_actual + NEW.cantidad
        WHERE id = NEW.producto_id;
    ELSEIF NEW.tipo_movimiento = 'salida' THEN
        UPDATE productos
        SET stock_actual = stock_actual - NEW.cantidad
        WHERE id = NEW.producto_id;
    ELSEIF NEW.tipo_movimiento = 'ajuste' THEN
        UPDATE productos
        SET stock_actual = NEW.cantidad_nueva
        WHERE id = NEW.producto_id;
    END IF;

    -- Actualizar costo promedio
    UPDATE productos p
    SET costo_promedio = (
        SELECT AVG(m.costo_unitario)
        FROM movimientos_inventario m
        WHERE m.producto_id = p.id
        AND m.tipo_movimiento = 'entrada'
        AND m.costo_unitario > 0
        ORDER BY m.fecha_movimiento DESC
        LIMIT 10
    )
    WHERE p.id = NEW.producto_id;
END//

-- Trigger: clientes - auditoría de creación
CREATE TRIGGER IF NOT EXISTS `tr_clientes_insert` AFTER INSERT ON `clientes`
FOR EACH ROW
BEGIN
    INSERT INTO logs_auditoria (usuario_id, empresa_id, accion, tabla, registro_id, modulo, descripcion, datos_nuevos, ip_address)
    VALUES (NEW.creado_por, NEW.empresa_id, 'crear', 'clientes', NEW.id, 'entidades',
            CONCAT('Cliente creado: ', NEW.razon_social),
            JSON_OBJECT('rut', NEW.rut, 'razon_social', NEW.razon_social, 'clasificacion', NEW.clasificacion),
            COALESCE(@client_ip, '127.0.0.1'));
END//

-- Trigger: proveedores - auditoría de creación
CREATE TRIGGER IF NOT EXISTS `tr_proveedores_insert` AFTER INSERT ON `proveedores`
FOR EACH ROW
BEGIN
    INSERT INTO logs_auditoria (usuario_id, empresa_id, accion, tabla, registro_id, modulo, descripcion, datos_nuevos, ip_address)
    VALUES (NEW.creado_por, NEW.empresa_id, 'crear', 'proveedores', NEW.id, 'entidades',
            CONCAT('Proveedor creado: ', NEW.razon_social),
            JSON_OBJECT('rut', NEW.rut, 'razon_social', NEW.razon_social, 'categoria', NEW.categoria),
            COALESCE(@client_ip, '127.0.0.1'));
END//

-- Trigger: empleados - auditoría de creación
CREATE TRIGGER IF NOT EXISTS `tr_empleados_insert` AFTER INSERT ON `empleados`
FOR EACH ROW
BEGIN
    INSERT INTO logs_auditoria (usuario_id, empresa_id, accion, tabla, registro_id, modulo, descripcion, datos_nuevos, ip_address)
    VALUES (NEW.creado_por, NEW.empresa_id, 'crear', 'empleados', NEW.id, 'entidades',
            CONCAT('Empleado creado: ', NEW.nombre, ' ', NEW.apellido),
            JSON_OBJECT('rut', NEW.rut, 'cargo', NEW.cargo, 'departamento', NEW.departamento),
            COALESCE(@client_ip, '127.0.0.1'));
END//

-- Trigger: productos - auditoría de creación
CREATE TRIGGER IF NOT EXISTS `tr_productos_insert` AFTER INSERT ON `productos`
FOR EACH ROW
BEGIN
    INSERT INTO logs_auditoria (usuario_id, empresa_id, accion, tabla, registro_id, modulo, descripcion, datos_nuevos, ip_address)
    VALUES (NEW.creado_por, NEW.empresa_id, 'crear', 'productos', NEW.id, 'entidades',
            CONCAT('Producto creado: ', NEW.nombre),
            JSON_OBJECT('sku', NEW.sku, 'nombre', NEW.nombre, 'precio_venta', NEW.precio_venta),
            COALESCE(@client_ip, '127.0.0.1'));
END//

DELIMITER ;

-- =====================================================
-- ÍNDICES ADICIONALES PARA OPTIMIZACIÓN
-- =====================================================

-- Búsquedas frecuentes
ALTER TABLE `clientes` ADD FULLTEXT INDEX `ft_razon_social` (`razon_social`, `nombre_fantasia`);
ALTER TABLE `proveedores` ADD FULLTEXT INDEX `ft_razon_social` (`razon_social`, `nombre_fantasia`);
ALTER TABLE `productos` ADD FULLTEXT INDEX `ft_nombre` (`nombre`, `descripcion`);

-- =====================================================
-- PERMISOS Y SEGURIDAD
-- =====================================================

FLUSH PRIVILEGES;
