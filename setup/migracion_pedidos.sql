-- Script de migración COMPLETO para módulo Pedidos, Pendientes y Entregas
-- Versión: 1.21
-- Autor: Antigravity
-- Fecha: 2026-03-13

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------
-- 1. Tabla `pedidos` (Estructura base + Logística)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `pedidos` (
  `id_pedido` INT NOT NULL AUTO_INCREMENT,
  `tipo` ENUM('Mantenimiento', 'Reparación', 'Soporte', 'Pedido Insumo') NOT NULL,
  `descripcion` TEXT NOT NULL,
  `solicitante_nombre` VARCHAR(100) NOT NULL,
  `solicitante_apellido` VARCHAR(100) DEFAULT NULL,
  `solicitante_telefono` VARCHAR(50) DEFAULT NULL,
  `solicitante_email` VARCHAR(100) DEFAULT NULL,
  `prioridad` ENUM('Baja', 'Media', 'Alta') NOT NULL DEFAULT 'Media',
  `estado` ENUM('Pendiente', 'En Proceso', 'Preparado', 'Completado', 'Rechazado') NOT NULL DEFAULT 'Pendiente',
  `id_usuario_solicitante` INT NOT NULL,
  `id_sede` INT NOT NULL,
  `id_area` INT DEFAULT NULL,
  `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `asignado_a` INT DEFAULT NULL,
  `id_insumo_relacionado` INT DEFAULT NULL,
  `insumo_relacionado` VARCHAR(255) DEFAULT NULL,
  `pdf_nota` VARCHAR(255) DEFAULT NULL,
  `id_remito` INT DEFAULT NULL,
  
  -- Campos de Logística y Entregas
  `metodo_entrega` ENUM('No aplica', 'Envío', 'Retiro') NOT NULL DEFAULT 'No aplica',
  `estado_entrega` ENUM('Pendiente', 'Preparado', 'Enviado', 'Entregado') NOT NULL DEFAULT 'Pendiente',
  `fecha_entrega` DATETIME DEFAULT NULL,
  `fecha_estimada_entrega` DATE DEFAULT NULL,
  `receptor_nombre` VARCHAR(255) DEFAULT NULL,
  `notas_entrega` TEXT DEFAULT NULL,

  PRIMARY KEY (`id_pedido`),
  INDEX `idx_pedidos_estado` (`estado` ASC),
  INDEX `idx_pedidos_estado_entrega` (`estado_entrega` ASC),
  CONSTRAINT `fk_pedidos_usuario` FOREIGN KEY (`id_usuario_solicitante`) REFERENCES `usuarios` (`id_usuario`),
  CONSTRAINT `fk_pedidos_sede` FOREIGN KEY (`id_sede`) REFERENCES `sedes` (`id_sede`),
  CONSTRAINT `fk_pedidos_area` FOREIGN KEY (`id_area`) REFERENCES `areas` (`id_area`) ON DELETE SET NULL,
  CONSTRAINT `fk_pedidos_asignado` FOREIGN KEY (`asignado_a`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL,
  CONSTRAINT `fk_pedidos_insumo` FOREIGN KEY (`id_insumo_relacionado`) REFERENCES `insumos` (`id_insumo`) ON DELETE SET NULL,
  CONSTRAINT `fk_pedidos_remito` FOREIGN KEY (`id_remito`) REFERENCES `remitos` (`id_remito`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Migración para bases existentes (si ya tenían Pedidos pero no las nuevas columnas)
ALTER TABLE `pedidos` 
MODIFY COLUMN `estado` ENUM('Pendiente', 'En Proceso', 'Preparado', 'Completado', 'Rechazado') NOT NULL DEFAULT 'Pendiente';

ALTER TABLE `pedidos` 
ADD COLUMN IF NOT EXISTS `id_remito` INT DEFAULT NULL AFTER `pdf_nota`,
ADD COLUMN IF NOT EXISTS `metodo_entrega` ENUM('No aplica', 'Envío', 'Retiro') NOT NULL DEFAULT 'No aplica' AFTER `id_remito`,
ADD COLUMN IF NOT EXISTS `estado_entrega` ENUM('Pendiente', 'Preparado', 'Enviado', 'Entregado') NOT NULL DEFAULT 'Pendiente' AFTER `metodo_entrega`,
ADD COLUMN IF NOT EXISTS `fecha_entrega` DATETIME DEFAULT NULL AFTER `estado_entrega`,
ADD COLUMN IF NOT EXISTS `fecha_estimada_entrega` DATE DEFAULT NULL AFTER `fecha_entrega`,
ADD COLUMN IF NOT EXISTS `receptor_nombre` VARCHAR(255) DEFAULT NULL AFTER `fecha_estimada_entrega`,
ADD COLUMN IF NOT EXISTS `notas_entrega` TEXT DEFAULT NULL AFTER `receptor_nombre`;

-- -----------------------------------------------------
-- 2. Tabla `pedidos_informes`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `pedidos_informes` (
  `id_informe` INT NOT NULL AUTO_INCREMENT,
  `id_pedido` INT NOT NULL,
  `diagnostico` TEXT NULL,
  `trabajo_realizado` TEXT NULL,
  `resultado` ENUM('Solucionado', 'Sin Solución', 'Requiere Repuestos') NOT NULL,
  `fecha_informe` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_informe`),
  CONSTRAINT `fk_informes_pedido` FOREIGN KEY (`id_pedido`) REFERENCES `pedidos` (`id_pedido`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- 3. Tabla `pedidos_historial`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `pedidos_historial` (
  `id_historial` INT NOT NULL AUTO_INCREMENT,
  `id_pedido` INT NOT NULL,
  `id_usuario` INT NOT NULL,
  `accion` VARCHAR(50) NOT NULL,
  `detalle` TEXT NULL,
  `fecha` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_historial`),
  CONSTRAINT `fk_historial_pedido` FOREIGN KEY (`id_pedido`) REFERENCES `pedidos` (`id_pedido`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- 4. Tabla `pedidos_adjuntos`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `pedidos_adjuntos` (
  `id_adjunto` INT NOT NULL AUTO_INCREMENT,
  `id_pedido` INT NOT NULL,
  `id_usuario` INT NOT NULL,
  `nombre_archivo` VARCHAR(255) NOT NULL,
  `ruta_archivo` VARCHAR(255) NOT NULL,
  `fecha_carga` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_adjunto`),
  CONSTRAINT `fk_adjuntos_pedido` FOREIGN KEY (`id_pedido`) REFERENCES `pedidos` (`id_pedido`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- 5. Roles y Permisos (Actualización v1.21)
-- -----------------------------------------------------
-- Roles Administrativos
UPDATE `roles` 
SET `permisos` = JSON_SET(COALESCE(`permisos`, '{}'), '$.pedidos', JSON_ARRAY('ver_propios', 'crear', 'ver_todos', 'gestionar', 'asignar', 'informe', 'eliminar'))
WHERE `nombre_rol` LIKE '%Admin%' OR `nombre_rol` LIKE '%Super%' OR `id_rol` IN (1, 2);

-- Operador
UPDATE `roles` 
SET `permisos` = JSON_SET(COALESCE(`permisos`, '{}'), '$.pedidos', JSON_ARRAY('ver_propios', 'crear', 'ver_todos', 'gestionar', 'informe'))
WHERE `nombre_rol` = 'Operador' OR `id_rol` = 3;

-- Consultor
UPDATE `roles` 
SET `permisos` = JSON_SET(COALESCE(`permisos`, '{}'), '$.pedidos', JSON_ARRAY('ver_propios', 'ver_todos'))
WHERE `nombre_rol` = 'Consultor' OR `id_rol` = 4;

SET FOREIGN_KEY_CHECKS = 1;
