-- =====================================================
-- SCRIPT DE CORRECCION: Estructura de Tablas de Catálogos
-- Sistema: CONECTA ERP
-- Fecha: 2025-11-27
-- Problema: Columna 'contribuyente' en tabla cat_tipos_cliente
-- =====================================================

-- IMPORTANTE: Este script corrige la estructura de las tablas de catálogos
-- eliminando columnas que no deberían existir

-- Verificar y eliminar columna 'contribuyente' de cat_tipos_cliente si existe
SET @dbname = DATABASE();
SET @tablename = 'cat_tipos_cliente';
SET @columnname = 'contribuyente';

SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      TABLE_SCHEMA = @dbname
      AND TABLE_NAME = @tablename
      AND COLUMN_NAME = @columnname
  ) > 0,
  CONCAT('ALTER TABLE ', @tablename, ' DROP COLUMN ', @columnname, ';'),
  'SELECT 1;'
));

PREPARE alterIfExists FROM @preparedStatement;
EXECUTE alterIfExists;
DEALLOCATE PREPARE alterIfExists;

-- Lo mismo para cat_categorias_cliente
SET @tablename = 'cat_categorias_cliente';

SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      TABLE_SCHEMA = @dbname
      AND TABLE_NAME = @tablename
      AND COLUMN_NAME = @columnname
  ) > 0,
  CONCAT('ALTER TABLE ', @tablename, ' DROP COLUMN ', @columnname, ';'),
  'SELECT 1;'
));

PREPARE alterIfExists FROM @preparedStatement;
EXECUTE alterIfExists;
DEALLOCATE PREPARE alterIfExists;

-- Lo mismo para cat_grupos_cliente
SET @tablename = 'cat_grupos_cliente';

SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      TABLE_SCHEMA = @dbname
      AND TABLE_NAME = @tablename
      AND COLUMN_NAME = @columnname
  ) > 0,
  CONCAT('ALTER TABLE ', @tablename, ' DROP COLUMN ', @columnname, ';'),
  'SELECT 1;'
));

PREPARE alterIfExists FROM @preparedStatement;
EXECUTE alterIfExists;
DEALLOCATE PREPARE alterIfExists;

-- Verificar que las tablas tienen la estructura correcta
SELECT 'Verificando estructura de cat_tipos_cliente...' AS mensaje;
DESCRIBE cat_tipos_cliente;

SELECT 'Verificando estructura de cat_categorias_cliente...' AS mensaje;
DESCRIBE cat_categorias_cliente;

SELECT 'Verificando estructura de cat_grupos_cliente...' AS mensaje;
DESCRIBE cat_grupos_cliente;

-- Mostrar datos actuales
SELECT 'Datos actuales en cat_tipos_cliente:' AS mensaje;
SELECT * FROM cat_tipos_cliente ORDER BY codigo;

SELECT 'Datos actuales en cat_categorias_cliente:' AS mensaje;
SELECT * FROM cat_categorias_cliente ORDER BY codigo;

SELECT 'Datos actuales en cat_grupos_cliente:' AS mensaje;
SELECT * FROM cat_grupos_cliente ORDER BY codigo;

-- =====================================================
-- FIN DEL SCRIPT DE CORRECCION
-- =====================================================
