-- Script de migración para módulo Pedidos y Pendientes
-- Autor: Antigravity
-- Fecha: 2025-12-15
-- Actualizado: 2026-01-07

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------
-- Tabla `pedidos`
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
  `estado` ENUM('Pendiente', 'En Proceso', 'Completado', 'Rechazado') NOT NULL DEFAULT 'Pendiente',
  `id_usuario_solicitante` INT NOT NULL COMMENT 'Usuario del sistema que carga el pedido',
  `id_sede` INT NOT NULL,
  `id_area` INT DEFAULT NULL,
  `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `asignado_a` INT DEFAULT NULL,
  `id_insumo_relacionado` INT DEFAULT NULL,
  `insumo_relacionado` VARCHAR(255) DEFAULT NULL,
  `pdf_nota` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id_pedido`),
  INDEX `idx_pedidos_usuario` (`id_usuario_solicitante` ASC),
  INDEX `idx_pedidos_sede` (`id_sede` ASC),
  INDEX `idx_pedidos_estado` (`estado` ASC),
  INDEX `idx_pedidos_asignado` (`asignado_a` ASC),
  INDEX `idx_pedidos_insumo` (`id_insumo_relacionado` ASC),
  CONSTRAINT `fk_pedidos_usuario`
    FOREIGN KEY (`id_usuario_solicitante`)
    REFERENCES `usuarios` (`id_usuario`)
    ON DELETE RESTRICT
    ON UPDATE CASCADE,
  CONSTRAINT `fk_pedidos_sede`
    FOREIGN KEY (`id_sede`)
    REFERENCES `sedes` (`id_sede`)
    ON DELETE RESTRICT
    ON UPDATE CASCADE,
  CONSTRAINT `fk_pedidos_area`
    FOREIGN KEY (`id_area`)
    REFERENCES `areas` (`id_area`)
    ON DELETE SET NULL
    ON UPDATE CASCADE,
  CONSTRAINT `fk_pedidos_asignado`
    FOREIGN KEY (`asignado_a`)
    REFERENCES `usuarios` (`id_usuario`)
    ON DELETE SET NULL
    ON UPDATE CASCADE,
  CONSTRAINT `fk_pedidos_insumo`
    FOREIGN KEY (`id_insumo_relacionado`)
    REFERENCES `insumos` (`id_insumo`)
    ON DELETE SET NULL
    ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Tabla `pedidos_informes`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `pedidos_informes` (
  `id_informe` INT NOT NULL AUTO_INCREMENT,
  `id_pedido` INT NOT NULL,
  `diagnostico` TEXT NULL,
  `trabajo_realizado` TEXT NULL,
  `resultado` ENUM('Solucionado', 'Sin Solución', 'Requiere Repuestos') NOT NULL,
  `fecha_informe` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_informe`),
  INDEX `idx_informes_pedido` (`id_pedido` ASC),
  CONSTRAINT `fk_informes_pedido`
    FOREIGN KEY (`id_pedido`)
    REFERENCES `pedidos` (`id_pedido`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Tabla `pedidos_historial`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `pedidos_historial` (
  `id_historial` INT NOT NULL AUTO_INCREMENT,
  `id_pedido` INT NOT NULL,
  `id_usuario` INT NOT NULL,
  `accion` VARCHAR(50) NOT NULL,
  `detalle` TEXT NULL,
  `fecha` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_historial`),
  INDEX `idx_historial_pedido` (`id_pedido` ASC),
  CONSTRAINT `fk_historial_pedido`
    FOREIGN KEY (`id_pedido`)
    REFERENCES `pedidos` (`id_pedido`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_historial_usuario`
    FOREIGN KEY (`id_usuario`)
    REFERENCES `usuarios` (`id_usuario`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Tabla `pedidos_adjuntos`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `pedidos_adjuntos` (
  `id_adjunto` INT NOT NULL AUTO_INCREMENT,
  `id_pedido` INT NOT NULL,
  `id_usuario` INT NOT NULL,
  `nombre_archivo` VARCHAR(255) NOT NULL,
  `ruta_archivo` VARCHAR(255) NOT NULL,
  `fecha_carga` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_adjunto`),
  INDEX `idx_adjuntos_pedido` (`id_pedido` ASC),
  CONSTRAINT `fk_adjuntos_pedido`
    FOREIGN KEY (`id_pedido`)
    REFERENCES `pedidos` (`id_pedido`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_adjuntos_usuario`
    FOREIGN KEY (`id_usuario`)
    REFERENCES `usuarios` (`id_usuario`)
    ON DELETE RESTRICT
    ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Tabla `notas_pedidos` (para notas/comentarios adicionales)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `notas_pedidos` (
  `id_nota` INT NOT NULL AUTO_INCREMENT,
  `id_pedido` INT NOT NULL,
  `id_usuario` INT NOT NULL,
  `nota` TEXT NOT NULL,
  `fecha` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_nota`),
  INDEX `idx_notas_pedido` (`id_pedido` ASC),
  CONSTRAINT `fk_notas_pedido`
    FOREIGN KEY (`id_pedido`)
    REFERENCES `pedidos` (`id_pedido`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_notas_usuario`
    FOREIGN KEY (`id_usuario`)
    REFERENCES `usuarios` (`id_usuario`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- -----------------------------------------------------
-- MIGRACIONES PARA BASES DE DATOS EXISTENTES
-- Ejecutar solo si la tabla ya existe y falta alguna columna
-- -----------------------------------------------------

-- Agregar columna solicitante_apellido si no existe
-- ALTER TABLE `pedidos` ADD COLUMN IF NOT EXISTS `solicitante_apellido` VARCHAR(100) DEFAULT NULL AFTER `solicitante_nombre`;

-- Agregar columna id_insumo_relacionado si no existe
-- ALTER TABLE `pedidos` ADD COLUMN IF NOT EXISTS `id_insumo_relacionado` INT DEFAULT NULL AFTER `asignado_a`;
-- ALTER TABLE `pedidos` ADD INDEX `idx_pedidos_insumo` (`id_insumo_relacionado`);
-- ALTER TABLE `pedidos` ADD CONSTRAINT `fk_pedidos_insumo` FOREIGN KEY (`id_insumo_relacionado`) REFERENCES `insumos` (`id_insumo`) ON DELETE SET NULL ON UPDATE CASCADE;

-- -----------------------------------------------------
-- Roles y Permisos
-- Nota: Esto asume compatibilidad con JSON_SET (MariaDB 10.2+ / MySQL 5.7+)
-- -----------------------------------------------------

-- Insertar roles si no existen
INSERT IGNORE INTO `roles` (`nombre_rol`, `permisos`) VALUES ('Operador', '{}');
INSERT IGNORE INTO `roles` (`nombre_rol`, `permisos`) VALUES ('Consultor', '{}');

-- Actualizar permisos Super Administrador y Admin (con variaciones de nombre)
UPDATE `roles` 
SET `permisos` = JSON_SET(COALESCE(`permisos`, '{}'), '$.pedidos', JSON_ARRAY('ver_propios', 'crear', 'ver_todos', 'gestionar', 'asignar', 'informe', 'eliminar'))
WHERE `nombre_rol` LIKE '%Admin%' OR `nombre_rol` LIKE '%Super%' OR `id_rol` IN (1, 2);

-- Actualizar permisos Operador
UPDATE `roles` 
SET `permisos` = JSON_SET(COALESCE(`permisos`, '{}'), '$.pedidos', JSON_ARRAY('ver_propios', 'crear', 'ver_todos', 'gestionar', 'informe'))
WHERE `nombre_rol` = 'Operador' OR `id_rol` = 3;

-- Actualizar permisos Consultor
UPDATE `roles` 
SET `permisos` = JSON_SET(COALESCE(`permisos`, '{}'), '$.pedidos', JSON_ARRAY('ver_propios', 'ver_todos'))
WHERE `nombre_rol` = 'Consultor' OR `id_rol` = 4;

-- -----------------------------------------------------
-- VERIFICACIÓN: Ejecutar esta consulta para verificar los permisos
-- SELECT id_rol, nombre_rol, JSON_EXTRACT(permisos, '$.pedidos') as permisos_pedidos FROM roles;
-- -----------------------------------------------------
