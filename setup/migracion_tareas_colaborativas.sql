-- Migración para Tareas Colaborativas

ALTER TABLE `tareas_internas` ADD COLUMN `es_colaborativa` TINYINT(1) NOT NULL DEFAULT 0;

CREATE TABLE IF NOT EXISTS `tareas_comentarios` (
  `id_comentario` INT(11) NOT NULL AUTO_INCREMENT,
  `id_tarea` INT(11) NOT NULL,
  `id_usuario` INT(11) NOT NULL,
  `comentario` TEXT NOT NULL,
  `fecha` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_comentario`),
  KEY `idx_comentario_tarea` (`id_tarea`),
  KEY `idx_comentario_usuario` (`id_usuario`),
  CONSTRAINT `fk_comentario_tarea` FOREIGN KEY (`id_tarea`) REFERENCES `tareas_internas` (`id_tarea`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_comentario_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
