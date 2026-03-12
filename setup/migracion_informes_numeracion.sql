-- Migración para numeración anual de informes técnicos
ALTER TABLE pedidos_informes ADD COLUMN numero_informe VARCHAR(50) AFTER id_pedido;

-- Tabla para secuencia de informes técnicos
CREATE TABLE IF NOT EXISTS pedidos_informes_secuencia (
    anio INT PRIMARY KEY,
    ultimo_numero INT DEFAULT 0
) ENGINE=InnoDB;

-- Inicializar año actual
INSERT IGNORE INTO pedidos_informes_secuencia (anio, ultimo_numero) VALUES (YEAR(CURRENT_DATE), 0);
