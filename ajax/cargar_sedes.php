<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

if (!isset($_GET['localidad_id']) || !is_numeric($_GET['localidad_id'])) {
    json_error('ID de localidad no proporcionado', 400);
    exit;
}

$conexion = conectarDB();
$localidad_id = (int)$_GET['localidad_id'];

try {
    $sql = "SELECT id_sede, nombre_sede FROM sedes WHERE id_localidad = ? ORDER BY nombre_sede";
    $stmt = $conexion->prepare($sql);
    $stmt->execute([$localidad_id]);
    $rows = $stmt->fetchAll();
    $sedes = array_map(function($r){
        return [ 'id' => (int)$r['id_sede'], 'nombre' => $r['nombre_sede'] ];
    }, $rows);
    json_success(['sedes' => $sedes]);
} catch (Exception $e) {
    json_error('Error al cargar sedes: ' . $e->getMessage(), 500);
}