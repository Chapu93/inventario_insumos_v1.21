<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

if (!isset($_GET['sede_id'])) { json_error('ID de sede no proporcionado', 400); exit; }

$conexion = conectarDB();
$sede_id = $_GET['sede_id'];

try {
    // Obtener insumos disponibles en la sede especificada
    // Para tipo Varios, usar cantidad_oficina en lugar de cantidad
    $sql = "SELECT i.id_insumo, i.nombre_insumo, i.tipo_insumo, i.numero_serie, i.id_fisico, 
                   CASE 
                       WHEN i.tipo_insumo = 'Varios' THEN COALESCE(i.cantidad_oficina, i.cantidad)
                       ELSE i.cantidad
                   END as cantidad,
                   i.cantidad_oficina, i.cantidad_deposito
            FROM insumos i 
            WHERE i.estado = 'Disponible' 
            AND (i.id_sede_actual = ? OR i.id_sede_actual IS NULL)
            AND (i.tipo_insumo <> 'Varios' OR COALESCE(i.cantidad_oficina, i.cantidad) > 0)
            ORDER BY i.nombre_insumo";
    
    $stmt = $conexion->prepare($sql);
    $stmt->execute([$sede_id]);
    $insumos = $stmt->fetchAll();
    
    // Responder JSON con datos crudos; el cliente construirá opciones
    json_success(['insumos' => $insumos]);
    
} catch (Exception $e) {
    json_error('Error al cargar insumos: ' . $e->getMessage(), 500);
}
?> 