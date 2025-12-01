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
    json_error('ID de localidad no proporcionado o inválido', 400);
}

try {
    $conexion = conectarDB();
    $sql = "SELECT id_sede, nombre_sede FROM sedes WHERE id_localidad = ? ORDER BY nombre_sede";
    $stmt = $conexion->prepare($sql);
    $stmt->execute([$localidadId]);
    $rows = $stmt->fetchAll();
    
    $sedes = array_map(function($r){
        return [ 'id' => (int)$r['id_sede'], 'nombre' => $r['nombre_sede'] ];
    }, $rows);
    
    Logger::debug('Sedes cargadas por localidad', ['localidad_id' => $localidadId, 'count' => count($sedes)]);
    
    json_success(['sedes' => $sedes]);
    
} catch (Exception $e) {
    Logger::error('Error al cargar sedes', [
        'error' => $e->getMessage(),
        'localidad_id' => $localidadId
    ]);
    json_error('Error al cargar sedes: ' . $e->getMessage(), 500);
}
?>