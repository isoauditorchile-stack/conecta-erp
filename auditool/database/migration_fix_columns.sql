-- ============================================
-- AUDITOR PRO - Migración de Columnas Faltantes
-- Script para agregar columnas faltantes a tablas existentes
-- EJECUTAR ESTE SCRIPT SI YA TIENES LAS TABLAS CREADAS
-- ============================================

USE conectae_isogestionbd;

-- Verificar y agregar columnas faltantes a iso27001_assets
ALTER TABLE `iso27001_assets`
  CHANGE COLUMN `description` `asset_description` text DEFAULT NULL;

ALTER TABLE `iso27001_assets`
  ADD COLUMN IF NOT EXISTS `acquisition_date` date DEFAULT NULL AFTER `data_classification`,
  ADD COLUMN IF NOT EXISTS `estimated_value` decimal(15,2) DEFAULT NULL AFTER `acquisition_date`,
  ADD COLUMN IF NOT EXISTS `recovery_time` varchar(50) DEFAULT NULL AFTER `replacement_cost`;

-- Mensaje de confirmación
SELECT 'Migración completada exitosamente' as resultado;

-- Verificar estructura de la tabla
DESCRIBE iso27001_assets;
