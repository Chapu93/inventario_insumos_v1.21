-- Migración: Agregar permisos personalizados por usuario
-- Fecha: 2025-12-01

-- Agregar columna para permisos personalizados en la tabla usuarios
ALTER TABLE usuarios 
ADD COLUMN permisos_personalizados LONGTEXT NULL COMMENT 'Permisos personalizados en formato JSON. Si es NULL, usa permisos del rol' 
AFTER id_rol;

-- Agregar índice para mejorar rendimiento en consultas
-- (Opcional, solo si se hacen muchas consultas sobre esta columna)
-- CREATE INDEX idx_usuarios_permisos ON usuarios(id_usuario, id_rol);
