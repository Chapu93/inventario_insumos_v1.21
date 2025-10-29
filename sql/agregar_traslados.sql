-- ====================================
-- MIGRACIÓN: Sistema de Traslados
-- Fecha: 2025-10-27
-- ====================================

-- 1. Agregar nuevo estado "Baja por Traslado"
ALTER TABLE `sedes_internet` 
MODIFY COLUMN `estado_servicio` ENUM('Activo', 'Pendiente', 'De Baja', 'Baja por Traslado') 
NOT NULL DEFAULT 'Pendiente';

-- 2. Vincular con nuevo servicio creado tras traslado
ALTER TABLE `sedes_internet` 
ADD COLUMN `id_servicio_trasladado_a` INT NULL DEFAULT NULL 
COMMENT 'ID del nuevo servicio creado tras el traslado'
AFTER `observaciones`;

-- 3. Vincular con servicio anterior (si proviene de traslado)
ALTER TABLE `sedes_internet` 
ADD COLUMN `id_servicio_trasladado_desde` INT NULL DEFAULT NULL 
COMMENT 'ID del servicio anterior del cual proviene este traslado'
AFTER `id_servicio_trasladado_a`;

-- 4. Fecha en que se realizó el traslado/baja
ALTER TABLE `sedes_internet` 
ADD COLUMN `fecha_traslado` DATE NULL DEFAULT NULL 
COMMENT 'Fecha en que se dio de baja por traslado'
AFTER `fecha_baja`;

-- 5. PDF de autorización del traslado
ALTER TABLE `sedes_internet` 
ADD COLUMN `archivo_autorizacion_traslado` VARCHAR(255) NULL DEFAULT NULL 
COMMENT 'PDF de autorización del traslado'
AFTER `archivo_autorizacion`;

-- 6. Crear índices para mejorar performance
-- Verificar si no existen antes de crear (idempotente)
SET @exist_trasladado_a := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE table_schema = DATABASE() 
    AND table_name = 'sedes_internet' 
    AND index_name = 'idx_trasladado_a'
);

SET @sql_trasladado_a = IF(
    @exist_trasladado_a = 0,
    'ALTER TABLE `sedes_internet` ADD INDEX `idx_trasladado_a` (`id_servicio_trasladado_a`)',
    'SELECT "Índice idx_trasladado_a ya existe" AS info'
);
PREPARE stmt_trasladado_a FROM @sql_trasladado_a;
EXECUTE stmt_trasladado_a;
DEALLOCATE PREPARE stmt_trasladado_a;

SET @exist_trasladado_desde := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE table_schema = DATABASE() 
    AND table_name = 'sedes_internet' 
    AND index_name = 'idx_trasladado_desde'
);

SET @sql_trasladado_desde = IF(
    @exist_trasladado_desde = 0,
    'ALTER TABLE `sedes_internet` ADD INDEX `idx_trasladado_desde` (`id_servicio_trasladado_desde`)',
    'SELECT "Índice idx_trasladado_desde ya existe" AS info'
);
PREPARE stmt_trasladado_desde FROM @sql_trasladado_desde;
EXECUTE stmt_trasladado_desde;
DEALLOCATE PREPARE stmt_trasladado_desde;

SET @exist_fecha_traslado := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE table_schema = DATABASE() 
    AND table_name = 'sedes_internet' 
    AND index_name = 'idx_fecha_traslado'
);

SET @sql_fecha_traslado = IF(
    @exist_fecha_traslado = 0,
    'ALTER TABLE `sedes_internet` ADD INDEX `idx_fecha_traslado` (`fecha_traslado`)',
    'SELECT "Índice idx_fecha_traslado ya existe" AS info'
);
PREPARE stmt_fecha_traslado FROM @sql_fecha_traslado;
EXECUTE stmt_fecha_traslado;
DEALLOCATE PREPARE stmt_fecha_traslado;
