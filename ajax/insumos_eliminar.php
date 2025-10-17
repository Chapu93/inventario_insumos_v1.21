<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

try {
    if (!verify_csrf()) { throw new Exception('CSRF inválido'); }
    $input = json_decode(file_get_contents('php://input'), true);
    $id = isset($input['id_insumo']) ? (int)$input['id_insumo'] : 0;
    if ($id <= 0) { throw new Exception('ID de insumo inválido'); }

    $db = conectarDB();
    $db->beginTransaction();

    // Verificar si el insumo participa en alguna asignación (remitos_detalle)
    $stmt = $db->prepare("SELECT r.id_remito, r.numero_remito, r.nombre_persona_asignada, r.apellido_persona_asignada
                           FROM remitos_detalle d JOIN remitos r ON r.id_remito = d.id_remito WHERE d.id_insumo = ?");
    $stmt->execute([$id]);
    $rows = $stmt->fetchAll();

    // Eliminar detalles y cabecera de remitos que queden sin items
    if (!empty($rows)) {
        // Borrar todos los detalles que refieren al insumo
        $delDet = $db->prepare('DELETE FROM remitos_detalle WHERE id_insumo = ?');
        $delDet->execute([$id]);
        // Borrar cabeceras de remito que hayan quedado sin items
        $db->exec('DELETE r FROM remitos r LEFT JOIN remitos_detalle d ON d.id_remito = r.id_remito WHERE d.id_remito IS NULL');
    }

    // Borrar datos específicos por tipo para evitar huérfanos
    $db->prepare('DELETE FROM pcs_completas WHERE id_insumo = ?')->execute([$id]);
    $db->prepare('DELETE FROM notebooks WHERE id_insumo = ?')->execute([$id]);
    $db->prepare('DELETE FROM impresoras WHERE id_insumo = ?')->execute([$id]);
    $db->prepare('DELETE FROM monitores WHERE id_insumo = ?')->execute([$id]);
    $db->prepare('DELETE FROM escaneres WHERE id_insumo = ?')->execute([$id]);

    // Finalmente, borrar el insumo
    $delIns = $db->prepare('DELETE FROM insumos WHERE id_insumo = ?');
    $delIns->execute([$id]);

    $db->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) { $db->rollBack(); }
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
