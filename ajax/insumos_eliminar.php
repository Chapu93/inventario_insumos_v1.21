<?php
require_once '../includes/config.php';

// Verificar autenticación y permisos
if (!estaAutenticado()) {
    json_error('No autenticado', 401);
}

if (!tienePermiso('insumos', 'eliminar')) {
    json_error('No tienes permisos para eliminar insumos', 403);
}

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

    // 1. Verificar existencia del insumo
    $stmtIns = $db->prepare("SELECT id_insumo, nombre_insumo, tipo_insumo, numero_serie, estado FROM insumos WHERE id_insumo = ?");
    $stmtIns->execute([$id]);
    $ins = $stmtIns->fetch(PDO::FETCH_ASSOC);
    if (!$ins) {
        json_error('Insumo no encontrado', 404);
    }

    // 2. Comprobar dependencias operativas que prohíben el borrado físico:
    // a) Remitos de asignación asociados
    $stmtRem = $db->prepare("SELECT COUNT(*) FROM remitos_detalle WHERE id_insumo = ?");
    $stmtRem->execute([$id]);
    $cantRemitos = (int)$stmtRem->fetchColumn();
    if ($cantRemitos > 0) {
        json_error("El insumo posee {$cantRemitos} remito(s) de asignación asociado(s). No puede ser eliminado para preservar la trazabilidad legal y contable; debe gestionarse mediante 'Baja de Insumo'.", 400);
    }

    // b) Pedidos de soporte técnico relacionados
    $stmtPed = $db->prepare("SELECT COUNT(*) FROM pedidos WHERE id_insumo_relacionado = ?");
    $stmtPed->execute([$id]);
    $cantPedidos = (int)$stmtPed->fetchColumn();
    if ($cantPedidos > 0) {
        json_error("El insumo está vinculado a pedidos de soporte técnico. No puede ser eliminado físicamente; debe gestionarse mediante 'Baja de Insumo'.", 400);
    }

    // c) Movimientos de stock en depósito
    try {
        $stmtMov = $db->prepare("SELECT COUNT(*) FROM insumos_movimientos_stock WHERE id_insumo = ?");
        $stmtMov->execute([$id]);
        if ((int)$stmtMov->fetchColumn() > 0) {
            json_error("El insumo posee movimientos de stock registrados. No puede ser eliminado; debe gestionarse mediante 'Baja de Insumo'.", 400);
        }
    } catch (Exception $e) {
        // Si la tabla no existiera en alguna versión legacy, ignorar
    }

    // Si está actualmente asignado (por cualquier motivo de inconsistencia)
    if ($ins['estado'] === 'Asignado') {
        json_error("El insumo figura con estado 'Asignado'. Debe devolverlo primero o regularizar su situación.", 400);
    }

    // 3. Si no tiene historial operativo (fue creado por error sin uso), proceder con la eliminación segura
    $db->beginTransaction();

    // Eliminar registros de bajas del insumo si hubiera (FK fk_ib_insumo)
    $db->prepare('DELETE FROM insumos_bajas WHERE id_insumo = ?')->execute([$id]);

    // Borrar datos específicos en tablas hijas de hardware para evitar huérfanos
    $db->prepare('DELETE FROM pcs_completas WHERE id_insumo = ?')->execute([$id]);
    $db->prepare('DELETE FROM notebooks WHERE id_insumo = ?')->execute([$id]);
    $db->prepare('DELETE FROM impresoras WHERE id_insumo = ?')->execute([$id]);
    $db->prepare('DELETE FROM monitores WHERE id_insumo = ?')->execute([$id]);
    $db->prepare('DELETE FROM escaneres WHERE id_insumo = ?')->execute([$id]);

    // Finalmente, borrar el insumo principal
    $delIns = $db->prepare('DELETE FROM insumos WHERE id_insumo = ?');
    $delIns->execute([$id]);

    $db->commit();
    
    // Registrar en auditoría
    registrarAuditoria(
        'eliminar_insumo',
        'insumos',
        "Insumo eliminado completamente (ID: {$id}, Tipo: {$ins['tipo_insumo']}, S/N: " . ($ins['numero_serie'] ?: 'S/N') . ")",
        'insumo',
        $id,
        $ins,
        null
    );

    Logger::info('Insumo sin historial eliminado correctamente', ['id_insumo' => $id]);
    
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
