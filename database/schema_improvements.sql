-- =====================================================
-- CONECTA ERP - MEJORAS MULTIPAÍS Y MULTIEMPRESA
-- Nuevas tablas para configuración avanzada
-- =====================================================

-- Tabla de Países con Configuración
CREATE TABLE IF NOT EXISTS `countries` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(3) NOT NULL UNIQUE,
  `name_es` VARCHAR(100) NOT NULL,
  `name_en` VARCHAR(100) NOT NULL,
  `name_pt` VARCHAR(100) NOT NULL,
  `currency_code` VARCHAR(3) NOT NULL,
  `currency_symbol` VARCHAR(5) NOT NULL,
  `tax_id_name` VARCHAR(50) NOT NULL,
  `tax_id_format` VARCHAR(100) NOT NULL,
  `tax_id_validation_regex` VARCHAR(255) NOT NULL,
  `phone_code` VARCHAR(10) NOT NULL,
  `date_format` VARCHAR(20) DEFAULT 'DD/MM/YYYY',
  `decimal_separator` VARCHAR(1) DEFAULT ',',
  `thousand_separator` VARCHAR(1) DEFAULT '.',
  `vat_rate` DECIMAL(5,2) DEFAULT 0.00,
  `previred_enabled` TINYINT(1) DEFAULT 0,
  `sii_enabled` TINYINT(1) DEFAULT 0,
  `sii_api_url` VARCHAR(255),
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar configuración de países
INSERT INTO `countries` (`code`, `name_es`, `name_en`, `name_pt`, `currency_code`, `currency_symbol`, `tax_id_name`, `tax_id_format`, `tax_id_validation_regex`, `phone_code`, `vat_rate`, `previred_enabled`, `sii_enabled`, `sii_api_url`) VALUES
('CL', 'Chile', 'Chile', 'Chile', 'CLP', '$', 'RUT', 'XX.XXX.XXX-X', '^[0-9]{7,8}-[0-9Kk]{1}$', '+56', 19.00, 1, 1, 'https://api.sii.cl/'),
('AR', 'Argentina', 'Argentina', 'Argentina', 'ARS', '$', 'CUIT', 'XX-XXXXXXXX-X', '^[0-9]{2}-[0-9]{8}-[0-9]{1}$', '+54', 21.00, 0, 0, NULL),
('PE', 'Perú', 'Peru', 'Peru', 'PEN', 'S/', 'RUC', 'XXXXXXXXXXX', '^[0-9]{11}$', '+51', 18.00, 0, 0, NULL),
('CO', 'Colombia', 'Colombia', 'Colômbia', 'COP', '$', 'NIT', 'XXX.XXX.XXX-X', '^[0-9]{3}\.[0-9]{3}\.[0-9]{3}-[0-9]{1}$', '+57', 19.00, 0, 0, NULL),
('MX', 'México', 'Mexico', 'México', 'MXN', '$', 'RFC', 'XXXX-XXXXXX-XXX', '^[A-Z]{4}[0-9]{6}[A-Z0-9]{3}$', '+52', 16.00, 0, 0, NULL),
('BR', 'Brasil', 'Brazil', 'Brasil', 'BRL', 'R$', 'CNPJ', 'XX.XXX.XXX/XXXX-XX', '^[0-9]{2}\.[0-9]{3}\.[0-9]{3}/[0-9]{4}-[0-9]{2}$', '+55', 17.00, 0, 0, NULL),
('US', 'Estados Unidos', 'United States', 'Estados Unidos', 'USD', '$', 'EIN', 'XX-XXXXXXX', '^[0-9]{2}-[0-9]{7}$', '+1', 0.00, 0, 0, NULL),
('ES', 'España', 'Spain', 'Espanha', 'EUR', '€', 'CIF/NIF', 'XXXXXXXXX', '^[A-Z][0-9]{8}$', '+34', 21.00, 0, 0, NULL);

-- Tabla de Empresas (multiempresa)
CREATE TABLE IF NOT EXISTS `companies` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `owner_user_id` INT(11) UNSIGNED NOT NULL,
  `company_name` VARCHAR(200) NOT NULL,
  `legal_name` VARCHAR(200) NOT NULL,
  `tax_id` VARCHAR(50) NOT NULL,
  `country_id` INT(11) UNSIGNED NOT NULL,
  `industry` VARCHAR(100),
  `address` TEXT,
  `city` VARCHAR(100),
  `state_province` VARCHAR(100),
  `postal_code` VARCHAR(20),
  `phone` VARCHAR(50),
  `email` VARCHAR(150),
  `website` VARCHAR(255),
  `logo_url` VARCHAR(255),
  `fiscal_year_start` VARCHAR(5) DEFAULT '01-01',
  `employees_count` VARCHAR(20),
  `annual_revenue` VARCHAR(50),
  `previred_enabled` TINYINT(1) DEFAULT 0,
  `previred_rut` VARCHAR(50),
  `previred_api_key` VARCHAR(255),
  `sii_enabled` TINYINT(1) DEFAULT 0,
  `sii_rut` VARCHAR(50),
  `sii_certificate` TEXT,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`owner_user_id`) REFERENCES `users`(`id`),
  FOREIGN KEY (`country_id`) REFERENCES `countries`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de Usuarios de Empresa (multiusuario por empresa)
