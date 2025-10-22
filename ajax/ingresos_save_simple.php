<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!verify_csrf($input['_csrf'] ?? '')) {
        echo json_encode(['success' => false, 'error' => 'CSRF inválido']);
        exit;
    }
    
    $tipo_ingreso = trim($input['tipo_ingreso'] ?? '');
    $nro_referencia = trim($input['nro_referencia'] ?? '');
    $fecha_finalizacion = $input['fecha_finalizacion'] ?? null;
    $descripcion = $input['descripcion'] ?? null;
    
    // Validaciones
    if (empty($tipo_ingreso)) {
        echo json_encode(['success' => false, 'error' => 'El tipo de ingreso es obligatorio']);
        exit;
    }
    
    if (empty($nro_referencia)) {
        echo json_encode(['success' => false, 'error' => 'El número de referencia es obligatorio']);
        exit;
    }
    
    $tiposValidos = ['fondos', 'compra_directa', 'licitacion', 'otros'];
    if (!in_array($tipo_ingreso, $tiposValidos)) {
        echo json_encode(['success' => false, 'error' => 'Tipo de ingreso no válido']);
        exit;
    }
    
    $db = conectarDB();
    
    // Verificar que no exista el número de referencia con el mismo tipo
    $stmt = $db->prepare('SELECT id_ingreso FROM ingresos WHERE tipo_ingreso = ? AND nro_referencia = ?');
    $stmt->execute([$tipo_ingreso, $nro_referencia]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Ya existe un ingreso de este tipo con ese número de referencia']);
        exit;
    }
    
    // Insertar ingreso
    $stmt = $db->prepare('INSERT INTO ingresos (tipo_ingreso, nro_referencia, fecha_finalizacion, descripcion) VALUES (?, ?, ?, ?)');
    $stmt->execute([$tipo_ingreso, $nro_referencia, $fecha_finalizacion, $descripcion]);
    
    $id = $db->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'id' => $id,
        'message' => 'Ingreso creado correctamente'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error al guardar: ' . $e->getMessage()
    ]);
}
?>
