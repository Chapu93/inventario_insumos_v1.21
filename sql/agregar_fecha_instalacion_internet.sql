-- Migración: Agregar campo fecha_instalacion a sedes_internet
-- Fecha: 2025-10-27
-- Descripción: Agrega campo para registrar la fecha de instalación programada
--              cuando el servicio está en estado Pendiente

-- Agregar campo para fecha de instalación
ALTER TABLE `sedes_internet` 
ADD COLUMN `fecha_instalacion` DATE NULL DEFAULT NULL 
COMMENT 'Fecha programada de instalación cuando el estado es Pendiente' 
AFTER `archivo_autorizacion`;

-- Crear índice para mejorar búsquedas por fecha de instalación (solo si no existe)
SET @exist_idx_fecha := (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE table_schema = DATABASE() 
    AND table_name = 'sedes_internet' 
    AND index_name = 'idx_fecha_instalacion'
);

SET @sql_idx_fecha := IF(@exist_idx_fecha = 0, 
    'ALTER TABLE `sedes_internet` ADD INDEX `idx_fecha_instalacion` (`fecha_instalacion`)', 
    'SELECT "El índice idx_fecha_instalacion ya existe" AS mensaje'
);

PREPARE stmt_idx_fecha FROM @sql_idx_fecha;
EXECUTE stmt_idx_fecha;
DEALLOCATE PREPARE stmt_idx_fecha;
