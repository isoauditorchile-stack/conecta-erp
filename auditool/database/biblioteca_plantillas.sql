-- =====================================================
-- MÓDULO: BIBLIOTECA DE PLANTILLAS
-- Cargue, organice y descargue plantillas Excel/Word/PDF
-- =====================================================

-- Tabla: Categorías de plantillas
CREATE TABLE IF NOT EXISTS `template_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `category_code` varchar(50) NOT NULL,
  `category_name` varchar(255) NOT NULL,
  `parent_category_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `color` varchar(20) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`),
  KEY `parent_category_id` (`parent_category_id`),
  CONSTRAINT `fk_tpl_cat_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tpl_cat_parent` FOREIGN KEY (`parent_category_id`) REFERENCES `template_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos iniciales de categorías
INSERT INTO `template_categories` (`company_id`, `category_code`, `category_name`, `icon`, `color`, `sort_order`) VALUES
(1, 'POL', 'Políticas', '📋', '#0066CC', 1),
(1, 'PROC', 'Procedimientos', '📄', '#00994D', 2),
(1, 'FO', 'Formatos y Registros', '📝', '#FF6600', 3),
(1, 'PLAN', 'Planes', '📅', '#CC0000', 4),
(1, 'INF', 'Informes', '📊', '#0099CC', 5),
(1, 'MAT', 'Matrices', '📈', '#9933CC', 6),
(1, 'CHECK', 'Checklists', '✓', '#00CC66', 7),
(1, 'PRES', 'Presentaciones', '🎯', '#FF9900', 8);

-- Tabla: Biblioteca de plantillas
CREATE TABLE IF NOT EXISTS `template_library` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `template_code` varchar(50) NOT NULL,
  `template_name` varchar(255) NOT NULL,
  `template_type` enum('Word','Excel','PowerPoint','PDF','Visio','Otro') NOT NULL,
  `file_extension` varchar(10) DEFAULT NULL,
  `version` varchar(20) DEFAULT '1.0',
  `description` text DEFAULT NULL,
  `usage_instructions` text DEFAULT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(11) DEFAULT NULL COMMENT 'Bytes',
  `preview_image` varchar(500) DEFAULT NULL,
  `tags` varchar(500) DEFAULT NULL COMMENT 'Etiquetas separadas por coma',
  `iso_control` varchar(50) DEFAULT NULL COMMENT 'Control ISO 27001 relacionado',
  `related_policy` varchar(50) DEFAULT NULL,
  `related_procedure` varchar(50) DEFAULT NULL,
  `language` varchar(10) DEFAULT 'es',
  `is_official` tinyint(1) DEFAULT 1,
  `is_public` tinyint(1) DEFAULT 0,
  `requires_approval` tinyint(1) DEFAULT 0,
  `download_count` int(11) DEFAULT 0,
  `last_download` datetime DEFAULT NULL,
  `rating_average` decimal(3,2) DEFAULT NULL COMMENT '0.00-5.00',
  `rating_count` int(11) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_by` int(11) DEFAULT NULL,
  `updated_date` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_template` (`company_id`,`template_code`),
  KEY `company_id` (`company_id`),
  KEY `category_id` (`category_id`),
  KEY `template_type` (`template_type`),
  FULLTEXT KEY `ft_search` (`template_name`,`description`,`tags`),
  CONSTRAINT `fk_tpl_lib_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tpl_lib_category` FOREIGN KEY (`category_id`) REFERENCES `template_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: Versiones de plantillas
CREATE TABLE IF NOT EXISTS `template_versions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `template_id` int(11) NOT NULL,
  `version_number` varchar(20) NOT NULL,
  `version_notes` text DEFAULT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(11) DEFAULT NULL,
  `is_current` tinyint(1) DEFAULT 0,
  `uploaded_by` int(11) DEFAULT NULL,
  `upload_date` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `template_id` (`template_id`),
  CONSTRAINT `fk_tpl_ver_template` FOREIGN KEY (`template_id`) REFERENCES `template_library` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: Descargas de plantillas
