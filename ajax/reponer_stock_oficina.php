<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

try {
    // Verificar CSRF
    if (!verify_csrf()) {
        throw new Exception('CSRF inválido');
    }

    // Obtener datos del request
    $input = json_decode(file_get_contents('php://input'), true);
    $idInsumo = isset($input['id_insumo']) ? (int)$input['id_insumo'] : 0;
    $cantidadReponer = isset($input['cantidad']) ? (int)$input['cantidad'] : 0;
    $observacion = isset($input['observacion']) ? trim($input['observacion']) : '';

    // Validaciones básicas
    if ($idInsumo <= 0) {
        throw new Exception('ID de insumo inválido');
    }

    if ($cantidadReponer <= 0) {
        throw new Exception('La cantidad a reponer debe ser mayor a 0');
    }

    $db = conectarDB();
    $db->beginTransaction();

    // Obtener el insumo y verificar que es tipo Varios
    $stmt = $db->prepare("SELECT * FROM insumos WHERE id_insumo = ? FOR UPDATE");
    $stmt->execute([$idInsumo]);
    $insumo = $stmt->fetch();

    if (!$insumo) {
        throw new Exception('Insumo no encontrado');
    }

    if ($insumo['tipo_insumo'] !== 'Varios') {
        throw new Exception('Solo se puede reponer stock para insumos tipo Varios');
    }

    $cantidadOficinaAntes = (int)($insumo['cantidad_oficina'] ?? 0);
    $cantidadDepositoAntes = (int)($insumo['cantidad_deposito'] ?? 0);

    // Verificar que hay suficiente stock en depósito
    if ($cantidadDepositoAntes < $cantidadReponer) {
        throw new Exception("Stock insuficiente en depósito. Disponible: {$cantidadDepositoAntes}, solicitado: {$cantidadReponer}");
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

    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'mensaje' => "Se repusieron {$cantidadReponer} unidades a oficina correctamente",
        'data' => [
            'cantidad_oficina' => $cantidadOficinaDespues,
            'cantidad_deposito' => $cantidadDepositoDespues,
            'cantidad_total' => $cantidadTotal
        ]
    ]);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
