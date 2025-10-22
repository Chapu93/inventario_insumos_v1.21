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
    
    $cod_expediente = trim($input['cod_expediente'] ?? '');
    $fecha_finalizacion = $input['fecha_finalizacion'] ?? null;
    $descripcion = $input['descripcion'] ?? null;
    
    if (empty($cod_expediente)) {
        echo json_encode(['success' => false, 'error' => 'El código de expediente es obligatorio']);
        exit;
    }
    
    $db = conectarDB();
    
    // Verificar que no exista el código de expediente
    $stmt = $db->prepare('SELECT id_licitacion FROM licitaciones WHERE cod_expediente = ?');
    $stmt->execute([$cod_expediente]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Ya existe una licitación con ese código de expediente']);
        exit;
    }
    
    // Insertar licitación
    $stmt = $db->prepare('INSERT INTO licitaciones (cod_expediente, fecha_finalizacion, descripcion) VALUES (?, ?, ?)');
    $stmt->execute([$cod_expediente, $fecha_finalizacion, $descripcion]);
    
    $id = $db->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'id' => $id,
        'message' => 'Licitación creada correctamente'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error al guardar: ' . $e->getMessage()
    ]);
}
?>
