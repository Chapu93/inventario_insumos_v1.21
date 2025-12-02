-- ============================================================
-- MIGRACIÓN: Sistema de Stock Dual (Oficina + Depósito)
-- Fecha: 2025-11-05
-- Descripción: Permite gestionar stock separado de oficina y 
--              depósito para insumos tipo "Varios"
-- ============================================================

START TRANSACTION;

-- ============================================================
-- PARTE 1: AGREGAR CAMPOS DE STOCK DUAL A TABLA INSUMOS
-- ============================================================

-- Agregar campo cantidad_oficina (stock disponible para asignaciones)
ALTER TABLE `insumos` 
ADD COLUMN `cantidad_oficina` INT(11) NULL DEFAULT NULL 
COMMENT 'Stock disponible en oficina para asignaciones inmediatas (solo tipo Varios)' 
AFTER `cantidad`;

-- Agregar campo cantidad_deposito (stock de reserva)
ALTER TABLE `insumos` 
ADD COLUMN `cantidad_deposito` INT(11) NULL DEFAULT NULL 
COMMENT 'Stock en depósito, requiere reposición a oficina (solo tipo Varios)' 
AFTER `cantidad_oficina`;

-- Crear índice para búsquedas por cantidad_oficina
ALTER TABLE `insumos` 
ADD INDEX `idx_cantidad_oficina` (`cantidad_oficina`);

-- ============================================================
-- PARTE 2: MIGRAR DATOS EXISTENTES
-- ============================================================

-- Todos los insumos "Varios" existentes se consideran en oficina
-- El depósito empieza en 0 para registros existentes
UPDATE `insumos` 
SET 
  `cantidad_oficina` = `cantidad`,
  `cantidad_deposito` = 0
WHERE `tipo_insumo` = 'Varios' 
  AND `cantidad_oficina` IS NULL;

-- Los insumos NO "Varios" mantienen NULL en ambos campos
-- (no utilizan el sistema de stock dual)

-- ============================================================
-- PARTE 3: CREAR TABLA DE HISTORIAL DE MOVIMIENTOS
-- ============================================================

CREATE TABLE IF NOT EXISTS `insumos_movimientos_stock` (
  `id_movimiento` INT(11) NOT NULL AUTO_INCREMENT,
  `id_insumo` INT(11) NOT NULL,
  `tipo_movimiento` ENUM(
    'reposicion_oficina',    -- Mover de depósito a oficina
    'devolucion_a_deposito', -- Mover de oficina a depósito
    'ajuste_manual',         -- Ajuste manual por inventario
    'ingreso_nuevo'          -- Nuevo ingreso de stock
  ) NOT NULL,
  `cantidad_movida` INT(11) NOT NULL COMMENT 'Cantidad trasladada/ajustada',
  `ubicacion_origen` ENUM('deposito', 'oficina', 'externo', 'N/A') NOT NULL,
  `ubicacion_destino` ENUM('deposito', 'oficina', 'externo', 'N/A') NOT NULL,
  
  -- Snapshot del estado antes del movimiento
  `cantidad_oficina_antes` INT(11) NOT NULL,
  `cantidad_deposito_antes` INT(11) NOT NULL,
  
  -- Snapshot del estado después del movimiento
  `cantidad_oficina_despues` INT(11) NOT NULL,
  `cantidad_deposito_despues` INT(11) NOT NULL,
  
  `usuario` VARCHAR(100) NULL COMMENT 'Usuario que realizó el movimiento',
  `observacion` TEXT NULL,
  `fecha_movimiento` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`id_movimiento`),
  KEY `idx_movimientos_insumo` (`id_insumo`),
  KEY `idx_movimientos_fecha` (`fecha_movimiento`),
  KEY `idx_movimientos_tipo` (`tipo_movimiento`),
  
  CONSTRAINT `fk_movimientos_insumo` 
    FOREIGN KEY (`id_insumo`) 
    REFERENCES `insumos` (`id_insumo`) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Historial de movimientos de stock entre oficina y depósito';

-- ============================================================
-- PARTE 4: ACTUALIZAR CAMPO cantidad COMO CALCULADO
-- ============================================================

-- Nota: El campo `cantidad` existente seguirá siendo la suma de:
-- - cantidad_oficina + cantidad_deposito (para tipo Varios)
-- - cantidad original (para otros tipos)

-- Crear trigger para mantener sincronizado el campo cantidad
DELIMITER $$

DROP TRIGGER IF EXISTS `trg_insumos_sync_cantidad_insert`$$
CREATE TRIGGER `trg_insumos_sync_cantidad_insert`
BEFORE INSERT ON `insumos`
FOR EACH ROW
BEGIN
  -- Para tipo Varios, calcular cantidad total
  IF NEW.tipo_insumo = 'Varios' THEN
    IF NEW.cantidad_oficina IS NULL THEN
      SET NEW.cantidad_oficina = 0;
    END IF;
    IF NEW.cantidad_deposito IS NULL THEN
      SET NEW.cantidad_deposito = 0;
    END IF;
    SET NEW.cantidad = NEW.cantidad_oficina + NEW.cantidad_deposito;
  END IF;
END$$

DROP TRIGGER IF EXISTS `trg_insumos_sync_cantidad_update`$$
CREATE TRIGGER `trg_insumos_sync_cantidad_update`
BEFORE UPDATE ON `insumos`
FOR EACH ROW
BEGIN
  -- Para tipo Varios, calcular cantidad total
  IF NEW.tipo_insumo = 'Varios' THEN
    IF NEW.cantidad_oficina IS NULL THEN
      SET NEW.cantidad_oficina = 0;
    END IF;
    IF NEW.cantidad_deposito IS NULL THEN
      SET NEW.cantidad_deposito = 0;
    END IF;
    SET NEW.cantidad = NEW.cantidad_oficina + NEW.cantidad_deposito;
  END IF;
END$$

DELIMITER ;

-- ============================================================
-- VERIFICACIONES Y COMMIT
-- ============================================================

-- Confirmar que todas las operaciones se completaron
SELECT 'Migración de stock dual completada correctamente' AS resultado;

-- Mostrar resumen de stock actual
SELECT 
  'Varios' AS tipo,
  COUNT(*) AS total_registros,
  SUM(cantidad_oficina) AS total_oficina,
  SUM(cantidad_deposito) AS total_deposito,
  SUM(cantidad) AS total_sistema
FROM insumos 
WHERE tipo_insumo = 'Varios';

COMMIT;

-- ============================================================
-- FIN DEL SCRIPT
-- ============================================================
-- 
-- VERIFICACIONES POST-EJECUCIÓN:
-- 
-- 1. Verificar campos nuevos:
--    SHOW COLUMNS FROM insumos LIKE 'cantidad_%';
-- 
-- 2. Verificar tabla de movimientos:
--    SHOW CREATE TABLE insumos_movimientos_stock;
-- 
-- 3. Verificar triggers:
--    SHOW TRIGGERS WHERE `Table` = 'insumos';
-- 
-- 4. Verificar datos migrados:
--    SELECT id_insumo, nombre_insumo, cantidad, cantidad_oficina, cantidad_deposito
--    FROM insumos WHERE tipo_insumo = 'Varios' LIMIT 10;
-- 
-- ============================================================
