-- Migración: Unificar velocidad de Internet a una sola columna velocidad_mbps
-- Ejecutar en MySQL/MariaDB

START TRANSACTION;

-- 1) Agregar nueva columna velocidad_mbps (si no existe)
ALTER TABLE sedes_internet
  ADD COLUMN IF NOT EXISTS velocidad_mbps INT NULL AFTER tipo_conexion;

-- 2) Migrar datos desde bajada/subida (usa bajada si existe; si no, usa subida; si ambas existen y son >0, toma el mayor)
UPDATE sedes_internet
SET velocidad_mbps = CASE
  WHEN COALESCE(velocidad_bajada_mbps,0) > 0 AND COALESCE(velocidad_subida_mbps,0) > 0 THEN GREATEST(velocidad_bajada_mbps, velocidad_subida_mbps)
  WHEN COALESCE(velocidad_bajada_mbps,0) > 0 THEN velocidad_bajada_mbps
  WHEN COALESCE(velocidad_subida_mbps,0) > 0 THEN velocidad_subida_mbps
  ELSE NULL
END;

-- 3) Eliminar columnas antiguas (opcional). Si tu motor no soporta IF EXISTS, puede requerir try/catch externo
ALTER TABLE sedes_internet
  DROP COLUMN IF EXISTS velocidad_bajada_mbps,
  DROP COLUMN IF EXISTS velocidad_subida_mbps;

COMMIT;
