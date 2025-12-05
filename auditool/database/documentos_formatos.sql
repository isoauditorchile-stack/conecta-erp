-- =====================================================
-- MÓDULO: DOCUMENTOS Y FORMATOS
-- Gestión de documentos, formatos y plantillas del SGSI
-- =====================================================

-- Tabla: Categorías de documentos
CREATE TABLE IF NOT EXISTS `document_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `category_code` varchar(20) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `color` varchar(20) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_date` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`),
  CONSTRAINT `fk_doc_categories_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos iniciales de categorías
INSERT INTO `document_categories` (`company_id`, `category_code`, `category_name`, `description`, `icon`, `color`, `sort_order`) VALUES
(1, 'POL', 'Políticas', 'Políticas de seguridad de la información', '📋', '#0066CC', 1),
(1, 'PROC', 'Procedimientos', 'Procedimientos operativos del SGSI', '📄', '#00994D', 2),
(1, 'FO', 'Formatos', 'Formatos y registros', '📝', '#FF6600', 3),
(1, 'MAN', 'Manuales', 'Manuales del sistema', '📚', '#9933CC', 4),
(1, 'PLAN', 'Planes', 'Planes de trabajo y proyectos', '📅', '#CC0000', 5),
(1, 'INF', 'Informes', 'Informes y reportes', '📊', '#0099CC', 6);

-- Tabla: Listado maestro de documentos
CREATE TABLE IF NOT EXISTS `document_master_list` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `document_code` varchar(50) NOT NULL,
  `document_name` varchar(255) NOT NULL,
  `document_type` enum('Política','Procedimiento','Formato','Manual','Plan','Informe','Otro') NOT NULL,
  `version` varchar(20) DEFAULT '1.0',
  `status` enum('Borrador','En Revisión','Vigente','Obsoleto') DEFAULT 'Borrador',
  `issue_date` date DEFAULT NULL,
  `effective_date` date DEFAULT NULL,
  `review_date` date DEFAULT NULL,
  `next_review_date` date DEFAULT NULL,
  `prepared_by` int(11) DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `document_owner` varchar(100) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `keywords` varchar(500) DEFAULT NULL,
  `related_controls` varchar(500) DEFAULT NULL,
  `distribution_list` text DEFAULT NULL,
  `retention_period` varchar(50) DEFAULT NULL,
  `is_controlled` tinyint(1) DEFAULT 1,
  `download_count` int(11) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_by` int(11) DEFAULT NULL,
  `updated_date` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_document` (`company_id`,`document_code`),
  KEY `category_id` (`category_id`),
  KEY `status` (`status`),
  KEY `document_type` (`document_type`),
  CONSTRAINT `fk_doc_master_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_doc_master_category` FOREIGN KEY (`category_id`) REFERENCES `document_categories` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: Versiones de documentos (control de cambios)
CREATE TABLE IF NOT EXISTS `document_versions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `document_id` int(11) NOT NULL,
  `version_number` varchar(20) NOT NULL,
  `change_description` text NOT NULL,
  `changed_by` int(11) NOT NULL,
  `change_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `file_path` varchar(500) DEFAULT NULL,
  `is_current` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `document_id` (`document_id`),
  CONSTRAINT `fk_doc_versions_document` FOREIGN KEY (`document_id`) REFERENCES `document_master_list` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: Solicitudes de documentos
CREATE TABLE IF NOT EXISTS `document_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `request_type` enum('Nuevo','Modificación','Eliminación','Copia') NOT NULL,
  `document_code` varchar(50) DEFAULT NULL,
  `document_name` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `justification` text NOT NULL,
  `requested_by` int(11) NOT NULL,
  `request_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `status` enum('Pendiente','En Revisión','Aprobada','Rechazada','Completada') DEFAULT 'Pendiente',
  `reviewed_by` int(11) DEFAULT NULL,
  `review_date` datetime DEFAULT NULL,
  `review_comments` text DEFAULT NULL,
  `priority` enum('Baja','Media','Alta','Urgente') DEFAULT 'Media',
  `due_date` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`),
  KEY `status` (`status`),
  CONSTRAINT `fk_doc_requests_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: Distribución de documentos
CREATE TABLE IF NOT EXISTS `document_distribution` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `document_id` int(11) NOT NULL,
  `distributed_to` int(11) NOT NULL,
  `distribution_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `distribution_method` enum('Email','Portal','Físico','Otro') DEFAULT 'Email',
  `acknowledgment_required` tinyint(1) DEFAULT 0,
  `acknowledged` tinyint(1) DEFAULT 0,
  `acknowledgment_date` datetime DEFAULT NULL,
  `distributed_by` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `document_id` (`document_id`),
  CONSTRAINT `fk_doc_distribution_document` FOREIGN KEY (`document_id`) REFERENCES `document_master_list` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: Plantillas de documentos
CREATE TABLE IF NOT EXISTS `document_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `template_code` varchar(50) NOT NULL,
  `template_name` varchar(255) NOT NULL,
  `template_type` enum('Word','Excel','PDF','PowerPoint','Otro') NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(11) DEFAULT NULL,
  `version` varchar(20) DEFAULT '1.0',
  `is_active` tinyint(1) DEFAULT 1,
  `download_count` int(11) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_by` int(11) DEFAULT NULL,
  `updated_date` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_template` (`company_id`,`template_code`),
  KEY `company_id` (`company_id`),
  CONSTRAINT `fk_templates_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: Registro de acceso a documentos (auditoría)
CREATE TABLE IF NOT EXISTS `document_access_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `document_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` enum('View','Download','Edit','Delete','Print') NOT NULL,
  `access_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `document_id` (`document_id`),
  KEY `user_id` (`user_id`),
  KEY `access_date` (`access_date`),
  CONSTRAINT `fk_doc_access_document` FOREIGN KEY (`document_id`) REFERENCES `document_master_list` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
