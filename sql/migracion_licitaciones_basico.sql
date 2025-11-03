-- Migración básica para licitaciones (sin proveedores)
START TRANSACTION;

CREATE TABLE IF NOT EXISTS licitaciones (
  id_licitacion INT AUTO_INCREMENT PRIMARY KEY,
  cod_expediente VARCHAR(50) NOT NULL UNIQUE,
  descripcion TEXT NULL,
  fecha_finalizacion DATE NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE insumos
  ADD COLUMN IF NOT EXISTS id_licitacion INT NULL AFTER id_sede_actual,
  ADD INDEX IF NOT EXISTS idx_insumos_licitacion (id_licitacion),
  ADD CONSTRAINT IF NOT EXISTS fk_insumos_licitacion FOREIGN KEY (id_licitacion)
    REFERENCES licitaciones(id_licitacion) ON DELETE SET NULL;

COMMIT;
