<?php
require_once '../includes/config.php';
header('Content-Type: application/json');
try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { throw new Exception('Método inválido'); }
    $payload = json_decode(file_get_contents('php://input'), true);
    if (!$payload || empty($payload['id'])) { throw new Exception('ID requerido'); }
    if (!verify_csrf()) { throw new Exception('CSRF inválido'); }
    $id = (int)$payload['id'];
    $db = conectarDB();
    
    $db->beginTransaction();
    
    // Setear id_licitacion a NULL en los insumos asociados
    $stmt = $db->prepare('UPDATE insumos SET id_licitacion = NULL WHERE id_licitacion = ?');
    $stmt->execute([$id]);
    
    // Eliminar la licitación
    $stmt = $db->prepare('DELETE FROM licitaciones WHERE id_licitacion = ?');
    $stmt->execute([$id]);
    
    $db->commit();
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>