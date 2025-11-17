-- ==================================================================
-- CONECTA ERP - TABLAS COMPLEMENTARIAS MÓDULOS
-- ==================================================================
-- Tablas adicionales necesarias para completar los módulos
-- Ventas, Compras, Inventario, Contabilidad, Reportes
-- ==================================================================

-- ==================================================================
-- MÓDULO: VENTAS
-- ==================================================================

-- Cotizaciones
CREATE TABLE IF NOT EXISTS `cotizaciones` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `numero_cotizacion` VARCHAR(50) NOT NULL,
  `cliente_id` INT(11) NOT NULL,
  `fecha_emision` DATE NOT NULL,
  `fecha_vencimiento` DATE,
  `estado` ENUM('borrador','enviada','aprobada','rechazada','convertida') DEFAULT 'borrador',
  `subtotal` DECIMAL(15,2) DEFAULT 0,
  `descuento` DECIMAL(15,2) DEFAULT 0,
  `impuestos` DECIMAL(15,2) DEFAULT 0,
  `total` DECIMAL(15,2) DEFAULT 0,
  `observaciones` TEXT,
  `condiciones` TEXT,
  `usuario_creador_id` INT(11),
  `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_empresa` (`empresa_id`),
  INDEX `idx_cliente` (`cliente_id`),
  INDEX `idx_numero` (`numero_cotizacion`),
  INDEX `idx_estado` (`estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Detalle de Cotizaciones
CREATE TABLE IF NOT EXISTS `cotizaciones_detalle` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `cotizacion_id` INT(11) NOT NULL,
  `producto_id` INT(11),
  `descripcion` VARCHAR(500) NOT NULL,
  `cantidad` DECIMAL(10,3) DEFAULT 0,
  `precio_unitario` DECIMAL(15,2) DEFAULT 0,
  `descuento_porcentaje` DECIMAL(5,2) DEFAULT 0,
  `descuento_monto` DECIMAL(15,2) DEFAULT 0,
  `subtotal` DECIMAL(15,2) GENERATED ALWAYS AS ((cantidad * precio_unitario) - descuento_monto) STORED,
  `orden` INT(3) DEFAULT 0,
  PRIMARY KEY (`id`),
  INDEX `idx_cotizacion` (`cotizacion_id`),
  INDEX `idx_producto` (`producto_id`),
  FOREIGN KEY (`cotizacion_id`) REFERENCES `cotizaciones`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Pedidos (Órdenes de Venta)
CREATE TABLE IF NOT EXISTS `pedidos` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `numero_pedido` VARCHAR(50) NOT NULL,
  `cliente_id` INT(11) NOT NULL,
  `cotizacion_id` INT(11),
  `fecha_pedido` DATE NOT NULL,
  `fecha_entrega_estimada` DATE,
  `fecha_entrega_real` DATE,
  `estado` ENUM('pendiente','en_preparacion','listo','entregado','cancelado') DEFAULT 'pendiente',
  `prioridad` ENUM('baja','normal','alta','urgente') DEFAULT 'normal',
  `subtotal` DECIMAL(15,2) DEFAULT 0,
  `descuento` DECIMAL(15,2) DEFAULT 0,
  `impuestos` DECIMAL(15,2) DEFAULT 0,
  `total` DECIMAL(15,2) DEFAULT 0,
  `direccion_entrega` VARCHAR(500),
  `observaciones` TEXT,
  `usuario_creador_id` INT(11),
  `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_empresa` (`empresa_id`),
  INDEX `idx_cliente` (`cliente_id`),
  INDEX `idx_numero` (`numero_pedido`),
  INDEX `idx_estado` (`estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Detalle de Pedidos
CREATE TABLE IF NOT EXISTS `pedidos_detalle` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `pedido_id` INT(11) NOT NULL,
  `producto_id` INT(11),
  `descripcion` VARCHAR(500) NOT NULL,
  `cantidad` DECIMAL(10,3) DEFAULT 0,
  `cantidad_entregada` DECIMAL(10,3) DEFAULT 0,
  `precio_unitario` DECIMAL(15,2) DEFAULT 0,
  `descuento_porcentaje` DECIMAL(5,2) DEFAULT 0,
  `descuento_monto` DECIMAL(15,2) DEFAULT 0,
  `subtotal` DECIMAL(15,2) GENERATED ALWAYS AS ((cantidad * precio_unitario) - descuento_monto) STORED,
  `orden` INT(3) DEFAULT 0,
  PRIMARY KEY (`id`),
  INDEX `idx_pedido` (`pedido_id`),
  INDEX `idx_producto` (`producto_id`),
  FOREIGN KEY (`pedido_id`) REFERENCES `pedidos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Guías de Despacho
CREATE TABLE IF NOT EXISTS `guias_despacho` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `tipo_documento` INT(3) DEFAULT 52 COMMENT 'Código SII',
  `folio` INT(11),
  `pedido_id` INT(11),
  `cliente_id` INT(11) NOT NULL,
  `fecha_emision` DATE NOT NULL,
  `fecha_traslado` DATE,
  `tipo_traslado` ENUM('venta','traslado_interno','consignacion','devolucion') DEFAULT 'venta',
  `direccion_destino` VARCHAR(500),
  `transportista` VARCHAR(200),
  `patente_vehiculo` VARCHAR(20),
  `estado` ENUM('emitida','en_transito','entregada','cancelada') DEFAULT 'emitida',
  `observaciones` TEXT,
  `dte_xml` LONGTEXT,
  `ted` TEXT,
  `track_id` VARCHAR(50),
  `estado_sii` ENUM('enviado','aceptado','rechazado','no_enviado') DEFAULT 'no_enviado',
  `usuario_creador_id` INT(11),
  `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_empresa` (`empresa_id`),
  INDEX `idx_folio` (`folio`),
  INDEX `idx_pedido` (`pedido_id`),
  INDEX `idx_cliente` (`cliente_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Detalle Guías de Despacho
CREATE TABLE IF NOT EXISTS `guias_despacho_detalle` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `guia_id` INT(11) NOT NULL,
  `producto_id` INT(11),
  `descripcion` VARCHAR(500) NOT NULL,
  `cantidad` DECIMAL(10,3) DEFAULT 0,
  `unidad_medida` VARCHAR(10) DEFAULT 'UN',
  `orden` INT(3) DEFAULT 0,
  PRIMARY KEY (`id`),
  INDEX `idx_guia` (`guia_id`),
  INDEX `idx_producto` (`producto_id`),
  FOREIGN KEY (`guia_id`) REFERENCES `guias_despacho`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Notas de Crédito
CREATE TABLE IF NOT EXISTS `notas_credito` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `tipo_documento` INT(3) DEFAULT 61 COMMENT 'Código SII',
  `folio` INT(11),
  `factura_referencia_id` INT(11),
  `tipo_doc_referencia` INT(3),
  `folio_referencia` INT(11),
  `fecha_referencia` DATE,
  `cliente_id` INT(11) NOT NULL,
  `fecha_emision` DATE NOT NULL,
  `motivo` ENUM('devolucion','descuento','correccion','anulacion','otro') DEFAULT 'devolucion',
  `descripcion_motivo` VARCHAR(500),
  `subtotal` DECIMAL(15,2) DEFAULT 0,
  `iva` DECIMAL(15,2) DEFAULT 0,
  `total` DECIMAL(15,2) DEFAULT 0,
  `estado` ENUM('emitida','aplicada','anulada') DEFAULT 'emitida',
  `dte_xml` LONGTEXT,
  `ted` TEXT,
  `track_id` VARCHAR(50),
  `estado_sii` ENUM('enviado','aceptado','rechazado','no_enviado') DEFAULT 'no_enviado',
  `usuario_creador_id` INT(11),
  `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_empresa` (`empresa_id`),
  INDEX `idx_folio` (`folio`),
  INDEX `idx_factura_ref` (`factura_referencia_id`),
  INDEX `idx_cliente` (`cliente_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Detalle Notas de Crédito
CREATE TABLE IF NOT EXISTS `notas_credito_detalle` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `nota_credito_id` INT(11) NOT NULL,
  `producto_id` INT(11),
  `descripcion` VARCHAR(500) NOT NULL,
  `cantidad` DECIMAL(10,3) DEFAULT 0,
  `precio_unitario` DECIMAL(15,2) DEFAULT 0,
  `descuento` DECIMAL(15,2) DEFAULT 0,
  `subtotal` DECIMAL(15,2) DEFAULT 0,
  `orden` INT(3) DEFAULT 0,
  PRIMARY KEY (`id`),
  INDEX `idx_nota_credito` (`nota_credito_id`),
  INDEX `idx_producto` (`producto_id`),
  FOREIGN KEY (`nota_credito_id`) REFERENCES `notas_credito`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==================================================================
-- MÓDULO: COMPRAS
-- ==================================================================

-- Órdenes de Compra
CREATE TABLE IF NOT EXISTS `ordenes_compra` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `numero_orden` VARCHAR(50) NOT NULL,
  `proveedor_id` INT(11) NOT NULL,
  `fecha_emision` DATE NOT NULL,
  `fecha_entrega_esperada` DATE,
  `fecha_entrega_real` DATE,
  `estado` ENUM('borrador','enviada','confirmada','recibida_parcial','recibida_total','cancelada') DEFAULT 'borrador',
  `subtotal` DECIMAL(15,2) DEFAULT 0,
  `impuestos` DECIMAL(15,2) DEFAULT 0,
  `total` DECIMAL(15,2) DEFAULT 0,
  `condiciones_pago` VARCHAR(200),
  `direccion_entrega` VARCHAR(500),
  `observaciones` TEXT,
  `usuario_creador_id` INT(11),
  `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_empresa` (`empresa_id`),
  INDEX `idx_proveedor` (`proveedor_id`),
  INDEX `idx_numero` (`numero_orden`),
  INDEX `idx_estado` (`estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Detalle Órdenes de Compra
CREATE TABLE IF NOT EXISTS `ordenes_compra_detalle` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `orden_compra_id` INT(11) NOT NULL,
  `producto_id` INT(11),
  `descripcion` VARCHAR(500) NOT NULL,
  `cantidad_solicitada` DECIMAL(10,3) DEFAULT 0,
  `cantidad_recibida` DECIMAL(10,3) DEFAULT 0,
  `precio_unitario` DECIMAL(15,2) DEFAULT 0,
  `descuento` DECIMAL(15,2) DEFAULT 0,
  `subtotal` DECIMAL(15,2) GENERATED ALWAYS AS ((cantidad_solicitada * precio_unitario) - descuento) STORED,
  `orden` INT(3) DEFAULT 0,
  PRIMARY KEY (`id`),
  INDEX `idx_orden` (`orden_compra_id`),
  INDEX `idx_producto` (`producto_id`),
  FOREIGN KEY (`orden_compra_id`) REFERENCES `ordenes_compra`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Facturas de Compra (Recepción)
CREATE TABLE IF NOT EXISTS `facturas_compra` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `orden_compra_id` INT(11),
  `proveedor_id` INT(11) NOT NULL,
  `tipo_documento` INT(3) DEFAULT 33 COMMENT 'Código SII',
  `folio` INT(11) NOT NULL,
  `fecha_emision` DATE NOT NULL,
  `fecha_recepcion` DATE NOT NULL,
  `fecha_vencimiento` DATE,
  `estado_pago` ENUM('pendiente','pagada_parcial','pagada_total','vencida') DEFAULT 'pendiente',
  `subtotal` DECIMAL(15,2) DEFAULT 0,
  `iva` DECIMAL(15,2) DEFAULT 0,
  `total` DECIMAL(15,2) DEFAULT 0,
  `monto_pagado` DECIMAL(15,2) DEFAULT 0,
  `saldo` DECIMAL(15,2) GENERATED ALWAYS AS (total - monto_pagado) STORED,
  `observaciones` TEXT,
  `archivo_pdf` VARCHAR(500),
  `archivo_xml` VARCHAR(500),
  `usuario_creador_id` INT(11),
  `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_empresa` (`empresa_id`),
  INDEX `idx_proveedor` (`proveedor_id`),
  INDEX `idx_orden` (`orden_compra_id`),
  INDEX `idx_estado` (`estado_pago`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Detalle Facturas de Compra
CREATE TABLE IF NOT EXISTS `facturas_compra_detalle` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `factura_compra_id` INT(11) NOT NULL,
  `producto_id` INT(11),
  `descripcion` VARCHAR(500) NOT NULL,
  `cantidad` DECIMAL(10,3) DEFAULT 0,
  `precio_unitario` DECIMAL(15,2) DEFAULT 0,
  `descuento` DECIMAL(15,2) DEFAULT 0,
  `subtotal` DECIMAL(15,2) DEFAULT 0,
  `orden` INT(3) DEFAULT 0,
  PRIMARY KEY (`id`),
  INDEX `idx_factura` (`factura_compra_id`),
  INDEX `idx_producto` (`producto_id`),
  FOREIGN KEY (`factura_compra_id`) REFERENCES `facturas_compra`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==================================================================
-- MÓDULO: INVENTARIO / PRODUCTOS
-- ==================================================================

-- Categorías de Productos
CREATE TABLE IF NOT EXISTS `categorias_productos` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `nombre` VARCHAR(200) NOT NULL,
  `descripcion` TEXT,
  `categoria_padre_id` INT(11),
  `orden` INT(3) DEFAULT 0,
  `activo` TINYINT(1) DEFAULT 1,
  `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_empresa` (`empresa_id`),
  INDEX `idx_padre` (`categoria_padre_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Unidades de Medida
CREATE TABLE IF NOT EXISTS `unidades_medida` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `codigo` VARCHAR(10) NOT NULL,
  `nombre` VARCHAR(100) NOT NULL,
  `tipo` ENUM('longitud','peso','volumen','unidad','tiempo','otro') DEFAULT 'unidad',
  `activo` TINYINT(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_codigo` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Productos/Servicios (si no existe)
CREATE TABLE IF NOT EXISTS `productos` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `codigo` VARCHAR(50) NOT NULL,
  `codigo_barras` VARCHAR(100),
  `nombre` VARCHAR(300) NOT NULL,
  `descripcion` TEXT,
  `tipo` ENUM('producto','servicio','combo') DEFAULT 'producto',
  `categoria_id` INT(11),
  `unidad_medida_id` INT(11),
  `precio_compra` DECIMAL(15,2) DEFAULT 0,
  `precio_venta` DECIMAL(15,2) DEFAULT 0,
  `precio_minimo` DECIMAL(15,2) DEFAULT 0,
  `margen_porcentaje` DECIMAL(5,2) GENERATED ALWAYS AS (((precio_venta - precio_compra) / precio_compra) * 100) STORED,
  `iva_incluido` TINYINT(1) DEFAULT 1,
  `afecto_iva` TINYINT(1) DEFAULT 1,
  `stock_actual` DECIMAL(10,3) DEFAULT 0,
  `stock_minimo` DECIMAL(10,3) DEFAULT 0,
  `stock_maximo` DECIMAL(10,3) DEFAULT 0,
  `permite_stock_negativo` TINYINT(1) DEFAULT 0,
  `imagen` VARCHAR(500),
  `activo` TINYINT(1) DEFAULT 1,
  `usuario_creador_id` INT(11),
  `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_codigo` (`empresa_id`, `codigo`),
  INDEX `idx_empresa` (`empresa_id`),
  INDEX `idx_categoria` (`categoria_id`),
  INDEX `idx_codigo_barras` (`codigo_barras`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Almacenes/Bodegas
CREATE TABLE IF NOT EXISTS `almacenes` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `codigo` VARCHAR(20) NOT NULL,
  `nombre` VARCHAR(200) NOT NULL,
  `descripcion` TEXT,
  `direccion` VARCHAR(500),
  `responsable_id` INT(11),
  `tipo` ENUM('principal','secundario','transito','virtual') DEFAULT 'principal',
  `activo` TINYINT(1) DEFAULT 1,
  `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_codigo` (`empresa_id`, `codigo`),
  INDEX `idx_empresa` (`empresa_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Stock por Almacén
CREATE TABLE IF NOT EXISTS `stock_almacen` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `almacen_id` INT(11) NOT NULL,
  `producto_id` INT(11) NOT NULL,
  `cantidad` DECIMAL(10,3) DEFAULT 0,
  `cantidad_reservada` DECIMAL(10,3) DEFAULT 0,
  `cantidad_disponible` DECIMAL(10,3) GENERATED ALWAYS AS (cantidad - cantidad_reservada) STORED,
  `ubicacion` VARCHAR(100),
  `fecha_ultima_actualizacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_almacen_producto` (`almacen_id`, `producto_id`),
  INDEX `idx_almacen` (`almacen_id`),
  INDEX `idx_producto` (`producto_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Movimientos de Inventario
CREATE TABLE IF NOT EXISTS `movimientos_inventario` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `tipo_movimiento` ENUM('entrada','salida','ajuste','transferencia','devolucion') NOT NULL,
  `almacen_origen_id` INT(11),
  `almacen_destino_id` INT(11),
  `producto_id` INT(11) NOT NULL,
  `cantidad` DECIMAL(10,3) NOT NULL,
  `costo_unitario` DECIMAL(15,2),
  `motivo` VARCHAR(500),
  `documento_referencia` VARCHAR(100),
  `documento_referencia_id` INT(11),
  `usuario_id` INT(11),
  `fecha_movimiento` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_empresa` (`empresa_id`),
  INDEX `idx_producto` (`producto_id`),
  INDEX `idx_tipo` (`tipo_movimiento`),
  INDEX `idx_fecha` (`fecha_movimiento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==================================================================
-- MÓDULO: CONTABILIDAD
-- ==================================================================

-- Plan de Cuentas Contables
CREATE TABLE IF NOT EXISTS `plan_cuentas` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `codigo` VARCHAR(20) NOT NULL,
  `nombre` VARCHAR(300) NOT NULL,
  `tipo_cuenta` ENUM('activo','pasivo','patrimonio','ingreso','gasto','resultado') NOT NULL,
  `nivel` INT(2) DEFAULT 1,
  `cuenta_padre_id` INT(11),
  `acepta_movimiento` TINYINT(1) DEFAULT 1 COMMENT 'Si puede recibir asientos directos',
  `tipo_balance` ENUM('general','ifrs','ambos') DEFAULT 'general',
  `requiere_cc` TINYINT(1) DEFAULT 0 COMMENT 'Requiere centro de costo',
  `requiere_aux` TINYINT(1) DEFAULT 0 COMMENT 'Requiere auxiliar (cliente/proveedor)',
  `naturaleza` ENUM('deudora','acreedora') NOT NULL,
  `saldo_actual` DECIMAL(15,2) DEFAULT 0,
  `activo` TINYINT(1) DEFAULT 1,
  `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_codigo` (`empresa_id`, `codigo`),
  INDEX `idx_empresa` (`empresa_id`),
  INDEX `idx_tipo` (`tipo_cuenta`),
  INDEX `idx_padre` (`cuenta_padre_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Centros de Costo
CREATE TABLE IF NOT EXISTS `centros_costo` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `codigo` VARCHAR(20) NOT NULL,
  `nombre` VARCHAR(200) NOT NULL,
  `descripcion` TEXT,
  `centro_padre_id` INT(11),
  `responsable_id` INT(11),
  `activo` TINYINT(1) DEFAULT 1,
  `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_codigo` (`empresa_id`, `codigo`),
  INDEX `idx_empresa` (`empresa_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Asientos Contables
CREATE TABLE IF NOT EXISTS `asientos_contables` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `periodo_id` INT(11),
  `numero_asiento` INT(11) NOT NULL,
  `tipo_asiento` ENUM('manual','automatico','ajuste','apertura','cierre') DEFAULT 'manual',
  `fecha` DATE NOT NULL,
  `glosa` VARCHAR(500) NOT NULL,
  `documento_origen` VARCHAR(100) COMMENT 'factura, boleta, pago, etc',
  `documento_origen_id` INT(11),
  `total_debe` DECIMAL(15,2) DEFAULT 0,
  `total_haber` DECIMAL(15,2) DEFAULT 0,
  `cuadrado` TINYINT(1) GENERATED ALWAYS AS (total_debe = total_haber) STORED,
  `estado` ENUM('borrador','contabilizado','anulado') DEFAULT 'borrador',
  `usuario_creador_id` INT(11),
  `fecha_contabilizacion` TIMESTAMP NULL,
  `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_empresa` (`empresa_id`),
  INDEX `idx_numero` (`numero_asiento`),
  INDEX `idx_fecha` (`fecha`),
  INDEX `idx_estado` (`estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Detalle Asientos Contables
CREATE TABLE IF NOT EXISTS `asientos_contables_detalle` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `asiento_id` INT(11) NOT NULL,
  `cuenta_id` INT(11) NOT NULL,
  `centro_costo_id` INT(11),
  `glosa` VARCHAR(500),
  `debe` DECIMAL(15,2) DEFAULT 0,
  `haber` DECIMAL(15,2) DEFAULT 0,
  `auxiliar_tipo` ENUM('cliente','proveedor','empleado','otro'),
  `auxiliar_id` INT(11),
  `orden` INT(3) DEFAULT 0,
  PRIMARY KEY (`id`),
  INDEX `idx_asiento` (`asiento_id`),
  INDEX `idx_cuenta` (`cuenta_id`),
  INDEX `idx_cc` (`centro_costo_id`),
  FOREIGN KEY (`asiento_id`) REFERENCES `asientos_contables`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`cuenta_id`) REFERENCES `plan_cuentas`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Libro Mayor
CREATE TABLE IF NOT EXISTS `libro_mayor` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `periodo_id` INT(11),
  `cuenta_id` INT(11) NOT NULL,
  `fecha` DATE NOT NULL,
  `asiento_id` INT(11),
  `documento` VARCHAR(100),
  `glosa` VARCHAR(500),
  `debe` DECIMAL(15,2) DEFAULT 0,
  `haber` DECIMAL(15,2) DEFAULT 0,
  `saldo` DECIMAL(15,2) DEFAULT 0,
  PRIMARY KEY (`id`),
  INDEX `idx_empresa` (`empresa_id`),
  INDEX `idx_cuenta` (`cuenta_id`),
  INDEX `idx_fecha` (`fecha`),
  INDEX `idx_asiento` (`asiento_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Balances
CREATE TABLE IF NOT EXISTS `balances` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `tipo_balance` ENUM('8_columnas','general','clasificado','ifrs','comparativo') NOT NULL,
  `periodo_inicio` VARCHAR(7) COMMENT 'YYYY-MM',
  `periodo_fin` VARCHAR(7) COMMENT 'YYYY-MM',
  `fecha_generacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `estado` ENUM('generado','aprobado','publicado') DEFAULT 'generado',
  `archivo_pdf` VARCHAR(500),
  `datos_json` LONGTEXT COMMENT 'Balance completo en JSON',
  `usuario_generador_id` INT(11),
  PRIMARY KEY (`id`),
  INDEX `idx_empresa` (`empresa_id`),
  INDEX `idx_tipo` (`tipo_balance`),
  INDEX `idx_periodo` (`periodo_fin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==================================================================
-- MÓDULO: TESORERÍA
-- ==================================================================

-- Cuentas Bancarias
CREATE TABLE IF NOT EXISTS `cuentas_bancarias` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `banco` VARCHAR(200) NOT NULL,
  `tipo_cuenta` ENUM('corriente','vista','ahorro','linea_credito') DEFAULT 'corriente',
  `numero_cuenta` VARCHAR(50) NOT NULL,
  `moneda` VARCHAR(3) DEFAULT 'CLP',
  `saldo_actual` DECIMAL(15,2) DEFAULT 0,
  `saldo_libro` DECIMAL(15,2) DEFAULT 0,
  `cuenta_contable_id` INT(11) COMMENT 'Cuenta en plan de cuentas',
  `activo` TINYINT(1) DEFAULT 1,
  `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_empresa` (`empresa_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Movimientos Bancarios
CREATE TABLE IF NOT EXISTS `movimientos_bancarios` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `cuenta_bancaria_id` INT(11) NOT NULL,
  `tipo_movimiento` ENUM('deposito','retiro','transferencia','comision','interes','ajuste') NOT NULL,
  `fecha` DATE NOT NULL,
  `documento` VARCHAR(100),
  `descripcion` VARCHAR(500),
  `monto` DECIMAL(15,2) NOT NULL,
  `saldo_despues` DECIMAL(15,2),
  `conciliado` TINYINT(1) DEFAULT 0,
  `fecha_conciliacion` DATE,
  `asiento_id` INT(11),
  `usuario_id` INT(11),
  `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_cuenta` (`cuenta_bancaria_id`),
  INDEX `idx_fecha` (`fecha`),
  INDEX `idx_conciliado` (`conciliado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Pagos
CREATE TABLE IF NOT EXISTS `pagos_proveedores` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `proveedor_id` INT(11) NOT NULL,
  `factura_compra_id` INT(11),
  `tipo_pago` ENUM('efectivo','transferencia','cheque','tarjeta','otro') DEFAULT 'transferencia',
  `numero_documento` VARCHAR(50),
  `fecha_pago` DATE NOT NULL,
  `monto` DECIMAL(15,2) NOT NULL,
  `cuenta_bancaria_id` INT(11),
  `estado` ENUM('emitido','entregado','cobrado','anulado') DEFAULT 'emitido',
  `observaciones` TEXT,
  `asiento_id` INT(11),
  `usuario_id` INT(11),
  `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_empresa` (`empresa_id`),
  INDEX `idx_proveedor` (`proveedor_id`),
  INDEX `idx_factura` (`factura_compra_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Cobranzas
CREATE TABLE IF NOT EXISTS `cobranzas` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `cliente_id` INT(11) NOT NULL,
  `factura_id` INT(11),
  `tipo_pago` ENUM('efectivo','transferencia','cheque','tarjeta','otro') DEFAULT 'transferencia',
  `numero_documento` VARCHAR(50),
  `fecha_pago` DATE NOT NULL,
  `monto` DECIMAL(15,2) NOT NULL,
  `cuenta_bancaria_id` INT(11),
  `estado` ENUM('recibido','procesado','rechazado') DEFAULT 'recibido',
  `observaciones` TEXT,
  `asiento_id` INT(11),
  `usuario_id` INT(11),
  `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_empresa` (`empresa_id`),
  INDEX `idx_cliente` (`cliente_id`),
  INDEX `idx_factura` (`factura_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==================================================================
-- DATOS INICIALES
-- ==================================================================

-- Unidades de Medida por defecto
INSERT INTO `unidades_medida` (`codigo`, `nombre`, `tipo`) VALUES
('UN', 'Unidad', 'unidad'),
('KG', 'Kilogramo', 'peso'),
('GR', 'Gramo', 'peso'),
('LT', 'Litro', 'volumen'),
('ML', 'Mililitro', 'volumen'),
('MT', 'Metro', 'longitud'),
('CM', 'Centímetro', 'longitud'),
('CJ', 'Caja', 'unidad'),
('PQ', 'Paquete', 'unidad'),
('HR', 'Hora', 'tiempo'),
('DI', 'Día', 'tiempo'),
('MES', 'Mes', 'tiempo')
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);

-- ==================================================================
-- VISTAS CONSOLIDADAS
-- ==================================================================

-- Vista: Estado de Órdenes de Compra
DROP VIEW IF EXISTS `v_ordenes_compra_estado`;
CREATE VIEW `v_ordenes_compra_estado` AS
SELECT
    oc.id,
    oc.empresa_id,
    oc.numero_orden,
    oc.proveedor_id,
    p.razon_social as proveedor_nombre,
    oc.fecha_emision,
    oc.fecha_entrega_esperada,
    oc.estado,
    oc.total,
    SUM(ocd.cantidad_solicitada) as total_items_solicitados,
    SUM(ocd.cantidad_recibida) as total_items_recibidos,
    CASE
        WHEN SUM(ocd.cantidad_recibida) = 0 THEN 0
        ELSE (SUM(ocd.cantidad_recibida) / SUM(ocd.cantidad_solicitada)) * 100
    END as porcentaje_recibido
FROM ordenes_compra oc
LEFT JOIN proveedores p ON oc.proveedor_id = p.id
LEFT JOIN ordenes_compra_detalle ocd ON oc.id = ocd.orden_compra_id
GROUP BY oc.id;

-- Vista: Cuentas por Pagar Resumen
DROP VIEW IF EXISTS `v_cuentas_por_pagar`;
CREATE VIEW `v_cuentas_por_pagar` AS
SELECT
    fc.empresa_id,
    fc.proveedor_id,
    p.razon_social as proveedor_nombre,
    p.rut as proveedor_rut,
    COUNT(fc.id) as total_facturas,
    SUM(fc.total) as monto_total,
    SUM(fc.monto_pagado) as monto_pagado,
    SUM(fc.saldo) as saldo_pendiente,
    SUM(CASE WHEN fc.estado_pago = 'vencida' THEN fc.saldo ELSE 0 END) as saldo_vencido,
    MIN(fc.fecha_vencimiento) as proxima_fecha_vencimiento
FROM facturas_compra fc
LEFT JOIN proveedores p ON fc.proveedor_id = p.id
WHERE fc.estado_pago IN ('pendiente', 'pagada_parcial', 'vencida')
GROUP BY fc.empresa_id, fc.proveedor_id;

-- Vista: Productos Stock Bajo
DROP VIEW IF EXISTS `v_productos_stock_bajo`;
CREATE VIEW `v_productos_stock_bajo` AS
SELECT
    p.id,
    p.empresa_id,
    p.codigo,
    p.nombre,
    p.stock_actual,
    p.stock_minimo,
    p.stock_maximo,
    (p.stock_minimo - p.stock_actual) as cantidad_a_reponer,
    CASE
        WHEN p.stock_actual <= 0 THEN 'SIN_STOCK'
        WHEN p.stock_actual <= p.stock_minimo THEN 'STOCK_BAJO'
        ELSE 'OK'
    END as estado_stock
FROM productos p
WHERE p.activo = 1
  AND p.tipo = 'producto'
  AND p.stock_actual <= p.stock_minimo;

-- Vista: Ventas por Período
DROP VIEW IF EXISTS `v_ventas_por_periodo`;
CREATE VIEW `v_ventas_por_periodo` AS
SELECT
    f.empresa_id,
    DATE_FORMAT(f.fecha_emision, '%Y-%m') as periodo,
    COUNT(f.id) as cantidad_facturas,
    SUM(f.total) as total_ventas,
    SUM(f.subtotal) as total_neto,
    SUM(f.iva) as total_iva,
    AVG(f.total) as promedio_venta
FROM facturas f
WHERE f.estado NOT IN ('anulada', 'rechazada')
GROUP BY f.empresa_id, DATE_FORMAT(f.fecha_emision, '%Y-%m');

-- Vista: Compras por Período
DROP VIEW IF EXISTS `v_compras_por_periodo`;
CREATE VIEW `v_compras_por_periodo` AS
SELECT
    fc.empresa_id,
    DATE_FORMAT(fc.fecha_emision, '%Y-%m') as periodo,
    COUNT(fc.id) as cantidad_facturas,
    SUM(fc.total) as total_compras,
    SUM(fc.subtotal) as total_neto,
    SUM(fc.iva) as total_iva,
    AVG(fc.total) as promedio_compra
FROM facturas_compra fc
GROUP BY fc.empresa_id, DATE_FORMAT(fc.fecha_emision, '%Y-%m');

-- ==================================================================
-- FIN DEL SCRIPT
-- ==================================================================
