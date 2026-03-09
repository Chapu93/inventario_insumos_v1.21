-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost
-- Tiempo de generación: 14-01-2026 a las 16:19:57
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
(106, 1, 29, 'sesion_expirada', 'usuarios', 'Sesión expirada por inactividad', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-11-26 08:55:16'),
(107, 1, 29, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-11-26 08:55:16'),
(108, 1, 33, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-11-26 08:55:21'),
(111, 1, 33, 'sesion_expirada', 'usuarios', 'Sesión expirada por inactividad', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-11-26 10:49:49'),
(112, 1, 33, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-11-26 10:49:49'),
(113, 1, 34, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-11-26 11:46:12'),
(114, 1, 35, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-01 08:51:11'),
(115, 1, 35, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-01 09:52:46'),
(116, 1, 36, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-01 09:52:58'),
(117, 1, 36, 'crear_usuario', 'usuarios', 'Usuario creado: juan (juan juan)', 'usuario', 8, NULL, '{\"username\":\"juan\",\"email\":\"juan@gmail.com\",\"nombre\":\"juan\",\"apellido\":\"juan\",\"id_rol\":4}', '127.0.0.1', 'exito', NULL, '2025-12-01 10:02:09'),
(118, 1, 36, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-01 10:02:13'),
(121, 1, 38, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-01 10:43:49'),
(122, 1, 38, 'sesion_expirada', 'usuarios', 'Sesión expirada por inactividad', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-01 11:17:01'),
(123, 1, 38, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-01 11:17:01'),
(124, 1, 39, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-01 11:17:06'),
(125, 1, 39, 'sesion_expirada', 'usuarios', 'Sesión expirada por inactividad', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-01 14:03:29'),
(126, 1, 39, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-01 14:03:30'),
(127, 1, 40, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-01 14:03:37'),
(128, 1, 41, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-02 08:46:30'),
(129, 1, 41, 'crear_insumo', 'insumos', 'Insumo creado:  (Tipo: Varios)', 'insumo', 106, NULL, '{\"nombre_insumo\":null,\"tipo_insumo\":\"Varios\",\"cantidad\":6,\"estado\":\"Disponible\"}', '127.0.0.1', 'exito', NULL, '2025-12-02 08:48:26'),
(130, 1, 41, 'editar_insumo', 'insumos', 'Insumo editado: Probando 33 (ID: 83)', 'insumo', 83, '{\"id_insumo\":83,\"nombre_insumo\":\"Probando 33\",\"tipo_insumo\":\"PC Escritorio\",\"subcategoria_varios\":null,\"descripcion_general\":null,\"numero_serie\":\"asdfkj\",\"id_fisico\":\"asldfk\",\"id_patrimonio\":\"asld\",\"cantidad\":1,\"cantidad_oficina\":null,\"cantidad_deposito\":null,\"fecha_adquisicion\":\"2025-10-30\",\"estado\":\"Disponible\",\"id_punto_stock_actual\":2,\"id_sede_actual\":null,\"id_ingreso\":31,\"es_nuevo\":1,\"id_area_asignacion_actual\":null,\"id_patrimonio_idx\":\"asld\"}', '{\"nombre_insumo\":\"Probando 33\",\"tipo_insumo\":\"PC Escritorio\",\"cantidad\":1}', '127.0.0.1', 'exito', NULL, '2025-12-02 08:49:03'),
(131, 1, 41, 'crear_asignacion', 'asignaciones', 'Asignación creada - Remito: 0054_2025 - Persona: roberto kaka', 'asignacion', 70, NULL, '{\"numero_remito\":\"0054_2025\",\"sede\":38,\"area\":2,\"persona\":\"roberto kaka\",\"insumos_count\":2}', '127.0.0.1', 'exito', NULL, '2025-12-02 08:49:59'),
(132, 1, 41, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-02 08:56:37'),
(137, 1, 43, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-02 09:10:03'),
(138, 1, 43, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-02 09:30:49'),
(139, 1, 44, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-02 09:30:55'),
(140, 1, 44, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-02 09:31:18'),
(147, 1, 48, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-02 09:45:39'),
(148, 1, 48, 'editar_permisos_usuario', 'usuarios', 'Permisos personalizados actualizados para usuario: adminis', 'usuario', 4, '{\"permisos_anteriores\":{\"insumos\":[\"ver\",\"crear\",\"editar\",\"eliminar\",\"baja\"],\"asignaciones\":[\"ver\",\"crear\",\"editar\",\"anular\",\"devolver\"],\"reportes\":[\"ver\",\"exportar\"],\"telecom\":[\"ver\",\"editar\"],\"sedes\":[\"ver\"],\"areas\":[\"ver\"]}}', '{\"permisos_nuevos\":{\"insumos\":[\"ver\",\"crear\",\"editar\",\"eliminar\",\"baja\"],\"asignaciones\":[\"ver\",\"crear\",\"editar\",\"anular\",\"devolver\"],\"reportes\":[\"ver\",\"exportar\"],\"telecom\":[\"ver\",\"editar\",\"eliminar\"],\"sedes\":[\"ver\",\"crear\",\"editar\",\"eliminar\"],\"areas\":[\"ver\",\"crear\",\"editar\",\"eliminar\"]}}', '127.0.0.1', 'exito', NULL, '2025-12-02 09:46:04'),
(149, 1, 48, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-02 09:46:07'),
(152, 1, 50, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-02 09:49:59'),
(153, 1, 50, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-02 09:57:26'),
(154, 1, 51, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-02 09:57:32'),
(155, 1, 51, 'crear_usuario', 'usuarios', 'Usuario creado: consu (consu ltor)', 'usuario', 9, NULL, '{\"username\":\"consu\",\"email\":\"ltor@gmail.com\",\"nombre\":\"consu\",\"apellido\":\"ltor\",\"id_rol\":4}', '127.0.0.1', 'exito', NULL, '2025-12-02 09:58:39'),
(156, 1, 51, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-02 09:58:44'),
(159, 1, 53, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-02 10:00:54'),
(160, 1, 53, 'editar_permisos_usuario', 'usuarios', 'Permisos personalizados actualizados para usuario: consu', 'usuario', 9, '{\"permisos_anteriores\":{\"insumos\":[\"ver\"],\"asignaciones\":[\"ver\"],\"reportes\":[\"ver\",\"exportar\"],\"telecom\":[\"ver\"],\"sedes\":[\"ver\"],\"areas\":[\"ver\"]}}', '{\"permisos_nuevos\":{\"insumos\":[\"ver\",\"crear\",\"editar\",\"eliminar\",\"baja\"],\"asignaciones\":[\"ver\",\"crear\",\"editar\",\"anular\",\"devolver\"],\"reportes\":[\"ver\",\"exportar\"],\"usuarios\":[\"ver\",\"crear\",\"editar\",\"eliminar\",\"cambiar_rol\"],\"auditoria\":[\"ver_todo\"],\"telecom\":[\"ver\",\"editar\",\"eliminar\"],\"sedes\":[\"ver\",\"crear\",\"editar\",\"eliminar\"],\"areas\":[\"ver\",\"crear\",\"editar\",\"eliminar\"]}}', '127.0.0.1', 'exito', NULL, '2025-12-02 10:01:09'),
(161, 1, 53, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-02 10:01:13'),
(166, 1, 55, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-02 10:10:52'),
(167, 1, 55, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-02 10:10:57'),
(168, 1, 56, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-02 10:11:05'),
(169, 1, 56, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-02 10:11:13'),
(183, 1, 62, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-03 08:43:26'),
(184, 1, 62, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-03 08:44:17'),
(187, 1, 63, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-03 08:44:46'),
(188, 1, 63, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-03 08:44:57'),
(194, 1, 66, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '::1', 'exito', NULL, '2025-12-03 10:41:40'),
(195, 1, 66, 'crear_insumo', 'insumos', 'Insumo creado:  (Tipo: Varios)', 'insumo', 109, NULL, '{\"nombre_insumo\":null,\"tipo_insumo\":\"Varios\",\"cantidad\":105,\"estado\":\"Disponible\"}', '::1', 'exito', NULL, '2025-12-03 10:44:42'),
(196, 1, 66, 'crear_insumo', 'insumos', 'Insumo creado:  (Tipo: Monitor)', 'insumo', 110, NULL, '{\"nombre_insumo\":null,\"tipo_insumo\":\"Monitor\",\"cantidad\":\"1\",\"estado\":\"Disponible\"}', '::1', 'exito', NULL, '2025-12-03 10:50:00'),
(199, 1, 67, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-03 11:02:53'),
(200, 1, 67, 'crear_insumo', 'insumos', 'Insumo creado:  (Tipo: Notebook)', 'insumo', 111, NULL, '{\"nombre_insumo\":null,\"tipo_insumo\":\"Notebook\",\"cantidad\":\"1\",\"estado\":\"Disponible\"}', '127.0.0.1', 'exito', NULL, '2025-12-03 11:03:30'),
(201, 1, 67, 'sesion_expirada', 'usuarios', 'Sesión expirada por inactividad', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-03 12:30:16'),
(202, 1, 67, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-03 12:30:16'),
(203, 1, 68, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-03 13:40:55'),
(204, 1, 69, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-04 09:44:03'),
(205, 1, 69, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-04 09:44:24'),
(206, 1, 70, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-04 09:44:30'),
(207, 1, 70, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-04 09:51:10'),
(210, 1, 72, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-04 10:08:33'),
(211, 1, 72, 'activar_usuario', 'usuarios', 'Usuario activar: heber', 'usuario', 2, '{\"activo\":0}', '{\"activo\":1}', '127.0.0.1', 'exito', NULL, '2025-12-04 10:10:45'),
(212, 1, 72, 'backup', 'sistema', 'Backup de base de datos generado', 'sistema', 0, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-04 10:15:20'),
(213, 1, 72, 'sesion_expirada', 'usuarios', 'Sesión expirada por inactividad', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-04 13:44:28'),
(214, 1, 72, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-04 13:44:28'),
(215, 1, 73, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-04 13:44:35'),
(216, 1, 74, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-05 10:03:36'),
(217, 1, 74, 'sesion_expirada', 'usuarios', 'Sesión expirada por inactividad', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-05 12:09:02'),
(218, 1, 74, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-05 12:09:02'),
(219, 1, 75, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-05 12:09:06'),
(220, 1, 75, 'sesion_expirada', 'usuarios', 'Sesión expirada por inactividad', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-05 13:01:34'),
(221, 1, 75, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-05 13:01:34'),
(222, 1, 76, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-05 13:01:40'),
(223, 1, 77, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-05 13:24:52'),
(224, 1, 78, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-09 08:56:52'),
(225, 1, 78, 'crear_usuario', 'usuarios', 'Usuario creado: vane (vane vane)', 'usuario', 10, NULL, '{\"username\":\"vane\",\"email\":\"vane@senar.com\",\"nombre\":\"vane\",\"apellido\":\"vane\",\"id_rol\":2}', '127.0.0.1', 'exito', NULL, '2025-12-09 09:50:25'),
(226, 1, 78, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-09 09:50:28'),
(229, 1, 80, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-09 10:02:15'),
(230, 1, 80, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-09 10:16:55'),
(233, 1, 82, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-09 10:17:24'),
(234, 1, 82, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-09 10:18:26'),
(235, 1, 83, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-09 10:18:30'),
(236, 1, 84, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-09 10:30:31'),
(237, 1, 84, 'reset_password', 'usuarios', 'Contraseña restablecida para usuario: super', 'usuario', 7, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-09 10:32:11'),
(238, 1, 84, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-09 10:32:17'),
(243, 1, 87, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '::1', 'exito', NULL, '2025-12-09 10:40:28'),
(244, 1, 83, 'sesion_expirada', 'usuarios', 'Sesión expirada por inactividad', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-09 11:19:40'),
(245, 1, 83, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-09 11:19:40'),
(246, 1, 88, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-09 11:19:48'),
(247, 1, 87, 'sesion_expirada', 'usuarios', 'Sesión expirada por inactividad', NULL, NULL, NULL, NULL, '::1', 'exito', NULL, '2025-12-09 11:57:07'),
(248, 1, 87, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '::1', 'exito', NULL, '2025-12-09 11:57:07'),
(249, 1, 89, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-09 12:01:42'),
(250, 1, 89, 'crear_usuario', 'usuarios', 'Usuario creado: jjj (JJ JJ)', 'usuario', 12, NULL, '{\"username\":\"jjj\",\"email\":\"jj@ASD.CONM\",\"nombre\":\"JJ\",\"apellido\":\"JJ\",\"id_rol\":1}', '127.0.0.1', 'exito', NULL, '2025-12-09 12:02:58'),
(251, 1, 89, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-09 12:03:02'),
(253, 1, 91, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '::1', 'exito', NULL, '2025-12-09 12:07:29'),
(254, 1, 91, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '::1', 'exito', NULL, '2025-12-09 12:13:51'),
(255, 1, 92, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '::1', 'exito', NULL, '2025-12-09 12:13:57'),
(256, 1, 93, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '::1', 'exito', NULL, '2025-12-09 12:19:32'),
(257, 1, 93, 'crear_usuario', 'usuarios', 'Usuario creado: adad (adad adad)', 'usuario', 13, NULL, '{\"username\":\"adad\",\"email\":\"adad@klac.com\",\"nombre\":\"adad\",\"apellido\":\"adad\",\"id_rol\":2}', '::1', 'exito', NULL, '2025-12-09 12:21:04'),
(258, 1, 93, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '::1', 'exito', NULL, '2025-12-09 12:21:10'),
(260, 1, 95, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '::1', 'exito', NULL, '2025-12-09 12:25:29'),
(261, 1, 96, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '::1', 'exito', NULL, '2025-12-09 12:34:32'),
(262, 1, 96, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '::1', 'exito', NULL, '2025-12-09 12:42:46'),
(266, 1, 98, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '::1', 'exito', NULL, '2025-12-09 13:09:48'),
(267, 1, 99, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-10 09:37:25'),
(268, 1, 99, 'crear_usuario', 'usuarios', 'Usuario creado: jvillaverde (joaquin villaverde)', 'usuario', 14, NULL, '{\"username\":\"jvillaverde\",\"email\":\"jvillaverde@senaf.rionegro.gov.ar\",\"nombre\":\"joaquin\",\"apellido\":\"villaverde\",\"id_rol\":3}', '127.0.0.1', 'exito', NULL, '2025-12-10 09:37:59'),
(269, 1, 99, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-10 09:38:02'),
(272, 1, 101, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-10 10:21:36'),
(273, 1, 101, 'sesion_expirada', 'usuarios', 'Sesión expirada por inactividad', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-10 11:48:43'),
(274, 1, 101, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-10 11:48:44'),
(284, 1, 108, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-11 12:45:45'),
(285, 1, 108, 'crear_asignacion', 'asignaciones', 'Asignación creada - Remito: 0055_2025 - Persona: ñkjlk fsghsg', 'asignacion', 76, NULL, '{\"numero_remito\":\"0055_2025\",\"sede\":51,\"area\":5,\"persona\":\"ñkjlk fsghsg\",\"insumos_count\":3}', '127.0.0.1', 'exito', NULL, '2025-12-11 12:53:33'),
(286, 1, 109, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-12 08:21:43'),
(287, 1, 109, 'crear_usuario', 'usuarios', 'Usuario creado: adminis (admi nitador)', 'usuario', 15, NULL, '{\"username\":\"adminis\",\"email\":\"adminis@gmail.com\",\"nombre\":\"admi\",\"apellido\":\"nitador\",\"id_rol\":2}', '127.0.0.1', 'exito', NULL, '2025-12-12 08:23:50'),
(288, 1, 109, 'crear_usuario', 'usuarios', 'Usuario creado: consul (consul tor)', 'usuario', 16, NULL, '{\"username\":\"consul\",\"email\":\"consul@gmail.com\",\"nombre\":\"consul\",\"apellido\":\"tor\",\"id_rol\":4}', '127.0.0.1', 'exito', NULL, '2025-12-12 08:25:03'),
(289, 1, 109, 'crear_usuario', 'usuarios', 'Usuario creado: opera (opera opera)', 'usuario', 17, NULL, '{\"username\":\"opera\",\"email\":\"opera@gmail.com\",\"nombre\":\"opera\",\"apellido\":\"opera\",\"id_rol\":3}', '127.0.0.1', 'exito', NULL, '2025-12-12 08:25:32'),
(290, 1, 109, 'crear_usuario', 'usuarios', 'Usuario creado: super (super visor)', 'usuario', 18, NULL, '{\"username\":\"super\",\"email\":\"super@gmail.com\",\"nombre\":\"super\",\"apellido\":\"visor\",\"id_rol\":1}', '127.0.0.1', 'exito', NULL, '2025-12-12 08:25:58'),
(291, 1, 109, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-12 08:26:05'),
(292, 16, 110, 'login', 'usuarios', 'Login exitoso: consul', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-12 08:26:13'),
(293, 16, 110, 'crear_asignacion', 'asignaciones', 'Asignación creada - Remito: 0056_2025 - Persona: dgd dfgd', 'asignacion', 77, NULL, '{\"numero_remito\":\"0056_2025\",\"sede\":27,\"area\":11,\"persona\":\"dgd dfgd\",\"insumos_count\":2}', '127.0.0.1', 'exito', NULL, '2025-12-12 08:26:49'),
(294, 16, 110, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-12 08:27:34'),
(295, 17, 111, 'login', 'usuarios', 'Login exitoso: opera', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-12 08:27:41'),
(296, 17, 111, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-12 08:29:03'),
(297, 15, 112, 'login', 'usuarios', 'Login exitoso: adminis', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-12 08:29:12'),
(298, 15, 112, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-12 08:29:39'),
(299, 18, 113, 'login', 'usuarios', 'Login exitoso: super', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-12 08:29:47'),
(300, 18, 113, 'editar_permisos_usuario', 'usuarios', 'Permisos personalizados actualizados para usuario: opera', 'usuario', 17, '{\"permisos_anteriores\":{\"insumos\":[\"ver\",\"crear\",\"editar\",\"baja\"],\"asignaciones\":[\"ver\",\"crear\",\"editar\",\"devolver\"],\"sedes\":[\"ver\",\"crear\"],\"telecom\":[\"ver\",\"crear\"],\"reportes\":[\"ver\",\"exportar\"],\"areas\":[\"ver\",\"crear\"]}}', '{\"permisos_nuevos\":{\"insumos\":[\"ver\",\"crear\",\"editar\",\"eliminar\",\"baja\"],\"asignaciones\":[\"ver\",\"crear\",\"editar\",\"anular\",\"devolver\"],\"reportes\":[\"ver\",\"exportar\"],\"usuarios\":[\"ver\",\"crear\",\"editar\",\"eliminar\",\"cambiar_rol\",\"reset_password\"],\"auditoria\":[\"ver_todo\"],\"sedes\":[\"ver\",\"crear\",\"editar\",\"eliminar\"],\"sistema\":[\"backup\"],\"telecom\":[\"ver\",\"crear\",\"editar\",\"eliminar\"],\"areas\":[\"ver\",\"crear\",\"editar\",\"eliminar\"]}}', '127.0.0.1', 'exito', NULL, '2025-12-12 08:30:27'),
(301, 18, 113, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-12 08:30:41'),
(302, 17, 114, 'login', 'usuarios', 'Login exitoso: opera', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-12 08:30:46'),
(303, 17, 114, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-12 08:32:31'),
(304, 1, 115, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-12 08:32:38'),
(305, 1, 115, 'crear_usuario', 'usuarios', 'Usuario creado: opop (opop opop)', 'usuario', 19, NULL, '{\"username\":\"opop\",\"email\":\"ospera@gmail.com\",\"nombre\":\"opop\",\"apellido\":\"opop\",\"id_rol\":3}', '127.0.0.1', 'exito', NULL, '2025-12-12 08:33:11'),
(306, 1, 115, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-12 08:33:15'),
(307, 19, 116, 'login', 'usuarios', 'Login exitoso: opop', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-12 08:33:21'),
(308, 19, 116, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-12 08:34:05'),
(309, 1, 117, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-12 08:34:09'),
(310, 1, 118, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-15 11:13:29'),
(311, 1, 118, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-15 11:36:01'),
(312, 1, 119, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-15 11:36:43'),
(313, 1, 119, 'crear_usuario', 'usuarios', 'Usuario creado: jvillaverde (joaquin villaverde)', 'usuario', 20, NULL, '{\"username\":\"jvillaverde\",\"email\":\"jvillaverde@senaf.rionegro.gov.ar\",\"nombre\":\"joaquin\",\"apellido\":\"villaverde\",\"id_rol\":3}', '127.0.0.1', 'exito', NULL, '2025-12-15 11:37:17'),
(314, 1, 119, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-15 11:37:19'),
(315, 20, 120, 'login', 'usuarios', 'Login exitoso: jvillaverde', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-15 11:37:26'),
(316, 20, 120, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-15 11:39:38'),
(317, 1, 121, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-15 11:39:45'),
(318, 1, 122, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-15 12:20:13'),
(319, 1, 121, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-15 12:22:20'),
(320, 15, 123, 'login', 'usuarios', 'Login exitoso: adminis', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-15 12:22:27'),
(321, 15, 123, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-15 12:22:57'),
(322, 20, 124, 'login', 'usuarios', 'Login exitoso: jvillaverde', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-15 12:23:07'),
(323, 20, 124, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-15 12:43:14'),
(324, 1, 125, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-15 12:43:19'),
(325, 20, 126, 'login', 'usuarios', 'Login exitoso: jvillaverde', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-16 08:14:53'),
(326, 20, 126, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-16 09:03:20'),
(327, 15, 127, 'login', 'usuarios', 'Login exitoso: adminis', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-16 09:03:39'),
(328, 15, 127, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-16 09:33:04'),
(329, 20, 128, 'login', 'usuarios', 'Login exitoso: jvillaverde', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-16 09:33:12'),
(330, 20, 129, 'login', 'usuarios', 'Login exitoso: jvillaverde', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-16 10:03:07'),
(331, 20, 129, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-16 10:15:38'),
(332, 1, 130, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-16 10:15:44'),
(333, 20, 131, 'login', 'usuarios', 'Login exitoso: jvillaverde', NULL, NULL, NULL, NULL, '::1', 'exito', NULL, '2025-12-16 10:23:00'),
(334, 20, 132, 'login', 'usuarios', 'Login exitoso: jvillaverde', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-16 11:00:09'),
(335, 20, 132, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-16 11:13:15'),
(336, 20, 133, 'login', 'usuarios', 'Login exitoso: jvillaverde', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-16 11:13:27'),
(337, 20, 133, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-16 11:14:40'),
(338, 1, 134, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-16 11:14:44'),
(339, 20, 135, 'login', 'usuarios', 'Login exitoso: jvillaverde', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-16 11:23:40'),
(340, 20, 136, 'login', 'usuarios', 'Login exitoso: jvillaverde', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-16 11:54:32'),
(341, 20, 136, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-16 11:58:04'),
(342, 20, 137, 'login', 'usuarios', 'Login exitoso: jvillaverde', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-16 11:58:30'),
(343, 20, 138, 'login', 'usuarios', 'Login exitoso: jvillaverde', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-16 12:10:21'),
(344, 20, 139, 'login', 'usuarios', 'Login exitoso: jvillaverde', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-16 12:40:38'),
(345, 20, 139, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-16 12:48:49'),
(346, 1, 140, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-16 12:48:57'),
(347, 20, 141, 'login', 'usuarios', 'Login exitoso: jvillaverde', NULL, NULL, NULL, NULL, '::1', 'exito', NULL, '2025-12-16 12:51:59'),
(348, 20, 142, 'login', 'usuarios', 'Login exitoso: jvillaverde', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-16 13:17:12'),
(349, 1, 143, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-16 13:20:41'),
(350, 20, 144, 'login', 'usuarios', 'Login exitoso: jvillaverde', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-16 13:49:32'),
(351, 1, 145, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-16 13:57:19'),
(352, 20, 141, 'sesion_expirada', 'usuarios', 'Sesión expirada por inactividad', NULL, NULL, NULL, NULL, '::1', 'exito', NULL, '2025-12-17 10:08:13'),
(353, 20, 141, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '::1', 'exito', NULL, '2025-12-17 10:08:14'),
(354, 1, 146, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '::1', 'exito', NULL, '2025-12-17 10:08:21'),
(355, 1, 147, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '::1', 'exito', NULL, '2025-12-17 10:27:37'),
(356, 1, 146, 'crear_insumo', 'insumos', 'Insumo creado:  (Tipo: Varios)', 'insumo', 116, NULL, '{\"nombre_insumo\":null,\"tipo_insumo\":\"Varios\",\"cantidad\":4,\"estado\":\"Disponible\"}', '::1', 'exito', NULL, '2025-12-17 11:34:35'),
(357, 1, 146, 'crear_asignacion', 'asignaciones', 'Asignación creada - Remito: 0057_2025 - Persona: asfasdfa asdfasdfa', 'asignacion', 78, NULL, '{\"numero_remito\":\"0057_2025\",\"sede\":27,\"area\":5,\"persona\":\"asfasdfa asdfasdfa\",\"insumos_count\":2}', '::1', 'exito', NULL, '2025-12-17 11:34:54'),
(358, 1, 146, 'editar_permisos_usuario', 'usuarios', 'Permisos personalizados actualizados para usuario: opop', 'usuario', 19, '{\"permisos_anteriores\":{\"insumos\":[\"ver\",\"crear\",\"editar\",\"baja\"],\"asignaciones\":[\"ver\",\"crear\",\"editar\",\"devolver\"],\"sedes\":[\"ver\",\"crear\"],\"telecom\":[\"ver\",\"crear\"],\"reportes\":[\"ver\",\"exportar\"],\"areas\":[\"ver\",\"crear\"],\"pedidos\":[\"ver_propios\",\"crear\",\"ver_todos\",\"gestionar\",\"informe\"]}}', '{\"permisos_nuevos\":{\"insumos\":[\"ver\",\"crear\",\"editar\",\"baja\"],\"asignaciones\":[\"ver\",\"crear\",\"editar\",\"devolver\"],\"reportes\":[\"ver\",\"exportar\"],\"sedes\":[\"ver\",\"crear\",\"editar\"],\"telecom\":[\"ver\",\"crear\",\"editar\"],\"areas\":[\"ver\",\"crear\",\"editar\"]}}', '::1', 'exito', NULL, '2025-12-17 11:35:19');
INSERT INTO `auditoria_acciones` (`id_auditoria`, `id_usuario`, `id_sesion`, `accion`, `modulo`, `descripcion`, `entidad_tipo`, `entidad_id`, `datos_antes`, `datos_despues`, `ip_address`, `resultado`, `mensaje_error`, `fecha_accion`) VALUES
(359, 1, 146, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '::1', 'exito', NULL, '2025-12-17 12:10:24'),
(360, 20, 148, 'login', 'usuarios', 'Login exitoso: jvillaverde', NULL, NULL, NULL, NULL, '::1', 'exito', NULL, '2025-12-17 12:10:35'),
(361, 20, 148, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '::1', 'exito', NULL, '2025-12-17 12:23:50'),
(362, 1, 149, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '::1', 'exito', NULL, '2025-12-17 12:23:56'),
(363, 1, 150, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '10.114.85.131', 'exito', NULL, '2025-12-17 12:46:54'),
(364, 1, 151, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-19 10:25:03'),
(365, 1, 149, 'sesion_expirada', 'usuarios', 'Sesión expirada por inactividad', NULL, NULL, NULL, NULL, '::1', 'exito', NULL, '2025-12-22 11:43:20'),
(366, 1, 149, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '::1', 'exito', NULL, '2025-12-22 11:43:20'),
(367, 1, 152, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '::1', 'exito', NULL, '2025-12-22 11:43:31'),
(368, 1, 153, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '::1', 'exito', NULL, '2025-12-22 11:53:22'),
(369, 1, 154, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-22 13:46:42'),
(370, 1, 155, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-22 13:50:37'),
(371, 1, 156, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '::1', 'exito', NULL, '2025-12-23 08:19:37'),
(372, 1, 157, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2025-12-23 10:19:17'),
(373, 1, 158, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '::1', 'exito', NULL, '2025-12-23 10:43:39'),
(374, 1, 158, 'sesion_expirada', 'usuarios', 'Sesión expirada por inactividad', NULL, NULL, NULL, NULL, '::1', 'exito', NULL, '2025-12-23 11:51:04'),
(375, 1, 158, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '::1', 'exito', NULL, '2025-12-23 11:51:04'),
(376, 1, 159, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '::1', 'exito', NULL, '2025-12-23 11:52:23'),
(377, 1, 159, 'sesion_expirada', 'usuarios', 'Sesión expirada por inactividad', NULL, NULL, NULL, NULL, '::1', 'exito', NULL, '2025-12-23 13:02:46'),
(378, 1, 159, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '::1', 'exito', NULL, '2025-12-23 13:02:46'),
(379, 1, 160, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '::1', 'exito', NULL, '2025-12-23 13:02:51'),
(380, 1, 160, 'editar_insumo', 'insumos', 'Insumo editado: sadfasdfas (ID: 116)', 'insumo', 116, '{\"id_insumo\":116,\"nombre_insumo\":\"sadfasdfas\",\"tipo_insumo\":\"Varios\",\"subcategoria_varios\":\"Hardware\",\"descripcion_general\":null,\"numero_serie\":null,\"id_fisico\":null,\"id_patrimonio\":null,\"cantidad\":4,\"cantidad_oficina\":2,\"cantidad_deposito\":2,\"fecha_adquisicion\":\"2025-12-17\",\"estado\":\"Disponible\",\"id_punto_stock_actual\":null,\"id_sede_actual\":null,\"id_ingreso\":null,\"es_nuevo\":1,\"id_area_asignacion_actual\":null,\"id_patrimonio_idx\":null}', '{\"nombre_insumo\":\"sadfasdfas\",\"tipo_insumo\":\"Varios\",\"cantidad\":4}', '::1', 'exito', NULL, '2025-12-23 13:03:53'),
(381, 1, 160, 'editar_insumo', 'insumos', 'Insumo editado: Notebook 14\" (ID: 17)', 'insumo', 17, '{\"id_insumo\":17,\"nombre_insumo\":\"Notebook 14\\\"\",\"tipo_insumo\":\"Notebook\",\"subcategoria_varios\":null,\"descripcion_general\":null,\"numero_serie\":null,\"id_fisico\":\"NOTEBOOK-ID0005\",\"id_patrimonio\":\"PAT00005\",\"cantidad\":1,\"cantidad_oficina\":null,\"cantidad_deposito\":null,\"fecha_adquisicion\":\"2025-09-16\",\"estado\":\"Disponible\",\"id_punto_stock_actual\":null,\"id_sede_actual\":null,\"id_ingreso\":null,\"es_nuevo\":1,\"id_area_asignacion_actual\":null,\"id_patrimonio_idx\":\"PAT00005\"}', '{\"nombre_insumo\":\"Notebook 14\\\"\",\"tipo_insumo\":\"Notebook\",\"cantidad\":1}', '::1', 'exito', NULL, '2025-12-23 13:04:06'),
(382, 1, 160, 'sesion_expirada', 'usuarios', 'Sesión expirada por inactividad', NULL, NULL, NULL, NULL, '::1', 'exito', NULL, '2025-12-23 13:50:44'),
(383, 1, 160, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '::1', 'exito', NULL, '2025-12-23 13:50:44'),
(384, 1, 161, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '::1', 'exito', NULL, '2025-12-23 13:50:55'),
(385, 1, 152, 'sesion_expirada', 'usuarios', 'Sesión expirada por inactividad', NULL, NULL, NULL, NULL, '::1', 'exito', NULL, '2025-12-26 09:01:17'),
(386, 1, 152, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '::1', 'exito', NULL, '2025-12-26 09:01:22'),
(387, 1, 162, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '::1', 'exito', NULL, '2025-12-26 09:12:22'),
(388, 1, NULL, 'login_fallido', 'usuarios', 'Intento de login fallido - Contraseña incorrecta para: admin', NULL, NULL, NULL, NULL, '::1', 'error', 'Contraseña incorrecta', '2025-12-26 09:20:12'),
(389, 1, 163, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '::1', 'exito', NULL, '2025-12-26 09:25:12'),
(390, 1, 164, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '::1', 'exito', NULL, '2025-12-26 09:31:38'),
(391, 1, 165, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '::1', 'exito', NULL, '2025-12-26 10:18:26'),
(392, 1, 166, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '::1', 'exito', NULL, '2025-12-26 10:21:48'),
(393, 1, 162, 'sesion_expirada', 'usuarios', 'Sesión expirada por inactividad', NULL, NULL, NULL, NULL, '::1', 'exito', NULL, '2025-12-26 11:39:54'),
(394, 1, 162, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '::1', 'exito', NULL, '2025-12-26 11:39:54'),
(395, 1, 167, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '::1', 'exito', NULL, '2025-12-26 11:39:59'),
(396, 1, 168, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '::1', 'exito', NULL, '2025-12-26 12:01:24'),
(397, 1, 166, 'sesion_expirada', 'usuarios', 'Sesión expirada por inactividad', NULL, NULL, NULL, NULL, '::1', 'exito', NULL, '2025-12-26 13:05:10'),
(398, 1, 166, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '::1', 'exito', NULL, '2025-12-26 13:05:10'),
(399, 1, 169, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2026-01-02 08:14:07'),
(400, 1, 169, 'crear_asignacion', 'asignaciones', 'Asignación creada - Remito: 0001_2026 - Persona: año nuevo', 'asignacion', 89, NULL, '{\"numero_remito\":\"0001_2026\",\"sede\":38,\"area\":2,\"persona\":\"año nuevo\",\"insumos_count\":2}', '127.0.0.1', 'exito', NULL, '2026-01-02 08:24:11'),
(401, 1, 169, 'crear_insumo', 'insumos', 'Insumo creado: asdfasdf (Tipo: Varios)', 'insumo', 127, NULL, '{\"nombre_insumo\":\"asdfasdf\",\"tipo_insumo\":\"Varios\",\"cantidad\":4,\"estado\":\"Disponible\"}', '127.0.0.1', 'exito', NULL, '2026-01-02 08:36:13'),
(402, 20, 170, 'login', 'usuarios', 'Login exitoso: jvillaverde', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2026-01-02 08:48:03'),
(403, 1, NULL, 'login_fallido', 'usuarios', 'Intento de login fallido - Contraseña incorrecta para: admin', NULL, NULL, NULL, NULL, '::1', 'error', 'Contraseña incorrecta', '2026-01-02 08:57:05'),
(404, 1, 171, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '::1', 'exito', NULL, '2026-01-02 08:58:27'),
(405, 1, 172, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '::1', 'exito', NULL, '2026-01-02 09:41:48'),
(406, 1, 167, 'sesion_expirada', 'usuarios', 'Sesión expirada por inactividad', NULL, NULL, NULL, NULL, '::1', 'exito', NULL, '2026-01-02 10:02:35'),
(407, 1, 167, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '::1', 'exito', NULL, '2026-01-02 10:02:35'),
(408, 1, 173, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '::1', 'exito', NULL, '2026-01-02 10:02:39'),
(409, 1, 173, 'crear_insumo', 'insumos', 'Insumo creado: asdfas (Tipo: Varios)', 'insumo', 129, NULL, '{\"nombre_insumo\":\"asdfas\",\"tipo_insumo\":\"Varios\",\"cantidad\":4,\"estado\":\"Disponible\"}', '::1', 'exito', NULL, '2026-01-02 10:46:13'),
(410, 1, 173, 'crear_insumo', 'insumos', 'Insumo creado: asdfa (Tipo: PC Escritorio)', 'insumo', 130, NULL, '{\"nombre_insumo\":\"asdfa\",\"tipo_insumo\":\"PC Escritorio\",\"cantidad\":\"1\",\"estado\":\"Disponible\"}', '::1', 'exito', NULL, '2026-01-02 10:57:07'),
(411, 1, 173, 'crear_asignacion', 'asignaciones', 'Asignación creada - Remito: 0002_2026 - Persona: sadfas asdfa', 'asignacion', 91, NULL, '{\"numero_remito\":\"0002_2026\",\"sede\":38,\"area\":9,\"persona\":\"sadfas asdfa\",\"insumos_count\":2}', '::1', 'exito', NULL, '2026-01-02 10:57:48'),
(412, 1, 173, 'crear_asignacion', 'asignaciones', 'Asignación creada - Remito: 0003_2026 - Persona: sdfsdf sdfsdf', 'asignacion', 92, NULL, '{\"numero_remito\":\"0003_2026\",\"sede\":32,\"area\":6,\"persona\":\"sdfsdf sdfsdf\",\"insumos_count\":2}', '::1', 'exito', NULL, '2026-01-02 11:01:35'),
(413, 1, 173, 'desactivar_usuario', 'usuarios', 'Usuario desactivar: adminis', 'usuario', 15, '{\"activo\":1}', '{\"activo\":0}', '::1', 'exito', NULL, '2026-01-02 11:02:44'),
(414, 1, 173, 'sesion_expirada', 'usuarios', 'Sesión expirada por inactividad', NULL, NULL, NULL, NULL, '::1', 'exito', NULL, '2026-01-02 12:16:32'),
(415, 1, 173, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '::1', 'exito', NULL, '2026-01-02 12:16:32'),
(416, 1, 174, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '::1', 'exito', NULL, '2026-01-02 12:16:37'),
(417, 1, 174, 'sesion_expirada', 'usuarios', 'Sesión expirada por inactividad', NULL, NULL, NULL, NULL, '::1', 'exito', NULL, '2026-01-02 13:23:17'),
(418, 1, 174, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '::1', 'exito', NULL, '2026-01-02 13:23:17'),
(419, 1, 175, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '::1', 'exito', NULL, '2026-01-02 13:23:24'),
(420, 1, 175, 'sesion_expirada', 'usuarios', 'Sesión expirada por inactividad', NULL, NULL, NULL, NULL, '::1', 'exito', NULL, '2026-01-05 12:12:55'),
(421, 1, 175, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '::1', 'exito', NULL, '2026-01-05 12:12:56'),
(422, 1, 176, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '::1', 'exito', NULL, '2026-01-05 12:13:01'),
(423, 1, 176, 'sesion_expirada', 'usuarios', 'Sesión expirada por inactividad', NULL, NULL, NULL, NULL, '::1', 'exito', NULL, '2026-01-05 13:05:46'),
(424, 1, 176, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '::1', 'exito', NULL, '2026-01-05 13:05:46'),
(425, 1, 177, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '::1', 'exito', NULL, '2026-01-05 13:05:56'),
(426, 1, 178, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2026-01-06 13:45:43'),
(427, 1, 179, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '10.114.85.131', 'exito', NULL, '2026-01-06 13:47:06'),
(428, 1, 178, 'backup', 'sistema', 'Backup de base de datos generado', 'sistema', 0, NULL, NULL, '127.0.0.1', 'exito', NULL, '2026-01-06 13:55:27'),
(429, 1, 180, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2026-01-07 08:24:11'),
(430, 1, 180, 'sesion_expirada', 'usuarios', 'Sesión expirada por inactividad', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2026-01-07 10:28:21'),
(431, 1, 180, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2026-01-07 10:28:21'),
(432, 1, NULL, 'login_fallido', 'usuarios', 'Intento de login fallido - Contraseña incorrecta para: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'error', 'Contraseña incorrecta', '2026-01-07 10:28:29'),
(433, 1, 181, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2026-01-07 10:28:37'),
(434, 1, NULL, 'login_fallido', 'usuarios', 'Intento de login fallido - Contraseña incorrecta para: admin', NULL, NULL, NULL, NULL, '10.114.85.131', 'error', 'Contraseña incorrecta', '2026-01-07 11:03:04'),
(435, 1, 182, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '10.114.85.131', 'exito', NULL, '2026-01-07 11:03:08'),
(436, 1, 182, 'crear_insumo', 'insumos', 'Insumo creado: PC (Tipo: PC Escritorio)', 'insumo', 131, NULL, '{\"nombre_insumo\":\"PC\",\"tipo_insumo\":\"PC Escritorio\",\"cantidad\":\"1\",\"estado\":\"Disponible\"}', '10.114.85.131', 'exito', NULL, '2026-01-07 11:11:51'),
(437, 1, 182, 'editar_insumo', 'insumos', 'Insumo editado: PC (ID: 131)', 'insumo', 131, '{\"id_insumo\":131,\"nombre_insumo\":\"PC\",\"tipo_insumo\":\"PC Escritorio\",\"subcategoria_varios\":null,\"descripcion_general\":null,\"numero_serie\":\"1\",\"id_fisico\":\"1\",\"id_patrimonio\":\"1\",\"cantidad\":1,\"cantidad_oficina\":null,\"cantidad_deposito\":null,\"fecha_adquisicion\":\"2026-01-02\",\"estado\":\"Disponible\",\"id_punto_stock_actual\":2,\"id_sede_actual\":null,\"id_ingreso\":43,\"es_nuevo\":1,\"id_area_asignacion_actual\":null,\"id_patrimonio_idx\":\"1\"}', '{\"nombre_insumo\":\"PC\",\"tipo_insumo\":\"PC Escritorio\",\"cantidad\":1}', '10.114.85.131', 'exito', NULL, '2026-01-07 11:13:15'),
(438, 1, 182, 'crear_insumo', 'insumos', 'Insumo creado: Toner MS421 (Tipo: Varios)', 'insumo', 132, NULL, '{\"nombre_insumo\":\"Toner MS421\",\"tipo_insumo\":\"Varios\",\"cantidad\":30,\"estado\":\"Disponible\"}', '10.114.85.131', 'exito', NULL, '2026-01-07 11:19:45'),
(439, 1, 182, 'crear_insumo', 'insumos', 'Insumo creado: LG 32\" (Tipo: Monitor)', 'insumo', 133, NULL, '{\"nombre_insumo\":\"LG 32\\\"\",\"tipo_insumo\":\"Monitor\",\"cantidad\":\"1\",\"estado\":\"Disponible\"}', '10.114.85.131', 'exito', NULL, '2026-01-07 11:25:34'),
(440, 1, 182, 'crear_asignacion', 'asignaciones', 'Asignación creada - Remito: 0004_2026 - Persona: Erika V. Gimenez', 'asignacion', 93, NULL, '{\"numero_remito\":\"0004_2026\",\"sede\":2,\"area\":1,\"persona\":\"Erika V. Gimenez\",\"insumos_count\":2}', '10.114.85.131', 'exito', NULL, '2026-01-07 11:27:18'),
(441, 1, 182, 'crear_asignacion', 'asignaciones', 'Asignación creada - Remito: 0005_2026 - Persona: Erika V. Gimenez', 'asignacion', 94, NULL, '{\"numero_remito\":\"0005_2026\",\"sede\":2,\"area\":1,\"persona\":\"Erika V. Gimenez\",\"insumos_count\":1}', '10.114.85.131', 'exito', NULL, '2026-01-07 11:28:45'),
(442, 1, 181, 'sesion_expirada', 'usuarios', 'Sesión expirada por inactividad', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2026-01-07 13:00:30'),
(443, 1, 181, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2026-01-07 13:00:30'),
(444, 1, 183, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2026-01-07 13:00:36'),
(445, 1, NULL, 'login_fallido', 'usuarios', 'Intento de login fallido - Contraseña incorrecta para: admin', NULL, NULL, NULL, NULL, '::1', 'error', 'Contraseña incorrecta', '2026-01-09 11:11:29'),
(446, 1, 184, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '::1', 'exito', NULL, '2026-01-09 11:11:34'),
(447, 1, 184, 'sesion_expirada', 'usuarios', 'Sesión expirada por inactividad', NULL, NULL, NULL, NULL, '::1', 'exito', NULL, '2026-01-09 13:36:56'),
(448, 1, 184, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '::1', 'exito', NULL, '2026-01-09 13:36:57'),
(449, 1, NULL, 'login_fallido', 'usuarios', 'Intento de login fallido - Contraseña incorrecta para: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'error', 'Contraseña incorrecta', '2026-01-12 09:04:47'),
(450, 1, 185, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '127.0.0.1', 'exito', NULL, '2026-01-12 09:04:55'),
(451, 1, 186, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '::1', 'exito', NULL, '2026-01-12 09:15:29'),
(452, 1, 187, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '::1', 'exito', NULL, '2026-01-12 09:48:25'),
(453, 1, 188, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '::1', 'exito', NULL, '2026-01-12 10:12:33'),
(454, 1, 188, 'sesion_expirada', 'usuarios', 'Sesión expirada por inactividad', NULL, NULL, NULL, NULL, '::1', 'exito', NULL, '2026-01-12 11:46:32'),
(455, 1, 188, 'logout', 'usuarios', 'Cierre de sesión', NULL, NULL, NULL, NULL, '::1', 'exito', NULL, '2026-01-12 11:46:33'),
(456, 1, NULL, 'login_fallido', 'usuarios', 'Intento de login fallido - Contraseña incorrecta para: admin', NULL, NULL, NULL, NULL, '::1', 'error', 'Contraseña incorrecta', '2026-01-12 11:50:23'),
(457, 1, 189, 'login', 'usuarios', 'Login exitoso: admin', NULL, NULL, NULL, NULL, '::1', 'exito', NULL, '2026-01-12 11:53:25');

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
(17, 92, 'Hp', 'Mc 2023'),
(18, 120, 'asdfa', 'e333');

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
(42, 'fondos', 'asdjñ', 'asdawd', '2026-01-10', '2026-01-07 10:43:53', '2026-01-07 10:43:53'),
(43, 'compra_directa', '101-Compuser-viedma', 'Los toner que no sirven', '2026-01-02', '2026-01-07 10:46:22', '2026-01-07 11:09:27'),
(44, 'licitacion', '1234', 'LAs pc de compuser', '2026-01-10', '2026-01-07 11:10:35', '2026-01-07 11:10:35');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ingresos_documentos`
--

CREATE TABLE `ingresos_documentos` (
  `id_documento` int(11) NOT NULL,
  `id_ingreso` int(11) NOT NULL,
  `nombre_archivo` varchar(255) NOT NULL,
  `ruta_archivo` varchar(500) NOT NULL,
  `tipo_documento` enum('remito','documentacion','otro') DEFAULT 'otro',
  `fecha_carga` datetime DEFAULT current_timestamp(),
  `cargado_por` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `ingresos_documentos`
--

INSERT INTO `ingresos_documentos` (`id_documento`, `id_ingreso`, `nombre_archivo`, `ruta_archivo`, `tipo_documento`, `fecha_carga`, `cargado_por`) VALUES
(10, 42, 'Este Joaquin.xlsx', 'remito_42_1767793479.xlsx', 'remito', '2026-01-07 10:44:39', 1),
(11, 42, 'Este Joaquin.xlsx', 'documentacion_42_1767793504.xlsx', 'documentacion', '2026-01-07 10:45:04', 1),
(12, 43, 'Formulario para la rendicion de la comision de servicios-1.pdf', 'remito_43_1767793582.pdf', 'remito', '2026-01-07 10:46:22', 1),
(13, 43, 'Este Joaquin(4).xlsx', 'documentacion_43_1767793582.xlsx', 'documentacion', '2026-01-07 10:46:22', 1),
(14, 43, 'Este Joaquin(3).xlsx', 'documentacion_43_1767793599.xlsx', 'documentacion', '2026-01-07 10:46:39', 1);

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
(7, 'Escarner Nuevo', 'Escaner', NULL, NULL, '108923', 'E45333', 'asfd123', 1, NULL, NULL, '2025-08-18', 'Disponible', NULL, NULL, NULL, 1, NULL),
(16, 'PC Escritorio Oficina recuperada en comision', 'PC Escritorio', NULL, NULL, 'PC COMPLETA-SN0004', 'PC COMPLETA-ID0004', 'PAT00004', 1, NULL, NULL, '2025-09-16', 'De Baja', 2, NULL, NULL, 0, NULL),
(17, 'Notebook 14\"', 'Notebook', NULL, NULL, 'fdgd4444', 'NOTEBOOK-ID0005', 'PAT00005', 1, NULL, NULL, '2025-09-16', 'Disponible', NULL, NULL, NULL, 1, NULL),
(19, 'Monitor 24\"', 'Monitor', NULL, NULL, 'MONITOR-SN0007', 'MONITOR-ID0007', 'PAT00007', 1, NULL, NULL, '2025-09-16', 'De Baja', 2, NULL, NULL, 1, NULL),
(23, 'Fichero', 'Escaner', NULL, NULL, '65468614', 'D466', 'D466', 1, NULL, NULL, '2025-10-14', 'Asignado', NULL, 38, NULL, 1, 2),
(27, 'Coradir 2020', 'PC Escritorio', NULL, NULL, '8768976', 'D342', 'D423', 1, NULL, NULL, '2025-10-17', 'Asignado', NULL, 51, NULL, 1, 5),
(29, 'Nueva', 'Impresora', NULL, NULL, '646979', 'D458', 'D748', 1, NULL, NULL, '2025-10-17', 'Asignado', NULL, 32, NULL, 1, 6),
(31, 'Test Accesorios', 'Notebook', NULL, NULL, '9879789', 'Df97', 'Df97', 1, NULL, NULL, '2025-10-17', 'Disponible', NULL, NULL, NULL, 1, NULL),
(47, 'impresora con scanner', 'Impresora', NULL, NULL, '3652656887795', '887795', '887795', 1, NULL, NULL, '2025-10-21', 'Asignado', NULL, 51, NULL, 1, 5),
(54, 'nueva fecha', 'Notebook', NULL, NULL, '2342', 's23', 's23', 1, NULL, NULL, '2025-11-23', 'Disponible', 2, NULL, NULL, 1, NULL),
(60, 'probando si la agregaa', 'PC Escritorio', NULL, NULL, 'asdlfkj', 'sdfj', 'adslfgk', 1, NULL, NULL, '2025-10-22', 'Disponible', 2, NULL, NULL, 1, NULL),
(61, 'Coradir', 'PC Escritorio', NULL, NULL, '394702', 'F45', 'F45', 1, NULL, NULL, '2025-05-01', 'Asignado', NULL, 51, NULL, 1, 5),
(75, 'asdfa', 'Monitor', NULL, NULL, 'asdfas', 'asdfasd', 'asdfa', 1, NULL, NULL, '2025-10-01', 'Disponible', NULL, NULL, NULL, 1, NULL),
(88, 'note note', 'Notebook', NULL, NULL, 'qsfeqsdf', 'asdfasdf', 'asdfasdf', 1, NULL, NULL, '2025-10-24', 'Disponible', 2, NULL, NULL, 1, NULL),
(90, NULL, 'PC Escritorio', NULL, NULL, '54646', NULL, NULL, 1, NULL, NULL, '2025-10-24', 'Asignado', NULL, 27, NULL, 1, 11),
(91, 'moni moni', 'Monitor', NULL, NULL, 'sasdkf023\'204', 'saldfj3', '04\'0284lkm', 1, NULL, NULL, '2025-10-24', 'Disponible', 2, NULL, NULL, 1, NULL),
(92, 'Recuperada en comision', 'Impresora', NULL, NULL, '123123123', '123123123', '123123123', 1, NULL, NULL, '2025-10-30', 'Asignado', NULL, 32, NULL, 1, 6),
(94, 'Tesr', 'PC Escritorio', NULL, NULL, 'asdaaf', 'asdfa', 'asdfaasd', 1, NULL, NULL, '2025-10-30', 'Disponible', 2, NULL, NULL, 1, NULL),
(95, NULL, 'PC Escritorio', NULL, NULL, '3908204', '0293420', '09283402', 1, NULL, NULL, '2025-10-30', 'Asignado', NULL, 27, NULL, 1, 11),
(97, 'sdf', 'PC Escritorio', NULL, NULL, NULL, 'sd', 'sdf', 1, NULL, NULL, '2025-11-04', 'Disponible', 2, NULL, NULL, 1, NULL),
(98, 'qwerqw', 'PC Escritorio', NULL, NULL, NULL, 'qwer', 'qwer', 1, NULL, NULL, '2025-11-04', 'Disponible', 2, NULL, NULL, 1, NULL),
(100, 'dfgh', 'Notebook', NULL, NULL, 'dfgh', NULL, NULL, 1, NULL, NULL, '2025-11-05', 'Disponible', NULL, NULL, NULL, 1, NULL),
(101, 'asdf', 'PC Escritorio', NULL, NULL, 'asdf', 'asdf', 'asdf', 1, NULL, NULL, '2025-11-18', 'Asignado', NULL, 38, NULL, 1, 9),
(102, 'Style USB', 'Varios', 'Hardware', 'para si algun dia compramos IPAds', NULL, NULL, NULL, 110, 110, 0, '2025-05-01', 'Disponible', NULL, 51, 43, 1, 5),
(104, 'm', 'Notebook', NULL, NULL, 'h', 'h', 'h', 1, NULL, NULL, '2025-11-20', 'Disponible', NULL, NULL, NULL, 1, NULL),
(105, 'asdas', 'Notebook', NULL, NULL, 'asdasda', 'asdasda', 'asdasda', 1, NULL, NULL, '2025-11-25', 'Asignado', NULL, 27, NULL, 1, 5),
(106, 'Insumo 2.2', 'Varios', 'Periféricos', NULL, NULL, NULL, NULL, 11, 6, 5, '2025-11-02', 'Disponible', NULL, NULL, 44, 1, NULL),
(108, 'asdf', 'PC Escritorio', NULL, NULL, 'asdfasaaa', 'adfdasd', 'adfaf', 1, NULL, NULL, '2025-12-03', 'Asignado', NULL, 27, NULL, 1, 5),
(109, 'Cable HDMI 2m5Cable HDMI 2m', 'Varios', 'Hardware', 'Cables HDMI de 2 metros para monitores', NULL, NULL, NULL, 109, 5, 104, '2025-12-03', 'Disponible', NULL, 2, NULL, 0, 1),
(110, NULL, 'Monitor', NULL, NULL, 'NB2025001', 'FIS-MON-001', NULL, 1, NULL, NULL, '2025-12-03', 'Asignado', NULL, 38, NULL, 1, 2),
(112, 'sdf', 'PC Escritorio', NULL, NULL, 'gfgfggfdssdggsfd', 'sdggfdsdgffgsgdf', 'sdgfdsgdsfgfdsgs', 1, NULL, NULL, '2025-12-10', 'Asignado', 2, 38, NULL, 1, 2),
(113, NULL, 'PC Escritorio', NULL, NULL, NULL, 'qwerq', 'qwerqqq', 1, NULL, NULL, '2025-12-10', 'Asignado', NULL, 51, NULL, 0, 9),
(114, NULL, 'PC Escritorio', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, '2025-12-10', 'Asignado', NULL, 51, NULL, 0, 5),
(115, NULL, 'PC Escritorio', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, '2025-12-10', 'Asignado', NULL, 51, NULL, 0, 9),
(116, 'sadfasdfas', 'Varios', 'Hardware', 'cbfdbdfb', NULL, NULL, NULL, 4, 2, 2, '2025-12-17', 'Disponible', NULL, NULL, NULL, 1, NULL),
(117, 'fasdf', 'Varios', 'Hardware', NULL, NULL, NULL, NULL, 0, 0, 0, '2025-12-22', 'Asignado', NULL, 39, NULL, 0, 10),
(118, 'asdasd', 'PC Escritorio', NULL, NULL, 'asdasd', 'asdasdasd', 'asdasdasd', 1, NULL, NULL, '2025-12-22', 'Asignado', NULL, 27, NULL, 0, 6),
(119, NULL, 'PC Escritorio', NULL, NULL, '45612548', '46584687', '46584687', 1, NULL, NULL, '2025-12-23', 'Asignado', NULL, 50, NULL, 0, 11),
(120, 'asdfasdfa', 'Impresora', NULL, NULL, 'asdfasdfasfd', 'asdfasdfadsf', 'asdfasdfa', 1, NULL, NULL, '2025-12-26', 'Asignado', NULL, 50, NULL, 0, 11),
(121, 'qweqwe', 'Varios', NULL, NULL, NULL, NULL, NULL, 0, 0, 0, '2025-12-26', 'Asignado', NULL, 27, NULL, 0, 6),
(122, 'Impresora 3d ender k1', 'Varios', 'Periféricos', NULL, NULL, NULL, NULL, 0, 0, 0, '2025-12-26', 'Asignado', NULL, 51, NULL, 1, 11),
(123, 'Manual Test', 'Varios', NULL, NULL, NULL, NULL, NULL, 0, 0, 0, '2025-12-26', 'Asignado', NULL, 38, NULL, 0, 2),
(124, 'asda', 'Varios', 'Hardware', NULL, NULL, NULL, NULL, 0, 0, 0, '2025-12-26', 'Disponible', NULL, 38, NULL, 0, 11),
(125, 'asdasd', 'PC Escritorio', NULL, NULL, 'asdasdas111', 'asdasdas', 'asdasdads', 1, NULL, NULL, '2025-12-26', 'Asignado', NULL, 51, NULL, 1, 9),
(126, 'aseasdasd', 'Varios', 'Periféricos', NULL, NULL, NULL, NULL, 0, 0, 0, '2025-12-26', 'Asignado', NULL, 43, NULL, 0, 6),
(127, 'asdfasdf', 'Varios', 'Hardware', NULL, NULL, NULL, NULL, 4, 2, 2, '2026-01-02', 'Disponible', NULL, NULL, NULL, 1, NULL),
(128, 'sdfaf', 'Notebook', NULL, NULL, '232wef', 'wer23234', 'sefsdf2342', 1, NULL, NULL, '2025-12-10', 'Disponible', NULL, NULL, NULL, 1, NULL),
(129, 'asdfas', 'Varios', 'Hardware', NULL, NULL, NULL, NULL, 3, 1, 2, '2026-01-02', 'Disponible', NULL, 38, NULL, 1, 9),
(130, 'asdfa', 'PC Escritorio', NULL, NULL, 'adfas', 'sdfasdfa', 'asdfadfa', 1, NULL, NULL, '2026-01-02', 'Disponible', NULL, NULL, NULL, 1, NULL),
(131, 'PC', 'PC Escritorio', NULL, NULL, '1', '1', '1', 1, NULL, NULL, '2026-01-10', 'Asignado', NULL, 2, 44, 1, 1),
(132, 'Toner MS421', 'Varios', 'Periféricos', 'Toners para la impresora', NULL, NULL, NULL, 30, 15, 15, '2026-01-10', 'Disponible', NULL, NULL, 44, 1, NULL),
(133, 'LG 32\"', 'Monitor', NULL, NULL, '2131321', '2', '2', 1, NULL, NULL, '2026-01-10', 'Asignado', NULL, 2, 44, 1, 1);

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
(14, 102, '2025-11-20 09:14:08', 'wrqwr', 20);

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
(8, 102, 'reposicion_oficina', 10, 'deposito', 'oficina', 88, 25, 98, 15, NULL, '', '2025-11-19 08:17:39'),
(10, 102, 'reposicion_oficina', 15, 'deposito', 'oficina', 95, 15, 110, 0, NULL, '', '2025-12-03 09:31:56'),
(11, 106, 'reposicion_oficina', 4, 'deposito', 'oficina', 2, 4, 6, 0, NULL, '', '2025-12-03 09:32:06');

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
(11, 91, 'waefj', 'oskdjf', 3.0, 'HDMI'),
(12, 110, 'Samsung', '27 Curved', 27.0, 'HDMI'),
(13, 133, 'LG', 'TVR32', 32.0, 'HDMI');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notas_pedidos`
--

CREATE TABLE `notas_pedidos` (
  `id_nota` int(11) NOT NULL,
  `id_pedido` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `nota` text NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(22, 100, 'dfgh', 'dfgh', 'dfgh', 4, 4, 0, 0, 0, NULL, 0, 0),
(23, 104, 'h', 'h', 'h', 5, 5, 0, 0, 0, NULL, 0, 0),
(24, 105, 'aasd', 'asda', 'aasd', 2, 2, 0, 1, 0, NULL, 0, 0),
(26, 17, 'Hp', 'Pavilion', 'I3-4478', 4, 500, 1, 1, 0, NULL, 0, 1),
(27, 128, 'sqdfqf', 'qfqfq', '234', 333, 333, 1, 1, 1, 64, 1, 1);

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
(17, 60, 'sdlkg', 3, 3, 'sdfj', NULL),
(18, 27, 'I7-7845', 8, 500, 'ASUS H110M VK', NULL),
(30, 95, 'I5-15882U', 4, 45, '431', 'Ubuntu 24'),
(32, 94, 'I9 ultra core', 3, 3, 'asdf', 'Linux Mint 16.05'),
(35, 97, 'sdf', 3, 3, 'sdf2', 'sdf'),
(37, 98, 'qwer', 3, 3, 'qwer', 'qwer'),
(38, 16, 'sfaddfg', 3, 3, 'sdf', 'Win 11'),
(39, 90, 'I9 ultra core', 3, 3, 'sdf', 'Ubuntu 22'),
(40, 101, '3a', 2, 2, 'asdf2', 'asf'),
(42, 61, 'intel i15 rasonware', 32, 1000, 'asus h110', 'won 11'),
(43, 108, '234', 23, 23, '23', '23'),
(44, 112, 'sdffgdsfggfsfgd', 3, 3, 'sdafdfdfdaf', 'asdfasdf'),
(45, 113, '3234', 23, 23, 'wfsdf', 'werfsd'),
(46, 114, 'l,{,', 8, 7, 'lm{', 'ĺñ'),
(47, 115, 'asdfa', 2, 2, 'sdf', 'sdf'),
(48, 118, 'asdasdad', 2, 2, 'asdasd', 'probando creacion'),
(49, 119, 'Ryzen 11', 8, 500, 'asus h110', 'Winwods 12'),
(50, 125, 'asdasd', 22, 22, 'aasddd222', 'asdasd22'),
(51, 130, 'sadfadsf', 2, 2, 'asdfasf', 'adsf'),
(53, 131, 'Threadripper', 64, 4096, 'ASUS X7', 'Windows 14');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedidos`
--

CREATE TABLE `pedidos` (
  `id_pedido` int(11) NOT NULL,
  `tipo` enum('Mantenimiento','Reparación','Soporte','Pedido Insumo') NOT NULL,
  `descripcion` text NOT NULL,
  `solicitante_nombre` varchar(100) NOT NULL,
  `solicitante_apellido` varchar(100) DEFAULT NULL,
  `solicitante_telefono` varchar(50) DEFAULT NULL,
  `solicitante_email` varchar(100) DEFAULT NULL,
  `prioridad` enum('Baja','Media','Alta') NOT NULL DEFAULT 'Media',
  `estado` enum('Pendiente','En Proceso','Completado','Rechazado') NOT NULL DEFAULT 'Pendiente',
  `id_usuario_solicitante` int(11) NOT NULL,
  `id_sede` int(11) NOT NULL,
  `id_area` int(11) DEFAULT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `asignado_a` int(11) DEFAULT NULL,
  `id_insumo_relacionado` int(11) DEFAULT NULL,
  `insumo_relacionado` varchar(255) DEFAULT NULL,
  `pdf_nota` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `pedidos`
--

INSERT INTO `pedidos` (`id_pedido`, `tipo`, `descripcion`, `solicitante_nombre`, `solicitante_apellido`, `solicitante_telefono`, `solicitante_email`, `prioridad`, `estado`, `id_usuario_solicitante`, `id_sede`, `id_area`, `fecha_creacion`, `fecha_actualizacion`, `asignado_a`, `id_insumo_relacionado`, `insumo_relacionado`, `pdf_nota`) VALUES
(30, 'Reparación', 'No enciende posiblemente erroe initframt', 'Jorge', 'Torres', '2915120832', NULL, 'Alta', 'Completado', 1, 43, 7, '2026-01-02 08:45:00', '2026-01-02 08:48:45', 20, 105, 'Notebook - asdas (S/N: asdasda)', 'pedido_1767354300_6957afbc81851.pdf'),
(31, 'Mantenimiento', 'Nesecita limpieza', 'ruben', 'castro', NULL, NULL, 'Media', 'En Proceso', 1, 27, 3, '2026-01-02 08:53:02', '2026-01-02 09:04:37', 1, 125, 'PC Escritorio - asdasd (S/N: asdasdas111)', 'pedido_1767354782_6957b19e95cc1.pdf'),
(32, 'Mantenimiento', 'asdfa', 'asdfasd', 'asdfa', NULL, NULL, 'Media', 'En Proceso', 1, 50, 3, '2026-01-02 09:10:18', '2026-01-02 09:13:06', 15, 110, 'Monitor (S/N: NB2025001)', NULL),
(33, 'Reparación', 'sadfa', 'asdfasdf', 'asdfasdf', NULL, NULL, 'Media', 'En Proceso', 1, 51, 8, '2026-01-02 09:40:30', '2026-01-02 09:55:10', 15, 95, 'PC Escritorio (S/N: 3908204)', 'pedido_1767357630_6957bcbeba09c.pdf'),
(34, 'Soporte', 'sdfa', 'asdfasdf', 'asdfa', NULL, NULL, 'Baja', 'En Proceso', 1, 50, 8, '2026-01-02 09:41:02', '2026-01-02 09:48:04', 15, 105, 'Notebook - asdas (S/N: asdasda)', NULL),
(35, 'Mantenimiento', 'ssdf', 'asdfaww333', 'asdf333', NULL, NULL, 'Media', 'En Proceso', 1, 40, 2, '2026-01-02 09:41:18', '2026-01-02 09:44:36', 15, 110, 'Monitor (S/N: NB2025001)', NULL),
(36, 'Soporte', 'wer', 'sfdsdfsdf', 'sdfsdf', NULL, NULL, 'Media', 'En Proceso', 1, 38, 3, '2026-01-02 09:59:32', '2026-01-02 10:02:50', 17, 110, 'Monitor (S/N: NB2025001)', NULL),
(37, 'Reparación', 'sdfsdf', 'qwerq', 'qwer', NULL, NULL, 'Media', 'Rechazado', 1, 41, 8, '2026-01-02 10:03:12', '2026-01-02 10:32:22', 1, 110, 'Monitor (S/N: NB2025001)', NULL),
(38, 'Mantenimiento', 'sdfsfff', '654dsf', 'asdf', NULL, NULL, 'Media', 'En Proceso', 1, 41, 8, '2026-01-02 10:13:24', '2026-01-02 10:21:59', 17, 110, 'Monitor (S/N: NB2025001)', 'pedido_1767359604_6957c474b9a80.pdf'),
(39, 'Reparación', 'fghdh', 'qwerqwer', 'qwerqwer', NULL, NULL, 'Media', 'En Proceso', 1, 15, 2, '2026-01-02 10:14:01', '2026-01-02 10:14:21', 19, 110, 'Monitor (S/N: NB2025001)', 'pedido_1767359641_6957c499a0591.pdf'),
(40, 'Reparación', 'adfasdfaf', 'asdfasdf', 'dfasdfas', NULL, NULL, 'Media', 'En Proceso', 1, 33, 8, '2026-01-02 10:27:10', '2026-01-02 10:27:32', 19, 128, 'Notebook - sdfaf (S/N: 232wef)', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedidos_adjuntos`
--

CREATE TABLE `pedidos_adjuntos` (
  `id_adjunto` int(11) NOT NULL,
  `id_pedido` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `nombre_archivo` varchar(255) NOT NULL,
  `ruta_archivo` varchar(255) NOT NULL,
  `fecha_carga` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedidos_historial`
--

CREATE TABLE `pedidos_historial` (
  `id_historial` int(11) NOT NULL,
  `id_pedido` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `accion` varchar(50) NOT NULL,
  `detalle` text DEFAULT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `pedidos_historial`
--

INSERT INTO `pedidos_historial` (`id_historial`, `id_pedido`, `id_usuario`, `accion`, `detalle`, `fecha`) VALUES
(78, 30, 1, 'Creación', 'Pedido de agente externo creado', '2026-01-02 08:45:00'),
(79, 30, 1, 'Asignación', 'Asignado manualmente por admin a jvillaverde', '2026-01-02 08:46:52'),
(80, 30, 20, 'Informe generado', 'Informe generado. Resultado: Solucionado', '2026-01-02 08:48:45'),
(81, 31, 1, 'Creación', 'Pedido de agente externo creado', '2026-01-02 08:53:02'),
(82, 31, 1, 'Asignación', 'Asignado manualmente por admin a admin', '2026-01-02 09:04:37'),
(83, 32, 1, 'Creación', 'Pedido de agente externo creado', '2026-01-02 09:10:18'),
(84, 32, 1, 'Asignación', 'Asignado manualmente por admin a adminis', '2026-01-02 09:13:06'),
(85, 33, 1, 'Creación', 'Pedido de agente externo creado', '2026-01-02 09:40:30'),
(86, 34, 1, 'Creación', 'Pedido de agente externo creado', '2026-01-02 09:41:02'),
(87, 35, 1, 'Creación', 'Pedido de agente externo creado', '2026-01-02 09:41:19'),
(88, 35, 1, 'Asignación', 'Asignado manualmente por admin a adminis', '2026-01-02 09:44:36'),
(89, 34, 1, 'Asignación', 'Asignado manualmente por admin a adminis', '2026-01-02 09:48:04'),
(90, 33, 1, 'Asignación', 'Asignado manualmente por admin a adminis', '2026-01-02 09:55:10'),
(91, 36, 1, 'Creación', 'Pedido de agente externo creado', '2026-01-02 09:59:32'),
(92, 36, 1, 'Asignación', 'Asignado manualmente por admin a opera', '2026-01-02 10:02:50'),
(93, 37, 1, 'Creación', 'Pedido de agente externo creado', '2026-01-02 10:03:12'),
(94, 37, 1, 'Asignación', 'Asignado manualmente por admin a admin', '2026-01-02 10:03:37'),
(95, 38, 1, 'Creación', 'Pedido de agente externo creado', '2026-01-02 10:13:24'),
(96, 39, 1, 'Creación', 'Pedido de agente externo creado', '2026-01-02 10:14:01'),
(97, 39, 1, 'Asignación', 'Asignado manualmente por admin a opop', '2026-01-02 10:14:21'),
(98, 38, 1, 'Asignación', 'Asignado manualmente por admin a opera', '2026-01-02 10:21:59'),
(99, 40, 1, 'Creación', 'Pedido de agente externo creado', '2026-01-02 10:27:10'),
(100, 40, 1, 'Asignación', 'Asignado manualmente por admin a opop', '2026-01-02 10:27:32'),
(101, 37, 1, 'Rechazado', 'Motivo: no quiero hacerlo', '2026-01-02 10:32:22');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedidos_informes`
--

CREATE TABLE `pedidos_informes` (
  `id_informe` int(11) NOT NULL,
  `id_pedido` int(11) NOT NULL,
  `diagnostico` text DEFAULT NULL,
  `trabajo_realizado` text DEFAULT NULL,
  `resultado` enum('Solucionado','Sin Solución','Requiere Repuestos') NOT NULL,
  `fecha_informe` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `pedidos_informes`
--

INSERT INTO `pedidos_informes` (`id_informe`, `id_pedido`, `diagnostico`, `trabajo_realizado`, `resultado`, `fecha_informe`) VALUES
(13, 30, 'no encendia', 'la repare', 'Solucionado', '2026-01-02 08:48:45');

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
(58, '0045_2025', 27, 5, 'asdasda', 'sdfadf', '2025-10-24', 'Devuelta', '2025-10-24', NULL, NULL, NULL),
(60, '0047_2025', 51, 9, 'diego', 'garcia', '2025-10-24', 'Anulado', NULL, NULL, 'asda', '2025-11-25 13:01:11'),
(61, '0048_2025', 38, 2, 'joaquin', 'villaverde', '2025-10-24', 'Anulado', '2025-10-24', NULL, 'asdasd', '2025-12-02 08:50:09'),
(62, '0001_2025', 44, 6, 'cintia', 'cuassolo', '2025-10-24', 'Anulado', NULL, NULL, 'Cambio de area', '2025-10-28 08:21:30'),
(64, '0050_2025', 38, 2, 'Probando', 'borrar', '2025-10-31', 'Anulado', NULL, NULL, 'Renuncio', '2025-10-31 11:47:33'),
(65, '0051_2025', 51, 11, 'qwer', 'qwer', '2025-11-04', 'Anulado', NULL, NULL, 'adsa', '2025-11-20 09:12:56'),
(68, 'HIST_20251125094013_435', 38, 2, 'probando hist', 'hist', '2025-11-25', 'Anulado', NULL, NULL, 'asdasd', '2025-11-25 13:01:07'),
(69, '0053_2025', 51, 5, 'apskgdj', 'asdf', '2025-11-25', 'Anulado', NULL, NULL, 'skadf', '2025-12-01 10:02:41'),
(70, '0054_2025', 38, 2, 'roberto', 'kaka', '2025-12-02', 'Activa', NULL, NULL, NULL, NULL),
(72, '112_2025_hist', 38, 2, 'histo', 'rico', '2025-12-10', 'Activa', NULL, NULL, NULL, NULL),
(73, '1_2025_hist', 51, 9, 'Genaro', 'Rodriguez', '2025-12-10', 'Activa', NULL, NULL, NULL, NULL),
(74, '2_2025_hist', 51, 5, 'pkmplm', 'ĺop,', '2025-12-10', 'Activa', NULL, NULL, NULL, NULL),
(75, '3_2025_hist', 51, 9, 'sdfasdf', 'asdfasdf', '2025-12-10', 'Activa', NULL, NULL, NULL, NULL),
(76, '0055_2025', 51, 5, 'ñkjlk', 'fsghsg', '2025-12-11', 'Activa', NULL, NULL, NULL, NULL),
(77, '0056_2025', 27, 11, 'dgd', 'dfgd', '2025-12-12', 'Activa', NULL, NULL, NULL, NULL),
(78, '0057_2025', 27, 5, 'asfasdfa', 'asdfasdfa', '2025-12-17', 'Activa', NULL, NULL, NULL, NULL),
(79, '4_2025_hist', 39, 10, 'juan', 'encargado', '2025-12-22', 'Activa', NULL, NULL, NULL, NULL),
(80, '5_2025_hist', 27, 6, 'asdasdasd', 'asdasdasd', '2025-12-22', 'Activa', NULL, NULL, NULL, NULL),
(81, '6_2025_hist', 50, 11, 'juan', 'sdfs', '2025-12-23', 'Activa', NULL, NULL, NULL, NULL),
(82, '7_2025_hist', 50, 11, 'asdasdasd', 'asdasdasda', '2025-12-26', 'Activa', NULL, NULL, NULL, NULL),
(83, '8_2025_hist', 27, 6, 'asda', 'asdasd', '2025-12-26', 'Activa', NULL, NULL, NULL, NULL),
(84, '9_2025_hist', 51, 11, 'testo', 'testa', '2025-12-26', 'Activa', NULL, NULL, NULL, NULL),
(85, '10_2025_hist', 38, 2, 'Test', 'User', '2025-12-26', 'Activa', NULL, NULL, NULL, NULL),
(86, '11_2025_hist', 38, 11, 'asdasd', 'asdasd', '2025-12-26', 'Anulado', NULL, NULL, 'asdasd', '2026-01-02 10:57:33'),
(87, '12_2025_hist', 51, 9, 'asdasdasdasd', 'asdasdasd', '2025-12-26', 'Activa', NULL, NULL, NULL, NULL),
(88, '13_2025_hist', 43, 6, 'asdasdasda', 'asdasdasdad', '2025-12-26', 'Activa', NULL, NULL, NULL, NULL),
(89, '0001_2026', 38, 2, 'año', 'nuevo', '2026-01-02', 'Activa', NULL, NULL, NULL, NULL),
(90, '1_2026_hist', 51, 5, 'qsdfasdf', 'asdfasdf', '2026-01-02', 'Anulado', NULL, NULL, 'sdasdf', '2026-01-02 11:01:07'),
(91, '0002_2026', 38, 9, 'sadfas', 'asdfa', '2026-01-02', 'Activa', NULL, NULL, NULL, NULL),
(92, '0003_2026', 32, 6, 'sdfsdf', 'sdfsdf', '2026-01-02', 'Activa', NULL, NULL, NULL, NULL),
(93, '0004_2026', 2, 1, 'Erika V.', 'Gimenez', '2026-01-07', 'Activa', NULL, 'PC COMPLETA PORQUE LA QUEMO CON MATE', NULL, NULL),
(94, '0005_2026', 2, 1, 'Erika V.', 'Gimenez', '2026-01-07', 'Activa', NULL, 'Me olvide', NULL, NULL);

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
(124, 69, 100, 1, 0),
(125, 70, 61, 1, 1),
(126, 70, 23, 1, 0),
(128, 72, 112, 1, 0),
(129, 73, 113, 1, 0),
(130, 74, 114, 1, 0),
(131, 75, 115, 1, 0),
(132, 76, 61, 1, 0),
(133, 76, 47, 1, 0),
(134, 76, 27, 1, 0),
(135, 77, 95, 1, 0),
(136, 77, 90, 1, 0),
(137, 78, 105, 1, 0),
(138, 78, 108, 1, 0),
(139, 79, 117, 1, 0),
(140, 80, 118, 1, 0),
(141, 81, 119, 1, 0),
(142, 82, 120, 1, 0),
(143, 83, 121, 3, 0),
(144, 84, 122, 1, 0),
(145, 85, 123, 1, 0),
(146, 86, 124, 1, 0),
(147, 87, 125, 1, 0),
(148, 88, 126, 1, 0),
(149, 89, 109, 2, 0),
(150, 89, 110, 1, 0),
(151, 90, 128, 1, 0),
(152, 91, 129, 1, 0),
(153, 91, 101, 1, 0),
(154, 92, 92, 1, 0),
(155, 92, 29, 1, 0),
(156, 93, 131, 1, 0),
(157, 93, 133, 1, 0),
(158, 94, 109, 1, 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `remitos_historicos_secuencia`
--

CREATE TABLE `remitos_historicos_secuencia` (
  `anio` int(11) NOT NULL,
  `ultimo_numero` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `remitos_historicos_secuencia`
--

INSERT INTO `remitos_historicos_secuencia` (`anio`, `ultimo_numero`) VALUES
(2025, 13),
(2026, 1);

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
(2025, 57),
(2026, 5);

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
(1, 'Super Administrador', 'Acceso total al sistema incluyendo gestión de usuarios', '{\"insumos\": [\"ver\", \"crear\", \"editar\", \"eliminar\", \"baja\"], \"asignaciones\": [\"ver\", \"crear\", \"editar\", \"anular\", \"devolver\"], \"sedes\": [\"ver\", \"crear\", \"editar\", \"eliminar\"], \"telecom\": [\"ver\", \"crear\", \"editar\", \"eliminar\"], \"reportes\": [\"ver\", \"exportar\"], \"usuarios\": [\"ver\", \"crear\", \"editar\", \"eliminar\", \"cambiar_rol\", \"reset_password\"], \"auditoria\": [\"ver_todo\"], \"sistema\": [\"backup\"], \"areas\": [\"ver\", \"crear\", \"editar\", \"eliminar\"], \"pedidos\": [\"ver_propios\", \"crear\", \"ver_todos\", \"gestionar\", \"asignar\", \"informe\", \"eliminar\"]}', '2025-11-06 09:08:29'),
(2, 'Administrador', 'Gestión completa de inventario sin acceso a usuarios', '{\"insumos\": [\"ver\", \"crear\", \"editar\", \"eliminar\", \"baja\"], \"asignaciones\": [\"ver\", \"crear\", \"editar\", \"anular\", \"devolver\"], \"sedes\": [\"ver\", \"crear\", \"editar\", \"eliminar\"], \"telecom\": [\"ver\", \"crear\", \"editar\", \"eliminar\"], \"reportes\": [\"ver\", \"exportar\"], \"usuarios\": [\"ver\"], \"areas\": [\"ver\", \"crear\", \"editar\", \"eliminar\"], \"pedidos\": [\"ver_propios\", \"crear\", \"ver_todos\", \"gestionar\", \"asignar\", \"informe\", \"eliminar\"]}', '2025-11-06 09:08:29'),
(3, 'Operador', 'Operaciones diarias de inventario y asignaciones', '{\"insumos\": [\"ver\", \"crear\", \"editar\", \"baja\"], \"asignaciones\": [\"ver\", \"crear\", \"editar\", \"devolver\"], \"sedes\": [\"ver\", \"crear\"], \"telecom\": [\"ver\", \"crear\"], \"reportes\": [\"ver\", \"exportar\"], \"areas\": [\"ver\", \"crear\"], \"pedidos\": [\"ver_propios\", \"crear\", \"ver_todos\", \"gestionar\", \"informe\"]}', '2025-11-06 09:08:29'),
(4, 'Consultor', 'Solo lectura y generación de reportes', '{\"insumos\": [\"ver\"], \"asignaciones\": [\"ver\", \"crear\"], \"sedes\": [\"ver\"], \"telecom\": [\"ver\"], \"reportes\": [\"ver\"], \"areas\": [\"ver\"], \"pedidos\": [\"ver_propios\", \"ver_todos\"]}', '2025-11-06 09:08:29');

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
(33, 1, '94f4a497750d95b8706942e464cc1156abf6af85390e1afc891ad13749983cd0', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-11-26 08:55:21', '2025-11-26 10:49:49', '2025-11-26 10:49:49', 0),
(34, 1, 'f5f9bb0de05196de236bda87214d85dba5471a27fbbd91466583b3429451be26', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-11-26 11:46:12', '2025-12-01 08:51:11', '2025-12-01 08:51:11', 0),
(35, 1, 'bc459e4274cd99563682f5c7eb5eaee7e24ef1295a13d46b7f6460f7e0d4f1d5', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-12-01 08:51:11', '2025-12-01 09:52:46', '2025-12-01 09:52:46', 0),
(36, 1, '26d52d482ba133aca0ae8f2c225bc714cdaca9350079d5ec0fd3febc4bd08ccc', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-12-01 09:52:58', '2025-12-01 10:02:13', '2025-12-01 10:02:13', 0),
(38, 1, '4e9cb0216ac3f98cab091fc0953bdca5438b172fef09e2786b4a0fcb381b9dad', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-12-01 10:43:49', '2025-12-01 11:17:01', '2025-12-01 11:17:01', 0),
(39, 1, 'f330fb75060950f2f3be2b76e02f1f3b137296e9e0adf11fced6e8d8c26e1898', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-12-01 11:17:06', '2025-12-01 14:03:30', '2025-12-01 14:03:30', 0),
(40, 1, '613ac52913289f61aedd63bb55ea1bf64fc8d8c6e759cf5a30c0208b6302eaf7', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-12-01 14:03:37', '2025-12-02 08:46:29', '2025-12-02 08:46:29', 0),
(41, 1, '9e50d91354d0d11d2ff3734da163f63a26c2c4c0382704d82b08f78b466773fd', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-12-02 08:46:30', '2025-12-02 08:56:37', '2025-12-02 08:56:37', 0),
(43, 1, '61d924872ea3154c7d9bc8fa4e8a0b65706cc551c870b8f19f97024cd09eaa0b', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-12-02 09:10:03', '2025-12-02 09:30:49', '2025-12-02 09:30:49', 0),
(44, 1, '33916bed60291af52d881d11670d07d2343eef0602496152f70cf64a9207b013', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-12-02 09:30:55', '2025-12-02 09:31:18', '2025-12-02 09:31:18', 0),
(48, 1, 'ab23a4b9ca8f881e71f3de6d4111b0983eef6f949dd62dc329fc4e56086d963f', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-12-02 09:45:39', '2025-12-02 09:46:07', '2025-12-02 09:46:07', 0),
(50, 1, '9db4a6e72b159aaec02ba465c97f37a8ea9811480aa163ed794680a569255ddb', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-12-02 09:49:58', '2025-12-02 09:57:26', '2025-12-02 09:57:26', 0),
(51, 1, 'dfc0acf3707e44212d1942b0bdf108dc10d05abda5b99a2807a368f0d5765c61', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-12-02 09:57:31', '2025-12-02 09:58:44', '2025-12-02 09:58:44', 0),
(53, 1, '2565dc1a3ab2b1d3b3c8a9dfe4c47142fbd1782c8cc06604242cfbbfd2f77c61', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-12-02 10:00:54', '2025-12-02 10:01:13', '2025-12-02 10:01:13', 0),
(55, 1, '501c4f43232d1dec500fdecabb9a485b5298a1cdd7b5f7e134d5d4e50b6cf248', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-12-02 10:10:51', '2025-12-02 10:10:57', '2025-12-02 10:10:57', 0),
(56, 1, '911f511d4a36fc56abe79e5e614d53743958c13a76259b3b1173010a344c17bf', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-12-02 10:11:05', '2025-12-02 10:11:13', '2025-12-02 10:11:13', 0),
(62, 1, '4a4c7d7d174bfcf70ffd3bb7b8583ff53da44db38ed065e8c56a7618465368d6', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-12-03 08:43:25', '2025-12-03 08:44:17', '2025-12-03 08:44:17', 0),
(63, 1, '12523516e7aa43ac5c64b6a07d3c19ff9b1d86dd4c26e5c785ba4eeb481e47e9', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-12-03 08:44:46', '2025-12-03 08:44:57', '2025-12-03 08:44:57', 0),
(66, 1, '4f57c78366386a90f7aea1dba1910026a0f2562fa3f667816f809b4a6d8ecbd1', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 10:41:40', '2025-12-03 11:02:53', '2025-12-03 11:02:53', 0),
(67, 1, 'c19f28bc59fa829423c2cd31d6980f42a2f9ff0f21117e851b3c04bfc9bc6d70', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-12-03 11:02:53', '2025-12-03 12:30:16', '2025-12-03 12:30:16', 0),
(68, 1, 'a61b87caddb96dce2b78bbee5c4a0c1611d3ebfd1d3206c0552c349df77f4543', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-12-03 13:40:54', '2025-12-04 09:44:03', '2025-12-04 09:44:03', 0),
(69, 1, '430a1388ac00472b4c92d4d584629bc303e7b6074b71ca00b67df1954241372a', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-12-04 09:44:03', '2025-12-04 09:44:24', '2025-12-04 09:44:24', 0),
(70, 1, 'dfb75d14cf3c7de934ab5b6e336dcd6e6085376cef629c9de615da230ae19b47', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-12-04 09:44:30', '2025-12-04 09:51:10', '2025-12-04 09:51:10', 0),
(72, 1, '5a7c275177407ebd1ef1a20579727b7ec37ae98f2bd3c88e31a8160e88f76b7a', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-12-04 10:08:33', '2025-12-04 13:44:28', '2025-12-04 13:44:28', 0),
(73, 1, 'c7fc5798ebebdb912bf4a62e14476256b14e5ed60073bc4e032c5ea8c24fa437', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-12-04 13:44:34', '2025-12-05 10:03:36', '2025-12-05 10:03:36', 0),
(74, 1, '01d04ef6c72f53012dfe1a13fb1a800237460b9cc21b45e5d398c97d1bad4171', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-12-05 10:03:36', '2025-12-05 12:09:02', '2025-12-05 12:09:02', 0),
(75, 1, 'f8a4f4314634eb348c55795bf3af4da3a6e313056163db231d534efb425ca31c', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-12-05 12:09:06', '2025-12-05 13:01:34', '2025-12-05 13:01:34', 0),
(76, 1, 'e10026bb54c7189f0d61670362c482f6ba7c8e684335663231e48210df2ddb84', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-12-05 13:01:40', '2025-12-05 13:51:48', '2025-12-05 13:24:52', 0),
(77, 1, '82f298db2cdf178ee6dd6f5016c3b24255028bb4e23c0916c6d1f77c01c32408', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-12-05 13:24:52', '2025-12-09 08:56:52', '2025-12-09 08:56:52', 0),
(78, 1, '10b635bf60a8f13f61fec9f1f5c1cbed10c300792bef3bbf9b03c94895e66d64', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-12-09 08:56:52', '2025-12-09 09:50:28', '2025-12-09 09:50:28', 0),
(80, 1, 'd88bbbde99234dea003eb2dc1711e43abf8c81aadc1b8a2d99d4ab8e82015180', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-12-09 10:02:15', '2025-12-09 10:16:55', '2025-12-09 10:16:55', 0),
(82, 1, '1b1c313adb560adbf10d374bfc1dc6244f240a59f092f0750e03dda6ac4a34a4', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-12-09 10:17:24', '2025-12-09 10:18:26', '2025-12-09 10:18:26', 0),
(83, 1, '00365ef3e4a2e78388d321fea8df3dfbc6b1e84bf3e1cc0133454868ce557469', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-12-09 10:18:30', '2025-12-09 11:19:40', '2025-12-09 11:19:40', 0),
(84, 1, 'acf8b805e38a19e1eef51cfd536e27851158b51a1abaab8cab6ce04ccee93c52', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-12-09 10:30:31', '2025-12-09 10:32:17', '2025-12-09 10:32:17', 0),
(87, 1, '50eb604bf4d38a591c5c61acf81e372a04ee7bf9d1e87226dcb8601eb1c4396b', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-09 10:40:28', '2025-12-09 11:57:07', '2025-12-09 11:57:07', 0),
(88, 1, 'db662cd918a946701344294ed893d39f1385b38e50870d65fdfa73f94cfabf8d', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-12-09 11:19:48', '2025-12-09 12:01:42', '2025-12-09 12:01:42', 0),
(89, 1, 'e00e2eea83c4997d2f5ca5f3b75309402060e26027228728ca817cb333d2c38c', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-12-09 12:01:42', '2025-12-09 12:03:02', '2025-12-09 12:03:02', 0),
(91, 1, 'd4a21f012f4977880c18cc1e41cc803b31c585c8e7696b37dd52f91b38205f04', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-09 12:07:29', '2025-12-09 12:13:51', '2025-12-09 12:13:51', 0),
(92, 1, '1751be064c851630bcf2ce2213408c7e18db765827a99d5fc77092af5695befe', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-09 12:13:57', '2025-12-09 12:19:32', '2025-12-09 12:19:32', 0),
(93, 1, '80b26c74f5ad9ac92258af4fdb4cc3330234c42fc05cee442a606d28aea9523c', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-09 12:19:32', '2025-12-09 12:21:10', '2025-12-09 12:21:10', 0),
(95, 1, 'ffd1894eaa7ec3ae5d030877acfd4c2383648bef6503c759bab3d4ac12a42c97', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-09 12:25:28', '2025-12-09 12:34:32', '2025-12-09 12:34:32', 0),
(96, 1, '89214fde57b13f6659553084726ebd4fc7106ec81ca33511532b244143d302b6', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-09 12:34:32', '2025-12-09 12:42:46', '2025-12-09 12:42:46', 0),
(98, 1, '1a3060b3feb2a349391b16d3848cb36a9505ab704d207091e05d76e11400f248', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-09 13:09:47', '2025-12-10 09:37:25', '2025-12-10 09:37:25', 0),
(99, 1, '115243ea58432018ea576be8f5e04b779a2b8aa30d15c82fdb7aabdfcb82bb9d', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-12-10 09:37:25', '2025-12-10 09:38:02', '2025-12-10 09:38:02', 0),
(101, 1, '9f6eded885e707538dd8a05cb319951ea8a9b62841b980c8c0e13d9f73e30451', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-12-10 10:21:35', '2025-12-10 11:48:44', '2025-12-10 11:48:44', 0),
(108, 1, '8d52d02385cd1fb741ce7fe165ad0b3d01a37f5fef2140a73a990e183effed0e', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-12-11 12:45:45', '2025-12-12 08:21:43', '2025-12-12 08:21:43', 0),
(109, 1, '88a7e84c3a89f09cddc9fb6f5d49279352b54a7f516dbe7a2dbb08d4f830b8e6', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-12-12 08:21:43', '2025-12-12 08:26:05', '2025-12-12 08:26:05', 0),
(110, 16, 'ef2fc70aad3ab6011623c6029862ed190e0daf456cfeca36fbc585948602aef1', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-12-12 08:26:13', '2025-12-12 08:27:34', '2025-12-12 08:27:34', 0),
(111, 17, 'f5f93c3f38d9122a0b54c6e612889b638e2977a1db5fece9a11ee9b6cf9fc8c0', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-12-12 08:27:41', '2025-12-12 08:29:03', '2025-12-12 08:29:03', 0),
(112, 15, '7661b2f503fee1c9d50af1f986327f4f2fcb8ec9550d7606163881c97e8470d8', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-12-12 08:29:12', '2025-12-12 08:29:39', '2025-12-12 08:29:39', 0),
(113, 18, '4783c6f7bc12db4ef76b0645241b32fd3a5b04b1201ab5769b687bccc5838ff5', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-12-12 08:29:47', '2025-12-12 08:30:41', '2025-12-12 08:30:41', 0),
(114, 17, 'cbc1906047465045442af33c5866bdc93826bed7b660ff34114bf1feb047d8be', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-12-12 08:30:46', '2025-12-12 08:32:31', '2025-12-12 08:32:31', 0),
(115, 1, 'ab315cff5dc6692020989b4c84e15425390d97fdb18d5ac3b67bf205451701b1', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-12-12 08:32:38', '2025-12-12 08:33:16', '2025-12-12 08:33:16', 0),
(116, 19, 'c5bfd4c8eaca9c92e9859736ac3d3e9dab11f5538f7633b9c8953d4d21ee536f', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-12-12 08:33:21', '2025-12-12 08:34:05', '2025-12-12 08:34:05', 0),
(117, 1, '11ee8f6bf7c1ae6bf151c56f6d32f1327bcea6c73cb69e7b5f07a4904d0c1e1b', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-12-12 08:34:09', '2025-12-15 11:13:29', '2025-12-15 11:13:29', 0),
(118, 1, 'ed829ca2e5bd8d3a6b98d1e918e5df54a6b4981f968cd24333fd46ba1ab5f54f', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-12-15 11:13:29', '2025-12-15 11:36:01', '2025-12-15 11:36:01', 0),
(119, 1, '1ba4d08e5acc0873b99a7c6fa6e8a161a8df7e07925cdf3589db4bd0c4594fe3', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-12-15 11:36:43', '2025-12-15 11:37:19', '2025-12-15 11:37:19', 0),
(120, 20, '54209ad51fbc3bc448b92e35572cc4a73e720d1e0566ea81f0e41f2c3409c09b', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-12-15 11:37:26', '2025-12-15 11:39:38', '2025-12-15 11:39:38', 0),
(121, 1, 'aa5b13a78c25267c5b662a196fdd908d117d92d938131c045f377ae23bb1706d', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-12-15 11:39:45', '2025-12-15 12:22:20', '2025-12-15 12:22:20', 0),
(122, 1, '169c6dcbcabb0beef5d84f56ceabd0008c15a7306d2a173dffbe580c1854dfa0', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-12-15 12:20:13', '2025-12-15 12:43:19', '2025-12-15 12:43:19', 0),
(123, 15, '34da87580057991bef4ff9bf3476a42bb80ea63276de6fcc51c7914cd216f253', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-12-15 12:22:27', '2025-12-15 12:22:57', '2025-12-15 12:22:57', 0),
(124, 20, '78adb6b54a3ca00d75b8126b6a48f54c55fd7f46035f782febf2687cce07d84d', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-12-15 12:23:07', '2025-12-15 12:43:14', '2025-12-15 12:43:14', 0),
(125, 1, '168a389b74c3b5d3593f297939fa1ff7b70fe3642c8fd8de6d6deb2ce71720ab', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-12-15 12:43:19', '2025-12-16 10:15:44', '2025-12-16 10:15:44', 0),
(126, 20, 'b15b5e3cf0c4bbce592190fce554af1b32f456351de91be4b99df57a72beb569', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-12-16 08:14:52', '2025-12-16 09:03:20', '2025-12-16 09:03:20', 0),
(127, 15, 'a53b89ad81fb9210be0131ad645c296238d13502f25dd3007d4df53dbb13b6de', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-12-16 09:03:39', '2025-12-16 09:33:04', '2025-12-16 09:33:04', 0),
(128, 20, '68da295048939080aaec37bebe53fc5db883790e142e4e1a2fe991673dec6179', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-12-16 09:33:12', '2025-12-16 10:03:07', '2025-12-16 10:03:07', 0),
(129, 20, 'eb313d17a418aa5e98508e942512440741f1a364aa89697a4b35429d4a847aa5', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-12-16 10:03:07', '2025-12-16 10:15:38', '2025-12-16 10:15:38', 0),
(130, 1, '6c823d04ffcf0598bf904e044269822c4416bc4e17ccecd26d966d9693e51477', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-12-16 10:15:44', '2025-12-16 11:14:44', '2025-12-16 11:14:44', 0),
(131, 20, '34f3974f4126e58e74adbce1c9de4c16532e76fa647c69c29219c30567324655', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-16 10:23:00', '2025-12-16 11:10:39', '2025-12-16 11:00:07', 0),
(132, 20, '243035b066b9241b473e2893c5a51d6bef68e80efd66eba9a6e53b0ea786f480', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-12-16 11:00:08', '2025-12-16 11:13:15', '2025-12-16 11:13:15', 0),
(133, 20, 'fd32ef17f1dc8c7104970ad4a5e82cd31935a6e671f54eb9ea7198e9398ce533', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-12-16 11:13:27', '2025-12-16 11:14:40', '2025-12-16 11:14:40', 0),
(134, 1, 'ee0fd425fda8f20ae3c036cca32968594be38c6d42a32a7ef43585c1c1814eea', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-12-16 11:14:44', '2025-12-16 12:48:57', '2025-12-16 12:48:57', 0),
(135, 20, '530d384764d29666ab92004f9a8db82921db81cb0476b679ee1fcea41e98396b', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-12-16 11:23:40', '2025-12-16 11:54:31', '2025-12-16 11:54:31', 0),
(136, 20, 'aff22754d0514ebb68171cbef6a90a2bf0cf97a26572cb9ec72bd9cee3fab21c', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-12-16 11:54:31', '2025-12-16 11:58:04', '2025-12-16 11:58:04', 0),
(137, 20, '42a67bdd1472c0b4da0642205c14e2267cd110a763f3aef9d25838e8629f3456', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-12-16 11:58:29', '2025-12-16 12:10:21', '2025-12-16 12:10:21', 0),
(138, 20, '2bd61679bec98b5c842a428f79e6ce6ebe8a92ec745cfca49e1377818d312623', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-12-16 12:10:21', '2025-12-16 12:40:37', '2025-12-16 12:40:37', 0),
(139, 20, 'eae1674998e40f5f0667b645fe5f4755d930a9f95ee22788f0e43928c1d2aec8', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-12-16 12:40:37', '2025-12-16 12:48:50', '2025-12-16 12:48:50', 0),
(140, 1, '4aa2203fe04e0c41bbbfb399fe2e880917903c34f6da91b084ab1cde2175e649', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-12-16 12:48:57', '2025-12-16 13:20:41', '2025-12-16 13:20:41', 0),
(141, 20, 'e649132c4436685b6114b9dc68edbb4756b271a59743d47f37d5d65074e4f79c', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-16 12:51:58', '2025-12-17 10:08:14', '2025-12-17 10:08:14', 0),
(142, 20, 'd7b25ffbbe23a1b336b194e6dde7d2d71093c36b6bab0c4b8a289fcaf0340d84', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-12-16 13:17:12', '2025-12-16 13:49:31', '2025-12-16 13:49:31', 0),
(143, 1, '3729586e6bf6710e4c7cd7a951c828799a6f2638c36467370ffc510c77fd20c5', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-12-16 13:20:41', '2025-12-16 13:57:18', '2025-12-16 13:57:18', 0),
(144, 20, '4d3d2e4516f3fe4d3749411789f2cdec13d3da8a77ec8e8591835542c706d786', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-12-16 13:49:31', '2025-12-17 12:10:34', '2025-12-17 12:10:34', 0),
(145, 1, 'ecedc796304279d5f4fbc987f2dbb965d765f9fc9c4856d17e2915a9541b77f6', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-12-16 13:57:18', '2025-12-17 10:08:20', '2025-12-17 10:08:20', 0),
(146, 1, '580db841d213754cce57e668e9b722579ffe6ffb13b4c0b6d99079b7a2dd6298', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-17 10:08:20', '2025-12-17 12:10:25', '2025-12-17 12:10:25', 0),
(147, 1, '17a48c9f95361e52a94735ef4c28a0bf8b24ce3c53be7192d91859280f9d13d6', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-17 10:27:37', '2025-12-17 12:23:55', '2025-12-17 12:23:55', 0),
(148, 20, 'c7eced78062e0cf6ffbedf4479a120730e4b515ee92155fa8660eb0756e2fe74', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-17 12:10:34', '2025-12-17 12:23:50', '2025-12-17 12:23:50', 0),
(149, 1, 'f96150926181e3b1bf2b56f4f0359b75ee8edd481fef51fc91b94350c085899a', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-17 12:23:55', '2025-12-22 11:43:20', '2025-12-22 11:43:20', 0),
(150, 1, '052d441dbfa30ae39e167b2ba679f81315d30cdb9aa28e72c0e86b8924f80560', '10.114.85.131', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-17 12:46:54', '2025-12-19 10:25:02', '2025-12-19 10:25:02', 0),
(151, 1, '240be013aba6ead69c813d9b7212bedd1e11423885538b329502884421ffc6e4', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-12-19 10:25:02', '2025-12-22 11:43:30', '2025-12-22 11:43:30', 0),
(152, 1, '6ddc3fb5ec72655c575505e833d0f69aab702c57b59ef275642fe4ed1d8f0a1c', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-22 11:43:31', '2025-12-26 09:01:22', '2025-12-26 09:01:22', 0),
(153, 1, 'cba94ed63085721e45ded2042e8244dfc37b887e15204266839fc3cf1ce46056', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-22 11:53:22', '2025-12-22 14:01:03', '2025-12-22 13:46:41', 0),
(154, 1, 'f413a6116c8d7a2581cd327bda6bad27c30e70d653030731b774170c1faa3823', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-12-22 13:46:41', '2025-12-22 13:50:36', '2025-12-22 13:50:36', 0),
(155, 1, '6b9447d7a67b0a2986998c40e6a1c13515804d33b2aaf3a098975bfab75e75e5', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-12-22 13:50:36', '2025-12-23 08:19:36', '2025-12-23 08:19:36', 0),
(156, 1, 'a3cff700398241f33c30f57e09e868320ea786a82ac2416d22aea5bc90ae18b4', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-23 08:19:36', '2025-12-23 10:19:16', '2025-12-23 10:19:16', 0),
(157, 1, '8a7a66d350bab5de10ba9da1ec146bbd5295a0f4ee345f1ddab8fb9e0decd2c6', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-12-23 10:19:16', '2025-12-23 10:45:21', '2025-12-23 10:43:38', 0),
(158, 1, 'a44725ee271795d491cc9988b44504b68066c5af6d1890bcaa93aa4bfa6569b7', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-23 10:43:39', '2025-12-23 11:51:04', '2025-12-23 11:51:04', 0),
(159, 1, '2a5a01660b231edf8562ac40f54a34f8e256ccb913ca150f59354ffcfbf82e45', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-23 11:52:23', '2025-12-23 13:02:46', '2025-12-23 13:02:46', 0),
(160, 1, 'f36ba31b64ce268023b0d71e8f6259d09defffd2b7f3379de62d31e40cbec4c2', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-23 13:02:50', '2025-12-23 13:50:44', '2025-12-23 13:50:44', 0),
(161, 1, 'ec2b17b07214767196110e5109fa97c13e36dc0ace6d531689391ef89a49fa82', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-23 13:50:55', '2025-12-26 09:12:19', '2025-12-26 09:12:19', 0),
(162, 1, 'a73ccb279dc0edf9dc73377f5c13a321983378a70a84d62b11e488ef88d9cceb', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-26 09:12:19', '2025-12-26 11:39:54', '2025-12-26 11:39:54', 0),
(163, 1, '9a1f6a0d7e29f4cc83f50af6016ee4ae2fc9744a99cd5b336aed21d9124a4652', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-26 09:25:12', '2025-12-26 09:31:38', '2025-12-26 09:31:38', 0),
(164, 1, '933655e65ff39f767fbbacfea852e2b8b166af6db405796774bbf926cc306d1c', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-26 09:31:38', '2025-12-26 10:18:25', '2025-12-26 10:18:25', 0),
(165, 1, '9e3fd810c474f7d211cf696c0d2d362c15b174f86a8392fac81a5821209d869e', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-26 10:18:25', '2025-12-26 10:21:47', '2025-12-26 10:21:47', 0),
(166, 1, '1f7e344feaa5b3c5cf4872858aea5479571dbf7bf0c30ca5ffd24a23fde39839', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-26 10:21:47', '2025-12-26 13:05:10', '2025-12-26 13:05:10', 0),
(167, 1, 'b1b64672e9fc7ccce7bc68ede21646092fb4789af4d899f0dd2e36a30b237a75', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-26 11:39:59', '2026-01-02 10:02:35', '2026-01-02 10:02:35', 0),
(168, 1, 'ce847bd982194a1872aba1bc2163af5625e18e5f10d4d2d4da035b880b127910', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-26 12:01:23', '2026-01-02 08:14:07', '2026-01-02 08:14:07', 0),
(169, 1, 'b35699afb90a181ee78c1866e2a3b7bb022b6d35c9d75c155477e0bb20419abf', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2026-01-02 08:14:07', '2026-01-02 08:58:27', '2026-01-02 08:58:27', 0),
(170, 20, 'f92c3451d1d0a1b7ce41f78f3540d75dbcdf07176565a4ef56872c73fb096e92', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2026-01-02 08:48:03', '2026-01-02 08:48:04', NULL, 1),
(171, 1, '05f0d8f3c71fa145e32ee7d5e2dcbcb47146bce4b7f45ff60a79e32e83cdef99', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 08:58:27', '2026-01-02 09:59:10', '2026-01-02 09:41:47', 0),
(172, 1, '2d3f83bf047df17531ea1ed347a5a0d275c4a636583282f0be4c3de468ef2034', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 09:41:47', '2026-01-02 10:02:39', '2026-01-02 10:02:39', 0),
(173, 1, '712756d2132aef10dc66b6bb31c2eb624b0277404fa948fe3d64adcad4d05830', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 10:02:39', '2026-01-02 12:16:32', '2026-01-02 12:16:32', 0),
(174, 1, '346a1cef8a305ddffc5b5f4bf45f93155c81a2e03a101161ad5610f05be39059', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 12:16:37', '2026-01-02 13:23:17', '2026-01-02 13:23:17', 0),
(175, 1, 'c904163d1a4221fc3a35dfac9efa6d5041b9d6219f2377f8a65659abcc728e38', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 13:23:24', '2026-01-05 12:12:56', '2026-01-05 12:12:56', 0),
(176, 1, '793013896bce2a5754878bc43ff0b6753020f795563f3484f99a3d00da95e75e', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-05 12:13:01', '2026-01-05 13:05:47', '2026-01-05 13:05:47', 0),
(177, 1, '2ef8c85e6ecd236c6bde7fe734fc8be7242fbbf6f625dcf840def63782095eb2', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-05 13:05:56', '2026-01-06 13:45:43', '2026-01-06 13:45:43', 0),
(178, 1, 'e085ff9ab1665896ecaaee8a05ec65c4900c672482aee42a4d6ab1054386aea0', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2026-01-06 13:45:43', '2026-01-06 13:54:35', '2026-01-06 13:47:06', 0),
(179, 1, 'f5c22991009f25df864073378fffdaea70c285d9b27dadcb04eb3304998db041', '10.114.85.131', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-06 13:47:06', '2026-01-07 08:24:11', '2026-01-07 08:24:11', 0),
(180, 1, '9abe591068c899062704710a32e5afe7cf44d4d3ee501a091ba3cc455dec9835', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2026-01-07 08:24:11', '2026-01-07 10:28:21', '2026-01-07 10:28:21', 0),
(181, 1, 'a922c1d3ef568a11d9554861a1b0facbcf05a79c1e7f6806fe6a392c12e6672e', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2026-01-07 10:28:37', '2026-01-07 13:00:30', '2026-01-07 13:00:30', 0),
(182, 1, '405b21d3d1f12276e96c4fa6f076d7f45b4783372444cd28fd1a6fea4918b238', '10.114.85.131', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-07 11:03:07', '2026-01-07 13:00:35', '2026-01-07 13:00:35', 0),
(183, 1, '23dd00b972e55647d0e922060f9434583b54c490678fdc0e57476842138dc712', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2026-01-07 13:00:36', '2026-01-09 11:11:34', '2026-01-09 11:11:34', 0),
(184, 1, 'c46608829694650d0e63ed2b68b0ef421b65423dd822d5d3fef846491a9fa9df', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 11:11:34', '2026-01-09 13:37:00', '2026-01-09 13:37:00', 0),
(185, 1, '7b37250085e4377de768f72b470c0d814f618007b2771e2c3e9faedf8a9055ab', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2026-01-12 09:04:54', '2026-01-12 09:17:22', '2026-01-12 09:15:28', 0),
(186, 1, 'bbfa7e7f82df9cf7a780fdd548d1209dad1e0dc2bc202915432796c1d1858331', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 09:15:29', '2026-01-12 09:48:24', '2026-01-12 09:48:24', 0),
(187, 1, '4c038fe364a24cf45fa81f69ca41f987ac7daf316df278df347ecdc17eb900ff', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 09:48:25', '2026-01-12 10:12:33', '2026-01-12 10:12:33', 0),
(188, 1, 'a9b45600757a70eba6e774d3b731ed6a7a22b768318b759ad955e8367c7dc4a0', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 10:12:33', '2026-01-12 11:46:33', '2026-01-12 11:46:33', 0),
(189, 1, '0ee804f1af024f89d9ff58d448e3da00b29ee717c3d4419d40fbc50365642e1d', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 11:53:24', '2026-01-12 12:38:15', NULL, 1);

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
  `permisos_personalizados` longtext DEFAULT NULL COMMENT 'Permisos personalizados en formato JSON. Si es NULL, usa permisos del rol',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `ultimo_acceso` datetime DEFAULT NULL,
  `modificado_por` int(11) DEFAULT NULL COMMENT 'ID del usuario que realizó la última modificación',
  `fecha_modificacion` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Usuarios del sistema';

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id_usuario`, `username`, `email`, `password_hash`, `nombre`, `apellido`, `id_rol`, `permisos_personalizados`, `activo`, `fecha_creacion`, `ultimo_acceso`, `modificado_por`, `fecha_modificacion`) VALUES
(1, 'admin', 'admin@inventario.local', '$2y$10$acRUJEKWeTLjLv5WF6M1sOa1hjCAWdFJQbqeQDytS2aLvVbFypMvS', 'Administrador', 'Sistema', 1, NULL, 1, '2025-11-06 09:08:29', '2026-01-12 11:53:25', NULL, '2026-01-12 11:53:25'),
(15, 'adminis', 'adminis@gmail.com', '$2y$10$W5OZvHrCIMh9kXi4.uzLIOMAeP8YPg40sMBXGFOQ1AawtI5jRFdSe', 'admi', 'nitador', 2, NULL, 0, '2025-12-12 08:23:50', '2025-12-16 09:03:39', 1, '2026-01-02 11:02:43'),
(16, 'consul', 'consul@gmail.com', '$2y$10$aYRNl21Ip/TbS6CEiEBBuuWz52S1VEFhuN7sdhtTm/euBDTN9vOFW', 'consul', 'tor', 4, NULL, 1, '2025-12-12 08:25:03', '2025-12-12 08:26:13', 1, '2025-12-12 08:26:13'),
(17, 'opera', 'opera@gmail.com', '$2y$10$xpyal1nVCgcmgUAMSz2CDejflVA8UmITH3.SQle6k5Y2rAVSmFM2e', 'opera', 'opera', 3, '{\"insumos\":[\"ver\",\"crear\",\"editar\",\"eliminar\",\"baja\"],\"asignaciones\":[\"ver\",\"crear\",\"editar\",\"anular\",\"devolver\"],\"reportes\":[\"ver\",\"exportar\"],\"usuarios\":[\"ver\",\"crear\",\"editar\",\"eliminar\",\"cambiar_rol\",\"reset_password\"],\"auditoria\":[\"ver_todo\"],\"sedes\":[\"ver\",\"crear\",\"editar\",\"eliminar\"],\"sistema\":[\"backup\"],\"telecom\":[\"ver\",\"crear\",\"editar\",\"eliminar\"],\"areas\":[\"ver\",\"crear\",\"editar\",\"eliminar\"]}', 1, '2025-12-12 08:25:32', '2025-12-12 08:30:46', 18, '2025-12-12 08:30:46'),
(18, 'super', 'super@gmail.com', '$2y$10$PqAExH8UxKre9cpBxAFIpeOO.UBiEzoQxUosDPs0FKfIPsXgvTuQe', 'super', 'visor', 1, NULL, 1, '2025-12-12 08:25:58', '2025-12-12 08:29:47', 1, '2025-12-12 08:29:47'),
(19, 'opop', 'ospera@gmail.com', '$2y$10$tG65DDMyTKz4sjxLBaujGOwzhPX.x3Ds7xPd5j9Rq3z4oCD1pQxDK', 'opop', 'opop', 3, '{\"insumos\":[\"ver\",\"crear\",\"editar\",\"baja\"],\"asignaciones\":[\"ver\",\"crear\",\"editar\",\"devolver\"],\"reportes\":[\"ver\",\"exportar\"],\"sedes\":[\"ver\",\"crear\",\"editar\"],\"telecom\":[\"ver\",\"crear\",\"editar\"],\"areas\":[\"ver\",\"crear\",\"editar\"]}', 1, '2025-12-12 08:33:11', '2025-12-12 08:33:21', 1, '2025-12-17 11:35:19'),
(20, 'jvillaverde', 'jvillaverde@senaf.rionegro.gov.ar', '$2y$10$7XByW.wEFSz.5YPBpRI7aOtOUie5VAFXDo.mhdfO53sfJAkTfQZzy', 'joaquin', 'villaverde', 3, NULL, 1, '2025-12-15 11:37:17', '2026-01-02 08:48:03', 1, '2026-01-02 08:48:03');

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
-- Indices de la tabla `ingresos_documentos`
--
ALTER TABLE `ingresos_documentos`
  ADD PRIMARY KEY (`id_documento`),
  ADD KEY `cargado_por` (`cargado_por`),
  ADD KEY `idx_id_ingreso` (`id_ingreso`);

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
-- Indices de la tabla `notas_pedidos`
--
ALTER TABLE `notas_pedidos`
  ADD PRIMARY KEY (`id_nota`),
  ADD KEY `id_pedido` (`id_pedido`),
  ADD KEY `id_usuario` (`id_usuario`);

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
-- Indices de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD PRIMARY KEY (`id_pedido`),
  ADD KEY `idx_pedidos_usuario` (`id_usuario_solicitante`),
  ADD KEY `idx_pedidos_sede` (`id_sede`),
  ADD KEY `idx_pedidos_estado` (`estado`),
  ADD KEY `idx_pedidos_asignado` (`asignado_a`),
  ADD KEY `fk_pedidos_area` (`id_area`);

--
-- Indices de la tabla `pedidos_adjuntos`
--
ALTER TABLE `pedidos_adjuntos`
  ADD PRIMARY KEY (`id_adjunto`),
  ADD KEY `idx_adjuntos_pedido` (`id_pedido`),
  ADD KEY `fk_adjuntos_usuario` (`id_usuario`);

--
-- Indices de la tabla `pedidos_historial`
--
ALTER TABLE `pedidos_historial`
  ADD PRIMARY KEY (`id_historial`),
  ADD KEY `idx_historial_pedido` (`id_pedido`),
  ADD KEY `fk_historial_usuario` (`id_usuario`);

--
-- Indices de la tabla `pedidos_informes`
--
ALTER TABLE `pedidos_informes`
  ADD PRIMARY KEY (`id_informe`),
  ADD KEY `idx_informes_pedido` (`id_pedido`);

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
-- Indices de la tabla `remitos_historicos_secuencia`
--
ALTER TABLE `remitos_historicos_secuencia`
  ADD PRIMARY KEY (`anio`);

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
  MODIFY `id_area` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `auditoria_acciones`
--
ALTER TABLE `auditoria_acciones`
  MODIFY `id_auditoria` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=458;

--
-- AUTO_INCREMENT de la tabla `escaneres`
--
ALTER TABLE `escaneres`
  MODIFY `id_escaner` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `impresoras`
--
ALTER TABLE `impresoras`
  MODIFY `id_impresora` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de la tabla `ingresos`
--
ALTER TABLE `ingresos`
  MODIFY `id_ingreso` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT de la tabla `ingresos_documentos`
--
ALTER TABLE `ingresos_documentos`
  MODIFY `id_documento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de la tabla `insumos`
--
ALTER TABLE `insumos`
  MODIFY `id_insumo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=134;

--
-- AUTO_INCREMENT de la tabla `insumos_bajas`
--
ALTER TABLE `insumos_bajas`
  MODIFY `id_baja` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de la tabla `insumos_movimientos_stock`
--
ALTER TABLE `insumos_movimientos_stock`
  MODIFY `id_movimiento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `localidades`
--
ALTER TABLE `localidades`
  MODIFY `id_localidad` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT de la tabla `monitores`
--
ALTER TABLE `monitores`
  MODIFY `id_monitor` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `notas_pedidos`
--
ALTER TABLE `notas_pedidos`
  MODIFY `id_nota` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `notebooks`
--
ALTER TABLE `notebooks`
  MODIFY `id_notebook` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT de la tabla `pcs_completas`
--
ALTER TABLE `pcs_completas`
  MODIFY `id_pc_completa` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  MODIFY `id_pedido` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT de la tabla `pedidos_adjuntos`
--
ALTER TABLE `pedidos_adjuntos`
  MODIFY `id_adjunto` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pedidos_historial`
--
ALTER TABLE `pedidos_historial`
  MODIFY `id_historial` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=111;

--
-- AUTO_INCREMENT de la tabla `pedidos_informes`
--
ALTER TABLE `pedidos_informes`
  MODIFY `id_informe` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `puntos_stock`
--
ALTER TABLE `puntos_stock`
  MODIFY `id_punto_stock` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `remitos`
--
ALTER TABLE `remitos`
  MODIFY `id_remito` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=95;

--
-- AUTO_INCREMENT de la tabla `remitos_detalle`
--
ALTER TABLE `remitos_detalle`
  MODIFY `id_detalle` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=159;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id_rol` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `sedes`
--
ALTER TABLE `sedes`
  MODIFY `id_sede` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT de la tabla `sedes_internet`
--
ALTER TABLE `sedes_internet`
  MODIFY `id_internet` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de la tabla `sedes_planos`
--
ALTER TABLE `sedes_planos`
  MODIFY `id_plano` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `sedes_red_dispositivos`
--
ALTER TABLE `sedes_red_dispositivos`
  MODIFY `id_dispositivo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `sedes_telefonia_lineas`
--
ALTER TABLE `sedes_telefonia_lineas`
  MODIFY `id_linea` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `sedes_vigilancia`
--
ALTER TABLE `sedes_vigilancia`
  MODIFY `id_vigilancia` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

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
  MODIFY `id_sesion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=190;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

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
-- Filtros para la tabla `ingresos_documentos`
--
ALTER TABLE `ingresos_documentos`
  ADD CONSTRAINT `ingresos_documentos_ibfk_1` FOREIGN KEY (`id_ingreso`) REFERENCES `ingresos` (`id_ingreso`) ON DELETE CASCADE,
  ADD CONSTRAINT `ingresos_documentos_ibfk_2` FOREIGN KEY (`cargado_por`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL;

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
-- Filtros para la tabla `notas_pedidos`
--
ALTER TABLE `notas_pedidos`
  ADD CONSTRAINT `notas_pedidos_ibfk_1` FOREIGN KEY (`id_pedido`) REFERENCES `pedidos` (`id_pedido`) ON DELETE CASCADE,
  ADD CONSTRAINT `notas_pedidos_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`);

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
-- Filtros para la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD CONSTRAINT `fk_pedidos_area` FOREIGN KEY (`id_area`) REFERENCES `areas` (`id_area`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pedidos_asignado` FOREIGN KEY (`asignado_a`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pedidos_sede` FOREIGN KEY (`id_sede`) REFERENCES `sedes` (`id_sede`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pedidos_usuario` FOREIGN KEY (`id_usuario_solicitante`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `pedidos_adjuntos`
--
ALTER TABLE `pedidos_adjuntos`
  ADD CONSTRAINT `fk_adjuntos_pedido` FOREIGN KEY (`id_pedido`) REFERENCES `pedidos` (`id_pedido`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_adjuntos_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `pedidos_historial`
--
ALTER TABLE `pedidos_historial`
  ADD CONSTRAINT `fk_historial_pedido` FOREIGN KEY (`id_pedido`) REFERENCES `pedidos` (`id_pedido`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_historial_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `pedidos_informes`
--
ALTER TABLE `pedidos_informes`
  ADD CONSTRAINT `fk_informes_pedido` FOREIGN KEY (`id_pedido`) REFERENCES `pedidos` (`id_pedido`) ON DELETE CASCADE ON UPDATE CASCADE;

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
