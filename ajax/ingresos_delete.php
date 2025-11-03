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
    
    $id = (int)($input['id'] ?? 0);
    if (!$id) {
        echo json_encode(['success' => false, 'error' => 'ID no válido']);
        exit;
    }
    
    $db = conectarDB();
    $db->beginTransaction();
    
    // Desvincular insumos asociados (SET NULL)
    $stmt = $db->prepare('UPDATE insumos SET id_ingreso = NULL WHERE id_ingreso = ?');
    $stmt->execute([$id]);
    
    // Eliminar ingreso
    $stmt = $db->prepare('DELETE FROM ingresos WHERE id_ingreso = ?');
    $stmt->execute([$id]);
    
    $db->commit();
    
    echo json_encode(['success' => true, 'message' => 'Ingreso eliminado correctamente']);
    
} catch (Exception $e) {
    if ($db && $db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode(['success' => false, 'error' => 'Error al eliminar: ' . $e->getMessage()]);
}
?>
