-- =============================================================================
-- MIGRACIÓN: Unificación de directorio de uploads
-- Fecha: 2026-03-11
-- Descripción: Actualiza rutas de archivos guardadas en la BD para reflejar
--              la nueva estructura unificada bajo /uploads/
-- =============================================================================

-- IMPORTANTE: Ejecutar este script DESPUÉS de mover físicamente los archivos.
-- Ver: setup/migrar_uploads.sh

-- 1. Planos de sedes (sedes_planos)
--    Antes:  public/uploads/planos/archivo.pdf
--    Ahora:  uploads/planos/archivo.pdf
UPDATE sedes_planos 
SET archivo = REPLACE(archivo, 'public/uploads/planos/', 'uploads/planos/')
WHERE archivo LIKE 'public/uploads/planos/%';

-- 2. Autorizaciones de Internet (sedes_internet)
--    Antes:  public/uploads/autorizaciones_internet/archivo.pdf
--    Ahora:  uploads/telecom/archivo.pdf
UPDATE sedes_internet 
SET archivo_autorizacion = REPLACE(
    archivo_autorizacion, 
    'public/uploads/autorizaciones_internet/', 
    'uploads/telecom/'
)
WHERE archivo_autorizacion LIKE 'public/uploads/autorizaciones_internet/%';

-- Verificación: mostrar registros actualizados
SELECT 'sedes_planos' as tabla, COUNT(*) as actualizados 
FROM sedes_planos WHERE archivo LIKE 'uploads/planos/%'
UNION ALL
SELECT 'sedes_internet', COUNT(*) 
FROM sedes_internet WHERE archivo_autorizacion LIKE 'uploads/telecom/%';
