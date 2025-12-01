<?php
require_once '../includes/config.php';

if (!estaAutenticado()) {
    json_error('No autenticado', 401);
}

if (!tienePermiso('insumos', 'ver')) {
    json_error('No tienes permisos para ver insumos', 403);
}

$sedeId = isset($_GET['sede_id']) ? (int)$_GET['sede_id'] : 0;

if ($sedeId <= 0) {
    json_error('ID de sede no proporcionado o inválido', 400);
}

try {
    $conexion = conectarDB();
    
    // Obtener insumos disponibles en la sede especificada
    // Para tipo Varios, mostrar total disponible (oficina + depósito)
    $sql = "SELECT i.id_insumo, i.nombre_insumo, i.tipo_insumo, i.numero_serie, i.id_fisico, 
                   i.cantidad,
                   i.cantidad_oficina, 
                   i.cantidad_deposito
            FROM insumos i 
            WHERE i.estado = 'Disponible' 
            AND (i.id_sede_actual = ? OR i.id_sede_actual IS NULL)
            AND (i.tipo_insumo <> 'Varios' OR i.cantidad > 0)
            ORDER BY i.nombre_insumo";
    
    $stmt = $conexion->prepare($sql);
    $stmt->execute([$sedeId]);
    $insumos = $stmt->fetchAll();
    
    Logger::debug('Insumos cargados para sede', ['sede_id' => $sedeId, 'count' => count($insumos)]);
    
    // Responder JSON con datos crudos; el cliente construirá opciones
    json_success(['insumos' => $insumos]);
    
} catch (Exception $e) {
    Logger::error('Error al cargar insumos', [
        'error' => $e->getMessage(),
        'sede_id' => $sedeId
    ]);
    json_error('Error al cargar insumos: ' . $e->getMessage(), 500);
}
?>