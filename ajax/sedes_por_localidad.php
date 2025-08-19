<?php
require_once '../includes/config.php';
header('Content-Type: application/json');
$localidadId = isset($_GET['localidad_id']) ? (int)$_GET['localidad_id'] : 0;
if ($localidadId <= 0) {
    echo json_encode(['success' => false, 'error' => 'localidad_id requerido']);
    exit;
}
try {
    $db = conectarDB();
    $stmt = $db->prepare("SELECT id_sede, nombre_sede FROM sedes WHERE id_localidad = ? ORDER BY nombre_sede");
    $stmt->execute([$localidadId]);
    $rows = $stmt->fetchAll();
    echo json_encode(['success' => true, 'data' => array_map(function($r){
        return ['id' => (int)$r['id_sede'], 'nombre' => $r['nombre_sede']];
    }, $rows)]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}