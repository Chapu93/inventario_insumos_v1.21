-- Migración: Módulo Tareas Internas
-- Fecha: 2026-04-09
-- Descripción: Tabla independiente para tareas internas del área (limpieza depósito, racks, etc.)

CREATE TABLE IF NOT EXISTS `tareas_internas` (
  `id_tarea`           INT(11)      NOT NULL AUTO_INCREMENT,
  `titulo`             VARCHAR(200) NOT NULL,
  `descripcion`        TEXT         NOT NULL,
  `estado`             ENUM('Pendiente','En Proceso','Completada') NOT NULL DEFAULT 'Pendiente',
  `creado_por`         INT(11)      NOT NULL,
  `asignado_a`         INT(11)      DEFAULT NULL,
  `fecha_creacion`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_finalizacion` DATETIME     DEFAULT NULL,
  `fecha_actualizacion` DATETIME    DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_tarea`),
  KEY `idx_estado`    (`estado`),
  KEY `idx_asignado`  (`asignado_a`),
  KEY `idx_creador`   (`creado_por`),
  CONSTRAINT `fk_tarea_creador`  FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id_usuario`),
  CONSTRAINT `fk_tarea_asignado` FOREIGN KEY (`asignado_a`) REFERENCES `usuarios` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
