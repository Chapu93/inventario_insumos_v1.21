-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost
-- Tiempo de generación: 01-12-2025 a las 12:52:18
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
-- Estructura de tabla para la tabla `auditoria_acciones`
--

CREATE TABLE `auditoria_acciones` (
  `id_auditoria` bigint(20) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_sesion` int(11) DEFAULT NULL,
  `accion` varchar(100) NOT NULL COMMENT 'crear_insumo, editar_insumo, eliminar_insumo, etc.',
  `modulo` varchar(50) NOT NULL COMMENT 'insumos, asignaciones, reportes, usuarios, etc.',
  `descripcion` text NOT NULL,
  `entidad_tipo` varchar(50) DEFAULT NULL COMMENT 'insumo, asignacion, usuario, etc.',
  `entidad_id` int(11) DEFAULT NULL COMMENT 'ID de la entidad afectada',
  `datos_antes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Estado anterior (para ediciones/eliminaciones)' CHECK (json_valid(`datos_antes`)),
  `datos_despues` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Estado posterior (para creaciones/ediciones)' CHECK (json_valid(`datos_despues`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `resultado` enum('exito','error') NOT NULL DEFAULT 'exito',
  `mensaje_error` text DEFAULT NULL,
  `fecha_accion` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Auditoría completa de acciones del sistema';

--
-- Volcado de datos para la tabla `auditoria_acciones`
--

INSERT INTO `auditoria_acciones` (`id_auditoria`, `id_usuario`, `id_sesion`, `accion`, `modulo`, `descripcion`, `entidad_tipo`, `entidad_id`, `datos_antes`, `datos_despues`, `ip_address`, `resultado`, `mensaje_error`, `fecha_accion`) VALUES
(1, 1, NULL, 'login_fallido', 'usuarios', 'Intento de login fallido - Contraseña incorrecta para: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'error', 'Contraseña incorrecta', '2025-11-06 09:09:02'),
(2, 1, NULL, 'login_fallido', 'usuarios', 'Intento de login fallido - Contraseña incorrecta para: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'error', 'Contraseña incorrecta', '2025-11-06 09:09:11'),
(3, 1, NULL, 'login_fallido', 'usuarios', 'Intento de login fallido - Contraseña incorrecta para: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'error', 'Contraseña incorrecta', '2025-11-06 09:13:13'),
(4, 1, NULL, 'login_fallido', 'usuarios', 'Intento de login fallido - Contraseña incorrecta para: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'error', 'Contraseña incorrecta', '2025-11-06 09:13:34'),
(5, 1, NULL, 'login_fallido', 'usuarios', 'Intento de login fallido - Contraseña incorrecta para: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'error', 'Contraseña incorrecta', '2025-11-06 09:13:47'),
(6, 1, NULL, 'login_fallido', 'usuarios', 'Intento de login fallido - Contraseña incorrecta para: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'error', 'Contraseña incorrecta', '2025-11-06 09:13:54'),
(7, 1, NULL, 'login_fallido', 'usuarios', 'Intento de login fallido - Contraseña incorrecta para: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'error', 'Contraseña incorrecta', '2025-11-06 09:13:56'),
(8, 1, NULL, 'login_fallido', 'usuarios', 'Intento de login fallido - Contraseña incorrecta para: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'error', 'Contraseña incorrecta', '2025-11-06 09:13:59'),
(9, 1, NULL, 'login_fallido', 'usuarios', 'Intento de login fallido - Contraseña incorrecta para: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'error', 'Contraseña incorrecta', '2025-11-06 09:26:52'),
(10, 1, 1, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-11-06 09:31:10'),
(11, 1, 2, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '10.114.85.131', 'exito', NULL, '2025-11-06 09:37:42'),
(12, 1, 1, 'sesion_expirada', 'usuarios', 'Sesión expirada por inactividad', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-11-06 13:57:04'),
(13, 1, 1, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-11-06 13:57:04'),
(14, 1, 3, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-11-06 13:58:44'),
(15, 1, 4, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-11-07 10:08:41'),
(16, 1, 4, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-11-07 10:09:51'),
(17, 1, 5, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-11-07 10:16:22'),
(18, 1, 6, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-11-07 10:16:41'),
(19, 1, 6, 'sesion_expirada', 'usuarios', 'Sesión expirada por inactividad', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-11-07 13:14:57'),
(20, 1, 6, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-11-07 13:14:57'),
(21, 1, 7, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-11-07 13:52:08'),
(22, 1, 8, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-11-17 12:04:04'),
(23, 1, 8, 'sesion_expirada', 'usuarios', 'Sesión expirada por inactividad', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-11-17 13:11:52'),
(24, 1, 8, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-11-17 13:11:52'),
(25, 1, 9, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-11-17 13:11:58'),
(26, 1, 9, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-11-17 13:18:28'),
(27, 1, 10, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-11-17 13:18:33'),
(28, 1, 11, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-11-18 08:11:10'),
(29, 1, 11, 'crear_insumo', 'insumos', 'Insumo creado:  (Tipo: PC Escritorio)', 'insumo', 101, NULL, '{\"nombre_insumo\":null,\"tipo_insumo\":\"PC Escritorio\",\"cantidad\":\"1\",\"estado\":\"Disponible\"}', '127.0.0.1', 'exito', NULL, '2025-11-18 08:11:45'),
(30, 1, 11, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-11-18 08:12:29'),
(31, 1, 12, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-11-18 08:12:35'),
(32, 1, 12, 'crear_usuario', 'usuarios', 'Usuario creado: heber (heber gonzalez)', 'usuario', 2, NULL, '{\"username\":\"heber\",\"email\":\"hgonzalez@senaf.rionegro.gov.ar\",\"nombre\":\"heber\",\"apellido\":\"gonzalez\",\"id_rol\":2}', '127.0.0.1', 'exito', NULL, '2025-11-18 08:13:13'),
(33, 1, 12, 'desactivar_usuario', 'usuarios', 'Usuario desactivar: heber', 'usuario', 2, '{\"activo\":1}', '{\"activo\":0}', '127.0.0.1', 'exito', NULL, '2025-11-18 08:13:23'),
(34, 1, 12, 'editar_usuario', 'usuarios', 'Usuario editado: heber', 'usuario', 2, '{\"username\":\"heber\",\"email\":\"hgonzalez@senaf.rionegro.gov.ar\",\"nombre\":\"heber\",\"apellido\":\"gonzalez\",\"id_rol\":2}', '{\"username\":\"heber\",\"email\":\"hgonzalez@senaf.rionegro.gov.ar\",\"nombre\":\"heber\",\"apellido\":\"gonzalez\",\"id_rol\":2,\"password_cambiada\":false}', '127.0.0.1', 'exito', NULL, '2025-11-18 08:13:34'),
(35, 1, 12, 'cambiar_rol_usuario', 'usuarios', 'Rol cambiado para heber: Administrador → Operador', 'usuario', 2, '{\"id_rol\":2,\"rol\":\"Administrador\"}', '{\"id_rol\":3,\"rol\":\"Operador\"}', '127.0.0.1', 'exito', NULL, '2025-11-18 08:13:40'),
(36, 1, 12, 'crear_usuario', 'usuarios', 'Usuario creado: rjasd (aasd asdf)', 'usuario', 3, NULL, '{\"username\":\"rjasd\",\"email\":\"rj45@gmail.com\",\"nombre\":\"aasd\",\"apellido\":\"asdf\",\"id_rol\":2}', '127.0.0.1', 'exito', NULL, '2025-11-18 08:15:25'),
(37, 1, 12, 'cambiar_rol_usuario', 'usuarios', 'Rol cambiado para rjasd: Administrador → Consultor', 'usuario', 3, '{\"id_rol\":2,\"rol\":\"Administrador\"}', '{\"id_rol\":4,\"rol\":\"Consultor\"}', '127.0.0.1', 'exito', NULL, '2025-11-18 08:15:31'),
(38, 1, 12, 'editar_insumo', 'insumos', 'Insumo editado: Parlantes Marca Genius (ID: 93)', 'insumo', 93, '{\"id_insumo\":93,\"nombre_insumo\":\"Parlantes Marca Genius\",\"tipo_insumo\":\"Varios\",\"subcategoria_varios\":\"Periféricos\",\"descripcion_general\":null,\"numero_serie\":null,\"id_fisico\":null,\"id_patrimonio\":null,\"cantidad\":11,\"cantidad_oficina\":6,\"cantidad_deposito\":5,\"fecha_adquisicion\":\"2025-10-24\",\"estado\":\"Disponible\",\"id_punto_stock_actual\":2,\"id_sede_actual\":null,\"id_ingreso\":29,\"es_nuevo\":1,\"id_area_asignacion_actual\":null,\"id_patrimonio_idx\":null}', '{\"nombre_insumo\":\"Parlantes Marca Genius\",\"tipo_insumo\":\"Varios\",\"cantidad\":11}', '127.0.0.1', 'exito', NULL, '2025-11-18 08:28:31'),
(39, 1, 12, 'cambiar_rol_usuario', 'usuarios', 'Rol cambiado para rjasd: Consultor → Operador', 'usuario', 3, '{\"id_rol\":4,\"rol\":\"Consultor\"}', '{\"id_rol\":3,\"rol\":\"Operador\"}', '127.0.0.1', 'exito', NULL, '2025-11-18 08:28:55'),
(40, 1, 12, 'activar_usuario', 'usuarios', 'Usuario activar: heber', 'usuario', 2, '{\"activo\":0}', '{\"activo\":1}', '127.0.0.1', 'exito', NULL, '2025-11-18 08:29:13'),
(41, 1, 12, 'cambiar_rol_usuario', 'usuarios', 'Rol cambiado para rjasd: Operador → Administrador', 'usuario', 3, '{\"id_rol\":3,\"rol\":\"Operador\"}', '{\"id_rol\":2,\"rol\":\"Administrador\"}', '127.0.0.1', 'exito', NULL, '2025-11-18 08:29:35'),
(42, 1, 12, 'cambiar_rol_usuario', 'usuarios', 'Rol cambiado para rjasd: Administrador → Operador', 'usuario', 3, '{\"id_rol\":2,\"rol\":\"Administrador\"}', '{\"id_rol\":3,\"rol\":\"Operador\"}', '127.0.0.1', 'exito', NULL, '2025-11-18 08:29:46'),
(43, 1, 12, 'cambiar_rol_usuario', 'usuarios', 'Rol cambiado para rjasd: Operador → Administrador', 'usuario', 3, '{\"id_rol\":3,\"rol\":\"Operador\"}', '{\"id_rol\":2,\"rol\":\"Administrador\"}', '127.0.0.1', 'exito', NULL, '2025-11-18 08:30:03'),
(44, 1, 12, 'cambiar_rol_usuario', 'usuarios', 'Rol cambiado para rjasd: Administrador → Operador', 'usuario', 3, '{\"id_rol\":2,\"rol\":\"Administrador\"}', '{\"id_rol\":3,\"rol\":\"Operador\"}', '127.0.0.1', 'exito', NULL, '2025-11-18 08:51:28'),
(45, 1, 13, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-11-18 08:51:57'),
(46, 1, 12, 'sesion_expirada', 'usuarios', 'Sesión expirada por inactividad', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-11-18 10:13:15'),
(47, 1, 12, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-11-18 10:13:15'),
(48, 1, 14, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-11-18 10:13:19'),
(49, 1, 14, 'sesion_expirada', 'usuarios', 'Sesión expirada por inactividad', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-11-18 12:18:22'),
(50, 1, 14, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-11-18 12:18:22'),
(51, 1, 15, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-11-18 12:18:32'),
(52, 1, NULL, 'login_fallido', 'usuarios', 'Intento de login fallido - Contraseña incorrecta para: admin', NULL, NULL, NULL, NULL, '10.114.85.131', 'error', 'Contraseña incorrecta', '2025-11-18 12:35:08'),
(53, 1, 16, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '10.114.85.131', 'exito', NULL, '2025-11-18 12:35:16'),
(54, 1, 16, 'crear_insumo', 'insumos', 'Insumo creado:  (Tipo: Varios)', 'insumo', 102, NULL, '{\"nombre_insumo\":null,\"tipo_insumo\":\"Varios\",\"cantidad\":113,\"estado\":\"Disponible\"}', '10.114.85.131', 'exito', NULL, '2025-11-18 12:36:17'),
(55, 1, 15, 'cambiar_rol_usuario', 'usuarios', 'Rol cambiado para rjasd: Operador → Administrador', 'usuario', 3, '{\"id_rol\":3,\"rol\":\"Operador\"}', '{\"id_rol\":2,\"rol\":\"Administrador\"}', '127.0.0.1', 'exito', NULL, '2025-11-18 12:38:45'),
(56, 1, 16, 'cambiar_rol_usuario', 'usuarios', 'Rol cambiado para heber: Operador → Consultor', 'usuario', 2, '{\"id_rol\":3,\"rol\":\"Operador\"}', '{\"id_rol\":4,\"rol\":\"Consultor\"}', '10.114.85.131', 'exito', NULL, '2025-11-18 12:39:10'),
(57, 1, 16, 'editar_usuario', 'usuarios', 'Usuario editado: heber', 'usuario', 2, '{\"username\":\"heber\",\"email\":\"hgonzalez@senaf.rionegro.gov.ar\",\"nombre\":\"heber\",\"apellido\":\"gonzalez\",\"id_rol\":4}', '{\"username\":\"heber\",\"email\":\"hgonzalez@senaf.rionegro.gov.ar\",\"nombre\":\"heber\",\"apellido\":\"gonzalez\",\"id_rol\":3,\"password_cambiada\":false}', '10.114.85.131', 'exito', NULL, '2025-11-18 12:39:33'),
(58, 1, 15, 'cambiar_rol_usuario', 'usuarios', 'Rol cambiado para heber: Operador → Super Administrador', 'usuario', 2, '{\"id_rol\":3,\"rol\":\"Operador\"}', '{\"id_rol\":1,\"rol\":\"Super Administrador\"}', '127.0.0.1', 'exito', NULL, '2025-11-18 12:39:35'),
(59, 1, 17, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-11-19 08:07:53'),
(60, 1, 17, 'cambiar_rol_usuario', 'usuarios', 'Rol cambiado para rjasd: Administrador → Operador', 'usuario', 3, '{\"id_rol\":2,\"rol\":\"Administrador\"}', '{\"id_rol\":3,\"rol\":\"Operador\"}', '127.0.0.1', 'exito', NULL, '2025-11-19 08:08:10'),
(61, 1, 17, 'cambiar_rol_usuario', 'usuarios', 'Rol cambiado para rjasd: Operador → Administrador', 'usuario', 3, '{\"id_rol\":3,\"rol\":\"Operador\"}', '{\"id_rol\":2,\"rol\":\"Administrador\"}', '127.0.0.1', 'exito', NULL, '2025-11-19 08:11:58'),
(62, 1, 17, 'cambiar_rol_usuario', 'usuarios', 'Rol cambiado para rjasd: Administrador → Operador', 'usuario', 3, '{\"id_rol\":2,\"rol\":\"Administrador\"}', '{\"id_rol\":3,\"rol\":\"Operador\"}', '127.0.0.1', 'exito', NULL, '2025-11-19 08:12:07'),
(63, 1, 17, 'cambiar_rol_usuario', 'usuarios', 'Rol cambiado para rjasd: Operador → Administrador', 'usuario', 3, '{\"id_rol\":3,\"rol\":\"Operador\"}', '{\"id_rol\":2,\"rol\":\"Administrador\"}', '127.0.0.1', 'exito', NULL, '2025-11-19 08:14:28'),
(64, 1, 17, 'cambiar_rol_usuario', 'usuarios', 'Rol cambiado para rjasd: Administrador → Operador', 'usuario', 3, '{\"id_rol\":2,\"rol\":\"Administrador\"}', '{\"id_rol\":3,\"rol\":\"Operador\"}', '127.0.0.1', 'exito', NULL, '2025-11-19 08:14:38'),
(65, 1, 18, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-11-20 08:25:51'),
(66, 1, 18, 'crear_insumo', 'insumos', 'Insumo creado:  (Tipo: Varios)', 'insumo', 103, NULL, '{\"nombre_insumo\":null,\"tipo_insumo\":\"Varios\",\"cantidad\":36,\"estado\":\"Disponible\"}', '127.0.0.1', 'exito', NULL, '2025-11-20 08:38:19'),
(67, 1, 18, 'crear_insumo', 'insumos', 'Insumo creado:  (Tipo: Notebook)', 'insumo', 104, NULL, '{\"nombre_insumo\":null,\"tipo_insumo\":\"Notebook\",\"cantidad\":\"1\",\"estado\":\"Disponible\"}', '127.0.0.1', 'exito', NULL, '2025-11-20 09:00:26'),
(68, 1, 18, 'baja_insumo', 'insumos', 'Baja de insumo (ID: 102): wrqwr - Cantidad: 20', 'insumo', 102, '{\"estado\":\"Disponible\",\"cantidad\":113}', '{\"estado\":\"Disponible\",\"cantidad\":93}', '127.0.0.1', 'exito', NULL, '2025-11-20 09:14:08'),
(69, 1, 19, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-11-20 09:32:40'),
(70, 1, 20, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-11-20 09:54:27'),
(71, 1, 18, 'sesion_expirada', 'usuarios', 'Sesión expirada por inactividad', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-11-20 13:14:41'),
(72, 1, 18, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-11-20 13:14:41'),
(73, 1, 21, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-11-20 13:14:46'),
(74, 1, 22, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-11-25 08:55:15'),
(75, 1, 22, 'cambiar_rol_usuario', 'usuarios', 'Rol cambiado para rjasd: Operador → Administrador', 'usuario', 3, '{\"id_rol\":3,\"rol\":\"Operador\"}', '{\"id_rol\":2,\"rol\":\"Administrador\"}', '127.0.0.1', 'exito', NULL, '2025-11-25 08:56:54'),
(76, 1, 22, 'desactivar_usuario', 'usuarios', 'Usuario desactivar: rjasd', 'usuario', 3, '{\"activo\":1}', '{\"activo\":0}', '127.0.0.1', 'exito', NULL, '2025-11-25 08:57:11'),
(77, 1, 22, 'sesion_expirada', 'usuarios', 'Sesión expirada por inactividad', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-11-25 09:35:54'),
(78, 1, 22, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-11-25 09:35:54'),
(79, 1, 23, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-11-25 09:39:24'),
(80, 1, 24, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '::1', 'exito', NULL, '2025-11-25 10:10:12'),
(81, 1, 23, 'sesion_expirada', 'usuarios', 'Sesión expirada por inactividad', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-11-25 10:16:08'),
(82, 1, 23, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-11-25 10:16:08'),
(83, 1, 25, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-11-25 10:16:14'),
(84, 1, 25, 'sesion_expirada', 'usuarios', 'Sesión expirada por inactividad', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-11-25 10:46:25'),
(85, 1, 25, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-11-25 10:46:25'),
(86, 1, 26, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-11-25 10:46:30'),
(87, 1, 26, 'crear_asignacion', 'asignaciones', 'Asignación creada - Remito: 0053_2025 - Persona: apskgdj asdf', 'asignacion', 69, NULL, '{\"numero_remito\":\"0053_2025\",\"sede\":51,\"area\":5,\"persona\":\"apskgdj asdf\",\"insumos_count\":5}', '127.0.0.1', 'exito', NULL, '2025-11-25 10:47:51'),
(88, 1, 26, 'sesion_expirada', 'usuarios', 'Sesión expirada por inactividad', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-11-25 12:58:42'),
(89, 1, 26, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-11-25 12:58:42'),
(90, 1, 27, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-11-25 13:00:43'),
(91, 1, 27, 'baja_insumo', 'insumos', 'Baja de insumo (ID: 93): asd - Cantidad: 1', 'insumo', 93, '{\"estado\":\"Disponible\",\"cantidad\":11}', '{\"estado\":\"Disponible\",\"cantidad\":10}', '127.0.0.1', 'exito', NULL, '2025-11-25 13:00:47'),
(92, 1, 27, 'baja_insumo', 'insumos', 'Baja de insumo (ID: 21): sdfsd - Cantidad: 2', 'insumo', 21, '{\"estado\":\"Disponible\",\"cantidad\":4}', '{\"estado\":\"Disponible\",\"cantidad\":2}', '127.0.0.1', 'exito', NULL, '2025-11-25 13:16:59'),
(93, 1, 28, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '10.114.85.1', 'exito', NULL, '2025-11-25 13:21:46'),
(94, 1, NULL, 'login_fallido', 'usuarios', 'Intento de login fallido - Contraseña incorrecta para: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'error', 'Contraseña incorrecta', '2025-11-26 08:10:59'),
(95, 1, 29, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-11-26 08:11:03'),
(96, 1, 29, 'crear_usuario', 'usuarios', 'Usuario creado: adminis (adminis trador)', 'usuario', 4, NULL, '{\"username\":\"adminis\",\"email\":\"adminis@gmail.com\",\"nombre\":\"adminis\",\"apellido\":\"trador\",\"id_rol\":2}', '127.0.0.1', 'exito', NULL, '2025-11-26 08:12:08'),
(97, 1, 29, 'crear_usuario', 'usuarios', 'Usuario creado: opera (opera dor)', 'usuario', 5, NULL, '{\"username\":\"opera\",\"email\":\"opera@gmail.com\",\"nombre\":\"opera\",\"apellido\":\"dor\",\"id_rol\":3}', '127.0.0.1', 'exito', NULL, '2025-11-26 08:13:30'),
(98, 1, 29, 'crear_usuario', 'usuarios', 'Usuario creado: consul (consul tor)', 'usuario', 6, NULL, '{\"username\":\"consul\",\"email\":\"consul@gmail.com\",\"nombre\":\"consul\",\"apellido\":\"tor\",\"id_rol\":4}', '127.0.0.1', 'exito', NULL, '2025-11-26 08:14:20'),
(99, 1, 29, 'crear_usuario', 'usuarios', 'Usuario creado: super (super admin)', 'usuario', 7, NULL, '{\"username\":\"super\",\"email\":\"super@gmail.com\",\"nombre\":\"super\",\"apellido\":\"admin\",\"id_rol\":1}', '127.0.0.1', 'exito', NULL, '2025-11-26 08:15:30'),
(100, 7, 30, 'login', 'usuarios', 'Login exitoso: super', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-11-26 08:29:39'),
(101, 7, 30, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-11-26 08:29:46'),
(102, 6, 31, 'login', 'usuarios', 'Login exitoso: consul', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-11-26 08:29:55'),
(103, 6, 31, 'editar_insumo', 'insumos', 'Insumo editado: emponits (ID: 103)', 'insumo', 103, '{\"id_insumo\":103,\"nombre_insumo\":\"emponits\",\"tipo_insumo\":\"Varios\",\"subcategoria_varios\":\"Hardware\",\"descripcion_general\":\"sefd\",\"numero_serie\":null,\"id_fisico\":null,\"id_patrimonio\":null,\"cantidad\":36,\"cantidad_oficina\":3,\"cantidad_deposito\":33,\"fecha_adquisicion\":\"2025-11-20\",\"estado\":\"Disponible\",\"id_punto_stock_actual\":null,\"id_sede_actual\":null,\"id_ingreso\":null,\"es_nuevo\":1,\"id_area_asignacion_actual\":null,\"id_patrimonio_idx\":null}', '{\"nombre_insumo\":\"emponits\",\"tipo_insumo\":\"Varios\",\"cantidad\":36}', '127.0.0.1', 'exito', NULL, '2025-11-26 08:30:06'),
(104, 6, 31, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-11-26 08:30:15'),
(105, 5, 32, 'login', 'usuarios', 'Login exitoso: opera', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-11-26 08:30:26'),
(106, 1, 29, 'sesion_expirada', 'usuarios', 'Sesión expirada por inactividad', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-11-26 08:55:16'),
(107, 1, 29, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-11-26 08:55:16'),
(108, 1, 33, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-11-26 08:55:21'),
(109, 5, 32, 'sesion_expirada', 'usuarios', 'Sesión expirada por inactividad', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-11-26 09:26:03'),
(110, 5, 32, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-11-26 09:26:03'),
(111, 1, 33, 'sesion_expirada', 'usuarios', 'Sesión expirada por inactividad', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-11-26 10:49:49'),
(112, 1, 33, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-11-26 10:49:49'),
(113, 1, 34, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-11-26 11:46:12'),
(114, 1, 35, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-01 08:51:11');

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
(34, 'fondos', 'awe', 'qweq', '2025-11-11', '2025-11-18 08:27:59', '2025-11-18 08:27:59'),
(35, 'compra_directa', 'asd', NULL, '2025-11-28', '2025-11-25 10:47:24', '2025-11-25 10:47:24');

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
  `cantidad_oficina` int(11) DEFAULT NULL COMMENT 'Stock disponible en oficina para asignaciones inmediatas (solo tipo Varios)',
  `cantidad_deposito` int(11) DEFAULT NULL COMMENT 'Stock en depósito, requiere reposición a oficina (solo tipo Varios)',
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

INSERT INTO `insumos` (`id_insumo`, `nombre_insumo`, `tipo_insumo`, `subcategoria_varios`, `descripcion_general`, `numero_serie`, `id_fisico`, `id_patrimonio`, `cantidad`, `cantidad_oficina`, `cantidad_deposito`, `fecha_adquisicion`, `estado`, `id_punto_stock_actual`, `id_sede_actual`, `id_ingreso`, `es_nuevo`, `id_area_asignacion_actual`) VALUES
(6, 'Monitor recuperado en comision', 'Monitor', NULL, NULL, '32', '84621359', '646546gg', 1, NULL, NULL, '2025-08-18', 'Disponible', NULL, NULL, NULL, 1, NULL),
(7, 'Escarner Nuevo', 'Escaner', NULL, NULL, '108923', 'E45333', 'asfd123', 1, NULL, NULL, '2025-08-18', 'Disponible', NULL, NULL, 31, 1, NULL),
(16, 'PC Escritorio Oficina recuperada en comision', 'PC Escritorio', NULL, NULL, 'PC COMPLETA-SN0004', 'PC COMPLETA-ID0004', 'PAT00004', 1, NULL, NULL, '2025-09-16', 'De Baja', 2, NULL, NULL, 0, NULL),
(17, 'Notebook 14\"', 'Notebook', NULL, NULL, NULL, 'NOTEBOOK-ID0005', 'PAT00005', 1, NULL, NULL, '2025-09-16', 'Disponible', NULL, NULL, NULL, 1, NULL),
(19, 'Monitor 24\"', 'Monitor', NULL, NULL, 'MONITOR-SN0007', 'MONITOR-ID0007', 'PAT00007', 1, NULL, NULL, '2025-09-16', 'De Baja', 2, NULL, NULL, 1, NULL),
(21, 'Teclado USB', 'Varios', 'Periféricos', 'Demo seed', NULL, NULL, NULL, 4, 4, 0, '2025-09-16', 'Disponible', NULL, NULL, NULL, 1, NULL),
(23, 'Fichero', 'Escaner', NULL, NULL, '65468614', 'D466', 'D466', 1, NULL, NULL, '2025-10-14', 'Asignado', NULL, 51, NULL, 1, 5),
(27, 'Coradir 2020', 'PC Escritorio', NULL, NULL, '8768976', 'D342', 'D423', 1, NULL, NULL, '2025-10-17', 'Disponible', NULL, NULL, NULL, 1, NULL),
(29, 'Nueva', 'Impresora', NULL, NULL, '646979', 'D458', 'D748', 1, NULL, NULL, '2025-10-17', 'Disponible', NULL, NULL, NULL, 1, NULL),
(31, 'Test Accesorios', 'Notebook', NULL, NULL, '9879789', 'Df97', 'Df97', 1, NULL, NULL, '2025-10-17', 'Disponible', NULL, NULL, NULL, 1, NULL),
(47, 'impresora con scanner', 'Impresora', NULL, NULL, '3652656887795', '887795', '887795', 1, NULL, NULL, '2025-10-21', 'Disponible', NULL, NULL, NULL, 1, NULL),
(54, 'nueva fecha', 'Notebook', NULL, NULL, '2342', 's23', 's23', 1, NULL, NULL, '2025-11-23', 'Disponible', 2, NULL, NULL, 1, NULL),
(60, 'probando si la agregaa', 'PC Escritorio', NULL, NULL, 'asdlfkj', 'sdfj', 'adslfgk', 1, NULL, NULL, '2025-10-22', 'Disponible', 2, NULL, NULL, 1, NULL),
(61, 'Coradir', 'PC Escritorio', NULL, NULL, '394702', 'F45', 'F45', 1, NULL, NULL, '2025-10-22', 'Disponible', NULL, NULL, 30, 1, NULL),
(74, 'otra ves', 'Varios', 'Hardware', NULL, NULL, NULL, NULL, 23, 18, 5, '2025-10-24', 'Disponible', NULL, NULL, 29, 1, NULL),
(75, 'asdfa', 'Monitor', NULL, NULL, 'asdfas', 'asdfasd', 'asdfa', 1, NULL, NULL, '2025-10-01', 'Disponible', NULL, NULL, 28, 1, NULL),
(83, 'Probando 33', 'PC Escritorio', NULL, NULL, 'asdfkj', 'asldfk', 'asld', 1, NULL, NULL, '2025-10-30', 'Disponible', 2, NULL, 31, 1, NULL),
(86, 'rrrrr', 'PC Escritorio', NULL, NULL, 'rrrrrr', 'rrrrrr', 'rrrrr', 1, NULL, NULL, '2025-10-24', 'Disponible', 2, NULL, 29, 1, NULL),
(88, 'note note', 'Notebook', NULL, NULL, 'qsfeqsdf', 'asdfasdf', 'asdfasdf', 1, NULL, NULL, '2025-10-24', 'Disponible', 2, NULL, 29, 1, NULL),
(90, NULL, 'PC Escritorio', NULL, NULL, '54646', NULL, NULL, 1, NULL, NULL, '2025-10-24', 'Disponible', 2, NULL, 29, 1, NULL),
(91, 'moni moni', 'Monitor', NULL, NULL, 'sasdkf023\'204', 'saldfj3', '04\'0284lkm', 1, NULL, NULL, '2025-10-24', 'Disponible', 2, NULL, NULL, 1, NULL),
(92, 'Recuperada en comision', 'Impresora', NULL, NULL, '123123123', '123123123', '123123123', 1, NULL, NULL, '2025-10-30', 'Disponible', 2, NULL, 31, 1, NULL),
(94, 'Tesr', 'PC Escritorio', NULL, NULL, 'asdaaf', 'asdfa', 'asdfaasd', 1, NULL, NULL, '2025-10-30', 'Disponible', 2, NULL, NULL, 1, NULL),
(95, NULL, 'PC Escritorio', NULL, NULL, '3908204', '0293420', '09283402', 1, NULL, NULL, '2025-10-30', 'Asignado', NULL, 51, 31, 1, 5),
(97, 'sdf', 'PC Escritorio', NULL, NULL, NULL, 'sd', 'sdf', 1, NULL, NULL, '2025-11-04', 'Disponible', 2, NULL, NULL, 1, NULL),
(98, 'qwerqw', 'PC Escritorio', NULL, NULL, NULL, 'qwer', 'qwer', 1, NULL, NULL, '2025-11-04', 'Disponible', 2, NULL, NULL, 1, NULL),
(100, 'dfgh', 'Notebook', NULL, NULL, 'dfgh', NULL, NULL, 1, NULL, NULL, '2025-11-05', 'Asignado', NULL, 51, NULL, 1, 5),
(101, 'asdf', 'PC Escritorio', NULL, NULL, 'asdf', 'asdf', 'asdf', 1, NULL, NULL, '2025-11-18', 'Disponible', NULL, NULL, NULL, 1, NULL),
(102, 'Style USB', 'Varios', 'Hardware', 'para si algun dia compramos IPAds', NULL, NULL, NULL, 110, 95, 15, '2025-05-01', 'Disponible', NULL, 51, 30, 1, 5),
(104, 'm', 'Notebook', NULL, NULL, 'h', 'h', 'h', 1, NULL, NULL, '2025-11-20', 'Disponible', NULL, NULL, NULL, 1, NULL),
(105, 'asdas', 'Notebook', NULL, NULL, 'asdasda', 'asdasda', 'asdasda', 1, NULL, NULL, '2025-11-25', 'Disponible', 2, NULL, NULL, 1, NULL);

--
-- Disparadores `insumos`
--
DELIMITER $$
CREATE TRIGGER `trg_insumos_sync_cantidad_insert` BEFORE INSERT ON `insumos` FOR EACH ROW BEGIN
  -- Para tipo Varios, calcular cantidad total
  IF NEW.tipo_insumo = 'Varios' THEN
    IF NEW.cantidad_oficina IS NULL THEN
      SET NEW.cantidad_oficina = 0;
    END IF;
    IF NEW.cantidad_deposito IS NULL THEN
      SET NEW.cantidad_deposito = 0;
    END IF;
    SET NEW.cantidad = NEW.cantidad_oficina + NEW.cantidad_deposito;
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_insumos_sync_cantidad_update` BEFORE UPDATE ON `insumos` FOR EACH ROW BEGIN
  -- Para tipo Varios, calcular cantidad total
  IF NEW.tipo_insumo = 'Varios' THEN
    IF NEW.cantidad_oficina IS NULL THEN
      SET NEW.cantidad_oficina = 0;
    END IF;
    IF NEW.cantidad_deposito IS NULL THEN
      SET NEW.cantidad_deposito = 0;
    END IF;
    SET NEW.cantidad = NEW.cantidad_oficina + NEW.cantidad_deposito;
  END IF;
END
$$
DELIMITER ;

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
(12, 21, '2025-10-24 08:08:28', 'Prueba', 3),
(14, 102, '2025-11-20 09:14:08', 'wrqwr', 20),
(16, 21, '2025-11-25 13:16:59', 'sdfsd', 2);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `insumos_movimientos_stock`
--

CREATE TABLE `insumos_movimientos_stock` (
  `id_movimiento` int(11) NOT NULL,
  `id_insumo` int(11) NOT NULL,
  `tipo_movimiento` enum('reposicion_oficina','devolucion_a_deposito','ajuste_manual','ingreso_nuevo') NOT NULL,
  `cantidad_movida` int(11) NOT NULL COMMENT 'Cantidad trasladada/ajustada',
  `ubicacion_origen` enum('deposito','oficina','externo','N/A') NOT NULL,
  `ubicacion_destino` enum('deposito','oficina','externo','N/A') NOT NULL,
  `cantidad_oficina_antes` int(11) NOT NULL,
  `cantidad_deposito_antes` int(11) NOT NULL,
  `cantidad_oficina_despues` int(11) NOT NULL,
  `cantidad_deposito_despues` int(11) NOT NULL,
  `usuario` varchar(100) DEFAULT NULL COMMENT 'Usuario que realizó el movimiento',
  `observacion` text DEFAULT NULL,
  `fecha_movimiento` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Historial de movimientos de stock entre oficina y depósito';

--
-- Volcado de datos para la tabla `insumos_movimientos_stock`
--

INSERT INTO `insumos_movimientos_stock` (`id_movimiento`, `id_insumo`, `tipo_movimiento`, `cantidad_movida`, `ubicacion_origen`, `ubicacion_destino`, `cantidad_oficina_antes`, `cantidad_deposito_antes`, `cantidad_oficina_despues`, `cantidad_deposito_despues`, `usuario`, `observacion`, `fecha_movimiento`) VALUES
(6, 74, 'reposicion_oficina', 5, 'deposito', 'oficina', 8, 15, 13, 10, NULL, '', '2025-11-05 12:38:52'),
(8, 102, 'reposicion_oficina', 10, 'deposito', 'oficina', 88, 25, 98, 15, NULL, '', '2025-11-19 08:17:39'),
(9, 74, 'reposicion_oficina', 5, 'deposito', 'oficina', 13, 10, 18, 5, NULL, '', '2025-11-20 09:12:02');

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
(21, 17, 'Hp', 'Pavilion', 'I3-4478', 4, 500, 1, 1, 0, NULL, 0, 1),
(22, 100, 'dfgh', 'dfgh', 'dfgh', 4, 4, 0, 0, 0, NULL, 0, 0),
(23, 104, 'h', 'h', 'h', 5, 5, 0, 0, 0, NULL, 0, 0),
(24, 105, 'aasd', 'asda', 'aasd', 2, 2, 0, 1, 0, NULL, 0, 0);

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
  `sist_op` varchar(100) DEFAULT NULL
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
(35, 97, 'sdf', 3, 3, 'sdf2', 'sdf'),
(37, 98, 'qwer', 3, 3, 'qwer', 'qwer'),
(38, 16, 'sfaddfg', 3, 3, 'sdf', 'Win 11'),
(39, 90, 'I9 ultra core', 3, 3, 'sdf', 'Ubuntu 22'),
(40, 101, '3a', 2, 2, 'asdf2', 'asf');

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
(57, '0044_2025', 51, 2, 'ruy', 'rgerg', '2025-10-24', 'Anulado', NULL, NULL, 'Error de carga', '2025-10-27 08:52:47'),
(58, '0045_2025', 27, 5, 'asdasda', 'sdfadf', '2025-10-24', 'Devuelta', '2025-10-24', NULL, NULL, NULL),
(60, '0047_2025', 51, 9, 'diego', 'garcia', '2025-10-24', 'Anulado', NULL, NULL, 'asda', '2025-11-25 13:01:11'),
(61, '0048_2025', 38, 2, 'joaquin', 'villaverde', '2025-10-24', 'Devuelta', '2025-10-24', NULL, NULL, NULL),
(62, '0001_2025', 44, 6, 'cintia', 'cuassolo', '2025-10-24', 'Anulado', NULL, NULL, 'Cambio de area', '2025-10-28 08:21:30'),
(64, '0050_2025', 38, 2, 'Probando', 'borrar', '2025-10-31', 'Anulado', NULL, NULL, 'Renuncio', '2025-10-31 11:47:33'),
(65, '0051_2025', 51, 11, 'qwer', 'qwer', '2025-11-04', 'Anulado', NULL, NULL, 'adsa', '2025-11-20 09:12:56'),
(68, 'HIST_20251125094013_435', 38, 2, 'probando hist', 'hist', '2025-11-25', 'Anulado', NULL, NULL, 'asdasd', '2025-11-25 13:01:07'),
(69, '0053_2025', 51, 5, 'apskgdj', 'asdf', '2025-11-25', 'Activa', NULL, NULL, NULL, NULL);

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
(104, 57, 86, 1, 0),
(105, 58, 88, 1, 1),
(107, 60, 90, 1, 0),
(108, 61, 75, 1, 1),
(111, 62, 61, 1, 0),
(112, 62, 75, 1, 0),
(115, 64, 75, 1, 0),
(116, 64, 95, 1, 0),
(117, 65, 98, 1, 0),
(119, 68, 105, 1, 0),
(120, 69, 23, 1, 0),
(121, 69, 102, 3, 0),
(122, 69, 95, 1, 0),
(124, 69, 100, 1, 0);

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
(2025, 53);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id_rol` int(11) NOT NULL,
  `nombre_rol` varchar(50) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `permisos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Permisos del rol en formato JSON' CHECK (json_valid(`permisos`)),
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Roles de usuario con permisos';

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id_rol`, `nombre_rol`, `descripcion`, `permisos`, `fecha_creacion`) VALUES
(1, 'Super Administrador', 'Acceso total al sistema incluyendo gestión de usuarios', '{\"insumos\": [\"ver\", \"crear\", \"editar\", \"eliminar\", \"baja\"], \"asignaciones\": [\"ver\", \"crear\", \"editar\", \"anular\", \"devolver\"], \"reportes\": [\"ver\", \"exportar\"], \"usuarios\": [\"ver\", \"crear\", \"editar\", \"eliminar\", \"cambiar_rol\"], \"auditoria\": [\"ver_todo\"], \"telecom\": [\"ver\", \"editar\"], \"sedes\": [\"ver\", \"crear\", \"editar\"], \"areas\": [\"ver\", \"crear\", \"editar\"]}', '2025-11-06 09:08:29'),
(2, 'Administrador', 'Gestión completa de inventario sin acceso a usuarios', '{\"insumos\": [\"ver\", \"crear\", \"editar\", \"eliminar\", \"baja\"], \"asignaciones\": [\"ver\", \"crear\", \"editar\", \"anular\", \"devolver\"], \"reportes\": [\"ver\", \"exportar\"], \"telecom\": [\"ver\", \"editar\"], \"sedes\": [\"ver\"], \"areas\": [\"ver\"]}', '2025-11-06 09:08:29'),
(3, 'Operador', 'Operaciones diarias de inventario y asignaciones', '{\"insumos\": [\"ver\", \"crear\", \"editar\", \"baja\"], \"asignaciones\": [\"ver\", \"crear\", \"devolver\"], \"reportes\": [\"ver\"], \"telecom\": [\"ver\"], \"sedes\": [\"ver\"], \"areas\": [\"ver\"]}', '2025-11-06 09:08:29'),
(4, 'Consultor', 'Solo lectura y generación de reportes', '{\"insumos\": [\"ver\"], \"asignaciones\": [\"ver\"], \"reportes\": [\"ver\", \"exportar\"], \"telecom\": [\"ver\"], \"sedes\": [\"ver\"], \"areas\": [\"ver\"]}', '2025-11-06 09:08:29');

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
(14, 50, 'Fibertel', 'Fibra óptica', 100, 1, 1, 'Baja por Traslado', 'Autorización superior', '2025-10-30', 'public/uploads/autorizaciones_internet/traslado_20251030_093632_69035bd0e3c29.pdf', NULL, '2025-10-30', NULL, '2025-10-30', NULL, 15, 13);

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
-- Estructura de tabla para la tabla `sesiones`
--

CREATE TABLE `sesiones` (
  `id_sesion` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `token_sesion` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `fecha_inicio` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_ultimo_acceso` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `fecha_cierre` datetime DEFAULT NULL,
  `activa` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Sesiones activas de usuarios';

--
-- Volcado de datos para la tabla `sesiones`
--

INSERT INTO `sesiones` (`id_sesion`, `id_usuario`, `token_sesion`, `ip_address`, `user_agent`, `fecha_inicio`, `fecha_ultimo_acceso`, `fecha_cierre`, `activa`) VALUES
(1, 1, '03cf9582a616d23aedf8d287a43e7b32996114deed6ede3ddd4ab41ad3125d7f', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-11-06 09:31:10', '2025-11-06 13:57:04', '2025-11-06 13:57:04', 0),
(2, 1, '2fdcb72f43afb6ce3f0c6489d47dd645c9b404e0f49ea724a0851a9093555bdf', '10.114.85.131', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-06 09:37:41', '2025-11-06 13:58:43', '2025-11-06 13:58:43', 0),
(3, 1, '3aae0ce244e6496f37de49037eb36169040219affa0c1dc82a577fd36220db11', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-11-06 13:58:43', '2025-11-07 10:08:41', '2025-11-07 10:08:41', 0),
(4, 1, '1371a62b5b79838f4f630a5d6880ead53d0d97d4695640fdf933226cc3102def', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-11-07 10:08:41', '2025-11-07 10:09:51', '2025-11-07 10:09:51', 0),
(5, 1, '3450fe9bc17a6064d47d50ddb54447a09e6e783ec9115bf9a55c501d3ef77545', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-11-07 10:16:22', '2025-11-07 10:16:40', '2025-11-07 10:16:40', 0),
(6, 1, 'b32acc1404d2e587ce29061607648414fc968d3ab8684c92f818d778ab7298cb', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-11-07 10:16:40', '2025-11-07 13:14:57', '2025-11-07 13:14:57', 0),
(7, 1, '73d1202b2a8c2e6604dfbf2290ac1642bcbee03e77eb82815f4636346d33918c', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-11-07 13:52:08', '2025-11-17 12:04:04', '2025-11-17 12:04:04', 0),
(8, 1, '74140357f8be4bd98195df0e19c071cfc5f1b14ba954c512410728f04e249360', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-11-17 12:04:04', '2025-11-17 13:11:52', '2025-11-17 13:11:52', 0),
(9, 1, '23f505a870077dd321107884194bd9bc5ba0efcc9b369248ad990d01565efbf8', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-11-17 13:11:58', '2025-11-17 13:18:28', '2025-11-17 13:18:28', 0),
(10, 1, 'c401bf7b76fcd4f09b393647fa6723528cf2fa19ee3113021e7a8c549500e4cc', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-11-17 13:18:33', '2025-11-18 08:11:10', '2025-11-18 08:11:10', 0),
(11, 1, '3af16d9c22ab0adcab31762fbc876f7dda916c5308eb064f385c0cb95f90863e', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-11-18 08:11:10', '2025-11-18 08:12:29', '2025-11-18 08:12:29', 0),
(12, 1, '5baa52fc6259ac92fd674fe06d56a62317cc0bf972846716767067e572b93ca9', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-11-18 08:12:34', '2025-11-18 10:13:15', '2025-11-18 10:13:15', 0),
(13, 1, 'aff9a75ff43b3339c59d3af57b346ea843f8288d5d7378849d54b2b708313feb', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-11-18 08:51:56', '2025-11-18 10:13:19', '2025-11-18 10:13:19', 0),
(14, 1, '366f52a2732c49e05044cc62ad08fd85c0c1d56f6ead059b90d1af2d86776885', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-11-18 10:13:19', '2025-11-18 12:18:22', '2025-11-18 12:18:22', 0),
(15, 1, '3af884964e24e99bd1f86f7a6406eb32739f90a470e58fef177d5428297ac178', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-11-18 12:18:32', '2025-11-18 12:35:33', '2025-11-18 12:35:16', 0),
(16, 1, 'b7b7c39d8ccbb0806990d676d989f8c322129db618fdca1b0b8f559f85436764', '10.114.85.131', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-18 12:35:16', '2025-11-19 08:07:53', '2025-11-19 08:07:53', 0),
(17, 1, '1259871e41b672257918441e919deb31e4ff0db9664cf46dc165e9d8da421eed', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-11-19 08:07:53', '2025-11-20 08:25:51', '2025-11-20 08:25:51', 0),
(18, 1, 'fb7bd4c57df5d27cea80c99d8d286aae22e29080db56cdcb8dea7f2fcdbd8adb', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-11-20 08:25:51', '2025-11-20 13:14:41', '2025-11-20 13:14:41', 0),
(19, 1, 'd3abb534d2d8602939ff609c08f0ecf74ffdc3550c7cfa8f4a19a0f9f16db421', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-11-20 09:32:40', '2025-11-20 09:54:27', '2025-11-20 09:54:27', 0),
(20, 1, '66eff8df74c4c777fb958d04b7a4a464b98f97cb6d30af548b84394d4144d28a', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-11-20 09:54:27', '2025-11-20 13:14:45', '2025-11-20 13:14:45', 0),
(21, 1, 'ae47344ea6d3e567114177fefe88c9b4595a0b269569a69eba6c1e7962539888', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-11-20 13:14:46', '2025-11-25 08:55:15', '2025-11-25 08:55:15', 0),
(22, 1, '3937b4e4bfd5889918c12abf129753d955fa7a4d9557e12e5a5aedc0b820cb6e', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-11-25 08:55:15', '2025-11-25 09:35:54', '2025-11-25 09:35:54', 0),
(23, 1, 'e4263d159f6bfe04ffe1d5c148cee3ce7f718845935240a5b993c23dfda9e377', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-11-25 09:39:24', '2025-11-25 10:16:08', '2025-11-25 10:16:08', 0),
(24, 1, 'ac77273b5674da105fffd439d2181c36c249260feb804cfa6bcf7a9728cbfde9', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-11-25 10:10:12', '2025-11-25 10:16:13', '2025-11-25 10:16:13', 0),
(25, 1, '0d286c90637e6363c30090bdca6bd58c2abfb4f7b1a8b43e6eb9c5ebcd8f9ed2', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-11-25 10:16:13', '2025-11-25 10:46:25', '2025-11-25 10:46:25', 0),
(26, 1, '9b4f120e6707ad53d387bd75f6b6c93ba3c46548c01b925d9a232fbcac3e7a23', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-11-25 10:46:30', '2025-11-25 12:58:42', '2025-11-25 12:58:42', 0),
(27, 1, '32284d427bc8cb49aa0291c576b7b896e8cac1adfa5d265735d52da3426016d7', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-11-25 13:00:43', '2025-11-25 13:52:19', '2025-11-25 13:21:46', 0),
(28, 1, '2cb4b70f97db011192fea6898bb405fb0fae1cfa15bb3f3780c37af75753c4ab', '10.114.85.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:134.0) Gecko/20100101 Firefox/134.0', '2025-11-25 13:21:46', '2025-11-26 08:11:03', '2025-11-26 08:11:03', 0),
(29, 1, '4cf6eb1522b19fdcb11d49317a06de78dda9e7d40affc6279f51e672f1f45328', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-11-26 08:11:03', '2025-11-26 08:55:16', '2025-11-26 08:55:16', 0),
(30, 7, '628f20684e5a31db77290c5ce7584a348eb6fbe47db734156ad83c906f82d7fe', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-11-26 08:29:39', '2025-11-26 08:29:46', '2025-11-26 08:29:46', 0),
(31, 6, '9e524a1e2f9cdf96984c1808dddaf62c8605396b13182f7f6c4bf037de4d01f8', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-11-26 08:29:55', '2025-11-26 08:30:15', '2025-11-26 08:30:15', 0),
(32, 5, '34639d235315fba5f646cb41392802a8426036b324ce912749e7931975fa777a', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-11-26 08:30:26', '2025-11-26 09:26:03', '2025-11-26 09:26:03', 0),
(33, 1, '94f4a497750d95b8706942e464cc1156abf6af85390e1afc891ad13749983cd0', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-11-26 08:55:21', '2025-11-26 10:49:49', '2025-11-26 10:49:49', 0),
(34, 1, 'f5f9bb0de05196de236bda87214d85dba5471a27fbbd91466583b3429451be26', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-11-26 11:46:12', '2025-12-01 08:51:11', '2025-12-01 08:51:11', 0),
(35, 1, 'bc459e4274cd99563682f5c7eb5eaee7e24ef1295a13d46b7f6460f7e0d4f1d5', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-12-01 08:51:11', '2025-12-01 08:51:11', NULL, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `id_rol` int(11) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `ultimo_acceso` datetime DEFAULT NULL,
  `modificado_por` int(11) DEFAULT NULL COMMENT 'ID del usuario que realizó la última modificación',
  `fecha_modificacion` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Usuarios del sistema';

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id_usuario`, `username`, `email`, `password_hash`, `nombre`, `apellido`, `id_rol`, `activo`, `fecha_creacion`, `ultimo_acceso`, `modificado_por`, `fecha_modificacion`) VALUES
(1, 'admin', 'admin@inventario.local', '$2y$10$acRUJEKWeTLjLv5WF6M1sOa1hjCAWdFJQbqeQDytS2aLvVbFypMvS', 'Administrador', 'Sistema', 1, 1, '2025-11-06 09:08:29', '2025-12-01 08:51:11', NULL, '2025-12-01 08:51:11'),
(2, 'heber', 'hgonzalez@senaf.rionegro.gov.ar', '$2y$10$UKTMSH5hbXcW5Ablzs2zyepme0nAXl4r4n4UA/sNtEpQNUrxzcDCC', 'heber', 'gonzalez', 1, 1, '2025-11-18 08:13:13', NULL, 1, '2025-11-18 12:39:35'),
(3, 'rjasd', 'rj45@gmail.com', '$2y$10$L6QTWQy/5QbtHj6NJnz0POgTzu.gv1L2Oh5kHC6DhYA16bGbM0SQ.', 'aasd', 'asdf', 2, 0, '2025-11-18 08:15:25', NULL, 1, '2025-11-25 08:57:11'),
(4, 'adminis', 'adminis@gmail.com', '$2y$10$bcT2K9QDJV296xo1keUZpus1MjVsBYAmsonWtOpjGN3HROkWA6QkK', 'adminis', 'trador', 2, 1, '2025-11-26 08:12:08', NULL, 1, NULL),
(5, 'opera', 'opera@gmail.com', '$2y$10$NQ4a6.SQ.QMfyjmC12GAFOaI5eOYgIskLNNS9wxtKf.Azt9WximzG', 'opera', 'dor', 3, 1, '2025-11-26 08:13:30', '2025-11-26 08:30:26', 1, '2025-11-26 08:30:26'),
(6, 'consul', 'consul@gmail.com', '$2y$10$C2RD/LvXMVfcGDS6JwlHNuhdRZioqDtC30s/QCf2A8gMfg811fkhG', 'consul', 'tor', 4, 1, '2025-11-26 08:14:20', '2025-11-26 08:29:55', 1, '2025-11-26 08:29:55'),
(7, 'super', 'super@gmail.com', '$2y$10$XIyT/A16VSuPbi/8LPkf/eeUnZZ8ANuGaYhhkYevKHkjyBKaRdEpO', 'super', 'admin', 1, 1, '2025-11-26 08:15:30', '2025-11-26 08:29:39', 1, '2025-11-26 08:29:39');

--
-- Disparadores `usuarios`
--
DELIMITER $$
CREATE TRIGGER `trg_usuarios_before_update` BEFORE UPDATE ON `usuarios` FOR EACH ROW BEGIN
    SET NEW.fecha_modificacion = NOW();
END
$$
DELIMITER ;

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
-- Indices de la tabla `auditoria_acciones`
--
ALTER TABLE `auditoria_acciones`
  ADD PRIMARY KEY (`id_auditoria`),
  ADD KEY `idx_usuario` (`id_usuario`),
  ADD KEY `idx_sesion` (`id_sesion`),
  ADD KEY `idx_accion` (`accion`),
  ADD KEY `idx_modulo` (`modulo`),
  ADD KEY `idx_fecha` (`fecha_accion`),
  ADD KEY `idx_entidad` (`entidad_tipo`,`entidad_id`),
  ADD KEY `idx_resultado` (`resultado`),
  ADD KEY `idx_usuario_fecha` (`id_usuario`,`fecha_accion`),
  ADD KEY `idx_modulo_accion` (`modulo`,`accion`);

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
  ADD KEY `idx_insumos_licitacion` (`id_ingreso`),
  ADD KEY `idx_cantidad_oficina` (`cantidad_oficina`);

--
-- Indices de la tabla `insumos_bajas`
--
ALTER TABLE `insumos_bajas`
  ADD PRIMARY KEY (`id_baja`),
  ADD KEY `idx_ib_insumo` (`id_insumo`);

--
-- Indices de la tabla `insumos_movimientos_stock`
--
ALTER TABLE `insumos_movimientos_stock`
  ADD PRIMARY KEY (`id_movimiento`),
  ADD KEY `idx_movimientos_insumo` (`id_insumo`),
  ADD KEY `idx_movimientos_fecha` (`fecha_movimiento`),
  ADD KEY `idx_movimientos_tipo` (`tipo_movimiento`);

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
  ADD KEY `idx_remitos_fecha` (`fecha_asignacion`),
  ADD KEY `idx_remitos_estado` (`estado`),
  ADD KEY `fk_remitos_sede` (`id_sede`),
  ADD KEY `fk_remitos_area` (`id_area`);

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
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id_rol`),
  ADD UNIQUE KEY `uk_nombre_rol` (`nombre_rol`);

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
-- Indices de la tabla `sesiones`
--
ALTER TABLE `sesiones`
  ADD PRIMARY KEY (`id_sesion`),
  ADD UNIQUE KEY `uk_token_sesion` (`token_sesion`),
  ADD KEY `idx_token` (`token_sesion`),
  ADD KEY `idx_usuario` (`id_usuario`),
  ADD KEY `idx_activa` (`activa`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `uk_username` (`username`),
  ADD UNIQUE KEY `uk_email` (`email`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_activo` (`activo`),
  ADD KEY `fk_usuarios_rol` (`id_rol`);

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
-- AUTO_INCREMENT de la tabla `auditoria_acciones`
--
ALTER TABLE `auditoria_acciones`
  MODIFY `id_auditoria` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=115;

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
  MODIFY `id_ingreso` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT de la tabla `insumos`
--
ALTER TABLE `insumos`
  MODIFY `id_insumo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=106;

--
-- AUTO_INCREMENT de la tabla `insumos_bajas`
--
ALTER TABLE `insumos_bajas`
  MODIFY `id_baja` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de la tabla `insumos_movimientos_stock`
--
ALTER TABLE `insumos_movimientos_stock`
  MODIFY `id_movimiento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

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
  MODIFY `id_notebook` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT de la tabla `pcs_completas`
--
ALTER TABLE `pcs_completas`
  MODIFY `id_pc_completa` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT de la tabla `puntos_stock`
--
ALTER TABLE `puntos_stock`
  MODIFY `id_punto_stock` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `remitos`
--
ALTER TABLE `remitos`
  MODIFY `id_remito` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=70;

--
-- AUTO_INCREMENT de la tabla `remitos_detalle`
--
ALTER TABLE `remitos_detalle`
  MODIFY `id_detalle` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=125;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id_rol` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

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
-- AUTO_INCREMENT de la tabla `sesiones`
--
ALTER TABLE `sesiones`
  MODIFY `id_sesion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `zonas`
--
ALTER TABLE `zonas`
  MODIFY `id_zona` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `auditoria_acciones`
--
ALTER TABLE `auditoria_acciones`
  ADD CONSTRAINT `fk_auditoria_sesion` FOREIGN KEY (`id_sesion`) REFERENCES `sesiones` (`id_sesion`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_auditoria_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE;

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
  ADD CONSTRAINT `fk_insumos_ingreso` FOREIGN KEY (`id_ingreso`) REFERENCES `ingresos` (`id_ingreso`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `insumos_bajas`
--
ALTER TABLE `insumos_bajas`
  ADD CONSTRAINT `fk_ib_insumo` FOREIGN KEY (`id_insumo`) REFERENCES `insumos` (`id_insumo`);

--
-- Filtros para la tabla `insumos_movimientos_stock`
--
ALTER TABLE `insumos_movimientos_stock`
  ADD CONSTRAINT `fk_movimientos_insumo` FOREIGN KEY (`id_insumo`) REFERENCES `insumos` (`id_insumo`) ON DELETE CASCADE ON UPDATE CASCADE;

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
  ADD CONSTRAINT `fk_r_sede` FOREIGN KEY (`id_sede`) REFERENCES `sedes` (`id_sede`);

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

--
-- Filtros para la tabla `sesiones`
--
ALTER TABLE `sesiones`
  ADD CONSTRAINT `fk_sesiones_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_usuarios_rol` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
