<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

if (!isset($_GET['localidad_id']) || !is_numeric($_GET['localidad_id'])) {
    echo json_encode(['success' => false, 'error' => 'ID de localidad no proporcionado']);
    exit;
}

$conexion = conectarDB();
$localidad_id = (int)$_GET['localidad_id'];

try {
    $sql = "SELECT id_sede, nombre_sede FROM sedes WHERE id_localidad = ? ORDER BY nombre_sede";
    $stmt = $conexion->prepare($sql);
    $stmt->execute([$localidad_id]);
    $sedes = $stmt->fetchAll();

    $options = '<option value="">Seleccione una sede</option>';
    foreach ($sedes as $sede) {
        $options .= '<option value="' . (int)$sede['id_sede'] . '">' . htmlspecialchars($sede['nombre_sede']) . '</option>';
    }

    echo json_encode(['success' => true, 'options' => $options]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Error al cargar sedes: ' . $e->getMessage()]);
}