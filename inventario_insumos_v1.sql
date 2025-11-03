-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost
-- Tiempo de generación: 03-11-2025 a las 13:05:24
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
(9, 7, 'Epsonnn', 'V3923'),
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
(4, 29, 'Lexmark', 'MS 215k'),
(8, 47, 'epson', 'lija '),
(17, 92, 'Hp', 'Mc 2023');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ingresos`
--

CREATE TABLE `ingresos` (
  `id_ingreso` int(11) NOT NULL,
  `tipo_ingreso` enum('fondos','compra_directa','licitacion','otros') DEFAULT 'licitacion',
  `nro_referencia` varchar(100) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_finalizacion` date DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `ingresos`
--

INSERT INTO `ingresos` (`id_ingreso`, `tipo_ingreso`, `nro_referencia`, `descripcion`, `fecha_finalizacion`, `created_at`, `updated_at`) VALUES
(28, 'compra_directa', 'Prueba fecha', NULL, '2025-10-01', '2025-10-23 10:41:38', '2025-10-23 10:41:38'),
(29, 'fondos', 'Número de nota de fondos', NULL, '2025-10-24', '2025-10-23 11:11:07', '2025-10-29 11:17:55'),
(30, 'otros', 'otra ves test', NULL, '2025-05-01', '2025-10-23 12:27:10', '2025-10-23 12:27:10'),
(31, 'licitacion', 'Lic-2025/10', NULL, '2025-10-30', '2025-10-24 11:37:54', '2025-10-24 11:37:54'),
(32, 'fondos', 'asdf', NULL, '2025-10-23', '2025-10-28 12:57:30', '2025-10-28 12:57:30'),
(33, 'licitacion', '135335-da-2025', 'Legitimo abono', '2025-07-02', '2025-10-29 11:20:14', '2025-10-29 11:20:14');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `insumos`
--

