-- Migración: Agregar campos de instancias para estado Pendiente
-- Fecha: 2025-10-27
-- Descripción: Agrega campos para gestionar instancias del estado "Pendiente" 
--              en servicios de internet (Solicitud de presupuesto, Autorización superior, Servicio tarifado)

-- 1. Agregar campo para la instancia del estado pendiente
ALTER TABLE `sedes_internet` 
ADD COLUMN `instancia_pendiente` ENUM('Solicitud de presupuesto', 'Autorización superior', 'Servicio tarifado') NULL DEFAULT NULL 
COMMENT 'Instancia específica cuando el estado es Pendiente' 
AFTER `estado_servicio`;

-- 2. Agregar campo para fecha de solicitud de autorización
ALTER TABLE `sedes_internet` 
ADD COLUMN `fecha_solicitud_autorizacion` DATE NULL DEFAULT NULL 
COMMENT 'Fecha de solicitud cuando la instancia es Autorización superior' 
AFTER `instancia_pendiente`;

-- 3. Agregar campo para archivo de autorización (PDF)
ALTER TABLE `sedes_internet` 
ADD COLUMN `archivo_autorizacion` VARCHAR(255) NULL DEFAULT NULL 
COMMENT 'Ruta del archivo PDF de autorización superior' 
AFTER `fecha_solicitud_autorizacion`;

-- 4. Crear índice para mejorar búsquedas por instancia (solo si no existe)
SET @exist_idx_instancia := (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE table_schema = DATABASE() 
    AND table_name = 'sedes_internet' 
    AND index_name = 'idx_instancia_pendiente'
);

SET @sql_idx_instancia := IF(@exist_idx_instancia = 0, 
    'ALTER TABLE `sedes_internet` ADD INDEX `idx_instancia_pendiente` (`instancia_pendiente`)', 
    'SELECT "El índice idx_instancia_pendiente ya existe" AS mensaje'
);

PREPARE stmt_idx_instancia FROM @sql_idx_instancia;
EXECUTE stmt_idx_instancia;
DEALLOCATE PREPARE stmt_idx_instancia;

-- 5. Crear índice para mejorar búsquedas por estado (solo si no existe)
SET @exist_idx_estado := (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE table_schema = DATABASE() 
    AND table_name = 'sedes_internet' 
    AND index_name = 'idx_estado_servicio'
);

SET @sql_idx_estado := IF(@exist_idx_estado = 0, 
    'ALTER TABLE `sedes_internet` ADD INDEX `idx_estado_servicio` (`estado_servicio`)', 
    'SELECT "El índice idx_estado_servicio ya existe" AS mensaje'
);

PREPARE stmt_idx_estado FROM @sql_idx_estado;
EXECUTE stmt_idx_estado;
DEALLOCATE PREPARE stmt_idx_estado;
