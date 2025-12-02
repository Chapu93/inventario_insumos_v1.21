<?php
require_once '../includes/config.php';

// Verificar autenticación y permisos
if (!estaAutenticado()) {
    json_error('No autenticado', 401);
}

if (!tienePermiso('asignaciones', 'devolver')) {
    json_error('No tienes permisos para devolver insumos', 403);
}

if (!verify_csrf()) {
    json_error('CSRF inválido', 403);
    exit;
}

try {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!$data || !isset($data['remito']) || !is_array($data['items'])) {
        json_error('Datos inválidos', 400);
        exit;
    }

    $numero = $data['remito'];
    $items = $data['items'];

    $db = conectarDB();

    // Asegurar columna cantidad_devuelta para historizar devoluciones
    try {
        $db->query("SELECT cantidad_devuelta FROM remitos_detalle LIMIT 1");
    } catch (Exception $e) {
        $db->exec("ALTER TABLE remitos_detalle ADD COLUMN cantidad_devuelta INT NOT NULL DEFAULT 0");
    }

    $stmt = $db->prepare("SELECT id_remito FROM remitos WHERE numero_remito = ? LIMIT 1");
    $stmt->execute([$numero]);
    $cab = $stmt->fetch();
    if (!$cab) {
        json_error('Remito no encontrado', 404);
        exit;
    }
    $idRemito = (int)$cab['id_remito'];

    $db->beginTransaction();

    $devolverList = [];

    foreach ($items as $it) {
        $idInsumo = (int)($it['id_insumo'] ?? 0);
        $cantidadDev = isset($it['cantidad']) ? (int)$it['cantidad'] : 1;
        $cantidadDev = max(1, $cantidadDev); // Mínimo 1
        if ($idInsumo <= 0) { continue; }

        // Obtener detalle actual del remito para ese insumo
        $stmt = $db->prepare("SELECT cantidad FROM remitos_detalle WHERE id_remito = ? AND id_insumo = ? FOR UPDATE");
        $stmt->execute([$idRemito, $idInsumo]);
        $det = $stmt->fetch();
        if (!$det) { continue; }

        $cantAsignada = (int)$det['cantidad'];
        $aDevolver = min($cantidadDev, $cantAsignada);

        // Recuperar info del insumo (incluir campos de stock dual)
        $stmt = $db->prepare("SELECT tipo_insumo, cantidad, cantidad_oficina, cantidad_deposito FROM insumos WHERE id_insumo = ? FOR UPDATE");
        $stmt->execute([$idInsumo]);
        $ins = $stmt->fetch();
        if (!$ins) { continue; }

        if ($ins['tipo_insumo'] === 'Varios') {
            // Devolver a oficina (stock disponible para asignaciones)
            $stockOficina = (int)($ins['cantidad_oficina'] ?? $ins['cantidad']);
            $stockDeposito = (int)($ins['cantidad_deposito'] ?? 0);
            
            $nuevoStockOficina = $stockOficina + $aDevolver;
            $nuevoStockTotal = $nuevoStockOficina + $stockDeposito;
            
            $db->prepare("UPDATE insumos SET cantidad = ?, cantidad_oficina = ?, estado = 'Disponible', id_sede_actual = NULL, id_area_asignacion_actual = NULL WHERE id_insumo = ?")
               ->execute([$nuevoStockTotal, $nuevoStockOficina, $idInsumo]);
        } else {
            // Para unitarios, devolver cambia estado a Disponible
            $db->prepare("UPDATE insumos SET estado = 'Disponible', id_sede_actual = NULL, id_area_asignacion_actual = NULL WHERE id_insumo = ?")
               ->execute([$idInsumo]);
        }

        // Actualizar historial: incrementar cantidad_devuelta, NO borrar filas
        $db->prepare("UPDATE remitos_detalle SET cantidad_devuelta = LEAST(cantidad, cantidad_devuelta + ?) WHERE id_remito = ? AND id_insumo = ?")
           ->execute([$aDevolver, $idRemito, $idInsumo]);
           
        $devolverList[] = ['id' => $idInsumo, 'cantidad' => $aDevolver];
    }

    // Si ya no quedan items en el remito, marcar estado Devuelta y fecha_devolucion
    $restantes = $db->prepare("SELECT SUM(GREATEST(d.cantidad - COALESCE(d.cantidad_devuelta,0),0)) AS restantes FROM remitos_detalle d WHERE d.id_remito = ?");
    $restantes->execute([$idRemito]);
    $c = (int)$restantes->fetch()['restantes'];
    if ($c === 0) {
        $db->prepare("UPDATE remitos SET estado = 'Devuelta', fecha_devolucion = CURDATE() WHERE id_remito = ?")
           ->execute([$idRemito]);
    }

    $db->commit();
    
    // Registrar en auditoría
    registrarAuditoria(
        'devolver_insumos',
        'asignaciones',
        "Devolución de insumos - Remito: {$numero}",
        'asignacion',
        $idRemito,
        null,
        ['devueltos' => $devolverList, 'restantes' => $c]
    );
    
    json_success();
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) { $db->rollBack(); }
    
    // Registrar error en auditoría
    if (isset($numero)) {
        registrarAuditoria(
            'devolver_insumos',
            'asignaciones',
            "Error al devolver insumos - Remito: {$numero}",
            null,
            null,
            null,
            null,
            'error',
            $e->getMessage()
        );
    }
    
    json_error($e->getMessage(), 500);
}
?>

