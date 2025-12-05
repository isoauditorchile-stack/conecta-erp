-- =====================================================
-- MÓDULO: MATRICES SGSI
-- Exportar matrices SOA, Riesgos, Activos en Excel
-- =====================================================

-- Tabla: Declaración de Aplicabilidad (SOA)
CREATE TABLE IF NOT EXISTS `iso27001_soa` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `control_id` varchar(20) NOT NULL COMMENT 'Ej: A.5.1',
  `control_name` varchar(255) NOT NULL,
  `control_type` enum('Organizacional','Personas','Físico','Tecnológico') NOT NULL,
  `is_applicable` tinyint(1) DEFAULT 1,
  `applicability_justification` text DEFAULT NULL,
  `implementation_status` enum('No Iniciado','Planificado','En Implementación','Implementado','No Aplica') DEFAULT 'No Iniciado',
  `implementation_level` decimal(5,2) DEFAULT 0.00 COMMENT 'Porcentaje 0-100',
  `responsible` varchar(100) DEFAULT NULL,
  `target_date` date DEFAULT NULL,
  `completion_date` date DEFAULT NULL,
  `evidence` text DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `last_assessment` date DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_by` int(11) DEFAULT NULL,
  `updated_date` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_control` (`company_id`,`control_id`),
  KEY `company_id` (`company_id`),
  KEY `implementation_status` (`implementation_status`),
  CONSTRAINT `fk_soa_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos iniciales: Controles Anexo A ISO 27001:2022 (93 controles)
INSERT INTO `iso27001_soa` (`company_id`, `control_id`, `control_name`, `control_type`) VALUES
-- Controles Organizacionales (37 controles)
(1, 'A.5.1', 'Políticas de seguridad de la información', 'Organizacional'),
(1, 'A.5.2', 'Roles y responsabilidades de seguridad de la información', 'Organizacional'),
(1, 'A.5.3', 'Segregación de funciones', 'Organizacional'),
(1, 'A.5.4', 'Responsabilidades de la dirección', 'Organizacional'),
(1, 'A.5.5', 'Contacto con las autoridades', 'Organizacional'),
(1, 'A.5.6', 'Contacto con grupos de interés especial', 'Organizacional'),
(1, 'A.5.7', 'Inteligencia de amenazas', 'Organizacional'),
(1, 'A.5.8', 'Seguridad de la información en la gestión de proyectos', 'Organizacional'),
(1, 'A.5.9', 'Inventario de activos de información', 'Organizacional'),
(1, 'A.5.10', 'Uso aceptable de la información y otros activos asociados', 'Organizacional'),
(1, 'A.5.11', 'Devolución de activos', 'Organizacional'),
(1, 'A.5.12', 'Clasificación de la información', 'Organizacional'),
(1, 'A.5.13', 'Etiquetado de la información', 'Organizacional'),
(1, 'A.5.14', 'Transferencia de información', 'Organizacional'),
(1, 'A.5.15', 'Control de acceso', 'Organizacional'),
(1, 'A.5.16', 'Gestión de identidad', 'Organizacional'),
(1, 'A.5.17', 'Información de autenticación', 'Organizacional'),
(1, 'A.5.18', 'Derechos de acceso', 'Organizacional'),
(1, 'A.5.19', 'Seguridad de la información en las relaciones con proveedores', 'Organizacional'),
(1, 'A.5.20', 'Abordar la seguridad de la información en los acuerdos con proveedores', 'Organizacional'),
(1, 'A.5.21', 'Gestión de la seguridad de la información en la cadena de suministro de TIC', 'Organizacional'),
(1, 'A.5.22', 'Monitoreo, revisión y gestión de cambios de servicios de proveedores', 'Organizacional'),
(1, 'A.5.23', 'Seguridad de la información para el uso de servicios en la nube', 'Organizacional'),
(1, 'A.5.24', 'Planificación y preparación de la gestión de incidentes de seguridad', 'Organizacional'),
(1, 'A.5.25', 'Evaluación y decisión sobre eventos de seguridad de la información', 'Organizacional'),
(1, 'A.5.26', 'Respuesta a incidentes de seguridad de la información', 'Organizacional'),
(1, 'A.5.27', 'Aprender de los incidentes de seguridad de la información', 'Organizacional'),
(1, 'A.5.28', 'Recopilación de evidencia', 'Organizacional'),
(1, 'A.5.29', 'Seguridad de la información durante la interrupción', 'Organizacional'),
(1, 'A.5.30', 'Preparación de las TIC para la continuidad del negocio', 'Organizacional'),
(1, 'A.5.31', 'Requisitos legales, estatutarios, reglamentarios y contractuales', 'Organizacional'),
(1, 'A.5.32', 'Derechos de propiedad intelectual', 'Organizacional'),
(1, 'A.5.33', 'Protección de registros', 'Organizacional'),
(1, 'A.5.34', 'Privacidad y protección de PII', 'Organizacional'),
(1, 'A.5.35', 'Revisión independiente de la seguridad de la información', 'Organizacional'),
(1, 'A.5.36', 'Cumplimiento de políticas, reglas y normas de seguridad de la información', 'Organizacional'),
(1, 'A.5.37', 'Procedimientos operativos documentados', 'Organizacional');

-- Tabla: Matriz de Riesgos Resumida
CREATE TABLE IF NOT EXISTS `risk_matrix_summary` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `risk_id` int(11) NOT NULL,
  `risk_code` varchar(50) DEFAULT NULL,
  `risk_description` varchar(500) NOT NULL,
  `asset_affected` varchar(255) DEFAULT NULL,
  `threat` varchar(255) DEFAULT NULL,
  `vulnerability` varchar(255) DEFAULT NULL,
  `inherent_probability` tinyint(1) DEFAULT NULL COMMENT '1-5',
  `inherent_impact` tinyint(1) DEFAULT NULL COMMENT '1-5',
  `inherent_risk_level` tinyint(2) DEFAULT NULL COMMENT '1-25',
  `inherent_classification` enum('Bajo','Medio','Alto','Crítico') DEFAULT NULL,
  `treatment_option` enum('Mitigar','Transferir','Aceptar','Evitar') DEFAULT NULL,
  `controls_implemented` text DEFAULT NULL,
  `residual_probability` tinyint(1) DEFAULT NULL,
  `residual_impact` tinyint(1) DEFAULT NULL,
  `residual_risk_level` tinyint(2) DEFAULT NULL,
  `residual_classification` enum('Bajo','Medio','Alto','Crítico') DEFAULT NULL,
  `responsible` varchar(100) DEFAULT NULL,
  `target_date` date DEFAULT NULL,
  `status` enum('Identificado','En Tratamiento','Controlado','Aceptado') DEFAULT 'Identificado',
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`),
  KEY `risk_id` (`risk_id`),
  CONSTRAINT `fk_risk_matrix_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_risk_matrix_risk` FOREIGN KEY (`risk_id`) REFERENCES `iso27001_risks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: Matriz de Activos Resumida
CREATE TABLE IF NOT EXISTS `asset_matrix_summary` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `asset_id` int(11) NOT NULL,
  `asset_code` varchar(50) DEFAULT NULL,
  `asset_name` varchar(255) NOT NULL,
  `asset_type` enum('Hardware','Software','Datos','Servicios','Personal','Instalaciones','Otro') DEFAULT NULL,
  `asset_category` varchar(100) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `owner` varchar(100) DEFAULT NULL,
  `custodian` varchar(100) DEFAULT NULL,
  `classification` enum('Público','Interno','Confidencial','Estrictamente Confidencial') DEFAULT NULL,
  `confidentiality` tinyint(1) DEFAULT NULL COMMENT '1-5',
  `integrity` tinyint(1) DEFAULT NULL COMMENT '1-5',
  `availability` tinyint(1) DEFAULT NULL COMMENT '1-5',
  `criticality` enum('Bajo','Medio','Alto','Crítico') DEFAULT NULL,
  `status` enum('Activo','Inactivo','En Desuso','Eliminado') DEFAULT 'Activo',
  `value` decimal(15,2) DEFAULT NULL,
  `related_risks_count` int(11) DEFAULT 0,
  `related_controls_count` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`),
  KEY `asset_id` (`asset_id`),
  CONSTRAINT `fk_asset_matrix_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_asset_matrix_asset` FOREIGN KEY (`asset_id`) REFERENCES `iso27001_assets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: Configuración de exportación de matrices
CREATE TABLE IF NOT EXISTS `matrix_export_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `matrix_type` enum('SOA','Riesgos','Activos','Controles','Completo') NOT NULL,
  `config_name` varchar(255) NOT NULL,
  `columns` text NOT NULL COMMENT 'JSON con columnas a exportar',
  `filters` text DEFAULT NULL COMMENT 'JSON con filtros predefinidos',
  `grouping` varchar(255) DEFAULT NULL,
  `sorting` varchar(255) DEFAULT NULL,
  `format_options` text DEFAULT NULL COMMENT 'JSON con opciones de formato',
  `is_default` tinyint(1) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_date` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`),
  CONSTRAINT `fk_matrix_export_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: Historial de exportaciones de matrices
CREATE TABLE IF NOT EXISTS `matrix_export_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `matrix_type` enum('SOA','Riesgos','Activos','Controles','Completo') NOT NULL,
  `export_format` enum('Excel','CSV','PDF','JSON') NOT NULL,
  `config_used` int(11) DEFAULT NULL,
  `rows_exported` int(11) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `exported_by` int(11) NOT NULL,
  `export_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `download_count` int(11) DEFAULT 0,
  `last_download` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`),
  KEY `exported_by` (`exported_by`),
  CONSTRAINT `fk_matrix_history_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