CREATE TABLE `insumos` (
  `id_insumo` int(11) NOT NULL,
  `nombre_insumo` varchar(100) DEFAULT NULL,
  `tipo_insumo` enum('Varios','PC Escritorio','Notebook','Impresora','Monitor','Escaner') NOT NULL,
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
  `id_ingreso` int(11) DEFAULT NULL,
  `es_nuevo` tinyint(1) DEFAULT 1 COMMENT '1=Nuevo, 0=Usado',
  `id_area_asignacion_actual` int(11) DEFAULT NULL,
  `id_patrimonio_idx` varchar(50) GENERATED ALWAYS AS (case when `tipo_insumo` <> 'Varios' then `id_patrimonio` else NULL end) VIRTUAL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `insumos`
--

INSERT INTO `insumos` (`id_insumo`, `nombre_insumo`, `tipo_insumo`, `subcategoria_varios`, `descripcion_general`, `numero_serie`, `id_fisico`, `id_patrimonio`, `cantidad`, `fecha_adquisicion`, `estado`, `id_punto_stock_actual`, `id_sede_actual`, `id_ingreso`, `es_nuevo`, `id_area_asignacion_actual`) VALUES
(6, 'Monitor recuperado en comision', 'Monitor', NULL, NULL, '32', '84621359', '646546gg', 1, '2025-08-18', 'Disponible', NULL, NULL, NULL, 1, NULL),
(7, 'Escarner Nuevo', 'Escaner', NULL, NULL, '108923', 'E45333', 'asfd123', 1, '2025-08-18', 'Disponible', NULL, NULL, 31, 1, NULL),
(16, 'PC Escritorio Oficina recuperada en comision', 'PC Escritorio', NULL, NULL, 'PC COMPLETA-SN0004', 'PC COMPLETA-ID0004', 'PAT00004', 1, '2025-09-16', 'De Baja', 2, NULL, NULL, 1, NULL),
(17, 'Notebook 14\"', 'Notebook', NULL, NULL, 'NOTEBOOK-SN0005', 'NOTEBOOK-ID0005', 'PAT00005', 1, '2025-09-16', 'Disponible', NULL, NULL, NULL, 1, NULL),
(19, 'Monitor 24\"', 'Monitor', NULL, NULL, 'MONITOR-SN0007', 'MONITOR-ID0007', 'PAT00007', 1, '2025-09-16', 'De Baja', 2, NULL, NULL, 1, NULL),
(21, 'Teclado USB', 'Varios', 'Periféricos', 'Demo seed', NULL, NULL, NULL, 4, '2025-09-16', 'Disponible', NULL, NULL, NULL, 1, NULL),
(23, 'Fichero', 'Escaner', NULL, NULL, '65468614', 'D466', 'D466', 1, '2025-10-14', 'Disponible', NULL, NULL, NULL, 1, NULL),
(27, 'Coradir 2020', 'PC Escritorio', NULL, NULL, '8768976', 'D342', 'D423', 1, '2025-10-17', 'Disponible', NULL, NULL, NULL, 1, NULL),
(29, 'Nueva', 'Impresora', NULL, NULL, '646979', 'D458', 'D748', 1, '2025-10-17', 'Disponible', NULL, NULL, NULL, 1, NULL),
(31, 'Test Accesorios', 'Notebook', NULL, NULL, '9879789', 'Df97', 'Df97', 1, '2025-10-17', 'Disponible', NULL, NULL, NULL, 1, NULL),
(47, 'impresora con scanner', 'Impresora', NULL, NULL, '3652656887795', '887795', '887795', 1, '2025-10-21', 'Disponible', NULL, NULL, NULL, 1, NULL),
(54, 'nueva fecha', 'Notebook', NULL, NULL, '2342', 's23', 's23', 1, '2025-11-23', 'Disponible', 2, NULL, NULL, 1, NULL),
(60, 'probando si la agregaa', 'PC Escritorio', NULL, NULL, 'asdlfkj', 'sdfj', 'adslfgk', 1, '2025-10-22', 'Disponible', 2, NULL, NULL, 1, NULL),
(61, 'Coradir', 'PC Escritorio', NULL, NULL, '394702', 'F45', 'F45', 1, '2025-10-22', 'Disponible', NULL, NULL, 30, 1, NULL),
(74, 'otra ves', 'Varios', 'Hardware', NULL, NULL, NULL, NULL, 8, '2025-10-24', 'Disponible', NULL, NULL, 29, 1, NULL),
(75, 'asdfa', 'Monitor', NULL, NULL, 'asdfas', 'asdfasd', 'asdfa', 1, '2025-10-01', 'Disponible', NULL, NULL, 28, 1, NULL),
(76, 'Prubando de nuevo', 'Varios', 'Hardware', NULL, NULL, NULL, NULL, 6, '2025-10-24', 'Disponible', 2, NULL, 29, 1, NULL),
(79, 'CABLE LIMPIO', 'Varios', 'Periféricos', NULL, NULL, NULL, NULL, 10, '2025-10-24', 'Disponible', 2, NULL, 31, 1, NULL),
(83, 'Probando 33', 'PC Escritorio', NULL, NULL, 'asdfkj', 'asldfk', 'asld', 1, '2025-10-30', 'Disponible', 2, NULL, 31, 1, NULL),
(86, 'rrrrr', 'PC Escritorio', NULL, NULL, 'rrrrrr', 'rrrrrr', 'rrrrr', 1, '2025-10-24', 'Disponible', 2, NULL, 29, 1, NULL),
(88, 'note note', 'Notebook', NULL, NULL, 'qsfeqsdf', 'asdfasdf', 'asdfasdf', 1, '2025-10-24', 'Disponible', 2, NULL, 29, 1, NULL),
(90, NULL, 'PC Escritorio', NULL, NULL, '54646', 'E15', 'E15', 1, '2025-10-24', 'Asignado', 2, 51, 29, 1, 9),
(91, 'moni moni', 'Monitor', NULL, NULL, 'sasdkf023\'204', 'saldfj3', '04\'0284lkm', 1, '2025-10-24', 'Disponible', 2, NULL, NULL, 1, NULL),
(92, 'Recuperada en comision', 'Impresora', NULL, NULL, '123123123', '123123123', '123123123', 1, '2025-10-30', 'Disponible', 2, NULL, 31, 1, NULL),
(93, 'Parlantes Marca Genius', 'Varios', 'Periféricos', NULL, NULL, NULL, NULL, 1, '2025-10-24', 'Disponible', 2, NULL, 29, 1, NULL),
(94, 'Tesr', 'PC Escritorio', NULL, NULL, 'asdaaf', 'asdfa', 'asdfaasd', 1, '2025-10-30', 'Disponible', 2, NULL, NULL, 1, NULL),
(95, NULL, 'PC Escritorio', NULL, NULL, '3908204', '0293420', '09283402', 1, '2025-10-30', 'Disponible', NULL, NULL, 31, 1, NULL),
(96, 'asdf', 'Varios', NULL, 'adsf', NULL, NULL, NULL, 1, '2025-07-02', 'Disponible', 2, NULL, 33, 1, NULL);

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
(3, 7, '2025-09-12 13:44:03', 'Se rompio', 1),
(4, 16, '2025-09-30 08:41:14', 'Se inundo por la lluvia y se quemo.', 1),
(10, 17, '2025-10-01 10:23:32', 'Se la robaron', 1),
(11, 19, '2025-10-03 09:04:51', 'Se quemo en una subida de tension, no tenia estabilizador.', 1),
(12, 21, '2025-10-24 08:08:28', 'Prueba', 3);

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
(5, 6, 'LG', 'Jk152', 19.0, 'VGA'),
(9, 75, 'asdfa', 'asdf', 12.0, 'VGA'),
(11, 91, 'waefj', 'oskdjf', 3.0, 'HDMI');

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
  `almacenamiento_gb` int(11) DEFAULT NULL,
  `cargador` tinyint(1) NOT NULL DEFAULT 0,
  `funda` tinyint(1) NOT NULL DEFAULT 0,
  `micro_sd` tinyint(1) NOT NULL DEFAULT 0,
  `micro_sd_gb` int(11) DEFAULT NULL,
  `caja` tinyint(1) NOT NULL DEFAULT 0,
  `adaptador_red` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `notebooks`
--

INSERT INTO `notebooks` (`id_notebook`, `id_insumo`, `marca`, `modelo`, `procesador`, `ram_gb`, `almacenamiento_gb`, `cargador`, `funda`, `micro_sd`, `micro_sd_gb`, `caja`, `adaptador_red`) VALUES
(9, 31, 'Sony Vaio', 'G85', 'I9 13200', 32, 2048, 1, 1, 1, 256, 1, 1),
(18, 54, 'hp', 'pavilion g54', 'i7 14000', 2, 2, 0, 0, 0, NULL, 0, 0),
(19, 88, 'sadfas', 'sadfa', 'asfdaf', 2, 2, 0, 1, 0, NULL, 0, 1),
(20, 17, 'Hp', 'Pavilion', 'I3-4478', 4, 500, 1, 1, 0, NULL, 0, 1);

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
  `mother` varchar(100) DEFAULT NULL,
  `sist_op` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pcs_completas`
--

INSERT INTO `pcs_completas` (`id_pc_completa`, `id_insumo`, `procesador`, `ram_gb`, `almacenamiento_gb`, `mother`, `sist_op`) VALUES
(16, 61, 'intel i15 rasonware', 32, 1000, 'asus h110', NULL),
(17, 60, 'sdlkg', 3, 3, 'sdfj', NULL),
(18, 27, 'I7-7845', 8, 500, 'ASUS H110M VK', NULL),
(25, 83, 'jdfh', 3, 3, 'wfopasjf', NULL),
(26, 86, 'rrr', 3, 3, '3rrr', NULL),
(30, 95, 'I5-15882U', 4, 45, '431', 'Ubuntu 24'),
(32, 94, 'I9 ultra core', 3, 3, 'asdf', 'Linux Mint 16.05'),
(33, 90, 'I9 ultra core', 3, 3, 'sdf', 'Ubuntu 22'),
(34, 16, 'sfaddfg', 3, 3, 'sdf', 'Win 11');

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
  `estado` enum('Activa','Devuelta','Anulado') NOT NULL DEFAULT 'Activa',
  `fecha_devolucion` date DEFAULT NULL,
  `observaciones` varchar(255) DEFAULT NULL,
  `motivo_anulacion` text DEFAULT NULL,
  `fecha_anulacion` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `remitos`
--

INSERT INTO `remitos` (`id_remito`, `numero_remito`, `id_sede`, `id_area`, `nombre_persona_asignada`, `apellido_persona_asignada`, `fecha_asignacion`, `estado`, `fecha_devolucion`, `observaciones`, `motivo_anulacion`, `fecha_anulacion`) VALUES
(49, '0036_2025', 38, 5, 'varios', 'devulucion', '2025-10-23', 'Activa', NULL, NULL, NULL, NULL),
(50, '0037_2025', 38, 9, 'QWEQ', 'QWEQ', '2025-10-23', 'Activa', NULL, NULL, NULL, NULL),
(51, '0038_2025', 27, 5, 'WWSD', 'ASD', '2025-10-23', 'Activa', NULL, NULL, NULL, NULL),
(53, '0040_2025', 38, 2, 'robert', 'jr asl', '2025-10-24', 'Devuelta', '2025-10-24', NULL, NULL, NULL),
(57, '0044_2025', 51, 2, 'ruy', 'rgerg', '2025-10-24', 'Anulado', NULL, NULL, 'Error de carga', '2025-10-27 08:52:47'),
(58, '0045_2025', 27, 5, 'asdasda', 'sdfadf', '2025-10-24', 'Devuelta', '2025-10-24', NULL, NULL, NULL),
(60, '0047_2025', 51, 9, 'diego', 'garcia', '2025-10-24', 'Activa', NULL, NULL, NULL, NULL),
(61, '0048_2025', 38, 2, 'joaquin', 'villaverde', '2025-10-24', 'Devuelta', '2025-10-24', NULL, NULL, NULL),
(62, '0001_2025', 44, 6, 'cintia', 'cuassolo', '2025-10-24', 'Anulado', NULL, NULL, 'Cambio de area', '2025-10-28 08:21:30'),
(64, '0050_2025', 38, 2, 'Probando', 'borrar', '2025-10-31', 'Anulado', NULL, NULL, 'Renuncio', '2025-10-31 11:47:33');

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
(91, 49, 61, 1, 1),
(95, 50, 75, 1, 1),
(96, 51, 74, 10, 8),
(98, 51, 21, 8, 7),
(100, 53, 79, 5, 5),
(104, 57, 86, 1, 0),
(105, 58, 88, 1, 1),
(107, 60, 90, 1, 0),
(108, 61, 75, 1, 1),
(111, 62, 61, 1, 0),
(112, 62, 75, 1, 0),
(115, 64, 75, 1, 0),
(116, 64, 95, 1, 0);

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
(2025, 50);

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
  `velocidad_mbps` int(11) DEFAULT NULL,
  `simetrico` tinyint(1) NOT NULL DEFAULT 0,
  `tiene_wifi` tinyint(1) NOT NULL DEFAULT 0,
  `estado_servicio` enum('Activo','Pendiente','De Baja','Baja por Traslado') NOT NULL DEFAULT 'Pendiente',
  `instancia_pendiente` enum('Solicitud de presupuesto','Autorización superior','Servicio tarifado') DEFAULT NULL COMMENT 'Instancia específica cuando el estado es Pendiente',
  `fecha_solicitud_autorizacion` date DEFAULT NULL COMMENT 'Fecha de solicitud cuando la instancia es Autorización superior',
  `archivo_autorizacion` varchar(255) DEFAULT NULL COMMENT 'Ruta del archivo PDF de autorización superior',
  `archivo_autorizacion_traslado` varchar(255) DEFAULT NULL COMMENT 'PDF de autorización del traslado',
  `fecha_instalacion` date DEFAULT NULL COMMENT 'Fecha programada de instalación cuando el estado es Pendiente',
  `fecha_baja` date DEFAULT NULL COMMENT 'Fecha en que el servicio pasó a estado De Baja',
  `fecha_traslado` date DEFAULT NULL COMMENT 'Fecha en que se dio de baja por traslado',
  `observaciones` varchar(255) DEFAULT NULL,
  `id_servicio_trasladado_a` int(11) DEFAULT NULL COMMENT 'ID del nuevo servicio creado tras el traslado',
  `id_servicio_trasladado_desde` int(11) DEFAULT NULL COMMENT 'ID del servicio anterior del cual proviene este traslado'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `sedes_internet`
--

INSERT INTO `sedes_internet` (`id_internet`, `id_sede`, `proveedor`, `tipo_conexion`, `velocidad_mbps`, `simetrico`, `tiene_wifi`, `estado_servicio`, `instancia_pendiente`, `fecha_solicitud_autorizacion`, `archivo_autorizacion`, `archivo_autorizacion_traslado`, `fecha_instalacion`, `fecha_baja`, `fecha_traslado`, `observaciones`, `id_servicio_trasladado_a`, `id_servicio_trasladado_desde`) VALUES
(11, 50, 'Fibertel', 'Fibra óptica', 100, 1, 1, 'Baja por Traslado', 'Autorización superior', '2025-10-16', 'public/uploads/autorizaciones_internet/autorizacion_20251030_092126_690358464dcb6.pdf', NULL, '2025-10-23', NULL, '2025-10-28', NULL, 12, NULL),
(12, 50, 'Fibertel', 'Fibra óptica', 100, 1, 1, 'Baja por Traslado', 'Autorización superior', '2025-10-28', 'public/uploads/autorizaciones_internet/traslado_20251030_092252_6903589ca504f.pdf', NULL, '2025-10-30', NULL, '2025-10-30', '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\r\n[ORIGEN DEL SERVICIO]\r\nTraslado desde servicio #11\r\nFecha: 28/10/2025', 13, 11),
(13, 50, 'Fibertel', 'Fibra óptica', 100, 1, 1, 'Baja por Traslado', 'Autorización superior', '2025-10-30', 'public/uploads/autorizaciones_internet/traslado_20251030_092649_6903598994136.pdf', NULL, '2025-10-29', NULL, '2025-10-30', '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\r\n[ORIGEN DEL SERVICIO]\r\nTraslado desde servicio #11\r\nFecha: 28/10/2025\r\n\r\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\r\n[ORIGEN DEL SERVICIO]\r\nTraslado desde servicio #12\r\nFecha: 30/10/2025', 14, 12),
(14, 50, 'Fibertel', 'Fibra óptica', 100, 1, 1, 'Baja por Traslado', 'Autorización superior', '2025-10-30', 'public/uploads/autorizaciones_internet/traslado_20251030_093632_69035bd0e3c29.pdf', NULL, '2025-10-30', NULL, '2025-10-30', NULL, 15, 13),
(15, 50, 'Fibertel', 'Fibra óptica', 100, 1, 1, 'Pendiente', 'Autorización superior', '2025-10-30', 'public/uploads/autorizaciones_internet/traslado_20251030_101217_690364313b3ee.pdf', NULL, NULL, NULL, NULL, '', NULL, 14);

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
(3, 49, 'Switch', 'hp aruba', '', 4, 'oficina 5, 6, 9', 'Activo', NULL);

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
,`tipo_insumo` enum('Varios','PC Escritorio','Notebook','Impresora','Monitor','Escaner')
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
-- Indices de la tabla `ingresos`
--
ALTER TABLE `ingresos`
  ADD PRIMARY KEY (`id_ingreso`),
  ADD UNIQUE KEY `cod_expediente` (`nro_referencia`);

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
  ADD KEY `idx_i_area_actual` (`id_area_asignacion_actual`),
  ADD KEY `idx_insumos_licitacion` (`id_ingreso`);

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
  ADD KEY `idx_r_fecha` (`fecha_asignacion`),
  ADD KEY `idx_estado` (`estado`);

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
  ADD KEY `idx_si_estado` (`estado_servicio`),
  ADD KEY `idx_instancia_pendiente` (`instancia_pendiente`),
  ADD KEY `idx_estado_servicio` (`estado_servicio`),
  ADD KEY `idx_fecha_instalacion` (`fecha_instalacion`),
  ADD KEY `idx_fecha_baja` (`fecha_baja`),
  ADD KEY `idx_trasladado_a` (`id_servicio_trasladado_a`),
  ADD KEY `idx_trasladado_desde` (`id_servicio_trasladado_desde`),
  ADD KEY `idx_fecha_traslado` (`fecha_traslado`);

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
  MODIFY `id_escaner` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `impresoras`
--
ALTER TABLE `impresoras`
  MODIFY `id_impresora` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de la tabla `ingresos`
--
ALTER TABLE `ingresos`
  MODIFY `id_ingreso` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT de la tabla `insumos`
--
ALTER TABLE `insumos`
  MODIFY `id_insumo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=97;

--
-- AUTO_INCREMENT de la tabla `insumos_bajas`
--
ALTER TABLE `insumos_bajas`
  MODIFY `id_baja` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `localidades`
--
ALTER TABLE `localidades`
  MODIFY `id_localidad` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT de la tabla `monitores`
--
ALTER TABLE `monitores`
  MODIFY `id_monitor` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `notebooks`
--
ALTER TABLE `notebooks`
  MODIFY `id_notebook` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de la tabla `pcs_completas`
--
ALTER TABLE `pcs_completas`
  MODIFY `id_pc_completa` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT de la tabla `puntos_stock`
--
ALTER TABLE `puntos_stock`
  MODIFY `id_punto_stock` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `remitos`
--
ALTER TABLE `remitos`
  MODIFY `id_remito` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- AUTO_INCREMENT de la tabla `remitos_detalle`
--
ALTER TABLE `remitos_detalle`
  MODIFY `id_detalle` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=117;

--
-- AUTO_INCREMENT de la tabla `sedes`
--
ALTER TABLE `sedes`
  MODIFY `id_sede` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT de la tabla `sedes_internet`
--
ALTER TABLE `sedes_internet`
  MODIFY `id_internet` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

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
  ADD CONSTRAINT `fk_insumos_ingreso` FOREIGN KEY (`id_ingreso`) REFERENCES `ingresos` (`id_ingreso`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_insumos_licitacion` FOREIGN KEY (`id_ingreso`) REFERENCES `ingresos` (`id_ingreso`) ON DELETE SET NULL,
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
