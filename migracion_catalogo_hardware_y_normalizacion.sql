-- ==============================================================================
-- SCRIPT DE MIGRACIÓN Y NORMALIZACIÓN DE HARDWARE: MOTHERBOARDS Y PROCESADORES
-- Sistema: SITIA (Inventario de Telecomunicaciones, Insumos y Administración)
-- Base de Datos: MariaDB / MySQL
-- Fecha: 2026-09-23
-- ==============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------------------------
-- 1. CREACIÓN DE TABLAS DE CATÁLOGO (CATALOGO_MOTHERBOARDS Y CATALOGO_PROCESADORES)
-- ------------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `catalogo_motherboards` (
  `id_mother` int(11) NOT NULL AUTO_INCREMENT,
  `marca` varchar(50) NOT NULL,
  `modelo` varchar(100) NOT NULL,
  `tipo_ram` varchar(25) NOT NULL COMMENT 'DDR4, DDR3, DDR2, Combo DDR3/DDR2',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_mother`),
  UNIQUE KEY `uk_mother_marca_modelo` (`marca`, `modelo`),
  KEY `idx_mother_tipo_ram` (`tipo_ram`),
  KEY `idx_mother_marca` (`marca`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Catálogo normalizado de motherboards';

CREATE TABLE IF NOT EXISTS `catalogo_procesadores` (
  `id_procesador` int(11) NOT NULL AUTO_INCREMENT,
  `marca` varchar(50) NOT NULL,
  `modelo` varchar(100) NOT NULL,
  `tipo_ram` varchar(25) NOT NULL COMMENT 'DDR4, DDR3, DDR2, etc.',
  `tipo_equipo` enum('pc','notebook','ambos') NOT NULL DEFAULT 'pc',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_procesador`),
  UNIQUE KEY `uk_cpu_marca_modelo_tipo` (`marca`, `modelo`, `tipo_equipo`),
  KEY `idx_cpu_tipo_ram` (`tipo_ram`),
  KEY `idx_cpu_marca` (`marca`),
  KEY `idx_cpu_tipo_equipo` (`tipo_equipo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Catálogo normalizado de procesadores';

-- ------------------------------------------------------------------------------
-- 2. POBLACIÓN DEL CATÁLOGO DE MOTHERBOARDS (24 MODELOS CANÓNICOS)
-- ------------------------------------------------------------------------------

INSERT INTO `catalogo_motherboards` (`marca`, `modelo`, `tipo_ram`, `activo`) VALUES
('Asus', 'Prime B450M-A II', 'DDR4', 1),
('Asus', 'Prime A520M-A II', 'DDR4', 1),
('Asus', 'H110M-K', 'DDR4', 1),
('Asus', 'H81M-C', 'DDR3', 1),
('Asus', '2A73h', 'DDR3', 1),
('Asus', 'M4N68T-M LE V2', 'DDR3', 1),
('Asus', 'P5KPL-AM SE', 'DDR2', 1),
('ASRock', 'A320M-HDV', 'DDR4', 1),
('ASRock', 'H110M-HDV R3.0', 'DDR4', 1),
('ASRock', 'FM2A68M-DG3+', 'DDR3', 1),
('ASRock', 'FM2A88M-HD+', 'DDR3', 1),
('ASRock', 'H81M-VG4 R2.0', 'DDR3', 1),
('ASRock', 'H81M-VGH', 'DDR3', 1),
('ASRock', 'M61M-HVS', 'DDR3', 1),
('ASRock', 'N68-VS3 FX', 'DDR3', 1),
('ASRock', 'G41-VS3', 'DDR3', 1),
('ASRock', 'G41C-VS', 'Combo DDR3/DDR2', 1),
('ASRock', 'D1800B-ITX', 'DDR3L (SO-DIMM)', 1),
('ASRock', 'N68-S', 'DDR2', 1),
('ASRock', 'Wolfdale 1333-D667', 'DDR2', 1),
('Biostar', 'A520MHP', 'DDR4', 1),
('Biostar', 'A68N-2100', 'DDR3', 1),
('Biostar', 'N68S3+', 'DDR3', 1),
('Biostar', 'N68S3B', 'DDR3', 1),
('Foxconn', '2A8C', 'DDR3', 1),
('Gigabyte', 'GA-A320M-H', 'DDR4', 1),
('Gigabyte', 'A520M K V2', 'DDR4', 1),
('Gigabyte', 'GA-H110M-H', 'DDR4', 1),
('Gigabyte', 'H410M S2H V3', 'DDR4', 1),
('Gigabyte', 'H410M H V3', 'DDR4', 1),
('Gigabyte', 'GA-B85M-D3H-A', 'DDR3', 1),
('Gigabyte', 'GA-F2A68HM-H', 'DDR3', 1),
('Gigabyte', 'GA-78LMT-S2', 'DDR3', 1),
('Gigabyte', 'GA-G41MT-S2PT', 'DDR3', 1),
('Gigabyte', 'GA-G41M-ES2L', 'DDR2', 1),
('HP', 'ProDesk 600 G3 (829D)', 'DDR4', 1),
('Intel', 'DH61HO', 'DDR3', 1),
('Intel', 'D946GZIS', 'DDR2', 1),
('MSI', 'H110M PRO-VH PLUS', 'DDR4', 1),
('MSI', 'A320M-A PRO', 'DDR4', 1),
('MSI', 'A68HM-E33 V2', 'DDR3', 1)
ON DUPLICATE KEY UPDATE `tipo_ram` = VALUES(`tipo_ram`), `activo` = 1;

-- ------------------------------------------------------------------------------
-- 3. POBLACIÓN DEL CATÁLOGO DE PROCESADORES (PCS Y NOTEBOOKS)
-- ------------------------------------------------------------------------------

INSERT INTO `catalogo_procesadores` (`marca`, `modelo`, `tipo_ram`, `tipo_equipo`, `activo`) VALUES
-- Intel PCs
('Intel', 'Core i3-7100', 'DDR4 / DDR3L', 'pc', 1),
('Intel', 'Core i3-6100', 'DDR4 / DDR3L', 'pc', 1),
('Intel', 'Core i3-4160', 'DDR3 / DDR3L', 'pc', 1),
('Intel', 'Core i3-4170', 'DDR3 / DDR3L', 'pc', 1),
('Intel', 'Core i3-3220', 'DDR3', 'pc', 1),
('Intel', 'Core i3-10100', 'DDR4', 'pc', 1),
('Intel', 'Core i3-540', 'DDR3', 'pc', 1),
('Intel', 'Pentium G3250', 'DDR3 / DDR3L', 'pc', 1),
('Intel', 'Pentium G630', 'DDR3', 'pc', 1),
('Intel', 'Pentium E2160', 'DDR2', 'pc', 1),
('Intel', 'Pentium E5200', 'DDR2 / DDR3', 'pc', 1),
('Intel', 'Pentium E5400', 'DDR2 / DDR3', 'pc', 1),
('Intel', 'Pentium E5500', 'DDR2 / DDR3', 'pc', 1),
('Intel', 'Pentium 4', 'DDR / DDR2', 'pc', 1),
('Intel', 'Pentium Dual-Core', 'DDR2', 'pc', 1),
('Intel', 'Celeron E3400', 'DDR2 / DDR3', 'pc', 1),
('Intel', 'Celeron J1800', 'DDR3L', 'pc', 1),
('Intel', 'Core i3-3110M', 'DDR3 / DDR3L', 'pc', 1),

-- AMD PCs
('AMD', 'Ryzen 3 3200G', 'DDR4', 'pc', 1),
('AMD', 'Ryzen 5 3400G', 'DDR4', 'pc', 1),
('AMD', 'Ryzen 5 2400G', 'DDR4', 'pc', 1),
('AMD', 'A8-9600', 'DDR4', 'pc', 1),
('AMD', 'A10-9700', 'DDR4', 'pc', 1),
('AMD', 'A8-7650K', 'DDR3', 'pc', 1),
('AMD', 'A4-4000', 'DDR3', 'pc', 1),
('AMD', 'A4-4250', 'DDR3', 'pc', 1),
('AMD', 'Athlon II X2 250', 'DDR3 / DDR2', 'pc', 1),
('AMD', 'Athlon 64 X2 Dual-Core', 'DDR2', 'pc', 1),
('AMD', 'Sempron 140', 'DDR3 / DDR2', 'pc', 1),
('AMD', 'FX-4130', 'DDR3', 'pc', 1),
('AMD', 'E1-2100', 'DDR3L', 'pc', 1),

-- Notebooks
('Intel', 'Celeron N3450', 'DDR3L / LPDDR4', 'notebook', 1),
('Intel', 'Celeron N2840', 'DDR3L', 'notebook', 1),
('Intel', 'Core i3-6006U', 'DDR4 / DDR3L', 'notebook', 1),
('Intel', 'Core i3-7020U', 'DDR4 / DDR3L', 'notebook', 1),
('Intel', 'Core i3-7100U', 'DDR4 / DDR3L', 'notebook', 1),
('Intel', 'Core i5-3320M', 'DDR3 / DDR3L', 'notebook', 1),
('Intel', 'Core i7-4710MQ', 'DDR3L', 'notebook', 1),
('Intel', 'Core i7-2620M', 'DDR3', 'notebook', 1),
('AMD', 'Ryzen 5 7430U', 'DDR4 / LPDDR4X', 'notebook', 1)
ON DUPLICATE KEY UPDATE `tipo_ram` = VALUES(`tipo_ram`), `activo` = 1;

-- ------------------------------------------------------------------------------
-- 4. SANEAMIENTO TRANSACCIONAL DE PCS_COMPLETAS (MOTHERBOARDS)
-- ------------------------------------------------------------------------------

START TRANSACTION;

-- Asus
UPDATE `pcs_completas` SET `mother` = 'Asus Prime B450M-A II' WHERE `mother` IN ('Asus Prime B450M-A II', 'Asustek Prime B450M-A II', 'Asus Prime B340M-A');
UPDATE `pcs_completas` SET `mother` = 'Asus Prime A520M-A II' WHERE `mother` IN ('Asus Prime A520M-A II', 'Asustek A520M-A');
UPDATE `pcs_completas` SET `mother` = 'Asus H110M-K' WHERE `mother` = 'Asus H110M-K';
UPDATE `pcs_completas` SET `mother` = 'Asus H81M-C' WHERE `mother` IN ('ASUS H81M-C', 'Asustek H81M-C', 'Asust H81M-C');
UPDATE `pcs_completas` SET `mother` = 'Asus 2A73h' WHERE `mother` IN ('Asus 2473h', 'Asus 2A73H');
UPDATE `pcs_completas` SET `mother` = 'Asus M4N68T-M LE V2' WHERE `mother` = 'Asus M4N68T-M-LE-V2';
UPDATE `pcs_completas` SET `mother` = 'Asus P5KPL-AM SE' WHERE `mother` = 'Asus P5KPL-AMSE';

-- MSI
UPDATE `pcs_completas` SET `mother` = 'MSI H110M PRO-VH PLUS' WHERE `mother` IN ('MSI H110M PRO-VH PLUS', 'MSI MS-7A15', 'MSI H110M Pro VH-Plus', 'MSI H110M-PRO-VH PLUS', 'MSI H110M-PRO-VH', 'MSI-7A154');
UPDATE `pcs_completas` SET `mother` = 'MSI A320M-A PRO' WHERE `mother` = 'MSI A320M-A Pro';
UPDATE `pcs_completas` SET `mother` = 'MSI A68HM-E33 V2' WHERE `mother` = 'MSI A68HM-E33 v2';

-- Gigabyte
UPDATE `pcs_completas` SET `mother` = 'Gigabyte GA-A320M-H' WHERE `mother` IN ('Gigabyte A320M-H CF', 'Gigabyte A320M-H', 'Gigabyte A320M-H-CF', 'Gigabyte GA-A320M-H', 'Gigabyte A320H');
UPDATE `pcs_completas` SET `mother` = 'Gigabyte A520M K V2' WHERE `mother` = 'Gigabyte A520M K V2';
UPDATE `pcs_completas` SET `mother` = 'Gigabyte GA-H110M-H' WHERE `mother` = 'Gigabyte H110M-H';
UPDATE `pcs_completas` SET `mother` = 'Gigabyte H410M S2H V3' WHERE `mother` = 'Gigabyte H410M S2H V3';
UPDATE `pcs_completas` SET `mother` = 'Gigabyte H410M H V3' WHERE `mother` = 'Gigabyte H410M H V3';
UPDATE `pcs_completas` SET `mother` = 'Gigabyte GA-B85M-D3H-A' WHERE `mother` IN ('Gigabyte B85M-D3H-A', 'Gigabyte B85M-B3H-A');
UPDATE `pcs_completas` SET `mother` = 'Gigabyte GA-F2A68HM-H' WHERE `mother` = 'Gigabyte F2A68HM-H';
UPDATE `pcs_completas` SET `mother` = 'Gigabyte GA-78LMT-S2' WHERE `mother` = 'Gigabyte G-A78LMTS2';
UPDATE `pcs_completas` SET `mother` = 'Gigabyte GA-G41MT-S2PT' WHERE `mother` = 'Gigabyte G41MT-S2PT';
UPDATE `pcs_completas` SET `mother` = 'Gigabyte GA-G41M-ES2L' WHERE `mother` = 'Gigabyte GA-G41M-ES2L';

-- Biostar
UPDATE `pcs_completas` SET `mother` = 'Biostar A520MHP' WHERE `mother` = 'MB Biostar AM4 A520MHP DD';
UPDATE `pcs_completas` SET `mother` = 'Biostar A68N-2100' WHERE `mother` = 'Biostar A68N-2100';
UPDATE `pcs_completas` SET `mother` = 'Biostar N68S3+' WHERE `mother` = 'Biostar N6853+';
UPDATE `pcs_completas` SET `mother` = 'Biostar N68S3B' WHERE `mother` = 'Biostar N6853B';

-- ASRock
UPDATE `pcs_completas` SET `mother` = 'ASRock A320M-HDV' WHERE `mother` = 'Asrock A320M-HDV';
UPDATE `pcs_completas` SET `mother` = 'ASRock H110M-HDV R3.0' WHERE `mother` = 'Asrock H110M-HDV R3.0';
UPDATE `pcs_completas` SET `mother` = 'ASRock FM2A68M-DG3+' WHERE `mother` = 'AsRock FM2A68M-DG3+';
UPDATE `pcs_completas` SET `mother` = 'ASRock FM2A88M-HD+' WHERE `mother` IN ('AsRock FM2A88M-HD+', 'AsRock FM2488M-HD+');
UPDATE `pcs_completas` SET `mother` = 'ASRock H81M-VG4 R2.0' WHERE `mother` = 'Asrock H81M-VG4 R2.0';
UPDATE `pcs_completas` SET `mother` = 'ASRock H81M-VGH' WHERE `mother` = 'Asrock H81M-VGH';
UPDATE `pcs_completas` SET `mother` = 'ASRock M61M-HVS' WHERE `mother` = 'Asrock M61M-HVS';
UPDATE `pcs_completas` SET `mother` = 'ASRock N68-VS3 FX' WHERE `mother` = 'Asrock N68-VS3 FX';
UPDATE `pcs_completas` SET `mother` = 'ASRock G41-VS3' WHERE `mother` = 'Asrock G41-VS3';
UPDATE `pcs_completas` SET `mother` = 'ASRock G41C-VS' WHERE `mother` = 'Asrock G41C-VS';
UPDATE `pcs_completas` SET `mother` = 'ASRock D1800B-ITX' WHERE `mother` = 'D1800B-ITX';
UPDATE `pcs_completas` SET `mother` = 'ASRock N68-S' WHERE `mother` = 'Asrock N68-S';
UPDATE `pcs_completas` SET `mother` = 'ASRock Wolfdale 1333-D667' WHERE `mother` = 'Wolfdale 1333-D667';

-- HP, Intel, Foxconn
UPDATE `pcs_completas` SET `mother` = 'HP ProDesk 600 G3 (829D)' WHERE `mother` = 'HP 829D';
-- Corrección especial confirmada: Intel DH61MO NO EXISTE, es DH61HO:
UPDATE `pcs_completas` SET `mother` = 'Intel DH61HO' WHERE `mother` IN ('Intel DH61H0', 'Intel DH61HO', 'Intel DH61M0');
UPDATE `pcs_completas` SET `mother` = 'Intel D946GZIS' WHERE `mother` = 'Intel D946GZIS';
UPDATE `pcs_completas` SET `mother` = 'Foxconn 2A8C' WHERE `mother` = 'Foxconn 2A8C';

-- ------------------------------------------------------------------------------
-- 5. SANEAMIENTO TRANSACCIONAL DE PCS_COMPLETAS (PROCESADORES)
-- ------------------------------------------------------------------------------

-- Intel PCs
UPDATE `pcs_completas` SET `procesador` = 'Intel Core i3-7100' WHERE `procesador` IN ('Intel Core i3-7100', 'Intel I3-7100');
UPDATE `pcs_completas` SET `procesador` = 'Intel Core i3-6100' WHERE `procesador` = 'Intel Core I3-6100';
UPDATE `pcs_completas` SET `procesador` = 'Intel Core i3-4160' WHERE `procesador` = 'Intel Core I3-4160';
UPDATE `pcs_completas` SET `procesador` = 'Intel Core i3-4170' WHERE `procesador` = 'Intel Core i3-4170';
UPDATE `pcs_completas` SET `procesador` = 'Intel Core i3-3220' WHERE `procesador` = 'Intel Core I3-3220';
UPDATE `pcs_completas` SET `procesador` = 'Intel Core i3-10100' WHERE `procesador` = 'Intel Core I3-10100';
UPDATE `pcs_completas` SET `procesador` = 'Intel Core i3-540' WHERE `procesador` = 'Intel Core I3-470';
UPDATE `pcs_completas` SET `procesador` = 'Intel Pentium G3250' WHERE `procesador` = 'Intel Pentium G3250';
UPDATE `pcs_completas` SET `procesador` = 'Intel Pentium G630' WHERE `procesador` = 'Intel Pentium G630';
UPDATE `pcs_completas` SET `procesador` = 'Intel Pentium E2160' WHERE `procesador` IN ('Intel Pentium Dual-Core E2160', 'Intel Pentium E2160');
UPDATE `pcs_completas` SET `procesador` = 'Intel Pentium E5200' WHERE `procesador` = 'Intel Pentium ES200';
UPDATE `pcs_completas` SET `procesador` = 'Intel Pentium E5400' WHERE `procesador` = 'Intel Pentium E5400';
UPDATE `pcs_completas` SET `procesador` = 'Intel Pentium E5500' WHERE `procesador` IN ('Intel Pentium E5500', 'Intel Pentium E5500 Dual Core', 'Pentium Dual-Core E5500');
UPDATE `pcs_completas` SET `procesador` = 'Intel Pentium 4' WHERE `procesador` = 'Intel Pentium 4';
UPDATE `pcs_completas` SET `procesador` = 'Intel Pentium Dual-Core' WHERE `procesador` = 'Intel Pentium Dual Core';
UPDATE `pcs_completas` SET `procesador` = 'Intel Celeron E3400' WHERE `procesador` = 'Intel Celeron E3400';
UPDATE `pcs_completas` SET `procesador` = 'Intel Celeron J1800' WHERE `procesador` = 'Intel Celeron J1800';
UPDATE `pcs_completas` SET `procesador` = 'Intel Core i3-3110M' WHERE `procesador` = 'Intel Core i3-3110M';

-- AMD PCs
UPDATE `pcs_completas` SET `procesador` = 'AMD Ryzen 3 3200G' WHERE `procesador` IN ('AMD Ryzen 3 3200 G', 'AMD Ryzen 3 3200G');
UPDATE `pcs_completas` SET `procesador` = 'AMD Ryzen 5 3400G' WHERE `procesador` IN ('AMD Ryzen 5 3400 G', 'AMD Ryzen 5 3400G');
UPDATE `pcs_completas` SET `procesador` = 'AMD Ryzen 5 2400G' WHERE `procesador` = 'AMD Ryzen 5 2400 G';
UPDATE `pcs_completas` SET `procesador` = 'AMD A8-9600' WHERE `procesador` IN ('AMD A8 9600 R7', 'AMD A8-9600 Radeon R7', 'AMD A8-9600 R7');
UPDATE `pcs_completas` SET `procesador` = 'AMD A10-9700' WHERE `procesador` = 'AMD A10 9700 R7';
UPDATE `pcs_completas` SET `procesador` = 'AMD A8-7650K' WHERE `procesador` = 'AMD A8-7650 K';
UPDATE `pcs_completas` SET `procesador` = 'AMD A4-4000' WHERE `procesador` IN ('AMD A4 4000', 'AMD A4 4000 Radeon HD 7480D', 'AMD A4-4000 Radeon HD 7480D');
UPDATE `pcs_completas` SET `procesador` = 'AMD A4-4250' WHERE `procesador` = 'AMD A4 4250';
UPDATE `pcs_completas` SET `procesador` = 'AMD Athlon II X2 250' WHERE `procesador` IN ('AMD Athlon II x2 250', 'AMD Athlon x2 250', 'AMD Athon II x2 250');
UPDATE `pcs_completas` SET `procesador` = 'AMD Athlon 64 X2 Dual-Core' WHERE `procesador` = 'AMD Athlon x2 Dual Core';
UPDATE `pcs_completas` SET `procesador` = 'AMD Sempron 140' WHERE `procesador` = 'AMD Semprom 140';
UPDATE `pcs_completas` SET `procesador` = 'AMD FX-4130' WHERE `procesador` = 'AMD FX-4130';
UPDATE `pcs_completas` SET `procesador` = 'AMD E1-2100' WHERE `procesador` = 'AMD E1-2100';

-- ------------------------------------------------------------------------------
-- 6. SANEAMIENTO TRANSACCIONAL DE NOTEBOOKS (PROCESADORES)
-- ------------------------------------------------------------------------------

UPDATE `notebooks` SET `procesador` = 'Intel Celeron N3450' WHERE `procesador` IN ('Intel Celeron N3450', 'Intel Celeron CPU N3450', 'Celeron N3450');
UPDATE `notebooks` SET `procesador` = 'Intel Celeron N2840' WHERE `procesador` = 'Intel Celeron N2840';
UPDATE `notebooks` SET `procesador` = 'Intel Core i3-6006U' WHERE `procesador` IN ('Intel Core I3-6006U', 'Intel I3-6006U');
UPDATE `notebooks` SET `procesador` = 'Intel Core i3-7020U' WHERE `procesador` = 'Core I3-7020U';
UPDATE `notebooks` SET `procesador` = 'Intel Core i3-7100U' WHERE `procesador` = 'Intel I3-7100U';
UPDATE `notebooks` SET `procesador` = 'Intel Core i5-3320M' WHERE `procesador` IN ('Intel Core I5-3320', 'Intel Core I5-3320M');
UPDATE `notebooks` SET `procesador` = 'Intel Core i7-4710MQ' WHERE `procesador` = 'Intel Core I7-4710';
UPDATE `notebooks` SET `procesador` = 'Intel Core i7-2620M' WHERE `procesador` = 'Intel Core I7-2620M';
UPDATE `notebooks` SET `procesador` = 'AMD Ryzen 5 7430U' WHERE `procesador` IN ('AMD Ryzen 5 7430U', 'Ryzen 5 7430U');

COMMIT;

SET FOREIGN_KEY_CHECKS = 1;

-- ==============================================================================
-- FIN DEL SCRIPT DE MIGRACIÓN Y NORMALIZACIÓN
-- ==============================================================================
