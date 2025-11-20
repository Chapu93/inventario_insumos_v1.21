<?php
require_once '../includes/config.php';

try {
    if (!verify_csrf()) {
        json_error('CSRF inválido', 403);
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $id = isset($input['id_insumo']) ? (int)$input['id_insumo'] : 0;
    
    if ($id <= 0) {
        json_error('ID de insumo inválido', 400);
    }

    $db = conectarDB();
    $db->beginTransaction();

    // Eliminar referencias en remitos_detalle para respetar FK (fk_rd_insumo)
    $db->prepare('DELETE FROM remitos_detalle WHERE id_insumo = ?')->execute([$id]);
    // Borrar cabeceras de remitos que hayan quedado sin ítems
    $db->exec('DELETE r FROM remitos r LEFT JOIN remitos_detalle d ON d.id_remito = r.id_remito WHERE d.id_remito IS NULL');

    // Eliminar registros de bajas del insumo (FK fk_ib_insumo)
    $db->prepare('DELETE FROM insumos_bajas WHERE id_insumo = ?')->execute([$id]);

    // Eliminar datos específicos del insumo en tablas por tipo

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
    
    Logger::info('Insumo eliminado completamente', ['id_insumo' => $id]);
    
    json_success(['message' => 'Insumo eliminado correctamente']);
    
} catch (Exception $e) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }
    Logger::error('Error al eliminar insumo', [
        'mensaje' => $e->getMessage(),
        'id_insumo' => $id ?? 0
    ]);
    json_error($e->getMessage(), 500);
}
