-- ==============================================================================
-- SITIA - Migración de Almacenamiento y Tipos de Disco (PC y Notebooks)
-- Compatible con MariaDB 10.4+ y MySQL 8+
-- ==============================================================================

-- 1. Respaldo automático preventivo de tablas afectadas
CREATE TABLE IF NOT EXISTS pcs_completas_backup_disco AS SELECT * FROM pcs_completas;
CREATE TABLE IF NOT EXISTS notebooks_backup_disco AS SELECT * FROM notebooks;

-- 2. Creación de la tabla catálogo para opciones de almacenamiento
CREATE TABLE IF NOT EXISTS `opciones_almacenamiento` (
  `id_opcion` INT NOT NULL AUTO_INCREMENT,
  `capacidad_gb` INT NOT NULL,
  `etiqueta` VARCHAR(50) COLLATE utf8mb4_general_ci NOT NULL,
  `orden` INT NOT NULL DEFAULT 0,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_opcion`),
  UNIQUE KEY `uk_capacidad` (`capacidad_gb`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3. Limpieza de capacidades removidas y poblado de capacidades estándar (idempotente)
DELETE FROM `opciones_almacenamiento` WHERE `capacidad_gb` IN (64, 120, 128, 240, 250, 500);

INSERT INTO `opciones_almacenamiento` (`capacidad_gb`, `etiqueta`, `orden`, `activo`) VALUES
(32, '32 GB', 10, 1),
(80, '80 GB', 20, 1),
(160, '160 GB', 30, 1),
(256, '256 GB', 40, 1),
(320, '320 GB', 50, 1),
(480, '480 GB', 60, 1),
(512, '512 GB', 70, 1),
(1024, '1 TB (1024 GB)', 80, 1),
(2048, '2 TB (2048 GB)', 90, 1),
(4096, '4 TB (4096 GB)', 100, 1),
(8192, '8 TB (8192 GB)', 110, 1),
(16384, '16 TB (16384 GB)', 120, 1)
ON DUPLICATE KEY UPDATE 
  `etiqueta` = VALUES(`etiqueta`), 
  `orden` = VALUES(`orden`), 
  `activo` = VALUES(`activo`);

-- 4. Ampliación de tablas para almacenamiento avanzado y soporte de HDD/SSD (Sintaxis nativa MySQL 8.0 / MariaDB)
ALTER TABLE `notebooks` ADD COLUMN `ssd_o_superior` TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE `pcs_completas` 
  ADD COLUMN `almacenamiento_secundario_gb` INT NULL DEFAULT NULL AFTER `ssd_o_superior`,
  ADD COLUMN `ssd_secundario` TINYINT(1) NOT NULL DEFAULT 0 AFTER `almacenamiento_secundario_gb`;

-- 5. Normalización y migración de datos históricos en pcs_completas
UPDATE `pcs_completas`
SET `almacenamiento_gb` = CASE
    WHEN `almacenamiento_gb` = 1 THEN 1024
    WHEN `almacenamiento_gb` = 1000 THEN 1024
    WHEN `almacenamiento_gb` = 1204 THEN 1024
    WHEN `almacenamiento_gb` = 1023 THEN 1024
    WHEN `almacenamiento_gb` = 64 THEN 80
    WHEN `almacenamiento_gb` IN (120, 128, 150, 186) THEN 160
    WHEN `almacenamiento_gb` IN (240, 250) THEN 256
    WHEN `almacenamiento_gb` = 500 THEN 512
    ELSE `almacenamiento_gb`
END
WHERE `almacenamiento_gb` IS NOT NULL;

-- 6. Normalización en notebooks
UPDATE `notebooks`
SET `almacenamiento_gb` = CASE
    WHEN `almacenamiento_gb` = 1 THEN 1024
    WHEN `almacenamiento_gb` = 1000 THEN 1024
    WHEN `almacenamiento_gb` = 1204 THEN 1024
    WHEN `almacenamiento_gb` = 1023 THEN 1024
    WHEN `almacenamiento_gb` = 64 THEN 80
    WHEN `almacenamiento_gb` IN (120, 128, 150, 186) THEN 160
    WHEN `almacenamiento_gb` IN (240, 250) THEN 256
    WHEN `almacenamiento_gb` = 500 THEN 512
    ELSE `almacenamiento_gb`
END
WHERE `almacenamiento_gb` IS NOT NULL;
