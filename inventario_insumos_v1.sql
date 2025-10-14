-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost
-- Tiempo de generación: 14-10-2025 a las 17:02:08
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `inventario_insumos_v1`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `areas`
--

CREATE TABLE `areas` (
  `id_area` int(11) NOT NULL,
  `nombre_area` varchar(100) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `areas`
--

INSERT INTO `areas` (`id_area`, `nombre_area`, `descripcion`) VALUES
(1, 'Informatica', 'Área de informática y sistemas'),
(2, 'Administración', 'Área administrativa'),
(3, 'Delegado/a', 'Área de Delegados/as'),
(4, 'Sueldos', 'Área de contabilidad'),
(5, 'Preventivos', 'Área de Preventivos'),
(6, 'Mesa de Entrada', 'Área de Recepción'),
(7, 'Fortalecimiento Familiar', 'Área de Fortalecimiento Familiar'),
(8, 'Admisión', 'Área de Admisión'),
(9, 'Penal Juvenil', 'Área de Penal Juvenil '),
(10, 'Legales', 'Área de Legales'),
(11, 'Otros', 'Otras áreas ');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `escaneres`
--

CREATE TABLE `escaneres` (
  `id_escaner` int(11) NOT NULL,
  `id_insumo` int(11) NOT NULL,
  `marca` varchar(100) NOT NULL,
  `modelo` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `escaneres`
--

INSERT INTO `escaneres` (`id_escaner`, `id_insumo`, `marca`, `modelo`) VALUES
(8, 9, 'HP', '402dn'),
(9, 7, 'Epsonnn', 'V3923'),
(10, 11, 'Epson', 'V39'),
(11, 23, 'Ficha', 'a la entrada');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `impresoras`
--

CREATE TABLE `impresoras` (
  `id_impresora` int(11) NOT NULL,
  `id_insumo` int(11) NOT NULL,
  `marca` varchar(100) NOT NULL,
  `modelo` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `impresoras`
--

INSERT INTO `impresoras` (`id_impresora`, `id_insumo`, `marca`, `modelo`) VALUES
(2, 5, 'HP', '400 dn'),
(3, 12, 'HP', '420dbn');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `insumos`
--

CREATE TABLE `insumos` (
  `id_insumo` int(11) NOT NULL,
  `nombre_insumo` varchar(100) NOT NULL,
  `tipo_insumo` enum('Varios','PC Completa','Notebook','Impresora','Monitor','Escaner') NOT NULL,
  `subcategoria_varios` enum('Hardware','Periféricos','Red') DEFAULT NULL,
  `descripcion_general` varchar(255) DEFAULT NULL,
  `numero_serie` varchar(50) DEFAULT NULL,
  `id_fisico` varchar(50) DEFAULT NULL,
  `id_patrimonio` varchar(50) DEFAULT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  `fecha_adquisicion` date DEFAULT NULL,
  `estado` enum('Disponible','Asignado','De Baja') NOT NULL DEFAULT 'Disponible',
  `id_punto_stock_actual` int(11) DEFAULT NULL,
  `id_sede_actual` int(11) DEFAULT NULL,
  `id_area_asignacion_actual` int(11) DEFAULT NULL,
  `id_patrimonio_idx` varchar(50) GENERATED ALWAYS AS (case when `tipo_insumo` <> 'Varios' then `id_patrimonio` else NULL end) VIRTUAL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `insumos`
--

INSERT INTO `insumos` (`id_insumo`, `nombre_insumo`, `tipo_insumo`, `subcategoria_varios`, `descripcion_general`, `numero_serie`, `id_fisico`, `id_patrimonio`, `cantidad`, `fecha_adquisicion`, `estado`, `id_punto_stock_actual`, `id_sede_actual`, `id_area_asignacion_actual`) VALUES
(1, 'Mouse Genius', 'Varios', 'Periféricos', NULL, NULL, NULL, NULL, 1, '2025-08-18', 'Disponible', 2, 45, 3),
(3, 'PC Oficina Coradir', 'PC Completa', NULL, NULL, '456123789', 'D154', 'sd122', 1, '2025-08-18', 'Asignado', NULL, 1, 1),
(5, 'Impresora HP Recuperada', 'Impresora', NULL, NULL, '745312689', 'J456', NULL, 1, '2025-08-18', 'Asignado', NULL, 43, 1),
(6, 'Monitor recuperado en comision', 'Monitor', NULL, NULL, '32', '84621359', '646546gg', 1, '2025-08-18', 'Asignado', NULL, 27, 3),
(7, 'Escarner Nuevo', 'Escaner', NULL, NULL, '108923', 'E45333', 'asfd123', 1, '2025-08-18', 'De Baja', 2, NULL, NULL),
(8, 'Teclado GT2', 'Varios', 'Periféricos', NULL, NULL, NULL, NULL, 5, '2025-08-19', 'Disponible', 2, NULL, NULL),
(9, 'Escaner', 'Escaner', NULL, NULL, '25556584', 'a-2444585', '2qwe5', 1, '2025-09-12', 'De Baja', NULL, NULL, NULL),
(10, 'Notebook 3ra licitacion', 'Notebook', NULL, NULL, '415787663', 'D252', NULL, 1, '2025-09-15', 'Asignado', NULL, 50, 7),
(11, 'Escaner Nuevo', 'Escaner', NULL, NULL, '6547687', 'D78', 'D789', 1, '2025-09-16', 'Asignado', NULL, 47, 6),
(12, 'Impresora nueva hp', 'Impresora', NULL, NULL, '12313', 'D435', 'Af123', 1, '2025-09-16', 'Asignado', NULL, 51, 2),
(13, 'Cable HDMI', 'Varios', 'Periféricos', 'Demo seed', NULL, NULL, NULL, 4, '2025-09-16', 'Disponible', 2, 38, 8),
(14, 'Patch cord CAT6', 'Varios', 'Red', 'Demo seed', NULL, NULL, NULL, 3, '2025-09-16', 'Disponible', 2, 38, 8),
(15, 'Mouse óptico', 'Varios', 'Periféricos', 'Demo seed', NULL, NULL, NULL, 3, '2025-09-16', 'Disponible', 2, NULL, NULL),
(16, 'PC Escritorio Oficina', 'PC Completa', NULL, NULL, 'PC COMPLETA-SN0004', 'PC COMPLETA-ID0004', 'PAT00004', 1, '2025-09-16', 'De Baja', 2, NULL, NULL),
(17, 'Notebook 14\"', 'Notebook', NULL, NULL, 'NOTEBOOK-SN0005', 'NOTEBOOK-ID0005', 'PAT00005', 1, '2025-09-16', 'De Baja', NULL, NULL, NULL),
(18, 'Impresora Láser', 'Impresora', NULL, NULL, 'IMPRESORA-SN0006', 'IMPRESORA-ID0006', 'PAT00006', 1, '2025-09-16', 'Asignado', NULL, 43, 8),
(19, 'Monitor 24\"', 'Monitor', NULL, NULL, 'MONITOR-SN0007', 'MONITOR-ID0007', 'PAT00007', 1, '2025-09-16', 'De Baja', 2, NULL, NULL),
(20, 'Escáner Documental', 'Escaner', NULL, NULL, 'ESCANER-SN0008', 'ESCANER-ID0008', 'PAT00008', 1, '2025-09-16', 'Asignado', NULL, 43, 8),
(21, 'Teclado USB', 'Varios', 'Periféricos', 'Demo seed', NULL, NULL, NULL, 8, '2025-09-16', 'Disponible', 2, NULL, NULL),
(22, 'Regleta 6 tomas', 'Varios', 'Hardware', 'Demo seed', NULL, NULL, NULL, 8, '2025-09-16', 'Disponible', 2, 38, 8),
(23, 'Fichero', 'Escaner', NULL, NULL, '65468614', 'D466', 'D466', 1, '2025-10-14', 'Asignado', NULL, 43, 11);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `insumos_bajas`
--

CREATE TABLE `insumos_bajas` (
  `id_baja` int(11) NOT NULL,
  `id_insumo` int(11) NOT NULL,
  `fecha_baja` datetime NOT NULL DEFAULT current_timestamp(),
  `observacion` varchar(255) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `insumos_bajas`
--

INSERT INTO `insumos_bajas` (`id_baja`, `id_insumo`, `fecha_baja`, `observacion`, `cantidad`) VALUES
(1, 9, '2025-09-12 13:39:54', 'Se lo robaron', 1),
(2, 9, '2025-09-12 13:40:08', 'robado', 1),
(3, 7, '2025-09-12 13:44:03', 'Se rompio', 1),
(4, 16, '2025-09-30 08:41:14', 'Se inundo por la lluvia y se quemo.', 1),
(5, 1, '2025-10-01 09:42:35', 'Error de carga.', 3),
(6, 1, '2025-10-01 09:42:42', 'Error de carga.', 3),
(7, 1, '2025-10-01 09:45:07', 'Mal cargado', 3),
(8, 13, '2025-10-01 09:54:28', 'Rotos', 3),
(9, 1, '2025-10-01 10:11:06', 'Rotos por humedad', 2),
(10, 17, '2025-10-01 10:23:32', 'Se la robaron', 1),
(11, 19, '2025-10-03 09:04:51', 'Se quemo en una subida de tension, no tenia estabilizador.', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `localidades`
--

CREATE TABLE `localidades` (
  `id_localidad` int(11) NOT NULL,
  `id_zona` int(11) NOT NULL,
  `nombre_localidad` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `localidades`
--

INSERT INTO `localidades` (`id_localidad`, `id_zona`, `nombre_localidad`) VALUES
(1, 1, 'Viedma'),
(2, 1, 'General Conesa'),
(3, 3, 'San Antonio Oeste'),
(4, 3, 'Sierra Grande'),
(5, 3, 'Valcheta'),
(6, 5, 'Choele Choel'),
(7, 5, 'Lamarque'),
(8, 5, 'Luis Beltran'),
(9, 5, 'Darwin'),
(10, 5, 'Belisle'),
(11, 5, 'Chimpay'),
(12, 5, 'Rio Colorado'),
(13, 2, 'Villa Regina'),
(14, 2, 'Chichinales'),
(15, 2, 'Ing. Huergo'),
(16, 4, 'General Roca'),
(17, 4, 'Allen'),
(18, 6, 'Cipolletti'),
(19, 6, 'Fernandez Oro'),
(20, 7, 'Cinco Saltos'),
(21, 8, 'Catriel'),
(22, 9, 'Ramos Mexia'),
(23, 9, 'Sierra Colorada'),
(24, 9, 'Los Menucos'),
(25, 9, 'Maquinchao'),
(26, 9, 'Ing. Jacobacci'),
(27, 10, 'Bariloche'),
(28, 11, 'El Bolson');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `monitores`
--

CREATE TABLE `monitores` (
  `id_monitor` int(11) NOT NULL,
  `id_insumo` int(11) NOT NULL,
  `marca` varchar(100) NOT NULL,
  `modelo` varchar(100) NOT NULL,
  `pulgadas` decimal(4,1) DEFAULT NULL,
  `conexion` enum('VGA','HDMI') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `monitores`
--

INSERT INTO `monitores` (`id_monitor`, `id_insumo`, `marca`, `modelo`, `pulgadas`, `conexion`) VALUES
(5, 6, 'LG', 'Jk152', 19.0, 'VGA');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notebooks`
--

CREATE TABLE `notebooks` (
  `id_notebook` int(11) NOT NULL,
  `id_insumo` int(11) NOT NULL,
  `marca` varchar(100) NOT NULL,
  `modelo` varchar(100) NOT NULL,
  `procesador` varchar(100) DEFAULT NULL,
  `ram_gb` int(11) DEFAULT NULL,
  `almacenamiento_gb` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `notebooks`
--

INSERT INTO `notebooks` (`id_notebook`, `id_insumo`, `marca`, `modelo`, `procesador`, `ram_gb`, `almacenamiento_gb`) VALUES
(2, 10, 'Silverstone', 'Rt 500', 'I3-5320', 8, 500);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pcs_completas`
--

CREATE TABLE `pcs_completas` (
  `id_pc_completa` int(11) NOT NULL,
  `id_insumo` int(11) NOT NULL,
  `procesador` varchar(100) DEFAULT NULL,
  `ram_gb` int(11) DEFAULT NULL,
  `almacenamiento_gb` int(11) DEFAULT NULL,
  `mother` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pcs_completas`
--

INSERT INTO `pcs_completas` (`id_pc_completa`, `id_insumo`, `procesador`, `ram_gb`, `almacenamiento_gb`, `mother`) VALUES
(8, 3, 'R3 3200 G', 8, 1000, 'ASUS H110M VK-PLUS');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `puntos_stock`
--

CREATE TABLE `puntos_stock` (
  `id_punto_stock` int(11) NOT NULL,
  `nombre_punto` enum('Oficina','Depósito') NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `puntos_stock`
--

INSERT INTO `puntos_stock` (`id_punto_stock`, `nombre_punto`, `descripcion`) VALUES
(1, 'Oficina', 'Punto de stock en oficina'),
(2, 'Depósito', 'Punto de stock en depósito');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `remitos`
--

CREATE TABLE `remitos` (
  `id_remito` int(11) NOT NULL,
  `numero_remito` varchar(50) NOT NULL,
  `id_sede` int(11) NOT NULL,
  `id_area` int(11) NOT NULL,
  `nombre_persona_asignada` varchar(100) NOT NULL,
  `apellido_persona_asignada` varchar(100) NOT NULL,
  `fecha_asignacion` date NOT NULL,
  `estado` enum('Activa','Devuelta') NOT NULL DEFAULT 'Activa',
  `fecha_devolucion` date DEFAULT NULL,
  `observaciones` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `remitos`
--

INSERT INTO `remitos` (`id_remito`, `numero_remito`, `id_sede`, `id_area`, `nombre_persona_asignada`, `apellido_persona_asignada`, `fecha_asignacion`, `estado`, `fecha_devolucion`, `observaciones`) VALUES
(1, 'REMITO_2025_0001', 47, 4, 'Juancito', 'Ramirez', '2025-08-19', 'Devuelta', '2025-09-12', NULL),
(3, 'REMITO_2025_0003', 51, 8, 'jose', 'ruiz', '2025-08-19', 'Devuelta', '2025-09-12', NULL),
(4, '0001_2025', 30, 5, 'Pepe', 'Sanchez', '2025-09-10', 'Devuelta', '2025-09-12', NULL),
(5, '0002_2025', 33, 2, 'Carina', 'Pastoriza', '2025-09-12', 'Activa', NULL, 'Otra vez pidiendo el mismo insumo'),
(6, '0003_2025', 50, 8, 'Ruben', 'Perez', '2025-09-12', 'Devuelta', '2025-09-12', NULL),
(7, '0004_2025', 43, 1, 'Robeto', 'Juanes', '2025-09-15', 'Activa', NULL, NULL),
(8, '0005_2025', 27, 3, 'Diego', 'Garcia', '2025-09-15', 'Activa', NULL, NULL),
(9, '0006_2025', 1, 1, 'Diego', 'Garcia|', '2025-09-15', 'Activa', NULL, NULL),
(10, '0007_2025', 50, 3, 'qwe', 'qwe', '2025-09-16', 'Devuelta', '2025-09-17', NULL),
(11, '0008_2025', 50, 7, 'skldfj', 'sñlkf', '2025-09-16', 'Activa', NULL, NULL),
(12, '0009_2025', 50, 5, 'Laura', 'Rivera', '2025-09-16', 'Activa', NULL, NULL),
(13, '0010_2025', 1, 1, 'Cintia', 'Cuassolo', '2025-09-16', 'Devuelta', '2025-09-30', NULL),
(14, '0011_2025', 30, 2, 'Pepe', 'Gomez', '2025-09-16', 'Activa', NULL, NULL),
(15, '0012_2025', 44, 6, 'Romina', 'Gomez', '2025-09-30', 'Devuelta', '2025-10-01', NULL),
(16, '0013_2025', 51, 6, 'Genaro', 'Ramirez', '2025-09-30', 'Activa', NULL, NULL),
(17, '0000_2025', 43, 8, ' Pepito', 'Prueba NRO RMT', '2025-09-30', 'Activa', NULL, NULL),
(27, '0014_2025', 27, 6, 'Marcos', 'Test NRO', '2025-09-30', 'Devuelta', '2025-10-01', NULL),
(28, '0015_2025', 51, 5, 'Leonardo', 'Test NRO', '2025-09-30', 'Activa', NULL, NULL),
(29, '0016_2025', 47, 6, 'Heber', 'Gonzalez', '2025-09-30', 'Activa', NULL, 'Lo retira en nombre de la Delegada.'),
(30, '0017_2025', 51, 2, 'Vanesa', 'Gimenez', '2025-09-30', 'Activa', NULL, NULL),
(31, '0018_2025', 29, 9, 'Fiorella', 'Maglione', '2025-09-30', 'Activa', NULL, NULL),
(32, '0019_2025', 53, 6, 'Horacio', 'Guarani', '2025-10-01', 'Devuelta', '2025-10-01', NULL),
(33, '0020_2025', 38, 3, 'Gerardo', 'Sofovich', '2025-10-03', 'Activa', NULL, NULL),
(34, '0021_2025', 42, 1, 'Carlos', 'Santana', '2025-10-03', 'Activa', NULL, NULL),
(35, '0022_2025', 45, 3, 'Adriana', 'Gomez', '2025-10-03', 'Activa', NULL, NULL),
(36, '0023_2025', 38, 8, 'Silvio', 'Rodriguez', '2025-10-03', 'Activa', NULL, NULL),
(37, '0024_2025', 38, 8, 'Jorge', 'Cuarto', '2025-10-13', 'Activa', NULL, NULL),
(38, '0025_2025', 43, 11, 'Daniel', 'Rosas', '2025-10-14', 'Activa', NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `remitos_detalle`
--

CREATE TABLE `remitos_detalle` (
  `id_detalle` int(11) NOT NULL,
  `id_remito` int(11) NOT NULL,
  `id_insumo` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  `cantidad_devuelta` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `remitos_detalle`
--

INSERT INTO `remitos_detalle` (`id_detalle`, `id_remito`, `id_insumo`, `cantidad`, `cantidad_devuelta`) VALUES
(1, 1, 6, 1, 1),
(2, 1, 1, 1, 1),
(3, 1, 8, 1, 1),
(4, 1, 3, 1, 1),
(7, 3, 7, 1, 1),
(8, 4, 5, 1, 1),
(9, 4, 1, 1, 1),
(10, 4, 8, 1, 1),
(11, 5, 1, 1, 0),
(12, 5, 8, 2, 0),
(13, 6, 9, 1, 1),
(14, 6, 5, 1, 1),
(15, 6, 1, 1, 1),
(16, 6, 8, 1, 1),
(17, 7, 5, 1, 0),
(18, 7, 1, 1, 1),
(19, 7, 8, 1, 1),
(20, 8, 6, 1, 0),
(21, 8, 1, 1, 0),
(22, 8, 8, 1, 0),
(23, 9, 3, 1, 0),
(24, 10, 1, 1, 1),
(25, 11, 10, 1, 0),
(26, 12, 1, 1, 0),
(27, 12, 8, 1, 0),
(28, 13, 11, 1, 1),
(29, 14, 13, 1, 0),
(30, 14, 15, 1, 0),
(31, 14, 8, 1, 0),
(32, 15, 13, 3, 3),
(33, 15, 1, 1, 1),
(34, 15, 15, 1, 1),
(35, 15, 17, 1, 1),
(36, 16, 15, 1, 0),
(37, 16, 22, 1, 0),
(38, 17, 20, 1, 0),
(39, 17, 18, 1, 0),
(40, 17, 22, 1, 0),
(41, 27, 13, 1, 1),
(42, 27, 15, 1, 1),
(43, 28, 14, 1, 0),
(44, 28, 22, 1, 0),
(45, 28, 8, 1, 0),
(46, 29, 11, 1, 0),
(47, 29, 15, 1, 0),
(48, 29, 14, 2, 0),
(49, 30, 12, 1, 0),
(50, 30, 8, 2, 0),
(51, 31, 8, 2, 0),
(52, 31, 13, 2, 0),
(53, 32, 1, 1, 1),
(54, 32, 8, 1, 1),
(55, 33, 14, 4, 0),
(56, 34, 22, 5, 0),
(57, 35, 13, 1, 0),
(58, 35, 1, 1, 0),
(59, 36, 22, 1, 0),
(60, 36, 14, 1, 0),
(61, 37, 22, 1, 0),
(62, 37, 13, 1, 0),
(63, 38, 23, 1, 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `remito_secuencia`
--

CREATE TABLE `remito_secuencia` (
  `anio` int(11) NOT NULL,
  `ultimo` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `remito_secuencia`
--

INSERT INTO `remito_secuencia` (`anio`, `ultimo`) VALUES
(2025, 25);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sedes`
--

CREATE TABLE `sedes` (
  `id_sede` int(11) NOT NULL,
  `id_localidad` int(11) NOT NULL,
  `nombre_sede` varchar(100) NOT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `observaciones` varchar(255) DEFAULT NULL,
  `delegado_nombre` varchar(100) DEFAULT NULL,
  `delegado_apellido` varchar(100) DEFAULT NULL,
  `delegado_telefono` varchar(50) DEFAULT NULL,
  `responsable_nombre` varchar(100) DEFAULT NULL,
  `responsable_apellido` varchar(100) DEFAULT NULL,
  `responsable_telefono` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `sedes`
--

INSERT INTO `sedes` (`id_sede`, `id_localidad`, `nombre_sede`, `direccion`, `observaciones`, `delegado_nombre`, `delegado_apellido`, `delegado_telefono`, `responsable_nombre`, `responsable_apellido`, `responsable_telefono`) VALUES
(1, 1, 'Central', 'Belgrano Y Pueyrredon', 'Testeador', NULL, NULL, NULL, NULL, NULL, NULL),
(2, 1, 'Viedma valle inferior', 'Mexico y Caseros', 'Test obs', 'Hernan', 'Araya', '2920609080', 'Luis', 'Dalfonso', '2920598410'),
(3, 1, 'Caina Varones', 'Ex Ruta Nº3 Parcela A68, km 1800', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(4, 1, 'Caina Mujeres', 'Tierra del Fuego Nº336', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(5, 1, 'Casa Abrigo Niños', 'J. M. Guido Nº122', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(6, 1, 'Globito Azul', 'Las Azucenas Nº595', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(7, 1, 'Ecos Galpon Amarillo', 'O`Higgins N.º 406', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(8, 1, 'Ecos Lavalle', 'Calle 18 y 13', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(9, 1, 'Ecos Casita del Nehuen', 'Bº Guido Esc. 35 Planta Baja Dpto D, Calle Harosteguy ', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(10, 1, 'Ecos Ceferino', 'Mexico N°585', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(11, 1, 'La Viruta', 'Ex RN Nº19 – Calle:Susana Rinaldi y Enrique Cadicamo Bº Patagonia  ', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(12, 1, 'Sonoridad Andina', 'Bº P. Independencia calle Las Amapolas Nº05', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(13, 1, 'Hueche El Condor ', 'Club de Los Amigos calle 13 y 67', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(14, 2, 'Sede', 'Belgrano Nº265', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(15, 2, 'Hueche', 'Julio A Roca N.º entre Chañares y Tamariscos Bº La Rivera\r\nEspacio que les brinda el Municipio', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(16, 3, 'Sede', 'Av Belgrano N.º 1625 ', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(17, 3, 'Ecos', 'Av Belgrano N.º 1625 ', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(18, 4, 'Sede', 'Comparten of. con el municipio\r\n', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(19, 4, 'Casa Abrigo Niños', 'Bº Villa Hiparsa Modulo 1 Planta Baja ', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(20, 5, 'Sede', 'Alem Nº830', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(21, 6, 'Sede', 'Uruaguay N.º234', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(22, 6, 'OF. FORTALECIMIENTO FAMILIAR', 'Falta direccion', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(23, 7, 'Sede', 'Arrieta Nº309', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(24, 7, 'Ecos', 'Guemes y Juan Jose Paso BºIndustrial', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(25, 8, 'Sede', 'San Martin s/n', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(26, 9, 'Sede', 'Av.Roca y Sarasola', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(27, 10, 'Sede', '12 de Octubre y Entre Rios ', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(28, 11, 'Sede', 'Sarmiento Nº248', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(29, 12, 'Sede', 'Alem Nº 890 se traslada a\r\nJuan B Justo 510', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(30, 12, 'Hueche', 'Ramon Tuero Nº870', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(31, 13, 'Sede', 'Guemes Nº284', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(32, 14, 'Sede ', 'Malvinas Nº 292', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(33, 15, 'Ecos', 'Adolfo Saiz Nº341 Bº San Martin ', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(34, 16, 'Sede', 'Rodhe 170', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(35, 16, 'ECOS Of. de Fortalecimiento Familiar ', 'Rodhe 350', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(36, 16, 'Caina Varones', 'Ushuaia Nº2384', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(37, 16, 'Caina Mujeres', 'Bariloche Nº2296', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(38, 17, 'Sede', 'Av. Peron ', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(39, 18, 'Sede y Ecos', '09 de Julio Nº 59', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(40, 19, 'Sede', 'Gral Roca Nº 1472', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(41, 19, 'CAD Rayito de oro', 'Chile y Cerros Colorados', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(42, 20, 'Sede', 'España y Rivadavia Nº 256', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(43, 21, 'Sede', 'Santa Rosa Nº 70\r\nMunicipio', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(44, 22, 'Sede', 'Av. San Martin y 12 de Octubre', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(45, 23, 'Sede', 'Av. 25 de Mayo Nº576', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(46, 24, 'Sede', 'Jicha Nº35', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(47, 25, 'Sede', 'San Jose S/N', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(48, 26, 'Sede', '09 de Julio Nº 748', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(49, 27, 'SEDE ECOS HUECHE – EQUIPOS \r\nTERRITORIALES Nº \"7,8,9\"', 'Perito Moreno Nº 1435', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(50, 27, 'Casa Abrigo Niños', 'Perito Moreno Nº 1435', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(51, 27, 'CAINA ADOLESCENTES VARONES \r\nCAINA ADOLESCENTES MUJERES', 'Albarracin Nº568', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(52, 28, 'Ecos Hueche', 'Rivadavia Nº 2200', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(53, 28, 'Casa Abrigo Niños', 'Bº Irigoyen Padre Guillermo Nº 332', NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sedes_internet`
--

CREATE TABLE `sedes_internet` (
  `id_internet` int(11) NOT NULL,
  `id_sede` int(11) NOT NULL,
  `proveedor` varchar(100) NOT NULL,
  `tipo_conexion` enum('ADSL','Fibra óptica','4G','5G','Satelital','Radioenlace') NOT NULL,
  `velocidad_bajada_mbps` int(11) DEFAULT NULL,
  `velocidad_subida_mbps` int(11) DEFAULT NULL,
  `simetrico` tinyint(1) NOT NULL DEFAULT 0,
  `tiene_wifi` tinyint(1) NOT NULL DEFAULT 0,
  `estado_servicio` enum('Activo','Pendiente','De Baja') NOT NULL DEFAULT 'Activo',
  `observaciones` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `sedes_internet`
--

INSERT INTO `sedes_internet` (`id_internet`, `id_sede`, `proveedor`, `tipo_conexion`, `velocidad_bajada_mbps`, `velocidad_subida_mbps`, `simetrico`, `tiene_wifi`, `estado_servicio`, `observaciones`) VALUES
(2, 2, 'Altec', 'Fibra óptica', 50, 50, 1, 0, 'Activo', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sedes_planos`
--

CREATE TABLE `sedes_planos` (
  `id_plano` int(11) NOT NULL,
  `id_sede` int(11) NOT NULL,
  `tipo_plano` enum('Base','Red','Vigilancia') NOT NULL,
  `archivo` varchar(255) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `fecha_subida` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `sedes_planos`
--

INSERT INTO `sedes_planos` (`id_plano`, `id_sede`, `tipo_plano`, `archivo`, `descripcion`, `fecha_subida`) VALUES
(2, 2, 'Red', 'public/uploads/planos/plano_2_Red_1758112560.pdf', 'Relevamiento 11/09/2025', '2025-09-17 09:36:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sedes_red_dispositivos`
--

CREATE TABLE `sedes_red_dispositivos` (
  `id_dispositivo` int(11) NOT NULL,
  `id_sede` int(11) NOT NULL,
  `tipo_dispositivo` enum('Switch','Router','UPS','AP','Firewall') NOT NULL,
  `marca` varchar(100) DEFAULT NULL,
  `modelo` varchar(100) DEFAULT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  `ubicacion` varchar(100) DEFAULT NULL,
  `estado` enum('Activo','De Baja') NOT NULL DEFAULT 'Activo',
  `observaciones` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `sedes_red_dispositivos`
--

INSERT INTO `sedes_red_dispositivos` (`id_dispositivo`, `id_sede`, `tipo_dispositivo`, `marca`, `modelo`, `cantidad`, `ubicacion`, `estado`, `observaciones`) VALUES
(1, 2, 'Switch', 'Cisco', 'wb40', 3, '', 'Activo', NULL),
(2, 2, 'Router', 'TP-Link', '3cv', 1, '', 'Activo', NULL),
(3, 49, 'Switch', 'hp aruba', '', 3, 'oficina 5, 6, 9', 'Activo', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sedes_telefonia_lineas`
--

CREATE TABLE `sedes_telefonia_lineas` (
  `id_linea` int(11) NOT NULL,
  `id_sede` int(11) NOT NULL,
  `tipo_linea` enum('Fija','Móvil') NOT NULL,
  `operador` varchar(100) DEFAULT NULL,
  `numero` varchar(30) DEFAULT NULL,
  `dispositivo_modelo` varchar(100) DEFAULT NULL,
  `interno_ext` varchar(20) DEFAULT NULL,
  `estado` enum('Activa','Pendiente','De Baja') NOT NULL DEFAULT 'Activa',
  `observaciones` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `sedes_telefonia_lineas`
--

INSERT INTO `sedes_telefonia_lineas` (`id_linea`, `id_sede`, `tipo_linea`, `operador`, `numero`, `dispositivo_modelo`, `interno_ext`, `estado`, `observaciones`) VALUES
(1, 2, 'Fija', 'Movistar', '2920425211', '', '', 'Activa', NULL),
(2, 1, 'Fija', 'Movistar', '2920558963', '', '17', 'Pendiente', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sedes_vigilancia`
--

CREATE TABLE `sedes_vigilancia` (
  `id_vigilancia` int(11) NOT NULL,
  `id_sede` int(11) NOT NULL,
  `proveedor` varchar(100) NOT NULL,
  `estado_servicio` enum('Activo','Pendiente','De Baja') NOT NULL DEFAULT 'Activo',
  `observaciones` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `sedes_vigilancia`
--

INSERT INTO `sedes_vigilancia` (`id_vigilancia`, `id_sede`, `proveedor`, `estado_servicio`, `observaciones`) VALUES
(2, 2, 'Compuser', 'Activo', 'No da soporte'),
(3, 34, 'Pirulo gomez', 'Activo', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sedes_vigilancia_dispositivos`
--

CREATE TABLE `sedes_vigilancia_dispositivos` (
  `id_vigilancia_dispositivo` int(11) NOT NULL,
  `id_vigilancia` int(11) NOT NULL,
  `tipo_dispositivo` enum('DVR','NVR','Cámara','Sensor','Monitor') NOT NULL,
  `marca` varchar(100) DEFAULT NULL,
  `modelo` varchar(100) DEFAULT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  `ubicacion` varchar(100) DEFAULT NULL,
  `estado` enum('Activo','De Baja') NOT NULL DEFAULT 'Activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `sedes_vigilancia_dispositivos`
--

INSERT INTO `sedes_vigilancia_dispositivos` (`id_vigilancia_dispositivo`, `id_vigilancia`, `tipo_dispositivo`, `marca`, `modelo`, `cantidad`, `ubicacion`, `estado`) VALUES
(1, 2, 'DVR', 'Cisco', 'txt', 1, 'Oficina deposito', 'Activo'),
(2, 2, 'Cámara', 'Cisco', 'xls', 3, '', 'Activo'),
(3, 3, 'Cámara', 'Nisuta', 'js', 7, '', 'Activo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sede_areas`
--

CREATE TABLE `sede_areas` (
  `id_sede_area` int(11) NOT NULL,
  `id_sede` int(11) NOT NULL,
  `id_area` int(11) NOT NULL,
  `activa` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_insumos_completos`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_insumos_completos` (
`id_insumo` int(11)
,`nombre_insumo` varchar(100)
,`tipo_insumo` enum('Varios','PC Completa','Notebook','Impresora','Monitor','Escaner')
,`subcategoria_varios` enum('Hardware','Periféricos','Red')
,`numero_serie` varchar(50)
,`id_fisico` varchar(50)
,`cantidad` int(11)
,`fecha_adquisicion` date
,`estado` enum('Disponible','Asignado','De Baja')
,`punto_stock` enum('Oficina','Depósito')
,`area_actual` varchar(100)
,`sede_actual` varchar(100)
,`nombre_localidad` varchar(100)
,`nombre_zona` varchar(100)
);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `zonas`
--

CREATE TABLE `zonas` (
  `id_zona` int(11) NOT NULL,
  `nombre_zona` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `zonas`
--

INSERT INTO `zonas` (`id_zona`, `nombre_zona`) VALUES
(4, 'Alto Valle Centro'),
(2, 'Alto Valle Este'),
(6, 'Alto Valle Oeste I'),
(7, 'Alto Valle Oeste II'),
(8, 'Alto Valle Oeste III'),
(10, 'Andina'),
(3, 'Atlantica'),
(11, 'El Bolson'),
(9, 'Linea Sur'),
(1, 'Valle Inferior'),
(5, 'Valle Medio');

-- --------------------------------------------------------

--
-- Estructura para la vista `v_insumos_completos`
--
DROP TABLE IF EXISTS `v_insumos_completos`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_insumos_completos`  AS SELECT `i`.`id_insumo` AS `id_insumo`, `i`.`nombre_insumo` AS `nombre_insumo`, `i`.`tipo_insumo` AS `tipo_insumo`, `i`.`subcategoria_varios` AS `subcategoria_varios`, `i`.`numero_serie` AS `numero_serie`, `i`.`id_fisico` AS `id_fisico`, `i`.`cantidad` AS `cantidad`, `i`.`fecha_adquisicion` AS `fecha_adquisicion`, `i`.`estado` AS `estado`, `ps`.`nombre_punto` AS `punto_stock`, `ar`.`nombre_area` AS `area_actual`, `s`.`nombre_sede` AS `sede_actual`, `l`.`nombre_localidad` AS `nombre_localidad`, `z`.`nombre_zona` AS `nombre_zona` FROM (((((`insumos` `i` left join `puntos_stock` `ps` on(`i`.`id_punto_stock_actual` = `ps`.`id_punto_stock`)) left join `areas` `ar` on(`i`.`id_area_asignacion_actual` = `ar`.`id_area`)) left join `sedes` `s` on(`i`.`id_sede_actual` = `s`.`id_sede`)) left join `localidades` `l` on(`s`.`id_localidad` = `l`.`id_localidad`)) left join `zonas` `z` on(`l`.`id_zona` = `z`.`id_zona`)) ;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `areas`
--
ALTER TABLE `areas`
  ADD PRIMARY KEY (`id_area`),
  ADD UNIQUE KEY `nombre_area` (`nombre_area`);

--
-- Indices de la tabla `escaneres`
--
ALTER TABLE `escaneres`
  ADD PRIMARY KEY (`id_escaner`),
  ADD UNIQUE KEY `id_insumo` (`id_insumo`);

--
-- Indices de la tabla `impresoras`
--
ALTER TABLE `impresoras`
  ADD PRIMARY KEY (`id_impresora`),
  ADD UNIQUE KEY `id_insumo` (`id_insumo`);

--
-- Indices de la tabla `insumos`
--
ALTER TABLE `insumos`
  ADD PRIMARY KEY (`id_insumo`),
  ADD UNIQUE KEY `uniq_id_patrimonio_idx` (`id_patrimonio_idx`),
  ADD KEY `id_punto_stock_actual` (`id_punto_stock_actual`),
  ADD KEY `id_area_asignacion_actual` (`id_area_asignacion_actual`),
  ADD KEY `id_sede_actual` (`id_sede_actual`),
  ADD KEY `idx_insumos_estado` (`estado`),
  ADD KEY `idx_insumos_tipo` (`tipo_insumo`),
  ADD KEY `idx_insumos_sede` (`id_sede_actual`),
  ADD KEY `idx_i_estado` (`estado`),
  ADD KEY `idx_i_tipo` (`tipo_insumo`),
  ADD KEY `idx_i_punto` (`id_punto_stock_actual`),
  ADD KEY `idx_i_sede_actual` (`id_sede_actual`),
  ADD KEY `idx_i_area_actual` (`id_area_asignacion_actual`);

--
-- Indices de la tabla `insumos_bajas`
--
ALTER TABLE `insumos_bajas`
  ADD PRIMARY KEY (`id_baja`),
  ADD KEY `idx_ib_insumo` (`id_insumo`);

--
-- Indices de la tabla `localidades`
--
ALTER TABLE `localidades`
  ADD PRIMARY KEY (`id_localidad`),
  ADD UNIQUE KEY `nombre_localidad` (`nombre_localidad`),
  ADD KEY `id_zona` (`id_zona`);

--
-- Indices de la tabla `monitores`
--
ALTER TABLE `monitores`
  ADD PRIMARY KEY (`id_monitor`),
  ADD UNIQUE KEY `id_insumo` (`id_insumo`);

--
-- Indices de la tabla `notebooks`
--
ALTER TABLE `notebooks`
  ADD PRIMARY KEY (`id_notebook`),
  ADD UNIQUE KEY `id_insumo` (`id_insumo`);

--
-- Indices de la tabla `pcs_completas`
--
ALTER TABLE `pcs_completas`
  ADD PRIMARY KEY (`id_pc_completa`),
  ADD UNIQUE KEY `id_insumo` (`id_insumo`);

--
-- Indices de la tabla `puntos_stock`
--
ALTER TABLE `puntos_stock`
  ADD PRIMARY KEY (`id_punto_stock`),
  ADD UNIQUE KEY `nombre_punto` (`nombre_punto`);

--
-- Indices de la tabla `remitos`
--
ALTER TABLE `remitos`
  ADD PRIMARY KEY (`id_remito`),
  ADD UNIQUE KEY `numero_remito` (`numero_remito`),
  ADD UNIQUE KEY `uniq_numero_remito` (`numero_remito`),
  ADD KEY `idx_remitos_fecha` (`fecha_asignacion`),
  ADD KEY `idx_remitos_estado` (`estado`),
  ADD KEY `fk_remitos_sede` (`id_sede`),
  ADD KEY `fk_remitos_area` (`id_area`),
  ADD KEY `idx_r_sede` (`id_sede`),
  ADD KEY `idx_r_area` (`id_area`),
  ADD KEY `idx_r_fecha` (`fecha_asignacion`);

--
-- Indices de la tabla `remitos_detalle`
--
ALTER TABLE `remitos_detalle`
  ADD PRIMARY KEY (`id_detalle`),
  ADD KEY `idx_rd_remito` (`id_remito`),
  ADD KEY `idx_rd_insumo` (`id_insumo`);

--
-- Indices de la tabla `remito_secuencia`
--
ALTER TABLE `remito_secuencia`
  ADD PRIMARY KEY (`anio`);

--
-- Indices de la tabla `sedes`
--
ALTER TABLE `sedes`
  ADD PRIMARY KEY (`id_sede`),
  ADD KEY `id_localidad` (`id_localidad`);

--
-- Indices de la tabla `sedes_internet`
--
ALTER TABLE `sedes_internet`
  ADD PRIMARY KEY (`id_internet`),
  ADD KEY `idx_si_sede` (`id_sede`),
  ADD KEY `idx_si_estado` (`estado_servicio`);

--
-- Indices de la tabla `sedes_planos`
--
ALTER TABLE `sedes_planos`
  ADD PRIMARY KEY (`id_plano`),
  ADD KEY `idx_sp_sede` (`id_sede`),
  ADD KEY `idx_sp_tipo` (`tipo_plano`);

--
-- Indices de la tabla `sedes_red_dispositivos`
--
ALTER TABLE `sedes_red_dispositivos`
  ADD PRIMARY KEY (`id_dispositivo`),
  ADD KEY `idx_srd_sede` (`id_sede`),
  ADD KEY `idx_srd_tipo` (`tipo_dispositivo`);

--
-- Indices de la tabla `sedes_telefonia_lineas`
--
ALTER TABLE `sedes_telefonia_lineas`
  ADD PRIMARY KEY (`id_linea`),
  ADD KEY `idx_stl_sede` (`id_sede`),
  ADD KEY `idx_stl_tipo` (`tipo_linea`);

--
-- Indices de la tabla `sedes_vigilancia`
--
ALTER TABLE `sedes_vigilancia`
  ADD PRIMARY KEY (`id_vigilancia`),
  ADD KEY `idx_svg_sede` (`id_sede`);

--
-- Indices de la tabla `sedes_vigilancia_dispositivos`
--
ALTER TABLE `sedes_vigilancia_dispositivos`
  ADD PRIMARY KEY (`id_vigilancia_dispositivo`),
  ADD KEY `idx_svd_vig` (`id_vigilancia`);

--
-- Indices de la tabla `sede_areas`
--
ALTER TABLE `sede_areas`
  ADD PRIMARY KEY (`id_sede_area`),
  ADD UNIQUE KEY `unique_sede_area` (`id_sede`,`id_area`),
  ADD KEY `fk_sede_areas_sede` (`id_sede`),
  ADD KEY `fk_sede_areas_area` (`id_area`);

--
-- Indices de la tabla `zonas`
--
ALTER TABLE `zonas`
  ADD PRIMARY KEY (`id_zona`),
  ADD UNIQUE KEY `nombre_zona` (`nombre_zona`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `areas`
--
ALTER TABLE `areas`
  MODIFY `id_area` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `escaneres`
--
ALTER TABLE `escaneres`
  MODIFY `id_escaner` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `impresoras`
--
ALTER TABLE `impresoras`
  MODIFY `id_impresora` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `insumos`
--
ALTER TABLE `insumos`
  MODIFY `id_insumo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT de la tabla `insumos_bajas`
--
ALTER TABLE `insumos_bajas`
  MODIFY `id_baja` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `localidades`
--
ALTER TABLE `localidades`
  MODIFY `id_localidad` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT de la tabla `monitores`
--
ALTER TABLE `monitores`
  MODIFY `id_monitor` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `notebooks`
--
ALTER TABLE `notebooks`
  MODIFY `id_notebook` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `pcs_completas`
--
ALTER TABLE `pcs_completas`
  MODIFY `id_pc_completa` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `puntos_stock`
--
ALTER TABLE `puntos_stock`
  MODIFY `id_punto_stock` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `remitos`
--
ALTER TABLE `remitos`
  MODIFY `id_remito` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT de la tabla `remitos_detalle`
--
ALTER TABLE `remitos_detalle`
  MODIFY `id_detalle` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;

--
-- AUTO_INCREMENT de la tabla `sedes`
--
ALTER TABLE `sedes`
  MODIFY `id_sede` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT de la tabla `sedes_internet`
--
ALTER TABLE `sedes_internet`
  MODIFY `id_internet` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `sedes_planos`
--
ALTER TABLE `sedes_planos`
  MODIFY `id_plano` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `sedes_red_dispositivos`
--
ALTER TABLE `sedes_red_dispositivos`
  MODIFY `id_dispositivo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `sedes_telefonia_lineas`
--
ALTER TABLE `sedes_telefonia_lineas`
  MODIFY `id_linea` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `sedes_vigilancia`
--
ALTER TABLE `sedes_vigilancia`
  MODIFY `id_vigilancia` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `sedes_vigilancia_dispositivos`
--
ALTER TABLE `sedes_vigilancia_dispositivos`
  MODIFY `id_vigilancia_dispositivo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `sede_areas`
--
ALTER TABLE `sede_areas`
  MODIFY `id_sede_area` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `zonas`
--
ALTER TABLE `zonas`
  MODIFY `id_zona` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `escaneres`
--
ALTER TABLE `escaneres`
  ADD CONSTRAINT `ESCANERES_ibfk_1` FOREIGN KEY (`id_insumo`) REFERENCES `insumos` (`id_insumo`);

--
-- Filtros para la tabla `impresoras`
--
ALTER TABLE `impresoras`
  ADD CONSTRAINT `IMPRESORAS_ibfk_1` FOREIGN KEY (`id_insumo`) REFERENCES `insumos` (`id_insumo`);

--
-- Filtros para la tabla `insumos`
--
ALTER TABLE `insumos`
  ADD CONSTRAINT `fk_i_area_actual` FOREIGN KEY (`id_area_asignacion_actual`) REFERENCES `areas` (`id_area`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_i_punto` FOREIGN KEY (`id_punto_stock_actual`) REFERENCES `puntos_stock` (`id_punto_stock`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_i_sede_actual` FOREIGN KEY (`id_sede_actual`) REFERENCES `sedes` (`id_sede`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_insumos_area_actual` FOREIGN KEY (`id_area_asignacion_actual`) REFERENCES `areas` (`id_area`),
  ADD CONSTRAINT `fk_insumos_punto_stock` FOREIGN KEY (`id_punto_stock_actual`) REFERENCES `puntos_stock` (`id_punto_stock`),
  ADD CONSTRAINT `fk_insumos_sede_actual` FOREIGN KEY (`id_sede_actual`) REFERENCES `sedes` (`id_sede`);

--
-- Filtros para la tabla `insumos_bajas`
--
ALTER TABLE `insumos_bajas`
  ADD CONSTRAINT `fk_ib_insumo` FOREIGN KEY (`id_insumo`) REFERENCES `insumos` (`id_insumo`);

--
-- Filtros para la tabla `localidades`
--
ALTER TABLE `localidades`
  ADD CONSTRAINT `LOCALIDADES_ibfk_1` FOREIGN KEY (`id_zona`) REFERENCES `zonas` (`id_zona`);

--
-- Filtros para la tabla `monitores`
--
ALTER TABLE `monitores`
  ADD CONSTRAINT `MONITORES_ibfk_1` FOREIGN KEY (`id_insumo`) REFERENCES `insumos` (`id_insumo`);

--
-- Filtros para la tabla `notebooks`
--
ALTER TABLE `notebooks`
  ADD CONSTRAINT `NOTEBOOKS_ibfk_1` FOREIGN KEY (`id_insumo`) REFERENCES `insumos` (`id_insumo`);

--
-- Filtros para la tabla `pcs_completas`
--
ALTER TABLE `pcs_completas`
  ADD CONSTRAINT `PCS_COMPLETAS_ibfk_1` FOREIGN KEY (`id_insumo`) REFERENCES `insumos` (`id_insumo`);

--
-- Filtros para la tabla `remitos`
--
ALTER TABLE `remitos`
  ADD CONSTRAINT `fk_r_area` FOREIGN KEY (`id_area`) REFERENCES `areas` (`id_area`),
  ADD CONSTRAINT `fk_r_sede` FOREIGN KEY (`id_sede`) REFERENCES `sedes` (`id_sede`),
  ADD CONSTRAINT `fk_remitos_area` FOREIGN KEY (`id_area`) REFERENCES `areas` (`id_area`),
  ADD CONSTRAINT `fk_remitos_sede` FOREIGN KEY (`id_sede`) REFERENCES `sedes` (`id_sede`);

--
-- Filtros para la tabla `remitos_detalle`
--
ALTER TABLE `remitos_detalle`
  ADD CONSTRAINT `fk_rd_insumo` FOREIGN KEY (`id_insumo`) REFERENCES `insumos` (`id_insumo`),
  ADD CONSTRAINT `fk_rd_remito` FOREIGN KEY (`id_remito`) REFERENCES `remitos` (`id_remito`);

--
-- Filtros para la tabla `sedes`
--
ALTER TABLE `sedes`
  ADD CONSTRAINT `SEDES_ibfk_1` FOREIGN KEY (`id_localidad`) REFERENCES `localidades` (`id_localidad`);

--
-- Filtros para la tabla `sedes_internet`
--
ALTER TABLE `sedes_internet`
  ADD CONSTRAINT `fk_si_sede` FOREIGN KEY (`id_sede`) REFERENCES `sedes` (`id_sede`);

--
-- Filtros para la tabla `sedes_planos`
--
ALTER TABLE `sedes_planos`
  ADD CONSTRAINT `fk_sp_sede` FOREIGN KEY (`id_sede`) REFERENCES `sedes` (`id_sede`);

--
-- Filtros para la tabla `sedes_red_dispositivos`
--
ALTER TABLE `sedes_red_dispositivos`
  ADD CONSTRAINT `fk_srd_sede` FOREIGN KEY (`id_sede`) REFERENCES `sedes` (`id_sede`);

--
-- Filtros para la tabla `sedes_telefonia_lineas`
--
ALTER TABLE `sedes_telefonia_lineas`
  ADD CONSTRAINT `fk_stl_sede` FOREIGN KEY (`id_sede`) REFERENCES `sedes` (`id_sede`);

--
-- Filtros para la tabla `sedes_vigilancia`
--
ALTER TABLE `sedes_vigilancia`
  ADD CONSTRAINT `fk_svg_sede` FOREIGN KEY (`id_sede`) REFERENCES `sedes` (`id_sede`);

--
-- Filtros para la tabla `sedes_vigilancia_dispositivos`
--
ALTER TABLE `sedes_vigilancia_dispositivos`
  ADD CONSTRAINT `fk_svd_vig` FOREIGN KEY (`id_vigilancia`) REFERENCES `sedes_vigilancia` (`id_vigilancia`);

--
-- Filtros para la tabla `sede_areas`
--
ALTER TABLE `sede_areas`
  ADD CONSTRAINT `fk_sede_areas_area` FOREIGN KEY (`id_area`) REFERENCES `areas` (`id_area`),
  ADD CONSTRAINT `fk_sede_areas_sede` FOREIGN KEY (`id_sede`) REFERENCES `sedes` (`id_sede`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
