-- Script de actualización para la tabla remitos
-- Agrega el campo nota_solicitud para adjuntar documentación de solicitud

ALTER TABLE `remitos` 
ADD COLUMN `nota_solicitud` varchar(255) DEFAULT NULL AFTER `declaracion_jurada`;
