<?php
require_once '../includes/config.php';

$sedeId = isset($_GET['sede_id']) ? (int)$_GET['sede_id'] : 0;

if ($sedeId <= 0) {
    json_error('sede_id requerido', 400);
}

try {
    $db = conectarDB();
    $sql = "SELECT DISTINCT a.id_area, a.nombre_area
            FROM areas a
            JOIN sede_areas sa ON sa.id_area = a.id_area
            WHERE sa.id_sede = ? AND sa.activa = 1
            ORDER BY a.nombre_area";
    $stmt = $db->prepare($sql);
    $stmt->execute([$sedeId]);
    $rows = $stmt->fetchAll();
    
    $areas = array_map(function($r){
        return ['id' => (int)$r['id_area'], 'nombre' => $r['nombre_area']];
    }, $rows);
    
    Logger::debug('Áreas cargadas por sede', ['sede_id' => $sedeId, 'count' => count($areas)]);
    
    json_success($areas);
    
} catch (Exception $e) {
    Logger::error('Error al cargar áreas por sede', [
        'mensaje' => $e->getMessage(),
        'sede_id' => $sedeId
    ]);
    json_error($e->getMessage(), 500);
}