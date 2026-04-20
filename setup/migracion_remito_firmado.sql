-- Migración para agregar columna remito_firmado a la tabla pedidos
-- Fecha de creación: 2026-03-30
-- Este script permite cargar el comprobante final de entrega (escaneado/foto) a un pedido de insumo o técnico.

ALTER TABLE `pedidos` 
ADD COLUMN `remito_firmado` varchar(255) DEFAULT NULL AFTER `pdf_nota`;
