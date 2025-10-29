-- Migración: Agregar campo fecha_baja a sedes_internet
-- Fecha: 2025-10-27
-- Descripción: Agrega campo para registrar automáticamente la fecha
--              cuando el servicio pasa a estado "De Baja"

-- Agregar campo para fecha de baja
ALTER TABLE `sedes_internet` 
ADD COLUMN `fecha_baja` DATE NULL DEFAULT NULL 
COMMENT 'Fecha en que el servicio pasó a estado De Baja' 
AFTER `fecha_instalacion`;

-- Crear índice para mejorar búsquedas por fecha de baja (solo si no existe)
SET @exist_idx_baja := (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE table_schema = DATABASE() 
    AND table_name = 'sedes_internet' 
    AND index_name = 'idx_fecha_baja'
);

SET @sql_idx_baja := IF(@exist_idx_baja = 0, 
    'ALTER TABLE `sedes_internet` ADD INDEX `idx_fecha_baja` (`fecha_baja`)', 
    'SELECT "El índice idx_fecha_baja ya existe" AS mensaje'
);

PREPARE stmt_idx_baja FROM @sql_idx_baja;
EXECUTE stmt_idx_baja;
DEALLOCATE PREPARE stmt_idx_baja;
