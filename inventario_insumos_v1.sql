-- MySQL dump 10.13  Distrib 8.0.44, for Linux (x86_64)
--
-- Host: localhost    Database: inventario_insumos_v1
-- ------------------------------------------------------
-- Server version	8.0.44-0ubuntu0.24.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `areas`
--

DROP TABLE IF EXISTS `areas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `areas` (
  `id_area` int NOT NULL AUTO_INCREMENT,
  `nombre_area` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id_area`),
  UNIQUE KEY `nombre_area` (`nombre_area`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `areas`
--

LOCK TABLES `areas` WRITE;
/*!40000 ALTER TABLE `areas` DISABLE KEYS */;
INSERT INTO `areas` VALUES (1,'Informatica','Área de informática y sistemas'),(2,'Administración','Área administrativa'),(3,'Delegado/a','Área de Delegados/as'),(4,'Sueldos','Área de contabilidad'),(5,'Preventivos','Área de Preventivos'),(6,'Mesa de Entrada','Área de Recepción'),(7,'Fortalecimiento Familiar','Área de Fortalecimiento Familiar'),(8,'Admisión','Área de Admisión'),(9,'Penal Juvenil','Área de Penal Juvenil '),(10,'Legales','Área de Legales'),(11,'Otros','Otras áreas '),(15,'Discapacidad','Área de Discapacidad'),(16,'Subsecretaria MPI','Área de MPI'),(17,'Subsecretaria MPE','Área de MPE'),(18,'Alquileres','Área de Alquileres'),(19,'Viaticos','Área de Viaticos');
/*!40000 ALTER TABLE `areas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `auditoria_acciones`
--

DROP TABLE IF EXISTS `auditoria_acciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `auditoria_acciones` (
  `id_auditoria` bigint NOT NULL AUTO_INCREMENT,
  `id_usuario` int NOT NULL,
  `id_sesion` int DEFAULT NULL,
  `accion` varchar(100) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'crear_insumo, editar_insumo, eliminar_insumo, etc.',
  `modulo` varchar(50) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'insumos, asignaciones, reportes, usuarios, etc.',
  `descripcion` text COLLATE utf8mb4_general_ci NOT NULL,
  `entidad_tipo` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'insumo, asignacion, usuario, etc.',
  `entidad_id` int DEFAULT NULL COMMENT 'ID de la entidad afectada',
  `datos_antes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin COMMENT 'Estado anterior (para ediciones/eliminaciones)',
  `datos_despues` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin COMMENT 'Estado posterior (para creaciones/ediciones)',
  `ip_address` varchar(45) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `resultado` enum('exito','error') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'exito',
  `mensaje_error` text COLLATE utf8mb4_general_ci,
  `fecha_accion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_auditoria`),
  KEY `idx_usuario` (`id_usuario`),
  KEY `idx_sesion` (`id_sesion`),
  KEY `idx_accion` (`accion`),
  KEY `idx_modulo` (`modulo`),
  KEY `idx_fecha` (`fecha_accion`),
  KEY `idx_entidad` (`entidad_tipo`,`entidad_id`),
  KEY `idx_resultado` (`resultado`),
  KEY `idx_usuario_fecha` (`id_usuario`,`fecha_accion`),
  KEY `idx_modulo_accion` (`modulo`,`accion`),
  CONSTRAINT `fk_auditoria_sesion` FOREIGN KEY (`id_sesion`) REFERENCES `sesiones` (`id_sesion`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_auditoria_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `auditoria_acciones_chk_1` CHECK (json_valid(`datos_antes`)),
  CONSTRAINT `auditoria_acciones_chk_2` CHECK (json_valid(`datos_despues`))
) ENGINE=InnoDB AUTO_INCREMENT=237 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Auditoría completa de acciones del sistema';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `auditoria_acciones`
--

LOCK TABLES `auditoria_acciones` WRITE;
/*!40000 ALTER TABLE `auditoria_acciones` DISABLE KEYS */;
INSERT INTO `auditoria_acciones` VALUES (194,10,66,'login','usuarios','Login exitoso: admin',NULL,NULL,NULL,NULL,'10.114.85.189','exito',NULL,'2025-12-03 09:38:05'),(195,10,67,'login','usuarios','Login exitoso: admin',NULL,NULL,NULL,NULL,'10.114.85.189','exito',NULL,'2025-12-04 06:44:48'),(196,10,67,'sesion_expirada','usuarios','Sesión expirada por inactividad',NULL,NULL,NULL,NULL,'10.114.85.189','exito',NULL,'2025-12-04 10:46:34'),(197,10,67,'logout','usuarios','Cierre de sesión',NULL,NULL,NULL,NULL,'10.114.85.189','exito',NULL,'2025-12-04 10:46:34'),(198,10,68,'login','usuarios','Login exitoso: admin',NULL,NULL,NULL,NULL,'10.114.85.189','exito',NULL,'2025-12-05 07:17:51'),(199,10,69,'login','usuarios','Login exitoso: admin',NULL,NULL,NULL,NULL,'10.114.85.189','exito',NULL,'2025-12-05 10:12:52'),(200,10,70,'login','usuarios','Login exitoso: admin',NULL,NULL,NULL,NULL,'10.114.85.189','exito',NULL,'2025-12-09 05:07:07'),(201,10,70,'crear_usuario','usuarios','Usuario creado: jvillaverde (Joaquin Villaverde)','usuario',11,NULL,'{\"username\":\"jvillaverde\",\"email\":\"jvillaverde@senaf.rionegro.gov.ar\",\"nombre\":\"Joaquin\",\"apellido\":\"Villaverde\",\"id_rol\":3}','10.114.85.189','exito',NULL,'2025-12-09 05:08:21'),(202,10,70,'editar_permisos_usuario','usuarios','Permisos personalizados actualizados para usuario: jvillaverde','usuario',11,'{\"permisos_anteriores\":{\"insumos\":[\"ver\",\"crear\",\"editar\",\"baja\"],\"asignaciones\":[\"ver\",\"crear\",\"devolver\"],\"reportes\":[\"ver\"],\"telecom\":[\"ver\"],\"sedes\":[\"ver\"],\"areas\":[\"ver\"]}}','{\"permisos_nuevos\":{\"insumos\":[\"ver\",\"crear\",\"editar\",\"baja\"],\"asignaciones\":[\"ver\",\"crear\",\"editar\",\"anular\",\"devolver\"],\"reportes\":[\"ver\"],\"telecom\":[\"ver\",\"editar\",\"eliminar\"],\"sedes\":[\"ver\",\"crear\",\"editar\"],\"areas\":[\"ver\",\"crear\",\"editar\"]}}','10.114.85.189','exito',NULL,'2025-12-09 05:08:52'),(203,10,70,'logout','usuarios','Cierre de sesión',NULL,NULL,NULL,NULL,'10.114.85.189','exito',NULL,'2025-12-09 05:08:57'),(204,11,71,'login','usuarios','Login exitoso: jvillaverde',NULL,NULL,NULL,NULL,'10.114.85.189','exito',NULL,'2025-12-09 05:09:05'),(205,11,71,'sesion_expirada','usuarios','Sesión expirada por inactividad',NULL,NULL,NULL,NULL,'10.114.85.189','exito',NULL,'2025-12-09 05:57:42'),(206,11,71,'logout','usuarios','Cierre de sesión',NULL,NULL,NULL,NULL,'10.114.85.189','exito',NULL,'2025-12-09 05:57:42'),(207,10,72,'login','usuarios','Login exitoso: admin',NULL,NULL,NULL,NULL,'10.114.85.189','exito',NULL,'2025-12-09 05:57:49'),(208,10,72,'sesion_expirada','usuarios','Sesión expirada por inactividad',NULL,NULL,NULL,NULL,'10.114.85.189','exito',NULL,'2025-12-09 06:49:42'),(209,10,72,'logout','usuarios','Cierre de sesión',NULL,NULL,NULL,NULL,'10.114.85.189','exito',NULL,'2025-12-09 06:49:42'),(210,10,73,'login','usuarios','Login exitoso: admin',NULL,NULL,NULL,NULL,'10.114.85.189','exito',NULL,'2025-12-09 06:49:47'),(211,10,74,'login','usuarios','Login exitoso: admin',NULL,NULL,NULL,NULL,'10.114.85.189','exito',NULL,'2025-12-09 08:19:02'),(212,10,75,'login','usuarios','Login exitoso: admin',NULL,NULL,NULL,NULL,'10.114.85.189','exito',NULL,'2025-12-09 09:03:14'),(213,10,76,'login','usuarios','Login exitoso: admin',NULL,NULL,NULL,NULL,'10.114.85.189','exito',NULL,'2025-12-09 10:54:01'),(214,10,76,'logout','usuarios','Cierre de sesión',NULL,NULL,NULL,NULL,'10.114.85.189','exito',NULL,'2025-12-09 10:54:52'),(215,11,77,'login','usuarios','Login exitoso: jvillaverde',NULL,NULL,NULL,NULL,'10.114.85.189','exito',NULL,'2025-12-09 10:55:00'),(216,11,77,'logout','usuarios','Cierre de sesión',NULL,NULL,NULL,NULL,'10.114.85.189','exito',NULL,'2025-12-09 10:55:53'),(217,10,78,'login','usuarios','Login exitoso: admin',NULL,NULL,NULL,NULL,'10.114.85.189','exito',NULL,'2025-12-09 10:55:58'),(218,10,78,'editar_permisos_usuario','usuarios','Permisos personalizados actualizados para usuario: jvillaverde','usuario',11,'{\"permisos_anteriores\":{\"insumos\":[\"ver\",\"crear\",\"editar\",\"baja\"],\"asignaciones\":[\"ver\",\"crear\",\"editar\",\"anular\",\"devolver\"],\"reportes\":[\"ver\"],\"telecom\":[\"ver\",\"editar\",\"eliminar\"],\"sedes\":[\"ver\",\"crear\",\"editar\"],\"areas\":[\"ver\",\"crear\",\"editar\"]}}','{\"permisos_nuevos\":{\"insumos\":[\"ver\",\"crear\",\"editar\",\"baja\"],\"asignaciones\":[\"ver\",\"crear\",\"editar\",\"devolver\"],\"reportes\":[\"ver\"],\"sedes\":[\"ver\",\"crear\",\"editar\"],\"telecom\":[\"ver\",\"crear\",\"editar\"],\"areas\":[\"ver\",\"crear\",\"editar\"]}}','10.114.85.189','exito',NULL,'2025-12-09 10:56:53'),(219,10,78,'logout','usuarios','Cierre de sesión',NULL,NULL,NULL,NULL,'10.114.85.189','exito',NULL,'2025-12-09 10:56:57'),(220,11,79,'login','usuarios','Login exitoso: jvillaverde',NULL,NULL,NULL,NULL,'10.114.85.189','exito',NULL,'2025-12-09 10:57:07'),(221,10,80,'login','usuarios','Login exitoso: admin',NULL,NULL,NULL,NULL,'10.114.85.189','exito',NULL,'2025-12-10 06:15:52'),(222,10,80,'crear_usuario','usuarios','Usuario creado: hgonzalez (Heber Gonzalez)','usuario',12,NULL,'{\"username\":\"hgonzalez\",\"email\":\"hgonzalez@senaf.rionegro.gov.ar\",\"nombre\":\"Heber\",\"apellido\":\"Gonzalez\",\"id_rol\":3}','10.114.85.189','exito',NULL,'2025-12-10 06:16:38'),(223,10,80,'crear_usuario','usuarios','Usuario creado: dgarcia (Diego Garcia)','usuario',13,NULL,'{\"username\":\"dgarcia\",\"email\":\"dgarcia@senaf.rionegro.gov.ar\",\"nombre\":\"Diego\",\"apellido\":\"Garcia\",\"id_rol\":1}','10.114.85.189','exito',NULL,'2025-12-10 06:18:33'),(224,10,80,'crear_usuario','usuarios','Usuario creado: egimenez (Vanesa Gimenez)','usuario',14,NULL,'{\"username\":\"egimenez\",\"email\":\"egimenez@senaf.rionegro.gov.ar\",\"nombre\":\"Vanesa\",\"apellido\":\"Gimenez\",\"id_rol\":2}','10.114.85.189','exito',NULL,'2025-12-10 06:19:28'),(225,10,80,'crear_usuario','usuarios','Usuario creado: ftoledo (Facundo Toledo)','usuario',15,NULL,'{\"username\":\"ftoledo\",\"email\":\"ftoledo@senaf.rionegro.gov.ar\",\"nombre\":\"Facundo\",\"apellido\":\"Toledo\",\"id_rol\":1}','10.114.85.189','exito',NULL,'2025-12-10 06:20:19'),(226,10,80,'logout','usuarios','Cierre de sesión',NULL,NULL,NULL,NULL,'10.114.85.189','exito',NULL,'2025-12-10 06:20:34'),(227,11,81,'login','usuarios','Login exitoso: jvillaverde',NULL,NULL,NULL,NULL,'10.114.85.189','exito',NULL,'2025-12-10 06:23:04'),(228,11,82,'login','usuarios','Login exitoso: jvillaverde',NULL,NULL,NULL,NULL,'10.114.85.189','exito',NULL,'2025-12-10 07:48:10'),(229,11,83,'login','usuarios','Login exitoso: jvillaverde',NULL,NULL,NULL,NULL,'10.114.85.189','exito',NULL,'2025-12-10 08:51:19'),(230,11,84,'login','usuarios','Login exitoso: jvillaverde',NULL,NULL,NULL,NULL,'10.114.85.189','exito',NULL,'2025-12-10 09:22:29'),(231,10,85,'login','usuarios','Login exitoso: admin',NULL,NULL,NULL,NULL,'10.114.85.189','exito',NULL,'2025-12-10 10:53:28'),(232,11,86,'login','usuarios','Login exitoso: jvillaverde',NULL,NULL,NULL,NULL,'10.114.85.189','exito',NULL,'2025-12-11 05:07:03'),(233,11,87,'login','usuarios','Login exitoso: jvillaverde',NULL,NULL,NULL,NULL,'10.114.85.189','exito',NULL,'2025-12-11 07:52:06'),(234,11,88,'login','usuarios','Login exitoso: jvillaverde',NULL,NULL,NULL,NULL,'10.114.85.189','exito',NULL,'2025-12-11 09:44:39'),(235,11,89,'login','usuarios','Login exitoso: jvillaverde',NULL,NULL,NULL,NULL,'10.114.85.189','exito',NULL,'2025-12-11 10:51:06'),(236,11,90,'login','usuarios','Login exitoso: jvillaverde',NULL,NULL,NULL,NULL,'10.114.85.189','exito',NULL,'2025-12-12 07:16:09');
/*!40000 ALTER TABLE `auditoria_acciones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `escaneres`
--

DROP TABLE IF EXISTS `escaneres`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `escaneres` (
  `id_escaner` int NOT NULL AUTO_INCREMENT,
  `id_insumo` int NOT NULL,
  `marca` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `modelo` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id_escaner`),
  UNIQUE KEY `id_insumo` (`id_insumo`),
  CONSTRAINT `ESCANERES_ibfk_1` FOREIGN KEY (`id_insumo`) REFERENCES `insumos` (`id_insumo`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `escaneres`
--

LOCK TABLES `escaneres` WRITE;
/*!40000 ALTER TABLE `escaneres` DISABLE KEYS */;
/*!40000 ALTER TABLE `escaneres` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `impresoras`
--

DROP TABLE IF EXISTS `impresoras`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `impresoras` (
  `id_impresora` int NOT NULL AUTO_INCREMENT,
  `id_insumo` int NOT NULL,
  `marca` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `modelo` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id_impresora`),
  UNIQUE KEY `id_insumo` (`id_insumo`),
  CONSTRAINT `IMPRESORAS_ibfk_1` FOREIGN KEY (`id_insumo`) REFERENCES `insumos` (`id_insumo`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `impresoras`
--

LOCK TABLES `impresoras` WRITE;
/*!40000 ALTER TABLE `impresoras` DISABLE KEYS */;
INSERT INTO `impresoras` VALUES (2,14,'Lexmark','MS421dn'),(4,15,'HP','Multifunción MFP M176n'),(5,16,'Samsung','ML-1665');
/*!40000 ALTER TABLE `impresoras` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ingresos`
--

DROP TABLE IF EXISTS `ingresos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ingresos` (
  `id_ingreso` int NOT NULL AUTO_INCREMENT,
  `tipo_ingreso` enum('fondos','compra_directa','licitacion','otros') COLLATE utf8mb4_general_ci DEFAULT 'licitacion',
  `nro_referencia` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `descripcion` text COLLATE utf8mb4_general_ci,
  `fecha_finalizacion` date DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_ingreso`),
  UNIQUE KEY `cod_expediente` (`nro_referencia`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ingresos`
--

LOCK TABLES `ingresos` WRITE;
/*!40000 ALTER TABLE `ingresos` DISABLE KEYS */;
/*!40000 ALTER TABLE `ingresos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ingresos_documentos`
--

DROP TABLE IF EXISTS `ingresos_documentos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ingresos_documentos` (
  `id_documento` int NOT NULL AUTO_INCREMENT,
  `id_ingreso` int NOT NULL,
  `nombre_archivo` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `ruta_archivo` varchar(500) COLLATE utf8mb4_general_ci NOT NULL,
  `tipo_documento` enum('remito','documentacion','otro') COLLATE utf8mb4_general_ci DEFAULT 'otro',
  `fecha_carga` datetime DEFAULT CURRENT_TIMESTAMP,
  `cargado_por` int DEFAULT NULL,
  PRIMARY KEY (`id_documento`),
  KEY `cargado_por` (`cargado_por`),
  KEY `idx_id_ingreso` (`id_ingreso`),
  CONSTRAINT `ingresos_documentos_ibfk_1` FOREIGN KEY (`id_ingreso`) REFERENCES `ingresos` (`id_ingreso`) ON DELETE CASCADE,
  CONSTRAINT `ingresos_documentos_ibfk_2` FOREIGN KEY (`cargado_por`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ingresos_documentos`
--

LOCK TABLES `ingresos_documentos` WRITE;
/*!40000 ALTER TABLE `ingresos_documentos` DISABLE KEYS */;
/*!40000 ALTER TABLE `ingresos_documentos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `insumos`
--

DROP TABLE IF EXISTS `insumos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `insumos` (
  `id_insumo` int NOT NULL AUTO_INCREMENT,
  `nombre_insumo` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tipo_insumo` enum('Varios','PC Escritorio','Notebook','Impresora','Monitor','Escaner') COLLATE utf8mb4_general_ci NOT NULL,
  `subcategoria_varios` enum('Hardware','Periféricos','Red') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `descripcion_general` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `numero_serie` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `id_fisico` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `id_patrimonio` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cantidad` int NOT NULL DEFAULT '1',
  `cantidad_oficina` int DEFAULT NULL COMMENT 'Stock disponible en oficina para asignaciones inmediatas (solo tipo Varios)',
  `cantidad_deposito` int DEFAULT NULL COMMENT 'Stock en depósito, requiere reposición a oficina (solo tipo Varios)',
  `fecha_adquisicion` date DEFAULT NULL,
  `estado` enum('Disponible','Asignado','De Baja') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Disponible',
  `id_punto_stock_actual` int DEFAULT NULL,
  `id_sede_actual` int DEFAULT NULL,
  `id_ingreso` int DEFAULT NULL,
  `es_nuevo` tinyint(1) DEFAULT '1' COMMENT '1=Nuevo, 0=Usado',
  `id_area_asignacion_actual` int DEFAULT NULL,
  `id_patrimonio_idx` varchar(50) COLLATE utf8mb4_general_ci GENERATED ALWAYS AS ((case when (`tipo_insumo` <> _utf8mb4'Varios') then `id_patrimonio` else NULL end)) VIRTUAL,
  PRIMARY KEY (`id_insumo`),
  UNIQUE KEY `uniq_id_patrimonio_idx` (`id_patrimonio_idx`),
  KEY `id_punto_stock_actual` (`id_punto_stock_actual`),
  KEY `id_area_asignacion_actual` (`id_area_asignacion_actual`),
  KEY `id_sede_actual` (`id_sede_actual`),
  KEY `idx_insumos_estado` (`estado`),
  KEY `idx_insumos_tipo` (`tipo_insumo`),
  KEY `idx_insumos_licitacion` (`id_ingreso`),
  KEY `idx_cantidad_oficina` (`cantidad_oficina`),
  CONSTRAINT `fk_i_area_actual` FOREIGN KEY (`id_area_asignacion_actual`) REFERENCES `areas` (`id_area`) ON DELETE SET NULL,
  CONSTRAINT `fk_i_punto` FOREIGN KEY (`id_punto_stock_actual`) REFERENCES `puntos_stock` (`id_punto_stock`) ON DELETE SET NULL,
  CONSTRAINT `fk_i_sede_actual` FOREIGN KEY (`id_sede_actual`) REFERENCES `sedes` (`id_sede`) ON DELETE SET NULL,
  CONSTRAINT `fk_insumos_ingreso` FOREIGN KEY (`id_ingreso`) REFERENCES `ingresos` (`id_ingreso`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=142 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `insumos`
--

LOCK TABLES `insumos` WRITE;
/*!40000 ALTER TABLE `insumos` DISABLE KEYS */;
INSERT INTO `insumos` (`id_insumo`, `nombre_insumo`, `tipo_insumo`, `subcategoria_varios`, `descripcion_general`, `numero_serie`, `id_fisico`, `id_patrimonio`, `cantidad`, `cantidad_oficina`, `cantidad_deposito`, `fecha_adquisicion`, `estado`, `id_punto_stock_actual`, `id_sede_actual`, `id_ingreso`, `es_nuevo`, `id_area_asignacion_actual`) VALUES (3,'Parlantes USB Genius SP-HF180','Varios','Periféricos',NULL,NULL,NULL,NULL,1,1,0,'2025-10-17','Disponible',NULL,1,NULL,1,11),(4,'Auricular Genius C/Micro HS04SU','Varios','Periféricos',NULL,NULL,NULL,NULL,4,4,0,'2025-10-17','Disponible',1,NULL,NULL,1,NULL),(5,'Teclado Genius USB KB-116','Varios','Periféricos',NULL,NULL,NULL,NULL,6,6,0,'2025-10-17','Disponible',1,NULL,NULL,1,NULL),(6,'WebCam 2k PJT-DCM173','Varios','Periféricos',NULL,NULL,NULL,NULL,4,4,0,'2025-10-17','Disponible',1,NULL,NULL,1,NULL),(7,'Mouse Trust','Varios','Periféricos',NULL,NULL,NULL,NULL,3,3,0,'2025-10-17','Disponible',1,43,NULL,0,11),(8,'Cargador Notebook Silverstone','Varios','Periféricos',NULL,NULL,NULL,NULL,1,1,0,'2025-10-17','Disponible',1,NULL,NULL,0,NULL),(9,'Teclados Varios USB/PS2','Varios','Periféricos',NULL,NULL,NULL,NULL,5,5,0,'2025-10-17','Disponible',1,43,NULL,0,11),(10,'Teclado Logitech Inalámbrico','Varios','Periféricos',NULL,NULL,NULL,NULL,1,1,0,'2025-10-17','Disponible',1,NULL,NULL,1,NULL),(13,'Fuente Kelyx KL-AT550','Varios','Hardware',NULL,NULL,NULL,NULL,7,0,7,'2025-11-05','Disponible',NULL,NULL,NULL,1,NULL),(14,'C/Toner, Sin unidad de imágen, Presenta error al tratar de reconocer toner','Impresora',NULL,NULL,'Nº de Caja 4600944314NZ3','ID de caja A10-2020','A10-2020',1,NULL,NULL,'2025-11-05','Disponible',2,NULL,NULL,0,NULL),(15,'Falta toner color Cian','Impresora',NULL,NULL,'S/Serie','S/ID','S/ID',1,NULL,NULL,'2025-11-05','Disponible',NULL,NULL,NULL,0,NULL),(16,'No Enciende','Impresora',NULL,NULL,'Z526BKDZ900875T',NULL,NULL,1,NULL,NULL,'2025-11-05','Disponible',2,NULL,NULL,0,NULL),(17,'Fichas Conectores RJ45 Hembra','Varios','Red','Hay 4 cajas nuevas',NULL,NULL,NULL,4,0,4,'2025-11-05','Disponible',NULL,NULL,NULL,1,NULL),(20,'Mouse Genius USB','Varios','Periféricos',NULL,NULL,NULL,NULL,16,0,16,'2025-11-26','Disponible',NULL,NULL,NULL,1,NULL),(21,'Monitor (Sin cable power/transformador)','Monitor',NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,'2025-11-26','Disponible',2,NULL,NULL,0,NULL),(22,'Cables MicroUSB','Varios','Periféricos',NULL,NULL,NULL,NULL,12,0,12,'2025-11-26','Disponible',NULL,NULL,NULL,1,NULL),(23,'Cables HDMI','Varios','Periféricos',NULL,NULL,NULL,NULL,8,0,8,'2025-11-26','Disponible',NULL,NULL,NULL,1,NULL),(24,'Teclados Genius USB','Varios','Periféricos',NULL,NULL,NULL,NULL,23,0,23,'2025-11-26','Disponible',NULL,NULL,NULL,1,NULL),(25,'Tarjetas MicroSD 64GB','Varios','Periféricos',NULL,NULL,NULL,NULL,50,0,50,'2025-11-26','Disponible',NULL,NULL,NULL,1,NULL),(109,NULL,'PC Escritorio',NULL,NULL,NULL,'A-0103','A-0103',1,NULL,NULL,'2018-11-01','Asignado',NULL,1,NULL,0,9),(110,NULL,'PC Escritorio',NULL,NULL,NULL,'A-0046','A-0046',1,NULL,NULL,'2020-12-01','Asignado',NULL,1,NULL,0,9),(111,NULL,'PC Escritorio',NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,'2025-12-10','Asignado',NULL,1,NULL,0,9),(112,NULL,'PC Escritorio',NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,'2025-12-10','Asignado',NULL,1,NULL,0,9),(113,NULL,'PC Escritorio',NULL,NULL,NULL,'A-0481','A-0481',1,NULL,NULL,'2020-12-01','Asignado',NULL,1,NULL,0,4),(114,NULL,'PC Escritorio',NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,'2020-12-01','Asignado',NULL,1,NULL,0,4),(115,NULL,'PC Escritorio',NULL,NULL,NULL,'D163','D163',1,NULL,NULL,'2017-12-01','Asignado',NULL,1,NULL,0,4),(116,NULL,'PC Escritorio',NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,'2020-12-01','Asignado',NULL,1,NULL,0,4),(117,NULL,'PC Escritorio',NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,'2025-12-10','Asignado',NULL,1,NULL,0,4),(118,NULL,'PC Escritorio',NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,'2018-11-01','Asignado',NULL,1,NULL,0,16),(119,NULL,'PC Escritorio',NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,'2025-12-10','Asignado',NULL,1,NULL,0,10),(120,NULL,'PC Escritorio',NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,'2018-11-01','Asignado',NULL,1,NULL,0,10),(121,NULL,'PC Escritorio',NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,'2018-11-01','Asignado',NULL,1,NULL,0,10),(122,NULL,'PC Escritorio',NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,'2018-11-01','Asignado',NULL,1,NULL,0,10),(123,NULL,'PC Escritorio',NULL,NULL,NULL,'A-0033','A-0033',1,NULL,NULL,'2018-11-01','Asignado',NULL,1,NULL,0,10),(124,NULL,'PC Escritorio',NULL,NULL,NULL,'D128','D128',1,NULL,NULL,'2017-11-01','Asignado',NULL,1,NULL,0,17),(125,NULL,'PC Escritorio',NULL,NULL,NULL,'A-0470','A-0470',1,NULL,NULL,'2018-11-01','Asignado',NULL,1,NULL,0,17),(126,NULL,'PC Escritorio',NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,'2018-12-01','Asignado',NULL,1,NULL,0,17),(127,NULL,'PC Escritorio',NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,'2018-11-01','Asignado',NULL,1,NULL,0,17),(128,NULL,'PC Escritorio',NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,'2018-11-01','Asignado',NULL,1,NULL,0,17),(129,NULL,'PC Escritorio',NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,'2020-11-01','Asignado',NULL,1,NULL,0,17),(130,NULL,'PC Escritorio',NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,'2025-12-11','Asignado',NULL,1,NULL,0,17),(131,NULL,'PC Escritorio',NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,'2018-11-01','Asignado',NULL,1,NULL,0,17),(132,NULL,'PC Escritorio',NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,'2025-12-11','Asignado',NULL,1,NULL,0,5),(133,NULL,'PC Escritorio',NULL,NULL,NULL,'A-0078','A-0078',1,NULL,NULL,'2018-11-01','Asignado',NULL,1,NULL,0,5),(134,NULL,'PC Escritorio',NULL,NULL,NULL,'A-0487','A-0487',1,NULL,NULL,'2018-11-01','Asignado',NULL,1,NULL,0,5),(135,NULL,'PC Escritorio',NULL,NULL,NULL,'A-0486','A-0486',1,NULL,NULL,'2018-11-01','Asignado',NULL,1,NULL,0,16),(136,NULL,'PC Escritorio',NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,'2017-11-01','Asignado',NULL,1,NULL,0,16),(137,NULL,'PC Escritorio',NULL,NULL,NULL,'D156','D156',1,NULL,NULL,'2017-11-01','Asignado',NULL,1,NULL,0,15),(138,NULL,'PC Escritorio',NULL,NULL,NULL,'D126','D126',1,NULL,NULL,'2018-11-01','Asignado',NULL,1,NULL,0,15),(139,NULL,'PC Escritorio',NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,'2018-11-01','Asignado',NULL,1,NULL,0,16),(140,NULL,'PC Escritorio',NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,'2017-11-01','Asignado',NULL,1,NULL,0,16),(141,NULL,'PC Escritorio',NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,'2018-11-01','Asignado',NULL,1,NULL,0,6);
/*!40000 ALTER TABLE `insumos` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`joaquin`@`localhost`*/ /*!50003 TRIGGER `trg_insumos_sync_cantidad_insert` BEFORE INSERT ON `insumos` FOR EACH ROW BEGIN
  
  IF NEW.tipo_insumo = 'Varios' THEN
    IF NEW.cantidad_oficina IS NULL THEN
      SET NEW.cantidad_oficina = 0;
    END IF;
    IF NEW.cantidad_deposito IS NULL THEN
      SET NEW.cantidad_deposito = 0;
    END IF;
    SET NEW.cantidad = NEW.cantidad_oficina + NEW.cantidad_deposito;
  END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`joaquin`@`localhost`*/ /*!50003 TRIGGER `trg_insumos_sync_cantidad_update` BEFORE UPDATE ON `insumos` FOR EACH ROW BEGIN
  
  IF NEW.tipo_insumo = 'Varios' THEN
    IF NEW.cantidad_oficina IS NULL THEN
      SET NEW.cantidad_oficina = 0;
    END IF;
    IF NEW.cantidad_deposito IS NULL THEN
      SET NEW.cantidad_deposito = 0;
    END IF;
    SET NEW.cantidad = NEW.cantidad_oficina + NEW.cantidad_deposito;
  END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `insumos_bajas`
--

DROP TABLE IF EXISTS `insumos_bajas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `insumos_bajas` (
  `id_baja` int NOT NULL AUTO_INCREMENT,
  `id_insumo` int NOT NULL,
  `fecha_baja` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `observacion` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `cantidad` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_baja`),
  KEY `idx_ib_insumo` (`id_insumo`),
  CONSTRAINT `fk_ib_insumo` FOREIGN KEY (`id_insumo`) REFERENCES `insumos` (`id_insumo`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `insumos_bajas`
--

LOCK TABLES `insumos_bajas` WRITE;
/*!40000 ALTER TABLE `insumos_bajas` DISABLE KEYS */;
/*!40000 ALTER TABLE `insumos_bajas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `insumos_movimientos_stock`
--

DROP TABLE IF EXISTS `insumos_movimientos_stock`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `insumos_movimientos_stock` (
  `id_movimiento` int NOT NULL AUTO_INCREMENT,
  `id_insumo` int NOT NULL,
  `tipo_movimiento` enum('reposicion_oficina','devolucion_a_deposito','ajuste_manual','ingreso_nuevo') COLLATE utf8mb4_general_ci NOT NULL,
  `cantidad_movida` int NOT NULL COMMENT 'Cantidad trasladada/ajustada',
  `ubicacion_origen` enum('deposito','oficina','externo','N/A') COLLATE utf8mb4_general_ci NOT NULL,
  `ubicacion_destino` enum('deposito','oficina','externo','N/A') COLLATE utf8mb4_general_ci NOT NULL,
  `cantidad_oficina_antes` int NOT NULL,
  `cantidad_deposito_antes` int NOT NULL,
  `cantidad_oficina_despues` int NOT NULL,
  `cantidad_deposito_despues` int NOT NULL,
  `usuario` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Usuario que realizó el movimiento',
  `observacion` text COLLATE utf8mb4_general_ci,
  `fecha_movimiento` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_movimiento`),
  KEY `idx_movimientos_insumo` (`id_insumo`),
  KEY `idx_movimientos_fecha` (`fecha_movimiento`),
  KEY `idx_movimientos_tipo` (`tipo_movimiento`),
  CONSTRAINT `fk_movimientos_insumo` FOREIGN KEY (`id_insumo`) REFERENCES `insumos` (`id_insumo`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Historial de movimientos de stock entre oficina y depósito';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `insumos_movimientos_stock`
--

LOCK TABLES `insumos_movimientos_stock` WRITE;
/*!40000 ALTER TABLE `insumos_movimientos_stock` DISABLE KEYS */;
/*!40000 ALTER TABLE `insumos_movimientos_stock` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `localidades`
--

DROP TABLE IF EXISTS `localidades`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `localidades` (
  `id_localidad` int NOT NULL AUTO_INCREMENT,
  `id_zona` int NOT NULL,
  `nombre_localidad` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id_localidad`),
  UNIQUE KEY `nombre_localidad` (`nombre_localidad`),
  KEY `id_zona` (`id_zona`),
  CONSTRAINT `LOCALIDADES_ibfk_1` FOREIGN KEY (`id_zona`) REFERENCES `zonas` (`id_zona`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `localidades`
--

LOCK TABLES `localidades` WRITE;
/*!40000 ALTER TABLE `localidades` DISABLE KEYS */;
INSERT INTO `localidades` VALUES (1,1,'Viedma'),(2,1,'General Conesa'),(3,3,'San Antonio Oeste'),(4,3,'Sierra Grande'),(5,3,'Valcheta'),(6,5,'Choele Choel'),(7,5,'Lamarque'),(8,5,'Luis Beltran'),(9,5,'Darwin'),(10,5,'Belisle'),(11,5,'Chimpay'),(12,5,'Rio Colorado'),(13,2,'Villa Regina'),(14,2,'Chichinales'),(15,2,'Ing. Huergo'),(16,4,'General Roca'),(17,4,'Allen'),(18,6,'Cipolletti'),(19,6,'Fernandez Oro'),(20,7,'Cinco Saltos'),(21,8,'Catriel'),(22,9,'Ramos Mexia'),(23,9,'Sierra Colorada'),(24,9,'Los Menucos'),(25,9,'Maquinchao'),(26,9,'Ing. Jacobacci'),(27,10,'Bariloche'),(28,11,'El Bolson');
/*!40000 ALTER TABLE `localidades` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `monitores`
--

DROP TABLE IF EXISTS `monitores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `monitores` (
  `id_monitor` int NOT NULL AUTO_INCREMENT,
  `id_insumo` int NOT NULL,
  `marca` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `modelo` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `pulgadas` decimal(4,1) DEFAULT NULL,
  `conexion` enum('VGA','HDMI') COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id_monitor`),
  UNIQUE KEY `id_insumo` (`id_insumo`),
  CONSTRAINT `MONITORES_ibfk_1` FOREIGN KEY (`id_insumo`) REFERENCES `insumos` (`id_insumo`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `monitores`
--

LOCK TABLES `monitores` WRITE;
/*!40000 ALTER TABLE `monitores` DISABLE KEYS */;
INSERT INTO `monitores` VALUES (3,21,'Samsung ','SyncMaster BX1950',19.0,'VGA');
/*!40000 ALTER TABLE `monitores` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notebooks`
--

DROP TABLE IF EXISTS `notebooks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notebooks` (
  `id_notebook` int NOT NULL AUTO_INCREMENT,
  `id_insumo` int NOT NULL,
  `marca` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `modelo` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `procesador` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ram_gb` int DEFAULT NULL,
  `almacenamiento_gb` int DEFAULT NULL,
  `cargador` tinyint(1) NOT NULL DEFAULT '0',
  `funda` tinyint(1) NOT NULL DEFAULT '0',
  `micro_sd` tinyint(1) NOT NULL DEFAULT '0',
  `micro_sd_gb` int DEFAULT NULL,
  `caja` tinyint(1) NOT NULL DEFAULT '0',
  `adaptador_red` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id_notebook`),
  UNIQUE KEY `id_insumo` (`id_insumo`),
  CONSTRAINT `NOTEBOOKS_ibfk_1` FOREIGN KEY (`id_insumo`) REFERENCES `insumos` (`id_insumo`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notebooks`
--

LOCK TABLES `notebooks` WRITE;
/*!40000 ALTER TABLE `notebooks` DISABLE KEYS */;
/*!40000 ALTER TABLE `notebooks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pcs_completas`
--

DROP TABLE IF EXISTS `pcs_completas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pcs_completas` (
  `id_pc_completa` int NOT NULL AUTO_INCREMENT,
  `id_insumo` int NOT NULL,
  `procesador` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ram_gb` int DEFAULT NULL,
  `almacenamiento_gb` int DEFAULT NULL,
  `mother` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sist_op` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id_pc_completa`),
  UNIQUE KEY `id_insumo` (`id_insumo`),
  CONSTRAINT `PCS_COMPLETAS_ibfk_1` FOREIGN KEY (`id_insumo`) REFERENCES `insumos` (`id_insumo`)
) ENGINE=InnoDB AUTO_INCREMENT=77 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pcs_completas`
--

LOCK TABLES `pcs_completas` WRITE;
/*!40000 ALTER TABLE `pcs_completas` DISABLE KEYS */;
INSERT INTO `pcs_completas` VALUES (44,109,'Intel I3-7100',4,1024,'MSI H110M PRO VH PLUS','Ubuntu 22.04'),(45,110,'Intel I3-7100',4,1024,'MSI H110M PRO VH PLUS','Ubuntu 22.04'),(46,111,'Intel I3-7100',8,1024,'MSI H110M PRO VH PLUS','Ubuntu 22.04'),(47,112,'Intel I3-10100',4,1024,'Gigabyte H410M S2H V3','Ubuntu 22.04'),(48,113,'AMD A8-9600 R7',4,1024,'Gigabyte A320M-H-CF','Ubuntu 18.04'),(49,114,'Intel I3-6100',4,500,'Hp Prodesk 600 G3 Mt/8290','Ubuntu 18.04'),(50,115,'Intel Pentium G3250',4,1024,'Asrock H81M-VG4 R2.0','Ubuntu 22.04'),(51,116,'AMD Ryzen 5 2400G',16,2048,'Asus B450M-A','Windows 10 Pro'),(52,117,'AMD A4-4000',8,1024,'MSI MS-7721','Windows 10 Pro'),(53,118,'Intel I3-4170',4,500,'Gigabyte B85M-D3H-A','Ubuntu 22.04'),(54,119,'Intel I3-7100',8,160,'Asus H110M-K','Ubuntu 22.04'),(55,120,'Intel I3-7100',4,500,'MSI H110M PRO VH PLUS','Ubuntu 18.04'),(56,121,'Intel I3-7100',4,1024,'MSI H110M PRO VH PLUS','Ubuntu 22.04'),(57,122,'Intel I3-7100',4,1024,'MSI H110M PRO VH PLUS','Ubuntu 22.04'),(58,123,'Intel I3-7100',4,1024,'MSI H110M PRO VH PLUS','Ubuntu 22.04'),(59,124,'AMD Athon II x2 250',4,500,'Biostar N6853+','Xubuntu 18.04'),(60,125,'AMD A8-9600 R7',4,1024,'Gigabyte A320M-H','Ubuntu 22.04'),(61,126,'Intal Celeron E3400',2,120,'Asrock G41C-VS','Lubuntu 18.04'),(62,127,'Intel I3-7100',4,1024,'MSI-7A154','Ubuntu 22.04'),(63,128,'Intel I3-4160',4,500,'Asus H81M-C','Ubuntu 22.04'),(64,129,'Intel I3-10100',8,1024,'Gigabyte H410M S2H V3','Ubuntu 22.04'),(65,130,'Intel I3-7100',4,1024,'Gigabyte H110M-H','Ubuntu 18.04'),(66,131,'Intel I3-7100',8,500,'Asus H110M-k','Ubuntu 22.04'),(67,132,'AMD A8-9600 R7',4,1024,'Gigabyte A320M-H','Ubuntu 22.04'),(68,133,'Intel I3-7100',4,1024,'MSI H110M PRO VH PLUS','Ubuntu 18.04'),(69,134,'AMD A8-9600 R7',4,1204,'Gigabyte A320M-H','Ubuntu 18.04'),(70,135,'AMD A8-9600 R7',4,1024,'Gigabyte A320M-H','Ubuntu 22.04'),(71,136,'Intel I3-3220',4,500,'Intel DH61H0','Ubuntu 20.04'),(72,137,'Intel Pentium E5400',4,320,'Compaq 500B 2A8C Foxcon','Lubuntu 22.04'),(73,138,'AMD A4-4000',4,1024,'Gigabyte F2A68HM-H','Ubuntu 22.04'),(74,139,'Intel I3-7100',4,1024,'MSI H110M PRO VH PLUS','Ubuntu 18.04'),(75,140,'AMD Athon II x2 250',4,250,'Biostar N6853B','Lubuntu 18.04'),(76,141,'AMD A10-9700 R7',8,500,'Asrock A320M-HDV','Ubuntu 22.04');
/*!40000 ALTER TABLE `pcs_completas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `puntos_stock`
--

DROP TABLE IF EXISTS `puntos_stock`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `puntos_stock` (
  `id_punto_stock` int NOT NULL AUTO_INCREMENT,
  `nombre_punto` enum('Oficina','Depósito') COLLATE utf8mb4_general_ci NOT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id_punto_stock`),
  UNIQUE KEY `nombre_punto` (`nombre_punto`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `puntos_stock`
--

LOCK TABLES `puntos_stock` WRITE;
/*!40000 ALTER TABLE `puntos_stock` DISABLE KEYS */;
INSERT INTO `puntos_stock` VALUES (1,'Oficina','Punto de stock en oficina'),(2,'Depósito','Punto de stock en depósito');
/*!40000 ALTER TABLE `puntos_stock` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `remito_secuencia`
--

DROP TABLE IF EXISTS `remito_secuencia`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `remito_secuencia` (
  `anio` int NOT NULL,
  `ultimo` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`anio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `remito_secuencia`
--

LOCK TABLES `remito_secuencia` WRITE;
/*!40000 ALTER TABLE `remito_secuencia` DISABLE KEYS */;
INSERT INTO `remito_secuencia` VALUES (2025,0);
/*!40000 ALTER TABLE `remito_secuencia` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `remitos`
--

DROP TABLE IF EXISTS `remitos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `remitos` (
  `id_remito` int NOT NULL AUTO_INCREMENT,
  `numero_remito` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `id_sede` int NOT NULL,
  `id_area` int NOT NULL,
  `nombre_persona_asignada` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `apellido_persona_asignada` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `fecha_asignacion` date NOT NULL,
  `estado` enum('Activa','Devuelta','Anulado') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Activa',
  `fecha_devolucion` date DEFAULT NULL,
  `observaciones` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `motivo_anulacion` text COLLATE utf8mb4_general_ci,
  `fecha_anulacion` datetime DEFAULT NULL,
  PRIMARY KEY (`id_remito`),
  UNIQUE KEY `numero_remito` (`numero_remito`),
  KEY `idx_remitos_fecha` (`fecha_asignacion`),
  KEY `idx_remitos_estado` (`estado`),
  KEY `fk_remitos_sede` (`id_sede`),
  KEY `fk_remitos_area` (`id_area`),
  CONSTRAINT `fk_r_area` FOREIGN KEY (`id_area`) REFERENCES `areas` (`id_area`),
  CONSTRAINT `fk_r_sede` FOREIGN KEY (`id_sede`) REFERENCES `sedes` (`id_sede`)
) ENGINE=InnoDB AUTO_INCREMENT=105 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `remitos`
--

LOCK TABLES `remitos` WRITE;
/*!40000 ALTER TABLE `remitos` DISABLE KEYS */;
INSERT INTO `remitos` VALUES (72,'109_2025_hist',1,9,'Fiorela','Maglione','2018-12-01','Activa',NULL,NULL,NULL,NULL),(73,'1_2025_hist',1,9,'Fiorela','Maglione','2020-12-01','Activa',NULL,NULL,NULL,NULL),(74,'2_2025_hist',1,9,'Fiorela','Maglione','2018-11-01','Activa',NULL,NULL,NULL,NULL),(75,'3_2025_hist',1,9,'Fiorela','Maglione','2025-12-10','Activa',NULL,NULL,NULL,NULL),(76,'4_2025_hist',1,4,'Leandro','Gutierrez','2020-11-01','Activa',NULL,NULL,NULL,NULL),(77,'5_2025_hist',1,4,'Leandro','Gutierrez','2020-11-01','Activa',NULL,NULL,NULL,NULL),(78,'6_2025_hist',1,4,'Leandro','Gutierrez','2017-11-01','Activa',NULL,NULL,NULL,NULL),(79,'7_2025_hist',1,4,'Leandro','Gutierrez','2020-11-01','Activa',NULL,NULL,NULL,NULL),(80,'8_2025_hist',1,4,'Leandro','Gutierrez','2025-12-01','Activa',NULL,NULL,NULL,NULL),(81,'9_2025_hist',1,16,'Sebastian','Sanchez','2018-12-01','Activa',NULL,NULL,NULL,NULL),(82,'10_2025_hist',1,10,'Ruben','Marin','2018-12-01','Activa',NULL,NULL,NULL,NULL),(83,'11_2025_hist',1,10,'Ruben','Marin','2018-12-01','Activa',NULL,NULL,NULL,NULL),(84,'12_2025_hist',1,10,'Ruben','Marin','2018-12-01','Activa',NULL,NULL,NULL,NULL),(85,'13_2025_hist',1,10,'Ruben','Marin','2018-12-01','Activa',NULL,NULL,NULL,NULL),(86,'14_2025_hist',1,10,'Ruben','Marin','2018-12-01','Activa',NULL,NULL,NULL,NULL),(87,'15_2025_hist',1,17,'Evelyn Aylen','Ramirez Tomasini','2017-12-01','Activa',NULL,NULL,NULL,NULL),(88,'16_2025_hist',1,17,'Evelyn Aylen','Ramirez Tomasini','2018-12-01','Activa',NULL,NULL,NULL,NULL),(89,'17_2025_hist',1,17,'Evelyn Aylen','Ramirez Tomasini','2017-11-01','Activa',NULL,NULL,NULL,NULL),(90,'18_2025_hist',1,17,'Evelyn Aylen','Ramirez Tomasini','2018-12-01','Activa',NULL,NULL,NULL,NULL),(91,'19_2025_hist',1,17,'Evelyn Aylen','Ramirez Tomasini','2018-12-01','Activa',NULL,NULL,NULL,NULL),(92,'20_2025_hist',1,17,'Evelyn Aylen','Ramirez Tomasini','2020-12-01','Activa',NULL,NULL,NULL,NULL),(93,'21_2025_hist',1,17,'Evelyn Aylen','Ramirez Tomasini','2018-12-01','Activa',NULL,NULL,NULL,NULL),(94,'22_2025_hist',1,17,'Evelyn Aylen','Ramirez Tomasini','2018-12-01','Activa',NULL,NULL,NULL,NULL),(95,'23_2025_hist',1,5,'Alejandra','Barros','2018-12-01','Activa',NULL,NULL,NULL,NULL),(96,'24_2025_hist',1,5,'Alejandra','Barros','2018-12-01','Activa',NULL,NULL,NULL,NULL),(97,'25_2025_hist',1,5,'Alejandra','Barros','2018-12-01','Activa',NULL,NULL,NULL,NULL),(98,'26_2025_hist',1,16,'Sebastian','Sanchez','2018-12-01','Activa',NULL,NULL,NULL,NULL),(99,'27_2025_hist',1,16,'Sebastian','Sanchez','2017-12-01','Activa',NULL,NULL,NULL,NULL),(100,'28_2025_hist',1,15,'Lujan','Teruel','2017-12-01','Activa',NULL,NULL,NULL,NULL),(101,'29_2025_hist',1,15,'Lujan','Teruel','2018-12-01','Activa',NULL,NULL,NULL,NULL),(102,'30_2025_hist',1,16,'Sebastian','Sanchez','2018-12-01','Activa',NULL,NULL,NULL,NULL),(103,'31_2025_hist',1,16,'Sebastia','Sanchez','2017-12-01','Activa',NULL,NULL,NULL,NULL),(104,'32_2025_hist',1,6,'Ignacio','Temprano','2018-12-01','Activa',NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `remitos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `remitos_detalle`
--

DROP TABLE IF EXISTS `remitos_detalle`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `remitos_detalle` (
  `id_detalle` int NOT NULL AUTO_INCREMENT,
  `id_remito` int NOT NULL,
  `id_insumo` int NOT NULL,
  `cantidad` int NOT NULL DEFAULT '1',
  `cantidad_devuelta` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id_detalle`),
  KEY `idx_rd_remito` (`id_remito`),
  KEY `idx_rd_insumo` (`id_insumo`),
  CONSTRAINT `fk_rd_insumo` FOREIGN KEY (`id_insumo`) REFERENCES `insumos` (`id_insumo`),
  CONSTRAINT `fk_rd_remito` FOREIGN KEY (`id_remito`) REFERENCES `remitos` (`id_remito`)
) ENGINE=InnoDB AUTO_INCREMENT=161 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `remitos_detalle`
--

