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
    // El FK en insumos deja id_licitacion en NULL al borrar licitación
    $stmt = $db->prepare('DELETE FROM licitaciones WHERE id_licitacion = ?');
    $stmt->execute([$id]);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>