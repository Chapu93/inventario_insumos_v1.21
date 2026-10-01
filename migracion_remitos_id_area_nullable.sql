-- Migración: Permitir id_area NULL en tabla remitos
-- Fecha: 2026-08-20
-- Motivo: Permitir remitos y relevamientos donde no se especifique área concreta

ALTER TABLE `remitos` MODIFY COLUMN `id_area` INT DEFAULT NULL;
