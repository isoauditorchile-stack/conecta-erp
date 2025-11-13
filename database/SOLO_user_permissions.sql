-- =====================================================
-- SOLO TABLA: user_permissions
-- =====================================================
-- Esta tabla es CRÍTICA para el sistema de registro
-- Ejecutar en phpMyAdmin si la tabla no existe
-- =====================================================

USE conectae_conectaerpbd;

-- Eliminar la tabla si existe (para recrearla limpia)
DROP TABLE IF EXISTS `user_permissions`;

-- Crear tabla user_permissions
CREATE TABLE `user_permissions` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `submodule_id` INT(11) UNSIGNED NOT NULL,
  `can_view` TINYINT(1) DEFAULT 1,
  `can_create` TINYINT(1) DEFAULT 0,
  `can_edit` TINYINT(1) DEFAULT 0,
  `can_delete` TINYINT(1) DEFAULT 0,
  `granted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_submodule` (`user_id`, `submodule_id`),
  INDEX `idx_user` (`user_id`),
  INDEX `idx_submodule` (`submodule_id`),
  CONSTRAINT `fk_user_permissions_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_user_permissions_submodule` FOREIGN KEY (`submodule_id`) REFERENCES `submodules`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Permisos granulares de usuarios por submódulo - CRUD permissions';

-- Verificación
SELECT 'user_permissions creada exitosamente' as resultado;