CREATE TABLE IF NOT EXISTS `template_downloads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `template_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `download_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `version_downloaded` varchar(20) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `purpose` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `template_id` (`template_id`),
  KEY `user_id` (`user_id`),
  KEY `download_date` (`download_date`),
  CONSTRAINT `fk_tpl_dl_template` FOREIGN KEY (`template_id`) REFERENCES `template_library` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: Valoraciones de plantillas
CREATE TABLE IF NOT EXISTS `template_ratings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `template_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` tinyint(1) NOT NULL COMMENT '1-5 estrellas',
  `review` text DEFAULT NULL,
  `rating_date` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_rating` (`template_id`,`user_id`),
  KEY `template_id` (`template_id`),
  CONSTRAINT `fk_tpl_rating_template` FOREIGN KEY (`template_id`) REFERENCES `template_library` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: Comentarios sobre plantillas
CREATE TABLE IF NOT EXISTS `template_comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `template_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `parent_comment_id` int(11) DEFAULT NULL,
  `is_approved` tinyint(1) DEFAULT 1,
  `created_date` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `template_id` (`template_id`),
  KEY `parent_comment_id` (`parent_comment_id`),
  CONSTRAINT `fk_tpl_comment_template` FOREIGN KEY (`template_id`) REFERENCES `template_library` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tpl_comment_parent` FOREIGN KEY (`parent_comment_id`) REFERENCES `template_comments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: Favoritos de plantillas
CREATE TABLE IF NOT EXISTS `template_favorites` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `template_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `added_date` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_favorite` (`template_id`,`user_id`),
  KEY `template_id` (`template_id`),
  CONSTRAINT `fk_tpl_fav_template` FOREIGN KEY (`template_id`) REFERENCES `template_library` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: Solicitudes de plantillas
CREATE TABLE IF NOT EXISTS `template_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `requested_by` int(11) NOT NULL,
  `template_name` varchar(255) NOT NULL,
  `template_type` enum('Word','Excel','PowerPoint','PDF','Visio','Otro') DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `description` text NOT NULL,
  `business_justification` text DEFAULT NULL,
  `priority` enum('Baja','Media','Alta','Urgente') DEFAULT 'Media',
  `target_date` date DEFAULT NULL,
  `status` enum('Pendiente','En Revisión','Aprobada','Rechazada','En Desarrollo','Completada') DEFAULT 'Pendiente',
  `assigned_to` int(11) DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `review_date` datetime DEFAULT NULL,
  `review_comments` text DEFAULT NULL,
  `completed_template_id` int(11) DEFAULT NULL,
  `request_date` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`),
  KEY `requested_by` (`requested_by`),
  KEY `status` (`status`),
  KEY `completed_template_id` (`completed_template_id`),
  CONSTRAINT `fk_tpl_req_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tpl_req_completed` FOREIGN KEY (`completed_template_id`) REFERENCES `template_library` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: Paquetes de plantillas
CREATE TABLE IF NOT EXISTS `template_packages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `package_code` varchar(50) NOT NULL,
  `package_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `templates` text NOT NULL COMMENT 'JSON con IDs de plantillas incluidas',
  `file_path` varchar(500) DEFAULT NULL COMMENT 'ZIP con todas las plantillas',
  `file_size` int(11) DEFAULT NULL,
  `version` varchar(20) DEFAULT '1.0',
  `is_active` tinyint(1) DEFAULT 1,
  `download_count` int(11) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_date` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_package` (`company_id`,`package_code`),
  KEY `company_id` (`company_id`),
  CONSTRAINT `fk_tpl_pkg_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos iniciales de paquetes
INSERT INTO `template_packages` (`company_id`, `package_code`, `package_name`, `description`, `templates`, `version`) VALUES
(1, 'PKG-ISO-BASIC', 'Paquete Básico ISO 27001', 'Plantillas esenciales para iniciar implementación de ISO 27001', '[]', '1.0'),
(1, 'PKG-ISO-COMPLETE', 'Paquete Completo ISO 27001', 'Todas las plantillas necesarias para certificación ISO 27001', '[]', '1.0'),
(1, 'PKG-AUDIT', 'Paquete de Auditorías', 'Plantillas para gestión completa de auditorías internas', '[]', '1.0');

-- Tabla: Etiquetas de plantillas
CREATE TABLE IF NOT EXISTS `template_tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tag_name` varchar(100) NOT NULL,
  `tag_color` varchar(20) DEFAULT NULL,
  `usage_count` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tag_name` (`tag_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos iniciales de etiquetas
INSERT INTO `template_tags` (`tag_name`, `tag_color`) VALUES
('ISO 27001', '#0066CC'),
('Obligatorio', '#CC0000'),
('Recomendado', '#00994D'),
('Auditoría', '#9933CC'),
('Riesgos', '#FF6600'),
('Activos', '#0099CC'),
('Controles', '#00CC66'),
('Políticas', '#0066CC'),
('Procedimientos', '#00994D'),
('Formatos', '#FF9900');

-- Tabla: Relación plantillas-etiquetas
CREATE TABLE IF NOT EXISTS `template_tag_relations` (
  `template_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL,
  PRIMARY KEY (`template_id`,`tag_id`),
  KEY `tag_id` (`tag_id`),
  CONSTRAINT `fk_tpl_tag_rel_template` FOREIGN KEY (`template_id`) REFERENCES `template_library` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tpl_tag_rel_tag` FOREIGN KEY (`tag_id`) REFERENCES `template_tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: Estadísticas de uso de plantillas
CREATE TABLE IF NOT EXISTS `template_statistics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `period_year` year NOT NULL,
  `period_month` tinyint(2) NOT NULL,
  `total_templates` int(11) DEFAULT 0,
  `total_downloads` int(11) DEFAULT 0,
  `most_downloaded_template_id` int(11) DEFAULT NULL,
  `top_category_id` int(11) DEFAULT NULL,
  `new_templates_added` int(11) DEFAULT 0,
  `templates_updated` int(11) DEFAULT 0,
  `unique_users` int(11) DEFAULT 0,
  `calculated_date` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_period` (`company_id`,`period_year`,`period_month`),
  KEY `company_id` (`company_id`),
  CONSTRAINT `fk_tpl_stats_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
