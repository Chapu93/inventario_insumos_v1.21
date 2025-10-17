<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

try {
    if (!verify_csrf()) { throw new Exception('CSRF inválido'); }
    $input = json_decode(file_get_contents('php://input'), true);
    $remito = isset($input['remito']) ? trim((string)$input['remito']) : '';
    if ($remito === '') { throw new Exception('Remito inválido'); }

    $db = conectarDB();
    $db->beginTransaction();

    // Obtener remito y sus items
    $cab = $db->prepare('SELECT id_remito, estado FROM remitos WHERE numero_remito = ? FOR UPDATE');
    $cab->execute([$remito]);
    $r = $cab->fetch();
    if (!$r) { throw new Exception('Remito no encontrado'); }
    $idRemito = (int)$r['id_remito'];

    // Revertir estado de insumos: para unitarios, poner Disponible y limpiar asignación; para "Varios" sumar cantidades
    $items = $db->prepare('SELECT d.id_insumo, d.cantidad, i.tipo_insumo, i.cantidad AS stock_actual FROM remitos_detalle d JOIN insumos i ON i.id_insumo = d.id_insumo WHERE d.id_remito = ?');
    $items->execute([$idRemito]);
    foreach ($items as $it) {
        if ($it['tipo_insumo'] === 'Varios') {
            $nuevo = (int)$it['stock_actual'] + (int)$it['cantidad'];
            $db->prepare("UPDATE insumos SET cantidad = ?, estado = 'Disponible' WHERE id_insumo = ?")->execute([$nuevo, (int)$it['id_insumo']]);
        } else {
            $db->prepare("UPDATE insumos SET estado = 'Disponible', id_sede_actual = NULL, id_area_asignacion_actual = NULL, id_punto_stock_actual = id_punto_stock_actual WHERE id_insumo = ?")
               ->execute([(int)$it['id_insumo']]);
        }
    }

    // Eliminar detalle y cabecera
    $db->prepare('DELETE FROM remitos_detalle WHERE id_remito = ?')->execute([$idRemito]);
    $db->prepare('DELETE FROM remitos WHERE id_remito = ?')->execute([$idRemito]);

    $db->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) { $db->rollBack(); }
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