CREATE TABLE IF NOT EXISTS `company_users` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `role` ENUM('owner', 'admin', 'manager', 'user', 'viewer') DEFAULT 'user',
  `is_active` TINYINT(1) DEFAULT 1,
  `invited_by` INT(11) UNSIGNED,
  `joined_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `company_user` (`company_id`, `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de Traducciones (i18n)
CREATE TABLE IF NOT EXISTS `translations` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `key_text` VARCHAR(255) NOT NULL,
  `lang_es` TEXT,
  `lang_en` TEXT,
  `lang_pt` TEXT,
  `lang_fr` TEXT,
  `lang_de` TEXT,
  `lang_it` TEXT,
  `lang_ru` TEXT,
  `lang_zh` TEXT,
  `category` VARCHAR(50) DEFAULT 'general',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key_text` (`key_text`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar traducciones básicas
INSERT INTO `translations` (`key_text`, `lang_es`, `lang_en`, `lang_pt`, `lang_fr`, `lang_de`, `lang_it`, `category`) VALUES
('welcome', 'Bienvenido', 'Welcome', 'Bem-vindo', 'Bienvenue', 'Willkommen', 'Benvenuto', 'general'),
('dashboard', 'Panel de Control', 'Dashboard', 'Painel de Controle', 'Tableau de bord', 'Instrumententafel', 'Cruscotto', 'general'),
('logout', 'Cerrar Sesión', 'Logout', 'Sair', 'Déconnexion', 'Abmelden', 'Disconnettersi', 'general'),
('save', 'Guardar', 'Save', 'Salvar', 'Enregistrer', 'Speichern', 'Salvare', 'general'),
('cancel', 'Cancelar', 'Cancel', 'Cancelar', 'Annuler', 'Abbrechen', 'Annullare', 'general'),
('edit', 'Editar', 'Edit', 'Editar', 'Modifier', 'Bearbeiten', 'Modificare', 'general'),
('delete', 'Eliminar', 'Delete', 'Excluir', 'Supprimer', 'Löschen', 'Eliminare', 'general'),
('create', 'Crear', 'Create', 'Criar', 'Créer', 'Erstellen', 'Creare', 'general'),
('search', 'Buscar', 'Search', 'Buscar', 'Rechercher', 'Suchen', 'Cercare', 'general'),
('email', 'Correo Electrónico', 'Email', 'E-mail', 'E-mail', 'E-Mail', 'E-mail', 'general'),
('password', 'Contraseña', 'Password', 'Senha', 'Mot de passe', 'Passwort', 'Password', 'general'),
('company_name', 'Nombre de Empresa', 'Company Name', 'Nome da Empresa', 'Nom de l\'entreprise', 'Firmenname', 'Nome dell\'azienda', 'general'),
('tax_id', 'RUT/NIT/RFC', 'Tax ID', 'CPF/CNPJ', 'Numéro fiscal', 'Steuernummer', 'Codice fiscale', 'general'),
('phone', 'Teléfono', 'Phone', 'Telefone', 'Téléphone', 'Telefon', 'Telefono', 'general'),
('address', 'Dirección', 'Address', 'Endereço', 'Adresse', 'Adresse', 'Indirizzo', 'general'),
('country', 'País', 'Country', 'País', 'Pays', 'Land', 'Paese', 'general'),
('currency', 'Moneda', 'Currency', 'Moeda', 'Devise', 'Währung', 'Valuta', 'general'),
('language', 'Idioma', 'Language', 'Idioma', 'Langue', 'Sprache', 'Lingua', 'general');

-- Tabla de Configuración Previred
CREATE TABLE IF NOT EXISTS `previred_config` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `enabled` TINYINT(1) DEFAULT 0,
  `rut_empleador` VARCHAR(50) NOT NULL,
  `api_key` VARCHAR(255),
  `api_secret` VARCHAR(255),
  `environment` ENUM('sandbox', 'production') DEFAULT 'sandbox',
  `last_sync` DATETIME,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de Configuración SII
CREATE TABLE IF NOT EXISTS `sii_config` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) UNSIGNED NOT NULL,
  `enabled` TINYINT(1) DEFAULT 0,
  `rut_empresa` VARCHAR(50) NOT NULL,
  `razon_social` VARCHAR(200) NOT NULL,
  `giro` VARCHAR(200),
  `actividad_economica` VARCHAR(100),
  `certificate_path` VARCHAR(255),
  `certificate_password` VARCHAR(255),
  `environment` ENUM('certificacion', 'produccion') DEFAULT 'certificacion',
  `last_sync` DATETIME,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Actualizar tabla users para incluir más campos
ALTER TABLE `users`
ADD COLUMN `company_id` INT(11) UNSIGNED AFTER `user_id`,
ADD COLUMN `tax_id` VARCHAR(50) AFTER `company_name`,
ADD COLUMN `tax_id_type` VARCHAR(20) AFTER `tax_id`,
ADD COLUMN `industry` VARCHAR(100) AFTER `position`,
ADD COLUMN `website` VARCHAR(255) AFTER `phone`,
ADD COLUMN `address` TEXT AFTER `website`,
ADD COLUMN `city` VARCHAR(100) AFTER `address`,
ADD COLUMN `state_province` VARCHAR(100) AFTER `city`,
ADD COLUMN `postal_code` VARCHAR(20) AFTER `state_province`,
ADD COLUMN `preferred_language` VARCHAR(10) DEFAULT 'es' AFTER `language`,
ADD COLUMN `timezone` VARCHAR(50) DEFAULT 'America/Santiago' AFTER `preferred_language`;
