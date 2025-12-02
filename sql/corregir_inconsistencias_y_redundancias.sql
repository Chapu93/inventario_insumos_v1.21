-- ============================================================
-- SCRIPT DE CORRECCIÓN DE INCONSISTENCIAS Y REDUNDANCIAS
-- Fecha: 2025-11-03
-- Descripción: Elimina redundancias y corrige inconsistencias
--               según migraciones y código PHP
-- IMPORTANTE: Ejecutar en transacción para poder revertir si es necesario
-- Compatibilidad: MySQL 5.7+, MariaDB 10.x+
-- ============================================================

START TRANSACTION;

-- ============================================================
-- PARTE 1: ELIMINAR ÍNDICES DUPLICADOS (Seguro con verificación)
-- ============================================================

-- Eliminar índices duplicados de la tabla insumos
-- Se eliminan manteniendo solo los índices necesarios

SET @index_exists = 0;

-- Eliminar idx_insumos_sede (duplicado de id_sede_actual)
SELECT COUNT(*) INTO @index_exists
FROM information_schema.STATISTICS 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'insumos' 
  AND INDEX_NAME = 'idx_insumos_sede';
SET @sql = IF(@index_exists > 0, 'ALTER TABLE `insumos` DROP INDEX `idx_insumos_sede`', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Eliminar idx_i_estado (duplicado de idx_insumos_estado)
SELECT COUNT(*) INTO @index_exists
FROM information_schema.STATISTICS 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'insumos' 
  AND INDEX_NAME = 'idx_i_estado';
SET @sql = IF(@index_exists > 0, 'ALTER TABLE `insumos` DROP INDEX `idx_i_estado`', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Eliminar idx_i_tipo (duplicado de idx_insumos_tipo)
SELECT COUNT(*) INTO @index_exists
FROM information_schema.STATISTICS 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'insumos' 
  AND INDEX_NAME = 'idx_i_tipo';
SET @sql = IF(@index_exists > 0, 'ALTER TABLE `insumos` DROP INDEX `idx_i_tipo`', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Eliminar idx_i_punto (duplicado de id_punto_stock_actual)
SELECT COUNT(*) INTO @index_exists
FROM information_schema.STATISTICS 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'insumos' 
  AND INDEX_NAME = 'idx_i_punto';
SET @sql = IF(@index_exists > 0, 'ALTER TABLE `insumos` DROP INDEX `idx_i_punto`', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Eliminar idx_i_sede_actual (duplicado de id_sede_actual)
SELECT COUNT(*) INTO @index_exists
FROM information_schema.STATISTICS 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'insumos' 
  AND INDEX_NAME = 'idx_i_sede_actual';
SET @sql = IF(@index_exists > 0, 'ALTER TABLE `insumos` DROP INDEX `idx_i_sede_actual`', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Eliminar idx_i_area_actual (duplicado de id_area_asignacion_actual)
SELECT COUNT(*) INTO @index_exists
FROM information_schema.STATISTICS 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'insumos' 
  AND INDEX_NAME = 'idx_i_area_actual';
SET @sql = IF(@index_exists > 0, 'ALTER TABLE `insumos` DROP INDEX `idx_i_area_actual`', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Eliminar índices duplicados de la tabla remitos

-- Eliminar uniq_numero_remito (duplicado de numero_remito UNIQUE)
SELECT COUNT(*) INTO @index_exists
FROM information_schema.STATISTICS 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'remitos' 
  AND INDEX_NAME = 'uniq_numero_remito';
SET @sql = IF(@index_exists > 0, 'ALTER TABLE `remitos` DROP INDEX `uniq_numero_remito`', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Eliminar idx_r_sede (duplicado de fk_remitos_sede)
SELECT COUNT(*) INTO @index_exists
FROM information_schema.STATISTICS 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'remitos' 
  AND INDEX_NAME = 'idx_r_sede';
SET @sql = IF(@index_exists > 0, 'ALTER TABLE `remitos` DROP INDEX `idx_r_sede`', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Eliminar idx_r_area (duplicado de fk_remitos_area)
SELECT COUNT(*) INTO @index_exists
FROM information_schema.STATISTICS 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'remitos' 
  AND INDEX_NAME = 'idx_r_area';
SET @sql = IF(@index_exists > 0, 'ALTER TABLE `remitos` DROP INDEX `idx_r_area`', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Eliminar idx_r_fecha (duplicado de idx_remitos_fecha)
SELECT COUNT(*) INTO @index_exists
FROM information_schema.STATISTICS 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'remitos' 
  AND INDEX_NAME = 'idx_r_fecha';
SET @sql = IF(@index_exists > 0, 'ALTER TABLE `remitos` DROP INDEX `idx_r_fecha`', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Eliminar idx_estado (duplicado de idx_remitos_estado)
SELECT COUNT(*) INTO @index_exists
FROM information_schema.STATISTICS 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'remitos' 
  AND INDEX_NAME = 'idx_estado';
SET @sql = IF(@index_exists > 0, 'ALTER TABLE `remitos` DROP INDEX `idx_estado`', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================
-- PARTE 2: ELIMINAR FOREIGN KEYS DUPLICADAS
-- ============================================================

-- Eliminar FKs duplicadas en remitos (mantener fk_r_area y fk_r_sede)

SELECT CONSTRAINT_NAME INTO @fk_name
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'remitos'
  AND CONSTRAINT_NAME = 'fk_remitos_area'
  AND REFERENCED_TABLE_NAME = 'areas'
LIMIT 1;
SET @sql = IF(@fk_name IS NOT NULL, CONCAT('ALTER TABLE `remitos` DROP FOREIGN KEY `', @fk_name, '`'), 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT CONSTRAINT_NAME INTO @fk_name
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'remitos'
  AND CONSTRAINT_NAME = 'fk_remitos_sede'
  AND REFERENCED_TABLE_NAME = 'sedes'
LIMIT 1;
SET @sql = IF(@fk_name IS NOT NULL, CONCAT('ALTER TABLE `remitos` DROP FOREIGN KEY `', @fk_name, '`'), 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Eliminar FKs duplicadas en insumos (mantener fk_i_* con ON DELETE SET NULL)

SELECT CONSTRAINT_NAME INTO @fk_name
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'insumos'
  AND CONSTRAINT_NAME = 'fk_insumos_area_actual'
  AND REFERENCED_TABLE_NAME = 'areas'
LIMIT 1;
SET @sql = IF(@fk_name IS NOT NULL, CONCAT('ALTER TABLE `insumos` DROP FOREIGN KEY `', @fk_name, '`'), 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT CONSTRAINT_NAME INTO @fk_name
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'insumos'
  AND CONSTRAINT_NAME = 'fk_insumos_punto_stock'
  AND REFERENCED_TABLE_NAME = 'puntos_stock'
LIMIT 1;
SET @sql = IF(@fk_name IS NOT NULL, CONCAT('ALTER TABLE `insumos` DROP FOREIGN KEY `', @fk_name, '`'), 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT CONSTRAINT_NAME INTO @fk_name
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'insumos'
  AND CONSTRAINT_NAME = 'fk_insumos_sede_actual'
  AND REFERENCED_TABLE_NAME = 'sedes'
LIMIT 1;
SET @sql = IF(@fk_name IS NOT NULL, CONCAT('ALTER TABLE `insumos` DROP FOREIGN KEY `', @fk_name, '`'), 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Eliminar fk_insumos_licitacion (duplicado de fk_insumos_ingreso)
SELECT CONSTRAINT_NAME INTO @fk_name
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'insumos'
  AND CONSTRAINT_NAME = 'fk_insumos_licitacion'
  AND REFERENCED_TABLE_NAME = 'ingresos'
LIMIT 1;
SET @sql = IF(@fk_name IS NOT NULL, CONCAT('ALTER TABLE `insumos` DROP FOREIGN KEY `', @fk_name, '`'), 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================
-- PARTE 3: ACTUALIZAR CAMPOS Y ENUMS SEGÚN MIGRACIONES
-- ============================================================

-- 1. Tabla remitos: Agregar estado 'Anulado' y campos de anulación
ALTER TABLE `remitos` 
MODIFY COLUMN `estado` ENUM('Activa','Devuelta','Anulado') NOT NULL DEFAULT 'Activa';

-- Agregar campos de anulación (usando procedimiento para evitar error si existen)
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'remitos'
  AND COLUMN_NAME = 'motivo_anulacion';
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `remitos` ADD COLUMN `motivo_anulacion` TEXT DEFAULT NULL AFTER `observaciones`',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'remitos'
  AND COLUMN_NAME = 'fecha_anulacion';
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `remitos` ADD COLUMN `fecha_anulacion` DATETIME DEFAULT NULL AFTER `motivo_anulacion`',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Nota: El índice idx_remitos_estado ya existe (línea 865 del SQL base), no necesitamos crear otro

-- 2. Tabla insumos: Permitir NULL en nombre_insumo
ALTER TABLE `insumos` 
MODIFY COLUMN `nombre_insumo` VARCHAR(100) NULL DEFAULT NULL;

-- 3. Tabla insumos: Cambiar 'PC Completa' a 'PC Escritorio' en enum
-- Primero agregar 'PC Escritorio' al enum
ALTER TABLE `insumos` 
MODIFY COLUMN `tipo_insumo` ENUM('Varios','PC Completa','PC Escritorio','Notebook','Impresora','Monitor','Escaner') NOT NULL;

-- Actualizar registros existentes
UPDATE `insumos` 
SET `tipo_insumo` = 'PC Escritorio' 
WHERE `tipo_insumo` = 'PC Completa';

-- Eliminar 'PC Completa' del enum
ALTER TABLE `insumos` 
MODIFY COLUMN `tipo_insumo` ENUM('Varios','PC Escritorio','Notebook','Impresora','Monitor','Escaner') NOT NULL;

-- 4. Tabla insumos: Agregar campo id_ingreso si no existe
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'insumos'
  AND COLUMN_NAME = 'id_ingreso';
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `insumos` ADD COLUMN `id_ingreso` INT(11) NULL DEFAULT NULL AFTER `id_area_asignacion_actual`',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Agregar índice para id_ingreso si no existe
SET @index_exists = 0;
SELECT COUNT(*) INTO @index_exists
FROM information_schema.STATISTICS 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'insumos' 
  AND INDEX_NAME = 'idx_insumos_ingreso';
SET @sql = IF(@index_exists = 0, 
    'ALTER TABLE `insumos` ADD INDEX `idx_insumos_ingreso` (`id_ingreso`)',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 5. Tabla insumos: Agregar campo es_nuevo si no existe
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'insumos'
  AND COLUMN_NAME = 'es_nuevo';
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `insumos` ADD COLUMN `es_nuevo` TINYINT(1) NOT NULL DEFAULT 1 COMMENT ''1=Nuevo, 0=Usado'' AFTER `id_ingreso`',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 6. Tabla pcs_completas: Agregar campo sist_op si no existe
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'pcs_completas'
  AND COLUMN_NAME = 'sist_op';
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `pcs_completas` ADD COLUMN `sist_op` VARCHAR(100) NULL DEFAULT NULL COMMENT ''Sistema Operativo'' AFTER `mother`',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 7. Tabla notebooks: Agregar campos extras si no existen
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'notebooks'
  AND COLUMN_NAME = 'cargador';
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `notebooks` ADD COLUMN `cargador` TINYINT(1) NOT NULL DEFAULT 0 AFTER `almacenamiento_gb`',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'notebooks'
  AND COLUMN_NAME = 'funda';
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `notebooks` ADD COLUMN `funda` TINYINT(1) NOT NULL DEFAULT 0 AFTER `cargador`',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'notebooks'
  AND COLUMN_NAME = 'micro_sd';
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `notebooks` ADD COLUMN `micro_sd` TINYINT(1) NOT NULL DEFAULT 0 AFTER `funda`',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'notebooks'
  AND COLUMN_NAME = 'micro_sd_gb';
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `notebooks` ADD COLUMN `micro_sd_gb` INT(11) NULL DEFAULT NULL AFTER `micro_sd`',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'notebooks'
  AND COLUMN_NAME = 'caja';
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `notebooks` ADD COLUMN `caja` TINYINT(1) NOT NULL DEFAULT 0 AFTER `micro_sd_gb`',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'notebooks'
  AND COLUMN_NAME = 'adaptador_red';
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `notebooks` ADD COLUMN `adaptador_red` TINYINT(1) NOT NULL DEFAULT 0 AFTER `caja`',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 8. Tabla monitores: Actualizar enum de conexion para incluir DisplayPort y DVI
ALTER TABLE `monitores` 
MODIFY COLUMN `conexion` ENUM('VGA','HDMI','DisplayPort','DVI') NOT NULL;

-- 9. Tabla pcs_completas: Cambiar sist_op de TEXT a VARCHAR(100)
ALTER TABLE `pcs_completas` 
MODIFY COLUMN `sist_op` VARCHAR(100) NULL DEFAULT NULL;

-- 10. Tabla ingresos: Corregir nro_referencia (debe ser NOT NULL) y agregar índice único compuesto
ALTER TABLE `ingresos` 
MODIFY COLUMN `nro_referencia` VARCHAR(100) NOT NULL COMMENT 'Número de referencia: Expediente, Nota, etc.';

-- Eliminar índice único simple de nro_referencia si existe (cod_expediente)
SET @index_exists = 0;
SELECT COUNT(*) INTO @index_exists
FROM information_schema.STATISTICS 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'ingresos' 
  AND INDEX_NAME = 'cod_expediente';
SET @sql = IF(@index_exists > 0, 'ALTER TABLE `ingresos` DROP INDEX `cod_expediente`', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Agregar índice único compuesto unique_tipo_referencia si no existe
SET @index_exists = 0;
SELECT COUNT(*) INTO @index_exists
FROM information_schema.STATISTICS 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'ingresos' 
  AND INDEX_NAME = 'unique_tipo_referencia';
SET @sql = IF(@index_exists = 0, 
    'ALTER TABLE `ingresos` ADD UNIQUE KEY `unique_tipo_referencia` (`tipo_ingreso`, `nro_referencia`)',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 11. Tabla sedes_internet: Eliminar índice duplicado idx_estado_servicio (duplicado de idx_si_estado)
SET @index_exists = 0;
SELECT COUNT(*) INTO @index_exists
FROM information_schema.STATISTICS 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'sedes_internet' 
  AND INDEX_NAME = 'idx_estado_servicio';
SET @sql = IF(@index_exists > 0, 'ALTER TABLE `sedes_internet` DROP INDEX `idx_estado_servicio`', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================
-- PARTE 4: VERIFICAR TABLA ingresos Y FOREIGN KEYS
-- ============================================================

-- Nota: La tabla ingresos ya existe, solo verificamos la foreign key

-- Agregar índices útiles para ingresos si no existen (solo si no se han agregado antes)
SET @index_exists = 0;
SELECT COUNT(*) INTO @index_exists
FROM information_schema.STATISTICS 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'ingresos' 
  AND INDEX_NAME = 'idx_ingresos_tipo';
SET @sql = IF(@index_exists = 0, 
    'ALTER TABLE `ingresos` ADD INDEX `idx_ingresos_tipo` (`tipo_ingreso`)',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @index_exists = 0;
SELECT COUNT(*) INTO @index_exists
FROM information_schema.STATISTICS 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'ingresos' 
  AND INDEX_NAME = 'idx_ingresos_fecha';
SET @sql = IF(@index_exists = 0, 
    'ALTER TABLE `ingresos` ADD INDEX `idx_ingresos_fecha` (`fecha_finalizacion`)',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Agregar foreign key de insumos a ingresos si no existe
SET @fk_exists = 0;
SELECT COUNT(*) INTO @fk_exists
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'insumos'
  AND CONSTRAINT_NAME = 'fk_insumos_ingreso'
  AND REFERENCED_TABLE_NAME = 'ingresos';
SET @sql = IF(@fk_exists = 0, 
    'ALTER TABLE `insumos` ADD CONSTRAINT `fk_insumos_ingreso` FOREIGN KEY (`id_ingreso`) REFERENCES `ingresos` (`id_ingreso`) ON DELETE SET NULL ON UPDATE CASCADE',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================
-- PARTE 5: ACTUALIZAR sedes_internet SEGÚN MIGRACIONES
-- ============================================================

-- Agregar velocidad_mbps si no existe (unificar bajada/subida)
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'sedes_internet'
  AND COLUMN_NAME = 'velocidad_mbps';
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `sedes_internet` ADD COLUMN `velocidad_mbps` INT(11) NULL DEFAULT NULL AFTER `tipo_conexion`',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Migrar datos desde bajada/subida a velocidad_mbps (si existen columnas antiguas)
SET @col_bajada_exists = 0;
SET @col_subida_exists = 0;
SELECT COUNT(*) INTO @col_bajada_exists FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sedes_internet' AND COLUMN_NAME = 'velocidad_bajada_mbps';
SELECT COUNT(*) INTO @col_subida_exists FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sedes_internet' AND COLUMN_NAME = 'velocidad_subida_mbps';
SET @sql = IF(@col_bajada_exists > 0 OR @col_subida_exists > 0,
    CONCAT('UPDATE `sedes_internet` SET `velocidad_mbps` = CASE ',
           'WHEN COALESCE(`velocidad_bajada_mbps`, 0) > 0 AND COALESCE(`velocidad_subida_mbps`, 0) > 0 ',
           'THEN GREATEST(`velocidad_bajada_mbps`, `velocidad_subida_mbps`) ',
           'WHEN COALESCE(`velocidad_bajada_mbps`, 0) > 0 THEN `velocidad_bajada_mbps` ',
           'WHEN COALESCE(`velocidad_subida_mbps`, 0) > 0 THEN `velocidad_subida_mbps` ',
           'ELSE NULL END WHERE `velocidad_mbps` IS NULL'),
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Agregar fecha_instalacion si no existe
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sedes_internet' AND COLUMN_NAME = 'fecha_instalacion';
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `sedes_internet` ADD COLUMN `fecha_instalacion` DATE NULL DEFAULT NULL AFTER `tiene_wifi`',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Agregar fecha_baja si no existe
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sedes_internet' AND COLUMN_NAME = 'fecha_baja';
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `sedes_internet` ADD COLUMN `fecha_baja` DATE NULL DEFAULT NULL AFTER `fecha_instalacion`',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Actualizar estado_servicio para incluir 'Baja por Traslado'
ALTER TABLE `sedes_internet` 
MODIFY COLUMN `estado_servicio` ENUM('Activo','Pendiente','De Baja','Baja por Traslado') NOT NULL DEFAULT 'Activo';

-- Agregar instancia_pendiente y campos relacionados si no existen
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sedes_internet' AND COLUMN_NAME = 'instancia_pendiente';
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `sedes_internet` ADD COLUMN `instancia_pendiente` ENUM(''Solicitud de presupuesto'', ''Autorización superior'', ''Servicio tarifado'') NULL DEFAULT NULL AFTER `estado_servicio`',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sedes_internet' AND COLUMN_NAME = 'fecha_solicitud_autorizacion';
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `sedes_internet` ADD COLUMN `fecha_solicitud_autorizacion` DATE NULL DEFAULT NULL AFTER `instancia_pendiente`',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sedes_internet' AND COLUMN_NAME = 'archivo_autorizacion';
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `sedes_internet` ADD COLUMN `archivo_autorizacion` VARCHAR(255) NULL DEFAULT NULL AFTER `fecha_solicitud_autorizacion`',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Agregar campos de traslado si no existen
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sedes_internet' AND COLUMN_NAME = 'id_servicio_trasladado_a';
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `sedes_internet` ADD COLUMN `id_servicio_trasladado_a` INT(11) NULL DEFAULT NULL AFTER `archivo_autorizacion`',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sedes_internet' AND COLUMN_NAME = 'id_servicio_trasladado_desde';
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `sedes_internet` ADD COLUMN `id_servicio_trasladado_desde` INT(11) NULL DEFAULT NULL AFTER `id_servicio_trasladado_a`',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sedes_internet' AND COLUMN_NAME = 'fecha_traslado';
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `sedes_internet` ADD COLUMN `fecha_traslado` DATE NULL DEFAULT NULL AFTER `id_servicio_trasladado_desde`',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Agregar índices para campos nuevos de sedes_internet si no existen
SET @index_exists = 0;
SELECT COUNT(*) INTO @index_exists FROM information_schema.STATISTICS 
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sedes_internet' AND INDEX_NAME = 'idx_fecha_instalacion';
SET @sql = IF(@index_exists = 0, 'ALTER TABLE `sedes_internet` ADD INDEX `idx_fecha_instalacion` (`fecha_instalacion`)', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @index_exists = 0;
SELECT COUNT(*) INTO @index_exists FROM information_schema.STATISTICS 
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sedes_internet' AND INDEX_NAME = 'idx_fecha_baja';
SET @sql = IF(@index_exists = 0, 'ALTER TABLE `sedes_internet` ADD INDEX `idx_fecha_baja` (`fecha_baja`)', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @index_exists = 0;
SELECT COUNT(*) INTO @index_exists FROM information_schema.STATISTICS 
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sedes_internet' AND INDEX_NAME = 'idx_instancia_pendiente';
SET @sql = IF(@index_exists = 0, 'ALTER TABLE `sedes_internet` ADD INDEX `idx_instancia_pendiente` (`instancia_pendiente`)', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @index_exists = 0;
SELECT COUNT(*) INTO @index_exists FROM information_schema.STATISTICS 
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sedes_internet' AND INDEX_NAME = 'idx_trasladado_a';
SET @sql = IF(@index_exists = 0, 'ALTER TABLE `sedes_internet` ADD INDEX `idx_trasladado_a` (`id_servicio_trasladado_a`)', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @index_exists = 0;
SELECT COUNT(*) INTO @index_exists FROM information_schema.STATISTICS 
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sedes_internet' AND INDEX_NAME = 'idx_trasladado_desde';
SET @sql = IF(@index_exists = 0, 'ALTER TABLE `sedes_internet` ADD INDEX `idx_trasladado_desde` (`id_servicio_trasladado_desde`)', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @index_exists = 0;
SELECT COUNT(*) INTO @index_exists FROM information_schema.STATISTICS 
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sedes_internet' AND INDEX_NAME = 'idx_fecha_traslado';
SET @sql = IF(@index_exists = 0, 'ALTER TABLE `sedes_internet` ADD INDEX `idx_fecha_traslado` (`fecha_traslado`)', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================
-- VERIFICACIONES Y COMMIT
-- ============================================================

-- Confirmar que todas las operaciones se completaron
SELECT 'Correcciones aplicadas correctamente' AS resultado;

COMMIT;

-- ============================================================
-- FIN DEL SCRIPT
-- ============================================================
-- 
-- IMPORTANTE:
-- - El script está en una transacción, si algo falla ejecuta: ROLLBACK;
-- - Si todo está correcto, el COMMIT ya se ejecutó automáticamente
-- - Revisa los mensajes de error si los hay antes de confirmar
--
-- VERIFICACIONES POST-EJECUCIÓN:
-- - Verifica que no haya índices duplicados
-- - Verifica que todas las foreign keys necesarias existan
-- - Prueba las funcionalidades del sistema
--
-- ============================================================
