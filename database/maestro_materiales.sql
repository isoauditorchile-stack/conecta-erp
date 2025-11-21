-- =====================================================
-- TABLA: mm_maestro_materiales
-- Descripcion: Maestro completo de materiales
-- Modulo: Materials Management (MM)
-- =====================================================

CREATE TABLE IF NOT EXISTS mm_maestro_materiales (
    -- Identificador unico
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,

    -- IDENTIFICACION DEL MATERIAL
    codigo_material VARCHAR(50) NOT NULL,
    codigo_interno VARCHAR(50),
    codigo_barras VARCHAR(100),
    descripcion VARCHAR(255) NOT NULL,
    descripcion_larga TEXT,
    tipo_material VARCHAR(50),
    familia VARCHAR(100),
    subfamilia VARCHAR(100),
    categoria VARCHAR(100),
    marca VARCHAR(100),
    modelo VARCHAR(100),
    sku VARCHAR(100),
    estado_material ENUM('activo', 'inactivo', 'descontinuado', 'en_evaluacion') DEFAULT 'activo',

    -- DATOS GENERALES
    unidad_medida_base VARCHAR(20),
    unidad_medida_compra VARCHAR(20),
    unidad_medida_venta VARCHAR(20),
    factor_conversion_compra DECIMAL(10,4) DEFAULT 1.0000,
    factor_conversion_venta DECIMAL(10,4) DEFAULT 1.0000,
    peso DECIMAL(10,4),
    volumen DECIMAL(10,4),
    dimensiones VARCHAR(100),
    color VARCHAR(50),
    talla VARCHAR(20),
    formato VARCHAR(50),

    -- INFORMACION COMERCIAL
    precio_base DECIMAL(15,2) DEFAULT 0.00,
    precio_minimo DECIMAL(15,2) DEFAULT 0.00,
    precio_maximo DECIMAL(15,2) DEFAULT 0.00,
    lista_precios VARCHAR(100),
    moneda VARCHAR(10) DEFAULT 'CLP',
    impuesto_asociado VARCHAR(50),
    afecta_iva TINYINT(1) DEFAULT 1,
    tipo_venta VARCHAR(50),
    tipo_producto ENUM('normal', 'servicio', 'conjunto', 'kit') DEFAULT 'normal',

    -- CONTROL DE INVENTARIO
    controla_stock TINYINT(1) DEFAULT 1,
    stock_minimo DECIMAL(10,2) DEFAULT 0.00,
    stock_maximo DECIMAL(10,2) DEFAULT 0.00,
    punto_reposicion DECIMAL(10,2) DEFAULT 0.00,
    lote VARCHAR(50),
    serie VARCHAR(50),
    vida_util INT,
    fecha_vencimiento DATE,
    politica_rotacion ENUM('FIFO', 'LIFO', 'FEFO') DEFAULT 'FIFO',
    ubicacion_bodega VARCHAR(100),
    bodegas_asociadas TEXT,
    stock_segun_bodega TEXT,

    -- COSTOS
    costo_promedio DECIMAL(15,2) DEFAULT 0.00,
    costo_ultimo DECIMAL(15,2) DEFAULT 0.00,
    costo_estandar DECIMAL(15,2) DEFAULT 0.00,
    metodo_costeo ENUM('promedio', 'fifo', 'lifo', 'estandar') DEFAULT 'promedio',
    margen_sugerido DECIMAL(5,2) DEFAULT 0.00,
    markup_sugerido DECIMAL(5,2) DEFAULT 0.00,

    -- PROVEEDORES
    proveedor_principal VARCHAR(100),
    proveedores_secundarios TEXT,
    codigo_proveedor VARCHAR(100),
    costo_proveedor DECIMAL(15,2) DEFAULT 0.00,
    plazo_entrega INT,
    minimo_compra DECIMAL(10,2) DEFAULT 0.00,
    unidad_compra VARCHAR(20),

    -- DATOS PARA COMPRAS
    permitir_compras TINYINT(1) DEFAULT 1,
    cantidad_minima_compra DECIMAL(10,2) DEFAULT 0.00,
    cantidad_multiplo_compra DECIMAL(10,2) DEFAULT 1.00,
    plazo_reposicion INT,
    costo_flete DECIMAL(15,2) DEFAULT 0.00,
    costo_importacion DECIMAL(15,2) DEFAULT 0.00,

    -- DATOS PARA VENTAS
    permitir_venta TINYINT(1) DEFAULT 1,
    unidad_venta VARCHAR(20),
    descuento_maximo DECIMAL(5,2) DEFAULT 0.00,
    lista_precios_venta VARCHAR(100),
    comision_vendedor DECIMAL(5,2) DEFAULT 0.00,
    aplica_promociones TINYINT(1) DEFAULT 1,

    -- IMAGEN Y ADJUNTOS
    imagen_principal VARCHAR(255),
    imagen_secundaria VARCHAR(255),
    ficha_tecnica VARCHAR(255),
    manual VARCHAR(255),
    documentos_asociados TEXT,

    -- INTEGRACIONES
    integracion_contabilidad TINYINT(1) DEFAULT 0,
    integracion_inventario TINYINT(1) DEFAULT 1,
    integracion_compras TINYINT(1) DEFAULT 1,
    integracion_ventas TINYINT(1) DEFAULT 1,
    integracion_ecommerce TINYINT(1) DEFAULT 0,
    integracion_pos TINYINT(1) DEFAULT 0,
    integracion_produccion TINYINT(1) DEFAULT 0,

    -- AUDITORIA
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    usuario_creacion INT,
    fecha_modificacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    usuario_modificacion INT,
    historial_cambios TEXT,

    -- INDICES
    INDEX idx_user_id (user_id),
    INDEX idx_codigo_material (codigo_material),
    INDEX idx_codigo_barras (codigo_barras),
    INDEX idx_estado (estado_material),
    INDEX idx_familia (familia),
    INDEX idx_categoria (categoria),
    INDEX idx_tipo_producto (tipo_producto),
    UNIQUE KEY unique_codigo_user (codigo_material, user_id),

    -- FOREIGN KEYS
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
