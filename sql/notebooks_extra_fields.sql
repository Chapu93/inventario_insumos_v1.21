-- Agregar columnas extras a notebooks
ALTER TABLE notebooks
  ADD COLUMN cargador TINYINT(1) NOT NULL DEFAULT 0 AFTER almacenamiento_gb,
  ADD COLUMN funda TINYINT(1) NOT NULL DEFAULT 0 AFTER cargador,
  ADD COLUMN micro_sd TINYINT(1) NOT NULL DEFAULT 0 AFTER funda,
  ADD COLUMN micro_sd_gb INT NULL AFTER micro_sd,
  ADD COLUMN caja TINYINT(1) NOT NULL DEFAULT 0 AFTER micro_sd_gb,
  ADD COLUMN adaptador_red TINYINT(1) NOT NULL DEFAULT 0 AFTER caja;