LOCK TABLES `remitos_detalle` WRITE;
/*!40000 ALTER TABLE `remitos_detalle` DISABLE KEYS */;
INSERT INTO `remitos_detalle` VALUES (128,72,109,1,0),(129,73,110,1,0),(130,74,111,1,0),(131,75,112,1,0),(132,76,113,1,0),(133,77,114,1,0),(134,78,115,1,0),(135,79,116,1,0),(136,80,117,1,0),(137,81,118,1,0),(138,82,119,1,0),(139,83,120,1,0),(140,84,121,1,0),(141,85,122,1,0),(142,86,123,1,0),(143,87,124,1,0),(144,88,125,1,0),(145,89,126,1,0),(146,90,127,1,0),(147,91,128,1,0),(148,92,129,1,0),(149,93,130,1,0),(150,94,131,1,0),(151,95,132,1,0),(152,96,133,1,0),(153,97,134,1,0),(154,98,135,1,0),(155,99,136,1,0),(156,100,137,1,0),(157,101,138,1,0),(158,102,139,1,0),(159,103,140,1,0),(160,104,141,1,0);
/*!40000 ALTER TABLE `remitos_detalle` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `remitos_historicos_secuencia`
--

DROP TABLE IF EXISTS `remitos_historicos_secuencia`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `remitos_historicos_secuencia` (
  `anio` int NOT NULL,
  `ultimo_numero` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`anio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `remitos_historicos_secuencia`
--

LOCK TABLES `remitos_historicos_secuencia` WRITE;
/*!40000 ALTER TABLE `remitos_historicos_secuencia` DISABLE KEYS */;
INSERT INTO `remitos_historicos_secuencia` VALUES (2025,32);
/*!40000 ALTER TABLE `remitos_historicos_secuencia` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id_rol` int NOT NULL AUTO_INCREMENT,
  `nombre_rol` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_general_ci,
  `permisos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Permisos del rol en formato JSON',
  `fecha_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_rol`),
  UNIQUE KEY `uk_nombre_rol` (`nombre_rol`),
  CONSTRAINT `roles_chk_1` CHECK (json_valid(`permisos`))
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Roles de usuario con permisos';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'Superadministrador','Acceso total al sistema incluyendo gestión de usuarios','{\n  \"insumos\": [\"ver\", \"crear\", \"editar\", \"eliminar\", \"baja\"],\n  \"asignaciones\": [\"ver\", \"crear\", \"editar\", \"anular\", \"devolver\"],\n  \"sedes\": [\"ver\", \"crear\", \"editar\", \"eliminar\"],\n  \"telecom\": [\"ver\", \"crear\", \"editar\", \"eliminar\"],\n  \"reportes\": [\"ver\", \"exportar\"],\n  \"usuarios\": [\"ver\", \"crear\", \"editar\", \"eliminar\", \"cambiar_rol\", \"reset_password\"],\n  \"auditoria\": [\"ver_todo\"],\n  \"sistema\": [\"backup\"],\n  \"areas\": [\"ver\", \"crear\", \"editar\", \"eliminar\"]\n}','2025-11-06 09:08:29'),(2,'Administrador','Gestión completa de inventario sin acceso a usuarios','{\n  \"insumos\": [\"ver\", \"crear\", \"editar\", \"eliminar\", \"baja\"],\n  \"asignaciones\": [\"ver\", \"crear\", \"editar\", \"anular\", \"devolver\"],\n  \"sedes\": [\"ver\", \"crear\", \"editar\", \"eliminar\"],\n  \"telecom\": [\"ver\", \"crear\", \"editar\", \"eliminar\"],\n  \"reportes\": [\"ver\", \"exportar\"],\n  \"usuarios\": [\"ver\"],\n  \"areas\": [\"ver\", \"crear\", \"editar\", \"eliminar\"]\n}','2025-11-06 09:08:29'),(3,'Operador','Operaciones diarias de inventario y asignaciones','{\n  \"insumos\": [\"ver\", \"crear\", \"editar\", \"baja\"],\n  \"asignaciones\": [\"ver\", \"crear\", \"editar\", \"devolver\"],\n  \"sedes\": [\"ver\", \"crear\"],\n  \"telecom\": [\"ver\", \"crear\"],\n  \"reportes\": [\"ver\", \"exportar\"],\n  \"areas\": [\"ver\", \"crear\"]\n}','2025-11-06 09:08:29'),(4,'Consultor','Solo lectura y generación de reportes','{\n  \"insumos\": [\"ver\"],\n  \"asignaciones\": [\"ver\", \"crear\"],\n  \"sedes\": [\"ver\"],\n  \"telecom\": [\"ver\"],\n  \"reportes\": [\"ver\"],\n  \"areas\": [\"ver\"]\n}','2025-11-06 09:08:29');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sede_areas`
--

DROP TABLE IF EXISTS `sede_areas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sede_areas` (
  `id_sede_area` int NOT NULL AUTO_INCREMENT,
  `id_sede` int NOT NULL,
  `id_area` int NOT NULL,
  `activa` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_sede_area`),
  UNIQUE KEY `unique_sede_area` (`id_sede`,`id_area`),
  KEY `fk_sede_areas_sede` (`id_sede`),
  KEY `fk_sede_areas_area` (`id_area`),
  CONSTRAINT `fk_sede_areas_area` FOREIGN KEY (`id_area`) REFERENCES `areas` (`id_area`),
  CONSTRAINT `fk_sede_areas_sede` FOREIGN KEY (`id_sede`) REFERENCES `sedes` (`id_sede`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sede_areas`
--

LOCK TABLES `sede_areas` WRITE;
/*!40000 ALTER TABLE `sede_areas` DISABLE KEYS */;
/*!40000 ALTER TABLE `sede_areas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sedes`
--

DROP TABLE IF EXISTS `sedes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sedes` (
  `id_sede` int NOT NULL AUTO_INCREMENT,
  `id_localidad` int NOT NULL,
  `nombre_sede` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `direccion` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `observaciones` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `delegado_nombre` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `delegado_apellido` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `delegado_telefono` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `responsable_nombre` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `responsable_apellido` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `responsable_telefono` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id_sede`),
  KEY `id_localidad` (`id_localidad`),
  CONSTRAINT `SEDES_ibfk_1` FOREIGN KEY (`id_localidad`) REFERENCES `localidades` (`id_localidad`)
) ENGINE=InnoDB AUTO_INCREMENT=56 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sedes`
--

LOCK TABLES `sedes` WRITE;
/*!40000 ALTER TABLE `sedes` DISABLE KEYS */;
INSERT INTO `sedes` VALUES (1,1,'Central','Belgrano Y Pueyrredon','Testeador',NULL,NULL,NULL,NULL,NULL,NULL),(2,1,'Viedma valle inferior','Mexico y Caseros','Test obs','Hernan','Araya','2920609080','Luis','Dalfonso','2920598410'),(3,1,'Caina Varones','Ex Ruta Nº3 Parcela A68, km 1800',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(4,1,'Caina Mujeres','Tierra del Fuego Nº336',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(5,1,'Casa Abrigo Niños','J. M. Guido Nº122',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(6,1,'Globito Azul','Las Azucenas Nº595',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(7,1,'Ecos Galpon Amarillo','O`Higgins N.º 406',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(8,1,'Ecos Lavalle','Calle 18 y 13',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(9,1,'Ecos Casita del Nehuen','Bº Guido Esc. 35 Planta Baja Dpto D, Calle Harosteguy ',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(10,1,'Ecos Ceferino','Mexico N°585',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(11,1,'La Viruta','Ex RN Nº19 – Calle:Susana Rinaldi y Enrique Cadicamo Bº Patagonia  ',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(12,1,'Sonoridad Andina','Bº P. Independencia calle Las Amapolas Nº05',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(13,1,'Hueche El Condor ','Club de Los Amigos calle 13 y 67',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(14,2,'Sede','Belgrano Nº265',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(15,2,'Hueche','Julio A Roca N.º entre Chañares y Tamariscos Bº La Rivera\r\nEspacio que les brinda el Municipio',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(16,3,'Sede','Av Belgrano N.º 1625 ',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(17,3,'Ecos','Av Belgrano N.º 1625 ',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(18,4,'Sede','Comparten of. con el municipio\r\n',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(19,4,'Casa Abrigo Niños','Bº Villa Hiparsa Modulo 1 Planta Baja ',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(20,5,'Sede','Alem Nº830',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(21,6,'Sede','Uruaguay N.º234',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(22,6,'OF. FORTALECIMIENTO FAMILIAR','Falta direccion',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(23,7,'Sede','Arrieta Nº309',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(24,7,'Ecos','Guemes y Juan Jose Paso BºIndustrial',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(25,8,'Sede','San Martin s/n',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(26,9,'Sede','Av.Roca y Sarasola',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(27,10,'Sede','12 de Octubre y Entre Rios ',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(28,11,'Sede','Sarmiento Nº248',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(29,12,'Sede','Alem Nº 890 se traslada a\r\nJuan B Justo 510',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(30,12,'Hueche','Ramon Tuero Nº870',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(31,13,'Sede','Guemes Nº284',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(32,14,'Sede ','Malvinas Nº 292',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(33,15,'Ecos','Adolfo Saiz Nº341 Bº San Martin ',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(34,16,'Sede','Rodhe 170',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(35,16,'ECOS Of. de Fortalecimiento Familiar ','Rodhe 350',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(36,16,'Caina Varones','Ushuaia Nº2384',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(37,16,'Caina Mujeres','Bariloche Nº2296',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(38,17,'Sede','Av. Peron ',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(39,18,'Sede y Ecos','09 de Julio Nº 59',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(40,19,'Sede','Gral Roca Nº 1472',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(41,19,'CAD Rayito de oro','Chile y Cerros Colorados',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(42,20,'Sede','España y Rivadavia Nº 256',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(43,21,'Sede','Santa Rosa Nº 70\r\nMunicipio',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(44,22,'Sede','Av. San Martin y 12 de Octubre',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(45,23,'Sede','Av. 25 de Mayo Nº576',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(46,24,'Sede','Jicha Nº35',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(47,25,'Sede','San Jose S/N',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(48,26,'Sede','09 de Julio Nº 748',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(49,27,'SEDE ECOS HUECHE – EQUIPOS \r\nTERRITORIALES Nº \"7,8,9\"','Perito Moreno Nº 1435',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(50,27,'Casa Abrigo Niños','Perito Moreno Nº 1435',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(51,27,'CAINA ADOLESCENTES VARONES \r\nCAINA ADOLESCENTES MUJERES','Albarracin Nº568',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(52,28,'Ecos Hueche','Rivadavia Nº 2200',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(53,28,'Casa Abrigo Niños','Bº Irigoyen Padre Guillermo Nº 332',NULL,NULL,NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `sedes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sedes_internet`
--

DROP TABLE IF EXISTS `sedes_internet`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sedes_internet` (
  `id_internet` int NOT NULL AUTO_INCREMENT,
  `id_sede` int NOT NULL,
  `proveedor` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `tipo_conexion` enum('ADSL','Fibra óptica','4G','5G','Satelital','Radioenlace') COLLATE utf8mb4_general_ci NOT NULL,
  `velocidad_mbps` int DEFAULT NULL,
  `simetrico` tinyint(1) NOT NULL DEFAULT '0',
  `tiene_wifi` tinyint(1) NOT NULL DEFAULT '0',
  `estado_servicio` enum('Activo','Pendiente','De Baja','Baja por Traslado') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Pendiente',
  `instancia_pendiente` enum('Solicitud de presupuesto','Autorización superior','Servicio tarifado') COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Instancia específica cuando el estado es Pendiente',
  `fecha_solicitud_autorizacion` date DEFAULT NULL COMMENT 'Fecha de solicitud cuando la instancia es Autorización superior',
  `archivo_autorizacion` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Ruta del archivo PDF de autorización superior',
  `archivo_autorizacion_traslado` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'PDF de autorización del traslado',
  `fecha_instalacion` date DEFAULT NULL COMMENT 'Fecha programada de instalación cuando el estado es Pendiente',
  `fecha_baja` date DEFAULT NULL COMMENT 'Fecha en que el servicio pasó a estado De Baja',
  `fecha_traslado` date DEFAULT NULL COMMENT 'Fecha en que se dio de baja por traslado',
  `observaciones` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `id_servicio_trasladado_a` int DEFAULT NULL COMMENT 'ID del nuevo servicio creado tras el traslado',
  `id_servicio_trasladado_desde` int DEFAULT NULL COMMENT 'ID del servicio anterior del cual proviene este traslado',
  PRIMARY KEY (`id_internet`),
  KEY `idx_si_sede` (`id_sede`),
  KEY `idx_si_estado` (`estado_servicio`),
  KEY `idx_instancia_pendiente` (`instancia_pendiente`),
  KEY `idx_fecha_instalacion` (`fecha_instalacion`),
  KEY `idx_fecha_baja` (`fecha_baja`),
  KEY `idx_trasladado_a` (`id_servicio_trasladado_a`),
  KEY `idx_trasladado_desde` (`id_servicio_trasladado_desde`),
  KEY `idx_fecha_traslado` (`fecha_traslado`),
  CONSTRAINT `fk_si_sede` FOREIGN KEY (`id_sede`) REFERENCES `sedes` (`id_sede`)
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sedes_internet`
--

LOCK TABLES `sedes_internet` WRITE;
/*!40000 ALTER TABLE `sedes_internet` DISABLE KEYS */;
INSERT INTO `sedes_internet` VALUES (1,48,'ISP Group','Fibra óptica',50,0,0,'Activo',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Se solicito cambio de proveedor el 19/08/25 de Internet Cable Vision a ISP Group.',NULL,NULL),(2,36,'Telcocom','Fibra óptica',100,0,1,'Activo',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Se solicito el servicio el 14/04/25 ',NULL,NULL),(3,37,'Telcocom','Fibra óptica',100,0,1,'Activo',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Se solicito el servicio el 14/04/25 ',NULL,NULL),(4,5,'Agilnet','Fibra óptica',50,0,1,'Activo',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Se solicito el servicio el 11/04/25 ',NULL,NULL),(5,3,'Leo Starlink - 01 TB','Satelital',NULL,0,0,'Activo',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Se solicito el servicio el 30/06/25 ',NULL,NULL),(6,35,'Telcocom','Fibra óptica',100,0,1,'Activo',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Se solicito el servicio el 14/07/25',NULL,NULL),(7,47,'ISP Group','Fibra óptica',300,0,0,'Pendiente',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Se solicito el servicio el 11/08/25',NULL,NULL),(8,10,'Agilnet','Fibra óptica',100,0,1,'Activo',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Se solicito el servicio el 19/08/25',NULL,NULL),(9,13,'Agilnet','Fibra óptica',100,0,1,'Activo',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Se solicito el servicio el 01/09/25',NULL,NULL),(10,14,'Telcocom','Fibra óptica',100,0,0,'Pendiente',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Se solicito el cambio de proveedor el 22/09/25 de Altec a Telcocom.',NULL,NULL),(11,42,'Telcocom','Fibra óptica',100,1,1,'Activo',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Se solicito cambio de proveedor el 19/04/24 de Davitel a Telcocom',NULL,NULL),(12,9,'Agilnet','Fibra óptica',50,0,1,'Activo',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Se solicito cambio de proveedor el 09/04/24 de Speedy Movistar a Agilnet',NULL,NULL),(13,7,'Agilnet','Fibra óptica',50,0,1,'Activo',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Se solicito cambio de proveedor el 09/04/24 de Speedy Movistar a Agilnet',NULL,NULL),(14,30,'ISP Group','Fibra óptica',100,0,1,'Activo',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Se solicito el servicio el 31/05/24',NULL,NULL),(15,43,'Telcocom','Fibra óptica',100,1,1,'Activo',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Se solicito el servicio el 19/03/24',NULL,NULL),(16,23,'Patagonia Data Grupo Equis S.A','Fibra óptica',100,0,1,'Activo',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Se solicito cambio de proveedor el 14/08/24 de @Sur Internet a Patagonia Data. ',NULL,NULL),(17,21,'Sin servicios','Fibra óptica',NULL,0,0,'De Baja',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Se solicito la baja del servicio, el 16/10/24 debido a baja de contrato inmobiliario',NULL,NULL),(18,31,'Telcocom','Fibra óptica',100,1,1,'Activo',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(19,38,'Telcocom','Fibra óptica',100,1,1,'Activo',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(20,22,'Telcocom','Fibra óptica',100,0,1,'Activo',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(21,18,'ISP Group','Fibra óptica',100,0,1,'Activo',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(22,16,'ISP Group','Fibra óptica',100,0,1,'Activo',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(23,6,'Agilnet','Fibra óptica',50,0,1,'Activo',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(24,21,'Comparte Internet con el municipio','Fibra óptica',NULL,0,0,'Activo',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(25,49,'ALTEC','Fibra óptica',NULL,0,0,'Activo',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(26,27,'Patagonia Data Grupo Equis','Fibra óptica',100,0,1,'Activo',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(27,39,'Telcocom','Fibra óptica',100,1,1,'Activo',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(28,40,'Telcocom','Fibra óptica',100,0,1,'Activo',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(29,25,'Patagonia Data Grupo Equis','Fibra óptica',100,0,1,'Activo',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(30,20,'ISP Group','Fibra óptica',100,0,0,'Activo',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(31,29,'ISP Group','Fibra óptica',NULL,0,1,'Activo',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(32,2,'Altec','ADSL',NULL,0,0,'Activo',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(33,1,'Altec','ADSL',50,0,0,'Activo',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `sedes_internet` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sedes_planos`
--

DROP TABLE IF EXISTS `sedes_planos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sedes_planos` (
  `id_plano` int NOT NULL AUTO_INCREMENT,
  `id_sede` int NOT NULL,
  `tipo_plano` enum('Base','Red','Vigilancia') COLLATE utf8mb4_general_ci NOT NULL,
  `archivo` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `fecha_subida` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_plano`),
  KEY `idx_sp_sede` (`id_sede`),
  KEY `idx_sp_tipo` (`tipo_plano`),
  CONSTRAINT `fk_sp_sede` FOREIGN KEY (`id_sede`) REFERENCES `sedes` (`id_sede`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sedes_planos`
--

LOCK TABLES `sedes_planos` WRITE;
/*!40000 ALTER TABLE `sedes_planos` DISABLE KEYS */;
/*!40000 ALTER TABLE `sedes_planos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sedes_red_dispositivos`
--

DROP TABLE IF EXISTS `sedes_red_dispositivos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sedes_red_dispositivos` (
  `id_dispositivo` int NOT NULL AUTO_INCREMENT,
  `id_sede` int NOT NULL,
  `tipo_dispositivo` enum('Switch','Router','UPS','AP','Firewall') COLLATE utf8mb4_general_ci NOT NULL,
  `marca` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `modelo` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cantidad` int NOT NULL DEFAULT '1',
  `ubicacion` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `estado` enum('Activo','De Baja') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Activo',
  `observaciones` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id_dispositivo`),
  KEY `idx_srd_sede` (`id_sede`),
  KEY `idx_srd_tipo` (`tipo_dispositivo`),
  CONSTRAINT `fk_srd_sede` FOREIGN KEY (`id_sede`) REFERENCES `sedes` (`id_sede`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sedes_red_dispositivos`
--

LOCK TABLES `sedes_red_dispositivos` WRITE;
/*!40000 ALTER TABLE `sedes_red_dispositivos` DISABLE KEYS */;
/*!40000 ALTER TABLE `sedes_red_dispositivos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sedes_telefonia_lineas`
--

DROP TABLE IF EXISTS `sedes_telefonia_lineas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sedes_telefonia_lineas` (
  `id_linea` int NOT NULL AUTO_INCREMENT,
  `id_sede` int NOT NULL,
  `tipo_linea` enum('Fija','Móvil') COLLATE utf8mb4_general_ci NOT NULL,
  `operador` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `numero` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dispositivo_modelo` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `interno_ext` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `estado` enum('Activa','Pendiente','De Baja') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Activa',
  `observaciones` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id_linea`),
  KEY `idx_stl_sede` (`id_sede`),
  KEY `idx_stl_tipo` (`tipo_linea`),
  CONSTRAINT `fk_stl_sede` FOREIGN KEY (`id_sede`) REFERENCES `sedes` (`id_sede`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sedes_telefonia_lineas`
--

LOCK TABLES `sedes_telefonia_lineas` WRITE;
/*!40000 ALTER TABLE `sedes_telefonia_lineas` DISABLE KEYS */;
INSERT INTO `sedes_telefonia_lineas` VALUES (1,1,'Fija','','2920 425211','','','Activa',NULL),(2,4,'Fija','','2920 424111','','','Activa',NULL),(3,9,'Fija','','2920 420710','','','Activa',NULL),(4,6,'Fija','','2920 429962','','','Activa',NULL),(5,42,'Fija','','2994 983607','','','Activa',NULL),(6,39,'Fija','','2994 771637','','','Activa',NULL),(7,51,'Fija','','2944 436726','','','Activa',NULL),(8,48,'Fija','','2940 432671','','','Activa',NULL),(9,25,'Fija','','2946 480881','','','Activa',NULL),(10,31,'Fija','','2984 465377','','','Activa',NULL),(11,31,'Fija','','2984 465377','','','Activa',NULL),(12,33,'Fija','','2984 480063','','','Activa',NULL),(13,38,'Fija','','2984 450740','','','Activa',NULL),(14,19,'Fija','','2934 481004','','','Activa',NULL),(15,20,'Fija','','2934 493464','','','Activa',NULL),(16,29,'Fija','','2931 499004','','','Activa',NULL),(17,46,'Fija','','2934 492584','','','Activa',NULL),(18,28,'Fija','','2934 494249','','','Activa',NULL),(19,16,'Fija','','2934 423032 / 421178','','','Activa',NULL),(20,2,'Fija','Movistar','2920425211','','','Activa',NULL),(21,1,'Fija','Movistar','2920558963','','17','Pendiente',NULL);
/*!40000 ALTER TABLE `sedes_telefonia_lineas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sedes_vigilancia`
--

DROP TABLE IF EXISTS `sedes_vigilancia`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sedes_vigilancia` (
  `id_vigilancia` int NOT NULL AUTO_INCREMENT,
  `id_sede` int NOT NULL,
  `proveedor` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `estado_servicio` enum('Activo','Pendiente','De Baja') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Activo',
  `observaciones` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id_vigilancia`),
  KEY `idx_svg_sede` (`id_sede`),
  CONSTRAINT `fk_svg_sede` FOREIGN KEY (`id_sede`) REFERENCES `sedes` (`id_sede`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sedes_vigilancia`
--

LOCK TABLES `sedes_vigilancia` WRITE;
/*!40000 ALTER TABLE `sedes_vigilancia` DISABLE KEYS */;
/*!40000 ALTER TABLE `sedes_vigilancia` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sedes_vigilancia_dispositivos`
--

DROP TABLE IF EXISTS `sedes_vigilancia_dispositivos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sedes_vigilancia_dispositivos` (
  `id_vigilancia_dispositivo` int NOT NULL AUTO_INCREMENT,
  `id_vigilancia` int NOT NULL,
  `tipo_dispositivo` enum('DVR','NVR','Cámara','Sensor','Monitor') COLLATE utf8mb4_general_ci NOT NULL,
  `marca` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `modelo` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cantidad` int NOT NULL DEFAULT '1',
  `ubicacion` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `estado` enum('Activo','De Baja') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Activo',
  PRIMARY KEY (`id_vigilancia_dispositivo`),
  KEY `idx_svd_vig` (`id_vigilancia`),
  CONSTRAINT `fk_svd_vig` FOREIGN KEY (`id_vigilancia`) REFERENCES `sedes_vigilancia` (`id_vigilancia`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sedes_vigilancia_dispositivos`
--

LOCK TABLES `sedes_vigilancia_dispositivos` WRITE;
/*!40000 ALTER TABLE `sedes_vigilancia_dispositivos` DISABLE KEYS */;
/*!40000 ALTER TABLE `sedes_vigilancia_dispositivos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sesiones`
--

DROP TABLE IF EXISTS `sesiones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sesiones` (
  `id_sesion` int NOT NULL AUTO_INCREMENT,
  `id_usuario` int NOT NULL,
  `token_sesion` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `fecha_inicio` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_ultimo_acceso` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fecha_cierre` datetime DEFAULT NULL,
  `activa` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_sesion`),
  UNIQUE KEY `uk_token_sesion` (`token_sesion`),
  KEY `idx_token` (`token_sesion`),
  KEY `idx_usuario` (`id_usuario`),
  KEY `idx_activa` (`activa`),
  CONSTRAINT `fk_sesiones_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=91 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Sesiones activas de usuarios';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sesiones`
--

LOCK TABLES `sesiones` WRITE;
/*!40000 ALTER TABLE `sesiones` DISABLE KEYS */;
INSERT INTO `sesiones` VALUES (66,10,'6bb7e2cec3f3be791b07c1842a373ea083c94a584446eb4da8cf256966ed7e3e','10.114.85.189','Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0','2025-12-03 09:38:05','2025-12-04 06:44:47','2025-12-04 06:44:47',0),(67,10,'c06e1cc1351d4a6f8e94439a4fce7a559944990a6897bc84c1abb3efd751a304','10.114.85.189','Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0','2025-12-04 06:44:47','2025-12-04 10:46:34','2025-12-04 10:46:34',0),(68,10,'c1f0ab6098fc37ac24b85e91eec60ffd285003cfc23455acd611fae8166d8f29','10.114.85.189','Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0','2025-12-05 07:17:51','2025-12-05 10:12:52','2025-12-05 10:12:52',0),(69,10,'52a4db106b6e8bec85725cf65f97f305dfeebf6670fcc4bb84ce2f49cb1da64d','10.114.85.189','Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0','2025-12-05 10:12:52','2025-12-09 05:07:07','2025-12-09 05:07:07',0),(70,10,'b36fb8b037b8eabf9c1ffbaf74e56b55a16b36b86b7f519d4876523322098d22','10.114.85.189','Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0','2025-12-09 05:07:07','2025-12-09 05:08:57','2025-12-09 05:08:57',0),(71,11,'4db8e05640f78b8861c9484a25af0768f0004154fcee13ca8fb9dcfbb72c383d','10.114.85.189','Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0','2025-12-09 05:09:04','2025-12-09 05:57:42','2025-12-09 05:57:42',0),(72,10,'d1a7ccdc738047ba9e38b7e236d4c00818aea4ee5cb9469e8ebb0008e1dd7961','10.114.85.189','Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0','2025-12-09 05:57:49','2025-12-09 06:49:42','2025-12-09 06:49:42',0),(73,10,'2eb7e506dbe260a495a9658b2f32005e063e270a6df978d205d5e6fba2da9413','10.114.85.189','Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0','2025-12-09 06:49:47','2025-12-09 08:19:02','2025-12-09 08:19:02',0),(74,10,'c3f487065360aa1cf4e30e46128da3ea02bade095ece45b1aa67cd0f55146eca','10.114.85.189','Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0','2025-12-09 08:19:02','2025-12-09 09:03:14','2025-12-09 09:03:14',0),(75,10,'51f710b278fc18c370eca250f841bdb56160f677c56a2fe2eb3fab00707893b7','10.114.85.189','Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0','2025-12-09 09:03:14','2025-12-09 10:54:00','2025-12-09 10:54:00',0),(76,10,'cac2511f33bb22f3d71549e41d44e73f083df7d1b1c9df5853404eff012237a7','10.114.85.189','Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0','2025-12-09 10:54:01','2025-12-09 10:54:52','2025-12-09 10:54:52',0),(77,11,'5c38d2192d1a1325b9b7f73c3aaefbdc825c61cb2fa836bcae6e1a4697e15966','10.114.85.189','Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0','2025-12-09 10:55:00','2025-12-09 10:55:54','2025-12-09 10:55:54',0),(78,10,'de5c14665d3f114563a4caf3392717f820020718109fa51ed8f6f024eaaece42','10.114.85.189','Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0','2025-12-09 10:55:58','2025-12-09 10:56:57','2025-12-09 10:56:57',0),(79,11,'fce8c653c6a1a915f6b879b4b0b6a8c2f5f17a7e8332c9d81cf5ac7e54777b3b','10.114.85.189','Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0','2025-12-09 10:57:06','2025-12-10 06:23:04','2025-12-10 06:23:04',0),(80,10,'3420bdaa951fce4e491d131d7a184c293d91356927d1731b12ba21892b624394','10.114.85.189','Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0','2025-12-10 06:15:52','2025-12-10 06:20:34','2025-12-10 06:20:34',0),(81,11,'0449202d90e793b3cbf8b45dc4ae9a7e2d796fa2182b14fd410073a8390652e1','10.114.85.189','Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0','2025-12-10 06:23:04','2025-12-10 07:48:10','2025-12-10 07:48:10',0),(82,11,'530d64fa48b9b393056ff359bd6bc700cfedf4e79ffea68dbc3314514ff18be2','10.114.85.189','Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0','2025-12-10 07:48:10','2025-12-10 08:51:18','2025-12-10 08:51:18',0),(83,11,'20220f29a13285395f71ab7c891d6e1fa8431bb0036328ab3a70bfc905e7d012','10.114.85.189','Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0','2025-12-10 08:51:19','2025-12-10 09:22:29','2025-12-10 09:22:29',0),(84,11,'f831490a861ee27223ab5ecf64670a2806897616181bedd626082fa1f765db58','10.114.85.189','Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0','2025-12-10 09:22:29','2025-12-11 05:07:02','2025-12-11 05:07:02',0),(85,10,'99b6d00dcc5b857b7a05db9432a3feecc14705e13a4d4998f0ee0f98687c1def','10.114.85.189','Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0','2025-12-10 10:53:28','2025-12-10 10:53:28',NULL,1),(86,11,'11d49f523fb274a960cd48a114c30cbce842058581a54e7064b9cd95e117150d','10.114.85.189','Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0','2025-12-11 05:07:02','2025-12-11 07:52:06','2025-12-11 07:52:06',0),(87,11,'cf3daf788d06d9c4f9c834e113f254e5aa375ef30a95615d8fd88b5c07625bdd','10.114.85.189','Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0','2025-12-11 07:52:06','2025-12-11 09:44:38','2025-12-11 09:44:38',0),(88,11,'845956139c3f6b8ab16567e03efa100b4304a3885d2d1d9b56b05a3a33e0758c','10.114.85.189','Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0','2025-12-11 09:44:38','2025-12-11 10:51:06','2025-12-11 10:51:06',0),(89,11,'af20eb00a40952272a459557862869fb7ffb8614af74ce893561d84134260e2c','10.114.85.189','Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0','2025-12-11 10:51:06','2025-12-12 07:16:09','2025-12-12 07:16:09',0),(90,11,'8e3f2f843f30ccd52e0f17afe22474d5cc87d630ed12e0b92b244f43174ab10b','10.114.85.189','Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0','2025-12-12 07:16:09','2025-12-12 07:25:08',NULL,1);
/*!40000 ALTER TABLE `sesiones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuarios` (
  `id_usuario` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `apellido` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `id_rol` int NOT NULL,
  `permisos_personalizados` longtext COLLATE utf8mb4_general_ci COMMENT 'Permisos personalizados en formato JSON. Si es NULL, usa permisos del rol',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `fecha_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ultimo_acceso` datetime DEFAULT NULL,
  `modificado_por` int DEFAULT NULL COMMENT 'ID del usuario que realizó la última modificación',
  `fecha_modificacion` datetime DEFAULT NULL,
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `uk_username` (`username`),
  UNIQUE KEY `uk_email` (`email`),
  KEY `idx_username` (`username`),
  KEY `idx_email` (`email`),
  KEY `idx_activo` (`activo`),
  KEY `fk_usuarios_rol` (`id_rol`),
  CONSTRAINT `fk_usuarios_rol` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Usuarios del sistema';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (10,'admin','admin@ejemplo.com','$2y$10$Kjr0aN2Ic64oZpIoT4ww7OCDKs0UXDXrS62JPyPBuu0.yRFFGwDIO','Admin','Sistema',1,NULL,1,'2025-12-03 12:37:11','2025-12-10 10:53:28',NULL,'2025-12-10 10:53:28'),(11,'jvillaverde','jvillaverde@senaf.rionegro.gov.ar','$2y$10$zTsWCa/BegsmVmAtAeCyLu5gMAoNayH.pWE2py5z.hI7nxCm77T8u','Joaquin','Villaverde',3,'{\"insumos\":[\"ver\",\"crear\",\"editar\",\"baja\"],\"asignaciones\":[\"ver\",\"crear\",\"editar\",\"devolver\"],\"reportes\":[\"ver\"],\"sedes\":[\"ver\",\"crear\",\"editar\"],\"telecom\":[\"ver\",\"crear\",\"editar\"],\"areas\":[\"ver\",\"crear\",\"editar\"]}',1,'2025-12-09 05:08:21','2025-12-12 07:16:09',10,'2025-12-12 07:16:09'),(12,'hgonzalez','hgonzalez@senaf.rionegro.gov.ar','$2y$10$5TrKKSqU6LkC5yXJMwTvU.LR0ESQ0FJ6TZ5.FeqWARrVrlZpd6Hve','Heber','Gonzalez',3,NULL,1,'2025-12-10 06:16:38',NULL,10,NULL),(13,'dgarcia','dgarcia@senaf.rionegro.gov.ar','$2y$10$ABvRGP5wP31Opl.MNSd60uWqBC07jCm3ELGUyTU7taHZC2UGpiwt6','Diego','Garcia',1,NULL,1,'2025-12-10 06:18:32',NULL,10,NULL),(14,'egimenez','egimenez@senaf.rionegro.gov.ar','$2y$10$Iq4ccl2R.b0s61I.bfBju.q0VcfNgszgqVi9I2cE8qC6VcR6oSPF6','Vanesa','Gimenez',2,NULL,1,'2025-12-10 06:19:28',NULL,10,NULL),(15,'ftoledo','ftoledo@senaf.rionegro.gov.ar','$2y$10$8GK77ahi2S4RJzu.bahfNO8KMyJr63Q882/oyYgRMbNuc2u9op/wO','Facundo','Toledo',1,NULL,1,'2025-12-10 06:20:19',NULL,10,NULL);
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`joaquin`@`localhost`*/ /*!50003 TRIGGER `trg_usuarios_before_update` BEFORE UPDATE ON `usuarios` FOR EACH ROW BEGIN
    SET NEW.fecha_modificacion = NOW();
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Temporary view structure for view `v_insumos_completos`
--

DROP TABLE IF EXISTS `v_insumos_completos`;
/*!50001 DROP VIEW IF EXISTS `v_insumos_completos`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_insumos_completos` AS SELECT 
 1 AS `id_insumo`,
 1 AS `nombre_insumo`,
 1 AS `tipo_insumo`,
 1 AS `subcategoria_varios`,
 1 AS `numero_serie`,
 1 AS `id_fisico`,
 1 AS `cantidad`,
 1 AS `fecha_adquisicion`,
 1 AS `estado`,
 1 AS `punto_stock`,
 1 AS `area_actual`,
 1 AS `sede_actual`,
 1 AS `nombre_localidad`,
 1 AS `nombre_zona`*/;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `zonas`
--

DROP TABLE IF EXISTS `zonas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `zonas` (
  `id_zona` int NOT NULL AUTO_INCREMENT,
  `nombre_zona` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id_zona`),
  UNIQUE KEY `nombre_zona` (`nombre_zona`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `zonas`
--

LOCK TABLES `zonas` WRITE;
/*!40000 ALTER TABLE `zonas` DISABLE KEYS */;
INSERT INTO `zonas` VALUES (4,'Alto Valle Centro'),(2,'Alto Valle Este'),(6,'Alto Valle Oeste I'),(7,'Alto Valle Oeste II'),(8,'Alto Valle Oeste III'),(10,'Andina'),(3,'Atlantica'),(11,'El Bolson'),(9,'Linea Sur'),(1,'Valle Inferior'),(5,'Valle Medio');
/*!40000 ALTER TABLE `zonas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Final view structure for view `v_insumos_completos`
--

/*!50001 DROP VIEW IF EXISTS `v_insumos_completos`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_insumos_completos` AS select `i`.`id_insumo` AS `id_insumo`,`i`.`nombre_insumo` AS `nombre_insumo`,`i`.`tipo_insumo` AS `tipo_insumo`,`i`.`subcategoria_varios` AS `subcategoria_varios`,`i`.`numero_serie` AS `numero_serie`,`i`.`id_fisico` AS `id_fisico`,`i`.`cantidad` AS `cantidad`,`i`.`fecha_adquisicion` AS `fecha_adquisicion`,`i`.`estado` AS `estado`,`ps`.`nombre_punto` AS `punto_stock`,`ar`.`nombre_area` AS `area_actual`,`s`.`nombre_sede` AS `sede_actual`,`l`.`nombre_localidad` AS `nombre_localidad`,`z`.`nombre_zona` AS `nombre_zona` from (((((`insumos` `i` left join `puntos_stock` `ps` on((`i`.`id_punto_stock_actual` = `ps`.`id_punto_stock`))) left join `areas` `ar` on((`i`.`id_area_asignacion_actual` = `ar`.`id_area`))) left join `sedes` `s` on((`i`.`id_sede_actual` = `s`.`id_sede`))) left join `localidades` `l` on((`s`.`id_localidad` = `l`.`id_localidad`))) left join `zonas` `z` on((`l`.`id_zona` = `z`.`id_zona`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-12-12 11:19:11
