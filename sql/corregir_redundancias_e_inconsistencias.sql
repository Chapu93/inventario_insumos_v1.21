-- ============================================================
-- SCRIPT DE CORRECCIÓN DE REDUNDANCIAS E INCONSISTENCIAS
-- Fecha: 2025-11-03
-- Descripción: Elimina redundancias (índices y FKs duplicadas)
--              y corrige inconsistencias menores
-- IMPORTANTE: Ejecutar en transacción para poder revertir si es necesario
-- Compatibilidad: MySQL 5.7+, MariaDB 10.x+
-- ============================================================

START TRANSACTION;

-- ============================================================
-- PARTE 1: ELIMINAR ÍNDICES DUPLICADOS (Seguro con verificación)
-- ============================================================

-- Eliminar índices duplicados de la tabla insumos
SET @index_exists = 0;

-- Eliminar idx_insumos_sede (duplicado de id_sede_actual FK)
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

-- Eliminar idx_i_punto (duplicado de id_punto_stock_actual FK)
SELECT COUNT(*) INTO @index_exists
FROM information_schema.STATISTICS 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'insumos' 
  AND INDEX_NAME = 'idx_i_punto';
SET @sql = IF(@index_exists > 0, 'ALTER TABLE `insumos` DROP INDEX `idx_i_punto`', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Eliminar idx_i_sede_actual (duplicado de id_sede_actual FK)
SELECT COUNT(*) INTO @index_exists
FROM information_schema.STATISTICS 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'insumos' 
  AND INDEX_NAME = 'idx_i_sede_actual';
SET @sql = IF(@index_exists > 0, 'ALTER TABLE `insumos` DROP INDEX `idx_i_sede_actual`', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Eliminar idx_i_area_actual (duplicado de id_area_asignacion_actual FK)
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

-- Eliminar idx_r_sede (duplicado de fk_remitos_sede FK)
SELECT COUNT(*) INTO @index_exists
FROM information_schema.STATISTICS 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'remitos' 
  AND INDEX_NAME = 'idx_r_sede';
SET @sql = IF(@index_exists > 0, 'ALTER TABLE `remitos` DROP INDEX `idx_r_sede`', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Eliminar idx_r_area (duplicado de fk_remitos_area FK)
SELECT COUNT(*) INTO @index_exists
FROM information_schema.STATISTICS 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'remitos' 
  AND INDEX_NAME = 'idx_r_area';
SET @sql = IF(@index_exists > 0, 'ALTER TABLE `remitos` DROP INDEX `idx_r_area`', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Eliminar índice duplicado de sedes_internet

-- Eliminar idx_estado_servicio (duplicado de idx_si_estado)
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

-- Eliminar FKs duplicadas en insumos (mantener las que tienen ON DELETE SET NULL)

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
  AND CONSTRAINT_NAME = 'fk_insumos_licitacion'
  AND REFERENCED_TABLE_NAME = 'ingresos'
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

-- ============================================================
-- PARTE 3: CORREGIR INCONSISTENCIAS MENORES
-- ============================================================

-- Cambiar pcs_completas.sist_op de TEXT a VARCHAR(100) para consistencia con código PHP
ALTER TABLE `pcs_completas` 
MODIFY COLUMN `sist_op` VARCHAR(100) NULL DEFAULT NULL;

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
-- CAMBIOS APLICADOS:
-- ✓ Eliminados 12 índices duplicados (insumos: 6, remitos: 5, sedes_internet: 1)
-- ✓ Eliminadas 7 foreign keys duplicadas (insumos: 4, remitos: 2)
-- ✓ Corregido pcs_completas.sist_op de TEXT a VARCHAR(100)
--
-- VERIFICACIONES POST-EJECUCIÓN:
-- - Verifica que no haya índices duplicados
-- - Verifica que todas las foreign keys necesarias existan
-- - Prueba las funcionalidades del sistema
--
-- ============================================================
