<?php
require_once '../includes/config.php';

if (!estaAutenticado()) {
    json_error('No autenticado', 401);
}

if (!tienePermiso('asignaciones', 'ver')) {
    json_error('No tienes permisos para ver asignaciones', 403);
}

$sedeId = isset($_GET['sede_id']) ? (int)$_GET['sede_id'] : 0;

if ($sedeId <= 0) {
    json_error('ID de sede no proporcionado o inválido', 400);
}

try {
    $conexion = conectarDB();
    
    // Primero intentar obtener áreas específicas de la sede
    $sql = "SELECT DISTINCT a.id_area, a.nombre_area 
            FROM areas a 
            JOIN sede_areas sa ON a.id_area = sa.id_area 
            WHERE sa.id_sede = ? AND sa.activa = 1 
            ORDER BY a.nombre_area";
    
    $stmt = $conexion->prepare($sql);
    $stmt->execute([$sedeId]);
    $areas = $stmt->fetchAll();
    
    // Si no hay áreas específicas para la sede, obtener todas las áreas
    if (empty($areas)) {
        Logger::debug('No se encontraron áreas específicas para la sede, cargando todas', ['sede_id' => $sedeId]);
        $sql = "SELECT id_area, nombre_area FROM areas ORDER BY nombre_area";
        $stmt = $conexion->prepare($sql);
        $stmt->execute();
        $areas = $stmt->fetchAll();
    }
    
    Logger::debug('Áreas cargadas', ['sede_id' => $sedeId, 'count' => count($areas)]);
    
    json_success(['areas' => $areas]);
    
} catch (Exception $e) {
    Logger::error('Error al cargar áreas', [
        'error' => $e->getMessage(),
        'sede_id' => $sedeId
    ]);
    json_error('Error al cargar áreas: ' . $e->getMessage(), 500);
}
?>