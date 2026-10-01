-- Migración para incorporar el estado 'Sin Stock' en la tabla de pedidos
-- Base de datos: inventario_insumos_v1

-- 1. Modificar el ENUM de la columna estado en la tabla pedidos
ALTER TABLE pedidos 
MODIFY COLUMN estado ENUM('Pendiente','En Proceso','Preparado','Completado','Rechazado','Sin Stock') NOT NULL DEFAULT 'Pendiente';

-- 2. Actualizar las solicitudes de insumos que fueron cerradas previamente por falta de stock
UPDATE pedidos 
SET estado = 'Sin Stock' 
WHERE tipo = 'Pedido Insumo' AND estado = 'Rechazado';

-- 3. Limpiar prefijo redundante en registros previos de pedidos_informes
UPDATE pedidos_informes 
SET diagnostico = TRIM(SUBSTRING(diagnostico, 27)) 
WHERE diagnostico LIKE 'Requerimiento de Insumos:%';
