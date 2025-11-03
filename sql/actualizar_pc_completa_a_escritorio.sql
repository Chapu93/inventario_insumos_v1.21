-- ====================================
-- ACTUALIZACIÓN: Renombrar PC Completa a PC Escritorio
-- Fecha: 2025-10-27
-- ====================================

-- PASO 1: Agregar nuevo valor 'PC Escritorio' al ENUM
-- (Mantener 'PC Completa' temporalmente para no perder datos)
ALTER TABLE `insumos` 
MODIFY COLUMN `tipo_insumo` ENUM(
    'Varios',
    'PC Completa',
    'PC Escritorio',
    'Notebook',
    'Impresora',
    'Monitor',
    'Escaner'
) NOT NULL;

-- PASO 2: Actualizar todos los registros existentes
UPDATE `insumos` 
SET `tipo_insumo` = 'PC Escritorio' 
WHERE `tipo_insumo` = 'PC Completa';

-- PASO 3: Eliminar 'PC Completa' del ENUM (ya no se usa)
ALTER TABLE `insumos` 
MODIFY COLUMN `tipo_insumo` ENUM(
    'Varios',
    'PC Escritorio',
    'Notebook',
    'Impresora',
    'Monitor',
    'Escaner'
) NOT NULL;

-- Verificar cambios realizados
SELECT 
    COUNT(*) as total_pcs_escritorio,
    (SELECT COUNT(*) FROM insumos WHERE tipo_insumo = 'PC Completa') as pcs_completas_restantes
FROM `insumos` 
WHERE `tipo_insumo` = 'PC Escritorio';
