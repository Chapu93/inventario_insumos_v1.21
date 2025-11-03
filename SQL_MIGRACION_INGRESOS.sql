-- ============================================================
-- MIGRACIÓN 006: LICITACIONES → INGRESOS
-- Fecha: 21 de octubre de 2025
-- Descripción: Renombrar licitaciones a ingresos y agregar tipo
-- ============================================================

-- IMPORTANTE: Ejecutar todo en una transacción
START TRANSACTION;

-- 1. Renombrar tabla licitaciones a ingresos
RENAME TABLE licitaciones TO ingresos;

-- 2. Renombrar columna id_licitacion a id_ingreso en tabla ingresos
ALTER TABLE ingresos 
CHANGE COLUMN id_licitacion id_ingreso INT AUTO_INCREMENT;

-- 3. Agregar campo tipo_ingreso (NUEVO)
ALTER TABLE ingresos 
ADD COLUMN tipo_ingreso ENUM('fondos', 'compra_directa', 'licitacion', 'otros') 
DEFAULT 'licitacion' 
COMMENT 'Tipo de ingreso: fondos, compra_directa, licitacion, otros'
AFTER id_ingreso;

-- 4. Renombrar cod_expediente a nro_referencia (más genérico)
ALTER TABLE ingresos 
CHANGE COLUMN cod_expediente nro_referencia VARCHAR(100) NOT NULL
COMMENT 'Número de referencia: Expediente, Nota, etc.';

-- 5. Eliminar foreign key antigua en tabla insumos (si existe)
-- Nota: El nombre puede variar según cómo se creó
SET @FK_NAME = (
    SELECT CONSTRAINT_NAME 
    FROM information_schema.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = DATABASE() 
      AND TABLE_NAME = 'insumos' 
      AND COLUMN_NAME = 'id_licitacion'
      AND CONSTRAINT_NAME != 'PRIMARY'
    LIMIT 1
);

SET @SQL = IF(@FK_NAME IS NOT NULL, 
    CONCAT('ALTER TABLE insumos DROP FOREIGN KEY ', @FK_NAME), 
    'SELECT "No foreign key to drop"');
    
PREPARE stmt FROM @SQL;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 6. Renombrar columna id_licitacion a id_ingreso en tabla insumos
ALTER TABLE insumos 
CHANGE COLUMN id_licitacion id_ingreso INT NULL
COMMENT 'ID del ingreso asociado (fondos, licitación, etc.)';

-- 7. Crear nueva foreign key
ALTER TABLE insumos 
ADD CONSTRAINT fk_insumos_ingreso 
FOREIGN KEY (id_ingreso) 
REFERENCES ingresos(id_ingreso) 
ON DELETE SET NULL 
ON UPDATE CASCADE;

-- 8. Actualizar datos existentes: todas las licitaciones pasan a tipo 'licitacion'
UPDATE ingresos 
SET tipo_ingreso = 'licitacion' 
WHERE tipo_ingreso IS NULL OR tipo_ingreso = '';

COMMIT;

-- ============================================================
-- VERIFICACIÓN POST-MIGRACIÓN
-- ============================================================

-- Ver estructura de tabla ingresos
SHOW CREATE TABLE ingresos;

-- Ver estructura de tabla insumos (columna id_ingreso)
SHOW COLUMNS FROM insumos LIKE 'id_ingreso';

-- Contar registros migrados
SELECT 
    COUNT(*) as total_ingresos,
    SUM(CASE WHEN tipo_ingreso = 'fondos' THEN 1 ELSE 0 END) as fondos,
    SUM(CASE WHEN tipo_ingreso = 'compra_directa' THEN 1 ELSE 0 END) as compra_directa,
    SUM(CASE WHEN tipo_ingreso = 'licitacion' THEN 1 ELSE 0 END) as licitacion,
    SUM(CASE WHEN tipo_ingreso = 'otros' THEN 1 ELSE 0 END) as otros
FROM ingresos;

-- Contar insumos asociados a ingresos
SELECT COUNT(*) as insumos_con_ingreso 
FROM insumos 
WHERE id_ingreso IS NOT NULL;

-- ============================================================
-- RESULTADO ESPERADO
-- ============================================================
-- 
-- Tabla ingresos:
--   - id_ingreso (INT, PK, AUTO_INCREMENT)
--   - tipo_ingreso (ENUM: fondos, compra_directa, licitacion, otros)
--   - nro_referencia (VARCHAR 100) - antes cod_expediente
--   - fecha_finalizacion (DATE)
--   - descripcion (TEXT)
--   - created_at (TIMESTAMP)
--
-- Tabla insumos:
--   - id_ingreso (INT, NULL) - antes id_licitacion
--   - FK: fk_insumos_ingreso → ingresos(id_ingreso)
--
-- ============================================================
