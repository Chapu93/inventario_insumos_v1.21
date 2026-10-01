-- SITIA: Migración para agregar soporte de baja lógica (activo/inactivo) en sedes
-- Permite desactivar sedes sin romper la integridad referencial de remitos o historial previo.

ALTER TABLE sedes 
ADD COLUMN activo TINYINT(1) NOT NULL DEFAULT 1 AFTER observaciones;
