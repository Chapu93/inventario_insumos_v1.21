<?php
require_once '../includes/config.php';
header('Content-Type: application/json');
$localidadId = isset($_GET['localidad_id']) ? (int)$_GET['localidad_id'] : 0;
if ($localidadId <= 0) { json_error('localidad_id requerido', 400); exit; }
try {
    $db = conectarDB();
    $stmt = $db->prepare("SELECT id_sede, nombre_sede FROM sedes WHERE id_localidad = ? ORDER BY nombre_sede");
    $stmt->execute([$localidadId]);
    $rows = $stmt->fetchAll();
    $data = array_map(function($r){
        return ['id' => (int)$r['id_sede'], 'nombre' => $r['nombre_sede']];
    }, $rows);
    json_success(['sedes' => $data]);
} catch (Exception $e) {
    json_error($e->getMessage(), 500);
}