<?php
require_once '../includes/config.php';

try {
    // Verificar CSRF
    if (!verify_csrf()) {
        json_error('CSRF inválido', 403);
    }

    // Obtener datos del request
    $input = json_decode(file_get_contents('php://input'), true);
    $idInsumo = isset($input['id_insumo']) ? (int)$input['id_insumo'] : 0;
    $cantidadReponer = isset($input['cantidad']) ? (int)$input['cantidad'] : 0;
    $observacion = isset($input['observacion']) ? trim($input['observacion']) : '';

    // Validaciones básicas
    if ($idInsumo <= 0) {
        json_error('ID de insumo inválido', 400);
    }

    if ($cantidadReponer <= 0) {
        json_error('La cantidad a reponer debe ser mayor a 0', 400);
    }

    $db = conectarDB();
    $db->beginTransaction();

    // Obtener el insumo y verificar que es tipo Varios
    $stmt = $db->prepare("SELECT * FROM insumos WHERE id_insumo = ? FOR UPDATE");
    $stmt->execute([$idInsumo]);
    $insumo = $stmt->fetch();

    if (!$insumo) {
        $db->rollBack();
        json_error('Insumo no encontrado', 404);
    }

    if ($insumo['tipo_insumo'] !== 'Varios') {
        $db->rollBack();
        json_error('Solo se puede reponer stock para insumos tipo Varios', 400);
    }

    $cantidadOficinaAntes = (int)($insumo['cantidad_oficina'] ?? 0);
    $cantidadDepositoAntes = (int)($insumo['cantidad_deposito'] ?? 0);

    // Verificar que hay suficiente stock en depósito
    if ($cantidadDepositoAntes < $cantidadReponer) {
        $db->rollBack();
        json_error("Stock insuficiente en depósito. Disponible: {$cantidadDepositoAntes}, solicitado: {$cantidadReponer}", 400);
    }

    // Calcular nuevas cantidades
    $cantidadOficinaDespues = $cantidadOficinaAntes + $cantidadReponer;
    $cantidadDepositoDespues = $cantidadDepositoAntes - $cantidadReponer;
    $cantidadTotal = $cantidadOficinaDespues + $cantidadDepositoDespues;

    // Actualizar el insumo
    $sqlUpdate = "UPDATE insumos 
                  SET cantidad_oficina = ?, 
                      cantidad_deposito = ?,
                      cantidad = ?
                  WHERE id_insumo = ?";
    
    $stmtUpdate = $db->prepare($sqlUpdate);
    $stmtUpdate->execute([
        $cantidadOficinaDespues,
        $cantidadDepositoDespues,
        $cantidadTotal,
        $idInsumo
    ]);

    // Registrar el movimiento en el historial
    $sqlMovimiento = "INSERT INTO insumos_movimientos_stock 
                      (id_insumo, tipo_movimiento, cantidad_movida, ubicacion_origen, ubicacion_destino,
                       cantidad_oficina_antes, cantidad_deposito_antes, 
                       cantidad_oficina_despues, cantidad_deposito_despues, 
                       observacion, fecha_movimiento)
                      VALUES (?, 'reposicion_oficina', ?, 'deposito', 'oficina', ?, ?, ?, ?, ?, NOW())";
    
    $stmtMovimiento = $db->prepare($sqlMovimiento);
    $stmtMovimiento->execute([
        $idInsumo,
        $cantidadReponer,
        $cantidadOficinaAntes,
        $cantidadDepositoAntes,
        $cantidadOficinaDespues,
        $cantidadDepositoDespues,
        $observacion
    ]);

    $db->commit();

    Logger::info('Stock repuesto de depósito a oficina', [
        'id_insumo' => $idInsumo,
        'cantidad' => $cantidadReponer,
        'oficina_antes' => $cantidadOficinaAntes,
        'oficina_despues' => $cantidadOficinaDespues,
        'deposito_antes' => $cantidadDepositoAntes,
        'deposito_despues' => $cantidadDepositoDespues
    ]);

    json_success([
        'mensaje' => "Se repusieron {$cantidadReponer} unidades a oficina correctamente",
        'cantidad_oficina' => $cantidadOficinaDespues,
        'cantidad_deposito' => $cantidadDepositoDespues,
        'cantidad_total' => $cantidadTotal
    ]);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    
    Logger::error('Error al reponer stock a oficina', [
        'mensaje' => $e->getMessage(),
        'id_insumo' => $idInsumo ?? 0,
        'cantidad' => $cantidadReponer ?? 0
    ]);
    
    json_error($e->getMessage(), 500);
}
