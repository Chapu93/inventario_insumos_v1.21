-- Migración: Agregar campo fecha_instalacion a sedes_internet
-- Fecha: 2025-10-27
-- Descripción: Agrega campo para registrar la fecha de instalación programada
--              cuando el servicio está en estado Pendiente

-- Agregar campo para fecha de instalación
ALTER TABLE `sedes_internet` 
ADD COLUMN `fecha_instalacion` DATE NULL DEFAULT NULL 
COMMENT 'Fecha programada de instalación cuando el estado es Pendiente' 
AFTER `archivo_autorizacion`;

-- Crear índice para mejorar búsquedas por fecha de instalación
ALTER TABLE `sedes_internet` 
ADD INDEX `idx_fecha_instalacion` (`fecha_instalacion`);
