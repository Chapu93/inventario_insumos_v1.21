<?php
require_once '../includes/config.php';

// Verificar autenticación y permisos
if (!estaAutenticado()) {
    json_error('No autenticado', 401);
}

if (!tienePermiso('asignaciones', 'anular')) {
    json_error('No tienes permisos para anular asignaciones', 403);
}

try {
    if (!verify_csrf()) {
        json_error('CSRF inválido', 403);
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $remito = isset($input['remito']) ? trim((string)$input['remito']) : '';
    $motivo = isset($input['motivo']) ? trim((string)$input['motivo']) : '';
    
    if ($remito === '') {
        json_error('Remito inválido', 400);
    }
    if ($motivo === '') {
        json_error('Debe especificar un motivo de anulación', 400);
    }

    $db = conectarDB();
    $db->beginTransaction();

    // Obtener remito y sus items
    $cab = $db->prepare('SELECT id_remito, estado FROM remitos WHERE numero_remito = ? FOR UPDATE');
    $cab->execute([$remito]);
    $r = $cab->fetch();
    if (!$r) { throw new Exception('Remito no encontrado'); }
    $idRemito = (int)$r['id_remito'];
    
    // No se puede anular un remito ya anulado
    if (strcasecmp((string)$r['estado'], 'Anulado') === 0) {
        throw new Exception('El remito ya se encuentra anulado');
    }

    // Revertir estado de insumos solo si el remito está Activa; si ya está Devuelta, no tocar stock/estado
    $items = $db->prepare('SELECT d.id_insumo, d.cantidad, i.tipo_insumo, i.cantidad AS stock_actual, i.cantidad_oficina, COALESCE(d.cantidad_devuelta,0) AS cantidad_devuelta FROM remitos_detalle d JOIN insumos i ON i.id_insumo = d.id_insumo WHERE d.id_remito = ?');
    $items->execute([$idRemito]);
    $itemsArray = $items->fetchAll();

    if (strcasecmp((string)$r['estado'], 'Activa') === 0) {
        foreach ($itemsArray as $it) {
            $idIns = (int)$it['id_insumo'];
            $pendiente = max(0, (int)$it['cantidad'] - (int)$it['cantidad_devuelta']);

            if ($it['tipo_insumo'] === 'Varios') {
                if ($pendiente > 0) {
                    $nuevoTotal = (int)$it['stock_actual'] + $pendiente;
                    $nuevaOficina = (int)($it['cantidad_oficina'] ?? $it['stock_actual']) + $pendiente;
                    
                    $db->prepare("UPDATE insumos SET cantidad = ?, cantidad_oficina = ?, estado = 'Disponible' WHERE id_insumo = ?")
                       ->execute([$nuevoTotal, $nuevaOficina, $idIns]);
                }
            } else {
                $db->prepare("UPDATE insumos SET estado = 'Disponible', id_sede_actual = NULL, id_area_asignacion_actual = NULL, id_punto_stock_actual = id_punto_stock_actual WHERE id_insumo = ?")
                   ->execute([$idIns]);
            }

            // IMPORTANTE: Registrar en el detalle que los insumos fueron devueltos (por la anulación)
            $db->prepare("UPDATE remitos_detalle SET cantidad_devuelta = cantidad WHERE id_remito = ? AND id_insumo = ?")
               ->execute([$idRemito, $idIns]);
        }
    }

    // Anular el remito en lugar de eliminarlo
    $fechaAnulacion = date('Y-m-d H:i:s');
    $db->prepare("UPDATE remitos SET estado = 'Anulado', motivo_anulacion = ?, fecha_anulacion = ? WHERE id_remito = ?")
       ->execute([$motivo, $fechaAnulacion, $idRemito]);

    $db->commit();
    
    Logger::info('Remito anulado', [
        'numero_remito' => $remito,
        'motivo' => $motivo,
        'id_remito' => $idRemito
    ]);
    
    json_success(['mensaje' => 'Remito anulado correctamente']);
    
} catch (Exception $e) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }
    Logger::error('Error al anular remito', [
        'mensaje' => $e->getMessage(),
        'numero_remito' => $remito ?? '',
        'motivo' => $motivo ?? ''
    ]);
    json_error($e->getMessage(), 500);
}
