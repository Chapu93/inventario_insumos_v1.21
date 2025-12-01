<?php
require_once '../includes/config.php';

if (!estaAutenticado()) {
    json_error('No autenticado', 401);
}

if (!tienePermiso('asignaciones', 'ver')) {
    json_error('No tienes permisos para ver asignaciones', 403);
}

$localidadId = isset($_GET['localidad_id']) ? (int)$_GET['localidad_id'] : 0;

if ($localidadId <= 0) {
    json_error('localidad_id requerido', 400);
}

try {
    $db = conectarDB();
    $stmt = $db->prepare("SELECT id_sede, nombre_sede FROM sedes WHERE id_localidad = ? ORDER BY nombre_sede");
    $stmt->execute([$localidadId]);
    $rows = $stmt->fetchAll();
    
    $data = array_map(function($r){
        return ['id' => (int)$r['id_sede'], 'nombre' => $r['nombre_sede']];
    }, $rows);
    
    Logger::debug('Sedes cargadas por localidad', ['localidad_id' => $localidadId, 'count' => count($data)]);
    
    json_success(['sedes' => $data]);
    
} catch (Exception $e) {
    Logger::error('Error al cargar sedes por localidad', [
        'mensaje' => $e->getMessage(),
        'localidad_id' => $localidadId
    ]);
    json_error($e->getMessage(), 500);
}
?>