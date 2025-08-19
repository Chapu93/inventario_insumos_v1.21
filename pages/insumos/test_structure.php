<?php
require_once '../../includes/config.php';

header('Content-Type: application/json');

try {
    $conexion = conectarDB();
    
    // Verificar estructura de la tabla insumos
    $stmt = $conexion->query("DESCRIBE insumos");
    $columnas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true, 
        'columnas' => $columnas,
        'message' => 'Estructura de tabla obtenida'
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Error: ' . $e->getMessage()]);
}
?> 