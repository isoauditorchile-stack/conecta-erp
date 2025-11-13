-- =====================================================
-- FIX: Permitir que owner_user_id sea NULL en tabla companies
-- =====================================================
-- Problema: No se puede crear empresa sin usuario, ni usuario sin empresa
-- Solución: Hacer owner_user_id nullable y actualizable después
-- =====================================================

USE conectae_conectaerpbd;

-- Modificar la columna owner_user_id para que sea NULLABLE
ALTER TABLE `companies`
MODIFY COLUMN `owner_user_id` INT(11) UNSIGNED NULL DEFAULT NULL;

-- Verificar el cambio
SELECT 'owner_user_id ahora es NULLABLE' as resultado;

-- Mostrar nueva estructura
DESCRIBE companies;
