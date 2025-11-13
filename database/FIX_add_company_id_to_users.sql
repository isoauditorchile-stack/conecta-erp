-- =====================================================
-- FIX FINAL: Agregar columna company_id a tabla users
-- =====================================================

USE conectae_conectaerpbd;

-- Agregar columna company_id si no existe
ALTER TABLE `users`
ADD COLUMN `company_id` INT(11) UNSIGNED NULL DEFAULT NULL AFTER `username`;

-- Agregar foreign key
ALTER TABLE `users`
ADD CONSTRAINT `fk_users_company` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE SET NULL;

-- Verificación
SELECT 'Columna company_id agregada a tabla users' as resultado;
