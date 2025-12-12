<?php
require_once '../includes/config.php';

if (!estaAutenticado()) {
    json_error('No autenticado', 401);
}

if (!tienePermiso('insumos', 'ver')) {
    json_error('No tienes permisos', 403);
}

try {
    $id_ingreso = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if (!$id_ingreso) {
        json_error('ID de ingreso inválido', 400);
    }
    
    $db = conectarDB();
    
    Logger::debug("Buscando documentos", ['id_ingreso' => $id_ingreso]);
    
    // Obtener documentos
    $stmt = $db->prepare(
        'SELECT id_documento, nombre_archivo, tipo_documento, DATE_FORMAT(fecha_carga, "%d/%m/%Y %H:%i") as fecha_carga
         FROM ingresos_documentos 
         WHERE id_ingreso = ?
         ORDER BY fecha_carga DESC'
    );
    $stmt->execute([$id_ingreso]);
    $documentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    Logger::debug("Documentos encontrados", ['count' => count($documentos), 'id_ingreso' => $id_ingreso]);
    
    json_success(['documentos' => $documentos ?: []]);
    
} catch (Exception $e) {
    Logger::error("Error al obtener documentos de ingreso", [
        'mensaje' => $e->getMessage(),
        'id_ingreso' => $_GET['id'] ?? 'no_disponible'
    ]);
    json_error('Error al obtener documentos: ' . $e->getMessage(), 500);
}
?>
