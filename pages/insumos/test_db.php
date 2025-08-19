<?php
require_once '../../includes/config.php';

header('Content-Type: application/json');

try {
    $conexion = conectarDB();
    echo json_encode(['success' => true, 'message' => 'Conexión a BD exitosa']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Error de conexión: ' . $e->getMessage()]);
}
?> 