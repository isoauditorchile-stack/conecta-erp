-- =====================================================
-- SCRIPT SQL: TABLAS DE CATALOGOS PARA QUICK ADD
-- Sistema: CONECTA ERP
-- Modulo: Maestro de Clientes
-- =====================================================

-- Tabla: Tipos de Cliente
CREATE TABLE IF NOT EXISTS cat_tipos_cliente (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) NOT NULL,
    nombre VARCHAR(200) NOT NULL,
    company_id INT NOT NULL DEFAULT 1,
    pais_id INT NOT NULL DEFAULT 1,
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_codigo_company_pais (codigo, company_id, pais_id),
    INDEX idx_company (company_id),
    INDEX idx_pais (pais_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos iniciales Tipos de Cliente
INSERT IGNORE INTO cat_tipos_cliente (codigo, nombre, company_id, pais_id) VALUES
('NACIONAL', 'Cliente Nacional', 1, 1),
('INTERNACIONAL', 'Cliente Internacional', 1, 1),
('GOBIERNO', 'Cliente Gubernamental', 1, 1),
('CORPORATIVO', 'Cliente Corporativo', 1, 1);

-- Tabla: Categorias de Cliente
CREATE TABLE IF NOT EXISTS cat_categorias_cliente (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) NOT NULL,
    nombre VARCHAR(200) NOT NULL,
    company_id INT NOT NULL DEFAULT 1,
    pais_id INT NOT NULL DEFAULT 1,
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_codigo_company_pais (codigo, company_id, pais_id),
    INDEX idx_company (company_id),
    INDEX idx_pais (pais_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos iniciales Categorias de Cliente
INSERT IGNORE INTO cat_categorias_cliente (codigo, nombre, company_id, pais_id) VALUES
('A', 'Categoria A - Premium', 1, 1),
('B', 'Categoria B - Estandar', 1, 1),
('C', 'Categoria C - Basico', 1, 1),
('VIP', 'Categoria VIP', 1, 1);

-- Tabla: Grupos de Cliente
CREATE TABLE IF NOT EXISTS cat_grupos_cliente (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) NOT NULL,
    nombre VARCHAR(200) NOT NULL,
    company_id INT NOT NULL DEFAULT 1,
    pais_id INT NOT NULL DEFAULT 1,
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_codigo_company_pais (codigo, company_id, pais_id),
    INDEX idx_company (company_id),
    INDEX idx_pais (pais_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos iniciales Grupos de Cliente
INSERT IGNORE INTO cat_grupos_cliente (codigo, nombre, company_id, pais_id) VALUES
('RETAIL', 'Clientes Retail', 1, 1),
('MAYORISTA', 'Clientes Mayoristas', 1, 1),
('CORPORATIVO', 'Clientes Corporativos', 1, 1),
('DISTRIBUIDOR', 'Distribuidores', 1, 1);

-- Tabla: Condiciones de Pago
CREATE TABLE IF NOT EXISTS cat_condiciones_pago (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) NOT NULL,
    nombre VARCHAR(200) NOT NULL,
    dias INT DEFAULT 0,
    descripcion TEXT,
    company_id INT NOT NULL DEFAULT 1,
    pais_id INT NOT NULL DEFAULT 1,
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_codigo_company_pais (codigo, company_id, pais_id),
    INDEX idx_company (company_id),
    INDEX idx_pais (pais_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos iniciales Condiciones de Pago
INSERT IGNORE INTO cat_condiciones_pago (codigo, nombre, dias, company_id, pais_id) VALUES
('CONTADO', 'Contado', 0, 1, 1),
('30DIAS', '30 Dias', 30, 1, 1),
('60DIAS', '60 Dias', 60, 1, 1),
('90DIAS', '90 Dias', 90, 1, 1),
('15DIAS', '15 Dias', 15, 1, 1),
('45DIAS', '45 Dias', 45, 1, 1);

-- Tabla: Listas de Precio
CREATE TABLE IF NOT EXISTS cat_listas_precio (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) NOT NULL,
    nombre VARCHAR(200) NOT NULL,
    descripcion TEXT,
    margen_porcentaje DECIMAL(10,2) DEFAULT 0,
    company_id INT NOT NULL DEFAULT 1,
    pais_id INT NOT NULL DEFAULT 1,
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_codigo_company_pais (codigo, company_id, pais_id),
    INDEX idx_company (company_id),
    INDEX idx_pais (pais_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos iniciales Listas de Precio
INSERT IGNORE INTO cat_listas_precio (codigo, nombre, margen_porcentaje, company_id, pais_id) VALUES
('GENERAL', 'Lista General', 0, 1, 1),
('MAYORISTA', 'Lista Mayorista', 15, 1, 1),
('RETAIL', 'Lista Retail', 30, 1, 1),
('VIP', 'Lista VIP', -10, 1, 1);

-- =====================================================
-- TABLAS PARA MODULO DE PROVEEDORES
-- =====================================================

-- Tabla: Tipos de Proveedor
CREATE TABLE IF NOT EXISTS cat_tipos_proveedor (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) NOT NULL,
    nombre VARCHAR(200) NOT NULL,
    company_id INT NOT NULL DEFAULT 1,
    pais_id INT NOT NULL DEFAULT 1,
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_codigo_company_pais (codigo, company_id, pais_id),
    INDEX idx_company (company_id),
    INDEX idx_pais (pais_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos iniciales Tipos de Proveedor
INSERT IGNORE INTO cat_tipos_proveedor (codigo, nombre, company_id, pais_id) VALUES
('BIENES', 'Proveedor de Bienes', 1, 1),
('SERVICIOS', 'Proveedor de Servicios', 1, 1),
('MIXTO', 'Proveedor Mixto', 1, 1),
('INTERNACIONAL', 'Proveedor Internacional', 1, 1);

-- Tabla: Rubros o Industrias
CREATE TABLE IF NOT EXISTS cat_rubros_industria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) NOT NULL,
    nombre VARCHAR(200) NOT NULL,
    company_id INT NOT NULL DEFAULT 1,
    pais_id INT NOT NULL DEFAULT 1,
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_codigo_company_pais (codigo, company_id, pais_id),
    INDEX idx_company (company_id),
    INDEX idx_pais (pais_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos iniciales Rubros
INSERT IGNORE INTO cat_rubros_industria (codigo, nombre, company_id, pais_id) VALUES
('TECNOLOGIA', 'Tecnologia e Informatica', 1, 1),
('CONSTRUCCION', 'Construccion', 1, 1),
('ALIMENTOS', 'Alimentos y Bebidas', 1, 1),
('TRANSPORTE', 'Transporte y Logistica', 1, 1),
('SERVICIOS', 'Servicios Generales', 1, 1);

-- Tabla: Categorias Financieras
CREATE TABLE IF NOT EXISTS cat_categorias_financiera (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) NOT NULL,
    nombre VARCHAR(200) NOT NULL,
    company_id INT NOT NULL DEFAULT 1,
    pais_id INT NOT NULL DEFAULT 1,
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_codigo_company_pais (codigo, company_id, pais_id),
    INDEX idx_company (company_id),
    INDEX idx_pais (pais_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos iniciales Categorias Financieras
INSERT IGNORE INTO cat_categorias_financiera (codigo, nombre, company_id, pais_id) VALUES
('AAA', 'Categoria AAA - Excelente', 1, 1),
('AA', 'Categoria AA - Muy Bueno', 1, 1),
('A', 'Categoria A - Bueno', 1, 1),
('B', 'Categoria B - Regular', 1, 1),
('C', 'Categoria C - Riesgoso', 1, 1);

-- Tabla: Formas de Pago
CREATE TABLE IF NOT EXISTS cat_formas_pago (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) NOT NULL,
    nombre VARCHAR(200) NOT NULL,
    company_id INT NOT NULL DEFAULT 1,
    pais_id INT NOT NULL DEFAULT 1,
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_codigo_company_pais (codigo, company_id, pais_id),
    INDEX idx_company (company_id),
    INDEX idx_pais (pais_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos iniciales Formas de Pago
INSERT IGNORE INTO cat_formas_pago (codigo, nombre, company_id, pais_id) VALUES
('EFECTIVO', 'Efectivo', 1, 1),
('TRANSFERENCIA', 'Transferencia Bancaria', 1, 1),
('CHEQUE', 'Cheque', 1, 1),
('TARJETA', 'Tarjeta de Credito', 1, 1),
('CREDITO', 'Credito', 1, 1);

-- Tabla: Bancos
CREATE TABLE IF NOT EXISTS cat_bancos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) NOT NULL,
    nombre VARCHAR(200) NOT NULL,
    company_id INT NOT NULL DEFAULT 1,
    pais_id INT NOT NULL DEFAULT 1,
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_codigo_company_pais (codigo, company_id, pais_id),
    INDEX idx_company (company_id),
    INDEX idx_pais (pais_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos iniciales Bancos (Chile)
INSERT IGNORE INTO cat_bancos (codigo, nombre, company_id, pais_id) VALUES
('BCHILE', 'Banco de Chile', 1, 1),
('BESTADO', 'Banco Estado', 1, 1),
('BSANTANDER', 'Banco Santander', 1, 1),
('BCI', 'Banco de Credito e Inversiones', 1, 1),
('SCOTIABANK', 'Scotiabank', 1, 1),
('ITAU', 'Banco Itau', 1, 1),
('BFALABELLA', 'Banco Falabella', 1, 1),
('BSECURITY', 'Banco Security', 1, 1);

-- =====================================================
-- FIN DEL SCRIPT
-- =====================================================
