-- ====================================
-- MODIFICACIÓN: Permitir NULL en nombre_insumo
-- Fecha: 2025-10-27
-- Descripción: Para tipos NO varios, el campo nombre_insumo (Descripción) es opcional
-- ====================================

-- Modificar columna nombre_insumo para permitir NULL
ALTER TABLE `insumos` 
MODIFY COLUMN `nombre_insumo` VARCHAR(100) NULL DEFAULT NULL;

-- Verificar cambio
SHOW COLUMNS FROM `insumos` LIKE 'nombre_insumo';
