-- Migración para agregar funcionalidad de remitos anulados
-- Fecha: 2025-10-27

-- Paso 1: Modificar el ENUM del estado de remitos para incluir 'Anulado'
ALTER TABLE `remitos` 
MODIFY COLUMN `estado` ENUM('Activa','Devuelta','Anulado') NOT NULL DEFAULT 'Activa';

-- Paso 2: Agregar campos para almacenar información de anulación
ALTER TABLE `remitos` 
ADD COLUMN `motivo_anulacion` TEXT DEFAULT NULL AFTER `observaciones`,
ADD COLUMN `fecha_anulacion` DATETIME DEFAULT NULL AFTER `motivo_anulacion`;

-- Paso 3: Crear índice para mejorar búsquedas por estado
ALTER TABLE `remitos` 
ADD INDEX `idx_estado` (`estado`);

-- Comentarios:
-- - Los remitos ahora pueden tener 3 estados: Activa, Devuelta, Anulado
-- - motivo_anulacion: Guarda el motivo por el cual se anuló el remito
-- - fecha_anulacion: Registra cuándo fue anulado el remito
-- - El índice idx_estado mejora el rendimiento al filtrar remitos por estado
