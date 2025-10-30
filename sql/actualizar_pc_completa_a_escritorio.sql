-- ====================================
-- ACTUALIZACIÓN: Renombrar PC Completa a PC Escritorio
-- Fecha: 2025-10-27
-- ====================================

-- Actualizar tipo_insumo en la tabla insumos
UPDATE `insumos` 
SET `tipo_insumo` = 'PC Escritorio' 
WHERE `tipo_insumo` = 'PC Completa';

-- Verificar cambios realizados
SELECT COUNT(*) as total_pcs_escritorio 
FROM `insumos` 
WHERE `tipo_insumo` = 'PC Escritorio';
