<?php
require_once '../includes/config.php';
header('Content-Type: application/json');
$sedeId = isset($_GET['sede_id']) ? (int)$_GET['sede_id'] : 0;
if ($sedeId <= 0) {
    echo json_encode(['success' => false, 'error' => 'sede_id requerido']);
    exit;
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
    echo json_encode(['success' => true, 'data' => array_map(function($r){
        return ['id' => (int)$r['id_area'], 'nombre' => $r['nombre_area']];
    }, $rows)]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}