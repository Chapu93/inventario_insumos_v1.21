<?php
require_once '../includes/config.php';

try {
    $db = conectarDB();
    $stmt = $db->query("SELECT id_localidad, nombre_localidad FROM localidades ORDER BY nombre_localidad");
    $rows = $stmt->fetchAll();
    
    $localidades = array_map(function($r){
        return ['id' => (int)$r['id_localidad'], 'nombre' => $r['nombre_localidad']];
    }, $rows);
    
    Logger::debug('Localidades cargadas', ['count' => count($localidades)]);
    
    json_success($localidades);
    
} catch (Exception $e) {
    Logger::error('Error al cargar localidades', ['mensaje' => $e->getMessage()]);
    json_error($e->getMessage(), 500);
}