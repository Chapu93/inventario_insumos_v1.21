-- =====================================================
-- MIGRACIÓN: Sistema de Usuarios, Roles y Auditoría
-- Descripción: Implementa sistema completo de autenticación
--              con roles, permisos y auditoría de acciones
-- Fecha: 2025-11-05
-- =====================================================

-- 1. Crear tabla de roles
CREATE TABLE IF NOT EXISTS `roles` (
  `id_rol` INT(11) NOT NULL AUTO_INCREMENT,
  `nombre_rol` VARCHAR(50) NOT NULL,
  `descripcion` TEXT NULL,
  `permisos` JSON NOT NULL COMMENT 'Permisos del rol en formato JSON',
  `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_rol`),
  UNIQUE KEY `uk_nombre_rol` (`nombre_rol`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Roles de usuario con permisos';

-- 2. Crear tabla de usuarios
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id_usuario` INT(11) NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `nombre` VARCHAR(100) NOT NULL,
  `apellido` VARCHAR(100) NOT NULL,
  `id_rol` INT(11) NOT NULL,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ultimo_acceso` DATETIME NULL,
  `modificado_por` INT(11) NULL COMMENT 'ID del usuario que realizó la última modificación',
  `fecha_modificacion` DATETIME NULL,
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `uk_username` (`username`),
  UNIQUE KEY `uk_email` (`email`),
  KEY `idx_username` (`username`),
  KEY `idx_email` (`email`),
  KEY `idx_activo` (`activo`),
  CONSTRAINT `fk_usuarios_rol` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Usuarios del sistema';

-- 3. Crear tabla de sesiones
CREATE TABLE IF NOT EXISTS `sesiones` (
  `id_sesion` INT(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` INT(11) NOT NULL,
  `token_sesion` VARCHAR(255) NOT NULL,
  `ip_address` VARCHAR(45) NULL,
  `user_agent` VARCHAR(255) NULL,
  `fecha_inicio` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_ultimo_acceso` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fecha_cierre` DATETIME NULL,
  `activa` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_sesion`),
  UNIQUE KEY `uk_token_sesion` (`token_sesion`),
  KEY `idx_token` (`token_sesion`),
  KEY `idx_usuario` (`id_usuario`),
  KEY `idx_activa` (`activa`),
  CONSTRAINT `fk_sesiones_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Sesiones activas de usuarios';

-- 4. Crear tabla de auditoría
CREATE TABLE IF NOT EXISTS `auditoria_acciones` (
  `id_auditoria` BIGINT(20) NOT NULL AUTO_INCREMENT,
  `id_usuario` INT(11) NOT NULL,
  `id_sesion` INT(11) NULL,
  `accion` VARCHAR(100) NOT NULL COMMENT 'crear_insumo, editar_insumo, eliminar_insumo, etc.',
  `modulo` VARCHAR(50) NOT NULL COMMENT 'insumos, asignaciones, reportes, usuarios, etc.',
  `descripcion` TEXT NOT NULL,
  `entidad_tipo` VARCHAR(50) NULL COMMENT 'insumo, asignacion, usuario, etc.',
  `entidad_id` INT(11) NULL COMMENT 'ID de la entidad afectada',
  `datos_antes` JSON NULL COMMENT 'Estado anterior (para ediciones/eliminaciones)',
  `datos_despues` JSON NULL COMMENT 'Estado posterior (para creaciones/ediciones)',
  `ip_address` VARCHAR(45) NULL,
  `resultado` ENUM('exito', 'error') NOT NULL DEFAULT 'exito',
  `mensaje_error` TEXT NULL,
  `fecha_accion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_auditoria`),
  KEY `idx_usuario` (`id_usuario`),
  KEY `idx_sesion` (`id_sesion`),
  KEY `idx_accion` (`accion`),
  KEY `idx_modulo` (`modulo`),
  KEY `idx_fecha` (`fecha_accion`),
  KEY `idx_entidad` (`entidad_tipo`, `entidad_id`),
  KEY `idx_resultado` (`resultado`),
  CONSTRAINT `fk_auditoria_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_auditoria_sesion` FOREIGN KEY (`id_sesion`) REFERENCES `sesiones` (`id_sesion`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Auditoría completa de acciones del sistema';

-- =====================================================
-- DATOS INICIALES: Roles
-- =====================================================

INSERT INTO `roles` (`id_rol`, `nombre_rol`, `descripcion`, `permisos`) VALUES
(1, 'Super Administrador', 'Acceso total al sistema incluyendo gestión de usuarios', JSON_OBJECT(
  'insumos', JSON_ARRAY('ver', 'crear', 'editar', 'eliminar', 'baja'),
  'asignaciones', JSON_ARRAY('ver', 'crear', 'editar', 'anular', 'devolver'),
  'reportes', JSON_ARRAY('ver', 'exportar'),
  'usuarios', JSON_ARRAY('ver', 'crear', 'editar', 'eliminar', 'cambiar_rol'),
  'auditoria', JSON_ARRAY('ver_todo'),
  'telecom', JSON_ARRAY('ver', 'editar'),
  'sedes', JSON_ARRAY('ver', 'crear', 'editar'),
  'areas', JSON_ARRAY('ver', 'crear', 'editar')
)),
(2, 'Administrador', 'Gestión completa de inventario sin acceso a usuarios', JSON_OBJECT(
  'insumos', JSON_ARRAY('ver', 'crear', 'editar', 'eliminar', 'baja'),
  'asignaciones', JSON_ARRAY('ver', 'crear', 'editar', 'anular', 'devolver'),
  'reportes', JSON_ARRAY('ver', 'exportar'),
  'telecom', JSON_ARRAY('ver', 'editar'),
  'sedes', JSON_ARRAY('ver'),
  'areas', JSON_ARRAY('ver')
)),
(3, 'Operador', 'Operaciones diarias de inventario y asignaciones', JSON_OBJECT(
  'insumos', JSON_ARRAY('ver', 'crear', 'editar', 'baja'),
  'asignaciones', JSON_ARRAY('ver', 'crear', 'devolver'),
  'reportes', JSON_ARRAY('ver'),
  'telecom', JSON_ARRAY('ver'),
  'sedes', JSON_ARRAY('ver'),
  'areas', JSON_ARRAY('ver')
)),
(4, 'Consultor', 'Solo lectura y generación de reportes', JSON_OBJECT(
  'insumos', JSON_ARRAY('ver'),
  'asignaciones', JSON_ARRAY('ver'),
  'reportes', JSON_ARRAY('ver', 'exportar'),
  'telecom', JSON_ARRAY('ver'),
  'sedes', JSON_ARRAY('ver'),
  'areas', JSON_ARRAY('ver')
));

-- =====================================================
-- DATOS INICIALES: Usuario Super Administrador
-- Password: admin123 (debe cambiarse en producción)
-- =====================================================

INSERT INTO `usuarios` (`username`, `email`, `password_hash`, `nombre`, `apellido`, `id_rol`, `activo`) 
VALUES (
  'admin',
  'admin@inventario.local',
  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
  'Administrador',
  'Sistema',
  1,
  1
);

-- =====================================================
-- TRIGGER: Registrar modificaciones en usuarios
-- =====================================================

DELIMITER $$

CREATE TRIGGER `trg_usuarios_before_update`
BEFORE UPDATE ON `usuarios`
FOR EACH ROW
BEGIN
    SET NEW.fecha_modificacion = NOW();
END$$

DELIMITER ;

-- =====================================================
-- ÍNDICES ADICIONALES PARA OPTIMIZACIÓN
-- =====================================================

-- Índice compuesto para búsquedas frecuentes en auditoría
ALTER TABLE `auditoria_acciones` 
ADD INDEX `idx_usuario_fecha` (`id_usuario`, `fecha_accion`);

ALTER TABLE `auditoria_acciones` 
ADD INDEX `idx_modulo_accion` (`modulo`, `accion`);

-- =====================================================
-- FIN DE LA MIGRACIÓN
-- =====================================================

-- VERIFICACIÓN: Mostrar datos insertados
SELECT 'Roles creados:' AS '';
SELECT id_rol, nombre_rol, descripcion FROM roles;

SELECT 'Usuario administrador creado:' AS '';
SELECT id_usuario, username, email, nombre, apellido, id_rol, activo FROM usuarios;


-- =====================================================
-- ACTUALIZACION DE PERMISOS SUPER ADMINISTRADOR
-- =====================================================
UPDATE roles 
SET permisos = JSON_SET(
    permisos, 
    '$.sedes', JSON_ARRAY('ver', 'crear', 'editar', 'eliminar'), 
    '$.areas', JSON_ARRAY('ver', 'crear', 'editar', 'eliminar')
) 
WHERE id_rol = 1;


-- Agregar columna para permisos personalizados en la tabla usuarios
ALTER TABLE usuarios 
ADD COLUMN permisos_personalizados LONGTEXT NULL COMMENT 'Permisos personalizados en formato JSON. Si es NULL, usa permisos del rol' 
AFTER id_rol;
