<?php
require_once '../includes/config.php';
header('Content-Type: application/json');
try {
    $db = conectarDB();
    $stmt = $db->query("SELECT id_localidad, nombre_localidad FROM localidades ORDER BY nombre_localidad");
    $rows = $stmt->fetchAll();
    echo json_encode(['success' => true, 'data' => array_map(function($r){
        return ['id' => (int)$r['id_localidad'], 'nombre' => $r['nombre_localidad']];
    }, $rows)]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}