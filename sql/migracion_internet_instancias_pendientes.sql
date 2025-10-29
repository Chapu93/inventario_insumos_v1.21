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

-- 4. Crear índice para mejorar búsquedas por instancia
ALTER TABLE `sedes_internet` 
ADD INDEX `idx_instancia_pendiente` (`instancia_pendiente`);

-- 5. Crear índice para mejorar búsquedas por estado
ALTER TABLE `sedes_internet` 
ADD INDEX `idx_estado_servicio` (`estado_servicio`);
