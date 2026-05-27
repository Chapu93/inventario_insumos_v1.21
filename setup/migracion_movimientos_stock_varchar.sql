-- MIGRACIÓN: Flexibilizar campos ENUM en tabla insumos_movimientos_stock
-- Autor: Antigravity
-- Fecha: 2026-05-27

-- Cambiar columnas tipo ENUM a VARCHAR(50) para evitar errores de truncado ('Warning: 1265 Data truncated') 
-- al insertar nuevos tipos de movimientos ('devolucion', 'ajuste_manual') u ubicaciones ('edicion', 'asignacion').

ALTER TABLE insumos_movimientos_stock 
    MODIFY COLUMN tipo_movimiento VARCHAR(50) NOT NULL COMMENT 'Tipo de movimiento de stock',
    MODIFY COLUMN ubicacion_origen VARCHAR(50) NOT NULL COMMENT 'Ubicación de origen del stock',
    MODIFY COLUMN ubicacion_destino VARCHAR(50) NOT NULL COMMENT 'Ubicación de destino del stock';
