-- ============================================================================
-- SCRIPT DE LIMPIEZA PARA PRODUCCIÓN
-- ============================================================================
-- Este script limpia los datos transaccionales pero mantiene la configuración
-- Asegura que el primer remito sea 0001_YYYY
-- 
-- IMPORTANTE: Hacer backup antes de ejecutar
-- ============================================================================

-- PASO 1: Desactivar verificación de claves foráneas temporalmente
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================================
-- LIMPIAR DATOS TRANSACCIONALES (Operaciones diarias)
-- ============================================================================

-- Limpiar remitos y su detalle (asignaciones)
TRUNCATE TABLE `remitos_detalle`;
TRUNCATE TABLE `remitos`;

-- Limpiar secuencia de remitos para que inicie en 1
TRUNCATE TABLE `remito_secuencia`;
-- Nota: No es necesario insertar nada. La función generarNumeroRemito() 
--       creará automáticamente el registro con ultimo=0 y el primer remito será 0001

-- Limpiar historial de bajas
TRUNCATE TABLE `historial_bajas`;

-- Limpiar tablas de ingresos
TRUNCATE TABLE `ingresos_detalle`;
TRUNCATE TABLE `ingresos`;

-- ============================================================================
-- RESETEAR INSUMOS A ESTADO INICIAL
-- ============================================================================

-- Opción A: Si quieres ELIMINAR todos los insumos
-- TRUNCATE TABLE `insumos`;
-- TRUNCATE TABLE `notebooks`;
-- TRUNCATE TABLE `pc_completas`;
-- TRUNCATE TABLE `monitores`;
-- TRUNCATE TABLE `impresoras`;
-- TRUNCATE TABLE `escaneres`;

-- Opción B: Si quieres MANTENER los insumos pero resetear su estado
UPDATE `insumos` SET 
    estado = 'Disponible',
    id_sede_actual = NULL,
    id_area_asignacion_actual = NULL;

-- ============================================================================
-- MANTENER CONFIGURACIÓN (Tablas maestras)
-- ============================================================================
-- Las siguientes tablas NO se limpian porque contienen configuración:
-- - areas
-- - sedes
-- - sede_areas
-- - localidades
-- - zonas
-- - puntos_stock
-- - licitaciones
-- - Tablas de telecomunicaciones (telecom_*)

-- ============================================================================
-- PASO 2: Reactivar verificación de claves foráneas
-- ============================================================================
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- VERIFICACIÓN POST-LIMPIEZA
-- ============================================================================

-- Verificar que las tablas transaccionales están vacías
SELECT 'Verificando limpieza...' AS mensaje;

SELECT 
    'remitos' AS tabla,
    COUNT(*) AS registros,
    CASE WHEN COUNT(*) = 0 THEN '✓ OK' ELSE '✗ ERROR' END AS estado
FROM remitos
UNION ALL
SELECT 
    'remitos_detalle' AS tabla,
    COUNT(*) AS registros,
    CASE WHEN COUNT(*) = 0 THEN '✓ OK' ELSE '✗ ERROR' END AS estado
FROM remitos_detalle
UNION ALL
SELECT 
    'remito_secuencia' AS tabla,
    COUNT(*) AS registros,
    CASE WHEN COUNT(*) = 0 THEN '✓ OK (iniciará en 1)' ELSE '⚠ ADVERTENCIA: Debería estar vacía' END AS estado
FROM remito_secuencia
UNION ALL
SELECT 
    'insumos' AS tabla,
    COUNT(*) AS registros,
    'ℹ️ INFO' AS estado
FROM insumos
UNION ALL
SELECT 
    'sedes' AS tabla,
    COUNT(*) AS registros,
    'ℹ️ CONFIG (mantiene)' AS estado
FROM sedes
UNION ALL
SELECT 
    'areas' AS tabla,
    COUNT(*) AS registros,
    'ℹ️ CONFIG (mantiene)' AS estado
FROM areas;

-- Verificar que los insumos están disponibles
SELECT 
    estado,
    COUNT(*) as cantidad
FROM insumos
GROUP BY estado;

-- ============================================================================
-- NOTAS IMPORTANTES
-- ============================================================================
-- 
-- 1. El primer remito será: 0001_2025 (o el año actual)
--    La función generarNumeroRemito() automáticamente:
--    - Inserta registro con ultimo=0 si no existe
--    - Suma 1, entonces el primer número será 1
--    - Retorna formato: 0001_YYYY
--
-- 2. Si ejecutas este script a mitad de año:
--    - Los remitos empezarán en 0001_2025
--    - Al cambiar de año, automáticamente iniciará en 0001_2026
--
-- 3. Si necesitas forzar un número específico:
--    INSERT INTO remito_secuencia (anio, ultimo) VALUES (2025, 0);
--    -- El siguiente remito será 0001_2025
--
-- 4. Las tablas de configuración se mantienen:
--    - Sedes, áreas, localidades, zonas
--    - Licitaciones
--    - Telecomunicaciones
--    - Puntos de stock
--
-- ============================================================================
