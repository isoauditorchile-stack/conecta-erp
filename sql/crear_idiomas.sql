-- ============================================
-- CREAR TABLA IDIOMAS - CONECTA ERP
-- Este archivo crea la tabla de idiomas con los 8 idiomas soportados
-- ============================================

-- Eliminar tabla si existe (solo para reinstalación)
-- DROP TABLE IF EXISTS `idiomas`;

-- Crear tabla de idiomas
CREATE TABLE IF NOT EXISTS `idiomas` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `codigo` VARCHAR(5) NOT NULL,
  `nombre` VARCHAR(100) NOT NULL,
  `nombre_nativo` VARCHAR(100) NOT NULL,
  `activo` TINYINT(1) DEFAULT 1,
  `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar los 8 idiomas soportados
INSERT INTO `idiomas` (`codigo`, `nombre`, `nombre_nativo`, `activo`) VALUES
('es', 'Español', 'Español', 1),
('en', 'Inglés', 'English', 1),
('pt', 'Portugués', 'Português', 1),
('fr', 'Francés', 'Français', 1),
('de', 'Alemán', 'Deutsch', 1),
('it', 'Italiano', 'Italiano', 1),
('ru', 'Ruso', 'Русский', 1),
('zh', 'Chino', '中文', 1);
