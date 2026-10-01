-- ======================================================
-- MIGRACIÓN: Sistema de Notificaciones de SITIA
-- ======================================================

CREATE TABLE IF NOT EXISTS `notificaciones` (
  `id_notificacion` INT AUTO_INCREMENT PRIMARY KEY,
  `id_usuario` INT NOT NULL,
  `tipo` VARCHAR(50) NOT NULL COMMENT 'tarea_asignada, tarea_colaborativa, cambio_estado, nuevo_comentario',
  `titulo` VARCHAR(150) NOT NULL,
  `mensaje` TEXT NOT NULL,
  `url` VARCHAR(255) DEFAULT NULL,
  `mostrada` TINYINT(1) DEFAULT 0 COMMENT '0: No mostrada en Toast, 1: Mostrada en Toast',
  `leida` TINYINT(1) DEFAULT 0 COMMENT '0: No leída en Campana, 1: Leída',
  `fecha_creacion` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_usuario_leida` (`id_usuario`, `leida`),
  INDEX `idx_usuario_mostrada` (`id_usuario`, `mostrada`),
  CONSTRAINT `fk_notificaciones_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
